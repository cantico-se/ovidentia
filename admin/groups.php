<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/grpincl.php";

function groupCreate()
	{
	global $body;
	class temp
		{
		var $name;
		var $description;
		var $managertext;
		var $useemail;
		var $no;
		var $yes;
		var $add;

		function temp()
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->managertext = babTranslate("Manager");
			$this->useemail = babTranslate("Use email");
			$this->no = babTranslate("No");
			$this->yes = babTranslate("Yes");
			$this->add = babTranslate("Add Group");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"groups.html", "groupscreate"));
	}

function groupList()
	{
	global $body;
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
			$this->name = babTranslate("Name");
			$this->mail = babTranslate("Mail");
			$this->description = babTranslate("Description");
			$this->manager = babTranslate("Manager");
			$this->db = new db_mysql();
			$req = "select * from groups where id > 2 order by id asc";
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
				$this->url = $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$this->arr[id];
				$this->urlname = $this->arr[name];
				$this->managername = getUserName($this->arr[manager]);
				if( $this->arr[mail] == "Y")
					$this->arr[mail] = babTranslate("Yes");
				else
					$this->arr[mail] = babTranslate("No");
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "groups.html", "groupslist"));
	}


function addGroup($name, $description, $manager, $bemail)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name !!");
		return;
		}

	$db = new db_mysql();

	$req = "select * from groups where name='$name'";	
	$res = $db->db_query($req);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("This group already exists");
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
		$req = "insert into groups (name, description, vacation, mail, manager) VALUES ('" .$name. "', '" . $description. "', '" . $vacation. "', '". $bemail. "', '" . $managerid. "')";
		$db->db_query($req);
		$id = $db->db_insert_id();

		$req = "insert into calendar (owner, actif, type) VALUES ('" .$id. "', 'Y', '2')";
		$db->db_query($req);
		}
	}

/* main */
if( !isset($idx))
	$idx = "List";

if( isset($add))
	addGroup($name, $description, $manager, $bemail);


switch($idx)
	{
	case "Create":
		groupCreate();
		$body->title = babTranslate("Create a group");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=groups&idx=Create");
		break;
	case "List":
	default:
		groupList();
		$body->title = babTranslate("Groups list");
		$body->addItemMenu("List", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=groups&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=groups&idx=Create");
		break;
	}

$body->setCurrentItemMenu($idx);

?>