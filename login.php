<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
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
	$req="select * from ".BAB_USERS_LOG_TBL." where id_user='$BAB_SESS_USERID'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$req="update ".BAB_USERS_LOG_TBL." set datelog=now(), lastlog='".$arr['datelog']."', dateact=now(),islogged='Y' where id_user='$BAB_SESS_USERID'";
		}
	else
		{
		$req="insert into ".BAB_USERS_LOG_TBL." (id_user, datelog, lastlog, dateact,islogged) values ('$BAB_SESS_USERID', now(), now(), now(), 'Y')";
		}
	$res=$db->db_query($req);
	return true;
	}

function signOff()
	{
	global $babBody, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	$db = $GLOBALS['babDB'];
	$req="select * from ".BAB_USERS_LOG_TBL." where id_user='$BAB_SESS_USERID'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req="update ".BAB_USERS_LOG_TBL." set islogged='N' where id_user='$BAB_SESS_USERID'";
		$res=$db->db_query($req);
		}

	$BAB_SESS_NICKNAME = "";
	$BAB_SESS_USER = "";
	$BAB_SESS_EMAIL = "";
	$BAB_SESS_USERID ="";
	$BAB_SESS_HASHID = "";
	session_unregister("BAB_SESS_NICKNAME");
	session_unregister("BAB_SESS_USER");
	session_unregister("BAB_SESS_EMAIL");
	session_unregister("BAB_SESS_USERID");
	session_unregister("BAB_SESS_HASHID");
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
	if( !addUser( $firstname, $lastname, $nickname, $email, $password1, $password2))
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
		$babBody->title = bab_translate("Please provide a valid email.") . "<br>";
		$babBody->title .= bab_translate("We will send you an email for confirmation before you can use our services") . "<br>";
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