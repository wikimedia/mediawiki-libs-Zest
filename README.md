zest.php
=========================

__zest.php__ is a fast, lightweight, extensible CSS selector engine for PHP.

Zest was designed to be very concise while still supporting CSS3/CSS4
selectors and remaining fast.

This is a port to PHP of the [zest.js](https://github.com/chjj/zest)
selector library.  Since that project hasn't been updated in a while,
bugfixes have been taken from the copy of zest included in the
[domino](https://github.com/fgnass/domino/pulls) DOM library.

Usage
-----

```php
use Wikimedia\Zest\Zest;

$els = Zest::find('section! > div[title="hello" i] > :local-link /href/ h1', $doc);
```

Install
-------

```bash
$ composer install wikimedia/zest
```

API
---

Tests
-----

License and Credits
-----------------
The original zest codebase is
(c) Copyright 2011-2012, Christopher Jeffrey.

The port to PHP is
(c) Copyright 2019, C. Scott Ananian.

Both the original zest codebase and this port are distributed under
the MIT license; see LICENSE for more info.
