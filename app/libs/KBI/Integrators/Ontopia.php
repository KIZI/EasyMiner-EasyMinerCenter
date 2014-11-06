<?php
/**
 * @version		$Id: Ontopia.php 64 2011-03-01 11:19:29Z hazucha.andrej@gmail.com $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;
//require_once dirname(__FILE__).'/../KBIntegrator.php';

/**
 * IKBIntegrator implementation for OKS.
 *
 * @package KBI
 */
class Ontopia extends KBIntegrator
{
	public function getSyntax()
	{
		return isset($this->config['syntax']) ? $this->config['syntax'] : '';
	}

	public function setSyntax($value)
	{
		$this->config['syntax'] = $value;
	}

	public function getTopicMap()
	{
		return isset($this->config['topicmap']) ? $this->config['topicmap'] : '';
	}

	public function setTopicMap($value)
	{
		$this->config['topicmap'] = $value;
	}

	public function __construct($config)
	{
		parent::__construct($config);
	}

	public function queryGet($query)
	{
		$url = $this->getUrl();

		$data = array(
			'topicmap' => $this->getTopicMap(),
			'tolog' => $query
		);

		if($this->getSyntax() != '')
		{
			$data['syntax'] = $this->getSyntax();
		}

		KBIDebug::info(array($url, $data));

		return $this->requestCurl($url, $data);
	}

	protected function querySoap($query) {
		//"http://nlp.vse.cz:8080/tmrap/services/TMRAPService?wsdl"
		$client = new SoapClient($this->getUrl());

		$params = array(
			'query' => $query,
			'tmid' => $this->getTopicMap(),
		);

		if($this->getSyntax() != '')
		{
			$params['syntax'] = $this->getSyntax();
		}

		$r = $client->getTolog($params);

		return ($r->any);
	}
}