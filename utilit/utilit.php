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
include_once "base.php";
include $babInstallPath."utilit/defines.php";
include $babInstallPath."utilit/dbutil.php";
include $babInstallPath."utilit/template.php";
include $babInstallPath."utilit/userincl.php";

function bab_stripDomainName ($txt)
	{
	return eregi_replace("((href|src)=['\"]?)".$GLOBALS['babUrl'], "\\1", $txt);
	}

function bab_isEmailValid ($email)
	{
	if( empty($email) || ereg(' ', $email))
		return false;
	else
		return true;
	}

function bab_getCssUrl()
	{
	global $babInstallPath, $babSkinPath;
	$filepath = "skins/".$GLOBALS['babSkin']."/styles/". $GLOBALS['babStyle'];
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath."styles/". $GLOBALS['babStyle'];
		}
	return $filepath;
	}

function bab_isMagicQuotesGpcOn()
	{
	$mqg = ini_get("magic_quotes_gpc");
	if( $mqg == 0 || strtolower($mqg) == "off" || !get_cfg_var("magic_quotes_gpc"))
		return false;
	else
		return true;
	}

function bab_mktime($time)
	{
	$arr = explode(" ", $time);
	$arr0 = explode("-", $arr[0]);
	$arr1 = explode(":", $arr[1]);
	return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
	}

function bab_strftime($time, $hour=true)
	{
	global $babDays, $babMonths;
	if( $time < 0)
		return "";
	if( !$hour )
		return $babDays[date("w", $time)]." ".date("j", $time)." ".$babMonths[date("n", $time)]." ".date("Y", $time); 
	else
		return $babDays[date("w", $time)]." ".date("j", $time)." ".$babMonths[date("n", $time)]." ".date("Y", $time)." ".date("H", $time).":".date("i", $time); 
	}

function bab_printTemplate( &$class, $file, $section="")
	{
	global $babInstallPath, $babSkinPath;
	$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath."templates/". $file;
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath."skins/ovidentia/templates/". $file;
			}
		}
	$tpl = new babTemplate();
	return $tpl->printTemplate($class,$filepath, $section);
	}

function bab_composeUserName( $firstname, $lastname)
	{
	return trim($firstname . " " . $lastname);
	}

function bab_browserOS()
	{
	global $HTTP_USER_AGENT;
	if ( stristr($HTTP_USER_AGENT, "windows"))
		{
	 	return "windows";
		}
	if ( stristr($HTTP_USER_AGENT, "mac"))
		{
		return "macos";
		}
	if ( stristr($HTTP_USER_AGENT, "linux"))
		{
		return "linux";
		}
	return "";
	}

function bab_browserAgent()
	{
	global $HTTP_USER_AGENT;
	if ( stristr($HTTP_USER_AGENT, "konqueror"))
		{
		return "konqueror";
		}
	if( stristr($HTTP_USER_AGENT, "opera"))
		{
		return "opera";
		}
	if( stristr($HTTP_USER_AGENT, "msie"))
		{
		return "msie";
		}
	if( stristr($HTTP_USER_AGENT, "mozilla"))
		{
		if(stristr($HTTP_USER_AGENT, "gecko"))
			return "nn6";
		else
			return "nn4";
		}
	return "";
	}

function bab_browserVersion()
	{
	global $HTTP_USER_AGENT;
	$tab = explode(";", $HTTP_USER_AGENT);
	if( ereg("([^(]*)([0-9].[0-9]{1,2})",$tab[1],$res))
		{
		return trim($res[2]);
		}
	return 0;
	}
function babLoadLanguage($lang, $folder, &$arr)
	{
	if( empty($folder))
		{
		$filename = "lang/lang-".$lang.".xml";
		if (!file_exists($filename))
			$filename = $GLOBALS['babInstallPath']."lang/lang-".$lang.".xml";
		}
	else
		{
		$filename = "lang/addons/".$folder."/lang-".$lang.".xml";
		if (!file_exists($filename))
			$filename = $GLOBALS['babInstallPath']."lang/addons/".$folder."/lang-".$lang.".xml";
		}

	$file = @fopen($filename, "r");
	if( $file )
		{
		$tmp = fread($file, filesize($filename));
		fclose($file);
		preg_match("/<".$lang.">(.*)<\/".$lang.">/s", $tmp, $m);
		preg_match_all("/<string\s+id=\"([^\"]*)\">(.*?)<\/string>/s", $m[1], $arr);
		}
	}

function bab_translate($str, $folder = "", $lang="")
{
	static $babLA = array();

	if( empty($lang))
		$lang = $GLOBALS['babLanguage'];

	if( empty($lang) || empty($str))
		return $str;

	if( !empty($folder))
		$tag = $folder."/".$lang;
	else
		$tag = "bab/".$lang;

	if( !isset($babLA[$tag]))
		babLoadLanguage($lang, $folder, $babLA[$tag]);

	for( $i = 0; $i < count($babLA[$tag][1]); $i++)
		{
		if( $babLA[$tag][1][$i] == $str )
			{
			return $babLA[$tag][2][$i];
			}
		}
	return $str;
}

