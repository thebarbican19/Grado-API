<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_exclude = explode(",", $_GET['include']);

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;
if (empty($passed_include)) $passed_include = explode(",", "text,url,video,image,audio");

$passed_pagenation = $passed_pagenation * $passed_limit;

foreach ($passed_include as $include) {
	$include_injection .= "`content_type` LIKE '$include'";
	$include_count ++;
	if ($include_count != count($passed_include)) $include_injection .= " OR ";
	
}

$include_injection =  "(" . $include_injection . ") AND ";

if ($passed_method == 'GET') {
	$stream_query = mysqli_query("SELECT * FROM `content` WHERE $include_injection `content_owner` LIKE '$authuser_key' ORDER BY `content_id` DESC LIMIT $passed_pagenation, $passed_limit");
	$stream_count = mysql_num_rows($stream_query);
	while($row = mysql_fetch_array($stream_query)) {	
		$stream_timestamp = $row['content_timestamp'];
		$stream_key = $row['content_key'];
		$stream_type = $row['content_type'];
		$stream_title = str_replace("\'", "'", $row['content_title']);
		$stream_title = html_entity_decode($stream_title);					
		$stream_description = explode(" ", $row['content_description']);
		$stream_description = html_entity_decode($stream_description);				
		if (count($stream_description) > 60) {
			$stream_description = array_splice($stream_description, 0, 60);
			$stream_description = implode(" ", $stream_description) . "...";
			
		}
		else $stream_description = implode(" ", $stream_description);
		
		$stream_description = str_replace("\'", "'", $stream_description);
		$stream_message = str_replace("\'", "'", $row['content_message']);
		$stream_site = $row['content_site'];			
		$stream_url = $row['content_url'];
		$stream_image = explode(",http", $row['content_images']);
		$stream_image = reset($stream_image);
		$stream_media = $row['content_media'];		
		$stream_latitude = (float)$row['content_latitude'];
		$stream_longitude = (float)$row['content_longitude'];
		$stream_coordinates = array("lat" => $stream_latitude, "lng" => $stream_longitude);
		$stream_comments = (int)$row['content_comments'];
		$stream_hidden = (int)$row['content_hidden'];
		$stream_stats = return_stats($stream_key);
		$stream_api = array('key' => $row['content_appkey'], 'url' => $row['content_apiurl']);		
				
		$owner_data = array("key" => $authuser_key, "name" => $authuser_username, "avatar" => $authuser_avatar);	
		
		$liked_query = mysqli_query("SELECT * FROM `likes` WHERE  `like_user` LIKE '$authuser_key' AND `like_content` LIKE '$stream_key'");
		$liked_boolean = mysql_num_rows($liked_query);	
		
		if ($stream_hidden == 0 && isset($stream_key)) {							
			$stream_output[] = array("timestamp" => $stream_timestamp, "key" => $stream_key, "type" => $stream_type, "message" => $stream_message, "title" => $stream_title, "description" => $stream_description, "url" => $stream_url, "site" => $stream_site, "image" => $stream_image, "media" => $stream_media, "coordinates" => $stream_coordinates, "comments_allowed" => $stream_comments, "liked" => $liked_boolean, "stats" => $stream_stats, "owner" => $owner_data, "api" => $stream_api);
			
		}
		
	}
		
	if ($stream_count == 0) $stream_output = array();
	
	$json_status = $stream_count . ' items returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'content' => $stream_output);
	echo json_encode($json_output);
	exit;
						
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}
