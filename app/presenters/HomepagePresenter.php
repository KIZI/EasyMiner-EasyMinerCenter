<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BaseRestPresenter
{

	public function renderDefault()
	{
    $metaattribute = new Model\Rdf\Entities\MetaAttribute();
    $metaattribute->name='cosi';
    $format=new Model\Rdf\Entities\Format();
    $format->name='a';
    $metaattribute->formats=array($format);
    $this->knowledgeRepository->saveMetaattribute($metaattribute);


/*
    exit(var_dump($metaAttribute->reflection->getAnnotations("property")));*/
	//	$this->template->anyVariable = 'any value';
	}

  public function actionReset(){

    $this->knowledgeRepository->reset();

    $knowledgeBase=new Model\Rdf\Entities\KnowledgeBase();
    $knowledgeBase->name='Testovaci KnowledgeBase';
    $this->knowledgeRepository->saveKnowledgeBase($knowledgeBase);


    for ($i=0;$i<10;$i++){
    $metaAttribute=new Model\Rdf\Entities\MetaAttribute();
    $metaAttribute->name='Age';
    $metaAttribute->formats=array();
    $metaAttribute->knowledgeBase=$knowledgeBase;
    $format=new Model\Rdf\Entities\Format();
    $format->name='Years';
    $rangeInterval=new Model\Rdf\Entities\Interval();
    $leftMarginValue=new Model\Rdf\Entities\Value();
    $leftMarginValue->value=0;
    $rangeInterval->leftMargin=$leftMarginValue;
    $intervalClosure=new Model\Rdf\Entities\IntervalClosure();
    $intervalClosure->setClosure('closedClosed');
    $rangeInterval->closure=$intervalClosure;
    $rightMarginValue=new Model\Rdf\Entities\Value();
    $rightMarginValue->value=80;
    $rangeInterval->rightMargin=$rightMarginValue;
    $format->intervals=array($rangeInterval);
    $format->dataType='int';
    $metaAttribute->formats[]=$format;

    $binInterval=new Model\Rdf\Entities\Interval();
    $leftMarginValueX=new Model\Rdf\Entities\Value();
    $leftMarginValueX->value=1;
    $binInterval->leftMargin=$leftMarginValueX;
    $intervalClosure=new Model\Rdf\Entities\IntervalClosure();
    $intervalClosure->setClosure('closedOpen');
    $binInterval->closure=$intervalClosure;
    $rightMarginValueX=new Model\Rdf\Entities\Value();
    $rightMarginValueX->value=10;
    $binInterval->rightMargin=$rightMarginValueX;

    $valuesBin=new Model\Rdf\Entities\ValuesBin();
    $valuesBin->name='children';
    $valuesBin->intervals=array($binInterval);

    $format->valuesBins=array($valuesBin);

    $attribute=new Model\Rdf\Entities\Attribute();
    $attribute->knowledgeBase=$knowledgeBase;
    $attribute->format=$format;
    $attribute->valuesBins=array($valuesBin);
    $attribute->name='vek';

    $this->knowledgeRepository->saveMetaattribute($metaAttribute);
    $this->knowledgeRepository->saveAttribute($attribute);

    $metaAttribute2=new Model\Rdf\Entities\MetaAttribute();
    $metaAttribute2->name='Rating';
    $metaAttribute2->formats=array();
    $metaAttribute2->knowledgeBase=$knowledgeBase;
    $format2=new Model\Rdf\Entities\Format();
    $format2->name='Letters';
    $format2->dataType='string';
    $value=new Model\Rdf\Entities\Value();
    $value->value='A';
    $value2=new Model\Rdf\Entities\Value();
    $value2->value='B';
    $format2->values=array($value,$value2);
    $valuesBinX=new Model\Rdf\Entities\ValuesBin();
    $valuesBinX->name='XA';
    $valuesBinX->values=array($value);
    $format2->valuesBins=array($valuesBinX);
    $metaAttribute2->formats[]=$format2;

    $this->knowledgeRepository->saveMetaattribute($metaAttribute2);
    $attribute2=new Model\Rdf\Entities\Attribute();
    $attribute2->format=$format2;
    $attribute2->valuesBins=array($valuesBinX);
    $attribute2->name='hodnoceni';
    $attribute2->knowledgeBase=$knowledgeBase;

    $this->knowledgeRepository->saveMetaattribute($metaAttribute2);
    $this->knowledgeRepository->saveAttribute($attribute2);

    $rule=new Model\Rdf\Entities\Rule();
    $rule->text='testovaci pravidlo';
    $antecedent=new Model\Rdf\Entities\Cedent();
    $antecedent->connective='conjunction';
    $ruleAttribute1=new Model\Rdf\Entities\RuleAttribute();
    $ruleAttribute1->attribute=$attribute;
    $ruleAttribute1->valuesBins=array($valuesBin);
    $antecedent->ruleAttributes=array($ruleAttribute1);
    $rule->antecedent=$antecedent;

    $consequent=new Model\Rdf\Entities\Cedent();
    $consequent->connective='conjunction';
    $ruleAttribute2=new Model\Rdf\Entities\RuleAttribute();
    $ruleAttribute2->attribute=$attribute2;
    $ruleAttribute2->valuesBins=array($valuesBinX);
    $consequent->ruleAttributes=array($ruleAttribute2);
    $rule->consequent=$consequent;


    $ruleSet=new Model\Rdf\Entities\RuleSet();
    $ruleSet->name='Testovaci RuleSet';
    $ruleSet->knowledgeBase=$knowledgeBase;
    $this->knowledgeRepository->saveRuleSet($ruleSet);

    $rule->ruleSets=array($ruleSet);
    $ruleSet->rules=array($rule);
    $rule->knowledgeBase=$knowledgeBase;

    $this->knowledgeRepository->saveRule($rule);
    $this->knowledgeRepository->saveRuleSet($ruleSet);
    }


    echo 'DONE';
    $this->terminate();
  }



}
