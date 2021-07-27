<?php

namespace Wikimedia\Zest\Tests;

use DOMDocument;
use Wikimedia\TestingAccessWrapper;
use Wikimedia\Zest\Zest;
use Wikimedia\Zest\ZestInst;

class GetElementsByTest extends \PHPUnit\Framework\TestCase {

	/** @dataProvider remexFragProvider */
	public function testGetElementsByTagName( bool $useRemex, bool $useFrag ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		// Move the entire thing into a document fragment.
		$context = $doc;
		if ( $useFrag ) {
			$frag = $doc->createDocumentFragment();
			$frag->appendChild( $doc->documentElement );
			$context = $frag;
		}
		// Now test that Zest::getElementsByTagName() works in all modes.
		$e = Zest::getElementsByTagName( $context, '*' );
		$this->assertCount( 40, $e );
		$e = Zest::getElementsByTagName( $context, 'head' );
		$this->assertCount( 1, $e );
		$e = Zest::getElementsByTagName( $context, 'body' );
		$this->assertCount( 1, $e );
		$e = Zest::getElementsByTagName( $context, 'html' );
		$this->assertCount( 1, $e );
		$html = $e[0];
		$e = Zest::getElementsByTagName( $context, 'li' );
		$this->assertCount( 5, $e );
		// Exclusive (ie, match shouldn't include context element)
		$e = Zest::getElementsByTagName( $html, 'html' );
		$this->assertCount( 0, $e );
	}

	/** @dataProvider remexFragProvider */
	public function testGetElementsByClassName( bool $useRemex, bool $useFrag ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		$doc->documentElement->setAttribute(
			'class', "testGetElementByClassName\t"
		);
		$this->assertTrue( Zest::matches( // sanity check
			$doc->documentElement, '.testGetElementByClassName'
		) );
		// Move the entire thing into a document fragment.
		$context = $doc;
		if ( $useFrag ) {
			$frag = $doc->createDocumentFragment();
			$frag->appendChild( $doc->documentElement );
			$context = $frag;
		}
		// Wrapper to access private function
		$func = static function ( $context, string $sel, array $opts = [] ): array {
			return TestingAccessWrapper::newFromObject( new ZestInst )
				->getElementsByClassName( $context, $sel, $opts );
		};
		// Now test that Zest::getElementsByClassName() works in all modes.
		$e = $func( $context, 'testGetElementByClassName' );
		$this->assertCount( 1, $e );
		$this->assertEqualsIgnoringCase( 'html', $e[0]->tagName );
		$html = $e[0];
		$e = $func( $context, 'foo' );
		$this->assertCount( 2, $e );
		$e = $func( $context, 'a' );
		$this->assertCount( 2, $e );
		// Exclusive (ie, match shouldn't include context element)
		$e = $func( $html, 'testGetElementByClassName' );
		$this->assertCount( 0, $e );
	}

	/** @dataProvider remexFragProvider */
	public function testGetElementsById( bool $useRemex, bool $useFrag ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		$doc->documentElement->setAttribute(
			'id', "something with spaces"
		);
		// Move the entire thing into a document fragment.
		$context = $doc;
		if ( $useFrag ) {
			$frag = $doc->createDocumentFragment();
			$frag->appendChild( $doc->documentElement );
			$context = $frag;
		}
		// Now test that Zest::getElementsById() works in all modes.
		$e = Zest::getElementsById( $context, 'something with spaces' );
		$this->assertCount( 1, $e );
		$this->assertEqualsIgnoringCase( 'html', $e[0]->tagName );
		$html = $e[0];
		$e = Zest::getElementsById( $context, 'hi' );
		$this->assertCount( 1, $e );
		// Exclusive (ie, match shouldn't include context element)
		$e = Zest::getElementsById( $html, 'something with spaces' );
		$this->assertCount( 0, $e );
	}

	public function remexFragProvider() {
		return [
			'loadHTMLFile, Document' => [ false, false ],
			'loadHTMLFile, DocumentFragment' => [ false, true ],
			'Remex, Document' => [ true, false ],
			'Remex, DocumentFragment' => [ true, true ],
		];
	}

	public static function toXPath( $node ) {
		return ZestTest::toXPath( $node );
	}

	public static function loadHtml( string $filename ) {
		return ZestTest::loadHtml( $filename );
	}
}
