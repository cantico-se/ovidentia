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

define("BAB_ART_STATUS_DRAFT", 0);
define("BAB_ART_STATUS_WAIT", 1);
define("BAB_ART_STATUS_OK"	, 2);
define("BAB_ART_STATUS_NOK"	, 3);

function bab_printOvml($content, $args)
	{
	include_once $GLOBALS['babInstallPath']."utilit/omlincl.php";
	$tpl = new babOvTemplate($args);
	return $tpl->printout($content);
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
		
				$this->images = bab_translate("Images");
				$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
				$this->files = bab_translate("Files");
				$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
				if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
					{
					$this->msie = 1;
					}
				else
					{
					$this->msie = 0;
					}
				}	
			}
		}
	$temp = new babEditorCls($content, $editname, $formname, $heightpx,$what);
	return bab_printTemplate($temp,"uiutil.html", "babeditortemplate");
	}



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
	if( count($babBody->topman) > 0 && isset($babBody->topman[$topics]))
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

function bab_getWaitingIdSAInstance($iduser)
	{
	static $wIdSAInstance = array();
	if( !isset($wIdSAInstance[$iduser]))
		{
		include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		$wIdSAInstance[$iduser] = getWaitingApprobations($iduser);
		}

	return $wIdSAInstance[$iduser]['idschi'];
	}

function bab_getWaitingIdSA($iduser)
	{
	static $wIdSA = array();
	if( !isset($wIdSA[$iduser]))
		{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
		$wIdSA[$iduser] = getWaitingApprobations($iduser);
	}

	return $wIdSA[$iduser]['idsch'];
	}


function bab_isWaitingApprobations()
	{
		global $babDB;
		static $iwa_called = false;
		static $iwa_result = false;

		if($iwa_called)
		{
			return $iwa_result;
		}
		$iwa_called = true;

		$arr = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arr) > 0 )
		{
			$iwa_result = true;
			return true;
		}

		$result = false;

		$arrf = array();

		$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y'");
		while( $arr = $babDB->db_fetch_array($res))
			{
			if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $arr['id']) )
				{
				$arrf[] = $arr['id'];
				}
			}

		if( count($arrf) > 0 )
			{
			list($posts) = $babDB->db_fetch_row($babDB->db_query("select count(pt.id) from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on pt.id_thread=tt.id left join ".BAB_POSTS_TBL." pt2 on tt.post=pt2.id left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.confirmed='N' and ft.id IN(".implode(',', $arrf).")"));
			if( $posts > 0 )
				{
				$result = true;
				}
			}
		$iwa_result = $result;
		return $result;
	}

