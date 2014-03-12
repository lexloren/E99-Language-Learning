function submitRegForm(){
    var email = $("#registerEmail").val();
    var handle = $("#registerHandle").val();
    var password = $("#registerPassword").val();

    if(email == "" || handle == "" || password == ""){
        $("#failure").html("Please enter email address, desired handle, and password.");
        displayAlert("#failure");
        return;
    }
	
    $.getJSON('http://cscie99.fictio.us/register.php', 
        { email: email, handle: handle, password: password},
        function(data){
            if(data.isError){
                $("#failure").html("Your account could not be created: " + data.errorDescription);
                displayAlert("#failure");
            }
            else{
                $("#success").html('Your account has been successfully created. You can now <a href="login.html" class="alert-link">login</a>.');
                displayAlert("#failure");
            }
    });
    return; 
}

function displayAlert(div){
    $(div).show();
    if($("#failure").is(":visible")){
        $("#success").hide();
    }
    else{
        $("#failure").hide();   
        $("#regForm")[0].reset();   
    }
    return;
}
