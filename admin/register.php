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
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/mailincl.php';

function auth_decode($str)
{
	global $babBody;
	return bab_ldapDecode($str, $babBody->babsite['ldap_decoding_type']);
}


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
			
		function toHtml() {
			$this->linkurl = bab_toHtml($this->linkurl);
            $this->username = bab_toHtml($this->username);
			$this->sitename = bab_toHtml($this->sitename);
			$this->message = bab_toHtml($this->message);
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return false;
    $mail->mailTo($email, $name);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	
	$message = bab_translate("Thank You For Registering at our site");
	$message .= "\n". bab_translate("To confirm your registration");
	$message .= ", ". bab_translate("simply follow this").": ";

	$tempa = new tempa($link, $name, $message);
	$tempa->toHtml();
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
	global $babBody, $babDB, $babAdminEmail, $babInstallPath;

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
			
		function toHtml() {
			$this->email = bab_toHtml($this->email);
            $this->username = bab_toHtml($this->username);
			$this->sitename = bab_toHtml($this->sitename);
			$this->warning = bab_toHtml($this->warning);
			}
		}
	
    $mail = bab_mail();
	if( $mail == false )
		return;

	$sql = "select * from ".BAB_USERS_GROUPS_TBL." where id_group='3'";
	$result=$babDB->db_query($sql);
	if( $result && $babDB->db_num_rows($result) > 0 )
		{
		while( $arr = $babDB->db_fetch_array($result))
			{
			$sql = "select email, firstname, lastname, disabled from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($arr['id_object'])."'";
			$res=$babDB->db_query($sql);
			$r = $babDB->db_fetch_array($res);
			if( $r['disabled'] != 1 )
				$mail->mailBcc($r['email'], bab_composeUserName($r['firstname'] , $r['lastname']));
			}
		}
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Inscription notification"));

	$tempb = new tempb($name, $useremail, $warning);
	$tempb->toHtml();
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
	global $babBody, $BAB_HASH_VAR, $babUrl, $babAdminEmail, $babSiteName;

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

	if( empty($email) )
		{
		$babBody->msgerror = bab_translate( "Email is required");
		return false;
		}

	if( empty($nickname) )
		{
		$babBody->msgerror = bab_translate( "Nickname is required");
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
		return false;
		}

	if ( strlen($password1) < 6 )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return false;
		}

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

	$id = bab_addUser($firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $isconfirmed, $babBody->msgerror);

	if( $id === false )
		{
		return false;
		}

	if( !$badmin )
		{
		$babBody->msgerror = bab_translate("Thank You For Registering at our site") ."<br />";
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
			$hash=md5($nickname.$BAB_HASH_VAR);
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

	return $id;
	}

function notifyUserPassword($passw, $email, $nickname='')
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
        var $linkname;
		var $username;
		var $message;


		function tempa($passw, $email, $nickname='')
			{
            global $babSiteName;
			$this->message = bab_translate('On site').' '.$babSiteName.' (<a href="'.$GLOBALS['babUrl'].'">'.$GLOBALS['babUrl'].'</a>)<br />';
			$this->message .= bab_translate('The password of the account the identifier of which is').' <b>'.$nickname."</b> ";
			$this->message .= bab_translate('was reset in').' <b>'.$passw.'</b><br />';
			}
		}
	
	$mail = bab_mail();
	if( $mail == false )
		return;
	
    $mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Your password has been reset"));

	$tempa = new tempa($passw, $email, $nickname);
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
	
	
	
function destroyAuthCookie() {

	if ( $GLOBALS['babCookieIdent'] != 'login' ) {
		setcookie('c_nickname','');
	}
	setcookie('c_password','');
}


