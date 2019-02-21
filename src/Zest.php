<?php

namespace Wikimedia\Zest;

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

$window = Object::create( null, [
		'location' => [ 'get' => function () {
				throw new Error( 'window.location is not supported.' );
			}
		]
	]
);

$compareDocumentPosition = function ( $a, $b ) {
	return $a->compareDocumentPosition( $b );
};

$order = function ( $a, $b ) use ( &$compareDocumentPosition ) {
	/* jshint bitwise: false */
	return ( $compareDocumentPosition( $a, $b ) & 2 ) ? 1 : -1;
};

$next = function ( $el ) {
	while ( ( $el = $el->nextSibling )
&&			$el->nodeType !== 1
	) {}
	return $el;
};

$prev = function ( $el ) {
	while ( ( $el = $el->previousSibling )
&&			$el->nodeType !== 1
	) {}
	return $el;
};

$child = function ( $el ) {
	/*jshint -W084 */
	if ( $el = $el->firstChild ) {
		while ( $el->nodeType !== 1
&&				( $el = $el->nextSibling )
		) {}
	}
	return $el;
};

$lastChild = function ( $el ) {
	/*jshint -W084 */
	if ( $el = $el->lastChild ) {
		while ( $el->nodeType !== 1
&&				( $el = $el->previousSibling )
		) {}
	}
	return $el;
};

$unquote = function ( $str ) {
	if ( !$str ) { return $str;  }
	$ch = $str[ 0 ];
	if ( $ch === '"' || $ch === "'" ) {
		if ( $str[ count( $str ) - 1 ] === $ch ) {
			$str = array_slice( $str, 1, -1 );
		} else {
			// bad string.
			$str = array_slice( $str, 1 );
		}
		return str_replace( $rules->str_escape, function ( $s ) {
				$m = /*RegExp#exec*/preg_match( '/^\\\(?:([0-9A-Fa-f]+)|([\r\n\f]+))/', $s, $FIXME );
				if ( !$m ) { return array_slice( $s, 1 );  }
				if ( $m[ 2 ] ) { return ''; /* escaped newlines are ignored in strings. */ }/* escaped newlines are ignored in strings. */
				$cp = intval( $m[ 1 ], 16 );
				return ( String::fromCodePoint ) ? String::fromCodePoint( $cp ) :
				// Not all JavaScript implementations have String.fromCodePoint yet.
				String::fromCharCode( $cp );
			}, $str )







		;
	} elseif ( preg_match( $rules->ident, $str ) ) {
		return decodeid( $str );
	} else {
		// NUMBER, PERCENTAGE, DIMENSION, etc
		return $str;
	}
};

$decodeid = function ( $str ) {
	return str_replace( $rules->escape, function ( $s ) {
			$m = /*RegExp#exec*/preg_match( '/^\\\([0-9A-Fa-f]+)/', $s, $FIXME );
			if ( !$m ) { return $s[ 1 ];  }
			$cp = intval( $m[ 1 ], 16 );
			return ( String::fromCodePoint ) ? String::fromCodePoint( $cp ) :
			// Not all JavaScript implementations have String.fromCodePoint yet.
			String::fromCharCode( $cp );
		}, $str )






	;
};

$indexOf = ( ( function () {
	if ( Array::prototype::indexOf ) {
		return Array::prototype::indexOf;
	}
	return function ( $obj, $item ) {
		$i = count( $this );
		while ( $i-- ) {
			if ( $this[ $i ] === $item ) { return $i;  }
		}
		return -1;
	};
} ) );
$indexOf = $indexOf();

$makeInside = function ( $start, $end ) {
	$regex = preg_replace(

		'/>/', $end, preg_replace(
			'/</', $start, $rules->inside->source )
	)
	;

	return new RegExp( $regex );
};

$replace = function ( $regex, $name, $val ) {
	$regex = $regex->source;
	$regex = str_replace( $name, $val->source || $val, $regex );
	return new RegExp( $regex );
};

$truncateUrl = function ( $url, $num ) {
	return implode(



		'/', explode(


			'/', $num, preg_replace(

				'/(?:\/+|\/*#.*?)$/', '', preg_replace(
					'/^(?:\w+:\/\/|\/+)/', '', $url, 1 )
				, 1
			)
		)
	);
};

