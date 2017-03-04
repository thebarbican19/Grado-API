<?

include '../auth.php';
include '../email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_value = $_POST['text'];
$passed_command = $_POST['command'];
$passed_team = $_POST['team_id'];
$passed_token = $_POST['token'];
$passed_userid = $_POST['user_id'];

if ($passed_method == 'POST') {
	if (empty($passed_token)) {
		$json_status = "You are not authorized to call this action." . $passed_userid;		
		$json_output = array('text' => $json_status, "response_type" => "in_channel", "fallback" => $json_status);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		preg_match('/(?=recipient:*\[(.*?)\])/', $passed_value, $passed_user);
		preg_match('/(?=subject:*\[(.*?)\])/', $passed_value, $passed_subject);
		preg_match('/(?=message:*\[(.*?)\])/', $passed_value, $passed_message);
				
		$passed_user = end($passed_user);
		$passed_subject = end($passed_subject);
		$passed_message = end($passed_message);
				
		$sender_query = mysqli_query("SELECT * FROM  `users` WHERE  `user_status` LIKE  'active' AND `user_type` LIKE  'admin' AND `user_slack` LIKE  '$passed_userid' LIMIT 0 , 1");
		$sender_exists = mysql_num_rows($sender_query);
		if ($sender_exists == 1) {
			$sender_data = mysql_fetch_assoc($sender_query);
			$sender_key = $sender_data['user_key'];
			if (empty($passed_user)) {
				$json_status = "You did not specify a recipient. To add recipient add 'recipient:[USER_KEY,USER_NAME,USER EMAIL]'" . $passed_userid;		
				$json_output = array("text" => $json_status, "response_type" => "in_channel");
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$user_query = mysqli_query("SELECT * FROM `users` WHERE `user_key` LIKE '$passed_user' OR `user_email` LIKE '$passed_user' OR `user_name` LIKE '$passed_user' LIMIT 0, 1");
				$user_exists = mysql_num_rows($user_query);
				$user_output = mysql_fetch_assoc($user_query);
				$user_name = $user_output['user_name'];
				$user_email = $user_output['user_email'];	
				$user_key = $user_output['user_key'];				
				if ($user_exists == 0) {
					$json_status = "User '" . $passed_user . "' does not exist.";		
					$json_output = array("text" => $json_status, "fallback" => $json_status, "response_type" => "in_channel");
					echo json_encode($json_output);
					exit;
					
				}	
				else {
					if (empty($passed_subject)) {
						$json_status = "Subject is required. To add subject add 'subject:[SUBJECT]'";	
						$json_output = array("text" => $json_status, "response_type" => "in_channel");
						echo json_encode($json_output);
						exit;
						
					}
					else if (empty($passed_message)) {
						$json_status = "Message is required. To add message add 'message:[MESSAGE]'";		
						$json_output = array("text" => $json_status, "response_type" => "in_channel");
						echo json_encode($json_output);
						exit;
						
					}
					else {
						$email_push = email_user($passed_subject, $passed_message, $user_key, $sender_key, "true");
						
						$json_fallback =  "Message sent to " . $user_name . "(" . $user_email . ")";
						$json_attachement[] = array("title" => $json_fallback, "text" => $passed_message, "color" => "#36D38F");
						$json_output = array("attachments" => $json_attachement, "response_type" => "in_channel", "text" => "", "fallback" => $json_fallback);
						echo json_encode($json_output);
						exit;
						
					}
					
				}
			
			}
			
		}
		else {
			$json_status = "You are not authorized to call this action." . $passed_userid;		
			$json_output = array("text" => $json_status, "response_type" => "in_channel");
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api.';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
}

?>