function sendPassword ($nickname)
	{
	global $babBody, $babDB, $BAB_HASH_VAR, $babAdminEmail;

	if (!empty($nickname))
		{
		$req="select id, email from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."'";
		$res = $babDB->db_query($req);
		if (!$res || $babDB->db_num_rows($res) < 1)
			{
			$babBody->msgerror = bab_translate("Incorrect nickname");
			return false;
			}
		else
			{
			$arr = $babDB->db_fetch_array($res);
			$new_pass=strtolower(random_password(8));

			switch($babBody->babsite['authentification'])
				{
				case BAB_AUTHENTIFICATION_AD: // Active Directory
					$babBody->msgerror = bab_translate("Cannot reset password !!");
					return false;
					break;
				case BAB_AUTHENTIFICATION_LDAP: // Active Directory
					if( !empty($babBody->babsite['ldap_encryptiontype']))
						{
						include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
						$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
						$ret = $ldap->connect();
						if( $ret === false )
							{
							$babBody->msgerror = bab_translate("LDAP connection failed");
							return false;
							}

						$ret = $ldap->bind($babBody->babsite['ldap_admindn'], $babBody->babsite['ldap_adminpassword']);
						if( !$ret )
							{
							$ldap->close();
							$babBody->msgerror = bab_translate("LDAP bind failed");
							return  false;
							}
				
						if( isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
							{
							$filter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
							$filter = str_replace('%NICKNAME', $nickname, $filter);
							}
						else
							{
							$filter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($nickname)."))";
							}

						$attributes = array("dn", $babBody->babsite['ldap_attribute'], "cn");
						$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);

						if( $entries === false )
							{
							$ldap->close();
							$babBody->msgerror = bab_translate("LDAP search failed");
							return false;
							}

						$ldappw = ldap_encrypt($new_pass, $babBody->babsite['ldap_encryptiontype']);
						$ret = $ldap->modify($entries[0]['dn'], array('userPassword'=>$ldappw));
						$ldap->close();
						if( !$ret)
							{
							$babBody->msgerror = bab_translate("Nothing Changed");
							return false;
							}
						}
					break;
				default:
					break;
				}


			//update the database to include the new password
			$req="update ".BAB_USERS_TBL." set password='". $babDB->db_escape_string(md5($new_pass)) ."' where nickname='".$babDB->db_escape_string($nickname)."'";
			$res=$babDB->db_query($req);

			//send a simple email with the new password
			notifyUserPassword($new_pass, $arr['email'], $nickname);
			$babBody->addError(bab_translate("Your new password has been emailed to you.") ." <".$arr['email'].">");
			$error = '';
			
			include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
			bab_callAddonsFunctionArray('onUserChangePassword', array(
				'id'=>$arr['id'], 
				'nickname'=>$nickname, 
				'password'=>$new_pass, 
				'error'=>&$error)
			);
			
			if( !empty($error))
				{
				$babBody->addError($error);
				return false;
				}
			return true;
			}
		}
	else
		{
		$babBody->msgerror = bab_translate("ERROR - Nickname is required");
		return false;
		}
}


function userCanLogin($iIdUser)
{
	global $babBody, $babDB;

	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET cnx_try=cnx_try+1 WHERE sessid='".session_id()."'");
	list($cnx_try) = $babDB->db_fetch_array($babDB->db_query("SELECT cnx_try FROM ".BAB_USERS_LOG_TBL." WHERE sessid='".session_id()."'"));
	if($cnx_try > 5)
	{
		$msgerror = bab_translate("Maximum connexion attempts has been reached");
		return false;
	}
	
	require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';
	$aUser = bab_getUserById($iIdUser);
	if(!is_null($aUser))
	{
		if($aUser['disabled'] == '1')
		{
			$babBody->addError(bab_translate("Sorry, your account is disabled. Please contact your administrator"));
			return false;
		}
		else if($aUser['is_confirmed'] != '1')
		{
			$babBody->addError(bab_translate("Sorry - You haven't Confirmed Your Account Yet"));
			return false;
		}
		return true;
	}
	return false;
}


