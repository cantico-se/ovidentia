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
define("BAB_TAG_CONTAINER", "OC");
define("BAB_TAG_VARIABLE", "OV");
define("BAB_TAG_FUNCTION", "OF");


define("BAB_OPE_EQUAL"				, 1);
define("BAB_OPE_NOTEQUAL"			, 2);
define("BAB_OPE_LESSTHAN"			, 3);
define("BAB_OPE_LESSTHANOREQUAL"	, 4);
define("BAB_OPE_GREATERTHAN"		, 5);
define("BAB_OPE_GREATERTHANOREQUAL"	, 6);


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
				case 'j': /* Day of the month with leading zeros */ 
					$val = date("d", $time);
					break;
				case 'm': /* A short textual representation of a month, three letters */
					$val = substr($babMonths[date("n", $time)], 0 , 3);
					break;
				case 'M': /* Month */
					$val = $babMonths[date("n", $time)];
					break;
				case 'n': /* Numeric representation of a month, with leading zeros */
					$val = date("m", $time);
					break;
				case 'Y': /* A full numeric representation of a year, 4 digits */
					$val = date("Y", $time);
					break;
				case 'y': /* A two digit representation of a year */
					$val = date("y", $time);
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
	var $idx;

	function bab_handler( &$ctx)
	{
		$this->ctx = $ctx;
		$this->idx = 0;
	}

	function printout($txt)
	{
		$this->ctx->push_handler($this);
		$res = '';
		$skip = false;
		while($this->getnext($skip))
		{
			if( !$skip)
				$res .= $this->ctx->handle_text($txt);
			$skip = false;
		}
		$this->ctx->pop_handler();
		return $res;
	}

	function getnext()
	{
		return false;
	}

}

class bab_Operator extends bab_handler
{
	var $count;

	function bab_Operator( &$ctx, $operator)
	{
		$this->count = 0;
		$this->bab_handler($ctx);
		$expr1 = $ctx->get_value('expr1');
		$expr2 = $ctx->get_value('expr2');
		if( $expr1 !== false && $expr2 !== false)
		{
			switch($operator)
				{
				case BAB_OPE_EQUAL:
					if( $expr1 == $expr2) $this->count = 1;	break;
				case BAB_OPE_NOTEQUAL:
					if( $expr1 != $expr2) $this->count = 1;	break;
				case BAB_OPE_LESSTHAN:
					if( $expr1 < $expr2) $this->count = 1;	break;
				case BAB_OPE_LESSTHANOREQUAL:
					if( $expr1 <= $expr2) $this->count = 1;	break;
				case BAB_OPE_GREATERTHAN:
					if( $expr1 > $expr2) $this->count = 1;	break;
				case BAB_OPE_GREATERTHANOREQUAL:
					if( $expr1 >= $expr2) $this->count = 1;	break;
				default:
					break;
				}
		}
	}

