<?

mysqli_select_db($database_grado_connect, "gradtag");

function tags_popularity($text, $limit) {
	$text_format = preg_replace('/[^a-zA-Z\d -]+/i', '', $text);
	$text_format = strtolower($text_format);
	$text_explode = explode(" ", $text_format);
	$text_exclude = tags_exclude();
	
	foreach ($text_explode as $word) {
		if (!in_array($word, $text_exclude) && strlen($word) > 2) $tag_popularity[] = $word;
			
	}
	
	foreach (array_count_values($tag_popularity) as $item => $count) {
		if ($count > (2 + $limit)) $tag_popularity_output[] = array("tag" => $item, "count" => (int)$count, "rule" => "dup_match");
		
	}
	
	return $tag_popularity_output;
	
}

function tags_produce($text) {		
	global $database_grado_connect;
	
	$text_format = html_entity_decode($text, ENT_QUOTES);
	$text_format = strip_tags($text_format);
	$text_format = trim($text_format);
	$text_format = preg_replace("/\r|\n/", " ", $text_format);
	$text_format = preg_replace("/\s+/"," ", $text_format);
	$text_format = preg_replace('/[^a-zA-Z\d -]+/i', '', $text_format);
	$text_format = strtolower($text_format);
	$text_explode = explode(" ", $text_format);
	$text_word_count = count($text_explode);
	
	$request_exists = mysqli_query($database_grado_connect, "SELECT `request_tags` FROM `requests` WHERE `request_body` LIKE '$text_format' AND `request_app` LIKE '$request_app' LIMIT 0, 1");
	$request_exists = mysqli_num_rows($request_exists);
	
	if ($text_word_count > 350) $tag_minimum_count = 2;
	elseif ($text_word_count > 450) $tag_minimum_count = 3;
	elseif ($text_word_count > 900) $tag_minimum_count = 4;
	elseif ($text_word_count > 1800) $tag_minimum_count = 5;
	else $tag_minimum_count = 1;
		
	//sql injection
	$tag_injection = "SELECT tag_type, GROUP_CONCAT(tag_word SEPARATOR ',') AS tag_words, GROUP_CONCAT(tag_subtype SEPARATOR ',') AS tag_subtype, COUNT(*) AS tag_count, tag_latitude, tag_longitude FROM dictionary WHERE (";
	$tag_duplicates = array();
	for ($i = 0; $i < $text_word_count; $i++) {
		$tag_lower = strtolower($text_explode[$i]);
		$tag_next = strtolower($text_explode[$i + 1]);		
		if (!in_array($tag_lower, $tag_duplicates) && !in_array($tag_lower, tags_exclude())) {
			$tag_duplicates[] = $tag_lower;
			$tag_injection .= "tag_word LIKE '$tag_lower' ";
				
			if (strlen($tag_next) > 0) $tag_injection .= "OR ";
			
		}
				
	}
	
	//dictionary query
	
	$tag_injection .= ") AND tag_search = 1 GROUP BY tag_type HAVING tag_count > 0 OR (tag_count > 1 && tag_latitude != 0 && tag_longitude != 0) ORDER BY tag_count DESC LIMIT 0, 200";
	$tag_injection = str_replace(" OR )", ")", $tag_injection);
	$tag_match = mysqli_query($database_grado_connect, $tag_injection);
	while($row = mysqli_fetch_array($tag_match)) {	
		$tags_match_count = $row['tag_count'];	
		$tags_match_type = $row['tag_type'];	
		$tags_match_subtype = explode(",", $row['tag_subtype']);	
		$tags_match_words = explode(",", $row['tag_words']);			
		$tags_match_latitude = (float)$row['tag_latitude'];	
		$tags_match_longitude = (float)$row['tag_longitude'];
		if ($tags_match_latitude != 0 && $tags_match_longitude != 0) {
			$tags_match_locations[] = array("latitude" => $tags_match_latitude, "longitude" => $tags_match_longitude, "country" => $tags_match_type);
			
		}
			
		$tags_output_sorted = tag_rules($tags_match_type, $tags_match_subtype, $tags_match_words, $tags_match_count, $tags_added);
		foreach ($tags_output_sorted as $array) {
			if (!in_array($array['tag'], $tags_added)) {
				$tags_output[] = $array;
				$tags_added[] = $array['tag'];
				
			}
			
		}
		
	}
	
	if (count($tags_match_locations) == 0) $tags_match_locations = array();
	
	preg_match_all('/[a-z][A-Z][\w-]{4,}|[A-Z][\w-]{3,}|([A-Z][\w-]*(\s+[A-Z][\w-]*)+)|\w*[A-Z]\w*[A-Z]\w*|\b[A-Z]{3,}\b/', $text, $names_output);	
	preg_match_all("/(#\w+)/", $text, $hashtag_output);
	preg_match_all('/(\$|£|€|ƒ|¥|៛|¢|лв|₪|₩|₦|₴|฿)[0-9,]+(\.[0-9]{2})?/i', $text, $price_output);
	preg_match_all('/(?:19|20)\d{2}/i' ,$text, $years_output);
	
	foreach (reset($price_output) as $costs) {
		$costs = preg_replace('/[^0-9.]/', '', $costs);
		$costs = (float)$costs;
		$costs_array[] = $costs;
		$costs_total += $costs;

	}
	
	if (max($costs_array) > 250) {
		$tags_added[] = "luxury";		
		$tags_output[] = array("tag" => "luxury", "count" => 0, "rule" => "price_match");
		
	}
	
	foreach (reset($years_output) as $year) {
		if ($year >= 1940 && $year < 1950) $year_dacade[] = "forties"; 
		if ($year >= 1950 && $year < 1960) $year_dacade[] = "fifties"; 
		if ($year >= 1960 && $year < 1970) $year_dacade[] = "sixties"; 
		if ($year >= 1970 && $year < 1980) $year_dacade[] = "seventies"; 
		if ($year >= 1980 && $year < 1990) $year_dacade[] = "eighties"; 
		if ($year >= 1990 && $year < 2000) $year_dacade[] = "nineties"; 
		if ($year >= 2000 && $year < 2010) $year_dacade[] = "noughties"; 
				
	}
	
	foreach (array_count_values($year_dacade) as $item => $count) {
		if ($count > 1) {
			$tags_added[] = strtolower($item);			
			$tags_output[] = array("tag" => $item, "count" => (int)$count, "rule" => "time_match");
			
		}
		
	}
	
	//new tags
	foreach (reset($names_output) as $name_full) {
		$item_array[] = str_replace(" ", "", strtolower($name_full));
		
	}

	foreach (array_count_values($item_array) as $item => $count) {
		$tag_explode = explode(" ", $item);
		if (!in_array($item, $tags_added) && strlen($item) > 2 && !in_array($item, tags_exclude())) {
			$tags_nonexistant[] = strtolower($item);
			if ((count($tag_explode) == 1 && $count > ($tag_minimum_count + 1)) || (count($tag_explode) > 1 && $count > ($tag_minimum_count + 1))) {
				$tags_added[] = strtolower($item);
				$tags_output[] = array("tag" => strtolower($item), "count" => (int)$count, "rule" => "name_match");
				
			}
			
		}
		
	}
	
	foreach (reset($hashtag_output) as $hashtag) {
		$tag_hashtag = str_replace("#", "", strtolower($hashtag));
		if (!in_array($tag_hashtag, $tags_added)) {
			$tags_nonexistant[] = $tag_hashtag;
			$tags_output[] = array("tag" => $tag_hashtag, "count" => 0, "rule" => "hash_match");
			$tags_added[] = $tag_hashtag;	

		}			
				
	}
	
	foreach (tags_popularity($text, $tag_minimum_count) as $tag) {
		foreach ($tag as $key => $type) {
			if (!in_array($type, $tags_added) && $key == "type") $tags_output[] = $tag;
		
		}
		
	}
	
	//names 
	$names_tags = array();
	$names_exclude = tags_exclude();
	$tags_names_output = array();
	foreach (array_count_values(reset($names_output)) as $name => $count) {
		if ($count > $tag_minimum_count && !in_array(strtolower($name), $names_exclude)) $names_tags[] = $name;
		
	}
	
	//count names
	
	if (count($tags_output) == 0) $tags_output = array();
			
	$tags_rating = tags_rating($text);
	$tags_rating_positivity = $tags_rating['positivity_rating'];
	$tags_rating_expletive = $tags_rating['expletive_count'];
	foreach ($tags_output as $key => $row) {
		$tags_sort[$key] = $row['count'];
		
	}
	
	array_multisort($tags_sort, SORT_DESC, $tags_output);
		
	if ($request_exists == 0) $tags_uploaded = tags_upload($tags_nonexistant, $tags_output[0]['tag']);
	$tags_requested = tags_requested($text_format, $tags_added);
			
	return array("total_words" => $text_word_count, "positivity_rating" => $tags_rating_positivity, "nsfw_score" => $tags_rating_expletive, "relevant_tags" => $tags_output, "added_tags" => $tags_uploaded, 'total_tags' => count($tags_output), "names" => $names_output[0], "locations" => $tags_match_locations);
	
}

