<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function topcatModify($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid topic category !!");
		return;
		}
	class temp
		{
		var $name;
		var $description;
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
			$this->enabled = babTranslate("Enabled");
			$this->no = babTranslate("No");
			$this->yes = babTranslate("Yes");
			$this->modify = babTranslate("Modify");
			$this->db = new db_mysql();
			$req = "select * from topics_categories where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( $this->arr['enabled'] == "Y")
				{
				$this->noselected = "";
				$this->yesselected = "selected";
				}
			else
				{
				$this->noselected = "selected";
				$this->yesselected = "";
				}
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"topcats.html", "topcatmodify"));
	}


function topcatDelete($id)
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

		function temp($id)
			{
			$this->message = babTranslate("Are you sure you want to delete this topic category");
			$this->title = getTopicCategoryTitle($id);
			$this->warning = babTranslate("WARNING: This operation will delete the topic category with all references"). "!";
			$this->urlyes = $GLOBALS['babUrl']."index.php?tg=topcat&idx=Delete&group=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS['babUrl']."index.php?tg=topcat&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyTopcat($oldname, $name, $description, $benabled, $id)
	{
	global $body;

	if( empty($name))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from topics_categories where title='$oldname'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("ERROR: This topic category doesn't exist");
		}
	else
		{
		$query = "update topics_categories set title='$name', description='$description', enabled='$benabled' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: index.php?tg=topcats&idx=List");
	}


function confirmDeleteTopcat($id)
	{
	$db = new db_mysql();

	// delete from sections_order
	$req = "delete from sections_order where id_section='$id' and type='3'";
	$res = $db->db_query($req);	

	// delete from sections_states
	$req = "delete from sections_states where id_section='$id' and private='3'";
	$res = $db->db_query($req);	

	// delete all topics/articles/comments
	$res = $db->db_query("select * from topics where id_cat='".$id."'");
	while( $arr = $db->db_fetch_array($res))
		confirmDeleteCategory($arr['id']);

	// delete topic category
	$req = "delete from topics_categories where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=topcats&idx=List");
	}

/* main */
if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	modifyTopcat($oldname, $title, $description, $benabled, $item);

if( isset($action) && $action == "Yes")
	{
	if($idx == "Delete")
		{
		confirmDeleteTopcat($group);
		}
	}

switch($idx)
	{
	case "Delete":
		topcatDelete($item);
		$body->title = babTranslate("Delete topic category");
		$body->addItemMenu("List", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS['babUrl']."index.php?tg=topcat&idx=Modify&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS['babUrl']."index.php?tg=topcat&idx=Delete&item=".$item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$item);
		break;
	case "Modify":
	default:
		topcatModify($item);
		$body->title = babTranslate("Modify topic category");
		$body->addItemMenu("List", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=List");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS['babUrl']."index.php?tg=topcat&idx=Modify&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS['babUrl']."index.php?tg=topcat&idx=Delete&item=".$item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$item);
		break;
	}

$body->setCurrentItemMenu($idx);

?>