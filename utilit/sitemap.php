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

/**
 * Sitemap rootNode
 * @package sitemap
 */
class bab_siteMapOrphanRootNode extends bab_OrphanRootNode {
	
	/**
	 * Get a folder node by ID in the correct delegation branch
	 * @param	string	$sId
	 * @return bab_Node | null
	 */
	function getDgNodeById($sId) {
	
		include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';

		$idDg = 'All';
		
		if (preg_match('/^babDG(\d+)/', $sId, $m)) {
			$idDg = $m[1];
		}

		
		return parent::getNodeById('DG'.$idDg.'-'.$sId);
	}
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
			
			$babDB->db_query('UPDATE '.BAB_SITEMAP_PROFILES_TBL.' 
			SET 
				uid_functions=\'0\' 
				WHERE id=\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'"
			);
			
			$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 
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
		$babDB->db_query('UPDATE '.BAB_SITEMAP_PROFILES_TBL." SET uid_functions='0'");
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTION_PROFILE_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTIONS_TBL);
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_FUNCTION_LABELS_TBL);
		$babDB->db_query('UPDATE '.BAB_USERS_TBL." SET id_sitemap_profile='0'");
		$babDB->db_query('TRUNCATE '.BAB_SITEMAP_TBL);
		
		//bab_siteMap::build();

	}
	
	
	/**
	 * Get sitemap for current user
	 * @return bab_OrphanRootNode
	 */
	public static function get() {
	
		include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
	
		static $rootNode = NULL;
		
		if (NULL !== $rootNode) {
			return $rootNode;
		}
	
		global $babDB;
		
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
				f.icon 
			FROM 
				'.BAB_SITEMAP_FUNCTIONS_TBL.' f, 
				'.BAB_SITEMAP_FUNCTION_LABELS_TBL.' fl,
				'.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' fp,
				'.BAB_SITEMAP_TBL.' s
					LEFT JOIN '.BAB_SITEMAP_TBL.' sp ON sp.id = s.id_parent,
				'.BAB_SITEMAP_PROFILES_TBL.' p
			'; 
			
	
		if ($GLOBALS['BAB_SESS_USERID']) {
		
			$query .= ', '.BAB_USERS_TBL.' u 
			
			WHERE 
				s.id_function = f.id_function 
				AND fp.id_function = f.id_function 
				AND fp.id_profile = p.id 
				AND p.uid_functions>\'0\'
				AND p.id = u.id_sitemap_profile 
				AND u.id = '.$babDB->quote($GLOBALS['BAB_SESS_USERID']).' 
				AND fl.id_function=f.id_function 
				AND fl.lang='.$babDB->quote($GLOBALS['babLanguage']).'
				';
			
		} else {
			$query .= 'WHERE 
				s.id_function = f.id_function 
				AND fp.id_function = f.id_function 
				AND fp.id_profile = p.id 
				AND p.uid_functions>\'0\' 
				AND p.id = \''.BAB_UNREGISTERED_SITEMAP_PROFILE.'\' 
				AND fl.id_function=f.id_function 
				AND fl.lang='.$babDB->quote($GLOBALS['babLanguage']).'
				';
		}
		
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

			bab_siteMap::build();
			$res = $babDB->db_query($query);
		}
		
		
		
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
			
			// in tree, uniques id are dynamicaly generated from the id in table and the id_function
			// the id_function is duplicated if the tree contain multiples delegations
			$node_list[$arr['id']] = $data->folder ? $current_delegation_node.'-'.$arr['id_function'] : $arr['id'].'-'.$arr['id_function'];
			
			// the id_parent is NULL if there is no parent, the items are allready ordered so the NULL is for root item only
			$id_parent = isset($node_list[$arr['id_parent']]) ? $node_list[$arr['id_parent']] : NULL;
		
			$node = & $rootNode->createNode($data, $node_list[$arr['id']]);
			$rootNode->appendChild($node, $id_parent);
		}

		// each level will be sorted individually if needed before each usage
		// $rootNode->sortSubTree();

		//die((string) $rootNode);
		
		return $rootNode;
	}

	/**
	 * Get the url of a sitemap node or null if the node does not exists or if there is no url
	 * @param	string	$sId
	 */
	public static function getUrlById($sId) {

		$notesNode = self::get()->getDgNodeById($sId);
	
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

		$notesNode = self::get()->getDgNodeById($sId);
	
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
	public static function build() {
		include_once $GLOBALS['babInstallPath'].'utilit/sitemap_build.php';
		return bab_siteMap_build();
	}

}

?>