function submitRegForm(){
    var email = $("#registerEmail").val();
    var handle = $("#registerHandle").val();
    var password = $("#registerPassword").val();

    if(email == "" || handle == "" || password == ""){
        $("#failure").html("Please enter email address, desired username, and password.");
        displayAlert("#failure", "#regForm");
        return;
    }
	
    $.post('../../user/register', 
        { email: email, handle: handle, password: password })
        .done(function(data){
            if(data.isError){
                $("#failure").html("Your account could not be created: " + data.errorDescription);
                displayAlert("#failure", "#regForm");
            }
            else{
                $("#success").html('Your account has been successfully created. You can now <a href="login.html" class="alert-link">login</a>.');
                displayAlert("#success", "#regForm");
            }
    });
    return; 
}