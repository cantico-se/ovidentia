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
define("BAB_TAG_CONTAINER", "OC");
define("BAB_TAG_VARIABLE", "OV");
define("BAB_TAG_FUNCTION", "OF");

function bab_formatDate($format, $time)
{
	global $babDays, $babMonths;
	$txt = $format;
	if(preg_match_all("/%(.)/", $format, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			switch($m[1][$i])
				{
				case 'd': /* A short textual representation of a day, three letters */
					$val = substr($babDays[date("w", $time)], 0 , 3);
					break;
				case 'D': /* day */
					$val = $babDays[date("w", $time)];
					break;
				case 'j': /* Day of the month without leading zeros */ 
					$val = date("j", $time);
					break;
				case 'm': /* A short textual representation of a month, three letters */
					$val = substr($babMonths[date("n", $time)], 0 , 3);
					break;
				case 'M': /* Month */
					$val = $babMonths[date("n", $time)];
					break;
				case 'n': /* Numeric representation of a month, without leading zeros */
					$val = date("n", $time);
					break;
				case 'Y': /* A full numeric representation of a year, 4 digits */
					$val = date("Y", $time);
					break;
				case 'H': /* 24-hour format of an hour with leading zeros */
					$val = date("H", $time);
					break;
				case 'i': /* Minutes with leading zeros */
					$val = date("i", $time);
					break;
				}
			$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
			}
		}
	return $txt;
}

function bab_formatAuthor($format, $id)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$id."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$txt = $format;
		if(preg_match_all("/%(.)/", $format, $m))
			{
			for( $i = 0; $i< count($m[1]); $i++)
				{
				switch($m[1][$i])
					{
					case 'F':
						$val = $arr['givenname'];
						break;
					case 'L':
						$val = $arr['sn'];
						break;
					case 'M':
						$val = $arr['mn'];
						break;
					}
				$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
				}
			}
		}
	else
		$txt = bab_translate("Anonymous");

	return $txt;
}

class bab_handler
{
	var $ctx;

	function bab_handler( &$ctx)
	{
		$this->ctx = $ctx;
	}

	function printout($txt)
	{
		$res = '';
		while($this->getnext())
		{
			$res .= $this->ctx->handle_text($txt);
		}
		return $res;
	}

	function getnext()
	{
		return false;
	}

}

class bab_ArticlesHomePages extends bab_handler
{
	var $arrid = array();

	function bab_ArticlesHomePages( &$ctx)
	{
		global $babBody, $babDB;

		$this->bab_handler($ctx);
		$arr = $babDB->db_fetch_array($babDB->db_query("select id from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'"));
		$idgroup = $ctx->get_value('type');
		$order = $ctx->get_value('order');
		switch(strtolower($idgroup))
			{
			case "public":
				$idgroup = 2; // non registered users
				break;
			case "private":
			default:
				if( $GLOBALS['BAB_SESS_LOGGED'])
					$idgroup = 1; // registered users
				else
					$idgroup = 2; // non registered users
				break;
			}
		$this->res = $babDB->db_query("select id_article from ".BAB_HOMEPAGES_TBL." where id_group='".$idgroup."' and id_site='".$arr['id']."' and ordering!='0' order by ordering ".($order == ""? "asc": $order));
		$this->count = $babDB->db_num_rows($this->res);
	}

	function getnext()
	{
		global $babDB;
		static $i=0;
		if( $i < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id='".$arr['id_article']."' and confirmed='Y'"));
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleReadMoreUrl', $GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			list($topictitle) = $babDB->db_fetch_array($babDB->db_query("select category from ".BAB_TOPICS_TBL." where id='".$arr['id_topic']."'"));
			$this->ctx->curctx->push('ArticleTopicTitle', $topictitle);
			$i++;
			return true;
		}
		else
		{
			$i=0;
			return false;
		}
	}
}


class bab_ArticleCategories extends bab_handler
{
	var $arrid = array();

	function bab_ArticleCategories( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$res = $babDB->db_query("select id, id_cat from ".BAB_TOPICS_TBL."");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( in_array($row['id'], $babBody->topview) )
				{
				if( !in_array($row['id_cat'], $this->arrid))
					array_push($this->arrid, $row['id_cat']);
				}
			}

		$this->count = count($this->arrid);
	}

	function getnext()
	{
		global $babDB;
		static $i=0;
		if( $i < $this->count)
		{
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$this->arrid[$i]."'"));
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$i++;
			return true;
		}
		else
		{
			$i=0;
			return false;
		}
	}
}

