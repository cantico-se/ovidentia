<?php
include $babInstallPath."utilit/forumincl.php";

function addVacation()
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->defaultdays = babTranslate("Default days number");
			$this->maxdays = babTranslate("Max days number");
			$this->maxdaysauthorized = babTranslate("Max days authorized");
			$this->add = babTranslate("Add");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"admvacs.html", "vacationcreate"));
	}

function listVacations()
	{
	global $body;

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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->days = babTranslate("Days");
			$this->max = babTranslate("Max");
			$this->db = new db_mysql();
			$req = "select * from vacations_types order by name asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=admvac&idx=modify&item=".$this->arr[id];
				$this->urlname = $this->arr[name];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "admvacs.html", "vacationslist"));
	return $temp->count;

	}


function listStatus()
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->db = new db_mysql();
			$req = "select * from vacations_states order by status asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=admvac&idx=modifystatus&item=".$this->arr[id];
				$this->urlname = $this->arr[status];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "admvacs.html", "statuslist"));
	}

function createStatus()
	{
	global $body;
	class temp
		{
		var $name;
		var $description;
		var $add;

		function temp()
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->add = babTranslate("Add status");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"admvacs.html", "statuscreate"));
	}

function saveVacation($name, $description, $defaultnday, $maxdays, $maxdaysauthorized)
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
	
	$req = "select * from vacations_types where name='$name'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$body->msgerror = babTranslate("This vacation already exists") ." !";
		return;
		}
	
	$req = "insert into vacations_types ( name, description, defaultdays, maxdays, days)";
	$req .= " values ('".$name."', '" .$description. "', '" .$dndval. "', '" .$maxdval. "', '" .$maxdauth. "')";
	$res = $db->db_query($req);

	}

function addStatus($name, $description)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("You must provide a name")." !!";
		return;
		}

	$db = new db_mysql();
	$query = "select * from vacations_states where status='$name'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("This state already exists");
		}
	else
		{
		$query = "insert into vacations_states (status, description) VALUES ('" .$name. "', '" . $description. "')";
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
		$body->title = babTranslate("Add a new vacation status");
		$body->addItemMenu("list", babTranslate("Vacations"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=list");
		$body->addItemMenu("addvacation", babTranslate("Add Vacation Type"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addvacation");
		$body->addItemMenu("liststatus", babTranslate("Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=liststatus");
		$body->addItemMenu("addstatus", babTranslate("Add Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addstatus");
		createStatus();
		break;
	*/
	case "addvacation":
		$body->title = babTranslate("Add a new vacation");
		$body->addItemMenu("list", babTranslate("Vacations"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=list");
		$body->addItemMenu("addvacation", babTranslate("Add Vacation Type"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addvacation");
		$body->addItemMenu("liststatus", babTranslate("Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=liststatus");
		//$body->addItemMenu("addstatus", babTranslate("Add Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addstatus");
		addVacation();
		break;

	case "liststatus":
		$body->title = babTranslate("List of all status");
		$body->addItemMenu("list", babTranslate("Vacations"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=list");
		$body->addItemMenu("addvacation", babTranslate("Add Vacation Type"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addvacation");
		$body->addItemMenu("liststatus", babTranslate("Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=liststatus");
		//$body->addItemMenu("addstatus", babTranslate("Add Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addstatus");
		listStatus();
		break;

	default:
	case "list":
		$body->title = babTranslate("List of all vacations");
		if( listVacations() > 0 )
			{
			$body->addItemMenu("list", babTranslate("Vacations"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=list");
			}
		else
			$body->title = babTranslate("There is no vacation definition");

		$body->addItemMenu("addvacation", babTranslate("Add Vacation Type"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addvacation");
		$body->addItemMenu("liststatus", babTranslate("Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=liststatus");
		//$body->addItemMenu("addstatus", babTranslate("Add Status"), $GLOBALS[babUrl]."index.php?tg=admvacs&idx=addstatus");
		break;
	}
$body->setCurrentItemMenu($idx);

?>