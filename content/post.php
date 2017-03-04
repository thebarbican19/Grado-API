<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/parser.php';
include '../lib/karma.php';
include '../lib/analytics.php';
include '../lib/image.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_content = str_replace("'", "\'", $passed_data['content']);
$passed_latlng = explode(",", $passed_data['latlng']);
$passed_latitude = reset($passed_latlng);
$passed_longitude = end($passed_latlng);
$passed_key =  $passed_data['key'];
$passed_comments =  $passed_data['comments'];
$passed_list =  $passed_data['list'];
$passed_visibility = $passed_data['public'];
$passed_file = $_FILES['image'];

if ($passed_comments == "1" || strcasecmp($passed_comments, "true") == 0 || strcasecmp($passed_comments, "yes") == 0 || !empty($passed_comments)) $passed_comments = "1";
else $passed_comments = "0";

if ($passed_visibility == "1" || strcasecmp($passed_visibility, "true") == 0 || strcasecmp($passed_visibility, "yes") == 0 || !isset($passed_visibility)) $passed_visibility = "1";
else $passed_visibility = "0";

if (empty($passed_latitude)) $passed_latitude = 0;
if (empty($passed_longitude)) $passed_longitude = 0;
	
if ($passed_method == 'POST') {
	if (!in_array("post", $application_scope)) {
		$json_status = 'application does not have the privileges to access this api';
		$json_output[] = array('status' => $json_status, 'error_code' => '371');
		echo json_encode($json_output);
		exit;
		
	}
	
	if (empty($passed_content)) {
		$json_status = 'content parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		preg_match_all('#\bhttps?://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#', $passed_content, $passed_url);
		
		$passed_url = reset(reset($passed_url));
		$passed_content = strip_tags($passed_content);
		$passed_content = str_replace($passed_url, "", $passed_content);
		$passed_content = preg_replace('/\s+/', ' ',$passed_content);
		$passed_content = trim($passed_content);
		$passed_content = str_replace("(null)", "", $passed_content);
				
		if (strlen($passed_content) > 160) {
			$json_status = 'content is too long, excluding urls the maxium characters limit is 160';
			$json_output[] = array('status' => $json_status, 'error_code' => '310');
			echo json_encode($json_output);
			exit;
			
		}
		else {
			if (isset($passed_file)) {
				$content_upload = upload_image($passed_file, $authuser_key, "content");
				$content_type = "image";			
				$content_parsed = array();
				$content_title = "";
				$content_description = "";
				$content_image =  $content_upload['file'];	
				$content_media = "";
				$content_url = "";
				$content_site = "";
				$content_api = "";
				$content_appkey = "";
				$content_icon = "";	
				
			}
			else if (empty($passed_url) && empty($passed_file)) {
				$content_parsed = parse_data("" ,$passed_content);				
				$content_type = "text";			
				$content_title = "";
				$content_description = "";
				$content_image = "";	
				$content_media = "";
				$content_url = "";
				$content_site = "";
				$content_api = "";
				$content_appkey = "";
				$content_icon = "";
				$content_comment = $content_parsed['message'];
								
			}
			else {
				$content_parsed = parse_data($passed_url ,$passed_content);
				$content_type = $content_parsed['type'];
				$content_title = $content_parsed['title'];		
				$content_description = $content_parsed['description'];
				$content_image = $content_parsed['image'];
				$content_media = $content_parsed['media'];
				$content_url = $content_parsed['url'];
				$content_api = $content_parsed['api'];
				$content_appkey = $content_parsed['key'];
				$content_site = $content_parsed['site'];	
				$content_tags = $content_parsed['tags'];	
				$content_icon = $content_parsed['icon'];
				$content_comment = $content_parsed['message'];
								
			}
			
			if (strlen($passed_url) > 0) $exiting_injection = "AND `content_url` LIKE '$passed_url'";
			else $exiting_injection = "AND `content_message` LIKE '$passed_content'";
			$existing_expiry = date('Y-m-d H:i:s', strtotime('-4 days'));
			$existing_query = mysqli_query("SELECT * FROM `content` WHERE `content_timestamp` > '$existing_expiry' AND `content_owner` LIKE '$authuser_key' $exiting_injection AND `content_hidden` = 0 LIMIT 0, 1");
			$existing_boolean = mysql_num_rows($existing_query);
			$existing_data = mysql_fetch_assoc($existing_query);
				
			if ($passed_url != "") {
				$blacklist_url = parse_url($passed_url);
				$blacklist_url = str_replace("www.", "", $blacklist_url['host']);
				$blacklist_query = mysqli_query("SELECT * FROM `blacklist` WHERE `blacklist_site` LIKE '$blacklist_url'");
				$blacklist_output = mysql_fetch_assoc($blacklist_query);	
				$blacklist_exists = mysql_num_rows($blacklist_query);
				
				if ($blacklist_exists == 1) {
					$json_status = 'url has been blacklisted by the grado team.';
					$json_output[] = array('status' => $json_status, 'error_code' => '310');
					echo json_encode($json_output);
					exit;
						
				}
				
			}
			
			$user_stats = return_user_stats($authuser_key);
			if ($existing_boolean > 0) {
				$json_status = 'You already shared this ' . $existing_data['content_type'];
				$json_output[] = array('status' => $json_status, 'error_code' => '311');
				echo json_encode($json_output);
				exit;
				
			}
			else if ($user_stats['score'] < 5) {
				$json_status = 'You need a score of atleast *5*';
				$json_output[] = array('status' => $json_status, 'error_code' => '311');
				echo json_encode($json_output);
				exit;
				
			}		
			else {
				$content_key = "itm_" . generate_key();
				$content_coordinates = array("latitude" => (float)$passed_latitude, "longitude" => (float)$passed_longitude);
				$content_output = array("key" => $content_key, 'comment' => $passed_content, "type" => $content_type, "title" => $content_title, "description" => $content_description, "url" => $content_url, "image" => $content_image, "icon" => $content_icon, "coordinates" => $content_coordinates, "comments_allowed" => (int)$passed_comments, "api" => $content_api, "appkey" => $content_appkey, "tags" => explode(",", $content_tags));
				$content_post = mysqli_query("INSERT INTO `content` (`content_id`, `content_timestamp`, `content_key`, `content_type`, `content_owner`, `content_message`, `content_title`, `content_description`, `content_url`, `content_site`, `content_images`, `content_icon`, `content_media`, `content_appkey`, `content_apiurl`, `content_tags`, `content_latitude`, `content_longitude`, `content_language`, `content_channels`, `content_auto`, `content_sponsored`, `content_comments`, `content_public`, `content_hidden`) VALUES (NULL, CURRENT_TIMESTAMP, '$content_key', '$content_type', '$authuser_key', '$content_comment', '$content_title', '$content_description', '$content_url', '$content_site', '$content_image', '$content_icon', '$content_media', '$content_appkey', '$content_api', '$content_tags', '$passed_latitude', '$passed_longitude', '$session_language', '', '0', '0', '$passed_comments', $passed_visibility, '0');");
				if ($content_post) {
					$list_query = mysqli_query("SELECT * FROM `lists` WHERE `list_key` LIKE '$passed_list' AND  `list_hidden` = 0 LIMIT 0, 1");
					$list_existing = mysql_num_rows($list_query);
					if ($list_existing == 1) {
						$list_data = mysql_fetch_assoc($list_query);
						$list_items = $list_data['list_items'];
						$list_image = $list_data['list_image'];
						
						if (empty($list_image)) {
							$image_content = reset(explode(",", $content_image));
							$image_update = ", `list_image` = '" . $image_content . "'";
							
						}
						
						$list_items = $list_items . "," . $content_key;
						$list_update = mysqli_query("UPDATE `lists` SET `list_updated` = CURRENT_TIMESTAMP, `list_items` = '$list_items' $image_update WHERE `list_key` LIKE '$passed_list';");	
						
					}
			
					if ($content_type == "url") $karma_title = "Link king";
					else if ($content_type == "video") $karma_title = "Video adict";
					else if ($content_type == "picture") {
						if ($content_site == "dribbble.com") $karma_title = "An eye for design";
						
					}
					else if ($content_type == "text") $karma_title = "Wordsmith";
												
					$karma_description = "you shared a " . $content_type;
					$karma_points = 15;
					$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);			
			
					$json_status = $content_type . ' was sucsessfully posted';
					$json_output[] = array('status' => $json_status, 'error_code' => '200' ,'content' => $content_output);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'content could not be posted - ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => '310');
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	if (empty($passed_key)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$existing_query = mysqli_query("SELECT * FROM `content` WHERE `content_key` LIKE '$passed_key' AND `content_hidden` = '0' LIMIT 0, 1");
		$existing_count = mysql_num_rows($existing_query);
		if ($existing_count == 1) {
			$existing_output = mysql_fetch_assoc($existing_query);
			$exiting_user = $existing_output['content_owner'];
			if ($exiting_user == $authuser_key || $authuser_type == "admin") {
				$content_delete = mysqli_query("UPDATE `content` SET `content_hidden` = '1' WHERE `content_key` LIKE '$passed_key';");
				if ($content_delete) {
					$json_status = 'content removed';
					$json_output[] = array('status' => $json_status, 'error_code' => '200');
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'content could not be removed - ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => '310');
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'user does not have permission to perform this action';
				$json_output[] = array('status' => $json_status, 'error_code' => '349');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'content does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '312');
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
	