class bab_ArticleCategory extends bab_handler
{
	var $arrid = array();

	function bab_ArticleCategory( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$ctx->get_value('categoryid')."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		else
			$this->count = 0;
	}

	function getnext()
	{
		global $babDB;
		static $i=0;
		if( $i < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$i++;
			return true;
		}
		else
		{
			$i=0;
			return false;
		}
	}
}


class bab_ArticleTopics extends bab_handler
{
	var $arrid = array();
	var $ctx;

	function bab_ArticleTopics( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$req = "select ".BAB_TOPICS_TBL.".* from ".BAB_TOPICS_TBL." join ".BAB_TOPICS_CATEGORIES_TBL." where ".BAB_TOPICS_TBL.".id_cat=".BAB_TOPICS_CATEGORIES_TBL.".id and  ".BAB_TOPICS_TBL.".id_cat='".$ctx->get_value('categoryid')."'";
		$req .= " order by ordering asc";
		$res = $babDB->db_query($req);
		while( $row = $babDB->db_fetch_array($res))
			{
			if(in_array($row['id'], $babBody->topview))
				{
				array_push($this->arrid, $row['id']);
				}
			}
		$this->count = count($this->arrid);
	}

	function getnext()
	{
		global $babDB;
		static $i=0;
		if( $i < $this->count)
		{
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$this->arrid[$i]."'"));
			$this->ctx->curctx->push('TopicTotal', $this->count);
			$this->ctx->curctx->push('TopicName', $arr['category']);
			$this->ctx->curctx->push('TopicDescription', $arr['description']);
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			list($cattitle) = $babDB->db_fetch_array($babDB->db_query("select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_cat']."'"));
			$this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
			$this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
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


class bab_ArticleTopic extends bab_handler
{
	var $topicid;

	function bab_ArticleTopic( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->topicid = $ctx->get_value('topicid');
		if(in_array($this->topicid, $babBody->topview))
			$this->count = 1;
		else
			$this->count = 0;
	}

	function getnext()
	{
		global $babDB;
		static $i=0;
		if( $i < $this->count)
		{
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$this->topicid."'"));
			$this->ctx->curctx->push('TopicName', $arr['category']);
			$this->ctx->curctx->push('TopicDescription', $arr['description']);
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			list($cattitle) = $babDB->db_fetch_array($babDB->db_query("select category from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_cat']."'"));
			$this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
			$this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
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


class bab_Articles extends bab_handler
{
	var $ctx;

	function bab_Articles( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);

		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $ctx->get_value('topicid')))
		{
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$ctx->get_value('topicid')."'";
			$rows = $ctx->get_value('rows');
			$offset = $ctx->get_value('offset');
			if( $rows !== "" && $offset !== "" )
				{
				$req .= " limit ".$rows.", ".$offset;
				}
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
		}
		else
			$this->count = 0;
	}

	function getnext()
	{
		global $babDB;
		static $i=0;
		if( $i < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleReadMoreUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$i++;
			return true;
		}
		else
		{
			$i=0;
			return false;
		}
	}
}

class bab_Article extends bab_handler
{
	var $ctx;
	var $count;

	function bab_Article( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);

		$req = "select * from ".BAB_ARTICLES_TBL." where id='".$ctx->get_value('articleid')."' and confirmed='Y'";
		$res = $babDB->db_query($req);
		$this->arr = $babDB->db_fetch_array($res);

		if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']))
			{
			$this->count = 1;
			}
		else
			{
			$this->count = 0;
			}
	}

	function getnext()
	{
		static $i=0;
		if( $i < $this->count)
		{
			$this->ctx->curctx->push('ArticleTitle', $this->arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($this->arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($this->arr['body']));
			$this->ctx->curctx->push('ArticleId', $this->arr['id']);
			$this->ctx->curctx->push('ArticleReadMoreUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$this->arr['id_topic']."&article=".$this->arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $this->arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($this->arr['date']));
			$i++;
			return true;
		}
		else
		{
			$i=0;
			return false;
		}
	}
}


class bab_RecentArticles extends bab_handler
{
	var $ctx;
	var $db;
	var $arrid = array();
	var $count;
	var $resarticles;
	var $countarticles;
	var $lastlog;
	var $nbdays;

	function bab_RecentArticles($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		if( $this->nbdays == false )
			$this->nbdays = 0;

		if( count($babBody->topview) > 0 )
			{
			$req = "select * from ".BAB_ARTICLES_TBL." where confirmed='Y'and date >= ";
			if( $this->nbdays > 0)
				$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
			else
				$req .= "'".$babBody->lastlog."'";
			$req .= " and id_topic IN (".implode(',', $babBody->topview).")";
			$req .= " order by date desc";
			$this->resarticles = $babDB->db_query($req);
			$this->countarticles = $babDB->db_num_rows($this->resarticles);
			}
		else
			$this->countarticles = 0;
		}

	function getnext()
		{
		global $babBody, $babDB;
		static $k=0;
		if( $k < $this->countarticles)
			{
			$arr = $babDB->db_fetch_array($this->resarticles);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleReadMoreUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$k++;
			return true;
			}
		else
			{
			$k = 0;
			return false;
			}
		}
}

class bab_RecentComments extends bab_handler
{
	var $ctx;
	var $db;
	var $arrid = array();
	var $count;
	var $rescomments;
	var $countcomments;
	var $lastlog;
	var $nbdays;

	function bab_RecentComments($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		if( $this->nbdays == false )
			$this->nbdays = 0;

		if( count($babBody->topview) > 0 )
			{
			$req = "select * from ".BAB_COMMENTS_TBL." where confirmed='Y'and date >= ";
			if( $this->nbdays > 0)
				$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
			else
				$req .= "'".$babBody->lastlog."'";
			$req .= " and id_topic IN (".implode(',', $babBody->topview).")";
			$req .= " order by date desc";
			$this->rescomments = $babDB->db_query($req);
			$this->countcomments = $babDB->db_num_rows($this->resarticles);
			}
		else
			$this->countcomments = 0;
		}

	function getnext()
		{
		global $babBody, $babDB;
		static $k=0;
		if( $k < $this->countcomments)
			{
			$arr = $babDB->db_fetch_array($this->rescomments);
			$this->ctx->curctx->push('CommentTitle', $arr['subject']);
			$this->ctx->curctx->push('CommentText', $arr['message']);
			$this->ctx->curctx->push('CommentId', $arr['id']);
			$this->ctx->curctx->push('CommentTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('CommentArticleId', $arr['id_article']);
			$this->ctx->curctx->push('CommentDate', bab_mktime($arr['date']));
			$k++;
			return true;
			}
		else
			{
			$k = 0;
			return false;
			}
		}
}

class bab_RecentPosts extends bab_handler
{
	var $ctx;
	var $db;
	var $arrid = array();
	var $count;
	var $resposts;
	var $countposts;
	var $lastlog;
	var $nbdays;

	function bab_RecentPosts($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		if( $this->nbdays == false )
			$this->nbdays = 0;

		$req = "select id, id_thread from ".BAB_POSTS_TBL." where confirmed='Y' and date >=";
		if( $this->nbdays > 0)
			$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
		else
			$req .= "'".$babBody->lastlog."'";
		$res = $babDB->db_query($req);

		while( $row = $babDB->db_fetch_array($res))
			{
			list($forum) = $babDB->db_fetch_array($babDB->db_query("select forum from ".BAB_THREADS_TBL." where id='".$row['id_thread']."'"));
			if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
				{
				array_push($this->arrid, $row['id']);
				}
			}
		$this->countposts = count($this->arrid);
		}

	function getnext()
		{
		global $babBody, $babDB;
		static $k=0;
		if( $k < $this->countposts)
			{
			$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_POSTS_TBL." where id='".$this->arrid[$k]."'"));
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->ctx->curctx->push('PostText', $arr['message']);
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$k++;
			return true;
			}
		else
			{
			$k = 0;
			return false;
			}
		}
}

class bab_RecentFiles extends bab_handler
	{

	var $db;
	var $count;
	var $res;

	function bab_RecentFiles($ctx)
		{
		global $babBody, $BAB_SESS_USERID, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		if( $this->nbdays == false )
			$this->nbdays = 0;

		$req = "select ".BAB_FILES_TBL.".id, ".BAB_FILES_TBL.".name, ".BAB_FILES_TBL.".description from ".BAB_FILES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_object = '".$BAB_SESS_USERID."' and ".BAB_FILES_TBL.".confirmed='Y' and ".BAB_FILES_TBL.".id_owner=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_FILES_TBL.".bgroup='Y' and ".BAB_FILES_TBL.".state='' and ".BAB_FILES_TBL.".modified >=";
		if( $this->nbdays > 0)
			$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
		else
			$req .= "'".$babBody->lastlog."'";

		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		}

	function getnext()
		{
		static $i=0;
		if( $i < $this->count )
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('FileId', $arr['id']);
			$this->ctx->curctx->push('FileName', $arr['name']);
			$this->ctx->curctx->push('FilePath', $arr['path']);
			$this->ctx->curctx->push('FileDesc', $arr['description']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=search&idx=e&id=".$arr['id']);
			$this->ctx->curctx->push('FileAuthor', $arr['author']);
			$this->ctx->curctx->push('FileDate', bab_mktime($arr['date']));
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



class bab_context
{
	var $name;
	var $variables = array();

	function bab_context($name)
	{
		$this->name = $name;
	}

	function push( $var, $value )
	{
		$this->variables[$var] = $value;
	}

	function pop()
	{
		return array_pop($this->variables);
	}


	function get($var)
	{
		if( isset($this->variables[$var]))
			return $this->variables[$var];
		else
			return false;
	}
}


class babOvTemplate
{
var $contexts = array();
var $handlers = array();
var $curctx;
var $gctx; /* global context */

function babOvTemplate()
	{
	global $babBody;
	$this->gctx = new bab_context('bab_main');
	$this->gctx->push("babSiteName", $GLOBALS['babSiteName']);
	$this->gctx->push("babSiteSlogan", $GLOBALS['babSlogan']);
	if( $GLOBALS['BAB_SESS_USERID'] != "" )
		$this->gctx->push("babUserName", $GLOBALS['BAB_SESS_USERID']);
	else
		$this->gctx->push("babUserName", 0);
	$this->gctx->push("babCurrentDate", mktime());
	$this->gctx->push("babNewArticlesCount", $babBody->newarticles);
	$this->gctx->push("babNewCommentsCount", $babBody->newcomments);
	$this->gctx->push("babNewPostsCount", $babBody->newposts);
	$this->gctx->push("babNewFilesCount", $babBody->newfiles);
	$this->push_ctx($this->gctx);
	}

function push_ctx(&$ctx)
	{
	$this->contexts[] = &$ctx;
	$this->curctx = &$ctx;
	return $this->curctx;
	}

function pop_ctx()
	{
	if( count($this->contexts) > 1 )
		{
		array_pop($this->contexts);
		$this->curctx =& $this->contexts[count($this->contexts)-1];
		return $this->curctx;
		}
	}

function get_value($var)
	{
	for( $i = count($this->contexts)-1; $i >= 0; $i--)
		{
		$val = $this->contexts[$i]->get($var);
		if( $val !== false)
			{
			return $val;
			}
		}
	return false;
	}

function handle_tag( $txt )
	{
	$out = '';

	if(preg_match_all("/(.*?)<".BAB_TAG_CONTAINER."([^\s]*)\s*([^>]*?)>(.*?)<\/".BAB_TAG_CONTAINER."\\2>(.*)/s", $txt, $m))
		{
		for( $i = 0; $i< count($m[3]); $i++)
			{
			$out .= $this->handle_text($m[1][$i]);

			$txt2 = $this->handle_text($m[3][$i]);
			if(preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $txt2, $mm))
				{
				for( $j = 0; $j< count($mm[1]); $j++)
					{
					$this->curctx->push($mm[1][$j], $mm[3][$j]);
					}
				}
	
			$handler = $m[2][$i];
			$ctx = new bab_context($handler);
			$this->push_ctx($ctx);
			$handler = "bab_".$handler;
			if( class_exists($handler))
				{
				$cls = new $handler($this);
				$out .= $this->handle_tag($cls->printout($m[4][$i]));
				$this->pop_ctx();
				$out .= $this->handle_tag($this->handle_text($m[5][$i]));
				}
			}
		}
	else
		{
		$out .= $this->handle_text($txt);
		}

	return $out;
	}

function format_output($val, $matches)
	{
	for( $j = 0; $j< count($matches[1]); $j++)
		{
		switch(strtolower(trim($matches[1][$j])))
			{
			case 'strlen':
				$val = substr($val, 0, $matches[3][$j]);
				break;
			case 'striptags':
				if( $matches[3][$j] == '1')
					$val = strip_tags($val);
				break;
			case 'htmlentities':
				if( $matches[3][$j] == '1')
					$val = htmlentities($val);
				break;
			case 'stripslashes':
				if( $matches[3][$j] == '1')
					$val = stripslashes($val);
				break;
			case 'urlencode':
				if( $matches[3][$j] == '1')
					$val = urlencode($val);
				break;
			case 'strcase':
				switch($matches[3][$j])
					{
					case 'upper':
						$val = strtoupper($val); break;
					case 'lower':
						$val = strtolower($val); break;
					}
				break;
			case 'trim':
				switch($matches[3][$j])
					{
					case 'left':
						$val = ltrim($val); break;
					case 'right':
						$val = rtrim($val); break;
					case 'all':
						$val = trim($val); break;
					}
				break;
			case 'nl2br':
				if( $matches[3][$j] == '1')
					$val = nl2br($val);
				break;
			case 'sprintf':
				$val = sprintf($matches[3][$j], $val);
				break;
			case 'date':
				$val = bab_formatDate($matches[3][$j], $val);
				break;
			case 'author':
				$val = bab_formatAuthor($matches[3][$j], $val);
				break;
			}
		}
	return $val;
	}

function handle_text($txt)
	{
	if(preg_match_all("/<".BAB_TAG_VARIABLE."([^\s>]*)\s*([^>]*?)>/", $txt, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			$val = $this->get_value($m[1][$i]);
			$args = trim($m[2][$i]);
			if( $val !== false )
				{
				if( $args != "" )
					{
					if(preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $args, $mm))
						{
						$val = $this->format_output($val, $mm);
						}
					}
				$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", $val, $txt);
				}
			}
		}
	
	if(preg_match_all("/<".BAB_TAG_FUNCTION."([^\s>]*)\s*([^>]*?)>/", $txt, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			$handler = "bab_".$m[1][$i];
			$val = $this->$handler(trim($m[2][$i]));
			$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", $val, $txt);
			}
		}

	return $txt;
	}

function match_args(&$args, &$mm)
	{
	return preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $args, $mm);
	}

/* translate text */
function bab_Translate($args)
	{
	$lang = "";

	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'text':
					$text = $mm[3][$j];
					break;
				case 'lang':
					$lang = $mm[3][$j];
					break;
				}
			}					
		return $this->format_output(bab_translate($text, "", $lang), $mm);
		}
	return '';
	}

/* save a variable to global space */
function bab_PutVar($args)
	{
	$name = "";
	$value = "";

	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'name':
					$name = $mm[3][$j];;					
					break;
				case 'value':
					$value = $mm[3][$j];;					
					break;
				}
			}					
		$this->gctx->push($name, $value);
		}
	}

