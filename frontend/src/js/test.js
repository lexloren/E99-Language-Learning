var test;
var modevalues = new Array();

$(document).ready(function(){
	pageSetup();
	$("#loader").hide();
	$("#test-loader").hide();
	$("#sitting-loader").hide();
  $("#deck-loader").hide(); 
	$("#entry-loader").hide();
	$("#dict-loader").hide();
	$("#choice-loader").hide();
	test = getURLparam('testid');
	if(test === null) {
    $("#testData").hide();
    failureMessage("The test must be specified. Go to a unit page and select a test to view.");
    return;
	} 
  else {
    $("#test-sitting").hide();
    $("#prev-question").hide();
    $("#updateTestForm").hide();
    $("#entry-addition").hide();
    $("#dict-add").hide();
    $("#list-add").hide();
    $("#word-search").hide();
    $("#owner-search").hide();
    $("#multi-choice").hide();
    getModes();
    getTestInfo();
	}
});

function showSearch(search){
    $('#word-search').hide();
    $('#owner-search').hide();
    $('#searchHeader').html('');
    $('#searchResults').html('');
    $('#searchListForm')[0].reset();
    $(search).show();
}

function showForm(frm,tohide){
    $(tohide).hide();
    $(frm).show();
    $('html, body').animate({scrollTop: $(frm).offset().top}, "slow");
}

function toggleSearch(search){
    if(search == "#dict-add"){
      $('#list-add').hide();
      $('#word-search').hide();
      $('#owner-search').hide();
      $('#searchHeader').html('');
      $('#searchResults').html('');
      $('#searchListForm')[0].reset();  
      $('#dict-add').show();
      $('#entry-addition').html('<strong>[Add entries from dictionary]</strong> &nbsp; <span class="span-action" onclick="toggleSearch(\'#list\-add\')">[Add entries from flashcard decks]</span><br /> &nbsp; ');     
    }
    else{
      $('#dict-add').hide();
      $('#dict-search')[0].reset();
      $('#dictionary').html('');
      $('#list-add').show();
      $('#entry-addition').html('<strong>[Add entries from flashcard decks]</strong> &nbsp; <span class="span-action" onclick="toggleSearch(\'#dict\-add\')">[Add entries from dictionary]</span><br /> &nbsp; ');     
    }
}

function getLangs(){
    $.getJSON('../../language_enumerate.php')
        .done(function(data){
            authorize(data);
            if(data.isError){
                failureMessage("Word search could not be initialized.");
            }
            else{
                $.each(data.result, function(i, item){           
                    $('#entry-lang1').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                    $('#entry-lang2').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                    $('#deck-lang1').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                    $('#deck-lang2').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                });
            }
    })
    .fail(function(error) {
        failureMessage('Something has gone wrong. Please refresh the page and try again.');
    });    
}

function getModes(){
    $.getJSON('../../mode_enumerate.php')
        .done(function(data){
            authorize(data);
            if(data.isError){
                failureMessage("Test display could not be initialized.");
            }
            else{
                $.each(data.result, function(i, item){           
                    modevalues[i] = new Array();
                    modevalues[i][0] = item.modeId;
                    modevalues[i][1] = item.directionFrom + '/' + item.directionTo;
                });
            }
    })
    .fail(function(error) {
        failureMessage('Something has gone wrong. Please refresh the page and try again.');
    });    
}

