<?php
//ファイルをインポート
require_once('config.php');
require_once('functions.php');
//Session宣言
session_start();

//変数設定
$_SESSION = array();

//Cookie無効化
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(),'',time()-86400,'/mydev/webapitest/web/auto/');
}
//Session破棄
session_destroy();

header('Location:'.SITE_URL.'/index.php');
?>