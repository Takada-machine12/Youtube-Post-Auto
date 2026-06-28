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

$error = array();
$complete_msg = "";

//必要情報の変数化
$user_screen_name = $user['user_screen_name'] ?? '';
$user_email = $user['user_email'] ?? '';
$user_password = $user['user_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //必要情報の変数化
    $user_email = $_POST['user_email'] ?? '';
    $user_email = $_POST['user_email'] ?? '';
    $user_password = $_POST['user_password'] ?? '';

    //エラー処理のための変数化(配列で保持)
    $error = array();

    //DB接続(PDO方式)関数
    $pdo = connectDb();

    //ユーザーの氏名入力チェック処理
    if ($user_screen_name == '') {
        $error['user_screen_name'] = '氏名を入力してください。';
    }
    //ユーザーのメールアドレス入力チェック処理
    if ($user_email == '') {
        $error['user_email'] = 'メールアドレスを入力してください。';
    } elseif (!filter_var($user_email,FILTER_VALIDATE_EMAIL)) {
        $error['user_email'] = '形式が正しくありません。正しい形式のメールアドレスを入力してください。';
    } else {

        if (checkEmail($user_email,$pdo)) {
            $error['user_email'] = 'このメールアドレスは既に登録されています。';
        }
    }
    //ユーザーのパスワード入力チェック処理
    if ($user_password == '') {
        $error['user_password'] = 'パスワードを入力してください。';
    }

    if (empty($error)) {
        $sql = 'update users 
                set 
                user_screen_name = :user_screen_name,
                user_email = :user_email,
                user_password = :user_password,
                updated_at = now()
                where id = :id
        ';
        $stmt = $pdo->prepare($sql);

        //データを設定しSQLを実行
        $stmt->execute(array(
                ":user_screen_name"=>$user_screen_name,
                ":user_email"=>$user_email,
                ":user_password"=>$user_password,
                ":id"=>$user['id']
        ));

        //Session情報を更新
        $user['user_screen_name'] = $user_screen_name;
        $user['user_email'] = $user_email;
        $user['user_password'] = $user_password;
        $_SESSION['USER'] = $user;

        //更新完了メッセージ
        $complete_msg = "ユーザー情報を更新しました。";

        //管理者にメール通知
        $to = HOST_MAIL;
        $subject = SUBJECT_UPDATE;
        $message = '氏名:'.$user['user_screen_name'].PHP_EOL;
        $message .= 'メールアドレス:'.$user['user_email'];
        $header = $user['user_email'];
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");
        mb_send_mail($to,$subject,$message,$header);
    }
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>ユーザー情報設定画面 | <?php echo SERVICE_NAME; ?></title>
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
                        <li><a href="./time_setting.php">投稿時間設定</a></li>
                        <li class="active"><a href="./user_edit.php">ユーザー情報設定</a></li>
                        <li><a href="./logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>ユーザー情報編集</h1>
            <?php if($complete_msg): ?>
                <div class="alert alert-success">
                    <?php echo $complete_msg; ?>
                </div>
            <?php endif; ?>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php if(!empty($error['user_screen_name'])) {echo "has-error";} ?>">
                    <label>氏名</label>
                    <input type="text" name="user_screen_name" class="form-control" value="<?php echo xss($user_screen_name); ?>" />
                    <span class="help-block"><?php echo $error['user_screen_name'] ?? ''; ?></span>
                </div><!-- form-group -->
                <div class="form-group <?php if(!empty($error['user_screen_name'])) {echo "has-error";} ?>">
                    <label>メールアドレス</label>
                    <input type="text" name="user_email" class="form-control" value="<?php echo xss($user_email); ?>" />
                    <span class="help-block"><?php echo $error['user_email'] ?? ''; ?></span>
                </div><!-- form-group -->
                <div class="form-group <?php if(!empty($error['user_password'])) {echo "has-error";} ?>">
                    <label>パスワード</label>
                    <input type="password" name="user_password" class="form-control" value="" />
                    <span class="help-block"><?php echo $error['user_password'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" name="action" class="btn btn-primary btn-block" value="更新" />
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" name="action" class="btn btn-danger btn-block" value="退会" onclick="return confirm('本当に退会しますか？')" />
                </div><!-- form-group -->
                <!-- トークンをPOSTで送信 -->
                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken']); ?>" />
            </form>
            <a href="./home.php">戻る</a>

            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->
    </body>
</html>