	function getnext()
	{
		if( $this->idx < $this->count)
		{
			$this->idx++;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_IfEqual extends bab_Operator
{
	function bab_IfEqual( &$ctx)
	{
		$this->bab_Operator($ctx, BAB_OPE_EQUAL);
	}
}

class bab_IfNotEqual extends bab_Operator
{
	function bab_IfNotEqual( &$ctx)
	{
		$this->bab_Operator($ctx, BAB_OPE_NOTEQUAL);
	}
}

class bab_IfLessThan extends bab_Operator
{
	function bab_IfLessThan( &$ctx)
	{
		$this->bab_Operator($ctx, BAB_OPE_LESSTHAN);
	}
}

class bab_IfLessThanOrEqual extends bab_Operator
{
	function bab_IfLessThanOrEqual( &$ctx)
	{
		$this->bab_Operator($ctx, BAB_OPE_LESSTHANOREQUAL);
	}
}

class bab_IfGreaterThan extends bab_Operator
{
	function bab_IfGreaterThan( &$ctx)
	{
		$this->bab_Operator($ctx, BAB_OPE_GREATERTHAN);
	}
}

class bab_IfGreaterThanOrEqual extends bab_Operator
{
	function bab_IfGreaterThanOrEqual( &$ctx)
	{
		$this->bab_Operator($ctx, BAB_OPE_GREATERTHANOREQUAL);
	}
}

class bab_ArticlesHomePages extends bab_handler
{
	var $IdEntries = array();
	var $arrid = array();
	var $index;
	var $count;

	function bab_ArticlesHomePages( &$ctx)
	{
		global $babBody, $babDB;

		$this->bab_handler($ctx);
		$arr['id'] = $babBody->babsite['id'];
		$idgroup = $ctx->get_value('type');
		$order = $ctx->get_value('order');
		if( $order === false || $order === '' )
			$order = "asc";

		switch(strtoupper($order))
		{
			case "DESC": $order = "ordering DESC"; break;
			case "RAND": $order = "rand()"; break;
			case "ASC":
			default: $order = "ordering ASC"; break;
		}

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
	
		$filter = $ctx->get_value('filter');

		if (($filter == "")||(strtoupper($filter) == "NO")) 
			$filter = false;
		else
			$filter = true;

		$res = $babDB->db_query("select at.id, at.id_topic, at.restriction from ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_HOMEPAGES_TBL." ht on ht.id_article=at.id where ht.id_group='".$idgroup."' and ht.id_site='".$arr['id']."' and ht.ordering!='0' order by ".$order);
		while($arr = $babDB->db_fetch_array($res))
		{
			if( $arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']) )
				{
				if( $filter == false || in_array($arr['id_topic'], $babBody->topview))
					{
					$this->IdEntries[] = $arr['id'];
					}
				}
		}

		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id IN (".implode(',', $this->IdEntries).")  and confirmed='Y'");
			}

		$this->count = $babDB->db_num_rows($this->res);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			list($topictitle) = $babDB->db_fetch_array($babDB->db_query("select category from ".BAB_TOPICS_TBL." where id='".$arr['id_topic']."'"));
			$this->ctx->curctx->push('ArticleTopicTitle', $topictitle);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_ArticleCategories extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_ArticleCategories( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$parentid = $ctx->get_value('parentid');

		if( $parentid === false || $parentid === '' )
			$parentid[] = 0;
		else
			$parentid = array_intersect($babBody->topcatview, explode(',', $parentid));

		if( count($parentid) > 0 )
		{
		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent IN (".implode(',', $parentid).")");

		while( $row = $babDB->db_fetch_array($res))
			{
			if( in_array($row['id'], $babBody->topcatview) )
				{
				if( count($this->IdEntries) == 0 || !in_array($row['id'], $this->IdEntries))
					{
					array_push($this->IdEntries, $row['id']);
					}
				}
			}
		}
		$this->count = count($this->IdEntries);
		if( $this->count > 0)
			{
			$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".implode(',', $this->IdEntries).")");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_ParentsArticleCategory extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	function bab_ParentsArticleCategory( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$categoryid = $ctx->get_value('categoryid');

		if( $categoryid === false || $categoryid === '' )
			$this->count = 0;
		else
			{
			while( $babBody->parentstopcat[$categoryid]['parent'] != 0 )
				{
				$this->IdEntries[] = $babBody->parentstopcat[$categoryid]['parent'];
				$categoryid = $babBody->parentstopcat[$categoryid]['parent'];
				}
			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$reverse = $ctx->get_value('reverse');
				if( $reverse === false || $reverse !== '1' )
					$this->IdEntries = array_reverse($this->IdEntries);
				$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".implode(',', $this->IdEntries).")");
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_ArticleCategory extends bab_handler
{
	var $arrid = array();
	var $index;
	var $count;
	var $res;

	function bab_ArticleCategory( &$ctx)
	{
		global $babBody, $babDB;
		$this->count = 0;
		$this->bab_handler($ctx);
		$catid = $ctx->get_value('categoryid');

		if( $catid === false || $catid === '' )
			$catid = $babBody->topcatview;
		else
			$catid = array_intersect($babBody->topcatview, explode(',', $catid));
		
		if( count($catid) > 0 )
		{
		$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".implode(',', $catid).")");
		$this->count = $babDB->db_num_rows($this->res);
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_ArticleCategoryPrevious extends bab_ArticleCategory
{
	var $handler;

	function bab_ArticleCategoryPrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_ArticleCategories');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('categoryid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_ArticleCategory($ctx);
	}

}

class bab_ArticleCategoryNext extends bab_ArticleCategory
{
	var $handler;

	function bab_ArticleCategoryNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_ArticleCategories');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('categoryid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_ArticleCategory($ctx);
	}

}




class bab_ArticleTopics extends bab_handler
{
	var $IdEntries = array();
	var $ctx;
	var $index;
	var $count;

	function bab_ArticleTopics( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$catid = $ctx->get_value('categoryid');

		if( $catid === false || $catid === '' )
			$catid = $babBody->topcatview;
		else
			$catid = array_intersect($babBody->topcatview, explode(',', $catid));

		if( count($catid) > 0 )
		{
		$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where type='2' and id_parent IN (".implode(',', $catid).") order by ordering asc";

		$res = $babDB->db_query($req);
		while( $row = $babDB->db_fetch_array($res))
			{
			if(in_array($row['id_topcat'], $babBody->topview))
				{
				array_push($this->IdEntries, $row['id_topcat']);
				}
			}
		}
		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id IN (".implode(',', $this->IdEntries).")");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('TopicTotal', $this->count);
			$this->ctx->curctx->push('TopicName', $arr['category']);
			$this->ctx->curctx->push('TopicDescription', $arr['description']);
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('TopicLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			list($cattitle) = $babDB->db_fetch_array($babDB->db_query("select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_cat']."'"));
			$this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
			$this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx = 0;
			return false;
		}
	}
}

class bab_ArticleTopic extends bab_handler
{
	var $IdEntries = array();
	var $topicid;
	var $count;
	var $index;

	function bab_ArticleTopic( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->topicid = $ctx->get_value('topicid');

		if( $this->topicid === false || $this->topicid === '' )
			$this->IdEntries = $babBody->topview;
		else
			$this->IdEntries = array_values(array_intersect($babBody->topview, explode(',', $this->topicid)));
		$this->count = count($this->IdEntries);

		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id IN (".implode(',', $this->IdEntries).")");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('TopicName', $arr['category']);
			$this->ctx->curctx->push('TopicDescription', $arr['description']);
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('TopicLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			list($cattitle) = $babDB->db_fetch_array($babDB->db_query("select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_cat']."'"));
			$this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
			$this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx = 0;
			return false;
		}
	}
}


class bab_ArticleTopicPrevious extends bab_ArticleTopic
{
	var $handler;

	function bab_ArticleTopicPrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_ArticleTopics');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('topicid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_ArticleTopic($ctx);
	}

}

class bab_ArticleTopicNext extends bab_ArticleTopic
{
	var $handler;

	function bab_ArticleTopicNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_ArticleCategories');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('topicid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_ArticleTopic($ctx);
	}

}



