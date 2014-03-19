var URL = "/";
var showWord = true;
var showPronun = false;
var showTrans = false;
var wordList = [];

/* mockjax for testing */
$.mockjax({
  url: 'enumerate_lists.php',
  responseText: {
	'1' : 'Lesson 1: Family', 
	'2' : 'Lesson 2: Animals' 
  }
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

/* prepare */
$( document ).ready(function() {
	setupDoc();
	getLists();
	handleClicks();
});


/* setup document */
function setupDoc() {
	$('#deck-selection-container').show();
	$('#flashcard-container').hide();
	$('#card-followup-container').hide();
};

function handleClicks() {

	/* send selected lists to server, get cards to practice with */
	$('#get-cards').click(function(event) {
		event.preventDefault();
		getCards();
	});
	
	/* rating buttons */
	$('.rating-button').click(function(event) {
		event.preventDefault();
		send_rating(this.value);
	});
	
	/* show hidden cards */
	$('#flashcard-word-button').click(function(event) {
		event.preventDefault();
		$('#flashcard-word-panel').html(wordList[0].word);
	});
	$('#flashcard-pronounce-button').click(function(event) {
		event.preventDefault();
		$('#flashcard-pronounce-panel').html(wordList[0].translation);
	});
	$('#flashcard-trans-button').click(function(event) {
		event.preventDefault();
		$('#flashcard-trans-panel').html(wordList[0].pronunciation);
	});
	
	/* shift the array and get the next card */
	$('#button-get-next').click(function(event) {
		event.preventDefault();
		shiftCards();
	});
	
	/* open menu to select a new deck */
	$('#button-new-deck').click(function(event) {
		event.preventDefault();
		setupDoc();
	});
};

function shiftCards() {
	var temp = wordList[0];
	wordList.shift();
	wordList.push(temp);
	nextCard();
};

/* request a list of the user's decks from the backend and populate the "select
your decks" form */
function getLists() {
	$.getJSON( 'enumerate_lists.php', function( data ) {
		$.each( data, function( key, value ) {
			$('#deck-selection-form').append('<div class="checkbox"><label>' + 
			'<input type="checkbox" name ="wordlist" id="' + key + '"> ' + value + ' </label></div>');
		});
	});
};

/* send user's selected decks to the backend and receive a list of cards to practice with */
function getCards() {

	/* clear old data from the flashcards */
	$('#flashcard-word-panel').html('');
	$('#flashcard-pronounce-panel').html('');
	$('#flashcard-trans-panel').html('');

	
	$('#deck-selection-container').hide();
	$('#flashcard-container').show();
	$('#card-followup-container').show();
	
	if ($('#show-word').prop('checked') == true) {
		showWord = true;
	} else {
		showWord = false;
	}
	if ($('#show-pronounce').prop('checked') == true) {
		showPronun = true;
	} else {
		showPronun = false;
	}
	if ($('#show-trans').prop('checked') == true) {
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
		wordList = data.result;
		nextCard();
	});
};

/* use the first card to populate the "flashcard view", using view preferences that the user specified earlier */
function nextCard() {
	if (showWord) {
		$('#flashcard-word-panel').html(wordList[0].word);
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
};

/* send student ratings to the backend */
function send_rating(value) {
	$.post('insert_result.php', 
        { 'entry_id' : wordList[0].entryId, 'correctness' : value });
};