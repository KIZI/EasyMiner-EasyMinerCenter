<?php
namespace App\Model\EasyMiner\Transformators;
//phpinfo();exit();
/**
 * Class XmlTransformator - třída pro transformace používaných XML dokumentů
 * @package App\Model\EasyMiner\Transformators
 */
class XmlTransformator {

  private $transformationsDirectory;
  private $templates=[];

  private $basePath='';

  public function __construct($params){
    $this->transformationsDirectory=__DIR__.'/../../../'.$params['directory'];
    $this->templates['guhaPMML']=$params['guhaPMML'];
  }

  /**
   * @param \SimpleXMLElement|\DOMDocument|string $xmlDocument
   * @return string
   * @throws \Exception
   */
  public function transformToHtml($xmlDocument,$basePath){
    //TODO kontrola jednotlivých typů dokumentů
    $filename=$this->transformationsDirectory.'/'.$this->templates['guhaPMML'];
    $this->basePath=$basePath;
    if (!($xslt=file_get_contents($filename))){
      throw new \Exception('Transformation template not found!');
    }
    $xsl = new \DOMDocument('1.0','UTF-8');

    $xsl->loadXML($xslt);
    $xsl->documentURI = $filename;

    return $this->xsltTransformation($xmlDocument,$xsl);
  }

  /**
   * @param \SimpleXMLElement|\DOMDocument|string $xml
   * @param \SimpleXMLElement|\DOMDocument|string $xslt
   * @return string
   */
  private function xsltTransformation($xml,$xslt){
    $xsltPreprocessor = new \XSLTProcessor();
    if (is_string($xml)){
      $xml=simplexml_load_string($xml);
    }
    if (is_string($xslt)){
      $xslt=simplexml_load_string($xslt);
    }
    $xsltPreprocessor->importStylesheet($xslt);


    //region parameters
    $xsltPreprocessor->setParameter('','contentOnly',true);
    $xsltPreprocessor->setParameter('','loadJquery',false);
    $xsltPreprocessor->setParameter('','basePath',$this->basePath.'/_XML/transformations/guhaPMML2HTML');//TODO
    //endregion parameters

    return $xsltPreprocessor->transformToXml($xml);
  }

}