<?php

namespace IZI\Parser;

use IZI\AssociationRule\Connective;

class ConnectiveParser
{
    private $ER;
    private $XPath;
    private $connectives;

    public function __construct($ER, $XPath)
    {
        $this->ER = $ER;
        $this->XPath = $XPath;
        $this->connectives = array();
    }

    /* // really slow
     public function parseConnectives()
     {
     // fuck PHP 5, distinct-values not implemented
     // $XPathExpr = 'distinct-values(//@connective)';
     // foreach ($this->XPath->evaluate($XPathExpr) as $c) {
     // $connective = new Connective($c);
     // $this->connectives[$c] = $connective;
     // }

     foreach ($this->XPath->evaluate('//DBA[@connective]/@connective') as $c) {
     if (!isset($this->connectives[$c->value])) {
     $this->connectives[$c->value] = new Connective($c->value);
     }
     }

     return $this->connectives;
     }
     */

    public function parseConnective($value)
    {
        if (!isset($this->connectives[$value])) {
            $connective = new Connective($value);
            $this->connectives[$value] = $connective;

            return $connective;
        }

        return $this->connectives[$value];
    }

}