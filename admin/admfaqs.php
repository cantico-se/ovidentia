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

function addCategory()
	{
	global $babBody;
	class temp
		{
		var $category;
		var $description;
		var $Manager;
		var $add;
		var $msie;

		function temp()
			{
			$this->category = bab_translate("FAQ Name");
			$this->description = bab_translate("Description");
			$this->manager = bab_translate("Manager");
			$this->add = bab_translate("Add");
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			$this->item = "";
			$this->managerval = "";
			$this->managerid = "";
			$this->bdel = false;
			$this->tgval = "admfaqs";
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";
			$this->faqname = "";
			$this->faqdesc = "";
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admfaqs.html", "categorycreate"));
	}

function listCategories()
	{
	global $babBody;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $checked;
		var $urlcategory;
		var $namecategory;
		var $access;
		var $accessurl;

		function temp()
			{
			$this->access = bab_translate("Access");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQCAT_TBL."";
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
				$this->arr['description'] = $this->arr['description'];// nl2br($this->arr['description']);
				$this->urlcategory = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Modify&item=".$this->arr['id'];
				$this->accessurl = $GLOBALS['babUrlScript']."?tg=admfaq&idx=Groups&item=".$this->arr['id'];
				$this->namecategory = $this->arr['category'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"admfaqs.html", "categorylist"));
	return $temp->count;
	}


function saveCategory($category, $description, $managerid)
	{
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a FAQ !!");
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$category = addslashes($category);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FAQCAT_TBL." where category='$category'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This FAQ already exists");
		return;
		}

	$query = "insert into ".BAB_FAQCAT_TBL." (id_manager, category, description) values ('" .$managerid. "', '" .$category. "', '" . $description. "')";
	$db->db_query($query);

	}

/* main */
if(!isset($idx))
	{
	$idx = "Categories";
	}

if( isset($add))
	{
	saveCategory($category, $description, $managerid);
	}

switch($idx)
	{
	case "Add":
		$babBody->title = bab_translate("Add a new faq");
		addCategory();
		$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
		$babBody->addItemMenu("Add", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Add");
		break;

	default:
	case "Categories":
		$babBody->title = bab_translate("List of all faqs");
		if( listCategories() > 0 )
			{
			$babBody->addItemMenu("Categories", bab_translate("Faqs"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Categories");
			}
		$babBody->addItemMenu("Add", bab_translate("Add"), $GLOBALS['babUrlScript']."?tg=admfaqs&idx=Add");

		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
