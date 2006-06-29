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
include_once $babInstallPath."admin/register.php";

function auth_decode($str)
{
	global $babBody;
	return bab_ldapDecode($str, $babBody->babsite['ldap_decoding_type']);
}


function isEmailPassword()
{
	global $babBody;
	if( $GLOBALS['babEmailPassword'] )
	{
	switch($babBody->babsite['authentification'])
		{
		case BAB_AUTHENTIFICATION_AD:
			return false;
			break;
		case BAB_AUTHENTIFICATION_LDAP:
			if( !empty($babBody->babsite['ldap_encryptiontype']))
				{
				return true;
				}
			break;
		default:
			return true;
			break;
		}
	}
	return false;
}

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

			if (!isset($GLOBALS['c_nickname'])) $this->c_nickname = '';
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

	if ( $GLOBALS['babCookieIdent'] != 'login' ) 
		setcookie('c_nickname'," ");
	setcookie('c_password'," ");

	loginRedirect($GLOBALS['babPhpSelf']);
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


function displayRegistration($nickname, $fields, $cagree)
	{
	global $babBody, $babDB;
	class temp
		{

		function temp($nickname, $fields, $cagree)
			{
			global $babBody, $babDB;
			$this->nickname = bab_translate("Nickname");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->adduser = bab_translate("Register");
			
			$this->requiredtxt = bab_translate("Those fields are required");
			$this->passwordlengthtxt = bab_translate("At least 6 characters");

			list($email_confirm) = $babDB->db_fetch_array($babDB->db_query("select email_confirm FROM ".BAB_SITES_TBL." where id='".$babBody->babsite['id']."'"));

			if ($email_confirm == 'Y')
				{
				$this->infotxt1 = bab_translate("Please provide a valid email.");
				$this->infotxt2 = bab_translate("We will send you an email for confirmation before you can use our services");
				}
			else
				{
				if($babBody->babsite['email_confirm'] == 2)
					{
					$this->infotxt1 = '';
					$this->infotxt2 = '';
					}
				else
					{
					$this->infotxt1 = '';
					$this->infotxt2 = bab_translate("Your account will be activated only after validation");
					}
				}

			if( $babBody->babsite['display_disclaimer'] == "Y" )
				{
				$this->disclaimer = bab_translate("I have read and accept the agreement");
				$this->readtxt = bab_translate("Read");
				$this->urlshowdp = $GLOBALS['babUrlScript']."?tg=login&cmd=showdp";
				$this->bagree = true;
				}
			else
				{
				$this->bagree = false;
				}

			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->fields = $fields;

			if( $cagree == 'Y' )
				{
				$this->cagreechecked = "checked";
				}
			else
				{
				$this->cagreechecked = "";
				}

			list($jpegphoto) = $babDB->db_fetch_array($babDB->db_query("select registration from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$babBody->babsite['id']."' and id_field='5'"));
			if( $jpegphoto == "Y" )
				{
				$this->bphoto = true;
				}
			else
				{
				$this->bphoto = false;
				}

			$this->res = $babDB->db_query("select sfrt.*, sfxt.id as idfx from ".BAB_SITES_FIELDS_REGISTRATION_TBL." sfrt left join ".BAB_DBDIR_FIELDSEXTRA_TBL." sfxt on sfxt.id_field=sfrt.id_field where sfrt.id_site='".$babBody->babsite['id']."' and sfrt.registration='Y' and sfxt.id_directory='0'");

			$this->count = $babDB->db_num_rows($this->res);

			$this->respf = $babDB->db_query("select * from ".BAB_PROFILES_TBL." where inscription='Y'");
			$this->countpf = $babDB->db_num_rows($this->respf);
			$this->altbg = true;
			}

		function getnextfield(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$res = $babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'");
					$rr = $babDB->db_fetch_array($res);
					$this->fieldname = translateDirectoryField($rr['description']);
					$this->fieldv = $rr['name'];
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->fieldname = translateDirectoryField($rr['name']);
					$this->fieldv = "babdirf".$arr['id'];
					}

				$this->bfieldphoto = false;
				if( isset($this->fields[$this->fieldv]))
					{
					$this->fieldval = isset($this->fields[$this->fieldv]) ? $this->fields[$this->fieldv] : '';
					}
				else
					{
					$this->fieldval = '';
					}

				$this->resfxv = $babDB->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$arr['idfx']."'");
				$this->countfxv = $babDB->db_num_rows($this->resfxv); 

				$this->required = $arr['required'];
				if( $this->countfxv == 0  )
					{
					$this->multivalues = false;
					}
				elseif( $this->countfxv > 1  )
					{
					$this->multivalues = true;
					}
				else
					{
					$this->multivalues = $arr['multi_values'] == 'Y'? true: false;
					}

				$this->fieldt = $arr['multilignes'];
				if( !empty( $arr['default_value'] ) && empty($this->fvalue) && $this->countfxv > 0)
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id='".$arr['default_value']."'"));
					$this->fieldval = $rr['field_value'];
					}

				if( $this->bphoto && $this->fieldv == "jpegphoto" )
					{
					$this->bfieldphoto = true;
					}

				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextfxv()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfxv)
				{
				$arr = $babDB->db_fetch_array($this->resfxv);
				$this->fxvvalue = $arr['field_value'];
				if( $this->fieldval == $this->fxvvalue )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextprofile()
			{
			global $babDB;
			static $j = 0;
			if( $j < $this->countpf)
				{
				$arr = $babDB->db_fetch_array($this->respf);
				$this->pname = $arr['name'];
				$this->pdesc = $arr['description'];
				$this->idprofile = $arr['id'];
				if( $arr['multiplicity'] == 'Y' )
					{
					$this->bmultiplicity = true;
					}
				else
					{
					$this->bmultiplicity = false;
					}
				if( $arr['required'] == "Y")
					{
					$this->brequired = true;
					}
				else
					{
					$this->brequired = false;
					}
				$this->resgrp = $babDB->db_query("select gt.* from ".BAB_PROFILES_GROUPSSET_TBL." pgt left join ".BAB_GROUPS_TBL." gt on pgt.id_group=gt.id where pgt.id_object ='".$arr['id']."'");
				$this->countgrp = $babDB->db_num_rows($this->resgrp);
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;
				}
			}

		function getnextgrp()
			{
			global $babBody, $babDB;
			static $i = 0;	
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->altbg = !$this->altbg;
				$this->grpid = $arr['id'];
				$this->grpname = $arr['name'];
				$this->grpdesc = empty($arr['description'])? $arr['name']: $arr['description'];
				if( isset($GLOBALS["grpids".$this->idprofile]) && count($GLOBALS["grpids".$this->idprofile]) > 0 && in_array($arr['id'] , $GLOBALS["grpids".$this->idprofile]))
					{
					if( $this->bmultiplicity == true )
						{
						$this->grpcheck = 'checked';
						}
					else
						{
						$this->grpcheck = 'selected';
						}
					}
				else
					{
					$this->grpcheck = '';
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		
		}

	$temp = new temp($nickname, $fields, $cagree);
	$babBody->babecho( bab_printTemplate($temp,"login.html", "registration"));
	}

function displayDisclaimer()
{
	global $babBody, $babBodyPopup;
	class temp
		{
		function temp()
			{
			global $babBody, $babDB;
			$this->title = bab_translate("Disclaimer/Privacy statement");
			$res = $babDB->db_query("select * from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$babBody->babsite['id']."'");
			$arr = $babDB->db_fetch_array($res);
			$this->content = bab_replace($arr['disclaimer_text']);
			}

		}

	$temp = new temp();
	$babBodyPopup->babecho( bab_printTemplate($temp, "login.html", "displaydisclaimer"));
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

	$password=strtolower($password);
	$res = $db->db_query("select * from ".BAB_USERS_TBL." where nickname='".$db->db_escape_string($nickname)."' and password='". $db->db_escape_string(md5($password)) ."'");
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
			$req = "update ".BAB_USERS_TBL." set password='".md5($password)."'";
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
			if( $babBody->babsite['idgroup'] != 0)
				{
				$res = $db->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$arr['id']."' and id_group='".$babBody->babsite['idgroup']."'");
				if( !$res || $db->db_num_rows($res) < 1)
					{
					bab_addUserToGroup($arr['id'], $babBody->babsite['idgroup']);
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
	

function addNewUser( $nickname, $password1, $password2)
	{
	global $babBody, $babDB, $fields, $cagree, $photof, $photof_name;
	if( empty($nickname) || empty($fields['email']) || empty($fields['givenname']) || empty($fields['sn']) || empty($password1) || empty($password2))
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

	if ( !bab_isEmailValid($fields['email']))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
		return false;
		}

	$bphoto = false;

	$res = $babDB->db_query("select sfrt.*, sfxt.id as idfx from ".BAB_SITES_FIELDS_REGISTRATION_TBL." sfrt left join ".BAB_DBDIR_FIELDSEXTRA_TBL." sfxt on sfxt.id_field=sfrt.id_field where sfrt.id_site='".$babBody->babsite['id']."' and sfrt.registration='Y' and sfxt.id_directory='0'");

	$req = "";
	$arridfx = array();
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
			$fieldv = $rr['name'];
			}
		else
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
			$fieldv = "babdirf".$arr['id'];
			}

		if( $fieldv ==  'jpegphoto')
			{
			if($arr['required'] == 'Y' && !isset($photof_name))
				{
				$babBody->msgerror = bab_translate( "You must complete all fields !!");
				return false;
				}
			else
				{
				$bphoto = true;
				}
			}
		else
			{
			if( $arr['required'] == 'Y' && empty($fields[$fieldv]))
				{
				$babBody->msgerror = bab_translate( "You must complete all fields !!");
				return false;
				}
			if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
				{
				$req .= $fieldv."='".addslashes($fields[$fieldv])."',";
				}
			else
				{
				$arridfx[$arr['id']] = $arr['idfx'];
				}
			}
		}

	if( $babBody->babsite['display_disclaimer'] == "Y" && !isset($cagree))
		{
		$babBody->msgerror = bab_translate( "You must complete all fields !!");
		return false;
		}

	$res = $babDB->db_query("select id, required from ".BAB_PROFILES_TBL." where inscription='Y'");
	$groups = array();
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( isset($GLOBALS["grpids".$arr['id']]))
			{
			$grpvar = $GLOBALS["grpids".$arr['id']];
			}
		else
			{
			$grpvar = array();
			}

		if($arr['required'] == 'Y' && (count($grpvar) == 0 || empty($grpvar[0])))
			{
			$babBody->msgerror = bab_translate( "You must complete all fields !!");
			return false;
			}

		for( $i = 0; $i < count($grpvar ); $i++ )
			{
			if( count($groups) == 0 || !in_array($grpvar[$i], $groups))
				{
				$groups[] = $grpvar[$i];
				}
			}
		}

	$iduser = registerUser(stripslashes($fields['givenname']), stripslashes($fields['sn']), stripslashes($fields['givenname']), $fields['email'],$nickname, $password1, $password2, false);
	if( $iduser == false )
		{
		return false;
		}

	if( $bphoto && !empty($photof_name) && $photof_name != "none")
		{
		if ($babBody->babsite['imgsize']*1000 < filesize($photof))
			{
			$babBody->msgerror = bab_translate("The image file is too big, maximum is :").$babBody->babsite['imgsize'].bab_translate("Kb");
			return false;
			}
		$fp=fopen($photof,"rb");
		if( $fp )
			{
			$cphoto = addslashes(fread($fp,filesize($photof)));
			fclose($fp);
			}
		}
	
	if( !empty($cphoto))
		{
		$req .= " photo_data='".$cphoto."'";
		}
	else
		{
		$req = substr($req, 0, strlen($req) -1);
		}

	if( !empty($req))
		{
		list($idu) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$iduser."' and id_directory='0'"));
		if( $idu )
			{
			$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
			$req .= " where id='".$idu."'";
			$babDB->db_query($req);

			foreach( $fields as $key => $value )
				{
				if( substr($key, 0, strlen("babdirf")) == 'babdirf' )
					{
					$tmp = substr($key, strlen("babdirf"));

					$rs = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$babDB->db_escape_string($arridfx[$tmp])."' and  id_entry='".$babDB->db_escape_string($idu)."'");
					if( $rs && $babDB->db_num_rows($rs) > 0 )
						{
						$babDB->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".$babDB->db_escape_string($value)."' where id_fieldx='".$arridfx[$tmp]."' and  id_entry='".$babDB->db_escape_string($idu)."'");
						}
					else
						{
						$babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (field_value, id_fieldx, id_entry) values ('".$babDB->db_escape_string($value)."', '".$babDB->db_escape_string($arridfx[$tmp])."', '".$babDB->db_escape_string($idu)."')");
						}
					}
				}

			}
		}


	if( count($groups) > 0 )
		{
		for( $i = 0; $i < count($groups); $i++ )
			{
			bab_addUserToGroup($iduser, $groups[$i]);
			}
		}
	return true;
	}

