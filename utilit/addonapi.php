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


/**
 * Returns a string containing the time formatted according to the user's preferences
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   int	$time	unix timestamp
 */
function bab_time($time)
	{
	if( $time < 0)
		return "";
	return date($GLOBALS['babTimeFormat'], $time);
	}

/**
 * Returns a unix timestamp corresponding to the string $time formatted as a MYSQL DATETIME
 * 
 * @access  public 
 * @return  int	unix timestamp
 * @param   string	(eg. '2006-03-10 17:37:02')
 */
function bab_mktime($time)
	{
	$arr = explode(" ", $time);
	if ('0000-00-00' == $arr[0]) {
		return -1;
	}
	$arr0 = explode("-", $arr[0]);
	if (isset($arr[1])) {
		$arr1 = explode(":", $arr[1]);
		return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
		} else {
		return mktime( 0,0,0,$arr0[1],$arr0[2],$arr0[0]);
		}
	}

/**
 * Returns a string containing the time formatted according to the format
 * 
 * Formatting options:
 * <pre>
 * %d   A short textual representation of a day, three letters
 * %D   day
 * %j   Day of the month with leading zeros
 * %m   A short textual representation of a month, three letters
 * %M   Month
 * %n   Numeric representation of a month, with leading zeros
 * %Y   A full numeric representation of a year, 4 digits
 * %y   A two digit representation of a year
 * %H   24-hour format of an hour with leading zeros
 * %i   Minutes with leading zeros
 * %S   user short date
 * %L   user long date
 * %T   user time format
 * <pre>
 * 
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   string	$format	desired format
 * @param   int	$time	unix timestamp
 */
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

/**
 * Returns a string containing the time formatted according to the user's preferences
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   int	$time	unix timestamp
 * @param   boolean $hour	(true == 'Ven 17 Mars 2006',
 *							false == 'Ven 17 Mars 2006 10:11')
 */
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


/**
 * Returns a string containing the time formatted according to the user's preferences
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   int	$time	unix timestamp
 * @param   boolean $hour	(true == '17/03/2006',
 *							false == '17/03/2006 10:11'
 */
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
						$clean_href = ereg_replace("[\"']([^(http|ftp|#|".preg_quote(basename($_SERVER['SCRIPT_NAME'])).")].*)[\"']", '"#"', $att_elem);
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
		$req = "select id from ".BAB_GROUPS_TBL." where name='".$db->db_escape_string($groupname)."'";
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
	$query = "select id from ".BAB_USERS_TBL." where email='".$db->db_escape_string($email)."'";
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

