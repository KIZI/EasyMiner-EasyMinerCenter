<?php
namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Libs\StringsHelper;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Nette\Application\UI\Presenter;

/**FIXME swagger 2.0
 * Class AuthPresenter
 *
 * @package EasyMinerCenter\RestModule\Presenters
 *
 * @REMOVE-SWG\Resource(
 *   apiVersion="1.0.0",
 *   description="Authentication of the user using API KEY",
 *   basePath="BASE_PATH",
 *   resourcePath="/auth",
 *   produces="['application/json','application/xml']"
 * )
 */
class AuthPresenter extends Presenter {

  /**
   * Akce pro ověření přihlášeného uživatele
   * @param string $key
   * @SWG\Get(
   *   tags={"Auth"},
   *   path="/auth",
   *   summary="Authenticate current user",
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}}},
   *   @SWG\Response(
   *     response=200,
   *     description="Successfully authenticated",
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid ID supplied",
   *   ),
   *   @SWG\Response(
   *     response=404,
   *     description="User not found",
   *   )
   * )
   */
  public function actionDefault($key) {
    //FIXME implement!
    $this->terminate();
    //$this->resource=['state'=>'ok'];
    //$this->sendResource();
  }

}