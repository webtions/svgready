<?php
/**
 * SVG Ready – Index
 *
 * Entry point for the SVG Ready web app.
 *
 * @category WebApp
 * @package  SVGready
 * @author   Harish Chouhan <mail@webtions.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @version  1.0.0
 * @link     https://svgready.com
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/classes/SVGReady.php';
require_once __DIR__ . '/inc/classes/View.php';

use SVGReady\Core;
use SVGReady\View;

// Core bootstrap.
Core::checkMaintenance();
Core::check404();
Core::initEnvironment();

// Handle request and prepare context.
$context = Core::handleRequest();

// Header.
require __DIR__ . '/inc/templates/header.php';
?>

<section class="intro">
	<h2>Convert SVG to CSS Data URI</h2>
	<p>Instantly convert SVG markup into percent-encoded or base64 Data URIs for cleaner, faster CSS.</p>
</section>

<main class="wrap">
	<div class="main-layout">
		<?php
		View::render('form', $context);
		View::render('output', $context);
		?>
	</div>
</main>

<?php
// Footer.
require __DIR__ . '/inc/templates/footer.php';
