<?php
/**
 * Main class to retrieve channellist and redirect to lincesed channellinks
 *
 * User: andre@freshest.me
 * Date: 18.04.15
 * Time: 15:56
 */

namespace KabelDeutschland;


class KabelDeutschland extends AbstractKabelDeutschland
{
	/**
	 * defines the m3u extended head defintion
	 * @var string
	 */
	protected $m3u_head = "#EXTM3U\n";

	/**
	 * sprintf template for each playlist line
	 * @var string
	 */
	protected $m3u_line = "#EXTINF:-1,%s\n%s\n";

	/**
	 * contains the qualitity divided playlist file-identifier
	 * @var array
	 */
	protected $arr_quality = array(
		'low' => 'CCURstream564000.m3u8',
		'medium' => 'CCURstream1064000.m3u8',
		'high' => 'CCURstream1664000.m3u8'
	);

	/**
	 * lifetime 1 day
	 * @var int
	 */
	protected $cache_lifetime = 86400; // 60s * 60m * 24h = 1day in seconds

	/**
	 * main entry point
	 */
	public function run()
	{
		/*
		 * initial set-up calls
		 */
		parent::run();
		$this->signIn();

		$arr_params = $_GET;

		$arr_channels = $this->getChannelList();
		$this->showChannelList($arr_params, $arr_channels);
	}

	/**
	 * handles the login procedure and sets some environment vars
	 * needs to be called after domain init
	 */
	private function signIn()
	{
		$url = $this->generateUrl('sign_in');

		$arr_params = array(
			'userName' => $this->credentials['username'],
			'password' => $this->credentials['password'],
			"providerID" => 0,
		);

		$arr_session = $this->get($url, $arr_params);

		$this->init_object['initObj']['SiteGuid'] = $arr_session['SiteGuid'];
		$this->init_object['initObj']['DomainID'] = $arr_session['DomainID'];

		$this->obj_log->log('signIn', json_encode($this->init_object));
	}

	/**
	 * get list of channels from kd backend
	 * @return array
	 */
	private function getChannelList()
	{
		$url = $this->generateUrl('channellist');

		$arr_params = array(
			"orderBy" => $this->api['default_channelorder'],
			"pageSize" => $this->api['default_pagesize'],
			"picSize" => $this->api['default_picsize'],
			"ChannelID" => $this->api['default_channelid']
		);

		$arr_channel = $this->get($url, $arr_params);

		$arr_return = array();
		foreach ($arr_channel as $channel)
		{
			if (!empty($channel['Files'][0]['URL']))
			{
				$arr_return[] = array(
					'link' => trim($channel['Files'][0]['URL']),
					'id' => $channel['Files'][0]['FileID'],
					'title' => $channel['MediaName']
				);
			}
		}

		$this->obj_log->log('channelList', 'Count: '. count($arr_return));

		return $arr_return;
	}

	/**
	 * handles head and output for channellist overview
	 *
	 * @param $arr_params
	 * @param $arr_channels
	 */
	private function showChannelList($arr_params, $arr_channels)
	{
		// quality selection
		$quality = isset($arr_params['quality']) ? $arr_params['quality'] : 'medium';
		$playlist = isset($this->arr_quality[$quality]) ? $this->arr_quality[$quality] : $this->arr_quality['medium'];

		// Manage the output format
		$format = isset($arr_params['format']) ? $arr_params['format'] : 'm3u';
		switch($format)
		{
			case 'txt':
				$mime_type = 'text/plain';
				break;
			case 'm3u':
			default:
				$mime_type = 'application/vnd.apple.mpegurl';
				break;
		}

		// Output headers and the playlist contents
		header('Status: 200 OK');
		header("Content-Type: $mime_type");
		header('Content-Disposition: inline; filename="playlist.m3u"');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

		// check cache
		$cached = false;
		if (file_exists($this->getCacheFile($quality)))
		{
			$file_mtime = filemtime($this->getCacheFile($quality));
			if ((time() - $file_mtime) <= $this->cache_lifetime)
			{
				$cached = true;
			}
		}

		if ($cached === false)
		{
			$bool_save_to_cache = true;

			// re-call licensed links
			$output = $this->m3u_head;
			foreach ($arr_channels as $channel) {
				$link = $this->getLicensedLink($channel['id'], $channel['link']);
				if (empty($link))
				{
					// can't get licensed link, abort channel-list request
					$output = "Can't get licensed link, try the golang version from http://freshest.me";
					$this->obj_log->log('licensedLink', $output);
					$bool_save_to_cache = false;
					break;
				}
				$link = $this->getRedirectTarget($link);

				// remove given playlist and replace it with direct ts-stream-playlist
				$link = substr($link, 0, strrpos($link, '/')) . '/' . $playlist;

				$output .= sprintf($this->m3u_line, $channel['title'], $link);
			}

			// save playlist as cached file
			if ($bool_save_to_cache)
			{
				file_put_contents($this->getCacheFile($quality), $output);
			}
		} else {
			// read from cache
			$output = file_get_contents($this->getCacheFile($quality));
		}

		// return the playlist
		echo $output;
	}

	/**
	 * returns the final destination of a redirected link
	 *
	 * @param $destination
	 * @return mixed
	 */
	private function getRedirectTarget($destination){
		$headers = get_headers($destination, 1);
		return $headers['Location'];
	}

	/**
	 * calls the kd backend to generate a valid stream link for the given channel
	 *
	 * @param $media_file_id
	 * @param $base_link
	 * @return mixed
	 */
	private function getLicensedLink($media_file_id, $base_link)
	{
		$url = $this->generateUrl('licensedlink');

		$arr_params = array(
			"mediaFileID" => (int)$media_file_id,
			"baseLink" => $base_link
		);

		$arr_link = $this->get($url, $arr_params);

		$this->obj_log->log('licensedLink', json_encode(array('params' => $arr_params, 'return' => $arr_link)));

		return $arr_link['mainUrl'];
	}

}