<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/forumincl.php";

function addVacation()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $defaultdays;
		var $maxdays;
		var $add;
		var $maxdaysauthorized;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->defaultdays = bab_translate("Default days number");
			$this->maxdays = bab_translate("Max days number");
			$this->maxdaysauthorized = bab_translate("Max days authorized");
			$this->add = bab_translate("Add");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admvacs.html", "vacationcreate"));
	}

function listVacations()
	{
	global $babBody;

	class temp
		{
		var $name;
		var $days;
		var $max;
		var $urlname;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->days = bab_translate("Days");
			$this->max = bab_translate("Max");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_TYPES_TBL." order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=admvac&idx=modify&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admvacs.html", "vacationslist"));
	return $temp->count;

	}


function listStatus()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_STATES_TBL." order by status asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=admvac&idx=modifystatus&item=".$this->arr['id'];
				$this->urlname = $this->arr['status'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "admvacs.html", "statuslist"));
	}

function createStatus()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $add;

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->add = bab_translate("Add status");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admvacs.html", "statuscreate"));
	}

function saveVacation($name, $description, $defaultnday, $maxdays, $maxdaysauthorized)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name")." !";
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
		

	$db = $GLOBALS['babDB'];
	
	$req = "select * from ".BAB_VACATIONS_TYPES_TBL." where name='$name'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This vacation already exists") ." !";
		return;
		}
	
	$req = "insert into ".BAB_VACATIONS_TYPES_TBL." ( name, description, defaultdays, maxdays, days)";
	$req .= " values ('".$name."', '" .$description. "', '" .$dndval. "', '" .$maxdval. "', '" .$maxdauth. "')";
	$res = $db->db_query($req);

	}

function addStatus($name, $description)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("You must provide a name")." !!";
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_VACATIONS_STATES_TBL." where status='$name'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This state already exists");
		}
	else
		{
		$query = "insert into ".BAB_VACATIONS_STATES_TBL." (status, description) VALUES ('" .$name. "', '" . $description. "')";
		$db->db_query($query);
		}
	}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($addvacation) && $addvacation == "addvacation" )
	{
	saveVacation($name, $description, $defaultnday, $maxdays, $maxdaysauthorized);
	}

if( isset($addstatus) && $addstatus == "addstatus" )
	{
	addStatus($name, $description);
	}

switch($idx)
	{
	/*
	case "addstatus":
		$babBody->title = bab_translate("Add a new vacation status");
		$babBody->addItemMenu("list", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
		$babBody->addItemMenu("addvacation", bab_translate("Add Vacation Type"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addvacation");
		$babBody->addItemMenu("liststatus", bab_translate("Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
		$babBody->addItemMenu("addstatus", bab_translate("Add Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addstatus");
		createStatus();
		break;
	*/
	case "addvacation":
		$babBody->title = bab_translate("Add a new vacation");
		$babBody->addItemMenu("list", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
		$babBody->addItemMenu("addvacation", bab_translate("Add Vacation Type"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addvacation");
		$babBody->addItemMenu("liststatus", bab_translate("Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
		//$babBody->addItemMenu("addstatus", bab_translate("Add Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addstatus");
		addVacation();
		break;

	case "liststatus":
		$babBody->title = bab_translate("List of all status");
		$babBody->addItemMenu("list", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
		$babBody->addItemMenu("addvacation", bab_translate("Add Vacation Type"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addvacation");
		$babBody->addItemMenu("liststatus", bab_translate("Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
		//$babBody->addItemMenu("addstatus", bab_translate("Add Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addstatus");
		listStatus();
		break;

	default:
	case "list":
		$babBody->title = bab_translate("List of all vacations");
		if( listVacations() > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=list");
			}
		else
			$babBody->title = bab_translate("There is no vacation definition");

		$babBody->addItemMenu("addvacation", bab_translate("Add Vacation Type"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addvacation");
		$babBody->addItemMenu("liststatus", bab_translate("Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=liststatus");
		//$babBody->addItemMenu("addstatus", bab_translate("Add Status"), $GLOBALS['babUrlScript']."?tg=admvacs&idx=addstatus");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>