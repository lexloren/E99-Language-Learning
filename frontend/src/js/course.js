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
	$("#addcourse").hide();
	$("#course").hide();
	$("#search").hide();
	var userData = getUserData(); 
	if (userData != null) {
		populateIndexPageUserData(userData);
	} else {
		$.getJSON("../../user_select.php", function(data){
			if(data.isError){
				$("#failure").html('Sorry unable to get the user info, Please try again.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
				$("#failure").show();
				$("#progress").hide();
			}
			else{
				populateIndexPageUserData(data);
				setUserData(data);
			
			}
		})
		.fail(function(error) {
			failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
			$("#progress").hide();
		});;  
	}

	
}

function populateIndexPageUserData(data) {
	try {
		$("#course").show();
		$("#search").show();
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
function populateProfileTab(data) {
	$("#inputLoginHandle").val(data.result.handle);
	$("#inputEmail").val(data.result.email);
	$("#inputNameGiven").val(data.result.nameGiven);
	$("#inputFamilyName").val(data.result.nameFamily);
	
	$.getJSON("../../status_enumerate.php", function(data){
			if(data.isError){
				$("#failure").html('Sorry unable to get the user info, Please try again.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
				$("#failure").show();
				$("#education").hide();
			}
			else{
				var reportOptions ='<select id="seleducation" class="form-control"><option value=\"0\">Please select</option>';
				$.each( data.result, function() {
					reportOptions = reportOptions + '<option value=\"' + this.statusId +'\">' + this.description +'</option>';
				});
				reportOptions = reportOptions + '</select>';
				$("#education").html	(reportOptions);
			
			}
		})
		.fail(function(error) {
			failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
			$("#progress").hide();
		});;
		
	var langCode ="en";
	var langYears ="0";
	for(var x=0; x<data.result.languageYears.length; x++)
	{
        if (typeof data.result.languageYears[x].en != "undefined")
		{
            langCode ="en";
			langYears = data.result.languageYears[x].en;
		} 
		else if (typeof data.result.languageYears[x].cn != "undefined")
		{
            langCode ="cn";
			langYears = data.result.languageYears[x].cn;
		} else if (typeof data.result.languageYears[x].jp != "undefined")
		{
            langCode ="jp";
			langYears = data.result.languageYears[x].jp;
		} 
		if ($("#lang_" + langCode +"_year")) {
			$("#lang_" + langCode +"_year").val(langYears);
		}
	}
	var proCourseHTML= "";
	if (data.result.coursesInstructedCount > 0) 
	{
		proCourseHTML = proCourseHTML +"<a href=\"#\">Courses Owned <span class=\"badge\">" + data.result.coursesInstructedCount +"</span></a><br/>";
	}
	if (data.result.coursesStudiedCount > 0) 
	{
		proCourseHTML = proCourseHTML +"<a href=\"#\">Course Studying<span class=\"badge\">" + data.result.coursesStudiedCount +"</span></a><br/>";
	}
	if (data.result.coursesResearchedCount > 0) 
	{
		proCourseHTML = proCourseHTML +"<a href=\"#\">Researching Course  <span class=\"badge\">" + data.result.coursesResearchedCount +"</span></a><br/>";
	}
	if (data.result.listsCount > 0) 
	{
		proCourseHTML = proCourseHTML +"<a href=\"#\">Lists Owned<span class=\"badge\">" + data.result.listsCount +"</span></a><br/>";
	}
	$("#profilecourse").html(proCourseHTML);
 /*
	$.each( data.result.languageYears, function() {
		var langCode = this.hasOwnProperty("en");
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
	});*/
}
/**Populate the My Course Tab */
function populateMyCourseTab(data, divID) {
	var courseHTML ='';
	try
	{
		if (data.result.coursesOwned.length >0) {
			courseHTML = courseHTML + '<div class="panel panel-success"><div class="panel-heading">Courses Owned</div><table class="table table-striped table-hover"><thead><tr><th>#</th><th>Course</th><th>Dates</th><th>Known Language</th><th>Learning</th></tr></thead>';
		}
		$.each( data.result.coursesOwned, function() {
			courseHTML = courseHTML + '<tr><td>' + this.courseId +'</td><td><a href="course.html?courseid=' + this.courseId +'">' + this.name +'</a>';
			if (this.message != null) {
				courseHTML = courseHTML +'<br/><small>' + this.message + '</small>';
			}
			courseHTML = courseHTML + '</td>';
			if (this.timeframe != null) {
				courseHTML =  courseHTML + '<td>'+ displayDate(this.timeframe.open) + '<br/>-<br/>' + displayDate(this.timeframe.close) + '</td>';
			}
			else {
				courseHTML =  courseHTML + '<td> not set</td>';
			}
				
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
		if (data.result.coursesOwned.length >0 ) {
			courseHTML =courseHTML + '</table></div></div>';
		}
		if ( data.result.coursesStudied.length >0) {
			courseHTML = courseHTML + '<div class="panel panel-primary"><div class="panel-heading">Courses Enrolled</div><table class="table table-striped table-hover"><thead><tr><th>#</th><th>Course</th><th>Dates</th><th>Known Language</th><th>Learning</th></tr></thead>';
		}
		$.each( data.result.coursesStudied, function() {
			courseHTML = courseHTML + '<tr><td>' + this.courseId +'</td><td><a href="course.html?courseid=' + this.courseId +'">' + this.name +'</a>';
			if (this.message != null) {
				courseHTML = courseHTML +'<br/><small>' + this.message + '</small>';
			}
			
			courseHTML = courseHTML + '</td>';
			if (this.timeframe != null) {
				courseHTML =  courseHTML + '<td>'+ displayDate(this.timeframe.open) + '<br/>-<br/>' + displayDate(this.timeframe.close) + '</td>';
			}
			else {
				courseHTML =  courseHTML + '<td> not set</td>';
			}
				
				
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
		if (data.result.coursesStudied.length >0) {
			courseHTML =courseHTML + '</table></div></div>';
		}
		courseHTML = courseHTML +'<button type="button" onclick="showAddCourse();"  class="btn btn-primary">Add Course</button>';
		$(divID).html(courseHTML);
	}
	catch (e ) {
		$(divID).html('<div  class="alert alert-info">No Courses are associated, Create a Course or Entroll into one</div>');
	}
	$("addcourse").hide();
}

function populateCourseSearchResults(data, divID) {
	var courseHTML ='';
	try
	{
		if (data.result.length >0) {
			courseHTML ='<div class="panel panel-info"><div class="panel-heading">Search Results</div><table class="table table-striped table-hover"><thead><tr><th>#</th><th>Course</th><th>Dates</th><th>Known Language</th><th>Learning</th></tr></thead>';
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
			
			courseHTML = courseHTML + '</td>';
			if (this.timeframe != null) {
				courseHTML =  courseHTML + '<td>'+ displayDate(this.timeframe.open) + '<br/>-<br/>' + displayDate(this.timeframe.close) + '</td>';
			}
			else {
				courseHTML =  courseHTML + '<td> not set</td>';
			}
			
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
			courseHTML =courseHTML + '</table></div></div>';
		}
		$(divID).html(courseHTML);
	}
	catch (e ) {
		$(divID).html('<div  class="alert alert-info">No Courses are associated, Create a Course or Entroll into one</div>');
	}
}

function addCourseCancel() {
	$("#addcourse").hide();
	$("#course").show();
	$("#search").show();
	$("#searchresults").hide();
}
function showAddCourse() {
	resetForm('#insertCourseForm');
	$("#addcourse").show();
	$("#course").hide();
	$("#search").hide();
	$('#opendate').datetimepicker();
	$('#closedate').datetimepicker();
}
function insertNew() {
	cleanupMessage();
	resetUserData();
    var courseName = $("#coursename").val();
	var courseMessage = $("#coursedetails").val();
    var startDate = $("#opendate").val();
    var endDate = $("#closedate").val();
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

	startDate = Date.parse(startDate)/1000;
    endDate = Date.parse(endDate)/1000;

    if(endDate < startDate){
		$("#failure").html("Open Date cannot be later than Close Date; please re-select the dates.");	
        displayAlert("#failure", "#addcourse");
        return;
    }
	
    $.post('../../course_insert.php', 
        { lang_known: knownLang, lang_unknw: unknownLang ,name: courseName, message:courseMessage,close:endDate, open:startDate})
        .done(function(data){
            if(data.isError){
                $("#failure").html("The course could not be created: " + data.errorDescription);
                $("#failure").show();
            }
            else{
                $("#success").html('The course has been successfully created.');
                initIndexPage();
            }
    })
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
    return; 
}


function updateCourse() {
	cleanupMessage();
	var courseID = getURLparam("courseid");
	if(courseID == null){
        $("#editCourseForm").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }
	
    var courseName = $("#coursename").val();
	var courseMessage = $("#coursedetails").val();
    var startDate = $("#opendate").val();
    var endDate = $("#closedate").val();
	var knownLang = $("#knownLang").val();
	var unknownLang = $("#unknownLang").val();
	
    if(courseName == "" ){
        $("#failure").html("Please enter course name");
        displayAlert("#failure", "#editCourseForm");
        return;
    }
	
	if(startDate == "" ){
        $("#failure").html("Please enter course start date");
        displayAlert("#failure", "#editCourseForm");
        return;
    }
	
	if(endDate == "" ){
        $("#failure").html("Please enter course end date");
        displayAlert("#failure", "#editCourseForm");
        return;
    }
	
	if(knownLang == "" ){
        $("#failure").html("Please the source Language");
        displayAlert("#failure", "#editCourseForm");
        return;
    }
	
	if(unknownLang == "" ){
        $("#failure").html("Please the Language to be learnt");
        displayAlert("#failure", "#editCourseForm");
        return;
    }

	startDate = Date.parse(startDate)/1000;
    endDate = Date.parse(endDate)/1000;

    if(endDate < startDate){
		$("#failure").html("Open Date cannot be later than Close Date; please re-select the dates.");	
        displayAlert("#failure", "#editCourseForm");
        return;
    }
	
    $.post('../../course_update.php', 
        { course_id: courseID,lang_known: knownLang, lang_unknw: unknownLang ,name: courseName, message:courseMessage,close:endDate, open:startDate})
        .done(function(data){
            if(data.isError){
                $("#failure").html("The course could not be created: " + data.errorDescription);
                $("#failure").show();
            }
            else{
                $("#success").html('The course has been successfully created.');
                
            }
    })
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
	});
    return; 
}


function updateProfile() {
	var inputEmail = $("#inputEmail").val();
    var nameGiven = $("#inputNameGiven").val();
	var nameFamily = $("#inputFamilyName").val();
	var statusID = "";
	var langsExp ="";
	
    if(inputEmail == "" ||  nameGiven == "" || nameFamily ==""){
        $("#failure").html("Please enter email address, desired username, and name.");
        return;
    }
	
	if ($("#lang_en_year").val() !="") 
	{
		langsExp =langsExp + "en " + $("#lang_en_year").val() ;
	}
	
	if ($("#lang_cn_year").val() !="") 
	{
		if (langsExp != '') { 
			langsExp = langsExp + ',';
		}
		langsExp =langsExp + "cn " + $("#lang_cn_year").val();
	}
	if ($("#lang_jp_year").val() !="") 
	{
		if (langsExp != '') { 
			langsExp = langsExp + ',';
		}
		langsExp =langsExp + "jp " + $("#lang_jp_year").val() ;
	}
	if ($("#seleducation").val() != "0") {
		statusID= $("#seleducation").val();
	} 
    $.post('../../user_update.php', 
        { name_given: nameGiven, email: inputEmail, name_family: nameFamily, langs:langsExp,status_id:statusID})
        .done(function(data){
            if(data.isError){
                $("#failure").html("Your account could not be updated: " + data.errorDescription);
                displayAlert("#failure", "#regForm");
            }
            else{
                $("#success").html('Your account has been successfully updated.');
                
            }
    })
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please try again.');
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

function searchCancel() {
	$("#searchresults").html('');
	$("#searchresults").hide();
	$("#course").show();
}
function searchCourses() {
	$("#searchresults").html('');
	$("#course").hide();
	cleanupMessage();
	
	var knownLangs = $("#searchKnownLang").val();
	var learnLangs = $("#searchLearnLang").val();
	var courseIDs = $("#searchCourseIds").val();
	var currentURL =  '../../course_find.php?a=b';
	
	if (knownLangs.length >0 && learnLangs.length >0) {
		currentURL = currentURL + '&langs=' + knownLangs + ',' + learnLangs;
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
				$("#searchresults").show();
			}
			finally {
				$("#progress").hide();
			}
		}
		
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
		$("#progress").hide();
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
	var courseID = getURLparam("courseid");
	if(courseID == null){
        $("#editcourse").hide();
        $("#failure").html('The course must be specified. Go to the <a href="course.html">courses page</a> and select a course to view.');
         $("#failure").show();
        return;
    }
	
	displayUnits('#units',courseID);
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
					courseHTML = courseHTML +'</td><td>' + displayDate(this.timeframe.open) + ' -' + displayDate(this.timeframe.close) + '</td>';
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
					var courseTitle="<h1>" + data.result.name +"</h1>";
					if (data.result.timeframe != null) {
						if (data.result.timeframe.isCurrent != null) {
							if (data.result.timeframe.isCurrent) {
								courseTitle = courseTitle + '          <div class="alert alert-success">Active <small>' + displayDate(data.result.timeframe.open) + '-' + displayDate(data.result.timeframe.close) +'</small></div>' ; 
							}
							else {
								courseTitle = courseTitle + '          <div class="alert alert-danger">In active</div>' ; 
							}
						}
					}
					$("#coursenameH1").html(courseTitle);
					
					updateCourseInstructor(data);
					updateCourseStudents(data);
					var navHTML ='';
					if (data.result.sessionUserPermissions.write ) {
						$("#coursename").val(data.result.name);
						$("#coursedetails").val(data.result.message);
						$("#knownLang").val(data.result.languageKnown.code);
						$("#unknownLang").val(data.result.languageUnknown.code);
						
						navHTML ='<li class="active"><a href="#students" data-toggle="tab">Students</a></li><li class=""><a href="#instructor" data-toggle="tab">Instructors</a></li>					<li class=""><a href="#unit" data-toggle="tab">Units</a></li>					<li class=""><a href="#updatecourse" data-toggle="tab">Update Course</a></li>';
						$("#studentadmin").show();
						$("#students").addClass("active in");
						$("#createUnit").show();
					} else {
						navHTML ='<li class="active"><a href="#courseprogress" data-toggle="tab">My Progress</a></li><li ><a href="#students" data-toggle="tab">Students</a></li>						<li class=""><a href="#unit" data-toggle="tab">Units</a></li>	';
						$("#studentadmin").hide();
						$("#students").addClass("active");
						$("#createUnit").hide();
					}
					$("#navtabs").html(navHTML);
					createUnitTable(data,"#units")
					
				}
				finally {
					$("#progress").hide();
				}
            }		
    })
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
		$("#progress").hide();
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
	$("#courseInstructor").html(listHTML);
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
	$("#enrolledstudents").html(listHTML);
}

function insertNewUnit(sourceDiv) {
	cleanupMessage();
	var courseID = getURLparam("courseid");
    var unitName = $("#unitname").val();
    var startDate = $("#openUnitdate").val();
    var endDate = $("#closeUnitdate").val();
	
	if(courseID == null){
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
	startDate = Date.parse(startDate)/1000;
    endDate = Date.parse(endDate)/1000;
	
	$("#unitscreatemsg").html('<img src="/frontend/src/images/loader.gif"> loading...');
	$.post('../../unit_insert.php', 
        { course_id: courseID,  name: unitName, open: startDate ,close: endDate})
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
	
	displayUnits(sourceDiv, courseID);
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
			createUnitTable(data,sourceDiv);
		}
	});   
}


