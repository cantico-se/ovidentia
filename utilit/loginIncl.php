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


require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';


/**
 * Base functionality for all authentication methods.
 *
 */
class PortalAuthentication extends bab_functionality
{
	function getDescription() 
	{
		return bab_translate("Authentication functionnality");
	}

	function getFunctionalityCallableMethods() 
	{
		return array('login', 'logout');
	}

	function login() 
	{
		die(bab_translate("PortalAuthentication::login must not be called directly"));
	}

	function logout() 
	{
		die(bab_translate("PortalAuthentication::logout must not be called directly"));
	}

	/**
	 * Checks whether current user is connected.
	 *
	 * @return bool
	 */
	function isLogged()
	{
		if (array_key_exists('BAB_SESS_LOGGED', $GLOBALS)) {
			return $GLOBALS['BAB_SESS_LOGGED'];
		}
		return false;
	}
}


class PortalAuthentication_ovidentia extends PortalAuthentication
{
	function getDescription() 
	{
		return bab_translate("Authentication methods: Form, LDAP, Active directory, Cookie");
	}

	function getFunctionalityCallableMethods() 
	{
		return array('login', 'logout');
	}

	function registerAuthType()
	{
		$oFunctionalities = new bab_functionalities();
		
		if(false !== $oFunctionalities->register('PortalAuthentication', $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php')) 
		{
			if(false !== $oFunctionalities->register('PortalAuthentication/ovidentia', $GLOBALS['babInstallPath'] . 'utilit/loginIncl.php'))
			{
				return true;			
			}
		}
		return false;		
	}

	function unregisterAuthType()
	{
		$oFunctionalities = new bab_functionalities();
		
		if(false !== $oFunctionalities->unregister('PortalAuthentication')) 
		{
			if(false !== $oFunctionalities->unregister('PortalAuthentication/ovidentia'))
			{
				return true;			
			}
		}
		return false;		
	}
	
	function login() 
	{
		bab_login();
	}

	function logout() 
	{
		bab_logout();
	}
}


/**
 * Ensures that the user is logged in. 
 *
 */
function bab_requireCredential()
{
	$sAuthType = bab_functionalities::sanitize((string) bab_rp('sAuthType', ''));
	
	$sAuthType = (strlen($sAuthType) != 0) ? 'PortalAuthentication/' . $sAuthType :
		'PortalAuthentication';
	
	$oAuthObject = @bab_functionality::get($sAuthType);
	
	if(false === $oAuthObject && 'PortalAuthentication' === $sAuthType)
	{
		// If the default authentication method 'PortalAuthentication' does not exist
		// for example during first installation we (re)create it.
		PortalAuthentication_ovidentia::registerAuthType();
		$oAuthObject = bab_functionality::get($sAuthType);
	}
	
	if(false !== $oAuthObject)
	{
		$_SESSION['sAuthType'] = $sAuthType;
		if (!$oAuthObject->isLogged()) {
			$oAuthObject->login();
		}
	}
	else
	{
		global $babBody;
		$babBody->addError(sprintf(bab_translate("The authentication method %s is invalid"), $sAuthType));
	}
}


function bab_getUserByLoginPassword($sLogin, $sPassword)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			* 
		FROM ' . 
			BAB_USERS_TBL . '
		WHERE 
			nickname = ' . $babDB->quote($sLogin) . ' AND 
			password = ' . $babDB->quote(md5(strtolower($sPassword)));

//	bab_debug($sQuery);
	
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iRows = $babDB->db_num_rows($oResult);
		if($iRows > 0 && false !== ($aDatas = $babDB->db_fetch_array($oResult)))
		{
			return $aDatas;
		}
	}
	return null;		
}


function bab_getUserById($iIdUser)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			* 
		FROM ' . 
			BAB_USERS_TBL . '
		WHERE 
			id = ' . $babDB->quote($iIdUser);

//	bab_debug($sQuery);
	
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iRows = $babDB->db_num_rows($oResult);
		if($iRows > 0 && false !== ($aDatas = $babDB->db_fetch_array($oResult)))
		{
			return $aDatas;
		}
	}
	return null;		
}


function bab_getUserByCookie($sCookie)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			* 
		FROM ' . 
			BAB_USERS_TBL . '
		WHERE 
			cookie_id = \'' . $babDB->db_escape_string($sCookie) . '\' AND
			cookie_validity > NOW()';
			
	$oResult = $babDB->db_query($sQuery);
	if(false != $oResult)
	{
		$iRows = $babDB->db_num_rows($oResult);
		if($iRows > 0 && false != ($aDatas = $babDB->db_fetch_array($oResult)))
		{
			return $aDatas;
		}
	}
	return null;		
}


