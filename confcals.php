<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function categoryCreate($userid, $grpid)
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->groupsname = babTranslate("Groups");
			$this->bgcolor = babTranslate("Color");
			$this->add = babTranslate("Add Category");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->count = count($grpid);
			$this->db = new db_mysql();
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from groups where id='".$this->idgrp[$i]."'";
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
	$body->babecho(	babPrintTemplate($temp,"confcals.html", "categorycreate"));
	}

function resourceCreate($userid, $grpid)
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->groupsname = babTranslate("Groups");
			$this->add = babTranslate("Add Resource");
			$this->idgrp = $grpid;
			$this->userid = $userid;
			$this->count = count($grpid);
			$this->db = new db_mysql();
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$req = "select * from groups where id='".$this->idgrp[$i]."'";
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
	$body->babecho(	babPrintTemplate($temp,"confcals.html", "resourcecreate"));
	}

function categoriesList($grpid, $userid)
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->bgcolor = babTranslate("Color");
			$this->group = babTranslate("Group");
			$this->idgrp = $grpid;
			$this->count = count($grpid);
			$this->db = new db_mysql();
			$this->userid = $userid;
			$req = "select * from categoriescal";
			$this->res = $this->db->db_query($req);
			$this->countcal = $this->db->db_num_rows($this->res);
			}
			
		function getnext()
			{
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->groupname = getGroupName($this->arr['id_group']);
				$this->burl = 0;
				for( $k = 0; $k < $this->count; $k++)
					{
					if( $this->idgrp[$k] == $this->arr['id_group'])
						{
						$this->burl = 1;
						break;
						}
					}

				$this->url = $GLOBALS['babUrl']."index.php?tg=confcal&idx=modifycat&item=".$this->arr['id']."&userid=".$this->userid;
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
	$body->babecho(	babPrintTemplate($temp, "confcals.html", "categorieslist"));
	}

function resourcesList($grpid, $userid)
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->group = babTranslate("Group");
			$this->idgrp = $grpid;
			$this->count = count($grpid);
			$this->db = new db_mysql();
			$this->userid = $userid;
			$req = "select * from resourcescal";
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
				$this->groupname = getGroupName($this->arr['id_group']);
				$this->url = $GLOBALS['babUrl']."index.php?tg=confcal&idx=modifyres&item=".$this->arr['id']."&userid=".$this->userid;
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
	$body->babecho(	babPrintTemplate($temp, "confcals.html", "resourceslist"));
	}

function addCalCategory($groups, $name, $description, $bgcolor)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name"). " !!";
		return;
		}

	$count = count ( $groups );
	if( $count == 0 )
		{
		$body->msgerror = babTranslate("You must select at least one group"). " !!";
		return;
		}
	$db = new db_mysql();

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from categoriescal where name='$name' and id_group='".$groups[$i]."'";	
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			//$body->msgerror = babTranslate("ERROR: This category already exists");
			}
		else
			{
			$query = "insert into categoriescal (name, id_group, description, bgcolor) VALUES ('" .$name. "', '" . $groups[$i]. "', '" . $description. "', '" . $bgcolor. "')";
			$db->db_query($query);
			}
		}
	}

function addCalResource($groups, $name, $description)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name"). " !!";
		return;
		}
	$count = count ( $groups );
	if( $count == 0 )
		{
		$body->msgerror = babTranslate("You must select at least one group"). " !!";
		return;
		}

	$db = new db_mysql();

	for( $i = 0; $i < $count; $i++)
		{		
		$query = "select * from resourcescal where name='$name' and id_group='".$groups[$i]."'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) > 0)
			{
			//$body->msgerror = babTranslate("This resource already exists");
			}
		else
			{
			$query = "insert into resourcescal (name, description, id_group) VALUES ('" .$name. "', '" . $description. "', '" . $groups[$i]. "')";
			$db->db_query($query);
			$id = $db->db_insert_id();

			$query = "insert into calendar (owner, actif, type) VALUES ('" .$id. "', 'Y', '3')";
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
	if( !isUserAdministrator())
		{
		return;
		}
	array_push($grpid, 1);
	}
else
	{
	$db = new db_mysql();
	$req = "select * from groups where manager='".$userid."'";
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
		$body->title = babTranslate("Create a calendar category");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("createcat", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=createcat&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listres&userid=".$userid);
		break;
	case "createres":
		resourceCreate($userid, $grpid);
		$body->title = babTranslate("Create Calendar resource");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listres&userid=".$userid);
		$body->addItemMenu("createres", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=createres&userid=".$userid);
		break;
	case "listres":
		resourcesList($grpid, $userid);
		$body->title = babTranslate("Calendar resources list");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listres&userid=".$userid);
		$body->addItemMenu("createres", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=createres&userid=".$userid);
		break;
	case "listcat":
	default:
		categoriesList($grpid, $userid);
		$body->title = babTranslate("Calendar categories list");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listcat&userid=".$userid);
		$body->addItemMenu("createcat", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=createcat&userid=".$userid);
		$body->addItemMenu("listres", babTranslate("Resources"), $GLOBALS['babUrl']."index.php?tg=confcals&idx=listres&userid=".$userid);
		break;
	}

$body->setCurrentItemMenu($idx);

?>