<?php

namespace Joseki\Application\DI;

use Nette;

class ErrorPresenterExtension extends Nette\DI\CompilerExtension
{



    public function loadConfiguration()
    {
        $container = $this->getContainerBuilder();

        $container->addDefinition($this->prefix('factory'))
            ->setClass('Joseki\Application\ErrorPresenterFactory');
    }



    public function afterCompile(Nette\PhpGenerator\ClassType $class)
    {
        $initialize = $class->methods['initialize'];

        $initialize->addBody(
            '$this->getService(?)->setDefaultErrorPresenter($this->getService(?)->errorPresenter);',
            array($this->prefix('factory'), 'application')
        );

        $initialize->addBody(
            '$this->getService(?)->errorPresenter = $this->getService(?)->getErrorPresenter();',
            array('application', $this->prefix('factory'))
        );
    }

}
