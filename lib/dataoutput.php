<?

function channel_output($data) {
	$channel_title = (string)$data['channel_title'];
	$channel_key = (string)$data['channel_key'];
	$channel_type = (string)$data['channel_type'];			
	$channel_updated = $data['channel_updated'];
	$channel_summary = (string)$data['channel_description'];
	$channel_header = (string)$data['channel_header'];
	$channel_hidden = (int)$data['channel_hidden'];
	$channel_verifyed = (int)$data['channel_verified'];	
	$channel_distance = (float)$data['channel_distance'];	
		
	return array('id' => $channel_key, 'type' => $channel_type, 'title' => $channel_title, 'description' => $channel_summary, 'lastupdated' => $channel_updated, 'header' => $channel_header, 'verifyed' => $channel_verifyed, 'removed' => $channel_hidden, 'distance' => $channel_distance);
	
}

function channel_output_extended($data) {
	global $authuser_key;	
		
	$channel_title = (string)$data['channel_title'];
	$channel_key = (string)$data['channel_key'];
	$channel_type = (string)$data['channel_type'];			
	$channel_uploaded = $data['channel_timestamp'];
	$channel_updated = $data['channel_updated'];
	$channel_summary = (string)$data['channel_description'];
	$channel_header = (string)$data['channel_header'];
	$channel_url = "https://gradoapp.com/" . $channel_title;
	$channel_tags = explode(",", $data['channel_tags']);
	$channel_admins = explode(",", $data['channel_admins']);
	$channel_latitude = (float)$data['channel_latitude'];
	$channel_longitude = (float)$data['channel_longitude'];
	$channel_coordinates = array("lat" => $channel_latitude, "lng" => $channel_longitude);
	$channel_hidden = (int)$data['channel_hidden'];
	$channel_verifyed = (int)$data['channel_verified'];			
	//$channel_stats = return_stats($channel_key);
	$channel_subscribed = channel_subscribed($authuser_key,$channel_key);
	$channel_canedit = channel_canedit($data);
		
	$channel_tagsf = array();
	foreach ($channel_tags as $tag) {
		if (strlen($tag) > 0) $channel_tagsf[] = $tag;
		
	}
				
	$channel_ownername = (string)$data['user_name'];
	$channel_ownerkey = (string)$data['user_key'];
	$channel_owneravatar = (string)$data['user_avatar'];
	$channel_owner = array('username' => $channel_ownername, 'id' => $channel_ownerkey, 'ovatar' => $channel_owneravatar);
								
	return array('id' => $channel_key, 'type' => $channel_type, 'title' => $channel_title, 'description' => $channel_summary, 'added' => $channel_uploaded, 'lastupdated' => $channel_updated, 'header' => $channel_header, 'publicurl' => $channel_url, 'owner' => $channel_owner, 'moderators' => $channel_admins, 'tags' => $channel_tagsf, 'location' => $channel_coordinates, 'removed' => $channel_hidden, 'subscribed' => $channel_subscribed, 'editable' => $channel_canedit);
	
}


function channel_subscribed($user,$subscription) {
	global $database_grado_connect;	
	
	$subscription_query = mysqli_query($database_grado_connect, "SELECT * FROM `subscriptions` WHERE `subscription_user` LIKE  '$user' AND `subscription_channel` LIKE '$subscription' LIMIT 0 ,1");
	$subscription_bool = mysqli_num_rows($subscription_query);
	
	return $subscription_bool;
	
}

function channel_canedit($data) {
	global $authuser_key;	
	global $authuser_type;	
	
	$channen_owner = $data['channel_owner'];
	$channen_moderators = explode(",", $data['channel_admins']);
		
	if ($authuser_key == $channen_owner) return 1;
	else if ($authuser_type == "admin") return 1;
	else if (in_array($authuser_key, $channen_moderators)) return 1;
	else return 0;
	
}

?>