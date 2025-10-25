<?php
/**
 * SVG Ready AJAX handler
 *
 * Receives POST data, processes SVG, and returns JSON.
 *
 * @package SVGready
 * @since   1.0.0
 */

require_once __DIR__ . '/inc/SvgConverter.php';
require_once __DIR__ . '/inc/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

$response = array(
    'error'   => null,
    'results' => array(),
);

try {
    $request = $_POST;

    $converter = new SvgConverter($request);
    $converter->process();

    if ($error = $converter->get_error()) {
        $response['error'] = $error;
    } else {
        $results = $converter->get_results();
        $response['results'] = array(
        'normalized'    => $results['normalized'] ?? '',
        'data_uri_css'  => $results['data_uri_css'] ?? '',
        'bg_snippet'    => $results['bg_snippet'] ?? '',
        'mask_snippet'  => $results['mask_snippet'] ?? '',
        'data_uri_b64'  => $results['data_uri_b64'] ?? '',
        'show_base64'   => !empty($request['show_base64']),
        'preview'       => $converter->sanitize_svg($results['normalized'] ?? ''),
        );
    }
} catch (Throwable $e) {
    $response['error'] = 'Unexpected server error. Please try again.';
}

echo json_encode($response);
exit;
