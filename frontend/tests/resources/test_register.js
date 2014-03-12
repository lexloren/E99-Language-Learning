var testURL = "http://cscie99.fictio.us/register.php";
var regErr = "Registration could not be completed: ";

// blank parameters error
var blankparamErr = "Please enter email address, desired handle, and password.";
// invalid email error
var invalidemailJSON = '{"isError":true,"errorTitle":"Invalid Email","errorDescription":"Email must conform to the standard pattern."}';
var invalidemailErr = "Registration could not be completed: Email must conform to the standard pattern.";
// invalid handle error
var invalidhandleJSON = '{"isError":true,"errorTitle":"Invalid Handle","errorDescription":"Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter."}';
var invalidhandleErr = "Registration could not be completed: Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter.";
// invalid password error
var invalidpasswordJSON = '{"isError":true,"errorTitle":"Invalid Password","errorDescription":"Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character."}';
var invalidpasswordErr = "Registration could not be completed: Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character.";
// handle conflict error
var handleconflictJSON = '{"isError":true,"errorTitle":"Handle Conflict","errorDescription":"The requested handle is already taken."}';
var handleconflictErr = "Registration could not be completed: The requested handle is already taken.";
// unknown error
var unknownJSON = '{"isError":true,"errorTitle":"Unknown Error","errorDescription":"The back end unexpectedly failed to create the user."}';
var unknownErr = "Registration could not be completed: There was an unexpected problem creating your account.";
// valid parameters
var validparamJSON = '{"isError":false}';
var validparamMsg = 'Your account has been successfully created. Please go to <a href="login.html">login page</a> and sign in.';

test("blank parameters not accepted", function(){
	var email = "";
	var handle = "";
	var password = "";

	if(email == "" || handle == "" || password == ""){
		$("#result").html(blankparamErr);	
	}
	var check = $("#result").html();
	ok(check === blankparamErr, "User notified of blank parameters.");
    $.mockjaxClear();
});

test("invalid email notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: invalidemailJSON
  	});

	$.getJSON(testURL, function(data){
		if(data.isError){
			$("#result").html(regErr + data.errorDescription);	
		}
		var check = $("#result").html();
		ok(check === invalidemailErr, "User notified of invalid email error.");
		start();		
  	});
    $.mockjaxClear();
});

test("invalid handle notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: invalidhandleJSON
  	});

	$.getJSON(testURL, function(data){
		if(data.isError){
			$("#result").html(regErr + data.errorDescription);	
		}
		var check = $("#result").html();
		ok(check === invalidhandleErr, "User notified of invalid handle error.");
		start();		
  	});
    $.mockjaxClear();
});

test("invalid password notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: invalidpasswordJSON
  	});

	$.getJSON(testURL, function(data){
		if(data.isError){
			$("#result").html(regErr + data.errorDescription);	
		}
		var check = $("#result").html();
		ok(check === invalidpasswordErr, "User notified of invalid password error.");
		start();		
  	});
    $.mockjaxClear();
});

test("handle conflict notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: handleconflictJSON
  	});

	$.getJSON(testURL, function(data){
		if(data.isError){
			$("#result").html(regErr + data.errorDescription);	
		}
		var check = $("#result").html();
		ok(check === handleconflictErr, "User notified of handle conflict error.");
		start();		
  	});
    $.mockjaxClear();
});

test("unknown error notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: unknownJSON
  	});

	$.getJSON(testURL, function(data){
		if(data.isError){
			$("#result").html(unknownErr);	
		}
		var check = $("#result").html();
		ok(check === unknownErr, "User notified of unknown error.");
		start();		
  	});
    $.mockjaxClear();
});

test("account creation notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: validparamJSON
  	});

	$.getJSON(testURL, function(data){
		if(!data.isError){
			$("#result").html(validparamMsg);	
		}
		var check = $("#result").html();
		ok(check === validparamMsg, "User notified account creation.");
		start();		
  	});
    $.mockjaxClear();
});