<?

function add_viewcount($item, $user) { 
	global $database_grado_connect;
	if (empty($item)) {
		$analytics_status = 'item parameter missing';
		$analytics_output[] = array('status' => $analytics_status, 'error_code' => '301');
		return $analytics_output;
		
	}
	elseif (empty($user)) {
		$analytics_status = 'user parameter missing';
		$analytics_output[] = array('status' => $analytics_status, 'error_code' => '301');
		return $analytics_output;
		
	}
	else {
		$analytics_exists_query = mysqli_query($database_grado_connect, "SELECT * FROM `views` WHERE `view_user` LIKE '$user' AND `view_content` LIKE '$item' LIMIT 0, 1");
		$analytics_exists_count = mysqli_num_rows($analytics_exists_query);
		if ($analytics_exists_count == 1) {
			$analytics_exists_data = mysql_fetch_assoc($analytics_exists_query);
			$analytics_exists_views = (int)$analytics_exists_data['view_count'] + 1;
			$analytics_exists_id = $analytics_exists_data['view_id'];
			
			$analytics_post_output = "updated";
			$analytics_post = mysqli_query("UPDATE `views` SET `view_timestamp` = CURRENT_TIMESTAMP, `view_count` = '$analytics_exists_views' WHERE `view_id` = $analytics_exists_id;");
					
		}
		else {
			$analytics_post_output = "added";		
			$analytics_post = mysqli_query("INSERT INTO `views` (`view_id`, `view_timestamp`, `view_user`, `view_content`, `view_count`) VALUES (NULL, CURRENT_TIMESTAMP, '$user', '$item', '1');");
						
		}
		
		if ($analytics_post) {
			$analytics_status = 'view ' . $analytics_post_output . ' sucsessfully';
			$analytics_output[] = array('status' => $analytics_status, 'error_code' => '200' ,'output' => array('user' => $user, 'item' => $item));
			return $analytics_output;
			
		}
		else {
			$analytics_status = 'view could not be ' . $analytics_post_output . ' - unknown error';
			$analytics_output[] = array('status' => $analytics_status, 'error_code' => '310');
			return $analytics_output;
					
		}
		
	}
	
}

function return_stats($item) {
	global $database_grado_connect;	
	if (empty($item)) {
		$analytics_status = 'item parameter missing';
		return array('status' => $analytics_status, 'error_code' => '301');;
		
	}
	else {
		$itm_query = mysqli_query($database_grado_connect, "SELECT * FROM `content` WHERE `content_key` LIKE '$item' LIMIT 0, 1");
		$itm_data = mysqli_fetch_assoc($itm_query);	
		$itm_timestamp = strtotime($itm_data['content_timestamp']);	
		$itm_current = strtotime(date("Y-m-d H:i:s"));
		$itm_hour = round(($itm_current - $itm_timestamp) / 3600, 0);
		
		$analytics_like_query = mysqli_query("SELECT * FROM `likes` WHERE `like_content` LIKE '$item'");
		$analytics_like_count = mysqli_num_rows($analytics_like_query);
		$analytics_like_count = $itm_hour + $analytics_like_count;
		
		$analytics_comments_query = mysqli_query("SELECT * FROM `comments` WHERE  `comment_item` LIKE '$item'");
		$analytics_comments_count = mysqli_num_rows($analytics_comments_query);	
	
		$analytics_view_query = mysqli_query("SELECT * FROM `views` WHERE `view_content` LIKE '$item'");
		$analytics_view_count = mysqli_num_rows($analytics_view_query);	
		$analytics_view_total = 0;
		while($row = mysql_fetch_array($analytics_view_query)) {	
			$analytics_view_total =+ (int)$row['view_count'];
		}	
						
		return array("likes_count" => $analytics_like_count, "comments_count" => $analytics_comments_count, "view_count_unique" => $analytics_view_count, "view_count_total" => $analytics_view_total);
		
	}
}

function return_user_stats($user) {
	global $database_grado_connect;
	if (empty($user)) {
		$analytics_status = 'user parameter missing';
		return array('status' => $analytics_status, 'error_code' => '301');;
		
	}
	else {
		$analytics_comment_query = mysqli_query($database_grado_connect, "SELECT COUNT(*) AS count FROM `comments` WHERE `comment_user` LIKE  '$user' AND `comment_deleted` = 0");
		$analytics_comment_count = mysqli_fetch_assoc($analytics_comment_query)['count'];
		
		$analytics_likes_query = mysqli_query($database_grado_connect, "SELECT COUNT(*) FROM `likes` WHERE `like_user` LIKE  '$user'");
		$analytics_likes_count = mysqli_fetch_assoc($analytics_likes_query)['count'];
				
		$analytics_posts_query = mysqli_query($database_grado_connect, "SELECT COUNT(*) FROM `content` WHERE `content_owner` LIKE '$user' AND `content_hidden` = 0");
		$analytics_posts_count = mysqli_fetch_assoc($analytics_posts_query)['count'];
					
		$analytics_karma_query = mysqli_query($database_grado_connect, "SELECT SUM(`karma_score`) FROM  `karma` WHERE  `karma_user` LIKE  '$user'");
		$analytics_karma_count = mysqli_fetch_assoc($analytics_karma_query)['count'];
		
		return array("comments" => (int)$analytics_comment_count, "likes" => (int)$analytics_likes_count, "uploads" => (int)$analytics_posts_count, "score" => (int)$analytics_karma_count);
		
	}
		
}

function return_user_tags($user) {
	global $database_grado_connect;	
	$tags_query = mysqli_query($database_grado_connect, "SELECT like_timestamp, GROUP_CONCAT(content_tags SEPARATOR ',') AS like_tags FROM likes LEFT JOIN content ON likes.like_content LIKE content.content_key WHERE likes.like_user LIKE  '$user'ORDER BY like_timestamp DESC LIMIT 500");
	$tags_data = mysqli_fetch_assoc($tags_query);
	$tags_all = explode(",", $tags_data['like_tags']);
	$tags_output = array();
	foreach(array_count_values($tags_all) as $key => $value) {
    	if ($value > 1 && strlen($key) > 1) $tags_output[] = $key;
		
	}
	
	return $tags_output;	
	
}	

function return_user_channels($user) {
	global $database_grado_connect;	
	$subscription_query = mysqli_query($database_grado_connect, "SELECT `subscription_channel` FROM `subscriptions` WHERE `subscription_user` LIKE '$user'");
	$subscription_count = mysqli_num_rows($subscription_query);	
	while($row = mysqli_fetch_array($subscription_query)) {
		$subscription_channels[] = $row['subscription_channel'];
		
	}
	
	if ($subscription_count == 0) $subscription_channels = array();
	
	return $subscription_channels;
	
}

?>