function tags_requested($text, $tags) {
	global $database_grado_connect;
	global $session_application;
	global $session_ip;
	global $session_country;
			
	$request_tags = implode(",", $tags);
	$request_post = mysqli_query($database_grado_connect, "INSERT INTO `gradtag`.`requests` (`request_id`, `request_timestamp`, `request_body`, `request_tags`, `request_app`, `request_ip`, `request_country`) VALUES (NULL, CURRENT_TIMESTAMP, '$text', '$request_tags', '$session_application', '$session_ip', '$session_country');");
	
}

function tags_rating($text) {
	global $database_grado_connect;
	
	$text_remove = tags_exclude();	
	$text_format = preg_replace('/[^a-zA-Z\d -]+/i', '', $text);
	$text_format = strtolower($text_format);
	$text_explode = explode(" ", $text_format);
	//$text_explode = array_diff($text_explode, $text_remove);	
	$text_count = count($text_explode);	
	
	for ($i = 0; $i < $text_count; $i++) {
		$tag_lower = strtolower($text_explode[$i]);
		if (!in_array($tag_lower, $tag_duplicates)) {
			$rating_tag_injection .= "tag_word LIKE '$tag_lower' ";
			$rating_word_count += 1;
			if ($i < ($text_count - 1)) $rating_tag_injection .= "OR ";
			
		}
				
	}
	
	$rating_negative = 0;
	$rating_positive = 0;
	$rating_expletive_count = 0;
	
	$rating_injection = "SELECT tag_type, COUNT(*) AS tag_count FROM dictionary WHERE (";	
	$rating_injection .= $rating_tag_injection;
	$rating_injection .= ") AND tag_search = 0 AND (tag_type LIKE 'positive' OR tag_type LIKE 'negative' OR tag_type LIKE 'expletive') GROUP BY tag_type ORDER BY tag_count DESC LIMIT 0, 3";
	$rating_query = mysqli_query($database_grado_connect, $rating_injection);
	while($row = mysqli_fetch_array($rating_query)) {
		if ($row['tag_type'] == "positive")	$rating_positive = $row['tag_count'];
		else if ($row['tag_type'] == "negative") $rating_negative = $row['tag_count'];
		else if ($row['tag_type'] == "expletive") $rating_expletive_count = (int)$row['tag_count'];
		
	}
	
	$rating_positivity_score = round(($rating_positive / ($rating_positive + $rating_negative)) * 100);
	
	return array("positivity_rating" => $rating_positivity_score, "expletive_count" => $rating_expletive_count);
	
}

