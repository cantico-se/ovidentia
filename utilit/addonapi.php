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
include_once 'base.php';

/**
* @internal SEC1 PR 18/01/2007 FULL
*/

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
 * @param   string	$time	(eg. '2006-03-10 17:37:02')
 */
function bab_mktime($time)
	{
	$arr = explode(" ", $time); //Split days and hours
	if ('0000-00-00' == $arr[0] || '' == $arr[0]) {
		return -1;
	}
	$arr0 = explode("-", $arr[0]); //Split year, month et day
	if (isset($arr[1])) { //If the hours exist we send back days and hours
		$arr1 = explode(":", $arr[1]);
		return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
		} else { //If the hours do not exist, we send back only days
		return mktime(0,0,0,$arr0[1],$arr0[2],$arr0[0]);
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
			if( isset($val))
				{
				$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
				}
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

/**
 * @deprecated
 */
function bab_editor($content, $editname, $formname, $heightpx=300, $what=3)
	{
	return '<textarea name="'.bab_toHtml($editname).'" cols="50" rows="10">'.bab_toHtml($content).'</textarea>';
	}

/**
 * @deprecated
 */
function bab_editor_record(&$str)
	{
	
	global $babDB;
	$str = eregi_replace("((href|src)=['\"]?)".$GLOBALS['babUrl'], "\\1", $str);

	if (!$arr = $babDB->db_fetch_array($babDB->db_query("SELECT * FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$babDB->db_escape_string($GLOBALS['babBody']->babsite['id'])."'")))
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

/**
* Return the username of a given user
*
* @param integer	$iIdUser			User identifier
* @param boolean	$bComposeUserName	If true the username will 
* 										be composed	
* 
* @return	mixed	If $bComposeUserName is true the retun value 
* 					is a string, the string is a concatenation of
* 					the firstname and lastname. The meaning depend 
* 					of ovidentia configuration.
* 					If $bComposeUserName is false the return value 
* 					is an array with two keys (firstname, lastname)   
*/
function bab_getUserName($iIdUser, $bComposeUserName = true)
{
	global $babDB;

	$sQuery = 
		'SELECT 
			firstname, 
			lastname 
		FROM ' . 
			BAB_USERS_TBL . ' 
		WHERE 
			id = ' . $babDB->quote($iIdUser);

	$aUserName[$iIdUser] = '';
			
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult && $babDB->db_num_rows($oResult) > 0)
	{
		$aDatas = $babDB->db_fetch_assoc($oResult);
		if(false !== $aDatas)
		{
			if(true === $bComposeUserName)
			{
				$aUserName[$iIdUser] = bab_composeUserName($aDatas['firstname'], $aDatas['lastname']);
			}
			else
			{
				$aUserName[$iIdUser] = $aDatas;
			}
		}
	}
	return $aUserName[$iIdUser];
}

function bab_getUserEmail($id)
	{
	global $babDB;
	$query = "select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['email'];
		}
	else
		{
		return "";
		}
	}

function bab_getUserNickname($id)
	{
	global $babDB;
	$query = "select nickname from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['nickname'];
		}
	else
		{
		return "";
		}
	}

function bab_getUserSetting($id, $what)
	{
	global $babDB;
	$query = "select ".$babDB->db_escape_string($what)." from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr[$what];
		}
	else
		{
		return "";
		}
	}

function bab_getPrimaryGroupId($userid)
	{
	global $babDB;
	if( empty($userid) || $userid == 0 )
		return "";
	$query = "select id_group from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($userid)."' and isprimary='Y'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id_group'];
		}
	else
		{
		return "";
		}
	}


/**
 * Get groups members
 * @param	int|array	$ids	id_group or an array of id_group
 * @return false|array
 */
