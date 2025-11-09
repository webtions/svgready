<?php
/**
 * SVG Ready – Helper Functions
 *
 * Provides global helper functions for escaping content safely
 * across JavaScript, HTML, and data attributes.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

/**
 * Escape a string for safe inclusion in JavaScript attributes.
 *
 * @param string $string Content to escape for JavaScript.
 *
 * @since 1.0.0
 *
 * @return string Escaped string safe for inline JS attributes.
 */
function svgreadyEscapeJs(string $string): string
{
	$escaped = json_encode(
		$string,
		JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
	);

	return substr($escaped, 1, -1);
}

/**
 * Escape HTML special characters for safe output in markup.
 *
 * @param string $string String to escape for HTML.
 *
 * @since 1.0.0
 *
 * @return string Escaped HTML-safe string.
 */
function svgreadyEscapeHtml(string $string): string
{
	return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Escape a string for safe use in HTML data attributes.
 *
 * Only escapes quotes and ampersands (not angle brackets, which are
 * safe in quoted attributes). This prevents attribute value injection
 * while preserving readable content.
 *
 * @param string $string String to escape for data attributes.
 *
 * @since 1.0.0
 *
 * @return string Escaped string safe for data-* attributes.
 */
function svgreadyEscapeDataAttr(string $string): string
{
	// Only escape quotes and ampersands for data attributes.
	return str_replace(['"', '&'], ['&quot;', '&amp;'], $string);
}