function createUnitTable(data,sourceDiv)  {
	var unitHTML ='';
	if (data.result.units.length >0) {
		unitHTML ='<br/><table class="table"><tr><th>Name</th><th>Timeframe</th><th>Lists</th><th>Tests</th>';
		if (data.result.sessionUserPermissions.write) {
			unitHTML = unitHTML + '<th>Delete</th>';
		}
		unitHTML = unitHTML + '</tr>';
	}
					
					
	$.each( data.result.units, function() {
		unitHTML = unitHTML + '<tr><td><a href="unit.html?unitid=' + this.unitId + '">'+ this.name +'</a></td>';
		unitHTML = unitHTML +'<td>' 
		if (this.timeframe != null) {
			if (this.timeframe.isCurrent) {
				unitHTML = unitHTML +   displayDate(this.timeframe.open) + '<br/> to <br/>' + displayDate(this.timeframe.close);
			}
		}
		unitHTML = unitHTML + '</td>';
		unitHTML = unitHTML +'<td>' + this.listsCount + ' lists <br/><a href="unit.html?unitid=' + this.unitId + '">View Unit</a></td>';
		if (this.testsCount) {
			unitHTML = unitHTML +'<td>' + this.testsCount + ' tests </td>';
		} else {
			unitHTML = unitHTML +'<td>&nbsp;</td>';
		}
		if (data.result.sessionUserPermissions.write) {
			unitHTML = unitHTML +'<td><span title="Delete this unit" onclick="removeUnit(' + this.unitId +');" class="glyphicon glyphicon-trash span-action"></span></td>';
		}
		unitHTML = unitHTML + '</tr>';
	});
	if (data.result.units.length >0) {
		unitHTML =unitHTML + '</table>';
	}
	$(sourceDiv).html(unitHTML);
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


function removeStudents(){
    var studentsRem = $('.rem_user_ids:checked').map(function () {
      return this.value;
    }).get().join(",");
	var courseID = getURLparam("courseid");

    $.post('../../course_students_remove.php', 
        { course_id: courseID, user_ids: studentsRem })
        .done(function(data){
            if(data.isError){
                var errorMsg = "Student(s) could not be removed: ";
                if(data.errorTitle == "Course Selection"){
                    errorMsg += "Course does not exist.";
                }
                // don't know all the error titles yet
                else{
                    errorMsg += "Please refresh the page and try again.";
                }
                $("#failure").html(errorMsg);
                showFailure();
            }
            else{
                location.reload(true);
            }
    });
    return; 
}

function addStudents(isStudent){
    var studentsAdd = $('.add_user_ids:checked').map(function () {
      return this.value;
    }).get().join(",");
	var courseID = getURLparam("courseid");
	var URL ;
	if (isStudent) {
		URL = '../../course_students_add.php'
	}else {
		URL='../../course_instructors_add.php'
	}
    $.post(URL, 
        { course_id: courseID, user_ids: studentsAdd })
        .done(function(data){
            if(data.isError){
                var errorMsg = "Student(s) could not be added: ";
                if(data.errorTitle == "Course Selection"){
                    errorMsg += "Course does not exist.";
                }
                else if(data.errorTitle == "Course-Students Addition"){
                    errorMsg += "Please refresh the page and try again.";
                }
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
	var courseID = getURLparam("courseid");
    if(courseID == null){
        $("#studentData").hide();
        $("#failure").html("The course must be specified. Go to the courses page and select a course to view.");
        showFailure(); 
        return;
    }
	
    $.getJSON('../../course_students.php', 
        {course_id: courseID},
        function(data){
            if(data.isError){
                $("#failure").html("Enrollment data could not be retrieved for this course.");
                showFailure();
            }
            else{
                $("#newStudent").show();
                $.each(data.result, function(i, item){
                    if(item.nameGiven == null){nameGiven="";}
                    else{nameGiven=item.nameGiven;}
                    if(item.nameFamily == null){nameFamily="";}
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

function toggleStudentSearch(resultDiv,formID){
    $(resultDiv).html('');
    $("#failure").hide();
    if($(formID).is(":visible")){
        $(formID).slideUp();
    }
    else{
        $(resultDiv).val('');
        $(formID).slideDown();
    }
}

function searchUsers(isStudent){
    var criteriaDiv, resultDiv, formID;
	if (isStudent) {
		criteriaDiv= '#searchCriteria';
		resultDiv = '#searchResults';
		formID ='#searchUserForm';
	} else {
		criteriaDiv= '#searchInstructor';
		resultDiv = '#searchResultsInstructor';
		formID ='#searchInstructorForm';
	}
	
	
	toggleStudentSearch(resultDiv, formID);
    criteria = $(criteriaDiv).val();
	
    $.getJSON('../../user_find.php', 
        {query: criteria}, 
        function(data){
            if(data.result.length == 0){
                $(resultDiv).html('<br />No matching users found.<br /><br />');
				$(resultDiv).append('<button type="button" stype="button" class="btn" onclick="toggleSearch(\'' + resultDiv + '\',\''+formID +'\');">New Search</button><br /><br />');
				
            }
            else{
                $(resultDiv).append('<br /><strong>Search Results:</strong>');
                $.each(data.result, function(i, item){
                    if($(".rem_user_ids:checkbox[value="+item.userId+"]").length > 0){
                        disabled = " disabled";
                    }
                    else{
                        disabled = "";
                    }
                    result = '<div class="checkbox"><label><input type="checkbox" class="add_user_ids" name="add_user_ids" value='+item.userId+disabled+'>'+
                             item.handle+'</label></div>';
                    $(resultDiv).append(result);
                });
                $(resultDiv).append('<br /><button class="btn btn-primary" type="button" onclick="addStudents(\'' + isStudent + '\');">Add Selected Users</button> &nbsp; &nbsp;'+
                                           '<button type="button" stype="button" class="btn" onclick="toggleSearch(\'' + resultDiv + '\',\''+formID +'\');">New Search</button><br /><br />');
            }		
    }); 
    $("html, body").animate({scrollBottom:0}, "slow");  
    return;
}
