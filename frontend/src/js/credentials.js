function submitRegForm(){
    var email = $("#registerEmail").val();
    var handle = $("#registerHandle").val();
    var password = $("#registerPassword").val();

    if(email == "" || handle == "" || password == ""){
        $("#failure").html("Please enter email address, desired username, and password.");
        displayAlert("#failure");
        return;
    }
	
    $.post('http://cscie99.fictio.us/register.php', 
        { email: email, handle: handle, password: password })
        .done(function(data){
            if(data.isError){
                $("#failure").html("Your account could not be created: " + data.errorDescription);
                displayAlert("#failure");
            }
            else{
                $("#success").html('Your account has been successfully created. You can now <a href="login.html" class="alert-link">login</a>.');
                displayAlert("#success");
            }
    });
    return; 
}

function displayAlert(div){
    $("#failure").hide();
    $("#success").hide();
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

function submitLoginForm(){
    var handle = $("#inputUsername").val();
    var password = $("#inputPassword").val();

    if(handle == "" || password == ""){
        $("#failure").html("Please enter username and password.");
        $("#failure").show();
        return;
    }
	
    $.post('http://cscie99.fictio.us/authenticate.php', 
        { handle: handle, password: password })
        .done(function(data){
            if(data.isError){
                var errorMsg = "You could not be signed in: ";
                if(data.errorTitle == "Invalid Handle"){
                    errorMsg = errorMsg + "Username is invalid.";
                } else if(data.errorTitle == "Invalid Password"){
                    errorMsg = errorMsg + "Password is invalid.";
                } else if(data.errorTitle == "Invalid Credentials"){
                    errorMsg = errorMsg + "User is unknown.";
                }
                $("#failure").html(errorMsg);
                $("#failure").show();
            }
            else{
                window.location.replace("http://cscie99.fictio.us/welcome.html");
            }
    });
    return; 
}
