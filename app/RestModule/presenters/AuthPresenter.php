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
   *   produces={"application/json","application/xml"},
   *   security={{"apiKey":{}}},
   *   @SWG\Response(
   *     response=200,
   *     description="Successfully authenticated",
   *     @SWG\Schema(
   *       required={"id","name"},
   *       @SWG\Property(property="id",type="integer",description="Authenticated user ID"),
   *       @SWG\Property(property="name",type="string",description="Authenticated user name"),
   *       @SWG\Property(property="email",type="string",description="Authenticated user e-mail"),
   *       @SWG\Property(
   *         property="role",
   *         type="array",
   *         description="Authenticated user roles",
   *         @SWG\Items(type="string")
   *       ),
   *     )
   *   ),
   *   @SWG\Response(
   *     response=400,
   *     description="Invalid API key supplied",
   *     @SWG\Schema(ref="#/definitions/StatusResponse")
   *   )
   * )
   */
  public function actionRead() {
    $identity=$this->identity;
    if (!empty($identity->data)){
      $identityData=$identity->data;
    }else{
      $identityData=[];
    }
    $this->setXmlMapperElements('user');
    $this->resource=['id'=>$identity->getId(),'name'=>@$identityData['name'],'email'=>@$identityData['email'],'role'=>$identity->getRoles()];
    $this->sendResource();
  }
}