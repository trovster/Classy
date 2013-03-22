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
	 * get_ID
	 * @origin	get_the_ID()
	 * @desc	Retrieve the post ID.
	 * @return	int
	 */
	public function get_ID() {
		return !empty($this->post) ? $this->post->ID : 0;
	}
	public function get_the_ID() {
		return $this->get_ID();
	}
	
	/**
	 * the_ID
	 * @origin	the_ID()
	 * @desc	Output the post ID.
	 * @output	string
	 */
	public function the_ID() {
		echo $this->get_ID();
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
	
	/**
	 * get_permalink
	 * @desc	Checks whether a permalink is set for this post type.
	 *			Defaults to 'true' but can be overridden.
	 * @return	boolean
	 */
	public function has_permalink() {
		return true;
	}

	/**
	 * get_permalink
	 * @origin	get_permalink()
	 * @desc	Retrieve the permalink using the built inWordPress functionality.
	 * @param	boolean	$leavename
	 * @return	string
	 */
	public function get_permalink($leavename = false) {
		return get_permalink($this->post->ID, $leavename);
	}
	
	/**
	 * the_permalink
	 * @desc	Output the permalink and apply the filter.
	 * @output	string
	 */
	public function the_permalink() {
		echo apply_filters('the_permalink', $this->get_permalink());
	}
	
	/**
	 * has_content
	 * @desc	Checks whether the post content exists.
	 * @return	boolean 
	 */
	public function has_content() {
		return isset($this->post->post_content) && strlen($this->post->post_content) > 0;
	}
	
	/**
	 * get_content
	 * @origin	get_the_content()
	 * @desc	Mirrors the default WordPress function, but uses $this->post.
	 * @global	boolean		$more
	 * @global	int			$page
	 * @global	array		$pages
	 * @global	boolean		$multipage
	 * @global	boolean		$preview
	 * @param	string		$more_link_text
	 * @param	boolean		$stripteaser 
	 * @return	string 
	 */
	public function get_content($more_link_text = null, $stripteaser = false) {
		global $more, $page, $pages, $multipage, $preview;

		if(is_null($more_link_text)) {
			$more_link_text = '(more...)';
		}

		$output		= '';
		$hasTeaser	= false;

		// If post password required and it doesn't match the cookie.
		if(post_password_required($this->post)) {
			return get_the_password_form();
		}
		
		// if the requested page doesn't exist
		// give them the highest numbered page that DOES exist
		if($page > count($pages)) {
			$page = count($pages);
		}

		$content = $this->has_content() ? $this->post->post_content : '';
		
		if(preg_match('/<!--more(.*?)?-->/', $content, $matches)) {
			$content = explode($matches[0], $content, 2);
			if (!empty($matches[1]) && !empty($more_link_text)) {
				$more_link_text = strip_tags(wp_kses_no_null(trim($matches[1])));
			}
			$hasTeaser = true;
		}
		else {
			$content = array($content);
		}
		
		if((false !== strpos($this->post->post_content, '<!--noteaser-->') && ((!$multipage) || ($page==1)))) {
			$stripteaser = true;
		}

		$teaser = $content[0];
		
		if($more && $stripteaser && $hasTeaser) {
			$teaser = '';
		}

		$output .= $teaser;
		
		if(count($content) > 1) {
			if($more) {
				$output .= '<span id="more-' . $this->post->ID . '"></span>' . $content[1];
			}
			else {
				if(!empty($more_link_text)) {
					$more_link = sprintf(' <a href="%s#more-%d" class="more-link">%s</a>', $this->get_permalink(), $this->post->ID, $more_link_text);
					$output .= apply_filters('the_content_more_link', $more_link, $more_link_text);
				}
				$output = force_balance_tags($output);
			}
		}
		
		// preview fix for javascript bug with foreign languages
		if($preview) {
			$output = preg_replace_callback('/\%u([0-9A-F]{4})/', '_convert_urlencoded_to_entities', $output);
		}	

		return $output;
	}
	public function get_the_content($more_link_text = null, $stripteaser = false) {
		return $this->get_content($more_link_text, $stripteaser);
	}
	
	/**
	 * the_content
	 * @desc	Output the content and apply the filter.
	 * @param	string		$more_link_text
	 * @param	boolean		$stripteaser
	 * @output	string
	 */
	public function the_content($more_link_text = null, $stripteaser = false) {
		$content = $this->get_content($more_link_text, $stripteaser);
		$content = apply_filters('the_content', $content);
		$content = str_replace(']]>', ']]&gt;', $content);
		
		echo $content;
	}
	
	/**
	 * has_excerpt
	 * @desc	Checks whether the post excerpt exists.
	 * @return	boolean 
	 */
	public function has_excerpt() {
		return isset($this->post->post_excerpt) && strlen($this->post->post_excerpt) > 0;
	}
	
	/**
	 * get_excerpt
	 * @origin	get_the_excerpt
	 * @desc	
	 * @param	int		$length
	 * @param	string	$append
	 * @return	string 
	 */
	public function get_excerpt($length = 12, $append = '…') {
		$excerpt = $this->has_excerpt() ? $this->post->post_excerpt : '';
		
		if(empty($excerpt)) {
			$excerpt = $this->post->post_content;
		}
		
		if(is_numeric($length)) {
			$excerpt = self::truncate_words($excerpt, $length, $append);
		}
		
		$excerpt = apply_filters('get_the_excerpt', $excerpt);
		
		return $excerpt;
	}
	public function get_the_excerpt($length = 12, $append = '…') {
		return $this->get_excerpt($length, $append);
	}
	
	/**
	 * the_excerpt
	 * @desc	Output the excerpt and apply the filter.
	 * @param	int		$length
	 * @param	string	$append
	 * @output	string 
	 */
	public function the_excerpt($length = 12, $append = '…') {
		echo apply_filters('the_excerpt', $this->get_excerpt($length, $append));
	}
	
	
	/*********************************************************
	 * =Common Methods
	 * @desc	Useful common methods.
	 *********************************************************/
	
	/**
	 * the_attr
	 * @desc	Output the attributes.
	 * @param	string	$type
	 * @param	array	$options
	 * @output	string
	 */
	public function the_attr($type, $options = array()) {
		$output = '';
		
		switch($type) {
			case 'class':
				$output = sprintf(' class="%s"', join(' ', $this->get_attr_classes($options)));
				break;
			
			case 'data':
				$attributes	= $this->get_attr_data($options);
				$output		= ' ' . implode(' ', array_map(function ($k, $v) { return $k . '="' . $v . '"'; }, array_keys($attributes), array_values($attributes)));
				break;
		}
		
		echo $output;
	}
	
	/**
	 * get_attr_classes
	 * @origin	get_post_class
	 * @desc	Get the post class, with any optional classes passed as an option.
	 * @param	array	$classes
	 * @return	array
	 */
	public function get_attr_classes($classes = array()) {
		return get_post_class($classes, $this->post->id);
	}
	
	/**
	 * get_attr_data
	 * @desc	Prefix the key/value attributes with data-
	 * @param	array	$attributes
	 * @return	array
	 */
	public function get_attr_data($attributes = array()) {
		return array_combine(array_map(function ($k) { return 'data-' . $k; }, array_keys($attributes)), $attributes);
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
	
	
	/*********************************************************
	 * =Loops
	 * @desc	Standardise looping.
	 *********************************************************/
	
	/**
	 * loop
	 * @desc	
	 * @param	array	$options
	 * @param	string	$slug
	 * @param	string	$name
	 * @return	string
	 */
	public static function loop($options, $slug, $name = null) {
		$original = pre_loop($options);
		
		ob_start();
		get_template_part('loop', 'slideshow');
		$content = ob_get_clean();

		Classy::post_loop($original);
		
		return $content;
	}

	/**
	 * pre_loop
	 * @desc	Used before a custom loop.
	 *			Parameter is the WP_Query options for the new loop.
	 *			Saves the original query and post data.
	 *			Returns the new query, along with the original wp_query and post.
	 * @global	WP_Query	$wp_query
	 * @global	object		$post
	 * @param	array		$options
	 * @return	array
	 */
	public static function pre_loop($options) {
		global $wp_query, $post;

		$original_post		= null;
		$original_wp_query	= null;

		if(!empty($post)) {
			$original_post = clone $post;
		}
		if(!empty($wp_query)) {
			$original_wp_query = clone $wp_query;
		}

		$wp_query = new WP_Query($options);

		return compact('wp_query', 'original_wp_query', 'original_post');
   }
   
   /**
    * post_loop
    * @desc		Used after a custom loop.
    *			Parameter is the original array saved from the pre loop function.
    *			Resets the query and post data.
    *			Returns the original wp_query and post data.
	* @global	WP_Query	$wp_query
	* @global	object		$post
    * @param	array		$original
    * @return	array
    */
	public static function post_loop($original) {
		global $wp_query, $post;

		extract($original);

		if(!empty($original_wp_query)) {
			$wp_query = clone $original_wp_query;
		}
		if(!empty($original_post)) {
			$post = clone $original_post;
		}

		wp_reset_query();

		return compact('wp_query', 'post');
	}

}