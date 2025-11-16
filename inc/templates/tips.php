<?php
/**
 * SVG Ready – Tips Template
 *
 * Displays SVG tips one at a time in the empty state.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

// Define SVG tips.
$tips = [
		 [
          'title' => 'Use CSS variables for dynamic colors:',
          'text'  => 'You can use CSS custom properties (variables) inside SVG by setting fill="var(--icon-color)" or stroke="var(--border-color)". This allows you to change SVG colors dynamically with CSS without modifying the SVG markup.',
         ],
		 [
          'title' => 'Inline SVG vs. external files:',
          'text'  => 'Inline SVG in HTML gives you CSS control and better performance for small icons, while external SVG files are better for reusable graphics and caching.',
         ],
		 [
          'title' => 'Remove unnecessary whitespace:',
          'text'  => 'Clean up extra spaces, tabs, and line breaks in your SVG code. This reduces file size and can improve rendering performance, especially for inline SVGs.',
         ],
		 [
          'title' => 'Always include a viewBox:',
          'text'  => 'The viewBox attribute (e.g., viewBox="0 0 24 24") makes your SVG scalable and responsive. Without it, SVGs may not scale properly when resized.',
         ],
		 [
          'title' => 'Group related elements:',
          'text'  => 'Use <g> tags to group related SVG elements. This allows you to apply transforms, styles, and animations to multiple elements at once, keeping your code organized and maintainable.',
         ],
        ];

// Shuffle and pick one tip.
shuffle($tips);
$tip = $tips[0];

?>
<div class="svg-tip">
	<span class="tip-icon" aria-hidden="true"></span>
	<p><strong><?php echo svgreadyEscapeHtml($tip['title']); ?></strong> <?php echo svgreadyEscapeHtml($tip['text']); ?></p>
</div>

