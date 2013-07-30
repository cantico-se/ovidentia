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
include_once $GLOBALS['babInstallPath'].'utilit/treebase.php';
include_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';

/**
 * Sitemap rootNode
 * @package sitemap
 */
class bab_siteMapOrphanRootNode extends bab_OrphanRootNode {

	/**
	 * for each id_function the id parent is stored with the rewrite name
	 * key 0 = ID function of parent
	 * key 1 = rewritename
	 * key 2 = functionality name inherited from Func_SitemapDynamicNode
	 * 
	 * @var array
	 */
	private $rewriteIndex_id = array();

	/**
	 * for each rewrite name, the all possibles id are stored as value
	 * @var array
	 */
	private $rewriteIndex_rn = array();


	/**
	 * each node ID under the rewrite root node
	 * @var array
	 */
	private $rewriteIndex_underRoot = array();
	
	
	/**
	 * If the node urls should be rewritten urls or not
	 *
	 * @var bool
	 */
	public $enableRewriting = false;
	

	/**
	 * Tries to append the node $newNode as child of the node having the id $id.
	 * If the node $id was not already created in the tree, $newNode is stored
	 * as an orphan node and will be appended to its parent node when the later
	 * will be created.
	 *
	 * @param bab_Node $newNode
	 * @param string $id
	 * @return boolean
	 */
	public function appendChild(bab_Node $newNode, $id = null) {
		$sitemapItem = $newNode->getData();
		$rewriteName = $sitemapItem->getRewriteName();

		if (isset($this->rewriteIndex_underRoot[$id]) || $rewriteName === bab_siteMap::REWRITING_ROOT_NODE)
		{
			$this->rewriteIndex_underRoot[$newNode->getId()] = '';

			if ($newNode->hasChildNodes())
			{
				// if the node already have childnodes, the childnodes are also under base
				// in this case, this subtree has been added before the rewriting root node

				$I = new bab_nodeIterator($newNode);
				foreach($I as $childNode)
				{
					$this->rewriteIndex_underRoot[$childNode->getId()] = '';
				}
			}
		}


		$this->rewriteIndex_id[$newNode->getId()] = array($id, $rewriteName, $sitemapItem->funcname);

		if (isset($this->rewriteIndex_rn[$rewriteName])) {
			if (isset($this->rewriteIndex_underRoot[$newNode->getId()]))
			{
				// if under root node, the node will have priority in the node detection from rewrite url
				array_unshift($this->rewriteIndex_rn[$rewriteName], $newNode->getId());
			} else {
				array_push($this->rewriteIndex_rn[$rewriteName], $newNode->getId());
			}
		} else {
			$this->rewriteIndex_rn[$rewriteName] = array($newNode->getId());
		}


		return parent::appendChild($newNode, $id);
	}


	/**
	 * Creates a node containing a sitemapItem.
	 *
	 * @param bab_SitemapItem $sitemapItem
	 * @param string $id
	 * @return bab_Node
	 */
	public function createNode($sitemapItem, $id = null)
	{
		$newNode = parent::createNode($sitemapItem, $id);
		$sitemapItem->node = $newNode;
		return $newNode;
	}


	/**
	 * get sitemap node id from the rewrite string
	 * a rewrite string is a path composed by rewrite names and slashes like Article/Category/topic
	 * at each level, the rewrite name is used
	 * @see bab_sitemap
	 *
	 * @param	string	$rewrite
	 * @return string
	 */
	public function getNodeIdFromRewritePath($rewrite)
	{
		$arr = explode('/', trim($rewrite, ' /'));
		if (0 === count($arr)) {
			throw new Exception('Empty rewrite path');
			return null;
		}

		$first = array_shift($arr);

		if (!isset($this->rewriteIndex_rn[$first]))
		{
			bab_debug('first node of rewrite path not found : '.$first);
			return null;
		}
		
		$dynamic_solutions = array();
		
		foreach($this->rewriteIndex_rn[$first] as $nodeId) {
			if (isset($this->rewriteIndex_id[$nodeId])) {

				if (0 === count($arr))
				{
					return $nodeId;
				}

				if ($found = $this->getNextRewriteNode($arr, $nodeId, $dynamic_solutions))
				{
					return $found;
					
				} elseif (!empty($dynamic_solutions)) {
					
					
					
					// try with dynamic nodes
					foreach($dynamic_solutions as $dynsol)
					{
						if ($dynnodeid = $this->getNodeIdFromRewriteInFunctionality($dynsol['id_parent'], $dynsol['funcname'], $dynsol['path']))
						{
							return $dynnodeid;
						}
					}
					
					return null;
					
				} else {
					
					return null;
				}
				
				
			} else {
				bab_debug("the node $nodeId is not in index");
				return null;
			}
		}
		
		
		

		bab_debug("the rewrite name $first has no id_function in index");
		return null;
	}
	
	
	
	
	/**
	 * Get node ID from a dynamic node and a rewrite path
	 * @param string	$funcname
	 * @param array 	$rewritepath	Relative rewrite path
	 */
	private function getNodeIdFromRewriteInFunctionality($id_parent, $funcname, Array $rewritepath)
	{
		$dynnode = bab_functionality::get('SitemapDynamicNode/'.$funcname);
		/*@var $dynnode Func_SitemapDynamicNode */
		
		if (false === $dynnode)
		{
			return null;
		}
		
		
		
		
		
		$parentNode = $this->getNodeById($id_parent);
		
		$itemList = $dynnode->getSitemapItemsFromRewritePath($parentNode, $rewritepath);
		
		if (!isset($itemList))
		{
			return null;
		}
		
		
		foreach($itemList as $sitemapItem)
		{
			$node = $this->createNode($sitemapItem, $sitemapItem->id_function);
			
			if (null === $node)
			{
				// node allready exists
				continue;
			}
			
			$this->addNodeIndexes($node, $sitemapItem);
			$this->appendChild($node, $id_parent);
			
			$id_parent = $sitemapItem->id_function;
		}
		
		return $sitemapItem->id_function;
	}
	
	
	
