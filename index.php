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
function bab_unset(&$arr)
{
	unset($arr['babInstallPath'], $arr['babDBHost'], $arr['babDBLogin'], $arr['babDBPasswd'], $arr['babDBName']);
	unset($arr['babUrl'], $arr['babFileNameTranslation'], $arr['babVersion']);
	unset($GLOBALS['babTmp']);
}

if(!session_id())
	{
		session_name(sprintf("OV%u", crc32($babUrl)));
		session_start();
	}
if (!session_is_registered('BAB_SESS_NICKNAME')) { session_register("BAB_SESS_NICKNAME"); $BAB_SESS_NICKNAME = ""; }
if (!session_is_registered('BAB_SESS_USER')) { session_register("BAB_SESS_USER"); $BAB_SESS_USER = ""; }
if (!session_is_registered('BAB_SESS_FIRSTNAME')) { session_register("BAB_SESS_FIRSTNAME"); $BAB_SESS_FIRSTNAME = ""; }
if (!session_is_registered('BAB_SESS_LASTNAME')) { session_register("BAB_SESS_LASTNAME"); $BAB_SESS_LASTNAME = ""; }
if (!session_is_registered('BAB_SESS_EMAIL')) { session_register("BAB_SESS_EMAIL"); $BAB_SESS_EMAIL = ""; }
if (!session_is_registered('BAB_SESS_USERID')) { session_register("BAB_SESS_USERID"); $BAB_SESS_USERID = ""; }
if (!session_is_registered('BAB_SESS_HASHID')) { session_register("BAB_SESS_HASHID"); $BAB_SESS_HASHID = ""; }
if (!session_is_registered('BAB_SESS_GROUPID')) { session_register("BAB_SESS_GROUPID"); $BAB_SESS_GROUPID = ""; }
if (!session_is_registered('BAB_SESS_GROUPNAME')) { session_register("BAB_SESS_GROUPNAME"); $BAB_SESS_GROUPNAME = ""; }
if (!empty($_GET))
	$babTmp =& $_GET;
else  if (!empty($HTTP_GET_VARS)) 
	$babTmp =& $HTTP_GET_VARS;
if( isset($babTmp)) { extract($babTmp, EXTR_SKIP); bab_unset($babTmp); }

if (!empty($_POST))
	$babTmp =& $_POST;
else  if (!empty($HTTP_POST_VARS)) 
	$babTmp =& $HTTP_POST_VARS;
if( isset($babTmp)) { extract($babTmp, EXTR_SKIP); bab_unset($babTmp); }

if (!empty($_SESSION))
	$babTmp =& $_SESSION;
else  if (!empty($HTTP_SESSION_VARS)) 
	$babTmp =& $HTTP_SESSION_VARS;
if( isset($babTmp)) { extract($babTmp, EXTR_SKIP); bab_unset($babTmp); }

if (!empty($_SERVER))
	$babTmp =& $_SERVER;
else  if (!empty($HTTP_SERVER_VARS)) 
	$babTmp =& $HTTP_SERVER_VARS;
if( isset($babTmp)) { extract($babTmp, EXTR_SKIP); bab_unset($babTmp); }

bab_unset($_REQUEST);
bab_unset($_SERVER);
bab_unset($_SESSION);
bab_unset($_COOKIE);

if (!empty($_FILES))
	{
	while (list($name, $value) = each($_FILES))
		{
		$$name = $value['tmp_name'];
		$file = $name."_size";
		$$file = $value['size'];
		$file = $name."_name";
		$$file = $value['name'];
		$file = $name."_type";
		$$file = $value['type'];
		}
	}
	else if (!empty($HTTP_POST_FILES))
		{
		while (list($name, $value) = each($HTTP_POST_FILES))
			{
			$$name = $value['tmp_name'];
			$file = $name."_size";
			$$file = $value['size'];
			$file = $name."_name";
			$$file = $value['name'];
			$file = $name."_type";
			$$file = $value['type'];
			}
	}

$babUserPassword = '';

if( !isset($GLOBALS['babMkdirMode']))
	{
	$GLOBALS['babMkdirMode'] = 0770;
	}

if( !isset($GLOBALS['babUmaskMode']))
	{
	$GLOBALS['babUmaskMode'] = 0;
	}

$babPhpSelf = substr($PHP_SELF,-strpos(strrev($PHP_SELF),'/'));
$babUrlScript = $babUrl.$babPhpSelf;
$babAddonsPath = $GLOBALS['babInstallPath']."addons/";
$babSiteName = substr($babSiteName, 0, 30);

