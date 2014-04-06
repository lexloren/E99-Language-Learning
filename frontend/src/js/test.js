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

    if(closedate < opendate){
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
    $.post('../../test_sections.php', 
        {test_id: urlParams.test})
        .done(function(data){
            if(data.isError){
                $("#sections").html("Section information unavailable.");
            }
            else{
                $.each(data.result, function(i, item){
                    $('#sections').append("<br /> &nbsp; &nbsp; "+item.name);
                });
            }		
            $.getJSON('../../test_select.php', 
                {test_id: urlParams.test},
                function(data){
                    if(data.isError){
                        $("#failure").html("Information for this test could not be retrieved.");
                        showFailure();
                    }
                    else{
                        $("#testname").val(data.result.name);
                        if(data.result.message != "null"){
                            $("#instructions").val(data.result.message);
                        }
                        if(data.result.timeframe != "null"){
                            if(data.result.timeframe.open != "null"){
                                opendate = new Date(data.result.timeframe.open);
                                $("#opendate").val(opendate);
                            }
                            if(data.result.timeframe.close != "null"){
                                closedate = new Date(data.result.timeframe.close);
                                $("#closedate").val(closedate);
                            }
                        }
                    }		
            });
    });
}

function showSectionForm(){
    $("#sectionData").load("createsection.html");
}

function deleteTest(){
    if(urlParams.test == null){
        $("#failure").html("The test to be deleted must be specified. Go to the associated test's page to delete it.");
        showFailure(); 
        return;
    }
    if(!confirm("Are you sure you want to delete this test?")){
        return;
    }
    $.post('../../test_delete.php', 
        { test_id: urlParams.test })
        .done(function(data){
        if(data.isError){
            var errorMsg = "Test could not be deleted: ";
            if(data.errorTitle == "Test Selection"){
                errorMsg += "Test does not exist.";
            }
            else if(data.errorTitle == "Test Deletion"){
                errorMsg += "Please refresh the page and try again.";
            }
            $("#failure").html(errorMsg);
            showFailure();
        }
        else{
            $("#testData").html("Test was successfully deleted.");
        }
    });
    return; 
}

function updateTest(){
    if(urlParams.test == null){
        $("#failure").html("The test to be updated must be specified. Go to the associated test's page to update it.");
        showFailure(); 
        return;
    }
    // should refactor this out into a separate function since create form uses it too
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

    if(closedate < opendate){
		    $("#failure").html("Open Date cannot be later than Close Date; please re-select the dates.");	
	      showFailure();
        return;
    }
    $.post('../../test_update.php', 
        { test_id: urlParams.test, name: testname, open: opendate, close: closedate, message: instructions } )
        .done(function(data){
            if(data.isError){
                var errorMsg = "Test could not be updated.";
                // not sure of error titles yet
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
                location.reload(true);
            }
    });
    return; 
}