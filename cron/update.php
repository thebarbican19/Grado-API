<?php

$_SERVER["HTTP_GDAPPKEY"] = "app_HAnda8123nsBZvs81sbZ7aoPi9s88s";
$_SERVER["HTTP_GDBEARER"] = "at_GRADMIN93712hd621hbn231dAWH2hs";

include '../lib/auth.php';
include '../lib/parser.php';
include '../lib/keygen.php';

/*
require '/var/www/html/gradoapp/api/v1/lib/auth.php';
require '/var/www/html/gradoapp/api/v1/lib/parser.php';
require '/var/www/html/gradoapp/api/v1/lib/keygen.php';
require '/var/www/html/gradoapp/api/v1/lib/push.php';
*/


$source_query = mysqli_query($database_grado_connect, "SELECT source_key, source_updated, source_updated, source_url, source_endpoint, source_type, source_latitude, source_longitude, channel_key, channel_owner, channel_title FROM sources LEFT JOIN channel ON FIND_IN_SET( sources.source_key, channel.channel_sources) > 0 ORDER BY source_updated ASC LIMIT 0 , 1");
$source_data = mysqli_fetch_assoc($source_query);
$source_url = $source_data['source_url'];
$source_endpoint = $source_data['source_endpoint'];
$source_key = $source_data['source_key'];
$source_title = $source_data['source_title'];
$source_type = $source_data['source_type'];
$source_latitude = (float)$source_data['source_latitude'];
$source_longitude = (float)$source_data['source_longitude'];
$source_channel = $source_data['channel_key'];
$source_channelname = $source_data['channel_title'];
$source_pushtag = array($source_channelname);

$source_timestamp = date("Y-m-d H:i:s");	
$source_update = mysqli_query($database_grado_connect, "UPDATE `sources` SET `source_updated` = '$source_timestamp' WHERE `source_key` LIKE '$source_key';");
$source_headers = array("ssl" => array("verify_peer" => false, "verify_peer_name" => false));
	
if ($source_type == "rss") {
	$source_feed = file_get_contents($source_url, false, stream_context_create($source_headers));
	$source_feed = simplexml_load_string($source_feed);
	
	$item_content = array();
	if (isset($source_feed->channel->item)) {
		foreach ($source_feed->channel->item as $item) {	
			$item_link = (string)$item->link;
			$item_title = (string)$item->title;
			$item_content[] = array("title" => $item_title, "url" => $item_link);
			
		}
		
	}
	else {
		foreach ($source_feed->channel->entry as $item) {	
			$item_link = (string)$item->link;
			$item_title = (string)$item->title;
			$item_content[] = array("title" => $item_title, "url" => $item_link);
			
		}	
		
	}
	
	$parse_object = $item_content[0];
	$parse_url = $parse_object['url'];
	
	print_r($item_content);
	
}
elseif ($source_type == "instagram") {
	//$twitter_access = "4780751332-X92CkvHh7Du6SFEJPBpOznMBmHDvqnFrPhj41hr";
	//$twitter_url = 
	//https://api.instagram.com/v1/users/{user-id}/?access_token=ACCESS-TOKEN
	
}
elseif ($source_type == "meetup") {
	$meetup_radius = "2.5";//miles
	$meetup_apikey = "17112b20c303d5e28261a64687d246b";
	$meetup_url = "https://api.meetup.com/find/events?key=" . $meetup_apikey . "&sign=true&radius=" . $meetup_radius . "&photo-host=public&lat=" . $source_latitude . "&lon=" . $source_longitude;
	$meetup_data = download_web_content($meetup_url);
	$meetup_data = json_decode($meetup_data);
	
}
elseif ($source_type == "twitter") {
	$twitter_access = "4780751332-X92CkvHh7Du6SFEJPBpOznMBmHDvqnFrPhj41hr";
	//$twitter_url = 
		
}
elseif ($source_type == "youtube") {
	$playlist_apikey = "AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg";
	$playlist_url = "https://www.googleapis.com/youtube/v3/playlistItems?key=" . $playlist_apikey . "&part=snippet&maxResults=1&playlistId=" . $source_endpoint;
	$playlist_data = download_web_content($playlist_url);
	$playlist_data = json_decode($playlist_data);
	print_r($playlist_data-items);
	$playlist_video = $playlist_data->items[0]->snippet->resourceId->videoId;
	$playlist_date = strtotime(reset(explode("T", $playlist_data->items[0]->snippet->publishedAt)));
	if ($playlist_date < strtotime('-2 days')) exit;
 	
	$parse_url = "https://www.youtube.com/watch?v=" . $playlist_video;
	
}
else {
	echo "Unknown Source Type/n";
	exit;
	
}

if (isset($parse_url)) {
	$content_output = parse_data($parse_url, "");
	$content_type = $content_output['type'];
	$content_title = $content_output['title'];		
	$content_description = $content_output['description'];
	$content_image = $content_output['image'];
	$content_media = $content_output['media'];
	$content_url = $content_output['url'];
	$content_api = $content_output['api'];
	$content_appkey = $content_output['key'];
	$content_site = $content_output['site'];	
	$content_tags = $content_output['tags'];	
	$content_icon = $content_output['icon'];
	$content_comment = $content_output['message'];
	
}

if (isset($content_title)) {
	$content_exists = mysqli_query($database_grado_connect, "SELECT * FROM  `content` WHERE  `content_owner` LIKE '$authuser_key' AND (`content_url` LIKE '$content_url' OR `content_title` LIKE '$content_title') AND `content_hidden` = 0 LIMIT 0 ,1");
	$content_count = mysqli_num_rows($database_grado_connect ,$content_exists);
	if ($content_count == 0) {
		$push_payload = json_encode(array("key" => $source_channel, "type" => "channel"));
		$push_title = "'" . $content_title . "' added to " . $source_channelname;
		//$push_output = sent_push_to_tag($source_pushtag, $push_payload, $push_title);

		$content_key = "itm_" . generate_key();
		//$content_post = mysqli_query($database_grado_connect, "INSERT INTO `content` (`content_id`, `content_timestamp`, `content_key`, `content_type`, `content_owner`, `content_message`, `content_title`, `content_description`, `content_url`, `content_site`, `content_images`, `content_icon`, `content_media`, `content_appkey`, `content_apiurl`, `content_tags`, `content_latitude`, `content_longitude`, `content_language`, `content_channels`, `content_auto`, `content_sponsored`, `content_comments`, `content_public`, `content_hidden`) VALUES (NULL, CURRENT_TIMESTAMP, '$content_key', '$content_type', '$authuser_key', '', '$content_title', '$content_description', '$content_url', '$content_site', '$content_image', '$content_icon', '$content_media', '$content_appkey', '$content_api', '$content_tags', '$source_latitude', '$source_longitude', 'en', '$source_channel', '1' ,'0', '1', '1', '0');");
		if ($content_post) echo "Content Posted from " . $content_site . " in " . $source_channel;
		else echo "Content not posted, unknown error";
		
	}
	else echo "Content already exists from source " . $content_site . " in " . $source_channel;
		
}
else echo "RSS URL is empty for " . $source_title;
	
?>
