<?php
/**
 * @version		$Id: Jucene.php 945 2013-07-01 00:22:10Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

//require_once dirname(__FILE__) . '/../KBIntegratorSynchronable.php';

/**
 * IKBIntegrator implementation for jucene (Joomla + Lucene).
 *
 * @package KBI
 */
class Jucene extends KBIntegratorSynchronable
{
	public function getMethod()
	{
		return isset($this->config['method']) ? $this->config['method'] : 'POST';
	}

	public function __construct(Array $config)
	{
		parent::__construct($config);
	}

	/**
	 * Method called when querying with POST data. Service should return XML with query results.
	 *
	 * @return string  XML with query results
	 */
	public function queryPost($query, $options)
	{
		$server = $this->getUrl();
		//$server = 'http://joomla.drupaler.cz';
		//$url = 'http://joomla.drupaler.cz/component/jucene/DistrictR-Praha/?sorting=SORT_STRING&ordering=';
		$url = "$server/index.php?option=com_jucene&controller=ApiKbi";

		//$post['sorting']	= JRequest::getWord('sorting', 'SORT_STRING', 'post');
		//$post['ordering']	= JRequest::getWord('ordering', null, 'post');
		//$post['limit']  = JRequest::getInt('limit', null, 'post');

		$postdata = array(
			'searchword' => $query,
			'sorting' => 'SORT_STRING',
			'ordering' => ''
		);

		return $this->requestCurlPost($url, $postdata);
	}

	/**
	 *
	 * @return string  XML with indexed documents
	 */
	public function getDocuments()
	{
		$ch = curl_init();
		$documents = array();

		$data = array(
			'action' => 'getDocuments',
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
			
			throw new Exception('Error in communication with code:'.$info['http_code']);
			
		}
		else
		{
			$json = json_decode($response);
			$docs = $json[0];
			if(!empty($docs))
			{
				foreach($docs as $doc)
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
	 * Sends PMML document to be indexed to service
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

		KBIDebug::log($data);
		KBIDebug::log($info);
		KBIDebug::log($response);
		
		var_dump($data);
		var_dump($info);
		var_dump($response);
		if($info['http_code'] != '200')
		{
			throw new Exception('Error in communication');
		}

		
	}

	/**
	 *
	 * @return string  indexed PMML document
	 */
	public function getDocument($id)
	{
	}

	/**
	 *
	 * Trigers document to be deleted from service.
	 */
	public function deleteDocument($id)
	{
	}

	/**
	 *
	 * @return string  data description from PMML documents if possible
	 */
	public function getDataDescription($params = null)
	{
		return '';
	}
}