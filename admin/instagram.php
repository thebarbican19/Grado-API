<?

$instagram_parameters = array('grant_type' => 'authorization_code', 'client_id' => 'ae87a670ec094e5fb332e58c25a3d081', 'client_secret' => 'f65dfea7a32a428b8815dc97532fa2b9', 'redirect_uri' => 'http://gradoapp.com/api/test/admin/instagram.php', 'code' => $_GET['code']);

$instagram_url = 'https://api.instagram.com/oauth/access_token';
echo print_r($instagram_parameters);

$redirect_context = stream_context_create(array('http' => array('method' => 'POST', 'content' => http_build_query($instagram_parameters))));
$redirect_response = file_get_contents($instagram_url, false, $redirect_context);

$returned_data = json_decode($redirect_response);

print_r($returned_data);

?>