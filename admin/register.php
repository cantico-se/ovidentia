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
include $babInstallPath."utilit/mailincl.php";

function notifyUserRegistration($link, $name, $email)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
        var $linkname;
		var $username;
		var $message;


		function tempa($link, $name, $msg)
			{
            global $babSiteName;
            $this->linkurl = $link;
            $this->linkname = bab_translate("link");
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = $msg;
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;
    $mail->mailTo($email, $name);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	
	$message = bab_translate("Thank You For Registering at our site");
	$message .= "<br>". bab_translate("To confirm your registration");
	$message .= ", ". bab_translate("simply follow this").": ";

	$tempa = new tempa($link, $name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "userregistration"));

    $mail->mailBody($message, "html");

	$message = bab_translate("Thank You For Registering at our site")."\n";
	$message .= bab_translate("To confirm your registration")."\n";
	$message .= bab_translate("go to this url").":\n";

	$tempa = new tempa($link, $name, $message);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userregistrationtxt");

	$mail->mailAltBody($message);
    $mail->send();
	}

function notifyAdminRegistration($name, $useremail, $warning)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempb
		{
        var $sitename;
		var $username;
		var $message;
		var $email;
		var $warning;


		function tempb($name, $useremail, $warning)
			{
            global $babSiteName;
            $this->email = $useremail;
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->warning = $warning;
			$this->message = bab_translate("Your site recorded a new registration on behalf of");
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$db = $GLOBALS['babDB'];
	$sql = "select * from ".BAB_USERS_GROUPS_TBL." where id_group='3'";
	$result=$db->db_query($sql);
	if( $result && $db->db_num_rows($result) > 0 )
		{
		while( $arr = $db->db_fetch_array($result))
			{
			$sql = "select email, firstname, lastname, disabled from ".BAB_USERS_TBL." where id='".$arr['id_object']."'";
			$res=$db->db_query($sql);
			$r = $db->db_fetch_array($res);
			if( $r['disabled'] != 1 )
				$mail->mailBcc($r['email'], bab_composeUserName($r['firstname'] , $r['lastname']));
			}
		}
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Registration Confirmation"));

	$tempb = new tempb($name, $useremail, $warning);
	$message = $mail->mailTemplate(bab_printTemplate($tempb,"mailinfo.html", "adminregistration"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempb,"mailinfo.html", "adminregistrationtxt");
    $mail->mailAltBody($message);
    $mail->send();
	}

function addUser( $nickname, $password1, $password2, $badmin)
	{
	global $babBody;
	if( empty($password1) || empty($password2))
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

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_USERS_TBL." where nickname='".$nickname."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This nickname already exists !!");
		return false;
		}
/*
	$replace = array( " " => "", "-" => "");

	$hash = md5(strtolower(strtr($firstname.$lastname, $replace)));
	$query = "select * from ".BAB_USERS_TBL." where hashname='".$hash."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Firstname and Lastname already exists !!");
		return false;
		}
*/

	if(!registerUser($nickname, $password1, $password2, $badmin))
		return false;

	return true;
	}

/* generate a random password given a len */
function random_password($length)
	{
	mt_srand((double)microtime() * 1000000);
	$possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$str = "";
	while( strlen($str) < $length)
		{
		$str .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
		}
	return $str;
	}


function registerUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $badmin)
	{
	global $BAB_HASH_VAR, $babBody, $babUrl, $babAdminEmail, $babSiteName, $babLanguage;

	if( empty($firstname) && empty($lastname))
		{
		$babBody->msgerror = bab_translate( "You must complete all fields !!");
		return false;
		}

	if ( empty($nickname) || strpos($nickname, ' ') !== false )
		{
		$babBody->msgerror = bab_translate("Nickname contains blanc characters");
		return false;
		}

	if( empty($password1) || empty($password2))
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

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_USERS_TBL." where nickname='".$nickname."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This nickname already exists !!");
		return false;
		}
	

	$replace = array( " " => "", "-" => "");

	$hashname = md5(strtolower(strtr($firstname.$middlename.$lastname, $replace)));
	$query = "select * from ".BAB_USERS_TBL." where hashname='".$hashname."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Firstname and Lastname already exists !!");
		return false;
		}

	$password1=strtolower($password1);
	$hash=md5($nickname.$BAB_HASH_VAR);
	if( $badmin )
		$isconfirmed = 1;
	else
		$isconfirmed = 0;

	$db = $GLOBALS['babDB'];

	$sql="insert into ".BAB_USERS_TBL." (nickname, firstname, lastname, hashname, password,email,date,confirm_hash,is_confirmed,changepwd,lang, langfilter, datelog, lastlog) ".
		"values ('";
	if( !bab_isMagicQuotesGpcOn())
		$sql .= addslashes($nickname)."','".addslashes($firstname)."','".addslashes($lastname);
	else
		$sql .= $nickname."','".$firstname."','".$lastname;
	$sql .= "','".$hashname."','". md5($password1) ."','$email', now(),'$hash','".$isconfirmed."','1','$babLanguage'";
	$sql .= ",'".$GLOBALS['babLangFilter']->getFilterAsInt()."', now(), now())";
	$result=$db->db_query($sql);
	if ($result)
		{
		$id = $db->db_insert_id();
		$db->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('$id', '1')");
		$db->db_query("insert into ".BAB_DBDIR_ENTRIES_TBL." (givenname, mn, sn, email, id_directory, id_user) values ('".addslashes($firstname)."', '".addslashes($middlename)."', '".addslashes($lastname)."', '".$email."', '0', '".$id."')");

		if( !$badmin )
			{
			$result=$db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'");
			if( $result && $db->db_num_rows($result) > 0 )
				{
				$r = $db->db_fetch_array($result);
				}

			$babBody->msgerror = bab_translate("Thank You For Registering at our site") ."<br>";
			$babBody->msgerror .= bab_translate("You will receive an email which let you confirm your registration.");
			$link = $GLOBALS['babUrlScript']."?tg=login&cmd=confirm&hash=$hash&name=". urlencode($nickname);
			$fullname = bab_composeUserName($firstname , $lastname);
			if( $r['email_confirm'] == 'Y')
				{
				notifyUserRegistration($link, $fullname, $email);
				$warning = "";
				}
			else
				{
				$warning = "( ". bab_translate("To let user log on your site, you must confirm his registration")." )";
				}
			notifyAdminRegistration($fullname, $email, $warning);
			}
		bab_callAddonsFunction('onUserCreate', $id);
		return $id;
		}
	else
		return false;
	}

function notifyUserPassword($passw, $email)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
        var $linkname;
		var $username;
		var $message;


		function tempa($passw)
			{
            global $babSiteName;
			$this->sitename = bab_translate("On site").": ". $babSiteName."( <a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babUrl']."</a> )";
			$this->message = bab_translate("Your password has been reset to").": ". $passw;
			}
		}
	
	$mail = bab_mail();
	if( $mail == false )
		return;
	
    $mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject("Ovidentia: ". bab_translate("Password Reset"));

	$tempa = new tempa($passw);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "sendpassword"));
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "sendpasswordtxt");
    $mail->mailAltBody($message);

	$mail->send();
	}

function notifyAdminUserRegistration($name, $email, $nickname, $pwd)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
        var $linkname;
		var $username;
		var $message;


		function tempa($name, $msg)
			{
            global $babSiteName;
            $this->linkurl = $link;
            $this->linkname = bab_translate("link");
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = $msg;
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;
    $mail->mailTo($email, $name);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	
	$message = bab_translate("You have been registered on our site") ."<br>";
	$message .= bab_translate("Nickname") .": ". $nickname;
	if( !empty($pwd))
		{
		$message .= " / ". bab_translate("Password") .": ". $pwd;
		}

	$tempa = new tempa($name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "userregistration2"));

    $mail->mailBody($message, "html");

	$message = bab_translate("You have been registered on our site")."\n";
	$message .= bab_translate("Nickname") .": ". $nickname;
	if( !empty($pwd))
		{
		$message .= " / ". bab_translate("Password") .": ". $pwd;
		}

	$tempa = new tempa($name, $message);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userregistrationtxt2");

	$mail->mailAltBody($message);
    $mail->send();
	}

?>
