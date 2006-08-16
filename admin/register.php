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
include_once $GLOBALS['babInstallPath']."utilit/mailincl.php";

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
    $mail->mailSubject(bab_translate("Inscription notification"));

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
		$cpw = '';
		if( extension_loaded('mcrypt') && isset($GLOBALS['babEncryptionKey']) && !empty($GLOBALS['babEncryptionKey']) && !isset($_REQUEST['babEncryptionKey']))
			{
			$cpw = bab_encrypt($password, md5($arr['id'].$arr['sessid'].$BAB_SESS_USERID.$GLOBALS['babEncryptionKey']));
			}
		$db->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$BAB_SESS_USERID."', cpw='".addslashes($cpw)."' where id='".$arr['id']."'");
		}

	// ajout cookie
	if ( $lifetime > 0 )
		{
		setcookie('c_nickname',$nickname,time()+$lifetime);
		$password = strtolower($password);
		if ($GLOBALS['babCookieIdent'] === true) setcookie('c_password',md5($password),time()+$lifetime);
		}
	return true;
	}

function sendPassword ($nickname)
	{
	global $babBody, $BAB_HASH_VAR, $babAdminEmail;

	if (!empty($nickname))
		{
		$req="select id, email from ".BAB_USERS_TBL." where nickname='$nickname'";
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
			$req="update ".BAB_USERS_TBL." set password='". md5($new_pass) ."' where nickname='$nickname'";
			$res=$db->db_query($req);

			//send a simple email with the new password
			notifyUserPassword($new_pass, $arr['email']);
			$babBody->msgerror = bab_translate("Your new password has been emailed to you.") ." &lt;".$arr['email']."&gt;";
			$error = '';
			bab_callAddonsFunctionArray('onUserChangePassword', array('id'=>$arr['id'], 'nickname'=>$nickname, 'password'=>$new_pass, 'error'=>&$error));
			if( !empty($error))
				{
				$babBody->msgerror = $error;
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


function userLogin($nickname,$password)
	{
	global $babBody;
	$db = $GLOBALS['babDB'];
	$iduser = 0;
	$logok = true;
	$authtype = isset($babBody->babsite['authentification'])? $babBody->babsite['authentification']: BAB_AUTHENTIFICATION_OVIDENTIA;

	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET cnx_try=cnx_try+1 WHERE sessid='".session_id()."'");
	list($cnx_try) = $db->db_fetch_array($db->db_query("SELECT cnx_try FROM ".BAB_USERS_LOG_TBL." WHERE sessid='".session_id()."'"));
	if( $cnx_try > 5)
		{
		$babBody->msgerror = bab_translate("Maximum connexion attempts has been reached");
		return false;
		}

	//$password=strtolower($password);
	$res = $db->db_query("select * from ".BAB_USERS_TBL." where nickname='".$db->db_escape_string($nickname)."' and password='". $db->db_escape_string(md5(strtolower($password))) ."'");
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$arruser = $db->db_fetch_array($res);
		if( $arruser['db_authentification'] == 'Y')
			{
			$authtype = BAB_AUTHENTIFICATION_OVIDENTIA;
			}
		}


	if( $authtype != BAB_AUTHENTIFICATION_OVIDENTIA )
		{
		// ldap authentification
		include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
		$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
		$ret = $ldap->connect();
		if( $ret === false )
			{
			$babBody->msgerror = bab_translate("LDAP connection failed. Please contact your administrator");
			$logok = false;
			}

		if( $logok )
			{
			$updattributes = array();
			$res = $db->db_query("select sfrt.*, sfxt.id as idfx from ".BAB_LDAP_SITES_FIELDS_TBL." sfrt left join ".BAB_DBDIR_FIELDSEXTRA_TBL." sfxt on sfxt.id_field=sfrt.id_field where sfrt.id_site='".$babBody->babsite['id']."' and sfxt.id_directory='0'");
			$arridfx = array();

			while( $arr = $db->db_fetch_array($res))
				{
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $db->db_fetch_array($db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$fieldname = $rr['name'];
					}
				else
					{
					$rr = $db->db_fetch_array($db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$fieldname = "babdirf".$arr['id'];
					$arridfx[$arr['id']] = $arr['idfx'];
					}

				if( !empty($arr['x_name']) )
					{
					$updattributes[$arr['x_name']] = strtolower($fieldname);
					}
				}
			
			$attributes = array("dn", "modifyTimestamp", $babBody->babsite['ldap_attribute'], "cn");
			reset($updattributes);
			while(list($key, $val) = each($updattributes))
				{
				if( !in_array($key, $attributes))
					{
					$attributes[] = $key;
					}
				}

			if( !isset($updattributes['sn']))
				{
				$attributes[] = "sn";
				}

			if( !isset($updattributes['mail']))
				{
				$attributes[] = "mail";
				}
			if( !isset($updattributes['givenname']))
				{
				$attributes[] = "givenname";
				}
			switch($authtype)
				{
				case BAB_AUTHENTIFICATION_AD: // Active Directory
					if( isset($GLOBALS['babAdLdapOptions']))
					{
						for( $k=0; $k < count($GLOBALS['babAdLdapOptions']); $k++)
						{						$ldap->set_option($GLOBALS['babAdLdapOptions'][$k][0],$GLOBALS['babAdLdapOptions'][$k][1]);
						}
					}

					$ret = $ldap->bind($nickname."@".$babBody->babsite['ldap_domainname'], $password);
					if( !$ret )
						{
						$babBody->msgerror = bab_translate("LDAP bind failed. Please contact your administrator");
						$logok = false;
						}
					else
						{
						if( isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
							{
							$filter = str_replace('%NICKNAME', ldap_escapefilter($nickname), $babBody->babsite['ldap_filter']);
							}
						else
							{
							$filter = "(|(samaccountname=".ldap_escapefilter($nickname)."))";
							}
						$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);
						}
					break;
				default:
						if( isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
							{
							$filter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
							$filter = str_replace('%NICKNAME', ldap_escapefilter($nickname), $filter);
							}
						else
							{
							$filter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($nickname)."))";
							}
					$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);

					if( $entries !== false && $entries['count'] > 0 && isset($entries[0]['dn']) )
						{
						$ret = $ldap->bind($entries[0]['dn'], $password);
						if( !$ret )
							{
							$babBody->msgerror = bab_translate("LDAP bind failed. Please contact your administrator");
							$logok = false;
							}
						}
					else
						{
						$logok = false;
						}
					break;
				}

			if( !isset($entries) || $entries === false )
				{
				$babBody->msgerror = bab_translate("LDAP authentification failed. Please verify your nickname and your password");
				$logok = false;
				}

			if( $logok )
				{
				$req = "select * from ".BAB_USERS_TBL." where nickname='".$nickname."'";
				$res=$db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0 )
					{
					$arruser = $db->db_fetch_array($res);
					$iduser = $arruser['id'];
					if( $arruser['disabled'] == '1')
						{
						$babBody->msgerror = bab_translate("Sorry, your account is disabled. Please contact your administrator");
						return false;
						}
					}
				else
					{
					$givenname = isset($updattributes['givenname'])?$entries[0][$updattributes['givenname']][0]:$entries[0]['givenname'][0];
					$sn = isset($updattributes['sn'])?$entries[0][$updattributes['sn']][0]:$entries[0]['sn'][0];
					$mn = isset($updattributes['mn'])?$entries[0][$updattributes['mn']][0]:'';
					$mail = isset($updattributes['email'])?$entries[0][$updattributes['email']][0]:$entries[0]['mail'][0];
					$iduser = registerUser(auth_decode($givenname), auth_decode($sn), auth_decode($mn), auth_decode($mail),$nickname, $password, $password, true);
					if( $iduser === false )
						{
						return false;
						}
					$arruser = $db->db_fetch_array($db->db_query("select * from ".BAB_USERS_TBL." where id='".$iduser."'"));
					}
				}
			}

		if( $logok )
			{
			$req = "update ".BAB_USERS_TBL." set password='".md5(strtolower($password))."'";
			reset($updattributes);
			while(list($key, $val) = each($updattributes))
				{
				switch($key)
					{
					case "sn":
						$req .= ", lastname='".addslashes(auth_decode($entries[0][$key][0]))."'";
						break;
					case "givenname":
						$req .= ", firstname='".addslashes(auth_decode($entries[0][$key][0]))."'";
						break;
					case "mail":
						$req .= ", email='".addslashes(auth_decode($entries[0][$key][0]))."'";
						break;
					default:
						break;
					}
				}
			$req .= " where id='".$iduser."'";
			$db->db_query($req);
			$req = "";

			list($idu) = $db->db_fetch_row($db->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$iduser."' and id_directory='0'"));
			if( count($updattributes) > 0 )
				{
				reset($updattributes);
				while(list($key, $val) = each($updattributes))
					{
					switch($key)
						{
						case "jpegphoto":
							$res = $ldap->read($entries[0]['dn'], "objectClass=*", array("jpegphoto"));
							if( $res)
								{
								$ei = $ldap->first_entry($res);
								if( $ei)
									{
									$info = $ldap->get_values_len($ei, "jpegphoto");
									if( $info && is_array($info))
										{
										$req .= ", photo_data='".addslashes($info[0])."'";
										}
									}
								}
							break;
						case "mail":
							$req .= ", email='".addslashes(auth_decode($entries[0][$key][0]))."'";
							break;
						default:
							if( substr($val, 0, strlen("babdirf")) == 'babdirf' )
								{
								$tmp = substr($val, strlen("babdirf"));
								$rs = $db->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$arridfx[$tmp]."' and  id_entry='".$idu."'");
								if( $rs && $db->db_num_rows($rs) > 0 )
									{
									$db->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".addslashes(auth_decode($entries[0][$key][0]))."' where id_fieldx='".$arridfx[$tmp]."' and id_entry='".$idu."'");
									}
								else
									{
									$db->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." ( field_value, id_fieldx, id_entry) values ('".addslashes(auth_decode($entries[0][$key][0]))."', '".$arridfx[$tmp]."', '".$idu."')");
									}
								}
							else
								{
								$req .= ", ".$val."='".addslashes(auth_decode($entries[0][$key][0]))."'";
								}
							break;
						}
					}

				$req = "update ".BAB_DBDIR_ENTRIES_TBL." set ".substr($req, 1);
				$req .= " where id_directory='0' and id_user='".$iduser."'";
				$db->db_query($req);
				}
			}

		if( $logok)
			{
			$ldap->close();
			}
		}

	if( $authtype == BAB_AUTHENTIFICATION_OVIDENTIA || (!$logok && $babBody->babsite['ldap_allowadmincnx'] == 'Y') )
		{
		if( isset($arruser) )
			{
			$iduser = $arruser['id'];
			if( !$logok && $babBody->babsite['ldap_allowadmincnx'] == 'Y' )
				{
				$res = $db->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$iduser."' and id_group='3'");
				if( $db->db_num_rows($res) == 0)
					{
					$babBody->msgerror = bab_translate("LDAP authentification failed. Please verify your nickname and your password");
					return false;
					}
				}

			if( $arruser['disabled'] == '1')
				{
				$babBody->msgerror = bab_translate("Sorry, your account is disabled. Please contact your administrator");
				return false;
				}
			$logok = true;
			}
		else
			{
			$babBody->msgerror = bab_translate("User not found or password incorrect");
			return false;
			}
		}
	
	if( !$logok )
		{
		return false;
		}

	$babBody->msgerror = "";
	if ($arruser['is_confirmed'] == '1')
		{
		if( isset($_SESSION))
			{
			$_SESSION['BAB_SESS_NICKNAME'] = $arruser['nickname'];
			$_SESSION['BAB_SESS_USER'] = bab_composeUserName($arruser['firstname'], $arruser['lastname']);
			$_SESSION['BAB_SESS_FIRSTNAME'] = $arruser['firstname'];
			$_SESSION['BAB_SESS_LASTNAME'] = $arruser['lastname'];
			$_SESSION['BAB_SESS_EMAIL'] = $arruser['email'];
			$_SESSION['BAB_SESS_USERID'] = $arruser['id'];
			$_SESSION['BAB_SESS_HASHID'] = $arruser['confirm_hash'];
			$_SESSION['BAB_SESS_GROUPID'] = bab_getPrimaryGroupId($arruser['id']);
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
			$GLOBALS['BAB_SESS_NICKNAME'] = $arruser['nickname'];
			$GLOBALS['BAB_SESS_USER'] = bab_composeUserName($arruser['firstname'], $arruser['lastname']);
			$GLOBALS['BAB_SESS_FIRSTNAME'] = $arruser['firstname'];
			$GLOBALS['BAB_SESS_LASTNAME'] = $arruser['lastname'];
			$GLOBALS['BAB_SESS_EMAIL'] = $arruser['email'];
			$GLOBALS['BAB_SESS_USERID'] = $arruser['id'];
			$GLOBALS['BAB_SESS_HASHID'] = $arruser['confirm_hash'];
			$GLOBALS['BAB_SESS_GROUPID']  = bab_getPrimaryGroupId($arruser['id']);
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
?>