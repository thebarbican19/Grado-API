<?

include '../auth.php';
include '../email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_value = explode(" ", $_POST['text']);
$passed_command = $_POST['command'];
$passed_team = $_POST['team_id'];
$passed_token = $_POST['token'];

if ($passed_method == 'POST') {
	if (empty($passed_token)) {
		$json_status = 'Action not allowed.';
		$json_output = array('text' => $json_status, "response_type" => "in_channel", "fallback" => $json_status);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		foreach ($passed_value as $word) {
			if (filter_var($word, FILTER_VALIDATE_EMAIL) !== false) $email_address = $word;
			else if (strlen($word) > 1) $email_list = $word;
			
		}
			
		if (empty($email_address)) {
			$json_fallback = "does not contain a valid email address";		
			$json_status = 'User *' . $passed_value . '* could not be found.';
			$json_output = array("text" => $json_status, "fallback" => $json_fallback);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			if (empty($email_list)) $email_list = "news";
			$email_output = json_decode(email_subscribe_mailinglist($email_list, $email_address, ""));
							
			$json_fallback = $email_output->message;		
			$json_status = $json_fallback;
			$json_output = array("text" => $json_status, "fallback" => $email_output, "attachments" => $email_output);
			echo json_encode($json_output);
			exit;	
			
		}
	
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api.';
	$json_output = array('text' => $json_status, "response_type" => "in_channel", "fallback" => $json_status);
	echo json_encode($json_output);
	exit;
}

?>