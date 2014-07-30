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



/**
 * OVML root functionality
 *
 *
 */
class Func_Ovml extends bab_functionality
{
	public function getDescription()
	{
		return bab_translate('Ovidentia Markup language');
	}
}



/**
 * OVML containers root functionality
 * Replace the old bab_handler
 *
 */
class Func_Ovml_Container extends Func_Ovml
{
	public $ctx;
	public $idx;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->ctx = $ctx;
		$this->idx = 0;
	}

	/**
	 * default description of OVML functionalities
	 * @see utilit/bab_functionality#getDescription()
	 */
	public function getDescription()
	{
		if (__CLASS__ === get_class($this)) {
			return bab_translate('All OVML containers');
		}


		$classname = explode('_',get_class($this));
		return BAB_TAG_CONTAINER. end($classname);
	}


	public function printout($txt)
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

	public function printoutws()
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

	/**
	 * Fetch the next container's element.
	 *
	 * @return bool		True if an element has been fetched, false if the container has reached the end.
	 */
	public function getnext()
	{
		return false;
	}




	/**
	 * Push editor content into context and apply editor transformations
	 *
	 * @param	string	$var				OVML variable name
	 * @param	string	$txt
	 * @param	string	$txtFormat			The text format (html, text...) corresponding to the format of the wysiwyg editor.
	 * @param	string	$editor				editor ID
	 */
	protected function pushEditor($var, $txt, $txtFormat, $editor)
	{
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
		$editor = new bab_contentEditor($editor);
		$editor->setContent($txt);
		$editor->setFormat($txtFormat);
		$txt = $editor->getHtml();

		$this->ctx->curctx->push($var, $txt);

		if ('html' === strtolower($txtFormat))
		{
			$this->ctx->curctx->setFormat($var, bab_context::HTML);
		}
	}

}





/**
 * OVML containers root functionality
 * Replace the old bab_handler
 *
 */
class Func_Ovml_Function extends Func_Ovml
{
	/**
	 *
	 * @var babOvTemplate
	 */
	public $template;

	/**
	 *
	 * @var bab_context
	 */
	protected $gctx;
	protected $args = array();


	/**
	 * default description of OVML functionalities
	 * @see utilit/bab_functionality#getDescription()
	 */
	public function getDescription()
	{
		if (__CLASS__ === get_class($this)) {
			return bab_translate('All OVML functions');
		}

		$classname = explode('_',get_class($this));
		return BAB_TAG_FUNCTION. end($classname);
	}


	/**
	 *
	 * @param babOvTemplate $template
	 * @return Func_Ovml_Function
	 */
	public function setTemplate(babOvTemplate $template)
	{
		$this->template = $template;
		$this->gctx = $template->gctx;

		return $this;
	}

	/**
	 *
	 * @param array $args
	 * @return Func_Ovml_Function
	 */
	public function setArgs($args)
	{
		$this->args = $args;
		return $this;
	}

	protected function format_output($val, $matches)
	{
		return $this->template->format_output($val, $matches);
	}


	public function cast($str)
	{
		return $this->template->cast($str);
	}

}





class Func_Ovml_Container_IfIsSet extends Func_Ovml_Container
{
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$name = $ctx->get_value('name');
		if( $name !== false && !empty($name))
			{
			if( $ctx->get_value($name) !== false )
				{
				$this->count = 1;
				}
			}
	}

	public function getnext()
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

class Func_Ovml_Container_IfNotIsSet extends Func_Ovml_Container
{
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$name = $ctx->get_value('name');
		if( $name !== false && !empty($name))
			{
			if( $ctx->get_value($name) === false )
				{
				$this->count = 1;
				}
			}
	}

	public function getnext()
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

class bab_Ovml_Container_Operator extends Func_Ovml_Container
{
	var $count;

	protected $operator = null;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$expr1 = $ctx->get_value('expr1');
		$expr2 = $ctx->get_value('expr2');
		if( $expr1 !== false && $expr2 !== false)
		{
			switch($this->operator)
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

	public function getnext()
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


class Func_Ovml_Container_IfEqual extends bab_Ovml_Container_Operator
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->operator = BAB_OPE_EQUAL;
		parent::setOvmlContext($ctx);
	}
}

class Func_Ovml_Container_IfNotEqual extends bab_Ovml_Container_Operator
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->operator = BAB_OPE_NOTEQUAL;
		parent::setOvmlContext($ctx);
	}
}

class Func_Ovml_Container_IfLessThan extends bab_Ovml_Container_Operator
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->operator = BAB_OPE_LESSTHAN;
		parent::setOvmlContext($ctx);
	}
}

class Func_Ovml_Container_IfLessThanOrEqual extends bab_Ovml_Container_Operator
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->operator = BAB_OPE_LESSTHANOREQUAL;
		parent::setOvmlContext($ctx);
	}
}

class Func_Ovml_Container_IfGreaterThan extends bab_Ovml_Container_Operator
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->operator = BAB_OPE_GREATERTHAN;
		parent::setOvmlContext($ctx);
	}
}

class Func_Ovml_Container_IfGreaterThanOrEqual extends bab_Ovml_Container_Operator
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->operator = BAB_OPE_GREATERTHANOREQUAL;
		parent::setOvmlContext($ctx);
	}
}


class Func_Ovml_Container_Addon extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$name = $ctx->get_value('name');
		$addon = bab_getAddonInfosInstance($name);

		if($addon && $addon->isAccessValid())
			{
			if( is_file($addon->getPhpPath()."ovml.php" ))
				{
				/* save old vars */
				$this->AddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
				$this->AddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
				$this->AddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
				$this->AddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
				$this->AddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
				$this->AddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

				bab_setAddonGlobals($addon->getId());
				require_once( $addon->getPhpPath()."ovml.php" );

				$call = $addon->getName()."_ovml";
				if( !empty($call)  && function_exists($call) )
					{
					$args = $ctx->get_variables('Addon');
					$this->IdEntries = $call($args);
					}
				}
			}
		$this->count = count( $this->IdEntries );
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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


class Func_Ovml_Container_ObjectsInfo extends Func_Ovml_Container
{
	var $res;
	var $fields = array();
	var $ovmlfields = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->count = 0;
		$type = $ctx->get_value('type');
		if( $type !== false && $type !== '' )
		{
			$type = mb_strtolower(trim($type));
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

	public function getnext()
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

class Func_Ovml_Container_ArticlesHomePages extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $arrid = array();
	var $index;
	var $count;
	var $idgroup;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;

		parent::setOvmlContext($ctx);
		$arr['id'] = $babBody->babsite['id'];
		$this->idgroup = $ctx->get_value('type');
		$order = $ctx->get_value('order');
		if( $order === false || $order === '' )
			$order = "asc";

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

		switch(mb_strtoupper($order))
		{
			case "DESC": $order = "ht.ordering DESC"; break;
			case "RAND": $order = "rand()"; break;
			case "ASC":
			default: $order = "ht.ordering"; break;
		}

		switch(mb_strtolower($this->idgroup))
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


		$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);

		$res = $babDB->db_query("select ht.id, at.id_topic, at.restriction from ".BAB_ARTICLES_TBL." at LEFT JOIN ".BAB_HOMEPAGES_TBL." ht on ht.id_article=at.id where ht.id_group='".$babDB->db_escape_string($this->idgroup)."' and ht.id_site='".$arr['id']."' and ht.ordering!='0' and (at.date_publication='0000-00-00 00:00:00' or at.date_publication <= now()) and (date_archiving='0000-00-00 00:00:00' or date_archiving >= now()) GROUP BY at.id order by ".$babDB->db_escape_string($order));
		while($arr = $babDB->db_fetch_array($res))
		{
			if( $arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']) )
				{
				if(isset($topview[$arr['id_topic']]))
					{
					$this->IdEntries[] = $arr['id'];
					}
				}
		}

		$this->count = count($this->IdEntries);
		if( $this->count > 0 )
			{
			$sQuery =
				'SELECT
					at.*,
					count(aft.id) as nfiles,
					topicCategory.id_dgowner iIdDelegation
				FROM ' .
					BAB_ARTICLES_TBL . ' at ' .
				'LEFT JOIN ' .
					BAB_HOMEPAGES_TBL . ' ht on ht.id_article=at.id ' .
				'LEFT JOIN ' .
					BAB_ART_FILES_TBL . ' aft on aft.id_article=at.id ' .
				'LEFT JOIN ' .
					BAB_TOPICS_TBL . ' topic on topic.id = at.id_topic ' .
				'LEFT JOIN ' .
					BAB_TOPICS_CATEGORIES_TBL . ' topicCategory on topicCategory.id = topic.id_cat ' .
				'WHERE
					ht.id IN (' . $babDB->quote($this->IdEntries) . ') ' .
				'GROUP BY ' .
					'at.id order by ' . $babDB->db_escape_string($order);

//			bab_debug($sQuery);

			$this->res = $babDB->db_query($sQuery);
			}

		$this->count = isset($this->res) ? $babDB->db_num_rows($this->res) : 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);



			$this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
			$this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');

			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id']."&idg=".$this->idgroup);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date'] == $arr['date_modification'])
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

			if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic']) )
			{
				$this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$arr['id_topic'].'&article='.$arr['id']);
				$this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
			} else {
				$this->ctx->curctx->push('ArticleEditUrl', '');
				$this->ctx->curctx->push('ArticleEditName', '');
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


class Func_Ovml_Container_ArticleCategories extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$parentid = $ctx->get_value('parentid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

		if( $parentid === false || $parentid === '' )
		{
			$parentid[] = 0;
		}
		else
		{
			require_once dirname(__FILE__).'/artapi.php';
			$topcatview = bab_getReadableArticleCategories();
			$parentid = array_intersect(array_keys($topcatview), explode(',', $parentid));
		}

		$delegationid = (int) $ctx->get_value('delegationid');

		include_once $GLOBALS['babInstallPath'].'utilit/artapi.php';
		$this->res = bab_getArticleCategoriesRes($parentid, $delegationid);

		if (false === $this->res) {
			$this->count = 0;
		} else {
			$this->count = $babDB->db_num_rows($this->res);
		}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setCategoryAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$this->ctx->curctx->push('CategoryDelegationId', $arr['id_dgowner']);
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


class Func_Ovml_Container_ParentsArticleCategory extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$categoryid = $ctx->get_value('categoryid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

		if( $categoryid === false || $categoryid === '' )
			$this->count = 0;
		else
			{
			require_once dirname(__FILE__).'/artapi.php';
			$topcats = bab_getArticleCategories();

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

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setCategoryAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$this->ctx->curctx->push('CategoryDelegationId', $arr['id_dgowner']);
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

class Func_Ovml_Container_ArticleCategory extends Func_Ovml_Container
{
	var $arrid = array();
	var $index;
	var $count;
	var $res;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$catid = $ctx->get_value('categoryid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');
		
		require_once dirname(__FILE__).'/artapi.php';
		$topcatview = bab_getReadableArticleCategories();

		if( $catid === false || $catid === '' )
			$catid = array_keys($topcatview);
		else
			$catid = array_intersect(array_keys($topcatview), explode(',', $catid));

		if ( count($catid) > 0 )
		{
			$sql = 'SELECT topics_categories.*, topcat_order.ordering
					FROM '.BAB_TOPICS_CATEGORIES_TBL.' AS topics_categories
					LEFT JOIN '.BAB_TOPCAT_ORDER_TBL.' AS topcat_order
						ON topcat_order.type='.$babDB->quote('1').'
						AND topcat_order.id_topcat = topics_categories.id
					WHERE topics_categories.id IN ('.$babDB->quote($catid).')
					ORDER BY topcat_order.ordering ASC';

			$this->res = $babDB->db_query($sql);
			$this->count = $babDB->db_num_rows($this->res);
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setCategoryAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CategoryName', $arr['title']);
			$this->ctx->curctx->push('CategoryDescription', $arr['description']);
			$this->ctx->curctx->push('CategoryId', $arr['id']);
			$this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
			$this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
			$this->ctx->curctx->push('CategoryDelegationId', $arr['id_dgowner']);
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

class Func_Ovml_Container_ArticleCategoryPrevious extends Func_Ovml_Container_ArticleCategory
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleCategories');
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

class Func_Ovml_Container_ArticleCategoryNext extends Func_Ovml_Container_ArticleCategory
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleCategories');
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




class Func_Ovml_Container_ArticleTopics extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $ctx;
	var $index;
	var $count;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$catid = $ctx->get_value('categoryid');
		$delegationid = (int) $ctx->get_value('delegationid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');
		
		require_once dirname(__FILE__).'/artapi.php';
		$topcatview = bab_getReadableArticleCategories();

		if( $catid === false || $catid === '' )
			$catid = array_keys($topcatview);
		else
			$catid = array_intersect(array_keys($topcatview), explode(',', $catid));

		include_once $GLOBALS['babInstallPath'].'utilit/artapi.php';
		$this->res = bab_getArticleTopicsRes($catid, $delegationid);

		if (false === $this->res) {
			$this->count = 0;
		} else {
			$this->count = $babDB->db_num_rows($this->res);
		}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setTopicAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id_cat'], $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('TopicTotal', $this->count);
			$this->ctx->curctx->push('TopicName', $arr['category']);

			$this->pushEditor('TopicDescription', $arr['description'], $arr['description_format'], 'bab_topic');
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('TopicLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id']) )
			{
				$this->ctx->curctx->push('TopicSubmitUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$arr['id']);
				$this->ctx->curctx->push('TopicSubmitName', bab_translate("Submit"));
			}
			else
			{
				$this->ctx->curctx->push('TopicSubmitUrl', '');
				$this->ctx->curctx->push('TopicSubmitName', '');
			}
			if( bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id']) )
			{
				$this->ctx->curctx->push('TopicManageUrl', $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$arr['id']);
				$this->ctx->curctx->push('TopicManageName', bab_translate("Articles management"));
			}
			else
			{
				$this->ctx->curctx->push('TopicManageUrl', '');
				$this->ctx->curctx->push('TopicManageName', '');
			}
			list($cattitle, $iddgowner) = $babDB->db_fetch_array($babDB->db_query("select title, id_dgowner from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
			$this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
			$this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
			$this->ctx->curctx->push('TopicCategoryDelegationId', $iddgowner);

			/**
			 * @see bab_TopicNotificationSubscription()
			 */
			if (!$GLOBALS['BAB_SESS_LOGGED'] || 'N' === $arr['notify'] || 0 === (int) $arr['allow_unsubscribe'])
			{
				$this->ctx->curctx->push('TopicSubscription', -1);
				$this->ctx->curctx->push('TopicSubscriptionUrl', '');
			} else {
				$this->ctx->curctx->push('TopicSubscription', null === $arr['unsubscribed'] ? 1 : 0);
				$this->ctx->curctx->push('TopicSubscriptionUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=subscription&topic=".$arr['id']);
			}

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

class Func_Ovml_Container_ArticleTopic extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $topicid;
	var $count;
	var $index;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->topicid = $ctx->get_value('topicid');
		$this->topicname = $ctx->get_value('topicname');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

		if( $this->topicid === false || $this->topicid === '' ){
			$this->IdEntries = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
		}else{
			$this->IdEntries = array_values(array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $this->topicid)));
		}
		$this->count = count($this->IdEntries);

		if( $this->count > 0 )
		{
			$req = "
				SELECT t.*, u.id_user unsubscribed
				FROM ".BAB_TOPICS_TBL." t
				
				LEFT JOIN bab_topics_unsubscribe u
				ON t.id=u.id_topic AND u.id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."
				WHERE t.id IN (".$babDB->quote($this->IdEntries).")
			";
			if( $this->topicname !== false && $this->topicname !== '' )
			{
				$req .= " and t.category like '".$babDB->db_escape_like($this->topicname)."'";
			}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			
			$req = 'SELECT at.id_topic as id, count(at.id) as nb
					FROM ' . BAB_ARTICLES_TBL . ' AS at
			
					WHERE at.id_topic IN (' . $babDB->quote($this->IdEntries) . ')
					AND (at.date_publication=' . $babDB->quote('0000-00-00 00:00:00') . ' OR at.date_publication <= NOW())
					AND archive="N"
					GROUP BY at.id_topic';
			
			$res = $babDB->db_query($req);
			while($arr = $babDB->db_fetch_array($res)){
				$this->nbarticles[$arr['id']] = $arr['nb'];
			} 
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setTopicAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id_cat'], $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('TopicName', $arr['category']);
			$this->pushEditor('TopicDescription', $arr['description'], $arr['description_format'], 'bab_topic');
			$this->ctx->curctx->push('TopicId', $arr['id']);
			$this->ctx->curctx->push('TopicLanguage', $arr['lang']);
			if(!isset($this->nbarticles[$arr['id']])){
				$this->nbarticles[$arr['id']] = 0;
			}
			$this->ctx->curctx->push('TopicArticleNumber', $this->nbarticles[$arr['id']]);
			$this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
			if( bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id']) )
			{
				$this->ctx->curctx->push('TopicSubmitUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$arr['id']);
				$this->ctx->curctx->push('TopicSubmitName', bab_translate("Submit"));
			}
			else
			{
				$this->ctx->curctx->push('TopicSubmitUrl', '');
				$this->ctx->curctx->push('TopicSubmitName', '');
			}

			if( bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id']) )
			{
				$this->ctx->curctx->push('TopicManageUrl', $GLOBALS['babUrlScript']."?tg=topman&idx=Articles&item=".$arr['id']);
				$this->ctx->curctx->push('TopicManageName', bab_translate("Articles management"));
			}
			else
			{
				$this->ctx->curctx->push('TopicManageUrl', '');
				$this->ctx->curctx->push('TopicManageName', '');
			}
			list($cattitle, $iddgowner) = $babDB->db_fetch_array($babDB->db_query("select title, id_dgowner from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
			$this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
			$this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
			$this->ctx->curctx->push('TopicCategoryDelegationId', $iddgowner);

			/**
			 * @see bab_TopicNotificationSubscription()
			 */
			if (!$GLOBALS['BAB_SESS_LOGGED'] || 'N' === $arr['notify'] || 0 === (int) $arr['allow_unsubscribe'])
			{
				$this->ctx->curctx->push('TopicSubscription', -1);
				$this->ctx->curctx->push('TopicSubscriptionUrl', '');
			} else {
				$this->ctx->curctx->push('TopicSubscription', null === $arr['unsubscribed'] ? 1 : 0);
				$this->ctx->curctx->push('TopicSubscriptionUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=subscription&topic=".$arr['id']);
			}



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


class Func_Ovml_Container_ArticleTopicPrevious extends Func_Ovml_Container_ArticleTopic
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleTopics');
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

class Func_Ovml_Container_ArticleTopicNext extends Func_Ovml_Container_ArticleTopic
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleCategories');
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



class Func_Ovml_Container_Articles extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $res;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB, $babBody;
		parent::setOvmlContext($ctx);
		$topicid = $ctx->get_value('topicid');
		$delegationid = (int) $ctx->get_value('delegationid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

		$sDelegation = ' ';
		$sLeftJoin = ' ';
		if(0 != $delegationid)
		{
			$sLeftJoin =
				'LEFT JOIN ' .
					BAB_TOPICS_TBL . ' t ON t.id = at.id_topic ' .
				'LEFT JOIN ' .
					BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = t.id_cat ';

			$sDelegation = ' AND tpc.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		if( $topicid === false || $topicid === '' )
			$topicid = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
		else
			$topicid = array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $topicid));

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

			switch(mb_strtoupper($archive))
			{
				case 'NO': $archive = " AND archive='N' "; break;
				case 'YES': $archive = " AND archive='Y' "; break;
				default: $archive = ''; break;

			}


			$minRating = $ctx->get_value('minrating');
			if (!is_numeric($minRating)) {
				 $minRating = 0;
				 $ratingGroupBy = ' GROUP BY at.id ';
			} else {
				 $ratingGroupBy = ' GROUP BY at.id HAVING average_rating >= ' . $babDB->quote($minRating) . ' ';
			};

			$req = 'SELECT at.id, at.restriction, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
					FROM ' . BAB_ARTICLES_TBL . ' AS at
					LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
					' . $sLeftJoin . '

					WHERE at.id_topic IN (' . $babDB->quote($topicid) . ')
					AND (at.date_publication=' . $babDB->quote('0000-00-00 00:00:00') . ' OR at.date_publication <= NOW())'
					. $archive
					. $sDelegation
					. $ratingGroupBy
					;

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' ) {
				$order = 'asc';
			}

			 /* topicorder=yes : order defined by managers */
			$forder = $ctx->get_value('topicorder');
			switch(mb_strtoupper($forder))
			{
				case 'YES':
					$forder = true;
					break;
				case 'NO': /* no break */
				default:
					$forder = false;
					break;
			}

			$orderby = $ctx->get_value('orderby');
			if( $orderby === false || $orderby === '' )
				$orderby = "at.date";
			else
				{
				switch(mb_strtolower($orderby))
					{
					case 'title': $orderby = 'at.title'; break;
					case 'rating': $orderby = 'average_rating'; break;
					case 'creation': $orderby = 'at.date'; break;
					case 'publication': $orderby = 'at.date_publication'; break;
					case 'modification':
					default:
						$orderby = 'at.date_modification'; break;
					}
				}

			switch(mb_strtoupper($order))
			{
				case 'ASC':
					if ($forder) { /* topicorder=yes : order defined by managers */
						$order = 'at.ordering asc, at.date_modification desc';
					} else {
						$order = $orderby.' asc';
					}
					break;
				case 'RAND':
					$order = 'rand()';
					break;
				case 'DESC':
				default:
					if ($forder) { /* topicorder=yes : order defined by managers */
						$order = 'at.ordering desc, at.date_modification asc';
					} else {
						$order = $orderby.' desc';
					}
					break;
			}

			$req .=  'ORDER BY '.$order;

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
				$req = 'SELECT at.*, COUNT(aft.id) AS nfiles, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
							FROM ' . BAB_ARTICLES_TBL . ' AS at
							LEFT JOIN ' . BAB_ART_FILES_TBL . ' AS aft ON at.id=aft.id_article
							LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
							WHERE at.id IN ('.$babDB->quote($this->IdEntries).')
							' . $ratingGroupBy . '
							ORDER BY ' . $order;

				$this->res = $babDB->db_query($req);
			}
		}
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
			$this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', bab_sitemap::url('babArticle_'.$arr['id'], $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']));
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date'] == $arr['date_modification'])
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
			if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic']) )
			{
				$this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$arr['id_topic'].'&article='.$arr['id']);
				$this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
			} else {
				$this->ctx->curctx->push('ArticleEditUrl', '');
				$this->ctx->curctx->push('ArticleEditName', '');
			}
			$this->ctx->curctx->push('ArticleAverageRating', (float)$arr['average_rating']);
			$this->ctx->curctx->push('ArticleNbRatings', (float)$arr['nb_ratings']);
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

class Func_Ovml_Container_Article extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$articleid = $ctx->get_value('articleid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

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

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);

			setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
			$this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleUrl', bab_sitemap::url('babArticle_'.$arr['id'], $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']));
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date'] == $arr['date_modification'])
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
			else
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));  /* for compatibility */
			$this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
			if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic']) )
			{
				$this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$arr['id_topic'].'&article='.$arr['id']);
				$this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
			} else {
				$this->ctx->curctx->push('ArticleEditUrl', '');
				$this->ctx->curctx->push('ArticleEditName', '');
			}
			$this->ctx->curctx->push('ArticleAverageRating', bab_getArticleAverageRating($arr['id']));
			$this->ctx->curctx->push('ArticleNbRatings', bab_getArticleNbRatings($arr['id']));
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


class Func_Ovml_Container_ArticleFiles extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		require_once dirname(__FILE__).'/artincl.php';
		parent::setOvmlContext($ctx);
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
				$this->res = $babDB->db_query("select aft.*, at.id_topic from ".BAB_ART_FILES_TBL." aft left join ".BAB_ARTICLES_TBL." at on aft.id_article=at.id where aft.id_article IN (".$babDB->quote($this->IdEntries).") ORDER BY aft.ordering");
				$this->count = $babDB->db_num_rows($this->res);
				}
		}

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleFileName', $arr['name']);
			$this->ctx->curctx->push('ArticleFileDescription', $arr['description']);
			$this->ctx->curctx->push('ArticleFileUrlGet', $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$arr['id_topic']."&idf=".$arr['id']);
			$this->ctx->curctx->push('ArticleFileFullPath', bab_getUploadArticlesPath().$arr['id_article'].",".stripslashes($arr['name']));
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

