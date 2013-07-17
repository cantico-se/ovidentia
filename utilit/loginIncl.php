<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'admin/register.php';


require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';


/**
 * Base functionality for all authentication methods.
 * @since 6.7.0
 */
class Func_PortalAuthentication extends bab_functionality
{
	var $loginMessage = '';
	public $errorMessages = array();



	public function getDescription()
	{
		return bab_translate("Authentication functionality");
	}

	/**
	 * Sets the message that will be displayed to the user when asked for his credentials.
	 *
	 * This should provide some information about the reason why the user is required
	 * to enter his credentials.
	 * The message will be displayed in different ways depending on the authentication system.
	 * For the default ovidentia authentication (with an html form), this will
	 * be used as a title for the page.
	 *
	 * Other authentication systems may not be able to display this text.
	 *
	 * @param string	$message
	 */
	public function setLoginMessage($message)
	{
		$this->loginMessage = $message;
	}

	/**
	 * Adds an error message that will be displayed to the user when asked for his credentials.
	 *
	 * @param unknown_type $message
	 */
	public function addError($message)
	{
		$this->errorMessages[] = $message;
	}

	public function clearErrors()
	{
		$this->errorMessages = array();
	}

	/**
	 * Checks whether the specified ovidentia user has reached the maximum number of unsuccessful connection attempts.
	 *
	 * @param int		$iIdUser
	 * @return bool
	 */
	public function checkAttempts()
	{
		global $babDB;
		$babDB->db_query('UPDATE '.BAB_USERS_LOG_TBL.' SET grp_change=1');
		$babDB->db_query('UPDATE '.BAB_USERS_LOG_TBL.' SET cnx_try=cnx_try+1 WHERE sessid=' . $babDB->quote(session_id()));
		$userLogs = $babDB->db_query('SELECT cnx_try FROM '.BAB_USERS_LOG_TBL.' WHERE sessid=' . $babDB->quote(session_id()));
		list($cnx_try) = $babDB->db_fetch_array($userLogs);
		return ($cnx_try > 5);
	}

	/**
	 * Checks whether the specified ovidentia user is allowed to log on the system.
	 *
	 * @param int		$iIdUser
	 * @return bool
	 */
	public function userCanLogin($iIdUser)
	{
		if (is_null($iIdUser)) {
			return false;
		}

		if ($this->checkAttempts()) {
			$this->addError(bab_translate("Maximum number of connection attempts has been reached"));
			return false;
		}

		$aUser = bab_getUserById($iIdUser);
		if (!is_null($aUser)) {
			$today = date('Y-m-d');
			if (($aUser['disabled'] == '1') ||
				(($aUser['validity_start'] != '0000-00-00' && $today < $aUser['validity_start'])
					||  ($aUser['validity_end'] != '0000-00-00' && $today > $aUser['validity_end'])) )
			{
				$this->addError(bab_translate("Sorry, your account is disabled. Please contact your administrator"));
				return false;
			} elseif ($aUser['is_confirmed'] != '1') {
				$this->addError(bab_translate("Sorry - You haven't confirmed your account yet"));
				return false;
			}
			return true;
		}
		return false;
	}


	/**
	 * Checks whether current user is connected.
	 *
	 * @return bool
	 */
	public function isLogged()
	{
		require_once $GLOBALS['babInstallPath'].'utilit/userincl.php';
		return bab_isUserLogged();
	}



	/**
	 * Returns the user id for the specified cookie id.
	 *
	 * @param string	$sCookie		The cookie id (stored in c_password cookie).
	 * @return int		The user id or null if not found
	 */
	public function authenticateUserByCookie($sCookie)
	{
		$aUser = bab_getUserByCookie($sCookie);
		if (!is_null($aUser))
		{
			return (int) $aUser['id'];
		}
		return null;
	}






	/**
	 * Returns the user id for the specified nickname and password using the default backend for the site.
	 *
	 * @param string	$sLogin		The user nickname
	 * @param string	$sPassword	The user password
	 * @return int		The user id or null if not found
	 */
	public function authenticateUser($sLogin, $sPassword)
	{
		require_once dirname(__FILE__).'/settings.class.php';
		
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
		
		$babsite = $settings->getSiteSettings();
		
		
		$aUser = bab_getUserByLoginPassword($sLogin, $sPassword);
		if (!is_null($aUser) && $aUser['db_authentification'] == 'Y')
			{
			$babsite['authentification'] = BAB_AUTHENTIFICATION_OVIDENTIA;
			}

		$iAuthenticationType = (int) $babsite['authentification'];
		$AuthOvidentia = bab_functionality::get('PortalAuthentication/AuthOvidentia');
		
		/*@var $AuthOvidentia Func_PortalAuthentication_AuthOvidentia */
		
		$return = null;

		switch ($iAuthenticationType)
		{
			case BAB_AUTHENTIFICATION_OVIDENTIA:
				$return = $AuthOvidentia->authenticateUserByLoginPassword($sLogin, $sPassword);
				break;
			case BAB_AUTHENTIFICATION_LDAP:
				$return = $AuthOvidentia->authenticateUserByLDAP($sLogin, $sPassword);
				break;
			case BAB_AUTHENTIFICATION_AD:
				$return = $AuthOvidentia->authenticateUserByActiveDirectory($sLogin, $sPassword);
				break;
		}
		
		// copy errors messages to original object
		$this->errorMessages = $AuthOvidentia->errorMessages;
		
		return $return;
	}
	
