<?php

namespace IZI\Parser;

use IZI\FileLoader\XMLLoader;

class KnowledgeBaseParser
{
    private $XPath;

    public function __construct($data)
    {
        $loader = new XMLLoader();
        $DOM = $loader->load($data);
        $this->XPath = new \DomXPath($DOM);
    }

    public function parse()
    {
        $numHits = (int) $this->XPath->evaluate('count(//Hits/Hit)');
        $numInteresting = 0;
        $numNotInteresting = 0;
        if ($numHits) {
            $numInteresting = (int) $this->XPath->evaluate('count(//Annotation[Interestingness = "interesting"])');
            $numNotInteresting = (int) $this->XPath->evaluate('count(//Annotation[Interestingness = "not interesting"])');
        }

        $arr = array (
            'hits' => $numHits,
            'numInteresting' => $numInteresting,
            'numNotInteresting' => $numNotInteresting
        );

        return $arr;
    }

}