function tag_rules($catgory, $subcatagorys, $tags, $typecount, $existing) {
	if ($catgory == "colors") $tags_rule = array("subtype");
	elseif ($catgory == "space") $tags_rule = array("type", "subtype");
	elseif ($catgory == "clothing") $tags_rule = array("tag", "subtype", "type");
	elseif ($catgory == "health") $tags_rule = array("subtype", "type");	
	elseif ($catgory == "food") $tags_rule = array("subtype", "tag");		
	elseif ($catgory == "country") $tags_rule = array("subtype", "tag");
	elseif ($catgory == "animals") $tags_rule = array("subtype", "type");
	elseif ($catgory == "country") $tags_rule = array("tag", "subtype");	
	else $tags_rule = array("type", "subtype");
		
	if (in_array("type", $tags_rule) && $typecount > 1) {
		$rules_output[] = array("tag" => $catgory, "count" => (int)$typecount, "rule" => "dict_match");
		
	}
	
	if (in_array("subtype", $tags_rule)) {
		foreach ($subcatagorys as $subtype) {
			if (strlen($subtype) > 0) $tags_match_subtype_added[] = $subtype;
				
		}
		
		foreach (array_count_values($tags_match_subtype_added) as $subtype => $count) {
			if ($count > 1 && strlen($subtype) > 2) {
				$rules_output[] = array("tag" => $subtype, "count" => (int)$count, "rule" => "dict_match");
				
			}
				
		}
		
	}

	if (in_array("tag", $tags_rule)) {
		foreach ($tags as $tag) {
			if (strlen($tag) > 0) $tags_match_tag_added[] = $tag;
				
		}
		
		foreach (array_count_values($tags_match_tag_added) as $tag => $count) {
			if ($count > 0 && strlen($tag) > 2) {
				$rules_output[] = array("tag" => $tag, "count" => (int)$count, "rule" => "dict_match");
				
			}
				
		}
		
	}
		
	if (count($tags_output) == 0) $tags_output = array();
	
	return $rules_output;
	
}

