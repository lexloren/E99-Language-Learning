/* practice.js: scripts for practising with flashcards.
 * 
 * 
 */

var URL = "http://cscie99.fictio.us/";
var wordList = [];
var qword = false;
var qpronun = false;
var qtrans = false;
var aword = false;
var apronun = false;
var atrans = false;
var iword = false;
var ipronun = false;
var itrans = false;

var listsURL = URL + 'user_lists.php';
var practiceURL = URL + 'user_practice.php';
var dictionaryURL = URL + 'entry_find.php';


$.mockjax({
  url: listsURL,
  responseText: {
	"isError":false,
	"errorTitle":null,
	"errorDescription":null,
	"result":[
		{"listId" : 1,
		"name" : "Lesson 1: Family"},
		{"listId" : 2,
		"name" : "Lesson 2: Animals"}],
	},
});

$.mockjax({
  url: practiceURL,
  responseText: {
	"isError":false, 
	"errorTitle":null,
	"errorDescription":null,
	"result":[
	{
		"entryId":12003,
		"languages":["en","jp"],
		"owner":{
			"userId":6,
			"isSessionUser":true,
			"handle":"practitioner",
			"email":"lloren@gmail.com",
			"nameGiven":"","nameFamily":""
		},
		"words":{
			"en":"(n) toughness (of a material)",
			"jp":"\u3058\u3093\u6027"
		},
		"pronuncations":{
			"jp":"\u3058\u3093\u305b\u3044"
		}
	},
	{
		"entryId":28,
		"languages":["en","jp"],
		"owner":{
			"userId":6,
			"isSessionUser":true,
			"handle":"practitioner",
			"email":"lloren@gmail.com",
			"nameGiven":"","nameFamily":""
		},
		"words":{
			"en":"fastening",
			"jp":"\u3006"
		},
		"pronuncations":{
			"jp":"\u3057\u3081"
		}
	},
	{
		"entryId":50234,
		"languages":["en","jp"],
		"owner":{
			"userId":6,
			"isSessionUser":true,
			"handle":"practitioner",
			"email":"lloren@gmail.com",
			"nameGiven":"","nameFamily":""
		},
		"words":{
			"en":"(n) (comp) Gopher",
			"jp":"\u30b4\u30fc\u30d5\u30a1\u30fc"
		},
		"pronuncations":{
			"jp":null
		}
	}],"resultInformation":null}
});

