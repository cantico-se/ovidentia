<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/topincl.php";

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
		var $approver;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $waiting;
		var $newa;
		var $newc;

		function temp()
			{
			global $body, $BAB_SESS_USERID;
			$this->articles = babTranslate("Article") ."(s)";
			$this->waiting = babTranslate("Waiting");
			$this->db = new db_mysql();
			$req = "select * from topics where id_approver='".$BAB_SESS_USERID."'";
			$req = "select topics.* from topics join topics_categories where topics.id_cat=topics_categories.id and topics.id_approver='".$BAB_SESS_USERID."'";

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->arr['description'] = $this->arr['description'];
				$this->namecategory = $this->arr['category'];
				$req = "select count(*) as total from articles where id_topic='".$this->arr['id']."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				$req = "select * from articles where id_topic='".$this->arr['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newa = $this->db->db_num_rows($res);

				$req = "select * from comments where id_topic='".$this->arr['id']."' and confirmed='N'";
				$res = $this->db->db_query($req);
				$this->newc = $this->db->db_num_rows($res);

				$this->urlarticles = $GLOBALS['babUrl']."index.php?tg=topman&idx=Articles&item=".$this->arr['id']."&new=".$this->newa."&newc=".$this->newc;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"topman.html", "categorylist"));
	return $temp->count;
	}

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

		function temp($id)
			{
			$this->titlename = babTranslate("Title");
			$this->uncheckall = babTranslate("Uncheck all");
			$this->checkall = babTranslate("Check all");
			$this->deletealt = babTranslate("Delete articles");
			$this->art0alt = babTranslate("Make available to unregistered users home page");
			$this->art1alt = babTranslate("Make available to registered users home page");
			$this->deletehelp = babTranslate("Click on this image to delete selected articles");
			$this->art0help = babTranslate("Click on this image to make selected articles available to unregistered users home page");
			$this->art1help = babTranslate("Click on this image to make selected articles available to registered users home page");
			$this->homepages = babTranslate("Customize home pages ( Registered and unregistered users )");
			$this->badmin = isUserAdministrator();

			$this->item = $id;
			$this->db = new db_mysql();
			$r = $this->db->db_fetch_array($this->db->db_query("select * from sites where name='".addslashes($GLOBALS['babSiteName'])."'"));
			$this->homepagesurl = $GLOBALS['babUrl']."index.php?tg=site&idx=modify&item=".$r['id'];
			$req = "select * from articles where id_topic='$id' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$req="select * from sites where name='".addslashes($GLOBALS['babSiteName'])."'";
			$r = $this->db->db_fetch_array($this->db->db_query($req));
			$this->siteid = $r['id'];
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from homepages where id_article='".$arr['id']."' and id_group='2' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked0 = "checked";
				else
					$this->checked0 = "";
				$req = "select * from homepages where id_article='".$arr['id']."' and id_group='1' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked0 = "checked";
				else
					$this->checked0 = "";
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=topman&idx=viewa&item=".$arr['id']."');";
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"topman.html", "articleslist"));
	}

function viewArticle($article)
	{
	global $body;

	class temp
		{
	
		var $content;
		var $head;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $baCss;
		var $close;


		function temp($article)
			{
			$this->babCss = babPrintTemplate($this,"config.html", "babCss");
			$this->close = babTranslate("Close");
			$this->db = new db_mysql();
			$req = "select * from articles where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->content = babReplace($this->arr['body']);
			$this->head = babReplace($this->arr['head']);
			}
		}
	
	$temp = new temp($article);
	echo babPrintTemplate($temp,"topman.html", "articleview");
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
					$this->title .= "<br>". $arr['title'];
					$items .= $arr['id'];
					}
				if( $i < count($art) -1)
					$items .= ",";
				}
			$this->warning = babTranslate("WARNING: This operation will delete articles and their comments"). "!";
			$this->urlyes = $GLOBALS['babUrl']."index.php?tg=topman&idx=Deletea&item=".$item."&action=Yes&items=".$items;
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS['babUrl']."index.php?tg=topman&idx=Articles&item=".$item;
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

function confirmDeleteArticles($items)
{
	$arr = explode(",", $items);
	$cnt = count($arr);
	$db = new db_mysql();
	for($i = 0; $i < $cnt; $i++)
		{
		$req = "delete from comments where id_article='".$arr[$i]."'";
		$res = $db->db_query($req);

		$req = "delete from homepages where id_article='".$arr[$i]."'";
		$res = $db->db_query($req);

		$req = "delete from articles where id='".$arr[$i]."'";	
		$res = $db->db_query($req);
		}
}

function addToHomePages($item, $homepage, $art)
{
	global $idx;

	$idx = "Articles";
	$count = count($art);

	$db = new db_mysql();

	$req = "select * from sites where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res = $db->db_query($req);
	if( !$res || $db->db_num_rows($res) < 1)
	{
		$req = "insert into sites ( name, adminemail, lang ) values ('" .addslashes($GLOBALS['babSiteName']). "', '" . $GLOBALS['babAdminEmail']. "', '" . $GLOBALS['babLanguage']. "')";
		$res = $db->db_query($req);
		$idsite = $db->db_insert_id();
	}
	else
	{
		$arr = $db->db_fetch_array($res);
		$idsite = $arr['id'];
	}

	$req = "select * from articles where id_topic='".$item."' order by date desc";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		if( $count > 0 && in_array($arr['id'], $art))
			{
				$req = "select * from homepages where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$res2 = $db->db_query($req);
				if( !$res2 || $db->db_num_rows($res2) < 1)
				{
					$req = "insert into homepages (id_article, id_site, id_group) values ('" .$arr['id']. "', '" . $idsite. "', '" . $homepage. "')";
					$db->db_query($req);
				}
			}
		else
			{
				$req = "delete from homepages where id_article='".$arr['id']."' and id_group='".$homepage."'";
				$db->db_query($req);
			}

		}
}

/* main */
if(!isset($idx))
	{
	$idx = "list";
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
	if( $idx == "Deletea")
		{
		confirmDeleteArticles($items);
		Header("Location: index.php?tg=topman&idx=Articles&item=".$item);
		}
	}

switch($idx)
	{
	case "viewa":
		viewArticle($item);
		exit;
	
	case "deletea":
		$body->title = babTranslate("Delete articles");
		deleteArticles($art, $item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topman");
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS['babUrl']."index.php?tg=topman&idx=Articles&item=".$item);
		$body->addItemMenu("deletea", babTranslate("Delete"), "javascript:(submitForm('deletea'))");
		break;
	
	case "Articles":
		$body->title = babTranslate("List of articles").": ".getCategoryTitle($item);
		listArticles($item);
		$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topman");
		$body->addItemMenu("Articles", babTranslate("Articles"), $GLOBALS['babUrl']."index.php?tg=topman&idx=Articles&item=".$item);
		$body->addItemMenu("Waiting", babTranslate("Waiting"), $GLOBALS['babUrl']."index.php?tg=waiting&idx=Waiting&topics=".$item."&new=".$new."&newc=".$newc);
		break;

	default:
	case "list":
		$body->title = babTranslate("List of managed topics");
		if( listCategories() > 0 )
			{
			$body->addItemMenu("list", babTranslate("Topics"), $GLOBALS['babUrl']."index.php?tg=topman");
			}
		else
			$body->title = babTranslate("There is no topic");
		break;
	}
$body->setCurrentItemMenu($idx);

?>