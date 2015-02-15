<?php

namespace IZI\Serializer;

use IZI\Algorithm\BasicETreeSettings;
use IZI\FileLoader\XMLLoader;

class ETreeSerializer
{
    protected $DD;
    protected $DDXpath;
    protected $FA;
    protected $FAXpath;

    protected $id = 0;
    protected $conditions = [];

    // XML document
    protected $output;
    protected $modelName;
    protected $taskSetting;
    protected $hypotheses;
    protected $bbaSettings;
    protected $dbaSettings;
    protected $antecedentSetting;
    protected $consequentSetting;
    protected $interestMeasureSetting;
    protected $conditionSetting;

    protected static $BOOLEAN_TYPES = ['neg' => 'Negation', 'and' => 'Conjunction', 'or' => 'Disjunction', 'lit' => 'Literal'];
    protected static $FORCE_DEPTH_BOOLEAN = 'Conjunction';
    protected static $LITERAL = 'Literal';
    protected static $LITERALS = ['Literal'];
    protected static $MINIMAL_LENGTH = 1;

    public function __construct($DDPath, $FAPath)
    {
        $loader = new XMLLoader();

        $this->DD = $loader->load($DDPath);
        $this->DDXpath = new \DOMXPath($this->DD);
        $this->DDXpath->registerNamespace('dd', "http://keg.vse.cz/ns/datadescription0_2");

        $this->FA = $loader->load($FAPath);
        $this->FAXpath = new \DOMXPath($this->FA);
    }

    public function serialize($json, $forcedDepth = 3)
    {
        $json = json_decode($json);
        $rule = $json->rule0;

        // Create basic structure of Document.
        $this->createBasicStructure();

        // Input attributes
        $this->createInputAttributesGroupSettings($json->attributes);

        // Class attribute
        $classAttribute = $this->createClassAttributeSettings($rule->succedent);

        // Condition
        $conditionId = $this->parseCedent($rule->antecedent, 1, $forcedDepth);
        $this->conditionSetting->appendChild($this->output->createTextNode($conditionId));

        // ETree and InterestMeasure settings
        $this->createETreeSettings($classAttribute, $this->conditions, $rule->IMs);

        // Update modelName
        $this->updateModelName();

        // Serialize XML
        return $this->output->saveXML();
    }

    private function createBasicStructure()
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
        $ext->setAttribute('value', $this->DDXpath->query("//dd:DataDescription/Dictionary/@sourceName")->item(0)->value);
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
        $ext->setAttribute('value', 'ETResult.exe');
        $header->appendChild($ext);
        $ext = $this->output->createElement('Extension');
        $ext->setAttribute('name', 'format');
        $ext->setAttribute('value', 'ETreeMiner.Task');
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
        $this->dataDictionary = $dd;

        // create AssociationModel
        $associationModel = $this->output->createElement('guha:ETreeModel');
        $associationModel->setAttribute('xmlns', '');
        $associationModel->setAttribute('xsi:schemaLocation', 'http://keg.vse.cz/ns/GUHA0.1rev1 http://sewebar.vse.cz/schemas/GUHA0.1rev1.xsd');
        $associationModel->setAttribute('xmlns:guha', 'http://keg.vse.cz/ns/GUHA0.1rev1');
        $this->modelName = $this->output->createAttribute('modelName');
        $associationModel->setAttributeNode($this->modelName);
        $associationModel->setAttribute('functionName', 'explorationTrees');
        $associationModel->setAttribute('algorithmName', 'ETree');

        // create TaskSetting
        $taskSetting = $this->output->createElement("TaskSetting");
        $this->taskSetting = $associationModel->appendChild($taskSetting);

        // extension LISp-Miner
        $this->extension = $extension = $this->output->createElement('Extension');
        $extension->setAttribute('name', 'LISp-Miner');
        $this->taskNotice = $extension->appendChild($this->output->createElement('TaskNotice'));

        $this->taskSetting->appendChild($extension);

        // extension metabase
        $extension = $this->output->createElement('Extension');
        $extension->setAttribute('name', 'metabase');
        $extension->setAttribute('value', $this->DDXpath->query("//dd:DataDescription/Dictionary[@default='true']/Identifier[@name='metabase']")->item(0)->nodeValue);
        $this->taskSetting->appendChild($extension);

        $this->inputAttributesGroupSettings = $this->taskSetting->appendChild($this->output->createElement("InputAttributesGroupSettings"));
        $this->classAttributeSettings = $this->taskSetting->appendChild($this->output->createElement("ClassAttributeSettings"));
        $this->bbaSettings = $this->taskSetting->appendChild($this->output->createElement("BBASettings"));
        $this->dbaSettings = $this->taskSetting->appendChild($this->output->createElement("DBASettings"));
        $this->conditionSetting = $this->taskSetting->appendChild($this->output->createElement("ConditionSetting"));

        $this->interestMeasureSetting = $this->taskSetting->appendChild($this->output->createElement('InterestMeasureSetting'));

