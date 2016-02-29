<?php

namespace EasyMinerCenter\Model\Translation;

/**
 * Class BlankTranslator - prázdná třída pro zajištění lokalizací (bude implementováno v budoucnu)
 * @package EasyMinerCenter\Model
 */
class BlankTranslator implements EasyMinerTranslator{

  /**
   * Translates the given string.
   * @param  string   $message
   * @param  int      $count plural
   * @return string
   */
  function translate($message, $count = NULL) {
    return $message;
  }

  /**
   * @return string
   */
  public function getLang() {
    return "en";
  }
}