$.mockjax({
  url: dictionaryURL,
  responseText: {"isError":false,"errorTitle":null,"errorDescription":null,"result":[{"entryId":840521,"languages":["en","jp"],"words":{"en":"melt","jp":"\u878d"},"pronuncations":{"jp":"rong2 \/ \u30e6\u30a6 \/ \u3068.\u3051\u308b \/ \u3068.\u304b\u3059"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":840520,"languages":["en","jp"],"words":{"en":"dissolve","jp":"\u878d"},"pronuncations":{"jp":"rong2 \/ \u30e6\u30a6 \/ \u3068.\u3051\u308b \/ \u3068.\u304b\u3059"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":421001,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u901a"},"pronuncations":{"jp":"\u3086\u3046\u305a\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420978,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u5408"},"pronuncations":{"jp":"\u3086\u3046\u3054\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420983,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u8cc7"},"pronuncations":{"jp":"\u3086\u3046\u3057"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":421048,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u548c"},"pronuncations":{"jp":"\u3086\u3046\u308f"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":174529,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u91d1\u878d"},"pronuncations":{"jp":"\u304d\u3093\u3086\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420982,"languages":["en","jp"],"words":{"en":"loan","jp":"\u878d\u8cc7"},"pronuncations":{"jp":"\u3086\u3046\u3057"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420974,"languages":["en","jp"],"words":{"en":"fusion","jp":"\u878d\u5408"},"pronuncations":{"jp":"\u3086\u3046\u3054\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":423412,"languages":["en","jp"],"words":{"en":"fusion","jp":"\u7194\u878d"},"pronuncations":{"jp":"\u3088\u3046\u3086\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":423377,"languages":["en","jp"],"words":{"en":"fusion","jp":"\u6eb6\u878d"},"pronuncations":{"jp":"\u3088\u3046\u3086\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":421009,"languages":["en","jp"],"words":{"en":"finance","jp":"\u878d\u901a"},"pronuncations":{"jp":"\u3086\u305a"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420977,"languages":["en","jp"],"words":{"en":"uniting","jp":"\u878d\u5408"},"pronuncations":{"jp":"\u3086\u3046\u3054\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":421015,"languages":["en","jp"],"words":{"en":"finance","jp":"\u878d\u901a"},"pronuncations":{"jp":"\u3086\u305a\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":421003,"languages":["en","jp"],"words":{"en":"finance","jp":"\u878d\u901a"},"pronuncations":{"jp":"\u3086\u3046\u3065\u3046"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":false,"execute":false}}],"resultInformation":{"pageSize":15,"pageNumber":1,"pagesCount":17,"entriesFoundCount":255}} 
});

/* end mockjax */



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
	});
	
	/* char/word lookup */
	$(document).on('click', '.char-of-word', function () {
		get_dictionary(this.innerHTML, 1);
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
	
	$(document).keypress(function(e) {
		if(e.which == 13) {
			flip_card();
		}
		if(e.which == 49) {
			send_rating(1);
		}
		if(e.which == 50) {
			send_rating(2);
		}
		if(e.which == 51) {
			send_rating(3);
		}
		if(e.which == 52) {
			send_rating(4);
		}
		if(e.which == 53) {
			send_rating(5);
		}
	});
}

/* split the word into individual characters */
function getWord(word) {
	if (word == null) return null;
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

/* returns true if options are ok, false if problem */
function readOptions() {
	qword = false;
	qpronun = false;
	qtrans = false;
	aword = false;
	apronun = false;
	atrans = false;
	iword = false;
	ipronun = false;
	itrans = false;
	
	var showThird = $('#side3').prop('checked');
	/* get checked options, removing invalid ones */
	if ($('#side1-word').prop('checked') === true) {
		$('#side2-word').removeAttr('checked');
		qword = true;
	}
	else if ($('#side1-pronounce').prop('checked') === true) {
		$('#side2-pronounce').removeAttr('checked');
		qpronun = true;
	}
	else if ($('#side1-trans').prop('checked') === true) {
		$('#side2-trans').removeAttr('checked');
		qtrans = true;
	}	
	if ($('#side2-word').prop('checked') === true) {
		aword = true;
	}
	else if ($('#side2-pronounce').prop('checked') === true) {
		apronun = true;
	}	
	else if (($('#side2-trans').prop('checked') === true)) {
		atrans = true;
	}
	
	/* get mode from selection */
	if (qword) {
		if (apronun) {
			mode = 2;
			if (showThird) {
				itrans = true;
			}
		}
		else if (atrans) {
			mode = 0;
			if (showThird) {
				ipronun = true;
			}
		}
	} else if (qpronun) {
		if (aword) {
			mode = 4;
			if (showThird) {
				itrans = true;
			}
		}
		else if (atrans) {
			mode = 3;
			if (showThird) {
				iword = true;
			}
		}
	} else if (qtrans) {
		if (apronun) {
			mode = 1;
			if (showThird) {
				iword = true;
			}
		}
		else if (aword) {
			mode = 5;
			if (showThird) {
				ipronun = true;
			}
		}
	} else {
		return false;
	}
	return true;
}

/* send user's selected decks to the backend and receive a list of cards to practice with */
function getCards() {
	
	if ( readOptions() == false) {
		failureMessage('Please select a question and answer.');
	}
	var requestedDecks = [];
	$("input[name='wordlist'][type='checkbox']:checked").each(function() {
		requestedDecks.push(this.id);
	});
	if (requestedDecks.length == 0) {
		failureMessage('Please select at least one deck.');
			return;
	}	
	var cardsReturned = $('#numCards').val();
	if  (!($.isNumeric(cardsReturned)) || (cardsReturned < 1)) {
		failureMessage('Invalid number of words.');
		return;
	}
	
	$('#deck-selection-container').hide();
	$("#loader-get-cards").show();
	
	var decks = '';
	for (var i = 0; i < requestedDecks.length - 1; i++) {
		decks = decks + requestedDecks[i] + ',';
	}
	decks = decks + requestedDecks[requestedDecks.length - 1];
	var currentURL = URL + 'user_practice.php';
	$.getJSON( currentURL, {
		list_ids: decks,
		entries_count: cardsReturned
		}, function( data ) {
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else {
			wordList = data.result;
			$('#flashcard-container').show();
			$('#card-followup-container').hide();
			nextCard();	
		}
		$("#loader-get-cards").hide();
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

/* use the next card to populate the "flashcard view", using view preferences that the user specified earlier */
function nextCard() {
	$('#card-followup-container').hide();
	if (wordList.length === 0) {
		practice_complete();
	} else {
		var word = getWord(wordList[0].words[wordList[0].languages[1]]);
		var pronun = getWord(wordList[0].pronuncations[wordList[0].languages[1]]);
		var trans = getWord(wordList[0].words[wordList[0].languages[0]]);
		
		/* populate side 1 */
		if (qword) {
			$('#flashcard-side1').html(word);
		}
		else if (qpronun) {
			$('#flashcard-side1').html(pronun);
		}
		else if (qtrans) {
			$('#flashcard-side1').html(trans);
		}
		
		/* populate side 2 */
		if (aword) {
			$('#flashcard-2a').html(word);
		}
		if (apronun) {
			$('#flashcard-2a').html(pronun);
		}
		if (atrans) {
			$('#flashcard-2a').html(trans);
		}

		/* populate side 3 */
		if (iword) {
			$('#flashcard-2b').html(word);
		}
		if (ipronun) {
			$('#flashcard-2b').html(pronun);
		}
		if (itrans) {
			$('#flashcard-2b').html(trans);
		}
		
		$('#flashcard-side1').show();
		$('#flashcard-flip').show();
		$('#flashcard-2a').hide();
		$('#flashcard-3').hide();
	}
}

function flip_card() {
	$('#flashcard-2a').show();
	if ($('#side3').prop('checked')) {
		$('#flashcard-2b').show();
		$('#flashcard-3').show();
	}
	$('#card-followup-container').show();
	$('#flashcard-flip').hide();
	
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
	shiftCards();
	nextCard();
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