class Func_Ovml_Container_ArticlePrevious extends Func_Ovml_Container_Article
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Articles');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('articleid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		parent::setOvmlContext($ctx);
	}

}

class Func_Ovml_Container_ArticleNext extends Func_Ovml_Container_Article
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Articles');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('articleid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		parent::setOvmlContext($ctx);
	}

}

class Func_Ovml_Container_Forums extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $IdEntries = array();
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$forumid = $ctx->get_value('forumid');
		$delegationid = (int) $ctx->get_value('delegationid');

		if (0 === $delegationid)
			{
			$delegationid = false;
			}


		if( $forumid === '' || $forumid === false )
			{
			$forumid = false;
			}
		else
			{
			$forumid = explode(',', $forumid);
			}

		include_once dirname(__FILE__).'/forumincl.php';
		$this->res = bab_getForumsRes($forumid, $delegationid);
		$this->count = $babDB->db_num_rows($this->res);

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $arr['id']))
			{
				$this->ctx->curctx->push('ForumNewThreadUrl', $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$arr['id']);
			}
			else
			{
				$this->ctx->curctx->push('ForumNewThreadUrl', '');
			}
			$this->ctx->curctx->push('ForumDelegationId', $arr['id_dgowner']);
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

class Func_Ovml_Container_Forum extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		/* Valid access rights */
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $ctx->get_value('forumid'))) {
			$this->res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($ctx->get_value('forumid'))."' and active='Y'");
			if( $this->res && $babDB->db_num_rows($this->res) == 1 ) {
				$this->count = 1;
			} else {
				$this->count = 0;
			}
		} else {
			$this->count = 0;
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
			$this->ctx->curctx->push('ForumDelegationId', $arr['id_dgowner']);
			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $arr['id']))
			{
				$this->ctx->curctx->push('ForumNewThreadUrl', $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$arr['id']);
			}
			else
			{
				$this->ctx->curctx->push('ForumNewThreadUrl', '');
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

class Func_Ovml_Container_ForumPrevious extends Func_Ovml_Container_Forum
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Forums');
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

class Func_Ovml_Container_ForumNext extends Func_Ovml_Container_Forum
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Forums');
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


class Func_Ovml_Container_Post extends Func_Ovml_Container
{
	var $res;
	var $arrid = array();
	var $arrfid = array();
	var $resposts;
	var $count;
	var $postid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		include_once $GLOBALS['babInstallPath'] . 'utilit/forumincl.php';
		parent::setOvmlContext($ctx);
		$this->postid = $ctx->get_value('postid');
		if( $this->postid === false || $this->postid === '' )
			$arr = array();
		else
			$arr = explode(',', $this->postid);

		$this->confirmed = $ctx->get_value('confirmed');
		if( $this->confirmed === false )
			$this->confirmed = "yes";

		switch(mb_strtoupper($this->confirmed))
		{
			case "YES": $this->confirmed = 'Y'; break;
			case "NO": $this->confirmed = 'N'; break;
			default: $this->confirmed = ''; break;
		}

		if( count($arr) > 0 )
			{
			$req = "SELECT p.id, p.id_thread, f.id id_forum FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y' and p.id IN (".$babDB->quote($arr).")";
			if($this->confirmed)
			{
				$req .= " AND p.confirmed =  '".$this->confirmed."'";
			}
			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				{
				$order = "asc";
				}

			switch(mb_strtoupper($order))
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
			$this->res = $babDB->db_query("select p.*, f.bupdatemoderator, f.bupdateauthor, t.active from ".BAB_POSTS_TBL." p left join ".BAB_THREADS_TBL." t on t.id=p.id_thread left join ".BAB_FORUMS_TBL." f on f.id=t.forum where p.id IN (".$babDB->quote($this->arrid).") order by ".$order);
			$this->count = $babDB->db_num_rows($this->res);
			}

		$this->ctx->curctx->push('CCount', $this->count);
		}

	public function getnext()
		{
		global $babBody, $babDB, $BAB_SESS_USERID;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
			$author = bab_getForumContributor($this->arrfid[$this->idx], $arr['id_author'], $arr['author']);
			$this->ctx->curctx->push('PostAuthor', $author);
			$this->ctx->curctx->push('PostAuthorId', $arr['id_author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $this->arrfid[$this->idx]))
			{
				$this->ctx->curctx->push('PostReplyUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=reply&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			}
			else
			{
				$this->ctx->curctx->push('PostReplyUrl', '');
			}
			if( ( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->arrfid[$this->idx]) && $arr['bupdatemoderator'] == 'Y') || ($arr["active"] == 'Y' && $BAB_SESS_USERID && $arr['bupdateauthor'] == 'Y' && $BAB_SESS_USERID == $arr['id_author']))
			{
				$this->ctx->curctx->push('PostModifyUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=Modify&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			}
			else
			{
				$this->ctx->curctx->push('PostModifyUrl', '');
			}
			if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->arrfid[$this->idx]) && $arr['confirmed'] == 'N')
			{
				$this->ctx->curctx->push('PostConfirmUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=Modify&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
				$this->ctx->curctx->push('PostDeleteUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=Delete&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			}
			else
			{
				$this->ctx->curctx->push('PostConfirmUrl', '');
				$this->ctx->curctx->push('PostDeleteUrl', '');
			}
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


class Func_Ovml_Container_PostFiles extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
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
					$iOffset = mb_strpos($file,',');
					if(false !== $iOffset && mb_substr($file, 0, $iOffset) == $postid)
						{
						$name = mb_substr($file, $iOffset+1);
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

	public function getnext()
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


class Func_Ovml_Container_Thread extends Func_Ovml_Container
{
	var $arrid = array();
	var $res;
	var $resposts;
	var $count;
	var $postid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		include_once $GLOBALS['babInstallPath'] . 'utilit/forumincl.php';
		parent::setOvmlContext($ctx);
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

			switch(mb_strtoupper($order))
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

	public function getnext()
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
			$starter = bab_getForumContributor($arr['forum'], $arr['starter'], bab_getUserName($arr['starter']));
			$this->ctx->curctx->push('ThreadStarter',  $starter);
			$this->ctx->curctx->push('ThreadStarterId',  $arr['starter']);
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


class Func_Ovml_Container_Folders extends Func_Ovml_Container
{
	var $index = 0;
	var $count = 0;
	var $IdEntries = array();
	var $oFmFolderSet = null;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babDB;
		parent::setOvmlContext($ctx);
		$folderid = $ctx->get_value('folderid');
		$iIdDelegation = (int) $ctx->get_value('delegationid');

		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		$this->oFmFolderSet = new BAB_FmFolderSet();

		$oIdDgOwner = $this->oFmFolderSet->aField['iIdDgOwner'];
		$oActive = $this->oFmFolderSet->aField['sActive'];
		$oId = $this->oFmFolderSet->aField['iId'];
		$oRelativePath = $this->oFmFolderSet->aField['sRelativePath'];

		$oCriteria = $oActive->in('Y');
		$oCriteria = $oCriteria->_and($oRelativePath->in(''));

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
		$this->oFmFolderSet->select($oId->in($this->IdEntries), array('sName' => 'ASC'));
		$this->count = $this->oFmFolderSet->count();
		$this->ctx->curctx->push('CCount', $this->oFmFolderSet->count());
	}

	public function getnext()
	{
		static $iIndex = 0;

		if(null !== ($oFmFolder = $this->oFmFolderSet->next()))
		{
			$this->ctx->curctx->push('CIndex', $iIndex);
			$this->ctx->curctx->push('FolderName', $oFmFolder->getName());
			$this->ctx->curctx->push('FolderId', $oFmFolder->getId());
			$this->ctx->curctx->push('FolderDelegationId', $oFmFolder->getDelegationOwnerId());
			$this->ctx->curctx->push('FolderPath', $oFmFolder->getRelativePath());
			$this->ctx->curctx->push('FolderPathname', $oFmFolder->getName());
			$url = $GLOBALS['babUrl']
					.  '?tg=fileman&idx=list&id='. $oFmFolder->getId() . '&gr=Y&path=' .$oFmFolder->getName();
			$this->ctx->curctx->push('FolderBrowseUrl', $url);
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




class Func_Ovml_Container_Folder extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $oFmFolderSet = null;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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

	public function getnext()
	{
		static $iIndex = 0;

		if(0 != $this->oFmFolderSet->count() && null !== ($oFmFolder = $this->oFmFolderSet->next()))
		{
			$path = $oFmFolder->getRelativePath();
			$name = $oFmFolder->getName();
			$pathname = $path . $name;
			$this->ctx->curctx->push('CIndex', $iIndex);
			$this->ctx->curctx->push('FolderName', $name);
			$this->ctx->curctx->push('FolderId', $oFmFolder->getId());
			$this->ctx->curctx->push('FolderDelegationId', $oFmFolder->getDelegationOwnerId());
			$this->ctx->curctx->push('FolderPath', $path);
			$this->ctx->curctx->push('FolderPathname', $pathname);
			$url = $GLOBALS['babUrl']
					.  '?tg=fileman&idx=list&id='. $oFmFolder->getId() . '&gr=Y&path=' . $pathname;
			$this->ctx->curctx->push('FolderBrowseUrl', $url);

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

class Func_Ovml_Container_FolderPrevious extends Func_Ovml_Container_Folder
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Folders');

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

class Func_Ovml_Container_FolderNext extends Func_Ovml_Container_Folder
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Folders');
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




class Func_Ovml_Container_SubFolders extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;

	var $oFmFolderSet = null;

	var $rootFolderPath;
	var $folderId;
	var $path;
	private $oFmFolder;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$folderid = (int) $ctx->get_value('folderid');
		$this->folderId = $folderid;
		$this->count = 0;


		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

		$sPath = (string) $path = $ctx->get_value('path');
		$this->path = $sPath;

		$this->oFmFolderSet = new BAB_FmFolderSet();
		$oId = $this->oFmFolderSet->aField['iId'];

		if(0 !== $folderid)
		{
			$oFmFolder = $this->oFmFolderSet->get($oId->in($folderid));
			$this->oFmFolder = $oFmFolder;

			if(!is_null($oFmFolder))
			{
				$iRelativePathLength = mb_strlen($oFmFolder->getRelativePath());
				$sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();

				$this->rootFolderPath = $sRelativePath;
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

					switch(mb_strtolower($order))
					{
						case 'desc':
							bab_sort::sort($this->IdEntries, bab_sort::CASE_INSENSITIVE);
							$this->IdEntries = array_reverse($this->IdEntries);
							break;
						default:
							bab_sort::sort($this->IdEntries, bab_sort::CASE_INSENSITIVE);
							break;
					}
				}
			}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('SubFolderName', $this->IdEntries[$this->idx]);
			$this->ctx->curctx->push('SubFolderPath', $this->path);
			$this->ctx->curctx->push('SubFolderPathname', $this->path . (empty($this->path) ? '' : '/') . $this->IdEntries[$this->idx]);
			$url = $GLOBALS['babUrl']
					.  '?tg=fileman&idx=list&id='. $this->folderId . '&gr=Y&path=' . $this->rootFolderPath . (empty($this->path) ? '' : '/' . $this->path);
			$this->ctx->curctx->push('SubFolderBrowseUrl', $url);
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

	public function accessValid($sName, $sPath)
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

	public function walkDirectory($sFullPathName)
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

				if(is_dir($sFullPathName . '/' . $sEntry) && $this->accessValid(getFirstPath($this->rootFolderPath), $this->oFmFolder->getName() . '/' . $this->path. '/' . $sEntry))
				{
					$this->IdEntries[] = $sEntry;
				}
			}
			$oDir->close();
		}
	}
}

class Func_Ovml_Container_Files extends Func_Ovml_Container
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

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		include_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
		parent::setOvmlContext($ctx);
		$this->count	= 0;
		$folderid		= (int) $ctx->get_value('folderid');
		$this->sPath	= (string) $ctx->get_value('path');
		$iLength		= mb_strlen(trim($this->sPath));

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');
		
		$order = (string) $ctx->get_value('order');
		switch (strtoupper(trim($order))) {
			case 'ASC':
			case 'DESC':
				break;
				
			default:
				$order = 'ASC';
				break;
		}


		$orderBy = (string) $ctx->get_value('orderby');
		switch ($orderBy) {
			case 'modification':
				$orderField = 'sModified';
				break;
			case 'creation':
				$orderField = 'sCreation';
				break;
			case 'size':
				$orderField = 'iSize';
				break;
			case 'hits':
				$orderField = 'iHits';
				break;
			case 'manual':
				$orderField = 'iDisplayPosition';
				break;
			case 'name':
			default:
				$orderField = 'sName';
				break;
		}
		

		if($iLength && '/' === $this->sPath{$iLength - 1})
		{
			$this->sPath = mb_substr($sEncodedPath, 0, -1);
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

				$iRelativePathLength = mb_strlen($oFmFolder->getRelativePath());
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

				require_once dirname(__FILE__) . '/tagApi.php';

				$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

				$this->oFolderFileSet->select($oCriteria, array($orderField => $order), $aLimit);
				while(null !== ($oFolderFile = $this->oFolderFileSet->next()))
				{
					$this->IdEntries[] = $oFolderFile->getId();
					$this->tags[$oFolderFile->getId()] = array();

					$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'files', 'file', $oFolderFile->getId()));
					$oIterator->orderAsc('tag_name');
					foreach($oIterator as $oTag)
					{
						$this->tags[$oFolderFile->getId()][] = $oTag->getName();
					}
				}
				$this->oFolderFileSet->rewind() ;
				$this->count = count($this->IdEntries);
			}
		}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function initRootFolderId($sRootFolderName)
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

	public function getnext()
	{
		global $babDB;
		if(0 !== $this->oFolderFileSet->count())
		{
			if(null !== ($oFolderFile = $this->oFolderFileSet->next()))
			{
				$iIdAuthor = (0 === $oFolderFile->getModifierId() ? $oFolderFile->getAuthorId() : $oFolderFile->getModifierId());
				
				$oFileManagerEnv =& getEnvObject();
				$sUploadPath = '';
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();
			
				$sFullPathName = $sUploadPath . $oFolderFile->getPathName() . $oFolderFile->getName();

				$mime = bab_getFileMimeType($sFullPathName);
				
				if(substr($mime, 0, 5) == "image"){
					setImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $sFullPathName);
				}else{
					$this->ctx->curctx->push('ImageUrl', '');
					$this->ctx->curctx->push('FileIsImage', 0);
				}
				
				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('FileName', $oFolderFile->getName());
				$this->ctx->curctx->push('FileDescription', $oFolderFile->getDescription());
				$this->ctx->curctx->push('FileKeywords', implode(' ', $this->tags[$oFolderFile->getId()]));
				$this->ctx->curctx->push('FileId', $oFolderFile->getId());
				$this->ctx->curctx->push('FileFolderId', $oFolderFile->getOwnerId());
				$this->ctx->curctx->push('FileDate', bab_mktime($oFolderFile->getModifiedDate()));
				$this->ctx->curctx->push('FileAuthor', $iIdAuthor);

/*
bab_debug(
	'FileName ==> ' . $oFolderFile->getName() .
	' FileAuthorId ==> ' . $iIdAuthor . ' ' .
	' FileAuthor ==> ' . bab_getUserName($iIdAuthor));
//*/

				$sGroup	= $oFolderFile->getGroup();

				$sEncodedPath = urlencode(removeEndSlashes($oFolderFile->getPathName()));

				$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript'] .'?tg=fileman&idx=list&id=' . $this->iIdRootFolder . '&gr=' .
					$sGroup . '&path=' . $sEncodedPath);

				$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript'] . '?tg=fileman&idx=viewFile&idf=' . $oFolderFile->getId() .
					'&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($oFolderFile->getName()));

				$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript'] . '?tg=fileman&sAction=getFile&id=' . $oFolderFile->getOwnerId() . '&gr=' .
					$sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($oFolderFile->getName()) . '&idf=' . $oFolderFile->getId());

				$oFileManagerEnv =& getEnvObject();
				$sUploadPath = $oFileManagerEnv->getCollectiveRootFmPath();

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


class Func_Ovml_Container_File extends Func_Ovml_Container
{
	var $arr;
	var $count;
	var $oFolderFile = null;
	var $iIdRootFolder = 0;
	var $tags = array();
	var $oFolderFileSet = null;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

		$this->oFolderFileSet = new BAB_FolderFileSet();
		$oId = $this->oFolderFileSet->aField['iId'];

		parent::setOvmlContext($ctx);
		$this->count = 0;
		$sFileId = (string) $ctx->get_value('fileid');
		if(0 !== mb_strlen(trim($sFileId)))
		{
			$aFileId = explode(',', $sFileId);
			$this->oFolderFileSet->select($oId->in($aFileId));
			$this->count = $this->oFolderFileSet->count();
			$this->ctx->curctx->push('CCount', $this->count);
		}
	}

	public function getnext()
	{
		static $iIndex = 0;

		if($iIndex < $this->count)
		{
			$bHaveFileAcess = false;

			while($iIndex < $this->count &&  false === $bHaveFileAcess)
			{
				$iIndex++;
				$this->oFolderFile = $this->oFolderFileSet->next();
				if(!is_null($this->oFolderFile))
				{
					if('Y' === $this->oFolderFile->getGroup() && '' === $this->oFolderFile->getState() && 'Y' === $this->oFolderFile->getConfirmed() && bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $this->oFolderFile->getOwnerId()))
					{
						$bHaveFileAcess = true;
					}
				}
			}

			if(true === $bHaveFileAcess)
			{
				global $babBody, $babDB;

				require_once dirname(__FILE__) . '/tagApi.php';

				$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

				$oIterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', 'files', 'file', $this->oFolderFile->getId()));
				$oIterator->orderAsc('tag_name');
				foreach($oIterator as $oTag)
				{
					$this->tags[] = $oTag->getName();
				}

				$iIdAuthor = (0 === $this->oFolderFile->getModifierId() ? $this->oFolderFile->getAuthorId() : $this->oFolderFile->getModifierId());

				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('FileName', $this->oFolderFile->getName());
				$this->ctx->curctx->push('FileDescription', $this->oFolderFile->getDescription());
				$this->ctx->curctx->push('FileKeywords', implode(' ', $this->tags));
				$this->ctx->curctx->push('FileId', $this->oFolderFile->getId());
				$this->ctx->curctx->push('FileFolderId', $this->oFolderFile->getOwnerId());
				$this->ctx->curctx->push('FileDate', bab_mktime($this->oFolderFile->getModifiedDate()));
				$this->ctx->curctx->push('FileAuthor', $iIdAuthor);

				$sRootFolderName = getFirstPath($this->oFolderFile->getPathName());
				$this->initRootFolderId($sRootFolderName);

				$sEncodedPath = urlencode(removeEndSlashes($this->oFolderFile->getPathName()));

				$sGroup	= $this->oFolderFile->getGroup();

				$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript'] .'?tg=fileman&idx=list&id=' . $this->iIdRootFolder . '&gr=' .
					$sGroup . '&path=' . $sEncodedPath);

				$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript'] . '?tg=fileman&idx=viewFile&idf=' . $this->oFolderFile->getId() .
					'&id=' . $this->iIdRootFolder . '&gr=' . $sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($this->oFolderFile->getName()));

				$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript'] . '?tg=fileman&sAction=getFile&id=' . $this->oFolderFile->getOwnerId() . '&gr=' .
					$sGroup . '&path=' . $sEncodedPath . '&file=' . urlencode($this->oFolderFile->getName()) . '&idf=' . $this->oFolderFile->getId());

				$sFullPathName = BAB_FileManagerEnv::getCollectivePath($this->oFolderFile->getDelegationOwnerId()) . $this->oFolderFile->getPathName() . $this->oFolderFile->getName();
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
		$this->idx = $iIndex = 0;
		return false;
	}

	public function initRootFolderId($sRootFolderName)
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




/**
 * <OCTags module="files|articles" type="file|article|draft" objectid="">
 * 		<OVTagName>
 * 		<OVTagSearchUrl>
 * </OCTags>
 */
class Func_Ovml_Container_Tags extends Func_Ovml_Container
{

	private $iterator;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->count = 0;
		$module 	= $ctx->get_value('module');
		$type 		= $ctx->get_value('type');
		$objectid	= $ctx->get_value('objectid');

		require_once dirname(__FILE__) . '/tagApi.php';

		$oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

		$this->iterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', $module, $type, $objectid));
		$this->iterator->orderAsc('tag_name');

		$this->ctx->curctx->push('CCount', $this->iterator->count());
		$this->iterator->rewind();
		$this->idx = 0;
	}


	public function getnext()
	{
		global $babDB;
		if( $this->iterator->valid())
		{

			$this->ctx->curctx->push('CIndex', $this->idx);

			$tag = $this->iterator->current();

			$this->ctx->curctx->push('TagName', $tag->getName());
			$this->ctx->curctx->push('TagSearchUrl', $GLOBALS['babUrlScript'] .'?tg=search&idx=find&item=tags&what='.urlencode($tag->getName()));

			$this->iterator->next();
			$this->index = $this->idx;
			return true;
		}
		else
		{
			$this->idx = 0;
			$this->iterator->rewind();
			return false;
		}
	}
}







class Func_Ovml_Container_FileFields extends Func_Ovml_Container
{
	var $fileid;
	var $index;
	var $count;
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FileFieldName', bab_translate($arr['name']));
			$fieldval = bab_toHtml($arr['fvalue']);
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


class Func_Ovml_Container_FilePrevious extends Func_Ovml_Container_File
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Files');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index > 1)
				{
				$ctx->curctx->push('IndexEntry', $this->handler->index -2);
				$ctx->curctx->push('fileid', $this->handler->IdEntries[$this->handler->index-2]);
				}
			}
		parent::setOvmlContext($ctx);
	}

}

class Func_Ovml_Container_FileNext extends Func_Ovml_Container_File
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Files');
		if( $this->handler !== false && $this->handler !== '' )
			{
			if( $this->handler->index < $this->handler->count)
				{
				$this->count = 1;
				$ctx->curctx->push('IndexEntry', $this->handler->index);
				$ctx->curctx->push('fileid', $this->handler->IdEntries[$this->handler->index]);
				}
			}
		parent::setOvmlContext($ctx);
	}

}