function bab_getGroupsMembers($ids)
	{
	global $babDB;
	if (!is_array($ids))
		{
		$ids = array($ids);
		}

	if( is_array($ids) && count($ids) > 0 )
		{
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
					$res = $babDB->db_query("SELECT id_group FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$babDB->db_escape_string($idg)."'");
					while ($arr = $babDB->db_fetch_assoc($res))
						{
						$ids[] = $arr['id_group'];
						}
					}
				}

			$req = "SELECT distinct u.id, u.email, u.firstname, u.lastname FROM ".BAB_USERS_GROUPS_TBL." g, ".BAB_USERS_TBL." u WHERE u.disabled='0' and u.is_confirmed='1' and g.id_group IN (".$babDB->quote($ids).") AND g.id_object=u.id";
			}

		
		$res = $babDB->db_query($req);
		$users = array();
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$i = 0;
			while ($arr = $babDB->db_fetch_array($res))
				{
				$users[$i]['id'] = $arr['id'];
				$users[$i]['name'] = bab_composeUserName($arr['firstname'],$arr['lastname']);
				$users[$i]['email'] = $arr['email'];
				$i++;
				}
			return $users;
			}
		}

		return false;
	}


/**
 * Test if the user is member of a group
 * This function accept a group id since 6.1.1
 * Before 6.1.1 the function return 0 if the user is not member
 * After 6.1.1 if the current user is logged out the function can return BAB_ALLUSERS_GROUP or BAB_UNREGISTERED_GROUP or false
 * @since 	6.1.1
 * @param	int|string	$group		group id or group name
 * @return 	false|int				group id or false if the user is not a member
 */
function bab_isMemberOfGroup($group, $userid="")
{
	global $BAB_SESS_USERID, $babDB;
	if(empty($group)) {
		return false;
	}
		
	if( $userid == "")
		$userid = $BAB_SESS_USERID;
		
	if (is_numeric($group)) {
		$id_group = $group;
	} else {
		$req = "select id from ".BAB_GROUPS_TBL." where name='".$babDB->db_escape_string($group)."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			$id_group = $arr['id'];
		} else {
			return false;	
		}
	}
	
	switch($id_group) {
		case BAB_ALLUSERS_GROUP:
			return BAB_ALLUSERS_GROUP;
			
		case BAB_REGISTERED_GROUP:
			return $userid ? BAB_REGISTERED_GROUP : false;
			
		case BAB_UNREGISTERED_GROUP:
			return $userid ? false : BAB_UNREGISTERED_GROUP;
			
		default:
			$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($userid)."' and id_group='".$babDB->db_escape_string($id_group)."'";
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0)
				return $id_group;
			else
				return false;
	}
}

function bab_getUserIdByEmail($email)
	{
	global $babDB;
	$query = "select id from ".BAB_USERS_TBL." where email LIKE '".$babDB->db_escape_string($email)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		{
		return 0;
		}
	}

function bab_getUserIdByNickname($nickname)
	{
	global $babDB;
	$res = $babDB->db_query("select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		{
		return 0;
		}
	}

function bab_getUserId( $name )
	{
	global $babDB;
	$replace = array( " " => "", "-" => "");
	$hash = md5(strtolower(strtr($name, $replace)));
	$query = "select id from ".BAB_USERS_TBL." where hashname='".$babDB->db_escape_string($hash)."'";	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		return 0;
	}
	
