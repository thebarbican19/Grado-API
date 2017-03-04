<?

include '../lib/auth.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_message = $passed_data['message'];
$passed_subject = $passed_data['subject'];
$passed_list = $passed_data['list'];

if ($passed_method == 'POST') {
	if ($authuser_type == "admin" && $session_ip == "82.23.4.163") {	
		if (empty($passed_list)) {
			$json_status = 'list parameter is missing';
			$json_output[] = array('status' => $json_status, 'error_code' => 422);
			echo json_encode($json_output);
			exit;
			
		}
		elseif (empty($passed_subject)) {
			$json_status = 'subject parameter is missing';
			$json_output[] = array('status' => $json_status, 'error_code' => 422);
			echo json_encode($json_output);
			exit;
			
		}
		elseif (empty($passed_message)) {
			$json_status = 'message parameter is missing';
			$json_output[] = array('status' => $json_status, 'error_code' => 422);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_output[] = email_user($passed_subject, $passed_message, $passed_list, "");
			echo json_encode($json_output);
			exit;
		
		}
		
	}
	else {
		$json_status = 'user does not have the privileges to access this api';
		$json_output[] = array('status' => $json_status, 'error_code' => 401, 'ip_address' => $session_ip);
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