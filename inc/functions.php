<?php
/**
 * Core functions for SVG Ready
 *
 * @category Functions
 * @package  SVGready
 * @author   Webtions <mail@webtions.com>
 * @license  https://opensource.org/licenses/MIT MIT
 * @link     https://svgready.com
 * @since    1.0.0
 */

/**
 * Normalizes SVG markup for optimal CSS data URI usage
 *
 * Removes unnecessary elements like XML declarations, comments, and optionally
 * strips width/height and class attributes from the root SVG element.
 * Also ensures proper xmlns declaration and collapses whitespace.
 *
 * @param string $svg              Raw SVG markup to normalize
 * @param bool   $strip_root_wh    Whether to remove width/height from root <svg>
 * @param bool   $strip_root_class Whether to remove class from root <svg>
 *
 * @return string Normalized SVG markup
 */
function normalize_svg(
    string $svg,
    bool $strip_root_wh = true,
    bool $strip_root_class = true
): string {
    // Remove BOM (Byte Order Mark) and trim whitespace
    $svg = preg_replace('/^\xEF\xBB\xBF/', '', $svg ?? '');
    $svg = trim($svg);

    // Return empty string if no content
    if ($svg === '') {
        return '';
    }

    // Remove XML declaration and comments to reduce file size
    $svg = preg_replace('/^\s*<\?xml[^>]*>\s*/i', '', $svg);
    $svg = preg_replace('/<!--.*?-->/s', '', $svg);

    // Ensure xmlns attribute is present on root SVG element
    if (stripos($svg, 'xmlns=') === false) {
        $svg = preg_replace(
            '/<svg\b(?![^>]*\sxmlns=)([^>]*)>/i',
            '<svg$1 xmlns="http://www.w3.org/2000/svg">',
            $svg,
            1
        );
    }

    // Process only the opening <svg> tag to remove unwanted attributes
    $svg = preg_replace_callback(
        '/<svg\b[^>]*>/i',
        function ($m) use ($strip_root_wh, $strip_root_class) {
            $open = $m[0];

            // Remove width and height attributes if requested
            if ($strip_root_wh) {
                $patterns = [
                '/\\swidth\\s*=\\s*"[^"]*"/i',
                "/\\swidth\\s*=\\s*'[^']*'/i",
                '/\\sheight\\s*=\\s*"[^"]*"/i',
                "/\\sheight\\s*=\\s*'[^']*'/i"
                ];
                $open = preg_replace($patterns, '', $open);
            }

            // Remove class attribute if requested
            if ($strip_root_class) {
                $patterns = [
                '/\\sclass\\s*=\\s*"[^"]*"/i',
                "/\\sclass\\s*=\\s*'[^']*'/i"
                ];
                $open = preg_replace($patterns, '', $open);
            }

            // Clean up any double spaces before closing >
            return preg_replace('/\\s+>$/', '>', $open);
        },
        $svg,
        1
    );

    // Collapse multiple whitespace/newlines into single spaces
    $svg = preg_replace('/\\s+/', ' ', $svg);
    $svg = str_replace('> <', '><', $svg);

    return trim($svg);
}

/**
 * Converts SVG markup to percent-encoded CSS data URI
 *
 * Percent-encoding is more readable and often smaller than base64 encoding.
 * This function encodes the SVG and then restores safe characters to keep
 * the data URI readable while maintaining CSS compatibility.
 *
 * @param string $svg Normalized SVG markup
 *
 * @return string Percent-encoded data URI ready for CSS use
 */
function svg_to_data_uri(string $svg): string
{
    // Percent-encode the entire SVG string
    $encoded = rawurlencode($svg);

    // Restore safe characters to improve readability and reduce size
    $search  = ['%20','%3D','%3A','%2F','%2C','%3B','%28','%29','%23',"%'","%22"];
    $replace = [' ',  '=',  ':',  '/',  ',',  ';',  '(',  ')',  '#',  "'",  '%22'];
    $encoded = str_replace($search, $replace, $encoded);

    return 'data:image/svg+xml,' . $encoded;
}

/**
 * Converts SVG markup to base64-encoded CSS data URI
 *
 * Base64 encoding is larger than percent-encoding but has better browser
 * compatibility and doesn't require URL encoding considerations.
 *
 * @param string $svg Normalized SVG markup
 *
 * @return string Base64-encoded data URI ready for CSS use
 */
function svg_to_base64(string $svg): string
{
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Sanitizes SVG markup to remove JavaScript and dangerous attributes
 *
 * Removes all JavaScript event handlers, script tags, and dangerous attributes
 * while preserving safe SVG elements and their legitimate attributes.
 *
 * @param string $svg Raw SVG markup to sanitize
 *
 * @return string Sanitized SVG markup safe for display
 */
function sanitize_svg(string $svg): string
{
    // Remove <script> blocks
    $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);

    // Remove any on*="..." attributes
    $svg = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/i', '', $svg);

    // Remove other potentially dangerous attributes
    $svg = preg_replace('/\s(xlink:href|href|style)\s*=\s*(["\']).*?\2/i', '', $svg);

    // Allow only safe SVG elements
    $allowed_tags = '<svg><g><path><rect><circle><polygon><line><polyline><ellipse><defs><use><text><image><clipPath><mask><pattern><linearGradient><radialGradient><stop><title><desc>';
    return strip_tags($svg, $allowed_tags);
}

/**
 * Sanitizes content for JavaScript data attributes
 *
 * Properly escapes content to prevent XSS when used in JavaScript context
 * like data-copy attributes or JSON output.
 *
 * @param string $content Content to sanitize for JavaScript
 *
 * @return string JavaScript-safe content
 */
function sanitize_for_js(string $content): string
{
    // Escape for JavaScript context using JSON encoding
    $content = json_encode($content, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    return substr($content, 1, -1); // Remove outer quotes
}

/**
 * Validates SVG input for security and structure
 *
 * Checks if input is actually SVG and doesn't contain dangerous patterns
 * before processing to prevent security issues.
 *
 * @param string $svg SVG markup to validate
 *
 * @return bool True if SVG is safe to process
 */
function validate_svg(string $svg): bool
{
    // Check if it's actually SVG
    if (!preg_match('/<svg\b/i', $svg)) {
        return false;
    }

    // Check for dangerous patterns
    if (preg_match('/<script\b|on\w+\s*=/i', $svg)) {
        return false;
    }

    // Check for suspicious content
    if (preg_match('/javascript:|data:text\/html|vbscript:/i', $svg)) {
        return false;
    }

    return true;
}

/**
 * Escapes HTML special characters for safe output
 *
 * Converts special characters to HTML entities to prevent XSS attacks
 * and ensure proper display of user input in HTML context.
 *
 * @param string $s String to escape
 *
 * @return string HTML-escaped string safe for output
 */
function h($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
