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
function browseUsers($pos, $cb)
	{
	global $babBody;
	class temp
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

		var $userid;

		var $nickname;

		function temp($pos, $cb)
			{
			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Nickname");
			$this->db = $GLOBALS['babDB'];
			$this->cb = $cb;

			if( !bab_isUserAdministrator())
				{
				$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
				$resgroups = $this->db->db_query($req);

				$reqa = "select distinct ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".nickname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed ='1' and disabled='0'";
				if( $this->db->db_num_rows($resgroups) > 0 )
					{
					$arr = $this->db->db_fetch_array($resgroups);
					$reqa .= " and ( ".BAB_USERS_GROUPS_TBL.".id_group='".$arr['id']."'";
					while($arr = $this->db->db_fetch_array($resgroups))
						{
						$reqa .= " or ".BAB_USERS_GROUPS_TBL.".id_group='".$arr['id']."'"; 
						}
					$reqa .= ") and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id";
					}
				}
			else
				$reqa = "select * from ".BAB_USERS_TBL." where is_confirmed ='1' and disabled='0'";

			if( strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = isset($pos[1]) ? $pos[1] : '';
				$this->ord = $pos[0];
				$reqa .= " and lastname like '".$this->pos."%' order by lastname, firstname asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"), bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=".$this->pos."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$reqa .= " and firstname like '".$this->pos."%' order by firstname, lastname asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=-".$this->pos."&cb=".$this->cb;
				}
			$this->res = $this->db->db_query($reqa);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=&cb=".$this->cb;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$this->arr['id']."&pos=".$this->ord.$this->pos."&cb=".$this->cb;
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->userid = $this->arr['id'];
				$this->nicknameval = $this->arr['nickname'];
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
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						$req = "select * from ".BAB_USERS_TBL." where lastname like '".$this->selectname."%'";
					else
						$req = "select * from ".BAB_USERS_TBL." where firstname like '".$this->selectname."%'";
					$res = $this->db->db_query($req);
					if( $this->db->db_num_rows($res) > 0 )
						$this->selected = 0;
					else
						$this->selected = 1;
					}
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos, $cb);
	echo bab_printTemplate($temp, "lusers.html", "browseusers");
	}

switch($idx)
	{	
	case "brow":
		if( !isset($pos)) { $pos = '';}
		if( !isset($cb)) { $cb = '';}
		browseUsers($pos, $cb);
		exit;
		break;
	}
?>