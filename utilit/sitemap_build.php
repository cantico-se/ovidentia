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
class bab_siteMap_buildItem {

	public  $uid;
	public  $label;
	public  $description 		= '';
	public  $href 				= '';
	public  $onclick 			= '';
	public  $position 			= array();
	public  $lang;
	public  $parentNode;		// ref bab_siteMap_buildItem
	public  $parentNode_str;
	public 	$childNodes 		= array();
	private	$icon_classnames	= array();
	
	public 	$id_dgowner 		= false;
	
	/**
	 * all sitemap item childnodes loaded
	 * if folder is false, progress must be true
	 * 
	 * @var bool
	 */
	public $progress 			= null;
	
	
	/**
	 * sitemap item is a folder
	 * @access public
	 */
	public  $folder 			= false;
	
	/**
	 * sitemap item is a delegation folder
	 * @access public
	 */
	public  $delegation 		= false;
	
	
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
	 * @param	bab_siteMap_buildItem	$obj
	 */
	public function addChildNode($obj) {
		$this->childNodes[$obj->uid] = $obj;
	}
	
	/**
	 * Add Icon classname
	 * @param	string	$classname
	 * @return	bab_siteMap_buildItem
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
	 * Create a clone
	 */
	public function cloneNode() {
		$clone = new bab_siteMap_buildItem($this->uid);

		$clone->label					= $this->label;
		$clone->description 			= $this->description;
		$clone->href					= $this->href;
		$clone->onclick					= $this->onclick;
		$clone->position 				= $this->position;
		$clone->folder 					= $this->folder;
		$clone->delegation 				= $this->delegation;
		$clone->progress				= $this->progress;
		
		return $clone;
	}
	
	
}



/**
 * Event used to collect items before creation of the sitemap
 */
class bab_eventBeforeSiteMapCreated extends bab_event {

	public $nodes = array();
	public $queue = array();
	public $propagation_status = true;
	
	/**
	 * required path for sitemap loading
	 * if the value is null, the full sitemap is required
	 * @var array
	 */
	public $path;
	
	/**
	 * Number of levels required for sitemap loading in path
	 * @var int
	 */
	public $levels;

	/**
	 * Get new item object
	 * @param	string	$uid	(64 characters)
	 * @return 	bab_siteMap_buildItem
	 */
	public function createItem($uid) {
		
		return new bab_siteMap_buildItem($uid);
	}
	
	
	
	/**
	 * Test if childnodes must be loaded
	 * if childnodes are loaded only while this method return true, the progress property will be atomatically set 
	 * if all childnodes are loaded while this method return false, the progress property must be set to true manually
	 * 
	 * @param	array	$position		Node position
	 * 
	 * true 	: all childnodes of the node are loaded
	 * false 	: childnodes need a reload
	 * 
	 * @return bool
	 */
	public function loadChildNodes(Array $position)
	{
		
		
		if (null === $this->path) {
			return true;
		}
		
		foreach($this->path as $k => $parentNode) {
			if (isset($position[$k]) && $position[$k] !== $parentNode) {
				// the path of node is not in required path, consider node in load on progress
				return false;
			}
		}
		
		if (null === $this->levels) {
			// all childnodes must be loaded
			return true;
		}
		
		
		if (count($position) >= $this->levels + count($this->path)) {
			return false;
		}
		
		return true;
	}
	
	
	
