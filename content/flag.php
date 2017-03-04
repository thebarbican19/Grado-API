<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/slack.php';
include '../lib/karma.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_item = $passed_data['item'];
$passed_reason = $passed_data['reason'];

if ($passed_method == 'POST') {
	if (empty($passed_reason)) {
		$json_status = 'reason parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (empty($passed_item)) {
		$json_status = 'item parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$content_query = mysql_query("SELECT * FROM `content` WHERE `content_key` LIKE '$passed_item'");
		$content_data = mysql_fetch_assoc($content_query);
		$content_url = parse_url($content_data['content_url']);
		$content_url = $content_url['host'];
		$content_url = str_replace("www.", "", $content_url);
		$content_title = $content_data['content_title'];
		$content_type = $content_data['content_type'];
		$content_image = reset(explode(",", $content_data['content_images']));
		$content_message = $content_data['content_message'];
		if (empty($content_message)) $content_message = "(no message)";
			
		$existing_query = mysql_query("SELECT * FROM `flagged` WHERE `flagged_sender` LIKE '$authuser_key' AND `flagged_content` LIKE '$passed_item' LIMIT 0, 1");
		$existing_count = mysql_num_rows($existing_query);
			
		if ($existing_count == 0 || empty($authuser_key)) {
			$flagged_query = mysql_query("SELECT * FROM `flagged` WHERE `flagged_content` LIKE '$passed_item'");
			$flagged_count = mysql_num_rows($flagged_query);
			$flagged_key = "flg_" . generate_key();
			$flagged_post = mysql_query("INSERT INTO `flagged` (`flagged_id`, `flagged_timestamp`, `flagged_key`, `flagged_sender`, `flagged_content`, `flagged_reason`) VALUES (NULL, CURRENT_TIMESTAMP, '$flagged_key', '$authuser_key', '$passed_item', '$passed_reason');");
			if ($flagged_post) {
				if ($flagged_count == 4 || $flagged_count == 8 || $flagged_count == 12) {
					if ($flagged_count == 4) $slack_colour = "#F0F0F0";
					elseif ($flagged_count == 8) $slack_colour = "#FDAA54";
					else $slack_colour = "#E36466";

					$slack_message = "Key: *" . $passed_item . "*\nType: *" . $content_type . "*\nTitle: *" . $content_title . "*\nMessage: *" . $content_message . "*";
					$slack_fallback = "A item has been flagged " . $flagged_count . " times.";
					$slack_attachment[] = array("title" => "", "text" => $slack_message, "color" => $slack_colour, "image_url" => $content_image);
					$slack_post = post_slack($slack_fallback, 'flagged', $authuser_avatar, $slack_attachment);	
										
				}
				if ($flagged_count == 10 || $flagged_count > 50 || $passed_reason == "blacklist" && $authuser_type == "admin") {
					$karma_message = "We H8 Spamming [post removed]";
					$karma_points = -10;
					$karma_return = add_karma($karma_points, $karma_message, $authuser_key);				
					
					$content_delete = mysql_query("UPDATE `content` SET `content_hidden` = '1' WHERE `content_key` LIKE '$passed_item';");
					
				}
				
				if ($passed_reason == "blacklist" && $authuser_type == "admin") {
					$blacklist_exists = mysql_query("SELECT * FROM `blacklist` WHERE `blacklist_site` LIKE '$content_url'");
					if (mysql_num_rows($blacklist_exists) == 0) {
						$blacklist_post = mysql_query("INSERT INTO `blacklist` (`blacklist_id`, `blacklist_timestamp`, `blacklist_type`, `blacklist_site`) VALUES (NULL, CURRENT_TIMESTAMP, 'explicit', '$content_url');");
											
					}
					
				}
				
				$json_status = 'item was flagged';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'item could not be flagged - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'content was already flagged by user';
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
		
