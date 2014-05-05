/* mockjax for testing */

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
  responseText: {"isError":false,"errorTitle":null,"errorDescription":null,"result":[{"practiceEntryId":12,"userEntryId":12,"mode":0,"interval":0,"efactor":2.5,"entry":{"entryId":611497,"languages":["en","cn"],"words":{"en":"puppy","cn":"\u5c0f\u72ac"},"pronuncations":{"cn":"xiao3 quan3"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},"sessionUserPermissions":{"read":true,"write":true,"execute":false}},{"practiceEntryId":15,"userEntryId":15,"mode":0,"interval":0,"efactor":2.5,"entry":{"entryId":820825,"languages":["en","cn"],"words":{"en":"horse","cn":"\u99ac\u5339"},"pronuncations":{"cn":"ma3 pi3"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},"sessionUserPermissions":{"read":true,"write":true,"execute":false}},{"practiceEntryId":20,"userEntryId":20,"mode":0,"interval":0,"efactor":2.5,"entry":{"entryId":699322,"languages":["en","cn"],"words":{"en":"dog","cn":"\u72ac"},"pronuncations":{"cn":"quan3"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},"sessionUserPermissions":{"read":true,"write":true,"execute":false}},{"practiceEntryId":16,"userEntryId":16,"mode":0,"interval":12,"efactor":2.08,"entry":{"entryId":826644,"languages":["en","cn"],"words":{"en":"bird","cn":"\u9ce5\u96c0"},"pronuncations":{"cn":"niao3 que4"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},"sessionUserPermissions":{"read":true,"write":true,"execute":false}},{"practiceEntryId":10,"userEntryId":10,"mode":0,"interval":31,"efactor":2.22,"entry":{"entryId":774042,"languages":["en","cn"],"words":{"en":"cat","cn":"\u8c93"},"pronuncations":{"cn":"mao1"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},"sessionUserPermissions":{"read":true,"write":true,"execute":false}}],"resultInformation":null}
});

$.mockjax({
  url: dictionaryURL,
  responseText: {"isError":false,"errorTitle":null,"errorDescription":null,"result":[{"entryId":840521,"languages":["en","jp"],"words":{"en":"melt","jp":"\u878d"},"pronuncations":{"jp":"rong2 \/ \u30e6\u30a6 \/ \u3068.\u3051\u308b \/ \u3068.\u304b\u3059"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":840520,"languages":["en","jp"],"words":{"en":"dissolve","jp":"\u878d"},"pronuncations":{"jp":"rong2 \/ \u30e6\u30a6 \/ \u3068.\u3051\u308b \/ \u3068.\u304b\u3059"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":421001,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u901a"},"pronuncations":{"jp":"\u3086\u3046\u305a\u3046"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420978,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u5408"},"pronuncations":{"jp":"\u3086\u3046\u3054\u3046"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":false,"execute":false}},{"entryId":420983,"languages":["en","jp"],"words":{"en":"(P)","jp":"\u878d\u8cc7"},"pronuncations":{"jp":"\u3086\u3046\u3057"},"annotationsCount":0,"sessionUserPermissions":{"read":true,"write":false,"execute":false}}],"resultInformation":{"pageSize":5,"pageNumber":1,"pagesCount":51,"entriesFoundCount":255}} 
});

/* end mockjax */