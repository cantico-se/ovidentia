<?php
//$lvc_include_dir = 'stats/include/';
//include($lvc_include_dir.'new-visitor.inc.php3');
unset($LOGGED_IN);
session_start();
session_register("BAB_SESS_USER");
session_register("BAB_SESS_EMAIL");
session_register("BAB_SESS_USERID");
session_register("BAB_SESS_HASHID");
include $babInstallPath."utilit/utilit.php";

userIsloggedin();
updateActivity();

if( isUserAdministrator())
	{
	$sec = new adminSection();
	$body->addSection($sec);
	}

$sec = new topicsSection();
if( $sec->count > 0 )
	$body->addSection($sec);

$sec = new forumsSection();
if( $sec->count > 0 )
	$body->addSection($sec);

$sec = new babMonth();
$sec->setPosition(1);
$body->addSection($sec);


if( isset($LOGGED_IN) && $LOGGED_IN)
	{
	$sec = new userSection();
	$sec->setPosition(1);
	$body->addSection($sec);
	}

function printBody()
	{
	class tpl
	{
		var $logoLT;
		var $logoRT;
		var $babLogoLT;
		var $babLogoRT;
		var $banner;
		var $babBanner;
		var $sitename;
		var $style;
		var $script;
		var $slogan;
		var $login;
		var $logurl;
		var $logoLB;
		var $logoRB;
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

		function tpl()
			{
			global $body, $LOGGED_IN, $babLogoLB, $babLogoRB, $babLogoLT, $babLogoRT, $babSiteName,$babBanner,$babSlogan,$babStyle;
			if( isset($babLogoLT) && !empty($babLogoLT))
				$this->logoLT = 1;
			if( isset($babLogoRT) && !empty($babLogoRT))
				$this->logoRT = 1;
			if( isset($babBanner) && !empty($babBanner))
				$this->banner = 1;
			if( isset($babLogoLB) && !empty($babLogoLB))
				$this->logoLB = 1;
			if( isset($babLogoRB) && !empty($babLogoRB))
				$this->logoRB = 1;
			$this->babLogoLT = $babLogoLT;
			$this->babLogoRT = $babLogoRT;
			$this->babLogoLB = $babLogoLB;
			$this->babLogoRB = $babLogoRB;
			$this->babBanner = $babBanner;
			$this->sitename = $babSiteName;
			$this->style = $babStyle;
			$this->script = $body->script;
			$this->slogan = $babSlogan;
			if( isset($LOGGED_IN) && $LOGGED_IN == true )
				{
				$this->login = babTranslate("Logout");
				$this->logurl = $GLOBALS[babUrl]."index.php?tg=login&cmd=signoff";
				}
			else
				{
				$this->login = babTranslate("Login");
				$this->logurl = $GLOBALS[babUrl]."index.php?tg=login&cmd=signon";
				}

			$this->menukeys = array_keys($body->menu->items);
			$this->menuvals = array_values($body->menu->items);
			$this->menuitems = count($this->menukeys);

			$this->nbsectleft = 0;
			$this->nbsectright = 0;
			foreach($body->sections as $sec)
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

			$this->content = $body->printout();
			$this->message = $body->message;
			}

		function getNextMenu()
			{
			global $body;
			static $i = 0;
			if( $i < $this->menuitems)
				{
				if(!strcmp($this->menukeys[$i], $body->menu->curItem))
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
			global $body;
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
			global $body;
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
	echo babPrintTemplate($temp,"page.html", "");
	}

switch($tg)
	{
	case "login":
		$incl = "login";
		break;
	case "sections":
		if( isUserAdministrator())
			$incl = "admin/sections";
		break;
	case "section":
		if( isUserAdministrator())
			$incl = "admin/section";
		break;
	case "register":
		$incl = "admin/register";
		break;
	case "users":
		if( isUserAdministrator())
			$incl = "admin/users";
		break;
	case "user":
		if( isUserAdministrator())
			$incl = "admin/user";
		break;
	case "groups":
		if( isUserAdministrator())
			$incl = "admin/groups";
		break;
	case "group":
		if( isUserAdministrator())
			$incl = "admin/group";
		break;
	case "admfaqs":
		if( isUserAdministrator())
			$incl = "admin/admfaqs";
		break;
	case "admfaq":
		if( isUserAdministrator())
			$incl = "admin/admfaq";
		break;
	case "topics":
		if( isUserAdministrator())
			$incl = "admin/topics";
		break;
	case "topic":
		if( isUserAdministrator())
			$incl = "admin/topic";
		break;
	case "forums":
		if( isUserAdministrator())
			$incl = "admin/forums";
		break;
	case "forum":
		if( isUserAdministrator())
			$incl = "admin/forum";
		break;
	case "admvacs":
		if( isUserAdministrator())
			$incl = "admin/admvacs";
		break;
	case "admvac":
		if( isUserAdministrator())
			$incl = "admin/admvac";
		break;
	case "vacation":
		$incl = "vacation";
		break;
	case "vacapp":
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
	case "notes":
		if( $LOGGED_IN)
			$incl = "notes";
		break;
	case "note":
		if( $LOGGED_IN)
			$incl = "note";
		break;
	case "month":
		$incl = "utilit/month";
		include $babInstallPath."$incl.php";
		exit;
		break;
	default:
		$incl = "entry";
		break;
	}
if( !empty($incl))
	{
	include $babInstallPath."$incl.php";
	}

getSections();
printBody();
unset($tg);
?>