function tags_stats() {
	global $database_grado_connect;
		
	$stats_requested = mysqli_query($database_grado_connect ,"SELECT `request_timestamp`,`request_ip`,`request_country` FROM `requests` WHERE `request_app` LIKE '$session_application'");
	$stats_request_count = mysqli_num_rows($stats_requested);
	
	$stats_query = mysqli_query($database_grado_connect ,"SELECT tag_type, GROUP_CONCAT(tag_subtype SEPARATOR ',') AS tag_subtype, COUNT(*) AS tag_count FROM dictionary GROUP BY tag_type ORDER BY tag_count DESC");
	$stats_type_total = mysqli_num_rows($stats_query);
	$stats_tag_total = 0;
	$stats_subtype_total = 0;
	while($row = mysqli_fetch_array($stats_query)) {
		$stats_subtypes = explode(",", $row['tag_subtype']);
		$stats_type = $row['tag_type'];
		$stats_tagcount = (int)$row['tag_count'];
		$stats_tag_total =+ $stats_tagcount;
		$stats_subtype_added = array();
		
		foreach ($stats_subtypes as $subtype) {
			if (strlen($subtype) > 1 && !in_array($subtype, $stats_subtype_added)) {
				$stats_subtype_added[] = strtolower($subtype);
				$stats_subtype_total += 1;
						
			}
			
		}
			
		$stats_types_output[] = array("type" => $stats_type, "subtypes" => $stats_subtype_added, "count" => $stats_tagcount);
		
	}
	
	return array("tags_total" => $stats_tag_total, "tags_types" => $stats_types_output, "types_total" => $stats_type_total, "subtypes_total" => $stats_subtype_total, 'request_count' => $stats_request_count);
	
}

