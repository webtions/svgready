<?php
/**
 * SVG Ready – Output Template (View)
 *
 * Displays conversion results, error states, or empty state.
 * Expected context variables are injected by View::render().
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

/*
 * Expected context keys:
 * - $errorMessage (string)
 * - $errorTitle (string)
 * - $results (array<string,mixed>)
 * - $inputSvg (string)
 * - $showBase64 (bool)
 * - $isAjax (bool)
 * - $converter (object|null)
 */

// Explicit defaults to satisfy static analysis.
$errorTitle = $errorTitle ?? '';
?>

<?php if (empty($isAjax)) : ?>
<section class="output-section <?php echo ! empty($errorMessage) ? 'has-error' : ''; ?>" aria-labelledby="output-heading">
	<h3 id="output-heading" class="sr-only">Conversion Output</h3>
<?php endif; ?>

<?php if (! empty($errorMessage)) : ?>
	<div class="error-state" role="alert">
		<span class="error-icon" aria-hidden="true"></span>
		<h3><?php echo svgreadyEscapeHtml($errorTitle ?: 'Something went wrong'); ?></h3>
		<p><?php echo svgreadyEscapeHtml($errorMessage); ?></p>
	</div>

<?php elseif (! empty($inputSvg)) : ?>
	<?php if (! empty($results['normalized'])) : ?>
		<p class="optimize-result">
			<span class="label">Optimized:</span>
			<strong><?php echo svgreadyEscapeHtml((string) ($results['input_kb'] ?? '')); ?> KB</strong>
			→
			<strong><?php echo svgreadyEscapeHtml((string) ($results['output_kb'] ?? '')); ?> KB</strong>
			<span class="change <?php echo ($results['percent'] ?? 0) > 0 ? 'reduced' : ''; ?>">
				(<?php
					$percent = (int) ($results['percent'] ?? 0);
					echo $percent >= 0 ? "-{$percent}" : '+' . abs($percent);
				?>%)
			</span>
		</p>
	<?php endif; ?>

	<article class="result-block">
		<h4>SVG Preview</h4>
		<div class="preview-container" role="img" aria-label="SVG Preview">
			<?php
			if (! empty($results['normalized'])) {
				if (isset($converter) && is_object($converter) && method_exists($converter, 'sanitizeSvg')) {
					echo $converter->sanitizeSvg((string) $results['normalized']);
				} else {
					echo $results['normalized']; // Fallback.
				}
			}
			?>
		</div>
	</article>

	<article class="result-block">
		<h4>
			Normalized SVG
			<button class="copy-btn">Copy</button>
		</h4>
		<pre><code><?php echo svgreadyEscapeHtml((string) ($results['normalized'] ?? '')); ?></code></pre>
	</article>

	<article class="result-block">
		<h4>
			Percent-encoded Data URI
			<button class="copy-btn">Copy</button>
		</h4>
		<pre><code><?php echo svgreadyEscapeHtml((string) ($results['data_uri_css'] ?? '')); ?></code></pre>
		<p class="small">Use this (quoted) in CSS: <code>url("&hellip;")</code></p>
	</article>

	<article class="result-block">
		<h4>
			Background Image CSS
			<button class="copy-btn">Copy</button>
		</h4>
		<pre><code><?php echo svgreadyEscapeHtml((string) ($results['bg_snippet'] ?? '')); ?></code></pre>
	</article>

	<article class="result-block">
		<h4>
			Mask Image CSS
			<button class="copy-btn">Copy</button>
		</h4>
		<pre><code><?php echo svgreadyEscapeHtml((string) ($results['mask_snippet'] ?? '')); ?></code></pre>
	</article>

	<?php if (! empty($showBase64) && ! empty($results['data_uri_b64'])) : ?>
		<article class="result-block">
			<h4>
				Base64 Data URI
				<button class="copy-btn">Copy</button>
			</h4>
			<pre><code><?php echo svgreadyEscapeHtml((string) $results['data_uri_b64']); ?></code></pre>
		</article>
	<?php endif; ?>

	<?php if (! empty($results['benchmark'])) : ?>
		<article class="result-block benchmark">
			<p>
				Page generated in
				<?php echo (float) ($results['benchmark']['time_ms'] ?? 0); ?> ms
				•
				Peak memory
				<?php echo (float) ($results['benchmark']['memory_mb'] ?? 0); ?> MB
			</p>
		</article>
	<?php endif; ?>

<?php else : ?>
	<div class="empty-state" role="status">
		<h3>Ready to convert your SVG?</h3>
		<p>Paste your SVG markup in the form and click <strong>Convert</strong> to begin.</p>
	</div>
<?php endif; ?>

<?php if (empty($isAjax)) : ?>
</section>
<?php endif; ?>
