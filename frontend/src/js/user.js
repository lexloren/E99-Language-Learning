function cleanupMessage()
{
	$("#success").hide();
    $("#failure").hide();
	$("#failure").html("");
    $("#success").html("");
}

function displayUser()
{
	cleanupMessage();
	
	$.getJSON('../../user_select.php', {},
		function (data)
		{
			if (data.isError)
			{
				$("#failure").html("Unable to load session user.");
				$("#failure").show();
			}
			else
			{
				var userNameFull = data.result.nameGiven;
				userNameFull += (!!userNameFull ? " " : "") + data.result.nameFamily;
				
				$("#userNameDiv").html(data.result.handle);
			}
		}
	);
}