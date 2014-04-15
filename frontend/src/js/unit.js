var unit;

$(document).ready(function(){
	pageSetup();
	$("#loader").hide();
	unit = getURLparam('unitid');
	if(unit === null) {
    $("#unitData").hide();
    failureMessage("The unit must be specified. Go to a course page and select a unit to view.");
    return;
	} 
  else {
    $("#updateUnitForm").hide();
    $("#createTestForm").hide();
    getUnitInfo();
	}
});

function getUnitInfo(){
	  $("#loader").show();
    $("#unitData").hide();
    $.getJSON('../../unit_select.php', 
        {unit_id: unit})
        .done(function(data){
            if(data.isError){
                failureMessage("Information for this unit could not be retrieved.");
            }
            else{
                unitheader = '<h3 class="form-signin-heading">Unit '+data.result.unitId+': '+data.result.name+'</h3>';
                $("#unit-header").html(unitheader);
                $("#unitname").val(data.result.name);
                if(data.result.message != null){
                    $("#unitdesc").val(data.result.message);
                }
                if(data.result.timeframe != null){
                    if(data.result.timeframe.open != null){
                        opendate = new Date(data.result.timeframe.open);
                        $("#unitopendate").val(opendate);
                    }
                    if(data.result.timeframe.close != null){
                        closedate = new Date(data.result.timeframe.close);
                        $("#unitclosedate").val(closedate);
                    }
                }
                if(data.result.lists.length == 0){
                    $('#lists').html("<em>This unit currently has no decks.</em>");
                }
                else{
                    $.each(data.result.lists, function(i, item){
                        testrow = '<tr><td><a href="list.html?listid='+item.listId+'">'+item.name+'</a></td>' +
                                  '<td>' + item.entriesCount + '</td>' +
                                  '<td><input type="checkbox" class="rem_list_ids" name="rem_list_ids" value='+item.listId+'></td></tr>';
                        $('#lists').append(newrow);
                    });
                    $('#lists').append('<tr><td></td><td></td><td><button class="btn btn-primary" type="button" onclick="removeLists();">Remove Selected Decks</button></td></tr>');
                }
                if(data.result.tests.length == 0){
                    $('#tests').html("<em>This unit currently has no tests.</em>");
                }
                else{
                    $.each(data.result.tests, function(i, item){
                        testrow = '<tr><td><a href="test.html?testid='+item.testId+'">'+item.name+'</a></td>' +
                                  '<td>' + item.timeframe.open + '</td>' +
                                  '<td>' + item.timeframe.close + '</td></tr>';
                        $('#tests').append(newrow);
                    });
                }
            }	
            $("#loader").hide();
            $("#unitData").show();		
    });

}


function deleteUnit(){
    if(!confirm("Are you sure you want to delete this unit?")){
        return;
    }
    $.post('../../unit_delete.php', 
        { unit_id: unit })
        .done(function(data){
        if(data.isError){
            var errorMsg = "Unit could not be deleted: ";
            if(data.errorTitle == "Unit Selection"){
                errorMsg += "Unit does not exist.";
            }
            else if(data.errorTitle == "Unit Deletion"){
                errorMsg += "Please refresh the page and try again.";
            }
            failureMessage(errorMsg);
        }
        else{
            $("#unitData").hide();
            successMessage("Unit was successfully deleted.");
        }
    });
    return; 
}

function showForm(frm,tohide){
    $(tohide).hide();
    $(frm).show();
}

function cancelUpdate(frm,tohide){
    if(frm != '#updateUnitForm'){ // but maybe add something to restore original values for updateUnitForm?
        $(frm)[0].reset(); 
    }
    $(frm).hide();
    $(tohide).show();
}

function saveUpdate(){
    var unitname = $("#unitname").val();
    var desc = $("#unitdesc").val();
    var opendate = $("#unitopendate").val();
    var closedate = $("#unitclosedate").val();
    
	  if(unitname == "" || desc == "" || opendate == "" || closedate == ""){
		    failureMessage("Please provide unit name, instructions, and open/close dates.");	
        return;
    }

    opendate = Date.parse(opendate);
    closedate = Date.parse(closedate);

    if(closedate < opendate){
		    failureMessage("Open Date cannot be later than Close Date; please re-select the dates.");	
        return;
    }

    $.post('../../unit_update.php', 
        { unit_id: unit, name: unitname, open: opendate, close: closedate, message: desc } )
        .done(function(data){
            if(data.isError){
                var errorMsg = "Unit could not be updated. Please reload the page and try again.";
                failureMessage(errorMsg);
            }
            else{
                getUnitInfo();
                cancelUpdate("#updateUnitForm","#unit-update");
                successMessage("Unit was successfully updated.");
            }
    });
    return; 
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

function submitCreateTestForm(testname, desc, opendate, closedate){
    $.post('../../test_insert.php', 
        { unit_id: unit, name: testname, open: opendate, close: closedate, message: desc } )
        .done(function(data){
            if(data.isError){
                var errorMsg = "Test could not be created: ";
                if(data.errorTitle == "Unit Selection"){
                    errorMsg += "Unit does not exist.";
                }
                else if(data.errorTitle == "Test Insertion"){
                    errorMsg += "Please refresh the page and try again.";
                }
                failureMessage(errorMsg);
            }
            else{
                getUnitInfo();
                cancelUpdate("#createTestForm","#add-test");
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}