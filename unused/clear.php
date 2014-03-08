<?php

mysql_select_db("cscie99", mysql_connect("68.178.216.146", "cscie99", "Ina28@Waffle"));

while (mysql_num_rows(mysql_query("SELECT * FROM dictionary LIMIT 1")) > 0)
{
	mysql_query("DELETE FROM dictionary LIMIT 10000");
}

?>