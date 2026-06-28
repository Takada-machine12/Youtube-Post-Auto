<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);
//ファイルをインポート
require_once('config.php');
require_once('functions.php');
//Session宣言
session_start();

//DB接続(PDO方式)関数
$pdo = connectDb();

//リクエスト処理
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    //初めてアクセスした時の処理(GET)
    //自動ログイン(Cookie照合)
    if (isset($_COOKIE['AUTO'])) {
        //ランダムキー変数化
        $c_key = $_COOKIE['AUTO'];

        //自動ログインキーをDBで照合
        $sql = 'select * from auto_login where c_key = :c_key and expire >= :expire limit 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(":c_key"=>$c_key,":expire"=>date('Y-m-d H:i:s')));
        $auto_login_user = $stmt->fetch();

        if ($auto_login_user) {
            //自動ログイン　照合
            $user = getUserbyUserId($auto_login_user['user_id'],$pdo);

            //Sessionハイジャック対策(ID書き換え)
            session_regenerate_id(true);
            //ログイン情報をSessionに保存
            $_SESSION['USER'] = $user;

            //HOME画面に遷移
            header('Location:'.SITE_URL.'/index.php');
            unset($pdo);
            exit;
        }
    }
    //CSRF対策
    setToken();
} else {
    //CSRF対策
    checkToken();

    //フォームからサブミットされた時の処理(POST)
    //ユーザ情報を取得し変数化
    $user_email = $_POST['user_email'] ?? '';
    $user_password = $_POST['user_password'] ?? '';
    $is_user_submit = isset($_POST['user_email']) || isset($_POST['user_password']);

    //管理者情報を取得し変数化（管理者フォームが送信された時のみ存在する）
    $admin_account = $_POST['admin_account'] ?? '';
    $admin_password = $_POST['admin_password'] ?? '';
    $is_admin_submit = isset($_POST['admin_account']) || isset($_POST['admin_password']);

    $error = array();

    //ユーザフォーム送信時のみ、ユーザ用バリデーションを実行する
    if ($is_user_submit) {
        //メールアドレス入力チェック処理
        if ($user_email  == '') {
            $error['user_email'] = 'メールアドレスを入力してください。';
        } elseif (!filter_var($user_email,FILTER_VALIDATE_EMAIL)) {
            $error['user_email'] = '形式が正しくありません。正しい形式のメールアドレスを入力してください。';
        } else {
            if (!checkEmail($user_email,$pdo)) {
                $error['user_email'] = 'このメールアドレスは登録されていません。';
            }
        }
        //パスワード入力チェック処理
        if ($user_password == '') {
            $error['user_password'] = 'パスワードを入力してください。';
        } else {
            if ($user_email && $user_password) {
                //getUser関数でユーザを検索
                $user = getUser($user_email, $pdo);
                if (!$user || !password_verify($user_password, $user['user_password'])){
                    $error['user_password'] = 'メールアドレスまたはパスワードが違います。';
                }
                //存在チェック
                // if (!$user) {
                //     $error['user_password'] = 'メールアドレスまたはパスワードが違います。';
                // }
            }
        }
    }

    // 管理者フォーム送信時のみ、管理者用バリデーションを実行する
    if ($is_admin_submit) {
        //管理者のアカウント名入力チェック処理
        if ($admin_account == '') {
            $error['admin_account'] = 'アカウント名を入力してください。';
        }
        //管理者のパスワード入力チェック処理
        if ($admin_password == '') {
            $error['admin_password'] = 'パスワードを入力してください。';
        } else {
            if ($admin_account && $admin_password) {
                //getAdmin関数で管理者を検索
                $admin_user = getAdmin($admin_account, $admin_password, $pdo);
                //存在チェック
                if (!$admin_user) {
                    $error['admin_password'] = 'アカウント名またはパスワードが違います。';
                }
            }
        }
    }

    //ユーザログイン成功時
    if ($is_user_submit && empty($error)) {

        //Sessionハイジャック対策(ID書き換え)
        session_regenerate_id(true);
        //ログイン情報を保存するためにSessionに保存
        $_SESSION['USER'] = $user;
        $auto_login = $_POST['auto_login'];

        //一度Cookie情報をクリア(ゴミを残さないように)
        if (isset($_COOKIE['AUTO'])) {
            //古いキーを取得
            $c_key = $_COOKIE['AUTO'];

            //Cookie情報をクリア
            setcookie('AUTO','',time()-86400,'/mydev/webapitest/web/auto/');
            //DB情報もクリア
            $sql = 'delete from auto_login where c_key = :c_key';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(":c_key"=>$c_key));
        }

        //自動ログインの場合は以下処理実行
        if ($auto_login) {
            //自動ログインキー生成
            $c_key = sha1(uniqid(mt_rand(), true));

            //Cookie情報保存
            setcookie('AUTO',$c_key,time()+3600*24*365,'/mydev/webapitest/web/auto/');

            //DB登録
            $sql = 'insert into auto_login (user_id,c_key,expire,created_at,updated_at) values (:user_id,:c_key,:expire,now(),now())';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array(":user_id"=>$user['id'],":c_key"=>$c_key,":expire"=>date('Y-m-d H:i:s',time()+3600*24*365)));
        }

        //home画面に遷移
        header('Location: '.SITE_URL.'/home.php');
        //処理終了
        exit;
    }

    //管理者ログイン成功時
    if ($is_admin_submit && empty($error)) {
        //Sessionハイジャック対策
        session_regenerate_id(true);
        //ログイン情報を保持するためSessionに保存
        $_SESSION['ADMIN_USER'] = $admin_user;

        //管理者画面に遷移
        header('Location:'.SITE_URL.'/admin_home.php');
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
        <title>TOPページ | <?php echo SERVICE_NAME; ?></title>
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
            <div class="row"><!-- 一つのrowの中では合計で12列作れる。 -->
                <div class="col-md-9">
                    <div class="jumbotron">
                        <h1>動画自動取得・投稿システム</h1>
                        <p>人気の動画を自動で取得して自分のブログに投稿しよう！<br />毎日人気の動画をご自分のブログに投稿できるサービスです。</p>
                        <p><a href="./signup.php" class="btn btn-success btn-lg">新規ユーザー登録(無料) &raquo;</a></p>
                    </div><!-- jumbotron -->

                    <div class="row">
                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title">どんなことに使えるの？</h2>
                                </div><!-- panel-heading -->
                                <div class="panel-body">
                                    <p>Youtubeで取得してきた人気の動画をそのままご自分のブログやXに投稿することができます。</p>
                                </div><!-- panel-body -->
                            </div><!-- panel panel-default -->
                        </div><!-- col-md-4 -->

                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title">お金がかかる？</h2>
                                </div><!-- panel-heading -->
                                <div class="panel-body">
                                    <p>Xに投稿する時のみお金がかかります。</p>
                                </div><!-- panel-body -->
                            </div><!-- panel panel-default -->
                        </div><!-- col-md-4 -->

                        <div class="col-md-4">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h2 class="panel-title">投稿内容は他の方にも見られるの？</h2>
                                </div><!-- panel-heading -->
                                <div class="panel-body">
                                    <p>投稿した内容は他の方も見ることができます。</p>
                                </div><!-- panel-body -->
                            </div><!-- panel panel-default -->
                        </div><!-- col-md-4 -->
                    </div><!-- row -->
                </div><!-- col-md-9 -->

                <div class="col-md-3">
                    <div class="sidebar-nav panel panel-default">
                        <div class="panel-heading">
                            <h2 class="panel-title">ログイン</h2>
                        </div><!-- panel-heading -->
                        <div class="panel-body">
                            <form method="POST">

                                <div class="form-group <?php echo !empty($error['user_email']) ? 'has-error':''; ?>">
                                    <label>メールアドレス</label>
                                    <input type="email" class="form-control" name="user_email" value="<?php echo xss($user_email ?? ''); ?>" placeholder="メールアドレス" />
                                    <span class="help-block"><?php echo $error['user_email'] ?? ''; ?></span>
                                </div><!-- form-group -->

                                <div class="form-group <?php echo !empty($error['user_password']) ? 'has-error':''; ?>">
                                    <label>パスワード</label>
                                    <input type="password" class="form-control" name="user_password" placeholder="パスワード" />
                                    <span class="help-block"><?php echo $error['user_password'] ?? ''; ?></span>
                                </div><!-- form-group -->

                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary btn-block" value="ログイン" />
                                </div><!-- form-group -->
                                
                                <div class="form-group">
                                    <label><input type="checkbox" name="auto_login">次回から自動ログイン</label>
                                </div><!-- form-group -->
    
                                <div class="form-group">
                                    <a href="reminder.php">パスワードを忘れた方はこちら。</a>
                                </div><!-- form-group -->
                                <!-- トークンをPOSTで送信 -->
                                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken']); ?>" />
                            </form>
                        </div><!-- panel-body -->
                    </div><!-- sidebar-nav panel panel-default -->
                    <div class="sidebar-nav panel panel-default">
                        <div class="panel-heading">
                            <h2 class="panel-title">管理者ログイン</h2>
                        </div><!-- panel-heading -->
                        <div class="panel-body">
                            <form method="POST">

                                <div class="form-group <?php echo !empty($error['admin_account']) ? 'has-error':''; ?>">
                                    <label>管理者アカウント名</label>
                                    <input type="text" class="form-control" name="admin_account" value="<?php echo xss($admin_account ?? ''); ?>" placeholder="管理者アカウント名" />
                                    <span class="help-block"><?php echo $error['admin_account'] ?? ''; ?></span>
                                </div><!-- form-group -->

                                <div class="form-group <?php echo !empty($error['admin_password']) ? 'has-error':''; ?>">
                                    <label>管理者パスワード</label>
                                    <input type="password" class="form-control" name="admin_password" placeholder="管理者パスワード" />
                                    <span class="help-block"><?php echo $error['admin_password'] ?? ''; ?></span>
                                </div><!-- form-group -->

                                <div class="form-group">
                                    <input type="submit" class="btn btn-primary btn-block" value="ログイン" />
                                </div><!-- form-group -->
                                <!-- トークンをPOSTで送信 -->
                                <input type="hidden" name="token" value="<?php echo xss($_SESSION['sstoken']); ?>" />
                            </form>
                        </div>
                    </div><!-- sidebar-nav panel panel-default -->
                </div><!-- col-md-3 -->
            </div><!-- row -->
            <hr>
            <footer class="footer">
                <p><?php echo COPYRIGHT; ?></p>
            </footer><!-- footer -->
        </div><!-- container -->

    </body>
</html>