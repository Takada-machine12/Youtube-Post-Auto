<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);
//ファイルをインポート
require_once('config.php');
require_once('functions.php');

//Session宣言
session_start();

//ログインチェック機能
if (!isset($_SESSION['USER'])) {
    header('Location:'.SITE_URL.'/login.php');
    exit;
}

//セッション情報を取得
$user = $_SESSION['USER'];

//通知時間設定
$delivery_hours_array = array(
    "99" => "しない",
    "0" => "0時",
    "1" => "1時",
    "2" => "2時",
    "3" => "3時",
    "4" => "4時",
    "5" => "5時",
    "6" => "6時",
    "7" => "7時",
    "8" => "8時",
    "9" => "9時",
    "10" => "10時",
    "11" => "11時",
    "12" => "12時",
    "13" => "13時",
    "14" => "14時",
    "15" => "15時",
    "16" => "16時",
    "17" => "17時",
    "18" => "18時",
    "19" => "19時",
    "20" => "20時",
    "21" => "21時",
    "22" => "22時",
    "23" => "23時",
);

$error = array();
$complete_msg = "";

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //変数設定
    $delivery_hour = $_POST['delivery_hour'];

    //DB接続
    $pdo = connectDb();

    //入力チェック
    if ($delivery_hour == '') {
        $error['delivery_hour'] = '通知を設定してください。';
    }
    if (empty($error)) {
    //SQL準備
    $sql = 'update users 
            set 
            delivery_hour = :delivery_hour,
            updated_at = now() 
            where id = :id
    ';
    $stmt = $pdo->prepare($sql);
    //SQL実行
    $stmt->execute(array(
            ":id"=>$user['id'],
            ":delivery_hour"=>$delivery_hour
    ));
    //Session情報を更新
    $user['delivery_hour'] = $delivery_hour;
    $_SESSION['USER'] = $user;
    //メッセージ
    $complete_msg = '登録が完了しました。';
    }
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>ブログの設定画面 | <?php echo SERVICE_NAME; ?></title>
        <meta name="description" content="Youtubeで話題の動画を自動で取得、投稿できるシステム。自動投稿システム" />
        <meta name="keywords" content="自動投稿" />
        <link href="css/bootstrap.min.css" rel="stylesheet">
        <script src="//code.jquery.com/jquery.js"></script>
        <script src="js/bootstrap.min.js"></script>
        <link href="css/auto.css" rel="stylesheet">
    </head>

    <body id="main">
        <div class="nav navbar-inverse navbar-fixed-top">
            <div class="navbar-inner">
                <div class="container">
                    <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><?php echo SERVICE_SHORT_NAME; ?></a>
                    <ul class="nav navbar-nav">
                        <li><a href="./twitter_setting.php">X設定</a></li>
                        <li><a href="./youtube_setting.php">Youtube設定</a></li>
                        <li><a href="./blog_setting.php">ブログ設定</a></li>
                        <li class="active"><a href="./time_setting.php">投稿時間設定</a></li>
                        <li><a href="./user_edit.php">ユーザー情報設定</a></li>
                        <li><a href="./logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>設定</h1>
            <?php if ($complete_msg): ?>
                <div class="alert alert-success">
                    <?php echo $complete_msg; ?>
                </div>
            <?php endif; ?>
            <div class="alert alert-info">
                何時に投稿するか投稿時間を設定してください。
            </div>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php if(!empty($error['delivery_hour'])) {echo "has-error";} ?>">
                    <label>メール通知</label>
                    <?php echo arrayToSelect("delivery_hour", $delivery_hours_array, $user['delivery_hour']); ?>
                    <span class="help-block"><?php echo $error['delivery_hour'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" class="btn btn-success btn-block" value="登録" />
                </div><!-- form-group -->
                <!-- トークンをPOSTで送信 -->
                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken'] ?? ''); ?>" />
            </form>
            <a href="./home.php">戻る</a>
            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->
    </body>
</html>