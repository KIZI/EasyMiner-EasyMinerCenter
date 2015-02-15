<?php

namespace IZI\Parser;

use IZI\AssociationRule\BBA;
use IZI\Exception\InvalidRuleException;

class BBAParser
{
    private $ER;
    private $XPath;
    private $BBAs;

    public function __construct($ER, $XPath)
    {
        $this->ER = $ER;
        $this->XPath = $XPath;
        $this->BBAs = array();
    }

    public function parseBBAs()
    {
        foreach ($this->XPath->evaluate('//AssociationRules/BBA | BBASettings/BBASetting | //Hits/BBA', $this->ER) as $iBBA) {
            $id = $iBBA->getAttribute('id');
            $catRefs = array();
            foreach($iBBA->childNodes as $n) {
                if ($n->nodeName == 'FieldRef') {
                    $fieldRef = $n->nodeValue;
                } elseif ($n->nodeName == 'CatRef') {
                    array_push($catRefs, $n->nodeValue);
                } elseif ($n->nodeName == 'Coefficient') {
                    $category = $this->XPath->evaluate('Category', $n)->item(0)->nodeValue;
                    array_push($catRefs, $category);
                }
            }

            try {
                $BBA = new BBA($id, $fieldRef, $catRefs);
                $this->BBAs[$id] = $BBA;
            } catch (InvalidBBAException $e) {
                throw new InvalidRuleException('Invalid rule');
            }
        }

        return count($this->BBAs);
    }

    public function getBBA($id)
    {
        return isset($this->BBAs[$id]) ? $this->BBAs[$id] : null;
    }

}