<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Repositories\AttributesRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\MetasourcesRepository;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;

/**
 * Class MetasourcesFacade - fasáda pro práci s jednotlivými metasources (datasety)
 *
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 */
class MetasourcesFacade {
  /** @var PreprocessingFactory $preprocessingFactory */
  private $preprocessingFactory;
  /** @var AttributesRepository $attributesRepository */
  private $attributesRepository;
  /** @var MetasourcesRepository $metasourcesRepository*/
  private $metasourcesRepository;
  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;

  /**
   * Funkce pro export pole s informacemi z TransformationDictionary
   * @param Metasource $metasource
   * @param User $user
   * @param int &rowsCount = null - počet řádků v datasource
   * @return array
   */
  public function exportTransformationDictionaryArr(Metasource $metasource, User $user, &$rowsCount = null) {
    $output = [];
    $this->updateMetasourceAttributes($metasource, $user);//aktualizace seznamu datových sloupců

    foreach($metasource->attributes as $attribute){
      if (!$attribute->active){continue;}
      $output[$attribute->attributeId]=[
        'name'=>$attribute->name,
        'type'=>$attribute->type,
        'choices'=>[]
      ];
    }
    $rowsCount = $metasource->size;
    return $output;
////    #region atributy
////
////
////    $this->databasesFacade->openDatabase($metasource->getDbConnection());
////    foreach($metasource->attributes as $attribute) {
////      $valuesArr=array();
////      try{
////        $valuesStatistics=$this->databasesFacade->getColumnValuesStatistic($metasource->attributesTable,$attribute->name,true);
////        if (!empty($valuesStatistics->valuesArr)){
////          foreach ($valuesStatistics->valuesArr as $value=>$count){
////            $valuesArr[]=$value;
////          }
////        }
////      }catch (\Exception $e){}
////      $output['transformationDictionary'][$attribute->name]=array('choices'=>$valuesArr);
////    }
////    #endregion atributy
////
////    return $output;
  }

  /**
   * Funkce pro nalezení metasource dle zadaného ID
   * @param int $id
   * @return Metasource
   */
  public function findMetasource($id) {
    return $this->metasourcesRepository->find($id);
  }

  /**
   * Funkce pro aktualizaci info o atributech v DB
   * @param Metasource &$metasource
   * @param User $user
   */
  public function updateMetasourceAttributes(Metasource &$metasource, User $user) {
    /** @var IPreprocessing $preprocessing */
    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->getPpConnection(), $user);

    $ppDataset=$preprocessing->getPpDataset($metasource->ppDatasetId?$metasource->ppDatasetId:$metasource->name);
    $datasource=$metasource->datasource;
    $metasource->size=$ppDataset->size;
    $ppAttributes=$preprocessing->getPpAttributes($ppDataset);

    #region připravení seznamu aktuálně existujících datasourceColumns
    /** @var Attribute[] $existingAttributesByPpDatasetAttributeId */
    $existingAttributesByPpDatasetAttributeId=[];
    /** @var Attribute[] $existingAttributesByName */
    $existingAttributesByName=[];
    /** @var Attribute[] $attributes */
    $attributes=$metasource->attributes;
    if (!empty($attributes)){
      foreach ($attributes as &$attribute){
        if (!empty($attribute->ppDatasetAttributeId)){
          $existingAttributesByPpDatasetAttributeId[$attribute->ppDatasetAttributeId]=$attribute;
        }else{
          $existingAttributesByName[$attribute->name]=$attribute;
        }

      }
    }
    #endregion