function bab_translate_old($str, $folder = "")
{
	static $langcontent;

	if( empty($GLOBALS['babLanguage']) || empty($str))
		return $str;

	if( empty($folder))
		{
		$tmp = &$langcontent;
		$filename = "lang/lang-".$GLOBALS['babLanguage'].".xml";
		if (!file_exists($filename))
			$filename = $GLOBALS['babInstallPath']."lang/lang-".$GLOBALS['babLanguage'].".xml";
		}
	else
		{
		$tmp = "";
		$filename = "lang/addons/".$folder."/lang-".$GLOBALS['babLanguage'].".xml";
		if (!file_exists($filename))
			$filename = $GLOBALS['babInstallPath']."lang/addons/".$folder."/lang-".$GLOBALS['babLanguage'].".xml";
		}

	if( empty($tmp))
		{
		clearstatcache();
		if( !file_exists($filename))
			{
			if( !empty($folder) && !is_dir($GLOBALS['babInstallPath']."lang/addons/".$folder))
				mkdir($GLOBALS['babInstallPath']."lang/addons/".$folder, 0777);
			$file = @fopen($filename, "w");
			if( $file )
				fclose($file);
			}
		else
			{
			$file = @fopen($filename, "r");
			$tmp = fread($file, filesize($filename));
			fclose($file);
			}
		}
	if( preg_match("/<".$GLOBALS['babLanguage'].">(.*)<string\s+id=\"".preg_quote($str)."\">(.*?)<\/string>(.*)<\/".$GLOBALS['babLanguage'].">/s", $tmp, $m))
		return $m[2];
	else
		{
		$file = @fopen($filename, "w");
		if( $file )
			{
			preg_match("/<".$GLOBALS['babLanguage'].">(.*)<\/".$GLOBALS['babLanguage'].">/s", $tmp, $m);
			$tmp = "<".$GLOBALS['babLanguage'].">".$m[1];
			$tmp .= "<string id=\"".$str."\">".$str."</string>\r\n";
			$tmp .= "</".$GLOBALS['babLanguage'].">";
			fputs($file, $tmp);
			fclose($file);
			}
		}
	return $str;
}

function bab_callAddonsFunction($func)
{
	global $babDB;
	$res = $babDB->db_query("select id, title from ".BAB_ADDONS_TBL." where enabled='Y'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $row['id']))
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_file($addonpath."/init.php" ))
				{
				$GLOBALS['babAddonFolder'] = $row['title'];
				$GLOBALS['babAddonTarget'] = "addon/".$row['id'];
				$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$row['id']."/";
				$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
				$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
				require_once( $addonpath."/init.php" );
				$call = $row['title']."_".$func;
				if( !empty($call)  && function_exists($call) )
					{
					$args = func_get_args();
					$call .= "(";
					for($k=1; $k < sizeof($args); $k++)
						eval ( "\$call .= \"$args[$k],\";");
					$call = substr($call, 0, -1);
					$call .= ")";
					eval ( "\$retval = $call;");
					}
				}
			}
		}
}

function bab_getAddonsMenus($row, $what)
{
	global $babDB;
	$addon_urls = array();
	$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
	if( is_file($addonpath."/init.php" ))
		{
		$GLOBALS['babAddonFolder'] = $row['title'];
		$GLOBALS['babAddonTarget'] = "addon/".$row['id'];
		$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$row['id']."/";
		$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
		$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
		require_once( $addonpath."/init.php" );
		$func = $row['title']."_".$what;
		if( !empty($func) && function_exists($func))
			{
			while( $func($url, $txt))
				{
				$addon_urls[$txt] = $url;
				}
			$func = $row['title']."_onSectionCreate";
			$res = $babDB->db_query("select id from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");
			if( $res && $babDB->db_num_rows($res) < 1 && function_exists($func))
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'"));
				$babDB->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$row['id']. "', '0', '4', '" . ($arr[0]+1). "')");
				}
			else if( $res && $babDB->db_num_rows($res) > 0 && !function_exists($func))
				{
				$babDB->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");	
				$babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$row['id']."' and type='4'");	
				}
			}
		}
	return $addon_urls;
}

class babSection
{
var $title;
var $content;
var $hiddenz;
var $position;
var $close;
var $boxurl;
var $bbox;
var $template;

function babSection($title = "Section", $content="<br>This is a sample of content<br>")
{
	$this->title = $title;
	$this->content = $content;
	$this->hiddenz = false;
	$this->position = 0;
	$this->close = 0;
	$this->boxurl = "";
	$this->bbox = 0;
	$this->template = "default";
}

function getTitle() { return $this->title;}
function setTitle($title) {	$this->title = $title;}
function getContent() {	return $this->content;}
function getTemplate() { return $this->template;}
function setTemplate($template)
	{
	if( !empty($template))
		$this->template = $template;
	}

function setContent($content)
{
	$this->content = $content;
}

function getPosition()
{
	return $this->position;
}

function setPosition($pos)
{
	$this->position = $pos;
}

function isVisible()
{
	if( $this->hiddenz == true)
		return false;
	else
		return true;
}

function show()
{
	$this->hiddenz = false;
}

function hide()
{
	$this->hiddenz = true;
}

function close()
{
	$this->close = 1;
}

function open()
{
	$this->close = 0;
}

function printout()
{
	$file = "sectiontemplate.html";
	$str = bab_printTemplate($this,$file, $this->template);
	if( empty($str))
		return bab_printTemplate($this,$file, "default");
	else
		return $str;
}

}  /* end of class babSection */


class babSectionTemplate extends babSection
{
var $file;
function babSectionTemplate($file, $section="")
	{
	$this->babSection("","");
	$this->file = $file;
	$this->setTemplate($section);
	}

function printout()
	{
	$str = bab_printTemplate($this,$this->file, $this->template);
	if( empty($str))
		return bab_printTemplate($this,$this->file, "template");
	else
		return $str;
	}
}

