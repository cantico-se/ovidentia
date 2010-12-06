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
		var $altbg = true;
		var $userid;

		var $nickname;

		function temp($pos, $cb)
			{
			global $babBody, $babDB;

			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Login ID");
			$this->cb = $cb;

			if( !bab_isUserAdministrator() && $babBody->babsite['browse_users'] == 'N')
				{
				$req = "select ".BAB_GROUPS_TBL.".id from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group";
				$resgroups = $babDB->db_query($req);

				$reqa = "select distinct ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname, ".BAB_USERS_TBL.".nickname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed ='1' and disabled='0'";
				if( $babDB->db_num_rows($resgroups) > 0 )
					{
					$arr = $babDB->db_fetch_array($resgroups);
					$reqa .= " and ( ".BAB_USERS_GROUPS_TBL.".id_group='".$babDB->db_escape_string($arr['id'])."'";
					while($arr = $babDB->db_fetch_array($resgroups))
						{
						$reqa .= " or ".BAB_USERS_GROUPS_TBL.".id_group='".$babDB->db_escape_string($arr['id'])."'"; 
						}
					$reqa .= ") and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id";
					}
				}
			else
				$reqa = "select * from ".BAB_USERS_TBL." where is_confirmed ='1' and disabled='0'";

			if( mb_strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = isset($pos[1]) ? $pos[1] : '';
				$this->ord = $pos[0];
				$reqa .= " and lastname like '".$babDB->db_escape_like($this->pos)."%' order by lastname, firstname asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"), bab_translate("Firstname"));
				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=".$this->pos."&cb=".$cb);
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$reqa .= " and firstname like '".$babDB->db_escape_like($this->pos)."%' order by firstname, lastname asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=-".$this->pos."&cb=".$cb);
				}
			$this->res = $babDB->db_query($reqa);
			$this->count = $babDB->db_num_rows($this->res);

			if( empty($this->pos))
				{
				$this->allselected = 1;
				}
			else
				{
				$this->allselected = 0;
				}
			$this->allurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=&cb=".$cb);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$this->arr['id']."&pos=".$this->ord.$this->pos."&cb=").$this->cb;
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = bab_toHtml($this->firstlast, BAB_HTML_JS);
				if( $this->ord == "-" )
					{
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
					}
				else
					{
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
					}
				$this->urlname = bab_toHtml($this->urlname);
				$this->userid = bab_toHtml($this->arr['id']);
				$this->nicknameval = bab_toHtml($this->arr['nickname']);
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $BAB_SESS_USERID, $babDB;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = mb_substr($t, $k, 1);
				$this->selecturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&pos=".$this->ord.$this->selectname."&cb=").$this->cb;

				if( $this->pos == $this->selectname)
					{
					$this->selected = 1;
					}
				else 
					{
					if( $this->ord == "-" )
						{
						$req = "select * from ".BAB_USERS_TBL." where lastname like '".$babDB->db_escape_like($this->selectname)."%'";
						}
					else
						{
						$req = "select * from ".BAB_USERS_TBL." where firstname like '".$babDB->db_escape_like($this->selectname)."%'";
						}
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0 )
						{
						$this->selected = 0;
						}
					else
						{
						$this->selected = 1;
						}
					}
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$temp = new temp($pos, $cb);

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "lusers.html", "browseusers"));
	printBabBodyPopup();
	die();
	}

switch($idx)
	{	
	case 'brow':
		$pos = bab_gp('pos');
		$cb = bab_gp('cb');
		browseUsers($pos, $cb);
		exit;
		break;
	}
?>