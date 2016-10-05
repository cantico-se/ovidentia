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


require_once dirname(__FILE__).'/utilit/functionality.class.php';
require_once dirname(__FILE__).'/utilit/addonapi.php';
require_once dirname(__FILE__).'/utilit/csrfprotect.class.php';

if(!bab_isAjaxRequest() && bab_getUserId()){
    if(isset($_SESSION['pwd_change_log']) && $_SESSION['pwd_change_log']){
        if(!isset($_REQUEST['tg']) || $_REQUEST['tg'] != 'login'){
            header('Location: ?tg=login&cmd=changePwd&user='.$_SESSION['BAB_SESS_USERID']);
            exit;
        }else{
            $_GET['babHttpContext'] = false;
        }
    }
}

/* Restore the REQUEST, POST, GET from the session */
if (isset($_GET['babHttpContext'])) {
    require_once $GLOBALS['babInstallPath'] . 'utilit/httpContext.php';
    bab_restoreHttpContext();
    bab_cleanGpc();
}


if (php_sapi_name() === 'cli' && isset($_SERVER["argv"][1])) {
    parse_str($_SERVER["argv"][1], $_GET);
    parse_str($_SERVER["argv"][1], $_REQUEST);
}


if (!bab_getInstance('bab_CsrfProtect')->isRequestValid()) {
    header($_SERVER["SERVER_PROTOCOL"].' 403 Forbidden');
	exit("403 Access Forbidden");
}

// addon controller

