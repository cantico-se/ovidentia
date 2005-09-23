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

function bab_time($time)
	{
	if( $time < 0)
		return "";
	return date($GLOBALS['babTimeFormat'], $time);
	}

function bab_mktime($time)
	{
	$arr = explode(" ", $time);
	$arr0 = explode("-", $arr[0]);
	if (isset($arr[1]))
		{
		$arr1 = explode(":", $arr[1]);
		return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
		}
	else
		{
		return mktime( 0,0,0,$arr0[1],$arr0[2],$arr0[0]);
		}
	}

function bab_formatDate($format, $time)
{
	global $babDays, $babMonths, $babShortMonths;
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
					$val = $babShortMonths[date("n", $time)];
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
				case 'S': /* user short date  */
					$val = bab_shortDate($time, false);
					break;
				case 'L': /* user long date  */
					$val = bab_longDate($time, false);
					break;
				case 'T': /* user time format  */
					$val = bab_time($time);
					break;
				}
			$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
			}
		}
	return $txt;
}

function bab_longDate($time, $hour=true)
	{
	if( $time < 0)
		return "";

	if( !isset($GLOBALS['babLongDateFormat']))
		{
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat("ddd dd MMMM yyyy");
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat("HH:mm");
		}

	if( !$hour )
		{
		return bab_formatDate($GLOBALS['babLongDateFormat'], $time );
		}
	else
		{
		return bab_formatDate($GLOBALS['babLongDateFormat'], $time )." ".date($GLOBALS['babTimeFormat'], $time);
		}
	}


function bab_shortDate($time, $hour=true)
	{
	if( $time < 0)
		return "";

	if( !isset($GLOBALS['babLongDateFormat']))
		{
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat("dd/mm/yyyy");
		}

	if( !$hour )
		{
		return bab_formatDate($GLOBALS['babShortDateFormat'], $time );
		}
	else
		{
		return bab_formatDate($GLOBALS['babShortDateFormat'], $time )." ".date($GLOBALS['babTimeFormat'], $time);
		}
	}

function bab_strftime($time, $hour=true)
	{
	return bab_longDate($time, $hour);
	}


