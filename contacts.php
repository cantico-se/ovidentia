<?php

function listContacts($pos)
	{
	global $body;
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
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $urlmail;

		function temp($pos)
			{
			global $BAB_SESS_USERID;
			$this->pos = $pos;
			$this->fullname = babTranslate("Full Name");
			$this->email = babTranslate("Email");
			$this->compagny = babTranslate("Compagny");
			$this->htel = babTranslate("Home Tel");
			$this->mtel = babTranslate("Mobile Tel");
			$this->btel = babTranslate("Business Tel");
			$this->uncheckall = babTranslate("Uncheck all");
			$this->checkall = babTranslate("Check all");
			$this->allname = babTranslate("All");
			$this->db = new db_mysql();
			$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and lastname like '".$pos."%' order by lastname desc";
			$this->res = $this->db->db_query($req);
			if( $this->res )
				$this->count = $this->db->db_num_rows($this->res);
			else
				$this->count = 0;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS[babUrl]."index.php?tg=contacts&idx=list&pos=";

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
				$this->accid = $arr[id];
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
				$this->url = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=contact&idx=modify&item=".$this->arr[id]."&bliste=1');";
				$this->urlmail = $GLOBALS[babUrl]."index.php?tg=mail&idx=compose&accid=".$this->accid."&to=".$this->arr[email];
				$this->urlname = $this->arr[lastname]. " ". $this->arr[firstname];
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
				$this->selecturl = $GLOBALS[babUrl]."index.php?tg=contacts&idx=list&pos=".$this->selectname;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					$req = "select * from contacts where owner='".$BAB_SESS_USERID."' and lastname like '".$this->selectname."%'";
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
	$body->babecho(	babPrintTemplate($temp, "contacts.html", "contactslist"));
	return $temp->count;
	}

function contactsDelete($item, $pos)
	{
	global $body, $idx;

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
			$this->message = babTranslate("Are you sure you want to delete those contacts");
			$this->title = "";
			$items = "";
			$db = new db_mysql();
			for($i = 0; $i < count($item); $i++)
				{
				$req = "select * from contacts where id='".$item[$i]."'and owner='".$BAB_SESS_USERID."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". $arr[firstname]. " " .$arr[lastname];
					$items .= $arr[id];
					}
				if( $i < count($item) -1)
					$items .= ",";
				}
			$this->warning = babTranslate("WARNING: This operation will delete contacts and their references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=contacts&idx=list&pos=".$pos."&action=Yes&items=".$items;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=contacts&idx=list&pos=".$pos;
			$this->no = babTranslate("No");
			}
		}

	if( count($item) <= 0)
		{
		$body->msgerror = babTranslate("Please select at least one item");
		listContacts($pos);
		$idx = "list";
		return;
		}
	$tempa = new tempa($item, $pos);
	$body->babecho(	babPrintTemplate($tempa,"warning.html", "warningyesno"));
	}


function confirmDeleteContacts($items)
{
	$arr = explode(",", $items);
	$cnt = count($arr);
	$db = new db_mysql();
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
		$body->title = babTranslate("Delete contact");
		contactsDelete($item, $pos);
		$body->addItemMenu("list", babTranslate("Contacts"),$GLOBALS[babUrl]."index.php?tg=contacts&idx=list");
		$body->addItemMenu("create", babTranslate("Create"), "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=contact&idx=create&bliste=1')");
		$body->addItemMenu("delete", babTranslate("Delete"), "javascript:(submitForm('delete'))");
		break;

	case "list":
	default:
		$body->title = babTranslate("Contacts list");
		$count = listContacts($pos);
		$body->addItemMenu("list", babTranslate("Contacts"),$GLOBALS[babUrl]."index.php?tg=contacts&idx=list");
		$body->addItemMenu("create", babTranslate("Create"), "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=contact&idx=create&bliste=1')");
		if( $count > 0 )
			{
			$body->addItemMenu("delete", babTranslate("Delete"), "javascript:(submitForm('delete'))");
			}
		break;
	}

$body->setCurrentItemMenu($idx);
?>