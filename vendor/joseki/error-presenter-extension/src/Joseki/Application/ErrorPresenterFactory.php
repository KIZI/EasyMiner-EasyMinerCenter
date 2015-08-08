<?php


namespace Joseki\Application;

use Nette\Application\InvalidPresenterException;
use Nette\Application\IPresenterFactory;
use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Http\IRequest;

class ErrorPresenterFactory
{

    /** @var IRouter */
    private $router;

    /** @var IRequest */
    private $httpRequest;

    /** @var IPresenterFactory */
    private $presenterFactory;

    private $defaultErrorPresenter = null;



    function __construct(IPresenterFactory $presenterFactory, IRouter $router, IRequest $httpRequest)
    {
        $this->router = $router;
        $this->httpRequest = $httpRequest;
        $this->presenterFactory = $presenterFactory;
    }



    /**
     * @return null|string
     */
    public function getErrorPresenter()
    {
        $errorPresenter = $this->defaultErrorPresenter;

        $request = $this->router->match($this->httpRequest);
        if (!$request instanceof Request) {
            return $errorPresenter;
        }

        $name = $request->getPresenterName();
        $modules = explode(":", $name);
        unset($modules[count($modules) - 1]);
        while (count($modules) != 0) {
            $catched = false;
            try {
                $errorPresenter = implode(":", $modules) . ':Error';
                $this->presenterFactory->getPresenterClass($errorPresenter);
                break;
            } catch (InvalidPresenterException $e) {
                $catched = true;
            }
            unset($modules[count($modules) - 1]);
        }
        if (isset($catched) && $catched) {
            return $this->defaultErrorPresenter;
        }

        return $errorPresenter;
    }



    /**
     * @param string $defaultErrorPresenter
     */
    public function setDefaultErrorPresenter($defaultErrorPresenter)
    {
        $this->defaultErrorPresenter = $defaultErrorPresenter;
    }
}
