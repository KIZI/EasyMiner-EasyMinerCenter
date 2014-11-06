<?php

namespace KBI;

//require_once 'RESTClientResponse.php';

class RESTClient
{
	private function getData($_data)
	{
		if ($_data == null) {
			return '';
		}

		$data = $_data;

		// convert variables array to string:
		if (is_array($_data) || is_object($_data)) {
			// $data = $this->encodeData($data);
			$data = http_build_query($_data);
		} else if (is_string($_data)) {
			$data = $_data;
		}

		return $data;
	}

	public function encodeData(Array $array)
	{
		// foreach ($array as $key=>$value) $data .= "{$key}=".urlencode($value).'&';

		$data = array();

		while(list($n,$v) = each($array)) {
			$data[] = "{$n}=" . urlencode($v);
		}

		return implode('&', $data);
	}

	/**
	 * @param $url
	 * @param array $_data
	 * @param null $credentials
	 * @return RESTClientResponse
	 */
	public function get($url, $_data = null, $credentials = null)
	{
		$data = $this->getData($_data);

		$ch = curl_init("$url?$data");

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		if ($credentials != null) {
			curl_setopt($ch, CURLOPT_USERPWD, $credentials['username'] . ":" . $credentials['password']);
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return new RESTClientResponse($response, $info);
	}

	/**
	 * @param $url
	 * @param array $_data
	 * @param null $credentials
	 * @return RESTClientResponse
	 */
	public function post($url, $_data = array(), $credentials = null)
	{
		$data = $this->getData($_data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		if ($credentials != null) {
			curl_setopt($ch, CURLOPT_USERPWD, $credentials['username'] . ":" . $credentials['password']);
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return new RESTClientResponse($response, $info);
	}

	/**
	 * @param $url
	 * @param $_data
	 * @param null $credentials
	 * @return RESTClientResponse
	 */
	public function put($url, $_data, $credentials = null)
	{
		$data = $this->getData($_data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');

		// enable tracking
		// curl_setopt($ch, CURLINFO_HEADER_OUT, true);

		if ($credentials != null) {
			curl_setopt($ch, CURLOPT_USERPWD, $credentials['username'] . ":" . $credentials['password']);
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		// KBIDebug::log(array('info' => $info, 'raw_data' => $data), "PUT");

		return new RESTClientResponse($response, $info);
	}

	/**
	 * @param $url
	 * @param $_data
	 * @param null $credentials
	 * @return RESTClientResponse
	 */
	public function patch($url, $_data, $credentials = null)
	{
		$data = $this->getData($_data);

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');

		if ($credentials != null) {
			curl_setopt($ch, CURLOPT_USERPWD, $credentials['username'] . ":" . $credentials['password']);
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return new RESTClientResponse($response, $info);
	}

	/**
	 * @param $url
	 * @param array $_data
	 * @param null $credentials
	 * @return RESTClientResponse
	 */
	public function delete($url, $_data = null, $credentials = null)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		if ($credentials != null) {
			curl_setopt($ch, CURLOPT_USERPWD, $credentials['username'] . ":" . $credentials['password']);
		}

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		return new RESTClientResponse($response, $info);
	}
}