	/**
	 * Init the session for a logged in user
	 * This method have to be called after the userCanLogin method
	 * This method should be followed by a redirect to refresh the page and display the logged in status
	 * 
	 * @see Func_PortalAuthentication::userCanLogin
	 * @see bab_eventAfterUserLoggedIn
	 * 
	 * @param	int	$id_user			The user must exists in ovidentia database
	 * @param	int	$cookie_duration	null = no cookie
	 * 
	 * @todo return false if one of the function does not work
	 * 
	 * @return bool
	 */
	public function setUserSession($id_user, $cookie_duration = null)
	{
		require_once dirname(__FILE__).'/eventloginincl.php';
		
		if (!session_id())
		{
			return false;
		}
		
		bab_setUserSessionInfo($id_user);
		bab_logUserConnectionToStat($id_user);
		bab_updateUserConnectionDate($id_user);
		
		if (null !== $cookie_duration)
		{
			bab_addUserCookie($id_user, bab_getUserNickname($id_user), $cookie_duration);
		}
		
		$event = new bab_eventAfterUserLoggedIn;
		$event->id_user = $id_user;
		
		bab_fireEvent($event);
		
		return true;
	}


	/**
	 * Register myself as a functionality.
	 */
	public static function register()
	{
		require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
		$functionalities = new bab_functionalities();
		return $functionalities->registerClass('Func_PortalAuthentication', __FILE__);
	}

	public function login()
	{
		die(bab_translate("Func_PortalAuthentication::login must not be called directly"));
	}

	public function logout()
	{
		die(bab_translate("Func_PortalAuthentication::logout must not be called directly"));
	}

}


/**
 * Functionality for classic ovidentia authentication method.
 * @since 6.7.0
 */
class Func_PortalAuthentication_AuthOvidentia extends Func_PortalAuthentication
{

	public function getDescription()
	{
		return bab_translate("Authentication methods: Form, LDAP, Active directory, Cookie");
	}

	/**
	 * Register myself as a functionality.
	 * @static
	 */
	public static function register()
	{
		require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
		$functionalities = new bab_functionalities();
		return $functionalities->registerClass('Func_PortalAuthentication_AuthOvidentia', __FILE__);
	}

	public function registerAuthType()
	{
		if (Func_PortalAuthentication::register() === false) {
			return false;
		}
		return Func_PortalAuthentication_AuthOvidentia::register();
	}

	public function login()
	{
		$sLogin		= bab_pp('nickname', null);
		$sPassword	= bab_pp('password', null);
		$iLifeTime	= (int) bab_pp('lifetime', 0);

		if (!empty($sLogin) && !empty($sPassword))
		{
			$iIdUser = $this->authenticateUser($sLogin, $sPassword);

			if ($this->userCanLogin($iIdUser))
			{
				$this->setUserSession($iIdUser, $iLifeTime);
				bab_createReversableUserPassword($iIdUser, $sPassword);

				return true;
			}
		}
		else if ($sLogin === '' || $sPassword === '')
		{
			$this->addError(bab_translate("You must complete all fields !!"));
		}
		header('location:'.$GLOBALS['babUrlScript'] . '?tg=login&cmd=authform&msg=' . urlencode($this->loginMessage) . '&err=' . urlencode(implode("\n", $this->errorMessages)));
		return false;
	}


	public function logout()
	{
		bab_logout();
	}


	/**
	 * Returns the user id for the specified nickname and password using the local database backend.
	 *
	 * @param string	$sLogin		The user nickname
	 * @param string	$sPassword	The user password
	 * @return int		The user id or null if not found
	 */
	public function authenticateUserByLoginPassword($sLogin, $sPassword)
	{
		$aUser = bab_getUserByLoginPassword($sLogin, $sPassword);
		
		if (null == $aUser )
		{
			$this->addError(bab_translate("User not found or bad password"));
			return null;
		}
		
		
		// test confirm hash, this is done only for authentication tu prevent a succes on login without a logged status

		if ($aUser['confirm_hash'] !== md5($sLogin.bab_getHashVar()))
		{
			$this->addError(bab_translate("Account encryption does not match with credentials"));
			return null;
		}
		
		

		return (int) $aUser['id'];
	}
	
	
	
	/**
	 * Returns the user id for the specified email and password using the local database backend.
	 *
	 * @param string	$email		The user email
	 * @param string	$sPassword	The user password
	 * @return int		The user id or null if not found
	 */
	public function authenticateUserByEmailPassword($email, $sPassword)
	{
		global $BAB_HASH_VAR;
	
		$aUser = bab_getUserByEmailPassword($email, $sPassword);
	
		if (null === $aUser )
		{
			$this->addError(bab_translate("User not found or bad password"));
			return null;
		}
		
		if (false === $aUser )
		{
			$this->addError(bab_translate("There are more than one user with this email and password"));
			return null;
		}
	
	
		// test confirm hash, this is done only for authentication tu prevent a succes on login without a logged status
	
		if ($aUser['confirm_hash'] !== md5($aUser['nickname'].$BAB_HASH_VAR))
		{
			$this->addError(bab_translate("Account encryption does not match with credentials"));
			return null;
		}
	
	
		return (int) $aUser['id'];
	}



