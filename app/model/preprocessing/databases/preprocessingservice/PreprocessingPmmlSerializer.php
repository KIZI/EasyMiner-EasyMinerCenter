<?php

namespace EasyMinerCenter\app\model\preprocessing\databases\preprocessingservice;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingNotSupportedException;
use Tracy\Debugger;

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
          #region klasický preprocessing pomocí výčtů hodnot či intervalů
          $serializeIntervals=false;
          foreach($preprocessing->valuesBins as $valuesBin){
            if (count($valuesBin->intervals)>0){
              $serializeIntervals=true;
            }
            break;
          }
          if ($serializeIntervals){
            #region serializace preprocessingu pomocí intervalů
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
            #endregion serializace preprocessingu pomocí intervalů
          }else{
            #region serializace preprocessingu pomocí výčtů hodnot
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
            #endregion serializace preprocessingu pomocí výčtů hodnot
          }
          #endregion klasický preprocessing pomocí výčtů hodnot či intervalů
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