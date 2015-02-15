<?php

namespace IZI\FileLoader;

use IZI\Exception\FileNotFoundException;

class XMLFileLoader
{
    protected $version = '1.0';
    protected $encoding = 'UTF-8';
    protected $stripPI = true;
    protected $ignoreBlanks = true;

    /**
     * @param string $file Path to file
     * @return \DomDocument|null XML document
     * @throws FileNotFoundException
     */
    public function load($file)
    {
        $document = new \DOMDocument($this->version, $this->encoding);
        if (!file_exists($file)) { throw new FileNotFoundException(); }

        $contents = file_get_contents($file);
        $XML = preg_replace('/<\?xml.{1,} ?>\n/', '', $contents);

        if ($this->ignoreBlanks) {
            $document->loadXML($XML, LIBXML_NOBLANKS);
        } else {
            $document->loadXML($XML);
        }

        return $document;
    }

}