class bab_Articles extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $res;

	function bab_Articles( &$ctx)
	{
		global $babDB, $babBody;
		$this->bab_handler($ctx);
		$topicid = $ctx->get_value('topicid');
		if( $topicid === false || $topicid === '' )
			$topicid = $babBody->topview;
		else
			$topicid = array_intersect($babBody->topview, explode(',', $topicid));

		if( count($topicid) > 0)
		{
			$archive = $ctx->get_value('archive');
			if( $archive === false || $archive === '')
				$archive = "no";

			switch(strtoupper($archive))
			{
				case 'NO': $archive = " and archive='N' "; break;
				case 'YES': $archive = " and archive='Y' "; break;
				default: $archive = ""; break;

			}

			$req = "select id, restriction from ".BAB_ARTICLES_TBL." where confirmed='Y' ".$archive." and id_topic IN (".implode(',', $topicid).")";

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "asc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "date ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "date DESC"; break;
			}

			$req .= " order by ".$order;

			$rows = $ctx->get_value('rows');
			$offset = $ctx->get_value('offset');
			if( $rows === false || $rows === '')
				$rows = "-1";

			if( $offset === false || $offset === '')
				$offset = "0";
			$req .= " limit ".$offset.", ".$rows;
			$res = $babDB->db_query($req);

			while( $arr = $babDB->db_fetch_array($res) )
			{
				if( $arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']))
					{
					$this->IdEntries[] = $arr['id'];
					}
			}

			$this->count = count($this->IdEntries);
			$this->res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id IN (".implode(',', $this->IdEntries).") order by ".$order);
		}
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_Article extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	function bab_Article( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$articleid = $ctx->get_value('articleid');
		if( $articleid === false || $articleid === '' )
			$this->count = 0;
		else
		{
			$res = $babDB->db_query("select id, id_topic, restriction from ".BAB_ARTICLES_TBL." where id IN (".$articleid.") and confirmed='Y'");
			while( $arr = $babDB->db_fetch_array($res))
			{
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])))
				{
				$this->IdEntries[] = $arr['id'];
				}
			}
			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id IN (".implode(',', $this->IdEntries).")");
				}
			$this->count = $babDB->db_num_rows($this->res);
		}
		
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_ArticlePrevious extends bab_Article
{
	var $handler;

	function bab_ArticlePrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Articles');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('articleid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_Article($ctx);
	}

}

class bab_ArticleNext extends bab_Article
{
	var $handler;

	function bab_ArticleNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Articles');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('articleid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_Article($ctx);
	}

}

