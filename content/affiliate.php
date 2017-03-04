<?

include '../lib/auth.php';
include '../lib/analytics.php';

header('Content-Type: application/json');

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_key = $_GET['key'];

if ($passed_method == 'GET') {
	if (empty($passed_key)) {
		$json_status = 'key parameter missing';
		$json_output[] = array('status' => $json_status, 'error_code' => '301');
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$stream_query = mysql_query("SELECT * FROM `content` WHERE `content_key` LIKE '%$passed_key%' AND `content_hidden` = 0 LIMIT 0 ,1");		
		$stream_count = mysql_num_rows($stream_query);
		if ($stream_count == 1) {
			$stream_data = mysql_fetch_assoc($stream_query);
			$stream_title = $stream_data['content_title'];
			$stream_description = $stream_data['content_description'];
			$stream_content = $stream_title . " " . $stream_description;
			$stream_tags = explode(",", $stream_data['content_tags']);											
			$stream_affiliate = array();
			
			preg_match_all('/[A-Z]([a-z]+|\.)(?:\s+[A-Z]([a-z]+|\.))*(?:\s+[a-z][a-z\-]+){0,2}\s+[A-Z]([a-z]+|\.)/', $stream_content, $stream_keywords);
					
			foreach ($stream_tags as $tag) {
				if (strpos($tag, 'film') !== false || strpos($tag, 'movie') !== false || strpos($tag, 'trailer') !== false || strpos($tag, 'empire') !== false) {
					$stream_affiliate = search_itunes(reset($stream_keywords), "movie");
					break;
					
				}
				else if (strpos($tag, 'band') !== false || strpos($tag, 'music') !== false || strpos($tag, 'album') !== false || strpos($tag, 'concert') !== false || strpos($tag, 'singer') !== false) {
					$stream_affiliate = search_itunes(reset($stream_keywords), "music");
					break;
					
				}
				else if (strpos($tag, 'tv') !== false || strpos($tag, 'show') !== false || strpos($tag, 'series') !== false || strpos($tag, 'television') !== false) {
					$stream_affiliate = search_itunes(reset($stream_keywords), "tv");
					break;
					
				}
				else if (strpos($tag, 'gaming') !== false || strpos($tag, 'xbox') !== false || strpos($tag, 'playstation') !== false || strpos($tag, 'tevelision') !== false) {
					//$stream_affiliate = search_itunes(reset($stream_keywords));
					//amazon api
					break;
					
				}
				
				
			}
			
			if (count($stream_affiliate) == 0) {
				$json_status = 'no results found';
				$json_output[] = array('status' => $json_status, 'error_code' => '312');
				echo json_encode($json_output);
				exit;
				
			}
			else {
				$json_status = 'found item';
				$json_output[] = array('status' => $json_status, 'error_code' => '200', 'content' => $stream_affiliate);
				echo json_encode($json_output);
				exit;
				
			}
			
		}
		else {
			$json_status = 'content does not exist';
			$json_output[] = array('status' => $json_status, 'error_code' => '312');
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

function search_itunes($items, $type) {
	$item_duplicate = sort(array_count_values($items));
	$item_duplicate = reset($item_duplicates);
	
	$itunes_query = "https://itunes.apple.com/search?term=" . str_replace(" ", "%20", $item_duplicates) . "&media=" . $type . "&limit=1";
	$itunes_contents = json_decode(file_get_contents($itunes_query));
	$itunes_results = $itunes_contents->results;		
	if (count($itunes_results) > 0) {
		$itunes_kind = $itunes_contents->results[0]->kind;
		$itunes_title = $itunes_contents->results[0]->trackName;
		$itunes_key = $itunes_contents->results[0]->trackId;
		$itunes_artwork = $itunes_contents->results[0]->artworkUrl60;		
		$itunes_url = "http://itunes.com/" . $itunes_key;
		if ($itunes_kind == "feature-movie") $itunes_subtitle = $itunes_contents->results[0]->primaryGenreName;
		else $itunes_subtitle = $itunes_contents->results[0]->artistName;
		
		$itunes_title = $itunes_contents->results[0]->trackName;
		$itunes_output = array("type" => "itunes", "keyword" => $tag, "title" => $itunes_title, "subtitle" => $itunes_subtitle, "image" => $itunes_artwork, "key" => $itunes_key, "action" => "download", "url" => $itunes_url);
		break;
		
	}
	else {		
		foreach ($items as $tag) {
			$itunes_query = "https://itunes.apple.com/search?term=" . str_replace(" ", "%20", $tag) . "&media=" . $type . "&limit=1";
			$itunes_contents = json_decode(file_get_contents($itunes_query));
			$itunes_results = $itunes_contents->results;
			if (count($itunes_results) > 0) {
				$itunes_kind = $itunes_contents->results[0]->kind;
				$itunes_title = $itunes_contents->results[0]->trackName;
				$itunes_key = $itunes_contents->results[0]->trackId;
				$itunes_artwork = $itunes_contents->results[0]->artworkUrl60;		
				$itunes_url = "http://itunes.com/" . $itunes_key;
				if ($itunes_kind == "feature-movie") $itunes_subtitle = $itunes_contents->results[0]->primaryGenreName;
				else $itunes_subtitle = $itunes_contents->results[0]->artistName;
				
				$itunes_title = $itunes_contents->results[0]->trackName;
				$itunes_output = array("type" => "itunes", "keyword" => $tag, "title" => $itunes_title, "subtitle" => $itunes_subtitle, "image" => $itunes_artwork, "key" => $itunes_key, "action" => "download", "url" => $itunes_url);
				break;
							
			}
			
		}
		
	}
	
	if (count($itunes_output) == 0) $itunes_output = array("tags" => $items);
	
	return $itunes_output;
	
}


?>