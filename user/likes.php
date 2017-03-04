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
	$likes_query = mysqli_query("SELECT * FROM `likes` LEFT JOIN content on likes.like_content LIKE content.content_key LEFT JOIN users on content.content_owner LIKE users.user_key WHERE $include_injection `like_user` LIKE '$authuser_key' AND content_key NOT LIKE '' AND (content_public = 1 OR content_owner LIKE '$authuser_key') AND content_hidden = 0 AND user_status LIKE 'active' ORDER BY `like_id` DESC LIMIT $passed_pagenation, $passed_limit");
	$likes_count = mysql_num_rows($likes_query);
	while($row = mysql_fetch_array($likes_query)) {	
		$like_key = $row['like_key'];
		$like_content = $row['like_content'];
		$like_timestamp = $row['like_timestamp'];
		$like_latitude = (float)$row['like_latitude'];
		$like_longitude = (float)$row['like_longitude'];
		$like_coordinates = array("lat" => $like_latitude, "lng" => $like_longitude);
		$like_output = array("timestamp" => $like_timestamp, "key" => $like_key, "coordinates" => $like_coordinates);
		
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
						
		$owner_key = $row['user_key'];
		$owner_username = $row['user_name'];
		$owner_avatar = $row['user_avatar'];
		if (empty($owner_key)) $owner_data = array();
		else $owner_data = array("key" => $owner_key, "name" => $owner_username, "avatar" => $owner_avatar);		
		
		if ($stream_hidden == 0 && isset($stream_key)) {				
			$stream_output[] = array("timestamp" => $stream_timestamp, "key" => $stream_key, "type" => $stream_type, "message" => $stream_message, "title" => $stream_title, "description" => $stream_description, "url" => $stream_url, "site" => $stream_site, "image" => $stream_image, "media" => $stream_media, "coordinates" => $stream_coordinates, "comments_allowed" => $stream_comments, "liked" => 1, "stats" => $stream_stats, "owner" => $owner_data, "api" => $stream_api);
			
		}
		
	}
		
	if ($likes_count == 0) $stream_output = array();
	
	$json_status = $likes_count . ' items returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'content' => $stream_output ,'sql' => "SELECT * FROM `likes` FROM likes LEFT JOIN content on likes.like_content LIKE content.content_key WHERE `like_user` LIKE '$authuser_key' AND content_key NOT LIKE '' AND content_hidden = 0 ORDER BY `like_id` DESC LIMIT $passed_pagenation, $passed_limit");
	echo json_encode($json_output);
	exit;
						
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}
