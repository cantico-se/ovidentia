<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
// 
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 * @package Utilities
 */




/**
 * An ordered collection of nodes.
 * @package Utilities
 * @subpackage Types
 */
class bab_NodeList
{
/**#@+
 * @access private
 */	
	var $_firstNode;
/**#@-*/
	
	public function __construct(&$firstNode)
	{
		$this->_length = null;
		$this->_firstNode =& $firstNode;
	}

	/**
	 * Returns the number of nodes in the node list.
	 * @return int
	 */
	public function length()
	{
		$length = 0;
		$node =& $this->_firstNode;
		while (!is_null($node)) {
			$node =& $node->nextSibling();
			$length++;
		}
		return $length;
	}
	
	/**
	 * Returns the $nth node of the node list.
	 * Returns null if n is greater than the length of the node list.
	 * @param int $n index of the node to fetch the node list (starting at 0).
	 * @return bab_Node
	 */
	public function &item($n)
	{
		$i = 0;
		$node =& $this->_firstNode;
		while (!is_null($node) && $i < $n) {
			$node =& $node->nextSibling();
			$i++;
		}
		return ($i === $n) ? $node : bab_Node::NULL_NODE();
	}
}




$GLOBALS['BAB_NODE_NULL'] = null;


/**
 * A tree node which can contain arbitrary data.
 * @package Utilities
 * @subpackage Types
 */
class bab_Node
{
/**#@+
 * @access private
 */	
	var $_id;
	var $_data;
	var $_nextSibling;
	var $_previousSibling;
	var $_parent;
	var $_firstChild;
	var $_lastChild;
	var $_tree;
/**#@-*/
	
	/**
	 * @param bab_RootNode $rootNode
	 * @param string $id
	 * @return bab_Node
	 */
	function __construct(&$rootNode, $id = null)
	{
		$this->_id = $id;
		$this->_data = null;
		$this->_nextSibling =& bab_Node::NULL_NODE();
		$this->_previousSibling =& bab_Node::NULL_NODE();
		$this->_parent =& bab_Node::NULL_NODE();
		$this->_firstChild =& bab_Node::NULL_NODE();
		$this->_lastChild =& bab_Node::NULL_NODE();
		$this->_tree =& $rootNode;
	}


	/**
	 * Returns a reference to null.
	 * @static 
	 * @return null
	 */
	public function &NULL_NODE()
	{
		return $GLOBALS['BAB_NODE_NULL'];
	}


	/**
	 * Sets the data associated to the node.
	 * @param mixed $data
	 */
	public function setData(&$data)
	{
		$this->_data =& $data;
	}

	/**
	 * Returns the data associated to the node.
	 * @return mixed
	 */
	public function &getData()
	{
		return $this->_data;
	}

	/**
	 * Returns the id of the node.
	 * @return string
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns the previous sibling of the node or null.
	 * @return bab_Node
	 */
	public function &previousSibling()
	{
		return $this->_previousSibling;
	}

	/**
	 * Returns the next sibling of the node or null.
	 * @return bab_Node
	 */
	public function &nextSibling()
	{
		return $this->_nextSibling;
	}

	/**
	 * Returns the parent of the node or null.
	 * @return bab_Node
	 */
	public function &parentNode()
	{
		return $this->_parent;
	}

	/**
	 * Returns the first child of the node or null.
	 * @return bab_Node
	 */
	public function &firstChild()
	{
		return $this->_firstChild;
	}

	/**
	 * Returns the last child of the node or null.
	 * @return bab_Node
	 */
	public function &lastChild()
	{
		return $this->_lastChild;
	}

	/**
	 * Returns whether the node is the first child of its parent.
	 * @return boolean
	 */
	public function isFirstChild()
	{
		return is_null($this->previousSibling());
	}

	/**
	 * Returns whether the node is the last child of its parent.
	 * @return boolean
	 */
	public function isLastChild()
	{
		return is_null($this->nextSibling());
	}

	/**
	 * Returns whether the node has children.
	 * @return boolean
	 */
	public function hasChildNodes()
	{
		return (!is_null($this->firstChild()));
	}
	
