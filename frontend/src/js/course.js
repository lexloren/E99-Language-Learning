function resetForm(frm){
  $("#failure").html("");
  $("#success").html("");
  $("#failure").hide();
  $("#success").hide(); 
  $(frm)[0].reset()
  $("#createnew").hide();
	$("#displayCourse").show();
}

function submitCreateNew(){
	$("#failure").html("");
	$("#failure").hide();
	$("#createnew").show();
	$("#displayCourse").hide();
}	

function cancelInsert() {
	$("#failure").html("");
	$("#failure").hide();
	$("#createnew").hide();
	$("#displayCourse").show();
}

function displayAlert(div, frm){
    $("#failure").hide();
    $("#success").hide();
    $(div).show();
    if($("#failure").is(":visible")){
        $("#success").hide();
    }
    else{
        $("#failure").hide();   
        $(frm)[0].reset();   
    }
    return;
}

function insertNew() {
    var courseName = $("#coursename").val();
    var startDate = $("#dtStartDate").val();
    var endDate = $("#dtEndDate").val();
	var knownLang = $("#knownLang").val();
	var unknownLang = $("#unknownLang").val();
	
    if(courseName == "" ){
        $("#failure").html("Please enter course name");
        displayAlert("#failure", "#courseForm");
        return;
    }
	
	if(startDate == "" ){
        $("#failure").html("Please enter course start date");
        displayAlert("#failure", "#courseForm");
        return;
    }
	
	if(endDate == "" ){
        $("#failure").html("Please enter course end date");
        displayAlert("#failure", "#courseForm");
        return;
    }
	
	if(knownLang == "" ){
        $("#failure").html("Please the source Language");
        displayAlert("#failure", "#courseForm");
        return;
    }
	
	if(unknownLang == "" ){
        $("#failure").html("Please the Language to be learnt");
        displayAlert("#failure", "#courseForm");
        return;
    }
	
    $.post('../../course_insert.php', 
        {   lang_known: knownLang, lang_unknw: unknownLang ,course_name: courseName})
        .done(function(data){
            if(data.isError){
                $("#failure").html("The course could not be created: " + data.errorDescription);
                displayAlert("#failure", "#courseForm");
            }
            else{
                $("#success").html('The course has been successfully created.Review <a href="course.html" class="alert-link">All Courses</a>');
                displayAlert("#success", "#courseForm");
				resetForm("#courseForm");
            }
    });
    return; 
}

function displayAlert(div, frm){
    $("#failure").hide();
    $("#success").hide();
    $(div).show();
    if($("#failure").is(":visible")){
        $("#success").hide();
    }
    else{
        $("#failure").hide();   
        $(frm)[0].reset();   
    }
    return;
}

function displayCourseForm(sourceDiv){
	$.getJSON('../../user_courses.php', function(data){
        if(data.isError){
            $("#failure").html("Sorry unable to get the Courses please try again.");
            showFailure();
        }
        else{
			var courseHTML ='';
			if (data.result.length >0) {
				courseHTML ='<br/><table class="table"><tr><th>Name</th><th>Dates</th><th>Enrolled Status</th><th>Action</th></tr>';
			}
            $.each( data.result, function() {
				courseHTML = courseHTML + '<tr><td><a href="editcourse.html?course=' + this.courseId +'">' + this.name +'</a><br/>';
				if (this.isPublic) {
					courseHTML = courseHTML +'<img src="images/lock_yellow.png" height="20" width="20"/>';
				}
				courseHTML = courseHTML +'</td><td>' + this.timeframe + '</td>';
				courseHTML = courseHTML +'<td>20 Students TBD<br/>10 Units <br/><a href="editcourse.html?course=' + this.courseId +'">view details</a></td>';
				courseHTML = courseHTML + '<td>';
				if (this.isSessionUser) {
					courseHTML = courseHTML + '<a href="">continue</a><br/>';
				}
				courseHTML = courseHTML +'<a href="enrollment.html?course=' + this.courseId +'">invite students</a><br/>';
				courseHTML = courseHTML +'<a href="test.html?course=' + this.courseId +'">create tests</a>';
				courseHTML = courseHTML + '</td></tr>';
			});
			if (data.result.length >0) {
				courseHTML =courseHTML + '</table>';
			}
			$(sourceDiv).html(courseHTML);
		}
	});   
}

function displayEditCourseForm() {
	if(urlParams.course == null){
        $("#editcourse").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }

	$.getJSON('../../user_courses.php', 
        {course_id: urlParams.course},
        function(data){
            if(data.isError){
                $("#failure").html("Sorry unable to get the Courses please try again.");
                 $("#failure").show();
            }
            else{
                $("#editcourse").show();
                $.each(data.result, function(i, item){
                    if(item.nameGiven != "null"){nameGiven="";}
                    else{nameGiven=item.nameGiven;}
                    if(item.nameFamily != "null"){nameFamily="";}
                    else{nameFamily=item.nameFamily;}
                    newrow = '<tr><td>' + item.handle + '</td>' +
                             '<td>' + nameFamily + '</td>' +
                             '<td>' + nameGiven + '</td>' +
                             '<td><input type="checkbox" class="rem_user_ids" name="rem_user_ids" value='+item.userId+'></td></tr>';
                    $('#studentDetails').append(newrow);
                });
                $('#studentDetails').append('<tr><td></td><td></td><td></td><td><button class="btn btn-primary" type="button" onclick="removeStudents();">Remove Selected Users</button></td></tr>');
            }		
    }); 
}