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
include_once $babInstallPath."utilit/defines.php";
include_once $babInstallPath."utilit/dbutil.php";
include_once $babInstallPath."utilit/template.php";
include_once $babInstallPath."utilit/userincl.php";
include_once $babInstallPath."utilit/mailincl.php";

function bab_mkdir($path, $mode='')
{
	if( substr($path, -1) == "/" )
		{
		$path = substr($path, 0, -1);
		}
	$umask = umask($GLOBALS['babUmaskMode']);
	if( $mode === '' )
	{
		$mode = $GLOBALS['babMkdirMode'];
	}
	$res = mkdir($path, $mode);
	umask($umask);
	return $res;
}

function bab_formatDate($format, $time)
{
	global $babDays, $babMonths;
	$txt = $format;
	if(preg_match_all("/%(.)/", $format, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			switch($m[1][$i])
				{
				case 'd': /* A short textual representation of a day, three letters */
					$val = substr($babDays[date("w", $time)], 0 , 3);
					break;
				case 'D': /* day */
					$val = $babDays[date("w", $time)];
					break;
				case 'j': /* Day of the month with leading zeros */ 
					$val = date("d", $time);
					break;
				case 'm': /* A short textual representation of a month, three letters */
					$val = substr($babMonths[date("n", $time)], 0 , 3);
					break;
				case 'M': /* Month */
					$val = $babMonths[date("n", $time)];
					break;
				case 'n': /* Numeric representation of a month, with leading zeros */
					$val = date("m", $time);
					break;
				case 'Y': /* A full numeric representation of a year, 4 digits */
					$val = date("Y", $time);
					break;
				case 'y': /* A two digit representation of a year */
					$val = date("y", $time);
					break;
				case 'H': /* 24-hour format of an hour with leading zeros */
					$val = date("H", $time);
					break;
				case 'i': /* Minutes with leading zeros */
					$val = date("i", $time);
					break;
				}
			$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
			}
		}
	return $txt;
}

function bab_formatAuthor($format, $id)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$txt = $format;
		if(preg_match_all("/%(.)/", $format, $m))
			{
			for( $i = 0; $i< count($m[1]); $i++)
				{
				switch($m[1][$i])
					{
					case 'F':
						$val = $arr['givenname'];
						break;
					case 'L':
						$val = $arr['sn'];
						break;
					case 'M':
						$val = $arr['mn'];
						break;
					}
				$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
				}
			}
		}
	else
		$txt = bab_translate("Anonymous");

	return $txt;
}

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
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath."skins/ovidentia/styles/ovidentia.css";
			}
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

function bab_printOvmlTemplate( $file, $args=array())
	{
	global $babInstallPath, $babSkinPath, $babOvmlPath;
	if( strstr($file, "..") || strtolower(substr($file, 0, 4)) == 'http' )
		return "<!-- ERROR filename: ".$file." -->";

	$filepath = $babOvmlPath.$file;
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath."ovml/". $file;
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath."skins/ovidentia/ovml/". $file;
			}
		}

	if( !file_exists( $filepath ) )
		return "<!-- ERROR filename: ".$filepath." -->";

	include_once $GLOBALS['babInstallPath']."utilit/omlincl.php";
	$tpl = new babOvTemplate($args);
	return $tpl->printout(implode("", file($filepath)));
	}