	/**
	 * Returns the list of child nodes.
	 * @return bab_NodeList
	 */
	public function childNodes()
	{
		$nodeList = new bab_NodeList($this->firstChild());
		return $nodeList;
	}
	
	/**
	 * Tries to append the node $newNode as last child of the node.
	 * @param bab_Node $newNode
	 * @return boolean
	 */
	public function appendChild(&$newNode)
	{
		if ($this->hasChildNodes()) {
			$this->_lastChild->_nextSibling =& $newNode;
		} else {
			$this->_firstChild =& $newNode;
		}
		$newNode->_previousSibling =& $this->_lastChild;
		$this->_lastChild =& $newNode;
		$newNode->_parent =& $this;
		return true;
	}

	/**
	 * Tries to insert the node $newNode before the node $refNode,
	 * so that $newNode would be the previousSibling of $refNode.
	 * @param bab_Node $newNode
	 * @param bab_Node $refNode
	 * @return boolean
	 */
	public function insertBefore(&$newNode, &$refNode)
	{
		if ($refNode->isFirstChild()) {
			$this->_firstChild =& $newNode;
		} elseif ($refNode->isLastChild()) {
			$this->_lastChild =& $newNode;
			$refNode->_previousSibling->_nextSibling =& $newNode;
		} else {
			$newNode->_previousSibling =& $refNode->_previousSibling;
			$refNode->_previousSibling->_nextSibling =& $newNode;			
		}

		$newNode->_parent =& $this;

		$refNode->_previousSibling =& $newNode;
		$newNode->_nextSibling =& $refNode;
		return true;
	}

	/**
	 * Remove $node from the child nodes.
	 *
	 * @param bab_Node $node
	 * @return bab_Node
	 */
	public function removeChild(&$node)
	{
		$node->_parent =& bab_Node::NULL_NODE();

		if ($node->isFirstChild()) {
			if ($node->isLastChild()) {
				$this->_firstChild =& bab_Node::NULL_NODE();
				$this->_lastChild =& bab_Node::NULL_NODE();
			} else {
				$this->_firstChild =& $node->_nextSibling;
				$this->_firstChild->_previousSibling =& bab_Node::NULL_NODE();
				$node->_nextSibling =& bab_Node::NULL_NODE();
			}
		} else {
			if ($node->isLastChild()) {
				$this->_lastChild =& $node->_previousSibling;
				$node->_previousSibling->_nextSibling =& bab_Node::NULL_NODE();
				$node->_previousSibling =& bab_Node::NULL_NODE();
			} else {
				$node->_previousSibling->_nextSibling =& $node->_nextSibling;
				$node->_nextSibling->_previousSibling =& $node->_previousSibling;
				$node->_previousSibling =& bab_Node::NULL_NODE();
				$node->_nextSibling =& bab_Node::NULL_NODE();
			}			
		}
        return $node;
	}


	/**
	 * Replace the node $oldNode from the child nodes by the node $newNode. 
	 * @param bab_Node $newNode
	 * @param bab_Node $oldNode
	 * @return bab_Node The node replaced.
	 */
	public function &replaceChild(&$newNode, &$oldNode)
	{
		$newNode->_parent =& $this;

		if ($oldNode->isFirstChild()) {
			if ($oldNode->isLastChild()) {
				$this->_firstChild =& $newNode;
				$this->_lastChild =& $newNode;
			} else {
				$this->_firstChild =& $newNode;
				$newNode->_nextSibling =& $oldNode->_nextSibling;
				$newNode->_nextSibling->_previousSibling =& $newNode;
			}
		} else {
			if ($node->isLastChild()) {
				$this->_lastChild =& $newNode;
				$oldNode->_previousSibling->_nextSibling =& $newNode;
				$newNode->_previousSibling =& $oldNode->_previousSibling;
			} else {
				$newNode->_previousSibling =& $oldNode->_previousSibling;
				$oldNode->_previousSibling->_nextSibling =& $newNode;
				$newNode->_nextSibling =& $oldNode->_nextSibling;
				$oldNode->_nextSibling->_previousSibling =& $newNode;
			}
		}
		$oldNode->_nextSibling =& bab_Node::NULL_NODE();
		$oldNode->_previousSibling =& bab_Node::NULL_NODE();
		$oldNode->_parent =& bab_Node::NULL_NODE();
		$oldNode->_firstChild =& bab_Node::NULL_NODE();
		$oldNode->_lastChild =& bab_Node::NULL_NODE();

		return $oldNode;
	}

