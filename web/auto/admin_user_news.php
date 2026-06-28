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
$complete_msg = '';
$news_text = '';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //情報取得
    $news_text = $_POST['news_text'] ?? '';

    $error = array();
    
    //DB接続
    $pdo = connectDb();

    if ($news_text == '') {
        $error['news_text'] = 'お知らせを入力してください。';
    }

    if (empty($error)) {
        $sql = 'update admin_info
                set
                news_text = :news_text,
                updated_at = now()
                where id = :id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
                        ':news_text'=>$news_text,
                        ':id'=>$admin_user['id']
                    ));
        //Session情報更新
        $admin_user['news_text'] = $news_text;
        $_SESSION['ADMIN_USER'] = $admin_user;
        //登録完了メッセージ
        $complete_msg = 'お知らせ登録が完了しました。';
    }
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
                        <li class="active"><a href="./admin_user_news.php">お知らせ登録</a></li>
                        <li><a href="./admin_user_list.php">ユーザー登録一覧</a></li>
                        <li><a href="./admin_logout.php">ログアウト</a></li>
                    </ul><!-- ul -->
                </div><!-- container -->
            </div><!-- navbar-inner -->
        </div><!-- navbar-inverse -->

        <div class="container">
            <h1>お知らせ登録画面</h1>
            <?php if ($complete_msg): ?>
                <div class="alert alert-success">
                    <?php echo $complete_msg; ?>
                </div>
            <?php endif; ?>
            <div class="alert alert-info">
                ユーザーへのお知らせを登録してください。
            </div>
            <form method="POST" class="panel panel-default panel-body">
                <div class="form-group <?php if(!empty($error['news_text'])) {echo "has-error";} ?>">
                    <label>ユーザーへのお知らせ</label>
                    <textarea class="form-control" name="news_text" placeholder="ユーザーへのお知らせ"><?php echo xss($news_text); ?></textarea>
                    <span class="help-block"><?php echo $error['news_text'] ?? ''; ?></span>
                </div><!-- form-group -->

                <div class="form-group">
                    <input type="submit" class="btn btn-success btn-block" value="登録" />
                </div><!-- form-group -->
                <!-- トークンをPOSTで送信 -->
                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken'] ?? ''); ?>" />
            </form>
            <a href="./admin_home.php">戻る</a>
            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div>
    </body>
</html>