function bab_getWaitingArticles($topics)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	if( !isset($babBody->waitingarticles))
		{
		$babBody->waitingarticles = array();
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 )
			{
			$res = $babDB->db_query("SELECT adt.id , adt.idfai, adt.id_topic FROM ".BAB_ART_DRAFTS_TBL." adt WHERE adt.result='".BAB_ART_STATUS_WAIT."'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$babBody->waitingarticles[$arr['id_topic']][] = $arr['id'];
				}
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
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 )
			{
			$res = $babDB->db_query("SELECT ct.id , ct.idfai, ct.id_topic FROM ".BAB_COMMENTS_TBL." ct where ct.confirmed='N'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$babBody->waitingcomments[$arr['id_topic']][] = $arr['id'];
				}
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

function bab_deleteArticleDraft($id)
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteDraft($id);
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

function bab_getDbUserName($id)
	{
	static $arrnames = array();

	if( isset($arrnames[$id]) )
		return $arrnames[$id];

	$db = $GLOBALS['babDB'];
	$query = "select sn, givenname, mn from ".BAB_DBDIR_ENTRIES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$arrnames[$id] = bab_composeUserName($arr['givenname'], $arr['sn']);
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

/* 0 no access, 1 user, 2 user/manager, 3 manager*/ 
function bab_mailAccessLevel()
	{
	global $babBody;

	$user = 0;
	$manager = 0;
	reset($babBody->ovgroups);
	while( $arr=each($babBody->ovgroups) ) 
	{ 
		if( isset($arr[1]['mail']) && $arr[1]['mail'] == 'Y')
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
	if( $babBody->ovgroups[1]['notes'] == 'Y' )
		return true;

	for( $i = 0; $i < count($babBody->usergroups); $i++)
		{
		if( $babBody->ovgroups[$babBody->usergroups[$i]]['notes'] == 'Y')
			{
			return true;
			}
		}
	return false;
	}

function bab_orgchartAccess()
	{
	global $babBody, $babDB;

	$ret = array();
	$res = $babDB->db_query("select id from ".BAB_ORG_CHARTS_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $row['id']) || bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $row['id']))
			{
			$ret[] = $row['id'];
			}
		}
	return $ret;
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
	global $babBody;
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

	$arrchi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	$res = $db->db_query("select * from ".BAB_VAC_ENTRIES_TBL."  where status=''");
	while($arr =  $db->db_fetch_array($res) )
		{
		if( count($arrchi) > 0  && in_array($arr['idfai'], $arrchi))
			{
			$array['approver'] = true;
			break;
			}
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

	list($restriction) = $db->db_fetch_row($db->db_query("select restriction from ".BAB_ARTICLES_TBL." where id='".$id."'"));
	if( empty($restriction))
		return true;
	return bab_articleAccessByRestriction($restriction, $iduser);
	}
	
function bab_getCalendarId($iduser, $type)
{
	global $babBody, $babDB;

	if( empty($iduser))
		return 0;
	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$iduser."' and type='".$type."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
	{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
	}
	return 0;
}

function bab_calendarAccess()
	{
	global $babBody;
	return $babBody->icalendars->calendarAccess();
	}


function bab_fileManagerAccessLevel()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	if( isset($babBody->aclfm))
		return;

	$babBody->aclfm = array();
	$babBody->ustorage = false;

	if( $GLOBALS['BAB_SESS_LOGGED'] && $babBody->ovgroups[1]['ustorage'] == 'Y')
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
		if( $row[1]['id'] != 1 && $row[1]['id'] != 2)
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


function bab_getOrgChartRoleUsers($idroles)
{
	global $babDB;
	$roles =  is_array($idroles)? implode(',', $idroles): $idroles;
	$arr = array();
	$res = $babDB->db_query("select det.sn, det.givenname, det.id_user, ocrut.id_role from ".BAB_DBDIR_ENTRIES_TBL." det left join ".BAB_OC_ROLES_USERS_TBL." ocrut on det.id=ocrut.id_user where ocrut.id_role IN (".$roles.")");
	while( $row = $babDB->db_fetch_array($res))
	{
		$arr['iduser'][] = $row['id_user'];
		$arr['idrole'][] = $row['id_role'];
		$arr['name'][] = bab_composeUserName($row['givenname'], $row['sn']);
	}
	return $arr;
}

function bab_getSuperior($iduser)
{
	global $babDB;

	$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$ocinfo = $babDB->db_fetch_array($res);
	}
	else
	{
		return array();
	}

	/* find user primary role */
	$res = $babDB->db_query("SELECT ocet.id_node, ocet.id as id_entity, ocrut.id_role, ocrt.type  FROM ".BAB_DBDIR_ENTRIES_TBL." det LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON det.id = ocrut.id_user LEFT  JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role LEFT JOIN ".BAB_OC_ENTITIES_TBL." ocet ON ocet.id = ocrt.id_entity WHERE ocrt.id_oc='".$ocinfo['id']."' and det.id_user IN ( ".$iduser."  )  AND det.id_directory = '0' and ocrut.isprimary='Y'");
	while( $arr = $babDB->db_fetch_array($res) )
	{
		$arroles = array();
		if( $arr['type'] != 1) /* not responsable */
		{
			/* find user's responsable */
			$res = $babDB->db_query("SELECT ocrut.*  FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role  WHERE ocrt.id_entity IN (".$arr['id_entity'].")  AND ocrt.type = '1'");

			while( $row = $babDB->db_fetch_array($res) )
			{
				$arroles[]= $row['id_role'];
			}
			if( count($arroles) > 0 )
				{
				return bab_getOrgChartRoleUsers($arroles);
				}
		}

		if( count($arroles) == 0 )
			{
			$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_TREES_TBL." where id='".$arr['id_node']."'"));
			$res = $babDB->db_query("SELECT ocrut.* FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT  JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role LEFT  JOIN ".BAB_OC_ENTITIES_TBL." ocet ON ocrt.id_entity = ocet.id LEFT  JOIN ".BAB_OC_TREES_TBL." oct ON oct.id = ocet.id_node and oct.id_user='1' WHERE ocrt.id_oc='".$ocinfo['id']."' and oct.lf <  '".$rr['lf']."' AND oct.lr >  '".$rr['lr']."' AND ocrut.isprimary='Y' and ocrt.type ='1' ORDER  BY oct.lf desc limit 0,1");
			while( $row = $babDB->db_fetch_array($res) )
			{
				$arroles[]= $row['id_role'];
			}
			if( count($arroles) > 0 )
				{
				return bab_getOrgChartRoleUsers($arroles);
				}
			}		
	}
	return array();
}

