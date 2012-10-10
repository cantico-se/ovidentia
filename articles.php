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
/**
* @internal SEC1 NA 08/12/2006 FULL
*/
include 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';
include_once $babInstallPath.'utilit/urlincl.php';




abstract class bab_listArticles extends categoriesHierarchy
{

	var $template;
	var $topurl;
	var $bottomurl;
	var $nexturl;
	var $prevurl;
	var $topname;
	var $bottomname;
	var $nextname;
	var $prevname;
	var $bnavigation;
	var $printtxt;
	var $deletetxt;
	var $modifytxt;
	var $approver;
	var $commentstxt;
	var $commentsurl;
	var $content;
	var $title;
	var $topictitle;
	var $bbody;
	var $author;
	var $articleauthor;
	var $articledate;
	var $moretxt;
	var $articleid;
	var $moreurl;
	var $modifyurl;
	var $delurl;
	var $morename;
	var $attachmentxt;

	protected $tags;

	public function __construct($topics)
		{
		global $babDB, $arrtop;

		parent::__construct($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
		$this->topurl = "";
		$this->bottomurl = "";
		$this->nexturl = "";
		$this->prevurl = "";
		$this->topname = "";
		$this->bottomname = "";
		$this->nextname = "";
		$this->prevname = "";

		$this->bnavigation = false;
		$this->approver = false;
		$this->commentstxt = false;

		$this->printtxt = bab_translate("Print Friendly");
		$this->deletetxt = bab_translate("Delete");
		$this->modifytxt = bab_translate("Modify");
		$this->moretxt = bab_translate("Read More");
		$this->morename = bab_translate("Read more");
		$this->attachmentxt = bab_translate("Associated documents");
		$this->tagstxt = bab_translate("Associated tags");

		$this->template = "default";
		if( $arrtop['display_tmpl'] != '' )
			{
			$this->template = $arrtop['display_tmpl'];
			$file = "topicsdisplay.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}

			$tplfound = false;
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( mb_substr($arr[$i], 5) == $this->template )
						{
						$tplfound = true;
						break;
						}
					}
				}
			if( !$tplfound )
				{
				$this->template = "default";
				}
			}

		$this->topicbuttons = false;

		if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $this->topics))
			{
			$this->submittxt = bab_translate("Submit");
			$this->bsubmiturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->topics);
			$this->bsubmit = true;
			$this->topicbuttons = true;
			}
		else
			{
			$this->bsubmit = false;
			}

		switch(bab_TopicNotificationSubscription($this->topics, $GLOBALS['BAB_SESS_USERID']))
			{
				case -1:
					$this->bsubscription = false;
					$this->subscriptiontxt = '';
					break;

				case 0:
					$this->bsubscription = true;
					$this->topicbuttons = true;
					$this->subscriptiontxt = bab_translate('Notify me by email when an article is published');
					break;

				case 1:
					$this->bsubscription = true;
					$this->topicbuttons = true;
					$this->subscriptiontxt = bab_translate('Stop receiving notifications for this topic');
					break;
			}

		$this->subscriptionurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=subscription&topic=".$this->topics);

		}

	/**
	 * Get list of tags
	 * @param int $article
	 * @return array
	 */
	protected function getTags($article)
	{
		global $arrtop;

		if ('Y' !== $arrtop['busetags'])
		{
			return array();
		}

		require_once dirname(__FILE__) . '/utilit/tagApi.php';

		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');
		$tags = array();
		$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'articles', 'article', $article));
		$oIterator->orderAsc('tag_name');
		foreach($oIterator as $oTag) {
			$tags[] = $oTag->getName();
		}

		return $tags;
	}

	/**
	 * Template method
	 */
	public function getnexttag()
	{

		if (list(, $tag) = each($this->tags))
		{
			$this->tagname = bab_toHtml($tag);
			$searchurl = bab_url::get_request();
			$searchurl->tg = 'search';
			$searchurl->idx = 'find';
			$searchurl->what = $tag;
			$this->searchurl = bab_toHtml($searchurl->toString());
			return true;
		}

		return false;
	}


	/**
	 * @param	int	$article
	 * @return bab_url
	 */
	protected function getImageUrl($article, $width = 100, $height = 100)
	{
		global $arrtop;

		if ('Y' !== $arrtop['allow_addImg'])
		{
			return null;
		}

		$img = bab_getImageArticle($article);

		if (!is_array($img)) {
			return null;
		}

		if ($T = @bab_functionality::get('Thumbnailer'))
		{
			require_once dirname(__FILE__) . '/utilit/pathUtil.class.php';

			$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
			$sFullPathName		= $sUploadPath . $img['relativePath'] . $img['name'];

			$T->setSourceFile($sFullPathName);
			$url = $T->getThumbnail($width, $height);

			if (is_string($url))
			{
				return new bab_url($url);
			}
		}

		$url = bab_url::get_request('tg');
		$url->idx = 'getImage';
		$url->sImage = $img['name'];
		$url->iIdArticle = $article;
		$url->iWidth = $width;
		$url->iHeight = $height;

		return $url;

	}
}