if( !isset($tg))
	$tg = '';

include_once "base.php";
include_once $babInstallPath."utilit/defines.php";
include_once $babInstallPath."utilit/dbutil.php";
$babDB = new babDatabase();
include_once $babInstallPath."utilit/statincl.php";
$babWebStat =& new bab_WebStatEvent();

include $babInstallPath."utilit/utilit.php";
unset($BAB_SESS_LOGGED);

if( $tg != "version" || !isset($idx) || $idx != "upgrade")
	{
	bab_updateSiteSettings();
	if ( isset($babNTauth) && !isset($_REQUEST['babNTauth']) && $babNTauth ) include $babInstallPath."utilit/ntident.php";
	if ($GLOBALS['babCookieIdent'] === true) include $babInstallPath."utilit/cookieident.php";
	bab_isUserLogged();
	bab_updateUserSettings();
	$babLangFilter->translateTexts();
	}
else
	{
	if (!isset($babLanguage))
		$babLanguage = 'en';

	if (!isset($babStyle))
		$babStyle = 'ovidentia.css';

	if (!isset($babSkin))
		$babSkin = 'ovidentia';
	}

$babSkinPath = $babInstallPath."skins/".$babSkin."/";
if(!is_dir($babSkinPath)) {
	$babSkinPath = $babInstallPath."skins/".'ovidentia'."/";
	if(!is_dir($babSkinPath)) {
		$folder = opendir($babInstallPath.'skins/');
		while (false!==($file = readdir($folder))) {
			if($file == '.' or $file == '..') break;
			if(is_dir($file)) {
				$babSkinPath = $babInstallPath."skins/".$file."/";
				break;
			}
		}
		closedir($folder);
	}
}
$babScriptPath = $babInstallPath."scripts/";
$babEditorImages = $babInstallPath."scripts/".$babLanguage."/";
$babOvidentiaJs = $babScriptPath."ovidentia.js";
$babOvmlPath = "skins/".$GLOBALS['babSkin']."/ovml/";

$babMonths = array(1=>bab_translate("January"), bab_translate("February"), bab_translate("March"), bab_translate("April"),
                        bab_translate("May"), bab_translate("June"), bab_translate("July"), bab_translate("August"),
                        bab_translate("September"), bab_translate("October"), bab_translate("November"), bab_translate("December"));

$babShortMonths = array();
foreach($babMonths as $key => $val )
	{
	$sm = substr($val, 0 , 3);
	if( count($babShortMonths) == 0 || !in_array($sm, $babShortMonths))
		{
		$babShortMonths[$key] = $sm;
		}
	else
		{
		$m=4;
		while( in_array($sm, $babShortMonths) && $m < strlen($val))
			{
			$sm = substr($val, 0 , $m++);
			}

		$babShortMonths[$key] = $sm;			
		}
	}

$babDays = array(bab_translate("Sunday"), bab_translate("Monday"),
				bab_translate("Tuesday"), bab_translate("Wednesday"), bab_translate("Thursday"),
				bab_translate("Friday"), bab_translate("Saturday"));

$babDayType = array(1=>bab_translate("Whole day"), bab_translate("Morning"), bab_translate("Afternoon"));

$babSearchUrl = "abcdefgh";
$babSearchItems = array ('a' => bab_translate("Articles"), 'b' => bab_translate("Forums"), 'c' => bab_translate("Faq"), 'd' => bab_translate("Notes"), 'e' => bab_translate("File manager"), 'f' => bab_translate("Contacts"), 'g' => bab_translate("Directories"), 'h' => bab_translate("Calendar"));  

$babJs = $GLOBALS['babScriptPath']."ovidentia.js";
$babCssPath = bab_getCssUrl();
class babDummy { var $duumy; }
$babDummy = new babDummy();

$babCss = bab_printTemplate($babDummy, "config.html", "babCss");
$babMeta = bab_printTemplate($babDummy, "config.html", "babMeta");
$babsectionpuce = bab_printTemplate($babDummy, "config.html", "babSectionPuce");
$babsectionbullet = bab_printTemplate($babDummy, "config.html", "babSectionBullet");
if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
	$babIE = 1;
else
	$babIE = 0;

