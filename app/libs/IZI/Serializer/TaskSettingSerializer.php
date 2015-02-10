<?php

namespace IZI\Serializer;

use IZI\FileLoader\XMLLoader;

class TaskSettingSerializer
{
    protected $DD;
    protected $ddXpath;

    protected $id = 0;

    // XML document
    protected $output;
    protected $arQuery;
    protected $bbaSettings;
    protected $dbaSettings;
    protected $antecedentSetting;
    protected $consequentSetting;
    protected $interestMeasureSetting;

    protected static $BOOLEAN_TYPES = ['neg' => 'Negation', 'and' => 'Conjunction', 'or' => 'Disjunction', 'lit' => 'Literal'];
    protected static $FORCE_DEPTH_BOOLEAN = 'Conjunction';
    protected static $LITERAL = 'Literal';
    protected static $LITERALS = ['Literal'];
    protected static $CEDENT_MINIMAL_LENGTH = 1;
    protected static $PCEDENT_MINIMAL_LENGTH = 0; // partial cedent

    public function __construct($DDPath)
    {
        $loader = new XMLLoader();
        $this->DD = $loader->load($DDPath);

        // init XPath
        $this->ddXpath = new \DOMXPath($this->DD);
        $this->ddXpath->registerNamespace('dd', "http://keg.vse.cz/ns/datadescription0_2");
    }

    public function serialize($json, $forcedDepth = 3)
    {
        $json = json_decode($json);
        $strict = $json->strict;
        $rule = $json->rule0;

        // Create basic structure of Document.
        $this->createBasicStructure($json->taskId, $json->limitHits);

        // Create antecedent
        if (!empty($rule->antecedent->children)) {
            $antecedentId = $this->parseCedent($rule->antecedent, 1, $forcedDepth, $strict);
            $this->antecedentSetting->appendChild($this->output->createTextNode($antecedentId));
        }

        // IMs
        foreach ($rule->IMs as $IM) {
            $this->createInterestMeasureThreshold($IM);
        }

        // Create consequent
        $consequentId = $this->parseCedent($rule->succedent, 1, $forcedDepth, $strict);
        $this->consequentSetting->appendChild($this->output->createTextNode($consequentId));

        // Serialize XML
        return $this->output->saveXML();
    }

