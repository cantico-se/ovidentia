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

function bab_array_search($str, $vars)
{
	foreach ($vars as $key => $val)
	{
		if ($val == $str)
		{
			return $key;
		}
	}
	return false;
}

function bab_toAmPm($str)
{
	$arr = explode(":", $str);
	$arr[0] = intval($arr[0]);
	$arr[1] = intval($arr[1]);

	if( $arr[0] < 12 )
	{
		if( $arr[0] == 0)
			$arr[0] = 12;
		return sprintf("%02d:%02d AM", $arr[0], $arr[1]);
	}
	else
	{
		if( $arr[0] > 12)
			$arr[0] -= 12;
		return sprintf("%02d:%02d PM", $arr[0], $arr[1]);
	}
		
}

function bab_isUserTopicManager($topics)
	{
	global $babBody, $BAB_SESS_USERID;
	if( count($babBody->babmanagertopics) > 0 && in_array($topics, $babBody->babmanagertopics))
		{
		return true;
		}
	else
		{
		return false;
		}
	}

function bab_isUserArticleApprover($topics)
	{
	global $BAB_SESS_USERID;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$db = $GLOBALS['babDB'];
	$query = "select idsaart from ".BAB_TOPICS_TBL." where id='".$topics."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_isCurrentUserApproverFlow($arr['idsaart']);
		}
	else
		{
		return false;
		}
	}

function bab_isUserCommentApprover($topics)
	{
	global $BAB_SESS_USERID;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$db = $GLOBALS['babDB'];
	$query = "select idsacom from ".BAB_TOPICS_TBL." where id='".$topics."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_isCurrentUserApproverFlow($arr['idsacom']);
		}
	else
		{
		return false;
		}
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

function bab_isUserAdministrator()
{
	global $babBody;
	return $babBody->isSuperAdmin;
}


function bab_getWaitingArticles($topics)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	if( !isset($babBody->waitingarticles))
		{
		$babBody->waitingarticles = array();
		$res = $babDB->db_query("SELECT at.id , at.id_topic FROM ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_FAR_INSTANCES_TBL." fi on at.idfai = fi.idschi WHERE fi.iduser =  '".$BAB_SESS_USERID."' AND fi.result =  '' AND fi.notified ='Y'");
		while( $arr = $babDB->db_fetch_array($res))
			{
			$babBody->waitingarticles[$arr['id_topic']] = $arr['id'];
			}
		}
	if( isset($babBody->waitingarticles[$topics]))
		{
		return $babBody->waitingarticles[$topics];
		}
	else
		{
		return array();
		}
	}

function bab_getWaitingComments($topics)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	if( !isset($babBody->waitingcomments))
		{
		$babBody->waitingcomments = array();
		$res = $babDB->db_query("SELECT ct.id , ct.id_topic FROM ".BAB_COMMENTS_TBL." ct LEFT JOIN ".BAB_FAR_INSTANCES_TBL." fi on ct.idfai = fi.idschi WHERE fi.iduser =  '".$BAB_SESS_USERID."' AND fi.result =  '' AND fi.notified ='Y'");
		while( $arr = $babDB->db_fetch_array($res))
			{
			$babBody->waitingcomments[$arr['id_topic']] = $arr['id'];
			}
		}
	if( isset($babBody->waitingcomments[$topics]))
		{
		return $babBody->waitingcomments[$topics];
		}
	else
		{
		return array();
		}
	}

function bab_isCurrentUserApproverFlow($idsa)
	{
	global $babBody, $BAB_SESS_USERID;
	if( isset($babBody->saarray[$idsa]))
		{
		if(  $babBody->saarray[$idsa] ==  true )
			{
			return true;
			}
		else
			{
			return false;
			}
		}
	else
		{
		$babBody->saarray[$idsa] = isUserApproverFlow($idsa, $BAB_SESS_USERID);
		return $babBody->saarray[$idsa];
		}
	}

