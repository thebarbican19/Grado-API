<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_exclude = explode(",", $_GET['exclude']);

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	$list_query = mysqli_query("SELECT * FROM `lists` LEFT JOIN users on lists.list_owner LIKE users.user_key WHERE (`list_owner` LIKE '$authuser_key' OR `list_moderators` LIKE '$authuser_key') AND (`list_public` = 1 OR `list_owner` LIKE '$authuser_key') AND `list_hidden` = 0 ORDER BY `list_updated` DESC LIMIT $passed_pagenation, $passed_limit");
	$list_count = mysql_num_rows($list_query);
	while($row = mysql_fetch_array($list_query)) {	
		$list_key = $row['list_key'];
		$list_timestamp = $row['list_timestamp'];
		$list_updated = $row['list_updated'];
		$list_title = $row['list_title'];
		$list_summary = $row['list_description'];
		$list_type = $row['list_type'];
		$list_image = $row['list_image'];
		$list_featured = (int)$row['list_featured'];			
		$list_moderators = explode(",", $row['list_moderators']);
		$list_items = explode(",", $row['list_items']);
		$list_public = (int)$row['list_public'];
				
		$subscribers_query = mysqli_query("SELECT `subscription_key`, COUNT(*) AS `subscription_count` FROM `subscriptions` WHERE `subscription_list` LIKE '$list_key'");
		$subscribers_data = mysql_fetch_assoc($subscribers_query);
		$subscribers_count = (int)$subscribers_data['subscription_count'];
		
		$owner_key = $row['user_key'];
		$owner_username = $row['user_name'];
		$owner_avatar = $row['user_avatar'];
		$owner_data = array("key" => $owner_key, "username" => $owner_username, "avatar" => $owner_avatar);	
		
		if ($authuser_key == $owner_key) $list_type = "admin";
		elseif (in_array($authuser_key, $list_moderators)) $list_type = "moderator";
		else $list_type = "user";
		
		$item_list = array();
		foreach ($list_items as $item) {
			$item_query = mysqli_query("SELECT * FROM `content` WHERE `content_key` LIKE '$item' AND `content_hidden` = 0 LIMIT 0 ,1");
			$item_data = mysql_fetch_assoc($item_query);
			$item_key = $item_data['content_key'];
			if (isset($item_key)) $item_list[] = $item_key;
										
		}
		
		$list_items = count($item_list);
		$list_output[] = array("timestamp" => $list_timestamp, "key" => $list_key, "title" => $list_title, "summary" => $list_summary, "image" => $list_image, "items" => $list_items, "subscribers" => $subscribers_count, "usertype" => $list_type, 'featured' => $list_featured, 'public' => $list_public, "owner" => $owner_data);
	}
		
	if ($list_count == 0) $list_output = array();
	
	$json_status = $list_count . ' list returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'content' => $list_output);
	echo json_encode($json_output);
	exit;
						
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}
