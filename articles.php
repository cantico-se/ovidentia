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
include "base.php";
include $babInstallPath."utilit/mailincl.php";
include $babInstallPath."utilit/topincl.php";

define("MAX_ARTICLES", 10);

class listArticles extends categoriesHierarchy
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

	function listArticles($topics)
		{
		global $babDB;

		$this->categoriesHierarchy($topics);
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

		$this->template = "default";
		if( !empty($this->topics) )
			{
			$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$this->topics."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				if( $arr['display_tmpl'] != '' )
					$this->template = $arr['display_tmpl'];
				}
			}
	
		}

}

function listSubmittedArticles($topics)
	{
	global $babBody, $babDB;

	class temp extends listArticles
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp($topics)
			{
			$this->listArticles($topics);

			$this->db = $GLOBALS['babDB'];
			$this->bbody = 0;

			if( $GLOBALS['BAB_SESS_USERID'] != '' )
				{
				$req = "select id, id_topic, id_author, date, title, head from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='N' and archive='N' and id_author='".$GLOBALS['BAB_SESS_USERID']."' order by date desc";
				$this->res = $this->db->db_query($req);
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				$this->count = 0;

			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->articleid = $this->arr['id'];
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					$this->articleauthor = $author;
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_strftime(bab_mktime($this->arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;

				$this->content = bab_replace($this->arr['head']);
				$this->title = stripslashes($this->arr['title']);
				$this->topictitle = bab_getCategoryTitle($this->arr['id_topic']);
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics);
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "head_".$temp->template));
	$arr = array($temp->count, $temp->nbarch);
	return $arr;
	}

function listArticles($topics, $approver)
	{
	global $babBody, $babDB;

	class temp extends listArticles
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $com;
		var $nbws;


		function temp($topics, $approver)
			{
			$this->listArticles($topics);
			$this->db = $GLOBALS['babDB'];

			$req = "select id, id_topic, id_author, date, title, head, LENGTH(body) as blen from ".BAB_ARTICLES_TBL."";
			$langFilterValue = $GLOBALS['babLangFilter']->getFilterAsInt();
			switch($langFilterValue)
				{
					case 2:
						$req .= " where id_topic='$topics' and confirmed='Y' and archive='N' and (lang='".$GLOBALS['babLanguage']."' or lang='*' or lang='')  order by date desc";
						break;
					case 1:
						$req .= " where id_topic='$topics' and confirmed='Y' and archive='N' and ((lang like '". substr($GLOBALS['babLanguage'], 0, 2) ."%') or lang='*' or lang='') order by date desc";
						break;
					case 0:
					default:
						$req .= " where id_topic='$topics' and confirmed='Y' and archive='N' order by date desc";
				}
				
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics) || bab_isUserCommentApprover($topics))
				$this->com = true;
			else
				$this->com = false;
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='N' and id_author='".$GLOBALS['BAB_SESS_USERID']."' and confirmed='N'");
			list($this->nbws) = $this->db->db_fetch_row($res);
			$this->approver = $approver;
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->articleid = $this->arr['id'];
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					$this->articleauthor = $author;
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_strftime(bab_mktime($this->arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->content = bab_replace($this->arr['head']);
				$this->title = stripslashes($this->arr['title']);
				$this->bbody = $this->arr['blen'];
				$this->topictitle = bab_getCategoryTitle($this->arr['id_topic']);
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
				$this->delurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id'];

				if( $this->com)
					{
					$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$total = $ar['total'];

					$req = "select count(".BAB_COMMENTS_TBL.".id) as total from ".BAB_COMMENTS_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_article='".$this->arr['id']."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_COMMENTS_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
					$res = $this->db->db_query($req);			
					$ar = $this->db->db_fetch_array($res);
					$totalw = $ar['total'];
					if( $total > 0 || ( $totalw > 0 && bab_isUserCommentApprover($this->topics) ))
						{
						$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
						if( $totalw > 0 )
							$this->commentstxt = bab_translate("Comments")."&nbsp;(".$total."-".$totalw.")";
						else
							$this->commentstxt = bab_translate("Comments")."&nbsp;(".$total.")";
						}
					else
						{
						$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=addComment&topics=".$this->topics."&article=".$this->arr['id'];
						$this->commentstxt = bab_translate("Add Comment");
						}

					}
				else
					{
					$this->commentsurl = "";
					$this->commentstxt = "";
					}

				$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id'];

				$i++;
				return true;
				}
			else
				return false;
			}

		}
	
	$temp = new temp($topics, $approver);
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "head_".$temp->template));
	$arr = array($temp->count, $temp->nbarch, $temp->nbws);
	return $arr;
	}

