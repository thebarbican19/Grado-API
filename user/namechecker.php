<?

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_username = $_GET['username'];

if ($passed_method == 'GET') {
	if (empty($passed_username)) {
		$json_status = 'username parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (preg_match('/[^A-Za-z0-9-_]/', $passed_username)) {
		$json_status = 'username is contains invalid characters. Can only contain letters numbers';
		$json_output[] = array('status' => $json_status, 'error_code' => '310');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (strlen($passed_username) > 15) {
		$json_status = 'username is too long';
		$json_output[] = array('status' => $json_status, 'error_code' => '310');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (strlen($passed_username) < 3) {
		$json_status = 'username is too short';
		$json_output[] = array('status' => $json_status, 'error_code' => '310');
		echo json_encode($json_output);
		exit;
		
	}	
	else {
		$username_query = mysql_query("SELECT * FROM `users` WHERE `user_name` LIKE '$passed_username' LIMIT 0, 1");
		$username_exists = mysql_num_rows($username_query);
		if ($username_exists == 0) {
			$json_status = 'username is fair game';
			$json_output[] = array('status' => $json_status, 'error_code' => '200');
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'username is in use';
			$json_output[] = array('status' => $json_status, 'error_code' => '331');
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