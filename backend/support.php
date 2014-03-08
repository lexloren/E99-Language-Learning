<?php

//  Returns a numerical array of associtive arrays for all results from a mysqli query.
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
//      and contains at least one non-alphanumeric character.
function validate_password($string_in_question)
{
	return strlen($string_in_question) >= 6
		&& strlen($string_in_question) < 32
		&& !!preg_match("/^(.*[^\d].*[^A-Za-z].*)|(.*[^A-Za-z].*[^\d].*)$/", $string_in_question);
}

function error_assoc($title, $description)
{
	return array(
		"isError" => true,
		"errorTitle" => $title,
		"errorDescription" => $description
	);
}

function echo_error($title, $contents)
{
	echo json_encode(error_assoc($title, $contents));
}

function exit_with_error($title, $contents)
{
	echo_error($title, $contents);
	exit;
}

function result_assoc($contents)
{
	return array(
		"isError" => false,
		"contents" => $contents
	);
}

function echo_result($contents)
{
	echo json_encode(result_assoc($contents));
}

function exit_with_result($contents)
{
	echo_result($contents);
	exit;
}

?>