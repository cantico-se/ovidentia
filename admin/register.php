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
include_once $babInstallPath."utilit/mailincl.php";

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
		return false;
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

	$retry = 0;
	while ( true !== $ret = $mail->send() && $retry < 5 )
		{
		$retry++;
		}

	return $ret;
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

	if( empty($firstname) )
		{
		$babBody->msgerror = bab_translate( "Firstname is required");
		return false;
		}

	if( empty($firstname) && empty($lastname))
		{
		$babBody->msgerror = bab_translate( "Lastname is required");
		return false;
		}

	if( empty($password1) || empty($password2))
		{
		$babBody->msgerror = bab_translate( "Passwords not match !!");
		return false;
		}
	if( $password1 != $password2)
		{
		$babBody->msgerror = bab_translate("Passwords not match !!");
		return;
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
		{
		switch( $babBody->babsite['email_confirm'] )
			{
			case 1: // Don't validate adresse email
				$isconfirmed = 0;
				break;
			case 2: // Confirm account without address email validation
				$isconfirmed = 1;
				break;
			default: //Confirm account by validationg address email
				$isconfirmed = 0;
				break;
			}
		}

	$db = $GLOBALS['babDB'];

	$sql="insert into ".BAB_USERS_TBL." (nickname, firstname, lastname, hashname, password,email,date,confirm_hash,is_confirmed,changepwd,lang, langfilter, datelog, lastlog) ".
		"values ('";
	$sql .= addslashes($nickname)."','".addslashes($firstname)."','".addslashes($lastname);
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
			$babBody->msgerror = bab_translate("Thank You For Registering at our site") ."<br>";
			$fullname = bab_composeUserName($firstname , $lastname);
			if( $babBody->babsite['email_confirm'] == 2)
				{
				$warning = "( ". bab_translate("Account user is already confirmed")." )";
				}
			elseif( $babBody->babsite['email_confirm'] == 1 )
				{
				$warning = "( ". bab_translate("To let user log on your site, you must confirm his registration")." )";
				}
			else
				{
				$babBody->msgerror .= bab_translate("You will receive an email which let you confirm your registration.");
				$link = $GLOBALS['babUrlScript']."?tg=login&cmd=confirm&hash=$hash&name=". urlencode($nickname);
				$warning = "";
				if (!notifyUserRegistration($link, $fullname, $email))
					{
					$babBody->msgerror = bab_translate("ERROR: Email message can't be sent !!");
					$warning = "( ". bab_translate("The user has not received his confirmation email")." )";
					}				
				}
			notifyAdminRegistration($fullname, $email, $warning);
			}

		if( isset($babBody->babsite['idgroup']) && $babBody->babsite['idgroup'] != 0)
			{
			bab_addUserToGroup($id, $babBody->babsite['idgroup']);
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
    $mail->mailSubject(bab_translate("Password Reset"));

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
