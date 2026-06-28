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
    header('Location:'.SITE_URL.'/index.php');
    exit;
}

//セッション情報を取得
$admin_user = $_SESSION['ADMIN_USER'];
?>

<!DOCTYPE html>
<html lang="ja">
    <head>
        <meta charset="utf-8" />
        <title>管理者HOMEページ | <?php echo SERVICE_NAME; ?></title>
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
                        <li class="active"><a href="./admin_logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>HOME</h1>

            <div class="list-group">
                <a href="admin_user_news.php" class="list-group-item">
                    <h4 class="list-group-item-heading">ユーザーへのお知らせ</h4>
                    <p class="list-group-item-text">ユーザーへのお知らせを登録</p>
                </a>
                <a href="admin_user_list.php" class="list-group-item">
                    <h4 class="list-group-item-heading">ユーザー登録一覧</h4>
                    <p class="list-group-item-text">登録されているユーザーの一覧を表示</p>
                </a>
            </div><!-- list-group -->

            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->
    </body>
</html>