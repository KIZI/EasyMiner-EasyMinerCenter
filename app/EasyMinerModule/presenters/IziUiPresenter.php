<?php
namespace App\EasyMinerModule\Presenters;

use IZI\IZIConfig;
use IZI\Parser\DataParser;

/**
 * Class IziUiPresenter - prezenter obsahující funkcionalitu vyžadovanou javascriptovým uživatelským rozhraním (migrace PHP kódu z projektu EasyMiner2)
 * @package App\EasyMinerModule\Presenters
 */
class IziUiPresenter extends BaseRestPresenter{
  private $lang='en';//TODO předávání jazyka rozhraní
  /** @var  IZIConfig $config */
  private $config;

  /**
   * Akce vracející data description a konfiguraci pro EasyMiner UI
   * @param $id_dm
   */
  public function actionGetData($id_dm){//TODO ošetření případné chyby!!!
    exit(var_dump($id_dm));
    if ($id_dm=='TEST'){
      $DP = new DataParser($this->config->DDPath, $this->config->FLPath, $this->config->FGCPath, null, null, $this->lang);
      $DP->loadData();
      $responseContent = $DP->parseData();
      $responseContent['status'] = 'ok';
      $this->sendJsonResponse($responseContent);
      return;
    }

    //TODO připravení DataDescriptionPMML
    $dataDescriptionPMML='';
    $dataParser = new DataParser($dataDescriptionPMML, $this->config->FLPath, $this->config->FGCPath, null, null, $this->lang);//TODO kontrola, jestli může být obsah předán bez uložení do souboru
    $dataParser->loadData();
    $responseContent = $dataParser->parseData();
    $responseContent['status'] = 'ok';

    $this->sendJsonResponse($responseContent);
  }

  /**
   * @param IZIConfig $iziConfig
   */
  public function injectIZIConfig(IZIConfig $iziConfig){
    $this->config=$iziConfig;
  }
} 