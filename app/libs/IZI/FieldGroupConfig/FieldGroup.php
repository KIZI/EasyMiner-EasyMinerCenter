<?php

namespace IZI\FieldGroupConfig;

use IZI\AssociationRule\Connective;
use IZI\Exception\InvalidAttributeException;
use IZI\Exception\InvalidCoefficientException;

class FieldGroup
{
    private $FGNode;
    private $XPath;
    private $lang;
    private $isRoot;
    private $attributes;
    private $coefficients;

    private $id;
    private $connective;
    private static $CONNECTIVE_DEFAULT = 'Conjunction';
    private $name;
    private $localizedName;
    private $explanation;
    private $fields;
    private $childGroups;

    public function  __construct(\DOMElement $FGNode, \DOMXPath $XPath, $lang, $isRoot, &$attributes, &$coefficients)
    {
        $this->FGNode = $FGNode;
        $this->XPath = $XPath;
        $this->lang = $lang;
        $this->isRoot = $isRoot;
        $this->attributes = $attributes;
        $this->coefficients = $coefficients;

        $this->fields = array();
        $this->childGroups = array();

        $this->invalidAttributes = array();
    }

    public function hasChildGroups()
    {
        return (count($this->childGroups) !== 0);
    }

    public function getChildGroups()
    {
        return $this->childGroups;
    }

    public function parse()
    {
        $this->id = intval($this->FGNode->getAttribute('id'));

        if ($this->isRoot !== true) {
            $this->connective = new Connective($this->FGNode->getAttribute('connective'));
        } else {
            $this->connective = new Connective(self::$CONNECTIVE_DEFAULT);
        }

        $this->name = $this->XPath->evaluate('Name', $this->FGNode)->item(0)->nodeValue;
        $this->localizedName = $this->XPath->evaluate('LocalizedName[@lang="en"]', $this->FGNode)->item(0)->nodeValue;
        $this->explanation = $this->XPath->evaluate('Explanation[@lang="en"]', $this->FGNode)->item(0)->nodeValue;

        foreach ($this->XPath->evaluate('FieldConfigs/FieldConfig', $this->FGNode) as $fcNode) {
            try {
                $field = $this->parseFieldConfig($fcNode);
                array_push($this->fields, $field);
            } catch (InvalidAttributeException $e) {
            } catch (InvalidCoefficientException $e) {
            }

        }

        foreach ($this->XPath->evaluate('ChildFieldGroups/FieldGroupRef/@id', $this->FGNode) as $id) {
            array_push($this->childGroups, intval($id->nodeValue));
        }
    }

    protected function parseFieldConfig($fcNode)
    {
        $fieldRef = $this->XPath->evaluate('FieldRef', $fcNode)->item(0)->nodeValue;
        if (!isset($this->attributes[$fieldRef])) {
            throw new InvalidAttributeException('Invalid attribute.');
        }

        $type = new CoefficientType($this->XPath->evaluate('Coefficient/Type', $fcNode)->item(0)->nodeValue);
        $minimalLength = $this->XPath->evaluate('Coefficient/MinimalLength', $fcNode)->item(0) ? intval($this->XPath->evaluate('Coefficient/MinimalLength', $fcNode)->item(0)->nodeValue) : null;
        $maximalLength = $this->XPath->evaluate('Coefficient/MinimalLength', $fcNode)->item(0) ? intval($this->XPath->evaluate('Coefficient/MinimalLength', $fcNode)->item(0)->nodeValue) : null;
        $category = $this->XPath->evaluate('Coefficient/Category', $fcNode)->item(0) ? $this->XPath->evaluate('Coefficient/Category', $fcNode)->item(0)->nodeValue : null;

        $field = new FieldGroupConfigCoefficient($fieldRef, $type, $minimalLength, $maximalLength, $category, $this->attributes, $this->coefficients);

        return $field;
    }

    public function toArray()
    {
        $array = array('id' => $this->id,
                        'name' => $this->name,
                        'localizedName' => $this->localizedName,
                        'explanation' => $this->explanation,
                        'fieldConfig' => array(),
                        'childGroups' => $this->childGroups,
                        'connective' => $this->connective->getName());

        foreach ($this->fields as $f) {
            $array['fieldConfig'] = array_merge_recursive($array['fieldConfig'], $f->toArray());
        }

        $array = array($array);

        return $array;
    }

}