function getSittings(){
    $.getJSON('../../user_sittings.php')
        .done(function(data){
            authorize(data);
            if(data.isError){
                failureMessage("Information for this test could not be retrieved.");
            }
            else{
                $.each(data.result, function(i, item){           
                    if(item.testId == test && item.live == false){
                        window.location.replace("sitting.html?sittingid="+item.sittingId);
                    }
                });
            }
    })
    .fail(function(error) {
        failureMessage('Something has gone wrong. Please refresh the page and try again.');
    }); 
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
                // temporary workaround for permissions
                if(data.errorDescription == "Session user cannot select test."){
                    getSittings();
                    $("#test-update").hide();
                    $("#search-list").hide();
                    $("#student-sittings").hide();
                    $("#test-entries").hide();
                    $("#question-block").hide();
                    $("#test-sitting").show();                
                }
                else{
                    failureMessage("Information for this test could not be retrieved.");
                    return;
                }
            }
            // temporary workaround for permissions
            else if(data.result.sessionUserPermissions.read != true && data.result.sessionUserPermissions.execute != true){
                    failureMessage("Information for this test could not be retrieved.");
            }
            else{
                if(data.result.name != null)
                    tname = data.result.name;
                else
                    tname = '';
                testheader = '<h3 class="form-signin-heading">Test '+data.result.testId+': '+tname+'</h3>';
                $("#test-header").html(testheader);
                if(data.result.sessionUserPermissions.read == true){
                    if(data.result.sessionUserPermissions.write == true){
                        $("#entry-addition").show();
                        $("#dict-add").show();
                        $("#testname").val(data.result.name);
                        if(data.result.message != null){
                            $("#testdesc").val(data.result.message);
                        }
                        if(data.result.timer != null){
                            $("#timer").val(data.result.timer/60);
                        }
                        if(data.result.timeframe != null){
                            if(data.result.timeframe.open != null){
                                opendate = prettyDate(new Date(data.result.timeframe.open*1000));
                                $("#testopendate").val(opendate);
                            }
                            if(data.result.timeframe.close != null){
                                closedate = prettyDate(new Date(data.result.timeframe.close*1000));
                                $("#testclosedate").val(closedate);
                            }
                        }
                    }
                    else{
                        $('#test-update').html('<br />');
                    }
                    if(data.result.entriesCount == 0){
                        $('#entry-list').html("<em>This test currently has no entries.</em>");
                    }
                    else{
                        if(data.result.sessionUserPermissions.write == true){
                            entryHeader = '<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td><td>Order</td>';
                            entryHeader += '<td>Mode &nbsp; <span id="mode\-info" class="glyphicon glyphicon-comment span-action" data-toggle="tooltip" data-placement="bottom" title="Mode refers to the Question/Answer type.<br/>';
                            for(num=0;num<modevalues.length;num++){
                                entryHeader += modevalues[num][0]+': '+modevalues[num][1]+'<br />';
                            }
                            entryHeader += '"></span></td><td>Multiple Choice</td><td></td></tr></thead><tbody>';
                            $('#entry-list').append(entryHeader);
                            $('#mode-info').tooltip({html: true});
                            $.each(data.result.entries, function(i, item){
                                count = i + 1;
                                orderselect = "entryorder"+item.entryId;
                                modeselect = "modeorder"+item.entryId;
                                entryrow = '<tr><td>'+item.words[item.languages[1]]+'</td>' +
                                           '<td>' + item.pronuncations[item.languages[1]] + '</td>' +
                                           '<td>' + item.words[item.languages[0]] + '</td>' +
                                           '<td><select id='+orderselect+'>';
                                for(num=1;num<=data.result.entriesCount;num++){
                                    if(num == count)
                                        selected = '<option selected="selected">'
                                    else
                                        selected = '<option>';
                                    entryrow += selected+num+'</option>';
                                }
                                entryrow += '</select> &nbsp; <span class="span-action" onclick="updateOrder('+item.entryId+');">[Update]</span></td>';
                                entryrow += '<td><select id='+modeselect+'>';
                                for(num=0;num<modevalues.length;num++){
                                    if(modevalues[num][0] == item.mode.modeId)
                                        selected = '<option value="'+modevalues[num][0]+'" selected="selected">';
                                    else
                                        selected = '<option value="'+modevalues[num][0]+'">';
                                    entryrow += selected;
                                    entryrow += modevalues[num][0];
                                    entryrow += '</option>';
                                }
                                entryrow += '</select> &nbsp; <span class="span-action" onclick="updateMode('+item.entryId+');">[Update]</span></td>';
                                entryrow += '<td><span class="glyphicon glyphicon-pencil span-action" onclick="showOptions('+item.entryId+');" title="Update multiple-choice options"></span></td>';
                                entryrow += '<td><label class="select-entry-ids"><input type="checkbox" class="rem_entry_ids" name="rem_entry_ids" value='+item.entryId+'>&nbsp; Remove</label></td></tr>';
                                $('#entry-list').append(entryrow);
                            });
                            $('#entry-list').append('<tr><td></td><td></td><td></td><td><span class="span-action" onclick="randOrder();">[Randomize Order]</span></td><td><span class="span-action" onclick="randMode();">[Randomize Mode]</span></td><td></td><td><span class="span-action" onclick="removeEntries();">[Remove Selected Entries]</span></td></tr></tbody>');
                        }
                        else{
                            entryHeader = '<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td><td>Order</td>';
                            entryHeader += '<td>Mode &nbsp; <span id="mode\-info" class="glyphicon glyphicon-comment span-action" data-toggle="tooltip" data-placement="bottom" title="Mode refers to the Question/Answer type.<br/>';
                            for(num=0;num<modevalues.length;num++){
                                entryHeader += modevalues[num][0]+': '+modevalues[num][1]+'<br />';
                            }
                            entryHeader += '"></span></td></tr></thead><tbody>';
                            $('#entry-list').append(entryHeader);
                            $('#mode-info').tooltip({html: true});
                            $.each(data.result.entries, function(i, item){
                                count = i + 1;
                                entryrow = '<tr><td>'+item.words[item.languages[1]]+'</td>' +
                                           '<td>' + item.pronuncations[item.languages[1]] + '</td>' +
                                           '<td>' + item.words[item.languages[0]] + '</td>' +
                                           '<td>' + count + '</td><td>'+item.mode.modeId+'</td></tr>';
                                $('#entry-list').append(entryrow);
                            });
                            $('#entry-list').append('</tbody>');
                        }
                    }
                    if(data.result.gradesDisclosed == false){
                        spanDisclose = '<span class="span-action" onclick="discloseScores(1);">[Show Scores to Students]</span>';
                    }
                    else{
                        spanDisclose = '<span class="span-action" onclick="discloseScores(0);">[Hide Scores from Students]</span>'
                    }
                    if(data.result.sittingsCount > 0){
                        $('#sitting-list').append('<tbody>');
                        $.each(data.result.sittings, function(i, item){
                            sittingrow = '<tr id="sittingrow'+item.sittingId+'"><td><a href="sitting.html?sittingid='+item.sittingId+'">'+item.student.handle+'</a></td>' +
                                         '<td>'+item.responsesCount+'</td>' +
                                         '<td>'+item.score.scoreScaled+'</td>' +
                                         '<td><span class="glyphicon glyphicon-trash span-action" onclick="deleteSitting('+item.sittingId+');" title="Delete this sitting"></span></tr>';
                            $('#sitting-list').append(sittingrow);
                        });
                        $('#sitting-list').append('<tr><td></td><td></td><td id="disclose">'+spanDisclose+'</td><td><span class="span-action" onclick="clearTest();">[Delete All Sittings]</span></td></tr></tbody>');
                    }
                    else{
                        $('#sitting-list').html('<div id="disclose">'+spanDisclose+'</div><br />');
                    }
                    if(data.result.studentsMissingSittings.length > 0){
                        $('#sitting-list').append('<div id="nonsitting" class="help-block-small container-small alert alert-warning"><strong>These students have not yet taken the test:</strong><br />');
                        $.each(data.result.studentsMissingSittings, function(i, item){
                            $('#nonsitting').append(item.handle+'<br />');
                        });
                        $('#nonsitting').append('</div>');
                    }
                    getLangs();
                    $("#doc-body").append('<a href="unit.html?unitid='+data.result.unitId+'" style="text-decoration:none;"><span class="glyphicon glyphicon-arrow-left span-action" title="Return to unit"></span>&nbsp; Return to unit</a><br />&nbsp; ');
                }
                else if(data.result.sessionUserPermissions.execute == true){
                    if(data.result.message != null){
                      testintro = '<h4 class="form-signin-heading">'+data.result.message+'</h4>';
                      $("#test-intro").html(testintro);  
                    }
                    if(data.result.timer != null){
                      timer = data.result.timer/60;
                      $('#test-intro').append('<br /><em>You will have '+timer+' minutes to complete this test. Good luck!</em>');
                    }
                    $("#test-update").hide();
                    $("#search-list").hide();
                    $("#student-sittings").hide();
                    $("#test-entries").hide();
                    $("#question-block").hide();    
                    $("#test-siting").show();              
                }
            }		
            $("#testData").show();
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
            var errorMsg = "Test could not be deleted. Please refresh the page and try again.";
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