function bab_getUserByNickname($sNickname)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			* 
		FROM ' . 
			BAB_USERS_TBL . '
		WHERE 
			nickname = \'' . $babDB->db_escape_string($sNickname) . '\'';
			
	$oResult = $babDB->db_query($sQuery);
	if(false != $oResult)
	{
		$iRows = $babDB->db_num_rows($oResult);
		if($iRows > 0 && false != ($aDatas = $babDB->db_fetch_array($oResult)))
		{
			return $aDatas;
		}
	}
	return null;		
}


function bab_haveAdministratorRight($iIdUser)
{
	global $babDB;
	$oRes = $babDB->db_query('select id from ' . BAB_USERS_GROUPS_TBL . ' where id_object=\'' . $babDB->db_escape_string($iIdUser) . '\' and id_group=\'3\'');
	return ($babDB->db_num_rows($oRes) !== 0);
}


function bab_login()
{
	if($GLOBALS['BAB_SESS_LOGGED']) 
	{
		return;
	} 
	else 
	{
		$sLogin = (string) bab_pp('nickname');
		
		if(strlen(trim($sLogin)) === 0) //Authentication
		{
			displayAuthenticationForm();
		} 
		else //Authorization
		{
			if(signOn())
			{
				$sUrl = (string) bab_rp('referer');
				
				if(substr_count($sUrl,'tg=login&cmd=') == 0) 
				{
					loginRedirect($sUrl);
				}
				else
				{
					loginRedirect($GLOBALS['babUrlScript']);
				}
			}
		}
	}	
}


function bab_logout()
{
	signOff();
	loginRedirect($GLOBALS['babPhpSelf']);
}



function displayAuthenticationForm()
{
	global $babBody;
	
	if(!empty($_SERVER['HTTP_HOST']) && !isset($_GET['redirected']) && substr_count($GLOBALS['babUrl'],$_SERVER['HTTP_HOST']) == 0 && !$GLOBALS['BAB_SESS_LOGGED'])
	{
		header('location:'.$GLOBALS['babUrlScript'].'?tg=login&cmd=signon&redirected=1');
	}
	
	$babBody->title = bab_translate("Login");
	$babBody->addItemMenu("signon", bab_translate("Login"), $GLOBALS['babUrlScript']."?tg=login&cmd=signon");
	
	if($babBody->babsite['registration'] == 'Y')
	{
		$babBody->addItemMenu("register", bab_translate("Register"), $GLOBALS['babUrlScript']."?tg=login&cmd=register");
	}
	
	if(isEmailPassword()) 
	{
		$babBody->addItemMenu("emailpwd", bab_translate("Lost Password"), $GLOBALS['babUrlScript']."?tg=login&cmd=emailpwd");
	}
	
	if(!isset($_REQUEST['referer'])) 
	{
		$referer = !empty($GLOBALS['HTTP_REFERER']) ? $GLOBALS['HTTP_REFERER'] : '';
	} 
	else 
	{
		$referer = $_REQUEST['referer'];
	}
	
	displayLogin($referer);
}






//--------------------------------------------------------------------------

function loginRedirect($url)
{
	if(isset($GLOBALS['babLoginRedirect']) && $GLOBALS['babLoginRedirect'] == false)
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
	}
	else
	{
		Header("Location:". $url);
	}
	
	exit;
}


function isEmailPassword()
{
	global $babBody;
	if($GLOBALS['babEmailPassword'])
	{
		switch($babBody->babsite['authentification'])
		{
			case BAB_AUTHENTIFICATION_AD:
				return false;
			case BAB_AUTHENTIFICATION_LDAP:
				if(!empty($babBody->babsite['ldap_encryptiontype']))
				{
					return true;
				}
				break;
			default:
				return true;
		}
	}
	return false;
}



