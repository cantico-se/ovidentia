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

function cookieUserLogin($nickname,$password)
	{
	global $babBody;
	$password=strtolower($password);
	$db = $GLOBALS['babDB'];
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET cnx_try=cnx_try+1 WHERE sessid='".session_id()."'");
	list($cnx_try) = $db->db_fetch_array($db->db_query("SELECT cnx_try FROM ".BAB_USERS_LOG_TBL." WHERE sessid='".session_id()."'"));
	if( $cnx_try > 5)
		{
		$babBody->msgerror = bab_translate("Maximum connexion attempts has been reached");
		return false;
		}

	$nickname = bab_isMagicQuotesGpcOn() ? $nickname : mysql_escape_string($nickname);

	$sql="select * from ".BAB_USERS_TBL." where nickname='".$nickname."' and password='".$password."'";
	$result=$db->db_query($sql);
	
	if ($db->db_num_rows($result) < 1)
		{
		setcookie ("c_nickname", "", time() - 3600);
		setcookie ("c_password", "", time() - 3600);
		return false;
		} 
	else 
		{
		$arr = $db->db_fetch_array($result);
		if( $arr['disabled'] == '1')
			{
			$babBody->msgerror = bab_translate("Sorry, your account is disabled. Please contact your administrator");
			return false;
			}
		if ($arr['is_confirmed'] == '1')
			{
			if( isset($_SESSION))
				{
				$_SESSION['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$_SESSION['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$_SESSION['BAB_SESS_FIRSTNAME'] = $arr['firstname'];
				$_SESSION['BAB_SESS_LASTNAME'] = $arr['lastname'];
				$_SESSION['BAB_SESS_EMAIL'] = $arr['email'];
				$_SESSION['BAB_SESS_USERID'] = $arr['id'];
				$_SESSION['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				$GLOBALS['BAB_SESS_NICKNAME'] = $_SESSION['BAB_SESS_NICKNAME'];
				$GLOBALS['BAB_SESS_USER'] = $_SESSION['BAB_SESS_USER'];
				$GLOBALS['BAB_SESS_FIRSTNAME'] = $_SESSION['BAB_SESS_FIRSTNAME'];
				$GLOBALS['BAB_SESS_LASTNAME'] = $_SESSION['BAB_SESS_LASTNAME'];
				$GLOBALS['BAB_SESS_EMAIL'] = $_SESSION['BAB_SESS_EMAIL'];
				$GLOBALS['BAB_SESS_USERID'] = $_SESSION['BAB_SESS_USERID'];
				$GLOBALS['BAB_SESS_HASHID'] = $_SESSION['BAB_SESS_HASHID'];
				}
			else
				{
				$GLOBALS['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$GLOBALS['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$GLOBALS['BAB_SESS_FIRSTNAME'] = $arr['firstname'];
				$GLOBALS['BAB_SESS_LASTNAME'] = $arr['lastname'];
				$GLOBALS['BAB_SESS_EMAIL'] = $arr['email'];
				$GLOBALS['BAB_SESS_USERID'] = $arr['id'];
				$GLOBALS['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				}

			$res=$db->db_query("select datelog from ".BAB_USERS_TBL." where id='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$db->db_query("update ".BAB_USERS_TBL." set datelog=now(), lastlog='".$arr['datelog']."' where id='".$GLOBALS['BAB_SESS_USERID']."'");
				}

			$res=$db->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='0' and sessid='".session_id()."'");
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$db->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$GLOBALS['BAB_SESS_USERID']."' where id='".$arr['id']."'");
				}
			
			return true;
			}
		else
			{
			$babBody->msgerror =  bab_translate("Sorry - You haven't Confirmed Your Account Yet");
			return false;
			}
		}
	}

if (!isset($HTTP_COOKIE_VARS['c_nickname'])) $HTTP_COOKIE_VARS['c_nickname'] = '';
if (!isset($HTTP_COOKIE_VARS['c_password'])) $HTTP_COOKIE_VARS['c_password'] = '';

if (trim($HTTP_COOKIE_VARS['c_nickname']) != "" && trim($HTTP_COOKIE_VARS['c_password']) != "" && !$GLOBALS['BAB_SESS_USERID'])
	{
	cookieUserLogin($HTTP_COOKIE_VARS['c_nickname'],$HTTP_COOKIE_VARS['c_password']);
	}
?>