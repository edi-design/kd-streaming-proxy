<?php
/**
 * Abstract to be inherited by all classes
 *
 * User: andre@freshest.me
 * Date: 18.04.15
 * Time: 16:00
 */

namespace KabelDeutschland;


class AbstractKabelDeutschland
{
	protected $base_config;

	/**
	 * @var array
	 */
	protected $credentials = array('username' => null, 'password' => null);

	/**
	 * api config
	 * @var array
	 */
	protected $api = array('version' => null, 'device' => null, 'ios_version' => null, 'udid' => null);

	/**
	 * @var array
	 */
	protected $methods = array('sign_in' => null, 'channel_list' => null);

	protected $base_url = '';

	protected $init_object = array('initObj' => null);

	/**
	 * Logger
	 * @var Log
	 */
	protected $obj_log = null;

	/**
	 * initialized cache file with directory
	 * @var null
	 */
	private $cache_file = null;

	private $cache_file_format = 'playlist_%s.m3u';

	/**
	 * constructor
	 *
	 * @param $arr_config
	 * @param $arr_api_config
	 */
	public function __construct($arr_config, $arr_api_config)
	{
		$this->base_config = $arr_api_config['base_config'];
		$this->credentials = $arr_config['credentials'];
		$this->api = $arr_config['api'];
		$this->methods = $arr_api_config['methods'];

		// init cache
		$cache_folder = dirname(__FILE__) . '/../cache/';
		$this->cache_file = $cache_folder . $this->cache_file_format;
	}

	/**
	 * main entry method
	 */
	public function run()
	{
		// get configuration from server
		$this->initConfigFromKabelDeutschland();
	}

	/**
	 * @param null $obj_log
	 */
	public function setObjLog($obj_log)
	{
		$this->obj_log = $obj_log;
	}

	/**
	 * handles all curl request
	 *
	 * @param $url
	 * @param $arr_params
	 * @param bool $bool_init
	 * @return mixed
	 */
	protected function get($url, $arr_params, $bool_init = false)
	{
		if (!$bool_init)
		{
			// merge init params from config call
			$arr_params = array_merge($arr_params, $this->init_object);
		}

		$params = json_encode($arr_params, JSON_UNESCAPED_SLASHES);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($params))
		);

		$result = curl_exec($ch);
		$this->obj_log->logResult($url, $result);

		return json_decode($result, true);
	}

	/**
	 * returns a full url according to given method
	 *
	 * @param $method
	 * @return string
	 */
	protected function generateUrl($method)
	{
		return $this->base_url .
		'?m=' . $this->methods[$method].
		'&iOSv=' . $this->api['ios_version'].
		'&Appv=' . $this->api['version'];
	}

	/**
	 * calls main kd-config backend to retrieve user-specific vars
	 */
	protected function initConfigFromKabelDeutschland()
	{
		$url = $this->base_config['config_url'] . '?';
		foreach($this->base_config['params'] as $name => $value)
		{
			$url .= urlencode($name).'='.urlencode($value).'&';
		}
		$url = substr($url, 0, strlen($url)-1);

		$arr_config = $this->get($url, array(), true);

		$this->base_url = $arr_config['params']['Gateways'][0]['JsonGW'];

		// init obj has wrong format
		$arr_init = $arr_config['params']['InitObj'];
		foreach ($arr_init as $element)
		{
			foreach ($element as $key => $value)
			{
				if (!is_array($value))
				{
					$this->init_object['initObj'][$key] = $value;
				} else {
					foreach ($value as $sub_element)
					{
						foreach ($sub_element as $sub_key => $sub_value)
						{
							$this->init_object['initObj'][$key][$sub_key] = $sub_value;
						}
					}
				}
			}
		}

		// set hardcoded values
		$this->init_object['initObj']['UDID'] = $this->api['udid'];

		$this->obj_log->log('initConfig', json_encode($this->init_object));
	}

	/**
	 * @return null
	 */
	public function getCacheFile($quality)
	{
		return sprintf($this->cache_file, $quality);
	}
}