class babAdminSection extends babSectionTemplate
{
var $array_urls = array();
var $addon_urls = array();
var $head;
var $foot;
var $key;
var $val;

function babAdminSection($close)
	{
	global $babDB;
	$this->babSectionTemplate("adminsection.html", "template");
	$this->title = bab_translate("Administration");
	if( $close )
		return;
	$this->array_urls[bab_translate("Sites")] = $GLOBALS['babUrlScript']."?tg=sites";
	$this->array_urls[bab_translate("Sections")] = $GLOBALS['babUrlScript']."?tg=sections";
	$this->array_urls[bab_translate("Users")] = $GLOBALS['babUrlScript']."?tg=users";
	$this->array_urls[bab_translate("Groups")] = $GLOBALS['babUrlScript']."?tg=groups";
	$this->array_urls[bab_translate("Faq")] = $GLOBALS['babUrlScript']."?tg=admfaqs";
	$this->array_urls[bab_translate("Topics categories")] = $GLOBALS['babUrlScript']."?tg=topcats";
	$this->array_urls[bab_translate("Forums")] = $GLOBALS['babUrlScript']."?tg=forums";
	$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=admvacs";
	$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=admcals";
	$this->array_urls[bab_translate("Mail")] = $GLOBALS['babUrlScript']."?tg=maildoms&userid=0&bgrp=y";
	$this->array_urls[bab_translate("File manager")] = $GLOBALS['babUrlScript']."?tg=admfms";
	$this->array_urls[bab_translate("Approbations")] = $GLOBALS['babUrlScript']."?tg=apprflow";
	$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=admdir";
	$this->array_urls[bab_translate("Add-ons")] = $GLOBALS['babUrlScript']."?tg=addons";
	$this->head = bab_translate("This section is for Administration");
	$this->foot = bab_translate("");

	$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $row['id']))
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_dir($addonpath))
				{
				$arr = bab_getAddonsMenus($row, "getAdminSectionMenus");
				reset ($arr);
				while (list ($txt, $url) = each ($arr))
					{
					$this->addon_urls[$txt] = $url;
					}
				}
			}
		}
	}

function addUrl()
	{
	static $i = 0;
	if( $i < count($this->array_urls))
		{
		$array_keys = array_keys($this->array_urls);
		$array_vals = array_values($this->array_urls);
		$this->val = $array_vals[$i];
		$this->key = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

function addAddonUrl()
	{
	static $i = 0;
	if( $i < count($this->addon_urls))
		{
		$array_keys = array_keys($this->addon_urls);
		$array_vals = array_values($this->addon_urls);
		$this->val = $array_vals[$i];
		$this->key = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

}

class babUserSection extends babSectionTemplate
{
var $array_urls = array();
var $addon_urls = array();
var $head;
var $foot;
var $key;
var $val;
var $newcount;
var $newtext;
var $newurl;
var $blogged;
var $aidetxt;
var $vacwaiting;

function babUserSection($close)
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$this->babSectionTemplate("usersection.html", "template");
	$this->title = bab_translate("User's section");
	$this->vacwaiting = false;

	if( $close )
		return;

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->head = bab_translate("You are logged on as").":<br><center><b>";
		$this->head .= $GLOBALS['BAB_SESS_USER'];
		}
	else
		{
		$this->head = bab_translate("You are not yet logged in")."<br><center><b>";
		}
	$this->head .= "</b></center>";
	$this->foot = "";
	$this->aidetxt = bab_translate("Since your last connection:");

	$this->blogged = false;
	$faq = false;
	$req = "select id from ".BAB_FAQCAT_TBL."";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
			{
			$faq = true;
			break;
			}
		}
	
	$vac = false;
	$mtopics = false;
	$bemail = false;
	$idcal = 0;
	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->blogged = true;
		$vacacc = bab_vacationsAccess();
		if( count($vacacc) > 0)
			{
			$this->vacwaiting = $vacacc['approver'];
			$vac = true;
			}

		$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where id_approver='".$BAB_SESS_USERID."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			$mtopics = true;

		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			$bemail = true;
		}

	if( $mtopics )
		$this->array_urls[bab_translate("Managed topics")] = $GLOBALS['babUrlScript']."?tg=topman";

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->array_urls[bab_translate("Summary")] = $GLOBALS['babUrlScript']."?tg=calview";
		$this->array_urls[bab_translate("Options")] = $GLOBALS['babUrlScript']."?tg=options";
		if( bab_notesAccess())
		$this->array_urls[bab_translate("Notes")] = $GLOBALS['babUrlScript']."?tg=notes";
		}

	if( $faq )
		{
		$this->array_urls[bab_translate("Faq")] = $GLOBALS['babUrlScript']."?tg=faq";
		}
	if( $vac )
		$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=vacuser";
	$idcal = bab_calendarAccess();
	if( $idcal != 0 )
		{
		$babBody->calaccess = true;
		list($view, $wv) = $babDB->db_fetch_row($babDB->db_query("select defaultview, defaultviewweek from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'"));
		switch($view)
			{
			case '1':
				if( $wv )
					$view='viewq';
				else
					$view='viewqc';
				break;
			case '2': $view='viewd'; break;
			default: $view='viewm'; break;
			}
		$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&calid=".$idcal;
		}
	if( $bemail )
		$this->array_urls[bab_translate("Mail")] = $GLOBALS['babUrlScript']."?tg=inbox";
	if( !empty($GLOBALS['BAB_SESS_USER']) && bab_contactsAccess())
		{
		$this->array_urls[bab_translate("Contacts")] = $GLOBALS['babUrlScript']."?tg=contacts";
		}
	bab_fileManagerAccessLevel();
	if( $babBody->ustorage || count($babBody->aclfm) > 0 )
		{
		$this->array_urls[bab_translate("File manager")] = $GLOBALS['babUrlScript']."?tg=fileman";
		}

	$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
			{
			$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=directory";
			break;
			}
		}
	$res = $babDB->db_query("select id, title from ".BAB_ADDONS_TBL." where enabled='Y'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $row['id']))
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_dir($addonpath))
				{
				$arr = bab_getAddonsMenus($row, 'getUserSectionMenus');
				reset ($arr);
				while (list ($txt, $url) = each ($arr))
					{
					$this->addon_urls[$txt] = $url;
					}
				}
			}
		}

	}

