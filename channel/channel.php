<?

include '../lib/auth.php';
include '../lib/analytics.php';
include '../lib/dataoutput.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_query = $_GET['query'];
$passed_title = strtolower($_GET['title']);
$passed_type = $_GET['type'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];
$passed_location = explode(",", $_GET['latlng']);
$passed_sections = $_GET['sections'];
 
$passed_latitude = reset($passed_location);
$passed_longitude = end($passed_location);

if (empty($_GET['limit'])) $passed_limit = 20;
if (empty($_GET['pangnation'])) $passed_pagenation = 0;
if (empty($_GET['type'])) $passed_types = explode(",", "staff,trending,user");
if (empty($_GET['sections'])) $passed_sections = explode(",", "trending,recent,nearby,subscriptions,featured,tailored");

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {	
	if (isset($passed_types)) {
		
	}
		
	if (empty($passed_query)) {
		$passed_types_injection = " AND (";
		$passed_types_append = 0;
		foreach ($passed_types as $key) {
			$passed_types_injection .= "`channel_type` LIKE '$key'";
			$passed_types_append ++;	
					
			if ($passed_types_append != count($passed_types)) $passed_types_injection .= " OR ";
			
		}
		
		$passed_types_injection .= ") ";
		
		if (in_array("featured", $passed_sections)) {
			$featured_query = mysql_query("SELECT `channel_key`, `channel_title`, `channel_type`, `channel_updated`, `channel_description`, `channel_header`, `channel_hidden`, `channel_verified` FROM channel WHERE (`channel_type` LIKE 'staff' OR `channel_verified` = 1) AND `channel_type` NOT LIKE 'local' AND `channel_hidden` = 0 ORDER BY channel_timestamp DESC LIMIT 0, 5");
			$featured_count = mysql_num_rows($featured_query);
			while($row = mysql_fetch_array($featured_query)) {
				//$exists_injection .= " AND `channel_key` NOT LIKE '" . $row['channel_key'] . "'";	
				$featured_output[] = channel_output($row);
				
			}
						
			if ($featured_count > 0) $channel_output[] = array("channels" => $featured_output, "type" => "featured", "title" => "Staff Picks");
						
		}
		
		if (in_array("subscriptions", $passed_sections) && isset($authuser_key)) {
			$subscriptions_query = mysql_query("SELECT `subscription_id`, `subscription_user` ,`subscription_channel`, `channel_key`, `channel_title`, `channel_type`, `channel_updated`, `channel_description`, `channel_header`, `channel_hidden`, `channel_verified`, COUNT(*) AS subscription_count FROM subscriptions LEFT JOIN channel on subscriptions.subscription_channel LIKE channel.channel_key WHERE `subscription_user` LIKE '$authuser_key' AND `channel_hidden` = 0 $passed_types_injection $exists_injection ORDER BY channel_updated DESC LIMIT 0, 100");
			$subscriptions_count = mysql_num_rows($subscriptions_query);
			while($row = mysql_fetch_array($subscriptions_query)) {	
				//$exists_injection .= " AND `channel_key` NOT LIKE '" . $row['channel_key'] . "'";		
				$subscribers_output[] = channel_output($row);
				
			}	
			
			if ($subscriptions_count > 0) $channel_output[] = array("channels" => $subscribers_output, "type" => "subscriptions", "title" => "My Subscriptions");
			
		}
		
		if (in_array("tailored", $passed_sections) && isset($authuser_key)) {
			$tailored_tags = return_user_tags($authuser_key);
			$tailored_injection = " AND (";
			$tailored_injection_append = 0;
			
			foreach ($tailored_tags as $tags) {
				$tailored_injection .= "`channel_tags` LIKE '%$tags%'";
				$tailored_injection_append ++;	
						
				if ($tailored_injection_append != count($tailored_tags)) $tailored_injection .= " OR ";
				
			}
			
			$tailored_injection .= ") ";
			
			$tailored_query = mysql_query("SELECT `subscription_id`, `subscription_channel`, `channel_key`, `channel_title`, `channel_type`, `channel_updated`, `channel_description`, `channel_header`, `channel_hidden`, `channel_verified`, COUNT(*) AS subscription_count FROM subscriptions RIGHT JOIN channel on subscriptions.subscription_channel LIKE channel.channel_key WHERE `channel_hidden` = 0 $passed_types_injection $exists_injection $tailored_injection GROUP BY subscription_channel ORDER BY subscription_count DESC, channel_updated DESC LIMIT 0, 100");
			$tailored_count = mysql_num_rows($tailored_query);
			while($row = mysql_fetch_array($tailored_query)) {	
				//$exists_injection .= " AND `channel_key` NOT LIKE '" . $row['channel_key'] . "'";			
				$tailored_output[] = channel_output($row);
				
			}
			
			if ($tailored_count > 0) $channel_output[] = array("channels" => $tailored_output, "type" => "tailored", "title" => "You may like");
			
		}
		
		if (in_array("trending", $passed_sections)) {
			$trending_query = mysql_query("SELECT `subscription_id`, `subscription_channel`, `channel_key`, `channel_title`, `channel_type`, `channel_updated`, `channel_description`, `channel_header`, `channel_hidden`, `channel_verified`, COUNT(*) AS subscription_count FROM subscriptions LEFT JOIN channel on subscriptions.subscription_channel LIKE channel.channel_key WHERE `channel_hidden` = 0 $passed_types_injection $exists_injection GROUP BY subscription_channel ORDER BY subscription_count DESC, channel_updated DESC LIMIT 0, 100");
			$trending_count = mysql_num_rows($trending_query);
			while($row = mysql_fetch_array($trending_query)) {	
				//$exists_injection .= " AND `channel_key` NOT LIKE '" . $row['channel_key'] . "'";			
				$trending_output[] = channel_output($row);
				
			}
			
			if ($trending_count > 0) $channel_output[] = array("channels" => $trending_output, "type" => "trending", "title" => "Trending");
						
		}	
		
		if (in_array("nearby", $passed_sections) && strlen($passed_location[0]) > 0 && strlen($passed_location[1]) > 0) {
			$nearby_query = mysql_query("SELECT `channel_key`, `channel_title`, `channel_type`, `channel_latitude`, `channel_longitude`, `channel_updated`, `channel_description`, `channel_header`, `channel_hidden`, `channel_verified`, (3959 * acos(cos(radians($passed_latitude)) * cos(radians(channel.channel_latitude)) * cos(radians(channel.channel_longitude) - radians($passed_longitude)) + sin(radians($passed_latitude)) * sin(radians(channel.channel_latitude)))) AS channel_distance FROM channel WHERE `channel_type` LIKE 'local' AND `channel_hidden` = 0 $exists_injection HAVING channel_distance < 25 ORDER BY channel_distance DESC, channel_updated DESC LIMIT 0, 10");
			$nearby_count = mysql_num_rows($nearby_query);
			while($row = mysql_fetch_array($nearby_query)) {	
				//$exists_injection .= " AND `channel_key` NOT LIKE '" . $row['channel_key'] . "'";				
				$nearby_output[] = channel_output($row);
				
			}
			
			
			if ($nearby_count > 0) $channel_output[] = array("channels" => $trending_output, "type" => "nearby", "title" => "Local");
						
		}		
				
		$json_status = 'returned ' . $featured_count + $subscriptions_count + $trending_count + $nearby_count . ' channels';
		$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $channel_output);
		echo json_encode($json_output);
		exit;
		 
	}
	else {
		$channel_query = mysql_query("SELECT * FROM `channel` LEFT JOIN users on channel.channel_owner LIKE users.user_key WHERE (`channel_key` LIKE '$passed_query' OR  `channel_title` LIKE  '$passed_query') AND `channel_hidden` = 0 AND `user_status` LIKE 'active' LIMIT $passed_pagenation, $passed_limit");
		$channel_exists = mysql_num_rows($channel_query);
		if ($channel_exists != 0) {
			$channel_data = mysql_fetch_assoc($channel_query);
			$channel_output = channel_output_extended($channel_data);
			$channel_key = (string)$channel_data['channel_key'];
			
			add_viewcount($channel_key, $authuser_key);
			
			$json_status = 'returned data for ' . $channel_title;
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $channel_output);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'no channel found';
			$json_output[] = array('status' => $json_status, 'error_code' => 404);
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
