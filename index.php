<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
session_start();
if (!session_is_registered('BAB_SESS_NICKNAME')) { session_register("BAB_SESS_NICKNAME"); $BAB_SESS_NICKNAME = ""; }
if (!session_is_registered('BAB_SESS_USER')) { session_register("BAB_SESS_USER"); $BAB_SESS_USER = ""; }
if (!session_is_registered('BAB_SESS_EMAIL')) { session_register("BAB_SESS_EMAIL"); $BAB_SESS_EMAIL = ""; }
if (!session_is_registered('BAB_SESS_USERID')) { session_register("BAB_SESS_USERID"); $BAB_SESS_USERID = ""; }
if (!session_is_registered('BAB_SESS_HASHID')) { session_register("BAB_SESS_HASHID"); $BAB_SESS_HASHID = ""; }
if (!empty($_GET))
	$babTmp =& $_GET;
else  if (!empty($HTTP_GET_VARS)) 
	$babTmp =& $HTTP_GET_VARS;
if( !empty($babTmp)) extract($babTmp);
unset($babTmp);
if (!empty($_POST))
	$babTmp =& $_POST;
else  if (!empty($HTTP_POST_VARS)) 
	$babTmp =& $HTTP_POST_VARS;
if( !empty($babTmp)) extract($babTmp);
unset($babTmp);

if (!empty($_SESSION))
	$babTmp =& $_SESSION;
else  if (!empty($HTTP_SESSION_VARS)) 
	$babTmp =& $HTTP_SESSION_VARS;
if( !empty($babTmp)) extract($babTmp);
unset($babTmp);

if (!empty($_SERVER))
	$babTmp =& $_SERVER;
else  if (!empty($HTTP_SERVER_VARS)) 
	$babTmp =& $HTTP_SERVER_VARS;
if( !empty($babTmp)) extract($babTmp);
unset($babTmp);

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

include $babInstallPath."utilit/utilit.php";
unset($BAB_SESS_LOGGED);

$babPhpSelf = substr($PHP_SELF,-strpos(strrev($PHP_SELF),'/'));
$babUrlScript = $babUrl.$babPhpSelf;
$babAddonsPath = $GLOBALS['babInstallPath']."addons/";


if( !isset($tg) || isset($tg) && $tg != "version" )
	{
	bab_isUserLogged();
	bab_updateSiteSettings();
	bab_updateUserSettings();
	}

$babSkinPath = $babInstallPath."skins/".$babSkin."/";
$babScriptPath = $babInstallPath."scripts/";
$babEditorImages = $babInstallPath."scripts/".$babLanguage."/";
$babOvidentiaJs = $babScriptPath."ovidentia.js";

$babMonths = array(1=>bab_translate("January"), bab_translate("February"), bab_translate("March"), bab_translate("April"),
                        bab_translate("May"), bab_translate("June"), bab_translate("July"), bab_translate("August"),
                        bab_translate("September"), bab_translate("October"), bab_translate("November"), bab_translate("December"));

$babDays = array(bab_translate("Sunday"), bab_translate("Monday"),
				bab_translate("Tuesday"), bab_translate("Wednesday"), bab_translate("Thursday"),
				bab_translate("Friday"), bab_translate("Saturday"));

$babSearchUrl = "";
$babSearchItems = array ('a' => "Articles", 'b' => "Forums", 'c' => "Faq", 'd' => "Notes", 'e' => "Files", 'f' => "Contacts");  

$babJs = $GLOBALS['babScriptPath']."ovidentia.js";
$babCssPath = bab_getCssUrl();
class babDummy { var $duumy; }
$babDummy = new babDummy();

$babCss = bab_printTemplate($babDummy, "config.html", "babCss");
$babMeta = bab_printTemplate($babDummy, "config.html", "babMeta");
$babsectionpuce = bab_printTemplate($babDummy, "config.html", "babSectionPuce");
$babsectionbullet = bab_printTemplate($babDummy, "config.html", "babSectionBullet");

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
		var $slogan;
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
			$this->version = $GLOBALS['babVersion'];
			$this->babLogoLT = "";
			$this->babLogoRT = "";
			$this->babLogoLB = "";
			$this->babLogoRB = "";
			$this->babBanner = "";

			$this->babLogoLT = bab_printTemplate($this, "config.html", "babLogoLT");
			$this->babLogoRT = bab_printTemplate($this, "config.html", "babLogoRT");
			$this->babLogoLB = bab_printTemplate($this, "config.html", "babLogoLB");
			$this->babLogoRB = bab_printTemplate($this, "config.html", "babLogoRB");
			$this->babBanner = bab_printTemplate($this, "config.html", "babBanner");
			$this->script = $babBody->script;
			$this->home = bab_translate("Home");
			$this->homeurl = $GLOBALS['babUrlScript']."?tg=entry";
			if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED == true )
				{
				$this->login = bab_translate("Logout");
				$this->logurl = $GLOBALS['babUrlScript']."?tg=login&cmd=signoff";
				}
			else
				{
				$this->login = bab_translate("Login");
				$this->logurl = $GLOBALS['babUrlScript']."?tg=login&cmd=signon";
				}

			$this->search = bab_translate("Search");
			if( !empty($babSearchUrl))
				{
				$this->searchurl = $GLOBALS['babUrlScript']."?tg=search&pat=".$babSearchUrl;
				$this->bsearch = 1;
				}
			else
				{
				$this->bsearch = 0;
				}
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

			$this->content = $babBody->printout();
			$this->message = $babBody->message;
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
					$this->menuurl = $this->menuvals[$i]["url"];
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

