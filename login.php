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
include $babInstallPath."admin/register.php";

function displayLogin($url)
	{
	global $babBody;
	class temp
		{
		var $nickname;
		var $password;

		function temp($url)
			{
			$this->nickname = bab_translate("Nickname");
			$this->password = bab_translate("Password");
			$this->login = bab_translate("Login");
			$this->referer = $url;
			$this->life = bab_translate("Remember my login");
			$this->nolife = bab_translate("No");
			$this->oneday = bab_translate("one day");
			$this->oneweek = bab_translate("one week");
			$this->onemonth = bab_translate("one month");
			$this->oneyear = bab_translate("one year");
			$this->infinite = bab_translate("unlimited");
			}
		}

	$temp = new temp($url);
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

function signOff()
	{
	global $babBody, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".BAB_USERS_LOG_TBL." where id_user='".$BAB_SESS_USERID."' and sessid='".session_id()."'");

	if( isset($_SESSION))
		{
		$_SESSION['BAB_SESS_NICKNAME'] = "";
		$_SESSION['BAB_SESS_USER'] = "";
		$_SESSION['BAB_SESS_FIRSTNAME'] = "";
		$_SESSION['BAB_SESS_LASTNAME'] = "";
		$_SESSION['BAB_SESS_EMAIL'] = "";
		$_SESSION['BAB_SESS_USERID'] = "";
		$_SESSION['BAB_SESS_HASHID'] = "";
		unset($_SESSION['BAB_SESS_NICKNAME']);
		unset($_SESSION['BAB_SESS_USER']);
		unset($_SESSION['BAB_SESS_FIRSTNAME']);
		unset($_SESSION['BAB_SESS_LASTNAME']);
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
		$GLOBALS['BAB_SESS_FIRSTNAME'] = "";
		$GLOBALS['BAB_SESS_LASTNAME'] = "";
		$GLOBALS['BAB_SESS_EMAIL'] = "";
		$GLOBALS['BAB_SESS_USERID'] ="";
		$GLOBALS['BAB_SESS_HASHID'] = "";
		session_unregister("BAB_SESS_NICKNAME");
		session_unregister("BAB_SESS_USER");
		session_unregister("BAB_SESS_FIRSTNAME");
		session_unregister("BAB_SESS_LASTNAME");
		session_unregister("BAB_SESS_EMAIL");
		session_unregister("BAB_SESS_USERID");
		session_unregister("BAB_SESS_HASHID");
		session_destroy();
		}

	setcookie('c_nickname'," ");
	setcookie('c_password'," ");

	Header("Location: ". $GLOBALS['babPhpSelf']);
	}

function userCreate($firstname, $middlename, $lastname, $nickname, $email)
	{
	global $babBody;
	class temp
		{
		var $firstname;
		var $middlename;
		var $lastname;
		var $nickname;
		var $email;
		var $password;
		var $repassword;
		var $adduser;
		var $firstnameval;
		var $middlenameval;
		var $lastnameval;
		var $nicknameval;
		var $emailval;
		var $infotxt;

		function temp($firstname, $middlename, $lastname, $nickname, $email)
			{
			$this->firstnameval = $firstname != ""? $firstname: "";
			$this->middlenameval = $middlename != ""? $middlename: "";
			$this->lastnameval = $lastname != ""? $lastname: "";
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->emailval = $email != ""? $email: "";
			$this->firstname = bab_translate("First Name");
			$this->middlename = bab_translate("Middle Name");
			$this->lastname = bab_translate("Last Name");
			$this->nickname = bab_translate("Nickname");
			$this->email = bab_translate("Email");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->adduser = bab_translate("Register");
			$this->infotxt = bab_translate("Please provide a valid email.") . "<br>";
			$this->infotxt .= bab_translate("We will send you an email for confirmation before you can use our services") . "<br>";
			}
		}

	$temp = new temp($firstname, $middlename, $lastname, $nickname, $email);
	$babBody->babecho(	bab_printTemplate($temp,"login.html", "usercreate"));
	}

function signOn( $nickname, $password,$lifetime)
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

	// ajout cookie
	if ( $lifetime > 0 )
		{
		setcookie('c_nickname',$nickname,time()+$lifetime);
		setcookie('c_password',md5($password),time()+$lifetime);
		}
	return true;
	}

function sendPassword ($nickname)
	{
	global $babBody, $BAB_HASH_VAR, $babAdminEmail;

	if (!empty($nickname))
		{
		$req="select * from ".BAB_USERS_TBL." where nickname='$nickname'";
		$db = $GLOBALS['babDB'];
		$res = $db->db_query($req);
		if (!$res || $db->db_num_rows($res) < 1)
			{
			$babBody->msgerror = bab_translate("Incorrect nickname");
			return false;
			}
		else
			{
			$arr = $db->db_fetch_array($res);
			$new_pass=strtolower(random_password(8));

			//update the database to include the new password
			$req="update ".BAB_USERS_TBL." set password='". md5($new_pass) ."' where nickname='$nickname'";
			$res=$db->db_query($req);

			//send a simple email with the new password
			notifyUserPassword($new_pass, $arr['email']);
			$babBody->msgerror = bab_translate("Your new password has been emailed to you.") ." &lt;".$arr['email']."&gt;";
			return true;
			}
		}
	else
		{
		$babBody->msgerror = bab_translate("ERROR - Nickname is required");
		return false;
		}
}

