<?php
/**
 * @version		$Id: KBIntegrator.php 1015 2013-10-31 08:24:05Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;
//require_once 'IKBIntegrator.php';
//require_once 'Query.php';
//require_once 'RESTClient.php';

/**
 * Generic implementation for IKBIntegrator.
 *
 * @package KBI
 */
class KBIntegrator {
	public static function create(Array $config)
	{
		$type = strtoupper(isset($config['type']) ? $config['type'] : 'GENERIC');

		switch($type){
			case 'LISPMINER':
				require_once 'Integrators/LispMiner.php';
				return new LispMiner($config);
				break;
		}
	}

	/** @var string */
	protected $config;

	/** @var RESTClient */
	private $restClient = null;

	public function getName()
	{
		return isset($this->config['name']) ? $this->config['name'] : '';
	}

	public function getUrl()
	{
		return isset($this->config['url']) ? $this->config['url'] : '';
	}

	public function setUrl($value)
	{
		$this->config['url'] = $value;
	}

	public function getMethod()
	{
		return isset($this->config['method']) ? $this->config['method'] : '';
	}

	public function setMethod($value)
	{
		$this->config['method'] = $value;
	}

	public function getPort()
	{
		return isset($this->config['port']) ? $this->config['port'] : 8081;
	}

	public function setPort($value)
	{
		$this->config['port'] = $value;
	}

	public function getUser()
	{
		// abstract method
		return null;
	}

	public function setUser($user)
	{
		// abstract method
	}

	protected function getRestClient()
	{
		if ($this->restClient === null) {
			$this->restClient = new RESTClient();
		}

		return $this->restClient;
	}

	public function __construct(Array $config = array())
	{
		if(isset($config['params']) && !empty($config['params'])) {
			$params = $this->parseNameValues($config['params']);
			unset($config['params']);

			$config = array_merge($config, $params);
		}

		$this->config = $config;
	}

	/**
	 * Implements the query execution. If remote source returned well-formed XML and XSLT is set the transformation is performed.
	 *
	 * @param KBIQuery|string $query
	 * @param string $xsl XSLT
	 * @return string The result of query execution.
	 */
	public function query($query, $xsl = '')
	{
		$options = array();

		if($query instanceof KBIQuery) {
			$query = $query->proccessQuery($options);
		}

		$method = strtoupper($this->getMethod());

		switch($method) {
			case 'POST':
				$xml_data = $this->queryPost($query, $options);
				break;
			case 'SOAP':
				$xml_data = $this->querySoap($query);
				break;
			default:
			case 'GET':
				$xml_data = $this->queryGet($query);
				break;
		}

		KBIDebug::log(array($xml_data), 'Raw result');

		if(empty($xsl)){
			return $xml_data;
		}

		$xml = new DOMDocument();
		if($xml->loadXML($xml_data)) {
			// Create XSLT document
			$xsl_document = new DOMDocument();
			$xsl_document->loadXML($xsl, LIBXML_NOCDATA);

			// Process XSLT
			$xslt = new XSLTProcessor();
			$xslt->importStylesheet($xsl_document);

			KBIDebug::info('Applying post-query transformation.');

			return $xslt->transformToXML($xml);
		} else {
			return $xml_data;
		}
	}

	/**
	 * Checks agains remote source if this instance is correctly configured.
	 *
	 * @return bool Wheter this instance is valid.
	 * @throws Exception
	 */
	public function test()
	{
		// should be overriden. Defaultly everything is valid.
		return true;
	}

	protected function queryGet($query)
	{
		$class = get_class($this);
		throw new Exception("Source type ({$class}) does not support this method (GET).");
	}

	protected function queryPost($query, $options)
	{
		$class = get_class($this);
		throw new Exception("Source type ({$class}) does not support this method (POST).");
	}

	protected function querySoap($query)
	{
		$class = get_class($this);
		throw new Exception("Source type ({$class}) does not support this method (SOAP).");
	}

	public function requestGet($url, $_data)
	{
		$data = array();
	    while(list($n,$v) = each($_data)){
	        $data[] = "$n=" . urlencode($v);
	    }
	    $data = implode('&', $data);

		$p = file_get_contents("$url?$data");

		KBIDebug::info("$url?$data", 'GET');

		return $p;
	}

	public function requestPost($url, $_data, $referer = NULL)
	{
	    // convert variables array to string:
	    $data = array();
	    while(list($n,$v) = each($_data)){
	        $data[] = "$n=$v";
	    }
	    $data = implode('&', $data);
	    // format --> test1=a&test2=b etc.

	    // parse the given URL
	    $url = parse_url($url);
	    if ($url['scheme'] != 'http') {
	        die('Only HTTP request are supported !');
	    }

	    // extract host and path:
	    $host = $url['host'];
	    $path = $url['path'];

	    // open a socket connection on port 80
	    $fp = fsockopen($host, $this->getPort(), $errno, $errstr);

        if(!$fp) {
            throw new Exception("Communication error: $errno $errstr ($host).");
        }

	    // send the request headers:
	    fputs($fp, "POST $path HTTP/1.1\r\n");
	    fputs($fp, "Host: $host\r\n");
	    if(!empty($referer)) fputs($fp, "Referer: $referer\r\n");
	    fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
	    fputs($fp, "Content-length: ". strlen($data) ."\r\n");
	    fputs($fp, "Connection: close\r\n\r\n");
	    fputs($fp, $data);

	 	if($fp)
	 	{
			$result = fgets($fp);

			while(!feof($fp))
			{
				// receive the results of the request
				$result .= fgets($fp, 128);
			}
		}

	    // close the socket connection:
	    fclose($fp);

	    // split the result header from the content
	    $result = explode("\r\n\r\n", $result, 2);
	    $header = isset($result[0]) ? $result[0] : '';
	    $content = isset($result[1]) ? $result[1] : '';

	    // return as array:
	    return $content;
	}

	//region cURL

	public function requestCurl($url, $_data)
	{
		$client = $this->getRestClient();

		return $client->get($url, $_data);
	}

	public function requestCurlPost($url, $_data)
	{
		$client = $this->getRestClient();

		return $client->post($url, $_data)->getBody();
	}

	//endregion

	public function parseNameValues($text)
	{
		if(is_array($text)) return $text;

		$values = json_decode($text, true);

		if($values == NULL) {
			$values = array();
			$matches = array();
			if (preg_match_all('/([^:\s]+)[\s]*:[\s]*("(?P<value1>[^"]+)"|' . '\'(?P<value2>[^\']+)\'|(?P<value3>.+?)\b)/', $text, $matches, PREG_SET_ORDER))
				foreach ($matches as $match)
					$values[trim($match[1])] = @$match['value1'] . @$match['value2'] . trim(@$match['value3']);
		}

		return $values;
	}
}