    #region aktualizace seznamu sloupců získaných z DB
    if (!empty($ppAttributes)){
      foreach($ppAttributes as $ppAttribute){
        if (!empty($ppAttribute->id) && is_int($ppAttribute->id) && isset($existingAttributesByPpDatasetAttributeId[$ppAttribute->id])){
          //sloupec s daným ID již je v databázi
          $attribute=$existingAttributesByPpDatasetAttributeId[$ppAttribute->id];
          $modified=false;
          if ($attribute->name!=$ppAttribute->name){
            $attribute->name=$ppAttribute->name;
            $modified=true;
          }
          if (!$attribute->active){
            $modified=true;
            $attribute->active=true;
          }
          if ($modified){
            $this->saveAttribute($attribute);
          }
          unset($existingAttributesByPpDatasetAttributeId[$ppAttribute->id]);
        }elseif(!empty($ppAttribute->name) && isset($existingAttributesByName[$ppAttribute->name])){
          //sloupec najdeme podle jména
          $attribute=$existingAttributesByName[$ppAttribute->name];
          if (!$attribute->active){
            $attribute->active=true;
            $this->saveAttribute($attribute);
          }
          unset($existingAttributesByName[$ppAttribute->name]);
        }elseif (!empty($ppAttribute->field)){
          //máme tu nový datový sloupec (který má svoji vazbu na datasource)
          $attribute=new Attribute();
          $attribute->metasource=$metasource;
          try{
            $datasourceColumn=$this->datasourcesFacade->findDatasourceColumnByDbDatasourceColumnId($datasource,$ppAttribute->field);
          }catch (\Exception $e){
            $datasourceColumn=null;
          }
          $attribute->datasourceColumn=$datasourceColumn;
          $attribute->name=$ppAttribute->name;
          if (is_int($ppAttribute->id)){
            $attribute->ppDatasetAttributeId=$ppAttribute->id;
          }
          $attribute->active=true;
          $attribute->type=$ppAttribute->type;
          $this->saveAttribute($attribute);
        }
      }
    }
    #endregion
    #region deaktivace již neexistujících sloupců
    if (!empty($existingAttributesByPpDatasetAttributeId)){
      foreach($existingAttributesByPpDatasetAttributeId as &$attribute){
        if ($attribute->active){
          $attribute->active=false;
          $this->saveAttribute($attribute);
        }
      }
    }
    if (!empty($existingAttributesByName)){
      foreach($existingAttributesByName as &$attribute){
        if ($attribute->active){
          $attribute->active=false;
          $this->saveAttribute($attribute);
        }
      }
    }
    #endregion
    //aktualizace datového zdroje z DB
    $metasource=$this->findMetasource($metasource->metasourceId);
  }

  /**
   * @param Attribute &$attribute
   * @return Attribute
   */
  public function saveAttribute(Attribute $attribute) {
    $attributeId=$this->attributesRepository->persist($attribute);
    return $this->attributesRepository->find($attributeId);
  }

  /**
   * @param PreprocessingFactory $preprocessingFactory
   * @param AttributesRepository $attributesRepository
   * @param MetasourcesRepository $metasourcesRepository
   * @param DatasourcesFacade $datasourcesFacade
   */
  public function __construct(PreprocessingFactory $preprocessingFactory, AttributesRepository $attributesRepository, MetasourcesRepository $metasourcesRepository, DatasourcesFacade $datasourcesFacade) {
    $this->preprocessingFactory=$preprocessingFactory;
    $this->attributesRepository=$attributesRepository;
    $this->metasourcesRepository=$metasourcesRepository;
    $this->datasourcesFacade=$datasourcesFacade;
  }

  /**
   * Funkce pro inicializaci Metasource pro konkrétní miner
   * @param Miner $miner
   * @return Metasource
   */
  public function initMetasourceForMiner(Miner $miner) {
    //TODO inicializace metasource pomocí ovladače preprocessingu
    $ppType = $this->preprocessingFactory->getPreprocessingTypeByDatabaseType($miner->datasource->type);
    /** @var IPreprocessing $preprocessing */
    $preprocessing = $this->preprocessingFactory->getPreprocessingInstanceWithDefaultPpConnection($ppType, $miner->user);

    $metasource=Metasource::newFromPpConnection($ppConnection);
    $metasource->datasource=$miner->datasource;
    $metasource->available=true;
    $metasourceId=$this->metasourcesRepository->persist($metasource);
    return $this->metasourcesRepository->find($metasourceId);
  }
}