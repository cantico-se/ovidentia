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
include_once 'base.php';

include_once $GLOBALS['babInstallPath'].'utilit/uiutil.php';
include_once $GLOBALS['babInstallPath'].'utilit/topincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/artincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/urlincl.php';

define("BAB_ART_MAXLOGS"	, 25);


function listCategories()
	{

	global $babBody;

	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';

	$topicTree = new bab_ArticleTreeView('article_topics_tree' . BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC);
	$topicTree->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS
							| BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS
							| BAB_ARTICLE_TREE_VIEW_HIDE_EMPTY_TOPICS_AND_CATEGORIES
							| BAB_TREE_VIEW_SHOW_TOOLBAR);
	$topicTree->setAction(BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC);
//	$topicTree->setLink($GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=%s");
	$topicTree->setTopicsLinks($GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=%s");
	$topicTree->order();
	$topicTree->sort();

	$babBody->babecho($topicTree->printTemplate());

	}




function listArticles($id)
	{
	global $babBody;

	class listArticlesTpl
		{
		public $title;
		public $titlename;
		public $articleid;
		public $item;
		public $checkall;
		public $uncheckall;
		public $urltitle;

		public $db;
		public $res;
		public $count;

		public $siteid;
		public $userid;
		public $badmin;
		public $homepages;
		public $homepagesurl;

		public $checked0;
		public $checked1;
		public $deletealt;
		public $art0alt;
		public $art1alt;
		public $archivealt;
		public $deletehelp;
		public $archivehelp;
		public $art0help;
		public $art1help;

		function __construct($id)
			{
			global $babBody, $babDB;
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete articles");
			$this->archivealt = bab_translate("Archived article");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");
			$this->art0alt = bab_translate("Available to unregistered users home page");
			$this->art1alt = bab_translate("Available to registered users home page");
			$this->archivehelp = bab_translate("Click on this image to archive selected articles");
			$this->homepages = bab_translate("Customize home pages ( Registered and unregistered users )");
			$this->datepublicationtxt = bab_translate("Publication date");
			$this->datearchivingtxt = bab_translate("Archiving date");
			$this->propertiestxt = bab_translate("Properties");
			$this->t_expand_all = bab_translate("Expand all");
			$this->t_collapse_all = bab_translate("Collapse all");
			$this->t_view_article = bab_translate("Preview article");
			$this->t_comment = bab_translate("Comment");
			$this->t_by = bab_translate("by");
			$this->t_with_selected = bab_translate("Update selected elements");
			$this->t_delete = bab_translate("Delete");
			$this->t_homepage_public = bab_translate("Make available to unregistered users home page");
			$this->t_homepage_private = bab_translate("Make available to registered users home page");
			$this->t_homepage_no = bab_translate("Make unavailable to home pages");
			$this->t_archive = bab_translate("Archive selected articles");
			$this->t_update = bab_translate("Update");
			$this->t_articles = bab_translate("Articles");
			$this->t_file = bab_translate("File");
			$this->t_files = bab_translate("Attached files");
			$this->t_comments = bab_translate("Comments");
			$this->js_confirm_delete = bab_translate("Are you sure you want to delete those articles");
			$this->js_confirm_delete = str_replace("'","\'",$this->js_confirm_delete);
			$this->badmin = bab_isUserAdministrator();
			$this->removedrafttxt = bab_translate("Remove the draft");
			$this->removedraftconfirm = bab_translate("This will remove all modification on this article, continue?");

			if (bab_searchEngineInfos()) {
				$this->index = true;
				include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
			}
			else
			{
				$this->index = false;
			}

			$this->item = $id;
			$this->siteid = $babBody->babsite['id'];

			$this->homepagesurl = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$this->siteid;

			$req = "select at.*, adt.id_article, adt.id as id_draft, adt.id_author as id_author, public.id public, private.id private
					FROM ".BAB_ARTICLES_TBL." at
					LEFT JOIN ".BAB_ART_DRAFTS_TBL." adt
						ON at.id=adt.id_article
					LEFT JOIN ".BAB_HOMEPAGES_TBL." public
						ON public.id_site='".$babDB->db_escape_string($this->siteid)."' AND public.id_article=at.id AND public.id_group='2'
					LEFT JOIN ".BAB_HOMEPAGES_TBL." private
						ON private.id_site='".$this->siteid."' AND private.id_article=at.id AND private.id_group='1'
					WHERE at.id_topic='".$babDB->db_escape_string($id)."' and at.archive='N'
					ORDER by at.ordering asc, at.date_modification desc
						";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->homepage_public  = isset($arr['public']);
				$this->homepage_private = isset($arr['private']);
				$this->archive = $arr['archive'] == 'Y';
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$arr['id_topic']."&art=".$arr['id'];
				$this->propurl = $GLOBALS['babUrlScript']."?tg=topman&idx=propa&item=".$arr['id_topic']."&art=".$arr['id'];
				$this->removedrafturl = $GLOBALS['babUrlScript']."?tg=topman&idx=rmdraft&item=".$arr['id_topic']."&art=".$arr['id_draft'];

				if( isset($arr['id_article']) && $arr['id_article'] != 0 )
					{
					$user = bab_getUserName($arr['id_author']);
					$this->bupdate = true;
					$this->status = bab_translate("Article in modification by ") . '<b>' . $user . '</b>';
					}
				else
					{
					$this->bupdate = false;
					$this->status = bab_translate("New article");
					}
				if( $arr['date_publication'] != '0000-00-00 00:00:00' )
					{
					$this->datepublication = bab_shortDate(bab_mktime($arr['date_publication']), true);
					}
				else
					{
					$this->datepublication = '';
					}
				if( $arr['date_archiving'] != '0000-00-00 00:00:00' )
					{
					$this->datearchiving = bab_shortDate(bab_mktime($arr['date_archiving']), true);
					}
				else
					{
					$this->datearchiving = '';
					}

				$this->rescom = $babDB->db_query("SELECT * FROM ".BAB_COMMENTS_TBL." WHERE id_article='".$babDB->db_escape_string($this->articleid)."' ORDER BY date DESC");
				$this->countcom = $babDB->db_num_rows($this->rescom);

				$this->resfiles = $babDB->db_query("SELECT * FROM ".BAB_ART_FILES_TBL." WHERE id_article='".$babDB->db_escape_string($this->articleid)."' order by ordering asc");
				$this->countfiles = $babDB->db_num_rows($this->resfiles);

				$this->filescomments = $this->countcom >0 || $this->countfiles > 0;

				$i++;
				return true;
				}
			else
				return false;

			}


		function getnextcom()
			{
			global $babDB;
			if ($this->com = $babDB->db_fetch_assoc($this->rescom))
				{
				$this->com['subject'] = bab_toHtml($this->com['subject']);
				if( $this->com['id_author'] )
					{
					$this->com['name'] = bab_toHtml(bab_getUserName($this->com['id_author']));
					}
				else
					{
					$this->com['name'] = bab_toHtml($this->com['name']);
					}
				return true;
				}
			else {
				return false;
				}
			}


		function getnextfile()
			{
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->resfiles)) {
				$this->filename = bab_toHtml($arr['name']);
				if ($this->index) {
					$this->index_status = bab_toHtml(bab_getIndexStatusLabel($arr['index_status']));
				}
				return true;
			} else {
				return false;
			}
		}

		}

		$temp = new listArticlesTpl($id);
	$babBody->addStyleSheet('tree.css');
	$babBody->addStyleSheet('groups.css');
	$babBody->babecho(bab_printTemplate($temp,"topman.html", "articleslist"));
	}

