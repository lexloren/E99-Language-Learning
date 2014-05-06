var sitting;

$(document).ready(function(){
	pageSetup();
	$("#loader").hide();
	$("#response-loader").hide();
	sitting = getURLparam('sittingid');
	if(sitting === null) {
    $("#sittingData").hide();
    failureMessage("The sitting must be specified. Go to a test page and select a sitting to view.");
    return;
	} 
  else {
    getSittingInfo();
	}
});

function getSittingInfo(){
    $("#loader").show();
    $("#sittingData").hide();
    $.getJSON('../../sitting_select.php', 
        {sitting_id: sitting},
        function(data){
		        authorize(data);
            if(data.isError || data.result.owner == null){
                failureMessage("Information for this sitting could not be retrieved.");
            }
            else{
                feedback = '';
                if(data.result.message != null)
                    feedback = data.result.message;
                $('#sitting-details').append('<br /><strong>Student:</strong> '+data.result.student.handle+'<br />');
                $('#sitting-details').append('<strong>Test Number: </strong>'+data.result.testId+'<br />');
                if(data.result.score != null){
                    $('#sitting-details').append('<strong>Score: </strong>'+data.result.score.scoreScaled+'<br />');
                }    
                $('#sitting-details').append('<label for="feedback">Feedback for Student:</label>');
                if(data.result.owner.isSessionUser == true){
                    $('#sitting-details').append('<textarea class="form-control container-small" id="feedback" rows="3">'+feedback+'</textarea><br />');
                    $('#sitting-details').append('<button class="btn btn-primary" type="button" onclick="updateFeedback();">Update</button><br /><br />');
                }
                else{
                    $('#sitting-details').append(feedback+'<br />');
                }
                if(data.result.responses == null){
                    $('#response-list').html("<em>Cannot access response data.</em>");                    
                }
                else if(data.result.responsesCount == 0){
                    $('#response-list').html("<em>This sitting currently has no responses.</em>");
                }
                else{
                    $('#response-list').append('<tbody>');
                    $.each(data.result.responses, function(i, item){
                        resp = '<em>None</em>';
                        if(item.pattern.contents != null)
                            resp = item.pattern.contents;
                        note = '';
                        if(item.pattern.message != null)
                            note = item.pattern.message;
                        responserow = '<tr id="responserow'+item.responseId+'"><td>'+resp+'</td>' +
                                      '<td>'+item.pattern.score+'</td><td>'+note+'</td>';
                        $('#response-list').append(responserow);
                    });
                    $('#response-list').append('</tbody>');
                }
                if(data.result.owner.isSessionUser == true){  
                    $("#doc-body").append('<a href="test.html?testid='+data.result.testId+'" style="text-decoration:none;"><span class="glyphicon glyphicon-arrow-left span-action" title="Return to test"></span>&nbsp; Return to test</a><br />&nbsp; ');
                }   
                $("#sittingData").show();
            }		
            $("#loader").hide();
    })
	 .fail(function(error) {
        $("#loader").hide();
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
}

function updateFeedback(){
	  $('#failure').hide();
	  $('#success').hide();

    var feedback = $("#feedback").val();

    $("#loader").show();
    $("#sittingData").hide();

    $.post('../../sitting_update.php', 
        { sitting_id: sitting, message: feedback })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Feedback could not be updated: Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                feedback = '';
                if(data.result.message != null)
                    feedback = data.result.message;
                $('#feedback').val(feedback);
                $('#loader').hide();
                $('#sittingData').show();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return;
}