function userLogin($iIdUser)
{
	require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';
	$aUser = bab_getUserById($iIdUser);
	if(!is_null($aUser))
	{
		$_SESSION['BAB_SESS_NICKNAME']	= $aUser['nickname'];
		$_SESSION['BAB_SESS_USER']		= bab_composeUserName($aUser['firstname'], $aUser['lastname']);
		$_SESSION['BAB_SESS_FIRSTNAME'] = $aUser['firstname'];
		$_SESSION['BAB_SESS_LASTNAME']	= $aUser['lastname'];
		$_SESSION['BAB_SESS_EMAIL']		= $aUser['email'];
		$_SESSION['BAB_SESS_USERID']	= $aUser['id'];
		$_SESSION['BAB_SESS_HASHID']	= $aUser['confirm_hash'];
		$_SESSION['BAB_SESS_GROUPID']	= bab_getPrimaryGroupId($aUser['id']);
		$_SESSION['BAB_SESS_GROUPNAME']	= bab_getGroupName($_SESSION['BAB_SESS_GROUPID']);
		
		$GLOBALS['BAB_SESS_NICKNAME'] 	= $_SESSION['BAB_SESS_NICKNAME'];
		$GLOBALS['BAB_SESS_USER'] 		= $_SESSION['BAB_SESS_USER'];
		$GLOBALS['BAB_SESS_FIRSTNAME'] 	= $_SESSION['BAB_SESS_FIRSTNAME'];
		$GLOBALS['BAB_SESS_LASTNAME'] 	= $_SESSION['BAB_SESS_LASTNAME'];
		$GLOBALS['BAB_SESS_EMAIL'] 		= $_SESSION['BAB_SESS_EMAIL'];
		$GLOBALS['BAB_SESS_USERID'] 	= $_SESSION['BAB_SESS_USERID'];
		$GLOBALS['BAB_SESS_HASHID'] 	= $_SESSION['BAB_SESS_HASHID'];
	}
}


function authenticateUserByLoginPassword($sLogin, $sPassword)
{
	global $babBody;

	require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';
	$aUser = bab_getUserByLoginPassword($sLogin, $sPassword);
	if(!is_null($aUser))
	{
		return (int) $aUser['id'];
	}
	else 
	{
		$babBody->addError(bab_translate("User not found or password incorrect"));
	}
	return false;
}


function authenticateUserByCookie($sCookie)
{
	require_once $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php';

	$aUser = bab_getUserByCookie($sCookie);
	if(!is_null($aUser))
	{
		return (int) $aUser['id'];
	}
	return null;
}


