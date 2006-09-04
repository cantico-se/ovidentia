<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";

function NTuserLogin($nickname)
	{
	global $babBody;
	$sql="select * from ".BAB_USERS_TBL." where nickname='$nickname'";
	$db = $GLOBALS['babDB'];
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$_SESSION['BAB_SESS_NTREGISTER'] = false;
		$GLOBALS['BAB_SESS_NTREGISTER'] = false;
		return false;
		} 
	else 
		{
		$arr = $db->db_fetch_array($result);
		if( $arr['disabled'] == '1')
			{
			$_SESSION['BAB_SESS_NTREGISTER'] = false;
			$GLOBALS['BAB_SESS_NTREGISTER'] = false;
			return false;
			}
		if ($arr['is_confirmed'] == '1')
			{
			if( isset($_SESSION))
				{
				$_SESSION['BAB_SESS_NTREGISTER'] = true;
				$_SESSION['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$_SESSION['BAB_SESS_FIRSTNAME'] = $arr['firstname'];
				$_SESSION['BAB_SESS_LASTNAME'] = $arr['lastname'];
				$_SESSION['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$_SESSION['BAB_SESS_EMAIL'] = $arr['email'];
				$_SESSION['BAB_SESS_USERID'] = $arr['id'];
				$_SESSION['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				
				$GLOBALS['BAB_SESS_NTREGISTER'] = true;
				$GLOBALS['BAB_SESS_NICKNAME'] = $_SESSION['BAB_SESS_NICKNAME'];
				$GLOBALS['BAB_SESS_FIRSTNAME'] = $_SESSION['BAB_SESS_FIRSTNAME'];
				$GLOBALS['BAB_SESS_LASTNAME'] = $_SESSION['BAB_SESS_LASTNAME'];
				$GLOBALS['BAB_SESS_USER'] = $_SESSION['BAB_SESS_USER'];
				$GLOBALS['BAB_SESS_EMAIL'] = $_SESSION['BAB_SESS_EMAIL'];
				$GLOBALS['BAB_SESS_USERID'] = $_SESSION['BAB_SESS_USERID'];
				$GLOBALS['BAB_SESS_HASHID'] = $_SESSION['BAB_SESS_HASHID'];
				}
			else
				{
				$GLOBALS['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$GLOBALS['BAB_SESS_FIRSTNAME'] = $arr['firstname'];
				$GLOBALS['BAB_SESS_LASTNAME'] = $arr['lastname'];
				$GLOBALS['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$GLOBALS['BAB_SESS_EMAIL'] = $arr['email'];
				$GLOBALS['BAB_SESS_USERID'] = $arr['id'];
				$GLOBALS['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				}
			return true;
			}
		else
			{
			$_SESSION['BAB_SESS_NTREGISTER'] = false;
			$GLOBALS['BAB_SESS_NTREGISTER'] = false;
			return false;
			}
		}
	}

if ( substr($GLOBALS['babPhpSelf'],0,1) == '/' )
		$babPhpSelf = substr($GLOBALS['babPhpSelf'],1);
	else
		$babPhpSelf = $GLOBALS['babPhpSelf'];
	
if (!session_is_registered('BAB_SESS_NTREGISTER'))
	{
	if (!isset($_COOKIE['ntident'])) setcookie("ntident","connexion");
	session_register("BAB_SESS_NTREGISTER");
	session_register("BAB_SESS_NTREGISTER2");
	$GLOBALS['BAB_SESS_NTREGISTER2'] = false;
	$GLOBALS['BAB_SESS_NTREGISTER'] = true;
	header("location:".$GLOBALS['babUrl'].$babPhpSelf);
	}

if (isset($NTidUser) && $GLOBALS['BAB_SESS_NTREGISTER'] && isset($_COOKIE['ntident']) && $_COOKIE['ntident'] == "connexion")
	{
	if (NTuserLogin($NTidUser))
		{
		$GLOBALS['BAB_SESS_NTREGISTER2'] = true;
		$GLOBALS['BAB_SESS_NTREGISTER'] = false;
		//setcookie("ntident","ok");
		$db = $GLOBALS['babDB'];
		$res=$db->db_query("select datelog from ".BAB_USERS_TBL." where id='".$BAB_SESS_USERID."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$db->db_query("update ".BAB_USERS_TBL." set datelog=now(), lastlog='".$arr['datelog']."' where id='".$BAB_SESS_USERID."'");
			}

		$res=$db->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='0' and sessid='".session_id()."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$db->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$BAB_SESS_USERID."' where id='".$arr['id']."'");
			}
		}
	}


if( $GLOBALS['BAB_SESS_NTREGISTER'] && isset($_COOKIE['ntident']) && $_COOKIE['ntident'] == "connexion")
	{
	$babBody->script .= 
		'//-->
	</script>
	<script type="text/javascript">
	<!--
	var WshShell = 0;
	var strLoggedUser
	function loggedUser()
	{
		try {
    		WshShell = new ActiveXObject("WScript.Network");
    		query = \'\' + this.location;
    		if ( query.indexOf(\'?\') != -1 && query.indexOf(\'NTidUser\') == -1)
    			{window.location.href = query+"&NTidUser="+escape(WshShell.Username);}
    		else if (query.indexOf(\'NTidUser\') == -1)
    			{window.location.href = "'.$babPhpSelf.'?NTidUser="+escape(WshShell.Username);}
			}
		catch(e)
			{}
	}
	loggedUser();';
	}

if( $GLOBALS['BAB_SESS_NTREGISTER2'] && $GLOBALS['BAB_SESS_USERID'] )
	$babCnxLink = 0;
?>