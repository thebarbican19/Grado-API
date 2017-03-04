<?

include '../lib/auth.php';
include '../lib/keygen.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_list = $passed_data['list'];
$passed_item = $passed_data['item'];

if ($passed_method == 'POST') {
	if (empty($passed_list)) {
		$json_status = 'list parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else if (empty($passed_item)) {
		$json_status = 'item parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$list_query = mysqli_query("SELECT * FROM `lists` WHERE `list_key` LIKE '$passed_list' AND  `list_hidden` = 0 LIMIT 0, 1");
		$list_existing = mysql_num_rows($list_query);
		if ($list_existing == 1) {
			$list_data = mysql_fetch_assoc($list_query);
			$list_owner = $list_data['list_owner'];
			$list_public = (int)$list_data['list_public'];
			$list_writable = $list_data['list_writable'];
			$list_items = $list_data['list_items'];
			$list_image = $list_data['list_image'];
			$list_moderators = explode(",", $list_data['list_moderators']);
								
			if ($authuser_key != $list_owner && !in_array($authuser_key, $list_moderators)) {
				$json_status = 'you are not authorized to add items to this list';
				$json_output[] = array('status' => $json_status, 'error_code' => '301');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				if (!in_array($passed_item, explode(",", $list_items))) {
					if (empty($list_image)) {
						$item_query = mysqli_query("SELECT * FROM `content` WHERE `content_key` LIKE '$passed_item' AND `content_hidden` = 0 LIMIT 0, 1");
						$item_exists = mysql_num_rows($item_query);
						$item_data = mysql_fetch_assoc($item_query);
						$item_image = reset(explode(",", $item_data['content_image']));
						
						$image_update = ", `list_image` = '$item_image'";
					}
					
					$list_items = $list_items . "," . $passed_item;
					$list_update = mysqli_query("UPDATE `lists` SET `list_updated` = CURRENT_TIMESTAMP, `list_items` = '$list_items' $image_update WHERE `list_key` LIKE '$passed_list';");	
					if ($list_update) {
						$json_status = 'item was added to list';
						$json_output[] = array('status' => $json_status, 'error_code' => '200');
						echo json_encode($json_output);
						exit;
						
					}
					else {
						$json_status = 'item could not be added to list - ' . mysql_error();
						$json_output[] = array('status' => $json_status, 'error_code' => '310');
						echo json_encode($json_output);
						exit;
						
					}
								
				}
				else {
					$json_status = 'item already exists in list';
					$json_output[] = array('status' => $json_status, 'error_code' => '301');
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			
		}
		else {
			$json_status = 'list does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '301');
			echo json_encode($json_output);
			exit;
			
		}
			
	}
	
}
else if ($passed_method == 'DELETE') {
	$list_query = mysqli_query("SELECT * FROM `lists` WHERE `list_key` LIKE '$passed_list' AND  `list_hidden` = 0 LIMIT 0, 1");
	$list_existing = mysql_num_rows($list_query);
	if ($list_existing == 1) {
		$list_data = mysql_fetch_assoc($list_query);
		$list_owner = $list_data['list_owner'];
		$list_items = $list_data['list_items'];
		if (in_array($passed_item, explode(",", $list_items))) {
			$list_remove = "," . $passed_item;
			$list_items = str_replace($list_remove, "", $list_items);
			$list_update = mysqli_query("UPDATE `lists` SET `list_updated` = CURRENT_TIMESTAMP, `list_items` = '$list_items' WHERE `list_key` LIKE '$passed_list';");	
			
			if ($list_update) {
				$json_status = 'item was removed from list';
				$json_output[] = array('status' => $json_status, 'error_code' => '200');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'item could not be removed from list - ' . mysql_error();
				$json_output[] = array('status' => $json_status, 'error_code' => '310');
				echo json_encode($json_output);
				exit;
				
			}
							
		}
		else {
			$json_status = 'item does not exists in list';
			$json_output[] = array('status' => $json_status, 'error_code' => '301');
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	else {
		$json_status = 'list does not exist';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	
}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>