if( !isset($tg))
	$tg = "entry";

switch($tg)
	{
	case "login":
		$incl = "login";
		break;
	case "sections":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/sections";
		break;
	case "section":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/section";
		break;
	case "register":
		$incl = "admin/register";
		break;
	case "users":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/users";
		break;
	case "user":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/user";
		break;
	case "groups":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/groups";
		break;
	case "group":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/group";
		break;
	case "admfaqs":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admfaqs";
		break;
	case "admfaq":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admfaq";
		break;
	case "topcat":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/topcat";
		break;
	case "topcats":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/topcats";
		break;
	case "topman":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "topman";
		break;
	case "topics":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "admin/topics";
		break;
	case "topic":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED)
			$incl = "admin/topic";
		break;
	case "topusr":
		$incl = "topusr";
		break;
	case "forums":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/forums";
		break;
	case "forum":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/forum";
		break;
	case "admvacs":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admvacs";
		break;
	case "admvac":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admvac";
		break;
	case "admcals":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admcals";
		break;
	case "admcal":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admcal";
		break;
	case "sites":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/sites";
		break;
	case "site":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/site";
		break;
	case "admfiles":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/admfiles";
		break;
	case "addons":
		if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
			$incl = "admin/addons";
		break;
	case "options":
		if( $BAB_SESS_LOGGED)
    		$incl = "options";
		break;
	case "mail":
		if( $BAB_SESS_LOGGED)
    		$incl = "mail";
		break;
	case "mailopt":
		if( $BAB_SESS_LOGGED)
    		$incl = "mailopt";
		break;
	case "maildoms":
		if( $BAB_SESS_LOGGED)
    		$incl = "maildoms";
		break;
	case "maildom":
		if( $BAB_SESS_LOGGED)
    		$incl = "maildom";
		break;
	case "confcals":
		if( $BAB_SESS_LOGGED)
    		$incl = "confcals";
		break;
	case "confcal":
		if( $BAB_SESS_LOGGED)
    		$incl = "confcal";
		break;
	case "calendar":
		if( $BAB_SESS_LOGGED)
    		$incl = "calendar";
		break;
	case "event":
		if( $BAB_SESS_LOGGED)
    		$incl = "event";
		break;
	case "calview":
		if( $BAB_SESS_LOGGED)
    		$incl = "calview";
		break;
	case "calopt":
		if( $BAB_SESS_LOGGED)
    		$incl = "calopt";
		break;
	case "vacation":
		if( $BAB_SESS_LOGGED)
    		$incl = "vacation";
		break;
	case "vacapp":
		if( $BAB_SESS_LOGGED)
    		$incl = "vacapp";
		break;
	case "threads":
		$incl = "threads";
		break;
	case "posts":
		$incl = "posts";
		break;
	case "articles":
		$incl = "articles";
		break;
	case "comments":
		$incl = "comments";
		break;
	case "waiting":
		$incl = "waiting";
		break;
	case "faq":
		$incl = "faq";
		break;
	case "search":
		$incl = "search";
		break;
	case "fileman":
		$incl = "fileman";
		break;
	case "notes":
		if( $BAB_SESS_LOGGED)
			$incl = "notes";
		break;
	case "note":
		if( $BAB_SESS_LOGGED)
			$incl = "note";
		break;
	case "inbox":
		if( $BAB_SESS_LOGGED)
			$incl = "inbox";
		break;
	case "contacts":
		if( $BAB_SESS_LOGGED)
			$incl = "contacts";
		break;
	case "contact":
		if( $BAB_SESS_LOGGED)
			{
			$incl = "contact";
			include $babInstallPath."$incl.php";
			exit;
			}
		break;
	case "address":
		if( $BAB_SESS_LOGGED)
			{
			$incl = "address";
			include $babInstallPath."$incl.php";
			exit;
			}
		break;
	case "month":
		$incl = "month";
		include $babInstallPath."$incl.php";
		exit;
		break;
	case "images":
		$incl = "images";
		include $babInstallPath."$incl.php";
		exit;
		break;
	case "version":
		$incl = "version";
		include $babInstallPath."$incl.php";
		exit;
		break;
	case "entry":
		$incl = "entry";
		break;
	default:
		$incl = "entry";
		$arr = explode("/", $tg);
		if( sizeof($arr) >= 3 && $arr[0] == "addon")
			{
			$db = $GLOBALS['babDB'];
			if(bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr[1]))
				{
				$res = $db->db_query("select title from ".BAB_ADDONS_TBL." where id='".$arr[1]."' and enabled='Y'");
				if( $res && $db->db_num_rows($res) > 0)
					{
					$row = $db->db_fetch_array($res);
					$incl = "addons/".$row['title'];
					if( is_dir( $GLOBALS['babInstallPath'].$incl))
						{
						for($i = 2; $i < sizeof($arr); $i++)
							$incl .= "/".$arr[$i];
						$GLOBALS['babAddonFolder'] = $row['title'];
						$GLOBALS['babAddonTarget'] = "addon/".$arr[1];
						$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$arr[1]."/";
						$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
						$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
						}
					else
						$incl = "entry";
					}
				}
			else
				$babBody->msgerror = bab_translate("Access denied");
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