/**
 * Handle `nth` Selectors
 */

$parseNth = function ( $param_, $test ) {
	$param = preg_replace( '/\s+/', '', $param_ );
	$cap = null;

	if ( $param === 'even' ) {
		$param = '2n+0';
	} elseif ( $param === 'odd' ) {
		$param = '2n+1';
	} elseif ( array_search( 'n', $param ) === -1 ) {
		$param = '0n' . $param;
	}

	$cap = /*RegExp#exec*/preg_match( '/^([+-])?(\d+)?n([+-])?(\d+)?$/', $param, $FIXME );

	return [
		'group' => ( $cap[ 1 ] === '-' ) ?
		-( $cap[ 2 ] || 1 ) :
		+( $cap[ 2 ] || 1 ),
		'offset' => ( $cap[ 4 ] ) ?
		( ( $cap[ 3 ] === '-' ) ? -$cap[ 4 ] : +$cap[ 4 ] ) :
		0
	];
};

$nth = function ( $param_, $test, $last ) use ( &$parseNth, &$child, &$lastChild, &$next, &$prev ) {
	$param = $parseNth( $param_ );
	$group = $param->group;
	$offset = $param->offset;
	$find = ( !$last ) ? $child : $lastChild;
	$advance = ( !$last ) ? $next : $prev;

	return function ( $el ) use ( &$find, &$test, &$offset, &$group, &$advance ) {
		if ( $el->parentNode->nodeType !== 1 ) { return;  }

		$rel = $find( $el->parentNode );
		$pos = 0;

		while ( $rel ) {
			if ( $test( $rel, $el ) ) { $pos++;  }
			if ( $rel === $el ) {
				$pos -= $offset;
				return ( $group && $pos ) ?
				( $pos % $group ) === 0 && ( $pos < 0 === $group < 0 ) :
				!$pos;
			}
			$rel = $advance( $rel );
		}
	};
};

/**
 * Simple Selectors
 */

