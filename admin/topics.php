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
	global $babBody;
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
		var $modcom;
		var $yes;
		var $no;

		function temp($cat)
			{
			$this->topcat = bab_translate("Topic category");
			$this->category = bab_translate("Topic");
			$this->description = bab_translate("Description");
			$this->approver = bab_translate("Approver");
			$this->modcom = bab_translate("Moderate comments");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			$this->idcat = $cat;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL."";
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
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorycreate"));
	}

function listCategories($cat, $adminid)
	{
	global $babBody;
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
		var $groups;
		var $comments;
		var $submit;
		var $urlgroups;
		var $urlcomments;
		var $urlsubmit;

		function temp($cat, $adminid)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->groups = bab_translate("View");
			$this->comments = bab_translate("Comment");
			$this->submit = bab_translate("Submit");
			$this->articles = bab_translate("Article") ."(s)";
			$this->db = $GLOBALS['babDB'];
			if( $adminid > 0)
				$req = "select * from ".BAB_TOPICS_TBL." where id_cat='".$cat."'";
			else
				$req = "select * from ".BAB_TOPICS_TBL." where id_cat='".$cat."' and id_approver='".$BAB_SESS_USERID."'";

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
				$this->urlgroups = $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->urlcomments = $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->urlsubmit = $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->arr['description'] = $this->arr['description'];//nl2br($this->arr['description']);
				$this->urlcategory = $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->namecategory = $this->arr['category'];
				$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['id_approver']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->approver = bab_composeUserName($arr2['firstname'], $arr2['lastname']);
				$req = "select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				$this->urlarticles = $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$this->arr['id']."&cat=".$this->idcat;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($cat, $adminid);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorylist"));
	return $temp->count;
	}

function saveCategory($category, $description, $approver, $cat, $modcom)
	{
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a category !!");
		return;
		}

	if( empty($approver))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide an approver !!");
		return;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_TBL." where category='$category' and id_cat='".$cat."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This topic already exists");
		return;
		}

	$approverid = bab_getUserId($approver);	
	if( $approverid < 1)
		{
		$babBody->msgerror = bab_translate("ERROR: The approver doesn't exist !!");
		return;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$category = addslashes($category);
		$description = addslashes($description);
		}

	$query = "insert into ".BAB_TOPICS_TBL." (id_approver, category, description, id_cat, mod_com) values ('" .$approverid. "', '" . $category. "', '" . $description. "', '" . $cat. "', '" . $modcom. "')";
	$db->db_query($query);
	}


/* main */
$adminid = bab_isUserAdministrator();
if( $adminid < 1 )
	{
	$babBody->title = bab_translate("Access denied");
	exit;
	}

if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($add) && $adminid > 0)
	{
	saveCategory($category, $description, $approver, $cat, $modcom);
	}

switch($idx)
	{
	case "addtopic":
		$babBody->title = bab_translate("Add a new topic");
		if( $adminid > 0)
		{
		addCategory($cat);
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("addtopic", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topics&idx=addtopic&cat=".$cat);
		}
		break;

	default:
	case "list":
		$catname = bab_getTopicCategoryTitle($cat);
		$babBody->title = bab_translate("List of all topics"). " [ " . $catname . " ]";
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List");
		if( listCategories($cat, $adminid) > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
			}
		else
			$babBody->title = bab_translate("There is no topic"). " [ " . $catname . " ]";

		if( $adminid > 0)
			$babBody->addItemMenu("addtopic", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topics&idx=addtopic&cat=".$cat);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>