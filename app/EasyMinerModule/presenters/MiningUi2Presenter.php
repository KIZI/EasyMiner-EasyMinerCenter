<?php
namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Facades\BreTestsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\DatasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMiner\MiningUI2\Integration as MiningUIIntegration;
use IZI\IZIConfig;
use IZI\Parser\DataParser;
use Nette\Application\ForbiddenRequestException;
use Nette\Utils\Strings;

/**
 * Class MiningUi2Presenter - presented with the functionality required by the submodule EasyMiner-MiningUI2 (UI for mining of association rules)
 * @package EasyMinerCenter\EasyMinerModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MiningUi2Presenter extends BasePresenter{
  use MinersFacadeTrait;
  use ResponsesTrait;
  use UsersTrait;

  /** @var  IZIConfig $config */
  private $config;
  /** @var BreTestsFacade $breTestsFacade */
  private $breTestsFacade;
  /** @var  DatasourcesFacade $datasourcesFacade*/
  private $datasourcesFacade;
  /** @var  MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;

  /**
   * Action for sending of data description and configuration for EasyMiner-MiningUI
   * @param int $id_dm
   * @param int $miner
   * @throws ForbiddenRequestException
   */
  public function actionGetData($id_dm,$miner){
    if (empty($miner)){
      $miner=$id_dm;
    }

    //------------------------------------------------------------------------------------------------------------------
    $miner=$this->findMinerWithCheckAccess($miner);
    $minerType=$miner->type;
    $FLPathElement='FLPath_'.Strings::upper($minerType);

    //------------------------------------------------------------------------------------------------------------------
    #region preparation of information for UI - with separated preparation of DataDictionary
    $dataDescriptionPMML=null;
    $dataParser = new DataParser($dataDescriptionPMML, $this->config->$FLPathElement, $this->config->FGCPath, null, null, $this->translator->getLang());
    $dataParser->loadData();
    $responseContent = $dataParser->parseData();

    $user=$this->getCurrentUser();

    $responseContent['DD']=[
      'dataDictionary'=>$this->datasourcesFacade->exportDataDictionaryArr($miner->datasource, $user, $rowsCount),
      'transformationDictionary'=>$this->metasourcesFacade->exportTransformationDictionaryArr($miner->metasource, $user),
      'recordCount'=>$rowsCount
    ];
    #endregion preparation of information for UI - with separated preparation of DataDictionary

    uksort($responseContent['DD']['transformationDictionary'],function($a,$b){
      return strnatcasecmp($a,$b);
    });
    uksort($responseContent['DD']['dataDictionary'],function($a,$b){
      return strnatcasecmp($a,$b);
    });

    $responseContent['status'] = 'ok';
    $responseContent['miner_type'] = $miner->type;
    $responseContent['miner_name'] = $miner->name;

    if ($miner->ruleSet){
      $ruleSet=$miner->ruleSet;
    }else{
      $ruleSet=$this->ruleSetsFacade->saveNewRuleSetForUser($miner->name,$this->getCurrentUser());
      $miner->ruleSet=$ruleSet;
      $this->minersFacade->saveMiner($miner);
    }

    $responseContent['miner_ruleset'] = ['id'=>$ruleSet->ruleSetId, 'name'=>$ruleSet->name];

    if ($breTest=$this->breTestsFacade->findBreTestByRulesetAndMiner($ruleSet,$miner)){
      $responseContent['bre_test']=['id'=>$breTest->breTestId,'name'=>$breTest->name];
    }

    $responseContent['miner_config'] = $miner->getExternalConfig();

    $this->sendJsonResponse($responseContent);
  }


  /**
   * Action for display of EasyMiner-MiningUI
   * @param int $id - Miner ID
   */
  public function renderDefault($id) {
    $miner=$this->findMinerWithCheckAccess($id);
    $this->template->miner=$miner;
    $this->template->encodedApiKey = $this->getCurrentUser()->encodedApiKey;

    $this->template->layout='emptyHtml';
    $this->template->javascriptFiles = MiningUIIntegration::getJavascriptFiles();
    $this->template->cssFiles = MiningUIIntegration::getCssFiles();
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
  /**
   * @param MetasourcesFacade $metasourcesFacade
   */
  public function injectMetasourcesFacade(MetasourcesFacade $metasourcesFacade) {
    $this->metasourcesFacade=$metasourcesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
  /**
   * @param BreTestsFacade $breTestsFacade
   */
  public function injectBreTestsFacade(BreTestsFacade $breTestsFacade){
    $this->breTestsFacade=$breTestsFacade;
  }
  #endregion injections
} 