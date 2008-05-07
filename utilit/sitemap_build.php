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
include_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/treeincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';

/** 
 * Sitemap node as object
 * 
 */
class bab_siteMap_item {

	var $uid;
	var $label;
	var $description = '';
	var $href;
	var $onclick;
	var $position = array();
	var $lang;
	var $parentNode;	// ref bab_siteMap_item
	var $parentNode_str;
	var $childNodes = array();
	
	
	/**
	 * sitemap item is a folder
	 * @access public
	 */
	var $folder = false;
	
	/**
	 * sitemap item is a delegation folder
	 * @access public
	 */
	var $delegation = false;
	
	/**
	 * by default, an item is viewable in all delegation, this status is set to true to copy the node in the others branchs
	 * If the node must be present in only one delegation, the variable can be overwriten to false
	 * @access public
	 */
	var $copy_to_all_delegations = true;
	
	/**
	 * constructor
	 * The uid parameter must be unique within the sitemap tree
	 * @param	string	$uid	[A-z0-9]
	 */
	function bab_siteMap_item($uid) {
		$this->uid = $uid;
		$this->lang = $GLOBALS['babLanguage'];
	}
	
	/**
	 * Set node label
	 * stored as VARCHAR(255)
	 * @param	string	$label
	 */
	function setLabel($label) {
		$this->label = $label;
	}
	
	/**
	 * Set node descripiton
	 * stored as a TEXT
	 * @param	string	$description
	 */
	function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Set link attributes
	 * record a relative url
	 *
	 * @param	string	$href
	 * @param	string	$onclick
	 */
	function setLink($href, $onclick = '') {
	
		if (0 === strpos($href, $GLOBALS['babUrl'].$GLOBALS['babPhpSelf'])) {
			$href = substr($href, strlen($GLOBALS['babUrl'].$GLOBALS['babPhpSelf']));
		}
	
		$this->href = $href;
		$this->onclick = $onclick;
	}
	
	/**
	 * Set position in tree with the full list of parents
	 * $position is an array of sitemap node uid
	 * @param	array	$position
	 */
	function setPosition($position) {
		$this->parentNode_str = end($position);
		$this->position = $position;
	}
	
	/**
	 * set position with sibblings
	 * @param	string	$before		: node uid
	 */ 
	function insertBefore($before) {
		$this->insertBefore = $before;
	}
	
	/**
	 * set position with sibblings
	 * @param	string	$after		: node uid
	 */ 
	function insertAfter($after) {
		$this->insertAfter = $after;
	}
	
	/**
	 * @param	string	$lang
	 */
	function setLanguage($lang) {
		$this->lang = $lang;
	}

	/**
	 * @param	bab_siteMap_item	$obj
	 */
	function addChildNode(&$obj) {
		$this->childNodes[$obj->uid] = $obj;
	}
	
	
	
	
	
	/**
	 * @return false|array
	 */
	function getParentsFromDelegation($parents = NULL) {
	
		if (NULL === $parents) {
			$parents = array();
		}
		
		$node = reset($parents);
		
		if (empty($node)) {
			$node = $this->parentNode;
		} else {
			$node = $node->parentNode;
		}
		
		if (empty($node)) {
			return false;
		}

		array_unshift($parents, $node);
		
		if ($node->delegation) {
			return $parents;
		}
		
		return $this->getParentsFromDelegation($parents);
	}
	
}



/**
 * Event used to collect items before creation of the sitemap
 */
class bab_eventBeforeSiteMapCreated extends bab_event {

	var $nodes = array();
	var $queue = array();
	var $propagation_status = true;

	/**
	 * Get item object
	 * @public
	 * @param	string	$uid	(64 characters)
	 * @return 	bab_siteMap_item
	 */
	function createItem($uid) {
		
		return new bab_siteMap_item($uid);
	}
	
	/**
	 * Add item as function into sitemap
	 * 
	 * @public
	 * @param	bab_siteMap_item
	 * @return	boolean
	 */
	function addFunction(&$obj) {
		
		$this->nodes[$obj->uid] = $obj;
		$this->buidtree($obj);
		
		return true;
	}
	
	
	/**
	 * Add folder into sitemap
	 * Folder must be unique
	 * @public
	 * @param	bab_siteMap_item	&$obj
	 * @return	boolean
	 */
	function addFolder(&$obj) {
		if (isset($this->nodes[$obj->uid])) {
			trigger_error(sprintf('The node %s is allready in the sitemap',$obj->uid));
			$this->propagation_status = false;
			return false;
		}
		$obj->folder = true;
		$this->nodes[$obj->uid] = $obj;
		$this->buidtree($obj);
		
		return true;
	}
	
	
	/**
	 * @private
	 * @param	bab_siteMap_item	&$obj
	 */
	function buidtree(&$obj) {
		if (isset($this->nodes[$obj->parentNode_str])) {
			
			// reference vers le parent
			$obj->parentNode = & $this->nodes[$obj->parentNode_str];
			
			// inserer la meme reference dans la node list , important pour php 4
			$this->nodes[$obj->uid]->parentNode = & $this->nodes[$obj->parentNode_str];
			
			$this->insertChildNodeWithDelegationSupport($obj->parentNode, $this->nodes[$obj->uid]);
		} else {
			$this->queue[$obj->parentNode_str] = $obj->uid;
		}
		
		if (isset($this->queue[$obj->uid])) {
			$this->buidtree($this->nodes[$this->queue[$obj->uid]]);
			unset($this->queue[$obj->uid]);
		}
	}
	