	/**
	 * Add indexes to the nodes from informations in sitemap item
	 * @param unknown_type $node
	 * @param unknown_type $sitemapItem
	 */
	public function addNodeIndexes(bab_Node $node, bab_sitemapItem $sitemapItem)
	{
		if ($sitemapItem->url)
		{
			// add url in index
			$node->addIndex('url', $sitemapItem->url);
		}
		
		
		if ($sitemapItem->target)
		{
			// add target in index
			$node->addIndex('target', $sitemapItem->target->id_function);
		}
		
		if ($sitemapItem->funcname)
		{
			$node->addIndex('funcname', $sitemapItem->funcname);
		}
	}
	
	
	
	

	/**
	 *
	 * @param array $path
	 * @param string $id_parent
	 * @return string
	 */
	private function getNextRewriteNode(Array $path, $id_parent, Array & $dynamic_solutions)
	{
		$searched_path = $path;
		$first = array_shift($path);

		if (!isset($this->rewriteIndex_rn[$first]))
		{
			$parent_funcname = $this->rewriteIndex_id[$id_parent][2];
			
			if (isset($parent_funcname))
			{
				$dynamic_solutions[] = array(
					'id_parent'	=> $id_parent,
					'path' 		=> $searched_path,
					'funcname' 	=> $parent_funcname
				);
				
				return null;
			}
			
			bab_debug("the rewrite name $first has no id_function in index, id_parent=$id_parent, parent funcname=$parent_funcname");
			return null;
		}
		
		


		foreach($this->rewriteIndex_rn[$first] as $nodeId) {
			
			if (!isset($this->rewriteIndex_id[$nodeId]))
			{
				bab_debug("the node $nodeId has no parent in index");
				continue;
			}
			
			if ($id_parent !== $this->rewriteIndex_id[$nodeId][0])
			{
				// bab_debug("$nodeId parent is not $id_parent");
				continue;
			}

			if (isset($this->rewriteIndex_id[$nodeId]) && $id_parent === $this->rewriteIndex_id[$nodeId][0]) {

				if (0 === count($path))
				{
					return $nodeId;
				}

				if ($next = $this->getNextRewriteNode($path, $nodeId, $dynamic_solutions))
				{
					return $next;
				}
			}
		}

		bab_debug("The rewrite name $first has no id_function in index, path : ".implode('/', $path)." , id_parent : $id_parent, will try with dynamic nodes", DBG_WARNING);
		
		return null;
	}
	
	/**
	 * Get node By id without dynamic nodes
	 */
	public function getStaticNodeById($id)
	{
		return parent::getNodeById($id);
	}
	
	
	/**
	 * Returns the node whose id is given by $id
	 *
	 * Returns the node whose id is given by $id. If no such node exists, returns null.
	 * if not found search in dynamic nodes
	 * 
	 * @param string $id
	 * @return bab_Node | null
	 */
	public function getNodeById($id)
	{
		$node = parent::getNodeById($id);
		
		if (isset($node))
		{
			return $node;
		}
		
		return $this->getDynamicNodeById($id);
	}
	
	
	
	
	/**
	 * Get the first node found under $baseNodeId with a target to $targetId
	 * @param	string	$baseNodeId
	 * @param	string	$targetId
	 *
	 * @return bab_Node
	 */
	public function getNodeByTargetId($baseNodeId, $targetId)
	{
		$customNodes = $this->getNodesByIndex('target', $targetId);
		foreach($customNodes as $customNode)
		{
			/*@var $customNode bab_Node */
	
			// get the first custom node under baseNode
			$testNode = $customNode->parentNode();
			/*@var $testNode bab_Node */
			do {
	
				if ($baseNodeId === $testNode->getId())
				{
					return $customNode;
				}
	
			} while($testNode = $testNode->parentNode());
		}
	
		return null;
	}
	
	
	
	/**
	 * Search node in dynamic functionalities and add it in the current site sitemap
	 * use only if not found in sitemap
	 * return null if not found in not loaded dynamic nodes
	 * 
	 * @param	string	$nodeId
	 * 
	 * @return bab_Node | null
	 */
	protected function getDynamicNodeById($nodeId)
	{
		
		
		$funcname_list = $this->getIndexValues('funcname');
		foreach($funcname_list as $funcname)
		{
			$dynnode = bab_functionality::get('SitemapDynamicNode/'.$funcname);
			/*@var $dynnode Func_SitemapDynamicNode */
			
			if (false === $dynnode)
			{
				continue;
			}
			
			
			
			$newNode = $dynnode->getNodeById($nodeId);
			
			if (!isset($newNode))
			{
				// not found in this functionality
				continue;
			}
			
			
			
			$parentNode = $newNode->parentNode();
			/*@var $parentNode bab_Node */
			
			if (!isset($parentNode))
			{
				continue;
			}
			
			do 
			{
				// if parentNode exists in current sitemap, append subtree
				// prefer the original instead of the target if available
				
				$baseItem = bab_sitemap::getVisibleRootNodeSitemapItem();
				$sitemapParentNode = $this->getNodeByTargetId($baseItem->id_function, $parentNode->getId());
				
				if (null === $sitemapParentNode)
				{
					$sitemapParentNode = parent::getNodeById($parentNode->getId());
				}
				
				if (isset($sitemapParentNode))
				{
					$cn = $parentNode->firstChild();
					/*@var $cn bab_Node */
					do {
						$cn->_tree = $this;
						$sitemapParentNode->appendChild($cn);
					} while ($cn = $cn->nextSibling());
					
					return $newNode;
				}
				
			} while($parentNode = $parentNode->parentNode());
		}
		
		return null;
		
	}
}

