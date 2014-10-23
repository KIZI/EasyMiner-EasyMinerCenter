<?php

namespace IZI\DataDescription;

class Field
{

    private $name;
    private $dataType;

    public function __construct($name, $dataType)
    {
        $this->name = $name;
        $this->dataType = $dataType;
    }

    public function toArray()
    {
        return array($this->name => $this->dataType);
    }

}