/* save a variable to global space */
function bab_UrlContent($args)
	{
	$url = "";
	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'url':
					$url = $mm[3][$j];
					$purl = parse_url($url);
					break;
				}
			}
		return $this->format_output(preg_replace("/(src=|background=|href=)(['\"])([^'\">]*)(['\"])/e", '"\1\"".bab_rel2abs("\3", $purl)."\""', implode('', file($url))), $mm);
		}
	}

function printout($txt)
	{
	global $babBody;
	$babBody->babecho( $out = $this->handle_tag($txt));
	}

}


function bab_rel2abs($relative, $url)
	{
    if (preg_match(',^(https?://|ftp://|mailto:|news:),i', $relative))
        return $relative;

	if( $relative[0] == '#')
		return $relative;

    if ($url['path']{strlen($url['path']) - 1} == '/')
        $dir = substr($url['path'], 0, strlen($url['path']) - 1);
    else
        $dir = dirname($url['path']);

    if ($relative{0} == '/')
		{
        $relative = substr($relative, 1);
        $dir = '';
		}

    else if (substr($relative, 0, 2) == './')
        $relative = substr($relative, 2);
    else while (substr($relative, 0, 3) == '../')
		{
        $relative = substr($relative, 3);
        $dir = substr($dir, 0, strrpos($dir, '/'));
		}
    return sprintf('%s://%s%s/%s', $url['scheme'], $url['host'], $dir, $relative);
}

/*
$tmp = new babOvTemplate();
$tmp->printout(implode("", file($file)));
*/
$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
if( !is_readable($filepath))
	{
	return;
	}
else
	{
	$tmp = new babOvTemplate();
	$tmp->printout(implode("", file($filepath)));
	}
?>