/**
 * Sitemap item contener
 * the sitemap is a tree of items, each items is a bab_siteMapItem object
 * @package sitemap
 */
class bab_siteMapItem {

	/**
	 * Unique string in sitemap that identify the item
	 * Mandatory
	 * @var string
	 */
	public $id_function;

	/**
	 * Internationalized name of the item
	 * Mandatory
	 * @var string
	 */
	public $name;

	/**
	 * Internationalized description of the item
	 * Optional
	 * @var string
	 */
	public $description;

	/**
	 * rewrite name
	 * in each level of sitemap tree, the rewrite name must be unique
	 * @see bab_siteMapItem::getRewriteName()
	 * @var string
	 */
	public $rewriteName = null;


	
	
	/**
	 * If the node url should be a modified with a rewrite rule or not
	 * @example ?babrw=/path is a modified url but generated with $enableRewriting = false
	 * @var bool
	 */
	public $disabledRewrite = false;
	
	/**
	 * Specify if a node and childnodes should be ignored in a menu
	 * @see Func_Ovml_Function_SitemapMenu::toString()
	 * @var bool
	 */
	public $menuIgnore = false;
	
	
	/**
	 * Specify if a node should be ignored in a displayed sitemap position path
	 * @see bab_siteMap::getBreadCrumb
	 * @var bool
	 */
	public $breadCrumbIgnore = false;


	/**
	 * Title of page used if not empty
	 * @var string
	 */
	public $pageTitle;

	/**
	 * Description of page used if not empty
	 * @var string
	 */
	public $pageDescription;


	/**
	 * Keywords of page, used if not empty
	 * @var string
	 */
	public $pageKeywords;

	
	/**
	 * canonicalUrl of page, used if not empty
	 * @var string
	 */
	public $canonicalUrl;


	/**
	 * Url
	 * Optional if folder si true or mandatory if folder is false
	 * @var string
	 */
	public $url;

	/**
	 * Javascript string for the onclick attribute in html
	 * Optional
	 * @var string
	 */
	public $onclick;

	/**
	 * node type folder yes/no
	 * If true, the item may contain sub-items
	 * @var bool
	 */
	public $folder;


	/**
	 * Icon classnames
	 * space separated classes
	 * @var string
	 */
	public $iconClassnames;

	/**
	 * symlink target
	 * @var bab_siteMapItem
	 */
	public $target = null;

	/**
	 * The containing bab_Node
	 * @var bab_Node
	 */
	public $node = null;
	
	
	/**
	 * 
	 * @var string
	 */
	public $funcname = null;

	/**
	 * Compare sitemap items
	 * @see bab_Node::sortSubTree()
	 * @see bab_Node::sortChildNodes()
	 */
	public function compare($node) {
		return bab_compare($this->name, $node->name);
	}

	/**
	 * get symlink target or current node
	 * @return bab_siteMapItem
	 */
	public function getTarget()
	{
		if (isset($this->target)) {
			return $this->target;
		}

		return $this;
	}
	


	/**
	 * return rewrite name of node
	 * @return string
	 */
	public function getRewriteName()
	{
		if (null !== $this->rewriteName && '' !== $this->rewriteName) {
			return $this->rewriteName;
		}

		return $this->id_function;
	}


	/**
	 * return space-separated classname string.
	 *
	 * @return string
	 */
	public function getIconClassnames()
	{
		return $this->iconClassnames;
	}


	/**
	 * Returns a list of comma separated keywords for an html meta/keywords tag.
	 *
	 * @param bool		$inherit		If true and pageKeywords are not defined the method will return the pageKeywords of
	 * 									the closest parent with pageKeywords defined.
	 * 
	 * @return string
	 */
	public function getPageKeywords($inherit = true)
	{
		if ($keywords = $this->getSitemapPageKeywords($inherit)) {
			return $keywords;
		}
		
		$head = bab_getInstance('babHead');
		
		if (null !== $keywords = $head->getKeywords())
		{
			return $keywords;
		}
		
		return null;
	}
	
	
	/**
	 * Returns a list of comma separated keywords defined in sitemap for an html meta/keywords tag.
	 *
	 * @param bool		$inherit		If true and pageKeywords are not defined the method will return the pageKeywords of
	 * 									the closest parent with pageKeywords defined.
	 * @return string
	 */
	private function getSitemapPageKeywords($inherit = true)
	{
		if (!empty($this->pageKeywords)) {
			return $this->pageKeywords;
		}
		
		if ($inherit
				&& $this->node
				&& ($parentNode = $this->node->parentNode())
				&& ($parentSitemapItem = $parentNode->getData())) {
			return $parentSitemapItem->getSitemapPageKeywords($inherit);
		}
		
		return null;
	}
	
	
	
	public function getCanonicalUrl()
	{
		if (!empty($this->canonicalUrl)) {
			return $this->canonicalUrl;
		}
		
		return null;
	}
	
	
	
	/**
	 * Returns the page title for the html title tag.
	 *
	 * @param bool		$inherit		If true and pageTitle are not defined the method will return the pageTitle of
	 * 									the closest parent with pageTitle defined.
	 * 
	 * @return string
	 */
	public function getPageTitle($inherit = false)
	{
		if ($title = $this->getSitemapPageTitle($inherit))
		{
			return $title;
		}
	
		$head = bab_getInstance('babHead');
		if (null !== $title = $head->getTitle())
		{
			return $title;
		}
	
		return '';
	}