function bab_editor($content, $editname, $formname, $heightpx=300, $what=3)
	{

	if( !class_exists('babEditorCls'))
		{
		class babEditorCls
			{
			var $editname;
			var $formname;
			var $contentval;

			function babEditorCls($content, $editname, $formname, $heightpx,$what)
				{
				$this->editname = $editname;
				$this->formname = $formname;
				$this->heightpx = $heightpx;
				$this->what = $what;
				$db = &$GLOBALS['babDB'];
				if (!list($use_editor,$this->filter_html) = $db->db_fetch_array($db->db_query("SELECT use_editor, filter_html FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$GLOBALS['babBody']->babsite['id']."'")))
					{
					$use_editor = 1;
					$this->filter_html = 0;
					}

				$this->t_filter_html = bab_translate("In this configuration, some html tags may be removed for security reasons");

				$this->text_toolbar = bab_editor_text_toolbar($editname,$this->what);

				// do not load script for ie < 5.5 to avoid javascript parsing errors

				preg_match("/MSIE\s+([\d|\.]*?);/", $_SERVER['HTTP_USER_AGENT'], $matches);
				$this->loadscripts = $use_editor && (!isset($matches[1]) || ($matches[1] >= 5.5));

				if( empty($content))
					{
					$this->contentval = "";
					}
				else
					{
					$this->contentval = htmlentities($content);
					}

				if( bab_isMagicQuotesGpcOn())
					{
					$this->contentval = stripslashes($this->contentval);
					}
				}	
			}
		}
	$temp = new babEditorCls($content, $editname, $formname, $heightpx,$what);
	return bab_printTemplate($temp,"uiutil.html", "babeditortemplate");
	}


function bab_editor_record(&$str)
	{
	$str = eregi_replace("((href|src)=['\"]?)".$GLOBALS['babUrl'], "\\1", $str);

	$db = &$GLOBALS['babDB'];

	if (!$arr = $db->db_fetch_array($db->db_query("SELECT * FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$GLOBALS['babBody']->babsite['id']."'")))
		{
		return;
		}

	if ($arr['filter_html'] == 0)
		{
		return;
		}

	$allowed_tags = explode(' ',$arr['tags']);
	$allowed_tags = array_flip($allowed_tags);

	$allowed_attributes = explode(' ',$arr['attributes']);
	$allowed_attributes = array_flip($allowed_attributes);

	$worked = array();

	preg_match_all("/<\/?([^>]+?)\/?>/i",$str,$out);

	$nbtags = count($out[0]);
	for($i = 0; $i < $nbtags ; $i++)
		{
		$tag  = &$out[0][$i];
		
		list($tmp) = explode(' ',trim($out[1][$i]));
		$name = strtolower($tmp);

		if (!isset($worked[$tag]))
			{
			$worked[$tag] = 1;
			if (isset($allowed_tags[$name]))
				{
				// work on attributes
				preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $out[1][$i], $elements);

				$worked_attributes = array();

				for($j = 0 ; $j < count($elements[0]) ; $j++ )
					{
					$att_elem = &$elements[0][$j];
					$att_name = strtolower($elements[1][$j]);

					if (!empty($att_name) && !isset($allowed_attributes[$att_name]))
						{
						$worked_attributes[$att_elem] = 1;
						$replace_tag = str_replace($att_elem,'',$tag);
						$str = preg_replace("/".preg_quote($tag,"/")."/", $replace_tag, $str);
						}


					if (!empty($att_name) && isset($allowed_attributes[$att_name]) && $att_name == 'href' && $arr['verify_href'] == 1)
						{
						$worked_attributes[$att_elem] = 1;
						$clean_href = ereg_replace("[\"']([^(http|ftp)].*)[\"']", '"#"', $att_elem);
						$replace_tag = str_replace($att_elem, $clean_href, $tag);

						$str = preg_replace("/".preg_quote($tag,"/")."/", $replace_tag, $str);

						}
					}
				}
			else
				{
				$str = preg_replace("/".preg_quote($tag,"/")."/", ' ', $str);
				}
			}
		}
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

function bab_isUserAdministrator()
	{
	global $babBody;
	return $babBody->isSuperAdmin;
	}

function bab_isUserGroupManager($grpid="")
	{
	global $babBody, $BAB_SESS_USERID;
	if( empty($BAB_SESS_USERID))
		return false;

	reset($babBody->ovgroups);
	while( $arr=each($babBody->ovgroups) ) 
		{ 
		if( $arr[1]['manager'] == $GLOBALS['BAB_SESS_USERID'])
			{
			if( empty($grpid))
				{
				return true;
				}
			else if( $arr[1]['id'] == $grpid )
				{
				return true;
				}
			}
		}
	}

function bab_getUserName($id)
	{
	static $arrnames = array();

	if( isset($arrnames[$id]) )
		{
		return $arrnames[$id];
		}

	$db = $GLOBALS['babDB'];
	$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$arrnames[$id] = bab_composeUserName($arr['firstname'], $arr['lastname']);
		}
	else
		{
		$arrnames[$id] = "";
		}
	return $arrnames[$id];
	}

function bab_getUserEmail($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select email from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['email'];
		}
	else
		{
		return "";
		}
	}

function bab_getUserNickname($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select nickname from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['nickname'];
		}
	else
		{
		return "";
		}
	}

function bab_getUserSetting($id, $what)
	{
	$db = $GLOBALS['babDB'];
	$query = "select ".$what." from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[$what];
		}
	else
		{
		return "";
		}
	}

function bab_getGroupName($id, $fpn=true)
	{
	global $babBody;
	if($fpn)
		{
		return $babBody->getGroupPathName($id);
		}
	else
		{
		return $babBody->ovgroups[$id]['name'];
		}
	}

function bab_getPrimaryGroupId($userid)
	{
	$db = $GLOBALS['babDB'];
	if( empty($userid) || $userid == 0 )
		return "";
	$query = "select id_group from ".BAB_USERS_GROUPS_TBL." where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id_group'];
		}
	else
		{
		return "";
		}
	}

function bab_getGroupsMembers($ids)
	{
	if (!is_array($ids))
		{
		$ids = array($ids);
		}

	if( is_array($ids) && count($ids) > 0 )
		{
		$db = &$GLOBALS['babDB'];

		if( in_array(BAB_REGISTERED_GROUP, $ids))
			{
			$req = "SELECT id, email, firstname, lastname FROM ".BAB_USERS_TBL." where disabled='0' and is_confirmed='1'";
			}
		else
			{
			global $babBody;

			foreach($ids as $idg)
				{
				if ($babBody->ovgroups[$idg]['nb_groups'] > 0)
					{
					$res = $db->db_query("SELECT id_group FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$idg."'");
					while ($arr = $db->db_fetc_assoc($res))
						{
						$ids[] = $arr['id_group'];
						}
					}
				}

			$req = "SELECT distinct u.id, u.email, u.firstname, u.lastname FROM ".BAB_USERS_GROUPS_TBL." g, ".BAB_USERS_TBL." u WHERE u.disabled='0' and u.is_confirmed='1' and g.id_group IN (".implode(',', $ids).") AND g.id_object=u.id";
			}

		
		$res = $db->db_query($req);
		$users = array();
		if( $res && $db->db_num_rows($res) > 0)
			{
			$i = 0;
			while ($arr = $db->db_fetch_array($res))
				{
				$users[$i]['id'] = $arr['id'];
				$users[$i]['name'] = bab_composeUserName($arr['firstname'],$arr['lastname']);
				$users[$i]['email'] = $arr['email'];
				$i++;
				}
			return $users;
			}
		}
	else
		return false;
	}

function bab_isMemberOfGroup($groupname, $userid="")
{
	global $BAB_SESS_USERID;
	if( !empty($groupname))
		{
		if( $userid == "")
			$userid = $BAB_SESS_USERID;
		$db = &$GLOBALS['babDB'];
		$req = "select id from ".BAB_GROUPS_TBL." where name='$groupname'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='$userid' and id_group='".$arr['id']."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				return $arr['id'];
			else
				return 0;
			}
		else
			return 0;
		}
	else
		return 0;
}

function bab_getUserIdByEmail($email)
	{
	$db = $GLOBALS['babDB'];
	$query = "select id from ".BAB_USERS_TBL." where email='$email'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id'];
		}
	else
		{
		return 0;
		}
	}

