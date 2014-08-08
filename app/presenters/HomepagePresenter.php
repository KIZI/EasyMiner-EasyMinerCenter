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



}
