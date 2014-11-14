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
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';

function categoryCreate($userid, $grpid)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $bgcolor;
		var $groupsname;
		var $idgrp;
		var $count;
		var $add;
		var $db;
		var $arrgroups = array();
		var $userid;

		function temp($userid, $grpid)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->groupsname = bab_translate("Groups");
			$this->bgcolor = bab_translate("Color");
			$this->add = bab_translate("Add Category");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->count = count($grpid);
			}

		function getnextgroup()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_GROUPS_TBL." where id=".$babDB->quote($this->idgrp[$i]);
				$res = $babDB->db_query($req);
				$this->arrgroups = $babDB->db_fetch_array($res);
				if( $i == 0 )
					$this->arrgroups['select'] = "selected";
				else
					$this->arrgroups['select'] = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($userid, $grpid);
	$babBody->babecho(	bab_printTemplate($temp,"confcals.html", "categorycreate"));
	}

function resourceCreate($userid, $grpid)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $grpid;
		var $add;
		var $groupsname;
		var $idgrp;
		var $db;
		var $count;
		var $arrgroups = array();
		var $userid;

		function temp($userid, $grpid)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->groupsname = bab_translate("Groups");
			$this->add = bab_translate("Add Resource");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->count = count($grpid);
			}

		function getnextgroup()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_GROUPS_TBL." where id=".$babDB->quote($this->idgrp[$i]);
				$res = $babDB->db_query($req);
				$this->arrgroups = $babDB->db_fetch_array($res);

				if( $i == 0 )
					$this->arrgroups['select'] = "selected";
				else
					$this->arrgroups['select'] = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($userid, $grpid);
	$babBody->babecho(	bab_printTemplate($temp,"confcals.html", "resourcecreate"));
	}

function categoriesList($grpid, $userid)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $bgcolor;
		var $idgrp = array();
		var $group;
		var $groupname;
				
		var $arr = array();
		var $db;
		var $count;
		var $countcal;
		var $res;
		var $altbg = true;
		var $userid;

		function temp($grpid, $userid)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->bgcolor = bab_translate("Color");
			$this->group = bab_translate("Group");
			$this->idgrp = $grpid;
			$this->count = count($grpid);
			$this->userid = $userid;
			$req = "select * from ".BAB_CATEGORIESCAL_TBL."";
			$this->res = $babDB->db_query($req);
			$this->countcal = $babDB->db_num_rows($this->res);
			}
			
		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->groupname = bab_getGroupName($this->arr['id_group']);
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['id_group'])
						{
						$this->burl = 1;
						break;
						}
					}

				$this->url = $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$this->arr['id']."&userid=".$this->userid;
				$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	$temp = new temp($grpid, $userid);
	$babBody->babecho(	bab_printTemplate($temp, "confcals.html", "categorieslist"));
	}

function resourcesList($grpid, $userid)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $idgrp;
		var $group;
		var $groupname;
				
		var $arr = array();
		var $db;
		var $count;
		var $countcal;
		var $res;

		var $userid;
		var $calrescheck;
		var $resid;
		var $disabled;
		var $update;
		var $altbg = true;

		function temp($grpid, $userid)
			{
			global $babDB;
			$this->disabled = bab_translate("Disabled");
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->group = bab_translate("Group");
			$this->update = bab_translate("Update");
			$this->idgrp = $grpid;
			$this->count = count($grpid);
			$this->userid = $userid;
			$this->res = $babDB->db_query("select * from ".BAB_RESOURCESCAL_TBL."");
			$this->countcal = $babDB->db_num_rows($this->res);
			}
		
			
		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['id_group'])
						{
						$this->burl = 1;
						break;
						}
					}
				$this->groupname = bab_getGroupName($this->arr['id_group']);
				$this->url = $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$this->arr['id']."&userid=".$this->userid;
				$this->urlname = $this->arr['name'];
				$arr = $babDB->db_fetch_array($babDB->db_query("select id, actif from ".BAB_CALENDAR_TBL." where owner='".$babDB->db_escape_string($this->arr['id'])."' and type='3'"));
				$this->resid = $this->arr['id'];
				if( $arr['actif'] == 'N' )
					$this->calrescheck = "checked";
				else
					$this->calrescheck = "";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	$temp = new temp($grpid, $userid);
	$babBody->babecho(	bab_printTemplate($temp, "confcals.html", "resourceslist"));
	}