function listArticles($topics)
	{
	global $babBody, $babDB, $arrtop;

	class temp extends bab_listArticles
		{



		public function __construct($topics)
			{
			global $babDB;
			parent::__construct($topics);
			$babDB = $GLOBALS['babDB'];
			$this->bmanager = bab_isUserTopicManager($this->topics);

			$req = "select at.id, at.id_topic, at.id_author, at.date, at.date_modification, at.title, at.head, at.body, at.head_format, at.body_format, LENGTH(at.body) as blen, at.restriction from ".BAB_ARTICLES_TBL." at where at.id_topic='".$babDB->db_escape_string($topics)."' and at.archive='N' and (date_publication='0000-00-00 00:00:00' or date_publication <= now())";
			$langFilterValue = bab_getInstance('babLanguageFilter')->getFilterAsInt();
			switch($langFilterValue)
				{
					case 2:
						$req .= " and (at.lang='".$babDB->db_escape_string($GLOBALS['babLanguage'])."' or at.lang='*' or lang='')  order by at.ordering asc";
						break;
					case 1:
						$req .= " and ((at.lang like '". $babDB->db_escape_like(mb_substr($GLOBALS['babLanguage'], 0, 2)) ."%') or at.lang='*' or lang='') order by at.ordering asc";
						break;
					case 0:
					default:
						$req .= " order by at.ordering asc";
				}

			$req .= ", at.date_modification desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics))
				{
				$this->bcomment = true;
				}
			else
				{
				$this->bcomment = false;
				}



			/* template variables */
			$this->babtpl_topicid = bab_toHtml($this->topics);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			}

		public function getnext(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				if( $this->arr['restriction'] != '' && !bab_articleAccessByRestriction($this->arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->articleid = bab_toHtml($this->arr['id']);
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					{
					$this->articleauthor = bab_toHtml($author);
					}
				else
					{
					$this->articleauthor = bab_translate("Anonymous");
					}

				if( bab_isArticleModifiable($this->arr['id']))
					{
					$this->bmodify = true;
					$res =  $babDB->db_query("select id, id_author from ".BAB_ART_DRAFTS_TBL." where id_article='".$babDB->db_escape_string($this->arr['id'])."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$rr = $babDB->db_fetch_array($res);
						$this->bmodifyurl = false;
						$this->modifybytxt = bab_translate("In modification by");
						$this->modifyauthor	= bab_toHtml(bab_getUserName($rr['id_author']));
						$this->modifyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id']);
						if( $rr['id_author'] == $GLOBALS['BAB_SESS_USERID'] )
							{
							$this->modifydrafttxt = bab_translate("Edit draft");
							$this->modifydrafturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=edit&iddraft=".$rr['id']."&rfurl=".urlencode("?tg=articles&idx=Articles&topics=".$this->topics));
							}
						}
					else
						{
						$this->bmodifyurl = true;
						$this->modifyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id']);
						}
					}
				else
					{
					$this->modifyurl = '';
					$this->bmodify = false;
					}

				if( $this->bmanager )
					{
					$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id']);
					}


				$this->articledate = bab_toHtml(bab_strftime(bab_mktime($this->arr['date_modification'])));
				$this->author = bab_translate("by") . " ". bab_toHtml($this->articleauthor). " - ". $this->articledate;

				$articleAverageRating = bab_getArticleAverageRating($this->arr['id']);
				$articleNbRatings = bab_getArticleNbRatings($this->arr['id']);

				$this->article_rating = bab_toHtml($articleAverageRating);
				$this->article_rating_percent = bab_toHtml($articleAverageRating * 20.0);
				$this->article_nb_ratings = bab_toHtml($articleNbRatings);

				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['head']);
				$editor->setFormat($this->arr['head_format']);
				$this->content = $editor->getHtml();

				/* template variables */
				$this->babtpl_authorid = bab_toHtml($this->arr['id_author']);
				$this->babtpl_articleid = $this->arr['id'];
				$this->babtpl_topicid = $this->arr['id_topic'];
				$this->babtpl_head = $this->content;

				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['body']);
				$editor->setFormat($this->arr['body_format']);
				$this->babtpl_body = $editor->getHtml();

				$this->title = bab_toHtml(stripslashes($this->arr['title']));
				$this->topictitle = bab_toHtml(bab_getCategoryTitle($this->arr['id_topic']));
				$this->printurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id']);
				$this->bbody = $this->arr['blen'];
				if( $this->bbody > 0 )
					{
					$this->moreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id']);
					}
				else
					{
					$GLOBALS['babWebStat']->addArticle($this->arr['id']);
					}

				list($totalc) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$babDB->db_escape_string($this->arr['id'])."' and confirmed='Y'"));

				if( $totalc > 0 || $this->bcomment)
					{
					$this->commentsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id']);
					$this->editcommentsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'] . '#bab_edit_comment');
					if( $totalc > 0 )
						{
						$this->commentstxt = bab_translate("Comments")."&nbsp;(".$totalc.")";
						}
					else
						{
						$this->commentstxt = bab_translate("Add Comment");
						}
 					}
				else
					{
					$this->commentsurl = '';
					$this->editcommentsurl = '';
					$this->commentstxt = '';
					}

				$this->resf = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($this->arr['id'])."' order by ordering asc");
				$this->countf = $babDB->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}


				$this->tags = $this->getTags($this->arr['id']);
				$this->btags = 0 < count($this->tags);

				if ($imgurl = $this->getImageUrl($this->arr['id']))
				{
					$this->imageurl = bab_toHtml($imgurl->toString());
				} else {
					$this->imageurl = false;
				}

				$i++;
				return true;
				}
			else
				return false;
			}

		public function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->topics."&idf=".$arr['id']);
				$this->docname = bab_toHtml($arr['name']);
				$this->docdesc = bab_toHtml($arr['description']);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}


		}


	$temp = new temp($topics);
	$babBody->babecho(	bab_printTemplate($temp, 'topicsdisplay.html', 'head_'.$temp->template));
	}


