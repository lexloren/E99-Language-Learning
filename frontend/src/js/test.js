var test;

$(document).ready(function(){
	pageSetup();
	$("#loader").hide();
	$("#test-loader").hide();
	$("#entry-loader").hide();
	$("#dict-loader").hide();
	test = getURLparam('testid');
	if(test === null) {
    $("#testData").hide();
    failureMessage("The test must be specified. Go to a unit page and select a test to view.");
    return;
	} 
  else {
    $("#updateTestForm").hide();
    getTestInfo();
	}
});

function showForm(frm,tohide){
    $(tohide).hide();
    $(frm).show();
    $('html, body').animate({scrollTop: $(frm).offset().top}, "slow");
}

function cancelUpdate(frm,tohide){
    $(frm).hide();
    $(tohide).show();
}

function getTestInfo(){
    $("#loader").show();
    $("#testData").hide();
    $.getJSON('../../test_select.php', 
        {test_id: test},
        function(data){
		        authorize(data);
            if(data.isError){
                failureMessage("Information for this test could not be retrieved.");
            }
            else{
                testheader = '<h3 class="form-signin-heading">Test '+data.result.testId+': '+data.result.name+'</h3>';
                $("#test-header").html(testheader);
                if(data.result.sessionUserPermissions.write == true){
                    $("#testname").val(data.result.name);
                    if(data.result.message != null){
                        $("#testdesc").val(data.result.message);
                    }
                    if(data.result.timeframe != null){
                        if(data.result.timeframe.open != null){
                            opendate = new Date(data.result.timeframe.open);
                            $("#testopendate").val(opendate);
                        }
                        if(data.result.timeframe.close != null){
                            closedate = new Date(data.result.timeframe.close);
                            $("#testclosedate").val(closedate);
                        }
                    }
                    if(data.result.entriesCount == 0){
                        $('#entry-list').html("<em>This test currently has no entries.</em>");
                    }
                    else{
                        $('#entry-list').append('<tbody>');
                        $.each(data.result.entries, function(i, item){
                            count = i + 1;
                            selectid = "entryorder"+item.entryId;
                            entryrow = '<tr><td>'+count+'</td>' +
                                       '<td>'+item.words[item.languages[1]]+'</a></td>' +
                                       '<td>' + item.pronuncations[item.languages[1]] + '</td>' +
                                       '<td>' + item.words[item.languages[0]] + '</td>' +
                                       '<td><select id='+selectid+'>';
                            for(num=1;num<=data.result.entriesCount;num++){
                                if(num == count){
                                    selected = '<option selected="selected">'
                                }
                                else{
                                    selected = '<option>';
                                }
                                entryrow += selected+num+'</option>';
                            }
                            entryrow += '</select> &nbsp; <span class="span-action" onclick="updateOrder('+item.entryId+');">[Update Order]</span></td><td><label class="select-entry-ids"><input type="checkbox" class="rem_entry_ids" name="rem_entry_ids" value='+item.entryId+'>&nbsp; Remove</label></td></tr>';
                            $('#entry-list').append(entryrow);
                        });
                        $('#entry-list').append('<tr><td></td><td></td><td></td><td></td><td></td><td><span class="span-action" onclick="removeEntries();">[Remove Selected Entries]</span></td></tr></tbody>');
                    }
                    $("#test-sitting").hide();
                }
                else{
                    if(data.result.message != null){
                      testintro = '<h4 class="form-signin-heading">'+data.result.message+'</h4>';
                      $("#test-intro").html(testintro);  
                    }
                    $("#test-update").hide();
                    $("#test-entries").hide();
                    $("#question-block").hide();
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
	  $('#failure').hide();
	  $('#success').hide();
    $.post('../../test_delete.php', 
        { test_id: test })
        .done(function(data){
		    authorize(data);
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
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function saveUpdate(){
	  $('#failure').hide();
	  $('#success').hide();
    var testname = $("#testname").val();
    var desc = $("#testdesc").val();
    var opendate = $("#testopendate").val();
    var closedate = $("#testclosedate").val();
    
	  if(testname == "" || desc == "" || opendate == "" || closedate == ""){
		    failureMessage("Please provide test name, instructions, and open/close dates.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }

    opendate = Date.parse(opendate);
    closedate = Date.parse(closedate);

    if(closedate < opendate){
		    failureMessage("Open Date cannot be later than Close Date; please re-select the dates.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }

	  $("#loader").show();
	  $("#testData").hide();
    $.post('../../test_update.php', 
        { test_id: test, name: testname, open: opendate, close: closedate, message: desc } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Test could not be updated. Please reload the page and try again.";
                failureMessage(errorMsg);
            }
            else{
                $("#testname").val(data.result.name);
                $("#testdesc").val(data.result.message);
                if(data.result.timeframe != null){
                    if(data.result.timeframe.open != null){
                        opendate = new Date(data.result.timeframe.open);
                        $("#testopendate").val(opendate);
                    }
                    if(data.result.timeframe.close != null){
                        closedate = new Date(data.result.timeframe.close);
                        $("#testclosedate").val(closedate);
                    }
                }
                successMessage("Test was successfully updated.");
                cancelUpdate("#updateTestForm","#test-update");
                $("#testData").show();
            }
            $("html, body").animate({scrollTop:0}, "slow");  
            $("#loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

<!--- Entries --->

function search_entry(page) {
	  $('#failure').hide();
	  $('#success').hide();
	  $("#dict-loader").show();
	  $('#dictionary').html('');
	  var word = $('#entrysearch').val();
	  var langcodes = $('#lang1').val() + ',' + $('#lang2').val();
	  $.getJSON( '../../entry_find.php', 
               { query : word,
		             langs : langcodes,
		             page_size : 5,
		             page_num : page }, 
              function( data ) {
		              authorize(data);
		              if (data.isError === true || data.result == null) {
                      if(data.errorTitle == "Entry Find" && page > 1){
                          $('#dictionary').html('<br />There are no more matching entries.<br /><br />');
                      }
                      else{
                          $('#dictionary').html('<br />Matching entries could not be found.<br /><br />');
                      }
		              } 
                  else {
			                $('#dictionary').append('<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td><td></td></tr></thead>');
			                $('#dictionary').append('<tbody>');
			                $.each( data.result, function() {
                          if($(".rem_entry_ids:checkbox[value="+this.entryId+"]").length > 0){
                              disabled = " disabled";
                          }
                          else{
                              disabled = "";
                          }
				                  $('#dictionary').append('<tr><td>' + this.words[this.languages[1]] + '</td>' + 
					                                        '<td>' + this.pronuncations[this.languages[1]] + '</td>' + 
					                                        '<td>' + this.words[this.languages[0]] + '</td>' + 
                                                  '<td><label class="select-entry-ids"><input type="checkbox" class="add_entry_ids" name="add_entry_ids" value='+this.entryId+disabled+'>&nbsp; Add</label></td></tr>');
                      });
                      if(data.resultInformation.pageNumber < data.resultInformation.pagesCount){
                          nextpage = page + 1;
                          nextspan = '<span class="span-action" onclick="search_entry('+nextpage+');">[Next Page]</span>';
                      }
                      else{
                          nextspan = '';
                      }
                      if(page > 1){
                          prevpage = page - 1;
                          prevspan = '<span class="span-action" onclick="search_entry('+prevpage+');">[Previous Page]</span> &nbsp; '
                      }
                      else{
                          prevspan = '';
                      }
			                $('#dictionary').append('<tr><td>'+prevspan+nextspan+'</td><td></td><td></td>' +
                                              '<td><span class="span-action" onclick="addEntries();">[Add Selected Entries]</span></td></tr>');
			                $('#dictionary').append('</tbody>');
		              }
                  $('html, body').animate({scrollTop: $("#dictionary").offset().top}, "slow");
	                $("#dict-loader").hide();
	})
  .fail(function(error){
      failureMessage('Something has gone wrong. Please refresh the page and try again.');
      $("html, body").animate({scrollTop:0}, "slow"); 
  });
}

function addEntries(page){
	  $('#failure').hide();
	  $('#success').hide();
    var entriesAdd = $('.add_entry_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../test_entries_add.php', 
        { test_id: test, entry_ids: entriesAdd })
        .done(function(data){
            authorize(data);
            if(data.isError){
                var errorMsg = "Entry/Entries could not be added: ";

                if(data.errorTitle == "Test Selection"){
                    errorMsg += "Test does not exist.";
                }
                else{
                    errorMsg += "Please refresh the page and try again.";
                }
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                $("#dict-add").hide();
                $('.add_entry_ids').prop('checked',false);
                refreshEntries();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function updateOrder(entry){
    var entrysel = $('#entryorder'+entry).find(":selected").text();
	  $('#failure').hide();
	  $('#success').hide();
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entry_update.php', 
        { test_id: test, entry_id: entry, number: entrysel })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Entry order could not be updated: Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshEntries();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function removeEntries(){
	  $('#failure').hide();
	  $('#success').hide();
    var entriesRem = $('.rem_entry_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../test_entries_remove.php', 
        { test_id: test, entry_ids: entriesRem })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Entry/Entries could not be removed: ";
                if(data.errorTitle == "Test Selection"){
                    errorMsg += "Test does not exist.";
                }
                else{
                    errorMsg += "Please refresh the page and try again.";
                }
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshEntries();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function refreshEntries(){
	  $('#failure').hide();
	  $('#success').hide();
    $("#entry-loader").show();
    $("#entry-list").html('');
    $.getJSON('../../test_entries.php', 
        {test_id: test},
        function(data){
		        authorize(data);
            if(data.isError){
                failureMessage("Entries could not be refreshed.");
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                if(data.result == null){
                    $('#entry-list').html("<em>This test currently has no entries.</em>");
                }
                else{
                    $('#entry-list').append('<thead><tr><td></td><td>Word</td><td>Pronunciation</td><td>Translation</td><td></td><td></td></tr></thead><tbody>');
                    $.each(data.result, function(i, item){
                        count = i + 1;
                        selectid = "entryorder"+item.entryId;
                        entryrow = '<tr><td>'+count+'</td>' +
                                   '<td>'+item.words[item.languages[1]]+'</td>' +
                                   '<td>' + item.pronuncations[item.languages[1]] + '</td>' +
                                   '<td>' + item.words[item.languages[0]] + '</td>' +
                                   '<td><select id='+selectid+'>';
                        for(num=1;num<=data.result.length;num++){
                            if(num == count){
                                selected = '<option selected="selected">'
                            }
                            else{
                                selected = '<option>';
                            }
                            entryrow += selected+num+'</option>';
                        }
                        entryrow += '</select> &nbsp; <span class="span-action" onclick="updateOrder('+item.entryId+');">[Update Order]</span></td><td><label class="select-entry-ids"><input type="checkbox" class="rem_entry_ids" name="rem_entry_ids" value='+item.entryId+'>&nbsp; Remove</label></td></tr>';
                        $('#entry-list').append(entryrow);
                    });
                    $('#entry-list').append('<tr><td></td><td></td><td></td><td></td><td></td><td><span class="span-action" onclick="removeEntries();">[Remove Selected Entries]</span></td></tr></tbody>');
                }
            }
            if(!$("#dict-add").is(":visible")){
                $("#dict-add").show();
            }
            $('html, body').animate({scrollTop: $("#test-entries").offset().top}, "slow");
            $("#entry-loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
}

<!--- Test Sitting --->
function startTest(){
    $("#test-loader").show();
    $('#test-start-btn').hide();
    $.post('../../test_execute.php', 
        { test_id: test } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                if(data.errorDescription == "Session user has already responded to all test entries."){
                  var errorMsg = "You have already taken this test!";                    
                }
                else if(data.errorDescription == "Session user cannot execute test because test timeframe is not current."){
                  var errorMsg = "This test cannot be taken because its timeframe is not current.";                    
                }
                else{
                  var errorMsg = "Test could not be started. Please reload the page and try again.";
                }
                failureMessage(errorMsg);
            }
            else{
                $("#q-remainder").html('Questions Remaining: '+data.result.entriesRemainingCount)
                $("#question-body").html(data.result.prompt);
                $("#answer-submit").click(function(){
                    submitAnswer(data.result.testEntryId);
                });
                $("#question-block").show();
            }
            $("#test-loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}

function submitAnswer(id){
    $("#test-loader").show();
    answer = $("#test-answer").val().trim();
    $('#question-block').hide();
    $("#test-answer").val('');
    $.post('../../test_execute.php', 
        { test_id: test, test_entry_id: id, contents: answer } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                console.log(data.errorTitle);
                console.log(data.errorDescription);
                if(data.errorDescription = "Session user has already responded to all test entries."){
                  successMessage("Test is complete and has been submitted.");                  
                }
            }
            else{
                $("#question-number").html('Questions Remaining: '+data.result.entriesRemainingCount);
                $("#question-body").html(data.result.prompt);
                $("#answer-submit").click(function(){
                     submitAnswer(data.result.testEntryId);                   
                });
                $("#question-block").show();
            }
            $("#test-loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}