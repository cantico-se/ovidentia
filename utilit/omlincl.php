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

class bab_IfIsSet extends bab_handler
{
	var $count;

	function bab_IfIsSet( &$ctx)
	{
		$this->count = 0;
		$this->bab_handler($ctx);
		$name = $ctx->get_value('name');
		if( $name !== false && !empty($name))
			{
			if( $ctx->get_value($name) !== false )
				{
				$this->count = 1;
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

class bab_IfNotIsSet extends bab_handler
{
	var $count;

	function bab_IfNotIsSet( &$ctx)
	{
		$this->count = 0;
		$this->bab_handler($ctx);
		$name = $ctx->get_value('name');
		if( $name !== false && !empty($name))
			{
			if( $ctx->get_value($name) === false )
				{
				$this->count = 1;
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


class bab_Addon extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_Addon( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$name = $ctx->get_value('name');
		$addonid = 0;
		foreach ($babBody->babaddons as $value)
			{
			if ($value['title'] == $name)
				{
				$addonid = $value['id'];
				break;
				}
			}

		if( $addonid && isset($babBody->babaddons[$addonid]) && $babBody->babaddons[$addonid]['access'] )
			{
			$addonpath = $GLOBALS['babAddonsPath'].$babBody->babaddons[$addonid]['title'];
			if( is_file($addonpath."/ovml.php" ))
				{
				/* save old vars */
				$this->AddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
				$this->AddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
				$this->AddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
				$this->AddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
				$this->AddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
				$this->AddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

				$GLOBALS['babAddonFolder'] = $babBody->babaddons[$addonid]['title'];
				$GLOBALS['babAddonTarget'] = "addon/".$addonid;
				$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$addonid."/";
				$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$babBody->babaddons[$addonid]['title']."/";
				$GLOBALS['babAddonHtmlPath'] = "addons/".$babBody->babaddons[$addonid]['title']."/";
				$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$babBody->babaddons[$addonid]['title']."/";
				require_once( $addonpath."/ovml.php" );

				$call = $babBody->babaddons[$addonid]['title']."_ovml";
				if( !empty($call)  && function_exists($call) )
					{
					$args = $ctx->get_variables('bab_Addon');
					$this->IdEntries = $call($args);
					}
				}
			}
		$this->count = count( $this->IdEntries );
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			foreach($this->IdEntries[$this->idx] as $name => $val)
				{
				$this->ctx->curctx->push($name, $val);
				}
			$this->idx++;
			$this->index = $this->idx;
			return true;
		}
		else
		{
			if( isset($this->AddonFolder))
			{
			$GLOBALS['babAddonFolder'] = $this->AddonFolder;
			$GLOBALS['babAddonTarget'] = $this->AddonTarget;
			$GLOBALS['babAddonUrl'] = $this->AddonUrl;
			$GLOBALS['babAddonPhpPath'] = $this->AddonPhpPath;
			$GLOBALS['babAddonHtmlPath'] = $this->AddonHtmlPath;
			$GLOBALS['babAddonUpload'] = $this->AddonUpload;
			}
			$this->idx=0;
			return false;
		}
	}
}


class bab_ArticlesHomePages extends bab_handler
{
	var $IdEntries = array();
	var $arrid = array();
	var $index;
	var $count;
	var $idgroup;

	function bab_ArticlesHomePages( &$ctx)
	{
		global $babBody, $babDB;

		$this->bab_handler($ctx);
		$arr['id'] = $babBody->babsite['id'];
		$this->idgroup = $ctx->get_value('type');
		$order = $ctx->get_value('order');
		if( $order === false || $order === '' )
			$order = "asc";

		switch(strtoupper($order))
		{
			case "DESC": $order = "ht.ordering DESC"; break;
			case "RAND": $order = "rand()"; break;
			case "ASC":
			default: $order = "ht.ordering"; break;
		}

		switch(strtolower($this->idgroup))
			{
			case "public":
				$this->idgroup = 2; // non registered users
				break;
			case "private":
			default:
				if( $GLOBALS['BAB_SESS_LOGGED'])
					{
					$this->idgroup = 1; // registered users
					}
				else
					{
					$this->idgroup = 2; // non registered users
					}
				break;
			}
	
		$filter = $ctx->get_value('filter');

		if (($filter == "")||(strtoupper($filter) == "NO")) 
			$filter = false;
		else
			$filter = true;

		$res = $babDB->db_query("select ht.id, at.id_topic, at.restriction from ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_HOMEPAGES_TBL." ht on ht.id_article=at.id where ht.id_group='".$this->idgroup."' and ht.id_site='".$arr['id']."' and ht.ordering!='0' GROUP BY at.id order by ".$order);
		while($arr = $babDB->db_fetch_array($res))
		{
			if( $arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']) )
				{
				if( $filter == false || isset($babBody->topview[$arr['id_topic']]))
					{
					$this->IdEntries[] = $arr['id'];
					}
				}
		}

		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_HOMEPAGES_TBL." ht on ht.id_article=at.id left join ".BAB_ART_FILES_TBL." aft on aft.id_article=at.id where ht.id IN (".implode(',', $this->IdEntries).") group by at.id order by ".$order);
			}

		$this->count = isset($this->res) ? $babDB->db_num_rows($this->res) : 0;
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
			bab_replace_ref($arr['head'],'OVML');
			bab_replace_ref($arr['body'],'OVML');
			$this->ctx->curctx->push('ArticleHead', $arr['head']);
			$this->ctx->curctx->push('ArticleBody', $arr['body']);
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id']."&idg=".$this->idgroup);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date_publication'] == $arr['date_modification'])
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
			else
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);			
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
			$parentid = array_intersect(array_keys($babBody->get_topcatview()), explode(',', $parentid));

		if( count($parentid) > 0 )
		{
		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent IN (".implode(',', $parentid).")");
		$topcatview = $babBody->get_topcatview();
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($topcatview[$row['id']]) )
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
			$req = "select tc.* from ".BAB_TOPICS_CATEGORIES_TBL." tc left join ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat where tc.id IN (".implode(',', $this->IdEntries).") and tot.type='1' order by tot.ordering asc";
			$this->res = $babDB->db_query($req);
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
			$topcats = $babBody->get_topcats();
			while( $topcats[$categoryid]['parent'] != 0 )
				{
				$this->IdEntries[] = $topcats[$categoryid]['parent'];
				$categoryid = $topcats[$categoryid]['parent'];
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
			$catid = array_keys($babBody->get_topcatview());
		else
			$catid = array_intersect(array_keys($babBody->get_topcatview()), explode(',', $catid));
		
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
			$catid = array_keys($babBody->get_topcatview());
		else
			$catid = array_intersect(array_keys($babBody->get_topcatview()), explode(',', $catid));

		if( count($catid) > 0 )
		{
		$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where type='2' and id_parent IN (".implode(',', $catid).") order by ordering asc";

		$res = $babDB->db_query($req);
		while( $row = $babDB->db_fetch_array($res))
			{
			if(isset($babBody->topview[$row['id_topcat']]))
				{
				array_push($this->IdEntries, $row['id_topcat']);
				}
			}
		}
		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$req = "select tc.* from ".BAB_TOPICS_TBL." tc left join ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat where tc.id IN (".implode(',', $this->IdEntries).") and tot.type='2' order by tot.ordering asc";
			$this->res = $babDB->db_query($req);
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
			$this->IdEntries = array_keys($babBody->topview);
		else
			$this->IdEntries = array_values(array_intersect(array_keys($babBody->topview), explode(',', $this->topicid)));
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
			$topicid = array_keys($babBody->topview);
		else
			$topicid = array_intersect(array_keys($babBody->topview), explode(',', $topicid));

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

			$req = "select at.id, at.restriction from ".BAB_ARTICLES_TBL." at where at.id_topic IN (".implode(',', $topicid).") and (at.date_publication='0000-00-00 00:00:00' or at.date_publication <= now())".$archive;

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "asc";

			$forder = $ctx->get_value('topicorder');
			switch(strtoupper($forder))
			{
				case 'YES': $forder = true; break;
				case 'NO': /* no break */
				default: $forder = false; break;

			}

			switch(strtoupper($order))
			{
				case "ASC":
					if( $forder )
					{
						$order = "at.ordering asc, at.date_modification desc"; 
					}
					else
					{
						$order = "at.date asc";
					}
					break;
				case "RAND": 
					$order = "rand()"; 
					break;
				case "DESC":
				default:
					if( $forder )
					{
						$order = "at.ordering desc, at.date_modification asc"; 
					}
					else
					{
						$order = "at.date desc";
					}
					break;

			}

			$req .= " order by ".$order;

			$rows = $ctx->get_value('rows');
			$offset = $ctx->get_value('offset');
			if( $rows === false || $rows === '')
				$rows = "-1";

			if( $offset === false || $offset === '')
				$offset = "0";

			if ($rows != -1)
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
			if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id IN (".implode(',', $this->IdEntries).") group by at.id order by ".$order);
			}
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
			bab_replace_ref($arr['head'],'OVML');
			bab_replace_ref($arr['body'],'OVML');
			$this->ctx->curctx->push('ArticleHead', $arr['head']);
			$this->ctx->curctx->push('ArticleBody', $arr['body']);
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date_publication'] == $arr['date_modification'])
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
			else
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
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
			$res = $babDB->db_query("select id, id_topic, restriction from ".BAB_ARTICLES_TBL." where id IN (".$articleid.") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())");
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
				$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id IN (".implode(',', $this->IdEntries).") group by at.id");
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
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			bab_replace_ref($arr['head'],'OVML');
			bab_replace_ref($arr['body'],'OVML');
			$this->ctx->curctx->push('ArticleHead', $arr['head']);
			$this->ctx->curctx->push('ArticleBody', $arr['body']);
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date_publication'] == $arr['date_modification'])
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
			else
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
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


class bab_ArticleFiles extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	function bab_ArticleFiles( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$articleid = $ctx->get_value('articleid');
		if( $articleid === false || $articleid === '' )
			$this->count = 0;
		else
		{
			$res = $babDB->db_query("select id, id_topic, restriction from ".BAB_ARTICLES_TBL." where id IN (".$articleid.") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())");
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
				$this->res = $babDB->db_query("select aft.*, at.id_topic from ".BAB_ART_FILES_TBL." aft left join ".BAB_ARTICLES_TBL." at on aft.id_article=at.id where aft.id_article IN (".implode(',', $this->IdEntries).")");
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
			$this->ctx->curctx->push('ArticleFileName', $arr['name']);
			$this->ctx->curctx->push('ArticleFileDescription', $arr['description']);
			$this->ctx->curctx->push('ArticleFileUrlGet', $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$arr['id_topic']."&idf=".$arr['id']);
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
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y' order by ordering asc");
		else
			{
			$forumid = explode(',', $forumid);
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y' and id IN (".implode(',', $forumid).") order by ordering asc");
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
			$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where active='Y' and id IN (".implode(',', $this->IdEntries).") order by ordering asc");
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
		$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where id='".$ctx->get_value('forumid')."' and active='Y'");
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
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y' and p.id IN (".implode(',', $arr).") AND p.confirmed =  'Y'";			
			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				{
				$order = "asc";
				}

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
			bab_replace_ref($arr['message'],'OVML');
			$this->ctx->curctx->push('PostText', $arr['message']);
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
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


class bab_PostFiles extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	function bab_PostFiles( &$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$postid = $ctx->get_value('postid');
		if( $postid === false || $postid === '' )
			$this->count = 0;
		else
		{
			$baseurl = $GLOBALS['babUploadPath'].'/forums/';
			if (is_dir($baseurl) && $h = opendir($baseurl))
				{
				$req = "SELECT t.forum FROM ".BAB_THREADS_TBL." t,".BAB_POSTS_TBL." p WHERE t.id = p.id_thread AND p.id='".$postid."'";
				list($forum) = $babDB->db_fetch_array($babDB->db_query($req));

				$this->arr = array();
				while (false !== ($file = readdir($h))) 
					{
					if (substr($file,0,strpos($file,',')) == $postid)
						{
						$name = substr($file,strstr(',',$file)+2);
						$this->arr[] = array(
								'url' => $GLOBALS['babUrlScript']."?tg=posts&idx=dlfile&forum=".$forum."&post=".$postid."&file=".urlencode($name),
								'name' => $name
								);
						}
					}
				$this->count = count($this->arr);
				}
		}
		
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostFileName', $this->arr[$this->idx]['name']);
			$this->ctx->curctx->push('PostFileUrlGet', $this->arr[$this->idx]['url']);
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
			$req = "select tt.id, tt.forum from ".BAB_THREADS_TBL." tt left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum WHERE ft.active='Y' and tt.id IN (".implode(',', $arr).") and tt.active='Y'";

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
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
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
			if ($rows != "-1")
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
			$this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));
			$this->ctx->curctx->push('FileAuthor', $arr['author']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path']));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$fullpath = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']);
			if (file_exists($fullpath.$arr['path']."/".$arr['name']) )
				$this->ctx->curctx->push('FileSize',bab_formatSizeFile(filesize($fullpath.$arr['path']."/".$arr['name'])) );
			else
				$this->ctx->curctx->push('FileSize', '???');
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
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";

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
			$this->ctx->curctx->push('FileDate', bab_mktime($this->arr['modified']));
			$this->ctx->curctx->push('FileAuthor', $this->arr['author']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path']));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$this->arr['id']."&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path'])."&file=".urlencode($this->arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path'])."&file=".urlencode($this->arr['name']));
			$fullpath = bab_getUploadFullPath($arr['bgroup'], $this->arr['id_owner']);
			if (file_exists($fullpath.$this->arr['path']."/".$this->arr['name']) )
				$this->ctx->curctx->push('FileSize',bab_formatSizeFile(filesize($fullpath.$this->arr['path']."/".$this->arr['name'])) );
			else
				$this->ctx->curctx->push('FileSize', '???');
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
		$this->topcatid = $ctx->get_value('categoryid');
		
		if ( $this->topcatid === false || $this->topcatid === '' )
			{
			if( $this->topicid === false || $this->topicid === '' )
				$this->topicid = array_keys($babBody->topview);
			else
				$this->topicid = array_intersect(array_keys($babBody->topview), explode(',', $this->topicid));
			}
		else
			{
			$this->topicid = array();
			$this->gettopics($this->topcatid);
			}

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

			$req = "select id, restriction from ".BAB_ARTICLES_TBL." where id_topic IN (".implode(',', $this->topicid).") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())";
			if( $this->nbdays !== false)
				$req .= " and date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

			$req .= $archive;

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "desc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "date_modification ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "date_modification DESC"; break;
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
				$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id IN (".implode(',', $this->IdEntries).") group by at.id order by ".$order);
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		else
			{
			$this->count = 0;
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}


	function gettopics($idparent)
		{
		$topcats = $GLOBALS['babBody']->get_topcats();
		foreach($topcats as $id => $arr) {
				if ($idparent == $arr['parent']) {
					$this->gettopics($id);
				}
			}

		$babDB = &$GLOBALS['babDB'];


		$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where id_cat='".$idparent."' AND id IN('".implode("','",array_keys($GLOBALS['babBody']->topview))."')");
		while( $row = $babDB->db_fetch_array($res))
			{
			$this->topicid[] = $row['id'];
			}
		}

	function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			bab_replace_ref($arr['head'],'OVML');
			bab_replace_ref($arr['body'],'OVML');
			$this->ctx->curctx->push('ArticleHead', $arr['head']);
			$this->ctx->curctx->push('ArticleBody', $arr['body']);
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date_publication'] == $arr['date_modification'])
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
			else
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
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
		if( count($babBody->topview) > 0 )
			{
			if( count($arrid) > 0 )
				{
				$req = "select * from ".BAB_COMMENTS_TBL." where id_article IN (".implode(',', $arrid).") and confirmed='Y' and id_topic IN (".implode(',', array_keys($babBody->topview)).")";
				}
			else
				{
				$req = "select * from ".BAB_COMMENTS_TBL." where confirmed='Y' and id_topic IN (".implode(',', array_keys($babBody->topview)).")";
				}
			}
		
		if( $req != '' )
			{
			if( $this->nbdays !== false)
				$req .= " and date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

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
		$access = array_keys(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL));

		if( $this->forumid === false || $this->forumid === '' )
			{
			$arr = $access;
			}
		else
			{
			$arr = explode(',', $this->forumid);
			$arr = array_intersect($arr, $access);
			}

		if( count($arr) > 0 )
			{
			$req = "SELECT p.*, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y' and t.forum IN (".implode(',', $arr).") and p.confirmed='Y'";	

			if( $this->nbdays !== false)
				$req .= " and p.date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";


			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "desc";

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

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
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
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			bab_replace_ref($arr['message'],'OVML');
			$this->ctx->curctx->push('PostText', $arr['message']);
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $arr['id_forum']);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['id_forum']."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$arr['id_forum']."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
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
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y' and t.forum IN (".implode(',', $arr).") and p.confirmed='Y' and p.id_parent='0'";			
			}
		else
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y' and p.confirmed='Y' and p.id_parent='0'";			
			}

		if( $this->nbdays !== false)
			$req .= " and p.date >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

		$order = $ctx->get_value('order');
		if( $order === false || $order === '' )
			$order = "desc";

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
			bab_replace_ref($arr['message'],'OVML');
			$this->ctx->curctx->push('PostText', $arr['message']);
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
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
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
			$req = "select f.* from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.state='' and f.confirmed='Y'";
			$path = $ctx->get_value('path');
			if( $path === false || $path === '' )
				{
				$path = '';
				}
			if( $path != '' )
				{
				$req .= " and f.path='".addslashes($path)."'";
				}

			$req .= " and f.id_owner IN (".implode(',', $arrid).")";

			if( $this->nbdays !== false)
				$req .= " and f.modified >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "desc";

			switch(strtoupper($order))
			{
				case "ASC": $order = "f.modified ASC"; break;
				case "RAND": $order = "rand()"; break;
				case "DESC":
				default: $order = "f.modified DESC"; break;
			}

			$req .= " group by f.id";

			$req .= " order by ".$order;

			
			
			
			if( $this->last !== false)
				$req .= " limit 0, ".$this->last;
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}
		else
			$this->count = 0;

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
			$this->ctx->curctx->push('FileModifiedBy', $arr['modifiedby']);
			$this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));
			$fullpath = bab_getUploadFullPath($arr['bgroup'], $arr['id_owner']);
			if (file_exists($fullpath.$arr['path']."/".$arr['name']) )
				$this->ctx->curctx->push('FileSize',bab_formatSizeFile(filesize($fullpath.$arr['path']."/".$arr['name'])) );
			else
				$this->ctx->curctx->push('FileSize', '???');
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
			{
			$userid = $GLOBALS['BAB_SESS_USERID'];
			}

