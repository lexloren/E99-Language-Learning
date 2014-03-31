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
                    newrow = '<tr><td>' + item.list_name + '</td></tr>';
                    $('#deckDetails').append(newrow);
                });
            }
    }); 
}

function getTestInfo(){
    if(urlParams.unit == null){
        $("#unitData").hide();
        $("#failure").html("The unit must be specified. Go to the course page and select a unit to view.");
        showFailure(); 
        return;
    }

    $.getJSON('../../unit_tests.php', 
        {unit_id: urlParams.unit},
        function(data){
            if(data.isError){
                $("#tests").html("Test data could not be retrieved for this unit.");
            }
            else{
                $.each(data.result, function(i, item){
                    newrow = '<tr><td>' + item.test_name + '</td></tr>';
                    $('#testDetails').append(newrow);
                });
            }
    }); 
}