function listArchiveArticles($topics, $pos)
	{
	global $babBody, $babDB;

	class listArchiveArticlesCls extends bab_listArticles
		{

		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $newc;
		var $com;

		public function __construct($topics, $pos)
			{
			global $babDB, $arrtop;

			parent::__construct($topics);
			$maxarticles = $arrtop['max_articles'];

			$res = $babDB->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topics)."'and archive='Y'");
			list($total)= $babDB->db_fetch_array($res);

			if( $total > $maxarticles)
				{
				$this->bnavigation = true;
				if( $pos > 0)
					{
					$this->topurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - $maxarticles;
				if( $next >= 0)
					{
					$this->prevurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next);
					$this->prevname = "&lt;";
					}

				$next = $pos + $maxarticles;
				if( $next < $total)
					{
					$this->nexturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next);
					$this->nextname = "&gt;";
					if( $next + $maxarticles < $total)
						{
						$bottom = $total - $maxarticles;
						}
					else
						$bottom = $next;
					$this->bottomurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$bottom);
					$this->bottomname = "&gt;&gt;";
					}
				}
			else
				$this->bnavigation = false;


			$req = "select id, id_topic, id_author, date, title, head, head_format, LENGTH(body) as blen, restriction from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topics)."' and archive='Y' order by date desc";
			if( $total > $maxarticles)
				{
				$req .= " limit ".$babDB->db_escape_string($pos).",".$maxarticles;
				}
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$this->babtpl_topicid = bab_toHtml($this->topics);
			}

		function getnext(&$skip)
			{
			global $babDB, $new;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				if( $this->arr['restriction'] != '' && !bab_articleAccessByRestriction($this->arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->articleid = $this->arr['id'];
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					$this->articleauthor = bab_toHtml($author);
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_toHtml(bab_strftime(bab_mktime($this->arr['date'])));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;

				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['head']);
				$editor->setFormat($this->arr['head_format']);
				$this->content = $editor->getHtml();

				$this->title = bab_toHtml(stripslashes($this->arr['title']));
				$this->bbody = $this->arr['blen'];
				if( $this->bbody == 0 )
					{
					$GLOBALS['babWebStat']->addArticle($this->arr['id']);
					}
				$this->topictitle = bab_toHtml(bab_getCategoryTitle($this->arr['id_topic']));
				$this->printurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id']);

				$this->modifyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id']);
				$this->delurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id']);

				$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$babDB->db_escape_string($this->arr['id'])."' and confirmed='Y'";
				$res = $babDB->db_query($req);
				$ar = $babDB->db_fetch_array($res);
				$total = $ar['total'];
				if( $total > 0)
					{
					$this->commentsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id']);
					$this->editcommentsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'] . '#bab_edit_comment');
					$this->commentstxt = bab_translate("Comments")."&nbsp;(".$total.")";
					}
				else
					{
					$this->commentsurl = '';
					$this->editcommentsurl = '';
					$this->commentstxt = '';
					}

				$this->moreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id']);

				$this->resf = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($this->arr['id'])."' order by ordering asc");
				$this->countf = $babDB->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}

				$this->tags = $this->getTags($this->arr['id']);
				$this->btags = 0 < count($this->tags);

				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->topics."&idf=".$arr['id']);
				$this->docname = bab_toHtml($arr['name']);
				$this->docdesc = bab_toHtml($arr['description']);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		}

	$temp = new listArchiveArticlesCls($topics, $pos);
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "head_".$temp->template));
	return $temp->count;
	}


