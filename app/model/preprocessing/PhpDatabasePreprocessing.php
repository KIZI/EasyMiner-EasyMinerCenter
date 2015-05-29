<?php
namespace App\Model\Preprocessing;

use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\EasyMiner\Entities\Attribute;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Interval;
use App\Model\EasyMiner\Entities\Metasource;
use App\Model\EasyMiner\Entities\Preprocessing;
use App\Model\EasyMiner\Facades\PreprocessingsFacade;
use Nette\Utils\Strings;

class PhpDatabasePreprocessing implements IPreprocessingDriver{
  /** @var  PreprocessingsFacade $preprocessingsFacade */
  private $preprocessingsFacade;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;

  public function __construct(PreprocessingsFacade $preprocessingsFacade, DatabasesFacade $databasesFacade){
    $this->preprocessingsFacade=$preprocessingsFacade;
    $this->databasesFacade=$databasesFacade;
  }

  /**
   * Funkce pro vygenerování preprocessingu
   * @param Attribute $attribute
   */
  public function generateAttribute(Attribute $attribute){
    $datasourceColumn=$attribute->datasourceColumn;
    $metasource=$attribute->metasource;
    $preprocessing=$attribute->preprocessing;
    if ($preprocessing->specialType==Preprocessing::SPECIALTYPE_EACHONE){
      $function=$this->generateAttributeEachOneFunction();
      if ($datasourceColumn->strLen){
        $attributeStrLen=$datasourceColumn->strLen;
      }else{
        $attributeStrLen=100;
      }
    }else{
      $attributeStrLen=0;
      $function=$this->generateAttributeValuesBinsFunction($preprocessing,$attributeStrLen);
    }
    $this->generateAttributeUsingPreprocessingFunction($datasourceColumn,$metasource,$attribute, $attributeStrLen,$function);
  }

  /**
   * Funkce, která projde všechny hodnoty v daném zdrojovém sloupci a předzpracované je překopíruje do cílové tabulky
   * @param DatasourceColumn $datasourceColumn
   * @param Metasource $metasource
   * @param Attribute $attribute
   * @param int $attributeStrLen
   * @param callable $function
   * @throws \Exception
   */
  private function generateAttributeUsingPreprocessingFunction(DatasourceColumn $datasourceColumn,Metasource $metasource,Attribute $attribute, $attributeStrLen, $function){
    $datasource=$datasourceColumn->datasource;
    $this->databasesFacade->openDatabase($datasource->getDbConnection(),$metasource->getDbConnection());
    //vytvoření DB sloupce
    $dbColumn=new DbColumn();
    $dbColumn->name=$attribute->name;
    $dbColumn->dataType=DbColumn::TYPE_STRING;
    $dbColumn->strLength=$attributeStrLen;
    try{
      $this->databasesFacade->createColumn($metasource->attributesTable,$dbColumn,DatabasesFacade::SECOND_DB);
    }catch (\Exception $e){
      throw new \Exception('Attribute creation failed!',$e);
    }
    $valuesArr=$this->databasesFacade->getColumnValuesWithId($datasource->dbTable,$datasourceColumn->name,DatabasesFacade::FIRST_DB);
    if (!empty($valuesArr)){
      $updateDataArr=array();
      foreach ($valuesArr as $id=>$value){
        //TODO $arr=[$metasource->attributesTable,$attribute->name,$id,$function($value)];
        $updateDataArr[$id]=$function($value);
      }
      $this->databasesFacade->multiUpdateColumnValueById($metasource->attributesTable,$attribute->name,$updateDataArr,DatabasesFacade::SECOND_DB);
    }
  }

  /**
   * Metoda vracející určovací funkci pro preprocessing each value - one bin
   * @return callable
   */
  private function generateAttributeEachOneFunction(){
    return function($value){
      return $value;
    };
  }

  /**
   * Metoda vracející určovací funkci pro určování finálních hodnot preprocessingu
   * @param Preprocessing $preprocessing
   * @param int &$attributeStrLen - délka názvů jednotlivých skupin
   * @return callable
   */
  private function generateAttributeValuesBinsFunction(Preprocessing $preprocessing, &$attributeStrLen){
    $valuesBins=$preprocessing->valuesBins;
    //připravení pole pro jednoduché přiřazování hodnot
    $finalValuesArr=array();
    /** @var Interval[] $finalIntervalsArr */
    $finalIntervalsArr=array();
    foreach ($valuesBins as $valuesBin){
      $name=$valuesBin->name;
      $attributeStrLen=max($attributeStrLen,Strings::length($name));
      $values=$valuesBin->values;
      if (!empty($values)){

        foreach ($values as $value){
          $finalValuesArr[$value->value]=$valuesBin->name;
        }
      }
      $intervals=$valuesBin->intervals;
      if (!empty($intervals)){
        foreach($intervals as $interval){
          $finalIntervalsArr[$valuesBin->name]=$interval;
        }
      }
    }
    if (!empty($finalValuesArr)){
      $result=function($value)use($finalValuesArr){
        if (isset($finalValuesArr[$value])){
          return $finalValuesArr[$value];
        }else{
          return null;
        }
      };
      return $result;
    }

    if (!empty($finalIntervalsArr)){
      $result=function($value)use($finalIntervalsArr){
        foreach($finalIntervalsArr as $key=>$interval){
          if ($interval->containsValue($value)){
            return $key;
          }
        }
        return null;
      };
      return $result;
    }

    return function(){return null;};
  }

}