function bab_getUserIdByNickname($nickname)
	{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id from ".BAB_USERS_TBL." where nickname='".$db->db_escape_string($nickname)."'");
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
	$query = "select id from ".BAB_USERS_TBL." where hashname='".$db->db_escape_string($hash)."'";	
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
	$arr = array('id' => array(), 'name' => array());
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
	include_once $GLOBALS['babInstallPath']."admin/acl.php";

	$users = aclGetAccessUsers($table, $idobject);

	if( isset($users[ $iduser]))
	{
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

//Il manque la partie pour les ensemble de groupes
function bab_getUsersAccess($table)
{
	global $babBody;

	$db = &$GLOBALS['babDB'];

	$ids = array();

	$res = $db->db_query("select id_group from ".$table);
	while($row = $db->db_fetch_array($res))
		{
		$ids[] = $row['id_group'] - BAB_ACL_GROUP_TREE;
		}
	return bab_getGroupsMembers($ids);
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
	$url = $GLOBALS['babUrlScript']."?tg=month&amp;callback=".$callback;
	if( !empty($month))
	{
		$url .= "&amp;month=".$month;
	}
	if( !empty($year))
	{
		$url .= "&amp;year=".$year;
	}
	if( !empty($low))
	{
		$url .= "&amp;ymin=".$low;
	}
	if( !empty($high))
	{
		$url .= "&amp;ymax=".$high;
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

/**
 * since ovidentia 5.8.6 quotes are always striped
 * @deprecated
 */
function bab_isMagicQuotesGpcOn()
	{
	return false;
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
	$tplfound = false;
	
	if( !isset($GLOBALS['babUseNewTemplateParser']) || $GLOBALS['babUseNewTemplateParser'] == false)
		{
		$tpl = new babTemplate(); /* old template parser */
		}
	else
		{
		$tpl = new bab_Template();
		}

	$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
	if( file_exists( $filepath ) )
		{
		if( empty($section))
			{
			return $tpl->printTemplate($class,$filepath, '');
			}

		$arr = $tpl->getTemplates($filepath);
		for( $i=0; $i < count($arr); $i++)
			{
			if( $arr[$i] == $section )
				{
				$tplfound = true;
				break;
				}
			}
		}
	
	if( !$tplfound )
		{
		$filepath = $babSkinPath."templates/". $file;
		if( file_exists( $filepath ) )
			{
			if( empty($section))
				{
				return $tpl->printTemplate($class,$filepath, '');
				}

			$arr = $tpl->getTemplates($filepath);
			for( $i=0; $i < count($arr); $i++)
				{
				if( $arr[$i] == $section )
					{
					$tplfound = true;
					break;
					}
				}
			}

		}

	if( !$tplfound )
		{
		$filepath = $babInstallPath."skins/ovidentia/templates/". $file;
		if( file_exists( $filepath ) )
			{
			if( empty($section))
				{
				return $tpl->printTemplate($class,$filepath, '');
				}

			$arr = $tpl->getTemplates($filepath);
			for( $i=0; $i < count($arr); $i++)
				{
				if( $arr[$i] == $section )
					{
					$tplfound = true;
					break;
					}
				}
			}

		}

	if( $tplfound )
		return $tpl->printTemplate($class,$filepath, $section);
	else
		return '';
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

/* API Directories */

function bab_getUserDirFields($id = false)
	{
	if (false == $id) $id = &$GLOBALS['BAB_SESS_USERID'];
	$db = &$GLOBALS['babDB'];
	$query = "select * from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$id."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0) {
		return $db->db_fetch_assoc($res);
		}
	else
		return array();
	}


/*

BAB_DIR_ENTRY_ID_USER		: $id est un id utilisateur
BAB_DIR_ENTRY_ID			: $id est un id de fiche d'annuaire
BAB_DIR_ENTRY_ID_DIRECTORY	: liste des champs de l'annuaire
BAB_DIR_ENTRY_ID_GROUP		: liste des champs de l'annuaire de groupe
*/

function bab_getDirEntry($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = NULL ) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getDirEntry($id, $type, $id_directory);
	}

function bab_getUserDirectories() {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getUserDirectories();
	}

function bab_getUserDirEntryLink($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = false) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getUserDirEntryLink($id, $type, $id_directory);
	}


/* API Groups */
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
		$arr['description'][] = $row['description']; 
		}

	return $arr;
	}

function bab_createGroup( $name, $description, $managerid, $parent = 1)
{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	return bab_addGroup($name, $description, $managerid, 0, $parent);
}

function bab_updateGroup( $id, $name, $description, $managerid)
{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	return bab_updateGroupInfo($id, $name, $description, $managerid);
}

function bab_removeGroup($id)
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteGroup($id);
}


/* API Users */
function bab_registerUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $confirmed, &$error)
{
	return bab_addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $confirmed, $error);
}

function bab_attachUserToGroup($iduser, $idgroup)
{
	bab_addUserToGroup($iduser, $idgroup);
}

function bab_detachUserFromGroup($iduser, $idgroup)
{
	bab_removeUserFromGroup($iduser, $idgroup);
}

