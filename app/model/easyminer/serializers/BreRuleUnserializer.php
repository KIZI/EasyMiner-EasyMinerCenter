<?php

namespace EasyMinerCenter\Model\EasyMiner\Serializers;

use EasyMinerCenter\Model\EasyMiner\Entities\Attribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Cedent;
use EasyMinerCenter\Model\EasyMiner\Entities\Rule;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleAttribute;
use EasyMinerCenter\Model\EasyMiner\Entities\Value;
use EasyMinerCenter\Model\EasyMiner\Entities\ValuesBin;
use EasyMinerCenter\Model\EasyMiner\Facades\MetaAttributesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\MetasourcesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use LeanMapper\Exception\InvalidArgumentException;
use Nette\Utils\Strings;

/**
 * Class BreRuleUnserializer
 * @package EasyMinerCenter\Model\EasyMiner\Serializers
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */
class BreRuleUnserializer{
  /** @var Rule $rule */
  private $rule;
  /** @var RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var MetasourcesFacade $metasourcesFacade */
  private $metasourcesFacade;
  /** @var MetaAttributesFacade $metaAttributesFacade */
  private $metaAttributesFacade;

  /**
   * Metoda pro deserializaci XML, které projde a uloží ho do DB
   * @param \SimpleXMLElement $xml
   * @param Rule|null $existingRule=null
   * @return Rule
   * @throws InvalidArgumentException
   */
  public function unserialize(\SimpleXMLElement $xml, Rule $existingRule=null){
    if (isset($xml->Antecedent) && count($xml->Antecedent->children())>0){
      $antecedent=$this->saveCedent($xml->Antecedent);
    }
    $consequent=$this->saveCedent($xml->Consequent);

    $confidence=floatval((string)@$xml->Rating[0]['confidence']);
    $support=floatval((string)@$xml->Rating[0]['support']);

    if ($existingRule instanceof Rule){
      $rule=$existingRule;
    }else{
      $rule=new Rule();
    }

    #region určení měr zajímavosti
    if ((round(((string)$rule->confidence)*1000000)!=round($confidence*1000000)) || (round(((string)$rule->support)*1000000)!=round($support*1000000))){
      //musíme určit nové hodnoty měr zajímavosti, protože původní pravidlo buď nemá čtyřpolní tabulku, nebo došlo ke změně hodnot měr zajímavosti
      $a=1000000;
      $c=0;

      $b=round(($a*(1-$confidence))/$confidence);
      $d=round($a/$support-$a-$b);

      $confidence=$a/($a+$b);
      $support=$a/($a+$b+$c+$d);

      $rule->a=$a;
      $rule->b=$b;
      $rule->c=$c;
      $rule->d=$d;
      $rule->confidence=$confidence;
      $rule->support=$support;
    }
    #endregion určení měr zajímavosti

    if (!empty($antecedent)){
      $rule->antecedent=$antecedent;
    }
    $rule->consequent=$consequent;

    //TODO textová reprezentace pravidla
    $rule->text='DOPLNIT';

    $rule->task=null;
    $this->rulesFacade->saveRule($rule);
    return $rule;
  }

  /**
   * Metoda pro naparsování a uložení rule atributu
   * @param \SimpleXMLElement $ruleAttributeXml
   * @return RuleAttribute|Cedent
   * @throws \LeanMapper\Exception\InvalidArgumentException
   */
  private function saveRuleAttribute(\SimpleXMLElement $ruleAttributeXml){
    $attributeId=(string)$ruleAttributeXml[0]['attribute'];
    $attribute=$this->metasourcesFacade->findAttribute($attributeId);

    $format=$attribute->preprocessing->format;
    //TODO kontrola existujícího rule atributu

    $dataArr=[];
    if (!empty($ruleAttributeXml->ValuesBin)){
      foreach ($ruleAttributeXml->ValuesBin as $valuesBinXml){
        $valuesBinName=(string)$valuesBinXml;
        try{
          $valuesBin=$this->metaAttributesFacade->findValuesBin($format,$valuesBinName);
        }catch (\Exception $e){/*prostě to není values bin*/}
        if (!empty($valuesBin) && $valuesBin instanceof ValuesBin){
          $dataArr[]=$valuesBin;
        }else{
          try{
            $value=$this->metaAttributesFacade->findValue($format,$valuesBinName);
          }catch (\Exception $e){
            //pokud hodnota neexistuje, tak ji vytvoříme
            $value=new Value();
            $value->format=$format;
            $this->metaAttributesFacade->saveValue($value);
          }
          $dataArr[]=$value;
        }
      }
    }

    $ruleAttributesArr=[];
    foreach ($dataArr as $dataItem){
      $ruleAttributesArr[]=$this->saveRuleAttributeToDB($attribute,$dataItem);
    }
    if (count($ruleAttributesArr)==1){
      return $ruleAttributesArr[0];
    }else{
      //rule atribut má větší množství hodnot => uděláme z něj dílčí cedent
      return $this->saveRuleAttributesCedentToDB($ruleAttributesArr);
    }
  }