    protected function createBasicStructure($modelName, $hypothesesCountMax)
    {
        $this->output = new \DOMDocument("1.0", "UTF-8");

        // add schematron validation
        $pi = $this->output->createProcessingInstruction('oxygen', 'SCHSchema="http://sewebar.vse.cz/schemas/GUHARestr0_1.sch"');
        $this->output->appendChild($pi);

        // create PMML
        $pmml = $this->output->createElement('PMML');
        $pmml->setAttribute('version', '4.0');
        $pmml->setAttribute('xmlns', 'http://www.dmg.org/PMML-4_0');
        $pmml->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $pmml->setAttribute('xmlns:pmml', 'http://www.dmg.org/PMML-4_0');
        $pmml->setAttribute('xsi:schemaLocation', 'http://www.dmg.org/PMML-4_0 http://sewebar.vse.cz/schemas/PMML4.0+GUHA0.1.xsd');
        $root = $this->output->appendChild($pmml);

        // add Header
        $header = $this->output->createElement('Header');
        $header->setAttribute('copyright', 'Copyright (c) KIZI UEP');
        $ext = $this->output->createElement('Extension');
        $ext->setAttribute('name', 'dataset');
        $ext->setAttribute('value', $this->ddXpath->query("//dd:DataDescription/Dictionary/@sourceName")->item(0) ? $this->ddXpath->query("//dd:DataDescription/Dictionary/@sourceName")->item(0)->value : 'Loans');
        $header->appendChild($ext);
        $ext = $this->output->createElement('Extension');
        $ext->setAttribute('name', 'author');
        $ext->setAttribute('value', 'admin');
        $header->appendChild($ext);
        $ext = $this->output->createElement('Extension');
        $ext->setAttribute('name', 'subsystem');
        $ext->setAttribute('value', '4ft-Miner');
        $header->appendChild($ext);
        $ext = $this->output->createElement('Extension');
        $ext->setAttribute('name', 'module');
        $ext->setAttribute('value', '4ftResult.exe');
        $header->appendChild($ext);
        $ext = $this->output->createElement('Extension');
        $ext->setAttribute('name', 'format');
        $ext->setAttribute('value', '4ftMiner.Task');
        $header->appendChild($ext);
        $app = $this->output->createElement('Application');
        $app->setAttribute('name', 'SEWEBAR-CMS');
        $app->setAttribute('version', '0.00.01 '.date('d.m.Y'));
        $header->appendChild($app);
        $annot = $this->output->createElement('Annotation');
        $header->appendChild($annot);
        $tst = $this->output->createElement('Timestamp');
        $tst->appendChild($this->output->createTextNode(date('d.m.Y H:i:s')));
        $header->appendChild($tst);
        $root->appendChild($header);

        // create DataDictionary
        $dd = $this->output->createElement('DataDictionary');
        $root->appendChild($dd);

        // create AssociationModel
        $associationModel = $this->output->createElement('guha:AssociationModel');
        $associationModel->setAttribute('xmlns', '');
        $associationModel->setAttribute('xsi:schemaLocation', 'http://keg.vse.cz/ns/GUHA0.1rev1 http://sewebar.vse.cz/schemas/GUHA0.1rev1.xsd');
        $associationModel->setAttribute('xmlns:guha', 'http://keg.vse.cz/ns/GUHA0.1rev1');
        $associationModel->setAttribute('modelName', $modelName);
        $associationModel->setAttribute('functionName', 'associationRules');
        $associationModel->setAttribute('algorithmName', '4ft');

        // create TaskSetting
        $taskSetting = $this->output->createElement("TaskSetting");
        $this->arQuery = $associationModel->appendChild($taskSetting);

        // extension LISp-Miner
        $extension = $this->output->createElement('Extension');
        $extension->setAttribute('name', 'LISp-Miner');
        $extension->appendChild($this->output->createElement('HypothesesCountMax', $hypothesesCountMax));
        $this->arQuery->appendChild($extension);

        // extension metabase
        $extension = $this->output->createElement('Extension');
        $extension->setAttribute('name', 'metabase');
        $extension->setAttribute('value', $this->ddXpath->query("//dd:DataDescription/Dictionary[@default='true']/Identifier[@name='Metabase']")->item(0) ? $this->ddXpath->query("//dd:DataDescription/Dictionary[@default='true']/Identifier[@name='Metabase']")->item(0)->nodeValue : 'LM Barbora.mdb MB');
        $this->arQuery->appendChild($extension);

        $bbaSettings = $this->output->createElement("BBASettings");
        $this->bbaSettings = $this->arQuery->appendChild($bbaSettings);
        $dbaSettings = $this->output->createElement("DBASettings");
        $this->dbaSettings = $this->arQuery->appendChild($dbaSettings);
        $antecedentSetting = $this->output->createElement("AntecedentSetting");
        $this->antecedentSetting = $this->arQuery->appendChild($antecedentSetting);
        $consequentSetting = $this->output->createElement("ConsequentSetting");
        $this->consequentSetting = $this->arQuery->appendChild($consequentSetting);
        $interestMeasureSetting = $this->output->createElement("InterestMeasureSetting");
        $this->interestMeasureSetting = $this->arQuery->appendChild($interestMeasureSetting);

        // create AssociationRules
        $associationRules = $this->output->createElement("AssociationRules");
        $associationModel->appendChild($associationRules);

        $root->appendChild($associationModel);
    }

    public function generateId()
    {
        return ++$this->id;
    }

    protected function createInterestMeasureThreshold($IM)
    {
        $interestMeasureThreshold = $this->output->createElement("InterestMeasureThreshold");
        $interestMeasureThreshold->setAttribute("id", $this->generateId());
        $interestMeasureThreshold->appendChild($this->output->createElement("InterestMeasure", $IM->name));
        foreach ($IM->fields as $f) {
            if ($f->name === 'threshold') {
                $interestMeasureThreshold->appendChild($this->output->createElement("Threshold", $f->value));
            } elseif ($f->name === 'alpha') {
                $interestMeasureThreshold->appendChild($this->output->createElement("SignificanceLevel", $f->value));
            }
        }
        $interestMeasureThreshold->appendChild($this->output->createElement("ThresholdType", $IM->thresholdType));
        $interestMeasureThreshold->appendChild($this->output->createElement("CompareType", $IM->compareType));

        $this->interestMeasureSetting->appendChild($interestMeasureThreshold);
    }

