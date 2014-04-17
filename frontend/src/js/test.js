var test;

$(document).ready(function(){
	pageSetup();
	$("#loader").hide();
	test = getURLparam('testid');
	if(test === null) {
    $("#testData").hide();
    failureMessage("The test must be specified. Go to a unit page and select a test to view.");
    return;
	} 
  else {
    $("#updateTestForm").hide();
    $("#takeTest").hide();
    getTestInfo();
	}
});

function showForm(frm,tohide){
    $(tohide).hide();
    $(frm).show();
}

function cancelUpdate(frm,tohide){
    $(frm).hide();
    $(tohide).show();
}

function verifyTestForm(){
    var testname = $("#testname").val();
    var desc = $("#testdesc").val();
    var opendate = $("#testopendate").val();
    var closedate = $("#testclosedate").val();
    
	  if(testname == "" || desc == "" || opendate == "" || closedate == ""){
		    failureMessage("Please provide test name, instructions, and open/close dates.");	
        return;
    }

    opendate = Date.parse(opendate);
    closedate = Date.parse(closedate);

    if(closedate < opendate){
		    failureMessage("Open Date cannot be later than Close Date; please re-select the dates.");	
        return;
    }

    submitCreateTestForm(testname, desc, opendate, closedate);
}

function getTestInfo(){
    $("#loader").show();
    $("#testData").hide();
    $.getJSON('../../test_select.php', 
        {test_id: test},
        function(data){
            if(data.isError){
                failureMessage("Information for this test could not be retrieved.");
            }
            else{
                testheader = '<h3 class="form-signin-heading">Test '+data.result.testId+': '+data.result.name+'</h3>';
                $("#testname").val(data.result.name);
                if(data.result.message != null){
                    $("#instructions").val(data.result.message);
                }
                if(data.result.timeframe != null){
                    if(data.result.timeframe.open != null){
                        opendate = new Date(data.result.timeframe.open);
                        $("#opendate").val(opendate);
                    }
                    if(data.result.timeframe.close != null){
                        closedate = new Date(data.result.timeframe.close);
                        $("#closedate").val(closedate);
                    }
                }
                $("#testData").show();
            }		
            $("#loader").hide();
    })
	 .fail(function(error) {
        $("#loader").hide();
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
}

function deleteTest(){
    if(!confirm("Are you sure you want to delete this test?")){
        return;
    }
    $.post('../../test_delete.php', 
        { test_id: test })
        .done(function(data){
        if(data.isError){
            var errorMsg = "Test could not be deleted: ";
            if(data.errorTitle == "Test Selection"){
                errorMsg += "Test does not exist.";
            }
            else if(data.errorTitle == "Test Deletion"){
                errorMsg += "Please refresh the page and try again.";
            }
            failureMessage(errorMsg);
        }
        else{
            $("#testData").hide();
            successMessage("Test was successfully deleted.");
        }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}

function updateTest(){
    verifyTestForm();
    $.post('../../test_update.php', 
        { test_id: test, name: testname, open: opendate, close: closedate, message: instructions } )
        .done(function(data){
            if(data.isError){
                var errorMsg = "Test could not be updated. Please reload the page and try again";
                failureMessage(errorMsg);
            }
            else{
                getTestInfo();
                cancelUpdate("#updateTestForm","#test-update");
                successMessage("Test was successfully updated.");
            }
    })
	 .fail(function(error) {
        $("#loader").hide();
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}