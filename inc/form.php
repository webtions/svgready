<section class="input-section" aria-labelledby="input-heading">
    <h3 id="input-heading" class="sr-only">Input SVG</h3>
    <form method="post" aria-describedby="form-instructions">
        <label for="svg" class="label">Paste raw SVG markup</label>
        <textarea id="svg" name="svg" placeholder="<svg ...>..." aria-describedby="form-instructions"><?php echo isset($_POST['svg']) ? h($_POST['svg']) : ''; ?></textarea>

        <fieldset class="opts">
            <legend class="sr-only">Conversion options</legend>
            <label><input type="checkbox" name="strip_wh" <?php echo $strip_root_wh ? 'checked' : ''; ?>> Strip width/height on root &lt;svg&gt;</label>
            <label><input type="checkbox" name="strip_class" <?php echo $strip_root_class ? 'checked' : ''; ?>> Remove class on root &lt;svg&gt;</label>
            <label><input type="checkbox" name="show_base64" <?php echo $show_base64 ? 'checked' : ''; ?>> Also show base64</label>
        </fieldset>

        <button class="btn" type="submit">Convert</button>

        <?php if (!empty($input_svg) && !empty($normalized)) :
            $input_size = strlen($input_svg);
            $output_size = strlen($normalized);
            $diff = $input_size - $output_size;
            $percent = $input_size > 0 ? round(($diff / $input_size) * 100) : 0;
            ?>
            <p class="optimize-result">
                <span class="label">Optimized:</span>
                <strong><?php echo round($input_size / 1024, 2); ?> KB</strong>
                →
                <strong><?php echo round($output_size / 1024, 2); ?> KB</strong>
                <span class="change <?php echo $percent > 0 ? 'reduced' : ''; ?>">(<?php echo $percent >= 0 ? '-' . $percent : '+' . abs($percent); ?>%)</span>
            </p>
        <?php endif; ?>
    </form>
</section>
