<?php
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
		var $modify;

		var $db;
		var $arr = array();
		var $res;

		function temp($id)
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->managertext = babTranslate("Manager");
			$this->modify = babTranslate("Modify Group");
			$this->db = new db_mysql();
			$req = "select * from groups where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$req = "select * from users where id='".$this->arr[manager]."'";
			$res = $this->db->db_query($req);
			if( $this->db->db_num_rows($res) > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->managerval = $arr[email];
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
			
		var $arr = array();
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp($id)
			{
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
				$db = new db_mysql();
				$req = "select * from users where id='".$this->arr[id_object]."'";
				$result = $db->db_query($req);
				$this->arr = $db->db_fetch_array($result);
				$this->url = $GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$this->arr[id];
				$this->urlname = $this->arr[fullname];
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
						$this->approvervalue = $arr2[email];
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


function modifyGroup($oldname, $name, $description, $manager, $id)
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
			$req = "select * from users where email='".$manager."'";	
			$res = $db->db_query($req);

			if( $db->db_num_rows($res) < 1)
				{
				$body->msgerror = babTranslate("The manager doesn't exist");
				return;
				}
			$arr = $db->db_fetch_array($res);
			$idmanager = $arr[id];
			}
		else
			$idmanager = 0;

		$query = "update groups set name='$name', description='$description', manager='$idmanager' where id='$id'";
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
		$req = "select * from users where email='".$approver[0]."'";	
		$res = $db->db_query($req);

		if( $db->db_num_rows($res) < 1)
			{
			$body->msgerror = babTranslate("The first approver doesn't exist");
			return;
			}

		for( $i = 0; $i < count($approver); $i++)
			{
			if( !empty($approver[$i]))
				{
				$req = "select * from users where email='".$approver[$i]."'";	
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$approverid = $arr[id];
					}
				else
					$approverid = 0;
				
				$req = "select * from vacationsman_groups where id_group ='$item' and id_object='$approverid'";
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0)
					{
					$req = "update vacationsman_groups set ordering='".($i+1)."' where id_group ='$item' and id_object='$approverid'";
					}
				else
					{
					$name = "Waiting to validate by". " " .$approver[$i].
					$description = "";
					$req = "insert into vacations_states (status, description) VALUES ('" .$name. "', '" . $description. "')";
					$res = $db->db_query($req);
					$statusid = $db->db_insert_id();
					$req = "insert into vacationsman_groups (id_object, id_group, ordering, status) VALUES ('" .$approverid. "', '" .$item. "', '".($i+1)."', '".$statusid."')";
					}
				$res = $db->db_query($req);
				}
			}
		}
	else
		$usevacation = "N";

	$req = "update groups set vacation='$usevacation' where id='$item'";
	$res = $db->db_query($req);
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

	// delete group
	$req = "delete from groups where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=groups&idx=List");
	}

/* main */
if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	modifyGroup($oldname, $name, $description, $manager, $item);

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
	confirmDeleteGroup($group);
	}

switch($idx)
	{
	case "Members":
		groupMembers($item);
		$body->title = babTranslate("Group's members");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		if( $item > 3 )
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=group&idx=Delete&item=".$item);
		break;
	case "Vacation":
		groupVacation($item);
		$body->title = babTranslate("Vacation");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		if( $item > 3 )
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=group&idx=Delete&item=".$item);
		break;
	case "Delete":
		if( $item > 3 )
			groupDelete($item);
		$body->title = babTranslate("Delete group");
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
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$item);
		$body->addItemMenu("Members", babTranslate("Members"), $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$item);
		$body->addItemMenu("Vacation", babTranslate("Vacation"), $GLOBALS[babUrl]."index.php?tg=group&idx=Vacation&item=".$item);
		if( $item > 3 )
			$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=group&idx=Delete&item=".$item);
		break;
	}

$body->setCurrentItemMenu($idx);

?>