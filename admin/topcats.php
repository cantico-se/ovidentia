<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function topcatCreate()
	{
	global $babBody;
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
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Enabled");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->add = bab_translate("Add");
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatcreate"));
	}

function topcatsList()
	{
	global $babBody;
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
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Disable");
			$this->topics = bab_translate("Number of topics");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL."";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$this->arr['id'];
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$this->arr['id']."'"));
				$this->topcount = $r['total'];
				$this->topcounturl = $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$this->arr['id'];
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
	$babBody->babecho(	bab_printTemplate($temp, "topcats.html", "topcatslist"));
	}


function addTopCat($name, $description, $benabled)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	$db = $GLOBALS['babDB'];

	$res = $db->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='$name'");
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This topic category already exists");
		}
	else
		{
		if(!get_cfg_var("magic_quotes_gpc"))
			{
			$description = addslashes($description);
			$name = addslashes($name);
			}
		$req = "insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled) VALUES ('" .$name. "', '" . $description. "', '" . $benabled. "')";
		$db->db_query($req);

		$id = $db->db_insert_id();
		$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$id. "', '0', '3', '" . ($arr[0]+1). "')";
		$db->db_query($req);
		}
	}

function disableTopcats($topcats)
	{
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL."";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($topcats) > 0 && in_array($row['id'], $topcats))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_TOPICS_CATEGORIES_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
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
		$babBody->title = bab_translate("Create a topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create");
		break;
	case "List":
	default:
		topcatsList();
		$babBody->title = bab_translate("topics categories list");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create");
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>