  /**
   * @param RuleAttribute[] $ruleAttributesArr
   * @param $connective = 'disjunction'
   * @return Cedent|null
   * @throws \LeanMapper\Exception\InvalidArgumentException
   */
  private function saveRuleAttributesCedentToDB($ruleAttributesArr,$connective=Cedent::CONNECTIVE_DISJUNCTION){
    if(empty($ruleAttributesArr)){return null;}
    $cedent=new Cedent();
    $cedent->connective=$connective;
    $this->rulesFacade->saveCedent($cedent);
    foreach ($ruleAttributesArr as $ruleAttribute){
      $cedent->addToRuleAttributes($ruleAttribute);
    }
    $this->rulesFacade->saveCedent($cedent);
    return $cedent;
  }

  /**
   * @param Attribute $attribute
   * @param ValuesBin|Value $data
   * @throws \LeanMapper\Exception\InvalidArgumentException
   * @return RuleAttribute
   */
  private function saveRuleAttributeToDB(Attribute $attribute, $data){
    $ruleAttribute=new RuleAttribute();
    $ruleAttribute->attribute=$attribute;
    if ($data instanceof ValuesBin){
      $ruleAttribute->valuesBin=$data;
    }elseif($data instanceof Value){
      $ruleAttribute->value=$data;
    }
    $this->rulesFacade->saveRuleAttribute($ruleAttribute);
    return $ruleAttribute;
  }

  /**
   * Metoda pro naparsování a uložení dílčího cedentu
   * @param \SimpleXMLElement $cedentXml
   * @throws \LeanMapper\Exception\InvalidArgumentException
   * @return Cedent
   */
  private function saveCedent(\SimpleXMLElement $cedentXml){
    $cedent=new Cedent();
    $childCedents=[];
    $childRuleAttributes=[];

    #region vyřešení spojky
    $connective=Strings::lower(!empty($cedentXml[0]['connective'])?$cedentXml[0]['connective']:'Conjunction');
    if (in_array($connective,[Cedent::CONNECTIVE_CONJUNCTION,Cedent::CONNECTIVE_DISJUNCTION,Cedent::CONNECTIVE_NEGATION])){
      $cedent->connective=$connective;
    }else{
      $cedent->connective=Cedent::CONNECTIVE_CONJUNCTION;
    }
    #endregion vyřešení spojky

    if (!empty($cedentXml->Cedent)){
      foreach ($cedentXml->Cedent as $childCedentXml){
        $childCedents[]=$this->saveCedent($childCedentXml);
      }
    }

    if (!empty($cedentXml->RuleAttribute)){
      foreach ($cedentXml->RuleAttribute as $ruleAttributeXml){
        $childRuleAttribute=$this->saveRuleAttribute($ruleAttributeXml);
        //zjištění, jestli je výsledkem uložení rule attribut, nebo cedent
        if ($childRuleAttribute instanceof RuleAttribute){
          $childRuleAttributes[]=$childRuleAttribute;
        }elseif($childRuleAttribute instanceof Cedent){
          $childCedents[]=$childRuleAttribute;
        }
      }
    }
    //TODO vyřešení případné shody s již existujícími cedenty

    $this->rulesFacade->saveCedent($cedent);
    if (!empty($childCedents)){
      foreach ($childCedents as $childCedent){
        $cedent->addToCedents($childCedent);
      }
    }
    if (!empty($childRuleAttributes)){
      foreach ($childRuleAttributes as $childRuleAttribute){
        $cedent->addToRuleAttributes($childRuleAttribute);
      }
    }
    $this->rulesFacade->saveCedent($cedent);
    return $cedent;
  }

  /**
   * BreRuleUnserializer constructor.
   * @param RulesFacade $rulesFacade
   * @param MetasourcesFacade $metasourcesFacade
   * @param MetaAttributesFacade $metaAttributesFacade
   */
  public function __construct(RulesFacade $rulesFacade, MetasourcesFacade $metasourcesFacade, MetaAttributesFacade $metaAttributesFacade){
    $this->rulesFacade=$rulesFacade;
    $this->metasourcesFacade=$metasourcesFacade;
    $this->metaAttributesFacade=$metaAttributesFacade;
  }
}