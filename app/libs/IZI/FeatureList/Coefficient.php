<?php

namespace IZI\FeatureList;

class Coefficient
{
    private $name;
    private $localizedName;
    private $explanation;
    private $fields;

    public function __construct($name, $localizedName, $explanation)
    {
        $this->name = $name;
        $this->localizedName = $localizedName;
        $this->explanation = $explanation;
        $this->fields = array();
    }

    public function addField($name, $localizedName, $minValue, $minValueInclusive, $maxValue, $maxValueInclusive, $dataType, $previous)
    {
        $field = array($name => array(
               'localizedName' => $localizedName,
               'minValue' => $minValue,
               'minValueInclusive' => $minValueInclusive,
               'maxValue' => $maxValue,
               'maxValueInclusive' => $maxValueInclusive,
               'dataType' => $dataType,
               'previous' => $previous));
        $this->fields = array_merge_recursive($this->fields, $field);
    }

    public function toArray()
    {
        $array = array($this->name => array('localizedName' => $this->localizedName,
                                    'explanation' => $this->explanation,
                                    'fields' => array()));
        $array[$this->name]['fields'] = array_merge_recursive($array[$this->name]['fields'], $this->fields);

        return $array;
    }

}