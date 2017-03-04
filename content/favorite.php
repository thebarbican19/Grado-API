<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/trending.php';
include '../lib/analytics.php';
include '../lib/karma.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_item = $passed_data['item'];
$passed_latlng = explode(",", $passed_data['latlng']);
$passed_latitude = $passed_latlng[0];
$passed_longitude = $passed_latlng[1];

if ($passed_method == 'POST') {
	if (empty($passed_item)) {
		$json_status = 'item key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$existing_query = mysql_query("SELECT * FROM `likes` WHERE `like_user` LIKE '$authuser_key' AND `like_content` LIKE '$passed_item' LIMIT 0, 1");
		$existing_count = mysql_num_rows($existing_query);
		if ($existing_count == 0) {
			$like_key = "like_" . generate_key();
			$like_post = mysql_query("INSERT INTO `likes` (`like_id`, `like_key`, `like_timestamp`, `like_user`, `like_content`, `like_latitude`, `like_longitude`) VALUES (NULL, '$like_key', CURRENT_TIMESTAMP, '$authuser_key', '$passed_item', '$passed_latitude', '$passed_longitude');");
			if ($like_post) {
				add_trending($passed_item, $authuser_key, $passed_latlng);
	
				$karma_title = "Cheerleader";
				$karma_description = "You favorited a post.";
				$karma_points = 5;
				$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);		
				
				$like_query = mysql_query("SELECT COUNT(*) FROM `likes` WHERE `like_content` LIKE  '$passed_item'");
				$like_count = intval(mysql_result($like_query, 0));	
				if ($like_count == 10 || $like_count == 50 || $like_count == 100 || $like_count == 250 || $like_count == 500 || $like_count == 1000 || $like_count == 5000) {
					$like_user = mysql_query("SELECT users.user_key FROM content LEFT JOIN users on content.content_owner LIKE users.user_key WHERE content.content_key LIKE '$passed_item'");
					$like_result = mysql_result ($like_user, 0);
			
					$karma_title = "Player x" . $like_count;
					$karma_description = " has " . $like_count . " favorites. Good job!";
					$karma_points = $like_count / 5;
					$karma_return = add_karma($karma_points, $karma_title, $karma_description, $like_result);				
				
				}
				
				$user_likes = mysql_query("SELECT COUNT(*) FROM `likes` WHERE `like_user` LIKE  '$authuser_key'");
				$user_likescount = intval(mysql_result($user_likes, 0));	
				if ($user_likescount == 100) {
					$karma_title = "King of Hearts";
					$karma_description = "You liked 100 posts";
					$karma_points = 10;
					$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);	
					
				}
				
				$json_status = 'like was added';
				$json_output[] = array('status' => $json_status, 'error_code' => '200', "like" => $like_count, "query" => $like_result);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'like could not be posted - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'content was already liked by user';
			$json_output[] = array('status' => $json_status, 'error_code' => '311');
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	if (empty($passed_item)) {
		$json_status = 'item key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$existing_query = mysql_query("SELECT * FROM `likes` WHERE `like_user` LIKE '$authuser_key' AND `like_content` LIKE '$passed_item' LIMIT 0, 1");
		$existing_count = mysql_num_rows($existing_query);
		if ($existing_count == 1) {
			$like_output = mysql_fetch_assoc($existing_query);
			$like_key = $like_output['like_key'];
			$like_delete = mysql_query("DELETE FROM `likes` WHERE `like_key` LIKE '$like_key';");
			if ($like_delete) {
				$json_status = 'like deleted';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'like could not be deleted - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'like already deleted by user';
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