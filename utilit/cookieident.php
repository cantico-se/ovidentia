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

if (isset($_COOKIE['c_password'])) 
{
	$token = trim($_COOKIE['c_password']);
	
	if (!empty($token) && !isset($_SESSION['BAB_SESS_USERID']))
	{
		require_once $GLOBALS['babInstallPath'] . 'admin/register.php';
		require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';
		
		$oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
		if (false === $oAuthObject)
		{
			// If the default authentication method 'AuthOvidentia' does not exist
			// for example during first installation we (re)create it.
			Func_PortalAuthentication_AuthOvidentia::registerAuthType();
			$oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
		}
		if (false === $oAuthObject)
		{
			destroyAuthCookie();
		}
		
		$iIdUser = $oAuthObject->authenticateUserByCookie($token);
		if ($oAuthObject->userCanLogin($iIdUser))
		{
			bab_setUserSessionInfo($iIdUser);
		}
		else 
		{
			destroyAuthCookie();
		}
	}
}
