<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

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
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->groupsname = bab_translate("Groups");
			$this->bgcolor = bab_translate("Color");
			$this->add = bab_translate("Add Category");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->count = count($grpid);
			$this->db = $GLOBALS['babDB'];
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_GROUPS_TBL." where id='".$this->idgrp[$i]."'";
				$res = $this->db->db_query($req);
				$this->arrgroups = $this->db->db_fetch_array($res);
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
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->groupsname = bab_translate("Groups");
			$this->add = bab_translate("Add Resource");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->count = count($grpid);
			$this->db = $GLOBALS['babDB'];
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from ".BAB_GROUPS_TBL." where id='".$this->idgrp[$i]."'";
				$res = $this->db->db_query($req);
				$this->arrgroups = $this->db->db_fetch_array($res);

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

		var $userid;

		function temp($grpid, $userid)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->bgcolor = bab_translate("Color");
			$this->group = bab_translate("Group");
			$this->idgrp = $grpid;
			$this->count = count($grpid);
			$this->db = $GLOBALS['babDB'];
			$this->userid = $userid;
			$req = "select * from ".BAB_CATEGORIESCAL_TBL."";
			$this->res = $this->db->db_query($req);
			$this->countcal = $this->db->db_num_rows($this->res);
			}
			
		function getnext()
			{
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
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

		function temp($grpid, $userid)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->group = bab_translate("Group");
			$this->idgrp = $grpid;
			$this->count = count($grpid);
			$this->db = $GLOBALS['babDB'];
			$this->userid = $userid;
			$req = "select * from ".BAB_RESOURCESCAL_TBL."";
			$this->res = $this->db->db_query($req);
			$this->countcal = $this->db->db_num_rows($this->res);
			}
		
			
		function getnext()
			{
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
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
	global $babBody;
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
	$db = $GLOBALS['babDB'];

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from ".BAB_CATEGORIESCAL_TBL." where name='$name' and id_group='".$groups[$i]."'";	
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			//$babBody->msgerror = bab_translate("ERROR: This category already exists");
			}
		else
			{
			$query = "insert into ".BAB_CATEGORIESCAL_TBL." (name, id_group, description, bgcolor) VALUES ('" .$name. "', '" . $groups[$i]. "', '" . $description. "', '" . $bgcolor. "')";
			$db->db_query($query);
			}
		}
	}

function addCalResource($groups, $name, $description)
	{
	global $babBody;
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

	$db = $GLOBALS['babDB'];

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from ".BAB_RESOURCESCAL_TBL." where name='$name' and id_group='".$groups[$i]."'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) > 0)
			{
			//$babBody->msgerror = bab_translate("This resource already exists");
			}
		else
			{
			$query = "insert into ".BAB_RESOURCESCAL_TBL." (name, description, id_group) VALUES ('" .$name. "', '" . $description. "', '" . $groups[$i]. "')";
			$db->db_query($query);
			$id = $db->db_insert_id();

			$query = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$id. "', 'Y', '3')";
			$db->db_query($query);
			
			}
		}
	}

/* main */
if( !isset($idx))
	$idx = "listcat";

if( isset($addcat) && $addcat == "add")
	addCalCategory($groups, $name, $description, $bgcolor);

if( isset($addres) && $addres == "add")
	addCalResource($groups, $name, $description);

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
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_GROUPS_TBL." where manager='".$userid."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		while( $arr = $db->db_fetch_array($res))
			array_push($grpid, $arr['id']);
		}
	else
		{
		return;
		}
	}

switch($idx)
	{
	case "createcat":
		categoryCreate($userid, $grpid);
		$babBody->title = bab_translate("Create a calendar category");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("createcat", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	case "createres":
		resourceCreate($userid, $grpid);
		$babBody->title = bab_translate("Create Calendar resource");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("createres", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createres&userid=".$userid);
		break;
	case "listres":
		resourcesList($grpid, $userid);
		$babBody->title = bab_translate("Calendar resources list");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("createres", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createres&userid=".$userid);
		break;
	case "listcat":
	default:
		categoriesList($grpid, $userid);
		$babBody->title = bab_translate("Calendar categories list");
		$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("createcat", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=confcals&idx=createcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>