function readMore($topics, $article)
	{
	global $babBody, $babDB, $arrtop;

	class temp extends bab_listArticles
		{

		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $author;
		var $resart;
		var $countart;
		var $titleart;
		var $titleurl;
		var $topictxt;
		var $title;
		var $titlearticle;
		var $printtxt;
		var $rescom;
		var $countcom;
		var $altbg = false;

		var $babtpl_head = '';
		var $babtpl_body = '';

		public function __construct($topics, $article)
			{
			global $babDB, $arrtop;
			/* template variables */
			parent::__construct($topics);

			$this->babtpl_topicid = $topics;
			$this->babtpl_articleid = $article;
			$this->babtpl_articlesurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics;
			$this->babtpl_archiveurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics;


			$this->printtxt = bab_translate("Print Friendly");
			$babDB = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."' and (date_publication='0000-00-00 00:00:00' or date_publication <= now())";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->count = $babDB->db_num_rows($this->res);
			$res = $babDB->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($this->topics)."' and archive='Y'");
			list($this->nbarch) = $babDB->db_fetch_row($res);
			$req = "select id,title, restriction from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($this->topics)."' and archive='N' and (date_publication='0000-00-00 00:00:00' or date_publication <= now()) order by date desc";
			$this->resart = $babDB->db_query($req);
			$this->countart = $babDB->db_num_rows($this->resart);
			$this->topictxt = bab_translate("In the same topic");
			$this->commenttxt = bab_translate("Comments");
			$this->t_edit_comment = bab_translate("Edit this comment");
			$this->t_edited = bab_translate("Edited by ");
			$this->article = bab_toHtml($article);
			$this->artcount = 0;

			$this->user_is_topic_manager = bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $topics);


			$this->rescom = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id_article='".$babDB->db_escape_string($article)."' and confirmed='Y' order by date desc");
			$this->countcom = $babDB->db_num_rows($this->rescom);

			if( $this->count > 0 && $this->arr['archive'] == 'N' && (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $this->topics) || ( $arrtop['allow_update'] != '0' && $this->arr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ($arrtop['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $this->topics))))
				{
				$this->bmodify = true;
				$res =  $babDB->db_query("select id_author from ".BAB_ART_DRAFTS_TBL." where id_article='".$babDB->db_escape_string($this->arr['id'])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$rr = $babDB->db_fetch_array($res);
					$this->bmodifyurl = false;
					$this->modifybytxt = bab_translate("In modification by");
					$this->modifyauthor	= bab_toHtml(bab_getUserName($rr['id_author']));
					$this->modifyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id']);
					}
				else
					{
					$this->modifyauthor	= '';
					$this->modifytxt = bab_translate("Modify");
					$this->bmodifyurl = true;
					$this->modifyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id']);
					}
				}
			else
				{
				$this->modifyurl = '';
				$this->bmodify = false;
				}

			if( $this->arr['archive'] == 'N' && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics))
				{
				$this->commentsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id']);
				$this->editcommentsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'] . '#bab_edit_comment');
				$this->commentstxt = bab_translate("Add Comment");
				}
			else
				{
				$this->commentsurl = '';
				$this->editcommentsurl = '';
				$this->commentstxt = '';
				}



			$this->resf = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($article)."' order by ordering asc");
			$this->countf = $babDB->db_num_rows($this->resf);

			if( $this->countf > 0 )
				{
				$this->attachmentxt = bab_translate("Associated documents");
				$this->battachments = true;
				}
			else
				{
				$this->battachments = false;
				}

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			}

		function getnext(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $this->arr['restriction'] != '' && !bab_articleAccessByRestriction($this->arr['restriction']))
					{
					$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
					$skip = true;
					$i++;
					return true;
					}
				$GLOBALS['babWebStat']->addArticle($this->arr['id']);
				$this->title = bab_toHtml($this->arr['title']);

				$sHead = '';
				$sBody = '';

				$articleAverageRating = bab_getArticleAverageRating($this->arr['id']);
				$articleNbRatings = bab_getArticleNbRatings($this->arr['id']);

				$this->article_rating = bab_toHtml($articleAverageRating);
				$this->article_rating_percent = bab_toHtml($articleAverageRating * 20.0);
				$this->article_nb_ratings = bab_toHtml($articleNbRatings);

				if( !empty($this->arr['body']))
					{
					$editor = new bab_contentEditor('bab_article_head');
					$editor->setContent($this->arr['head']);
					$editor->setFormat($this->arr['head_format']);
					$sHead = $this->head = $editor->getHtml();

					$editor = new bab_contentEditor('bab_article_body');
					$editor->setContent($this->arr['body']);
					$editor->setFormat($this->arr['body_format']);
					$sBody = $this->content = $editor->getHtml();
					}
				else
					{
					$editor = new bab_contentEditor('bab_article_head');
					$editor->setContent($this->arr['head']);
					$editor->setFormat($this->arr['head_format']);
					$sHead = $this->content = $editor->getHtml();
					}

				/* template variables */
				$this->babtpl_head = $sHead;
				$this->babtpl_body = $sBody;

				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					{
					$this->articleauthor = bab_toHtml($author);
					}
				else
					{
					$this->articleauthor = bab_translate("Anonymous");
					}
				$this->articledate = bab_toHtml(bab_strftime(bab_mktime($this->arr['date'])));
				//$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->printurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id']);

				$this->tags = $this->getTags($this->arr['id']);
				$this->btags = 0 < count($this->tags);

				if ($imgurl = $this->getImageUrl($this->arr['id']))
				{
					$this->imageurl = bab_toHtml($imgurl->toString());
				} else {
					$this->imageurl = false;
				}

				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextart(&$skip)
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $babDB->db_fetch_array($this->resart);
				if( ($arr['restriction'] != '' && !bab_articleAccessByRestriction($arr['restriction'])) || $this->article == $arr['id'])
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->artcount++;
				$this->titlearticle = bab_toHtml($arr['title']);
				$this->urlview = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$this->topics."&article=".$arr['id']);
				$this->urlreadmore = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$arr['id']);

				$i++;
				return true;
				}
			else
				{
				if( $this->countart > 0 )
					{
					$babDB->db_data_seek($this->resart,0);
					}
				$i=0;
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
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				if( $arr['id_author'] )
					{
					$this->authorname = bab_getUserName($arr['id_author']);
					}
				else
					{
					$this->authorname = $arr['name'];
					}
				$this->authorname = bab_toHtml($arr['name']);
				if ($arr['id_last_editor']) {
					$this->article_is_edited = true;
					$this->lasteditorname = bab_toHtml(bab_getUserName($arr['id_last_editor'], true));
					$this->last_update = bab_toHtml(bab_strftime(bab_mktime($arr['last_update'])));
				} else {
					$this->article_is_edited = false;
				}
				$this->commenttitle = bab_toHtml($arr['subject']);
				$this->editcommenturl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=comments&idx=edit&comment_id=' . $arr['id'] . '&topics=' . $arr['id_topic'] . '&article=' . $arr['id_article']);

				$this->article_rating = bab_toHtml($arr['article_rating']);
				$this->article_rating_percent = bab_toHtml($arr['article_rating'] * 20.0);

				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$editor->setFormat($arr['message_format']);
				$this->commentbody = $editor->getHtml();

				$i++;
				return true;
				}
			else
				{
				if( $this->countcom > 0 )
					{
					$babDB->db_data_seek($this->rescom,0);
					}
				$i=0;
				return false;
				}
			}

		function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->topics."&idf=".$arr['id']);
				$this->docname = bab_toHtml($arr['name']);
				$this->docdesc = bab_toHtml($arr['description']);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		}

	if( $arrtop['display_tmpl'] != '' )
		{
		$template = $arrtop['display_tmpl'];
		$file = "topicsdisplay.html";
		$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
		if( !file_exists( $filepath ) )
			{
			$filepath = $GLOBALS['babSkinPath']."templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
				}
			}

		$tplfound = false;
		if( file_exists( $filepath ) )
			{
			$tpl = new babTemplate();
			$arr = $tpl->getTemplates($filepath);
			for( $i=0; $i < count($arr); $i++)
				{
				if( mb_substr($arr[$i], 5) == $template )
					{
					$tplfound = true;
					break;
					}
				}
			}
		if( !$tplfound )
			{
			$template = "default";
			}
		}
	else
		{
		$template = "default";
		}

	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "body_".$template));
	return $temp->nbarch;
	}

