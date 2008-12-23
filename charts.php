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

function listOrgCharts()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $urlname;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $edit;
		var $editurl;
		var $view;
		var $viewurl;
		var $descval;
		var $bedit;
		var $bediturl;
		var $lock;
		var $unlock;
		var $unlockurl;

		function temp()
			{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->edit = bab_translate("Edit");
			$this->view = bab_translate("View");

			$ocids = bab_orgchartAccess();

			if( count($ocids) > 0 )
				{
				$req = "select * from ".BAB_ORG_CHARTS_TBL." where id IN (".$babDB->quote($ocids).") order by name asc";
				$this->res = $babDB->db_query($req);
				$this->count = $babDB->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->ocid = $arr['id'];
				$this->viewurl = $GLOBALS['babUrlScript']."?tg=chart&ocid=".$arr['id']."&disp=disp3";
				$this->urlname = $arr['name'];
				$this->descval = $arr['description'];
				$this->lock = $arr['edit'];
				if( bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $arr['id']))
					{
					$this->bedit = true;
					if( $arr['edit'] == 'Y')
						{
						if( $arr['edit_author'] == $GLOBALS['BAB_SESS_USERID'] )
							{
							$this->edit = bab_translate("Edit");
							$this->unlock = bab_translate("Unlock");
							$this->unlockurl = $GLOBALS['babUrlScript']."?tg=charts&idx=unlock&ocid=".$arr['id'];
							$this->editurl = $GLOBALS['babUrlScript']."?tg=chart&ocid=".$arr['id']."&disp=disp1";
							$this->bediturl = true;
							}
						else
							{
							$this->bediturl = false;
							$this->edit = bab_translate("Locked")."[".bab_getUserName($arr['edit_author'])."]";
							}
						}
					else
						{
						$this->editurl = $GLOBALS['babUrlScript']."?tg=charts&idx=edit&ocid=".$arr['id']."&disp=disp1";
						$this->edit = bab_translate("Edit");
						$this->bediturl = true;
						}
					}
				else
					{
					$this->bedit = false;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "charts.html", "oclist"));
	return $temp->count;
	}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if(!isset($disp))
	{
	$disp = "disp1";
	}

$access = false;
if(bab_orgchartAccess())
{
	if( $idx == "edit" || $idx == "unlock")
	{
	if( isset($ocid) && bab_isAccessValid(BAB_OCUPDATE_GROUPS_TBL, $ocid))
		{
		$ocinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ORG_CHARTS_TBL." where id='".$babDB->db_escape_string($ocid)."'"));
		if( $ocinfo['edit'] == 'Y' && $ocinfo['edit_author'] == $BAB_SESS_USERID)
			{
			if( $idx == "edit" )
				{
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=acharts&ocid=".$ocid);
				exit;
				}
			else
				{
				$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set edit='N' where id='".$babDB->db_escape_string($ocid)."'");
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=charts&disp=".$disp);
				exit;
				}
			}
		else if( $ocinfo['edit'] == 'N')
			{
			if( $idx == "edit" )
				{
				$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set edit='Y', edit_author='".$babDB->db_escape_string($BAB_SESS_USERID)."', edit_date=now() where id='".$babDB->db_escape_string($ocid)."'");
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=chart&ocid=".$ocid."&disp=".$disp);
				exit;
				}
			else
				{
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=charts");
				exit;
				}
			}
		}
	}
}


switch($idx)
	{
	default:
	case "list":
		$babBody->title = bab_translate("List of all organization charts");
		listOrgCharts();
		$babBody->addItemMenu("list", bab_translate("Charts"), $GLOBALS['babUrlScript']."?tg=charts&idx=list");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>