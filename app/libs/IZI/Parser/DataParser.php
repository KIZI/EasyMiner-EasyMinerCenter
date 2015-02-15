<?php

namespace IZI\Parser;

use IZI\FileLoader\XMLLoader;
use IZI\Parser\ETreeParser;
use IZI\Parser\DataDescriptionParser;
use IZI\Parser\FeatureListParser;
use IZI\Parser\FieldGroupConfigParser;
use IZI\Parser\RulesParser;

class DataParser
{
    private $DDPath;
    private $DD;
    private $FLPaths;
    private $FLs;
    private $FGC;
    private $FGCPath;
    private $ERPath;
    private $ER;
    private $ETreePath;
    private $ETree;
    private $lang;
    private $data;

    public function __construct($DDPath, $FLPath, $FGCPath, $ERPath, $ETreePath, $lang)
    {
        $this->DDPath = $DDPath;
        $this->FLPaths = is_array($FLPath) ? $FLPath : array($FLPath);
        $this->FLs = array ();
        $this->FGCPath = $FGCPath;
        $this->ERPath = $ERPath;
        $this->ETreePath = $ETreePath;
        $this->lang = $lang;
        $this->data = array();
    }

    public function loadData()
    {
        $loader = new XMLLoader();

        if (!empty($this->DDPath)){
          $this->DD = $loader->load($this->DDPath);
        }

        foreach ($this->FLPaths as $FLPath) {
            $FL = $loader->load($FLPath);
            array_push($this->FLs, $FL);
        }

        $this->FGC = $loader->load($this->FGCPath);
        $this->ER = $loader->load($this->ERPath);
        $this->ETree = $loader->load($this->ETreePath);
    }

    public function parseData()
    {
        if (!empty($this->DD)){
          $DDParser = new DataDescriptionParser($this->DD);
          $this->data = array_merge_recursive($this->data, $DDParser->parseData());
        }

        $this->data['FLs'] = array();
        foreach ($this->FLs as $FL) {
            $FLParser = new FeatureListParser($FL, $this->lang);
            array_push($this->data['FLs'], $FLParser->parseData());
        }
        usort($this->data['FLs'], array('IZI\Parser\DataParser', 'sortFLs'));

        $FGCParser = new FieldGroupConfigParser(
            $this->FGC,
            $this->data['DD'],
            $this->data['FLs'][0]['BBA']['coefficients'],
            $this->lang);
        $this->data['FGC'] = $FGCParser->parseConfig();

        $ERParser = new RulesParser($this->ER, $this->data['DD'], $this->data['FLs'][0]['interestMeasures']);
        $this->data = array_merge_recursive($this->data, $ERParser->parseData());

        $ETreeParser = new ETreeParser($this->ETree);
        $this->data = array_merge_recursive($this->data, $ETreeParser->parseData());

        return $this->data;
    }

    protected static function sortFLs ($a, $b)
    {
        if ($a['priority'] === $b['priority']) {
            return 0;
        }

        return ($a['priority'] < $b['priority']) ? 1 : -1;
    }

    public function getER()
    {
        return $this->data['existingRules'];
    }

    public function getRecommendedAttributes()
    {
        return $this->data['recommendedAttributes'];
    }
}