function addUrl()
	{
	static $i = 0;
	if( $i < count($this->array_urls))
		{
		$array_keys = array_keys($this->array_urls);
		$array_vals = array_values($this->array_urls);
		$this->url = $array_vals[$i];
		$this->text = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

function addAddonUrl()
	{
	static $i = 0;
	if( $i < count($this->addon_urls))
		{
		$array_keys = array_keys($this->addon_urls);
		$array_vals = array_values($this->addon_urls);
		$this->url = $array_vals[$i];
		$this->text = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

function getnextnew()
	{
	global $babBody;
	static $i = 0;
	if( $i < 4)
		{
		switch( $i )
			{
			case 0:
				$this->newcount = $babBody->newarticles;
				$this->newtext = bab_translate("Articles");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=calview&idx=art";
				break;
			case 1:
				$this->newcount = $babBody->newcomments;
				$this->newtext = bab_translate("Comments");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=calview&idx=com";
				break;
			case 2:
				$this->newcount = $babBody->newposts;
				$this->newtext = bab_translate("Replies");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=calview&idx=for";
				break;
			case 3:
				$this->newcount = $babBody->newfiles;
				$this->newtext = bab_translate("Files");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=calview&idx=fil";
				break;
			}
		$i++;
		return true;
		}
	else
		return false;
	}

}


class babTopcatSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;

function babTopcatSection($close)
	{
	global $babDB, $babBody;
	$this->babSectionTemplate("topcatsection.html", "template");
	$this->title = bab_translate("Topics categories");

	$res = $babDB->db_query("select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( in_array($row['id'], $babBody->topview) )
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			if( !in_array($row['id_cat'], $this->arrid))
				array_push($this->arrid, $row['id_cat']);
			}
		}
	$this->head = bab_translate("List of different topics categories");
	$this->count = count($this->arrid);
	}

function topcatGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$this->arrid[$i]."'");
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$this->arr = $babDB->db_fetch_array($res);
			$this->text = $this->arr['title'];
			$this->url = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arr['id'];
			}
		$i++;
		return true;
		}
	else
		return false;
	}
}

class babTopicsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;
var $newa;
var $newc;
var $waitingc;
var $waitinga;
var $waitingcimg;
var $waitingaimg;
var $bfooter;

function babTopicsSection($cat, $close)
	{
	global $babDB, $babBody;
	static $foot, $waitingc, $waitinga, $waitingaimg, $waitingcimg;
	$this->babSectionTemplate("topicssection.html", "template");
	$r = $babDB->db_fetch_array($babDB->db_query("select description, title, template from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'"));
	$this->setTemplate($r['template']);
	$this->title = $r['title'];
	$this->head = $r['description'];

	$req = "select id, lang from ".BAB_TOPICS_TBL." where id_cat='".$cat."' order by ordering asc";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if(in_array($row['id'], $babBody->topview)) //2003-02-27
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}

			$whatToFilter = $GLOBALS['babLangFilter']->getFilterAsInt();

			if(($row['lang'] == '*') or ($row['lang'] == ''))
				$whatToFilter = 0;
			else if(($GLOBALS['babApplyLanguageFilter'] == 'loose') and ( bab_isUserTopicManager($row['id']) or bab_isUserArticleApprover($row['id']) or bab_isUserCommentApprover($row['id'])))
				$whatToFilter = 0;

			if(($whatToFilter == 0)	or ($whatToFilter == 1 and (substr($row['lang'], 0, 2) == substr($GLOBALS['babLanguage'], 0, 2)))
				or ($whatToFilter == 2 and ($row['lang'] == $GLOBALS['babLanguage'])))
				array_push($this->arrid, $row['id']);
			}
		}

	$this->bfooter = 0;
	if( empty($foot)) $foot = bab_translate("Topics with asterisk have waiting articles or comments ");
	if( empty($waitingc)) $waitingc = bab_translate("Waiting comments");
	if( empty($waitinga)) $waitinga = bab_translate("Waiting articles");
	if( empty($waitingaimg)) $waitingaimg = bab_printTemplate($this, "config.html", "babWaitingArticle");
	if( empty($waitingcimg)) $waitingcimg = bab_printTemplate($this, "config.html", "babWaitingComment");

	$this->foot = &$foot;
	$this->waitingc = &$waitingc;
	$this->waitinga = &$waitinga;
	$this->waitingaimg = &$waitingaimg;
	$this->waitingcimg = &$waitingcimg;

	$this->count = count($this->arrid);
	} // function babTopicsSection

function topicsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID, $babInstallPath;
	include_once $babInstallPath."utilit/afincl.php";
	static $i = 0;
	if( $i < $this->count)
		{
		$req = "select id, idsaart, category  from ".BAB_TOPICS_TBL." where id='".$this->arrid[$i]."'";
		$res = $babDB->db_query($req);
		$this->newa = "";
		$this->newc = "";
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$this->arr = $babDB->db_fetch_array($res);
			if( isUserApproverFlow($this->arr['idsaart'], $BAB_SESS_USERID))
				{
				$this->bfooter = 1;
				$req = "select ".BAB_ARTICLES_TBL.".id from ".BAB_ARTICLES_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_topic='".$this->arr['id']."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_ARTICLES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
				$res = $babDB->db_query($req);
				if($babDB->db_num_rows($res) > 0)
					$this->newa = "a";

				$req = "select ".BAB_COMMENTS_TBL.".id from ".BAB_COMMENTS_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_topic='".$this->arr['id']."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_COMMENTS_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
				$res = $babDB->db_query($req);
				if($babDB->db_num_rows($res) > 0)
					$this->newc = "c";
				}
			$this->text = $this->arr['category'];
			$this->url = $GLOBALS['babUrlScript']."?tg=articles&topics=".$this->arr['id'];
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

class babForumsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;
var $waiting;
var $bfooter;
var $waitingf;
var $waitingpostsimg;

function babForumsSection($close)
	{
	global $babDB, $babBody;
	static $waitingpostsimg;
	$this->babSectionTemplate("forumssection.html", "template");
	$this->title = bab_translate("Forums");

	$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." order by ordering asc");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			array_push($this->arrid, $row['id']);
			}
		}
	if( empty($waitingpostsimg)) $waitingpostsimg = bab_printTemplate($this, "config.html", "babWaitingPosts");
	$this->waitingpostsimg = &$waitingpostsimg;
	$this->head = bab_translate("List of different forums");
	$this->waitingf = bab_translate("Waiting posts");
	$this->bfooter = 0;
	$this->count = count($this->arrid);
	$this->foot = "";
	}

function forumsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$req = "select * from ".BAB_FORUMS_TBL." where id='".$this->arrid[$i]."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$this->arr = $babDB->db_fetch_array($res);
			$this->text = $this->arr['name'];
			$this->url = $GLOBALS['babUrlScript']."?tg=threads&forum=".$this->arr['id'];
			$this->waiting = "";
			if( $BAB_SESS_USERID == $this->arr["moderator"])
				{
				$this->bfooter = 1;
				$req = "select count(".BAB_POSTS_TBL.".id) as total from ".BAB_POSTS_TBL." join ".BAB_THREADS_TBL." where ".BAB_THREADS_TBL.".active='Y' and ".BAB_THREADS_TBL.".forum='".$this->arr['id'];
				$req .= "' and ".BAB_POSTS_TBL.".confirmed='N' and ".BAB_THREADS_TBL.".id=".BAB_POSTS_TBL.".id_thread";
				$res = $babDB->db_query($req);
				$ar = $babDB->db_fetch_array($res);
				if( $ar['total'] > 0)
					{
					$this->waiting = "*";
					}
				}
			}
		$i++;
		return true;
		}
	else
		return false;
	}
}

class babMenu
{
var $curItem = "";
var $items = array();

function babMenu()
{
	$GLOBALS['babCurrentMenu'] = "";
}

function addItem($title, $txt, $url, $enabled=true)
{
	$this->items[$title]["text"] = $txt;
	$this->items[$title]["url"] = $url;
	$this->items[$title]["enabled"] = $enabled;
}

function addItemAttributes($title, $attr)
{
	$this->items[$title]["attributes"] = $attr;
}

function setCurrent($title, $enabled=false)
{
	foreach($this->items as $key => $val)
		{
		if( !strcmp($key, $title))
			{
			$this->curItem = $key;
			$this->items[$key]["enabled"] = $enabled;
			if( !$enabled )
				$GLOBALS['babCurrentMenu'] = $this->items[$key]["text"];
			break;
			}
		}
}
}  /* end of class babMenu */

class babBody
{
var $sections = array();
var $menu;
var $msgerror;
var $content;
var $title;
var $message;
var $script;
var $lastlog; /* date of user last log */
var $newarticles;
var $newcomments;
var $newposts;
var $topview = array();
var $calaccess;

function babBody()
{
	$this->menu = new babMenu();
	$this->message = "";
	$this->script = "";
	$this->title = "";
	$this->msgerror = "";
	$this->content = "";
	$this->lastlog = "";
	$this->newarticles = 0;
	$this->newcomments = 0;
	$this->newposts = 0;
	$this->newfiles = 0;
	$this->calaccess = false;
}

function resetContent()
{
	$this->content = "";
}

function babecho($txt)
{
	$this->content .= $txt;
}

function isSectionClose($idsec, $type)
{
	global $babDB, $BAB_SESS_USERID;
	$close = 0;
	$req = "select * from ".BAB_SECTIONS_STATES_TBL." where id_section='".$idsec."' and id_user='".$BAB_SESS_USERID."' and type='".$type."'";
	$res2 = $babDB->db_query($req);
	if( $res2 && $babDB->db_num_rows($res2) > 0)
		{
		$arr2 = $babDB->db_fetch_array($res2);
		if( $arr2['closed'] == "Y")
			{
			$close = 1;
			}
		}
	return $close;
}

function loadSections()
{
	global $babDB, $babBody, $BAB_SESS_LOGGED, $BAB_SESS_USERID;
	$add = false;
	$req = "select * from ".BAB_SECTIONS_ORDER_TBL." order by ordering asc";
	$res = $babDB->db_query($req);
	while( $arr =  $babDB->db_fetch_array($res))
		{
		$close = $BAB_SESS_USERID == "" ? 0: $this->isSectionClose($arr['id_section'], $arr['type']);
		$add = false;
		switch( $arr['type'] )
			{
			case "1": // BAB_PRIVATE_SECTIONS_TBL
				$r = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id='".$arr['id_section']."'"));
				switch( $arr['id_section'] )
					{
					case 1: // admin
						if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && bab_isUserAdministrator())
							{
							$add = true;
							$sec = new babAdminSection($close);
							}
						break;
					case 2: // month
						if( $r['enabled'] == "Y" )
							{
							$add = true;
							$sec = new babMonthA();
							}
						break;
					case 3: // topics
						$sec = new babTopcatSection($close);
						if( $sec->count > 0 )
							{
							if( $r['enabled'] == "Y" )
								$add = true;
							}
						break;
					case 4: // Forums
						$sec = new babForumsSection($close);
						if( $sec->count > 0 )
							{
							if( $r['enabled'] == "Y" )
								$add = true;
							}
						break;
					case 5: // user's section
						if( $r['enabled'] == "Y" )
							$add = true;
						$sec = new babUserSection($close);
						break;
					}
				break;
			case "3": // BAB_TOPICS_CATEGORIES_TBL sections
				$r = $babDB->db_fetch_array($babDB->db_query("select id, enabled from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_section']."'"));
				if( $r['enabled'] == "Y")
					{
					$sec = new babTopicsSection($r['id'], $close);
					if( $sec->count > 0 )
						$add = true;
					}
				break;
			case "4": // BAB_ADDONS_TBL sections
				if(bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr['id_section']))
					{
					$r = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ADDONS_TBL." where id='".$arr['id_section']."'"));
					if( $r['enabled'] == "Y" && is_file($GLOBALS['babAddonsPath'].$r['title']."/init.php"))
						{
						$GLOBALS['babAddonFolder'] = $r['title'];
						$GLOBALS['babAddonTarget'] = "addon/".$r['id'];
						$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$r['id']."/";
						$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$r['title']."/";
						$GLOBALS['babAddonHtmlPath'] = "addons/".$r['title']."/";
						require_once( $GLOBALS['babAddonsPath'].$r['title']."/init.php" );
						$func = $r['title']."_onSectionCreate";
						if( function_exists($func))
							{
							if( $func($stitle, $scontent))
								{
								$add = true;
								if( !$close )
									$sec = new babSection($stitle, $scontent);
								else
									$sec = new babSection($stitle, "");
								$sec->setTemplate($r['title']);
								}
							}
						}
					}
				break;
			default: // user's sections
				$add = bab_isAccessValid(BAB_SECTIONS_GROUPS_TBL, $arr['id_section']);
				if( $add )
					{
					$langFilterValues = $GLOBALS['babLangFilter']->getLangValues();
					$req = "select * from ".BAB_SECTIONS_TBL." where id='".$arr['id_section']."' and enabled='Y'";
					if( count($langFilterValues) > 0 )
						$req .= " and SUBSTRING(lang, 1, 2 ) IN (".implode(',', $langFilterValues).")";
					$res2 = $babDB->db_query($req);
					if( $res2 && $babDB->db_num_rows($res2) > 0)
						{
						$arr2 = $babDB->db_fetch_array($res2);
						if( !$close )
							{
							if( $arr2['script'] == "Y")
								eval("\$arr2['content'] = \"".$arr2['content']."\";");
							$sec = new babSection($arr2['title'], $arr2['content']);
							}
						else
							$sec = new babSection($arr2['title'], "");
						$sec->setTemplate($arr2['template']);
						}
					else
						$add = false;
					}
				break;
			}