function authenticateUserByLDAP($sLogin, $sPassword)
{
	global $babBody;
	
	include_once $GLOBALS['babInstallPath'] . 'utilit/ldap.php';
	$oLdap = new babLDAP($babBody->babsite['ldap_host'], '', false);
	if(false === $oLdap->connect())
	{
		$babBody->addError(bab_translate("LDAP connection failed. Please contact your administrator"));
		return null;
	}
	
	$aAttributes		= array('dn', 'modifyTimestamp', $babBody->babsite['ldap_attribute'], 'cn');
	$aUpdateAttributes	= array();
	$aExtraFieldId		= array();
	
	bab_getLdapExtraFieldIdAndUpdateAttributes($aAttributes, $aUpdateAttributes, $aExtraFieldId);
	
	$bLdapOk = true;
	$aEntries = array();
	
	//LDAP
	{
		if(isset($babBody->babsite['ldap_userdn']) && !empty($babBody->babsite['ldap_userdn']))
		{
			$sUserdn = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_userdn']);
			$sUserdn = str_replace('%NICKNAME', ldap_escapefilter($sLogin), $sUserdn);
			if(false === $ldap->bind($sUserdn, $sPassword))
			{
				$aError[] = bab_translate("LDAP bind failed. Please contact your administrator");
				$bLdapOk = false;
			}
			else
			{
				$aEntries = $oLdap->search($sUserdn, '(objectclass=*)', $aAttributes);
				if($aEntries === false || $aEntries['count'] == 0)
				{
					$babBody->addError(bab_translate("LDAP search failed"));
					$bLdapOk = false;
				}
			}
		}
		else
		{
			$sFilter = '';
			if(isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
			{
				$sFilter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
				$sFilter = str_replace('%NICKNAME', ldap_escapefilter($sLogin), $sFilter);
			}
			else
			{
				$sFilter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($sLogin)."))";
			}
			
			$aEntries = $oLdap->search($babBody->babsite['ldap_searchdn'], $sFilter, $aAttributes);

			if($aEntries !== false && $aEntries['count'] > 0 && isset($aEntries[0]['dn']))
			{
				if(isset($GLOBALS['babAdLdapOptions']))
				{
					for($k=0; $k < count($GLOBALS['babAdLdapOptions']); $k++)
					{						
						$oLdap->set_option($GLOBALS['babAdLdapOptions'][$k][0],$GLOBALS['babAdLdapOptions'][$k][1]);
					}
				}
				
				if(false === $oLdap->bind($aEntries[0]['dn'], $sPassword))
				{
					$babBody->addError(bab_translate("LDAP bind failed. Please contact your administrator"));
					$bLdapOk = false;
				}
			}
			else 
			{
				$bLdapOk = false;
			}
		}
	}
	
	
	
	$iIdUser = false;
	if(!isset($aEntries) || $aEntries === false)
	{
		$babBody->addError(bab_translate("LDAP authentification failed. Please verify your nickname and your password"));
		$bLdapOk = false;
	}
	
	$iIdUser = bab_registerUserIfNotExist($sLogin, $sPassword, $aEntries, $aUpdateAttributes);
	if(false === $iIdUser)
	{
		$oLdap->close();
		return null;
	}
	else 
	{
		if($aEntries['count'] > 0)
		{
			bab_ldapEntryToOvEntry($oLdap, $iIdUser, $sPassword, $aEntries, $aUpdateAttributes, $aExtraFieldId);
		}
	}
		
	$oLdap->close();
	
	if(false === $bLdapOk)
	{
		if($babBody->babsite['ldap_allowadmincnx'] == 'Y')
		{
			$bLdapOk = bab_haveAdministratorRight($iIdUser);
			if(false === $bLdapOk)
			{
				$babBody->addError(bab_translate("LDAP authentification failed. Please verify your nickname and your password"));
			}
		}
	}
	
	if(false !== $iIdUser)
	{
		$babBody->msgerror = '';
		return $iIdUser;
	}
	
	return null;
}


function authenticateUserByActiveDirectory($sLogin, $sPassword)
{
	global $babBody;
	
	include_once $GLOBALS['babInstallPath'] . 'utilit/ldap.php';
	$oLdap = new babLDAP($babBody->babsite['ldap_host'], '', false);
	if(false === $oLdap->connect())
	{
		$babBody->addError(bab_translate("LDAP connection failed. Please contact your administrator"));
		return null;
	}
	
	$aAttributes		= array('dn', 'modifyTimestamp', $babBody->babsite['ldap_attribute'], 'cn');
	$aUpdateAttributes	= array();
	$aExtraFieldId		= array();
	
	bab_getLdapExtraFieldIdAndUpdateAttributes($aAttributes, $aUpdateAttributes, $aExtraFieldId);
	
	$bLdapOk = true;
	$aEntries = array();
	
	//Active directory
	{
		if(isset($GLOBALS['babAdLdapOptions']))
		{
			for( $k=0; $k < count($GLOBALS['babAdLdapOptions']); $k++)
			{						
				$oLdap->set_option($GLOBALS['babAdLdapOptions'][$k][0],$GLOBALS['babAdLdapOptions'][$k][1]);
			}
		}

		if(false === $oLdap->bind($sLogin."@".$babBody->babsite['ldap_domainname'], $sPassword))
		{
			$babBody->addError(bab_translate("LDAP bind failed. Please contact your administrator"));
			$bLdapOk = false;
		}
		else
		{
			$sFilter = '';
			if(isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
			{
				$sFilter = str_replace('%NICKNAME', ldap_escapefilter($sLogin), $babBody->babsite['ldap_filter']);
			}
			else
			{
				$sFilter = "(|(samaccountname=".ldap_escapefilter($sLogin)."))";
			}
			$aEntries = $oLdap->search($babBody->babsite['ldap_searchdn'], $sFilter, $aAttributes);
		}
	}
	
	
	$iIdUser = false;
	if(!isset($aEntries) || $aEntries === false)
	{
		$babBody->addError(bab_translate("LDAP authentification failed. Please verify your nickname and your password"));
		$bLdapOk = false;
	}
	
	$iIdUser = bab_registerUserIfNotExist($sLogin, $sPassword, $aEntries, $aUpdateAttributes);
	if(false === $iIdUser)
	{
		$oLdap->close();
		return null;
	}
	else 
	{
		if($aEntries['count'] > 0)
		{
			bab_ldapEntryToOvEntry($oLdap, $iIdUser, $sPassword, $aEntries, $aUpdateAttributes, $aExtraFieldId);
		}
	}
		
	$oLdap->close();
	
	if(false === $bLdapOk)
	{
		if($babBody->babsite['ldap_allowadmincnx'] == 'Y')
		{
			$bLdapOk = bab_haveAdministratorRight($iIdUser);
			if(false === $bLdapOk)
			{
				$babBody->addError(bab_translate("LDAP authentification failed. Please verify your nickname and your password"));
			}
		}
	}
	
	if(false !== $iIdUser)
	{
		$babBody->msgerror = '';
		return $iIdUser;
	}
	
	return null;
}


function signOn()
{
	global $babBody;

	$sLogin		= (string) bab_pp('nickname');
	$sPassword	= (string) bab_pp('password');
	$iLifeTime	= (int) bab_pp('lifetime', 0);	
	
	if(!empty($sLogin) && !empty($sPassword))
	{
		$iIdUser = null;
		$iAuthenticationType = (int) $babBody->babsite['authentification'];	
		switch($iAuthenticationType)
		{
			case BAB_AUTHENTIFICATION_OVIDENTIA:
				$iIdUser = authenticateUserByLoginPassword($sLogin, $sPassword);
				break;
			case BAB_AUTHENTIFICATION_LDAP:
				$iIdUser = authenticateUserByLDAP($sLogin, $sPassword);
				break;
			case BAB_AUTHENTIFICATION_AD:
				$iIdUser = authenticateUserByActiveDirectory($sLogin, $sPassword);
				break;
		}
		
		if(!is_null($iIdUser) && userCanLogin($iIdUser))
		{
			userLogin($iIdUser);
			bab_logUserConnectionToStat($iIdUser);
			bab_updateUserConnectionDate($iIdUser);
			bab_createReversableUserPassword($iIdUser, $sPassword);
			bab_addUserCookie($iIdUser, $sLogin, $iLifeTime);
			return true;
		}
	}	
	else
	{
		$babBody->addError(bab_translate("You must complete all fields !!"));
	}
	return false;
}


function signOff()
{
	global $babBody, $babDB, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."' and sessid='".$babDB->db_escape_string(session_id())."'");
	
	$babDB->db_query("UPDATE ".BAB_USERS_TBL." SET  cookie_validity=NOW(), cookie_id='' WHERE id='".$babDB->db_escape_string($BAB_SESS_USERID)."'");



	unset($_SESSION['BAB_SESS_NICKNAME']);
	unset($_SESSION['BAB_SESS_USER']);
	unset($_SESSION['BAB_SESS_FIRSTNAME']);
	unset($_SESSION['BAB_SESS_LASTNAME']);
	unset($_SESSION['BAB_SESS_EMAIL']);
	unset($_SESSION['BAB_SESS_USERID']);
	unset($_SESSION['BAB_SESS_HASHID']);

	
	$GLOBALS['BAB_SESS_NICKNAME'] = "";
	$GLOBALS['BAB_SESS_USER'] = "";
	$GLOBALS['BAB_SESS_FIRSTNAME'] = "";
	$GLOBALS['BAB_SESS_LASTNAME'] = "";
	$GLOBALS['BAB_SESS_EMAIL'] = "";
	$GLOBALS['BAB_SESS_USERID'] ="";
	$GLOBALS['BAB_SESS_HASHID'] = "";


	// We destroy the session cookie. A new one will be created at the next session.
	if(isset($_COOKIE[session_name()])) 
	{
	   setcookie(session_name(), '', time()-42000, '/');
	}
	session_destroy();
	destroyAuthCookie();
}

?>