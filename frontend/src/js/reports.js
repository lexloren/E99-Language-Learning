function reportPageSetup() {
	$("#exportdiv").hide();
	$("#success").hide();
    $("#failure").hide();
	$("#progress").show();
	getUserCourses();
	
}

function getUserCourses() {
	if(typeof(Storage)!=="undefined")
	{
		// Code for localStorage/sessionStorage.
		var data = localStorage.getItem("USER_COURSE_CACHE");
		if (data !== "undefined" && data != null ) {
			populateCourseData(JSON.parse(data));
			return;
		}
	}
				
	$.getJSON("../../user_select.php", function(data){
		if(data.isError){
			$("#failure").html('Sorry unable to get the all course info, Please try again.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
			$("#failure").show();
			$("#progress").hide();
		}
		else{
			populateCourseData(data);
		}
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
		$("#progress").hide();
	});;  

}

function populateCourseData (data) 
{
	try {
		$("#progress").show();
		var courseHTML ="";
		var reportOptions ='<select id="reporttype" class="form-control">';
		
		if (data.result.coursesInstructed.length >0) {
			courseHTML = courseHTML + '<select id="reportcourse" class="form-control">';
		}
		$.each( data.result.coursesInstructed, function() {
			courseHTML = courseHTML + '<option value=\"' + this.courseId +'\">' + this.name +'</option>';
		});
		if (data.result.length >0 ) {
			courseHTML = courseHTML + '</select>';
			reportOptions = reportOptions + '<option value="CP">Course Practice</option><option value="CT">Course Test Report</option>';
		}
		if (data.result.coursesResearched.length >0) {
			reportOptions = reportOptions + '<option value="RES">Complete Data -Researcher</option>';
		}
		
		reportOptions = reportOptions + '</select>';
		
		$("#courses").html(courseHTML);
		$("#reportoptions").html	(reportOptions);
		
					
					
					
				
	}		
	finally {
		$("#progress").hide();
	}
}
function getModes() {
	$.getJSON("../../mode_enumerate.php", function(data){
        if(data.isError){
            $("#failure").html('Sorry unable to get the the reporting modes.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
            $("#failure").show();
			$("#progress").hide();
        }
        else{
			try {
				$("#progress").show();
				var modeHTML ="";
				/** Get Course*/
				if (data.result.length >0) {
					modeHTML = modeHTML + '<select id="reportmode" class="form-control">';
				}
				$.each( data.result, function() {
						modeHTML = modeHTML + '<option value=\"' + this.modeId +'\">' + this.directionFrom + ' to ' + this.directionTo +'</option>';
				
				});
				if (data.result.length >0 ) {
					modeHTML = modeHTML + '</select>';
				}
				$("#reportmodes").html(modeHTML);
			}		
			finally {
				$("#progress").hide();
			}
		}
	})
	.fail(function(error) {
		failureMessage('Something has gone wrong. Please hit the back button on your browser and try again.');
		$("#progress").hide();
	});;  
}

/**
Run the reports
*/
function runReport()
{
	$("#exportdiv").hide();
	$("#success").hide();
    $("#failure").hide();
	
	var repType =$("#reporttype").val();
	var courseID = $("#reportcourse").val();
	
	if(repType == null  ){
        $("#failure").html("Please select the type of report");
        $("#failure").show();
        return;
    }
	
	if(courseID == null  ){
        $("#failure").html("Please select the course");
        $("#failure").show();
        return;
    }
	if  (repType =='CP') {
		return executeCoursePracticeReport ( courseID);
	} else if  (repType =='CT') {
		return executeCourseTestReport ( courseID);
	} else if  (repType =='RES') {
		return researcherData ( );
	}
	
}

function researcherData() {
	var generator = window.open('../../user_researcher_data_dump.php', 'Researcher Data', 'height=400,width=600');
	
}

function executeCoursePracticeReport ( courseId) 
{
	var url ='../../course_practice_report.php';
	$("#progress").show();
	$.getJSON(url, {course_id: courseId},
        function(data){
            if(data== null || data.isError){
                $("#failure").html("Sorry unable to get the Courses please try again.");
                 $("#failure").show();
				 $("#progress").hide();
            }
            else{
				try {
					var tableData ='';
					if (data.result.coursePracticeReport.studentPracticeReports.length > 0) {
						tableData ='<table id="reporttable"  class="table table-striped table-hover"><tr><th>Student</th>';
						$.each( data.result.coursePracticeReport.studentPracticeReports[0].unitReports, function() {
							tableData = tableData + '<th>' + this.unit.name + '</th>';
						});
						tableData = tableData + '</tr>';
						$.each( data.result.coursePracticeReport.studentPracticeReports, function() {
							tableData = tableData + '<tr><td>' + this.student.email + '<br/>' + this.student.handle + '</td>';
							
							$.each( this.unitReports, function() {
								tableData = tableData + '<td>' + this.progressPercent + '</td>';
							});
							tableData = tableData + '</tr>';
						});
						tableData =tableData + '</table>';
						$("#exportdiv").show();
					}
					else {
						tableData ='Sorry Report returned no data';
					}
					$("#results").html(tableData);
					
					/*JSONToCSVConvertor(data.result,'Export Data',true);*/
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
	
	return;
}

function executeCourseTestReport ( courseId) 
{
	var url ='../../course_test_report.php';
	$("#progress").show();
	$.getJSON(url, {course_id: courseId},
        function(data){
            if(data== null || data.isError){
                $("#failure").html("Sorry unable to get the Courses please try again.");
                 $("#failure").show();
				 $("#progress").hide();
            }
            else{
				try {
					var tableData ='';
					if (data.result.courseTestsReport.testReports.studentTestReports.length > 0) {
						/*store the prononciation*/
						var tr2Row = "<tr><td></td><td></td>";
						tableData ='<table id="reporttable"  class="table table-striped table-hover"><tr><th>Student Email</th><th>Handle</th>';
						$.each( data.result.courseTestsReport.testReports.studentTestReports[0].entryReports, function() {
							tableData = tableData + '<th>' + this.entry.words.en +  this.entry.words.jp + '</th>';
							if (typeof this.entry.words.en != "undefined")	{
								tableData = tableData + '  en:' + this.entry.words.en;
							} 
							if (typeof this.entry.words.jp != "undefined")	{
								tableData = tableData + '  jp:' + this.entry.words.jp;
							}
							if (typeof this.entry.words.cn != "undefined")	{
								tableData = tableData + '  cn:' + this.entry.words.cn;
							}
							if (typeof this.entry.pronuncations.en != "undefined")	{
								tr2Row = tr2Row + '<td>' + this.entry.pronuncations.en + '</td>';
							} else if (typeof this.entry.pronuncations.jp != "undefined")	{
								tr2Row = tr2Row + '<td>' + this.entry.pronuncations.jp + '</td>';
							} else if (typeof this.entry.pronuncations.cn != "undefined")	{
								tr2Row = tr2Row + '<td>' + this.entry.pronuncations.cn + '</td>';
							} else {
								tr2Row = tr2Row + '<td></td>';
							}
							
						});
						tableData = tableData + '</tr>' +tr2Row;
						$.each( data.result.courseTestsReport.testReports.studentTestReports, function() {
							tableData = tableData + '<tr><td>' + this.student.email + '</td><td>' + this.student.handle + '</td>';
							
							$.each( this.entryReports, function() {
								tableData = tableData + '<td>' + this.score + '</td>';
							});
							tableData = tableData + '</tr>';
						});
						tableData =tableData + '</table>';
						$("#exportdiv").show();
					}
					else {
						tableData ='Sorry Report returned no data';
					}
					$("#results").html(tableData);
					
					/*JSONToCSVConvertor(data.result,'Export Data',true);*/
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
	
	return;
}
	
function exportToExcel() {
	tableToExcel ('reporttable', 'Course Report');
}


var tableToExcel = (function() {
  var uri = 'data:application/vnd.ms-excel;base64,'
    , template = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>{worksheet}</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body><table>{table}</table></body></html>'
    , base64 = function(s) { return window.btoa(unescape(encodeURIComponent(s))) }
    , format = function(s, c) { return s.replace(/{(\w+)}/g, function(m, p) { return c[p]; }) }
  return function(table, name) {
    if (!table.nodeType) table = document.getElementById(table)
    var ctx = {worksheet: name || 'Worksheet', table: table.innerHTML}
    window.location.href = uri + base64(format(template, ctx))
  }
})()
