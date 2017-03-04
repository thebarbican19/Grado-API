<?

include '../lib/auth.php';
include '../lib/parser.php';
include '../lib/karma.php';
include '../lib/email.php';

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_user =  $passed_data['user'];
$passed_points =  intval($passed_data['points']);
$passed_title =  $passed_data['title'];
$passed_description =  $passed_data['description'];

if ($authuser_type == "admin" && $session_ip == "82.23.4.163") {	
	if (empty($passed_user)) {
		$json_status = 'user parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_title)) {
		$json_status = 'title parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
	}
	else if (empty($passed_description)) {
		$json_status = 'description parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if ($passed_points == 0) {
		$json_status = 'points cannot be zero';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$user_query = mysqli_query("SELECT COUNT(`user_key`) FROM  `users` WHERE  `user_key` LIKE  '$passed_user' AND  `user_status` LIKE  'active'");
		$user_exists = intval(mysql_result($user_query, 0));	
		if ($user_exists == 1)	{
			$karma_return = add_karma($passed_points, $passed_title, $passed_description, $passed_user);
			if ($karma_return)	{
				$email_subject = "Achievement Unlocked - " . $passed_title . " (" . $passed_points .  ") ";
				$email_message = "<center><img src='http://gradoapp.com/api/v1/assets/achivment_icon.png' width='50px'><div align='center' style='position:relative; bottom:55px; left:0px; font-size:14px; font-weight:300; color:white;'>" . $passed_points . "</div></center><div style='position:relative; top:-20px;'><center><h2><strong>" . $passed_title . "</h2><h4 style='position:relative; top:-12px; color:#A2A9AF;'>" . $passed_description . "</h4></center><p><p>Achievement in the Grado community given to reward users who utilise the platform in all different ways. You can earn points for sharing and liking content, creating lists, etc. And what to points mean, prizes of course! <p>We will disclose more details about achievements very soon.</div>";
				$email_push = email_user($email_subject, $email_message, $passed_user, $authuser_key, "true");					
				
				$json_status = 'karma added';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'karma not added - unknown error';
				$json_output[] = array('status' => $json_status, 'error_code' => '301');
				echo json_encode($json_output);
				exit;
			}
		
			
		}
		else {
			$json_status = 'user does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '301');
			echo json_encode($json_output);
			exit;
			
		}
		
	}

	
}
else {
	$json_status = 'user does not have the privileges to access this api';
	$json_output[] = array('status' => $json_status, 'error_code' => '349');
	echo json_encode($json_output);
	exit;
	
}

?>