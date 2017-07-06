<?php

$_SERVER["HTTP_GDAPPKEY"] = "app_HAnda8123nsBZvs81sbZ7aoPi9s88s";
$_SERVER["HTTP_GDBEARER"] = "at_GRADMIN93712hd621hbn231dAWH2hs";

require '/var/www/html/gradoapp/api/v1/lib/auth.php';
require '/var/www/html/gradoapp/api/v1/lib/keygen.php';
require '/var/www/html/gradoapp/api/v1/lib/slack.php';

$trending_expiry =  date('Y-m-d H:i:s', strtotime('-80 days'));
$trending_query = mysqli_query($database_grado_connect, "SELECT like_timestamp, like_content, COUNT(*) AS likes_count, GROUP_CONCAT(content_tags SEPARATOR ',') AS like_tags, GROUP_CONCAT(like_user SEPARATOR ',') AS like_users FROM likes LEFT JOIN content ON likes.like_content LIKE content.content_key WHERE like_timestamp > '$trending_expiry' AND content_hidden = 0 GROUP BY like_content HAVING likes_count > 0 AND like_users NOT LIKE '$authuser_key' ORDER BY likes_count DESC");
$trending_likes = 0;
$trending_tags = array();
while($row = mysqli_fetch_array($trending_query)) {	
	$trending_likes += (int)$row['likes_count'];
	foreach (explode(",", $row['like_tags']) as $tag) {
		if (strlen($tag) > 1) $trending_tags[] = $tag;
		
	}
	
}

$trending_threshold = round(($trending_likes / 100) * 8);
$trending_tags = array_count_values($trending_tags);
foreach($trending_tags as $key => $value) {
    if ($value >= $trending_threshold) $tags_output[] = $key;
		
}

foreach ($tags_output as $channel) {
	$channel_title = strtolower($channel);
	$channel_description = "This is auto generated channel for " . $channel . " and all things related.";
	$channel_create = channel_create($channel_title, $channel_description, $channel, "trending", "", $authuser_key);
			
}
	
if (count($tags_output) == 0) echo 'no trending channels to be created\n';

//Nearby Channels

$nearby_query = mysqli_query($database_grado_connect, "SELECT city_name, city_countrycode, city_latitude, city_longitude, COUNT(*) AS city_users FROM users LEFT JOIN cities ON users.user_city LIKE cities.city_name WHERE user_status LIKE  'active' AND city_name NOT LIKE '' AND city_countrycode LIKE user_country GROUP BY user_city HAVING city_users > 0 AND city_users < 100 ORDER BY city_users DESC");
$nearby_count = mysqli_num_rows($nearby_query);
while($row = mysqli_fetch_array($nearby_query)) {	
	$nearby_city = str_replace(" ", "", $row['city_name']);
	$nearby_country = $row['city_countrycode'];
	$nearby_latitude = (float)$row['city_latitude'];
	$nearby_longitude = (float)$row['city_longitude'];
	$nearby_location = array($nearby_latitude, $nearby_longitude);
		
	$channel_location = array($row['city_latitude'], $nearby_longitude);
	$channel_title = strtolower($nearby_city) . $nearby_country;
	$channel_description = "News, events, local gossip and everything else in the city of " . ucwords($nearby_city) . ", " . strtoupper($nearby_country) . ".";
	$channel_create = channel_create($channel_title, $channel_description, strtolower($nearby_city), "local", $nearby_location, $authuser_key);
	
	//Add sources
	if ($channel_create) {
		
	}
	
}
		
if (count($nearby_count) == 0) echo 'no nearby channels to be created\n';

