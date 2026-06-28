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

//Youtbeカテゴリ
$youtube_movies_array = array(
    "99" => "選択してください",
    "20" => "ゲーム",
    "17" => "スポーツ",
    "10" => "音楽",
    "25" => "ニュース",
    "24" => "エンタメ",
);

$error = array();
$complete_msg = "";

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //カテゴリ変数化
    $youtube_category = $_POST['youtube_category'] ?? '';

    //DB接続
    $pdo = connectDb();
    //入力チェック
    if ($youtube_category == '99') {
        $error['youtube_category'] = 'カテゴリを設定してください。';
    }
    if (empty($error)) {
        // ユーザーのカテゴリを更新
        $sql = 'update users 
                set 
                youtube_category = :youtube_category, 
                updated_at = now() 
                where id = :id
        ';
        $stmt = $pdo->prepare($sql);
        //SQL実行
        $stmt->execute(array(
                ":youtube_category" => $youtube_category, 
                ":id" => $user['id']
        ));
        //Session情報を更新
        $user['youtube_category'] = $youtube_category;
        $_SESSION['USER'] = $user;
        //登録完了メッセージ
        $complete_msg = "カテゴリが登録されました。";
    }
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>Youtubeの設定画面 | <?php echo SERVICE_NAME; ?></title>
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
                        <li class="active"><a href="./youtube_setting.php">Youtube設定</a></li>
                        <li><a href="./blog_setting.php">ブログ設定</a></li>
                        <li><a href="./time_setting.php">投稿時間設定</a></li>
                        <li><a href="./user_edit.php">ユーザー情報設定</a></li>
                        <li><a href="./logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>Youtubeの設定画面</h1>
            <?php if ($complete_msg): ?>
                <div class="alert alert-success">
                    <?php echo $complete_msg; ?>
                </div>
            <?php endif; ?>
            <div class="alert alert-info">
                自分の好きなカテゴリのYoutube動画を選んで登録してください。
            </div>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php if(!empty($error['youtube_category'])) {echo "has-error";} ?>">
                    <label>Youtube動画カテゴリ設定</label>
                    <?php echo arrayToSelect("youtube_category", $youtube_movies_array, $user['youtube_category']); ?>
                    <span class="help-block"><?php echo $error['youtube_category'] ?? ''; ?></span>
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