<?php
/**
 * SVG Ready – Ads Template
 *
 * Displays ad placeholders or injected ad content within the layout.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);


// Define ads with base links (no ?ref=).
$ads = [
		[
		 'link' => 'https://r.freemius.com/14609/7846652/',
		 'text' => 'Shorten & track links the smart way — ClickWhale for WordPress.',
		 'slug' => 'clickwhale',
		],
		[
		 'link' => 'https://kokoanalytics.com/',
		 'text' => 'Switch to privacy-first site stats — Koko Analytics for WordPress.',
		 'slug' => 'kokoanalytics',
		],
	   ];

// Shuffle and pick one ad.
shuffle($ads);
$ad = $ads[0];

// Append standard UTM parameters for outbound tracking.
$utmParams = http_build_query(
	[
	 'utm_source'   => 'svgready',
	 'utm_medium'   => 'referral',
	 'utm_campaign' => 'footer_ads',
	 'utm_content'  => $ad['slug'],
	]
);

$link = rtrim($ad['link'], '/') . '/?' . $utmParams;

?>
<div class="ad-banner">
	<a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>"
	   target="_blank" rel="noopener">
		<?php echo htmlspecialchars($ad['text'], ENT_QUOTES, 'UTF-8'); ?>
	</a>
</div>
