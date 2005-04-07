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
	global $babBody;

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

				$this->text_toolbar = bab_editor_text_toolbar($editname,$this->what);

				// do not load script for ie < 5.5 to avoid javascript parsing errors

				preg_match("/MSIE\s+([\d|\.]*?);/", $_SERVER['HTTP_USER_AGENT'], $matches);
				$this->loadscripts = !isset($matches[1]) || ($matches[1] >= 5.5);

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

function bab_getGroupName($id)
	{
	global $babBody;
	switch( $id )
		{
		case 1:
			return bab_translate("Registered users");
		case 2:
			return bab_translate("Unregistered users");
		default:
			return isset($babBody->ovgroups[$id]) ? $babBody->ovgroups[$id]['name'] : '';
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
		if( in_array(1, $ids))
			{
			$req = "SELECT id, email, firstname, lastname FROM ".BAB_USERS_TBL." where disabled='0' and is_confirmed='1'";
			}
		else
			{
			$req = "SELECT distinct u.id, u.email, u.firstname, u.lastname FROM ".BAB_USERS_GROUPS_TBL." g, ".BAB_USERS_TBL." u WHERE u.disabled='0' and u.is_confirmed='1' and g.id_group IN (".implode(',', $ids).") AND g.id_object=u.id";
			}

		$db = $GLOBALS['babDB'];
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
		$db = $GLOBALS['babDB'];
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
			$arr['id'][] = $babBody->usergroups[$i];
			$arr['name'][] = $babBody->ovgroups[$babBody->usergroups[$i]]['name'];
			}
		return $arr;
		}
	if( !empty($id))
		{
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select ".BAB_GROUPS_TBL.".id, ".BAB_GROUPS_TBL.".name from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object=".$id." and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group");
		if( $res && $db->db_num_rows($res) > 0 )
			{
			while( $r = $db->db_fetch_array($res))
				{
				$arr['id'][] = $r['id'];
				$arr['name'][] = $r['name'];
				}
			}
		}
	return $arr;
	}

function bab_getGroups()
	{
	global $babBody;
	$arr = array();
	reset($babBody->ovgroups);
	while( $row=each($babBody->ovgroups) ) 
		{ 
		if( $row[1]['id'] != 1 && $row[1]['id'] != 2)
			{
			$arr['id'][] = $row[1]['id'];
			$arr['name'][] = $row[1]['name'];
			}
		}
	return $arr;
	}

function bab_composeUserName( $F, $L)
	{
	global $babBody;
	return trim(sprintf("%s %s", ${$babBody->nameorder[0]}, ${$babBody->nameorder[1]}));
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
	global $babBody;
	$add = false;
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id_group from ".$table." where id_object='".$idobject."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$row = $db->db_fetch_array($res);
		switch($row['id_group'])
			{
			case "0": // everybody
				$add = true;
				break;
			case "1": // users
					$res = $db->db_query("select id from ".BAB_USERS_TBL." where id='".$iduser."' and disabled!='1'");
					if( $res && $db->db_num_rows($res) > 0 )
						{
						$add = true;
						}
				break;
			case "2": // guests
				if( $iduser == 0 )
					{
					$add = true;
					}
				break;
			default:  //groups
				if( $iduser != 0 )
					{
					$res = $db->db_query("select ".BAB_USERS_GROUPS_TBL.".id from ".BAB_USERS_GROUPS_TBL." join ".$table." where ".$table.".id_object=".$idobject." and ".$table.".id_group=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_USERS_GROUPS_TBL.".id_object = '".$iduser."'");
					if( $res && $db->db_num_rows($res) > 0 )
						{
						$add = true;
						}
					}
				break;
			}
		}
	return $add;
}

function bab_isAccessValid($table, $idobject, $iduser='')
{
	global $babBody, $BAB_SESS_USERID, $BAB_SESS_LOGGED;

	if( $iduser != '')
		{
		return bab_isAccessValidByUser($table, $idobject, $iduser);
		}

	$ok = false;
	if( !isset($babBody->acltables[$table]))
		{
		$babBody->acltables[$table] = array();
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select * from ".$table."");
		while( $row = $db->db_fetch_array($res))
			{
			switch($row['id_group'])
				{
				case "0": // everybody
					$babBody->acltables[$table][$row['id_object']][1] = 1;
					$babBody->acltables[$table][$row['id_object']][2] = 1;
					break;
				case "1": // users
					$babBody->acltables[$table][$row['id_object']][1] = 1;
					break;
				case "2": // guests
					$babBody->acltables[$table][$row['id_object']][2] = 1;
					break;
				default:  //groups
					$babBody->acltables[$table][$row['id_object']][$row['id_group']]=1;
					break;
				}

			}
		}


	if( !$BAB_SESS_LOGGED )
		{
		if( isset($babBody->acltables[$table][$idobject][2]))
			{
			$ok = true;
			}
		}
	else
	{
		if( isset($babBody->acltables[$table][$idobject][1]))
			{
			$ok = true;
			}
		else
		{
			for( $i = 0; $i < count($babBody->usergroups); $i++)
			{
				if( isset($babBody->acltables[$table][$idobject][$babBody->usergroups[$i]]))
				{
					$ok = true;
					break;
				}

			}
		}
	}
	return $ok;
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

?>