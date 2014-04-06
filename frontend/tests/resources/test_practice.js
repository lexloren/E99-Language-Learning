/* mockjax for testing */

var URL = "http://cscie99.fictio.us/";
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

test( "getWord(word), splitting word into span-wrapped letters", function() {
  var word = 'hello';
  var expected = '<span class="char-of-word">h</span>' + 
  '<span class="char-of-word">e</span>' +
  '<span class="char-of-word">l</span>' +
  '<span class="char-of-word">l</span>' +
  '<span class="char-of-word">o</span>';
  var result = getWord(word);
  equal( expected, result, 'Word is split appropriately');
});

test( "shiftCards(), shift appropriately when list has 3 items", function() {
  wordList = [{a : 'b'}, {b : 'c'}, {c : 'd'}];
  var expected = [{b : 'c'}, {c : 'd'}, {a : 'b'}];
  shiftCards();
  for (var i = 0; i < wordList.length; i++ ){
	equal( expected[i].a, wordList[i].a, 'test a');
	equal( expected[i].b, wordList[i].b, 'test b');
	equal( expected[i].c, wordList[i].c, 'test c');
	}
});

test( "shiftCards(), 1 item", function() {
  wordList = [{a : 'b'}];
  var expected = [{a : 'b'}];
  shiftCards();
  equal( expected[0].a, wordList[0].a, 'test a');

});

test( "shiftCards(), 0 items", function() {
  wordList = [];
  var expected = [];
  shiftCards();
  equal(0, 0, 'code functions');

});