<?php
/************************************************************************
 * Ovidentia                                                            *
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ************************************************************************
 * This program is free software; you can redistribute it and/or modify *
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
include $babInstallPath."admin/register.php";

function displayLogin()
	{
	global $babBody;
	class temp
		{
		var $nickname;
		var $password;

		function temp()
			{
			$this->nickname = bab_translate("Nickname");
			$this->password = bab_translate("Password");
			$this->login = bab_translate("Login");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"login.html", "login"));
	}


function emailPassword()
	{
	global $babBody;
	class temp
		{
		var $nickname;
		var $send;

		function temp()
			{
			$this->nickname = bab_translate("Your nickname");
			$this->send = bab_translate("Send");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"login.html", "emailpassword"));
	}

function signOn( $nickname, $password)
	{
	global $babBody, $BAB_SESS_USER, $BAB_SESS_USERID;
	if( empty($nickname) || empty($password))
		{
		$babBody->msgerror = bab_translate("You must complete all fields !!");
		return false;
		}

	if( !userLogin($nickname, $password))
		return false;

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

	return true;
	}

function signOff()
	{
	global $babBody, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".BAB_USERS_LOG_TBL." where id_user='".$BAB_SESS_USERID."' and sessid='".session_id()."'");

	if( isset($_SESSION))
		{
		$_SESSION['BAB_SESS_NICKNAME'] = "";
		$_SESSION['BAB_SESS_USER'] = "";
		$_SESSION['BAB_SESS_EMAIL'] = "";
		$_SESSION['BAB_SESS_USERID'] = "";
		$_SESSION['BAB_SESS_HASHID'] = "";
		unset($_SESSION['BAB_SESS_NICKNAME']);
		unset($_SESSION['BAB_SESS_USER']);
		unset($_SESSION['BAB_SESS_EMAIL']);
		unset($_SESSION['BAB_SESS_USERID']);
		unset($_SESSION['BAB_SESS_HASHID']);
		unset($_SESSION);
		session_destroy();
		}
	else
		{
		$GLOBALS['BAB_SESS_NICKNAME'] = "";
		$GLOBALS['BAB_SESS_USER'] = "";
		$GLOBALS['BAB_SESS_EMAIL'] = "";
		$GLOBALS['BAB_SESS_USERID'] ="";
		$GLOBALS['BAB_SESS_HASHID'] = "";
		session_unregister("BAB_SESS_NICKNAME");
		session_unregister("BAB_SESS_USER");
		session_unregister("BAB_SESS_EMAIL");
		session_unregister("BAB_SESS_USERID");
		session_unregister("BAB_SESS_HASHID");
		session_destroy();
		}
	Header("Location: ". $GLOBALS['babPhpSelf']);
	}

function userCreate($firstname, $lastname, $nickname, $email)
	{
	global $babBody;
	class temp
		{
		var $firstname;
		var $lastname;
		var $nickname;
		var $email;
		var $password;
		var $repassword;
		var $adduser;
		var $firstnameval;
		var $lastnameval;
		var $nicknameval;
		var $emailval;
		var $infotxt;

		function temp($firstname, $lastname, $nickname, $email)
			{
			$this->firstnameval = $firstname != ""? $firstname: "";
			$this->lastnameval = $lastname != ""? $lastname: "";
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->emailval = $email != ""? $email: "";
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->nickname = bab_translate("Nickname");
			$this->email = bab_translate("Email");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Paasword");
			$this->adduser = bab_translate("Register");
			$this->infotxt = bab_translate("Please provide a valid email.") . "<br>";
			$this->infotxt .= bab_translate("We will send you an email for confirmation before you can use our services") . "<br>";
			}
		}

	$temp = new temp($firstname, $lastname, $nickname, $email);
	$babBody->babecho(	bab_printTemplate($temp,"login.html", "usercreate"));
	}

/* main */

$db = $GLOBALS['babDB'];
$res=$db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'");
if( $res && $db->db_num_rows($res) > 0 )
{
	$r = $db->db_fetch_array($res);
}

if( isset($login) && $login == "login")
	{
	if(!signOn($nickname, $password))
		return;
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=entry");
	}


if( isset($adduser) && $adduser == "register" && $r['registration'] == 'Y')
	{
	if( !addUser( $firstname, $lastname, $nickname, $email, $password1, $password2, false))
		$cmd = "register";
	}

if( isset($sendpassword) && $sendpassword == "send")
	{
	sendPassword($nickname);
	}

switch($cmd)
	{
	case "signoff":
		signOff();
		break;

	case "register":
		$babBody->title = bab_translate("Register");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $r['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		userCreate($firstname, $lastname, $nickname, $email);
		break;

	case "emailpwd":
		$babBody->title = bab_translate("Email a new password");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $r['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		emailPassword();
		break;

	case "signon":
	default:
		$babBody->title = bab_translate("Login");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $r['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		displayLogin();
		break;
	}
$babBody->setCurrentItemMenu($cmd);

?>
