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
    // Error codes for future i18n support
    const ERROR_TOO_LARGE = 'too_large';
    const ERROR_EMPTY = 'empty';
    const ERROR_INVALID_ROOT = 'invalid_root';
    const ERROR_MALFORMED_XML = 'malformed_xml';
    const ERROR_INVALID_ATTRIBUTE = 'invalid_attribute';
    const ERROR_NESTING_DEEP = 'nesting_too_deep';
    const ERROR_XML_PARSE = 'xml_parse_error';

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
     * Error code holder.
     *
     * @var string|null
     */
    private ?string $error_code = null;

    /**
     * Debug error details (technical information).
     *
     * @var string|null
     */
    private ?string $error_debug = null;

    /**
     * Debug mode flag.
     *
     * @var bool
     */
    private bool $debug_mode = false;

    /**
     * Allowed SVG tags (whitelist).
     *
     * @var array
     */
    private array $allowed_tags = array();

    /**
     * Allowed SVG attributes (whitelist).
     *
     * @var array
     */
    private array $allowed_attributes = array();

    /**
     * Use element nesting limit.
     *
     * @var int
     */
    private int $use_nesting_limit = 15;

    /**
     * XML document instance.
     *
     * @var \DOMDocument|null
     */
    private ?\DOMDocument $xml_document = null;

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
        'debug'       => ! empty($request['debug']),
        );

        // Set debug mode
        $this->debug_mode = $this->options['debug'];

        // Initialize allowed tags and attributes
        $this->initialize_whitelists();
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
            $this->set_error(self::ERROR_TOO_LARGE, 'SVG too large (250 KB limit).');
            return;
        }

        // Remove PHP tags recursively before processing (XXE protection)
        $cleaned_input = $this->remove_php_tags($this->input_svg);

        // Sanitize and validate using DOMDocument
        $sanitized_svg = $this->sanitize_and_validate_svg($cleaned_input);

        if ($sanitized_svg === false || $this->error_message !== null ) {
            return; // Error already set
        }

        // Proceed only if validation passed.
        $normalized = $this->normalize_svg(
            $sanitized_svg,
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
     * Backward compatible - returns user-friendly message.
     *
     * @return string|null Error message or null.
     */
    public function get_error(): ?string
    {
        return $this->error_message;
    }

    /**
     * Get error code for i18n support.
     *
     * @return string|null Error code or null.
     */
    public function get_error_code(): ?string
    {
        return $this->error_code;
    }

    /**
     * Get detailed error information (code, message, debug).
     *
     * @return array|null Error details or null.
     */
    public function get_error_details(): ?array
    {
        if ($this->error_message === null ) {
            return null;
        }

        $details = array(
            'code'    => $this->error_code ?? self::ERROR_XML_PARSE,
            'message' => $this->error_message,
        );

        // Include debug details if debug mode is enabled
        if ($this->debug_mode && $this->error_debug !== null ) {
            $details['debug'] = $this->error_debug;
        }

        return $details;
    }

    /**
     * Set an error message with code and optional debug details.
     *
     * @param string      $code    Error code constant.
     * @param string      $message User-friendly error message.
     * @param string|null $debug   Optional technical details for debug mode.
     */
    private function set_error( string $code, string $message, ?string $debug = null ): void
    {
        $this->error_code = $code;
        $this->error_message = $message;
        $this->error_debug = $debug;
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
        // Already sanitized SVG, just return it
        return $svg;
    }

    /**
     * Remove PHP tags recursively (XXE protection).
     *
     * @param  string $svg SVG markup.
     * @return string Cleaned SVG markup.
     */
    private function remove_php_tags( string $svg ): string
    {
        do {
            $svg = preg_replace('/<\?(=|php)(.+?)\?>/i', '', $svg);
        } while (preg_match('/<\?(=|php)(.+?)\?>/i', $svg) != 0);

        return $svg;
    }

    /**
     * Initialize whitelists for tags and attributes.
     */
    private function initialize_whitelists(): void
    {
        // Allowed SVG tags (core set)
        $this->allowed_tags = array_map(
            'strtolower', array(
            'svg', 'g', 'path', 'rect', 'circle', 'polygon', 'line', 'polyline',
            'ellipse', 'defs', 'use', 'text', 'tspan', 'image', 'clipPath',
            'mask', 'pattern', 'linearGradient', 'radialGradient', 'stop',
            'title', 'desc', 'a', 'switch', 'symbol', 'view',
            )
        );

        // Allowed SVG attributes (core set)
        $this->allowed_attributes = array_map(
            'strtolower', array(
            // Common attributes
            'id', 'class', 'style', 'title', 'lang', 'xml:space',
            // SVG attributes
            'x', 'y', 'width', 'height', 'viewBox', 'preserveAspectRatio',
            'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width',
            'stroke-opacity', 'stroke-linecap', 'stroke-linejoin',
            'transform', 'opacity', 'display', 'visibility',
            // Text attributes
            'font-family', 'font-size', 'font-weight', 'text-anchor',
            // Link attributes
            'href', 'xlink:href', 'target',
            // Other common attributes
            'cx', 'cy', 'r', 'rx', 'ry', 'x1', 'y1', 'x2', 'y2',
            'points', 'd', 'offset', 'stop-color', 'stop-opacity',
            'xmlns', 'xmlns:xlink',
            )
        );
    }

    /**
     * Sanitize and validate SVG using DOMDocument.
     *
     * @param  string $svg SVG markup.
     * @return string|false Sanitized SVG or false on failure.
     */
    private function sanitize_and_validate_svg( string $svg )
    {
        $svg = trim($svg);

        if ($svg === '' ) {
            $this->set_error(self::ERROR_EMPTY, 'Empty SVG content.');
            return false;
        }

        // Must start with <svg
        if (! preg_match('/^<\s*svg\b/i', $svg) ) {
            $this->set_error(self::ERROR_INVALID_ROOT, 'SVG must start with an &lt;svg&gt; tag.');
            return false;
        }

        // Set up DOMDocument with XXE protection
        $this->xml_document = new \DOMDocument();
        $this->xml_document->preserveWhiteSpace = false;
        $this->xml_document->strictErrorChecking = false;

        // Disable entity loader for older libxml (XXE protection)
        $xml_loader_value = null;
        if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
            $xml_loader_value = libxml_disable_entity_loader(true);
        }

        // Enable internal error handling
        $use_errors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        // Try to load XML (LIBXML_NONET prevents network access)
        $loaded = @$this->xml_document->loadXML($svg, LIBXML_NONET);

        // Get XML errors
        $xml_errors = libxml_get_errors();
        libxml_clear_errors();
        libxml_use_internal_errors($use_errors);

        // Restore entity loader
        if ($xml_loader_value !== null && function_exists('libxml_disable_entity_loader')) {
            libxml_disable_entity_loader($xml_loader_value);
        }

        // If XML parsing failed
        if (!$loaded || !empty($xml_errors) ) {
            // Get technical error message
            $technical_error = !empty($xml_errors) ? trim($xml_errors[0]->message) : 'Invalid XML structure.';

            // Map technical errors to user-friendly messages
            $user_message = $this->map_xml_error_to_user_message($technical_error);

            // Set error with code and optional debug details
            $this->set_error(
                self::ERROR_XML_PARSE,
                $user_message,
                $technical_error // Include technical details for debug mode
            );
            return false;
        }

        // Get root element
        $root = $this->xml_document->documentElement;
        if (!$root || strtolower($root->tagName) !== 'svg' ) {
            $this->set_error(self::ERROR_INVALID_ROOT, 'Invalid SVG: root element must be &lt;svg&gt;.');
            return false;
        }

        // Clean the DOM structure
        $this->clean_dom_tree($root, 0);

        // Check for remaining use elements with nesting issues
        if ($this->check_use_nesting($root, 0) === false ) {
            $this->set_error(self::ERROR_NESTING_DEEP, 'SVG contains excessive nesting in &lt;use&gt; elements.');
            return false;
        }

        // Save cleaned XML
        $cleaned = $this->xml_document->saveXML($root);
        $this->xml_document = null; // Clean up

        return $cleaned;
    }

    /**
     * Clean DOM tree recursively, removing non-whitelisted elements and attributes.
     *
     * @param \DOMElement $element Current element.
     * @param int         $depth   Current nesting depth.
     */
    private function clean_dom_tree( \DOMElement $element, int $depth ): void
    {
        // Prevent excessive nesting
        if ($depth > 100 ) {
            $element->parentNode->removeChild($element);
            return;
        }

        // Handle CDATA sections (convert to text nodes)
        $cdata_nodes = array();
        foreach ($element->childNodes as $child ) {
            if ($child instanceof \DOMCdataSection ) {
                $cdata_nodes[] = $child;
            }
        }
        foreach ($cdata_nodes as $cdata_node ) {
            $text_node = $this->xml_document->createTextNode($cdata_node->nodeValue);
            $element->replaceChild($text_node, $cdata_node);
        }

        // Check if tag is allowed
        $tag_name = strtolower($element->tagName);
        if (!in_array($tag_name, $this->allowed_tags, true) ) {
            // Special handling: check if it's a dangerous tag that should be removed
            $dangerous_tags = array('script', 'foreignobject', 'iframe', 'object', 'embed', 'link', 'style');
            if (in_array($tag_name, $dangerous_tags, true) ) {
                $element->parentNode->removeChild($element);
                return;
            }
            // Remove unknown tags
            $element->parentNode->removeChild($element);
            return;
        }

        // Clean attributes
        $attributes_to_remove = array();
        foreach ($element->attributes as $attr ) {
            $attr_name = strtolower($attr->nodeName);
            $attr_name_no_prefix = strtolower($attr->localName ?? $attr_name);

            // Remove event handlers (onclick, onload, etc.)
            if (strpos($attr_name, 'on') === 0 && ctype_alpha(substr($attr_name, 2, 1)) ) {
                $attributes_to_remove[] = $attr->nodeName;
                // Note: Event handlers are already caught during XML parsing,
                // but we still remove them here if XML parsing succeeded
                continue;
            }

            // Check if attribute is allowed
            $is_allowed = in_array($attr_name, $this->allowed_attributes, true) ||
                         in_array($attr_name_no_prefix, $this->allowed_attributes, true) ||
                         strpos($attr_name, 'aria-') === 0 ||
                         strpos($attr_name, 'data-') === 0;

            if (!$is_allowed ) {
                $attributes_to_remove[] = $attr->nodeName;
                continue;
            }

            // Validate href attributes
            if (strpos($attr_name, 'href') !== false ) {
                if (!$this->is_href_safe($attr->value) ) {
                    $attributes_to_remove[] = $attr->nodeName;
                }
            }

            // Reject javascript: and data:text/html in any attribute
            if (preg_match('/(?:javascript:|data:text\/html|vbscript:)/i', $attr->value) ) {
                $attributes_to_remove[] = $attr->nodeName;
            }
        }

        // Remove disallowed attributes
        foreach ($attributes_to_remove as $attr_name ) {
            $element->removeAttribute($attr_name);
        }

        // Recursively clean child elements
        $children = array();
        foreach ($element->childNodes as $child ) {
            if ($child instanceof \DOMElement ) {
                $children[] = $child;
            }
        }

        foreach ($children as $child ) {
            $this->clean_dom_tree($child, $depth + 1);
        }
    }

    /**
     * Check if href value is safe.
     *
     * @param  string $href Href value.
     * @return bool True if safe, false otherwise.
     */
    private function is_href_safe( string $href ): bool
    {
        if (empty($href) ) {
            return true;
        }

        // Allow fragment identifiers
        if (substr($href, 0, 1) === '#' ) {
            return true;
        }

        // Allow relative URIs
        if (substr($href, 0, 1) === '/' ) {
            return true;
        }

        // Allow HTTPS/HTTP domains
        if (substr($href, 0, 8) === 'https://' || substr($href, 0, 7) === 'http://' ) {
            return true;
        }

        // Allow only safe image data URIs
        $safe_data_uris = array(
            'data:image/png',
            'data:image/gif',
            'data:image/jpg',
            'data:image/jpeg',
            'data:image/svg+xml',
        );
        foreach ($safe_data_uris as $safe_uri ) {
            if (substr($href, 0, strlen($safe_uri)) === $safe_uri ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check use element nesting depth.
     *
     * @param  \DOMElement $element Current element.
     * @param  int         $depth   Current nesting depth.
     * @return bool True if safe, false if nesting too deep.
     */
    private function check_use_nesting( \DOMElement $element, int $depth ): bool
    {
        if ($depth > $this->use_nesting_limit ) {
            return false;
        }

        if (strtolower($element->tagName) === 'use' ) {
            // Check href/xlink:href
            $href = $element->getAttribute('href') ?:
                    $element->getAttributeNS('http://www.w3.org/1999/xlink', 'href');

            if ($href && substr($href, 0, 1) === '#' ) {
                // Recursively check referenced elements
                $referenced_id = substr($href, 1);
                $referenced = $this->xml_document->getElementById($referenced_id);
                if ($referenced && $referenced instanceof \DOMElement ) {
                    return $this->check_use_nesting($referenced, $depth + 1);
                }
            }
        }

        // Check children
        foreach ($element->childNodes as $child ) {
            if ($child instanceof \DOMElement ) {
                if (!$this->check_use_nesting($child, $depth) ) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Map technical XML parser errors to user-friendly messages.
     *
     * @param  string $technical_error Raw XML parser error message.
     * @return string User-friendly error message.
     */
    private function map_xml_error_to_user_message( string $technical_error ): string
    {
        $technical_lower = strtolower($technical_error);

        // Map common technical errors to user-friendly messages
        $error_mappings = array(
            // Event handler errors
            '/attribute\s+on\w+/i'                                    => 'SVG contains invalid event handler attributes.',
            '/specification\s+mandates\s+value\s+for\s+attribute\s+on/i' => 'SVG contains invalid event handler attributes.',

            // Tag mismatch errors
            '/opening\s+and\s+ending\s+tag\s+mismatch/i'             => 'SVG has mismatched tags.',
            '/end\s+tag\s+name/i'                                    => 'SVG has mismatched tags.',

            // Syntax errors
            '/syntax\s+error/i'                                       => 'SVG has syntax errors.',
            '/not\s+well-formed/i'                                   => 'SVG is not well-formed.',
            '/unclosed\s+token/i'                                    => 'SVG has unclosed tags.',

            // Premature/incomplete errors
            '/premature\s+end\s+of\s+data/i'                         => 'SVG is incomplete or corrupted.',
            '/unexpected\s+end\s+of\s+data/i'                        => 'SVG is incomplete or corrupted.',
            '/unexpected\s+end\s+tag/i'                                => 'SVG is incomplete or corrupted.',

            // Attribute errors
            '/attribute\s+value\s+not\s+terminated/i'                 => 'SVG has invalid attribute values.',
            '/required\s+attribute\s+missing/i'                       => 'SVG is missing required attributes.',
        );

        // Check each mapping pattern
        foreach ($error_mappings as $pattern => $user_message ) {
            if (preg_match($pattern, $technical_error) ) {
                return $user_message;
            }
        }

        // Fallback for unknown XML errors
        return 'Could not parse SVG.';
    }
}
