<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/slack.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_type = $passed_data['type'];
$passed_message = $passed_data['message'];

if ($passed_method == 'POST') {
	if (empty($passed_type)) {
		$json_status = 'type key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_message)) {
		$json_status = 'message key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if (empty($authuser_fullname)) $passed_name = $authuser_username;
		else $passed_name = $authuser_fullname . " (" . $authuser_username . ")";
		
		$slack_fallback = $passed_name . " posted a *" . ucfirst($passed_type) . "* message";
		$slack_attachment[] = array("title" => "", "text" => $passed_message, "color" => "#36D38F");
		$slack_post = post_slack($slack_fallback, 'general', $authuser_avatar, $slack_attachment);
		
		$karma_message = "Team Player [submitted feedback]";
		$karma_points = 25;
		$karma_return = add_karma($karma_points, $karma_message, $authuser_key);	
		
		$json_status = 'message sent';
		$json_output[] = array('status' => $json_status, 'error_code' => '200');
		echo json_encode($json_output);
		exit;
			
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}