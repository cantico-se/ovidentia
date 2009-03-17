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

	public  $uid;
	public  $label;
	public  $description 		= '';
	public  $href 				= '';
	public  $onclick 			= '';
	public  $position 			= array();
	public  $lang;
	public  $parentNode;	// ref bab_siteMap_item
	public  $parentNode_str;
	public 	$childNodes 		= array();
	private	$icon_classnames	= array();
	
	public 	$id_dgowner = false;
	
	
	/**
	 * sitemap item is a folder
	 * @access public
	 */
	public  $folder = false;
	
	/**
	 * sitemap item is a delegation folder
	 * @access public
	 */
	public  $delegation = false;
	
	
	/**
	 * constructor
	 * The uid parameter must be unique within the sitemap tree
	 * @param	string	$uid	[A-z0-9]
	 */
	public function __construct($uid) {
		$this->uid = $uid;
		$this->lang = $GLOBALS['babLanguage'];
	}
	
	/**
	 * Set node label
	 * stored as VARCHAR(255)
	 * @param	string	$label
	 */
	public function setLabel($label) {
		$this->label = $label;
	}
	
	/**
	 * Set node descripiton
	 * stored as a TEXT
	 * @param	string	$description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Set link attributes
	 * record a relative url
	 *
	 * @param	string	$href
	 * @param	string	$onclick
	 */
	public function setLink($href, $onclick = '') {

		$self = bab_getSelf();
	
		if (0 === mb_strpos($href, $GLOBALS['babUrl'].$self)) {
			$href = mb_substr($href, mb_strlen($GLOBALS['babUrl'].$self));
		}
	
		$this->href = $href;
		$this->onclick = $onclick;
	}
	
	/**
	 * Set position in tree with the full list of parents
	 * $position is an array of sitemap node uid
	 * @param	array	$position
	 */
	public function setPosition($position) {
		$this->parentNode_str = end($position);
		$this->position = $position;
	}
	
	
	/**
	 * @param	string	$lang
	 */
	public function setLanguage($lang) {
		$this->lang = $lang;
	}

	/**
	 * @param	bab_siteMap_item	$obj
	 */
	public function addChildNode(&$obj) {
		$this->childNodes[$obj->uid] = $obj;
	}
	
	/**
	 * Add Icon classname
	 * @param	string	$classname
	 * @return	bab_siteMap_item
	 */
	public function addIconClassname($classname) {
		$this->icon_classnames[] = $classname;
		return $this;
	}

	/**
	 * Icon information to record in database
	 * @return	string
	 */
	public function getIcon() {
		return implode(' ', $this->icon_classnames);
	}
	
	
	/**
	 * @return false|array
	 */
	public function getParentsFromDelegation($parents = NULL) {
	
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
	
	
	
	/**
	 * Create a clone
	 */
	public function cloneNode() {
		$clone = new bab_siteMap_item($this->uid);

		$clone->label					= $this->label;
		$clone->description 			= $this->description;
		$clone->href					= $this->href;
		$clone->onclick					= $this->onclick;
		$clone->position 				= $this->position;
		$clone->folder 					= $this->folder;
		$clone->delegation 				= $this->delegation;
		
		return $clone;
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
	 * Get new item object
	 * @param	string	$uid	(64 characters)
	 * @return 	bab_siteMap_item
	 */
	public function createItem($uid) {
		
		return new bab_siteMap_item($uid);
	}
	
	/**
	 * Add item as function into sitemap
	 * item must be unique in one delegation
	 * 
	 * @param	bab_siteMap_item	&$obj
	 * @return	boolean
	 */
	public function addFunction(&$obj) {

		if (isset($this->nodes[$obj->uid])) {
			trigger_error(sprintf('The node %s is allready in the sitemap',$obj->uid));
			$this->propagation_status = false;
			return false;
		}

		$this->nodes[$obj->uid] = $obj;
		$this->buidtree($obj);
		
		return true;
	}
	
	
	/**
	 * Add folder into sitemap
	 * Folder must be unique
	 * @param	bab_siteMap_item	&$obj
	 * @return	boolean
	 */
	public function addFolder(&$obj) {
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
	 * Get insert entry or false
	 * @param	string	$uid
	 * @return 	bab_siteMap_item
	 */
	public function getById($uid) {

		if (!isset($this->nodes[$uid])) {
			return false;
		}

		return $this->nodes[$uid];
	}

	
	
	/**
	 * 
	 * @param	bab_siteMap_item	&$obj
	 */
	public function buidtree(&$obj) {
		if (isset($this->nodes[$obj->parentNode_str])) {
			$this->nodes[$obj->parentNode_str]->addChildNode($obj);
		} else {
			$this->queue[$obj->parentNode_str] = $obj->uid;
		}
		
		if (isset($this->queue[$obj->uid])) {
			$this->buidtree($this->nodes[$this->queue[$obj->uid]]);
			unset($this->queue[$obj->uid]);
		}
	}

	
	
	
	
	
	
	/**
	 * Display as text
	 * @param	string	$uid
	 * @param	int		[$deep]
	 */
	public function displayAsText($uid, $deep = 0) {
	
		$node = $this->getById($uid);
		
		$href = isset($node->href) ? $node->href : '';
	
		$str = sprintf("%-50s %-30s %-40s\n", str_repeat('|   ',$deep).$node->label, $node->uid, $href);
		
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
	
	var $nodes;

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

		foreach($this->nodes[$node->uid]->childNodes as $childNode) {

			$id++;
			$current_id = $id;
			$key = count($insertlist);
			
			$insertlist[$key] = array(
				'id' => $id,
				'id_parent' => $id_parent,
				'lf' => $lf,
				'lr' => 1 + $lf,
				'id_function' => $childNode->uid,
				'id_dgowner' => $childNode->id_dgowner
			);
			
			
			
			
			
			if (0 < count($this->nodes[$childNode->uid]->childNodes)) {
				$this->getLevelToInsert($childNode, $id, $id, $insertlist);
				
				$nb_inserted = $id - $current_id;
				$insertlist[$key]['lr'] = $lf + 1 + (2*$nb_inserted);
			}
			
			$lf = 1 + $insertlist[$key]['lr'];
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
				$values[] = '('.$babDB->quote($node->uid).','.$babDB->quote($href).','.$babDB->quote($onclick).','.$babDB->quote($folder).','.$babDB->quote($node->getIcon()).')';
			}
			
			$babDB->db_query('
				INSERT INTO '.BAB_SITEMAP_FUNCTIONS_TBL.' 
					(
						id_function,
						url,
						onclick,
						folder,
						icon 
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




function bab_siteMap_setUserProfile($id_profile) {
	global $babDB;
	
	$babDB->db_query('UPDATE '.BAB_USERS_TBL.'  
			SET id_sitemap_profile='.$babDB->quote($id_profile).' 
			WHERE id='.$babDB->quote($GLOBALS['BAB_SESS_USERID']));
}





/**
 * Insert node and childs into database
 * @param	bab_siteMap_item	$rootNode
 * @param	array				$nodeList
 * @param	$crc				$crc		sitemap uid_functions
 */
function bab_siteMap_insertTree($rootNode, $nodeList, $crc) {


	
	global $babDB;

	//$crc = abs(crc32(serialize($rootNode)));
	//bab_debug("crc for new sitemap = $crc");
	

	// search for available profile
	// create new profile
	
	if ($GLOBALS['BAB_SESS_USERID']) {
		$res = $babDB->db_query('SELECT id FROM '.BAB_SITEMAP_PROFILES_TBL.' WHERE uid_functions = '.$babDB->quote($crc));
		if ($arr = $babDB->db_fetch_assoc($res)) {
			$id_profile = $arr['id'];
			
			bab_debug('found profile '.$id_profile, DBG_TRACE, 'Sitemap');
			bab_siteMap_setUserProfile($id_profile);
			return;

		} else {
		
			// create new profile
			$res = $babDB->db_query('INSERT INTO '.BAB_SITEMAP_PROFILES_TBL.' (uid_functions) VALUES ('.$babDB->quote($crc).')');
			$id_profile = $babDB->db_insert_id($res);
			
			bab_debug('new profile created '.$id_profile, DBG_TRACE, 'Sitemap');
			bab_siteMap_setUserProfile($id_profile);
		}
		
		
		
		
		
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
		// tree is not empty, add missing nodes
		
		$addFuncToProfile = new bab_sitemap_addFuncToProfile();
		
		

		foreach($functions as $id_function => $val) {
		
	
			switch($val) {
				case true:
					// la fonction n'est pas liée à l'arbre
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
					// la fonction est liée à l'arbre
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
			'id_function' => $rootNode->uid,
			'id_dgowner' => $rootNode->id_dgowner
		);
		
		$tree->nodes = & $nodeList;
		$tree->getLevelToInsert($rootNode, $id, 1, $insertlist);
		
		$insertlist[0]['lr'] = 2 + (2*(count($insertlist) - 1));
		
		// insert
		
		$start = 0;
		$length = 50;
		
		while ($arr = array_slice($insertlist, $start, $length)) {
		
			
			
			$req = 'INSERT INTO '.BAB_SITEMAP_TBL.' (id, id_parent, lf, lr, id_function, id_dgowner) VALUES '."\n";
			foreach($arr as $key => $row) {
			
				if (0 < $key) {
					$req.= ",\n";
				}
				
				$dgOwner = false === $row['id_dgowner'] ? 'NULL' : $babDB->quote($row['id_dgowner']);
				

				$req.= '('.$babDB->quote($row['id'])
				.','.$babDB->quote($row['id_parent'])
				.','.$babDB->quote($row['lf'])
				.','.$babDB->quote($row['lr'])
				.','.$babDB->quote($row['id_function'])
				.','.$dgOwner
				.")";
			}
			
			//bab_debug($req);
			$babDB->db_query($req);
			
			$start += $length;
		}
		
		
		$addFuncToProfile = new bab_sitemap_addFuncToProfile();
		
		foreach($functions as $id_function => $val) {

			// la fonction n'est pas liée à l'arbre
			if (isset($nodeList[$id_function])) {
				$addFuncToProfile->add($id_function, $id_profile);
			}
		}
		
		$addFuncToProfile->commit();
		
	}
	
	
	$profile_stop_time = bab_getMicrotime();
	
	/*
	bab_debug(
		sprintf('
	insert function, function label : %s s
	insert node, profile : %s s', 
		($nodes_stop_time - $nodes_start_time), 
		($profile_stop_time - $nodes_stop_time)
		),
		
		DBG_TRACE,
		'Sitemap'
	);
	*/
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

	// the node UID contain a delegation identifier before the first - if the node type si function

	if ($node->folder) {
		$node_uid = $node->uid;
	} else {
		$pos = mb_strpos($node->uid, '-');
		if (false !== $pos) {
			$node_uid = mb_substr($node->uid, 1+$pos);
		} else {
			$node_uid = $node->uid;
		}
	}
	
	if (!isset($node->position[$deep])) {
	
		// create node, test if exists
		
		$child = $tree->getFirstChild($id_parent);
		if ($child) {
		
			if ($node_uid == $child['id_function']) {
				return false;
			}
		
			while ($child = $tree->getNextSibling($child['id'])) {
				if ($node_uid == $child['id_function']) {
					return false;
				}
			}
		}
		
	
	
		// leaf creation

		$id_node = $tree->add($id_parent);

		if ($id_node) {
			$tree->setFunction($id_node, $node_uid);
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
 * write delegation id into table
 */
function bab_siteMap_delegationsRecord() {

	global $babDB;
	
	$res = $babDB->db_query('SELECT id_dgowner, lf, lr FROM '.BAB_SITEMAP_TBL.' WHERE id_dgowner IS NOT NULL');
	while ($arr = $babDB->db_fetch_assoc($res)) {
	
		$req = '
			UPDATE '.BAB_SITEMAP_TBL.' SET 
				id_dgowner = '.$babDB->quote($arr['id_dgowner']).' 
				
			WHERE 
				lf > '.$babDB->quote($arr['lf']).' 
				AND lr < '.$babDB->quote($arr['lr']).' 
		';

		$babDB->db_query($req);
	}
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
	$rootNode->id_dgowner = false;
	$rootNode->folder = 1;
	$rootNode->addIconClassname('action-go-home');

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
		$dgNode->id_dgowner = $arr['id'];
		
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

		bab_debug($newNode, DBG_TRACE, 'Sitemap');

		$event->nodes[$newNode->uid] = $newNode;
		$event->buidtree($newNode);
	}
	

	$textview = $event->displayAsText('root');
	$crc = abs(crc32($textview));

	// bab_debug($textview, DBG_TRACE, 'Sitemap');

	 $insert_time = bab_getMicrotime();

	// insert tree into database
	bab_siteMap_insertTree($rootNode, $event->nodes, $crc);
	
	// write id_dgowner for delegation branchs
	bab_siteMap_delegationsRecord();


    $stop_time = bab_getMicrotime();
    /*
    bab_debug(sprintf("
    
    tree : %s s
    insert : %s s 
    bab_siteMap_build : %s s", 
    
    ($insert_time - $start_time),
    ($stop_time - $insert_time),
    ($stop_time - $start_time) ), DBG_TRACE, 'Sitemap');
	*/

	return $event->propagation_status;
}






/**
 * get Url provided by addon api : getUserSectionMenus
 * @return array
 */
function bab_getUserAddonsUrls() {

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
				
					if (0 === mb_strpos($url, $GLOBALS['babUrl'].$GLOBALS['babPhpSelf'])) {
						$url = mb_substr($url, mb_strlen($GLOBALS['babUrl'].$GLOBALS['babPhpSelf']));
					}
				
					$addon_urls[] = array(
						'label' => $txt,
						'url' => $url,
						'uid' => $row['title'].sprintf('_%u',crc32($url))
						);
					}
				}
			}
		}

	return $addon_urls;
}







/**
 * get Urls for user section for one delegation
 * @param	int		$id_delegation	
 * @param	array	delegation row	
 * @param	string	$dg_prefix			string to use in sitemap UIDs, dg_refix will be 'bab' on DGAll and babDG1 on delegation 1
 * @return array
 */
function bab_getUserDelegationUrls($id_delegation, $deleg, $dg_prefix) {

	global $babDB, $babBody;

	$array_urls = array();

	// user links
	
	
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
		if( count(bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL)) > 0  || count(bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL)) > 0 )
			{
			$array_urls[] = array(
				'label' => bab_translate("Publication"),
				'url' => $GLOBALS['babUrlScript']."?tg=artedit",
				'uid' => $dg_prefix.'UserPublication'
				);
			}

		$babBody->waitapprobations = bab_isWaitingApprobations();
		if( $babBody->waitapprobations )
			{
			$array_urls[] = array(
				'label' => bab_translate("Approbations"),
				'url' => $GLOBALS['babUrlScript']."?tg=approb",
				'uid' => $dg_prefix.'UserApprob',
				'desc' => bab_translate("Validate waiting items"),
				'icon' => 'apps-approbations' 
				);
			}
		}

	if( count(bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL)) > 0 || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id'])|| bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1))
		{
		$array_urls[] = array(
				'label' => bab_translate("Articles management"),
				'url' 	=> $GLOBALS['babUrlScript']."?tg=topman",
				'uid' 	=> $dg_prefix.'UserArticlesMan',
				'desc' 	=> bab_translate("List article topics where i am manager"),
				'icon'	=> 'apps-articles'
				);
		}

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$array_urls[] = array(
			'label' => bab_translate("Summary"),
			'url' => $GLOBALS['babUrlScript']."?tg=calview",
			'uid' => $dg_prefix.'UserSummary',
			'desc' => bab_translate("Last published items")
		);
		
		$array_urls[] = array(
			'label' => bab_translate("Options"),
			'url' => $GLOBALS['babUrlScript']."?tg=options",
			'uid' => $dg_prefix.'UserOptions'
		);
		
		if( bab_notesAccess())
			$array_urls[] = array(
				'label' => bab_translate("Notes"),
				'url' => $GLOBALS['babUrlScript']."?tg=notes",
				'uid' => $dg_prefix.'UserNotes',
				'icon'	=> 'apps-notes'
			);
		}

	include_once dirname(__FILE__).'/faqincl.php';
	if(bab_getFaqDgNumber($deleg['id']))
		{
		$array_urls[] = array(
			'label' => bab_translate("Faq"),
			'url' => $GLOBALS['babUrlScript']."?tg=faq",
			'uid' => $dg_prefix.'UserFaq',
			'desc' => bab_translate("Frequently Asked Questions"),
			'icon' => 'apps-faqs'
			);
		}


	if( $vac )
		{
		$array_urls[] = array(
			'label' => bab_translate("Vacation"),
			'url' =>  $GLOBALS['babUrlScript']."?tg=vacuser",
			'uid' => $dg_prefix.'UserVac',
			'icon' => 'apps-vacations'
			);
		}

	if( bab_getICalendars()->calendarAccess())
		{	
		$array_urls[] = array(
			'label' => bab_translate("Calendar"),
			'url' =>  $GLOBALS['babUrlScript']."?tg=calendar",
			'uid' => $dg_prefix.'UserCal',
			'icon' => 'apps-calendar'
			);
		}

	if( $bemail )
		{
		$array_urls[] = array(
			'label' => bab_translate("Mail"),
			'url' =>  $GLOBALS['babUrlScript']."?tg=inbox",
			'uid' => $dg_prefix.'UserMail'
			);
		}
	if( !empty($GLOBALS['BAB_SESS_USER']) && bab_contactsAccess())
		{
		$array_urls[] = array(
			'label' => bab_translate("Contacts"),
			'url' =>  $GLOBALS['babUrlScript']."?tg=contacts",
			'uid' => $dg_prefix.'UserContacts',
			'icon'	=> 'apps-contacts'
			);
		}
		
		
	require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
	if(userHavePersonnalStorage() || userHaveRightOnCollectiveFolder())
		{
		$array_urls[] = array(
			'label' 	=> bab_translate("File manager"),
			'url' 		=>  $GLOBALS['babUrlScript']."?tg=fileman",
			'uid' 		=> $dg_prefix.'UserFm',
			'desc' 		=> bab_translate("Access to file manager"),
			'icon'		=> 'apps-file-manager'
			);
		}

	require_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
	$directories 		= getUserDirectoriesMixed(true, $deleg['id']);

	if( $directories )
		{

		$array_urls[] = array(
			'label' 	=> bab_translate("Directories"),
			'url' 		=>  $GLOBALS['babUrlScript']."?tg=directory",
			'uid' 		=> $dg_prefix.'UserDir',
			'folder' 	=> true,
			'icon' 		=> 'apps-directories'
			);



		foreach($directories as $arr_directory) {
				$array_urls[] = array(
					'label' 	=> $arr_directory['name'],
					'url' 		=> $arr_directory['url'],
					'uid' 		=> $arr_directory['uid'],
					'desc' 		=> $arr_directory['description'],
					'position' 	=> array('root', $id_delegation, $dg_prefix.'User',$dg_prefix.'UserSection', $dg_prefix.'UserDir')
				);
			}
		}

	if( bab_orgchartAccess() )
		{
		$array_urls[] = array(
			'label' => bab_translate("Charts"),
			'url' =>  $GLOBALS['babUrlScript']."?tg=charts",
			'uid' => $dg_prefix.'UserCharts',
			'icon'	=> 'apps-orgcharts'
			);
		}

	if ( bab_statisticsAccess() != -1 )
		{
		$array_urls[] = array(
			'label' => bab_translate("Statistics"),
			'url' =>  $GLOBALS['babUrlScript']."?tg=stat",
			'uid' => $dg_prefix.'UserStats',
			'icon'	=> 'apps-statistics' 
			);
		}

		
	global $babInstallPath;
	require_once($babInstallPath . 'tmContext.php');

	$context =& getTskMgrContext();
	
	$bIsAccessValid = ($context->isUserProjectVisualizer() || $context->isUserCanCreateProject() || $context->isUserProjectManager() 
		|| $context->isUserSuperviseProject() || $context->isUserManageTask() || $context->isUserPersonnalTaskOwner());
			
	if($bIsAccessValid)
		{
			$array_urls[] = array(
				'label' => bab_translate("Task Manager"),
				'url' 	=> $GLOBALS['babUrlScript'].'?tg=usrTskMgr',
				'uid' 	=> $dg_prefix.'UserTm',
				'folder' => true
			);

			$projects = array();

			foreach($context->getVisualisedIdProjectSpace() as $id_projectSpace => $dummy) {
				$projectSpace = bab_selectProjectSpace($id_projectSpace);

				if (false === $deleg['id'] || (int) $deleg['id'] === (int) $projectSpace['idDelegation']) {

					$res = bab_selectProjectList($id_projectSpace);
					while ($project = $babDB->db_fetch_assoc($res))
					{
						if (bab_isAccessValid(BAB_TSKMGR_PROJECTS_VISUALIZERS_GROUPS_TBL, $project['id']))
						{
							$projects[] = $project;
						}
					}
				}
			}

			bab_sort::asort($projects, 'name', bab_sort::CASE_INSENSITIVE);

			foreach($projects as $project) {
				$array_urls[] = array(
					'label' => $project['name'],
					'url' =>  $GLOBALS['babUrlScript'].'?tg=usrTskMgr&idx=displayMyTaskList&iIdProjectSpace='.$project['idProjectSpace'].'&isProject=1&iIdProject='.$project['id'],
					'uid' => $dg_prefix.'UserTm'.$project['id'],
					'desc' => $project['description'],
					'position' => array('root', $id_delegation, $dg_prefix.'User',$dg_prefix.'UserSection', $dg_prefix.'UserTm')
				);
			}
		}

	

	include_once dirname(__FILE__).'/forumincl.php';
	$res = bab_getForumsRes(false, $deleg['id']);
	if ($res) {

		$array_urls[] = array(
			'label' => bab_translate("Forums"),
			'url' 	=> $GLOBALS['babUrlScript'].'?tg=forumsuser',
			'uid' 	=> $dg_prefix.'UserForums',
			'desc' 	=> bab_translate('Discussion forums'),
			'folder' => true,
			'icon'	=> 'apps-forums'
		);

		while ($forum = $babDB->db_fetch_assoc($res)) {

			$array_urls[] = array(
				'label' 	=> $forum['name'],
				'url' 		=> $GLOBALS['babUrlScript'].'?tg=threads&forum='.$forum['id'],
				'uid' 		=> $dg_prefix.'UserForum'.$forum['id'],
				'desc' 		=> $forum['description'],
				'position'	=> array('root', $id_delegation, $dg_prefix.'User',$dg_prefix.'UserSection', $dg_prefix.'UserForums')
			);
		}
	}

	
	if( bab_isAccessValid(BAB_TAGSMAN_GROUPS_TBL, 1) )
		{
		$array_urls[] = array(
			'label' => bab_translate("Thesaurus"),
			'url' 	=>  $GLOBALS['babUrlScript'].'?tg=thesaurus',
			'uid' 	=> $dg_prefix.'UserThesaurus'
			);
		}


	return $array_urls;
}







/**
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_userSection(&$event) {

	global $babBody, $babDB;




	$addon_urls = bab_getUserAddonsUrls();
	$delegations = bab_getUserVisiblesDelegations();
	
	foreach($delegations as $id_delegation => $deleg) {

		$dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
		$delegation_urls = bab_getUserDelegationUrls($id_delegation, $deleg, $dg_prefix);

		if (0 < count($delegation_urls) || 0 < count($addon_urls)) {

			$item = $event->createItem($dg_prefix.'User');
			$item->setLabel(bab_translate("User's section"));
			$item->setPosition(array('root', $id_delegation));
			$event->addFolder($item);

			if (0 < count($delegation_urls)) {

				$item = $event->createItem($dg_prefix.'UserSection');
				$item->setLabel(bab_translate("Ovidentia functions"));
				$item->setPosition(array('root', $id_delegation, $dg_prefix.'User'));
				$event->addFolder($item);

				foreach($delegation_urls as $arr) {
					$item = $event->createItem($arr['uid']);
					$item->setLabel($arr['label']);
					$item->setLink($arr['url']);
					$position = isset($arr['position']) ? $arr['position'] : array('root', $id_delegation, $dg_prefix.'User',$dg_prefix.'UserSection');
					$item->setPosition($position);

					if (isset($arr['desc'])) {
						$item->setDescription($arr['desc']);
					}
					
					if (isset($arr['folder'])) {
						$event->addFolder($item);
					} else {
						$event->addFunction($item);
					}

					if (isset($arr['icon'])) {
						$item->addIconClassname($arr['icon']);
					}
				}
				
			}
			
			
			// append links provided by addons on all delegations
			if (0 < count($addon_urls)) {
				
				$item = $event->createItem($dg_prefix.'UserSectionAddons');
				$item->setLabel(bab_translate("Add-ons links"));
				$item->setPosition(array('root', $id_delegation, $dg_prefix.'User'));
				$event->addFolder($item);

			
				

				foreach($addon_urls as $arr) {
					$link = $event->createItem($dg_prefix.$arr['uid']);
					$link->setLabel($arr['label']);
					$link->setLink($arr['url']);
					$link->setPosition(array('root', $id_delegation, $dg_prefix.'User',$dg_prefix.'UserSectionAddons'));
					$event->addFunction($link);
				}
			}
		}
	}
}






/**
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_articles(&$event) {
	global $babDB;
	
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
				$item->setLink($GLOBALS['babUrlScript']."?tg=topusr&cat=".$arr['id']);
				$item->addIconClassname('apps-articles');
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
				$item->setLink($GLOBALS['babUrlScript']."?tg=articles&topics=".$arr['id']);
				$event->addFunction($item);
			}
		}

		
	}
	
	
	$delegations = bab_getUserVisiblesDelegations();
	
	foreach($delegations as $id_delegation => $arr) {

		$res = bab_getArticleCategoriesRes(array(0), $id_delegation);
		if (0 < $babDB->db_num_rows($res)) {

			$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
	
			$item = $event->createItem('bab'.$dg.'Articles');
			$item->setLabel(bab_translate("Articles"));
			$item->setPosition(array('root', $id_delegation));
			$event->addFolder($item);

			$position = array('root', $id_delegation, 'bab'.$dg.'Articles');
			bab_sitemap_articlesCategoryLevel(0, $position, $event, $arr['id']);
		}

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
	
		
		
		$res = bab_getFaqRes(false, $arr['id']);
		if (false !== $res) {

			$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
	
			$item = $event->createItem('bab'.$dg.'Faqs');
			$item->setLabel(bab_translate("Faqs"));
			$item->setPosition(array('root', $delegation));
			$event->addFolder($item);
		
			$position = array('root', $delegation, 'bab'.$dg.'Faqs');


			while ($faq = $babDB->db_fetch_assoc($res)) {
				
				$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
			
				$uid = 'bab'.$dg.'Faq_'.$faq['id'];
		
				$item = $event->createItem($uid);
				$item->setLabel($faq['category']);
				$item->setDescription(strip_tags($faq['description']));
				$item->setPosition($position);
				$item->setLink("?tg=faq&idx=Print&item=".$faq['id']);
				$item->addIconClassname('apps-faqs');
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

	// $logged_status = empty($BAB_SESS_LOGGED) ? 'FALSE' : 'TRUE';
	// $isSuperAdmin  =  $babBody->isSuperAdmin ? 'TRUE'  : 'FALSE';

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