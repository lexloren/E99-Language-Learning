function showFailure(){
  $("#failure").show();
  $("html, body").animate({scrollTop:0}, "slow");  
}

function createTest(){
    window.location.href = 'createtest.html?unit='+urlParams.unit;
}

function getDeckInfo(){
    if(urlParams.unit == null){
        $("#unitData").hide();
        $("#failure").html("The unit must be specified. Go to the course page and select a unit to view.");
        showFailure(); 
        return;
    }

    $.getJSON('../../unit_lists.php', 
        {unit_id: urlParams.unit},
        function(data){
            if(data.isError){
                $("#decks").html("Flashcard data could not be retrieved for this unit.");
            }
            else{
                $.each(data.result, function(i, item){
                    newrow = '<tr><td>' + item.name + '</td></tr>';
                    $('#deckDetails').append(newrow);
                });
            }
    }); 
}

/*
function getUnitInfo(){
    if(urlParams.unit == null){
        $("#unitData").hide();
        $("#failure").html("The unit must be specified. Go to the course page and select a unit to view.");
        showFailure(); 
        return;
    }
    $.getJSON('../../unit_tests.php', 
        {unit_id: urlParams.unit})
        .done(function(data){
            if(data.isError){
                $("#tests").html("Test data could not be retrieved for this unit.");
            }
            else{
                $.each(data.result, function(i, item){
                    testlink = '<a href="test.html?test='+item.testId+'">';
                    $('#tests').append("<br /> &nbsp; &nbsp; "+testlink+item.name+"</a>");
                });
            }		
            $.getJSON('../../unit_select.php', 
                {unit_id: urlParams.unit})
                .done(function(data){
                    if(data.isError){
                        $("#failure").html("Information for this unit could not be retrieved.");
                    }
                    else{
                        $("#unitname").val(data.result.name);
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
                    }			
            });
    });
}*/
function getUnitInfo(){
    if(urlParams.unit == null){
        $("#unitData").hide();
        $("#failure").html("The unit must be specified. Go to the course page and select a unit to view.");
        showFailure(); 
        return;
    }
	
    $.getJSON('../../unit_select.php', 
        {unit_id: urlParams.unit})
        .done(function(data){
            if(data.isError){
                $("#failure").html("Information for this unit could not be retrieved.");
            }
            else{
                $("#unitname").val(data.result.name);
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
                $.each(data.result.tests, function(i, item){
                    testlink = '<a href="test.html?test='+item.testId+'">';
                    $('#tests').append("<br /> &nbsp; &nbsp; "+testlink+item.name+"</a>");
                });
            }			
    });

}

/*
function getTestInfo(){
    $.getJSON('../../unit_tests.php', 
        {unit_id: urlParams.unit})
        .done(function(data){
            if(data.isError){
                $("#tests").html("Test data could not be retrieved for this unit.");
            }
            else{
                $.each(data.result, function(i, item){
                    testlink = '<a href="test.html?test='+item.testId+'">';
                    $('#tests').append("<br /> &nbsp; &nbsp; "+testlink+item.name+"</a>");
                });
            }		
    });

}*/

function deleteUnit(){
    if(urlParams.unit == null){
        $("#failure").html("The unit to be deleted must be specified. Go to the associated unit's page to delete it.");
        showFailure(); 
        return;
    }
    if(!confirm("Are you sure you want to delete this unit?")){
        return;
    }
    $.post('../../unit_delete.php', 
        { unit_id: urlParams.unit })
        .done(function(data){
        if(data.isError){
            var errorMsg = "Unit could not be deleted: ";
            if(data.errorTitle == "Unit Selection"){
                errorMsg += "Unit does not exist.";
            }
            else if(data.errorTitle == "Unit Deletion"){
                errorMsg += "Please refresh the page and try again.";
            }
            $("#failure").html(errorMsg);
            showFailure();
        }
        else{
            $("#unitData").html("Unit was successfully deleted.");
        }
    });
    return; 
}

function updateUnit(){
    if(urlParams.unit == null){
        $("#failure").html("The unit to be updated must be specified. Go to the associated unit's page to update it.");
        showFailure(); 
        return;
    }
    // should refactor this out into a separate function since create form uses it too
    var unitname = $("#unitname").val();
    var instructions = $("#instructions").val();
    var opendate = $("#opendate").val();
    var closedate = $("#closedate").val();
    
	  if(unitname == "" || instructions == "" || opendate == "" || closedate == ""){
		    $("#failure").html("Please provide unit name, instructions, and open/close dates.");	
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
    $.post('../../unit_update.php', 
        { unit_id: urlParams.unit, name: unitname, open: opendate, close: closedate, message: instructions } )
        .done(function(data){
            if(data.isError){
                var errorMsg = "Unit could not be updated.";
                // not sure of error titles yet
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
                document.location.reload(true);
            }
    });
    return; 
}