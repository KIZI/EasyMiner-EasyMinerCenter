<?php

namespace EasyMinerCenter\EasyMinerModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Entities\KnowledgeBaseRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSet;
use EasyMinerCenter\Model\EasyMiner\Entities\RuleSetRuleRelation;
use EasyMinerCenter\Model\EasyMiner\Facades\KnowledgeBaseFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RulesFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\RuleSetsFacade;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use EasyMinerCenter\Model\EasyMiner\Repositories\RuleRuleRelationsRepository;
use EasyMinerCenter\Model\EasyMiner\Serializers\AssociationRulesXmlSerializer;
use EasyMinerCenter\Model\EasyMiner\Transformators\XmlTransformator;
use Nette\InvalidArgumentException;

/**
 * Class RuleSetsPresenter - presenter pro práci s rulesety
 * @package EasyMinerCenter\KnowledgeBaseModule\Presenters
 */
class RuleSetsPresenter extends BasePresenter{
  use ResponsesTrait;

  /** @var  RulesFacade $rulesFacade */
  private $rulesFacade;
  /** @var  RuleSetsFacade $ruleSetsFacade */
  private $ruleSetsFacade;
    /** @var  KnowledgeBaseFacade $knowledgeBaseFacade */
    private $knowledgeBaseFacade;
  /** @var  UsersFacade $usersFacade */
  private $usersFacade;
  /** @var  XmlTransformator $xmlTransformator */
  private $xmlTransformator;


  /**
   * Akce pro vykreslení detailů úlohy ve formátu PMML
   * @param int $id - id of the rule set
   * @throws \Exception
   * @throws \Nette\Application\ForbiddenRequestException
   */
  public function renderDRL($id){
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //vygenerování a zobrazení DRL
    $associationRulesXmlSerializer=new AssociationRulesXmlSerializer($ruleSet->findRules());
    $xml=$associationRulesXmlSerializer->getXml();
    $this->sendTextResponse($this->xmlTransformator->transformToDrl($xml,$this->template->basePath));
  }

  /**
   * Akce pro vypsání existujících rulesetů
   */
  public function actionList(){
    $ruleSets=$this->ruleSetsFacade->findRuleSetsByUser($this->user->id);
    $result=[];
    if (empty($ruleSets)) {
      //pokud není nalezen ani jeden RuleSet, jeden založíme...
      $ruleSet=new RuleSet();
      $user=$this->usersFacade->findUser($this->user->id);
      $ruleSet->user=$user;
      $ruleSet->name=$user->name;
      $this->ruleSetsFacade->saveRuleSet($ruleSet);
      $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
    }else{
      foreach ($ruleSets as $ruleSet){
        $result[$ruleSet->ruleSetId]=$ruleSet->getDataArr();
      }
    }
    $this->sendJsonResponse($result);
  }

  #region akce pro manipulaci s rulesetem

  /**
   * Akce pro vytvoření nového rulesetu (se zadaným jménem)
   * @param string $name
   * @param string $description=""
   * @throws InvalidArgumentException
   */
  public function actionNew($name, $description=""){
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id);
    //vytvoření rulesetu
    $ruleSet=new RuleSet();
    $ruleSet->name=$name;
    $ruleSet->description=$description;
    $ruleSet->user=$this->usersFacade->findUser($this->user->id);
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //odeslání výsledku
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Akce pro přejmenování existujícího rulesetu
   * @param int $id
   * @param string $name
   * @param string $description=""
   */
  public function actionRename($id,$name,$description=""){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    $this->ruleSetsFacade->checkUniqueRuleSetNameByUser($name,$this->user->id,$ruleSet);
    //změna a uložení
    $ruleSet->name=$name;
    $ruleSet->description=$description;
    $this->ruleSetsFacade->saveRuleSet($ruleSet);
    //odeslání výsledku
    $this->sendJsonResponse($ruleSet->getDataArr());
  }

