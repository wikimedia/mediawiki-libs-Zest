<?php

namespace Wikimedia\Zest\Tests;

use DOMDocument;
use Wikimedia\RemexHtml\DOM;
use Wikimedia\RemexHtml\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder;
use Wikimedia\Zest\Zest;

/**
 * @covers \Wikimedia\Zest\Zest
 */
class ZestTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider findProvider
	 */
	public function testFindDocument( callable $docFunc, string $selector, array $expectedList ) {
		if ( array_key_exists( 'Document', $expectedList ) ) {
			$expectedList = $expectedList['Document'];
		}
		$doc = $docFunc();

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
	public function testFindDocumentFragment( callable $docFunc, string $selector, array $expectedList ) {
		if ( array_key_exists( 'DocumentFragment', $expectedList ) ) {
			$expectedList = $expectedList['DocumentFragment'];
		}
		$doc = $docFunc();
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

	public static function findProvider() {
		$cases = [
			[ "body > header > h1", [ "/html[1]/body[1]/header[1]/h1[1]" ] ],
			[ "h1", [ "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]" ] ],
			[ "*", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/meta[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[2]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[4]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/p[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]", "/html[1]/body[1]/footer[1]/a[2]", "/html[1]/body[1]/mw:section[1]" ] ],
			[ "article > header", [ "/html[1]/body[1]/article[1]/header[1]" ] ],
			[ "header + p", [ "/html[1]/body[1]/article[1]/p[1]" ] ],
			[ "header ~ footer", [ "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/footer[1]" ] ],
			[ ":root", [ 'Document' => [ "/html[1]" ], 'DocumentFragment' => [] ] ],
			[ ":scope", [ 'Document' => [ "/html[1]" ], 'DocumentFragment' => [] ] ],
			[ ":first-child", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/meta[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			[ ":last-child", [ "/html[1]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]", "/html[1]/body[1]/footer[1]/small[1]/a[1]", "/html[1]/body[1]/footer[1]/a[2]", "/html[1]/body[1]/mw:section[1]" ] ],
			[ "header > :first-child", [ "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]" ] ],
			[ ":empty", [ "/html[1]/head[1]/meta[1]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[4]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]" ] ],
			[ "a[rel=\"section\"]", [ "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]" ] ],
			[ "html header", [ "/html[1]/body[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]" ] ],
			[ ".a", [ "/html[1]/body[1]/article[1]", "/html[1]/body[1]/footer[1]/form[1]" ] ],
			[ "#hi", [ "/html[1]/body[1]/header[1]/h1[1]" ] ],
			[ "html > :root", [] ],
			[ "header h1", [ "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]" ] ],
			[ "article p", [ "/html[1]/body[1]/article[1]/p[1]" ] ],
			[ ":not(a)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/meta[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[2]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[4]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/article[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/p[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/form[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/form[1]/input[2]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/mw:section[1]" ] ],
			[ ".bar", [ "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]" ] ],
			[ "[id=\"hi\"]", [ "/html[1]/body[1]/header[1]/h1[1]" ] ],
			[ "h1 + time[datetime]", [ "/html[1]/body[1]/article[1]/header[1]/time[1]" ] ],
			[ "h1 + time[datetime]:last-child", [ "/html[1]/body[1]/article[1]/header[1]/time[1]" ] ],
			[ ":nth-child(2n+1)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/meta[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			[ ":nth-child(2n-1)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/meta[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]" ] ],
			[ ":nth-of-type(2n+1)", [ "/html[1]", "/html[1]/head[1]", "/html[1]/head[1]/meta[1]", "/html[1]/head[1]/title[1]", "/html[1]/head[1]/script[1]", "/html[1]/head[1]/script[3]", "/html[1]/head[1]/script[5]", "/html[1]/body[1]", "/html[1]/body[1]/header[1]", "/html[1]/body[1]/header[1]/h1[1]", "/html[1]/body[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[1]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[2]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[3]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[4]/a[1]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]", "/html[1]/body[1]/header[1]/nav[1]/ul[1]/li[5]/a[1]", "/html[1]/body[1]/article[1]", "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]", "/html[1]/body[1]/article[1]/header[1]/h1[1]/a[1]", "/html[1]/body[1]/article[1]/header[1]/time[1]", "/html[1]/body[1]/article[1]/p[1]", "/html[1]/body[1]/article[1]/footer[1]", "/html[1]/body[1]/article[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]", "/html[1]/body[1]/footer[1]/a[1]", "/html[1]/body[1]/footer[1]/form[1]", "/html[1]/body[1]/footer[1]/form[1]/input[1]", "/html[1]/body[1]/footer[1]/small[1]", "/html[1]/body[1]/footer[1]/small[1]/a[1]", "/html[1]/body[1]/mw:section[1]" ] ],
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
			// CSS escapes that look like pseudoclasses (no match, but the selector is valid)
			[ 'x\\:x', [] ],
			[ '#x\\:x', [] ],
			[ '.x\\:x', [] ],
			// Escaped selectors of this form will match where appropriate
			[ 'mw\\:section', [ "/html[1]/body[1]/mw:section[1]" ] ],
			[ '#mw\\:section', [ "/html[1]/body[1]/mw:section[1]" ] ],
			[ '.mw\\:class', [ "/html[1]/body[1]/mw:section[1]" ] ],
			// The comma combinator
			[ '#\\00a9, article p', [ '/html[1]/body[1]/article[1]/p[1]', '/html[1]/body[1]/footer[1]/small[1]/a[1]' ] ],
			[ 'article > header, header + p', [ "/html[1]/body[1]/article[1]/header[1]", "/html[1]/body[1]/article[1]/p[1]" ] ],
			// Case insensitive attribute value matching
			[ 'input[value="submit"i]', [ '/html[1]/body[1]/footer[1]/form[1]/input[2]' ] ],
			// Namespace selector
			[ 'head', [ '/html[1]/head[1]' ] ],
			[ '*|head', [ '/html[1]/head[1]' ] ],
			// Note that the result of this next test is different from
			// what a standards-compliant DOM library would report, but
			// the \DOMDocument implementation in PHP has a number of
			// bugs handling namespaced elements and in order to
			// workaround these we strip the namespace of all elements.
			// (DISABLED because \Dom\Document actually does the right thing!)
			// [ '|head', [ '/html[1]/head[1]' ] ],
			// Ensure selectors can't match Document or DocumentFragment at root
			[ ':checked html, :enabled html, :disabled html', [] ],
			[ ':lang(en) html, :dir(rtl) html', [] ],
			[ ':required html, :read-only html', [] ],
			[ ':has(*) > html', [] ],
			[ '[href] > html', [] ],
			[ ':not(.xyz) > html', [] ],
			[ ':is(head, body) > html', [] ],
			[ '* html', [] ],
			[ '*[pubdate]', [ '/html[1]/body[1]/article[1]/header[1]/time[1]' ] ],
			[ '*[nonexistent]', [] ],
		];
		foreach ( self::docProvider() as $desc => [ $docFunc ] ) {
			foreach ( $cases as $c ) {
				array_unshift( $c, $docFunc );
				yield $c[1] . "($desc)" => $c;
			}
		}
	}

	/**
	 * @dataProvider docProvider
	 */
	public function testFindId( callable $docFunc ) {
		$doc = $docFunc();
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

	/**
	 * @dataProvider docProvider
	 */
	public function testFindTag( callable $docFunc ) {
		$doc = $docFunc();
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

	/**
	 * @dataProvider docProvider
	 */
	public function testScoping( callable $docFunc ) {
		$doc = $docFunc();
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
		$this->assertSame( [], $els ); // *exclusive*
		$els = Zest::find( 'h1', $h1 );
		$this->assertSame( [], $els ); // *exclusive*
		$h1->setAttribute( 'class', 'shortcut' );
		$els = Zest::find( '.shortcut', $h1 );
		$this->assertSame( [], $els ); // *exclusive*

		// Test `matches` as well; these are *inclusive*
		$this->assertTrue( Zest::matches( $scope, '*' ) );
		$this->assertTrue( Zest::matches( $scope, 'header' ) );
		$this->assertTrue( Zest::matches( $scope, ':scope' ) );
		$this->assertFalse( Zest::matches( $scope, ':root' ) );
		$this->assertTrue( Zest::matches( $h1, '#hi' ) );
		$this->assertTrue( Zest::matches( $h1, 'h1' ) );
		$this->assertTrue( Zest::matches( $h1, '.shortcut' ) );
	}

	/**
	 * @dataProvider multiIdProvider
	 */
	public function testMultiId( callable $docFunc, bool $useCallable ) {
		$doc = $docFunc();
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

	public static function multiIdProvider() {
		foreach ( self::docProvider() as $desc => [ $docFunc ] ) {
			yield "$desc,not callable" => [ $docFunc, false ];
			yield "$desc,callable" => [ $docFunc, true ];
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

	public static function loadDOMHtml( string $filename ) {
		$doc = new DOMDocument;
		$html = file_get_contents( $filename );
		libxml_use_internal_errors( true );
		$doc->loadHTML( $html, LIBXML_NOERROR );
		self::fixupMwSection( $doc );
		libxml_clear_errors();
		return $doc;
	}

	public static function loadDomDocumentHtml( string $filename ) {
		libxml_use_internal_errors( true );
		$doc = \Dom\HTMLDocument::createFromFile( $filename, LIBXML_NOERROR );
		self::fixupMwSection( $doc );
		libxml_clear_errors();
		return $doc;
	}

	public static function fixupMwSection( $doc ) {
		// PHP's "loadHTMLFile" screws up HTML tags with embedded colons,
		// so fix up the 'mw:section' element in the test document.
		$section = $doc->getElementById( "mw:section" );
		$mwSection = $doc->createElement( "mw:section" );
		// Transfer attributes
		$mwSection->setAttribute( "class", "mw:class" );
		$mwSection->setAttribute( "id", "mw:section" );
		// Transfer children
		while ( $section->firstChild !== null ) {
			$mwSection->appendChild( $section->firstChild );
		}
		// Replace element with incorrect tagName w/ corrected element
		$section->parentNode->replaceChild( $mwSection, $section );
	}

	public static function loadRemexHtml( string $filename, $options = [] ) {
		$text = file_get_contents( $filename );
		return self::parseHtml( $text, $options );
	}

	public static function parseHtml( string $text, $options = [] ) {
		$domBuilder = new DOM\DOMBuilder( $options + [
			/* DOM builder options  */
			// Element names with embedded colons don't work properly unless
			// 'suppressHtmlNamespace' is set.
			'suppressHtmlNamespace' => true,
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

	public static function docProvider() {
		$defaultFilename = __DIR__ . "/index.html";

		$docFunc = fn ( $filename = null ) => self::loadDOMHtml( $filename ?? $defaultFilename );
		yield "DOMDocument, loadHTML" => [ $docFunc ];

		$docFunc = fn ( $filename = null ) => self::loadRemexHtml( $filename ?? $defaultFilename, [
			// Element names with embedded colons don't work properly unless
			// 'suppressHtmlNamespace' is set.
			'suppressHtmlNamespace' => true,
		] );
		yield "DOMDocument, Remex" => [ $docFunc ];

		// PHP 8.4 \Dom\Document
		if ( class_exists( '\Dom\Document' ) ) {
			$docFunc = fn ( $filename = null ) => self::loadDomDocumentHtml( $filename ?? $defaultFilename );
			yield 'Dom\\Document, createFromFile' => [ $docFunc ];

			$docFunc = fn ( $filename = null ) => self::loadRemexHtml( $filename ?? $defaultFilename, [
				'suppressIdAttribute' => true,
				'domImplementationClass' => \Dom\Implementation::class,
			] );
			yield 'Dom\\Document, Remex' => [ $docFunc ];
		}
	}

}
