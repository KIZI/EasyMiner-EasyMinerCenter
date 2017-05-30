<?php

namespace IZI\AssociationRule;

/**
 * Class InterestMeasure
 * @package IZI\AssociationRule
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class InterestMeasure
{
    private $name;
    private $value;

    public function __construct($name, $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function toArray()
    {
        $array = array('name' => $this->name,
               'type' => 'im',
               'category' => '',
               'fields' => array('name' => 'threshold',
                                   'value' => $this->value));

        return $array;
    }

}