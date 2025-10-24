<?php
declare( strict_types = 1 );

namespace Wikimedia\Zest\Tests;

use Wikimedia\Zest\ZestJQ;

/**
 * @covers \Wikimedia\Zest\ZestJQ
 */
class ZestJQTest extends \PHPUnit\Framework\TestCase {

	/**
	 * HTML fixture with JSON-bearing attributes.
	 *
	 * Elements:
	 *	 cite-needed  typeof=mw:Transclusion, data-mw has one template part:
	 *					Citation_needed with a date param ("March 2026")
	 *	 refimprove	  typeof=mw:Transclusion, data-mw has one template part:
	 *					Refimprove, no date param
	 *	 parserfunc	  typeof=mw:Transclusion, data-mw has one parserfunction part
	 *					(no "template" key at all)
	 *	 mixed		  typeof=mw:Transclusion, data-mw has two parts:
	 *					parts[0] is a bare string, parts[1] is Citation_needed
	 *	 no-attr	  no data-mw attribute
	 *	 bad-json	  data-mw present but not valid JSON
	 *	 numbers	  separate data-vals attribute with numeric/bool/null values
	 */
	private const HTML = <<<'HTML'
<html><body>
<span id="cite-needed" typeof="mw:Transclusion"
  data-mw='{"parts":[{"template":{"target":{"wt":"Citation needed","href":"./Template:Citation_needed"},"params":{"date":{"wt":"March 2026"}},"i":0}}]}'
></span>
<span id="refimprove" typeof="mw:Transclusion"
  data-mw='{"parts":[{"template":{"target":{"wt":"Refimprove","href":"./Template:Refimprove"},"params":{},"i":0}}]}'
></span>
<span id="parserfunc" typeof="mw:Transclusion"
  data-mw='{"parts":[{"parserfunction":{"target":{"wt":"#if:"},"params":{},"i":0}}]}'
></span>
<span id="mixed" typeof="mw:Transclusion"
  data-mw='{"parts":["some text",{"template":{"target":{"wt":"Citation needed","href":"./Template:Citation_needed"},"params":{},"i":1}}]}'
></span>
<span id="no-attr"></span>
<span id="bad-json" data-mw="not valid json"></span>
<span id="numbers" data-vals='{"count":42,"flag":true,"nothing":null}'></span>
</body></html>
HTML;

	private static function doc() {
		return ZestTest::parseHtml( self::HTML );
	}

	/** Extract the id attribute from each matched element, sorted. */
	private static function ids( array $els ): array {
		return array_map(
			static fn ( $el ) => $el->getAttribute( 'id' ),
			$els
		);
	}

	/**
	 * @dataProvider findProvider
	 */
	public function testFind( string $selector, array $expectedIds ) {
		$this->assertEqualsCanonicalizing( $expectedIds, self::ids( ZestJQ::find( $selector, self::doc() ) ) );
	}

	public static function findProvider(): iterable {
		// --- Equality on a nested path through an array ---

		yield 'equality: Citation_needed href' => [
			'[data-mw/.parts[].template?.target.href == "./Template:Citation_needed"]',
			[ 'cite-needed', 'mixed' ],
		];

		yield 'equality: Refimprove href' => [
			'[data-mw/.parts[].template?.target.href == "./Template:Refimprove"]',
			[ 'refimprove' ],
		];

		yield 'equality: no match for non-existent template' => [
			'[data-mw/.parts[].template?.target.href == "./Template:NonExistent"]',
			[],
		];

		// --- Combined standard CSS attribute selector + JQ ---

		yield 'combined CSS+JQ: typeof and Citation_needed' => [
			'[typeof="mw:Transclusion"][data-mw/.parts[].template?.target.href == "./Template:Citation_needed"]',
			[ 'cite-needed', 'mixed' ],
		];

		// --- Path-only truthy check (no comparison operator) ---

		yield 'path-only: .template? exists in any part' => [
			'[data-mw/.parts[].template?]',
			[ 'cite-needed', 'mixed', 'refimprove' ],
			// parserfunc: has parserfunction key, not template; ? suppresses the
			// missing-key error so it contributes no results and does not match
		];

		yield 'path-only: .parserfunction? exists in any part' => [
			'[data-mw/.parts[].parserfunction?]',
			[ 'parserfunc' ],
		];

		// --- Existence check distinguishes valid JSON from missing/invalid attrs ---

		yield 'path-only: .parts exists (valid JSON only, not bad-json or no-attr)' => [
			'[data-mw/.parts]',
			[ 'cite-needed', 'mixed', 'parserfunc', 'refimprove' ],
			// bad-json: data-mw is present but not valid JSON -> silently no match
			// no-attr: data-mw attribute is absent -> no match
		];

		// --- Deep path: multiple property steps ---

		yield 'deep path: date param value' => [
			'[data-mw/.parts[].template?.params.date.wt == "March 2026"]',
			[ 'cite-needed' ],
			// refimprove: empty params object; mixed: Citation_needed has no date param
		];

		// --- Explicit array indexing with [n] ---

		yield 'index [0]: first part only' => [
			'[data-mw/.parts[0].template?.target.href == "./Template:Citation_needed"]',
			[ 'cite-needed' ],
			// mixed: parts[0] is a bare string, so .template? yields nothing for it
		];

		yield 'negative index [-1]: last part' => [
			'[data-mw/.parts[-1].template?.target.href == "./Template:Citation_needed"]',
			[ 'cite-needed', 'mixed' ],
			// cite-needed: one part, parts[-1] = parts[0] = the template
			// mixed: two parts, parts[-1] = parts[1] = Citation_needed template
		];

		// --- Inequality ---

		yield 'inequality: href is not Citation_needed' => [
			'[typeof="mw:Transclusion"][data-mw/.parts[].template?.target.href != "./Template:Citation_needed"]',
			[ 'parserfunc', 'refimprove' ],
			// parserfunc: no .template key -> .template? = null (absent key yields null in jq)
			//   -> null.target = null -> null.href = null -> null != "..." is true -> match
			// cite-needed, mixed: href == Citation_needed -> != is false -> no match
			// refimprove: href != Citation_needed -> match
		];

		// --- Numeric and boolean literal comparisons ---

		yield 'numeric equality' => [
			'[data-vals/.count == 42]',
			[ 'numbers' ],
		];

		yield 'numeric >=: match' => [
			'[data-vals/.count >= 10]',
			[ 'numbers' ],
		];

		yield 'numeric <: no match' => [
			'[data-vals/.count < 10]',
			[],
		];

		yield 'boolean true equality' => [
			'[data-vals/.flag == true]',
			[ 'numbers' ],
		];

		yield 'boolean false: no match when value is true' => [
			'[data-vals/.flag == false]',
			[],
		];

		yield 'null equality' => [
			'[data-vals/.nothing == null]',
			[ 'numbers' ],
		];

		// --- Missing attribute ---

		yield 'missing attribute: never matches' => [
			'[no-such-attr/.foo == "bar"]',
			[],
		];
	}

}
