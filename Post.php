<?php

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
	 * @return	Classy_Post
	 */
	public function __construct($options = array()) {
		parent::__construct($options);
	
		return $this;
	}

}