function listOldArticles($id)
	{
	global $babBody;

	class listOldArticlesTpl
		{
		public $title;
		public $titlename;
		public $articleid;
		public $item;
		public $checkall;
		public $uncheckall;
		public $urltitle;

		public $db;
		public $res;
		public $count;

		public $archivealt;
		public $archivehelp;

		public $deletealt;
		public $deletehelp;

		function __construct($id)
			{
			global $babDB;
			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->archivealt = bab_translate("Move selected articles from archive");
			$this->archivehelp = bab_translate("Click on this image to move out selected articles from archive");
			$this->deletealt = bab_translate("Delete articles");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");

			$this->item = $id;
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($id)."' and archive='Y' order by date desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$arr['id_topic']."&art=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}

		}

		$temp = new listOldArticlesTpl($id);
	$babBody->babecho(bab_printTemplate($temp,"topman.html", "oldarticleslist"));
	}

function viewArticle($article)
	{
	class viewArticleTpl
		{

		public $content;
		public $head;
		public $arr = array();
		public $db;
		public $count;
		public $res;
		public $more;
		public $topics;
		public $babMeta;
		public $babCss;
		public $close;
		public $altbg = false;
		public $sContent = '';

		function __construct($article)
			{
			global $babDB;
			$this->babCss		= bab_printTemplate($this,"config.html", "babCss");
			$this->babMeta		= bab_printTemplate($this,"config.html", "babMeta");
			$this->close		= bab_translate("Close");
			$this->deletetxt	= bab_translate("Delete");
			$this->attachmentxt = bab_translate("Associated documents");
			$this->commentstxt	= bab_translate("Comments");
			$req				= "select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
			$this->res			= $babDB->db_query($req);
			$this->arr			= $babDB->db_fetch_array($this->res);
			$this->sContent		= 'text/html; charset=' . bab_charset::getIso();

			if( bab_isUserTopicManager($this->arr['id_topic']))
				{
				include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['body']);
				$editor->setFormat($this->arr['body_format']);
				$this->content = $editor->getHtml();

				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['head']);
				$editor->setFormat($this->arr['head_format']);
				$this->head = $editor->getHtml();

				}
			else
				{
				$this->content = '';
				$this->head = bab_translate("Access denied");
				}
			$this->resf = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($article)."' order by ordering asc");
			$this->countf = $babDB->db_num_rows($this->resf);

			if( $this->countf > 0 )
				{
				$this->battachments = true;
				}
			else
				{
				$this->battachments = false;
				}

			$this->rescom = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id_article='".$babDB->db_escape_string($article)."' and confirmed='Y' order by date desc");
			$this->countcom = $babDB->db_num_rows($this->rescom);
			}

		function getnextdoc()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=topman&idx=getf&item=".$this->arr['id_topic']."&idf=".$arr['id'];
				$this->docname = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextcom()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $babDB->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_strftime(bab_mktime($arr['date']));
				if( $arr['id_author'] )
					{
					$this->authorname = bab_getUserName($arr['id_author']);
					}
				else
					{
					$this->authorname = $arr['name'];
					}
				$this->commenttitle = $arr['subject'];

				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$editor->setFormat($arr['message_format']);
				$this->commentbody = $editor->getHtml();

				$this->delcomurl = $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&delc=com&item=".$this->arr['id_topic']."&art=".$this->arr['id']."&idc=".$arr['id'];
				$i++;
				return true;
				}
			else
				{
				$babDB->db_data_seek($this->rescom,0);
				$i=0;
				return false;
				}
			}
		}

	$temp = new viewArticleTpl($article);
	echo bab_printTemplate($temp,"topman.html", "articleview");
	}

