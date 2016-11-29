<?php
namespace EasyMinerCenter\Model\EasyMiner\Transformators;
use EasyMinerCenter\Model\Translation\EasyMinerTranslator;

/**
 * Class XmlTransformator - třída pro transformace používaných XML dokumentů
 * @package EasyMinerCenter\Model\EasyMiner\Transformators
 */
class XmlTransformator {

  private $transformationsDirectory;
  private $templates=[];
  private $language='en';

  /**
   * @var string $basePath
   */
  private $basePath='';

  /**
   * @param array $params
   * @param EasyMinerTranslator $translator
   */
  public function __construct($params, EasyMinerTranslator $translator=null){
    $this->transformationsDirectory=__DIR__.'/../../../'.$params['directory'];
    $this->templates['guhaPMML']=$params['guhaPMML'];
    $this->templates['DRL']=$params['DRL'];
    if ($translator instanceof EasyMinerTranslator){
      $this->language=$translator->getLang();
    }
  }

  /**
   * @param $basePath
   */
  public function setBasePath($basePath){
    $this->basePath=$basePath;
  }

  /**
   * @param \SimpleXMLElement|\DOMDocument|string $xmlDocument
   * @return string
   * @throws \Exception
   */
  public function transformToDrl($xmlDocument){
    $filename=$this->transformationsDirectory.'/';
    /** @var array $transformationParams Parametry transformace */
    $transformationParams=[];
    if (is_array($this->templates['DRL'])){
      $filename.=$this->templates['DRL']['path'];
      if (!empty($this->templates['DRL']['params'])){
        $transformationParams=$this->templates['DRL']['params'];
      }
    }else{
      $filename.=$this->templates['DRL'];
    }
    if (!($xslt=file_get_contents($filename))){
      throw new \Exception('Transformation template not found!');
    }
    $xsl = new \DOMDocument('1.0','UTF-8');

    $xsl->loadXML($xslt);
    $xsl->documentURI = $filename;

    return $this->xsltTransformation($xmlDocument, $xsl, $transformationParams);
  }


  /**
   * @param \SimpleXMLElement|\DOMDocument|string $xmlDocument
   * @return string
   * @throws \Exception
   */
  public function transformToHtml($xmlDocument){
    //TODO kontrola jednotlivých typů dokumentů
    $filename=$this->transformationsDirectory.'/';
    /** @var array $transformationParams Parametry transformace */
    $transformationParams=[];
    if (is_array($this->templates['guhaPMML'])){
      $filename.=$this->templates['guhaPMML']['path'];
      if (!empty($this->templates['guhaPMML']['params'])){
        $transformationParams=$this->templates['guhaPMML']['params'];
      }
    }else{
      $filename.=$this->templates['guhaPMML'];
    }
    if (!($xslt=file_get_contents($filename))){
      throw new \Exception('Transformation template not found!');
    }
    $xsl = new \DOMDocument('1.0','UTF-8');

    $xsl->loadXML($xslt);
    $xsl->documentURI = $filename;

    return $this->xsltTransformation($xmlDocument, $xsl, $transformationParams);
  }

  /**
   * @param \SimpleXMLElement|\DOMDocument|string $xml
   * @param \SimpleXMLElement|\DOMDocument|string $xslt
   * @param array $params
   * @return string
   */
  private function xsltTransformation($xml, $xslt, $params=array()){
    $xsltPreprocessor = new \XSLTProcessor();
    if (is_string($xml)){
      $xml=simplexml_load_string($xml);
    }
    if (is_string($xslt)){
      $xslt=simplexml_load_string($xslt);
    }
    $xsltPreprocessor->importStylesheet($xslt);


    //region parameters
    if (!empty($params)){
      foreach ($params as $name => $value) {
        $value=str_replace('$basePath',$this->basePath,$value);
        $value=str_replace('$lang',$this->language,$value);
        $xsltPreprocessor->setParameter('',$name,$value);
      }
    }
    //endregion parameters

    return $xsltPreprocessor->transformToXml($xml);
  }

}