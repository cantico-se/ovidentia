<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function topcatModify($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid topic category !!");
		return;
		}
	class tempa
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

		function tempa($id)
			{
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->enabled = bab_translate("Enabled");
			$this->no = bab_translate("No");
			$this->yes = bab_translate("Yes");
			$this->modify = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
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

	$temp = new tempa($id);
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatmodify"));
	}


function topcatDelete($id)
	{
	global $babBody, $idx;
	
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
			$this->message = bab_translate("Are you sure you want to delete this topic category");
			$this->title = bab_getTopicCategoryTitle($id);
			$this->warning = bab_translate("WARNING: This operation will delete the topic category with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&group=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$db = $GLOBALS['babDB'];
	$r = $db->db_fetch_array($db->db_query("select count(*) as total from topics where id_cat='".$id."'"));
	if( $r['total'] > 0 )
		{
		$babBody->msgerror = bab_translate("To delete topic category, you must delete topics before");
		$idx = "Modify";
		topcatModify($id);
		return;
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function modifyTopcat($oldname, $name, $description, $benabled, $id)
	{
	global $babBody;

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from topics_categories where title='$oldname'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("ERROR: This topic category doesn't exist");
		}
	else
		{
		$query = "update topics_categories set title='$name', description='$description', enabled='$benabled' where id='$id'";
		$db->db_query($query);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
	}


function confirmDeleteTopcat($id)
	{
	$db = $GLOBALS['babDB'];

	// delete from sections_order
	$req = "delete from sections_order where id_section='$id' and type='3'";
	$res = $db->db_query($req);	

	// delete from sections_states
	$req = "delete from sections_states where id_section='$id' and type='3'";
	$res = $db->db_query($req);	

	// delete all topics/articles/comments
	$res = $db->db_query("select * from topics where id_cat='".$id."'");
	while( $arr = $db->db_fetch_array($res))
		bab_confirmDeleteCategory($arr['id']);

	// delete topic category
	$req = "delete from topics_categories where id='$id'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
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
		$babBody->title = bab_translate("Delete topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&item=".$item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$item);
		break;
	case "Modify":
	default:
		topcatModify($item);
		$babBody->title = bab_translate("Modify topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&item=".$item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>