	/**
	 * Returns the page title defined in sitemap for the html title tag.
	 *
	 * @param bool		$inherit		If true and pageTitle are not defined the method will return the pageTitle of
	 * 									the closest parent with pageTitle defined.
	 * @return string
	 */
	private function getSitemapPageTitle($inherit = false)
	{
		if (!empty($this->pageTitle)) {
			return $this->pageTitle;
		}
		
		if ($inherit
		  && $this->node
		  && ($parentNode = $this->node->parentNode())
		  && ($parentSitemapItem = $parentNode->getData())) {
			return $parentSitemapItem->getSitemapPageTitle($inherit);
		} 
		
		return null;
	}


	/**
	 * Returns the page description for an html meta/description tag.
	 *
	 * @param bool		$inherit		If true and pageDescription are not defined the method will return the pageDescription of
	 * 									the closest parent with pageDescription defined.
	 */
	public function getPageDescription($inherit = true)
	{
		if ($description = $this->getSitemapPageDescription($inherit))
		{
			return $description;
		}
		
		$head = bab_getInstance('babHead');
		if (null !== $description = $head->getDescription())
		{
			return $description;
		}
		
		return $this->description;
	}



	/**
	 * Returns the page description defined in sitemap for an html meta/description tag.
	 *
	 * @param bool		$inherit		If true and pageDescription are not defined the method will return the pageDescription of
	 * 									the closest parent with pageDescription defined.
	 * 
	 * @return null
	 */
	private function getSitemapPageDescription($inherit)
	{
		if (!empty($this->pageDescription)) {
			return $this->pageDescription;
		}
	
		if ($inherit
				&& $this->node
				&& ($parentNode = $this->node->parentNode())
				&& ($parentSitemapItem = $parentNode->getData())) {
			return $parentSitemapItem->getSitemapPageDescription($inherit);
		}
		
		return null;
	}
	
	
	/**
	 * Test if babrw is used in url or if the url contain only the path 
	 * @return bool
	 */
	public function rewritingEnabled()
	{
		if (!isset($this->node) || !isset($this->node->_tree))
		{
			return false;
		}

		return $this->node->_tree->enableRewriting;
	}
	


	/**
	 * Get rewritten url is the rewriting is enabled or the regular url if there is no rewriting
	 * @return string
	 */
	public function getRwUrl()
	{
		$url = $this->url;
		
		if ('' === $url || $this->disabledRewrite)
		{
			return $url;
		}
		
		if ('http' === mb_substr($url, 0, 4) && $GLOBALS['babUrl'] !== mb_substr($url, 0, strlen($GLOBALS['babUrl'])))
		{
			return $url;
		}
		
		
		if ($this->rewritingEnabled()) {
			$url = bab_Sitemap::rewrittenUrl($this->id_function);
			
		} else {
			$path = bab_Sitemap::rewrittenUrl($this->id_function);
			if ($path)
			{
				$url = '?babrw='.$path;
			}
			
			
		}

		return $url;
	}
}




/**
 * Sitemap manipulation and access
 * @package sitemap
 */
class bab_siteMap {

	/**
	 * node UID of current page, used for breadCrumb
	 * @see bab_siteMap::setPosition()
	 * @var string
	 */
	private static $current_page = null;
	
	/**
	 * 
	 * @var array
	 */
	private static $hitcache = array();
	

	private $siteMapName 		= '';
	private $siteMapDescription = '';

	/**
	 * Visible root node UID to use in interfaces
	 * @var string
	 */
	private $visibleRootNode = null;

	/**
	 * This string in a rewritten url specifies that node url rewriting
	 * should stop a this node.
	 *
	 * @var string
	 */
	const REWRITING_ROOT_NODE	=	'rewriting-root-node';

	/**
	 * set sitemap informations
	 * @return bab_siteMapOrphanRootNode
	 */
	public function __construct($name, $description) {

		$this->siteMapName 			= $name;
		$this->siteMapDescription	= $description;

		return $this;
	}

	/**
	 * Sitemap name
	 * @return string
	 */
	public function getSiteMapName() {
		return $this->siteMapName;
	}


	/**
	 * Sitemap description
	 * @return string
	 */
	public function getSiteMapDescription() {
		return $this->siteMapDescription;
	}

	/**
	 *
	 * @param array $path
	 * @param int $levels
	 * @return bab_siteMapOrphanRootNode
	 */
	public function getRootNode($path = null, $levels = null) {
		return bab_siteMap::get($path, $levels);
	}

	/**
	 *
	 * @param string $uid
	 * @return unknown_type
	 */
	public function setVisibleRootNode($uid) {
		$this->visibleRootNode = $uid;
	}

	/**
	 * get the visible root node to use in interfaces
	 * default is babDGAll
	 * externally provided sitemap can set the value to another node in the same sitemap
	 * @return string
	 */
	public function getVisibleRootNode()
	{
		return $this->visibleRootNode;
	}
	


