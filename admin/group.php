<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/grpincl.php";
include $babInstallPath."utilit/fileincl.php";

function groupModify($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid group !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $managertext;
		var $managerval;
		var $useemail;
		var $no;
		var $yes;
		var $noselected;
		var $yesselected;
		var $add;
		var $delete;
		var $bdel;

		var $usersbrowurl;
		var $grpid;
		var $noselected;
		var $yesselected;

		function temp($id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->managertext = bab_translate("Manager");
			$this->useemail = bab_translate("Use email");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Modify Group");
			$this->delete = bab_translate("Delete");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_GROUPS_TBL." where id='$id'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->grpid = $id;
			$this->grpname = $arr['name'];
			$this->grpdesc = $arr['description'];
			if( $arr['mail'] == "Y")
				{
				$this->noselected = "";
				$this->yesselected = "selected";
				}
			else
				{
				$this->noselected = "selected";
				$this->yesselected = "";
				}
			$req = "select * from ".BAB_USERS_TBL." where id='".$arr['manager']."'";
			$res = $db->db_query($req);
			if( $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$this->managerval = bab_composeUserName($arr['firstname'], $arr['lastname']);
				$this->managerid = $arr['id'];
				}
			else
				{
				$this->managerid = "";
				$this->managerval = "";
				}
			if( $id > 3 )
				$this->bdel = true;
			else
				$this->bdel = false;
			$this->tgval = "group";
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"groups.html", "groupscreate"));
	}

function groupMembers($id)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $url;
		var $urlname;
		var $idgroup;
		var $group;
		var $grpid;
		var $primary;
		var $deletealt;
			
		var $arr = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp($id)
			{
			$this->grpid = $id;
			$this->fullname = bab_translate("Full Name");
			$this->deletealt = bab_translate("Delete group's members");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->idgroup = $id;
			$this->group = bab_getGroupName($id);
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_USERS_GROUPS_TBL." where id_group= '$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr['isprimary'] == "Y")
					$this->primary = "Y";
				else
					$this->primary = "";
				$db = $GLOBALS['babDB'];
				$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['id_object']."'";
				$result = $db->db_query($req);
				$this->arr = $db->db_fetch_array($result);
				$this->url = $GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$this->arr['id'];
				$this->urlname = bab_composeUserName($this->arr['firstname'], $this->arr['lastname']);
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "groups.html", "memberslist"));
	}

function groupDelete($id)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this group");
			$this->title = bab_getGroupName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the group with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=group&idx=Delete&group=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function deleteMembers($users, $item)
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

		function tempa($users, $item)
			{
			global $BAB_SESS_USERID;
			$this->message = bab_translate("Are you sure you want to delete those members");
			$this->title = "";
			$names = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($users); $i++)
				{
				$req = "select * from ".BAB_USERS_TBL." where id='".$users[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". bab_composeUserName($arr['firstname'], $arr['lastname']);
					$names .= $arr['id'];
					}
				if( $i < count($users) -1)
					$names .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will delete members and their references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=group&idx=Deletem&item=".$item."&action=Yes&names=".$names;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item;
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		groupMembers($pos);
		$idx = "Members";
		return;
		}
	$tempa = new tempa($users, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}

function modifyGroup($name, $description, $managerid, $bemail, $grpid)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$name = addslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_GROUPS_TBL." where id='".$grpid."'"));
	$res = $db->db_query("select * from ".BAB_GROUPS_TBL." where id!='".$grpid."' and name='".$name."'");
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("Group with the same name already exists!");
		return false;
		}
	else
		{
		if( empty($managerid))
			$managerid = 0;

		$query = "update ".BAB_GROUPS_TBL." set name='".$name."', description='".$description."', mail='".$bemail."', manager='".$managerid."' where id='".$grpid."'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List");
	return true;
	}


