function resetForm(frm){
  $("#failure").html("");
  $("#success").html("");
  $("#failure").hide();
  $("#success").hide(); 
  $(frm)[0].reset()
}

function showFailure(){
  $("#failure").show();
  $("html, body").animate({scrollTop:0}, "slow");  
}

function verifyTestForm(){
    if(urlParams.unit == null){
        $("#failure").html("The course unit for the test must be specified. Go to the course unit page first to add the associated test.");
        showFailure(); 
        return;
    }
    var testname = $("#testname").val();
    var instructions = $("#instructions").val();
    var opendate = $("#opendate").val();
    var closedate = $("#closedate").val();
    
	  if(testname == "" || instructions == "" || opendate == "" || closedate == ""){
		    $("#failure").html("Please provide test name, instructions, and open/close dates.");	
	      showFailure();
        return;
    }
    opendate = new Date(Date.parse(opendate));
    closedate = new Date(Date.parse(closedate));

    if(closedate <= opendate){
		    $("#failure").html("Open Date cannot be later than Close Date; please re-select the dates.");	
	      showFailure();
        return;
    }
    submitCreateTestForm(testname, instructions, opendate, closedate);
}

function verifySectionForm(){
    if(urlParams.test == null){
        $("#failure").html("The test for the section must be specified. Go to the associated test's page to add the section.");
        showFailure(); 
        return;
    }
    var sectionname = $("#sectionname").val();
    var instructions = $("#sectioninstructions").val();
    var questions = $("#questions").val();
    
	  if(sectionname == "" || instructions == "" || questions == ""){
		    $("#failure").html("Please provide section name, instructions, and questions.");	
	      showFailure();
        return;
    }

    submitCreateSectionForm(sectionname, instructions, questions);
}

function submitCreateTestForm(testname, instructions, opendate, closedate){
    var mockJSON = '{"testId":"123","testName":"Chinese Exam 1","timeframe":{"open":"2014/01/01 08:00","close":"2014/01/01 09:00"},"instructions":"some instructions"}';
    $.mockjax({
        url: '../../unit/tests',  
        contentType: 'text/json',
        responseText: mockJSON
    });	

    $.post('../../unit/tests', function(data){
            if(data.isError){
                var errorMsg = "Test could not be created: ";
                // don't know the error titles yet
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
            nextUrl = "test.html"+"?test="+data.testId;
            window.location.replace(nextUrl);
            }
    });
    $.mockjaxClear();
    return; 
}

function submitCreateSectionForm(sectionname, instructions, questions){
    var mockJSON = '{"sectionId":"1","sectionName":"Nouns","instructions":"some instructions", "questions": ""}';
    $.mockjax({
        url: '../../unit/tests',  
        contentType: 'text/json',
        responseText: mockJSON
    });	
    $.post('../../unit/tests', function(data){
        if(data.isError){
            var errorMsg = "Section could not be created: ";
            // don't know the error titles yet
            $("#failure").html(errorMsg);
            showFailure();
        }
        else{
            location.reload(true);
        }
    });
    return; 
}

function getTestInfo(){
    if(urlParams.test == null){
        $("#testData").hide();
        $("#failure").html("The test must be specified. Go to the unit page and select a test to view.");
        showFailure(); 
        return;
    }

    var mockJSON = '{"testId":"123","testName":"Chinese Exam 1","timeframe":{"open":"2014/01/01 08:00","close":"2014/01/01 09:00"},"instructions":"some instructions"}';
    $.mockjax({
        url: '../../unit/tests', 
        contentType: 'text/json',
        responseText: mockJSON
    });

    $.getJSON('../../unit/tests', function(data){
        if(data.isError){
            $("#failure").html("Information for this test could not be retrieved.");
            showFailure();
        }
        else{
            $("#testname").html(data.testName);
            $("#instructions").html(data.instructions);
            $("#opendate").html(data.timeframe.open);
            $("#closedate").html(data.timeframe.close);
        }		
    });

    $.mockjaxClear();

    var mockJSONsec = '[{"sectionId":"1","sectionName":"Nouns","instructions":"some instructions", "questions": ""}]';
    $.mockjax({ 
        url: '../../unit/tests', 
        contentType: 'text/json',
        responseText: mockJSONsec
    });

    $.getJSON('../../unit/tests', function(data){
        if(data.isError){
            $("#sections").html("Section information unavailable.");
        }
        else{
	          $.each(data, function(i, item){
			          $('#sections').append(item.sectionName);
            });
        }		
    });
}

function showSectionForm(){
    $("#sectionData").load("createsection.html");
}