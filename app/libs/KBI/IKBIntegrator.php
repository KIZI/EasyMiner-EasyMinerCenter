<?php
/**
 * @version		$Id: IKBIntegrator.php 64 2011-03-01 11:19:29Z hazucha.andrej@gmail.com $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

//require_once 'Debug.php';

/**
 * Interface for communication between CMSs and KBs.
 *
 * @package KBI
 */
interface IKBIntegrator
{
	public function getUrl();
	public function setUrl($value);

	/**
	 * Generic implementation for IKBIntegrator.
	 *
	 * @param KBIQuery | array | string $query
	 * @param unknown_type $xsl
	 */
	public function query($query, $xsl = '');
}