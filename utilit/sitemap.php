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
	
	private $rewriteIndex_id = array();
	private $rewriteIndex_rn = array();
	
	/**
	 * @return bool
	 */
	public function appendChild(bab_Node $newNode, $id = null) {
		$sitemapItem = $newNode->getData();
		$rewriteName = $sitemapItem->getRewriteName();
		
		$rewriteIndex_id[$newNode->getId()] = array($id, $rewriteName);
		
		if (isset($rewriteIndex_rn[$rewriteName])) {
			$rewriteIndex_rn[$rewriteName][] = $newNode->getId();
		} else {
			$rewriteIndex_rn[$rewriteName] = array($newNode->getId());
		}
		
		return parent::appendChild($newNode, $id);
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
		foreach($this->rewriteIndex_rn[$first] as $nodeId) {
			if (isset($this->rewriteIndex_id[$nodeId]) && $first === $this->rewriteIndex_id[$nodeId][1]) {
				return $this->getNextRewriteNode($arr, $nodeId);
			}
		}
		
		return null;
	}
	
	/**
	 * 
	 * @param array $path
	 * @param string $id_parent
	 * @return string
	 */
	private function getNextRewriteNode(Array $path, $id_parent)
	{
		
		$first = array_shift($path);
		foreach($this->rewriteIndex_rn[$first] as $nodeId) {
			if (isset($this->rewriteIndex_id[$nodeId]) && $id_parent === $this->rewriteIndex_id[$nodeId][0]) {
				
				if (0 === count($path))
				{
					return $nodeId;
				}
				
				return $this->getNextRewriteNode($path, $nodeId);
			}
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
		if (isset($this->rewriteName)) {
			return $this->rewriteName;
		}
		
		return $this->id_function;
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
	
	private $siteMapName 		= '';
	private $siteMapDescription = '';
	
	
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
	 * Delete sitemap for current user or id_user
	 * @param	int		$id_user
	 */
	public static function clear($id_user = false) {
	
		global $babDB;
		
		
		
		
		if (($GLOBALS['BAB_SESS_LOGGED'] && false === $id_user) || false !== $id_user) {
		
			if (false === $id_user) {
				$id_user = $GLOBALS['BAB_SESS_USERID'];
			}

			
			$babDB->db_query('UPDATE '.BAB_USERS_TBL.' 
			SET 
				id_sitemap_profile=\'0\' 
				WHERE id='.$babDB->quote($id_user)
			);
		} else {
			
			// delete profile 
			
			$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 
				WHERE id_profile=\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'"
			);
			
			$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' 
				WHERE id_profile=\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'"
			);
		}
	}
	
	/**
	 * Delete sitemap for all users
	 * 
	 * set same lock as sitemap building
	 * 
	 */
	public static function clearAll() {
		global $babDB;
		/*
		$babDB->db_query('
			LOCK TABLES 
				'.BAB_SITEMAP_PROFILES_TBL.' 				 	WRITE,
				'.BAB_SITEMAP_PROFILES_TBL.' 			AS p 	WRITE, 
				'.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' 		 	WRITE, 
				'.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' 	AS pv 	WRITE, 
				'.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 		 	WRITE,
				'.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 	AS fp 	WRITE,
				'.BAB_SITEMAP_FUNCTIONS_TBL.' 				 	WRITE, 
				'.BAB_SITEMAP_FUNCTIONS_TBL.' 			AS f 	WRITE, 
				'.BAB_SITEMAP_FUNCTION_LABELS_TBL.' 		 	WRITE,
				'.BAB_SITEMAP_FUNCTION_LABELS_TBL.' 	AS fl 	WRITE, 
				'.BAB_USERS_TBL.' 							 	WRITE, 
				'.BAB_SITEMAP_TBL.' 						 	WRITE,
				'.BAB_SITEMAP_TBL.' 					AS s 	WRITE,
				'.BAB_SITEMAP_TBL.' 					AS p1 	WRITE,
				'.BAB_SITEMAP_TBL.' 					AS p2 	WRITE  
		');
		*/
		// bab_debug('Clear sitemap...', DBG_TRACE, 'Sitemap');
		
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILES_TBL.' WHERE id<>\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'");
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_PROFILE_VERSIONS_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTION_PROFILE_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTIONS_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTION_LABELS_TBL);
		$babDB->db_query('UPDATE '.BAB_USERS_TBL." SET id_sitemap_profile='0'");
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_TBL);
		
		//bab_siteMap::build();
		
		
		//$babDB->db_query('UNLOCK TABLES');

	}
	
	
	
	
	/**
	 * 
	 * @param 	ressource 	$res
	 * 
	 * @return bab_siteMapOrphanRootNode
	 */
	private static function buildFromRessource($res)
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
			

			$node_list[$arr['id']] = $arr['id_function'];
			
			// the id_parent is NULL if there is no parent, the items are allready ordered so the NULL is for root item only
			$id_parent = isset($node_list[$arr['id_parent']]) ? $node_list[$arr['id_parent']] : NULL;
		
			$node = $rootNode->createNode($data, $node_list[$arr['id']]);
			
			if (null === $node) {
				// bab_debug((string) $rootNode);
				return $rootNode;
			}
			
			$rootNode->appendChild($node, $id_parent);
		}

		// each level will be sorted individually if needed before each usage
		// $rootNode->sortSubTree();

		// bab_debug((string) $rootNode);
		
		return $rootNode;
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
		
		include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
		
		static $cache = array();
		
		
		$cachekey = null === $path ? '0' : end($path);
		if (null !== $levels) {
			$cachekey .= ','.$levels;
		}
		
		if (isset($cache[$cachekey])) {
			return $cache[$cachekey];
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
		
		
		$rootNode = self::buildFromRessource($res);
		
		$cache[$cachekey] = $rootNode;
		
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
		if (!$sitemapItem->title) {
			throw new Exception('Missing title on node '.$sId);
		}

		return $sitemapItem->title;
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
	 * Get sitemap tree by unique UID from sitemap list
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
	 * Set position in sitemap for current page, if the position is not allready defined by the URL
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

			// for now current delegation is allways DGAll, suffix is just appended
			
			self::$current_page = $uid_prefix.$uid_suffix;
			
		}
	}
	
	
	

	
	
	
	/**
	 * get position in the sitemap from homepage (delegation node) to current position
	 * If position is not set, the method return an empty array
	 * 
	 * @see bab_sitemap::setPosition()
	 * 
	 * @param	string	$sitemap_uid	ID of sitemap tree, default is sitemap selected in site options
	 * 
	 * @return array					Array of bab_Node
	 */ 
	public static function getBreadCrumb($sitemap_uid = null) {
		
		if (!isset(self::$current_page)) {
			return array();
		}
		
		if (null === $sitemap_uid) {
			global $babBody;
			$sitemap_uid = $babBody->babsite['sitemap'];
			$sitemap = self::getByUid($sitemap_uid);
			if (!isset($sitemap)) {
				$sitemap = self::getByUid('core');
			}
		} else {
			$sitemap = self::getByUid($sitemap_uid);
			if (!isset($sitemap)) {
				return array();
			}
		}

		
		$page_node = $sitemap->getNodeById(self::$current_page);
		
		if (!isset($page_node)) {
			bab_debug(sprintf('The node %s does not exists in sitemap %s', self::$current_page, $sitemap_uid), DBG_ERROR);
			return array();
		}
		
		
		$breadcrumb = array($page_node);
		
		while (($page_node instanceOf bab_Node) && $page_node = $page_node->parentNode()) {
			
			if ('root' === $page_node->getId()) {
				break;
			}
			
			array_unshift($breadcrumb, $page_node);
		}
		
		return $breadcrumb;
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
	public function addSiteMap($uid, bab_siteMap $siteMap) {
		$this->available[$uid] = $siteMap;
		
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
