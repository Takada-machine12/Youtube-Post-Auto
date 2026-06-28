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
if (!isset($_SESSION['ADMIN_USER'])) {
    header('Location:'.SITE_URL.'/login.php');
    exit;
}

$admin_user = $_SESSION['ADMIN_USER'];
$error = array();
$users = array();
$complete_msg = '';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();

    //GETでのアクセス時は登録されているユーザ情報を表示
    $id = $_GET['id'] ?? '';

    //DB接続
    $pdo = connectDb();
    //SQL準備
    $sql = 'select user_screen_name,user_email,user_password from users where id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(':id'=>$id));
    //結果を取得
    $user = $stmt->fetch();
    if (!$user) {
        exit;
    }
    //結果を変数化
    $user_screen_name = $user['user_screen_name'];
    $user_email = $user['user_email'];
    $user_password = $user['user_password'];

    unset($pdo);
} else {
     //CSRF対策
    checkToken();

    //DB接続
    $pdo = connectDb();

    $id = $_POST['id'];
    $user_screen_name = $_POST['user_screen_name'];
    $user_email = $_POST['user_email'];
    $user_password = $_POST['user_password'];

    if ($_POST['action'] == '変更') {
        //変更ボタンを押下時、以下の処理を実行
        //そもそもユーザー情報が登録されていない場合は以下を表示
        if (empty($user_screen_name)) {
            $error['user_screen_name'] = 'ユーザー名が登録されていません。';
        }

        if (empty($user_email)) {
            $error['user_email'] = 'ユーザーメールが登録されていません。';
        }
        
        if (empty($error) && $user_password == '') {
            //SQL準備
            $sql = 'update users
                    set 
                    user_screen_name = :user_screen_name,
                    user_email = :user_email,
                    updated_at = now() 
                    where id = :id'
                ;
            $stmt = $pdo->prepare($sql);
            //SQL実行
            $stmt->execute(array(
                            ":id"=>$id,
                            ":user_screen_name"=>$user_screen_name,
                            ":user_email"=>$user_email,
                        ));
            $complete_msg = 'ユーザー情報が変更されました。';
        } elseif(empty($error) && $user_password != '') {
            //SQL準備
            $sql = 'update users
                    set 
                    user_screen_name = :user_screen_name,
                    user_email = :user_email,
                    user_password = :user_password,
                    updated_at = now() 
                    where id = :id'
                ;
            $stmt = $pdo->prepare($sql);
            //SQL実行
            $stmt->execute(array(
                            ":id"=>$id,
                            ":user_screen_name"=>$user_screen_name,
                            ":user_email"=>$user_email,
                            ":user_password"=>password_hash($user_password, PASSWORD_DEFAULT) //パスワードハッシュ化
                        ));
            $complete_msg = 'ユーザー情報が変更されました。';

            unset($pdo);
        }
    } elseif ($_POST['action'] == '退会') {
        //退会ボタンを押下後、以下の処理を実行
        //SQL
        $sql = 'delete from users where id = :id';
        $stmt = $pdo->prepare($sql);
        //SQL実行
        $stmt->execute(array(':id'=>$id));
        $complete_msg = 'ユーザー情報を削除しました。';

        //一覧画面へ遷移
        header('Location:'.SITE_URL.'/admin_user_list.php');
        unset($pdo);
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>お知らせ登録ページ | <?php echo SERVICE_NAME; ?></title>
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
                        <li><a href="./admin_user_news.php">お知らせ登録</a></li>
                        <li class="active"><a href="./admin_user_list.php">ユーザー登録一覧</a></li>
                        <li><a href="./admin_logout.php">ログアウト</a></li>
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
                <input type="hidden" name="id" value="<?php echo $id ?>" />
                <div class="form-group">
                    <input type="text" name="user_screen_name" class="form-control" value="<?php echo xss($user_screen_name); ?>" />
                    <span class="help-block"><?php echo $error['user_screen_name'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="text" name="user_email" class="form-control" value="<?php echo xss($user_email); ?>" />
                    <span class="help-block"><?php echo $error['user_email'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="password" name="user_password" class="form-control" value="" />
                    <span class="help-block"><?php echo $error['user_password'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" name="action" class="btn btn-primary btn-block" value="変更" />
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" name="action" class="btn btn-danger btn-block" value="退会" onclick="return confirm('本当に退会しますか？')" />
                </div><!-- form-group -->
                <!-- トークンをPOSTで送信 -->
                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken']); ?>" />
            </form>
            <a href="./admin_user_list.php">戻る</a>

            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->
    </body>
</html>