	/**
	 * Insert nodes in multiples delegations branchs
	 * @param	bab_siteMap_item	&$parent_node
	 * @param	bab_siteMap_item	&$obj
	 * @private
	 */
	function insertChildNodeWithDelegationSupport(&$parent_node, &$obj) {

		if ($obj->copy_to_all_delegations) {
			$parents = $obj->getParentsFromDelegation();
			if (false === $parents) {
				trigger_error(sprintf('The node %s cannot be duplicated in all delegation',$obj->uid));
				return false;
			}
			
			
			$delegationParent = $parents[0]->parentNode;
			foreach($delegationParent->childNodes as $uid => $node) {
				if ($uid !== $parents[0]->uid) {
					foreach($parents as $key => $nodeToInsert) {
						if (0 < $key) {
							if (isset($_parent->childNodes[$key])) {
								$_parent = & $this->nodes[$key];
							} else {
								$_parent->addChildNode($parents[$key]);
								$_parent = & $parents[$key];
							}
						} else {
							$_parent = & $node;
						}
					}
				}
			}
		}

		$parent_node->addChildNode($obj);
	}
	
	
	
	
	
	
	/**
	 * Display as text
	 * @param	string	$uid
	 * @param	int		[$deep]
	 */
	function displayAsText($uid, $deep = 0) {
	
		$node = & $this->nodes[$uid];
	
		$str = sprintf("%-50s %-30s %s\n", str_repeat('|   ',$deep).$node->label, $node->uid, $node->href);
		
		if ($node->childNodes) {
			$deep++;
			foreach($node->childNodes as $uid => $obj) {
				$str .= $this->displayAsText($uid, $deep);
			}
		}
		
		return $str;
	}
	
	
}




/**
 * Tree db editor
 */
class bab_sitemap_tree extends bab_dbtree
{
	var $iduser = '';
	var $userinfo = '';
	var $table;

	function bab_sitemap_tree() {

		$this->bab_dbtree(BAB_SITEMAP_TBL, null);
		$this->where = '';
	}
	
