<?

include '../auth.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_date = $_POST['text'];
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
		if (empty($passed_date)) $passed_enddate = date('Y-m-d H:i:s');
		else {
			$passed_enddate = date('d/m/Y' ,strtotime($passed_date));
			//$passed_enddate = date('Y-m-d H:i:s', $passed_enddate);
		}
		//$passed_startdate = $passed_date . "-24 hours";
		$passed_startdate = date("Y-m-d H:i:s", strtotime('+3 hours', $passed_enddate));
		//$passed_startdate = date('Y-m-d H:i:s' ,strtotime($passed_startdate));
			
		//Post Count	
		$post_query = mysql_query("SELECT * FROM  `content` WHERE  `content_timestamp` > '$passed_startdate' AND `content_timestamp` < '$passed_enddate'");
		$post_count = mysql_num_rows($post_query);
		if (count($post_count) == 0) $post_count = "0";		
 		$json_attachement[] = array("title" => "Total Posts", "text" => $post_count, "color" => "#36D38F");
		
		//Likes Count
		$likes_query = mysql_query("SELECT * FROM  `likes` WHERE  `like_timestamp` > '$passed_startdate' AND `like_timestamp` < '$passed_enddate'");
		$likes_count = mysql_num_rows($likes_query);
		if (count($likes_count) == 0) $likes_count = "0";		
 		$json_attachement[] = array("title" => "Total Likes", "text" => $likes_count, "color" => "#36D38F");
		
		//Comments Count
		$comments_query = mysql_query("SELECT * FROM  `comments` WHERE  `comment_timestamp` > '$passed_startdate' AND `comment_timestamp` < '$passed_enddate'");
		$comments_count = mysql_num_rows($comments_query);
		if (count($comments_count) == 0) $comments_count = "0";	
 		$json_attachement[] = array("title" => "Total Comments Posted", "text" => $comments_count, "color" => "#36D38F");
		
		//Lists Created
		$lists_query = mysql_query("SELECT * FROM  `lists` WHERE  `list_timestamp` > '$passed_startdate' AND `list_timestamp` < '$passed_enddate'");
		$lists_count = mysql_num_rows($lists_query);
		if (count($lists_count) == 0) $lists_count = "0";
 		$json_attachement[] = array("title" => "Total Lists Created", "text" => $lists_count, "color" => "#36D38F");
		
		//New Users
		$newusers_query = mysql_query("SELECT * FROM  `users` WHERE  `user_signup` > '$passed_startdate' AND `user_signup` < '$passed_enddate'");
		$newusers_count = mysql_num_rows($newusers_query);
		if (count($newusers_count) == 0) $newusers_count = "0";		
 		$json_attachement[] = array("title" => "New Users", "text" => $newusers_count, "color" => "#36D38F");
	
		//Active Users
		$activeusers_query = mysql_query("SELECT * FROM  `users` WHERE  `user_lastactive` > '$passed_startdate' AND `user_lastactive` < '$passed_enddate' AND `user_status` LIKE 'active'");
		$activeusers_count = mysql_num_rows($activeusers_query);
		if (count($activeusers_count) == 0) $activeusers_count = "0";		
 		$json_attachement[] = array("title" => "Active Users", "text" => $activeusers_count, "color" => "#36D38F");
		
		//$json_fallback =  "Stats for " . date('D j F Y' ,strtotime($passed_startdate)) . " to " . date('D j F Y' ,strtotime($passed_enddate));
				$json_fallback =  "Stats for " . $passed_startdate . " to " . $passed_enddate . " passed " . $passed_date;
				
		$json_output = array("attachments" => $json_attachement, "response_type" => "in_channel", "text" => $json_fallback, "fallback" => $json_fallback);
		echo json_encode($json_output);
		exit;
		
			
			
		
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api.';
	$json_output = array('text' => $json_status, "response_type" => "in_channel", "fallback" => $json_status);
	echo json_encode($json_output);
	exit;
}

?>