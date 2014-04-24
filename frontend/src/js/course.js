function cleanupMessage() {
	$("#success").hide();
    $("#failure").hide();
}
function resetForm(frm){
  cleanupMessage(); 
  $(frm)[0].reset()
  $("#createnew").hide();
	$("#coursesOwned").show();
}

/** This function is used by Index Page to init the Tabs etc.*/
function initIndexPage() {

	cleanupMessage();
	$("#progress").show();
	
	$.getJSON("../../user_select.php", function(data){
        if(data.isError){
            $("#failure").html('Sorry unable to get the user info, Please try again.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
            $("#failure").show();
			$("#progress").hide();
        }
        else{
			try {
			/** Begin Populate the Profile*/
			populateProfileTab(data);
			/** Write Course Information*/
			populateMyCourseTab(data,"#course");
			/** Write My List Information*/
			populateMyListTab(data,"#list");
			}
			
			finally {
				$("#progress").hide();
			}
		}
	});  

	
}

function populateProfileTab(data) {
	$("#inputLoginHandle").val(data.result.handle);
	$("#inputEmail").val(data.result.email);
	$("#inputNameGiven").val(data.result.nameGiven);
	$("#inputFamilyName").val(data.result.nameFamily);
	$.each( data.result.languages, function() {
		var langCode = this.code;
		var langDesc = this.names.en;
		if (this.names.cn) {
			langDesc = langDesc + " - " + this.names.cn;
		}
		if (this.names.jp) {
			langDesc = langDesc + " - " + this.names.jp;
		}
		if ($("#lang_" + langCode)) {
			$("#lang_" + langCode).prop('checked', true); 
			$("#lang_" + langCode +"_init").val("Y");
			$("#lang_" + langCode +"_desc").html(langDesc);
		}
	});
}
/**Populate the My Course Tab */
function populateMyCourseTab(data, divID) {
	var courseHTML ='';
	try
	{
		if (data.result.coursesOwned.length >0 || data.result.coursesStudied.length >0) {
			courseHTML ='<table class="table table-striped table-hover"><thead><tr><th>#</th><th>Course</th><th>Known Language</th><th>Learning</th></tr></thead>';
		}
		$.each( data.result.coursesOwned, function() {
			courseHTML = courseHTML + '<tr><td>' + this.courseId +'</td><td><a href="course.html?courseid=' + this.courseId +'">' + this.name +'</a>';
			if (this.message != null) {
				courseHTML = courseHTML +'<br/><small>' + this.message + '</small>';
			}
			if (this.sessionUserPermissions.write) {
				courseHTML =  courseHTML + '<br/><span class="glyphicon glyphicon-wrench"></span>';
			}
			courseHTML = courseHTML + '</td>';
			courseHTML =  courseHTML + '<td>'+ this.languageKnown.names.en;
			if (this.languageKnown.names.cn) {
				courseHTML =  courseHTML + '<br/>' + this.languageKnown.names.cn;
			}
			if (this.languageKnown.names.jp) {
				courseHTML =  courseHTML + '<br/>' + this.languageKnown.names.jp;
			}
			courseHTML = courseHTML + '</td>';
			
			courseHTML =  courseHTML + '<td>'+ this.languageUnknown.names.en;
			if (this.languageUnknown.names.cn) {
				courseHTML =  courseHTML + '<br/>' + this.languageUnknown.names.cn;
			}
			if (this.languageUnknown.names.jp) {
				courseHTML =  courseHTML + '<br/>' + this.languageUnknown.names.jp;
			}
			courseHTML = courseHTML + '</td></tr>';
				
		});
		$.each( data.result.coursesStudied, function() {
			courseHTML = courseHTML + '<tr><td>' + this.courseId +'</td><td><a href="course.html?courseid=' + this.courseId +'">' + this.name +'</a>';
			if (this.message != null) {
				courseHTML = courseHTML +'<br/><small>' + this.message + '</small>';
			}
			if (this.sessionUserPermissions.read) {
				courseHTML =  courseHTML + '<br/><span class="glyphicon glyphicon-file"></span>';
			}
			courseHTML = courseHTML + '</td>';
			courseHTML =  courseHTML + '<td>'+ this.languageKnown.names.en;
			if (this.languageKnown.names.cn) {
				courseHTML =  courseHTML + '<br/>' + this.languageKnown.names.cn;
			}
			if (this.languageKnown.names.jp) {
				courseHTML =  courseHTML + '<br/>' + this.languageKnown.names.jp;
			}
			courseHTML = courseHTML + '</td>';
			
			courseHTML =  courseHTML + '<td>'+ this.languageUnknown.names.en;
			if (this.languageUnknown.names.cn) {
				courseHTML =  courseHTML + '<br/>' + this.languageUnknown.names.cn;
			}
			if (this.languageUnknown.names.jp) {
				courseHTML =  courseHTML + '<br/>' + this.languageUnknown.names.jp;
			}
			courseHTML = courseHTML + '</td></tr>';
				
		});
		if (data.result.coursesOwned.length >0 || data.result.coursesStudied.length >0) {
			courseHTML =courseHTML + '</table>';
		}
		$(divID).html(courseHTML);
	}
	catch (e ) {
		$(divID).html('<div  class="alert alert-info">No Courses are associated, Create a Course or Entroll into one</div>');
	}
}

function populateCourseSearchResults(data, divID) {
	var courseHTML ='';
	try
	{
		if (data.result.length >0) {
			courseHTML ='<table class="table table-striped table-hover"><thead><tr><th>#</th><th>Course</th><th>Known Language</th><th>Learning</th></tr></thead>';
		}
		$.each( data.result, function() {
			courseHTML = courseHTML + '<tr><td>' + this.courseId +'</td><td>';
			if (this.sessionUserPermissions.write) {
				courseHTML = courseHTML +'<a href="course.html?courseid=' + this.courseId +'">' + this.name +'</a>';
			}
			else {
				courseHTML = courseHTML + this.name ;
			}
			if (this.message != null) {
				courseHTML = courseHTML +'<br/><small>' + this.message + '</small>';
			}
			if (this.sessionUserPermissions.execute) {
				courseHTML =  courseHTML + '<br/><span class="glyphicon glyphicon-search"></span>';
			}
			if (this.sessionUserPermissions.write) {
				courseHTML =  courseHTML + '<br/><span class="glyphicon glyphicon-wrench"></span>';
			}
			courseHTML = courseHTML + '</td>';
			courseHTML =  courseHTML + '<td>'+ this.languageKnown.names.en;
			if (this.languageKnown.names.cn) {
				courseHTML =  courseHTML + '<br/>' + this.languageKnown.names.cn;
			}
			if (this.languageKnown.names.jp) {
				courseHTML =  courseHTML + '<br/>' + this.languageKnown.names.jp;
			}
			courseHTML = courseHTML + '</td>';
			
			courseHTML =  courseHTML + '<td>'+ this.languageUnknown.names.en;
			if (this.languageUnknown.names.cn) {
				courseHTML =  courseHTML + '<br/>' + this.languageUnknown.names.cn;
			}
			if (this.languageUnknown.names.jp) {
				courseHTML =  courseHTML + '<br/>' + this.languageUnknown.names.jp;
			}
			courseHTML = courseHTML + '</td></tr>';
				
		});
		if (data.result.length >0) {
			courseHTML =courseHTML + '</table>';
		}
		$(divID).html(courseHTML);
	}
	catch (e ) {
		$(divID).html('<div  class="alert alert-info">No Courses are associated, Create a Course or Entroll into one</div>');
	}
}

function insertNew() {
	cleanupMessage();
    var courseName = $("#coursename").val();
	var courseDetails = $("#coursedetails").val();
    var startDate = $("#dtStartDate").val();
    var endDate = $("#dtEndDate").val();
	var knownLang = $("#knownLang").val();
	var unknownLang = $("#unknownLang").val();
	
    if(courseName == "" ){
        $("#failure").html("Please enter course name");
        displayAlert("#failure", "#addcourse");
        return;
    }
	
	if(startDate == "" ){
        $("#failure").html("Please enter course start date");
        displayAlert("#failure", "#addcourse");
        return;
    }
	
	if(endDate == "" ){
        $("#failure").html("Please enter course end date");
        displayAlert("#failure", "#addcourse");
        return;
    }
	
	if(knownLang == "" ){
        $("#failure").html("Please the source Language");
        displayAlert("#failure", "#addcourse");
        return;
    }
	
	if(unknownLang == "" ){
        $("#failure").html("Please the Language to be learnt");
        displayAlert("#failure", "#addcourse");
        return;
    }
	
    $.post('../../course_insert.php', 
        {   lang_known: knownLang, lang_unknw: unknownLang ,name: courseName, message:coursedetails})
        .done(function(data){
            if(data.isError){
                $("#failure").html("The course could not be created: " + data.errorDescription);
                $("#failure").show();
            }
            else{
                $("#success").html('The course has been successfully created.');
                displayAlert("#success", "#courseForm");
				initIndexPage();
            }
    });
    return; 
}


function updateProfile() {
	var inputEmail = $("#inputEmail").val();
    var nameGiven = $("#inputNameGiven").val();
	var nameFamily = $("#inputFamilyName").val();

    if(inputEmail == "" ||  nameGiven == "" || nameFamily ==""){
        $("#failure").html("Please enter email address, desired username, and name.");
        return;
    }
	
    $.post('../../user_update.php', 
        { name_given: nameGiven, email: inputEmail, name_family: nameFamily })
        .done(function(data){
            if(data.isError){
                $("#failure").html("Your account could not be updated: " + data.errorDescription);
                displayAlert("#failure", "#regForm");
            }
            else{
                $("#success").html('Your account has been successfully updated.');
                
            }
    });
    return; 
}


/**Populate the My List Tab */
function populateMyListTab(data, divID) {
	var listHTML ='';
	try
	{
		if (data.result.lists.length ==0) {
			listHTML ='<div  class="alert alert-info">No Lists, Please create <a href="list.html">list</a> and practice</div>';
		}
		else {
			if (data.result.lists.length >0) {
				listHTML ='<ul class="list-group">';
			}
			$.each( data.result.lists, function() {
				listHTML = listHTML + '<li class="list-group-item"><span class="badge">'+ this.entriesCount + '</span>  <a href="list.html?listid=' + this.listId +'">' + this.name +'</a>  </li>';
			});
			if (data.result.lists.length >0) {
				listHTML =listHTML + '</ul>';
			}
		}
		$(divID).html(listHTML);
	}
	catch (e ) {
		$(divID).html('Error while populating my list, Please try again.');
	}
}

function searchCourses() {
	$("#searchresults").html('');
	var langs = $("#searchLang").val();
	var courseIDs = $("#searchCourseIds").val();
	var currentURL =  '../../course_find.php?a=b';
	
	if (langs.length >0) {
		currentURL = currentURL + '&langs=' + langs;
	}
	if (courseIDs.length >0) {
		currentURL = currentURL + '&course_ids=' + courseIDs;
	}
	$("#progress").show();
	
	$.getJSON( currentURL, function( data ) {
		
		if (data.isError === true) {
			failureMessage(data.errorTitle + '<br/>' + data.errorDescription);
			$("#progress").hide();
		} else {
			try {
				populateCourseSearchResults(data,"#searchresults");
			}
			finally {
				$("#progress").hide();
			}
		}
		
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
	
}
function setupMaintCourse(){
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
	if(urlParams.courseid == null){
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
	var courseID = getURLparam("courseid");
	if(courseID == null){
        $("#editcourse").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }

	$("#progress").show();
	
	$.getJSON('../../course_select.php', 
        {course_id: courseID},
        function(data){
            if(data== null || data.isError){
                $("#failure").html("Sorry unable to get the Courses please try again.");
                 $("#failure").show();
				 $("#progress").hide();
            }
            else{
				try {
					$("#coursename").val(data.result.name);
					$("#coursedetails").val(data.result.message);
					$("#knownLang").val(data.result.languageKnown);
					$("#unknownLang").val(data.result.languageUnknown);
					
					updateCourseInstructor(data);
					updateCourseResearcher(data);
					updateCourseStudents(data);
					
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
				finally {
					$("#progress").hide();
				}
            }		
    }); 
}

function updateCourseInstructor(data) {
	var listHTML ='';
	if (data.result.instructors.length >0) {
		listHTML ='<ul class="list-group">';
	}
	$.each( data.result.instructors, function() {
		listHTML = listHTML + '<li class="list-group-item"><span class="badge">'+ this.coursesInstructedCount  + '</span>' + this.handle  + '(' + this.email + ' ) ' + this.nameGiven + ' ' + this.nameFamily +'</li>';
	});
	if (data.result.instructors.length >0) {
		listHTML =listHTML + '</ul>';
	}
	$("#instructor").html(listHTML);
}

function updateCourseResearcher(data) {
	var listHTML ='';
	if (data.result.researchers.length >0) {
		listHTML ='<ul class="list-group">';
	}
	$.each( data.result.researchers, function() {
		listHTML = listHTML + '<li class="list-group-item"><span class="badge">'+ this.coursesResearcherCount  + '</span>' + this.handle  + '( ' + this.email + ' ) ' + this.nameGiven + ' ' + this.nameFamily +'</li>';
	});
	if (data.result.researchers.length >0) {
		listHTML =listHTML + '</ul>';
	}
	$("#researcher").html(listHTML);
}

function updateCourseStudents(data) {
	var listHTML ='';
	if (data.result.students.length >0) {
		listHTML ='<ul class="list-group">';
	}
	$.each( data.result.students, function() {
		listHTML = listHTML + '<li class="list-group-item"><span class="badge">'+ this.coursesStudiedCount  + '</span>' + this.handle  + '(' + this.email + ' ) ' + this.nameGiven + ' ' + this.nameFamily +'</li>';
	});
	if (data.result.students.length >0) {
		listHTML =listHTML + '</ul>';
	}
	$("#students").html(listHTML);
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
