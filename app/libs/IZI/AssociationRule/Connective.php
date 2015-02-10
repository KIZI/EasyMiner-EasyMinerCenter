<?php

namespace IZI\AssociationRule;

use IZI\Exception\InvalidConnectiveException;

class Connective
{
    private $connective;

    public function  __construct($connective)
    {
        if (!in_array($connective, array('Conjunction', 'Disjunction', 'Negation'))) {
            throw new InvalidConnectiveException('Invalid connective');
        }

        $this->connective = $connective;
    }

    public function getName()
    {
        return $this->connective;
    }

    public function isUnary()
    {
        return $this->connective === 'Negation';
    }

    public function isBinary()
    {
        return !$this->isUnary();
    }

    public function getLbrac()
    {
        return array('name' => '(',
             'type' => 'lbrac',
             'category' => '',
             'fields' => array());
    }

    public function getRbrac()
    {
        return array('name' => ')',
               'type' => 'rbrac',
               'category' => '',
               'fields' => array());
    }

    protected function convert()
    {
        switch ($this->connective) {
            case 'Conjunction':
                return 'AND';
                break;
            case 'Disjunction':
                return 'OR';
                break;
            case 'Negation':
                return 'NEG';
                break;
        }
    }

    public function toArray()
    {
        $array = array('name' => $this->convert(),
               'type' => strtolower($this->convert()),
               'category' => '',
               'fields' => array());

        return $array;
    }

}