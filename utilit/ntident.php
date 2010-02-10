<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
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
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';

require_once dirname(__FILE__).'/loginIncl.php';
require_once dirname(__FILE__).'/urlincl.php';


/**
 * Authenticate user by nickname
 * @param string	$nickname
 * @return bool
 */
function NTuserLoginAuth($nickname)
{
	global $babDB;
	
	if (empty($nickname)) {
		// not supported
		return false;
	}
	

	$oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
	if (false === $oAuthObject)
	{
		// ovidentia is not installed correctly
		return false;
	}

	$sql = 'SELECT id FROM ' . BAB_USERS_TBL . ' WHERE nickname=' . $babDB->quote($nickname);

	$result = $babDB->db_query($sql);
	if ($babDB->db_num_rows($result) < 1)
	{
		// user not found
		return false;
	}
	
	
	list($id_user) = $babDB->db_fetch_array($result);
	
	
	if (!$oAuthObject->userCanLogin($id_user))
	{
		// disabled account
		return false;
	}
	
	bab_setUserSessionInfo($id_user);
	bab_logUserConnectionToStat($id_user);
	bab_updateUserConnectionDate($id_user);
	return true;
}




/**
 * Redirect to same page with nickname in parameter
 * @return unknown_type
 */
function NTuserLoginRedirect()
{
	global $babBody;
	
	if (isset($_GET['NTidUser'])) {
		return;
	}
	
	$GLOBALS['babMeta'] = '';
	$GLOBALS['babCss'] = '';
	$GLOBALS['babOvidentiaJs'] = $GLOBALS['babInstallPath']."scripts/ovidentia.js";
	
	
	// It will redirect here with the variable NTidUser set to the Windows(TM) user name.
    $babBody->babPopup('
	    <script type="text/javascript">
	    //<![CDATA[
	    
	    function NTuserLoginRedirect(nickname) {
	    	var query = \'\' + this.location;
	    	if (query.indexOf(\'?\') != -1) {
				window.location.href = query+"&NTidUser="+escape(nickname);
			} else {
				window.location.href = query+"?NTidUser="+escape(nickname);
			}
	    }
	    
		try {
			var WshShell = new ActiveXObject("WScript.Network");
			NTuserLoginRedirect(WshShell.Username);
			
	    } catch(e) { 
	    	NTuserLoginRedirect(\'\');
		}
		
		//]]>
	    </script>
    ');
}





if (isset($_GET['NTidUser']) && empty($_SESSION['BAB_SESS_USERID']))
{
	// prevent more login redirect on login failure 
	// or with manual disconnect
	setcookie('BAB_NTLOGIN', 1);
	
	NTuserLoginAuth($_GET['NTidUser']);
}


if (empty($_SESSION['BAB_SESS_USERID']) && !isset($_COOKIE['BAB_NTLOGIN']))
{
	NTuserLoginRedirect();
}