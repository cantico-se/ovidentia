<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

function getFaqName($id)
	{
	$db = new db_mysql();
	$query = "select * from faqcat where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[category];
		}
	else
		{
		return "";
		}
	}


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
		var $manager;
		var $managername;
		var $add;

		var $db;
		var $arr = array();
		var $res;

		function temp($id)
			{
			$this->category = babTranslate("Category");
			$this->description = babTranslate("Description");
			$this->add = babTranslate("Update Category");
			$this->manager = babTranslate("Manager");
			$this->db = new db_mysql();
			$req = "select * from faqcat where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);

			$req = "select * from users where id='".$this->arr[id_manager]."'";
			$this->res = $this->db->db_query($req);
			$this->arr2 = $this->db->db_fetch_array($this->res);
			$this->managername = composeName( $this->arr2[firstname], $this->arr2[lastname]);
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"admfaqs.html", "categorymodify"));
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

		function temp($id)
			{
			$this->message = babTranslate("Are you sure you want to delete this faq");
			$this->title = getFaqName($id);
			$this->warning = babTranslate("WARNING: This operation will delete category with all questions/responses"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Delete&item=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}

function updateCategory($id, $category, $description, $manager)
	{
	global $body;
	if( empty($category))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a category !!");
		return;
		}
	if( empty($manager))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a manager !!");
		return;
		}

	$managerid = getUserId($manager);
	if( $managerid < 1)
		{
		$body->msgerror = babTranslate("ERROR: The manager doesn't exist !!");
		return;
		}

	$db = new db_mysql();
	$query = "update faqcat set id_manager='$managerid', category='$category', description='$description' where id = '$id'";
	$db->db_query($query);
	Header("Location: index.php?tg=admfaqs&idx=Categories");

	}

function confirmDeleteFaq($id)
	{
	$db = new db_mysql();

	// delete questions/responses for this faq
	$req = "delete from faqqr where idcat='$id'";
	$res = $db->db_query($req);	

	// delete faq from groups
	$req = "delete from faqcat_groups where id_object='$id'";
	$res = $db->db_query($req);	

	// delete faq
	$req = "delete from faqcat where id='$id'";
	$res = $db->db_query($req);
	Header("Location: index.php?tg=admfaqs&idx=Categories");
	}

/* main */
if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($update))
	{
	updateCategory($item, $category, $description, $manager);
	}

if( isset($aclfaq))
	{
	aclUpdate($table, $item, $groups, $what);
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteFaq($item);
	}

switch($idx)
	{

	case "Modify":
		$body->title = babTranslate("Modify a category");
		modifyCategory($item);
		$body->addItemMenu("Categories", babTranslate("Faqs"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Delete&item=".$item);
		break;

	case "Groups":
		$body->title = babTranslate("Liste of groups");
		aclGroups("admfaq", "Modify", "faqcat_groups", $item, "aclfaq");
		$body->addItemMenu("Categories", babTranslate("Faqs"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Delete&item=".$item);
		break;

	case "Delete":
		$body->title = babTranslate("Delete a category");
		deleteCategory($item);
		$body->addItemMenu("Categories", babTranslate("Faqs"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$body->title = babTranslate("Modify a category");
		modifyCategory($item);
		$body->addItemMenu("Categories", babTranslate("Faqs"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Categories");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Groups&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Delete&item=".$item);
		break;
	}
$body->setCurrentItemMenu($idx);

?>