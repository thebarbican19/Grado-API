<?

include '../lib/auth.php';
include '../lib/karma.php';
include '../lib/analytics.php';
include '../lib/image.php';
include '../lib/push.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_type = $passed_data['type'];
$passed_value = $passed_data['value'];
$passed_avatar = $_FILES['avatar'];

if ($passed_method == 'GET') {
	$user_stats = return_user_stats($authuser_key);
	$user_output = array("key" => $authuser_key, "username" => $authuser_username, "email" => $authuser_email, "type" => $authuser_type, "slack" => $authuser_slack, "avatar" => $authuser_avatar, "location" => $authuser_location, "langauge" => $authuser_language, "lastactive" => $authuser_lastactive, "signup" => $authuser_signup, "stats" => $user_stats);	
	
	$json_status = 'user data returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'user' => $user_output);
	echo json_encode($json_output);
	exit;
						
}
elseif ($passed_method == 'PUT') {
	$allowed_types = array('status' ,'email', 'password', 'avatar', 'language', 'location', 'slack', 'device');
	$allowed_statuses = array('invited' ,'active', 'inactive', 'mailing');
	$allowed_imagetypes = array("png", "jpg", "jpeg");
	if (!in_array("update", $application_scope)) {
		$json_status = 'application does not have the privileges to access this api';
		$json_output[] = array('status' => $json_status, 'error_code' => '371');
		echo json_encode($json_output);
		exit;
		
	}
	
	if (empty($passed_type)) {
		$json_status = 'type parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if (!in_array($passed_type, $allowed_types)) {
		$json_status = 'type parameter is invalid';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_value)) {
		$json_status = 'value parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "status") {
			if (!in_array($passed_value, $allowed_statuses)) {
				$json_status = 'status is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_status` = '" . $passed_value . "'";
					
		}
		elseif ($passed_type == "email") {
			if (filter_var($passed_value, FILTER_VALIDATE_EMAIL) === false) {
				$json_status = 'email is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_email` = '" . $passed_value . "'";
						
		}
		elseif ($passed_type == "password") {
			if (strlen($passed_value) < 5) {
				$json_status = 'password is too short';
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_encrypted = password_hash($passed_value ,PASSWORD_BCRYPT);
				$update_injection = "`user_password` = '" . $update_encrypted . "'";	
				
			}
						
		}
		elseif ($passed_type == "slack") {
			if (strlen($passed_value) != 8 && strlen($passed_value) > 12) {
				$json_status = 'slack username is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$update_injection = "`user_slack` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "device") {
			if (strlen($passed_value) != 64) {
				$json_status = 'device token is invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				submit_device($authuser_username, $passed_value);
				$update_injection = "`user_device` = '" . $passed_value . "'";	
				
			}	
							
		}
		elseif ($passed_type == "language") {
			if (strlen($passed_value) != 2) {
				$json_status = 'langauge code invalid';
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			else $update_injection = "`user_language` = '" . $passed_value . "'";	
						
		}
		elseif ($passed_type == "location") {
			$update_push = upload_push_token($authuser_key, $passed_value);
			$update_injection = "`user_location` = '" . $passed_value . "'";	
			
			
		}		
		
		$update_post = mysql_query("UPDATE `users` SET $update_injection WHERE `user_key` LIKE '$authuser_key';");
		if ($update_post) {
			$json_status = 'user ' . $passed_type . ' was sucsessfully updated';
			$json_output[] = array('status' => $json_status, 'error_code' => '200', 'pushbots' => $update_push);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'user ' . $passed_type . ' could not be updated - ' . mysql_error();
			$json_output[] = array('status' => $json_status, 'error_code' => '310');
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
elseif ($passed_method == 'POST') {
	if (empty($passed_avatar)) {
		$json_status = 'avatar was not passed';
		$json_output[] = array('status' => $json_status, 'error_code' => '310', 'file' => $passed_avatar);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$upload_output = upload_image($passed_avatar, $authuser_key, "avatar");
		$upload_sucsess = $upload_output['error_code'];
		$upload_directory = $upload_output['file'];		
		if ($upload_sucsess != "200") {
			echo json_encode($upload_output);
			exit;
			
		}
		else {
			$update_post = mysql_query("UPDATE `users` SET `user_avatar` = '$upload_directory' WHERE `user_key` LIKE '$authuser_key';");
			if ($update_post) {
				$karma_title = "What a stunner!";
				$karma_description = "you uploaded a new avatar";
				$karma_points = 10;
				$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);			
				
				$json_status = 'user ' . $passed_type . ' was sucsessfully updated';
				$json_output[] = array('status' => $json_status, 'error_code' => '200', 'file' => $upload_directory);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'user ' . $passed_type . ' could not be updated - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
						
	}
	
}
else {
	$json_status = $passed_method . ' menthods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => '380');
	echo json_encode($json_output);
	exit;
	
}