		if( $add )
			{
			$sec->setPosition($arr['position']);
			$sec->close = $close;
			$sec->bbox = 1;
			if(empty($BAB_SESS_USERID))
				$sec->bbox = 0;
			if( $sec->close )
				$sec->boxurl = $GLOBALS['babUrlScript']."?tg=options&idx=ob&s=".$arr['id_section']."&w=".$arr['type'];
			else
				$sec->boxurl = $GLOBALS['babUrlScript']."?tg=options&idx=cb&s=".$arr['id_section']."&w=".$arr['type'];
			$babBody->addSection($sec);
			}
		}

}

function addSection($sec)
{
	array_push($this->sections, $sec);
}

function showSection($title)
{
	for( $i = 0; $i < count($this->sections); $i++)
		{
		if( !strcmp($this->sections[$i]->getTitle(), $title))
			{
			$this->sections[$i]->show();
			}
		}
}

function hideSection($title)
{
	for( $i = 0; $i < count($this->sections); $i++)
		{
		if( !strcmp($this->sections[$i]->getTitle(), $title))
			{
			$this->sections[$i]->hide();
			}
		}
}

function addItemMenu($title, $txt, $url, $enabled=true)
{
	$this->menu->addItem($title, $txt, $url, $enabled);
}

function addItemMenuAttributes($title, $attr)
{
	$this->menu->addItemAttributes($title, $attr);
}

function setCurrentItemMenu($title, $enabled=false)
{
	$this->menu->setCurrent($title, $enabled);
}

function printout()
{
    if(!empty($this->msgerror))
		{
		$this->message = bab_printTemplate($this,"warning.html", "texterror");
		//return "";
		}
	else if(!empty($this->title))
		{
		$this->message = bab_printTemplate($this,"warning.html", "texttitle");
		}
	return $this->content;
}

}  /* end of class babBody */

class babMonthA  extends babSection
{
var $currentMonth;
var $currentYear;
var $curmonth;
var $curyear;
var $day3;

var $days;
var $daynumber;
var $now;
var $w;
var $event;
var $dayurl;
var $babCalendarStartDay;


function babMonthA($month = "", $year = "")
	{
	global $babDB,$BAB_SESS_USERID;

	$this->babSection("","");

	if(empty($month))
		$this->currentMonth = Date("n");
	else
		{
		$this->currentMonth = $month;
		}
	
	if(empty($year))
		{
		$this->currentYear = Date("Y");
		}
	else
		{
		$this->currentYear = $year;
		}

	if( !empty($BAB_SESS_USERID))
		{
		$res = $babDB->db_query("select startday from ".BAB_CALOPTIONS_TBL." where id_user='".$BAB_SESS_USERID."'");
		$this->babCalendarStartDay = 0;
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			$this->babCalendarStartDay = $arr['startday'];
			}
		}
	else
		$this->babCalendarStartDay = 0;

	}

