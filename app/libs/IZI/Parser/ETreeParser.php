<?php

namespace IZI\Parser;

use IZI\Algorithm\BasicChiSquareAlgorithm;

/**
 * Class ETreeParser
 * @package IZI\Parser
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ETreeParser
{
    protected $ETree;
    protected $ETreeXPath;

    public function __construct(\DOMDocument $ETree)
    {
        $this->ETree = $ETree;
        $this->ETreeXPath = new \DOMXPath($this->ETree);
        $this->ETreeXPath->registerNamespace('guha', "http://keg.vse.cz/ns/GUHA0.1rev1");
    }

    public function parseData()
    {
        $array['recommendedAttributes'] = array();
        if ($this->ETreeXPath->evaluate('count(//guha:ETreeModel)')) {
            $algorithm = new BasicChiSquareAlgorithm($this->ETreeXPath->evaluate('//guha:ETreeModel/ETreeRules')->item(0), $this->ETreeXPath);
            $algorithm->evaluate();
            $array['recommendedAttributes'] = $algorithm->getAttributes();
        }

        return $array;
    }
}

