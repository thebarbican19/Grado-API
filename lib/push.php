<?

function submit_device($user, $token) {
	$push_url = 'https://api.pushbots.com/deviceToken';
	$push_content = array('platform' => '0', 'token' => $token, 'alias' => $user, 'tag' => array('gradobot'), 'active' => array("comments", "reccomended", "general", "channel") ,'locale' => $authuser_language);
	$push_return = pushbots($push_url, $push_content, "PUT");
	
	return $push_return;
}

function subscribe_to_channel($user, $token, $channel) {
	$push_url = 'https://api.pushbots.com/tag';
	$push_content = array('tag' => $channel, 'platform' => '0', 'token' => $token, 'alias' => $user);
	$push_return = pushbots($push_url, $push_content, "PUT");
	
	return $push_return;
	
}

function unsubscribe_to_channel($user, $token, $channel) {
	$push_url = 'https://api.pushbots.com/tag/del';
	$push_content = array('tag' => $channel, 'platform' => '0', 'token' => $token, 'alias' => $user);
	$push_return = pushbots($push_url, $push_content, "PUT");
	
	return $push_return;
	
}

function sent_push_to_user($user, $payload) {
	$push_url = 'https://api.pushbots.com/push/all';
	$push_content = array('platform' => '0', 'token' => $token, 'alias' => $user, 'payload' => $payload);
	$push_return = 	pushbots($push_url, $push_content, "POST");
		
	return $push_return;
		
}

function sent_push_to_tag($tag, $payload, $title) {
	$push_url = 'https://api.pushbots.com/push/all';
	$push_content = array('platform' => array('0', '1'), 'token' => $token, 'tags' => $tag, 'badge' => '+1', 'msg' => $title, 'payload' => $payload);
	$push_return = 	pushbots($push_url, $push_content, "POST");
		
	return $push_return;
	
}

function pushbots($api, $data, $method) {
	$curl_data = json_encode($data);
	$curl_headers = array('X-PUSHBOTS-APPID:' . '5759fcbb4a9efa12b58b4567', 'X-PUSHBOTS-SECRET:' . '5a7a2a72239efeec6763c91c5eb764ad', 'Content-Type: application/json', 'Content-Length: ' . strlen($curl_data));
	$curl_pushbots = curl_init();
	
 	curl_setopt($curl_pushbots, CURLOPT_CONNECTTIMEOUT, 0); 
 	curl_setopt($curl_pushbots, CURLOPT_CONNECTTIMEOUT, 0); 
    curl_setopt($curl_pushbots, CURLOPT_TIMEOUT, 0); 
    curl_setopt($curl_pushbots, CURLOPT_RETURNTRANSFER, TRUE); 
	curl_setopt($curl_pushbots, CURLOPT_HTTPHEADER, $curl_headers);
	curl_setopt($curl_pushbots, CURLOPT_HEADER, FALSE); 
	curl_setopt($curl_pushbots, CURLOPT_SSL_VERIFYPEER, FALSE);
	if ($method == "POST") {
		 curl_setopt($curl_pushbots, CURLOPT_POST, TRUE); 
		 curl_setopt($curl_pushbots, CURLOPT_POSTFIELDS, $curl_data); 
		 
	}
	elseif ($method == "PUT") {
		curl_setopt($curl_pushbots, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($curl_pushbots, CURLOPT_POSTFIELDS, $curl_data);
		
	}
	curl_setopt($curl_pushbots, CURLINFO_HEADER_OUT, TRUE ); 
	curl_setopt($curl_pushbots, CURLOPT_URL, $api); 
	
	$curl_output = curl_exec($curl_pushbots);
	$curl_response = curl_getinfo($curl_pushbots); 
	curl_close($curl_pushbots);
		
	return array("message" =>  $curl_response['msg'] ,"data" => $data, 'headers' => $curl_headers, 'output' => $curl_output);
	
	
}
