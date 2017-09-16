<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;
use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Datasource;
use EasyMinerCenter\Model\EasyMiner\Entities\Interval;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\MetasourceTask;
use EasyMinerCenter\Model\EasyMiner\Entities\Miner;
use EasyMinerCenter\Model\EasyMiner\Entities\Preprocessing;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Repositories\AttributesRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\MetasourcesRepository;
use EasyMinerCenter\Model\EasyMiner\Repositories\MetasourceTasksRepository;
use EasyMinerCenter\Model\Preprocessing\Databases\IPreprocessing;
use EasyMinerCenter\Model\Preprocessing\Databases\PreprocessingFactory;
use EasyMinerCenter\Model\Preprocessing\Entities\PpAttribute;
use EasyMinerCenter\Model\Preprocessing\Entities\PpConnection;
use EasyMinerCenter\Model\Preprocessing\Entities\PpDataset;
use EasyMinerCenter\Model\Preprocessing\Entities\PpTask;
use EasyMinerCenter\Model\Preprocessing\Entities\PpValue;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingCommunicationException;
use EasyMinerCenter\Model\Preprocessing\Exceptions\PreprocessingException;

/**
 * Class MetasourcesFacade - fasáda pro práci s jednotlivými metasources (datasety)
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class MetasourcesFacade {
  /** @var PreprocessingFactory $preprocessingFactory */
  private $preprocessingFactory;
  /** @var AttributesRepository $attributesRepository */
  private $attributesRepository;
  /** @var MetasourcesRepository $metasourcesRepository*/
  private $metasourcesRepository;
  /** @var MetasourceTasksRepository $metasourceTasksRepository */
  private $metasourceTasksRepository;
  /** @var DatasourcesFacade $datasourcesFacade */
  private $datasourcesFacade;
  /** @var  MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;

  const LONG_NAMES_MAX_LENGTH=255;
  const SHORT_NAMES_MAX_LENGTH=40;

  /**
   * Method for exporting array with info from TransformationDictionary
   * @param Metasource $metasource
   * @param User $user
   * @param int &rowsCount = null - počet řádků v datasource
   * @return array
   */
  public function exportTransformationDictionaryArr(Metasource $metasource, User $user, &$rowsCount = null) {
    $output = [];
    $this->updateMetasourceAttributes($metasource, $user);//update list of attributes

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
  }

  /**
   * Method for finding metasource by the given MetasourceId
   * @param int $id
   * @return Metasource
   */
  public function findMetasource($id) {
    return $this->metasourcesRepository->find($id);
  }

  /**
   * Method for finding MetasourceTask by MetasourceTaskId
   * @param int $id
   * @return MetasourceTask
   * @throws \EasyMinerCenter\Exceptions\EntityNotFoundException
   */
  public function findMetasourceTask($id) {
    return $this->metasourceTasksRepository->find($id);
  }


  /**
   * Method for finding all metasources based on the selected datasource
   * @param int|Datasource $datasource
   * @return Metasource[]|null
   */
  public function findMetasourcesByDatasource($datasource){
    if ($datasource instanceof Datasource){
      $datasource=$datasource->datasourceId;
    }
    return $this->metasourcesRepository->findAllBy(array('datasource_id'=>$datasource));
  }

  /**
   * Method for saving of MetasourceTask
   * @param MetasourceTask $metasourceTask
   */
  public function saveMetasourceTask(MetasourceTask $metasourceTask) {
    $this->metasourceTasksRepository->persist($metasourceTask);
  }

  /**
   * Method for saving of Metasource
   * @param Metasource $metasource
   */
  public function saveMetasource(Metasource $metasource) {
    $this->metasourcesRepository->persist($metasource);
  }

  /**
   * Method for deleting of MetasourceTask
   * @param MetasourceTask $metasourceTask
   * @return int
   * @throws \LeanMapper\Exception\InvalidStateException
   */
  public function deleteMetasourceTask(MetasourceTask $metasourceTask) {
    return $this->metasourceTasksRepository->delete($metasourceTask);
  }

  /**
   * Method returning list of preprocessing types, supported by the given preprocessing driver
   * @param Metasource $metasource
   * @param User $user
   * @return string[]
   */
  public function getSupportedPreprocessingTypes(Metasource $metasource, User $user) {
    /** @var IPreprocessing $preprocessing */
    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->getPpConnection(), $user);
    return $preprocessing->getSupportedPreprocessingTypes();
  }

  /**
   * Method for updating the info about attributes in database
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

    #region prepare list of actually existing metasource attributes
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
    #endregion prepare list of actually existing metasource attributes

    #region update list of attributes gained from database
    if (!empty($ppAttributes)){
      foreach($ppAttributes as $ppAttribute){
        if (!empty($ppAttribute->id) && is_int($ppAttribute->id) && isset($existingAttributesByPpDatasetAttributeId[$ppAttribute->id])){
          //attribute with the given ID already exists in the metasource (remote database)
          $attribute=$existingAttributesByPpDatasetAttributeId[$ppAttribute->id];
          $modified=false;
          if ($attribute->name!=$ppAttribute->name){
            $attribute->name=$ppAttribute->name;
            $modified=true;
          }
          if ($attribute->uniqueValuesCount!=$ppAttribute->uniqueValuesSize){
            $attribute->uniqueValuesCount=$ppAttribute->uniqueValuesSize;
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
          //find attribute
          $attribute=$existingAttributesByName[$ppAttribute->name];
          if (!empty($ppAttribute->id) && $attribute->ppDatasetAttributeId!=$ppAttribute->id){
            $attribute->ppDatasetAttributeId=$ppAttribute->id;
            $attribute->uniqueValuesCount=$ppAttribute->uniqueValuesSize;
            $attribute->active=true;
            $this->saveAttribute($attribute);
          }elseif (!$attribute->active){
            $attribute->uniqueValuesCount=$ppAttribute->uniqueValuesSize;
            $attribute->active=true;
            $this->saveAttribute($attribute);
          }
          unset($existingAttributesByName[$ppAttribute->name]);
        }elseif (!empty($ppAttribute->field)){
          //we have there new attribute
          $attribute=new Attribute();
          $attribute->metasource=$metasource;
          try{
            $datasourceColumn=$this->datasourcesFacade->findDatasourceColumnByDbDatasourceColumnId($datasource,$ppAttribute->field);
          }catch (\Exception $e){
            $datasourceColumn=null;
          }
          $attribute->datasourceColumn=$datasourceColumn;
          $attribute->name=$ppAttribute->name;
          $attribute->uniqueValuesCount=$ppAttribute->uniqueValuesSize;
          if (is_int($ppAttribute->id)){
            $attribute->ppDatasetAttributeId=$ppAttribute->id;
          }
          $attribute->active=true;
          $attribute->type=$ppAttribute->type;
          $this->saveAttribute($attribute);
        }
      }
    }
    #endregion update list of columns gained from metasource

    #region deactivate currently not existing attributes
    //TODO kontrola, jestli je není možné kompletně smazat (pokud ještě nebyly dovytvořeny v rámci preprocessingu)
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
    #endregion deactivate currently not existing attributes

    //update object $metasource from database
    $metasource=$this->findMetasource($metasource->metasourceId);

    #region cleanup preprocessing tasks
    if (!empty($metasource->metasourceTasks)){
      $attributesArr=$metasource->getAttributesArr();
      if (!empty($attributesArr)){
        foreach($metasource->metasourceTasks as $metasourceTask){
          if ($metasourceTask->type==MetasourceTask::TYPE_PREPROCESSING){
            if (!empty($metasourceTask->attributes)){
              $finished=true;
              foreach($metasourceTask->attributes as $taskAttribute){
                if (!$taskAttribute->active){
                  $finished=false;
                }
              }
              if ($finished){
                $this->metasourceTasksRepository->delete($metasourceTask);
              }
            }
          }
        }
      }
    }
    #endregion cleanup preprocessing tasks
  }

  /**
   * @param int $attributeId
   * @return Attribute
   */
  public function findAttribute($attributeId){
    return $this->attributesRepository->find($attributeId);
  }

  /**
   * @param Attribute|int $attribute
   * @param int $offset = 0
   * @param int $limit = 1000
   * @return PpValue[]
   */
  public function getAttributePpValues($attribute,$offset=0,$limit=1000){
    if (!($attribute instanceof Attribute)){
      $attribute=$this->findAttribute($attribute);
    }
    $metasource=$attribute->metasource;
    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->getPpConnection(),$metasource->user);
    $ppDataset = $preprocessing->getPpDataset($metasource->ppDatasetId?$metasource->ppDatasetId:$metasource->getDbTable());
    return $preprocessing->getPpValues($ppDataset,$attribute->ppDatasetAttributeId,$offset,$limit);
  }

  /**
   * @param Attribute &$attribute
   * @return Attribute
   */
  public function saveAttribute(Attribute $attribute) {
    $this->attributesRepository->persist($attribute);
    return $attribute;
  }

  /**
   * @param PreprocessingFactory $preprocessingFactory
   * @param AttributesRepository $attributesRepository
   * @param MetasourcesRepository $metasourcesRepository
   * @param MetasourceTasksRepository $metasourceTasksRepository
   * @param DatasourcesFacade $datasourcesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function __construct(PreprocessingFactory $preprocessingFactory, AttributesRepository $attributesRepository, MetasourcesRepository $metasourcesRepository, MetasourceTasksRepository $metasourceTasksRepository, DatasourcesFacade $datasourcesFacade, MetaAttributesFacade $metaAttributesFacade) {
    $this->preprocessingFactory=$preprocessingFactory;
    $this->attributesRepository=$attributesRepository;
    $this->metasourcesRepository=$metasourcesRepository;
    $this->metasourceTasksRepository=$metasourceTasksRepository;
    $this->datasourcesFacade=$datasourcesFacade;
    $this->metaAttributesFacade=$metaAttributesFacade;
  }

  #region initialization of metasource
  /**
   * Method for initialization of Metasource for the given Miner
   * @param Miner $miner
   * @param MinersFacade $minersFacade
   * @return MetasourceTask
   */
  public function startMinerMetasourceInitialization(Miner $miner, MinersFacade $minersFacade) {
    //create new metasource
    $ppType = $this->preprocessingFactory->getPreprocessingTypeByDatabaseType($miner->datasource->type);
    /** @var PpConnection $ppConnection - DB/API connection for preprocessing */
    $ppConnection = $this->preprocessingFactory->getDefaultPpConnection($ppType, $miner->user);
    $metasource=Metasource::newFromPpConnection($ppConnection);
    $metasource->datasource=$miner->datasource;
    $metasource->state=Metasource::STATE_PREPARATION;
    $metasource->user=$miner->user;
    $this->saveMetasource($metasource);

    //connect metasource to miner
    $miner->metasource=$metasource;
    $minersFacade->saveMiner($miner);

    //create task for initialization of metasource
    $metasourceTask = new MetasourceTask();
    $metasourceTask->type=MetasourceTask::TYPE_INITIALIZATION;
    $metasourceTask->state=MetasourceTask::STATE_NEW;
    $metasourceTask->metasource=$metasource;
    $this->saveMetasourceTask($metasourceTask);
    return $metasourceTask;
  }

  /**
   * Method for initialization of metasource using preprocessing driver
   * @param Miner $miner
   * @param MetasourceTask $metasourceTask
   * @return MetasourceTask
   * @throws \Exception
   */
  public function initializeMinerMetasource(Miner $miner, MetasourceTask $metasourceTask) {
    $metasource=$metasourceTask->metasource;
    if ($miner->metasource->metasourceId!=$metasource->metasourceId){
      throw new \InvalidArgumentException('Different metasource confing in miner and metasource task!');
    }

    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->getPpConnection(), $miner->user);
    if ($metasourceTask->state==MetasourceTask::STATE_NEW){
      //initialization
      $datasource=$metasource->datasource;
      $result=$preprocessing->createPpDataset(new PpDataset(null,$miner->name,$datasource->dbDatasourceId?$datasource->dbDatasourceId:$datasource->dbTable,null,null),null);
    }elseif($metasourceTask->state==MetasourceTask::STATE_IN_PROGRESS){
      //check the metasource task state
      try{
        $result=$preprocessing->createPpDataset(null,$metasourceTask->getPpTask());
      }catch (PreprocessingCommunicationException $e){
        if($e->getCode()==404){
          //if there is no currently running preprocessing task, try to retry the initialization
          $this->deleteMetasourceTask($metasourceTask);//delete existing MetasourceTask
          //send request for preparing of new metasource (PpDataset)
          $datasource=$metasource->datasource;
          $result=$preprocessing->createPpDataset(new PpDataset(null,$miner->name,$datasource->dbDatasourceId?$datasource->dbDatasourceId:$datasource->dbTable,null,null),null);
        }else{
          throw $e;
        }
      }
    }else{
      return $metasourceTask;
    }

    if ($result instanceof PpTask){
      //there was created a long running task
      $metasourceTask->state=MetasourceTask::STATE_IN_PROGRESS;
      $metasourceTask->setPpTask($result);
      $this->saveMetasourceTask($metasourceTask);
    }elseif($result instanceof PpDataset){
      //dataset created successfully
      $metasource->state=Metasource::STATE_AVAILABLE;
      $metasource->ppDatasetId=$result->id;
      $metasource->size=$result->size;
      $metasource->type=$result->type;
      $this->saveMetasource($metasource);
      $metasourceTask->state=MetasourceTask::STATE_DONE;
      $this->saveMetasourceTask($metasourceTask);
    }else{
      throw new \Exception('Unexpected type of result!');
    }

    return $metasourceTask;
  }

  #endregion initialization of metasource

  /**
   * Method for checking the availability of metasource (and update of list of attributes)
   * @param Metasource $metasource
   * @throws \Exception
   */
  public function checkMetasourceState(Metasource $metasource) {
    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->getPpConnection(),$metasource->user);
    $ppDataset = $preprocessing->getPpDataset($metasource->ppDatasetId?$metasource->ppDatasetId:$metasource->getDbTable());
    //TODO kontrola, jestli je ppDataset dostupný!
    $ppAttributes=$preprocessing->getPpAttributes($ppDataset);
    //TODO aktualizace seznamu sloupců

  }

  /**
   * Method for deleting the metasource
   * @param Metasource $metasource
   * @return int
   * @throws \LeanMapper\Exception\InvalidStateException
   */
  public function deleteMetasource(Metasource $metasource) {
    $this->deleteMetasourceData($metasource);
    return $this->metasourcesRepository->delete($metasource);
  }

  /**
   * Method for deleting data from metasource
   * @param Metasource $metasource
   * @throws \LeanMapper\Exception\InvalidStateException
   * @throws PreprocessingException
   */
  public function deleteMetasourceData(Metasource $metasource) {
    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->getPpConnection(),$metasource->user);
    $ppDataset = new PpDataset($metasource->ppDatasetId,$metasource->name,null,$metasource->type,$metasource->size);
    $preprocessing->deletePpDataset($ppDataset);
    $metasource->ppDatasetId=null;
    $metasource->state=Metasource::STATE_UNAVAILABLE;
    $this->saveMetasource($metasource);
  }

  /**
   * Method returning info about the support of long column names in metasource
   * @param Metasource $metasource
   * @return bool
   */
  public function metasourceSupportsLongNames(Metasource $metasource){
    return $this->datasourcesFacade->dbTypeSupportsLongNames($metasource->type);
  }

  #region preprocessing of attributes
  /**
   * Method for preparation of metasourcetask for preprocessing of attributes
   * @param Metasource $metasource
   * @param Attribute[] $attributes
   * @return MetasourceTask
   */
  public function startAttributesPreprocessing(Metasource $metasource,array $attributes) {
    if (empty($attributes)){
      throw new \BadMethodCallException('No attributes found.');
    }

    //create task for preprocessing of attributes
    $metasourceTask = new MetasourceTask();
    $metasourceTask->type=MetasourceTask::TYPE_PREPROCESSING;
    $metasourceTask->state=MetasourceTask::STATE_NEW;
    $metasourceTask->metasource=$metasource;
    $this->saveMetasourceTask($metasourceTask);
    foreach($attributes as $attribute){
      if ($attribute instanceof Attribute){
        $metasourceTask->addToAttributes($attribute);
      }else{
        throw new \BadMethodCallException('Requested attribute not found!');
      }
    }
    $this->saveMetasourceTask($metasourceTask);
    return $metasourceTask;
  }

  /**
   * Method for preprocessing of attributes using preprocessing driver
   * @param MetasourceTask $metasourceTask
   * @return MetasourceTask
   * @throws \Exception
   */
  public function preprocessAttributes(MetasourceTask $metasourceTask) {
    $metasource=$metasourceTask->metasource;
    $preprocessing=$this->preprocessingFactory->getPreprocessingInstance($metasource->ppConnection, $metasource->user);

    if ($metasourceTask->state==MetasourceTask::STATE_NEW){
      //execute new preprocessing
      $result=$preprocessing->createAttributes($metasourceTask->attributes,null);
    }elseif($metasourceTask->state==MetasourceTask::STATE_IN_PROGRESS){
      //check state of already running preprocessing task
      try{
        $result=$preprocessing->createAttributes(null,$metasourceTask->getPpTask());
      }catch (PreprocessingCommunicationException $e){
        if($e->getCode()==404){
          //task was not found, mark it as finished
          $metasourceTask->state=MetasourceTask::STATE_DONE;
          return $metasourceTask;
        }else{
          throw $e;
        }
      }
    }else{
      return $metasourceTask;
    }

    if ($result instanceof PpTask){
      $metasourceTask->state=MetasourceTask::STATE_IN_PROGRESS;
      $metasourceTask->setPpTask($result);
      $this->saveMetasourceTask($metasourceTask);
      return $metasourceTask;
    }elseif(is_array($result)){
      //prepare working array for assignment od fieldIds to individual names of created attributes
      $resultAttributes=[];
      foreach($result as $resultItem){
        $resultAttributes[$resultItem->name]=['id'=>$resultItem->id,'uniqueValuesSize'=>@$resultItem->uniqueValuesSize];//TODO
      }
      $attributes=$metasourceTask->attributes;
      $ppDataset=$preprocessing->getPpDataset($metasource->ppDatasetId);
      foreach($attributes as $attribute){
        if (isset($resultAttributes[$attribute->name])){
          $attribute->ppDatasetAttributeId=$resultAttributes[$attribute->name]['id'];
          $attribute->uniqueValuesCount=$resultAttributes[$attribute->name]['uniqueValuesSize'];
          $attribute->active=true;
          $this->saveAttribute($attribute);

          if (!empty($attribute->preprocessing->specialType) && in_array($attribute->preprocessing->specialType,Preprocessing::getSpecialTypesWithoutEachOne())){
            //we need to parse intervals from preprocessed values
            /** @var PpAttribute $ppAttribute */
            $ppAttribute=$preprocessing->getPpAttribute($ppDataset,$attribute->ppDatasetAttributeId);
            $unprocessedIntervalsCount=$ppAttribute->uniqueValuesSize;
            $createdIntervalValuesArr=[];
            while ($unprocessedIntervalsCount>0){
              $ppValues=$preprocessing->getPpValues($ppDataset,$ppAttribute->id,0,100);
              if (!empty($ppValues)){
                foreach($ppValues as $ppValue){
                  $createdIntervalValuesArr[]=$ppValue->value;
                }
              }
              $unprocessedIntervalsCount-=100;
            }
            $this->updateAttributePreprocessingFromPpIntervalValues($attribute->preprocessing,$createdIntervalValuesArr);
          }
        }
      }
      $metasourceTask->state=MetasourceTask::STATE_DONE;
      return $metasourceTask;
    }else{
      throw new \Exception('Unexpected type of result!');
    }
  }

  /**
   * Method for updating preprocessing definition using names of created intervals
   * @param Preprocessing $preprocessing
   * @param string[] $createdIntervalValues
   */
  private function updateAttributePreprocessingFromPpIntervalValues(Preprocessing $preprocessing, array $createdIntervalValues){
    $preprocessing->specialType='';
    $preprocessing->setSpecialTypeParams(null);
    if (count($createdIntervalValues)){
      foreach($createdIntervalValues as $createdIntervalValue){
        try{
          $interval=Interval::createFromString($createdIntervalValue);
        }catch(\Exception $e){
          continue;
        }
        $interval->format=$preprocessing->format;
        $this->metaAttributesFacade->saveInterval($interval);
        $valuesBin=new ValuesBin();
        $valuesBin->name=$createdIntervalValue;
        $valuesBin->format=$preprocessing->format;
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $valuesBin->addToIntervals($interval);
        $this->metaAttributesFacade->saveValuesBin($valuesBin);
        /** @noinspection PhpMethodParametersCountMismatchInspection */
        $preprocessing->addToValuesBins($valuesBin);
      }
    }
    $this->metaAttributesFacade->savePreprocessing($preprocessing);
  }
  #endregion preprocessing of attributes

}