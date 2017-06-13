<?php
namespace EasyMinerCenter\RestModule\Presenters;

use Drahak\Restful\Resource\Link;
use EasyMinerCenter\Model\EasyMiner\Entities\User;
use EasyMinerCenter\Model\EasyMiner\Facades\UsersFacade;
use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\Http\IInput;
use Drahak\Restful\Validation\IDataProvider;
use EasyMinerCenter\RestModule\Model\Mappers\XmlMapper;
use Nette\Application\Responses\TextResponse;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Utils\Strings;

/**
 * Class BaseResourcePresenter
 * @package EasyMinerCenter\RestModule\Presenters
 * @author Stanislav Vojíř
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 *
 * @property IInput|IDataProvider $input
 *
 * @SWG\Swagger(
 *   basePath="%BASE_PATH%",
 *   host="%HOST%",
 *   schemes={"https","http"},
 *   @SWG\Info(
 *     title="EasyMinerCenter REST API",
 *     version="%VERSION%",
 *     description="API for access to EasyMinerCenter functionalities - authentication of users, management of data sources. All resources are secured with the API key. Please append ?apiKey={yourKey} to all your requests. Alternatively, you can send the header 'Authorization: ApiKey {yourKey}'",
 *     @SWG\Contact(name="Stanislav Vojíř",email="stanislav.vojir@vse.cz"),
 *     @SWG\License(name="Apache License, Version 2.0",url="http://www.apache.org/licenses/LICENSE-2.0")
 *   )
 * )
 *
 * @SWG\SecurityScheme(
 *   securityDefinition="apiKey",
 *   type="apiKey",
 *   in="query",
 *   name="apiKey"
 * )
 * @SWG\SecurityScheme(
 *   securityDefinition="apiKeyHeader",
 *   type="apiKey",
 *   in="header",
 *   name="ApiKey"
 * )
 *
 *
 * @SWG\Tag(
 *   name="Auth",
 *   description="Authentication of the user using API KEY"
 * )
 * @SWG\Tag(
 *   name="Databases",
 *   description="Access to user databases"
 * )
 * @SWG\Tag(
 *   name="Attributes",
 *   description="Management of attributes"
 * )
 * @SWG\Tag(
 *   name="Evaluation",
 *   description="Methods for model evaluation/scoring"
 * )
 * @SWG\Tag(
 *   name="Datasources",
 *   description="Management of datasources"
 * )
 * @SWG\Tag(
 *   name="Miners",
 *   description="Management of rule miners"
 * )
 * @SWG\Tag(
 *   name="Outliers",
 *   description="Management of outlier detection tasks"
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
 * @SWG\Tag(
 *   name="Tasks",
 *   description="Management of rule mining tasks"
 * )
 */
abstract class BaseResourcePresenter extends ResourcePresenter {
  /** @var  UsersFacade $usersFacade */
  protected $usersFacade;
  /** @var IIdentity $identity = null */
  protected $identity=null;
  /** @var User $currentUser = null */
  protected $currentUser=null;
  /** @var XmlMapper $xmlMapper */
  protected $xmlMapper=null;

  /**
   * @param bool $allowAnonymous=false - if it us true, the API KEY check will be ignored
   * @throws AuthenticationException
   * @throws \Drahak\Restful\Application\BadRequestException
   * @throws \Exception
   */
  public function startup($allowAnonymous=false) {
    //check of user credentials via API KEY
    $apiKey=@$this->getInput()->getData()['apiKey'];
    if (empty($apiKey)){
      $authorizationHeader=$this->getHttpRequest()->getHeader('Authorization');
      $apiKey=(substr($authorizationHeader,0,7)=="ApiKey "?substr($authorizationHeader,7):null);
    }
    try{
      if (empty($apiKey)) {
        throw new AuthenticationException("You have to use API KEY!",IAuthenticator::FAILURE);
      }else{
        $this->identity=$this->usersFacade->authenticateUserByApiKey($apiKey,$this->currentUser);
      }
    }catch(\Exception $e){
      if (!$allowAnonymous){
        throw $e;
      }
    }
    //run default startup() method
    parent::startup();
  }

  /**
   * Method returning instance of actually logged-in User (using session login or API KEY)
   * @return User
   */
  public function getCurrentUser(){
    return $this->currentUser;
  }

  /**
   * Generic method for sending a XML response
   * @param \SimpleXMLElement|string $simpleXml
   */
  protected function sendXmlResponse($simpleXml){
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('application/xml','UTF-8');
    $this->sendResponse(new TextResponse(($simpleXml instanceof \SimpleXMLElement?$simpleXml->asXML():$simpleXml)));
  }

  /**
   * Generic method for sending PLAINTEXT response
   * @param string $text
   */
  protected function sendTextResponse($text) {
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('text/plain','UTF-8');
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Generic method for sending HTML response
   * @param string $text
   */
  protected function sendHtmlResponse($text) {
    $httpResponse=$this->getHttpResponse();
    $httpResponse->setContentType('text/html','UTF-8');
    $this->sendResponse(new TextResponse($text));
  }

  /**
   * Method for configuration of XML element names (for automatic XML response)
   * @param string $rootElement
   * @param string $itemElement
   */
  protected function setXmlMapperElements($rootElement, $itemElement="") {
    $this->xmlMapper->setRootElement($rootElement);
    if (!empty($itemElement)){
      $this->xmlMapper->setItemElement($itemElement);
    }
  }

  /**
   * Method returning absolute URL of the given link
   * @param string $destination
   * @param array $args
   * @param string $rel
   * @param bool $includeCurrentParams=false - it if is true, the link should merge actually params with the new params given in $args
   * @return string
   */
  protected function getAbsoluteLink($destination, $args=[], $rel=Link::SELF, $includeCurrentParams=false) {
    if (($destination=='self'||$destination=='//self')&&empty($args)){
      $args=$this->params;
    }elseif($includeCurrentParams){
      $args=array_merge($this->params,$args);
    }
    $link=$this->link($destination, $args, $rel)->getHref();
    if (Strings::startsWith($link,'/')=='/'){
      $link=rtrim($this->getHttpRequest()->getUrl()->getHostUrl(),'/').$link;
    }
    return $link;
  }


  #region injections
  /**
   * @param UsersFacade $usersFacade
   */
  public function injectUsersFacade(UsersFacade $usersFacade){
    $this->usersFacade=$usersFacade;
  }

  /**
   * @param XmlMapper $xmlMapper
   */
  public function injectXmlMapper(XmlMapper $xmlMapper) {
    $this->xmlMapper=$xmlMapper;
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