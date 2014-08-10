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

    $metaAttribute=new Model\Rdf\Entities\MetaAttribute();
    $metaAttribute->name='Age';
    $metaAttribute->formats=array();
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
    $attribute->format=$format;
    $attribute->valuesBins=array($valuesBin);
    $attribute->name='vek';

    $this->knowledgeRepository->saveMetaattribute($metaAttribute);
    $this->knowledgeRepository->saveAttribute($attribute);

    echo 'DONE';
    $this->terminate();
  }


}
