<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";
include $babInstallPath."admin/acl.php";
include $babInstallPath."utilit/mailincl.php";
include $babInstallPath."utilit/topincl.php";

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
		var $approver;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $waiting;
		var $newa;
		var $newc;
		var $urlwaitinga;
		var $urlwaitingc;
		var $newac;

		function temp()
			{
			global $babBody, $BAB_SESS_USERID;
			$this->articles = bab_translate("Article(s)");
			$this->comments = bab_translate("Comment(s)");
			$this->waiting = bab_translate("Waiting");
			$this->db = $GLOBALS['babDB'];
			$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and ".BAB_TOPICS_TBL.".id_approver='".$BAB_SESS_USERID."' ORDER BY ".BAB_TOPICS_TBL.".category";

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
				$req = "select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				if( bab_isUserArticleApprover($this->arr['id']) )
					{
					$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'";
					$res = $this->db->db_query($req);
					$this->newa = $this->db->db_num_rows($res);
					}
				else
					{
					$this->newa = 0;
					}

				if( bab_isUserCommentApprover($this->arr['id']))
					{
					$req = "select * from ".BAB_COMMENTS_TBL." where id_topic='".$this->arr['id']."' and confirmed='N'";
					$res = $this->db->db_query($req);
					$this->newc = $this->db->db_num_rows($res);
					}
				else
					{
					$this->newc = 0;
					}

				$this->newac = $this->newa + $this->newc;

				$this->urlwaitinga = $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$this->arr['id']."&new=".$this->new."&newc=".$this->newc;

				$this->urlwaitingc = $GLOBALS['babUrlScript']."?tg=articles&topics=".$this->arr['id'];

				$this->urlarticles = $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$this->arr['id']."&new=".$this->newa."&newc=".$this->newc;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "categorylist"));
	return $temp->count;
	}

function listArticles($id)
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
		var $archivealt;
		var $deletehelp;
		var $archivehelp;
		var $art0help;
		var $art1help;

		function temp($id)
			{
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete articles");
			$this->art0alt = bab_translate("Make available to unregistered users home page");
			$this->art1alt = bab_translate("Make available to registered users home page");
			$this->archivealt = bab_translate("Archive selected articles");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");
			$this->art0help = bab_translate("Click on this image to make selected articles available to unregistered users home page");
			$this->art1help = bab_translate("Click on this image to make selected articles available to registered users home page");
			$this->archivehelp = bab_translate("Click on this image to archive selected articles");
			$this->homepages = bab_translate("Customize home pages ( Registered and unregistered users )");
			$this->badmin = bab_isUserAdministrator();

			$this->item = $id;
			$this->db = $GLOBALS['babDB'];
			$r = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'"));
			$this->homepagesurl = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$r['id'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='$id' and archive='N' and confirmed='Y' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$req="select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
			$r = $this->db->db_fetch_array($this->db->db_query($req));
			$this->siteid = $r['id'];
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$id."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
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
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&art=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "articleslist"));
	return $temp->nbarch;
	}

function listOldArticles($id)
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

		var $archivealt;
		var $archivehelp;

		var $deletealt;
		var $deletehelp;

		function temp($id)
			{
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->archivealt = bab_translate("Move selected articles from archive");
			$this->archivehelp = bab_translate("Click on this image to move out selected articles from archive");
			$this->deletealt = bab_translate("Delete articles");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");

			$this->item = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='$id' and archive='Y' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&art=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "oldarticleslist"));
	}

function viewArticle($article)
	{
	global $babBody;

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
		var $babMeta;
		var $babCss;
		var $close;


		function temp($article)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta = bab_printTemplate($this,"config.html", "babMeta");
			$this->close = bab_translate("Close");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( bab_isUserTopicManager($this->arr['id_topic']))
				{
				$this->content = bab_replace($this->arr['body']);
				$this->head = bab_replace($this->arr['head']);
				}
			else
				{
				$this->content = "";
				$this->head = bab_translate("Access denied");
				}
			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"topman.html", "articleview");
	}

function deleteArticles($art, $item)
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

		function tempa($art, $item)
			{
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
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topman&idx=Deletea&item=".$item."&action=Yes&items=".$items;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item;
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		listArticles($item);
		$idx = "Articles";
		return;
		}
	$tempa = new tempa($art, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}


function addToHomePages($item, $homepage, $art)
{
	global $idx;

	$idx = "Articles";
	$count = count($art);

	$db = $GLOBALS['babDB'];

	$req = "select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	$idsite = $arr['id'];

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
					notifyArticleHomePage(bab_getCategoryTitle($item), $arr['title'], $homepage, $homepage);

				}
			}
		else
			{
				$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$db->db_query($req);
			}

		}
}

function archiveArticles($item, $aart)
{
	$cnt = count($aart);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		$db->db_query("update ".BAB_ARTICLES_TBL." set archive='Y' where id='".$aart[$i]."'");
		$db->db_query("delete from ".BAB_HOMEPAGES_TBL." where id_article='".$aart[$i]."'");
		}
}

function unarchiveArticles($item, $aart)
{
	global $idx;

	$idx = "Articles";
	$cnt = count($aart);
	$db = $GLOBALS['babDB'];
	for($i = 0; $i < $cnt; $i++)
		{
		$db->db_query("update ".BAB_ARTICLES_TBL." set archive='N' where id='".$aart[$i]."'");
		}
}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($item) && bab_isUserTopicManager($item) )
	$manager = true;
else
	$manager = true;

if( isset($upart) && $upart == "articles" && $manager)
	{
	switch($idx)
		{
		case "homepage0":
			addToHomePages($item, 2, $hart0);
			break;
		case "homepage1":
			addToHomePages($item, 1, $hart1);
			break;
		case "unarch":
			unarchiveArticles($item, $aart);
			break;
		}
	}

if( isset($action) && $action == "Yes" && $manager)
	{
	if( $idx == "Deletea")
		{
		include_once $babInstallPath."utilit/delincl.php";
		bab_confirmDeleteArticles($items);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		}
	}

switch($idx)
	{
	case "viewa":
		viewArticle($art);
		exit;
	
	case "deletea":
		if( $manager )
		{
			$babBody->title = bab_translate("Delete articles");
			deleteArticles($art, $item);
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
			$babBody->addItemMenu("deletea", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topman&idx=deletea&art=".$art);
		}
		else
			$babBody->msgerror = bab_translate("Access denied");

		break;

	case "alist":
		if( $manager )
		{
			$babBody->title = bab_translate("List of old articles").": ".bab_getCategoryTitle($item);
			listOldArticles($item);
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item."&new=".$new."&newc=".$newc);
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item."&new=".$new."&newc=".$newc);

			if( $new > 0)
				$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$item."&new=".$new."&newc=".$newc);
		}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	case "archive":
		if( $manager )
		{
			archiveArticles($item, $aart);
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		/* no break; */
	case "Articles":
		if( $manager )
		{
			$babBody->title = bab_translate("List of articles").": ".bab_getCategoryTitle($item);
			$nbarch = listArticles($item);
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item."&new=".$new."&newc=".$newc);
			if( $nbarch > 0)
				$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item."&new=".$new."&newc=".$newc);

			if( $new > 0 && bab_isUserArticleApprover($item))
				$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$item."&new=".$new."&newc=".$newc);
		}
		else
			$babBody->msgerror = bab_translate("Access denied");
		break;

	default:
	case "list":
		$babBody->title = bab_translate("List of managed topics");
		if( listCategories() > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
			}
		else
			$babBody->title = bab_translate("There is no topic");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>