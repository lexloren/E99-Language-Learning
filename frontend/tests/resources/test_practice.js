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

