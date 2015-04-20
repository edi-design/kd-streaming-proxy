<?php
/**
 * KabelDeutschland streaming proxy
 *
 * User: andre@freshest.me
 * Date: 18.04.15
 * Time: 15:30
 */

// needed includes
$path = dirname(__FILE__);
set_include_path(get_include_path() . PATH_SEPARATOR . $path);
include 'classes/AbstractKabelDeutschland.php';
include 'classes/KabelDeutschland.php';

// config
include 'config/config.php';

$obj_kd = new \KabelDeutschland\KabelDeutschland($arr_config, $arr_api_config);

// run
$channellist = $obj_kd->run();