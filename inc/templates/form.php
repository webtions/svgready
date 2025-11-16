<?php
/**
 * SVG Ready – Input Form
 *
 * Displays the SVG input area and conversion options.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);
?>

<section class="input-section" aria-labelledby="input-heading">
	<h3 id="input-heading" class="sr-only">Input SVG</h3>

	<form method="post" aria-describedby="form-instructions">
		<label for="svg" class="label">Paste raw SVG markup or <span class="upload-link">upload your SVG file</span></label>
		<input type="file" id="svgFile" accept=".svg,image/svg+xml" style="display:none;">
		<textarea
			id="svg"
			name="svg"
			placeholder="<svg ...>..."
			aria-describedby="form-instructions"
		><?php echo isset($_POST['svg']) ? svgreadyEscapeHtml($_POST['svg']) : ''; ?></textarea>

		<fieldset class="opts">
			<legend class="sr-only">Conversion options</legend>

			<label>
				<input type="checkbox" name="stripWh" <?php echo ! empty($stripRootWh) ? 'checked' : ''; ?>>
				Strip width/height on root &lt;svg&gt;
			</label>

			<label>
				<input type="checkbox" name="stripClass" <?php echo ! empty($stripRootClass) ? 'checked' : ''; ?>>
				Remove class on root &lt;svg&gt;
			</label>

			<label>
				<input type="checkbox" name="showBase64" <?php echo ! empty($showBase64) ? 'checked' : ''; ?>>
				Also show base64
			</label>
		</fieldset>

		<button class="btn" type="submit">Convert</button>
	</form>
</section>
