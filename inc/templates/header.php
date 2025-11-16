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

	<script>
	(() => {
		const saved = localStorage.getItem('theme');
		const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
		if (saved === 'dark' || (!saved && systemDark)) {
			document.documentElement.classList.add('dark');
		}
	})();
	</script>
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
		<a href="/" aria-label="SVG Ready">
			<svg viewBox="0 0 328 100" aria-hidden="true" class="site-logo" xmlns="http://www.w3.org/2000/svg" >
				<!-- S (accent / primary) -->
				<path d="M90.125 0H49.829c-13.804 0-26.309 5.596-35.355 14.646C6.392 22.73 1.058 33.567.017 45.633c-.1 1.163.254 2.204 1.042 3.063C1.85 49.558 2.854 50 4.021 50h45.808L92.967 6.863c1.175-1.175 1.508-2.846.87-4.38C93.204.946 91.783 0 90.125 0Z" fill="var(--color-accent)"/>
				<path d="M9.534 100h40.296c13.808 0 26.308-5.595 35.354-14.645 8.084-8.083 13.413-18.921 14.458-30.988.1-1.163-.254-2.205-1.042-3.063-.787-.863-1.792-1.305-2.958-1.305H49.83L6.693 93.138C5.518 94.313 5.184 95.984 5.818 97.521 6.455 99.055 7.872 100 9.534 100Z" fill="var(--color-primary)"/>

				<!-- V (accent / primary) -->
				<path d="M113.66 47.838 160.818 98.732c1.567 1.691 4.121 1.691 5.688 0L213.66 47.838V4.354c0-1.794-.946-3.323-2.479-4.011-1.538-.683-3.208-.328-4.383.941l-43.138 46.555h-50Z" fill="var(--color-accent)"/>
				<path d="M113.66 4.352V47.836h50L138.66 20.859 128.306 9.68l-7.783-8.4c-1.175-1.263-2.846-1.623-4.38-.935C114.606 1.029 113.66 2.562 113.66 4.352Z" fill="var(--color-primary)"/>

				<!-- G (primary / accent) -->
				<path d="M277.66 100c26.142 0 47.6-20.062 49.813-45.633.1-1.163-.25-2.205-1.042-3.063-.788-.862-1.796-1.304-2.962-1.304H227.66c0 27.617 22.388 50 50 50Z" fill="var(--color-primary)"/>
				<path d="M317.956 0H277.66c-13.804 0-26.308 5.596-35.354 14.646C233.256 23.692 227.66 36.192 227.66 50h50l43.138-43.138c1.175-1.175 1.508-2.846.87-4.38C321.035.946 319.618 0 317.956 0Z" fill="var(--color-accent)"/>
			</svg>
			<span class="word">ready</span>
		</a>
	</h1>

	<nav class="site-nav" aria-label="Main navigation">
		<ul>
			<li>
				<a href="mailto:mail@webtions.com?subject=SVG%20Ready%20Feedback" aria-label="Send feedback">
					<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7"/><rect x="2" y="4" width="20" height="16" rx="2"/></svg>
					<span class="sr-only">Send feedback</span>
				</a>
			</li>
			<li>
				<button id="theme-toggle" aria-label="Toggle dark mode">
					<svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
					<svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="var(--color-primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
				</button>
			</li>
		</ul>
	</nav>
</header>
