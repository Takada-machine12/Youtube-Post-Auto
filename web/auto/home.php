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
    header('Location:'.SITE_URL.'/index.php');
    exit;
}
//セッション情報を取得
$user = $_SESSION['USER'];

//DB接続
$pdo = connectDb();
//SQL(お知らせ情報取得)
$sql1 = 'select news_text from admin_info';
$stmt = $pdo->prepare($sql1);
$stmt->execute();
$admin_news = $stmt->fetch(PDO::FETCH_ASSOC);

//SQL(投稿情報取得)
$sql2 = 'select cron_message, created_at from cron_log where user_id = :user_id order by created_at desc limit 10';
$stmt = $pdo->prepare($sql2);
$stmt->execute(array(':user_id'=>$user['id']));
$cron_message = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>MENUページ | <?php echo SERVICE_NAME; ?></title>
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
                        <li class="active"><a href="./logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>MENU</h1>
            <p>
                <h4>ようこそ、<?php echo $_SESSION['USER']['user_screen_name']; ?>さん!</h4>
            </p>
            <div class="panel panel-default">
                <div class="panel-heading">
                    管理者からのお知らせ
                </div>
                <div class="panel-body">
                    <?php echo nl2br(xss($admin_news['news_text'])); ?>
                </div>
            </div>

            <div class="panel panel-default">
                <div class="panel-heading">
                    実行ログ
                </div>
                <div class="panel-body">
                    <?php foreach($cron_message as $log):?>
                        <?php echo xss($log['cron_message']); ?><br >
                        <?php echo xss($log['created_at']); ?><br >
                        <p>-----------------------------------------</p>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="list-group">
                <!-- ここに管理者が書いたお知らせとcron処理の実行ログを表示 -->
                <a href="twitter_setting.php" class="list-group-item">
                    <h4 class="list-group-item-heading">Xの設定</h4>
                    <p class="list-group-item-text">API KeyやAccess Tokenを設定</p>
                </a>
                <a href="youtube_setting.php" class="list-group-item">
                    <h4 class="list-group-item-heading">Youtubeの設定</h4>
                    <p class="list-group-item-text">Youtube動画のカテゴリを設定</p>
                </a>
                <a href="blog_setting.php" class="list-group-item">
                    <h4 class="list-group-item-heading">ブログの設定</h4>
                    <p class="list-group-item-text">ブログのユーザー名やホスト名、パスなどを設定</p>
                </a>
                <a href="time_setting.php" class="list-group-item">
                    <h4 class="list-group-item-heading">投稿時間の設定</h4>
                    <p class="list-group-item-text">ブログに投稿する時間を設定</p>
                </a>
                <a href="user_edit.php" class="list-group-item">
                    <h4 class="list-group-item-heading">ユーザー情報設定</h4>
                    <p class="list-group-item-text">ニックネームの編集などを設定</p>
                </a>
            </div>
            
            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </textarea><!-- container -->
    </body>
</html>