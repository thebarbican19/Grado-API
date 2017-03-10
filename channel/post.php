<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/email.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_key = $passed_data['key'];
$passed_title = strtolower($passed_data['title']);
$passed_description = htmlspecialchars($passed_data['description'], ENT_QUOTES, 'UTF-8');
$passed_tags = $passed_data['tags'];
$passed_type = $passed_data['type'];
$passed_header = $passed_data['image'];
$passed_admins = $passed_data['admins'];
$passed_sources = $passed_data['sources'];
$passed_latitude = (float)$passed_data['latitude'];
$passed_longitude = (float)$passed_data['latitude'];

if (empty($passed_type)) $passed_type = "public";
if (empty($passed_description)) $passed_description = "";
if (empty($passed_header)) $passed_header = "";
if (empty($passed_tags)) $passed_tags = array();
else $passed_tags = explode(",", $passed_tags);
if (empty($passed_sources)) $passed_sources = array();
else $passed_sources = explode(",", $passed_sources);
if (empty($passed_admins)) $passed_admins = array();
else $passed_admins = explode(",", $passed_admins);

$allowed_types = array("local", "trending", "user", "staff");
$allowed_updates = array("sources", "admins", "header", "description", "tags");

if ($passed_method == 'POST') {
	$exists_query = mysqli_query($database_grado_connect, "SELECT * FROM  `channel` WHERE  `channel_title` LIKE  '$passed_title' LIMIT 0 ,1");
	$exists_bool = mysqli_num_rows($exists_query);
	if (empty($passed_title)) {
		$json_status = 'title parameter is missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (preg_match("/[^A-Za-z0-9]/", $passed_title)) {
		$json_status = 'title parameter must not contain any spaces or punctuation';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (count($passed_tags) == 0) {
		$json_status = 'tags parameter is missing';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (count($passed_tags) > 8) {
		$json_status = 'too many tags, 8 is the limit';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;	
		
	}
	elseif (!in_array($passed_type, $allowed_types)) {
		$json_status = 'type is not supported';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	elseif ($exists_bool == 1) {
		$json_status = "channel with the name '" . $passed_title . "' already exists";
		$json_output[] = array('status' => $json_status, 'error_code' => 409); //Conflict
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$channel_tags = implode(",", $passed_tags);
		$channel_admins = implode(",", $passed_admins);
		$channel_sources = implode(",", $passed_sources);
		$channel_key = "ch_" . generate_key();	
		$channel_post = mysqli_query($database_grado_connect, "INSERT INTO  `channel` (`channel_id` ,`channel_key` ,`channel_timestamp` ,`channel_updated` ,`channel_title` ,`channel_description` ,`channel_header` ,`channel_tags` ,`channel_type` ,`channel_sources` ,`channel_owner` ,`channel_admins` ,`channel_latitude` ,`channel_longitude` ,`channel_verified` ,`channel_hidden`) VALUES (NULL ,  '$channel_key',  '$session_timestamp', '$session_timestamp' ,  '$passed_title',  '$passed_description',  '$passed_header',  '$channel_tags,',  '$passed_type', '$channel_sources',  '$authuser_key',  '$channel_admins',  '$passed_latitude',  '$passed_longitude',  '0',  '0');");
		$channel_location = array("latitude" => $passed_latitude, "longitude" => $passed_longitude);
		$channel_output = array("title" => $passed_title, "description" => $passed_description, "type" => $passed_type, "header" => $passed_header, "tags" => $passed_tags, "coordinates" => $channel_location, "admins" => $passed_admins, "sources" => $passed_sources, "key" => $channel_key);
		if ($channel_post) {
			$email_mailinglist = email_new_mailinglist($channel_key, $passed_title);
			
			$json_status = 'channel sucsessfully added';
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'channel' => $channel_output);
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
	
}
elseif ($passed_method == 'PUT') {
	if (empty($passed_key)) {
		$json_status = 'key parameter required';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$existing_query = mysqli_query($database_grado_connect, "SELECT * FROM  `channel` WHERE  `channel_key` LIKE '$passed_key' AND `channel_hidden` = 0 LIMIT 0 ,1");
		$exitsing_data = mysqli_fetch_assoc($existing_query);
		$existing_title = $exitsing_data['channel_title'];
		$existing_owner = $exitsing_data['channel_owner'];		
		$existing_tags = explode(",", $exitsing_data['channel_tags']);
		$existing_sources = explode(",", $exitsing_data['channel_sources']);
		$existing_admins = explode(",", $exitsing_data['channel_admins']);		
		if ($existing_owner == $authuser_key || in_array($authuser_key, $existing_admins) || $authuser_type == "admin") {
			foreach ($passed_data as $key => $value) {
				if (in_array($key, $allowed_updates)) {
					if (count($sql_updated) > 0) $sql_injection .= " AND ";				
					if ($key == "sources") {
						if (isset($value) && !in_array($value, $existing_sources)) {
							$sql_string = implode(",", $existing_sources) . "," . $value;
							$sql_injection .= "`channel_sources` =  '" . $sql_string . "'";
							$sql_updated[] = $key;
					
						}
						
					}
					elseif ($key == "admins") {
						if (isset($value) && strpos($value, $existing_owner) == false) {
							$sql_string = $value;
							$sql_injection .= "`channel_admins` =  '" . $sql_string . "'";
							$sql_updated[] = $key;
												
						}
						
					}
					elseif ($key == "header") {
						if (strpos($value, "http") !== false) {
							$sql_string = $value;
							$sql_injection .= "`channel_header` =  '" . $sql_string . "'";
							$sql_updated[] = $key;
							
						}
						
					}
					elseif ($key == "description") {
						$sql_string = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
						$sql_injection .= "`channel_description` =  '" . $sql_string . "'";
						$sql_updated[] = $key;

					}
					
				}
				
			}
			
			if (count($sql_updated) == 0) {
				$json_status = 'nothing updated, please see documentation';
				$json_output[] = array('status' => $json_status, 'error_code' => 422);
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$channel_update = mysqli_query($database_grado_connect, "UPDATE `channel` SET  $sql_injection WHERE `channel_key` LIKE '$passed_key'");
				if ($channel_update) {
					$json_status = 'channel updated ' . implode(",", $sql_updated) . " sucsessfully";
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
			
		}
		else {
			$json_status = 'you do not have the permission to update this channel';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}
elseif ($passed_method == 'DELETE') {
	$passed_key = $_GET['key'];
	if (empty($passed_key)) {
		$json_status = 'key parameter required';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$existing_query = mysqli_query($database_grado_connect, "SELECT * FROM  `channel` WHERE  `channel_key` LIKE '$passed_key' LIMIT 0 ,1");
		$exitsing_data = mysqli_fetch_assoc($existing_query);
		$existing_owner = $exitsing_data['channel_owner'];	
		$existing_name = $exitsing_data['channel_title'];	
		$existing_hidden = (int)$exitsing_data['channel_hidden'];
		if (($existing_owner == $authuser_key || $authuser_type == "admin") && $existing_hidden == 0) {
			$channel_update = mysqli_query($database_grado_connect, "UPDATE `channel` SET `channel_hidden` = 1 WHERE `channel_key` LIKE '$passed_key'");
			if ($channel_update) {
				$mailing_delete = email_delete_mailinglist($existing_name);
										
				$json_status = "channel deleted sucsessfully";
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
		elseif ($existing_hidden == 1) {
			$json_status = 'this channel no longer exists';
			$json_output[] = array('status' => $json_status, 'error_code' => 403);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'you do not have the permission to delete this channel';
			$json_output[] = array('status' => $json_status, 'error_code' => 401);
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
