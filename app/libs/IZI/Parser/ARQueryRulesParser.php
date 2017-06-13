<?php

namespace IZI\Parser;

use IZI\AssociationRule\ARQueryRule;
use IZI\Exception\InvalidRuleException;

/**
 * Class ARQueryRulesParser
 * @package IZI\Parser
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class ARQueryRulesParser
{
    private $ER;
    private $attributes;
    private $interestMeasures;
    private $XPath;

    public function __construct(\DOMDocument $ER, &$attributes, &$interestMeasures)
    {
        $this->ER = $ER;
        $this->XPath = new \DOMXPath($this->ER);
        $this->attributes = $attributes;
        $this->interestMeasures = $interestMeasures;
    }

    public function parseRules()
    {
        $rules = array();

        $CP = new ConnectiveParser($this->ER, $this->XPath);

        $BBAP = new BBAParser($this->ER, $this->XPath);
        $BBAP->parseBBAs();

        $DBAP = new DBAParser($this->ER, $this->XPath, $CP);
        $DBAP->parseDBAs();

        $ARQR = @new ARQueryRule($this->XPath->evaluate('//ARQuery')->item(0), $this->ER, $this->attributes, $this->interestMeasures, $DBAP, $BBAP); // arrays are passed by reference for performance and legacy reasons
        try {
            $ARQR->parse($DBAP, $BBAP);
            array_push($rules, $ARQR);
        } catch (InvalidRuleException $e) {}

        return $rules;
    }
}

