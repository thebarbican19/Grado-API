<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/email.php';
include '../lib/push.php';

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_channel = $passed_data['channel'];
$passed_key = $passed_data['key'];
$passed_emails = (int)$passed_data['emails'];
$passed_push = (int)$passed_data['push'];

if ($passed_method == 'POST') {
	if (empty($passed_channel)) {
		$json_status = 'channel parameter is missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {		
		$channel_query = mysqli_query("SELECT `channel_id`, `channel_key`, `channel_title` FROM `channel` WHERE `channel_key` LIKE '$passed_channel' AND channel_hidden = 0 LIMIT 0 ,1");
		$channel_exists = mysql_num_rows($channel_query);
		$channel_data = mysql_fetch_assoc($channel_query);
		$channel_name = $channel_data['channel_title'];
		if ($channel_exists == 1) {
			$subscribtion_query = mysqli_query("SELECT subscription_id, subscription_key, channel_title, channel_type FROM subscriptions LEFT JOIN channel ON subscriptions.subscription_channel LIKE channel.channel_key WHERE subscription_channel LIKE '$passed_channel' AND subscription_user LIKE '$authuser_key' AND channel_hidden = 0 LIMIT 0 , 1");
			$subscribtion_exists = mysql_num_rows($subscribtion_query);
			if ($subscribtion_exists == 0) {
				$subscribtion_key =  "sub_" . generate_key();		
				$subscribe_add = mysqli_query("INSERT INTO  subscriptions (subscription_id ,subscription_timestamp ,subscription_key ,subscription_user ,subscription_channel ,subscription_email ,subscription_push) VALUES (NULL , CURRENT_TIMESTAMP ,  '$subscribtion_key',  '$authuser_key',  '$passed_channel',  '$passed_emails',  '$passed_push');");
				if ($subscribe_add) {
					//if ($passed_emails == 1)
					//email_subscribe_mailinglist($subscribtion_channelname, $authuser_email, $authuser_username);
					
					if ($passed_push == 1) subscribe_to_channel($authuser_username, $authuser_device, $channel_name);
					
					$json_status = 'subscription was added';
					$json_output[] = array('status' => $json_status, 'error_code' => 200);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'an uknown error occured ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'already subscribed';
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'channel does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 403);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
elseif ($passed_method == 'PUT') {
	if (empty($passed_key)) {
		$json_status = 'key parameter is missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$subscribtion_query = mysqli_query("SELECT * FROM subscriptions WHERE subscription_key LIKE '$passed_key' AND subscription_user LIKE '$authuser_key' LIMIT 0, 1");
		$subscribtion_exists = mysql_num_rows($subscribtion_query);
		if ($subscribtion_exists == 0) {
			$json_status = 'subscription does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 403);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$subscription_data = mysql_fetch_assoc($subscribtion_query);
			$subscription_emails = (int)$subscription_data['subscription_email'];
			$subscription_push = (int)$subscription_data['subscription_push'];
			//if ($subscription_emails != $passed_emails) $subscription_injection
			
		}
		
	}
	
}
elseif ($passed_method == 'DELETE') {
	if (empty($passed_key)) {
		$json_status = 'key parameter is missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$subscribtion_query = mysqli_query("SELECT subscription_id, subscription_key, channel_title, channel_type FROM subscriptions LEFT JOIN channel ON subscriptions.subscription_channel LIKE channel.channel_key WHERE subscription_key LIKE '$passed_key' LIMIT 0 , 1");
		$subscribtion_exists = mysql_num_rows($subscribtion_query);
		$subscription_data = mysql_fetch_assoc($subscribtion_query);
		$subscription_channel = $subscription_data['channel_title'];
		if ($subscribtion_exists == 0) {
			$json_status = 'subscription does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 403);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$subscription_delete = mysqli_query("DELETE FROM subscriptions WHERE subscription_key LIKE '$passed_key';");
			if ($subscription_delete) {
				$push_unsubscribe = unsubscribe_to_channel($authuser_username, $authuser_device, $subscription_channel);
				
				$json_status = 'sucsessfully unsubscribed';
				$json_output[] = array('status' => $json_status, 'error_code' => 200, 'push' => $push_unsubscribe);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'an uknown error occured ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
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