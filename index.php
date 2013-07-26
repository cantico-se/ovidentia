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


// addon controller

if (isset($_REQUEST['addon']))
{
	include_once $babInstallPath.'utilit/dbutil.php';
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	
	$babDB = new babDatabase();
	$babDB->db_setCharset();
	
	$addon = explode('.',$_REQUEST['addon']);

	if($id_addon = bab_addonsInfos::getAddonIdByName($addon[0]))
	{
		$row = bab_addonsInfos::getDbRow($id_addon);
		$incl = "addons/".$row['title'];
		$incl .= "/".preg_replace("/[^A-Za-z0-9_\-]/", "", $addon[1]);
		
		include $babInstallPath.$incl.'.php';
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



if (!isset($_SERVER['HTTP_HOST']) && isset($_SERVER["argv"][1])) {
	parse_str($_SERVER["argv"][1], $_GET);
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

/*
 * Get the name of the PHP file of script curently executed (default : index.php)
 *
 * @return string
 */
function bab_getSelf() {
	$pos = mb_strrpos($_SERVER['PHP_SELF'], '/');

	if (false === $pos) {
		return $_SERVER['PHP_SELF'];
	}

	return mb_substr($_SERVER['PHP_SELF'], $pos +1);
}

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
		$babLanguage = 'en';
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
$babOvmlPath = "skins/".$GLOBALS['babSkin']."/ovml/";




/*
 * Display the current page : head, metas, sections, body...
 */
function printBody()
{
	class tpl
	{
		public $sitename;
		public $style;
		public $script;
		public $babSlogan;
		public $login;
		public $logurl;
		public $enabled;
		public $menuclass;
		public $menuattribute;
		public $menuurl;
		public $menutext;
		public $menukeys = array();
		public $menuvals = array();
		public $arrsectleft = array();
		private $nbsectleft = null;
		public $arrsectright = array();
		private $nbsectright = null;
		public $message;
		public $version;
		public $search;
		public $searchurl;
		public $sContent;
		public $styleSheet;

		private	$babLogoLT = null;
		private	$babLogoRT = null;
		private	$babLogoLB = null;
		private	$babLogoRB = null;
		private	$babBanner = null;
		private $babHeadStyleSheets = null;
		
		private $sitemapPosition = null;

		public function __construct()
		{
			global $babBody, $BAB_SESS_LOGGED, $babSiteName, $babSlogan, $babStyle;
			$this->version		= isset($GLOBALS['babVersion']) ? $GLOBALS['babVersion'] : '';
			$this->sContent		= 'text/html; charset=' . bab_charset::getIso();

			$this->style = $babStyle;

			$this->script = $babBody->script;
			$this->home = bab_translate("Home");
			$this->homeurl = $GLOBALS['babUrlScript'];
			$this->tpowered = bab_translate("Powered by Ovidentia,");
			$this->tgroupware = bab_translate("Groupware Portal");
			$this->ttrademark = bab_translate('Ovidentia is a registered trademark by');
			if (isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED == true) {
				$this->login = bab_translate("Logout");
				$this->logurl = $GLOBALS['babUrlScript'].'?tg=login&amp;cmd=signoff';
			} else {
				// Variables redeclarations for IIS (bug or default config)
				if (!isset($GLOBALS['BAB_SESS_FIRSTNAME'])) $GLOBALS['BAB_SESS_FIRSTNAME'] = '';
				if (!isset($GLOBALS['BAB_SESS_LASTNAME'])) $GLOBALS['BAB_SESS_LASTNAME'] = '';
				$this->login = bab_translate("Login");
				$this->logurl = $GLOBALS['babUrlScript'].'?tg=login&amp;cmd=signon';
			}

			$this->search = bab_translate("Search");
			$this->searchurl = $GLOBALS['babUrlScript'].'?tg=search';

			if (!isset($GLOBALS['babMarquee']) || $GLOBALS['babMarquee'] == '') {
				$this->babSlogan = $babSlogan;
			} else {
				$this->babSlogan = $GLOBALS['babMarquee'];
			}
			$this->menukeys = array_keys($babBody->menu->items);
			$this->menuvals = array_values($babBody->menu->items);

			if (isset($GLOBALS['babHideMenu'])) {
				$tg = bab_rp('tg', '');
				$idx = bab_rp('idx', '');

				if ($tg && isset($GLOBALS['babHideMenu'][$tg]) && (count($GLOBALS['babHideMenu'][$tg]) == 0  || in_array($idx, $GLOBALS['babHideMenu'][$tg]))) {
					$this->menuitems = 0;
				} else {
					$this->menuitems = count($this->menukeys);
				}
			} else {
				$this->menuitems = count($this->menukeys);
			}

			$this->message = $babBody->message;
			$this->title = $babBody->title;
			$this->msgerror = $babBody->msgerror;
			
			
		}
		
		
		/**
		 * Template method for messages
		 */
		public function getNextMessage()
		{
			global $babBody;
			
			if (list($key, $message) = each($babBody->messages))
			{
				$this->message = bab_toHtml($message);
				return true;
			}
			
			return false;
		}


		/**
		 * These getter are used to do some initialization stuff when some variables are
		 * accessed for the first time.
		 *
		 * @param string $propertyName
		 * @return mixed
		 */
		public function __get($propertyName)
		{
			global $babBody;

			switch ($propertyName) {

				case 'content':
					
					$this->content = '';
					
					$debug = bab_getDebug();
					if (false !== $debug) {
						$this->content  .= $debug;
					}
					
					// if message not added to page by skin, add them to content
					while($this->getNextMessage())
					{
						$this->content .= sprintf('<div class="bab-page-message">%s</div>', $this->message);
					}
					
					$this->content .= $babBody->printout();
					return $this->content;

				// The values of nbsectleft and nbsectright are only valid after loadsections has been called.
				case 'nbsectleft':
					$this->loadsections();
					return $this->nbsectleft;

				case 'nbsectright':
					$this->loadsections();
					return $this->nbsectright;

				case 'babLogoLT':
					if (!isset($this->babLogoLT)) {
						$this->babLogoLT = bab_printTemplate($this, 'config.html', 'babLogoLT');
					}
					return $this->babLogoLT;

				case 'babLogoRT':
					if (!isset($this->babLogoRT)) {
						$this->babLogoRT = bab_printTemplate($this, 'config.html', 'babLogoRT');
					}
					return $this->babLogoRT;

				case 'babLogoLB':
					if (!isset($this->babLogoLB)) {
						$this->babLogoLB = bab_printTemplate($this, 'config.html', 'babLogoLB');
					}
					return $this->babLogoLB;

				case 'babLogoRB':
					if (!isset($this->babLogoRB)) {
						$this->babLogoRB = bab_printTemplate($this, 'config.html', 'babLogoRB');
					}
					return $this->babLogoRB;

				case 'babBanner':
					if (!isset($this->babBanner)) {
						$this->babBanner = bab_printTemplate($this, 'config.html', 'babBanner');
					}
					return $this->babBanner;

				case 'babHeadStyleSheets':
					if (!isset($this->babHeadStyleSheets)) {
						foreach ($babBody->styleSheet as $sheet) {
							$this->babHeadStyleSheets .= '<link rel="stylesheet" type="text/css" href="' . bab_toHtml($GLOBALS['babInstallPath'] . 'styles/' . $sheet) . '" />' . "\n";
						}
						$babBody->styleSheet = array();
					}
					return $this->babHeadStyleSheets;

				case 'sitemapPosition':
					if (null === $this->sitemapPosition)
					{
						$func = bab_functionality::get('Ovml/Function/SitemapPosition');
						$this->sitemapPosition = $func->toString();
					}
					return $this->sitemapPosition;
					
					
				case 'imageUrl':
				
					$head = bab_getInstance('babHead');
					if ($imageUrl = $head->getImageUrl())
					{
						return bab_toHtml($imageUrl);
					}
					return '';
					
					
				case 'canonicalUrl':
					
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($canonicalUrl = $sitemapItem->getCanonicalUrl())
						{
							return bab_toHtml($canonicalUrl);
						}
					}
					
					$head = bab_getInstance('babHead');
					if ($canonicalUrl = $head->getCanonicalUrl())
					{
						return bab_toHtml($canonicalUrl);
					}
					return '';
					
					
				case 'pageTitle':
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($title = $sitemapItem->getPageTitle(true))
						{
							return bab_toHtml($title);
						}
					}
					
					// no sitemap node, use title provided by script 
					$head = bab_getInstance('babHead');
					if ($title = $head->getTitle())
					{
						return bab_toHtml($title);
					}

					// use the sitemap root node page title
					if ($root = bab_siteMap::getVisibleRootNodeSitemapItem()) {
						return bab_toHtml($root->getPageTitle());
					}
					
					return '';
					
					
					
				case 'pageDescription':
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($description = $sitemapItem->getPageDescription(true))
						{
							return bab_toHtml($description);
						}
					}
				
					$head = bab_getInstance('babHead');
					if ($description = $head->getDescription())
					{
						return bab_toHtml($description);
					}
					
					if ($root = bab_siteMap::getVisibleRootNodeSitemapItem()) {
						return bab_toHtml($root->getPageDescription());
					}
					
					return '';
					
					
					
				case 'pageKeywords':
				case 'sitemapPageKeywords':
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($keywords = $sitemapItem->getPageKeywords(true))
						{
							return bab_toHtml($keywords);
						}
					}
				
					$head = bab_getInstance('babHead');
					if ($keywords = $head->getKeywords())
					{
						return bab_toHtml($keywords);
					}
					
					if ($root = bab_siteMap::getVisibleRootNodeSitemapItem()) {
						return bab_toHtml($root->getPageKeywords());
					}
					
					return '';

				default:
					return $this->$propertyName;
			}
		}
		
		/**
		 * 
		 * @return bab_sitemapItem
		 */
		private function getSitemapItem()
		{
			
			if (($rootNode = bab_siteMap::getFromSite()) 
					&& ($currentNodeId = bab_Sitemap::getPosition())
					&& ($currentNode = $rootNode->getNodeById($currentNodeId))
					&& ($sitemapItem = $currentNode->getData()) ) {
				// if on a positioned sitemap node
				return $sitemapItem;
			}
			
			return null;
		}


		/**
		 * Isset has to be overriden as well for some variables.
		 *
		 * @param string $propertyName
		 * @return bool
		 */
		public function __isset($propertyName)
		{
			switch ($propertyName) {

				case 'content':
				case 'nbsectleft':
				case 'nbsectright':
				case 'babLogoLT':
				case 'babLogoRT':
				case 'babLogoLB':
				case 'babLogoRB':
				case 'babBanner':
				case 'babHeadStyleSheets':
				case 'sitemapPosition':
				case 'sitemapPageKeywords':
				case 'pageKeywords':
				case 'pageDescription':
				case 'pageTitle':
				case 'canonicalUrl':
				case 'imageUrl':
					return true;
			}
			return false;
		}


		public function getNextMenu()
		{
			global $babBody;

			static $i = 0;
			if ($i < $this->menuitems) {
				if (!strcmp($this->menukeys[$i], $babBody->menu->curItem)) {
					$this->menuclass = 'BabMenuCurArea';
				} else {
					$this->menuclass = 'BabMenuArea';
				}

				$this->menutext = $this->menuvals[$i]['text'];
				if( $this->menuvals[$i]['enabled'] == false) {
					$this->enabled = 0;
					if (!empty($this->menuvals[$i]['attributes'])) {
						$this->menuattribute = $this->menuvals[$i]['attributes'];
					} else {
						$this->menuattribute = "";
					}
				} else {
					$this->enabled = 1;
					if (!empty($this->menuvals[$i]['attributes'])) {
						$this->menuattribute = $this->menuvals[$i]['attributes'];
					} else {
						$this->menuattribute = "";
					}
					$this->menuurl = bab_toHtml($this->menuvals[$i]['url']);
				}
				$i++;
				return true;
			} else {
				return false;
			}
		}


		private function loadsections()
		{
			global $babBody;

			if (null !== $this->nbsectleft) {
				return;
			}

			$babBody->loadSections();

			$this->nbsectleft = 0;
			$this->nbsectright = 0;
			foreach($babBody->sections as $sec)
			{
				if ($sec->isVisible())
				{
					if ($sec->getPosition() == 0)
					{
						$this->arrsectleft[$this->nbsectleft] = $sec;
						$this->nbsectleft++;
					}
					else
					{
						$this->arrsectright[$this->nbsectright] = $sec;
						$this->nbsectright++;
					}
				}
			}
		}


		public function getNextSectionLeft()
		{
			$this->loadsections();
			static $i = 0;
			if( $i < $this->nbsectleft)
			{
				$sec = $this->arrsectleft[$i];
				$this->sectionleft = $sec->printout();
				$i++;
				return true;
			}
			else
				return false;
		}

		public function getNextSectionRight()
		{
			$this->loadsections();
			static $i = 0;
			if( $i < $this->nbsectright)
			{
				$sec = $this->arrsectright[$i];
				$this->sectionright = $sec->printout();
				$i++;
				return true;
			}
			else
				return false;
		}


		public function getNextStyleSheet()
		{
			global $babBody;

			list(,$this->styleSheet) = $babBody->getnextstylesheet();
			if ($this->styleSheet) {
				$this->styleSheet = bab_getStaticUrl().$GLOBALS['babInstallPath'] . 'styles/' . bab_toHtml($this->styleSheet);
				return true;
			}
			return false;
		}
	}
	
	// we make sure that the sitemap is created before final processing of the page
	// because sitemap reconstruction errors are hidden if the reconstruction process is done in an eval
	bab_sitemap::get();

	$temp = new tpl();
	echo bab_printTemplate($temp, 'page.html', '');
}





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
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/sections";
		break;
	case "section":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sections");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/section";
		break;
	case "users":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Users");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/users";
		break;
	case "user":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Users");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/user";
		break;
	case "groups":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/groups";
		break;
	case "group":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/group";
		break;
	case "setsofgroups":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sets of groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/setsofgroups";
		break;
	case "profiles":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Profiles");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/profiles";
		break;
	case "admfaqs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Faqs");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfaqs";
		break;
	case "admfaq":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Faqs");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfaq";
		break;
	case "topcat":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/topcat";
		break;
	case "topcats":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/topcats";
		break;
	case "apprflow":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Approbations");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/apprflow";
		break;
	case "admfms":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("File manager");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfms";
		break;
	case "admfm":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("File manager");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfm";
		break;
	case "admindex":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Search indexes");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() && $babBody->currentAdmGroup == 0))
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
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/forums";
		break;
	case "forum":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Forums");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/forum";
		break;
	case "admvacs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Vacation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator() )
			$incl = "admin/admvacs";
		break;
	case "admcals":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Calendar");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admcals";
		break;
	case "admcal":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Calendar");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admcal";
		break;
	case "admocs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Organization chart");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
			$incl = "admin/admocs";
		break;
	case "admoc":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Organization chart");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
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
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || $babBody->currentAdmGroup != 0))
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
	case "vacuser":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Vacation");
		if( $BAB_SESS_LOGGED)
    		$incl = "vacuser";
		break;
	case "vacchart":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Vacation");
		if( $BAB_SESS_LOGGED)
    		$incl = "vacchart";
		break;
	case "directory":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Directories");
   		$incl = "directory";
		break;
	case "vacadm":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Vacations");
		if( $BAB_SESS_LOGGED)
    		$incl = "vacadm";
		break;
	case "vacadma":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Vacations");
		if( $BAB_SESS_LOGGED)
    		$incl = "vacadma";
		break;
	case "vacadmb":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Vacations");
		if( $BAB_SESS_LOGGED)
    		$incl = "vacadmb";
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
	case "search":
		$babLevelOne = bab_translate("Home");
		$babLevelTwo = bab_translate("Search");
		$incl = "search";
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
				
			
			if(!bab_isAddonAccessValid($arr[1])) {
				$addon_row = bab_addonsInfos::getDbRow($arr[1]);
				$addon = bab_getAddonInfosInstance($addon_row['title']);
				
				if ($addon->hasAccessControl() && $addon->isInstalled() && !$addon->isDisabled())
				{
					bab_requireAccess('bab_addons_groups', $arr[1], bab_translate('You must be logged in to access this page.'));
				}
				
				$babBody->addError(bab_translate("Access denied"));
				
			} else {
				
				$row = bab_addonsInfos::getDbRow($arr[1]);
				$incl = "addons/".$row['title'];
				$module = "/".preg_replace("/[^A-Za-z0-9_\-]/", "", $arr[2]);
				bab_setAddonGlobals($arr[1]);
				$babWebStat->addon($row['title']);
				$babWebStat->module($module);
				$incl .= $module;
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

printBody(); /* Display the current page : head, metas, sections, body... */