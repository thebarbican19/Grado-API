<?

function add_trending($item, $user, $latlng) { 
	if (isset($item)) {
		$trending_item = mysqli_query("SELECT * FROM `content` WHERE `content_key` LIKE '$item' AND `content_hidden` = 0 ORDER BY `content_id` LIMIT 0, 1");
		$trending_output = mysql_fetch_assoc($trending_item);
		$trending_tags = explode(",", $trending_output['content_tags']);
		$trending_latitude = reset($latlng);
		$trending_longitude = end($latlng);
				
		foreach ($trending_tags as $tag) {
			if (strlen($tag) > 1) {
				$trending_exists = mysqli_query("SELECT * FROM  `trending` WHERE  `trending_keyword` LIKE  '$tag' AND  `trending_user` LIKE  '$authuser_key' LIMIT 0, 1");
				$trending_count = mysql_num_rows($trending_exists);
				if ($trending_count == 0) {
					//$trending_post = mysqli_query("INSERT INTO  `trending` (`trending_id` ,`trending_timestamp` ,`trending_keyword` ,`trending_user` ,`trending_latitude` ,`trending_longitude`) VALUES (NULL , CURRENT_TIMESTAMP ,  '$tag',  '$user',  '$trending_latitude',  '$trending_longitude');");
					
				}
					
			}
			
		}
						
	}
	
}

?>