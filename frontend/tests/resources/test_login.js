var testURL = "http://cscie99.fictio.us/authenticate.php";

// blank parameters error
var blankparamErr = "Please enter username and password.";
// invalid handle error
var invalidhandleJSON = '{"isError":true,"errorTitle":"Invalid Handle","errorDescription":"Handle must consist of between 4 and 63 (inclusive) alphanumeric characters beginning with a letter."}';
var invalidhandleErr = "You could not be signed in: Username is invalid. Please try entering your credentials again.";
// invalid password error
var invalidpasswordJSON = '{"isError":true,"errorTitle":"Invalid Password","errorDescription":"Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character."}';
var invalidpasswordErr = "You could not be signed in: Password is invalid. Please try entering your credentials again.";
// invalid password error";
// unknown credentials error
var unknowncredJSON = '{"isError":true,"errorTitle":"Invalid Credentials","errorDescription":"The handle and password entered match no users in the database."}';
var unknowncredErr = "You could not be signed in: User is unknown.";
// valid parameters
var validparamJSON = '{"isError":false}';
var validparamUrl = 'welcome.html';

test("blank parameters not accepted", function(){
	var handle = "";
	var password = "";

	if(handle == "" || password == ""){
		$("#result").html(blankparamErr);	
	}
	var check = $("#result").html();
	ok(check === blankparamErr, "User notified of blank parameters.");
});

test("invalid handle notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: invalidhandleJSON
  	});

	$.post(testURL, function(data){
		if(data.isError){
			$("#result").html(invalidhandleErr);	
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

	$.post(testURL, function(data){
		if(data.isError){
			$("#result").html(invalidpasswordErr);	
		}
		var check = $("#result").html();
		ok(check === invalidpasswordErr, "User notified of invalid password error.");
		start();		
  	});
    $.mockjaxClear();
});

test("unknown credentials notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: unknowncredJSON
  	});

	$.post(testURL, function(data){
		if(data.isError){
			$("#result").html(unknowncredErr);	
		}
		var check = $("#result").html();
		ok(check === unknowncredErr, "User notified of unknown credentials error.");
		start();		
  	});
    $.mockjaxClear();
});

test("successful login", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: validparamJSON
  	});

  var redirectUrl;

	$.post(testURL, function(data){
		if(!data.isError){
			redirectUrl = validparamUrl;
		}
    else{
      redirectUrl = '';
    }
		ok(redirectUrl === validparamUrl, "User successfully logged in.");
		start();		
  	});
    $.mockjaxClear();
});