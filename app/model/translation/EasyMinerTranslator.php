<?php
namespace EasyMinerCenter\Model\Translation;


use Nette\Localization\ITranslator;

/**
 * Interface EasyMinerTranslator
 * @package EasyMinerCenter\Model\Translation
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
interface EasyMinerTranslator extends ITranslator{

  /**
   * Method returning the actual language
   * @return string
   */
  public function getLang();

}