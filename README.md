# zest.php

__zest.php__ is a fast, lightweight, extensible CSS selector engine for PHP.

Zest was designed to be very concise while still supporting CSS3/CSS4
selectors and remaining fast.

This is a port to PHP of the [zest.js](https://github.com/chjj/zest)
selector library.  Since that project hasn't been updated in a while,
bugfixes have been taken from the copy of zest included in the
[domino](https://github.com/fgnass/domino/pulls) DOM library.

## Usage

```php
use Wikimedia\Zest\Zest;

$els = Zest::find('section! > div[title="hello" i] > :local-link /href/ h1', $doc);
```

## Install

```bash
$ composer install wikimedia/zest
```

## API

`Zest::find( $selector, $context )` -> this is equivalent to the standard
DOM method [`ParentNode#querySelectorAll()`](https://developer.mozilla.org/en-US/docs/Web/API/ParentNode/querySelectorAll).

`Zest::matches( $element, $selector )` -> this is equivalent to the standard
DOM method [`Element#matches()`](https://developer.mozilla.org/en-US/docs/Web/API/Element/matches).

## Extension

It is possible to add your own selectors, operators, or combinators.
These are added to an instance of Zest, so they don't affect other instances
of Zest or the static `Zest::find`/`Zest::matches` methods.

### Adding a simple selector

Adding simple selectors is fairly straight forward. Only the addition of pseudo
classes and attribute operators is possible. (Adding your own "style" of
selector would require changes to the core logic.)

Here is an example of a custom `:name` selector which will match for an
element's `name` attribute: e.g. `h1:name(foo)`. Effectively an alias
for `h1[name=foo]`.

``` php
use Wikimedia\Zest\ZestInst;

$z = new ZestInst;
$z->addSelector1( ':name', function( string $param ):callable {
  return function ( DOMNode $el ) use ( $param ):bool {
    if ($el->getAttribute('name') === $param) return true;
    return false;
  };
} );
```

__NOTE__: if your pseudo-class does not take a parameter, use `addSelector0`.

### Adding an attribute operator

``` php
$z = new ZestInst;
// `$attr` is the attribute
// `$val` is the value to match
$z->addOperator( '!=', function( string $attr, string $val ):bool {
  return $attr !== $val;
} );
```

### Adding a combinator

Adding a combinator is a bit trickier. It may seem confusing at first because
the logic is upside-down. Zest interprets selectors from right to left.

Here is an example how a parent combinator could be implemented:

``` js
$z = new ZestInst;
$z->addCombinator( '<', function( callable $test ): callable {
  return function( DOMNode $el ) use ( $test ): ?DOMNode {
    // `$el` is the current element
    $el = $el->firstChild;
    while ($el) {
      // return the relevant element
      // if it passed the test
      if ($el->nodeType === 1 && call_user_func($test, $el)) {
        return $el;
      }
      $el = $el->nextSibling;
    }
    return null;
  };
} );
```

The `$test` function tests whatever simple selectors it needs to look for, but
it isn't important what it does. The most important part is that you return
the relevant element once it's found.


## Tests

```bash
$ composer test
```

## License and Credits

The original zest codebase is
(c) Copyright 2011-2012, Christopher Jeffrey.

The port to PHP is
(c) Copyright 2019, C. Scott Ananian.

Both the original zest codebase and this port are distributed under
the MIT license; see LICENSE for more info.
