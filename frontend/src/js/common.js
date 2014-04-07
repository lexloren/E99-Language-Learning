// To contain global JS functions

function getCourses(){
	  $.getJSON('../../user_courses.php', 
        function(data){
            if(data.isError){
                // show error
            }
            else{
                $.each(data.result, function(i, item){
                    courseli = '<li><a href="editcourse.html?course='+item.courseId+'">'+item.name+'</a></li>';
                    $('#course-menu').append(courseli);
                });
            }
            $('#course-menu').append('<li class="dropdown-header">Other</li><li><a href="#">Search for Courses</a></li>');
    });
}

function signout(){
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