<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Homepage presenter.
 */
class HomepagePresenter extends BasePresenter
{

	public function renderDefault()
	{

    $this->context->knowledgeRepository->findCedent(array('item'));
    exit('------');
    exit(var_dump($format->valuesBins));
    $format->metaAttribute='http://cosi';
    $metaattribute=$format->metaAttribute;

    $knowledgeRepository=$this->context->knowledgeRepository;

    exit(var_dump($knowledgeRepository->findMetaattribute('kb:MetaAttribute/testovaci-metaatribut7')));
/*
    exit(var_dump($metaAttribute->reflection->getAnnotations("property")));*/
	//	$this->template->anyVariable = 'any value';
	}



}
