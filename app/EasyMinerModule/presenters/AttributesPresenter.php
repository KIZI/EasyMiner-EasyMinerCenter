<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 31. 10. 2014
 * Time: 11:12
 */

namespace EasyMinerModule\presenters;


use App\EasyMinerModule\Presenters\BasePresenter;
use Nette\Application\BadRequestException;

class AttributesPresenter extends BasePresenter{

  /**
   * @var string $layout
   * @persistent
   */
  public $layout='default';
  /**
   * Akce pro možnost přidání
   * @param $miner
   * @param $column
   */
  public function addAttribute($miner,$column){
    try{
      $miner=$this->minersFacade->findMiner($miner);
    }catch (\Exception $e){
      throw new BadRequestException('Requested miner not specified!', 404, $e);
    }
    $this->checkMinerAccess($miner);
  }


  protected function beforeRender(){
    parent::beforeRender();
    if ($this->layout=='component' || $this->layout=='iframe'){
      $this->template->layout='iframe';
    }
  }

} 