	/**
	 * Delete sitemap for current user or id_user
	 * @param	int		$id_user
	 */
	public static function clear($id_user = false) {

		global $babDB;

		self::lockTables();

		if ((isset($GLOBALS['BAB_SESS_LOGGED']) && $GLOBALS['BAB_SESS_LOGGED'] && false === $id_user) || false !== $id_user) {

			if (false === $id_user) {
				$id_user = $GLOBALS['BAB_SESS_USERID'];
			}


			$babDB->db_query('UPDATE '.BAB_USERS_TBL.'
			SET
				id_sitemap_profile=\'0\'
				WHERE id='.$babDB->quote($id_user)
			);
			
			self::$hitcache = array();
			
		} else {

			// delete profile

			$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.'
				WHERE id_profile=\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'"
			);

			$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILE_VERSIONS_TBL.'
				WHERE id_profile=\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'"
			);
		}


		self::unlockTables();
	}

	/**
	 * Delete sitemap for all users
	 *
	 * need lock table to prevent a sitemap build in the middle of delete process
	 * truncate tables is not possible within a transaction.
	 *
	 */
	public static function clearAll() {
		global $babDB;

		self::lockTables();

		// bab_debug('Clear sitemap...', DBG_TRACE, 'Sitemap');

		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILES_TBL.' WHERE id<>\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'");
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILE_VERSIONS_TBL);
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL);
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTIONS_TBL);
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_LABELS_TBL);
		$babDB->db_query('UPDATE '.BAB_USERS_TBL." SET id_sitemap_profile='0'");
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_TBL);
		
		self::$hitcache = array();

		self::unlockTables();

		//bab_siteMap::build();

	}



	private static function lockTables()
	{
		global $babDB;

		$babDB->db_query('
			LOCK TABLES
				'.BAB_SITEMAP_PROFILES_TBL.' 				 	WRITE,
				'.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' 		 	WRITE,
				'.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 		 	WRITE,
				'.BAB_SITEMAP_FUNCTIONS_TBL.' 				 	WRITE,
				'.BAB_SITEMAP_FUNCTION_LABELS_TBL.' 		 	WRITE,
				'.BAB_USERS_TBL.' 							 	WRITE,
				'.BAB_SITEMAP_TBL.' 						 	WRITE
		');

	}


	private static function unlockTables()
	{
		global $babDB;

		$babDB->db_query('UNLOCK TABLES');

	}




	/**
	 *
	 * @param 	resource 	$res
	 *
	 * @return bab_siteMapOrphanRootNode
	 */
	private static function buildFromResource($res)
	{
		global $babDB;
		$rootNode = new bab_siteMapOrphanRootNode();

		$node_list = array();

		// bab_debug(sprintf('bab_siteMap::get() %d nodes', $babDB->db_num_rows($res)));

		$current_delegation_node = NULL;


		while ($arr = $babDB->db_fetch_assoc($res)) {

			if ('root' === $arr['parent_node']) {
				$current_delegation_node = $arr['id_function'];
			}


			if ('?' === @mb_substr($arr['url'],0,1)) {
				// sitemap store URL without the php filename
				$arr['url'] = $GLOBALS['babPhpSelf'].$arr['url'];
			}

			$data = new bab_siteMapItem();
			$data->id_function 		= $arr['id_function'];
			$data->name 			= $arr['name'];
			$data->description 		= $arr['description'];
			$data->url 				= $arr['url'];
			$data->onclick 			= $arr['onclick'];
			$data->folder 			= 1 == $arr['folder'];
			$data->iconClassnames	= $arr['icon'];
			$data->rewriteName		= $arr['rewrite'];
			if ('' !== $arr['funcname'])
			{
				$data->funcname	= $arr['funcname'];
			}

			$node_list[$arr['id']] = $arr['id_function'];

			// the id_parent is NULL if there is no parent, the items are already ordered so the NULL is for root item only
			$id_parent = isset($node_list[$arr['id_parent']]) ? $node_list[$arr['id_parent']] : NULL;

			$node = $rootNode->createNode($data, $node_list[$arr['id']]);

			if (null === $node) {
				// bab_debug((string) $rootNode);
				return $rootNode;
			}

			$rootNode->addNodeIndexes($node, $data);
				
			$rootNode->appendChild($node, $id_parent);
		}

		// each level will be sorted individually if needed before each usage
		// $rootNode->sortSubTree();

		// bab_debug((string) $rootNode);

		return $rootNode;
	}



	/**
	 * Get sitemap selected in site options
	 *
	 * @return bab_siteMapOrphanRootNode
	 */
	public static function getFromSite()
	{
		global $babBody;

		$sitemapId = $babBody->babsite['sitemap'];
		$sitemap = self::getByUid($sitemapId);
		if (!isset($sitemap)) {
			$sitemap = self::getByUid('core');
		}

		return $sitemap;
	}
	
	
	/**
	 * Get sitemap profil UID of user
	 * return the CRC of the list of functions accessibles to the user
	 * 
	 * @since 7.8.3 	
	 *
	 * @param	array	$path
	 * @param	int		$levels
	 * @return int (crc32) or NULL if no sitemap registered for the user
	 */
	public static function getProfilVersionUid($path = null, $levels = null)
	{
		/** @var $babDB bab_Database */
		global $babDB;
		
		$root_function = null === $path ? null : end($path);
		
		$query_root_function = null === $root_function ? 'pv.root_function IS NULL' : 'pv.root_function='.$babDB->quote($root_function);
		$query_levels = null === $levels ? 'pv.levels IS NULL' : 'pv.levels='.$babDB->quote($levels);
		
		if (!bab_isUserLogged())
		{
			$query = "SELECT pv.uid_functions FROM bab_sitemap_profile_versions pv WHERE pv.id_profile=".$babDB->quote(BAB_UNREGISTERED_SITEMAP_PROFILE); 
		} else {
			$query = "SELECT pv.uid_functions FROM bab_users u, bab_sitemap_profile_versions pv WHERE  pv.id_profile=u.id_sitemap_profile AND u.id=".$babDB->quote(bab_getUserId());
		}
		
		$query .= ' AND '.$query_root_function;
		$query .= ' AND '.$query_levels;
		
		$res = $babDB->db_query($query);
		
		if (0 === $babDB->db_num_rows($res)) {
			return null;
		}
		
		if (1 !== $babDB->db_num_rows($res)) {
			throw new Exception('error in profile version');
			return null;
		}
		
		$arr = $babDB->db_fetch_assoc($res);
		
		return (int) $arr['uid_functions'];
	}
	
	



	/**
	 * Get sitemap default for current user
	 *
	 * @param	array	$path
	 * @param	int		$levels
	 *
	 * @return bab_siteMapOrphanRootNode
	 */
	public static function get($path = null, $levels = null) {

		// echo "sitemap get ".implode('/',$path)."\n";

		include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';


		$cachekey = null === $path ? '0' : end($path);
		if (null !== $levels) {
			$cachekey .= ','.$levels;
		}

		if (isset(self::$hitcache[$cachekey])) {
			return self::$hitcache[$cachekey];
		}

		if (isset(self::$hitcache['0'])) {
			// if global sitemap already requested on same page, use this sitemap
			return self::$hitcache['0'];
		}


		/** @var $babDB bab_Database */
		global $babDB;

		
		$root_function = null === $path ? null : end($path);

		$query_root_function = null === $root_function ? 'pv.root_function IS NULL' : 'pv.root_function='.$babDB->quote($root_function);
		$query_levels = null === $levels ? 'pv.levels IS NULL' : 'pv.levels='.$babDB->quote($levels);


		$query = 'SELECT
				s.id,
				s.id_parent,
				sp.id_function parent_node,
				f.id_function,
				fl.name,
				fl.description,
				f.url,
				f.onclick,
				f.folder,
				f.icon,
				f.rewrite,
				f.funcname, 
				s.progress,
				pv.id profile_version
			FROM
				'.BAB_SITEMAP_FUNCTIONS_TBL.' f,
				'.BAB_SITEMAP_FUNCTION_LABELS_TBL.' fl,
				'.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' fp,
				'.BAB_SITEMAP_TBL.' s
					LEFT JOIN '.BAB_SITEMAP_TBL.' sp ON sp.id = s.id_parent,
				'.BAB_SITEMAP_PROFILES_TBL.' p
					LEFT JOIN '.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' pv
					ON p.id = pv.id_profile
					AND '.$query_root_function.'
					AND '.$query_levels.'
			';


		if ($GLOBALS['BAB_SESS_USERID']) {

			$query .= ', '.BAB_USERS_TBL.' u

			WHERE
				s.id_function = f.id_function
				AND fp.id_function = f.id_function
				AND fp.id_profile = p.id
				AND p.id = u.id_sitemap_profile
				AND u.id = '.$babDB->quote($GLOBALS['BAB_SESS_USERID']).'
				';

		} else {
			$query .= 'WHERE
				s.id_function = f.id_function
				AND fp.id_function = f.id_function
				AND fp.id_profile = p.id
				AND p.id = \''.BAB_UNREGISTERED_SITEMAP_PROFILE.'\'
				';
		}


		$query .= '
			AND fl.id_function=f.id_function
			AND fl.lang='.$babDB->quote($GLOBALS['babLanguage']).'
		';

		
		/*
		$viewable_delegations = array();

		$delegations = bab_getUserVisiblesDelegations();
		foreach($delegations as $arr) {
			$viewable_delegations[$arr['id']] = $arr['id'];
		}
		*/
		// $query .= ' AND (s.id_dgowner IS NULL OR s.id_dgowner IN('.$babDB->quote($viewable_delegations).') )';
		// tenir compte que de DGAll pour le moment
		// $query .= ' AND s.id_dgowner IS NULL ';

		$query .= 'ORDER BY s.lf';

		// bab_debug($query);
		
		$res = $babDB->db_query($query);
		
		if (0 === $babDB->db_num_rows($res)) {
			// no sitemap for user, build it
			
			self::build($path, $levels);
			$res = $babDB->db_query($query);
		}
		

		$firstnode = $babDB->db_fetch_assoc($res);

		if (null === $firstnode['profile_version']) {
			// the profile version is missing, add version to profile
			// the user have a correct profile and a correct sitemap but the sitemap is incomplete
			// additional nodes need to be created in sitemap without deleting the profile
			self::repair($path, $levels);
			$res = $babDB->db_query($query);

		} else {

			$babDB->db_data_seek($res, 0);
		}


		$rootNode = self::buildFromResource($res);

		self::$hitcache[$cachekey] = $rootNode;

		return $rootNode;
	}

	/**
	 * Get the url of a sitemap node or null if the node does not exists or if there is no url
	 * @param	string	$sId
	 */
	public static function getUrlById($sId) {

		$notesNode = self::get()->getNodeById($sId);

		if (!isset($notesNode)) {
			return null;
		}

		$sitemapItem = $notesNode->getData();
		return $sitemapItem->url;
	}

	/**
	 * Get the name of a sitemap node or null if the node does not exists or if there is no url
	 * @param	string	$sId
	 * @return string
	 */
	public static function getNameById($sId) {

		$notesNode = self::get()->getNodeById($sId);

		if (!isset($notesNode)) {
			return null;
		}

		$sitemapItem = $notesNode->getData();
		if (!$sitemapItem->name) {
			throw new Exception('Missing name on node '.$sId);
		}

		return $sitemapItem->name;
	}


	/**
	 * Build sitemap for current user
	 * @return boolean
	 */
	public static function build($path, $levels) {


		include_once $GLOBALS['babInstallPath'].'utilit/sitemap_build.php';
		return bab_siteMap_build($path, $levels);

	}


	/**
	 * Add missing node to current sitemap
	 * @return boolean
	 */
	private static function repair($path, $levels) {


		include_once $GLOBALS['babInstallPath'].'utilit/sitemap_build.php';
		return bab_siteMap_repair($path, $levels);

	}



	/**
	 * Get the list of available sitemap
	 * This method collect all sitemap by fireing an event
	 *
	 * @see bab_eventBeforeSitemapList
	 * @see bab_siteMapOrphanRootNode
	 *
	 * @return array	of bab_siteMap
	 */
	public static function getList() {

		$event = new bab_eventBeforeSiteMapList;
		$core = new bab_siteMap(bab_translate('Default'), bab_translate('Default sitemap proposed by Ovidentia'));

		$event->addSiteMap('core', $core);

		bab_fireEvent($event);

		return $event->getAvailable();

	}


	/**
	 * get the visible root node to use in interfaces
	 * default is babDGAll
	 * externally provided sitemap can set the value to another node in the same sitemap
	 * @param string	$uid sitemap uid
	 * @return string
	 */
	public static function getVisibleRootNodeByUid($uid)
	{
		$list = self::getList();

		if (!isset($list[$uid])) {
			return null;
		}
		
		if($list[$uid]->getVisibleRootNode() !== NULL){
			return $list[$uid]->getVisibleRootNode();
		}else{
			return 'DGAll';
		}	
	}
	
	
	/**
	 * get the visible root node associated to site configured sitemap
	 * @return string
	 */
	public static function getSitemapRootNode()
	{
		global $babBody;
		return self::getVisibleRootNodeByUid($babBody->babsite['sitemap']);
	}
	
	
	/**
	 * get the visible root node associated to site configured sitemap
	 * @return bab_siteMapItem
	 */
	public static function getVisibleRootNodeSitemapItem()
	{
		global $babBody;
		
		$nodeId = self::getSitemapRootNode();
		$sitemapRootNode = bab_sitemap::getByUid($babBody->babsite['sitemap']);
		if (null === $sitemapRootNode)
		{
			return null;
		}
		
		$node = $sitemapRootNode->getNodeById($nodeId);

		if (!isset($node)) {
			return null;
		}

		return $node->getData();
	}
	


	/**
	 * Get sitemap tree by unique UID from sitemap list
	 * @param	string	$uid
	 * @return bab_siteMapOrphanRootNode
	 */
	public static function getByUid($uid) {
		$list = self::getList();

		if (!isset($list[$uid])) {
			return null;
		}

		return $list[$uid]->getRootNode();
	}







	/**
	 * Set position in sitemap for current page, if the position is not already defined by the URL
	 *
	 *
	 * @param	string	$uid_prefix	sitemap node UID prefix before delegation identification
	 * @param	string	$uid_suffix sitemap node UID suffix after delegation identification
	 *
	 */
	public static function setPosition($uid_prefix, $uid_suffix = null) {
		
		if (null !== self::$current_page) {
			return;
		}


		if (null === $uid_suffix) {

			self::$current_page = $uid_prefix;
		} else {

			// for now current delegation is always DGAll, suffix is just appended

			self::$current_page = $uid_prefix.$uid_suffix;

		}
	}


	/**
	 * Returns the position in sitemap for current page.
	 *
	 * @return string
	 */
	public static function getPosition() {
		return self::$current_page;
	}

	
	/**
	 * Returns the position in sitemap for current page as a sitemapItem object. 
	 * follow target in sitemap item
	 * 
	 * @since 7.8.92
	 *
	 * @return bab_SitemapItem
	 */
	public static function getRealPosition() {
		
		global $babBody;
		
		$position = self::getPosition();
		$sitemap = self::getByUid($babBody->babsite['sitemap']);
		if (isset($sitemap))
		{
			$node = $sitemap->getNodeById($position);
		
			if (isset($node)) {
				$sitemapItem = $node->getData();
				/*@var $sitemapItem bab_SitemapItem */
				return $sitemapItem->getTarget();
			}
		}
		
		return null;
	}




	/**
	 * get position in the sitemap from homepage (delegation node) to current position
	 * empty array is for an empty breadcrumb (no breadcrumb should be displayed)
	 * null if for breadcrumb failure because of unkhnown postion (no position set) in this case, the last khnow breadcrumb can be displayed
	 *
	 * @see bab_sitemap::setPosition()
	 *
	 * @param	string	$sitemap_uid	ID of sitemap tree, default is sitemap selected in site options
	 * @param	string	$baseNodeId		start of the breadcrumb, default is the visible root node of the sitemap
	 * @param	string	$nodeId			current page node, default is the automatic current page
	 *
	 * @return array | null				Array of bab_Node 
	 */
	public static function getBreadCrumb($sitemap_uid = null, $baseNodeId = null, $nodeId = null) {

		if (!isset(self::$current_page) && !isset($nodeId)) {
			return null;
		}

		if (null === $sitemap_uid) {
			global $babBody;
			$sitemap_uid = $babBody->babsite['sitemap'];
			$sitemap = self::getByUid($sitemap_uid);
			if (!isset($sitemap)) {
				$sitemap_uid = 'core';
				$sitemap = self::getByUid('core');
			}
		} else {
			$sitemap = self::getByUid($sitemap_uid);
		}
		
		if (!isset($sitemap)) {
			bab_debug(sprintf('Breadcrumb : empty breadcrumb because sitemap not found %s', $sitemap_uid), DBG_ERROR);
			return array();
		}


		if (!isset($nodeId))
		{
			$nodeId = self::$current_page;
		}

		// if undefined baseNodeId, use the default visible root for sitemap

		if (!isset($baseNodeId))
		{
			$baseNodeId = bab_siteMap::getVisibleRootNodeByUid($sitemap_uid);
		}

		
		$baseNode = $sitemap->getNodeById($baseNodeId);
		if (!isset($baseNode)) {
			// basenode not found
			bab_debug(sprintf('Breadcrumb : empty breadcrumb because baseNodeId=%s not found in sitemap %s', $baseNodeId, $sitemap_uid), DBG_ERROR);
			return array();
		}


		// verify if the $nodeId or target is under baseNode
		
		
		$currentNode = $sitemap->getNodeById($nodeId);
		$matchingNodes = array();
		
		
		if (!isset($currentNode)) {
			// nodeId not found
			bab_debug(sprintf('Breadcrumb : empty breadcrumb because nodeId=%s not found in sitemap %s', $nodeId, $sitemap_uid), DBG_ERROR);
			return array();
		}
		

		
		// test if current node is under baseNode
		
		
		$testNode = $currentNode;
		do {
		
			if ($baseNodeId === $testNode->getId())
			{
				$matchingNodes[] = $currentNode;
				break;
			}
		
		} while($testNode = $testNode->parentNode());
		
		
		// test if a target to current node is under basenode
		
		$customNodes = $sitemap->getNodesByIndex('target', $nodeId);
		foreach($customNodes as $customNode)
		{
			/*@var $customNode bab_Node */
		
			// get the first custom node under baseNode
			$testNode = $customNode->parentNode();
			/*@var $testNode bab_Node */
			do {
		
				if ($baseNodeId === $testNode->getId())
				{
					$matchingNodes[] = $customNode;
					break;
				}
		
			} while($testNode = $testNode->parentNode());
		}
		
		
		
		
		if (count($matchingNodes) === 0) {
		
			bab_debug(sprintf('Breadcrumb : The node %s does not exists in sitemap %s under baseNode %s', $nodeId, $sitemap_uid, $baseNodeId), DBG_ERROR);
			return array();
		}
		

		if (count($matchingNodes) > 1) {

			bab_debug(sprintf('Breadcrumb : Multiple matching nodes for %s in sitemap %s under baseNode %s', $nodeId, $sitemap_uid, $baseNodeId), DBG_ERROR);
			// return array();
		}

		$node = $matchingNodes[0];

		$breadCrumbs = array($node);
		while (($node->getId() !== $baseNodeId) && ($node = $node->parentNode())) {
			if (false === $node->getData()->breadCrumbIgnore)
			{
				array_unshift($breadCrumbs, $node);
			}
		}
		
		
		

		return $breadCrumbs;
	}



	/**
	 * Select current node from the rewrite url and extract sitemap node url parameters in an array
	 * @param	string	$rewrite		Rewrite string (ex : babrw variable)
	 * @param	bool	$setPosition	if set to true, the current page will be associated to the corresponding sitemap node
	 * @return array
	 */
	public static function extractNodeUrlFromRewrite($rewrite, $setPosition = false)
	{
		$root = bab_siteMap::getFromSite();
		$nodeId = $root->getNodeIdFromRewritePath($rewrite);

		if (null === $nodeId)
		{
			return false;
		}
		
		if ($setPosition)
		{
			bab_siteMap::setPosition($nodeId);
		}


		$node = $root->getNodeById($nodeId);
		if (!$node)
		{
			return false;
		}

		$sitemapItem = $node->getData();
		$tmp = explode('?', $sitemapItem->url);

		if (count($tmp) <= 1) {
			return array(); // the node exists but there are no variables : homepage
		}

		parse_str($tmp[1], $arr);

		return $arr;
	}
	
	/**
	 * Get the rewritten url of a sitemap node or an ur l with babrw= if the rewriting is disabled
	 * @param string $id_function
	 * @return string
	 */
	public static function url($id_function)
	{
		$node = self::getFromSite()->getNodeById($id_function);

		if (!isset($node)) {
			return null;
		}

		$sitemapItem = $node->getData();
		
		return $sitemapItem->getRwUrl();
	}


	/**
	 * Get the rewritten url of a sitemap node
	 * @param string $id_function
	 * @return string
	 */
	public static function rewrittenUrl($id_function)
	{
		static $root = null;

		if (!isset($root)) {
			$root = bab_siteMap::getFromSite();
		}

		$node = $root->getNodeById($id_function);

		if (!$node)
		{
			bab_debug('Failed to get '.$id_function.' in sitemap');
			return null;
		}
		


		$arr = array();

		do
		{
			$sitemapItem = $node->getData();
			if (!$sitemapItem || $sitemapItem->getRewriteName() === self::REWRITING_ROOT_NODE) {
				break;
			}
			array_unshift($arr, $sitemapItem->getRewriteName());
		} while ($node = $node->parentNode());

		return implode('/', $arr);
	}


	/**
	 * @deprecated
	 * @param string $id_function
	 */
	public static function rewritedUrl($id_function)
	{
		return self::rewrittenUrl($id_function);
	}
}



/**
 * Collect available sitemap
 * @package sitemap
 * @see bab_siteMap::getList()
 */
class bab_eventBeforeSiteMapList extends bab_event {

	private $available = array();

	/**
	 * @param	string				$uid		ASCII string, unique identifier
	 * @param	bab_siteMap			$siteMap	sitemap
	 *
	 *
	 * @return bab_eventBeforeSiteMapList
	 */
	public function addSiteMap($uid, bab_siteMap $siteMap, $viewRoot = 'DGAll') {
		$this->available[$uid] = $siteMap;
		$siteMap->setVisibleRootNode($viewRoot);

		return $this;
	}

	/**
	 *
	 * @return array
	 */
	public function getAvailable() {
		return $this->available;
	}

}
