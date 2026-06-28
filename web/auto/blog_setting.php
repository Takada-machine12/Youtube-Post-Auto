<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);
//ファイルをインポート
require_once('config.php');
//ライブラリをインポート
require_once('vendor/autoload.php');
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
$blog_host = $user['blog_host'] ?? '';
$blog_xmlrpc_path = $user['blog_xmlrpc_path'] ?? '';
$blog_user_name = $user['blog_user_name'] ?? '';
$blog_user_password = $user['blog_user_password'] ?? '';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //必要情報の変数化
    $blog_host = $_POST['blog_host'] ?? '';
    $blog_xmlrpc_path = $_POST['blog_xmlrpc_path'] ?? '';
    $blog_user_name = $_POST['blog_user_name'] ?? '';
    $blog_user_password = $_POST['blog_user_password'] ?? '';

    $error = array();

    //DB接続
    $pdo = connectDb();

    //入力チェック
    if ($blog_host == '') {
        $error['blog_host'] = 'ブログのホスト名を設定してください。';
    }
    if ($blog_xmlrpc_path == '') {
        $error['blog_xmlrpc_path'] = 'XML_RPCパスを設定してください。';
    }
    if ($blog_user_name == '') {
        $error['blog_user_name'] = 'ユーザー名を設定してください。';
    }
    if ($blog_user_password == '') {
        $error['blog_user_password'] = 'パスワードを設定してください。';
    }
    if (empty($error)) {
        // ユーザーのカテゴリを更新
        $sql = 'update users 
                set 
                blog_host = :blog_host,
                blog_xmlrpc_path = :blog_xmlrpc_path,
                blog_user_name = :blog_user_name,
                blog_user_password = :blog_user_password,
                updated_at = now()
                where id = :id
        ';
        $stmt = $pdo->prepare($sql);
        //SQL実行
        $stmt->execute(array(
                ":blog_host" => $blog_host, 
                ":blog_xmlrpc_path" => $blog_xmlrpc_path, 
                ":blog_user_name" => $blog_user_name, 
                ":blog_user_password" => $blog_user_password, 
                ":id" => $user['id']
        ));

        //Session情報を更新
        $user['blog_host'] = $blog_host;
        $user['blog_xmlrpc_path'] = $blog_xmlrpc_path;
        $user['blog_user_name'] = $blog_user_name;
        $user['blog_user_password'] = $blog_user_password;
        $_SESSION['USER'] = $user;

        //登録完了メッセージ
        $complete_msg = "各種設定キーが登録されました。";
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
                        <li class="active"><a href="./blog_setting.php">ブログ設定</a></li>
                        <li><a href="./time_setting.php">投稿時間設定</a></li>
                        <li><a href="./user_list.php">ユーザー情報設定</a></li>
                        <li><a href="./logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>ブログの設定画面</h1>
            <?php if ($complete_msg): ?>
                <div class="alert alert-success">
                    <?php echo $complete_msg; ?>
                </div>
            <?php endif; ?>
            <div class="alert alert-info">
                自分のブログの各種情報を入力してください。
                入力した情報を利用して自動投稿を行います。
            </div>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php if(!empty($error['blog_host'])) {echo "has-error";} ?>">
                    <label>ホスト名</label>
                    <input type="text" class="form-control" name="blog_host" value="<?php echo xss($blog_host); ?>" placeholder="ホスト名" />
                    <span class="help-block"><?php echo $error['blog_host'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php if(!empty($error['blog_xmlrpc_path'])) {echo "has-error";} ?>">
                    <label>XML_RPCパス</label>
                    <input type="text" class="form-control" name="blog_xmlrpc_path" value="<?php echo xss($blog_xmlrpc_path); ?>" placeholder="XML_RPCパス" />
                    <span class="help-block"><?php echo $error['blog_xmlrpc_path'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php if(!empty($error['blog_user_name'])) {echo "has-error";} ?>">
                    <label>ブログユーザー名</label>
                    <input type="text" class="form-control" name="blog_user_name" value="<?php echo xss($blog_user_name); ?>" placeholder="ブログユーザー名" />
                    <span class="help-block"><?php echo $error['blog_user_name'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php if(!empty($error['blog_user_password'])) {echo "has-error";} ?>">
                    <label>ブログのパスワード</label>
                    <input type="password" class="form-control" name="blog_user_password" value="" placeholder="ブログのパスワード" />
                    <span class="help-block"><?php echo $error['blog_user_password'] ?? ''; ?></span>
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
        </div>
    </body>
</html>