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
	 * Cached .env values.
	 *
	 * @var array<string,string>|null
	 */
	private static ?array $env = null;

	/**
	 * Initialize the logger with the log file path.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private static function init(): void
	{
		if (self::$logFile === '') {
            // Root (public_html) – two levels up from inc/classes.
			$logPath = dirname(__DIR__, 2) . '/debug.log';

			$realBasePath = realpath(dirname(__DIR__, 2));
			$realLogPath  = realpath(dirname($logPath));

			if ($realBasePath === false || $realLogPath === false || strpos($realLogPath, $realBasePath) !== 0) {
				$logPath = dirname(__DIR__, 2) . '/debug.log';
			}

			self::$logFile = $logPath;
		}
	}

	/**
	 * Load .env once and return its values as an array.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,string>
	 */
	private static function loadEnv(): array
	{
		if (self::$env !== null) {
			return self::$env;
		}

		self::$env = [];

		$envPath = dirname(__DIR__, 2) . '/.env';
		if (! file_exists($envPath)) {
			return self::$env;
		}

		$lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($lines === false) {
			return self::$env;
		}

		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || $line[0] === '#' || ! str_contains($line, '=')) {
				continue;
			}

			[
             $key,
             $value,
            ]      = explode('=', $line, 2);
			$key   = trim($key);
			$value = trim($value);

			if ($key !== '') {
				self::$env[$key] = $value;
			}
		}

		return self::$env;
	}

	/**
	 * Get a single value from .env.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key Env key.
	 * @return string|null
	 */
	public static function getEnv(string $key): ?string
	{
		$env = self::loadEnv();
		return $env[$key] ?? null;
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

		$sanitizedMessage = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $message);
		$sanitizedLevel   = preg_replace('/[^A-Z0-9_]/', '', strtoupper($level));

		if (! empty($context)) {
			$contextStr = ' | Context: ' . json_encode(
				$context,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
			);
		}

		$logEntry = sprintf(
			"[%s] [%s] %s%s\n",
			$timestamp,
			$sanitizedLevel,
			$sanitizedMessage,
			$contextStr
		);

		$logDir = dirname(self::$logFile);
		if (! is_dir($logDir)) {
			@mkdir($logDir, 0700, true);
		}

		self::rotateLogIfNeeded();

		$result = @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

		if ($result !== false && file_exists(self::$logFile)) {
			@chmod(self::$logFile, 0600);
		}

		// Intentionally no PHP error_log() fallback:
		// you rely on debug.log as the single source of truth.
	}

	/**
	 * Log an error message.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $message Error message.
	 * @param  array  $context Extra context.
	 * @return void
	 */
	public static function error(string $message, array $context = []): void
	{
		// If error_code is present, use user-friendly title from errors.php.
		$errorCode    = $context['error_code'] ?? null;
		$logMessage   = $message;
		$logSnagTitle = $message;

		if ($errorCode !== null) {
			$errorKey = self::mapErrorCodeToKey($errorCode);
			if ($errorKey !== null) {
				$errors = self::loadErrorMessages();
				if (isset($errors[$errorKey])) {
					$logMessage   = $errors[$errorKey]['title'];
					$logSnagTitle = $errors[$errorKey]['title'];
				}
			}
		}

		// Filter out null/empty values from context - only keep useful debugging info.
		$filteredContext = array_filter(
			$context,
			function ($value) {
				return $value !== null && $value !== '';
			}
		);

		self::log($logMessage, 'ERROR', $filteredContext);
		self::sendToLogSnag($logSnagTitle, 'ERROR', $filteredContext);
	}

	/**
	 * Map error code to error key from errors.php.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $errorCode Error code constant.
	 * @return string|null Error key or null.
	 */
	private static function mapErrorCodeToKey(string $errorCode): ?string
	{
		// Map SVGConverter error codes to errors.php keys.
		switch ($errorCode) {
			case 'too_large':
				return 'too_large';
			case 'empty':
				return 'empty_input';
			case 'invalid_root':
			case 'malformed_xml':
			case 'invalid_attribute':
			case 'nesting_too_deep':
			case 'xml_parse_error':
				return 'invalid_svg';
			default:
				return 'server_error';
		}
	}

	/**
	 * Load error messages from errors.php.
	 *
	 * @since 1.0.0
	 *
	 * @return array<string,array<string,string>>
	 */
	private static function loadErrorMessages(): array
	{
		static $errors = null;
		if ($errors === null) {
			$errorsPath = __DIR__ . '/../functions/errors.php';
			if (file_exists($errorsPath)) {
				$errors = include $errorsPath;
			} else {
				$errors = [];
			}
		}
		return $errors;
	}

	/**
	 * Log a warning message.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $message Warning message.
	 * @param  array  $context Extra context.
	 * @return void
	 */
	public static function warning(string $message, array $context = []): void
	{
		self::log($message, 'WARNING', $context);
	}

	/**
	 * Log an info message.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $message Info message.
	 * @param  array  $context Extra context.
	 * @return void
	 */
	public static function info(string $message, array $context = []): void
	{
		self::log($message, 'INFO', $context);
	}

	/**
	 * Log a debug message.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $message Debug message.
	 * @param  array  $context Extra context.
	 * @return void
	 */
	public static function debug(string $message, array $context = []): void
	{
		self::log($message, 'DEBUG', $context);
	}

	/**
	 * Log an exception with full stack trace.
	 *
	 * @since 1.0.0
	 *
	 * @param  \Throwable $exception Exception object.
	 * @param  array      $context   Extra context.
	 * @return void
	 */
	public static function exception(\Throwable $exception, array $context = []): void
	{
		self::init();

		self::sendToLogSnag(
			$exception->getMessage(),
			'EXCEPTION',
			[
             'file' => $exception->getFile(),
             'line' => $exception->getLine(),
			]
		);

		$timestamp        = date('Y-m-d H:i:s');
		$sanitizedMessage = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $exception->getMessage());
		$sanitizedFile    = preg_replace('/[\r\n\x00-\x1F\x7F]/', '', $exception->getFile());
		$sanitizedTrace   = preg_replace(
			'/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F]/',
			'',
			$exception->getTraceAsString()
		);

		$contextStr = '';
		if (! empty($context)) {
			$contextStr = ' | Context: ' . json_encode(
				$context,
				JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
			);
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

		$logDir = dirname(self::$logFile);
		if (! is_dir($logDir)) {
			@mkdir($logDir, 0700, true);
		}

		self::rotateLogIfNeeded();

		$result = @file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);

		if ($result !== false && file_exists(self::$logFile)) {
			@chmod(self::$logFile, 0600);
		}

		// No error_log fallback – same reasoning as in log().
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

		$backupFile = self::$logFile . '.' . date('Ymd_His') . '.bak';

		if (@rename(self::$logFile, $backupFile)) {
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

		usort(
			$backupFiles,
			function ($a, $b) {
				$timeA = @filemtime($a);
				$timeB = @filemtime($b);

				if ($timeA === false || $timeB === false) {
					return 0;
				}

				return $timeB <=> $timeA;
			}
		);

		$filesToRemove = array_slice($backupFiles, self::MAX_BACKUP_FILES);
		foreach ($filesToRemove as $file) {
			@unlink($file);
		}
	}

	/**
	 * Get the path to the log file.
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
	 * Cloudways-safe: no getenv/putenv; all failures go to debug.log only.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $title   Event title or message.
	 * @param  string $level   Severity level.
	 * @param  array  $context Extra context data.
	 * @return void
	 */
	private static function sendToLogSnag(string $title, string $level = 'INFO', array $context = []): void
	{
		static $inLogSnag = false;

		if ($inLogSnag) {
			return;
		}

		$inLogSnag = true;

		try {
			// Do not send from CLI scripts – no need for remote logging there.
			if (php_sapi_name() === 'cli') {
				$inLogSnag = false;
				return;
			}

			$token = self::getEnv('LOGSNAG_TOKEN');
			if ($token === null || $token === '') {
				$inLogSnag = false;
				return;
			}

			// Disable SSL verification on local, enable on production.
			$host    = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
			$isLocal = str_ends_with($host, '.test') ||
				str_ends_with($host, '.local') ||
				$host === 'localhost' ||
				preg_match('/^127\.0\.0\./', $host);

			if ($isLocal) {
				$verifyPeer = false;
				$verifyHost = 0;
			} else {
				$verifyPeer = true;
				$verifyHost = 2;
			}

			$payload = [
                        'project' => 'webtions',
                        'channel' => 'svgready',
                        'event'   => $title,
                        'notify'  => in_array($level, ['ERROR', 'EXCEPTION'], true),
                       ];

			if (! empty($context)) {
				$description = [];
				$tags        = [];

				foreach ($context as $key => $value) {
					$tagKey = strtolower(preg_replace('/[^a-z0-9]+/', '-', (string) $key));

					if (is_scalar($value)) {
						// LogSnag tag values are limited to 160 characters.
						// Truncate svg_input specifically for tags (debug.log keeps full 500 chars).
						if ($key === 'svg_input' && is_string($value) && strlen($value) > 160) {
							$tags[$tagKey] = substr($value, 0, 157) . '...';
						} else {
							$tags[$tagKey] = $value;
						}
					} else {
						$description[] = sprintf(
							'%s: %s',
							$key,
							json_encode(
								$value,
								JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
							)
						);
					}
				}

				if (! empty($description)) {
					$payload['description'] = implode(' | ', $description);
				}

				if (! empty($tags)) {
					$payload['tags'] = $tags;
				}
			}

			$ch = curl_init('https://api.logsnag.com/v1/log');
			curl_setopt_array(
				$ch,
				[
                 CURLOPT_POST           => true,
                 CURLOPT_HTTPHEADER     => [
                                            'Content-Type: application/json',
                                            'Authorization: Bearer ' . $token,
                                           ],
                 CURLOPT_POSTFIELDS     => json_encode($payload),
                 CURLOPT_RETURNTRANSFER => true,
                 CURLOPT_FOLLOWLOCATION => true,
                 CURLOPT_TIMEOUT        => 5,
				 CURLOPT_ENCODING       => '',
				 CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
				 CURLOPT_SSL_VERIFYPEER => $verifyPeer,
				 CURLOPT_SSL_VERIFYHOST => $verifyHost,
				]
			);

			$response = curl_exec($ch);
			$errno    = curl_errno($ch);
			$error    = $errno ? curl_error($ch) : null;
			$status   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($errno || $status < 200 || $status >= 300) {
				// Local only – no recursion (warning() does not call sendToLogSnag()).
				self::warning(
					'LogSnag post failed',
					[
                     'httpStatus' => $status,
                     'curlErrno'  => $errno,
                     'curlError'  => $error,
                     'response'   => is_string($response) ? trim($response) : null,
					]
				);
			}
			// Success: don't log - we only care about failures.
		} catch (\Throwable $e) {
			// IMPORTANT: use log() directly to avoid recursion.
			self::log(
				'LogSnag fatal error: ' . $e->getMessage(),
				'ERROR',
				[
                 'file' => $e->getFile(),
                 'line' => $e->getLine(),
				]
			);
		} finally {
			$inLogSnag = false;
		}
	}
}
