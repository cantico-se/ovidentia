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

		function temp()
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->managertext = bab_translate("Manager");
			$this->useemail = bab_translate("Use email");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add Group");
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


function addGroup($name, $description, $manager, $bemail)
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
		if( !empty($manager))
			{
			$idmanager = bab_getUserId($manager);	
			if( $idmanager < 1)
				{
				$babBody->msgerror = bab_translate("The manager doesn't exist");
				return;
				}
			}
		else
			$idmanager = 0;
		$req = "insert into ".BAB_GROUPS_TBL." (name, description, vacation, mail, manager) VALUES ('" .$name. "', '" . $description. "', '" . $vacation. "', '". $bemail. "', '" . $idmanager. "')";
		$db->db_query($req);
		$id = $db->db_insert_id();

		$req = "insert into ".BAB_CALENDAR_TBL." (owner, actif, type) VALUES ('" .$id. "', 'Y', '2')";
		bab_callAddonsFunction('bab_group_create', $id);
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
		$babBody->title = bab_translate("Create a group");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		break;
	case "List":
	default:
		groupList();
		$babBody->title = bab_translate("Groups list");
		$babBody->addItemMenu("List", bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=groups&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=groups&idx=Create");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>