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
define('BAB_TAG_CONTAINER', 'OC');
define('BAB_TAG_VARIABLE', 'OV');
define('BAB_TAG_FUNCTION', 'OF');


define('BAB_OPE_EQUAL'				, 1);
define('BAB_OPE_NOTEQUAL'			, 2);
define('BAB_OPE_LESSTHAN'			, 3);
define('BAB_OPE_LESSTHANOREQUAL'	, 4);
define('BAB_OPE_GREATERTHAN'		, 5);
define('BAB_OPE_GREATERTHANOREQUAL'	, 6);

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

	function printoutws()
	{
		$this->ctx->push_handler($this);
		$res = array();
		$skip = false;
		while($this->getnext($skip))
		{
			$tmparr = array();
			if( !$skip)
				{
				foreach($this->ctx->get_variables($this->ctx->get_currentContextname()) as $key => $val )
					{
					$tmparr[] = array('name'=> $key, 'value'=> $val);
					}
				}
			$res[] = $tmparr;
			$skip = false;
		}
		$this->ctx->pop_handler();
		return $res;
	}

	function getnext()
	{
		return false;
	}
	
	/**
	 * transform editor content to html 
	 * @param	string	&$txt
	 * @param	string	$editor
	 */
	function replace_ref(&$txt, $editor) {
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor($editor);
		$editor->setContent($txt);
		$txt = $editor->getHtml();
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


class bab_ObjectsInfo extends bab_handler
{
	var $res;
	var $fields = array();
	var $ovmlfields = array();
	var $index;
	var $count;

	function bab_ObjectsInfo( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$this->count = 0;
		$type = $ctx->get_value('type');
		if( $type !== false && $type !== '' )
		{
			$type = strtolower(trim($type));
			switch( $type )
			{
				case 'folder':
					$folder = $ctx->get_value('folder');
					if( $folder !== false && $folder !== '' )
					{
					$this->fields = array('id', 'folder');
					$this->ovmlfields = array('Id', 'Folder');
					$this->res = $babDB->db_query('select id, folder from '.BAB_FM_FOLDERS_TBL.' where folder=\''.$babDB->db_escape_string($folder).'\'');
					$this->count = $babDB->db_num_rows($this->res);
					}
				case 'articlecategories':
					$category = $ctx->get_value('category');
					if( $category !== false && $category !== '' )
					{
						$parent = $ctx->get_value('parent');
						$parents = array();
						if( $parent !== false && $parent !== '' )
						{
						$parents = array_reverse(explode('/', $parent));
						}
						$this->fields = array('id', 'title');
						$this->ovmlfields = array('Id', 'Category');
						$leftjoin = '';
						$where = ' where c0.title=\''.$babDB->db_escape_string($category).'\'';
						$req = 'select c0.title, c0.id from '.BAB_TOPICS_CATEGORIES_TBL.' c0';
						for( $k=0; $k < count($parents); $k++ )
						{
							$leftjoin .= ' left join '.BAB_TOPICS_CATEGORIES_TBL.' c'.($k+1).' on c'.($k+1).'.id = c'.$k.'.id_parent';
							$where .= ' and  c'.($k+1).'.title=\''.$babDB->db_escape_string($parents[$k]).'\'';
						}
						$this->res = $babDB->db_query($req.$leftjoin.$where);
						$this->count = $babDB->db_num_rows($this->res);
					}
					break;
				case 'articletopics':
					$topic = $ctx->get_value('topic');
					if( $topic !== false && $topic !== '' )
					{
						$parent = $ctx->get_value('parent');
						$parents = array();
						if( $parent !== false && $parent !== '' )
						{
						$parents = array_reverse(explode('/', $parent));
						}
						$this->fields = array('id', 'category');
						$this->ovmlfields = array('Id', 'Topic');
						$leftjoin = '';
						$where = ' where c0.category=\''.$babDB->db_escape_string($topic).'\'';
						$req = 'select c0.category, c0.id from '.BAB_TOPICS_TBL.' c0';
						for( $k=0; $k < count($parents); $k++ )
						{
							$leftjoin .= ' left join '.BAB_TOPICS_CATEGORIES_TBL.' c'.($k+1).' on c'.($k+1).'.id = c'.$k.($k==0? '.id_cat':'.id_parent');
							$where .= ' and  c'.($k+1).'.title=\''.$babDB->db_escape_string($parents[$k]).'\'';
						}
						$this->res = $babDB->db_query($req.$leftjoin.$where);
						$this->count = $babDB->db_num_rows($this->res);
					}
					break;
				case 'user':
					$nickname = $ctx->get_value('nickname');
					if( $nickname !== false && $nickname !== '' )
					{
					$this->fields = array('id', 'nickname', 'firstname', 'lastname', 'mn');
					$this->ovmlfields = array('Id', 'Nickname', 'Firstname', 'Lastname', 'Middlename');
					$this->res = $babDB->db_query('select u.id, u.nickname, u.firstname, u.lastname, d.mn from '.BAB_USERS_TBL.' u left join '.BAB_DBDIR_ENTRIES_TBL.' d on u.id=d.id_user where d.id_directory=0 and u.nickname=\''.$babDB->db_escape_string($nickname).'\'');
					$this->count = $babDB->db_num_rows($this->res);
					}
					break;
				default:
					break;
			}
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
			for( $k=0; $k < count($this->fields); $k++ )
			{
			$this->ctx->curctx->push('Object'.$this->ovmlfields[$k], $arr[$this->fields[$k]]);
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

		$res = $babDB->db_query("select ht.id, at.id_topic, at.restriction from ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_HOMEPAGES_TBL." ht on ht.id_article=at.id where ht.id_group='".$babDB->db_escape_string($this->idgroup)."' and ht.id_site='".$arr['id']."' and ht.ordering!='0' and (at.date_publication='0000-00-00 00:00:00' or at.date_publication <= now()) GROUP BY at.id order by ".$babDB->db_escape_string($order));
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
			$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_HOMEPAGES_TBL." ht on ht.id_article=at.id left join ".BAB_ART_FILES_TBL." aft on aft.id_article=at.id where ht.id IN (".$babDB->quote($this->IdEntries).") group by at.id order by ".$babDB->db_escape_string($order));
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
			$this->replace_ref($arr['head'], 'bab_article_head');
			$this->replace_ref($arr['body'], 'bab_article_body');
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
				{
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
				}
			else
				{
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
				}
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
			$this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);			
			list($topictitle) = $babDB->db_fetch_array($babDB->db_query("select category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($arr['id_topic'])."'"));
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

		$delegationid = (int) $ctx->get_value('delegationid');
		$sDelegation = ' ';
		if(0 != $delegationid)
		{
			$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		
		if( count($parentid) > 0 )
		{
		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent IN (".$babDB->quote($parentid).")");
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
			$req = "select tc.* from ".BAB_TOPICS_CATEGORIES_TBL." tc left join ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat where tc.id IN (".$babDB->quote($this->IdEntries).") and tot.type='1'" . $sDelegation .  " order by tot.ordering asc";
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
				$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".$babDB->quote($this->IdEntries).")");
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
		$this->res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".$babDB->quote($catid).")");
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
		$delegationid = (int) $ctx->get_value('delegationid');

		$sDelegation = ' ';
		$sLeftJoin = ' ';
		if(0 != $delegationid)
		{
			$sLeftJoin = 
				'LEFT JOIN ' .
					BAB_TOPICS_TBL . ' tc ON tc.id = id_topcat ' .
				'LEFT JOIN ' .
					BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = tc.id_cat ';
			
			$sDelegation = ' AND tpc.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}
		
		
		if( $catid === false || $catid === '' )
			$catid = array_keys($babBody->get_topcatview());
		else
			$catid = array_intersect(array_keys($babBody->get_topcatview()), explode(',', $catid));

		if( count($catid) > 0 )
		{
		$req = "select * from ".BAB_TOPCAT_ORDER_TBL. " tco " . $sLeftJoin . " where tco.type='2' and tco.id_parent IN (".$babDB->quote($catid).")" . $sDelegation . " order by tco.ordering asc";

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
			$req = "select tc.* from ".BAB_TOPICS_TBL." tc left join ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat where tc.id IN (".$babDB->quote($this->IdEntries).") and tot.type='2' order by tot.ordering asc";
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
			
			$this->replace_ref($arr['description'], 'bab_topic');
			
			$this->ctx->curctx->push('TopicDescription', $arr['description']);
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('TopicLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			list($cattitle) = $babDB->db_fetch_array($babDB->db_query("select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
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
		$this->topicname = $ctx->get_value('topicname');

		if( $this->topicid === false || $this->topicid === '' )
			$this->IdEntries = array_keys($babBody->topview);
		else
			$this->IdEntries = array_values(array_intersect(array_keys($babBody->topview), explode(',', $this->topicid)));
		$this->count = count($this->IdEntries);

		if( $this->count > 0 )
			{
			if( $this->topicname === false || $this->topicname === '' )
				{
				$req = "select * from ".BAB_TOPICS_TBL." where id IN (".$babDB->quote($this->IdEntries).")";
				}
			else
				{
				$req = "select * from ".BAB_TOPICS_TBL." where id IN (".$babDB->quote($this->IdEntries).") and category like '".$babDB->db_escape_string($this->topicname)."'";
				}

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
			$this->ctx->curctx->push('TopicName', $arr['category']);
			$this->replace_ref($arr['description'], 'bab_topic');
			$this->ctx->curctx->push('TopicDescription', $arr['description']);
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('TopicLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			list($cattitle) = $babDB->db_fetch_array($babDB->db_query("select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
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
		$delegationid = (int) $ctx->get_value('delegationid');

		$sDelegation = ' ';
		$sLeftJoin = ' ';
		if(0 != $delegationid)
		{
			$sLeftJoin = 
				'LEFT JOIN ' .
					BAB_TOPICS_TBL . ' t ON t.id = id_topic ' .
				'LEFT JOIN ' .
					BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = t.id_cat ';
			
			$sDelegation = ' AND tpc.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		if( $topicid === false || $topicid === '' )
			$topicid = array_keys($babBody->topview);
		else
			$topicid = array_intersect(array_keys($babBody->topview), explode(',', $topicid));

		if( count($topicid) > 0)
		{
			$this->excludetopicid = $ctx->get_value('excludetopicid');
			if ( $this->excludetopicid !== false && $this->excludetopicid !== '' )
				{
				$topicid = array_diff($topicid, explode(',', $this->excludetopicid));
				}

			$archive = $ctx->get_value('archive');
			if( $archive === false || $archive === '')
				$archive = "no";

			switch(strtoupper($archive))
			{
				case 'NO': $archive = " and archive='N' "; break;
				case 'YES': $archive = " and archive='Y' "; break;
				default: $archive = ""; break;

			}

			$req = "select at.id, at.restriction from " . BAB_ARTICLES_TBL . " at " . $sLeftJoin . "where at.id_topic IN (".$babDB->quote($topicid).") and (at.date_publication='0000-00-00 00:00:00' or at.date_publication <= now())" . $archive . $sDelegation;

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = 'asc';

			$forder = $ctx->get_value('topicorder');
			switch(strtoupper($forder))
			{
				case 'YES': $forder = true; break;
				case 'NO': /* no break */
				default: $forder = false; break;

			}

			$orderby = $ctx->get_value('orderby');
			if( $orderby === false || $orderby === '' )
				$orderby = "at.date";
			else
				{
				switch(strtolower($orderby))
					{
					case 'creation': $orderby = 'at.date'; break;
					case 'publication': $orderby = 'at.date_publication'; break;
					case 'modification':
					default:
						$orderby = 'at.date_modification'; break;
					}
				}

			switch(strtoupper($order))
			{
				case 'ASC':
					if( $forder )
					{
						$order = 'at.ordering asc, at.date_modification desc'; 
					}
					else
					{
						$order = $orderby.' asc';
					}
					break;
				case 'RAND': 
					$order = 'rand()'; 
					break;
				case 'DESC':
				default:
					if( $forder )
					{
						$order = 'at.ordering desc, at.date_modification asc'; 
					}
					else
					{
						$order = $orderby.' desc';
					}
					break;

			}

			$req .=  'order by '.$order;

			$rows = $ctx->get_value('rows');
			$offset = $ctx->get_value('offset');
			if( $rows === false || $rows === '')
				$rows = "-1";

			if( $offset === false || $offset === '')
				$offset = "0";

			if ($rows != -1)
				$req .= ' limit '.$babDB->db_escape_string($offset).', '.$babDB->db_escape_string($rows);

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
			$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id IN (".$babDB->quote($this->IdEntries).") group by at.id order by ".$order);
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
			$this->replace_ref($arr['head'], 'bab_article_head');
			$this->replace_ref($arr['body'], 'bab_article_body');
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
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date'])); /* for compatibility */
			$this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
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
			$res = $babDB->db_query("select id, id_topic, restriction from ".BAB_ARTICLES_TBL." where id IN (".$babDB->quote(explode(',', $articleid)).") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())");
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
				$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id IN (".$babDB->quote($this->IdEntries).") group by at.id");
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
			$this->replace_ref($arr['head'], 'bab_article_head');
			$this->replace_ref($arr['body'], 'bab_article_body');
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
			$res = $babDB->db_query("select id, id_topic, restriction from ".BAB_ARTICLES_TBL." where id IN (".$babDB->quote(explode(',', $articleid)).") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())");
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
				$this->res = $babDB->db_query("select aft.*, at.id_topic from ".BAB_ART_FILES_TBL." aft left join ".BAB_ARTICLES_TBL." at on aft.id_article=at.id where aft.id_article IN (".$babDB->quote($this->IdEntries).")");
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
		$delegationid = (int) $ctx->get_value('delegationid');

		$sDelegation = ' ';	
		if(0 != $delegationid)	
		{
			$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}
		
		if( $forumid === false || $forumid === '' )
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y'" . $sDelegation . "order by ordering asc");
		else
			{
			$forumid = explode(',', $forumid);
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y'" . $sDelegation . "and id IN (".$babDB->quote($forumid).") order by ordering asc");
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
			$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where active='Y' and id IN (".$babDB->quote($this->IdEntries).") order by ordering asc");
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
		$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($ctx->get_value('forumid'))."' and active='Y'");
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
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y' and p.id IN (".$babDB->quote($arr).") AND p.confirmed =  'Y'";			
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
			$this->res = $babDB->db_query("select * from ".BAB_POSTS_TBL." p where id IN (".$babDB->quote($this->arrid).") order by ".$order);
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
			$this->replace_ref($arr['message'], 'bab_forum_post');
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
				$req = "SELECT t.forum FROM ".BAB_THREADS_TBL." t,".BAB_POSTS_TBL." p WHERE t.id = p.id_thread AND p.id='".$babDB->db_escape_string($postid)."'";
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
			$req = "select tt.id, tt.forum from ".BAB_THREADS_TBL." tt left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum WHERE ft.active='Y' and tt.id IN (".$babDB->quote($arr).") and tt.active='Y'";

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
			$this->res = $babDB->db_query("select * from ".BAB_THREADS_TBL." where id IN (".$babDB->quote($this->arrid).") order by ".$order);
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
	var $index = 0;
	var $count = 0;
	var $IdEntries = array();
	var $oFmFolderSet = null;
	
	function bab_Folders(&$ctx)
	{
		global $babDB;
		$this->bab_handler($ctx);
		$folderid = $ctx->get_value('folderid');
		$iIdDelegation = (int) $ctx->get_value('delegationid');
		
		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		$this->oFmFolderSet = new BAB_FmFolderSet();
		$oIdDgOwner = $this->oFmFolderSet->aField['iIdDgOwner'];
		$oActive = $this->oFmFolderSet->aField['sActive'];
		$oId = $this->oFmFolderSet->aField['iId'];

		$oCriteria = $oActive->in('Y');
		if(0 !== $iIdDelegation)
		{
			$oCriteria = $oCriteria->_and($oIdDgOwner->in($iIdDelegation));
		}

		if(false !== $folderid && '' !== $folderid)
		{
			$oCriteria = $oCriteria->_and($oId->in(explode(',', $folderid)));
		}

		$this->oFmFolderSet->select($oCriteria);
		
		while(null !== ($oFmFolder = $this->oFmFolderSet->next()))
		{
			if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $oFmFolder->getId()))
			{
				array_push($this->IdEntries, $oFmFolder->getId());
			}
		}
		$this->oFmFolderSet->select($oId->in($this->IdEntries));
		$this->count = $this->oFmFolderSet->count();
		$this->ctx->curctx->push('CCount', $this->oFmFolderSet->count());
	}

	function getnext()
	{
		static $iIndex = 0;
		
		if(null !== ($oFmFolder = $this->oFmFolderSet->next()))
		{
			$this->ctx->curctx->push('CIndex', $iIndex);
			$this->ctx->curctx->push('FolderName', $oFmFolder->getName());
			$this->ctx->curctx->push('FolderId', $oFmFolder->getId());
			$iIndex++;
			$this->index = $iIndex;
			return true;
		}
		else
		{
			$this->oFmFolderSet->reset();
			$this->index = $iIndex = 0;
			return false;
		}
	}
}




class bab_Folder extends bab_handler
{
	var $index;
	var $count;
	var $oFmFolderSet = null;

	function bab_Folder( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$folderid = (int) $ctx->get_value('folderid');
		$this->count = 0;
		
		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		$this->oFmFolderSet = new BAB_FmFolderSet();
		$oId = $this->oFmFolderSet->aField['iId'];
		
		if(0 !== $folderid && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folderid))
		{
			$this->oFmFolderSet->select($oId->in($folderid));
			$this->count = $this->oFmFolderSet->count();
			$this->ctx->curctx->push('CCount', $this->count);
		}
		else 
		{
			$this->ctx->curctx->push('CCount', 0);
		}
	}

	function getnext()
	{
		static $iIndex = 0;
		
		if(0 != $this->oFmFolderSet->count() && null !== ($oFmFolder = $this->oFmFolderSet->next()))
		{
			$this->ctx->curctx->push('CIndex', $iIndex);
			$this->ctx->curctx->push('FolderName', $oFmFolder->getName());
			$this->ctx->curctx->push('FolderId', $oFmFolder->getId());
			$iIndex++;
			$this->index = $iIndex;
			return true;
		}
		else
		{
			$iIndex = 0;
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
	
	var $oFmFolderSet = null;

	function bab_SubFolders(&$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$folderid = (int) $ctx->get_value('folderid');
		$this->count = 0;

		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

		$sPath = (string) $path = $ctx->get_value('path');

		$this->oFmFolderSet = new BAB_FmFolderSet();
		$oId = $this->oFmFolderSet->aField['iId'];

		if(0 !== $folderid)
		{
			$oFmFolder = $this->oFmFolderSet->get($oId->in($folderid));
//			bab_debug($this->oFmFolderSet->getSelectQuery($oId->in($folderid)));
			if(!is_null($oFmFolder))
			{

				
				$iRelativePathLength = strlen($oFmFolder->getRelativePath());
				$sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();
				
//				bab_debug('sRelativePath ==> ' . $sRelativePath . 
//					' sRootFolderName ==> ' . getFirstPath($sRelativePath));
	
				$sRootFolderName = getFirstPath($sRelativePath);
				if($this->accessValid($sRootFolderName, $oFmFolder->getName() . '/' . $sPath))
				{				
					$sRelativePath = $sRootFolderName . '/' . $sPath . '/';
					
//					$oFileManagerEnv =& getEnvObject();
					$sUploadPath = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId());
					
					$sFullPathName = realpath($sUploadPath . $sRelativePath);
					
					$this->walkDirectory($sFullPathName);
					
					$this->count = count($this->IdEntries);
					$order = $ctx->get_value('order');
					if($order === false || $order === '')
					{
						$order = 'asc';
					}
					
					switch(strtolower($order))
					{
						case 'desc':
							rsort($this->IdEntries);
							break;
						default:
							sort($this->IdEntries);
							break;
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
	
	function accessValid($sName, $sPath)
	{
		$oName = $this->oFmFolderSet->aField['sName'];
		$oRelativePath = $this->oFmFolderSet->aField['sRelativePath'];
		
		$oCriteria = $oName->in($sName);
		$oCriteria = $oCriteria->_and($oRelativePath->in(''));
		
		//Get the root folder
		$oFmFolder = $this->oFmFolderSet->get($oCriteria);
		if(!is_null($oFmFolder))
		{
			$iIdOwner = 0;
			$sRelativePath = '';

			BAB_FmFolderHelper::getFileInfoForCollectiveDir($oFmFolder->getId(), $sPath, 
				$iIdOwner, $sRelativePath, $oFmFolder);		

				
			return 	bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $iIdOwner);
		}
		return false;
	}
	
	function walkDirectory($sFullPathName)
	{
//		bab_debug(__LINE__ . ' ' . basename(__FILE__) . ' ' . __FUNCTION__ . ' sPathName ==> ' . $sFullPathName);
		if(is_dir($sFullPathName))
		{
			$oDir = dir($sFullPathName);
			while(false !== $sEntry = $oDir->read()) 
			{
				// Skip pointers
				if($sEntry == '.' || $sEntry == '..' || $sEntry == BAB_FVERSION_FOLDER) 
				{
					continue;
				}
				
				if(is_dir($sFullPathName . '/' . $sEntry))
				{
					$this->IdEntries[] = $sEntry;
				}
			}
			$oDir->close();
		}
	}
}

class bab_Files extends bab_handler
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;
	var $tags = array();

	var $oFmFolderSet = null;
	var $oFolderFileSet = null;
	var $iIdRootFolder = 0;
	var $sPath = '';
	var $sEncodedPath = '';
	var $iIdDelegation = 0;
	function bab_Files(&$ctx)
	{
		global $babBody, $babDB;
		include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
		$this->bab_handler($ctx);
		$this->count	= 0;
		$folderid		= (int) $ctx->get_value('folderid');
		$this->sPath	= (string) $ctx->get_value('path');
		$iLength		= strlen(trim($this->sPath));
		
		if($iLength && '/' === $this->sPath{$iLength - 1})
		{
			$this->sPath = substr($sEncodedPath, 0, -1); 
		}

		$this->sEncodedPath = urlencode($this->sPath);
		
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

		$this->oFolderFileSet = new BAB_FolderFileSet();

		$this->oFmFolderSet = new BAB_FmFolderSet();
		$oId = $this->oFmFolderSet->aField['iId'];

		if(0 !== $folderid)
		{
			$oFmFolder = $this->oFmFolderSet->get($oId->in($folderid));
			if(!is_null($oFmFolder))
			{
				$this->iIdDelegation = $oFmFolder->getDelegationOwnerId();
				
				$iRelativePathLength = strlen($oFmFolder->getRelativePath());
				$sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();
				
//				bab_debug('sRelativePath ==> ' . $sRelativePath . 
//					' sRootFolderName ==> ' . getFirstPath($sRelativePath));
	
				$sRootFolderName = getFirstPath($sRelativePath);
				$sRelativePath = $sRootFolderName . '/' . ($iLength? $this->sPath . '/': '');
				
				$this->initRootFolderId($sRootFolderName);
				
				$rows	= (int) $ctx->get_value('rows');
				$offset	= (int) $ctx->get_value('offset');
				
				$oGroup		= $this->oFolderFileSet->aField['sGroup'];
				$oState		= $this->oFolderFileSet->aField['sState'];
				$oPathName	= $this->oFolderFileSet->aField['sPathName'];
				$oConfirmed	= $this->oFolderFileSet->aField['sConfirmed'];
				$oIdDgOwner	= $this->oFolderFileSet->aField['iIdDgOwner'];
				
				$oCriteria = $oGroup->in('Y');
				$oCriteria = $oCriteria->_and($oState->in(''));
				$oCriteria = $oCriteria->_and($oPathName->in($sRelativePath));
				$oCriteria = $oCriteria->_and($oConfirmed->in('Y'));
				$oCriteria = $oCriteria->_and($oIdDgOwner->in($oFmFolder->getDelegationOwnerId()));
				
				$aLimit = array();
				if(0 !== $rows)
				{
					$aLimit = array($offset, $rows);
				}
				
				$this->oFolderFileSet->select($oCriteria, array('sName' => 'ASC'), $aLimit);
				while(null !== ($oFolderFile = $this->oFolderFileSet->next()))
				{
					$this->IdEntries[] = $oFolderFile->getId();
					$this->tags[$oFolderFile->getId()] = array();
					$rs = $babDB->db_query("select tag_name from ".BAB_TAGS_TBL." tt left join ".BAB_FILES_TAGS_TBL." ftt on tt.id = ftt.id_tag WHERE id_file='".$oFolderFile->getId()."'");
					while( $rr = $babDB->db_fetch_array($rs))
					{
						$this->tags[$oFolderFile->getId()][] = $rr['tag_name'];
					}
				}
				$this->oFolderFileSet->rewind() ;
				$this->count = count($this->IdEntries);
			}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function initRootFolderId($sRootFolderName)
	{
		$oName = $this->oFmFolderSet->aField['sName'];
		$oRelativePath = $this->oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner	= $this->oFmFolderSet->aField['iIdDgOwner'];
		
		$oCriteria = $oName->in($sRootFolderName);
		$oCriteria = $oCriteria->_and($oRelativePath->in(''));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in($this->iIdDelegation));
		
		//Get the root folder
		$oFmFolder = $this->oFmFolderSet->get($oCriteria);
		if(!is_null($oFmFolder))
		{
			$this->iIdRootFolder = $oFmFolder->getId();
		}
	}
	
	function getnext()
	{
		global $babDB;
		if(0 !== $this->oFolderFileSet->count())
		{
			if(null !== ($oFolderFile = $this->oFolderFileSet->next()))
			{
				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('FileName', $oFolderFile->getName());
				$this->ctx->curctx->push('FileDescription', $oFolderFile->getDescription());
				$this->ctx->curctx->push('FileKeywords', implode(' ', $this->tags[$oFolderFile->getId()]));
				$this->ctx->curctx->push('FileId', $oFolderFile->getId());
				$this->ctx->curctx->push('FileFolderId', $oFolderFile->getOwnerId());
				$this->ctx->curctx->push('FileDate', bab_mktime($oFolderFile->getModifiedDate()));
				$this->ctx->curctx->push('FileAuthor', bab_getUserName($oFolderFile->getAuthorId()));
				
				$sGroup	= $oFolderFile->getGroup();
				
				$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript'] .'?tg=fileman&idx=list&id=' . $this->iIdRootFolder . '&gr=' . 
					$sGroup . '&path=' . $this->sEncodedPath);
				
				$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript'] . '?tg=fileman&idx=viewFile&idf=' . $oFolderFile->getId() . 
					'&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $this->sEncodedPath . '&file=' . urlencode($oFolderFile->getName()));
					
				$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript'] . '?tg=fileman&sAction=getFile&id=' . $oFolderFile->getOwnerId() . '&gr=' . 
					$sGroup . '&path=' . $this->sEncodedPath . '&file=' . urlencode($oFolderFile->getName()) . '&idf=' . $oFolderFile->getId());
					
				$oFileManagerEnv =& getEnvObject();
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();;
				
				$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();
				if(file_exists($sFullPathName))
				{
					$this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathName)));
				}
				else
				{
					$this->ctx->curctx->push('FileSize', '???');
				}
				$this->idx++;
				$this->index = $this->idx;
				return true;
			}
			else
			{
				return false;
			}
		}
		$this->idx=0;
		return false;
	}
}


class bab_File extends bab_handler
{
	var $arr;
	var $index;
	var $count;
	var $oFolderFile = null;
	var $iIdRootFolder = 0;
	var $tags = array();
	
	function bab_File(&$ctx)
	{
		global $babBody, $babDB;
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

		$oFolderFileSet = new BAB_FolderFileSet();
		$oId = $oFolderFileSet->aField['iId'];

		$this->bab_handler($ctx);
		$this->count = 0;
		$fileid = (int) $ctx->get_value('fileid');
		if(0 !== $fileid)
		{
			$this->oFolderFile = $oFolderFileSet->get($oId->in($fileid));
			if(!is_null($this->oFolderFile))
			{
				if('Y' === $this->oFolderFile->getGroup() && '' === $this->oFolderFile->getState() && 'Y' === $this->oFolderFile->getConfirmed() && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $this->oFolderFile->getOwnerId()))
				{
					$this->count = 1;
					
					$rs = $babDB->db_query("select tag_name from ".BAB_TAGS_TBL." tt left join ".BAB_FILES_TAGS_TBL." ftt on tt.id = ftt.id_tag WHERE id_file='".$this->oFolderFile->getId()."'");
					while( $rr = $babDB->db_fetch_array($rs))
					{
						$this->tags[] = $rr['tag_name'];
					}
				}
			}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	function getnext()
	{
		static $iIndex = 0;
		
		if($iIndex < $this->count)
		{
			$iIndex++;
			if(null !== $this->oFolderFile)
			{

				
				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('FileName', $this->oFolderFile->getName());
				$this->ctx->curctx->push('FileDescription', $this->oFolderFile->getDescription());
				$this->ctx->curctx->push('FileKeywords', implode(' ', $this->tags));
				$this->ctx->curctx->push('FileId', $this->oFolderFile->getId());
				$this->ctx->curctx->push('FileFolderId', $this->oFolderFile->getOwnerId());
				$this->ctx->curctx->push('FileDate', bab_mktime($this->oFolderFile->getModifiedDate()));
				$this->ctx->curctx->push('FileAuthor', bab_getUserName($this->oFolderFile->getAuthorId()));
				
				$sRootFolderName = getFirstPath($this->oFolderFile->getPathName());
				$this->initRootFolderId($sRootFolderName);
				
				$sEncodedPath = urlencode(removeFirstPath($this->oFolderFile->getPathName()));
				
				$sGroup	= $this->oFolderFile->getGroup();
				
				$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript'] .'?tg=fileman&idx=list&id=' . $this->iIdRootFolder . '&gr=' . 
					$sGroup . '&path=' . $sEncodedPath);
				
				$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript'] . '?tg=fileman&idx=viewFile&idf=' . $this->oFolderFile->getId() . 
					'&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($this->oFolderFile->getName()));
					
				$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript'] . '?tg=fileman&sAction=getFile&id=' . $this->oFolderFile->getOwnerId() . '&gr=' . 
					$sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($this->oFolderFile->getName()) . '&idf=' . $oFolderFile->getId());
					
//				$oFileManagerEnv =& getEnvObject();
//				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
				
				$sFullPathName = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $this->oFolderFile->getPathName() . $this->oFolderFile->getName();
				if(file_exists($sFullPathName))
				{
					$this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathName)));
				}
				else
				{
					$this->ctx->curctx->push('FileSize', '???');
				}
				$this->idx++;
				$this->index = $this->idx;
				return true;
			}
		}
		$this->idx=0;
		return false;
	}

	function initRootFolderId($sRootFolderName)
	{
		$oFmFolderSet = new BAB_FmFolderSet();
		$oName = $oFmFolderSet->aField['sName'];
		$oRelativePath = $oFmFolderSet->aField['sRelativePath'];
		$oIdDgOwner	= $oFmFolderSet->aField['iIdDgOwner'];
		
		$oCriteria = $oName->in($sRootFolderName);
		$oCriteria = $oCriteria->_and($oRelativePath->in(''));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(bab_getCurrentUserDelegation()));
		
		//Get the root folder
		$oFmFolder = $oFmFolderSet->get($oCriteria);
		if(!is_null($oFmFolder))
		{
			$this->iIdRootFolder = $oFmFolder->getId();
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
			$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id='".$babDB->db_escape_string($this->fileid)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['bgroup'] == 'Y' && $arr['state'] == '' && $arr['confirmed'] == 'Y' && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id_owner']))
				{
				$this->res = $babDB->db_query("select ff.*, fft.name from ".BAB_FM_FIELDSVAL_TBL." ff LEFT JOIN ".BAB_FM_FIELDS_TBL." fft on fft.id = ff.id_field where id_file='".$babDB->db_escape_string($this->fileid)."' and id_folder='".$babDB->db_escape_string($arr['id_owner'])."'");
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
		$lang = $ctx->get_value('lang');
		$delegationid = (int) $ctx->get_value('delegationid');
		
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
			$this->excludetopicid = $ctx->get_value('excludetopicid');
			if ( $this->excludetopicid !== false && $this->excludetopicid !== '' )
				{
				$this->topicid = array_diff($this->topicid, explode(',', $this->excludetopicid));
				}

			$archive = $ctx->get_value('archive');
			if( $archive === false || $archive === '')
				$archive = "no";

			switch(strtoupper($archive))
			{
				case 'NO': $archive = " AND at.archive='N' "; break;
				case 'YES': $archive = " AND at.archive='Y' "; break;
				default: $archive = ''; break;

			}

			$req = 
				'SELECT ' . 
					'at.id, ' .
					'at.restriction ' .
				'FROM ' . 
					BAB_ARTICLES_TBL . ' at ';
					
			$sDelegation = ' ';	
			if(0 != $delegationid)	
			{
				$req .= 
					'LEFT JOIN ' .
						BAB_TOPICS_TBL . ' tp ON tp.id = at.id_topic ' .
					'LEFT JOIN ' .
						BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';
						
				$sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			}
					
			$req .= 
				'WHERE ' .
					'at.id_topic IN (' . $babDB->quote($this->topicid). ') AND ' .
					'(at.date_publication = \'0000-00-00 00:00:00\' OR at.date_publication <= now()) ' .
					$sDelegation;

			if( $this->nbdays !== false)
				{
				$req .= " AND at.date >= DATE_ADD(\"".$babDB->db_escape_string($babBody->lastlog)."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
				}

			
			if ($lang !== false ) {
				$req .= " AND at.lang='".$babDB->db_escape_string($lang)."'";
			}
			
			$req .= $archive;

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = 'desc';

			$orderby = $ctx->get_value('orderby');
			if( $orderby === false || $orderby === '' )
				$orderby = 'at.date_modification';
			else
				{
				switch(strtolower($orderby))
					{
					case 'creation': $orderby = 'at.date'; break;
					case 'publication': $orderby = 'at.date_publication'; break;
					case 'modification':
					default:
						$orderby = 'at.date_modification'; break;
					}
				}

			switch(strtoupper($order))
			{
				case 'ASC': $order = $orderby.' ASC'; break;
				case 'RAND': $order = 'rand()'; break;
				case 'DESC':
				default: $order = $orderby.' DESC'; break;
			}

			$req .= ' ORDER BY ' . $order;

			if( $this->last !== false)
				$req .= ' LIMIT 0, ' . $babDB->db_escape_string($this->last);

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
				$this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_FILES_TBL." aft on at.id=aft.id_article where at.id IN (".$babDB->quote($this->IdEntries).") group by at.id order by ".$order);
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


		$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where id_cat='".$babDB->db_escape_string($idparent)."' AND id IN(".$babDB->quote(array_keys($GLOBALS['babBody']->topview)).")");
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
			$this->replace_ref($arr['head'], 'bab_article_head');
			$this->replace_ref($arr['body'], 'bab_article_body');
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
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date_modification'])); /* for compatibility */
			$this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
			$this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
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
		$delegationid = (int) $ctx->get_value('delegationid');

		if( $this->articleid === false || $this->articleid === '' )
			$arrid = array();
		else
			$arrid = explode(',', $this->articleid);

		$req = '';
		$topview = ' ';
		if( count($babBody->topview) > 0 )
			{
				
				$req = 
					'SELECT ' .
						'* ' .
					'FROM ' .
						BAB_COMMENTS_TBL . ' ';
				
			if( count($arrid) > 0 )
				{
				$topview = "where id_article IN (".$babDB->quote($arrid).") and confirmed='Y' and id_topic IN (".$babDB->quote(array_keys($babBody->topview)).")";
				}
			else
				{
				$topview = "where confirmed='Y' and id_topic IN (".$babDB->quote(array_keys($babBody->topview)).")";
				}

			$sDelegation = ' ';	
			if(0 != $delegationid)	
			{
				$req .= 
					'LEFT JOIN ' .
						BAB_TOPICS_TBL . ' tp ON tp.id = id_topic ' .
					'LEFT JOIN ' .
						BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';
						
				$sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			}
				
			$req .= $topview . $sDelegation;
			}
		
			
		if( $req != '' )
			{
			if( $this->nbdays !== false)
				$req .= " and date >= DATE_ADD(\"".$babDB->db_escape_string($babBody->lastlog)."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";

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
				$req .= " limit 0, ".$babDB->db_escape_string($this->last);
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
			if( $arr['id_author'] )
				{
				$this->ctx->curctx->push('CommentAuthor', bab_getUserName($arr['id_author']));
				}
			else
				{
				$this->ctx->curctx->push('CommentAuthor', $arr['name']);
				}

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
		$delegationid = (int) $ctx->get_value('delegationid');

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
			$sDelegation = ' ';	
			if(0 != $delegationid)	
			{
				$sDelegation = ' AND f.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			}

		
			$req = "SELECT p.*, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y'" . $sDelegation . "and t.forum IN (".$babDB->quote($arr).") and p.confirmed='Y'";	

			if( $this->nbdays !== false)
				{
				$req .= " and p.date >= DATE_ADD(\"".$babDB->db_escape_string($babBody->lastlog)."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
				}


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
				$req .= " limit 0, ".$babDB->db_escape_string($this->last);

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
			$this->replace_ref($arr['message'], 'bab_forum_post');
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
		$delegationid = (int) $ctx->get_value('delegationid');

		$sDelegation = ' ';	
		if(0 != $delegationid)	
		{
			$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		if( $this->forumid === false || $this->forumid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->forumid);

		if( count($arr) > 0 )
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y'" . $sDelegation . "and t.forum IN (".$babDB->quote($arr).") and p.confirmed='Y' and p.id_parent='0'";			
			}
		else
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y'" . $sDelegation . "and p.confirmed='Y' and p.id_parent='0'";			
			}

		if( $this->nbdays !== false)
			$req .= " and p.date >= DATE_ADD(\"".$babDB->db_escape_string($babBody->lastlog)."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";

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
			$this->res = $babDB->db_query("select * from ".BAB_POSTS_TBL." p where id IN (".$babDB->quote($this->arrid).") order by ".$order);
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
			$this->replace_ref($arr['message'], 'bab_forum_post');
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

	var $oFmFolderSet = null;

	function bab_RecentFiles($ctx)
		{
		global $babBody, $BAB_SESS_USERID, $babDB;
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
		
		$this->bab_handler($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->folderid = $ctx->get_value('folderid');
		$delegationid = (int) $ctx->get_value('delegationid');

		$this->oFmFolderSet = new BAB_FmFolderSet();
		
		$sDelegation = ' ';	
		if(0 != $delegationid)	
		{
			$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		if($this->folderid === false || $this->folderid === '')
			{
			$arr = array();
			}
		else
			{
			$arr = explode(',', $this->folderid);
			}
		
		if( count($arr) == 0 )
			{
			$req = "select * from ".BAB_FM_FOLDERS_TBL." where active='Y'" . $sDelegation;
			}
		else
			{
			$req = "select * from ".BAB_FM_FOLDERS_TBL." where active='Y' and id IN (".$babDB->quote($arr).")" . $sDelegation;
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
				$req .= " and f.path like '%".$babDB->db_escape_like($path.'/')."'";
				}

			$req .= " and f.id_owner IN (".$babDB->quote($arrid).")";

			if( $this->nbdays !== false)
				{
				$req .= " and f.modified >= DATE_ADD(\"".$babDB->db_escape_string($babBody->lastlog)."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
				}

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				{
				$order = "desc";
				}

			switch(strtoupper($order))
			{
				case "ASC": $order = 'f.modified ASC'; break;
				case "RAND": $order = 'rand()'; break;
				case "DESC":
				default: $order = 'f.modified DESC'; break;
			}

			$req .= ' group by f.id';

			$req .= ' order by '.$order;
			
			if( $this->last !== false)
				{
				$req .= " limit 0, ".$babDB->db_escape_string($this->last);
				}

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

			$sPath = removeEndSlah($arr['path']);
			$iid = $arr['id_owner'];

			$sName = getFirstPath($arr['path']);
			$sRelativePath = '';
			$iIdDgOwner = 0;
			
			$oName =& $this->oFmFolderSet->aField['sName'];
			$oRelativePath =& $this->oFmFolderSet->aField['sRelativePath'];
			$oIdDgOwner =& $this->oFmFolderSet->aField['iIdDgOwner'];

			$oCriteria = $oName->in($sName);
			$oCriteria = $oCriteria->_and($oRelativePath->in(''));
			$oCriteria = $oCriteria->_and($oIdDgOwner->in($arr['iIdDgOwner']));
			
			$oFmFolder = $this->oFmFolderSet->get($oCriteria);
			$iId = $oFmFolder->getId();

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileId', $arr['id']);
			$this->ctx->curctx->push('FileName', $arr['name']);
			$this->ctx->curctx->push('FilePath', $arr['path']);
			$this->ctx->curctx->push('FileDescription', $arr['description']);
			$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$iId."&gr=".$arr['bgroup']."&path=".urlencode($sPath));
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$iId."&gr=".$arr['bgroup']."&path=".urlencode($sPath)."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$iId."&gr=".$arr['bgroup']."&path=".urlencode($sPath)."&file=".urlencode($arr['name']) . '&idf=' . $arr['id']);
			$this->ctx->curctx->push('FileAuthor', $arr['author']);
			$this->ctx->curctx->push('FileModifiedBy', $arr['modifiedby']);
			$this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));
			
			$sFullPathname = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $arr['path'] . $arr['name'];
			if (file_exists($sFullPathname))
			{
				$this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathname)));
			}
			else
			{
				$this->ctx->curctx->push('FileSize', '???');
			}
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
			$delegationid = (int) $ctx->get_value('delegationid');

			$req = 
				'select ' .
					'adt.id, ' .
					'adt.id_topic ' .
				'FROM ' .
					BAB_ART_DRAFTS_TBL . ' adt ';
			
			$sDelegation = ' ';	
			if(0 != $delegationid)	
			{
				$req .= 
					'LEFT JOIN ' .
						BAB_TOPICS_TBL . ' tp ON tp.id = id_topic ' .
					'LEFT JOIN ' .
						BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';
						
				$sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			}
			
					
			$req .= "where adt.result='".BAB_ART_STATUS_WAIT."'" . $sDelegation;

			
			if( $this->topicid !== false && $this->topicid !== '' )
				{
				$req .= " and adt.id_topic IN (".$babDB->quote(explode(',', $this->topicid)).")";
				}

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
				$this->res = $babDB->db_query("select adt.*, count(adft.id) as nfiles from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adt.id=adft.id_draft where adt.id IN (".$babDB->quote($this->IdEntries).") group by adt.id order by adt.date_submission desc");
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
			$this->replace_ref($arr['head'], 'bab_article_head');
			$this->replace_ref($arr['body'], 'bab_article_body');
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
			$delegationid = (int) $ctx->get_value('delegationid');

			$req = "select c.id, c.id_topic from ".BAB_COMMENTS_TBL." c ";
			
			$sDelegation = ' ';	
			if(0 != $delegationid)	
			{
				$req .= 
					'LEFT JOIN ' .
						BAB_TOPICS_TBL . ' tp ON tp.id = id_topic ' .
					'LEFT JOIN ' .
						BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';
						
				$sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			}
					
			$req .= "where c.confirmed='N'" . $sDelegation;

			
			if( $this->articleid !== false && $this->articleid !== '' )
				{
				$req .= " and c.id_article IN (".$babDB->quote(explode(',', $this->articleid)).")";
				}

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
				$this->res = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id IN (".$babDB->quote($this->IdEntries).") order by date desc");
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
			if( $arr['id_author'] )
				{
				$this->ctx->curctx->push('CommentAuthor', bab_getUserName($arr['id_author']));
				}
			else
				{
				$this->ctx->curctx->push('CommentAuthor', $arr['name']);
				}
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
		$delegationid = (int) $ctx->get_value('delegationid');

		$sDelegation = ' ';	
		$sLeftJoin = ' ';
		if(0 != $delegationid)	
		{
			$sLeftJoin = 'LEFT JOIN ' .
				BAB_FM_FOLDERS_TBL . ' fld ON fld.id = id_owner ';
			$sDelegation = ' AND fld.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		$userid = $ctx->get_value('userid');
		if( $userid === false || $userid === '' )
			{
			$userid = $GLOBALS['BAB_SESS_USERID'];
			}

		if( $userid != '')
			{
			$this->folderid = $ctx->get_value('folderid');
			$req = "select f.id, f.idfai from ".BAB_FILES_TBL . " f " . $sLeftJoin . "where f.bgroup='Y' and f.confirmed='N'" . $sDelegation;
			if( $this->folderid !== false && $this->folderid !== '' )
				{
				$req .= " and f.id_owner IN (".$babDB->quote(explode(',', $this->folderid)).")";
				}

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
					$this->res = $babDB->db_query("select * from ".BAB_FILES_TBL." where id IN (".$babDB->quote($this->IdEntries).")");
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
			$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']));
			$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']) . '&idf=' . $arr['id']);
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
			$req .= " and id IN (".$babDB->quote(explode(',', $this->forumid)).")";
			}

		$delegationid = (int) $ctx->get_value('delegationid');
		$sDelegation = ' ';	
		if(0 != $delegationid)	
		{
			$req .= ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			
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
			$req = "SELECT p.*, t.forum  FROM  ".BAB_POSTS_TBL." p, ".BAB_FORUMS_TBL." f, ".BAB_THREADS_TBL." t WHERE p.confirmed ='N' AND t.forum = f.id AND t.id = p.id_thread and f.id IN (".$babDB->quote($arrf).")";

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
			$this->replace_ref($arr['message'], 'bab_forum_post');
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
		$delegationid = (int) $ctx->get_value('delegationid');

		$req = "select id from ".BAB_FAQCAT_TBL;
		
		$isFaqId = ( $faqid !== false && $faqid !== '' );
		if( $isFaqId )
		{
			$req .= " where id IN (".$babDB->quote(explode(',', $faqid)).")";
		}

		$sDelegation = ' ';	
		if(0 != $delegationid)	
		{
			$sDelegation = 'id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
			$req .= ($isFaqId) ? (' AND ' . $sDelegation) : (' WHERE ' . $sDelegation);
		}

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
			$this->res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id IN (".$babDB->quote($this->IdEntries).")");
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
		$this->res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($ctx->get_value('faqid'))."'");
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
				$this->faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($faqid)."'"));
				$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$babDB->db_escape_string($faqid)."'");
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
			$res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat IN (".$babDB->quote(explode(',', $faqsubcatid)).")");
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
			$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id IN (".$babDB->quote($this->IdEntries).")");
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
			$faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
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
			$req .= " where idcat IN (".$babDB->quote(explode(',', $faqid)).")";
			if( $faqsubcatid !== false && $faqsubcatid !== '' )
				{
				$req .= " and id_subcat IN (".$babDB->quote(explode(',', $faqsubcatid)).")";
				}
			}
		elseif( $faqsubcatid !== false && $faqsubcatid !== '' )
			{
			$req .= " where id_subcat IN (".$babDB->quote(explode(',', $faqsubcatid)).")";
			}

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
			$this->res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id IN (".$babDB->quote($this->IdEntries).")");
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
			$this->replace_ref($arr['response'], 'bab_faq_response');
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
		$this->res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($ctx->get_value('questionid'))."'");
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
			$this->replace_ref($arr['response'], 'bab_faq_response');
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
		$delegationid = (int) $ctx->get_value('delegationid');

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
				if(0 != $delegationid && $delegationid != $row['value']['id_dgowner'])
					continue;
				$this->IdEntries[] = $row[0];
				}

			reset($babBody->icalendars->rescal);
			while( $row=each($babBody->icalendars->rescal) ) 
				{ 
				if(0 != $delegationid && $delegationid != $row['value']['id_dgowner'])
					continue;
					
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
						if(0 != $delegationid && $delegationid != $row['value']['id_dgowner'])
							continue;
						$this->IdEntries[] = $row[0];
						}
					break;

				case BAB_CAL_RES_TYPE:
					reset($babBody->icalendars->rescal);
					while( $row=each($babBody->icalendars->rescal) ) 
						{ 
						if(0 != $delegationid && $delegationid != $row['value']['id_dgowner'])
							continue;
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



/**
 * OCCalendarEvents
 * 
 */
class bab_CalendarEvents extends bab_handler
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	var $cal_groups 		= 1;
	var $cal_resources		= 1;
	var $cal_users			= 1;
	var $cal_default_users 	= 1; // if empty calendarid, get all accessibles user calendars

	function bab_CalendarEvents( &$ctx)
	{
		global $babBody, $babDB;
		$this->bab_handler($ctx);
		$babBody->icalendars->initializeCalendars();
		$calendarid = $ctx->get_value('calendarid');
		$delegationid = (int) $ctx->get_value('delegationid');
		$filter = strtoupper($ctx->get_value('filter')) !== "NO"; 
			

		include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
		include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

		$startdate = $ctx->get_value('date');
		if( $startdate === false || $startdate === '' )
			{
			$startdate = BAB_dateTime::now();
			}
		else
			{
			$startdate = BAB_dateTime::fromIsoDateTime($startdate);
			}

		$limit = $ctx->get_value('limit');
		$lf = $lr = 0;

		if( $limit !== false && $limit !== '' )
			{
			$limit = explode(',', $limit);
			if( count($limit) > 1 )
				{
				$lf = empty($limit[0])?0: (int) $limit[0];
				$lr = empty($limit[1])?0: (int) $limit[1];
				}
			elseif ( count($limit) == 1 )
				{
				$lf = empty($limit[0])?0: (int) $limit[0];
				}
			}


		$enddate = $startdate->cloneDate();
		$startdate->add((-1*$lf), BAB_DATETIME_DAY);
		$enddate->add($lr, BAB_DATETIME_DAY);


		$this->whObj = new bab_userWorkingHours($startdate, $enddate);

		if ($filter) {
			$this->getUserCalendars($calendarid, $delegationid);
		} else {
			$this->getCalendars($calendarid);
		}

		$categoryid = $ctx->get_value('categoryid');
		if( $categoryid !== false && $categoryid !== '' )
			{
			$this->whObj->category = $categoryid;
		}

		
		$this->whObj->createPeriods(BAB_PERIOD_NWDAY | BAB_PERIOD_WORKING | BAB_PERIOD_VACATION | BAB_PERIOD_CALEVENT);
		$this->whObj->orderBoundaries();
		
		$this->events = $this->whObj->getEventsBetween(
			$startdate->getTimeStamp(), 
			$enddate->getTimeStamp(), 
			BAB_PERIOD_NWDAY | BAB_PERIOD_VACATION | BAB_PERIOD_CALEVENT 
		);

		$this->count = count($this->events);
		$this->ctx->curctx->push('CCount', $this->count);
	}


	/**
 	 * Get available calendar without filter
	 */
	function getCalendars($calendarid) {
		global $babDB;
		
		if (empty($calendarid)) {
			trigger_error('filter=NO must be used with calendarid');
			return;
		}

		$res = $babDB->db_query("select id, type, owner from ".BAB_CALENDAR_TBL." where id IN (".$babDB->quote($calendarid).") and  actif='Y'");
		while( $arr = $babDB->db_fetch_array($res) )
			{
			switch($arr['type']) {
				case BAB_CAL_USER_TYPE:
					if ($this->cal_users) {
						$this->whObj->addCalendar($arr['id']);
						$this->whObj->addIdUser($arr['owner']);
					}
					break;
				
				case BAB_CAL_PUB_TYPE:
					if ($this->cal_groups) {
						$this->whObj->addCalendar($arr['id']);
					}
					break;

				case BAB_CAL_RES_TYPE:
					if ($this->cal_resources) {
						$this->whObj->addCalendar($arr['id']);
					}
					break;
			}
		}
	}

	/**
 	 * Get available calendar with filter
	 */
	function getUserCalendars($calendarid, $delegationid) {
		global $babBody;
		$rr = empty($calendarid) ? false : array_flip(explode(',', $calendarid));

		if( $babBody->icalendars->id_percal && $this->cal_users) {
			if (false === $rr || isset($rr[$babBody->icalendars->id_percal])) {
				$this->whObj->addCalendar($babBody->icalendars->id_percal);
				$this->whObj->addIdUser($GLOBALS['BAB_SESS_USERID']);
			}
		}

		if ($this->cal_users) {
			
			if ($rr || (false === $rr && $this->cal_default_users)) {
			
				foreach( $babBody->icalendars->usercal as $key => $val ) {
					if (false === $rr || isset($rr[$key])) {
						$this->whObj->addIdUser($babBody->icalendars->getCalendarOwner($key));
						$this->whObj->addCalendar($key);
					}
				}
			}
		}

		if ($this->cal_resources) {
			foreach( $babBody->icalendars->rescal as $key => $val ) {
				if(0 != $delegationid && $delegationid != $val['id_dgowner'])
					continue;
				if (false === $rr || isset($rr[$key])) {
					$this->whObj->addCalendar($key);
				}
			}
		}

		if ($this->cal_groups) {
			foreach( $babBody->icalendars->pubcal as $key => $val ) {
				if(0 != $delegationid && $delegationid != $val['id_dgowner'])
					continue;
				if (false === $rr || isset($rr[$key])) {
					$this->whObj->addCalendar($key);
				}
			}
		}
	}

	/**
 	 * for deprecated attribute idgroup, iduser, idresource
	 * in events contener
	 * idcalendar is better
	 * @param object	$ctx
	 * @param array 	$owner
	 */
	function getCalendarsFromOwner(&$ctx, $owner) {
		global $babDB;
		$calendars = array();
		$res = $babDB->db_query("SELECT id FROM ".BAB_CALENDAR_TBL." WHERE owner IN(".$babDB->quote($owner).")");
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$calendars[] = $arr['id'];
		}

		$ctx->curctx->push('calendarid', implode(',',$calendars));
	}

	
	function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			list(, $p) = each($this->events);
			$arr = $p->getData();

			$id_category = isset($arr['id_cat']) ? $arr['id_cat'] : 0;

			$id_event = isset($arr['id']) ? $arr['id'] : 0;

			if (isset($arr['nbowners']) && (0 < $arr['nbowners'])) {
				$arr['id_cal'] = implode(',',$this->whObj->id_calendars);
			}

			$calid_param = !empty($arr['id_cal']) ? '&idcal='.$arr['id_cal'] : '';
			$description = $p->getProperty('DESCRIPTION');
			$this->replace_ref($description, 'bab_calendar_event');
			$date = explode(' ', $p->getProperty('DTSTART'));
			$date = explode('-', $date[0]);
			$date = $date[0].",".$date[1].",".$date[2];

			$this->ctx->curctx->push('CIndex'					, $this->idx);
			$this->ctx->curctx->push('EventTitle'				, $p->getProperty('SUMMARY'));
			$this->ctx->curctx->push('EventDescription'			, $description);
			$this->ctx->curctx->push('EventLocation'			, $p->getProperty('LOCATION'));
			$this->ctx->curctx->push('EventBeginDate'			, bab_mktime($p->getProperty('DTSTART')));
			$this->ctx->curctx->push('EventEndDate'				, bab_mktime($p->getProperty('DTEND')));
			$this->ctx->curctx->push('EventCategoryId'			, $id_category);
			$this->ctx->curctx->push('EventCategoryColor'		, $p->color);
			$this->ctx->curctx->push('EventUrl'					, $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$id_event.$calid_param);
			$this->ctx->curctx->push('EventCalendarUrl'			, $GLOBALS['babUrlScript']."?tg=calmonth".$calid_param."&date=".$date);
			$this->ctx->curctx->push('EventCategoriesPopupUrl'	, $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc".$calid_param);
			$this->ctx->curctx->push('EventCategoryName'		, $p->getProperty('CATEGORIES'));
			
			$EventOwner = isset($arr['id_cal']) ? bab_getCalendarOwner($arr['id_cal']) : '';
			
			$this->ctx->curctx->push('EventOwner'				, $EventOwner);
			$this->ctx->curctx->push('EventUpdateDate', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('EventUpdateAuthor', $arr['id_creator']);
				
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



class bab_CalendarUserEvents extends bab_CalendarEvents
{
	function bab_CalendarUserEvents(&$ctx)
	{
		$this->cal_users 			= 1;
		$this->cal_groups			= 0;
		$this->cal_resources		= 0;
		$this->cal_default_users	= 0;

		$userid = $ctx->get_value('userid');

		if (false !== $userid && '' !== $userid) {
			$this->getCalendarsFromOwner($ctx, explode(',',$userid));
		}

		$this->bab_CalendarEvents($ctx);
	}
}


class bab_CalendarGroupEvents extends bab_CalendarEvents
{
	function bab_CalendarGroupEvents(&$ctx)
	{
		$this->cal_users 		= 0;
		$this->cal_groups		= 1;
		$this->cal_resources	= 0;

		$groupid = $ctx->get_value('groupid');

		if (false !== $groupid && '' !== $groupid) {
			$this->getCalendarsFromOwner($ctx, explode(',',$groupid));
		}

		$this->bab_CalendarEvents($ctx);
	}
}


class bab_CalendarResourceEvents extends bab_CalendarEvents
{
	function bab_CalendarResourceEvents(&$ctx)
	{
		$this->cal_users 		= 0;
		$this->cal_groups		= 0;
		$this->cal_resources	= 1;

		$resourceid = $ctx->get_value('resourceid');

		if (false !== $resourceid && '' !== $resourceid) {
			$this->getCalendarsFromOwner($ctx, explode(',',$resourceid));
		}

		$this->bab_CalendarEvents($ctx);
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
		
		$userid = $ctx->get_value('userid');
		if( $userid === false  )
		{
			$userid = $GLOBALS['BAB_SESS_USERID'];
		}


		if( $userid != "" )
			{
			$all = $ctx->get_value('all');

			if ( $all !== false && strtoupper($all) == "YES") 
				$all = true;
			else
				$all = false;

			$groupid = $ctx->get_value('groupid');
			if( $groupid !== false && $groupid !== '' )
				{
				$groupid = explode(',', $groupid);
				}
			else
				{
				$groupid = array();
				}

			$childs = $ctx->get_value('childs');
			if ( $childs !== false && strtoupper($childs) == "YES")
				{
				include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
				$rr = $groupid;
				$tree = new bab_grptree();
				for( $k=0; $k < count($rr); $k++ )
					{
					$groups = $tree->getChilds($rr[$k]);
					if( is_array($groups) && count($groups) > 0 )
						{
						foreach ($groups as $arr)
							{
							if(!in_array($arr['id'], $rr))
								{
								$groupid[] = $arr['id'];
								}
							}
						}
					}
				}

			if( count($groupid))
				{
				list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($userid)."' and id_group IN (".$babDB->quote($groupid).")"));
				if( $all == false)
					{
					if( $total )
						{
						$this->count = 1;
						}
					}
				else
					{
					if( $total >= count($groupid))
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

class bab_OvmlArray extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	function bab_OvmlArray( &$ctx)
	{
		$this->count = 0;
		$this->bab_handler($ctx);
		$this->name = $ctx->get_value('name');
		$value = $ctx->get_value('value');
		if( preg_match_all("/(.*?)\[([^\]]+)\]/", $value, $m2) > 0)
		{
			$this->IdEntries = $ctx->get_value($m2[1][0]);
			for( $t=0; $t < count($m2[2]); $t++)
				{
				if( isset($this->IdEntries[$m2[2][$t]]) )
					{
					$this->IdEntries = $this->IdEntries[$m2[2][$t]];
					}
				else
					break;
				}
		}
		else
		{
			$this->IdEntries = $ctx->get_value($value);
		}
		if( is_array($this->IdEntries))
			{
			$this->ctx->curctx->push($this->name, $this->IdEntries);
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
		if( $this->idx < $this->count )
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			list( $key, $val) = each ($this->IdEntries );
			$this->ctx->curctx->push($this->name.'Key', $key);
			$this->ctx->curctx->push($this->name.'Value', $val);
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


class bab_OvmlArrayFields extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	function bab_OvmlArrayFields( &$ctx)
	{
		$this->count = 0;
		$this->bab_handler($ctx);
		$this->name = $ctx->get_value('name');
		$value = $ctx->get_value('value');
		if( preg_match_all("/(.*?)\[([^\]]+)\]/", $value, $m2) > 0)
		{
			$this->IdEntries = $ctx->get_value($m2[1][0]);
			for( $t=0; $t < count($m2[2]); $t++)
				{
				if( isset($this->IdEntries[$m2[2][$t]]) )
					{
					$this->IdEntries = $this->IdEntries[$m2[2][$t]];
					}
				else
					break;
				}
		}
		else
		{
			$this->IdEntries = $ctx->get_value($value);

		}

		if( is_array($this->IdEntries))
			{
			$this->ctx->curctx->push($this->name, $this->IdEntries);
			$this->count = 1; //count($this->IdEntries);
			}
		else
			{
			$this->count = 0;
			}

		$this->ctx->curctx->push('CCount', $this->count);

	}

	function getnext()
	{
		if( $this->idx < $this->count )
		{
			foreach( $this->IdEntries as $key => $val)
				{
				$this->ctx->curctx->push($key, $val);
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


class bab_OvmlSoap extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	function bab_OvmlSoap( &$ctx)
	{
		$this->count = 1;
		$this->bab_handler($ctx);
		$vars = $ctx->get_variables($ctx->get_currentContextname());
		if( isset($vars['apiserver']) && isset($vars['container']))
			{
			$apiserver = $vars['apiserver']; unset($vars['apiserver']);
			$args = array();
			$args['container'] = $vars['container']; unset($vars['container']);
			if( isset($vars['debug']))
				{
				$debug = $vars['debug']; 
				unset($vars['debug']);
				}
			else
				{
				$debug = false;
				}

			if( isset($vars['proxyhost']))
				{
				$proxyhost = $vars['proxyhost']; 
				unset($vars['proxyhost']);
				if( isset($vars['proxyport']))
					{
					$proxyport = $vars['proxyport']; 
					unset($vars['proxyport']);
					}
				else
					{
					$proxyport = false;
					}
				if( isset($vars['proxyusername']))
					{
					$proxyusername = $vars['proxyusername']; 
					unset($vars['proxyusername']);
					}
				else
					{
					$proxyusername = false;
					}
				if( isset($vars['proxypassword']))
					{
					$proxypassword = $vars['proxypassword']; 
					unset($vars['proxypassword']);
					}
				else
					{
					$proxypassword = false;
					}
				}
			else
				{
				$proxyhost = false;
				}

			$args['args'] = array();
			foreach($vars as $key => $val )
				{
				$args['args'][] = array( 'name'=>$key, 'value' => $val);
				}

			
			include_once $GLOBALS['babInstallPath']."utilit/nusoap/nusoap.php";

			if( !empty($proxyhost))
				{
				$soapclient = new soapclient_b($apiserver, false, $proxyhost, $proxyport, $proxyusername, $proxypassword);
				}
			else
				{
			$soapclient = new soapclient_b($apiserver);
				}
			$this->IdEntries = $soapclient->call('babSoapOvml', $args, '');
			$err = $soapclient->getError();
			if( $debug )
				{
				$this->ctx->curctx->push('babSoapDebug', $soapclient->getDebug());
				}
			bab_debug($soapclient->getDebug());
			if( $err )
				{
				$this->ctx->curctx->push('babSoapError', $err);
				$this->ctx->curctx->push('babSoapResponse', $soapclient->response);
				$this->ctx->curctx->push('babSoapRequest', $soapclient->request);
				if( $soapclient->fault )
					{
					foreach( $this->IdEntries as $key=>$val )
						{
						$this->ctx->curctx->push($key, $val);
						}
					}
				}

			$this->count = count($this->IdEntries);
			}
	}

	function getnext()
	{
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			for( $i=0; $i < count($this->IdEntries[$this->idx]); $i++ )
				{
				$this->ctx->curctx->push($this->IdEntries[$this->idx][$i]['name'], $this->IdEntries[$this->idx][$i]['value']);
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

class bab_Soap extends bab_handler
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	function bab_Soap( &$ctx)
	{
		$this->count = 1;
		$this->bab_handler($ctx);
		$vars = $ctx->get_variables($ctx->get_currentContextname());
		if( isset($vars['apiserver']) && isset($vars['apicall']))
			{
			$apiserver = $vars['apiserver']; unset($vars['apiserver']);
			$apicall = $vars['apicall']; unset($vars['apicall']);
			if( isset($vars['debug']))
				{
				$debug = $vars['debug']; 
				unset($vars['debug']);
				}
			else
				{
				$debug = false;
				}
			if( isset($vars['apinamespace']))
				{
				$apinamespace = $vars['apinamespace']; 
				unset($vars['apinamespace']);
				}
			else
				{
				$apinamespace = '';
				}

			if( isset($vars['proxyhost']))
				{
				$proxyhost = $vars['proxyhost']; 
				unset($vars['proxyhost']);
				if( isset($vars['proxyport']))
					{
					$proxyport = $vars['proxyport']; 
					unset($vars['proxyport']);
					}
				else
					{
					$proxyport = false;
					}
				if( isset($vars['proxyusername']))
					{
					$proxyusername = $vars['proxyusername']; 
					unset($vars['proxyusername']);
					}
				else
					{
					$proxyusername = false;
					}
				if( isset($vars['proxypassword']))
					{
					$proxypassword = $vars['proxypassword']; 
					unset($vars['proxypassword']);
					}
				else
					{
					$proxypassword = false;
					}
				}
			else
				{
				$proxyhost = false;
				}


			$args = array();
			foreach($vars as $key => $val )
				{
				$args[$key] = $val;
				}

			include_once $GLOBALS['babInstallPath']."utilit/nusoap/nusoap.php";
			if( !empty($proxyhost))
				{
				$soapclient = new soapclient_b($apiserver, false, $proxyhost, $proxyport, $proxyusername, $proxypassword);
				}
			else
				{
			$soapclient = new soapclient_b($apiserver);
				}
			$this->IdEntries = $soapclient->call($apicall, $args, $apinamespace);
			$err = $soapclient->getError();
			if( $debug )
				{
				$this->ctx->curctx->push('babSoapDebug', $soapclient->getDebug());
				}
			bab_debug($soapclient->getDebug());

			if( $err )
				{
				$this->ctx->curctx->push('babSoapError', $err);
				$this->ctx->curctx->push('babSoapResponse', $soapclient->response);
				$this->ctx->curctx->push('babSoapRequest', $soapclient->request);
				if( $soapclient->fault )
					{
					foreach( $this->IdEntries as $key=>$val )
						{
						$this->ctx->curctx->push($key, $val);
						}
					}
				}
			else
				{
				$this->ctx->curctx->push('SoapResult', $this->IdEntries);
				//print_r($this->IdEntries);
				}
			//$this->count = count($this->IdEntries);
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
	var $content;

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

	function setContent($txt) {
		$this->content = $txt;
	}

	function getcontent() {
		return $this->content;
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
	$this->gctx->push("babSiteSlogan", $babBody->babsite['babslogan']);
	if( $GLOBALS['BAB_SESS_USERID'] != "" )
		{
		$this->gctx->push("babUserName", $GLOBALS['BAB_SESS_USER']);
		}
	else
		{
		$this->gctx->push("babUserName", 0);
		}
//	$this->gctx->push("babCurrentDate", mktime());
	$this->gctx->push("babCurrentDate", time());

	foreach($args as $variable => $contents)
		{
		$this->gctx->push($variable, $contents);
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

function get_currentContextname()
	{
	return $this->curctx->name;
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

function cast($str)
	{
	if( !empty($str) && $str{0} == '(' )
		{
		if(preg_match('/\(\s*(.*?)\s*\)(.*)/',$str, $m))
			{
			switch($m[1])
				{
				case 'bool':
				case 'boolean':
					return (bool)$m[2];
					break;
				case 'integer':
				case 'int':
					return (int)$m[2];
					break;
				case 'float':
				case 'double':
				case 'real':
					return (float)$m[2];
					break;
				case 'string':
					return (string)$m[2];
					break;
				case 'var':
				case 'variable':
					return $this->get_value($m[2]);
					break;
				}
			}
		}
	return $str;
	}

function getArgs($str)
	{
	$args = array();
	
	if(preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $this->vars_replace($str), $mm))
		{
		for( $j = 0; $j< count($mm[1]); $j++)
			{
			$args[$mm[1][$j]] = $this->cast($mm[3][$j]);
			}
		}
	return $args;
	}

function handle_tag( $handler, $txt, $args, $fprint = 'printout' )
	{
	$out = '';
	$handler = "bab_".$handler;
	if( !class_exists($handler))
		{
		if( !strncmp($handler, "bab_DbDir", strlen("bab_DbDir")))
			{
			include_once $GLOBALS['babInstallPath']."utilit/ovmldir.php";
			}
		elseif( !strncmp($handler, "bab_Delegation", strlen("bab_Delegation")))
			{
			include_once $GLOBALS['babInstallPath']."utilit/ovmldeleg.php";
			}
		elseif( !strncmp($handler, 'bab_Tm', strlen('bab_Tm')))
			{
			include_once $GLOBALS['babInstallPath'].'utilit/ovmltm.php';
			}
		}


	if( class_exists($handler))
		{
		$ctx = new bab_context($handler);
		$ctx->setContent($txt);
		$this->push_ctx($ctx);

		foreach( $args as $key => $val )
			{
			$this->curctx->push($key, $val);
			}
		$cls = new $handler($this);
		if( $fprint == 'object' )
			{
			return $cls;
			}
		$out = $cls->$fprint($txt);
		$this->pop_ctx();
		return $out;
		}
	else
		{
		if( $fprint == 'object' )
			{
			return null;
			}
		return $txt;
		}
	}

function format_output($val, $matches)
	{
	$saveas = false;
	$lhtmlentities = false;
	$ghtmlentities = $this->get_value('babHtmlEntities');

	if( $ghtmlentities === false )
		{
		$ghtmlentities = 0;
		}
	else
		{
		$ghtmlentities = intval($ghtmlentities);
		}

	foreach( $matches as $p => $v)
		{
		switch(strtolower(trim($p)))
			{
			case 'strlen':
				$arr = explode(',', $v );
				if( strlen($val) > $arr[0] )
					{
					$val = substr($val, 0, $v).$arr[1];
					$this->gctx->push('substr', 1);
					}
				else
					$this->gctx->push('substr', 0);
				break;
			case 'striptags':
				switch($v)
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
				switch($v)
					{
					case '0':
						$lhtmlentities = true; break;
					case '1':
						$lhtmlentities = true;
						$val = htmlentities($val); break;
					case '2':
						$lhtmlentities = true;
						$trans = get_html_translation_table(HTML_ENTITIES);
						$trans = array_flip($trans);
						$val = strtr($val, $trans);
						break;
					case '3':
						$lhtmlentities = true;
						$val = htmlspecialchars($val);
						break;
					}
				break;
			case 'stripslashes':
				if( $v == '1')
					$val = stripslashes($val);
				break;
			case 'urlencode':
				if( $v == '1')
					$val = urlencode($val);
				break;
			case 'jsencode':
				if( $v == '1')
					{
					$val = bab_toHtml($val, BAB_HTML_JS);
					}
				break;
			case 'strcase':
				switch($v)
					{
					case 'upper':
						$val = strtoupper($val); break;
					case 'lower':
						$val = strtolower($val); break;
					}
				break;
			case 'nlremove':
				if( $v == '1')
					$val = preg_replace("(\r\n|\n|\r)", "", $val);
				break;
			case 'trim':
				switch($v)
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
				if( $v == '1')
					$val = nl2br($val);
				break;
			case 'sprintf':
				$val = sprintf($v, $val);
				break;
			case 'date':
				$val = bab_formatDate($v, $val);
				break;
			case 'author':
				$val = bab_formatAuthor($v, $val);
				break;
			case 'saveas':
				$varname = $v;
				$saveas = true;
				break;
			case 'strtr':
				if( !empty($v))
				{
				$trans = array();
				for( $i =0; $i < strlen($v); $i +=2 )
					{
					$trans[substr($v, $i, 1)] = substr($v, $i+1, 1);
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
		{
		$this->gctx->push($varname, $val);
		}

	if( !$lhtmlentities && $ghtmlentities ) 
		{
		return bab_toHtml($val);
		}
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
					$params = array();
					if($this->match_args($this->vars_replace(trim($m[3][$i])), $mm))
						{
						for( $j = 0; $j< count($mm[1]); $j++)
							{
							$p = trim($mm[1][$j]);
							if( !empty($p))
								{
								$params[$p] = $mm[3][$j];
								}
							}
						}
					$val = $this->$handler($params);
					$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
					break;
				case BAB_TAG_VARIABLE:
					if( preg_match_all("/(.*?)\[([^\]]+)\]/", $m[2][$i], $m2) > 0)
					{
						$val = $this->get_value($m2[1][0]);
						for( $t=0; $t < count($m2[2]); $t++)
							{
							if( isset($val[$m2[2][$t]]) )
								{
								$val = $val[$m2[2][$t]];
								}
							else
								{
								$val = '';
								break;
								}
							}
					}
					else
					{
					$val = $this->get_value($m[2][$i]);
					}

					$args = $this->vars_replace(trim($m[3][$i]));
					if( $val !== false )
						{
						$params = array();
						if($this->match_args($args, $mm))
							{
							for( $j = 0; $j< count($mm[1]); $j++)
								{
								$p = trim($mm[1][$j]);
								if( !empty($p))
									{
									$params[$p] = $mm[3][$j];
									}
								}
							}
						$val = $this->format_output($val, $params);
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
			$out .= $this->handle_tag($m[2][$i], $m[5][$i], $this->getArgs($m[3][$i]));
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

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'text':
					$text = $v;
					break;
				case 'lang':
					$lang = $v;
					break;
				}
			}					
		return $this->format_output(bab_translate($text, "", $lang), $args);
		}
	return '';
	}

/* Web statistic */
function bab_WebStat($args)
	{
	if(count($args))
		{
		$name = '';
		$value = '';
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				case 'value':
					$value = $v;
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


function bab_SetCookie($args)
	{
	global $babBody;
	$name = "";
	$value = "";

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				case 'value':
					$value = $v;
					break;
				case 'expire': // seconds
					$expire = time() + $v;
					break;
				}
			}
			
		if( !empty($name))
			{
			if( !isset($expire))
				{
				setcookie($name, $value);
				}
			else
				{
				setcookie($name, $value, $expire);
				}
			}
		}
	}

function bab_GetCookie($args)
	{
	global $babBody;
	$name = "";

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				}
			}					

		if( !empty($name) && isset($_COOKIE[$name]))
			{
			$this->gctx->push($name, $_COOKIE[$name]);
			}
		}
	}
	
function bab_SetSessionVar($args)
	{
	global $babBody;
	$name = '';
	$value = '';

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				case 'value':
					$value = $v;
					break;
				}
			}
		if( $name !== '')
			{
			$_SESSION[$name] = $value;
			$this->gctx->push($name, $value);
			}
		}
	}


function bab_GetSessionVar($args)
	{
	global $babBody;
	$name = '';
	$value = '';

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				}
			}
		if( $name !== '' && isset($_SESSION[$name]))
			{
			$this->gctx->push($name, $_SESSION[$name]);
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

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					$global = true;
					break;
				case 'value':
					$value = $v;
					$global = false;
					switch($name)
					{
						case 'babSlogan': $GLOBALS['babSlogan'] = $value; break;
						case 'babTitle': $babBody->title = $value; break;
						case 'babError': $babBody->msgerror = $value; break;
						default: 
							$value = $this->cast($value);
							break;
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
	$name = '';

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				}
			}					

		if( !empty($name))
			{
			$value = $this->get_value($name);
			if( $value !== false )
				{
				return $value;
				}
			}
		}
	}

/* save a variable to global space if not already defined */
function bab_IfNotIsSet($args)
	{
	$name = "";
	$value = "";

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = $v;
					break;
				case 'value':
					$value = $v;
					break;
				}
			}

		if( $this->gctx->get($name) === false )
			{
			$this->gctx->push($name, $this->cast($value));
			}
		}
	}


/* save a array to global space */
function bab_PutArray($args)
	{
	$name = "";
	$arr = array();
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = trim($v);
					break;
				default:
					$arr[trim($p)] = $this->cast(trim($v));
					break;
			}
		}
	}

	$this->gctx->push($name, $arr);
	}

/* save a soap array type to global space */
function bab_PutSoapArray($args)
	{
	$name = "";
	$arr = array();
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					$name = trim($v);
					break;
				default:
					$arr[] = array('name'=> trim($p), 'value'=>$this->cast(trim($v)));
					break;
			}
		}
	}

	$this->gctx->push($name, $arr);
	}

