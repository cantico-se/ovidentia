<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/topincl.php";

function addCategory($cat)
	{
	global $body;
	class temp
		{
		var $category;
		var $description;
		var $approver;
		var $add;
		var $msie;
		var $idcat;
		var $db;
		var $count;
		var $res;
		var $selected;
		var $topcat;

		function temp($cat)
			{
			$this->topcat = babTranslate("Topic category");
			$this->category = babTranslate("Topic");
			$this->description = babTranslate("Description");
			$this->approver = babTranslate("Approver");
			$this->add = babTranslate("Add");
			if( strtolower(browserAgent()) == "msie")
				$this->msie = 1;
			else
				$this->msie = 0;	
			$this->idcat = $cat;
			$this->db = new db_mysql();
			$req = "select * from topics_categories";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}
		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr['id'] == $this->idcat )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;
			}
		}


	$temp = new temp($cat);
	$body->babecho(	babPrintTemplate($temp,"topics.html", "categorycreate"));
	}

function listCategories($cat, $adminid)
	{
	global $body;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $select;
		var $approver;
		var $urlcategory;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $idcat;

		function temp($cat, $adminid)
			{
			global $body, $BAB_SESS_USERID;
			$this->articles = babTranslate("Article") ."(s)";
			$this->db = new db_mysql();
			if( $adminid > 0)
				$req = "select * from topics where id_cat='".$cat."'";
			else
				$req = "select * from topics where id_cat='".$cat."' and id_approver='".$BAB_SESS_USERID."'";

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->adminid = $adminid;
			$this->idcat = $cat;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->select = "checked";
				else
					$this->select = "";
					
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->arr['description'] = $this->arr['description'];//nl2br($this->arr['description']);
				$this->urlcategory = $GLOBALS['babUrl']."index.php?tg=topic&idx=Modify&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->namecategory = $this->arr['category'];
				$req = "select * from users where id='".$this->arr['id_approver']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->approver = composeName($arr2['firstname'], $arr2['lastname']);
				$req = "select count(*) as total from articles where id_topic='".$this->arr['id']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				$this->urlarticles = $GLOBALS['babUrl']."index.php?tg=topic&idx=Articles&item=".$this->arr['id']."&cat=".$this->idcat;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($cat, $adminid);
	$body->babecho(	babPrintTemplate($temp,"topics.html", "categorylist"));
	return $temp->count;
	}

function saveCategory($category, $description, $approver, $cat)
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

	$db = new db_mysql();
	$query = "select * from topics where category='$category' and id_cat='".$cat."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This topic already exists");
		}

	$approverid = getUserId($approver);	
	if( $approverid < 1)
		{
		$body->msgerror = babTranslate("ERROR: The approver doesn't exist !!");
		return;
		}

	$query = "insert into topics (id_approver, category, description, id_cat) values ('" .$approverid. "', '" . $category. "', '" . $description. "', '" . $cat. "')";
	$db->db_query($query);
	}


/* main */
$adminid = isUserAdministrator();
if( $adminid < 1 )
	{
	$body->title = babTranslate("Access denied");
	exit;
	}

if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($add) && $adminid > 0)
	{
	saveCategory($category, $description, $approver, $cat);
	}

switch($idx)
	{
	case "addtopic":
		$body->title = babTranslate("Add a new topic");
		if( $adminid > 0)
		{
		addCategory($cat);
		$body->addItemMenu("List", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=List");
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$cat);
		$body->addItemMenu("addtopic", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=topics&idx=addtopic&cat=".$cat);
		}
		break;

	default:
	case "list":
		$catname = getTopicCategoryTitle($cat);
		$body->title = babTranslate("List of all topics"). " [ " . $catname . " ]";
		$body->addItemMenu("List", babTranslate("Categories"), $GLOBALS['babUrl']."index.php?tg=topcats&idx=List");
		if( listCategories($cat, $adminid) > 0 )
			{
			$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list&cat=".$cat);
			}
		else
			$body->title = babTranslate("There is no topic"). " [ " . $catname . " ]";

		if( $adminid > 0)
			$body->addItemMenu("addtopic", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=topics&idx=addtopic&cat=".$cat);
		break;
	}
$body->setCurrentItemMenu($idx);

?>