function listOldArticles($topics, $pos, $approver)
	{
	global $babBody, $babDB;

	class temp extends listArticles
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $newc;
		var $com;

		function temp($topics, $pos, $approver)
			{
			$this->listArticles($topics);

			$this->db = $GLOBALS['babDB'];
			$this->approver = $approver;

			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='Y' and archive='Y'");
			list($total)= $this->db->db_fetch_array($res);

			if( $total > MAX_ARTICLES)
				{
				$this->bnavigation = true;
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics;
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - MAX_ARTICLES;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + MAX_ARTICLES;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + MAX_ARTICLES < $total)
						{
						$bottom = $total - MAX_ARTICLES;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}
			else
				$this->bnavigation = false;


			$req = "select id, id_topic, id_author, date, title, head, LENGTH(body) as blen from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='Y' and archive='Y' order by date desc";
			if( $total > MAX_ARTICLES)
				{
				$req .= " limit ".$pos.",".MAX_ARTICLES;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics) || bab_isUserCommentApprover($topics))
				$this->com = true;
			else
				$this->com = false;
			}

		function getnext()
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->articleid = $this->arr['id'];
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					$this->articleauthor = $author;
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_strftime(bab_mktime($this->arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->content = bab_replace($this->arr['head']);
				$this->title = stripslashes($this->arr['title']);
				$this->bbody = $this->arr['blen'];
				$this->topictitle = bab_getCategoryTitle($this->arr['id_topic']);
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];

				$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
				$this->delurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id'];

				if( $this->com)
					{
					$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='Y'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$total = $ar['total'];
					$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='N'";
					$res = $this->db->db_query($req);
					$ar = $this->db->db_fetch_array($res);
					$totalw = $ar['total'];
					$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
					if( $totalw > 0 )
						$this->commentstxt = bab_translate("Comments")."&nbsp;(".$total."-".$totalw.")";
					else
						$this->commentstxt = bab_translate("Comments")."&nbsp;(".$total.")";
					}
				else
					{
					$this->commentsurl = "";
					$this->commentstxt = "";
					}

				$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		}
	
	$temp = new temp($topics, $pos, $approver);
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "head_".$temp->template));
	return $temp->count;
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
			$req = "select * from ".BAB_ARTICLES_TBL." where id='".$article."' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']))
				{
			$this->content = bab_replace($this->arr['body']);
			$this->head = bab_replace($this->arr['head']);
			}
			else
				$this->content = bab_translate("Access denied");

			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"articles.html", "articleview");
	}

function readMore($topics, $article)
	{
	global $babBody, $babDB;

	class temp extends categoriesHierarchy
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

		function temp($topics, $article)
			{
			$this->categoriesHierarchy($topics);
			$this->printtxt = bab_translate("Print Friendly");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$this->topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			$req = "select id,title from ".BAB_ARTICLES_TBL." where id_topic='".$this->topics."' and confirmed='Y' and archive='N' order by date desc";
			$this->resart = $this->db->db_query($req);
			$this->countart = $this->db->db_num_rows($this->resart);
			$this->topictxt = bab_translate("In the same topic");
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->title = bab_replace($this->arr['title']);
				if( !empty($this->arr['body']))
					$this->content = bab_replace($this->arr['body']);
				else
					$this->content = bab_replace($this->arr['head']);
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					$this->articleauthor = $author;
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_strftime(bab_mktime($this->arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextart()
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				$this->titlearticle = $arr['title']; 
				$this->urlview = $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$this->topics."&article=".$arr['id'];
				$this->urlreadmore = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$template = "default";
	if( !empty($topics) )
		{
		$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$topics."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['display_tmpl'] != '' )
				$template = $arr['display_tmpl'];
			}
		}

	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "body_".$template));
	return $temp->nbarch;
	}

