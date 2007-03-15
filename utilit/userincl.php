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

function bab_editor_text_toolbar($editname,$mode)
{
	if (!class_exists('text_toolbar'))
	{
	class text_toolbar
		{
		function text_toolbar()
			{
			$this->t_bab_image = bab_translate("Insert image");
			$this->t_bab_file = bab_translate("Insert file link");
			$this->t_bab_article = bab_translate("Insert article link");
			$this->t_bab_faq = bab_translate("Insert FAQ link");
			$this->t_bab_ovml = bab_translate("Insert OVML file");
			$this->t_bab_contdir = bab_translate("Insert contact link");
			}
		}
	}

	$tmp = & new text_toolbar();
	$tmp->mode = $mode;
	$tmp->editname = $editname;
	return bab_printTemplate($tmp,"uiutil.html", "babtexttoolbartemplate");

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

/* for all users */
function bab_isUserLogged($iduser = "")
{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED, $babDB;
	
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
		if( isset($babBody->ovgroups[$babBody->usergroups[$i]]['notes']) && $babBody->ovgroups[$babBody->usergroups[$i]]['notes'] == 'Y')
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
		if( isset($babBody->ovgroups[$babBody->usergroups[$i]]['contacts']) && $babBody->ovgroups[$babBody->usergroups[$i]]['contacts'] == 'Y')
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
	
function bab_getCalendarId($iduser, $type)
{
	global $babBody, $babDB;

	if( empty($iduser))
		return 0;
	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($iduser)."' and type='".$babDB->db_escape_string($type)."'");
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
		foreach( $babBody->usergroups as $g)
			{
			if( isset($babBody->ovgroups[$g]['ustorage']) && $babBody->ovgroups[$g]['ustorage'] == 'Y')
				{
				$babBody->ustorage = true;
				break;
				}
			}
		}
	
	$res = $babDB->db_query("select id, idsa, folder, bhide from ".BAB_FM_FOLDERS_TBL." where active='Y' ORDER BY folder");
	$babBody->aclfm['bshowfm'] = false;
	$babBody->aclfm['id'] = array();
	$babBody->aclfm['folder'] = array();
	$babBody->aclfm['down'] = array();
	$babBody->aclfm['uplo'] = array();
	$babBody->aclfm['upda'] = array();
	$babBody->aclfm['idsa'] = array();
	$babBody->aclfm['ma'] = array();
	$babBody->aclfm['hide'] = array();
	while($row = $babDB->db_fetch_array($res))
		{
		$uplo = bab_isAccessValid(BAB_FMUPLOAD_GROUPS_TBL, $row['id']);
		$down = bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $row['id']);
		$upda = bab_isAccessValid(BAB_FMUPDATE_GROUPS_TBL, $row['id']);
		$man = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $row['id']);

		if( $down || $uplo || $upda || $man )
			{
			$babBody->aclfm['id'][] = $row['id'];
			$babBody->aclfm['folder'][] = $row['folder'];
			$babBody->aclfm['down'][] = $down;
			$babBody->aclfm['uplo'][] = $uplo;
			$babBody->aclfm['upda'][] = $upda;
			$babBody->aclfm['idsa'][] = $row['idsa'];
			if( $man )
				$babBody->aclfm['ma'][] = 1;
			else
				$babBody->aclfm['ma'][] = 0;

			if( ($row['bhide'] == 'Y') && ($uplo == false) && ($upda == false) && ($man == false) )
				{
				$babBody->aclfm['hide'][] = true;
				}
			else
				{
				$babBody->aclfm['hide'][] = false;
				$babBody->aclfm['bshowfm'] = true;
				}
			}
		}
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
		$emails = substr("$emails", 0, -1);
		return $emails;
		}
	else
		{
		return "";
		}
}


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
	$res = $babDB->db_query("SELECT ocet.id_node, ocet.id as id_entity, ocrut.id_role, ocrt.type  FROM ".BAB_DBDIR_ENTRIES_TBL." det LEFT JOIN ".BAB_OC_ROLES_USERS_TBL." ocrut ON det.id = ocrut.id_user LEFT  JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role LEFT JOIN ".BAB_OC_ENTITIES_TBL." ocet ON ocet.id = ocrt.id_entity WHERE ocrt.id_oc='".$babDB->db_escape_string($idoc)."' and det.id_user IN ( ".$babDB->db_escape_string($iduser)."  )  AND det.id_directory = '0' and ocrut.isprimary='Y'");
	while( $arr = $babDB->db_fetch_array($res) )
	{
		$arroles = array();
		if( $arr['type'] != 1) /* not responsable */
		{
			/* find user's responsable */
			$res = $babDB->db_query("SELECT ocrut.*  FROM ".BAB_OC_ROLES_USERS_TBL." ocrut LEFT JOIN ".BAB_OC_ROLES_TBL." ocrt ON ocrt.id = ocrut.id_role  WHERE ocrt.id_entity IN (".$babDB->quote($arr['id_entity']).")  AND ocrt.type = '1'");

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
			$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_OC_TREES_TBL." where id='".$babDB->db_escape_string($arr['id_node'])."'"));
			$res = $babDB->db_query("
			
			SELECT ocrut.* 
			FROM ".BAB_OC_ROLES_USERS_TBL." ocrut 
			LEFT  JOIN ".BAB_OC_ROLES_TBL." ocrt 
				ON ocrt.id = ocrut.id_role 
			LEFT  JOIN ".BAB_OC_ENTITIES_TBL." ocet 
				ON ocrt.id_entity = ocet.id 
			LEFT  JOIN ".BAB_OC_TREES_TBL." oct 
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
			$babBody->usergroups[] = $idgroup;
			}
		}

	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	
	require_once($GLOBALS['babInstallPath']."utilit/eventdirectory.php");
	$event = new bab_eventUserAttachedToGroup($iduser, $idgroup);
	bab_fireEvent($event);
	
	/**
	 * @deprecated
	 */
	bab_callAddonsFunction('onUserAssignedToGroup', $iduser, $idgroup);
}

