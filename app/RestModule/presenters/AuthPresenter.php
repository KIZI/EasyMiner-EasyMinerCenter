<?php
namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\UI\Presenter;

/**
 * Class AuthPresenter
 * @package EasyMinerCenter\RestModule\Presenters
 *
 * @SWG\Resource(
 *   apiVersion="1.0.0",
 *   description="Authentication of the user using API KEY",
 *   basePath="BASE_PATH",
 *   resourcePath="/auth",
 *   produces="['application/json','application/xml']"
 * )
 */
class AuthPresenter extends Presenter {

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
   *     @SWG\ResponseMessage(code=404, message="User not found")
   *   )
   * )
   */
  public function actionDefault($key){

    $this->terminate();
    //$this->resource=['state'=>'ok'];
    //$this->sendResource();
  }

}