function loginRedirect($url)
{

	if( isset($GLOBALS['babLoginRedirect']) && $GLOBALS['babLoginRedirect'] == false )
	{
		class loginRedirectCls 
			{
			function loginRedirectCls($url)
				{
				$this->url = $url;
				}
			}

		$lrc = new loginRedirectCls($url);
		echo bab_printTemplate($lrc, "login.html", "javaredirect");
		exit;
	}
	else
	{
		Header("Location: ". $url);
	}
}
/* main */
// ajout cookie
if (!isset($lifetime))
	{
	$lifetime = 0;
	}

if (!isset($cmd))
	{
	$cmd = 'signon';
	}

if( isset($login) && $login == "login")
	{
	if(!signOn($nickname, $password, $lifetime))
		$idx = 'signon';
	else
		{
		$url = urldecode($referer);
		if (substr_count($url,$GLOBALS['babUrlScript']) == 1 && substr_count($url,'tg=login&cmd=') == 0)
			loginRedirect($url);
		else
			loginRedirect($GLOBALS['babUrlScript']);
		}
	}
else if( isset($adduser) && $adduser == "register" && $babBody->babsite['registration'] == 'Y')
	{
	if( !addNewUser( $nickname, $password1, $password2))
		$cmd = "register";
	elseif( $babBody->babsite['email_confirm'] == 2 )
		{
		if( !signOn( $nickname, $password1, $lifetime))
			{
			$cmd = 'signon';
			}
		else
			{
			Header("Location: ". $GLOBALS['babUrlScript']);
			}
		}
	}
