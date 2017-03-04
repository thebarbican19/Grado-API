<?

include '../lib/auth.php';
include '../lib/slack.php';
include '../lib/keygen.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_email = $_GET['email'];
$passed_list = $_GET['list'];

if (empty($passed_email)) {
	$json_status = 'email key parameter missing';
	$json_output[] = array('status' => $json_status, 'error_code' => '301');
	echo json_encode($json_output);
	exit;
	
}
elseif (filter_var($passed_email, FILTER_VALIDATE_EMAIL) === false) {
	$json_status = 'email is invalid';
	$json_output[] = array('status' => $json_status, 'error_code' => '310');
	echo json_encode($json_output);
	exit;
	
}	
else if (empty($passed_list)) {
	$json_status = 'list parameter missing';
	$json_output[] = array('status' => $json_status, 'error_code' => '301');
	echo json_encode($json_output);
	exit;
	
}

if ($passed_method == 'GET') {
	$exists_query = mysqli_query($database_grado_connect, "SELECT * FROM `users` WHERE `user_email` LIKE '$passed_email'");
	$exists_count = mysqli_num_rows($exists_query);
	$exists_data = mysqli_fetch_assoc($exists_query);
	$exists_username = $exists_data['user_name'];
	$exists_avatar = $exists_data['user_avatar'];
		
	$mailing_update = email_subscribe_mailinglist($passed_list, $passed_email, $exists_username);
	$mailing_message = $mailing_update->message;
		
	if (strpos($mailing_message, 'Mailing list member has been created') !== false) {
		if (empty($exists_username)) $slack_message = "" . $passed_email . " has signed up to Grado's Mailing List!";
		else $slack_message = "*" . $exists_username . "*(" . $passed_email . ") has signed up to Grado's Mailing List!";
		$slack_post = post_slack($slack_message, 'mailing', $exists_avatar);
		
		$json_status = $passed_email . ' subscribed';
		$json_output[] = array('status' => $json_status, 'error_code' => 200, 'mailgun' => $mailing_message);
		echo json_encode($json_output);
		exit;
		
	}	
	else if (strpos($mailing_message, 'Address already exists') !== false) {
		$json_status = $passed_email . ' already subscribed';
		$json_output[] = array('status' => $json_status, 'error_code' => 409, 'mailgun' => $mailing_message);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = $mailing_message;
		$json_output[] = array('status' => $json_status, 'error_code' => 400, 'test' => $mailing_update);
		echo json_encode($json_output);
		exit;	
		
	}
	
}
elseif ($passed_method == 'DELETE') {
	$mailing_update = email_delete_mailinglist($channel_name);
			
	$json_status = $passed_email . ' unsubscribed';
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'mailgun' => $mailing_update);
	echo json_encode($json_output);
	exit;
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>