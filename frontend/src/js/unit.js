var unit;

$(document).ready(function(){
	pageSetup();
	$("#loader").hide();
	$("#test-loader").hide();
  $("#deck-loader").hide(); 
  $("#unitdeck-loader").hide();
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
    $("#word-search").hide();
    $("#owner-search").hide();
    getUnitInfo();
	}
});

function showForm(frm,tohide){
    $(tohide).hide();
    $(frm).show();
    if(frm != "#searchListForm")
        $('html, body').animate({scrollTop: $(frm).offset().top}, "slow");
}

function showSearch(search){
    $('#word-search').hide();
    $('#owner-search').hide();
    $('#searchHeader').html('');
    $('#searchResults').html('');
    $('#searchListForm')[0].reset();
    $(search).show();
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
                    $('#lang1').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                    $('#lang2').append('<option value="'+item.code+'">'+item.names.en+'</option>');
                });
            }
    })
    .fail(function(error) {
        failureMessage('Something has gone wrong. Please refresh the page and try again.');
    });    
}

function cancelUpdate(frm,tohide){
    if(frm != '#updateUnitForm'){ // but maybe add something to restore original values for updateUnitForm?
        $(frm)[0].reset(); 
        $('#searchHeader').html('');
        $('#searchResults').html('');
        $('#word-search').hide();
        $('#owner-search').hide();
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
		        authorize(data);
            if(data.isError){
                failureMessage("Information for this unit could not be retrieved.");
            }
            else{
                if(data.result.name != null)
                    uname = data.result.name;
                else
                    uname = 'Unnamed Unit';
                unitheader = '<h3 class="form-signin-heading">'+uname+'</h3>';
                $("#unit-header").html(unitheader);
                if(data.result.sessionUserPermissions.write == true){
                    $("#unitname").val(uname);
                    if(data.result.message != null){
                        $("#unitdesc").val(data.result.message);
                    }
                    if(data.result.timeframe != null){
                        if(data.result.timeframe.open != null){
                            opendate = prettyDate(new Date(data.result.timeframe.open*1000));
                            $("#unitopendate").val(opendate);
                        }
                        if(data.result.timeframe.close != null){
                            closedate = prettyDate(new Date(data.result.timeframe.close*1000));
                            $("#unitclosedate").val(closedate);
                        }
                    }
                    getLangs();
                }
                else{
                    if(data.result.message != null){
                      unitintro = '<h4 class="form-signin-heading">'+data.result.message+'</h4><br />';
                      $("#unit-intro").html(unitintro);  
                    }
                    $("#unit-update").hide();
                    $("#search-list").hide();
                    $("#add-test").hide();
                }
                if(data.result.lists == null || data.result.lists == 0){
                    $('#lists').html("<em>This unit currently has no decks.</em>");
                }
                else{
                    $('#lists').append('<tr><th>Name</th><th>Card Count</th><th></th></tr>');
                    $.each(data.result.lists, function(i, item){
                        if(data.result.sessionUserPermissions.write == true){
                            listremovetd = '<td><label class="select-entry-ids"><input type="checkbox" class="rem_list_ids" name="rem_list_ids" value='+item.listId+'>&nbsp; Remove</label></td></tr>';
                        }
                        else{
                            listremovetd = '<td></td></tr>';
                        }             
                        if(item.name != null)
                            lname = item.name;
                        else
                            lname = '<em>unnamed</em>';           
                        listrow = '<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' +
                                  '<td>' + item.entriesCount + '</td>'+listremovetd;
                        $('#lists').append(listrow);
                    });
                    if(data.result.sessionUserPermissions.write == true){
                        $('#lists').append('<tr><td></td><td></td><td><span class="span-action" onclick="removeLists();">[Remove Selected Decks]</span></td></tr></tbody>');
                    }
                }

                if(data.result.tests == null || data.result.tests.length == 0){
                    $('#tests').html("<em>This unit currently has no tests.</em>");
                }
                else{
                    $.each(data.result.tests, function(i, item){
                        if(item.timeframe == null){
                            topen = "";
                            tclose = "";
                        }
                        else{
                            if(item.timeframe.open != null)
                                topen = prettyDate(new Date(item.timeframe.open*1000));
                            else
                                topen = "";
                            if(item.timeframe.close != null)
                                tclose = prettyDate(new Date(item.timeframe.close*1000));
                            else
                                tclose = "";
                        }
                        if(item.timer != null)
                            time = item.timer/60;
                        if(item.name != null)
                            tname = item.name;
                        else
                            tname = '<em>unnamed</em>';
                        testrow = '<tr><td><a href="test.html?testid='+item.testId+'">'+tname+'</a></td>' +
                                  '<td>' + time + '</td>' +
                                  '<td>' + topen + '</td>' +
                                  '<td>' + tclose + '</td></tr>';
                        $('#tests').append(testrow);
                    });
                }
                $("#doc-body").append('<a href="course.html?courseid='+data.result.courseId+'" style="text-decoration:none;"><span class="glyphicon glyphicon-arrow-left span-action" title="Return to course"></span>&nbsp; Return to course</a><br />&nbsp; ');
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
	  $('#failure').hide();
	  $('#success').hide();
    $.post('../../unit_delete.php', 
        { unit_id: unit })
        .done(function(data){
		    authorize(data);
        if(data.isError){
            var errorMsg = "Unit could not be deleted. Please refresh the page and try again.";
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
	  $('#failure').hide();
	  $('#success').hide();
    var unitname = $("#unitname").val();
    var desc = $("#unitdesc").val();
    var opendate = $("#unitopendate").val();
    var closedate = $("#unitclosedate").val();
    
	  if(unitname == "" || desc == "" || opendate == "" || closedate == ""){
		    failureMessage("Please provide unit name, instructions, and open/close dates.");	
        return;
    }

    opendate = Date.parse(opendate)/1000;
    closedate = Date.parse(closedate)/1000;

    if(closedate < opendate){
		    failureMessage("Open Date cannot be later than Close Date; please re-select the dates.");	
        $("html, body").animate({scrollTop:0}, "slow");         
        return;
    }

	  $("#loader").show();
	  $("#unitData").hide();
    $.post('../../unit_update.php', 
        { unit_id: unit, name: unitname, open: opendate, close: closedate, message: desc } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Unit could not be updated. Please reload the page and try again.";
                failureMessage(errorMsg);
            }
            else{
                $("#unitname").val(data.result.name);
                $("#unitdesc").val(data.result.message);
                if(data.result.timeframe != null){
                    if(data.result.timeframe.open != null){
                        opendate = prettyDate(new Date(data.result.timeframe.open*1000));
                        $("#unitopendate").val(opendate);
                    }
                    if(data.result.timeframe.close != null){
                        closedate = prettyDate(new Date(data.result.timeframe.close*1000));
                        $("#unitclosedate").val(closedate);
                    }
                }
                successMessage("Unit was successfully updated.");
                cancelUpdate("#updateUnitForm","#unit-update");
                $("#unitData").show();
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

<!--- Tests --->
function verifyTestForm(){
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

    submitCreateTestForm(testname, desc, opendate, closedate, timer);
}

function submitCreateTestForm(testname, desc, opendate, closedate, timer){
	  $('#failure').hide();
	  $('#success').hide();
    $.post('../../test_insert.php', 
        { unit_id: unit, name: testname, open: opendate, close: closedate, timer: timer, message: desc } )
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "Test could not be created. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                cancelUpdate("#createTestForm","#add-test");
                refreshTests();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function refreshTests(){
	  $('#failure').hide();
	  $('#success').hide();
    $("#test-loader").show();
    $("#tests").html('');
    $.getJSON('../../unit_tests.php', 
        {unit_id: unit},
        function(data){
		        authorize(data);
            if(data.isError){
                failureMessage("Tests could not be refreshed.");
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                if(data.result == null){
                    $('#tests').html("<em>This unit currently has no tests.</em>");
                }
                else{
                    $('#tests').append('<tr><th>Name</th><th>Time (in minutes)</th><th>Open Date</th><th>Close Date</th></tr>');         
                    $.each(data.result, function(i, item){
                        if(item.timeframe == null){
                            topen = "";
                            tclose = "";
                        }
                        else{
                            if(item.timeframe.open != null)
                                topen = prettyDate(new Date(item.timeframe.open*1000));
                            else
                                topen = "";
                            if(item.timeframe.close != null)
                                tclose = prettyDate(new Date(item.timeframe.close*1000));
                            else
                                tclose = "";
                        }
                        if(item.timer != null)
                            time = item.timer/60;
                        testrow = '<tr><td><a href="test.html?testid='+item.testId+'">'+item.name+'</a></td>' +
                                  '<td>' + time + '</td>' +
                                  '<td>' + topen + '</td>' +
                                  '<td>' + tclose + '</td></tr>';
                        $('#tests').append(testrow);
                    });
                }
            }
            //$('html, body').animate({scrollTop: $("#tests").offset().top}, "slow");
            $("#test-loader").hide();
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
                          if($(".rem_list_ids:checkbox[value="+item.listId+"]").length > 0){
                              disabled = " disabled";
                          }
                          else{
                              disabled = "";
                          }
                          if(item.name != null)
                              lname = item.name;
                          else
                              lname = '<em>unnamed</em>';
				                  $('#searchResults').append('<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' + 
                                                     '<td>'+item.entriesCount+'</td>' +
                                                     '<td><label class="select-entry-ids"><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+this.listId+disabled+'>&nbsp; Add</label></td></tr>');
                      });
			                $('#searchResults').append('<tr><td></td><td></td><td><span class="span-action" onclick="addLists();">[Add Selected Decks]</span></td></tr>');
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
			                $('#searchResults').append('<thead><tr><td>Name</td><td>Card Count</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function(i,item) {
                          if($(".rem_list_ids:checkbox[value="+item.listId+"]").length > 0){
                              disabled = " disabled";
                          }
                          else{
                              disabled = "";
                          }
                          if(item.name != null)
                              lname = item.name;
                          else
                              lname = '<em>unnamed</em>';
				                  $('#searchResults').append('<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' + 
                                                     '<td>'+item.entriesCount+'</td>' +
                                                     '<td><label class="select-entry-ids"><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+this.listId+disabled+'>&nbsp; Add</label></td></tr>');
                      });
			                $('#searchResults').append('<tr><td></td><td></td><td><span class="span-action" onclick="addLists();">[Add Selected Decks]</span></td></tr>');
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
    var entriesAdd = $('.add_entry_ids:checked').map(function () {
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
			                $('#searchResults').append('<thead><tr><td>Name</td><td>Card Count</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function(i,item) {
                          if($(".rem_list_ids:checkbox[value="+item.listId+"]").length > 0){
                              disabled = " disabled";
                          }
                          else{
                              disabled = "";
                          }
                          if(item.name != null)
                              lname = item.name;
                          else
                              lname = '<em>unnamed</em>';
				                  $('#searchResults').append('<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' + 
                                                     '<td>'+item.entriesCount+'</td>' +
                                                     '<td><label class="select-entry-ids"><input type="checkbox" class="add_list_ids" name="add_list_ids" value='+this.listId+disabled+'>&nbsp; Add</label></td></tr>');
                      });
			                $('#searchResults').append('<tr><td></td><td></td><td><span class="span-action" onclick="addLists();">[Add Selected Decks]</span></td></tr>');
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

function search_entry(page) {
	  $('#failure').hide();
	  $('#success').hide();
	  $("#deck-loader").show();
	  $('#searchResults').html('');
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
                          $('#searchResults').html('There are no more matching entries.<br />');
                      }
                      else{
                          $('#searchResults').html('Matching entries could not be found.<br />');
                      }
		              } 
                  else {
			                $('#searchResults').append('<thead><tr><td>Word</td><td>Pronunciation</td><td>Translation</td><td></td></tr></thead>');
			                $('#searchResults').append('<tbody>');
			                $.each( data.result, function() {
				                  $('#searchResults').append('<tr><td>' + this.words[this.languages[1]] + '</td>' + 
					                                        '<td>' + this.pronuncations[this.languages[1]] + '</td>' + 
					                                        '<td>' + this.words[this.languages[0]] + '</td>' + 
                                                  '<td><label class="select-entry-ids"><input type="checkbox" class="add_entry_ids" name="add_entry_ids" value='+this.entryId+'>&nbsp; Select</label></td></tr>');
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
    var listsAdd = $('.add_list_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../unit_lists_add.php', 
        { unit_id: unit, list_ids: listsAdd })
        .done(function(data){
		        authorize(data);
            if(data.isError){
                var errorMsg = "List(s) could not be added. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                $("#searchResults").hide();
                $('.add_list_ids').prop('checked',false);
                refreshLists();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function removeLists(){
	  $('#failure').hide();
	  $('#success').hide();
    var listsRem = $('.rem_list_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../unit_lists_remove.php', 
        { unit_id: unit, list_ids: listsRem })
        .done(function(data){
          	authorize(data);
            if(data.isError){
                var errorMsg = "List(s) could not be removed. Please refresh the page and try again.";
                failureMessage(errorMsg);
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                refreshLists();
            }
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
    return; 
}

function refreshLists(){
	  $('#failure').hide();
	  $('#success').hide();
    $("#unitdeck-loader").show();
    $("#lists").html('');
    $.getJSON('../../unit_lists.php', 
        {unit_id: unit},
        function(data){
		        authorize(data);
            if(data.isError){
                failureMessage("Decks could not be refreshed.");
                $("html, body").animate({scrollTop:0}, "slow"); 
            }
            else{
                if(data.result == null || data.result.length < 1){
                    $('#lists').html("<em>This unit currently has no decks.</em>");
                }
                else{
                    $('#lists').append('<thead><tr><td>Name</td><td>Card Count</td><td></td></tr></thead><tbody>');
                    $.each(data.result, function(i, item){
                        if(item.name != null)
                            lname = item.name;
                        else
                            lname = '<em>unnamed</em>';
                        listrow = '<tr><td><a href="list.html?listid='+item.listId+'">'+lname+'</a></td>' +
                                  '<td>'+item.entriesCount+'</td>' +
                                  '<td><label class="select-entry-ids"><input type="checkbox" class="rem_list_ids" name="rem_list_ids" value='+item.listId+'>&nbsp; Remove</label></td></tr>';
                        $('#lists').append(listrow);
                    });
                    $('#lists').append('<tr><td></td><td></td><td><span class="span-action" onclick="removeLists();">[Remove Selected Decks]</span></td></tr></tbody>');
                }
            }
            if(!$("#searchResults").is(":visible")){
                $("#searchResults").show();
            }
            //$('html, body').animate({scrollTop: $("#lists").offset().top}, "slow");
            $("#unitdeck-loader").hide();
    })
	 .fail(function(error) {
		    failureMessage('Something has gone wrong. Please refresh the page and try again.');
        $("html, body").animate({scrollTop:0}, "slow"); 
	  });
}