function submitArticleByFile($topics)
	{
	global $babBody;
	
	class temp extends categoriesHierarchy
		{
		var $title;
		var $doctag;
		var $introtag;
		var $filename;
		var $add;
		var $maxupload;
		var $notearticle;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;
		var $countLangFiles;


		function temp($topics)
			{
			global $babMaxUpload;
			$this->categoriesHierarchy($topics);

			$this->title = bab_translate("Title");
			$this->doctag = bab_translate("Document Tag");
			$this->introtag = bab_translate("Introduction Tag");
			$this->filename = bab_translate("Filename");
			$this->add = bab_translate("Add article");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->topics = $topics;
			$this->maxupload = $babMaxUpload;
			$this->notearticle = bab_translate("Note: Articles are moderate and consequently your article will not be visible immediately");
			} // function temp

		function getnextlang()
		{
			static $i = 0;
			if($i < $this->countLangFiles)
			{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $GLOBALS['babLanguage'])
				{
					$this->langSelected = 'selected';
				}
				else
				{
					$this->langSelected = '';
			}
				$i++;
				return true;
		}
			return false;
		} // function getnextlang

		} // class temp

	$temp = new temp($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "articlecreatebyfile"));
	}

function deleteArticle($topics, $article, $new, $newc)
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

		function temp($topics, $article, $new, $newc)
			{
			$this->message = bab_translate("Are you sure you want to delete the article");
			$this->title = bab_getArticleTitle($article);
			$this->warning = bab_translate("WARNING: This operation will delete the article with all its comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics."&article=".$article."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno =$GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article."&new=".$new."&newc=".$newc;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($topics, $article, $new, $newc);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

//##: warn this fucntion is duplicated in waiting.php file 
function modifyArticle($topics, $article)
	{
	global $babBody;

	class temp extends categoriesHierarchy
		{
	
		var $head;
		var $title;
		var $titleval;
		var $headval;
		var $babBody;
		var $bodyval;
		var $modify;
		var $topics;
		var $article;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $msie;
		var $topicstxt;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;
		var $countLangFiles;
		var $topicid;


		function temp($topics, $article)
			{
			$this->categoriesHierarchy($topics);
			$this->article = $article;
			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Modify");
			$this->topicstxt = bab_translate("Topic");
			$this->notifymembers = bab_translate("Notify group members by mail");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			if($GLOBALS['babApplyLanguageFilter'] == 'loose')
			{
				$this->db = $GLOBALS['babDB'];
				$this->res = $this->db->db_query("select lang from ".BAB_TOPICS_TBL." where id='".$topics."'");
				$this->arr = $this->db->db_fetch_array($this->res);
				if($this->arr['lang'] != '*')
				{
					$this->langFiles = array();
					$this->langFiles[] = '*';
				}
			}
			$this->countLangFiles = count($this->langFiles);
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $this->count > 0)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->headval = htmlentities($this->arr['head']);
				$this->bodyval = htmlentities($this->arr['body']);
				$this->titleval = htmlentities($this->arr['title']);
				}
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;

			$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and ".BAB_TOPICS_TBL.".id_approver='".$GLOBALS['BAB_SESS_USERID']."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			$arr = $this->db->db_fetch_array($this->db->db_query("select notify from ".BAB_TOPICS_TBL." where id='".$topics."'"));
			if( $arr['notify'] == "N" )
				{
				$this->notifnsel = "selected";
				$this->notifysel = "";
				}
			else
				{
				$this->notifnsel = "selected";
				$this->notifysel = "";
				}
			}

		function getnexttopic()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->topicid = $arr['id'];
				$this->topictitle = $arr['category'];
				if( $arr['id'] == $this->topics )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				return false;
			} // function getnexttopic()
		
		function getnextlang()
		{
			static $i = 0;
			if($i < $this->countLangFiles)
			{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $this->arr['lang'])
				{
					$this->langSelected = 'selected';
				}
				else
				{
					$this->langSelected = '';
			}
				$i++;
				return true;
		}
			return false;
		} // function getnextlang

		} // class temp
	
	$temp = new temp($topics, $article);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "modifyarticle"));
	}

