<?php
/* Gets sessions */
function getSessions() {
	/* Sessions cache file */
	$sessions_file = './cache/sessions.tmp';
	
	/* If the cache file is over an hour old or it doesn't exists, it is updated */
	if(((time() - @filectime($sessions_file)) > 3600) || !file_exists($sessions_file)) {
		$url = 'http://www.daytrotter.com/al/artists/alphabetical.html';
		$html = @file_get_contents($url);
		
		/* Strips relevant html from Daytrotter */
		$pattern = '/SessionArchivesChronological.+id="RightNav"/s';
		preg_match_all($pattern, $html, $matches);
		$sessions_html = $matches[0][0];

		/* Fetches artist names */
		$pattern = '/\<b\>(.+)\<\/b\>/';
		preg_match_all($pattern, $sessions_html, $matches);
		$sessions_artists = array_reverse($matches[1]);
		
		/* Removes irrelevant data - "daytrotter session archives sorted alphabetically" */
		unset($sessions_artists[count($sessions_artists) - 1]);

		/* Fetches session ids */
		$pattern = '/\/dt\/.+\/([0-9]+\-[0-9]+)\.html/';
		preg_match_all($pattern, $sessions_html, $matches);	
		$sessions_ids = array_reverse($matches[1]);

		/* Pair artist with session id */
		$howmany = count($sessions_artists);
		for($i = 0; $i < $howmany; $i++) {
			$all_sessions[] = array(
				'artist'=> $sessions_artists[$i],
				'id'	=> $sessions_ids[$i]);
		}
		
		/* Saves the data to the cache file, as a php serialized array object */
		file_put_contents($sessions_file, serialize($all_sessions));		
	} else {
		/* Gets the serialized php array object from the cache file */
		$all_sessions = unserialize(file_get_contents($sessions_file));
	}
	
	/* Shuffles the sessions */
	shuffle($all_sessions);

	/* Selects 7 random sessions */
	for($i = 0; $i < 7; $i++) {
		$artist = $all_sessions[$i]['artist'];
		$id = $all_sessions[$i]['id'];
		$cover = 'http://images.daytrotter.com/concerts/' . $id . '.jpg';
		
		$sessions[] = array(
			'artist'=> $artist,
			'cover' => $cover,
			'id'	=> $id
			);
	}
	
	return $sessions;
}

/* gets the given session */
function getSession($session) {
	/* Location of the session's cache file */
	$session_file = './cache/' . $session . '.tmp';
	
	/* The cache file is used, if its exists */
	if(!file_exists($session_file)) {
		$url = 'http://www.daytrotter.com/dt/daytrotter-explorer/' . $session . '.html';
		$html = @file_get_contents($url);
	} else {
	 	return unserialize(file_get_contents($session_file));
		exit();
	}
	
	/* If the session isn't found */
	if(!$html) {
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	
	/* Fetches the artist name */
	$pattern = '/\<h1\>(.+)\<\/h1\>/';
	preg_match($pattern, $html, $matches);
	$artist = $matches[1];
	
	/* Fetches the session title */
	$pattern = '/\<h2\>(.+)\<\/h2\>/';
	preg_match($pattern, $html, $matches);
	$title = $matches[1];
	
	/* Fetches the session date */
	$pattern = '/\<h3\>(.+)\<\/h3\>/';
	preg_match($pattern, $html, $matches);
	$date = $matches[1];
	
	/* Fetches relevant tracks html */
	$pattern = '/SessionPlayers.+id="Comments"/s';
	preg_match_all($pattern, $html, $matches);
	$tracks_html = $matches[0][0];
	
	/* Fetches track titles */
	$pattern = '/Track\(\d+,\ "(.+)",\ "(.+)",\ \d+\)/';
	preg_match_all($pattern, $tracks_html, $matches);
	$track_titles = $matches[1];
	
	/* Fetches mp3 IDs (due to new urls :( ) */
	$pattern = '/,\ (\d+)\);/';
	preg_match_all($pattern, $tracks_html, $matches);
	$mp3_ids = $matches[1];
	
	foreach($mp3_ids as $mp3_id) {
		$track_mp3s[] = 'http://media.daytrotter.com/audio/96/' . $mp3_id . '.mp3';
	}
	
	#Deprecated due to new url schema at Daytrotter.com - Album cover is not included in the mp3s any longer :(
	/* Generates mp3 URLs */
	#foreach($track_titles as $track_title) {		
	#	$track_mp3s[] = mp3File($artist . ' ' . $track_title);
	#}
	
	/* Generates tracks array with title and mp3 url */
	$howmany = count($track_titles);	
	for($i = 0; $i < $howmany; $i++) {
		$tracks[] = array(
			'title' => $track_titles[$i],
			'mp3'	=> $track_mp3s[$i]
			);
	}
	
	/* Prepares the session array */
	$session = array(
		'artist' => $artist,
		'title' => $title,
		'date' => $date,
		'tracks' => $tracks,
		'url' => $url
		);
	
	/* Saves the session data to the session's cache file */
	file_put_contents($session_file, serialize($session));
	
	/* Returns the session */
	return $session;
}

/* 
updateHistory($data);
data: Array with track info (artist, track title, mp3 url)
- Adds currently playing to user session history
*/
function updateHistory($data) {
	$_SESSION['daytrotter_history'][] = $data;
}

/*
mp3File($string);
string: String to be "converted" to valid mp3 url

Warning!
- Probably only works with session prior March 10th 2010, due to Daytrotter system changes...
*/
function mp3File($string) {
	$charsToNothing = array('\'', '(', ')', '[', ']', '!', '?', '#', ',', '...', ':');
	$charsToHyphen = array(' ', '/', '.');
	$string = strtolower($string);
	$string = preg_replace('/d&w/', 'd-and-w', $string); # husband&wife = husband-and-wife
	$string = preg_replace('/\.$/', '', $string);
	$string = str_replace($charsToNothing, '', $string);
	$string = str_replace($charsToHyphen, '-', $string);
	$string = str_replace(array('---', '--', '----'), '-', $string);
	$string  = str_replace(array('&amp;', '&'), 'and', $string);
	
	return 'http://media.daytrotter.com/audio/dt/' . $string . '.mp3';
}

?>