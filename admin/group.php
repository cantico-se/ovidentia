<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/grpincl.php";

function groupModify($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid group !!");
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
		var $modify;

		var $db;
		var $arr = array();
		var $res;

		function temp($id)
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->managertext = babTranslate("Manager");
			$this->useemail = babTranslate("Use email");
			$this->no = babTranslate("No");
			$this->yes = babTranslate("Yes");
			$this->modify = babTranslate("Modify Group");
			$this->db = new db_mysql();
			$req = "select * from groups where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( $this->arr[mail] == "Y")
				{
				$this->noselected = "";
				$this->yesselected = "selected";
				}
			else
				{
				$this->noselected = "selected";
				$this->yesselected = "";
				}
			$req = "select * from users where id='".$this->arr[manager]."'";
			$res = $this->db->db_query($req);
			if( $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->managerval = composeName($arr[firstname], $arr[lastname]);
				}
			else
				$this->managerval = "";
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"groups.html", "groupsmodify"));
	}

function groupMembers($id)
	{
	global $body;
	class temp
		{
		var $fullname;
		var $url;
		var $urlname;
		var $idgroup;
		var $group;
		var $grpid;
		var $primary;
			
		var $arr = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp($id)
			{
			$this->grpid = $id;
			$this->fullname = babTranslate("Full Name");
			$this->idgroup = $id;
			$this->group = getGroupName($id);
			$this->db = new db_mysql();
			$req = "select * from users_groups where id_group= '$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr[isprimary] == "Y")
					$this->primary = "Y";
				else
					$this->primary = "";
				$db = new db_mysql();
				$req = "select * from users where id='".$this->arr[id_object]."'";
				$result = $db->db_query($req);
				$this->arr = $db->db_fetch_array($result);
				$this->url = $GLOBALS[babUrl]."index.php?tg=user&idx=Groups&item=".$this->arr[id];
				$this->urlname = composeName($this->arr[firstname], $this->arr[lastname]);
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp, "groups.html", "memberslist"));
	}

function groupDelete($id)
	{
	global $body;
	
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
			$this->message = babTranslate("Are you sure you want to delete this group");
			$this->title = getGroupName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the group with all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=group&idx=Delete&group=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function groupVacation($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid group !!");
		return;
		}
	class temp
		{
		var $usevacation;
		var $approver;
		var $manager;
		var $approvertext;
		var $approvername;
		var $approvervalue;
		var $modify;
		var $group;
		var $checked;

		var $groupid;

		var $db;
		var $res;
		var $count;
		var $arrapprover = array();

		function temp($id)
			{
			$this->approver = "";
			$this->manager = "";
			$this->usevacation = babTranslate("Use Vacation");
			$this->modify = babTranslate("Update Vacation");
			$this->groupid = $id;
			$this->group = getGroupName($id);
			$this->db = new db_mysql();
			$this->count = 2;

			$req = "select * from groups where id='$id'";
			$res = $this->db->db_query($req);
			if( $res && $this->db->db_num_rows($res) > 0)
				{
				$arr2 = $this->db->db_fetch_array($res);
				if( $arr2[vacation] == "Y")
					$this->checked = "checked";
				else
					$this->checked = "";
				}
			}

		function getnextapprover()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->approvervalue = "";
				$this->approvertext = babTranslate("Approver")." ".($i+1);
				$this->approvername = "approver-".($i+1);

				$req = "select * from vacationsman_groups where id_group='".$this->groupid."' and ordering='".($i+1)."'";
				$res = $this->db->db_query($req);

				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$arr = $this->db->db_fetch_array($res);
					$req = "select * from users where id='".$arr[id_object]."'";
					$res = $this->db->db_query($req);
					if( $this->db->db_num_rows($res) > 0)
						{
						$arr2 = $this->db->db_fetch_array($res);
						$this->approvervalue = composeName($arr2[firstname], $arr2[lastname]);
						}
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"groups.html", "groupvacation"));
	}

