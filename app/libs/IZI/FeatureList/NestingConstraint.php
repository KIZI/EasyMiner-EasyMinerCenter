<?php

namespace IZI\FeatureList;

/**
 * Class NestingConstraint
 * @package IZI\FeatureList
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class NestingConstraint
{
    private $connectives;

    public function __construct()
    {
        $this->connectives = array();
    }

    public function hasConnective($name) {
        return array_key_exists($name, $this->connectives);
    }

    public function isConnectiveAllowed($name) {
        return $this->connectives[$name];
    }

    public function addConnective($name, $allowed)
    {
        $connective = array($name => $allowed);
        $this->connectives = array_merge_recursive($this->connectives, $connective);
    }

    public function updateConnective($name, $allowed) {
        $this->connectives[$name] = $allowed;
    }

    public function toArray()
    {
        return $this->connectives;
    }

}