	/**
	 * Swaps the node $firstNode and its next sibling.
	 * @param bab_Node $firstNode
	 * @access private
	 */
	private function _swapConsecutiveNodes(&$firstNode)
	{
		$secondNode =& $firstNode->_nextSibling;
		if ($firstNode->isFirstChild()) {
			$firstNode->_parent->_firstChild =& $secondNode;
		} else {
			$firstNode->_previousSibling->_nextSibling =& $secondNode;
		}
		if ($secondNode->isLastChild()) {
			$secondNode->_parent->_lastChild =& $firstNode;
		} else {
			$secondNode->_nextSibling->_previousSibling =& $firstNode;
		}
		$firstNode->_nextSibling =& $secondNode->_nextSibling;
		$secondNode->_nextSibling =& $firstNode;
		$secondNode->_previousSibling =& $firstNode->_previousSibling;
		$firstNode->_previousSibling =& $secondNode;
	}


	/**
	 * DO NOT USE ** BROKEN
	 * 
	 * This method has to be finished
	 * 
	 * Swaps the nodes $firstNode and $secondNode.
	 * @param bab_Node $firstNode
	 * @param bab_Node $secondNode
	 */
	private function _swapNodes(bab_Node &$second, bab_Node &$secondNode)
	{
		if ($firstNode->nextSibling() === $secondNode) {
			$this->_swapConsecutiveNodes($firstNode);
		} else if ($secondNode->nextSibling() === $firstNode) {
			$this->_swapConsecutiveNodes($secondNode);
		} else {
			$firstNodeParent =& $firstNode->_parent;
			$firstNodePreviousSibling =& $firstNode->_previousSibling;
			$firstNodeNextSibling =& $firstNode->_nextSibling;

			$secondNodeParent =& $firstNode->_parent;
			$secondNodePreviousSibling =& $secondNode->_previousSibling;
			$secondNodeNextSibling =& $secondNode->_nextSibling;
			
			$firstNode->_parent =& $secondNodeParent;
			$firstNode->_previousSibling =& $secondNodeNextSibling;
			$firstNode->_nextSibling =& $secondNodeNextSibling;

			$secondNode->_parent =& $firstNodeParent;
			$secondNode->_previousSibling =& $firstNodePreviousSibling;
			$secondNode->_nextSibling =& $firstNodeNextSibling;
		}
	}

	/**
	 * Sorts the child nodes.
	 * The data associated to the nodes must be an object implementing a 'compare'
	 * method. This method must compare the object with a similar object passed in
	 * parameter and return a scalar value.
	 * The value returned by $a->compare($b) must be:
	 * - 0 if "$a == $b"
	 * - > 0 if "$a > $b"
	 * - < 0 if "$a < $b"
	 * 
	 * @see bab_Node::sortSubTree()
	 */
	public function sortChildNodes_deprecated()
	{
		$nodes = array();
		$node =& $this->firstChild();
		for ($i = 0; !is_null($node); $node =& $node->nextSibling()) {
			$nodes[$i++] =& $node;
		}
		if ($i === 0)
			return;
//		$elementClass = get_class($nodes[0]);
		$changed = true;
		for ($end = count($nodes) - 1; $changed && $end > 0; $end--) {
			$changed = false;
			for ($current = 0; $current < $end; $current++) {
				$currentElement =& $nodes[$current]->getData();
				$nextElement =& $nodes[$current + 1]->getData();
				if ($currentElement->compare($nextElement) > 0) {
					$changed = true;
					bab_Node::_swapConsecutiveNodes($nodes[$current]);
					$temp =& $nodes[$current];
					$nodes[$current] =& $nodes[$current + 1];
					$nodes[$current + 1] =& $temp;
				}
			}
		}
	}


