<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function topcatCreate()
	{
	global $body;
	class temp
		{
		var $name;
		var $description;
		var $enabled;
		var $no;
		var $yes;
		var $add;

		function temp()
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->enabled = babTranslate("Enabled");
			$this->no = babTranslate("No");
			$this->yes = babTranslate("Yes");
			$this->add = babTranslate("Add");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"topcats.html", "topcatcreate"));
	}

function topcatsList()
	{
	global $body;
	class temp
		{
		var $name;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $catchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $topcount;
		var $topcounturl;
		var $topics;

		function temp()
			{
			$this->name = babTranslate("Name");
			$this->description = babTranslate("Description");
			$this->disabled = babTranslate("Disabled");
			$this->uncheckall = babTranslate("Uncheck all");
			$this->checkall = babTranslate("Check all");
			$this->update = babTranslate("Disable");
			$this->topics = babTranslate("Topics");
			$this->db = new db_mysql();
			$req = "select * from topics_categories";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrl']."index.php?tg=topcat&idx=Modify&item=".$this->arr['id'];
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from topics where id_cat='".$this->arr['id']."'"));
				$this->topcount = $r['total'];
				$this->topcounturl = $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->catchecked = "checked";
				else
					$this->catchecked = "";
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "topcats.html", "topcatslist"));
	}


function addTopCat($name, $description, $benabled)
	{
	global $body;
	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name !!");
		return;
		}

	$db = new db_mysql();

	$res = $db->db_query("select * from topics_categories where title='$name'");
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("This topic category already exists");
		}
	else
		{
		if(!get_cfg_var("magic_quotes_gpc"))
			{
			$description = addslashes($description);
			$name = addslashes($name);
			}
		$req = "insert into topics_categories (title, description, enabled) VALUES ('" .$name. "', '" . $description. "', '" . $benabled. "')";
		$db->db_query($req);

		$id = $db->db_insert_id();
		$req = "select max(ordering) from sections_order where position='0'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$req = "insert into sections_order (id_section, position, type, ordering) VALUES ('" .$id. "', '0', '3', '" . ($arr[0]+1). "')";
		$db->db_query($req);
		}
	}

function disableTopcats($topcats)
	{
	$db = new db_mysql();
	$req = "select id from topics_categories";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($topcats) > 0 && in_array($row['id'], $topcats))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update topics_categories set enabled='".$enabled."' where id='".$row['id']."'";
		$db->db_query($req);
		}
	}

/* main */
if( !isset($idx))
	$idx = "List";

if( isset($add))
	addTopCat($name, $description, $benabled);

if( isset($update))
	{
	if( $update == "disable" )
		disableTopcats($topcats);
	}

switch($idx)
	{
	case "Create":
		topcatCreate();
		$body->title = babTranslate("Create a topic category");
		$body->addItemMenu("List", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=Create");
		break;
	case "List":
	default:
		topcatsList();
		$body->title = babTranslate("Groups list");
		$body->addItemMenu("List", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=Create");
		break;
	}

$body->setCurrentItemMenu($idx);

?>