	/**
	 * Add item as function into sitemap
	 * item must be unique
	 * 
	 * @param	bab_siteMap_buildItem	$obj
	 * @return	boolean
	 */
	public function addFunction(bab_siteMap_buildItem $obj) {

		if (isset($this->nodes[$obj->uid])) {
			trigger_error(sprintf('The node %s is allready in the sitemap',$obj->uid));
			$this->propagation_status = false;
			return false;
		}
		
		
		if (!$this->loadChildNodes($obj->position)) {
			return false;
		}
		
		$obj->progress = true;
		$this->nodes[$obj->uid] = $obj;
		$this->buidtree($obj);
		
		return true;
	}
	
	
	/**
	 * Add folder into sitemap
	 * Folder must be unique
	 * @param	bab_siteMap_buildItem	$obj
	 * @return	boolean
	 */
	public function addFolder(bab_siteMap_buildItem $obj) {
		if (isset($this->nodes[$obj->uid])) {
			trigger_error(sprintf('The node %s is allready in the sitemap',$obj->uid));
			$this->propagation_status = false;
			return false;
		}
		
		if (!$this->loadChildNodes($obj->position)) {
			// bab_debug(sprintf('The node %s is not in the required subtree (%s)',implode('/', $obj->position).'/'.$obj->uid, implode('/', $this->path)), DBG_TRACE, 'Sitemap');
			return false;
		}

		$obj->folder = true;
		
		if (null === $obj->progress) {
			
			$childNodesPos = $obj->position;
			$childNodesPos[] = $obj->uid;

			$obj->progress = $this->loadChildNodes($childNodesPos);
		}

		$this->nodes[$obj->uid] = $obj;
		$this->buidtree($obj);
		
		return true;
	}

	/**
	 * Get insert entry or false
	 * @param	string	$uid
	 * @return 	bab_siteMap_buildItem
	 */
	public function getById($uid) {

		if (!isset($this->nodes[$uid])) {
			return false;
		}

		return $this->nodes[$uid];
	}

	
	
