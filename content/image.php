<?

//include '../lib/auth.php';
include '../lib/keygen.php';

$source_size = getimagesize($_FILES['image']['tmp_name']);
$source_width = $source_size[0];
$source_height = $source_size[1];

$new_size = ($source_width + $source_height) / ($source_width * ($source_height / 100));
$new_width = $source_width * $new_size;
$new_height = $source_height * $new_size;

$new_image = imagecreatetruecolor($new_width, $new_height);
$soruce_image = imagecreatefromjpeg($_FILES['image']['tmp_name']);

imagecopyresampled($new_image, $soruce_image, 0, 0, 0, 0, $new_width ,$new_height, $source_width, $source_height);

ob_start();
imageJPEG($new_image, null, 100); 
return ob_get_contents();
ob_end_clean();

//$passed_image = resizeimage($_GET['i']);
//echo "image: " . $passed_image;$sBinaryThumbnail = addslashes($sBinaryThumbnail);
	
//$passed_image = addslashes($new_image);
$passed_imagesize = getimagesize($_FILES['image']['tmp_name']);
$passed_imageextension = pathinfo(basename($_FILES['image']), PATHINFO_EXTENSION);
$passed_type = $_GET['type'];

$database_connect = mysql_connect('109.169.18.127', 'gradoadmin', 'Getoutmygrado**19'); 
$database_table = mysql_select_db("gradodb");

if (empty($passed_type)) {
	$json_status = 'type parameter missing';
	$json_output[] = array('status' => $json_status, 'error_code' => '301');
	echo json_encode($json_output);
	exit;
	
}
else if (!in_array($passed_type, array("content", "avatar"))) {
	$json_status = 'type parameter is not valid';
	$json_output[] = array('status' => $json_status, 'error_code' => '301');
	echo json_encode($json_output);
	exit;
	
}	
else if (empty($_FILES['image'])) {
	$json_status = 'image parameter empty';
	$json_output[] = array('status' => $json_status, 'error_code' => '301');
	echo json_encode($json_output);
	exit;
	
}
else if ($passed_imagesize == false) {
	$json_status = 'image type is not allowed';
	$json_output[] = array('status' => $json_status, 'error_code' => '301');
	echo json_encode($json_output);
	exit;
	
}
else {
	$exists_query = mysql_query("SELECT * FROM `store` WHERE `store_owner` LIKE '%user%' AND `store_type` LIKE 'avatar'");
	$exists_count = mysql_num_rows($exists_query);
	$exists_data = mysql_fetch_assoc($exists_query);
	if ($exists_count == 0) {
		$image_key = "img_" . generate_key() . "";
		$image_output = "http://gradoapp.com/api/assets/avatar.php?i=" . str_replace("img_", "", $image_key);
		$image_upload = mysql_query("INSERT INTO `store` (`store_id`, `store_timestamp`, `store_key`, `store_owner`, `store_type`, `store_data`) VALUES (NULL, CURRENT_TIMESTAMP, '$image_key', '$authuser_key', '$passed_type' , '$passed_image');");
		
		$json_status = "uploaded " . $passed_type . " image";
				
	}
	else {
		$image_key = $exists_data['store_key'];
		$image_output = "http://gradoapp.com/api/assets/avatar.php?i=" . str_replace("img_", "", $image_key);	
		$image_upload = mysql_query("UPDATE `store` SET `store_data` = '$passed_image' WHERE `store_key` LIKE '$image_key';");
		
		$json_status = "updated " . $passed_type . " image";
		
	}
	
	if ($passed_type == "avatar") $user_update = mysql_query("UPDATE `users` SET `user_avatar` = '$image_output' WHERE `user_key` LIKE '$authuser_key';");
	
	if ($image_upload) {
		$json_output[] = array('status' => $json_status, 'error_code' => '200', 'url' => $image_output);
		echo json_encode($json_output);
		exit;
		
	}
	else {
		$json_status = 'image not uploaded - ' . mysql_error();
		$json_output[] = array('status' => $json_status, 'error_code' => '310');
		echo json_encode($json_output);
		exit;
		
	}
	
}
	

?>