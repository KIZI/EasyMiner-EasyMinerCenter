<?php
namespace App\RestModule\Presenters;

use Drahak\Restful\Application\UI\ResourcePresenter;
use Drahak\Restful\Application\UI\SecuredResourcePresenter;
use Drahak\Restful\Http\IInput;
use Drahak\Restful\Validation\IDataProvider;

/**
 * Class BaseResourcePresenter
 * @package App\RestModule\Presenters
 * @property IInput|IDataProvider $input
 *
 * @SWG\Info(
 *   title="EasyMinerCenter REST API",
 *   description="Api for access to EasyMinerCenter functionalities - authentication of users, management of data sources",
 *   contact="stanislav.vojir@vse.cz",
 *   license="BSD3",
 * )
 *
 * @SWG\Authorization(
 *   type="apiKey",
 *   passAs="query",
 *   keyname="key"
 * )
 */
abstract class BaseResourcePresenter extends ResourcePresenter {
  //TODO implement

}