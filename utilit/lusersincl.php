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
		var $altbg = true;

		var $pos;

		var $userid;

		var $nickname;

		function temp($pos, $cb)
			{
			global $babBody, $babDB;

			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Login");
			$this->cb = $cb;

			switch ($babBody->nameorder[0]) {
				case "L":
					$this->namesearch = 'lastname';
					$this->namesearch2 = 'firstname';
				break;
				case "F":
				default:
					$this->namesearch = 'firstname';
					$this->namesearch2 = 'lastname';
				break; }

			if( strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				if( $babBody->currentAdmGroup == 0)
					{
					$req = "select * from ".BAB_USERS_TBL." where disabled != '1' and ".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by ".$this->namesearch2.", ".$this->namesearch." asc";
					}
				else
					{
					$req .= "select distinct u.* from ".BAB_USERS_TBL." u, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g where u.disabled != '1' and ug.id_object=u.id and ug.id_group=g.id AND g.lf>='".$babDB->db_escape_string($babBody->currentDGGroup['lf'])."' AND g.lr<='".$babDB->db_escape_string($babBody->currentDGGroup['lr'])."' and u.".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by u.".$this->namesearch2.", u.".$this->namesearch." asc";
					}

				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&pos=".$this->pos."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				if( $babBody->currentAdmGroup == 0)
					{
					$req = "select * from ".BAB_USERS_TBL." where disabled != '1' and ".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
					}
				else
					{
					$req = "select distinct u.* from ".BAB_USERS_TBL." u, ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g where u.disabled != '1' and ug.id_object=u.id and ug.id_group=g.id AND g.lf>='".$babDB->db_escape_string($babBody->currentDGGroup['lf'])."' AND g.lr<='".$babDB->db_escape_string($babBody->currentDGGroup['lr'])."' and u.".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' order by u.".$this->namesearch.", u.".$this->namesearch2." asc";
					}

				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&pos=-".$this->pos."&cb=".$this->cb;
				}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&pos=&cb=".$this->cb;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->urlname = bab_toHtml($this->urlname);
				$this->userid = $this->arr['id'];
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
			global $babBody, $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

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

	
	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$temp = new temp($pos, $cb);

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "lusers.html", "browseusers"));
	printBabBodyPopup();
	die();

	}

function browseArticlesAuthors($pos, $cb)
	{
	global $babBody;
	class temp
		{
		function temp($pos, $cb)
			{
			global $babBody, $babDB;

			$this->allname = bab_translate("All");
			$this->nickname = bab_translate("Login");
			$this->cb = $cb;
			$this->altbg = false;

			switch ($babBody->nameorder[0]) {
				case "L":
					$this->namesearch = 'lastname';
					$this->namesearch2 = 'firstname';
				break;
				case "F":
				default:
					$this->namesearch = 'firstname';
					$this->namesearch2 = 'lastname';
				break; }

			if( strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];

				$req = "select distinct ut.* from ".BAB_USERS_TBL." ut left join ".BAB_ARTICLES_TBL." at on ut.id=at.id_author where at.id_author!=0 and at.id_topic in (".$babDB->quote(array_keys($babBody->topview)).") and ut.".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by ut.".$this->namesearch2.", ut.".$this->namesearch." asc";

				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=".$this->pos."&cb=".$this->cb;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select distinct ut.* from ".BAB_USERS_TBL." ut left join ".BAB_ARTICLES_TBL." at on ut.id=at.id_author where at.id_author!=0 and at.id_topic in (".$babDB->quote(array_keys($babBody->topview)).")  and ut.".$this->namesearch." like '".$babDB->db_escape_like($this->pos)."%' order by ut.".$this->namesearch.", ut.".$this->namesearch2." asc";

				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=-".$this->pos."&cb=".$this->cb;
				}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=&cb=".$this->cb;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->firstlast = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);
				$this->firstlast = str_replace("'", "\'", $this->firstlast);
				$this->firstlast = str_replace('"', "'+String.fromCharCode(34)+'",$this->firstlast);
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->urlname = bab_toHtml($this->urlname);
				$this->userid = $this->arr['id'];
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
			global $babBody, $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&pos=".$this->ord.$this->selectname."&cb=".$this->cb;

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

	
	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$temp = new temp($pos, $cb);

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "lusers.html", "browseusers"));
	printBabBodyPopup();
	die();

	}
?>