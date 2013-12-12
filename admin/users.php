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
* @internal SEC1 PR 16/02/2007 FULL
*/
include_once "base.php";
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
require_once $babInstallPath . 'utilit/toolbar.class.php';
include_once $babInstallPath . 'admin/register.php';
include_once $babInstallPath . 'utilit/lusersincl.php';

function listUsers($pos, $grp, $deleteAction)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
		var $email;
		var $status;
				
		var $fullnameval;
		var $emailval;

		var $arr = array();
		var $count;
		var $res;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $urlmail;

		var $grp;
		var $group;
		var $groupurl;
		var $checked;
		var $userid;
		var $usert;

		var $nickname;
		var $bmodname;
		var $altbg = true;

		var $sHtmlToolBarData		= '';
		var $sSearchCaption			= '';
		var $sSearchButtonCaption	= '';
		var $sSearchText			= '';
		var $iUseSearchText			= '0';
			
		var $sContent;
		
		function temp($pos, $grp, $deleteAction)
			{
			global $babBody;
			global $babDB;

			$this->email			= bab_translate("Email");
			$this->allname			= bab_translate("All");
			$this->updategroup		= bab_translate("Update group");
			$this->nickname			= bab_translate("Login ID");
			$this->t_online			= bab_translate("Online");
			$this->t_unconfirmed	= bab_translate("Unconfirmed");
			$this->t_disabled		= bab_translate("Disabled");
			$this->t_dirdetail		= bab_translate("Detail");
			$this->t_groups			= bab_translate("Groups");
			$this->checkall			= bab_translate("Check all");
			$this->uncheckall		= bab_translate("Uncheck all");
			$this->sContent			= 'text/html; charset=' . bab_charset::getIso();
			$this->t_or				= bab_translate("Or");
			$this->t_delete			= bab_translate("Delete");
			$this->withselected 	= bab_translate("With selected");
			
			$this->group	= bab_toHtml(bab_getGroupName($grp));
			$this->deleteAction = $deleteAction;	

			$this->grp		= bab_toHtml($grp);

			$this->namesearch	= "lastname";
			$this->namesearch2	= "firstname";

			// group members
			$this->group_members = array();
			if( !$this->deleteAction)
			{
				$res = $babDB->db_query("SELECT id_object FROM ".BAB_USERS_GROUPS_TBL." WHERE id_group='".$babDB->db_escape_string($this->grp)."'");
				while (list($id_user) = $babDB->db_fetch_array($res))
					{
					$this->group_members[$id_user] = $id_user;
					}
			}
			
			// User login status
			$this->users_logged = array();
			$res = $babDB->db_query("SELECT id_user FROM ".BAB_USERS_LOG_TBL."");
			while (list($id_user) = $babDB->db_fetch_array($res))
				{
				$this->users_logged[$id_user] = $id_user;
				}

			$this->bupdate				= bab_toHtml(bab_rp('bupd', 0));
			$this->sSearchText			= bab_toHtml(bab_rp('sSearchText', ''));
			$this->iUseSearchText		= (0 == mb_strlen(trim($this->sSearchText))) ? '0' : '1';
			
			$currentDGGroup = bab_getCurrentDGGroup();

			if(0 == $this->iUseSearchText)
			{
				$req = "SELECT distinct u.* from ".BAB_USERS_TBL." u";
	
				if( isset($pos) &&  mb_strlen($pos) > 0 && $pos[0] == "-" )
					{
					$this->pos = mb_strlen($pos)>1? bab_toHtml($pos[1]): '';
					$this->ord = bab_toHtml($pos[0]);
					
					
					if( bab_getCurrentAdmGroup() == 0 || ($this->bupdate && bab_isDelegated('battach') && $this->grp == $currentDGGroup['id_group']))
						{
						$req .= " where ".$this->namesearch2." like '".$babDB->db_escape_string($this->pos)."%' order by ".$babDB->db_escape_string($this->namesearch2).", ".$babDB->db_escape_string($this->namesearch)." asc";
						}
					else
						{
						$req .= ", ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g 
							where 
								".bab_userInfos::queryAllowedUsers('u')." 
								and ug.id_object=u.id 
								and ug.id_group=g.id 
								AND g.lf>='".$babDB->db_escape_string($currentDGGroup['lf'])."' 
								AND g.lr<='".$babDB->db_escape_string($currentDGGroup['lr'])."' 
								and u.".$babDB->db_escape_string($this->namesearch2)." like '".$babDB->db_escape_string($this->pos)."%' 
							
							order by u.".$babDB->db_escape_string($this->namesearch2).", u.".$babDB->db_escape_string($this->namesearch)." asc 
						";
						}
	
					//$this->fullname = bab_toHtml(bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname")));
					$this->fullnameurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".urlencode($this->ord.$this->pos)."&grp=".urlencode($this->grp));
					}
				else
					{
					$this->pos = bab_toHtml($pos);
					$this->ord = "";
					if( bab_getCurrentAdmGroup() == 0 || ($this->bupdate && bab_isDelegated('battach') && $this->grp == $currentDGGroup['id_group']))
						$req .= " where ".$babDB->db_escape_string($this->namesearch)." like '".$babDB->db_escape_string($this->pos)."%' order by ".$babDB->db_escape_string($this->namesearch).", ".$babDB->db_escape_string($this->namesearch2)." asc";
					else {
						$req .= ", ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g 
							where 
								".bab_userInfos::queryAllowedUsers('u')." 
								and ug.id_object=u.id 
								and ug.id_group=g.id 
								AND g.lf>='".$babDB->db_escape_string($currentDGGroup['lf'])."' 
								AND g.lr<='".$babDB->db_escape_string($currentDGGroup['lr'])."' 
								and u.".$babDB->db_escape_string($this->namesearch)." like '".$babDB->db_escape_string($this->pos)."%' 
								
							order by u.".$babDB->db_escape_string($this->namesearch).", u.".$babDB->db_escape_string($this->namesearch2)." asc
						";
					}
					//$this->fullname = bab_toHtml(bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname")));
					
					$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".urlencode($this->ord.$this->pos)."&grp=".urlencode($this->grp));
					}
				if( !$this->ord == "-" )
					$this->fullname = bab_toHtml(bab_translate("Lastname") . ' ' . bab_translate("Firstname"));
				else
					$this->fullname = bab_toHtml(bab_translate("Firstname") . ' ' . bab_translate("Lastname"));
				
				// bab_debug($req);
				$this->res = $babDB->db_query($req);
			}
			else
			{
				$this->selectFilteredUsers($pos, $grp);
			}
				
			$this->count = $babDB->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=List&pos=&grp=".$this->grp."&bupd=".$this->bupdate);
			$this->groupurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$this->grp);
			
			if( (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0))
			{
				list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group=".$babDB->quote(BAB_REGISTERED_GROUP)));
			} else {
				list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group=".$babDB->quote(bab_getCurrentAdmGroup())));
			}
			
			$this->set_directory = bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL,$iddir);
			
			if( (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0) || bab_isDelegated('users') )
			{
				
				$this->bmodname = true;
			}
			else
			{
				$this->bmodname = false;
			}
			
			

			$this->userst = '';

			if( bab_getCurrentAdmGroup() != 0 && $grp == $currentDGGroup['id_group'] && (!bab_isDelegated('battach') || $this->bupdate == 0))
				{
				$this->bshowform = false;
				}
			else
				{
				$this->bshowform = true;
				}
	
			//The toolbar call bab_toHtml
			$sCreateUserUrl = $GLOBALS['babUrlScript'].'?tg=users&idx=Create&pos='.urlencode($pos).'&grp='.urlencode($grp);
			$sAttachUserUrl = $GLOBALS['babUrlScript'].'?tg=users&idx=List&grp='.urlencode($grp).'&bupd=1';
			$sSearchUserUrl = $GLOBALS['babUrlScript'].'?tg=users&idx=List&pos='.urlencode($pos).'&grp='.urlencode($grp).
				'&sSearchText='.urlencode($this->sSearchText);
			
			$this->sSearchCaption		= bab_translate("Search by login ID, firstname, lastname and email");
			$this->sSearchButtonCaption	= bab_translate("Search");
			
			$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
			$babBody->addStyleSheet('toolbar.css');
			$babBody->addStyleSheet('admUserList.css');
			$sImgPath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/images/Puces/';
			
			$oToolbar = new BAB_Toolbar();
			
			if( (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0) || bab_isDelegated('users'))
				{
					$oToolbar->addToolbarItem(
						new BAB_ToolbarItem(bab_translate("Create a user"), $sCreateUserUrl, 
							$sImgPath . 'list-add-user.png', bab_translate("Create a user"), bab_translate("Add a user"), '')
					);
				}
			if( bab_getCurrentAdmGroup() != 0 && bab_isDelegated('battach') && isset($_REQUEST['bupd']) && $_REQUEST['bupd'] == 0)
				{
					$oToolbar->addToolbarItem(
						new BAB_ToolbarItem(bab_translate("Attach"), $sAttachUserUrl, 
							$sImgPath . 'user-group-new.png', bab_translate("Attach"), bab_translate("Attach"), '')
					);
				}
			
			if($this->bshowform) 
				{
				$oToolbar->addToolbarItem(
					new BAB_ToolbarItem(bab_translate("Search"), $sSearchUserUrl, 
						$sImgPath . 'searchSmall.png', bab_translate("Search"), bab_translate("Search"), 'oSearchImg')
					);
				}
					
			$this->sHtmlToolBarData = $oToolbar->printTemplate();

			}
			
			
		function selectFilteredUsers($pos, $grp)
		{
			global $babDB, $babBody;

			$sOrderBy = '';
			if(isset($pos) &&  mb_strlen($pos) > 0 && $pos[0] == "-" )
			{
				$this->pos = mb_strlen($pos) > 1 ? $pos[1] : '';
				$this->ord = $pos[0];
			 
				$sOrderBy = 'ORDER BY ' . $babDB->db_escape_string($this->namesearch2) . ', ' . $babDB->db_escape_string($this->namesearch) . ' asc';
			}
			else
			{
				$this->pos = $pos;
				$this->ord = "";
			
				$sOrderBy = 'ORDER BY ' . $babDB->db_escape_string($this->namesearch2) . ', ' . $babDB->db_escape_string($this->namesearch) . ' asc';
			}
			
			
			$currentDGGroup = bab_getCurrentDGGroup();
			
			$bUseInnerJoin = !(bab_getCurrentAdmGroup() == 0 || ($this->bupdate && bab_isDelegated('battach') && $this->grp == $currentDGGroup['id_group']));
			
			$aWhereClauseItem	= array();
			$sInnerJoin			= '';

			if($bUseInnerJoin)
			{
				$sInnerJoin = 
					', ' . BAB_USERS_GROUPS_TBL . ' ug' . 
					', ' . BAB_GROUPS_TBL . ' g ';	

				$aWhereClauseItem[] = bab_userInfos::queryAllowedUsers('u');
				$aWhereClauseItem[] = 'ug.id_object = u.id';
				$aWhereClauseItem[] = 'ug.id_group = g.id';
				$aWhereClauseItem[] = 'g.lf >= ' . $babDB->db_escape_string($currentDGGroup['lf']);
				$aWhereClauseItem[] = 'g.lr <= ' . $babDB->db_escape_string($currentDGGroup['lr']);
			}
			
			$sWhereClauseItem = (0 == count($aWhereClauseItem)) ? ' ' : implode(' AND ', $aWhereClauseItem) . ' AND ';
			
			$sQuery = 
				'SELECT ' .
					'DISTINCT u.* ' .
				'FROM ' . 
					BAB_USERS_TBL . ' u ' . 
				$sInnerJoin . ' ' .
				'WHERE ' .
					$sWhereClauseItem .
					'(	' .
						'u.email	 LIKE \'%' . $babDB->db_escape_like($this->sSearchText) . '%\' OR '  .
						'u.nickname	 LIKE \'%' . $babDB->db_escape_like($this->sSearchText) . '%\' OR '  .
						'u.firstname LIKE \'%' . $babDB->db_escape_like($this->sSearchText) . '%\' OR '  .
						'u.lastname	 LIKE \'%' . $babDB->db_escape_like($this->sSearchText) . '%\' ' . 
					') ' .
				$sOrderBy;
			
			//bab_debug($sQuery);
			$this->res = $babDB->db_query($sQuery);
			
			if( !$this->ord == "-" ){
				$this->fullname = bab_toHtml(bab_translate("Lastname") . ' ' . bab_translate("Firstname"));
			}else{
				$this->fullname = bab_toHtml(bab_translate("Firstname") . ' ' . bab_translate("Lastname"));
			}
			$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=users&idx=chg&pos='.
				urlencode($this->ord.$this->pos).'&grp='.urlencode($this->grp).
				'&sSearchText='.urlencode($this->sSearchText));
		}

		
		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				global $babDB;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml( $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".urlencode($this->arr['id'])."&pos=".urlencode($this->ord.$this->pos)."&grp=".urlencode($this->grp));
				if( !$this->ord == "-" )
					$this->urlname = bab_toHtml($this->arr['lastname'].' '.$this->arr['firstname']);
				else
					$this->urlname = bab_toHtml($this->arr['firstname'].' '.$this->arr['lastname']);

				$this->userid = bab_toHtml($this->arr['id']);

				if( isset($this->users_logged[$this->userid]))
					$this->status ="*";
				else
					$this->status ="";

				if( isset($this->group_members[$this->userid]))
					{
					$this->checked = 'checked="checked"';
					if( empty($this->userst))
						$this->userst = $this->arr['id'];
					else
						$this->userst .= ",".$this->arr['id'];
					}
				else
					{
					$this->checked = "";
					}
					
				$today = date('Y-m-d');

				$this->disabled = ($this->arr['disabled'] || 
						(($this->arr['validity_start'] != '0000-00-00' && $today < $this->arr['validity_start'])
						||  ($this->arr['validity_end'] != '0000-00-00' && $today > $this->arr['validity_end']))
				);
				//$this->dirdetailurl = $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$this->iddir."&idu=".$this->arr['idu']."&pos=&xf=";

				$this->dirdetailurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=dirv&id_user=".urlencode($this->userid));
				$this->groupsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=user&idx=viewgroups&id_user=".urlencode($this->userid).'&grp='.urlencode($this->grp).'&pos='.urlencode($this->ord.$this->pos));
				
				$this->usersInfo = array();
				foreach($this->arr as $k => $v){
					$this->usersInfo[$k] = bab_toHtml($v);
				}
				
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $babBody, $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".urlencode($this->ord.$this->selectname)."&grp=".urlencode($this->grp)."&bupd=".urlencode($this->bupdate));
				$this->selected = 0;
				
				if( $this->pos == $this->selectname)
					$this->selected = 1;

				$k++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new temp($pos, $grp, $deleteAction);
	$babBody->babecho(	bab_printTemplate($temp, "users.html", "userslist"));
	return $temp->count;
	}

	
