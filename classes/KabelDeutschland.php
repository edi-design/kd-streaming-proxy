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
		if (isset($arr_params['id']) && isset($arr_params['link']))
		{
			$this->redirectToStreamUrl($arr_params['id'], $arr_params['link']);
		}
		else
		{
			$arr_channels = $this->getChannelList();
			$this->showChannelList($arr_params, $arr_channels);
		}
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
		$self_link = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];

		// Manage the output format
		$format = isset($arr_params['format']) ? $arr_params['format'] : 'm3u';
		switch($format)
		{
			case 'txt':
				$mime_type = 'text/plain';
				break;
			case 'm3u':
			default:
				$mime_type = 'audio/x-mpequrl';
				break;
		}

		// Output headers and the playlist contents
		header('Status: 200 OK');
		header("Content-Type: $mime_type");
		header('Content-Disposition: inline; filename="playlist.m3u"');
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

		echo $this->m3u_head;
		foreach ($arr_channels as $channel)
		{
			$link =
				$self_link . '?id=' . $channel['id'] .
				'&link=' . urlencode($channel['link']) .
				'&log_level=' . $this->obj_log->getLogLevel();

			echo sprintf($this->m3u_line, $channel['title'], $link);
		}

	}

	/**
	 * gets a licensed stream link and redirects to it
	 *
	 * @param $media_file_id
	 * @param $base_link
	 */
	private function redirectToStreamUrl($media_file_id, $base_link)
	{
		$url = $this->getLicensedLink($media_file_id, $base_link);

		if (empty($url))
		{
			$message =
				"Can't get licensed link for Id: " . $media_file_id .
				' Link: '.$base_link;
			echo $message;
			$this->obj_log->log('licensedLink', $message);
			return;
		}

		// send redirect
		header('Location: '.$url);
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