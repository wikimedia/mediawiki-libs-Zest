<?php

use Wikimedia\Zest\Zest;

class ZestTest extends \PHPUnit\Framework\TestCase {
	/**
	 * Just check if the Zest class has no syntax error.
	 *
	 * This is just a simple check to make sure your library has no
	 * syntax error. This helps you troubleshoot any typo before you
	 * even use this library in a real project.
	 *
	 */
	public function testIsThereAnySyntaxError() {
		$var = new Zest;
		$this->assertTrue( is_object( $var ) );
		unset( $var );
		$init = self::getPrivateMethod( Zest::class, 'init' );
		$init->invoke( null );
	}

	/**
	 * @dataProvider unquoteProvider
	 */
	public function testUnquote( $given, $expected ) {
		$unquote = self::getPrivateMethod( Zest::class, 'unquote' );
		$var = $unquote->invoke( null, $given );
		$this->assertSame( $var, $expected );
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
		$unquote = self::getPrivateMethod( Zest::class, 'parseNth' );
		$res = $unquote->invoke( null, $given );
		$this->assertSame( $res->group, $group );
		$this->assertSame( $res->offset, $offset );
	}
	public function parseNthProvider() {
		return [
			[ 'even', 2, 0 ],
			[ 'odd', 2, 1 ],
			[ '+3n+45', 3, 45 ],
			[ '-3n-45', -3, -45 ],
			[ '-2n+1', -2, 1 ],
		];
	}

	/**
	 * Get a private or protected method for testing/documentation purposes.
	 * How to use for MyClass->foo():
	 *      $cls = new MyClass();
	 *      $foo = PHPUnitUtil::getPrivateMethod($cls, 'foo');
	 *      $foo->invoke($cls, $...);
	 * @param object $obj The instantiated instance of your class
	 * @param string $name The name of your private/protected method
	 * @return ReflectionMethod The method you asked for
	 */
	public static function getPrivateMethod( $obj, $name ) {
		$class = new ReflectionClass( $obj );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}
}