function bab_getUserId( $name )
	{
	$replace = array( " " => "", "-" => "");
	$db = $GLOBALS['babDB'];
	$hash = md5(strtolower(strtr($name, $replace)));
	$query = "select id from ".BAB_USERS_TBL." where hashname='".$hash."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id'];
		}
	else
		return 0;
	}
	
function bab_getUserGroups($id = "")
	{
	global $babBody;
	$arr = array();
	if( empty($id))
		{
		for( $i = 0; $i < count($babBody->usergroups); $i++ )
			{
			if( $babBody->usergroups[$i] != BAB_REGISTERED_GROUP && $babBody->usergroups[$i] != BAB_UNREGISTERED_GROUP && $babBody->usergroups[$i] != BAB_ALLUSERS_GROUP)
				{
				$arr['id'][] = $babBody->usergroups[$i];
				$arr['name'][] = $babBody->getGroupPathName($babBody->usergroups[$i]);
				}
			}
		return $arr;
		}
	if( !empty($id))
		{
		$db = &$GLOBALS['babDB'];
		$res = $db->db_query("select id_group from ".BAB_USERS_GROUPS_TBL." where id_object=".$id."");
		if( $res && $db->db_num_rows($res) > 0 )
			{
			while( $r = $db->db_fetch_array($res))
				{
				$arr['id'][] = $r['id_group'];
				$arr['name'][] = $babBody->getGroupPathName($r['id_group']);
				}
			}
		}
	return $arr;
	}

