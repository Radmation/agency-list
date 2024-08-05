<?php

class ElmPro_ErrorHandler {
	const MAX_STRING_ARG_LENGTH = 25;
	const MEMORY_RESERVE_SIZE = 102400;

	/**
	 * @var ElmPro_Context
	 */
	private $context;

	/**
	 * Error types that can't be handled by a user-defined function.
	 *
	 * @link http://php.net/manual/en/function.set-error-handler.php
	 * @var array
	 */
	private $uncatchableErrorTypes = array(
		E_ERROR,
		E_PARSE,
		E_CORE_ERROR,
		E_CORE_WARNING,
		E_COMPILE_ERROR,
		E_COMPILE_WARNING,
		E_STRICT,
	);

	private $previousExceptionHandler = null;

	private $primaryErrorHandlerEnabled = true;

	/**
	 * @var callable Error handler registered before primaryErrorHandler().
	 */
	private $previousErrorHandler = null;
	/**
	 * @var callable Error handler registered before alternativeErrorHandler().
	 * Only used when the alternative handler is enabled.
	 */
	private $previousErrorHandlerAlt = null;

	/**
	 * @var Exception|Throwable
	 */
	private $lastException = null;

	/**
	 * @var string Reserves memory for the fatal error handler.
	 */
	private $memoryReserve = '';

	public function __construct(ElmPro_Context $context) {
		$this->context = $context;
	}

	public function install() {
		$this->previousErrorHandler = set_error_handler(array($this, 'primaryErrorHandler'));
		$this->previousExceptionHandler = set_exception_handler(array($this, 'handleException'));

		/*
		 * Compatibility workaround:
		 *
		 * Other plugins can also register new error handlers and some of them don't call the previous
		 * handler. In that case, our handler might never be called. To reduce that risk, let's register
		 * an alternative handler after all plugins have been loaded. This way our handler will be on
		 * the top of the stack, so it will be called first.
		 */
		add_action('plugins_loaded', array($this, 'registerAlternativeErrorHandler'), 5000);

		//Catch WPDB errors. For this to work, the user must copy the file /db-wrapper/db.php
		//to the "wp-content" directory.
		add_action('elm_wpdb_error', array($this, 'handleDatabaseError'), 10, 2);

		register_shutdown_function(array($this, 'onShutdown'));
		$this->memoryReserve = str_repeat('E', self::MEMORY_RESERVE_SIZE);
	}

	public function primaryErrorHandler($level, $message = '', $fileName = '', $line = 0, $deprecated = array()) {
		if ( !$this->primaryErrorHandlerEnabled ) {
			if ( $this->previousErrorHandler !== null ) {
				return call_user_func($this->previousErrorHandler, $level, $message, $fileName, $line, $deprecated);
			}
			return false;
		}

		return $this->handleError($level, $message, $fileName, $line, $deprecated, $this->previousErrorHandler);
	}

	public function registerAlternativeErrorHandler() {
		/*
		 * We only need to use the alternative handler if someone else has set a different handler.
		 * Unfortunately, there's no way to get the current error handler without replacing it,
		 * so we set a new handler first and then restore the previous handler if necessary.
		 */
		$previousHandler = set_error_handler(array($this, 'alternativeErrorHandler'));
		$isPrimaryHandler = is_array($previousHandler)
			&& isset($previousHandler[0], $previousHandler[1])
			&& ($previousHandler[0] === $this)
			&& ($previousHandler[1] === 'primaryErrorHandler');

		if ( $isPrimaryHandler ) {
			restore_error_handler();
			return;
		}

		$this->previousErrorHandlerAlt = $previousHandler;
		$this->primaryErrorHandlerEnabled = false;
	}

	public function alternativeErrorHandler($level, $message = '', $fileName = '', $line = 0, $deprecated = array()) {
		return $this->handleError($level, $message, $fileName, $line, $deprecated, $this->previousErrorHandlerAlt);
	}

	private function handleEvent($fileName = '', $lineNumber = 0, $stackTrace = array(), $parentEntryPosition = null) {
		$context = $this->context->snapshot();

		$context['fileName'] = $fileName;
		$context['lineNumber'] = $lineNumber;

		if ( !empty($stackTrace) ) {
			$context['stackTrace'] = $this->prepareStackTrace($stackTrace);
		}
		if ( !empty($parentEntryPosition) ) {
			$context['parentEntryPosition'] = (string)$parentEntryPosition;
		}

		//Log context data.
		$serializedContext = json_encode($context);
		$suffix = rand(1, 100000);
		error_log(sprintf('[ELM_context_%1$d]%2$s[/ELM_context_%1$d]', $suffix, $serializedContext));
	}

	private function handleError($level, $message = '', $fileName = '', $line = 0, $deprecated = array(), $previousHandler = null) {
		//error_reporting() will return zero when an error is suppressed by the @ operator.
		//We only add context to those errors that will be reported and logged.
		$errorReporting = error_reporting();
		if ( ($errorReporting !== 0) && (($errorReporting & $level) !== 0) ) {
			$trace = debug_backtrace();
			//Discard the stack trace items that correspond to the current method and the XErrorHandler wrapper.
			array_shift($trace);
			array_shift($trace);

			//Note: The stack trace is in deepest-call-first order (i.e. most recent call first).
			$this->handleEvent($fileName, $line, $trace);
		}

		if ( $previousHandler !== null ) {
			return call_user_func($previousHandler, $level, $message, $fileName, $line, $deprecated);
		}
		return false;
	}