function userLogin($nickname,$password)
	{
	global $babBody;
	$password=strtolower($password);
	$sql="select * from ".BAB_USERS_TBL." where nickname='$nickname' and password='". md5($password) ."'";
	$db = $GLOBALS['babDB'];
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$babBody->msgerror = bab_translate("User not found or password incorrect");
		return false;
		} 
	else 
		{
		$arr = $db->db_fetch_array($result);
		if( $arr['disabled'] == '1')
			{
			$babBody->msgerror = bab_translate("Sorry, your account is disabled. Please contact your adminsitrator");
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
				$_SESSION['BAB_SESS_GROUPID'] = bab_getPrimaryGroupId($arr['id']);
				$_SESSION['BAB_SESS_GROUPNAME'] = bab_getGroupName($_SESSION['BAB_SESS_GROUPID']);
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
				$GLOBALS['BAB_SESS_GROUPID']  = bab_getPrimaryGroupId($arr['id']);
				$GLOBALS['BAB_SESS_GROUPNAME'] = bab_getGroupName($GLOBALS['BAB_SESS_GROUPID']);
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
	
function confirmUser($hash, $nickname)
	{
	global $BAB_HASH_VAR, $babBody;
	$new_hash=md5($nickname.$BAB_HASH_VAR);
	if ($new_hash && ($new_hash==$hash))
		{
		$sql="select * from ".BAB_USERS_TBL." where confirm_hash='$hash'";
		$db = $GLOBALS['babDB'];
		$result=$db->db_query($sql);
		if( $db->db_num_rows($result) < 1)
			{
			$babBody->msgerror = bab_translate("User Not Found") ." !";
			return false;
			}
		else
			{
			$arr = $db->db_fetch_array($result);
			$babBody->msgerror = bab_translate("User Account Updated - You can now log to our site");
			$sql="update ".BAB_USERS_TBL." set is_confirmed='1', datelog=now(), lastlog=now()  WHERE id='".$arr['id']."'";
			$db->db_query($sql);
			$arr2 = $db->db_fetch_array($db->db_query("select idgroup from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'"));
			if( $arr2['idgroup'] != 0)
				{
				$res = $db->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$arr['id']."' and id_group='".$arr2['idgroup']."'");
				if( !$res || $db->db_num_rows($res) < 1)
					{
					$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_group, id_object) VALUES ('" .$arr2['idgroup']. "', '" . $arr['id']. "')");
					}
				}
			return true;
			}
		}
	else
		{
		$babBody->msgerror = bab_translate("Update failed");
		return false;
		}

	}
	
function addNewUser( $firstname, $middlename, $lastname, $nickname, $email, $password1, $password2)
	{
	global $babBody, $babDB;
	if( empty($nickname) || empty($email) || empty($firstname) || empty($lastname) || empty($password1) || empty($password2))
		{
		$babBody->msgerror = bab_translate( "You must complete all fields !!");
		return false;
		}
	if( $password1 != $password2)
		{
		$babBody->msgerror = bab_translate("Passwords not match !!");
		return;
		}
	if ( strlen($password1) < 6 )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return false;
		}

	if ( strpos($nickname, ' ') !== false )
		{
		$babBody->msgerror = bab_translate("Nickname contains blanc characters");
		return false;
		}

	if ( !bab_isEmailValid($email))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
		return false;
		}

	$iduser = registerUser($firstname, $lastname, $middlename, $email,$nickname, $password1, $password2, false);
	if( $iduser == false )
		return false;

	return true;
	}

/* main */

$db = $GLOBALS['babDB'];
$res=$db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'");
if( $res && $db->db_num_rows($res) > 0 )
{
	$r = $db->db_fetch_array($res);
}

// ajout cookie
if (!isset($lifetime))
	{
	$lifetime = 0;
	}

if( isset($login) && $login == "login")
	{
	if(!signOn($nickname, $password, $lifetime))
		$idx = 'signon';
	else
		{
		$url = urldecode($referer);
		if (substr_count($url,$GLOBALS['babUrlScript']) == 1 && substr_count($url,'tg=login&cmd=signon') == 0)
			Header("Location: ". $url);
		else
			Header("Location: ". $GLOBALS['babUrlScript']);
		}
	}
else if( isset($adduser) && $adduser == "register" && $r['registration'] == 'Y')
	{
	if( !addNewUser( $firstname, $middlename, $lastname, $nickname, $email, $password1, $password2))
		$cmd = "register";
	}
else if( isset($sendpassword) && $sendpassword == "send")
	{
	sendPassword($nickname);
	}

if ($cmd == "emailpwd" && !$GLOBALS['babEmailPassword'])
	{
	$babBody->msgerror = bab_translate("Acces denied");
	$cmd = "signon";
	}

if ($cmd == "detect" && $GLOBALS['BAB_SESS_LOGGED'])
	header( "location:".$referer );

switch($cmd)
	{
	case "confirm":
		confirmUser( $hash, $name );
		break;

	case "signoff":
		signOff();
		break;

	case "register":
		$babBody->title = bab_translate("Register");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $r['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if ($GLOBALS['babEmailPassword'] ) 
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		userCreate($firstname, $middlename, $lastname, $nickname, $email);
		break;

	case "emailpwd":
		$babBody->title = bab_translate("Email a new password");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $r['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if ($GLOBALS['babEmailPassword'] ) 
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		emailPassword();
		break;

	case "signon":
	default:
		$babBody->title = bab_translate("Login");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $r['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if ($GLOBALS['babEmailPassword'] ) 
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		if (!$referer) $referer = urlencode($HTTP_REFERER);
			displayLogin($referer);
		break;
	}
$babBody->setCurrentItemMenu($cmd);
?>