function deleteUsers($users)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($users)
			{
			global $BAB_SESS_USERID;
			$pos = bab_rp('pos', '');
			$grp = bab_rp('grp', '');
			
			$this->warning = bab_translate("WARNING: This operation will remove users and their references"). '!';
			$this->title = "";
			$names = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($users); $i++)
				{
				$req = 'select * from '.BAB_USERS_TBL.' where id='.$db->quote($users[$i]);	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". bab_composeUserName($arr['firstname'], $arr['lastname']);
					$names .= $arr['id'];
					}
				if( $i < count($users) -1)
					$names .= ",";
				}
			$this->message = bab_translate("Are you sure you want to continue");
			$this->urlyes = $GLOBALS['babUrlScript'].'?tg=users&idx=Deleteu&pos='.$pos.'&action=Yes&grp='.$grp.'&names='.$names;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript'].'?tg=users&idx=List&pos='.$pos.'&action=Yes&grp='.$grp;
			$this->no = bab_translate("No");
			}
		}
	if( count($users) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		return false;
		}
	
	$tempa = new tempa($users);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	return true;
	}

	
	
	
function userCreate()
{
	require_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';
	$list = bab_url::get_request('grp');
	$list->tg = 'users';
	
	$usereditor = bab_functionality::get('UserEditor');
	$usereditor->getAsPage(null, $list)->displayHtml();
}


