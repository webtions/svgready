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
	 * Maximum log file size before rotation (5MB).
	 *
	 * @var int
	 */
	private const MAX_LOG_SIZE = 5_000_000;

	/**
	 * Maximum number of backup files to keep.
	 *
	 * @var int
	 */
	private const MAX_BACKUP_FILES = 10;

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

		// Rotate log file if it exceeds maximum size.
		self::rotateLogIfNeeded();

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
		self::sendToLogSnag($message, 'ERROR', $context);
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
	// existing exception logging logic (unchanged) …
		self::sendToLogSnag($exception->getMessage(), 'EXCEPTION', [
                                                                    'file' => $exception->getFile(),
                                                                    'line' => $exception->getLine(),
                                                                   ]);


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

		// Rotate log file if it exceeds maximum size.
		self::rotateLogIfNeeded();

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
	 * Rotate log file if it exceeds maximum size.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function rotateLogIfNeeded(): void
	{
		if (! file_exists(self::$logFile)) {
			return;
		}

		$fileSize = @filesize(self::$logFile);
		if ($fileSize === false || $fileSize < self::MAX_LOG_SIZE) {
			return;
		}

		// Create backup filename with timestamp.
		$backupFile = self::$logFile . '.' . date('Ymd_His') . '.bak';

		// Rename current log file to backup.
		if (@rename(self::$logFile, $backupFile)) {
			// Clean up old backup files (keep only the most recent ones).
			self::cleanupOldBackups();
		}
	}

	/**
	 * Clean up old backup log files, keeping only the most recent ones.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function cleanupOldBackups(): void
	{
		$logDir      = dirname(self::$logFile);
		$logBaseName = basename(self::$logFile);
		$pattern     = $logDir . '/' . $logBaseName . '.*.bak';

		$backupFiles = glob($pattern);
		if ($backupFiles === false || count($backupFiles) <= self::MAX_BACKUP_FILES) {
			return;
		}

		// Sort by modification time (newest first).
		usort($backupFiles, function ($a, $b) {
			$timeA = @filemtime($a);
			$timeB = @filemtime($b);

			if ($timeA === false || $timeB === false) {
				return 0;
			}

			return $timeB - $timeA;
		});

		// Remove oldest backup files beyond the limit.
		$filesToRemove = array_slice($backupFiles, self::MAX_BACKUP_FILES);
		foreach ($filesToRemove as $file) {
			@unlink($file);
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

	/**
	 * Send an event to LogSnag.
	 *
	 * @param string $title   Event title or message.
	 * @param string $level   Severity level.
	 * @param array  $context Extra context data.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function sendToLogSnag(string $title, string $level = 'INFO', array $context = []): void
	{
		try {
			// Load .env if not already loaded
			$envPath = dirname(__DIR__, 2) . '/.env';
			if (file_exists($envPath)) {
				foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
					if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
						continue;
					}
					[
					 $name,
					 $value,
					] = array_map('trim', explode('=', $line, 2));
					if ($name !== '' && ! getenv($name)) {
						putenv(sprintf('%s=%s', $name, $value));
					}
				}
			}

			$token = getenv('LOGSNAG_TOKEN');
			if (empty($token)) {
				return; // Skip silently if no token defined
			}

			$server = $_SERVER['SERVER_NAME'] ?? '';
			if ($server === '') {
				// Likely CLI/cron; skip silently.
				return;
			}

			$payload = [
                        'project' => 'svgready',
                        'channel' => 'error',
                        'event'   => $title,
                        'notify'  => in_array($level, ['ERROR', 'EXCEPTION'], true),
                       ];

			if (! empty($context)) {
				$payload['description'] = json_encode(
                    $context,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
				);
			}

			$ch = curl_init('https://api.logsnag.com/v1/log');
			curl_setopt_array($ch, [
                                    CURLOPT_POST           => true,
                                    CURLOPT_HTTPHEADER     => [
                                                               'Content-Type: application/json',
                                                               'Authorization: Bearer ' . $token,
                                                              ],
                                    CURLOPT_POSTFIELDS     => json_encode($payload),
                                    CURLOPT_RETURNTRANSFER => true,
                                    CURLOPT_FOLLOWLOCATION => true,
                                    CURLOPT_TIMEOUT        => 5,
                                   ]);

			$response = curl_exec($ch);
			$errno    = curl_errno($ch);
			$error    = $errno ? curl_error($ch) : null;
			$status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			// Success codes: 2xx
			if ($errno || $status < 200 || $status >= 300) {
				Logger::warning('LogSnag post failed', [
                                                        'httpStatus' => $status,
                                                        'curlErrno'  => $errno,
                                                        'curlError'  => $error,
                                                        'response'   => is_string($response) ? trim($response) : null,
                                                       ]);

				error_log(sprintf(
					'[LogSnag] Failed (HTTP %d, errno %d): %s | resp=%s',
					$status,
					$errno,
					$error ?? 'n/a',
					is_string($response) ? substr($response, 0, 500) : 'n/a'
				));
			}
		} catch (\Throwable $e) {
			Logger::exception($e, ['where' => 'sendToLogSnag']);
		}
	}
}