class bab_Forums extends bab_handler
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	function bab_Forums( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$forumid = $ctx->get_value('forumid');
		if( $forumid === false || $forumid === '' )
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." order by ordering asc");
		else
			{
			$forumid = explode(',', $forumid);
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where id IN (".implode(',', $forumid).") order by ordering asc");
			}
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
				{
				array_push($this->IdEntries, $row['id']);
				}
			}
		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where id IN (".implode(',', $this->IdEntries).") order by ordering asc");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);

	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ForumName', $arr['name']);
			$this->ctx->curctx->push('ForumDescription', $arr['description']);
			$this->ctx->curctx->push('ForumId', $arr['id']);
			$this->ctx->curctx->push('ForumUrl', $GLOBALS['babUrlScript']."?tg=threads&forum=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_Forum extends bab_handler
{
	var $index;
	var $count;
	var $res;

	function bab_Forum( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where id='".$ctx->get_value('forumid')."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ForumName', $arr['name']);
			$this->ctx->curctx->push('ForumDescription', $arr['description']);
			$this->ctx->curctx->push('ForumId', $arr['id']);
			$this->ctx->curctx->push('ForumUrl', $GLOBALS['babUrlScript']."?tg=threads&forum=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_ForumPrevious extends bab_Forum
{
	var $handler;

	function bab_ForumPrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Forums');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('forumid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_Forum($ctx);
	}

}

class bab_ForumNext extends bab_Forum
{
	var $handler;

	function bab_ForumNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Forums');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('forumid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_Forum($ctx);
	}

}


class bab_Post extends bab_handler
{
	var $res;
	var $arrid = array();
	var $arrfid = array();
	var $resposts;
	var $count;
	var $postid;

	function bab_Post($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->postid = $ctx->get_value('postid');
		if( $this->postid === false || $this->postid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->postid);

		if( count($arr) > 0 )
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE p.id IN (".implode(',', $arr).") AND p.confirmed =  'Y'";			
			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "asc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "p.date ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "p.date DESC"; break;
			}

			$req .= " order by ".$order;

			$res = $babDB->db_query($req);

			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id_forum']))
					{
					array_push($this->arrid, $row['id']);
					array_push($this->arrfid, $row['id_forum']);
					}
				}
			}

		$this->count = count($this->arrid);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_POSTS_TBL." p where id IN (".implode(',', $this->arrid).") order by ".$order);
			$this->count = $babDB->db_num_rows($this->res);
			}

		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);	
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->ctx->curctx->push('PostText', bab_replace($arr['message']));
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_Thread extends bab_handler
{
	var $arrid = array();
	var $res;
	var $resposts;
	var $count;
	var $postid;

	function bab_Thread($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->threadid = $ctx->get_value('threadid');
		if( $this->threadid === false || $this->threadid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->threadid);

		if( count($arr) > 0 )
			{
			$req = "select id, forum from ".BAB_THREADS_TBL." WHERE id IN (".implode(',', $arr).") and active='Y'";

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "asc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "date ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "date DESC"; break;
			}

			$req .= " order by ".$order;

			$res = $babDB->db_query($req);

			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['forum']))
					{
					array_push($this->arrid, $row['id']);
					}
				}
			}

		$this->count = count($this->arrid);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_THREADS_TBL." where id IN (".implode(',', $this->arrid).") order by ".$order);
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ThreadForumId', $arr['forum']);
			$this->ctx->curctx->push('ThreadId', $arr['id']);
			$this->ctx->curctx->push('ThreadPostId', $arr['post']);
			$this->ctx->curctx->push('ThreadLastPostId', $arr['lastpost']);
			$this->ctx->curctx->push('ThreadDate',  bab_mktime($arr['date']));
			$this->ctx->curctx->push('ThreadStarter',  $arr['starter']);
			$this->ctx->curctx->push('ThreadUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['forum']."&thread=".$arr['id']."&views=1");
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_Folders extends bab_handler
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	function bab_Folders( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$folderid = $ctx->get_value('folderid');
		if( $folderid === false || $folderid === '' )
			$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where active='Y' order by folder asc");
		else
			{
			$folderid = explode(',', $folderid);
			$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where active='Y' and id IN (".implode(',', $folderid).") order by folder asc");
			}

		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $row['id']))
				{
				array_push($this->IdEntries, $row['id']);
				}
			}
		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id IN (".implode(',', $this->IdEntries).") order by folder asc");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);

	}

	function getnext()
	{
		global $babDB;

		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FolderName', $arr['folder']);
			$this->ctx->curctx->push('FolderId', $arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_Folder extends bab_handler
{
	var $index;
	var $count;
	var $res;

	function bab_Folder( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$folderid = $ctx->get_value('folderid');
		$this->count = 0;
		if($folderid !== false && $folderid !== '' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folderid))
		{
		$this->res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id='".$folderid."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FolderName', $arr['folder']);
			$this->ctx->curctx->push('FolderId', $arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_FolderPrevious extends bab_Folder
{
	var $handler;

	function bab_FolderPrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Folders');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('folderid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_Folder($ctx);
	}

}

class bab_FolderNext extends bab_Folder
{
	var $handler;

	function bab_FolderNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Folders');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('folderid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_Folder($ctx);
	}

}




class bab_SubFolders extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $res;

	function bab_SubFolders( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$folderid = $ctx->get_value('folderid');
		$this->count = 0;
		if($folderid !== false && $folderid !== '' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folderid))
		{
		$res = $babDB->db_query("select * from ".BAB_FM_FOLDERS_TBL." where id='".$folderid."'");
		if( $res && $babDB->db_num_rows($res) == 1 )
			{
			$arr = $babDB->db_fetch_array($res);
			$path = $ctx->get_value('path');
			if( $path === false || $path === '' )
				$path = '';

			if( substr($GLOBALS['babUploadPath'], -1) == "/" )
				$fullpath = $GLOBALS['babUploadPath'];
			else
				$fullpath = $GLOBALS['babUploadPath']."/";

			if( $path != "" )
				$fullpath = $fullpath."G".$folderid."/".$path."/";
			else
				$fullpath = $fullpath."G".$folderid."/";
			if( is_dir($fullpath))
				{
					$h = opendir($fullpath);
					while (($f = readdir($h)) != false)
						{
						if ($f != "." and $f != ".." and $f != "OVF") 
							{
							if (is_dir($fullpath."/".$f))
								{
								$this->IdEntries[] = $f;
								}
							}
						}
					closedir($h);
					$this->count = count($this->IdEntries);
				}
			}

		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('SubFolderName', $this->IdEntries[$this->idx]);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_Files extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	function bab_Files( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		$folderid = $ctx->get_value('folderid');
		$path = $ctx->get_value('path');
		if( $path === false || $path === '' )
			$path = '';
		if($folderid !== false && $folderid !== '' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folderid))
		{
			$rows = $ctx->get_value('rows');
			$offset = $ctx->get_value('offset');
			if( $rows === false || $rows === '')
				$rows = "-1";

			if( $offset === false || $offset === '')
				$offset = "0";
			$req = "select id from ".BAB_FILES_TBL." where id_owner='".$folderid."' and bgroup='Y' and state='' and path='".addslashes($path)."' and confirmed='Y' order by name asc";
			$req .= " limit ".$offset.", ".$rows;

			$this->res = $babDB->db_query($req);
			while($arr = $babDB->db_fetch_array($this->res))
			{
				$this->IdEntries[] = $arr['id'];
			}

			$this->count =  count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id IN (".implode(',', $this->IdEntries).") order by name asc");
				$this->count = $babDB->db_num_rows($this->res);
				}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileName', $arr['name']);
			$this->ctx->curctx->push('FileDescription', $arr['description']);
			$this->ctx->curctx->push('FileKeywords', $arr['keywords']);
			$this->ctx->curctx->push('FileId', $arr['id']);
			$this->ctx->curctx->push('FileFolderId', $arr['id_owner']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path']));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_File extends bab_handler
{
	var $arr;
	var $index;
	var $count;

	function bab_File(&$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		$fileid = $ctx->get_value('fileid');
		if($fileid !== false && $fileid !== '')
		{
			$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$fileid."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$this->arr = $babDB->db_fetch_array($res);
			if( $this->arr['bgroup'] == 'Y' && $this->arr['state'] == '' && $this->arr['confirmed'] == 'Y' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $this->arr['id_owner']))
				$this->count = 1;
			}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileName', $this->arr['name']);
			$this->ctx->curctx->push('FileDescription', $this->arr['description']);
			$this->ctx->curctx->push('FileKeywords', $this->arr['keywords']);
			$this->ctx->curctx->push('FileId', $this->arr['id']);
			$this->ctx->curctx->push('FileFolderId', $this->arr['id_owner']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path']));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$this->arr['id']."&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path'])."&file=".urlencode($this->arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path'])."&file=".urlencode($this->arr['name']));
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_FileFields extends bab_handler
{
	var $fileid;
	var $index;
	var $count;
	var $res;

	function bab_FileFields(&$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		$this->fileid = $ctx->get_value('fileid');
		if($this->fileid !== false && $this->fileid !== '')
		{
			$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$this->fileid."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['bgroup'] == 'Y' && $arr['state'] == '' && $arr['confirmed'] == 'Y' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id_owner']))
				{
				$this->res = $babDB->db_query("select ff.*, fft.name from ".BAB_FM_FIELDSVAL_TBL." ff LEFT JOIN ".BAB_FM_FIELDS_TBL." fft on fft.id = ff.id_field where id_file='".$this->fileid."' and id_folder='".$arr['id_owner']."'");
				if( $this->res )
					{
					$this->count = $babDB->db_num_rows($this->res);
					}
				}
			}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileFieldName', bab_translate($arr['name']));
			$fieldval = htmlentities($arr['fvalue']);
			$this->ctx->curctx->push('FileFieldValue', $fieldval);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_FilePrevious extends bab_File
{
	var $handler;

	function bab_FilePrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Files');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('fileid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_File($ctx);
	}

}

class bab_FileNext extends bab_File
{
	var $handler;

	function bab_FileNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Files');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('fileid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_File($ctx);
	}

}

class bab_RecentArticles extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $arrid = array();
	var $index;
	var $count;
	var $resarticles;
	var $nbdays;
	var $last;
	var $topicid;

	function bab_RecentArticles($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->topicid = $ctx->get_value('topicid');
		if( $this->topicid === false || $this->topicid === '' )
			$this->topicid = $babBody->topview;
		else
			$this->topicid = array_intersect($babBody->topview, explode(',', $this->topicid));

		if( count($this->topicid) > 0 )
			{
			$archive = $ctx->get_value('archive');
			if( $archive === false || $archive === '')
				$archive = "no";

			switch(strtoupper($archive))
			{
				case 'NO': $archive = " and archive='N' "; break;
				case 'YES': $archive = " and archive='Y' "; break;
				default: $archive = ""; break;

			}

			$req = "select id, restriction from ".BAB_ARTICLES_TBL." where confirmed='Y'".$archive;
			if( $this->nbdays !== false)
				$req .= " and date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

			$req .= " and id_topic IN (".implode(',', $this->topicid).")";

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "desc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "date ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "date DESC"; break;
			}

			$req .= " order by ".$order;

			if( $this->last !== false)
				$req .= " limit 0, ".$this->last;

			$this->resarticles = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($this->resarticles))
				{
				if( $arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']))
					{
					$this->IdEntries[] = $arr['id'];
					}
				}
			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id IN (".implode(',', $this->IdEntries).") order by ".$order);
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		else
			{
			$this->count = 0;
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}

class bab_RecentComments extends bab_handler
{
	var $index;
	var $count;
	var $rescomments;
	var $countcomments;
	var $lastlog;
	var $nbdays;
	var $last;
	var $articleid;

	function bab_RecentComments($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->articleid = $ctx->get_value('articleid');
		if( $this->articleid === false || $this->articleid === '' )
			$arrid = array();
		else
			$arrid = explode(',', $this->articleid);

		$req = '';
		if( count($arrid) > 0 )
			{
			$req = "select * from ".BAB_COMMENTS_TBL." where id_article IN (".implode(',', $arrid).") and confirmed='Y'";
			}
		else if( count($babBody->topview) > 0 )
			{
			$req = "select * from ".BAB_COMMENTS_TBL." where confirmed='Y' and id_topic IN (".implode(',', $babBody->topview).")";
			}
		
		if( $req != '' )
			{
			if( $this->nbdays !== false)
				$req .= " and date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "asc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "date ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "date DESC"; break;
			}

			$req .= " order by ".$order;

			if( $this->last !== false)
				$req .= " limit 0, ".$this->last;
			$this->rescomments = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->rescomments);
			}
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->rescomments);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CommentTitle', $arr['subject']);
			$this->ctx->curctx->push('CommentText', $arr['message']);
			$this->ctx->curctx->push('CommentId', $arr['id']);
			$this->ctx->curctx->push('CommentTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('CommentArticleId', $arr['id_article']);
			$this->ctx->curctx->push('CommentDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('CommentAuthor', $arr['name']);
			$this->ctx->curctx->push('CommentLanguage', $arr['lang']);
			$this->ctx->curctx->push('CommentUrl', $GLOBALS['babUrlScript']."?tg=comments&idx=read&topics=".$arr['id_topic']."&article=".$arr['id_article']."&com=".$arr['id']);
			$this->ctx->curctx->push('CommentPopupUrl', $GLOBALS['babUrlScript']."?tg=comments&idx=viewc&com=".$arr['id']."&article=".$arr['id_article']."&topics=".$arr['id_topic']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}

class bab_RecentPosts extends bab_handler
{
	var $res;
	var $arrid = array();
	var $arrfid = array();
	var $resposts;
	var $count;
	var $lastlog;
	var $nbdays;
	var $last;
	var $forumid;

	function bab_RecentPosts($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->forumid = $ctx->get_value('forumid');
		if( $this->forumid === false || $this->forumid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->forumid);

		if( count($arr) > 0 )
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE t.forum IN (".implode(',', $arr).") and p.confirmed='Y'";	
			}
		else
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE p.confirmed='Y'";			
			}

		if( $this->nbdays !== false)
			$req .= " and p.date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";


		$order = $ctx->get_value('order');
		if( $order === false || $order === '' )
			$order = "asc";

		switch(strtoupper($order))
		{
			case "ASC": $order = "p.date ASC"; break;
			case "RAND": $order = "rand()"; break;
			case "DESC":
			default: $order = "p.date DESC"; break;
		}

		$req .= " order by ".$order;
		
		if( $this->last !== false)
			$req .= " limit 0, ".$this->last;

		$res = $babDB->db_query($req);

		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id_forum']))
				{
				array_push($this->arrid, $row['id']);
				array_push($this->arrfid, $row['id_forum']);
				}
			}
		$this->count = count($this->arrid);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_POSTS_TBL." p where id IN (".implode(',', $this->arrid).") order by ".$order);
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->ctx->curctx->push('PostText', bab_replace($arr['message']));
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}

class bab_RecentThreads extends bab_handler
{
	var $res;
	var $arrid = array();
	var $arrfid = array();
	var $resposts;
	var $count;
	var $lastlog;
	var $nbdays;
	var $last;
	var $forumid;

	function bab_RecentThreads($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->forumid = $ctx->get_value('forumid');
		if( $this->forumid === false || $this->forumid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->forumid);

		if( count($arr) > 0 )
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE t.forum IN (".implode(',', $arr).") and p.confirmed='Y' and p.id_parent='0'";			
			}
		else
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE p.confirmed='Y' and p.id_parent='0'";			
			}

		if( $this->nbdays !== false)
			$req .= " and p.date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

		$order = $ctx->get_value('order');
		if( $order === false || $order === '' )
			$order = "asc";

		switch(strtoupper($order))
		{
			case "ASC": $order = "p.date ASC"; break;
			case "RAND": $order = "rand()"; break;
			case "DESC":
			default: $order = "p.date DESC"; break;
		}

		$req .= " order by ".$order;

		if( $this->last !== false)
			$req .= " limit 0, ".$this->last;

		$res = $babDB->db_query($req);

		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id_forum']))
				{
				array_push($this->arrid, $row['id']);
				array_push($this->arrfid, $row['id_forum']);
				}
			}
		$this->count = count($this->arrid);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_POSTS_TBL." p where id IN (".implode(',', $this->arrid).") order by ".$order);
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->ctx->curctx->push('PostText', bab_replace($arr['message']));
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_RecentFiles extends bab_handler
	{

	var $index;
	var $count;
	var $res;
	var $lastlog;
	var $nbdays;
	var $last;
	var $folderid;


	function bab_RecentFiles($ctx)
		{
		global $babBody, $BAB_SESS_USERID, $babDB;
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->folderid = $ctx->get_value('folderid');
		if( $this->folderid === false || $this->folderid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->folderid);
		
		if( count($arr) == 0 )
			{
			$req = "select * from ".BAB_FM_FOLDERS_TBL." where active='Y'";
			}
		else
			{
			$req = "select * from ".BAB_FM_FOLDERS_TBL." where active='Y' and id IN (".implode(',', $arr).")";
			}

		$arrid = array();
		$res = $babDB->db_query($req);
		while( $arr = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id']))
				$arrid[] = $arr['id'];
			}

		if( count($arrid) > 0 )
			{
			$req = "select distinct f.* from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.state='' and f.confirmed='Y' and f.id_owner IN (".implode(',', $arrid).")";
			if( $this->nbdays !== false)
				$req .= " and f.modified >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "asc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "f.modified ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "f.modified DESC"; break;
			}

			$req .= " order by ".$order;
			
			
			if( $this->last !== false)
				$req .= " limit 0, ".$this->last;
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}
		else
			$this->count = 0;

		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babDB;
		if( $this->idx < $this->count )
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileId', $arr['id']);
			$this->ctx->curctx->push('FileName', $arr['name']);
			$this->ctx->curctx->push('FilePath', $arr['path']);
			$this->ctx->curctx->push('FileDescription', $arr['description']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path']));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileAuthor', $arr['author']);
			$this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));
			$this->ctx->curctx->push('FileFolderId', $arr['id_owner']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}

	}


