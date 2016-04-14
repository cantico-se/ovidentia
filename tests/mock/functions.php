<?php 


$GLOBALS['babInstallPath'] = 'ovidentia/';
$GLOBALS['babInstallPath'] = $GLOBALS['babInstallPath'];

require_once $GLOBALS['babInstallPath'].'utilit/addonapi.php';
require_once $GLOBALS['babInstallPath'].'utilit/session.class.php';
$session = bab_getInstance('bab_Session');
/*@var $session bab_Session */
$session->setStorage(new bab_SessionMockStorage());
