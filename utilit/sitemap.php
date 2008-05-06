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
 */
class bab_siteMapOrphanRootNode extends bab_OrphanRootNode {
	
	/**
	 * Get a folder node by ID in the correct delegation branch
	 * @param	string	$sId
	 * @return bab_Node | null
	 */
	function getDgNodeById($sId) {
	
		include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
		//$idDg = bab_getCurrentUserDelegation();
		$idDg = 'All';
		return parent::getNodeById('DG'.$idDg.'-'.$sId);
	}
}

/**
 * Sitemap item contener
 */
class bab_siteMapItem {


	var $id_function;
	var $name;
	var $description;
	var $url;
	var $onclick;
	var $folder; 

}




/**
 * 
 */
class bab_siteMap {

	/**
	 * Delete sitemap for current user or id_user
	 * @param	int		$id_user
	 * @static
	 */
	function clear($id_user = false) {
	
		global $babDB;
		
		
		
		
		if (($GLOBALS['BAB_SESS_LOGGED'] && false === $id_user) || false !== $id_user) {
		
			if (false === $id_user) {
				$id_user = $GLOBALS['BAB_SESS_USERID'];
			}
		
			// delete profile link
			
			$res = $babDB->db_query('SELECT id_sitemap_profile FROM '.BAB_USERS_TBL.' 
				WHERE id='.$babDB->quote($id_user)
			);
			
			$arr = $babDB->db_fetch_assoc($res);

			if ($arr) {
				$res = $babDB->db_query('SELECT COUNT(*) FROM '.BAB_USERS_TBL.' 
				WHERE id_sitemap_profile='.$babDB->quote($arr['id_sitemap_profile']));
				
				list($n) = $babDB->db_fetch_array($res);
				if (1 === (int) $n) {
					$babDB->db_query('DELETE FROM '.BAB_SITEMAP_PROFILES_TBL." WHERE id=".$babDB->quote($arr['id_sitemap_profile']));
					
					$res2 = $babDB->db_query('SELECT id_function FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 
					WHERE id_profile='.$babDB->quote($arr['id_sitemap_profile']));
					
					while(list($id_function) = $babDB->db_fetch_array($res2)) {
						$res3 = $babDB->db_query('SELECT COUNT(*) FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 
						WHERE id_function='.$babDB->quote($id_function));
						
						list($n) = $babDB->db_fetch_array($res3);
						if (1 === (int) $n) {
							// delete function
							include_once $GLOBALS['babInstallPath'].'utilit/sitemap_build.php';
							bab_siteMap_deleteFunction($id_function);
						}
					}
					
					$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL." WHERE id_profile=".$babDB->quote($arr['id_sitemap_profile']));
				}
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
	 * @static
	 */
	function clearAll() {
		global $babDB;
		
		bab_debug('Clear sitemap...');
		
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
	 * @static
	 * @return bab_OrphanRootNode
	 */
	function get() {
	
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
				f.folder  
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
				AND p.id = \''.BAB_UNREGISTERED_SITEMAP_PROFILE.'\' 
				AND p.uid_functions>\'0\' 
				AND fl.id_function=f.id_function 
				AND fl.lang='.$babDB->quote($GLOBALS['babLanguage']).'
				';
		}
		
		
		$query .= 'ORDER BY s.lf';
		
		// bab_debug($query);
		
		$res = $babDB->db_query($query);
		
		if (0 === $babDB->db_num_rows($res)) {
			// no sitemap for user, build it
			
			bab_debug('Buid sitemap...');
			
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
		
			$data = & new bab_siteMapItem();
			$data->id_function 	= $arr['id_function'];
			$data->name 		= $arr['name'];
			$data->description 	= $arr['description'];
			$data->url 			= $arr['url'];
			$data->onclick 		= $arr['onclick'];
			$data->folder 		= 1 == $arr['folder'];
			
			$node_list[$arr['id']] = $data->folder ? $current_delegation_node.'-'.$arr['id_function'] : $arr['id'].'-'.$arr['id_function'];
			$id_parent = isset($node_list[$arr['id_parent']]) ? $node_list[$arr['id_parent']] : NULL;
		
			$node = & $rootNode->createNode($data, $node_list[$arr['id']]);
			$rootNode->appendChild($node, $id_parent);
		}

		
		return $rootNode;
	}
	
	
	/**
	 * Build sitemap for current user
	 * @return boolean
	 */
	function build() {
		include_once $GLOBALS['babInstallPath'].'utilit/sitemap_build.php';
		return bab_siteMap_build();
	}

}

?>