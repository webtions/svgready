<?php
/**
 * SVG Ready – Converter Class
 *
 * Handles SVG normalization, validation, and encoding.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SVGReady;

use SVGReady\Logger;

/**
 * Converts and validates SVG markup.
 */
class SVGConverter
{
	// Error codes for future i18n support.
	public const ERROR_TOO_LARGE         = 'too_large';
	public const ERROR_EMPTY             = 'empty';
	public const ERROR_INVALID_ROOT      = 'invalid_root';
	public const ERROR_MALFORMED_XML     = 'malformed_xml';
	public const ERROR_INVALID_ATTRIBUTE = 'invalid_attribute';
	public const ERROR_NESTING_DEEP      = 'nesting_too_deep';
	public const ERROR_XML_PARSE         = 'xml_parse_error';

	/**
	 * Raw input SVG markup.
	 *
	 * @var string
	 */
	private string $inputSvg = '';

	/**
	 * Processed results.
	 *
	 * @var array<string, mixed>
	 */
	private array $results = [];

	/**
	 * Options for processing.
	 *
	 * @var array<string, mixed>
	 */
	private array $options = [];

	/**
	 * Error message holder.
	 *
	 * @var string|null
	 */
	private ?string $errorMessage = null;

	/**
	 * Error code holder.
	 *
	 * @var string|null
	 */
	private ?string $errorCode = null;

	/**
	 * Debug error details (technical information).
	 *
	 * @var string|null
	 */
	private ?string $errorDebug = null;

	/**
	 * Debug mode flag.
	 *
	 * @var bool
	 */
	private bool $debugMode = false;

	/**
	 * Allowed SVG tags (whitelist).
	 *
	 * @var array<int, string>
	 */
	private array $allowedTags = [];

	/**
	 * Allowed SVG attributes (whitelist).
	 *
	 * @var array<int, string>
	 */
	private array $allowedAttributes = [];

	/**
	 * Use element nesting limit.
	 *
	 * @var int
	 */
	private int $useNestingLimit = 15;

	/**
	 * XML document instance.
	 *
	 * @var \DOMDocument|null
	 */
	private ?\DOMDocument $xmlDocument = null;

	/**
	 * Constructor.
	 *
	 * @param array<string, mixed> $request Request data (e.g., $_POST).
	 */
	public function __construct(array $request = [])
	{
		$this->inputSvg = trim($request['svg'] ?? '');

		$this->options = [
                          'strip_wh'    => ! empty($request['stripWh']),
                          'strip_class' => ! empty($request['stripClass']),
                          'show_base64' => ! empty($request['showBase64']),
                          'debug'       => ! empty($request['debug']),
                         ];

		// Set debug mode.
		$this->debugMode = $this->options['debug'];

		// Initialize allowed tags and attributes.
		$this->initializeWhitelists();
	}

	/**
	 * Process SVG input and generate outputs.
	 *
	 * @return void
	 */
	public function process(): void
	{
		// Start benchmark timer.
		$startTime = microtime(true);

		if ($this->inputSvg === '') {
			return;
		}

		if (strlen($this->inputSvg) > 250000) {
			$this->setError(self::ERROR_TOO_LARGE, 'SVG too large (250 KB limit).');
			return;
		}

		// Remove PHP tags recursively before processing (XXE protection).
		$cleanedInput = $this->removePhpTags($this->inputSvg);

		// Sanitize and validate using DOMDocument.
		$sanitizedSvg = $this->sanitizeAndValidateSvg($cleanedInput);

		if ($sanitizedSvg === false || $this->errorMessage !== null) {
			return; // Error already set.
		}

		// Proceed only if validation passed.
		$normalized = $this->normalizeSvg(
			$sanitizedSvg,
			$this->options['strip_wh'],
			$this->options['strip_class']
		);

		$this->results['normalized']   = $normalized;
		$this->results['data_uri_css'] = $this->svgToDataUri($normalized);
		$this->results['bg_snippet']   = 'background-image: url("' . $this->results['data_uri_css'] . '");';
		$this->results['mask_snippet'] = 'mask-image: url("' . $this->results['data_uri_css'] . '");' . "\n" .
			'-webkit-mask-image: url("' . $this->results['data_uri_css'] . '");';

		if ($this->options['show_base64']) {
			$this->results['data_uri_b64'] = $this->svgToBase64($normalized);
		}

		// Record benchmark metrics.
		$this->results['benchmark'] = [
                                       'time_ms'   => round((microtime(true) - $startTime) * 1000, 2),
                                       'memory_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
                                      ];
	}

