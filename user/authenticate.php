<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

global $database_grado_connect;
		
$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_email = $passed_data['email'];
$passed_password = $passed_data['password'];

if ($passed_method == 'POST') {
	if (empty($passed_email)) {
		$json_status = 'email parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (filter_var($passed_email, FILTER_VALIDATE_EMAIL) === false) {
		$json_status = 'email is invalid';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}	
	elseif (empty($passed_password)) {
		$json_status = 'password parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$user_query = mysqli_query($database_grado_connect ,"SELECT * FROM `users` WHERE `user_status` LIKE 'active' AND `user_email` LIKE '$passed_email' LIMIT 0, 1");
		$user_exists = mysqli_num_rows($user_query);
		if ($user_exists == 1) {
			$user_data = mysqli_fetch_assoc($user_query);	
			$user_password = $user_data['user_password'];
			if (password_verify($passed_password ,$user_password)) {
				$user_key = $user_data['user_key'];
				$user_name = $user_data['user_name'];
				$user_fullname = $user_data['user_actualname'];
				$user_email = $user_data['user_email'];
				$user_type = $user_data['user_type'];
				$user_avatar = $user_data['user_avatar'];
				$user_city = $user_data['user_city'];
				$user_country = $user_data['user_country'];		
				$user_language = $user_data['user_language'];
				$user_lastactive = $user_data['user_lastactive'];
				$user_signup = $user_data['user_signup'];
				$user_slack = $user_data['user_slack'];
				$user_stats = return_user_stats($user_key);
										
				if ($user_type == "admin") $bearer_scope = "auth,post,delete,update,admin";
				else $bearer_scope = "auth,post,delete,update";
				$bearer_token = "at_" . generate_key();	
				$bearer_expiry = date('Y-m-d H:i:s', strtotime('+100 days'));
				$bearer_output = array("expiry" => $bearer_expiry, "token" => $bearer_token, 'scope' => explode(",", $bearer_scope));
				$bearer_create = mysqli_query($database_grado_connect, "INSERT INTO `access` (`access_id`, `access_created`, `access_expiry`, `access_token`, `access_user`, `access_app`, `access_scope`) VALUES (NULL, CURRENT_TIMESTAMP, '$bearer_expiry', '$bearer_token', '$user_key', '$session_application', '$bearer_scope');");
				if ($bearer_create)	 {
					$user_update = mysqli_query($database_grado_connect, "UPDATE `users` SET `user_status` = 'active' WHERE `user_key` LIKE '$user_key';");
					$user_output = array("key" => $user_key, "username" => $user_name, "actualname" => $user_fullname, "email" => $user_email, "type" => $user_type, "slack" => $user_slack, "avatar" => $user_avatar, "city" => $user_city, "country" => $user_country, "langauge" => $user_language, "lastactive" => $user_lastactive, "signup" => $user_signup, "auth" => $bearer_output, "stats" => $user_stats);	
							
					$json_status = 'user logged in';
					$json_output[] = array('status' => $json_status, 'error_code' => 200, 'user' => $user_output);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'access token not be created - ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'Password incorrect';
				$json_output[] = array('status' => $json_status, 'error_code' => 401);
				echo json_encode($json_output);
				exit;
				
			}
							
		}
		else {
			$json_status = 'user does not exist with the email ' . $passed_email;
			$json_output[] = array('status' => $json_status, 'error_code' => 350);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
	
}
elseif ($passed_method == 'DELETE') {
	$destroy_token = mysqli_query($database_grado_connect ,"DELETE FROM `access` WHERE `access_token` LIKE '$session_bearer';");
	if ($destroy_token) {
		$json_status = 'access token destroyed';
		$json_output[] = array('status' => $json_status, 'error_code' => 200);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'access token not destroyed - ' . mysql_error();
		$json_output[] = array('status' => $json_status, 'error_code' => 400);
		echo json_encode($json_output);
		exit;
		
	}
	
}
else {
	$json_status = $passed_method . ' menthods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>