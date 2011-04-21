<?php

session_start();

if($_SERVER['QUERY_STRING'] == 'reset') {
	unset($_SESSION['daytrotter_history']);
	header('Location: ./');
	exit();
}

?>
<!DOCTYPE html>
<head>
	<meta charset="utf-8"/>
	<title>Daytrotter Explorer</title>
	<link rel="stylesheet" href="inc/stylesheet.css" type="text/css" media="screen"/>
	<script type="text/javascript" src="http://www.google.com/jsapi"></script>	
	<script type="text/javascript">
		google.load("jquery", "1.4.2");
	</script>
	<script src="inc/audio-player/audio-player.js" type="text/javascript"></script>
	<script src="inc/javascript.js" type="text/javascript"></script>
</head>

<body>

<div id="page">
	<div id="header">
		<h1>Daytrotter Explorer</h1>
	</div>
	<div id="sessions"></div>
	<div id="session">
		<div id="cover"></div>
		<div id="info">
			<h2></h2>
			<div id="title"></div>
			<div id="playerWrapper"><div id="player"></div></div>
			<ol></ol>			
			<div id="daytrotter_link"></div>
		</div>
	</div>
	<div id="extra">
		<div id="history">
			<h3>Listning history</h3>
			<a href="?reset" id="reset">(reset)</a>
			<ol>
				<?php
				$tracks = @array_reverse($_SESSION['daytrotter_history']);
				if(count($tracks) > 0) {
					foreach($tracks as $track) {
						$html .= '<li><a href="' . $track['mp3'] . '" rel="' . $track['share'] . '">' . stripslashes($track['artist']) . ' - ' . stripslashes($track['title']) . '</a></li>';
					}
					print $html;
				}
				?>
			</ol>
		</div>
		<div id="about">
			<h3>About Daytrotter Explorer</h3>
			<p>
				This web application is build by <a href="http://henninghorn.dk">Henning Horn</a>.<br />
				It fetches random <a href="http://www.daytrotter.com/al/artists/alphabetical.html" target="_blank">Daytrotter Sessions</a> and gives you the opportunity to discover new artists, in a slightly <span class="strike">easier</span> faster way, than on Daytrotter's website.
			</p>
			<p>
				If you want to download a track, you simply right-click on the track and select "Save destination/link...".<br />
				You can also share the current playing track, by copying the link in the address bar of your browser.
			</p>
			<p>
				Daytrotter Explorer has no affiliation with <a href="http://www.daytrotter.com/" target="_blank">Daytrotter</a>.
			</p>
			<p>
				Made possible with <a href="http://jquery.com" target="_blank">jQuery</a> and <a href="http://wpaudioplayer.com/" target="_blank">Audio Player</a>.
			</p>
		</div>
	</div>
</div>

</body>
</html>
