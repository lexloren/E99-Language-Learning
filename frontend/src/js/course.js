function cleanupMessage() {
	$("#success").hide();
    $("#failure").hide();
	$("#failure").html("");
    $("#success").html("");
}
function resetForm(frm){
  cleanupMessage(); 
  $(frm)[0].reset()
  $("#createnew").hide();
	$("#coursesOwned").show();
}

function setupMaintCourse(){
	cleanupMessage();
	showEditUnits();
	displayEditCourseForm();
}

function showEditCourse(){
	cleanupMessage();
	$("#editcourse").show();
	$("#unitmaint").hide();
	$('#closedate').datetimepicker();
	$('#opendate').datetimepicker();
	$('#closeUnitdate').datetimepicker();
	$('#openUnitdate').datetimepicker();
}

function showEditUnits() {
	cleanupMessage();
	
	$("#editcourse").hide();
	$("#unitmaint").show();

	$('#closedate').datetimepicker();
	$('#opendate').datetimepicker();
	$('#closeUnitdate').datetimepicker();
	$('#openUnitdate').datetimepicker();
}
function setupMaintUnit(){

	showEditUnits();
	if(urlParams.course == null){
        $("#editcourse").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }
	
	displayUnits('#units',urlParams.course);
	$("#units").show();
}


function submitCreateNew(){
	cleanupMessage();
	$("#createnew").show();
	$("#coursesOwned").hide();
}	

function cancelInsert() {
	cleanupMessage();
	$("#createnew").hide();
	$("#coursesOwned").show();
}

function displayAlert(div, frm){
    cleanupMessage();
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
	cleanupMessage();
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
        {   lang_known: knownLang, lang_unknw: unknownLang ,name: courseName})
        .done(function(data){
            if(data.isError){
                $("#failure").html("The course could not be created: " + data.errorDescription);
                displayAlert("#failure", "#courseForm");
            }
            else{
                $("#success").html('The course has been successfully created.Review <a href="course.html" class="alert-link">All Courses</a>');
                displayAlert("#success", "#courseForm");
				resetForm("#courseForm");
       			displayCourses('#coursesOwned', '../../user_courses.php');
       			displayCourses('#coursesInstructed', '../../user_instructor_courses.php');
       			displayCourses('#coursesStudied', '../../user_student_courses.php');
            }
    });
    return; 
}