  /**
   * Akce pro smazání konkrétního rulesetu
   * @param int $id
   */
  public function actionDelete($id){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //smazání
    if ($this->ruleSetsFacade->deleteRuleSet($ruleSet)){
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  #endregion akce pro manipulaci s rulesetem

  #region akce pro manipulaci s pravidly v rulesetech

  /**
   * Akce vracející jeden konkrétní ruleset se základním přehledem pravidel
   * @param int $id
   * @param int $offset
   * @param int $limit
   * @param string|null $order = null
   */
  public function actionGetRules($id,$offset=0,$limit=25,$order=null){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //připravení výstupu
    $result=[
      'ruleset'=>$ruleSet->getDataArr(),
      'rules'=>[]
    ];
    if ($ruleSet->rulesCount>0 || true){
      $rules=$this->ruleSetsFacade->findRulesByRuleSet($ruleSet,$order,$offset,$limit);
      if (!empty($rules)){
        foreach($rules as $rule){
          $result['rules'][$rule->ruleId]=$rule->getBasicDataArr();
        }
      }
    }
    $this->sendJsonResponse($result);
  }

  /**
   * Akce pro přidání pravidel do rulesetu
   * @param int $id
   * @param string|int $rules - ID pravidel oddělená čárkami či středníky
   * @param string $relation = 'positive'
   * @param string $result = "simple" (varianty "simple", "rules")
   */
  public function actionAddRules($id,$rules,$relation=RuleSetRuleRelation::RELATION_POSITIVE, $result="simple"){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->addRuleToRuleSet($rule,$ruleSet,$relation);
        }catch (\Exception $e){continue;}
      }
    }
    if ($result=="rules"){
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr,$ruleSet);
      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Akce pro odebrání pravidel z rulesetu
   * @param int $id
   * @param string|int $rules
   * @param string $result = "simple" (varianty "simple", "rules")
   */
  public function actionRemoveRules($id, $rules, $result="simple"){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    /** @var int[] $ruleIdsArr */
    $ruleIdsArr=explode(',',str_replace(';',',',$rules));
    if (!empty($ruleIdsArr)){
      foreach($ruleIdsArr as $ruleId){
        if (!$ruleId){continue;}
        try{
          $rule=$this->rulesFacade->findRule($ruleId);
          $this->ruleSetsFacade->removeRuleFromRuleSet($rule,$ruleSet);
        }catch (\Exception $e){continue;}
      }
    }
    $this->ruleSetsFacade->updateRuleSetRulesCount($ruleSet);

    if ($result=="rules"){
      $result=[
        'ruleset'=>$ruleSet->getDataArr(),
        'rules'=>[]
      ];
      $result['rules']=$this->prepareRulesResult($ruleIdsArr, $ruleSet);
      $this->sendJsonResponse($result);
    }else{
      $this->sendJsonResponse(['state'=>'ok']);
    }
  }

  /**
   * Akce pro odebrání všech pravidel z rulesetu
   * @param int $id
   */
  public function actionRemoveAllRules($id){
    //najití RuleSetu a kontroly
    $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
    $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
    //smazání
    $this->ruleSetsFacade->removeAllRulesFromRuleSet($ruleSet);
    $this->sendJsonResponse(['state'=>'ok']);
  }

  /**
   * Funkce připravující pole s informacemi o vybraných pravidlech pro vrácení v rámci JSON odpovědi
   * @param int[] $ruleIdsArr
   * @param RuleSet|int $ruleSet
   * @return array
   */
  private function prepareRulesResult($ruleIdsArr, $ruleSet=null){
    $result=[];
    if (!empty($ruleIdsArr)) {
      foreach ($ruleIdsArr as $ruleId) {
        if (!$ruleId) {
          continue;
        }
        try {
          $rule = $this->rulesFacade->findRule($ruleId);
          $result[$rule->ruleId]=$rule->getBasicDataArr();
          if (!empty($ruleSet)){
            $relation=$rule->getRuleSetRelation($ruleSet);
            if (!empty($relation)){
              $result[$rule->ruleId]['ruleSetRelation']=$relation->relation;
            }else{
              $result[$rule->ruleId]['ruleSetRelation']='';
            }
          }
        }catch (\Exception $e){continue;}
      }
    }
    return $result;
  }

  #endregion akce pro manipulaci s pravidly v rulesetech

    #region akce pro porovnávání pravidel

    /**
     * Akce vracející jeden konkrétní ruleset se jmény pravidel a vztahy z datasource daného mineru
     * @param Int $id RuleSet id
     * @param Int $miner Miner id
     */
    public function actionGetRulesNames($id, $miner){
        //najití RuleSetu a kontroly
        $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
        $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
        //připravení výstupu
        $result=[
            'ruleset'=>$ruleSet->getDataArr(),
            'rules'=>[]
        ];
        if ($ruleSet->rulesCount>0 || true){
            $rules=$this->knowledgeBaseFacade->findRulesByDatasource($ruleSet,$miner);
            if (!empty($rules)){
                //$result['rules'] = $rules;
                foreach($rules as $rule){
                    $result['rules'][$rule->ruleId]=[
                        'name' => $rule->text,
                        'relation' => $rule->relation
                    ];
                }
            }
        }
        $this->sendJsonResponse($result);
    }

