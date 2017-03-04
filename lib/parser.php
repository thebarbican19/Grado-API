<?

function parse_data($url, $message) {		
	$url_components = parse_url($url);	
	if (str_replace("www.", "", $url_components['host']) == "soundcloud.com") {
		$url_api = "http://api.soundcloud.com/resolve.json?url=" . $url . "&client_id=7c09611e6e77f1f7383ca61378180fb4";
		$url_contents = json_decode(file_get_contents($url_api));
		
		preg_match_all("/(#\w+)/", $url_contents->description, $url_hashtags);
			
		$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));
		$url_title = str_replace("'", "\'", $url_contents->title);
		$url_description = str_replace("'", "\'", $url_contents->user->username);
		$url_image[] = $url_contents->artwork_url;
		$url_key = $url_contents->id;
		$url_tags = implode(",", explode(" ", $url_contents->tag_list)) . "," . $url_hashtags . "," . str_replace(" ", "", $url_description) . ",soundcloud";
		$url_media = "http://api.soundcloud.com/tracks/" . $url_contents->id . "/stream?client_id=7c09611e6e77f1f7383ca61378180fb4";
		$url_site = "soundcloud.com";		
		$url_type = "audio";
		$url_icon = "";			
		
	}
	elseif (str_replace("www.", "", $url_components['host']) == "instagram.com") {
		$url_api = "http://instagram.com/publicapi/oembed/?url=" . $url;
		$url_context = stream_context_create(array('http' => array('timeout' => 45)));		
		$url_contents = json_decode(file_get_contents($url_api, false, $url_context));
				
		$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));
		$url_title = $url_contents->author_name;
		$url_description = $url_contents->title;
		$url_image[] = $url_contents->thumbnail_url;
		$url_key = $url_contents->media_id;
		$url_tags = $url_contents->author_name . ",instagram," . $url_hashtags . "," . implode(",", explode(" ", $url_description));
		$url_media = "";
		$url_site = "instagram.com";	
		$url_type = "image";	
		$url_icon = "";
				
	}
