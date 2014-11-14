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
include_once "base.php";



/*
 * Return the URL of the site
 *
 * @return string (url)
 */
function bab_getBabUrl() {
	$babWebRoot = trim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
	if (!empty($babWebRoot)) {
		$babWebRoot .= '/';
	}

	if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
		$babHost = $_SERVER['HTTP_X_FORWARDED_HOST'];
	} else if (isset($_SERVER['HTTP_HOST'])) {
		$babHost = $_SERVER['HTTP_HOST'];
	} else {
		$babHost = 'localhost';
	}

	if ( (isset($_SERVER['HTTPS']) && 'on' == strtolower($_SERVER['HTTPS']))
	  || (isset($_SERVER['SCRIPT_URI']) && strtolower(substr($_SERVER['SCRIPT_URI'], 0, 5)) == 'https')) {
		$babProtocol = 'https://';
	} else {
		$babProtocol = 'http://';
	}

	return $babProtocol . $babHost . '/' . $babWebRoot ;
}


/**
 * Remove escapes if magic quotes is on
 */
function bab_cleanGpc() {
	static $firstcall = 1;
	if (1 !== $firstcall)
		return;
	$firstcall = 0;
	function bab_slashes(&$val, $key='') {
			if (is_array($val)) {
				array_walk($val,'bab_slashes');
				}
			else
				{
				if( ini_get('register_globals') == 1 && isset($GLOBALS[$key]) && $GLOBALS[$key] === $val )
					{
					$GLOBALS[$key] =  stripslashes($val);
					}
				$val = stripslashes($val);
				}
	}

	if (get_magic_quotes_gpc())	{
		bab_slashes($_GET);
		bab_slashes($_POST);
		bab_slashes($_COOKIE);
		bab_slashes($_REQUEST);
		if (!empty($_FILES))
			{
			foreach($_FILES as $userfile => $fileinfo)
				{
				bab_slashes($_FILES[$userfile]['name']);
				}
			}

	}
}

/* Remove escapes if magic quotes is on */
bab_cleanGpc();

/* URL of the site */
if (!isset($babUrl)) {
	$babUrl = bab_getBabUrl();
}


/* Restore the REQUEST, POST, GET from the session */
if (isset($_GET['babHttpContext'])) {
	require_once $GLOBALS['babInstallPath'] . 'utilit/httpContext.php';
	bab_restoreHttpContext();
	bab_cleanGpc();
}


if (!isset($_SERVER['HTTP_HOST']) && isset($_SERVER["argv"][1])) {
	parse_str($_SERVER["argv"][1], $_GET);
	parse_str($_SERVER["argv"][1], $_REQUEST);
}


// addon controller

if (isset($_REQUEST['addon']))
{
	include_once $babInstallPath.'utilit/dbutil.php';
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	
	$babDB = new babDatabase();
	$babDB->db_setCharset();
	
	$controller = explode('.',$_REQUEST['addon']);
	$addon = bab_getAddonInfosInstance($controller[0]);

	if($addon)
	{
		$file = preg_replace("/[^A-Za-z0-9_\-]/", "", $controller[1]);
		include $addon->getPhpPath().$file.'.php';
	}
	
	die();
}



/* Management of WSSESSIONID for Web Services */
if (isset($_REQUEST['WSSESSIONID'])) {
	require_once $GLOBALS['babInstallPath'].'utilit/addonapi.php';
	session_name(sprintf("OV%u", crc32($babUrl)));
	session_id($_REQUEST['WSSESSIONID']);
	session_start();
	if (!isset($_SESSION['BAB_SESS_WSUSER']) || !$_SESSION['BAB_SESS_WSUSER']) {
		die('Access denied');
	}
} elseif (!session_id()) {
	session_name(sprintf("OV%u", crc32($babUrl)));
	session_start();
}







