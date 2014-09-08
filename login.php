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
		
		public $ask_nickname;

		function temp()
			{
				
			include_once dirname(__FILE__).'/utilit/settings.class.php';
				
			$this->intro = bab_translate("Before we can reset your password, you need to enter the information below to help identify your account:");
			$this->nickname = bab_translate("Your login ID");
			$this->email = bab_translate("Your email");
			$this->send = bab_translate("Send");
			
			$settings = bab_getInstance('bab_Settings');
			/*@var $settings bab_Settings */
			$site = $settings->getSiteSettings();
			$this->ask_nickname = (bool) $site['ask_nickname'];
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"login.html", "emailpassword"));
	}




function displayRegistration()
{
	require_once dirname(__FILE__).'/utilit/urlincl.php';
	
	$unload = bab_url::get_request('tg');
	$unload->cmd = 'endregistration';
	
	/*@var $usereditor Func_UserEditor */
	$usereditor = bab_functionality::get('UserEditor');
	
	$usereditor->setRegister();

	/*@var $page Widget_BabPage */
	$page = $usereditor->getAsPage(null, $unload);
	$page->setEmbedded(true);
	$page->displayHtml();
	
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






function login_signon()
{
	require_once $GLOBALS['babInstallPath'].'utilit/loginIncl.php';
	$sAuthType = (string) bab_rp('sAuthType', '');
	if (false === bab_requireCredential(bab_translate("Login"), $sAuthType)) {
		$babBody->addError(sprintf(bab_translate("The authentication method '%s' is invalid"), $sAuthType));
	}

	// if already logged, return to homepage
	header('location:'.$GLOBALS['babUrlScript']);
	exit;
}






/* main */

$cmd = bab_rp('cmd','signon');
if('send' === bab_pp('sendpassword'))
{
	sendPassword(bab_pp('nickname'), bab_pp('email'));
	$cmd = 'displayMessageResetPwd';
}




switch($cmd)
	{
	case 'signoff':
		bab_signOff();
		break;
		
	case 'displayMessageResetPwd':
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
		
	case 'displayMessage':
		global $babBody;
		require_once $GLOBALS['babInstallPath'] . 'utilit/baseFormProcessingClass.php';
		
		$babBody->msgerror = bab_translate("Thank You For Registering at our site") ."<br />";
		if( $babBody->babsite['email_confirm'] == 2){
		}elseif( $babBody->babsite['email_confirm'] == 1 ){
		}else{
			$babBody->msgerror .= bab_translate("You will receive an email which let you confirm your registration.");
		}

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
		$babBody->setTitle(bab_translate("Register"));
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y') {
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");

			include_once $babInstallPath."utilit/dirincl.php";
			displayRegistration();
		}
		if ($GLOBALS['babEmailPassword'] ) {
			$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
		}
		break;
		
	case 'endregistration':
		$babBody->setTitle(bab_translate("Registration is complete"));
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		$babBody->addItemMenu("endregistration", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=endregistration");
		break;

	case "emailpwd":
		$babBody->title = bab_translate("Email a new password");
		$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
		if( $babBody->babsite['registration'] == 'Y')
			$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
		if (bab_isEmailPassword() )  {
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
		
	case "denied";
		foreach(bab_rp('errors') as $error)
		{
			$babBody->addError($error);
		}
		break;

	case "signon":
	default:
		login_signon();
		break;

	}
$babBody->setCurrentItemMenu($cmd);