function bab_ldapEntryToOvEntry($oLdap, $iIdUser, $sPassword, $aEntries, $aUpdateAttributes, $aExtraFieldId)
{
	global $babDB;
	
	$sQuery = 'update ' . BAB_USERS_TBL . ' set password=\'' . md5(strtolower($sPassword)) . '\'';
	reset($aUpdateAttributes);
	while(list($key, $val) = each($aUpdateAttributes))
	{
		switch($key)
		{
			case 'sn':
				$sQuery .= ', lastname=\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\'';
				break;
			case 'givenname':
				$sQuery .= ', firstname=\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\'';
				break;
			case 'mail':
				$sQuery .= ', email=\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\'';
				break;
			default:
				break;
		}
	}
	$sQuery .= ' where id=\'' . $babDB->db_escape_string($iIdUser) . '\'';
	$babDB->db_query($sQuery);
	$sQuery = '';
	
	list($idu) = $babDB->db_fetch_row($babDB->db_query('select id from ' . BAB_DBDIR_ENTRIES_TBL . ' where id_user=\'' . $babDB->db_escape_string($iIdUser) . '\' and id_directory=\'0\''));
	if(count($aUpdateAttributes) > 0)
	{
		reset($aUpdateAttributes);
		while(list($key, $val) = each($aUpdateAttributes))
		{
			switch($key)
			{
				case 'jpegphoto':
					$oRes = $oLdap->read($aEntries[0]['dn'], 'objectClass=*', array('jpegphoto'));
					if($oRes)
					{
						$ei = $oLdap->first_entry($oRes);
						if($ei)
						{
							$info = $oLdap->get_values_len($ei, 'jpegphoto');
							if($info && is_array($info))
							{
								$sQuery .= ', photo_data=\'' . $babDB->db_escape_string($info[0]) . '\'';
							}
						}
					}
					break;
					
				case 'mail':
					$sQuery .= ', email=\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\'';
					break;
					
				default:
					if(substr($val, 0, strlen('babdirf')) == 'babdirf')
					{
						$tmp = substr($val, strlen('babdirf'));
						$rs = $babDB->db_query('select id from ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' where id_fieldx=\'' . $babDB->db_escape_string($arridfx[$tmp]) . '\' and  id_entry=\'' . $babDB->db_escape_string($idu) . '\'');
						if($rs && $babDB->db_num_rows($rs) > 0)
						{
							$babDB->db_query('update ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' set field_value=\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\' where id_fieldx=\'' . $babDB->db_escape_string($arridfx[$tmp]) . '\' and id_entry=\'' . $babDB->db_escape_string($idu) . '\'');
						}
						else
						{
							$babDB->db_query('insert into ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' ( field_value, id_fieldx, id_entry) values (\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\', \'' . $babDB->db_escape_string($aExtraFieldId[$tmp]) . '\', \'' . $babDB->db_escape_string($idu) . '\')');
						}
					}
					else
					{
						$sQuery .= ', ' . $val . '=\'' . $babDB->db_escape_string(auth_decode($aEntries[0][$key][0])) . '\'';
					}
					break;
			}
		}

		$sQuery = 'update ' . BAB_DBDIR_ENTRIES_TBL . ' set ' . substr($sQuery, 1);
		$sQuery .= ' where id_directory=\'0\' and id_user=\'' . $babDB->db_escape_string($iIdUser) . '\'';
		$babDB->db_query($sQuery);
	}	
}


function bab_registerUserIfNotExist($sNickname, $sPassword, $aEntries, $aUpdateAttributes)
{
	$iIdUser = false;
	$aUser = bab_getUserByNickname($sNickname);
	if(is_null($aUser))
	{
		$sGivenname	= isset($aUpdateAttributes['givenname'])?$aEntries[0][$aUpdateAttributes['givenname']][0]:$aEntries[0]['givenname'][0];
		$sSn		= isset($aUpdateAttributes['sn'])?$aEntries[0][$aUpdateAttributes['sn']][0]:$aEntries[0]['sn'][0];
		$sMn		= isset($aUpdateAttributes['mn'])?$aEntries[0][$aUpdateAttributes['mn']][0]:'';
		$sMail		= isset($aUpdateAttributes['email'])?$aEntries[0][$aUpdateAttributes['email']][0]:$aEntries[0]['mail'][0];
		$iIdUser	= registerUser(auth_decode($sGivenname), auth_decode($sSn), auth_decode($sMn), auth_decode($sMail), $sNickname, $sPassword, $sPassword, true);
	}
	else 
	{
		$iIdUser = $aUser['id'];
	}
	return $iIdUser;
}


function bab_getLdapExtraFieldIdAndUpdateAttributes(&$aAttributes, &$aUpdateAttributes, &$aExtraFieldId)
{
	global $babDB;
	global $babBody;	
	
	$oResult = $babDB->db_query('select sfrt.*, sfxt.id as idfx from ' . BAB_LDAP_SITES_FIELDS_TBL . ' sfrt left join ' . BAB_DBDIR_FIELDSEXTRA_TBL . ' sfxt on sfxt.id_field=sfrt.id_field where sfrt.id_site=\'' . $babDB->db_escape_string($babBody->babsite['id']) . '\' and sfxt.id_directory=\'0\'');
	$iNumRows = $babDB->db_num_rows($oResult);
	$iIndex = 0;
	
	$aDatasReq1 = array();
	$aDatasReq2 = array();
	while($iIndex < $iNumRows && false !== ($aDatasReq1 = $babDB->db_fetch_array($oResult)))
	{
		$iIndex++;
		if($aDatasReq1['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
		{
			$aDatasReq2 = $babDB->db_fetch_array($babDB->db_query('select name, description from ' . BAB_DBDIR_FIELDS_TBL . ' where id=\'' . $babDB->db_escape_string($aDatasReq1['id_field']) . '\''));
			$sFieldName = $aDatasReq2['name'];
		}
		else
		{
//			$aDatasReq2 = $babDB->db_fetch_array($babDB->db_query('select * from ' . BAB_DBDIR_FIELDS_DIRECTORY_TBL . ' where id=\'' . $babDB->db_escape_string(($aDatasReq1['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)) . '\''));
			$sFieldName = 'babdirf' . $aDatasReq1['id'];
			$arridfx[$aDatasReq1['id']] = $aDatasReq1['idfx'];
		}

		if(!empty($aDatasReq1['x_name']))
		{
			$aUpdateAttributes[$aDatasReq1['x_name']] = strtolower($sFieldName);
		}
	}
	
	reset($aUpdateAttributes);
	while(list($key, $val) = each($aUpdateAttributes))
	{
		if(!in_array($key, $aAttributes))
		{
			$aAttributes[] = $key;
		}
	}

	if(!isset($aUpdateAttributes['sn']))
	{
		$aAttributes[] = 'sn';
	}

	if(!isset($aUpdateAttributes['mail']))
	{
		$aAttributes[] = 'mail';
	}
	
	if(!isset($aUpdateAttributes['givenname']))
	{
		$aAttributes[] = 'givenname';
	}	
}

function bab_logUserConnectionToStat($iIdUser)
{
	// Here we log the connection.
	if($GLOBALS['babStatOnOff'] == 'Y') 
	{
		$registry = bab_getRegistryInstance();
		$registry->changeDirectory('/bab/statistics');
		if($registry->getValue('logConnections')) 
		{
			bab_logUserConnectionTime($iIdUser, session_id());
		}
	}
}

function bab_updateUserConnectionDate($iIdUser)
{
	global $babDB;
	
	$res = $babDB->db_query("select datelog from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($iIdUser)."'");
	if($res && $babDB->db_num_rows($res) > 0)
	{
		$arr = $babDB->db_fetch_array($res);
		$babDB->db_query("update ".BAB_USERS_TBL." set datelog=now(), lastlog='".$babDB->db_escape_string($arr['datelog'])."' where id='".$babDB->db_escape_string($iIdUser)."'");
	}
}


function bab_createReversableUserPassword($iIdUser, $sPassword)
{
	global $babDB;

	$res=$babDB->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='0' and sessid='".session_id()."'");
	if($res && $babDB->db_num_rows($res) > 0)
	{
		$arr = $babDB->db_fetch_array($res);
		$cpw = '';
		if(extension_loaded('mcrypt') && isset($GLOBALS['babEncryptionKey']) && 
		   !empty($GLOBALS['babEncryptionKey']) && !isset($_REQUEST['babEncryptionKey']))
		{
			$cpw = bab_encrypt($sPassword, md5($arr['id'].$arr['sessid'].$iIdUser.$GLOBALS['babEncryptionKey']));
		}
		$babDB->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$babDB->db_escape_string($iIdUser)."', cpw='".$babDB->db_escape_string($cpw)."' where id='".$babDB->db_escape_string($arr['id'])."'");
	}
}


/**
 * Add a cookie for auto login
 * if cookie_id allready exists in batabase for the user, the old cookie_id is used on the new browser
 * @param	int		$iIdUser
 * @param	string	$sLogin
 * @param	int		$iLifeTime
 */
function bab_addUserCookie($iIdUser, $sLogin, $iLifeTime)
{
	
	if($iLifeTime > 0)
	{
		$cookie_validity = time() + $iLifeTime;
		
		if(true === $GLOBALS['babCookieIdent']) 
		{
			
			global $babDB;
			
			$old_token = '';
			
			$res = $babDB->db_query("select cookie_id from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($iIdUser)."'");
			if($res && $babDB->db_num_rows($res) > 0)
			{
				$arr = $babDB->db_fetch_array($res);
				$old_token = $arr['cookie_id'];
			}
			
			$token = empty($old_token) ? md5(uniqid(rand(), true)) : $old_token;
			setcookie('c_password', $token, $cookie_validity);
			
			
			
			$babDB->db_query("UPDATE ".BAB_USERS_TBL." SET 
				cookie_validity='".$babDB->db_escape_string(date('Y-m-d H:i:s',$cookie_validity))."', 
				cookie_id='".$babDB->db_escape_string($token)."' 
			WHERE id='".$babDB->db_escape_string($iIdUser)."'");
		}
			
		if('login' === $GLOBALS['babCookieIdent']) 
		{
			setcookie('c_nickname', $sLogin, $cookie_validity);
		}
	}	
}
?>