<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_item = $passed_data['item'];
$passed_message = $passed_data['message'];
$passed_recipients = explode(",", $passed_data['recipients']);
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_include = explode(",", $_GET['include']);

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;
if (empty($passed_include)) $passed_include = explode(",", "text,url,video,image,audio");

$passed_pagenation = $passed_pagenation * $passed_limit;

foreach ($passed_include as $include) {
	$include_injection .= "`content_type` LIKE '$include'";
	$include_count ++;
	if ($include_count != count($passed_include)) $include_injection .= " OR ";
	
}

$include_injection =  "(" . $include_injection . ") AND ";

if ($passed_method == 'GET') {
	$recommendations_query = mysqli_query("SELECT * FROM  `recommended` LEFT JOIN users on recommended.recommended_sender LIKE users.user_key WHERE `recommended_recipients` LIKE '%$authuser_key%' ORDER BY `recommended_timestamp` DESC LIMIT $passed_pagenation, $passed_limit");
	$recommendations_count = mysql_num_rows($recommendations_query);
	while($row = mysql_fetch_array($recommendations_query)) {	
		$recommendations_item = $row['recommended_item'];
		$recommendations_sender_key = $row['user_key'];
		$recommendations_sender_name = $row['user_name'];
		$recommendations_sender_avatar = $row['user_avatar'];
						
		$stream_query = mysqli_query("SELECT * FROM `content` LEFT JOIN users on content.content_owner LIKE users.user_key  WHERE `content_key` LIKE '' AND `content_hidden` = 0 AND user_status LIKE 'active' ORDER BY `content_timestamp` DESC LIMIT 0, 1");
		$stream_data = mysql_fetch_assoc($stream_query);
		$stream_timestamp = $row['content_timestamp'];
		$stream_key = $row['content_key'];
		$stream_type = $row['content_type'];
		$stream_title = $row['content_title'];
		$stream_description = substr($row['content_description'], 0, 100) . '...';					
		$stream_message = $row['content_message'];
		$stream_url = $row['content_url'];
		$stream_site = $row['content_site'];		
		$stream_image = reset(explode(",", $row['content_images']));
		$stream_tags = explode(",", $row['content_tags']);
		$stream_owner = $row['content_owner'];				
		$stream_comments = (int)$row['content_comments'];
		$stream_hidden = (int)$row['content_hidden'];
		$stream_sender_output = array("username" => $recommendations_sender_name, "key" => $recommendations_sender_key ,"avatar" => $recommendations_sender_avatar);
			
		$stream_output = array("timestamp" => $stream_timestamp, "key" => $stream_key, "type" => $stream_type, "message" => $stream_message, "title" => $stream_title, "description" => $stream_description, "url" => $stream_url, "site" => $stream_site, "image" => $stream_image, "comments_allowed" => $stream_comments, "likes" => $like_count, "comments" => $comment_output, "owner" => $stream_user_output, "tags" => $stream_tags, 'content_liked' => $liked_boolean, 'sender' => $stream_sender_output);
	}
	
	if ($stream_count == 0) $stream_output = array();
	
	$json_status = $recommendations_count . ' items returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'content' => $stream_output);
	echo json_encode($json_output);
	
}
else if ($passed_method == 'POST') {
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}