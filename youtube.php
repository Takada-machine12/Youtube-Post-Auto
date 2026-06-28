<?php
$json = file_get_contents('youtube.json');
$arr = json_decode($json,true);
//$item = $arr["items"];
//$snippet = $item[0]["snippet"];
//echo $snippet["title"];
//echo "<br />";
//echo '<div class="youtube-box"><iframe src="https://youtube.com/embed/'.$item[0]["id"].'" frameborder="0"></iframe></div>';
//echo '<a href="'.$snippet["thumbnails"]["medium"].'"></a>';

foreach($arr["items"] as $item) {
    $snippet = $item["snippet"];
    echo '<h3>'.$snippet["title"].'</h3>';
    echo '<div class="youtube-box"><iframe src="https://youtube.com/embed/'.$item["id"].'" frameborder="0"></iframe></div>';
    echo '<a href="'.$snippet["thumbnails"]["medium"]["url"].'"></a>';
}
?>