class bab_WaitingArticles extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;
	var $topicid;

	function bab_WaitingArticles($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);

		$userid = $ctx->get_value('userid');
		if( $userid === false || $userid === '' )
			$userid = $GLOBALS['BAB_SESS_USERID'];

		if( $userid != '')
			{
			//include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			$this->topicid = $ctx->get_value('topicid');
			$req = "select a.id from ".BAB_ARTICLES_TBL." a join ".BAB_FAR_INSTANCES_TBL." fi where a.confirmed='N'";
			if( $this->topicid !== false && $this->topicid !== '' )
				$req .= " and a.id_topic IN (".$this->topicid.")";

			$req .= " and fi.idschi=a.idfai and fi.iduser='".$userid."' and fi.result='' and  fi.notified='Y'";
			$req .=  "order by a.date desc";

			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->IdEntries[] = $arr['id'];
				}

			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_ARTICLES_TBL." where id IN (".implode(',', $this->IdEntries).") order by date desc");
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		else
			$this->count = 0;

		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->ctx->curctx->push('ArticleHead', bab_replace($arr['head']));
			$this->ctx->curctx->push('ArticleBody', bab_replace($arr['body']));
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=waiting&idx=Confirm&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=waiting&idx=viewa&article=".$arr['id']."&topics=".$arr['id_topic']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_WaitingComments extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;
	var $articleid;

	function bab_WaitingComments($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);

		$userid = $ctx->get_value('userid');
		if( $userid === false || $userid === '' )
			$userid = $GLOBALS['BAB_SESS_USERID'];

		if( $userid != '')
			{
			//include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

			$this->articleid = $ctx->get_value('articleid');
			$req = "select c.id from ".BAB_COMMENTS_TBL." c join ".BAB_FAR_INSTANCES_TBL." fi where c.confirmed='N'";
			if( $this->articleid !== false && $this->articleid !== '' )
				$req .= " and c.id_article IN (".$this->articleid.")";

			$req .= " and fi.idschi=c.idfai and fi.iduser='".$userid."' and fi.result='' and  fi.notified='Y'";

			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->IdEntries[] = $arr['id'];
				}
			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id IN (".implode(',', $this->IdEntries).")");
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		else
			$this->count = 0;

		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CommentTitle', $arr['subject']);
			$this->ctx->curctx->push('CommentText', $arr['message']);
			$this->ctx->curctx->push('CommentId', $arr['id']);
			$this->ctx->curctx->push('CommentTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('CommentArticleId', $arr['id_article']);
			$this->ctx->curctx->push('CommentDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('CommentAuthor', $arr['name']);
			$this->ctx->curctx->push('CommentLanguage', $arr['lang']);
			$this->ctx->curctx->push('CommentUrl', $GLOBALS['babUrlScript']."?tg=waiting&idx=ReadC&com=".$arr['id']."&topics=".$arr['id_topic']."&article=".$arr['id_article']);
			$this->ctx->curctx->push('CommentPopupUrl', $GLOBALS['babUrlScript']."?tg=waiting&idx=viewc&com=".$arr['id']."&article=".$arr['id_article']."&topics=".$arr['id_topic']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_WaitingFiles extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;
	var $folderid;

	function bab_WaitingFiles($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);

		$userid = $ctx->get_value('userid');
		if( $userid === false || $userid === '' )
			$userid = $GLOBALS['BAB_SESS_USERID'];

		if( $userid != '')
			{
			//include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

			$this->folderid = $ctx->get_value('folderid');
			$req = "select f.id from ".BAB_FILES_TBL." f join ".BAB_FAR_INSTANCES_TBL." fi where f.bgroup='Y' and f.confirmed='N'";
			if( $this->folderid !== false && $this->folderid !== '' )
				$req .= " and f.id_owner IN (".$this->folderid.")";

			$req .= " and fi.idschi=f.idfai and fi.iduser='".$userid."' and fi.result='' and  fi.notified='Y'";

			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->IdEntries[] = $arr['id'];
				}
			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id IN (".implode(',', $this->IdEntries).")");
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		else
			$this->count = 0;

		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileId', $arr['id']);
			$this->ctx->curctx->push('FileName', $arr['name']);
			$this->ctx->curctx->push('FilePath', $arr['path']);
			$this->ctx->curctx->push('FileDescription', $arr['description']);
			$this->ctx->curctx->push('FileAuthor', $arr['author']);
			$this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path']));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileFolderId', $arr['id_owner']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_WaitingPosts extends bab_handler
{
	var $res;
	var $index;
	var $count;
	var $topicid;

	function bab_WaitingPosts($ctx)
		{
		global $babBody, $babDB;
		$this->bab_handler($ctx);

		$userid = $ctx->get_value('userid');
		if( $userid === false || $userid === '' )
			$userid = $GLOBALS['BAB_SESS_USERID'];

		if( $userid != '')
			{
			$this->forumid = $ctx->get_value('forumid');
			$req = "SELECT p.*, t.forum  FROM  ".BAB_POSTS_TBL." p, ".BAB_FORUMS_TBL." f, ".BAB_THREADS_TBL." t WHERE p.confirmed ='N' AND t.forum = f.id AND t.id = p.id_thread and f.moderator='".$userid."'";

			if( $this->forumid !== false && $this->forumid !== '' )
				$req .= " and f.id IN (".$this->forumid.")";

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}
		else
			$this->count = 0;

		$this->ctx->curctx->push('CCount', $this->count);
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->ctx->curctx->push('PostText', bab_replace($arr['message']));
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $arr['forum']);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['forum']."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$arr['forum']."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
			}
		else
			{
			$this->idx = 0;
			return false;
			}
		}
}


class bab_Faqs extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_Faqs( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$faqid = $ctx->get_value('faqid');
		$req = "select id from ".BAB_FAQCAT_TBL;
		if( $faqid !== false && $faqid !== '' )
			$req .= " where id IN (".$faqid.")";

		$res = $babDB->db_query($req);
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
				{
				array_push($this->IdEntries, $row['id']);
				}
			}

		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id IN (".implode(',', $this->IdEntries).")");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqName', $arr['category']);
			$this->ctx->curctx->push('FaqDescription', $arr['description']);
			$this->ctx->curctx->push('FaqId', $arr['id']);
			$this->ctx->curctx->push('FaqLanguage', $arr['lang']);
			$this->ctx->curctx->push('FaqUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}

class bab_Faq extends bab_handler
{
	var $index;
	var $count;
	var $res;

	function bab_Faq( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$ctx->get_value('faqid')."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqName', $arr['category']);
			$this->ctx->curctx->push('FaqDescription', $arr['description']);
			$this->ctx->curctx->push('FaqId', $arr['id']);
			$this->ctx->curctx->push('FaqLanguage', $arr['lang']);
			$this->ctx->curctx->push('FaqUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}



class bab_FaqPrevious extends bab_Faq
{
	var $handler;

	function bab_FaqPrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Faqs');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('faqid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_Faq($ctx);
	}

}

class bab_FaqNext extends bab_Faq
{
	var $handler;

	function bab_FaqNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_Faqs');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('faqid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_Faq($ctx);
	}

}




class bab_FaqQuestions extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_FaqQuestions( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$faqid = $ctx->get_value('faqid');
		$req = "select id, idcat from ".BAB_FAQQR_TBL;
		if( $faqid !== false && $faqid !== '' )
			$req .= " where idcat IN (".$faqid.")";

		$res = $babDB->db_query($req);
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['idcat']))
				{
				array_push($this->IdEntries, $row['id']);
				}
			}

		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id IN (".implode(',', $this->IdEntries).")");
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqQuestion', $arr['question']);
			$this->ctx->curctx->push('FaqResponse', bab_replace($arr['response']));
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_FaqQuestion extends bab_handler
{
	var $index;
	var $count;
	var $res;

	function bab_FaqQuestion( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id='".$ctx->get_value('questionid')."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqQuestion', $arr['question']);
			$this->ctx->curctx->push('FaqResponse', bab_replace($arr['response']));
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".$arr['id']);
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_FaqQuestionPrevious extends bab_FaqQuestion
{
	var $handler;

	function bab_FaqQuestionPrevious( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_FaqQuestions');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('questionid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		$this->bab_FaqQuestion($ctx);
	}

}

class bab_FaqQuestionNext extends bab_FaqQuestion
{
	var $handler;

	function bab_FaqQuestionNext( &$ctx)
	{
		$this->handler = $ctx->get_handler('bab_FaqQuestions');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('questionid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		$this->bab_FaqQuestion($ctx);
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
			{
			return $this->variables[$var];
			}
		else
			{
			return false;
			}
	}
}


class babOvTemplate
{
var $contexts = array();
var $handlers = array();
var $curctx;
var $gctx; /* global context */

function babOvTemplate($args = array())
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
	foreach($args as $variable => $contents)
		{
		$this->gctx->push($variable, stripslashes($contents));
		}
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
		$tmp = array_pop($this->contexts);
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

function push_handler(&$handler)
	{
	$this->handlers[] = &$handler;
	}

function pop_handler()
	{
	if( count($this->handlers) > 0 )
		{
		$tmp = array_pop($this->handlers);
		}
	}

function get_handler($name)
	{
	for( $i = count($this->handlers)-1; $i >= 0; $i--)
		{
		if( get_class($this->handlers[$i]) == strtolower($name) )
			{
			return $this->handlers[$i];
			}
		}
	return false;
	}

function handle_tag( $handler, $txt, $txt2 )
	{
	$out = '';
	$handler = "bab_".$handler;
	if( class_exists($handler))
		{
		$ctx = new bab_context($handler);
		$this->push_ctx($ctx);
		if(preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $txt2, $mm))
			{
			for( $j = 0; $j< count($mm[1]); $j++)
				{
				$this->curctx->push($mm[1][$j], $mm[3][$j]);
				}
			}
		$cls = new $handler($this);
		$out = $cls->printout($txt);
		$this->pop_ctx();
		return $out;
		}
	else
		return $txt;
	}

function format_output($val, $matches)
	{
	$saveas = false;

	for( $j = 0; $j< count($matches[1]); $j++)
		{
		switch(strtolower(trim($matches[1][$j])))
			{
			case 'strlen':
				$arr = explode(',', $matches[3][$j] );
				if( strlen($val) > $arr[0] )
					$val = substr($val, 0, $matches[3][$j]).$arr[1];
				break;
			case 'striptags':
				if( $matches[3][$j] == '1')
					$val = strip_tags($val);
				break;
			case 'htmlentities':
				switch($matches[3][$j])
					{
					case '1':
						$val = htmlentities($val); break;
					case '2':
						$trans = get_html_translation_table(HTML_ENTITIES);
						$trans = array_flip($trans);
						$val = strtr($val, $trans);
						break;
					}
				break;
			case 'stripslashes':
				if( $matches[3][$j] == '1')
					$val = stripslashes($val);
				break;
			case 'urlencode':
				if( $matches[3][$j] == '1')
					$val = urlencode($val);
				break;
			case 'jsencode':
				if( $matches[3][$j] == '1')
					{
					$val = str_replace("'", "\'", $val);
					$val = str_replace('"', "'+String.fromCharCode(34)+'",$val);
					}
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
			case 'nlremove':
				if( $matches[3][$j] == '1')
					$val = preg_replace("(\r\n|\n|\r)", "", $val);
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
			case 'saveas':
				$varname = $matches[3][$j];
				$saveas = true;
				break;
			}
		}

	if( $saveas )
		$this->gctx->push($varname, $val);
	return $val;
	}

function vars_replace($txt)
	{
	if(preg_match_all("/<".BAB_TAG_FUNCTION."([^\s>]*)\s*(\w+\s*=\s*[\"].*?\")*\s*>/", $txt, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			$handler = "bab_".$m[1][$i];
			$val = $this->$handler($this->vars_replace(trim($m[2][$i])));
			$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", $val, $txt);
			}
		}

	if(preg_match_all("/<".BAB_TAG_VARIABLE."([^\s>]*)\s*(\w+\s*=\s*[\"].*?\")*\s*>/", $txt, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			$val = $this->get_value($m[1][$i]);
			$args = $this->vars_replace(trim($m[2][$i]));
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
	
	return $txt;
	}

function handle_text($txt)
	{
	if(preg_match_all("/(.*?)<".BAB_TAG_CONTAINER."([^\s]*)\s*(\w+\s*=\s*[\"].*?\")*\s*(\w*)\s*>(.*?)<\/".BAB_TAG_CONTAINER."\\2\s*\\4\s*>(.*)/s", $txt, $m))
		{
		$out = '';
		for( $i = 0; $i< count($m[3]); $i++)
			{
			$out .= $this->handle_text($m[1][$i]);
			$out .= $this->handle_tag($m[2][$i], $m[5][$i], $this->vars_replace($m[3][$i]));
			$out .= $this->handle_text($m[6][$i]);
			}
		return $out;
		}
	else
		{
		$out = $this->vars_replace($txt);
		return $out;
		}
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
	$global = true;

	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'name':
					$name = $mm[3][$j];
					break;
				case 'value':
					$value = $mm[3][$j];
					$global = false;
					break;
				}
			}					
		if( $global )
			$value = $GLOBALS[$name];
		$this->gctx->push($name, $value);
		}
	}

/* save a variable to global space if not already defined */
function bab_IfNotIsSet($args)
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
					$name = $mm[3][$j];
					break;
				case 'value':
					$value = $mm[3][$j];
					break;
				}
			}

		if( $this->gctx->get($name) === false )
			{
			$this->gctx->push($name, $value);
			}
		}
	}

