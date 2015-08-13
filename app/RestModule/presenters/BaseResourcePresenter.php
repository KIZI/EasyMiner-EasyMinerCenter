<?php
namespace EasyMinerCenter\RestModule\Presenters;

use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\Http\IInput;
use Drahak\Restful\Validation\IDataProvider;
use Nette\Application\Responses\TextResponse;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;

/**
 * Class BaseResourcePresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @property IInput|IDataProvider $input
 *
 * @SWG\Swagger(
 *   basePath="%BASE_PATH%",
 *   host="%HOST%",
 *   schemes={"http"},
 *   @SWG\Info(
 *     title="EasyMinerCenter REST API",
 *     version="%VERSION%",
 *     description="API for access to EasyMinerCenter functionalities - authentication of users, management of data sources",
 *     @SWG\Contact(name="Stanislav Vojíř",email="stanislav.vojir@vse.cz"),
 *     @SWG\License(name="BSD3")
 *   )
 * )
 *
 * @SWG\SecurityScheme(
 *   securityDefinition="apiKey",
 *   type="apiKey",
 *   in="query",
 *   name="key"
 * )
 *
 *
 * @SWG\Tag(
 *   name="Auth",
 *   description="Authentication of the user using API KEY"
 * )
 * @SWG\Tag(
 *   name="Users",
 *   description="Management of user accounts"
 * )
 * @SWG\Tag(
 *   name="Rules",
 *   description="Management of rules"
 * )
 * @SWG\Tag(
 *   name="RuleSets",
 *   description="Management of rule sets"
 * )
 *
 */
abstract class BaseResourcePresenter extends ResourcePresenter {
  /** @var  UsersFacade $usersFacade */
  protected $usersFacade;
  /** @var IIdentity $identity = null */
  protected $identity=null;
  /** @var User $currentUser = null */
  protected $currentUser=null;

  /**
   * @throws AuthenticationException
   * @throws \Drahak\Restful\Application\BadRequestException
   * @throws \Exception
   */
  public function startup() {
    parent::startup();
    $key=@$this->getInput()->getData()['key'];
    if (empty($key)) {
      throw new AuthenticationException("You have to use API KEY!",IAuthenticator::FAILURE);
    }else{
      $this->identity=$this->usersFacade->authenticateUserByApiKey($key);
    }
  }

    /**
   * Funkce vracející instanci aktuálně přihlášeného uživatele (buď dle přihlášení, nebo podle API KEY)
   * @return User
   */
  public function getCurrentUser(){
    return $this->currentUser;
  }

  /**
   * Funkce pro odeslání XML odpovědi
   * @param \SimpleXMLElement|string $simpleXml
   */
  protected function sendXmlResponse($simpleXml){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/xml','UTF-8');
    $this->sendResponse(new TextResponse(($simpleXml instanceof \SimpleXMLElement?$simpleXml->asXML():$simpleXml)));
  }

  #region injections
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }
  #endregion
}

/**
 * @SWG\Definition(
 *   definition="StatusResponse",
 *   title="Status",
 *   required={"code","status"},
 *   @SWG\Property(property="code", type="integer", description="Status code"),
 *   @SWG\Property(property="status", type="string", description="Status string", enum={"OK","error"}),
 *   @SWG\Property(property="message", type="string", description="User-friendly message")
 * )
 * @SWG\Definition(
 *   definition="InputErrorResponse",
 *   title="InputError",
 *   required={"code","status"},
 *   @SWG\Property(property="code", type="integer", description="Status code"),
 *   @SWG\Property(property="status", type="string", description="Status string", enum={"OK","error"}),
 *   @SWG\Property(property="message", type="string", description="User-friendly message"),
 *   @SWG\Property(
 *     property="errors",
 *     type="array",
 *     description="List of errors",
 *     @SWG\Items(
 *       ref="#/definitions/InputErrorItem"
 *     )
 *   )
 * )
 * @SWG\Definition(
 *   definition="InputErrorItem",
 *   title="InputErrorItem",
 *   @SWG\Property(property="field", type="string"),
 *   @SWG\Property(property="message", type="string"),
 *   @SWG\Property(property="code", type="integer")
 * )
 *
 */