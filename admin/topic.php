<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/topincl.php";

function modifyCategory($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid category !!");
		return;
		}
	class temp
		{
		var $category;
		var $description;
		var $add;
		var $approver;
		var $approvername;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;

		function temp($id)
			{
			$this->category = babTranslate("Category");
			$this->description = babTranslate("Description");
			$this->approver = babTranslate("Approver");
			$this->add = babTranslate("Update Category");
			$this->db = new db_mysql();
			$req = "select * from topics where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);

			$req = "select * from users where id='".$this->arr[id_approver]."'";
			$this->res = $this->db->db_query($req);
			$this->arr2 = $this->db->db_fetch_array($this->res);
			$this->approvername = composeName($this->arr2[firstname], $this->arr2[lastname]);
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"topics.html", "categorymodify"));
	}

function deleteCategory($id)
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
			$this->message = babTranslate("Are you sure you want to delete this topic");
			$this->title = getCategoryTitle($id);
			$this->warning = babTranslate("WARNING: This operation will delete the topic, articles and comments"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&category=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function updateCategory($id, $category, $description, $approver)
	{
	global $body;
	if( empty($category))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a category !!");
		return;
		}

	if( empty($approver))
		{
		$body->msgerror = babTranslate("ERROR: You must provide an approver !!");
		return;
		}

	$approverid  = getUserId($approver);	
	if( $approverid < 1)
		{
		$body->msgerror = babTranslate("ERROR: The approver doesn't exist !!");
		return;
		}

	$db = new db_mysql();
	$query = "update topics set id_approver='$approverid', category='$category', description='$description' where id = '$id'";
	$db->db_query($query);
	Header("Location: index.php?tg=topics&idx=Categories");

	}

/* main */
if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($update))
	{
	updateCategory($item, $category, $description, $approver);
	}

if( isset($aclview))
	{
	aclUpdate($table, $item, $groups, $what);
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteCategory($category);
	Header("Location: index.php?tg=topics&idx=Categories");
	}

switch($idx)
	{

	case "Groups":
		$body->title = babTranslate("Liste of groups");
		aclGroups("topic", "Modify", "topicsview_groups", $item, "aclview");
		$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=topics&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		break;

	case "Comments":
		$body->title = babTranslate("Liste of groups");
		aclGroups("topic", "Modify", "topicscom_groups", $item, "aclview");
		$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=topics&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		break;

	case "Submit":
		$body->title = babTranslate("Liste of groups");
		aclGroups("topic", "Modify", "topicssub_groups", $item, "aclview");
		$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=topics&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		break;

	case "Delete":
		$body->title = babTranslate("Delete a category");
		deleteCategory($item);
		$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=topics&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$body->title = babTranslate("Modify a category");
		modifyCategory($item);
		$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=topics&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		break;
	}
$body->setCurrentItemMenu($idx);

?>