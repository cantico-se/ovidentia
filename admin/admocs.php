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

function addOrgChart($nameval, $descriptionval, $dirid, $display_mode)
{
	global $babBody;
	class temp
	{
		var $name;
		var $description;
		var $nameval;
		var $descriptionval;
		var $add;
		var $dirname;
		var $dirid;
		var $res;
		var $count;
		var $directory;

		function temp($nameval, $descriptionval, $display_mode, $dirid)
		{
			global $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->directory = bab_translate("Directories");
			$this->add = bab_translate("Add");
			$this->nameval = $nameval == ""? "": $nameval;
			$this->descriptionval = $descriptionval == ""? "": $descriptionval;
			$this->res = $babDB->db_query("select * from ".BAB_DB_DIRECTORIES_TBL." order by name asc");
			$this->count = $babDB->db_num_rows($this->res);

			$this->display_horizontal = bab_translate("Horizontal view");
			$this->display_text = bab_translate("Text view");
			$this->display_mode = bab_translate("Default view");
			
			$this->displayval = $display_mode;
			$this->diridval = $dirid;
		}

		function getnextdir()
		{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
			{
				$arr = $babDB->db_fetch_array($this->res);
				$this->dirname = bab_toHtml($arr['name']);
				$this->dirid = $arr['id'];
				$i++;
				return true;
			} else {
				return false;

			}
		}
	}

	$temp = new temp($nameval, $descriptionval, $display_mode, $dirid);
	$babBody->babecho(	bab_printTemplate($temp,"admocs.html", "occreate"));
}

function listOrgCharts()
{
	global $babBody;
	class temp
	{
		var $name;
		var $urlname;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $gview;
		var $gviewurl;
		var $gupdate;
		var $gupdateurl;
		var $descval;
		var $access;
		var $altbg = true;

		function temp()
		{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->directory = bab_translate("Directories");
			$this->access = bab_translate("Access");
			$this->grights = bab_translate("Rights");
			$this->db = $GLOBALS['babDB'];
			$req = "select oc.*, dd.name as dirname from ".BAB_ORG_CHARTS_TBL." oc left join ".BAB_DB_DIRECTORIES_TBL." dd on oc.id_directory=dd.id where oc.id_dgowner='".bab_getCurrentAdmGroup()."' order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
		}

		function getnext()
		{
			static $i = 0;
			if( $i < $this->count)
			{
				$this->altbg = !$this->altbg;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=admoc&idx=modify&item=".$this->arr['id'];
				$this->grightsurl = $GLOBALS['babUrlScript']."?tg=admoc&idx=ocrights&item=".$this->arr['id'];
				$this->dirval = $this->arr['dirname'];
				$this->urlname = $this->arr['name'];
				$this->descval = $this->arr['description'];
				$i++;
				return true;
			} else {
				return false;
			}
		}
	}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admocs.html", "oclist"));
	return $temp->count;
}

function saveOrgChart($name, $description, $dirid, $display_mode)
{
	global $babBody;
	if( empty($name))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
	}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select id from ".BAB_ORG_CHARTS_TBL." where name='".$db->db_escape_string($name)."' and id_dgowner='".$db->db_escape_string(bab_getCurrentAdmGroup())."'");
	if( $db->db_num_rows($res) > 0)
	{
		$babBody->msgerror = bab_translate("ERROR: This organization chart already exists");
		return false;
	}

	$query = "insert into ".BAB_ORG_CHARTS_TBL."
			(name, description, id_directory, id_dgowner, display_mode)
		values (
			'" .$db->db_escape_string($name). "',
			'" . $db->db_escape_string($description). "',
			'" . $db->db_escape_string($dirid). "',
			'" . $db->db_escape_string(bab_getCurrentAdmGroup()). "',
			'" . $db->db_escape_string($display_mode). "'
		)";
	$db->db_query($query);
	$id = $db->db_insert_id();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admoc&idx=ocview&item=".$id);
	exit;
}

/* main */
if( !bab_isUserAdministrator() && !bab_isDelegated('orgchart'))
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');
$addoc = bab_pp('addoc');
if( $addoc == "addoc" )
{
	$fname = bab_pp('fname');
	$description = bab_pp('description');
	$dirid = bab_pp('dirid');
	$display_mode = bab_pp('display_mode');
	if( !saveOrgChart($fname, $description, $dirid, $display_mode)){
		$idx = "addocs";
	}
}

switch($idx)
{
	case "addocs":
		$babBody->title = bab_translate("Add a new organization chart");
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("addocs", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admocs&idx=addocs");
		$fname = bab_rp('fname');
		$description = bab_rp('description');
		$dirid = bab_rp('dirid');
		$display_mode = bab_rp('display_mode');
		addOrgChart($fname, $description, $dirid, $display_mode);
		break;

	default:
	case "list":
		$babBody->title = bab_translate("List of all organization charts");
		listOrgCharts();
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=admocs&idx=list");
		$babBody->addItemMenu("addocs", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admocs&idx=addocs");
		break;
}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','AdminCharts');
?>