/*	
	elseif (str_replace("www.", "", $url_components['host']) == "itunes.apple.com") {
		$url_api = "https://itunes.apple.com/lookup?id=" . str_replace("id", "", end(explode("/", $url_components['path'])));	
		$url_context = stream_context_create(array('http' => array('timeout' => 45)));		
		$url_contents = json_decode(file_get_contents($url_api, false, $url_context));
		$url_contents = $url_contents->results[0];
		
		if ($url_contents->wrapperType == "software") {
			$url_title = str_replace("'", "\'", $url_contents->trackName);
			$url_description = str_replace("'", "\'", $url_contents->description);
			if (count($url_contents->screenshotUrls) > 0) $url_image = $url_contents->screenshotUrls[0];
			else $url_image = $url_contents->artworkUrl100;
			$url_data = $url_contents->trackId;
			$url_key = $url_contents->trackId;
			$url_tags = implode(",", $url_contents->genres) . ",software,ios,app";
			$url_media = "";
			$url_site = "apple";	
			$url_type = "app";
			$url_icon = "";
					
		}
						
	}
*/	
	elseif (str_replace("www.", "", $url_components['host']) == "youtube.com" || str_replace("www.", "", $url_components['host']) == "youtu.be") {
		preg_match("/^.*(youtu.be\/|v\/|embed\/|watch\?|youtube.com\/user\/[^#]*#([^\/]*?\/)*)\??v?=?([^#\&\?]*).*/", $url, $url_id);
	
		$url_api = "https://www.googleapis.com/youtube/v3/videos?key=AIzaSyCud6oml0_pgYIH0kM23BUAGnv0QMt7Qqg&id=" . end($url_id) . "&part=snippet";	
		$url_context = stream_context_create(array('http' => array('timeout' => 45)));		
		$url_contents = json_decode(file_get_contents($url_api, false, $url_context));
	
		preg_match_all("/(#\w+)/", $url_contents->items[0]->snippet->description, $url_hashtags);
			
		$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));
		$url_title = $url_contents->items[0]->snippet->title;
		$url_description = $url_contents->items[0]->snippet->description;
		$url_image[] = "http://img.youtube.com/vi/" . end($url_id) . "/mqdefault.jpg";
		$url_key = end($url_id);
		$url_media = "";
		$url_tags = implode(",", $url_contents->items[0]->snippet->tags) . "," . implode(",", explode(" ", $url_description));
		$url_site = "youtube.com";			
		$url_type = "video";
		$url_icon = "";
						
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "flickr.comsdadasdadasdsadasdasdasdad") {		
		$query = "http://api.flickr.com/services/rest/?method=flickr.photos.getInfo&api_key=" . API_KEY . "&photo_id=" . $photoid . "&format=json&nojsoncallback=1";
		
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "vine.co") {		
		$url_contents = download_web_content($url);			
		
		preg_match_all('/<meta[^>]+(?:name|property)=\"([^\"]*)\"[^>]+content=\"([^\"]*)\"[^>]*>/', $url_contents, $match_tags);
		
		if (isset($match_tags[2]) && count($match_tags[2])) {
            foreach ($match_tags[2] as $key => $value) {
                $meta_key = trim($match_tags[1][$key]);
                $meta_tag = trim($value);
                if ($meta_tag) $metatags[] = array($meta_key, $meta_tag);
               
            }
			
        }
		
		foreach($metatags as $tags) {
			if ($tags[0] == "twitter:title") $url_title =  str_replace("&quot;", "'", $tags[1]);
			if ($tags[0] == "twitter:description") $url_description = str_replace("&quot;", "'", $tags[1]);
			if ($tags[0] == "twitter:image:src") $url_image[] = $tags[1];	
			if ($tags[0] == "twitter:player:stream") $url_media = $tags[1];
						
	 	}
		
		preg_match_all("/(#\w+)/", $url_description, $url_hashtags);
			
		$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));	
		$url_tags = implode(",", $url_hashtags) . "," . explode(" " , implode(",", $url_title)) . "," . explode(" " , implode(",", $url_description));
		$url_key = end(explode("/", $url));
		$url_site = "vine.co";			
		$url_type = "video";
		$url_icon = "";
		
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "vimeo.comsdadasdadasdsadasdasdasdad") {		
		//preg_match('#document\.getElementById\(\'player_(.+)\n#i', file_get_contents($url), $media_script);	
		//preg_match('#"timestamp":([0-9]+)#i', $media_script[1], $timestamp_match);
   	 	//preg_match('#"signature":"([a-z0-9]+)"#i', $media_script[1], $signature_match);
		
		//preg_match("/(https?:\/\/)?(www\.)?(player\.)?vimeo\.com\/([a-z]*\/)*([0-9]{6,11})[?]?.*/", $url, $url_id);
		//preg_match_all("/(#\w+)/", $url_contents[0]->description, $url_hashtags);
				
		$url_api = "https://vimeo.com/api/v2/video/" . end($url_id) . ".json";	
		$url_context = stream_context_create(array('http' => array('timeout' => 45)));		
		$url_contents = json_decode(file_get_contents($url_api, false, $url_context));
	
		$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));
		$url_title = $url_contents[0]->title;
		$url_description = $url_contents[0]->description;
		$url_image[] = $url_contents[0]->thumbnail_large;
		$url_key = $url_contents[0]->id;
		$url_media = "http://player.vimeo.com/play_redirect?clip_id=" . $url_id . "&sig=" . $signature_match[0] . '&time=' . $timestamp_match[0] . "&quality=sd";
		$url_tags = implode(",", explode(",", str_replace(" ", "", $url_contents[0]->tags))) . "," . $url_hashtags;
		$url_site = "vimeo.com";		
		$url_type = "video";
		$url_icon = "";
					
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "media.giphy.com" || str_replace("www.", "", $url_components['host']) == "giphy.com") {
		$url_contents = download_web_content($url);
		$url_metatags = output_meta($url_contents);
		
		foreach($url_metatags as $tags) {
			if ($tags[0] == "keywords") $url_keywords = explode(", ", strtolower($tags[1]));
			if ($tags[0] == "og:image") $url_image = explode(",", $tags[1]);
			
		}
							
		preg_match('/<title>(.*)<\/title>/', $url_contents, $match_title);
			
		$url_hashtags = "";		
		$url_title = str_replace(" GIF", "", reset(explode(" - ", $match_title[1])));
		$url_description = "";
		$url_key = end(explode("/", $url));
		$url_media = "";
		$url_tags = implode(",", $url_keywords);
		$url_site = "giphy.com";		
		$url_type = "image";
		$url_api = "";	
		$url_icon = "";
		if (empty($url_image)) $url_image[] = $url;
		
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "imgur.com" || str_replace("www.", "", $url_components['host']) == "i.imgur.com" || str_replace("www.", "", $url_components['host']) == "m.imgur.com") {			
		$url_contents = download_web_content($url);
				
		preg_match('/<title>(.*)<\/title>/', $url_contents, $match_title);
			
		$url_hashtags = "";		
		$url_title = $match_title[1];
		$url_description = "";
		$url_key = reset(explode(".", end(explode("/", $url))));
		$url_image[] = "http://imgur.com/" . $url_key . ".gif";
		$url_media = "";
		$url_tags = "gif," . implode(",", explode(" ", $match_title[1]));
		$url_site = "imgur.com";		
		$url_type = "image";
		$url_api = "";	
		$url_icon = "";
		
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "dribbble.com") {			
		preg_match("/(?<=dribbble.com\/shots\/).{7}/", $url, $url_id);
		
		$url_api = "https://api.dribbble.com/v1/shots/" . end($url_id) . "?access_token=83b66354dbc16b5eb2e2e162249f2c640fba9598e9cd687c3b908efbb9955f8c";	
		$url_context = stream_context_create(array('http' => array('timeout' => 45)));		
		$url_contents = json_decode(file_get_contents($url_api, false, $url_context));
		
		preg_match_all("/(#\w+)/", $url_contents->description, $url_hashtags);
		
		$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));
		$url_title = $url_contents->title;
		$url_description = $url_contents->description;
		if (array_key_exists('hidpi', $url_contents->images) && isset($url_contents->images->hidpi)) $url_image[] = $url_contents->images->hidpi;
		else $url_image[] = $url_contents->images->normal;
		$url_key = end($url_id);
		$url_media = "";
		$url_tags = implode(",", $url_contents->tags) . ",design";
		$url_site = "dribbble.com";			
		$url_type = "image";
		$url_icon = "";
				
	}
	
	elseif (str_replace("www.", "", $url_components['host']) == "imdb.com") {			
		preg_match("/tt\\d{7}/", $url, $url_id);
			
		$url_api = "http://www.omdbapi.com/?i=" . end($url_id) . "&plot=full&r=json";	
		$url_context = stream_context_create(array('http' => array('timeout' => 45)));		
		$url_contents = json_decode(file_get_contents($url_api, false, $url_context));
		
		$url_title = $url_contents->Title . " (" . $url_contents->Year . ")";
		$url_description = $url_contents->Plot;
		$url_image[] = $url_contents->Poster;
		$url_key = end($url_id);
		$url_media = "";
		$url_talent = $url_contents->Director . ", " . $url_contents->Actors . ", " . $url_contents->Writer;
		$url_tags = str_replace(" ", "", $url_title) . "," .  str_replace(" ", "", $url_contents->Genre) . "," . $url_contents->Type . "," . str_replace(" ", "", $url_talent);
		$url_site = $url_components['host'];
		$url_type = "video";
		$url_icon = "";
		$url_hashtags = "";		
				
	}
	
	if (empty($url_type)) {
		$url_file = pathinfo($url);
		$url_imagefiles = array("png", "jpg", "jpeg", "tiff", "gif");
		$url_videofiles = array("mp4", "mov", "3gp");
		if (in_array($url_file['extension'], $url_imagefiles) || getimagesize($url)) {
			$url_api = "";				
			$url_title = "";
			$url_description = "";
			$url_image[] = $url;
			$url_key = "";
			$url_media = "";
			$url_tags = $url_components['host'] . "";
			$url_site = $url_components['host'];
			$url_type = "image";
			$url_icon = "";
			
		}
		/*
		elseif (in_array($url_file['extension'], $url_videofiles)) {
			$url_api = "";				
			$url_title = "";
			$url_description = "";
			$url_image = "";
			$url_key = "";
			$url_media = $url;
			$url_tags = "video";
			$url_site = $url_components['host'];
			$url_type = "video";
			
		}*/
		else {
			$url_contents = download_web_content($url);
			
		  	preg_match('/<title>(.*)<\/title>/', $url_contents, $match_title);
			preg_match_all('/<link[^>]+(?:rel)=\"([^\"]*)\"[^>]+href=\"([^\"]*)\"[^>]*>/', $url_contents, $match_links);
		   	preg_match_all('/<img[^>]+src=([\'"])?((?(1).+?|[^\s>]+))(?(1)\1)/', $url_contents, $match_images);
		 	preg_match_all('/(?<=src=\"(https://|http://|)(www.|)youtube.com/(embed|watch?v=|?v=){11}/', $url_contents, $match_videos);
				
			$url_title = $match_title[1];
			$url_metatags = output_meta($url_contents);
			
			//read and seporate meta tags
		 	foreach($url_metatags as $tags) {
				if ($tags[0] == "description") $url_description = str_replace("&quot;", "'", $tags[1]);
				if ($tags[0] == "keywords") $url_keywords = explode(" ", strtolower($tags[1]));
				if ($tags[0] == "og:image") $url_image[] = $tags[1];	
				if ($tags[0] == "og:type") $url_page = $tags[1];
				if ($tags[0] == "og:title" || empty($url_title)) $url_title = $tags[1];	
				if ($tags[0] == "article:tag") $url_keywords[] = str_replace(":", "", $tags[1]);
					
				
		 	}
			
			preg_match_all("/(#\w+)/", $url_description, $url_hashtags);
						
			//convert meta links into readable array			
			if (isset($match_links[2]) && count($match_links[2])) {
                foreach ($match_links[2] as $key => $value) {
                    $link_key = trim($match_links[1][$key]);
                    $link_tag = trim($value);
                    if ($link_tag) $metatags[] = array($link_key, $link_tag);
                   
                }
				
            }
			
			$url_icon = "";
			foreach($metatags as $links) {
				if ($links[0] == "shortcut icon") $url_icon = $links[1];	
				if ($links[0] == "apple-touch-icon-precomposed") $url_icon = $links[1];	
				
			}
						
			if (isset($match_images[2]) && count($match_images[2])) {
                foreach ($match_images[2] as $image) {
                    $image_value = trim($image);
					$image_filetype = end(explode(".", $image));
					if (($image_filetype == "jpg" || $image_filetype == "png" || $image_filetype == "gif" || $image_filetype == "jpeg") && (strpos($image, 'http://') !== false || strpos($image, 'https://') !== false))	{
						$url_image[] = $image;	
						
					}
					
                }
								
			}
				
			$url_hashtags = str_replace("#", "", implode(",", reset($url_hashtags)));
			$url_api = "";		
			$url_type = "url";	
			$url_key = "";
			$url_site = $url_components['host'];	
			$url_media = "";
			
			if (count(explode(" ", $url_description)) < 2) $url_description = "";
			if (count(explode(" ", $url_title)) < 2) $url_title = $url_components['host'];
					
			$url_site = $url_components['host'];
			$url_title = $url_contents->title;
			$url_description = $url_description;
			$url_image = $url_contents->images;		
			$url_api = "";		
			$url_type = "url";	
			$url_key = "";
			$url_media = "";
			$url_hashtags = "";
			$url_icon = "";
			//$url_videos = $url_contents->videos;
			
		}
		
	}
	
	$url_image = implode(",", $url_image);
	
	$url_tags .= implode(",", explode(" ", $message));
	$url_tags = explode(",", $url_tags);	
	$url_tags_output = array();		
	$url_tag_exists = array();	
	foreach ($url_tags as $tag) {
		if (!empty($tag) && exclude_tag($tag) == FALSE && count($url_tags_output) <= 20 && strlen($tag) > 2 && strlen($tag) < 18 && preg_match('/[0-9.!?,;:()].\"\'/', $tag) == 0) {
			if (strlen($tag) > 2) $url_tags_output[] = strtolower(ereg_replace("[^A-Za-z]", "", $tag));
			
		}
		
	}
	
	foreach ($url_tags_output as $tag) {
		if (!in_array($tag, $url_tag_exists) && strlen($tag) > 3) $url_tag_exists[] = $tag; 
		
	}
			
	
	$url_tags_output = implode(",", $url_tag_exists);
		
	$url_title = format_text($url_title);
	$url_description = format_text($url_description);
	$url_message = format_text($url_message);
	
	return array("type" => $url_type, "title" => $url_title, "description" => $url_description, "image" => $url_image, "media" => $url_media, "key" => $url_key, "tags" => $url_tags_output, 'url' => $url, 'api' => $url_api, 'site' => $url_site, 'icon' => $url_icon, 'hashtags' => $url_hashtags, 'message' => $url_message, 'videos' => $url_videos);
		
}

