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
		"entryId":836492,
		"privileges":"",
		"word":"\u8acb",
		"translation":"ask",
		"pronuncation":"qing3 \/ \u30bb\u30a4 \/ \u30b7\u30f3 \/ \u30b7\u30e7\u30a6 \/ \u3053.\u3046 \/ \u3046.\u3051\u308b",
		"userId":null,
		"annotations":[]},
		{"entryId":82,
		"privileges":"",
		"myRating":"",
		"word":"\u8acb 2",
		"translation":"ask 2",
		"pronunciation":"~~~~",
		"annotations":[]}],
	"resultInformation":{"entriesCount":452,"pageSize":452,"pageNum":1}} 
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
	$('#get-cards').click(function(event) {
		event.preventDefault();
		getCards();
	});

}

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
	$('#deck-selection-container').hide();
	$('#flashcard-container').show();
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

/* show the hidden part of a card when the appropriate button is clicked */




/* send student ratings to the backend */