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

?>