<?php
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

function addCategory()
	{
	global $body;
	class temp
		{
		var $category;
		var $description;
		var $Manager;
		var $add;

		function temp()
			{
			$this->category = babTranslate("Category");
			$this->description = babTranslate("Description");
			$this->manager = babTranslate("Manager");
			$this->add = babTranslate("Add");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"admfaqs.html", "categorycreate"));
	}

function listCategories()
	{
	global $body;
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

		function temp()
			{
			$this->db = new db_mysql();
			$req = "select * from faqcat";
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
				$this->arr[description] = nl2br($this->arr[description]);
				$this->urlcategory = $GLOBALS[babUrl]."index.php?tg=admfaq&idx=Modify&item=".$this->arr[id];
				$this->namecategory = $this->arr[category];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"admfaqs.html", "categorylist"));
	return $temp->count;
	}


function saveCategory($category, $description, $manager)
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

	$db = new db_mysql();
	$query = "select * from users where email='$manager'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("ERROR: The manager doesn't exist !!");
		return;
		}
	$arr = $db->db_fetch_array($res);

	$query = "select * from faqcat where category='$category'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This category already exists");
		}
	else
		{
		$query = "insert into faqcat (id_manager, category, description) values ('" .$arr[id]. "', '" .$category. "', '" . $description. "')";
		$db->db_query($query);
		}
	}

/* main */
if(!isset($idx))
	{
	$idx = "Categories";
	}

if( isset($add))
	{
	saveCategory($category, $description, $manager);
	}

switch($idx)
	{
	case "Add Category":
		$body->title = babTranslate("Add a new categorie");
		addCategory();
		$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Categories");
		$body->addItemMenu("Add Category", babTranslate("Add Category"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Add Category");
		break;

	default:
	case "Categories":
		$body->title = babTranslate("List of all categories");
		if( listCategories() > 0 )
			{
			$body->addItemMenu("Categories", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Categories");
			}
		$body->addItemMenu("Add Category", babTranslate("Add Category"), $GLOBALS[babUrl]."index.php?tg=admfaqs&idx=Add Category");

		break;
	}
$body->setCurrentItemMenu($idx);

?>