function articlePrint($topics, $article)
	{
	global $babBody;

	class temp
		{

		var $content;
		var $head;
		var $title;
		var $url;
		var $sContent;

		function temp($topics, $article)
			{
			global $babDB;

			$this->res		= $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'");
			$this->count	= $babDB->db_num_rows($this->res);
			$this->topics	= $topics;
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();

			if( $this->count > 0)
				{
				$GLOBALS['babWebStat']->addArticle($article);
				$this->arr = $babDB->db_fetch_array($this->res);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['head']);
				$editor->setFormat($this->arr['head_format']);
				$this->head = $editor->getHtml();

				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['body']);
				$editor->setFormat($this->arr['body_format']);
				$this->content = $editor->getHtml();

				$this->title = bab_toHtml($this->arr['title']);
				$this->url = "<a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babSiteName']."</a>";
				}
			$this->print_head = bab_translate('With/without introduction');
			$this->print_body = bab_translate('With/without body');
			}
		}

	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
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
		var $altbg = false;
		var $sContent = '';


		function temp($article)
			{
			global $babDB;
			$this->close			= bab_translate("Close");
			$this->attachmentxt		= bab_translate("Associated documents");
			$this->commentstxt		= bab_translate("Comments");
			$req					= "select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
			$this->res				= $babDB->db_query($req);
			$this->arr				= $babDB->db_fetch_array($this->res);
			$this->article_title	= bab_toHtml($this->arr['title']);
			$this->countf			= 0;
			$this->countcom			= 0;
			$this->sContent			= 'text/html; charset=' . bab_charset::getIso();

			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']) && bab_articleAccessByRestriction($this->arr['restriction']))
				{
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['head']);
				$editor->setFormat($this->arr['head_format']);
				$this->head = $editor->getHtml();

				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['body']);
				$editor->setFormat($this->arr['body_format']);
				$this->content = $editor->getHtml();

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
				$GLOBALS['babWebStat']->addArticle($article);
				}
			else
				{
				$this->content = "";
				$this->head = bab_translate("Access denied");
				}
			}

		function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->arr['id_topic']."&article=".$this->arr['id']."&idf=".$arr['id']);
				$this->docname = bab_toHtml($arr['name']);
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
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				if( $arr['id_author'] )
					{
					$this->authorname = bab_getUserName($arr['id_author']);
					}
				else
					{
					$this->authorname = $arr['name'];
					}
				$this->authorname = bab_toHtml($this->authorname);
				$this->commenttitle = bab_toHtml($arr['subject']);

				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$editor->setFormat($arr['message_format']);
				$this->commentbody = $editor->getHtml();

				$i++;
				return true;
				}
			else
				{
				if( $this->countcom > 0 )
					{
					$babDB->db_data_seek($this->rescom,0);
					}
				$i=0;
				return false;
				}
			}
		}

	$temp = new temp($article);
	echo bab_printTemplate($temp,"articles.html", "articleview");
	}



