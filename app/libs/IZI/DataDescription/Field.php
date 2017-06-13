<?php

namespace IZI\DataDescription;

/**
 * Class Field
 * @package IZI\DataDescription
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
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
