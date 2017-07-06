<?php

header("Content-type: application/json; charset=utf-8");

//$database_grado_connect = mysqli_connect('localhost', 'phpmyadmin', '6DvPEpvwZLRcqj'); 
$database_grado_connect = mysqli_connect('localhost', 'root', 'root'); 
if (!$database_grado_connect) { 
	header('HTTP/ 400 HOST ERROR', true, 400);
		
	$json_status = 'host not connected';
    $json_output[] = array('status' => $json_status, 'error_code' => 400);
	echo json_encode($json_output);
	exit;
	
} 

$database_grado_table = mysqli_select_db($database_grado_connect, "gradoapp");
if (!$database_grado_table) { 
	header('HTTP/ 400 DATABASE ERROR', true, 400);
			
	$json_status = 'database table not found';
    $json_output[] = array('status' => $json_status, 'error_code' => 400);
	echo json_encode($json_output);
	exit;
	
}

$session_info = file_get_contents('http://ip-api.com/json');
$session_info = json_decode($session_info);
$session_country = strtolower($session_info->countryCode);
$session_isp = $session_info->isp;
$session_headers = $_SERVER;
$session_referal = $_SERVER['HTTP_REFERER'];
$session_hosturl = $_SERVER['HTTP_HOST'];
$session_ip = $_SERVER['REMOTE_ADDR'];
$session_url =  $_SERVER["SERVER_NAME"] . reset(explode('?', $_SERVER["REQUEST_URI"]));
$session_page = str_replace(".php", "", basename($session_url));
$session_exclude = array("signup", "authenticate", "namechecker", "mailing", "avatar", "reset", "command_user", "command_message", "command_stats", "command_addmailing", "stream", "flag", "search", "vidparser", "mailing", "keywords", "feedback", "channel", "sitemap");
$session_bearer = $session_headers["HTTP_GDBEARER"];
$session_application = $session_headers["HTTP_GDAPPKEY"];
if (empty($session_headers["HTTP_GDAPPKEY"])) $session_application = $_GET['appkey'];
else $session_application = $session_headers["HTTP_GDAPPKEY"];
$session_language = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$session_method = $_SERVER['REQUEST_METHOD'];
$session_slackteam = $_POST['team_id'];

if (isset($_GET['latlng'])) $session_latlng = explode(",", $_GET['latlng']);
elseif (isset($_POST['latlng'])) $session_latlng = explode(",", $_POST['latlng']);

date_default_timezone_set($session_info->timezone);

$session_timestamp = date("Y-m-d H:i");
$session_timezone_difference = date("P");
$session_timezone_update = mysqli_query($database_grado_connect, "SET `time_zone` = '$session_timezone_difference'");

if (empty($session_language)) $session_language = "";
if (isset($session_application) || $session_slackteam == 'T06KCC0D6') {
	$application_query = mysqli_query($database_grado_connect ,"SELECT * FROM `applications` WHERE `app_key` LIKE '$session_application' AND `app_status` LIKE 'active' LIMIT 0, 1");
	$application_exists = mysqli_num_rows($application_query);
	$application_output = mysqli_fetch_assoc($application_query);
	$application_name = $application_output['app_name'];
	$application_description = $application_output['app_description'];
	$application_domain = $application_output['app_domain'];
	$application_scope = explode(",", $application_output['app_scope']);
	if ($application_exists == 0 && (empty($application_domain) || $application_domain == $session_referal)) {
		header('HTTP/1.1 400 APPLICATION KEY INVALID', true, 400);
			
		$json_status = 'application key is invalid';
		$json_output[] = array('status' => $json_status, 'error_code' => 400, 'appdom' => $application_domain, 'host' => $session_hosturl);
		echo json_encode($json_output);
		exit;
			
	}
	
}
else {
	$session_directory = explode("/" ,dirname($_SERVER['PHP_SELF']));
	if (end($session_directory) != "assets") {
		header('HTTP/1.1 400 APPLICATION KEY NOT PASSED');
				
		$json_status = 'application key was not passed';
		$json_output[] = array('status' => $json_status, 'error_code' => 371);
		echo json_encode($json_output);
		exit;
		
	}
	
}

