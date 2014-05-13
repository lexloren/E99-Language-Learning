<?php
	//helps with the connection, the database, and the session
	
	//FIRST, create the connection to the database
	
	if (! isset ($connection))
	{
		if (!($connection = mysql_connect("68.178.216.146", "cscie99", "Ina28@Waffle")))
		{
			die ("Failed database connection");
		}
	}
	
	if (! isset ($database))
	{
		if (!(mysql_select_db("cscie99", $connection)))
		{
			die ("Failed database selection");
		}
	}
	
	//function alias for clarity when we want just to discard the result of the query
	function mysql_perform_query($query)
	{
		mysql_get_result_from_query($query);
	}

	//convenience function that logs each query and returns the result
	function mysql_get_result_from_query($query)
	{
		global $mysql_queries;
		if (! isset ($mysql_queries)) $mysql_queries = array ();
		array_push($mysql_queries, $query);
		$result = mysql_query($query);
		
		//for debugging...
		//if (!$result) die ("Failed database query ('" . htmlspecialchars($query) . "')");
		return $result;
	}
	
	//boots the client when logging out or encountering a user-generated error (e.g. incorrect password)
	function exit_booting_client_with_message($error)
	{
		//see this function below...
		session_log_out();
		
		//I was having trouble with persistent session ids...
		//this code does manage to force PHP to generate an entire new session
		session_destroy();
		session_start();
		session_regenerate_id();
		
		//sends the browser to index.php (with optional $error message)
		exit_redirecting_client_to_url("", $error);
	}
	
	//redirects the client's browser, not necessarily in case of $error
	function exit_redirecting_client_to_url($destination_url, $error = NULL)
	{
		if ($error !== NULL)
		{
			$_SESSION["error"] = $error;
		}
		
		$host = $_SERVER["HTTP_HOST"];
		$path = rtrim(dirname($_SERVER["PHP_SELF"]), "/\\");
		header("Location: http://$host$path/$destination_url");
		
		//Just to be safe, send a meta refresh, too
		echo "<!DOCTYPE html><html><head><title></title><meta http-equiv=\"Content-Type\" content=\"text/html;charset=UTF-8\"><meta http-equiv=\"refresh\" content=\"0;http://$host$path/$destination_url\"></head><body></body></html>";
		
		exit;
	}
	
	//again, this function is convenience to force PHP to generate
	//   an entire new session complete with id
	function session_start_new()
	{
		if (! isset ($_SESSION))
		{
			session_start();
		}
		session_destroy();
		session_start();
		session_regenerate_id();
	}
	
	//picks up the client's session where the user left off
	function session_log_over()
	{
		session_start();
		
		//boots the client if credentials are missing
		if (! isset ($_SESSION) || ! isset ($_SESSION["u"]))
		{
			exit_booting_client_with_message("Authentication failed.");
		}

		//checks whether we havea client with matching credentials that have not expired (after five minutes)
		$authentication_query = sprintf("SELECT u, email, (TIMESTAMPDIFF(MINUTE, update, NOW()) <= 5) AS user_active FROM users WHERE (u = %d) AND (session = '%s')", intval($_SESSION["u"]), mysql_real_escape_string(session_id()));
		
		$authentication_result = mysql_get_result_from_query($authentication_query);
		
		if (!!($result_assoc = mysql_fetch_assoc($authentication_result)))
		{
			//"user_active" tells us whether the user has done anything in the last five minutes
			if (in_array($result_assoc["user_active"], array (true, 'true')))
			{
				global $u;
				$u = intval($result_assoc["u"]);
			
				global $email;
				$email = $result_assoc["email"];
				
				//make another activity checkpoint to preserve the session
				mysql_perform_query("UPDATE users SET update = CURRENT_TIMESTAMP() WHERE (u = $u)");
			}
			else
			{
				exit_booting_client_with_message("Session expired after inactivity.");
			}
		}
		else
		{
			exit_booting_client_with_message("Authentication failed.");
		}
	}
	
	//starts a session, given $email and $password
	function session_log_in($email, $password)
	{
		//first, see whether we have any matching users for the email
		$users_matching = mysql_get_result_from_query(sprintf("SELECT * FROM users WHERE (email = '%s')", mysql_real_escape_string($email)));
		
		//now, we compare the password hash values
		if (!($user_assoc = mysql_fetch_assoc($users_matching))
		|| crypt($password, $user_assoc["phash"]) != $user_assoc["phash"])
		{
			exit_booting_client_with_message("Authentication failed.");
		}
		
		//everything seems fine, so start the session
		session_start_new();
		
		//update the database to keep track of the new PHP session_id (useful for credentials verification)
		mysql_perform_query(sprintf("UPDATE users SET session = '%s' WHERE (u = %d)", mysql_real_escape_string(session_id()), intval($user_assoc["u"])));
		
		//also, we want to have access to the u when the user goes to a new page, so store the value in $_SESSION
		$_SESSION["u"] = $user_assoc["u"];
		global $u;
		$u = intval($user_assoc["u"]);
		global $email;
		$email = $user_assoc["email"];
	}
	
	//ends the client's session when the user logs out
	function session_log_out()
	{
		global $u;
		global $email;
		
		if ( isset ($u))
		{
			//makes sure we have someone in database with matching credentials
			$authentication_query = sprintf("SELECT * FROM users WHERE (u = $u) AND (session = '%s')", mysql_real_escape_string(session_id()));
			
			$authentication_result = mysql_get_result_from_query($authentication_query);
			
			if (!!($result_assoc = mysql_fetch_assoc($authentication_result)))
			{
				//if we found someone, then we clear their session from the database
				mysql_perform_query("UPDATE users SET update = NULL, session = NULL WHERE (u = $u)");
				
				
				//...and we prevent any other parts of the script calling this function from using the session information
				unset ($u);
				unset ($email);
			}
			
			//see below...
			session_adjourn();
		}
	}
	
	//checks whether a string is kinda like an email (not exactly the specification)
	//slightly more lenient (on purpose) than the specification from the assignment in terms of what follows the @
	//...but slightly stricter in terms of what preceds the @ (viz. can't start an email with a nonalphabetic character)
	function string_validate_email($string_in_question)
	{
		$string_in_question = strtolower($string_in_question);
		return strlen($string_in_question) <= 64
			&& !!preg_match("/^[a-z](\+|-|_|\.)?([a-z\d]+(\+|-|_|\.)?)*@([a-z][a-z\d]*\.){1,3}[a-z]{2,3}$/", $string_in_question);
	}
	
	//checks whether the string is an acceptable password in terms of length and variety
	function string_validate_password($string_in_question)
	{
		return strlen($string_in_question) >= 6
			&& strlen($string_in_question) <= 32
			&& !!preg_match("/^(.*[^\d].*[^A-Za-z].*)|(.*[^A-Za-z].*[^\d].*)$/", $string_in_question);
	}
	
	//commits transactional changes to the database and closes the connection
	function session_adjourn()
	{
		mysql_perform_query("COMMIT");
		
		global $connection;
		global $database;
		mysql_close($connection);
		
		unset ($connection);
		unset ($database);
	}
	
	//this function I use to generate the form fields on the homepage
	//these fields all use javascript to make them more natural to edit
	//test the interface to see the result
	function html_autoclear_textfield($size, $maxlength, $form_name, $form_value, $is_password = false)
	{
		$size = intval($size);
		$maxlength = intval($maxlength);
		$form_name = htmlentities($form_name);
		$form_value = htmlentities($form_value);
		
		if ($is_password)
		{
			return "<input type=\"text\" size=\"$size\" maxlength=\"$maxlength\" name=\"$form_name\" value=\"$form_value\" onfocus=\"if (this.type == 'text') { this.type = 'password'; this.value = ''; }\" onblur=\"if (this.value == '') { this.type = 'text'; this.value = '$form_value'; }\">";
		}
		else
		{
			return "<input type=\"text\" size=\"$size\" maxlength=\"$maxlength\" name=\"$form_name\" value=\"$form_value\" onfocus=\"if (this.value == '$form_value') this.value = '';\" onblur=\"if (this.value == '') this.value = '$form_value';\">";
		}
	}
	
	//these queries actually execute at the time of inclusion,
	//ensuring that we work within a transaction
	
//	mysql_perform_query("SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED");
//	mysql_perform_query("SET autocommit = 0");
//	mysql_perform_query("BEGIN");
?>