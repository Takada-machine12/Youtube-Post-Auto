<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);
//ファイルをインポート
require_once('config.php');
require_once('functions.php');
//Session宣言
session_start();

//リクエスト処理
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //初めてアクセスした時の処理(GET)
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //フォームからサブミットされた時の処理(POST)
    //ユーザ情報を取得し変数化
    $user_screen_name = $_POST['user_screen_name'];
    $user_email = $_POST['user_email'];
    $user_password = $_POST['user_password'];

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
        $sql = 'insert into users (user_screen_name,user_email,user_password,delivery_hour,created_at,updated_at) values(:user_screen_name,:user_email,:user_password,99,now(),now())';
        $stmt = $pdo->prepare($sql);

        //データを設定しSQLを実行
        $stmt->execute(array(
                        ':user_screen_name'=>$user_screen_name,
                        ':user_email'=>$user_email,
                        ':user_password'=>password_hash($user_password, PASSWORD_DEFAULT) //パスワードハッシュ化
                    ));

        //自動ログイン機能
        //getUser関数で検索
        $user = getUser($user_email, $pdo);

        //ユーザ情報をSessionに保存
        $_SESSION['USER'] = $user;
        //Sessionハイジャック対策(ID書き換え)
        session_regenerate_id(true);
        //DB接続終了
        unset($pdo);

        //管理者にメール通知
        $to = HOST_MAIL;
        $subject = SUBJECT;
        $message = '氏名:'.$user['user_screen_name'].PHP_EOL;
        $message .= 'メールアドレス:'.$user['user_email'];
        $header = $user['user_email'];
        mb_language("Japanese");
        mb_internal_encoding("UTF-8");
        mb_send_mail($to,$subject,$message,$header);

        //登録完了画面に遷移
        header('Location: '.SITE_URL.'/signup_complete.php');

        //処理終了
        exit;
    }
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>ユーザー登録 | <?php echo SERVICE_NAME; ?></title>
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
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>ユーザー登録</h1>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php echo !empty($error['user_screen_name']) ? 'has-error':''; ?>">
                    <label>氏名</label>
                    <input type="text" name="user_screen_name" class="form-control" value="<?php echo xss($user_screen_name ?? ''); ?>" placeholder="氏名" />
                    <span class="help-block"><?php echo $error['user_screen_name'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php echo !empty($error['user_email']) ? 'has-error':''; ?>">
                    <label>メールアドレス</label>
                    <input type="email" name="user_email" class="form-control" value="<?php echo xss($user_email ?? ''); ?>" placeholder="メールアドレス" />
                    <span class="help-block"><?php echo $error['user_email'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group <?php echo !empty($error['user_password']) ? 'has-error':''; ?>">
                    <label>パスワード</label>
                    <input type="password" name="user_password" class="form-control" value="" placeholder="パスワード" />
                    <span class="help-block"><?php echo $error['user_password'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" class="btn btn-success btn-block" value="アカウント作成" />
                </div><!-- form-group -->
                <!-- トークンをPOSTで送信 -->
                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken']); ?>" />
            </form>
            <a href="./index.php">戻る</a>
            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->
    </body>
</html>