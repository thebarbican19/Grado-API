<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/push.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_tag = $passed_data['tag'];
$passed_user = $passed_data['user'];
$passed_payload = $passed_data['payload'];
$passed_type = $passed_data['type'];
$passed_token = $passed_data['token'];

if ($authuser_type == "admin" && $session_ip == "82.23.4.163") {	
	if (empty($passed_type)) {
		$json_status = 'type parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_payload)) {
		$json_status = 'payload parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "pushtouser") $push = upload_push_token();
		else if ($passed_type == "uploadtok") $push = upload_push_token($passed_user, $passed_token);
		
		$json_status = 'called pushbots api';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $push);
		echo json_encode($json_output);
		exit;
		
	}
}
else {
	$json_status = 'user does not have the privileges to access this api';
	$json_output[] = array('status' => $json_status, 'error_code' => '349');
	echo json_encode($json_output);
	exit;
	
}

?>
