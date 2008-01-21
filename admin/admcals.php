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
* @internal SEC1 PR 12/04/2007 FULL
*/

include_once "base.php";
include_once $babInstallPath."utilit/calincl.php";


function calendarsCategories()
	{
	global $babBody;
	class calendarsCategoriesCls
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $desc;
		var $desctxt;
		var $bgcolor;
		var $bgcolortxt;
				
		var $arr = array();
		var $db;
		var $count;
		var $countcal;
		var $res;
		var $altbg = true;

		function calendarsCategoriesCls()
			{
			global $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->add = bab_translate("Add");
			$this->urladdcat = $GLOBALS['babUrlScript'].'?tg=admcals&idx=addc';
			
			$this->t_delete = bab_translate("Delete");
			$this->res = $babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." ORDER BY name,description ");
			$this->countcal = $babDB->db_num_rows($this->res);
			}
			
		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->altbg = !$this->altbg;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=modc&idcat=".$this->arr['id']);
				$this->urlname = bab_toHtml($this->arr['name']);
				$this->desc = bab_toHtml($this->arr['description']);
				$this->bgcolor = bab_toHtml($this->arr['bgcolor']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=delc&idcat=".$this->arr['id']);
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

	$temp = new calendarsCategoriesCls();
	$babBody->babecho(	bab_printTemplate($temp, "admcals.html", "categorieslist"));
	}



function calendarsAddCategory($catname, $catdesc, $bgcolor)
	{
	global $babBody;
	class calendarsAddCategoryCls
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

		function calendarsAddCategoryCls($catname, $catdesc, $bgcolor)
			{
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->addtxt = bab_translate("Add Category");
			$this->idcat = '';
			$this->add = 'addcat';
			$this->tgval = 'admcals';
			$this->name = bab_toHtml($catname);
			$this->desc = bab_toHtml($catdesc);
			$this->bgcolor = bab_toHtml($bgcolor);
			$this->selctorurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=selectcolor&idx=popup&callback=setColor");
			}
		}

	$temp = new calendarsAddCategoryCls($catname, $catdesc, $bgcolor);
	$babBody->babecho( bab_printTemplate($temp,"admcals.html", "categorycreate"));
	}


