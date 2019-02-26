<?php

use Wikimedia\Zest\Zest;

use RemexHtml\DOM;
use RemexHtml\Tokenizer;
use RemexHtml\TreeBuilder;

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
	 * @dataProvider findProvider
	 */
	public function testFind( string $selector, array $expectedList ) {
		$doc = self::loadHTML( __DIR__ . "/index.html" );
		$matches = Zest::find( $selector, $doc );
		$this->assertSame( count( $matches ), count( $expectedList ) );

		foreach ( $matches as $m ) {
			$path = self::toXPath( $m );
			$this->assertContains( $path, $expectedList );
		}
	}
	public function findProvider() {
		return [
			[ "body > header > h1", [ "/html[1]/body[1]/header[1]/h1[1]" ] ],
			[ "h1", [ "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]" ] ],
			[ "*", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[2]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[4]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/p[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]", "/html[1]/body[1]/footer[1]/a[2]" ] ],
			[ "article > header", [ "/html[1]/body[1]/article[1]/header[1]" ] ],
			[ "header + p", [ "/html[1]/body[1]/article[1]/p[1]" ] ],
			[ "header ~ footer", [ "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/footer[1]" ] ],
			[ ":root", [ "/html[1]" ] ],
			[ ":first-child", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/title[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			[ ":last-child", [ "/html[1]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]", "/html[1]/body[1]/footer[1]/small[1]/a[1]", "/html[1]/body[1]/footer[1]/a[2]" ] ],
			[ "header > :first-child", [ "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]" ] ],
			[ ":empty", [ "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[4]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]" ] ],
			[ "a[rel=\"section\"]", [ "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]" ] ],
			[ "html header", [ "/html[1]/body[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]" ] ],
			[ ".a", [ "/html[1]/body[1]/article[1]", "/html[1]/body[1]/footer[1]/form[1]" ] ],
			[ "#hi", [ "/html[1]/body[1]/header[1]/h1[1]" ] ],
			[ "html > :root", [] ],
			[ "header h1", [ "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]" ] ],
			[ "article p", [ "/html[1]/body[1]/article[1]/p[1]" ] ],
			[ ":not(a)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[2]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[4]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/article[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/p[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/form[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]", "/html[1]/body[1]/footer[1]/small[1]" ] ],
			[ ".bar", [ "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]" ] ],
			[ "[id=\"hi\"]", [ "/html[1]/body[1]/header[1]/h1[1]" ] ],
			[ "h1 + time[datetime]", [ "/html[1]/body[1]/article[1]/header[1]/time[1]" ] ],
			[ "h1 + time[datetime]:last-child", [ "/html[1]/body[1]/article[1]/header[1]/time[1]" ] ],
			[ ":nth-child(2n+1)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[2]", "/html[1]/head[1]/script[4]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			[ ":nth-child(2n-1)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[2]", "/html[1]/head[1]/script[4]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			[ ":nth-of-type(2n+1)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/p[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			// Child selectors on the document element `html`
			[ "html:first-child", [ '/html[1]' ] ],
			[ "html:only-child", [ '/html[1]' ] ],
			[ "html:last-child", [ '/html[1]' ] ],
			[ "html:first-of-type", [ '/html[1]' ] ],
			[ "html:last-of-type", [ '/html[1]' ] ],
			[ "html:nth-child(1)", [ '/html[1]' ] ],
			[ "html:nth-child(2)", [] ],
			[ "html:nth-last-child(1)", [ '/html[1]' ] ],
			[ "html:nth-last-child(2)", [] ],
			[ "html:nth-of-type(1)", [ '/html[1]' ] ],
			[ "html:nth-of-type(2)", [] ],
			[ "html:nth-last-of-type(1)", [ '/html[1]' ] ],
			[ "html:nth-last-of-type(2)", [] ],
			// The :contains selector
			[ 'header:contains("A Date")', [ '/html[1]/body[1]/article[1]/header[1]' ] ],
			// The :has selector
			[ 'li:has(a[rel=section].foo)', [ '/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]', '/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]' ] ],
			// CSS escapes
			[ "#cite_note-13\\.3F_It_Can\\'t_Be\\!-3", [ '/html[1]/body[1]/footer[1]/a[2]' ] ],
			[ '#\\a9', [ '/html[1]/body[1]/footer[1]/small[1]/a[1]' ] ],
		];
	}

	public static function toXPath( DOMNode $node ) {
		// which child of parent is this?
		$parent = $node->parentNode;
		if ( !$parent ) {
			return '';
		}
		$name = $node->nodeName;
		if ( $name === 'html' ) {
			return '/html[1]';
		}
		$count = 0;
		foreach ( $parent->childNodes as $n ) {
			if ( $n->nodeName === $name ) {
				$count++;
			}
			if ( $n === $node ) {
				break;
			}
		}
		return self::toXPath( $parent ) . "/" . $name . "[$count]";
	}
	public static function loadHtml( string $filename ) : DOMDocument {
		$text = file_get_contents( $filename );
		return self::parseHtml( $text );
	}

	public static function parseHtml( string $text ) : DOMDocument {
		$domBuilder = new DOM\DOMBuilder;
		$treeBuilder = new TreeBuilder\TreeBuilder( $domBuilder, [
			/* tree builder options */
		] );
		$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, [
			/* tokenizer options */
		] );
		$tokenizer->execute( [
			/* execute options */
		] );
		return $domBuilder->getFragment();
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