/*
function bab_composeUserName( $F, $L)
	{
	global $babBody;
	if ($babBody->nameorder[0]) eval("\$var0 = \$".$babBody->nameorder[0].";");
	else $var0 = $F;
	if ($babBody->nameorder[1]) eval("\$var1 = \$".$babBody->nameorder[1].";");
	else $var0 = $L;
	return trim(sprintf("%s %s", $var0 ,$var1));
	}
*/
function bab_composeUserName( $F, $L)
{
global $babBody;
return trim(sprintf("%s %s", ${$babBody->nameorder[0]}, ${$babBody->nameorder[1]}));
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
		$filename_c = "lang/lang-".$lang.".dat";
		$filename_m = "lang/lang-".$lang.".xml";
		$filename = $GLOBALS['babInstallPath']."lang/lang-".$lang.".xml";
		}
	else
		{
		$filename_c = "lang/addons-".$folder."-lang-".$lang.".dat";
		$filename_m = "lang/addons/".$folder."/lang-".$lang.".xml";
		$filename = $GLOBALS['babInstallPath']."lang/addons/".$folder."/lang-".$lang.".xml";
		}
	
	if (!file_exists($filename))
		{
		$filename = false;
		}
	else
		{
		$time = filemtime($filename);
		}

	if (!file_exists($filename_m))
		{
		$filename_m = false;
		}
	else
		{
		$time_m = filemtime($filename_m);
		}

	if (!file_exists($filename_c))
		{
		$bfile_c = false;
		}
	else
		{
		$bfile_c = true;
		$time_c = filemtime($filename_c);
		}

	if( !$filename && !$filename_c)
		{
		return;
		}

	if( !$bfile_c || (($filename && ($time > $time_c)) || ($filename_m && ($time_m > $time_c)) ))
		{
		if( $filename )
			{
			$file = @fopen($filename, "r");
			if( $file )
				{
				$tmp = fread($file, filesize($filename));
				fclose($file);
				preg_match("/<".$lang.">(.*)<\/".$lang.">/s", $tmp, $m);
				preg_match_all("/<string\s+id=\"([^\"]*)\">(.*?)<\/string>/s", isset($m[1]) ? $m[1] : '' , $tmparr);
				}
			}

		if( isset($tmparr[0]))
			{
			for( $i = 0; $i < count($tmparr[0]); $i++ )
				{
				$arr[$tmparr[1][$i]] = $tmparr[2][$i];
				}
			}

		if ($filename_m)
			{
			$file = @fopen($filename_m, "r");
			if( $file )
				{
				$tmp = fread($file, filesize($filename_m));
				fclose($file);
				preg_match("/<".$lang.">(.*)<\/".$lang.">/s", $tmp, $m);
				preg_match_all("/<string\s+id=\"([^\"]*)\">(.*?)<\/string>/s", $m[1], $arr_replace);
				for( $i = 0; $i < count($arr_replace[0]); $i++ )
					{
					$arr[$arr_replace[1][$i]] = $arr_replace[2][$i];
					}
				}
			}

		$file = @fopen($filename_c, 'w');
		if( $file )
			{
			fwrite($file, serialize($arr));
			fclose($file);
			}
		}
	else
		{
			$file = @fopen($filename_c, 'r');
			$arr = unserialize(fread($file, filesize($filename_c)));
			fclose($file);
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

	if(isset($babLA[$tag][$str]))
		{
			return $babLA[$tag][$str];
		}
	else
		{
			return $str;
		}
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
				bab_mkdir($GLOBALS['babInstallPath']."lang/addons/".$folder, $GLOBALS['babMkdirMode']);
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
	global $babBody,$babDB;
	reset($babBody->babaddons);
	while( $row=each($babBody->babaddons) ) 
		{ 
		$acces =false;
		if (is_file($GLOBALS['babAddonsPath'].$row['title']."/addonini.php"))
			$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$row['title']."/addonini.php");
		else $acces =true;
		if($row['access'] && (($arr_ini['version'] == $row['version']) || $acces))
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_file($addonpath."/init.php" ))
				{
				$GLOBALS['babAddonFolder'] = $row['title'];
				$GLOBALS['babAddonTarget'] = "addon/".$row['id'];
				$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$row['id']."/";
				$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
				$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
				$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$row['title']."/";
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
		$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$row['title']."/";
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
	$this->content = bab_replace($content);
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
	global $babBody, $babDB;
	$this->babSectionTemplate("adminsection.html", "template");
	$this->title = bab_translate("Administration");
	if( $close )
		return;

	if( ($dgcnt = count($babBody->dgAdmGroups)) > 0 )
		{
		if( $babBody->isSuperAdmin || $dgcnt > 1 )
			{
			$this->array_urls[bab_translate("Change administration")] = $GLOBALS['babUrlScript']."?tg=delegusr";
			}
		}

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
		{
		$this->array_urls[bab_translate("Delegation")] = $GLOBALS['babUrlScript']."?tg=delegat";
		$this->array_urls[bab_translate("Sites")] = $GLOBALS['babUrlScript']."?tg=sites";
		}

	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentAdmGroup != 0)
		$this->array_urls[bab_translate("Users")] = $GLOBALS['babUrlScript']."?tg=users";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['groups'] == 'Y')
		$this->array_urls[bab_translate("Groups")] = $GLOBALS['babUrlScript']."?tg=groups";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['sections'] == 'Y')
		$this->array_urls[bab_translate("Sections")] = $GLOBALS['babUrlScript']."?tg=sections";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['faqs'] == 'Y')
		$this->array_urls[bab_translate("Faq")] = $GLOBALS['babUrlScript']."?tg=admfaqs";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['articles'] == 'Y')
		$this->array_urls[bab_translate("Articles")] = $GLOBALS['babUrlScript']."?tg=topcats";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['forums'] == 'Y')
		$this->array_urls[bab_translate("Forums")] = $GLOBALS['babUrlScript']."?tg=forums";
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=admvacs";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['calendars'] == 'Y')
		$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=admcals";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['mails'] == 'Y')
		$this->array_urls[bab_translate("Mail")] = $GLOBALS['babUrlScript']."?tg=maildoms&userid=0&bgrp=y";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['filemanager'] == 'Y')
		$this->array_urls[bab_translate("File manager")] = $GLOBALS['babUrlScript']."?tg=admfms";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['approbations'] == 'Y')
		$this->array_urls[bab_translate("Approbations")] = $GLOBALS['babUrlScript']."?tg=apprflow";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['directories'] == 'Y')
		$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=admdir";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || (isset($babBody->currentDGGroup['orgchart']) && $babBody->currentDGGroup['orgchart'] == 'Y'))
		$this->array_urls[bab_translate("Charts")] = $GLOBALS['babUrlScript']."?tg=admocs";
	
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$this->array_urls[bab_translate("Add-ons")] = $GLOBALS['babUrlScript']."?tg=addons";
	
	$this->head = bab_translate("Currently you administer ");
	if( $babBody->currentAdmGroup == 0 )
		$this->head .= bab_translate("all site");
	else
		$this->head .= bab_getGroupName($babBody->currentAdmGroup);
	$this->foot = "";

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		{
		reset($babBody->babaddons);
		while( $row=each($babBody->babaddons) ) 
			{
			$row = $row[1];
			$acces =false;
			if (is_file($GLOBALS['babAddonsPath'].$row['title']."/addonini.php"))
				$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$row['title']."/addonini.php");
			else $acces =true;
			if($row['access'] && (($arr_ini['version'] == $row['version']) || $acces))
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
		{
		return;
		}

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
	$bemail = false;
	$idcal = 0;
	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->blogged = true;
		$vacacc = bab_vacationsAccess();
		if( count($vacacc) > 0)
			{
			$this->vacwaiting = isset($vacacc['approver']) ? $vacacc['approver'] : '';
			$vac = true;
			}

		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			$bemail = true;
		}


	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		if( count($babBody->topsub) > 0  || count($babBody->topmod) > 0 )
			{
			$this->array_urls[bab_translate("Publication")] = $GLOBALS['babUrlScript']."?tg=artedit";
			}

		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 )
			{
			$this->array_urls[bab_translate("Approbations")] = $GLOBALS['babUrlScript']."?tg=approb";
			}
		}

	if( count($babBody->topman) > 0 || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
		{
		$this->array_urls[bab_translate("Articles management")] = $GLOBALS['babUrlScript']."?tg=topman";
		}

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
		{
		$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=vacuser";
		}
	if( bab_calendarAccess() )
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
		$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=calendar&idx=".$view."&calid=".$babBody->calendarids[0]['id'];
		}
	if( $bemail )
		{
		$this->array_urls[bab_translate("Mail")] = $GLOBALS['babUrlScript']."?tg=inbox";
		}
	if( !empty($GLOBALS['BAB_SESS_USER']) && bab_contactsAccess())
		{
		$this->array_urls[bab_translate("Contacts")] = $GLOBALS['babUrlScript']."?tg=contacts";
		}
	bab_fileManagerAccessLevel();
	if( $babBody->ustorage || count($babBody->aclfm) > 0 )
		{
		$this->array_urls[bab_translate("File manager")] = $GLOBALS['babUrlScript']."?tg=fileman";
		}

	$bdiradd = false;
	$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( $row['id_group'] != 0 )
			{
			list($bdiraccess) = $babDB->db_fetch_row($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
			}
		else
			$bdiraccess = 'Y';
		if($bdiraccess == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
			{
			$bdiradd = true;
			break;
			}
		}

	if( $bdiradd === false )
		{
		$res = $babDB->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL."");
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id']))
				{
				$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=directory";
				break;
				}
			}
		}

	if( $bdiradd )
		{
		$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=directory";
		}

	if( count($babBody->ocids) > 0 )
		{
		$this->array_urls[bab_translate("Charts")] = $GLOBALS['babUrlScript']."?tg=charts";
		}

		reset($babBody->babaddons);
		while( $row=each($babBody->babaddons) ) 
			{
			$row = $row[1];
			$acces =false;
			if (is_file($GLOBALS['babAddonsPath'].$row['title']."/addonini.php"))
				$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$row['title']."/addonini.php");
			else $acces =true;
			if($row['access'] && (($arr_ini['version'] == $row['version']) || $acces))
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
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&file=newarticles.html&nbdays=0";
				break;
			case 1:
				$this->newcount = $babBody->newcomments;
				$this->newtext = bab_translate("Comments");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&file=newcomments.html&nbdays=0";
				break;
			case 2:
				$this->newcount = $babBody->newposts;
				$this->newtext = bab_translate("Replies");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&file=newposts.html&nbdays=0";
				break;
			case 3:
				$this->newcount = $babBody->newfiles;
				$this->newtext = bab_translate("Files");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&file=newfiles.html&nbdays=0";
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

	$res = $babDB->db_query("select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='0' and type='1' order by ordering asc");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( in_array($row['id_topcat'], $babBody->topcatview) )
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			if( !in_array($row['id_topcat'], $this->arrid))
				array_push($this->arrid, $row['id_topcat']);
			}
		}
	$this->head = bab_translate("List of different topics categories");
	$this->count = count($this->arrid);

	if($this->count > 0)
		{
		$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN(".implode(',', $this->arrid).")");
		$this->count = $babDB->db_num_rows($this->res);
		}
	}

function topcatGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$this->arr = $babDB->db_fetch_array($this->res);
		$this->text = $this->arr['title'];
		$this->url = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arr['id'];
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

	$req = "select top.id topid, type, top.id_topcat id, lang, idsaart, idsacom from ".BAB_TOPCAT_ORDER_TBL." top LEFT JOIN ".BAB_TOPICS_TBL." t ON top.id_topcat=t.id and top.type=2 LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tc ON top.id_topcat=tc.id and top.type=1 where top.id_parent='".$cat."' order by top.ordering asc";
	$res = $babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['type'] == 2 && in_array($arr['id'], $babBody->topview))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}

			$whatToFilter = $GLOBALS['babLangFilter']->getFilterAsInt();
			if(($arr['lang'] == '*') or ($arr['lang'] == ''))
				$whatToFilter = 0;
			else if((isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose') and ( bab_isUserTopicManager($arr['id']) or bab_isCurrentUserApproverFlow($arr['idsaart']) or bab_isCurrentUserApproverFlow($arr['iddacom'])))
				$whatToFilter = 0;

			if(($whatToFilter == 0)	or ($whatToFilter == 1 and (substr($arr['lang'], 0, 2) == substr($GLOBALS['babLanguage'], 0, 2)))
				or ($whatToFilter == 2 and ($arr['lang'] == $GLOBALS['babLanguage'])))
				array_push($this->arrid, $arr['topid']);
			}
		else if( $arr['type'] == 1 && in_array($arr['id'], $babBody->topcatview))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			array_push($this->arrid, $arr['topid']);
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
	if($this->count > 0)
		{
		$inclause = implode(',', $this->arrid);
		$this->res = $babDB->db_query("SELECT tot.id, tot.id_topcat, tot.type, tt.id AS id_tt, tt.idsacom, tt.idsaart, tt.category, tct.id AS id_tct, tct.title FROM ".BAB_TOPCAT_ORDER_TBL." AS tot LEFT JOIN ".BAB_TOPICS_TBL." AS tt ON tt.id=tot.id_topcat LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." AS tct ON tct.id=tot.id_topcat WHERE tot.id IN(".$inclause.") ORDER BY tot.ordering");
		$this->count = $babDB->db_num_rows($this->res);
		}
	} // function babTopicsSection

function topicsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID, $babInstallPath;
	include_once $babInstallPath."utilit/afincl.php";
	static $i = 0;
	if( $i < $this->count)
		{
		$arr = $babDB->db_fetch_array($this->res, "fff".$i);

		if( $arr['type'] == 2 )
			{
			$this->newa = "";
			$this->newc = "";
			if( bab_isCurrentUserApproverFlow($arr['idsaart']))
				{
				$this->bfooter = 1;
				if( count(bab_getWaitingArticles($arr['id_tt'])) > 0 )
					{
					$this->newa = "a";
					}
				}

			if( bab_isCurrentUserApproverFlow($arr['idsacom']))
				{
				if( count(bab_getWaitingComments($arr['id_tt'])) > 0 )
					{
					$this->newc = "c";
					}
				}
			$this->text = $arr['category'];
			$this->url = $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id_tt'];
			}
		else if( $arr['type'] == 1 )
			{
			$this->newa = "";
			$this->newc = "";
			$this->text = $arr['title'];
			$this->url = $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id_tct'];
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

	$res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where active='Y' order by ordering asc");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			array_push($this->arrid, $row);
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
		$this->arr = $this->arrid[$i];
		$this->text = $this->arr['name'];
		$this->url = $GLOBALS['babUrlScript']."?tg=threads&forum=".$this->arr['id'];
		$this->waiting = "";
		if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->arr['id']))
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
		$i++;
		return true;
		}
	else
		{
		return false;
		}
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
var $topsub = array();
var $topcom = array();
var $topmod = array();
var $topman = array();
var $topcatview = array();
var $topcats = array(); /* all topics categories */
var $calaccess;
var $isSuperAdmin;
var $currentAdmGroup; /* current group administrated by current user */	
var $currentDGGroup; /* contains database row of current delegation groups */
var $dgAdmGroups; /* all groups administrated by current user */
var $ovgroups; /* all ovidentia groups */
var $babsite;
var $ocids; /* orgnization chart ids */
var $calendarids = array(); /* calendar ids */