	/**
	 * Returns the user id for the specified nickname and password using the ldap backend.
	 *
	 * @param string	$sLogin		The user nickname
	 * @param string	$sPassword	The user password
	 * @return int		The user id or null if not found
	 */
	public function authenticateUserByLDAP($sLogin, $sPassword)
	{
		global $babBody;

		include_once $GLOBALS['babInstallPath'] . 'utilit/ldap.php';

		$oLdap = new babLDAP($babBody->babsite['ldap_host'], '', false);
		if (false === $oLdap->connect())
		{
			$this->addError(bab_translate("LDAP connection failed. Please contact your administrator"));
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
			if (isset($babBody->babsite['ldap_userdn']) && !empty($babBody->babsite['ldap_userdn']))
			{
				$sUserdn = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_userdn']);
				$sUserdn = str_replace('%NICKNAME', ldap_escapefilter($sLogin), $sUserdn);


				if (false === $oLdap->bind(bab_ldapEncode($sUserdn), bab_ldapEncode($sPassword)))
				{

					$this->addError(bab_translate("LDAP bind failed. Please contact your administrator"));
					$bLdapOk = false;
				}
				else
				{
					$aEntries = $oLdap->search(bab_ldapEncode($sUserdn), '(objectclass=*)', $aAttributes);
					if ($aEntries === false || $aEntries['count'] == 0)
					{
						$this->addError(bab_translate("LDAP search failed"));
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

				$DnEntries = $oLdap->search(bab_ldapEncode($babBody->babsite['ldap_searchdn']), bab_ldapEncode($sFilter), array('dn'));

				if($DnEntries !== false && $DnEntries['count'] > 0 && isset($DnEntries[0]['dn']))
				{

					if(false === $oLdap->bind($DnEntries[0]['dn'], bab_ldapEncode($sPassword)))
					{
						$this->addError(bab_translate("LDAP bind failed. Please contact your administrator"));
						$bLdapOk = false;
					} else {
						
						// in some cases, the search is not allowed on all fields so a first search get the DN from search filter
						// and the second search get the directory entry after the bind operation
						
						$aEntries = $oLdap->search($DnEntries[0]['dn'], '(objectclass=*)', $aAttributes);
					}
				}
				else
				{
					$bLdapOk = false;
				}
			}
		}

		$iIdUser = false;
		if (!isset($aEntries) || $aEntries === false)
		{
			$this->addError(bab_translate("LDAP authentification failed. Please verify your login ID and your password"));
			$bLdapOk = false;
		}

		if( $bLdapOk )
		{
			$isNew = false;
			$iIdUser = $this->registerUserIfNotExist($sLogin, $sPassword, $aEntries, $aUpdateAttributes, $isNew);
			if (false === $iIdUser)
			{
				$oLdap->close();
				return null;
			}
			else
			{
				if ($aEntries['count'] > 0)
				{
					bab_ldapEntryToOvEntry($oLdap, $iIdUser, $sPassword, $aEntries, $aUpdateAttributes, $aExtraFieldId);
					bab_ldapEntryGroups($iIdUser, $aEntries[0], $babBody->babsite['ldap_groups'], (bool) $babBody->babsite['ldap_groups_create']);
				}

				if( $babBody->babsite['ldap_notifyadministrators'] == 'Y' && $isNew )
				{
					$sGivenname	= isset($aUpdateAttributes['givenname'])?$aEntries[0][$aUpdateAttributes['givenname']][0]:$aEntries[0]['givenname'][0];
					$sSn		= isset($aUpdateAttributes['sn'])?$aEntries[0][$aUpdateAttributes['sn']][0]:$aEntries[0]['sn'][0];
					$sMail		= isset($aUpdateAttributes['email'])?$aEntries[0][$aUpdateAttributes['email']][0]:$aEntries[0]['mail'][0];
					notifyAdminRegistration(bab_composeUserName(bab_ldapDecode($sGivenname), bab_ldapDecode($sSn)), bab_ldapDecode($sMail), "");
				}
			}
		}

		$oLdap->close();

		if (false === $bLdapOk)
		{
			if($babBody->babsite['ldap_allowadmincnx'] == 'Y')
			{
				$bLdapOk = bab_haveAdministratorRight($sLogin, $sPassword, $iIdUser);
				if (false === $bLdapOk)
				{
					$this->addError(bab_translate("LDAP authentification failed. Please verify your login ID and your password"));
				}
			}
		}

		if (false !== $iIdUser && $bLdapOk)
		{
			$this->clearErrors();
			return $iIdUser;
		}

		return null;
	}


	/**
	 * LDAP method to create the account
	 * or update directory entry
	 */
	private function registerUserIfNotExist($sNickname, $sPassword, $aEntries, $aUpdateAttributes, &$isNew)
	{
		global $babBody;
		
		//has to do it because keys and values are flipped
		$aUpdateAttributes = array_flip($aUpdateAttributes);
		
		$iIdUser = false;
		$aUser = bab_getUserByNickname($sNickname);
		
		
		$attribute_for_givenname	= isset($aUpdateAttributes['givenname']) 	? $aUpdateAttributes['givenname'] 	: 'givenname';
		$attribute_for_sn			= isset($aUpdateAttributes['sn']) 			? $aUpdateAttributes['sn']			: 'sn';
		$attribute_for_mn			= isset($aUpdateAttributes['mn'])			? $aUpdateAttributes['mn']			: '';
		$attribute_for_mail			= isset($aUpdateAttributes['email'])		? $aUpdateAttributes['email'] 		: 'mail';
		
		
		$test_fields = (bool) $babBody->babsite['ldap_usercreate_test'];
		
		
		if (isset($aEntries[0][$attribute_for_givenname][0])) {
			$sGivenname	= bab_ldapDecode($aEntries[0][$attribute_for_givenname][0]);
		} else if ($test_fields) {
			$this->addError(bab_translate('Error, registration of user is impossible, the givenname is missing'));
			return false;
		} else {
			$sGivenname = '';
		}


		if (isset($aEntries[0][$attribute_for_sn][0])) {
			$sSn	= bab_ldapDecode($aEntries[0][$attribute_for_sn][0]);
		} else if ($test_fields) {
			$this->addError(bab_translate('Error, registration of user is impossible, the lastname is missing'));
			return false;
		} else {
			$sSn = '';
		}

		if ($attribute_for_mn && isset($aEntries[0][$attribute_for_mn][0])) {
			$sMn	= bab_ldapDecode($aEntries[0][$attribute_for_mn][0]);
		} else {
			$sMn	= '';
		}

		if (isset($aEntries[0][$attribute_for_mail][0])) {
			$sMail	= bab_ldapDecode($aEntries[0][$attribute_for_mail][0]);
		} else if ($test_fields) {
			$this->addError(bab_translate('Error, registration of user is impossible, the email is missing'));
			return false;
		} else {
			$sMail = '';
		}
		

		if(is_null($aUser))
		{

			$isNew = true;

			


			$iIdUser = registerUser(
				$sGivenname,
				$sSn,
				$sMn,
				$sMail,
				$sNickname,
				$sPassword,
				$sPassword,
				true
			);

			if (!$iIdUser) {
				// msgerror should be set by the registerUser function
				
				$this->addError($babBody->msgerror);
				return false;
			}
		}
		else
		{
			$isNew = false;
			$iIdUser = $aUser['id'];
		}
		return $iIdUser;
	}








	/**
	 * Returns the user id for the specified nickname and password using the active directory backend.
	 *
	 * @param string	$sLogin		The user nickname
	 * @param string	$sPassword	The user password
	 * @return int		The user id or null if not found
	 */
	public function authenticateUserByActiveDirectory($sLogin, $sPassword)
	{
		global $babBody;

		include_once $GLOBALS['babInstallPath'] . 'utilit/ldap.php';
		$oLdap = new babLDAP($babBody->babsite['ldap_host'], '', false);
		if (false === $oLdap->connect())
		{
			$this->addError(bab_translate("LDAP connection failed. Please contact your administrator"));
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

			if (false === $oLdap->bind(bab_ldapEncode($sLogin."@".$babBody->babsite['ldap_domainname']), bab_ldapEncode($sPassword)))
			{
				$this->addError(bab_translate("LDAP bind failed. Please contact your administrator"));
				$bLdapOk = false;
			}
			else
			{
				$sFilter = '';
				if (isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
				{
					$sFilter = str_replace('%NICKNAME', ldap_escapefilter($sLogin), $babBody->babsite['ldap_filter']);
				}
				else
				{
					$sFilter = "(|(samaccountname=".ldap_escapefilter($sLogin)."))";
				}
				$aEntries = $oLdap->search(bab_ldapEncode($babBody->babsite['ldap_searchdn']), bab_ldapEncode($sFilter), $aAttributes);
			}
		}

		$iIdUser = false;
		if (!isset($aEntries) || $aEntries === false || (isset($aEntries['count']) && 0 === (int) $aEntries['count']))
		{
			$this->addError(bab_translate("LDAP authentification failed. Please verify your login ID and your password"));
			$bLdapOk = false;
		}

		if( $bLdapOk )
		{
			$isNew = false;
			$iIdUser = $this->registerUserIfNotExist($sLogin, $sPassword, $aEntries, $aUpdateAttributes, $isNew);
			if (false === $iIdUser)
			{
				$oLdap->close();
				return null;
			}
			else
			{
				if ($aEntries['count'] > 0)
				{
					bab_ldapEntryToOvEntry($oLdap, $iIdUser, $sPassword, $aEntries, $aUpdateAttributes, $aExtraFieldId);
					bab_ldapEntryGroups($iIdUser, $aEntries[0], $babBody->babsite['ldap_groups'], (bool) $babBody->babsite['ldap_groups_create']);
				}

				if( $babBody->babsite['ldap_notifyadministrators'] == 'Y' && $isNew )
				{
					$sGivenname	= isset($aUpdateAttributes['givenname'])?$aEntries[0][$aUpdateAttributes['givenname']][0]:$aEntries[0]['givenname'][0];
					$sSn		= isset($aUpdateAttributes['sn'])?$aEntries[0][$aUpdateAttributes['sn']][0]:$aEntries[0]['sn'][0];
					$sMail		= isset($aUpdateAttributes['email'])?$aEntries[0][$aUpdateAttributes['email']][0]:$aEntries[0]['mail'][0];
					notifyAdminRegistration(bab_composeUserName(bab_ldapDecode($sGivenname), bab_ldapDecode($sSn)), bab_ldapDecode($sMail), "");
				}

			}
		}
		$oLdap->close();

		if (false === $bLdapOk)
		{
			if ($babBody->babsite['ldap_allowadmincnx'] == 'Y')
			{
				$bLdapOk = bab_haveAdministratorRight($sLogin, $sPassword, $iIdUser);
				if( false === $bLdapOk)
				{
					$this->addError(bab_translate("LDAP authentification failed. Please verify your login ID and your password"));
				}
			}
		}

		if (false !== $iIdUser && $bLdapOk)
		{
			$this->clearErrors();
			return $iIdUser;
		}

		return null;
	}




}


/**
 * Returns the authentication type optionnaly specified by the url.
 *
 * @return string
 */
function bab_getAuthType()
{
	return bab_rp('sAuthType', '');
}



/**
 * Ensures that the user is logged in.
 * If the user is not logged the "PortalAutentication" functionality
 * is used to let the user log in with its credential.
 *
 * The parameter $sAuthType can be used to force the authentication method,
 * it must be the name (path) of the functionality to use without 'PortalAuthentication/'
 *
 * @see bab_requireCredential
 *
 * @param	string		$sLoginMessage	Message displayed to the user when asked to log in.
 * @param	string		$sAuthType		Authentication type.
 * @since 6.7.0
 */
function bab_doRequireCredential($sLoginMessage, $sAuthType)
{
	if(Func_PortalAuthentication::isLogged())
	{
		return true;
	}

	if ($sAuthType === '') {
		// Check if an AuthType has been specified by the url.
		$sAuthType = bab_getAuthType();
		if ($sAuthType === '') {
			// If no AuthType has been specified we use the default authencation functionality.
			$sAuthPath = 'PortalAuthentication';
		} else {
			$sAuthPath = bab_functionalities::sanitize('PortalAuthentication/Auth' . $sAuthType);
		}
	} else {
		$sAuthPath = bab_functionalities::sanitize('PortalAuthentication/Auth' . $sAuthType);
	}

	$oAuthObject = @bab_functionality::get($sAuthPath);

	if (false === $oAuthObject)
	{
		$oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
		if (false === $oAuthObject)
		{
			// If the default authentication method 'AuthOvidentia' does not exist
			// for example during first installation we (re)create it.
			Func_PortalAuthentication_AuthOvidentia::registerAuthType();
			$oAuthObject = @bab_functionality::get('PortalAuthentication/AuthOvidentia');
		}
	}

	if (false === $oAuthObject) {
		die(bab_translate("The system was not able to launch the authentication"));
	}
	$_SESSION['sAuthPath'] = $sAuthPath;
	if(!$oAuthObject->isLogged())
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/httpContext.php';
		if('login' !== bab_pp('login'))
		{
			bab_storeHttpContext();
		}
		$oAuthObject->setLoginMessage($sLoginMessage);
		if(true === $oAuthObject->login())
		{
			loginRedirect($GLOBALS['babUrlScript'] . '?babHttpContext=restore');
			exit;
		}
		
		
		if ($oAuthObject->errorMessages)
		{
			require_once dirname(__FILE__).'/urlincl.php';
			
			$url = new bab_url($GLOBALS['babUrlScript']);
			$url->tg = 'login';
			$url->cmd = 'denied';
			$url->errors = $oAuthObject->errorMessages;
			
			loginRedirect($url->toString());
			
		}
		
		// failed authentication without error message
		die(bab_translate("Failed authentication"));
	}
	return true;
}

/**
 * get user by email and password
 * return false if more than one user with this email and password
 * 
 * @param string $email
 * @param string $sPassword
 * @return multitype:|boolean|NULL
 */
function bab_getUserByEmailPassword($email, $sPassword)
{
	global $babDB;

	$sQuery = '
	SELECT *
	FROM ' . BAB_USERS_TBL . '
	WHERE email = ' . $babDB->quote($email) . '
	AND password = ' . $babDB->quote(md5(mb_strtolower($sPassword)));

	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		$iRows = $babDB->db_num_rows($oResult);
		if($iRows === 1 && false !== ($aDatas = $babDB->db_fetch_array($oResult)))
		{
			return $aDatas;
		}
		
		if ($iRows > 1)
		{
			return false;
		}
	}
	return null;
}



function bab_getUserByLoginPassword($sLogin, $sPassword)
{
	global $babDB;

	$sQuery = '
		SELECT *
		FROM ' . BAB_USERS_TBL . '
		WHERE nickname = ' . $babDB->quote($sLogin) . '
		  AND password = ' . $babDB->quote(md5(mb_strtolower($sPassword)));

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

	$sQuery = '
		SELECT *
		FROM ' . BAB_USERS_TBL . '
		WHERE id = ' . $babDB->quote($iIdUser);

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

	$sQuery = '
		SELECT *
		FROM ' . BAB_USERS_TBL . '
		WHERE cookie_id = ' . $babDB->quote($sCookie) . '
		  AND cookie_validity > NOW()';

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

	$sQuery = '
		SELECT *
		FROM ' . BAB_USERS_TBL . '
		WHERE nickname = ' . $babDB->quote($sNickname);

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

/**
 * Test user connexion and return true if the user with the correct password is administrator
 *
 * @return bool
 */
function bab_haveAdministratorRight($sLogin, $sPassword, &$iIdUser)
{
	global $babDB;
	if (empty($iIdUser))
	{
		$res = $babDB->db_query("select id from ".BAB_USERS_TBL." WHERE nickname='".$babDB->db_escape_string($sLogin)."' and password='". $babDB->db_escape_string(md5(mb_strtolower($sPassword))) ."'");
		if( $res && $babDB->db_num_rows($res))
		{
			$arr = $babDB->db_fetch_array($res);
			$iIdUser = $arr['id'];
		}
	}

	if( $iIdUser )
	{
		$oRes = $babDB->db_query('select id from ' . BAB_USERS_GROUPS_TBL . ' where id_object=\'' . $babDB->db_escape_string($iIdUser) . '\' and id_group=\''.BAB_ADMINISTRATOR_GROUP.'\'');
		return ($babDB->db_num_rows($oRes) !== 0);
	}
	else
	{
		return false;
	}
}


function bab_signOff()
{
	require_once $GLOBALS['babInstallPath'].'utilit/loginIncl.php';

	if(array_key_exists('sAuthPath', $_SESSION))
	{
		$sAuthPath = $_SESSION['sAuthPath'];

		$oAuthObject = bab_functionality::get($sAuthPath);

		if(false !== $oAuthObject)
		{
			$oAuthObject->logout();

			unset($_SESSION['sAuthPath']);
			return;
		}
	}
	bab_logout();
}


function bab_logout($bRedirect = true)
{
	require_once dirname(__FILE__).'/eventloginincl.php';
	global $babBody, $babDB, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."' and sessid='".$babDB->db_escape_string(session_id())."'");
	$babDB->db_query("UPDATE ".BAB_USERS_TBL." SET  cookie_validity=NOW(), cookie_id='' WHERE id='".$babDB->db_escape_string($BAB_SESS_USERID)."'");

	$event = new bab_eventAfterUserLoggedOut;
	$event->id_user = $BAB_SESS_USERID;

	bab_unsetUserSessionInfo();

	// We destroy the session cookie. A new one will be created at the next session.
	if(isset($_COOKIE[session_name()]))
	{
	   setcookie(session_name(), '', time()-42000, '/');
	}
	session_destroy();
	destroyAuthCookie();
	
	bab_fireEvent($event);

	if($bRedirect)
	{
		loginRedirect($GLOBALS['babPhpSelf']);
	}
}


class displayLogin_Template
{
	var $nickname;
	var $password;

