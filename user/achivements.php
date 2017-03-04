<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_limit = $_GET['limit'];
$passed_pagenation = $_GET['pangnation'];

if (empty($passed_limit)) $passed_limit = 20;
if (empty($passed_pagenation)) $passed_pagenation = 0;

$passed_pagenation = $passed_pagenation * $passed_limit;

if ($passed_method == 'GET') {
	$achivement_query = mysql_query("SELECT * FROM `karma` WHERE `karma_user` LIKE '$authuser_key'");
	$achivement_count = mysql_num_rows($achivement_query);
	$achivement_stats = return_user_stats($authuser_key);
	while($row = mysql_fetch_array($achivement_query)) {	
		$achivment_title = $row['karma_title'];
		$achivment_description = $row['karma_description'];
		$achivment_timestamp = $row['karma_timestamp'];
		$achivment_score = intval($row['karma_score']);
		$achivment_output[] = array("timestamp" => $achivment_timestamp, "title" => $achivment_title, "description" => $achivment_description, "score" => $achivment_score);
						
	}		
	
	$json_status = $achivement_count . ' achiments returned';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $achivment_output, 'score' => $achivement_stats['score']);
	echo json_encode($json_output);
	exit;

}
else {
	$json_status = $passed_method . ' methods are not supported in the api';
	$json_output[] = array('status' => $json_status, 'error_code' => 405);
	echo json_encode($json_output);
	exit;
	
}

?>