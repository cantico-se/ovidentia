<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/topincl.php";

function listArticles($id, $userid)
	{
	global $babBody;

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

		var $siteid;
		var $userid;
		var $badmin;
		var $homepages;
		var $homepagesurl;
		var $checked0;
		var $checked1;
		var $deletealt;
		var $art0alt;
		var $art1alt;
		var $deletehelp;
		var $art0help;
		var $art1help;

		function temp($id, $userid)
			{
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete articles");
			$this->art0alt = bab_translate("Make available to unregistered users home page");
			$this->art1alt = bab_translate("Make available to registered users home page");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");
			$this->art0help = bab_translate("Click on this image to make selected articles available to unregistered users home page");
			$this->art1help = bab_translate("Click on this image to make selected articles available to registered users home page");
			$this->homepages = bab_translate("Customize home pages ( Registered and unregistered users )");
			$this->badmin = bab_isUserAdministrator();

			$this->userid = $userid;
			$this->item = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='$id' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$req="select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
			$r = $this->db->db_fetch_array($this->db->db_query($req));
			$this->siteid = $r['id'];
			$this->homepagesurl = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$r['id'];
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='2' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked0 = "checked";
				else
					$this->checked0 = "";
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='1' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked1 = "checked";
				else
					$this->checked1 = "";
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topic&idx=viewa&item=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id, $userid);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "articleslist"));
	}

function deleteArticles($art, $item, $userid)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($art, $item, $userid)
			{
			global $BAB_SESS_USERID;
			$this->message = bab_translate("Are you sure you want to delete those articles");
			$this->title = "";
			$items = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($art); $i++)
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$art[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". $arr['title'];
					$items .= $arr['id'];
					}
				if( $i < count($art) -1)
					$items .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will delete articles and their comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topic&idx=Deletea&item=".$item."&action=Yes&items=".$items."&userid=".$userid;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item."&userid=".$userid;
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		listArticles($item, $userid);
		$idx = "Articles";
		return;
		}
	$tempa = new tempa($art, $item, $userid);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
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
		var $add;
		var $approver;
		var $approvername;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;
		var $msie;
		var $count;
		var $topcat;
		var $modcom;
		var $yes;
		var $no;
		var $yesselected;
		var $noselected;
		var $delete;

		function temp($id)
			{
			$this->topcat = bab_translate("Topic category");
			$this->category = bab_translate("Topic");
			$this->description = bab_translate("Description");
			$this->approver = bab_translate("Approver");
			$this->add = bab_translate("Update Topic");
			$this->modcom = bab_translate("Moderate comments");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->delete = bab_translate("Delete");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_TBL." where id='$id'";
			$res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($res);
			if( $this->arr['mod_com'] == "Y")
				{
				$this->yesselected = "selected";
				$this->noselected = "";
				}
			else
				{
				$this->yesselected = "";
				$this->noselected = "selected";
				}

			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL."";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			$req = "select * from ".BAB_USERS_TBL." where id='".$this->arr['id_approver']."'";
			$res = $this->db->db_query($req);
			$r = $this->db->db_fetch_array($res);
			$this->approvername = bab_composeUserName($r['firstname'], $r['lastname']);
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;	
			}

		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr2 = $this->db->db_fetch_array($this->res);
				if( $this->arr2['id'] == $this->arr['id_cat'] )
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

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorymodify"));
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
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this topic");
			$this->title = bab_getCategoryTitle($id);
			$this->warning = bab_translate("WARNING: This operation will delete the topic, articles and comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topic&idx=Delete&category=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function viewArticle($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $baCss;
		var $close;
		var $head;


		function temp($article)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->close = bab_translate("Close");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->content = bab_replace($this->arr['body']);
			$this->head = bab_replace($this->arr['head']);
			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"topics.html", "articleview");
	}

function updateCategory($id, $category, $description, $approver, $cat, $modcom)
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

	$approverid  = bab_getUserId($approver);	
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

	$db = $GLOBALS['babDB'];
	$query = "update ".BAB_TOPICS_TBL." set id_approver='".$approverid."', category='".$category."', description='".$description."', id_cat='$cat', mod_com='".$modcom."' where id = '".$id."'";
	$db->db_query($query);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
	}

function bab_confirmDeleteArticles($items)
{
	$arr = explode(",", $items);
	$cnt = count($arr);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		$req = "delete from ".BAB_COMMENTS_TBL." where id_article='".$arr[$i]."'";
		$res = $db->db_query($req);

		$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$arr[$i]."'";
		$res = $db->db_query($req);

		$req = "delete from ".BAB_ARTICLES_TBL." where id='".$arr[$i]."'";	
		$res = $db->db_query($req);
		}
}

