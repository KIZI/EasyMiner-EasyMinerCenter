<?php
/**
 * @version		$Id: IHasDataDictionary.php 845 2013-04-10 06:28:35Z hazucha.andrej $
 * @package		KBI
 * @author		Andrej Hazucha
 * @copyright	Copyright (C) 2010 All rights reserved.
 * @license		GNU/GPL, see LICENSE.php
 */

namespace KBI;

interface IHasDataDictionary
{
	public function getDataDescription($params=null);
}