class Func_Ovml_Container_RecentArticles extends Func_Ovml_Container
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

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->topicid = $ctx->get_value('topicid');
		$this->topcatid = $ctx->get_value('categoryid');
		$lang = $ctx->get_value('lang');
		$delegationid = (int) $ctx->get_value('delegationid');

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

		if ( $this->topcatid === false || $this->topcatid === '' )
			{
			if( $this->topicid === false || $this->topicid === '' )
				$this->topicid = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
			else
				$this->topicid = array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $this->topicid));
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

			switch(mb_strtoupper($archive))
			{
				case 'NO': $archive = " AND at.archive='N' "; break;
				case 'YES': $archive = " AND at.archive='Y' "; break;
				default: $archive = ''; break;

			}

			$minRating = $ctx->get_value('minrating');
			if (!is_numeric($minRating)) {
				 $minRating = 0;
				 $ratingGroupBy = ' GROUP BY at.id ';
			} else {
				 $ratingGroupBy = ' GROUP BY at.id HAVING average_rating >= ' . $babDB->quote($minRating) . ' ';
			};


			$req =
				'SELECT ' .
					'at.id, ' .
					'at.restriction ' .
					', AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings ' .
				'FROM ' .
					BAB_ARTICLES_TBL . ' at ' .
				'LEFT JOIN ' .
					BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0 ';

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
				require_once dirname(__FILE__).'/userinfosincl.php';
				$usersettings = bab_userInfos::getUserSettings();

				$req .= " AND at.date >= DATE_ADD(\"".$babDB->db_escape_string($usersettings['lastlog'])."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
				}


			if ($lang !== false ) {
				$req .= " AND at.lang='".$babDB->db_escape_string($lang)."'";
			}

			$req .= $archive;

			$req .= $ratingGroupBy;

			$order = $ctx->get_value('order');
			if ( $order === false || $order === '' ) {
				$order = 'desc';
			}

			 /* topicorder=yes : order defined by managers */
			$forder = $ctx->get_value('topicorder');
			switch(mb_strtoupper($forder))
			{
				case 'YES':
					$forder = true;
					break;
				case 'NO': /* no break */
				default:
					$forder = false;
					break;
			}

			$orderby = $ctx->get_value('orderby');
			if( $orderby === false || $orderby === '' ) {
				$orderby = 'at.date_modification';
			} else {
				switch(mb_strtolower($orderby))
					{
					case 'title': $orderby = 'at.title'; break;
					case 'rating': $orderby = 'average_rating'; break;
					case 'creation': $orderby = 'at.date'; break;
					case 'publication': $orderby = 'at.date_publication'; break;
					case 'modification':
					default:
						$orderby = 'at.date_modification'; break;
				}
			}

			switch(mb_strtoupper($order))
			{
				case 'ASC':
					if ($forder) { /* topicorder=yes : order defined by managers */
						$order = 'at.ordering ASC, at.date_modification DESC';
					} else {
						$order = $orderby.' ASC';
					}
					break;
				case 'RAND':
					$order = 'rand()';
					break;
				case 'DESC':
				default:
					if($forder) { /* topicorder=yes : order defined by managers */
						$order = 'at.ordering DESC, at.date_modification ASC';
					} else {
						$order = $orderby.' DESC';
					}
					break;
			}

			$req .= ' ORDER BY ' . $order;

			if (!empty($this->last) && is_numeric($this->last)) {
				$req .= ' LIMIT 0, ' . $babDB->db_escape_string($this->last);
			}

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
				$req = 'SELECT at.*, atc.id_dgowner, COUNT(aft.id) AS nfiles, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
							FROM ' . BAB_ARTICLES_TBL . ' AS at
							LEFT JOIN ' . BAB_ART_FILES_TBL . ' AS aft ON at.id=aft.id_article
							LEFT JOIN ' . BAB_TOPICS_TBL . ' AS att ON at.id_topic=att.id
							LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS atc ON att.id_cat=atc.id
							LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
							WHERE at.id IN ('.$babDB->quote($this->IdEntries).')
							' . $ratingGroupBy . '
							ORDER BY ' . $order;
				$this->res = $babDB->db_query($req);
				$this->count = $babDB->db_num_rows($this->res);
				}
			}
		else
			{
			$this->count = 0;
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}


	public function gettopics($idparent)
		{
		require_once dirname(__FILE__).'/artapi.php';
		$topcats = bab_getArticleCategories();
		
		foreach($topcats as $id => $arr) {
				if ($idparent == $arr['parent']) {
					$this->gettopics($id);
				}
			}

		$babDB = &$GLOBALS['babDB'];


		$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where id_cat='".$babDB->db_escape_string($idparent)."' AND id IN(".$babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)).")");
		while( $row = $babDB->db_fetch_array($res))
			{
			$this->topicid[] = $row['id'];
			}
		}

	public function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);

			setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
			$this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
			if( empty($arr['body']))
				$this->ctx->curctx->push('ArticleReadMore', 0);
			else
				$this->ctx->curctx->push('ArticleReadMore', 1);
			$this->ctx->curctx->push('ArticleId', $arr['id']);
			$this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
			if ($arr['date'] == $arr['date_modification'])
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
			else
				$this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
			$this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date_modification'])); /* for compatibility */
			$this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
			$this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
			$this->ctx->curctx->push('ArticleUrl', bab_sitemap::url('babArticle_'.$arr['id'], $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']));
			$this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id']);
			$this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
			$this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
			$this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
			$this->ctx->curctx->push('ArticleDelegationId', $arr['id_dgowner']);
			if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic']) )
			{
				$this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$arr['id_topic'].'&article='.$arr['id']);
				$this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
			} else {
				$this->ctx->curctx->push('ArticleEditUrl', '');
				$this->ctx->curctx->push('ArticleEditName', '');
			}
			$this->ctx->curctx->push('ArticleAverageRating', (float)$arr['average_rating']);
			$this->ctx->curctx->push('ArticleNbRatings', (float)$arr['nb_ratings']);
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

class Func_Ovml_Container_RecentComments extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $rescomments;
	var $countcomments;
	var $lastlog;
	var $nbdays;
	var $last;
	var $articleid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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
		if( count(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)) > 0 )
			{

				$req =
					'SELECT ' .
						'* ' .
					'FROM ' .
						BAB_COMMENTS_TBL . ' ';

			if( count($arrid) > 0 )
				{
				$topview = "where id_article IN (".$babDB->quote($arrid).") and confirmed='Y' and id_topic IN (".$babDB->quote(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL))).")";
				}
			else
				{
				$topview = "where confirmed='Y' and id_topic IN (".$babDB->quote(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL))).")";
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
			if( $this->nbdays !== false  && bab_isUserLogged())
			{
				require_once dirname(__FILE__).'/userinfosincl.php';
				$usersettings = bab_userInfos::getUserSettings();
				
				$this->nbdays = (int) $this->nbdays;
				$req .= " and date >= DATE_ADD(\"".$babDB->db_escape_string($usersettings['lastlog'])."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
			}
			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "desc";

			switch(mb_strtoupper($order))
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

	public function getnext()
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
			$this->ctx->curctx->push('CommentArticleRating', $arr['article_rating']);
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

class Func_Ovml_Container_RecentPosts extends Func_Ovml_Container
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
	var $threadid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->forumid = $ctx->get_value('forumid');
		$this->threadid = $ctx->get_value('threadid');
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


			$req = "SELECT p.*, f.id id_forum, f.id_dgowner FROM ".BAB_POSTS_TBL." p LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum WHERE f.active='Y'" . $sDelegation . "and t.forum IN (".$babDB->quote($arr).") and p.confirmed='Y'";
			if ($this->threadid !== false && is_numeric($this->threadid)) {
				$req .= " and p.id_thread = '".$this->threadid."'";
			}

			if( $this->nbdays !== false  && bab_isUserLogged())
				{
				require_once dirname(__FILE__).'/userinfosincl.php';
				$usersettings = bab_userInfos::getUserSettings();
				
				$req .= " and p.date >= DATE_ADD(\"".$babDB->db_escape_string($usersettings['lastlog'])."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
				}


			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				$order = "desc";

			switch(mb_strtoupper($order))
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

	public function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $arr['id_forum']);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['id_forum']."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$arr['id_forum']."&thread=".$arr['id_thread']."&post=".$arr['id'].'&views=1');
			$this->ctx->curctx->push('PostDelegationId', $arr['id_dgowner']);
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


