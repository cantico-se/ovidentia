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
		var $grpdgtxt;
		var $grpdgid;
		var $grpdgname;
		var $count;
		var $res;
		var $selected;
		var $bdggroup;


		function temp()
			{
			global $babBody, $babDB;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->managertext = bab_translate("Manager");
			$this->useemail = bab_translate("Use email");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add Group");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->grpdgtxt = bab_translate("Delegation group");
			$this->noselected = "selected";
			$this->yesselected = "";
			$this->grpid = "";
			$this->grpname = "";
			$this->grpdesc = "";
			$this->managerval = "";
			$this->managerid = "";
			$this->bdel = false;
			$this->bdggroup = false;
			$this->tgval = "groups";
			$this->selected = "";
			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
				{
				$this->res = $babDB->db_query("select * from ".BAB_DG_GROUPS_TBL."");
				$this->count = $babDB->db_num_rows($this->res);
				if( $this->count > 0 )
					$this->bdggroup = true;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;	
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->grpdgname = $arr['name'];
				$this->grpdgid = $arr['id'];
				$i++;
				return true;
				}
			return false;
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
		var $descval;
		var $dgtxt;
		var $dgval;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $checked;

		function temp()
			{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->mail = bab_translate("Mail");
			$this->description = bab_translate("Description");
			$this->manager = bab_translate("Manager");
			$this->dgtxt = bab_translate("Delegation");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_GROUPS_TBL." where id > 2 and id_dgowner='".$babBody->currentAdmGroup."' order by id asc";
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
				$this->descval = $this->arr['description'];
				$this->managername = bab_getUserName($this->arr['manager']);
				if( $this->arr['mail'] == "Y")
					$this->arr['mail'] = bab_translate("Yes");
				else
					$this->arr['mail'] = bab_translate("No");
				if( $this->arr['id_dggroup'] != 0 )
					{
					list($this->dgval) = $this->db->db_fetch_row($this->db->db_query("select name from ".BAB_DG_GROUPS_TBL." where id='".$this->arr['id_dggroup']."'"));
					}
				else
					$this->dgval = '';					
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
		var $notes;
		var $contacts;
		var $directory;
		var $url;
		var $urlname;
		var $group;
			
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $burl;
		var $persdiskspace;

		var $bdgmail;
		var $bdgcalendar;
		var $bpcalendar;
		var $bdgnotes;
		var $bdgcontacts;
		var $bdgdirectories;
		var $bdgpds;

		function temp()
			{
			global $babBody;
			$this->fullname = bab_translate("Groups");
			$this->mail = bab_translate("Mail");
			$this->calendar = bab_translate("Group calendar");
			$this->pcalendar = bab_translate("Personal calendar");
			$this->notes = bab_translate("Notes");
			$this->contacts = bab_translate("Contacts");
			$this->persdiskspace = bab_translate("Personal disk space");
			$this->directory = bab_translate("Site directory");
			$this->modify = bab_translate("Update");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");

			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
				{
				$this->bdgmail = true;
				$this->bdgcalendar = true;
				$this->bpcalendar = true;
				$this->bdgnotes = true;
				$this->bdgcontacts = true;
				$this->bdgdirectories = true;
				$this->bdgpds = true;
				}
			else
				{
				if( $babBody->currentDGGroup['mails'] == 'Y' )
					$this->bdgmail = true;
				else
					$this->bdgmail = false;

				if( $babBody->currentDGGroup['calendars'] == 'Y' )
					{
					$this->bdgcalendar = true;
					$this->bpcalendar = true;
					}
				else
					{
					$this->bdgcalendar = false;
					$this->bpcalendar = false;
					}

				$this->bdgnotes = true;
				$this->bdgcontacts = true;

				if( $babBody->currentDGGroup['directories'] == 'Y' )
					$this->bdgdirectories = true;
				else
					$this->bdgdirectories = false;

				if( $babBody->currentDGGroup['filemanager'] == 'Y' )
					$this->bdgpds = true;
				else
					$this->bdgpds = false;
				}
			$req = "select * from ".BAB_GROUPS_TBL." where id!='2' and id_dgowner='".$babBody->currentAdmGroup."' order by id asc";
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
				if( $this->arr['ustorage'] == "Y")
					$this->pdscheck = "checked";
				else
					$this->pdscheck = "";

				if( $this->arr['directory'] == "Y")
					$this->dircheck = "checked";
				else
					$this->dircheck = "";
				if( $this->arr['id'] < 3 )
					{
					$this->urlname = bab_getGroupName($this->arr['id']);
					}
				else
					{
					$this->urlname = $this->arr['name'];
					}

				if( $this->arr['pcalendar'] == "Y")
					$this->pcalcheck = "checked";
				else
					$this->pcalcheck = "";

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

function addGroup($name, $description, $managerid, $bemail, $grpdg)
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
		return false;
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
		if( empty($grpdg))
			$grpdg = 0;
		$req = "insert into ".BAB_GROUPS_TBL." (name, description, mail, manager, id_dggroup, id_dgowner) VALUES ('" .$name. "', '" . $description. "', 'N', '" . $managerid. "', '".$grpdg. "', '".$babBody->currentAdmGroup."')";
		$db->db_query($req);
		$id = $db->db_insert_id();

		$req = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$id. "', 'Y', '2')";
		bab_callAddonsFunction('onGroupCreate', $id);
		$db->db_query($req);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$id);
		return true;
		}
	}

