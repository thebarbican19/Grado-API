<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_type = $_GET['type'];
$passed_query = $_GET['search'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	if (empty($passed_type)) {
		$json_status = 'type parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "trending") {
			$trending_sql = "SELECT `subscription_timestamp`, `list_key`, `list_timestamp`, `list_updated`, `list_title`, `list_description`, `list_type`, `list_image`, `list_featured`, `list_items`, `list_hidden`, `user_key`, `user_name`, `user_avatar`, COUNT(*) AS subscribers_count FROM subscriptions LEFT JOIN lists on subscriptions.subscription_list LIKE lists.list_key LEFT JOIN users on lists.list_owner LIKE users.user_key WHERE subscription_timestamp > '$search_timestamp' AND `list_type` LIKE 'public' AND `list_hidden` = 0 GROUP BY subscription_list ORDER BY COUNT(*) DESC , subscription_timestamp DESC LIMIT $passed_pagenation, $passed_limit";	
			
			
		}
		elseif ($passed_type == "featured") {
			$trending_sql = "SELECT `subscription_timestamp`, `list_key`, `list_timestamp`, `list_updated`, `list_title`, `list_description`, `list_type`, `list_image`, `list_items`, `list_featured`, `list_hidden`, `user_key`, `user_name`, `user_avatar`, COUNT(*) AS subscribers_count FROM lists RIGHT JOIN subscriptions on lists.list_key LIKE subscriptions.subscription_list LEFT JOIN users on lists.list_owner LIKE users.user_key WHERE `list_type` LIKE 'public' AND `list_hidden` = 0 AND `list_featured` = 1 GROUP BY subscription_list ORDER BY COUNT(*) DESC, `list_updated` DESC LIMIT $passed_pagenation, $passed_limit";	
		}
		
		$trending_query = mysql_query($trending_sql);		
		$trending_count = mysql_num_rows($trending_query);
		while($row = mysql_fetch_array($trending_query)) {		
			$list_key = $row['list_key'];
			$list_timestamp = $row['list_timestamp'];
			$list_updated = $row['list_updated'];
			$list_title = $row['list_title'];
			$list_summary = $row['list_description'];
			$list_image = $row['list_image'];
			$list_items = explode(",", $row['list_items']);
			$list_featured = (int)$row['list_featured'];		
			$list_subscribers = (int)$row['subscribers_count'];
			
			if ($authuser_key == $row['user_key']) $owner_boolean = 1;	
			else $owner_boolean = 0;	
			$owner_key = $row['user_key'];
			$owner_username = $row['user_name'];
			$owner_avatar = $row['user_avatar'];
			$owner_data = array("key" => $owner_key, "username" => $owner_username, "avatar" => $owner_avatar);	
			
			$list_output[] = array("timestamp" => $list_timestamp, "key" => $list_key, "title" => $list_title, "summary" => $list_summary, "image" => $list_image, "items" => $item_count, "subscribers" => $list_subscribers, "owner" => $owner_data, "moderator" => $owner_boolean, 'featured' => $list_featured);
			
		}
		
		if (count($list_output) == 0) $list_output = array();
			
		$json_status = $trending_count . " lists returned";
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'list' => $list_output, 'sql' => $trending_sql);
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


?>