	/**
	 * Sorts the child nodes.
	 * The data associated to the nodes must be an object implementing a 'compare'
	 * method. This method must compare the object with a similar object passed in
	 * parameter and return a scalar value.
	 * The value returned by $a->compare($b) must be:
	 * - 0 if "$a == $b"
	 * - > 0 if "$a > $b"
	 * - < 0 if "$a < $b"
	 * 
	 * @see bab_Node::sortSubTree()
	 */
	public function sortChildNodes()
	{
		$nodes = array();
		while (!is_null($node = &$this->firstChild())) {
			$nodes[] = &$this->removeChild($node);
		}

		usort($nodes, array($this, 'sortChildNodes_compare'));
		foreach($nodes as &$node) {
			$this->appendChild($node);
		}
	}


	public function sortChildNodes_compare($a, $b)
	{
		return $a->getData()->compare($b->getData());
	}



	
	/**
	 * Recursively sorts the descendants of the node.
	 * @see bab_Node::sortChildNodes()
	 */
	public function sortSubTree()
	{
		if ($this->hasChildNodes()) {
			$node =& $this->firstChild();
			while (!is_null($node)) {
				$node->sortSubTree();
				$node =& $node->_nextSibling;				
			}
			$this->sortChildNodes();
		}
	}
	

	
	
	
	public function __toString()
	{
		return $this->displayAsText();
	}


	/**
	 * Display as text
	 * @param	int		[$deep]
	 */
	public function displayAsText($deep = 0) {
	
		$title = '';
		$mixed = $this->getData();
		if (is_array($mixed)) {
			$title = reset($mixed);
		} else if (is_object($mixed)) {
			foreach($mixed as $key => $title) {
				break;
			}
		
		} else {
			$title = (string) $mixed;
		}
	
		$str = sprintf("%-60s %-30s\n", str_repeat('|   ',$deep).$title, $this->getId());
		
		if ($this->hasChildNodes()) {
			$deep++;
			$currentNode = $this->firstChild();
			$str .= $currentNode->displayAsText($deep);
			while(NULL !== $currentNode = $currentNode->nextSibling()) {
				$str .= $currentNode->displayAsText($deep);
			}
		}
		
		return $str;
	}
}


/**
 * The root node of a tree.
 * The class bab_RootNode provides the ability to access quickly any node in
 * the tree by its id (getNodeById).
 * @package Utilities
 * @subpackage Types
 */
class bab_RootNode extends bab_Node
{
	/**#@+
	 * @access private
	 */	
	var $_ids;
	/**#@-*/
	
	public function __construct()
	{
		parent::__construct(bab_Node::NULL_NODE());
		$this->_ids = array();
	}

	/**
	 * Creates a node.
	 * 
	 * If $id is specified, it must not be the id of a node in the descendant of the bab_RootNode.
	 * @param mixed $data The data for the node.
	 * @param string $id
	 * @return bab_Node
	 */
	function &createNode(&$data, $id = null)
	{
		if (!is_null($id) && array_key_exists($id, $this->_ids)) {
			bab_debug(sprintf('Node id "%s" already exists.', $id));
			return bab_Node::NULL_NODE();
		}
		$newNode =& new bab_Node($this, $id);
		$newNode->setData($data);
		if (!is_null($newNode->getId())) {
			$this->_ids[$newNode->getId()] =& $newNode;
		}
		return $newNode;
	}
	
	/**
	 * Returns an iterator starting from the node $root.
	 * @param bab_Node $root
	 * @return bab_NodeIterator
	 */
	function &createNodeIterator(&$root)
	{
		$nodeIterator =& new bab_NodeIterator($root);
		return $nodeIterator;
	}

	/**
	 * Returns the node whose id is given by $id
	 *
	 * Returns the node whose id is given by $id. If no such node exists, returns null.
	 * @param string $id
	 * @return bab_Node | null
	 */
	function &getNodeById($id)
	{
		if (array_key_exists($id, $this->_ids)) {
			return $this->_ids[$id];
		}
		return bab_Node::NULL_NODE();
	}

}

/**
 * This class provides the ability to perform a depth-first traversal of a tree.
 * @package Utilities
 * @subpackage Types
 */
