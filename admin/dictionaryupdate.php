<?

include '../lib/auth.php';
include '../lib/keywords.php';

$passed_method = $_SERVER['REQUEST_METHOD'];
$passed_data = json_decode(file_get_contents('php://input'), true);
$passed_tags = explode(",", $passed_data['tags']);
$passed_catagory = $passed_data['catagory'];
$passed_subcatagory = $passed_data['subcatagory'];

/*
$city_query = mysqli_query("SELECT * FROM `cities` WHERE `city_population` > 50000 AND `city_countrycode` NOT LIKE 'gb' AND `city_countrycode` NOT LIKE 'us'");
while($row = mysql_fetch_array($city_query)) {	
	$city = preg_replace('/[^A-Za-z-]+/', '', $row['city_name']);		
	$city = strtolower($city);
	
	$country = preg_replace('/[^A-Za-z]+/', '', $row['city_country']);		
	$country = strtolower($country);
	
	$lat = (float)$row['city_latitude'];
	$lng = (float)$row['city_longitude'];
				
	if (strlen($city) > 2) {
		$existing_bool = 1;
		$existing_query = mysqli_query("SELECT * FROM `dictionary` WHERE `tag_word` LIKE '$city' AND `tag_type` LIKE '$country'");
		$existing_bool = mysql_num_rows($existing_query);
		if ($existing_bool == 0) {
			$add_tag = mysqli_query("INSERT INTO `dictionary` (`tag_id`, `tag_word`, `tag_type`, `tag_subtype`, `tag_affiliate`, `tag_search`, `tag_auto`, `tag_latitude`, `tag_longitude`) VALUES (NULL, '$city', '$country', '', '', '1', '0', '$lat', '$lng');");
			if ($add_tag) $added_tags[] = $city . " - " . $country;
			
		}
		
	}
	
	
}
*/

foreach ($passed_tags as $tag) {
	$app_key = $_SERVER["HTTP_GDAPPKEY"];
		
	$tag = preg_replace('/[^A-Za-z-]+/', '', $tag);		
	$tag = strtolower($tag);
	if (strlen($tag) > 2) {
		$existing_bool = 1;
		$existing_query = mysqli_query("SELECT * FROM `dictionary` WHERE `tag_word` LIKE '$tag' AND `tag_type` LIKE '$passed_catagory'");
		$existing_bool = mysql_num_rows($existing_query);
		$existing_data = mysql_fetch_assoc($existing_query);
		$existing_subtype = $existing_data['tag_subtype'];
		$existing_cansearch = (int)$existing_data['tag_search'];		
		if ($existing_bool == 0) {
			$add_tag = mysqli_query("INSERT INTO `dictionary` (`tag_id`, `tag_word`, `tag_type`, `tag_subtype`, `tag_affiliate`, `tag_search`, `tag_auto`, `tag_latitude`, `tag_longitude`, `tag_submitted`) VALUES (NULL, '$tag', '$passed_catagory', '$passed_subcatagory', '', '1', '0', '', '', '$app_key');");
			if ($add_tag) $added_tags[] = $tag;
			
		}
		else if (strlen($passed_subcatagory) > 1 && empty($existing_subtype) && $existing_cansearch == 1) {
			$update_tag = mysqli_query("UPDATE `dictionary` SET `tag_subtype` = '$passed_subcatagory' WHERE `tag_word` LIKE '$tag';");
			if ($update_tag) $updated_tags[] = $tag;
			
		}
		
	}
	
}

if (count($updated_tags) == 0) $updated_tags = array();

/*
$location_search = mysqli_query("SELECT * FROM `dictionary` WHERE `tag_type` LIKE 'automobile'");
while($row = mysql_fetch_array($location_search)) {	
	$location_city = $row['tag_word'];
	$location_country =  $row['tag_type'];
	$location_update = mysqli_query("UPDATE `dictionary` SET `tag_type` = 'transportation', `tag_subtype` = 'automobile' WHERE `tag_word` LIKE '$location_city';");
	if ($location_update) $updated_tags[] = $location_country . " - " . $location_city;
	
}
/*
	
/*
$city_query = mysqli_query("SELECT * FROM `cities` WHERE `city_country` LIKE '%Great Britain%' LIMIT 0, 1000");
while($row = mysql_fetch_array($city_query)) {	
	$city_name = $row['city_name'];
	$city_update = mysqli_query("UPDATE `cities` SET `city_country` = 'United Kingdom' WHERE `city_name` LIKE '$city_name';");
	if ($city_update) 	$added_tags[] = $city_name;
	
}
*/


$tag_stats = tags_stats();
if (count($added_tags) > 0 || count($updated_tags) > 0) {
	$json_status = 'added ' . count($added_tags) . ' tags and updated ' . count($updated_tags);
	$json_output[] = array('status' => $json_status, 'error_code' => 200, 'added' => $added_tags, 'updated' => $updated_tags, 'stats' => $tag_stats);
	echo json_encode($json_output);
	exit;
	
}
else {
	$json_status = 'could not add tags';
	$json_output[] = array('status' => $json_status, 'error_code' => 400);
	echo json_encode($json_output);
	exit;
	
}
	

/*
if ($authuser_type == "admin") {	
	if (count($passed_tags) == 0) {
		$json_status = 'no tags added';
		$json_output[] = array('status' => $json_status, 'error_code' => 200);
		echo json_encode($json_output);
		exit;
		
	}
	elseif (empty($passed_catagory)) {
		$json_status = 'missing catagory parameter';
		$json_output[] = array('status' => $json_status, 'error_code' => 422);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		foreach ($passed_tags as $tag) {
			$tag = preg_replace('/[^A-Za-z]+/', '', $tag);		
			$tag = strtolower($tag);
			if (strlen($tag) > 2) {
				$existing_bool = 1;
				$existing_query = mysqli_query("SELECT * FROM `dictionary` WHERE `tag_word` LIKE '$tag' AND `tag_type` LIKE '$passed_catagory'");
				$existing_bool = mysql_num_rows($existing_query);
				if ($existing_bool == 0) {
					$add_tag = mysqli_query("INSERT INTO `dictionary` (`tag_id`, `tag_word`, `tag_type`, `tag_subtype`, `tag_affiliate`, `tag_search`, `tag_auto`, `tag_latitude`, `tag_longitude`) VALUES (NULL, '$tag', '$passed_catagory', '$passed_subcatagory', '', '1', '0', '', '');");
					if ($add_tag) $added_tags[] = $tag;
					
				}
				
			}
			
		}
		
		if (count($added_tags) > 0) {
			$json_status = 'added ' . count($added_tags) . ' tags to dictioanry';
			$json_output[] = array('status' => $json_status, 'error_code' => 200, 'tags' => $added_tags);
			echo json_encode($json_output);
			exit;
			
		}
		else {
			$json_status = 'could not add tags';
			$json_output[] = array('status' => $json_status, 'error_code' => 400);
			echo json_encode($json_output);
			exit;
			
		}
		
	}

}
else {
	$json_status = 'user does not have the privileges to access this api';
	$json_output[] = array('status' => $json_status, 'error_code' => 349);
	echo json_encode($json_output);
	exit;
	
}
*/

?>