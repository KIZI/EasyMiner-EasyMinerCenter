<?php

namespace IZI\Parser;

use IZI\Exception\InvalidConnectiveException;
use IZI\Exception\InvalidFieldGroupConfigException;
use IZI\FieldGroupConfig\FieldGroup;

class FieldGroupConfigParser
{
    private $FGC;
    private $attributes;
    private $coefficients;
    private $lang;
    private $XPath;

    private $rootConfigID;
    private $predefinedAttributes;

    public function __construct(\DOMDocument $FGC, &$attributes, &$coefficients, $lang)
    {
        $this->FGC = $FGC;
        $this->attributes = $attributes;
        $this->coefficients = $coefficients;
        $this->lang = $lang;

        $this->XPath = new \DOMXPath($this->FGC);
        $this->XPath->registerNamespace('fg', 'http://keg.vse.cz/ns/fieldgroupconfig0_1');

        $this->predefinedAttributes = array();
    }

    public function parseConfig()
    {
        $config = array();
        $config['groups'] = array();
        try {
            $config = array_merge_recursive($config, $this->parseRootConfigID());
            $config['groups'] = $this->parseFieldGroupConfig($this->rootConfigID, $config['groups']);
        } catch (InvalidFieldGroupConfigException $e) {
            $this->initDefaultRootConfigGroup($config);
            $this->initDefaultAttributes($config);
        } catch (InvalidConnectiveException $e) {
            $this->initDefaultRootConfigGroup($config);
            $this->initDefaultAttributes($config);
        }

        return $config;
    }

    protected function parseFieldGroupConfig($id, $config)
    {
        $FGCNode = $this->XPath->evaluate('//FieldGroupConfig[@id="'.$id.'"]')->item(0);
        if ($FGCNode === null) {
            throw new InvalidFieldGroupConfigException('Invalid field group config structure.');
        }

        $FG = new FieldGroup($FGCNode, $this->XPath, $this->lang, ($this->rootConfigID === $id), $this->attributes,
                    $this->coefficients);
        $FG->parse();
        $config = array_merge_recursive($config, $FG->toArray());

        if ($FG->hasChildGroups() === true) {
            foreach ($FG->getChildGroups() as $chg) {
                $config = $this->parseFieldGroupConfig($chg, $config);
            }
        }

        return $config;
    }

    protected function parseRootConfigID()
    {
        $array = array();
        if (!$this->XPath->evaluate('//@rootConfigID')->item(0)) {
            throw new InvalidFieldGroupConfigException('Invalid root config ID.');
        }
        $this->rootConfigID = intval($this->XPath->evaluate('//@rootConfigID')->item(0)->value);
        $array['rootConfigID'] = $this->rootConfigID;

        return $array;
    }

    protected function initDefaultRootConfigGroup(&$config)
    {
        $config['rootConfigID'] = 1;
        $config['groups'] = array();
        $config['groups'][0] = array('id' => 1,
                                   'name' => 'Root',
                                   'fieldConfig' => array(),
                                   'childGroups' => array(),
                                   'connective' => 'Conjunction');
    }

    protected function initDefaultAttributes(&$config)
    {
        foreach (array_keys($this->attributes) as $name) {
            $this->initDefaultAttribute($config, $name);

        }
    }

    protected function initDefaultAttribute(&$config, &$name)
    {
        $config['groups'][0]['fieldConfig'][$name] = array('coefficient' => null);
    }

    protected function initMissingAttributes(&$config)
    {
        // find predefined attributes
        foreach ($config['groups'] as $cg) {
            foreach (array_keys($cg['fieldConfig']) as $attr) {
                array_push($this->predefinedAttributes, $attr);
            }
        }

        // init other attributes (missing, invalid, invalid coef)
        foreach (array_keys($this->attributes) as $name) {
            if (array_search($name, $this->predefinedAttributes) === false) {
                $this->initDefaultAttribute($config, $name);
            }
        }
    }

}

