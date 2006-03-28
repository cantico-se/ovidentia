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
include_once $babInstallPath."utilit/uiutil.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/artincl.php";

define("BAB_ART_MAXLOGS"	, 25);


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
	var $attachmentxt;

	function listArticles($topics)
		{
		global $babDB, $arrtop;

		$this->categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
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
					if( substr($arr[$i], 5) == $this->template )
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
		}
}

function listArticles($topics)
	{
	global $babBody, $babDB, $arrtop;

	class temp extends listArticles
		{
	
		function temp($topics)
			{
			$this->listArticles($topics);
			$this->db = $GLOBALS['babDB'];
			$this->bmanager = bab_isUserTopicManager($this->topics);

			$req = "select at.id, at.id_topic, at.id_author, at.date, at.date_modification, at.title, at.head, LENGTH(at.body) as blen, at.restriction from ".BAB_ARTICLES_TBL." at where at.id_topic='".$topics."' and at.archive='N' and (date_publication='0000-00-00 00:00:00' or date_publication <= now())";
			$langFilterValue = $GLOBALS['babLangFilter']->getFilterAsInt();
			switch($langFilterValue)
				{
					case 2:
						$req .= " and (at.lang='".$GLOBALS['babLanguage']."' or at.lang='*' or lang='')  order by at.ordering asc";
						break;
					case 1:
						$req .= " and ((at.lang like '". substr($GLOBALS['babLanguage'], 0, 2) ."%') or at.lang='*' or lang='') order by at.ordering asc";
						break;
					case 0:
					default:
						$req .= " order by at.ordering asc";
				}
				
			$req .= ", at.date_modification desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics))
				{
				$this->bcomment = true;
				}
			else
				{
				$this->bcomment = false;
				}
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $this->topics))
				{
				$this->submittxt = bab_translate("Submit");
				$this->bsubmiturl = $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->topics;
				$this->bsubmit = true;
				}
			else
				{
				$this->bsubmit = false;
				}
			}

		function getnext(&$skip)
			{
			global $arrtop;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr['restriction'] != '' && !bab_articleAccessByRestriction($this->arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->articleid = $this->arr['id'];
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					{
					$this->articleauthor = $author;
					}
				else
					{
					$this->articleauthor = bab_translate("Anonymous");
					}

				if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $this->topics) || ( $arrtop['allow_update'] != '0' && $this->arr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $arrtop['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $this->topics)))
					{
					$this->bmodify = true;
					$res =  $this->db->db_query("select id, id_author from ".BAB_ART_DRAFTS_TBL." where id_article='".$this->arr['id']."'");
					if( $res && $this->db->db_num_rows($res) > 0 )
						{
						$rr = $this->db->db_fetch_array($res);
						$this->bmodifyurl = false;
						$this->modifybytxt = bab_translate("In modification by");
						$this->modifyauthor	= bab_getUserName($rr['id_author']);
						$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=log&topics=".$this->topics."&article=".$this->arr['id'];
						if( $rr['id_author'] == $GLOBALS['BAB_SESS_USERID'] )
							{
							$this->modifydrafttxt = bab_translate("Edit draft");
							$this->modifydrafturl = $GLOBALS['babUrlScript']."?tg=artedit&idx=s1&idart=".$rr['id']."&rfurl=".urlencode($GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$this->topics);
							}
						}
					else
						{
						$this->bmodifyurl = true;
						$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
						}
					}
				else
					{
					$this->modifyurl = '';
					$this->bmodify = false;
					}

				if( $this->bmanager )
					{
					$this->delurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id'];
					}

				/* template variables */
				$this->babtpl_authorid = $this->arr['id_author'];

				$this->articledate = bab_strftime(bab_mktime($this->arr['date_modification']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->content = bab_replace($this->arr['head']);
				$this->title = stripslashes($this->arr['title']);
				$this->topictitle = bab_getCategoryTitle($this->arr['id_topic']);
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$this->bbody = $this->arr['blen'];
				if( $this->bbody > 0 )
					{
					$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id'];
					}
				else
					{
					$GLOBALS['babWebStat']->addArticle($this->arr['id']);
					}

				list($totalc) = $this->db->db_fetch_row($this->db->db_query("select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='Y'"));

				if( $totalc > 0 || $this->bcomment)
					{
					$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
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
					$this->commentsurl = "";
					$this->commentstxt = "";
					}

				$this->resf = $this->db->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$this->arr['id']."' order by name asc");
				$this->countf = $this->db->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextdoc()
			{
			global $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $this->db->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->topics."&idf=".$arr['id'];
				$this->docname = $arr['name'];
				$this->docdesc = $arr['description'];
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
	$babBody->babecho(	bab_printTemplate($temp,"topicsdisplay.html", "head_".$temp->template));
	}


function listArchiveArticles($topics, $pos)
	{
	global $babBody, $babDB;

	class listArchiveArticlesCls extends listArticles
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $newc;
		var $com;

		function listArchiveArticlesCls($topics, $pos)
			{
			global $arrtop;

			$this->listArticles($topics);
			$this->db = $GLOBALS['babDB'];
			$maxarticles = $arrtop['max_articles'];

			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."'and archive='Y'");
			list($total)= $this->db->db_fetch_array($res);

			if( $total > $maxarticles)
				{
				$this->bnavigation = true;
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics;
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - $maxarticles;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + $maxarticles;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + $maxarticles < $total)
						{
						$bottom = $total - $maxarticles;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}
			else
				$this->bnavigation = false;


			$req = "select id, id_topic, id_author, date, title, head, LENGTH(body) as blen, restriction from ".BAB_ARTICLES_TBL." where id_topic='$topics' and archive='Y' order by date desc";
			if( $total > $maxarticles)
				{
				$req .= " limit ".$pos.",".$maxarticles;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext(&$skip)
			{
			global $new; 
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr['restriction'] != '' && !bab_articleAccessByRestriction($this->arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
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
				if( $this->bbody == 0 )
					{
					$GLOBALS['babWebStat']->addArticle($this->arr['id']);
					}
				$this->topictitle = bab_getCategoryTitle($this->arr['id_topic']);
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];

				$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
				$this->delurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Delete&topics=".$this->topics."&article=".$this->arr['id'];

				$req = "select count(id) as total from ".BAB_COMMENTS_TBL." where id_article='".$this->arr['id']."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$ar = $this->db->db_fetch_array($res);
				$total = $ar['total'];
				if( $total > 0)
					{
					$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
					$this->commentstxt = bab_translate("Comments")."&nbsp;(".$total.")";
					}
				else
					{
					$this->commentsurl = "";
					$this->commentstxt = "";
					}

				$this->moreurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$this->arr['id'];

				$this->resf = $this->db->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$this->arr['id']."' order by name asc");
				$this->countf = $this->db->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextdoc()
			{
			global $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $this->db->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->topics."&idf=".$arr['id'];
				$this->docname = $arr['name'];
				$this->docdesc = $arr['description'];
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
		var $rescom;
		var $countcom;
		var $altbg = false;

		function temp($topics, $article)
			{
			global $arrtop;
			$this->categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
			$this->printtxt = bab_translate("Print Friendly");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='".$article."'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->count = $this->db->db_num_rows($this->res);
			$res = $this->db->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$this->topics."' and archive='Y'");
			list($this->nbarch) = $this->db->db_fetch_row($res);
			$req = "select id,title, restriction from ".BAB_ARTICLES_TBL." where id_topic='".$this->topics."' and archive='N' order by date desc";
			$this->resart = $this->db->db_query($req);
			$this->countart = $this->db->db_num_rows($this->resart);
			$this->topictxt = bab_translate("In the same topic");
			$this->commenttxt = bab_translate("Comments");
			$this->article = $article;
			$this->artcount = 0;

			$this->rescom = $this->db->db_query("select * from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='Y' order by date desc");
			$this->countcom = $this->db->db_num_rows($this->rescom);

			if( $this->count > 0 && $this->arr['archive'] == 'N' && (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $this->topics) || ( $arrtop['allow_update'] != '0' && $this->arr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ($arrtop['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $this->topics))))
				{
				$this->bmodify = true;
				$res =  $this->db->db_query("select id_author from ".BAB_ART_DRAFTS_TBL." where id_article='".$this->arr['id']."'");
				if( $res && $this->db->db_num_rows($res) > 0 )
					{
					$rr = $this->db->db_fetch_array($res);
					$this->bmodifyurl = false;
					$this->modifybytxt = bab_translate("In modification by");
					$this->modifyauthor	= bab_getUserName($rr['id_author']);
					$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=log&topics=".$this->topics."&article=".$this->arr['id'];
					}
				else
					{
					$this->modifytxt = bab_translate("Modify");
					$this->bmodifyurl = true;
					$this->modifyurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$this->topics."&article=".$this->arr['id'];
					}
				}
			else
				{
				$this->modifyurl = '';
				$this->bmodify = false;
				}

			if( $this->arr['archive'] == 'N' && bab_isAccessValid(BAB_TOPICSCOM_GROUPS_TBL, $this->topics))
				{
				$this->commentsurl = $GLOBALS['babUrlScript']."?tg=comments&idx=List&topics=".$this->topics."&article=".$this->arr['id'];
				$this->commentstxt = bab_translate("Add Comment");
				}
			else
				{
				$this->commentsurl = "";
				$this->commentstxt = "";
				}

			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $this->topics))
				{
				$this->submittxt = bab_translate("Submit");
				$this->bsubmiturl = $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->topics;
				$this->bsubmit = true;
				}
			else
				{
				$this->bsubmit = false;
				}

			$this->resf = $this->db->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$article."' order by name asc");
			$this->countf = $this->db->db_num_rows($this->resf);

			if( $this->countf > 0 )
				{
				$this->attachmentxt = bab_translate("Associated documents");
				$this->battachments = true;
				}
			else
				{
				$this->battachments = false;
				}
			}

		function getnext(&$skip)
			{
			static $i = 0;
			if( $i < $this->count)
				{
				if( $this->arr['restriction'] != '' && !bab_articleAccessByRestriction($this->arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
				$GLOBALS['babWebStat']->addArticle($this->arr['id']);
				$this->title = bab_replace($this->arr['title']);
				if( !empty($this->arr['body']))
					{
					$this->head = bab_replace($this->arr['head']);
					$this->content = bab_replace($this->arr['body']);
					}
				else
					{
					$this->content = bab_replace($this->arr['head']);
					}
				if( $this->arr['id_author'] != 0 && (($author = bab_getUserName($this->arr['id_author'])) != ""))
					{
					$this->articleauthor = $author;
					}
				else
					{
					$this->articleauthor = bab_translate("Anonymous");
					}
				$this->articledate = bab_strftime(bab_mktime($this->arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->printurl = $GLOBALS['babUrlScript']."?tg=articles&idx=Print&topics=".$this->topics."&article=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextart(&$skip)
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				if( ($arr['restriction'] != '' && !bab_articleAccessByRestriction($arr['restriction'])) || $this->article == $arr['id'])
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->artcount++;
				$this->titlearticle = $arr['title']; 
				$this->urlview = $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$this->topics."&article=".$arr['id'];
				$this->urlreadmore = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->topics."&article=".$arr['id'];
				$i++;
				return true;
				}
			else
				{
				if( $this->countart > 0 )
					{
					$this->db->db_data_seek($this->resart,0);
					}
				$i=0;
				return false;
				}
			}
		function getnextcom()
			{
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_strftime(bab_mktime($arr['date']));
				$this->authorname = $arr['name'];
				$this->commenttitle = $arr['subject'];
				$this->commentbody = bab_replace($arr['message']);
				$i++;
				return true;
				}
			else
				{
				if( $this->countcom > 0 )
					{
					$this->db->db_data_seek($this->rescom,0);
					}
				$i=0;
				return false;
				}
			}

		function getnextdoc()
			{
			global $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $this->db->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->topics."&idf=".$arr['id'];
				$this->docname = $arr['name'];
				$this->docdesc = $arr['description'];
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
				if( substr($arr[$i], 5) == $template )
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

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$article."'");
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( $this->count > 0)
				{
				$GLOBALS['babWebStat']->addArticle($article);
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->head = bab_replace($this->arr['head']);
				$this->content = bab_replace($this->arr['body']);
				$this->title = $this->arr['title'];
				$this->url = "<a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babSiteName']."</a>";
				}
			$this->print_head = bab_translate('With/without introduction');
			$this->print_body = bab_translate('With/without body');
			}
		}
	
	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
	}


function modifyArticle($topics, $article)
{
	global $babBodyPopup;
	class temp
		{
		var $arttxt;

		function temp($topics, $article)
			{
			global $babBodyPopup, $babBody, $babDB, $arrtop, $rfurl;

			$access = false;
			if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $topics) )
				{
				$access = true;
				}
			else
				{
				if( $arrtop['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $topics))
					{
					$access = true;
					}
				else
					{
					list($author) = $babDB->db_fetch_row($babDB->db_query("select id_author from ".BAB_ARTICLES_TBL." where id='".$article."'"));
					if( $arrtop['allow_update'] != '0' && $author == $GLOBALS['BAB_SESS_USERID'] )
						{
						$access = true;
						}
					}
				}

			if(!isset($rfurl))
				{
				$rfurl = $GLOBALS['babUrlScript']."?tg=articles&idx=articles&topics=".$topics;
				}
			else
				{
				$this->rfurl = $rfurl;
				}
				
			if( $access )
				{
				list($this->blog) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_ART_LOG_TBL." where id_article='".$article."'"));
				$res = $babDB->db_query("select at.id, at.title, at.id_topic, adt.id_author as id_modifier from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id='".$article."'");
				$this->bmodiy = false;

				if( $access && $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->article = $article;
					$this->topics = $topics;
					$this->arttxt = bab_translate("Article");
					$this->pathtxt = bab_translate("Path");
					$this->arttitle = $arr['title'];
					$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
					if( !isset($arr['id_modifier']) || empty($arr['id_modifier']) )
						{
						$this->commenttxt = bab_translate("Reason of the modification");
						$this->canceltxt = bab_translate("Cancel");
						$this->updatetxt = bab_translate("Next");
						$this->updatemodtxt = bab_translate("Don't update article modification date");
						$this->bmodify = true;
						}
					else
						{
						$babBodyPopup->msgerror = bab_translate("Article in modification by ").bab_getUsername($arr['id_modifier']);
						}
					}
				else
					{
					$babBodyPopup->msgerror = bab_translate("Access denied");
					}
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($topics, $article);
	$babBodyPopup->babecho(bab_printTemplate($temp, "articles.html", "modifyarticle"));
	return $temp->blog;
}


function viewArticleLog($topics, $article, $pos)
{
	global $babBodyPopup;

	class temp
		{
		var $topname;
		var $topurl;
		var $prevname;
		var $prevurl;
		var $nextname;
		var $nexturl;
		var $bottomname;
		var $bottomurl;

		function temp($topics, $article, $pos)
			{
			global $babBodyPopup, $babDB, $rfurl;

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
			$this->commenttxt = bab_translate("Comment");

			$res = $babDB->db_query("select id, id_author  from ".BAB_ART_DRAFTS_TBL." where id_article='".$article."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->bmodify = false;
				if( $arr['id_author'] ==  $GLOBALS['BAB_SESS_USERID'] )
					{
					$this->editdrafttxt = bab_translate("Edit");
					$rfurl = !empty($rfurl) ? urlencode($rfurl) : urlencode($GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
					$this->editdrafturl = $GLOBALS['babUrlScript']."?tg=artedit&idx=s1&idart=".$arr['id']."&rfurl=".$rfurl;
					}
				else
					{
					$this->editdrafttxt = false;
					}
				}
			else
				{
				$this->editdrafttxt = false;
				$this->bmodify = true;
				}


			$res = $babDB->db_query("select count(*) as total from ".BAB_ART_LOG_TBL." where id_article='".$article."' order by date_log desc");
			$row = $babDB->db_fetch_array($res);
			$total = $row["total"];

			$url = $GLOBALS['babUrlScript']."?tg=articles&idx=log&topics=".$topics."&article=".$article;
			if( $total > BAB_ART_MAXLOGS)
				{
				if( $pos > 0)
					{
					$this->topurl = $url."&pos=0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - BAB_ART_MAXLOGS;
				if( $next >= 0)
					{
					$this->prevurl = $url."&pos=".$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + BAB_ART_MAXLOGS;
				if( $next < $total)
					{
					$this->nexturl = $url."&pos=".$next;
					$this->nextname = "&gt;";
					if( $next + BAB_ART_MAXLOGS < $total)
						{
						$bottom = $total - BAB_ART_MAXLOGS;
						}
					else
						{
						$bottom = $next;
						}
					$this->bottomurl = $url."&pos=".$bottom;
					$this->bottomname = "&gt;&gt;";
					}
				}

			$req = "select * from ".BAB_ART_LOG_TBL." where id_article='".$article."' order by date_log desc";
			if( $total > BAB_ART_MAXLOGS)
				{
				$req .= " limit ".$pos.",".BAB_ART_MAXLOGS;
				}

			$this->artpath = viewCategoriesHierarchy_txt($topics);
			list($this->arttitle) = $babDB->db_fetch_row($babDB->db_query("select title from ".BAB_ARTICLES_TBL." where id='".$article."'"));
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnextlog()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
				$arr = $babDB->db_fetch_array($this->res);
				$this->datelock = bab_strftime(bab_mktime($arr['date_log']), true);
				$this->author = bab_getUserName($arr['id_author']);
				switch($arr['action_log'])
					{
					case 'lock': $this->action = bab_translate("Lock"); break;
					case 'unlock': $this->action = bab_translate("Unlock"); break;
					case 'commit': $this->action = bab_translate("Commit"); break;
					case 'refused': $this->action = bab_translate("Refused"); break;
					case 'accepted': $this->action = bab_translate("Accepted"); break;
					default: $this->action = ""; break;
					}
				$this->comment = str_replace("\n", "<br>", $arr['art_log']);
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		}

	$temp = new temp($topics, $article, $pos);
	$babBodyPopup->babecho(bab_printTemplate($temp, "articles.html", "articlehistoric"));
	return $temp->bmodify;
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


		function temp($article)
			{
			$this->close = bab_translate("Close");
			$this->attachmentxt = bab_translate("Associated documents");
			$this->commentstxt = bab_translate("Comments");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->countf = 0;
			$this->countcom = 0;
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']) && bab_articleAccessByRestriction($this->arr['restriction']))
				{
				$this->content = bab_replace($this->arr['body']);
				$this->head = bab_replace($this->arr['head']);

				$this->resf = $this->db->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$article."' order by name asc");
				$this->countf = $this->db->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}

				$this->rescom = $this->db->db_query("select * from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='Y' order by date desc");
				$this->countcom = $this->db->db_num_rows($this->rescom);
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
			global $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $this->db->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->arr['id_topic']."&article=".$this->arr['id']."&idf=".$arr['id'];
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
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_strftime(bab_mktime($arr['date']));
				$this->authorname = $arr['name'];
				$this->commenttitle = $arr['subject'];
				$this->commentbody = bab_replace($arr['message']);
				$i++;
				return true;
				}
			else
				{
				if( $this->countcom > 0 )
					{
					$this->db->db_data_seek($this->rescom,0);
					}
				$i=0;
				return false;
				}
			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"articles.html", "articleview");
	}


function confirmModifyArticle($topics, $article, $comment, $bupdmod)
{
	global $babBody, $babDB, $arrtop, $rfurl;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id_article='".$article."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		echo bab_translate("This article is in modification");
	}
	else
	{
		list($author) = $babDB->db_fetch_row($babDB->db_query("select id_author from ".BAB_ARTICLES_TBL." where id='".$article."'"));
		if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $topics) || ( $arrtop['allow_update'] != '0' && $author == $GLOBALS['BAB_SESS_USERID']) || ( $arrtop['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $topics)))
		{
			$idart = bab_newArticleDraft($topics, $article);
			if( $idart != 0 )
			{
				$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set update_datemodif='".$bupdmod."' where id='".$idart."'");		

				if( bab_isMagicQuotesGpcOn())
					{
					$comment = stripslashes($comment);
					}
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log, art_log) values ('".$article."', '".$GLOBALS['BAB_SESS_USERID']."', now(), 'lock', '".addslashes($comment)."')");		
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=s1&idart=".$idart."&rfurl=".urlencode($rfurl));
				exit;
			}
			else
			{
			echo bab_translate("Draft creation failed");
			exit;
			}
		}
		else
		{
			echo bab_translate("Access denied");
			exit;
		}
	}
}

function submitArticle($topics)
{
	global $babBody, $babDB;
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=s1&topicid=".$topics."&rfurl=".urlencode($GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics));
	exit;
}

function articles_init($topics)
{
	global $babDB;
	$arrret = array();

	$res = $babDB->db_query("select count(*) from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and archive='Y'");
	list($arrret['nbarchive']) = $babDB->db_fetch_row($res);
	return $arrret;
}

function getDocumentArticle($idf, $topics)
{
	global $babDB;
	$access = false;
	$res = $babDB->db_query("select at.restriction, at.id_topic from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where aft.id='".$idf."'");
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


/* main */
$arrtop = array();

if(!isset($idx))
	{
	$idx = "Articles";
	}

if( count($babBody->topview) == 0 || (!isset($babBody->topview[$topics]) && in_array($idx, array('Articles','getf','More','Print','larch')) ))
{
	$babBody->msgerror = bab_translate("Access denied");
	$idx = 'denied';
}
elseif (count($babBody->topview) > 0)
{
$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$topics."'");
$arrtop = $babDB->db_fetch_array($res);
}



if( isset($conf) && $conf == "mod" )
{
	if( isset($bupdate))
		{
		if( !isset($bupdmod)) { $bupdmod ='Y';}
		confirmModifyArticle($topics, $article, $comment, $bupdmod);
		}
}

$supp_rfurl = isset($rfurl) ? '&rfurl='.urlencode($rfurl) : '';

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
		getDocumentArticle($idf, $topics);
		exit;
		break;

	case "viewa":
		viewArticle($article);
		exit;
		break;

	case "More":
		$babBody->title = bab_getCategoryTitle($topics);
		readMore($topics, $article);
		$arr = articles_init($topics);
		$babBody->addItemMenu("Articles",bab_translate("Articles"),$GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
		$babBody->addItemMenu("More",bab_translate("Article"),$GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$topics."&article=".$article);
		if( $arr['nbarchive'] )
			{
			$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			}
		break;

	case "log":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Article historic");
		if( !isset($pos)) {	$pos = 0; }
		$bmodify = viewArticleLog($topics, $article, $pos);
		if( $bmodify )
		{
		$babBodyPopup->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article.$supp_rfurl);
		}
		$babBodyPopup->addItemMenu("log", bab_translate("Historic"), $GLOBALS['babUrlScript']."?tg=articles&idx=log&topics=".$topics."&article=".$article.$supp_rfurl);
		$babBodyPopup->setCurrentItemMenu($idx);
		printBabBodyPopup();
		exit;
		break;

	case "Modify":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Reason of the modification");
		
		$blog = modifyArticle($topics, $article);
		
		$babBodyPopup->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$topics."&article=".$article.$supp_rfurl);
		if( $blog )
		{
		$babBodyPopup->addItemMenu("log", bab_translate("Historic"), $GLOBALS['babUrlScript']."?tg=articles&idx=log&topics=".$topics."&article=".$article.$supp_rfurl);
		}
		$babBodyPopup->setCurrentItemMenu($idx);
		printBabBodyPopup();
		exit;
		break;

	case "Submit":
		submitArticle($topics);
		exit;
		break;

	case "Print":
		if( bab_articleAccessById($article))
			{
			articlePrint($topics, $article);
			}
		exit;
		break;

	case "larch":
		$babBody->title = bab_translate("List of old articles");
		if( !isset($pos)) {	$pos = 0; }
		listArchiveArticles($topics, $pos);
		$babBody->addItemMenu("Articles",bab_translate("Articles"),$GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
		$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
		break;

	default:
	case "Articles":
		$babBody->title = bab_getCategoryTitle($topics);
		listArticles($topics);
		$arr = articles_init($topics);
		$babBody->addItemMenu("Articles",bab_translate("Articles"),$GLOBALS['babUrlScript']."?tg=articles&idx=Articles&topics=".$topics);
		if( $arr['nbarchive'] )
			{
			$babBody->addItemMenu("larch", bab_translate("Archives"), $GLOBALS['babUrlScript']."?tg=articles&idx=larch&topics=".$topics);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>