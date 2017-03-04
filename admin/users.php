<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_type = $_GET['type'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_query = $_GET['query'];
$passed_order = $_GET['order'];

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;
if (empty($passed_order)) $passed_order = "user_signup";
if (isset($passed_query)) $passed_query = "WHERE (`user_email` LIKE '%$passed_query%' OR `user_name` LIKE '%$passed_query%' OR `user_key` LIKE '%$passed_query%')";

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	if ($authuser_type == "admin" && $session_ip == "82.23.4.163") {	
		$user_query = mysql_query("SELECT * FROM  `users` $passed_query ORDER BY `$passed_order` ASC LIMIT $passed_pagenation, $passed_limit");
		$user_count = mysql_num_rows($user_query);
		while($row = mysql_fetch_array($user_query)) {	
			$user_key = $row['user_key'];
			$user_name = $row['user_name'];
			$user_fullname = $row['user_actualname'];
			$user_email = $row['user_email'];
			$user_type = $row['user_type'];
			$user_avatar = $row['user_avatar'];
			$user_location = $row['user_location'];
			$user_language = $row['user_language'];
			$user_lastactive = $row['user_lastactive'];
			$user_signup = $row['user_signup'];
			$user_slack = $row['user_slack'];
			$user_stats = return_user_stats($user_key);
			
			$user_output[] = array("key" => $user_key, "username" => $user_name, "actualname" => $user_fullname, "email" => $user_email, "type" => $user_type, "slack" => $user_slack, "avatar" => $user_avatar, "location" => $user_location, "langauge" => $user_language, "lastactive" => $user_lastactive, "signup" => $user_signup, "stats" => $user_stats);	
								
		}
		
		if (count($user_output) == 0) $user_output = array();
		
		$json_status = 'returned ' . $user_count . ' users';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'users' => $user_output);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'user does not have the privileges to access this api';
		$json_output[] = array('status' => $json_status, 'error_code' => '349', 'ip_address' => $session_ip);
		echo json_encode($json_output);
		exit;
		
	}
	
}
else {
	$json_status = $passed_method . ' menthods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => '380');
	echo json_encode($json_output);
	exit;
	
}

?>