function bab_removeUserFromGroup($iduser, $idgroup)
{
	global $babDB, $babBody;

	$babDB->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_group='".$babDB->db_escape_string($idgroup)."' and id_object='".$babDB->db_escape_string($iduser)."'");
	$idx = bab_array_search($idgroup, $babBody->usergroups);
	if( $idx )
		{
		array_splice($babBody->usergroups, $idx, 1);
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
	
	/**
	 * @deprecated
	 */
	bab_callAddonsFunction('onUserUnassignedFromGroup', $iduser, $idgroup);
}

function bab_addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $isconfirmed, &$error, $bgroup = true)
	{
	require_once($GLOBALS['babInstallPath']."utilit/usermodifyincl.php");
	return bab_userModify::addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $isconfirmed, $error, $bgroup);
	}

function bab_replace_var(&$txt,$var,$new)
	{
	$txt = preg_replace("/".preg_quote($var,"/")."/", $new, $txt);
	}
	
function bab_replace_make_link($url,$text,$popup = 0,$url_popup = false)
	{
	if (isset($GLOBALS['bab_replace_ext_url'])) {
		$url = $GLOBALS['babUrlScript']."?tg=login&cmd=detect&referer=".urlencode($url);
		$popup = 0;
		}

	$url = ($popup == 1 || $popup == true) && $url_popup != false ? $url_popup : $url;
	if ($popup == 1 || $popup === true)
		{
		return '<a href="'.bab_toHtml($url).'" onclick="bab_popup(this.href);return false;">'.$text.'</a>';
		}
	elseif ($popup == 2) {
		return '<a target="_blank" href="'.bab_toHtml($url).'">'.$text.'</a>';
		}
	else {
		return '<a href="'.bab_toHtml($url).'">'.$text.'</a>';
		}
	}
	
function bab_replace( $txt, $remove = '')
	{
	bab_replace_ref( $txt, $remove);
	return $txt;
	}

function bab_replace_ext($txt, $remove = '')
	{
	$GLOBALS['bab_replace_ext_url'] = true;
	bab_replace_ref( $txt, $remove);
	unset($GLOBALS['bab_replace_ext_url']);
	return $txt;
	}

