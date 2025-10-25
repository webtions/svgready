<?php
/**
 * SVG Converter Class
 *
 * Handles SVG normalization, validation, and encoding.
 *
 * @package SVGready
 * @since   1.0.0
 */

if (! defined('SVGREADY') ) {
    define('SVGREADY', true);
}

/**
 * Converts and validates SVG markup.
 */
class SvgConverter
{

    /**
     * Raw input SVG markup.
     *
     * @var string
     */
    private string $input_svg = '';

    /**
     * Processed results.
     *
     * @var array
     */
    private array $results = array();

    /**
     * Options for processing.
     *
     * @var array
     */
    private array $options = array();

    /**
     * Error message holder.
     *
     * @var string|null
     */
    private ?string $error_message = null;

    /**
     * Constructor.
     *
     * @param array $request Request data (e.g., $_POST).
     */
    public function __construct( array $request = array() )
    {
        $this->input_svg = trim($request['svg'] ?? '');

        $this->options = array(
        'strip_wh'    => ! empty($request['strip_wh']),
        'strip_class' => ! empty($request['strip_class']),
        'show_base64' => ! empty($request['show_base64']),
        );
    }

    /**
     * Process SVG input and generate outputs.
     */
    public function process(): void
    {
        if ($this->input_svg === '' ) {
            return;
        }

        if (strlen($this->input_svg) > 250000 ) {
            $this->set_error('SVG too large (250 KB limit).');
            return;
        }

        // Validate before any processing.
        if (! $this->validate_svg($this->input_svg) ) {
            $this->set_error('Invalid or potentially dangerous SVG content detected.');
            return;
        }

        // Proceed only if validation passed.
        $normalized = $this->normalize_svg(
            $this->input_svg,
            $this->options['strip_wh'],
            $this->options['strip_class']
        );

        $this->results['normalized']   = $normalized;
        $this->results['data_uri_css'] = $this->svg_to_data_uri($normalized);
        $this->results['bg_snippet']   = 'background-image: url("' . $this->results['data_uri_css'] . '");';
        $this->results['mask_snippet'] = 'mask-image: url("' . $this->results['data_uri_css'] . '");' . "\n" .
        '-webkit-mask-image: url("' . $this->results['data_uri_css'] . '");';

        if ($this->options['show_base64'] ) {
            $this->results['data_uri_b64'] = $this->svg_to_base64($normalized);
        }
    }

    /**
     * Get all processed results.
     */
    public function get_results(): array
    {
        return $this->results;
    }

    /**
     * Get current error message, if any.
     */
    public function get_error(): ?string
    {
        return $this->error_message;
    }

    /**
     * Set an error message.
     */
    private function set_error( string $message ): void
    {
        $this->error_message = $message;
    }

    /**
     * Normalize SVG markup.
     */
    private function normalize_svg( string $svg, bool $strip_root_wh = true, bool $strip_root_class = true ): string
    {
        $svg = preg_replace('/^\xEF\xBB\xBF/', '', $svg ?? '');
        $svg = trim($svg);

        if ($svg === '' ) {
            return '';
        }

        $svg = preg_replace('/^\s*<\?xml[^>]*>\s*/i', '', $svg);
        $svg = preg_replace('/<!--.*?-->/s', '', $svg);

        // Add missing xmlns if not present.
        if (stripos($svg, 'xmlns=') === false ) {
            $svg = preg_replace(
                '/<svg\b(?![^>]*\sxmlns=)([^>]*)>/i',
                '<svg$1 xmlns="http://www.w3.org/2000/svg">',
                $svg,
                1
            );
        }

        // Strip width/height or class if requested.
        $svg = preg_replace_callback(
            '/<svg\b[^>]*>/i',
            function ( $m ) use ( $strip_root_wh, $strip_root_class ) {
                $open = $m[0];

                if ($strip_root_wh ) {
                    $open = preg_replace(
                        array(
                        '/\swidth\s*=\s*"[^"]*"/i',
                        "/\swidth\s*=\s*'[^']*'/i",
                        '/\sheight\s*=\s*"[^"]*"/i',
                        "/\sheight\s*=\s*'[^']*'/i",
                        ),
                        '',
                        $open
                    );
                }

                if ($strip_root_class ) {
                    $open = preg_replace(
                        array(
                        '/\sclass\s*=\s*"[^"]*"/i',
                        "/\sclass\s*=\s*'[^']*'/i",
                        ),
                        '',
                        $open
                    );
                }

                return preg_replace('/\s+>$/', '>', $open);
            },
            $svg,
            1
        );

        $svg = preg_replace('/\s+/', ' ', $svg);
        $svg = str_replace('> <', '><', $svg);

        return trim($svg);
    }

    /**
     * Convert SVG to percent-encoded data URI.
     */
    private function svg_to_data_uri( string $svg ): string
    {
        $encoded  = rawurlencode($svg);
        $search   = array( '%20', '%3D', '%3A', '%2F', '%2C', '%3B', '%28', '%29', '%23', "%'", '%22' );
        $replace  = array( ' ', '=', ':', '/', ',', ';', '(', ')', '#', "'", '%22' );
        $encoded  = str_replace($search, $replace, $encoded);
        return 'data:image/svg+xml,' . $encoded;
    }

    /**
     * Convert SVG to base64-encoded data URI.
     */
    private function svg_to_base64( string $svg ): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Sanitize SVG for safe display in preview.
     *
     * This is used *only after validation* passes.
     */
    public function sanitize_svg( string $svg ): string
    {
        $svg = preg_replace('/<script\b[^>]*>.*?<\/script>/is', '', $svg);
        $svg = preg_replace('/\son\w+\s*=\s*(["\']).*?\1/i', '', $svg);
        $svg = preg_replace('/\s(xlink:href|href|style)\s*=\s*(["\']).*?\2/i', '', $svg);

        $allowed_tags = '<svg><g><path><rect><circle><polygon><line><polyline><ellipse><defs><use><text><image><clipPath><mask><pattern><linearGradient><radialGradient><stop><title><desc>';
        return strip_tags($svg, $allowed_tags);
    }

    /**
     * Validate SVG for security and structure.
     *
     * Returns false immediately if not pure SVG.
     */
    private function validate_svg( string $svg ): bool
    {
        $svg = trim($svg);

        // Must start with <svg
        if (! preg_match('/^<\s*svg\b/i', $svg) ) {
            return false;
        }

        // Reject PHP or other server-side tags
        if (preg_match('/<\?(?:php|=)?|<%|%>|<jsp|<asp/i', $svg) ) {
            return false;
        }

        // Reject DOCTYPE or ENTITY (XXE)
        if (preg_match('/<!DOCTYPE\b|<!ENTITY\b/i', $svg) ) {
            return false;
        }

        // Reject dangerous elements
        if (preg_match('/<script\b|<foreignObject\b|<style\b|<iframe\b|<object\b|<embed\b|<link\b/i', $svg) ) {
            return false;
        }

        // Reject inline event handlers
        if (preg_match('/on\w+\s*=/i', $svg) ) {
            return false;
        }

        // Reject JS or data-based URLs
        if (preg_match('/(?:javascript:|data:text\/html|vbscript:)/i', $svg) ) {
            return false;
        }

        // Disallow external image references (http, https, or data URIs)
        if (preg_match('/<image[^>]+(href|xlink:href)\s*=\s*["\'](?:https?:|data:)/i', $svg) ) {
            return false;
        }

        return true;
    }
}
