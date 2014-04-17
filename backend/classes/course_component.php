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
	
	public function get_timeframe()
	{
		return null;
	}
	
	public function is_current()
	{
		return !$this->get_timeframe() || $this->get_timeframe()->is_current();
	}
	
	public function get_course_id()
	{
		return !!($course = $this->get_course()) ? $course->get_course_id() : null;
	}
	
	public function get_instructors()
	{
		return !!($course = $this->get_course()) ? $course->get_instructors() : array ();
	}
	public function user_is_instructor($user)
	{
		return !!($course = $this->get_course()) ? $course->user_is_instructor($user) : false;
	}
	public function session_user_is_instructor()
	{
		return !!Session::get() && $this->user_is_instructor(Session::get()->get_user());
	}
	
	public function get_students()
	{
		return !!($course = $this->get_course()) ? $course->get_students() : array ();
	}
	public function user_is_student($user)
	{
		return !!($course = $this->get_course()) ? $course->user_is_student($user) : false;
	}
	public function session_user_is_student()
	{
		return !!Session::get() && $this->user_is_student(Session::get()->get_user());
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
	public function set_message($message)
	{
		return static::set_error_description("CourseComponent subclass failed to override default implementation of set_message.");
	}
	protected static function set_this_message($instance, $message, $table, $column, $id)
	{
		if (!self::update_this($instance, $table, array ("message" => $message), $column, $id))
		{
			return null;
		}
		$instance->message = $message;
		return $instance;
	}
	
	protected function privacy()
	{
		if (!$this->session_user_can_write()
				&& ($this->session_user_can_read() && !$this->is_current()))
		{
			return true;
		}
		else
		{
			return parent::privacy();
		}
	}
}

?>