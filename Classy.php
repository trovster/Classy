<?php

/**
 * Classy
 * @desc	
 */

class Classy {
	
	protected $_post_type,
			  $_custom		= null;
	
	protected static $_allowed_keys_orderby = array(
		'author', 'post_author', 'date', 'post_date', 'title', 'post_title', 'name', 'post_name', 'modified',
		'post_modified', 'modified_gmt', 'post_modified_gmt', 'menu_order', 'parent', 'post_parent',
		'id', 'rand', 'comment_count'
	);
	
	protected static $_allowed_keys_order = array('asc', 'desc');

	/**
	 * __construct
	 * @desc	
	 * @param	array	$options
	 * @return	\Classy
	 */
	public function __construct($options = array()) {
		if(is_array($options)) {
			foreach($options as $key => $value) {
				$this->$key = $value;
			}
		}
	
		return $this;
	}
	
	/**
	 * __set
	 * @desc	Magic method for setting data.
	 *			Uses method if it exists, else sets the variable on the class itself.
	 * @param	string	$key
	 * @param	string	$value
	 * @return	\Classy
	 */
	public function __set($key, $value) {
		if(method_exists($this, 'set_' . $key)) {
			return $this->{'set_' . $key}($value);
		}
		else {
			$this->{$key} = $value;
		}
		return $this;
	}
	
	/**
	 * __get
	 * @desc	Magic method for geting data.
	 *			Checks three different areas;
	 *			- Method, prefixed with get_ ($this->get_forename())
	 *			- Variable, on the class ($this->forename())
	 * 			- Variable, within the default WordPress data
	 * @param	string	$key
	 * @return	mixed
	 */
	public function __get($key) {
		if(method_exists($this, 'get_' . $key)) {
			return $this->{'get_' . $key}();
		}
		elseif(isset($this->{$key})) {
			return $this->{$key};
		}
		elseif(isset($this->_post->{$key})) {
			return $this->_post->{$key};
		}
		return null;
	}
	
	/**
	 * __isset
	 * @desc	Magic method to check whether data is set
	 * @param	string	$key
	 * @return	boolean
	 */
	public function __isset($key) {
		if(method_exists($this, 'get_' . $key)) {
			$value = $this->{'get_' . $key}();
		}
		elseif(isset($this->{$key})) {
			$value = $this->{$key};
		}
		elseif(isset($this->_post->{$key})) {
			$value = $this->_post->{$key};
		}
		return !empty($value) ? true : false;
	}
	
	/**
	 * set_post
	 * @desc	Sets up the default WordPress post data, including custom data.
	 * @param	object	$post
	 * @return	\Classy 
	 */
	public function set_post($post) {
		$this->_post	= $post;
		$this->custom	= is_object($post) && !empty($post->ID) ? $post->ID : null;
		
		return $this;
	}
	
	/**
	 * get_post
	 * @desc	Retrieve the default WordPress post data.
	 * @return	\Classy 
	 */
	public function get_post() {
		return $this->_post;
	}
	
	/**
	 * post_type
	 * @desc	Set the post type.
	 * @return	string
	 */
	public function set_post_type($post_type) {
		$this->_post_type = $post_type;
		return $this;
	}

	/**
	 * get_post_type
	 * @desc	Checks the post type of the set data.
	 *			Defaults to the one set within the class.
	 * @return	string 
	 */
	public function get_post_type() {
		if(!empty($this->_post)) {
			return $this->_post->post_type;
		}
		return (string) $this->_post_type;
	}
	
	
	/**
	 * set_custom
	 * @desc	Retrieves and sets up all of the custom data.
	 * @param	int		$id
	 * @return	\Classy
	 */
	public function set_custom($id) {
		$this->_custom = get_post_custom($id);
		
		return $this;
	}
	
	/**
	 * get_custom
	 * @desc	Retrieves the custom data.
	 * @return	array 
	 */
	public function get_custom() {
		return $this->_custom;
	}
	
	/*********************************************************
	 * =Finding Methods
	 * @desc	Turn the basic data in to Classy objects
	 *********************************************************/
	
	/**
	 * forge
	 * @desc	Create an new instance of the Classy class.
	 * @param	array	$data
	 * @return	instance 
	 */
	public static function forge($data) {
		return new static($data);
	}
	
	/**
	 * find_by_id
	 * @desc	Find a post by id.
	 * @param	int		$id
	 * @return	mixed 
	 */
	public static function find_by_id($id) {
		$post = get_post($id);
		
		if(is_object($post)) {
			return self::forge(array(
				'post'	=> $post
			));
		}
		
		return false;
	}
	
	/**
	 * find_by_slug
	 * @desc	Find a post by 'slug'.
	 * @param	string	$slug
	 * @return	mixed 
	 */
	public static function find_by_slug($slug) {
		$post = get_page_by_path($slug, OBJECT, $post_type);
		
		if(is_object($post)) {
			return self::forge(array(
				'post'	=> $post
			));
		}
		
		return false;
	}
	
	/*********************************************************
	 * =WordPress Methods
	 * @desc	General WordPress methods
	 *********************************************************/
	
	/**
	 * get_the_ID
	 * @origin	get_the_ID()
	 * @desc	Retieve the post ID.
	 * @return	int
	 */
	public function get_the_ID() {
		return $this->post->ID;
	}
	
	/**
	 * the_ID
	 * @origin	the_ID()
	 * @desc	Echo the post ID.
	 */
	public function the_ID() {
		echo $this->get_the_ID();
	}
	
}