function confirmDeleteMembers($item, $names)
{
	if( !empty($names))
	{
		$arr = explode(",", $names);
		$cnt = count($arr);
		$db = $GLOBALS['babDB'];
		for($i = 0; $i < $cnt; $i++)
			{
			$req = "delete from ".BAB_USERS_GROUPS_TBL." where id_object='".$arr[$i]."' and id_group='".$item."'";	
			$res = $db->db_query($req);
			}
	}
}

function confirmDeleteGroup($id)
	{
	if( $id <= 3)
		return;
	$db = $GLOBALS['babDB'];
	$db->db_query("delete from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_TOPICSCOM_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_TOPICSSUB_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_SECTIONS_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_FAQCAT_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_USERS_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_CATEGORIESCAL_TBL." where id_group='$id'");
	$db->db_query("delete from ".BAB_FMDOWNLOAD_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_FMUPDATE_GROUPS_TBL." where id_group='$id'");	
	$db->db_query("delete from ".BAB_FMUPLOAD_GROUPS_TBL." where id_group='$id'");	

	$res = $db->db_query("select * from ".BAB_RESOURCESCAL_TBL." where id_group='$id'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		
		while( $arr = $db->db_fetch_array($res))
			{
			$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='3'");
			$r = $db->db_fetch_array($res);

			// delete resource's events
			$res = $db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$r['id']."'");	

			// delete resource from calendar
			$res = $db->db_query("delete from ".BAB_CALENDAR_TBL." where owner='".$arr['id']."' and type='3'");	

			// delete resource
			$res = $db->db_query("delete from ".BAB_RESOURCESCAL_TBL." where id_group='$id'");
			}
		}

	$res = $db->db_query("select * from ".BAB_CALENDAR_TBL." where owner='$id' and type='2'");
	$arr = $db->db_fetch_array($res);

	// delete group's events
	$res = $db->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$arr['id']."'");	

	// delete user from calendar
	$res = $db->db_query("delete from ".BAB_CALENDAR_TBL." where owner='$id' and type='2'");	

	// delete user from BAB_MAIL_DOMAINS_TBL
	$res = $db->db_query("delete from ".BAB_MAIL_DOMAINS_TBL." where owner='$id' and bgroup='Y'");	

    // delete group
	$res = $db->db_query("delete from ".BAB_GROUPS_TBL." where id='$id'");
	bab_callAddonsFunction('onGroupDelete', $id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=groups&idx=List");
	}

/* main */
if( !isset($idx))
	$idx = "Modify";

if( isset($add))
	{
	if( isset($submit))
		{
		if(!modifyGroup($name, $description, $managerid, $bemail, $grpid))
			$idx = "Modify";
			$item = $grpid;
		}
	else if( isset($deleteg) )
		{
		$item = $grpid;
		$idx = "Delete";
		}
	}

if( isset($action) && $action == "Yes")
	{
	if($idx == "Delete")
		{
		confirmDeleteGroup($group);
		}
	if($idx == "Deletem")
		{
		confirmDeleteMembers($item, $names);
		$idx = "Members";
		}
	}

switch($idx)
	{
	case "Deletem":
		if( count($users) > 0)
			{
			deleteMembers($users, $item);
			$babBody->title = bab_translate("Delete group's members");
			$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
			$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
			$babBody->addItemMenu("Deletem", bab_translate("Delete"), "");
			break;
			}
		/* no break */
	case "Members":
		groupMembers($item);
		$babBody->title = bab_translate("Group's members");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		$babBody->addItemMenu("Add", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=users&idx=List&grp=".$item);
		break;
	case "Vacation":
		groupVacation($item);
		$babBody->title = bab_translate("Vacation");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		break;
	case "Delete":
		if( $item > 3 )
			groupDelete($item);
		$babBody->title = bab_translate("Delete group");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=group&idx=Delete&item=".$item);
		break;
	case "Modify":
	default:
		groupModify($item);
		$babBody->title = bab_getGroupName($item) . " ". bab_translate("group");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=group&idx=Modify&item=".$item);
		$babBody->addItemMenu("Members", bab_translate("Members"), $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>