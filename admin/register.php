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


/**
 * Updates the specified user's password
 * 
 * @param int		$userId						The user id		
 * @param string	$newPassword				The new user password
 * @param string	$newPassword2				The new user password (copy : used when we created 2 input fields in a form to confirm the password)
 * @param bool		$ignoreAccessRights			false (value by default) if you want to verify if the current user can update the account (superadmin...)
 * @param bool		$ignoreSixCharactersMinimum	false (value by default) if you want to verify if the password have at least 6 characters
 * @param string	&$error						Error message
 * 
 * @return bool		true on success, false on error
 */
function updateUserPasswordById($userId, $newPassword, $newPassword2, $ignoreAccessRights=false, $ignoreSixCharactersMinimum=false, &$error)
{
	global $babBody, $babDB, $BAB_HASH_VAR;
	
	/* Test rights */
	if (!$ignoreAccessRights) {
		$res = bab_canCurrentUserUpdateUser($userId);
		if (!$res) {
			$error = bab_translate("You don't have access to update the user");
			return false;
		}
	}
	
	/* Delete spaces in passwords */
	$newPassword = trim($newPassword);
	$newPassword2 = trim($newPassword2);
	
	/* Test if passwords are same */
	if ($newPassword != $newPassword2) {
		$error = bab_translate("Passwords not match !!");
		return false;
	}
	
	$minPasswordLengh = 6;
	if(ISSET($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])){
		$minPasswordLengh = $GLOBALS['babMinPasswordLength'];
		if($minPasswordLengh < 1){
			$minPasswordLengh = 1;
		}
	}
	
	/* Test if the password have at least $GLOBALS['babMinPasswordLength'] or 6 characters */
	if (!$ignoreSixCharactersMinimum) {
		if (mb_strlen($newPassword) < $minPasswordLengh) {
			$error = sprintf(bab_translate("Password must be at least %s characters !!"),$minPasswordLengh);
			return false;
		}
	}

	/* Verify the authentification mode of the user */
	$sql = 'SELECT nickname, db_authentification FROM ' . BAB_USERS_TBL . ' WHERE id=' . $babDB->quote($userId);
	list($nickname, $dbauth) = $babDB->db_fetch_row($babDB->db_query($sql));

	$authentification = $babBody->babsite['authentification'];
	if ($dbauth == 'Y') {
		$authentification = ''; // force to default
	}

	switch ($authentification)
	{
		case BAB_AUTHENTIFICATION_AD: // Active Directory
			$error = bab_translate("Nothing Changed !!");
			return false;
			break;

		case BAB_AUTHENTIFICATION_LDAP: // Active Directory
			if (!empty($babBody->babsite['ldap_encryptiontype'])) {
				include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
				$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
				$ret = $ldap->connect();
				if ($ret === false) {
					$error = bab_translate("LDAP connection failed");
					return false;
				}

				$ret = $ldap->bind($babBody->babsite['ldap_admindn'], $babBody->babsite['ldap_adminpassword']);
				if (!$ret) {
					$ldap->close();
					$error = bab_translate("LDAP bind failed");
					return  false;
				}

				if (isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter'])) {
					$filter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
					$filter = str_replace('%NICKNAME', ldap_escapefilter($nickname), $filter);
				} else {
					$filter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($nickname)."))";
				}

				$attributes = array("dn", $babBody->babsite['ldap_attribute'], "cn");
				$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);

				if ($entries === false) {
					$ldap->close();
					$error = bab_translate("LDAP search failed");
					return false;
				}

				$ldappw = ldap_encrypt($newPassword, $babBody->babsite['ldap_encryptiontype']);
				$ret = $ldap->modify($entries[0]['dn'], array('userPassword'=>$ldappw));
				$ldap->close();
				if (!$ret) {
					$error = bab_translate("Nothing Changed");
					return false;
				}
			}
			break;

		default:
			break;
	}

	/* Update the user's password */
	$sql = 'UPDATE ' . BAB_USERS_TBL . ' SET password=' . $babDB->quote(md5(mb_strtolower($newPassword))) . ' WHERE id=' . $babDB->quote($userId);
	$babDB->db_query($sql);

	/* Call the functionnality event onUserChangePassword */
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunctionArray('onUserChangePassword',
		array(
			'id' => $userId, 
			'nickname' => $nickname, 
			'password' => $newPassword, 
			'error' => &$error
		)
	);

	return true;
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
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

	$users = aclGetAccessUsers(BAB_LDAP_LOGGIN_NOTIFY_GROUPS_TBL, 1);
	
	foreach($users as $user){
		$mail->$mailBCT($user['email'], $user['name']);
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
	while( mb_strlen($str) < $length)
		{
		$str .= mb_substr($possible, mt_rand(0, mb_strlen($possible) - 1), 1);
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
		$babBody->msgerror = bab_translate( "Login ID is required");
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
	
	$minPasswordLengh = 6;
	if(ISSET($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])){
		$minPasswordLengh = $GLOBALS['babMinPasswordLength'];
		if($minPasswordLengh < 1){
			$minPasswordLengh = 1;
		}
	}
	if ( mb_strlen($password1) < $minPasswordLengh )
		{
		$babBody->msgerror = sprintf(bab_translate("Password must be at least %s characters !!"),$minPasswordLengh);
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
	
	
class bab_notifyAdminUserRegistrationCls
	{
    public $sitename;
    public $linkurl;
    public $linkname;
	public $username;
	public $message;


	public function __construct($name, $msg)
		{
        global $babSiteName;
        $this->linkname = bab_translate("link");
        $this->username = $name;
		$this->sitename = $babSiteName;
		$this->message = $msg;
		}
	}

	
/**
 * Send notification for registration
 * @param unknown_type $name
 * @param unknown_type $email
 * @param unknown_type $nickname
 * @param unknown_type $pwd
 * @return unknown_type
 */
function notifyAdminUserRegistration($name, $email, $nickname, $pwd)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	$mail = bab_mail();
	if( $mail == false )
		return;
    $mail->mailTo($email, $name);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	
	$message = bab_translate("You have been registered on our site") ."<br>";
	$message .= bab_translate("Login ID") .": ". $nickname;
	if( !empty($pwd))
		{
		$message .= " / ". bab_translate("Password") .": ". $pwd;
		}

	$tempa = new bab_notifyAdminUserRegistrationCls($name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "userregistration2"));

    $mail->mailBody($message, "html");

	$message = bab_translate("You have been registered on our site")."\n";
	$message .= bab_translate("Nickname") .": ". $nickname;
	if( !empty($pwd))
		{
		$message .= " / ". bab_translate("Password") .": ". $pwd;
		}

	$tempa = new bab_notifyAdminUserRegistrationCls($name, $message);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userregistrationtxt2");

	$mail->mailAltBody($message);
    return $mail->send();
	}
	
	
	
