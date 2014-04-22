var listsURL = URL + 'user_lists.php';
var viewlistURL = URL + 'list_select.php?list_id=1';
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
  responseText: 
  {"isError":false,"errorTitle":null,"errorDescription":null,"result":
	{"listId":6,"name":"Practice 2","owner":
		{"userId":6,"isSessionUser":true,"languages":
			[{"code":"en","names":{"en":"English","cn":"\u82f1\u8a9e\uff08\u82f1\u8bed\uff09"}},
			{"code":"cn","names":{"en":"Chinese","cn":"\u6f22\u8a9e\uff08\u6c49\u8bed\uff09"}},
			{"code":"jp","names":{"en":"Japanese","cn":"\u65e5\u8a9e\uff08\u65e5\u8bed\uff09","jp":"\u65e5\u672c\u8a9e"}}],
			"handle":"practitioner","email":"lloren@gmail.com","nameGiven":"","nameFamily":"","languagesCount":3,"coursesOwnedCount":0,"coursesInstructedCount":0,"coursesStudiedCount":4,"listsCount":2,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},
		"isPublic":true,"entriesCount":2,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":true,"execute":false},
		"entries":[{"entryId":382334,"languages":["en","jp"],"words":{"en":"real-estate loan","jp":"\u4e0d\u52d5\u7523\u878d\u8cc7"},"pronuncations":{"jp":"\u3075\u3069\u3046\u3055\u3093\u3086\u3046\u3057"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":true,"execute":false}},{"entryId":358618,"languages":["en","jp"],"words":{"en":"(n) restaurant specializing in meat","jp":"\u8089\u51e6"},"pronuncations":{"jp":"\u306b\u304f\u3069\u3053\u308d"},"annotationsCount":0,"hiddenFromSessionUser":false,"sessionUserPermissions":{"read":true,"write":true,"execute":false}}]},
	"resultInformation":null}});