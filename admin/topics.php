<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/topincl.php";

function addCategory()
	{
	global $body;
	class temp
		{
		var $category;
		var $description;
		var $approver;
		var $add;
		var $msie;

		function temp()
			{
			$this->category = babTranslate("Topic");
			$this->description = babTranslate("Description");
			$this->approver = babTranslate("Approver");
			$this->add = babTranslate("Add");
			if( strtolower(browserAgent()) == "msie")
				$this->msie = 1;
			else
				$this->msie = 0;	
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"topics.html", "categorycreate"));
	}

function listCategories($adminid)
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

		function temp($adminid)
			{
			global $BAB_SESS_USERID;
			$this->articles = babTranslate("Article") ."(s)";
			$this->db = new db_mysql();
			if( $adminid > 0)
				$req = "select * from topics";
			else
				$req = "select * from topics where id_approver='".$BAB_SESS_USERID."'";

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->adminid = $adminid;
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
				$this->urlcategory = $GLOBALS['babUrl']."index.php?tg=topic&idx=Modify&item=".$this->arr['id'];
				$this->namecategory = $this->arr['category'];
				$req = "select * from users where id='".$this->arr['id_approver']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->approver = composeName($arr2['firstname'], $arr2['lastname']);
				$req = "select count(*) as total from articles where id_topic='".$this->arr['id']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				$this->urlarticles = $GLOBALS['babUrl']."index.php?tg=topic&idx=Articles&item=".$this->arr['id'];
				if( $this->adminid == 0)
					$this->urlarticles = $GLOBALS['babUrl']."index.php?tg=topic&idx=Articles&item=".$this->arr['id']."&userid=".$GLOBALS['BAB_SESS_USERID'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($adminid);
	$body->babecho(	babPrintTemplate($temp,"topics.html", "categorylist"));
	return $temp->count;
	}

function saveCategory($category, $description, $approver)
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
	$query = "select * from topics where category='$category'";	
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

	$query = "insert into topics (id_approver, category, description) values ('" .$approverid. "', '" . $category. "', '" . $description. "')";
	$db->db_query($query);
	}


/* main */
$adminid = isUserAdministrator();
if(isset($userid))
	{
	$adminid = 0;
	}

if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($add) && $adminid > 0)
	{
	saveCategory($category, $description, $approver);
	}

switch($idx)
	{
	case "addtopic":
		$body->title = babTranslate("Add a new topic");
		if( $adminid > 0)
		{
		addCategory();
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list");
		$body->addItemMenu("addtopic", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=topics&idx=addtopic");
		}
		break;

	default:
	case "list":
		$body->title = babTranslate("List of all topics");
		if( listCategories($adminid) > 0 )
			{
			$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topics&idx=list");
			}
		else
			$body->title = babTranslate("There is no topic");

		if( $adminid > 0)
			$body->addItemMenu("addtopic", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=topics&idx=addtopic");
		break;
	}
$body->setCurrentItemMenu($idx);

?>