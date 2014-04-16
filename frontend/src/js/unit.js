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
    $("#searchListForm").hide();
    getUnitInfo();
	}
});

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

<!--- Units --->
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
                $("#unitData").show();	
            }	
            $("#loader").hide();	
    })
	 .fail(function(error) {
        $("#loader").hide();
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
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
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
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
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}

<!--- Tests --->
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

<!--- Lists --->
function addLists(){
    var listsAdd = $('.add_list_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../unit_lists_add.php', 
        { unit_id: unit, list_ids: listsAdd })
        .done(function(data){
            if(data.isError){
                var errorMsg = "List(s) could not be added: ";

                if(data.errorTitle == "Unit Selection"){
                    errorMsg += "Unit does not exist.";
                }
                else{
                    errorMsg += "Please refresh the page and try again.";
                }
                failureMessage(errorMsg);
            }
            else{
                getUnitInfo();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}

function removeLists(){
    var listsRem = $('.rem_list_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../unit_lists_remove.php', 
        { unit_id: unit, list_ids: listsRem })
        .done(function(data){
            if(data.isError){
                var errorMsg = "List(s) could not be removed: ";
                if(data.errorTitle == "Unit Selection"){
                    errorMsg += "Unit does not exist.";
                }
                else{
                    errorMsg += "Please refresh the page and try again.";
                }
                failureMessage(errorMsg);
            }
            else{
                getUnitInfo();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}

function toggleSearch(){
    $("#searchResults").html('');
    if($("#searchListForm").is(":visible")){
        $("#searchListForm").slideUp();
    }
    else{
        $("#listCriteria").val('');
        $("#searchListForm").slideDown();
    }
}

function searchLists(){
    toggleSearch();
    criteria = $("#listCriteria").val();
    $.getJSON('../../list_find.php', <!--- not implemented yet --->
        {query: criteria}, 
        function(data){
            if(data.result.length == 0){
                $('#searchResults').html('<br />No matching lists found.<br /><br />');
                $('#searchResults').append('<button type="button" stype="button" class="btn" onclick="toggleSearch();">New Search</button><br /><br />');
            }
            else{
                $('#searchResults').append('<br /><strong>Search Results:</strong>');
                $.each(data.result, function(i, item){
                    if($(".rem_list_ids:checkbox[value="+item.listId+"]").length > 0){
                        disabled = " disabled";
                    }
                    else{
                        disabled = "";
                    }
                    result = '<div class="checkbox"><label><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+item.listId+disabled+'>'+
                             item.name+'</label></div>';
                    $('#searchResults').append(result);
                });
                $('#searchResults').append('<br /><button class="btn btn-primary" type="button" onclick="addLists();">Add Selected Lists</button> &nbsp; &nbsp;'+
                                           '<button type="button" stype="button" class="btn" onclick="toggleSearch();">New Search</button><br /><br />');
            }		
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  }); 
    //$("html, body").animate({scrollBottom:0}, "slow");  
    return;
}
