<?php

namespace IZI\Selenium;

class QUnitTestCase extends TestCase
{
    protected function setUp()
    {
        $this->setBrowserUrl(WEB_PATH.'../tests/unit/web/js/index.html');
    }
}