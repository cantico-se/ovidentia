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
require_once dirname(__FILE__).'/utilit/registerglobals.php';

function sectionsList()
{
	global $babBody;

	class SectionsList_template
	{
		public $title;
		public $description;
		
		public $enabled;
		public $checkall;
		public $uncheckall;
		public $update;
		public $idvalue;
		public $access;
		public $accessurl;

		public $descval;
		public $titleval;
		public $arrcatid = array();

		public $maxallowedsectxt;

		public $sections = array();


		public function __construct()
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


			$req = 'SELECT DISTINCT s.* FROM '.BAB_SECTIONS_TBL.' s, '.BAB_USERS_GROUPS_TBL.' ug, '.BAB_SECTIONS_GROUPS_TBL.' sg WHERE s.enabled=\'Y\' AND s.optional=\'Y\' AND s.id=sg.id_object AND ( (ug.id_group=sg.id_group AND ug.id_object='.$babDB->quote($GLOBALS['BAB_SESS_USERID']).') OR sg.id_group=0 or sg.id_group=1)';
			$publicSections = $babDB->db_query($req);

			while ($public = $babDB->db_fetch_assoc($publicSections)) {
				list($hidden) = $babDB->db_fetch_row($babDB->db_query('SELECT hidden FROM '.BAB_SECTIONS_STATES_TBL.' WHERE type=\'2\' AND id_section='.$babDB->quote($public['id']).' AND id_user='.$babDB->quote($GLOBALS['BAB_SESS_USERID'])));
				$checked = (isset($hidden) && $hidden != 'Y');
				$this->sections[$public['id'] . '-2'] = array('title' => $public['title'], 'description' => $public['description'], 'checked' => $checked);
			}				

			// don't get Administrator section and User's section
			$privateSections = $babDB->db_query('SELECT * FROM '.BAB_PRIVATE_SECTIONS_TBL.' WHERE enabled=\'Y\' AND optional=\'Y\' AND id !=1 AND id!=5');

			while ($private = $babDB->db_fetch_assoc($privateSections)) {
				list($hidden) = $babDB->db_fetch_row($babDB->db_query('SELECT hidden FROM '.BAB_SECTIONS_STATES_TBL.' WHERE type=\'1\' AND id_section='.$babDB->quote($private['id']).' AND id_user='.$babDB->quote($GLOBALS['BAB_SESS_USERID'])));
				$checked = (isset($hidden) && $hidden != 'Y');
				$this->sections[$private['id'] . '-1'] = array('title' => $private['title'], 'description' => $private['description'], 'checked' => $checked);
			}				

			// Add sections from article categories.
			$res = $babDB->db_query('SELECT '.BAB_TOPICS_TBL.'.id,'.BAB_TOPICS_TBL.'.id_cat FROM '.BAB_TOPICS_TBL.' JOIN '.BAB_TOPICS_CATEGORIES_TBL.' c WHERE '.BAB_TOPICS_TBL.'.id_cat=c.id AND c.optional=\'Y\' AND c.enabled=\'Y\'');
			while ($row = $babDB->db_fetch_assoc($res)) {
				if (isset($babBody->topview[$row['id']]) && !in_array($row['id_cat'], $this->arrcatid)) {
					array_push($this->arrcatid, $row['id_cat']);
					$category = $babDB->db_fetch_assoc($babDB->db_query('SELECT * FROM '.BAB_TOPICS_CATEGORIES_TBL.' WHERE id='.$babDB->quote($row['id_cat'])));
					list($hidden) = $babDB->db_fetch_row($babDB->db_query('SELECT hidden FROM '.BAB_SECTIONS_STATES_TBL.' WHERE type=\'3\' AND id_section='.$babDB->quote($category['id']).' AND id_user='.$babDB->quote($GLOBALS['BAB_SESS_USERID'])));
					$checked = (isset($hidden) && $hidden != 'Y');
					$this->sections[$row['id_cat'] . '-3'] = array('title' => $category['title'], 'description' => $category['description'], 'checked' => $checked);
				}
			}

			if (isset($GLOBALS['babMaxOptionalSections'])) {
				$this->babMaxOptionalSections = $GLOBALS['babMaxOptionalSections'];
			} else {
				$this->babMaxOptionalSections = 0;
			}
			$this->countcat = count($this->arrcatid);
			$this->altbg = false;
			
			bab_Sort::asort($this->sections, 'title');
		}


		public function getNextSection()
		{
			if (list($sectionId, $section) = each($this->sections)) {
				$this->altbg = $this->altbg ? false : true;
				$this->titleval = bab_toHtml($section['title']);
				$this->descval = bab_toHtml($section['description']);
				$this->idvalue = bab_toHtml($sectionId);
				$this->secchecked = $section['checked'] ? 'checked' : '';
				return true;
			}
			reset($this->sections);
			return false;
		}
	}


	$temp = new SectionsList_template();
	$babBody->babecho(bab_printTemplate($temp, 'sectopt.html', 'sectionslist'));
	return count($temp->sections);
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