function bab_getGroups()
	{
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

	$tree = new bab_grptree();
	$groups = $tree->getGroups(BAB_REGISTERED_GROUP);
	$arr = array();
	foreach ($groups as $row)
		{
		$arr['id'][] = $row['id'];
		$arr['name'][] = $row['name'];
		}

	return $arr;
	}

function bab_composeUserName( $F, $L)
	{
	global $babBody;
	if( isset($babBody->nameorder))
		return trim(sprintf("%s %s", ${$babBody->nameorder[0]}, ${$babBody->nameorder[1]}));
	else
		return trim(sprintf("%s %s", $F, $L));
	}

/* for current user */
function bab_userIsloggedin()
	{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;

	if (isset($BAB_SESS_LOGGED))
		{
		return $BAB_SESS_LOGGED;
		}
	if (!empty($BAB_SESS_NICKNAME) && !empty($BAB_SESS_HASHID))
		{
		$hash=md5($BAB_SESS_NICKNAME.$BAB_HASH_VAR);
		if ($hash == $BAB_SESS_HASHID)
			{
			$BAB_SESS_LOGGED=true;
			}
		else
			{
			$BAB_SESS_LOGGED=false;
			}
		}
	else
		{
		$BAB_SESS_LOGGED=false;
		}
    return $BAB_SESS_LOGGED;
	}

