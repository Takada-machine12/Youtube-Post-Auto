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
$users = array();

if ($_SERVER['REQUEST_METHOD'] == 'GET') { 
    //DB接続
    $pdo = connectDb();

    //SQL
    $sql = 'select * from users';
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    unset($pdo);
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
            <div class="container">
            <h1>ユーザー情報一覧</h1>
            <?php if (!$users): ?>
                <div class="alert" id="message">ユーザーが登録されていません。</div>
            <?php endif; ?>
            <ul class="list-group">
                <table width="100%" border="1">
                    <?php foreach($users as $user): ?>
                        <tr>
                            <th>氏名</th><th>メールアドレス</th><th>登録日時</th><th></th>
                        </tr>
                        <tr>
                            <td><?php echo xss($user['user_screen_name']); ?></td>
                            <td><?php echo xss($user['user_email']); ?></td>
                            <td><?php echo xss($user['created_at']); ?></td>
                            <td><a href="admin_user_edit.php?id=<?php echo $user['id']; ?>" >[変更]</a></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </ul>
            <a href="admin_home.php">戻る</a>

            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->
    </body>
</html>