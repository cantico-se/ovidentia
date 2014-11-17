<?php 


$babInstallPath = 'ovidentia/';
$GLOBALS['babInstallPath'] = $babInstallPath;

require_once $GLOBALS['babInstallPath'].'utilit/addonapi.php';
require_once $GLOBALS['babInstallPath'].'utilit/session.class.php';
$session = bab_getInstance('bab_Session');
/*@var $session bab_Session */
$session->setStorage(new bab_SessionMockStorage());
