<?php
namespace App\RestModule\Presenters;
use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\IResource;
use Drahak\Restful\Resource;
use Drahak\Restful\ResourceFactory;
use Drahak\Restful\Validation\IValidator;
use Nette\Application\UI\Presenter;

class AuthPresenter extends Presenter {

  /**
   * @param int $id
   */
  public function actionRead($id=null){
    echo 'OK';
    $this->terminate();
    //$this->resource=['state'=>'ok'];
    //$this->sendResource();
  }


}