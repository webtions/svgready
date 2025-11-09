<?php
/**
 * SVG Ready – View Renderer
 *
 * Handles rendering of templates by injecting context
 * variables safely and ensuring defaults are defined.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SVGReady;

use SVGReady\Logger;

/**
 * Simple View Renderer for SVG Ready templates.
 *
 * @since 1.0.0
 */
class View
{
	/**
	 * Renders a template with the given context.
	 *
	 * @param string               $template Template name (without .php).
	 * @param array<string, mixed> $context  Data to pass into the template.
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public static function render(string $template, array $context = []): void
	{
		$templatePath = __DIR__ . '/../templates/' . $template . '.php';

		if (! file_exists($templatePath)) {
			// Log the template error.
			Logger::error('Template not found', [
				'template'     => $template,
				'template_path' => $templatePath,
			]);

			http_response_code(500);
			echo '<p>Template not found: ' . htmlspecialchars($template, ENT_QUOTES, 'UTF-8') . '</p>';
			return;
		}

		// Hard guarantees for template variables.
		$defaults = [
					 'errorMessage' => '',
					 'errorTitle'   => '',
					 'results'      => [],
					 'inputSvg'     => '',
					 'showBase64'   => false,
					 'isAjax'       => false,
					 'converter'    => null,
					];

		// Merge provided context over defaults.
		$ctx = array_merge($defaults, $context);

		// Make context variables available in template scope.
		extract($ctx, EXTR_SKIP);

		require $templatePath;
	}
}
