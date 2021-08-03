<?php

namespace Wikimedia\Zest\Tests;

use DOMDocument;
use RemexHtml\DOM;
use RemexHtml\Tokenizer;
use RemexHtml\TreeBuilder;
use Wikimedia\Zest\Zest;

class ZestTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider findProvider
	 */
	public function testFindRemex( string $selector, array $expectedList ) {
		if ( array_key_exists( 'Document', $expectedList ) ) {
			$expectedList = $expectedList['Document'];
		}
		if ( array_key_exists( 'remex', $expectedList ) ) {
			$expectedList = $expectedList['remex'];
		}
		$doc = self::loadHTML( __DIR__ . "/index.html" );
		$matches = Zest::find( $selector, $doc );

		$matchesList = array_map( [ self::class, 'toXPath' ], $matches );
		foreach ( $matchesList as $m ) {
			$this->assertContains( $m, $expectedList );
		}
		foreach ( $expectedList as $e ) {
			$this->assertContains( $e, $matchesList );
		}
		if ( count( $expectedList ) === 0 ) {
			// Just ensure there's at least one assertion to keep the test
			// runner happy, even if the selector isn't expected to match
			// anything.
			$this->assertSame( $expectedList, $matchesList );
		}
	}

	/**
	 * @dataProvider findProvider
	 */
	public function testFindRemexFragment( string $selector, array $expectedList ) {
		if ( array_key_exists( 'DocumentFragment', $expectedList ) ) {
			$expectedList = $expectedList['DocumentFragment'];
		}
		if ( array_key_exists( 'remex', $expectedList ) ) {
			$expectedList = $expectedList['remex'];
		}
		$doc = self::loadHTML( __DIR__ . "/index.html" );
		$frag = $doc->createDocumentFragment();
		$frag->appendChild( $doc->documentElement );
		$matches = Zest::find( $selector, $frag );

		$matchesList = array_map( [ self::class, 'toXPath' ], $matches );
		foreach ( $matchesList as $m ) {
			$this->assertContains( $m, $expectedList );
		}
		foreach ( $expectedList as $e ) {
			$this->assertContains( $e, $matchesList );
		}
		if ( count( $expectedList ) === 0 ) {
			// Just ensure there's at least one assertion to keep the test
			// runner happy, even if the selector isn't expected to match
			// anything.
			$this->assertSame( $expectedList, $matchesList );
		}
	}

	/**
	 * @dataProvider findProvider
	 */
	public function testFindDOM( string $selector, array $expectedList ) {
		if ( array_key_exists( 'Document', $expectedList ) ) {
			$expectedList = $expectedList['Document'];
		}
		if ( array_key_exists( 'dom', $expectedList ) ) {
			$expectedList = $expectedList['dom'];
		}
		$doc = new DOMDocument;
		$html = file_get_contents( __DIR__ . "/index.html" );
		$html = mb_convert_encoding( $html, "HTML-ENTITIES", "utf-8" );
		$doc->loadHTML( $html, LIBXML_NOERROR );

		$matches = Zest::find( $selector, $doc );
		$matchesList = array_map( [ self::class, 'toXPath' ], $matches );
		foreach ( $matchesList as $m ) {
			$this->assertContains( $m, $expectedList );
		}
		foreach ( $expectedList as $e ) {
			$this->assertContains( $e, $matchesList );
		}
		if ( count( $expectedList ) === 0 ) {
			// Just ensure there's at least one assertion to keep the test
			// runner happy, even if the selector isn't expected to match
			// anything.
			$this->assertSame( $expectedList, $matchesList );
		}
	}

	/**
	 * @dataProvider findProvider
	 */
	public function testFindDOMFragment( string $selector, array $expectedList ) {
		if ( array_key_exists( 'DocumentFragment', $expectedList ) ) {
			$expectedList = $expectedList['DocumentFragment'];
		}
		if ( array_key_exists( 'dom', $expectedList ) ) {
			$expectedList = $expectedList['dom'];
		}
		$doc = new DOMDocument;
		$html = file_get_contents( __DIR__ . "/index.html" );
		$html = mb_convert_encoding( $html, "HTML-ENTITIES", "utf-8" );
		$doc->loadHTML( $html, LIBXML_NOERROR );
		$frag = $doc->createDocumentFragment();
		$frag->appendChild( $doc->documentElement );
		$matches = Zest::find( $selector, $frag );

		$matchesList = array_map( [ self::class, 'toXPath' ], $matches );
		foreach ( $matchesList as $m ) {
			$this->assertContains( $m, $expectedList );
		}
		foreach ( $expectedList as $e ) {
			$this->assertContains( $e, $matchesList );
		}
		if ( count( $expectedList ) === 0 ) {
			// Just ensure there's at least one assertion to keep the test
			// runner happy, even if the selector isn't expected to match
			// anything.
			$this->assertSame( $expectedList, $matchesList );
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
			[ ":root", [ 'Document' => [ "/html[1]" ], 'DocumentFragment' => [] ] ],
			[ ":scope", [ 'Document' => [ "/html[1]" ], 'DocumentFragment' => [] ] ],
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
			[ "html", [ '/html[1]' ] ], // sanity check before we start
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
			// The :nth-child selector
			[ "body > *:first-child", [ '/html[1]/body[1]/header[1]' ] ],
			[ "body > *:nth-child(1)", [ '/html[1]/body[1]/header[1]' ] ],
			// The :contains selector
			[ 'header:contains("A Date")', [ '/html[1]/body[1]/article[1]/header[1]' ] ],
			// The :has selector
			[ 'li:has(a[rel=section].foo)', [ '/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]', '/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]' ] ],
			// CSS escapes
			[ "#cite_note-13\\.3F_It_Can\\'t_Be\\!-3", [ '/html[1]/body[1]/footer[1]/a[2]' ] ],
			[ '#\\a9', [ '/html[1]/body[1]/footer[1]/small[1]/a[1]' ] ],
			// The comma combinator
			[ '#\\00a9, article p', [ '/html[1]/body[1]/article[1]/p[1]', '/html[1]/body[1]/footer[1]/small[1]/a[1]' ] ],
			[ 'article > header, header + p', [ "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/p[1]" ] ],
			// Case insensitive attribute value matching
			[ 'input[value="submit"i]', [ '/html[1]/body[1]/footer[1]/form[1]/input[2]' ] ],
			// Namespace selector
			[ 'head', [ '/html[1]/head[1]' ] ],
			[ '*|head', [ '/html[1]/head[1]' ] ],
			[ '|head', [ 'remex' => [], 'dom' => [ '/html[1]/head[1]' ] ] ],
			// Ensure selectors can't match Document or DocumentFragment at root
			[ ':checked html, :enabled html, :disabled html', [] ],
			[ ':lang(en) html, :dir(rtl) html', [] ],
			[ ':required html, :read-only html', [] ],
			[ ':has(*) > html', [] ],
			[ '[href] > html', [] ],
			[ ':not(.xyz) > html', [] ],
			[ ':is(head, body) > html', [] ],
			[ '* html', [] ],
		];
	}

	/**
	 * @dataProvider findIdProvider
	 */
	public function testFindId( bool $useRemex ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		$matches = Zest::find( '#hi', $doc );
		$this->assertCount( 1, $matches );
		$el0 = $matches[0];
		$ns = $doc->documentElement->namespaceURI;
		$el1 = $doc->createElementNS( $ns, 'p' );
		$el1->setAttribute( 'id', 'hi' );
		$el2 = $doc->createElementNS( $ns, 'a' );
		$el2->setAttribute( 'id', 'hi' );
		Zest::find( 'body', $doc )[0]->appendChild( $el2 );
		$el0->parentNode->removeChild( $el0 );
		$matches = Zest::find( '#hi', $doc );
		$this->assertCount( 1, $matches );
		$this->assertContains( $el2, $matches );
	}

	public function findIdProvider() {
		return [ [ false ], [ true ] ];
	}

	/**
	 * @dataProvider findTagProvider
	 */
	public function testFindTag( bool $useRemex ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		// Elements with non-word characters in the name
		$ns = $doc->documentElement->namespaceURI;
		$el = $doc->createElementNS( $ns, "p\u{00C0}p" );
		Zest::find( 'body', $doc )[0]->appendChild( $el );
		$matches = Zest::find( "p\u{00C0}p", $doc );
		$this->assertCount( 1, $matches );
		$this->assertContains( $el, $matches );
		// Using CSS escape mechanism to smuggle quotation marks into tagname
		$matches = Zest::find( 'p\\22\\27p', $doc );
		$this->assertCount( 0, $matches );
	}

	public function findTagProvider() {
		return [ [ false ], [ true ] ];
	}

	/**
	 * @dataProvider scopingProvider
	 */
	public function testScoping( bool $useRemex ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		// From https://drafts.csswg.org/selectors-4/#scoping-root :
		// "When a selector is scoped, it matches an element only if
		// the element is a descendant of the scoping root. (The rest
		// of the selector can match unrestricted; it’s only the final
		// matched elements that must be within the scope.)"
		$scope = Zest::find( 'article.a > header', $doc )[0] ?? null;
		$this->assertNotNull( $scope );
		$testEl = Zest::find( 'body > article > header > h1 > a[href]', $scope );
		$this->assertCount( 1, $testEl );
		$testEl2 = Zest::find( 'body > article > header > h1 > a[href]', $doc );
		$this->assertSame( $testEl2, $testEl );

		$h1 = Zest::find( '*[id=hi]', $doc );
		$this->assertCount( 1, $h1 );
		$h1 = $h1[0];

		// Basic usage:
		// (Ideas from https://developer.mozilla.org/en-US/docs/Web/CSS/:scope )
		$scope2 = Zest::find( ':scope', $scope );
		$this->assertSame( [], $scope2 ); // find() is *exclusive*
		$testEl3 = Zest::find( ':scope > h1 > a[href]', $scope );
		$this->assertSame( $testEl3, $testEl );
		// test the 'shortcuts'
		$els = Zest::find( '#hi', $h1 );
		$this->assertSame( $els, [] ); // *exclusive*
		$els = Zest::find( 'h1', $h1 );
		$this->assertSame( $els, [] ); // *exclusive*
		$h1->setAttribute( 'class', 'shortcut' );
		$els = Zest::find( '.shortcut', $h1 );
		$this->assertSame( $els, [] ); // *exclusive*

		// Test `matches` as well; these are *inclusive*
		$this->assertTrue( Zest::matches( $scope, '*' ) );
		$this->assertTrue( Zest::matches( $scope, 'header' ) );
		$this->assertTrue( Zest::matches( $scope, ':scope' ) );
		$this->assertFalse( Zest::matches( $scope, ':root' ) );
		$this->assertTrue( Zest::matches( $h1, '#hi' ) );
		$this->assertTrue( Zest::matches( $h1, 'h1' ) );
		$this->assertTrue( Zest::matches( $h1, '.shortcut' ) );
	}

	public function scopingProvider() {
		return [ [ false ], [ true ] ];
	}

	/**
	 * @dataProvider multiIdProvider
	 */
	public function testMultiId( bool $useRemex, bool $useCallable ) {
		if ( $useRemex ) {
			$doc = self::loadHTML( __DIR__ . "/index.html" );
		} else {
			$doc = new DOMDocument;
			$doc->loadHTMLFile( __DIR__ . "/index.html", LIBXML_NOERROR );
		}
		$els = Zest::find( 'nav li', $doc );
		$this->assertCount( 5, $els );
		foreach ( $els as $el ) {
			$el->setAttribute( 'id', 'samesame' );
		}
		$opts = $useCallable ? [
			'getElementsById' => static function ( $context, $id ) use ( $els ) {
				return $els;
			},
		] : [ 'getElementsById' => true ];
		// Test the "fast path"
		$result1 = Zest::find( '#samesame', $doc, $opts );
		$this->assertCount( 5, $result1 );
		$this->assertSame( $result1, $els );
		// Test the "slow path"
		$result2 = Zest::find( "nav > ul > #samesame", $doc, $opts );
		$this->assertCount( 5, $result2 );
		$this->assertSame( $result2, $els );
	}

	public function multiIdProvider() {
		for ( $i = 0; $i < 4; $i++ ) {
			$remex = ( $i & 1 ) !== 0;
			$callable = ( $i & 2 ) !== 0;
			yield [ $remex, $callable ];
		}
	}

	public static function toXPath( $node ) {
		// which child of parent is this?
		$parent = $node->parentNode;
		if ( !$parent ) {
			return '';
		}
		$name = strtolower( $node->nodeName );
		if ( $name === 'html' ) {
			return '/html[1]';
		}
		$count = 0;
		foreach ( $parent->childNodes as $n ) {
			if ( strtolower( $n->nodeName ) === $name ) {
				$count++;
			}
			if ( $n === $node ) {
				break;
			}
		}
		return self::toXPath( $parent ) . "/" . $name . "[$count]";
	}

	public static function loadHtml( string $filename, $options = [] ) {
		$text = file_get_contents( $filename );
		return self::parseHtml( $text, $options );
	}

	public static function parseHtml( string $text, $options = [] ) {
		$domBuilder = new DOM\DOMBuilder( $options + [
			/* DOM builder options  */
		] );
		$treeBuilder = new TreeBuilder\TreeBuilder( $domBuilder, [
			/* tree builder options */
		] );
		$dispatcher = new TreeBuilder\Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer\Tokenizer( $dispatcher, $text, $options + [
			/* tokenizer options */
		] );
		$tokenizer->execute( [
			/* execute options */
		] );
		return $domBuilder->getFragment();
	}
}
