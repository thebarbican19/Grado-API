<?

/*
$trending_import_url = "https://api.themoviedb.org/3/discover/movie?api_key=b8c339e24878d6766a67e14ba5ab3cbf&sort_by=popularity.desc";
$trending_import_data = json_decode(file_get_contents($trending_import_url));
$trending_title = md5($trending_import_data->results[0]->title) ;

print_r($trending_title);
*/

$request = xmlrpc_encode_request("LogIn", array("joebarbour", "getoutmystuff19", "en", "http://gradoapp.com"));
$context = stream_context_create(array('http' => array(
			'method' => "POST",
            'header' => "Content-Type: text/xml",
            'content' => $request)));
$file = file_get_contents("http://api.opensubtitles.org/xml-rpc", false, $context);
$response = xmlrpc_decode($file);

		

		

print_r($response);

?>