class Func_Ovml_Container_RecentThreads extends Func_Ovml_Container
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

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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

		$req = "
			SELECT p.id, p.id_thread, f.id id_forum
			FROM
				".BAB_POSTS_TBL." p
				LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id
				LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum
				LEFT JOIN ".BAB_POSTS_TBL." lp ON lp.id = t.lastpost

			WHERE
				f.active='Y'" . $sDelegation . "
				and p.confirmed='Y'
				and p.id_parent='0'
		";



		if( count($arr) > 0 )
			{
			$req .= " and t.forum IN (".$babDB->quote($arr).")";
			}


		if( $this->nbdays !== false  && bab_isUserLogged()) {
			
			require_once dirname(__FILE__).'/userinfosincl.php';
			$usersettings = bab_userInfos::getUserSettings();
			
			$req .= " and p.date >= DATE_ADD(\"".$babDB->db_escape_string($usersettings['lastlog'])."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
		}

		$order = $ctx->get_value('order');

		if( $order === false || $order === '' ) {
			$order = "desc";
		}

		switch(mb_strtoupper($order))
		{
			case "POST": 	$order = "lp.date DESC"; break;
			case "ASC": 	$order = "p.date ASC"; break;
			case "RAND": 	$order = "rand()"; break;
			case "DESC":
			default: 		$order = "p.date DESC"; break;
		}



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

			$req = "
				select p.*, f.id_dgowner
				from
					".BAB_POSTS_TBL." p
					LEFT JOIN ".BAB_THREADS_TBL." t on p.id_thread = t.id
					LEFT JOIN ".BAB_FORUMS_TBL." f on f.id = t.forum
					LEFT JOIN ".BAB_POSTS_TBL." lp ON lp.id = t.lastpost
				where
					p.id IN (".$babDB->quote($this->arrid).") order by ".$order;

			if( $this->last !== false)
				$req .= " limit 0, ".$this->last;

			$this->res = $babDB->db_query($req);

			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
		}

	public function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
			$this->ctx->curctx->push('PostId', $arr['id']);
			$this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
			$this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
			$this->ctx->curctx->push('PostAuthor', $arr['author']);
			$this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
			$this->ctx->curctx->push('PostUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$this->arrfid[$this->idx]."&thread=".$arr['id_thread']."&post=".$arr['id']);
			$this->ctx->curctx->push('PostDelegationId', $arr['id_dgowner']);
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


class Func_Ovml_Container_RecentFiles extends Func_Ovml_Container
	{

	var $index;
	var $count;
	var $res;
	var $lastlog;
	var $nbdays;
	var $last;
	var $folderid;

	var $oFmFolderSet = null;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $BAB_SESS_USERID, $babDB;
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';

		parent::setOvmlContext($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$this->folderid = $ctx->get_value('folderid');
		$delegationid = (int) $ctx->get_value('delegationid');
		$path = $ctx->get_value('path');
		$fullpath = $ctx->get_value('fullpath');

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
		} else {
			$oId = $this->oFmFolderSet->aField['iId'];
			$res = $this->oFmFolderSet->select($oId->in($arr));
			$arrpath = array();

			foreach($res as $oFmFolder)
			{
				$iRelativePathLength = mb_strlen($oFmFolder->getRelativePath());
				$sRelativePath = ($iRelativePathLength === 0) ? $oFmFolder->getName() : $oFmFolder->getRelativePath();
				$sRootFolderName = getFirstPath($sRelativePath);
				$arrpath[] = $sRootFolderName . '/' . $path;
			}

			$req = "select * from ".BAB_FM_FOLDERS_TBL." where active='Y' and (sRelativePath='' AND id IN(".$babDB->quote($arr).") OR CONCAT(sRelativePath, folder) IN(".$babDB->quote($arrpath)."))" . $sDelegation;
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

			if( $path === false || $path === '' )
			{
				$path = '';
			}
			if( $path != '' )
			{
				if($fullpath){
					$req .= " and f.path = '".$babDB->db_escape_string($path.'/')."'";
				}else{
					$req .= " and f.path like '%".$babDB->db_escape_like($path.'/')."'";
				}
			}

			$req .= " and f.id_owner IN (".$babDB->quote($arrid).")";

			if( $this->nbdays !== false && bab_isUserLogged())
			{
				require_once dirname(__FILE__).'/userinfosincl.php';
				$usersettings = bab_userInfos::getUserSettings();
				
				$req .= " and f.modified >= DATE_ADD(\"".$babDB->db_escape_string($usersettings['lastlog'])."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
			}

			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
			{
				$order = "desc";
			}

			switch(mb_strtoupper($order))
			{
				case "ASC": $order = 'f.modified ASC'; break;
				case "RAND": $order = 'rand()'; break;
				case "DESC":
				default: $order = 'f.modified DESC'; break;
			}

			$req .= ' order by '.$order;

			if( $this->last !== false)
			{
				$req .= ' limit 0, ' . $babDB->db_escape_string((int)$this->last);
			}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
		} else
			$this->count = 0;

		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
			if(!is_null($oFmFolder))
			{
				$iId = $oFmFolder->getId();

				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('FileId', $arr['id']);
				$this->ctx->curctx->push('FileName', $arr['name']);
				$this->ctx->curctx->push('FilePath', $arr['path']);
				$this->ctx->curctx->push('FileDescription', $arr['description']);
				$this->ctx->curctx->push('FileUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=list&id=".$iId."&gr=".$arr['bgroup']."&path=".urlencode(removeEndSlashes($sPath)));
				$this->ctx->curctx->push('FilePopupUrl', $GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$iId."&gr=".$arr['bgroup']."&path=".urlencode(removeEndSlashes($sPath))."&file=".urlencode($arr['name']));
				$this->ctx->curctx->push('FileUrlGet', $GLOBALS['babUrlScript']."?tg=fileman&sAction=getFile&id=".$iId."&gr=".$arr['bgroup']."&path=".urlencode(removeEndSlashes($sPath))."&file=".urlencode($arr['name']) . '&idf=' . $arr['id']);
				$this->ctx->curctx->push('FileAuthor', $arr['author']);
				$this->ctx->curctx->push('FileModifiedBy', $arr['modifiedby']);
				$this->ctx->curctx->push('FileDate', bab_mktime($arr['modified']));

				$sFullPathname = BAB_FileManagerEnv::getCollectivePath($oFmFolder->getDelegationOwnerId()) . $arr['path'] . $arr['name'];
				
				$this->ctx->curctx->push('FileFullPath', $sFullPathname);
				if (file_exists($sFullPathname))
				{
					$this->ctx->curctx->push('FileSize', bab_formatSizeFile(filesize($sFullPathname)));
				}
				else
				{
					$this->ctx->curctx->push('FileSize', '???');
				}
				$this->ctx->curctx->push('FileDelegationId', $arr['iIdDgOwner']);
			}
			else
			{
				$this->ctx->curctx->push('CIndex', $this->idx);
				$this->ctx->curctx->push('FileId', 0);
				$this->ctx->curctx->push('FileName', '');
				$this->ctx->curctx->push('FilePath', '');
				$this->ctx->curctx->push('FileDescription', '');
				$this->ctx->curctx->push('FileUrl', '');
				$this->ctx->curctx->push('FilePopupUrl', '');
				$this->ctx->curctx->push('FileUrlGet', '');
				$this->ctx->curctx->push('FileAuthor', '');
				$this->ctx->curctx->push('FileModifiedBy', '');
				$this->ctx->curctx->push('FileDate', '');
				$this->ctx->curctx->push('FileSize', '');
				$this->ctx->curctx->push('FileDelegationId', '');
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


class Func_Ovml_Container_WaitingArticles extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $res;
	var $index;
	var $count;
	var $topicid;

	var $imageheightmax;
	var $imagewidthmax;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);

		$this->imageheightmax	= (int) $ctx->get_value('imageheightmax');
		$this->imagewidthmax	= (int) $ctx->get_value('imagewidthmax');

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

	public function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$arr = $babDB->db_fetch_array($this->res);

			setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('ArticleTitle', $arr['title']);
			$this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
			$this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
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
			if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic']) )
			{
				$this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$arr['id_topic'].'&article='.$arr['id']);
				$this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
			} else {
				$this->ctx->curctx->push('ArticleEditUrl', '');
				$this->ctx->curctx->push('ArticleEditName', '');
			}
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


class Func_Ovml_Container_WaitingComments extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;
	var $articleid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);

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

	public function getnext()
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
			$this->ctx->curctx->push('CommentArticleRating', $arr['article_rating']);
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


class Func_Ovml_Container_WaitingFiles extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;
	var $folderid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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

	public function getnext()
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


class Func_Ovml_Container_WaitingPosts extends Func_Ovml_Container
{
	var $res;
	var $index;
	var $count;
	var $topicid;

	public function setOvmlContext(babOvTemplate $ctx)
		{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);

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

	public function getnext()
		{
		global $babBody, $babDB;
		if( $this->idx < $this->count)
			{
			$this->ctx->curctx->push('CIndex', $this->idx);
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('PostTitle', $arr['subject']);
			$this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
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


class Func_Ovml_Container_Faqs extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$faqid = $ctx->get_value('faqid');
		$delegationid = (int) $ctx->get_value('delegationid');

		if (empty($faqid)) {
			$faqid = false;
		} else {
			$faqid = explode(',', $faqid);
		}

		if (empty($delegationid)) {
			$delegationid = false;
		}

		include_once $GLOBALS['babInstallPath'].'utilit/faqincl.php';

		$this->res = bab_getFaqRes($faqid, $delegationid);
		$this->count = $babDB->db_num_rows($this->res);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
			$this->ctx->curctx->push('FaqDelegationId', $arr['id_dgowner']);
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

class Func_Ovml_Container_Faq extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->res = $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($ctx->get_value('faqid'))."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
			$this->ctx->curctx->push('FaqDelegationId', $arr['id_dgowner']);
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



class Func_Ovml_Container_FaqPrevious extends Func_Ovml_Container_Faq
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Faqs');
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

class Func_Ovml_Container_FaqNext extends Func_Ovml_Container_Faq
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_Faqs');
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

class Func_Ovml_Container_FaqSubCategories extends Func_Ovml_Container
{
	var $res;
	var $index;
	var $count;
	var $faqinfo;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->count = 0;
		$faqid = $ctx->get_value('faqid');
		if( $faqid != '')
			{
			if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $faqid))
				{
				$this->faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($faqid)."'"));
				$this->res = $babDB->db_query("select * from ".BAB_FAQ_SUBCAT_TBL." where id_cat='".$babDB->db_escape_string($faqid)."' order by name asc");
				$this->count = $babDB->db_num_rows($this->res);
				}

			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			if( $this->faqinfo['id_root'] == $arr['id'] )
			{
			$this->ctx->curctx->push('FaqSubCatName', $this->faqinfo['category']);
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

class Func_Ovml_Container_FaqSubCategory extends Func_Ovml_Container
{
	var $res;
	var $index;
	var $count;
	var $faqinfo;
	var $IdEntries = array();

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			if( empty($arr['name']) )
			{
			$this->faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id='".$babDB->db_escape_string($arr['id_cat'])."'"));
			$this->ctx->curctx->push('FaqSubCatName', $this->faqinfo['category']);
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


class Func_Ovml_Container_FaqQuestions extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
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

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqQuestion', $arr['question']);
			$this->pushEditor('FaqResponse', $arr['response'], $arr['response_format'], 'bab_faq_response');
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			if( $arr['id_modifiedby'] )
			{
			$this->ctx->curctx->push('FaqQuestionDate', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('FaqQuestionAuthor', bab_getUserName($arr['id_modifiedby']));
			}
			else
			{
			$this->ctx->curctx->push('FaqQuestionDate', '');
			$this->ctx->curctx->push('FaqQuestionAuthor', '');
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


class Func_Ovml_Container_FaqQuestion extends Func_Ovml_Container
{
	var $index;
	var $count;
	var $res;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id='".$babDB->db_escape_string($ctx->get_value('questionid'))."'");
		if( $this->res && $babDB->db_num_rows($this->res) == 1 )
			$this->count = 1;
		else
			$this->count = 0;
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqQuestion', $arr['question']);
			$this->pushEditor('FaqResponse', $arr['response'], $arr['response_format'], 'bab_faq_response');
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			if( $arr['id_modifiedby'] )
			{
			$this->ctx->curctx->push('FaqQuestionDate', bab_mktime($arr['date_modification']));
			$this->ctx->curctx->push('FaqQuestionAuthor', bab_getUserName($arr['id_modifiedby']));
			}
			else
			{
			$this->ctx->curctx->push('FaqQuestionDate', '');
			$this->ctx->curctx->push('FaqQuestionAuthor', '');
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


class Func_Ovml_Container_FaqQuestionPrevious extends Func_Ovml_Container_FaqQuestion
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_FaqQuestions');
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

class Func_Ovml_Container_FaqQuestionNext extends Func_Ovml_Container_FaqQuestion
{
	var $handler;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->handler = $ctx->get_handler('Func_Ovml_Container_FaqQuestions');
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


class Func_Ovml_Container_RecentFaqQuestions extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->nbdays = $ctx->get_value('from_lastlog');
		$this->last = $ctx->get_value('last');
		$faqid = $ctx->get_value('faqid');
		$faqsubcatid = $ctx->get_value('faqsubcatid');
		$delegationid = (int) $ctx->get_value('delegationid');

		$req = "select ft.id, ft.idcat from ".BAB_FAQQR_TBL." ft";
		$where = array();
		if(0 != $delegationid)
		{
			$req .= " left join ".BAB_FAQCAT_TBL." fct on fct.id=ft.idcat";
			$where[] = 'fct.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}

		if( $faqid !== false && $faqid !== '' )
			{
			$where[] = "ft.idcat IN (".$babDB->quote(explode(',', $faqid)).")";
			}
		if( $faqsubcatid !== false && $faqsubcatid !== '' )
			{
			$where[] = "ft.id_subcat IN (".$babDB->quote(explode(',', $faqsubcatid)).")";
			}

		if( $this->nbdays !== false)
			{
			require_once dirname(__FILE__).'/userinfosincl.php';
			$usersettings = bab_userInfos::getUserSettings();
			
			$where[] = "ft.date_modification >= DATE_ADD(\"".$babDB->db_escape_string($usersettings['lastlog'])."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
			}

		if( count($where))
			{
			$req .= " where ".implode(' AND ', $where);
			}

		if( $this->last !== false)
			{
			$req .= ' LIMIT 0, ' . $babDB->db_escape_string($this->last);
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
			$order = $ctx->get_value('order');
			if( $order === false || $order === '' )
				{
				$order = 'asc';
				}
			$this->res = $babDB->db_query("select * from ".BAB_FAQQR_TBL." where id IN (".$babDB->quote($this->IdEntries).") order by date_modification ".$order);
			$this->count = $babDB->db_num_rows($this->res);
			}
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babDB;
		if( $this->idx < $this->count)
		{
			$arr = $babDB->db_fetch_array($this->res);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('FaqQuestion', $arr['question']);
			$this->pushEditor('FaqResponse', $arr['response'], $arr['response_format'], 'bab_faq_response');
			$this->ctx->curctx->push('FaqQuestionId', $arr['id']);
			$this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			$this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&idcat=".$arr['idcat']."&idscat=".$arr['id_subcat']."&idq=".$arr['id']);
			if( $arr['id_modifiedby'] )
			{
				$this->ctx->curctx->push('FaqQuestionDate', bab_mktime($arr['date_modification']));
				$this->ctx->curctx->push('FaqQuestionAuthor', bab_getUserName($arr['id_modifiedby']));
			}
			else
			{
				$this->ctx->curctx->push('FaqQuestionDate', '');
				$this->ctx->curctx->push('FaqQuestionAuthor', '');
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

class Func_Ovml_Container_Calendars extends Func_Ovml_Container
{
	var $res;
	var $Entries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$type = $ctx->get_value('type');
		$delegationid = (int) $ctx->get_value('delegationid');


		switch(bab_getICalendars()->defaultview)
		{
			case BAB_CAL_VIEW_DAY: 	$this->view='calday';		break;
			case BAB_CAL_VIEW_WEEK: $this->view='calweek'; 		break;
			default: 				$this->view='calmonth'; 	break;
		}


		$calendars = bab_getICalendars()->getCalendars();
		$typename = mb_strtolower($type);

		switch($typename)
		{
			case 'user': 		$class = 'bab_PersonalCalendar';	break;
			case 'group': 		$class = 'bab_PublicCalendar';	break;
			case 'resource': 	$class = 'bab_ResourceCalendar';	break;
			default: 			$class = 'bab_EventCalendar'; 	break;
		}

		$calendarid = $ctx->get_value('calendarid');
		if( $calendarid !== false && $calendarid !== '' )
		{
			$calendarid = array_flip(explode(',',$calendarid));
		}
		else
		{
			$calendarid = null;
		}

		foreach($calendars as $calendar)
		{
			if (!($calendar instanceof $class))
			{
				continue;
			}

			if (isset($calendarid) && !isset($calendarid[$calendar->getUid()]))
			{
				continue;
			}

			$dg = $calendar->getDgOwner();

			if(0 != $delegationid && isset($dg) && $delegationid != $dg)
			{
				continue;
			}

			$this->Entries[] = $calendar;
		}

		bab_Sort::sortObjects($this->Entries, 'getName');//sort by name

		$this->count = count($this->Entries);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count)
		{
			$calendar = current($this->Entries);
			$this->ctx->curctx->push('CIndex', $this->idx);
			$this->ctx->curctx->push('CalendarId', $calendar->getUid());
			$this->ctx->curctx->push('CalendarName', $calendar->getName());
			$this->ctx->curctx->push('CalendarDescription', $calendar->getDescription());
			$this->ctx->curctx->push('CalendarOwnerId', $calendar->getIdUser());

			switch($calendar->getReferenceType())
			{
				case 'personal':
					$this->ctx->curctx->push('CalendarType', BAB_CAL_USER_TYPE);
					break;

				case 'public':
					$this->ctx->curctx->push('CalendarType', BAB_CAL_PUB_TYPE);
					break;

				case 'resource':
					$this->ctx->curctx->push('CalendarType', BAB_CAL_RES_TYPE);
					break;

				default:
					$this->ctx->curctx->push('CalendarType', 0);
					break;
			}

			$this->ctx->curctx->push('CalendarUrl', $GLOBALS['babUrlScript']."?tg=".$this->view."&calid=".$calendar->getUrlIdentifier());
			$this->idx++;
			$this->index = $this->idx;
			next($this->Entries);
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}


class Func_Ovml_Container_CalendarCategories extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$req = "select * from ".BAB_CAL_CATEGORIES_TBL." order by name asc";

		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		$this->ctx->curctx->push('CCount', $this->count);
	}

	public function getnext()
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
}/**
 * Return a list of calendar events
 *
 * calendarid 			: coma separated calendars id
 * delegationid			: filter the list of calendars by delegation
 * filter 				: filter by delegation YES | NO, if filter=NO calendars without access rights can be used
 * date					: ISO date or ISO datetime, default is current date
 * limit				: x days before, y days after the date "x,y"
 * holiday 				: return vacation events YES | NO (default YES)
 * private 				: return non accessibles private events YES | NO (default YES)
 * awaiting_approval 	: return non accessibles awaiting approval events YES | NO (default NO)
 * maxevents		 	: max number of events display (default 0) (0 = unlimited)
 *
 * <OCCalendarEvents calendarid="" delegationid="" date="NOW()" limit="" filter="YES" holiday="YES" private="YES" awaiting_approval="NO" maxevents="0">
 *
 * 	<OVEventId>
 * 	<OVEventTitle>
 * 	<OVEventDescription>
 * 	<OVEventLocation>
 * 	<OVEventBeginDate>
 * 	<OVEventEndDate>
 * 	<OVEventCategoryId>
 * 	<OVEventCategoryColor>	category color
 * 	<OVEventColor>			event color or category color if exists
 * 	<OVEventUrl>
 * 	<OVEventCalendarUrl>
 * 	<OVEventCategoriesPopupUrl>
 * 	<OVEventCategoryName>
 * 	<OVEventOwner>
 * 	<OVEventUpdateDate>
 * 	<OVEventUpdateAuthor>
 * 	<OVEventAuthor>
 *
 * </OCCalendarEvents>
 *
 */
class Func_Ovml_Container_CalendarEvents extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;
	
	var $maxEvent;

	var $cal_groups 			= 1;
	var $cal_resources			= 1;
	var $cal_users				= 1;
	var $cal_default_users 		= 1; // if empty calendarid, get all accessibles user calendars

	/**
	 *
	 * @var bool
	 */
	private $private 			= null;

	/**
	 *
	 * @var bool
	 */
	private $awaiting_approval 	= null;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);

		$calendarid = $ctx->get_value('calendarid');
		$delegationid = (int) $ctx->get_value('delegationid');
		$this->maxEvent = (int) $ctx->get_value('maxevents');
		$filter = mb_strtoupper($ctx->get_value('filter')) !== "NO";
		$holiday = mb_strtoupper($ctx->get_value('holiday')) !== "NO";
		$this->private = mb_strtoupper($ctx->get_value('private')) === "YES" || !$ctx->get_value('private');
		$this->awaiting_approval = mb_strtoupper($ctx->get_value('awaiting_approval')) === "YES";

		switch(bab_getICalendars()->defaultview)
			{
			case BAB_CAL_VIEW_DAY: $this->view='calday';	break;
			case BAB_CAL_VIEW_WEEK: $this->view='calweek'; 	break;
			default: $this->view='calmonth'; break;
			}

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


		$this->whObj = new bab_UserPeriods($startdate, $enddate);

		$backend = bab_functionality::get('CalendarBackend/Ovi');
		/*@var $backend Func_CalendarBackend_Ovi */
		$factory = $backend->Criteria();
		/*@var $factory bab_PeriodCriteriaFactory */

		if ($filter) {
			$calendars = $this->getUserCalendars($calendarid, $delegationid);
		} else {
			$calendars = $this->getCalendars($calendarid);
		}

		$criteria = $factory->Calendar($calendars);

		$categoryid = $ctx->get_value('categoryid');
		if( $categoryid !== false && $categoryid !== '' )
			{
			$catnames = array();
			$arr = explode(",",$categoryid);
			foreach($arr as $categoryid)
			{
				$cat = bab_getCalendarCategory($categoryid);
				$catnames[] = $cat['name'];
			}

			$criteria = $criteria->_AND_($factory->Property('CATEGORIES', $catnames));
		}

		$backend->includePeriodCollection();
		$collections = array('bab_CalendarEventCollection');

		if( $holiday )
		{
			$collections[] = 'bab_VacationPeriodCollection';
		}

		$criteria = $criteria->_AND_($factory->Collection($collections));

		$this->whObj->createPeriods($criteria);
		$this->whObj->orderBoundaries();

		$this->events = $this->whObj->getEventsBetween(
			$startdate->getTimeStamp(),
			$enddate->getTimeStamp(),
			null,
			false
		);


		if (!$this->private || !$this->awaiting_approval)
		{
			foreach($this->events as $key => $event)
			{
				/* @var $event bab_CalendarPeriod */
				if (!$this->awaiting_approval && !$event->WfInstanceAccess())
				{
					// the ovml container does not require to display waiting events and the event is in waiting state
					unset($this->events[$key]);
				}


				if (!$this->private && (!$event->isPublic() && $event->getAuthorId() !== (int) $GLOBALS['BAB_SESS_USERID']))
				{
					// the ovml container does not require to display the private events and the event is private
					unset($this->events[$key]);
				}
			}

			reset($this->events);
		}


		$this->count = count($this->events);
		$this->ctx->curctx->push('CCount', $this->count);
	}


	/**
 	 * Get available calendar without filter
	 */
	public function getCalendars($calendarid) {
		global $babDB;

		if (empty($calendarid)) {
			trigger_error('filter=NO must be used with calendarid');
			return;
		}

		require_once dirname(__FILE__).'/cal.ovicalendar.class.php';

		$public = bab_cal_getPublicCalendars(0, $calendarid);
		$resource = bab_cal_getResourceCalendars(0, $calendarid);
		$personal = bab_cal_getPersonalCalendars(0, $calendarid);

		return array_merge($public, $resource, $personal);
	}

	/**
 	 * Get available calendar with filter
 	 * @param	string	$calendarid		coma separated list of calendar id
 	 * @param	int		$delegationid
 	 *
	 */
	public function getUserCalendars($calendarid, $delegationid) {
		$calendars = bab_getICalendars()->getCalendars();

		if($calendarid)
		{
			$calendarid_list = array_flip(explode(',',$calendarid));
		}
		elseif (!$delegationid)
		{
			switch(true)
			{
				case ($this instanceof Func_Ovml_Container_CalendarGroupEvents):

					$calendarid_list = array();
					foreach($calendars as $calendar)
					{
						if ($calendar instanceof bab_PublicCalendar)
						{

							$calendarid_list[$calendar->getUid()] = 1;
						}
					}
				break;

				case ($this instanceof Func_Ovml_Container_CalendarResourceEvents):

					$calendarid_list = array();
					foreach($calendars as $calendar)
					{
						if ($calendar instanceof bab_ResourceCalendar)
						{

							$calendarid_list[$calendar->getUid()] = 1;
						}
					}
				break;


				case ($this instanceof Func_Ovml_Container_CalendarUserEvents):

					 $personal = bab_getICalendars()->getPersonalCalendar();
					if (!$personal)
					{
					return array();
					}

					$calendarid_list = array($personal->getUid() => 1);

				break;

				default:

					$calendarid_list = array();
					foreach($calendars as $calendar)
					{
						$calendarid_list[$calendar->getUid()] = 1;
					}

				break;

			}

		}



		$return = array();

		foreach($calendars as $calendar)
		{
			if (isset($calendarid_list) && !isset($calendarid_list[$calendar->getUid()]))
			{
				continue;
			}

			$dg = $calendar->getDgOwner();

			if($delegationid && $delegationid != $dg)
			{
				continue;
			}



			$return[] = $calendar;
		}


		return $return;
	}

	/**
 	 * for deprecated attribute idgroup, iduser, idresource
	 * in events contener
	 * idcalendar is better
	 * @param object	$ctx
	 * @param array 	$owner
	 */
	public function getCalendarsFromOwner(&$ctx, $owner) {
		global $babDB;
		$calendars = array();
		$res = $babDB->db_query("SELECT id FROM ".BAB_CALENDAR_TBL." WHERE owner IN(".$babDB->quote($owner).")");
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$calendars[] = $arr['id'];
		}

		$ctx->curctx->push('calendarid', implode(',',$calendars));
	}


	public function getnext()
	{
		global $babBody,$babDB;
		if( $this->idx < $this->count && ($this->maxEvent == 0 || $this->idx < $this->maxEvent))
		{
			list(, $p) = each($this->events);
			$arr = $p->getData();

			$id_category = '';
			$category_color = '';
			$color = $p->getProperty('X-CTO-COLOR');

			$cat = bab_getCalendarCategory($p->getProperty('CATEGORIES'));
			if ($cat)
			{
				$id_category = $cat['id'];
				$category_color = $cat['bgcolor'];
				$color = $category_color;
			}

			$id_event = $p->getProperty('UID');

			$collection = $p->getCollection();
			$calendar = $collection->getCalendar();
			
			
			if (!$calendar)
			{
				$calendar = reset($p->getCalendars());
			}
			

			if ($calendar)
			{
				/* @var $calendar bab_EventCalendar */
				$arr['id_cal'] = $calendar->getUrlIdentifier();
			} else {
				$arr['id_cal'] = 0;
			}

			$calid_param = !empty($arr['id_cal']) ? '&idcal='.$arr['id_cal'] : '';
			$summary = $p->getProperty('SUMMARY');
			$description = bab_toHtml($p->getProperty('DESCRIPTION'));
			$location = $p->getProperty('LOCATION');
			$categories = $p->getProperty('CATEGORIES');
			$date = date('Y,m,d',$p->ts_begin);

			// with filter
			if ($calendar && !$calendar->canViewEventDetails($p))
			{
				$summary = $p->isPublic() ? bab_translate('Awaiting approval') : bab_translate('Private');
				$description = '';
				$location = '';
				$categories = '';
			}

			$this->ctx->curctx->push('CIndex'					, $this->idx);
			$this->ctx->curctx->push('EventId'					, $id_event);
			$this->ctx->curctx->push('EventCalendarId'			, $arr['id_cal']);
			$this->ctx->curctx->push('EventTitle'				, $summary);

			if (isset($arr['description']) && isset($arr['description_format'])) {
				$this->pushEditor('EventDescription', $arr['description'], $arr['description_format'], 'bab_calendar_event');
			} else {
				$this->ctx->curctx->push('EventDescription'			, $description);
			}
			$this->ctx->curctx->push('EventLocation'			, $location);
			$this->ctx->curctx->push('EventBeginDate'			, $p->ts_begin);
			$this->ctx->curctx->push('EventEndDate'				, $p->ts_end);
			$this->ctx->curctx->push('EventCategoryId'			, $id_category);
			$this->ctx->curctx->push('EventCategoryColor'		, $category_color);
			$this->ctx->curctx->push('EventColor'				, $color);
			if ($calid_param)
			{
				$this->ctx->curctx->push('EventUrl'					, $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$id_event.$calid_param);
				$this->ctx->curctx->push('EventCalendarUrl'			, $GLOBALS['babUrlScript']."?tg=".$this->view.$calid_param."&date=".$date);
				$this->ctx->curctx->push('EventCategoriesPopupUrl'	, $GLOBALS['babUrlScript']."?tg=calendar&idx=viewc".$calid_param);
			} else {
				$this->ctx->curctx->push('EventUrl'					, '');
				$this->ctx->curctx->push('EventCalendarUrl'			, '');
				$this->ctx->curctx->push('EventCategoriesPopupUrl'	, '');
			}

			$this->ctx->curctx->push('EventCategoryName'		, $categories);

			$EventOwner = isset($arr['id_cal']) ? bab_getCalendarOwner($arr['id_cal']) : '';

			$this->ctx->curctx->push('EventOwner'				, $EventOwner);
			if( isset($arr['id_modifiedby']) && $arr['id_modifiedby'] )
			{
			$this->ctx->curctx->push('EventUpdateDate', BAB_DateTime::fromICal($p->getProperty('LAST-MODIFIED'))->getTimeStamp());
			$this->ctx->curctx->push('EventUpdateAuthor', $arr['id_modifiedby']);
			}
			if( isset($arr['id_creator']) && $arr['id_creator'] )
			{
			$this->ctx->curctx->push('EventAuthor', $arr['id_creator']);
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














/**
 * Return a list of domain
 *
 * calendarid 			: complete calendar id (ex: public/3), should be <OVEventCalendarId<OVEventId>>
 * eventid				: uid of an event, should be <OVEventId>
 * dtstart 				: <OVEventBeginDate>
 *
 * <OCCalendarEvents calendarid="" eventid="" dtstart="">
 *
 * 	<OVDomainName>
 * 	<OVDomainValue>
 *
 * </OCCalendarEvents>
 *
 */
class Func_Ovml_Container_CalendarEventDomains extends Func_Ovml_Container
{
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);

		$calendarid = $ctx->get_value('calendarid');
		$eventid = $ctx->get_value('eventid');
		$dtstart = $ctx->get_value('dtstart');
		
		$calendar = bab_getICalendars()->getEventCalendar($calendarid);
		
		$backend = $calendar->getBackend();
		
		$collection = $backend->CalendarEventCollection($calendar);

		$period = $backend->getPeriod($backend->CalendarEventCollection($calendar), $eventid, $dtstart);
		
		$domsStr = $period->getDomains();
		
		$this->doms = array();
		if ($domsStr){
			$this->doms = bab_getDomains($domsStr);
		}

		$this->count = count($this->doms);
		$this->ctx->curctx->push('CCount', $this->count);
	}


	public function getnext()
	{
		global $babBody,$babDB;
		if(!empty($this->doms) && $dom = array_shift($this->doms))
		{
			$this->ctx->curctx->push('DomainName'	, $dom['domain']);
			$this->ctx->curctx->push('DomainValue'	, $dom['value']);
			return true;
		}
		else
		{
			$this->idx=0;
			return false;
		}
	}
}



class Func_Ovml_Container_CalendarUserEvents extends Func_Ovml_Container_CalendarEvents
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->cal_users 			= 1;
		$this->cal_groups			= 0;
		$this->cal_resources		= 0;
		$this->cal_default_users	= 0;

		$userid = $ctx->get_value('userid');

		if (false !== $userid && '' !== $userid) {
			$this->getCalendarsFromOwner($ctx, explode(',',$userid));
		}

		parent::setOvmlContext($ctx);
	}
}


class Func_Ovml_Container_CalendarGroupEvents extends Func_Ovml_Container_CalendarEvents
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->cal_users 		= 0;
		$this->cal_groups		= 1;
		$this->cal_resources	= 0;

		$groupid = $ctx->get_value('groupid');

		if (false !== $groupid && '' !== $groupid) {
			$this->getCalendarsFromOwner($ctx, explode(',',$groupid));
		}

		parent::setOvmlContext($ctx);
	}
}


class Func_Ovml_Container_CalendarResourceEvents extends Func_Ovml_Container_CalendarEvents
{
	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->cal_users 		= 0;
		$this->cal_groups		= 0;
		$this->cal_resources	= 1;

		$resourceid = $ctx->get_value('resourceid');

		if (false !== $resourceid && '' !== $resourceid) {
			$this->getCalendarsFromOwner($ctx, explode(',',$resourceid));
		}

		parent::setOvmlContext($ctx);
	}
}


class Func_Ovml_Container_IfUserMemberOfGroups extends Func_Ovml_Container
{
	var $res;
	var $IdEntries = array();
	var $index;
	var $count;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babBody, $babDB;
		parent::setOvmlContext($ctx);
		$this->count = 0;

		$userid = $ctx->get_value('userid');
		if( $userid === false  )
		{
			$userid = $GLOBALS['BAB_SESS_USERID'];
		}


		if( $userid != "" )
			{
			$all = $ctx->get_value('all');

			if ( $all !== false && mb_strtoupper($all) == "YES")
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
			if ( $childs !== false && mb_strtoupper($childs) == "YES")
				{
				include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
				$rr = $groupid;
				$tree = new bab_grptree();
				for( $k=0; $k < count($rr); $k++ )
					{
					$groups = $tree->getChilds($rr[$k], 1);
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

	public function getnext()
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

class Func_Ovml_Container_OvmlArray extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
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

	public function getnext()
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


class Func_Ovml_Container_OvmlArrayFields extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
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

	public function getnext()
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


class Func_Ovml_Container_OvmlSoap extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 1;
		parent::setOvmlContext($ctx);
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

	public function getnext()
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

class Func_Ovml_Container_Soap extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 1;
		parent::setOvmlContext($ctx);
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

			$soapAction = '';
			$headers=false;
			$style='rpc';
			$use='encoded';
			if( isset($vars['soapaction']))
				{
				$soapAction = $vars['soapaction'];
				unset($vars['soapaction']);
				}
			if( isset($vars['headers']))
				{
				$headers = $vars['headers'];
				unset($vars['soapaction']);
				}
			if( isset($vars['style']))
				{
				$style = $vars['style'];
				unset($vars['style']);
				}
			if( isset($vars['use']))
				{
				$use = $vars['use'];
				unset($vars['use']);
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
			$this->IdEntries = $soapclient->call($apicall, $args, $apinamespace,$soapAction,$headers,null,$style,$use);
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

	public function getnext()
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



class Func_Ovml_Container_Multipages extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$total = $ctx->get_value('total');

		$maxpages = $ctx->get_value('maxpages');
		$perpage = $ctx->get_value('perpage');
		$currentpage = $ctx->get_value('currentpage');
		if (false === $currentpage || !is_numeric($currentpage))
		{
			$currentpage = 1;
		}

		if (false !== $total && is_numeric($total))
			{
			if (false === $perpage || !is_numeric($perpage))
				{
				$perpage = $total;
				}

			$total_pages = ceil($total/$perpage);

			if (false === $maxpages || !is_numeric($maxpages))
				{
					$maxpages = $total_pages;
				}

			for( $k = 0; $k < $maxpages && $currentpage + $k <= $total_pages; $k++ )
				{
				$tmp['CurrentPageNumber'] = $currentpage + $k;
				if( $currentpage + $k + 1 > $total_pages )
					{
					$tmp['NextPageNumber'] = '';
					}
				else
					{
					$tmp['NextPageNumber'] = $currentpage + $k + 1;
					}
				if( $currentpage + $k > 1 && $total_pages > 1 )
					{
					$tmp['PreviousPageNumber'] = $currentpage + $k - 1;
					}
				else
					{
					$tmp['PreviousPageNumber'] = '';
					}

				$tmp['TotalPages'] = $total_pages;
				$tmp['ResultFirst'] = (($currentpage+$k-1) * $perpage) + 1;
				if( $currentpage + $k < $total_pages )
					{
					$tmp['ResultLast'] = $tmp['ResultFirst'] + $perpage -1;
					}
				else
					{
					$tmp['ResultLast'] = $total;
					}

				$tmp['ResultsPage'] = $tmp['ResultLast'] - $tmp['ResultFirst'] + 1;
				$this->IdEntries[] = $tmp;
				}
			}

		$this->count = count($this->IdEntries);
		$this->ctx->curctx->push('CCount', $this->count);

	}

	public function getnext()
	{
		if( $this->idx < $this->count )
		{
			$this->ctx->curctx->push('CIndex', $this->idx);
			foreach( $this->IdEntries[$this->idx] as $key => $val )
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



/**
 *
 */
class bab_context
{
	const TEXT = 0;
	const HTML = 1;

	var $name;
	var $variables = array();

	/**
	 *
	 * @var string
	 */
	var $content;

	/**
	 * storage for variable content format
	 * @var array
	 */
	private $format = array();

	public function bab_context($name)
	{
		$this->name = $name;
	}

	public function push( $var, $value )
	{
		$this->variables[$var] = $value;
	}

	/**
	 * Set optional format of content on a variable (optional)
	 * @param	string	$var
	 * @param	int		$format		bab_context::TEXT | bab_context::HTML
	 * @return unknown_type
	 */
	public function setFormat($var, $format )
	{
		$this->format[$var] = $format;
		return $this;
	}

	public function pop()
	{
		return array_pop($this->variables);
	}

	public function setContent($txt) {
		$this->content = $txt;
	}

	public function getcontent() {
		return $this->content;
	}

	/**
	 * Get value in context
	 * @param string $var
	 * @return string
	 */
	public function get($var)
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

	/**
	 * get string format of value
	 * @param	string	$var
	 * @return int		bab_context::TEXT | bab_context::HTML
	 */
	public function getFormat($var)
	{
		if (!isset($this->variables[$var]))
		{
			return null;
		}

		if (!isset($this->format[$var]))
		{
			return self::TEXT;
		}

		return $this->format[$var];
	}


	public function getname()
	{
		return $this->name;
	}

	public function getvars()
	{
		return $this->variables;
	}

}




/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdArticle
 * @return void
 */
function setArticleAssociatedImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $iIdArticle)
{
	require_once dirname(__FILE__) . '/gdiincl.php';
	require_once dirname(__FILE__) . '/artapi.php';
	require_once dirname(__FILE__) . '/pathUtil.class.php';

	$bProcessed		= false;
	$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));

	if (is_dir($sUploadPath)) {
		$aImgInfo = bab_getImageArticle($iIdArticle);
		if (is_array($aImgInfo)) {
			$iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
			$iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
			$sName				= $aImgInfo['name'];
			$sRelativePath		= $aImgInfo['relativePath'];
			$sFullPathName		= $sUploadPath . $sRelativePath . $sName;
			$sImageUrl			= $GLOBALS['babUrlScript'] . '?tg=articles&idx=getImage&sImage=' . urlencode($sName);
			$sOriginalImageUrl	= $sImageUrl . '&iIdArticle=' . $iIdArticle;

			$T = @bab_functionality::get('Thumbnailer');
			$thumbnailUrl = null;

			if ($T) {
				// The thumbnailer functionality is available.
			 	$T->setSourceFile($sFullPathName);
				$thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
			}
			if ($thumbnailUrl) {
				// The thumbnailer functionality was able to create a thumbnail.
				$oCtx->curctx->push('AssociatedImage', 1);
				$oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
				$oCtx->curctx->push('ImageUrl', $thumbnailUrl);
				$oCtx->curctx->push('ImageWidth', $iWidth);
				$oCtx->curctx->push('ImageHeight', $iHeight);

				// We reload the thumbnail image to get the real resized width and height.
				$thumbnailPath = $T->getThumbnailPath($iWidth, $iHeight);
				$imageSize = getImageSize($thumbnailPath->toString());
				if ($imageSize !== false) {
					$oCtx->curctx->push('ResizedImageWidth', $imageSize[0]);
					$oCtx->curctx->push('ResizedImageHeight', $imageSize[1]);
				}

				$bProcessed = true;
			} else {
				// If the thumbnailer was not available or not able to create a thumbnail,
				// we fall back to the old method for creating thumbnails (url of the page
				// dynamically resizing the image).
				$oImageResize = new bab_ImageResize();
				$iHeight = $iMaxImageHeight;
				$iWidth = $iMaxImageWidth;
				if (false !== $oImageResize->computeImageResizeWidthAndHeight($sFullPathName, $iWidth, $iHeight)) {
					$sImageUrl .= '&iIdArticle=' . $iIdArticle;
					$sImageUrl .= '&iWidth=' . $iWidth;
					$sImageUrl .= '&iHeight=' . $iHeight;

					$oCtx->curctx->push('AssociatedImage', 1);
					$oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
					$oCtx->curctx->push('ImageUrl', $sImageUrl);
					$oCtx->curctx->push('ImageWidth', $oImageResize->getRealWidth());
					$oCtx->curctx->push('ImageHeight', $oImageResize->getRealHeight());
					$oCtx->curctx->push('ResizedImageWidth', $iWidth);
					$oCtx->curctx->push('ResizedImageHeight', $iHeight);

					$bProcessed = true;
				}
			}
		}
	}

	if (false === $bProcessed) {
		$oCtx->curctx->push('AssociatedImage', 0);
		$oCtx->curctx->push('OriginalImageUrl', '');
		$oCtx->curctx->push('ImageUrl', '');
		$oCtx->curctx->push('ImageWidth', 0);
		$oCtx->curctx->push('ImageHeight', 0);
		$oCtx->curctx->push('ResizedImageWidth', 0);
		$oCtx->curctx->push('ResizedImageHeight', 0);
	}
}




/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdCategory
 * @return void
 */
function setCategoryAssociatedImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $iIdCategory)
{
	require_once dirname(__FILE__) . '/gdiincl.php';
	require_once dirname(__FILE__) . '/artapi.php';
	require_once dirname(__FILE__) . '/pathUtil.class.php';

	$bProcessed		= false;
	$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));

	if (is_dir($sUploadPath)) {
		$aImgInfo = bab_getImageCategory($iIdCategory);
		if (is_array($aImgInfo)) {
			$iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
			$iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
			$sName				= $aImgInfo['name'];
			$sRelativePath		= $aImgInfo['relativePath'];
			$sFullPathName		= $sUploadPath . $sRelativePath . $sName;
			$sImageUrl			= $GLOBALS['babUrlScript'] . '?tg=topusr&idx=getCategoryImage&sImage=' . bab_toHtml($sName);
			$sOriginalImageUrl	= $sImageUrl . '&iIdCategory=' . $iIdCategory;

			$T = @bab_functionality::get('Thumbnailer');
			$thumbnailUrl = null;

			if ($T && $iWidth && $iHeight) {
				// The thumbnailer functionality is available.
			 	$T->setSourceFile($sFullPathName);
				$thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
			}
			if ($thumbnailUrl) {
				// The thumbnailer functionality was able to create a thumbnail.
				$oCtx->curctx->push('AssociatedImage', 1);
				$oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
				$oCtx->curctx->push('ImageUrl', $thumbnailUrl);
				$oCtx->curctx->push('ImageWidth', $iWidth);
				$oCtx->curctx->push('ImageHeight', $iHeight);

				// We reload the thumbnail image to get the real resized width and height.
				$thumbnailPath = $T->getThumbnailPath($iWidth, $iHeight);
				$imageSize = getImageSize($thumbnailPath->toString());
				if ($imageSize !== false) {
					$oCtx->curctx->push('ResizedImageWidth', $imageSize[0]);
					$oCtx->curctx->push('ResizedImageHeight', $imageSize[1]);
				}

				$bProcessed = true;
			} else {
				// If the thumbnailer was not available or not able to create a thumbnail,
				// we fall back to the old method for creating thumbnails (url of the page
				// dynamically resizing the image).
				$oImageResize = new bab_ImageResize();
				$iHeight = $iMaxImageHeight;
				$iWidth = $iMaxImageWidth;
				if (false !== $oImageResize->computeImageResizeWidthAndHeight($sFullPathName, $iWidth, $iHeight)) {
					$sImageUrl .= '&iIdCategory=' . $iIdCategory;
					$sImageUrl .= '&iWidth=' . $iWidth;
					$sImageUrl .= '&iHeight=' . $iHeight;

					$oCtx->curctx->push('AssociatedImage', 1);
					$oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
					$oCtx->curctx->push('ImageUrl', $sImageUrl);
					$oCtx->curctx->push('ImageWidth', $oImageResize->getRealWidth());
					$oCtx->curctx->push('ImageHeight', $oImageResize->getRealHeight());
					$oCtx->curctx->push('ResizedImageWidth', $iWidth);
					$oCtx->curctx->push('ResizedImageHeight', $iHeight);

					$bProcessed = true;
				}
			}
		}
	}

	if (false === $bProcessed) {
		$oCtx->curctx->push('AssociatedImage', 0);
		$oCtx->curctx->push('OriginalImageUrl', '');
		$oCtx->curctx->push('ImageUrl', '');
		$oCtx->curctx->push('ImageWidth', 0);
		$oCtx->curctx->push('ImageHeight', 0);
		$oCtx->curctx->push('ResizedImageWidth', 0);
		$oCtx->curctx->push('ResizedImageHeight', 0);
	}
}




/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdCategory
 * @param int           $iIdTopic
 * @return void
 */
function setTopicAssociatedImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $iIdCategory, $iIdTopic)
{
	require_once dirname(__FILE__) . '/gdiincl.php';
	require_once dirname(__FILE__) . '/artapi.php';
	require_once dirname(__FILE__) . '/pathUtil.class.php';

	$bProcessed		= false;
	$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));

	if(is_dir($sUploadPath)) {
		$aImgInfo = bab_getImageTopic($iIdTopic);
		if (is_array($aImgInfo)) {
			$iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
			$iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
			$sName				= $aImgInfo['name'];
			$sRelativePath		= $aImgInfo['relativePath'];
			$sFullPathName		= $sUploadPath . $sRelativePath . $sName;
			$sImageUrl			= $GLOBALS['babUrlScript'] . '?tg=topusr&idx=getTopicImage&sImage=' . bab_toHtml($sName);
			$sOriginalImageUrl	= $sImageUrl . '&iIdTopic=' . $iIdTopic . '&item=' . $iIdTopic  . '&iIdCategory=' . $iIdCategory;

			$T = @bab_functionality::get('Thumbnailer');
			$thumbnailUrl = null;

			if ($T && $iWidth && $iHeight) {
				// The thumbnailer functionality is available.
			 	$T->setSourceFile($sFullPathName);
				$thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
			}
			if ($thumbnailUrl) {
				// The thumbnailer functionality was able to create a thumbnail.
				$oCtx->curctx->push('AssociatedImage', 1);
				$oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
				$oCtx->curctx->push('ImageUrl', $thumbnailUrl);
				$oCtx->curctx->push('ImageWidth', $iWidth);
				$oCtx->curctx->push('ImageHeight', $iHeight);

				// We reload the thumbnail image to get the real resized width and height.
				$thumbnailPath = $T->getThumbnailPath($iWidth, $iHeight);
				$imageSize = getImageSize($thumbnailPath->toString());
				if ($imageSize !== false) {
					$oCtx->curctx->push('ResizedImageWidth', $imageSize[0]);
					$oCtx->curctx->push('ResizedImageHeight', $imageSize[1]);
				}

				$bProcessed = true;
			} else {
				// If the thumbnailer was not available or not able to create a thumbnail,
				// we fall back to the old method for creating thumbnails (url of the page
				// dynamically resizing the image).
				$oImageResize = new bab_ImageResize();
				$iHeight = $iMaxImageHeight;
				$iWidth = $iMaxImageWidth;
				if (false !== $oImageResize->computeImageResizeWidthAndHeight($sFullPathName, $iWidth, $iHeight)) {
					$sImageUrl .= '&iIdTopic=' . $iIdTopic;
					$sImageUrl .= '&item=' . $iIdTopic;
					$sImageUrl .= '&iIdCategory=' . $iIdCategory;
					$sImageUrl .= '&iWidth=' . $iWidth;
					$sImageUrl .= '&iHeight=' . $iHeight;

					$oCtx->curctx->push('AssociatedImage', 1);
					$oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
					$oCtx->curctx->push('ImageUrl', $sImageUrl);
					$oCtx->curctx->push('ImageWidth', $oImageResize->getRealWidth());
					$oCtx->curctx->push('ImageHeight', $oImageResize->getRealHeight());
					$oCtx->curctx->push('ResizedImageWidth', $iWidth);
					$oCtx->curctx->push('ResizedImageHeight', $iHeight);

					$bProcessed = true;
				}
			}
		}
	}

	if (false === $bProcessed) {
		$oCtx->curctx->push('AssociatedImage', 0);
		$oCtx->curctx->push('OriginalImageUrl', '');
		$oCtx->curctx->push('ImageUrl', '');
		$oCtx->curctx->push('ImageWidth', 0);
		$oCtx->curctx->push('ImageHeight', 0);
		$oCtx->curctx->push('ResizedImageWidth', 0);
		$oCtx->curctx->push('ResizedImageHeight', 0);
	}
}



/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdCategory
 * @param int           $iIdTopic
 * @return void
 */
function setImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $path)
{
	require_once dirname(__FILE__) . '/gdiincl.php';
	require_once dirname(__FILE__) . '/artapi.php';
	require_once dirname(__FILE__) . '/pathUtil.class.php';

	$bProcessed		= false;
		
	$iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
	$iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
	$sFullPathName		= $path;

	$T = @bab_functionality::get('Thumbnailer');
	$thumbnailUrl = null;

	if ($T && $iWidth && $iHeight) {
		// The thumbnailer functionality is available.
		$T->setSourceFile($sFullPathName);
		$thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
	}
	if ($thumbnailUrl) {
		// The thumbnailer functionality was able to create a thumbnail.
		$oCtx->curctx->push('FileIsImage', 1);
		$oCtx->curctx->push('ImageUrl', $thumbnailUrl);
		$bProcessed = true;
	}
	if (false === $bProcessed) {
		$oCtx->curctx->push('FileIsImage', 0);
		$oCtx->curctx->push('ImageUrl', '');
	}
}