        $root->appendChild($associationModel);
    }

    public function generateId()
    {
        return ++$this->id;
    }

    protected function parseCedent($cedent, $level, $forcedDepth)
    {
        $connective = $this->getBooleanName($cedent->connective->type);

        $attributes = [];
        $cedentIds = [];
        foreach ($cedent->children as $child) {
            if (isset($child->type) && $child->type === 'cedent') {
                $cedentId = $this->parseCedent($child, ($level + 1), $forcedDepth);
                array_push($cedentIds, $cedentId);
            } else {
                array_push($attributes, $child);
            }
        }

        $cedentId = $this->createDbaSetting($connective, $attributes, $level, $forcedDepth, $cedentIds);

        return $cedentId;
    }

    protected function createDbaSetting($connective, $attributes, $level, $forcedDepth, $cedentIds)
    {
        $dbaId = $this->generateId();
        $dbaSetting = $this->output->createElement("DBASetting");
        $dbaSetting->setAttribute("id", $dbaId);
        $this->dbaSettings->appendChild($dbaSetting);

        if (!$this->isLiteral($connective)) {
            if ($level === 1 && $connective === 'Disjunction') { // Disjunction is not permitted on level 1
                $dbaSetting->setAttribute("type", self::$FORCE_DEPTH_BOOLEAN);
                $nextLevel = $level + 1;
                $baSettingRefId = $this->createDbaSetting($connective, $attributes, $nextLevel, $forcedDepth, $cedentIds);
                $dbaSetting->appendChild($this->output->createElement("BASettingRef", $baSettingRefId));
            } else {
                $dbaSetting->setAttribute("type", $connective);
                foreach ($attributes as $attribute) {
                    $nextLevel = $level + 1;
                    $dbaConnective = $nextLevel === $forcedDepth ? self::$LITERAL : self::$FORCE_DEPTH_BOOLEAN;
                    $baSettingRefId = $this->createDbaSetting($dbaConnective, [$attribute], $nextLevel, $forcedDepth, []);
                    $dbaSetting->appendChild($this->output->createElement("BASettingRef", $baSettingRefId));
                }

                foreach ($cedentIds as $cedentId) {
                    $dbaSetting->appendChild($this->output->createElement("BASettingRef", $cedentId));
                }
            }

            $dbaSetting->appendChild($this->output->createElement("MinimalLength", self::$MINIMAL_LENGTH));
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

        $condition = array();
        $condition['name'] = $attribute->name;
        $condition['type'] = $attribute->category;

        if ($attribute->category == 'One category') {
            $coefficient->appendChild($this->output->createElement("Category", $attribute->fields[0]->value));
            $condition['cat'] = $attribute->fields[0]->value;
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

            $condition['minLength'] = $minLength;
            $condition['minLength'] = $maxLength;
        }

        array_push($this->conditions, $condition);

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

    protected function createInputAttributesGroupSettings($attributes)
    {
        $element = $this->output->createElement('InputAttributesSettings');
        $element->setAttribute('id', $this->generateId());
        $element->appendChild($this->output->createElement('Name', 'Attributes'));
        $element->appendChild($this->output->createElement('MinimalLength', 1));
        $element->appendChild($this->output->createElement('MaximalLength', 1));
        foreach ($attributes as $a) {
            $elementInner = $this->output->createElement('InputAttributeSetting');
            $elementInner->setAttribute('id', $this->generateId());
            $elementInner->appendChild($this->output->createElement('FieldRef', $a));
            $element->appendChild($elementInner);
        }
        $this->inputAttributesGroupSettings->appendChild($element);
    }

    protected function createClassAttributeSettings($succedent)
    {
        $attribute = $succedent->children[0];

        $element = $this->output->createElement('ClassAttributeSetting');
        $element->setAttribute('id', $this->generateId());
        $element->appendChild($this->output->createElement('FieldRef', $attribute->name));
        $this->classAttributeSettings->appendChild($element);

        $taskNotice = 'Succedent|'.$attribute->name.'|'.$attribute->category.'|';
        if ($attribute->category === 'One category') {
            $taskNotice .= $attribute->fields[0]->value;
        } else {
            $taskNotice .= $attribute->fields[0]->value.'-';
            $taskNotice .= $attribute->fields[1]->value;
        }
        $this->taskNotice->appendChild($this->output->createTextNode($taskNotice));

        return array('name' => $attribute->name);
    }

    private function createETreeSettings($classAttribute, $conditions, $IMs)
    {
        $settings = new BasicETreeSettings($this->FAXpath, $classAttribute, $conditions, $IMs);
        $params = $settings->evaluate();

        foreach ($params['extension'] as $name => $value) {
            $this->extension->appendChild($this->output->createElement($name, $value));
        }

        foreach ($params['IM'] as $name => $value) {
            $this->interestMeasureSetting->appendChild($this->output->createElement($name, $value));
        }
    }

    protected function updateModelName()
    {
        $modelName = sha1($this->output->saveXML($this->taskSetting));
        $this->modelName->appendChild($this->output->createTextNode($modelName));
    }

}
