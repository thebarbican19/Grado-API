<?

function add_karma($points, $title, $description, $user) { 
	if (isset($user) && strlen($title) > 3) {
		$karma_query = mysqli_query("SELECT * FROM `karma` WHERE `karma_description` LIKE '$description' AND `karma_user` LIKE '$user' LIMIT 0 ,1");
		$karma_exists = mysql_num_rows($karma_query);
		if ($karma_exists == 0) {
			$karma_sql = "INSERT INTO  `karma` (`karma_id` ,`karma_timestamp` ,`karma_score` ,`karma_title` ,`karma_description` ,`karma_user`) VALUES (NULL , CURRENT_TIMESTAMP ,  '$points',  '$title', '$description',  '$user');";
			$karma_post = mysqli_query($karma_sql);
			if ($karma_post) return true;
			else return false;
					
		}
		else return false;
		
	}
	else return false;

}

?>