function bab_replace_ref( &$txt, $remove = '')
	{
	global $babBody, $babDB;

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
								$res = $babDB->db_query("select a.id,a.id_topic,a.title,a.restriction from ".BAB_TOPICS_TBL." t, ".BAB_ARTICLES_TBL." a where t.category='".$babDB->db_escape_string($title_topic)."' AND a.id_topic=t.id AND a.title='".$babDB->db_escape_string($title_object)."'");
								if( $res && $babDB->db_num_rows($res) > 0)
									$arr = $babDB->db_fetch_array($res);
								else
									$title_topic = false;
								}
							if (!$title_topic)
								{
								$res = $babDB->db_query("select id,id_topic,title,restriction from ".BAB_ARTICLES_TBL." where title LIKE '%".$babDB->db_escape_like($title_object)."%'");
								if( $res && $babDB->db_num_rows($res) > 0)
									$arr = $babDB->db_fetch_array($res);
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
							$res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
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
							$res = $babDB->db_query("select aft.*, at.id_topic, at.restriction from ".BAB_ART_FILES_TBL." aft left join ".BAB_ARTICLES_TBL." at on aft.id_article=at.id where aft.id='".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
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
							$res = $babDB->db_query("select * from ".BAB_CONTACTS_TBL." where  owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and firstname LIKE '%".$babDB->db_escape_string($param[0])."%' and lastname LIKE '%".$babDB->db_escape_like($param[1])."%'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								$title_object = bab_replace_make_link($GLOBALS['babUrlScript'].'?tg=contact&idx=modify&item='.$arr['id'].'&bliste=0',$title_object,true);
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'CONTACTID':
							$id_object = $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $babDB->db_query("select * from ".BAB_CONTACTS_TBL." where  owner='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and id= '".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								$title_object = empty($title_object) ? bab_composeUserName($arr['firstname'],$arr['lastname']) : $title_object;
								$title_object = bab_replace_make_link($GLOBALS['babUrlScript'].'?tg=contact&idx=modify&item='.$arr['id'].'&bliste=0',$title_object,true);
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'DIRECTORYID':
							$id_object = trim($param[0]);
							$title_object = isset($param[1]) ? $param[1] : '';
							$res = $babDB->db_query("select id,sn,givenname,id_directory from ".BAB_DBDIR_ENTRIES_TBL." where id= '".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if( $arr['id_directory'] == 0  )
									{
									$iddir = isset($param[2]) ? trim($param[2]): '' ;
									}
								else
									{
									$iddir = $arr['id_directory'];
									}

								if ( $iddir && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $iddir))
									{
									$title_object = empty($title_object) ? bab_composeUserName($arr['sn'],$arr['givenname']) : $title_object;
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".((int) $iddir)."&userid=".$arr['id'],$title_object,true);
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FAQ':
							$title_object = $param[1];
							$res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where category='".$babDB->db_escape_string($param[0])."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
									{
									$req = "select * from ".BAB_FAQQR_TBL." where question='".$babDB->db_escape_string($param[1])."'";
									$res = $babDB->db_query($req);
									if( $res && $babDB->db_num_rows($res) > 0)
										{
										$arr = $babDB->db_fetch_array($res);
										$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idq=".$arr['id'],$title_object,true);
										}
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FAQID':
							$id_object = (int) $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							$popup = isset($param[2]) ? $param[2] : false;
							$res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($id_object)."'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['idcat']))
									{
									$title_object = empty($title_object) ? $arr['question'] : $title_object;
									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=faq&idx=listq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$id_object."#".$id_object,$title_object,$popup,$GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idq=".$id_object);
									
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FILE':
							$id_object = (int) $param[0];
							$title_object = isset($param[1]) ? $param[1] : '';
							include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
							$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($id_object)."' and state='' and confirmed='Y'");
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
								if (bab_isAccessFileValid($arr['bgroup'], $arr['id_owner']))
									{
									$title_object = empty($title_object) ? $arr['name'] : $title_object;
									if( bab_getFileContentDisposition() == '')
										{
										$inl = empty($GLOBALS['files_as_attachment']) ? '&inl=1' : '';
										}
									else
										{
										$inl ='';
										}

									$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=fileman&idx=get".$inl."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']),$title_object,2);
									}
								}
							bab_replace_var($txt,$var,$title_object);
							break;
							
						case 'FOLDER':
							$id_object = (int) $param[0];
							$path_object = isset($param[1]) ? $param[1] : '';
							$title_object = isset($param[2]) ? $param[2] : '';
							$res = $babDB->db_query("select id,folder from ".BAB_FM_FOLDERS_TBL." where id='".$babDB->db_escape_string($id_object)."' and active='Y'");
							bab_fileManagerAccessLevel();
							if( $res && $babDB->db_num_rows($res) > 0)
								{
								$arr = $babDB->db_fetch_array($res);
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
							$title_object = isset($param[1]) ? $param[1] : $url_object;
							$popup = isset($param[2]) ? $param[2] : 2;
							$title_object = bab_replace_make_link($GLOBALS['babUrlScript']."?tg=link&idx=popup&url=".urlencode($url_object),$title_object, $popup);
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
										$args[trim($tmp[0])] = trim($tmp[1], '"');
										}
								}
							}

							bab_replace_var($txt,$var,preg_replace("/\\\$OVML\(.*\)/","",trim(bab_printOvmlTemplate($param[0], $args))));
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
