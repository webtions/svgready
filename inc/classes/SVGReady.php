<?php
/**
 * Core app bootstrap for SVG Ready
 *
 * Handles shared setup like maintenance checks, 404 validation,
 * and basic environment preparation.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SVGReady;

use SVGReady\SVGConverter;
use SVGReady\Logger;

class Core
{
	/**
	 * Checks if the site is in maintenance mode.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function checkMaintenance(): void
	{
		if (file_exists(__DIR__ . '/../../.maintenance')) {
			require __DIR__ . '/../templates/maintenance.php';
			exit;
		}
	}

	/**
	 * Serves a 404 page if the current request targets a missing path.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function check404(): void
	{
		$uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
		$path = __DIR__ . '/../' . ltrim((string) $uri, '/');

		if (
			$uri !== '/'
			&& ! file_exists($path)
			&& ! is_dir(__DIR__ . '/../' . explode('/', trim((string) $uri, '/'))[0])
		) {
			http_response_code(404);
			include __DIR__ . '/../templates/404.html';
			exit;
		}
	}

	/**
	 * Loads all required files and initializes the environment.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function initEnvironment(): void
	{
		require_once __DIR__ . '/../functions/functions.php';
		require_once __DIR__ . '/Logger.php';
		require_once __DIR__ . '/SVGConverter.php';

		// Set up global error handler for PHP errors and warnings.
		set_error_handler(
			function (int $errno, string $errstr, string $errfile, int $errline): bool {
				// Map error numbers to error types.
				$errorTypes = [
                               E_ERROR             => 'ERROR',
                               E_WARNING           => 'WARNING',
                               E_PARSE             => 'PARSE',
                               E_NOTICE            => 'NOTICE',
                               E_CORE_ERROR        => 'CORE_ERROR',
                               E_CORE_WARNING      => 'CORE_WARNING',
                               E_COMPILE_ERROR     => 'COMPILE_ERROR',
                               E_COMPILE_WARNING   => 'COMPILE_WARNING',
                               E_USER_ERROR        => 'USER_ERROR',
                               E_USER_WARNING      => 'USER_WARNING',
                               E_USER_NOTICE       => 'USER_NOTICE',
                               E_STRICT            => 'STRICT',
                               E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
                               E_DEPRECATED        => 'DEPRECATED',
                               E_USER_DEPRECATED   => 'USER_DEPRECATED',
                              ];

				$errorType = $errorTypes[$errno] ?? 'UNKNOWN';

				Logger::log(
					sprintf('%s in %s:%d', $errstr, $errfile, $errline),
					$errorType,
					['error_number' => $errno]
				);

				// Return false to continue with normal error handling.
				return false;
			}
		);

		// Set up exception handler for uncaught exceptions.
		set_exception_handler(
			function (\Throwable $exception): void {
				Logger::exception($exception, ['uncaught' => true]);
			}
		);
	}

	/**
	 * Handles POST request processing and returns the full context array.
	 *
	 * @since  1.0.0
	 * @return array<string,mixed>
	 */
	public static function handleRequest(): array
	{
		$errorMessage = '';
		$errorTitle   = '';
		$results      = [];
		$inputSvg     = $_POST['svg'] ?? '';
		$converter    = null;

		// Checkbox flags from the form.
		$stripRootWh    = isset($_POST['stripWh']);
		$stripRootClass = isset($_POST['stripClass']);
		$showBase64     = isset($_POST['showBase64']);

		// Centralised error messages.
		$errors = include __DIR__ . '/../functions/errors.php';

		if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
			try {
				// Pass the form data and boolean flags to the converter.
				$converter = new SVGConverter([
                                               'svg'        => $inputSvg,
                                               'stripWh'    => $stripRootWh,
                                               'stripClass' => $stripRootClass,
                                               'showBase64' => $showBase64,
                                              ]);

				$converter->process();

				$results   = $converter->getResults();
				$errorCode = $converter->getErrorCode();
				$errorMsg  = $converter->getError();


				if ($errorCode !== null) {
					switch ($errorCode) {
						case SVGConverter::ERROR_TOO_LARGE:
							$key = 'too_large';
							break;
						case SVGConverter::ERROR_EMPTY:
							$key = 'empty_input';
							break;
						case SVGConverter::ERROR_INVALID_ROOT:
						case SVGConverter::ERROR_MALFORMED_XML:
						case SVGConverter::ERROR_INVALID_ATTRIBUTE:
						case SVGConverter::ERROR_NESTING_DEEP:
						case SVGConverter::ERROR_XML_PARSE:
							$key = 'invalid_svg';
							break;
						default:
							$key = 'server_error';
							break;
					}

					$errorTitle   = $errors[$key]['title'] ?? '';
					$errorMessage = $errors[$key]['text'] ?? '';
				} elseif (! empty($errorMsg)) {
					$errorTitle   = $errors['server_error']['title'] ?? '';
					$errorMessage = $errorMsg;
				}

				// Compute size/percent differences if valid.
				if (! empty($inputSvg) && ! empty($results['normalized'])) {
					$inputSize  = strlen($inputSvg);
					$outputSize = strlen((string) $results['normalized']);
					$diff       = $inputSize - $outputSize;

					$results['input_kb']  = round($inputSize / 1024, 2);
					$results['output_kb'] = round($outputSize / 1024, 2);
					$results['percent']   = ($inputSize > 0) ? (int) round(($diff / $inputSize) * 100) : 0;
				}
			} catch (\Throwable $e) {
				// Log the exception to debug.log.
				Logger::exception($e, [
                                       'input_svg_length' => strlen($inputSvg),
                                       'request_method'   => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                                      ]);

				$errorTitle   = $errors['server_error']['title'] ?? '';
				$errorMessage = $errors['server_error']['text'] ?? '';
			}
		}

		// Return the complete context for all templates.
		return [
                'errorMessage'   => $errorMessage,
                'errorTitle'     => $errorTitle,
                'results'        => $results,
                'inputSvg'       => $inputSvg,
                'showBase64'     => $showBase64,
                'stripRootWh'    => $stripRootWh,
                'stripRootClass' => $stripRootClass,
                'isAjax'         => false,
                'converter'      => $converter,
               ];
	}
}
