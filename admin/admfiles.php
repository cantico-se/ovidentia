<?php
/************************************************************************
 * Ovidentia                                                            *
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ************************************************************************
 * This program is free software; you can redistribute it and/or modify *
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


function listGroups()
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $public;
		var $private;
		var $moderate;
		var $url;
		var $urlname;
		var $group;
			
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $burl;

		function temp()
			{
			$this->fullname = bab_translate("Groups");
			$this->public = bab_translate("Public");
			$this->private = bab_translate("Private");
			$this->moderate = bab_translate("Moderate");
			$this->notify = bab_translate("Notify");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$req = "select * from ".BAB_GROUPS_TBL." order by id asc";
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->burl = true;
				$this->bview = true;
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr['id'] == 1 || $this->arr['id'] == 2)
					$this->burl = false;

				if( $this->arr['id'] == 2)
					$this->bview = false;

				if( $this->arr['gstorage'] == "Y")
					$this->gstorage = "checked";
				else
					$this->gstorage = "";
				if( $this->arr['ustorage'] == "Y")
					$this->ustorage = "checked";
				else
					$this->ustorage = "";
				if( $this->arr['moderate'] == "Y")
					$this->cmoderate = "checked";
				else
					$this->cmoderate = "";
				if( $this->arr['filenotify'] == "Y")
					$this->cnotify = "checked";
				else
					$this->cnotify = "";
				$this->url = $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$this->arr['id'];
				if( $this->arr['id'] < 3 )
					$this->urlname = bab_getGroupName($this->arr['id']);
				else
					$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admfiles.html", "admfileslist"));
	}

function updateGroups($groups, $ugroups, $moderate, $ntfgrps )
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_GROUPS_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($ugroups) > 0 && in_array($row['id'], $ugroups))
			$us = "Y";
		else
			$us = "N";

		if( count($groups) > 0 && in_array($row['id'], $groups))
			$gs = "Y";
		else
			$gs = "N";

		if( count($moderate) > 0 && in_array($row['id'], $moderate))
			$mod = "Y";
		else
			$mod = "N";
		
		if( count($ntfgrps) > 0 && in_array($row['id'], $ntfgrps))
			$nf = "Y";
		else
			$nf = "N";

		$req = "update ".BAB_GROUPS_TBL." set gstorage='".$gs."', ustorage='".$us."', moderate='".$mod."', filenotify='".$nf."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}


/* main */
if( !isset($idx))
	$idx = "list";

if( isset($update) && $update == "update")
	updateGroups($groups, $ugroups, $moderate, $ntfgrps );

switch($idx)
	{
	case "Modify":
	default:
		$babBody->title = bab_translate("File manager");
		listGroups();
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>
