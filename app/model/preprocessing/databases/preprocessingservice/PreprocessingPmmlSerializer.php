<?php

namespace EasyMinerCenter\app\model\preprocessing\databases\preprocessingservice;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingNotSupportedException;

/**
 * Class PreprocessingPmmlSerializer - třída pro přípravu PMML pro preprocessing službu
 * @package EasyMinerCenter\app\model\preprocessing\databases\preprocessingservice
 * @author Stanislav Vojíř
 */
class PreprocessingPmmlSerializer{


  /**
   * Funkce pro sestavení PMML se zadáním preprocessingu jednotlivých atributů
   *
   * @param Attribute[] $attributes
   * @return string
   * @throws PreprocessingNotSupportedException
   * @throws \BadMethodCallException
   */
  public static function preparePreprocessingPmml(array $attributes){
    if(!empty($attributes)){
      $pmml=self::prepareBlankPreprocessingPmml();

      foreach($attributes as $attribute){
        if ($attribute->preprocessing->specialType==Preprocessing::SPECIALTYPE_EACHONE){
          //jde o preprocessing each-one => připravíme PMML se zadáním
          $derivedField=$pmml->addChild('DerivedField');
          $derivedField->addAttribute('name',$attribute->name);
          $mapValues=$derivedField->addChild('MapValues');
          $mapValues->addAttribute('outputColumn', 'field');
          $fieldColumnPair=$mapValues->addChild('FieldColumnPair');
          $fieldColumnPair->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);
          $fieldColumnPair->addAttribute('column', 'column');
        }else{
          throw new PreprocessingNotSupportedException('Selected preprocessing type is not supported.');
        }
      }

      return $pmml->asXML();
    }else{
      throw new \BadMethodCallException('It is required to input at least one attribute for preprocessing!');
    }
  }

  /**
   * Funkce vracející kostru prázdného PMML pro preprocessing
   * @return \SimpleXMLElement
   */
  private static function prepareBlankPreprocessingPmml(){
    return simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><TransformationDictionary xmlns="http://www.dmg.org/PMML-4_2"></TransformationDictionary>');
  }

}