function bab_addUserToGroup($iduser, $idgroup, $oc = true)
{
	global $babDB, $babBody;

	if( $oc )
	{
		list($identity) = $babDB->db_fetch_row($babDB->db_query("select id_ocentity from ".BAB_GROUPS_TBL." where id='".$idgroup."'"));
		if( $identity )
		{
			list($idrole) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_OC_ROLES_TBL." where id_entity='".$identity."' and type='3'"));
			list($idduser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$iduser."'"));
			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_OC_ROLES_USERS_TBL." where id_role='".$idrole."' and id_user='".$idduser."'"));
			if( !$total )
				{
				$req = "insert into ".BAB_OC_ROLES_USERS_TBL." (id_role, id_user, isprimary) values ('".$idrole."','".$idduser."','Y')";
				$babDB->db_query($req);
				}
		}
	}

	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_USERS_GROUPS_TBL." where id_group='".$idgroup."' and id_object='".$iduser."'"));
	if( !$total )
		{
		$res = $babDB->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_group, id_object) VALUES ('" .$idgroup. "', '" . $iduser. "')");
		$babBody->usergroups[] = $idgroup;
		}
}

function bab_removeUserFromGroup($iduser, $idgroup)
{
	global $babDB, $babBody;

	$babDB->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_group='".$idgroup."' and id_object='".$iduser."'");
	$idx = bab_array_search($idgroup, $babBody->usergroups);
	if( $idx )
		{
		array_splice($babBody->usergroups, $idx, 1);
		}

	list($identity) = $babDB->db_fetch_row($babDB->db_query("select id_ocentity from ".BAB_GROUPS_TBL." where id='".$idgroup."'"));
	if( $identity )
		{
		$res = $babDB->db_query("select ocrut.id FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrut.id_role = ocrt.id LEFT JOIN ".BAB_DBDIR_ENTRIES_TBL." det ON ocrut.id_user = det.id WHERE ocrt.id_entity =  '".$identity."' AND det.id_directory =  '0' AND det.id_user =  '".$iduser."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("delete from ".BAB_OC_ROLES_USERS_TBL." where id='".$row['id']."'");
			}
		}
}


function bab_replace_var(&$txt,$var,$new)
	{
	$txt = preg_replace("/".preg_quote($var,"/")."/", $new, $txt);
	}
	
function bab_replace_make_link($url,$text,$popup = 0,$url_popup = false)
	{
	$url = ($popup == 1 || $popup == true) && $url_popup != false ? $url_popup : $url;
	if ($popup == 1 || $popup === true)
		{
		return '<a href="javascript:bab_popup(\''.$url.'\')">'.$text.'</a>';
		}
	elseif ($popup == 2)
		return '<a target="_blank" href="'.$url.'">'.$text.'</a>';
	else
		return '<a href="'.$url.'">'.$text.'</a>';
	}
	
