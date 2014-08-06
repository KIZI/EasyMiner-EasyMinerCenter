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

    $knowledgeRepository=$this->context->knowledgeRepository;

    exit(var_dump($knowledgeRepository->findMetaattribute('kb:MetaAttribute/testovaci-metaatribut7')));
/*
    exit(var_dump($metaAttribute->reflection->getAnnotations("property")));*/
	//	$this->template->anyVariable = 'any value';
	}



}
