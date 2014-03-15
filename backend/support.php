<?php

if (isset ($support)) exit;
$support = true;

//  Returns a numerical array of associtive arrays for all results from a mysqli query.
//!!!!  DEPRECATED SINCE MOVING TO OBJECT ORIENTATION
function mysqli_fetch_all_assocs($inputs)
{
	$outputs = array();
	if (!!$inputs)
	{
		while ($next = $inputs->fetch_assoc())
		{
			array_push($outputs, $next);
		}
	}
	return $outputs;
}

function validate_email($string_in_question)
{
	$string_in_question = strtolower($string_in_question);
	return strlen($string_in_question) < 64
		&& !!preg_match("/^[a-z](\+|-|_|\.)?([a-z\d]+(\+|-|_|\.)?)*@([a-z][a-z\d]*\.){1,3}[a-z]{2,3}$/", $string_in_question);
}

//  Valid handle consists of between 4 and 63 (inclusive) alphanumeric characters
//      beginning with a letter.
function validate_handle($string_in_question)
{
	$string_in_question = strtolower($string_in_question);
	return strlen($string_in_question) >= 4
		&& strlen($string_in_question) < 64
		&& !!preg_match("/^[a-z]+[a-z\d]*$/", $string_in_question);
}

//  Valid password consists of between 6 and 31 (inclusive) characters
//      and contains at least one letter, one number, and one non-alphanumeric character.
function validate_password($string_in_question)
{
	return strlen($string_in_question) >= 6
		&& strlen($string_in_question) < 32
		&& !!preg_match("/[\d]/", $string_in_question)
		&& !!preg_match("/[A-Za-z]/", $string_in_question)
		&& !!preg_match("/[^\dA-Za-z]/", $string_in_question)
		&& !!preg_match("/^.*$/", $string_in_question);
}

//  Returns new PHP associative array for returning to front end.
function new_return_template()
{
	return array(
		"isError" => false,
		"errorTitle" => NULL,
		"errorDescription" => NULL,
		"result" => NULL,
		"resultInformation" => NULL
	);
}

//  Formats an error as a PHP associative array.
function error_assoc($title, $description)
{
	$return = new_return_template();
	
	$return["isError"] = true;
	$return["errorTitle"] = $title;
	$return["errorDescription"] = $description;
	
	return $return;
}

//  Outputs a JSON representation of an error.
function echo_error($title, $description)
{
	echo json_encode(error_assoc($title, $description));
}

//  Exits the executing script, outputting an error formatted in JSON.
function exit_with_error($title, $description)
{
	global $headers;
	if (!isset ($headers)) require_once "headers.php";
	
	echo_error($title, $description);
	exit;
}

//  Formats a result as a PHP associative array.
function result_assoc($result, $result_information = NULL)
{
	$return = new_return_template();
	
	$return["result"] = $result;
	$return["resultInformation"] = $result_information;
	
	return $return;
}

//  Outputs a JSON representation of a result.
function echo_result($result, $result_information = NULL)
{
	echo json_encode(result_assoc($result, $result_information));
}

//  Exits the executing script, outputting a result formatted in JSON.
function exit_with_result($result, $result_information = NULL)
{
	global $headers;
	if (!isset ($headers)) require_once "headers.php";
	
	echo_result($result, $result_information);
	exit;
}

?>