class babOvTemplate
{
	public $contexts = array();
	public $handlers = array();
	public $curctx;

	/**
	 * global context
	 * @var bab_context
	 */
	public $gctx;

	/**
	 * @var string
	 */
	public $debug_location;


	public function __construct($args = array())
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

	public function push_ctx(&$ctx)
	{
	$this->contexts[] = &$ctx;
	$this->curctx = &$ctx;
	return $this->curctx;
	}

	public function pop_ctx()
	{
	if( count($this->contexts) > 1 )
		{
		$tmp = array_pop($this->contexts);
		$this->curctx =& $this->contexts[count($this->contexts)-1];
		return $this->curctx;
		}
	}

	public function get_value($var)
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

	public function get_format($var)
	{
	for( $i = count($this->contexts)-1; $i >= 0; $i--)
		{
		$val = $this->contexts[$i]->getFormat($var);
		if( $val !== null)
			{
			return $val;
			}
		}
	return null;
	}


	public function get_variables($contextname)
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

	public function get_currentContextname()
	{
	return $this->curctx->name;
	}

	public function push_handler(&$handler)
	{
	$this->handlers[] = &$handler;
	}

	public function pop_handler()
	{
	if( count($this->handlers) > 0 )
		{
		$tmp = array_pop($this->handlers);
		}
	}

