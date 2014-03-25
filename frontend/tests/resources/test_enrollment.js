var testaddURL = "http://cscie99.fictio.us/add_course_student.php";
var testdelURL = "http://cscie99.fictio.us/remove_course_student.php";

// unknown user id
var invaliduserJSON = '{"isError":true,"errorTitle":"Invalid User","errorDescription":"User not found in database."}';
var invaliduserErr = "The user is unknown and could not be added/deleted."
// missing course parameter
var courseparamErr = "The course to add the student to must be specified.";
// unknown error
var unknownJSON = '{"isError":true,"errorTitle":"Unknown Error","errorDescription":"The back end unexpectedly failed to add the student."}';
var unknownErr = "Student could not be added: There was an unexpected problem encountered when adding the student.";
// valid student parameter
var validstudentparamJSON = '{"isError":false}';
var validstudentparamUrl = 'enrollment.html?course=123';

test("missing course not accepted", function(){
	var course = null;

	if(course == null){
		$("#result").html(courseparamErr);	
	}
	var check = $("#result").html();
	ok(check === courseparamErr, "User notified of missing course parameter.");
});

test("unknown user notification", function(){
	stop();
	$.mockjax({
		url: testaddURL,
		contentType: 'text/json',
		responseText: invaliduserJSON
  	});

	$.post(testaddURL, function(data){
		if(data.isError){
			$("#result").html(invaliduserErr);	
		}
		var check = $("#result").html();
		ok(check === invaliduserErr, "Notification of invalid user error.");
		start();		
  	});
    $.mockjaxClear();
});

test("unknown error notification", function(){
	stop();
	$.mockjax({
		url: testaddURL,
		contentType: 'text/json',
		responseText: unknownJSON
  	});

	$.post(testaddURL, function(data){
		if(data.isError){
			$("#result").html(unknownErr);	
		}
		var check = $("#result").html();
		ok(check === unknownErr, "User notified of unknown error.");
		start();		
  	});
    $.mockjaxClear();
});

test("student enrollment success", function(){
	stop();
	$.mockjax({
		url: testaddURL,
		contentType: 'text/json',
		responseText: validstudentparamJSON
  	});

	$.post(testaddURL, function(data){
		if(!data.isError){
			redirectUrl = validstudentparamUrl;
		}
    else{
      redirectUrl = "";
    }
    ok(redirectUrl === validstudentparamUrl, "Student successfully enrolled.");
    start();	
  	});
    $.mockjaxClear();
});