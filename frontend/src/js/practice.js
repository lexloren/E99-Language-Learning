/* practice.js: scripts for practising with flashcards.
 * 
 * 
 */

var URL = "http://cscie99.fictio.us/";
var showWord = true;
var showPronun = false;
var showTrans = false;
var wordList = [];

/* prepare */

$( document ).ready(function() {
	pageSetup();
	setupDoc();
	getLists();
	handleClicks();
});

/* set up document for first use. */

function setupDoc() {
	$('#deck-selection-container').show();
	$('#flashcard-container').hide();
	$('#card-followup-container').hide();
	$("#loader-get-cards").hide();
	$("#loader-get-trans").hide();
}

function populate_word() {
	$('#flashcard-word-panel').html(getWord(wordList[0].words[wordList[0].languages[1]]));	
}

function populate_pronoun() {
	$('#flashcard-pronounce-panel').html(wordList[0].pronuncations[wordList[0].languages[1]]);
}

function populate_trans() {
	$('#flashcard-trans-panel').html(wordList[0].words[wordList[0].languages[0]]);
}

function show_full_hide_empty() {
	if (wordList[0].words[wordList[0].languages[1]] === null) {
		$('#flashcard-word').hide();
	} else {
		$('#flashcard-word').show();
	}
	if (wordList[0].pronuncations[wordList[0].languages[1]] === null) {
		$('#flashcard-pronounce').hide();
	} else {
		$('#flashcard-pronounce').show();
	}
	if (wordList[0].words[wordList[0].words[wordList[0].languages[0]]] === null) {
		$('#flashcard-trans').hide();
	} else {
		$('#flashcard-trans').show();
	}
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
		get_dictionary(this.innerHTML, 1);
	});
		
	/* hide lookup panel */
	$(document).on('click', '.dictionary-close', function () {
		$('#translation-panel').hide();
	});	
	
	/* show hidden cards */
	$('#flashcard-word-button').click(function(event) {
		event.preventDefault();
		populate_word();
	});
	$('#flashcard-pronounce-button').click(function(event) {
		event.preventDefault();
		populate_pronoun();
	});
	$('#flashcard-trans-button').click(function(event) {
		event.preventDefault();
		populate_trans();
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
	wordList.shift();
}

/* request a list of the user's decks from the backend and populate the "select
your decks" form */
function getLists() {

	
	$("#loader-show-lists").show();
	var currentURL = URL + 'user_lists.php';
	$.getJSON( currentURL, function( data ) {
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else if (data.result.length === 0) {
			failureMessage("You don't have any lists to practice with.");
		} else {
			$('#deck-selection-form').html('');
			$.each( data.result, function( ) {
				$('#deck-selection-form').append('<div class="checkbox"><label>' + 
				'<input type="checkbox" name ="wordlist" id="' + this.listId + '"> ' + this.name + ' </label></div>');
			});
			$('#deck-selection-container').show();
			$("#loader-show-lists").hide();
		}
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

/*  */
function get_dictionary(word, page) {
	$('#translation-panel-inner').html('');
	$("#loader-get-trans").show();
	$('#translation-panel').show();
	
	var currentURL = URL + 'entry_find.php';
	var langcodes = wordList[0].languages[0] + ',' + wordList[0].languages[1];
	$.getJSON( currentURL, {
		query : word,
		langs : langcodes,
		page_size : 5,
		page_num : page
	}, function( data ) {
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else {
			$.each( data.result, function() {
			$('#translation-panel-inner').append('<div>' + this.words[this.languages[1]] + ' : ' + this.words[this.languages[0]] +
				' : ' + this.pronuncations[this.languages[1]] + '</div>');
			});
			$('#translation-panel-inner').append('<div><a href="#" id="next_dict">[next page]</a></div>');
			$('#next_dict').on('click', function (event) {
				event.preventDefault();
				get_dictionary(word, page + 1);
			});
		}
		$("#loader-get-trans").hide();
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

/* send user's selected decks to the backend and receive a list of cards to practice with */
function getCards() {

	/* clear old data from the flashcards */
	$('#flashcard-word-panel').html('');
	$('#flashcard-pronounce-panel').html('');
	$('#flashcard-trans-panel').html('');
	
	$('#deck-selection-container').hide();
	$("#loader-get-cards").show();
	
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
	var decks = '';
	for (var i = 0; i < requestedDecks.length - 1; i++) {
		decks = decks + requestedDecks[i] + ',';
	}
	decks = decks + requestedDecks[requestedDecks.length - 1];
	var currentURL = URL + 'user_practice.php';
	$.getJSON( currentURL, {
		list_ids: decks,
		entries_count: 50
		}, function( data ) {
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else {
			wordList = data.result;
			nextCard();
		}
		$("#loader-get-cards").hide();
		$('#flashcard-container').show();
		$('#card-followup-container').show();
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

/* use the first card to populate the "flashcard view", using view preferences that the user specified earlier */
function nextCard() {
	if (wordList.length === 0) {
		practice_complete();
	} else {
		$('#translation-panel-inner').html('Want to know more? Click on a character to find related words.');
		$("#loader-get-trans").hide();
		if (showWord) {
			populate_word();
		} else {
			$('#flashcard-word-panel').html('');
		}
		if (showPronun) {
			populate_pronoun();
		} else {
			$('#flashcard-pronounce-panel').html('');
		}
		if (showTrans) {
			populate_trans();
		} else {
			$('#flashcard-trans-panel').html('');
		}
		show_full_hide_empty();
	}
}

function practice_complete() {
	$("#success").html('Practice complete!');
	$("#success").show();
	getLists();
	$('#deck-selection-container').show();
	$('#flashcard-container').hide();
	$('#card-followup-container').hide();
}

/* send student ratings to the backend */
function send_rating(value) {
	var currentURL = URL + 'user_practice_response.php';
	$.post(currentURL, 
        { 'entry_id' : wordList[0].entryId, 'correctness' : value }, function(data) {
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		}
	})
	.fail(function() {
	failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}