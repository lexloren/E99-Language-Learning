<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class Outbox extends ErrorReporter
{
	protected static $errors = null;
	
	public static function send($sender, $to, $subject, $contents)
	{
		$user_id = "NULL";
		$course_id = "NULL";
		
		if ($sender !== null)
		{
			if ($sender instanceof Course) $course_id = $sender->get_course_id();
			else if ($sender instanceof User) $user_id = $sender->get_user_id();
			else return static::errors_push("Sender class must be Course or User.");
		}
		
		Connection::query(sprintf("INSERT INTO outbox (user_id, course_id, `to`, `subject`, `contents`) VALUES ($user_id, $course_id, '%s', '%s', '%s')",
			Connection::escape($to),
			Connection::escape($subject),
			Connection::escape($contents)
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to insert message into outbox: $error.");
		}
		
		$message_id = Connection::insert_id();
		
		$result = Connection::query("SELECT * FROM outbox");
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to select messages from outbox: $error.");
		}
		
		$sent_message_ids = array ();
		while (($result_assoc = $result->fetch_assoc()))
		{
			if (!Session::get()->get_allow_email()
				|| mail($result_assoc["to"], $result_assoc["subject"], $result_assoc["contents"], "From: no-reply@cscie99.hansandersson.me\r\nReply-To: no-reply@cscie99.hansandersson.me"))
			{
				array_push($sent_message_ids, intval($result_assoc["message_id"], 10));
			}
			else break;
		}
		
		Connection::query(sprintf("DELETE FROM outbox WHERE message_id IN (%s)",
			implode(",", $sent_message_ids)
		));
		
		if (!!($error = Connection::query_error_clear()))
		{
			return static::errors_push("Failed to delete sent messages from outbox: $error.");
		}
		
		return in_array($message_id, $sent_message_ids) ? $sender : false;
	}
}

?>