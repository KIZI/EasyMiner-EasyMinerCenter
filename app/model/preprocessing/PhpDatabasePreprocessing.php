<?php
namespace App\Model\Preprocessing;

use App\Model\Data\Entities\DbColumn;
use App\Model\Data\Facades\DatabasesFacade;
use App\Model\EasyMiner\Entities\Attribute;
use App\Model\EasyMiner\Entities\DatasourceColumn;
use App\Model\EasyMiner\Entities\Metasource;
use App\Model\Rdf\Entities\Preprocessing;
use App\Model\Rdf\Repositories\PreprocessingsRepository;
use Nette\Utils\Strings;

class PhpDatabasePreprocessing implements IPreprocessingDriver{
  /** @var  PreprocessingsRepository $preprocessingsRepository */
  private $preprocessingsRepository;
  /** @var  DatabasesFacade $databasesFacade */
  private $databasesFacade;

  public function __construct(PreprocessingsRepository $preprocessingsRepository, DatabasesFacade $databasesFacade){
    $this->preprocessingsRepository=$preprocessingsRepository;
    $this->databasesFacade=$databasesFacade;
  }

  /**
   * Funkce pro vygenerování preprocessingu
   * @param Attribute $attribute
   */
  public function generateAttribute(Attribute $attribute) {
    $datasourceColumn=$attribute->datasourceColumn;
    $metasource=$attribute->metasource;
    $preprocessing=$this->preprocessingsRepository->findPreprocessing($attribute->preprocessingId);
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
      foreach ($valuesArr as $id=>$value){
        $this->databasesFacade->updateColumnValueById($metasource->attributesTable,$attribute->name,$id,$function($value),DatabasesFacade::SECOND_DB);
      }
    }
  }

  /**
   * Metoda vracející určovací funkci pro preprocessing each value - one category;
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
    //TODO připravení hodnot nového sloupce na základě příslušnosti jednotlivých hodnot do valuesBins
    $valuesBins=$preprocessing->valuesBins;
    //připravení pole pro jednoduché přiřazování hodnot
    $finalValuesArr=array();
    foreach ($valuesBins as $valuesBin){
      $name=$valuesBin->name;
      $attributeStrLen=max($attributeStrLen,Strings::length($name));
      $values=$valuesBin->values;
      if (!empty($values)){
        foreach ($values as $value){
          $finalValuesArr[$value->value]=$valuesBin->name;
        }
      }
    }
    if (!empty($finalValuesArr)){
      return function($value)use($finalValuesArr){
        if (isset($finalValuesArr[$value])){
          return $finalValuesArr[$value];
        }else{
          return null;
        }
      };
    }
    //TODO funkce pro přiřazování intervalů!!!
    return function($value){
      return null;
    };
  }

}