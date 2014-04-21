<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Outbox extends ErrorReporter
{
	protected static $error_description = null;
	
	public static function send($sender, $to, $subject, $contents)
	{
		$user_id = "NULL";
		$course_id = "NULL";
		
		if ($sender instanceof Course) $course_id = $sender->get_course_id();
		else if ($sender instanceof User) $user_id = $sender->get_user_id();
		else return self::set_error_description("Sender class must be Course or User.");
		
		$mysqli = Connection::get_shared_instance();
		
		$mysqli->query(sprintf("INSERT INTO outbox (user_id, course_id, `to`, `subject`, `contents`) VALUES ($user_id, $course_id, '%s', '%s', '%s')",
			$mysqli->escape_string($to),
			$mysqli->escape_string($subject),
			$mysqli->escape_string($contents)
		));
		
		$message_id = $mysqli->insert_id;
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Failed to insert message into outbox: " . $mysqli->error . ".");
		}
		
		$result = $mysqli->query("SELECT * FROM outbox");
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Failed to select messages from outbox: " . $mysqli->error . ".");
		}
		
		$sent_message_ids = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (mail($result_assoc["to"], $result_assoc["subject"], $result_assoc["contents"], "From: no-reply@cscie99.hansandersson.me\r\nReply-To: no-reply@cscie99.hansandersson.me"))
			{
				array_push($sent_message_ids, intval($result_assoc["message_id"], 10));
			}
			else break;
		}
		
		$mysqli->query(sprintf("DELETE FROM outbox WHERE message_id IN (%s)",
			implode(",", $sent_message_ids)
		));
		
		if (!!$mysqli->error)
		{
			return self::set_error_description("Failed to delete sent messages from outbox: " . $mysqli->error . ".");
		}
		
		return in_array($message_id, $sent_message_ids) ? $sender : false;
	}
}

?>