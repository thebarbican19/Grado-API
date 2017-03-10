<?

function email_mailgun_connect($url, $data, $method) {
	$ch_url = "https://api.mailgun.net/v3/" . $url;
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($ch, CURLOPT_URL, $ch_url);
	curl_setopt($ch, CURLOPT_USERPWD, "api:key-8f933c67697b82ef9455014e2b5c5ca0");
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	$result = curl_exec($ch);
	if(curl_errno($ch)) $result = 'Curl error: ' . curl_error($ch);
	curl_close($ch);
		
	return json_decode($result);
	
}

function email_user($email_subject, $email_body, $email_user, $email_sender, $email_force) { 
	if (empty($email_sender)) {
		$sender_email = "joe@gradoapp.com";
		$sender_name = "Joe Barbour";
		$sender_icon = "http://gradoapp.com/api/v1/assets/user_icon.png";
		
		$email_sender = 'Grado <info@gradoapp.com>';
		
	}
	/*
	else {
		$sender_query = mysqli_query("SELECT * FROM  `users` WHERE  `user_status` LIKE  'active' AND `user_key` LIKE '$email_sender' LIMIT 0, 1");
		$sender_data = mysql_fetch_assoc($sender_query);
		$sender_email = $sender_data['user_email'];
		$sender_name = $sender_data['user_actualname'];
		$sender_icon = $sender_data['user_avatar'];
					
		if (empty($sender_icon)) $sender_icon = "http://gradoapp.com/api/v1/assets/user_icon.png";
		
		$email_sender = reset(explode(" ", $sender_name)) . ' from Grado <' . $sender_email . '>';
	}
	*/
	
	if (!empty($email_user)) {
		$email_formatted .= "<link rel='stylesheet' href='http://gradoapp.com/style/email.css' type='text/css' charset='utf-8'/>";
		$email_formatted .= "<html>";
		$email_formatted .= "<body>";
		$email_formatted .= "<div class='header'>";
		$email_formatted .= "<a href='' target='_blank' alt='grado website'><img src='http://gradoapp.com/assets/logo_white.png' class='logo'></a>";
		$email_formatted .= "</div>";
		$email_formatted .= "<div class='container'>";
		$email_formatted .= $email_body;
		$email_formatted .= "<p><p>";
		$email_formatted .= "King regards,<p>";
		$email_formatted .= "<div class='sender'>";	
			$email_formatted .= "<div style='width:68px; float:left;'>";	
				$email_formatted .= "<img src='" . $sender_icon . "' class='social'>";
				$email_formatted .= "</div><div style='width:235px; font-size:13px; float:left;'>";	
					$email_formatted .= "<strong>" . $sender_name . "</strong>";
					$email_formatted .= "<br/><a href='mailto:" . $sender_email . "'>" . $sender_email . "</a><p>";		
				$email_formatted .= "</div>";	
			$email_formatted .= "</div>";
		$email_formatted .= "</div>";
	
		
		$email_data = array();
		$email_data['from'] = $email_sender;
		$email_data['to'] = $email_user;
		$email_data['h:Reply-To'] = 'info@gradoapp.com';
		$email_data['subject'] = $email_subject;
		$email_data['html'] = $email_formatted;
		
		$email_output = email_mailgun_connect("mg.gradoapp.com/messages", $email_data, "POST");
		
		if ($email_output->message == "Queued. Thank you.") $json_output = array("status" => $email_output->message, 'error_code' => 200);
		else $json_output = array("status" => $email_output->message, 'error_code' => 400, 'data' => $email_data);
		
		return $json_output;
		
	}
	else return array("status" => $email_output->message, 'error_code' => 403);
	
}

function email_new_mailinglist($channel_key, $channel_name) {
	$email_data = array();
	$email_data['address'] = $channel_name . "<" . $channel_name . "@mg.gradoapp.com>";
	$email_data['name'] = $channel_name;
	$email_data['description'] = "Auto Mailing list for " . $channel_name . " (" . $channel_key . ")";
	
 	return email_mailgun_connect("lists", $email_data, "POST");
	
}


function email_delete_mailinglist($channel_name) {
 	return email_mailgun_connect("lists/" . $channel_name . "@mg.gradoapp.com", array(), "DELETE");
	
}

function email_subscribe_mailinglist($channel_name, $subscriber_email, $subscriber_username) {
	$email_data = array();
	$email_data['address'] = $subscriber_email;
	$email_data['name'] = $subscriber_username;
	$email_data['subscribed'] = "yes";
	
  	return email_mailgun_connect("lists/" . $channel_name . "@mg.gradoapp.com/members", $email_data, "POST");
	
}

function email_unsubscribe_mailinglist($channel_name, $subscriber_email) {
	$email_data = array();
	$email_data['subscribed'] = "no";
	
  	return email_mailgun_connect("lists/" . $channel_name . "@mg.gradoapp.com/members/" . $subscriber_email, $email_data, "PUT");
	
}

?>