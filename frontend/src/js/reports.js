function reportPageSetup() {
	$("#success").hide();
    $("#failure").hide();
	$("#progress").show();
	getModes();
	$("#progress").show();
	getCourses();
	
}

function getCourses() {
	if (courseData != "undefined" && courseData != null && courseData.result != null &&  courseData.result.coursesInstructed.length>0 ) 
	{
		populateCourseData (courseData);
	} else {
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
}

function populateCourseData (data) 
{
	try {
		$("#progress").show();
		var courseHTML ="";
		/** Get Course*/
		if (data.result.coursesInstructed.length >0) {
			courseHTML = courseHTML + '<div class="panel panel-success"><div class="panel-heading">Courses Instructed</div><table class="table table-striped table-hover"><thead><tr><th><input type="radio" name="coursesel" id="coursesel_0" value="0"/>All</th><th>Course</th><th>Data</th><th>Details</th><th>Known Language</th><th>Learning</th></tr></thead>';
		}
		$.each( data.result.coursesInstructed, function() {
				courseHTML = courseHTML + '<tr><td><input type="radio" name="coursesel" id="coursesel_'+ this.courseId +'" value="' + this.courseId  +'"/></td><td>' + this.name;
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
			courseHTML =  courseHTML + '<td>';
			if (this.studentsCount >0 ) {
				courseHTML =  courseHTML +  this.studentsCount + ' students <br/>';
			}
			if (this.listsCount >0 ) {
				courseHTML =  courseHTML +  this.listsCount + ' lists <br/>';
			}
			if (this.unitsCount >0 ) {
				courseHTML =  courseHTML +  this.unitsCount + ' units <br/>';
			}
			if (this.testsCount >0 ) {
				courseHTML =  courseHTML +  this.testsCount + ' tests <br/>';
			}
			
			courseHTML =  courseHTML + '</td>';
			
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
		if (data.result.coursesInstructed.length >0 ) {
			courseHTML =courseHTML + '</table></div></div>';
		}
		$("#courses").html(courseHTML);
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
	$("#success").hide();
    $("#failure").hide();
	
	var modeId = $("#reportmode").val();
	var repType =$("#reporttype").val();
	var courseID = $('input:radio[name=coursesel]:checked').val();

	if(modeId == null  ){
        $("#failure").html("Please select the type of report mode");
        $("#failure").show();
        return;
    }

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
	return executeReport (modeId, repType, courseID);
}

function executeReport (modeId, repType, courseId) 
{
	var url ='../../student_practice_report.php';
	if (repType =='ST') {
		url ='../../student_test_report.php';
	} else if (repType =='CP') {
		url ='../../course_practice_report.php';
	} else if (repType =='CT') {
		url ='../../course_test_report.php';
	} 
	$.getJSON(url, {course_id: courseId,mode_id:modeId },
        function(data){
            if(data== null || data.isError){
                $("#failure").html("Sorry unable to get the Courses please try again.");
                 $("#failure").show();
				 $("#progress").hide();
            }
            else{
				try {
					
					JSONToCSVConvertor(data.result,'Export Data',true);
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


function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel) {
    //If JSONData is not an object then JSON.parse will parse the JSON string in an Object
    var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;
    
    var CSV = '';    
    //Set Report title in first row or line
    
    CSV += ReportTitle + '\r\n\n';

    //This condition will generate the Label/Header
    if (ShowLabel) {
        var row = "";
        
        //This loop will extract the label from 1st index of on array
        for (var index in arrData[0]) {
            
            //Now convert each value to string and comma-seprated
            row += index + ',';
        }

        row = row.slice(0, -1);
        
        //append Label row with line break
        CSV += row + '\r\n';
    }
    
    //1st loop is to extract each row
    for (var i = 0; i < arrData.length; i++) {
        var row = "";
        
        //2nd loop will extract each column and convert it in string comma-seprated
        for (var index in arrData[i]) {
            row += '"' + arrData[i][index] + '",';
        }

        row.slice(0, row.length - 1);
        
        //add a line break after each row
        CSV += row + '\r\n';
    }

    if (CSV == '') {        
        alert("Invalid data");
        return;
    }   
    
    //Generate a file name
    var fileName = "MyReport_";
    //this will remove the blank-spaces from the title and replace it with an underscore
    fileName += ReportTitle.replace(/ /g,"_");   
    
    //Initialize file format you want csv or xls
    var uri = 'data:text/csv;charset=utf-8,' + escape(CSV);
    
    // Now the little tricky part.
    // you can use either>> window.open(uri);
    // but this will not work in some browsers
    // or you will not get the correct file extension    
    
    //this trick will generate a temp <a /> tag
    var link = document.createElement("a");    
    link.href = uri;
    
    //set the visibility hidden so it will not effect on your web-layout
    link.style = "visibility:hidden";
    link.download = fileName + ".csv";
    
    //this part will append the anchor tag and remove it after automatic click
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
