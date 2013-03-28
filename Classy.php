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
		elseif($this->has_custom_value($key)) {
			return $this->get_custom_value($key);
		}
		elseif(isset($this->_post->{$key})) {
			return $this->_post->{$key};
		}
		elseif(isset($this->{$key})) {
			return $this->{$key};
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
		elseif(property_exists($this->_post->{$key})) {
			$value = $this->_post->{$key};
		}
		elseif(property_exists($this->{$key})) {
			$value = $this->{$key};
		}
		elseif($this->has_custom_value($key)) {
			$value = $this->get_custom_value($key);
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
	 * has_date
	 * @desc	Checks whether the post date exists.
	 * @return	boolean
	 */
	public function has_date() {
		return isset($this->post->post_date) && strlen($this->post->post_date) > 0;
	}
	
	/**
	 * get_date
	 * @origin	get_the_date()
	 * @desc	Retrieves the post date and apply the filter.
	 * @param	string		$d
	 * @return	string 
	 */
	public function get_date($d = '') {
		$the_date = ($d === '') ? mysql2date(get_option('date_format'), $this->post->post_date) : mysql2date($d, $this->post->post_date);

		return apply_filters('get_the_date', $the_date, $d);
	}
	
	/**
	 * the_date
	 * @origin	the_date()
	 * @desc	Output the date, with optional format, prefixes and suffixes.
	 * @global	string		$currentday
	 * @global	string		$previousday
	 * @param	string		$d
	 * @param	string		$before
	 * @param	string		$after
	 * @output	string
	 */
	public function the_date($d = '', $before = '', $after = '') {
		global $currentday, $previousday;
		
		$the_date = '';
		
		if($currentday != $previousday) {
			$the_date	.= $before;
			$the_date	.= $this->get_date($d);
			$the_date	.= $after;
			$previousday = $currentday;

			$the_date = apply_filters('the_date', $the_date, $d, $before, $after);
		}

		echo $the_date;
	}
	public function get_the_date() {
		return $this->get_date();
	}
	
	/**
	 * has_title
	 * @desc	Checks whether the post title exists.
	 * @return	boolean
	 */
	public function has_title() {
		return isset($this->post->post_title) && strlen($this->post->post_title) > 0;
	}
	
	/**
	 * get_title
	 * @origin	get_title()
	 * @desc	Retrieves the post title and apply the filter.
	 * @return	string
	 */
	public function get_title() {
		$title	= $this->has_title() ? $this->post->post_title : '';
		$id		= $this->get_ID();

		return apply_filters('the_title', $title, $id);
	}
	public function get_the_title() {
		return $this->get_title();
	}
	
	/**
	 * the_title
	 * @origin	the_title()
	 * @desc	Output the title, with optional prefixes and suffixes.
	 * @param	string	$before
	 * @param	string	$after
	 * @return	string
	 */
	public function the_title($before = '', $after = '') {
		echo $this->has_title() ? $before . $this->get_title() . $after : '';
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
	 * @origin	get_the_excerpt()
	 * @desc	
	 * @param	int		$length
	 * @param	string	$append
	 * @return	string 
	 */
	public function get_excerpt($length = 200, $append = '…') {
		$excerpt = $this->has_excerpt() ? $this->post->post_excerpt : '';
		
		if(empty($excerpt)) {
			$excerpt = $this->post->post_content;
		}
		
		if(is_numeric($length)) {
			$excerpt = self::substr_letters($excerpt, $length, $append);
		}
		
		$excerpt = apply_filters('get_the_excerpt', $excerpt);
		
		return $excerpt;
	}
	public function get_the_excerpt($length = 200, $append = '…') {
		return $this->get_excerpt($length, $append);
	}

	/**
	 * the_excerpt
	 * @desc	Output the excerpt and apply the filter.
	 * @param	int		$length
	 * @param	string	$append
	 * @output	string 
	 */
	public function the_excerpt($length = 200, $append = '…') {
		echo apply_filters('the_excerpt', $this->get_excerpt($length, $append));
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
	 * the_thumbnail
	 * @desc	Output the post thumbnail HTML.
	 * @param	string			$size
	 * @param	string|array	$attr
	 * @return	string 
	 */
	public function the_thumbnail($size = 'post-thumbnail', $attr = '') {
		echo $this->get_thumbnail($size, $attr);
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
				$output = sprintf(' class="%s"', implode(' ', $this->get_attr_classes($options)));
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
		return get_post_class($classes, $this->post->ID);
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
	 * =Miscellaneous
	 * @desc	Useful common methods, non post type methods.
	 *********************************************************/
	
	/**
	 * substr_words
	 * @desc	Truncate text by the word count.
	 * @param	string $text
	 * @param	int $length
	 * @param	string $append
	 * @return	string
	 */
	public static function substr_words($text, $length, $append = '…') {
		$phrase_array = explode(' ', $text);
		if(count($phrase_array) > $length && $length > 0) {
			$text = implode(' ',array_slice($phrase_array, 0, $length)) . $append;
		}
		return $text;
	}
	
	/**
	 * substr_letters
	 * @desc	Truncate text by letter count.
	 * @param	string $text
	 * @param	int $length
	 * @param	string $append
	 * @return	string
	 */
	public static function substr_letters($text, $length, $append = '…') {
		if(strlen($text) > $length) {
			$text = substr($text, 0, $length) . $append;
		}
		return $text;
	}
	
	
	/*********************************************************
	 * =HTML
	 * @desc	Generating HTML elements.
	 *********************************************************/
	
	/**
	 * html_tag
	 * @desc	Create a HTML tag.
	 * @param	string			$tag
	 * @param	array|string	$attr
	 * @param	string|bool		$content	The content to place in the tag, or false for no closing tag
	 * @return	string
	 */
	public static function html_tag($tag, $attr = array(), $content = false) {
		$html			= '';
		$has_content	= (bool) ($content !== false and $content !== null);
		
		$html .= '<' . $tag;
		$html .= (!empty($attr)) ? ' '.(is_array($attr) ? self::array_to_attr($attr) : $attr) : '';
		$html .= $has_content ? '>' : ' />';
		$html .= $has_content ? $content . '</' . $tag . '>' : '';

		return $html;
	}
	
	/**
	 * array_to_attr
	 * @desc	Takes an array of attributes and turns it into a string for an html tag
	 * @param	array	$attr
	 * @return	string
	 */
	public static function array_to_attr($attr) {
		$attr_str = '';

		if (!is_array($attr)) {
			$attr = (array) $attr;
		}

		foreach($attr as $property => $value) {
			if(is_null($value)) {
				continue;
			}

			if(is_numeric($property)) {
				$property = $value;
			}

			$attr_str .= sprintf('%s="%s" ', $property, $value);
		}

		return trim($attr_str);
	}
	
	
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
		$original = self::pre_loop($options);
		
		ob_start();
		get_template_part($slug, $name);
		$content = ob_get_clean();

		self::post_loop($original);
		
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