	public function get_handler($name)
	{
	for( $i = count($this->handlers)-1; $i >= 0; $i--)
		{
		$handler = get_class($this->handlers[$i]);
		if(  $handler && (mb_strtolower($handler) == mb_strtolower($name)) )
			{
			return $this->handlers[$i];
			}
		}
	return false;
	}



	public function getArgs($str)
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

	public function handle_tag( $handler, $txt, $args, $fprint = 'printout' )
	{
		$out = '';

		$cls = bab_functionality::get('Ovml/Container/'.$handler, false);

		if (false === $cls) {
			if( $fprint == 'object' )
				{
				return null;
				}
			return sprintf(bab_translate("OVML : the container %s does not exists"), BAB_TAG_CONTAINER.$handler);
		}


		$ctx = new bab_context($handler);
		$ctx->setContent($txt);
		$this->push_ctx($ctx);

		foreach( $args as $key => $val )
			{
			$this->curctx->push($key, $val);
			}

		$cls->setOvmlContext($this);
		if( $fprint == 'object' )
			{
			return $cls;
			}

		$out = $cls->$fprint($txt);
		$this->pop_ctx();
		return $out;
	}


	public function cast($str)
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

	/**
	 * Format output
	 * @param	string	$val		variable content
	 * @param	array	$matches	keys are attributes names, values are attribute value
	 * @param	int		$format		Format of string bab_context::TEXT | bab_context::HTML
	 *
	 * @return string	the modified variable content
	 */
	public function format_output($val, $matches, $format = bab_context::TEXT)
	{
		$saveas = null;
		$attributes = new bab_OvmlAttributes($this, $format);


		foreach( $matches as $p => $v)
		{
			$method = mb_strtolower(trim($p));

			if ('saveas' === $method)
			{
				$saveas = $v;
				continue;
			}

			$val = $attributes->$method($val, $v);
			$attributes->history[$method] = $v;
		}

		$ghtmlentities = $this->get_value('babHtmlEntities');
		if( $ghtmlentities !== false && 0 !== intval($ghtmlentities))
		{
			// apply global htmlentities
			$val = $attributes->htmlentities($val, $ghtmlentities);
		}

		if( $saveas )
		{
			// allways apply saveas as the last attribute
			$val = $attributes->saveas($val, $saveas);
		}

		return $val;
	}




	public function vars_replace($txt)
	{
	if( empty($txt))
		{
		return $txt;
		}

	if(preg_match_all("/<(".BAB_TAG_FUNCTION."|".BAB_TAG_VARIABLE.")([^\s>]*)\s*(\w+\s*=\s*[\"].*?\")*\s*>/s", $txt, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			switch( $m[1][$i] )
				{
				case BAB_TAG_FUNCTION:
					$handler = $m[2][$i];
					$params = array();
					$argsStr = $this->vars_replace(trim($m[3][$i]));

					if($this->match_args($argsStr, $mm))
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

					$cls = bab_functionality::get('Ovml/Function/'.$handler);

					if (false === $cls) {
						$val = sprintf(bab_translate("OVML : the function %s does not exists"), BAB_TAG_FUNCTION.$handler);
					} else {

						$cls->setTemplate($this);
						$cls->setArgs($params);
						$val = $cls->toString();
					}


					// $val = $this->$handler($params);

					$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
					break;
				case BAB_TAG_VARIABLE:
					if( preg_match_all("/(.*?)\[([^\]]+)\]/", $m[2][$i], $m2) > 0)
					{
						//print_r($m2);
						$val = $this->get_value($m2[1][0]);
						$format = $this->get_format($m2[1][0]);
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
					$format = $this->get_format($m[2][$i]);
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
						$val = $this->format_output($val, $params, $format);
						$txt = preg_replace("/".preg_quote($m[0][$i], "/")."/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
						}
					break;
				}
			}
		}

	return $txt;
	}

	public function handle_text($txt)
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

	public function match_args(&$args, &$mm)
	{
	return preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/s", $args, $mm);
	}


	/**
	 * Process ovml source
	 *
	 * @param 	string 	$txt				ovml source content
	 * @param	string	$debug_location		can contain the file path of the processed ovml file or any info to describe where the ovml source is located
	 * @return unknown_type
	 */
	public function printout($txt, $debug_location = null)
	{
		$this->debug_location = $debug_location;
		$replace = bab_replace_get();
		$replace->addIgnoreMacro('OVML');
		$txt = $this->handle_text($txt);
		$replace->removeIgnoreMacro('OVML');
		return $txt;
	}
}





/**
 * All methods of this objects are OVML attributes
 */
class bab_OvmlAttributes
{
	/**
	 *
	 * @var babOvTemplate
	 */
	private $ctx;


	/**
	 * OVML global context
	 * @var bab_context
	 */
	private $gctx;


	/**
	 * contain the list of called methods
	 * @var bool
	 */
	public $history = array();


	/**
	 * bab_context::TEXT | bab_context::HTML
	 * @var int
	 */
	private $format;


	/**
	 * @param	bab_context 	$gctx
	 * @param	int				$format		bab_context::TEXT | bab_context::HTML
	 */
	public function __construct(babOvTemplate $ctx, $format)
	{
		$this->ctx = $ctx;
		$this->gctx = $ctx->gctx;
		$this->format = $format;
	}

	/**
	 * @return bool
	 */
	private function done($method, $option = null)
	{
		if (!isset($this->history[$method]))
		{
			return false;
		}

		if (null !== $option && $this->history[$method] !== $option)
		{
			return false;
		}

		return true;
	}

	/**
	 *
	 * @param string $method
	 * @param array $args
	 * @return string
	 */
	public function __call($method, $args)
	{
		trigger_error(sprintf('Unknown OVML attribute %s="%s" in %s, attribute ignored', $method, $args[1], (string) $this->ctx->debug_location));
		return $args[0];
	}