function saveGroupsOptions($mailgrpids, $calgrpids, $notgrpids, $congrpids, $pdsgrpids, $dirgrpids, $calperids)
{

	global $babBody;

	$db = $GLOBALS['babDB'];

	$db->db_query("update ".BAB_GROUPS_TBL." set mail='N', notes='N', contacts='N', ustorage='N', directory='N', pcalendar='N' where  id_dgowner='".$babBody->currentAdmGroup."'"); 

	for( $i=0; $i < count($mailgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set mail='Y' where id='".$mailgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($notgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set notes='Y' where id='".$notgrpids[$i]."'"); 
	}

	for( $i=0; $i < count($congrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set contacts='Y' where id='".$congrpids[$i]."'"); 
	}

	for( $i=0; $i < count($pdsgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set ustorage='Y' where id='".$pdsgrpids[$i]."'"); 
	}

	$db->db_query("update ".BAB_CALENDAR_TBL." set actif='N' where type='2'");
	for( $i = 0; $i < count($calgrpids); $i++)
	{
		$res = $db->db_query("update ".BAB_CALENDAR_TBL." set actif='Y' where owner='".$calgrpids[$i]."' and type='2'");
	}

	for( $i=0; $i < count($dirgrpids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set directory='Y' where id='".$dirgrpids[$i]."'");
		$res = $db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$dirgrpids[$i]."'");
		if( !$res || $db->db_num_rows($res) == 0 )
		{
			$db->db_query("insert into ".BAB_DB_DIRECTORIES_TBL." (name, description, id_group, id_dgowner) values ('".bab_getGroupName($dirgrpids[$i])."','','".$dirgrpids[$i]."', '".$babBody->currentAdmGroup."')");
		}
	}

	for( $i = 0; $i < count($calperids); $i++)
	{
		$db->db_query("update ".BAB_GROUPS_TBL." set pcalendar='Y' where id='".$calperids[$i]."'"); 
	}


}

/* main */
if( !isset($idx))
	$idx = "List";

if( isset($add) && ($babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y'))
	addGroup($name, $description, $managerid, $bemail, $grpdg);

if( isset($update) && $update == "options" && ($babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y'))
	saveGroupsOptions($mailgrpids, $calgrpids, $notgrpids, $congrpids, $pdsgrpids, $dirgrpids, $calperids);

switch($idx)
	{
	case "brow":
		include_once $babInstallPath."utilit/grpincl.php";
		browseGroups($cb);
		exit;
		break;
	case "options":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			groupsOptions();
			$babBody->title = bab_translate("Options");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
			$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "Create":
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			groupCreate();
			$babBody->title = bab_translate("Create a group");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
			$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	case "List":
	default:
		if( $babBody->isSuperAdmin || $babBody->currentDGGroup['groups'] == 'Y')
		{
			groupList();
			$babBody->title = bab_translate("Groups list");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=groups&idx=options");
			$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>