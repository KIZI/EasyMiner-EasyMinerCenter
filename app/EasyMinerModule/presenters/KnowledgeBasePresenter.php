<?php
namespace App\EasyMinerModule\Presenters;

use App\Model\EasyMiner\Facades\DatasourcesFacade;
use App\Model\EasyMiner\Facades\UsersFacade;
use App\Model\Rdf\Facades\MetaAttributesFacade;

/**
 * Class KnowledgeBasePresenter - presenter pro práci s knowledge base v uživatelském prostředí EasyMineru
 * @package App\EasyMinerModule\Presenters
 */
class KnowledgeBasePresenter extends BasePresenter{

  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;



  #region injections
  public function injectDatasourcesFacade(DatasourcesFacade $datasourcesFacade){
    $this->datasourcesFacade=$datasourcesFacade;
  }
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  public function injectMetaAttributesFacade(MetaAttributesFacade $metaAttributesFacade){
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
  #endregion injections
} 