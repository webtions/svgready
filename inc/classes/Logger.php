<?php
/**
 * SVG Ready – Logger
 *
 * Handles error and debug logging to debug.log file.
 *
 * @package SVGready
 * @since   1.0.0
 */

declare(strict_types=1);

namespace SVGReady;

/**
 * Logger class for writing errors to debug.log file.
 *
 * @since 1.0.0
 */
class Logger
{
	/**
	 * Path to the debug log file.
	 *
	 * @var string
	 */
	private static string $logFile = '';

	/**
	 * Initialize the logger with the log file path.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function init(): void
	{
		if (self::$logFile === '') {
			// Set log file path to root directory (one level up from inc/classes).
			$logPath = dirname(__DIR__, 2) . '/debug.log';

			// Validate path to prevent path traversal attacks.
			$realBasePath = realpath(dirname(__DIR__, 2));
			$realLogPath  = realpath(dirname($logPath));

			// Ensure log file is within the base directory.
			if ($realBasePath === false || $realLogPath === false || strpos($realLogPath, $realBasePath) !== 0) {
				// Fallback to a safe path if validation fails.
				$logPath = dirname(__DIR__, 2) . '/debug.log';
			}

			self::$logFile = $logPath;
		}
	}

	/**
	 * Write a message to the debug log file.
	 *
	 * @param string $message  The message to log.
	 * @param string $level    Log level (ERROR, WARNING, INFO, DEBUG).
	 * @param array  $context  Additional context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function log(string $message, string $level = 'INFO', array $context = []): void
	{
		self::init();

		$timestamp  = date('Y-m-d H:i:s');
		$contextStr = '';

		// Sanitize message to prevent log injection (remove newlines and control characters).
		$sanitizedMessage = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $message);

		// Sanitize level to prevent injection.
		$sanitizedLevel = preg_replace('/[^A-Z0-9_]/', '', strtoupper($level));

		if (! empty($context)) {
			$contextStr = ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
		}

		$logEntry = sprintf(
			"[%s] [%s] %s%s\n",
			$timestamp,
			$sanitizedLevel,
			$sanitizedMessage,
			$contextStr
		);

		// Ensure the directory exists and is writable.
		$logDir = dirname(self::$logFile);
		if (! is_dir($logDir)) {
			@mkdir($logDir, 0700, true);
		}

		// Write to log file (append mode) with restricted permissions.
		$result = @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

		// Set restrictive file permissions (owner read/write only).
		if ($result !== false && file_exists(self::$logFile)) {
			@chmod(self::$logFile, 0600);
		}

		// If writing failed, try to log to PHP error log as fallback.
		if ($result === false) {
			$error = error_get_last();
			error_log(
				sprintf(
					'Logger failed to write to %s: %s. Original message: [%s] %s',
					self::$logFile,
					$error['message'] ?? 'Unknown error',
					$level,
					$message
				)
			);
		}
	}

	/**
	 * Log an error message.
	 *
	 * @param string $message The error message.
	 * @param array  $context Additional context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function error(string $message, array $context = []): void
	{
		self::log($message, 'ERROR', $context);
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message The warning message.
	 * @param array  $context Additional context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function warning(string $message, array $context = []): void
	{
		self::log($message, 'WARNING', $context);
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message The info message.
	 * @param array  $context Additional context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function info(string $message, array $context = []): void
	{
		self::log($message, 'INFO', $context);
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message The debug message.
	 * @param array  $context Additional context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function debug(string $message, array $context = []): void
	{
		self::log($message, 'DEBUG', $context);
	}

	/**
	 * Log an exception with full stack trace.
	 *
	 * @param \Throwable $exception The exception to log.
	 * @param array      $context   Additional context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function exception(\Throwable $exception, array $context = []): void
	{
		self::init();

		$timestamp  = date('Y-m-d H:i:s');
		$contextStr = '';

		// Sanitize exception message to prevent log injection.
		$sanitizedMessage = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $exception->getMessage());
		$sanitizedFile    = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $exception->getFile());
		$sanitizedTrace   = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/', '', $exception->getTraceAsString());

		if (! empty($context)) {
			$contextStr = ' | Context: ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
		}

		$logEntry = sprintf(
			"[%s] [EXCEPTION] %s: %s in %s:%d%s\nStack Trace:\n%s\n",
			$timestamp,
			get_class($exception),
			$sanitizedMessage,
			$sanitizedFile,
			$exception->getLine(),
			$contextStr,
			$sanitizedTrace
		);

		// Ensure the directory exists and is writable.
		$logDir = dirname(self::$logFile);
		if (! is_dir($logDir)) {
			@mkdir($logDir, 0700, true);
		}

		// Write to log file (append mode) with restricted permissions.
		$result = @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

		// Set restrictive file permissions (owner read/write only).
		if ($result !== false && file_exists(self::$logFile)) {
			@chmod(self::$logFile, 0600);
		}

		// If writing failed, try to log to PHP error log as fallback.
		if ($result === false) {
			$error = error_get_last();
			error_log(
				sprintf(
					'Logger failed to write to %s: %s. Original exception: %s',
					self::$logFile,
					$error['message'] ?? 'Unknown error',
					$exception->getMessage()
				)
			);
		}
	}

	/**
	 * Get the path to the log file.
	 *
	 * Note: This method should only be used for debugging purposes.
	 * The log file path should not be exposed to end users.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public static function getLogFile(): string
	{
		self::init();
		return self::$logFile;
	}
}
