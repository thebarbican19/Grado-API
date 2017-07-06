<?php

include '../lib/auth.php';
include '../lib/analytics.php';
include '../lib/dataoutput.php';
include '../lib/push.php';
include '../lib/email.php';
include '../lib/slack.php';
include '../lib/keygen.php';

header("Content-type: application/json; charset=utf-8");

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_location = explode(",", $_GET['latlng']);
$passed_latitude = $passed_location[0];
$passed_longitude = $passed_location[1];
$passed_channel = $_GET['key'];

if ($passed_method == 'GET') {
	if (empty($passed_latitude) && empty($passed_longitude)) {
		$json_status = 'location parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$place_query = mysqli_query($database_grado_connect, "SELECT `channel_timestamp`, `channel_key`, `channel_title`, `channel_type`, `channel_latitude`, `channel_longitude`, `channel_updated`, `channel_description`, `channel_header`, `channel_hidden`, `channel_verified`, (3959 * acos(cos(radians($passed_latitude)) * cos(radians(channel.channel_latitude)) * cos(radians(channel.channel_longitude) - radians($passed_longitude)) + sin(radians($passed_latitude)) * sin(radians(channel.channel_latitude)))) AS channel_distance FROM channel WHERE `channel_hidden` = 0 HAVING channel_distance < 5 ORDER BY channel_distance ASC, channel_updated DESC LIMIT 0, 3");
		$place_exists = mysqli_num_rows($place_query);
		if ($place_exists == 0) {
			while($row = mysqli_fetch_array($place_query)) {
				$place_output[] = channel_output($row);
				
			}
			
			$json_status = 'location exists!';
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'places' => $place_output);
			echo json_encode($json_output);
			exit;
			
		}
		else {	
			$request_exists = mysqli_query($database_grado_connect, "SELECT * FROM `request` WHERE `request_user` LIKE '$authuser_key'");
			$request_exists = mysqli_num_rows($request_exists);
			if ($request_exists = 0) {
				$request_post = mysqli_query($database_grado_connect, "INSERT INTO `request` (`request_id`, `request_timestamp`, `request_user`, `request_latitude`, `request_longitude`, `request_city`) VALUES (NULL, CURRENT_TIMESTAMP, '$authuser_key', '$passed_latitude', '$passed_longitude', '$location_city');");
				
			}
			
			$request_count = mysqli_query($database_grado_connect, "SELECT * FROM `request` WHERE `request_city` LIKE '$location_city'");
			$request_count = mysqli_num_rows($request_count);
			
			if ($request_count % 2 == 0) {
				$slack_fallback = "" . $request_count . " requests have been made for the City of " . $location_city . ", " . strtoupper($location_country);
				$slack_attachment[] = array("title" => "", "text" => $slack_fallback, "color" => "#36D38F");		
				$slack_post = post_slack($slack_fallback, 'requested', $user_ovatar, $slack_attachment);
			
			}
	
			$json_status = $location_city . ' is not yet supported';
			$json_output[] = array('status' => $json_status, 'error_code' => 404, 'city' => $location_city, 'requests' => $request_count);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
		
}
else if ($passed_method == 'POST') {
	if (empty($passed_channel)) {
		$json_status = 'channel parameter is missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {		
		$channel_query = mysqli_query($database_grado_connect, "SELECT `channel_id`, `channel_key`, `channel_title` FROM `channel` WHERE `channel_key` LIKE '$passed_channel' AND channel_hidden = 0 LIMIT 0 ,1");
		$channel_exists = mysqli_num_rows($channel_query);
		$channel_data = mysqli_fetch_assoc($channel_query);
		$channel_name = $channel_data['channel_title'];
		if ($channel_exists == 1) {
			$subscribtion_query = mysqli_query($database_grado_connect, "SELECT subscription_id, subscription_key, channel_title, channel_type FROM subscriptions LEFT JOIN channel ON subscriptions.subscription_channel LIKE channel.channel_key WHERE subscription_channel LIKE '$passed_channel' AND subscription_user LIKE '$authuser_key' AND channel_hidden = 0 LIMIT 0 , 1");
			$subscribtion_exists = mysqli_num_rows($subscribtion_query);
			if ($subscribtion_exists == 0) {
				$subscribtion_key =  "sub_" . generate_key();		
				$subscribe_add = mysqli_query($database_grado_connect, "INSERT INTO  subscriptions (subscription_id ,subscription_timestamp ,subscription_key ,subscription_user ,subscription_channel ,subscription_email ,subscription_push) VALUES (NULL , CURRENT_TIMESTAMP ,  '$subscribtion_key',  '$authuser_key',  '$passed_channel',  '$passed_emails',  '$passed_push');");
				if ($subscribe_add) {
					if ($passed_emails == 1) $email_post = email_subscribe_mailinglist($channel_name, $authuser_email, $authuser_username);
					if ($passed_push == 1) subscribe_to_channel($authuser_username, $authuser_device, $channel_name);
					
					$json_status = 'subscription was added';
					$json_output[] = array('status' => $json_status, 'error_code' => 200);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'an uknown error occured ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => 400);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'already subscribed';
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'channel does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => 403);
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

?>