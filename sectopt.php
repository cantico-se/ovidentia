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
* @internal SEC1 NA 18/12/2006 FULL
*/
include_once 'base.php';

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
			global $babBody, $babDB;
			$this->title = bab_translate("Title");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Enabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->access = bab_translate("Access");
			$this->groups = bab_translate("View");
			$this->maxallowedsectxt = bab_translate("The maximum number of authorized optional sections was reached");
			$req = "select distinct s.* from ".BAB_SECTIONS_TBL." s, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_SECTIONS_GROUPS_TBL." sg where s.enabled='Y' AND s.optional='Y' and s.id=sg.id_object and ( (ug.id_group=sg.id_group and ug.id_object='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."') or sg.id_group='0' or sg.id_group='1')";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			// don't get Administrator section and User's section
			$this->resa = $babDB->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where enabled='Y' AND optional='Y' and id !='1' and id!='5'");
			$this->counta = $babDB->db_num_rows($this->resa);

			$res = $babDB->db_query("select ".BAB_TOPICS_TBL.".id,".BAB_TOPICS_TBL.".id_cat  from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." c where ".BAB_TOPICS_TBL.".id_cat=c.id and c.optional='Y' AND c.enabled='Y'");
			while( $row = $babDB->db_fetch_array($res))
				{
				if( isset($babBody->topview[$row['id']]) )
					{
					if( !in_array($row['id_cat'], $this->arrcatid))
						array_push($this->arrcatid, $row['id_cat']);
					}
				}

			if( isset($GLOBALS['babMaxOptionalSections']))
				{
				$this->babMaxOptionalSections = $GLOBALS['babMaxOptionalSections'];
				}
			else
				{
				$this->babMaxOptionalSections = 0;
				}
			$this->countcat = count($this->arrcatid);
			$this->altbg = false;
			}

		function getnextp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counta)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $babDB->db_fetch_array($this->resa);
				$this->titleval = bab_toHtml(bab_translate($this->arr['title']));
				$this->descval = bab_toHtml(bab_translate($this->arr['description']));
				$this->idvalue = bab_toHtml($this->arr['id'])."-1";
				list($hidden) = $babDB->db_fetch_row($babDB->db_query("select hidden from ".BAB_SECTIONS_STATES_TBL." where type='1' and id_section='".$babDB->db_escape_string($this->arr['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
				if( !isset($hidden) || $hidden == 'Y')
					{
					$this->secchecked = '';
					}
				else
					{
					$this->secchecked = 'checked';
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcat)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($this->arrcatid[$i])."'"));
				$this->titleval = bab_toHtml($this->arr['title']);
				$this->descval = bab_toHtml($this->arr['description']);
				$this->idvalue = bab_toHtml($this->arr['id'])."-3";
				list($hidden) = $babDB->db_fetch_row($babDB->db_query("select hidden from ".BAB_SECTIONS_STATES_TBL." where type='3' and id_section='".$babDB->db_escape_string($this->arr['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
				if( !isset($hidden) || $hidden == "Y")
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
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->titleval = bab_toHtml($this->arr['title']);
				$this->descval = bab_toHtml($this->arr['description']);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=section&idx=Modify&item=".$this->arr['id']);
				$this->accessurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=section&idx=Groups&item=".$this->arr['id']);
				$this->idvalue = bab_toHtml($this->arr['id'])."-2";
				list($hidden) = $babDB->db_fetch_row($babDB->db_query("select hidden from ".BAB_SECTIONS_STATES_TBL." where type='2' and id_section='".$babDB->db_escape_string($this->arr['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
				if( !isset($hidden) || $hidden == "Y")
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
	global $babBody, $babDB;

	if( !empty($GLOBALS['BAB_SESS_USERID']))
		{
		$req = "select distinct s.id, s.optional from ".BAB_SECTIONS_TBL." s, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_SECTIONS_GROUPS_TBL." sg where s.id=sg.id_object and ( (ug.id_group=sg.id_group and ug.id_object='".$GLOBALS['BAB_SESS_USERID']."') or sg.id_group='0' or sg.id_group='1')";
		$res = $babDB->db_query($req);

		while( $row = $babDB->db_fetch_array($res))
			{
			if( count($sections) > 0 && in_array($row['id']."-2", $sections))
				$hidden = "N";
			else if( $row['optional'] == 'Y' )
				$hidden = "Y";
			else
				$hidden = "N";

			$req = "select id from ".BAB_SECTIONS_STATES_TBL." where type='2' and id_section='".$babDB->db_escape_string($row['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'";
			$res2 = $babDB->db_query($req);
			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				$babDB->db_query("update ".BAB_SECTIONS_STATES_TBL." set hidden='".$babDB->db_escape_string($hidden)."' where type='2' and id_section='".$babDB->db_escape_string($row['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			else
				$babDB->db_query("insert into ".BAB_SECTIONS_STATES_TBL." (id_section, type, id_user, hidden) values ('".$babDB->db_escape_string($row['id'])."', '2', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($hidden)."')");

			}

		$req = "select * from ".BAB_PRIVATE_SECTIONS_TBL." where id !='1' and id!='5'";
		$res = $babDB->db_query($req);
		while( $row = $babDB->db_fetch_array($res))
			{
			if( count($sections) > 0 && in_array($row['id']."-1", $sections))
				$hidden = "N";
			else if( $row['optional'] == 'Y' )
				$hidden = "Y";
			else
				$hidden = "N";

			$req = "select id from ".BAB_SECTIONS_STATES_TBL." where type='1' and id_section='".$babDB->db_escape_string($row['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'";
			$res2 = $babDB->db_query($req);
			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				$babDB->db_query("update ".BAB_SECTIONS_STATES_TBL." set hidden='".$babDB->db_escape_string($hidden)."' where type='1' and id_section='".$babDB->db_escape_string($row['id'])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			else
				$babDB->db_query("insert into ".BAB_SECTIONS_STATES_TBL." (id_section, type, id_user, hidden) values ('".$babDB->db_escape_string($row['id'])."', '1', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($hidden)."')");
			}

		$arrcatid = array();
		$res = $babDB->db_query("select ".BAB_TOPICS_TBL.".id,".BAB_TOPICS_TBL.".id_cat  from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($babBody->topview[$row['id']]) )
				{
				if( !in_array($row['id_cat'], $arrcatid))
					array_push($arrcatid, $row['id_cat']);
				}
			}

		for( $i = 0; $i < count($arrcatid); $i++ )
			{
			list($optional) = $babDB->db_fetch_row($babDB->db_query("select optional from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($arrcatid[$i])."'"));
			if( count($sections) > 0 && in_array($arrcatid[$i]."-3", $sections))
				$hidden = "N";
			else if( $optional == 'Y' )
				$hidden = "Y";
			else
				$hidden = "N";

			$req = "select id from ".BAB_SECTIONS_STATES_TBL." where type='3' and id_section='".$babDB->db_escape_string($arrcatid[$i])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'";
			$res2 = $babDB->db_query($req);
			if( $res2 && $babDB->db_num_rows($res2) > 0 )
				$babDB->db_query("update ".BAB_SECTIONS_STATES_TBL." set hidden='".$babDB->db_escape_string($hidden)."' where type='3' and id_section='".$babDB->db_escape_string($arrcatid[$i])."' and  id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			else
				$babDB->db_query("insert into ".BAB_SECTIONS_STATES_TBL." (id_section, type, id_user, hidden) values ('".$babDB->db_escape_string($arrcatid[$i])."', '3', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($hidden)."')");
			}

		}
	}

/* main */
$idx = bab_rp('idx', 'list');
if( '' != ($update = bab_pp('update')))
	{
	if( $update == 'enable')
		{
		$sections = bab_pp('sections', array());
		enableOptionalSections($sections);
		}
	}

switch($idx)
	{
	case 'list':
	default:
		$babBody->title = bab_translate("Optional sections list");
		if( sectionsList() == 0 )
			{
			$babBody->title = bab_translate("There is no section");
			}

		$babBody->addItemMenu('global', bab_translate("Options"), $GLOBALS['babUrlScript'].'?tg=options&idx=global');
		$babBody->addItemMenu('list', bab_translate("Sections"),$GLOBALS['babUrlScript'].'?tg=sectopt&idx=list');
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
