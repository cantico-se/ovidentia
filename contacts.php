<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

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

		function temp($pos)
			{
			global $BAB_SESS_USERID;
			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = $pos[0];
				$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and lastname like '".$this->pos."%' order by lastname, firstname asc";
				$this->fullname = bab_translate("Lastname Firstname");
				$this->urlfullname = $GLOBALS['babUrlScript']."?tg=contacts&idx=chg&pos=".$pos;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and firstname like '".$this->pos."%' order by firstname, lastname asc";
				$this->fullname = bab_translate("Firstname Lastname");
				$this->urlfullname = $GLOBALS['babUrlScript']."?tg=contacts&idx=chg&pos=".$pos;
				}
			$this->email = bab_translate("Email");
			$this->compagny = bab_translate("Compagny");
			$this->htel = bab_translate("Home Tel");
			$this->mtel = bab_translate("Mobile Tel");
			$this->btel = bab_translate("Business Tel");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->allname = bab_translate("All");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query($req);
			if( $this->res )
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=";

			/* find prefered mail account */
			$req = "select * from mail_accounts where owner='".$BAB_SESS_USERID."' and prefered='Y'";
			$res = $this->db->db_query($req);
			if( !$res || $this->db->db_num_rows($res) == 0 )
				{
				$req = "select * from mail_accounts where owner='".$BAB_SESS_USERID."'";
				$res = $this->db->db_query($req);
				}

			if( $this->db->db_num_rows($res) > 0 )
				{
				$arr = $this->db->db_fetch_array($res);
				$this->accid = $arr['id'];
				}
			else
				$this->accid = 0;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = "javascript:Start('".$GLOBALS['babUrlScript']."?tg=contact&idx=modify&item=".$this->arr['id']."&bliste=1');";
				$this->urlmail = "javascript:Start('".$GLOBALS['babUrlScript']."?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->arr['email']."');";
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName( $this->arr['lastname'], $this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName( $this->arr['firstname'], $this->arr['lastname']);
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
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=contacts&idx=list&pos=".$this->ord.$this->selectname;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						{
						$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and lastname like '".$this->selectname."%'";
						}
					else
						{
						$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and firstname like '".$this->selectname."%'";
						}
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
			global $BAB_SESS_USERID;
			$this->message = bab_translate("Are you sure you want to delete those contacts");
			$this->title = "";
			$items = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($item); $i++)
				{
				$req = "select * from contacts where id='".$item[$i]."'and owner='".$BAB_SESS_USERID."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
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
	$arr = explode(",", $items);
	$cnt = count($arr);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		$req = "delete from contacts where id='".$arr[$i]."'";	
		$res = $db->db_query($req);
		}
}

/* main */
if( !isset($pos))
	$pos = "";

if( !isset($idx))
	$idx = "list";

if( isset($action) && $action == "Yes")
	{
	confirmDeleteContacts($items);
	}

switch($idx)
	{
	case "delete":
		$babBody->title = bab_translate("Delete contact");
		contactsDelete($item, $pos);
		$babBody->addItemMenu("list", bab_translate("Contacts"),$GLOBALS['babUrlScript']."?tg=contacts&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), "javascript:Start('".$GLOBALS['babUrlScript']."?tg=contact&idx=create&bliste=1')");
		$babBody->addItemMenu("delete", bab_translate("Delete"), "javascript:(submitForm('delete'))");
		break;

	case "chg":
		if( $pos[0] == "-")
			$pos = $pos[1];
		else
			$pos = "-" .$pos;
		/* no break */
	case "list":
	default:
		$babBody->title = bab_translate("Contacts list");
		$count = listContacts($pos);
		$babBody->addItemMenu("list", bab_translate("Contacts"),$GLOBALS['babUrlScript']."?tg=contacts&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"), "javascript:Start('".$GLOBALS['babUrlScript']."?tg=contact&idx=create&bliste=1')");
		if( $count > 0 )
			{
			$babBody->addItemMenu("delete", bab_translate("Delete"), "javascript:(submitForm('delete'))");
			}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>