<?php

namespace App\Presenters;
use App\Model\Rdf\Entities\Attribute;
use App\Model\Rdf\Entities\BaseEntity;
use App\Model\Rdf\Entities\Cedent;
use App\Model\Rdf\Entities\Format;
use App\Model\Rdf\Entities\Interval;
use App\Model\Rdf\Entities\IntervalClosure;
use App\Model\Rdf\Entities\KnowledgeBase;
use App\Model\Rdf\Entities\Rule;
use App\Model\Rdf\Entities\RuleAttribute;
use App\Model\Rdf\Entities\RuleSet;
use App\Model\Rdf\Entities\Value;
use App\Model\Rdf\Entities\ValuesBin;
use Nette\Application\BadRequestException;
use Nette\Utils\Strings;

/**
 * Třída pro aktuální integraci s EasyMinerem
 * TODO předělat na novou verzi easymineru...
 * Class AssociationRules
 * @package App\Presenters
 */
class AssociationRulesPresenter extends BaseRestPresenter{

  private $intervalClosuresArr=array();

  /**
   * Funkce pro získání instance KnowledgeBase (pokud neexistuje, je vygenerována nová...)
   * @param $baseId
   * @return KnowledgeBase|null
   */
  private function getKnowledgeBase($baseId){
    try{
      $knowledgeBase=$this->knowledgeRepository->findKnowledgeBase($baseId);
      if ($knowledgeBase && ($knowledgeBase instanceof KnowledgeBase)){
        return $knowledgeBase;
      }else{
        $knowledgeBase=new KnowledgeBase();
        if ($baseId!=''){
          $knowledgeBase->uri=$baseId;
        }
        $knowledgeBase->name='generated '.date('Y-m-d H:i:s');
        $this->knowledgeRepository->saveKnowledgeBase($knowledgeBase);
        return $knowledgeBase;
      }
    }catch (\Exception $e){
      return null;
    }
  }

  /**
   * Funkce pro získání instance RuleSet (pokud neexistuje, je vygenerován nový...)
   * @param $baseId
   * @param $kbi
   * @return RuleSet|null
   */
  private function getRuleSet($baseId,$kbi){
    $uri=BaseEntity::BASE_ONTOLOGY.'/RuleSet/'.Strings::webalize($kbi);
    try{
      $ruleSet=$this->knowledgeRepository->findRuleSet($uri);
      if ($ruleSet && ($ruleSet instanceof RuleSet)){
        return $ruleSet;
      }else{
        $ruleSet=new RuleSet();
        $ruleSet->uri=$uri;
        $ruleSet->name='KBI_'.$kbi;
        $ruleSet->knowledgeBase=$this->getKnowledgeBase($baseId);
        $this->knowledgeRepository->saveRuleSet($ruleSet);
        return $ruleSet;
      }
    }catch (\Exception $e){
      return null;
    }
  }

