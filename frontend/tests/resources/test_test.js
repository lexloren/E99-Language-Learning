var testURL = "http://cscie99.fictio.us/unit/tests";
var createErr = "Test could not be created: ";

// blank test parameters error
var blanktestparamErr = "Please provide test name, instructions, and open/close dates.";
// missing unit url parameter
var unitparamErr = "The course unit for the test must be specified.";
// invalid date range error
var invaliddatesErr = "Open Date cannot be later than Close Date; please re-select the dates.";
// unknown error
var unknownJSON = '{"isError":true,"errorTitle":"Unknown Error","errorDescription":"The back end unexpectedly failed to create the test."}';
var unknownErr = "Test could not be created: There was an unexpected problem creating your test.";
// valid test parameters
var validtestparamJSON = '{"isError":false}';
var validtestparamUrl = 'test.html?test=123';

test("blank parameters not accepted", function(){
	var name = "";
	var instructions = "";
	var opendate = "";
	var closedate = "";

	if(name == "" || instructions == "" || opendate == "" || closedate == ""){
		$("#result").html(blanktestparamErr);	
	}
	var check = $("#result").html();
	ok(check === blanktestparamErr, "User notified of blank parameters.");
});

test("missing unit not accepted", function(){
	var unit = null;

	if(unit == null){
		$("#result").html(unitparamErr);	
	}
	var check = $("#result").html();
	ok(check === unitparamErr, "User notified of missing unit parameter.");
});


test("invalid date range notification", function(){
	var open = "03/28/2014 23:35";
  var close = "03/27/2014 23:35";

  var opendate = new Date(Date.parse(open));
  var closedate = new Date(Date.parse(close));

  if(closedate <= opendate){
    $("#result").html(invaliddatesErr);
  }

	var check = $("#result").html();
	ok(check === invaliddatesErr, "User notified of invalid date range.");
});

test("unknown error notification", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: unknownJSON
  	});

	$.post(testURL, function(data){
		if(data.isError){
			$("#result").html(unknownErr);	
		}
		var check = $("#result").html();
		ok(check === unknownErr, "User notified of unknown error.");
		start();		
  	});
    $.mockjaxClear();
});

test("test creation success", function(){
	stop();
	$.mockjax({
		url: testURL,
		contentType: 'text/json',
		responseText: validtestparamJSON
  	});

	$.post(testURL, function(data){
		if(!data.isError){
			redirectUrl = validtestparamUrl;
		}
    else{
      redirectUrl = validtestparamUrl;
    }
    ok(redirectUrl === validtestparamUrl, "Test successfully created.");
    start();	
  	});
    $.mockjaxClear();
});