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
* @internal SEC1 NA 15/12/2006 FULL
*/

include_once 'base.php';

function listContacts($pos)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $email;
		var $compagny;
		var $btel;
		var $htel;
		var $mtel;
		var $checkall;
		var $uncheckall;
		var $pos;
		var $ord;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $urlmail;
		var $altbg = true;

		function temp($pos)
			{
			global $BAB_SESS_USERID,$babBody, $babDB;

			switch ($babBody->nameorder[0]) {
			case 'L':
				$this->namesearch = 'lastname';
				$this->namesearch2 = 'firstname';
			break; 
			case 'F':
			default:
				$this->namesearch = 'firstname';
				$this->namesearch2 = 'lastname';
			break;}

			if( mb_substr($pos,0,1) == '-' )
				{
				$this->pos = '-';
				$this->ord = mb_substr($pos,1);
				$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and ".$this->namesearch2." like '".$babDB->db_escape_like($this->pos)."%' order by ".$babDB->db_escape_string($this->namesearch2).", ".$babDB->db_escape_string($this->namesearch)." asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->urlfullname = bab_toHtml($GLOBALS['babUrlScript']."?tg=contacts&idx=chg&pos=".$pos);
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and ".$babDB->db_escape_string($this->namesearch)." like '".$babDB->db_escape_like($this->pos)."%' order by ".$babDB->db_escape_string($this->namesearch).", ".$babDB->db_escape_string($this->namesearch2)." asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"), bab_translate("Lastname"));
				$this->urlfullname = bab_toHtml($GLOBALS['babUrlScript']."?tg=contacts&idx=chg&pos=".$pos);
				}
			$this->pos = bab_toHtml($this->pos);
			$this->email = bab_translate("Email");
			$this->compagny = bab_translate("Compagny");
			$this->htel = bab_translate("Home Tel");
			$this->mtel = bab_translate("Mobile Tel");
			$this->btel = bab_translate("Business Tel");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->allname = bab_translate("All");
			$this->addcontact = bab_translate("Add");
			$this->deletealt = bab_translate("Delete Contacts");
			$this->res = $babDB->db_query($req);
			if( $this->res )
				$this->count = $babDB->db_num_rows($this->res);
			else
				$this->count = 0;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=");
			$this->addurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=contact&idx=create&bliste=1");

			/* find prefered mail account */
			$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and prefered='Y'";
			$res = $babDB->db_query($req);
			if( !$res || $babDB->db_num_rows($res) == 0 )
				{
				$req = "select * from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
				$res = $babDB->db_query($req);
				}

			if( $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->accid = $arr['id'];
				}
			else
				$this->accid = 0;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->arr_id = bab_toHtml($this->arr['id']);
				$this->arr_email = bab_toHtml($this->arr['email']);
				$this->arr_compagny = bab_toHtml($this->arr['compagny']);
				$this->arr_businesstel = bab_toHtml($this->arr['businesstel']);
				$this->arr_mobiletel = bab_toHtml($this->arr['mobiletel']);

				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=contact&idx=modify&item=".$this->arr['id']."&bliste=1");
				$this->urlmail = bab_toHtml($GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->arr['email']);
				if( $this->ord == "-" )
					$this->urlname = bab_toHtml(bab_composeUserName( $this->arr['lastname'], $this->arr['firstname']));
				else
					$this->urlname = bab_toHtml(bab_composeUserName( $this->arr['firstname'], $this->arr['lastname']));
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
				$this->selectname = bab_toHtml(mb_substr($t, $k, 1));
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=".$this->ord.$this->selectname;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						{
						$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and ".$babDB->db_escape_string($this->namesearch2)." like '".$babDB->db_escape_like($this->selectname)."%'";
						}
					else
						{
						$req = "select * from ".BAB_CONTACTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' and ".$babDB->db_escape_string($this->namesearch)." like '".$babDB->db_escape_like($this->selectname)."%'";
						}
					$res = $babDB->db_query($req);
					if( $babDB->db_num_rows($res) > 0 )
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

	$temp = new temp($pos);
	$babBody->babecho(	bab_printTemplate($temp, "contacts.html", "contactslist"));
	return $temp->count;
	}

function contactsDelete($item, $pos)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($item, $pos)
			{
			global $BAB_SESS_USERID, $babDB;
			$this->message = bab_translate("Are you sure you want to delete those contacts");
			$this->title = "";
			$items = "";
			for($i = 0; $i < count($item); $i++)
				{
				$req = "select * from ".BAB_CONTACTS_TBL." where id='".$babDB->db_escape_string($item[$i])."'and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";	
				$res = $babDB->db_query($req);
				if( $babDB->db_num_rows($res) > 0)
					{
					$arr = $babDB->db_fetch_array($res);
					$this->title .= "<br>". bab_composeUserName($arr['firstname'], $arr['lastname']);
					$items .= $arr['id'];
					}
				if( $i < count($item) -1)
					$items .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will delete contacts and their references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=".$pos."&action=Yes&items=".$items;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=".$pos;
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		listContacts($pos);
		$idx = "list";
		return;
		}
	$tempa = new tempa($item, $pos);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}


function confirmDeleteContacts($items)
{
	global $BAB_SESS_USERID, $babDB;
	$arr = explode(",", $items);
	$cnt = count($arr);
	for($i = 0; $i < $cnt; $i++)
		{
		$req = "delete from ".BAB_CONTACTS_TBL." where id='".$babDB->db_escape_string($arr[$i])."' and owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";	
		$res = $babDB->db_query($req);
		}
}

/* main */
if( !$BAB_SESS_LOGGED || !bab_contactsAccess())
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');
$pos = bab_rp('pos', '');

if( 'Yes' == bab_gp('action'))
	{
	confirmDeleteContacts(bab_gp('items'));
	}

switch($idx)
	{
	case 'delete':
		$babBody->title = bab_translate("Delete contact");
		contactsDelete(bab_pp('item'), $pos);
		$babBody->addItemMenu("list", bab_translate("Contacts"),$GLOBALS['babUrlScript']."?tg=contacts&idx=list");
		break;

	case 'chg':
		if( !empty($pos) && $pos[0] == '-')
			{
			$pos = $pos[1];
			}
		else
			{
			$pos = '-' .$pos;
			}
		/* no break */
	case 'list':
	default:
		$babBody->title = bab_translate("Contacts list");
		$count = listContacts($pos);
		$babBody->addItemMenu("list", bab_translate("Contacts"),$GLOBALS['babUrlScript']."?tg=contacts&idx=list");
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserContacts');
?>
