<?php

namespace IZI\Serializer;

use IZI\FileLoader\XMLLoader;

class AnnotatedAssociationRulesSerializer
{
    protected $DD;
    protected $ddXpath;

    protected $id = 0;

    // XML document
    protected $output;
    protected $associationRules;
    protected $interestMeasureSetting;

    protected static $BOOLEAN_TYPES = ['neg' => 'Negation', 'and' => 'Conjunction', 'or' => 'Disjunction', 'lit' => 'Literal'];
    protected static $FORCE_DEPTH_BOOLEAN = 'Conjunction';
    protected static $LITERAL = 'Literal';
    protected static $LITERALS = ['Literal'];
    protected static $MINIMAL_LENGTH = 1;

    public function __construct($DDPath)
    {
        $loader = new XMLLoader();
        $this->DD = $loader->load($DDPath);

        // init XPath
        $this->ddXpath = new \DOMXPath($this->DD);
        $this->ddXpath->registerNamespace('data', "http://keg.vse.cz/ns/datadescription0_2");
    }

    public function serialize($json, $annotationText)
    {
        $json = json_decode($json);

        // Create basic structure of Document.
        $this->createBasicStructure();

        // Serialize rule
        $associationRule = $this->parseAssociationRule($json->rule0, $annotationText);
        $this->associationRules->appendChild($associationRule);

        // Serialize XML
        return $this->output->saveXML();
    }

    protected function parseAssociationRule($rule, $annotationText) {
        $associationRule = $this->output->createElement('AssociationRule');

        // Create antecedent
        $antecedentId = $this->parseCedent($rule->antecedent, 1);
        $associationRule->setAttribute('antecedent', $antecedentId);

        // Create consequent
        $consequentId = $this->parseCedent($rule->succedent, 1);
        $associationRule->setAttribute('consequent', $consequentId);

        // IMs
        foreach ($rule->IMs as $IM) {
            $interestMeasureValue = $this->createInterestMeasureValue($IM);
            $associationRule->appendChild($interestMeasureValue);
        }


        $annotation = $this->output->createElement('Annotation');
        $annotation->appendChild($this->output->createElement('Interestingness', $annotationText));
        $associationRule->appendChild($annotation);

        return $associationRule;
    }

    protected function createBasicStructure()
    {
        $this->output = new \DOMDocument("1.0", "UTF-8");

        // create PMML
        $document = $this->output->createElement('ar:ARBuilder');
        $document->setAttribute("xmlns:ar", "http://keg.vse.cz/ns/arbuilder0_2");
        $document->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $document->setAttribute("xmlns:dd", "http://keg.vse.cz/ns/datadescription0_2");
        $document->setAttribute("xmlns:guha", "http://keg.vse.cz/ns/GUHA0.1rev1");
        $document->setAttribute("xsi:schemaLocation", "http://keg.vse.cz/ns/arbuilder0_2 http://sewebar.vse.cz/schemas/ARBuilder0_2.xsd");
        $root = $this->output->appendChild($document);

        // create DataDescription
        $dataDescription = $root->appendChild($this->output->createElement("DataDescription"));

        $anXPathExpression = "//data:DataDescription/Dictionary[@sourceDictType='TransformationDictionary']";
        $dictionary = $this->ddXpath->query($anXPathExpression)->item(0);
        $dataDescription->appendChild($this->output->importNode($dictionary, true));

        $this->associationRules = $root->appendChild($this->output->createElement("AnnotatedAssociationRules"));
    }

    public function generateId()
    {
        return ++$this->id;
    }

    protected function parseCedent($cedent, $level)
    {
        $connective = $this->getBooleanName($cedent->connective->type);

        $attributes = [];
        $cedentIds = [];
        foreach ($cedent->children as $child) {
            if (isset($child->type) && $child->type === 'cedent') {
                $cedentId = $this->parseCedent($child, ($level + 1));
                array_push($cedentIds, $cedentId);
            } else {
                array_push($attributes, $child);
            }
        }

        $cedentId = $this->createDbaSetting($connective, $attributes, $level, $cedentIds);

        return $cedentId;
    }

    protected function createInterestMeasureValue($IM)
    {
        $interestMeasureValue = $this->output->createElement("IMValue", $IM->fields[0]->value);
        $interestMeasureValue->setAttribute('name', $IM->name);

        return $interestMeasureValue;
    }


    protected function createDbaSetting($connective, $attributes, $level, $cedentIds)
    {
        if (!$this->isLiteral($connective)) {
            $id = $this->generateId();
            $dbaSetting = $this->output->createElement("DBA");
            $dbaSetting->setAttribute("id", $id);
            $this->associationRules->appendChild($dbaSetting);

            $dbaSetting->setAttribute("connective", $connective);
            foreach ($attributes as $attribute) {
                $nextLevel = $level + 1;
                $dbaConnective = self::$LITERAL;
                $baSettingRefId = $this->createDbaSetting($dbaConnective, [$attribute], $nextLevel, []);
                $dbaSetting->appendChild($this->output->createElement("BARef", $baSettingRefId));
            }

            foreach ($cedentIds as $cedentId) {
                $dbaSetting->appendChild($this->output->createElement("BARef", $cedentId));
            }
        } else  { // literal
            $id = $this->createBbaSetting($attributes[0]);
        }

        return $id;
    }

    protected function createBbaSetting($attribute)
    {
        $bbaId = $this->generateId();

        $bbaSetting = $this->output->createElement("BBA");
        $bbaSetting->setAttribute('id', $bbaId);
        $bbaSetting->appendChild($this->output->createElement("Text", $attribute->name));
        $bbaSetting->appendChild($this->output->createElement("FieldRef", $attribute->name));
        $bbaSetting->appendChild($this->output->createElement("CatRef", $attribute->fields[0]->value[0]));
        $this->associationRules->appendChild($bbaSetting);

        return $bbaId;
    }

    protected function createCoefficient($attribute)
    {
        $coefficient = $this->output->createElement("Coefficient");
        $coefficient->appendChild($this->output->createElement("Type", $attribute->category));

        if ($attribute->category == 'One category') {
            $coefficient->appendChild($this->output->createElement("Category", $attribute->fields[0]->value[0]));
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
