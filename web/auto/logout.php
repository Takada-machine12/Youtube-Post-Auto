<?php
//ファイルをインポート
require_once('config.php');
require_once('functions.php');
//Session宣言
session_start();

//DB接続
$pdo = connectDb();

//自動ログイン情報クリア
if (isset($_COOKIE['AUTO'])) {
    $c_key = $_COOKIE['AUTO'];

    //Cookie情報をクリア
    setcookie('AUTO','',time()-86400,'/mydev/webapitest/web/auto/');

    //DB情報もクリア
    $sql = 'delete from auto_login where c_key = :c_key';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(":c_key"=>$c_key));
}

//変数設定
$_SESSION = array();

//Cookie無効化
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(),'',time()-86400,'/mydev/webapitest/web/auto/');
}
//Session破棄
session_destroy();
unset($pdo);

header('Location:'.SITE_URL.'/index.php');
?>