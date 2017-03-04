<?

include '../lib/auth.php';
include '../lib/keygen.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_list = $passed_data['list'];
$passed_subscription = $passed_data['key'];
$passed_notifications =  $passed_data['notifications'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	$subscription_sql = "SELECT * FROM `subscriptions` LEFT JOIN lists on subscriptions.subscription_list LIKE lists.list_key LEFT JOIN users on lists.list_owner LIKE users.user_key WHERE `subscription_user` LIKE '$authuser_key' AND (`list_public` = 1 OR `list_owner` LIKE '$authuser_key') AND `list_hidden` = 0 GROUP BY `subscription_list` ORDER BY `list_updated` DESC LIMIT $passed_pagenation, $passed_limit";
	$subscription_query = mysqli_query($subscription_sql);
	$subscription_count = mysql_num_rows($subscription_query);
	while($row = mysql_fetch_array($subscription_query)) {	
		$subscription_key = $row['list_key'];
		$subscription_timestamp = $row['list_timestamp'];
		$subscription_updated = $row['list_updated'];
		$subscription_title = $row['list_title'];
		$subscription_summary = $row['list_description'];
		$subscription_type = $row['list_type'];
		$subscription_image = $row['list_image'];
		$subscription_featured = (int)$row['list_featured'];			
		$subscription_moderators = explode(",", $row['list_moderators']);
		$subscription_items = explode(",", $row['list_items']);
		$subscription_public = (int)$row['list_public'];
		
		$subscribers_query = mysqli_query("SELECT `subscription_key`, COUNT(*) AS `subscription_count` FROM `subscriptions` WHERE `subscription_list` LIKE '$subscription_key'");
		$subscribers_data = mysql_fetch_assoc($subscribers_query);
		$subscribers_count = (int)$subscribers_data['subscription_count'];
						
		$owner_key = $row['user_key'];
		$owner_username = $row['user_name'];
		$owner_avatar = $row['user_avatar'];
		$owner_data = array("key" => $owner_key, "username" => $owner_username, "avatar" => $owner_avatar);	
		
		if ($authuser_key == $owner_key) $subscription_type = "admin";
		elseif (in_array($authuser_key, $subscription_moderators)) $subscription_type = "moderator";
		else $subscription_type = "user";
		
		$item_list = array();
		foreach ($subscription_items as $item) {
			$item_query = mysqli_query("SELECT * FROM `content` WHERE `content_key` LIKE '$item' AND `content_hidden` = 0 LIMIT 0 ,1");
			$item_data = mysql_fetch_assoc($item_query);
			$item_key = $item_data['content_key'];
			if (isset($item_key)) $item_list[] = $item_key;
										
		}
		
		$subscription_items = count($item_list);
		$subscription_output[] = array("timestamp" => $subscription_timestamp, "key" => $subscription_key, "title" => $subscription_title, "summary" => $subscription_summary, "image" => $subscription_image, "items" => $subscription_items, "subscribers" => $subscribers_count, "usertype" => $subscription_type, 'featured' => $subscription_featured, 'public' => $subscription_public, "owner" => $owner_data);
	}
		
	if ($subscription_count == 0) $subscription_output = array();
	
	$json_status = $subscription_count . ' lists returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'subscriptions' => $subscription_output);
	echo json_encode($json_output);
	exit;
				
	
}
elseif ($passed_method == 'POST') {
	if (empty($passed_list)) {
		$json_status = 'list parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "list") {
			$list_query = mysqli_query("SELECT * FROM `lists` WHERE `list_key` LIKE '$passed_list' AND  `list_hidden` = 0 LIMIT 0, 1");
			$list_exists = mysql_num_rows($list_query);
			if ($list_exists == 0) {
				$json_status = 'list does not exist';
				$json_output[] = array('status' => $json_status, 'error_code' => '301');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$list_data = mysql_fetch_assoc($list_query);
				$list_owner = $list_data['list_owner'];
				$list_type = $list_data['list_type'];
				if ($list_type == "private" && $list_owner != $authuser_key) {
					$json_status = 'you are not autohrized to subscribe to this list';
					$json_output[] = array('status' => $json_status, 'error_code' => '301');
					echo json_encode($json_output);
					exit;
				
				}			
				elseif ($list_owner == $authuser_key) {
					$json_status = 'cannot subscribe to a list you own';
					$json_output[] = array('status' => $json_status, 'error_code' => '301');
					echo json_encode($json_output);
					exit;
					
				}

			}
			
		}
		
		$subscribtion_query = mysqli_query("SELECT * FROM `subscriptions` WHERE `subscription_type` LIKE '$passed_type' AND `subscription_user` LIKE '$authuser_key' AND `subscription_list` LIKE '$passed_list'");
		$subscribtion_exists = mysql_num_rows($subscribtion_query);
		if ($subscribtion_exists == 1) {
			$json_status = 'already subcsribed to list';
			$json_output[] = array('status' => $json_status, 'error_code' => '301');
			echo json_encode($json_output);
			exit;
			
		}
		else {			
			$subscribtion_key =  "sub_" . generate_key();		
			$subscribtion_create = mysqli_query("INSERT INTO `subscriptions` (`subscription_id`, `subscription_timestamp`, `subscription_key`, `subscription_user`, `subscription_list`, `subscription_notify`) VALUES (NULL, CURRENT_TIMESTAMP, '$subscribtion_key', '$authuser_key', '$passed_list', '$passed_notifications');");
			if ($subscribtion_create) {
				$json_status = 'sucsessfully subcsribed to ' . $passed_type;
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'list could not be created - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		
	}

}
else if ($passed_method == 'PUT') {
	if (empty($passed_subscription)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$subscribtion_query = mysqli_query("SELECT * FROM `subscriptions` WHERE `subscription_key` LIKE '$passed_subscription' LIMIT 0, 1");
		$subscribtion_exists = mysql_num_rows($subscribtion_query);
		if ($subscribtion_exists == 0) {
			$json_status = 'subscription does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '301');
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$subscription_update = mysqli_query("UPDATE `subscriptions` SET `subscription_notify` = '$passed_notifications' WHERE `subscription_key` LIKE '$passed_subscription';");
			if ($subscription_update) {
				$json_status = 'subscription was updated';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'subscription could not be updated - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		
	}
	
}
else if ($passed_method == 'DELETE') {
	if (empty($passed_subscription)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$subscribtion_query = mysqli_query("SELECT * FROM `subscriptions` WHERE `subscription_key` LIKE '$passed_subscription' LIMIT 0, 1");
		$subscribtion_exists = mysql_num_rows($subscribtion_query);
		if ($subscribtion_exists == 0) {
			$json_status = 'subscription does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '301');
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$subscription_delete = mysqli_query("DELETE FROM `subscriptions` WHERE `subscription_key` LIKE '$passed_subscription';");
			if ($subscription_delete) {
				$json_status = 'subscription was deleted';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'subscription could not be deleted - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
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
