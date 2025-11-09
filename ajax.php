<?php
/**
 * SVG Ready – AJAX Handler
 *
 * Receives POST data, processes SVG, and returns HTML (not JSON)
 * for the live preview panel.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

use SVGReady\Core;
use SVGReady\View;

require_once __DIR__ . '/inc/classes/SVGReady.php';
require_once __DIR__ . '/inc/classes/View.php';

// Environment setup.
Core::initEnvironment();

// Prepare context.
$context           = Core::handleRequest();
$context['isAjax'] = true;

// Return HTML fragment.
header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');

View::render('output', $context);
exit;
