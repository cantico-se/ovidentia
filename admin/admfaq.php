<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";

function getFaqName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FAQCAT_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}


function modifyCategory($id)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid category !!");
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
		var $msie;
		var $delete;

		function temp($id)
			{
			$this->category = bab_translate("FAQ Name");
			$this->description = bab_translate("Description");
			$this->add = bab_translate("Update FAQ");
			$this->delete = bab_translate("Delete");
			$this->manager = bab_translate("Manager");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQCAT_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['category'] = htmlentities($this->arr['category']);
			$this->arr['description'] = htmlentities($this->arr['description']);
			$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['id_manager']."'";
			$this->res = $this->db->db_query($req);
			$this->arr2 = $this->db->db_fetch_array($this->res);
			$this->managerval = bab_composeUserName( $this->arr2['firstname'], $this->arr2['lastname']);
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;
			$this->item = $id;
			$this->managerid = $this->arr['id_manager'];
			$this->bdel = true;
			$this->tgval = "admfaq";
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->faqname = $this->arr['category'];
			$this->faqdesc = $this->arr['description'];
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"admfaqs.html", "categorycreate"));
	}

function deleteCategory($id)
	{
	global $babBody;

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
			$this->message = bab_translate("Are you sure you want to delete this faq");
			$this->title = getFaqName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the FAQ with all questions and responses"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Delete&item=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function updateCategory($id, $category, $description, $managerid)
	{
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a FAQ name !!");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$category = addslashes($category);
		$description = addslashes($description);
		}

	$db = $GLOBALS['babDB'];
	$query = "update ".BAB_FAQCAT_TBL." set id_manager='".$managerid."', category='".$category."', description='".$description."' where id = '".$id."'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");

	}

function confirmDeleteFaq($id)
	{
	$db = $GLOBALS['babDB'];

	// delete questions/responses for this faq
	$req = "delete from ".BAB_FAQQR_TBL." where idcat='$id'";
	$res = $db->db_query($req);	

	// delete faq from groups
	$req = "delete from ".BAB_FAQCAT_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);	

	// delete faq
	$req = "delete from ".BAB_FAQCAT_TBL." where id='$id'";
	$res = $db->db_query($req);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
	}

/* main */
if(!isset($idx))
	{
	$idx = "Modify";
	}

if( isset($add))
	{
	if( isset($submit))
		{
		if(!updateCategory($item, $category, $description, $managerid))
			$idx = "Modify";
		}
	else if( isset($faqdel))
		$idx = "Delete";
	}

if( isset($aclfaq))
	{
	aclUpdate($table, $item, $groups, $what);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteFaq($item);
	}

switch($idx)
	{

	case "Groups":
		$babBody->title = bab_translate("FAQ").": ". getFaqName($item)." ".bab_translate("is visible by") ;
		aclGroups("admfaq", "Modify", BAB_FAQCAT_GROUPS_TBL, $item, "aclfaq");
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete FAQ");
		deleteCategory($item);
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify FAQ").": ". getFaqName($item);
		modifyCategory($item);
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("Access"), $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
