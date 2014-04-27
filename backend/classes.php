<?php

require_once "./backend/classes/error_reporter.php";
require_once "./backend/classes/timeframe.php";
require_once "./backend/classes/outbox.php";
require_once "./backend/classes/database_row.php";
require_once "./backend/classes/language.php";
require_once "./backend/classes/dictionary.php";
require_once "./backend/classes/session.php";
require_once "./backend/classes/user.php";
require_once "./backend/classes/entry.php";
require_once "./backend/classes/annotation.php";
require_once "./backend/classes/list.php";
require_once "./backend/classes/practice.php";
require_once "./backend/classes/grade.php";
require_once "./backend/classes/course.php";
require_once "./backend/classes/course_component.php";
require_once "./backend/classes/unit.php";
require_once "./backend/classes/test.php";
require_once "./backend/classes/sitting.php";
require_once "./backend/classes/response.php";
require_once "./backend/classes/report.php";
require_once "./backend/classes/status.php";
require_once "./backend/classes/pattern.php";

function array_drop(&$array, $item)
{
	foreach ($array as $key => $value)
	{
		if ($array[$key] === $item) unset($array[$key]);
	}
}

?>
