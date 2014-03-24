/* practice.js: scripts for practising with flashcards.
 * 
 * 
 */

var URL = "/";
var showWord = true;
var showPronun = false;
var showTrans = false;
var wordList = [];

/* mockjax for testing */

$.mockjax({
  url: 'user_lists.php',
  responseText: {
	"isError":false,
	"errorTitle":null,
	"errorDescription":null,
	"result":[
		{"listId" : 1,
		"listName" : "Lesson 1: Family"},
		{"listId" : 2,
		"listName" : "Lesson 2: Animals"}],
	},
});

$.mockjax({
  url: 'practice.php',
  responseText: {
	"isError":false,
	"errorTitle":null,
	"errorDescription":null,
	"result":[{
		"entryId":"1",
		"privileges":"",
		"word":"Word 1",
		"translation":"Translation 1",
		"pronunciation":"Pronunciation 1",
		"userId":null,
		"annotations":[]},
		{"entryId":"2",
		"privileges":"",
		"word":"Word 2",
		"translation":"Translation 2",
		"pronunciation":"Pronunciation 2",
		"userId":null,
		"annotations":[]},
		{"entryId":"3",
		"privileges":"",
		"word":"Word 3",
		"translation":"Translation 3",
		"pronunciation":"Pronunciation 3",
		"userId":null,
		"annotations":[]},],
	"resultInformation":{"entriesCount":2,"pageSize":2,"pageNum":1}} 
});

$.mockjax({
  url: 'query_dictionary.php',
  responseText: {
	"isError":false,
	"errorTitle":null,
	"errorDescription":null,
	"result":[{
		"entryId":"5",
		"word":"Word 5",
		"translation":"Translation 5",
		"pronunciation":"Pronunciation 5"},
		{"entryId":"6",
		"word":"Word 6",
		"translation":"Translation 6",
		"pronunciation":"Pronunciation 6"},
		{"entryId":"7",
		"word":"Word 7",
		"translation":"Translation 7",
		"pronunciation":"Pronunciation 7"}],
	"resultInformation":{"entriesCount":3,"pageSize":1,"pageNum":1}} 
});

/* end mockjax */

/* prepare */

$( document ).ready(function() {
	setupDoc();
	getLists();
	handleClicks();
});

/* set up document for first use. */

function setupDoc() {
	$("#success").hide();
    $("#failure").hide();
    $("#navbar").load("navbar.html");
	$('#deck-selection-container').show();
	$('#flashcard-container').hide();
	$('#card-followup-container').hide();
}

function handleClicks() {

	/* send selected lists to server, get cards to practice with */
	$('#get-cards').click(function(event) {
		event.preventDefault();
		getCards();
	});
	
	/* rating buttons */
	$(".rating-button").click(function(event) {
		event.preventDefault();
		send_rating(this.value);
		shiftCards();
		nextCard();
	});
	
	/* char/word lookup */
	$(document).on('click', '.char-of-word', function () {
		get_dictionary(this.name);
	});
	
	/* hide lookup panel */
	$(document).on('click', '.dictionary-close', function () {
		$('#translation-panel').hide();
	});	

	/* show hidden cards */
	$('#flashcard-word-button').click(function(event) {
		event.preventDefault();
		$('#flashcard-word-panel').html(getWord(wordList[0].word));
	});
	$('#flashcard-pronounce-button').click(function(event) {
		event.preventDefault();
		$('#flashcard-pronounce-panel').html(wordList[0].pronunciation);
	});
	$('#flashcard-trans-button').click(function(event) {
		event.preventDefault();
		$('#flashcard-trans-panel').html(wordList[0].translation);
	});

	/* open menu to select a new deck */
	$('#button-new-deck').click(function(event) {
		event.preventDefault();
		setupDoc();
	});
}

/* split the word into individual characters */
function getWord(word) {
	var myarray = word.split('');
	var newWord = '';
	$.each(myarray, function() {
		newWord = newWord.concat('<span class="char-of-word">' + this + '</span>');
	});
	return newWord;
}

function shiftCards() {
	var temp = wordList[0];
	wordList.shift();
	wordList.push(temp);
}

/* request a list of the user's decks from the backend and populate the "select
your decks" form */
function getLists() {
	$.getJSON( 'user_lists.php', function( data ) {
		if (data.isError === true) {
			console.log(data.errorTitle);
			console.log(data.errorDescription);
		} else {
			$.each( data.result, function( ) {
				$('#deck-selection-form').append('<div class="checkbox"><label>' + 
				'<input type="checkbox" name ="wordlist" id="' + this.listId + '"> ' + this.listName + ' </label></div>');
			});
		}
	})
	 .fail(function() {
		console.log( "error" );
	});
}

/*  */
function get_dictionary(word) {
	$('#translation-panel').show();
	$('#translation-panel-inner').html('');
	$.getJSON( 'query_dictionary.php', {query : word }, function( data ) {
		if (data.isError === true) {
			console.log(data.errorTitle);
			console.log(data.errorDescription);
		} else {
			$.each( data.result, function() {
			$('#translation-panel-inner').append('<div>' + this.word + ' : ' + this.translation +
				' : ' + this.pronunciation + '</div>');
			});
		}
	});
}


/* send user's selected decks to the backend and receive a list of cards to practice with */
function getCards() {

	/* clear old data from the flashcards */
	$('#flashcard-word-panel').html('');
	$('#flashcard-pronounce-panel').html('');
	$('#flashcard-trans-panel').html('');

	
	$('#deck-selection-container').hide();
	$('#flashcard-container').show();
	$('#card-followup-container').show();
	
	if ($('#show-word').prop('checked') === true) {
		showWord = true;
	} else {
		showWord = false;
	}
	if ($('#show-pronounce').prop('checked') === true) {
		showPronun = true;
	} else {
		showPronun = false;
	}
	if ($('#show-trans').prop('checked') === true) {
		showTrans = true;
	} else {
		showTrans = false;
	}

	var requestedDecks = [];
	$("input[name='wordlist'][type='checkbox']:checked").each(function() {
		requestedDecks.push(this.id);
	});
	var string = JSON.stringify(requestedDecks);
	$.getJSON( 'practice.php', string, function( data ) {
		if (data.isError === true) {
			console.log(data.errorTitle);
			console.log(data.errorDescription);
		} else {
			wordList = data.result;
			nextCard();
		}
	});
}

/* use the first card to populate the "flashcard view", using view preferences that the user specified earlier */
function nextCard() {
	if (showWord) {
		$('#flashcard-word-panel').html(getWord(wordList[0].word));
	} else {
		$('#flashcard-word-panel').html('');
	}
	if (showPronun) {
		$('#flashcard-pronounce-panel').html(wordList[0].translation);
	} else {
		$('#flashcard-pronounce-panel').html('');
	}
	if (showTrans) {
		$('#flashcard-trans-panel').html(wordList[0].pronunciation);
	} else {
		$('#flashcard-trans-panel').html('');
	}
}

/* send student ratings to the backend */
function send_rating(value) {
	$.post('insert_result.php', 
        { 'entry_id' : wordList[0].entryId, 'correctness' : value });
}