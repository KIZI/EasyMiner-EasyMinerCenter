<?php

namespace IZI\FieldGroupConfig;

use IZI\Exception\InvalidCoefficientException;

class CoefficientType
{
    private $coefficient;

    public function  __construct($coefficient)
    {
        if (!in_array($coefficient, array('Interval', 'Cyclic interval', 'Subset', 'Cut', 'Left cut', 'Right cut',
                                       'One category', 'Both boolean', 'Boolean true', 'Boolean false'))) {
            throw new InvalidCoefficientException('Invalid coefficient type.');
        }

        $this->coefficient = $coefficient;
    }

    public function getName()
    {
        return $this->coefficient;
    }

    public function toArray()
    {
        $array = array('type' => $this->coefficient);

        return $array;
    }

}