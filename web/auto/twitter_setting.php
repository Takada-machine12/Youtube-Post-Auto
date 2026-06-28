<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);
//ファイルをインポート
require_once('config.php');
//ライブラリをインポート
require_once ('vendor/autoload.php');
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
$api_key = $user['api_key'] ?? '';
$api_key_secret = $user['api_key_secret'] ?? '';
$access_token = $user['access_token'] ?? '';
$access_token_secret = $user['access_token_secret'] ?? '';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //必要情報の変数化
    $api_key = $_POST['api_key'] ?? '';
    $api_key_secret = $_POST['api_key_secret'] ?? '';
    $access_token = $_POST['access_token'] ?? '';
    $access_token_secret = $_POST['access_token_secret'] ?? '';

    $error = array();

    //DB接続
    $pdo = connectDb();

    //入力チェック
    if ($api_key == '') {
        $error['api_key'] = 'APIキーを設定してください。';
    }
    if ($api_key_secret == '') {
        $error['api_key_secret'] = 'APIシークレットキーを設定してください。';
    }
    if ($access_token == '') {
        $error['access_token'] = 'アクセストークンを設定してください。';
    }
    if ($access_token_secret == '') {
        $error['access_token_secret'] = 'アクセストークンシークレットキーを設定してください。';
    }
    if (empty($error)) {
        // ユーザーのカテゴリを更新
        $sql = 'update users 
                set 
                api_key = :api_key,
                api_key_secret = :api_key_secret,
                access_token = :access_token,
                access_token_secret = :access_token_secret,
                updated_at = now()
                where id = :id
        ';
        $stmt = $pdo->prepare($sql);
        //SQL実行
        $stmt->execute(array(
                ":api_key" => $api_key, 
                ":api_key_secret" => $api_key_secret, 
                ":access_token" => $access_token, 
                ":access_token_secret" => $access_token_secret, 
                ":id" => $user['id']
        ));
        //Session情報を更新
        $user['api_key'] = $api_key;
        $user['api_key_secret'] = $api_key_secret;
        $user['access_token'] = $access_token;
        $user['access_token_secret'] = $access_token_secret;
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
        <title>Xの設定画面 | <?php echo SERVICE_NAME; ?></title>
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
                        <li class="active"><a href="./twitter_setting.php">X設定</a></li>
                        <li><a href="./youtube_setting.php">Youtube設定</a></li>
                        <li><a href="./blog_setting.php">ブログ設定</a></li>
                        <li><a href="./time_setting.php">投稿時間設定</a></li>
                        <li><a href="./user_edit.php">ユーザー情報設定</a></li>
                        <li><a href="./logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>Xの設定画面</h1>
            <?php if ($complete_msg): ?>
                <div class="alert alert-success">
                    <?php echo $complete_msg; ?>
                </div>
            <?php endif; ?>
            <div class="alert alert-info">
                X Deveroper Portalで取得した各種キーを入力してください。
                入力した情報を利用して自動投稿を行います。
            </div>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php if(!empty($error['api_key'])) {echo "has-error";} ?>">
                    <label>API KEY</label>
                    <input type="text" class="form-control" name="api_key" value="<?php echo xss($api_key); ?>" placeholder="API KEY" />
                    <span class="help-block"><?php echo $error['api_key'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php if(!empty($error['api_key_secret'])) {echo "has-error";} ?>">
                    <label>API KEY SECRET</label>
                    <input type="text" class="form-control" name="api_key_secret" value="<?php echo xss($api_key_secret); ?>" placeholder="API KEY SECRET" />
                    <span class="help-block"><?php echo $error['api_key_secret'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php if(!empty($error['access_token'])) {echo "has-error";} ?>">
                    <label>ACCESS TOKEN</label>
                    <input type="text" class="form-control" name="access_token" value="<?php echo xss($access_token); ?>" placeholder="ACCESS TOKEN" />
                    <span class="help-block"><?php echo $error['access_token'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php if(!empty($error['access_token_secret'])) {echo "has-error";} ?>">
                    <label>ACCESS TOKEN SECRET</label>
                    <input type="text" class="form-control" name="access_token_secret" value="<?php echo xss($access_token_secret); ?>" placeholder="ACCESS TOKEN SECRET" />
                    <span class="help-block"><?php echo $error['access_token_secret'] ?? ''; ?></span>
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