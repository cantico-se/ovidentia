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

function bab_getCategoryCalName($id)
	{
	global $babDB;
	$query = "select name from ".BAB_CATEGORIESCAL_TBL." where id=".$babDB->quote($id);
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function bab_getResourceCalName($id)
	{
	global $babDB;
	$query = "select name from ".BAB_RESOURCESCAL_TBL." where id=".$babDB->quote($id);
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function categoryCalModify($userid, $id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("You must choose a valid category !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $bgcolor;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $userid;
		var $delete;

		function temp($userid, $id)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->bgcolor = bab_translate("Color");
			$this->modify = bab_translate("Modify Category");
			$this->delete = bab_translate("Delete");
			$req = "select * from ".BAB_CATEGORIESCAL_TBL." where id=".$babDB->quote($id);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id);
	$babBody->babecho(	bab_printTemplate($temp,"confcals.html", "categorycalmodify"));
	}

function resourceCalModify($userid, $id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid resource !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $delete;

		function temp($userid, $id)
			{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->modify = bab_translate("Modify Resource");
			$this->delete = bab_translate("Delete");
			$req = "select * from ".BAB_RESOURCESCAL_TBL." where id=".$babDB->quote($id);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->userid = $userid;
			}
		}

	$temp = new temp($userid, $id);
	$babBody->babecho(	bab_printTemplate($temp,"confcals.html", "resourcecalmodify"));
	}

function categoryCalDelete($userid, $id)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($userid,$id)
			{
			$this->message = bab_translate("Are you sure you want to delete this event category");
			$this->title = bab_getCategoryCalName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the category with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=confcal&idx=delcat&category=".$id."&action=Yes&userid=".$userid;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$id."&userid=".$userid;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($userid,$id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function resourceCalDelete($userid, $id)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($userid,$id)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar resource");
			$this->title = bab_getResourceCalName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the resource with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=confcal&idx=delres&resource=".$id."&action=Yes&userid=".$userid;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$id."&userid=".$userid;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($userid,$id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyCategoryCal($userid, $oldname, $name, $description, $bgcolor, $id)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name !!");
		return;
		}

	$query = "select * from ".BAB_CATEGORIESCAL_TBL." where name='".$babDB->db_escape_string($oldname)."'";	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("The category doesn't exist");
		}
	else
		{

		$query = "UPDATE ".BAB_CATEGORIESCAL_TBL." 
		set 
			name='".$babDB->db_escape_string($name)."', 
			description='".$babDB->db_escape_string($description)."', 
			bgcolor='".$babDB->db_escape_string($bgcolor)."' 
		where 
			id='".$babDB->db_escape_string($id)."'
		";

		$babDB->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
	}

function modifyResourceCal($userid, $oldname, $name, $description, $id)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name !!");
		return;
		}

	$query = "select * from ".BAB_RESOURCESCAL_TBL." where name=".$babDB->quote($oldname);	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("The resource doesn't exist");
		}
	else
		{

		$query = "UPDATE ".BAB_RESOURCESCAL_TBL." 
		set 
			name='".$babDB->db_escape_string($name)."', 
			description='".$babDB->db_escape_string($description)."' 
		where 
			id='".$babDB->db_escape_string($id)."'
		";

		$babDB->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
	}

function confirmDeletecategoriescal($userid, $id)
	{
	global $babDB;

	$req = "select* from ".BAB_CATEGORIESCAL_TBL." where id=".$babDB->quote($id);
	$res = $babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);

	// delete category
	$req = "delete from ".BAB_CATEGORIESCAL_TBL." where id=".$babDB->quote($id);
	$res = $babDB->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
	}

function confirmDeleteresourcescal($userid, $id)
	{
	global $babDB;

	$req = "select* from ".BAB_RESOURCESCAL_TBL." where id=".$babDB->quote($id);
	$res = $babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);

	// delete category
	$req = "delete from ".BAB_RESOURCESCAL_TBL." where id=".$babDB->quote($id);
	$res = $babDB->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
	}

/* main */
if( !isset($idx))
	$idx = "modifycat";

$grpid = array();
if( $userid == 0 )
	{
	if( !bab_isUserAdministrator())
		{
		return;
		}
	}
else
	{
	$req = "select * from ".BAB_GROUPS_TBL." where manager=".$babDB->quote($userid);
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		//while( $arr = $babDB->db_fetch_array($res))
		//	array_push($grpid, $arr['id']);
		}
	else
		{
		return;
		}
	}

if( isset($modify) && $modify == "modcat")
	{
	if( isset($submit))
		modifyCategoryCal($userid, $oldname, $name, $description, $bgcolor, $item);
	else if( isset($caldel))
		$idx = "delcat";
	}

if( isset($modify) && $modify == "modres")
	{
	if( isset($submit))
		modifyResourceCal($userid, $oldname, $name, $description, $item);
	else if( isset($resdel))
		$idx = "delres";
	}

if( isset($action) && $action == "Yes")
	{
	if( isset($category))
		confirmDeletecategoriescal($userid,$category);
	if( isset($resource))
		confirmDeleteresourcescal($userid,$resource);
	}


switch($idx)
	{
	case "delcat":
		categoryCalDelete($userid, $item);
		$babBody->title = bab_translate("Delete event category");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("modifycat", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$item);
		$babBody->addItemMenu("delcat", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=confcal&idx=delcat&item=".$item."&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	case "delres":
		resourceCalDelete($userid, $item);
		$babBody->title = bab_translate("Delete calendar resource");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("modifyres", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$item);
		$babBody->addItemMenu("delres", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=confcal&idx=delres&item=".$item."&userid=".$userid);
		break;
	case "modifyres":
		resourceCalModify($userid, $item);
		$babBody->title = bab_getResourceCalName($item) . " ". bab_translate("resource");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		$babBody->addItemMenu("modifyres", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifyres&item=".$item);
		break;
	case "modifycat":
	default:
		categoryCalModify($userid, $item);
		$babBody->title = bab_getCategoryCalName($item) . " ". bab_translate("category");
		$babBody->addItemMenu("listcat", bab_translate("Events categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=".$userid);
		$babBody->addItemMenu("modifycat", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=confcal&idx=modifycat&item=".$item);
		$babBody->addItemMenu("listres", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=".$userid);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>