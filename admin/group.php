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
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath."utilit/grpincl.php";
include_once $babInstallPath."utilit/fileincl.php";
include_once $babInstallPath."utilit/grptreeincl.php";


function groupMembers($id)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $url;
		var $urlname;
		var $idgroup;
		var $group;
		var $grpid;
		var $primary;
		var $deletealt;
			
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $bmodname;
		var $bdel;
		var $altbg = true;

		function temp($id)
			{
			global $babBody, $babDB;

			$this->grpid = $id;
			$this->t_lastname = bab_translate("Lastname");
			$this->t_firstname = bab_translate("Firstname");
			$this->t_upgrade = bab_translate("Ok");
			$this->t_delete = bab_translate("Delete");
			$this->deletealt = bab_translate("Delete group's members");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->withselected = bab_translate("With selected");
			$this->deleteonly = bab_translate("Delete from group only");
			$this->copytogroup = bab_translate("Copy to group");
			$this->movetogroup = bab_translate("Move to group");
			$this->idgroup = $id;
			
			$this->db = &$GLOBALS['babDB'];

			$req = "select ut.*, ugt.isprimary from ".BAB_USERS_TBL." ut left join ".BAB_USERS_GROUPS_TBL." ugt on ut.id=ugt.id_object where ugt.id_group= ".$babDB->quote($id)." order by ut.lastname asc";
			
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( bab_getCurrentAdmGroup() == 0)
				{
				$this->bmodname = true;
				}
			else
				{
				$this->bmodname = false;
				}
				
				
			$this->bshowform = true;

			if( bab_getCurrentAdmGroup() != 0)
			{
				$currentDGGroup = bab_getCurrentDGGroup();
				
				$this->bshowform = (bab_isDelegated('battach') 
					&& ($id == $currentDGGroup['id_group'] || false !== bab_groupIsChildOf($currentDGGroup['id_group'], $id))
				);
			}
			
				
				
			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_REGISTERED_GROUP);
			if (isset($this->groups[$this->idgroup]))
				{
				unset($this->groups[$this->idgroup]);
				}

			$this->w1selected = $this->w2selected = $this->w3selected = '';
			$wchoice = bab_rp('wchoice', 1);
			switch($wchoice)
				{
					case 2:
						$this->w2selected = 'selected';
						break;	
					case 3:
						$this->w3selected = 'selected';
						break;	
					case 1:
					default:
						$this->w1selected = 'selected';
						break;	
				}
			$this->idgroup = bab_rp('id_group', '');
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$this->arr['id'];
				$this->urlname = bab_composeUserName($this->arr['firstname'], $this->arr['lastname']);
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextgroup()
			{
			if (list($this->id, $arr) = each($this->groups))
				{
				$this->name = bab_toHtml($arr['name']);
				if( $this->idgroup == $this->id)
				{
					$this->groupselected = 'selected';
				}
				else
				{
					$this->groupselected = '';
				}
				return true;
				}
			else
				{
				return false;
				}
			}			
			
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "groups.html", "memberslist"));
	}



function deleteMembers($users, $item)
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

		function tempa($users, $item)
			{
			global $BAB_SESS_USERID;
			$wchoice = bab_rp('wchoice', 1);
			$id_group = bab_rp('id_group', '');
			$name_group = empty($id_group)? '': bab_getGroupName($id_group);
			switch($wchoice)
			{
				case 2:
					$this->warning = bab_translate("WARNING: This operation will attach those users to group ").'['.$name_group. ']!';
					break;
				case 3:
					$this->warning = bab_translate("WARNING: This operation will move those users to group ").'['.$name_group. ']!';
					break;
				case 1:
				default:
					$this->warning = bab_translate("WARNING: This operation will delete members and their references"). '!';
					break;
					
			}
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
			$this->urlyes = $GLOBALS['babUrlScript'].'?tg=group&idx=Deletem&item='.$item.'&action=Yes&names='.$names.'&wchoice='.$wchoice.'&idgroup='.$id_group;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript'].'?tg=groups';
			$this->no = bab_translate("No");
			}
		}
	if( count($users) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		return false;
		}
	$wchoice = bab_rp('wchoice', 1);
	$id_group = bab_rp('id_group', '');
	if( $wchoice != 1 && empty($id_group))
	{
		$babBody->msgerror = bab_translate("You must select one group");
		return false;
	}
	
	$tempa = new tempa($users, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	return true;
	}

