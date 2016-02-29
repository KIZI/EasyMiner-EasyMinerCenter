<?php
namespace EasyMinerCenter\Model\Translation;


use Nette\Localization\ITranslator;

/**
 * Interface EasyMinerTranslator -
 * @package EasyMinerCenter\Model\Translation
 */
interface EasyMinerTranslator extends ITranslator{

  /**
   * Funkce vracející aktuálně nastavený jazyk
   * @return string
   */
  public function getLang();

}