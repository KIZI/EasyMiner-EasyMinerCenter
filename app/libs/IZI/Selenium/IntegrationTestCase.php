<?php

namespace IZI\Selenium;

class IntegrationTestCase extends TestCase
{
    protected function setUp()
    {
        $this->setBrowserUrl(WEB_PATH);
    }
}