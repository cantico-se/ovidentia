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
	
	
}

/**
 * Sitemap item contener
 * the sitemap is a tre of items, each items is a bab_siteMapItem object
 * @package sitemap
 */
class bab_siteMapItem {

	/**
	 * Unique string in sitemap that identify the item
	 * Mandatory
	 */
	public $id_function;

	/**
	 * Internationalized name of the item
	 * Mandatory
	 */
	public $name;

	/**
	 * Internationalized description of the item
	 * Optional
	 */
	public $description;

	/**
	 * Url 
	 * Optional if folder si true or mandatory if folder is false
	 */
	public $url;

	/**
	 * Javascript string for the onclick attribute in html
	 * Mandatory
	 */
	public $onclick;

	/**
	 * Boolean
	 * If true, the item may contain sub-items
	 */
	public $folder; 


	/**
	 * Icon classnames
	 */
	public $iconClassnames;


	/**
	 * Compare sitemap items
	 * @see bab_Node::sortSubTree()
	 * @see bab_Node::sortChildNodes()
	 */
	public function compare($node) {
		return bab_compare($this->name, $node->name);
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
	 */
	public static function clearAll() {
		global $babDB;
		
		// bab_debug('Clear sitemap...', DBG_TRACE, 'Sitemap');
		
		$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILES_TBL.' WHERE id<>\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'");
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_PROFILE_VERSIONS_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTION_PROFILE_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTIONS_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTION_LABELS_TBL);
		$babDB->db_query('UPDATE '.BAB_USERS_TBL." SET id_sitemap_profile='0'");
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_TBL);
		
		//bab_siteMap::build();

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
			// the profile verion is missing, add version to profile
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
	 * Set position in sitemap for current page
	 * 
	 * 
	 * @param	string	$uid_prefix	sitemap node UID prefix before delegation identification
	 * @param	string	$uid_suffix sitemap node UID suffix after delegation identification
	 * 
	 */ 
	public static function setPosition($uid_prefix, $uid_suffix = null) {
		
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
	 * @param	string	$sitemap_uid	ID of sitemap tree, default is core sitemap
	 * 
	 * @return array					Array of bab_Node
	 */ 
	public static function getBreadCrumb($sitemap_uid = 'core') {
		
		if (!isset(self::$current_page)) {
			return array();
		}
		
		$sitemap = self::getByUid($sitemap_uid);
		
		if (!isset($sitemap)) {
			return array();
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
