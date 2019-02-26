<?php

namespace Wikimedia\Zest;

use \DOMNode as DOMNode;
use \InvalidArgumentException as InvalidArgumentException;

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

	/**
	 * Helpers
	 */

	/*
$compareDocumentPosition = function ( $a, $b ) {
	return $a->compareDocumentPosition( $b );
};

$order = function ( $a, $b ) use ( &$compareDocumentPosition ) {
	return ( $compareDocumentPosition( $a, $b ) & 2 ) ? 1 : -1;
};
	*/

	private static function next( DOMNode $el ): ?DOMNode {
		while ( ( $el = $el->nextSibling ) && $el->nodeType !== 1 ) {
			// no op
		}
		return $el;
	}

	private static function prev( DOMNode $el ): ?DOMNode {
		while ( ( $el = $el->previousSibling ) && $el->nodeType !== 1 ) {
			// no op
		}
		return $el;
	}

	private static function child( DOMNode $el ): ?DOMNode {
		if ( $el = $el->firstChild ) {
			while ( $el->nodeType !== 1 && ( $el = $el->nextSibling ) ) {
				// no op
			}
		}
		return $el;
	}

	private static function lastChild( DOMNode $el ): ?DOMNode {
		if ( $el = $el->lastChild ) {
			while ( $el->nodeType !== 1 && ( $el = $el->previousSibling ) ) {
				// no op
			}
		}
		return $el;
	}

	private static function parentIsElement( DOMNode $n ): bool {
		if ( !$n->parentNode ) { return false;
  }
		$nodeType = $n->parentNode->nodeType;
		// The root `html` element can be a first- or last-child, too.
		return $nodeType === 1 || $nodeType === 9;
	}

	private static function unquote( string $str ): string {
		if ( !$str ) {
			return $str;
		}
		self::initRules();
		$ch = $str[ 0 ];
		if ( $ch === '"' || $ch === "'" ) {
			if ( substr( $str, - 1 ) === $ch ) {
				$str = substr( $str, 1, -1 );
			} else {
				// bad string.
				$str = substr( $str, 1 );
			}
			return preg_replace_callback( self::$rules->str_escape, function ( array $matches ) {
				$s = $matches[0];
				if ( !preg_match( '/^\\\(?:([0-9A-Fa-f]+)|([\r\n\f]+))/', $s, $m ) ) {
					return substr( $s, 1 );
				}
				if ( $m[ 2 ] ) {
					return ''; /* escaped newlines are ignored in strings. */
				}
				$cp = intval( $m[ 1 ], 16 );
				return IntlChar::chr( $cp );
			}, $str );
		} elseif ( preg_match( self::$rules->ident, $str ) ) {
			return self::decodeid( $str );
		} else {
			// NUMBER, PERCENTAGE, DIMENSION, etc
			return $str;
		}
	}

	private static function decodeid( string $str ): string {
		return preg_replace_callback( self::$rules->escape, function ( array $matches ) {
			$s = $matches[0];
			if ( !preg_match( '/^\\\([0-9A-Fa-f]+)/', $s, $m ) ) {
				return $s[ 1 ];
			}
			$cp = intval( $m[ 1 ], 16 );
			return IntlChar::chr( $cp );
		}, $str );
	}

	private static function makeInside( string $start, string $end ): string {
		$regex = preg_replace(
			'/>/', $end, preg_replace(
				'/</', $start, self::resource( self::$rules->inside )
			)
		);
		return '/' . $regex . '/Su';
	}

	private static function resource( string $regex ): string {
		// strip delimiter and flags from regular expression
		return preg_replace( '/(^\/)|(\/[a-z]*$)/Diu', '', $regex );
	}

	private static function replace( string $regex, string $name, string $val ): string {
		$regex = self::resource( $regex );
		$regex = str_replace( $name, self::resource( $val ), $regex );
		return '/' . $regex . '/Su';
	}

	private static function truncateUrl( string $url, int $num ): string {
		$url = preg_replace( '/^(?:\w+:\/\/|\/+)/', '', $url );
		$url = preg_replace( '/(?:\/+|\/*#.*?)$/', '', $url );
		return implode( '/', explode( '/', $url, $num ) );
	}

	/**
	 * Handle `nth` Selectors
	 */
	private static function parseNth( string $param ): object {
		$param = preg_replace( '/\s+/', '', $param );

		if ( $param === 'even' ) {
			$param = '2n+0';
		} elseif ( $param === 'odd' ) {
			$param = '2n+1';
		} elseif ( strpos( $param, 'n' ) === false ) {
			$param = '0n' . $param;
		}

		preg_match( '/^([+-])?(\d+)?n([+-])?(\d+)?$/', $param, $cap );

		$group = intval( ( $cap[1] ?: '' ) . ( $cap[2] ?: '1' ), 10 );
		$offset = intval( ( $cap[3] ?: '' ) . ( $cap[4] ?: '0' ), 10 );
		return (object)[
			'group' => $group,
			'offset' => $offset,
		];
	}

	private static function nth( string $param, callable $test, bool $last ): callable {
		$param = self::parseNth( $param );
		$group = $param->group;
		$offset = $param->offset;
		$find = ( !$last ) ? [ self::class, 'child' ] : [ self::class, 'lastChild' ];
		$advance = ( !$last ) ? [ self::class, 'next' ] : [ self::class, 'prev' ];
		return function ( DOMNode $el ) use ( $find, $test, $offset, $group, $advance ): bool {
			if ( !self::parentIsElement( $el ) ) {
				return false;
			}

			$rel = call_user_func( $find, $el->parentNode );
			$pos = 0;

			while ( $rel ) {
				if ( call_user_func( $test, $rel, $el ) ) {
					$pos++;
				}
				if ( $rel === $el ) {
					$pos -= $offset;
					return ( $group && $pos ) ?
						( $pos % $group ) === 0 && ( $pos < 0 === $group < 0 ) :
						!$pos;
				}
				$rel = call_user_func( $advance, $rel );
			}
			return false;
		};
	}

	/**
	 * Simple Selectors
	 */
	private static $selectors;

	private static function initSelectors() {
		if ( isset( self::$selectors ) ) { return;
  }
		self::$selectors = [
			'*' => function ( DOMNode $el ): bool {
				return true;
			},
			'type' => function ( string $type ): callable {
				$type = strtolower( $type );
				return function ( DOMNode $el ) use ( $type ): bool {
					return strtolower( $el->nodeName ) === $type;
				};
			},
			'attr' => function ( string $key, string $op, string $val, bool $i ): callable {
				$op = self::$operators[ $op ];
				return function ( DOMNode $el ) use ( $key, $i, $op, $val ): bool {
					/* XXX: the below all assumes a more complete PHP DOM than we have
					switch ( $key ) {
					#case 'for':
					#	$attr = $el->htmlFor; // Not supported in PHP DOM
					#	break;
					case 'class':
						// PHP DOM doesn't support $el->className
						// className is '' when non-existent
						// getAttribute('class') is null
						if ($el->hasAttributes() && $el->hasAttribute( 'class' ) ) {
							$attr = $el->getAttribute( 'class' );
						} else {
							$attr = null;
						}
						break;
					case 'href':
					case 'src':
						$attr = $el->getAttribute( $key, 2 );
						break;
					case 'title':
						// getAttribute('title') can be '' when non-existent sometimes?
						if ($el->hasAttribute('title')) {
							$attr = $el->getAttribute( 'title' );
						} else {
							$attr = null;
						}
						break;
					// careful with attributes with special getter functions
					case 'id':
					case 'lang':
					case 'dir':
					case 'accessKey':
					case 'hidden':
					case 'tabIndex':
					case 'style':
						if ( $el->getAttribute ) {
							$attr = $el->getAttribute( $key );
							break;
						}
					// falls through
					default:
						if ( $el->hasAttribute && !$el->hasAttribute( $key ) ) {
							break;
						}
						$attr = ( $el[ $key ] != null ) ?
							$el[ $key ] :
							$el->getAttribute && $el->getAttribute( $key );
						break;
					}
					*/
					// This is our simple PHP DOM version
					if ( $el->hasAttributes() && $el->hasAttribute( $key ) ) {
						$attr = $el->getAttribute( $key );
					} else {
						$attr = null;
					}
					// End simple PHP DOM version
					if ( $attr == null ) {
						return false;
					}
					$attr = $attr . '';
					if ( $i ) {
						$attr = strtolower( $attr );
						$val = strtolower( $val );
					}
					return call_user_func( $op, $attr, $val );
				};
			},
			':first-child' => function ( DOMNode $el ): bool {
				return !self::prev( $el ) && self::parentIsElement( $el );
			},
			':last-child' => function ( DOMNode $el ): bool {
				return !self::next( $el ) && self::parentIsElement( $el );
			},
			':only-child' => function ( DOMNode $el ): bool {
				return !self::prev( $el ) && !self::next( $el )
				&& self::parentIsElement( $el );
			},
			':nth-child' => function ( string $param, $last = false ): callable {
				return self::nth( $param, function () {
					return true;
				}, $last );
			},
			':nth-last-child' => function ( string $param ): callable {
				return self::$selectors[ ':nth-child' ]( $param, true );
			},
			':root' => function ( DOMNode $el ): bool {
				return $el->ownerDocument->documentElement === $el;
			},
			':empty' => function ( DOMNode $el ): bool {
				return !$el->firstChild;
			},
			':not' => function ( string $sel ) {
				$test = self::compileGroup( $sel );
				return function ( DOMNode $el ) use ( $test ): bool {
					return !call_user_func( $test, $el );
				};
			},
			':first-of-type' => function ( DOMNode $el ): bool {
				if ( !self::parentIsElement( $el ) ) {
					return false;
				}
				$type = $el->nodeName;
				while ( $el = self::prev( $el ) ) {
					if ( $el->nodeName === $type ) {
						return false;
					}
				}
				return true;
			},
			':last-of-type' => function ( DOMNode $el ): bool {
				if ( !self::parentIsElement( $el ) ) {
					return false;
				}
				$type = $el->nodeName;
				while ( $el = self::next( $el ) ) {
					if ( $el->nodeName === $type ) {
						return false;
					}
				}
				return true;
			},
			':only-of-type' => function ( DOMNode $el ): bool {
				return self::$selectors[ ':first-of-type' ]( $el ) &&
					self::$selectors[ ':last-of-type' ]( $el );
			},
			':nth-of-type' => function ( string $param, bool $last = false ): callable  {
				return self::nth( $param, function ( DOMNode $rel, DOMNode $el ) {
					return $rel->nodeName === $el->nodeName;
				}, $last );
			},
			':nth-last-of-type' => function ( string $param ): callable {
				return self::$selectors[ ':nth-of-type' ]( $param, true );
			},
			':checked' => function ( DOMNode $el ): bool {
				// XXX these properties don't exist in the PHP DOM
				// return !!( $el->checked || $el->selected );
				return (bool)( $el->hasAttribute( 'checked' ) || $el->hasAttribute( 'selected' ) );
			},
			':indeterminate' => function ( DOMNode $el ): bool {
				return !self::$selectors[ ':checked' ]( $el );
			},
			':enabled' => function ( DOMNode $el ): bool {
				// XXX these properties don't exist in the PHP DOM
				// return !$el->disabled && $el->type !== 'hidden';
				return !$el->hasAttribute( 'disabled' ) && $el->getAttribute( 'type' ) !== 'hidden';
			},
			':disabled' => function ( DOMNode $el ): bool {
				// XXX these properties don't exist in the PHP DOM
				// return !!$el->disabled;
				return $el->hasAttribute( 'disabled' );
			},
			/*
			':target' => function ( DOMNode $el ) use ( &$window ) {
				return $el->id === $window->location->hash->substring( 1 );
			},
			':focus' => function ( DOMNode $el ) {
				return $el === $el->ownerDocument->activeElement;
			},
			*/
			':is' => function ( string $sel ): callable {
				return self::compileGroup( $sel );
			},
			// :matches is an older name for :is; see
			// https://github.com/w3c/csswg-drafts/issues/3258
			':matches' => function ( string $sel ): callable {
				return self::$selectors[ ':is' ]( $sel );
			},
			':nth-match' => function ( string $param, bool $last = false ): callable {
				$args = preg_split( '/\s*,\s*/', $param );
				$arg = array_shift( $args );
				$test = self::compileGroup( implode( ',', $args ) );

				return self::nth( $arg, $test, $last );
			},
			':nth-last-match' => function ( string $param ): callable {
				return self::$selectors[ ':nth-match' ]( $param, true );
			},
			/*
			':links-here' => function ( DOMNode $el ) use ( &$window ) {
				return $el . '' === $window->location . '';
			},
			*/
			':lang' => function ( string $param ): callable {
				return function ( DOMNode $el ) use ( $param ): bool {
					while ( $el ) {
						// PHP DOM doesn't have 'lang' property
						$lang = $el->getAttribute( 'lang' );
						if ( $lang ) {
							return strpos( $lang, $param ) === 0;
						}
						$el = $el->parentNode;
					}
					return false;
				};
			},
			':dir' => function ( string $param ): callable {
				return function ( DOMNode $el ) use ( $param ): bool {
					while ( $el ) {
						$dir = $el->getAttribute( 'dir' );
						if ( $dir ) {
							return $dir === $param;
						}
						$el = $el->parentNode;
					}
					return false;
				};
			},
			':scope' => function ( DOMNode $el, $con = null ): bool {
				$context = $con ?? $el->ownerDocument;
				if ( $context->nodeType === 9 ) {
					return $el === $context->documentElement;
				}
				return $el === $context;
			},
# ':any-link' => function ( $el ) {
# return gettype( $el->href ) === 'string';
# },
# ':local-link' => function ( $el ) use ( &$window, &$truncateUrl ) {
# if ( $el->nodeName ) {
# return $el->href && $el->host === $window->location->host;
# }
# $param = +$el + 1;
# return function ( $el ) use ( &$el, &$window, &$truncateUrl, &$param ) {
# if ( !$el->href ) { return;  }
#
# $url = $window->location . '';
# $href = $el . '';
#
# return $truncateUrl( $url, $param ) === $truncateUrl( $href, $param );
# };
# },
# ':default' => function ( $el ) {
# return !!$el->defaultSelected;
# },
# ':valid' => function ( $el ) {
# return $el->willValidate || ( $el->validity && $el->validity->valid );
# },
			':invalid' => function ( $el ) use ( &$selectors ) {
				return !self::$selectors[ ':valid' ]( $el );
			},
# ':in-range' => function ( $el ) {
# return $el->value > $el->min && $el->value <= $el->max;
# },
			':out-of-range' => function ( DOMNode $el ): bool {
				return !self::$selectors[ ':in-range' ]( $el );
			},
			':required' => function ( DOMNode $el ): bool {
				return $el->hasAttribute( 'required' );
			},
			':optional' => function ( DOMNode $el ): bool {
				return !self::$selectors[ ':required' ]( $el );
			},
			':read-only' => function ( DOMNode $el ): bool {
				if ( $el->hasAttribute( 'readOnly' ) ) {
					return true;
				}

				$attr = $el->getAttribute( 'contenteditable' );
				$name = strtolower( $el->nodeName );

				$name = $name !== 'input' && $name !== 'textarea';

				return ( $name || $el->hasAttribute( 'disabled' ) ) && $attr == null;
			},
			':read-write' => function ( DOMNode $el ): bool {
				return !self::$selectors[ ':read-only' ]( $el );
			},
			':hover' => function ( DOMNode $el ): bool {
				throw new Error( ':hover is not supported.' );
			},
			':active' => function ( DOMNode $el ): bool {
			throw new Error( ':active is not supported.' );
			},
			':link' => function ( DOMNode $el ): bool {
				throw new Error( ':link is not supported.' );
			},
			':visited' => function ( DOMNode $el ): bool {
			throw new Error( ':visited is not supported.' );
			},
			':column' => function ( DOMNode $el ): bool {
				throw new Error( ':column is not supported.' );
			},
			':nth-column' => function ( DOMNode $el ): bool {
			throw new Error( ':nth-column is not supported.' );
			},
			':nth-last-column' => function ( DOMNode $el ): bool {
				throw new Error( ':nth-last-column is not supported.' );
			},
			':current' => function ( DOMNode $el ): bool {
			throw new Error( ':current is not supported.' );
			},
			':past' => function ( DOMNode $el ): bool {
				throw new Error( ':past is not supported.' );
			},
			':future' => function ( DOMNode $el ): bool {
			throw new Error( ':future is not supported.' );
			},
			// Non-standard, for compatibility purposes.
			':contains' => function ( string $param ): callable {
				return function ( DOMNode $el ) use ( $param ): bool {
					$text = $el->textContent;
					return strpos( $text, $param ) !== false;
				};
			},
			':has' => function ( string $param ): callable {
				return function ( DOMNode $el ) use ( $param ): bool {
					return count( self::find( $param, $el ) ) > 0;
				};
			}
			// Potentially add more pseudo selectors for
			// compatibility with sizzle and most other
			// selector engines (?).
		];
	}

	/**
	 * Attribute Operators
	 */
	private static $operators;

	private static function initOperators() {
		if ( isset( self::$operators ) ) { return;
  }
		self::$operators = [
			'-' => function ( string $attr, string $val ): bool {
				return true;
			},
			'=' => function ( string $attr, string $val ): bool {
				return $attr === $val;
			},
			'*=' => function ( string $attr, string $val ): bool {
				return strpos( $attr, $val ) !== false;
			},
			'~=' => function ( string $attr, string $val ): bool {
				$attrLen = strlen( $attr );
				$valLen = strlen( $val );
				for ( $s = 0;  $s < $attrLen;  $s = $i + 1 ) {
					$i = strpos( $attr, $val, $s );
					if ( $i === false ) {
						return false;
					}
					$j = $i + $valLen;
					$f = ( $i === 0 ) ? ' ' : $attr[ $i - 1 ];
					$l = ( $j >= $attrLen ) ? ' ' : $attr[ $j ];
					if ( $f === ' ' && $l === ' ' ) {
						return true;
					}
				}
				return false;
			},
			'|=' => function ( string $attr, string $val ): bool {
				$i = strpos( $attr, $val );
				if ( $i !== 0 ) {
					return false;
				}
				$j = $i + strlen( $val );
				if ( $j >= strlen( $attr ) ) {
					return true;
				}
				$l = $attr[ $j ];
				return $l === '-';
			},
			'^=' => function ( string $attr, string $val ): bool {
				return strpos( $attr, $val ) === 0;
			},
			'$=' => function ( string $attr, string $val ): bool {
				$i = strrpos( $attr, $val );
				return $i !== false && $i + strlen( $val ) === strlen( $attr );
			},
			// non-standard
			'!=' => function ( string $attr, string $val ): bool {
				return $attr !== $val;
			},
		];
	}

	/**
	 * Combinator Logic
	 */
	private static $combinators;

	private static function initCombinators() {
		if ( isset( self::$combinators ) ) {
			return;
		}
		self::$combinators = [
			' ' => function ( callable $test ): callable {
				return function ( DOMNode $el ) use ( $test ): ?DOMNode {
					/*jshint -W084 */
					while ( $el = $el->parentNode ) {
						if ( call_user_func( $test, $el ) ) {
							return $el;
						}
					}
					return null;
				};
			},
			'>' => function ( callable $test ): callable {
				return function ( DOMNode $el ) use ( $test ): ?DOMNode {
					if ( $el = $el->parentNode ) {
						if ( call_user_func( $test, $el ) ) {
							return $el;
						}
					}
					return null;
				};
			},
			'+' => function ( callable $test ): callable {
				return function ( DOMNode $el ) use ( $test ): ?DOMNode {
					if ( $el = self::prev( $el ) ) {
						if ( call_user_func( $test, $el ) ) {
							return $el;
						}
					}
					return null;
				};
			},
			'~' => function ( callable $test ): callable {
				return function ( DOMNode $el ) use ( $test ): ?DOMNode {
					while ( $el = self::prev( $el ) ) {
						if ( call_user_func( $test, $el ) ) {
							return $el;
						}
					}
					return null;
				};
			},
			'noop' => function ( callable $test ): callable {
				return function ( DOMNode $el ) use ( $test ): ?DOMNode {
					if ( call_user_func( $test, $el ) ) {
						return $el;
					}
					return null;
				};
			},
			'ref' => function ( callable $test, string $name ): ZestFunc {
				$node = null;
				$ref = new ZestFunc( function ( DOMNode $el ) use ( &$node, &$ref ) : boolean {
					$doc = $el->ownerDocument;
					$nodes = $doc->getElementsByTagName( '*' );
					$i = count( $nodes );

					while ( $i-- ) {
						$node = $nodes[ $i ];
						if ( call_user_func( $ref->test->func, $el ) ) {
							$node = null;
							return true;
						}
					}

					$node = null;
					return false;
				} );

				$ref->combinator = function ( DOMNode $el ) use ( &$node, $name, $test ): ?DOMNode {
					if ( !$node || !( $node instanceof DOMElement ) ) {
						return null;
					}

					$attr = $node->getAttribute( $name ) || '';
					if ( $attr[ 0 ] === '#' ) {
						$attr = $attr->substring( 1 );
					}

					$id = $node->getAttribute( 'id' ) || '';
					if ( $attr === $el->id && call_user_func( $test, $node ) ) {
						return $node;
					}
				};

				return $ref;
			},
		];
	}

	/**
	 * Grammar
	 */

	private static $rules = [
		'escape' => '/\\\(?:[^0-9A-Fa-f\r\n]|[0-9A-Fa-f]{1,6}[\r\n\t ]?)/',
		'str_escape' => '/(escape)|\\\(\n|\r\n?|\f)/',
		'nonascii' => '/[\x{00A0}-\x{FFFF}]/',
		'cssid' => '/(?:(?!-?[0-9])(?:escape|nonascii|[-_a-zA-Z0-9])+)/',
		'qname' => '/^ *(cssid|\*)/',
		'simple' => '/^(?:([.#]cssid)|pseudo|attr)/',
		'ref' => '/^ *\/(cssid)\/ */',
		'combinator' => '/^(?: +([^ \w*.#\\\]) +|( )+|([^ \w*.#\\\]))(?! *$)/',
		'attr' => '/^\[(cssid)(?:([^\w]?=)(inside))?\]/',
		'pseudo' => '/^(:cssid)(?:\((inside)\))?/',
		'inside' => "/(?:\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*'|<[^\"'>]*>|\\\\[\"'>]|[^\"'>])*/",
		'ident' => '/^(cssid)$/',
	];

	public static function initRules() {
		if ( is_object( self::$rules ) ) { return;
  }
		self::$rules = (object)self::$rules;
		self::$rules->cssid = self::replace( self::$rules->cssid, 'nonascii', self::$rules->nonascii );
		self::$rules->cssid = self::replace( self::$rules->cssid, 'escape', self::$rules->escape );
		self::$rules->qname = self::replace( self::$rules->qname, 'cssid', self::$rules->cssid );
		self::$rules->simple = self::replace( self::$rules->simple, 'cssid', self::$rules->cssid );
		self::$rules->ref = self::replace( self::$rules->ref, 'cssid', self::$rules->cssid );
		self::$rules->attr = self::replace( self::$rules->attr, 'cssid', self::$rules->cssid );
		self::$rules->pseudo = self::replace( self::$rules->pseudo, 'cssid', self::$rules->cssid );
		self::$rules->inside = self::replace( self::$rules->inside, "[^\"'>]*", self::$rules->inside );
		self::$rules->attr = self::replace( self::$rules->attr, 'inside', self::makeInside( '\[', '\]' ) );
		self::$rules->pseudo = self::replace( self::$rules->pseudo, 'inside', self::makeInside( '\(', '\)' ) );
		self::$rules->simple = self::replace( self::$rules->simple, 'pseudo', self::$rules->pseudo );
		self::$rules->simple = self::replace( self::$rules->simple, 'attr', self::$rules->attr );
		self::$rules->ident = self::replace( self::$rules->ident, 'cssid', self::$rules->cssid );
		self::$rules->str_escape = self::replace( self::$rules->str_escape, 'escape', self::$rules->escape );
	}

	/**
	 * Compiling
	 */

	private static function compile( string $sel ): ZestFunc {
		$sel = preg_replace( '/^\s+|\s+$/', '', $sel );
		$test = null;
		$filter = [];
		$buff = [];
		$subject = null;
		$qname = null;
		$cap = null;
		$op = null;
		$ref = null;

		while ( $sel ) {
			if ( preg_match( self::$rules->qname, $sel, $cap ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$qname = $cap[ 1 ];
				$buff[] = self::tokQname( $qname );
			} elseif ( preg_match( self::$rules->simple, $sel, $cap, PREG_UNMATCHED_AS_NULL ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$qname = '*';
				$buff[] = self::tokQname( $qname );
				$buff[] = self::tok( $cap );
			} else {
				throw new InvalidArgumentException( 'Invalid selector.' );
			}

			while ( preg_match( self::$rules->simple, $sel, $cap, PREG_UNMATCHED_AS_NULL ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$buff[] = self::tok( $cap );
			}

			if ( $sel && $sel[ 0 ] === '!' ) {
				$sel = substr( $sel, 1 );
				$subject = self::makeSubject();
				$subject->qname = $qname;
				$buff[] = $subject->simple;
			}

			if ( preg_match( self::$rules->ref, $sel, $cap ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$ref = self::$combinators['ref']( self::makeSimple( $buff ), self::decodeid( $cap[ 1 ] ) );
				$filter[] = $ref->combinator;
				$buff = [];
				continue;
			}

			if ( preg_match( self::$rules->combinator, $sel, $cap, PREG_UNMATCHED_AS_NULL ) ) {
				$sel = substr( $sel, strlen( $cap[0] ) );
				$op = $cap[ 1 ] ?? $cap[ 2 ] ?? $cap[ 3 ];
				if ( $op === ',' ) {
					$filter[] = self::$combinators['noop']( self::makeSimple( $buff ) );
					break;
				}
			} else {
				$op = 'noop';
			}

			if ( !isset( self::$combinators[ $op ] ) ) {
				throw new InvalidArgumentException( 'Bad combinator: ' . $op );
			}
			$filter[] = self::$combinators[ $op ]( self::makeSimple( $buff ) );
			$buff = [];
		}

		$test = self::makeTest( $filter );
		$test->qname = $qname;
		$test->sel = $sel;

		if ( $subject ) {
			$subject->lname = $test->qname;

			$subject->test = $test;
			$subject->qname = $subject->qname;
			$subject->sel = $test->sel;
			$test = $subject;
		}

		if ( $ref ) {
			$ref->test = $test;
			$ref->qname = $test->qname;
			$ref->sel = $test->sel;
			$test = $ref;
		}

		return $test;
	}

	private static function tokQname( string $cap ): callable {
		// qname
		return ( $cap === '*' ) ?
			self::$selectors[ '*' ] :
			self::$selectors['type']( self::decodeid( $cap ) );
	}

	private static function tok( array $cap ): callable {
		// class/id
		if ( $cap[ 1 ] ) {
			return $cap[ 1 ][ 0 ] === '.'
			// XXX unescape here?  or in attr?
				? self::$selectors['attr']( 'class', '~=', self::decodeid( substr( $cap[ 1 ], 1 ) ), false ) :
				self::$selectors['attr']( 'id', '=', self::decodeid( substr( $cap[ 1 ], 1 ) ), false );
		}

		// pseudo-name
		// inside-pseudo
		if ( $cap[ 2 ] ) {
			return ( isset( $cap[3] ) && $cap[ 3 ] ) ?
				self::$selectors[ self::decodeid( $cap[ 2 ] ) ]( self::unquote( $cap[ 3 ] ) ) :
				self::$selectors[ self::decodeid( $cap[ 2 ] ) ];
		}

		// attr name
		// attr op
		// attr value
		if ( $cap[ 4 ] ) {
			$value = $cap[ 6 ] ?? '';
			$i = preg_match( "/[\"'\\s]\\s*I\$/", $value );
			if ( $i ) {
				$value = preg_replace( '/\s*I$/i', '', $value, 1 );
			}
			return self::$selectors['attr']( self::decodeid( $cap[ 4 ] ), $cap[ 5 ] ?? '-', self::unquote( $value ), (bool)$i );
		}

		throw new InvalidArgumentException( 'Unknown Selector.' );
	}

	// Returns true if all $func return true
	private static function makeSimple( array $func ): callable {
		$l = count( $func );

		// Potentially make sure
		// `el` is truthy.
		if ( $l < 2 ) {
			return $func[ 0 ];
		}

		return function ( DOMNode $el ) use ( $l, $func ): bool {
			if ( !$el ) {
				return false;
			}
			for ( $i = 0;  $i < $l;  $i++ ) {
				if ( !call_user_func( $func[ $i ], $el ) ) {
					return false;
				}
			}
			return true;
		};
	}

	// Returns the element that all $func return
	private static function makeTest( array $func ): ZestFunc {
		if ( count( $func ) < 2 ) {
			return new ZestFunc( function ( DOMNode $el ) use ( $func ): bool {
				return (bool)call_user_func( $func[ 0 ], $el );
			} );
		}
		return new ZestFunc( function ( DOMNode $el ) use ( $func ): bool {
			$i = count( $func );
			while ( $i-- ) {
				if ( !( $el = call_user_func( $func[ $i ], $el ) ) ) {
					return false;
				}
			}
			return true;
		} );
	}

	private static function makeSubject(): ZestFunc {
		$target = null;

		$subject = new ZestFunc( function ( DOMNode $el ) use ( &$subject, &$target ): bool {
			$node = $el->ownerDocument;
			$scope = $node->getElementsByTagName( $subject->lname );
			$i = count( $scope );

			while ( $i-- ) {
				if ( preg_match( $subject, $scope[ $i ] ) && $target === $el ) {
					$target = null;
					return true;
				}
			}

			$target = null;
			return false;
		} );

		$subject->simple = function ( DOMNode $el ): bool {
			$target = $el;
			return true;
		};

		return $subject;
	}

	private static function compileGroup( string $sel ): callable {
		$test = self::compile( $sel );
		$tests = [ $test ];

		while ( $test->sel ) {
			$test = self::compile( $test->sel );
			$tests[] = $test;
		}

		if ( count( $tests ) < 2 ) {
			return $test->func;
		}

		return function ( DOMNode $el ) use ( $tests ): bool {
			for ( $i = 0, $l = count( $tests );  $i < $l;  $i++ ) {
				if ( call_user_func( $tests[ $i ]->func, $el ) ) {
					return true;
				}
			}
			return false;
		};
	}

	/**
	 * Selection
	 */

	// $node should be a DOMDocument or a DOMElement
	private static function findInternal( string $sel, DOMNode $node ): array {
		$results = [];
		$test = self::compile( $sel );
		$scope = $node->getElementsByTagName( $test->qname );
		$i = 0;
		$el = null;

		foreach ( $scope as $el ) {
			if ( call_user_func( $test->func, $el ) ) {
				$results[] = $el;
			}
		}

		if ( $test->sel ) {
			while ( $test->sel ) {
				$test = self::compile( $test->sel );
				$scope = $node->getElementsByTagName( $test->qname );
				foreach ( $scope as $el ) {
					if ( call_user_func( $test->func, $el ) && !in_array( $el, $results ) ) {
						$results[] = $el;
					}
				}
			}
			// $results->sort( $order );//XXX
		}

		return $results;
	}

	/**
	 * Find elements matching a CSS selector underneath $context.
	 * @param string $sel The CSS selector string
	 * @param DOMNode $context The scope for the search
	 * @return array Elements matching the CSS selector
	 */
	public static function find( string $sel, DOMNode $context ): array {
		self::init(); // XXX
		/* when context isn't a DocumentFragment and the selector is simple: */
		if ( $context->nodeType !== 11 && strpos( $sel, ' ' ) === false ) {
			if ( $sel[ 0 ] === '#' /*&& $context->rooted*/ && preg_match( '/^#[A-Z_][-A-Z0-9_]*$/', $sel ) ) {
				/*
				if ( $context->doc->_hasMultipleElementsWithId ) {
					$id = $sel->substring( 1 );
					if ( !$context->doc->_hasMultipleElementsWithId( $id ) ) {
						$r = $context->doc->getElementById( $id );
						return ( $r ) ? [ $r ] : [];
					}
				}
				*/
				if ( $context instanceof \DOMDocument ) {
					$id = substr( $sel, 1 );
					$r = $context->getElementById( $id );
					return ( $r ) ? [ $r ] : [];
				}
			}
			/*
			if ( $sel[ 0 ] === '.' && preg_match( '/^\.\w+$/', $sel ) ) {
				return $context->getElementsByClassName( $sel->substring( 1 ) );
			}
			*/
			if ( preg_match( '/^\w+$/', $sel ) ) {
				$nodes = [];
				foreach ( $context->getElementsByTagName( $sel ) as $el ) {
					$nodes[] = $el;
				}
				return $nodes;
			}
		}
		/* do things the hard/slow way */
		return self::findInternal( $sel, $context );
	}

	/**
	 * Determine whether an element matches the given selector.
	 * @param DOMNode $el The element to be tested
	 * @param string $sel The CSS selector string
	 * @return bool True iff the element matches the selector
	 */
	public static function matches( DOMNode $el, string $sel ): bool {
		self::init(); // XXX
		$test = new ZestFunc( function ( DOMNode $el ):bool {
			return true;
		} );
		$test->sel = $sel;
		do {
			$test = self::compile( $test->sel );
			if ( call_user_func( $test->func, $el ) ) {
				return true;
			}
		} while ( $test->sel );
		return false;
	}

	private static function init() {
		self::initRules();
		self::initSelectors();
		self::initOperators();
		self::initCombinators();
	}

}
