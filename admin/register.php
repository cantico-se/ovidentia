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
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	
	$message = bab_translate("Thank You For Registering at our site");
	$message .= "<br>". bab_translate("To confirm your registration");
	$message .= ", ". bab_translate("simply follow this").": ";

	$tempa = new tempa($link, $name, $message);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userregistration");

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
			$sql = "select email, firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_object']."'";
			$res=$db->db_query($sql);
			$r = $db->db_fetch_array($res);
			$mail->mailTo($r['email'], bab_composeUserName($r['firstname'] , $r['lastname']));
			}
		}
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("Registration Confirmation"));

	$tempb = new tempb($name, $useremail, $warning);
	$message = bab_printTemplate($tempb,"mailinfo.html", "adminregistration");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempb,"mailinfo.html", "adminregistrationtxt");
    $mail->mailAltBody($message);
    $mail->send();
	}

function addUser( $firstname, $lastname, $nickname, $email, $password1, $password2, $badmin)
	{
	global $babBody;
	if( empty($firstname) || empty($lastname) || empty($email) || empty($password1) || empty($password2))
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

	if ( !bab_isEmailValid($email))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
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

	$hash = md5(strtolower(strtr($firstname.$lastname, $replace)));
	$query = "select * from ".BAB_USERS_TBL." where hashname='".$hash."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Firstname and Lastname already exists !!");
		return false;
		}
	if(!registerUser($nickname, $firstname, $lastname, $email, $password1, $password2, $hash, $badmin))
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


function registerUser( $nickname, $firstname, $lastname, $email, $password1, $password2, $hashname, $badmin)
	{
	global $BAB_HASH_VAR, $babBody, $babUrl, $babAdminEmail, $babSiteName, $babLanguage;
	$password1=strtolower($password1);
	$hash=md5($nickname.$BAB_HASH_VAR);
	if( $badmin )
		$isconfirmed = 1;
	else
		$isconfirmed = 0;

	$sql="insert into ".BAB_USERS_TBL." (nickname, firstname, lastname, hashname, password,email,date,confirm_hash,is_confirmed,changepwd,lang) ".
		"values ('";
	if( !bab_isMagicQuotesGpcOn())
		$sql .= addslashes($nickname)."','".addslashes($firstname)."','".addslashes($lastname);
	else
		$sql .= $nickname."','".$firstname."','".$lastname;
	$sql .= "','".$hashname."','". md5($password1) ."','$email', now(),'$hash','".$isconfirmed."','1','$babLanguage')";
	$db = $GLOBALS['babDB'];
	$result=$db->db_query($sql);
	if ($result)
		{
		$id = $db->db_insert_id();
		$sql = "insert into ".BAB_CALENDAR_TBL." (owner, type) values ('$id', '1')";
		$result=$db->db_query($sql);

		if( !$badmin )
			{
		$result=$db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'");
		if( $result && $db->db_num_rows($result) > 0 )
			{
			$r = $db->db_fetch_array($result);
			}

		$babBody->msgerror = bab_translate("Thank You For Registering at our site") ."<br>";
		$babBody->msgerror .= bab_translate("You will receive an email which let you confirm your registration.");
		$link = $GLOBALS['babUrlScript']."?tg=register&cmd=confirm&hash=$hash&name=". urlencode($nickname);
		//mail ($email,bab_translate("Registration Confirmation"),$message,"From: \"".$babAdminEmail."\" \nContent-Type:text/html;charset=iso-8859-1\n");
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
		//$babBody->msgerror = $msg;
			}
		bab_callAddonsFunction('onUserCreate', $id);
		return true;
		}
	else
		return false;
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
		/*
		if( bab_isUserAlreadyLogged($arr['id']))
			{
			$babBody->msgerror = bab_translate("Sorry, this account is already used elsewhere");
			return false;
			}
		*/
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
				$_SESSION['BAB_SESS_EMAIL'] = $arr['email'];
				$_SESSION['BAB_SESS_USERID'] = $arr['id'];
				$_SESSION['BAB_SESS_HASHID'] = $arr['confirm_hash'];
				$GLOBALS['BAB_SESS_NICKNAME'] = $_SESSION['BAB_SESS_NICKNAME'];
				$GLOBALS['BAB_SESS_USER'] = $_SESSION['BAB_SESS_USER'];
				$GLOBALS['BAB_SESS_EMAIL'] = $_SESSION['BAB_SESS_EMAIL'];
				$GLOBALS['BAB_SESS_USERID'] = $_SESSION['BAB_SESS_USERID'];
				$GLOBALS['BAB_SESS_HASHID'] = $_SESSION['BAB_SESS_HASHID'];
				}
			else
				{
				$GLOBALS['BAB_SESS_NICKNAME'] = $arr['nickname'];
				$GLOBALS['BAB_SESS_USER'] = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$GLOBALS['BAB_SESS_EMAIL'] = $arr['email'];
				$GLOBALS['BAB_SESS_USERID'] = $arr['id'];
				$GLOBALS['BAB_SESS_HASHID'] = $arr['confirm_hash'];
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

function userChangePassword($oldpwd, $newpwd)
	{
	global $babBody, $BAB_SESS_USERID, $BAB_SESS_HASHID;

	$new_password1=strtolower($newpwd);
	$sql="select * from ".BAB_USERS_TBL." where id='". $BAB_SESS_USERID ."'";
	$db = $GLOBALS['babDB'];
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$babBody->msgerror = bab_translate("User not found or bad password");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($result);
		$oldpwd2 = md5(strtolower($oldpwd));
		if( $oldpwd2 == $arr['password'])
			{
			$sql="update ".BAB_USERS_TBL." set password='". md5(strtolower($newpwd)). "' ".
				"where id='". $BAB_SESS_USERID . "'";
			$result=$db->db_query($sql);
			if ($db->db_affected_rows() < 1)
				{
				$babBody->msgerror = bab_translate("Nothing Changed");
				return false;
				}
			else
				{
				$babBody->msgerror = bab_translate("Password Changed");
				return true;
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("ERROR: Old password incorrect !!");
			return false;
			}
		}
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
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject("Ovidentia: ". bab_translate("Password Reset"));

	$tempa = new tempa($passw);
	$message = bab_printTemplate($tempa,"mailinfo.html", "sendpassword");
    $mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "sendpasswordtxt");
    $mail->mailAltBody($message);

	$mail->send();
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

if(isset($cmd) && $cmd == "confirm")
	confirmUser( $hash, $name);
?>
