<?php

namespace IZI\Selenium;

class UITestCase extends TestCase
{
    protected function setUp()
    {
        $this->setBrowserUrl(WEB_PATH);
    }
}