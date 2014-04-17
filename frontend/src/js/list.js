var URL = "http://cscie99.fictio.us/";
var listsURL = URL + 'user_lists.php';
var viewlistURL = URL + 'list_entries.php?list_id=1';
var listnum;

$.mockjax({
  url: listsURL,
  responseText: {
	"isError":false,
	"errorTitle":null,
	"errorDescription":null,
	"result":[
		{"listId" : 1,
		"name" : "Lesson 1: Family"},
		{"listId" : 1,
		"name" : "Lesson 2: Animals"}],
	},
});

$.mockjax({
  url: viewlistURL,
  responseText: {"isError":false,"errorTitle":null,"errorDescription":null,"result":[{"entryId":12003,"owner":{"userId":6,"isSessionUser":true,"handle":"practitioner","email":"lloren@gmail.com","nameGiven":"","nameFamily":""},"languages":["en","jp"],"words":{"en":"(n) toughness (of a material)","jp":"\u3058\u3093\u6027"},"pronuncations":{"jp":"\u3058\u3093\u305b\u3044"}},{"entryId":28,"owner":{"userId":6,"isSessionUser":true,"handle":"practitioner","email":"lloren@gmail.com","nameGiven":"","nameFamily":""},"languages":["en","jp"],"words":{"en":"fastening","jp":"\u3006"},"pronuncations":{"jp":"\u3057\u3081"}},{"entryId":50234,"owner":{"userId":6,"isSessionUser":true,"handle":"practitioner","email":"lloren@gmail.com","nameGiven":"","nameFamily":""},"languages":["en","jp"],"words":{"en":"(n) (comp) Gopher","jp":"\u30b4\u30fc\u30d5\u30a1\u30fc"},"pronuncations":{"jp":null}}],"resultInformation":null}
});

$(document).ready(function(){
	pageSetup();
	$("#listpagetitle").hide();
	$("#dict-add").hide();
	$('#list-add').hide();
	handleClicks();
	listnum = getURLparam('listid');
	if(listnum === null) {
		showAllLists();
	} else {
		$('#create-list').hide();
		viewList(listnum);
	}
	
});

function showAllLists() {
	getLists();
	$("#listpagetitle").show();
	var message = 'You can practice with your lists as well as lists from your courses at your ' +
	'<a href="practice.html">practice page</a>.';
	successMessage(message);
}

function handleClicks() {
	$(document).on('click', '.list-delete', function (event) {
		event.preventDefault();
		deleteList(this.id);
		$(this).parent().parent().remove();
	});
	$(document).on('click', '.entry-delete', function (event) {
		event.preventDefault();
		delete_entry(this.id);
		$(this).parent().parent().remove();
	});
	$('#dict-search').submit(function(event) {
		event.preventDefault();
		search_entry(1);
	});
	$(document).on('click', '.add-entry', function () {
		addEntry(this.id);
	});
	$('#next_dict').on('click', function (event) {
		event.preventDefault();
		search_entry();
	});
	$('#create-list').click(function(event) {
		event.preventDefault();
		$('#create-list').hide();
		$('#list-add').show();
	});
	$('#list-add').submit(function(event) {
		event.preventDefault();
		newList( $('#new-list-name').val() );
	});
}

function getLists() {
	$('#lists').html('');
	var currentURL = URL + 'user_lists.php';
	$.getJSON( currentURL, function( data ) {
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else if (data.result.length === 0) {
			failureMessage("You don't have any lists to practice with.");
		} else {
			$('#lists').append('<tbody>');
			$.each( data.result, function () {
				insertListRow(this.name, this.listId);
			});
			$('#lists').append('</tbody>');
		}
	})
	 .fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function insertListRow(listname, listid) {
	$('#lists').append('<tr><td>' + listname + '</td>' + 
		'<td><a href="list.html?listid='+ listid + '">[view/edit]</a></td>' +
		'<td><a href="#" class="list-delete" id="'+ listid + '">[delete]</a><td>' + 
		'</tr>');
}

function viewList(listid) {

	var currentURL = URL + 'list_entries.php?list_id=' + listid;
	$.getJSON( currentURL, function( data ) {
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else if (data.result.length === 0) {
			failureMessage("This list doesn't have any entries yet.");
			$("#dict-add").show();
		} else {
			$('#lists').html('');
			$('#lists').append('<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td>' + '</tr></thead>');
			$('#lists').append('<tbody>');
			$.each( data.result, function( ) {
				$('#lists').append('<tr>' + 
				'<td>' + this.languages[1] + ' : ' + this.words[this.languages[1]] + '</td>' + 
				'<td>' + this.languages[1] + ' : ' + this.pronuncations[this.languages[1]] + '</td>' + 
				'<td>' + this.languages[0] + ' : ' + this.words[this.languages[0]] + '</td>' + 
				'<td><a href="#" class="entry-delete" id="' + this.entryId + '">[delete]</a></td>' + 
				'</tr>');
			});
			$("#dict-add").show();
		}
	})			
	 .fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function delete_entry(entry) {
	var currentURL = URL + 'list_entries_remove.php';
	$.post(currentURL, {'list_id' : listnum, 'entry_ids' : entry }, function() {
		console.log( "success" );
	})
	.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	})
}

function search_entry(page) {
	$('#dictionary').show();
	$('#dictionary').html('');
	var currentURL = URL + 'entry_find.php';
	var word = $('#entrysearch').val();
	var langcodes = $('#lang1').val() + ',' + $('#lang2').val();
	$.getJSON( currentURL, {
		query : word,
		langs : langcodes,
		page_size : 5,
		page_num : page
	}, function( data ) {
		if (data.isError === true) {
			console.log(data.errorTitle);
			console.log(data.errorDescription);
		} else {
			$('#dictionary').append('<tbody>');
			$.each( data.result, function() {
				$('#dictionary').append('<tr><td>' + this.words[this.languages[1]] + '</td>' + 
					'<td>' + this.words[this.languages[0]] + '</td>' + 
					'<td>' + this.pronuncations[this.languages[1]] + '</td>' + 
					'<td><button type="submit" class="btn btn-primary add-entry" id="' + this.entryId + '">add</button></td></tr>');
			});
			$('#dictionary').append('<tr><td><a href="#" id="next_dict">[next page]</a></td></tr>');
			$('#lists').append('</tbody>');
		}
	});
}

function addEntry(entryid) {
	var currentURL = URL + 'list_entries_add.php';
	$.post(currentURL, {'list_id' : listnum, 'entry_ids' : entryid }, function() {
		viewList(listnum);
	})
		.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	})
}

function deleteList(listid) {
	var currentURL = URL + 'list_entries_remove.php';
	$.post(currentURL, {'list_id' : listid }, function() {
		console.log( "success" );
	})
	.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	})
}

function newList(listname) {
	var currentURL = URL + 'list_insert.php';
	$.post(currentURL, {'list_name' : listname}, function(data) {
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else {
			insertListRow(listname, data.result.listId);
		}
	})
		.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	})
}