function confirmDeleteMembers($item, $names)
{
	global $babBody;
	
	if( bab_getCurrentAdmGroup() != 0)
	{
		if (!bab_isDelegated('battach'))
		{
			// si la delegation courrante n'a pas le droit d'attacher / detacher des users
			throw new ErrorException('Members modification is not allowed on this delegation');
		}
		
		$currentDGGroup = bab_getCurrentDGGroup();
		
		if (!($item == $currentDGGroup['id_group'] || false !== bab_groupIsChildOf($currentDGGroup['id_group'], $item)))
		{
			// si le groupe qu'on essai de modifier n'est pas dans la branche de delegation
			throw new ErrorException('Members modification is not allowed on this group');
		}
	}

	$wchoice = bab_rp('wchoice', '');
	$id_group = bab_rp('idgroup', '');
	
	if (empty($wchoice))
	{
		throw new ErrorException('missing action choice');
	}
	
	if (empty($names))
	{
		throw new ErrorException('missing names');
	}
	
	
	$arr = explode(",", $names);
	$cnt = count($arr);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		if( $wchoice == 1 ) // delete
			{
			bab_removeUserFromGroup($arr[$i], $item);
			}
		elseif($wchoice == 2) // copy
			{
				bab_addUserToGroup($arr[$i], $id_group);
			}
		elseif($wchoice == 3) // move
			{
				bab_removeUserFromGroup($arr[$i], $item);
				bab_addUserToGroup($arr[$i], $id_group);
			}
		}

	return true;
}

function confirmDeleteGroup($id)
	{
	if( $id <= 3)
		return;

	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteGroup($id);
	}


/* main */
if( !bab_isUserAdministrator() && bab_getCurrentAdmGroup() == 0 )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "Members";

if( isset($action) && $action == "Yes")
	{
	if($idx == "Deletem")
		{
		if (confirmDeleteMembers($item, $names))
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
			exit;
			}
		}
	}
elseif(isset($action) && $action=="DeleteG")
{
	if( isset($byes))
	{
		$dgwhat = bab_pp('dgwhat', 0);
		$idgroup = bab_pp('idgroup', '');
		$bdelself = false;
		if( !empty($idgroup))
		{
			switch($dgwhat)
			{
				case 0: //delete only this group
					$bdelself = true;
					break;
				case 1: // delete this group with all childs
					$bdelself = true;
					$arrgrp = bab_getGroups($idgroup, true);
					break;
				case 2: // delete only childs
					$bdelself = false;
					$arrgrp = bab_getGroups($idgroup, true);
					break;
				case 3: // delete only childs of the first level
					$bdelself = false;
					$arrgrp = bab_getGroups($idgroup, false);
					break;
			}
		}
	}
	if( $bdelself)
	{
		confirmDeleteGroup($idgroup);
	}
	if( isset($arrgrp['id']) && count($arrgrp['id']))
	{
		//print_r($arrgrp);
		for($k=0; $k < count($arrgrp['id']); $k++)
		{
			confirmDeleteGroup($arrgrp['id'][$k]);
		}
			
	}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List");
	exit;
}

switch($idx)
	{
	case "deldg":
		if( $item > 3 )
			groupAdmDelete($item);
		$babBody->title = bab_translate("Delete group");
		if( bab_getCurrentAdmGroup() == 0 || bab_isDelegated('groups') )
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		$babBody->addItemMenu("deldg", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=group&idx=deldg&item=".$item);
		break;
	case "Deletem":
			if(!isset($users)) { $users = array();}
			if(deleteMembers($users, $item))
			{
			$babBody->title = bab_translate("Delete group's members");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
			$babBody->addItemMenu("Deletem", bab_translate("Delete"), "");
			break;
			}
		/* no break */
	case "Members":
	default:
		groupMembers($item);
		$babBody->title = bab_translate("Group's members").' : '.bab_getGroupName($item);
		if( bab_getCurrentAdmGroup() == 0 || bab_isDelegated('groups') )
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		
		$currentDGGroup = bab_getCurrentDGGroup();
		if( bab_getCurrentAdmGroup() == 0 || bab_isDelegated('battach') || $item != $currentDGGroup['id_group'] )
		{
			$babBody->addItemMenu("Add", bab_translate("Attach"), $GLOBALS['babUrlScript']."?tg=users&idx=List&grp=".$item."&bupd=1");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