//var $aclfm;
//var $babsite;
//var $waitingarticles;
//var $waitingcomments;

function babBody()
{
	global $babDB;
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
	$this->isSuperAdmin = false;
	$this->currentAdmGroup = 0;
	$this->currentDGGroup = array();
	$this->dgAdmGroups = array();
	$this->usergroups = array();
	$this->saarray = array();
	$this->babaddons = array();

	$res = $babDB->db_query("select * from ".BAB_GROUPS_TBL."");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$arr['member'] = 'N';
		$arr['primary'] = 'N';
		$this->ovgroups[$arr['id']] = $arr;
	}

}

function resetContent()
{
	$this->content = "";
}

function babecho($txt)
{
	$this->content .= $txt;
}

function loadSections()
{
	global $babDB, $babBody, $BAB_SESS_LOGGED, $BAB_SESS_USERID;

	$req = "SELECT ".BAB_SECTIONS_ORDER_TBL.".*, ".BAB_SECTIONS_STATES_TBL.".closed, ".BAB_SECTIONS_STATES_TBL.".hidden, ".BAB_SECTIONS_STATES_TBL.".id_section AS states_id_section FROM ".BAB_SECTIONS_ORDER_TBL." LEFT JOIN ".BAB_SECTIONS_STATES_TBL." ON ".BAB_SECTIONS_STATES_TBL.".id_section=".BAB_SECTIONS_ORDER_TBL.".id_section AND ".BAB_SECTIONS_STATES_TBL.".type=".BAB_SECTIONS_ORDER_TBL.".type AND ".BAB_SECTIONS_STATES_TBL.".id_user='".$BAB_SESS_USERID."' ORDER BY ".BAB_SECTIONS_ORDER_TBL.".ordering ASC";
	$res = $babDB->db_query($req);
	$arrsections = array();
	$arrsectionsinfo = array();
	$arrsectionsbytype = array();
	$arrsectionsorder = array();

	while($arr =  $babDB->db_fetch_array($res))
		{
			$objectid = $arr['id'];

			$arrsectioninfo = array('close'=>0, 'bshow'=>true);
			$typeid = $arr['type'];
			$sectionid = $arr['id_section'];

			if(isset($arr['states_id_section']) && !empty($arr['states_id_section']))
				{
					if( $arr['closed'] == "Y")
						{
							$arrsectioninfo['close'] = 1;
						}
					if( $arr['hidden'] == "Y")
						{
							$arrsectioninfo['bshow'] = false;
						}
				}

			if($typeid == 1 || $typeid == 3 || $typeid == 4)
				{
					$arrsectionsbytype[$typeid][$sectionid] = $objectid;
					$arrsectioninfo['type'] = $typeid;
				}
			else
				{
					$arrsectionsbytype['users'][$sectionid] = $objectid;
					$arrsectioninfo['type'] = $typeid;
				}

			$arrsectioninfo['position'] = $arr['position'];
			$arrsectioninfo['sectionid'] = $sectionid;
			$arrsectionsinfo[$objectid] = $arrsectioninfo;

			$arrsectionsorder[] = $objectid;
		}

	// BAB_PRIVATE_SECTIONS_TBL

	$type = 1;
	if(!empty($arrsectionsbytype[$type]))
		{
			$res2 = $babDB->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id IN(".implode(',', array_keys($arrsectionsbytype[$type])).")");
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$arrdbinfo[$arr2['id']] = $arr2;
				}
			foreach($arrsectionsbytype[$type] AS $sectionid => $objectid)
				{
					$arr2 = $arrdbinfo[$sectionid];
					$arrsectioninfo = $arrsectionsinfo[$objectid];

					switch($sectionid)
						{
							case 1: // admin
								if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
									{
										$sec = new babAdminSection($arrsectioninfo['close']);
										$arrsections[$objectid] = $sec;
									}
								break;
							case 2: // month
								if( $arr2['enabled'] == "Y" && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
									{
										$sec = new babMonthA();
										$arrsections[$objectid] = $sec;
									}
								break;
							case 3: // topics
								$sec = new babTopcatSection($arrsectioninfo['close']);
								if( $sec->count > 0 )
									{
										if( $arr2['enabled'] == "Y" && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
											{
												$arrsections[$objectid] = $sec;
											}
									}
								break;
							case 4: // Forums
								$sec = new babForumsSection($arrsectioninfo['close']);
								if( $sec->count > 0 )
									{
										if( $arr2['enabled'] == "Y"  && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
											{
												$arrsections[$objectid] = $sec;
											}
									}
								break;
							case 5: // user's section
								if( $arr2['enabled'] == "Y" )
									{
										$sec = new babUserSection($arrsectioninfo['close']);
										$arrsections[$objectid] = $sec;
									}
								break;
						}
				}
		}

	// BAB_TOPICS_CATEGORIES_TBL sections
	$type = '3';
	if(!empty($arrsectionsbytype[$type]))
		{
			$res2 = $babDB->db_query("select id, enabled, optional from ".BAB_TOPICS_CATEGORIES_TBL." where id IN(".implode(',', array_keys($arrsectionsbytype[$type])).")");
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$sectionid = $arr2['id'];
					$objectid = $arrsectionsbytype[$type][$sectionid];
					if( $arr2['enabled'] == "Y" && ( $arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow'] ))
						{
							$sec = new babTopicsSection($sectionid, $arrsectionsinfo[$objectid]['close']);
							if( $sec->count > 0 )
								{
									$arrsections[$objectid] = $sec;
								}
						}
				}
		}

	// BAB_ADDONS_TBL sections
	$type = '4';
	if(!empty($arrsectionsbytype[$type]))
		{
			$at = array_keys($arrsectionsbytype[$type]);
			for($i=0; $i < count($at); $i++)
				{
					if( isset($babBody->babaddons[$at[$i]]))
					{
					$arr2 = $babBody->babaddons[$at[$i]];
					$sectionid = $arr2['id'];
					$objectid = $arrsectionsbytype[$type][$sectionid];
					$acces =false;
					if (is_file($GLOBALS['babAddonsPath'].$arr2['title']."/addonini.php"))
						$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$arr2['title']."/addonini.php");
					else $acces =true;
					if( $arr2['access']  && (($arr_ini['version'] == $arr2['version']) || $acces) && is_file($GLOBALS['babAddonsPath'].$arr2['title']."/init.php"))
						{
							$GLOBALS['babAddonFolder'] = $arr2['title'];
							$GLOBALS['babAddonTarget'] = "addon/".$sectionid;
							$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$sectionid."/";
							$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$arr2['title']."/";
							$GLOBALS['babAddonHtmlPath'] = "addons/".$arr2['title']."/";
							$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$arr2['title']."/";
							require_once( $GLOBALS['babAddonsPath'].$arr2['title']."/init.php" );
							$func = $arr2['title']."_onSectionCreate";
							if(function_exists($func))
								{
									if (!isset($template)) $template = false;
									if($func($stitle, $scontent, $template))
										{
											if( !$arrsectionsinfo[$objectid]['close'])
												{
												$sec = new babSection($stitle, $scontent);
												$sec->setTemplate($template);
												}
											else
												{
												$sec = new babSection($stitle, "");
												}
											$sec->setTemplate($arr2['title']);
											$arrsections[$objectid] = $sec;
										}
								}
						}
					}
				}
		}

	// user's sections
	$type = 'users';
	if(!empty($arrsectionsbytype[$type]))
		{
			$langFilterValues = $GLOBALS['babLangFilter']->getLangValues();
			$req = "SELECT * FROM ".BAB_SECTIONS_TBL." WHERE id IN(".implode(',', array_keys($arrsectionsbytype[$type])).") and enabled='Y'";
			if( count($langFilterValues) > 0 )
				{
					$req .= " AND SUBSTRING(lang, 1, 2 ) IN (".implode(',', $langFilterValues).")";
				}
			$res2 = $babDB->db_query($req);
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$sectionid = $arr2['id'];
					$objectid = $arrsectionsbytype[$type][$sectionid];
					if(bab_isAccessValid(BAB_SECTIONS_GROUPS_TBL, $sectionid))
						{
							if($arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow'])
								{
									if(!$arrsectionsinfo[$objectid]['close'])
										{
										if( $arr2['script'] == "Y")
											{
												eval("\$arr2['content'] = \"".$arr2['content']."\";");
											}
										$sec = new babSection($arr2['title'], $arr2['content']);
										}
									else
										{
										$sec = new babSection($arr2['title'], "");
										}
									$sec->setTemplate($arr2['template']);
									$arrsections[$objectid] = $sec;
								}
						}
				}
		}

	foreach($arrsectionsorder AS $objectid)
		{
			$sectionid = $arrsectionsinfo[$objectid]['sectionid'];
			$type = $arrsectionsinfo[$objectid]['type'];
			if(isset($arrsections[$objectid]))
				{
					$sec = $arrsections[$objectid];
					$sec->setPosition($arrsectionsinfo[$objectid]['position']);
					$sec->close = $arrsectionsinfo[$objectid]['close'];
					$sec->bbox = 1;
					if(empty($BAB_SESS_USERID))
						{
							$sec->bbox = 0;
						}
					if( $sec->close )
						{
							$sec->boxurl = $GLOBALS['babUrlScript']."?tg=options&idx=ob&s=".$sectionid."&w=".$type;
						}
					else
						{
							$sec->boxurl = $GLOBALS['babUrlScript']."?tg=options&idx=cb&s=".$sectionid."&w=".$type;
						}
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
var $curmonthevents = array();
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
		{
		$this->babCalendarStartDay = 0;
		}
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

		$arrgroups = array();
		while($arr = $babDB->db_fetch_array($res))
			{
				$arrgroups[] = $arr['id'];
			}
		if(!empty($arrgroups))
			{
				$ingroups = implode(',', $arrgroups);
				$res2 = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner IN(".$ingroups.") and type='2' and actif='Y'");
				while( $arr2 = $babDB->db_fetch_array($res2))
					{
						$this->idcals[] = $arr2['id'];
					}
			}

		$mktime = mktime(0,0,0,$this->currentMonth, 1,$this->currentYear);
		$daymin = date('Y-m-d', $mktime);
		$mktime = mktime(0,0,0,$this->currentMonth, $this->days,$this->currentYear);
		$daymax = date('Y-m-d', $mktime);
		if(count($this->idcals) > 0)
			{
			$currmonthevents = array();
			$res2 = $babDB->db_query('SELECT id_cal, IF(EXTRACT(MONTH FROM start_date)<'.$this->currentMonth.', 1, DAYOFMONTH(start_date)) AS start_day, IF(EXTRACT(MONTH FROM end_date)>'.$this->currentMonth.', '.$this->days.', DAYOFMONTH(end_date)) AS end_day FROM '.BAB_CAL_EVENTS_TBL.' WHERE id_cal IN ('.implode(',', $this->idcals).') AND ((end_date>=\''.$daymin.'\' AND end_date<=\''.$daymax.'\') OR (start_date<=\''.$daymax.'\' AND start_date>=\''.$daymin.'\'))');
			while($event = $babDB->db_fetch_array($res2))
				{
				for($day = $event['start_day'] ; $day<=$event['end_day']; $day++)
					{
						$currmonthevents[$day][] = $event['id_cal'];
					}
				}
			$this->currmonthevents = $currmonthevents;
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
						if(isset($this->currmonthevents[$this->day]) && !empty($this->currmonthevents[$this->day]))
							{
								$idcals = implode(',', array_unique($this->currmonthevents[$this->day]));
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

	if( !empty($BAB_SESS_USERID))
		{
		$res=$babDB->db_query("select id_group, isprimary from ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."'");
		$babBody->ovgroups[1]['member'] = 'Y'; /* registered user */
		while( $arr = $babDB->db_fetch_array($res))
			{
			$babBody->usergroups[] = $arr['id_group'];
			$babBody->ovgroups[$arr['id_group']]['member'] = 'Y';
			$babBody->ovgroups[$arr['id_group']]['primary'] = $arr['isprimary'];
			}
		}

	bab_getCalendarIds();

	$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		$arr['access'] = bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr['id']);
		$babBody->babaddons[$arr['id']] = $arr;
		}

	$res = $babDB->db_query("select id, id_cat from ".BAB_TOPICS_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $row['id']))
			{
			$babBody->topman[] = $row['id'];
			}

		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $row['id']) )
			{
			$babBody->topview[] = $row['id'];
			if( count($babBody->topcatview) == 0 || !in_array($row['id_cat'], $babBody->topcatview))
				{
				$babBody->topcatview[] = $row['id_cat'];
				}
			}
		if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $row['id']) )
			{
			$babBody->topsub[] = $row['id'];
			}
		if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $row['id']) )
			{
			$babBody->topcom[] = $row['id'];
			}
		if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $row['id']))
			{
			$babBody->topmod[] = $row['id'];
			}
		}

	$babBody->ocids = bab_orgchartAccess();

	if(!empty($babBody->topcatview))
		{
		$topcats = $babBody->topcatview;
		for( $i=0; $i < count($topcats); $i++ )
			{
			$cat = $topcats[$i];
			while( $babBody->topcats[$cat]['parent'] != 0 )
				{
				if( !in_array($babBody->topcats[$cat]['parent'], $babBody->topcatview))
					{
					$babBody->topcatview[] = $babBody->topcats[$cat]['parent'];
					}
				$cat = $babBody->topcats[$cat]['parent'];
				}
		}
		}

	$babBody->isSuperAdmin = false;

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
			
			if( $arr['skin'] != "" && is_dir("skins/".$arr['skin']))
				{
				$GLOBALS['babSkin'] = $arr['skin'];
				}

			if( $arr['style'] != ""  && is_file("skins/".$GLOBALS['babSkin']."/styles/".$arr['style']))
				{
				$GLOBALS['babStyle'] = $arr['style'];
				}

			$babBody->lastlog = $arr['lastlog'];

			if( count($babBody->topview) > 0 )
				{
				$res = $babDB->db_query("select id_topic, restriction from ".BAB_ARTICLES_TBL." where date >= '".$babBody->lastlog."'");
				while( $row = $babDB->db_fetch_array($res))
					{
					if( in_array($row['id_topic'], $babBody->topview) && ( $row['restriction'] == '' || bab_articleAccessByRestriction($row['restriction']) ))
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

			bab_fileManagerAccessLevel();

			$arrfid = array();
			if( isset($babBody->aclfm['id']))
				{
				for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
					{
					if($babBody->aclfm['down'][$i])
						{
						$arrfid[] = $babBody->aclfm['id'][$i];
						}
					}
				}

			if( count($arrfid) > 0 )
				{
				$req = "select count(f.id) from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.state='' and f.confirmed='Y' and f.id_owner IN (".implode(',', $arrfid).")";
				$req .= " and f.modified >= '".$babBody->lastlog."'";
				$req .= " order by f.modified desc";

				list($babBody->newfiles) = $babDB->db_fetch_row($babDB->db_query($req));
				}
			else
				$babBody->newfiles = 0;

			if( $babBody->ovgroups[3]['member'] == 'Y')
				$babBody->isSuperAdmin = true;

			$res = $babDB->db_query("select g.id from ".BAB_GROUPS_TBL." g, ".BAB_DG_USERS_GROUPS_TBL." dg where dg.id_object='".$BAB_SESS_USERID."' and dg.id_group=g.id_dggroup");
			while( $arr = $babDB->db_fetch_array($res) )
				{
				$babBody->dgAdmGroups[] = $arr['id'];
				}
			
			}
		}
	
	if (!isset($GLOBALS['REMOTE_ADDR'])) $GLOBALS['REMOTE_ADDR'] = '0.0.0.0';
	if (!isset($GLOBALS['HTTP_X_FORWARDED_FOR'])) $GLOBALS['HTTP_X_FORWARDED_FOR'] = '0.0.0.0';
	$res = $babDB->db_query("select id, id_dggroup from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);	
		if( $arr['id_dggroup'] != 0 && count($babBody->dgAdmGroups) > 0 && in_array($arr['id_dggroup'], $babBody->dgAdmGroups ))
			{
			$babBody->currentAdmGroup = $arr['id_dggroup'];
			$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.* from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id='".$babBody->currentAdmGroup."' and dg.id=g.id_dggroup"));
			}
		else if( !$babBody->isSuperAdmin && count($babBody->dgAdmGroups) > 0 )
			{
			$babBody->currentAdmGroup = $babBody->dgAdmGroups[0];
			$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.* from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id='".$babBody->dgAdmGroups[0]."' and dg.id=g.id_dggroup"));
			}

		$babDB->db_query("update ".BAB_USERS_LOG_TBL." set dateact=now(), remote_addr='".$GLOBALS['REMOTE_ADDR']."', forwarded_for='".$GLOBALS['HTTP_X_FORWARDED_FOR']."', id_dggroup='".$babBody->currentAdmGroup."' where id = '".$arr['id']."'");
		}
	else
		{
		if( !empty($BAB_SESS_USERID))
			{
			$userid = $BAB_SESS_USERID;
			if( !$babBody->isSuperAdmin && count($babBody->dgAdmGroups) > 0 )
				{
				$babBody->currentAdmGroup = $babBody->dgAdmGroups[0];
				$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.* from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id='".$babBody->dgAdmGroups[0]."' and dg.id=g.id_dggroup"));
				}
			}
		else
			{
			$userid = 0;
			}

		$babDB->db_query("insert into ".BAB_USERS_LOG_TBL." (id_user, sessid, dateact, remote_addr, forwarded_for, id_dggroup) values ('".$userid."', '".session_id()."', now(), '".$GLOBALS['REMOTE_ADDR']."', '".$GLOBALS['HTTP_X_FORWARDED_FOR']."', '".$babBody->currentAdmGroup."')");
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

	$req="select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass, DECODE(ldap_password, \"".$GLOBALS['BAB_HASH_VAR']."\") as ldap_password  from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res=$babDB->db_query($req);
	if ($babDB->db_num_rows($res) == 0)
		{
		$babBody->msgerror = bab_translate("Configuration error : babSiteName in config.php not match site name in administration sites configuration");
		}
	$arr = $babDB->db_fetch_array($res);
	$babBody->babsite = $arr;

	if( $arr['skin'] != "")
		{
		$GLOBALS['babSkin'] = $arr['skin'];
		}
	else {
		$GLOBALS['babSkin'] = "ovidentia"; }

	if( $arr['style'] != "")
		{
		$GLOBALS['babStyle'] = $arr['style'];
		}
	else {
		$GLOBALS['babStyle'] = "ovidentia.css"; }

	if( $arr['lang'] != "")
		{
		$GLOBALS['babLanguage'] = $arr['lang'];
		}
	else {
		$GLOBALS['babLanguage'] = "en"; }
	if( $arr['adminemail'] != "")
		{
		$GLOBALS['babAdminEmail'] = $arr['adminemail'];
		}
	else {
		$GLOBALS['babAdminEmail'] = "admin@your-domain.com"; }
	if( $arr['langfilter'] != "")
		{
		$GLOBALS['babLangFilter']->setFilter($arr['langfilter']);
		}
	else {
		$GLOBALS['babLangFilter']->setFilter(0); }
	// options bloc2
	if( $arr['total_diskspace'] != 0)
		{
		$GLOBALS['babMaxTotalSize'] = $arr['total_diskspace']*1048576;
		}
	elseif ($GLOBALS['babMaxTotalSize'] == 0) {
		$GLOBALS['babMaxTotalSize'] = "200000000";}
	if( $arr['user_diskspace'] != 0)
		{
		$GLOBALS['babMaxUserSize'] = $arr['user_diskspace']*1048576;
		}
	elseif ($GLOBALS['babMaxUserSize'] == 0) {
		$GLOBALS['babMaxUserSize'] = "30000000";}
	if( $arr['folder_diskspace'] != 0)
		{
		$GLOBALS['babMaxGroupSize'] = $arr['folder_diskspace']*1048576;
		}
	elseif ($GLOBALS['babMaxGroupSize'] == 0) {
		$GLOBALS['babMaxGroupSize'] = "50000000"; }
	if( $arr['maxfilesize'] != 0)
		{
		$GLOBALS['babMaxFileSize'] = $arr['maxfilesize']*1048576;
		}
	elseif ($GLOBALS['babMaxFileSize'] == 0) {
		$GLOBALS['babMaxFileSize'] = "30000000"; }
	if( $arr['uploadpath'] != "")
		{
		$GLOBALS['babUploadPath'] = $arr['uploadpath'];
		}

	if( !is_dir($GLOBALS['babUploadPath']."/addons/"))
		{
		bab_mkdir($GLOBALS['babUploadPath']."/addons/", $GLOBALS['babMkdirMode']);
		}

	if( $arr['babslogan'] != "")
		{
		$GLOBALS['babSlogan'] = $arr['babslogan'];
		}
	if( $arr['name_order'] != "")
		{
		$GLOBALS['babBody']->nameorder = explode(" ",$arr['name_order']);
		}
	else {
		$GLOBALS['babBody']->nameorder = Array('F','L');}
	if( $arr['remember_login'] == "Y")
		{
		$GLOBALS['babCookieIdent'] = true;
		$GLOBALS['c_nickname'] = '';
		}
	elseif ($arr['remember_login'] == "L")
		{
		$GLOBALS['babCookieIdent'] = 'login' ;
		$GLOBALS['c_nickname'] = isset($_COOKIE['c_nickname']) ? trim($_COOKIE['c_nickname']) : '';
		}
	else {
		$GLOBALS['babCookieIdent'] = false ; 
		$GLOBALS['c_nickname'] = '';
		}
	if( $arr['email_password'] == "Y") {
		$GLOBALS['babEmailPassword'] = true ; }
	else {
		$GLOBALS['babEmailPassword'] = false ; }

	$GLOBALS['babAdminName'] = $arr['adminname'];

	if( $arr['authentification'] == 1 ) // LDAP authentification
		{
		$babBody->babsite['registration'] ='N';
		$babBody->babsite['change_password'] ='N';
		$babBody->babsite['change_nickname'] ='N';
		}

	$res = $babDB->db_query("select id, title, description, id_parent from ".BAB_TOPICS_CATEGORIES_TBL."");
	while($arr = $babDB->db_fetch_array($res))
		{
		$babBody->topcats[$arr['id']]['parent'] = $arr['id_parent'];
		$babBody->topcats[$arr['id']]['title'] = $arr['title'];
		$babBody->topcats[$arr['id']]['description'] = $arr['description'];
		}

	$res = $babDB->db_query("select id, UNIX_TIMESTAMP(dateact) as time from ".BAB_USERS_LOG_TBL);
	while( $row  = $babDB->db_fetch_array($res))
		{
		if( $row['time'] + get_cfg_var('session.gc_maxlifetime') < time()) 
			{
			$res2 = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where id_author='0' and id_anonymous='".$row['id']."'");
			while( $arr  = $babDB->db_fetch_array($res2))
				{
				bab_deleteArticleDraft($arr['id']);
				}
			$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id='".$row['id']."'");
			}
		}

	$res = $babDB->db_query("select id,id_author, id_topic, id_article, date_submission from ".BAB_ART_DRAFTS_TBL." where result='".BAB_ART_STATUS_DRAFT."' and date_submission <= now() and date_submission !='0000-00-00 00:00:00'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
	include_once $GLOBALS['babInstallPath']."utilit/topincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/artincl.php";
	while( $arr  = $babDB->db_fetch_array($res))
		{
		$bsubmit = false;
		if( $arr['id_article'] != 0 )
			{
			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id  where at.id='".$arr['id_article']."'");
			if( $res && $babDB->db_num_rows($res) == 1 )
				{
				$rr = $babDB->db_fetch_array($res);
				if( ( $rr['allow_update'] != '0' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || bab_isAccessValidByUser(BAB_TOPICSMOD_GROUPS_TBL, $rr['id_topic'], $arr['id_author']) || ( $rr['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $arr['id_author'])))
					{
					$bsubmit = true;
					}
				}
			}

		if( $arr['id_topic'] != 0 && bab_isAccessValidByUser(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic'], $arr['id_author']))
			{
			$bsubmit = true;
			}

		if( $bsubmit )
			{
			bab_submitArticleDraft($arr['id']);
			}
		}
	}

	$res = $babDB->db_query("select id from ".BAB_ARTICLES_TBL." where date_archiving <= now() and date_archiving !='0000-00-00 00:00:00' and archive='N'");
	while( $arr  = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("update ".BAB_ARTICLES_TBL." set archive='Y' where id = '".$arr['id']."'");
		}
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
				$this->langFiles = array();
				$tmpLang = '';
				$i = 0;
				$tmpLangFiles[-1]='';
				while ($i < count($tmpLangFiles) - 1)
				{
					if ($tmpLangFiles[$i] != $tmpLangFiles[$i-1])
					{
						$this->langFiles[] = $tmpLangFiles[$i];
					}
					$i++;
				}
			} // function readLangFiles() // 2003-09-08
		
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
