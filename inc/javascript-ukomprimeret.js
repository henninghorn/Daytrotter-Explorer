/*
Daytrotter Explorer
Javascript developed by Henning Horn - http://henninghorn.dk
Produced: March 2010
*/

/*
getSessions()
This function fetches random sessions
*/
function getSessions() {
	ajaxSpinner('show');
	
	$.ajax({
		url: './ajax.php?getSessions',
		dataType: 'json',
		timeout: 7500, /* We're not a second more patient! */
		success: function(response){
			/* Empties the session bar */			
			$('#sessions').html('');
			
			/* Add each session to the session bar (<div id="sessions">) */
			$.each(response.sessions, function(i, item){
				$('#sessions').append('<img src="' + item.cover + '" id="' + item.id + '" alt="' + item.artist + '" class="cover"/>');
			});
			
			/* Checks whether or not the user entered through a hash tag or not */
			if(window.location.hash && !$.daytrotterID) {
				/* If so, the session in the hash is played */
				var hash = window.location.hash.replace(/#/, '');
				var sessionID = hash;
			} else {
				/* If not, the first session of the random sessions is played */
				var sessionID = response.sessions[0].id;
			}
			
			/* Loads the session */
			getSession(sessionID);

			/* Adds click event: By clicking on a session, the session is played */
			$('#sessions img').click(function(){
				/* Checks if the current session is clicked on again */
				if(this.id == $.daytrotterID) {
					alert('You\'re already viewing this session');
				} else {
					getSession(this.id);
				}
			});
			
			/* Session-hovering effect and changes the text of <h1> */
			$('#sessions img').hover(function(){
				$('h1').html(this.alt);
				$(this).css({opacity: 0.5});
			});
			
			/* Session-hovering effect and changes the text of <h1> */
			$('#sessions img').mouseout(function(){
				$('h1').html('Daytrotter Explorer');
				$(this).css({opacity: 1});
			});
		},
		error: function(){
			/* Triggers when the request is timed out */
			alert('Timed out... Please try again.');
		},
		complete: function() {
			/* When the request is completed, successful or not, the spinner-gif is removed */
			ajaxSpinner('hide');
		}
	});
}

function getSession(id) {
	ajaxSpinner('show');
	
	/* retryID is used, when the request times out and the user decides to try again */
	var retryID = id;
	
	/* The argument 'id' is split into an array */
	var hash = id.split('-');
	id = hash[0] + '-' + hash[1];
	
	/* The cover image source URL */
	var coverArt = 'http://images.daytrotter.com/concerts/320/' + id + '.jpg';
	
	/* Removes the selected class from the previous viewed session */
	if($.daytrotterID) {
		$('#' +	$.daytrotterID).removeClass('selected');
	}
	
	/* Adds spinner to the cover container */
	$('#session #cover').addClass('loading');
	
	/* Adds selected class to the session being viewed  in the session */
	$('#' + id).addClass('selected');
	
	/* Sets the id of the session to a global variable */
	$.daytrotterID = id;
	
	$.ajax({
		url: './ajax.php?getSession',
		data: {session: id},
		dataType: 'json',
		timeout: 7500,
		type: 'POST',
		success: function(response){
			/* Take a good guess.. */
			ajaxSpinner('hide');
			
			/* Adds cover art with a 1.5-second fade animation! */
			$('#session #cover').html('<img src="' + coverArt + '" alt="' + response.artist + '" title="' + response.artist + '" style="display:none;"/>');
			$('#session #cover img').fadeIn(1500, function(){
				/* Removes spinner (Really useful for horizontal cover art) */
				$('#session #cover').removeClass('loading');
			});
			
			/* Set the session info, artist + date + session title */
			$('#session h2').html(response.artist);
			$('#session #title').html(response.date + ': ' + response.title);
			
			/* Empties track list */
			$('#session ol').html('');
			
			/* Fills the track list */
			$.each(response.tracks, function(i, item){
				$('#session ol').append('<li><a href="' + item.mp3 + '" title="' + item.title + '" rel="' + (i + 1) + '">' + item.title + '</a></li>');
			});
			
			/* Adds odd/even colouring to the track list */
			$('#session ol li:odd').css({background: '#eee'});
			
			/* Adds click functionality to each track, so the player starts by click the track title */
			$('#session ol a').click(function(){
				createPlayer(this.href, this.title, this.rel);
				return false;
			});
			
			/* Why '(i + 1)' and '- 1'? It makes more sense, visually, when you look at the URL	*/
			if(hash[2]) {
				var track = hash[2] - 1;
			} else {
				var track = 0;
			}
			
			/* Saves session tracks array into global variable */
			$.daytrotterTracks = response.tracks;
			
			/* Checks whether or not the given track exists in the session */
			if(track > (response.tracks.length - 1)) {
				$.daytrotterHash = window.location.hash;
				alert('Invalid track number');
			} else {
				createPlayer(response.tracks[track].mp3, response.tracks[track].title, track + 1);
			}
			
			/* Sets the Daytrotter article link */
			$('#session #daytrotter_link').html('<a href="' + response.url + '" target="_blank">Read the Daytrotter article</a>');
		},
		error: function(response, status){
			/* If the request exceeds 7.5 seconds, the following is triggered */
			if(status == 'timeout') {
				tryAgain = confirm('Request timeout... Try again?');
				if(tryAgain) {
					getSession(retryID);
				}
			} else {
				/* If the sessions isn't found */
				alert('The session was not found. Try another one.');
			}
		},
		complete: function() {
			/* Wonder what this does... */
			ajaxSpinner('hide');
		}
	});
}

/*
createPlayer(mp3, title, track);
mp3: URL to mp3-file
title: Track title
track: The track number of the session - used in the window's hash
*/
function createPlayer(mp3, title, track) {
	/* Sets the window's hash */
	window.location.hash = $.daytrotterID + '-' + track;
	
	/* The window's hash is stored in a global variable */
	$.daytrotterHash = window.location.hash;
	
	/* Fetches the artist */
	artist = $('#session #info h2').html();
	
	/* Sets the documents title */
	document.title = 'Daytrotter Explorer: ' + artist.replace(/amp;/, '') + ' - ' + title;
	
	/* Controls the '(playing) text */
	$('#playingIndicator').remove();
	$('#session ol a[rel="' + track + '"]').after('<span id="playingIndicator">(playing)</span>');
	
	/* Fixes the artist and title - fx. ',' and '&' can't be displayed in the player */
	artist = fixChars(artist);
	title = fixChars(title);
	
	/* Creates the flash mp3 player */
	AudioPlayer.embed('player', {soundFile: mp3, artists: artist, titles: title, autostart: 'yes'});
	
	/* Prepares the listning history data */
	var data = {
		artist: artist,
		title: title,
		mp3: mp3,
		sessionID: $.daytrotterID,
		share: window.location.hash.replace(/#/, '')
	};
	
	/* Posts the data */
	$.ajax({
		url: 'ajax.php?updateHistory',
		data: data,
		type: 'POST',
	});
	
	/* Adds the track to the history list */
	$('#history ol').prepend('<li><a href="' + data.mp3 + '" rel="' + data.share + '">' + data.artist + ' - ' + data.title + '</a></li>');
	
	/* Read historyPlaylist(); */
	historyPlaylist();
}

/*
fixChars(string);
string: String to be converted to audio-player compatible string
*/
function fixChars(string) {
	string = string.replace(/,/, '').replace(/&amp;/, 'and');
	return string;
}

/*
ajaxSpinner(action)
action: Show or hide
*/
function ajaxSpinner(action) {
	if(action == 'show') {
		$('h1').addClass('loading');
	} else {
		$('h1').removeClass('loading');
	}
}

/*
historyPlaylist();
- Adds odd-even colouring to listning history
- Adds click functionality
*/
function historyPlaylist() {
	$('#history ol li:odd').css({background: '#eee'});
	$('#history ol li:even').css({background: '#fff'});
	
	/* Removes current click event */
	$('#history ol li a').unbind('click');
	$('#history ol li a').click(function(){
		var hash = this.rel.split('-');
		playSessionTrack(hash);
		return false;
	});
}

/*
hashObserve();
Observes the window's hash
If the user clicks the browser's back-button,
the application registers, and the given session and track is played
*/
function hashObserve() {
	if($.daytrotterHash) {
		if($.daytrotterHash != window.location.hash && window.location.hash != '') {
			var hash = window.location.hash.replace(/#/, '').split('-');
			playSessionTrack(hash);
		}
	}
	setTimeout('hashObserve()', 500);
}

/*
playSessionTrack(hash);
hash: Session ID + opt. track number
*/
function playSessionTrack(hash) {
	var currentID = hash[0] + '-' + hash[1];
	var track = hash[2] - 1;
	
	/* If the requested track is in the current session, the track is selected from the $.daytrotterTracks array */
	/* Used to prevent unnecessary ajax call */
	if(currentID == $.daytrotterID) {
		createPlayer($.daytrotterTracks[track].mp3, $.daytrotterTracks[track].title, track + 1);
	} else {
		getSession(hash[0] + '-' + hash[1] + '-' + hash[2]);
	}
}

/* 
Daytrotter Explorer initializing
- Gets random sessions
- AudioPlayer init
*/

$(document).ready(function(){
	getSessions();	
	AudioPlayer.setup(
		'./inc/audio-player/player.swf',
		{width: 620, animation: 'no'}
		);
	
	/* Come on, take a good guess! */
	$('h1').click(function(){
		getSessions();
	});
	
	$('h1').hover(function(){
		$(this).html('Click here to get new random sessions');
	});
	
	$('h1').mouseout(function(){
		$(this).html('Daytrotter Explorer');
	});
	
	historyPlaylist();
	hashObserve();
});