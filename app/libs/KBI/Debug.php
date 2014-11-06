<?php
/**
 * @version		$Id: Debug.php 945 2013-07-01 00:22:10Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

if(!class_exists('FirePHP'))
{
	include_once dirname(__FILE__) . '/FirePHPCore/fb.php';
}

/**
 * Wrapper for debugging tools (FirePHP, file...).
 *
 * @package KBI
 */
class KBIDebug
{
	/** @var FirePHP */
	protected static $singleton;

	protected static function getInstance()
	{
		return FirePHP::getInstance(true);
	}

	public static function log($Object, $Label=null)
	{
		$instance = self::getInstance();
		$instance->log($Object, $Label);
	}

	public static function info($Object, $Label=null)
	{
		$instance = self::getInstance();
		$instance->info($Object, $Label);
	}

	public static function setEnabled($enabled)
	{
		$instance = self::getInstance();
		$instance->setEnabled($enabled);
	}
}