<?php
/**
 * Abstract to be inherited by all classes
 *
 * User: andre@freshest.me
 * Date: 22.04.15
 * Time: 09:00
 */

namespace KabelDeutschland;


class Log
{
	/**
	 * defined log-levels
	 */
	const LOG_LEVEL_DEBUG = 'debug';
	const LOG_LEVEL_ERROR = 'error';
	const LOG_LEVEL_NONE = 'none';

	/**
	 * log file to write general errors
	 * @var null
	 */
	protected $generic_log = null;

	/**
	 * log-file to write results from api
	 * for debug purposes
	 * @var null
	 */
	protected $result_log = null;

	/**
	 * default log-level is 'none'
	 * @var string
	 */
	protected $log_level = self::LOG_LEVEL_NONE;

	/**
	 * Log constructor.
	 */
	public function __construct($arr_config)
	{
		$log_folder = dirname(__FILE__) . '/../logs/';

		$this->generic_log = $log_folder . $arr_config['generic_log'];
		$this->result_log = $log_folder . $arr_config['result_log'];
	}

	/**
	 * @param null $log_level
	 */
	public function setLogLevel($log_level)
	{
		$this->log_level = $log_level;
	}

	/**
	 * @return string
	 */
	public function getLogLevel()
	{
		return $this->log_level;
	}

	/**
	 * generic log-writer
	 *
	 * @param $namespace
	 * @param $message
	 */
	public function log($namespace, $message)
	{
		switch ($this->log_level)
		{
			case self::LOG_LEVEL_DEBUG:
			case self::LOG_LEVEL_ERROR:
				$message = '['.date('Y-m-d H:m:s').'] - ['.$namespace.']' . "\n" . $message . "\n";
				file_put_contents($this->generic_log, $message, FILE_APPEND);
				break;
		}
	}

	/**
	 * result log-writer, only if log-level is debug
	 *
	 * @param $namespace
	 * @param $message
	 */
	public function logResult($namespace, $message)
	{
		switch ($this->log_level)
		{
			case self::LOG_LEVEL_DEBUG:
				$message =
					'['.date('Y-m-d H:m:s').'] - ['.$namespace.']' . "\n" .
					preg_replace("/\r|\n/",'',$message) .	"\n";

				file_put_contents($this->result_log, $message, FILE_APPEND);
				break;
		}
	}
}