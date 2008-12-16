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

function aclUsersGroups($pos, $table, $target, $idgroup)
	{
	global $babBody;
	class aclusersgroups
		{
		var $fullname;
		var $urlname;
		var $url;
		var $email;
		var $status;
				
		var $fullnameval;
		var $emailval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $urlmail;

		var $grp;
		var $group;
		var $groupurl;
		var $checked;
		var $userid;
		var $usert;

		var $urltrail;

		function aclusersgroups($pos, $table, $target, $idgroup)
			{
			global $babBody;
			switch ($babBody->nameorder[0]) {
			case "L":
				$this->namesearch = "lastname";
				$this->namesearch2 = "firstname";
			break; 
			case "F":
			default:
				$this->namesearch = "firstname";
				$this->namesearch2 = "lastname";
			break;}

			$this->allname = bab_translate("All");
			$this->update = bab_translate("Update");
			$this->db = $GLOBALS['babDB'];
			$this->table = $table;
			$this->target = $target;
			$this->idgroup = $idgroup;
			$this->urltrail = "&table=".$this->table."&target=".$this->target."&idgroup=".$this->idgroup;
			$this->userst =  '';

			if( mb_strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = mb_strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				$req = "select * from ".BAB_USERS_TBL." where ".$this->namesearch2." like '".$this->pos."%' order by ".$this->namesearch2.", ".$this->namesearch." asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=aclug&idx=chg&pos=".$this->ord.$this->pos.$this->urltrail;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select * from ".BAB_USERS_TBL." where ".$this->namesearch." like '".$this->pos."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"), bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=aclug&idx=chg&pos=".$this->ord.$this->pos.$this->urltrail;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=aclug&idx=list&pos=".$this->urltrail;

			// dg group members
			$this->dg_group_members = array();
			$res = $this->db->db_query("select id_object from ".$this->table." where id_group='".$this->idgroup."'");
			while (list($id_object) = $this->db->db_fetch_array($res))
				{
				$this->dg_group_members[$id_object] = $id_object;
				}

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->userid = $this->arr['id'];

				if( isset($this->dg_group_members[$this->userid]))
					{
					$this->checked = "checked";
					if( empty($this->userst))
						$this->userst = $this->arr['id'];
					else
						$this->userst .= ",".$this->arr['id'];
					}
				else
					{
					$this->checked = "";
					}

				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=aclug&idx=list&pos=".$this->ord.$this->selectname.$this->urltrail;
				$this->selected = 0;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new aclusersgroups($pos, $table, $target, $idgroup);
	echo bab_printTemplate($temp, "aclug.html", "userslist");
	return $temp->count;
	}


function aclugUnload($redirect)
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;

		function temp($redirect)
			{
			global $arrfile;
			$this->message = bab_translate("Your list has been updated");
			$this->close = bab_translate("Close");
			$this->redirecturl = $redirect;	
			}
		}

	$temp = new temp($redirect);
	echo bab_printTemplate($temp, "aclug.html", "unload");
	}

/* main */
if( $idx == "chg")
{
	if( mb_strlen($pos) > 0 && $pos[0] == "-" )
		$pos = mb_strlen($pos)>1? $pos[1]: '';
	else
		$pos = "-" .$pos;
	$idx = "list";
}


switch($idx)
	{
	case "unload":
		aclugUnload($url);
		exit;
		break;

	case "list":
	default:
		if( !isset($pos)) $pos = 'A';
		aclUsersGroups($pos, $table, $target, $idgroup);
		exit;
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>