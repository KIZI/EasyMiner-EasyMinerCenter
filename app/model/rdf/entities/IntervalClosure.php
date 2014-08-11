<?php
/**
 * Created by PhpStorm.
 * User: Stanislav
 * Date: 5.7.14
 * Time: 15:54
 */

namespace App\Model\Rdf\Entities;

use Nette\Utils\Strings;

/**
 * Class IntervalClosure
 *
 * @package App\Model\Rdf\Entities
 *
 * @property string $uri
 * @property string $closure
 *
 * @rdfClass(class="kb:IntervalClosure")
 * @rdfNamespaces(kb="http://easyminer.eu/kb/")
 * @rdfLiteral (property=$closure,relation='kb:hasValue')
 */
class IntervalClosure extends BaseEntity{

  public function getClosure(){
    return $this->closure;
  }
  public function setClosure($closure){
    if (in_array($closure,array('closedOpen','openClosed','closedClosed','openOpen'))){
      $this->closure=$closure;
    }else{
      throw new \Exception('Bad interval closure value');
    }
  }
  public function getLeftClosure(){
    $closure=$this->closure;
    if (Strings::startsWith($closure,'open')){
      return 'open';
    }else{
      return 'closed';
    }
  }
  public function getRightClosure(){
    $closure=$this->closure;
    if (Strings::endsWith($closure,'Open')){
      return 'open';
    }else{
      return 'closed';
    }
  }
  public function setLeftClosure($closure){
    $closure=Strings::lower($closure);
    $this->closure=$closure.$this->getRightClosure();
  }
  public function setRightClosure($closure){
    $this->closure=$this->getLeftClosure().Strings::firstUpper($closure);
  }
} 