function discloseScores(flag){
    if(flag == 0){
        msg = "This will hide scores for this test from students. Continue?";
    }
    else{
        msg = "This will allow students to see their scores on this test. Continue?";
    }
    if(!confirm(msg)){
        return;
    }
	  $('#failure').hide();
	  $('#success').hide();

    $.post('../../test_update.php', 
        { test_id: test, disclosed: flag } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Test could not be updated. Please reload the page and try again.";
                failureMessage(errorMsg);
            }
            else{
                if(flag == 1)
                    $("#disclose").html('<span class="span-action" onclick="discloseScores(0);">[Hide Scores from Students]</span>');
                else
                    $("#disclose").html('<span class="span-action" onclick="discloseScores(1);">[Show Scores to Students]</span>');
            }
            $("html, body").animate({scrollTop:0}, "slow");  
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
    var timer = $("#timer").val();
    
	  if(testname == "" || desc == "" || opendate == "" || closedate == ""){
		    failureMessage("Please provide test name, instructions, and open/close dates.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }
    if(isNaN(timer) || timer == ""){
		    failureMessage("Please provide a valid number for test timer.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }
    else{
        timer = timer * 60;
    }

    opendate = Date.parse(opendate)/1000;
    closedate = Date.parse(closedate)/1000;

    if(closedate < opendate){
		    failureMessage("Open Date cannot be later than Close Date; please re-select the dates.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }

	  $("#loader").show();
	  $("#testData").hide();
    $.post('../../test_update.php', 
        { test_id: test, name: testname, open: opendate, close: closedate, timer: timer, message: desc } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Test could not be updated. Please reload the page and try again.";
                failureMessage(errorMsg);
            }
            else{
                $("#testname").val(data.result.name);
                $("#testdesc").val(data.result.message);
                $("#timer").val(data.result.timer/60);
                if(data.result.timeframe != null){
                    if(data.result.timeframe.open != null){
                        opendate = prettyDate(new Date(data.result.timeframe.open*1000));
                        $("#testopendate").val(opendate);
                    }
                    if(data.result.timeframe.close != null){
                        closedate = prettyDate(new Date(data.result.timeframe.close*1000));
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
	  var langcodes = $('#entry-lang1').val() + ',' + $('#entry-lang2').val();
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
                      modeselect = '<select id="deckmode"><option value="">Select a mode (Question/Answer type)</options>';
                      for(num=0;num<modevalues.length;num++)
                          modeselect += '<option value="'+modevalues[num][0]+'">'+modevalues[num][1]+'</option>';
                      modeselect += '</select> &nbsp;';
			                $('#dictionary').append('<tr><td>'+prevspan+nextspan+'</td><td></td><td></td>' +
                                              '<td>'+modeselect+'<span class="span-action" onclick="addEntries();">[Add Selected Entries]</span></td></tr>');
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
    mode = $('#deckmode').val();
    $('#entry-addition').hide();
    $('#dict-add').hide();
    var entriesAdd = $('.add_entry_ids:checked').map(function () {
      return this.value;
    }).get().join(",");
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entries_add.php', 
        { test_id: test, entry_ids: entriesAdd, mode: mode })
        .done(function(data){
            authorize(data);
            if(data.isError){
                var errorMsg = "Entry/Entries could not be added. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                $('.add_entry_ids').prop('checked',false);
                refreshEntries('#dict-add');
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
                refreshEntries('');
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
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entries_remove.php', 
        { test_id: test, entry_ids: entriesRem })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Entry/Entries could not be removed. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshEntries('');
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function updateMode(entry){
    var modesel = $('#modeorder'+entry).find(":selected").val();
	  $('#failure').hide();
	  $('#success').hide();
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entry_update.php', 
        { test_id: test, entry_id: entry, mode: modesel })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Entry mode could not be updated: Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshEntries('');
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return;
}

function randMode(){
	  $('#failure').hide();
	  $('#success').hide();
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entries_randomize.php', 
        { test_id: test, remode: 1 })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Entry mode could not be randomized: Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshEntries('');
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}
function randOrder(){
	  $('#failure').hide();
	  $('#success').hide();
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entries_randomize.php', 
        { test_id: test, renumber: 1 })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Entry order could not be randomized: Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshEntries('');
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function updateChoice(pattern, entry){
	  $('#failure').hide();
	  $('#success').hide();

    var newscore = $("#newscore"+pattern).val();
    var feedback = $("#feedback"+pattern).val();
	  if(isNaN(newscore)){
		    failureMessage("Please provide a valid score.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }

    $("#choice-loader").show();
    $('#multi-choice').hide();

    $.post('../../test_entry_pattern_update.php', 
        { pattern_id: pattern, score: newscore, message: feedback })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Choice could not be updated. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                showOptions(entry);
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return;
}

function deletePattern(pattern, entry){
    if(!confirm("Are you sure you want to delete this choice?")){
        return;
    }
	  $('#failure').hide();
	  $('#success').hide();
    $("#multi-choice").hide();

    $.post('../../test_entry_options_remove.php', 
        { pattern_id: pattern })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Choice could not be removed. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                showOptions(entry);
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return;
}

function addPattern(entry){
	  $('#failure').hide();
	  $('#success').hide();

    newchoice = $('#newchoice').val().trim();
	  if(newchoice == ""){
		    failureMessage("Please provide new choice text.");	
        $("html, body").animate({scrollTop:0}, "slow"); 
        return;
    }

    $("#choice-loader").show();
    $("#multi-choice").hide();
    $.post('../../test_entry_options_add.php', 
        { test_id: test, entry_id: entry, contents: newchoice })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Choice could not be added. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                showOptions(entry);
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return;
}

function showOptions(id){
    $('#choice-loader').show();
    $('#entry-list').hide();
    $('#choice-note').html('');
    $('#choice-list').html('');
    $('#choiceform').html('');
	  $('#failure').hide();
	  $('#success').hide();
	  $.getJSON( '../../test_entry_options.php', 
               { test_id: test, entry_id: id }, 
               function( data ) {
		              authorize(data);
		              if (data.isError === true || data.result == null) {
                      failureMessage('Something has gone wrong. Please refresh the page and try again.');
                      $("html, body").animate({scrollTop:0}, "slow"); 
                                  } 
                  else {
                      if(data.result.length <= 1)
                          $('#choice-note').html('This is not currently a multiple-choice question. Add additional choices to make the question multiple-choice.<br />');
                      else
                          $('#choice-note').html('This is currently a multiple-choice question. Remove extra choices to make the question free-form.<br />');
			                $('#choice-list').append('<thead><tr><td>Choice</td><td>Note &nbsp; <span id="note\-info" class="glyphicon glyphicon-comment span-action" data-toggle="tooltip" data-placement="top" title="Notes are shown in test results to students who selected the choice."</td><td>Score</td><td></td></tr></thead><tbody>');
                      $('#note-info').tooltip({html: true});
			                $.each( data.result, function(i,item) {
                          feedback = '';
                          if(item.message != null)
                              feedback = item.message;
				                  $('#choice-list').append('<tr><td>'+item.contents+'</td>' + 
                                                   '<td><textarea class="form-control" id="feedback'+item.patternId+'" rows="2">'+feedback+'</textarea></td>' +
                                                   '<td><input id="newscore'+item.patternId+'" type="text" value="'+item.score+'"</input> &nbsp; <span class="span-action" onclick="updateChoice('+item.patternId+','+id+');">[Update]</span></td>' +
                                                   '<td><span class="glyphicon glyphicon-trash span-action" onclick="deletePattern('+item.patternId+','+id+');" title="Delete this choice"></span></td></tr>');
                      });
			                $('#choice-list').append('<tr><td></td><td></td><td></td></tr>');
			                $('#choice-list').append('</tbody>');
                      $('#choiceform').append('<div class="form-group"><input type="text" class="form-control" id="newchoice" placeholder="Enter new choice"></div>');
                      $('#choiceform').append('&nbsp; <button class="btn btn-primary" type="button" onclick="addPattern('+id+');">Add</button><br />');
		              }
                  //$('html, body').animate({scrollTop: $("#searchResults")}, "slow");
	                $("#multi-choice").show();
                  $('#choice-loader').hide();
	})
  .fail(function(error){
      failureMessage('Something has gone wrong. Please refresh the page and try again.');
      $("html, body").animate({scrollTop:0}, "slow"); 
  });    
}

function hideOptions(){
  $('#multi-choice').hide();
  $('#entry-list').show();
}

function refreshEntries(div){
	  $('#failure').hide();
	  $('#success').hide();
    $('#multi-choice').hide();
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
                if(data.result == null || data.result.length == 0){
                    $('#entry-list').html("<em>This test currently has no entries.</em>");
                }
                else{
                    $('#entry-list').html('');
                    entryHeader = '<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td><td>Order</td>';
                    entryHeader += '<td>Mode &nbsp; <span id="mode\-info" class="glyphicon glyphicon-comment span-action" data-toggle="tooltip" data-placement="bottom" title="Mode refers to the Question/Answer type.<br/>';
                    for(num=0;num<modevalues.length;num++){
                        entryHeader += modevalues[num][0]+': '+modevalues[num][1]+'<br />';
                    }
                    entryHeader += '"></span></td><td>Multiple Choice</td><td></td></tr></thead><tbody>';
                    $('#entry-list').append(entryHeader);
                    $('#mode-info').tooltip({html: true});
                    $.each(data.result, function(i, item){
                        count = i + 1;
                        orderselect = "entryorder"+item.entryId;
                        modeselect = "modeorder"+item.entryId;
                        entryrow = '<tr><td>'+item.words[item.languages[1]]+'</td>' +
                                   '<td>' + item.pronuncations[item.languages[1]] + '</td>' +
                                   '<td>' + item.words[item.languages[0]] + '</td>' +
                                   '<td><select id='+orderselect+'>';
                        for(num=1;num<=data.result.length;num++){
                            if(num == count){
                                selected = '<option selected="selected">'
                            }
                            else{
                                selected = '<option>';
                            }
                            entryrow += selected+num+'</option>';
                        }
                        entryrow += '</select> &nbsp; <span class="span-action" onclick="updateOrder('+item.entryId+');">[Update]</span></td>';
                        entryrow += '<td><select id='+modeselect+'>';
                        for(num=0;num<modevalues.length;num++){
                            if(modevalues[num][0] == item.mode.modeId)
                                selected = '<option value="'+modevalues[num][0]+'" selected="selected">';
                            else
                                selected = '<option values="'+modevalues[num][0]+'">';
                            entryrow += selected+modevalues[num][0]+'</option>';
                        }
                        entryrow += '</select> &nbsp; <span class="span-action" onclick="updateMode('+item.entryId+');">[Update]</span></td>';
                        entryrow += '<td><span class="glyphicon glyphicon-pencil span-action" onclick="showOptions('+item.entryId+');" title="Update multiple-choice options"></span></td>';
                        entryrow += '<td><label class="select-entry-ids"><input type="checkbox" class="rem_entry_ids" name="rem_entry_ids" value='+item.entryId+'>&nbsp; Remove</label></td></tr>';
                        $('#entry-list').append(entryrow);
                    });
                    $('#entry-list').append('<tr><td></td><td></td><td></td><td><span class="span-action" onclick="randOrder();">[Randomize Order]</span></td><td><span class="span-action" onclick="randMode();">[Randomize Mode]</span></td><td></td><td><span class="span-action" onclick="removeEntries();">[Remove Selected Entries]</span></td></tr></tbody>');
                    $('#mode-info').tooltip({html: true});
                }
            }
            if(div != "")
              $(div).show();
            $('#entry-addition').show();
            $('html, body').animate({scrollTop: $("#test-entries").offset().top}, "slow");
            $("#entry-loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
}

<!--- Lists --->
function myDecks() {
	  $('#failure').hide();
	  $('#success').hide();
    $('#word-search').hide();
    $('#owner-search').hide();
	  $("#deck-loader").show();
    $('#searchHeader').html('');
	  $('#searchResults').html('');
	  $.getJSON( '../../user_lists.php', 
               function( data ) {
		              authorize(data);
		              if (data.isError === true || data.result == null) {
                    $('#searchResults').html('<br />You do not have any decks.</br>');
		              } 
                  else {
                      $('#searchHeader').html('<h5>Your Decks</h5>');
			                $('#searchResults').append('<thead><tr><td>Name</td><td>Card Count</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function(i,item) {
                          if(item.name != null)
                              lname = item.name;
                          else
                              lname = '<em>unnamed</em>';
				                  $('#searchResults').append('<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' + 
                                                     '<td>'+item.entriesCount+'</td>' +
                                                     '<td><label class="select-entry-ids"><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+this.listId+'>&nbsp; Add</label></td></tr>');
                      });
                      modeselect = '<select id="deckmode"><option value="">Select a mode (Question/Answer type)</options>';
                      for(num=0;num<modevalues.length;num++)
                          modeselect += '<option value="'+modevalues[num][0]+'">'+modevalues[num][1]+'</option>';
                      modeselect += '</select> &nbsp;';
			                $('#searchResults').append('<tr><td></td><td></td><td>'+modeselect+'<span class="span-action" onclick="addLists();">[Add Entries From Selected Decks]</span></td></tr>');
			                $('#searchResults').append('</tbody>');
		              }
                  //$('html, body').animate({scrollTop: $("#searchResults")}, "slow");
	                $("#deck-loader").hide();
	})
  .fail(function(error){
      failureMessage('Something has gone wrong. Please refresh the page and try again.');
      $("html, body").animate({scrollTop:0}, "slow"); 
  });
}

function searchOwner() {
	  $('#failure').hide();
	  $('#success').hide();
	  $("#deck-loader").show();
    $('#searchHeader').html('');
	  $('#searchResults').html('');
	  var criteria = $('#ownerCriteria').val().trim();
	  $.getJSON( '../../list_find.php', 
               { user_query : criteria }, 
               function( data ) {
		              authorize(data);
		              if (data.isError === true || data.result == null) {
                    $('#searchResults').html('Matching entries could not be found.</br>');
		              } 
                  else {
                      $('#searchHeader').html('<h5>Search Results</h5>');
			                $('#searchResults').append('<thead><tr><td>Name</td><td>Card Count</td><td>Owner</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function(i,item) {
                          if(item.name != null)
                              lname = item.name;
                          else
                              lname = '<em>unnamed</em>';
				                  $('#searchResults').append('<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' + 
                                                     '<td>'+item.entriesCount+'</td>' +
                                                     '<td>'+item.owner.handle+'</td>' +
                                                     '<td><label class="select-entry-ids"><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+this.listId+'>&nbsp; Add</label></td></tr>');
                      });
                      modeselect = '<select id="deckmode"><option value="">Select a mode</options>';
                      for(num=0;num<modevalues.length;num++)
                          modeselect += '<option value="'+modevalues[num][0]+'">'+modevalues[num][1]+'</option>';
                      modeselect += '</select> &nbsp;';
			                $('#searchResults').append('<tr><td></td><td></td><td></td><td>'+modeselect+'<span class="span-action" onclick="addLists();">[Add Entries From Selected Decks]</span></td></tr>');
			                $('#searchResults').append('</tbody>');
		              }
                  //$('html, body').animate({scrollTop: $("#searchResults")}, "slow");
	                $("#deck-loader").hide();
	})
  .fail(function(error){
      failureMessage('Something has gone wrong. Please refresh the page and try again.');
      $("html, body").animate({scrollTop:0}, "slow"); 
  });
}

function searchWord() {
	  $('#failure').hide();
	  $('#success').hide();
    var entriesAdd = $('.add_entry_deck_ids:checked').map(function () {
      return this.value;
    }).get().join(",");
	  $("#deck-loader").show();
    $('#searchHeader').html('');
	  $('#searchResults').html('');
	  $.getJSON( '../../list_find.php', 
               { entry_ids : entriesAdd }, 
               function( data ) {
		              authorize(data);
		              if (data.isError === true || data.result == null || data.result.length == 0) {
                    $('#searchResults').html('Matching lists could not be found.</br>');
		              } 
                  else {
                      $('#searchHeader').html('<h5>Search Results</h5>');
			                $('#searchResults').append('<thead><tr><td>Name</td><td>Card Count</td><td>Owner</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function(i,item) {
                          if(item.name != null)
                              lname = item.name;
                          else
                              lname = '<em>unnamed</em>';
				                  $('#searchResults').append('<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' + 
                                                     '<td>'+item.entriesCount+'</td>' +
                                                     '<td>'+item.owner.handle+'</td>' +
                                                     '<td><label class="select-entry-ids"><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+this.listId+'>&nbsp; Add</label></td></tr>');
                      });
                      modeselect = '<select id="deckmode"><option value="">Select a mode</options>';
                      for(num=0;num<modevalues.length;num++)
                          modeselect += '<option value="'+modevalues[num][0]+'">'+modevalues[num][1]+'</option>';
                      modeselect += '</select> &nbsp;';
			                $('#searchResults').append('<tr><td></td><td></td><td></td><td>'+modeselect+'<span class="span-action" onclick="addLists();">[Add Entries From Selected Decks]</span></td></tr>');
			                $('#searchResults').append('</tbody>');
		              }
                  //$('html, body').animate({scrollTop: $("#searchResults").offset().top}, "slow");
	                $("#deck-loader").hide();
	})
  .fail(function(error){
      failureMessage('Something has gone wrong. Please refresh the page and try again.');
      $("html, body").animate({scrollTop:0}, "slow"); 
  });
}

function search_entry_deck(page) {
	  $('#failure').hide();
	  $('#success').hide();
	  $("#deck-loader").show();
    $('#searchHeader').html('');
	  $('#searchResults').html('');
	  var word = $('#wordsearch').val();
	  var langcodes = $('#deck-lang1').val() + ',' + $('#deck-lang2').val();
	  $.getJSON( '../../entry_find.php', 
               { query : word,
		             langs : langcodes,
		             page_size : 5,
		             page_num : page }, 
              function( data ) {
		              authorize(data);
		              if (data.isError === true || data.result == null) {
                      if(data.errorTitle == "Entry Find" && page > 1){
                          $('#searchResults').html('There are no more matching entries.<br />');
                      }
                      else{
                          $('#searchResults').html('Matching entries could not be found.<br />');
                      }
		              } 
                  else {
                      $('#searchHeader').html('<h5>Search Results</h5>');
			                $('#searchResults').append('<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function() {
				                  $('#searchResults').append('<tr><td>' + this.words[this.languages[1]] + '</td>' + 
					                                        '<td>' + this.pronuncations[this.languages[1]] + '</td>' + 
					                                        '<td>' + this.words[this.languages[0]] + '</td>' + 
                                                  '<td><label class="select-entry-ids"><input type="checkbox" class="add_entry_deck_ids" name="add_entry_deck_ids" value='+this.entryId+'>&nbsp; Select</label></td></tr>');
                      });
                      if(data.resultInformation.pageNumber < data.resultInformation.pagesCount){
                          nextpage = page + 1;
                          nextspan = '<span class="span-action" onclick="search_entry_deck('+nextpage+');">[Next Page]</span>';
                      }
                      else{
                          nextspan = '';
                      }
                      if(page > 1){
                          prevpage = page - 1;
                          prevspan = '<span class="span-action" onclick="search_entry_deck('+prevpage+');">[Previous Page]</span> &nbsp; '
                      }
                      else{
                          prevspan = '';
                      }
			                $('#searchResults').append('<tr><td>'+prevspan+nextspan+'</td><td></td><td></td>' +
                                              '<td><span class="span-action" onclick="searchWord();">[Find lists containing selected words]</span></td></tr>');
			                $('#searchResults').append('</tbody>');
		              }
                  //$('html, body').animate({scrollTop: $("#searchResults").offset().top}, "slow");
	                $("#deck-loader").hide();
	})
  .fail(function(error){
      failureMessage('Something has gone wrong. Please refresh the page and try again.');
      $("html, body").animate({scrollTop:0}, "slow"); 
  });
}

function addLists(){
	  $('#failure').hide();
	  $('#success').hide();
    mode = $('#deckmode').val();
    $('#entry-addition').hide();
    $('#list-add').hide();
    var listsAdd = $('.add_list_ids:checked').map(function () {
      return this.value;
    }).get().join(",");
    $("#entry-loader").show();
    $("#entry-list").html('');

    $.post('../../test_entries_add.php', 
        { test_id: test, list_ids: listsAdd, mode: mode })
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
                $('.add_list_ids').prop('checked',false);
                refreshEntries('#list-add');
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

<!--- Test Sitting --->

function getFreeformPrompt(mode){
  switch(mode){
    case 0:
      return "Enter the translation";
    case 1:
      return "Enter the translation";
    case 2:
      return "Enter the pronunciation";
    case 3:
      return "Enter the associated word in translation";  
    case 4:
      return "Enter the associated word (without translating)";  
    case 5:
      return "Enter the pronunciation";  
    case 6:
      return "Enter the translation";  
  }
}

function getOptionPrompt(mode){
  switch(mode){
    case 0:
      return "Select the translation";
    case 1:
      return "Select the translation";
    case 2:
      return "Select the pronunciation";
    case 3:
      return "Select the associated word";  
    case 4:
      return "Select the associated word";  
    case 5:
      return "Select the pronunciation";  
    case 6:
      return "Select the translation";  
  }
}

function startTest(){
    $("#test-loader").show();
    $('#test-start-btn').hide();
    $.post('../../test_execute.php', 
        { test_id: test } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Test could not be started: " + data.errorDescription;
                $('#testData').hide();
                failureMessage(errorMsg);
            }
            else if(data.result == "Session user has finished sitting for the test."){
                var errorMsg = "You have already taken this test!";    
                $('#testData').hide();
                failureMessage(errorMsg); 
            }
            else{
                $("#q-remainder").html('Questions Remaining: '+data.result.next.entriesRemainingCount);
                $("#question-body").html(data.result.next.prompt);
                $("#answer-submit").click(function(){
                    submitAnswer(data.result.next.testEntryId);
                });
                if(data.result.next.options == null || data.result.next.options.length == 0){
                    prompt = getFreeformPrompt(data.result.next.mode.modeId);
                    $("#test-answer").append('<textarea class="form-control" id="freeform-answer" rows="2" placeholder="'+prompt+'"></textarea>');
                }
                else{
                    prompt = getOptionPrompt(data.result.next.mode.modeId);                    
                    optsradio = prompt+':<br />';
                    $.each( data.result.next.options, function(i,item) {
                        optsradio += '<label><input type="radio" name="radio-answer" value="'+item+'">&nbsp; '+item+'</label><br />';
                    });
                    $("#test-answer").append(optsradio);
                }
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
    $("#prev-question").html('');
    $("#prev-question").hide();
    $("#test-loader").show();
    if($('#freeform-answer').length > 0) 
        answer = $("#freeform-answer").val().trim();
    else{
        answer = $('input[name=radio-answer]:checked', '#test-answer').val();
        if(answer == undefined)
            answer = "";
    }
    $('#question-block').hide();
    $("#test-answer").html('');
    $.post('../../test_execute.php', 
        { test_id: test, test_entry_id: id, contents: answer } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                failureMessage("There was a problem obtaining the test data. Please refresh the page and try again.");
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else if(data.result == "Session user has finished sitting for the test."){
                successMessage("Test is complete and has been submitted.");    
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                if(data.result.prev.pattern.testId != null){
                    answer = data.result.prev.pattern.contents;
                    if(data.result.prev.entry.mode.modeId == 0 || data.result.prev.entry.mode.modeId == 3 || data.result.prev.entry.mode.modeId == 6){
                        lang = data.result.prev.entry.languages[0];
                        correct = data.result.prev.entry.words[lang];
                    }
                    else if(data.result.prev.entry.mode.modeId == 1 || data.result.prev.entry.mode.modeId == 4){
                        lang = data.result.prev.entry.languages[1];
                        correct = data.result.prev.entry.words[lang];
                    }
                    else if(data.result.prev.entry.mode.modeId == 2 || data.result.prev.entry.mode.modeId == 5){
                        correct = data.result.prev.entry.pronunciations[0];
                    }
                    $('#prev-question').html('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>The correct answer was: '+'<strong>'+correct+'</strong><br />You answered: '+'<strong>'+answer+'</strong>');
                    $('#prev-question').show();
                }
                if(data.result.next != null){
                    $("#q-remainder").html('Questions Remaining: '+data.result.next.entriesRemainingCount);
                    $("#question-body").html(data.result.next.prompt);
                    $("#answer-submit").unbind('click');
                    $("#answer-submit").click(function(){
                        submitAnswer(data.result.next.testEntryId);                   
                    });
                    if(data.result.next.options == null || data.result.next.options.length == 0){
                        prompt = getFreeformPrompt(data.result.next.mode.modeId);
                        $("#test-answer").append('<textarea class="form-control" id="freeform-answer" rows="2" placeholder="'+prompt+'"></textarea>');
                    }
                    else{
                        prompt = getOptionPrompt(data.result.next.mode.modeId);                    
                        optsradio = prompt+':<br />';
                        $.each( data.result.next.options, function(i,item) {
                            optsradio += '<label><input type="radio" name="radio-answer" value="'+item+'">&nbsp; '+item+'</label><br />';
                        });
                        $("#test-answer").append(optsradio);
                    }
                    $("#question-block").show();
                }
                else{
                    successMessage("Test is complete and has been submitted.");    
                    $("html, body").animate({scrollTop:0}, "slow"); 
                }
            }
            $("#test-loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
	  });
    return; 
}

function deleteSitting(id){
    if(!confirm("Are you sure you want to delete this sitting?")){
        return;
    }
	  $('#failure').hide();
	  $('#success').hide();
    $('#sitting-list').hide();
    $('#sitting-loader').show();
    $.post('../../sitting_delete.php', 
        { sitting_id: id })
        .done(function(data){
		    authorize(data);
        if(data.isError){
            var errorMsg = "Sitting could not be deleted. Please refresh the page and try again.";
            failureMessage(errorMsg);
        }
        else{
            rowid = '#sittingrow'+id;
            $(rowid).html('Sitting has been deleted.');
            $('#sitting-loader').hide();
            $('#sitting-list').show();
        }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function clearTest(){
    if(!confirm("Are you sure you delete all sittings and reset the test?")){
        return;
    }
	  $('#failure').hide();
	  $('#success').hide();
    $('#sitting-list').hide();
    $('#sitting-loader').show();
    $.post('../../test_unexecute.php', 
        { test_id: test })
        .done(function(data){
		    authorize(data);
        if(data.isError){
            var errorMsg = "Test could not be reset: Please refresh the page and try again.";
            failureMessage(errorMsg);
        }
        else{
            $('#sitting-list').html('All sittings have been deleted.');
            $('#sitting-loader').hide();
            $('#sitting-list').show();
        }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}