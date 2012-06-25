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

/**
* @internal SEC1 PR 2006-12-12 FULL
*/

include_once "base.php";

define("BAB_ART_STATUS_DRAFT", 0); /* Used with BAB_ART_DRAFTS_TBL table in column result */
define("BAB_ART_STATUS_WAIT", 1); /* Used with BAB_ART_DRAFTS_TBL table in column result : article draft is waiting to approbation */
define("BAB_ART_STATUS_OK"	, 2); /* Used with BAB_ART_DRAFTS_TBL table in column result : article draft is approved (Remark : this status is not used because a draft approved in converted to an article) */
define("BAB_ART_STATUS_NOK"	, 3); /* Used with BAB_ART_DRAFTS_TBL table in column result : article draft is non-approved */

function bab_printOvml($content, $args)
	{
	include_once $GLOBALS['babInstallPath']."utilit/omlincl.php";
	$tpl = new babOvTemplate($args);
	return $tpl->printout($content);
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

/**
 * Return true if the current user can create or modify at least one article
 * @return bool
 */
function bab_isArticleEditAccess()
{
	global $babDB;
	
	if( count(bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL)) > 0  || count(bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL)) > 0 )
	{
		return true;
	}
	
	
	if (!$GLOBALS['BAB_SESS_LOGGED'])
	{
		return false;
	}
	
	
	// topics where i am manager
	
	$topman = bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL);
	
	if (!empty($topman))
	{
		$res = $babDB->db_query('SELECT id FROM bab_topics WHERE id IN('.$babDB->quote($topman).") AND allow_manupdate<>'0'");
		if ($babDB->db_num_rows($res) > 0)
		{
			return true;
		}
	}
	
	
	// articles where i am author
	
	$res = $babDB->db_query("SELECT a.id FROM bab_topics t, bab_articles a 
		WHERE a.id_topic=t.id AND allow_update<>'0' AND a.id_author=".$babDB->quote($GLOBALS['BAB_SESS_USERID']));
	
	if ($babDB->db_num_rows($res) > 0)
	{
		return true;
	}
	
	
	
	return false;
}



function bab_isUserTopicManager($topics)
	{
	return bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $topics);
	}

function bab_isUserArticleApprover($topics)
	{
	global $BAB_SESS_USERID,$babDB;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	
	$query = "select idsaart from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return bab_isCurrentUserApproverFlow($arr['idsaart']);
		}
	else
		{
		return false;
		}
	}

function bab_isUserCommentApprover($topics)
	{
	global $BAB_SESS_USERID,$babDB;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$query = "select idsacom from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return bab_isCurrentUserApproverFlow($arr['idsacom']);
		}
	else
		{
		return false;
		}
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
			list($posts) = $babDB->db_fetch_row($babDB->db_query("select count(pt.id) from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on pt.id_thread=tt.id left join ".BAB_POSTS_TBL." pt2 on tt.post=pt2.id left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.confirmed='N' and ft.id IN(".$babDB->quote($arrf).")"));
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
			$res = $babDB->db_query("SELECT adt.id , adt.idfai, adt.id_topic FROM ".BAB_ART_DRAFTS_TBL." adt WHERE adt.result='".$babDB->db_escape_string(BAB_ART_STATUS_WAIT)."'");
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


function bab_deleteArticleDraft($id)
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteDraft($id);
}

/* Test if the current user is logged and set the global variable $BAB_SESS_LOGGED
 * @return boolean
 */
function bab_isUserLogged($iduser = "")
{
	// global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED, $babDB;
	global $BAB_SESS_LOGGED, $babDB;
	require_once dirname(__FILE__).'/session.class.php';
	
	if( !isset($iduser) || empty($iduser) || $iduser == $GLOBALS['BAB_SESS_USERID'])
		{
		if (isset($BAB_SESS_LOGGED))
			{
			return $BAB_SESS_LOGGED;
			}
			
		$session = bab_getInstance('bab_Session');

		if (!empty($session->BAB_SESS_NICKNAME) && !empty($session->BAB_SESS_HASHID))
			{
			$hash=md5($session->BAB_SESS_NICKNAME.bab_getHashVar());
			if ($hash == $session->BAB_SESS_HASHID)
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

		$res=$babDB->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='".$babDB->db_escape_string($iduser)."'");
		if( $res && $babDB->db_num_rows($res) > 0)
			return true;		
		return false;
	}
}

function bab_getDbUserName($id)
	{
	static $arrnames = array();

	if( isset($arrnames[$id]) )
		return $arrnames[$id];

	global $babDB;
	$query = "select sn, givenname, mn from ".BAB_DBDIR_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$arrnames[$id] = bab_composeUserName($arr['givenname'], $arr['sn']);
		}
	else
		{
		$arrnames[$id] = "";
		}
	return $arrnames[$id];
	}