$selectors = [
	'*' => ( ( function () {
		if ( false/*function() {
			      var el = document.createElement('div');
			      el.appendChild(document.createComment(''));
			      return !!el.getElementsByTagName('*')[0];
			    }()*/
		) {
			return function ( $el ) {
				if ( $el->nodeType === 1 ) { return true;  }
			};
		}
		return function () {
			return true;
		};
	} ) );
	$null = $null(),
	'type' => function ( $type ) {
		$type = strtolower( $type );
		return function ( $el ) use ( &$type ) {
			return strtolower( $el->nodeName ) === $type;
		};
	},
	'attr' => function ( $key, $op, $val, $i ) {
		$op = $operators[ $op ];
		return function ( $el ) use ( &$key, &$i, &$op ) {
			$attr = null;
			switch ( $key ) {
				case 'for':
				$attr = $el->htmlFor;
				break;
				case 'class':
				// className is '' when non-existent
				// getAttribute('class') is null
				$attr = $el->className;
				if ( $attr === '' && $el->getAttribute( 'class' ) == null ) {
					$attr = null;
				}
				break;
				case 'href':

				case 'src':
				$attr = $el->getAttribute( $key, 2 );
				break;
				case 'title':
				// getAttribute('title') can be '' when non-existent sometimes?
				$attr = $el->getAttribute( 'title' ) || null;
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
				/* falls through */
				default:
				if ( $el->hasAttribute && !$el->hasAttribute( $key ) ) {
					break;
				}
				$attr = ( $el[ $key ] != null ) ?
				$el[ $key ] :
				$el->getAttribute && $el->getAttribute( $key );
				break;
			}
			if ( $attr == null ) { return;  }
			$attr = $attr . '';
			if ( $i ) {
				$attr = strtolower( $attr );
				$val = strtolower( $val );
			}
			return $op( $attr, $val );
		};
	},
	':first-child' => function ( $el ) use ( &$prev ) {
		return !$prev( $el ) && $el->parentNode->nodeType === 1;
	},
	':last-child' => function ( $el ) use ( &$next ) {
		return !$next( $el ) && $el->parentNode->nodeType === 1;
	},
	':only-child' => function ( $el ) use ( &$prev, &$next ) {
		return !$prev( $el ) && !$next( $el )
&&			$el->parentNode->nodeType === 1;
	},
	':nth-child' => function ( $param, $last ) use ( &$nth ) {
		return $nth( $param, function () {
				return true;
			}, $last
		);
	},
	':nth-last-child' => function ( $param ) use ( &$selectors ) {
		return $selectors[ ':nth-child' ]( $param, true );
	},
	':root' => function ( $el ) {
		return $el->ownerDocument->documentElement === $el;
	},
	':empty' => function ( $el ) {
		return !$el->firstChild;
	},
	':not' => function ( $sel ) {
		$test = compileGroup( $sel );
		return function ( $el ) use ( &$test ) {
			return !$test( $el );
		};
	},
	':first-of-type' => function ( $el ) use ( &$prev ) {
		if ( $el->parentNode->nodeType !== 1 ) { return;  }
		$type = $el->nodeName;
		/*jshint -W084 */
		while ( $el = $prev( $el ) ) {
			if ( $el->nodeName === $type ) { return;  }
		}
		return true;
	},
	':last-of-type' => function ( $el ) use ( &$next ) {
		if ( $el->parentNode->nodeType !== 1 ) { return;  }
		$type = $el->nodeName;
		/*jshint -W084 */
		while ( $el = $next( $el ) ) {
			if ( $el->nodeName === $type ) { return;  }
		}
		return true;
	},
	':only-of-type' => function ( $el ) use ( &$selectors ) {
		return $selectors[ ':first-of-type' ]( $el )
&&			$selectors[ ':last-of-type' ]( $el );
	},
	':nth-of-type' => function ( $param, $last ) use ( &$nth ) {
		return $nth( $param, function ( $rel, $el ) {
				return $rel->nodeName === $el->nodeName;
			}, $last
		);
	},
	':nth-last-of-type' => function ( $param ) use ( &$selectors ) {
		return $selectors[ ':nth-of-type' ]( $param, true );
	},
	':checked' => function ( $el ) {
		return !!( $el->checked || $el->selected );
	},
	':indeterminate' => function ( $el ) use ( &$selectors ) {
		return !$selectors[ ':checked' ]( $el );
	},
	':enabled' => function ( $el ) {
		return !$el->disabled && $el->type !== 'hidden';
	},
	':disabled' => function ( $el ) {
		return !!$el->disabled;
	},
	':target' => function ( $el ) use ( &$window ) {
		return $el->id === $window->location->hash->substring( 1 );
	},
	':focus' => function ( $el ) {
		return $el === $el->ownerDocument->activeElement;
	},
	':matches' => function ( $sel ) {
		return compileGroup( $sel );
	},
	':nth-match' => function ( $param, $last ) use ( &$nth ) {
		$args = preg_split( '/\s*,\s*/', $param );
		$arg = array_shift( $args );
		$test = compileGroup( implode( ',', $args ) );

		return $nth( $arg, $test, $last );
	},
	':nth-last-match' => function ( $param ) use ( &$selectors ) {
		return $selectors[ ':nth-match' ]( $param, true );
	},
	':links-here' => function ( $el ) use ( &$window ) {
		return $el . '' === $window->location . '';
	},
	':lang' => function ( $param ) {
		return function ( $el ) {
			while ( $el ) {
				if ( $el->lang ) { return array_search( $param, $el->lang ) === 0;  }
				$el = $el->parentNode;
			}
		};
	},
	':dir' => function ( $param ) {
		return function ( $el ) use ( &$param ) {
			while ( $el ) {
				if ( $el->dir ) { return $el->dir === $param;  }
				$el = $el->parentNode;
			}
		};
	},
	':scope' => function ( $el, $con ) {
		$context = $con || $el->ownerDocument;
		if ( $context->nodeType === 9 ) {
			return $el === $context->documentElement;
		}
		return $el === $context;
	},
	':any-link' => function ( $el ) {
		return gettype( $el->href ) === 'string';
	},
	':local-link' => function ( $el ) use ( &$window, &$truncateUrl ) {
		if ( $el->nodeName ) {
			return $el->href && $el->host === $window->location->host;
		}
		$param = +$el + 1;
		return function ( $el ) use ( &$el, &$window, &$truncateUrl, &$param ) {
			if ( !$el->href ) { return;  }

			$url = $window->location . '';
			$href = $el . '';

			return $truncateUrl( $url, $param ) === $truncateUrl( $href, $param );
		};
	},
	':default' => function ( $el ) {
		return !!$el->defaultSelected;
	},
	':valid' => function ( $el ) {
		return $el->willValidate || ( $el->validity && $el->validity->valid );
	},
	':invalid' => function ( $el ) use ( &$selectors ) {
		return !$selectors[ ':valid' ]( $el );
	},
	':in-range' => function ( $el ) {
		return $el->value > $el->min && $el->value <= $el->max;
	},
	':out-of-range' => function ( $el ) use ( &$selectors ) {
		return !$selectors[ ':in-range' ]( $el );
	},
	':required' => function ( $el ) {
		return !!$el->required;
	},
	':optional' => function ( $el ) {
		return !$el->required;
	},
	':read-only' => function ( $el ) {
		if ( $el->readOnly ) { return true;  }

		$attr = $el->getAttribute( 'contenteditable' );
		$prop = $el->contentEditable;
		$name = strtolower( $el->nodeName );

		$name = $name !== 'input' && $name !== 'textarea';

		return ( $name || $el->disabled ) && $attr == null && $prop !== 'true';
	},
	':read-write' => function ( $el ) use ( &$selectors ) {
		return !$selectors[ ':read-only' ]( $el );
	},
	':hover' => function () {
		throw new Error( ':hover is not supported.' );
	},
	':active' => function () {
		throw new Error( ':active is not supported.' );
	},
	':link' => function () {
		throw new Error( ':link is not supported.' );
	},
	':visited' => function () {
		throw new Error( ':visited is not supported.' );
	},
	':column' => function () {
		throw new Error( ':column is not supported.' );
	},
	':nth-column' => function () {
		throw new Error( ':nth-column is not supported.' );
	},
	':nth-last-column' => function () {
		throw new Error( ':nth-last-column is not supported.' );
	},
	':current' => function () {
		throw new Error( ':current is not supported.' );
	},
	':past' => function () {
		throw new Error( ':past is not supported.' );
	},
	':future' => function () {
		throw new Error( ':future is not supported.' );
	},
	// Non-standard, for compatibility purposes.
	':contains' => function ( $param ) {
		return function ( $el ) {
			$text = $el->innerText || $el->textContent || $el->value || '';
			return array_search( $param, $text ) !== -1;
		};
	},
	':has' => function ( $param ) {
		return function ( $el ) {
			return count( find( $param, $el ) ) > 0;
		};
	}
	// Potentially add more pseudo selectors for
	// compatibility with sizzle and most other
	// selector engines (?).
];

