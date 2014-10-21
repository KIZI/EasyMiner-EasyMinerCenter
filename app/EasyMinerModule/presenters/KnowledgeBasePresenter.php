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

  /**
   * Akce pro výběr metaatributu k daném datovému sloupci
   * @param int $datasource
   * @param int $column
   */
  public function renderSelectMetaAttribute($datasource,$column){//TODO stránkování metaatributů
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    $this->checkDatasourceAccess($datasourceColumn->datasource);

    $this->template->datasourceColumn=$datasourceColumn;
    $this->template->metaAttributes=$this->metaAttributesFacade->findMetaAttributes();
  }

  /**
   * Akce pro výběr odpovídajícího formátu
   * @param int $datasource
   * @param int $column
   * @param string $metaAttribute - uri metaatributu
   */
  public function renderSelectFormat($datasource,$column,$metaAttribute){
    $datasourceColumn=$this->datasourcesFacade->findDatasourceColumn($datasource,$column);
    $this->checkDatasourceAccess($datasourceColumn->datasource);
    try{
      $this->template->metaAttribute=$this->metaAttributesFacade->findMetaAttribute($metaAttribute);
    }catch (\Exception $e){
      $this->flashMessage($this->translate('Requested meta-attribute not found!'),'error');
      $this->redirect('KnowledgeBase:selectMetaAttribute',array('datasource'=>$datasource,'column'=>$column));
    }
    $this->template->formats=$this->template->metaAttribute->formats;
  }




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