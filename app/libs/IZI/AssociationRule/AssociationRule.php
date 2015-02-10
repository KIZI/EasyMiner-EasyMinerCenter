<?php

namespace IZI\AssociationRule;

use IZI\AssociationRule\InterestMeasure;
use IZI\Exception\InvalidRuleException;
use IZI\Parser\BBAParser;
use IZI\Parser\DBAParser;

class AssociationRule
{
    private $ARNode;
    private $ER;
    private $attributes; // supported attributes
    private $IMs;        // supported IMs

    private $DBAP;
    private $BBAP;

    private $id;
    private $interestMeasures;
    private $BBAs;
    private $antecedent;
    private $consequent;

    private $XPath;

    public function __construct(&$ARNode, &$ER, &$attributes, &$IMs, DBAParser $DBAP, BBAParser $BBAP)
    {
        $this->ARNode = $ARNode;
        $this->ER = $ER;
        $this->attributes = $attributes;
        $this->IMs = $IMs;

        $this->DBAP = $DBAP;
        $this->BBAP = $BBAP;

        $this->interestMeasures = array();
        $this->BBAs = array();
        $this->antecedent = null;
        $this->consequent = null;

        $this->XPath = new \DOMXPath($this->ER);
    }

    public function parse()
    {
        // ID
        $this->id = (int) $this->ARNode->getAttribute('id');

        // init IMs
        $this->parseIMs();

        // init antecedent
        $antId = $this->ARNode->getAttribute('antecedent');
        if (!empty($antId)) {
            $this->antecedent = $this->parseElement($antId, 1, '');
        }

        // init consequent
        $consId = $this->ARNode->getAttribute('consequent');
        if (!empty($consId)) {
            $this->consequent = $this->parseElement($consId, 1, '');
        }
    }

    protected function parseIMs()
    {
        $XPathExpr = 'IMValue[@imSettingRef]';
        foreach ($this->XPath->evaluate($XPathExpr, $this->ARNode) as $iIM){
            $IM = new InterestMeasure($iIM->getAttribute('name'), floatval($iIM->nodeValue));
            if ($this->hasValidIM($IM)) {
                array_push($this->interestMeasures, $IM);
            }
        }
    }

    protected function parseElement($elementId, $level, $type)
    {
        if ($type === 'BBA') {
            $element = $this->BBAP->getBBA($elementId);
            if ($element === null) { throw new InvalidRuleException('Rule has invalid BBA and cannot be parsed'); }
        } elseif ($type === 'DBA') {
            $element = $this->DBAP->getDBA($elementId);
            if ($element === null) { throw new InvalidRuleException('Rule has invalid DBA and cannot be parsed'); }
        } else {
            $element = $this->DBAP->getDBA($elementId) ? $this->DBAP->getDBA($elementId) : $this->BBAP->getBBA($elementId);
            if ($element === null) { throw new InvalidRuleException('Rule has invalid BBA | DBA and cannot be parsed'); }
        }

        if ($element instanceof BBA) {
            if (!$this->hasValidBBA($element)) {
                $element->setActive(false);
            }
            $this->BBAs[$elementId] = $element;
        } else { // DBA
            $element->setLevel($level);
            foreach ($element->getRefIds() as $id) {
                $ref = $this->parseElement($id, ($level + 1), '');
                $element->addRef($ref);
            }
            $this->DBAs[$elementId] = $element;
        }

        return $element;
    }

    protected function hasValidIM($IM)
    {
        if (isset($this->IMs['types'][$IM->getName()])) {
            if ($this->hasValidIMValue($IM)) { return true; }
        }

        return false;
    }

    protected function hasValidIMValue($IM)
    {
        $field = &$this->IMs['types'][$IM->getName()]['field'];

        // validate data type
        if (isset($field['dataType'])) {
            $dataType = &$field['dataType'];

            if ($dataType == 'string') {
                // supposed as valid
            } elseif ($dataType == 'integer' || $dataType == 'float' || $dataType == 'double') {
                if (stripos($IM->getName(), 'chi') !== false) {
                    return true;
                }

                if (!is_numeric($IM->getValue())) { return false; }

                if (isset($field['minValue']) && isset($field['minValueInclusive'])) {
                    if ($field['minValueInclusive'] === true) {
                        if ($IM->getValue() < $field['minValue']) { return false; }
                    } else {
                        if ($IM->getValue() <= $field['minValue']) { return false; }
                    }
                }

                if (isset($field['maxValue']) && isset($field['maxValueInclusive'])) {
                    if ($field['maxValueInclusive'] === true) {
                        if ($IM->getValue() > $field['maxValue']) { return false; }
                    } else {
                        if ($IM->getValue() >= $field['maxValue']) { return false; }
                    }
                }
            }
        }

        return true;
    }

    protected function hasValidBBA($BBA)
    {
        if (isset($this->attributes[$BBA->getFieldRef()])) {  // find the right attribute
            $attribute = &$this->attributes[$BBA->getFieldRef()];
            foreach ($BBA->getCatRefs() as $cr) {
                if (in_array($cr, $attribute['choices'])) {  // find the right value
                    return true;
                }

                if (strpos($cr, '<') !== false || strpos($cr, '(') !== false ||
                strpos($cr, ')') !== false || strpos($cr, '>') !== false) { return true; }
            }
        }


        return false;
    }

    public function toArray()
    {
        $array = array('id' => $this->id, 'antecedent' => array(), 'IM' => array(), 'consequent' => array());

        if ($this->antecedent !== null) {
            $array['antecedent'] = $this->antecedent->toArray();
        }

        foreach ($this->interestMeasures as $IM) {
            array_push($array['IM'], $IM->toArray());
        }

        if ($this->consequent !== null) {
            $array['consequent'] = $this->consequent->toArray();
        }

        return $array;
    }

}