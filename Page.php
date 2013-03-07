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
	 * @param	mixed	$options
	 * @return	Classy_Post
	 */
	public function __construct($options = array()) {
		parent::__construct($options);
	
		return $this;
	}

}