function utilit($grp = '')
	{
	global $babBody;
	class temp
		{

		function temp($grp)
			{
			global $babDB;
			$this->t_nb_total_users = bab_translate('Total users');
			$this->t_nb_unconfirmed_users = bab_translate('Unconfirmed users');
			$this->t_delete_unconfirmed = bab_translate('Delete unconfirmed users from');
			$this->t_delete_since = bab_translate('Delete all users created since');
			$this->t_days = bab_translate('Days');
			$this->t_ok = bab_translate('Ok');
			$this->js_alert = bab_translate('Day number must be 1 at least');
			
			$this->grp = bab_toHtml($grp);
			
			$this->today = bab_toHtml(date('d/m/Y'));
			
			list($this->arr['nb_total_users']) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_USERS_TBL.""));

			list($this->arr['nb_unconfirmed_users']) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_USERS_TBL." WHERE is_confirmed='0'"));
			}
		}

	$temp = new temp($grp);
	$babBody->babecho(bab_printTemplate($temp, 'users.html', 'utilit'));
	}
	
function delete_unconfirmed()
	{
	include $GLOBALS['babInstallPath']."utilit/delincl.php";
	global $babDB;
	$res = $babDB->db_query("SELECT id FROM ".BAB_USERS_TBL." WHERE is_confirmed='0' AND (DATE_ADD(datelog, INTERVAL ".$babDB->db_escape_string($_POST['nb_days'])." DAY) < NOW() OR datelog = '0000-00-00 00:00:00')");
	while (list($id) = $babDB->db_fetch_array($res))
		bab_deleteUser($id);
	}
	
	
