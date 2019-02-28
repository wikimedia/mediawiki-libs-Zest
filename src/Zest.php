<?php

namespace Wikimedia\Zest;

use \DOMDocument as DOMDocument;
use \DOMElement as DOMElement;
use \DOMNode as DOMNode;

/**
 * Zest.php (https://github.com/wikimedia/zest.php)
 * Copyright (c) 2019, C. Scott Ananian. (MIT licensed)
 * PHP port based on:
 *
 * Zest (https://github.com/chjj/zest)
 * A css selector engine.
 * Copyright (c) 2011-2012, Christopher Jeffrey. (MIT Licensed)
 * Domino version based on Zest v0.1.3 with bugfixes applied.
 */

class Zest {

	private static $singleton = null;

	private static function singleton() {
		if ( !self::$singleton ) {
			self::$singleton = new ZestInst();
		}
		return self::$singleton;
	}

	/**
	 * Find elements matching a CSS selector underneath $context.
	 * @param string $sel The CSS selector string
	 * @param DOMDocument|DOMElement $context The scope for the search
	 * @return array Elements matching the CSS selector
	 */
	public static function find( string $sel, DOMNode $context ): array {
		return self::singleton()->find( $sel, $context );
	}

	/**
	 * Determine whether an element matches the given selector.
	 * @param DOMNode $el The element to be tested
	 * @param string $sel The CSS selector string
	 * @return bool True iff the element matches the selector
	 */
	public static function matches( DOMNode $el, string $sel ): bool {
		return self::singleton()->matches( $el, $sel );
	}
}