function tags_upload($tags, $catagory) {
	global $database_grado_connect;
		
	foreach ($tags as $tag) {
		$trending_upload_count = 5;
		$trending_tag = strtolower($tag);
		$trending_post = mysqli_query($database_grado_connect, "INSERT INTO `discovered` (`discovered_id`, `discovered_timestamp`, `discovered_tag`, `discovered_catagory`, `discovered_subcatagory`) VALUES ('', CURRENT_TIMESTAMP, '$trending_tag', '$catagory', '');");
		if ($trending_post) {
			$trending_existing = mysqli_query($database_grado_connect, "SELECT `discovered_tag`, COUNT( * ) AS discovered_count FROM `discovered` WHERE `discovered_tag` LIKE '$trending_tag' HAVING discovered_count >= $trending_upload_count ORDER BY COUNT(*) DESC ,discovered_timestamp DESC LIMIT 0 , 1");
			$trending_exists = mysqli_num_rows($trending_existing);
			if ($trending_exists > 0) {
				$dictionary_add = mysqli_query($database_grado_connect, "INSERT INTO `dictionary` (`tag_id` ,`tag_word` ,`tag_type` ,`tag_subtype` ,`tag_affiliate` ,`tag_search` ,`tag_auto`, `tag_submitted`) VALUES (NULL, '$trending_tag',  '$catagory', '', '', '1', '1', '$session_application');");
				if ($dictionary_add) $dictionary_added[] = array("tag" => $trending_tag, "catagory" => $catagory);
									
			}
			
		}
		
	}
	
	if (count($dictionary_added) == 0) $dictionary_added = array(); 
	
	return $dictionary_added;
	
}

