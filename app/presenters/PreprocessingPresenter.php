<?php

namespace App\Presenters;


use App\Model\XmlSerializer;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Http\IResponse;

class PreprocessingPresenter extends BaseRestPresenter{

  public function __construct(){
    parent::__construct();
    /*  TENTO PRESENTER JEŠTĚ NENÍ PŘIPRAVEN!!!  */
    throw new AbortException();
  }

  /**
   * Akce pro vypsání seznamu uložených preprocessingů na základě vybraného formátu
   * @param string $baseId = ''
   * @param string $format
   */
  public function actionListByFormat($baseId='',$format){
    $preprocessings=$this->knowledgeRepository->findPreprocessings();//TODO filtrování na základě zadaného baseId a formátu
    if (empty($preprocessings)){
      $this->sendXmlResponse($this->xmlSerializer->basePreprocessingsXml());//TODO nový XML pro preprocessingy
      return;
    }

    $responseXml=$this->xmlSerializer->basePreprocessingsXml();
    foreach ($preprocessings as $preprocessing){
      $this->xmlSerializer->preprocessingAsXml($preprocessing,$responseXml);
    }

    $this->sendXmlResponse($responseXml);
  }

  /**
   * Akce vracející jedno pravidlo ve formátu JSON
   * @param string $baseId = ''
   * @param string $uri
   * @throws \Nette\Application\BadRequestException
   */
  public function actionGet($baseId='',$uri){
    $rule=$this->knowledgeRepository->findRule($uri);//TODO vyřešení baseId
    if ($rule){
      //odešleme daný metaatribut
      $this->sendXmlResponse($this->xmlSerializer->ruleAsXml($rule));
    }else{
      throw new BadRequestException('Requested Rule not found.',IResponse::S404_NOT_FOUND);
    }
  }

  /**
   * Akce pro uložení pravidla
   * @param string $baseId = ''
   * @param string $uri
   * @param string $data - pravidlo ve formátu JSON
   * @throws \Nette\Application\BadRequestException
   * @internal param $_POST['data']
   */
  public function actionSave($baseId='',$uri,$data=null){
    if (empty($data)){
      $data=$this->getHttpRequest()->getPost('data');
      if (empty($data)){
        throw new BadRequestException('Preprocessing data are missing!');
      }
    }

    //TODO

  }
} 