function articles_init($topics)
{
	global $babDB;
	
	
	$registry = bab_getRegistryInstance();
	$registry->changeDirectory('/bab/articles/');
	
	$arrret = array(
		'topic_title' 		=> $registry->getValue('topic_title', true),
		'topic_menu'		=> $registry->getValue('topic_menu', true)
	);

	$res = $babDB->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topics)."' and archive='Y'");
	list($arrret['nbarchive']) = $babDB->db_fetch_row($res);
	return $arrret;
}

function getDocumentArticle($idf, $topics)
{
	global $babDB;
	$access = false;
	$res = $babDB->db_query("select at.restriction, at.id_topic from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where aft.id='".$babDB->db_escape_string($idf)."'");
	if( $res && $babDB->db_num_rows($res))
	{
	$arr = $babDB->db_fetch_array($res);
	if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic'])  && bab_articleAccessByRestriction($arr['restriction']))
		{
		$access = true;
		}
	}

	if( !$access )
	{
		echo bab_translate("Access denied");
	}
	else
	{
		bab_getDocumentArticle($idf);
	}
}





function getImage()
{
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/gdiincl.php';

	$iIdArticle		= (int) bab_rp('iIdArticle', 0);
	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');

	// verify topic access rights


	$article = bab_getArticleArray($iIdArticle);
	if (!bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL,$article['id_topic']))
	{
		return;
	}


	$iIdDelegation = bab_getArticleDelegationId($iIdArticle);
	if(false === $iIdDelegation)
	{
		return;
	}

	$oEnvObj->setEnv($iIdDelegation);
	$sPath = $oEnvObj->getArticleImgPath($iIdArticle);

	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImageAuto($sPath . $sImage, $iWidth, $iHeight);
}



