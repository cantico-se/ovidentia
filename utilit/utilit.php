<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/defines.php";
include $babInstallPath."utilit/dbutil.php";
include $babInstallPath."utilit/template.php";
include $babInstallPath."utilit/userincl.php";
include $babInstallPath."utilit/calincl.php";

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
var $babsectionpuce;

function babSection($title = "Section", $content="<br>This is a sample of content<br>")
{
	$this->title = $title;
	$this->content = $content;
	$this->hiddenz = false;
	$this->position = 0;
	$this->close = 0;
	$this->boxurl = "";
	$this->bbox = 0;
}

function getTitle() { return $this->title;}
function setTitle($title) {	$this->title = $title;}
function getContent() {	return $this->content;}

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
	global $babInstallPath, $babSkinPath;
	$file = "sectiontemplate.html";
	$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath."templates/". $file;
		}
	$str = bab_printTemplate($this,$file, $this->title);
	if( empty($str))
		return bab_printTemplate($this,$file, "default");
	else
		return $str;
}

}  /* end of class babSection */


class babSectionTemplate extends babSection
{
var $file;
var $section;
function babSectionTemplate($file, $section="")
	{
	$this->babSection("","");
	$this->file = $file;
	$this->section = $section;
	}

function printout()
	{
	return bab_printTemplate($this,$this->file, $this->section);		
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

function babUserSection($close)
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$this->babSectionTemplate("usersection.html", "template");
	$this->title = bab_translate("User's section");

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
	$pgrpid = $BAB_SESS_USERID == "" ? "": bab_getPrimaryGroupId($BAB_SESS_USERID);
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
		$req = "select id from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' or supplier='".$BAB_SESS_USERID."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0 || bab_isUserUseVacation($BAB_SESS_USERID))
			$vac = true;

		$req = "select id from ".BAB_TOPICS_TBL." where id_approver='".$BAB_SESS_USERID."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0 )
			$mtopics = true;

		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			$bemail = true;
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
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
		$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=vacation";
	if( (bab_getCalendarId(1, 2) != 0  || ($pgrpid != "" && bab_getCalendarId($pgrpid, 2) != 0)) &&  $idcal != 0 )
		$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&calid=".$idcal;
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

	$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=directory";
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
	$r = $babDB->db_fetch_array($babDB->db_query("select description, title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'"));
	$this->title = $r['title'];
	$this->head = $r['description'];
	$req = "select id from ".BAB_TOPICS_TBL." where id_cat='".$cat."' order by ordering asc";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if(in_array($row['id'], $babBody->topview) || ($GLOBALS['BAB_SESS_USERID'] != "" && bab_isUserTopicManager($row['id'])))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
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
	}

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
								}
							}
						}
					}
				break;
			default: // user's sections
				$add = bab_isAccessValid(BAB_SECTIONS_GROUPS_TBL, $arr['id_section']);
				if( $add )
					{
					$req = "select * from ".BAB_SECTIONS_TBL." where id='".$arr['id_section']."' and enabled='Y'";
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
	if( !empty($BAB_SESS_USERID))
		{
		$this->idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		$this->idcals = "(id_cal='".$this->idcal."' ";
		$res = $babDB->db_query("select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group");
		$req2 = "select ".BAB_CALENDAR_TBL.".id from ".BAB_CALENDAR_TBL." join ".BAB_RESOURCESCAL_TBL." where ".BAB_CALENDAR_TBL.".owner=".BAB_RESOURCESCAL_TBL.".id and (".BAB_RESOURCESCAL_TBL.".id_group='1'";
		while($arr = $babDB->db_fetch_array($res))
			{
			$res2 = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='2' and actif='Y'");
			while( $arr2 = $babDB->db_fetch_array($res2))
				{
				$this->idcals .= " or id_cal='".$arr2['id']."' ";
				$req2 .= " or ".BAB_RESOURCESCAL_TBL.".id_group='".$arr2['id']."'"; 
				}
			}
		$req2 .= ")"; 
		$res = $babDB->db_query($req2);
		while($arr = $babDB->db_fetch_array($res))
			{
			$this->idcals .= "or id_cal='".$arr['id']."' ";
			}
		$this->idcals .=")";
		}
	else
		$this->idcal = "";

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
				if( $this->idcal != "" )
					{
					$mktime = mktime(0,0,0,$this->currentMonth, $total,$this->currentYear);
					$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
					$req = "select distinct(".BAB_CAL_EVENTS_TBL.".id_cal) as idcal from ".BAB_CAL_EVENTS_TBL." where ".$this->idcals;
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

		$res=$babDB->db_query("select lang, skin, style, lastlog from ".BAB_USERS_TBL." where id='".$BAB_SESS_USERID."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['lang'] != "")
				{
				$GLOBALS['babLanguage'] = $arr['lang'];
				}
			if( $arr['skin'] != "" && is_dir($GLOBALS['babInstallPath']."skins/".$arr['skin']))
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

			$req = "select count(".BAB_FILES_TBL.".id) from ".BAB_FILES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_object = '".$BAB_SESS_USERID."' and ".BAB_FILES_TBL.".confirmed='Y' and ".BAB_FILES_TBL.".id_owner=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_FILES_TBL.".bgroup='Y' and ".BAB_FILES_TBL.".state='' and ".BAB_FILES_TBL.".modified >= '".$babBody->lastlog."'";

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

	$req="select skin, style, adminemail, lang from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res=$babDB->db_query($req);

	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['skin'] != "")
			{
			$GLOBALS['babSkin'] = $arr['skin'];
			}
		if( $arr['style'] != "")
			{
			$GLOBALS['babStyle'] = $arr['style'];
			}
		if( $arr['lang'] != "")
			{
			$GLOBALS['babLanguage'] = $arr['lang'];
			}
        if( $arr['adminemail'] != "")
			{
			$GLOBALS['babAdminEmail'] = $arr['adminemail'];
			}
		}
	else
		{
		if( empty($GLOBALS['babSiteName']))
			$GLOBALS['babSiteName'] = "ovidentia";

		if( empty($GLOBALS['babSkin']))
			$GLOBALS['babSkin'] = "ovidentia";

		if( empty($GLOBALS['babStyle']))
			$GLOBALS['babStyle'] = "ovidentia.css";
		
		if( empty($GLOBALS['babLanguage']))
			$GLOBALS['babLanguage'] = "en";

		$req = "insert into ".BAB_SITES_TBL." ( name, adminemail, lang, skin, style ) values ('" .addslashes($GLOBALS['babSiteName']). "', '" . $GLOBALS['babAdminEmail']. "', '" . $GLOBALS['babLanguage']. "', '" . $GLOBALS['babSkin']. "', '" . $GLOBALS['babStyle']. "')";
		$res = $babDB->db_query($req);
		}
}

$babDB = new babDatabase();
$babBody = new babBody();
$BAB_HASH_VAR='aqhjlongsmp';
?>