/**
 * @return int
 *  0 no access, 1 user
 */ 
function bab_mailAccessLevel()
	{
	global $babBody;

	$user = 0;
	
	$ovgroups = bab_Groups::getGroups();
	
	reset($ovgroups);
	while( list(,$arr) = each($ovgroups) ) 
	{ 
		if( isset($arr['mail']) && $arr['mail'] == 'Y')
		{
			if( false !== bab_isMemberOfGroup($arr['id']))
			{
				return 1;
				
			}
		}
	}

	return 0;
}

/**
 * 
 * @return bool
 */
function bab_notesAccess()
	{
	$registered = bab_Groups::get(BAB_REGISTERED_GROUP);
	
	if( $GLOBALS['BAB_SESS_LOGGED'] && $registered['notes'] == 'Y' )
	{
		return true;
	}
	
	$usergroups = bab_Groups::getUserGroups();

	for( $i = 0; $i < count($usergroups); $i++)
		{
		$group = bab_Groups::get($usergroups[$i]);
			
		if( isset($group['notes']) && $group['notes'] == 'Y')
			{
			return true;
			}
		}
	return false;
	}


/**
 * get id of accessibles org charts
 * @return array
 */
function bab_orgchartAccess()
	{
	static $ret = null;

	if (null === $ret) {
		
		global $babDB;

		$ret = array();
		$res = $babDB->db_query("select id from ".BAB_ORG_CHARTS_TBL."");
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_OCVIEW_GROUPS_TBL, $row['id']) || bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $row['id']))
				{
				$ret[] = $row['id'];
				}
			}
		}
	return $ret;
	}

/**
 * 
 * @return bool
 */
function bab_contactsAccess()
	{
	
	$registered = bab_Groups::get(BAB_REGISTERED_GROUP);
	
	if( $GLOBALS['BAB_SESS_LOGGED'] && $registered['contacts'] == 'Y' )
	{
		return true;
	}

	$usergroups = bab_Groups::getUserGroups();
	for( $i = 0; $i < count($usergroups); $i++)
		{
			$group = bab_Groups::get($usergroups[$i]);
		if( isset($group['contacts']) && $group['contacts'] == 'Y')
			{
			return true;
			}
		}
	return false;
	}

function bab_vacationsAccess()
	{
	global $babBody, $babDB;

	$array = array();
	$res = $babDB->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$array['user'] = true;
		}

	$res = $babDB->db_query("select id from ".BAB_VAC_MANAGERS_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$array['manager'] = true;
		}

	$arrchi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	$res = $babDB->db_query("select idfai from ".BAB_VAC_ENTRIES_TBL."  where status=''");
	while($arr =  $babDB->db_fetch_array($res) )
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
	global $babDB;

	if( empty($restriction))
		return true;

	if( strchr($restriction, ","))
		$sep = ',';
	else
		$sep = '&';

	$arr = explode($sep, $restriction);
	if( empty($iduser))
		$iduser = $GLOBALS['BAB_SESS_USERID'];

	$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($iduser)."' and id_group IN (".$babDB->quote($arr).")";
	$res = $babDB->db_query($req);
	$num = $babDB->db_num_rows($res);
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
	global $babDB;

	list($restriction) = $babDB->db_fetch_row($babDB->db_query("select restriction from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	if( empty($restriction))
		return true;
	return bab_articleAccessByRestriction($restriction, $iduser);
	}
	
	
/**
 * get calendar reference part "type/id"
 * 
 * @return string | null
 */
function bab_getDefaultCalendarId()
{
	$calendar = bab_getICalendars()->getDefaultCalendar();
	
	if (!$calendar) {
		return null;
	}
	
	$reference = $calendar->getReference();
	$type = $reference->getType();
	$idObject = $reference->getObjectId();
	
	return "$type/$idObject";
}

function bab_calendarAccess()
	{
	global $babBody;
	return bab_getICalendars()->calendarAccess();
	}


function bab_statisticsAccess()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	if( isset($babBody->stataccess))
		{
		return $babBody->stataccess;
		}

	$babBody->stataccess = -1;
	if (bab_isAccessValid(BAB_STATSMAN_GROUPS_TBL, 1) )
		{
		$babBody->stataccess = BAB_STAT_ACCESS_MANAGER; // stat manager
		}
	elseif( $babBody->currentAdmGroup != 0 )
		{
		$babBody->stataccess = BAB_STAT_ACCESS_DELEGATION; // stat delegation
		}
	else
		{
		$bbasket = false;

		$res = $babDB->db_query("select id from ".BAB_STATS_BASKETS_TBL."");
		while( $arr = $babDB->db_fetch_array($res))
			{
			if( bab_isAccessValid(BAB_STATSBASKETS_GROUPS_TBL,$arr['id']))
				{
				$bbasket = true;
				break;
				}
			}
		if( $bbasket )
			{
			$babBody->stataccess = BAB_STAT_ACCESS_USER; // stat user
			}
		}
	return $babBody->stataccess;
	}