function bab_uppdateUserById($id, $info, &$error)
{
	global $babDB;
	$res = $babDB->db_query('select u.*, det.mn, det.id as id_entry from '.BAB_USERS_TBL.' u left join '.BAB_DBDIR_ENTRIES_TBL.' det on det.id_user=u.id where u.id=\''.$id.'\'');
	$arruq = array();
	$arrdq = array();

	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arruinfo = $babDB->db_fetch_array($res);

		if( is_array($info) && count($info) /*&& isset($info['disabled'])*/)
		{

			if( isset($info['password']) && empty($info['password']) )
			{
				$error = bab_translate("Empty password");
				return false;
			}

			if( isset($info['password']) )
			{
				$arruq[] = 'password=\''.md5(strtolower($info['password'])).'\'';
			}
			
			if( isset($info['disabled']))
			{
				if($info['disabled'])
				{
					$arruq[] =  'disabled=1';
				}
				else
				{
					$arruq[] =  'disabled=0';
				}
			}

			if( isset($info['email']))
			{
				$arruq[] =  'email=\''.addslashes($info['email']).'\'';
			}

			if( isset($info['sn']) || isset($info['givenname']) || isset($info['mn']))
			{
				if( isset($info['sn']) && empty($info['sn']))
				{
					$error = bab_translate( "Lastname is required");
					return false;
				}
				else
				{
					$lastname = $arruinfo['lastname'];
				}

				if( isset($info['givenname']) && empty($info['givenname']))
				{
					$error = bab_translate( "Firstname is required");
					return false;
				}
				else
				{
					$firstname = $arruinfo['firstname'];
				}

				if( isset($info['mn']))
				{
					$mn = $info['mn'];
				}
				else
				{
					$mn = $arruinfo['mn'];
				}

				$replace = array( " " => "", "-" => "");
				$hashname = md5(strtolower(strtr($firstname.$mn.$lastname, $replace)));
				$arruq[] =  'firstname=\''.addslashes($firstname).'\'';
				$arruq[] =  'lastname=\''.addslashes($lastname).'\'';
				$arruq[] =  'hashname=\''.$hashname.'\'';

				$arrdq[] =  'givenname=\''.addslashes($firstname).'\'';
				$arrdq[] =  'sn=\''.addslashes($lastname).'\'';
				$arrdq[] =  'mn=\''.addslashes($mn).'\'';

			}

			if( count($arruq))
			{
				$babDB->db_query('update '.BAB_USERS_TBL.' set '.implode(',', $arruq).' where id=\''.$id.'\'');
			}

			$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='0'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
					$fieldname = $rr['name'];
						switch( $fieldname )
						{
							case 'sn':
							case 'givenname':
							case 'mn':
								break;
							default:
								if( isset($info[$fieldname]))
								{
								$arrdq[] =  $fieldname.'=\''.addslashes($info[$fieldname]).'\'';
								}
								break;
						}

					}
				else
					{
					$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$fieldname = "babdirf".$arr['id'];
					if( isset($info[$fieldname]))
						{
						$res2 = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_fieldx='".$arr['id']."' and id_entry='".$arruinfo['id_entry']."'");
						if( $res2 && $babDB->db_num_rows($res2) > 0 )
							{
							$arr2 = $babDB->db_fetch_array($res2);
							$babDB->db_query("update ".BAB_DBDIR_ENTRIES_EXTRA_TBL." set field_value='".addslashes($info[$fieldname])."' where id='".$arr2['id']."'");
							}
						else
							{
							$babDB->db_query("insert into ".BAB_DBDIR_ENTRIES_EXTRA_TBL." (id_fieldx, id_entry, field_value) values('".$arr['id']."','".$arruinfo['id_entry']."','".addslashes($info[$fieldname])."')");
							}
						}
					}
				}

			if( count($arrdq))
			{
				$babDB->db_query('update '.BAB_DBDIR_ENTRIES_TBL.' set '.implode(',', $arrdq).' where id=\''.$arruinfo['id_entry'].'\'');
			}
			return true;
		}
		else
		{
			$error = bab_translate("Nothing Changed");
			return false;
		}
	}
	else
	{
		$error = bab_translate("Unknown user");
		return false;
	}
}

function bab_uppdateUserByNickname($nickname, $info, &$error)
{
	global $babDB;
	$res = $babDB->db_query('select id from '.BAB_USERS_TBL.' where nickname=\''.$babDB->db_escape_string($nickname).'\'');
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$arr = $babDB->db_fetch_array($res);
		return bab_uppdateUserById($arr['id'], $info, $error);
	}
	else
	{
		$error = bab_translate("Unknown user");
		return false;
	}
}

/**
 * push some content into the debug console
 * @param string|array|object
 */
