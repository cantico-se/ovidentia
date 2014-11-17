<?php

require dirname(__FILE__).'/mock/functions.php';
require $GLOBALS['babInstallPath'].'utilit/body.class.php';

class bab_BodyTest extends PHPUnit_Framework_TestCase
{
    protected function addStyleSheet($input, $expected)
    {
        $body = new babBody();
        $body->addStyleSheet($input);
        
        $this->assertCount(1, $body->styleSheet);
        $this->assertEquals($expected, $body->styleSheet[0]);
    }
    
    
    public function testAddStyleSheet_stylesFolder()
    {
        $this->addStyleSheet('artedit.css', 'ovidentia/styles/artedit.css');
        $this->addStyleSheet('ovidentia/styles/artedit.css', 'ovidentia/styles/artedit.css');
        $this->addStyleSheet('addons/fakeaddon/artedit.css', 'ovidentia/styles/addons/fakeaddon/artedit.css');
    }
    
    
    public function testAddStyleSheet_vendorFolder()
    {
        $this->addStyleSheet('vendor/ovidentia/fakeaddon/styles/artedit.css', 'vendor/ovidentia/fakeaddon/styles/artedit.css');
    }
}