	/**
	 * @param Exception|Throwable $exception
	 * @throws Throwable
	 */
	public function handleException($exception) {
		$this->lastException = $exception;

		$trace = $exception->getTrace();
		//Add the location where the exception was thrown.
		array_unshift(
			$trace,
			array(
				'file' => $exception->getFile(),
				'line' => $exception->getLine(),
			)
		);

		$this->handleEvent($exception->getFile(), $exception->getLine(), $trace);

		if ( $this->previousExceptionHandler !== null ) {
			call_user_func($this->previousExceptionHandler, $exception);
		} else {
			throw $exception;
		}
	}

	/** @noinspection PhpUnusedParameterInspection Currently unused. */
	public function handleDatabaseError($message = '', $query = '') {
		global $wpdb;
		/** @var wpdb $wpdb */
		if ( !$wpdb || $wpdb->suppress_errors ) {
			return;
		}

		$trace = debug_backtrace();
		//Discard the current method, hook handlers, and the print_error() method of the wrapper class.
		$skipItems = 4;
		if ( class_exists('WP_Hook', false) ) {
			$skipItems++;
		}

		$callerFileName = '';
		$callerLineNumber = 0;

		$filteredTrace = array();
		foreach ($trace as $item) {
			if ( $skipItems > 0 ) {
				$skipItems--;
				continue;
			}
			$filteredTrace[] = $item;

			//Find the file that called a WPDB method.
			$isDbCall = (isset($item['class']) && ($item['class'] === 'wpdb'))
				|| (isset($item['object']) && ($item['object'] === $wpdb));
			if ( $isDbCall ) {
				$callerFileName = isset($item['file']) ? $item['file'] : '';
				$callerLineNumber = isset($item['line']) ? $item['line'] : 0;
			}
		}

		$this->handleEvent($callerFileName, $callerLineNumber, $filteredTrace, 'next');
	}

	public function onShutdown() {
		unset($this->memoryReserve);

		$error = error_get_last();
		if ( ($error === null) || !is_array($error) ) {
			return; //No error.
		}

		if ( $this->isUncaughtFatalError($error['type'], $error['message']) ) {
			$this->handleEvent($error['file'], $error['line'], array(), 'previous');
		}
	}

	private function isUncaughtFatalError($type, $message) {
		if ( $this->lastException ) {
			//Skip fatal errors caused by uncaught exceptions. We already process exceptions in handleException().
			$uncaughtExceptionMessage = sprintf(
				'Uncaught %s: %s',
				get_class($this->lastException),
				$this->lastException->getMessage()
			);
			if ( ($type === E_ERROR) && (strpos($message, $uncaughtExceptionMessage) === 0) ) {
				return false;
			}
		}

		return in_array($type, $this->uncatchableErrorTypes);
	}

	private function prepareStackTrace($trace) {
		$result = array();

		foreach ($trace as $frame) {
			$entry = array(
				'file' => isset($frame['file']) ? $frame['file'] : null,
				'line' => isset($frame['line']) ? $frame['line'] : null,
			);

			//TODO: For uncaught exceptions, the deepest call may have no class or function
			//but it's not actually an anonymous closure.
			$entry['call'] = '{anonymous}';

			if ( !empty($frame['class']) ) {
				if ( !empty($frame['type']) && !empty($frame['function']) ) {
					$entry['call'] = $frame['class'] . $frame['type'] . $frame['function'];
				} else {
					$entry['call'] = $frame['class'];
				}
			} else if ( !empty($frame['function']) ) {
				$entry['call'] = $frame['function'];
			}

			if ( !empty($frame['args']) ) {
				$entry['call'] .= '(' . $this->formatArgs($frame['args']) . ')';
			} else {
				$entry['call'] .= '()';
			}

			$result[] = $entry;
		}

		return $result;
	}

	private function formatArgs($args) {
		$formatted = array();
		foreach ($args as $arg) {
			if ( is_string($arg) ) {
				$value = '\'' . $this->truncate($arg, self::MAX_STRING_ARG_LENGTH, '...') . '\'';
			} else if ( is_bool($arg) ) {
				$value = $arg ? 'true' : 'false';
			} else if ( $arg === null ) {
				$value = 'null';
			} else if ( is_scalar($arg) ) {
				$value = $arg;
			} else if ( is_object($arg) ) {
				$value = get_class($arg);
			} else if ( is_array($arg) ) {
				$value = 'Array(' . count($arg) . ')';
			} else {
				$value = gettype($arg);
			}
			$formatted[] = $value;
		}
		return implode(', ', $formatted);
	}

	private function truncate($string, $length, $suffix = '...') {
		if ( strlen($string) > $length ) {
			return substr($string, 0, ($length - strlen($suffix))) . $suffix;
		}
		return $string;
	}
}