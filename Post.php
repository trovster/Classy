<?php require_once(dirname(__FILE__) . '/Classy.php');

/**
 * Classy_Post
 * @desc	
 */

class Classy_Post extends Classy {
	
	protected $_post_type	= 'post';

	/**
	 * __construct
	 * @desc	
	 * @param	array	$options
	 * @return	\Classy_Post
	 */
	public function __construct($options = array()) {
		parent::__construct($options);
		
		if($options === 'initialize') {
			add_filter(sprintf('manage_posts_columns', $this->get_post_type()),			array($this, 'filter_manage_column_listing'));
			add_action(sprintf('manage_posts_custom_column', $this->get_post_type()),	array($this, 'action_manage_column_value'), 10, 2);
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
	
	/**
	 * get_options
	 * @desc	Options for WP_Query.
	 * @param	array	$options
	 * @return	array
	 */
	public static function get_options($options = array()) {
		return array_merge(array(
			'post_type'			=> 'post',
			'orderby'			=> 'date',
			'order'				=> 'DESC',
			'title_li'			=> '',
			'echo'				=> 0,
			'depth'				=> 1,
			'posts_per_page'	=> 10,
		), $options);
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
	 * filter_manage_column_sorting
	 * @desc	Sort any columns on the admin listing screen.
	 * @param	array	$columns
	 * @return	array
	 */
	public function filter_manage_column_sorting($columns) {
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
	
	
	/*********************************************************
	 * =Admin Boxes
	 * @desc	Default actions and filters called for adding
	 *			extra content / boxes in the admin area.
	 *********************************************************/

	/**
	* action_admin_init_meta_box
	* @desc		Assign the meta box.
	*/
	public function action_admin_init_meta_box() {}
	
	
	/*********************************************************
	 * =Finding Methods
	 * @desc	Turn the basic data in to Classy objects.
	 *********************************************************/
	
	/**
	 * find_by_slug
	 * @desc	Find a post by 'slug'.
	 * @param	string	$slug
	 * @return	mixed 
	 */
	public static function find_by_slug($slug) {
		return parent::find_by_slug($slug, 'post');
	}

}

/**
 * Hook in to WordPress
 */
if(class_exists('Classy_Post')) {
	$classy_post = new Classy_Post('initialize');
}