function submitArticle($title, $headtext, $bodytext, $topics)
	{
	global $babBody;

	class temp extends categoriesHierarchy
		{
	
		var $head;
		var $babBody;
		var $modify;
		var $title;
		var $msie;
		var $notearticle;
		var $langLabel;
		var $langValue;
		var $langSelected;
		var $langFiles;	
		var $countLangFiles;
		var $db;
		var $res;
		var $arr;

		function temp($title, $headtext, $bodytext, $topics)
			{
			$this->categoriesHierarchy($topics);

			if( empty($title))
				$this->titleval = "";
			else
				$this->titleval = $title;
			if( empty($headtext))
				$this->headval = "";
			else
				$this->headval = $headtext;
			if( empty($bodytext))
				$this->bodyval = "";
			else
				$this->bodyval = $bodytext;
			if( bab_isMagicQuotesGpcOn())
				{
				$this->titleval = stripslashes($this->titleval);
				$this->headval = stripslashes($this->headval);
				$this->bodyval = stripslashes($this->bodyval);
				}


			$this->head = bab_translate("Head");
			$this->body = bab_translate("Body");
			$this->title = bab_translate("Title");
			$this->modify = bab_translate("Add Article");
			$this->notearticle = bab_translate("Note: Articles are moderate and consequently your article will not be visible immediately");
			$this->images = bab_translate("Images");
			$this->urlimages = $GLOBALS['babUrlScript']."?tg=images";
			$this->files = bab_translate("Files");
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select lang, article_tmpl from ".BAB_TOPICS_TBL." where id='".$topics."'");
			$this->arr = $this->db->db_fetch_array($this->res);
			if($GLOBALS['babApplyLanguageFilter'] == 'loose')
			{
				if($this->arr['lang'] != '*')
				{
					$this->langFiles = array();
					$this->langFiles[] = '*';
				}
			}
			$this->countLangFiles = count($this->langFiles);
			$this->urlfiles = $GLOBALS['babUrlScript']."?tg=fileman&idx=brow";
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				$this->msie = 1;
			else
				$this->msie = 0;

			if( $this->arr['article_tmpl'] != '' && $this->headval == '' && $this->bodyval == '')
				{
				$file = "articlestemplate.html";
				$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babSkinPath']."templates/". $file;
					if( !file_exists( $filepath ) )
						{
						$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
						}
					}
				if( file_exists( $filepath ) )
					{
					$tp = new babTemplate();
					$this->headval = $tp->printTemplate($this, $filepath, "head_".$this->arr['article_tmpl']);
					$this->bodyval = $tp->printTemplate($this, $filepath, "body_".$this->arr['article_tmpl']);
					}
				}

			} // function temp
			
			function getnextlang()
			{
				static $i = 0;
				if($i < $this->countLangFiles)
				{
					$this->langValue = $this->langFiles[$i];
					if($this->langValue == $GLOBALS['babLanguage'])
					{
						$this->langSelected = 'selected';
				}
					else
					{
						$this->langSelected = '';
					}
					$i++;
					return true;
				}
				return false;
			} // function getnextlang

		} // class temp
	
	$temp = new temp($title, $headtext, $bodytext, $topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "createarticle"));
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

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( $this->count > 0 )
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->head = bab_replace($this->arr['head']);
				$this->content = bab_replace($this->arr['body']);
				$this->title = $this->arr['title'];
				$this->url = "<a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babSiteName']."</a>";
				}
			}
		}
	
	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
	}

function notifyApprovers($id, $topics)
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_TOPICS_TBL." where id='".$topics."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['idsaart'] == 0 )
			{
			$db->db_query("update ".BAB_ARTICLES_TBL." set confirmed='Y' where id='".$id."'");
			return true;
			}

		$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$arr['idsaart']."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$idfai = makeFlowInstance($arr['idsaart'], "art-".$id);
			$db->db_query("update ".BAB_ARTICLES_TBL." set idfai='".$idfai."' where id='".$id."'");
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			notifyArticleApprovers($id, $nfusers);
			}
		}
	}

function saveArticleByFile($filename, $title, $doctag, $introtag, $topics, $lang)
	{
	global $BAB_SESS_USERID, $babBody , $babAdminEmail;

	class dummy
		{
		}

	$dummy = new dummy();
	if( $filename == "none")
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a file name");
		return;
		}

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}

	$tp = new babTemplate();
	$headtext = $tp->printTemplate($dummy, $filename, $introtag);
	$bodytext = $tp->printTemplate($dummy, $filename, $doctag);

	$headtext = addslashes($headtext);
	$bodytext = addslashes($bodytext);

	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_ARTICLES_TBL." (id_topic, id_author, confirmed, date, title, body, head, lang) values ";
	$req .= "('" .$topics. "', '" . $BAB_SESS_USERID. "', 'N', now(), '" . $title. "', '" . $bodytext. "', '" . $headtext. "', '" .$lang. "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();
	notifyApprovers($id, $topics);
	}