	/**
	 * Cut string,
	 * for html, remove tags if not allready removed
	 * @param string	$val
	 * @param string	$v
	 * @return string
	 */
	public function strlen($val, $v) {


		if (bab_context::HTML === $this->format )
		{
			if (!$this->done('striptags'))
			{
				$val = $this->striptags($val , '1');
			}

			if (!$this->done('htmlentities', '2'))
			{
				$val = $this->htmlentities($val , '2');
			}

			if (!$this->done('trim'))
			{
				$val = $this->trim($val , 'left');
			}
		}

		$arr = explode(',', $v );
		if( mb_strlen($val) > $arr[0] )
			{
			if (isset($arr[1])) {
				$val = mb_substr($val, 0, $arr[0]).$arr[1];
			} else {
				$val = mb_substr($val, 0, $v);
			}
			$this->gctx->push('substr', 1); // permet de savoir dans la suite du code ovml si la variable a ete coupe ou non
			}
		else
			$this->gctx->push('substr', 0);

		return $val;
	}



	public function striptags($val, $v) {
		switch($v)
			{
			case '1':
				return strip_tags($val);

			case '2':
				$val = eregi_replace('<BR[[:space:]]*/?[[:space:]]*>', "\n ", $val);
				$val = eregi_replace('<P>|</P>|<P />|<P/>', "\n ", $val);
				return strip_tags($val);
			}
	}

	/**
	 * Encoding of html entites can be set only one time per variable
	 * @param string $val
	 * @param int $v
	 * @return unknown_type
	 */
	public function htmlentities($val, $v) {


		if ($this->done(__FUNCTION__, '0'))
		{
			// auto htmlentities has been disabled with an attribute htmlentities="0", others htmlentities are ignored
			return $val;
		}

		if ($this->done(__FUNCTION__, '1') || $this->done(__FUNCTION__, '3'))
		{
			// job allready done
			return $val;
		}

		switch($v)
			{
			case '0': // disable auto htmlentities
				break;

			case '1':
				$val = bab_toHtml($val);
				break;
			case '2':
				require_once dirname(__FILE__).'/tohtmlincl.php';
				$val = bab_unhtmlentities($val);
				break;
			case '3':
				$val = htmlspecialchars($val, ENT_COMPAT, bab_charset::getIso());
				break;
			}

		return $val;
	}


	public function stripslashes($val, $v) {

		if( $v == '1') {
			$val = stripslashes($val);
		}
		return $val;
	}

	public function urlencode($val, $v) {
		if( $v == '1') {
			$val = urlencode($val);
		}

		return $val;
	}

	public function jsencode($val, $v) {
		if( $v == '1') {
			$val = bab_toHtml($val, BAB_HTML_JS);
		}
		return $val;
	}


	public function strcase($val, $v) {
		switch($v)
			{
			case 'upper':
				$val = mb_strtoupper($val); break;
			case 'lower':
				$val = mb_strtolower($val); break;
			}
		return $val;
	}

	public function nlremove($val, $v) {
		if( $v == '1') {
			$val = preg_replace("(\r\n|\n|\r)", "", $val);
		}

		return $val;
	}

	public function trim($val, $v) {
		switch($v)
		{
			case 'left':
				$val = ltrim($val, " \x0B\0\n\t\r".bab_nbsp()); break;
			case 'right':
				$val = rtrim($val, " \x0B\0\n\t\r".bab_nbsp()); break;
			case 'all':
				$val = trim($val, " \x0B\0\n\t\r".bab_nbsp()); break;
		}

		return $val;
	}

	public function nl2br($val, $v) {
		if( $v == '1') {
			$val = nl2br($val);
		}

		return $val;
	}

	public function sprintf($val, $v) {
		return sprintf($v, $val);
	}

	public function date($val, $v) {
		return bab_formatDate($v, $val);
	}

	public function author($val, $v) {
		return bab_formatAuthor($v, $val);
	}

	public function saveas($val, $v) {
		$this->gctx->push($v, $val);
		return $val;
	}

	public function strtr($val, $v) {
		if( !empty($v))
		{
		$trans = array();
		for( $i =0; $i < mb_strlen($v); $i +=2 )
			{
			$trans[mb_substr($v, $i, 1)] = mb_substr($v, $i+1, 1);
			}
		if( count($trans)> 0 )
			{
			$val = strtr($val, $trans);
			}
		}

		return $val;
	}

}










/**
 *  translate text
 */
class Func_Ovml_Function_Translate extends Func_Ovml_Function {


	/**
	 * @return string
	 */
	public function toString()
	{
	$args = $this->args;
	$lang = "";

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
				{
				case 'text':
					$text = $v;
					unset($args[$p]);
					break;
				case 'lang':
					$lang = $v;
					unset($args[$p]);
					break;
				}
			}

		return $this->format_output(bab_translate($text, "", $lang), $args);
		}
	return '';
	}
}

/**
 *  Web statistic
 */
class Func_Ovml_Function_WebStat extends Func_Ovml_Function {


	public function toString()
	{
	$args = $this->args;

	if(count($args))
		{
		$name = '';
		$value = '';
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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
			if( mb_substr($name, 0, 4) == "bab_" )
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
}

class Func_Ovml_Function_SetCookie extends Func_Ovml_Function {


	public function toString()
	{
	global $babBody;
	$name = "";
	$value = "";
	$args = $this->args;

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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
				$_COOKIE[$name] = $value; /* It allows to recover in the same code OVML the value of the cookie (OFSeCookie then OFGetCookie will work) */
				}
			else
				{
				setcookie($name, $value, $expire);
				$_COOKIE[$name] = $value; /* It allows to recover in the same code OVML the value of the cookie (OFSeCookie then OFGetCookie will work) */
				}
			}
		}
	}
}

class Func_Ovml_Function_GetCookie extends Func_Ovml_Function {

	public function toString()
	{
	global $babBody;
	$name = "";
	$args = $this->args;

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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
}

class Func_Ovml_Function_SetSessionVar extends Func_Ovml_Function {

	public function toString()
	{
	global $babBody;
	$args = $this->args;
	$name = '';
	$value = '';

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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
}



class Func_Ovml_Function_GetSessionVar extends Func_Ovml_Function {

	public function toString()
	{
	global $babBody;
	$args = $this->args;
	$name = '';
	$value = '';

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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

}

class Func_Ovml_Function_GetPageTitle extends Func_Ovml_Function {

	public function toString()
	{
	global $babBody;
	$varname = '';
	$args = $this->args;

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
				{
				case 'saveas':
					$varname = $v;
					break;
				}
			}
		if( $varname !== '')
			{
			$this->gctx->push($varname, $babBody->title);
			}
		else
			{
			return $babBody->title;
			}
		}
	else
		{
		return $babBody->title;
		}
	}

}

/**
 * save a variable to global space
 */
class Func_Ovml_Function_PutVar extends Func_Ovml_Function {


	public function toString()
	{
	global $babBody;
	$args = $this->args;
	$name = "";
	$value = "";
	$global = true;

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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


}

/**
 * get a variable
 */
class Func_Ovml_Function_GetVar extends Func_Ovml_Function {


	public function toString()
	{
	global $babBody;
	$name = '';
	$args = $this->args;

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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

}

/**
 *  save a variable to global space if not already defined
 */
class Func_Ovml_Function_IfNotIsSet extends Func_Ovml_Function {


	public function toString()
	{
	$args = $this->args;
	$name = "";
	$value = "";

	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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

}

/**
 * save a array to global space
 */
class Func_Ovml_Function_PutArray extends Func_Ovml_Function {


	public function toString()
	{
	$args = $this->args;
	$name = "";
	$arr = array();
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
				{
				case 'name':
					$name = trim($v);
					break;
				default:
					$arr[trim($p)] = $this->cast(trim($v));
					break;
				}
			}

		$this->gctx->push($name, $arr);
		}
	}
}

/**
 *  save a soap array type to global space
 */
class Func_Ovml_Function_PutSoapArray extends Func_Ovml_Function {

	public function toString()
	{
	$args = $this->args;
	$name = "";
	$arr = array();
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
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
}

class bab_rgp extends Func_Ovml_Function {

	public function rgp($args, $method)
	{
		$name = '';
		$default = '';
		$saveas = false;
		$saveasname = '';
	
		if (count($args))
		{
			foreach( $args as $p => $v)
			{
				switch(mb_strtolower(trim($p)))
				{
					case 'name':
						$name = $v;
						break;
					case 'default':
						$default = $v;
						break;
					case 'saveas':
						if (!empty($v))
							{
							$saveas = true;
							$saveasname = $v;
							}
						break;
				}
			}

			if (!empty($name))
			{
				if ($saveas)
				{
					if(strpos($name,'[') !== false){
						$name = str_replace(']', '', $name);
						$name = explode('[', $name);
						$value = $method($name[0], $default);
						$i = 1;
						while(is_array($value) && isset($name[$i]) && $name[$i]){
							$value = $value[$name[$i]];
							$i++;
						}
						if(is_array($value)){
							$this->gctx->push($saveasname, $default);
						}else{
							$this->gctx->push($saveasname, $value);
						}
					}else{
						$this->gctx->push($saveasname, $method($name, $default));
					}
				}
				else
				{
					if(strpos($name,'[') !== false){
						$name = str_replace(']', '', $name);
						$name = explode('[', $name);
						$value = $method($name[0], $default);
						$i = 1;
						while(is_array($value) && isset($name[$i]) && $name[$i]){
							$value = $value[$name[$i]];
							$i++;
						}
						if(is_array($value)){
							$this->gctx->push($name[0], $default);
						}else{
							$this->gctx->push($name[0], $value);
						}
					}else{
						$this->gctx->push($name, $method($name, $default));
					}
				}
			}
		}
	}
}

class Func_Ovml_Function_Request extends bab_rgp {

	public function toString()
	{
	$this->rgp($this->args, 'bab_rp');
	}
}

class Func_Ovml_Function_Post extends bab_rgp {
	public function toString()
	{
	$this->rgp($this->args, 'bab_pp');
	}

}

class Func_Ovml_Function_Get extends bab_rgp {

	public function toString()
	{
		$this->rgp($this->args, 'bab_gp');
	}
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
class Func_Ovml_Function_Ajax extends Func_Ovml_Function {


	public function toString()
	{
		global $babBody;

		$args = $this->args;
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
				switch(mb_strtolower($p))
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
}


/**
 * Arithmetic operators
 */
class bab_ArithmeticOperator extends Func_Ovml_Function {



