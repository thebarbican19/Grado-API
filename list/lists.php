<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_list = $_GET['key'];
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
	if (empty($passed_list)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$list_query = mysql_query("SELECT * FROM `lists` LEFT JOIN users on lists.list_owner LIKE users.user_key WHERE (`list_public` = 1 OR `list_owner` LIKE '$authuser_key') AND `list_key` LIKE '$passed_list' AND `list_hidden` = 0");
		$list_exists = mysql_num_rows($list_query);
		if ($list_exists == 1) {
			$list_data = mysql_fetch_assoc($list_query);	
			$list_key = $list_data['list_key'];
			$list_timestamp = $list_data['list_timestamp'];
			$list_updated = $list_data['list_updated'];
			$list_title = $list_data['list_title'];
			$list_summary = $list_data['list_description'];
			$list_type = $list_data['list_type'];
			$list_image = $list_data['list_image'];
			$list_featured = (int)$list_data['list_featured'];			
			$list_moderators = explode(",", $list_data['list_moderators']);
			$list_items = explode(",", $list_data['list_items']);
			$list_public = (int)$list_data['list_public'];
					
			$subscribers_query = mysql_query("SELECT `subscription_key`, COUNT(*) AS `subscription_count` FROM `subscriptions` WHERE `subscription_list` LIKE '$list_key'");
			$subscribers_data = mysql_fetch_assoc($subscribers_query);
			$subscribers_count = (int)$subscribers_data['subscription_count'];
			
			$owner_key = $list_data['user_key'];
			$owner_username = $list_data['user_name'];
			$owner_avatar = $list_data['user_avatar'];
			$owner_data = array("key" => $owner_key, "username" => $owner_username, "avatar" => $owner_avatar);	
			
			if ($authuser_key == $owner_key) $list_type = "admin";
			elseif (in_array($authuser_key, $list_moderators)) $list_type = "moderator";
			else $list_type = "user";
			
			foreach ($list_items as $content) {
				$content_sql = "SELECT * FROM `content` WHERE $include_injection `content_key` LIKE '$content' AND (content_public = 1 OR content_owner LIKE '$authuser_key') AND `content_hidden` = 0 LIMIT $passed_pagenation, $passed_limit";
				$content_query = mysql_query($content_sql);
				$content_exists = mysql_num_rows($content_query);
				if ($content_exists == 1) {
					$content_data = mysql_fetch_assoc($content_query);	
					$content_timestamp = $content_data['content_timestamp'];
					$content_key = $content_data['content_key'];
					$content_type = $content_data['content_type'];
					$content_message = $content_data['content_message'];		
					$content_title = $content_data['content_title'];
					$content_description = $content_data['content_description'];
					$content_site = $content_data['content_site'];				
					$content_url = $content_data['content_url'];
					$content_image = $content_data['content_images'];
					$content_image = reset(explode(",", $content_image));			
					$content_media = $content_data['content_media'];		
					$content_latitude = (float)$content_data['content_latitude'];
					$content_longitude = (float)$content_data['content_longitude'];
					$content_coordinates = array("lat" => $content_latitude, "lng" => $content_longitude);
					$content_comments = (int)$content_data['content_comments'];
					$content_hidden = (int)$content_data['content_hidden'];
					$content_stats = return_stats($content_key);
					$content_api = array('key' => $content_data['content_appkey'], 'url' => $content_data['content_apiurl']);		
							
					$like_query = mysql_query("SELECT * FROM `likes` WHERE `like_content` LIKE '$content_key'");
					$like_count = mysql_num_rows($like_query);
					
					$liked_query = mysql_query("SELECT * FROM `likes` WHERE  `like_user` LIKE '$authuser_key' AND `like_content` LIKE '$content_key'");
					$liked_boolean = mysql_num_rows($liked_query);
					
					if (isset($content_key)) {
						$content_output[] = array("timestamp" => $content_timestamp, "key" => $content_key, "type" => $content_type, "message" => $content_message, "title" => $content_title, "description" => $content_description, "site" => $content_site, "url" => $content_url, "image" => $content_image, "media" => $content_media, "coordinates" => $content_coordinates, "comments_allowed" => $content_comments, 'liked' => $liked_boolean, 'likes' => $like_count, "stats" => $content_stats, "owner" => $owner_data, "api" => $content_api);
						
					}
				
		
				}	
				else $content_output = array();
				
			}
			
			$list_items = count($item_list);
			$list_output = array("timestamp" => $list_timestamp, "key" => $list_key, "title" => $list_title, "summary" => $list_summary, "image" => $list_image, "items" => $list_count, "subscribers" => $subscribers_count, "usertype" => $list_type, 'featured' => $list_featured, 'public' => $list_public, "owner" => $owner_data, "items" => array_reverse($content_output));
					
			$json_status = 'list sucsessfully returned with ' . count($content_output) . " items";
			$json_output[] = array('status' => $json_status, 'error_code' => '200', 'list' => $list_output);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'list does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '311');
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>