	/**
	 * Get all processed results.
	 *
	 * @return array<string, mixed>
	 */
	public function getResults(): array
	{
		return $this->results;
	}

	/**
	 * Get current error message, if any.
	 * Backward compatible - returns user-friendly message.
	 *
	 * @return string|null Error message or null.
	 */
	public function getError(): ?string
	{
		return $this->errorMessage;
	}

	/**
	 * Get error code for i18n support.
	 *
	 * @return string|null Error code or null.
	 */
	public function getErrorCode(): ?string
	{
		return $this->errorCode;
	}

	/**
	 * Get detailed error information (code, message, debug).
	 *
	 * @return array<string, mixed>|null Error details or null.
	 */
	public function getErrorDetails(): ?array
	{
		if ($this->errorMessage === null) {
			  return null;
		}

		$details = [
                    'code'    => $this->errorCode ?? self::ERROR_XML_PARSE,
                    'message' => $this->errorMessage,
                   ];

		// Include debug details if debug mode is enabled.
		if ($this->debugMode && $this->errorDebug !== null) {
			$details['debug'] = $this->errorDebug;
		}

		return $details;
	}

	/**
	 * Set an error message with code and optional debug details.
	 *
	 * @param string      $code    Error code constant.
	 * @param string      $message User-friendly error message.
	 * @param string|null $debug   Optional technical details for debug mode.
	 *
	 * @return void
	 */
	private function setError(string $code, string $message, ?string $debug = null): void
	{
		$this->errorCode    = $code;
		$this->errorMessage = $message;
		$this->errorDebug   = $debug;

		$context = [
			'error_code'       => $code,
			'input_svg_length' => strlen($this->inputSvg),
			'debug_details'    => $debug,
		];

		// Include SVG snippet for invalid SVG errors if LOG_SVG_CONTENT is enabled.
		$invalidSvgCodes = [
			self::ERROR_INVALID_ROOT,
			self::ERROR_MALFORMED_XML,
			self::ERROR_INVALID_ATTRIBUTE,
			self::ERROR_NESTING_DEEP,
			self::ERROR_XML_PARSE,
		];

		if (in_array($code, $invalidSvgCodes, true)) {
			$logSvgContent = Logger::getEnv('LOG_SVG_CONTENT');
			if ($logSvgContent === 'true' || $logSvgContent === '1') {
				$svgSnippet = substr($this->inputSvg, 0, 500);
				if (strlen($this->inputSvg) > 500) {
					$svgSnippet .= '... [truncated]';
				}
				$context['svg_input'] = $svgSnippet;
			}
		}

		// Log the error to debug.log.
		Logger::error($message, $context);
	}

