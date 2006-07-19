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
include_once $babInstallPath."utilit/evtincl.php";
include_once $babInstallPath."utilit/calincl.php";

function modifyCalendarCategory($idcat, $catname, $catdesc, $bgcolor)
	{
	global $babBody;
	class modifyCalendarCategoryCls
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

		function modifyCalendarCategoryCls($idcat, $catname, $catdesc, $bgcolor)
			{
			global $babDB;

			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->bgcolortxt = bab_translate("Color");
			$this->addtxt = bab_translate("Update");
			$this->idcat = $idcat;
			$this->add = 'updcat';
			$this->tgval = 'admcal';
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_CAL_CATEGORIES_TBL." where id=".$babDB->quote($idcat)));
			if( !empty($catname))
				{
				$this->name = $catname;
				}
			else
				{
				$this->name = $arr['name'];
				}
			if( !empty($catdesc))
				{
				$this->desc = $catdesc;
				}
			else
				{
				$this->desc = $arr['description'];
				}

			if( !empty($bgcolor))
				{
				$this->bgcolor = $bgcolor;
				}
			else
				{
				$this->bgcolor = $arr['bgcolor'];
				}
			$this->selctorurl = $GLOBALS['babUrlScript']."?tg=selectcolor&idx=popup&callback=setColor";
			}
		}

	$temp = new modifyCalendarCategoryCls($idcat, $catname, $catdesc, $bgcolor);
	$babBody->babecho( bab_printTemplate($temp,"admcals.html", "categorycreate"));
	}


