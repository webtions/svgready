<?php
/**
 * Helper functions for SVG Ready
 *
 * @package SVGready
 * @since   1.0.0
 */

/**
 * Escapes a string for safe inclusion in JavaScript attributes.
 *
 * @since 1.0.0
 *
 * @param  string $string Content to escape for JavaScript.
 * @return string Escaped string safe for inline JS attributes.
 */
function svgready_escape_js( string $string ): string
{
    $escaped = json_encode($string, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return substr($escaped, 1, -1);
}

/**
 * Escapes HTML special characters for safe output in markup.
 *
 * @since 1.0.0
 *
 * @param  string $string String to escape for HTML.
 * @return string Escaped HTML-safe string.
 */
function svgready_escape_html( string $string ): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
