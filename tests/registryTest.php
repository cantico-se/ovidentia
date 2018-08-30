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


    public function testRegistryGetReturnsCustomValueIfDefined()
    {
        bab_Registry::set('/testAddon/testKey3', 'customDefaultValue3') ;

        $value = bab_Registry::get('/testAddon/testKey3');
        $this->assertEquals($value, 'customDefaultValue3');
    }


    public function testRegistryGetPrefersCustomValueToDefaultValue()
    {
        define('/testAddon/testKey4', 'defaultValue4');

        $value = bab_Registry::set('/testAddon/testKey4', 'customDefaultValue4');

        $value = bab_Registry::get('/testAddon/testKey4');
        $this->assertEquals($value, 'customDefaultValue4');
    }

    public function testRegistryGetPrefersOverrideValueToDefaultValue()
    {
        define('/testAddon/testKey7', 'defaultValue7');

        bab_Registry::override('/testAddon/testKey7', 'overrideValue7');

        $value = bab_Registry::get('/testAddon/testKey7');

        $this->assertEquals($value, $value);
    }


    public function testRegistryGetPrefersOverrideValueToCustomValue()
    {
        bab_Registry::set('/testAddon/testKey6', 'customDefaultValue6');
        bab_Registry::override('/testAddon/testKey6', 'overrideValue6');

        $value = bab_Registry::get('/testAddon/testKey6');

        $this->assertEquals($value, 'overrideValue6');
    }


    public function testRegistryGetPrefersLockedValueToDefaultValue()
    {
        define('/testAddon/testKey2', 'defaultValue2');

        $value = bab_Registry::get('/testAddon/testKey2');
        $this->assertEquals($value, 'defaultValue2');

        define('!/testAddon/testKey2', 'lockedValue2');

        $value = bab_Registry::get('/testAddon/testKey2');
        $this->assertEquals($value, 'lockedValue2');
    }


    public function testRegistryGetPrefersLockedValueToCustomValue()
    {
        define('!/testAddon/testKey8', 'lockedValue8');

        bab_Registry::set('/testAddon/testKey8', 'customDefaultValue8');

        $value = bab_Registry::get('/testAddon/testKey8') ;
        $this->assertEquals($value, 'lockedValue8');
    }



    public function testRegistryGetPrefersLockedValueToOverrideValue()
    {
        define('!/testAddon/testKey5', 'lockedValue5');

        bab_Registry::override('/testAddon/testKey5', 'customDefaultValue5');

        $value = bab_Registry::get('/testAddon/testKey5') ;
        $this->assertEquals($value, 'lockedValue5');
    }
}