	function setFunction($id_node, $str) {
		global $babDB;
		$babDB->db_query('UPDATE '.BAB_SITEMAP_TBL.' 
			SET id_function='.$babDB->quote($str).' 
			WHERE id='.$babDB->quote($id_node) );
	}
	

	
	
	
	/**
	 * Get childnodes from this node with collumn to insert
	 *
	 * @param	bab_siteMap_item	$node
	 * @param	int					&$id
	 * @param	int					$id_parent
	 * @param	array				&$insertlist		(id, id_parent, lf, lr, id_function)
	 *
	 */
	function getLevelToInsert($node, &$id, $id_parent, &$insertlist) {
	
		
		$lf = 1 + $insertlist[count($insertlist) - 1]['lf'];

		foreach($node->childNodes as $childNode) {
		
			$id++;
			$current_id = $id;
			$key = count($insertlist);
			
			$insertlist[$key] = array(
				'id' => $id,
				'id_parent' => $id_parent,
				'lf' => $lf,
				'lr' => 1 + $lf,
				'id_function' => $childNode->uid
			);
			
			if (0 < count($childNode->childNodes)) {
				$this->getLevelToInsert($childNode, $id, $id, $insertlist);
				
				$nb_inserted = $id - $current_id;
				$insertlist[$key]['lr'] = $lf + 1 + (2*$nb_inserted);
			}
			
			$lf = 1 + $insertlist[$key]['lr'];
		}
		
	}
	
	
	
	
	/**
	 * Insert a new child node into a list in memory
	 * Get ordered child nodes with first child ID
	 * @param	int		$id_parent
	 * @param	string	$childname
	 * @return array
	 */
	function setAlphaChild($id_parent, $childname)
	{
		
		global $babDB;
		
		$res = $babDB->db_query('
			SELECT 
				s.id, 
				l.name  
			FROM 
				'.BAB_SITEMAP_TBL.' s, 
				'.BAB_SITEMAP_FUNCTION_LABELS_TBL.' l 
			WHERE 
				l.id_function=s.id_function
				AND s.id_parent = '.$babDB->quote($id_parent).'
		');
		
		
		if (0 === (int) $babDB->db_num_rows($res)) {
			return array(
				array(
					'new' => $childname
				)
				,0);
		}
		
		$child_nodes = array();
		while ($arr = $babDB->db_fetch_assoc($res)) {
		
			$child_nodes[$arr['id']] = $arr['name'];
		}
		
		$child_nodes['new'] = $childname;
		natcasesort($child_nodes);
		$first = reset($child_nodes);
		$firstchild = $first['id'];
		return array($child_nodes,$firstchild);
	}


	/**
	 * Add a node ordered into childnodes list
	 * @param	int		$id_parent
	 * @param	string	$childname
	 */
	function addAlpha($id_parent, $childname)
	{
		list($child_nodes, $firstchild) = $this->setAlphaChild($id_parent, $childname);
		foreach($child_nodes as $key => $value) {
		
			if ('new' == $key && isset($id_previous))
				{
				return $this->add($id_parent,$id_previous, true);
			}
			elseif ('new' == $key)
				{
				return $this->add($id_parent, $firstchild, false);
			}
			$id_previous = $key;
		}
	}
}



/**
 * Add function to profile
 */
class bab_sitemap_addFuncToProfile {

	var $arr = array();

	function add($id_function, $id_profile) {
		
		$this->arr[] = array(
			'id_function' => $id_function,
			'id_profile' => $id_profile
		);
	}
	
	function commit() {
	
		global $babDB;
		
		$start = 0;
		$length = 50;
		
		while ($arr = array_slice($this->arr, $start, $length)) {
		
			$values = array();
			foreach($arr as $row) {
				$values[] = '('.$babDB->quote($row['id_function']).','.$babDB->quote($row['id_profile']).')';
			}
			
			$babDB->db_query('
				INSERT INTO '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 
					(id_function, id_profile) 
				VALUES 
						'.implode(",\n ",$values).' 
			');
			
			$start += $length;
		}
	}
}










function bab_sitemap_removeFuncFromProfile(&$tree, $id_function, $id_profile) {
	global $babDB;
	
	$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' 
		WHERE 
			id_profile='.$babDB->quote($id_profile).' 
			AND id_function='.$babDB->quote($id_function)
		);
		
	$res = $babDB->db_query('SELECT COUNT(*) FROM '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' WHERE id_function='.$babDB->quote($id_function));
	$arr = $babDB->db_fetch_array($res);
	if (0 == $arr[0]) {
		// la fonction n'existe plus
		bab_siteMap_deleteFunction($id_function, $tree);
	}
	
	
}




function bab_siteMap_deleteFunction($id_function, $tree = false) {

	global $babDB;

	$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTIONS_TBL.' WHERE id_function='.$babDB->quote($id_function));
	$babDB->db_query('DELETE FROM '.BAB_SITEMAP_FUNCTION_LABELS_TBL.' WHERE id_function='.$babDB->quote($id_function));
	
	if (false == $tree) {
		$tree = new bab_sitemap_tree();
	}
	
	$res = $babDB->db_query('SELECT id FROM '.BAB_SITEMAP_TBL.' WHERE id_function='.$babDB->quote($id_function));
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$tree->remove($arr['id']);
	}
	
	// bab_debug('sitemap remove : '.$id_function);
}










/**
 * insert functions into tables
 */
class bab_siteMap_insertFunctionObj {

	var $functions = array();
	var $labels = array();

	/**
	 * Insert function into database
	 * @param	bab_siteMap_item	$node
	 */
	function insertFunction($node) {
	
		$this->functions[] = $node;
	
		$this->insertFunctionLabel($node);
	}
	