		if( $userid != '')
			{
			$this->topicid = $ctx->get_value('topicid');
			$req = "select adt.id, adt.id_topic from ".BAB_ART_DRAFTS_TBL." adt where adt.result='".BAB_ART_STATUS_WAIT."'";
			if( $this->topicid !== false && $this->topicid !== '' )
				$req .= " and adt.id_topic IN (".$this->topicid.")";

			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$waitart = bab_getWaitingArticles($arr['id_topic']);
				if( count($waitart) > 0  && in_array( $arr['id'], $waitart))
					{
					$this->IdEntries[] = $arr['id'];
					}
				}

			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select adt.*, count(adft.id) as nfiles from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adt.id=adft.id_draft where adt.id IN (".implode(',', $this->IdEntries).") group by adt.id order by adt.date_submission desc");
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
			bab_replace_ref($arr['head'],'OVML');
			bab_replace_ref($arr['body'],'OVML');
			$this->ctx->curctx->push('ArticleHead', $arr['head']);
			$this->ctx->curctx->push('ArticleBody', $arr['body']);
			if( empty($arr['body']))
				{
				$this->ctx->curctx->push('ArticleReadMore', 0);
				}
			else
				{
				$this->ctx->curctx->push('ArticleReadMore', 1);
				}
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date_submission']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=approb");
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=approb&idx=viewart&idart=".$arr['id']."&topics=".$arr['id_topic']);
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
			$this->articleid = $ctx->get_value('articleid');
			$req = "select c.id, c.id_topic from ".BAB_COMMENTS_TBL." c where c.confirmed='N'";
			if( $this->articleid !== false && $this->articleid !== '' )
				$req .= " and c.id_article IN (".$this->articleid.")";

			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$waitcom = bab_getWaitingComments($arr['id_topic']);
				if( count($waitcom) > 0 && in_array( $arr['id'], $waitcom))
					{
					$this->IdEntries[] = $arr['id'];
					}
				}
			$this->count = count($this->IdEntries);
			if( $this->count > 0 )
				{
				$this->res = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id IN (".implode(',', $this->IdEntries).") order by date desc");
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
			$this->ctx->curctx->push('CommentUrl', $GLOBALS['babUrlScript']."?tg=approb");
			$this->ctx->curctx->push('CommentPopupUrl', $GLOBALS['babUrlScript']."?tg=approb&idx=viewcom&idcom=".$arr['id']."&idart=".$arr['id_article']."&topics=".$arr['id_topic']);
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
			$this->folderid = $ctx->get_value('folderid');
			$req = "select f.id, f.idfai from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.confirmed='N'";
			if( $this->folderid !== false && $this->folderid !== '' )
				$req .= " and f.id_owner IN (".$this->folderid.")";

			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				$res = $babDB->db_query($req);
				while( $arr = $babDB->db_fetch_array($res))
					{
					if(in_array( $arr['idfai'], $arrschi))
						{
						$this->IdEntries[] = $arr['id'];
						}
					}

				$this->count = count($this->IdEntries);
				if( $this->count > 0 )
					{
					$this->res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id IN (".implode(',', $this->IdEntries).")");
					$this->count = $babDB->db_num_rows($this->res);
					}
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
			{
			$userid = 0;
			}

		$req = "select id from ".BAB_FORUMS_TBL." where active='Y'";
		$this->forumid = $ctx->get_value('forumid');
		if( $this->forumid !== false && $this->forumid !== '' )
			{
			$req .= " and id IN (".$this->forumid.")";
			}

		$res = $babDB->db_query($req);
		$arrf = array();
		while($arr = $babDB->db_fetch_array($res))
			{
			if( $userid == 0 && bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $arr['id']))
				{
				$arrf[] = $arr['id'];
				}
			elseif( $userid != 0 && bab_isAccessValidByUser(BAB_FORUMSMAN_GROUPS_TBL, $arr['id'], $userid))
				{
				$arrf[] = $arr['id'];
				}
			}

		if( count($arrf) > 0)
			{
			$req = "SELECT p.*, t.forum  FROM  ".BAB_POSTS_TBL." p, ".BAB_FORUMS_TBL." f, ".BAB_THREADS_TBL." t WHERE p.confirmed ='N' AND t.forum = f.id AND t.id = p.id_thread and f.id IN (".implode(',', $arrf).")";

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
			bab_replace_ref($arr['message'],'OVML');
			$this->ctx->curctx->push('PostText', $arr['message']);
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

class bab_FaqSubCategories extends bab_handler
{
	var $res;
	var $index;
	var $count;
	var $faqinfo;

	function bab_FaqSubCategories( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		$faqid = $ctx->get_value('faqid');
		if( $faqid != '')
			{
			if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $faqid))
				{
				$this->faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$faqid."'"));
				$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$faqid."'");
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
			if( $this->faqinfo['id_root'] == $arr['id'] )
			{
			$this->ctx->curctx->push('FaqSubCatName', $faqinfo['category']);
			}
			else
			{
			$this->ctx->curctx->push('FaqSubCatName', $arr['name']);
			}
			$this->ctx->curctx->push('FaqId', $arr['id_cat']);
			$this->ctx->curctx->push('FaqSubCatId', $arr['id']);
			$this->ctx->curctx->push('FaqSubCatUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['id_cat']."&idscat=".$arr['id']);
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

class bab_FaqSubCategory extends bab_handler
{
	var $res;
	var $index;
	var $count;
	var $faqinfo;
	var $IdEntries = array();

	function bab_FaqSubCategory( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		$faqsubcatid = $ctx->get_value('faqsubcatid');
		if( $faqsubcatid !== false && $faqsubcatid !== '' )
		{
			$res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat IN (".$faqsubcatid.")");
			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id_cat']))
					{
					array_push($this->IdEntries, $row['id']);
					}
				}
		}

		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id IN (".implode(',', $this->IdEntries).")");
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
			if( empty($arr['name']) )
			{
			$faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$arr['id_cat']."'"));
			$this->ctx->curctx->push('FaqSubCatName', $faqinfo['category']);
			}
			else
			{
			$this->ctx->curctx->push('FaqSubCatName', $arr['name']);
			}
			$this->ctx->curctx->push('FaqId', $arr['id_cat']);
			$this->ctx->curctx->push('FaqSubCatId', $arr['id']);
			$this->ctx->curctx->push('FaqSubCatUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['id_cat']."&idscat=".$arr['id']);
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
		$faqsubcatid = $ctx->get_value('faqsubcatid');
		$req = "select id, idcat from ".BAB_FAQQR_TBL;
		if( $faqid !== false && $faqid !== '' )
			{
			$req .= " where idcat IN (".$faqid.")";
			if( $faqsubcatid !== false && $faqsubcatid !== '' )
				$req .= " and id_subcat IN (".$faqsubcatid.")";
			}
		elseif( $faqsubcatid !== false && $faqsubcatid !== '' )
			$req .= " where id_subcat IN (".$faqsubcatid.")";

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
			bab_replace_ref($arr['response'],'OVML');
			$this->ctx->curctx->push('FaqResponse', $arr['response']);
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
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
			bab_replace_ref($arr['response'],'OVML');
			$this->ctx->curctx->push('FaqResponse', $arr['response']);
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
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


class bab_Calendars extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_Calendars( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$type = $ctx->get_value('type');
		$babBody->icalendars->initializeCalendars();
		if( $type === false || $type === '' )
			{
			if( $babBody->icalendars->id_percal )
				{
				$this->IdEntries[] = $babBody->icalendars->id_percal;
				}

			reset($babBody->icalendars->usercal);
			while( $row=each($babBody->icalendars->usercal) ) 
				{ 
				$this->IdEntries[] = $row[0];
				}
			
			reset($babBody->icalendars->pubcal);
			while( $row=each($babBody->icalendars->pubcal) ) 
				{ 
				$this->IdEntries[] = $row[0];
				}

			reset($babBody->icalendars->rescal);
			while( $row=each($babBody->icalendars->rescal) ) 
				{ 
				$this->IdEntries[] = $row[0];
				}
			}
		else
			{
			$typename = strtolower($type);
			switch($typename)
				{
				case 'user': $type = BAB_CAL_USER_TYPE;	break;
				case 'group': $type = BAB_CAL_PUB_TYPE;	break;
				case 'resource': $type = BAB_CAL_RES_TYPE;	break;
				default: $type = ''; break;
				}
			switch($type)
				{
				case BAB_CAL_USER_TYPE:
					if( $babBody->icalendars->id_percal )
						{
						$this->IdEntries[] = $babBody->icalendars->id_percal;
						}

					reset($babBody->icalendars->usercal);
					while( $row=each($babBody->icalendars->usercal) ) 
						{ 
						$this->IdEntries[] = $row[0];
						}
					break;
				
				case BAB_CAL_PUB_TYPE:
					reset($babBody->icalendars->pubcal);
					while( $row=each($babBody->icalendars->pubcal) ) 
						{ 
						$this->IdEntries[] = $row[0];
						}
					break;

				case BAB_CAL_RES_TYPE:
					reset($babBody->icalendars->rescal);
					while( $row=each($babBody->icalendars->rescal) ) 
						{ 
						$this->IdEntries[] = $row[0];
						}
					break;
				}
			}


		$calendarid = $ctx->get_value('calendarid');
		if( $calendarid !== false && $calendarid !== '' )
			{
			$calendarid = explode(',',$calendarid);
			$this->IdEntries = array_intersect($this->IdEntries, $calendarid );
			}

		$this->count = count($this->IdEntries);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			$calendarid = current($this->IdEntries);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CalendarId', $calendarid);
			$iarr = $babBody->icalendars->getCalendarInfo($calendarid);
			$this->ctx->curctx->push('CalendarName', $iarr['name']);
			$this->ctx->curctx->push('CalendarDescription', $iarr['description']);
			$this->ctx->curctx->push('CalendarOwnerId', $iarr['idowner']);
			$this->ctx->curctx->push('CalendarType', $iarr['type']);
			$this->ctx->curctx->push('CalendarUrl', $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$this->IdEntries[$this->idx]);
			$this->idx++;
			$this->index = $this->idx;
			next($this->IdEntries);
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class bab_CalendarCategories extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_CalendarCategories( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$req = "select * from ".BAB_CAL_CATEGORIES_TBL." order by name asc";

		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CalendarCategoryId', $arr['id']);
			$this->ctx->curctx->push('CalendarCategoryName', $arr['name']);
			$this->ctx->curctx->push('CalendarCategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CalendarCategoryColor', $arr['bgcolor']);
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

class bab_CalendarUserEvents extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_CalendarUserEvents( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$userid = $ctx->get_value('userid');
		$calid = '';
		$babBody->icalendars->initializeCalendars();
		if( $userid === false || $userid === '' )
			{
			if( $babBody->icalendars->id_percal )
				{
				$calid = $babBody->icalendars->id_percal;
				}
			}
		else
			{
			$filter = $ctx->get_value('filter');

			if (strtoupper($filter) == "NO") 
				{
				$filter = false;
				}
			else
				{
				$filter = true;
				}

			$ar = array();
			$rr = explode(',', $userid);
			if( $filter )
				{
				if( $babBody->icalendars->id_percal && in_array($GLOBALS['BAB_SESS_USERID'], $rr))
					{
					$ar[] = $babBody->icalendars->id_percal;
					}

				reset($babBody->icalendars->usercal);
				while( $row=each($babBody->icalendars->usercal) ) 
					{
					if( in_array($row[1]['idowner'], $rr) )
						{
						$ar[] = $row[0];
						}
					}
				}
			elseif( !empty($userid))
				{
				$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner IN (".$userid.") and type='".BAB_CAL_USER_TYPE."' and actif='Y'");
				while( $arr = $babDB->db_fetch_array($res) )
					{
					$ar[] = $arr['id'];
					}
				}

			if( count($ar) > 0 )
				{
				$calid = implode(',', $ar);
				}
			else
				{
				$calid = '';
				}
			}

		$date = $ctx->get_value('date');
		if( $date === false || $date === '' )
			{
			$date = 'CURDATE()';
			}
		else
			{
			$date = "'".$date."'";
			}
		$limit = $ctx->get_value('limit');
		$lf = $lr = 0;

		if( $limit !== false && $limit !== '' )
			{
			$limit = explode(',', $limit);
			if( count($limit) > 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				$lr = empty($limit[1])?0:$limit[1];
				}
			elseif ( count($limit) == 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				}
			}

		if( !empty($calid))
		{
		$categoryid = $ctx->get_value('categoryid');
		if( $categoryid === false || $categoryid === '' )
			{
			$categoryid = '';
			}

		$req = "select cet.*, ceot.id_cal, rct.name, rct.bgcolor from ".BAB_CAL_EVENTS_OWNERS_TBL." ceot left join ".BAB_CAL_EVENTS_TBL." cet on ceot.id_event=cet.id left join ".BAB_CAL_CATEGORIES_TBL." rct on rct.id=cet.id_cat where ceot.id_cal IN (".$calid.") and ((start_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ) or (end_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ))";
		if( !empty($categoryid))
			{
			$req .= " and cet.id_cat IN (".$categoryid.")";
			}
		$req .= " order by start_date asc";
		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		}
		else
		{
		$this->count = 0;
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('EventTitle', $arr['title']);
			bab_replace_ref($arr['description'],'OVML');
			$this->ctx->curctx->push('EventDescription', $arr['description']);
			$this->ctx->curctx->push('EventLocation', $arr['location']);
			$this->ctx->curctx->push('EventBeginDate', bab_mktime($arr['start_date']));
			$this->ctx->curctx->push('EventEndDate', bab_mktime($arr['end_date']));
			$this->ctx->curctx->push('EventCategoryId', $arr['id_cat']);
			if( !empty($arr['color']))
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['color']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['bgcolor']);
				}
			
			$this->ctx->curctx->push('EventOwner', bab_getCalendarOwnerName($arr['id_cal'], BAB_CAL_USER_TYPE));
			$date = explode(' ', $arr['start_date']);
			$date = explode('-', $date[0]);
			$this->ctx->curctx->push('EventUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal']);
			$this->ctx->curctx->push('EventCalendarUrl', $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$arr['id_cal']."&date=".$date[0].",".$date[1].",".$date[2]);
			$this->ctx->curctx->push('EventCategoriesPopupUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc&calid=".$arr['id_cal']);
			if( isset($arr['name']))
				{
				$this->ctx->curctx->push('EventCategoryName', $arr['name']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryName', '');
				}
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

class bab_CalendarGroupEvents extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_CalendarGroupEvents( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$babBody->icalendars->initializeCalendars();
		$groupid = $ctx->get_value('groupid');
		$ar = array();
		if( $groupid === false || $groupid === '' )
			{
			reset($babBody->icalendars->pubcal);
			while( $row=each($babBody->icalendars->pubcal) ) 
				{
				$ar[] = $row[0];
				}
			}
		else
			{
			$filter = $ctx->get_value('filter');

			if (strtoupper($filter) == "NO") 
				{
				$filter = false;
				}
			else
				{
				$filter = true;
				}

			$rr = explode(',', $groupid);
			if( $filter )
				{
				reset($babBody->icalendars->pubcal);
				while( $row=each($babBody->icalendars->pubcal) ) 
					{
					if( in_array($row[1]['idowner'], $rr) )
						{
						$ar[] = $row[0];
						}
					}
				}
			elseif( !empty($groupid))
				{
				$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner IN (".$groupid.") and type='".BAB_CAL_PUB_TYPE."' and actif='Y'");
				while( $arr = $babDB->db_fetch_array($res) )
					{
					$ar[] = $arr['id'];
					}
				}
			}
		if( count($ar) > 0 )
			{
			$calid = implode(',', $ar);
			}
		else
			{
			$calid = '';
			}

		$date = $ctx->get_value('date');
		if( $date === false || $date === '' )
			{
			$date = 'CURDATE()';
			}
		else
			{
			$date = "'".$date."'";
			}
		$limit = $ctx->get_value('limit');
		$lf = $lr = 0;

		if( $limit !== false && $limit !== '' )
			{
			$limit = explode(',', $limit);
			if( count($limit) > 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				$lr = empty($limit[1])?0:$limit[1];
				}
			elseif ( count($limit) == 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				}
			}

		if( !empty($calid))
		{
		$categoryid = $ctx->get_value('categoryid');
		if( $categoryid === false || $categoryid === '' )
			{
			$categoryid = '';
			}

		$req = "select cet.*, ceot.id_cal, rct.name, rct.bgcolor from ".BAB_CAL_EVENTS_OWNERS_TBL." ceot left join ".BAB_CAL_EVENTS_TBL." cet on ceot.id_event=cet.id left join ".BAB_CAL_CATEGORIES_TBL." rct on rct.id=cet.id_cat where ceot.status='".BAB_CAL_STATUS_ACCEPTED."' and ceot.id_cal IN (".$calid.") and ((start_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ) or (end_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ))";
		if( !empty($categoryid))
			{
			$req .= " and cet.id_cat IN (".$categoryid.")";
			}
		$req .= " order by start_date asc";
		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		}
		else
		{
		$this->count = 0;
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	
	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('EventTitle', $arr['title']);
			bab_replace_ref($arr['description'],'OVML');
			$this->ctx->curctx->push('EventDescription', $arr['description']);
			$this->ctx->curctx->push('EventLocation', $arr['location']);
			$this->ctx->curctx->push('EventBeginDate', bab_mktime($arr['start_date']));
			$this->ctx->curctx->push('EventEndDate', bab_mktime($arr['end_date']));
			$this->ctx->curctx->push('EventCategoryId', $arr['id_cat']);
			if( !empty($arr['color']))
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['color']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['bgcolor']);
				}
			$this->ctx->curctx->push('EventOwner', bab_getCalendarOwnerName($arr['id_cal'], BAB_CAL_PUB_TYPE));
			$date = explode(' ', $arr['start_date']);
			$date = explode('-', $date[0]);
			$this->ctx->curctx->push('EventUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal']);
			$this->ctx->curctx->push('EventCalendarUrl', $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$arr['id_cal']."&date=".$date[0].",".$date[1].",".$date[2]);
			$this->ctx->curctx->push('EventCategoriesPopupUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc&calid=".$arr['id_cal']);
			if( isset($arr['name']))
				{
				$this->ctx->curctx->push('EventCategoryName', $arr['name']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryName', '');
				}
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


class bab_CalendarResourceEvents extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_CalendarResourceEvents( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$babBody->icalendars->initializeCalendars();
		$resourceid = $ctx->get_value('resourceid');
		$ar = array();
		if( $resourceid === false || $resourceid === '' )
			{
			reset($babBody->icalendars->rescal);
			while( $row=each($babBody->icalendars->rescal) ) 
				{
				$ar[] = $row[0];
				}
			}
		else
			{
			$filter = $ctx->get_value('filter');

			if (strtoupper($filter) == "NO") 
				{
				$filter = false;
				}
			else
				{
				$filter = true;
				}

			$ar = array();
			$rr = explode(',', $resourceid);
			if( $filter )
				{
				reset($babBody->icalendars->rescal);
				while( $row=each($babBody->icalendars->rescal) ) 
					{
					if( in_array($row[1]['idowner'], $rr) )
						{
						$ar[] = $row[0];
						}
					}
				}
			elseif( !empty($resourceid))
				{
				$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where owner IN (".$resourceid.") and type='".BAB_CAL_RES_TYPE."' and actif='Y'");
				while( $arr = $babDB->db_fetch_array($res) )
					{
					$ar[] = $arr['id'];
					}
				}
			}
		if( count($ar) > 0 )
			{
			$calid = implode(',', $ar);
			}
		else
			{
			$calid = '';
			}

		$date = $ctx->get_value('date');
		if( $date === false || $date === '' )
			{
			$date = 'CURDATE()';
			}
		else
			{
			$date = "'".$date."'";
			}
		$limit = $ctx->get_value('limit');
		$lf = $lr = 0;

		if( $limit !== false && $limit !== '' )
			{
			$limit = explode(',', $limit);
			if( count($limit) > 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				$lr = empty($limit[1])?0:$limit[1];
				}
			elseif ( count($limit) == 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				}
			}

		if( !empty($calid))
		{
		$categoryid = $ctx->get_value('categoryid');
		if( $categoryid === false || $categoryid === '' )
			{
			$categoryid = '';
			}

		$req = "select cet.*, ceot.id_cal, rct.name, rct.bgcolor from ".BAB_CAL_EVENTS_OWNERS_TBL." ceot left join ".BAB_CAL_EVENTS_TBL." cet on ceot.id_event=cet.id left join ".BAB_CAL_CATEGORIES_TBL." rct on rct.id=cet.id_cat where ceot.status='".BAB_CAL_STATUS_ACCEPTED."' and ceot.id_cal IN (".$calid.") and ((start_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ) or (end_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ))";
		if( !empty($categoryid))
			{
			$req .= " and cet.id_cat IN (".$categoryid.")";
			}
		$req .= " order by start_date asc";
		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		}
		else
		{
		$this->count = 0;
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	
	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('EventTitle', $arr['title']);
			bab_replace_ref($arr['description'],'OVML');
			$this->ctx->curctx->push('EventDescription', $arr['description']);
			$this->ctx->curctx->push('EventLocation', $arr['location']);
			$this->ctx->curctx->push('EventBeginDate', bab_mktime($arr['start_date']));
			$this->ctx->curctx->push('EventEndDate', bab_mktime($arr['end_date']));
			$this->ctx->curctx->push('EventCategoryId', $arr['id_cat']);
			if( !empty($arr['color']))
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['color']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['bgcolor']);
				}
			$this->ctx->curctx->push('EventOwner', bab_getCalendarOwnerName($arr['id_cal'], BAB_CAL_RES_TYPE));
			$date = explode(' ', $arr['start_date']);
			$date = explode('-', $date[0]);
			$this->ctx->curctx->push('EventUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal']);
			$this->ctx->curctx->push('EventCalendarUrl', $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$arr['id_cal']."&date=".$date[0].",".$date[1].",".$date[2]);
			$this->ctx->curctx->push('EventCategoriesPopupUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc&calid=".$arr['id_cal']);
			if( isset($arr['name']))
				{
				$this->ctx->curctx->push('EventCategoryName', $arr['name']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryName', '');
				}
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



class bab_CalendarEvents extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_CalendarEvents( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$babBody->icalendars->initializeCalendars();
		$calendarid = $ctx->get_value('calendarid');
		$ar = array();
		if( $calendarid === false || $calendarid === '' )
			{
			if( $babBody->icalendars->id_percal )
				{
				$ar[] = $babBody->icalendars->id_percal;
				}

			foreach( $babBody->icalendars->usercal as $key => $val )
				{
				$ar[] = $key;
				}
			foreach( $babBody->icalendars->rescal as $key => $val )
				{
				$ar[] = $key;
				}
			foreach( $babBody->icalendars->pubcal as $key => $val )
				{
				$ar[] = $key;
				}
			}
		else
			{
			$filter = $ctx->get_value('filter');

			if (strtoupper($filter) == "NO") 
				{
				$filter = false;
				}
			else
				{
				$filter = true;
				}

			$rr = explode(',', $calendarid);
			if( $filter )
				{
				if( $babBody->icalendars->id_percal && in_array($babBody->icalendars->id_percal, $rr))
					{
					$ar[] = $babBody->icalendars->id_percal;
					}

				foreach( $babBody->icalendars->usercal as $key => $val )
					{
					if( in_array($key, $rr))
						{
						$ar[] = $key;
						}
					}
				foreach( $babBody->icalendars->pubcal as $key => $val )
					{
					if( in_array($key, $rr))
						{
						$ar[] = $key;
						}
					}				
				foreach( $babBody->icalendars->rescal as $key => $val )
					{
					if( in_array($key, $rr))
						{
						$ar[] = $key;
						}
					}
				}
			elseif( !empty($calendarid))
				{
				$res = $babDB->db_query("select id from ".BAB_CALENDAR_TBL." where id IN (".$calendarid.") and  actif='Y'");
				while( $arr = $babDB->db_fetch_array($res) )
					{
					$ar[] = $arr['id'];
					}
				}
			}
		if( count($ar) > 0 )
			{
			$calid = implode(',', $ar);
			}
		else
			{
			$calid = '';
			}

		$date = $ctx->get_value('date');
		if( $date === false || $date === '' )
			{
			$date = 'CURDATE()';
			}
		else
			{
			$date = "'".$date."'";
			}
		$limit = $ctx->get_value('limit');
		$lf = $lr = 0;

		if( $limit !== false && $limit !== '' )
			{
			$limit = explode(',', $limit);
			if( count($limit) > 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				$lr = empty($limit[1])?0:$limit[1];
				}
			elseif ( count($limit) == 1 )
				{
				$lf = empty($limit[0])?0:$limit[0];
				}
			}

		if( !empty($calid))
		{
		$categoryid = $ctx->get_value('categoryid');
		if( $categoryid === false || $categoryid === '' )
			{
			$categoryid = '';
			}

		$req = "select cet.*, ceot.id_cal, rct.name, rct.bgcolor from ".BAB_CAL_EVENTS_OWNERS_TBL." ceot left join ".BAB_CAL_EVENTS_TBL." cet on ceot.id_event=cet.id left join ".BAB_CAL_CATEGORIES_TBL." rct on rct.id=cet.id_cat where ceot.status='".BAB_CAL_STATUS_ACCEPTED."' and ceot.id_cal IN (".$calid.") and ((start_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ) or (end_date between ".$date." - INTERVAL ".$lf." DAY and  ".$date." + INTERVAL ".$lr." DAY ))";
		if( !empty($categoryid))
			{
			$req .= " and cet.id_cat IN (".$categoryid.")";
			}
		$req .= " order by start_date asc";
		$this->res = $babDB->db_query($req);

		while( $arr = $babDB->db_fetch_array($this->res))
			{
			if( isset($this->IdEntries[$arr['id']]))
				{
				$this->IdEntries[$arr['id']]['id_cal'] .= ','.$arr['id_cal'];
				}
			else
				{
				$this->IdEntries[$arr['id']] = $arr;
				}
			}
		$this->count = count($this->IdEntries);
		}
		else
		{
		$this->count = 0;
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	
	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			list($idevent, $arr) = each($this->IdEntries);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('EventTitle', $arr['title']);
			bab_replace_ref($arr['description'],'OVML');
			$this->ctx->curctx->push('EventDescription', $arr['description']);
			$this->ctx->curctx->push('EventLocation', $arr['location']);
			$this->ctx->curctx->push('EventBeginDate', bab_mktime($arr['start_date']));
			$this->ctx->curctx->push('EventEndDate', bab_mktime($arr['end_date']));
			$this->ctx->curctx->push('EventCategoryId', $arr['id_cat']);
			if( !empty($arr['color']))
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['color']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryColor', $arr['bgcolor']);
				}
			$date = explode(' ', $arr['start_date']);
			$date = explode('-', $date[0]);
			$this->ctx->curctx->push('EventUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal']);
			$this->ctx->curctx->push('EventCalendarUrl', $GLOBALS['babUrlScript']."?tg=calmonth&calid=".$arr['id_cal']."&date=".$date[0].",".$date[1].",".$date[2]);
			$this->ctx->curctx->push('EventCategoriesPopupUrl', $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc&calid=".$arr['id_cal']);
			if( isset($arr['name']))
				{
				$this->ctx->curctx->push('EventCategoryName', $arr['name']);
				}
			else
				{
				$this->ctx->curctx->push('EventCategoryName', '');
				}
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


class bab_IfUserMemberOfGroups extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	function bab_IfUserMemberOfGroups( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		
		if( $GLOBALS['BAB_SESS_USERID'] != "" )
			{
			$all = $ctx->get_value('all');

			if ( $all !== false && strtoupper($all) == "YES") 
				$all = true;
			else
				$all = false;

			$groupid = $ctx->get_value('groupid');
			if( $groupid !== false && $groupid !== '' )
				{
				list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_USERS_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and id_group IN (".$groupid.")"));
				if( $all == false)
					{
					if( $total )
						{
						$this->count = 1;
						}
					}
				else
					{
					$rr = explode(',', $groupid);
					if( $total >= count($rr))
						{
						$this->count = 1;
						}
					}
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

	function getname()
	{
		return $this->name;
	}

	function getvars()
	{
		return $this->variables;
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
		{
		$this->gctx->push("babUserName", $GLOBALS['BAB_SESS_USER']);
		}
	else
		{
		$this->gctx->push("babUserName", 0);
		}
	$this->gctx->push("babCurrentDate", mktime());

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

function get_variables($contextname)
	{
	for( $i = count($this->contexts)-1; $i >= 0; $i--)
		{
		if( $this->contexts[$i]->getname() == $contextname )
			{
			return $this->contexts[$i]->getvars();
			}
		}
	return array();
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
		$handler = get_class($this->handlers[$i]);
		if(  $handler && (strtolower($handler) == strtolower($name)) )
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
	if( !class_exists($handler))
		{
		if( !strncmp($handler, "bab_DbDir", strlen("bab_DbDir")))
			{
			include_once $GLOBALS['babInstallPath']."utilit/ovmldir.php";
			}
		}

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
					{
					$val = substr($val, 0, $matches[3][$j]).$arr[1];
					$this->gctx->push('substr', 1);
					}
				else
					$this->gctx->push('substr', 0);
				break;
			case 'striptags':
				switch($matches[3][$j])
					{
					case '1':
						$val = strip_tags($val);
						break;
					case '2':
						$val = eregi_replace('<BR[[:space:]]*/?[[:space:]]*>', "\n ", $val);
						$val = eregi_replace('<P>|</P>|<P />|<P/>', "\n ", $val);
						$val = strip_tags($val);
						break;
					}
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
			case 'strtr':
				if( !empty($matches[3][$j]))
				{
				$trans = array();
				for( $i =0; $i < strlen($matches[3][$j]); $i +=2 )
					{
					$trans[substr($matches[3][$j], $i, 1)] = substr($matches[3][$j], $i+1, 1);
					}
				if( count($trans)> 0 )
					{
					$val = strtr($val, $trans);
					}
				}
				break;
			}
		}

	if( $saveas )
		$this->gctx->push($varname, $val);
	return $val;
	}

function vars_replace($txt)
	{
	if( empty($txt))
		{
		return $txt;
		}

	if(preg_match_all("/<(".BAB_TAG_FUNCTION."|".BAB_TAG_VARIABLE.")([^\s>]*)\s*(\w+\s*=\s*[\"].*?\")*\s*>/", $txt, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			switch( $m[1][$i] )
				{
				case BAB_TAG_FUNCTION:
					$handler = "bab_".$m[2][$i];
					$val = $this->$handler($this->vars_replace(trim($m[3][$i])));
					$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
					break;
				case BAB_TAG_VARIABLE:
					$val = $this->get_value($m[2][$i]);
					$args = $this->vars_replace(trim($m[3][$i]));
					if( $val !== false )
						{
						if( $args != "" )
							{
							if(preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $args, $mm))
								{
								$val = $this->format_output($val, $mm);
								}
							}
						$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
						}
					break;
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

/* Web statistic */
function bab_WebStat($args)
	{
	if($this->match_args($args, $mm))
		{
		$name = '';
		$value = '';
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
		if( !empty($name) && !empty($value))
			{
			if( substr($name, 0, 4) == "bab_" )
				{	
				$arr = explode(',', $value);
				for( $k = 0; $k < count($arr); $k++ )
					{
					$GLOBALS['babWebStat']->addArrayInfo($name, $arr[$k]);
					}
				}
			else
				{
				$GLOBALS['babWebStat']->addInfo($name, $value);
				}
			}
		}
	}

/* save a variable to global space */
function bab_PutVar($args)
	{
	global $babBody;
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
					$global = true;
					break;
				case 'value':
					$value = $mm[3][$j];
					$global = false;
					switch($name)
					{
						case 'babSlogan': $GLOBALS['babSlogan'] = $value; break;
						case 'babTitle': $babBody->title = $value; break;
						case 'babError': $babBody->msgerror = $value; break;
						default: break;
					}
					
					break;
				}
			}					
		if( $global && isset($GLOBALS[$name]) )
			{
			$value = $GLOBALS[$name];
			}
		$this->gctx->push($name, $value);
		}
	}

/* get a variable */
function bab_GetVar($args)
	{
	global $babBody;
	$name = "";

	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'name':
					$name = $mm[3][$j];
					$global = true;
					break;
				}
			}					

		if( !empty($name))
			{
			$value = $this->get_value($name);
			if( $value !== false )
				return $value;
			}
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
	//print_r($args);
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

function bab_Header($args)
	{
	$value = '';
	if($this->match_args($args, $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'value':
					$value = $mm[3][$j];
					break;
				}
			}
		header($value);
		}
	}


function bab_Addon($args)
	{
	global $babBody;
	$output = '';
	if($this->match_args($args, $mm))
		{
		$function_args = array();
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			switch(strtolower(trim($mm[1][$j])))
				{
				case 'name':
					foreach ($babBody->babaddons as $value)
						{
						if ($value['title'] == $mm[3][$j])
							{
							$addonid = $value['id'];
							break;
							}
						}
					break;
				
				case 'function':
					$function = $mm[3][$j];
					break;
				default:
					$function_args[] = $mm[3][$j];
					break;
				}
			}

		if (!empty($addonid) && !empty($function) && isset($babBody->babaddons[$addonid]) && $babBody->babaddons[$addonid]['access'])
			{
			$addonpath = $GLOBALS['babAddonsPath'].$babBody->babaddons[$addonid]['title'];
			if( is_file($addonpath."/ovml.php" ))
				{
				/* save old vars */
				$oldAddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
				$oldAddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
				$oldAddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
				$oldAddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
				$oldAddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
				$oldAddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

				$GLOBALS['babAddonFolder'] = $babBody->babaddons[$addonid]['title'];
				$GLOBALS['babAddonTarget'] = "addon/".$addonid;
				$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$addonid."/";
				$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$babBody->babaddons[$addonid]['title']."/";
				$GLOBALS['babAddonHtmlPath'] = "addons/".$babBody->babaddons[$addonid]['title']."/";
				$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$babBody->babaddons[$addonid]['title']."/";
				require_once( $addonpath."/ovml.php" );

				$call = $babBody->babaddons[$addonid]['title']."_".$function;
				if( !empty($call)  && function_exists($call) )
					{
					$output = call_user_func_array ( $call, $function_args );
					}

				$GLOBALS['babAddonFolder'] = $oldAddonFolder;
				$GLOBALS['babAddonTarget'] = $oldAddonTarget;
				$GLOBALS['babAddonUrl'] = $oldAddonUrl;
				$GLOBALS['babAddonPhpPath'] = $oldAddonPhpPath;
				$GLOBALS['babAddonHtmlPath'] = $oldAddonHtmlPath;
				$GLOBALS['babAddonUpload'] = $oldAddonUpload;
				}
			}
		}
	return $output;
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

    if (strlen($url['path']) > 1 && $url['path']{strlen($url['path']) - 1} == '/')
        $dir = substr($url['path'], 0, strlen($url['path']) - 1);
    else
        $dir = dirname($url['path']);

    if ($relative{0} == '/')
		{
        $relative = substr($relative, 1);
        $dir = '';
		}
    else if (substr($relative, 0, 2) == './')
		{
        $relative = substr($relative, 2);
		}
    else while (substr($relative, 0, 3) == '../')
		{
        $relative = substr($relative, 3);
        $dir = substr($dir, 0, strrpos($dir, '/'));
		}
    return sprintf('%s://%s%s/%s', $url['scheme'], $url['host'], $dir, $relative);
}
?>