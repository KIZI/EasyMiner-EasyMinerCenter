<?php

namespace EasyMinerCenter\Model\Translation;

/**
 * Class BlankTranslator - dummy translator class (for future implementation of localizations)
 * @package EasyMinerCenter\Model\Translation
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
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
   * Method returning the actual language
   * @return string
   */
  public function getLang() {
    return "en";
  }
}