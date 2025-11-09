<?php
/**
 * SVG Ready – Maintenance Template
 *
 * Displayed when a .maintenance file is present.
 * Sends a 503 response with a Retry-After header.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

http_response_code(503);
header('Retry-After: 3600');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Maintenance</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		body {
			font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
			background: #fafafa;
			color: #333;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			height: 100vh;
			text-align: center;
			margin: 0;
		}
		h1 {
			font-size: 1.75rem;
			margin-bottom: 0.5rem;
		}
		p {
			color: #666;
			font-size: 1rem;
			max-width: 320px;
		}
	</style>
</head>
<body>
	<h1>We'll be right back</h1>
	<p>SVG Ready is currently undergoing maintenance. Please check back soon.</p>
</body>
</html>