function bab_getGroupEmails($id)
{
	global $babDB;
	$query = "select distinct email from ".BAB_USERS_TBL." usr , ".BAB_USERS_GROUPS_TBL." grp where grp.id_group in ('".$babDB->db_escape_string($id)."') and grp.id_object=usr.id";
	$res = $babDB->db_query($query);
	$emails = "";
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		while ($arr = $babDB->db_fetch_array($res)){
		if ($arr['email'])
			{
			$emails .= $arr['email'].",";
			}
		}
		$emails = mb_substr("$emails", 0, -1);
		return $emails;
		}
	else
		{
		return "";
		}
}

/**
 * 
 * @param array $idroles
 * @return array
 */
function bab_getOrgChartRoleUsers($idroles)
{
	global $babDB;

	$arr = array();
	$res = $babDB->db_query("select det.sn, det.givenname, det.id_user, ocrut.id_role from ".BAB_DBDIR_ENTRIES_TBL." det left join ".BAB_OC_ROLES_USERS_TBL." ocrut on det.id=ocrut.id_user where ocrut.id_role IN (".$babDB->quote($idroles).")");
	while( $row = $babDB->db_fetch_array($res))
	{
		$arr['iduser'][] = $row['id_user'];
		$arr['idrole'][] = $row['id_role'];
		$arr['name'][] = bab_composeUserName($row['givenname'], $row['sn']);
	}
	return $arr;
}


/**
 * Get superior in organizational chart
 * @param int	$iduser
 * @param int	$idoc
 * @return unknown_type
 */
