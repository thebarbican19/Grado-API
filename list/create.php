<?

include '../lib/auth.php';
include '../lib/keygen.php';
include '../lib/karma.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_title = $passed_data['title'];
$passed_description = $passed_data['description'];
$passed_items = $passed_data['items'];
$passed_latlng = explode(",", $passed_data['latlng']);
$passed_latitude = reset($passed_latlng);
$passed_longitude = end($passed_latlng);
$passed_key = $_GET['key'];
$passed_writeable = $passed_data['writable'];
$passed_visibility = $passed_data['public'];
$passed_image = $passed_data['image'];

if ($passed_visibility == "1" || strcasecmp($passed_visibility, "true") == 0 || strcasecmp($passed_visibility, "yes") == 0 || !isset($passed_visibility)) $passed_visibility = "1";
else $passed_visibility = "0";

if (empty($passed_writeable)) $passed_writeable = "owner";

if ($passed_method == 'POST') {
	if (empty($passed_title)) {
		$json_status = 'title parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}	
	else if (strlen($passed_title) > 50) {
		$json_status = 'title too long';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;		
	
	}
	else if (strlen($passed_description) > 200) {
		$json_status = 'description too long';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;	 
		
	}	
	else {
		$existing_query = mysqli_query("SELECT * FROM `lists` WHERE `list_owner` LIKE '$authuser_key' AND `list_title` LIKE '$passed_title' LIMIT 0 ,1");
		$existing_boolean = mysql_num_rows($existing_query);
		if ($existing_boolean == 0) {
			$item_primary = reset(explode(",", $passed_items));
			$item_query = mysqli_query("SELECT * FROM `content` WHERE `content_key` LIKE '$item_primary' AND `content_hidden` = 0 LIMIT 0, 1");
			$item_exists = mysql_num_rows($item_query);
			if ($item_exists == 1 || empty($passed_items)) {
				$item_data = mysql_fetch_assoc($item_query);
				$item_image = $item_data['content_images'];
				
				$list_title = str_replace("'", "\'", $passed_title);
				$list_title = str_replace("\n", " ", $list_title);
				$list_description = str_replace("'", "\'", $passed_description);
				$list_description = str_replace("\n", " ", $list_description);
				$list_key = "list_" . generate_key();		
				$list_add = mysqli_query("INSERT INTO `lists` (`list_id`, `list_key`, `list_timestamp`, `list_updated`, `list_owner`, `list_title`, `list_description`, `list_tags`, `list_writable`, `list_latitude`, `list_longitude`, `list_image`, `list_items`, `list_moderators`, `list_featured`, `list_public`, `list_hidden`) VALUES (NULL, '$list_key', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '$authuser_key', '$list_title', '$list_description', '$passed_tags', '$passed_writeable', '$passed_latitude', '$passed_longitude', '$item_image', '$passed_items', '$passed_moderators', 0, '$passed_visibility', 0);");
				if ($list_add) {
					$karma_title = "Organised";
					$karma_description = "You created your first list!";
					$karma_points = 10;
					$karma_return = add_karma($karma_points, $karma_title, $karma_description, $authuser_key);	
					
					$json_status = 'list was created';
					$json_output[] = array('status' => $json_status, 'error_code' => '200', 'tems' => $passed_items, 'visibility' => $passed_visibility, 'dasd' => $passed_data['public']);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'list could not be created - ' . mysql_error();
					$json_output[] = array('status' => $json_status, 'error_code' => '310');
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'item does not exist';
				$json_output[] = array('status' => $json_status, 'error_code' => '311');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'list exists with the name ' . $passed_title;
			$json_output[] = array('status' => $json_status, 'error_code' => '311');
			echo json_encode($json_output);
			exit;
				
		}
		
	}
	
}
else if ($passed_method == 'PUT') {
	if (empty($passed_key)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$list_query = mysqli_query("SELECT * FROM `lists` WHERE `list_key` LIKE '$passed_key' AND `list_hidden` = 0 LIMIT 0, 1");
		$list_exists = mysql_num_rows($list_query);
		$list_data = mysql_fetch_assoc($list_query);		
		if ($list_exists == 1) {		
			if (isset($passed_title)) $passed_title = str_replace("'", "\'", $passed_title);
			else $passed_title = $list_data['list_title'];
			
			if (isset($passed_description)) $passed_description = str_replace("'", "\'", $passed_description);
			else $passed_description = $list_data['list_description'];
			
			if (empty($passed_image)) $passed_image = $list_data['list_image'];
					
			$list_update = mysqli_query("UPDATE `lists` SET `list_title` = '$passed_title', `list_description` = '$passed_description', `list_image` = '$passed_image', `list_writable` = '$passed_writeable' WHERE `list_key` LIKE '$passed_key';");
			if ($list_update) {
				$json_status = 'list was sucsessfully updated';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'list could not be updated - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
							
		}
		else {
			$json_status = 'list does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '311');
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}	
else if ($passed_method == 'DELETE') {
	if (empty($passed_key)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$list_query = mysqli_query("SELECT * FROM `lists` WHERE `list_key` LIKE '$passed_key' AND `list_hidden` = 0 LIMIT 0, 1");
		$list_exists = mysql_num_rows($list_query);
		if ($list_exists == 1) {
			$list_data = mysql_fetch_assoc($list_query);
			$list_owner = $list_data['list_owner'];
			$list_delete = mysqli_query("UPDATE `lists` SET `list_hidden` = '1' WHERE `list_key` LIKE '$passed_key';");
			if ($list_delete) {
				$json_status = 'list was deleted';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'list could not be deleted - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'list does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '311');
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
	