/* Arithmetic operators */
function bab_AOAddition($args)
	{
	return $this->bab_ArithmeticOperator($args, '+');
	}

/* Arithmetic operators */
function bab_AOSubtraction($args)
	{
	return $this->bab_ArithmeticOperator($args, '-');
	}
/* Arithmetic operators */
function bab_AOMultiplication($args)
	{
	return $this->bab_ArithmeticOperator($args, '*');
	}
/* Arithmetic operators */
function bab_AODivision($args)
	{
	return $this->bab_ArithmeticOperator($args, '/');
	}
/* Arithmetic operators */
function bab_AOModulus($args)
	{
	return $this->bab_ArithmeticOperator($args, '%');
	}

/* Arithmetic operators */
function bab_ArithmeticOperator($args, $ope)
	{
	$expr1 = "";
	$expr2 = "";
	$saveas = true;

	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'expr1':
					$expr1 = $mm[3][$j];
					break;
				case 'expr2':
					$expr2 = $mm[3][$j];
					break;
				case 'saveas':
					$saveas = true;
					$varname = $mm[3][$j];
					break;
				}
			}
		switch($ope)
			{
			case '+': $val = $expr1 + $expr2; break;
			case '-': $val = $expr1 - $expr2; break;
			case '*': $val = $expr1 * $expr2; break;
			case '/': $val = $expr1 / $expr2; break;
			case '%': $val = $expr1 % $expr2; break;
			}

		if( $saveas )
			$this->gctx->push($varname, $val);
		else
			return $val;
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
	return $this->handle_text($txt);
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
?>