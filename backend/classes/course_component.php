<?php

require_once "./backend/connection.php";
require_once "./backend/classes.php";

class CourseComponent extends DatabaseRow
{
	protected function get_container()
	{
		return null;
	}
	
	public function get_owner()
	{
		return !!($container = $this->get_container()) ? $container->get_owner() : null;
	}
	
	public function get_timeframe()
	{
		return null;
	}
	
	public function is_current()
	{
		return (!$this->get_timeframe() || $this->get_timeframe()->is_current())
			&& (!$this->get_container() || $this->get_container()->is_current());
	}
	
	public function get_course()
	{
		return $this->get_container()
			? $this->get_container()->get_course()
			: null;
	}
	
	public function get_course_id()
	{
		return !!($course = $this->get_course()) ? $course->get_course_id() : null;
	}
	
	public function instructors()
	{
		return !!($course = $this->get_course()) ? $course->instructors() : array ();
	}
	public function user_is_instructor($user)
	{
		return !!($course = $this->get_course()) ? $course->user_is_instructor($user) : false;
	}
	public function session_user_is_instructor()
	{
		return !!Session::get() && $this->user_is_instructor(Session::get()->get_user());
	}
	
	public function students()
	{
		return !!($course = $this->get_course()) ? $course->students() : array ();
	}
	public function user_is_student($user)
	{
		return !!($course = $this->get_course()) ? $course->user_is_student($user) : false;
	}
	public function session_user_is_student()
	{
		return !!Session::get() && $this->user_is_student(Session::get()->get_user());
	}
	
	public function researchers()
	{
		return !!($course = $this->get_course()) ? $course->researchers() : array ();
	}
	public function user_is_researcher($user)
	{
		return !!($course = $this->get_course()) ? $course->user_is_researcher($user) : false;
	}
	public function session_user_is_researcher()
	{
		return !!Session::get() && $this->user_is_researcher(Session::get()->get_user());
	}
	
	public function user_can_write($user)
	{
		return $this->user_can_administer($user);
	}
	
	public function user_can_administer($user)
	{
		return $this->user_is_instructor($user) || $this->user_is_owner($user);
	}
	public function session_user_can_administer()
	{
		return !!Session::get() && $this->user_can_administer(Session::get()->get_user());
	}
	
	/*public function user_can_read($user)
	{
		return !!($container = $this->get_container())
			? $container->user_can_read($user) && $container->user_can_execute($user)
			: false;
	}*/
	
	public function user_can_execute($user)
	{
		return !!($container = $this->get_container())
			? ($container->user_can_read($user)
				&& $container->user_can_execute($user)
				&& $this->is_current())
			: false;
	}
	
	protected $message;
	public function get_message()
	{
		return $this->message;
	}
	public function set_message($message)
	{
		return static::errors_push("CourseComponent subclass failed to override default implementation of set_message.");
	}
	protected static function set_this_message($instance, $message, $table, $column, $id)
	{
		if (strlen($message) === 0) $message = null;
		if (!self::update_this($instance, $table, array ("message" => $message), $column, $id))
		{
			return null;
		}
		$instance->message = $message;
		return $instance;
	}
}

?>