<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/grpincl.php";

function groupCreate()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $managertext;
		var $useemail;
		var $no;
		var $yes;
		var $add;
		var $usersbrowurl;
		var $grpid;
		var $noselected;
		var $yesselected;
		var $tgval;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->managertext = bab_translate("Manager");
			$this->useemail = bab_translate("Use email");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add Group");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->noselected = "selected";
			$this->yesselected = "";
			$this->grpid = "";
			$this->grpname = "";
			$this->grpdesc = "";
			$this->managerval = "";
			$this->managerid = "";
			$this->bdel = false;
			$this->tgval = "groups";
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"groups.html", "groupscreate"));
	}

function groupList()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $mail;
		var $urlname;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $checked;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->mail = bab_translate("Mail");
			$this->description = bab_translate("Description");
			$this->manager = bab_translate("Manager");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_GROUPS_TBL." where id > 2 order by id asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->checked = "checked";
				else
					$this->checked = "";
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$this->managername = bab_getUserName($this->arr['manager']);
				if( $this->arr['mail'] == "Y")
					$this->arr['mail'] = bab_translate("Yes");
				else
					$this->arr['mail'] = bab_translate("No");
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "groups.html", "groupslist"));
	}

function groupsOptions()
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $mail;
		var $calendar;
		var $vacation;
		var $notes;
		var $contacts;
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
			$this->mail = bab_translate("Mail");
			$this->calendar = bab_translate("Calendar");
			$this->vacation = bab_translate("Vacation");
			$this->notes = bab_translate("Notes");
			$this->contacts = bab_translate("Contacts");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$req = "select * from ".BAB_GROUPS_TBL." where id!='2' order by id asc";
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
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->grpid = $this->arr['id'];

				if( $this->arr['vacation'] == "Y")
					$this->vaccheck = "checked";
				else
					$this->vaccheck = "";
				if( $this->arr['mail'] == "Y")
					$this->mailcheck = "checked";
				else
					$this->mailcheck = "";
				if( $this->arr['notes'] == "Y")
					$this->notescheck = "checked";
				else
					$this->notescheck = "";
				if( $this->arr['contacts'] == "Y")
					$this->concheck = "checked";
				else
					$this->concheck = "";
				if( $this->arr['id'] < 3 )
					$this->urlname = bab_getGroupName($this->arr['id']);
				else
					$this->urlname = $this->arr['name'];

				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$this->arr['id']."' and type='2'"));
				if( $arr['actif'] == "Y")
					$this->calcheck = "checked";
				else
					$this->calcheck = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "groups.html", "groupsoptions"));
	}

function addGroup($name, $description, $managerid, $bemail)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	$db = $GLOBALS['babDB'];

	$req = "select * from ".BAB_GROUPS_TBL." where name='$name'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This group already exists");
		}
	else
		{
		if( !bab_isMagicQuotesGpcOn())
			{
			$description = addslashes($description);
			$name = addslashes($name);
			}
		if( empty($managerid))
			$managerid = 0;
		$req = "insert into ".BAB_GROUPS_TBL." (name, description, mail, manager) VALUES ('" .$name. "', '" . $description. "', '". $bemail. "', '" . $managerid. "')";
		$db->db_query($req);
		$id = $db->db_insert_id();

		$req = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$id. "', 'Y', '2')";
		bab_callAddonsFunction('onGroupCreate', $id);
		$db->db_query($req);
		}
	}

function saveGroupsOptions($mailgrpids, $vacgrpids, $calgrpids, $notgrpids, $congrpids)
{

	$db = $GLOBALS['babDB'];

	$db->db_query("update ".BAB_GROUPS_TBL." set mail='N', vacation='N', notes='N', contacts='N'"); 
	for( $i=0; $i < count($mailgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set mail='Y' where id='".$mailgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($vacgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set vacation='Y' where id='".$vacgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($notgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set notes='Y' where id='".$notgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($congrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set contacts='Y' where id='".$congrpids[$i]."'"); 
	}

	$db->db_query("update ".BAB_CALENDAR_TBL." set actif='N' where type='2'");
	for( $i = 0; $i < count($calgrpids); $i++)
	{
		$res = $db->db_query("update ".BAB_CALENDAR_TBL." set actif='Y' where owner='".$calgrpids[$i]."' and type='2'");
	}
}

/* main */
if( !isset($idx))
	$idx = "List";

if( isset($add))
	addGroup($name, $description, $managerid, $bemail);

if( isset($update) && $update == "options")
	saveGroupsOptions($mailgrpids, $vacgrpids, $calgrpids, $notgrpids, $congrpids);

switch($idx)
	{
	case "options":
		groupsOptions();
		$babBody->title = bab_translate("Options");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		break;
	case "Create":
		groupCreate();
		$babBody->title = bab_translate("Create a group");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		break;
	case "List":
	default:
		groupList();
		$babBody->title = bab_translate("Groups list");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>