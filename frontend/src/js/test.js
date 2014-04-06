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
    opendate = Date.parse(opendate);
    closedate = Date.parse(closedate);

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
    //var questions = $("#questions").val();
    
	  if(sectionname == "" || instructions == ""){
		    $("#failure").html("Please provide section name and instructions.");	
	      showFailure();
        return;
    }

    submitCreateSectionForm(sectionname, instructions);
}

function submitCreateTestForm(testname, instructions, opendate, closedate){
    $.post('../../test_insert.php', 
        { unit_id: urlParams.unit, name: testname, open: opendate, close: closedate, message: instructions } )
        .done(function(data){
            if(data.isError){
                var errorMsg = "Test could not be created: ";
                if(data.errorTitle == "Unit Selection"){
                    errorMsg += "Unit does not exist.";
                }
                else if(data.errorTitle == "Test Insertion"){
                    errorMsg += "Please refresh the page and try again.";
                }
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
                nextUrl = "test.html"+"?test="+data.result.testId;
                window.location.replace(nextUrl);
            }
    });
    return; 
}

function submitCreateSectionForm(sectionname, instructions){
    $.post('../../section_insert.php', 
        { test_id: urlParams.test, name: sectionname, message: instructions })
        .done(function(data){
        if(data.isError){
            var errorMsg = "Section could not be created: ";
            if(data.errorTitle == "Test Selection"){
                errorMsg += "Test does not exist.";
            }
            else if(data.errorTitle == "Section Insertion"){
                errorMsg += "Please refresh the page and try again.";
            }
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

    $.getJSON('../../test_select.php', 
        {test_id: urlParams.test},
        function(data){
            if(data.isError){
                $("#failure").html("Information for this test could not be retrieved.");
                showFailure();
            }
            else{
                $("#testname").html(data.result.name);
                if(data.result.message != "null"){
                    $("#instructions").html(data.result.message);
                }
                if(data.result.timeframe != "null"){
                    $("#opendate").html(data.result.timeframe.open);
                    $("#closedate").html(data.result.timeframe.close);
                }
            }		
    });

    $.getJSON('../../test_sections.php', 
        {test_id: urlParams.test},
        function(data){
            if(data.isError){
                $("#sections").html("Section information unavailable.");
            }
            else{
                $.each(data, function(i, item){
                    $('#sections').append(item.name);
                });
            }		
    });
}

function showSectionForm(){
    $("#sectionData").load("createsection.html");
}