function bab_isAccessValid($table, $idobject)
{
	global $babBody, $BAB_SESS_USERID, $BAB_SESS_LOGGED;
	$ok = false;
	if( !isset($babBody->ovgroups[1][$table]))
		{
		$babBody->ovgroups[1][$table] = array();
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select * from ".$table."");
		while( $row = $db->db_fetch_array($res))
			{
			switch($row['id_group'])
				{
				case "0": // everybody
					$babBody->ovgroups[1][$table][] = $row['id_object'];
					$babBody->ovgroups[2][$table][] = $row['id_object'];
					break;
				case "1": // users
					$babBody->ovgroups[1][$table][] = $row['id_object'];
					break;
				case "2": // guests
					$babBody->ovgroups[2][$table][] = $row['id_object'];
					break;
				default:  //groups
					$babBody->ovgroups[$row['id_group']][$table][] = $row['id_object'];
					break;
				}

			}
		}

	if( !$BAB_SESS_LOGGED )
		{
		if( isset($babBody->ovgroups[2][$table]) && in_array($idobject,$babBody->ovgroups[2][$table]))
			{
			$ok = true;
			}
		}
	else
	{
		if( isset($babBody->ovgroups[1][$table]) && in_array($idobject,$babBody->ovgroups[1][$table]))
			{
			$ok = true;
			}
		else
		{
			for( $i = 0; $i < count($babBody->usergroups); $i++)
			{
				if( isset($babBody->ovgroups[$babBody->usergroups[$i]][$table]) && in_array($idobject, $babBody->ovgroups[$babBody->usergroups[$i]][$table]))
				{
					$ok = true;
					break;
				}

			}
		}
	}
	return $ok;
}



