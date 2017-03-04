<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/karma.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_comment = $passed_data['comment'];
$passed_item = $passed_data['item'];
$passed_type = $passed_data['type'];
$passed_key = $passed_data['key'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	$passed_item = $_GET['item'];
	if (empty($passed_item)) {
		$json_status = 'item parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$comment_query = mysql_query("SELECT * FROM `comments` WHERE `comment_item` LIKE '$passed_item' ORDER BY `comment_timestamp` ASC LIMIT $passed_pagenation, $passed_limit;");
		$comment_count = mysql_num_rows($comment_query);
		while($row = mysql_fetch_array($comment_query)) {		
			$comment_key = $row['comment_key'];
			$comment_content = $row['comment_message'];
			$comment_type = $row['comment_type'];				
			$comment_user = $row['comment_user'];
			$comment_timestamp = $row['comment_timestamp'];
								
			$comment_user_query = mysql_query("SELECT * FROM `users` WHERE `user_key` LIKE '$comment_user'");
			$comment_user_data = mysql_fetch_assoc($comment_user_query);
			$comment_user_status = $comment_user_data['user_status'];				
			$comment_user_name = $comment_user_data['user_name'];
			$comment_user_avatar = $comment_user_data['user_avatar'];
			
			if ($comment_user_status == "active") $comment_user_output = $comment_user_output = array("key" => $comment_user, "username" => $comment_user_name, "avatar" => $comment_user_avatar);
			else $comment_user_output = array("key" => $comment_user, "username" => "", "avatar" => "", "timestamp" => $comment_timestamp);
		
			if ($comment_type == "private" && isset($authuser_key) && ($comment_user == $authuser_key || $stream_owner == $authuser_key)) $comment_show = "true";
			elseif ($comment_type == "public") $comment_show = "true";
			else $comment_show = "false";
			
			if ($comment_show == "true") $comment_output[] = array("key" => $comment_key, "type" => $comment_type, "user" => $comment_user_output, "comment" => $comment_content, "timestamp" => $comment_timestamp);
			
		}
		
		if (count($comment_output) == 0) $comment_output = array();	
		
		$json_status = count($comment_output) . ' comments returned';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'comments' => $comment_output);
		echo json_encode($json_output);
		exit;
		
	}
						
}
else if ($passed_method == 'POST') {
	if (!in_array("post", $application_scope)) {
		$json_status = 'application does not have the privileges to access this api';
		$json_output[] = array('status' => $json_status, 'error_code' => '371');
		echo json_encode($json_output);
		exit;
		
	}
	
	if (empty($passed_comment)) {
		$json_status = 'comment parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (strlen($passed_comment) < 1)	{
		$json_status = 'comment is too short, limit is 300 characters';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (strlen($passed_comment) > 300)	{
		$json_status = 'comment is too long, limit is 300 characters';
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
	elseif ($passed_type != "public" && $passed_type != "private") {
		$json_status = 'type is invalid, must be public or private' . $passed_type;
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}	
	else {
		$passed_comment = strip_tags($passed_comment);
		$passed_comment = str_replace("'", "\'", $passed_comment);
		$passed_comment = str_replace("\n", " ", $passed_comment);
		$passed_comment = preg_replace('/\s+/', ' ',$passed_comment);
		
		preg_match_all("/(@\w+)/", $passed_comment, $user_mentions);
		
		foreach ($user_mentions as $user_nickname) {
			$mention_nickname = str_replace("@", "", $user_nickname);
			$mention_query = mysql_query("SELECT * FROM `users` WHERE `user_status` LIKE 'active' AND `user_name` LIKE '$mention_nickname'");
			$mention_exists = mysql_num_rows($mention_query);
			if ($mention_exists == 1) {
				$mention_data = mysql_fetch_assoc($mention_query);
			
			}
			
		}
		
		$item_query = mysql_query("SELECT * FROM `content` WHERE `content_key` LIKE '$passed_item' AND `content_hidden` = 0 LIMIT 0, 1");
		$item_exists = mysql_num_rows($item_query);
		if ($item_exists == 1) {
			$existing_query = mysql_query("SELECT * FROM `comments` WHERE `comment_user` LIKE '$authuser_key' AND `comment_message` LIKE '$passed_comment' LIMIT 0, 1");
			$existing_count = mysql_num_rows($existing_query);
			if ($existing_count == 0) {
				$comment_key = "talk_" . generate_key();
				$comment_timestamp = date('Y-m-d H:i:s');
				$comment_user_output = array("key" => $authuser_key, "username" => $authuser_username, "avatar" => $authuser_avatar, "timestamp" => $comment_timestamp);
				$comment_output = array("key" => $comment_key, "type" =>  $passed_type, "user" => $comment_user_output, "comment" => $passed_comment);
				$comment_post = mysql_query("INSERT INTO `comments` (`comment_id`, `comment_timestamp`, `comment_key`, `comment_item`, `comment_user`, `comment_type`, `comment_message`, `comment_flagged`, `comment_deleted`) VALUES (NULL, CURRENT_TIMESTAMP, '$comment_key', '$passed_item', '$authuser_key', '$passed_type', '$passed_comment', '0', '0');");
				if ($comment_post) {
					$karma_title = "First!";
					$karma_description = "You joined the discussion on a post";
					$karma_points = 10;
					$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);			
					
					$json_status = 'comment posted';
					$json_output[] = array('status' => $json_status, 'error_code' => '200', 'comment' => $comment_output);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'comment could not be posted - ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => '310');
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'comment was already posted';
				$json_output[] = array('status' => $json_status, 'error_code' => '311');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'item does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '312');
			echo json_encode($json_output);
			exit;
			
		}
	
	}
	
}
else if ($passed_method == 'DELETE') {
	if (empty($passed_key)) {
		$json_status = 'comment key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {		
		$existing_query = mysql_query("SELECT * FROM `comments` WHERE `comment_key` LIKE '$passed_key' LIMIT 0, 1");
		$existing_output = mysql_fetch_assoc($existing_query);
		$exiting_owner = $existing_output['comment_user'];
		if ($exiting_user == $authuser_key || $authuser_type == "admin") {		
			$comment_delete = mysql_query("UPDATE `comments` SET `comment_deleted` = '1' WHERE `comment_key` LIKE '$passed_key';");
			if ($comment_delete) {
				$karma_title = "";
				$karma_description = "your comment was removed";
				$karma_points = -5;
				$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);			
			
				$json_status = 'comment deleted';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'comment could not be deleted - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
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

?>