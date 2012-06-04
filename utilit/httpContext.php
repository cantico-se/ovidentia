<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once dirname(__FILE__).'/addonapi.php';
include_once dirname(__FILE__).'/session.class.php';


/**
 * Save the REQUEST, POST, GET into the session
 */
function bab_storeHttpContext()
{
	$session = bab_getInstance('bab_Session');
	
	if('login' == bab_rp('tg', '') && 'signon' == bab_rp('cmd', 'signon'))
	{
		$tabreferer = array();

		$referer = bab_rp('referer', '');
		if( !empty($referer))
		{
			$rr = parse_url($referer);
			if( $rr !== false && isset($rr['query']))
			{
				parse_str($rr['query'], $tabreferer);	
			}
			
			if ($tabreferer)
			{
				$session->babHttpContext = array('Post' => array(),
				'Get' => $tabreferer, 'Request' => array());
				return;
			}
		}
	}
	
	$session->babHttpContext = array('Post' => $_POST,
		'Get' => $_GET, 'Request' => $_REQUEST);
}

/**
 * Restore the REQUEST, POST, GET from the session
 * 
 * If the restored context contains only GET parameters, we redirect
 * to the corresponding page, so that the url will be visible in
 * the user's web browser navigation bar.
 * 
 * @see bab_storeHttpContext
 */
function bab_restoreHttpContext()
{
	$session = bab_getInstance('bab_Session');

	if(isset($session->babHttpContext))
	{
		$_POST		= $session->babHttpContext['Post'];
		$_GET 		= $session->babHttpContext['Get'];
		$_REQUEST 	= $session->babHttpContext['Request'];

		if (empty($_POST)) {
			require_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';
			$redirectUrl = bab_getSelf();
			if ($query = bab_url::buildQuery($_GET)) {
				$redirectUrl .= '?' . $query;
			}

			header('Location: ' . $redirectUrl);
			unset($session->babHttpContext);
			exit;
		}

		unset($session->babHttpContext);
	}
}