<?php

namespace App\Model\EasyMiner\Serializers;

use Nette\Utils\Json;

class TaskSettingsSerializer {
  /** @var  \SimpleXMLElement $pmml */
  protected $pmml;

  protected $id = 0;
  /** @var  \SimpleXMLElement $arQuery */
  protected $arQuery;
  /** @var  \SimpleXMLElement $bbaSettings */
  protected $bbaSettings;
  /** @var  \SimpleXMLElement $dbaSettings */
  protected $dbaSettings;
  /** @var  \SimpleXMLElement $antecedentSetting */
  protected $antecedentSetting;
  /** @var  \SimpleXMLElement $consequentSetting */
  protected $consequentSetting;
  /** @var  \SimpleXMLElement $interestMeasureSetting */
  protected $interestMeasureSetting;

  protected static $BOOLEAN_TYPES = ['neg' => 'Negation', 'and' => 'Conjunction', 'or' => 'Disjunction', 'lit' => 'Literal'];
  protected static $FORCE_DEPTH_BOOLEAN = 'Conjunction';
  protected static $LITERAL = 'Literal';
  protected static $LITERALS = ['Literal'];
  protected static $CEDENT_MINIMAL_LENGTH = 1;
  protected static $PCEDENT_MINIMAL_LENGTH = 0; // partial cedent

  #region construct, (get|set)Pmml
  /**
   * @param \SimpleXMLElement|string|null $pmml
   */
  public function __construct($pmml = null) {
    if (!empty($pmml)){
      $this->setPmml($pmml);
    }
  }

  /**
   * @param \SimpleXMLElement|string $pmml
   */
  public function setPmml($pmml){
    if ($pmml instanceof \SimpleXMLElement){
      $this->pmml=$pmml;
    }else{
      $this->pmml=simplexml_load_string($pmml);
    }
  }

  /**
   * Funkce vracející SimpleXML s PMML...
   * @return \SimpleXMLElement
   */
  public function getPmml(){
    return $this->pmml;
  }
  #endregion

  /**
   * @param string|object $json
   * @param int $forcedDepth
   * @return \SimpleXMLElement
   */
  public function settingsFromJson($json, $forcedDepth = 3) {
    if (is_string($json)){
      $json=Json::decode($json);
    }
    $strict = $json->strict;
    $rule = $json->rule0;

    // Create basic structure of Document.
    $this->init($json->taskId, $json->limitHits);

    // Create antecedent
    if (!empty($rule->antecedent->children)) {
      $antecedentId = $this->parseCedent($rule->antecedent, 1, $forcedDepth, $strict);
      $this->antecedentSetting[0]=(string)$antecedentId;
    }

    // IMs
    foreach ($rule->IMs as $IM) {
      $this->createInterestMeasureThreshold($IM);
    }

    // Create consequent
    $consequentId = $this->parseCedent($rule->succedent, 1, $forcedDepth, $strict);
    $this->consequentSetting[0]=(string)$consequentId;

    return $this->pmml;
  }

  /**
   * Funkce pro výchozí inicializaci...
   * @param string $modelName
   * @param int $hypothesesCountMax
   */
  private function init($modelName, $hypothesesCountMax) {
    /** @var \SimpleXMLElement $guhaAssociationModelXml */
    $guhaAssociationModelXml=$this->pmml->children('guha',true)[0];
    $guhaAssociationModelXml['modelName']=$modelName;
    $guhaAssociationModelXml['functionName']='associationRules';
    $guhaAssociationModelXml['algorithmName']='4ft';

    // create TaskSetting
    if (isset($guhaAssociationModelXml->TaskSetting)){
      $this->arQuery = $guhaAssociationModelXml->TaskSetting[0];
    }else{
      $this->arQuery = $guhaAssociationModelXml->addChild("TaskSetting",null,'');
    }
    //TODO kontrola, jestli daná extension zatím neexistuje...
    // extension LISp-Miner
    $extension = $this->arQuery->addChild('Extension',null,'');
    $extension->addAttribute('name', 'LISp-Miner');
    $extension->addChild('HypothesesCountMax', $hypothesesCountMax,'');

    if (isset($this->arQuery->BBASettings)){
      $this->bbaSettings = $this->arQuery->BBASettings;
    }else{
      $this->bbaSettings = $this->arQuery->addChild("BBASettings",null,'');
    }
    if (isset($this->arQuery->DBASettings)){
      $this->dbaSettings = $this->arQuery->DBASettings;
    }else{
      $this->dbaSettings = $this->arQuery->addChild("DBASettings",null,'');
    }
    if (isset($this->arQuery->AntecedentSetting)){
      $this->antecedentSetting = $this->arQuery->AntecedentSetting;
    }else{
      $this->antecedentSetting = $this->arQuery->addChild("AntecedentSetting",null,'');
    }
    if (isset($this->arQuery->ConsequentSetting)){
      $this->consequentSetting = $this->arQuery->ConsequentSetting;
    }else{
      $this->consequentSetting = $this->arQuery->addChild("ConsequentSetting",null,'');
    }
    if (isset($this->arQuery->InterestMeasureSetting)){
      $this->interestMeasureSetting = $this->arQuery->InterestMeasureSetting;
    }else{
      $this->interestMeasureSetting = $this->arQuery->addChild("InterestMeasureSetting",null,'');
    }
  }

  public function generateId() {
    return ++$this->id;
  }