/**
 * Attribute Operators
 */

$operators = [
	'-' => function () {
		return true;
	},
	'=' => function ( $attr, $val ) {
		return $attr === $val;
	},
	'*=' => function ( $attr, $val ) {
		return array_search( $val, $attr ) !== -1;
	},
	'~=' => function ( $attr, $val ) {
		$i = null;
		$s = null;
		$f = null;
		$l = null;

		for ( $s = 0;  true;  $s = $i + 1 ) {
			$i = array_search( $val, $attr );
			if ( $i === -1 ) { return false;  }
			$f = $attr[ $i - 1 ];
			$l = $attr[ $i + count( $val ) ];
			if ( ( !$f || $f === ' ' ) && ( !$l || $l === ' ' ) ) { return true;  }
		}
	},
	'|=' => function ( $attr, $val ) {
		$i = array_search( $val, $attr );
		$l = null;

		if ( $i !== 0 ) { return;  }
		$l = $attr[ $i + count( $val ) ];

		return $l === '-' || !$l;
	},
	'^=' => function ( $attr, $val ) {
		return array_search( $val, $attr ) === 0;
	},
	'$=' => function ( $attr, $val ) {
		$i = array_search( $val, $attr );
		return $i !== -1 && $i + count( $val ) === count( $attr );
	},
	// non-standard
	'!=' => function ( $attr, $val ) {
		return $attr !== $val;
	}
];

/**
 * Combinator Logic
 */