function deleteArticles($art, $item)
	{
	global $babBody, $idx;

	class deleteArticlesTpl
		{
		public $warning;
		public $message;
		public $title;
		public $urlyes;
		public $urlno;
		public $yes;
		public $no;

		function __construct($art, $item)
			{
			global $babDB;
			$this->message = bab_translate("Are you sure you want to delete those articles");
			$this->title = "";
			$items = "";
			for($i = 0; $i < count($art); $i++)
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($art[$i])."'";
				$res = $babDB->db_query($req);
				if( $babDB->db_num_rows($res) > 0)
					{
					$arr = $babDB->db_fetch_array($res);
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
	$tempa = new deleteArticlesTpl($art, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}


function orderArticles($id)
	{
	global $babBody;
	class orderArticlesTpl
		{
		public $sorta;
		public $sortd;
		public $topicid;

		function __construct($id)
			{
			global $babDB;
			$this->topicid = $id;
			$this->toplisttxt = "---- ".bab_translate("Top")." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$req = "select id, title from ".BAB_ARTICLES_TBL." where archive='N' and id_topic='".$babDB->db_escape_string($id)."' order by ordering asc, date_modification desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->articletitle = bab_toHtml($arr['title']);
				$this->articleid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
		$temp = new orderArticlesTpl($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "articlesorder"));
	}


function viewArticleHistory($idart)
{
	class viewArticleHistoryTpl
		{
		public $topname;
		public $topurl;
		public $prevname;
		public $prevurl;
		public $nextname;
		public $nexturl;
		public $bottomname;
		public $bottomurl;

		function __construct($article, $pos)
			{
			global $babDB;

			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->titletxt = bab_translate("Article");
			$this->pathtxt = bab_translate("Topic");
			$this->authortxt = bab_translate("Author");
			$this->datelocktxt = bab_translate("Date");
			$this->actiontxt = bab_translate("Action");
			$this->commenttxt = bab_translate("Reason of the modification");

			$res = $babDB->db_query("select id, id_author  from ".BAB_ART_DRAFTS_TBL." where id_article='".$babDB->db_escape_string($article)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$this->bmodify = false;
				$this->editdrafttxt = false;
				}
			else
				{
				$this->editdrafttxt = false;
				$this->bmodify = true;
				}


			$res = $babDB->db_query("select count(*) as total from ".BAB_ART_LOG_TBL." where id_article='".$babDB->db_escape_string($article)."'");
			$row = $babDB->db_fetch_array($res);
			$total = $row["total"];

			$url = bab_url::get_request_gp();
			if( $total > BAB_ART_MAXLOGS)
				{
				if( $pos > 0)
					{
					$url->pos = 0;
					$this->topurl = bab_toHtml($url->toString());
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - BAB_ART_MAXLOGS;
				if( $next >= 0)
					{
					$url->pos = $next;
					$this->prevurl = bab_toHtml($url->toString());
					$this->prevname = "&lt;";
					}

				$next = $pos + BAB_ART_MAXLOGS;
				if( $next < $total)
					{
					$url->pos = $next;
					$this->nexturl = bab_toHtml($url->toString());
					$this->nextname = "&gt;";
					if( $next + BAB_ART_MAXLOGS < $total)
						{
						$bottom = $total - BAB_ART_MAXLOGS;
						}
					else
						{
						$bottom = $next;
						}

					$url->pos = $bottom;
					$this->bottomurl = bab_toHtml($url->toString());
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * from ".BAB_ART_LOG_TBL." where id_article='".$babDB->db_escape_string($article)."' order by ordering desc, date_log desc";
			if( $total > BAB_ART_MAXLOGS)
				{
				$req .= " limit ".$babDB->db_escape_string($pos).",".BAB_ART_MAXLOGS;
				}


			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextlog()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
				$arr = $babDB->db_fetch_array($this->res);
				$this->datelock = bab_toHtml(bab_strftime(bab_mktime($arr['date_log']), true));
				$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				switch($arr['action_log'])
					{
					case 'lock': $this->action = bab_translate("Lock"); break;
					case 'unlock': $this->action = bab_translate("Unlock"); break;
					case 'commit': $this->action = bab_translate("Commit"); break;
					case 'refused': $this->action = bab_translate("Refused"); break;
					case 'accepted': $this->action = bab_translate("Accepted"); break;
					default: $this->action = ""; break;
					}
				$this->comment = str_replace("\n", "<br>", bab_toHtml($arr['art_log']));
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		}

	global $babBody;

	$temp = new viewArticleHistoryTpl($idart, (int) bab_rp('pos', 0));
	$babBody->babPopup(bab_printTemplate($temp, "topman.html", "articlehistoric"));
}


function viewArticleProperties($item, $idart)
	{
	global $babBody;
	class viewArticlePropertiesTpl
		{

		    function __construct($item, $idart)
			{
			global $babBody, $babDB;
			$this->access = false;

			$req = "select at.id, at.title, at.id_topic, at.date_publication, at.date_archiving, at.restriction, count(aft.id) as totalf from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id='".$babDB->db_escape_string($idart)."' group by aft.id_article";
			$res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($res);
			if( $this->count > 0 )
				{
				$this->access = true;
				$this->idart = $idart;
				$arrart = $babDB->db_fetch_array($res);
				$this->submittxt = bab_translate("Update");
				$this->topictxt = bab_translate("Topic");
				$this->titletxt = bab_translate("Title");
				$this->idart = $idart;
				$this->item = $item;
				$this->idtopicsel = $arrart['id_topic'];
				$this->steptitle = viewCategoriesHierarchy_txt($arrart['id_topic']);

				$this->draftname = $arrart['title'];

				if( count(bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL)) > 0 )
					{
					/* Parent topics */
					$this->restopics = $babDB->db_query("select tt.id, tt.category, tt.restrict_access, tct.title, tt.notify from ".BAB_TOPICS_TBL." tt LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat where tt.id IN(".$babDB->quote(array_keys(bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL))).")");
					$this->counttopics = $babDB->db_num_rows($this->restopics);
					$this->array_parent_topics = array();
					for ($i=0;$i<=$this->counttopics-1;$i++) {
						$this->array_parent_topics[] = $babDB->db_fetch_assoc($this->restopics);
					}

					/* Tree view popup when javascript is activated */
					global $babSkinPath;
					$this->urlimgselecttopic = $babSkinPath.'images/nodetypes/topic.png';
					$this->idcurrentparenttopic = $this->idtopicsel;
					$this->namecurrentparenttopic = '';
					for ($i=0;$i<=count($this->array_parent_topics)-1;$i++) {
						if ($this->array_parent_topics[$i]['id'] == $this->idtopicsel) {
							$this->namecurrentparenttopic = $this->array_parent_topics[$i]['category'];
						}
					}


					if( $arrart['totalf'] > 0 )
						{
						$this->warnfilemessage = bab_translate("Warning! If you change topic, you can lost associated documents");
						}
					$this->bshowtopics = true;
					}
				else
					{
					$this->counttopics = 0;
					$this->warnfilemessage = '';
					$this->bshowtopics = false;
					}

				$this->elapstime = 5;
				$this->ampm = bab_isAmPm();


				$this->cdateecheck = '';
				if( $arrart['date_publication'] != '0000-00-00 00:00:00' )
					{
					$this->cdatepcheck = 'checked';
					$rr = explode(" ", $arrart['date_publication']);
					$rr0 = explode("-", $rr[0]);
					$rr1 = explode(":", $rr[1]);
					$this->yearpub = $rr0[0];
					$this->monthpub = $rr0[1];
					$this->daypub = $rr0[2];
					$this->timepub = $rr1[0].":".$rr1[1];
					}
				else
					{
					$this->cdatescheck = '';
					$this->yearpub = date("Y");
					$this->monthpub = date("n");
					$this->daypub = date("j");
					$this->timepub = "00:00";
					}

				if( $arrart['date_archiving'] != '0000-00-00 00:00:00' )
					{
					$this->cdateecheck = 'checked';
					$rr = explode(" ", $arrart['date_archiving']);
					$rr0 = explode("-", $rr[0]);
					$rr1 = explode(":", $rr[1]);
					$this->yearend = $rr0[0];
					$this->monthend = $rr0[1];
					$this->dayend = $rr0[2];
					$this->timeend = $rr1[0].":".$rr1[1];
					}
				else
					{
					$this->cdateecheck = '';
					$this->yearend = date("Y");
					$this->monthend = date("n");
					$this->dayend = date("j");
					$this->timeend = "00:00";
					}

				$this->yearmin = min($this->yearpub, $this->yearend);
				$this->yearmin = min($this->yearmin, date("Y"));
				$this->yearmax = max($this->yearpub, $this->yearend);
				$this->yearmax = max($this->yearmax, date("Y"));

				$this->daysel = $this->daypub;
				$this->monthsel = $this->monthpub;
				$this->yearsel = $this->yearpub - $this->yearmin + 1;
				$this->timesel = $this->timepub;

				$this->datepubtitle = bab_translate("Date of publication");
				$this->datepuburl = $GLOBALS['babUrlScript']."?tg=month&callback=datePub&ymin=".abs($this->yearmin-date("Y"))."&ymax=".abs($this->yearmax+2-date("Y"));
				$this->datepubtxt = bab_translate("Publication date");
				$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=".abs($this->yearmin-date("Y"))."&ymax=".abs($this->yearmax+2-date("Y"));
				$this->dateendtxt = bab_translate("Archiving date");
				$this->invaliddate = bab_translate("ERROR: End date must be older");
				$this->invaliddate = str_replace("'", "\'", $this->invaliddate);
				$this->invaliddate = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate);

				$rr = $babDB->db_fetch_array($babDB->db_query("select restrict_access from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($arrart['id_topic'])."'"));
				if( $arrart['restriction'] != '' || (isset($rr['restrict_access']) && $rr['restrict_access'] == 'Y'))
					{
					$this->restrictaccess = true;
					$this->restrictiontitletxt = bab_translate("Access restriction");
					$this->operatortxt = bab_translate("Operator");
					$this->ortxt = bab_translate("Or");
					$this->andtxt = bab_translate("And");
					$this->groupstxt = bab_translate("Groups");
					$this->restrictiontxt = bab_translate("Access restriction");
					$this->norestricttxt = bab_translate("No restriction");
					$this->yesrestricttxt = bab_translate("Groups");
					$this->resgrp = $babDB->db_query("select * from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arrart['id_topic'])."' and id_group > '2'");
					if( $this->resgrp )
						{
						$this->countgrp = $babDB->db_num_rows($this->resgrp);
						}
					else
						{
						$this->countgrp = 0;
						}

					if( strchr($arrart['restriction'], "&"))
						{
						$this->arrrest = explode('&', $arrart['restriction']);
						$this->operatororysel = '';
						$this->operatorornsel = 'selected';
						}
					else if( strchr($arrart['restriction'], ","))
						{
						$this->arrrest = explode(',', $arrart['restriction']);
						$this->operatororysel = 'selected';
						$this->operatorornsel = '';
						}
					else
						{
						$this->arrrest = array($arrart['restriction']);
						$this->operatororysel = '';
						$this->operatorornsel = '';
						}

					if( empty($arrart['restriction']))
						{
						$this->norestrictsel = 'selected';
						$this->yesrestrictsel = '';
						}
					else
						{
						$this->norestrictsel = '';
						$this->yesrestrictsel = 'selected';
						}
					}
				}
			else
				{
				$babBody->addError(bab_translate("Access denied"));
				}
			}

		function getnexttopic()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counttopics)
				{
				$this->topicname = $this->array_parent_topics[$i]['category'];
				$this->categoryname = $this->array_parent_topics[$i]['title'];
				$this->idtopic = $this->array_parent_topics[$i]['id'];
				if( $this->idtopicsel == $this->array_parent_topics[$i]['id'] )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextgroup()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->grpid = $arr['id_group'];
				if ($this->grpid > BAB_ACL_GROUP_TREE) {
					$this->grpid -= BAB_ACL_GROUP_TREE;
				}
				$this->grpname = bab_getGroupName($this->grpid);

				if( in_array($this->grpid, $this->arrrest))
					{
					$this->grpcheck = 'checked';
					}
				else
					{
					$this->grpcheck = '';
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}


		function getnextday()
			{
			static $i = 1, $p=0;

			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( $this->daysel == $i)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $p == 0 )
					{
					$this->daysel = $this->dayend;
					$p++;
					}
				$i = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			static $i = 1, $p;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = bab_DateStrings::getMonth($i);
				if( $this->monthsel == $i)
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}

				$i++;
				return true;
				}
			else
				{
				if( $p == 0)
					{
					$this->monthsel = $this->monthend;
					$p++;
					}
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0, $p;
			if( $i < ($this->yearmax - $this->yearmin) + 3)
				{
				$this->yearid = $i+1;
				$this->yearidval = $this->yearmin + $i;
				if( $this->yearsel == $this->yearid )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $p == 0 )
					{
					$this->yearsel = $this->yearend - $this->yearmin + 1;
					$p++;
					}
				$i = 0;
				return false;
				}

			}

		function getnexttime()
			{

			static $i = 0, $p = 0;

			if( $i < 1440/$this->elapstime)
				{
				$this->timeval = sprintf("%02d:%02d", ($i*$this->elapstime)/60, ($i*$this->elapstime)%60);
				if( $this->ampm )
					{
					$this->time = bab_toAmPm($this->timeval);
					}
				else
					{
					$this->time = $this->timeval;
					}
				if( $this->timeval == $this->timesel )
					{
					$this->selected = "selected";
					}
				else
					{
					$this->selected = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $p == 0)
					{
					$this->timesel = $this->timeend;
					$p++;
					}
				$i = 0;
				return false;
				}

			}
		}

	global $babBody, $babScriptPath;
	$babBody->addJavascriptFile($babScriptPath.'bab_dialog.js');

	$temp = new viewArticlePropertiesTpl($item, $idart);
	$babBody->babPopup(bab_printTemplate($temp, "topman.html", "propertiesarticle"));
	}


function siteHomePage0($id)
	{

	global $babBody;
	class siteHomePage0Tpl
		{
		public $create;

		public $moveup;
		public $movedown;

		public $id;
		public $arr = array();
		public $db;
		public $res;

		public $listhometxt;
		public $listpagetxt;
		public $title;

		function __construct($id)
			{
			global $babDB;
			$this->title = bab_translate("Unregistered users home page");
			$this->listhometxt = bab_translate("---- Proposed Home articles ----");
			$this->listpagetxt = bab_translate("---- Home page articles ----");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->id = (int) $id;

			$req = "select at.title, ht.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_HOMEPAGES_TBL." ht on at.id=ht.id_article where ht.id_group='2' and ht.id_site='".$babDB->db_escape_string($id)."' and ht.ordering='0' order by ht.ordering asc";

			$this->reshome0 = $babDB->db_query($req);
			$this->counthome0 = $babDB->db_num_rows($this->reshome0);

			$req = "select at.title, ht.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_HOMEPAGES_TBL." ht on at.id=ht.id_article where ht.id_group='2' and ht.id_site='".$babDB->db_escape_string($id)."' and ht.ordering!='0' order by ht.ordering asc";

			$this->respage0 = $babDB->db_query($req);
			$this->countpage0 = $babDB->db_num_rows($this->respage0);
			}

		function getnexthome0()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counthome0 )
				{
				$arr = $babDB->db_fetch_array($this->reshome0 );
				$this->home0id = $arr['id_article'];
				$this->home0val = $arr['title'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpage0()
			{
			global $babDB;
			static $k = 0;
			if( $k < $this->countpage0 )
				{
				$arr = $babDB->db_fetch_array($this->respage0 );
				$this->page0id = $arr['id_article'];
				$this->page0val = $arr['title'];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

		$temp0 = new siteHomePage0Tpl($id);
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp0, "topman.html", "sitehomepage0"));
	}

function siteHomePage1($id)
	{

	global $babBody;
	class siteHomePage1Tpl
		{
		public $create;

		public $moveup;
		public $movedown;

		public $id;
		public $arr = array();
		public $db;
		public $res;

		public $listhometxt;
		public $listpagetxt;
		public $title;

		function __construct($id)
			{
			global $babDB;
			$this->title = bab_translate("Registered users home page");
			$this->listhometxt = bab_translate("---- Proposed Home articles ----");
			$this->listpagetxt = bab_translate("---- Home page articles ----");
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->create = bab_translate("Modify");
			$this->id = (int) $id;

			$req = "select at.title, ht.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_HOMEPAGES_TBL." ht on at.id=ht.id_article where ht.id_group='1' and ht.id_site='".$babDB->db_escape_string($id)."' and ht.ordering='0' and (at.date_archiving = '0000-00-00 00:00' OR at.date_archiving >= now()) order by ht.ordering asc";

			$this->reshome1 = $babDB->db_query($req);
			$this->counthome1 = $babDB->db_num_rows($this->reshome1);

			$req = "select at.title, ht.id_article from ".BAB_ARTICLES_TBL." at left join ".BAB_HOMEPAGES_TBL." ht on at.id=ht.id_article where ht.id_group='1' and ht.id_site='".$babDB->db_escape_string($id)."' and ht.ordering!='0' and (at.date_archiving = '0000-00-00 00:00' OR at.date_archiving >= now()) order by ht.ordering asc";

			$this->respage1 = $babDB->db_query($req);
			$this->countpage1 = $babDB->db_num_rows($this->respage1);

			}

		function getnexthome1()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counthome1 )
				{
				$arr = $babDB->db_fetch_array($this->reshome1 );
				$this->home1id = $arr['id_article'];
				$this->home1val = $arr['title'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpage1()
			{
			global $babDB;
			static $k = 0;
			if( $k < $this->countpage1 )
				{
				$arr = $babDB->db_fetch_array($this->respage1 );
				$this->page1id = $arr['id_article'];
				$this->page1val = $arr['title'];
				$k++;
				return true;
				}
			else
				return false;
			}
		}

		$temp0 = new siteHomePage1Tpl($id);
	$babBody->babecho(	bab_printTemplate($temp0, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp0, "topman.html", "sitehomepage1"));
	}


function displayTags()
{
	global $babBody;
	class displayTagsCls
		{

		    function __construct()
			{
			global $babDB;

			$this->tags_txt = bab_translate("Tags to add ( Comma separated: tag1, tag2, etc )");
			$this->update_txt = bab_translate("Update");
			$this->add_txt = bab_translate("Add");
			$this->tag_txt = bab_translate("Tag to update ( empty = delete )");

			$this->res = $babDB->db_query("select * from ".BAB_TAGS_TBL." order by tag_name asc");
			$this->count = $babDB->db_num_rows($this->res);
			$this->tagsvalue= isset($GLOBALS['tagsvalue'])?$GLOBALS['tagsvalue']: '';
			$this->tagvalue= isset($GLOBALS['tagvalue'])?$GLOBALS['tagvalue']: '';
			$this->tagidvalue= isset($GLOBALS['tagidvalue'])?$GLOBALS['tagidvalue']: '';
			}

		function getnexttag()
			{
			global $babDB;
			static $k = 0;
			if( $k < $this->count )
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->tagname = $arr['tag_name'];
				$this->tagid = $arr['id'];
				if( isset($GLOBALS['lasttags']) && in_array($this->tagid, $GLOBALS['lasttags']) )
					{
					$this->big = true;
					}
				else
					{
					$this->big = false;
					}
				$k++;
				return true;
				}
			else
				return false;
			}
		}

	$temp = new displayTagsCls();
	$babBody->babecho(	bab_printTemplate($temp, "topman.html", "tagsman"));
}


function importTagsFile()
	{
	global $babBody;
	class importTagsFileTpl
		{
		public $import;
		public $name;
		public $id;
		public $separator;
		public $other;
		public $comma;
		public $tab;

		function __construct()
			{
			$this->import = bab_translate("Import");
			$this->name = bab_translate("File");
			$this->separator = bab_translate("Separator");
			$this->other = bab_translate("Other");
			$this->comma = bab_translate("Comma");
			$this->tab = bab_translate("Tab");
			}
		}

		$temp = new importTagsFileTpl();
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "tagsimpfile"));
	}



function mapTagsImportFile($file, $tmpfile, $wsepar, $separ)
	{
	global $babBody;
	class mapTagsImportFileTpl
		{
		public $res;
		public $count;
		public $db;
		public $id;

		function __construct($pfile, $wsepar, $separ)
			{
			$this->helpfields = bab_translate("Choose the column");
			$this->process = bab_translate("Import");
			$this->ofieldname = bab_translate("Column");

			$this->pfile = $pfile;

			switch($wsepar)
				{
				case "1":
					$separ = ",";
					break;
				case "2":
					$separ = "\t";
					break;
				default:
					if( empty($separ))
						$separ = ",";
					break;
				}
			$fd = fopen($pfile, "r");
			$this->arr = fgetcsv( $fd, 4096, $separ);
			fclose($fd);
			$this->separ = $separ;
			$this->count = count($this->arr);
			}

		function getnextval()
			{
			static $i = 0;
			static $k = 0;
			if( $i < $this->count)
				{
				$this->ffieldid = $i;
				$this->ffieldname = $this->arr[$i];
				$i++;
				return true;
				}
			else
				{
				$k++;
				$i = 0;
				return false;
				}
			}

		}

	include_once $GLOBALS['babInstallPath']."utilit/tempfile.php";
	$tmpdir = get_cfg_var('upload_tmp_dir');
	if( empty($tmpdir))
		$tmpdir = session_save_path();

	$tf = new babTempFiles($tmpdir);
	$nf = $tf->tempfile($tmpfile, $file);
	if( empty($nf))
		{
		$babBody->msgerror = bab_translate("Cannot create temporary file");
		return;
		}
		$temp = new mapTagsImportFileTpl($nf, $wsepar, $separ);
	$babBody->babecho(	bab_printTemplate($temp,"topman.html", "tagsmapfile"));
	}

function addToHomePages($item, $homepage, $art)
{
	global $babBody, $babDB;
	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($item)."' order by date desc";
	$res = $babDB->db_query($req);
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( count($art) > 0 && in_array($arr['id'], $art))
			{
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$babDB->db_escape_string($arr['id'])."' and id_group='".$babDB->db_escape_string($homepage)."' and id_site='".$babDB->db_escape_string($babBody->babsite['id'])."'";
				$res2 = $babDB->db_query($req);
				if( !$res2 || $babDB->db_num_rows($res2) < 1)
				{
					$req = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$babDB->db_escape_string($arr['id']). "', '" . $babDB->db_escape_string($babBody->babsite['id']). "', '" . $babDB->db_escape_string($homepage). "')";
					$babDB->db_query($req);
					notifyArticleHomePage(bab_getCategoryTitle($item), $arr['title'], $homepage, $homepage);

				}
			}
		}
}

function removeFromHomePages($homepage, $art)
{
	global $babBody, $babDB;

	$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article IN (".$babDB->quote($art).") and id_group='".$babDB->db_escape_string($homepage)."' and id_site='".$babDB->db_escape_string($babBody->babsite['id'])."'";
	$babDB->db_query($req);
}

function archiveArticles($item, $aart)
{
	global $babDB;
	$cnt = count($aart);
	for($i = 0; $i < $cnt; $i++)
		{
		$babDB->db_query("update ".BAB_ARTICLES_TBL." set archive='Y' where id='".$babDB->db_escape_string($aart[$i])."'");
		$babDB->db_query("delete from ".BAB_HOMEPAGES_TBL." where id_article='".$babDB->db_escape_string($aart[$i])."'");
		}
}

function unarchiveArticles($item, $aart)
{
	global $babDB, $idx;
	$idx = "Articles";
	$cnt = count($aart);
	for($i = 0; $i < $cnt; $i++)
		{
		$babDB->db_query("update ".BAB_ARTICLES_TBL." set archive='N', date_archiving='0000-00-00 00:00' where id='".$babDB->db_escape_string($aart[$i])."'");
		}
}

function saveOrderArticles($id, $listarts)
	{
	global $babDB;

	$babDB->db_query("update ".BAB_ARTICLES_TBL." set ordering='0' where id_topic='".$babDB->db_escape_string($id)."'");
	for($i=0; $i < count($listarts); $i++)
		{
		$babDB->db_query("update ".BAB_ARTICLES_TBL." set ordering='".($i+1)."' where id='".$babDB->db_escape_string($listarts[$i])."'");
		}
	}


function saveArticleProperties()
{
	global $babDB;
	$idart = bab_rp('idart');
	$topicid = bab_rp('topicid');
	$cdatep = bab_rp('cdatep', null);
	$yearpub = bab_rp('yearpub');
	$monthpub = bab_rp('monthpub');
	$daypub = bab_rp('daypub');
	$timepub = bab_rp('timepub');
	$cdatee = bab_rp('cdatee', null);
	$yearend = bab_rp('yearend');
	$monthend = bab_rp('monthend');
	$dayend = bab_rp('dayend');
	$timeend = bab_rp('timeend');
	$restriction = bab_rp('restriction');
	$operator = bab_rp('operator');
	$grpids = bab_rp('grpids');
	$ymin = bab_rp('ymin');

	if( isset($cdatep) || isset($cdatee) || isset($topicid) || isset($restriction))
	{
	$res = $babDB->db_query("select at.id_topic, count(aft.id) as totalf from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id='".$babDB->db_escape_string($idart)."' group by aft.id_article");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arrreq = array();

		$arrart = $babDB->db_fetch_array($res);
		if( isset($cdatep))
			{
			$date_pub = sprintf("%04d-%02d-%02d %s:00", $ymin + $yearpub - 1, $monthpub, $daypub, $timepub);
			$arrreq[] = "date_publication='".$babDB->db_escape_string($date_pub)."'";
			}
		else
			{
			$arrreq[] = "date_publication='0000-00-00 00:00'";
			}

		if( isset($cdatee))
			{
			$date_end = sprintf("%04d-%02d-%02d %s:00", $ymin + $yearend - 1, $monthend, $dayend, $timeend);
			$arrreq[] = "date_archiving='".$babDB->db_escape_string($date_end)."'";
			}
		else
			{
			$arrreq[] = "date_archiving='0000-00-00 00:00'";
			}

		if( isset($restriction))
			{
			if( $restriction == "1" && isset($grpids) && count($grpids) > 0)
				{
				$restriction = implode($operator, $grpids);
				}
			else
				{
				$restriction = '';
				}

			$arrreq[] = "restriction='".$babDB->db_escape_string($restriction)."'";
			}

		if( $arrart['id_topic'] != $topicid )
			{
			$babDB->db_query("update ".BAB_COMMENTS_TBL." set id_topic='".$babDB->db_escape_string($topicid)."' where id_article='".$babDB->db_escape_string($idart)."' and id_topic='".$babDB->db_escape_string($topicid)."'");

			if( $arrart['totalf'] >  0 )
				{
				list($allowattach) = $babDB->db_fetch_array($babDB->db_query("select allow_attachments from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topicid)."'"));
				if( $allowattach ==  'N' )
					{
					include_once $GLOBALS['babInstallPath']."utilit/artincl.php";
					bab_deleteArticleFiles($idart);
					}
				}
			$arrreq[] = "id_topic='".$babDB->db_escape_string($topicid)."'";
			}

		if( count($arrreq) > 0 )
			{
			$req = "update ".BAB_ARTICLES_TBL." set ".implode(',', $arrreq)." where id='".$babDB->db_escape_string($idart)."'";
			$babDB->db_query($req);
			}
		}
	}
}

function siteUpdateHomePage0($item, $listpage0)
	{
	global $babDB;
	$req = "update ".BAB_HOMEPAGES_TBL." set ordering='0' where id_site='".$babDB->db_escape_string($item)."' and id_group='2'";
	$babDB->db_query($req);

	for($i=0; $i < count($listpage0); $i++)
		{
		$req = "update ".BAB_HOMEPAGES_TBL." set ordering='".($i + 1)."' where id_group='2' and id_site='".$babDB->db_escape_string($item)."' and id_article='".$babDB->db_escape_string($listpage0[$i])."'";
		$babDB->db_query($req);
		}
	return true;
	}

function siteUpdateHomePage1($item, $listpage1)
	{
	global $babDB;
	$req = "update ".BAB_HOMEPAGES_TBL." set ordering='0' where id_site='".$babDB->db_escape_string($item)."' and id_group='1'";
	$babDB->db_query($req);

	for($i=0; $i < count($listpage1); $i++)
		{
		$req = "update ".BAB_HOMEPAGES_TBL." set ordering='".($i + 1)."' where id_group='1' and id_site='".$babDB->db_escape_string($item)."' and id_article='".$babDB->db_escape_string($listpage1[$i])."'";
		$babDB->db_query($req);
		}
	return true;
	}


function topman_init($item)
{
	global $babBody, $babDB;
	$arrinit = array();

	$res = $babDB->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($item)."' and archive='Y'");
	list($arrinit['nbarchive']) = $babDB->db_fetch_row($res);

	$res = $babDB->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($item)."' and archive='N'");
	list($arrinit['nbonline']) = $babDB->db_fetch_row($res);

	$arrinit['hman'] = bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']);

	return $arrinit;
}

function bab_removeDraft($art){
	global $babDB;

	$req = "UPDATE ".BAB_ART_DRAFTS_TBL." SET id_article = 0, id_topic = 0 where id='".$babDB->db_escape_string($art)."'";
	$babDB->db_query($req);

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".bab_rp('item'));
	exit;
}

/* main */


$iNbSeconds = 2 * 86400; //2 jours
require_once dirname(__FILE__) . '/utilit/artincl.php';
bab_PublicationImageUploader::deleteOutDatedTempImage($iNbSeconds);

$idx = bab_rp('idx', 'list');
$item = bab_rp('item', null);

if( isset($item) && bab_isUserTopicManager($item) ) {
	$manager = true;
}
else {
	$manager = false;
}



$popupmessage = bab_rp('popupmessage');
$refreshurl = bab_rp('refreshurl');



if("articles" === bab_rp('upart') && $manager)
	{
	if (isset($_POST['action'])) {

		switch($_POST['action'])
			{
			case "homepage0":
				if (isset($_POST['articles'])) {
					bab_requireSaveMethod() && addToHomePages($_POST['item'], 2, $_POST['articles']);
				}
				break;

			case "homepage1":
				if (isset($_POST['articles'])) {
					bab_requireSaveMethod() && addToHomePages($_POST['item'], 1, $_POST['articles']);
				}
				break;

			case "homepage":
				if (isset($_POST['articles'])) {
				    bab_requireDeleteMethod();
					removeFromHomePages(2, $_POST['articles']);
					removeFromHomePages(1, $_POST['articles']);
					}
				break;

			case "archive":
				if (isset($_POST['articles'])) {
					bab_requireSaveMethod() && archiveArticles($_POST['item'], $_POST['articles']);
				}
				break;

			case "Deletea":
				include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
				if (isset($_POST['comments']) && count($_POST['comments']) > 0) {
					foreach($_POST['comments'] as $idc) {
					    bab_requireDeleteMethod() && bab_deleteComment($idc);
					}
				}
				if (isset($_POST['articles'])) {
					bab_requireDeleteMethod() && bab_confirmDeleteArticles(implode(',',$_POST['articles']));
				}
				break;
			}
	    }

	if ($_POST['idx'] == 'unarch') {
		bab_requireSaveMethod() && unarchiveArticles($_POST['item'], $_POST['aart']);
		}
	}
elseif("file" === bab_rp('delf') && $manager)
	{
	bab_requireDeleteMethod() && delDocumentArticle(bab_rp('idf'));
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$item."&art=".bab_rp('art'));
	exit;
	}
elseif("com" === bab_rp('delc') && $manager)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_requireDeleteMethod() && bab_deleteComment($idc);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=viewa&item=".$item."&art=".bab_rp('art'));
	exit;
	}
elseif( bab_rp('update')  && $manager)
	{
	if("order" === bab_rp('update') )
		{
		bab_requireSaveMethod() && saveOrderArticles($item, bab_rp('listarts'));
		}
	elseif("propa" === bab_rp('update') )
		{
		bab_requireSaveMethod() && saveArticleProperties($item, bab_rp('idart'));
		$idx='unload';
		$popupmessage = bab_translate("Update done");
		$refreshurl = $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item;
		}
	}
elseif( bab_rp('updateh') && bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
	{
	if( "homepage0" === bab_rp('updateh') )
		{
		bab_requireSaveMethod() && siteUpdateHomePage0($item, bab_rp('listpage0', array()));
		}
	else if( "homepage1" === bab_rp('updateh') )
		{
		bab_requireSaveMethod() && siteUpdateHomePage1($item, bab_rp('listpage1', array()));
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topman&idx=list");
	exit;
	}

switch($idx)
	{
	case "rmdraft":
		$art = bab_rp('art', '');
		if ( $manager && $art != '' )
		{
			bab_requireDeleteMethod() && bab_removeDraft($art);
		}
		else
		{
			echo bab_translate("Access denied");
		}
		exit;
		break;

	case "unload":
		popupUnload($popupmessage, $refreshurl);
		exit;

	case "getf":
		if ( $manager ) {
		    bab_getDocumentArticle( bab_rp('idf'));
		}
		else {
			echo bab_translate("Access denied");
		}
		exit;
		break;

	case "viewa":
		if ( $manager ) {
		    viewArticle(bab_rp('art'));
		}
		else {
			echo bab_translate("Access denied");
		}
		exit;


	case 'history';
		if ( $manager ) {
			$babBody->setTitle = bab_translate("Article history");
			$babBody->addItemMenu("propa", bab_translate("Properties"), $GLOBALS['babUrlScript']."?tg=topman&idx=propa&item=".$item."&art=".bab_rp('art'));
			$babBody->addItemMenu("history", bab_translate("History"), $GLOBALS['babUrlScript']."?tg=topman&idx=history&item=".$item."&art=".bab_rp('art'));
			$babBody->setCurrentItemMenu($idx);
			viewArticleHistory(bab_rp('art'));
		}
		else {
			echo bab_translate("Access denied");
		}
		exit;
		break;

	case "propa":
		if( $manager )
		{
			$babBody->setTitle = bab_translate("Article properties");
			$babBody->addItemMenu("propa", bab_translate("Properties"), $GLOBALS['babUrlScript']."?tg=topman&idx=propa&item=".$item."&art=".bab_rp('art'));
			$babBody->addItemMenu("history", bab_translate("History"), $GLOBALS['babUrlScript']."?tg=topman&idx=history&item=".$item."&art=".bab_rp('art'));
			$babBody->setCurrentItemMenu($idx);
			viewArticleProperties( $item, bab_rp('art'));
		}
		else
		{
			echo bab_translate("Access denied");
		}
		exit;
		break;
	case "deletea":
		$arrinit = topman_init($item);
		if( $manager && $arrinit['nbonline'] > 0)
		{
		$babBody->title = bab_translate("Delete articles");
		deleteArticles(bab_rp('art'), $item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		$babBody->addItemMenu("deletea", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topman&idx=deletea&art=".bab_rp('art'));
		if( $arrinit['nbarchive'] > 0)
			{
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
			}
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "alist":
		$arrinit = topman_init($item);
		if( $manager  && $arrinit['nbarchive'] > 0)
		{
		$babBody->title = bab_translate("List of old articles").": ".bab_getCategoryTitle($item);
		listOldArticles($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( $arrinit['nbonline'] > 0 )
			{
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
			}
		$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;


	case "Articles":
		$arrinit = topman_init($item);
		if( $manager )
		{
		$babBody->title = bab_translate("List of articles").": ".bab_getCategoryTitle($item);
		if( $arrinit['nbonline'] > 0 )
			{
			listArticles($item);
			}
		else
			{
			$babBody->msgerror = bab_translate("No article in this topic !");
			}
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		$babBody->addItemMenu("ord", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topman&idx=ord&item=".$item);
		if( $arrinit['nbarchive'] > 0)
			{
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
			}
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "ord":
		$arrinit = topman_init($item);
		if( $manager && $arrinit['nbonline'] > 0)
		{
		$babBody->title = bab_translate("Order of articles").": ".bab_getCategoryTitle($item);
		orderArticles($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$item);
		$babBody->addItemMenu("ord", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topman&idx=ord&item=".$item);
		if($arrinit['nbarchive'] > 0)
			{
			$babBody->addItemMenu("alist", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=topman&idx=alist&item=".$item);
			}
		if( $arrinit['hman'] > 0)
			{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "hpriv":
		if( $manager || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
		{
		$babBody->title = bab_translate("Registered users home page for site").": ".$babBody->babsite['name'];
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']) )
			{
			siteHomePage1(bab_rp('ids'));
			$babBody->addItemMenu("hpriv", bab_translate("Private home page"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			$babBody->addItemMenu("hpub", bab_translate("Public home page"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpub&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	case "hpub":
		if( $manager || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
		{
		$babBody->title = bab_translate("Unregistered users home page for site").": ".$babBody->babsite['name'];
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		if( bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']) )
			{
			siteHomePage0(bab_rp('ids'));
			$babBody->addItemMenu("hpriv", bab_translate("Private home page"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
			$babBody->addItemMenu("hpub", bab_translate("Public home page"),$GLOBALS['babUrlScript']."?tg=topman&idx=hpub&ids=".$babBody->babsite['id']);
			}
		}
		else
		{
			$babBody->msgerror = bab_translate("Access denied");
			break;
		}
		break;

	default:
	case "list":
		$babBody->title = bab_translate("List of managed topics");
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topman");
		listCategories();
		if( bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']) )
		{
			$babBody->addItemMenu("hman", bab_translate("Home pages"), $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id']);
		}
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserArticlesMan');