function calendarsPublic()
	{
	global $babBody;

	class calendarsPublicCls
		{
		var $altbg = true;
		function calendarsPublicCls()
			{
			global $babDB, $babBody;

			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->disabledtxt = bab_translate("Disabled");
			$this->rightstxt = bab_translate("Rights");
			$this->t_delete = bab_translate("Delete");
			$this->add = bab_translate("Add");
			$this->urladdcal = $GLOBALS['babUrlScript'].'?tg=admcals&idx=addp';
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");

			$this->res = $babDB->db_query("select cpt.*, ct.actif, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.type='".BAB_CAL_PUB_TYPE."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' ORDER BY cpt.name");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
		
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->idcal = bab_toHtml($arr['idcal']);
				$this->nameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=modp&grpid=".$arr['id']."&idcal=".$arr['idcal']);
				$this->rightsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=rigthsp&idcal=".$arr['idcal']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=delp&idcal=".$arr['idcal']);
				if( $arr['actif'] == 'Y')
					{
					$this->calchecked = '';
					}
				else
					{
					$this->calchecked = 'checked';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new calendarsPublicCls();
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendarslist"));
	}

function calendarsResource()
	{
	global $babBody;

	class calendarsResourceCls
		{
		var $altbg = true;

		function calendarsResourceCls()
			{
			global $babDB, $babBody;

			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->disabledtxt = bab_translate("Disabled");
			$this->rightstxt = bab_translate("Rights");
			$this->t_delete = bab_translate("Delete");
			$this->add = bab_translate("Add");
			$this->urladdcal = $GLOBALS['babUrlScript'].'?tg=admcals&idx=addr';
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");

			$this->res = $babDB->db_query("select cpt.*, ct.actif, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.type='".BAB_CAL_RES_TYPE."' and id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' ORDER BY cpt.name");
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
		
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->idcal = bab_toHtml($arr['idcal']);
				$this->nameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=modr&grpid=".$arr['id']."&idcal=".$arr['idcal']);
				$this->rightsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcal&idx=rigthsr&idcal=".$arr['idcal']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=delr&idcal=".$arr['idcal']);
				if( $arr['actif'] == 'Y')
					{
					$this->calchecked = '';
					}
				else
					{
					$this->calchecked = 'checked';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new calendarsResourceCls();
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendarslist"));
	}

function calendarsAddPublic($name, $desc, $idsa)
	{
	global $babBody;

	class calendarsAddPublicCls
		{

		function calendarsAddPublicCls($name, $desc, $idsa)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->addtxt = bab_translate("Add");
			$this->approbationtxt = bab_translate("Approbation schema");
			$this->nonetxt = bab_translate("None");
			$this->calname = bab_toHtml($name);
			$this->caldesc = bab_toHtml($desc);
			$this->calidsa = bab_toHtml($idsa);
			$this->add = "addp";
			$this->idcal = '';
			$this->tgval = 'admcals';
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnextschapp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = bab_toHtml($arr['name']);
				$this->said = bab_toHtml($arr['id']);
				if( $this->said == $this->calidsa )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new calendarsAddPublicCls($name, $desc, $idsa);
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendaradd"));
	}

function calendarsAddResource($name, $desc, $idsa)
	{
	global $babBody;

	class calendarsAddResourceCls
		{

		function calendarsAddResourceCls($name, $desc, $idsa)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->addtxt = bab_translate("Add");
			$this->approbationtxt = bab_translate("Approbation schema");
			$this->nonetxt = bab_translate("None");
			$this->calname = bab_toHtml($name);
			$this->caldesc = bab_toHtml($desc);
			$this->calidsa = bab_toHtml($idsa);
			$this->add = "addr";
			$this->idcal = '';
			$this->tgval = 'admcals';
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."' order by name asc");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnextschapp()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = bab_toHtml($arr['name']);
				$this->said = bab_toHtml($arr['id']);
				if( $this->said == $this->calidsa )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new calendarsAddResourceCls($name, $desc, $idsa);
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendaradd"));
	}

function calendarsDelResource($idcal)
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

		function temp($idcal)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar");
			$this->title = bab_toHtml(bab_getCalendarOwnerName($idcal, BAB_CAL_RES_TYPE));
			$this->warning = bab_translate("WARNING: This operation will delete the calendar and all associated events"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=res&idcal=".$idcal."&action=Yes");
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=res");
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($idcal);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function calendarsDelPublic($idcal)
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

		function temp($idcal)
			{
			$this->message = bab_translate("Are you sure you want to delete this calendar");
			$this->title = bab_getCalendarOwnerName($idcal, BAB_CAL_PUB_TYPE);
			$this->warning = bab_translate("WARNING: This operation will delete the calendar and all associated events"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=pub&idcal=".$idcal."&action=Yes");
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($idcal);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function addPublicCalendar($calname, $caldesc, $calidsa)
{
	global $babDB, $babBody;

	if( empty($calname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}


	$babDB->db_query("insert into ".BAB_CAL_PUBLIC_TBL." (name, description, id_dgowner, idsa) values ('" .$babDB->db_escape_string($calname). "', '".$babDB->db_escape_string($caldesc)."', '".$babDB->db_escape_string($babBody->currentAdmGroup)."', '".$babDB->db_escape_string($calidsa)."')");
	$idowner = $babDB->db_insert_id();
	$babDB->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('" .$babDB->db_escape_string($idowner). "', '".BAB_CAL_PUB_TYPE."')");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
	exit;
}

function addResourceCalendar($calname, $caldesc, $calidsa)
{
	global $babDB, $babBody;

	if( empty($calname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$babDB->db_query("insert into ".BAB_CAL_RESOURCES_TBL." (name, description, id_dgowner, idsa) values ('" .$babDB->db_escape_string($calname). "', '".$babDB->db_escape_string($caldesc)."', '".$babDB->db_escape_string($babBody->currentAdmGroup)."', '".$babDB->db_escape_string($calidsa)."')");
	$idowner = $babDB->db_insert_id();
	$babDB->db_query("insert into ".BAB_CALENDAR_TBL." (owner, type) values ('" .$babDB->db_escape_string($idowner). "', '".BAB_CAL_RES_TYPE."')");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
	exit;
}

function addCategoryCalendar($catname, $catdesc, $bgcolor)
{
	global $babDB, $babBody;

	if( empty($catname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$babDB->db_query("insert into ".BAB_CAL_CATEGORIES_TBL." (name, description, bgcolor) values ('" .$babDB->db_escape_string($catname). "', '".$babDB->db_escape_string($catdesc)."', '".$babDB->db_escape_string($bgcolor)."')");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
	exit;
}

function updatePublicCalendars($calids)
{
	global $babDB, $babBody;
	
	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where type='".BAB_CAL_PUB_TYPE."'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($calids) > 0 && in_array($row['id'], $calids))
			$enabled = "N";
		else
			$enabled = "Y";

		$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='".$babDB->db_escape_string($enabled)."' where id='".$babDB->db_escape_string($row['id'])."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
	exit;
}

function updateResourceCalendars($calids)
{
	global $babDB, $babBody;
	
	$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where type='".BAB_CAL_RES_TYPE."'");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($calids) > 0 && in_array($row['id'], $calids))
			$enabled = "N";
		else
			$enabled = "Y";

		$babDB->db_query("update ".BAB_CALENDAR_TBL." set actif='".$babDB->db_escape_string($enabled)."' where id='".$babDB->db_escape_string($row['id'])."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
	exit;
}


function deleteCalendarCategory($idcat)
{
	global $babDB, $babBody;
	
	$babDB->db_query("delete from ".BAB_CAL_CATEGORIES_TBL." WHERE id=".$babDB->quote($idcat));
	$babDB->db_query("update ".BAB_CAL_EVENTS_TBL." set id_cat='0' WHERE id_cat=".$babDB->quote($idcat));
	$babDB->db_query("update ".BAB_VAC_COLLECTIONS_TBL." set id_cat='0' WHERE id_cat=".$babDB->quote($idcat));
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['calendars'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'pub');

if( bab_rp('addc'))
{
	if( "addp" == bab_rp('addc') )
	{
		if( addPublicCalendar(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa')))
		{
			$idx = "pub";
		}
		else
		{
			$idx = "addp";
		}
	}elseif( "addr" == bab_rp('addc') )
	{
		if( addResourceCalendar(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa')))
		{
			$idx = "res";
		}
		else
		{
			$idx = "addr";
		}
	}
}
elseif( bab_rp('sublist'))
{
	if( $idx == "pub" )
	{
		$calids = bab_rp('calids', array());
		updatePublicCalendars($calids);
	}elseif( $idx == "res" )
	{
		$calids = bab_rp('calids', array());
		updateResourceCalendars($calids);
	}
}
elseif("Yes" == bab_rp('action'))
{
	if( $idx == "pub" )
	{
		bab_deleteCalendar(bab_rp('idcal'));
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		exit;
	}elseif( $idx == "res" )
	{
		bab_deleteCalendar(bab_rp('idcal'));
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		exit;
	}
}
elseif( "addcat" == bab_rp('add') && $babBody->isSuperAdmin)
{
	if( !addCategoryCalendar(bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor')))
	{
		$idx = "addc";
	}
}
elseif( $idx == "delc"  && $babBody->isSuperAdmin )
{
	deleteCalendarCategory(bab_rp('idcat'));
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
	exit;
}

switch($idx)
	{
	case "addc":
		if( $babBody->isSuperAdmin )
		{
		calendarsAddCategory(bab_rp('catname'), bab_rp('catdesc'), bab_rp('bgcolor'));
		$babBody->title = bab_translate("Add event category");
		$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		$babBody->addItemMenu("addc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addc");
		}
		break;
	case "cats":
		if( $babBody->isSuperAdmin )
		{
		calendarsCategories();
		$babBody->title = bab_translate("Calendar categories list");
		$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		/*$babBody->addItemMenu("addc", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addc");*/
		}
		break;

	case "delr":
		calendarsDelResource(bab_rp('idcal'));
		$babBody->title = bab_translate("Delete resource calendar");
		$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("delr", bab_translate("Del"), $GLOBALS['babUrlScript']."?tg=admcals&idx=delp");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		}
		break;

	case "delp":
		calendarsDelPublic(bab_rp('idcal'));
		$babBody->title = bab_translate("Delete public calendar");
		$babBody->addItemMenu("pub", bab_translate("Public"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("delp", bab_translate("Del"), $GLOBALS['babUrlScript']."?tg=admcals&idx=delr");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		}
		break;

	case "addr":
		calendarsAddResource(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa'));
		$babBody->title = bab_translate("Add resource calendar");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("addr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addr");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		}
		break;
	case "addp":
		calendarsAddPublic(bab_rp('calname'), bab_rp('caldesc'), bab_rp('calidsa'));
		$babBody->title = bab_translate("Add public calendar");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("addp", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addp");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		}
		break;
	case "res":
		calendarsResource();
		$babBody->title = bab_translate("Resources calendars List");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		/*$babBody->addItemMenu("addr", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addr");*/
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		}
		break;
	case "pub":
	default:
		calendarsPublic();
		$babBody->title = bab_translate("Public calendars List");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		/*$babBody->addItemMenu("addp", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admcals&idx=addp");*/
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		if( $babBody->isSuperAdmin )
		{
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>