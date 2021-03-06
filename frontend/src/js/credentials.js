$(document).ready(function(){
    statusBoxes();
	  $("#forgot-pw").hide();
	  $("#register").hide();	
});

function showForm(frm){
    $("#success").hide();
    $("#failure").hide();
    $("#sign-in").hide();
    $("#register").hide();
    $("#forgot-pw").hide();
    $("#form-signin")[0].reset();
    $("#pwRequestForm")[0].reset();
    $("#regForm")[0].reset();
    $(frm).show();
}

function submitRegForm(){
    var email = $("#registerEmail").val();
    var handle = $("#registerHandle").val();
    var password = $("#registerPassword").val();

    if(email == "" || handle == "" || password == ""){
        $("#failure").html("Please enter email address, desired username, and password.");
        displayAlert("#failure", "#regForm");
        return;
    }
	
    $.post('../../user_register.php', 
        { email: email, handle: handle, password: password })
        .done(function(data){
            if(data.isError){
                $("#failure").html("Your account could not be created: " + data.errorDescription);
                displayAlert("#failure", "#regForm");
            }
            else{
                //$("#success").html('Your account has been successfully created. You can now <a href="login.html" class="alert-link">login</a>.');
                //displayAlert("#success", "#regForm");
                window.location.replace("index.html");
            }
    });
    return; 
}

function displayAlert(div, frm){
    $("#failure").hide();
    $("#success").hide();
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

function submitLoginForm(){
    var handle = $("#inputUsername").val();
    var password = $("#inputPassword").val();

    if(handle == "" || password == ""){
        $("#failure").html("Please enter username and password.");
        $("#failure").show();
        return;
    }
	
    $.post('../../user_authenticate.php', 
        { handle: handle, password: password })
        .done(function(data){
            if(data.isError){
                $("#failure").html("You could not be signed in: " + data.errorDescription);
                $("#failure").show();
            }
            else{
                window.location.replace("index.html");
            }
    });
    return; 
}

function submitResetForm(){
    var newpass1 = $("#pw1").val();
    var newpass2 = $("#pw2").val();

    if(newpass1 == "" || newpass2 == ""){
        $("#failure").html("Please enter new password.");
        displayAlert("#failure", "#resetForm");
        return;
    }

    if(newpass1 != newpass2){
        $("#failure").html("Passwords do not match. Please re-enter.");
        displayAlert("#failure", "#resetForm");
        return;
    }
	
    $.post('../../user_password_reset.php', // script doesn't exist yet 
        { password1: newpass1, password2: newpass2 }) // need to pass user info
        .done(function(data){
            if(data.isError){
                $("#failure").html("This password does not meet the criteria. Please try again.");
                displayAlert("#failure", "#resetForm");
            }
            else{
                $("#success").html('Your password has been successfully reset.');
                displayAlert("#success", "#resetForm");
            }
    });
    return; 
}

function submitPwRequestForm(){
    alert("Placeholder: This script doesn't exist yet.");
    /*var handle = $("#pwUsername").val();
	
    if(handle == ""){
        $("#failure").html("Please enter username.");
        displayAlert("#failure", "#pwRequestForm");
        return;
    }

    $.post('../../user_password_reset.php', // script doesn't exist yet 
        { handle: handle }) 
        .done(function(data){
            if(data.isError){
                $("#failure").html("There is no account associated with that username. Please try again.");
                displayAlert("#failure", "#pwRequestForm");
            }
            else{
                $("#success").html('A new password has been emailed to you. Please reset your password once you are able to sign in.');
                displayAlert("#success", "#pwRequestForm");
            }
    });*/
    return; 
}
