<?

include '../lib/auth.php';

header('Content-Type: application/json');

$passed_channelname = $_GET['username'];
$passed_channelid = $_GET['identifyer'];
$passed_playlist = $_GET['playlist'];

if (empty($passed_channelname) && empty($passed_channelid)) {
	$json_status = 'username or identifyer is required';
	$json_output[] = array('status' => $json_status, 'error_code' => 422);
	echo json_encode($json_output);
	exit;
	
}
else {
	if (empty($passed_playlist)) {
		if (isset($passed_channelname)) $channel_url = "https://www.googleapis.com/youtube/v3/channels?key=AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg&part=contentDetails&forUsername=" . $passed_channelname;
		else $channel_url = "https://www.googleapis.com/youtube/v3/channels?key=AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg&part=contentDetails&id=" . $passed_channelid;
		$channel_data = file_get_contents($channel_url);
		$channel_data = json_decode($channel_data);
		$channel_playlist = $channel_data->items[0]->contentDetails->relatedPlaylists->uploads;
		$channel_id = $channel_data->items[0]->id;
		
		if (isset($channel_id)) {
			$playlist_url = "https://www.googleapis.com/youtube/v3/playlists?key=AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg&part=snippet&maxResults=50&channelId=" . $channel_id;
			$playlist_data = file_get_contents($playlist_url);
			$playlist_data = json_decode($playlist_data);
			$playlist_output[] = array("title" => "Uploads", "key" => $channel_playlist);
			foreach ($playlist_data->items as $value) {
				$playlist_output[] = array("title" => $value->snippet->title, "key" => $value->id);
				
			}
			
			$json_status = count($playlist_output) . ' playlists retured';
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'playlists' => $playlist_output);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'channel id could not be returned';
			$json_output[] = array('status' => $json_status, 'error_code' => 301);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	else {
		$playlist_url = "https://www.googleapis.com/youtube/v3/playlistItems?key=AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg&part=snippet&maxResults=1&playlistId=" . $passed_playlist;
		$playlist_data = file_get_contents($playlist_url);
		$playlist_data = json_decode($playlist_data);
		$playlist_video = $playlist_data->items[0]->snippet->resourceId->videoId;
		$playlist_date = strtotime(reset(explode("T", $playlist_data->items[0]->snippet->publishedAt)));
		if (isset($playlist_video)) {
			$json_status = 'video found';
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'video' => $playlist_data->items[0]);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'playlist contains no videos';
			$json_output[] = array('status' => $json_status, 'error_code' => 301);
			echo json_encode($json_output);
			exit;
			
		}
		
	}
	
}

?>