function bab_isAccessValidByUser($table, $idobject, $iduser)
{
	$db = &$GLOBALS['babDB'];

	$res = $db->db_query("select 
							t.id_group 
						FROM 
								".$table." t, 
								".BAB_USERS_GROUPS_TBL." u  
						WHERE 
								t.id_object='".$idobject."' 
								AND (
								(u.id_object='".$iduser."' AND u.id_group = t.id_group) 
								OR 
								t.id_group >= '".BAB_ACL_GROUP_TREE."')
							");

	if( $res && $db->db_num_rows($res) > 0)
		{
		$row = $db->db_fetch_assoc($res);
		if ($row['id_group'] >= BAB_ACL_GROUP_TREE)
			{
			return bab_isMemberOfTree($row['id_group'], $iduser);
			}
		return true;
		}
	return false;
}

function bab_isAccessValid($table, $idobject, $iduser='')
{
	if( $iduser != '')
		{
		return bab_isAccessValidByUser($table, $idobject, $iduser);
		}

	if( !isset($_SESSION['bab_groupAccess']['acltables'][$table]))
		{
		bab_getUserIdObjects($table);
		}

	return isset($_SESSION['bab_groupAccess']['acltables'][$table][$idobject]);
}


function bab_getUserIdObjects($table)
{
global $babBody;
if( !isset($_SESSION['bab_groupAccess']['acltables'][$table]))
	{
	$_SESSION['bab_groupAccess']['acltables'][$table] = array();
	
	$db = &$GLOBALS['babDB'];
	$res = $db->db_query("select id_object, id_group from ".$table." WHERE id_group IN('".implode("','",$babBody->usergroups)."') OR id_group>='".BAB_ACL_GROUP_TREE."'");
	
	while( $row = $db->db_fetch_assoc($res))
		{
		if ($row['id_group'] >= BAB_ACL_GROUP_TREE )
			{
			$row['id_group'] -= BAB_ACL_GROUP_TREE;
			if (bab_isMemberOfTree($row['id_group']))
				$_SESSION['bab_groupAccess']['acltables'][$table][$row['id_object']] = 1;
			}
		else
			$_SESSION['bab_groupAccess']['acltables'][$table][$row['id_object']] = 1;
		}
	}

	return $_SESSION['bab_groupAccess']['acltables'][$table];
}


function bab_getGroupsAccess($table, $idobject)
{
	global $babBody;

	$db = &$GLOBALS['babDB'];

	$ret = array();

	$res = $db->db_query("select id_group from ".$table." where id_object='".$idobject."'");
	while( $row = $db->db_fetch_array($res))
		{
		if ($row['id_group'] >= BAB_ACL_GROUP_TREE)
			{
			$row['id_group'] -= BAB_ACL_GROUP_TREE;
			foreach($babBody->ovgroups as $arr)
				{
				if ($arr['lf'] >= $babBody->ovgroups[$row['id_group']]['lf'] && $arr['lr'] <= $babBody->ovgroups[$row['id_group']]['lr'])
					{
					$ret[] = $arr['id'];
					}
				}
			}
		else
			{
			$ret[] = $row['id_group'];
			}
		}
	return $ret;
}

function bab_calendarPopup($callback, $month='', $year='', $low='', $high='')
{
	$url = $GLOBALS['babUrlScript']."?tg=month&callback=".$callback;
	if( !empty($month))
	{
		$url .= "&month=".$month;
	}
	if( !empty($year))
	{
		$url .= "&year=".$year;
	}
	if( !empty($low))
	{
		$url .= "&ymin=".$low;
	}
	if( !empty($high))
	{
		$url .= "&ymax=".$high;
	}
	return "javascript:Start('".$url."','OVCalendarPopup','width=250,height=250,status=no,resizable=no,top=200,left=200')";
}

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

function bab_isMagicQuotesGpcOn()
	{
	$mqg = ini_get("magic_quotes_gpc");
	if( $mqg == 0 || strtolower($mqg) == "off" || !get_cfg_var("magic_quotes_gpc"))
		return false;
	else
		return true;
	}

function bab_getAvailableLanguages()
	{
	$langs = array();
	$h = opendir($GLOBALS['babInstallPath']."lang/"); 
	while ( $file = readdir($h))
		{ 
		if ($file != "." && $file != "..")
			{
			if( eregi("lang-([^.]*)", $file, $regs))
				{
				if( $file == "lang-".$regs[1].".xml")
					$langs[] = $regs[1]; 
				}
			} 
		}
	closedir($h);
	return $langs;
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

function bab_getActiveSessions()
{
	$db = &$GLOBALS['babDB'];
	$output = array();
	$res = $db->db_query("SELECT l.id_user,
								l.sessid,
								l.remote_addr,
								l.forwarded_for,
								UNIX_TIMESTAMP(l.dateact) dateact,
								u.firstname,
								u.lastname,
								u.email,
								UNIX_TIMESTAMP(u.lastlog) lastlog,
								UNIX_TIMESTAMP(u.datelog) datelog,
								UNIX_TIMESTAMP(u.date) registration  
								FROM ".BAB_USERS_LOG_TBL." l 
								LEFT JOIN ".BAB_USERS_TBL." u ON u.id=l.id_user");

	while($arr = $db->db_fetch_array($res))
		{
		$output[] = array(
						'id_user' => $arr['id_user'],
						'user_name' => bab_composeUserName($arr['firstname'], $arr['lastname']),
						'user_email' => $arr['email'],
						'session_id' => $arr['sessid'],
						'remote_addr' => $arr['remote_addr'] != 'unknown' ? $arr['remote_addr']  : '',
						'forwarded_for' => $arr['forwarded_for'] != 'unknown' ? $arr['forwarded_for']  : '',
						'registration_date' => $arr['registration'],
						'previous_login_date' => $arr['lastlog'],
						'login_date' => $arr['datelog'],
						'last_hit_date' => $arr['dateact'],
							);
		}
	return $output;
}


function bab_getFileMimeType($file)
{
	$mime = "application/octet-stream";
	if ($ext = strrchr($file,"."))
		{
		$ext = substr($ext,1);
		$db = &$GLOBALS['babDB'];
		$res = $db->db_query("select * from ".BAB_MIME_TYPES_TBL." where ext='".$ext."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$mime = $arr['mimetype'];
			}
		}
	return $mime;
}

?>