$combinators = [
	' ' => function ( $test ) {
		return function ( $el ) use ( &$test ) {
			/*jshint -W084 */
			while ( $el = $el->parentNode ) {
				if ( $test( $el ) ) { return $el;  }
			}
		};
	},
	'>' => function ( $test ) {
		return function ( $el ) use ( &$test ) {
			/*jshint -W084 */
			if ( $el = $el->parentNode ) {
				return $test( $el ) && $el;
			}
		};
	},
	'+' => function ( $test ) use ( &$prev ) {
		return function ( $el ) use ( &$prev, &$test ) {
			/*jshint -W084 */
			if ( $el = $prev( $el ) ) {
				return $test( $el ) && $el;
			}
		};
	},
	'~' => function ( $test ) use ( &$prev ) {
		return function ( $el ) use ( &$prev, &$test ) {
			/*jshint -W084 */
			while ( $el = $prev( $el ) ) {
				if ( $test( $el ) ) { return $el;  }
			}
		};
	},
	'noop' => function ( $test ) {
		return function ( $el ) use ( &$test ) {
			return $test( $el ) && $el;
		};
	},
	'ref' => function ( $test, $name ) {
		$node = null;

		function ref( $el ) {
			$doc = $el->ownerDocument;
			$nodes = $doc->getElementsByTagName( '*' );
			$i = count( $nodes );

			while ( $i-- ) {
				$node = $nodes[ $i ];
				if ( preg_match( $ref, $el ) ) {
					$node = null;
					return true;
				}
			}

			$node = null;
		}

		$ref->combinator = function ( $el ) use ( &$node, &$name, &$test ) {
			if ( !$node || !$node->getAttribute ) { return;  }

			$attr = $node->getAttribute( $name ) || '';
			if ( $attr[ 0 ] === '#' ) { $attr = $attr->substring( 1 );  }

			if ( $attr === $el->id && $test( $node ) ) {
				return $node;
			}
		};

		return $ref;
	}
];

/**
 * Grammar
 */

$rules = [
	'escape' => /* RegExp */ '/\\\(?:[^0-9A-Fa-f\r\n]|[0-9A-Fa-f]{1,6}[\r\n\t ]?)/g',
	'str_escape' => /* RegExp */ '/(escape)|\\\(\n|\r\n?|\f)/g',
	'nonascii' => /* RegExp */ '/[\u00A0-\uFFFF]/',
	'cssid' => /* RegExp */ '/(?:(?!-?[0-9])(?:escape|nonascii|[-_a-zA-Z0-9])+)/',
	'qname' => /* RegExp */ '/^ *(cssid|\*)/',
	'simple' => /* RegExp */ '/^(?:([.#]cssid)|pseudo|attr)/',
	'ref' => /* RegExp */ '/^ *\/(cssid)\/ */',
	'combinator' => /* RegExp */ '/^(?: +([^ \w*.#\\\]) +|( )+|([^ \w*.#\\\]))(?! *$)/',
	'attr' => /* RegExp */ '/^\[(cssid)(?:([^\w]?=)(inside))?\]/',
	'pseudo' => /* RegExp */ '/^(:cssid)(?:\((inside)\))?/',
	'inside' => /* RegExp */ "/(?:\"(?:\\\\\"|[^\"])*\"|'(?:\\\\'|[^'])*'|<[^\"'>]*>|\\\\[\"'>]|[^\"'>])*/",
	'ident' => /* RegExp */ '/^(cssid)$/'
];

$rules->cssid = $replace( $rules->cssid, 'nonascii', $rules->nonascii );
$rules->cssid = $replace( $rules->cssid, 'escape', $rules->escape );
$rules->qname = $replace( $rules->qname, 'cssid', $rules->cssid );
$rules->simple = $replace( $rules->simple, 'cssid', $rules->cssid );
$rules->ref = $replace( $rules->ref, 'cssid', $rules->cssid );
$rules->attr = $replace( $rules->attr, 'cssid', $rules->cssid );
$rules->pseudo = $replace( $rules->pseudo, 'cssid', $rules->cssid );
$rules->inside = $replace( $rules->inside, "[^\"'>]*", $rules->inside );
$rules->attr = $replace( $rules->attr, 'inside', $makeInside( '\[', '\]' ) );
$rules->pseudo = $replace( $rules->pseudo, 'inside', $makeInside( '\(', '\)' ) );
$rules->simple = $replace( $rules->simple, 'pseudo', $rules->pseudo );
$rules->simple = $replace( $rules->simple, 'attr', $rules->attr );
$rules->ident = $replace( $rules->ident, 'cssid', $rules->cssid );
$rules->str_escape = $replace( $rules->str_escape, 'escape', $rules->escape );