function modifyCalendarResource($idcal, $name, $desc, $idsa)
	{
	global $babBody;

	class modifyCalendarResourceCls
		{

		function modifyCalendarResourceCls($idcal, $name, $desc, $idsa)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->addtxt = bab_translate("Modify");
			$this->approbationtxt = bab_translate("Approbation schema");
			$this->nonetxt = bab_translate("None");
			$arr = $babDB->db_fetch_array($babDB->db_query("select cpt.* from ".BAB_CAL_RESOURCES_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.id=".$babDB->quote($idcal)));
			if( !empty($name))
				{
				$this->calname = $name;
				}
			else
				{
				$this->calname = $arr['name'];
				}
			if( !empty($desc))
				{
				$this->caldesc = $desc;
				}
			else
				{
				$this->caldesc = $arr['description'];
				}
			if( !empty($idsa))
				{
				$this->calidsa = $idsa;
				}
			else
				{
				$this->calidsa = $arr['idsa'];
				}
			$this->add = "modr";
			$this->idcal = $arr['id'];
			$this->tgval = 'admcal';
			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner=".$babDB->quote($babBody->currentAdmGroup)." order by name asc");
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
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
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

	$temp = new modifyCalendarResourceCls($idcal, $name, $desc, $idsa);
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendaradd"));
	}

function modifyCalendarPublic($idcal, $name, $desc, $idsa)
	{
	global $babBody;

	class modifyCalendarPublicCls
		{

		function modifyCalendarPublicCls($idcal, $name, $desc, $idsa)
			{
			global $babBody, $babDB;
			$this->nametxt = bab_translate("Name");
			$this->desctxt = bab_translate("Description");
			$this->addtxt = bab_translate("Modify");
			$this->approbationtxt = bab_translate("Approbation schema");
			$this->nonetxt = bab_translate("None");
			$arr = $babDB->db_fetch_array($babDB->db_query("select cpt.* from ".BAB_CAL_PUBLIC_TBL." cpt left join ".BAB_CALENDAR_TBL." ct on ct.owner=cpt.id where ct.id=".$babDB->quote($idcal)));
			if( !empty($name))
				{
				$this->calname = $name;
				}
			else
				{
				$this->calname = $arr['name'];
				}
			if( !empty($desc))
				{
				$this->caldesc = $desc;
				}
			else
				{
				$this->caldesc = $arr['description'];
				}
			if( !empty($idsa))
				{
				$this->calidsa = $idsa;
				}
			else
				{
				$this->calidsa = $arr['idsa'];
				}
			$this->add = "modp";
			$this->idcal = $arr['id'];
			$this->tgval = 'admcal';
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
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
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

	$temp = new modifyCalendarPublicCls($idcal, $name, $desc, $idsa);
	$babBody->babecho( bab_printTemplate($temp, "admcals.html", "calendaradd"));
	}


function updateResourceCalendar($idcal, $calname, $caldesc, $calidsa)
{
	global $babDB, $babBody;

	if( empty($calname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}


	list($old_idsa) = $babDB->db_fetch_row($babDB->db_query("select idsa from ".BAB_CAL_RESOURCES_TBL." where id='".$babDB->db_escape_string($idcal)."'"));
	if( $old_idsa != 0 && $old_idsa != $calidsa )
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_cal='".$babDB->db_escape_string($idcal)."' and status='".BAB_CAL_STATUS_NONE."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['idfai'] != 0 )
			{
			deleteFlowInstance($arr['idfai']);
			}

		if( $calidsa == 0 )
			{
			$idfai = 0;
			}
		else
			{
			$idfai = makeFlowInstance($calidsa, "cal-".$idcal."-".$arr['id_event']);
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			$calinfo = $babBody->icalendars->getCalendarInfo($idcal);
			notifyEventApprovers($arr['id_event'], $nfusers, $calinfo);
			}
		$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id_cal='".$babDB->db_escape_string($idcal)."'and id_event='".$babDB->db_escape_string($arr['id_event'])."'");
		}		
	}

	$babDB->db_query("update ".BAB_CAL_RESOURCES_TBL." set name='".$babDB->db_escape_string($calname)."', description='".$babDB->db_escape_string($caldesc)."', idsa='".$babDB->db_escape_string($calidsa)."' where id='".$babDB->db_escape_string($idcal)."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
	exit;
}

function updatePublicCalendar($idcal, $calname, $caldesc, $calidsa)
{
	global $babDB, $babBody;

	if( empty($calname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	list($old_idsa) = $babDB->db_fetch_row($babDB->db_query("select idsa from ".BAB_CAL_PUBLIC_TBL." where id='".$babDB->db_escape_string($idcal)."'"));
	if( $old_idsa != 0 && $old_idsa != $calidsa )
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_cal='".$babDB->db_escape_string($idcal)."' and status='".BAB_CAL_STATUS_NONE."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['idfai'] != 0 )
			{
			deleteFlowInstance($arr['idfai']);
			}

		if( $calidsa == 0 )
			{
			$idfai = 0;
			}
		else
			{
			$idfai = makeFlowInstance($calidsa, "cal-".$idcal."-".$arr['id_event']);
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			$calinfo = $babBody->icalendars->getCalendarInfo($idcal);
			notifyEventApprovers($arr['id_event'], $nfusers, $calinfo);
			}
		$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id_cal='".$babDB->db_escape_string($idcal)."'and id_event='".$babDB->db_escape_string($arr['id_event'])."'");
		}		
	}

	$babDB->db_query("update ".BAB_CAL_PUBLIC_TBL." set name='".$babDB->db_escape_string($calname)."', description='".$babDB->db_escape_string($caldesc)."', idsa='".$babDB->db_escape_string($calidsa)."'  where id='".$babDB->db_escape_string($idcal)."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
	exit;
}

function updateCalendarCategory($idcat, $catname, $catdesc, $bgcolor)
{
	global $babDB, $babBody;

	if( empty($catname))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
		return false;
		}

	$babDB->db_query("update ".BAB_CAL_CATEGORIES_TBL." set name='".$babDB->db_escape_string($catname)."', description='".$babDB->db_escape_string($catdesc)."', bgcolor='".$babDB->db_escape_string($bgcolor)."' where id='".$babDB->db_escape_string($idcat)."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
	exit;
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['calendars'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($addc))
{
	if( $addc == "modp" )
	{
		if( updatePublicCalendar($idcal, $calname, $caldesc, $calidsa))
		{
			$idx = "pub";
		}
		else
		{
			$idx = "modp";
		}
	}elseif( $addc == "modr" )
	{
		if( updateResourceCalendar($idcal, $calname, $caldesc, $calidsa))
		{
			$idx = "res";
		}
		else
		{
			$idx = "modr";
		}
	}
}
elseif( isset($add) && $add == "updcat"  && $babBody->isSuperAdmin)
{
	updateCalendarCategory($idcat, $catname, $catdesc, $bgcolor);

}elseif( isset($aclpub))
	{
	include_once $babInstallPath."admin/acl.php";
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
	}
elseif( isset($aclres))
	{
	include_once $babInstallPath."admin/acl.php";
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
	}


switch($idx)
	{
	case "rigthsr":
		include_once $babInstallPath."admin/acl.php";
		$babBody->title = bab_translate("Rights for resource calendar").": ".bab_getCalendarOwnerName($idcal, BAB_CAL_RES_TYPE);
		$macl = new macl("admcal", "rightsp", $idcal, "aclres");
        $macl->addtable( BAB_CAL_RES_VIEW_GROUPS_TBL,bab_translate("Who can view this calendar"));
		$macl->addtable( BAB_CAL_RES_ADD_GROUPS_TBL,bab_translate("Who can add events to this calendar"));
		$macl->filter(0,0,1,0,1);
		$macl->addtable( BAB_CAL_RES_MAN_GROUPS_TBL,bab_translate("Who can manage this calendar"));
		$macl->filter(0,0,1,1,1);
		$macl->addtable( BAB_CAL_RES_GRP_GROUPS_TBL,bab_translate("Users groups that will be notified"));
		$macl->filter(0,0,1,0,1);
        $macl->babecho();

		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("rigthsr", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admcal&idx=rightsp&idcal=".$idcal);
		$babBody->addItemMenu("user", bab_translate("Personal"), $GLOBALS['babUrlScript']."?tg=admcals&idx=user");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		break;
	case "rigthsp":
		include_once $babInstallPath."admin/acl.php";
		$babBody->title = bab_translate("Rights for public calendar").": ".bab_getCalendarOwnerName($idcal, BAB_CAL_PUB_TYPE);
		$macl = new macl("admcal", "rightsp", $idcal, "aclpub");
        $macl->addtable( BAB_CAL_PUB_VIEW_GROUPS_TBL,bab_translate("Who can view this calendar"));
		$macl->addtable( BAB_CAL_PUB_MAN_GROUPS_TBL,bab_translate("Who can manage this calendar"));
		$macl->filter(0,0,1,1,1);
		$macl->addtable( BAB_CAL_PUB_GRP_GROUPS_TBL,bab_translate("Users groups"));
		$macl->filter(0,0,1,0,1);
        $macl->babecho();

		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("rigthsp", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=admcal&idx=rightsp&idcal=".$idcal);
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("user", bab_translate("Personal"), $GLOBALS['babUrlScript']."?tg=admcals&idx=user");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		break;
	case "modc":
		if( !isset($catname)) {	$catname = ""; }
		if( !isset($catdesc)) {	$catdesc = ""; }
		if( !isset($bgcolor)) {	$bgcolor = ""; }
		modifyCalendarCategory($idcat, $catname, $catdesc, $bgcolor);
		$babBody->title = bab_translate("Modify event category");
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("user", bab_translate("Personal"), $GLOBALS['babUrlScript']."?tg=admcals&idx=user");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		$babBody->addItemMenu("modc", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admcals&idx=modc");
		break;
	case "modr":
		if( !isset($calname)) {	$calname = ""; }
		if( !isset($caldesc)) {	$caldesc = ""; }
		if( !isset($calidsa)) {	$calidsa = ""; }
		modifyCalendarResource($idcal, $calname, $caldesc, $calidsa);
		$babBody->title = bab_translate("Resource calendar").": ".bab_getCalendarOwnerName($idcal, BAB_CAL_RES_TYPE);
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("user", bab_translate("Personal"), $GLOBALS['babUrlScript']."?tg=admcals&idx=user");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		break;
	case "modp":
	default:
		if( !isset($calname)) {	$calname = ""; }
		if( !isset($caldesc)) {	$caldesc = ""; }
		if( !isset($calidsa)) {	$calidsa = ""; }
		modifyCalendarPublic($idcal, $calname, $caldesc, $calidsa);
		$babBody->title = bab_translate("Public calendar").": ".bab_getCalendarOwnerName($idcal, BAB_CAL_PUB_TYPE);
		$babBody->addItemMenu("pub", bab_translate("PublicCalendar"), $GLOBALS['babUrlScript']."?tg=admcals&idx=pub");
		$babBody->addItemMenu("modp", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admcal&idx=modp");
		$babBody->addItemMenu("res", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=admcals&idx=res");
		$babBody->addItemMenu("user", bab_translate("Personal"), $GLOBALS['babUrlScript']."?tg=admcals&idx=user");
		$babBody->addItemMenu("cats", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=admcals&idx=cats");
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>