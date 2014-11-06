<?php
/**
 * @version		$Id: Query.php 945 2013-07-01 00:22:10Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

/**
 * Query representation.
 *
 * @package KBI
 */
class KBIQuery
{
	protected $query;
	protected $params;
	protected $params_xslt;
	protected $delimiter;
	protected $options_post;
	protected $options_get;
	//dictionaryquery
	//dictionaryqueryxsl
	//featurelist

	public function getQuery()
	{
		return $this->query;
	}

	public function setQuery($value)
	{
		$this->query = $value;
		return $this;
	}

	public function getParameters()
	{
		return $this->params;
	}

	public function setParameters($value)
	{
		$this->params = $this->recleanup($value);
		return $this;
	}

	public function getXslt()
	{
		return $this->params_xslt;
	}

	public function setXslt($value)
	{
		$this->params_xslt = $value;
		return $this;
	}

	public function getDelimiter()
	{
		return isset($this->delimiter) ? $this->delimiter : ':';
	}

	public function setDelimiter($value)
	{
		$this->delimiter = $value;
		return $this;
	}

	public function getOptions($type = 'GET')
	{
		if(strtolower($type) === 'get')
		{
			return isset($this->options_get) ? $this->options_get : $this->options_get = array();
		}

		if(strtolower($type) === 'post')
		{
			return isset($this->options_post) ? $this->options_post : $this->options_post = array();
		}

		throw new Exception('Only GET and POST method types are supported');
	}

	public function setOptions(array $options, $type = 'GET')
	{
		if(strtolower($type) === 'get')
		{
			$this->options_get = $options;
			return $this;
		}

		if(strtolower($type) === 'post')
		{
			$this->options_post = $options;
			return $this;
		}

		throw new Exception('Only GET and POST method types are supported');
	}

	public function __construct()
	{

	}

	/**
	 * Generates final query from skeleton using parameters and/or XSLT transformation.
	 *
	 * @return string
	 */
	public function proccessQuery(&$options = array())
	{
		$parameters = $this->getParameters();
		$xslt = $this->getXslt();

		/* output parameter */
		$options = array_merge($this->getOptions('GET'), $this->getOptions('POST'));

		if(!empty($parameters))	{
			if(is_array($parameters)) {
				$delimiter = $this->getDelimiter();
				$replace_pairs = array();

				foreach($parameters as $name=>$value)
				{
					$replace_pairs[$delimiter.$name.$delimiter] = $value;
				}

				KBIDebug::log($replace_pairs, 'Applying parameters.');

				$this->query = strtr($this->query, $replace_pairs);
			} elseif(empty($this->query) && is_string($parameters)) {
				// in case query is empty and parameters is string then we assume parameters to be query itself
				KBIDebug::info('Parameters considered as query.');
				$this->setQuery($parameters);
			}
		}

		if(!empty($xslt)) {
			$xml = new DOMDocument();
			if($xml->loadXML($this->query)) {
				// Create XSLT document
				$xsl_document = new DOMDocument();
				$xsl_document->loadXML($xslt, LIBXML_NOCDATA);

				// Process XSLT
				$xslt = new XSLTProcessor();
				$xslt->importStylesheet($xsl_document);

				KBIDebug::info('Applying pre-query transformation.');

				$this->query = $xslt->transformToXML($xml);
			} else {
				KBIDebug::info('Query is not valid XML therefore XSLT could not be executed.');
			}
		}

		return $this->getQuery();
	}

	protected function recleanup($p)
	{
		return str_replace('<!--[CDATA[<?oxygen SCHSchema="http://sewebar.vse.cz/schemas/QueryByAssociationRule0_1.sch"?>', '',
			str_replace(']]-->', '', $p));
	}
}