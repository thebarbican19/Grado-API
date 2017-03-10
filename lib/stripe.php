<?

function stripe_connect($url, $data, $method) {
	$ch_url = "https://api.stripe.com/v1/" . $url;
 	$ch_headers[] = "Authorization: Bearer sk_test_jiqLZZMgMx5MyBJtwwLjxxRI";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, $ch_headers);	
	curl_setopt($ch, CURLOPT_URL, $ch_url);
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

function stripe_create_customer($email) {
	$stripe_data = array();
	$stripe_data['email'] = $email;
	
	stripe_connect("customers", $stripe_data, "POST");
	
}

function stripe_retrive_plan($customer) {
	
}

function stripe_retrive_customer($customer) {
	
}

?>