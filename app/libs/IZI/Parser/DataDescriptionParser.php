<?php

namespace IZI\Parser;

use IZI\DataDescription\Attribute;
use IZI\DataDescription\Field;

class DataDescriptionParser
{
    private $DD;
    private $XPath;

    public function __construct($DD)
    {
        $this->DD = $DD;
        $this->XPath = new \DOMXPath($this->DD);
        $this->XPath->registerNamespace('dd', "http://keg.vse.cz/ns/datadescription0_2");
    }

    public function parseData()
    {
        $data = array();
        $data['DD'] = array(
            'transformationDictionary' => array(),
            'dataDictionary' => array()
        );

        $data['DD']['recordCount'] = $this->parseRecordCount();

        foreach ($this->parseAttributes() as $a) {
            $data['DD']['transformationDictionary'] = array_merge_recursive($data['DD']['transformationDictionary'], $a->toArray());
        }

        foreach ($this->parseFields() as $f) {
            $data['DD']['dataDictionary'] = array_merge_recursive($data['DD']['dataDictionary'], $f->toArray());
        }

        return $data;
    }

    protected function parseRecordCount()
    {
        return $this->XPath->evaluate('//dd:DataDescription/Dictionary[@sourceDictType="DataDictionary"]/Identifier[@name="recordCount"]')->item(0)->nodeValue;
    }

    protected function parseAttributes()
    {
        $attributes = array();
        foreach ($this->XPath->evaluate('//dd:DataDescription/Dictionary[@sourceDictType="TransformationDictionary" and @default="true"]/Field') as $f) {
            $attribute = new Attribute();
            foreach ($f->childNodes as $n) {
                if ($n->nodeName == "Name") {
                    $attribute->setName($n->nodeValue);
                }

                if ($n->nodeName == "Category") {
                    $attribute->addCategory($n->nodeValue);
                }

                if ($n->nodeName == "Interval") {
                    $closure = $n->getAttribute('closure');
                    $interval = substr($closure, 0, 4) === 'open' ? '(' : '<';
                    $interval .= $n->getAttribute('leftMargin').';'.$n->getAttribute('rightMargin');
                    $interval .= substr($closure, -4, 4) === 'Open' ? ')' : '>';
                    $attribute->addInterval($interval);
                }
            }
            array_push($attributes, $attribute);
        }

        return $attributes;
    }

    protected function parseFields()
    {
        $fields = array();
        foreach ($this->XPath->evaluate('//dd:DataDescription/Dictionary[@sourceDictType="DataDictionary"]/Field') as $f) {
            $name = $f->firstChild->nodeValue;
            $dataType = $f->getAttribute('dataType');
            $field = new Field($name, $dataType);
            array_push($fields, $field);
        }

        return $fields;
    }

}