/**
 * Compiling
 */

$compile = function ( $sel_ ) use ( &$rules, &$combinators, &$decodeid ) {
	$sel = preg_replace( '/^\s+|\s+$/', '', $sel_ );
	$test = null;
	$filter = [];
	$buff = [];
	$subject = null;
	$qname = null;
	$cap = null;
	$op = null;
	$ref = null;

	/*jshint -W084 */
	while ( $sel ) {
		if ( $cap = $rules->qname->exec( $sel ) ) {
			$sel = $sel->substring( count( $sel ) );
			$qname = $cap[ 1 ];
			$buff[] = tok( $qname, true );
		} elseif ( $cap = $rules->simple->exec( $sel ) ) {
			$sel = $sel->substring( count( $sel ) );
			$qname = '*';
			$buff[] = tok( $qname, true );
			$buff[] = tok( $cap );
		} else {
			throw new SyntaxError( 'Invalid selector.' );
		}

		while ( $cap = $rules->simple->exec( $sel ) ) {
			$sel = $sel->substring( count( $sel ) );
			$buff[] = tok( $cap );
		}

		if ( $sel[ 0 ] === '!' ) {
			$sel = $sel->substring( 1 );
			$subject = makeSubject();
			$subject->qname = $qname;
			$buff[] = $subject->simple;
		}

		if ( $cap = $rules->ref->exec( $sel ) ) {
			$sel = $sel->substring( count( $sel ) );
			$ref = $combinators->ref( makeSimple( $buff ), $decodeid( $cap[ 1 ] ) );
			$filter[] = $ref->combinator;
			$buff = [];
			continue;
		}

		if ( $cap = $rules->combinator->exec( $sel ) ) {
			$sel = $sel->substring( count( $sel ) );
			$op = $cap[ 1 ] || $cap[ 2 ] || $cap[ 3 ];
			if ( $op === ',' ) {
				$filter[] = $combinators->noop( makeSimple( $buff ) );
				break;
			}
		} else {
			$op = 'noop';
		}

		if ( !$combinators[ $op ] ) { throw new SyntaxError( 'Bad combinator.' );  }
		$filter[] = $combinators[ $op ]( makeSimple( $buff ) );
		$buff = [];
	}

	$test = makeTest( $filter );
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
};

$tok = function ( $cap, $qname ) use ( &$selectors, &$decodeid, &$unquote ) {
	// qname
	if ( $qname ) {
		return ( $cap === '*' ) ?
		$selectors[ '*' ] :
		$selectors->type( $decodeid( $cap ) );
	}

	// class/id
	if ( $cap[ 1 ] ) {
		return ( $cap[ 1 ][ 0 ] === '.'
			// XXX unescape here?  or in attr?
		) ? $selectors->attr( 'class', '~=', $decodeid( $cap[ 1 ]->substring( 1 ) ), false ) :
		$selectors->attr( 'id', '=', $decodeid( $cap[ 1 ]->substring( 1 ) ), false );
	}

	// pseudo-name
	// inside-pseudo
	if ( $cap[ 2 ] ) {
		return ( $cap[ 3 ] ) ?
		$selectors[ $decodeid( $cap[ 2 ] ) ]( $unquote( $cap[ 3 ] ) ) :
		$selectors[ $decodeid( $cap[ 2 ] ) ];
	}

	// attr name
	// attr op
	// attr value
	if ( $cap[ 4 ] ) {
		$value = $cap[ 6 ];
		$i = preg_match( "/[\"'\\s]\\s*I\$/", $value );
		if ( $i ) {
			$value = preg_replace( '/\s*I$/i', '', $value, 1 );
		}
		return $selectors->attr( $decodeid( $cap[ 4 ] ), $cap[ 5 ] || '-', $unquote( $value ), $i );
	}

	throw new SyntaxError( 'Unknown Selector.' );
};

