<?

include 'aws.php';

function upload_image($file, $user, $type) {
	$client_key = "AKIAJWU2PWK2VPSK526Q";
	$client_secret = "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";
	$client_bucket = "grado-assets";
	$client = new S3(array("key" => $client_key, "secret" => $client_secret, "region" => "eu-central-1", "s3_signature_version" => "v4"));
	$client_contents = $client->listBuckets();
	
	if (empty($file)) {
		$json_status = 'type or key parameter missing';
		$json_output = array('status' => $json_status, 'error_code' => '301');
		
		return $json_output;
		
	}
	else if (empty($user)) {
		$json_status = 'user parameter missing';
		$json_output = array('status' => $json_status, 'error_code' => '301');
		
		return $json_output;
	
	}
	else {
		$file_name = $file['name'];
		$file_temp = $file['tmp_name'];
		$file_extension = explode(".", $file_name);
		$file_extension = strtolower(end($file_extension));
		if ($type == "avatar") $file_gen = "img_" .substr($user, 5, 14) . md5(uniqid()) . "." . $file_extension;
		$file_directory = "http://gradoapp.com/assets/avatar?i=" . str_replace("img_", "", $file_gen);
		
		if ($client->putObjectFile($file_temp, $client_bucket, $file_gen, S3::ACL_PUBLIC_READ)) {
			$json_status = 'file uploaded';
			$json_output = array('status' => $json_status, 'error_code' => '200', 'file' => $file_directory);

			return $json_output;
		
		}
		else {
			$json_status = $file_name . ' file not uploaded';
			$json_output = array('status' => $json_status, 'error_code' => '300');

			return $json_output;
		
		}
		
	}
	
}

function upload_resize($file ,$new_width, $new_height) {	
    $file_data = getimagesize($file['tmp_name']);
	   
	if($file_data['mime']=='image/png') $src_img = imagecreatefrompng($path); 
    if($file_data['mime']=='image/jpg') $src_img = imagecreatefromjpeg($path);
    if($file_data['mime']=='image/jpeg') $src_img = imagecreatefromjpeg($path);
    if($file_data['mime']=='image/pjpeg') $src_img = imagecreatefromjpeg($path);

    $old_x = imageSX($src_img);
    $old_y = imageSY($src_img);
	
    if ($old_x > $old_y) {
        $thumb_w    =   $new_width;
        $thumb_h    =   $old_y / $old_x * $new_width;
    }

    if ($old_x < $old_y) {
        $thumb_w    =   $old_x / $old_y * $new_height;
        $thumb_h    =   $new_height;
    }

    if ($old_x == $old_y) {
        $thumb_w = $new_width;
        $thumb_h = $new_height;
    }

    $dst_img        =   ImageCreateTrueColor($thumb_w,$thumb_h);

    imagecopyresampled($dst_img, $src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);


    // New save location
    $new_thumb_loc = $moveToDir . $imageName;

    if($file_data['mime']=='image/png') $result = imagepng($dst_img,$new_thumb_loc,8);
    if($file_data['mime']=='image/jpg') $result = imagejpeg($dst_img,$new_thumb_loc,80);
    if($file_data['mime']=='image/jpeg') $result = imagejpeg($dst_img,$new_thumb_loc,80);
    if($file_data['mime']=='image/pjpeg') $result = imagejpeg($dst_img,$new_thumb_loc,80); 

    imagedestroy($dst_img);
    imagedestroy($src_img);
    return $result;
}

?>