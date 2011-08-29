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

/**
* @internal SEC1 PR 2006-12-12 FULL
*/


include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'admin/register.php';
include_once $babInstallPath.'utilit/loginIncl.php';






function emailPassword()
	{
	global $babBody;
	class temp
		{
		var $nickname;
		var $send;

		function temp()
			{
			$this->intro = bab_translate("Before we can reset your password, you need to enter the information below to help identify your account:");
			$this->nickname = bab_translate("Your login ID");
			$this->email = bab_translate("Your email");
			$this->send = bab_translate("Send");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"login.html", "emailpassword"));
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
			$this->firstnameval = bab_toHtml($firstname);
			$this->middlenameval = bab_toHtml($middlename);
			$this->lastnameval = bab_toHtml($lastname);
			$this->nicknameval = bab_toHtml($nickname);
			$this->emailval = bab_toHtml($email);
			$this->firstname = bab_translate("First Name");
			$this->middlename = bab_translate("Middle Name");
			$this->lastname = bab_translate("Last Name");
			$this->nickname = bab_translate("Login ID");
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
			$this->nickname = bab_translate("Login ID");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->adduser = bab_translate("Register");
			
			$this->requiredtxt = bab_translate("Those fields are required");
			$this->passwordlengthtxt = bab_translate("At least 6 characters");

			list($email_confirm) = $babDB->db_fetch_array($babDB->db_query("select email_confirm FROM ".BAB_SITES_TBL." where id='".$babDB->db_escape_string($babBody->babsite['id'])."'"));

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
				$this->urlshowdp = bab_toHtml($GLOBALS['babUrlScript']."?tg=login&cmd=showdp");
				$this->bagree = true;
				}
			else
				{
				$this->bagree = false;
				}

			$this->nicknameval = bab_toHtml($nickname);
			$this->fields = $fields;

			if( $cagree == 'Y' )
				{
				$this->cagreechecked = "checked";
				}
			else
				{
				$this->cagreechecked = "";
				}

			list($jpegphoto) = $babDB->db_fetch_array($babDB->db_query("select registration from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$babDB->db_escape_string($babBody->babsite['id'])."' and id_field='5'"));
			if( $jpegphoto == "Y" )
				{
				$this->bphoto = true;
				}
			else
				{
				$this->bphoto = false;
				}

			$this->res = $babDB->db_query("select sfrt.*, sfxt.id as idfx from ".BAB_SITES_FIELDS_REGISTRATION_TBL." sfrt left join ".BAB_DBDIR_FIELDSEXTRA_TBL." sfxt on sfxt.id_field=sfrt.id_field WHERE sfrt.id_site='".$babDB->db_escape_string($babBody->babsite['id'])."' and sfrt.registration='Y' and sfxt.id_directory='0'");

			$this->count = $babDB->db_num_rows($this->res);

			$this->respf = $babDB->db_query("select * from ".BAB_PROFILES_TBL." where inscription='Y'");
			$this->countpf = $babDB->db_num_rows($this->respf);
			$this->altbg = true;
			
			$oCaptcha = @bab_functionality::get('Captcha');
			$this->bUseCaptcha = false;
			if(false !== $oCaptcha)
				{
					$this->bUseCaptcha = true;
					$this->sCaptchaCaption1 = bab_translate("Word Verification");
					$this->sCaptchaSecurityData = $oCaptcha->getGetSecurityHtmlData();
					$this->sCaptchaCaption2 = bab_translate("Enter the letters in the image above");
				}
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
					$res = $babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'");
					$rr = $babDB->db_fetch_array($res);
					$this->fieldname = bab_toHtml(translateDirectoryField($rr['description']));
					$this->fieldv = $rr['name'];
					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
					$this->fieldname = bab_toHtml(translateDirectoryField($rr['name']));
					$this->fieldv = "babdirf".$arr['id'];
					}

				$this->bfieldphoto = false;
				if( isset($this->fields[$this->fieldv]))
					{
					$this->fieldval = bab_toHtml($this->fields[$this->fieldv]);
					}
				else
					{
					$this->fieldval = '';
					}

				$this->resfxv = $babDB->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." where id_fieldextra='".$babDB->db_escape_string($arr['idfx'])."'");
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
					$rr = $babDB->db_fetch_array($babDB->db_query("select field_value from ".BAB_DBDIR_FIELDSVALUES_TBL." WHERE id='".$babDB->db_escape_string($arr['default_value'])."'"));
					
					$this->fieldval = bab_toHtml($rr['field_value']);
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
				$this->fxvvalue = bab_toHtml($arr['field_value']);
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
				$this->pname = bab_toHtml($arr['name']);
				$this->pdesc =  bab_toHtml($arr['description']);
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
				$this->resgrp = $babDB->db_query("
					SELECT 
						gt.* 
					FROM 
						".BAB_PROFILES_GROUPSSET_TBL." pgt 
						LEFT JOIN ".BAB_GROUPS_TBL." gt on pgt.id_group=gt.id 
					WHERE 
						pgt.id_object ='".$babDB->db_escape_string($arr['id'])."'
					");
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
				$this->grpname = bab_toHtml($arr['name']);
				$this->grpdesc = empty($arr['description']) ? bab_toHtml($arr['name']) : bab_toHtml($arr['description']);
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
	$babBody->babecho(bab_printTemplate($temp,"login.html", "registration"));
	}