function bab_getSuperior($iduser, $idoc = '')
{
	global $babBody, $babDB;

	static $supparr = array();

	if( empty($idoc))
	{
		if( !empty($babBody->idprimaryoc))
		{
			$idoc = $babBody->idprimaryoc;
		}
		else
		{
			$res = $babDB->db_query("select oct.id from ".BAB_ORG_CHARTS_TBL." oct LEFT JOIN ".BAB_DB_DIRECTORIES_TBL." ddt on oct.id_directory=ddt.id where ddt.id_group='1' and oct.isprimary='Y'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				$ocinfo = $babDB->db_fetch_array($res);
				$idoc = $ocinfo['id'];
				$babBody->idprimaryoc = $idoc;
			}
			else
			{
				return array();
			}
		}
	}


	if (isset($supparr[$idoc.'.'.$iduser])) {
		return $supparr[$idoc.'.'.$iduser];
	}


	/* find user primary role */
	$query = "
		SELECT 
			ocet.id_node, 
			ocet.id as id_entity, 
			ocrut.id_role, 
			ocrt.type  
		FROM 
			".BAB_DBDIR_ENTRIES_TBL." det 
				LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON det.id = ocrut.id_user 
				LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role 
				LEFT JOIN ".BAB_OC_ENTITIES_TBL." ocet ON ocet.id = ocrt.id_entity 
				
		WHERE 
			ocrt.id_oc='".$babDB->db_escape_string($idoc)."' 
			AND det.id_user IN ( ".$babDB->db_escape_string($iduser)."  )  
			AND det.id_directory = '0' 
			AND ocrut.isprimary='Y'
	";
	$res = $babDB->db_query($query." AND ocrut.isprimary='Y'");
	
	if (0 === $babDB->db_num_rows($res))
	{
		bab_debug(sprintf('No primary role found in chart %d for user %s', $idoc, bab_getUserName($iduser)));
		
		// try on each roles
		$res = $babDB->db_query($query);
	}
	
	
	while( $arr = $babDB->db_fetch_array($res) )
	{
		$arroles = array();
		if( $arr['type'] != 1) /* not responsible */
		{
			/* find user's responsible in same entity */
			
			$res = $babDB->db_query("
				SELECT 
					ocrut.*  
				FROM ".BAB_OC_ROLES_USERS_TBL." ocrut 
					LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role  
					
				WHERE 
					ocrt.id_entity IN (".$babDB->quote($arr['id_entity']).") 
					AND ocrt.type = '1'
			");

			while( $row = $babDB->db_fetch_array($res) )
			{
				$arroles[]= $row['id_role'];
			}
			if( count($arroles) > 0 )
				{
				return $supparr[$idoc.'.'.$iduser] = bab_getOrgChartRoleUsers($arroles);
				}
		}

		if( count($arroles) == 0 )
		{
			/* find user's responsible in upper entity */
				
			$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_TREES_TBL." where id='".$babDB->db_escape_string($arr['id_node'])."'"));
			$res = $babDB->db_query("
			
			SELECT ocrut.* 
			FROM ".BAB_OC_ROLES_USERS_TBL." ocrut 
				LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt 
					ON ocrt.id = ocrut.id_role 
				LEFT JOIN ".BAB_OC_ENTITIES_TBL." ocet 
					ON ocrt.id_entity = ocet.id 
				LEFT JOIN ".BAB_OC_TREES_TBL." oct 
					ON oct.id = ocet.id_node and oct.id_user='".$babDB->db_escape_string($idoc)."' 
			
			WHERE 
				ocrt.id_oc='".$babDB->db_escape_string($idoc)."' 
				and oct.lf <  '".$babDB->db_escape_string($rr['lf'])."' 
				AND oct.lr >  '".$babDB->db_escape_string($rr['lr'])."' 
				AND ocrut.isprimary='Y' 
				and ocrt.type ='1' 
			ORDER  BY oct.lf desc 
			limit 0,1
			");
			
			while( $row = $babDB->db_fetch_array($res) )
			{
				$arroles[]= $row['id_role'];
			}
			
			if( count($arroles) > 0 )
			{
				return $supparr[$idoc.'.'.$iduser] = bab_getOrgChartRoleUsers($arroles);
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
		list($identity) = $babDB->db_fetch_row($babDB->db_query("select id_ocentity from ".BAB_GROUPS_TBL." where id='".$babDB->db_escape_string($idgroup)."'"));
		if( $identity )
		{
			list($idrole) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_OC_ROLES_TBL." where id_entity='".$babDB->db_escape_string($identity)."' and type='3'"));
			list($idduser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$babDB->db_escape_string($iduser)."'"));
			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_OC_ROLES_USERS_TBL." where id_role='".$babDB->db_escape_string($idrole)."' and id_user='".$babDB->db_escape_string($idduser)."'"));
			if( !$total )
				{
				$req = "insert into ".BAB_OC_ROLES_USERS_TBL." (id_role, id_user, isprimary) values ('".$babDB->db_escape_string($idrole)."','".$babDB->db_escape_string($idduser)."','Y')";
				$babDB->db_query($req);
				}
		}
	}

	list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_USERS_GROUPS_TBL." where id_group='".$babDB->db_escape_string($idgroup)."' and id_object='".$babDB->db_escape_string($iduser)."'"));
	if( !$total )
		{
		$res = $babDB->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_group, id_object) VALUES ('" .$babDB->db_escape_string($idgroup). "', '" . $babDB->db_escape_string($iduser). "')");
		if( isset($GLOBALS['BAB_SESS_LOGGED']) && $GLOBALS['BAB_SESS_LOGGED'] && $GLOBALS['BAB_SESS_USERID'] == $iduser )
			{
			// add to cache
			$_SESSION['bab_groupAccess']['usergroups'][] = $idgroup;
			}
		}
		
	list($pcalendar) = $babDB->db_fetch_row($babDB->db_query("select pcalendar as pcal from ".BAB_GROUPS_TBL." where id='".$babDB->db_escape_string($idgroup)."'"));
	if( $pcalendar == 'Y')
	{
		$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='Y' where type='".BAB_CAL_USER_TYPE."' and owner=".$babDB->quote($iduser)); 
	}
		
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	
	require_once($GLOBALS['babInstallPath']."utilit/eventdirectory.php");
	$event = new bab_eventUserAttachedToGroup($iduser, $idgroup);
	bab_fireEvent($event);
	
	bab_siteMap::clear($iduser);
	
	/**
	 * @deprecated
	 */
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunction('onUserAssignedToGroup', $iduser, $idgroup);
}

