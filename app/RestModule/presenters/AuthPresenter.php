<?php
namespace App\RestModule\Presenters;

use App\Libs\StringsHelper;
use App\Model\EasyMiner\Entities\User;
use App\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\UI\Presenter;

/**
 * Class AuthPresenter
 * @package App\RestModule\Presenters
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   description="Authentication of the user using API KEY",
 *   basePath="/api",
 *   resourcePath="/auth",
 *   produces="['application/json','application/xml']"
 * )
 */
class AuthPresenter extends Presenter {

  /** @var UsersFacade $usersFacade */
  private $usersFacade;

  /**
   * @param string $key
   * @SWG\Api(
   *   path="/auth",
   *   @SWG\Operation(
   *     method="GET",
   *     summary="Authenticate current user",
   *     authorizations={},
   *     @SWG\Parameter(
   *       name="key",
   *       description="API KEY, which should be validated",
   *       required=true,
   *       type="string",
   *       format="string",
   *       paramType="query",
   *       allowMultiple=false
   *     ),
   *     @SWG\ResponseMessage(code=400, message="Invalid ID supplied"),
   *     @SWG\ResponseMessage(code=404, message="Pet not found")
   *   )
   * )
   */
  public function actionDefault($key){

    $this->terminate();
    //$this->resource=['state'=>'ok'];
    //$this->sendResource();
  }

  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }

}