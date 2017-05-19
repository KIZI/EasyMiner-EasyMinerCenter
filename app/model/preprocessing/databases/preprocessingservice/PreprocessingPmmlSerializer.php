<?php

namespace EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingNotSupportedException;

/**
 * Class PreprocessingPmmlSerializer - serializer for preparation of PMML for EasyMiner-Preprocessing service
 * @package EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingService
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class PreprocessingPmmlSerializer{


  /**
   * Method for building PMML with settings of preprocessings for individual attributes
   * @param Attribute[] $attributes
   * @return string
   * @throws PreprocessingNotSupportedException
   * @throws \BadMethodCallException
   */
  public static function preparePreprocessingPmml(array $attributes){
    if(!empty($attributes)){
      $pmml=self::prepareBlankPreprocessingPmml();

      foreach($attributes as $attribute){
        $preprocessing=$attribute->preprocessing;
        if ($preprocessing->specialType==Preprocessing::SPECIALTYPE_EACHONE){
          #region each-one
          $derivedField=$pmml->addChild('DerivedField');
          $derivedField->addAttribute('name',$attribute->name);
          $mapValues=$derivedField->addChild('MapValues');
          $mapValues->addAttribute('outputColumn', 'field');
          $fieldColumnPair=$mapValues->addChild('FieldColumnPair');
          $fieldColumnPair->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);
          $fieldColumnPair->addAttribute('column', 'column');
          #endregion each-one
        }elseif($preprocessing->specialType==Preprocessing::SPECIALTYPE_EQUIFREQUENT_INTERVALS){
          #region equifrequent intervals
          $derivedFieldXml=$pmml->addChild('DerivedField');
          $derivedFieldXml->addAttribute('name',$attribute->name);
          $discretizeXml=$derivedFieldXml->addChild('Discretize');
          $discretizeXml->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);

          $preprocessingSpecialParams=$preprocessing->getSpecialTypeParams();

          $algorithmExtensionXml=$discretizeXml->addChild('Extension');
          $algorithmExtensionXml->addAttribute('name','algorithm');
          $algorithmExtensionXml->addAttribute('value','equifrequent-intervals');

          $binsExtensionXml=$discretizeXml->addChild('Extension');
          $binsExtensionXml->addAttribute('name','bins');
          $binsExtensionXml->addAttribute('value',@$preprocessingSpecialParams['count']);

          if (isset($preprocessingSpecialParams['from']) && isset($preprocessingSpecialParams['to'])){
            $leftMarginExtensionXml=$discretizeXml->addChild('Extension');
            $leftMarginExtensionXml->addAttribute('name','leftMargin');
            $leftMarginExtensionXml->addAttribute('value',$preprocessingSpecialParams['from']);
            $rightMarginExtensionXml=$discretizeXml->addChild('Extension');
            $rightMarginExtensionXml->addAttribute('name','rightMargin');
            $rightMarginExtensionXml->addAttribute('value',$preprocessingSpecialParams['to']);
          }
          #endregion equifrequent intervals
        }elseif($preprocessing->specialType==Preprocessing::SPECIALTYPE_EQUISIZED_INTERVALS){
          #region equisized intervals
          $derivedFieldXml=$pmml->addChild('DerivedField');
          $derivedFieldXml->addAttribute('name',$attribute->name);
          $discretizeXml=$derivedFieldXml->addChild('Discretize');
          $discretizeXml->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);

          $preprocessingSpecialParams=$preprocessing->getSpecialTypeParams();

          $algorithmExtensionXml=$discretizeXml->addChild('Extension');
          $algorithmExtensionXml->addAttribute('name','algorithm');
          $algorithmExtensionXml->addAttribute('value','equisized-intervals');

          $binsExtensionXml=$discretizeXml->addChild('Extension');
          $binsExtensionXml->addAttribute('name','support');
          $binsExtensionXml->addAttribute('value',@$preprocessingSpecialParams['support']);

          if (isset($preprocessingSpecialParams['from']) && isset($preprocessingSpecialParams['to'])){
            $leftMarginExtensionXml=$discretizeXml->addChild('Extension');
            $leftMarginExtensionXml->addAttribute('name','leftMargin');
            $leftMarginExtensionXml->addAttribute('value',$preprocessingSpecialParams['from']);
            $rightMarginExtensionXml=$discretizeXml->addChild('Extension');
            $rightMarginExtensionXml->addAttribute('name','rightMargin');
            $rightMarginExtensionXml->addAttribute('value',$preprocessingSpecialParams['to']);
          }
          #endregion equisized intervals
        }elseif($preprocessing->specialType==Preprocessing::SPECIALTYPE_EQUIDISTANT_INTERVALS){
          #region equidistant intervals
          $derivedFieldXml=$pmml->addChild('DerivedField');
          $derivedFieldXml->addAttribute('name',$attribute->name);
          $discretizeXml=$derivedFieldXml->addChild('Discretize');
          $discretizeXml->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);

          $preprocessingSpecialParams=$preprocessing->getSpecialTypeParams();

          $algorithmExtensionXml=$discretizeXml->addChild('Extension');
          $algorithmExtensionXml->addAttribute('name','algorithm');
          $algorithmExtensionXml->addAttribute('value','equidistant-intervals');

          $binsExtensionXml=$discretizeXml->addChild('Extension');
          $binsExtensionXml->addAttribute('name','bins');
          $binsExtensionXml->addAttribute('value',@$preprocessingSpecialParams['count']);

          if (isset($preprocessingSpecialParams['from']) && isset($preprocessingSpecialParams['to'])){
            $leftMarginExtensionXml=$discretizeXml->addChild('Extension');
            $leftMarginExtensionXml->addAttribute('name','leftMargin');
            $leftMarginExtensionXml->addAttribute('value',$preprocessingSpecialParams['from']);
            $rightMarginExtensionXml=$discretizeXml->addChild('Extension');
            $rightMarginExtensionXml->addAttribute('name','rightMargin');
            $rightMarginExtensionXml->addAttribute('value',$preprocessingSpecialParams['to']);
          }
          #endregion equidistant intervals
        }elseif(!empty($preprocessing->valuesBins)){
          #region normal preprocessing using enumerations of values or intervals
          $serializeIntervals=false;
          foreach($preprocessing->valuesBins as $valuesBin){
            if (count($valuesBin->intervals)>0){
              $serializeIntervals=true;
            }
            break;
          }
          if ($serializeIntervals){
            #region serialization of preprocessing using intervals
            $derivedFieldXml=$pmml->addChild('DerivedField');
            $derivedFieldXml->addAttribute('name',$attribute->name);
            $discretizeXml=$derivedFieldXml->addChild('Discretize');
            $discretizeXml->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);
            foreach($preprocessing->valuesBins as $valuesBin){
              $valuesBinName=$valuesBin->name;
              if (!empty($valuesBin->intervals)){
                foreach($valuesBin->intervals as $interval){
                  $discretizeBinXml=$discretizeXml->addChild('DiscretizeBin');
                  $discretizeBinXml->addAttribute('binValue',$valuesBinName);
                  $intervalXml=$discretizeBinXml->addChild('Interval');
                  $intervalXml->addAttribute('closure',$interval->getClosure());
                  $intervalXml->addAttribute('leftMargin',$interval->leftMargin);
                  $intervalXml->addAttribute('rightMargin',$interval->rightMargin);
                }
              }
            }
            #endregion serialization of preprocessing using intervals
          }else{
            #region serialization of preprocessing using values
            $derivedField=$pmml->addChild('DerivedField');
            $derivedField->addAttribute('name',$attribute->name);
            $mapValues=$derivedField->addChild('MapValues');
            $mapValues->addAttribute('outputColumn', 'field');
            $fieldColumnPair=$mapValues->addChild('FieldColumnPair');
            $fieldColumnPair->addAttribute('field', $attribute->datasourceColumn->dbDatasourceFieldId);
            $inlineTable=$mapValues->addChild('InlineTable');
            foreach($preprocessing->valuesBins as $valuesBin){
              $valuesBinName=$valuesBin->name;
              if (!empty($valuesBin->values)){
                foreach($valuesBin->values as $valuesBinValue){
                  $rowXml=$inlineTable->addChild('row');
                  $columnXml=$rowXml->addChild('column');
                  $columnXml[0]=$valuesBinValue->value;
                  $fieldXml=$rowXml->addChild('field');
                  $fieldXml[0]=$valuesBinName;
                }
              }
            }
            #endregion serialization of preprocessing using values
          }
          #endregion normal preprocessing using enumerations of values or intervals
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
   * Method returning blank SimpleXMLElement for preprocessing PMML
   * @return \SimpleXMLElement
   */
  private static function prepareBlankPreprocessingPmml(){
    return simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><TransformationDictionary xmlns="http://www.dmg.org/PMML-4_2"></TransformationDictionary>');
  }

}