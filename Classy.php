<?php

/**
 * Classy
 * @desc	
 */

abstract class Classy {
	
	protected $_post_type,
			  $_post		= null,
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
	 * @param	mixed	$options
	 * @return	\Classy
	 */
	public function __construct($options = array()) {
		if($options === 'initialize') {
			add_action('init',			array($this, 'init_register_post_type'));
			add_action('init',			array($this, 'init_register_taxonomies'));
			add_action('init',			array($this, 'init_register_images'));

			add_filter(sprintf('manage_edit-%s_columns', $this->get_post_type()), array($this, 'filter_manage_column_listing'));
			add_action(sprintf('manage_%s_posts_custom_column', $this->get_post_type()), array($this, 'action_manage_column_value'), 10, 2);
			add_filter(sprintf('manage_edit-%s_sortable_columns', $this->get_post_type()), array(&$this, 'filter_manage_column_sorting'));
		}
		elseif(is_array($options)) {
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
		elseif($this->has_custom_value($key)) {
			return $this->get_custom_value($key);
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
		elseif($this->has_custom_value($key)) {
			$value = $this->get_custom_value($key);
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
	
	
	/*********************************************************
	 * =Custom Fields
	 * @desc	Checking whether custom values exist
	 *			and getting them. Includes methods for
	 *			special content types;
	 *			json, boolean, and serialized.
	 *********************************************************/
	
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
	
	/**
	 * has_custom_value
	 * @desc	Check whether a custom value exists.
	 * @param	string	$key
	 * @param	string	$prefix
	 * @return	boolean
	 */
	public function has_custom_value($key, $prefix = '_site_') {
		return !empty($this->_custom[$prefix . $key][0]);
	}
	
	/**
	 * get_custom_value
	 * @desc	Return the custom value.
	 * @param	string	$key
	 * @param	string	$prefix
	 * @param	string	$type
	 * @return	string
	 */
	public function get_custom_value($key, $prefix = '_site_', $type = 'string') {
		$value = '';
		
		if($this->has_custom_value($key, $prefix)) {
			$value = $this->_custom[$prefix . $key][0];
		}
	
		switch(strtolower($type)) {
			case 'boolean':
				$value = $value === '1' || $value === 'true' ? true : false;
				break;
			
			case 'json':
				$value = json_decode($value);
				break;
			
			case 'serialized':
				$value = unserialize($value);
				break;
		}
		
		return $value;
	}
	
	/**
	 * get_custom_value_boolean
	 * @desc	Return the custom value as a boolean.
	 *			Converts the following;
	 *			+ '1'		=> true
	 *			+ 'true'	=> true
	 * @param	string	$key
	 * @param	string	$prefix
	 * @return	string
	 */
	public function get_custom_value_boolean($key, $prefix = '_site_') {
		return $this->get_custom_value($key, $prefix, 'boolean');
	}
	
	/**
	 * get_custom_value_json
	 * @desc	Converts the custom value from a JSON object
	 * @param	string	$key
	 * @param	string	$prefix
	 * @return	string
	 */
	public function get_custom_value_json($key, $prefix = '_site_') {
		return $this->get_custom_value($key, $prefix, 'json');
	}
	
	/**
	 * get_custom_value_serialized
	 * @desc	Converts the custom value from a serialized object
	 * @param	string	$key
	 * @param	string	$prefix
	 * @return	string
	 */
	public function get_custom_value_serialized($key, $prefix = '_site_') {
		return $this->get_custom_value($key, $prefix, 'serialized');
	}
	
	
	/*********************************************************
	 * =Finding Methods
	 * @desc	Turn the basic data in to Classy objects.
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
	 * @param	string	$post_type
	 * @return	mixed 
	 */
	public static function find_by_slug($slug, $post_type) {
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
	 * @desc	General WordPress methods.
	 *********************************************************/
	
	/**
	 * get_the_ID
	 * @origin	get_the_ID()
	 * @desc	Retieve the post ID.
	 * @return	int
	 */
	public function get_the_ID() {
		return !empty($this->post) ? $this->post->ID : 0;
	}
	
	/**
	 * the_ID
	 * @origin	the_ID()
	 * @desc	Echo the post ID.
	 */
	public function the_ID() {
		echo $this->get_the_ID();
	}
	
	/**
	 * has_thumbnail
	 * @origin	has_post_thumbnail()
	 * @desc	Checks whether the post has a thumbnail.
	 * @return	boolean 
	 */
	public function has_thumbnail() {
		return (bool) get_post_meta($this->get_the_ID(), '_thumbnail_id', true);
	}
	
	/**
	 * get_thumbnail_id
	 * @origin	get_post_thumbnail_id()
	 * @desc	Retrieve the post thumbnail ID
	 * @return	boolean 
	 */
	public function get_thumbnail_id() {
		return get_post_meta($this->get_the_ID(), '_thumbnail_id', true);
	}
	
	/**
	 * get_thumbnail
	 * @origin	get_the_post_thumbnail()
	 * @desc	Retrieve the post thumbnail HTML.
	 * @param	string			$size
	 * @param	string|array	$attr
	 * @return	string 
	 */
	public function get_thumbnail($size = 'post-thumbnail', $attr = '') {
		if($this->has_thumbnail()) {
			$size = apply_filters('post_thumbnail_size', $size);
			
			do_action('begin_fetch_post_thumbnail_html', $this->get_the_ID(), $this->get_thumbnail_id(), $size);

			if(in_the_loop()) {
				update_post_thumbnail_cache();
			}

			$html = wp_get_attachment_image($this->get_thumbnail_id(), $size, false, $attr);
			
			do_action('end_fetch_post_thumbnail_html', $this->get_the_ID(), $this->get_thumbnail_id(), $size);
			
			return apply_filters('post_thumbnail_html', $html, $this->get_the_ID(), $this->get_thumbnail_id(), $size, $attr);
		}
		return '';
	}
	
	
	/*********************************************************
	 * =Actions
	 * @desc	Default actions called when the class is setup.
	 *********************************************************/
	
	abstract public function init_register_post_type();
	abstract public function init_register_taxonomies();
	abstract public function init_register_images();
	
	
	/*********************************************************
	 * =Admin Listing
	 * @desc	Default actions and filters called for
	 *			listing of columns on the admin area.
	 *********************************************************/
	
	abstract public function filter_manage_column_listing($columns);
	abstract public function filter_manage_column_sorting($columns);
	abstract public function action_manage_column_value($column, $post_id);

	
}