function destroyAuthCookie() {

	if ( $GLOBALS['babCookieIdent'] != 'login' ) {
		setcookie('c_nickname','');
	}
	setcookie('c_password','');
}


function sendPassword($nickname, $email)
	{
	global $babBody, $babDB, $BAB_HASH_VAR, $babAdminEmail;

	if (!empty($nickname))
		{
		$req="select id, nickname, email, changepwd from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."' AND email=".$babDB->quote($email);
		$res = $babDB->db_query($req);
		if (!$res || $babDB->db_num_rows($res) < 1)
			{
			$babBody->msgerror = bab_translate("Incorrect login ID or email");
			return false;
			}
		else
			{
			$arr = $babDB->db_fetch_array($res);
			
			if( $arr['changepwd'] != 1)
			{
				$babBody->msgerror = bab_translate("Sorry, You cannot change your password. Please contact administrator");
				return false;
			}
			$new_pass=mb_strtolower(random_password(8));

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
			notifyUserPassword($new_pass, $arr['email'], $arr['nickname']);
			$babBody->addError(bab_translate("Your new password has been emailed to you.") ." <".$arr['email'].">");
			$error = '';
			
			include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
			bab_callAddonsFunctionArray('onUserChangePassword', 
				array(
					'id'		=> $arr['id'], 
					'nickname'	=> $arr['nickname'], 
					'password'	=> $new_pass, 
					'error'		=> &$error
				)
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
		$babBody->msgerror = bab_translate("ERROR - Login ID and email are required");
		return false;
		}
}

