<?php

namespace EasyMinerCenter\Model\EasyMiner\Facades;
use EasyMinerCenter\Model\EasyMiner\Entities\Metasource;
use EasyMinerCenter\Model\EasyMiner\Entities\User;

/**
 * Class MetasourcesFacade - fasáda pro práci s jednotlivými metasources (datasety)
 *
 * @package EasyMinerCenter\Model\EasyMiner\Facades
 * @author Stanislav Vojíř
 */
class MetasourcesFacade {//TODO implementovat...

  /**
   * Funkce pro export pole s informacemi z TransformationDictionary
   * @param Metasource|null $metasource
   * @param User $user
   * @return array
   */
  public function exportTransformationDictionaryArr(Metasource $metasource=null, User $user) {
    $output = [];

    $this->updateMetasourceAttributes($metasource, $user);//aktualizace seznamu datových sloupců
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


}