<?php

namespace App\Model;


use Nette\Localization\ITranslator;

/**
 * Class BlankTranslator - prázdná třída pro zajištění lokalizací (bude implementováno v budoucnu)
 * @package App\Model
 */
class BlankTranslator implements ITranslator{

  /**
   * Translates the given string.
   * @param  string   $message
   * @param  int      $count plural
   * @return string
   */
  function translate($message, $count = NULL) {
    return $message;
  }
}