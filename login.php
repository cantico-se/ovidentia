<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/register.php";

function displayLogin()
	{
	global $body;
	class temp
		{
		var $nickname;
		var $password;

		function temp()
			{
			$this->nickname = babTranslate("Nickname");
			$this->password = babTranslate("Password");
			$this->login = babTranslate("Login");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"login.html", "login"));
	}


function emailPassword()
	{
	global $body;
	class temp
		{
		var $nickname;
		var $send;

		function temp()
			{
			$this->nickname = babTranslate("Your nickname");
			$this->send = babTranslate("Send");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"login.html", "emailpassword"));
	}

function signOn( $nickname, $password)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_USERID;
	if( empty($nickname) || empty($password))
		{
		$body->msgerror = babTranslate("You must complete all fields !!");
		return false;
		}

	if( !userLogin($nickname, $password))
		return false;

	$db = new db_mysql();
	$req="select * from users_log where id_user='$BAB_SESS_USERID'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$req="update users_log set datelog=now(), lastlog='".$arr[datelog]."', dateact=now(),islogged='Y' where id_user='$BAB_SESS_USERID'";
		}
	else
		{
		$req="insert into users_log (id_user, datelog, lastlog, dateact,islogged) values ('$BAB_SESS_USERID', now(), now(), now(), 'Y')";
		}
	$res=$db->db_query($req);
	return true;
	}

function signOff()
	{
	global $body, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$LOGGED_IN;
	
	$db = new db_mysql();
	$req="select * from users_log where id_user='$BAB_SESS_USERID'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req="update users_log set islogged='N' where id_user='$BAB_SESS_USERID'";
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
	Header("Location: index.php");
	}

function userCreate($firstname, $lastname, $nickname, $email)
	{
	global $body;
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
			$this->firstname = babTranslate("First Name");
			$this->lastname = babTranslate("Last Name");
			$this->nickname = babTranslate("Nickname");
			$this->email = babTranslate("Email");
			$this->password = babTranslate("Password");
			$this->repassword = babTranslate("Retype Paasword");
			$this->adduser = babTranslate("Register");
			}
		}

	$temp = new temp($firstname, $lastname, $nickname, $email);
	$body->babecho(	babPrintTemplate($temp,"login.html", "usercreate"));
	}

/* main */
if( isset($login) && $login == "login")
	{
	if(!signOn($nickname, $password))
		return;
	Header("Location: index.php?tg=calview");
	}


if( isset($adduser) && $adduser == "register")
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
		$body->title = babTranslate("Please provide a valid email.") . "<br>";
		$body->title .= babTranslate("We will send you an email for confirmation before you can use our services") . "<br>";
		$body->addItemMenu("signon", babTranslate("Login"), $GLOBALS[babUrl]."index.php?tg=login&cmd=signon");
		$body->addItemMenu("register", babTranslate("Register"), $GLOBALS[babUrl]."index.php?tg=login&cmd=register");
		$body->addItemMenu("emailpwd", babTranslate("Lost Password"), $GLOBALS[babUrl]."index.php?tg=login&cmd=emailpwd");
		userCreate($firstname, $lastname, $nickname, $email);
		break;

	case "emailpwd":
		$body->title = babTranslate("Email a new password");
		$body->addItemMenu("signon", babTranslate("Login"), $GLOBALS[babUrl]."index.php?tg=login&cmd=signon");
		$body->addItemMenu("register", babTranslate("Register"), $GLOBALS[babUrl]."index.php?tg=login&cmd=register");
		$body->addItemMenu("emailpwd", babTranslate("Lost Password"), $GLOBALS[babUrl]."index.php?tg=login&cmd=emailpwd");
		emailPassword();
		break;

	case "signon":
	default:
		$body->title = babTranslate("Login");
		$body->addItemMenu("signon", babTranslate("Login"), $GLOBALS[babUrl]."index.php?tg=login&cmd=signon");
		$body->addItemMenu("register", babTranslate("Register"), $GLOBALS[babUrl]."index.php?tg=login&cmd=register");
		$body->addItemMenu("emailpwd", babTranslate("Lost Password"), $GLOBALS[babUrl]."index.php?tg=login&cmd=emailpwd");
		displayLogin();
		break;
	}
$body->setCurrentItemMenu($cmd);

?>