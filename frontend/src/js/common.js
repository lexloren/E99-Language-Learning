
function getUserData()
{
	if(typeof(Storage)!=="undefined")
	{
		// Code for localStorage/sessionStorage.
		var data = localStorage.getItem("USER_COURSE_CACHE");
		if (data !== "undefined" && data != null ) {
			return JSON.parse(data);
		}
	}
	return null;
}

function setUserData(data)
{
	if(typeof(Storage)!=="undefined")
	{
		// Code for localStorage/sessionStorage.
		localStorage.setItem("USER_COURSE_CACHE", JSON.stringify(data));
	}
	
}

function resetUserData()
{
	if(typeof(Storage)!=="undefined")
	{
		// Code for localStorage/sessionStorage.
		localStorage.removeItem("USER_COURSE_CACHE");
	}
	
}
function getCourses(){
	if(typeof(Storage)!=="undefined")
	{
		// Code for localStorage/sessionStorage.
		var data = localStorage.getItem("USER_COURSE_CACHE");
		if (data !== "undefined" && data != null ) {
			populateCourseDropDown(JSON.parse(data));
			return;
		}
	}
	
	$.getJSON('../../user_courses.php', function(data){
		authorize(data);
		if(data.isError){
			// show error
        }
        else {
			var courseli;
            $.each(data.result, function(i, item){
				courseli = '<li><a href="course.html?courseid='+item.courseId+'">'+item.name+'</a></li>';
                    $('#course-menu').append(courseli);
            });
        }
        $('#course-menu').append('<li class="dropdown-header">Other</li><li><a href="#">Search for Courses</a></li>');
    });
}

function populateCourseDropDown (data) {
	var courseli;
	if (data.result.coursesOwned.length >0) {

		$.each( data.result.coursesOwned, function() {
			courseli = '<li><a href="course.html?courseid='+this.courseId+'">'+this.name+'</a></li>';
            $('#course-menu').append(courseli);
		});
		
	}
	
}

function navbar() {
	$('#navbar').load('navbar.html');
	$('#nav-signout').on('click', function(event) {
		event.preventDefault();
		signout();
	});
	getCourses();
}

function signout(){
	resetUserData();
    $.getJSON('../../user_deauthenticate.php', 
        function(data){
            if(data.isError){
                // tbd
            }
            else{
                window.location.replace("login.html");
            }		
    });
    return; 
}

// url parameter grabbing code from http://stackoverflow.com/questions/19491336/get-url-parameter-jquery
function getURLparam(paramName) {
	var queries  = window.location.search.substring(1).split('&');  
	var result = null;
    $.each(queries, function () {
        var nameValPair = this.split('=');
        if (nameValPair[0] === paramName) 
        {
			result = nameValPair[1];
        }
    });
	return result;
}

/* required: 
	a div at the top of the body, id="navbar"
		ex: <div id="navbar"></div>
	the main div (containing most/all other content) id="doc-body"
		ex: <div class="container" role="main" id="doc-body"> */
function pageSetup() {
	navbar();
	statusBoxes();
}

function statusBoxes() {
	$('#doc-body').prepend(
		'<div id="success" class="alert alert-success">' + 
		'<button type="button" class="close success-close" aria-hidden="true">&times;</button>' + 
		'<div id="success-text"></div>' +
		'</div>' + 
		'<div id="failure" class="alert alert-danger">' + 
		'<button type="button" class="close failure-close"  aria-hidden="true">&times;</button>' + 
		'<div id="failure-text"></div>' +
		'</div>');
	$('#success').hide();
    $('#failure').hide();
	$(document).on('click', '.success-close', function () {
		$('#success').hide();
	});
	$(document).on('click', '.failure-close', function () {
		$('#failure').hide();
	});	
}

function successMessage(message) {
	$('#success-text').html(message);
	$('#success').show();
}

function failureMessage(message) {
	$('#failure-text').html(message);
	$('#failure').show();
}

/* redirects user to login page if not logged in 
 * @param data: any response from server
*/
function authorize(data) {
	if (data == null) {
		window.location.replace("login.html");
	}
}

function prettyDate(datetime){
  return datetime.toDateString() + ' ' + datetime.toLocaleTimeString();
}

function displayDate(input){
	var datetime = new Date(input*1000);
	return datetime.toDateString();
}
