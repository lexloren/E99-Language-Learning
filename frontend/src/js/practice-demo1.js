/* practice.js: scripts for practising with flashcards.
 * 
 * 
 */

var URL = "http://cscie99.fictio.us/";
var cardFrontUp = true;

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
	
	/* flip flashcard */
	$('#flashcard-flip').click(function(event) {
		event.preventDefault();
		flip_card();
	});

	/* open menu to select a new deck */
	$('#button-new-deck').click(function(event) {
		event.preventDefault();
		setupDoc();
	});
	
	/* card selection radio buttons */
	$('#side1-word').on('change', function () {
		$('#side2-word').attr("disabled", true);
		$('#side2-word').removeAttr('checked');
		$('#side2-pronounce').removeAttr("disabled");
		$('#side2-trans').removeAttr("disabled");
	});	
	
	$('#side1-pronounce').on('change', function () {
		$('#side2-pronounce').attr("disabled", true);
		$('#side2-pronounce').removeAttr('checked');
		$('#side2-word').removeAttr("disabled");
		$('#side2-trans').removeAttr("disabled");
	});	
	
	$('#side1-trans').on('change', function () {
		$('#side2-trans').attr("disabled", true);
		$('#side2-trans').removeAttr('checked');
		$('#side2-pronounce').removeAttr("disabled");
		$('side2-word').removeAttr("disabled");
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
	
	$('#deck-selection-container').hide();
	$("#loader-get-cards").show();

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
		
		var word = getWord(wordList[0].words[wordList[0].languages[1]]);
		var pronun = wordList[0].pronuncations[wordList[0].languages[1]];
		var trans = wordList[0].words[wordList[0].languages[0]];
		var secondanswer = false;
		
		/* populate side 1 */
		if ($('#side1-word').prop('checked') === true) {
			populate_side1(word);
		}
		else if ($('#side1-pronounce').prop('checked') === true) {
			populate_side1(pronun);
		}
		else if ($('#side1-trans').prop('checked') === true) {
			populate_side1(trans);
		}
		
		/* populate side 2 */
		if ($('#side2-word').prop('checked') === true) {
			populate_2a(word);
			secondanswer = true;
		}
		if (($('#side2-pronounce').prop('checked') === true) && (pronun != null)) {
			if (secondanswer != true) {
				populate_2a(pronun);
				secondanswer = true;
			} else {
				populate_2b(pronun);
			}
		}
		if (($('#side2-trans').prop('checked') === true) && (trans != null)) {
			if (secondanswer != true) {
				populate_2a(trans);
			} else {
				populate_2b(trans);
			}
		}
		
		$('#flashcard-side1').show();
		$('#flashcard-2a').hide();
		$('#flashcard-2b').hide();
		cardFrontUp = true;
	}
}

function populate_side1(string) {
	$('#flashcard-side1').html(string);
}

function populate_2a(string) {
	$('#flashcard-2a').html(string);
	$('#flashcard-2b').html('');
}

function populate_2b(string) {
	$('#flashcard-2b').html('<hr/>');
	$('#flashcard-2b').append(string);
}

function flip_card() {
	if (cardFrontUp == true) {
		$('#flashcard-side1').hide();
		$('#flashcard-2a').show();
		$('#flashcard-2b').show();
		cardFrontUp = false;
	} else { 
		$('#flashcard-side1').show();
		$('#flashcard-2a').hide();
		$('#flashcard-2b').hide();
		cardFrontUp = true;
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