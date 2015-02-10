<?php

namespace IZI\AssociationRule;

use IZI\Exception\InvalidDBAException;

class DBA
{
    private $id;
    private $connective;
    private $refIds;
    private $level;
    private $refs;

    public function  __construct($id, Connective $connective, $refIds, $level)
    {
        $this->id = $id;
        $this->connective = $connective;
        $this->refIds = $refIds;
        $this->level = $level;
        $this->refs = array();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getConnective()
    {
        return $this->connective;
    }

    public function getRefIds()
    {
        return $this->refIds;
    }

    public function getLevel()
    {
        return $this->level;
    }

    public function setLevel($val)
    {
        $this->level = $val;
    }

    public function addRef($ref)
    {
        if (!($ref instanceof BBA) && !($ref instanceof DBA)) {
            throw new InvalidDBAException('Invalid DBA');
        }

        array_push($this->refs, $ref);
    }

    public function getRefs()
    {
        return $this->refs;
    }

    public function toArray()
    {
        if (count($this->refs) == 1) {
            $arrRef = $this->refs[0]->toArray();
            if ($this->connective->isUnary() && $this->getLevel() === 3) {  // negation
                $arrRef[0]['sign'] = 'negative';
            } else if (!isset($arrRef[0]['sign'])) {
                $arrRef[0]['sign'] = 'positive';
            }

            return $arrRef;
        }

        $array = array();
        if ($this->connective->isBinary() && $this->getLevel() > 1 && count($this->refs) > 1) {
            array_push($array, $this->connective->getLbrac());
        }

        foreach ($this->refs as $k => $r) {
            if ($this->connective->isBinary() && $k > 0) { // conjunction, disjunction
                array_push($array, $this->connective->toArray());
            }

            $arrRef = $r->toArray();
            if ($this->connective->isUnary() && $this->getLevel() === 3) {  // negation
                $arrRef[0]['sign'] = 'negative';
            } else if (!isset($arrRef[0]['sign'])) {
                $arrRef[0]['sign'] = 'positive';
            }

            $array = array_merge_recursive($array, $arrRef);
        }

        if ($this->connective->isBinary() && $this->getLevel() > 1 && count($this->refs) > 1) {
            array_push($array, $this->connective->getRbrac());
        }

        return $array;
    }

}
