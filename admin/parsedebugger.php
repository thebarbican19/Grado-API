<?

include '../lib/auth.php';
include '../lib/parser.php';

if ($authuser_type == "admin") {
	$parse_url = $_GET['url'];
	$parse_message = $_GET['message'];
	$parse_output = parse_data($parse_url, $parse_message);
	
	if (empty($parse_url)) {
		$json_status = 'url parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'output for ' . $parse_url;
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $parse_output);
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
