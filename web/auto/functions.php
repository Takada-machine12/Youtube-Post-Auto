<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);
//ファイルをインポート
require_once('config.php');

//データベース接続
function connectDb() {
    //DB接続(PDO方式)
    //パラメータ設定
    $param = 'mysql:dbname='.DB_NAME.';host='.HOST;
    try {
        //接続
        $pdo = new PDO($param,USER,PASS);
        //文字コード設定
        $pdo->query('set names utf8');
        return $pdo;
    } catch (PDOException $e) {
        echo $e->getMessage();
        exit;
    }
}
//メールアドレス存在チェック
function checkEmail($user_email, PDO $pdo):bool {
    $sql = 'select * from users where user_email = :user_email limit 1';
    $stmt = $pdo->prepare($sql);

    //データを設定しSQLを実行
    $stmt->execute(array(":user_email"=>$user_email));

    //結果を取得し、変数に格納
    $user = $stmt->fetch();

    return $user ? true : false;
}
//メールアドレスとパスワードからuserを検索する
function getUser($user_email, $pdo) {
    $sql = 'select * from users where user_email = :user_email limit 1';
    $stmt = $pdo->prepare($sql);

    //実値設定とSQL実行
    $stmt->execute(array(':user_email'=>$user_email));

    //結果取得と変数格納
    $user = $stmt->fetch();

    return $user ? $user : false;
}

//管理者アカウント名とパスワードからuserを検索する
function getAdmin($admin_account, $admin_password, $pdo) {
    $sql = 'select * from admin_info where admin_account = :admin_account and binary admin_password = :admin_password limit 1';
    $stmt = $pdo->prepare($sql);

    //実値設定とSQL実行
    $stmt->execute(array(":admin_account"=>$admin_account,":admin_password"=>$admin_password));

    //結果取得と変数格納
    $admin_user = $stmt->fetch();

    return $admin_user ? $admin_user : false;
}

//通知時間の設定
function arrayToSelect($inputName,$srcArray,$selectedIndex="") {
    $temphtml = '<select class="form-control" name="'.$inputName.'">'.PHP_EOL;

    foreach($srcArray as $key=>$val) {
        if ($selectedIndex == $key) {
            $selectedText = 'selected="selected"';
        } else {
            $selectedText = '';
        }
        $temphtml .= '<option value="'.$key.'"'.$selectedText.'>'.$val.'</option>'.PHP_EOL;
    }

    $temphtml .= '</select>'.PHP_EOL;
    return $temphtml;
}

//XSS(クロスサイトスクリプティング)対策
function xss($original_str) {
    return htmlspecialchars($original_str,ENT_QUOTES,"UTF-8");
}

//CSRF対策(トークン生成)
function setToken() {
    //ランダムな文字列を生成し変数化
    $token = sha1(uniqid(mt_rand(), true));
    //Sessionに生成したトークンを保存
    $_SESSION['sstoken'] = $token;
}

//CSRF対策(生成したトークンをチェック)
function checkToken() {
    if (empty($_SESSION['sstoken'] || $_SESSION['sstoken'] != $_POST['token'])) {
        echo '<html><head><meta charset="utf-8"></head><body>不正なアクセスです。</body></html>';
        exit;
    }
}

//user_idからユーザを検索
function getUserbyUserId($user_id,$pdo) {
    $sql = 'select * from users where id = :user_id limit 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(":user_id"=>$user_id));
    $user = $stmt->fetch();

    return $user ? $user : false;
}

// ランダム文字列生成 (英数字)
function makeRandStr($length)
{
    $str = array_merge(range('a', 'z'), range('0', '9'), range('A', 'Z'));
    $r_str = null;
    for ($i = 0; $i < $length; $i++) {
        $r_str .= $str[rand(0, count($str) - 1)];
    }
    return $r_str;
}
?>