function channel_image($tag, $location, $type) {
	if ($type == "trending") {
		$post_query = mysqli_query($database_grado_connect, "SELECT `content_images` FROM  `content` WHERE `content_tags` LIKE '%$tags%' AND `content_images` NOT LIKE '' ORDER BY `content_timestamp` DESC LIMIT 0 ,1");
		$post_last = mysql_fetch_assoc($post_query);
		$post_images = explode(",", $post_last['content_images']);
		
		return reset($post_images);
		
	}
	elseif ($type == "local") {
		$flickr_key = "e5d43d5d0af8184100f4d239728a90a3";
		$flickr_search_url = "https://api.flickr.com/services/rest/?method=flickr.photos.search&api_key=" . $flickr_key . "&lat=" . $location[0] . "&lon=" . $location[1] . "&radius=3&safe_search=1&per_page=1&media=photos&format=json&nojsoncallback=1&sort=relevance&tags=landscape&licence=2";		
		$flickr_headers = array("ssl" => array("verify_peer" => false, "verify_peer_name"=>false));
		$flickr_search = file_get_contents($flickr_search_url, false, stream_context_create($flickr_headers));
		$flickr_search = json_decode($flickr_search);
		$flickr_search_key = $flickr_search->photos->photo[0]->id;

		$flickr_image_url = "https://api.flickr.com/services/rest/?method=flickr.photos.getSizes&api_key=" . $flickr_key . "&photo_id=" . $flickr_search_key . "&format=json&nojsoncallback=1";		
		$flickr_image = file_get_contents($flickr_image_url, false, stream_context_create($flickr_headers));
		$flickr_image = json_decode($flickr_image);
		$flickr_image_output = "";
		for ($i = 0; $i < count($flickr_image->sizes->size); $i++) {
			if ($flickr_image->sizes->size[$i]->width == "800") $flickr_image_output = $flickr_image->sizes->size[$i]->source;
			
		}
		
		return $flickr_image_output;
		
	}
	
}

function channel_create($title, $description, $tags, $type, $location, $owner) {
	if (isset($location[0])) {
		$channel_latitude = $location[0];
		$channel_longitude = $location[1];
		
	}
	
	$channel_exists = 1;
	$channel_key = "ch_" . generate_key();
	$channel_timestamp = date('Y-m-d H:i:s');
	$channel_title = $title;
	$channel_description = $description;
	$channel_share = "http://gradoapp.com/" . $channel_title;
	if ($type == "trending") $channel_injection = " WHERE `channel_title` LIKE '$title'";
	elseif ($type == "local") $channel_injection = " WHERE `channel_title` LIKE '$title' OR (`channel_latitude` LIKE '$channel_latitude' AND `channel_longitude` LIKE '$channel_longitude')";
	
	$channel_query = mysqli_query($database_grado_connect, "SELECT * FROM `channel` $channel_injection LIMIT 0 ,1");
	$channel_exists = mysqli_num_rows($channel_query);
	if ($channel_exists == 0) {
		$channel_header = channel_image($tags, $location, $type);
		$channel_add = mysqli_query($database_grado_connect, "INSERT INTO `channel` (`channel_id` ,`channel_key` ,`channel_timestamp` ,`channel_updated` ,`channel_title` ,`channel_description` ,`channel_header` ,`channel_tags` ,`channel_type` ,`channel_sources` ,`channel_owner` ,`channel_admins` ,`channel_latitude` ,`channel_longitude` ,`channel_verified` ,`channel_hidden`) VALUES (NULL ,  '$channel_key',  '$channel_timestamp',  '$channel_timestamp',  '$channel_title',  '$channel_description',  '$channel_header',  '$tags',  '$type',  '',  '$owner',  '',  '$channel_latitude',  '$channel_longitude',  '0',  '0');");
		if ($channel_add) {
			$slack_fallback = "A new " . ucfirst($type) . " channel has been autocreated!";
			$slack_attachment[] = array("title" => $channel_title, "text" => $channel_description, "color" => "#36D38F", "thumb_url" => $channel_header, "title_link" => $channel_share);
			$slack_post = post_slack($slack_fallback, 'communities', $channel_header, $slack_attachment);
			
		}
		else echo 'failed creating channel ' . $title . " - " . mysqli_error($channel_add) . "\n";
		
	}
	else echo $type . ' channel ' . $title . ' already exists!\n';
		
}
	
?>
