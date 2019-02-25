<?php

namespace Wikimedia\Zest;

class ZestFunc {
	/** @var callable */
	public $func;
	/** @var ?string */
	public $sel = null;
	/** @var ?callable */
	public $simple = null;
	/** @var ?callable */
	public $combinator = null;
	/** @var ?ZestFunc */
	public $test = null;
	/** @var ?string */
	public $lname = null;
	/** @var ?string */
	public $qname = null;
	function __construct( callable $func ) {
		$this->func = $func;
	}
}