    /**
     * Akce vracející nejpodobnější pravidlo z rulesetu s pravidlem obdrženým
     * @param Int $id RuleSet id
     * @param Int $rule Rule id
     */
    public function actionCompareRuleWithRuleset($id, $rule){
        //najití RuleSetu a kontroly
        $ruleSet=$this->ruleSetsFacade->findRuleSet($id);
        $this->ruleSetsFacade->checkRuleSetAccess($ruleSet,$this->user->id);
        //připravení výstupu
        $result=[];
        $compareResults=[];
        try{
            $ruleSimilarity = $this->knowledgeBaseFacade->findRuleSimilarity($id, $rule);
        }catch (\Exception $e){
            $ruleSimilarity = null;
        }
        if($ruleSimilarity && ($ruleSimilarity->resultDate->getTimestamp() >= $ruleSet->lastModified->getTimestamp())){ // result from past, which is newer than Rule set last modification
            $result['max'] = $ruleSimilarity->rate;
            $result['rule'] = [
                RuleRuleRelationsRepository::COLUMN_RULESET_RULE => $ruleSimilarity->knowledgeBaseRuleId,
                RuleRuleRelationsRepository::COLUMN_RELATION => $ruleSimilarity->relation,
                RuleRuleRelationsRepository::COLUMN_RATE=> $ruleSimilarity->rate
            ];
        } elseif ($ruleSet->rulesCount>0 && $rule){
            if(!$ruleSimilarity){
                $ruleSimilarity = $rule;
            }
            $ruleObj = $this->rulesFacade->findRule($rule);
            $ruleParts = [
                "ant" => $this->decomposeCedent($ruleObj->antecedent),
                "con" => $this->decomposeCedent($ruleObj->consequent)
            ];
            $result['max'] = 0;
            $result['rule'] = [
                RuleRuleRelationsRepository::COLUMN_RELATION => ''
            ];

            $ruleCompareResults = $this->knowledgeBaseFacade->getRulesComparingResults($rule, $id);
            if(count($ruleCompareResults) > 0){
                $result['rule'] = array_slice($ruleCompareResults,0,1)[0];
                $result['max'] = $result['rule'][RuleRuleRelationsRepository::COLUMN_RATE];
            }

            foreach($this->ruleSetsFacade->findRulesByRuleSet($ruleSet, null) as $ruleSetRule){ //TO-DO save in cache/session etc.
                if(isset($ruleCompareResults[$ruleSetRule->ruleId])){ // has been already compared and is impossible to have higher rate due to DB ordering
                    continue;
                }
                $compareResult = $this->compareRules($ruleParts, $ruleSetRule);
                if($compareResult[RuleRuleRelationsRepository::COLUMN_RATE] > $result['max']){
                    $result['max'] = $compareResult[RuleRuleRelationsRepository::COLUMN_RATE];
                    $result['rule'] = $compareResult;
                }
                $compareResult[RuleRuleRelationsRepository::COLUMN_RULE] = $rule;
                $compareResult[RuleRuleRelationsRepository::COLUMN_RULE_SET] = $id;
                $compareResults[] = $compareResult;
            }

            if($result['max'] > 0){
                try{
                    $this->knowledgeBaseFacade->addRuleToKBRuleRelation(
                        $id, $ruleSimilarity,
                        $result['rule'][RuleRuleRelationsRepository::COLUMN_RULESET_RULE],
                        $result['rule'][RuleRuleRelationsRepository::COLUMN_RELATION],
                        $result['max']
                    );
                }catch (\Exception $e){}
            }

            $this->knowledgeBaseFacade->saveComparingResults($compareResults);
        }
        $this->sendJsonResponse($result);
    }

