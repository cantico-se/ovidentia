<?php
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/vacincl.php";

function modifyVacation($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("You must choose a valid vacation")." !!";
		return;
		}

	class temp
		{
		var $name;
		var $description;
		var $defaultdays;
		var $maxdays;
		var $maxdaysauthorized;
		var $update;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;

		function temp($id)
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->defaultdays = babTranslate("Default days number");
			$this->maxdays = babTranslate("Max days number");
			$this->maxdaysauthorized = babTranslate("Max days authorized");
			$this->update = babTranslate("Update");

			$this->db = new db_mysql();
			$req = "select * from vacations_types where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"admvacs.html", "vacationmodify"));
	}

function deleteVacation($id)
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
			$this->message = babTranslate("Are you sure you want to delete this vacation");
			$this->title = getVacationName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the vacation and all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=admvac&idx=delete&vacation=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=admvac&idx=modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyStatus($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("You must choose a valid status")." !!";
		return;
		}
	class temp
		{
		var $name;
		var $description;
		var $modify;

		var $db;
		var $arr = array();
		var $res;

		function temp($id)
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->modify = babTranslate("Modify Status");
			$this->db = new db_mysql();
			$req = "select * from vacations_states where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"admvacs.html", "statusmodify"));
	}

function deleteStatus($id)
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
			$this->message = babTranslate("Are you sure you want to delete this status");
			$this->title = getStatusName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the status and all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=admvac&idx=deletestatus&status=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=admvac&idx=modifystatus&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function updateVacation($id, $name, $description, $defaultnday, $maxdays, $maxdaysauthorized)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name")." !";
		return;
		}

	$dnd = sscanf($defaultnday, "%d");
	if( empty($dnd[0]))
		{
		$dndval = 0;
		}
	else
		$dndval = abs($dnd[0]);
	$maxd = sscanf($maxdays, "%d");
	if( empty($maxd[0]))
		{
		$maxdval = 0;
		}
	else
		$maxdval = abs($maxd[0]);

	$maxdauth = sscanf($maxdaysauthorized, "%d");
	if( empty($maxdauth[0]))
		{
		$maxdauth = $maxdval;
		}
	else
		{
		$maxdauth = abs($maxdauth[0]);
		}
	if( $maxdauth > $maxdval )
		$maxdauth = $maxdval;

	$db = new db_mysql();

	$query = "update vacations_types set name='$name', description='$description', defaultdays='$dndval', maxdays='$maxdval', days='$maxdauth' where id = '$id'";
	$db->db_query($query);
	Header("Location: index.php?tg=admvacs&idx=list");

	}

function confirmDeleteVacation($id)
	{
	
	$db = new db_mysql();

	$req = "delete from vacationsview_groups where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from vacations_types where id='$id'";
	$res = $db->db_query($req);
	}

function confirmDeleteStatus($id)
	{	
	$db = new db_mysql();

	$req = "delete from vacations_states where id='$id'";
	$res = $db->db_query($req);

	}

function updateStatus($id, $name, $description)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name")." !!";
		return;
		}

	$db = new db_mysql();
	$query = "select * from vacations_states where id ='$id'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("The state doesn't exist");
		}
	else
		{
		$query = "update vacations_states set status='$name', description='$description' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=admvacs&idx=liststatus");
	}

/* main */
if(!isset($idx))
	{
	$idx = "modify";
	}

if( isset($update) && $update == "updatevacation")
	{
	updateVacation($item, $name, $description, $defaultnday, $maxdays, $maxdaysauthorized);
	}

if( isset($updatestatus) && $updatestatus == "update")
	{
	updateStatus($item, $name, $description);
	}

if( isset($action) && $action == "Yes")
	{
	if( $idx == "delete")
		{
		confirmDeleteVacation($vacation);
		Header("Location: index.php?tg=admvacs&idx=list");
		}
	if( $idx == "deletestatus")
		{
		confirmDeleteStatus($status);
		Header("Location: index.php?tg=admvacs&idx=liststatus");
		}
	}

switch($idx)
	{

	case "groups":
		$body->title = babTranslate("Liste of groups");
		aclGroups("vacation", "modify", "vacationsview_groups", $item, "aclview");
		$body->addItemMenu("modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=modify&item=".$item);
		//$body->addItemMenu("delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=delete&item=".$item);
		break;

	case "delete":
		$body->title = babTranslate("Delete vacation");
		deleteVacation($item);
		$body->addItemMenu("modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=modify&item=".$item);
		$body->addItemMenu("delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=delete&item=".$item);
		break;

	case "modifystatus":
		$body->title = babTranslate("Modify status");
		modifyStatus($item);
		$body->addItemMenu("liststatus", babTranslate("Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=liststatus");
		$body->addItemMenu("modifystatus", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=modifystatus&item=".$item);
		break;

	/*
	case "deletestatus":
		$body->title = babTranslate("delete status");
		deleteStatus($item);
		$body->addItemMenu("modifystatus", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=modifystatus&item=".$item);
		$body->addItemMenu("deletestatus", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=deletestatus&item=".$item);
		break;
	*/
	default:
	case "modify":
		$body->title = babTranslate("Modify vacation");
		modifyVacation($item);
		$body->addItemMenu("list", babTranslate("Vacations"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=list");
		$body->addItemMenu("modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=modify&item=".$item);
		$body->addItemMenu("delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admvac&idx=delete&item=".$item);
		break;
	}
$body->setCurrentItemMenu($idx);

?>