if (!in_array($session_page, $session_exclude) || $session_method == "DELETE" || isset($session_bearer)) {
	if (empty($session_bearer)) {	
		header('HTTP/1.1 401', true, 401);
				
		$json_status = 'bearer token was not passed';
		$json_output[] = array('status' => $json_status, 'error_code' => 401);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$authentication_date = date('Y-m-d H:i:s');
		$authentication_query = mysqli_query($database_grado_connect, "SELECT * FROM `access` WHERE `access_expiry` > '$authentication_date' AND `access_token` LIKE '$session_bearer' AND `access_app` LIKE '$session_application' LIMIT 0 ,1");
		$authentication_exists = mysqli_num_rows($authentication_query);
		$authentication_data = mysqli_fetch_assoc($authentication_query);
		$authentication_expiry = $authentication_data['access_expiry'];
		$authentication_user = $authentication_data['access_user'];
		$authentication_scope = explode("," ,$authentication_data['access_scope']);	
		if ($authentication_exists == 0) {
			header('HTTP/1.1 401', true, 401);
			
			$json_status = 'bearer token is invalid';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$authuser_query = mysqli_query($database_grado_connect ,"SELECT * FROM `users` LEFT JOIN applications on users.user_key LIKE applications.app_owner WHERE `user_key` LIKE '$authentication_user' AND `user_status` LIKE 'active'");
			$authuser_exists = mysqli_num_rows($authuser_query);
			$authuser_data = mysqli_fetch_assoc($authuser_query);
			$authuser_key = $authuser_data['user_key'];
			$authuser_username = $authuser_data['user_name'];
			$authuser_location = $authuser_data['user_location'];
			$authuser_email = $authuser_data['user_email'];
			$authuser_avatar = $authuser_data['user_avatar'];
			$authuser_type = $authuser_data['user_type'];
			$authuser_status = $authuser_data['user_status'];
			$authuser_fullname = $authuser_data['user_actualname'];
			$authuser_language = $authuser_data['user_language'];
			$authuser_lastactive = $authuser_data['user_lastactive'];
			$authuser_signup = $authuser_data['user_signup'];	
			$authuser_slack = $authuser_data['user_slack'];
			$authuser_device = $authuser_data['user_device'];
			$authuser_customer = $authuser_data['user_customer'];
			
			if (empty($authuser_data['app_key'])) {
				$application_key = $authuser_data['app_key'];
				$application_title = $authuser_data['app_title'];
				$application_summary = $authuser_data['app_description'];
				$application_platform = $authuser_data['app_platfrom'];			
				$application_data = array("key" => $application_key, "title" => $application_title, "summary" => $application_summary, "platform" => $application_platform);
				
			}
			else $application_data = array();
					
			if ($authuser_exists == 0) {
				$json_status = 'user does not exist';
				$json_output[] = array('status' => $json_status, 'error_code' => 350);
				echo json_encode($json_output);
				exit;
			
			}
			else {
				if (isset($session_latlng)) {
					$location_latitude = $session_latlng[0];
					$location_longitude = $session_latlng[1];
					$location_query = mysqli_query($database_grado_connect, "SELECT *, (3959 * acos(cos(radians($location_latitude)) * cos(radians(city_latitude)) * cos(radians(city_longitude) - radians($location_longitude)) + sin(radians($location_latitude)) * sin(radians(city_latitude)))) AS location_distance FROM cities WHERE (city_latitude NOT LIKE '0' AND city_latitude NOT LIKE '0') AND city_name NOT LIKE '' ORDER BY `location_distance` ASC, `city_population` ASC LIMIT 0, 1");
					$location_exists = mysqli_num_rows($location_query);
					if ($location_exists == 1) {
						$location_data = mysqli_fetch_assoc($location_query);
						$location_city = $location_data['city_name'];
						$location_country = $location_data['city_countrycode'];
									
						setcookie('gd_location_city', $location_city, 60*60*24, '/');
	
						$authuser_update = mysqli_query($database_grado_connect, "UPDATE `users` SET `user_lastactive` = CURRENT_TIMESTAMP, `user_city` = '$location_city', `user_country` = '$location_country' WHERE `user_key` LIKE '$authuser_key';");
						
					}
					else {
						$authuser_update = mysqli_query($database_grado_connect, "UPDATE `users` SET `user_lastactive` = CURRENT_TIMESTAMP WHERE `user_key` LIKE '$authuser_key';");
						
					}
					
				}
				else {
					$authuser_update = mysqli_query($database_grado_connect, "UPDATE `users` SET `user_lastactive` = CURRENT_TIMESTAMP WHERE `user_key` LIKE '$authuser_key';");
					
				}
								
			}
			
		}
		
	}
	
}  

?>