function displayDisclaimer()
{
	global $babBody, $babDB;
	$babBody->setTitle(bab_translate("Disclaimer/Privacy statement"));
	$res = $babDB->db_query("select * from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$babDB->db_escape_string($babBody->babsite['id'])."'");
	$arr = $babDB->db_fetch_array($res);
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_disclaimer');
	$editor->setContent($arr['disclaimer_text']);

	$babBody->babpopup($editor->getHtml());
}


function confirmUser($hash, $nickname)
	{
	global $BAB_HASH_VAR, $babBody, $babDB;
	$new_hash=md5($nickname.$BAB_HASH_VAR);
	if ($new_hash && ($new_hash==$hash))
		{
		$sql="select * from ".BAB_USERS_TBL." where confirm_hash='".$babDB->db_escape_string($hash)."'";
		$result=$babDB->db_query($sql);
		if( $babDB->db_num_rows($result) < 1)
			{
			$babBody->msgerror = bab_translate("User Not Found") ." !";
			return false;
			}
		else
			{
			$arr = $babDB->db_fetch_array($result);
			$babBody->msgerror = bab_translate("User Account Updated - You can now log to our site");
			$sql="update ".BAB_USERS_TBL." set is_confirmed='1', datelog=now(), lastlog=now()  WHERE id='".$babDB->db_escape_string($arr['id'])."'";
			$babDB->db_query($sql);
			if( $babBody->babsite['idgroup'] != 0)
				{
				$res = $babDB->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id'])."' and id_group='".$babDB->db_escape_string($babBody->babsite['idgroup'])."'");
				if( !$res || $babDB->db_num_rows($res) < 1)
					{
					bab_addUserToGroup($arr['id'], $babBody->babsite['idgroup']);
					}
				}
				
			include_once $GLOBALS['babInstallPath']."utilit/eventdirectory.php";
			$event = new bab_eventUserModified($arr['id']);
			bab_fireEvent($event);
				
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
	global $babBody, $babDB;
	
	$fields = bab_pp('fields', array());
	$cagree = bab_pp('cagree');
	
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

	if ( mb_strpos($nickname, ' ') !== false )
		{
		$babBody->msgerror = bab_translate("Login ID should not contain spaces");
		return false;
		}

	if ( !bab_isEmailValid($fields['email']))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
		return false;
		}

	$oCaptcha = @bab_functionality::get('Captcha');
	if(false !== $oCaptcha)
		{
		$sCaptchaSecurityCode = bab_pp('sCaptchaSecurityCode', '');
		//echo 'sCaptchaSecurityCode ==> ' . $sCaptchaSecurityCode . ' session ==> ' . $_SESSION['sCaptchaSecurityCode'] . '<br />';
					
		if(!$oCaptcha->securityCodeValid($sCaptchaSecurityCode))
		{
			$babBody->msgerror = bab_translate("The captcha value is incorrect");
			return false;
		}/*
		else
		{
			$babBody->msgerror = bab_translate("The captcha value is correct");
			return false;
		}/*/
		}
		
	if ( !bab_isEmailValid($fields['email']))
		{
		$babBody->msgerror = bab_translate("Your email is not valid !!");
		return false;
		}

	$bphoto = false;

	$res = $babDB->db_query("SELECT sfrt.*, sfxt.id as idfx from ".BAB_SITES_FIELDS_REGISTRATION_TBL." sfrt left join ".BAB_DBDIR_FIELDSEXTRA_TBL." sfxt on sfxt.id_field=sfrt.id_field where sfrt.id_site='".$babDB->db_escape_string($babBody->babsite['id'])."' and sfrt.registration='Y' and sfxt.id_directory='0'");

	$req = '';
	$arridfx = array();
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
			$fieldv = $rr['name'];
			}
		else
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
			$fieldv = "babdirf".$arr['id'];
			}

		if( $fieldv ==  'jpegphoto')
			{
			if($arr['required'] == 'Y' && (!isset($_FILES['photof']) || $_FILES['photof']['size'] == 0))
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
				$req .= $fieldv."='".$babDB->db_escape_string($fields[$fieldv])."',";
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

	if( $bphoto && isset($_FILES['photof']) && $_FILES['photof']['name'] != "none" && $_FILES['photof']['tmp_name'] && $_FILES['photof']['name'] != '')
		{
		if (0 == $_FILES['photof']['size'] || ($babBody->babsite['imgsize']*1000) < filesize($_FILES['photof']['tmp_name']))
			{
			$babBody->msgerror = bab_translate("The image file is too big, maximum is :").$babBody->babsite['imgsize'].bab_translate("Kb");
			return false;
			}
		include_once $babInstallPath."utilit/uploadincl.php";
		$cphoto = bab_getUploadedFileContent('photof');
		}
	
	if( !empty($cphoto))
		{
		$req .= " photo_data='".$babDB->db_escape_string($cphoto)."'";
		}
	else
		{
		$req = mb_substr($req, 0, mb_strlen($req) -1);
		}

	if( !empty($req))
		{
		list($idu) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($iduser)."' and id_directory='0'"));
		if( $idu )
			{
			$req = "update ".BAB_DBDIR_ENTRIES_TBL." set " . $req;
			$req .= " where id='".$babDB->db_escape_string($idu)."'";
			$babDB->db_query($req);

			foreach( $fields as $key => $value )
				{
				if( mb_substr($key, 0, mb_strlen("babdirf")) == 'babdirf' )
					{
					$tmp = mb_substr($key, mb_strlen("babdirf"));

					$rs = $babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$babDB->db_escape_string($arridfx[$tmp])."' and  id_entry='".$babDB->db_escape_string($idu)."'");
					if( $rs && $babDB->db_num_rows($rs) > 0 )
						{
						$babDB->db_query("UPDATE ".BAB_DBDIR_ENTRIES_EXTRA_TBL." 
							SET 
								field_value='".$babDB->db_escape_string($value)."' 
							WHERE 
								id_fieldx='".$babDB->db_escape_string($arridfx[$tmp])."' 
								AND  id_entry='".$babDB->db_escape_string($idu)."'
							");
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




function login_signon()
{
	require_once $GLOBALS['babInstallPath'].'utilit/loginIncl.php';
	$sAuthType = (string) bab_rp('sAuthType', '');
	if (false === bab_requireCredential(bab_translate("Login"), $sAuthType)) {
		$babBody->addError(sprintf(bab_translate("The authentication method '%s' is invalid"), $sAuthType));
	}
	
	// if allready logged, return to homepage
	header('location:'.$GLOBALS['babUrlScript']);
	exit;
}
	





/* main */

$cmd = bab_rp('cmd','signon');

if('register' === bab_pp('adduser') && $babBody->babsite['registration'] == 'Y')
{
	if(!addNewUser(bab_pp('nickname'), bab_pp('password1'), bab_pp('password2'))) 
	{
		$cmd = 'register';
	}
	elseif(2 == $babBody->babsite['email_confirm'])
	{
		// Confirm account without address email validation
		
		$sLogin		= (string) bab_pp('nickname');
		$sPassword	= (string) bab_pp('password1');
		$iLifeTime	= (int) bab_pp('lifetime', 0);
		
		$AuthOvidentia = bab_functionality::get('PortalAuthentication/AuthOvidentia');
		
		$iIdUser = $AuthOvidentia->authenticateUserByLoginPassword($sLogin, $sPassword);
		if(!is_null($iIdUser) && $AuthOvidentia->userCanLogin($iIdUser))
		{
			bab_setUserSessionInfo($iIdUser);
			bab_logUserConnectionToStat($iIdUser);
			bab_updateUserConnectionDate($iIdUser);
			bab_createReversableUserPassword($iIdUser, $sPassword);
			bab_addUserCookie($iIdUser, $sLogin, 0);
			$cmd = 'signon';
		}
		else
		{
			Header("Location: ". $GLOBALS['babUrlScript']);
		}
	}
	else
	{
		$cmd = 'displayMessage';
	}
}
else if('send' === bab_pp('sendpassword'))
{
	sendPassword(bab_pp('nickname'), bab_pp('email'));
	$cmd = 'displayMessage';
}




switch($cmd)
	{
	case 'signoff':
		bab_signOff();
		break;

	case 'displayMessage':
		require_once $GLOBALS['babInstallPath'] . 'utilit/baseFormProcessingClass.php'; 
		
		global $babBody;
		
		$oForm = new BAB_BaseFormProcessing();
		
		$oForm->set_data('sTg', 'login');
		$oForm->set_data('sCmd', 'authform');
		$oForm->set_data('sMessage', $babBody->msgerror);
		$oForm->set_data('sBtnCaption', bab_translate("Ok"));
		
		$babBody->msgerror = '';
		
		$babBody->babecho(bab_printTemplate($oForm, 'login.html', 'displayMessage'));
		break;
		
	case "showdp":
		displayDisclaimer();
		break;

	case "register":
		$babBody->title = bab_translate("Register");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y') {
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
			
			include_once $babInstallPath."utilit/dirincl.php";
			displayRegistration(
					bab_pp('nickname'), 
					bab_rp('fields', array()), 
					bab_pp('cagree')
				);
		}
		if ($GLOBALS['babEmailPassword'] ) {
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		}
		break;

	case "emailpwd":
		$babBody->title = bab_translate("Email a new password");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if (isEmailPassword() )  {
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
			emailPassword();
		} else {
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
		
	case "authform":
		require_once $GLOBALS['babInstallPath'].'utilit/loginIncl.php';
		$loginMessage = bab_rp('msg', '');
		$errorMessage = bab_rp('err', '');
		displayAuthenticationForm($loginMessage, $errorMessage);
		$cmd = 'signon';
		break;
		
	case "confirm":
		confirmUser( $hash, $name );
		login_signon();
		break;
				
	case 'detect':
		if ($GLOBALS['BAB_SESS_LOGGED']) {
			header( "location:".bab_rp('referer') );
			exit;
		}
		else
		{
			login_signon();
		}
		break;

	case "signon":
	default:
		login_signon();
		break;
				
	}
$babBody->setCurrentItemMenu($cmd);