/**
 * Change subsription for current user and go back to previous page
 * @param int $id_topic
 * @return unknown_type
 */
function bab_topicSubscription($id_topic)
{

	switch(bab_TopicNotificationSubscription($id_topic, $GLOBALS['BAB_SESS_USERID']))
	{
		case 1:
			bab_TopicNotificationSubscription($id_topic, $GLOBALS['BAB_SESS_USERID'], false);
			break;

		case 0:
			bab_TopicNotificationSubscription($id_topic, $GLOBALS['BAB_SESS_USERID'], true);
			break;
	}

	$backurl = new bab_url;
	$backurl->tg='articles';
	$backurl->topics=$id_topic;

	if (!empty($_SERVER['HTTP_REFERER']))
	{
		$referer = new bab_url($_SERVER['HTTP_REFERER']);
		$self = bab_url::get_request_gp();

		if ($referer->checksum() !== $self->checksum())
		{
			$backurl = $referer;
		}
	}

	$backurl->location();
}





/* main */
$arrtop = array();

$idx = bab_rp('idx', 'Articles');
$topics = bab_rp('topics', false); /* Topic Id */

if ($topics === false) {
	$article = bab_gp('article', null);
	if (isset($article)) {
		$articleArray = bab_getArticleArray($article);
		if (isset($articleArray['id_topic']))
		{
			$topics = $articleArray['id_topic'];
		}
	}
}

/* Topic id don't exist, we search a topic id that the current user has rights to view */
if (!$topics && count(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)) > 0) {
	$rr = array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL));
	$topics = $rr[0]; /* Topic Id */
}

if ($topics === false || (!bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL,$topics) && !bab_requireAccess(BAB_TOPICSVIEW_GROUPS_TBL, $topics, null))) {
	/* The current user has not rights to view or modification articles in the topic, or there is no topic id */
	$babBody->msgerror = bab_translate("Access denied");
	bab_debug("The current user has not rights to view or modification articles in the topic, or there is no topic id");
	$idx = 'denied';
} else {
	$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'");
	$arrtop = $babDB->db_fetch_array($res); /* !!! $arrtop is a global variable : it contains information from the topic */
}

/* conf=mod is received when the form 'reason of modification of the article' is submitted (See the function modifyarticle()) */
if ('mod' == bab_pp('conf')) {
	if (isset($_POST['bupdate'])) { /* bupdate is the name of the button submitted in the form (bcancel the other button) */
		/* Create a new article draft and go to ?tg=artedit&idx=s1 */
		confirmModifyArticle(bab_pp('topics'), bab_pp('article'), bab_pp('comment'), bab_pp('bupdmod'));
	}
}

