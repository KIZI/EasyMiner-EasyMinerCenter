<?php

namespace IZI\Selenium;

class TestCase extends \PHPUnit_Extensions_SeleniumTestCase
{

    public static $browsers = array(
//        array(
//            'name' => 'IE on Windows 7',
//            'browser' => '*custom /Users/radek/Applications\ \(Parallels\)/\{b30ccbcd-c9aa-4bfd-8346-330ebe1c2bea\}\ Applications.localized/Internet\ Explorer.app'
//        )
        array(
            'name' => 'Firefox on Mac OS X',
            'browser' => '*firefox'
        )//,
//        array(
//            'name' => 'Safari on Mac OS X',
//            'browser' => '*safari'
//        )
//        array(
//            'name' => 'Chrome on Mac OS X',
//            'browser' => '*googlechrome /Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --ignore-certificate-errors'
//        )
    );
}