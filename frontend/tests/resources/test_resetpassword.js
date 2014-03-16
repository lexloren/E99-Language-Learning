var testURL = "http://cscie99.fictio.us/resetpassword.php";

// blank password error
var blankErr = "Please enter new password.";
// mismatched passwords error
var mismatchErr = "Passwords do not match. Please re-enter.";
// invalid password error
var invalidpasswordJSON = '{"isError":true,"errorTitle":"Invalid Password","errorDescription":"Password must consist of between 6 and 31 (inclusive) characters containing at least one letter, at least one number, and at least one non-alphanumeric character."}';
var invalidpasswordErr = "This password does not meet the criteria. Please try again.";
// valid password
var validpasswordJSON = '{"isError":false}';
var validpasswordMsg = 'Your password has been successfully reset.';

test("blank password not accepted", function(){
	var password1 = "";
  var password2 = "";

	if(password1 == "" || password2 == ""){
		$("#result").html(blankErr);	
	}
	var check = $("#result").html();
	ok(check === blankErr, "User notified of blank passwords.");
});

test("mismatched passwords not accepted", function(){
	var password1 = "pa$$w01";
	var password2 = "pa$$w02";

	if(password1 != password2){
		$("#result").html(mismatchErr);	
	}
	var check = $("#result").html();
	ok(check === mismatchErr, "User notified of mismatching passwords.");
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

test("successful password reset", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: validpasswordJSON
  	});

	$.post(testURL, function(data){
		if(!data.isError){
			$("#result").html(validpasswordMsg);	
    }
		var check = $("#result").html();
		ok(check === validpasswordMsg, "User successfully reset password.");
		start();		
  	});
    $.mockjaxClear();
});