function bab_debug($str)
{
	if (isset($_COOKIE['bab_debug'])) {
		if (!is_string($str)) {
			ob_start();
			print_r($str);
			$str = ob_get_contents();
			ob_end_clean();
		}
		if (isset($GLOBALS['bab_debug_messages'])) {
			$GLOBALS['bab_debug_messages'][] = $str;
		} else {
			$GLOBALS['bab_debug_messages'] = array($str);
		}
	}

	if (file_exists('bab_debug.txt') && is_writable('bab_debug.txt')) {
		if (!is_string($str)) {
			$str = print_r($str, true);
		}
		$h = fopen('bab_debug.txt', 'a');
		fwrite($h, date('d/m/Y H:i:s')."\n\n".$str."\n\n\n------------------------\n");
		fclose($h);
	}
}

/**
 * return the html for the debug console, usefull for popups
 * @return html
 */
function bab_getDebug() {
	if (bab_isUserAdministrator() && isset($GLOBALS['bab_debug_messages'])) {
		include_once $GLOBALS['babInstallPath']."utilit/devtools.php";
		return bab_f_getDebug();
	}
	return false;
}

/**
 * transform some plain text into html
 * available options :
 * <ul>
 * <li>BAB_HTML_ALL			: a combination of all the options</li>
 * <li>BAB_HTML_ENTITIES	: special characters will be replaced with html entities</li>
 * <li>BAB_HTML_AUTO		: the paragraphs tags will be added only if the text contein some text line-breaks</li>
 * <li>BAB_HTML_P			: double line breaks will be replaced by html paragraphs, if there is no double line breaks, all the text will be in one paragraph</li>
 * <li>BAB_HTML_BR			: Line-breaks will be replaced by html line breaks</li>
 * <li>BAB_HTML_LINKS		: url and email adress will be replaced by links</li>
 * <li>BAB_HTML_JS			: ' and " are encoded for javascript strings, not in BAB_HTML_ALL</li>
 * <li>BAB_HTML_REPLACE		: Replace ovidentia macro $XXX()</li>
 * </ul>
 * @param string $str
 * @param int [$opt] the default value for the option is BAB_HTML_ENTITIES
 * @return string html
 */
function bab_toHtml($str, $option = BAB_HTML_ENTITIES) {
	include_once $GLOBALS['babInstallPath'].'utilit/tohtmlincl.php';
	return bab_f_toHtml($str, $option);
}


/**
 * Return informations about search engine for files content
 * if the function return false, search engine is disabled
 * @return false|array
 */
function bab_searchEngineInfos() {
	include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
	
	if (isset($GLOBALS['babSearchEngine'])) {

		$obj = bab_searchEngineInfosObj($GLOBALS['babSearchEngine']);

		return array(
			'name'			=> $GLOBALS['babSearchEngine'],
			'description'	=> $obj->getDescription(),
			'types'			=> $obj->getAvailableMimeTypes(),
			'indexes'		=> bab_searchEngineIndexes()
		);
	}
	return false;
}


/**
 * Get the instance for the registry class
 * 
 * $instance->changeDirectory($dir)
 * $instance->setKeyValue($key, $value)
 * $instance->removeKey($key)
 * $instance->getValue($key)
 * $instance->getValueEx($key)
 * $instance->deleteDirectory()
 *
 * @see bab_registry
 */
function bab_getRegistryInstance() {
	static $_inst = null;
	if (null === $_inst) {
		include_once $GLOBALS['babInstallPath'].'utilit/registry.php';
		$_inst = new bab_registry();
	}

	return $_inst;
}

/**
 * Request param
 * @param string $name
 * @param mixed	$default
 * @return string
 */
function bab_rp($name, $default = '') {
	if (isset($_GET[$name])) {
		return $_GET[$name];
	}
	if (isset($_POST[$name])) {
		return $_POST[$name];
	}
	return $default;
}

/**
 * Return the current file content disposition ( attchement, inline, undefined )
 * @return mixed ('': undefined, 1: inline, 2: attachment )
 */
function bab_getFileContentDisposition() {
	if (!isset($GLOBALS['babFileContentDisposition']))
		return '';
	else
	{
		switch($GLOBALS['babFileContentDisposition'])
		{
			case 1: return 1;
			case 2: return 2;
			default: return '';
		}
	}

}


?>