$supp_rfurl = isset($_REQUEST['rfurl']) ? '&rfurl='.urlencode($_REQUEST['rfurl']) : '';

switch($idx)
	{
	case "unload":
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl ='';}
		popupUnload($popupmessage, $refreshurl);
		exit;

	case "denied":
		break;

	case "getf":
		$idf = bab_gp('idf', 0);
		getDocumentArticle($idf, $topics);
		exit;
		break;

	case 'getImage':
		getImage();
		break;

	case "viewa":
		viewArticle(bab_gp('article'));
		exit;
		break;

	case "More":
		$arr = articles_init($topics);
		if ($arr['topic_title'])
		{
			$babBody->setTitle(bab_getCategoryTitle($topics));
		}
		$article = bab_gp('article');
		bab_siteMap::setPosition('bab', 'ArticleTopic_'.$topics);
		readMore($topics, $article);
		if ($arr['topic_menu'])
		{
			$babBody->addItemMenu("Articles",bab_translate("Articles"),$GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			$babBody->addItemMenu("More",bab_translate("Article"),$GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article);
			if( $arr['nbarchive'] )
				{
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
				}
		}
		break;

	case 'Submit':
		require_once dirname(__FILE__).'/utilit/arteditincl.php';
		$form = new bab_ArticleDraftEditor;
		$form->fromTopic(bab_rp('topics'));

		$backUrl = null;

		if (!empty($_SERVER['HTTP_REFERER'])) {
			$referer = new bab_url($_SERVER['HTTP_REFERER']);
			$self = bab_url::get_request_gp();

			if ($referer->checksum() !== $self->checksum()) {
				$backUrl = $referer;
			}
		}

		if (!isset($backUrl)) {
			$backUrl = bab_url::get_request('tg', 'topics');
		}

		$form->setBackUrl($backUrl);

		$form->display();
		break;


	case 'subscription':
		// change notification subscription status to topic
		$id_topic = (int) bab_rp('topic');
		if (!$id_topic || !$GLOBALS['BAB_SESS_LOGGED'])
		{
			$babBody->addError(bab_translate('You need to be logged in to subscribe or unsuscribe'));
			break;
		}
		bab_topicSubscription($id_topic);

		break;


	case "Modify":
		require_once dirname(__FILE__).'/utilit/arteditincl.php';
		$form = new bab_ArticleDraftEditor;
		$article = bab_rp('article');
		$form->fromArticle($article);

		$backUrl = null;

		if (!empty($_SERVER['HTTP_REFERER'])) {
			$referer = new bab_url($_SERVER['HTTP_REFERER']);
			$self = bab_url::get_request_gp();

			if ($referer->checksum() !== $self->checksum()) {
				$backUrl = $referer;
			}
		}

		if (!isset($backUrl)) {
			// If the referer url is not available we go back to the article's topic page.
			$backUrl = bab_url::request('tg');
			$backUrl = new bab_url($backUrl);
			$articles = $babDB->db_query("SELECT id_topic from ".BAB_ARTICLES_TBL." WHERE id=".$babDB->quote($article)." AND archive='N'");
			$art = $babDB->db_fetch_assoc($articles);

			$backUrl->topics = $art['id_topic'];
		}

		$form->setBackUrl($backUrl);

		$form->display();
		break;



	case "Print":
		$article = bab_rp('article');

		$articles = $babDB->db_query("SELECT id_topic from ".BAB_ARTICLES_TBL." WHERE id=".$babDB->quote($article)." AND archive='N'");
		$art = $babDB->db_fetch_assoc($articles);

		if ($art !== false && bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $art['id_topic']) && bab_articleAccessById($article)) {
			articlePrint($topics, $article);
		}
		exit;
		break;

	case "larch":
		$babBody->setTitle(bab_translate("List of old articles"));
		$pos = bab_rp('pos', 0);
		listArchiveArticles($topics, $pos);
		$babBody->addItemMenu("Articles",bab_translate("Articles"),$GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
		$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
		break;

	default:
	case "Articles":
		$arr = articles_init($topics);
		if ($arr['topic_title'])
		{
			$babBody->setTitle(bab_getCategoryTitle($topics));
		}
		bab_siteMap::setPosition('bab', 'ArticleTopic_'.$topics);
		listArticles($topics);
		
		if( $arr['nbarchive'] )
			{
			$babBody->addItemMenu("Articles",bab_translate("Articles"),$GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
