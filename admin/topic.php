<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/topincl.php";

function listArticles($id)
	{
	global $body;

	class temp
		{
		var $title;
		var $titlename;
		var $articleid;
		var $item;
		var $checkall;
		var $uncheckall;
		var $urltitle;

		var $db;
		var $res;
		var $count;

		function temp($id)
			{
			$this->titlename = babTranslate("Title");
			$this->uncheckall = babTranslate("Uncheck all");
			$this->checkall = babTranslate("Check all");
			$this->item = $id;
			$this->db = new db_mysql();
			$req = "select * from articles where id_topic='$id' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->title = $arr[title];
				$this->articleid = $arr[id];
				$this->urltitle = "javascript:Start('".$GLOBALS[babUrl]."index.php?tg=topic&idx=viewa&item=".$arr[id]."');";
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"topics.html", "articleslist"));
	}

function deleteArticles($art, $item)
	{
	global $body, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($art, $item)
			{
			global $BAB_SESS_USERID;
			$this->message = babTranslate("Are you sure you want to delete those articles");
			$this->title = "";
			$items = "";
			$db = new db_mysql();
			for($i = 0; $i < count($art); $i++)
				{
				$req = "select * from articles where id='".$art[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". $arr[title];
					$items .= $arr[id];
					}
				if( $i < count($art) -1)
					$items .= ",";
				}
			$this->warning = babTranslate("WARNING: This operation will delete artciles and their comments"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=topic&idx=Deletea&item=".$item."&action=Yes&items=".$items;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item;
			$this->no = babTranslate("No");
			}
		}

	if( count($item) <= 0)
		{
		$body->msgerror = babTranslate("Please select at least one item");
		listArticles($item);
		$idx = "Articles";
		return;
		}
	$tempa = new tempa($art, $item);
	$body->babecho(	babPrintTemplate($tempa,"warning.html", "warningyesno"));
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
		var $add;
		var $approver;
		var $approvername;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;

		function temp($id)
			{
			$this->category = babTranslate("Topic");
			$this->description = babTranslate("Description");
			$this->approver = babTranslate("Approver");
			$this->add = babTranslate("Update Topic");
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

function viewArticle($article)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $style;
		var $babUrl;
		var $sitename;
		var $close;


		function temp($article)
			{
			$this->style = $GLOBALS[babStyle];
			$this->babUrl = $GLOBALS[babUrl];
			$this->sitename = $GLOBALS[babSiteName];
			$this->close = babTranslate("Close");
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->content = $this->arr[body];
			}
		}
	
	$temp = new temp($article);
	echo babPrintTemplate($temp,"topics.html", "articleview");
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
	Header("Location: index.php?tg=topics&idx=list");

	}

function confirmDeleteArticles($items)
{
	$arr = explode(",", $items);
	$cnt = count($arr);
	$db = new db_mysql();
	for($i = 0; $i < $cnt; $i++)
		{
		$req = "delete from comments where id_article='".$arr[$i]."'";
		$res = $db->db_query($req);

		$req = "delete from articles where id='".$arr[$i]."'";	
		$res = $db->db_query($req);
		}
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
	if( $idx == "Delete")
		{
		confirmDeleteCategory($category);
		Header("Location: index.php?tg=topics&idx=list");
		}
	else if( $idx == "Deletea")
		{
		confirmDeleteArticles($items);
		Header("Location: index.php?tg=topic&idx=Articles&item=".$item);
		}
	}

switch($idx)
	{
	case "viewa":
		viewArticle($item);
		exit;
	case "Deletea":
		$body->title = babTranslate("Delete articles");
		deleteArticles($art, $item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		$body->addItemMenu("Deletea", babTranslate("Delete"), "javascript:(submitForm('Deletea'))");
		break;

	case "Articles":
		$body->title = babTranslate("List of articles").": ".getCategoryTitle($item);
		listArticles($item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		$body->addItemMenu("Deletea", babTranslate("Delete"), "javascript:(submitForm('Deletea'))");
		break;

	case "Groups":
		$body->title = babTranslate("List of groups");
		aclGroups("topic", "Modify", "topicsview_groups", $item, "aclview");
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		break;

	case "Comments":
		$body->title = babTranslate("List of groups");
		aclGroups("topic", "Modify", "topicscom_groups", $item, "aclview");
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		break;

	case "Submit":
		$body->title = babTranslate("List of groups");
		aclGroups("topic", "Modify", "topicssub_groups", $item, "aclview");
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		break;

	case "Delete":
		$body->title = babTranslate("Delete a topic");
		deleteCategory($item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		break;

	default:
	case "Modify":
		$body->title = babTranslate("Modify a topic");
		modifyCategory($item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS[babUrl]."index.php?tg=topics&idx=list");
		$body->addItemMenu("Modify", babTranslate("Modify"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Modify&item=".$item);
		$body->addItemMenu("Groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Groups&item=".$item);
		$body->addItemMenu("Comments", babTranslate("Comments"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Comments&item=".$item);
		$body->addItemMenu("Submit", babTranslate("Submit"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Submit&item=".$item);
		$body->addItemMenu("Delete", babTranslate("Delete"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Delete&item=".$item);
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS[babUrl]."index.php?tg=topic&idx=Articles&item=".$item);
		break;
	}
$body->setCurrentItemMenu($idx);
?>