else if( isset($sendpassword) && $sendpassword == "send")
	{
	sendPassword($nickname);
	}

if ($cmd == "emailpwd" && !isEmailPassword())
	{
	$babBody->msgerror = bab_translate("Acces denied");
	$cmd = "signon";
	}

if ($cmd == "detect" && $GLOBALS['BAB_SESS_LOGGED'])
	header( "location:".$referer );

switch($cmd)
	{
	case "signoff":
		signOff();
		break;

	case "showdp":
		include_once $babInstallPath."utilit/uiutil.php";
		$babBodyPopup = new babBodyPopup();
		displayDisclaimer();
		printBabBodyPopup();
		exit;
		break;

	case "register":
		$babBody->title = bab_translate("Register");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if ($GLOBALS['babEmailPassword'] ) 
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		if( !isset($nickname)) { $nickname = '';}
		if( !isset($cagree)) { $cagree = '';}
		//userCreate($firstname, $middlename, $lastname, $nickname, $email);
		if( !isset($fields)) { $fields = array();}
		include_once $babInstallPath."utilit/dirincl.php";
		displayRegistration($nickname, $fields, $cagree);
		break;

	case "emailpwd":
		$babBody->title = bab_translate("Email a new password");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if (isEmailPassword() ) 
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		emailPassword();
		break;

	case "confirm":
		confirmUser( $hash, $name );
		/* no break; */
	case "signon":
	default:
		if (!empty($_SERVER['HTTP_HOST']) && !isset($_GET['redirected']) && substr_count($GLOBALS['babUrl'],$_SERVER['HTTP_HOST']) == 0 && !$GLOBALS['BAB_SESS_LOGGED'])
			{
			header('location:'.$GLOBALS['babUrlScript'].'?tg=login&cmd=signon&redirected=1');
			}
		$babBody->title = bab_translate("Login");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if (isEmailPassword() ) 
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		if (!isset($referer)) $referer = !empty($GLOBALS['HTTP_REFERER']) ? urlencode($GLOBALS['HTTP_REFERER']) : '';
			displayLogin($referer);
		break;
	}
$babBody->setCurrentItemMenu($cmd);
?>