	/**
	 * Insert function label for current language into database
	 * @param	bab_siteMap_item	$node
	 */
	function insertFunctionLabel($node) {
		$this->labels[] = $node;
	}
	
	
	function commit() {
		global $babDB;
		
		$start = 0;
		$length = 50;
		
		while ($arr = array_slice($this->functions, $start, $length)) {
		
			$values = array();
			foreach($arr as $node) {

				$onclick 	= isset($node->onclick)	? 	$node->onclick 	: '';
				$href		= isset($node->href)	?	$node->href		: '';
			
				$folder = $node->folder ? '1' : '0';
				$values[] = '('.$babDB->quote($node->uid).','.$babDB->quote($href).','.$babDB->quote($onclick).','.$babDB->quote($folder).')';
			}
			
			$babDB->db_query('
				INSERT INTO '.BAB_SITEMAP_FUNCTIONS_TBL.' 
					(
						id_function,
						url,
						onclick,
						folder
					)
				VALUES 
					'.implode(",\n ",$values).'
			');
			
			$start += $length;
		}
		
		
		
		$start = 0;
		
		while ($arr = array_slice($this->labels, $start, $length)) {
			
			$values = array();
			foreach($arr as $node) {
				$values[] = '('.$babDB->quote($node->uid).','.$babDB->quote($node->lang).','.$babDB->quote($node->label).','.$babDB->quote($node->description).')';
			}
	
			$babDB->db_query('
				INSERT INTO '.BAB_SITEMAP_FUNCTION_LABELS_TBL.' 
					(
						id_function,
						lang,
						name,
						description
					)
					
				VALUES 
					'.implode(",\n ",$values).'
			');
			
			$start += $length;
		}
	}
}










/**
 * Insert node and childs into database
 * @param	bab_siteMap_item	$rootNode
 * @param	array				$nodeList
 */
function bab_siteMap_insertTree($rootNode, $nodeList) {


	
	global $babDB;

	$crc = abs(crc32(serialize($rootNode)));

	// search for available profile
	// create new profile
	
	if ($GLOBALS['BAB_SESS_USERID']) {
		$res = $babDB->db_query('SELECT id FROM '.BAB_SITEMAP_PROFILES_TBL.' WHERE uid_functions = '.$babDB->quote($crc));
		if ($arr = $babDB->db_fetch_assoc($res)) {
			$id_profile = $arr['id'];
			
			bab_debug('found profile '.$id_profile);


		} else {
		
			// create new profile
			$res = $babDB->db_query('INSERT INTO '.BAB_SITEMAP_PROFILES_TBL.' (uid_functions) VALUES ('.$babDB->quote($crc).')');
			$id_profile = $babDB->db_insert_id($res);
			
			bab_debug('new profile created '.$id_profile);
			
		}
		
		$babDB->db_query('UPDATE '.BAB_USERS_TBL.'  
			SET id_sitemap_profile='.$babDB->quote($id_profile).' 
			WHERE id='.$babDB->quote($GLOBALS['BAB_SESS_USERID']));
		
		
		
	} else {
		$babDB->db_query('UPDATE '.BAB_SITEMAP_PROFILES_TBL.' SET uid_functions='.$babDB->quote($crc).' WHERE id=\''.BAB_UNREGISTERED_SITEMAP_PROFILE."'");
		$id_profile = BAB_UNREGISTERED_SITEMAP_PROFILE;
	}
	
	// get exisiting functions list
	$functions = array();
	$missing_labels = array();
	$missing_profile = array();
	
	$res = $babDB->db_query('SELECT 
		f.id_function, 
		IFNULL(s.id,\'noref\') id, 
		fl.lang,
		p.id_profile  
	FROM 
		'.BAB_SITEMAP_FUNCTIONS_TBL.' f 
		LEFT JOIN '.BAB_SITEMAP_TBL.' s ON s.id_function = f.id_function 
		LEFT JOIN '.BAB_SITEMAP_FUNCTION_LABELS_TBL.' fl 
			ON f.id_function = fl.id_function AND fl.lang='.$babDB->quote($GLOBALS['babLanguage']).' 
		LEFT JOIN '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' p ON p.id_function = f.id_function AND p.id_profile='.$babDB->quote($id_profile).'
	');
	while ($arr = $babDB->db_fetch_assoc($res)) {
	
		$functions[$arr['id_function']] = $arr['id'];
		
		if (is_null($arr['lang']) && !is_null($arr['id'])) {
			$missing_labels[$arr['id_function']] = $arr['id'];
		}
		
		if (is_null($arr['id_profile'])) {
			$missing_profile[$arr['id_function']] = $arr['id'];
		}
	}

	$previous_node = 'root';
	$previous_id = 1;
	
	$nodes_start_time = bab_getMicrotime();

	
	$debug_str = '';
	
	$insertFunc = new bab_siteMap_insertFunctionObj();
	
	foreach($nodeList as $node) {

		$debug_str .= implode('/',$node->position).'/'.$node->uid."\n";

		if (isset($functions[$node->uid]) && 'noref' === $functions[$node->uid]) {
			// NULL : la fonction existe mais n'est pas inseree dans l'arbre
			$functions[$node->uid] = true;
			
		} elseif (isset($functions[$node->uid])) {
			// isset : la fonction existe et est dans l'arbre
			$previous_node = $node->uid;
			$previous_id = $functions[$node->uid];
			$functions[$node->uid] = false;
			
		} else {
			// !isset : la fonction n'existe pas
			// bab_debug('sitemap add : '.$node->uid.' ('.$node->label.')');
			$insertFunc->insertFunction($node);
			$functions[$node->uid] = true;
		}
		
		if (isset($missing_labels[$node->uid])) {
			$insertFunc->insertFunctionLabel($node);
		}
	}
	$insertFunc->commit();
	//bab_debug($debug_str);
	
	$nodes_stop_time = bab_getMicrotime();
	
	


	$tree = new bab_sitemap_tree();
	
	if (false !== $tree->getNodeInfo(1)) {
		// tree is not empty
		
		$addFuncToProfile = new bab_sitemap_addFuncToProfile();
		

		foreach($functions as $id_function => $val) {
		
	
			switch($val) {
				case true:
					// la fonction n'est pas li�e � l'arbre
					if (isset($nodeList[$id_function])) {
					
						if ('root' != $id_function) {
							bab_sitemap_insertNode(
								$tree, 
								$nodeList[$id_function],
								0,
								0
							);
						}
						
						$addFuncToProfile->add($id_function, $id_profile);
					}
					break;
					
				case false:
					// la fonction est li�e � l'arbre
					if (isset($missing_profile[$id_function]) && isset($nodeList[$id_function])) {
						// mais n'est pas dans le profile
						$addFuncToProfile->add($id_function, $id_profile);
					}
					break;
				
				default:
					// la fonction n'est plus dans le profile
					bab_sitemap_removeFuncFromProfile($tree, $id_function, $id_profile);
					break;
			}
		}
		
		$addFuncToProfile->commit();
		
	} else {
		// the tree is empty, build from scratch
		
		$id = 1;
		
		$insertlist = array();

		// root node
		$insertlist[0] = array(
			'id' => $id,
			'id_parent' => 0,
			'lf' => 1,
			'lr' => 0,
			'id_function' => $rootNode->uid
		);
		
		$tree->getLevelToInsert($rootNode, $id, 1, $insertlist);
		
		$insertlist[0]['lr'] = 2 + (2*(count($insertlist) - 1));
		
		// insert
		
		$start = 0;
		$length = 50;
		
		while ($arr = array_slice($insertlist, $start, $length)) {
		
			
			
			$req = 'INSERT INTO '.BAB_SITEMAP_TBL.' (id, id_parent, lf, lr, id_function) VALUES '."\n";
			foreach($arr as $key => $row) {
			
				if (0 < $key) {
					$req.= ",\n";
				}

				$req.= '('.$babDB->quote($row['id'])
				.','.$babDB->quote($row['id_parent'])
				.','.$babDB->quote($row['lf'])
				.','.$babDB->quote($row['lr'])
				.','.$babDB->quote($row['id_function'])
				.")";
			}
			
			//bab_debug($req);
			$babDB->db_query($req);
			
			$start += $length;
		}
		
		
		$addFuncToProfile = new bab_sitemap_addFuncToProfile();
		
		foreach($functions as $id_function => $val) {

			// la fonction n'est pas li�e � l'arbre
			if (isset($nodeList[$id_function])) {
				$addFuncToProfile->add($id_function, $id_profile);
			}
		}
		
		$addFuncToProfile->commit();
		
	}
	
	
	$profile_stop_time = bab_getMicrotime();
	
	
	bab_debug(
		sprintf('
	insert function, function label : %s s
	insert node, profile : %s s', 
		($nodes_stop_time - $nodes_start_time), 
		($profile_stop_time - $nodes_stop_time)
		)
	);
}


/**
 * insert a node into tree
 * @param	bab_sitemap_tree	&$tree
 * @param	bab_siteMap_item	$node
 * @param	int					$id_parent
 * @param	int					$deep		profondeur dans l'arbre
 */
function bab_sitemap_insertNode(&$tree, $node, $id_parent, $deep) {

	global $babDB;

	$parent = $tree->getNodeInfo($id_parent);
	
	if (!isset($node->position[$deep])) {
	
		// create node, test if exists
		
		$child = $tree->getFirstChild($id_parent);
		if ($child) {
		
			if ($node->uid == $child['id_function']) {
				return false;
			}
		
			while ($child = $tree->getNextSibling($child['id'])) {
				if ($node->uid == $child['id_function']) {
					return false;
				}
			}
		}
		
	
	
		// leaf creation
		
		$id_node = $tree->addAlpha($id_parent, $node->label);
		if ($id_node) {
			$tree->setFunction($id_node, $node->uid);
		}
		
		return $id_node;
	}
	
	$current = $node->position[$deep];
	
	$res = $babDB->db_query('SELECT id FROM '.BAB_SITEMAP_TBL.' WHERE id_parent='.$babDB->quote($id_parent).' AND id_function='.$babDB->quote($current));
	if ($arr = $babDB->db_fetch_assoc($res)) {
		// node exists, try to insert next node
		$deep++;
		bab_sitemap_insertNode($tree, $node, $arr['id'], $deep);
		
	} else {
		// node does not exists, create it
		$deep++;
		$id_node = $tree->add($id_parent);
		if ($id_node) {
			$tree->setFunction($id_node, $current);
		}
		bab_sitemap_insertNode($tree, $node, $id_node, $deep);
	}
	
	return false;
}



/**
 * Recursive childs count
 * @param	bab_siteMap_item	$node
 * @param	int					[$n]
 */
function bab_sitemap_countChilds($node, $n = 0) {
	foreach($node->childNodes as $child) {
		$n++;
		$n += bab_sitemap_countChilds($child,$n);
	}
	return $n;
}







function bab_getMicrotime() {
	list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);

}




/**
 * @see bab_siteMap::build()
 * @return boolean
 */
function bab_siteMap_build() {


    $start_time = bab_getMicrotime();


	global $babBody;
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	include_once $GLOBALS['babInstallPath'].'utilit/utilitsections.php';
	include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
	
	
	$event = new bab_eventBeforeSiteMapCreated;
	
	// insert rootnode
	
	$rootNode = new bab_siteMap_item('root');
	$rootNode->setLabel($GLOBALS['babSiteName']);
	$rootNode->setDescription($babBody->babsite['babslogan']);
	$rootNode->setLink('?');
	$rootNode->folder = 1;
	
	$event->nodes[$rootNode->uid] = $rootNode;
	
	// create delegations nodes
	
	$delgations = bab_getUserVisiblesDelegations();
	foreach($delgations as $dgid => $arr) {
		
		$dgNode = new bab_siteMap_item($dgid);
		$dgNode->setLabel($arr['name']);
		$dgNode->setDescription($arr['description']);
		$dgNode->setPosition(array('root'));
		$dgNode->setLink($arr['homePageUrl']);
		$dgNode->folder = 1;
		$dgNode->delegation = 1;
		$dgNode->copy_to_all_delegations = false;
		
		$event->nodes[$dgid] = $dgNode;
		$event->buidtree($dgNode);
	}
	
	
	
	bab_fireEvent($event);
	
	
	// add orphans nodes to tree
	foreach($event->queue as $missing_node => $orphan) {
		$newNode = new bab_siteMap_item($missing_node);
		$newNode->setPosition(array('root', 'DGAll'));
		$newNode->folder = 1;
		$newNode->setLabel($missing_node);
		$newNode->setLink('?tg=sitemap&node='.urlencode($missing_node));
		$event->nodes[$newNode->uid] = $newNode;
		$event->buidtree($newNode);
	}
	
	
	bab_debug($event->displayAsText('root'));
	
	
	
	
	 $insert_time = bab_getMicrotime();

	// insert tree into database
	bab_siteMap_insertTree($rootNode, $event->nodes);


    $stop_time = bab_getMicrotime();
    
    bab_debug(sprintf("
    
    tree : %s s
    insert : %s s 
    bab_siteMap_build : %s s", 
    
    ($insert_time - $start_time),
    ($stop_time - $insert_time),
    ($stop_time - $start_time) ));

	return $event->propagation_status;
}


/**
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_userSection(&$event) {

	global $babBody, $babDB;

	$item = $event->createItem('babUser');
	$item->setLabel(bab_translate("User's section"));
	$item->setPosition(array('root', 'DGAll'));
	$event->addFolder($item);
	
	$item = $event->createItem('babUserSection');
	$item->setLabel(bab_translate("Ovidentia functions"));
	$item->setPosition(array('root', 'DGAll', 'babUser'));
	$event->addFolder($item);
	
	$item = $event->createItem('babUserSectionAddons');
	$item->setLabel(bab_translate("Addons links"));
	$item->setPosition(array('root', 'DGAll', 'babUser'));
	$event->addFolder($item);

	// user links
	
	$array_urls= array();
	$faq = false;
	$req = "select id from ".BAB_FAQCAT_TBL."";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
			{
			$faq = true;
			break;
			}
		}
	
	$vac = false;
	$bemail = false;
	$idcal = 0;
	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$vacacc = bab_vacationsAccess();
		if( count($vacacc) > 0)
			{
			$vac = true;
			}

		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			$bemail = true;
		}


	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		if( count($babBody->topsub) > 0  || count($babBody->topmod) > 0 )
			{
			$array_urls[bab_translate("Publication")] = array(
				'url' => $GLOBALS['babUrlScript']."?tg=artedit",
				'uid' => 'babUserPublication'
				);
			}

		$babBody->waitapprobations = bab_isWaitingApprobations();
		if( $babBody->waitapprobations )
			{
			$array_urls[bab_translate("Approbations")] = array(
				'url' => $GLOBALS['babUrlScript']."?tg=approb",
				'uid' => 'babUserApprob',
				'desc' => bab_translate("Validate waiting items")
				);
			}
		}

	if( count($babBody->topman) > 0 || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id'])|| bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1))
		{
		$array_urls[bab_translate("Articles management")] = array(
				'url' => $GLOBALS['babUrlScript']."?tg=topman",
				'uid' => 'babUserArticlesMan',
				'desc' => bab_translate("List article topics where i am manager")
				);
		}

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$array_urls[bab_translate("Summary")] = array(
			'url' => $GLOBALS['babUrlScript']."?tg=calview",
			'uid' => 'babUserSummary',
			'desc' => bab_translate("Last published items")
		);
		
		$array_urls[bab_translate("Options")] = array(
			'url' => $GLOBALS['babUrlScript']."?tg=options",
			'uid' => 'babUserOptions'
		);
		
		if( bab_notesAccess())
			$array_urls[bab_translate("Notes")] = array(
				'url' => $GLOBALS['babUrlScript']."?tg=notes",
				'uid' => 'babUserNotes'
			);
		}

	if( $faq )
		{
		$array_urls[bab_translate("Faq")] = array(
			'url' => $GLOBALS['babUrlScript']."?tg=faq",
			'uid' => 'babUserFaq',
			'desc' => bab_translate("Frequently Asked Questions")
			);
		}
	if( $vac )
		{
		$array_urls[bab_translate("Vacation")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=vacuser",
			'uid' => 'babUserVac'
			);
		}

	if( $babBody->icalendars->calendarAccess())
		{
		$babBody->calaccess = true;
		switch($babBody->icalendars->defaultview)
			{
			case BAB_CAL_VIEW_DAY: $view='calday';	break;
			case BAB_CAL_VIEW_WEEK: $view='calweek'; break;
			default: $view='calmonth'; break;
			}
		if( empty($babBody->icalendars->user_calendarids))
			{
			$babBody->icalendars->initializeCalendars();
			}
			
		$array_urls[bab_translate("Calendar")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=".$view,
			'uid' => 'babUserCal'
			);
		}

	if( $bemail )
		{
		$array_urls[bab_translate("Mail")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=inbox",
			'uid' => 'babUserMail'
			);
		}
	if( !empty($GLOBALS['BAB_SESS_USER']) && bab_contactsAccess())
		{
		$array_urls[bab_translate("Contacts")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=contacts",
			'uid' => 'babUserContacts'
			);
		}
		
		
	require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
	if(userHavePersonnalStorage() || userHaveRightOnCollectiveFolder())
		{
		$array_urls[bab_translate("File manager")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=fileman",
			'uid' => 'babUserFm',
			'desc' => bab_translate("Access to file manager")
			);
		}


	$bdiradd = false;
	$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( $row['id_group'] != 0 )
			{
			list($bdiraccess) = $babDB->db_fetch_row($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
			}
		else
			$bdiraccess = 'Y';
		if($bdiraccess == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
			{
			$bdiradd = true;
			break;
			}
		}

	if( $bdiradd === false )
		{
		$res = $babDB->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL."");
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id']))
				{
				$array_urls[bab_translate("Directories")] = array(
					'url' =>  $GLOBALS['babUrlScript']."?tg=directory",
					'uid' => 'babUserDir'
					);
				break;
				}
			}
		}

	if( $bdiradd )
		{
		$array_urls[bab_translate("Directories")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=directory",
			'uid' => 'babUserDir'
			);
		}

	if( count($babBody->ocids) > 0 )
		{
		$array_urls[bab_translate("Charts")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=charts",
			'uid' => 'babUserCharts'
			);
		}

	if ( bab_statisticsAccess() != -1 )
		{
		$array_urls[bab_translate("Statistics")] = array(
			'url' =>  $GLOBALS['babUrlScript']."?tg=stat",
			'uid' => 'babUserStats'
			);
		}

		
	global $babInstallPath;
	require_once($babInstallPath . 'tmContext.php');

	$context =& getTskMgrContext();
	
	$bIsAccessValid = ($context->isUserProjectVisualizer() || $context->isUserCanCreateProject() || $context->isUserProjectManager() 
		|| $context->isUserSuperviseProject() || $context->isUserManageTask() || $context->isUserPersonnalTaskOwner());
			
	if($bIsAccessValid)
		{
		$array_urls[bab_translate("Task Manager")] = array(
			'url' =>  $GLOBALS['babUrlScript'].'?tg=usrTskMgr',
			'uid' => 'babUserTm'
			);
		}
		
	$forums = $babBody->get_forums();
	if(count($forums))
		{
		$array_urls[bab_translate("Forums")] = array(
			'url' =>  $GLOBALS['babUrlScript'].'?tg=forumsuser',
			'uid' => 'babUserForums'
			);
		}
	
	
	ksort($array_urls);
	
	
	foreach($array_urls as $label => $arr) {
		$link = $event->createItem($arr['uid']);
		$link->setLabel($label);
		$link->setLink($arr['url']);
		$link->setPosition(array('root', 'DGAll', 'babUser','babUserSection'));
		if (isset($arr['desc'])) {
			$link->setDescription($arr['desc']);
		}
		$event->addFunction($link);
	}
	
	
	// addons
	$addon_urls = array();
	$addons = bab_addonsInfos::getRows();				
	foreach( $addons as $row ) 
		{
		if($row['access']) {
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_dir($addonpath)) {
				$arr = bab_getAddonsMenus($row, 'getUserSectionMenus');
				reset ($arr);
				while (list ($txt, $url) = each($arr)) {
					$addon_urls[$txt] = array(
						'url' => $url,
						'uid' => $row['title'].sprintf('_%u',crc32($url))
						);
					}
				}
			}
		}
	
	ksort($addon_urls);
	

	foreach($addon_urls as $label => $arr) {
		$link = $event->createItem($arr['uid']);
		$link->setLabel($label);
		$link->setLink($arr['url']);
		$link->setPosition(array('root', 'DGAll', 'babUser','babUserSectionAddons'));
		$event->addFunction($link);
	}
}






/**
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_articles(&$event) {

	
	include_once $GLOBALS['babInstallPath'].'utilit/artapi.php';
	
	function bab_sitemap_articlesCategoryLevel($id_category, $position, &$event, $id_delegation) {
		
		global $babDB;
		$res = bab_getArticleCategoriesRes(array($id_category), $id_delegation);
		
		if (false !== $res) {
			while ($arr = $babDB->db_fetch_assoc($res)) {
			
				$dg = false === $id_delegation ? '' : 'DG'.$id_delegation;
		
				$uid = 'bab'.$dg.'ArticleCategory_'.$arr['id'];
		
				$item = $event->createItem($uid);
				$item->setLabel($arr['title']);
				$item->setDescription(strip_tags($arr['description']));
				$item->setPosition($position);
				$item->copy_to_all_delegations = false;
				$item->setLink($GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
				$event->addFolder($item);
				
				array_push($position, $uid);
				bab_sitemap_articlesCategoryLevel($arr['id'], $position, $event, $id_delegation);
				array_pop($position);
			}
		}
		
		
		
		
		
		
		$res = bab_getArticleTopicsRes(array($id_category), $id_delegation);
		
		if (false !== $res) {
			while ($arr = $babDB->db_fetch_assoc($res)) {
			
				$dg = false === $id_delegation ? '' : 'DG'.$id_delegation;
		
				$uid = 'bab'.$dg.'ArticleTopic_'.$arr['id'];
		
				$item = $event->createItem($uid);
				$item->setLabel($arr['category']);
				$item->setDescription(strip_tags($arr['description']));
				$item->setPosition($position);
				$item->copy_to_all_delegations = false;
				$item->setLink($GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
				$event->addFunction($item);
			}
		}

		
	}
	
	
	$delegations = bab_getUserVisiblesDelegations();
	
	foreach($delegations as $id_delegation => $arr) {
	
		$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
	
		$item = $event->createItem('bab'.$dg.'Articles');
		$item->setLabel(bab_translate("Articles"));
		$item->setPosition(array('root', $id_delegation));
		$event->addFolder($item);
	
		$position = array('root', $id_delegation, 'bab'.$dg.'Articles');
		bab_sitemap_articlesCategoryLevel(0, $position, $event, $arr['id']);
		
	}
}





/**
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_faq(&$event) {

	global $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/faqincl.php';
	
	
	
	$delegations = bab_getUserVisiblesDelegations();
	
	foreach($delegations as $delegation => $arr) {
	
		$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
	
		$item = $event->createItem('bab'.$dg.'Faqs');
		$item->setLabel(bab_translate("Faqs"));
		$item->setPosition(array('root', $delegation));
		$event->addFolder($item);
	
		$position = array('root', $delegation, 'bab'.$dg.'Faqs');
		
		$res = bab_getFaqRes(false, $arr['id']);
		if (false !== $res) {
			while ($faq = $babDB->db_fetch_assoc($res)) {
				
				$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
			
				$uid = 'bab'.$dg.'Faq_'.$faq['id'];
		
				$item = $event->createItem($uid);
				$item->setLabel($faq['category']);
				$item->setDescription(strip_tags($faq['description']));
				$item->setPosition($position);
				$item->copy_to_all_delegations = false;
				$item->setLink($GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$arr['id']);
				$event->addFunction($item);
			}
		}
	}
}







/**
 * Registred function
 * @param	bab_eventBeforeSiteMapCreated	$event
 */
function bab_onBeforeSiteMapCreated(&$event) {

	global $babBody, $BAB_SESS_LOGGED;
	
	// build user node
	bab_sitemap_userSection($event);

	// build admin node
	if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0)) {
		include_once $GLOBALS['babInstallPath'].'admin/admmenu.php';
		bab_sitemap_adminSection($event);
	}
	
	// articles
	bab_sitemap_articles($event);
	
	// faq
	bab_sitemap_faq($event);
}


?>