function displayCourses(sourceDiv, scriptAddress){
	cleanupMessage();
	$("#createnew").hide();
	$("#coursesOwned").show();
	$('#dtStartDate').datetimepicker();
	$('#dtEndDate').datetimepicker();
	$(sourceDiv).html('<img src="/frontend/src/images/loader.gif"> loading...');
	
	$.getJSON(scriptAddress, function(data){
        if(data.isError){
            $("#failure").html('Sorry unable to get the Courses please try again.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
            $("#failure").show();
			$(sourceDiv).html('');
        }
        else{
			var courseHTML ='';
			if (data.result.length >0) {
				courseHTML ='<br/><table class="table"><tr><th>Name</th><th>Dates</th><th>Enrolled Status</th><th>Action</th></tr>';
			}
            $.each( data.result, function() {
				courseHTML = courseHTML + '<tr><td><a href="editcourse.html?course=' + this.courseId +'">' + this.name +'</a>';
				if (this.isPublic) {
					courseHTML = courseHTML +'<br/><img src="images/lock_yellow.png" height="20" width="20"/>';
				}
				if (this.message != null) {
					courseHTML = courseHTML +'<br/><small>' + this.message + '</small>';
				}
				if (this.timeframe != null) {
					courseHTML = courseHTML +'</td><td>' + this.timeframe + '</td>';
				} else {
					courseHTML = courseHTML +'</td><td> No Dates are set</td>';
				}
				courseHTML = courseHTML +'<td>' + this.studentsCount + ' Students <br/>' + this.unitsCount + ' Units <br/>' + this.listsCount + ' Lists <br/>' + this.testsCount + ' Test <br/><a href="editcourse.html?course=' + this.courseId +'">view details</a></td>';
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
	cleanupMessage();
	if(urlParams.course == null){
        $("#editcourse").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }

	$("#units").html('<img src="/frontend/src/images/loader.gif"> loading...');
	
	$.getJSON('../../course_select.php', 
        {course_id: urlParams.course},
        function(data){
            if(data.isError){
                $("#failure").html("Sorry unable to get the Courses please try again.");
                 $("#failure").show();
				 $("#units").html('');
            }
            else{
				$("#coursename").val(data.result.name);
				if (data.result.isPublic) {
					$("#inputPublic")
				} else {
				}
				$("#coursedetails").val(data.result.message);
				
				var unitHTML ='';
				if (data.result.units.length >0) {
					unitHTML ='<br/><table class="table"><tr><th>Name</th><th>Timeframe</th><th>Lists</th><th>Tests</th><th>Delete</th></tr>';
				}
				$.each( data.result.units, function() {
					unitHTML = unitHTML + '<tr><td><a href="unit.html?unit=' + this.unitId + '">'+ this.name +'</a></td>';
					unitHTML = unitHTML +'<td>' + this.timeframe + '</td>';
					unitHTML = unitHTML +'<td>' + this.listsCount + ' lists <br/><a href="unit.html?unit=' + this.unitId + '">Edit Unit</a></td>';
					unitHTML = unitHTML +'<td>' + this.testsCount + ' tests <br/><a href="createtest.html?unit=' + this.unitId + '">Add Test</a></td>';
					unitHTML = unitHTML +'<td><button class="btn btn-primary" type="button" onclick="removeUnit(' + this.unitId +');">Delete</button></td>';
					unitHTML = unitHTML + '</tr>';
				});
				if (data.result.units.length >0) {
					unitHTML =unitHTML + '</table>';
				}
				$("#units").html(unitHTML);
				
            }		
    }); 
}

function insertNewUnit(sourceDiv) {
	cleanupMessage();
    var unitName = $("#unitname").val();
    var startDate = $("#dtStartDate").val();
    var endDate = $("#dtEndDate").val();
	
	if(urlParams.course == null){
        $("#editcourse").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }
    if(unitName == "" ){
        $("#failure").html("Please enter unit name");
        displayAlert("#failure", "#createUnit");
        return;
    }
	
	if(startDate == "" ){
        $("#failure").html("Please enter unit start date");
        displayAlert("#failure", "#createUnit");
        return;
    }
	
	if(endDate == "" ){
        $("#failure").html("Please enter unit end date");
        displayAlert("#failure", "#createUnit");
        return;
    }
	
	$("#unitscreatemsg").html('<img src="/frontend/src/images/loader.gif"> loading...');
	$.post('../../unit_insert.php', 
        { course_id: urlParams.course,  name: unitName, open: startDate ,close: endDate})
        .done(function(data){
            if(data.isError){
                $("#failure").html("The unit could not be created: " + data.errorDescription);
                displayAlert("#failure", "#createUnit");
            }
            else{
				resetForm("#createUnit");
            }
    });
	$("#unitscreatemsg").html('');
	
	displayUnits(sourceDiv, urlParams.course);
    return; 
}

function displayUnits(sourceDiv, courseID){
	cleanupMessage();
	$.getJSON('../../course_units.php', 
        {course_id: courseID},
		function(data){
        if(data.isError){
            $("#failure").html("Sorry unable to get the units please try again.");
            $("#failure").show();
        }
        else{
			var unitHTML ='';
			if (data.result.length >0) {
				unitHTML ='<br/><table class="table"><tr><th>Name</th><th>Timeframe</th><th>Delete</th></tr>';
			}
            $.each( data.result, function() {
				unitHTML = unitHTML + '<tr><td><a href="unit.html?unit=' + this.unitId + '">'+ this.name +'</a></td>';
				unitHTML = unitHTML +'<td>' + this.timeframe + '</td>';
				unitHTML = unitHTML +'<td><button class="btn btn-primary" type="button" onclick="removeUnit("' + this.unitId + '","' + sourceDiv +'","' +  courseID +'");>Delete</button></td>';
				unitHTML = unitHTML + '</tr>';
			});
			if (data.result.length >0) {
				unitHTML =unitHTML + '</table>';
			}
			$(sourceDiv).html(unitHTML);
		}
	});   
}

function removeUnit(unitId) {
	cleanupMessage();
	$.post('../../unit_delete.php', 
        {unit_id: unitId},
		function(data){
        if(data.isError){
            $("#failure").html("Sorry unable to delete units please try again.");
            $("#failure").show();
        }
        else{
			displayEditCourseForm();
			$("#success").html("Unit successfully deleted");
			$("#success").show();
		}
	});   
}