    protected function parseCedent($cedent, $level, $forcedDepth, $strict)
    {
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

    protected function createDbaSetting($connective, $attributes, $level, $forcedDepth, $cedentIds, $strict)
    {
        $dbaId = $this->generateId();
        $dbaSetting = $this->output->createElement("DBASetting");
        $dbaSetting->setAttribute("id", $dbaId);
        $this->dbaSettings->appendChild($dbaSetting);

        if (!$this->isLiteral($connective)) {
            if ($level === 1 && $connective === 'Disjunction') { // Disjunction is not permitted on level 1
                $dbaSetting->setAttribute("type", self::$FORCE_DEPTH_BOOLEAN);
                $nextLevel = $level + 1;
                $baSettingRefId = $this->createDbaSetting($connective, $attributes, $nextLevel, $forcedDepth, $cedentIds, $strict);
                $dbaSetting->appendChild($this->output->createElement("BASettingRef", $baSettingRefId));
            } else {
                $dbaSetting->setAttribute("type", $connective);
                foreach ($attributes as $attribute) {
                    $nextLevel = $level + 1;
                    $dbaConnective = $nextLevel === $forcedDepth ? self::$LITERAL : self::$FORCE_DEPTH_BOOLEAN;
                    $baSettingRefId = $this->createDbaSetting($dbaConnective, [$attribute], $nextLevel, $forcedDepth, [], $strict);
                    $dbaSetting->appendChild($this->output->createElement("BASettingRef", $baSettingRefId));
                }

                foreach ($cedentIds as $cedentId) {
                    $dbaSetting->appendChild($this->output->createElement("BASettingRef", $cedentId));
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
                $dbaSetting->appendChild($this->output->createElement("MinimalLength", $minimalLength));
            } else {
                $dbaSetting->appendChild($this->output->createElement("MinimalLength", self::$CEDENT_MINIMAL_LENGTH));
            }
        } else  { // literal
            $dbaSetting->setAttribute("type", $connective);
            $attribute = $attributes[0];

            $baSettingRefId = $this->createBbaSetting($attribute);
            $dbaSetting->appendChild($this->output->createElement("BASettingRef", $baSettingRefId));

            $literalSignText = $attribute->sign === 'positive' ? "Positive" : "Negative";
            $dbaSetting->appendChild($this->output->createElement("LiteralSign", $literalSignText));
        }

        return $dbaId;
    }

    protected function createBbaSetting($attribute)
    {
        $bbaId = $this->generateId();

        $bbaSetting = $this->output->createElement("BBASetting");
        $bbaSetting->setAttribute('id', $bbaId);
        $bbaSetting->appendChild($this->output->createElement("Text", $attribute->name));
        $bbaSetting->appendChild($this->output->createElement("Name", $attribute->name));
        $bbaSetting->appendChild($this->output->createElement("FieldRef", $attribute->name));
        $bbaSetting->appendChild($this->createCoefficient($attribute));
        $this->bbaSettings->appendChild($bbaSetting);

        return $bbaId;
    }

    protected function createCoefficient($attribute)
    {
        $coefficient = $this->output->createElement("Coefficient");
        $coefficient->appendChild($this->output->createElement("Type", $attribute->category));

        if ($attribute->category == 'One category') {
            $coefficient->appendChild($this->output->createElement("Category", $attribute->fields[0]->value));
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

            $coefficient->appendChild($this->output->createElement("MinimalLength", $minLength));
            $coefficient->appendChild($this->output->createElement("MaximalLength", $maxLength));
        }

        return $coefficient;
    }

    public function getBooleanName($type)
    {
        return self::$BOOLEAN_TYPES[$type];
    }

    public function isLiteral($literal)
    {
        return in_array($literal, self::$LITERALS);
    }

}