function delete_since()
	{
		
	$creation_date = explode('/', bab_pp('creation_date'));
	
	if (3 !== count($creation_date)) {
		return false;
	}
	
	$isodate = $creation_date[2].'-'.$creation_date[1].'-'.$creation_date[0];
		
	include $GLOBALS['babInstallPath']."utilit/delincl.php";
	global $babDB;
	$res = $babDB->db_query("SELECT id FROM ".BAB_USERS_TBL." WHERE `date`>=".$babDB->quote($isodate));
	while (list($id) = $babDB->db_fetch_array($res))
		bab_deleteUser($id);
	}
	
	

function updateGroup( $grp, $users, $userst)
{
	global $babBody;
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";

	$id_parent = false;

	if( bab_getCurrentAdmGroup() )
		{
		$currentDGGroup = bab_getCurrentDGGroup();
		$id_parent = $currentDGGroup['id_group'];
		}
	elseif( bab_isUserAdministrator() )
		{
		$id_parent = BAB_REGISTERED_GROUP;
		}

	if( false === $id_parent  || false === bab_isGroup($grp, $id_parent, false) )
	{
		return;
	}

	if( !empty($userst))
		$tab = explode(",", $userst);
	else
		$tab = array();

	for( $i = 0; $i < count($tab); $i++)
	{
		if( count($users) < 1 || !in_array($tab[$i], $users))
		{
			bab_removeUserFromGroup($tab[$i], $grp);
		}
	}
	for( $i = 0; $i < count($users); $i++)
	{
		if( count($tab) < 1 || !in_array($users[$i], $tab))
		{
			bab_addUserToGroup($users[$i], $grp);
		}
	}
}