/**
 * Experimental ( can be changed in futur )
 * Returns an HTTP Request javascript call 
 * 
 * @access  public 
 * @return  string	javascript call to bab_ajaxRequest() 
 * @param   url	http request
 * @param   output	elem:property like mydiv:innerHTML where to put ajax response
 * @param   action	GET|POST default GET
 * @param   indicator	HTML element to show when request is pending
*/

function bab_Ajax($args)
{
	global $babBody;

	$params = array();
	$url = '';
	$output = '';
	$action = 'GET';
	$indicator = '';



	if(count($args))
		{
		$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
		$babBody->addJavascriptFile($GLOBALS['babScriptPath']."babajax.js");

		foreach( $args as $p => $v)
			{
			$p = trim($p);
			switch(strtolower($p))
				{
				case 'url':
					$url = $v;
					break;
				case 'output':
					$output = $v;
					break;
				case 'action':
					$action = $v;
					break;
				case 'indicator':
					$indicator = $v;
					break;
				default:
					$params[] = $p.'='.$v;
					break;
				}
			}					
		return "bab_ajaxRequest('".$url."','".$action."','".$output."','".$indicator."','".implode('&',$params)."')";
		}
	return '';
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

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'expr1':
					$expr1 = $this->cast($v);
					break;
				case 'expr2':
					$expr2 = $this->cast($v);
					break;
				case 'saveas':
					$saveas = true;
					$varname = $v;
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
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'url':
					$url = $v;
					$purl = parse_url($url);
					break;
				}
			}
		return $this->format_output(preg_replace("/(src=|background=|href=)(['\"])([^'\">]*)(['\"])/e", '"\1\"".bab_rel2abs("\3", $purl)."\""', implode('', file($url))), $args);
		}
	}

function bab_Header($args)
	{
	$value = '';
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'value':
					$value = $v;
					break;
				}
			}
		header($value);
		}
	}


function bab_Recurse($args) {
	$handler = substr($this->curctx->getname(), 4);
	return $this->handle_tag($handler, $this->curctx->getcontent(), $args);	
}


function bab_Addon($args)
	{
	global $babBody;
	$output = '';
	if(count($args))
		{
		$function_args = array();
		foreach( $args as $p => $v)
			{
			switch(strtolower(trim($p)))
				{
				case 'name':
					foreach ($babBody->babaddons as $value)
						{
						if ($value['title'] == $v)
							{
							$addonid = $value['id'];
							break;
							}
						}
					break;
				
				case 'function':
					$function = $v;
					break;
				default:
					$function_args[] = $v;
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
	$replace = bab_replace_get();
	$replace->addIgnoreMacro('OVML');
	$txt = $this->handle_text($txt);
	$replace->removeIgnoreMacro('OVML');
	return $txt;
	}

} // end class


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