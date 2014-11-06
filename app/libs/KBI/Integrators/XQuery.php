<?php
/**
 * @version		$Id: XQuery.php 945 2013-07-01 00:22:10Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

//require_once dirname(__FILE__) . '/../KBIntegratorSynchronable.php';

/**
 * IKBIntegrator implementation for XQuery database.
 *
 * @package KBI
 */
class XQuery extends KBIntegratorSynchronable
{
	public function getMethod()
	{
		return isset($this->config['method']) ? $this->config['method'] : 'POST';
	}

	public function getAction()
	{
		return isset($this->config['action']) ? $this->config['action'] : 'directQuery';
	}

	public function setAction($value)
	{
		$this->config['action'] = $value;
	}

	public function getVariable()
	{
		return isset($this->config['variable']) ? $this->config['variable'] : '';
	}

	public function setVariable($value)
	{
		$this->config['variable'] = $value;
	}

	public function __construct(Array $config)
	{
		parent::__construct($config);
	}

	public function queryPost($query, $options)
	{
		$url = $this->getUrl();

		$postdata = array(
			'action' => $this->getAction(),
			'id' => $this->getVariable(),
			'content' => $query,
		);

		KBIDebug::info(array($url, $postdata));
		return $this->requestCurlPost($url, $postdata);
	}

	public function getDocuments()
	{
		$ch = curl_init();
		$documents = array();

		$data = array(
			'action' => 'getDocsNames',
			'id' => '',
			'content' => '',
		);

		curl_setopt($ch, CURLOPT_URL, $this->getUrl());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encodeData($data));
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		KBIDebug::log(array($response, $info));

		if($info['http_code'] != '200')
		{
			throw new Exception('Error in communication');
		}
		else
		{
			$xml = simplexml_load_string($response);
			$docs = $xml->children();
			if(!empty($docs))
			{
				foreach($docs[0] as $doc)
				{
					$document = new stdClass;
					//$document->id = $doc->__toString();
					//http://bugs.php.net/bug.php?id=44484
					$document->id = $doc['joomlaID'];
					$document->name = $doc;
					$document->timestamp = $doc['timestamp'];
					$documents[] = $document;
				}
			}
		}

		return $documents;
	}

	/**
	 *
	 * @see http://dtbaker.com.au/random-bits/uploading-a-file-using-curl-in-php.html
	 */
	public function addDocument($id, $document, $path = true)
	{
		$ch = curl_init();

		if(is_object($document)) {
			$data = array(
				'action' => 'addDocument',
				'id' => $id,
				'docName' => $document->title,
				'creationTime' => $document->modified,
				'content'=> $document->text,
				'reportUri' => $document->uri,
			);
		} else {
			$data = array(
				'action' => 'addDocument',
				'id' => $id,
				'docName' => '',
				'creationTime' => '',
				'content'=> $path ? file_get_contents($document) : $document,
			);
		}

		curl_setopt($ch, CURLOPT_URL, $this->getUrl());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encodeData($data));
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		KBIDebug::log($data, "Adding document to source {$this->getName()}");
		KBIDebug::log($info, 'Document add CURL info');
		KBIDebug::log($response, 'Document add CURL response');

		if($info['http_code'] != '200')
		{
			throw new Exception('Error in communication');
		}

		$xml_response = simplexml_load_string($response);

		if($xml_response === FALSE)
		{
			throw new Exception('Unexpected response');
		}

		if(isset($xml_response->error))
		{
			throw new Exception($xml_response->error);
		}
	}

	public function getDocument($id)
	{
		$ch = curl_init();

		$data = array(
			'action' => 'getDocument',
			'id' => $id,
			'content'=> '',
		);

		curl_setopt($ch, CURLOPT_URL, $this->getUrl());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encodeData($data));
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if($info['http_code'] != '200')
		{
			throw new Exception('Error in communication');
		}

		return $response;
	}

	public function deleteDocument($id)
	{
		$ch = curl_init();

		$data = array(
			'action' => 'deleteDocument',
			'id' => $id,
			'content'=> '',
		);

		curl_setopt($ch, CURLOPT_URL, $this->getUrl());
		curl_setopt($ch, CURLOPT_POSTFIELDS, $this->encodeData($data));
		curl_setopt($ch, CURLOPT_VERBOSE, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);

		if($info['http_code'] != '200')
		{
			throw new Exception('Error in communication');
		}
	}

	public function getDataDescription($params = null)
	{
		$postdata = array(
			'action' => 'getDescription',
			'id' => '',
			'content'=> '',
		);

		$url = $this->getUrl();

		KBIDebug::info(array($url, $postdata));
		$dd = $this->requestCurlPost($url, $postdata);

		$replace1 = '<?xml version="1.0" encoding="UTF-8"?>';
		$replace2 = '</result>';
		$replace3 = '/\<result milisecs=".*">/U';
		//$replace3 = '/\<!-- kbiLink({.*\}) \/kbiLink -->/U';

		$dd = str_replace($replace1, '', $dd);
		$dd = str_replace($replace2, '', $dd);
		$dd = preg_replace($replace3, '', $dd);

		return trim($dd);
	}
}