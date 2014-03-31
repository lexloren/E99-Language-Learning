<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class CourseComponent extends DatabaseRow
{
	public function get_course()
	{
		return null;
	}
	
	public function get_owner()
	{
		return !!($course = $this->get_course()) ? $course->get_owner() : null;
	}
	
	public function get_course_id()
	{
		return !!($course = $this->get_course()) ? $course->get_course_id() : null;
	}
	
	public function get_instructors()
	{
		return !!($course = $this->get_course()) ? $course->get_instructors() : array ();
	}
	public function session_user_is_instructor()
	{
		return !!($course = $this->get_course()) ? $course->session_user_is_instructor() : false;
	}
	
	public function get_students()
	{
		return !!($course = $this->get_course()) ? $course->get_students() : array ();
	}
	public function session_user_is_student()
	{
		return !!($course = $this->get_course()) ? $course->session_user_is_student() : false;
	}
	
	public function session_user_can_write()
	{
		return !!($course = $this->get_course()) ? $course->session_user_can_write() : false;
	}
	
	public function session_user_can_read()
	{
		return !!($course = $this->get_course()) ? $course->session_user_can_read() : false;
	}
	
	protected $message;
	public function get_message()
	{
		return $this->message;
	}
	protected function set_message($message, $table, $column, $id)
	{
		if (!self::update_this($this, $table, array ("message" => $message), $column, $id))
		{
			return null;
		}
		$this->message = $message;
		return $this;
	}
	
}

?>