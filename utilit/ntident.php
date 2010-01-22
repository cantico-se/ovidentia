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

function NTuserLogin($nickname)
{
	global $babBody, $babDB;

	$sql = 'SELECT * FROM ' . BAB_USERS_TBL . ' WHERE nickname=' . $babDB->quote($nickname);

	$result = $babDB->db_query($sql);
	if ($babDB->db_num_rows($result) < 1)
	{
		$_SESSION['BAB_SESS_NTREGISTER'] = false;
		return false;
	}
	else
	{
		$arr = $babDB->db_fetch_array($result);
		if ($arr['disabled'] != '1' && $arr['is_confirmed'] == '1')
		{
			$GLOBALS['BAB_SESS_NICKNAME'] = $arr['nickname'];
			$GLOBALS['BAB_SESS_FIRSTNAME'] = $arr['firstname'];
			$GLOBALS['BAB_SESS_LASTNAME'] = $arr['lastname'];
			$GLOBALS['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
			$GLOBALS['BAB_SESS_EMAIL'] = $arr['email'];
			$GLOBALS['BAB_SESS_USERID'] = $arr['id'];
			$GLOBALS['BAB_SESS_HASHID'] = $arr['confirm_hash'];
			if (isset($_SESSION))
			{
				$_SESSION['BAB_SESS_NTREGISTER'] = true;
				$_SESSION['BAB_SESS_NICKNAME'] = $GLOBALS['BAB_SESS_NICKNAME'];
				$_SESSION['BAB_SESS_FIRSTNAME'] = $GLOBALS['BAB_SESS_FIRSTNAME'];
				$_SESSION['BAB_SESS_LASTNAME'] = $GLOBALS['BAB_SESS_LASTNAME'];
				$_SESSION['BAB_SESS_USER'] = $GLOBALS['BAB_SESS_USER'];
				$_SESSION['BAB_SESS_EMAIL'] = $GLOBALS['BAB_SESS_EMAIL'];
				$_SESSION['BAB_SESS_USERID'] = $GLOBALS['BAB_SESS_USERID'];
				$_SESSION['BAB_SESS_HASHID'] = $GLOBALS['BAB_SESS_HASHID'];
			}
			bab_logUserConnectionToStat($GLOBALS['BAB_SESS_USERID']);
			return true;
		}
		else
		{
			$_SESSION['BAB_SESS_NTREGISTER'] = false;
			return false;
		}
	}
}

if (mb_substr($GLOBALS['babPhpSelf'], 0, 1) == '/')
{
	$babPhpSelf = mb_substr($GLOBALS['babPhpSelf'], 1);
}
else
{
	$babPhpSelf = $GLOBALS['babPhpSelf'];
}

if (!isset($_SESSION['BAB_SESS_NTREGISTER']))
{
	if (!isset($_COOKIE['ntident']))
	{
		setcookie('ntident', 'connexion');
	}
	$_SESSION['BAB_SESS_NTREGISTER2'] = false;
	$_SESSION['BAB_SESS_NTREGISTER'] = true;
	header('location:' . $GLOBALS['babUrl'] . $babPhpSelf);
}

if (isset($NTidUser) && $_SESSION['BAB_SESS_NTREGISTER'] && isset($_COOKIE['ntident']) && $_COOKIE['ntident'] == 'connexion')
{
	if (NTuserLogin($NTidUser))
	{
		$_SESSION['BAB_SESS_NTREGISTER2'] = true;
		$_SESSION['BAB_SESS_NTREGISTER'] = false;
		$res = $babDB->db_query('SELECT datelog FROM ' . BAB_USERS_TBL . ' WHERE id=' . $babDB->quote($GLOBALS['BAB_SESS_USERID']));
		if ($res && $babDB->db_num_rows($res) > 0)
		{
			$arr = $babDB->db_fetch_array($res);
			$babDB->db_query('UPDATE ' . BAB_USERS_TBL . ' SET datelog=now(), lastlog=' . $babDB->quote($arr['datelog']) . ' WHERE id=' . $babDB->quote($GLOBALS['BAB_SESS_USERID']));
		}

		$res = $babDB->db_query('SELECT * FROM ' . BAB_USERS_LOG_TBL . ' WHERE id_user=0 AND sessid=' . $babDB->quote(session_id()));
		if ($res && $babDB->db_num_rows($res) > 0)
		{
			$arr = $babDB->db_fetch_array($res);
			$babDB->db_query('UPDATE ' . BAB_USERS_LOG_TBL . ' SET id_user=' . $babDB->quote($GLOBALS['BAB_SESS_USERID']) . ' WHERE id=' . $babDB->quote($arr['id']));
		}
	}
}


if ($_SESSION['BAB_SESS_NTREGISTER'] && isset($_COOKIE['ntident']) && $_COOKIE['ntident'] == 'connexion')
{
	// This script must be included in the page.html template with a { script } template variable.
	// It will redirect here with the variable NTidUser set to the Windows(TM) user name.
    $babBody->script .= '
	try {
		var WshShell = new ActiveXObject("WScript.Network");
		var query = \'\' + this.location;
		if (query.indexOf(\'?\') != -1 && query.indexOf (\'NTidUser\') == -1) {
			window.location.href = query+"&NTidUser="+escape(WshShell.Username);
		} else if (query.indexOf(\'NTidUser\') == -1) {
			window.location.href = "'.$babPhpSelf.'?NTidUser="+escape(WshShell.Username);
		}
    } catch(e) { window.status = e.message; }
    ';
}

if ($_SESSION['BAB_SESS_NTREGISTER2'] && $GLOBALS['BAB_SESS_USERID'])
{
	$babCnxLink = 0;
}
