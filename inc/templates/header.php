<?php
/**
 * SVG Ready – Header Template
 *
 * Contains the opening markup, metadata, and page structure setup.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>SVG Ready – Convert SVG to CSS Data URI | Webtions</title>

	<meta name="description" content="Convert SVG to CSS Data URI instantly. Optimize SVG markup into percent-encoded or base64 Data URIs for cleaner, faster CSS. Free SVG converter tool.">
	<meta name="keywords" content="SVG converter, CSS Data URI, SVG to base64, SVG optimization, SVG to CSS, Data URI generator">
	<meta property="og:title" content="SVG Ready - Convert SVG to CSS Data URI">
	<meta property="og:description" content="Instantly convert SVG markup into percent-encoded or base64 Data URIs for cleaner, faster CSS.">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, user-scalable=yes, viewport-fit=cover">

	<link rel="mask-icon" href="favicon.svg" color="#0000ee">
	<link rel="icon" href="favicon-dark.svg" media="(prefers-color-scheme: dark)">
	<link rel="icon" href="favicon.svg" media="(prefers-color-scheme: light)">
	<link rel="apple-touch-icon" href="favicon.svg">
	<meta name="msapplication-TileImage" content="favicon.svg">

	<link rel="preload" href="assets/fonts/Recursive-Basic.woff2" as="font" type="font/woff2" crossorigin>
	<link rel="stylesheet" href="<?php echo \SVGReady\Core::asset('assets/style.css'); ?>">

	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "WebApplication",
		"name": "SVG Ready",
		"description": "Convert SVG to CSS Data URI instantly",
		"applicationCategory": "DeveloperApplication"
	}
	</script>
</head>

<body>
<header class="site-header">
	<h1 class="site-title">
		<a href="/" aria-label="SVG Ready Home">SVG <span class="word">ready</span></a>
	</h1>

	<nav class="site-nav" aria-label="Main navigation">
		<ul>
			<li>
				<a href="https://github.com/webtions/svgready" target="_blank" rel="noopener noreferrer" aria-label="Contribute on GitHub">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-github"><path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/></svg>
					<span class="sr-only">Contribute</span>
				</a>
			</li>
			<li>
				<a href="https://github.com/webtions/svgready/issues" target="_blank" rel="noopener noreferrer" aria-label="Report an issue">
					<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3l-8.47-14.14a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12" y2="17"></line></svg>
					<span class="sr-only">Report issue</span>
				</a>
			</li>
			<li>
				<button id="theme-toggle" aria-label="Toggle dark mode">
					<svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
					<svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>

				</button>
			</li>
		</ul>
	</nav>
</header>