	/**
	 * 
	 * @param	bab_siteMap_buildItem	$obj
	 */
	public function buidtree($obj) {
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
	
	function setFunction($id_node, $str, $progress) {
		global $babDB;
		
		$progress = $progress ? '1' : '0';
		
		$babDB->db_query('UPDATE '.BAB_SITEMAP_TBL.' 
			SET 
				id_function='.$babDB->quote($str).', 
				progress='.$babDB->quote($progress).'
			WHERE id='.$babDB->quote($id_node) );
	}
	

	
	
	
	/**
	 * Get childnodes from this node with collumn to insert
	 *
	 * @param	bab_siteMap_buildItem	$node
	 * @param	int						&$id
	 * @param	int						$id_parent
	 * @param	array					&$insertlist		(id, id_parent, lf, lr, id_function)
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
				'id_dgowner' => $childNode->id_dgowner,
				'progress' => $childNode->progress ? '1' : '0'
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
	 * @param	bab_siteMap_buildItem	$node
	 */
	function insertFunction($node) {
	
		$this->functions[] = $node;
	
		$this->insertFunctionLabel($node);
	}
	
	/**
	 * Insert function label for current language into database
	 * @param	bab_siteMap_buildItem	$node
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










/**
 * Insert node and childs into database
 * @param	bab_siteMap_buildItem	$rootNode
 * @param	array					$nodeList
 * @param	$crc					$crc		sitemap uid_functions
 */
class bab_siteMap_insertTree
{
	/**
	 * @var bab_siteMap_buildItem
	 */
	private $rootNode;
	
	/**
	 * @var array
	 */
	private $nodeList;
	
	
	/**
	 * All functions in sitemap
	 * @var array
	 */
	private $functions;
	
	/**
	 * Missing label on functions in current language
	 * @var array
	 */
	private $missing_labels;
	
	
	/**
	 * Missing functions in profile
	 * @var array
	 */
	private $missing_profile;
	
	
	/**
	 * Existing nodes with progress set to 0
	 * @var array
	 */
	private $missing_progress;
	

	public function __construct(bab_siteMap_buildItem $rootNode, $nodeList) {

		
		$this->rootNode = $rootNode;
		$this->nodeList = $nodeList;
	}
	
	
	/**
	 * Create a new profile based on the CRC of the sitemap to insert
	 * if profile allready exists, the profile is associated to user
	 * 
	 * @param int $crc
	 * @return null
	 */
	public function fromCrc($crc, $root_function, $levels)
	{
		//$crc = abs(crc32(serialize($rootNode)));
		//bab_debug("crc for new sitemap = $crc");
		
		$id_profile = $this->getUserProfileFromSiteMap($crc, $root_function, $levels);
		$this->completeProfile($id_profile);
	}
	
	
	/**
	 * the profile from the current user will be updated with the new nodes
	 * all users with this profile will have the new inserted nodes
	 * 
	 * @return bool
	 */
	public function addNodesToProfile($crc, $root_function, $levels)
	{
		global $babDB;
		
		$id_profile = $this->getProfileFromUser();
		
		if (null === $id_profile) {
			return false;
		}
		
		
		// in this case, multiple CRC are valuable for one profile
		// for now, the system will probably create more profiles
		
		$this->completeProfile($id_profile);
		$this->addProfileVersion($id_profile, $crc, $root_function, $levels);
	}
	
	
	
	
	private function lock()
	{

		global $babDB;
		
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
		
	}
	
	
	private function unlock()
	{
		global $babDB;
		
		$babDB->db_query('UNLOCK TABLES');
	}
	
	
	/**
	 * Complete tree and profile
	 * @param int $id_profile
	 * @return unknown_type
	 */
	private function completeProfile($id_profile)
	{
		$this->lock();
		
		$this->loadFunctions($id_profile);
		$this->insertFunction();

	
		$tree = new bab_sitemap_tree();
		
		if (false !== $tree->getNodeInfo(1)) {
			// tree is not empty, add missing nodes
			
			$this->addMissingNodes($tree, $id_profile);
			
		} else {
			// the tree is empty, build from scratch
			$this->quickPopulate($tree, $id_profile);
		}
		
		// write id_dgowner for delegation branchs
		$this->delegationsRecord();
		
		$this->unlock();
	}
	
	
	
	/**
	 * write delegation id into table
	 */
	private function delegationsRecord() {
	
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
	 * get exisiting functions list
	 * populate functions list, missing labels and missing functions in profile for the profile and the user language
	 */ 
	private function loadFunctions($id_profile)
	{
		global $babDB;
		
		$this->functions = array();
		$this->missing_labels = array();
		$this->missing_profile = array();
		$this->missing_progress = array();
		
		$res = $babDB->db_query('
			SELECT 
				f.id_function, 
				IFNULL(s.id,\'noref\') id, 
				fl.lang,
				fp.id_profile, 
				s.progress   
			FROM 
				'.BAB_SITEMAP_FUNCTIONS_TBL.' f 
				LEFT JOIN '.BAB_SITEMAP_TBL.' s ON s.id_function = f.id_function 
				LEFT JOIN '.BAB_SITEMAP_FUNCTION_LABELS_TBL.' fl 
					ON f.id_function = fl.id_function AND fl.lang='.$babDB->quote($GLOBALS['babLanguage']).' 
				LEFT JOIN '.BAB_SITEMAP_FUNCTION_PROFILE_TBL.' fp ON fp.id_function = f.id_function AND fp.id_profile='.$babDB->quote($id_profile).'
		');
		while ($arr = $babDB->db_fetch_assoc($res)) {
		
			$this->functions[$arr['id_function']] = $arr['id'];
			
			if (is_null($arr['lang']) && !is_null($arr['id'])) {
				$this->missing_labels[$arr['id_function']] = $arr['id'];
			}
			
			if (is_null($arr['id_profile'])) {
				$this->missing_profile[$arr['id_function']] = $arr['id'];
			}
			
			if ('0' === $arr['progress']) {
				$this->missing_progress[$arr['id_function']] = $arr['id'];
			}
		}
	}
	
	
	
	
	
	/**
	 * Insert missing functions and missing labels
	 * @return null
	 */
	private function insertFunction()
	{
		$previous_node = 'root';
		$previous_id = 1;


		$insertFunc = new bab_siteMap_insertFunctionObj();
		
		foreach($this->nodeList as $node) {

			if (isset($this->functions[$node->uid]) && 'noref' === $this->functions[$node->uid]) {
				// noref : la fonction existe mais n'est pas inseree dans l'arbre
				$this->functions[$node->uid] = true;
				
			} elseif (isset($this->functions[$node->uid])) {
				// isset : la fonction existe et est dans l'arbre
				$previous_node = $node->uid;
				$previous_id = $this->functions[$node->uid];
				$this->functions[$node->uid] = false;
				
			} else {
				// !isset : la fonction n'existe pas
				// bab_debug('sitemap add : '.$node->uid.' ('.$node->label.')');
				$insertFunc->insertFunction($node);
				$this->functions[$node->uid] = true;
			}
			
			if (isset($this->missing_labels[$node->uid])) {
				$insertFunc->insertFunctionLabel($node);
			}
		}
		
		$insertFunc->commit();
		
	}
	
	
	
	
	
	
	/**
	 * The tree is not empty, add missing nodes
	 * @return unknown_type
	 */
	private function addMissingNodes($tree, $id_profile)
	{
		$addFuncToProfile = new bab_sitemap_addFuncToProfile();

		foreach($this->functions as $id_function => $val) {

	
			switch($val) {
				case true:
					// the fonction is not linked to tree
					if (isset($this->nodeList[$id_function])) {
					
						if ('root' != $id_function) {

							bab_sitemap_insertNode(
								$tree, 
								$this->nodeList[$id_function],
								0,
								0
							);
						}
						
						$addFuncToProfile->add($id_function, $id_profile);
					}
					break;
					
				case false:
					// the fonction is linked to tree
					if (isset($this->missing_profile[$id_function]) && isset($this->nodeList[$id_function])) {
						// mais n'est pas dans le profile
						$addFuncToProfile->add($id_function, $id_profile);
					} else {
						// exists and linked to tree 
						// in sitemap modification, the progress status need to be updated
						
						if (isset($this->nodeList[$id_function]) && true === $this->nodeList[$id_function]->progress && isset($this->missing_progress[$id_function])) {
							// consider childnodes as loaded
							$this->loadCompleted($id_function);
						}
					}
					break;
				
				default:
					// la fonction n'est plus dans le profile
					bab_sitemap_removeFuncFromProfile($tree, $id_function, $id_profile);
					break;
			}
		}
		
		$addFuncToProfile->commit();
	}
	
	
	
	/**
	 * 
	 * @param string $id_function
	 * @return unknown_type
	 */
	private function loadCompleted($id_function)
	{
		global $babDB;
		
		$babDB->db_query('UPDATE '.BAB_SITEMAP_TBL.' SET progress=\'1\' WHERE id_function='.$babDB->quote($id_function));
		
	}
	
	
	
	/**
	 * the tree is empty, build from scratch
	 * @return unknown_type
	 */
	private function quickPopulate($tree, $id_profile)
	{
		global $babDB;
		
		$id = 1;
		
		$insertlist = array();

		// root node
		$insertlist[0] = array(
			'id' => $id,
			'id_parent' => 0,
			'lf' => 1,
			'lr' => 0,
			'id_function' => $this->rootNode->uid,
			'id_dgowner' => $this->rootNode->id_dgowner,
			'progress' => '1'
		);
		
		$tree->nodes = & $this->nodeList;
		$tree->getLevelToInsert($this->rootNode, $id, 1, $insertlist);
		
		$insertlist[0]['lr'] = 2 + (2*(count($insertlist) - 1));
		
		// insert
		
		$start = 0;
		$length = 50;
		
		while ($arr = array_slice($insertlist, $start, $length)) {
		

			$req = 'INSERT INTO '.BAB_SITEMAP_TBL.' (id, id_parent, lf, lr, id_function, id_dgowner, progress) VALUES '."\n";
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
				.','.$babDB->quote($row['progress'])
				.")";
			}
			
			//bab_debug($req);
			$babDB->db_query($req);
			
			$start += $length;
		}
		
		
		$addFuncToProfile = new bab_sitemap_addFuncToProfile();
		
		foreach($this->functions as $id_function => $val) {

			// the function is not linked to tree
			if (isset($this->nodeList[$id_function])) {
				$addFuncToProfile->add($id_function, $id_profile);
			}
		}
		
		$addFuncToProfile->commit();
	}
	
	
	
	
	
	
	
	
	
	/**
	 * search for available profile or create new profile
	 * @return int
	 */
	private function getUserProfileFromSiteMap($crc, $root_function, $levels)
	{
		global $babDB;
		
		if ($GLOBALS['BAB_SESS_USERID']) {
			$res = $babDB->db_query('SELECT 
					p.id 
				FROM 
					'.BAB_SITEMAP_PROFILES_TBL.' p, 
					'.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' pv
					
				WHERE 
					pv.id_profile = p.id 
					AND pv.uid_functions = '.$babDB->quote($crc)
			);
			
			if ($arr = $babDB->db_fetch_assoc($res)) {
				$id_profile = $arr['id'];
				
				bab_debug('found profile '.$id_profile, DBG_TRACE, 'Sitemap');
				$this->setUserProfile($id_profile);
				return $id_profile;
	
			} else {
				
				// the uid_functions on profiles table is deprecated
				// the collumn is not deleted for now (version 7.2.90) 
				// this way, the database stay compatible with older version of ovidentia (7.2.0 and PATCHS-7-2-0)
				// this collumn may be deleted in the next main release
			
				// create new profile
				$res = $babDB->db_query('INSERT INTO '.BAB_SITEMAP_PROFILES_TBL.' (uid_functions) VALUES (\'0\')');
				$id_profile = $babDB->db_insert_id($res);
				
				$this->addProfileVersion($id_profile, $crc, $root_function, $levels);
				
				bab_debug('new profile created '.$id_profile, DBG_TRACE, 'Sitemap');
				$this->setUserProfile($id_profile);
			}
			
			
			
			
			
		} else {
			
			$this->addProfileVersion(BAB_UNREGISTERED_SITEMAP_PROFILE, $crc, $root_function, $levels);
			$id_profile = BAB_UNREGISTERED_SITEMAP_PROFILE;
		}
		
		return $id_profile;
	}
	
	
	
	private function addProfileVersion($id_profile, $crc, $root_function, $levels)
	{
		global $babDB;
		
		$babDB->db_query('INSERT INTO '.BAB_SITEMAP_PROFILE_VERSIONS_TBL.' (id_profile, uid_functions, root_function, levels) 
					VALUES ('.$babDB->quote($id_profile).', '.$babDB->quote($crc).', '.$babDB->quoteOrNull($root_function).', '.$babDB->quoteOrNull($levels).')');
			
	}
	
	
	
	
	/**
	 * Get profile
	 * null : no profile
	 * @return int
	 */
	private function getProfileFromUser()
	{
		global $babDB;
		
		if ($GLOBALS['BAB_SESS_USERID']) {
			$res = $babDB->db_query('SELECT id_sitemap_profile 
				FROM '.BAB_USERS_TBL.'  
				WHERE id='.$babDB->quote($GLOBALS['BAB_SESS_USERID']));
			
			if ($arr = $babDB->db_fetch_assoc($res))
			{
				$id_sitemap_profile = (int) $arr['id_sitemap_profile'];
				
				if (0 !== $id_sitemap_profile && BAB_UNREGISTERED_SITEMAP_PROFILE !== $id_sitemap_profile)
				{
					return $id_sitemap_profile;
				}
			}
			
			return null;
			
			
		} else {
			return BAB_UNREGISTERED_SITEMAP_PROFILE;
		}
		
		return $id_profile;
	}
	
	
	
	
	
	private function setUserProfile($id_profile) {
		global $babDB;
		
		$babDB->db_query('UPDATE '.BAB_USERS_TBL.'  
				SET id_sitemap_profile='.$babDB->quote($id_profile).' 
				WHERE id='.$babDB->quote($GLOBALS['BAB_SESS_USERID']));
	}
}

/**
 * insert a node into tree
 * @param	bab_sitemap_tree		$tree
 * @param	bab_siteMap_buildItem	$node
 * @param	int						$id_parent
 * @param	int						$deep		profondeur dans l'arbre
 */
function bab_sitemap_insertNode($tree, $node, $id_parent, $deep) {

	global $babDB;
	

	if (!isset($node->position[$deep])) {
	
		// create node, test if exists
		
		if ($childs = $tree->getChilds($id_parent)) {
			foreach($childs as $row) {
				if ($node->uid == $row['id_function']) {
					return false;
				}
			}
		}
	
		// leaf creation

		$id_node = $tree->add($id_parent);

		if ($id_node) {
			$tree->setFunction($id_node, $node->uid, $node->progress);
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
			$tree->setFunction($id_node, $current, false);
		}
		bab_sitemap_insertNode($tree, $node, $id_node, $deep);
	}
	
	return false;
}












/**
 * Recursive childs count
 * @param	bab_siteMap_buildItem	$node
 * @param	int						[$n]
 * 
 * 
 * @return int
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
 * 
 * @return bab_eventBeforeSiteMapCreated
 */
function bab_siteMap_loadNodes($path, $levels) {

	global $babBody, $babDB;
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	include_once $GLOBALS['babInstallPath'].'utilit/utilitsections.php';
	include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
	
	
	$event = new bab_eventBeforeSiteMapCreated;
	
	$event->path = $path;
	$event->levels = $levels;
	
	// insert rootnode
	
	$rootNode = new bab_siteMap_buildItem('root');
	$rootNode->setLabel($GLOBALS['babSiteName']);
	$rootNode->setDescription($babBody->babsite['babslogan']);
	$rootNode->setLink('?');
	$rootNode->id_dgowner = false;
	$rootNode->folder = 1;
	$rootNode->progress = 1;
	$rootNode->addIconClassname('action-go-home');

	$event->nodes[$rootNode->uid] = $rootNode;
	
	// create delegations nodes
	
	$delgations = bab_getUserAdministratorDelegations();
	
	foreach($delgations as $dgid => $arr) {
		
		$dgNode = new bab_siteMap_buildItem($dgid);
		$dgNode->setLabel($arr['name']);
		$dgNode->setDescription($arr['description']);
		$dgNode->setPosition(array('root'));
		$dgNode->setLink($arr['homePageUrl']);
		$dgNode->folder = 1;
		$dgNode->progress = $event->loadChildNodes(array('root', $dgid));
		$dgNode->delegation = 1;
		$dgNode->id_dgowner = $arr['id'];
		
		$event->nodes[$dgid] = $dgNode;
		$event->buidtree($dgNode);
	}

	bab_fireEvent($event);
	
	foreach($event->queue as $missing_node => $orphan) {
		unset($event->nodes[$orphan]);
	}
	$event->queue = array();
	
	return $event;
	
}





/**
 * @see bab_siteMap::build()
 * 
 * @param	array	$path
 * @param	int		$levels
 * 
 * @return boolean
 */
function bab_siteMap_build($path, $levels) {

	$event = bab_siteMap_loadNodes($path, $levels);

	$textview = $event->displayAsText('root');
	$crc = abs(crc32($textview));

	// insert tree into database
	$insert = new bab_siteMap_insertTree($event->nodes['root'], $event->nodes);
	$root_function = null === $path ? null : end($path);
	$insert->fromCrc($crc, $root_function, $levels);

	return $event->propagation_status;
}



/**
 * @see bab_siteMap::repair()
 * 
 * @param	array	$path
 * @param	int		$levels
 * 
 */
function bab_siteMap_repair($path, $levels)
{
	$event = bab_siteMap_loadNodes($path, $levels);
	
	$textview = $event->displayAsText('root');
	$crc = abs(crc32($textview));

	$insert = new bab_siteMap_insertTree($event->nodes['root'], $event->nodes);
	$root_function = null === $path ? null : end($path);
	$insert->addNodesToProfile($crc, $root_function, $levels);

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
				'uid' => $dg_prefix.'UserPublication',
				'icon'	=> 'apps-articles'
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
			'label' => bab_translate("Options"),
			'url' => $GLOBALS['babUrlScript']."?tg=options",
			'uid' => $dg_prefix.'UserOptions',
			'icon' => 'categories-preferences-desktop'
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
			'uid' => $dg_prefix.'UserMail',
			'icon'	=> 'apps-mail'
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
	if(userHavePersonnalStorage() || userHaveRightOnCollectiveFolder(true))
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
				'icon'	=> 'apps-task-manager', 
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
			'uid' 	=> $dg_prefix.'UserThesaurus',
			'icon'	=> 'apps-thesaurus'	
			);
		}


	return $array_urls;
}







/**
 * @param	bab_eventBeforeSiteMapCreated $event
 */
function bab_sitemap_userSection($event) {

	global $babBody, $babDB;

	
	$delegations = bab_getUserVisiblesDelegations();
	
	foreach($delegations as $id_delegation => $deleg) {

		$dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];

		if ($event->loadChildNodes(array('root', $id_delegation))) {
			
			$item = $event->createItem($dg_prefix.'User');
			$item->setLabel(bab_translate("User's section"));
			$item->setPosition(array('root', $id_delegation));
			$event->addFolder($item);

			$position = array('root', $id_delegation, $dg_prefix.'User');

			if ($event->loadChildNodes($position)) {
				
				$item = $event->createItem($dg_prefix.'UserSection');
				$item->setLabel(bab_translate("Ovidentia functions"));
				$item->setPosition($position);
				$event->addFolder($item);
				
				if ($event->loadChildNodes(array('root', $id_delegation, $dg_prefix.'User', $dg_prefix.'UserSection'))) {
					
					$delegation_urls = bab_getUserDelegationUrls($id_delegation, $deleg, $dg_prefix);
	
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
							$item->progress = true;
							$event->addFolder($item);
						} else {
							$event->addFunction($item);
						}
	
						if (isset($arr['icon'])) {
							$item->addIconClassname($arr['icon']);
						}
					}
				
				}
			
			//}
			
	
				$position = array('root', $id_delegation, $dg_prefix.'User');
			
			
			//if ($event->loadChildNodes($position)) {
				
				$item = $event->createItem($dg_prefix.'UserSectionAddons');
				$item->setLabel(bab_translate("Add-ons links"));
				$item->setPosition($position);
				$event->addFolder($item);
				
				
				$position = array('root', $id_delegation, $dg_prefix.'User',$dg_prefix.'UserSectionAddons');
			
				if ($event->loadChildNodes($position)) {
				
					$addon_urls = bab_getUserAddonsUrls();

					foreach($addon_urls as $arr) {
						$link = $event->createItem($dg_prefix.$arr['uid']);
						$link->setLabel($arr['label']);
						$link->setLink($arr['url']);
						$link->setPosition($position);
						$event->addFunction($link);
					}
				}
			}
		}
	}
}


function bab_sitemap_articlesCategoryLevel($id_category, $position, $event, $id_delegation) {
	
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
			$item->progress = true;
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



/**
 * @param	bab_eventBeforeSiteMapCreated $event
 */
function bab_sitemap_articles($event) {
	global $babDB;
	
	include_once $GLOBALS['babInstallPath'].'utilit/artapi.php';
	
	
	
	
	$delegations = bab_getUserVisiblesDelegations();
	
	foreach($delegations as $id_delegation => $arr) {
		
		$delegPosition = array('root', $id_delegation);
		
		if ($event->loadChildNodes($delegPosition)) {
			
			$dg = false === $arr['id'] ? '' : 'DG'.$arr['id'];
		
			$item = $event->createItem('bab'.$dg.'Articles');
			$item->setLabel(bab_translate("Articles"));
			$item->setPosition($delegPosition);
			$event->addFolder($item);
			
			if ($event->loadChildNodes(array('root', $id_delegation, 'bab'.$dg.'Articles'))) {

				$res = bab_getArticleCategoriesRes(array(0), $id_delegation);
				if (0 < $babDB->db_num_rows($res)) {
	
					$position = array('root', $id_delegation, 'bab'.$dg.'Articles');
					bab_sitemap_articlesCategoryLevel(0, $position, $event, $arr['id']);
				}
			
			}
		
		}

	}
}





/**
 * @param	bab_eventBeforeSiteMapCreated $event
 */
function bab_sitemap_faq($event) {

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
function bab_onBeforeSiteMapCreated($event) {

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