function tags_exclude() {
	return array("this", "that", "then", "than", "there", "theres", "their", "they", "there", "are", "a", "is", "isnt", "it", "itll", "ive", "its", "you", "your", "yours", "youve", "yes", "yet", "no", "at", "on", "in", "instead", "where", "when", "went", "whens", "whenever", "who", "whos", "whod", "what", "whats", "want", "wants", "wanted", "why", "true", "false", "the", "get", "got", "gets", "gave", "i", "do", "dont", "does", "doing", "usually", "said", "says", "saw", "say", "saying", "set", "touch", "tocuhing", "stare", "can", "cant", "always", "forever", "now", "today", "tomorrow", "last", "week", "month", "year", "yestarday", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday", "day", "time", "am", "pm", "usually", "unusual", "user", "use", "yo", "turned", "tap", "tan", "turned", "action", "full", "from", "morning", "afternoon", "evening", "night", "yeh", "yah", "yeah", "oh", "aw" ,"aww", "how", "however", "has", "much", "maybe", "depending", "depends", "dependand", "but", "more", "thats", "im", "well", "getting", "prepared", "dont", "doesnt", "same", "and", "description", "no", "if", "these", "happen","happened", "happening" ,"top", "things", "heard", "here", "first", "lets", "just", "not", "join", "about", "as", "we", "down", "our", "will", "for", "latest", "growing", "list", "new", "another", "written", "follow", "gains", "me", "few", "includes", "are", "should", "may", "soon", "change", "since", "article", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twleve", "thirteen", "fourteen", "fiveteen", "sixteen", "seventeen", "eighteen", "nineteen", "twenty", "thrity" ,"fourty", "fifty", "sixty", "seventy" ,"eighty", "ninety", "hundred", "thousand", "million", "billion" ,"mainly", "enough", "visit", "visited", "visiting", "back", "backing", "backed", "featured", "below", "like", "were" ,"theyre", "lot", "every", "very", "thing", "think", "wrong", "say", "agree" ,"everything", "divided", "weekends", "but", "free", "sliding", "while", "posted", "info", "tell", "have", "havent", "having", "watch", "again", "all", "lack", "middle", "top", "left", "right", "bottom", "wont" ,"reveal", "best", "bad", "was", "wasnt", "realised", "part", "only", "been", "double", "subconsciously", "clicked", "fast", "company", "website", "know", "test", "walk", "over", "ready", "looks", "newest", "released", "news", "which", "cant", "according" , "once", "twice", "times", "method", "fall", "under", "minute", "second", "hour", "simple", "takes", "hardly", "any", "anything", "anyone", "anywhere", "anyway", "anyways", "done", "pretty", "tool", "also", "known", "losing", "stream", "popular", "announcing", "announcment", "announced", "sir", "side", "take", "too", "many", "against", "apparently", "updated", "post", "uprising", "stand", "friend", "being", "knocked", "knock", "knocking", "real", "realism", "catches", "movement", "moving", "moved", "bark", "catches", "uncanny", "attention", "look", "looking", "lookout", "check", "out", "held", "whats", "coming", "between", "services", "great", "work", "ago", "some", "examples", "brief", "whether", "name", "idea", "daily", "into", "most", "largest", "somewhere", "largest", "years", "grew", "board", "putting", "put", "puts", "his", "story", "saved", "man", "woman", "show", "really", "led", "increase", "improve", "behind", "increase", "small", "large", "tiny", "big", "massive", "huge", "might", "looking", "constantly", "other", "let", "opening", "finally", "other", "had", "need", "because", "cause", "caused", "didnt", "quite", "would", "quite", "unnecessary", "revealed", "impressively", "make", "makes", "making", "mr", "miss", "mrs", "super", "chance", "those", "shine", "air", "land", "route", "routes", "even", "move", "them", "themselves", "ourselves", "head", "roll", "foot", "means", "whatever", "fine", "working", "colour", "try", "improve", "opportunity", "tend", "feel", "feeling", "try", "heres", "job", "youre", "youd", "comment", "comes", "come", "love", "gender", "still", "matter", "male", "female", "could", "luck", "cheer", "up", "noticed", "owner", "sad", "happy", "brought", "tells", "explains", "likes", "hot", "hottest", "hotter", "cold", "colder", "coldest", "turn", "turned", "turning", "taken", "yourself", "myself", "see", "saw", "seeing", "inside", "after", "discovers", "discovered", "discovering", "disturbing", "low", "high", "often", "protects", "used", "strength", "weight", "plain", "heat", "old", "older", "oldest", "never", "giving", "ranked", "greet", "stranger", "cheek", "giving", "firm", "ever", "whom" ,"comfortable", "touched", "less", "more", "biggest", "big", "bigger", "small", "smallest", "smaller", "large", "largest", "larger", "little", "littlest", "days", "men", "women", "male", "female", "sank", "drank", "thank", "thanks", "plank", "he", "hes", "she", "shes", "her", "him", "own", "buck", "dared", "cared", "scared", "paying", "call", "calls", "called", "phones", "talks", "talk", "talking", "shout", "shouting", "shouted", "wisper", "wispering", "wispered", "alive", "experience", "official", "presentation", "making", "goes", "going", "gonna", "go", "gone", "reported", "reporting", "reports", "report", "demand", "demanding", "demanded", "actually", "actual", "send", "sent", "sending", "sender" , "next", "previous", "using", "used", "use", "uses", "bunch", "munch", "punch", "including" , "include", "included", "uninclude", "giant", "tiny", "small", "large", "enormous", "micro", "offered", "offer", "offering", "offered", "departure", "departed", "departing", "issues", "issues", "issued", "longtime", "long", "detailed", "detail", "details", "explanation", "explain", "explained", "explaning", "passes", "passed", "pass", "passing", "motion", "shared", "sharing", "sharer", "somewhat", "somehow", "somewhere", "someone", "someones", "something", "tools", "tool", "create", "created", "creator", "creating", "nowadays" ,"details", "verdict", "verdicts", "vote", "voting", "voted", "help", "helped", "helping", "helpless", "fit", "fat", "thinks", "think", "thinking", "thought", "thoughts", "safe", "safety", "danger", "dangerous", "status", "check", "checked", "checking", "checker", "warns", "warning", "warned", "pages", "page", "available" ,"availability", "jan", "feb", "mar", "apr", "may", "june", "july", "aug", "sep", "oct", "nov", "dec", "january", "february", "march", "april", "august", "september", "october", "november", "december", "beautiful", "beauty", "pretty", "stunning", "stunner", "stop", "stopping", "stopped", "telling", "told", "tell", "clearly", "clear", "unclear", "unclearly", "far", "forward", "further", "selected", "select", "deselect", "deselected", "posts", "post", "upload", "uploaded", "images", "image", "unbelievably", "unbelievable", "believably", "unbelievable", "off", "on", "insane", "crazy", "work", "worked", "working", "works", "worker", "amazing", "amaze", "amazed", "nicknamed", "nickname", "read", "reading", "wrote", "writing", "give", "gave", "given", "hear", "heard", "hearing", "final", "experienced", "experience", "link", "linked", "bio", "member", "members", "abandoned", "abandon", "abandonment", "current", "currently", "scroll", "scrolling", "scroller", "scrolling", "trip", "trips", "tripping", "tripped", "walls", "wall", "slide", "slider", "sliding", "youll", "normal", "normality", "easy", "easiest", "hard", "hardest", "nice", "nicest", "nicely", "nughty", "horrible", "good", "great", "incredible", "fantastic", "fantastical", "tall", "taller", "tallest", "short", "shorter", "shortest", "tiny", "tiniest", "tinier", "needs", "need", "needed", "needing", "upon", "finds", "found", "finding", "finder", "lost", "loose", "looser", "study", "studying", "studied", "already", "ready", "aka", "recent", "recently", "besides", "beside", "impress", "impressive", "impressed", "skills", "skill", "skilled", "slow", "slower", "slowing", "slowest", "fastest", "fast", "fasted", "faster", "main", "center", "central", "bit", "bite", "stir", "stired", "stiring", "meet", "meeting", "without", "within", "with", "aims", "aims", "designed", "designing", "before", "after", "alter", "simplify", "simple", "interest", "interested", "around", "round", "rounded", "replaced" ,"replace", "replacing", "meant", "mean", "convert", "converted", "converting", "harmless", "harm", "harmful", "harming", "harmed", "harmless", "breaks", "breaking", "broken", "broke", "break", "report", "rumor", "rumors" ,"rumored", "flying", "fly", "flew", "decided", "decide", "deciding", "spare", "sparing", "spared", "suffered", "suffering", "suffer" ,"delusions", "deluded", "control", "controling", "attacking", "attack", "attacked", "started", "starting", "start", "okay", "ok", "alright", "waited", "wait", "hope", "hopeless", "hoping", "hopes", "leave", "leaving", "arrive", "arriving", "arrived", "away", "far", "glad", "catch", "caught", "worries", "worry", "worried", "probably", "exactly", "exact", "later", "late", "earlier", "early", "fall", "fell", "falling", "sort", "sorting", "find", "found", "finding", "outside", "inside", "through", "though", "although", "sort", "sorting", "sorted", "nothing", "nothingness", "least", "atleast", "bring", "bringing", "brought", "able", "disable", "both", "all", "forgot", "forgotton", "forget", "either", "asked", "asking", "ask", "pick", "picked", "until", "heading", "headed", "combine", "combines");
	
}

