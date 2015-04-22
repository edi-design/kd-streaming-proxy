<?php
/**
 * KabelDeutschland streaming proxy
 *
 * User: andre@freshest.me
 * Date: 18.04.15
 * Time: 15:30
 */

// needed includes
use KabelDeutschland\KabelDeutschland;
use KabelDeutschland\Log;

$path = dirname(__FILE__);
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
include 'classes/AbstractKabelDeutschland.php';
include 'classes/KabelDeutschland.php';
include 'classes/Log.php';

// config
include 'config/config.php';

switch (isset($_GET['log_level']) ? $_GET['log_level'] : Log::LOG_LEVEL_NONE)
{
	case Log::LOG_LEVEL_ERROR:
	case Log::LOG_LEVEL_DEBUG:
		$log_level = $_GET['log_level'];
		break;
	default:
		$log_level = Log::LOG_LEVEL_NONE;
}

/*
 * init logger
 */
$obj_logger = new Log($arr_config['log']);
$obj_logger->setLogLevel($log_level);

/*
 * init main class
 */
$obj_kd = new KabelDeutschland($arr_config, $arr_api_config);
$obj_kd->setObjLog($obj_logger);

// run
$obj_kd->run();