function bab_replace( $txt, $remove = '')
	{
	bab_replace_ref( $txt, $remove);
	return $txt;
	}

function bab_replace_ref( &$txt, $remove = '')
	{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	$exclude = array();
	$exclude = explode(',',$remove);
	
	$reg = "/\\\$([A-Z]*?)\((.*?)\)/";
	if (preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			if (!in_array($m[1][$k],$exclude))
				{
				$var = $m[0][$k];
				$varname = $m[1][$k];
				$param = explode(',',$m[2][$k]);

				if (count($param) > 0)
					{
					switch ($varname)
						{
						case 'ARTICLEPOPUP':
							$popup = true;
						case 'ARTICLE':
							$title_topic = count($param) > 1 ? trim($param[0],'"') : false;
							$title_object = count($param) > 1 ? trim($param[1],'"') : trim($param[0],'"');
							if (!isset($popup)) $popup = false;
							if ($title_topic)
								{
								$res = $db->db_query("select a.id,a.id_topic,a.title,a.restriction from ".BAB_TOPICS_TBL." t, ".BAB_ARTICLES_TBL." a where t.category='".addslashes($title_topic)."' AND a.id_topic=t.id AND a.title='".addslashes($title_object)."'");
								if( $res && $db->db_num_rows($res) > 0)
									$arr = $db->db_fetch_array($res);
								else
									$title_topic = false;
								}
							if (!$title_topic)
								{
								$res = $db->db_query("select id,id_topic,title,restriction from ".BAB_ARTICLES_TBL." where title LIKE '%".addslashes($title_object)."%'");
								if( $res && $db->db_num_rows($res) > 0)
									$arr = $db->db_fetch_array($res);
								}
							if(isset($babBody->topview[$arr['id_topic']]) && bab_articleAccessByRestriction($arr['restriction']))
								{
								$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic'],$title_object,$popup,$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'ARTICLEID':
							if (!is_numeric($param[0]))
								break;
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$popup = isset($param[2]) ? $param[2] : false;
							$connect = isset($param[3]) ? $param[3] : false;
							$res = $db->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$id_object."'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								$title_object = empty($title_object) ? $arr['title'] : $title_object;
								if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])))
									{
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic'],$title_object,$popup,$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
									}
								elseif (!$GLOBALS['BAB_SESS_LOGGED'] && $connect)
									{
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic']),$title_object);
									}

								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'ARTICLEFILEID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $db->db_query("select aft.*, at.id_topic, at.restriction from ".BAB_ART_FILES_TBL." aft left join ".BAB_ARTICLES_TBL." at on aft.id_article=at.id where aft.id='".$id_object."'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])))
									{
									$title_object = empty($title_object) ? (empty($arr['description'])? $arr['name']: $arr['description']) : $title_object;
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$arr['id_topic']."&idf=".$arr['id'],$title_object);
									}

								}
							bab_replace_var($txt,$var,$title_object);
							break;

						case 'CONTACT':
							$title_object = $param[0].' '.$param[1];
							$res = $db->db_query("select * from ".BAB_CONTACTS_TBL." where  owner='".$GLOBALS['BAB_SESS_USERID']."' and firstname LIKE '%".addslashes($param[0])."%' and lastname LIKE '%".addslashes($param[1])."%'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								$title_object = bab_replace_make_link($GLOBALS['babUrlScript'].'?tg=contact&idx=modify&item='.$arr['id'].'&bliste=0',$title_object,true);
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'CONTACTID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $db->db_query("select * from ".BAB_CONTACTS_TBL." where  owner='".$GLOBALS['BAB_SESS_USERID']."' and id= '".$id_object."'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								$title_object = empty($title_object) ? bab_composeUserName($arr['firstname'],$arr['lastname']) : $title_object;
								$title_object = bab_replace_make_link($GLOBALS['babUrlScript'].'?tg=contact&idx=modify&item='.$arr['id'].'&bliste=0',$title_object,true);
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'DIRECTORYID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $db->db_query("select id,sn,givenname,id_directory from ".BAB_DBDIR_ENTRIES_TBL." where id= '".$id_object."'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								if ( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id_directory']) || $arr['id_directory'] == 0 )
									{
									$title_object = empty($title_object) ? bab_composeUserName($arr['sn'],$arr['givenname']) : $title_object;
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$arr['id_directory']."&idu=".$arr['id'],$title_object,true);
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FAQ':
							$title_object = $param[1];
							$res = $db->db_query("select * from ".BAB_FAQCAT_TBL." where category='".addslashes($param[0])."'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
									{
									$req = "select * from ".BAB_FAQQR_TBL." where question='".addslashes($param[1])."'";
									$res = $db->db_query($req);
									if( $res && $db->db_num_rows($res) > 0)
										{
										$arr = $db->db_fetch_array($res);
										$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idq=".$arr['id'],$title_object,true);
										}
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FAQID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$popup = isset($param[2]) ? $param[2] : false;
							$res = $db->db_query("select * from ".BAB_FAQQR_TBL." where id='".$id_object."'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['idcat']))
									{
									$title_object = empty($title_object) ? $arr['question'] : $title_object;
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$id_object."#".$id_object,$title_object,$popup,$GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idq=".$id_object);
									
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FILE':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
							$res = $db->db_query("select * from ".BAB_FILES_TBL." where id='".$id_object."' and state='' and confirmed='Y'");
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								if (bab_isAccessFileValid($arr['bgroup'], $arr['id_owner']))
									{
									$title_object = empty($title_object) ? $arr['name'] : $title_object;
									$inl = empty($GLOBALS['files_as_attachment']) ? '&inl=1' : '';
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=fileman&idx=get".$inl."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']),$title_object,2);
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FOLDER':
							$id_object = $param[0];
							$path_object = isset($param[1]) ? $param[1] : '';
							$title_object = isset($param[2]) ? $param[2] : '';
							$res = $db->db_query("select id,folder from ".BAB_FM_FOLDERS_TBL." where id='".$id_object."' and active='Y'");
							bab_fileManagerAccessLevel();
							if( $res && $db->db_num_rows($res) > 0)
								{
								$arr = $db->db_fetch_array($res);
								if ( in_array($arr['id'],$babBody->aclfm['id']) )
									{
									$title_object = empty($title_object) ? $arr['folder'] : $title_object;
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arr['id']."&gr=Y&path=".urlencode($path_object),$title_object);
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'LINKPOPUP':
							$url_object = $param[0];
							$name_object = isset($param[1]) ? $param[1] : $url_object;
							$popup = isset($param[2]) ? $param[2] : 2;
							$title_object = "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=link&idx=popup&url=".urlencode($url_object)."', '', '');\">".$name_object."</a>";
							bab_replace_var($txt,$var,$title_object);
							break;

						case 'VAR':
							$title_object = $param[0];
							switch($title_object)
								{
								case "BAB_SESS_USERID":
								case "BAB_SESS_NICKNAME":
								case "BAB_SESS_USER":
								case "BAB_SESS_FIRSTNAME":
								case "BAB_SESS_LASTNAME":
								case "BAB_SESS_EMAIL":
									$title_object = $GLOBALS[$title_object];
									break;
								case "babslogan":
								case "adminemail":
								case "adminname":
									$title_object = $babBody->babsite[$title_object];
									break;
								default:
									$title_object = '';
									break;
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'OVML':
							$args = array();
							if( ($cnt = count($param)) > 1 )
							{
								for( $i=1; $i < $cnt; $i++)
								{
									$tmp = explode('=', $param[$i]);
									if( is_array($tmp) && count($tmp) == 2 )
										{
										$args[$tmp[0]] = trim($tmp[1], '"');
										}
								}
							}

							bab_replace_var($txt,$var,preg_replace("/\\\$OVML\(.*\)/","",bab_printOvmlTemplate($param[0], $args)));
							break;
						}
					}
				}
			else
				{
				bab_replace_var($txt,$m[1][$k],'');
				}
			}
		}
	}
?>
