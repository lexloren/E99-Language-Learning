function reportPageSetup() {
	$("#success").hide();
    $("#failure").hide();
	$("#progress").show();
	
	getCourses();
	getModes();
}

function getCourses() {
	$.getJSON("../../user_select.php", function(data){
        if(data.isError){
            $("#failure").html('Sorry unable to get the all course info, Please try again.<br/>The session could have timed out...please login <a href="login.html">Login</a>');
            $("#failure").show();
			$("#progress").hide();
        }
        else{
			try {
				$("#progress").show();
				var courseHTML ="";
				/** Get Course*/
				if (data.result.coursesOwned.length >0) {
					courseHTML = courseHTML + '<div class="panel panel-success"><div class="panel-heading">Courses Owned</div><table class="table table-striped table-hover"><thead><tr><th><input type="radio" name="coursesel" id="coursesel_0" value="0"/>All</th><th>Course</th><th>Data</th><th>Details</th><th>Known Language</th><th>Learning</th></tr></thead>';
				}
				$.each( data.result.coursesOwned, function() {
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
				if (data.result.coursesOwned.length >0 ) {
					courseHTML =courseHTML + '</table></div></div>';
				}
				$("#courses").html(courseHTML);
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
					modeHTML = modeHTML + '<select class="form-control">';
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