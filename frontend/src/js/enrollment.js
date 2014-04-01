function showFailure(){
  $("#failure").show();
  $("html, body").animate({scrollTop:0}, "slow");  
}

function removeStudents(){
    var studentsRem = $('.rem_user_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../course_students_remove.php', 
        { course_id: urlParams.course, user_ids: studentsRem })
        .done(function(data){
            if(data.isError){
                var errorMsg = "Student(s) could not be removed: ";
                if(data.errorTitle == "Course Selection"){
                    errorMsg += "Course does not exist.";
                }
                // don't know all the error titles yet
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
                location.reload(true);
            }
    });
    return; 
}

function addStudents(){
    var studentsAdd = $('.add_user_ids:checked').map(function () {
      return this.value;
    }).get().join(",");

    $.post('../../course_students_add.php', 
        { course_id: urlParams.course, user_ids: studentsAdd })
        .done(function(data){
            if(data.isError){
                var errorMsg = "Student(s) could not be added: ";
                if(data.errorTitle == "Course Selection"){
                    errorMsg += "Course does not exist.";
                }
                // don't know all the error titles yet
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
                location.reload(true);
            }
    });
    return; 
}

function getStudentInfo(){
    if(urlParams.course == null){
        $("#studentData").hide();
        $("#failure").html("The course must be specified. Go to the courses page and select a course to view.");
        showFailure(); 
        return;
    }

    $.getJSON('../../course_students.php', 
        {course_id: urlParams.course},
        function(data){
            if(data.isError){
                $("#failure").html("Enrollment data could not be retrieved for this course.");
                showFailure();
            }
            else{
                $("#newStudent").show();
                $.each(data.result, function(i, item){
                    if(item.nameGiven != "null"){nameGiven="";}
                    else{nameGiven=item.nameGiven;}
                    if(item.nameFamily != "null"){nameFamily="";}
                    else{nameFamily=item.nameFamily;}
                    newrow = '<tr><td>' + item.handle + '</td>' +
                             '<td>' + nameFamily + '</td>' +
                             '<td>' + nameGiven + '</td>' +
                             '<td>' + item.email + '</td>' +
                             '<td><input type="checkbox" class="rem_user_ids" name="rem_user_ids" value='+item.userId+'></td></tr>';
                    $('#studentDetails').append(newrow);
                });
                $('#studentDetails').append('<tr><td></td><td></td><td></td><td></td><td><button class="btn btn-primary" type="button" onclick="removeStudents();">Remove Selected Users</button></td></tr>');
            }		
    }); 
}

function toggleSearch(){
    $("#searchResults").html('');
    $("#failure").hide();
    if($("#searchUserForm").is(":visible")){
        $("#searchUserForm").slideUp();
    }
    else{
        $("#searchCriteria").val('');
        $("#searchUserForm").slideDown();
    }
}

function searchUsers(){
    toggleSearch();
    criteria = $("#searchCriteria").val();
    $.getJSON('../../user_find.php', 
        {query: criteria}, 
        function(data){
            if(data.result.length == 0){
                $('#searchResults').html('<br />No matching users found.<br /><br />');
                $('#searchResults').append('<button type="button" stype="button" class="btn" onclick="toggleSearch();">New Search</button><br /><br />');
            }
            else{
                $('#searchResults').append('<br /><strong>Search Results:</strong>');
                $.each(data.result, function(i, item){
                    result = '<div class="checkbox"><label><input type="checkbox" class="add_user_ids" name="add_user_ids" value='+item.userId+'>'+
                             item.handle+' | '+item.email+'</label></div>';
                    $('#searchResults').append(result);
                });
                $('#searchResults').append('<br /><button class="btn btn-primary" type="button" onclick="addStudents();">Add Selected Users</button> &nbsp; &nbsp;'+
                                           '<button type="button" stype="button" class="btn" onclick="toggleSearch();">New Search</button><br /><br />');
            }		
    }); 
    $("html, body").animate({scrollBottom:0}, "slow");  
    return;
}

function showStudentForm(){
    $("#newStudent").load("addstudent.html");
}