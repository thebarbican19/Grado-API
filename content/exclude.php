<?

include '../lib/auth.php';
include '../lib/keygen.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_tags = explode(",", $passed_data['tags']);

if ($passed_method == 'POST') {
	if (empty($passed_data['tags'])) {
		$json_status = 'tags parameter must contain more than one tag';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		foreach ($passed_tags as $tag) {
			$tags_query = mysql_query("SELECT * FROM  `exclude` WHERE  `exclude_user` LIKE  '$authuser_key' AND  `exclude_tag` LIKE  '$tag' LIMIT 0, 1");
			$tags_existing = mysql_num_rows($tags_query);
			if ($tags_existing == 0) {
				if (strlen($tag) > 2) {
					$tag_post = mysql_query("INSERT INTO  `exclude` (`exclude_id` ,`exclude_user` ,`exclude_tag`) VALUES (NULL ,  '$authuser_key',  '$tag');");
					if ($tag_post) {
						$tags_added[] = $tag;
						
					}
					
				}
				
			}
			
		}
		
		if (count($tags_added) == 0) $tags_added = array();
		
		$json_status = count($tags_added) . ' tags added';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'tags' => $tags_added);
		echo json_encode($json_output);
		exit;
		
	}
	
}
elseif ($passed_method == 'GET') {
	$tags_query = mysql_query("SELECT * FROM  `exclude` WHERE  `exclude_user` LIKE  '$authuser_key'");
	$tags_count = mysql_num_rows($tags_query);
	while($row = mysql_fetch_array($tags_query)) {	
		$tags_output[] = $row['exclude_tag'];
							
	}
	
	if (count($tags_output) == 0) $tags_output = array();
			
	$json_status = $tags_count . ' tags returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'tags' => $tags_output);
	echo json_encode($json_output);
	exit;
	
	
}
elseif ($passed_method == 'DELETE') {
	if (empty($passed_data['tags'])) {
		$json_status = 'tags parameter must contain more than one tag';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		foreach ($passed_tags as $tag) {
			$tags_query = mysql_query("SELECT * FROM  `exclude` WHERE  `exclude_user` LIKE  '$authuser_key' AND  `exclude_tag` LIKE  '$tag' LIMIT 0, 1");
			$tags_existing = mysql_num_rows($tags_query);
			if ($tags_existing == 1) {
				$tag_delete = mysql_query("DELETE FROM `exclude` WHERE `exclude_user` LIKE '$authuser_key' AND `exclude_tag` LIKE '$tag';");
				if ($tag_delete) {
					$tags_removed[] = $tag;
					
				}
				
			}
			
		}
		
		if (count($tags_removed) == 0) $tags_removed = array();
		
		$json_status = count($tags_removed) . ' tags removed';
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'tags' => $tags_removed);
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