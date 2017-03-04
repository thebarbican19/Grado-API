<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_email = $_GET['email'];
$passed_device = $_GET['device'];

if ($passed_method == 'GET') {
	if (empty($passed_email)) {
		$json_status = 'email parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$user_query = mysqli_query("SELECT * FROM  `users` WHERE  `user_status` LIKE  'active' AND  `user_email` LIKE  '$passed_email' LIMIT 0, 1");
		$user_exists = mysql_num_rows($user_query);
		$user_data = mysql_fetch_assoc($user_query);
		$user_key = $user_data['user_key'];
		if (empty($user_data['user_actualname'])) $user_name = $user_data['user_name'];
		else $user_name = $user_data['user_actualname'];
					
		if ($user_exists == 1) {
			$user_password = generate_password();
			$user_passwordencripted = password_hash($user_password ,PASSWORD_BCRYPT);
			
			$user_update = mysqli_query("UPDATE `users` SET `user_password` = '$user_passwordencripted' WHERE `user_email` LIKE '$passed_email';");
			if ($user_update) {
				$email_subject = "Password Reset";
				$email_body .= "Your password has been reset, here is your new password (you can click it to auto login if your on iOS)";
				$email_body .= "<p><center><div style='padding:7px; background-color:#36D38F; font-weight:800; color:white; border-radius:4px; display:inline-block; font-size:14px;margin:7px;'>";
				$email_body .= "<a href='gradoapp://login?email=" . $passed_email .  "&password=" . $user_password . "' style='color:white; text-decoration:none;'>" . $user_password . "";
				$email_body .= "</a></div></center>";
				$email_body .= "<p>You can change this to something more memorable by navigating to <strong>Settings</strong> > <strong>User Password</strong> in the app.";
				
				$email_push = email_user($email_subject, $email_body, $user_key, "", "true");
					
				$json_status = 'Your new password has been sent to you.';
				$json_output[] = array('status' => $json_status, 'error_code' => '200', 'email' => $email_push);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'Password could not be reset - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'User does not exist with the email ' . $passed_email;
			$json_output[] = array('status' => $json_status, 'error_code' => '350');
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