    /**
     * Porovnání dvou pravidel
     * @param Array $ruleParts části pravidla k porovnání
     * @param Array $ruleSetRule pravidlo Rulesetu
     * @return Array míra podobnosti, id a vztah, ve kterém je pravidlo uloženo v KB
     */
    private function compareRules($ruleParts, $ruleSetRule){
        $antecedentAttributesCount = $consequentAttributesCount = 0;
        $antecedentAttributesSame = $consequentAttributesSame = 0;
        $crossAttributesConflict = false;
        $ruleSetRuleDecomposed = json_decode($ruleSetRule->decomposed, true);
        if(!$ruleSetRuleDecomposed){ // if hasn't rule decomposed yet
            $ruleSetRuleDecomposed = [
                "ant" => $this->decomposeCedent($ruleSetRule->antecedent),
                "con" => $this->decomposeCedent($ruleSetRule->consequent)
            ];
            try{
                $this->knowledgeBaseFacade->setDecomposedRuleSetRule($ruleSetRule->ruleId, json_encode($ruleSetRuleDecomposed));
            }catch (\Exception $e){}
        }
        foreach($ruleSetRuleDecomposed['ant'] as $attribute => $value){
            if(isset($ruleParts['con'][$attribute]) || array_key_exists($attribute, $ruleParts['con'])){
                $crossAttributesConflict = true; // attribute on opposite site of rule
                break;
            }
            if(isset($ruleParts['ant'][$attribute]) || array_key_exists($attribute, $ruleParts['ant'])){
                $antecedentAttributesSame++; // same attribute
                if($ruleParts['ant'][$attribute] == $value){
                    $antecedentAttributesSame++; // same value or values bin
                }
            }
            $antecedentAttributesCount++;
        }
        foreach($ruleSetRuleDecomposed['con'] as $attribute => $value){
            if(isset($ruleParts['ant'][$attribute]) || array_key_exists($attribute, $ruleParts['ant'])){
                $crossAttributesConflict = true; // attribute on opposite site of rule
                break;
            }
            if(isset($ruleParts['con'][$attribute]) || array_key_exists($attribute, $ruleParts['con'])){
                $consequentAttributesSame++; // same attribute
                if($ruleParts['con'][$attribute] == $value){
                    $consequentAttributesSame++; // same value or values bin
                }
            }
            $consequentAttributesCount++;
        }
        $sameRateAntecedent = $antecedentAttributesSame/($antecedentAttributesCount+count($ruleParts['ant']));
        $sameRateConsequent = $consequentAttributesSame/($consequentAttributesCount+count($ruleParts['con']));
        $sameRateFinal = $crossAttributesConflict ? 0 : ($sameRateAntecedent+$sameRateConsequent)/2;

        return [
            RuleRuleRelationsRepository::COLUMN_RULESET_RULE => $ruleSetRule->ruleId,
            RuleRuleRelationsRepository::COLUMN_RELATION => $ruleSetRule->relation,
            RuleRuleRelationsRepository::COLUMN_RATE => $sameRateFinal
        ];
    }

    /**
     * @param Cedent $cedent cedent na dekomponování
     * @return Array atribut => hodnota
     */
    private function decomposeCedent($cedent){
        $ruleAttributes = [];
        foreach($cedent->cedents as $childCedent){
            $ruleAttributes = array_merge($ruleAttributes, $this->decomposeCedent($childCedent));
        }
        foreach($cedent->ruleAttributes as $ruleAttribute){
            if($ruleAttribute->value){
                $ruleAttributes[$ruleAttribute->attribute->preprocessing->preprocessingId] = $ruleAttribute->value->valueId;
            } elseif($ruleAttribute->valuesBin){
                $ruleAttributes[$ruleAttribute->attribute->preprocessing->preprocessingId] = $ruleAttribute->valuesBin->valuesBinId;
            } else{
                $ruleAttributes[$ruleAttribute->attribute->preprocessing->preprocessingId] = "";
            }
        }
        return $ruleAttributes;
    }

    #endregion akce pro porovnávání pravidel

  #region injections
  /**
   * @param RulesFacade $rulesFacade
   */
  public function injectRulesFacade(RulesFacade $rulesFacade){
    $this->rulesFacade=$rulesFacade;
  }
  /**
   * @param RuleSetsFacade $ruleSetsFacade
   */
  public function injectRuleSetsFacade(RuleSetsFacade $ruleSetsFacade){
    $this->ruleSetsFacade=$ruleSetsFacade;
  }
    /**
     * @param KnowledgeBaseFacade $knowledgeBaseFacade
     */
    public function injectKnowledgeBaseFacade(KnowledgeBaseFacade $knowledgeBaseFacade){
        $this->knowledgeBaseFacade=$knowledgeBaseFacade;
    }
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  /**
   * @param XmlTransformator $xmlTransformator
   */
  public function injectXmlTransformator(XmlTransformator $xmlTransformator){
    $this->xmlTransformator=$xmlTransformator;
    //nastaven basePath
    $this->xmlTransformator->setBasePath($this->template->basePath);
  }
  #endregion injections
}