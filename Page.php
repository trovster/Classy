<?php

/**
 * Classy_Page
 * @desc	
 */

class Classy_Page extends Classy {
	
	protected $_post_type	= 'page';

	/**
	 * __construct
	 * @desc	
	 * @param	array	$options
	 * @return	Classy_Post
	 */
	public function __construct($options = array()) {
		parent::__construct($options);
		
		if($options === 'initialize') {
			add_filter(sprintf('manage_pages_columns', $this->get_post_type()), array(&$this, 'filter_manage_column_listing'));
			add_action(sprintf('manage_pages_custom_column', $this->get_post_type()), array(&$this, 'action_manage_column_value'), 10, 2);
		}
	
		return $this;
	}
	
	/**
	 * init_register_post_type
	 * @desc	Register the post type, for custom post types.
	 */
	public function init_register_post_type() {}
	
	/**
	 * init_register_taxonomies
	 * @desc	Register any taxonomies.
	 */
	public function init_register_taxonomies() {}
	
	/**
	 * init_register_images
	 * @desc	Register any image sizes.
	 *			Can also be used to setup multiple images.
	 */
	public function init_register_images() {
		add_image_size($this->get_post_type() . '-thumbnail', 300, 300, true);
		add_image_size($this->get_post_type() . '-large', 1024, 768, true);
	}
	
	
	/*********************************************************
	 * =Specific
	 * @desc	Page specific methods.
	 *********************************************************/
	/**
	 * get_template
	 * @desc	Returns the page template
	 * @param	boolean	$trim	Optionally remove the .php suffix
	 * @return	string
	 */
	public function get_template($trim = false) {
		$value = $this->get_custom_value('template', '_wp_page_');

		if($trim === true) {
			$value = rtrim($value, '.php');
		}

		return $value;
	}
	
	
	/*********************************************************
	 * =Admin Listing
	 * @desc	Default actions and filters called for
	 *			listing of columns on the admin area.
	 *********************************************************/
	
	/**
	 * filter_manage_column_listing
	 * @desc	Add extra columns to the admin listing screen.
	 * @param	array	$columns
	 * @return	array
	 */
	public function filter_manage_column_listing($columns) {
		return $columns;
	}
	
	/**
	 * action_manage_column_value
	 * @desc	Output the values for the extra columns.
	 * @param	string	$column
	 * @param	int		$post_id
	 */
	public function action_manage_column_value($column, $post_id) {
		$classy_post	= self::find_by_id($post_id);
		
		switch($column) {}
	}

}