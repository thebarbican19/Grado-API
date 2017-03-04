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
	
	return array('id' => $channel_key, 'type' => $channel_type, 'title' => $channel_title, 'description' => $channel_summary, 'lastupdated' => $channel_updated, 'header' => $channel_header, 'verifyed' => $channel_verifyed, 'removed' => $channel_hidden);
	
}

function channel_output_extended($data) {
	$channel_title = (string)$data['channel_title'];
	$channel_key = (string)$data['channel_key'];
	$channel_type = (string)$data['channel_type'];			
	$channel_uploaded = $data['channel_timestamp'];	
	$channel_updated = $data['channel_updated'];
	$channel_summary = (string)$data['channel_description'];
	$channel_header = (string)$data['channel_header'];
	$channel_url = "http://gradoapp.com/" . $channel_title;
	$channel_tags = explode(",", $data['channel_tags']);
	$channel_admins = explode(",", $data['channel_admins']);
	$channel_latitude = (float)$data['channel_latitude'];
	$channel_longitude = (float)$data['channel_longitude'];
	$channel_coordinates = array("lat" => $channel_latitude, "lng" => $channel_longitude);
	$channel_hidden = (int)$data['channel_hidden'];
	$channel_verifyed = (int)$data['channel_verified'];			
	$channel_stats = return_stats($channel_key);
	$channel_subscribed = channel_subscribed($authuser_key,$channel_key);
		
	$channel_tagsf = array();
	foreach ($channel_tags as $tag) {
		if (strlen($tag) > 0) $channel_tagsf[] = $tag;
		
	}
				
	$channel_ownername = (string)$channel_data['user_name'];
	$channel_ownerkey = (string)$channel_data['user_key'];
	$channel_owneravatar = (string)$channel_data['user_avatar'];
	$channel_owner = array('username' => $channel_ownername, 'id' => $channel_ownerkey, 'ovatar' => $channel_owneravatar);
								
	return array('id' => $channel_key, 'type' => $channel_type, 'title' => $channel_title, 'description' => $channel_summary, 'added' => $channel_uploaded, 'lastupdated' => $channel_updated, 'header' => $channel_header, 'publicurl' => $channel_url, 'owner' => $channel_owner, 'moderators' => $channel_admins, 'tags' => $channel_tagsf, 'location' => $channel_coordinates, 'stats' => $channel_stats, 'verifyed' => $channel_verifyed, 'removed' => $channel_hidden, 'subscribed' => $channel_subscribed);
	
}


function channel_subscribed($user,$subscription) {
	$subscription_query = mysqli_query("SELECT * FROM `subscriptions` WHERE `subscription_user` LIKE  '$user' AND `subscription_channel` LIKE '$subscription' LIMIT 0 ,1");
	$subscription_bool = mysql_num_rows($subscription_query);
	
	return $subscription_bool;
	
}

?>