class bab_NodeIterator
{
	/**#@+
	 * @access private
	 */	
	var $_tree;
	var $_currentNode;
	var $_nodeStack;
	var $_levelStack;
	var $_level;
	/**#@-*/
	
	/**
	 * @param bab_Node $node	The starting node for the iterator.
	 */
	public function __construct(&$node)
	{
		$this->_tree =& $node->_tree;
		$this->_currentNode =& $node;
		$this->_nodeStack = array();
		$this->_levelStack = array();
		$this->_level = 0;
	}

	/**
	 * Returns the current depth level of the iterator in the tree. The
	 * starting node of the iterator is considered level 0.
	 * @return int
	 */
	public function level()
	{
		return $this->_level;
	}

	/**
	 * Returns the next node in the tree.
	 * 
	 * @return bab_Node
	 */
	public function &nextNode()
	{
		$node =& $this->_currentNode;

		if (!is_null($node)) {
			if ($node->hasChildNodes()) {
				$sibling =& $node->nextSibling();
				if (!is_null($sibling) /* && $this->_level !== 0 */) {
					$this->_nodeStack[] =& $sibling;
					array_push($this->_levelStack, $this->_level);
				}
				$this->_currentNode =& $node->firstChild();
				$this->_level++;
			} else {
				$this->_currentNode =& $node->nextSibling();
				if (is_null($this->_currentNode) && count($this->_nodeStack) > 0) {
					end($this->_nodeStack);
					$this->_currentNode =& $this->_nodeStack[key($this->_nodeStack)];
					unset($this->_nodeStack[key($this->_nodeStack)]);
					$this->_level = array_pop($this->_levelStack);
				}
			}
		}
		return $node;
	}
}


/**
 * The class bab_OrphanRootNode provides the ability to insert nodes before their parents
 * are inserted. When the parents are inserted later, their children will
 * automatically be appended to their list of child nodes.
 * 
 * @package Utilities
 * @subpackage Types
 */
class bab_OrphanRootNode extends bab_RootNode
{
	private $_orphansByParent;
	private $_orphans;
	
	public function __construct()
	{
		parent::__construct();
		$this->_orphansByParent = array();
		$this->_orphans = array();
	}
	

	/**
	 * Checks if the node $newNodeId has orphans waiting for it and
	 * appends them to its list of child nodes.
	 */
	private function _update($newNodeId)
	{
		if (!isset($this->_orphansByParent[$newNodeId])) {
			return;
		}
		$newNodeChildNodes =& $this->_orphansByParent[$newNodeId];
		$newNode =& $this->getNodeById($newNodeId);	
		foreach (array_keys($newNodeChildNodes) as $childId) {
			$childNode =& $newNodeChildNodes[$childId];
			unset($newNodeChildNodes[$childId]);
			unset($this->_orphans[$childNode->getId()]);
			$newNode->appendChild($childNode);
		}
	}


	/**
	 * Creates a node.
	 *
	 * @param mixed $data
	 * @param string $id
	 * @return bab_Node
	 */
	public function &createNode(&$data, $id = null)
	{
		$newNode =& parent::createNode($data, $id);
		if (is_null($newNode)) {
			return bab_Node::NULL_NODE();
		}
		$this->_update($id);
		return $newNode;
	}

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
	public function appendChild(&$newNode, $id = null)
	{
		if (!($newNode instanceof bab_Node))
			return false;

		if (is_null($id)) {
			return parent::appendChild($newNode);
		}
		$newNodeId = $newNode->getId();
		if (array_key_exists($newNodeId, $this->_orphans)) {
			return false;
		}

		$parentNode =& $this->getNodeById($id);
		if (!is_null($parentNode)) {
			return $parentNode->appendChild($newNode);
		}
		if (array_key_exists($id, $this->_orphans)) {
			$parentNode =& $this->_orphans[$id];
			return $parentNode->appendChild($newNode);
		}
		
		if (!array_key_exists($id, $this->_orphansByParent)) {
			$this->_orphansByParent[$id] = array();
		}
		$this->_orphans[$newNodeId] =& $newNode;
		$this->_orphansByParent[$id][] =& $newNode;
		
		return true;
	}
}