function bab_removeUserFromGroup($iduser, $idgroup)
{
	global $babDB, $babBody;

	$babDB->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_group='".$babDB->db_escape_string($idgroup)."' and id_object='".$babDB->db_escape_string($iduser)."'");
	
	if (isset($_SESSION['bab_groupAccess']['usergroups']))
	{
		// update in cache
		$idx = bab_array_search($idgroup, $_SESSION['bab_groupAccess']['usergroups']);
		if( $idx )
			{
			array_splice($_SESSION['bab_groupAccess']['usergroups'], $idx, 1);
			}
	}

	list($identity) = $babDB->db_fetch_row($babDB->db_query("select id_ocentity from ".BAB_GROUPS_TBL." where id='".$babDB->db_escape_string($idgroup)."'"));
	if( $identity )
		{
		$res = $babDB->db_query("select ocrut.id FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrut.id_role = ocrt.id LEFT JOIN ".BAB_DBDIR_ENTRIES_TBL." det ON ocrut.id_user = det.id WHERE ocrt.id_entity =  '".$babDB->db_escape_string($identity)."' AND det.id_directory =  '0' AND det.id_user =  '".$babDB->db_escape_string($iduser)."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$babDB->db_query("delete from ".BAB_OC_ROLES_USERS_TBL." where id='".$babDB->db_escape_string($row['id'])."'");
			}
		}

	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	
	require_once($GLOBALS['babInstallPath']."utilit/eventdirectory.php");
	$event = new bab_eventUserDetachedFromGroup($iduser, $idgroup);
	bab_fireEvent($event);
	
	bab_siteMap::clear($iduser);
	
	/**
	 * @deprecated
	 */
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunction('onUserUnassignedFromGroup', $iduser, $idgroup);
}

function bab_addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $isconfirmed, &$error, $bgroup = true)
	{
	require_once($GLOBALS['babInstallPath']."utilit/usermodifyincl.php");
	return bab_userModify::addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $isconfirmed, $error, $bgroup);
	}
	
	
/**
 * get a bab_replace object instance
 * @see bab_replace
 * @since 6.4.0
 */
function bab_replace_get() {
	static $obj = NULL;
	if (NULL === $obj) {
		require_once($GLOBALS['babInstallPath']."utilit/replaceincl.php");
		$obj = new bab_replace();
	}
	
	return $obj;
}


/**
* Test if a session for a given user or for the current user
* is active
* Used in add-ons
* 
* @param	string	$sIdSession	Session to test
* @param	integer	$iIdUser	Optional user identifier, if this parameter
* 								is not passed so the current logged user is
* 								used
* 
* @since	6.7.0
* @author	Zébina Samuel
* 
* @return	boolean	True if the session for the given user is in bab_user_logs,
* 					false othewise
*/
function bab_userSessionActive($sIdSession, $iIdUser = null)
{
	if(0 >= (int) $iIdUser)
	{
		$iIdUser = (int) $GLOBALS['BAB_SESS_USERID'];
	}
	
	global $babDB;
	
	$sQuery = 
		'SELECT 
			* 
		FROM ' . 
			BAB_USERS_LOG_TBL . '
		WHERE 
			id_user = ' . $babDB->quote($iIdUser) . ' AND 
			sessid = ' . $babDB->quote($sIdSession);

	//bab_debug($sQuery);
			
	$oResult = $babDB->db_query($sQuery);
	if(false !== $oResult)
	{
		return (0 < $babDB->db_num_rows($oResult));
	}
	return false;
}
?>