function printout()
	{
	global $babDB, $babMonths, $BAB_SESS_USERID;
	$this->curmonth = $babMonths[date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear))];
	$this->curyear = $this->currentYear;
	$this->days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->daynumber = date("w", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->now = date("j");
	$this->w = 0;
	$todaymonth = date("n");
	$todayyear = date("Y");
	$this->idcals = array();
	if( !empty($BAB_SESS_USERID))
		{
		$this->idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( $this->idcal != 0 )
			$this->idcals[] = $this->idcal;

		$res = $babDB->db_query("select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group");
		while($arr = $babDB->db_fetch_array($res))
			{
			$res2 = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='2' and actif='Y'");
			while( $arr2 = $babDB->db_fetch_array($res2))
				{
				$this->idcals[] = $arr2['id'];
				}
			}
		}

	return bab_printTemplate($this,"montha.html", "");
	}

	function getnextday3()
		{
		global $babDays;
		static $i = 0;
		if( $i < 7)
			{
			$a = $i + $this->babCalendarStartDay;
			if( $a > 6)
				$a -=  7;
			$this->day3 = substr($babDays[$a], 0, 1);
			$i++;
			return true;
			}
		else
			return false;
		}

	function getnextweek()
		{
		if( $this->w < 7)
			{
			$this->w++;
			return true;
			}
		else
			{
			return false;
			}
		}

	function getnextday()
		{
		global $babDB;
		static $d = 0;
		static $total = 0;
		if( $d < 7)
			{
			$this->bgcolor = 0;
			$this->event = 0;

			$a = $this->daynumber - $this->babCalendarStartDay;
			if( $a < 0)
				$a += 7;

			if( $this->w == 1 &&  $d < $a)
				{
				$this->day = "&nbsp;";
				}
			else
				{
				$total++;

				if( $total > $this->days)
					return false;
				$this->day = $total;
				if( count($this->idcals) > 0 )
					{
					$mktime = mktime(0,0,0,$this->currentMonth, $total,$this->currentYear);
					$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
					$req = "select distinct(".BAB_CAL_EVENTS_TBL.".id_cal) as idcal from ".BAB_CAL_EVENTS_TBL." where id_cal IN (".implode(',', $this->idcals).")";
					$req .= " and '".$daymin."' between start_date and end_date";
					$res = $babDB->db_query($req);
					if( $res && $babDB->db_num_rows($res))
						{
						while($row = $babDB->db_fetch_array($res))
							{
							$idcals .= $row['idcal'] .",";
							}
						$idcals = substr($idcals, 0, -1);
						if( !empty($idcals))
							{
							$this->event = 1;
							$this->dayurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewd&day=".$total."&month=".$this->currentMonth. "&year=".$this->currentYear. "&calid=".$idcals;
							$this->day = "<b>".$total."</b>";
							}
						}
					}
				if( $total == $this->now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
					{
					$this->bgcolor = 1;
					}

				}
			if( $total > $this->days)
				{
				return false;
				}
			$d++;
			return true;
			}
		else
			{
			$d = 0;
			return false;
			}
		}
}

function bab_updateUserSettings()
{
	global $babDB, $babBody,$BAB_SESS_USERID;

	$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $row['id']) )
			{
			$babBody->topview[] = $row['id'];
			}
		}


	if( !empty($BAB_SESS_USERID))
		{

		$res=$babDB->db_query("select lang, skin, style, lastlog, langfilter from ".BAB_USERS_TBL." where id='".$BAB_SESS_USERID."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['lang'] != "")
				{
				$GLOBALS['babLanguage'] = $arr['lang'];
				}
			
			if($arr['langfilter'] != '')
				$GLOBALS['babLangFilter']->setFilter($arr['langfilter']);
			
			if( $arr['skin'] != "" && (is_dir($GLOBALS['babInstallPath']."skins/".$arr['skin']) || is_dir("skins/".$arr['skin'])))
				{
				$GLOBALS['babSkin'] = $arr['skin'];
				}
			if( $arr['style'] != "")
				{
				$GLOBALS['babStyle'] = $arr['style'];
				}

			$babBody->lastlog = $arr['lastlog'];

			if( count($babBody->topview) > 0 )
				{
				$res = $babDB->db_query("select id_topic from ".BAB_ARTICLES_TBL." where confirmed='Y' and date >= '".$babBody->lastlog."'");
				while( $row = $babDB->db_fetch_array($res))
					{
					if( in_array($row['id_topic'], $babBody->topview) )
						{
						$babBody->newarticles++;
						}
					}

				$res = $babDB->db_query("select id_topic from ".BAB_COMMENTS_TBL." where confirmed='Y' and date >= '".$babBody->lastlog."'");
				while( $row = $babDB->db_fetch_array($res))
					{
					if( in_array($row['id_topic'], $babBody->topview) )
						{
						$babBody->newcomments++;
						}
					}
				}
			$res = $babDB->db_query("select distinct ".BAB_THREADS_TBL.".forum from ".BAB_THREADS_TBL." join ".BAB_POSTS_TBL." where ".BAB_THREADS_TBL.".id = ".BAB_POSTS_TBL.".id_thread and ".BAB_POSTS_TBL.".confirmed='Y' and ".BAB_POSTS_TBL.".date >= '".$babBody->lastlog."'");
			while( $row = $babDB->db_fetch_array($res))
				{
				if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['forum']) )
					{
					$arr = $babDB->db_fetch_array($babDB->db_query("select count(".BAB_POSTS_TBL.".id) as total from ".BAB_POSTS_TBL.", ".BAB_THREADS_TBL." where ".BAB_POSTS_TBL.".date >= '".$babBody->lastlog."' and ".BAB_POSTS_TBL.".confirmed='Y' and ".BAB_POSTS_TBL.".id_thread=".BAB_THREADS_TBL.".id and ".BAB_THREADS_TBL.".forum='".$row['forum']."'"));
					$babBody->newposts += $arr['total'];
					}
				}

			$req = "select count(distinct f.id) from ".BAB_FILES_TBL." f, ".BAB_FMDOWNLOAD_GROUPS_TBL." fmg,  ".BAB_USERS_GROUPS_TBL." ug where f.bgroup='Y' and f.state='' and f.confirmed='Y' and fmg.id_object = f.id_owner and ( fmg.id_group='2'";
			if( $BAB_SESS_USERID != "" )
			$req .= " or fmg.id_group='1' or (fmg.id_group=ug.id_group and ug.id_object='".$BAB_SESS_USERID."')";
			$req .= ")";
			
			if( $this->nbdays !== false)
				$req .= " and f.modified >= '".$babBody->lastlog."'";

			list($babBody->newfiles) = $babDB->db_fetch_row($babDB->db_query($req));
			}
		}

	$res = $babDB->db_query("select id from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$babDB->db_query("update ".BAB_USERS_LOG_TBL." set dateact=now(), remote_addr='".$GLOBALS['REMOTE_ADDR']."', forwarded_for='".$GLOBALS['HTTP_X_FORWARDED_FOR']."' where sessid = '".session_id()."'");
		}
	else
		{
		if( !empty($BAB_SESS_USERID))
			$userid = $BAB_SESS_USERID;
		else
			$userid = 0;

		$babDB->db_query("insert into ".BAB_USERS_LOG_TBL." (id_user, sessid, dateact, remote_addr, forwarded_for) values ('".$userid."', '".session_id()."', now(), '".$GLOBALS['REMOTE_ADDR']."', '".$GLOBALS['HTTP_X_FORWARDED_FOR']."')");
		}

	$res = $babDB->db_query("select id, UNIX_TIMESTAMP(dateact) as time from ".BAB_USERS_LOG_TBL);
	while( $row  = $babDB->db_fetch_array($res))
		{
		if( $row['time'] + get_cfg_var('session.gc_maxlifetime') < time()) 
			$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id='".$row['id']."'");
		}

}