/*
function tags_upload($tags, $catagory, $channel) {
	$last_tag = count($tags);
	$last_formatted = strtolower($tags[0]);
	$last_query = mysqli_query("SELECT * FROM `trending` ORDER BY `trending_id` DESC LIMIT $last_tag, 1");	
	$last_data = mysqli_fetch_assoc($last_query);
	
	if ($last_data['trending_keyword'] != $tags[$last_tag - 1]) {
		foreach ($tags as $tag) {
			$trending_tag = strtolower($tag);
			$trending_post = mysqli_query("INSERT INTO  `trending` (`trending_id` ,`trending_timestamp` ,`trending_keyword` ,`trending_catagory` ,`trending_user` ,`trending_latitude` ,`trending_longitude`) VALUES (NULL , CURRENT_TIMESTAMP ,  '$trending_tag', '$catagory',  '$user',  '$trending_latitude',  '$trending_longitude');");
			
				
			$trending_expired = date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s') . ' - 4 day'));
			$trending_existing = mysqli_query("SELECT `trending_keyword`, COUNT( * ) AS trending_count FROM `trending` WHERE `trending_timestamp` > '$trending_expired' AND `trending_keyword` LIKE '$trending_tag' HAVING trending_count > 20 ORDER BY COUNT(*) DESC ,trending_timestamp DESC LIMIT 0 , 1");
			$trending_exists = mysqli_num_rows($trending_existing);
			if ($trending_exists > 1) {
				$dictionary_query = mysqli_query("SELECT * FROM  `dictionary` WHERE  `tag_word` LIKE '$trending_tag'AND  `tag_type` LIKE  '$catagory' LIMIT 0 ,1");
				$dictionary_exists = mysqli_num_rows($dictionary_query);
				if ($dictionary_exists == 0) {
					$dictionary_add = mysqli_query("INSERT INTO `dictionary` (`tag_id` ,`tag_word` ,`tag_type` ,`tag_subtype` ,`tag_affiliate` ,`tag_search` ,`tag_auto`) VALUES (NULL, '$trending_tag',  '$catagory', '', '', '1', '1');");
					if ($dictionary_add) $dictionary_added[] = array("tag" => $trending_tag, "catagory" => $catagory);
					
				}
				
				if ($channel == true) tag_channel_create($trending_tag, $catagory);
				
			}
			
		}
		
	}
	
	if (count($dictionary_added) == 0) $dictionary_added = array(); 
	
	return $dictionary_added;
			
}
*/