	/**
	 * Normalize SVG markup.
	 *
	 * @param string $svg            SVG markup.
	 * @param bool   $stripRootWh    Strip width/height from root.
	 * @param bool   $stripRootClass Strip class from root.
	 *
	 * @return string
	 */
	private function normalizeSvg(string $svg, bool $stripRootWh = true, bool $stripRootClass = true): string
	{
		$svg = preg_replace('/^\xEF\xBB\xBF/', '', $svg);
		$svg = trim($svg);

		if ($svg === '') {
			  return '';
		}

		$svg = preg_replace('/^\s*<\?xml[^>]*>\s*/i', '', $svg);
		$svg = preg_replace('/<!--.*?-->/s', '', $svg);

		// Add missing xmlns if not present.
		if (stripos($svg, 'xmlns=') === false) {
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
            function ($m) use ($stripRootWh, $stripRootClass) {
                  $open = $m[0];

                if ($stripRootWh) {
                     $open = preg_replace(
                         [
                          '/\swidth\s*=\s*"[^"]*"/i',
                          "/\swidth\s*=\s*'[^']*'/i",
                          '/\sheight\s*=\s*"[^"]*"/i',
                          "/\sheight\s*=\s*'[^']*'/i",
                         ],
                         '',
                         $open
                     );
                }

                if ($stripRootClass) {
                     $open = preg_replace(
                         [
                          '/\sclass\s*=\s*"[^"]*"/i',
                          "/\sclass\s*=\s*'[^']*'/i",
                         ],
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
	 *
	 * @param string $svg SVG markup.
	 *
	 * @return string
	 */
	private function svgToDataUri(string $svg): string
	{
		$encoded = rawurlencode($svg);
		$search  = [
                    '%20',
                    '%3D',
                    '%3A',
                    '%2F',
                    '%2C',
                    '%3B',
                    '%28',
                    '%29',
                    '%23',
                    "%'",
                    '%22',
                   ];
		$replace = [
                    ' ',
                    '=',
                    ':',
                    '/',
                    ',',
                    ';',
                    '(',
                    ')',
                    '#',
                    "'",
                    '%22',
                   ];
		$encoded = str_replace($search, $replace, $encoded);
		return 'data:image/svg+xml,' . $encoded;
	}

	/**
	 * Convert SVG to base64-encoded data URI.
	 *
	 * @param string $svg SVG markup.
	 *
	 * @return string
	 */
	private function svgToBase64(string $svg): string
	{
		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	/**
	 * Sanitize SVG for safe display in preview.
	 *
	 * This is used *only after validation* passes.
	 *
	 * @param string $svg SVG markup.
	 *
	 * @return string
	 */
	public function sanitizeSvg(string $svg): string
	{
		// Already sanitized SVG, just return it.
		return $svg;
	}

	/**
	 * Remove PHP tags recursively (XXE protection).
	 *
	 * @param string $svg SVG markup.
	 *
	 * @return string Cleaned SVG markup.
	 */
	private function removePhpTags(string $svg): string
	{
		do {
			  $svg = preg_replace('/<\?(=|php)(.+?)\?>/i', '', $svg);
		} while (preg_match('/<\?(=|php)(.+?)\?>/i', $svg) != 0);

		return $svg;
	}

	/**
	 * Initialize whitelists for tags and attributes.
	 *
	 * @return void
	 */
	private function initializeWhitelists(): void
	{
		// Allowed SVG tags (core set).
		$this->allowedTags = array_map(
            'strtolower',
            [
             'svg',
             'g',
             'path',
             'rect',
             'circle',
             'polygon',
             'line',
             'polyline',
             'ellipse',
             'defs',
             'use',
             'text',
             'tspan',
             'image',
             'clipPath',
             'mask',
             'pattern',
             'linearGradient',
             'radialGradient',
             'stop',
             'title',
             'desc',
             'a',
             'switch',
             'symbol',
             'view',
            ]
		);

		// Allowed SVG attributes (core set).
		$this->allowedAttributes = array_map(
            'strtolower',
            [
            // Common attributes.
             'id',
             'class',
             'style',
             'title',
             'lang',
             'xml:space',
            // SVG attributes.
             'x',
             'y',
             'width',
             'height',
             'viewBox',
             'preserveAspectRatio',
             'fill',
             'fill-opacity',
             'fill-rule',
             'stroke',
             'stroke-width',
             'stroke-opacity',
             'stroke-linecap',
             'stroke-linejoin',
             'transform',
             'opacity',
             'display',
             'visibility',
            // Text attributes.
             'font-family',
             'font-size',
             'font-weight',
             'text-anchor',
            // Link attributes.
             'href',
             'xlink:href',
             'target',
            // Other common attributes.
             'cx',
             'cy',
             'r',
             'rx',
             'ry',
             'x1',
             'y1',
             'x2',
             'y2',
             'points',
             'd',
             'offset',
             'stop-color',
             'stop-opacity',
             'xmlns',
             'xmlns:xlink',
            ]
		);
	}

	/**
	 * Sanitize and validate SVG using DOMDocument.
	 *
	 * @param string $svg SVG markup.
	 *
	 * @return string|false Sanitized SVG or false on failure.
	 */
	private function sanitizeAndValidateSvg(string $svg)
	{
		$svg = trim($svg);

		if ($svg === '') {
			  $this->setError(self::ERROR_EMPTY, 'Empty SVG content.');
			  return false;
		}

		// Must start with <svg.
		if (! preg_match('/^<\s*svg\b/i', $svg)) {
			$this->setError(self::ERROR_INVALID_ROOT, 'SVG must start with an &lt;svg&gt; tag.');
			return false;
		}

		// Set up DOMDocument with XXE protection.
		$this->xmlDocument                      = new \DOMDocument();
		$this->xmlDocument->preserveWhiteSpace  = false;
		$this->xmlDocument->strictErrorChecking = false;

		// Disable entity loader for older libxml (XXE protection).
		$xmlLoaderValue = null;
		if (\LIBXML_VERSION < 20900 && function_exists('libxml_disable_entity_loader')) {
			// phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
			$xmlLoaderValue = libxml_disable_entity_loader(true);
		}

		// Enable internal error handling.
		$useErrors = libxml_use_internal_errors(true);
		libxml_clear_errors();

		// Try to load XML (LIBXML_NONET prevents network access).
		$previousErrorHandler = set_error_handler(
            function () {
                  return true; // Suppress errors.
            }
		);
		$loaded               = $this->xmlDocument->loadXML($svg, LIBXML_NONET);
		restore_error_handler();

		// Get XML errors.
		$xmlErrors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors($useErrors);

		// Restore entity loader.
		if ($xmlLoaderValue !== null && function_exists('libxml_disable_entity_loader')) {
			  // phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated
			  libxml_disable_entity_loader($xmlLoaderValue);
		}

		// If XML parsing failed.
		if (! $loaded || ! empty($xmlErrors)) {
			// Get technical error message.
			$technicalError = ! empty($xmlErrors) ? trim($xmlErrors[0]->message) : 'Invalid XML structure.';

			// Map technical errors to user-friendly messages.
			$userMessage = $this->mapXmlErrorToUserMessage($technicalError);

			// Set error with code and optional debug details.
			$this->setError(
                self::ERROR_XML_PARSE,
                $userMessage,
                $technicalError // Include technical details for debug mode.
			);
			return false;
		}

		// Get root element.
		$root = $this->xmlDocument->documentElement;
		if (! $root || strtolower($root->tagName) !== 'svg') {
			$this->setError(self::ERROR_INVALID_ROOT, 'Invalid SVG: root element must be &lt;svg&gt;.');
			return false;
		}

		// Clean the DOM structure.
		$this->cleanDomTree($root, 0);

		// Check for remaining use elements with nesting issues.
		if ($this->checkUseNesting($root, 0) === false) {
			$this->setError(self::ERROR_NESTING_DEEP, 'SVG contains excessive nesting in &lt;use&gt; elements.');
			return false;
		}

		// Save cleaned XML.
		$cleaned           = $this->xmlDocument->saveXML($root);
		$this->xmlDocument = null; // Clean up.

		return $cleaned;
	}

	/**
	 * Clean DOM tree recursively, removing non-whitelisted elements and attributes.
	 *
	 * @param \DOMElement $element Current element.
	 * @param int         $depth   Current nesting depth.
	 *
	 * @return void
	 */
	private function cleanDomTree(\DOMElement $element, int $depth): void
	{
		// Prevent excessive nesting.
		if ($depth > 100) {
			  $element->parentNode->removeChild($element);
			  return;
		}

		// Handle CDATA sections (convert to text nodes).
		$cdataNodes = [];
		foreach ($element->childNodes as $child) {
			if ($child instanceof \DOMCdataSection) {
				   $cdataNodes[] = $child;
			}
		}
		foreach ($cdataNodes as $cdataNode) {
			$textNode = $this->xmlDocument->createTextNode($cdataNode->nodeValue);
			$element->replaceChild($textNode, $cdataNode);
		}

		// Check if tag is allowed.
		$tagName = strtolower($element->tagName);
		if (! in_array($tagName, $this->allowedTags, true)) {
			// Special handling: check if it's a dangerous tag that should be removed.
			$dangerousTags = [
                              'script',
                              'foreignobject',
                              'iframe',
                              'object',
                              'embed',
                              'link',
                              'style',
                             ];
			if (in_array($tagName, $dangerousTags, true)) {
				  $element->parentNode->removeChild($element);
				  return;
			}
			// Remove unknown tags.
			$element->parentNode->removeChild($element);
			return;
		}

		// Clean attributes.
		$attributesToRemove = [];
		foreach ($element->attributes as $attr) {
			$attrName         = strtolower($attr->nodeName);
			$attrNameNoPrefix = strtolower($attr->localName ?? $attrName);

			// Remove event handlers (onclick, onload, etc.).
			if (strpos($attrName, 'on') === 0 && ctype_alpha(substr($attrName, 2, 1))) {
				   $attributesToRemove[] = $attr->nodeName;
				   // Note: Event handlers are already caught during XML parsing,
				   // but we still remove them here if XML parsing succeeded.
				   continue;
			}

		  	// Check if attribute is allowed.
			$isAllowed = in_array($attrName, $this->allowedAttributes, true) ||
			in_array($attrNameNoPrefix, $this->allowedAttributes, true) ||
			strpos($attrName, 'aria-') === 0 ||
			strpos($attrName, 'data-') === 0;

			if (! $isAllowed) {
				$attributesToRemove[] = $attr->nodeName;
				continue;
			}

		  	// Validate href attributes.
			if (strpos($attrName, 'href') !== false) {
				if (! $this->isHrefSafe($attr->value)) {
					   $attributesToRemove[] = $attr->nodeName;
				}
			}

		  	// Reject javascript: and data:text/html in any attribute.
			if (preg_match('/(?:javascript:|data:text\/html|vbscript:)/i', $attr->value)) {
				$attributesToRemove[] = $attr->nodeName;
			}
		}

		// Remove disallowed attributes.
		foreach ($attributesToRemove as $attrName) {
			$element->removeAttribute($attrName);
		}

		// Recursively clean child elements.
		$children = [];
		foreach ($element->childNodes as $child) {
			if ($child instanceof \DOMElement) {
				   $children[] = $child;
			}
		}

		foreach ($children as $child) {
			$this->cleanDomTree($child, $depth + 1);
		}
	}

	/**
	 * Check if href value is safe.
	 *
	 * @param string $href Href value.
	 *
	 * @return bool True if safe, false otherwise.
	 */
	private function isHrefSafe(string $href): bool
	{
		if (empty($href)) {
			  return true;
		}

		// Allow fragment identifiers.
		if (substr($href, 0, 1) === '#') {
			return true;
		}

		// Allow relative URIs.
		if (substr($href, 0, 1) === '/') {
			return true;
		}

		// Allow HTTPS/HTTP domains.
		if (substr($href, 0, 8) === 'https://' || substr($href, 0, 7) === 'http://') {
			return true;
		}

		// Allow only safe image data URIs.
		$safeDataUris = [
                         'data:image/png',
                         'data:image/gif',
                         'data:image/jpg',
                         'data:image/jpeg',
                         'data:image/svg+xml',
                        ];
		foreach ($safeDataUris as $safeUri) {
			if (substr($href, 0, strlen($safeUri)) === $safeUri) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check use element nesting depth.
	 *
	 * @param \DOMElement $element Current element.
	 * @param int         $depth   Current nesting depth.
	 *
	 * @return bool True if safe, false if nesting too deep.
	 */
	private function checkUseNesting(\DOMElement $element, int $depth): bool
	{
		if ($depth > $this->useNestingLimit) {
			  return false;
		}

		if (strtolower($element->tagName) === 'use') {
			// Check href/xlink:href.
			$href = $element->getAttribute('href') ?: $element->getAttributeNS('http://www.w3.org/1999/xlink', 'href');

			if ($href && substr($href, 0, 1) === '#') {
				   // Recursively check referenced elements.
				   $referencedId = substr($href, 1);
				   $referenced   = $this->xmlDocument->getElementById($referencedId);
				if ($referenced && $referenced instanceof \DOMElement) {
					return $this->checkUseNesting($referenced, $depth + 1);
				}
			}
		}

		// Check children.
		foreach ($element->childNodes as $child) {
			if ($child instanceof \DOMElement) {
				if (! $this->checkUseNesting($child, $depth)) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Map technical XML parser errors to user-friendly messages.
	 *
	 * @param string $technicalError Raw XML parser error message.
	 *
	 * @return string User-friendly error message.
	 */
	private function mapXmlErrorToUserMessage(string $technicalError): string
	{
		$technicalLower = strtolower($technicalError);

		// Map common technical errors to user-friendly messages.
		$errorMappings = [
							// Event handler errors.
                          '/attribute\s+on\w+/i'                                       => 'SVG contains invalid event handler attributes.',
                          '/specification\s+mandates\s+value\s+for\s+attribute\s+on/i' => 'SVG contains invalid event handler attributes.',

							// Tag mismatch errors.
                          '/opening\s+and\s+ending\s+tag\s+mismatch/i'                 => 'SVG has mismatched tags.',
                          '/end\s+tag\s+name/i'                                        => 'SVG has mismatched tags.',

							// Syntax errors.
                          '/syntax\s+error/i'                                          => 'SVG has syntax errors.',
                          '/not\s+well-formed/i'                                       => 'SVG is not well-formed.',
                          '/unclosed\s+token/i'                                        => 'SVG has unclosed tags.',

							// Premature/incomplete errors.
                          '/premature\s+end\s+of\s+data/i'                             => 'SVG is incomplete or corrupted.',
                          '/unexpected\s+end\s+of\s+data/i'                            => 'SVG is incomplete or corrupted.',
                          '/unexpected\s+end\s+tag/i'                                  => 'SVG is incomplete or corrupted.',

							// Attribute errors.
                          '/attribute\s+value\s+not\s+terminated/i'                    => 'SVG has invalid attribute values.',
                          '/required\s+attribute\s+missing/i'                          => 'SVG is missing required attributes.',
                         ];

		// Check each mapping pattern.
		foreach ($errorMappings as $pattern => $userMessage) {
			if (preg_match($pattern, $technicalError)) {
				   return $userMessage;
			}
		}

		// Fallback for unknown XML errors.
		return 'Could not parse SVG.';
	}
}
