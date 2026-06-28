<?php
//エラーメッセージ表示処理
ini_set('display_errors', 1);
error_reporting(E_ALL);

//必要なファイルの読み込み
require_once('config.php');
require_once('functions.php');
require_once('vendor/autoload.php');

//使用するクラスの宣言
//ブログ関連のクラス宣言
use PhpXmlRpc\Value;
use PhpXmlRpc\Request;
use PhpXmlRpc\Client;
use PhpXmlRpc\Encoder;

//Xのクラス宣言
use Abraham\TwitterOAuth\TwitterOAuth;

//文字コード指定
$GLOBALS['XML_RPC_defencoding'] = "UTF-8";

$pdo = connectDb();
$sql = 'select * from users';
$stmt = $pdo->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    //X情報取得
    $api_key = $user['api_key']; //XのAPI_KEY
    $api_key_secret = $user['api_key_secret']; //XのAPI_SECRET_KEY
    $access_token = $user['access_token']; //XのACCESS_TOKEN
    $access_token_secret = $user['access_token_secret']; //XのACCESS_TOKEN_SECRET

    //Blog情報取得
    $blog_host = $user['blog_host']; //ブログのホスト名
    $blog_xmlrpc_path = $user['blog_xmlrpc_path']; //ブログのXML_RPCパス
    $blog_user = $user['blog_user_name']; //ブログのユーザ名
    $blog_password = $user['blog_user_password']; //ブログのパスワード

    //Yotube動画取得
    $youtube_api_key = YOUTUBE_API_KEY;
    $category = $user['youtube_category'];
    $url = "https://www.googleapis.com/youtube/v3/videos?".
            "key=".$youtube_api_key.
            "&part=snippet".
            "&regionCode=JP".
            "&chart=mostPopular".
            "&maxResults=10".
            "&videoCategoryId=".$category
    ;

    //Youtube動画の解析
    $json = file_get_contents($url);
    $data = json_decode($json,true);

    //しっかりとデータが取得できているかどうか判定
    if (!isset($data['items'])) {
        //YoutubeAPI取得が失敗したら次のユーザの処理に進む
        continue;
    }

    //投稿時間を変数化
    $current_hour = date('G');

    if ($current_hour != $user['delivery_hour']) {
        //設定されている時間(投稿時間)と現在時刻が異なれば次のユーザの処理に進む
        continue;
    }

    foreach($data['items'] as $item) {
        $videoId = $item['id'];
        $snippet = $item['snippet'];
        $title = $snippet['title'];
        $description = $snippet["description"];
        $youtube_category = $snippet["categoryId"];
        $youtube_src = "https://youtube.com/embed/".$videoId;

        //投稿する前に同じ動画なのかどうかを判定
        //まずDBに情報を見にいく
        $sql = 'select id from posted_videos where user_id = :user_id and video_id = :video_id';
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array(
                        ':user_id'=>$user['id'],
                        ':video_id'=>$videoId
                    ));
        $posted = $stmt->fetch(PDO::FETCH_ASSOC);

        //同じ動画かどうか判定
        if ($posted) {
            //同じ動画であれば投稿せず処理を終了し、次の判定に進む
            continue;
        }
        //未投稿の動画であれば投稿
        //投稿内容を変数化
        $blog_id = '1';
        $blog_title = $title;
        $blog_description = $description.
                            '<div class="youtube-box">'.
                            '<iframe src="'.$youtube_src.'" frameborder="0"></iframe>'.
                            '</div>';
        $blog_category = $youtube_category;

        //XML_RPCクライアント作成
        $client = new Client($blog_xmlrpc_path,$blog_host,80);

        //投稿ブログに送信するためのデータ準備
        $content = new Value(
            array(
                'title'=>new Value($blog_title,'string'),
                'description'=>new Value($blog_description,'string'),
                'categories'=>new Value(array(new Value($blog_category,"string")),'array'),
                'dateCreated'=>new Value(time(),'dateTime.iso8601')
            ),
            'struct'
        );

        //XML_RPCメッセージ作成
        $message = new Request(
            'metaWeblog.newPost',
            array(
                new Value($blog_id,'string'),
                new Value($blog_user,'string'),
                new Value($blog_password,'string'),
                $content,
                new Value(1,'boolean') //公開は1,下書きは0
            )
        );

        //XML_RPCサーバにメッセージ送信
        $result = $client->send($message);
        //サーバへの送信が成功したかどうか判定
        if ($result->faultCode()) {
            //失敗したら以降の処理を終了し次の判定に進む
            continue;
        }

        //投稿IDを取得
        //デコーダのインスタンス生成
        $encoder = new Encoder();
        //XML-RPCレスポンスを通常のphp値に変換
        $post_id = $encoder->decode($result->value());

        //記事を取得
        $get_message = new Request(
            'metaWeblog.getPost',
            array(
                new Value($post_id,'int'),
                new Value($blog_user,'string'),
                new Value($blog_password,'string')
            )
        );

        $get_result = $client->send($get_message);
        //サーバへの送信が成功したかどうか判定
        if ($get_result->faultCode()) {
            //失敗したら以降の処理を終了し次の判定に進む
            continue;
        }

        //記事情報を取得
        $get_post = $encoder->decode($get_result->value());

        //Xへの投稿準備
        $title = $get_post['title'];
        $link = $get_post['link'];

        //OAuthインスタンス生成
        $connection = new TwitterOAuth($api_key, $api_key_secret, $access_token, $access_token_secret);
        $connection->setApiVersion('2');

        //Xポスト生成
        $post_message = 
                        $title .
                        "\n" .
                        $link;
        echo '<pre>';
        print_r($post_message);
        echo '</pre>';
        $res = $connection->post('tweets', ['text' => $post_message], ['jsonPayload' => true]);
        
        if (isset($res->data)) {
        	//正常終了
         $status = 'SUCCESS';
         $message = '投稿完了';
        	echo '投稿完了';
        } else {
        	//エラー
         $status = 'ERROR';
         $message = 'アクセストークン期限切れ';
        	echo 'エラー[' . $res->status . '] ' . $res->detail;
        }

        // if (isset($post_message)) {
        //     $status = 'SUCCESS';
        //     $message = '投稿完了';
        //     echo '投稿完了';
        // } else {
        //     $status = 'ERROR';
        //     $message = 'アクセストークン期限切れ';
        //     echo 'エラー';
        // }

        //投稿が完了したらDBに保存
        $sql1 = 'insert into posted_videos(user_id, video_id, blog_posted_id) values(:user_id, :video_id, :blog_posted_id)';
        $stmt = $pdo->prepare($sql1);
        $stmt->execute(array(
                        ':user_id'=>$user['id'],
                        ':video_id'=>$videoId,
                        ':blog_posted_id'=>$post_id
                    ));
        $sql2 = 'insert into cron_log(user_id, cron_message, created_at) values(:user_id, :cron_message, now())';
        $stmt = $pdo->prepare($sql2);
        $stmt->execute(array(
                        ':user_id'=>$user['id'],
                        ':cron_message'=>$message
                    ));
        break;
    }
}
?>