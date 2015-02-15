<?php

namespace IZI\Algorithm;

class BasicChiSquareAlgorithm
{
    private $ETreeRules;
    private $XPath;
    private $coefficient;

    // algorithm settings
    private $pow = 1;
    private $precision = 2;

    // result
    private $attributes;

    public function __construct(\DOMElement $ETreeRules, \DOMXPath $XPath)
    {
        $this->ETreeRules = $ETreeRules;
        $this->XPath = $XPath;
        $this->coefficient = $this->parseCoefficient();
        $this->attributes = array();
    }

    public function evaluate()
    {
        // parse
        $ETRules = array();
        foreach($this->XPath->evaluate('ETRule', $this->ETreeRules) as $elETRule) {
            $ETRule = $this->parseETRule($elETRule);
            if (!empty($ETRule)) {
                array_push($ETRules, $ETRule);
            }
        }

        // calculate & normalize & order
        $attributes = $this->calculate($ETRules);
        $attributes = $this->normalize($attributes);
        $this->attributes = $this->order($attributes);
    }

    protected function parseETRule($elETRule)
    {
        if ($this->coefficient['type'] === 'One category') {
            $XPathExpr = "ETSplitArray//ETSplit[child::node()/child::node()[@ClassCategory='".$this->coefficient['cat']."']]";
        } else {
            $XPathExpr = "ETSplitArray//ETSplit[child::node()/child::node()[@ClassCategory]]";
        }

        $attributes = array();
        foreach ($this->XPath->evaluate($XPathExpr, $elETRule) as $elementETSplit) {
            $attribute = array(
                'name' => $elementETSplit->getAttribute('Attribute'),
                'level' => $elementETSplit->getAttribute('SplitLevel'),
                'significance' => $elementETSplit->getAttribute('SplitSignificanceCoef'));
            array_push($attributes, $attribute);
        }

        return $attributes;
    }

    protected function getMinLevel($ETRules)
    {
        $minLevel = PHP_INT_MAX;
        foreach ($ETRules as $ETRule) {
            foreach ($ETRule as $attr) {
                if ($attr['level'] < $minLevel) { $minLevel = $attr['level']; }
            }
        }

        return $minLevel;
    }

    protected function calculate($ETRules)
    {
        $minLevel = $this->getMinLevel($ETRules);

        $attributes = array();
        foreach ($ETRules as $ETRule) {
            foreach ($ETRule as $attr) {
                $value = $attr['significance'] / pow($attr['level'] + 1 - $minLevel, $this->pow);

                if (!array_key_exists($attr['name'], $attributes) || ($value > $attributes[$attr['name']])) {
                    $attributes[$attr['name']] = $value;
                }
            }
        }

        return $attributes;
    }

    protected function normalize($attributes)
    {
        $maxVal = 15;
        foreach ($attributes as $val) {
            if ($val > $maxVal) {
                $maxVal = $val;
            }
        }

        foreach ($attributes as &$val) {
            $val = round($val / $maxVal, $this->precision);
        }

        return $attributes;
    }

    protected function order($attributes)
    {
        arsort($attributes);

        return $attributes;
    }

    protected function parseCoefficient()
    {
        $coef = array();
        $taskNotice = explode('|', $this->XPath->evaluate('//TaskNotice')->item(0)->nodeValue);
        $coef['type'] = $taskNotice[2];
        if ($coef['type'] === 'One category') {
            $coef['cat'] = $taskNotice[3];
        } else {
            $limits = explode('-', $taskNotice[3]);
            $coef['minLength'] = $limits[0];
            $coef['maxLength'] = $limits[1];
        }

        return $coef;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

}