function bab_getUserGroups($id = "")
	{
	global $babBody, $babDB;
	$arr = array('id' => array(), 'name' => array());
	if( empty($id))
		{
		for( $i = 0; $i < count($babBody->usergroups); $i++ )
			{
			if( $babBody->usergroups[$i] != BAB_REGISTERED_GROUP && $babBody->usergroups[$i] != BAB_UNREGISTERED_GROUP && $babBody->usergroups[$i] != BAB_ALLUSERS_GROUP)
				{
				$arr['id'][] = $babBody->usergroups[$i];
				$nm = $babBody->getGroupPathName($babBody->usergroups[$i]);
				if( empty($nm))
					{
					$nm =  $babBody->getSetOfGroupName($babBody->usergroups[$i]);
					}
				$arr['name'][] = $nm;
				}
			}
		return $arr;
		}
	if( !empty($id))
		{
		$res = $babDB->db_query("select id_group from ".BAB_USERS_GROUPS_TBL." where id_object=".$babDB->db_escape_string($id)."");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			while( $r = $babDB->db_fetch_array($res))
				{
				$arr['id'][] = $r['id_group'];
				$nm = $babBody->getGroupPathName($r['id_group']);
				if( empty($nm))
					{
					$nm =  $babBody->getSetOfGroupName($r['id_group']);
					}
				$arr['name'][] = $nm;
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

/**
 * Connexion status for current user 
 * @return boolean
 */
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
global $babBody, $babDB;
if( !isset($_SESSION['bab_groupAccess']['acltables'][$table]))
	{
	$_SESSION['bab_groupAccess']['acltables'][$table] = array();
	
	$res = $babDB->db_query("select id_object, id_group from ".$babDB->db_escape_string($table)." WHERE id_group IN(".$babDB->quote($babBody->usergroups).") OR id_group>='".BAB_ACL_GROUP_TREE."'");
	
	while( $row = $babDB->db_fetch_assoc($res))
		{
		if ($row['id_group'] >= BAB_ACL_GROUP_TREE )
			{
			$row['id_group'] -= BAB_ACL_GROUP_TREE;
			if (bab_isMemberOfTree($row['id_group']))
				$_SESSION['bab_groupAccess']['acltables'][$table][$row['id_object']] = $row['id_object'];
			}
		else
			$_SESSION['bab_groupAccess']['acltables'][$table][$row['id_object']] = $row['id_object'];
		}
	}

	return $_SESSION['bab_groupAccess']['acltables'][$table];
}


/**
 * @deprecated
 * @see aclGetAccessUsers() in admin/acl.php
 * Il manque la partie pour les ensemble de groupes
 */
function bab_getUsersAccess($table)
{
	global $babBody, $babDB;
	
	trigger_error('deprecated function bab_getUsersAccess');
	$babBody->addError('deprecated function bab_getUsersAccess');

	$ids = array();

	$res = $babDB->db_query("select id_group from ".$babDB->db_escape_string($table));
	while($row = $babDB->db_fetch_array($res))
		{
		$ids[] = $row['id_group'];
		}
	return bab_getGroupsMembers($ids);
}


/**
 * Get group list for access right
 * @param	string	$table
 * @param	int		$idobject
 * @return 	array
 */
function bab_getGroupsAccess($table, $idobject)
{
	global $babBody, $babDB;

	$ret = array();

	$res = $babDB->db_query("select id_group from ".$babDB->db_escape_string($table)." where id_object='".$babDB->db_escape_string($idobject)."'");
	while( $row = $babDB->db_fetch_array($res))
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
	if (!$res) {
		include_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
		bab_debug_print_backtrace();
	}
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

	$h = opendir("lang/"); 
	while ( $file = readdir($h))
		{ 
		if ($file != "." && $file != "..")
			{
			if( eregi("lang-([^.]*)", $file, $regs))
				{
				if( $file == "lang-".$regs[1].".xml" && !in_array($regs[1], $langs))
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
	
	if( isset($GLOBALS['babUseNewTemplateParser']) && $GLOBALS['babUseNewTemplateParser'] === false)
	{
		$tpl = new babTemplate(); /* old template parser */
	}
	else
	{
		$tpl = new bab_Template();
		if (bab_TemplateCache::get('skins/'.$GLOBALS['babSkin'].'/templates/'. $file, $section)) {
			return $tpl->printTemplate($class, 'skins/'.$GLOBALS['babSkin'].'/templates/'. $file, $section);
		}
		if (bab_TemplateCache::get($babSkinPath.'templates/'.$file, $section)) {
			return $tpl->printTemplate($class, $babSkinPath.'templates/'.$file, $section);
		}
		if (bab_TemplateCache::get($babInstallPath.'skins/ovidentia/templates/'.$file, $section)) {
			return $tpl->printTemplate($class, $babInstallPath.'skins/ovidentia/templates/'.$file, $section);
		}
	}

	$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
	if( file_exists( $filepath ) )
		{
		if( empty($section))
			{
				return $tpl->printTemplate($class,$filepath, '');
			}

		$arr = $tpl->getTemplates($filepath);
		$tplfound = in_array($section, $arr);
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
			$tplfound = in_array($section, $arr);
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
			$tplfound = in_array($section, $arr);
			}

		}

	if( $tplfound ) {
//		$start = microtime(true);
		$t = $tpl->printTemplate($class,$filepath, $section);
//		bab_debug($filepath . ':' . $section . '=' . (int)((microtime(true) - $start) * 1000000));
		return $t;
//		return $tpl->printTemplate($class,$filepath, $section);
	} else {
		return '';
	}
}
function bab_getActiveSessions()
{
	global $babDB;
	$output = array();
	$res = $babDB->db_query("SELECT l.id_user,
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

	while($arr = $babDB->db_fetch_array($res))
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

/**
 * Get mime type by filename extention
 */
function bab_getFileMimeType($file)
{
	global $babDB;
	$mime = "application/octet-stream";
	if ($ext = strrchr($file,"."))
		{
		$ext = substr($ext,1);
		$res = $babDB->db_query("select * from ".BAB_MIME_TYPES_TBL." where ext='".$babDB->db_escape_string($ext)."'");
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			$mime = $arr['mimetype'];
			}
		}
	return $mime;
}

/* API Directories */

/**
 * @deprecated
 * @see bab_getDirEntry
 */
function bab_getUserDirFields($id = false)
	{
	trigger_error('This function is deprecated, please use bab_getDirEntry()');
	
	global $babDB;
	if (false == $id) $id = &$GLOBALS['BAB_SESS_USERID'];
	$query = "select * from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0) {
		return $babDB->db_fetch_assoc($res);
		}
	else
		return array();
	}


/** 
 * Get a directory entry or a list of entries
 *
 * BAB_DIR_ENTRY_ID_USER		: $id is a user id
 * BAB_DIR_ENTRY_ID				: $id is a directory entry
 * BAB_DIR_ENTRY_ID_DIRECTORY	: liste des champs de l'annuaire
 * BAB_DIR_ENTRY_ID_GROUP		: liste des champs de l'annuaire de groupe
 *
 * @param	false|int	$id
 * @param	int			$type
 * @param	NULL|int	$id_directory
 * @return array
 */

function bab_getDirEntry($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = NULL ) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getDirEntry($id, $type, $id_directory, true);
	}

/**
 * List of viewables directories for the user
 */ 
function bab_getUserDirectories() {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getUserDirectories();
	}

/**
 * return a link to the popup of the directory entry
 * @param	int		[$id]				id_user or id_entry
 * @param	int		[$type]				the type of the $id parameter BAB_DIR_ENTRY_ID_USER | BAB_DIR_ENTRY_ID
 * @param	int		[$id_directory]		if $id is a directory entry
 * @return 	string
 */
function bab_getUserDirEntryLink($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = false) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getUserDirEntryLink($id, $type, $id_directory);
	}


/* API Groups */

/**
 * Get group name
 * @param	int			$id
 * @param	boolean		[$fpn]	full path name
 *
 * @return string
 */
function bab_getGroupName($id, $fpn=true)
	{
	
	$id = (int) $id;
	
	global $babBody;
	if($fpn)
		{
		return $babBody->getGroupPathName($id);
		}
	else
		{
		
		if (BAB_ALLUSERS_GROUP === $id || BAB_REGISTERED_GROUP === $id || BAB_UNREGISTERED_GROUP === $id || BAB_ADMINISTRATOR_GROUP === $id) {
			return bab_translate($babBody->ovgroups[$id]['name']);
		}
		
		
		return $babBody->ovgroups[$id]['name'];
		}
	}

function bab_getGroups($parent=BAB_REGISTERED_GROUP, $all=true)
	{
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

	$tree = new bab_grptree();
	$groups = $tree->getGroups($parent, '%2$s > ', $all);
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


/**
 * Register a user
 * @param	boolean	$bgroup
 * @return 	int|false
 */
function bab_registerUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $confirmed, &$error, $bgroup = true)
{
	require_once($GLOBALS['babInstallPath']."utilit/usermodifyincl.php");
	return bab_userModify::addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $confirmed, $error, $bgroup);
}

function bab_attachUserToGroup($iduser, $idgroup)
{
	bab_addUserToGroup($iduser, $idgroup);
}

function bab_detachUserFromGroup($iduser, $idgroup)
{
	bab_removeUserFromGroup($iduser, $idgroup);
}


/**
 * Get user infos from directory and additionnal parameters specific to registered users
 * return all infos necessary to use bab_updateUserById()
 * warning, password is not returned, $info['password_md5'] is returned instead
 *
 * 'changepwd', 'jpegphoto' are not modifiable
 *
 * @param	int		$id_user
 * @return 	false|array
 */
function bab_getUserInfos($id_user) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	$directory = getDirEntry($id_user, BAB_DIR_ENTRY_ID_USER, NULL, false);
	
	if (!$directory) {
		return false;
	}
	
	global $babDB;
	$res = $babDB->db_query('
	SELECT 
		disabled, 
		password password_md5, 
		changepwd,
		is_confirmed  
		
	FROM '.BAB_USERS_TBL.' WHERE id='.$babDB->quote($id_user));
	$infos = $babDB->db_fetch_assoc($res);
	
	foreach($directory as $field => $arr) {
		$infos[$field] = $arr['value'];
	}
	
	return $infos;
}


/**
 * Update a user
 * @see bab_getUserInfos()
 * 'changepwd', 'jpegphoto' are not modifiable
 *
 * @param	int		$id
 * @param	array	$info		: Array returned by bab_getUserInfos()
 * @param	string	&$error
 * @return 	boolean
 */
function bab_updateUserById($id, $info, &$error)
{
	require_once($GLOBALS['babInstallPath']."utilit/usermodifyincl.php");
	return bab_userModify::updateUserById($id, $info, $error);
}

/**
 * Because of a typing error, we must keep compatibility
 * @deprecated
 */
function bab_uppdateUserById($id, $info, &$error)
{
	return bab_updateUserById($id, $info, $error);
}


/**
 * Update a user by nickname
 */
function bab_updateUserByNickname($nickname, $info, &$error)
{
	$id_user = bab_getUserIdByNickname($nickname);
	if (0 === $id_user) {
		$error = bab_translate("Unknown user");
		return false;
	}
	return bab_updateUserById($id_user, $info, $error);
}



/**#@+
 * Severity levels for bab_debug
 */
define('DBG_TRACE',		 1);
define('DBG_DEBUG',		 2);
define('DBG_INFO',		 4);
define('DBG_WARNING',	 8);
define('DBG_ERROR',		16);
define('DBG_FATAL',		32);
/**#@-*/


/**
 * Log information.
 * 
 * @param mixed	$data		The data to log. If not a string $data is transformed through print_r.
 * @param int	$severity	The severity of the logged information.
 */
function bab_debug($data, $severity = DBG_TRACE)
{

	if (isset($_COOKIE['bab_debug']) && ((int)$_COOKIE['bab_debug'] & $severity)) {
		if (!is_string($data)) {
			ob_start();
			print_r($data);
			$data = ob_get_contents();
			ob_end_clean();
		}
		if (isset($GLOBALS['bab_debug_messages'])) {
			$GLOBALS['bab_debug_messages'][] = $data;
		} else {
			$GLOBALS['bab_debug_messages'] = array($data);
		}
	}

	if (file_exists('bab_debug.txt') && is_writable('bab_debug.txt')) {
		if (!is_string($data)) {
			$data = print_r($data, true);
		}
		$h = fopen('bab_debug.txt', 'a');
		fwrite($h, date('d/m/Y H:i:s')."\n\n".$data."\n\n\n------------------------\n");
		fclose($h);
	}
}

/**
 * Returns the html for the debug console, useful for popups
 * 
 * @return string	
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
 * <li>BAB_HTML_JS			: \ and ' and " are encoded for javascript strings, not in BAB_HTML_ALL</li>
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
 * $instance->fetchChildDir()
 * $instance->fetchChildKey()
 *
 * @see bab_registry
 * 
 * @return bab_Registry
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
 * @since 6.0.6
 * @param string $name
 * @param mixed	$default
 * @return mixed
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
 * Post param
 * @since 6.0.6
 * @param string $name
 * @param mixed	$default
 * @return mixed
 */
function bab_pp($name, $default = '') {
	if (isset($_POST[$name])) {
		return $_POST[$name];
	}
	return $default;
}

/**
 * Get param
 * @since 6.0.6
 * @param string $name
 * @param mixed	$default
 * @return mixed
 */
function bab_gp($name, $default = '') {
	if (isset($_GET[$name])) {
		return $_GET[$name];
	}
	return $default;
}

/**
 * Return the current file content disposition ( attchement, inline, undefined )
 * @since 6.0.6
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


/**
 * Convert ovml to html
 * @param	string	$file
 * @param	array	$args
 * @return	string	html
 */
function bab_printOvmlTemplate($file, $args=array())
{
	global $babInstallPath, $babSkinPath, $babOvmlPath;

	if ((false !== strstr($file, '..')) || strtolower(substr($file, 0, 4)) == 'http')
	{
		return '<!-- ERROR filename: '.bab_toHtml($file).' -->';
	}

	$filepath = $babOvmlPath.$file;
	if (!file_exists($filepath))
	{
		$filepath = $babSkinPath.'ovml/'.$file;
		if (!file_exists($filepath))
		{
			$filepath = $babInstallPath.'skins/ovidentia/ovml/'.$file;
		}
	}

	if (!file_exists($filepath))
	{
		return '<!-- ERROR filename: '.bab_toHtml($filepath).' -->';
	}

	$GLOBALS['babWebStat']->addOvmlFile($filepath);
	include_once $babInstallPath.'utilit/omlincl.php';
	$tpl = new babOvTemplate($args);
	return $tpl->printout(implode('', file($filepath)));
}


/**
 * Abbreviate text with 2 types
 * BAB_ABBR_FULL_WORDS 	: the text is trucated if too long without cuting the words
 * BAB_ABBR_INITIAL 	: First letter of each word uppercase with dot
 * 
 * Additional dots are not included in the $max_length parameter
 *
 * @since	6.1.0
 *
 * @param	string	$text
 * @param	int		$type
 * @param	int		$max_length
 * 
 * @return 	string
 */
function bab_abbr($text, $type, $max_length) {
	$len = strlen($text);
	if ($len < $max_length) {
		return $text;
	}
	
	$mots = preg_split('/[\s,]+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);

	if (BAB_ABBR_FULL_WORDS === $type) {
		for ($i = count($mots)-1; $i >= 0; $i--) {
			if ($mots[$i][1] < $max_length) {
				return substr($text,0,$mots[$i][1]).'...';
			}
		}
	}
	
	if (BAB_ABBR_INITIAL === $type) {
		$n = ceil($max_length/count($mots));
		if ($max_length < $n) {
			return bab_abbr($text, BAB_ABBR_FULL_WORDS, $max_length);
		} else {
			array_walk($mots, create_function('&$v,$k','$v = strtoupper(substr($v[0],0,1)).".";'));
			return implode('',$mots);
		}
	}
}


/**
 * Define and get the locale
 * @see		setLocale 
 * @since 	6.1.1
 * @return 	false|string
 */
function bab_locale() {
	
	static $locale = NULL;

	if (NULL !== $locale) {
		return $locale;
		
	} else {
		global $babLanguage;
		
		
		if (function_exists('textdomain')) {
			// clear gettext cache for mo files modifications
			textdomain(textdomain(NULL));
		}
		
		
		switch(strtolower($babLanguage)) {
			case 'fr':
				$arrLoc = array('fr_FR', 'fr');
				break;
			case 'en':
				$arrLoc = array('en_GB', 'en_US', 'en');
				break;
			default:
				$arrLoc = array(strtolower($babLanguage).'_'.strtoupper($babLanguage), strtolower($babLanguage));
				break;
		}
		
		foreach($arrLoc as $languageCode) {
			
			/*
			 * Some systems only require LANG, others (like Mandrake) seem to require
			 * LANGUAGE also.
			 */
			putenv("LANG=${languageCode}");
			putenv("LANGUAGE=${languageCode}");
			
			if ($locale = setLocale(LC_ALL, $languageCode)) {
				return $locale;
			}

			/*
			 * Try appending some character set names; some systems (like FreeBSD) need this.
			 * Some require a format with hyphen (e.g. gentoo) and others without (e.g. FreeBSD).
			 */
			if (false === $locale) {
				foreach (array('utf8', 'UTF-8', 'UTF8', 
						   'ISO8859-1', 'ISO8859-2', 'ISO8859-5', 'ISO8859-7', 'ISO8859-9',
						   'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-5', 'ISO-8859-7', 'ISO-8859-9',
						   'EUC', 'Big5') as $charset) {
					if (($locale = setlocale(LC_ALL, $languageCode . '.' . $charset)) !== false) {
						return $locale;
					}
				}
			}
		}
		
		if (false === $locale) {
			bab_debug("No locale found for : $languageCode");
			return false;
		}
		
		return $locale;
	}
}




/**
 * Returns a singleton of the specified class.
 *
 * @param string $classname
 * @return object
 */
function bab_getInstance($classname) {
	static $instances = NULL;
	if (is_null($instances)) {
		$instances = array();
	}
	if (!array_key_exists($classname, $instances)) {
		$instances[$classname] = new $classname();
	}
	
	return $instances[$classname];
}





/**
 * Functionality interface
 * Functionalities are inherited from this object, to instanciate a functionality use the static method
 * @see bab_functionality::get($path)
 * @since 6.6.90
 */
class bab_functionality {

	/**
	 * Constructor
	 *
	 * @return bab_functionality
	 */
	function bab_functionality()
	{
	}

	/**
	 * @access public
	 * @static
	 * @param	string	$path
	 * @return boolean
	 */
	function includefile($path) {
		$include_result = /*@*/include dirname($_SERVER['SCRIPT_FILENAME']).'/'.BAB_FUNCTIONALITY_ROOT_DIRNAME.'/'.$path.'/'.BAB_FUNCTIONALITY_LINK_FILENAME;
		
		if (false === $include_result) {
			trigger_error(sprintf('The functionality %s is not available', $path));
		}
		
		return $include_result;
	}


	/**
	 * Returns the specified functionality object.
	 * 
	 * If $singleton is set to true, the functionality object will be instanciated as
	 * a singleton, i.e. there will be at most one instance of the functionality
	 * at a given time.
	 * 
	 * @access public
	 * @static
	 * @param	string	$path		The functionality path.
	 * @param	bool	$singleton	Whether the functionality should be instanciated as singleton (default true).
	 * @return	object				The functionality object or false on error.
	 */
	function get($path, $singleton = true) {
		$classname = bab_functionality::includefile($path);
		if (!$classname) {
			return false;
		}
		if ($singleton) {
			return bab_getInstance($classname);
		}
		return new $classname();
	}
	
	/**
	 * get functionalities compatible with the interface
	 * @access public
	 * @static
	 * @param	string	$path
	 * @return array
	 */
	function getFunctionalities($path) {
		require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
		$obj = new bab_functionalities();
		return $obj->getChildren($path);
	}
	
	/**
	 * Default method to create in inherited functionalities
	 * @access protected
	 * @return string
	 */
	function getDescription() {
		return '';
	}
	
	
	/**
	 * Get path to functionality at this node which is the current path or a reference to a childnode
	 * @return string
	 */
	function getPath() {
		require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
		return bab_Functionalities::getPath(get_class($this));
	}
}


/**
 * Get an object with informations for one addon
 * @since 6.6.93
 * @param	string	$addonname
 * @return bab_addonInfos|false
 */
function bab_getAddonInfosInstance($addonname) {

	require_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	static $instances = array();
	
	if (false === array_key_exists($addonname, $instances)) {
		$obj = new bab_addonInfos();
		if (false === $obj->setAddonName($addonname)) {
			$instances[$addonname] = false;
		} else {
			$instances[$addonname] = $obj;
		}
	}
	return $instances[$addonname];
}


/**
 * Ensures that the user is logged in.
 * If the user is not logged the "PortalAutentication" functionality
 * is used to let the user log in with its credential.
 * 
 * The parameter $sAuthType can be used to force the authentication method,
 * it must be the name (path) of the functionality to use without 'PortalAuthentication/' 
 * 
 * @param	string		$sAuthType		Optional authentication type.
 * @since 6.7.0
 *
 * @return boolean
 */
function bab_requireCredential($sAuthType = '') {
	require_once $GLOBALS['babInstallPath'].'utilit/loginIncl.php';
	return bab_doRequireCredential($sAuthType);
}