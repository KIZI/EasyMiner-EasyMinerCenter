<?php

namespace IZI\Algorithm;

class BasicETreeSettings
{
    private $XPath;
    private $classAttr;
    private $cond;
    private $IM;

    // ETree extension settings
    private $ETTaskParamSplitAttributesMax = 6;
    private $ETTaskParamTreeDepthMax = 1;
    private $ETTaskParamTreeCountMax = 500;
    private $ETTaskParamFullDepthTreesOnly = 'Yes';
    private $HypothesesCountMax = 150;

    // ETree IM settings
    private $NodeFreqMin = 1;
    private $PerformChiSqTest = 'Yes';
    private $SplitSignificanceAlpha = 0.025;

    public function __construct(\DOMXPath $XPath, $classAttr, $cond, $IM)
    {
        $this->XPath = $XPath;
        $this->classAttr = $classAttr;
        $this->cond = $cond;
        $this->IM = $IM;
    }

    public function evaluate()
    {
        // static
        $params['extension'] = array(
            'ETTaskParamSplitAttributesMax' => $this->ETTaskParamSplitAttributesMax,
            'ETTaskParamTreeDepthMax' => $this->ETTaskParamTreeDepthMax,
            'ETTaskParamTreeCountMax' => $this->ETTaskParamTreeCountMax,
            'ETTaskParamFullDepthTreesOnly' => $this->ETTaskParamFullDepthTreesOnly,
            'HypothesesCountMax' => $this->HypothesesCountMax,
        );

        $NP = 0;
        $TQ = 0;
        foreach ($this->cond as $cnd) {
            $NPVal = $this->calculateNP($cnd, $this->classAttr);
            $TQVal = $this->calculateTQ($cnd, $this->classAttr);
            if ($NPVal > $NP) {
                $NP = ($NPVal > $TQVal ? $NPVal : $TQVal);
                $TQ = $TQVal;
            }
        }

        $NP *= 1.001;

        // IM
        $params['IM'] = array(
            'NodeFreqMin' => $this->NodeFreqMin,
            'PerformChiSqTest' => $this->PerformChiSqTest,
            'SplitSignificanceAlpha' => $this->SplitSignificanceAlpha,
            'NodePurityMin' => $NP,
            'TreeQualityMin' => $TQ,
        );

        return $params;
    }

    protected function calculateNP ($cond, $classAttr)
    {
        /*
        $vals = array();
        $XPathExpr = "//Frequency[child::Condition/Name = '".$this->cond['name']."' and child::Class/Name = '".$this->classAttr['name']."']/Values/Value";
        foreach ($this->XPath->evaluate($XPathExpr) as $elVal) {
            array_push($vals, $elVal->nodeValue);
        }
        $NP = (array_sum($vals) / count($vals)) + ($this->stdev($vals));
        */

        //$NP = $this->median($vals) + ($this->stdev($vals) / 2);

        $vals = $this->getFreqValues($cond, $classAttr);
        $avg = array_sum($vals) / count($vals);
        $NP = ($avg + max($vals)) / 2;

        return round($NP, 3);
    }

    protected function calculateTQ ($cond, $classAttr)
    {
        $vals = $this->getFreqValues($cond, $classAttr);
        $TQ = $this->median($vals);

        return round($TQ, 3);
    }

    protected function getFreqValues ($cond, $classAttr)
    {
        $vals = array();
        if ($cond['type'] === 'One category') {
            $XPathExpr = "//Frequency[child::Condition/Name = '".$cond['name']."' and child::Class/Name = '".$classAttr['name']."']/Values/Value[@cat='".$cond['cat']."']";
        } else {
            $XPathExpr = "//Frequency[child::Condition/Name = '".$cond['name']."' and child::Class/Name = '".$classAttr['name']."']/Values/Value";
        }

        foreach ($this->XPath->evaluate($XPathExpr) as $elVal) {
            array_push($vals, $elVal->nodeValue);
        }

        return $vals;
    }

    protected function stdev ($sample)
    {
        if (is_array ($sample)){
            $mean = array_sum($sample) / count($sample);
            foreach($sample as $key => $num) $devs[$key] = pow($num - $mean, 2);
            return sqrt(array_sum($devs) / (count($devs) - 1));
        }

        return 0;
    }

    protected function median ($sample)
    {
        rsort($sample);
        $mid = (count($sample) / 2);
        $median = (float)($mid % 2 != 0) ? $sample{$mid-1} : (($sample{$mid-1}) + $sample{$mid}) / 2;

        return $median;
    }

}