$BAB_SESS_NICKNAME 		= isset($_SESSION['BAB_SESS_NICKNAME']) 	? $_SESSION['BAB_SESS_NICKNAME'] 	: "";
$BAB_SESS_USER 			= isset($_SESSION['BAB_SESS_USER']) 		? $_SESSION['BAB_SESS_USER'] 		: "";
$BAB_SESS_FIRSTNAME 	= isset($_SESSION['BAB_SESS_FIRSTNAME']) 	? $_SESSION['BAB_SESS_FIRSTNAME'] 	: "";
$BAB_SESS_LASTNAME 		= isset($_SESSION['BAB_SESS_LASTNAME']) 	? $_SESSION['BAB_SESS_LASTNAME'] 	: "";
$BAB_SESS_EMAIL 		= isset($_SESSION['BAB_SESS_EMAIL']) 		? $_SESSION['BAB_SESS_EMAIL'] 		: "";
$BAB_SESS_USERID 		= isset($_SESSION['BAB_SESS_USERID']) 		? $_SESSION['BAB_SESS_USERID'] 		: "";
$BAB_SESS_HASHID 		= isset($_SESSION['BAB_SESS_HASHID']) 		? $_SESSION['BAB_SESS_HASHID'] 		: "";
$BAB_SESS_GROUPID 		= isset($_SESSION['BAB_SESS_GROUPID']) 		? $_SESSION['BAB_SESS_GROUPID'] 	: "";
$BAB_SESS_GROUPNAME 	= isset($_SESSION['BAB_SESS_GROUPNAME']) 	? $_SESSION['BAB_SESS_GROUPNAME'] 	: "";
$BAB_SESS_WSUSER 		= isset($_SESSION['BAB_SESS_WSUSER']) 		? $_SESSION['BAB_SESS_WSUSER'] 		: false;


$babUserPassword = '';
$incl = '';

/* Define the value of chmod used when we create folders
 * babMkdirMode can be defined in config.php
 * default value : 0770
 */
if (!isset($GLOBALS['babMkdirMode'])) {
	$GLOBALS['babMkdirMode'] = 0770;
}

/* Define the value of Umask used when we create files (mask of creation of file by the user)
 * babUmaskMode can be defined in config.php
 * default value : 0
 */
if (!isset($GLOBALS['babUmaskMode'])) {
	$GLOBALS['babUmaskMode'] = 0;
}


include_once $babInstallPath.'utilit/addonapi.php';


$babPhpSelf		= bab_getSelf();
$babUrlScript	= $babUrl.$babPhpSelf;
$babAddonsPath	= $GLOBALS['babInstallPath'].'addons/';
$babSiteName	= mb_substr($babSiteName, 0, 255);



/* Controler */

include_once $babInstallPath.'utilit/defines.php';
include_once $babInstallPath.'utilit/dbutil.php';
$babDB = new babDatabase();
$babDB->db_setCharset();
include_once $babInstallPath.'utilit/statincl.php';
$babWebStat =new bab_WebStatEvent();

include $babInstallPath.'utilit/utilit.php';
include $babInstallPath.'utilit/skinincl.php';

bab_initMbString();
bab_UsersLog::check();
$babBody = bab_getInstance('babBody');
$BAB_HASH_VAR = bab_getHashVar();


unset($BAB_SESS_LOGGED);

/* Set the charset of the current page (ISO-8859-15, UTF-8...)
 * This configuration prevails on the meta tag (meta http-equiv="Content-type" content="text/html; charset=ISO-8859-15"/>)
 */
ini_set('default_charset', bab_charset::getIso());

if ('version' !== bab_rp('tg') || 'upgrade' !== bab_rp('idx')) {
	bab_updateSiteSettings(); /* Get the site settings */
	if ($GLOBALS['babCookieIdent'] === true) {
		include $babInstallPath."utilit/cookieident.php";
	}

	bab_isUserLogged();
	bab_updateUserSettings();

	if (isset($_GET['clear'])) {
		bab_siteMap::clearAll();
		if (isset($_SESSION['ovml_cache']))
		{
			unset($_SESSION['ovml_cache']);
		}
	}
} else {
	if (!isset($babLanguage)) {
		$babLanguage = 'fr';
	}
	if (!isset($babStyle)) {
		$babStyle = 'ovidentia.css';
	}
	if (!isset($babSkin)) {
		$babSkin = 'ovidentia';
	}
}

$babSkinPath = bab_getSkinPath();
$babScriptPath = bab_getStaticUrl().$babInstallPath."scripts/";
$babOvidentiaJs = $babScriptPath."ovidentia.js";
$babOvmlPath = bab_Skin::getUserSkin()->getThemePath().'ovml/';






