<?

include '../lib/auth.php';
include '../lib/karma.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_include = explode(",", $_GET['include']);
$passed_location = explode(",", $_GET['latlng']);
$passed_search = str_replace("&", "and", $_GET['query']);
$passed_keywords = explode(" ", $passed_search);

$passed_latitude = reset($passed_location);
$passed_longitude = end($passed_location);

if (empty($_GET['limit'])) $passed_limit = 10;
if (empty($_GET['pangnation'])) $passed_pagenation = 0;

if ($passed_method == 'GET') {
	if (empty($passed_search)) {
		$expiry_query = mysql_query("SELECT * FROM `trending` ORDER BY `trending_id` ASC LIMIT 0, 12");
		$expiry_data = mysql_fetch_assoc($expiry_query);
				
		$search_timestamp = $expiry_data['trending_timestamp'];
		$search_sql = "SELECT `trending_keyword`, `trending_user`, `trending_latitude`, `trending_longitude`, COUNT(*) AS trending_count FROM trending WHERE trending_timestamp > '$search_timestamp' GROUP BY trending_keyword ORDER BY COUNT(*) DESC, trending_timestamp DESC LIMIT $passed_pagenation, $passed_limit";
		$search_query = mysql_query($search_sql);
		$search_count = mysql_num_rows($search_query);
		while($row = mysql_fetch_array($search_query)) {	
			$search_output[] = array("keyword" => $row['trending_keyword'], "type" => "keyword");
			
		}
		
		if (count($search_output) == 0) $search_output = array();		
		
		$json_status = 'returned ' . $search_count . ' results';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $search_output);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$search_output[] = array("keyword" => $passed_search, "type" => "keyword");		
		$search_exists[] = $passed_search;	
							
		if (substr($passed_search, 0, 1) === "@" && $authuser_type == "admin") {
			$search_username = str_replace("@", "", $passed_search);
			$search_sql = "SELECT `user_key`, `user_lastactive`, `user_status`, `user_email`, `user_name`, `user_avatar` FROM `users` WHERE (user_name LIKE '%$search_username%' OR user_email LIKE '%$search_username%') ORDER BY `user_lastactive` DESC LIMIT $passed_pagenation, $passed_limit";
			$search_query = mysql_query($search_sql);
			$search_count = mysql_num_rows($search_query);
			while($row = mysql_fetch_array($search_query)) {	
				$user_key = $row['user_key'];
				$user_name = $row['user_name'];
				$user_status = $row['user_status'];
				$user_email = $row['user_email'];
				$user_avatar = $row['user_avatar'];
				$user_lastactive = $row['user_lastactive'];
				$user_stats = return_user_stats($user_key);
			
				$search_output[] = array("key" => $user_key, "username" => $user_name, "email" => $user_email, "avatar" => $user_avatar,"lastactive" => $user_lastactive, "status" => $user_status, "stats" => $user_stats, "type" => "user");	
									
			}
				
		}
		else {
			$search_sql = "SELECT `content_title`, `content_tags`, `content_type`, `content_description`, COUNT(*) FROM content RIGHT JOIN likes on content.content_key LIKE likes.like_content WHERE content_tags LIKE '%$passed_search%' AND content_key NOT LIKE '' AND (content_public = 1 OR content_owner LIKE '$authuser_key') AND content_hidden = 0 GROUP BY like_content ORDER BY COUNT(*) DESC, like_timestamp DESC LIMIT $passed_pagenation, $passed_limit";
			$search_query = mysql_query($search_sql);
			$search_count = mysql_num_rows($search_query);
			while($row = mysql_fetch_array($search_query)) {
				$search_keywords = explode("," ,$row['content_tags']);			
				foreach($search_keywords as $search_keyword) {
	        		if (stripos($search_keyword, $passed_search) !== false) {	
						if (!in_array($search_keyword, $search_exists)) {
							$search_output[] = array("keyword" => $search_keyword, "type" => "keyword");
							$search_exists[] = $search_keyword;	
							
						}	
						
					}
					
	    		}
				
			}	
				
			/*
			if ($search_count < 5) {
				$search_sql = "SELECT *, COUNT(*) AS likes_count FROM likes LEFT JOIN content on likes.like_content LIKE content.content_key WHERE content_title LIKE '%$passed_search%' AND content_key NOT LIKE '' AND content_hidden = 0 GROUP BY like_content ORDER BY COUNT(*) DESC, like_timestamp DESC LIMIT $passed_pagenation, $passed_limit";
				$search_query = mysql_query($search_sql);
				$search_count = mysql_num_rows($search_query);
				while($row = mysql_fetch_array($search_query)) {	
					$search_type = $row['content_type'];		
					$search_item = $row['content_key'];	
					
					if ($search_type == "url")	$search_output[] = array("keyword" => $row['content_title'], "type" => $search_type, "item" => $search_item);
					else $search_media_output[] = array("keyword" => $row['content_title'], "type" => $search_type, "item" => $search_item);
											
				}
								
			}
			*/
			
		}
	
		if (count($search_media_output) == 0) $search_media_output = array();
		if (count($search_output) == 0) $search_output = array();
		else {
			$karma_title = "Detective " . $authuser_username;
			$karma_description = "You searched for something in Grado";
			$karma_points = 5;
			$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);	
			
		}
			
		$json_status = 'returned ' . $search_count . ' results';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $search_output, 'media' => $search_media_output);
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