function dir_view($id_user)
{
global $babDB;

$arr = $babDB->db_fetch_array($babDB->db_query("SELECT dbt.id FROM ".BAB_DBDIR_ENTRIES_TBL." dbt WHERE '".$babDB->db_escape_string($id_user)."'=dbt.id_user and dbt.id_directory='0'"));

list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));

Header("Location: ". $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$iddir."&idu=".$arr['id']."&pos=&xf=");

}

function confirmDeleteUsers($names)
{
	global $babBody, $babDB;
	
	if( !empty($names) && bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0)
	{
		include_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
		$arr = explode(",", $names);
		$cnt = count($arr);
		for($i = 0; $i < $cnt; $i++)
			{
			bab_deleteUser($arr[$i]);
			}
	}
}

/* main */

$pos = bab_rp('pos','A');
$grp = bab_rp('grp');
$idx = bab_rp('idx','List');

$displayDeleteAction = false;
if( !isset($grp) || empty($grp))
{
	$displayMembersItemMenu = false;
	if( bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0 )
	{
		$displayDeleteAction = true;
		$grp = '';//BAB_ADMINISTRATOR_GROUP;
	}
	else if( bab_getCurrentAdmGroup() != 0 )
	{
		$currentDGGroup = bab_getCurrentDGGroup();
		$grp = $currentDGGroup['id_group'];
	}
} else {
	$displayMembersItemMenu = true;
}



if( mb_strlen($pos) > 0 && $pos[0] == "-" ){
	$babBody->nameorder[0] = 'F';
	$babBody->nameorder[1] = 'L';
} else {
	$babBody->nameorder[0] = 'L';
	$babBody->nameorder[1] = 'F';
}

if( $idx == "chg")
{
	if( mb_strlen($pos) > 0 && $pos[0] == "-" ){
		$pos = mb_strlen($pos)>1? $pos[1]: '';
		$babBody->nameorder[0] = 'L';
		$babBody->nameorder[1] = 'F';
	} else {
		$pos = "-" .$pos;
		$babBody->nameorder[0] = 'F';
		$babBody->nameorder[1] = 'L';
	}
	$idx = "List";
}