function tag_channel_create($tag, $catagory) {
	global $database_grado_connect;
		
	$channel_query = mysqli_query($database_grado_connect, "SELECT * FROM `channel` WHERE `channel_title` LIKE '$tag' LIMIT 0 ,1");
	$channel_exists = mysqli_num_rows($channel_query);
	if ($channel_exists == 0) {
		$post_query = mysqli_query($database_gradtag_table, "SELECT * FROM  `content` WHERE `content_tags` LIKE '$tag,' AND `content_tags` LIKE '$catagory,' LIMIT 0 ,50");
		$post_count = mysqli_num_rows($post_query);
		$post_last = mysqli_fetch_assoc($post_query);
		$post_images = rexplode(",", $post_last['content_images']);
		
		if ($post_count > 5) {
			$channel_timestamp = date('Y-m-d H:i:s');
			$channel_title = $tag;
			$channel_tags = $tag . "," . $catagory;
			$channel_header = reset($post_images);
			$channel_description = "An auto generated channel for all things " . ucwords($tag);
			$channel_add = mysqli_query($database_grado_connect, "INSERT INTO `channel` (`channel_id` ,`channel_key` ,`channel_timestamp` ,`channel_updated` ,`channel_title` ,`channel_description` ,`channel_header` ,`channel_tags` ,`channel_type` ,`channel_sources` ,`channel_owner` ,`channel_admins` ,`channel_latitude` ,`channel_longitude` ,`channel_verified` ,`channel_hidden`) VALUES (NULL ,  '',  '$channel_timestamp',  '$channel_timestamp',  '$channel_title',  '$channel_description',  '$channel_header',  '$channel_tags',  'auto',  '',  '$authuser_key',  '',  '',  '',  '0',  '0');");
			
		}
		
	}
	
}

?>