function addCalCategory($groups, $name, $description, $bgcolor)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name"). " !!";
		return;
		}

	$count = count ( $groups );
	if( $count == 0 )
		{
		$babBody->msgerror = bab_translate("You must select at least one group"). " !!";
		return;
		}

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from ".BAB_CATEGORIESCAL_TBL." where name='".$babDB->db_escape_string($name)."' and id_group='".$babDB->db_escape_string($groups[$i])."'";	
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			//$babBody->msgerror = bab_translate("ERROR: This category already exists");
			}
		else
			{
			$query = "insert into ".BAB_CATEGORIESCAL_TBL." (name, id_group, description, bgcolor) VALUES ('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($groups[$i]). "', '" . $babDB->db_escape_string($description). "', '" . $babDB->db_escape_string($bgcolor). "')";
			$babDB->db_query($query);
			}
		}
	}

function addCalResource($groups, $name, $description)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name"). " !!";
		return;
		}
	$count = count ( $groups );
	if( $count == 0 )
		{
		$babBody->msgerror = bab_translate("You must select at least one group"). " !!";
		return;
		}

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from ".BAB_RESOURCESCAL_TBL." where name='".$babDB->db_escape_string($name)."' and id_group='".$babDB->db_escape_string($groups[$i])."'";	
		$res = $babDB->db_query($query);
		if( $babDB->db_num_rows($res) > 0)
			{
			//$babBody->msgerror = bab_translate("This resource already exists");
			}
		else
			{
			$query = "insert into ".BAB_RESOURCESCAL_TBL." (name, description, id_group) VALUES ('" .$babDB->db_escape_string($name). "', '" . $babDB->db_escape_string($description). "', '" . $babDB->db_escape_string($groups[$i]). "')";
			$babDB->db_query($query);
			$id = $babDB->db_insert_id();

			$query = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$babDB->db_escape_string($id). "', 'Y', '3')";
			$babDB->db_query($query);
			
			}
		}
	}

function disableCalResource($resids, $userid, $grpid)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_RESOURCESCAL_TBL);
	while($arr = $babDB->db_fetch_array($res))
	{
		for( $k = 0; $k < count($grpid); $k++)
		{
			if( $grpid[$k] == $arr['id_group'])
				{
				if( count($resids) > 0 && in_array($arr['id'], $resids))
					$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='N' where owner='".$babDB->db_escape_string($arr['id'])."' and type='3'");
				else
					$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='Y' where owner='".$babDB->db_escape_string($arr['id'])."' and type='3'");
				break;
				}
		}
	}

}

/* main */
if( !isset($idx))
	$idx = "listcat";

$grpid = array();
if( $userid == 0 )
	{
	if( !bab_isUserAdministrator())
		{
		return;
		}
	array_push($grpid, 1);
	}
else
	{
	$req = "select * from ".BAB_GROUPS_TBL." where manager='".$babDB->db_escape_string($userid)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		while( $arr = $babDB->db_fetch_array($res))
			array_push($grpid, $arr['id']);
		}
	else
		{
		return;
		}
	}

if( isset($addcat) && $addcat == "add")
	addCalCategory($groups, $name, $description, $bgcolor);
else if( isset($addres) && $addres == "add")
	addCalResource($groups, $name, $description);
else if( isset($update) && $update == "disable")
	disableCalResource($resids, $userid, $grpid);


switch($idx)
	{
	case "createcat":
		categoryCreate($userid, $grpid);
		$babBody->title = bab_translate("Create event category");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("createcat", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	case "createres":
		resourceCreate($userid, $grpid);
		$babBody->title = bab_translate("Create Calendar resource");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("createres", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createres&userid=".$userid);
		break;
	case "listres":
		resourcesList($grpid, $userid);
		$babBody->title = bab_translate("Calendar resources list");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("createres", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createres&userid=".$userid);
		break;
	case "listcat":
	default:
		categoriesList($grpid, $userid);
		$babBody->title = bab_translate("Event categories list");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("createcat", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>