function saveArticle($title, $headtext, $bodytext, $topics, $lang='')
	{
	global $BAB_SESS_USERID, $babBody ;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return false;
		}

	if( empty($headtext))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a head for your article");
		return false;
		}

	if($lang == '') $lang = $GLOBALS['babLanguage'];
	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_ARTICLES_TBL." (id_topic, id_author, confirmed, date, lang) values ";
	$req .= "('" .$topics. "', '" . $BAB_SESS_USERID. "', 'N', now(), '" .$lang. "')";
	$res = $db->db_query($req);
	$id = $db->db_insert_id();

	if( !strcasecmp($bodytext, "<P>&nbsp;</P>"))
		$bodytext = "";

	$headtext = stripslashes($headtext);
	$bodytext = stripslashes($bodytext);
	$title = stripslashes($title);

	$ar = array();
	$headtext = imagesReplace($headtext, $id."_art_", $ar);
	$bodytext = imagesReplace($bodytext, $id."_art_", $ar);

	$req = "update ".BAB_ARTICLES_TBL." set head='".addslashes(bab_stripDomainName($headtext))."', body='".addslashes(bab_stripDomainName($bodytext))."', title='".addslashes($title)."' where id='".$id."'";
	$res = $db->db_query($req);

	notifyApprovers($id, $topics);
	return true;
	}

//@@: warn this function is duplicated in waiting.php file 
function updateArticle($topics, $title, $article, $headtext, $bodytext, $topicid, $bnotif, $lang)
	{
	global $babBody;

	if( empty($title))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a title");
		return;
		}

	if( !strcasecmp($bodytext, "<P>&nbsp;</P>"))
		$bodytext = "";

	if( bab_isMagicQuotesGpcOn())
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		$title = stripslashes($title);
		}

	$ar = array();
	$headtext = imagesReplace($headtext, $article."_art_", $ar);
	$bodytext = imagesReplace($bodytext, $article."_art_", $ar);

	$db = $GLOBALS['babDB'];
	if($GLOBALS['babApplyLanguageFilter'] == 'loose')
	{
		$req = "select lang from ".BAB_TOPICS_TBL." where id='".$topicid."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		if($arr['lang'] != '*') $lang = '*';
	}
	$req = "update ".BAB_ARTICLES_TBL." set title='".addslashes($title)."', head='".addslashes(bab_stripDomainName($headtext))."', body='".addslashes(bab_stripDomainName($bodytext))."', date=now(), id_topic='".$topicid."', lang='" .$lang. "' where id='".$article."'";
	$res = $db->db_query($req);

	if( $bnotif == "Y" )
		{
		$arr = $db->db_fetch_array($db->db_query("select category from ".BAB_TOPICS_TBL." where id='".$topics."'"));
		notifyArticleGroupMembers($arr['category'], $topics, $title, bab_getArticleAuthor($article), 'mod');
	}
	} // function updateArticle


/* main */
if(!isset($idx))
	{
	$idx = "Articles";
	}
if( !isset($pos))
	$pos = 0;

if( isset($topics ) && $BAB_SESS_USERID != "")
	$approver = bab_isUserTopicManager($topics);
else
	$approver = false;

if( isset($addarticle) && ($approver || bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)))
	{
	saveArticleByFile($filename, $title, $doctag, $introtag, $topics, $lang);
	$idx = "Articles";
	}

if( isset($addart) && $addart == "add" && ($approver || bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)))
	{
	if( saveArticle($title, $headtext, $bodytext, $topics, $lang))
		$idx = "Articles";
	else
		$idx = "Submit";
	}

if( isset($action) && $action == "Yes" && $BAB_SESS_USERID != "" && $approver)
	{
	include_once $babInstallPath."utilit/delincl.php";
	bab_confirmDeleteArticle($article);
	}

if( isset($modify) && $approver)
	{
	updateArticle($topics, $title, $article, $headtext, $bodytext, $topicid, $bnotif, $lang);
	$idx = "Articles";
	}

$uaapp = bab_isUserArticleApprover($topics);
$ucapp = bab_isUserCommentApprover($topics);
if( $approver || $uaapp || $ucapp )
	$access = true;
else
	$access = false;
if( $uaapp )
	{
	$db = $GLOBALS['babDB'];
	$req = "select ".BAB_ARTICLES_TBL.".id from ".BAB_ARTICLES_TBL." join ".BAB_FAR_INSTANCES_TBL." where id_topic='".$topics."' and confirmed='N' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_ARTICLES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$BAB_SESS_USERID."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y'";
	$res = $db->db_query($req);
	$new = $db->db_num_rows($res);
	}