  protected function createInterestMeasureThreshold($IM) {
    /** @var \SimpleXMLElement $interestMeasureThreshold */
    $interestMeasureThreshold = $this->interestMeasureSetting->addChild("InterestMeasureThreshold");
    $interestMeasureThreshold->addAttribute("id", $this->generateId());
    $interestMeasureThreshold->addChild("InterestMeasure", $IM->name);
    foreach ($IM->fields as $f) {
      if ($f->name === 'threshold') {
        $interestMeasureThreshold->addChild("Threshold", $f->value);
      } elseif ($f->name === 'alpha') {
        $interestMeasureThreshold->addChild("SignificanceLevel", $f->value);
      }
    }
    $interestMeasureThreshold->addChild("ThresholdType", $IM->thresholdType);
    $interestMeasureThreshold->addChild("CompareType", $IM->compareType);
  }

  protected function parseCedent($cedent, $level, $forcedDepth, $strict) {
    $connective = $this->getBooleanName($cedent->connective->type);

    $attributes = [];
    $cedentIds = [];
    foreach ($cedent->children as $child) {
      if (isset($child->type) && $child->type === 'cedent') {
        $cedentId = $this->parseCedent($child, ($level + 1), $forcedDepth, $strict);
        array_push($cedentIds, $cedentId);
      } else {
        array_push($attributes, $child);
      }
    }

    $cedentId = $this->createDbaSetting($connective, $attributes, $level, $forcedDepth, $cedentIds, $strict);

    return $cedentId;
  }

  protected function createDbaSetting($connective, $attributes, $level, $forcedDepth, $cedentIds, $strict) {
    $dbaId = $this->generateId();
    $dbaSettingXml = $this->dbaSettings->addChild("DBASetting");
    $dbaSettingXml->addAttribute("id", $dbaId);

    if (!$this->isLiteral($connective)) {
      if ($level === 1 && $connective === 'Disjunction') { // Disjunction is not permitted on level 1
        $dbaSettingXml->addAttribute("type", self::$FORCE_DEPTH_BOOLEAN);
        $nextLevel = $level + 1;
        $baSettingRefId = $this->createDbaSetting($connective, $attributes, $nextLevel, $forcedDepth, $cedentIds, $strict);
        $dbaSettingXml->addChild("BASettingRef", $baSettingRefId);
      } else {
        $dbaSettingXml->addAttribute("type", $connective);
        foreach ($attributes as $attribute) {
          $nextLevel = $level + 1;
          $dbaConnective = $nextLevel === $forcedDepth ? self::$LITERAL : self::$FORCE_DEPTH_BOOLEAN;
          $baSettingRefId = $this->createDbaSetting($dbaConnective, [$attribute], $nextLevel, $forcedDepth, [], $strict);
          $dbaSettingXml->addChild("BASettingRef", $baSettingRefId);
        }

        foreach ($cedentIds as $cedentId) {
          $dbaSettingXml->addChild("BASettingRef", $cedentId);
        }
      }

      if ($level === 2) {
        $minimalLength = self::$PCEDENT_MINIMAL_LENGTH;
        if ($strict) {
          if ($connective === 'Conjunction') {
            $minimalLength = count($attributes);
          } else { // $connective === 'Disjunction'
            $minimalLength = 1;
          }
        }
        $dbaSettingXml->addChild("MinimalLength", $minimalLength);
      } else {
        $dbaSettingXml->addChild("MinimalLength", self::$CEDENT_MINIMAL_LENGTH);
      }
    } else { // literal
      $dbaSettingXml->addAttribute("type", $connective);
      $attribute = $attributes[0];

      $baSettingRefId = $this->createBbaSetting($attribute);
      $dbaSettingXml->addChild("BASettingRef", $baSettingRefId);

      $literalSignText = $attribute->sign === 'positive' ? "Positive" : "Negative";
      $dbaSettingXml->addChild("LiteralSign", $literalSignText);
    }

    return $dbaId;
  }

  protected function createBbaSetting($attribute) {
    $bbaId = $this->generateId();
    $bbaSettingXml = $this->bbaSettings->addChild("BBASetting");
    $bbaSettingXml->addAttribute('id', $bbaId);
    $bbaSettingXml->addChild("Text", $attribute->name);
    $bbaSettingXml->addChild("Name", $attribute->name);
    $bbaSettingXml->addChild("FieldRef", $attribute->name);
    $this->createCoefficient($bbaSettingXml,$attribute);
    return $bbaId;
  }

  protected function createCoefficient(\SimpleXMLElement $parentElement,$attribute){
    $coefficientXml = $parentElement->addChild("Coefficient");
    $coefficientXml->addChild("Type", $attribute->category);

    if ($attribute->category == 'One category') {
      $coefficientXml->addChild("Category", $attribute->fields[0]->value);
    } else {
      $fieldsLength = count($attribute->fields);
      $minLength = null;
      $maxLength = null;
      if ($fieldsLength < 1) {
        $minLength = 0;
      } else {
        $minLength = intval($attribute->fields[0]->value);
      }

      if ($fieldsLength < 2) {
        $maxLength = 9999;
      } else {
        $maxLength = intval($attribute->fields[1]->value);
      }

      $coefficientXml->addChild("MinimalLength", $minLength);
      $coefficientXml->addChild("MaximalLength", $maxLength);
    }

    return $coefficientXml;
  }

  public function getBooleanName($type) {
    return self::$BOOLEAN_TYPES[$type];
  }

  public function isLiteral($literal) {
    return in_array($literal, self::$LITERALS);
  }

}