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

function sectionsList()
	{
	global $babBody;
	class temp
		{
		var $title;
		var $description;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $counta;
		var $resa;
		var $countcat;
		var $rescat;
		var $secchecked;
		var $enabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $idvalue;
		var $access;
		var $accessurl;

		var $descval;
		var $titleval;
		var $arrcatid = array();

		var $maxallowedsectxt;

		function temp()
			{
			global $babBody;
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Enabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->access = bab_translate("Access");
			$this->groups = bab_translate("View");
			$this->maxallowedsectxt = bab_translate("The maximum number of authorized optional sections was reached");
			$this->db = $GLOBALS['babDB'];
			$req = "select distinct s.* from ".BAB_SECTIONS_TBL." s, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_SECTIONS_GROUPS_TBL." sg where s.optional='Y' and s.id=sg.id_object and ( (ug.id_group=sg.id_group and ug.id_object='".$GLOBALS['BAB_SESS_USERID']."') or sg.id_group='0' or sg.id_group='1')";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			// don't get Administrator section and User's section
			$this->resa = $this->db->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where optional='Y' and id !='1' and id!='5'");
			$this->counta = $this->db->db_num_rows($this->resa);

			$res = $this->db->db_query("select ".BAB_TOPICS_TBL.".id,".BAB_TOPICS_TBL.".id_cat  from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and ".BAB_TOPICS_CATEGORIES_TBL.".optional='Y'");
			while( $row = $this->db->db_fetch_array($res))
				{
				if( in_array($row['id'], $babBody->topview) )
					{
					if( !in_array($row['id_cat'], $this->arrcatid))
						array_push($this->arrcatid, $row['id_cat']);
					}
				}

			$this->countcat = count($this->arrcatid);
			}

		function getnextp()
			{
			static $i = 0;
			if( $i < $this->counta)
				{
				$this->arr = $this->db->db_fetch_array($this->resa);
				$this->titleval = bab_translate($this->arr['title']);
				$this->descval = bab_translate($this->arr['description']);
				$this->idvalue = $this->arr['id']."-1";
				list($hidden) = $this->db->db_fetch_row($this->db->db_query("select hidden from ".BAB_SECTIONS_STATES_TBL." where type='1' and id_section='".$this->arr['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
				if( $hidden == "Y")
					$this->secchecked = "";
				else
					$this->secchecked = "checked";
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countcat)
				{
				$this->arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$this->arrcatid[$i]."'"));
				$this->titleval = $this->arr['title'];
				$this->descval = $this->arr['description'];
				$this->idvalue = $this->arr['id']."-3";
				list($hidden) = $this->db->db_fetch_row($this->db->db_query("select hidden from ".BAB_SECTIONS_STATES_TBL." where type='3' and id_section='".$this->arr['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
				if( $hidden == "Y")
					$this->secchecked = "";
				else
					$this->secchecked = "checked";
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->titleval = $this->arr['title'];
				$this->descval = $this->arr['description'];
				$this->url = $GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$this->arr['id'];
				$this->accessurl = $GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$this->arr['id'];
				$this->idvalue = $this->arr['id']."-2";
				list($hidden) = $this->db->db_fetch_row($this->db->db_query("select hidden from ".BAB_SECTIONS_STATES_TBL." where type='2' and id_section='".$this->arr['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
				if( $hidden == "Y")
					$this->secchecked = "";
				else
					$this->secchecked = "checked";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sectopt.html", "sectionslist"));
	return $temp->count + $temp->countcat + $temp->counta;
	}


function enableOptionalSections($sections)
	{
	global $babBody;

	if( !empty($GLOBALS['BAB_SESS_USERID']))
		{
		$db = $GLOBALS['babDB'];

		$req = "select distinct s.id, s.optional from ".BAB_SECTIONS_TBL." s, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_SECTIONS_GROUPS_TBL." sg where s.id=sg.id_object and ( (ug.id_group=sg.id_group and ug.id_object='".$GLOBALS['BAB_SESS_USERID']."') or sg.id_group='0' or sg.id_group='1')";
		$res = $db->db_query($req);

		while( $row = $db->db_fetch_array($res))
			{
			if( count($sections) > 0 && in_array($row['id']."-2", $sections))
				$hidden = "N";
			else if( $row['optional'] == 'Y' )
				$hidden = "Y";
			else
				$hidden = "N";

			$req = "select id from ".BAB_SECTIONS_STATES_TBL." where type='2' and id_section='".$row['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'";
			$res2 = $db->db_query($req);
			if( $res2 && $db->db_num_rows($res2) > 0 )
				$db->db_query("update ".BAB_SECTIONS_STATES_TBL." set hidden='".$hidden."' where type='2' and id_section='".$row['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			else
				$db->db_query("insert into ".BAB_SECTIONS_STATES_TBL." (id_section, type, id_user, hidden) values ('".$row['id']."', '2', '".$GLOBALS['BAB_SESS_USERID']."', '".$hidden."')");

			}

		$req = "select * from ".BAB_PRIVATE_SECTIONS_TBL." where id !='1' and id!='5'";
		$res = $db->db_query($req);
		while( $row = $db->db_fetch_array($res))
			{
			if( count($sections) > 0 && in_array($row['id']."-1", $sections))
				$hidden = "N";
			else if( $row['optional'] == 'Y' )
				$hidden = "Y";
			else
				$hidden = "N";

			$req = "select id from ".BAB_SECTIONS_STATES_TBL." where type='1' and id_section='".$row['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'";
			$res2 = $db->db_query($req);
			if( $res2 && $db->db_num_rows($res2) > 0 )
				$db->db_query("update ".BAB_SECTIONS_STATES_TBL." set hidden='".$hidden."' where type='1' and id_section='".$row['id']."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			else
				$db->db_query("insert into ".BAB_SECTIONS_STATES_TBL." (id_section, type, id_user, hidden) values ('".$row['id']."', '1', '".$GLOBALS['BAB_SESS_USERID']."', '".$hidden."')");
			}

		$arrcatid = array();
		$res = $db->db_query("select ".BAB_TOPICS_TBL.".id,".BAB_TOPICS_TBL.".id_cat  from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id");
		while( $row = $db->db_fetch_array($res))
			{
			if( in_array($row['id'], $babBody->topview) )
				{
				if( !in_array($row['id_cat'], $arrcatid))
					array_push($arrcatid, $row['id_cat']);
				}
			}

		for( $i = 0; $i < count($arrcatid); $i++ )
			{
			list($optional) = $db->db_fetch_row($db->db_query("select optional from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arrcatid[$i]."'"));
			if( count($sections) > 0 && in_array($arrcatid[$i]."-3", $sections))
				$hidden = "N";
			else if( $optional == 'Y' )
				$hidden = "Y";
			else
				$hidden = "N";

			$req = "select id from ".BAB_SECTIONS_STATES_TBL." where type='3' and id_section='".$arrcatid[$i]."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'";
			$res2 = $db->db_query($req);
			if( $res2 && $db->db_num_rows($res2) > 0 )
				$db->db_query("update ".BAB_SECTIONS_STATES_TBL." set hidden='".$hidden."' where type='3' and id_section='".$arrcatid[$i]."' and  id_user='".$GLOBALS['BAB_SESS_USERID']."'");
			else
				$db->db_query("insert into ".BAB_SECTIONS_STATES_TBL." (id_section, type, id_user, hidden) values ('".$arrcatid[$i]."', '3', '".$GLOBALS['BAB_SESS_USERID']."', '".$hidden."')");
			}

		}
	}

/* main */
if( isset($update))
	{
	if( $update == "enable")
		enableOptionalSections($sections);
	}

if( !isset($idx))
	$idx = "list";

switch($idx)
	{
	case "list":
	default:
		$babBody->title = bab_translate("Optional sections list");
		if( sectionsList() == 0 )
			$babBody->title = bab_translate("There is no section");

		$babBody->addItemMenu("global", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=options&idx=global");
		$babBody->addItemMenu("list", bab_translate("Sections"),$GLOBALS['babUrlScript']."?tg=sectopt&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
