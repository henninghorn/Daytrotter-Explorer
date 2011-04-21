<?php

function getSessions() {
	$sessions_file = './cache/sessions.html';
	if(((time() - @filectime($sessions_file)) > 3600) || !file_exists($sessions_file)) {
		$url = 'http://www.daytrotter.com/al/artists/alphabetical.html';
		$html = file_get_contents($url);
		file_put_contents($sessions_file, $html);
	} else {
		$html = file_get_contents($sessions_file);
	}

	# Session artist
	$pattern = '/AllArtists.+id="RightNav"/s';
	preg_match_all($pattern, $html, $matches);
	$sessions_html = $matches[0][0];

	$pattern = '/\<h3\>(.+)\<\/h3\>/';
	preg_match_all($pattern, $sessions_html, $matches);
	$sessions_artists = $matches[1];

	# Session links
	$pattern = '/\/dt\/.+\.html/';
	preg_match_all($pattern, $sessions_html, $matches);	
	$sessions_links = $matches[0];
	
	$howmany = count($sessions_artists);
	for($i = 0; $i < $howmany; $i++) {
		$all_sessions[] = array(
			'artist'=> $sessions_artists[$i],
			'link'	=> $sessions_links[$i]);
	}
	
	shuffle($all_sessions);

	for($i = 0; $i < 7; $i++) {
		$pattern = '/([0-9]+\-[0-9]+)/';
		preg_match($pattern, $all_sessions[$i]['link'], $matches);
		$artist = $all_sessions[$i]['artist'];
		$id = $matches[0];
		$cover = 'http://concerts.wolfgangsvault.com/images/concerts/' . $id . '.jpg';
		
		$sessions[] = array(
			'artist'=> $artist,
			'cover' => $cover,
			'id'	=> $id
			);
	}
	
	return $sessions;
}

function getSession($session) {
	$session_file = './cache/' . $session . '.html';
	$url = 'http://www.daytrotter.com/dt/daytrotter-explorer/' . $session . '.html';
	if(/*((time() - @filectime($session_file)) > 3600) || */!file_exists($session_file)) {
		$html = file_get_contents($url);
		file_put_contents($session_file, $html);
	} else {
		$html = file_get_contents($session_file);
	}
	
	if(!$html) {
		header('HTTP/1.0 404 Not Found');
		exit();
	}
	
	# Artist, title and date
	$pattern = '/\<title\>(.+)\<\/title>/s';
	preg_match($pattern, $html, $matches);
	$title = explode(':', $matches[1]);
	
	$artist = trim($title[0]);
	$title = trim($title[1]);
	
	$title = explode(' recorded ', $title);
	$date = $title[1];
	$title = $title[0];
	
	# Tracks
	$pattern = '/RightNav.+"noformat"\>.+\<li\>first.+/s';
	preg_match_all($pattern, $html, $matches);
	$tracks_html = $matches[0][0];
	
	# Tracks titles
	$pattern = '/"\>(.+)\<\/h4\>/';
	preg_match_all($pattern, $tracks_html, $matches);
	$track_titles = $matches[1];
	
	# Tracks MP3s
	$pattern = '/http.+\/dt\/.+\.mp3/';
	preg_match_all($pattern, $tracks_html, $matches);
	$track_mp3s = $matches[0];
	
	$howmany = count($track_titles);
	
	for($i = 0; $i < $howmany; $i++) {
		$tracks[] = array(
			'title' => $track_titles[$i],
			'mp3'	=> $track_mp3s[$i]
			);
	}
	
	$session = array(
		'artist' => $artist,
		'title' => $title,
		'date' => $date,
		'tracks' => $tracks,
		'url' => $url
		);
	
	return $session;
}

function updateHistory($data) {
	$_SESSION['daytrotter_history'][] = $data;
}

?>