<?php

namespace IZI\FileLoader;

use IZI\FileLoader\XMLFileLoader;
use IZI\FileLoader\XMLStringLoader;

class XMLLoader
{

    /**
     * @param string $string Path to XML document or XML string
     * @return \DomDocument|null XML document
     */
    public function load($string)
    {
        if (file_exists($string)) {
            $loader = new XMLFileLoader();
        } else {
            $loader = new XMLStringLoader();
        }

        $document = $loader->load($string);

        return $document;
    }

}