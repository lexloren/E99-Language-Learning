var URL = "../../";
var listnum;


$(document).ready(function(){
	pageSetup();
	$("#dict-add").hide();
	$('#list-add').hide();
	$('#rename-form').hide();
	$('#loader-dict').hide();
	$("#list-change").hide();
	handleClicks();
	getLangs();
	listnum = getURLparam('listid');
	if(listnum === null) {
		showAllLists();
	} else {
		$('#create-list').hide();
		viewList(listnum);
	}
});

function getLangs(){
    $.getJSON('../../language_enumerate.php')
        .done(function(data){
            authorize(data);
            if(data.isError){
                failureMessage("Word search could not be initialized.");
            }
            else{
                $.each(data.result, function(i, item){           
                    $('#entry-lang1').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                    $('#entry-lang2').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                });
            }
    })
    .fail(function(error) {
        failureMessage('Something has gone wrong. Please refresh the page and try again.');
    });    
}



function showAllLists() {
	getLists();
	var message = 'You can practice with your lists as well as lists from your courses at your ' +
	'<a href="practice.html">practice page</a>.';
	successMessage(message);
}

function handleClicks() {
	$(document).on('click', '.entry-delete', function (event) {
		event.preventDefault();
		delete_entry(this.id);
		$(this).parent().parent().remove();
	});
	$('#delete-list').submit(function(event) {
		event.preventDefault();
		delete_entry(listnum);
	});
	$('#dict-search').submit(function(event) {
		event.preventDefault();
		search_entry(1);
	});
	$(document).on('click', '.add-entry', function () {
		addEntry(this.id);
	});
	$(document).on('click', '#rename-show', function () {
		$('#pagetitle').hide();
		$('#rename-form').show();
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
	$('#rename-form').submit(function(event) {
		event.preventDefault();
		ListRename($('#rename-input').val());
	});
}

function getLists() {
	$('#lists').html('');
	var currentURL = URL + 'user_lists.php';
	$.getJSON( currentURL, function( data ) {
		authorize(data);
		$('#loader-show-lists').hide();
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else if (data.result.length === 0) {
			failureMessage("You don't have any lists to practice with.");
		} else {
			$('#lists').append('<tbody>');
			$.each( data.result, function () {
				insertListRow(this.name, this.listId, this.entriesCount);
			});
			$('#lists').append('</tbody>');
		}
	})
	 .fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function insertListRow(listname, listid, entriescount) {
	$('#lists').append('<tr class="static-listname" id="'+ listid + '">' + 
		'<td><a href="list.html?listid='+ listid + '">' + listname + '</a></td>' +
		'<td><span class="badge pull-right">'+ entriescount + '</span><td>' + 
		'</tr>');
}

function viewList(listid) {
	$('#loader-show-lists').show();
	$('#lists').html('');
	var currentURL = URL + 'list_select.php?list_id=' + listid;
	$.getJSON( currentURL, function( data ) {
		$('#loader-show-lists').hide();
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else {
			$("#pagetitle").html(data.result.name);
			$("#rename-input").val(data.result.name);	
			$("#dict-add").show();
			$("#list-change").show();
			$('#lists').append('<tbody>');
			if (data.result.entries.length === 0) {
				failureMessage("This list doesn't have any entries yet.");
			} else {
				
				$('#lists').append('<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td>' + '</tr></thead>');
				
				$.each( data.result.entries, function() {
					insertEntryRow(this.languages[1], this.languages[0], this.words[this.languages[1]], 
						this.pronuncations[this.languages[1]], this.words[this.languages[0]], this.entryId);
				});
			}
		}
	})			
	 .fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function insertEntryRow(lang1, lang2, word, pro, trans, id) {
	$('#lists').append('<tr>' + 
		'<td>' + lang1 + ' : ' + word + '</td>' + 
		'<td>' + lang1 + ' : ' + pro + '</td>' + 
		'<td>' + lang2 + ' : ' + trans + '</td>' + 
		'<td><a href="#" class="entry-delete btn btn-default" id="' + id + 
		'"><span class="glyphicon glyphicon-trash"></span></a></td>' + 
		'</tr>');
}

function delete_entry(entry) {
	var currentURL = URL + 'list_entries_remove.php';
	$.post(currentURL, {'list_id' : listnum, 'entry_ids' : entry }, function(data) {
		authorize(data);
	})
	.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function search_entry(page) {
	$('#loader-dict').show();
	$('#dictionary').show();
	$('#dictionary').html('');
	var currentURL = URL + 'entry_find.php';
	var word = $('#entrysearch').val();
	var langcodes = $('#entry-lang1').val() + ',' + $('#entry-lang2').val();
	$.getJSON( currentURL, {
		query : word,
		langs : langcodes,
		page_size : 5,
		page_num : page
	}, function( data ) {
		authorize(data);
		$('#loader-dict').hide();
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
	})
	.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function addEntry(entryid) {
	var currentURL = URL + 'list_entries_add.php';
	$.post(currentURL, {'list_id' : listnum, 'entry_ids' : entryid }, function(data) {
		authorize(data);
		viewList(listnum);
	})
		.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function deleteList(listid) {
	var currentURL = URL + 'list_delete.php';
	$.post(currentURL, {'list_id' : listid }, function(data) {
		authorize(data);
	})
	.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function newList(listname) {
	var currentURL = URL + 'list_insert.php';
	$.post(currentURL, {'name' : listname}, function(data) {
		authorize(data);
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
		} else {
			insertListRow(listname, data.result.listId, 0);
		}
	})
		.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}

function ListRename(newname) {
	var currentURL = URL + 'list_update.php';
	
	$("#rename-form").hide();
	$("#pagetitle").html(newname);
	$("#pagetitle").show();
	
	$.post(currentURL, {
		'name' : newname,
		'list_id' : listnum }, function(data) {
			authorize(data);
			if (data.isError === true) {
				failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
			}
	})
		.fail(function() {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
}