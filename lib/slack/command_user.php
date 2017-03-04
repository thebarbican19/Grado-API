<?

include '../auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_value = $_POST['text'];
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
		$user_query = mysqli_query("SELECT * FROM `users` WHERE `user_key` LIKE '$passed_value' OR `user_email` LIKE '$passed_value' OR `user_name` LIKE '%$passed_value%' LIMIT 0, 1");
		$user_exists = mysql_num_rows($user_query);
		$user_output = mysql_fetch_assoc($user_query);
		$user_key = $user_output['user_key'];
		$user_name = $user_output['user_name'];
		$user_location = $user_output['user_location'];
		$user_email = $user_output['user_email'];
		$user_avatar = $user_output['user_avatar'];
		$user_type = $user_output['user_type'];
		$user_status = $user_output['user_status'];
		$user_fullname = $user_output['user_actualname'];		
		$user_language = $user_output['user_language'];		
		$user_lastactive = $user_output['user_lastactive'];			
		$user_signup = $user_output['user_signup'];	
		
		$stream_query = mysqli_query("SELECT * FROM `content` WHERE `content_owner` LIKE '$user_key' AND `content_hidden` = 0 ORDER BY `content_id` DESC");
		$stream_count = mysql_num_rows($stream_query);
		
		$user_return = "Email: *" . $user_email . "*\nFullname: *" . ucwords($user_fullname) . "*\nKey: *" . $user_key . "*\nStatus: *" . ucfirst($user_status) . "*\nLocation: *" . ucfirst($user_location) . "*\nSigned Up: * " . $user_signup . "*\nLast Active on: *" . $user_lastactive . "*\nUser Type: *" . ucfirst($user_type) . "*\nPosts: *" . (string)$stream_count . "*";
				
		if ($user_status == "active") $user_status_colour = "#36D38F";	
		else $user_status_colour = "#E36466";
		
		if ($user_exists == 1) {
			$json_fallback =  "Found user matching " . $passed_value;
			$json_attachement[] = array("title" => $user_name, "text" => $user_return, "color" => $user_status_colour, "author_icon" => $user_avatar);
			$json_output = array("attachments" => $json_attachement, "response_type" => "in_channel", "text" => $json_fallback, "fallback" => $json_fallback);
			echo json_encode($json_output);
			exit;
			
			
		}
		else {
			$json_fallback = $passed_value . " could not be found";		
			$json_status = 'User *' . $passed_value . '* could not be found.';
			$json_output = array("text" => $json_status, "fallback" => $json_fallback);
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