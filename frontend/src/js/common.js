// To contain global JS functions
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