	protected function getValue($args, $ope)
	{
		$expr1 = "";
		$expr2 = "";
		$saveas = true;

		if(count($args))
			{
			foreach( $args as $p => $v)
				{
				switch(mb_strtolower(trim($p)))
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
}


/* Arithmetic operators */
class Func_Ovml_Function_AOAddition extends bab_ArithmeticOperator {
	public function toString()
	{
		//print_r($args);
		return parent::getValue($this->args, '+');
	}
}

/* Arithmetic operators */
class Func_Ovml_Function_AOSubtraction extends bab_ArithmeticOperator {
	public function toString()
	{
		return parent::getValue($this->args, '-');
	}
}


/**
 * Arithmetic operators
 */
class Func_Ovml_Function_AOMultiplication extends bab_ArithmeticOperator {
	public function toString()
	{
		return parent::getValue($this->args, '*');
	}
}


/**
 * Arithmetic operators
 */
class Func_Ovml_Function_AODivision extends bab_ArithmeticOperator {

	public function toString()
	{
		return parent::getValue($this->args, '/');
	}

}



/**
 * Arithmetic operators
 */
class Func_Ovml_Function_AOModulus extends bab_ArithmeticOperator {

	public function toString()
	{
		return parent::getValue($this->args, '%');
	}

}


/**
 * save a variable to global space
 */
class Func_Ovml_Function_UrlContent extends Func_Ovml_Function {


	public function toString()
	{
	$args = $this->args;

	$url = "";
	if(count($args))
		{
		foreach( $args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
				{
				case 'url':
					$url = $v;
					$purl = parse_url($url);
					unset($args[$p]);
					break;
				}
			}
		return $this->format_output(preg_replace("/(src=|background=|href=)(['\"])([^'\">]*)(['\"])/e", '"\1\"".bab_rel2abs("\3", $purl)."\""', implode('', file($url))), $args);
		}
	}
}


class Func_Ovml_Function_Header extends Func_Ovml_Function {

	public function toString()
	{
	$value = '';
	if(count($this->args))
		{
		foreach( $this->args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
				{
				case 'value':
					$value = $v;
					break;
				}
			}
		header($value);
		}
	}
}



/**
 * Include another ovml file
 * <OFInclude file="" cache="1|0">
 */
class Func_Ovml_Function_Include extends Func_Ovml_Function {

	public function toString()
	{
	$file = '';
	$cache = false;
	if(count($this->args))
		{
		foreach( $this->args as $p => $v)
			{
			switch(mb_strtolower(trim($p)))
				{
				case 'file':
					$file = $v;
					break;

				case 'cache':
					if ($v) {	$cache = true; }
					break;
				}
			}


			if ($cache)
			{
				return bab_printCachedOvmlTemplate($file, $this->gctx->getvars());
			} else {
				return bab_printOvmlTemplate($file, $this->gctx->getvars());
			}
		}
	}
}





/**
 * Add a stylesheet to the current page
 * the file is relative to "style" folder of ovidentia core
 * <OFAddStyleSheet file="addons/addonname/filename.css">
 */
class Func_Ovml_Function_AddStyleSheet extends Func_Ovml_Function {

	public function toString()
	{
		$file = null;

		foreach($this->args as $p => $v)
		{
		switch(mb_strtolower(trim($p)))
			{
			case 'file':
				$file = $v;
				break;
			}
		}


		if (isset($file))
		{
			global $babBody;
			$babBody->addStyleSheet($file);
		} else {
			trigger_error(sprintf('OFAddStyleSheet : the file attribute is mandatory in %s', (string) $this->gctx->debug_location));
		}
	}
}





class Func_Ovml_Function_Recurse extends Func_Ovml_Function {


	public function toString()
	{
		$handler = $this->template->curctx->getname();
		return $this->template->handle_tag($handler, $this->template->curctx->getcontent(), $this->args);
	}

}


class Func_Ovml_Function_Addon extends Func_Ovml_Function {



	public function toString()
	{
		$args = $this->args;

		global $babBody;
		$output = '';
		if(count($args))
			{
			$function_args = array();
			foreach( $args as $p => $v)
				{
				switch(mb_strtolower(trim($p)))
					{
					case 'name':
						$addon = bab_getAddonInfosInstance($v);
						break;

					case 'function':
						$function = $v;
						break;
					default:
						$function_args[] = $v;
						break;
					}
				}



			if ($addon && $addon->isAccessValid())
				{
				$addonpath = $addon->getPhpPath();
				if( is_file($addonpath."ovml.php" ))
					{
					/* save old vars */
					$oldAddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
					$oldAddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
					$oldAddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
					$oldAddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
					$oldAddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
					$oldAddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

					include_once $GLOBALS['babInstallPath']."utilit/addonsincl.php";
					bab_setAddonGlobals($addon->getId());
					require_once( $addonpath."ovml.php" );

					$call = $addon->getName()."_".$function;
					if( !empty($call)  && function_exists($call) )
						{
						$output = call_user_func_array($call, $function_args );
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


}














/**
 * Return the file manager tree in a html UL LI
 *
 * <OFFileTree [path=""] [file="0|1"] [filelimit="fileNumber"] [maxdepth="depth"] [emptyfolder=0|1] [hidefirstnode="0|1"]>
 *
 * - The path attribute is optional. It define where the tree will start.
 * 		The default value is the entire file manager with rights.
 * - The file attribute is optional, it define if file are display or not.
 * 		The default value is '1'.
 * - The filelimit attribute is optional, it will limit the number of file per folder which will be display. 0 = no limit.
 * 		The default value is '0'.
 * - The maxdepth attribute is optional, limits the number of levels of nested <ul>.
 * 		No maximum depth by default.
 * - The emptyfolder attribute is optional, it will desside if empty folder should be display.
 * 		The default value is '1'.
 * - The hidefirstnode attribute is optional, it define if the name of the first not should be display or not.
 * 		The default value is '0'.
 *
 *
 * Example:
 *
 * The following OVML function :
 * <OFFileTree>
 *
 * Will yield:
 *
 * <ul class="filetree-root">
 * a definir
 * </ul>
 */
class Func_Ovml_Function_FileTree extends Func_Ovml_Function {

	protected	$path = '';
	protected	$delegation = 0;
	protected	$file = 1;
	protected	$filelimit = 0;
	protected	$emptyfolder = 1;

	protected	$selectedClass = 'selected';
	protected	$activeClass = 'active';

	protected	$maxDepth = 100;

	private function getChildTree($relativePath = '')
	{
		global $babDB;
		$nextFolder = '';
		$currentFolder = '';
		$return = '';
		$child = '';

		BAB_FmFolderHelper::getInfoFromCollectivePath($this->path.$relativePath, $iIdRootFolder, $oFmFolder);
		$rPath = new bab_Path(realpath(BAB_FileManagerEnv::getCollectivePath($this->delegation)), $this->path, $relativePath);
		$rPath->orderAsc(bab_Path::BASENAME);

		if( in_array($oFmFolder->getId(), $this->arrid) ){
			foreach($rPath as $subPath){
				if($subPath->isDir() && $subPath->getBasename() != 'OVF'){
					$childs = $this->getChildTree($relativePath.'/'.$subPath->getBasename());
					if($childs != ''){
						$child[] = $this->getChildTree($relativePath.'/'.$subPath->getBasename());
					}
				}
			}

			if($this->file){//file display?
				$req = "SELECT * FROM " . BAB_FILES_TBL . " f WHERE f.bgroup='Y' AND f.state='' AND f.confirmed='Y' AND iIdDgOwner = '" . $babDB->db_escape_string($this->delegation) . "'  AND f.path = '".$babDB->db_escape_string($this->path.$relativePath.'/')."' ORDER BY display_position ASC, name ASC";
				if($this->filelimit != 0){
					$req.= " LIMIT 0,".$this->filelimit;
				}
				$res = $babDB->db_query($req);
				while($arr = $babDB->db_fetch_assoc($res)){
					$child[] = array(
						'type' => 'file',
						'url'=> htmlentities($GLOBALS['babUrlScript'].'?tg=fileman&gr=Y&sAction=getFile&idf='.$arr['id'].'&id='.$iIdRootFolder.'&path='.$arr['path']),
						'name' => $arr['name'],
						'child' => ''
					);
				}
			}

			if($this->emptyfolder || $child != ''){
				$return = array(
					'type' => 'folder',
					'url'=> htmlentities($GLOBALS['babUrlScript'].'?tg=fileman&idx=list&gr=Y&path='.$this->path.$relativePath.'&id='.$iIdRootFolder),
					'name' => $rPath->getBasename(),
					'child' => $child
				);
			}
		}
		return $return;
	}

	function generateUL($currentStage, $firstLevel = false)
	{
		$return = '';
		foreach($currentStage as $nextStage){
			if($firstLevel){
				$return.='<ul class="filetree">';
			}
			if(!($this->hidefirstnode && $firstLevel)){
				//Si on est au prmier niveau et qu'on veut cacher le premier niveau on ne rentre pas dans le IF
				$return.= '<li class="'.$nextStage['type'].'"><span class="unfold-fold"></span><a href="'.$nextStage['url'].'">'.$nextStage['name']."</a>";
			}

			if(isset($nextStage['child']) && $nextStage['child'] != ''){
				if(!($this->hidefirstnode && $firstLevel)){
					$return.= '<ul>' . $this->generateUL($nextStage['child']) . '</ul>';
				}else{
					$return.= $this->generateUL($nextStage['child']);
				}
			}

			if(!($this->hidefirstnode && $firstLevel)){
				$return.= '</li>';
			}
			if($firstLevel){
				$return.='</ul>';
			}
		}
		return $return;
	}

	/**
	 * @return string
	 */
	public function toString()
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
		global $babDB;
		$args = $this->args;

		if (isset($args['maxdepth'])) {
			$this->maxDepth = $args['maxdepth'];
		}else{
			$this->maxDepth = 100;
		}

		if (isset($args['file'])) {
			$this->file = $args['file'];
		}else{
			$this->file = 1;
		}

		if (isset($args['filelimit'])) {
			$this->filelimit = $args['filelimit'];
		}else{
			$this->filelimit = 0;
		}

		if (isset($args['emptyfolder'])) {
			$this->emptyfolder = $args['emptyfolder'];
		}else{
			$this->emptyfolder = 1;
		}

		if (isset($args['path'])) {
			$this->path = $args['path'];
		}else{
			$this->path = '';
		}

		if (isset($args['hidefirstnode'])) {
			$this->hidefirstnode = $args['hidefirstnode'];
		}else{
			$this->hidefirstnode = 0;
		}

		$req = "select * from ".BAB_FM_FOLDERS_TBL." where active='Y' AND id_dgowner = 0 ORDER BY folder ASC";

		$this->arrid = array();
		$res = $babDB->db_query($req);
		while( $arr = $babDB->db_fetch_array($res))
		{
			if(bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id'])){
				$this->arrid[] = $arr['id'];
			}
		}
		$core = array();
		if($this->path == ''){
			$req = "select * from ".BAB_FM_FOLDERS_TBL." where sRelativePath = '' AND id IN (".$babDB->quote($this->arrid).") AND active='Y' AND id_dgowner = 0";

			$res = $babDB->db_query($req);
			$return = '';
			while($arr = $babDB->db_fetch_assoc($res)){
				$this->path = $arr['folder'];
				$core[]= $this->getChildTree();
			}
		}else{
			$core[] = $this->getChildTree();
		}
		return $this->generateUL($core, true);
	}
}














/**
 * Return the article tree in a html UL LI
 *
 * <OFArticleTree [category="id"] [topic="id"] [delegation="id"] [article="0|1"] [articlelimit="articleNumber"] [maxdepth="depth"] [hideempty="none|topic|category|all"] [date="publication|modification|all|none"] [hidefirstnode="0|1"]>
 *
 * - The category attribute is optional. It define where the tree will start.
 * 		The default value is the entire articles tree with rights.
 * - The topic attribute is optional. It define where the tree will start.
 * 		The default value is the entire articles tree with rights.
 * - The delegation attribute is optional.
 * 		The default value is '0'.
 * - The article attribute is optional, it define if article are display or not.
 * 		The default value is '1'.
 * - The filelimit attribute is optional, it will limit the number of file per folder which will be display. 0 = no limit.
 * 		The default value is '0'.
 * - The maxdepth attribute is optional, limits the number of levels of nested <ul>.
 * 		No maximum depth by default.
 * - The date attribute is optional, choose which date should be display with the article title.
 * 		The default value is 'none'.
 * - The hidefirstnode attribute is optional, it define if the name of the first not should be display or not.
 * 		The default value is '0'.
 *
 *
 * Example:
 *
 * The following OVML function :
 * <OFFileTree>
 *
 * Will yield:
 *
 * <ul class="articletree-root">
 * a definir
 * </ul>
 */
class Func_Ovml_Function_ArticleTree extends Func_Ovml_Function {

	protected	$path = null;
	protected	$delegation = 0;
	protected	$article = 1;
	protected	$articlelimit = 0;
	protected	$hideempty = 'none';
	protected	$hidefirstnode = 0;

	protected	$selectedClass = 'selected';
	protected	$activeClass = 'active';
	protected	$date = 'none';


	protected	$maxDepth = 100;


	private function getChild($id, $depth = 1)
	{
		global $babDB, $babBody;
		$return = '';

		$req = "SELECT bab_topics_categories.id as id, bab_topics_categories.title as title
				FROM ".BAB_TOPICS_CATEGORIES_TBL.", ".BAB_TOPCAT_ORDER_TBL."
				WHERE bab_topcat_order.type = 1
				AND bab_topics_categories.id_parent=".$babDB->quote($id)."
				AND bab_topcat_order.id_topcat=bab_topics_categories.id
				ORDER BY bab_topcat_order.ordering ASC";
		$res = $babDB->db_query($req);
		while( $arr = $babDB->db_fetch_assoc($res))
		{
			$child = $this->getChild($arr['id']);
			if(!($child == '' && ($this->hideempty == "all" || $this->hideempty == "category"))){
				$return[] = array(
					'type' => 'category',
					'url'=> htmlentities($GLOBALS['babUrlScript'].'?tg=topusr&cat='.$arr['id']),
					'name' => $arr['title'],
					'child' => $child,
					'date' => ''
				);
			}
		}

		$sTopic = ' ';
		if($this->topic){
			$sTopic = ' AND id = \'' . $babDB->db_escape_string($this->topic) . '\' ';
		}
		$req = "SELECT bab_topics.id as id, bab_topics.category as category
				FROM ".BAB_TOPICS_TBL.", ".BAB_TOPCAT_ORDER_TBL."
				WHERE bab_topcat_order.type = 2
				AND id_cat=".$babDB->quote($id) . $sTopic . "
				AND bab_topcat_order.id_topcat=bab_topics.id
				ORDER BY bab_topcat_order.ordering ASC";
		$req = "select * from ".BAB_TOPICS_TBL." where id_cat=".$babDB->quote($id) . $sTopic;
		$res = $babDB->db_query($req);
		
		if (bab_isUserLogged())
		{
			require_once dirname(__FILE__).'/userinfosincl.php';
			$usersettings = bab_userInfos::getUserSettings();
			$lastlog = $usersettings['lastlog'];
		} else {
			$lastlog = '';
		}

		while( $arr = $babDB->db_fetch_assoc($res))
		{
			$returnTempTopic = '';
			if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id'])){
				$child = '';
				if($this->article){
					$reqArticles = "select * from ".BAB_ARTICLES_TBL." where id_topic=".$babDB->quote($arr['id']) . 'ORDER BY date DESC';
					if($this->articlelimit != 0){
						$reqArticles.= " LIMIT 0,".$this->articlelimit;
					}
					$resArticles = $babDB->db_query($reqArticles);

					$returnArticle = "";
					while( $arrArticles = $babDB->db_fetch_array($resArticles))
					{
						$classNew = '';
						if( $arrArticles['date'] > $lastlog)
						{
							$classNew = 'new';
						}
						$date = '';
						if( $this->date == 'publication'){
							$date = '('.bab_shortDate($arrArticles['date_publication']).')';
						}elseif( $this->date == 'modification'){
							$date = '('.bab_shortDate($arrArticles['date_modification']).')';
						}elseif( $this->date == 'all'){
							$date = '('.bab_shortDate($arrArticles['date_publication']) .' - '. bab_shortDate($arrArticles['date_modification']).')';
						}
						$child[] = array(
							'type' => 'article '.$classNew,
							'url'=> bab_toHtml(bab_sitemap::url($arrArticles['id'], $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arrArticles['id_topic']."&article=".$arrArticles['id'])),
							'name' => $arrArticles['title'],
							'child' => '',
							'date' => $date
						);
					}
				}

				$returnTempTopic = array(
					'type' => 'topic',
					'url'=> htmlentities($GLOBALS['babUrlScript'].'?tg=articles&idx=Articles&topics='.$arr['id']),
					'name' => $arr['category'],
					'child' => $child,
					'date' => ''
				);

				if($child != "" || ($this->hideempty != "all" && $this->hideempty != "topic")){
					$return[] = $returnTempTopic;
				}
			}
		}

		return $return;
	}

	function generateUL($currentStage, $firstLevel = false)
	{
		$return = '';
		foreach($currentStage as $nextStage){
			if($firstLevel){
				$return.='<ul class="articletree">';
			}
			if(!($this->hidefirstnode && $firstLevel)){//Si on est au prmier niveau et qu'on veut cacher le premier niveau on ne rentre pas dans le IF
				$return.= '<li class="'.$nextStage['type'].'"><span class="unfold-fold"></span><a href="'.$nextStage['url'].'">'.$nextStage['name']."</a>".$nextStage['date'];
			}

			if($nextStage['child'] != ''){
				if(!($this->hidefirstnode && $firstLevel)){
					$return.= '<ul>' . $this->generateUL($nextStage['child']) . '</ul>';
				}else{
					$return.= $this->generateUL($nextStage['child']);
				}
			}

			if(!($this->hidefirstnode && $firstLevel)){
				$return.= '</li>';
			}
			if($firstLevel){
				$return.='</ul>';
			}
		}
		return $return;
	}


	/**
	 * @return string
	 */
	public function toString()
	{
		global $babDB;
		$args = $this->args;

		if (isset($args['delegation'])) {
			$this->delegation = $args['delegation'];
		}else{
			$this->delegation = 0;
		}

		if (isset($args['maxdepth'])) {
			$this->maxDepth = $args['maxdepth'];
		}else{
			$this->maxDepth = 100;
		}

		if (isset($args['article'])) {
			$this->article = $args['article'];
		}else{
			$this->article = 1;
		}

		if (isset($args['topic'])) {
			$this->topic = $args['topic'];
		}else{
			$this->topic = null;
		}

		if (isset($args['category'])) {
			$this->category = $args['category'];
		}else{
			$this->category = null;
		}

		if (isset($args['articlelimit'])) {
			$this->articlelimit = $args['articlelimit'];
		}else{
			$this->articlelimit = 0;
		}

		if (isset($args['date'])) {
			$this->date = $args['date'];
		}else{
			$this->date = 'none';
		}

		if (isset($args['hideempty'])) {
			$this->hideempty = $args['hideempty'];
		}else{
			$this->hideempty = 'none';
		}

		if (isset($args['hidefirstnode'])) {
			$this->hidefirstnode = $args['hidefirstnode'];
		}else{
			$this->hidefirstnode = 0;
		}

		$sDelegation = ' ';

		$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($this->delegation) . '\' ';

		$sCategory = ' ';
		if($this->category){
			$sCategory = ' AND bab_topics_categories.id = \'' . $babDB->db_escape_string($this->category) . '\' ';
		}

		$req = "SELECT bab_topics_categories.id as id, bab_topics_categories.title as title
				FROM ".BAB_TOPICS_CATEGORIES_TBL.", ".BAB_TOPCAT_ORDER_TBL."
				WHERE bab_topcat_order.type = 1
				AND bab_topcat_order.id_topcat=bab_topics_categories.id" . $sDelegation . $sCategory."
				ORDER BY bab_topcat_order.ordering ASC";
		$res = $babDB->db_query($req);

		$core = array();
		while( $arr = $babDB->db_fetch_assoc($res))
		{
			$core[]= array(
				'type' => 'category',
				'url' => htmlentities($GLOBALS['babUrlScript'].'?tg=topusr&cat='.$arr['id']),
				'name' => $arr['title'],
				'child' => $this->getChild($arr['id']),
				'date' => '');
		}

		return $this->generateUL($core, true);
	}
}


function bab_rel2abs($relative, $url)
	{
    if (preg_match(',^(https?://|ftp://|mailto:|news:),i', $relative))
        return $relative;

	if( $relative[0] == '#')
		return $relative;

    if (mb_strlen($url['path']) > 1 && $url['path']{mb_strlen($url['path']) - 1} == '/')
        $dir = mb_substr($url['path'], 0, mb_strlen($url['path']) - 1);
    else
        $dir = dirname($url['path']);

    if ($relative{0} == '/')
		{
        $relative = mb_substr($relative, 1);
        $dir = '';
		}
    else if (mb_substr($relative, 0, 2) == './')
		{
        $relative = mb_substr($relative, 2);
		}
    else while (mb_substr($relative, 0, 3) == '../')
		{
        $relative = mb_substr($relative, 3);
        $dir = mb_substr($dir, 0, mb_strrpos($dir, '/'));
		}
    return sprintf('%s://%s%s/%s', $url['scheme'], $url['host'], $dir, $relative);
}





class Func_Ovml_Function_PreviousOrNextArticle extends Func_Ovml_Function {
	
	protected $articleid = null;
	protected $topicid = null;
	protected $excludetopicid = null;
	protected $delegationid = null;	
	protected $archive = null;
	protected $orderby = null;
	protected $order = null;
	protected $topicorder = false;
	protected $minrating = null;
	protected $articles = null;

	protected $saveas = null;


	/**
	 * @return string
	 */
	public function toString()
	{
		return '';
	}



	public function init()
	{
	
		global $babDB;
		$args = $this->args;

		if (isset($args['saveas'])) {
			$this->saveas = $args['saveas'];
		}
		
		if (isset($args['articleid'])) {
			$this->articleid = $args['articleid'];
		}

		if (isset($args['topicid'])) {
			$this->topicid = $args['topicid'];
		}

		if (isset($args['excludetopicid'])) {
			$this->excludetopicid = $args['excludetopicid'];
		}

		if (isset($args['delegationid'])) {
			$this->topicid = $args['delegationid'];
		}

		if (isset($args['orderby'])) {
			$this->orderby = $args['orderby'];
		}

		if (isset($args['order'])) {
			$this->order = $args['order'];
		} else {
			$this->order = 'asc';
		}

		if (isset($args['topicorder'])) {
			$this->topicorder = (mb_strtoupper($args['topicorder']) === 'YES');
		}

		if (isset($args['archive'])) {
			$this->archive = $args['archive'];
		} else {
			$this->archive = 'NO';
		}

		if (isset($args['minrating'])) {
			$this->minrating = $args['minrating'];
		}

		$sDelegation = ' ';
		$sLeftJoin = ' ';
		if (0 != $this->delegationid) {
			$sLeftJoin =
				'LEFT JOIN ' .
					BAB_TOPICS_TBL . ' t ON t.id = at.id_topic ' .
				'LEFT JOIN ' .
					BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = t.id_cat ';

			$sDelegation = ' AND tpc.id_dgowner = \'' . $babDB->db_escape_string($this->delegationid) . '\' ';
		}

		if ($this->topicid === null || $this->topicid === '' ) {
			$this->topicid = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
		} else {
			$this->topicid = array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $this->topicid));
		}

		if (count($this->topicid) == 0) {
			return false;
		}

		switch(mb_strtoupper($this->archive)) {
			case 'YES':
				$this->archive = " AND archive='Y' ";
				break;
			case 'NO':
				 $this->archive = " AND archive='N' ";
				 break;
			default:
				$this->archive = " ";
				break;
		}

		if (!is_numeric($this->minrating)) {
			 $this->minrating = 0;
			 $ratingGroupBy = ' GROUP BY at.id ';
		} else {
			 $ratingGroupBy = ' GROUP BY at.id HAVING average_rating >= ' . $babDB->quote($this->minrating) . ' ';
		}

		$req = '
			SELECT at.id, at.restriction, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
			FROM ' . BAB_ARTICLES_TBL . ' AS at
			LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
			' . $sLeftJoin . '

			WHERE at.id_topic IN (' . $babDB->quote($this->topicid) . ')
			AND (at.date_publication=' . $babDB->quote('0000-00-00 00:00:00') . ' OR at.date_publication <= NOW())'
				. $this->archive
				. $sDelegation
				. $ratingGroupBy
			;


		if ($this->orderby === null || $this->orderby === '') {
			$this->orderby = 'at.date';
		} else {
			switch (mb_strtolower($this->orderby )) {
				case 'title': 
					$this->orderby = 'at.title';
					break;
				case 'rating':
					$this->orderby = 'average_rating';
					break;
				case 'creation':
					$this->orderby = 'at.date';
					break;
				case 'publication':
					$this->orderby = 'at.date_publication';
					break;
				case 'modification':
				default:
					$this->orderby = 'at.date_modification';
					break;
			}
		}


		switch (mb_strtoupper($this->order)) {
			case 'ASC':
				if ($this->topicorder) { /* topicorder=yes : order defined by managers */
					$this->order = 'at.ordering ASC, at.date_modification desc';
				} else {
					$this->order = $this->orderby.' ASC';
				}
				break;
				case 'RAND':
				$this->order = 'rand()';
				break;

			case 'DESC':
			default:
				if ($this->topicorder) { /* topicorder=yes : order defined by managers */
					$this->order = 'at.ordering DESC, at.date_modification ASC';
				} else {
					$this->order = $this->orderby.' DESC';
				}
				break;
		}

		$req .=  'ORDER BY ' . $this->order;
		$res = $babDB->db_query($req);
		while ($arr = $babDB->db_fetch_assoc($res)) {
			if ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])) {
				$this->IdEntries[] = $arr['id'];
			}
		}

		$this->count = count($this->IdEntries);
		if ($this->count == 0) {
			return false;
		}

		$req = '
			SELECT at.id
			FROM ' . BAB_ARTICLES_TBL . ' AS at
			WHERE at.id IN ('.$babDB->quote($this->IdEntries).')
			ORDER BY ' . $this->order;

		$this->articles = $babDB->db_query($req);

		return true;
	}
	
}




class Func_Ovml_Function_NextArticle extends Func_Ovml_Function_PreviousOrNextArticle {

	/**
	 * @return string
	 */
	public function toString()
	{
		global $babDB;

		if (!parent::init()) {
			return '';
		}

		$nextArticleId = '';
		while ($arr = $babDB->db_fetch_assoc($this->articles)) {
			if ($arr['id'] == $this->articleid) {
				if ($arr = $babDB->db_fetch_assoc($this->articles)) {
					$nextArticleId = $arr['id'];
				}
				break;
			}
		}

		if ($this->saveas) {
			$this->gctx->push($this->saveas, $nextArticleId);
			return;
		}
		return $nextArticleId;				
	}
	
}



class Func_Ovml_Function_PreviousArticle extends Func_Ovml_Function_PreviousOrNextArticle {

	/**
	 * @return string
	 */
	public function toString()
	{
		global $babDB;

		if (!parent::init()) {
			return '';
		}
		
		$previousArticleId = '';
		while ($arr = $babDB->db_fetch_assoc($this->articles)) 
		{
			if ($arr['id'] == $this->articleid) {
				break;
			}
			$previousArticleId = $arr['id'];
		}
		
		
		if ($this->saveas) {
			$this->gctx->push($this->saveas, $previousArticleId);
			return;
		}
		return $previousArticleId;
	}
	
}


