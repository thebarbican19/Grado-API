<?

$_SERVER["HTTP_GDAPPKEY"] = "app_HAnda8123nsBZvs81sbZ7aoPi9s88s";
$_SERVER["HTTP_GDBEARER"] = "at_GRADMIN93712hd621hbn231dAWH2hs";

include '/home/gradoapp/public_html/api/v1/lib/auth.php';

//trending cleanup
$trending_current_date = date('Y-m-d H:i:s');
$trending_expired = date('Y-m-d H:i:s', strtotime($trending_current_date . ' - 10 day'));
$trending_query = mysql_query("SELECT * FROM  `trending` WHERE  `trending_timestamp` < '$trending_expired'");
$trending_delete_count = 0;
while($row = mysql_fetch_array($trending_query)) {		
	$trending_item = $row['trending_id'];
	$trending_delete =  mysql_query("DELETE FROM `trending` WHERE `trending_id` = '$trending_item';");
	if ($trending_delete) $trending_delete_count += 1;
	
}	

$content_current_date = date('Y-m-d H:i:s');
$content_expired = date('Y-m-d H:i:s', strtotime($content_current_date . ' - 80 day'));
$content_query = mysql_query("SELECT * FROM `content` WHERE `content_timestamp` < '$trending_expired' AND `content_hidden` = '1'");
while($row = mysql_fetch_array($content_query)) {		
	$content_item = $row['trending_id'];
	$content_delete =  mysql_query("DELETE FROM `content` WHERE `content_id` = '$content_item';");
	if ($content_delete) $trending_delete_count += 1;
	
}

echo "Deleted " . $trending_delete_count . " trending keywords and posts /n";
exit;

?>