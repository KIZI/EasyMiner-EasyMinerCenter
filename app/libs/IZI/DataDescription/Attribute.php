<?php

namespace IZI\DataDescription;

class Attribute
{
    private $name;
    private $categories;
    private $intervals;
    private $groups;

    public function  __construct()
    {
        $this->categories = array();
        $this->intervals = array();
        $this->groups = array();
    }

    public function setName($name)
    {
        return $this->name = $name;
    }

    public function addCategory($category)
    {
        array_push($this->categories, $category);
    }

    public function addInterval($interval)
    {
        array_push($this->intervals, $interval);
    }

    public function addGroup($group)
    {
        array_push($this->groups, $group);
    }

    public function toArray()
    {
        $array = array($this->name => array());
        if (!empty($this->categories)) {
            $array[$this->name]['choices'] = $this->categories;
        }
        if (!empty($this->intervals)) {
            $array[$this->name]['choices'] = $this->intervals;
        }
        if (!empty($this->groups)) {
            $array[$this->name]['groups'] = $this->groups;
        }

        return $array;
    }

}