	function displayLogin_Template($url)
	{
		$this->nickname = bab_translate("Login ID");
		$this->password = bab_translate("Password");
		$this->login = bab_translate("Login");

		// verify and buid url
		$params = array();
		$arr = explode('?',$url);

		if (isset($arr[1])) {
			$params = explode('&',$arr[1]);
		}

		$url = $GLOBALS['babPhpSelf'];

		foreach($params as $key => $param) {
			$arr = explode('=',$param);
			if (2 == count($arr)) {

				$params[$key] = $arr[0].'='.$arr[1];
			} else {
				unset($params[$key]);
			}
		}

		if (0 < count($params)) {
			$url .= '?'.implode('&',$params);
		}

		$url = str_replace("\n",'', $url);
		$url = str_replace("\r",'', $url);
		$url = str_replace('%0d','', $url);
		$url = str_replace('%0a','', $url);

		$this->referer = bab_toHtml($url);
		$this->life = bab_translate("Connection timeout");
		$this->nolife = bab_translate("No");
		$this->oneday = bab_translate("one day");
		$this->oneweek = bab_translate("one week");
		$this->onemonth = bab_translate("one month");
		$this->oneyear = bab_translate("one year");
		$this->infinite = bab_translate("unlimited");

		$this->c_nickname = isset($_COOKIE['c_nickname']) ? bab_toHtml($_COOKIE['c_nickname']) : '';
	}
}


/**
 * @param string	$title				The title displayed for the login form.
 * @param string	$errorMessages		A string of "\n" separated error messages.
 */
function displayAuthenticationForm($title, $errorMessages)
{
	global $babBody;

	/*
	if(!empty($_SERVER['HTTP_HOST']) && !isset($_GET['redirected']) && mb_substr_count($GLOBALS['babUrl'], $_SERVER['HTTP_HOST']) == 0 && !$GLOBALS['BAB_SESS_LOGGED'])
	{
		header('location:'.$GLOBALS['babUrlScript'].'?tg=login&cmd=signon&redirected=1');
	}
	*/

	$babBody->setTitle($title);
	$errors = explode("\n", $errorMessages);
	foreach ($errors as $errorMessage) {
		$babBody->addError($errorMessage);
	}
	$babBody->addItemMenu('signon', bab_translate("Login"), $GLOBALS['babUrlScript'].'?tg=login&cmd=signon');
//	bab_debug($babBody->babsite);
	if($babBody->babsite['registration'] == 'Y') {
		$babBody->addItemMenu('register', bab_translate("Register"), $GLOBALS['babUrlScript'].'?tg=login&cmd=register');
	}

	if(isEmailPassword())
	{
		$babBody->addItemMenu('emailpwd', bab_translate("Lost Password"), $GLOBALS['babUrlScript'].'?tg=login&cmd=emailpwd');
	}

	if(!isset($_REQUEST['referer']))
	{
		$referer = '';
	}
	else
	{
		$referer = $_REQUEST['referer'];
	}

	$temp = new displayLogin_Template($referer);
	$babBody->babecho(	bab_printTemplate($temp, 'login.html', 'login'));
}






//--------------------------------------------------------------------------

function loginRedirect($url)
{
	if(isset($GLOBALS['babLoginRedirect']) && $GLOBALS['babLoginRedirect'] == false)
	{
		class loginRedirectCls
		{
			var $sContent;

			function loginRedirectCls($url)
			{
				$this->url		= $url;
				$this->next		= bab_translate('Next page');
				$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
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


/**
 * Checks whether a user who has forgotten his password
 * can ask for it to be resent by email.
 *
 * @return bool
 */
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


/**
 * Update directory entry from LDAP entry
 * @param unknown_type 	$oLdap
 * @param int 			$iIdUser
 * @param string 		$sPassword
 * @param array 		$aEntries
 * @param array 		$aUpdateAttributes
 * @param array 		$aExtraFieldId
 */
function bab_ldapEntryToOvEntry($oLdap, $iIdUser, $sPassword, $aEntries, $aUpdateAttributes, $aExtraFieldId)
{
	global $babDB;

	$sQuery = 'update ' . BAB_USERS_TBL . ' set password=\'' . md5(mb_strtolower($sPassword)) . '\'';
	reset($aUpdateAttributes);
	while(list($key, $val) = each($aUpdateAttributes))
	{
		switch($key)
		{
			case 'sn':
				$sQuery .= ', lastname=\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\'';
				break;
			case 'givenname':
				$sQuery .= ', firstname=\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\'';
				break;
			case 'mail':
				$sQuery .= ', email=\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\'';
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
			
			/**
			 * ldap attributes are in lowercase
			 * @see http://fr2.php.net/ldap_get_entries
			 */
			$key = mb_strtolower($key);
			
			switch($key)
			{
				case 'jpegphoto':
					$sQuery .= ', photo_data=\'' . $babDB->db_escape_string($aEntries[0][$key][0]) . '\'';
					break;

				case 'mail':
					$sQuery .= ', email=\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\'';
					break;

				default:
					if(mb_substr($val, 0, mb_strlen('babdirf')) == 'babdirf')
					{
						$tmp = mb_substr($val, mb_strlen('babdirf'));
						$rs = $babDB->db_query('select id from ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' where id_fieldx=\'' . $babDB->db_escape_string($arridfx[$tmp]) . '\' and  id_entry=\'' . $babDB->db_escape_string($idu) . '\'');
						if($rs && $babDB->db_num_rows($rs) > 0)
						{
							$babDB->db_query('update ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' set field_value=\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\' where id_fieldx=\'' . $babDB->db_escape_string($arridfx[$tmp]) . '\' and id_entry=\'' . $babDB->db_escape_string($idu) . '\'');
						}
						else
						{
							$babDB->db_query('insert into ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' ( field_value, id_fieldx, id_entry) values (\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\', \'' . $babDB->db_escape_string($aExtraFieldId[$tmp]) . '\', \'' . $babDB->db_escape_string($idu) . '\')');
						}
					}
					else
					{
						$sQuery .= ', ' . $val . '=\'' . $babDB->db_escape_string(bab_ldapDecode($aEntries[0][$key][0])) . '\'';
					}
					break;
			}
		}

		$sQuery = 'update ' . BAB_DBDIR_ENTRIES_TBL . ' set ' . mb_substr($sQuery, 1);
		$sQuery .= ' where id_directory=\'0\' and id_user=\'' . $babDB->db_escape_string($iIdUser) . '\'';
		$babDB->db_query($sQuery);
	}
}



/**
 * add user to ldap entry groups
 * 
 * 
 * 
 * @param int $id_user				
 * @param array $entry			ldap entry
 * @param string $ldap_groups	ldap attribute with group
 * @param bool $create			Create groups if not exists
 * @return unknown_type
 */
function bab_ldapEntryGroups($id_user, $entry, $ldap_groups, $create)
{
	require_once dirname(__FILE__).'/grpincl.php';
	
	
	if (empty($ldap_groups))
	{
		return;
	}
	
	global $babBody;
	
	// get groups in ldap entry
	
	$groups = $entry[$ldap_groups];
	unset($groups['count']);
	
	$root_groups = bab_getGroups(BAB_REGISTERED_GROUP, false);
	$group_names = array_flip($root_groups['name']);
	
	$valid_groups = array();
	
	foreach($groups as $group)
	{
		if (empty($group))
		{
			continue;
		}
		
		
		if (preg_match('/^CN=([^,]+),'.preg_quote($babBody->babsite['ldap_searchdn']).'$/i', $group, $m))
		{
			// if group is in search DN use CN as group name
			$group = $m[1];
			
		} else if (preg_match('/^CN=([^,]+),/i', $group, $m))
		{
			// ignore groups not in search DN but link to ldap entry
			continue;
		}
		
		
		if (!isset($group_names[$group]))
		{
			bab_debug($group);
			
			if (0 === (int) $babBody->babsite['ldap_groups_create'])
			{
				continue;
			}
			
			$id_group = bab_addGroup($group, '', 0, 0, BAB_REGISTERED_GROUP);
			$valid_groups[$id_group] = $id_group;
			
		} else {
			$id_group = $root_groups['id'][$group_names[$group]];
			$valid_groups[$id_group] = $id_group;
			
			if (bab_isMemberOfGroup($id_group, $id_user))
			{
				continue;
			}
		}
		
		bab_addUserToGroup($id_user, $id_group);
	}
	
	
	
	if (1 === (int) $babBody->babsite['ldap_groups_remove'])
	{
		$arr = bab_getUserGroups($id_user);
		foreach($arr['id'] as $id_user_group)
		{
			if (!isset($valid_groups[$id_user_group]))
			{
				bab_removeUserFromGroup($id_user, $id_user_group);
			}
		}
	}
	
}




function bab_getLdapExtraFieldIdAndUpdateAttributes(&$aAttributes, &$aUpdateAttributes, &$aExtraFieldId)
{
	global $babDB;
	global $babBody;
	
	
	if (!empty($babBody->babsite['ldap_groups']))
	{
		$aAttributes[] = $babBody->babsite['ldap_groups'];
	}
	

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
			$aExtraFieldId[$aDatasReq1['id']] = $aDatasReq1['idfx'];
		}

		if(!empty($aDatasReq1['x_name']))
		{
			$aUpdateAttributes[$aDatasReq1['x_name']] = mb_strtolower($sFieldName);
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
	require_once dirname(__FILE__).'/settings.class.php';
	
	$settings = bab_getInstance('bab_Settings');
	/*@var $settings bab_Settings */
	
	$babsite = $settings->getSiteSettings();
	
	// Here we log the connection.
	if($babsite['stat_log'] == 'Y')
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
 * if cookie_id already exists in batabase for the user, the old cookie_id is used on the new browser
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


function bab_setUserSessionInfo($iIdUser)
{
	global $babBody;

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

		$GLOBALS['BAB_SESS_NICKNAME'] 	= $_SESSION['BAB_SESS_NICKNAME'];
		$GLOBALS['BAB_SESS_USER'] 		= $_SESSION['BAB_SESS_USER'];
		$GLOBALS['BAB_SESS_FIRSTNAME'] 	= $_SESSION['BAB_SESS_FIRSTNAME'];
		$GLOBALS['BAB_SESS_LASTNAME'] 	= $_SESSION['BAB_SESS_LASTNAME'];
		$GLOBALS['BAB_SESS_EMAIL'] 		= $_SESSION['BAB_SESS_EMAIL'];
		$GLOBALS['BAB_SESS_USERID'] 	= $_SESSION['BAB_SESS_USERID'];
		$GLOBALS['BAB_SESS_HASHID'] 	= $_SESSION['BAB_SESS_HASHID'];
		
		if (session_id())
		{
			require_once dirname(__FILE__).'/groupsincl.php';
			bab_Groups::clearCache();
		}

		// empty approbation cache
		if (isset($_SESSION['bab_waitingApprobations'])) {
			unset($_SESSION['bab_waitingApprobations']);
		}
	}
}

function bab_unsetUserSessionInfo()
{
	unset($_SESSION['BAB_SESS_NICKNAME']);
	unset($_SESSION['BAB_SESS_USER']);
	unset($_SESSION['BAB_SESS_FIRSTNAME']);
	unset($_SESSION['BAB_SESS_LASTNAME']);
	unset($_SESSION['BAB_SESS_EMAIL']);
	unset($_SESSION['BAB_SESS_USERID']);
	unset($_SESSION['BAB_SESS_HASHID']);


	$GLOBALS['BAB_SESS_NICKNAME'] = '';
	$GLOBALS['BAB_SESS_USER'] = '';
	$GLOBALS['BAB_SESS_FIRSTNAME'] = '';
	$GLOBALS['BAB_SESS_LASTNAME'] = '';
	$GLOBALS['BAB_SESS_EMAIL'] = '';
	$GLOBALS['BAB_SESS_USERID'] = '';
	$GLOBALS['BAB_SESS_HASHID'] = '';
}
