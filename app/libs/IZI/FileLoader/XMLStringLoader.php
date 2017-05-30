<?php

namespace IZI\FileLoader;

/**
 * Class XMLStringLoader
 * @package IZI\FileLoader
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class XMLStringLoader
{
    protected $version = '1.0';
    protected $encoding = 'UTF-8';
    protected $stripPI = true;
    protected $ignoreBlanks = true;

    /**
     * @param string $XML XML document
     * @return \DomDocument|null XML document
     */
    public function load($XML)
    {
        $document = new \DOMDocument($this->version, $this->encoding);

        $XML = preg_replace('/<\?xml.{1,} ?>\n/', '', $XML);

        if ($this->ignoreBlanks) {
            @$document->loadXML($XML, LIBXML_NOBLANKS);
        } else {
            @$document->loadXML($XML);
        }

        return $document;
    }

}