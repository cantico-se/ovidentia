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


/**
 * Retun true if the HttpContext was saved, false otherwise
 * 
 * @return boolean True if the context was saved, false otherwise
 */
function bab_haveHttpContext()
{
	return array_key_exists('babHttpContext', $_SESSION); 
}


/**
 * Save the REQUEST, POST, GET into the session
 */
function bab_storeHttpContext()
{
	if('login' == bab_rp('tg', '') && 'signon' == bab_rp('cmd', ''))
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

		}
		$_SESSION['babHttpContext'] = array('Post' => array(),
			'Get' => $tabreferer, 'Request' => array());
		return;
	}
	
	$_SESSION['babHttpContext'] = array('Post' => $_POST,
		'Get' => $_GET, 'Request' => $_REQUEST);
}

/**
 * Restore the REQUEST, POST, GET from the session
 * 
 * If the context contains only GET parameters, we redirect
 * to the corresponding page, so that the url will be visible in
 * web browser navigation bar.
 * 
 * @see bab_storeHttpContext, bab_haveHttpContext
 */
function bab_restoreHttpContext()
{
	if(bab_haveHttpContext())
	{
		$_POST		= $_SESSION['babHttpContext']['Post'];
		$_GET 		= $_SESSION['babHttpContext']['Get'];
		$_REQUEST 	= $_SESSION['babHttpContext']['Request'];

		if (empty($_POST) && !empty($_GET)) {
			require_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';
			$redirectUrl = $GLOBALS['babPhpSelf'] . '?' . bab_url::buildQuery($_GET);
			header('Location: ' . $redirectUrl);
		}

		unset($_SESSION['babHttpContext']);
	}
}