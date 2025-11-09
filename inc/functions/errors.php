<?php
/**
 * SVG Ready – Error Messages
 *
 * Centralised list of error titles and descriptions
 * used across converters and templates.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

return [
        'empty_input'  => [
                           'title' => 'Nothing to convert',
                           'text'  => 'Please paste your SVG markup first.',
                          ],
        'too_large'    => [
                           'title' => 'File too large',
                           'text'  => 'SVG exceeds the 250 KB size limit.',
                          ],
        'invalid_svg'  => [
                           'title' => 'Invalid SVG',
                           'text'  => 'The SVG markup contains unsafe or unsupported elements.',
                          ],
        'server_error' => [
                           'title' => 'Something went wrong',
                           'text'  => 'Unexpected server error. Please try again.',
                          ],
       ];
