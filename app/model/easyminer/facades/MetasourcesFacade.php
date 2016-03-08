<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
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
class MetasourcesFacade {//TODO implementovat...
  /** @var PreprocessingFactory $preprocessingFactory */
  private $preprocessingFactory;
  /** @var  AttributesRepository $attributesRepository */
  private $attributesRepository;
  /** @var MetasourcesRepository $metasourcesRepository*/
  private $metasourcesRepository;


  /**
   * Funkce pro export pole s informacemi z TransformationDictionary
   * @param Metasource|null $metasource
   * @param User $user
   * @return array
   */
  public function exportTransformationDictionaryArr(Metasource $metasource=null, User $user) {
    $output = [];
    $this->updateMetasourceAttributes($metasource, $user);//aktualizace seznamu datových sloupců

    //TODO... (dodělat)
    foreach($metasource->datasourceColumns as $datasourceColumn){
      if (!$datasourceColumn->active){continue;}
      $output['dataDictionary'][$datasourceColumn->name]=$datasourceColumn->type;
    }

    #region atributy
    $this->databasesFacade->openDatabase($metasource->getDbConnection());
    foreach($metasource->attributes as $attribute) {
      $valuesArr=array();
      try{
        $valuesStatistics=$this->databasesFacade->getColumnValuesStatistic($metasource->attributesTable,$attribute->name,true);
        if (!empty($valuesStatistics->valuesArr)){
          foreach ($valuesStatistics->valuesArr as $value=>$count){
            $valuesArr[]=$value;
          }
        }
      }catch (\Exception $e){}
      $output['transformationDictionary'][$attribute->name]=array('choices'=>$valuesArr);
    }
    #endregion atributy

    return $output;
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
          if ($attribute->type!=$ppAttribute->type){
            $attribute->type=$ppAttribute->type;
            $modified=true;
          }
          if (!$attribute->active){
            $modified=true;
            $attribute->active=true;
          }
          if ($modified){
            $this->attributesRepository->persist($attribute);
          }
          unset($existingAttributesByPpDatasetAttributeId[$ppAttribute->id]);
        }elseif(!empty($ppAttribute->name) && isset($existingAttributesByName[$ppAttribute->name])){
          //sloupec najdeme podle jména
          $attribute=$existingAttributesByName[$ppAttribute->name];
          $modified=false;
          if ($attribute->type!=$ppAttribute->type){
            $attribute->type=$ppAttribute->type;
            $modified=true;
          }
          if (!$attribute->active){
            $attribute->active=true;
            $modified=true;
          }
          if ($modified){
            $this->attributesRepository->persist($attribute);
          }
          unset($existingAttributesByName[$ppAttribute->name]);
        }else{
          //máme tu nový datový sloupec
          $attribute=new Attribute();
          $attribute->metasource=$metasource;
          $attribute->name=$ppAttribute->name;
          if (is_int($ppAttribute->id)){
            $attribute->ppDatasetAttributeId=$ppAttribute->id;
          }
          $attribute->active=true;
          $attribute->type=$ppAttribute->type;
          $this->attributesRepository->persist($attribute);
        }
      }
    }
    #endregion
    #region deaktivace již neexistujících sloupců
    if (!empty($existingAttributesByPpDatasetAttributeId)){
      foreach($existingAttributesByPpDatasetAttributeId as &$attribute){
        if ($attribute->active){
          $attribute->active=false;
          $this->attributesRepository->persist($attribute);
        }
      }
    }
    if (!empty($existingAttributesByName)){
      foreach($existingAttributesByName as &$attribute){
        if ($attribute->active){
          $attribute->active=false;
          $this->attributesRepository->persist($attribute);
        }
      }
    }
    #endregion
    //aktualizace datového zdroje z DB
    $metasource=$this->findMetasource($metasource->metasourceId);
  }


  /**
   * @param PreprocessingFactory $preprocessingFactory
   * @param AttributesRepository $attributesRepository
   * @param MetasourcesRepository $metasourcesRepository
   */
  public function __construct(PreprocessingFactory $preprocessingFactory, AttributesRepository $attributesRepository, MetasourcesRepository $metasourcesRepository) {
    $this->preprocessingFactory=$preprocessingFactory;
    $this->attributesRepository=$attributesRepository;
    $this->metasourcesRepository=$metasourcesRepository;
  }
}