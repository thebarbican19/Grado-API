<?

include '../lib/auth.php';
include '../lib/analytics.php';
include '../lib/trending.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_type = $_GET['type'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_include = explode(",", $_GET['include']);
$passed_location = explode(",", $_GET['latlng']);
$passed_search = $_GET['query'];
$passed_key = $_GET['key'];

$passed_latitude = reset($passed_location);
$passed_longitude = end($passed_location);

if (empty($_GET['limit'])) $passed_limit = 20;
if (empty($_GET['pangnation'])) $passed_pagenation = 0;
if (empty($_GET['include'])) $passed_include = explode(",", "text,url,video,image,audio");

$passed_pagenation = $passed_pagenation * $passed_limit;

foreach ($passed_include as $include) {
	$include_injection .= "`content_type` LIKE '$include'";
	$include_count ++;
	if ($include_count != count($passed_include)) $include_injection .= " OR ";
	
}

$include_injection =  "(" . $include_injection . ") AND ";

if ($passed_method == 'GET') {
	if (empty($passed_type) && empty($passed_key)) {
		$json_status = 'type or key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif ($passed_type == "search" && empty($passed_search)) {
		$json_status = 'query parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}		
	else {	
		if (isset($authuser_key)) {	
			$flagged_appended = 0;
			$flagged_query = mysql_query("SELECT * FROM `flagged` WHERE `flagged_sender` LIKE '$authuser_key'");	
			$flagged_count = mysql_num_rows($flagged_query);
			while($row = mysql_fetch_array($flagged_query)) {	
				$flagged_item = $row['flagged_content'];
				$flagged_injection .= "`content_key` NOT LIKE '$flagged_item'";
				$flagged_appended ++;
				if ($flagged_appended != $flagged_count) $flagged_injection .= " AND ";
			
			}
			
			if ($flagged_count > 0) $flagged_injection =  "(" . $flagged_injection . ") AND ";
			
			$exclude_query = mysql_query("SELECT * FROM  `exclude` WHERE  `exclude_user` LIKE  '$authuser_key'");	
			while($row = mysql_fetch_array($exclude_query)) {	
				$exclude_tag[] = $row['exclude_tag'];
				
			}	
			
		}
							
		if (isset($passed_type)) {
			if ($passed_type == "trending") {
				$expiry_query = mysql_query("SELECT * FROM `content` WHERE `content_public` = 1 AND `content_hidden` = 0 ORDER BY `content_timestamp` DESC LIMIT 200, 1");
				$expiry_data = mysql_fetch_assoc($expiry_query);
				$expiry_timestamp = $expiry_data['content_timestamp'];
				
				$stream_sql = "SELECT *, COUNT(*) FROM likes LEFT JOIN content on likes.like_content LIKE content.content_key LEFT JOIN users on content.content_owner LIKE users.user_key WHERE $include_injection $flagged_injection like_timestamp > '$expiry_timestamp' AND (content_public = 1 OR content_owner LIKE '$authuser_key') AND content_hidden = 0 AND user_status LIKE 'active' GROUP BY like_content ORDER BY COUNT(*) DESC, like_timestamp DESC LIMIT $passed_pagenation, $passed_limit";			
				
			}
			elseif ($passed_type == "new") {
				$stream_sql = "SELECT * FROM `content` LEFT JOIN users on content.content_owner LIKE users.user_key  WHERE $include_injection $flagged_injection (content_public = 1 OR content_owner LIKE '$authuser_key') AND `content_hidden` = 0 AND user_status LIKE 'active' ORDER BY `content_timestamp` DESC LIMIT $passed_pagenation, $passed_limit";			
				
			}
			elseif ($passed_type == "nearby") {
				if (count($passed_location) == 2) {
					$stream_sql = "SELECT *, (3959 * acos(cos(radians($passed_latitude)) * cos(radians(content.content_latitude)) * cos(radians(content.content_longitude) - radians($passed_longitude)) + sin(radians($passed_latitude)) * sin(radians(content.content_latitude)))) AS content_distance FROM content LEFT JOIN users on content.content_owner LIKE users.user_key WHERE $include_injection $flagged_injection (content_public = 1 OR content_owner LIKE '$authuser_key') AND `content_hidden` = 0 AND user_status LIKE 'active' HAVING content_distance < 25 ORDER BY `content_distance` ASC, content_timestamp DESC LIMIT $passed_pagenation, $passed_limit";
					
				}
				else {
					$json_status = 'latlng parameter missing';
					$json_output[] = array('status' => $json_status, 'error_code' => 422);
					echo json_encode($json_output);
					exit;
					
				}
									
			}
			elseif ($passed_type == "search") {
				$search_keywords = explode(" ", $passed_search);
				$search_injection = " AND ";
				$search_keyword_count = count($search_keywords);		
				foreach ($search_keywords as $key) {
					$search_injection .= "(`content_message` LIKE '%$key%' OR `content_tags` LIKE '%$key%' OR `content_title` LIKE '%$key%' OR `content_description` LIKE '%$key%')";
					$search_injection_append ++;
					if ($search_injection_append != $search_keyword_count) $search_injection .= " AND ";
											
				}
				
				if ($search_keyword_count > 0) $search_injection .=  " AND ";
			
				$stream_sql = "SELECT *, COUNT(*) FROM likes LEFT JOIN content on likes.like_content LIKE content.content_key LEFT JOIN users on content.content_owner LIKE users.user_key WHERE $include_injection $flagged_injection like_timestamp > '$expiry_timestamp' $search_injection (content_public = 1 OR content_owner LIKE '$authuser_key') AND content_hidden = 0 AND user_status LIKE 'active' GROUP BY like_content ORDER BY COUNT(*) DESC, like_timestamp DESC LIMIT $passed_pagenation, $passed_limit";			
				
			}			
			
			$stream_query = mysql_query($stream_sql);		
			$stream_count = mysql_num_rows($stream_query);
			while($row = mysql_fetch_array($stream_query)) {		
				$stream_timestamp = $row['content_timestamp'];
				$stream_key = $row['content_key'];
				$stream_type = $row['content_type'];
				$stream_title = html_entity_decode($row['content_title'], ENT_QUOTES);		
				$stream_description = explode(" ", $row['content_description']);
				if (count($stream_description) > 60) {
					$stream_description = array_splice($stream_description, 0, 60);
					$stream_description = implode(" ", $stream_description) . "...";
					
				}
				else $stream_description = implode(" ", $stream_description);
	
				$stream_description = html_entity_decode($stream_description, ENT_QUOTES);
				$stream_message = html_entity_decode($row['content_message'], ENT_QUOTES);
				$stream_url = $row['content_url'];
				$stream_site = $row['content_site'];		
				$stream_image = explode(",http", $row['content_images']);
				$stream_image = reset($stream_image);
				$stream_share = "http://gradoapp.com/i-" . substr(end(explode("_", $stream_key)) , 0, 9);	
				$stream_icon = "https://logo.clearbit.com/" . parse_url($stream_url, PHP_URL_HOST);
				$stream_tags = explode(",", $row['content_tags']);
				$stream_owner = $row['content_owner'];				
				$stream_latitude = (float)$row['content_latitude'];
				$stream_longitude = (float)$row['content_longitude'];
				$stream_coordinates = array("lat" => $stream_latitude, "lng" => $stream_longitude);
				$stream_hidden = (int)$row['content_hidden'];
				$stream_stats = return_stats($stream_key);
				$stream_api = array('key' => $row['content_appkey'], 'url' => $row['content_apiurl']);		
								
				$owner_key = $row['user_key'];
				$owner_username = $row['user_name'];
				$owner_avatar = $row['user_avatar'];
				if (empty($owner_key)) $owner_data = array();
				else $owner_data = array("key" => $owner_key, "name" => $owner_username, "avatar" => $owner_avatar);				
					
				if (isset($authuser_key)) {
					$liked_query = mysql_query("SELECT * FROM `likes` WHERE  `like_user` LIKE '$authuser_key' AND `like_content` LIKE '$stream_key'");
					$liked_boolean = mysql_num_rows($liked_query);
					
				}	
				else $liked_boolean = 0;
			
				$stream_hide = 0;
				foreach ($stream_tags as $tag) {
					if (in_array($tag, $exclude_tag) && $passed_type != "search" && $stream_owner != $authuser_key) {
						$stream_hide = 1;
										
					}
					
				}
				
				
				if ($stream_hide == 0 && isset($stream_key)) {
					$stream_output[] = array("timestamp" => $stream_timestamp, "key" => $stream_key, "type" => $stream_type, "message" => $stream_message, "title" => $stream_title, "description" => $stream_description, "url" => $stream_url, "site" => $stream_site, "image" => $stream_image, "icon" => $stream_icon, "coordinates" => $stream_coordinates, "share" => $stream_share, "liked" => $liked_boolean, "stats" => $stream_stats, "owner" => $owner_data, 'api' => $stream_api);
					
				}
				
			}
		
			if ($stream_count == 0) $stream_output = array();
	
			$json_status = $stream_count . ' items returned';
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'content' => $stream_output);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$stream_query = mysql_query("SELECT * FROM `content` WHERE `content_key` LIKE '%$passed_key%' AND `content_hidden` = 0 LIMIT 0 ,1");		
			$stream_count = mysql_num_rows($stream_query);
			if ($stream_count == 1) {
				$stream_data = mysql_fetch_assoc($stream_query);					
				$stream_timestamp = $stream_data['content_timestamp'];
				$stream_key = $stream_data['content_key'];
				$stream_type = $stream_data['content_type'];
				$stream_title = htmlspecialchars_decode($stream_data['content_title'], ENT_QUOTES);
				$stream_description = htmlspecialchars_decode($stream_data['content_description'], ENT_QUOTES);
				$stream_message = htmlspecialchars_decode($stream_data['content_message'], ENT_QUOTES);
				$stream_url = $stream_data['content_url'];
				$stream_site = $stream_data['content_site'];		
				$stream_icon = $stream_data['content_icon'];
				$stream_image = explode(",", $stream_data['content_images']);
				$stream_appurl = $stream_data['content_apiurl'];		
				$stream_appkey = $stream_data['content_appkey'];	
				$stream_app = array("key" => $stream_appkey, "url" => $stream_appurl, "name" => str_replace(".com", "", $stream_site));
				$stream_tags = explode(",", $stream_data['content_tags']);											
				$stream_owner = $stream_data['content_owner'];		
				$stream_latitude = (float)$stream_data['content_latitude'];
				$stream_longitude = (float)$stream_data['content_longitude'];
				$stream_coordinates = array("lat" => $stream_latitude, "lng" => $stream_longitude);
				$stream_comments = (int)$stream_data['content_comments'];
				$stream_hidden = (int)$stream_data['content_hidden'];		
				$stream_api = array('key' => $stream_data['content_appkey'], 'url' => $stream_data['content_apiurl']);		
								
				$stream_user_query = mysql_query("SELECT * FROM `users` WHERE `user_key` LIKE '$stream_owner'");
				$stream_user_data = mysql_fetch_assoc($stream_user_query);
				$stream_user_status = $stream_user_data['user_status'];				
				$stream_user_name = $stream_user_data['user_name'];
				$stream_user_avatar = $stream_user_data['user_avatar'];	
	
				if ($stream_user_status == "active") $stream_user_output = $comment_user_output = array("key" => $stream_owner, "username" => $stream_user_name, "avatar" => $stream_user_avatar);
				else $stream_user_output = array("key" => $stream_owner, "username" => "", "avatar" => "");
			
				$like_query = mysql_query("SELECT * FROM `likes` WHERE `like_content` LIKE '$stream_key'");
				$like_count = mysql_num_rows($like_query);
				
				$liked_query = mysql_query("SELECT * FROM `likes` WHERE  `like_user` LIKE '$authuser_key' AND `like_content` LIKE '$stream_key'");
				$liked_boolean = mysql_num_rows($liked_query);
				
				$stream_output = array("timestamp" => $stream_timestamp, "key" => $stream_key, "type" => $stream_type, "message" => $stream_message, "title" => $stream_title, "description" => $stream_description, "url" => $stream_url, "site" => $stream_site, "image" => $stream_image, "icon" => $stream_icon, "coordinates" => $stream_coordinates, "comments_allowed" => $stream_comments, "likes" => $like_count, "owner" => $stream_user_output, "tags" => $stream_tags, "app" => $stream_app, 'content_liked' => $liked_boolean, 'api' => $stream_api);
					
				
				$view_trending = add_trending($passed_key, $authuser_key);	
				$view_count = add_viewcount($stream_key, $authuser_key);
				
				$json_status = 'items returned';
				$json_output[] = array('status' => $json_status, 'error_code' => 200, 'content' => $stream_output);
				echo json_encode($json_output);
				exit;			
				
			}	
			else {
				$json_status = 'content does not exist';
				$json_output[] = array('status' => $json_status, 'error_code' => 404);
				echo json_encode($json_output);
				exit;
				
			}
			
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