function deleteMembers($users, $item)
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

		function tempa($users, $item)
			{
			global $BAB_SESS_USERID;
			$this->message = babTranslate("Are you sure you want to delete those members");
			$this->title = "";
			$names = "";
			$db = new db_mysql();
			for($i = 0; $i < count($users); $i++)
				{
				$req = "select * from users where id='".$users[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". composeName($arr[firstname], $arr[lastname]);
					$names .= $arr[id];
					}
				if( $i < count($users) -1)
					$names .= ",";
				}
			$this->warning = babTranslate("WARNING: This operation will delete members and their references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=group&idx=Deletem&item=".$item."&action=Yes&names=".$names;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item;
			$this->no = babTranslate("No");
			}
		}

	if( count($item) <= 0)
		{
		$body->msgerror = babTranslate("Please select at least one item");
		groupMembers($pos);
		$idx = "Members";
		return;
		}
	$tempa = new tempa($users, $item);
	$body->babecho(	babPrintTemplate($tempa,"warning.html", "warningyesno"));
	}

function modifyGroup($oldname, $name, $description, $manager, $bemail, $id)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from groups where name='$oldname'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("ERROR: Th group doesn't exist");
		}
	else
		{
		if( !empty($manager))
			{
			$idmanager = getUserId($manager);
			if( $idmanager < 1)
				{
				$body->msgerror = babTranslate("The manager doesn't exist");
				return;
				}
			}
		else
			$idmanager = 0;

		$query = "update groups set name='$name', description='$description', mail='$bemail', manager='$idmanager' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=groups&idx=List");
	}

function vacationGroup($usevacation, $approver, $item)
	{
	global $body;

	$db = new db_mysql();

	if( $usevacation == "Y")
		{
		if( empty($approver[0]))
			{
			$body->msgerror = babTranslate("You must provide at least the first approver")." !!";
			return;
			}
		
		if( getUserId($approver[0]) < 1)
			{
			$body->msgerror = babTranslate("The first approver doesn't exist");
			return;
			}

		for( $i = 0; $i < count($approver); $i++)
			{
			if( !empty($approver[$i]))
				{
				$approverid = getUserId($approver[$i]);
				
				if( $approverid != 0)
					{
					$req = "select * from vacationsman_groups where id_group ='$item' and ordering='".($i+1)."'";
					$res = $db->db_query($req);
					if( $res && $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						if( $arr[id_object] !== $approverid)
							{
							$req = "delete from vacations_states where id='".$arr[status]."'";
							$res = $db->db_query($req);
							$name = "Waiting to validate by". " " .$approver[$i].
							$description = "";
							$req = "insert into vacations_states (status, description) VALUES ('" .$name. "', '" . $description. "')";
							$res = $db->db_query($req);
							$statusid = $db->db_insert_id();
							$req = "update vacationsman_groups set id_object='".$approverid."', status='".$statusid."' where id_group ='$item' and ordering='".($i+1)."'";
							$res = $db->db_query($req);
							}
						}
					else
						{
						$name = "Waiting to validate by". " " .$approver[$i].
						$description = "";
						$req = "insert into vacations_states (status, description) VALUES ('" .$name. "', '" . $description. "')";
						$res = $db->db_query($req);
						$statusid = $db->db_insert_id();
						$req = "insert into vacationsman_groups (id_object, id_group, ordering, status) VALUES ('" .$approverid. "', '" .$item. "', '".($i+1)."', '".$statusid."')";
						$res = $db->db_query($req);
						}
					}
				else
					{
					$req = "select * from vacationsman_groups where id_group ='$item' and ordering='".($i+1)."'";
					$res = $db->db_query($req);
					if( $res && $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$req = "delete from vacations_states where id='".$arr[status]."'";
						$res = $db->db_query($req);
						$req = "delete from vacationsman_groups where id_group ='$item' and ordering='".($i+1)."'";
						$res = $db->db_query($req);
						}
					}
				}
			}
		}
	else
		$usevacation = "N";

	$req = "update groups set vacation='$usevacation' where id='$item'";
	$res = $db->db_query($req);
	}