function exclude_tag($tag) {
	$tags_exclude = array("this", "that", "then", "than", "there", "theres", "their", "they", "the're", "are", "a", "is", "it", "its", "you", "your", "you've", "at", "on", "in", "where", "when", "whens", "who", "whos", "whod", "what", "whats", "want", "wants", "why", "true", "false", "the", "get", "got", "gets", "gave", "i", "do", "don't", "does", "usually", "said", "says", "saw", "touch", "stare", "can", "can't", "always", "forever", "now", "today", "tomorrow", "last", "week", "month", "year", "yestarday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday", "day", "time", "am", "pm", "usually", "unusual", "user", "use", "yo", "turned", "tap", "tan", "turned", "action", "full", "from", "morning", "afternoon", "evening", "night", "yeh", "yah", "yeah", "oh", "aw" ,"aww", "how", "has", "much", "maybe", "depending", "depends", "dependand", "but", "more", "thats", "im", "well", "getting", "prepared", "dont", "doesnt", "same", "and", "description", "no", "if", "these", "happen","happened", "happening" ,"top", "things", "heard", "here", "first", "lets", "just", "not", "join", "about", "as", "we", "down", "our", "will", "for", "latest", "growing", "list", "new", "another", "written", "follow", "gains", "me", "few", "includes", "are", "should", "may", "soon", "change", "since", "article", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twleve", "thirteen", "fourteen", "fiveteen", "sixteen", "seventeen", "eighteen", "nineteen", "twenty", "thrity" ,"fourty", "fifty", "sixty", "seventy" ,"eighty", "ninety", "hundred", "thousand", "million", "billion" ,"mainly", "enough", "visit", "visited", "visiting", "back", "backing", "backed", "featured", "below", "like", "were" ,"theyre", "lot", "every", "thing", "think", "wrong", "say", "agree" ,"everything", "divided", "weekends", "but", "free", "sliding", "while", "posted", "info", "tell", "have", "watch", "again", "all", "lack", "middle", "top", "left", "right", "bottom", "wont" ,"reveal", "best", "bad", "was", "realised", "part", "only", "been", "double", "subconsciously", "clicked", "fast", "company", "website", "know", "test", "walk", "over", "ready", "looks", "newest", "released", "news", "which", "cant", "according" , "once", "twice", "times", "method", "fall", "under", "minute", "second", "hour", "simple", "takes", "hardly", "any", "done", "pretty", "tool", "also", "known", "losing", "stream", "popular", "announcing", "announcment", "announced", "sir", "side", "take", "too", "many", "against", "apparently", "updated", "post", "uprising", "stand", "friend", "being", "knocked", "knock", "knocking", "real", "realism", "catches", "movement", "moving", "moved", "bark", "catches", "uncanny", "attention", "look", "check", "out", "held", "whats", "coming", "between", "services", "great", "work", "ago", "some", "examples", "brief", "whether", "name", "idea", "daily", "into", "most", "largest", "somewhere", "largest", "years", "grew", "board", "putting", "put", "puts", "his", "story", "saved", "man", "woman", "show", "really", "led", "increase", "improve", "behind", "increase", "small", "large", "tiny", "big", "massive", "huge", "might", "looking", "constantly", "other", "let", "opening", "finally", "other", "had", "need", "because", "cause", "caused", "didnt", "quite", "would", "quite", "unnecessary", "revealed", "impressively", "make", "mr", "miss", "mrs", "super", "chance", "those", "shine", "air", "land", "route", "routes", "even", "move", "them", "themselves", "ourselves", "head", "roll", "foot", "means", "whatever", "fine", "working", "colour", "try", "improve", "opportunity", "tend", "feel", "photos", "photo", "image", "video", "clip", "try", "heres", "job", "youre", "comment", "comes", "love", "gender", "still", "matter", "male", "female", "could", "luck", "cheer", "up", "noticed", "owner", "sad", "happy", "brought", "tells", "explains", "likes", "hot", "hottest", "hotter", "cold", "colder", "coldest", "turn", "turned", "turning", "taken", "yourself", "myself", "see", "saw", "seeing", "inside", "after", "discovers", "discovered", "discovering", "disturbing", "low", "high", "often", "protects", "used", "strength", "weight", "plain", "heat", "old", "older", "oldest", "never", "giving", "ranked", "greet", "stranger", "cheek", "giving", "firm", "ever", "whom" ,"comfortable", "touched", "less", "more", "biggest", "big", "bigger", "small", "smallest", "smaller", "large", "largest", "larger", "days", "men", "women", "male", "female", "sank", "drank", "thank", "thanks", "plank", "he", "hes", "she", "shes", "her", "him", "own", "buck", "dared", "cared", "scared", "paying", "call", "calls", "called", "phones", "talks", "talk", "talking", "shout", "shouting", "shouted", "wisper", "wispering", "wispered", "alive", "experience", "official", "presentation", "making", "goes", "going", "go", "gone", "reported", "reporting", "reports", "report", "demand", "demanding", "demanded", "actually", "actual", "send", "sent", "sending", "sender" , "next", "previous", "using", "used", "use", "uses", "bunch", "munch", "punch", "including" , "include", "included", "uninclude", "giant", "tiny", "small", "large", "enormous", "micro", "offered", "offer", "offering", "offered", "departure", "departed", "departing", "issues", "issues", "issued", "longtime", "long", "detailed", "detail", "details", "explanation", "explain", "explained", "explaning", "passes", "passed", "pass", "passing", "motion", "shared", "sharing", "sharer", "somewhat", "somehow", "somewhere", "someone", "someones", "something", "tools", "tool", "create", "created", "creator", "creating", "nowadays" ,"details", "verdict", "verdicts", "vote", "voting", "voted", "help", "helped", "helping", "helpless", "fit", "fat", "thinks", "think", "thinking", "thought", "thoughts", "safe", "safety", "danger", "dangerous", "status", "check", "checked", "checking", "checker", "warns", "warning", "warned", "pages", "page", "available" ,"availability", "jan", "feb", "mar", "apr", "may", "june", "july", "aug", "sep", "oct", "nov", "dec", "january", "february", "march", "april", "august", "september", "october", "november", "december", "beautiful", "beauty", "pretty", "stunning", "stunner", "stop", "stopping", "stopped", "telling", "told", "tell", "clearly", "clear", "unclear", "unclearly", "far", "forward", "further", "selected", "select", "deselect", "deselected", "posts", "post", "upload", "uploaded", "images", "image", "unbelievably", "unbelievable", "believably", "unbelievable", "off", "on", "insane", "crazy", "work", "worked", "working", "works", "worker", "amazing", "amaze", "amazed", "nicknamed", "nickname", "read", "reading", "wrote", "writing", "give", "gave", "given", "hear", "heard", "hearing", "final", "experienced", "experience", "link", "linked", "bio", "member", "members", "abandoned", "abandon", "abandonment", "current", "currently", "scroll", "scrolling", "scroller", "scrolling", "trip", "trips", "tripping", "tripped", "walls", "wall", "slide", "slider", "sliding", "youll", "normal", "normality", "easy", "easiest", "hard", "hardest", "nice", "nicest", "nicely", "nughty", "horrible", "good", "great", "incredible", "fantastic", "fantastical", "tall", "taller", "tallest", "short", "shorter", "shortest", "tiny", "tiniest", "tinier", "needs", "need", "needed", "needing", "upon", "finds", "found", "finding", "finder", "lost", "loose", "looser", "study", "studying", "studied", "already", "ready", "aka", "recent", "recently", "besides", "beside", "impress", "impressive", "impressed", "skills", "skill", "skilled", "slow", "slower", "slowing", "slowest", "fastest", "fast", "fasted", "faster", "main", "center", "central", "bit", "bite", "stir", "stired", "stiring", "meet", "meeting", "without", "within", "with", "aims", "aims", "designed", "designing", "before", "after", "alter", "simplify", "simple", "interest", "interested", "around", "round", "rounded", "replaced" ,"replace", "replacing", "meant", "mean", "convert", "converted", "converting", "harmless", "harm", "harmful", "harming", "harmed", "harmless", "breaks", "breaking", "broken", "broke", "break", "report", "rumor", "rumors" ,"rumored", "flying", "fly", "flew", "decided", "decide", "deciding", "spare", "sparing", "spared", "suffered", "suffering", "suffer" ,"delusions", "deluded", "control", "controling", "attacking", "attack", "attacked", "started", "starting", "start");
				
	if (!in_array(strtolower(ereg_replace("[^A-Za-z]", "", $tag)), $tags_exclude)) return FALSE;
	else return TRUE;
					
}

function download_web_content($url) {
	$url_image = array();
	$ch = curl_init();
	$url_curl = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_BINARYTRANSFER => true,
        CURLOPT_HEADER => true, // don't return headers
        CURLOPT_FOLLOWLOCATION => true, // follow redirects
        CURLOPT_ENCODING => "", // handle all encodings
        CURLOPT_USERAGENT => "spider", // who am i
        CURLOPT_AUTOREFERER => true, // set referrer on redirect
        CURLOPT_CONNECTTIMEOUT => 60, // timeout on connect
        CURLOPT_TIMEOUT => 120, // timeout on response
        CURLOPT_MAXREDIRS => 5, // stop after 10 redirects
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_VERBOSE => false,
		
    );
	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt_array($ch, $url_curl);
    $url_contents = curl_exec($ch);
    curl_close($ch);
	
	return $url_contents;
	
}

function output_meta($content) {
	preg_match_all('/<meta[^>]+(?:name|property)=\"([^\"]*)\"[^>]+content=\"([^\"]*)\"[^>]*>/', $content, $match_tags);
	
	//convert meta tags into readable array		
	if (isset($match_tags[2]) && count($match_tags[2])) {
        foreach ($match_tags[2] as $key => $value) {
            $meta_key = trim($match_tags[1][$key]);
            $meta_tag = trim($value);
            if ($meta_tag) $metatags[] = array($meta_key, $meta_tag);
           
        }
		
    }
	
	return $metatags;
	
}

function format_text($text) {
	$replace_text = trim($text);
	$replace_text = htmlentities($replace_text, ENT_QUOTES);
	return $replace_text;
		
}

?>