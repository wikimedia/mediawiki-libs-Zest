<?php

namespace Wikimedia\Zest\Tests;

use Wikimedia\TestingAccessWrapper;
use Wikimedia\Zest\ZestInst;

class ZestInstTest extends \PHPUnit\Framework\TestCase {
	/**
	 * Just check if the Zest class has no syntax error.
	 *
	 * This is just a simple check to make sure your library has no
	 * syntax error. This helps you troubleshoot any typo before you
	 * even use this library in a real project.
	 *
	 */
	public function testIsThereAnySyntaxError() {
		$var = new ZestInst;
		$this->assertTrue( is_object( $var ) );
	}

	/**
	 * @dataProvider unquoteProvider
	 */
	public function testUnquote( $given, $expected ) {
		$var = TestingAccessWrapper::newFromClass( ZestInst::class )->unquote( $given );
		$this->assertSame( $expected, $var );
	}

	public function unquoteProvider() {
		return [
			[ 'foo', 'foo' ],
			[ '"foo"', 'foo' ],
			[ "'foo'", 'foo' ],
			[ "'\x41\x42'", 'AB' ],
		];
	}

	/**
	 * @dataProvider parseNthProvider
	 */
	public function testParseNth( $given, $group, $offset ) {
		$res = TestingAccessWrapper::newFromClass( ZestInst::class )->parseNth( $given );
		$this->assertSame( $group, $res->group );
		$this->assertSame( $offset, $res->offset );
	}

	public function parseNthProvider() {
		return [
			[ 'even', 2, 0 ],
			[ 'odd', 2, 1 ],
			[ '+3n+45', 3, 45 ],
			[ '-3n-45', -3, -45 ],
			[ '-2n+1', -2, 1 ],
			[ '1', 0, 1 ],
			[ '-1', 0, -1 ],
			[ '0n2', 0, 2 ],
			[ '-0n-2', 0, -2 ],
		];
	}

	public function testCustom() {
		$doc = self::loadRemexHtml( __DIR__ . "/index.html" );
		$thrown = 0;
		$z0 = new ZestInst;
		// Verify that we can create a custom selector
		$z1 = new ZestInst;
		$z1->addSelector0( ':zesttest', static function ( $el ): bool {
			return strtolower( $el->nodeName ) === 'footer' &&
				strtolower( $el->parentNode->nodeName ) === 'article';
		} );
		$matches = $z1->find( ':zesttest', $doc );
		$this->assertCount( 1, $matches );
		$this->assertSame( '/html[1]/body[1]/article[1]/footer[1]', self::toXPath( $matches[0] ) );

		// Verify that this new selector doesn't infect previously- or
		// subsequently-created selector engines.
		try {
			$z0->find( ':zesttest', $doc );
		} catch ( \Exception $e ) {
			$thrown++;
		}
		$z2 = new ZestInst;
		try {
			$z2->find( ':zesttest', $doc );
		} catch ( \Exception $e ) {
			$thrown++;
		}
		$this->assertSame( 2, $thrown );
	}

	public static function toXPath( $node ) {
		return ZestTest::toXPath( $node );
	}

	public static function loadRemexHtml( string $filename ) {
		return ZestTest::loadRemexHtml( $filename );
	}
}
