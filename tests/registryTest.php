<?php

require dirname(__FILE__).'/mock/functions.php';
require_once $GLOBALS['babInstallPath'].'utilit/dbutil.php';
require_once $GLOBALS['babInstallPath'].'utilit/registry.php';



class bab_RegistryTest extends PHPUnit_Framework_TestCase
{



    public function testRegistryGetReturnsNullIfConstantNotDefined()
    {
        $value = bab_Registry::get('/testAddon/undefinedKey1');

        $this->assertEquals($value, null);
    }



    public function testRegistryGetReturnsConstantIfDefined()
    {
        define('/testAddon/testRegistryGetReturnsConstantIfDefined', 'testValue');

        $value = bab_Registry::get('/testAddon/testRegistryGetReturnsConstantIfDefined');

        $this->assertEquals($value, 'testValue');
    }


    public function testRegistryGetPrependsSlashToPathIfMissing()
    {
        define('/testAddon/testRegistryGetPrependsSlashToPathIfMissing', 'testValue');

        $value = bab_Registry::get('testAddon/testRegistryGetPrependsSlashToPathIfMissing');

        $this->assertEquals($value, 'testValue');
    }


    public function testRegistryGetReturnsRegistryValueIfDefined()
    {
        bab_Registry::set('/testAddon/testKey3', 'registryValue3') ;

        $value = bab_Registry::get('/testAddon/testKey3');
        $this->assertEquals($value, 'registryValue3');
    }


    public function testRegistryGetPrefersRegistryValueToConstantValue()
    {
        define('/testAddon/testKey4', 'defaultValue4');

        $value = bab_Registry::set('/testAddon/testKey4', 'registryValue4');

        $value = bab_Registry::get('/testAddon/testKey4');
        $this->assertEquals($value, 'registryValue4');
    }

    public function testRegistryGetPrefersOverrideValueToConstantValue()
    {
        define('/testAddon/testKey7', 'defaultValue7');

        bab_Registry::override('/testAddon/testKey7', 'overrideValue7');

        $value = bab_Registry::get('/testAddon/testKey7');

        $this->assertEquals($value, $value);
    }


    public function testRegistryGetPrefersOverrideValueToRegistryValue()
    {
        bab_Registry::set('/testAddon/testKey6', 'registryValue6');
        bab_Registry::override('/testAddon/testKey6', 'overrideValue6');

        $value = bab_Registry::get('/testAddon/testKey6');

        $this->assertEquals($value, 'overrideValue6');
    }


    public function testRegistryGetPrefersImportantConstantValueToConstantValue()
    {
        define('/testAddon/testKey2', 'defaultValue2');

        $value = bab_Registry::get('/testAddon/testKey2');
        $this->assertEquals($value, 'defaultValue2');

        define('!/testAddon/testKey2', 'importantConstantValue2');

        $value = bab_Registry::get('/testAddon/testKey2');
        $this->assertEquals($value, 'importantConstantValue2');
    }


    public function testRegistryGetPrefersImportantConstantValueToRegistryValue()
    {
        define('!/testAddon/testKey8', 'importantConstantValue8');

        bab_Registry::set('/testAddon/testKey8', 'registryValue8');

        $value = bab_Registry::get('/testAddon/testKey8') ;
        $this->assertEquals($value, 'importantConstantValue8');
    }



    public function testRegistryGetPrefersImportantConstantValueToOverrideValue()
    {
        define('!/testAddon/testKey5', 'importantConstantValue5');

        bab_Registry::override('/testAddon/testKey5', 'registryValue5');

        $value = bab_Registry::get('/testAddon/testKey5') ;
        $this->assertEquals($value, 'importantConstantValue5');
    }
}