function printBody()
	{
	class tpl
	{
		var $babLogoLT;
		var $babLogoRT;
		var $babBanner;
		var $sitename;
		var $style;
		var $script;
		var $babSlogan;
		var $login;
		var $logurl;
		var $babLogoLB;
		var $babLogoRB;
		var $enabled;
		var $menuclass;
		var $menuattribute;
		var $menuurl;
		var $menutext;
		var $menukeys = array();
		var $menuvals = array();
		var $arrsectleft = array();
		var $nbsectleft;
		var $arrsectright = array();
		var $nbsectright;
		var $content;
		var $message;
		var $version;
		var $search;
		var $bsearch;
		var $searchurl;

		function tpl()
			{
			global $babBody, $BAB_SESS_LOGGED, $babSiteName,$babSlogan,$babStyle, $babSearchUrl;
			$this->version = isset($GLOBALS['babVersion']) ? $GLOBALS['babVersion'] : '';
			$this->babLogoLT = "";
			$this->babLogoRT = "";
			$this->babLogoLB = "";
			$this->babLogoRB = "";
			$this->babBanner = "";

			$this->style = $babStyle;

			$this->babLogoLT = bab_printTemplate($this, "config.html", "babLogoLT");
			$this->babLogoRT = bab_printTemplate($this, "config.html", "babLogoRT");
			$this->babLogoLB = bab_printTemplate($this, "config.html", "babLogoLB");
			$this->babLogoRB = bab_printTemplate($this, "config.html", "babLogoRB");
			$this->babBanner = bab_printTemplate($this, "config.html", "babBanner");
			$this->script = $babBody->script;
			$this->home = bab_translate("Home");
			$this->homeurl = $GLOBALS['babUrlScript'];
			if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED == true )
				{
				$this->login = bab_translate("Logout");
				$this->logurl = $GLOBALS['babUrlScript']."?tg=login&amp;cmd=signoff";
				}
			else
				{
				// Variables redeclarations for IIS (bug or default config)
				if (!isset($GLOBALS['BAB_SESS_FIRSTNAME'])) $GLOBALS['BAB_SESS_FIRSTNAME'] = "";
				if (!isset($GLOBALS['BAB_SESS_LASTNAME'])) $GLOBALS['BAB_SESS_LASTNAME'] = "";
				$this->login = bab_translate("Login");
				$this->logurl = $GLOBALS['babUrlScript']."?tg=login&amp;cmd=signon";
				}

			$this->search = bab_translate("Search");
			if( !empty($babSearchUrl))
				{
				$this->searchurl = $GLOBALS['babUrlScript']."?tg=search&amp;pat=".$babSearchUrl;
				$this->bsearch = 1;
				}
			else
				{
				$this->bsearch = 0;
				}

			if (!isset($GLOBALS['babMarquee']) || $GLOBALS['babMarquee'] == '')
				$this->babSlogan = $babSlogan;
			else
				$this->babSlogan = $GLOBALS['babMarquee'];
			$this->menukeys = array_keys($babBody->menu->items);
			$this->menuvals = array_values($babBody->menu->items);
			$this->menuitems = count($this->menukeys);

			$this->nbsectleft = 0;
			$this->nbsectright = 0;
			foreach($babBody->sections as $sec)
				{
				if(  $sec->isVisible())
					{
					if( $sec->getPosition() == 0 )
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

			$debug = bab_getDebug();
			if (false === $debug)
				$debug = '';

			$this->content = $debug.$babBody->printout();
			$this->message = $babBody->message;
			$this->title = $babBody->title;
			$this->msgerror = $babBody->msgerror;
			}

		function getNextMenu()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->menuitems)
				{
				if(!strcmp($this->menukeys[$i], $babBody->menu->curItem))
					{
					$this->menuclass = "BabMenuCurArea";
					}
				else
					$this->menuclass = "BabMenuArea";
					 
				$this->menutext = $this->menuvals[$i]["text"];
				if( $this->menuvals[$i]["enabled"] == false)
					$this->enabled = 0;
				else
					{
					$this->enabled = 1;
					if( !empty($this->menuvals[$i]["attributes"]))
						{
						$this->menuattribute = $this->menuvals[$i]["attributes"];
						}
					else
						{
						$this->menuattribute = "";
						}
					$this->menuurl = htmlentities($this->menuvals[$i]["url"]);
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getNextSectionLeft()
			{
			global $babBody;
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

		function getNextSectionRight()
			{
			global $babBody;
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
	}

	$temp = new tpl();
	echo bab_printTemplate($temp,"page.html", "");
	}


switch($tg)
	{
	case "login":
		$babLevelOne = bab_translate("Home");
		$babLevelTwo = bab_translate("Login");
		$incl = "login";
		break;
	case "sections":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sections");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/sections";
		break;
	case "section":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sections");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/section";
		break;
	case "register":
		$babLevelOne = bab_translate("Home");
		$babLevelTwo = bab_translate("Login");
		$incl = "admin/register";
		break;
	case "users":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Users");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/users";
		break;
	case "user":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Users");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/user";
		break;
	case "groups":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/groups";
		break;
	case "group":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/group";
		break;
	case "setsofgroups":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sets of groups");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/setsofgroups";
		break;
	case "profiles":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Profiles");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/profiles";
		break;
	case "admfaqs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Faqs");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfaqs";
		break;
	case "admfaq":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Faqs");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfaq";
		break;
	case "topcat":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/topcat";
		break;
	case "topcats":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Topics categories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/topcats";
		break;
	case "apprflow":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Approbations");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/apprflow";
		break;
	case "admfms":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("File manager");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfms";
		break;
	case "admfm":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("File manager");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admfm";
		break;
	case "admindex":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Search indexes");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0))
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
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/forums";
		break;
	case "forum":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Forums");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/forum";
		break;
	case "admvacs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Vacation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin )
			$incl = "admin/admvacs";
		break;
	case "admvac":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Vacation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin)
			$incl = "admin/admvac";
		break;
	case "admcals":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Calendar");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admcals";
		break;
	case "admcal":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Calendar");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admcal";
		break;
	case "admocs":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Organization chart");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admocs";
		break;
	case "admoc":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Organization chart");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admoc";
		break;
	case "sites":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sites");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin)
			$incl = "admin/sites";
		break;
	case "site":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Sites");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin)
			$incl = "admin/site";
		break;
	case "addons":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Add-ons");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin)
			$incl = "admin/addons";
		break;
	case "admdir":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Directories");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/admdir";
		break;
	case "delegat":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Delegation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin)
			$incl = "admin/delegat";
		break;
	case "admstats":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Statistics");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->isSuperAdmin)
			$incl = "admin/admstats";
		break;
	case "aclug":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = "";
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
			$incl = "admin/aclug";
		break;
	case "delegusr":
		$babLevelOne = bab_translate("Administration");
		$babLevelTwo = bab_translate("Delegation");
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && $babBody->dgAdmGroups > 0)
			$incl = "delegusr";
		break;
	case "options":
		$babLevelOne = bab_translate("User's section");
		$babLevelTwo = bab_translate("Options");
		if( $BAB_SESS_LOGGED)
    		$incl = "options";
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
	case "stat":
		$babLevelOne = bab_translate("Statistics");
		$incl = "stat";
		break;
	case "statconf":
		$babLevelOne = bab_translate("Statistics");
		$incl = "statconf";
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
	case "htmlarea":
		include $babInstallPath."htmlarea.php";
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
	case "accden":
		$babBody->msgerror = bab_translate("Access denied");
		/* no break; */
	case "entry":
		$babLevelOne = bab_translate("Home");
		$babLevelTwo = bab_translate("");
		$incl = "entry";
		break;
	default:
		$babLevelOne = "";
		$babLevelTwo = "";
		$incl = "entry";
		$babWebStat->module($incl);
		$arr = explode("/", $tg);
		if( sizeof($arr) >= 3 && $arr[0] == "addon")
			{
			if (!is_numeric($arr[1]))
				{
				foreach($GLOBALS['babBody']->babaddons as $k => $v)
					{
					if ($v['title'] == $arr[1])
						{
						$arr[1] = $k;
						break;
						}
					}
				}
			if(bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr[1]))
				{
				if( isset($babBody->babaddons[$arr[1]]))
					{
					$row = &$babBody->babaddons[$arr[1]];

					$incl = "addons/".$row['title'];
					if( is_dir( $GLOBALS['babInstallPath'].$incl))
						{
						$module = "";
						for($i = 2; $i < sizeof($arr); $i++)
							$module .= "/".$arr[$i];
						$GLOBALS['babAddonFolder'] = $row['title'];
						$GLOBALS['babAddonTarget'] = "addon/".$arr[1];
						$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$arr[1]."/";
						$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
						$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
						$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$row['title']."/";
						$babWebStat->addon($row['title']);
						$babWebStat->module($module);
						$incl .= $module;
						}
					else
						$incl = "entry";
					}
				else
					$babBody->msgerror = bab_translate("The addon is disabled or not installed");
				}
			else
				$babBody->msgerror = bab_translate("Access denied");
			}
		else
		{
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

$babBody->loadSections();
printBody();
unset($tg);
?>