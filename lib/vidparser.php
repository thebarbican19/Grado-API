<?

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_type = $_GET['type'];
$passed_id = $_GET['id'];

if ($passed_method == 'GET') {
	if (empty($passed_type)) {
		$json_status = 'type parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	elseif (empty($passed_id)) {
		$json_status = 'identifyer parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		if ($passed_type == "youtube") {
			parse_str(file_get_contents("http://youtube.com/get_video_info?video_id=" . $passed_id) ,$video_info); 
			$video_streams = explode(",", $video_info['url_encoded_fmt_stream_map']); 
			
			if (count($video_streams) > 1) {
				parse_str(reset($video_streams), $video_high); 
				parse_str(end($video_streams), $video_low); 
					
				if (isset($video_high['url'])) $video_output[] = array('stream' => $video_high['url'], 'quality' => $video_high['quality']);
				if (isset($video_low['url'])) $video_output[] = array('stream' => $video_low['url'], 'quality' => $video_low['quality']);				
							  
			}
			else $video_output = array();
						
			$json_status = 'returning ' . $passed_type . ' url';
			$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $video_output);
			echo json_encode($json_output);
			exit;
			
		}
		else if ($passed_type == "vine") {
			$video_query = mysql_query("SELECT * FROM  `content` WHERE `content_site` LIKE  'vine.co' AND `content_appkey` LIKE  '$passed_id' LIMIT 0, 1");
			$video_exists = mysql_num_rows($video_query);
			if ($video_exists == 1) {
				$video_data = mysql_fetch_assoc($video_query);
				$video_stream = $video_data['content_media'];
				
				$video_output[] = array('type' => $passed_type, 'format' => 'unknown', 'stream' => $video_stream, 'quality' => "high");
				
			}
						
			if (count($video_output) == 0) $video_output = array();			
			
			$json_status = 'returning ' . $passed_type . ' url';
			$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $video_output);
			echo json_encode($json_output);
			exit;
		}
		
	}
	
	$video_output = array();
	$json_status = 'returning ' . $passed_type . ' url';
	$json_output[] = array('status' => $json_status, 'error_code' => '200', 'output' => $video_output);
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