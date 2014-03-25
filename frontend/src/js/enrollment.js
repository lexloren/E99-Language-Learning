function showFailure(){
  $("#failure").show();
  $("html, body").animate({scrollTop:0}, "slow");  
}

function removeStudent(user,course){
    var mockJSON = '{"course_id":'+course+',"user_id":'+user+'}';
    $.mockjax({
        url: '../../remove_course_student.php',  
        contentType: 'text/json',
        responseText: mockJSON
    });	
    $.post('../../remove_course_student.php', function(data){
        if(data.isError){
            var errorMsg = "Student could not be removed: ";
            // don't know the error titles yet
            $("#failure").html(errorMsg);
            showFailure();
        }
        else{
            $("#success").html("Placeholder: when this is linked to API, student will be removed and page will refresh.");
            $("#success").show();
            //location.reload(true);
        }
    });
    return; 
}

function addStudent(user,course){
    var mockJSON = '{"course_id":'+course+',"user_id":'+user+'}';
    $.mockjax({
        url: '../../add_course_student.php',  
        contentType: 'text/json',
        responseText: mockJSON
    });	
    $.post('../../add_course_student.php', function(data){
        if(data.isError){
            var errorMsg = "Student could not be enrolled: ";
            // don't know the error titles yet
            $("#failure").html(errorMsg);
            showFailure();
        }
        else{
            $("#success").html("Placeholder: when this is linked to API, student will be added and page will refresh.");
            $("#success").show();
            //location.reload(true);
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

    var mockJSON = '[{"userId":"1","handle":"ssodhi","email":"sukhm@hotmail.com", "nameGiven": "Sukh", "nameFamily": "Sodhi"},' +
                    '{"userId":"2","handle":"lloren","email":"lloren@gmail.com", "nameGiven": "Lex", "nameFamily": "Loren"}]';
    $.mockjax({
        url: '../../user_courses_student.php', 
        contentType: 'text/json',
        responseText: mockJSON
    });

    $.getJSON('../../user_courses_student.php', function(data){
        if(data.isError){
            $("#failure").html("Enrollment for this course could not be retrieved.");
            showFailure();
        }
        else{
	          $.each(data, function(i, item){
                newrow = '<tr><td>' + item.handle + '</td>' +
                         '<td>' + item.nameGiven + '</td>' +
                         '<td>' + item.nameFamily + '</td>' +
                         '<td>' + item.email + '</td>' +
                         '<td><span class="span-action" onclick="removeStudent('+item.userId+','+urlParams.course+');">Remove</span></td></tr>';
                $('#studentDetails').append(newrow);
            });
        }		
    }); 
}

function searchUsers(){
    var mockJSON = '[{"userId":"3","handle":"arunag","email":"nag.arunabha@gmail.com", "nameGiven": "Arunabha", "nameFamily": "Nag"},' +
                    '{"userId":"4","handle":"hansa","email":"leif.hans.daniel.andersson@gmail.com", "nameGiven": "Hans", "nameFamily": "Andersson"},' +
                    '{"userId":"5","handle":"nirmal","email":"nirmal.veerasamy@gmail.com", "nameGiven": "Nirmal", "nameFamily": "Veerasamy"}]';
    $.mockjax({
        url: '../../user_search.php', 
        contentType: 'text/json',
        responseText: mockJSON
    });

    $.getJSON('../../user_search.php', function(data){
        if(data.isError){
            $('#searchResults').html('<br />No user found with that email address.');
        }
        else{
            $('#searchResults').append('<br /><strong>Search Results:</strong>');
	          $.each(data, function(i, item){
                result = '<br />'+item.nameFamily+', '+item.nameGiven+' | '+ item.email+' - '+
                         '<span class="span-action" onclick="addStudent('+item.userId+','+urlParams.course+');">Add</span>';
                $('#searchResults').append(result);
            });
        }		
    }); 
}

function showStudentForm(){
    $("#newStudent").load("addstudent.html");
}