if( isset($Updateg) && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0 ))
{
	$users = isset($_POST['users']) ? $_POST['users'] : array();
	updateGroup($_POST['grp'], $users, $_POST['userst']);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp."&bupd=".$_REQUEST['bupd']);
	exit;
}
elseif(isset($Deleteg) && bab_isUserAdministrator())
{
	$idx = 'deletem';
}

if( isset($action) && $action == 'Yes')
	{
	if($idx == "Deleteu" && bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0)
		{
		confirmDeleteUsers($names);
		Header('Location: '. $GLOBALS['babUrlScript'].'?tg=users&idx=List&pos='.$pos.'&grp='.$grp);
		exit;
		}
	}

if( $idx == "Create" && !bab_isUserAdministrator() && !bab_isDelegated('users'))
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($aclnotif))
{
	require_once $babInstallPath."admin/acl.php";
	maclGroups();
}

switch($idx)
	{
	case "dirv": 

		dir_view($_GET['id_user']);
		exit;
		break;

	case "brow": // Used by add-ons but deprecated
		if( bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0 )
			{
			browseUsers($pos, $cb);
			}
		else
			{
			echo bab_translate("Access denied");
			}
		exit;
		break;
	case "Create":
		$babBody->title = bab_translate("Create a user");

		userCreate();
		if ($displayMembersItemMenu)
			{
			$babBody->addItemMenu("Members", bab_translate("Members"),$GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".bab_rp('grp'));
			}
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".bab_rp('grp'));
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create&pos=".bab_rp('grp'));
		break;
		
	case 'deletem':
		if( bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0 )
			{
			$babBody->setTitle(bab_translate("Delete users"));
			
			if ($displayMembersItemMenu)
				{
				$babBody->addItemMenu("Members", bab_translate("Members"),$GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".bab_rp('grp'));
				}
			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".bab_rp('grp'));
			if( bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0 )
				{
					$users = isset($_POST['users']) ? $_POST['users'] : array();
					deleteUsers($users);
				}
			

			if( bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0 )
				{
				$babBody->addItemMenu("utilit", bab_translate("Utilities"), $GLOBALS['babUrlScript']."?tg=users&idx=utilit&grp=".bab_rp('grp'));
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;
			
	case "List":
		if( bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0 )
			{
			$babBody->setTitle(bab_translate("Users list"));
			$cnt = listUsers($pos, $grp, $displayDeleteAction);
			
			if ($displayMembersItemMenu)
				{
				$babBody->addItemMenu("Members", bab_translate("Members"),$GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".bab_rp('grp'));
				}
			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".bab_rp('grp'));
			

			if( bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0 )
				{
				$babBody->addItemMenu("utilit", bab_translate("Utilities"), $GLOBALS['babUrlScript']."?tg=users&idx=utilit&grp=".bab_rp('grp'));
				$babBody->addItemMenu("notif", bab_translate("Notices"),$GLOBALS['babUrlScript']."?tg=users&idx=notif");
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;

		case "notif":
			if (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0)
			{
				require_once $babInstallPath."admin/acl.php";
				
				$babBody->title = bab_translate("Notices");
				$macl = new macl("users", "notif", 1, "aclnotif");
		        $macl->addtable(BAB_LDAP_LOGGIN_NOTIFY_GROUPS_TBL, bab_translate("Who is notified when an account is created when a user first loggin on a LDAP directory?"));
				$macl->filter(0,0,0,1,0);
		        $macl->babecho();
				
				$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".bab_rp('grp'));
				$babBody->addItemMenu("notif", bab_translate("Notices"),$GLOBALS['babUrlScript']."?tg=users&idx=notif");
			}
			break;
			
	case "utilit":
		if (bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0)
			{
			if (isset($_POST['action']) && $_POST['action'] == 'delete_unconfirmed')
				{
				delete_unconfirmed();
				}
				
			if (isset($_POST['action']) && $_POST['action'] == 'delete_since')
				{
				delete_since();
				}
				
			if ($displayMembersItemMenu)
				{
				$babBody->addItemMenu("Members", bab_translate("Members"),$GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".bab_rp('grp'));
				}
			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".bab_rp('grp'));
			$babBody->addItemMenu("utilit", bab_translate("Utilities"), $GLOBALS['babUrlScript']."?tg=users&idx=utilit&grp=".bab_rp('grp'));
			$babBody->setTitle(bab_translate("Utilities"));
			utilit($grp);
			}
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminUsers');
?>
