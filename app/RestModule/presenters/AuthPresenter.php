<?php
namespace EasyMinerCenter\RestModule\Presenters;

/**
 * Class AuthPresenter
 *
 * @package EasyMinerCenter\RestModule\Presenters
 */
class AuthPresenter extends BaseResourcePresenter {

  /**
   * Akce pro ověření přihlášeného uživatele
   * @SWG\Get(
   *   tags={"Auth"},
   *   path="/auth",
   *   summary="Authenticate current user",
   *   produces={"application/json"},
   *   security={{"apiKey":{}}},
   *   @SWG\Response(
   *     response=200,
   *     description="Successfully authenticated",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid ID supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   )
   * )
   */
  public function actionRead() {
    $this->resource=['code'=>200,'status'=>'OK'];
    $this->sendResource();
  }

}