if (isset($_REQUEST['addon']))
{
    include_once $GLOBALS['babInstallPath'].'utilit/dbutil.php';
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
    require_once $GLOBALS['babInstallPath'].'utilit/functionality.class.php';
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



$babPhpSelf		= bab_getSelf();
$babUrlScript	= $babUrl.$babPhpSelf;
$babAddonsPath	= $GLOBALS['babInstallPath'].'addons/';
$babSiteName	= mb_substr($babSiteName, 0, 255);



/* Controler */

include_once $GLOBALS['babInstallPath'].'utilit/defines.php';
include_once $GLOBALS['babInstallPath'].'utilit/dbutil.php';
$babDB = new babDatabase();
$babDB->db_setCharset();
include_once $GLOBALS['babInstallPath'].'utilit/statincl.php';
$babWebStat =new bab_WebStatEvent();

include $GLOBALS['babInstallPath'].'utilit/utilit.php';
include $GLOBALS['babInstallPath'].'utilit/skinincl.php';

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
    
    /**
     * Context intialisation for all pages except the new 
     * addon controller (addon=name.file) and tg=version (upgrades iframe)
     */
    
    
    bab_updateSiteSettings(); /* Get the site settings */
    if ($GLOBALS['babCookieIdent'] === true) {
        include $GLOBALS['babInstallPath']."utilit/cookieident.php";
    }

    if (isset($_GET['clear'])) {
        bab_siteMap::clearAll();
        if (isset($_SESSION['ovml_cache']))
        {
            unset($_SESSION['ovml_cache']);
        }
    }

    bab_isUserLogged();
    bab_updateUserSettings();
} else {
    
    /**
     * Special context initialization for upgrades
     */
    
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
$babScriptPath = bab_getStaticUrl().$GLOBALS['babInstallPath']."scripts/";
$babOvidentiaJs = $babScriptPath."ovidentia.js";
$babOvmlPath = bab_Skin::getUserSkin()->getThemePath().'ovml/';





/**
 * URL rewriting
 */
if (isset($_GET['babrw']))
{
    if (false !== $arr = bab_siteMap::extractNodeUrlFromRewrite($_GET['babrw'], true))
    {
        $_GET += $arr;
        $_REQUEST += $arr;
        
    } else {
        bab_pageNotFound();
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
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/sections";
        break;
    case "section":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Sections");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/section";
        break;
    case "users":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Users");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/users";
        break;
    case "user":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Users");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/user";
        break;
    case "groups":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Groups");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/groups";
        break;
    case "group":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Groups");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/group";
        break;
    case "setsofgroups":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Sets of groups");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/setsofgroups";
        break;
    case "profiles":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Profiles");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/profiles";
        break;
    case "admfaqs":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Faqs");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admfaqs";
        break;
    case "admfaq":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Faqs");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admfaq";
        break;
    case "topcat":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Topics categories");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/topcat";
        break;
    case "topcats":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Topics categories");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/topcats";
        break;
    case "admfms":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("File manager");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admfms";
        break;
    case "admfm":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("File manager");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admfm";
        break;
    case "admindex":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Search indexes");
        if( bab_isUserLogged() && (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0))
            $incl = "admin/indexfiles";
        break;
    case "topman":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Managed topics");
        if( bab_isUserLogged())
            $incl = "topman";
        break;
    case "topics":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Topics categories");
        if( bab_isUserLogged())
            $incl = "admin/topics";
        break;
    case "topic":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Topics categories");
        if( bab_isUserLogged())
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
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/forums";
        break;
    case "forum":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Forums");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/forum";
        break;
    case "admcals":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Calendar");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admcals";
        break;
    case "admcal":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Calendar");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admcal";
        break;
    case "admocs":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Organization chart");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admocs";
        break;
    case "admoc":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Organization chart");
        if( bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admoc";
        break;
    case "sites":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Sites");
        if( bab_isUserLogged() && bab_isUserAdministrator())
            $incl = "admin/sites";
        break;
    case "site":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Sites");
        if( bab_isUserLogged() && bab_isUserAdministrator())
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
        if(bab_isUserLogged() && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
            $incl = "admin/admdir";
        break;
    case "delegat":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Delegation");
        if( bab_isUserLogged() && bab_isUserAdministrator())
            $incl = "admin/delegat";
        break;
    case "admstats":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Statistics");
        if( bab_isUserLogged() && bab_isUserAdministrator())
            $incl = "admin/admstats";
        break;
    case "admthesaurus":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Thesaurus");
        if( bab_isUserLogged() && bab_isUserAdministrator())
            $incl = "admin/admthesaurus";
        break;

    case "delegusr":
        $babLevelOne = bab_translate("Administration");
        $babLevelTwo = bab_translate("Delegation");
        if( bab_isUserLogged())
            $incl = "delegusr";
        break;

    case "delegation":
        $incl = "delegation";
        break;
    case "options":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Options");
        if( bab_isUserLogged())
            $incl = "options";
        break;
    case "confcals":
        if( bab_isUserLogged())
            $incl = "confcals";
        break;
    case "confcal":
        if( bab_isUserLogged())
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
        if( bab_isUserLogged())
            $incl = "event";
        break;
    case "calopt":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Calendar");
           $incl = "calopt";
        break;
    case "sectopt":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Options");
        if( bab_isUserLogged())
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
        if( bab_isUserLogged())
            $incl = "lusers";
        break;
    case "selector":
        $babLevelOne = "";
        $babLevelTwo = "";
        if( bab_isUserLogged())
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
        
    /*
    case "charts":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Charts");
        $incl = "charts";
        break;
    case "chart":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Charts");
        include $GLOBALS['babInstallPath']."chart.php";
        exit;
        break;
    case "frchart":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Charts");
        include $GLOBALS['babInstallPath']."frchart.php";
        exit;
        break;
    case "fltchart":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Charts");
        include $GLOBALS['babInstallPath']."fltchart.php";
        exit;
        break;
    case "flbchart":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Charts");
        include $GLOBALS['babInstallPath']."flbchart.php";
        exit;
        break;
    */
        
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
        if( bab_isUserLogged() && bab_notesAccess())
            $incl = "notes";
        break;
    case "note":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Notes");
        if( bab_isUserLogged() && bab_notesAccess())
            $incl = "note";
        break;
    case "contacts":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Contacts");
        if( bab_isUserLogged() && bab_contactsAccess())
            $incl = "contacts";
        break;
    case "contact":
        $babLevelOne = bab_translate("User's section");
        $babLevelTwo = bab_translate("Contacts");
        if( bab_isUserLogged() && bab_contactsAccess())
            {
            include $GLOBALS['babInstallPath']."contact.php";
            exit;
            }
        break;
    case "address":
        if( bab_isUserLogged())
            {
            include $GLOBALS['babInstallPath']."address.php";
            exit;
            }
        break;
    case "lsa":
        if( bab_isUserLogged())
            {
            include $GLOBALS['babInstallPath']."lsa.php";
            exit;
            }
        break;
    case "month":
        include $GLOBALS['babInstallPath']."month.php";
        exit;
        break;
    case "images":
        include $GLOBALS['babInstallPath']."images.php";
        exit;
        break;
    case "version":
        include $GLOBALS['babInstallPath']."version.php";
        exit;
        break;
    case "statproc": // deprecated: use LibTimer instead to update stats
        include $GLOBALS['babInstallPath']."statproc.php";
        break;
    case "calnotif":
        include $GLOBALS['babInstallPath']."calnotif.php";
        exit;
        break;
    case "editorarticle":
        include $GLOBALS['babInstallPath']."editorarticle.php";
        exit;
        break;
    case "editorfaq":
        include $GLOBALS['babInstallPath']."editorfaq.php";
        exit;
        break;
    case "editorovml":
        include $GLOBALS['babInstallPath']."editorovml.php";
        exit;
        break;
    case "editorcontdir":
        include $GLOBALS['babInstallPath']."editorcontdir.php";
        exit;
        break;
    case 'editorfunctions':
        include $GLOBALS['babInstallPath']."editorfunctions.php";
        exit;
        break;
    case "selectcolor":
        include $GLOBALS['babInstallPath']."selectcolor.php";
        exit;
        break;
    case "imgget":
        include $GLOBALS['babInstallPath']."imgget.php";
        exit;
        break;
    case "link":
        include $GLOBALS['babInstallPath']."link.php";
        exit;
        break;
    case "oml":
        $incl = "oml";
        break;
    case "omlsoap":
        include $GLOBALS['babInstallPath']."omlsoap.php";
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
    /*
    case 'admTskMgr':
        $incl = 'admin/tmtaskmanager';
        break;
    case 'usrTskMgr':
        $incl = 'tmtaskmanager';
        break;
    */
        
    case 'charset':
        $incl = 'admin/charset';
        break;
    case "menu":
        include $GLOBALS['babInstallPath']."menu.php";
        break;
    case 'csrfprotect':
        die(bab_getInstance('bab_CsrfProtect')->getToken());
        break;
        
    case 'search':
        /**
         * forward to search addon for backward compatibility
         * @deprecated User tg=addon/search/main instead
         */

        $searchTg = bab_functionality::get('SearchUi')->getTg();
        if ($module = bab_getAddonFilePathFromTg($searchTg, $babWebStat)) {
            require_once $module;
        }
        break;

    default:
        $babLevelOne = "";
        $babLevelTwo = "";

        $babWebStat->module("entry");

        if ($module = bab_getAddonFilePathFromTg(bab_rp('tg'), $babWebStat)) {
            if (!file_exists($module)) {
                bab_pageNotFound();
            }
            require_once $module;
        } else {
            
            if ('' !== bab_rp('tg', '')) {
                bab_pageNotFound();
            }
            
            if ($home = bab_functionality::get('Home')) {
                /*@var $home Func_Home */
                
                $home->setSitemapPosition();
                $home->includePage();
            }
        }
        break;
    }


if( !empty($incl))
    {
    include $GLOBALS['babInstallPath']."$incl.php";
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