function addToHomePages($item, $homepage, $art)
{
	global $idx;

	$idx = "Articles";
	$count = count($art);

	$db = $GLOBALS['babDB'];

	$req = "select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res = $db->db_query($req);
	if( !$res || $db->db_num_rows($res) < 1)
	{
		$req = "insert into ".BAB_SITES_TBL." ( name, adminemail, lang ) values ('" .addslashes($GLOBALS['babSiteName']). "', '" . $GLOBALS['babAdminEmail']. "', '" . $GLOBALS['babLanguage']. "')";
		$res = $db->db_query($req);
		$idsite = $db->db_insert_id();
	}
	else
	{
		$arr = $db->db_fetch_array($res);
		$idsite = $arr['id'];
	}

	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$item."' order by date desc";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		if( $count > 0 && in_array($arr['id'], $art))
			{
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$res2 = $db->db_query($req);
				if( !$res2 || $db->db_num_rows($res2) < 1)
				{
					$req = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$arr['id']. "', '" . $idsite. "', '" . $homepage. "')";
					$db->db_query($req);
				}
			}
		else
			{
				$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."'";
				$db->db_query($req);
			}

		}
}

/* main */
$adminid = bab_isUserAdministrator();
if(!isset($idx))
	{
	$idx = "Modify";
	}

if(!isset($cat))
	{
	$db = $GLOBALS['babDB'];
	$r = $db->db_fetch_array($db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$item."'"));
	$cat = $r['id_cat'];
	}

if( isset($update) && $adminid >0)
	{
	if( isset($Submit))
		updateCategory($item, $category, $description, $approver, $cat, $modcom);
	else if( isset($topdel))
		$idx = "Delete";
	}

if( isset($aclview) && $adminid >0)
	{
	aclUpdate($table, $item, $groups, $what);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
	}

if( isset($upart) && $upart == "articles")
	{
	switch($idx)
		{
		case "homepage0":
			addToHomePages($item, 2, $hart0);
			break;
		case "homepage1":
			addToHomePages($item, 1, $hart1);
			break;
		}
	}

if( isset($action) && $action == "Yes")
	{
	if( $idx == "Delete" && $adminid > 0 )
		{
		bab_confirmDeleteCategory($category);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat."userid=".$userid);
		}
	else if( $idx == "Deletea")
		{
		bab_confirmDeleteArticles($items);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item."&userid=".$userid);
		}
	}

switch($idx)
	{
	case "viewa":
		viewArticle($item);
		exit;
	case "deletea":
		$babBody->title = bab_translate("Delete articles");
		deleteArticles($art, $item, $userid);
		if( $adminid > 0)
		{
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&userid=".$userid);
		}
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item."&userid=".$userid);
		$babBody->addItemMenu("deletea", bab_translate("Delete"), "");
		break;

	case "Articles":
		$babBody->title = bab_translate("List of articles").": ".bab_getCategoryTitle($item);
		listArticles($item, $userid);
		if( isset($userid) && !empty($userid))
		{
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&userid=".$GLOBALS['BAB_SESS_USERID']);
		} else if( $adminid > 0 )
		{
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		}
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	case "Groups":
		$babBody->title = bab_getCategoryTitle($item);
		if( $adminid > 0)
		{
		aclGroups("topic", "Modify", BAB_TOPICSVIEW_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		}
		break;

	case "Comments":
		$babBody->title = bab_getCategoryTitle($item);
		if( $adminid > 0)
		{
		aclGroups("topic", "Modify", BAB_TOPICSCOM_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		}
		break;

	case "Submit":
		$babBody->title = bab_getCategoryTitle($item);
		if( $adminid > 0)
		{
		aclGroups("topic", "Modify", BAB_TOPICSSUB_GROUPS_TBL, $item, "aclview");
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete a topic");
		if( $adminid > 0)
		{
		deleteCategory($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topic&idx=Delete&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		}
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a topic");
		if( $adminid > 0)
		{
		modifyCategory($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		$babBody->addItemMenu("Groups", bab_translate("View"), $GLOBALS['babUrlScript']."?tg=topic&idx=Groups&item=".$item);
		$babBody->addItemMenu("Comments", bab_translate("Comment"), $GLOBALS['babUrlScript']."?tg=topic&idx=Comments&item=".$item);
		$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=topic&idx=Submit&item=".$item);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>
