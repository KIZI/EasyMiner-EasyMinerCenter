<?php
namespace App\EasyMinerModule\Presenters;

use App\Model\EasyMiner\Entities\Miner;
use App\Model\EasyMiner\Facades\DatasourcesFacade;
use IZI\IZIConfig;
use IZI\Parser\DataParser;
use Nette\Application\ForbiddenRequestException;
use Nette\Utils\Strings;

/**
 * Class MiningUiPresenter - presenter obsahující funkcionalitu vyžadovanou javascriptovým uživatelským rozhraním (migrace PHP kódu z projektu EasyMiner2)
 * @package App\EasyMinerModule\Presenters
 */
class MiningUiPresenter extends BasePresenter{
  private $lang='en';//TODO předávání jazyka rozhraní
  /** @var  IZIConfig $config */
  private $config;
  /** @var  DatasourcesFacade $datasourcesFacade*/
  private $datasourcesFacade;

  /**
   * Akce vracející data description a konfiguraci pro EasyMiner UI
   * @param int $id_dm
   * @param int $miner
   * @throws ForbiddenRequestException
   */
  public function actionGetData($id_dm,$miner){
    if (empty($miner)){
      $miner=$id_dm;
    }

    //------------------------------------------------------------------------------------------------------------------
    $minerType=Miner::DEFAULT_TYPE;
    if ($miner){
      $miner=$this->minersFacade->findMiner($miner);
      if (!$this->minersFacade->checkMinerAccess($miner,$this->user->id)){
        throw new ForbiddenRequestException($this->translator->translate('You are not authorized to access selected miner data!'));
      }
      $minerType=$miner->type;
    }
    $FLPathElement='FLPath_'.Strings::upper($minerType);

    //------------------------------------------------------------------------------------------------------------------
    #region připravení informací pro UI - s odděleným připravením DataDictionary
    $dataDescriptionPMML=null;
    $dataParser = new DataParser($dataDescriptionPMML, $this->config->$FLPathElement, $this->config->FGCPath, null, null, $this->lang);//TODO kontrola, jestli může být obsah předán bez uložení do souboru
    $dataParser->loadData();
    $responseContent = $dataParser->parseData();

    $metasource=null;
    try{
      $metasource=$miner->metasource;
    }catch (\Exception $e){/*chybu ignorujeme - zatím pravděpodobně neexistují žádné atributy*/}

    $responseContent['DD']=$this->datasourcesFacade->exportDictionariesArr($miner->datasource,$metasource);
    #endregion připravení informací pro UI - s odděleným připravením DataDictionary

    uksort($responseContent['DD']['transformationDictionary'],function($a,$b){
      return strnatcasecmp($a,$b);
    });
    uksort($responseContent['DD']['dataDictionary'],function($a,$b){
      return strnatcasecmp($a,$b);
      //return strnatcasecmp(mb_strtolower($a,'utf-8'),mb_strtolower($b,'utf-8'));
    });

    $responseContent['status'] = 'ok';
    $responseContent['miner_type'] = $miner->type;

    $this->sendJsonResponse($responseContent);
  }


  /**
   * Akce pro zobrazení EasyMiner-MiningUI
   */
  public function renderDefault($id_dm) {
    //TODO doplnit kontroly na přihlášeného uživatele, oprávnění k mineru atd.
    require __DIR__.'/../../../submodules/EasyMiner-MiningUI/web/Integration.php';
    /** @noinspection PhpUndefinedNamespaceInspection */
    $this->template->javascriptFiles=\EasyMiner\MiningUI\Integration::$javascriptFiles;
    /** @noinspection PhpUndefinedNamespaceInspection */
    $this->template->cssFiles=\EasyMiner\MiningUI\Integration::$cssFiles;
  }
  

  #region injections
  /**
   * @param IZIConfig $iziConfig
   */
  public function injectIZIConfig(IZIConfig $iziConfig){
    $this->config=$iziConfig;
  }
  /**
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }
  #endregion
} 