function bab_updateSiteSettings()
{
	global $babDB, $babBody;

	$req="select skin, style, adminemail, lang, langfilter from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res=$babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);
	if( $arr['skin'] != "")
		{
		$GLOBALS['babSkin'] = $arr['skin'];
		}
	else
		$GLOBALS['babSkin'] = "ovidentia";

	if( $arr['style'] != "")
		{
		$GLOBALS['babStyle'] = $arr['style'];
		}
	else
		$GLOBALS['babStyle'] = "ovidentia.css";

	if( $arr['lang'] != "")
		{
		$GLOBALS['babLanguage'] = $arr['lang'];
		}
	else
		$GLOBALS['babLanguage'] = "en";
	if( $arr['adminemail'] != "")
		{
		$GLOBALS['babAdminEmail'] = $arr['adminemail'];
		}
	else
		$GLOBALS['babAdminEmail'] = "admin@your-domain.com";
	if( $arr['langfilter'] != "")
		{
		$GLOBALS['babLangFilter']->setFilter($arr['langfilter']);
		}
	else
		$GLOBALS['babLangFilter']->setFilter(0);
}

class babLanguageFilter
	{
		var $langFilterNames;
		var $activeLanguageFilter;
		var $activeLanguageValues;

		function babLanguageFilter()
			{
				$this->setFilter(0);
			} //function LanguageFilter
			
		function translateTexts()
		{
			$this->langFilterNames = array(bab_translate("No filter")
					,bab_translate("Filter language")
					,bab_translate("Filter language and country")
					//,bab_translate("Filter translated")
					);
		}

		function setFilter($filterInt)
			{
				$this->activeLanguageValues = array();
				switch($filterInt)
				{
					case 2:
						$this->activeLanguageValues[] = '\'*\'';
						$this->activeLanguageValues[] = '\'\'';
						break;
					case 1:
						$this->activeLanguageValues[] = '\''.substr($GLOBALS['babLanguage'], 0, 2).'\'';
						$this->activeLanguageValues[] = '\'*\'';
						$this->activeLanguageValues[] = '\'\'';
						break;
					case 0:
					default:
						break;
				}
				$this->activeLanguageFilter = $filterInt;
			}

		function getFilterAsInt()
			{
				return $this->activeLanguageFilter;
			}

		function getFilterAsStr()
			{
				return $this->langFilterNames[$this->activeLanguageFilter];
			}

		function convertFilterToStr($filterInt)
			{
				return $this->langFilterNames[$filterInt];
			}
		
		function convertFilterToInt($filterStr)
			{
				$i = 0;
				while ($i < count($this->langFilterNames))
					{
						if ($this->langFilterNames[$i] == $filterStr) return $i;
						$i++;
					}
				return 0;
			}

		function countFilters()
			{
				return count($this->langFilterNames);
			}

		function getFilterStr($i)
			{
				return $this->langFilterNames[$i];
			} 

		function isLangFile($fileName)
			{
				$res = substr($fileName, 0, 5);
				if ($res != "lang-") return false;
				$res = strtolower(strstr($fileName, "."));
				if ($res != ".xml") return false;
				return true;
			}

		function getLangCode($file)
			{
				$langCode = substr($file,5);
				return substr($langCode,0,strlen($langCode)-4);
			}

		function readLangFiles()
			{
				global $babInstallPath;
				$tmpLangFiles = array();
				$i = 0;
				if (file_exists($babInstallPath.'lang'))
					{
						$folder = opendir($babInstallPath.'lang');
						while (false!==($file = readdir($folder)))
							{
								if ($this->isLangFile($file))
									{
										$tmpLangFiles[$i] = $this->getLangCode($file);
										$i++;
									}
							}
				closedir($folder);
					}
				if (file_exists("lang"))
					{
						$folder = opendir("lang");
						while (false!==($file = readdir($folder)))
							{
								if ($this->isLangFile($file))
									{
										$tmpLangFiles[$i] = $this->getLangCode($file);
										$i++;
									}
							}
						closedir($folder);
}
				$tmpLangFiles[] = '*';
				sort($tmpLangFiles);
				$this->langFiles = array_unique($tmpLangFiles);
			} // readLangFiles()
		
		function getLangFiles()
			{
				static $callNbr = 0;
				if($callNbr == 0)
					{
						$this->readLangFiles();
						$callNbr++;
					}
				return $this->langFiles;
			}  // getLangFiles

		function getLangValues()
			{
				return $this->activeLanguageValues;
			}  // getLangFiles

	} //class LanguageFilter

$babDB = new babDatabase();
$babBody = new babBody();
$BAB_HASH_VAR='aqhjlongsmp';
$babLangFilter = new babLanguageFilter();
?>