if (isset($_GET['babrw']))
{
	if (false !== $arr = bab_siteMap::extractNodeUrlFromRewrite($_GET['babrw'], true))
	{
		$_GET += $arr;
		$_REQUEST += $arr;
		extract($arr, EXTR_SKIP);
	} else {
		class bab_eventPageNotFound extends bab_event { }
		$event = new bab_eventPageNotFound;
		bab_fireEvent($event);
		
		header("HTTP/1.0 404 Not Found");
		$babBody->addError(bab_translate('This page does not exists'));
	}
}



/**
 * Event : Before Page Created
 * Event intervenes just before the inclusion of code PHP which manages the current page:
 * the body of the page is not prepared, the template of the page is not treated.
 */
if ('addons' !== bab_rp('tg') || 'import_frame' !== bab_rp('idx')) {
	class bab_eventBeforePageCreated extends bab_event { }
	$event = new bab_eventBeforePageCreated;
	bab_fireEvent($event); /* Fire all event registered as listeners */
}

/* Controler */
switch(bab_rp('tg'))
	{
	case "login":
		$babLevelOne = bab_translate("Home");
		$babLevelTwo = bab_translate("Login");
		$incl = "login";
		break;
	case "sections":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sections");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/sections";
		break;
	case "section":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sections");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/section";
		break;
	case "users":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Users");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/users";
		break;
	case "user":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Users");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/user";
		break;
	case "groups":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/groups";
		break;
	case "group":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/group";
		break;
	case "setsofgroups":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sets of groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/setsofgroups";
		break;
	case "profiles":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Profiles");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/profiles";
		break;
	case "admfaqs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Faqs");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admfaqs";
		break;
	case "admfaq":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Faqs");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admfaq";
		break;
	case "topcat":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/topcat";
		break;
	case "topcats":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/topcats";
		break;
	case "apprflow":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Approbations");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/apprflow";
		break;
	case "admfms":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("File manager");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admfms";
		break;
	case "admfm":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("File manager");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admfm";
		break;
	case "admindex":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Search indexes");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0))
			$incl = "admin/indexfiles";
		break;
	case "topman":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Managed topics");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "topman";
		break;
	case "topics":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "admin/topics";
		break;
	case "topic":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "admin/topic";
		break;
	case "topusr":
		$babLevelOne = bab_translate("Topics categories");
		$incl = "topusr";
		break;
	case "usrindex":
		$babLevelOne = bab_translate("Search indexes");
		$incl = "indexfiles";
		break;
	case "forums":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Forums");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/forums";
		break;
	case "forum":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Forums");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/forum";
		break;
	case "admcals":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Calendar");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admcals";
		break;
	case "admcal":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Calendar");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admcal";
		break;
	case "admocs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Organization chart");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admocs";
		break;
	case "admoc":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Organization chart");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admoc";
		break;
	case "sites":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sites");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/sites";
		break;
	case "site":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sites");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/site";
		break;
	case "addons":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Add-ons");
		$incl = "admin/addons";
		break;
	case "admdir":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Directories");
		if(isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
			$incl = "admin/admdir";
		break;
	case "delegat":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Delegation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/delegat";
		break;
	case "admstats":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Statistics");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admstats";
		break;
	case "admthesaurus":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Thesaurus");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admthesaurus";
		break;

	case "delegusr":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Delegation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "delegusr";
		break;

	case "delegation":
		$incl = "delegation";
		break;
	case "options":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Options");
		if( $BAB_SESS_LOGGED)
    		$incl = "options";
		break;
	case "composemail":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Compose mail");
		if( $BAB_SESS_LOGGED)
    		$incl = "composemail";
		break;
	case "mail":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Mail");
		if( $BAB_SESS_LOGGED)
    		$incl = "mail";
		break;
	case "mailopt":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Options");
		if( $BAB_SESS_LOGGED)
    		$incl = "mailopt";
		break;
	case "maildoms":
		if( isset($userid) && $userid == 0 )
			{
			$babLevelOne = bab_translate("Administration");
			$babLevelTwo = bab_translate("Mail");
			}
		else
			{
			$babLevelOne = bab_translate("User's section");
			$babLevelTwo = bab_translate("Options");
			}
		if( $BAB_SESS_LOGGED)
    		$incl = "maildoms";
		break;
	case "maildom":
		if( isset($userid) && $userid == 0 )
			{
			$babLevelOne = bab_translate("Administration");
			$babLevelTwo = bab_translate("Mail");
			}
		else
			{
			$babLevelOne = bab_translate("User's section");
			$babLevelTwo = bab_translate("Options");
			}
		if( $BAB_SESS_LOGGED)
    		$incl = "maildom";
		break;
	case "confcals":
		if( isset($userid) && $userid == 0 )
			{
			$babLevelOne = bab_translate("Administration");
			$babLevelTwo = bab_translate("Calendar");
			}
		else
			{
			$babLevelOne = bab_translate("User's section");
			$babLevelTwo = bab_translate("Options");
			}
		if( $BAB_SESS_LOGGED)
    		$incl = "confcals";
		break;
	case "confcal":
		if( isset($userid) && $userid == 0 )
			{
			$babLevelOne = bab_translate("Administration");
			$babLevelTwo = bab_translate("Calendar");
			}
		else
			{
			$babLevelOne = bab_translate("User's section");
			$babLevelTwo = bab_translate("Options");
			}
		if( $BAB_SESS_LOGGED)
    		$incl = "confcal";
		break;
	case "calendar":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Calendar");
   		$incl = "calendar";
		break;
	case "calmonth":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Calendar");
   		$incl = "calmonth";
		break;
	case "calweek":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Calendar");
   		$incl = "calweek";
		break;
	case "calday":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Calendar");
   		$incl = "calday";
		break;
	case "event":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Calendar");
		if( $BAB_SESS_LOGGED)
    		$incl = "event";
		break;
	case "calview":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Summary");
   		$incl = "calview";
		break;
	case "calopt":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Calendar");
   		$incl = "calopt";
		break;
	case "sectopt":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Options");
		if( $BAB_SESS_LOGGED)
    		$incl = "sectopt";
		break;
	case "directory":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Directories");
   		$incl = "directory";
		break;
	case "lusers":
		$babLevelOne = "";
		$babLevelTwo = "";
		if( $BAB_SESS_LOGGED)
    		$incl = "lusers";
		break;
	case "selector":
		$babLevelOne = "";
		$babLevelTwo = "";
		if( $BAB_SESS_LOGGED)
    		$incl = "selector";
		break;
	case "stat":
		$babLevelOne = bab_translate("Statistics");
		$incl = "stat";
		break;
	case "statconf":
		$babLevelOne = bab_translate("Statistics");
		$incl = "statconf";
		break;
	case "thesaurus":
		$babLevelOne = bab_translate("Thesaurus");
		$incl = "thesaurus";
		break;
	case "forumsuser":
		$babLevelOne = bab_translate("Forums");
		$babLevelTwo = bab_translate("Forums");
   		$incl = "forumsuser";
		break;
	case "threads":
		$babLevelOne = bab_translate("Forums");
		$incl = "threads";
		break;
	case "posts":
		$babLevelOne = bab_translate("Forums");
		$incl = "posts";
		break;
	case "articles":
		$incl = "articles";
		break;
	case "artedit":
		$incl = "artedit";
		break;
	case "approb":
		$incl = "approb";
		break;
	case "comments":
		$incl = "comments";
		break;
	case "charts":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Charts");
		$incl = "charts";
		break;
	case "chart":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Charts");
		include $babInstallPath."chart.php";
		exit;
		break;
	case "frchart":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Charts");
		include $babInstallPath."frchart.php";
		exit;
		break;
	case "fltchart":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Charts");
		include $babInstallPath."fltchart.php";
		exit;
		break;
	case "flbchart":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Charts");
		include $babInstallPath."flbchart.php";
		exit;
		break;
	case "faq":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Faqs");
		$incl = "faq";
		break;
	case "fileman":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("File manager");
		$incl = "fileman";
		break;
	case "filever":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("File manager");
		$incl = "filever";
		break;
	case "notes":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Notes");
		if( $BAB_SESS_LOGGED && bab_notesAccess())
			$incl = "notes";
		break;
	case "note":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Notes");
		if( $BAB_SESS_LOGGED && bab_notesAccess())
			$incl = "note";
		break;
	case "inbox":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Mail");
		if( $BAB_SESS_LOGGED)
			$incl = "inbox";
		break;
	case "contacts":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Contacts");
		if( $BAB_SESS_LOGGED && bab_contactsAccess())
			$incl = "contacts";
		break;
	case "contact":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Contacts");
		if( $BAB_SESS_LOGGED && bab_contactsAccess())
			{
			include $babInstallPath."contact.php";
			exit;
			}
		break;
	case "address":
		if( $BAB_SESS_LOGGED)
			{
			include $babInstallPath."address.php";
			exit;
			}
		break;
	case "lsa":
		if( $BAB_SESS_LOGGED)
			{
			include $babInstallPath."lsa.php";
			exit;
			}
		break;
	case "month":
		include $babInstallPath."month.php";
		exit;
		break;
	case "images":
		include $babInstallPath."images.php";
		exit;
		break;
	case "version":
		include $babInstallPath."version.php";
		exit;
		break;
	case "statproc":
		include $babInstallPath."utilit/statproc.php";
		break;
	case "calnotif":
		include $babInstallPath."utilit/calnotif.php";
		exit;
		break;
	case "editorarticle":
		include $babInstallPath."editorarticle.php";
		exit;
		break;
	case "editorfaq":
		include $babInstallPath."editorfaq.php";
		exit;
		break;
	case "editorovml":
		include $babInstallPath."editorovml.php";
		exit;
		break;
	case "editorcontdir":
		include $babInstallPath."editorcontdir.php";
		exit;
		break;
	case 'editorfunctions':
		include $babInstallPath."editorfunctions.php";
		exit;
		break;
	case "selectcolor":
		include $babInstallPath."selectcolor.php";
		exit;
		break;
	case "imgget":
		include $babInstallPath."imgget.php";
		exit;
		break;
	case "link":
		include $babInstallPath."link.php";
		exit;
		break;
	case "oml":
		$incl = "oml";
		break;
	case "omlsoap":
		include $babInstallPath."omlsoap.php";
		exit;
		break;
	case "accden":
		$babBody->msgerror = bab_translate("Access denied");
		/* no break; */
	case "entry":
		$babLevelOne = bab_translate("Home");
		$babLevelTwo = '';
		$incl = "entry";
		break;
	case 'admTskMgr':
		$incl = 'admin/tmtaskmanager';
		break;
	case 'usrTskMgr':
		$incl = 'tmtaskmanager';
		break;
	case 'charset':
		$incl = 'admin/charset';
		break;
	default:
		$babLevelOne = "";
		$babLevelTwo = "";
		$incl = "entry";
		$babWebStat->module($incl);
		$arr = explode("/", bab_rp('tg'));
		if( sizeof($arr) == 3 && $arr[0] == "addon")
		{
			include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';

			if (!is_numeric($arr[1]))
			{
				$arr[1] = bab_addonsInfos::getAddonIdByName($arr[1], false);
			}
			
			$addon_row = bab_addonsInfos::getDbRow($arr[1]);
			$addon = bab_getAddonInfosInstance($addon_row['title']);
				
			
			if(false === $addon || !$addon->isAccessValid()) {
				if ($addon && $addon->hasAccessControl() && $addon->isInstalled() && !$addon->isDisabled())
				{
					bab_requireAccess('bab_addons_groups', $addon->getId(), bab_translate('You must be logged in to access this page.'));
				}
				
				$babBody->addError(bab_translate("Access denied"));
				
			} else {
				
				$module = preg_replace("/[^A-Za-z0-9_\-]/", "", $arr[2]);
				bab_setAddonGlobals($addon->getId());
				$babWebStat->addon($addon->getName());
				$babWebStat->module("/".$module);
				
				$incl = null;
				require_once $addon->getPhpPath().$module.'.php';
			}
		}
		else
		{
			bab_siteMap::setPosition(bab_siteMap::getSitemapRootNode());
			if( $BAB_SESS_LOGGED)
			{
				$file = "private.html";
			}
			else
			{
				$file = "public.html";
			}

			if( file_exists($GLOBALS['babOvmlPath'].$file))
			{
				$incl = "oml";
			}
			else
			{
				$incl = "entry";
			}
		}
		break;
	}


if( !empty($incl))
	{
	include $babInstallPath."$incl.php";
	}
	

/**
 * Event page refreshed
 * @since 6.6.90
 */
class bab_eventPageRefreshed extends bab_event { }
$event = new bab_eventPageRefreshed;
bab_fireEvent($event); /* Fire all event registered as listeners */

require_once $GLOBALS['babInstallPath'].'utilit/pageincl.php';

printBody(); /* Display the current page : head, metas, sections, body... */