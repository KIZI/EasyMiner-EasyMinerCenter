<?php

namespace IZI\AssociationRule;

use IZI\Exception\InvalidBBAException;

class BBA
{
    private $id;
    private $fieldRef;
    private $catRefs;
    private $coefficient;
    private $active;

    public function __construct($id, $fieldRef, $catRefs, $coefficient = null)
    {
        if ($fieldRef === null || is_array($fieldRef)) {
            throw new InvalidBBAException('Invalid BBA');
        }

        if ($catRefs === null || !is_array($catRefs) || empty($catRefs)) {
            throw new InvalidBBAException('Invalid BBA');
        }

        $this->id = $id;
        $this->fieldRef = $fieldRef;
        $this->catRefs = $catRefs;
        $this->coefficient = $coefficient;
        $this->active = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getFieldRef()
    {
        return $this->fieldRef;
    }

    public function getCatRef()
    {
        $keys = array_keys($this->catRefs);
        return $this->catRefs[$keys[0]];
    }

    public function getCatRefs()
    {
        return $this->catRefs;
    }

    public function getCoefficient()
    {
        return $this->coefficient;
    }

    public function setActive($active)
    {
        $this->active = (boolean) $active;
    }

    public function toArray()
    {
        $array = array('name' => $this->fieldRef,
               'type' => 'attr',
               'category' => 'One category',
               'active' => $this->active);
        $fields = array();
        foreach ($this->catRefs as $cr) {
            $field = array('name' => 'coef',
                 'value' => $cr);
            array_push($fields, $field);
        }

        $array['fields'] = $fields;
        $array = array($array);

        return $array;
    }

}