/* for all users */
function bab_isUserLogged($iduser = "")
{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	if( !isset($iduser) || empty($iduser) || $iduser == $GLOBALS['BAB_SESS_USERID'])
		{
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
	else
	{
		if( $iduser == 0)
			return false;
		$db = $GLOBALS['babDB'];
		$res=$db->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='".$iduser."'");
		if( $res && $db->db_num_rows($res) > 0)
			return true;		
		return false;
	}
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

function bab_getUserName($id)
	{
	static $arrnames = array();

	if( isset($arrnames[$id]) )
		return $arrnames[$id];

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

function bab_getUserDirFields($id = "")
	{
	if ($id == "") $id = $GLOBALS['BAB_SESS_USERID'];
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$id."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		return $db->db_fetch_array($res);
	else
		return "";
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

/* 0 no access, 1 user, 2 user/manager, 3 manager*/ 
function bab_mailAccessLevel()
	{
	global $babBody;

	$user = 0;
	$manager = 0;
	reset($babBody->ovgroups);
	while( $arr=each($babBody->ovgroups) ) 
	{ 
		if( $arr[1]['mail'] == 'Y')
		{
			if( $arr[1]['member'] == 'Y' || $arr[1]['id'] == 1)
				$user = 1;

			if( $arr[1]['manager'] == $GLOBALS['BAB_SESS_USERID'])
				$manager = 1;
		}
	}

	if( $user && $manager )
		return 2;
	if( $user )
		return 1;
	if( $manager )
		return 3;
	}

function bab_notesAccess()
	{
	global $babBody;
	if( $babBody->ovgroups[1]['mail'] == 'Y' )
		return true;

	for( $i = 0; $i < count($babBody->usergroups); $i++)
		{
		if( $babBody->ovgroups[$babBody->usergroups[$i]]['mail'] == 'Y')
			{
			return true;
			}
		}
	return false;
	}


function bab_contactsAccess()
	{
	global $babBody;
	if( $babBody->ovgroups[1]['contacts'] == 'Y' )
		return true;

	for( $i = 0; $i < count($babBody->usergroups); $i++)
		{
		if( $babBody->ovgroups[$babBody->usergroups[$i]]['contacts'] == 'Y')
			{
			return true;
			}
		}
	return false;
	}

function bab_vacationsAccess()
	{
	$db = $GLOBALS['babDB'];

	$array = array();
	$res = $db->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$array['user'] = true;
		}

	$res = $db->db_query("select id from ".BAB_VAC_MANAGERS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$array['manager'] = true;
		}

	$res = $db->db_query("select ".BAB_VAC_ENTRIES_TBL.".* from ".BAB_VAC_ENTRIES_TBL." join ".BAB_FAR_INSTANCES_TBL." where status='' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_VAC_ENTRIES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'");
	if($res && $db->db_num_rows($res) > 0 )
		{
		$array['approver'] = true;
		}

	return $array;
	}

function bab_articleAccessByRestriction($restriction, $iduser ='')
	{
	$db = $GLOBALS['babDB'];

	if( empty($restriction))
		return true;

	if( strchr($restriction, ","))
		$sep = ',';
	else
		$sep = '&';

	$arr = explode($sep, $restriction);
	if( empty($iduser))
		$iduser = $GLOBALS['BAB_SESS_USERID'];

	$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$iduser."' and id_group IN (".implode(',', $arr).")";
	$res = $db->db_query($req);
	$num = $db->db_num_rows($res);
	if( $res && $num > 0)
		{
		if( $sep == ',' )
			return true;

		if( $num == count($arr))
			return true;
		}
	return false;
	}

function bab_articleAccessById($id, $iduser ='')
	{
	$db = $GLOBALS['babDB'];

	list($restriction) = $db->db_fetch_row($db->db_query("select restriction from ".BAB_ARTICLES_TBL." where id='".id."'"));
	if( empty($restriction))
		return true;
	return bab_articleAccessByRestriction($restriction, $iduser);
	}
	
function bab_getCalendarId($iduser, $type)
{
	if( empty($iduser))
		return 0;
	$db = $GLOBALS['babDB'];
	if( $type == 1)
	{
	$res = $db->db_query("select id from ".BAB_GROUPS_TBL." where id='1' and pcalendar='Y'");

	if( !$res || $db->db_num_rows($res) == 0 )
		{
		$res = $db->db_query("select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$iduser."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_GROUPS_TBL.".pcalendar ='Y'");

		if( !$res || $db->db_num_rows($res) == 0 )
			return 0;
		}
	}

	$query = "select id from ".BAB_CALENDAR_TBL." where owner='$iduser' and actif='Y' and type='".$type."'";
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

function bab_calendarAccess()
	{
	global $babBody;
	$db = $GLOBALS['babDB'];

	if( $GLOBALS['BAB_SESS_USERID'] != "" )
		{
		$idcal = bab_getCalendarId($GLOBALS['BAB_SESS_USERID'], 1);
		if( $idcal != 0 )
			return $idcal;
	
		$idcal = bab_getCalendarId(1, 2);
		if( $idcal != 0 )
			return $idcal;

		if( count($babBody->usergroups) > 0 )
			{
			$res = $db->db_query("select id from ".BAB_CALENDAR_TBL." where owner IN (".implode(',', $babBody->usergroups).") and type='2' and actif='Y' limit 0, 1");
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				return $arr['id'];
				}
			}

		$res = $db->db_query("select ".BAB_CALENDAR_TBL.".id from ".BAB_CALENDAR_TBL." join ".BAB_RESOURCESCAL_TBL." where ".BAB_RESOURCESCAL_TBL.".id_group='1' and ".BAB_CALENDAR_TBL.".owner=".BAB_RESOURCESCAL_TBL.".id and ".BAB_CALENDAR_TBL.".type='3' and ".BAB_CALENDAR_TBL.".actif='Y' limit 0, 1");

		if( $res && $db->db_num_rows($res) > 0 )
			{
			$arr = $db->db_fetch_array($res);
			return $arr['id'];
			}

		if( count($babBody->usergroups) > 0 )
			{
			$res = $db->db_query("select id from ".BAB_RESOURCESCAL_TBL." where id_group IN (".implode(',', $babBody->usergroups).")");
			while( $arr = $db->db_fetch_array($res) )
				{
				$idcal = bab_getCalendarId($arr['id'], 3);
				if( $idcal != 0 )
					return $idcal;
				}
			}

		$res = $db->db_query("select * from ".BAB_CALACCESS_USERS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		while($row = $db->db_fetch_array($res))
			{
				if( bab_isCalendarAccessValid($row['id_cal']) )
				{
					return $row['id_cal'];
				}
			}
		}

	return 0;
	}


function bab_fileManagerAccessLevel()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	if( isset($babBody->aclfm))
		return;

	$babBody->aclfm = array();
	$babBody->ustorage = false;

	if( $babBody->ovgroups[1]['ustorage'] == 'Y')
		{
		$babBody->ustorage = true;
		}
	else
		{
		for( $i = 0; $i < count($babBody->usergroups); $i++)
			{
			if( $babBody->ovgroups[$babBody->usergroups[$i]]['ustorage'] == 'Y')
				{
				$babBody->ustorage = true;
				break;
				}
			}
		}
	
	$res = $babDB->db_query("select id, manager, idsa, folder from ".BAB_FM_FOLDERS_TBL." where active='Y' ORDER BY folder");
	while($row = $babDB->db_fetch_array($res))
		{
		$uplo = bab_isAccessValid(BAB_FMUPLOAD_GROUPS_TBL, $row['id']);
		$down = bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $row['id']);
		$upda = bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $row['id']);

		if( $down || $uplo || $upda || $row['manager'] == $BAB_SESS_USERID)
			{
			$babBody->aclfm['id'][] = $row['id'];
			$babBody->aclfm['folder'][] = $row['folder'];
			$babBody->aclfm['down'][] = $down;
			$babBody->aclfm['uplo'][] = $uplo;
			$babBody->aclfm['upda'][] = $upda;
			$babBody->aclfm['idsa'][] = $row['idsa'];
			if( $row['manager'] != 0 && $row['manager'] == $BAB_SESS_USERID)
				$babBody->aclfm['ma'][] = 1;
			else
				$babBody->aclfm['ma'][] = 0;
			}
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
		if( $row[1]['id'] == 1 && $row[1]['id'] == 2)
			{
			$arr['id'][] = $row[1]['id'];
			$arr['name'][] = $row[1]['name'];
			}
		}
	return $arr;
	}


function bab_getGroupEmails($id)
{
	$db = $GLOBALS['babDB'];
	$query = "select distinct email from ".BAB_USERS_TBL." usr , ".BAB_USERS_GROUPS_TBL." grp where grp.id_group in ($id) and grp.id_object=usr.id";
	$res = $db->db_query($query);
	$emails = "";
	if( $res && $db->db_num_rows($res) > 0)
		{
		while ($arr = $db->db_fetch_array($res)){
		if ($arr['email'])
			{
			$emails .= $arr['email'].",";
			}
		}
		$emails = substr("$emails", 0, -1);
		return $emails;
		}
	else
		{
		return "";
		}
}

function bab_replace( $txt )
{
	global $babBody;
	$db = $GLOBALS['babDB'];
	$artarray = array("ARTICLEPOPUP", "ARTICLE");
	for( $i = 0; $i < count($artarray); $i++)
	{
	$reg = "/\\\$".$artarray[$i]."\((.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			unset($idtopic);
			$tab = preg_split("/(\"[\s]*,)|(,[\s]*\")|(\"[\s]*,[\s]*\")+/", $m[1][$k]);
			if( sizeof($tab) > 1)
				{
				$topic = trim($tab[0]);
				if( $topic[0] == '"' )
					$topic = substr($topic, 1);
				if( $topic[strlen($topic)-1] == '"' )
					$topic = substr($topic, 0, -1);

				$article = trim($tab[1]);
				if( $article[0] == '"' )
					$article = substr($article, 1);
				if( $article[strlen($article)-1] == '"' )
					$article = substr($article, 0, -1);
				$req = "select * from ".BAB_TOPICS_TBL." where category='".addslashes($topic)."'";
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$idtopic = $arr['id'];
					}
				}
			else
				{
				$tab = preg_split("/[,]+/", $m[1][$k]);
				if( sizeof( $tab ) > 1 )
					{
					$article = trim($tab[1]);
					$req = "select * from ".BAB_TOPICS_TBL." where category='".addslashes(trim($tab[0]))."'";
					$res = $db->db_query($req);
					if( $res && $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$idtopic = $arr['id'];
						}
					}
				else
					{
					$article = trim($m[1][$k]);
					}
				}

			if( isset($idtopic))
				$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$idtopic."' and title= '".addslashes($article)."'";
			else
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where title like '%".addslashes($article)."%'";
				}

			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(in_array($arr['id_topic'], $babBody->topview) && bab_articleAccessByRestriction($arr['restriction']))
					{
					if( $i == 0 )
						$txt = preg_replace("/\\\$".$artarray[$i]."\(".preg_quote($m[1][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&article=".$arr['id']."', 'Article', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$arr['title']."</a>", $txt);
					else
						$txt = preg_replace("/\\\$".$artarray[$i]."\(".preg_quote($m[1][$k],"/")."\)/", "<a href=\"".$GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic']."\">".$arr['title']."</a>", $txt);
					}
				else
					$txt = preg_replace("/\\\$".$artarray[$i]."\(".preg_quote($m[1][$k],"/")."\)/", $arr['title'], $txt);
				}
			}
		}
	}

	$reg = "/\\\$ARTICLEID\((.*?),(.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$repl = false;
			$req = "select * from ".BAB_ARTICLES_TBL." where id=".$m[1][$k];
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']))
					{
					if( $arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']))
						{
						$repl = true;
						if ($m[2][$k] == '0')
							{
							$titre = $arr['title'];
							}
						else
							{
							$titre = $m[2][$k];
							}
						if ($m[3][$k] == '0')
							{
							$txt = preg_replace("/\\\$ARTICLEID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/").",".preg_quote($m[3][$k],"/")."\)/", "<a href=\"".$GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic']."\">".$titre."</a>", $txt);
							}
						else
							{
							$txt = preg_replace("/\\\$ARTICLEID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/").",".preg_quote($m[3][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&article=".$arr['id']."', 'Article', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$titre."</a>", $txt);
							}
						}
					}
				}

			if( $repl == false )
				{
				$txt = preg_replace("/\\\$ARTICLEID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/").",".preg_quote($m[3][$k],"/")."\)/", $m[2][$k] , $txt);
				}
			}
		}

	$reg = "/\\\$CONTACT\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_CONTACTS_TBL." where  owner='".$GLOBALS['BAB_SESS_USERID']."' and firstname like '%".addslashes(trim($m[1][$k]))."%' and lastname like '%".addslashes(trim($m[2][$k]))."%'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$txt = preg_replace("/\\\$CONTACT\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=contact&idx=modify&item=".$arr['id']."&bliste=0', 'Contact', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$m[1][$k]." ".$m[2][$k]."</a>", $txt);
				}
			else
				$txt = preg_replace("/\\\$CONTACT\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", $m[1][$k]." ".$m[2][$k], $txt);
			}
		}

	$reg = "/\\\$CONTACTID\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$title = $m[2][$k];
			$req = "select * from ".BAB_CONTACTS_TBL." where  owner='".$GLOBALS['BAB_SESS_USERID']."' and id= '".trim($m[1][$k])."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if (trim($m[2][$k]) == '')
					$title = bab_composeUserName($arr['firstname'],$arr['lastname']);
				$txt = preg_replace("/\\\$CONTACTID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=contact&idx=modify&item=".$arr['id']."&bliste=0', 'Contact', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$title."</a>", $txt);
				}
			else
				$txt = preg_replace("/\\\$CONTACTID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", $m[2][$k], $txt);
			}
		}

	$reg = "/\\\$DIRECTORYID\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$title = $m[2][$k];
			$req = "select id,sn,givenname,id_directory from ".BAB_DBDIR_ENTRIES_TBL." where id= '".trim($m[1][$k])."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if ( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id_directory']) || $arr['id_directory'] == 0 )
					{
					if (trim($m[2][$k]) == '')
						$title = bab_composeUserName($arr['sn'],$arr['givenname']);
					$txt = preg_replace("/\\\$DIRECTORYID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$arr['id_directory']."&idu=".$arr['id']."', 'Contact', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$title."</a>", $txt);
					}
				else
					$txt = preg_replace("/\\\$DIRECTORYID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", $m[2][$k], $txt);
				}
			else
				$txt = preg_replace("/\\\$DIRECTORYID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", $m[2][$k], $txt);
			}
		}

	$reg = "/\\\$FAQ\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_FAQCAT_TBL." where category='".addslashes(trim($m[1][$k]))."'";
			$res = $db->db_query($req);
			$repl = false;
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
					{
					$req = "select * from ".BAB_FAQQR_TBL." where question='".addslashes(trim($m[2][$k]))."'";
					$res = $db->db_query($req);
					if( $res && $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$txt = preg_replace("/\\\$FAQ\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".$arr['id']."', 'Faq', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$m[2][$k]."</a>", $txt);
						$repl = true;
						}
					}
				}
			if( $repl == false )
				$txt = preg_replace("/\\\$FAQ\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/")."\)/", $m[2][$k], $txt);
			}
		}
	
	$reg = "/\\\$FAQID\((.*?),(.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_FAQQR_TBL." where id='".trim($m[1][$k])."'";
			$res = $db->db_query($req);
			$repl = false;
			$message = trim($m[2][$k]);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['idcat']))
					{
					if (trim($m[2][$k]) == "")
						{$message = $arr['question'];}
					if (trim($m[3][$k]) == 1)
						{
						$txt = preg_replace("/\\\$FAQID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/").",".preg_quote($m[3][$k],"/")."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".trim($m[1][$k])."', 'Faq', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$message."</a>", $txt);
						}
					else
						{
						$txt = preg_replace("/\\\$FAQID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/").",".preg_quote($m[3][$k],"/")."\)/", "<a href=\"".$GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idq=".trim($m[1][$k])."\">".$message."</a>", $txt);
						}
					}
				$repl = true;
				}

			if( $repl == false )
				$txt = preg_replace("/\\\$FAQID\(".preg_quote($m[1][$k],"/").",".preg_quote($m[2][$k],"/").",".preg_quote($m[3][$k],"/")."\)/", $message, $txt);
			}
		}

	$reg = "/\\\$FILE\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_FILES_TBL." where id='".trim($m[1][$k])."' and state='' and confirmed='Y'";
			$res = $db->db_query($req);
			$access = false;
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$access = bab_isAccessFileValid($arr['bgroup'], $arr['id_owner']);
				}

			$urltxt = trim($m[2][$k]);
			if( empty($urltxt) && $access )
				$urltxt = $arr['name'];

			if( $access )
				{
				$txt = preg_replace("/\\\$FILE\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k], "/")."\)/", "<a href=\"".$GLOBALS['babUrlScript']."?tg=fileman&idx=get&inl=1&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name'])."\" target=_blank>".$urltxt."</a>", $txt);
				}
			else
				$txt = preg_replace("/\\\$FILE\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k], "/")."\)/", $urltxt, $txt);
			}
		}

	$reg = "/\\\$VAR\((.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$var = trim($m[1][$k]);
			switch($var)
				{
				case "BAB_SESS_NICKNAME":
				case "BAB_SESS_USER":
				case "BAB_SESS_EMAIL":
					$txt = preg_replace("/\\\$VAR\(".preg_quote($var,"/")."\)/", $GLOBALS[$var], $txt);
					break;
				default:
					break;
				}
			}
		}

	$reg = "/\\\$OVML\((.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$txt = preg_replace("/\\\$OVML\(".preg_quote($m[1][$k], "/")."\)/", bab_printOvmlTemplate($m[1][$k]), $txt);
			}
		}

	return $txt;
}
?>