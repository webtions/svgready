<?php
/**
 * SVG Ready – Output Section
 *
 * @package SVGready
 * @since   1.0.0
 */
?>

<section class="output-section <?php echo ! empty($error_message) ? 'has-error' : ''; ?>" aria-labelledby="output-heading">
    <h3 id="output-heading" class="sr-only">Conversion Output</h3>

    <?php if (! empty($error_message) ) : ?>
        <div class="error-state" role="alert">
            <span class="error-icon" aria-hidden="true"></span>
            <h3>Something went wrong</h3>
            <p><?php echo svgready_escape_html($error_message); ?></p>
        </div>

    <?php elseif ($input_svg !== '' ) : ?>

        <article class="result-block">
            <h4>SVG Preview</h4>
            <div class="preview-container" role="img" aria-label="SVG Preview">
        <?php
        if (isset($converter) && $normalized !== '' ) {
            echo $converter->sanitize_svg($normalized);
        }
        ?>
            </div>
        </article>

        <article class="result-block">
            <h4>
                Normalized SVG
                <button class="copy-btn" data-copy="<?php echo svgready_escape_js($normalized); ?>">Copy</button>
            </h4>
            <pre><code id="normalized-svg"><?php echo svgready_escape_html($normalized); ?></code></pre>
        </article>

        <article class="result-block">
            <h4>
                Percent-encoded Data URI
                <button class="copy-btn" data-copy="<?php echo svgready_escape_js($data_uri_css); ?>">Copy</button>
            </h4>
            <pre><code id="data-uri"><?php echo svgready_escape_html($data_uri_css); ?></code></pre>
            <p class="small">Use this (quoted) in CSS: <code>url("&hellip;")</code></p>
        </article>

        <article class="result-block">
            <h4>
                Background Image CSS
                <button class="copy-btn" data-copy="<?php echo svgready_escape_js($bg_snippet); ?>">Copy</button>
            </h4>
            <pre><code id="bg-snippet"><?php echo svgready_escape_html($bg_snippet); ?></code></pre>
        </article>

        <article class="result-block">
            <h4>
                Mask Image CSS
                <button class="copy-btn" data-copy="<?php echo svgready_escape_js($mask_snippet); ?>">Copy</button>
            </h4>
            <pre><code id="mask-snippet"><?php echo svgready_escape_html($mask_snippet); ?></code></pre>
        </article>

        <?php if ($show_base64 ) : ?>
            <article class="result-block">
                <h4>
                    Base64 Data URI
                    <button class="copy-btn" data-copy="<?php echo svgready_escape_js($data_uri_b64); ?>">Copy</button>
                </h4>
                <pre><code id="base64-uri"><?php echo svgready_escape_html($data_uri_b64); ?></code></pre>
            </article>
        <?php endif; ?>

    <?php else : ?>
        <div class="empty-state" role="status">
            <h3>Ready to convert your SVG?</h3>
            <p>Paste your SVG markup in the form and click <strong>Convert</strong> to begin.</p>
        </div>
    <?php endif; ?>
</section>