  /**
   * Akce pro import pravidel z XML s asociačními pravidly
   * @param string $baseId
   * @param string $kbi
   * @param null $data
   * @throws \Nette\Application\BadRequestException
   */
  public function actionImportAssociationRules($baseId='',$kbi='',$data=null){
    #region příprava dat, vytvoření SimpleXML
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('Preprocessing data are missing!');
      }
    }

    $xml=simplexml_load_string($data);
    #endregion příprava dat, vytvoření SimpleXML

    if ($xml->AssociationRule && count($xml->AssociationRule)>0){
      $ruleSet=$this->getRuleSet($baseId,$kbi);
      $rulesArr=$ruleSet->rules;
      foreach ($xml->AssociationRule as $xmlAssociationRule){
        #region projdeme jednotlivá asociační pravidla a naimportujeme je do báze...
        $rule=new Rule();
        $rule->text=(string)$xmlAssociationRule->Text;
        $rule->antecedent=$this->prepareCedentFromXml($kbi,$xmlAssociationRule->Antecedent);
        $rule->consequent=$this->prepareCedentFromXml($kbi,$xmlAssociationRule->Consequent);
        $rule->knowledgeBase=$this->getKnowledgeBase($baseId);

        $fourFtTable=$xmlAssociationRule->FourFtTable;
        $fourFtA=floatval((string)$fourFtTable['a']);
        $fourFtB=floatval((string)$fourFtTable['b']);
        $fourFtC=floatval((string)$fourFtTable['c']);
        $fourFtD=floatval((string)$fourFtTable['d']);

        $rule->setRating(array('confidence'=>(round(100*$fourFtA/($fourFtA+$fourFtB))/100),'support'=>(round(100*$fourFtA/($fourFtA+$fourFtB+$fourFtC+$fourFtD))/100)));
        $rulesArr[]=$rule;
        //$this->knowledgeRepository->saveRule($rule);
        #endregion projdeme jednotlivá asociační pravidla a naimportujeme je do báze...
      }
      $ruleSet->rules=$rulesArr;
      $this->knowledgeRepository->saveRuleSet($ruleSet);
    }
    $this->sendTextResponse('OK');
  }

  /**
   * Private funkce pro připravení Cedentu na základě zaslaného XML s asociačními pravidly
   * @param string $kbi
   * @param \SimpleXMLElement $xml
   * @return Cedent
   */
  private function prepareCedentFromXml($kbi,$xml){
    $cedent=new Cedent();
    if (isset($xml['connective'])){
      $cedent->connective=(string)$xml['connective'];
    }else{
      $cedent->connective='Conjunction';
    }

    if (count($xml->Cedent)){
      $cedentsArr=array();
      foreach ($xml->Cedent as $xmlCedent){
        $cedentsArr[]=$this->prepareCedentFromXml($kbi,$xmlCedent);
      }
      $cedent->cedents=$cedentsArr;
    }

    if (count($xml->Attribute)){
      $ruleAttributesArr=array();
      foreach ($xml->Attribute as $xmlRuleAttribute){
        $ruleAttributesArr[]=$this->prepareRuleAttributeFromXml($kbi,$xmlRuleAttribute);
      }
      $cedent->ruleAttributes=$ruleAttributesArr;
    }
    return $cedent;
  }

  /**
   * Private funkce pro připravení RuleAttributu na základě zaslaného XML s asociačními pravidly
   * @param string $kbi
   * @param \SimpleXMLElement $xml
   * @return RuleAttribute
   */
  private function prepareRuleAttributeFromXml($kbi,$xml){
    $ruleAttribute=new RuleAttribute();
    $attributeUri=BaseEntity::BASE_ONTOLOGY.'/Attribute/'.Strings::webalize($kbi).'/'.Strings::webalize((string)$xml->Name);
    $ruleAttribute->attribute=$this->knowledgeRepository->findAttribute($attributeUri);
    $valuesBinsArr=array();
    if (isset($xml->Category) && count($xml->Category)){
      //projdeme jednotlivé kategorie
      foreach ($xml->Category as $xmlCategory){
        $valuesBin=new ValuesBin();//TODO dodělat načítání BINů!!!
        $valuesBin->uri=$attributeUri.'/'.Strings::webalize((string)$xmlCategory->Name);
        $valuesBin->setChanged(false);
        $valuesBinsArr[]=$valuesBin;
      }
    }
    $ruleAttribute->valuesBins=$valuesBinsArr;
    return $ruleAttribute;
  }

  /**
   * Akce pro import DataDictionary z XML z EasyMineru
   * @param string $baseId
   * @param string $kbi
   * @param null $data
   * @throws \Nette\Application\BadRequestException
   */
  public function actionImportDataDescription($baseId='',$kbi='',$data=null){
    #region příprava dat, vytvoření SimpleXML
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('Preprocessing data are missing!');
      }
    }

    $xml=simplexml_load_string($data);
    #endregion příprava dat, vytvoření SimpleXML

    if ((count($xml->Field)>0)&&(count($xml->Field->Category)>0)){
      foreach ($xml->Field as $xmlField){
        $name=(string)$xmlField->Name;
        $attribute=new Attribute();
        $attribute->name=$name;
        //TODO pracovní přiřazení URI
        $uri=BaseEntity::BASE_ONTOLOGY.'/Attribute/'.Strings::webalize($kbi).'/'.Strings::webalize($name);
        //$uri=$attribute->prepareBaseUri();
        if ($this->knowledgeRepository->findAttribute($uri)){
          continue; //v atributech nemohou být v LM změny => pokračujeme k dalšímu...
        }else{
          $attribute->uri=$uri;
        }
        $this->knowledgeRepository->saveAttribute($attribute);
        $format=new Format();
        $format->uri=BaseEntity::BASE_ONTOLOGY.'/Format/'.Strings::webalize($kbi).'/'.Strings::webalize($name);
        $format->name=$name;
        $format->dataType=(string)$xmlField['dataType'];

        $attribute->dbColumn=(string)$xmlField->DbColumn;

        //projdeme jednotlivé kategorie
        $valuesBinsArr=array();
        foreach ($xmlField->Category as $xmlCategory){
          $valuesBin=new ValuesBin();
          $valuesBin->uri=$uri.'/'.Strings::webalize((string)$xmlCategory->Name);
          $valuesBin->name=(string)$xmlCategory->Name;
          $xmlCategoryData=$xmlCategory->Data;
          if (count($xmlCategoryData->Interval)>0){
            $intervalsMin=9999999999999;
            $intervalsMax=-9999999999999;
            $valuesBin->intervals=array();
            foreach ($xmlCategoryData->Interval as $xmlInterval){
              $interval=new Interval();
              $closureStr=(string)$xmlInterval['closure'];
              if (isset($this->intervalClosuresArr[$closureStr])){
                $interval->setClosure($this->intervalClosuresArr[$closureStr]);
              }else{
                $closure=new IntervalClosure();
                $closure->setClosure($closureStr);
                $interval->setClosure($closure);
                $this->intervalClosuresArr[$closureStr]=$closure;
              }
              $leftMargin=(string)$xmlInterval['leftMargin'];
              if ($leftMargin<$intervalsMin){$intervalsMin=$leftMargin;}
              $leftMarginValue=new Value();
              $leftMarginValue->setValue($leftMargin);
              $interval->leftMargin=$leftMarginValue;
              $rightMargin=(string)$xmlInterval['rightMargin'];
              if ($rightMargin>$intervalsMax){$intervalsMax=$rightMargin;}
              $rightMarginValue=new Value();
              $rightMarginValue->setValue($rightMargin);
              $interval->rightMargin=$rightMarginValue;
              $valuesBin->intervals[]=$interval;
            }
            $rangeInterval=new Interval();
            if (isset($this->intervalClosuresArr['closedClosed'])){
              $rangeInterval->setClosure($this->intervalClosuresArr['closedClosed']);
            }else{
              $intervalClosure=new IntervalClosure();
              $intervalClosure->setClosure('closedClosed');
              $rangeInterval->setClosure($intervalClosure);
            }
            $leftMarginValue=new Value();
            $leftMarginValue->setValue($intervalsMin);
            $rangeInterval->leftMargin=$leftMarginValue;
            $rightMarginValue=new Value();
            $rightMarginValue->setValue($intervalsMax);
            $rangeInterval->rightMargin=$rightMarginValue;
            $format->intervals=array($rangeInterval);
          }
          if (count($xmlCategoryData->Value)>0){
            $valuesBin->values=array();
            $format->values=array();
            foreach ($xmlCategoryData->Value as $xmlValue){
              $value=new Value();
              $value->value=(string)$xmlValue;
              $valuesBin->values[]=$value;
              $format->values[]=$value;
            }
          }
          $valuesBinsArr[]=$valuesBin;
        }
        $attribute->knowledgeBase=$this->getKnowledgeBase($baseId);
        $attribute->valuesBins=$valuesBinsArr;
        //$this->knowledgeRepository->saveFormat($format);
        $attribute->format=$format;
        $this->knowledgeRepository->saveAttribute($attribute);
      }
      $this->sendTextResponse('OK');
    }

  }



} 