else
	$new = 0;

$babLevelTwo = bab_getCategoryTitle($topics);
$arr = $babDB->db_fetch_array($babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id='".$topics."'"));
$babLevelOne = bab_getTopicCategoryTitle($arr['id_cat']);

switch($idx)
	{
	case "viewa":
		viewArticle($article);
		exit;

	case "Submit":
		$babBody->title = bab_translate("Submit an article")." [ ". $babLevelTwo ." ]";
		if( in_array($topics, $babBody->topview))
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
		if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics) || $approver)
			{
			submitArticle($title, $headtext, $bodytext, $topics);
			$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
			$babBody->addItemMenu("subfile", bab_translate("File"), $GLOBALS['babUrlScript']."?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "subfile":
		$babBody->title = bab_translate("Submit an article")." [ ". $babLevelTwo ." ]";
		if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics) || $approver)
			{
			submitArticleByFile($topics);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
			$babBody->addItemMenu("subfile", bab_translate("File"), $GLOBALS['babUrlScript']."?tg=articles&idx=subfile&topics=".$topics);
			}
		break;

	case "Comments":
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=comments&topics=".$topics."&article=".$article);
		return;

	case "More":
		$babBody->title = $babLevelTwo;
		if( in_array($topics, $babBody->topview) || $access)
			{
			$barch = readMore($topics, $article);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics) || $approver)
				{
				if( $approver)
					{
					$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$topics."&article=".$article);
					$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article);
					}
				}
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $topics) || $access)
				{
				$babBody->addItemMenu("Comments", bab_translate("Comments"), $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$topics."&article=".$article);
				}
			if( $barch > 0 )
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			}
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete article");
		if( $approver)
			{
			viewCategoriesHierarchy($topics);
			deleteArticle($topics, $article, $new, $newc);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$topics."&article=".$article);
			}
		break;

	case "Modify":
		$babBody->title = bab_getArticleTitle($article);
		if( $approver)
			{
			modifyArticle($topics, $article);
			$babBody->addItemMenu("Cancel", bab_translate("Cancel"), $GLOBALS['babUrlScript']."?tg=articles&topics=".$topics);
			$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article);
			}
		break;

	case "Print":
		if( in_array($topics, $babBody->topview) || $access)
			articlePrint($topics, $article);
		exit();
		break;

	case "larch":
		$babBody->title = bab_translate("List of old articles");
		if( in_array($topics, $babBody->topview)|| $access)
			{
			$nbarch = listOldArticles($topics, $pos, $approver);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)|| $access)
				{
				$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
				}
			$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			if( $nbarch < 1)
				$babBody->title = $babLevelTwo.": ". bab_translate("Today, there are no article");
			else
				$babBody->title = $babLevelTwo.": ".bab_translate("List of old articles");
			}
		break;

	case "Submited":
		$babBody->title = bab_translate("List of submitted articles");
		if( in_array($topics, $babBody->topview)|| $access)
			{
			$arr = listSubmittedArticles($topics);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)|| $approver)
				{
				$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
				$babBody->addItemMenu("Submited", bab_translate("Submitted"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submited&topics=".$topics);
				}
			if( isset($new) && $new > 0 && $uaapp)
				$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics);
			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			if( $arr[1] > 0 )
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			if( $arr[0] < 1)
				$babBody->title = $babLevelTwo.": ".bab_translate("There are no submitted article");
			else
				$babBody->title = $babLevelTwo;
			}
		break;

	default:
	case "Articles":
		$babBody->title = bab_translate("List of articles");
		if( in_array($topics, $babBody->topview)|| $access)
			{
			$arr = listArticles($topics, $approver);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topics)|| $approver)
				{
				$babBody->addItemMenu("Submit", bab_translate("Submit"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$topics);
				if( $arr[2] > 0 && $GLOBALS['BAB_SESS_USERID'] != '')
					$babBody->addItemMenu("Submited", bab_translate("Submitted"), $GLOBALS['babUrlScript']."?tg=articles&idx=Submited&topics=".$topics);
				}
			if( isset($new) && $new > 0 && $uaapp)
				$babBody->addItemMenu("Waiting", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=waiting&idx=Waiting&topics=".$topics);

			$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
			if( $arr[1] > 0 )
				$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			if( $arr[0] < 1)
				$babBody->title = $babLevelTwo.": ".bab_translate("Today, there are no article");
			else
				$babBody->title = $babLevelTwo;
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>