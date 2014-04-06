var URL = "http://cscie99.fictio.us/";
var listsURL = URL + 'user_lists.php';
var viewlistURL = URL + 'list_entries.php?list_id=1';

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

// url parameter grabbing code from http://stackoverflow.com/questions/901115/how-can-i-get-query-string-values-in-javascript
var urlParams;
(window.onpopstate = function () {
	var match,
    pl     = /\+/g,  // Regex for replacing addition symbol with a space
    search = /([^&=]+)=?([^&]*)/g,
    decode = function (s) { return decodeURIComponent(s.replace(pl, " ")); },
    query  = window.location.search.substring(1);
      
    urlParams = {};
    while (match = search.exec(query))
    urlParams[decode(match[1])] = decode(match[2]);
   })();
	  
$(document).ready(function(){
	$("#success").hide();
    $("#failure").hide();
	$("#navbar").load("navbar.html");
	if(urlParams.listid == null) {
		getLists();
	} else {
		viewList(urlParams.listid);
	}
	$(document).on('click', '.list-delete', function () {
		//function(this.id);
	});
});
	  
function getLists() {

	$('#lists').html('');
	var currentURL = URL + 'user_lists.php';
	$.getJSON( currentURL, function( data ) {
		if (data.isError === true) {
			$("#failure").html(data.errorTitle + '<br/>' + data.errorDescription);
			$("#failure").show();
		} else if (data.result.length === 0) {
			$("#failure").html("You don't have any lists to practice with.");
			$("#failure").show();
		} else {
			$('#lists').append('<tbody>');
			$.each( data.result, function( ) {
				$('#lists').append('<tr><td>' + this.name + '</td>' + 
				'<td><a href="list.html?listid='+ this.listId + '">[view/edit]</a></td>' +
				'<td><a href="#" class="list-delete" id="'+ this.listId + '">[delete]</a><td>' + 
				'</tr>');
			});
		}
		
	})
	 .fail(function(error) {
		$("#failure").html('Something has gone wrong. Please hit the back button on your browser and try again.');
		$("#failure").show();
	});
}

function viewList(listid) {

	var currentURL = URL + 'list_entries.php?list_id=' + listid;
	$.getJSON( currentURL, function( data ) {
		if (data.isError === true) {
			$("#failure").html(data.errorTitle + '<br/>' + data.errorDescription);
			$("#failure").show();
		} else if (data.result.length === 0) {
			$("#failure").html("No entries yet.");
			$("#failure").show();
		} else {
			$('#lists').append('<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td>' + '</tr></thead>');
			$('#lists').append('<tbody>');
			$.each( data.result, function( ) {
				$('#lists').append('<tr>' + 
				'<td>' + this.languages[1] + ' : ' + this.words[this.languages[1]] + '</td>' + 
				'<td>' + this.languages[1] + ' : ' + this.pronuncations[this.languages[1]] + '</td>' + 
				'<td>' + this.languages[0] + ' : ' + this.words[this.languages[0]] + '</td>' + 
				'</tr>');
			});
		}
		
	})			
	 .fail(function(error) {
		$("#failure").html('Something has gone wrong. Please hit the back button on your browser and try again.');
		$("#failure").show();
	});
}