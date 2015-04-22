<?php
/**
 * Configuration
 *
 * User: andre@freshest.me
 * Date: 18.04.15
 * Time: 15:39
 */

/**
 * user configuration
 * - change your credentials
 * - all other value-changes are optional
 */
$arr_config = array(
	'credentials' => array(
		'username' => '##CSC_USERNAME###',
		'password' => '##CSC_PASSWORD###'
	),
	'api' => array(
		'version' => '1.1.5',
		'device' => 'iPad',
		'ios_version' => '8.1.2',
		'udid' => 'D2AC633AFB644CF0A67505370578CDE5',
		'default_channelorder' => 'None',
		'default_channelid' => 340758,
		'default_picsize' => '100X100',
		'default_pagesize' => 1000,
	),
	'log' => array(
		'generic_log' => 'generic.log',
		'result_log' => 'result.log'
	)
);

/**
 * KabelDeutschland configuration
 * - no need to change anything
 */
$arr_api_config = array(
	'base_config' => array(
		'config_url' => 'https://dms-live.iptv.kabel-deutschland.de/api.svc/getconfig',
		'params' => array(
			'username' => 'kdg',
			'password' => 'DKNaAHvuuaTkhPN8rtTD',
			'appname' => 'com.kabeldeutschland.tvapp',
			'cver' => '1.1.5',
			'platform' => 'iOS',
			'udid' => $arr_config['api']['udid']
		)
	),
	'methods' => array(
		'sign_in' => 'SSOSignIn',
		'channellist' => 'GetChannelMediaList',
		'licensedlink' => 'GetLicensedLinks',
		'devicedomains' => 'GetDeviceDomains'
	)
);