function confirmDeleteMembers($item, $names)
{
	$arr = explode(",", $names);
	$cnt = count($arr);
	$db = new db_mysql();
	for($i = 0; $i < $cnt; $i++)
		{
		$req = "delete from users_groups where id_object='".$arr[$i]."' and id_group='".$item."'";	
		$res = $db->db_query($req);
		}
}

function confirmDeleteGroup($id)
	{
	if( $id <= 3)
		return;
	$db = new db_mysql();
	$req = "delete from topicsview_groups where id_group='$id'";
	$res = $db->db_query($req);	
	$req = "delete from topicscom_groups where id_group='$id'";
	$res = $db->db_query($req);	
	$req = "delete from topicssub_groups where id_group='$id'";
	$res = $db->db_query($req);	
	$req = "delete from sections_groups where id_group='$id'";
	$res = $db->db_query($req);	
	$req = "delete from faqcat_groups where id_group='$id'";
	$res = $db->db_query($req);	
	$req = "delete from users_groups where id_group='$id'";
	$res = $db->db_query($req);	
	$req = "delete from vacationsman_groups where id_group='$id'";
	$res = $db->db_query($req);
	$req = "delete from categoriescal where id_group='$id'";
	$res = $db->db_query($req);

	$req = "select * from resourcescal where id_group='$id'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		
		while( $arr = $db->db_fetch_array($res))
			{
			$req = "select * from calendar where owner='".$arr[id]."' and type='3'";
			$res = $db->db_query($req);
			$r = $db->db_fetch_array($res);

			// delete resource's events
			$req = "delete from cal_events where id_cal='".$r[id]."'";
			$res = $db->db_query($req);	

			// delete resource from calendar
			$req = "delete from calendar where owner='".$arr[id]."' and type='3'";
			$res = $db->db_query($req);	

			// delete resource
			$req = "delete from resourcescal where id_group='$id'";
			$res = $db->db_query($req);
			}
		}

	$req = "select * from calendar where owner='$id' and type='2'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete group's events
	$req = "delete from cal_events where id_cal='".$arr[id]."'";
	$res = $db->db_query($req);	

	// delete user from calendar
	$req = "delete from calendar where owner='$id' and type='2'";
	$res = $db->db_query($req);	

	// delete user from mailview_groups
	$req = "delete from mailview_groups where id_group='$id'";
	$res = $db->db_query($req);	

	// delete user from mailview_domains
	$req = "delete from mailview_groups where owner='$id' and bgroup='Y'";
	$res = $db->db_query($req);	

    // delete group
	$req = "delete from groups where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=groups&idx=List");
	}

/* main */
if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	modifyGroup($oldname, $name, $description, $manager, $bemail, $item);

if( isset($vacation) && $vacation == "update")
	{
	$arrapprover = array();
	for( $i = 0; $i < $count; $i++)
		{
		$var = "approver-".($i+1);
		array_push($arrapprover, $$var);
		}
	vacationGroup($usevacation, $arrapprover, $item);
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
		deleteMembers($users, $item);
		$body->title = babTranslate("Delete group's members");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Deletem", babTranslate("Delete"), "javascript:(submitForm('Deletem'))");
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		break;
	case "Members":
		groupMembers($item);
		$body->title = babTranslate("Group's members");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Deletem", babTranslate("Delete"), "javascript:(submitForm('Deletem'))");
		$body->addItemMenu("Add", babTranslate("Add"), $GLOBALS[babUrl]."index.php?tg=users&idx=List&grp=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		break;
	case "Vacation":
		groupVacation($item);
		$body->title = babTranslate("Vacation");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		break;
	case "Delete":
		if( $item > 3 )
			groupDelete($item);
		$body->title = babTranslate("Delete group");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		if( $item > 3 )
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=group&idx=Delete&item=".$item);
		break;
	case "Modify":
	default:
		groupModify($item);
		$body->title = getGroupName($item) . " ". babTranslate("group");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		if( $item > 3 )
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=group&idx=Delete&item=".$item);
		break;
	}

$body->setCurrentItemMenu($idx);

?>