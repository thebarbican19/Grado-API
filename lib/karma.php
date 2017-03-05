<?

function add_karma($points, $title, $description, $user) { 
	global $database_grado_connect;
	
	if (isset($user) && strlen($title) > 3) {
		$karma_query = mysqli_query($database_grado_connect, "SELECT * FROM `karma` WHERE `karma_description` LIKE '$description' AND `karma_user` LIKE '$user' LIMIT 0 ,1");
		$karma_exists = mysqli_num_rows($karma_query);
		if ($karma_exists == 0) {
			$karma_sql = "INSERT INTO  `karma` (`karma_id` ,`karma_timestamp` ,`karma_score` ,`karma_title` ,`karma_description` ,`karma_user`) VALUES (NULL , CURRENT_TIMESTAMP ,  '$points',  '$title', '$description',  '$user');";
			$karma_post = mysqli_query($database_grado_connect, $karma_sql);
			if ($karma_post) return true;
			else return false;
					
		}
		else return false;
		
	}
	else return false;

}

?>