$makeSimple = function ( $func ) {
	$l = count( $func );
	$i = null;

	// Potentially make sure
	// `el` is truthy.
	if ( $l < 2 ) { return $func[ 0 ];  }

	return function ( $el ) use ( &$l, &$func ) {
		if ( !$el ) { return;  }
		for ( $i = 0;  $i < $l;  $i++ ) {
			if ( !$func[ $i ]( $el ) ) { return;  }
		}
		return true;
	};
};

$makeTest = function ( $func ) {
	if ( count( $func ) < 2 ) {
		return function ( $el ) use ( &$func ) {
			return !!$func[ 0 ]( $el );
		};
	}
	return function ( $el ) use ( &$func ) {
		$i = count( $func );
		while ( $i-- ) {
			if ( !( $el = $func[ $i ]( $el ) ) ) { return;  }
		}
		return true;
	};
};

$makeSubject = function () {
	$target = null;

	function subject( $el ) use ( &$target ) {
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
	}

	$subject->simple = function ( $el ) {
		$target = $el;
		return true;
	};

	return $subject;
};

$compileGroup = function ( $sel ) use ( &$compile ) {
	$test = $compile( $sel );
	$tests = [ $test ];

	while ( $test->sel ) {
		$test = $compile( $test->sel );
		$tests[] = $test;
	}

	if ( count( $tests ) < 2 ) { return $test;  }

	return function ( $el ) use ( &$tests ) {
		$l = count( $tests );
		$i = 0;

		for ( ;  $i < $l;  $i++ ) {
			if ( $tests[ $i ]( $el ) ) { return true;  }
		}
	};
};

/**
 * Selection
 */

$find = function ( $sel, $node ) use ( &$compile, &$order ) {
	$results = [];
	$test = $compile( $sel );
	$scope = $node->getElementsByTagName( $test->qname );
	$i = 0;
	$el = null;

	/*jshint -W084 */
	while ( $el = $scope[ $i++ ] ) {
		if ( $test( $el ) ) { $results[] = $el;  }
	}

	if ( $test->sel ) {
		while ( $test->sel ) {
			$test = $compile( $test->sel );
			$scope = $node->getElementsByTagName( $test->qname );
			$i = 0;
			/*jshint -W084 */
			while ( $el = $scope[ $i++ ] ) {
				if ( $test( $el ) && call_user_func( 'indexOf', $el ) === -1 ) {
					$results[] = $el;
				}
			}
		}
		$results->sort( $order );
	}

	return $results;
};

/**
 * Expose
 */

$module->exports = $exports = function ( $sel, $context ) use ( &$find ) {
	/* when context isn't a DocumentFragment and the selector is simple: */
	$id = null; $r = null;
	if ( $context->nodeType !== 11 && array_search( ' ', $sel ) === -1 ) {
		if ( $sel[ 0 ] === '#' && $context->rooted && preg_match( '/^#[A-Z_][-A-Z0-9_]*$/', $sel ) ) {
			if ( $context->doc->_hasMultipleElementsWithId ) {
				$id = $sel->substring( 1 );
				if ( !$context->doc->_hasMultipleElementsWithId( $id ) ) {
					$r = $context->doc->getElementById( $id );
					return ( $r ) ? [ $r ] : [];
				}
			}
		}
		if ( $sel[ 0 ] === '.' && preg_match( '/^\.\w+$/', $sel ) ) {
			return $context->getElementsByClassName( $sel->substring( 1 ) );
		}
		if ( preg_match( '/^\w+$/', $sel ) ) {
			return $context->getElementsByTagName( $sel );
		}
	}
	/* do things the hard/slow way */
	return $find( $sel, $context );
};

$exports->selectors = $selectors;
$exports->operators = $operators;
$exports->combinators = $combinators;

$exports->matches = function ( $el, $sel ) use ( &$compile ) {
	$test = [ 'sel' => $sel ];
	do {
		$test = $compile( $test->sel );
		if ( $test( $el ) ) { return true;  }
	} while ( $test->sel );
	return false;
};

}