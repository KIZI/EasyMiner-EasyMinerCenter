<?php

namespace IZI\Parser;

use IZI\Parser\AssociationRulesParser;

class RulesParser
{
    private $ER;
    private $attributes;
    private $interestMeasures;
    private $XPath;
    private $data;

    private static $FINISHED_TASK_STATE = 'Solved';

    public function __construct(\DOMDocument $ER, &$attribues, &$iterestMeasures)
    {
        $this->ER = $ER;
        $this->attributes = $attribues;
        $this->interestMeasures = $iterestMeasures;
        $this->XPath = new \DOMXPath($this->ER);
        $this->data = array();
    }

    public function parseData()
    {
        $this->data['existingRules'] = array();
        $this->data['existingRules'] = array_merge_recursive($this->data['existingRules'],
        $this->parseExistingRules());

        return $this->data;
    }

    protected function parseExistingRules()
    {
        $this->XPath->registerNamespace('guha', 'http://keg.vse.cz/ns/GUHA0.1rev1');
        if ($this->XPath->evaluate('count(//AssociationRules) and count(//TaskSetting/Extension[@name="LISp-Miner"])')) {
            return $this->parseAssociationRules();
        } else if ($this->XPath->evaluate('//guha:AssociationModel/TaskSetting')->length) {
            $this->XPath->registerNamespace('arb', "http://keg.vse.cz/ns/arbuilder0_1");
            return $this->parseTaskSettingRules();
        } else if ($this->XPath->evaluate('count(//ARQuery)')) {
            $this->XPath->registerNamespace('arb', "http://keg.vse.cz/ns/arbuilder0_1");
            return $this->parseARQueryRules();
        }

        return array();
    }

    protected function parseAssociationRules()
    {
        $array['rules'] = array();
        if ($TS = $this->XPath->evaluate('//TaskSetting/Extension/TaskState')->item(0)) {
            $array['taskState'] = $TS->nodeValue;
        } else {
            $array['taskState'] = self::$FINISHED_TASK_STATE;
        }
        $ARParser = new AssociationRulesParser($this->ER, $this->attributes, $this->interestMeasures);
        foreach ($ARParser->parseRules() as $r) {
            array_push($array['rules'], $r->toArray());
        }
        return $array;
    }

    protected function parseTaskSettingRules()
    {
        $array['rules'] = array();
        if ($TS = $this->XPath->evaluate('/arb:ARBuilder/@taskState')->item(0)) {
            $array['taskState'] = $TS->value;
        } else {
            $array['taskState'] = self::$FINISHED_TASK_STATE;
        }
        $TSParser = new TaskSettingRulesParser($this->ER, $this->attributes, $this->interestMeasures);
        foreach ($TSParser->parseRules() as $r) {
            array_push($array['rules'], $r->toArray());
        }

        return $array;
    }

    protected function parseARQueryRules()
    {
        $array['rules'] = array();
        if ($TS = $this->XPath->evaluate('/arb:ARBuilder/@taskState')->item(0)) {
            $array['taskState'] = $TS->value;
        } else {
            $array['taskState'] = self::$FINISHED_TASK_STATE;
        }
        $ARQParser = new ARQueryRulesParser($this->ER, $this->attributes, $this->interestMeasures);
        foreach ($ARQParser->parseRules() as $r) {
            array_push($array['rules'], $r->toArray());
        }

        return $array;
    }

}

