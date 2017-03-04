<?

include '../lib/auth.php';
include '../lib/keygen.php';

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_source = $passed_data['source'];
$passed_endpoint = $passed_data['endpoint'];
$passed_title = $passed_data['title'];
$passed_type = $passed_data['type'];
$passed_latitude = (float)$passed_data['latitude'];
$passed_longitude = (float)$passed_data['longitude'];
$passed_radius = (float)$passed_data['radius'];

if (isset($passed_latitude) && isset($passed_longitude) && empty($passed_radius)) $passed_radius = 1000.00;

if ($authuser_type == "admin") {	
	if ($passed_method == 'GET') {
		$source_query = mysqli_query("SELECT * FROM sources ORDER BY source_title ASC LIMIT 0 ,500");
		$source_count = mysql_num_rows($source_query);
		$source_output = array();
		while($row = mysql_fetch_array($source_query)) {
			$source_updated = $row['source_updated'];
			$source_added = $row['source_added'];
			$source_key = $row['source_key'];
			$source_title = $row['source_title'];
			$source_type = $row['source_type'];
				
			$source_output[] = array("updated" => $source_updated, "added" => $source_added, "title" => $source_title, "type" => $source_type, "key" => $source_key);
			
		}
		
		$json_status = 'returned ' . $source_count . ' sources';
		$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $source_output);
		echo json_encode($json_output);
		exit;
		
	}
	elseif ($passed_method == 'POST') {
		if (empty($passed_type)) {
			$json_status = 'type parameter missing';
			$json_output[] = array('status' => $json_status, 'error_code' => 301);
			echo json_encode($json_output);
			exit;
			
		}
		elseif (empty($passed_title)) {
			$json_status = 'title parameter missing';
			$json_output[] = array('status' => $json_status, 'error_code' => 301);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			if ($passed_type == "rss") {
				if (empty($passed_source)) {
					$json_status = 'source parameter missing';
					$json_output[] = array('status' => $json_status, 'error_code' => 301);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$source_feed = file_get_contents($passed_source);
					$source_feed = simplexml_load_string($source_feed);
					$source_output = array();
					foreach ($source_feed->channel->item as $item) {	
						$source_link = (string)$item->link;
						$source_title = (string)$item->title;
						$source_output[] = array("title" => $source_title, "url" => $source_link);
							
					}
					
					$source_data = $source_output[0];
					if (empty($source_data['title']) && empty($source_data['url'])) {
						$json_status = 'feed is broken';
						$json_output[] = array('status' => $json_status, 'error_code' => 301, 'data' => $source_data);
						echo json_encode($json_output);
						exit;
						
					}
					
				}
				
			}
			elseif ($passed_type == "instagram") {
				if (empty($passed_endpoint)) {
					$json_status = 'endpoint parameter missing';
					$json_output[] = array('status' => $json_status, 'error_code' => 422);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					//$instagram_url = "https://api.instagram.com/v1/users/" . . "/?access_token=ACCESS-TOKEN";
								
				}
				
			}	
			elseif ($passed_type == "youtube") {
				if (empty($passed_endpoint)) {
					$json_status = 'endpoint parameter missing';
					$json_output[] = array('status' => $json_status, 'error_code' => 422);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$playlist_url = "https://www.googleapis.com/youtube/v3/playlistItems?key=AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg&part=snippet&maxResults=1&playlistId=" . $passed_endpoint;
					$playlist_data = file_get_contents($playlist_url);
					$playlist_data = json_decode($playlist_data);
					$playlist_video = $playlist_data->items[0]->snippet->resourceId->videoId;
					if (empty($playlist_video)) {
						$json_status = 'youtube channel endpoint is invalid';
						$json_output[] = array('status' => $json_status, 'error_code' => 301);
						echo json_encode($json_output);
						exit;
						
					}
					
				}
				
			}
			
			$exists_query = mysqli_query("SELECT * FROM sources WHERE source_url LIKE '$passed_source' AND `source_endpoint` LIKE '$passed_endpoint' LIMIT 0 ,1");
			$exists_bool = mysql_num_rows($exists_query);
			if ($exists_bool == 0) {
				$source_key = "src_" . generate_key();
				$source_add = mysqli_query("INSERT INTO `sources` (`source_id` ,`source_key` ,`source_added` ,`source_updated` ,`source_title` ,`source_url`, `source_endpoint` ,`source_type` ,`source_latitude` ,`source_longitude` ,`source_radius`) VALUES (NULL ,  '$source_key', CURRENT_TIMESTAMP ,  '0000-00-00 00:00:00',  '$passed_title',  '$passed_source', '$passed_endpoint',  '$passed_type', '$passed_latitude', '$passed_longitude', '$passed_radius');");
				$source_output = array("key" => $source_key, "name" => $passed_title);
				if ($source_add) {
					$json_status = 'source added';
					$json_output[] = array('status' => $json_status, 'error_code' => 200, 'output' => $source_output);
					echo json_encode($json_output);
					exit;
					
				}
				else {
					$json_status = 'source not added - unknown error';
					$json_output[] = array('status' => $json_status, 'error_code' => 301);
					echo json_encode($json_output);
					exit;
					
				}
				
			}
			else {
				$json_status = 'source already exists';
				$json_output[] = array('status' => $json_status, 'error_code' => 400);
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
	
}
else {
	$json_status = 'user does not have the privileges to access this api';
	$json_output[] = array('status' => $json_status, 'error_code' => 401);
	echo json_encode($json_output);
	exit;
	
}

?>