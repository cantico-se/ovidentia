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
 * @subpackage Widgets
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
	
	function bab_NodeList(&$firstNode)
	{
		$this->_length = null;
		$this->_firstNode =& $firstNode;
	}

	/**
	 * Returns the number of nodes in the node list.
	 * @return int
	 */
	function length()
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
	function &item($n)
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
	function bab_Node(&$rootNode, $id = null)
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
	function &NULL_NODE()
	{
		return $GLOBALS['BAB_NODE_NULL'];
	}


	/**
	 * Sets the data associated to the node.
	 * @param mixed $data
	 */
	function setData(&$data)
	{
		$this->_data =& $data;
	}

	/**
	 * Returns the data associated to the node.
	 * @return mixed
	 */
	function &getData()
	{
		return $this->_data;
	}

	/**
	 * Returns the id of the node.
	 * @return string
	 */
	function getId()
	{
		return $this->_id;
	}

	/**
	 * Returns the previous sibling of the node or null.
	 * @return bab_Node
	 */
	function &previousSibling()
	{
		return $this->_previousSibling;
	}

	/**
	 * Returns the next sibling of the node or null.
	 * @return bab_Node
	 */
	function &nextSibling()
	{
		return $this->_nextSibling;
	}

	/**
	 * Returns the parent of the node or null.
	 * @return bab_Node
	 */
	function &parentNode()
	{
		return $this->_parent;
	}

	/**
	 * Returns the first child of the node or null.
	 * @return bab_Node
	 */
	function &firstChild()
	{
		return $this->_firstChild;
	}

	/**
	 * Returns the last child of the node or null.
	 * @return bab_Node
	 */
	function &lastChild()
	{
		return $this->_lastChild;
	}

	/**
	 * Returns whether the node is the first child of its parent.
	 * @return boolean
	 */
	function isFirstChild()
	{
		return is_null($this->previousSibling());
	}

	/**
	 * Returns whether the node is the last child of its parent.
	 * @return boolean
	 */
	function isLastChild()
	{
		return is_null($this->nextSibling());
	}

	/**
	 * Returns whether the node has children.
	 * @return boolean
	 */
	function hasChildNodes()
	{
		return (!is_null($this->firstChild()));
	}
	
	/**
	 * Returns the list of child nodes.
	 * @return bab_NodeList
	 */
	function childNodes()
	{
		$nodeList = new bab_NodeList($this->firstChild());
		return $nodeList;
	}
	
	/**
	 * Tries to append the node $newNode as last child of the node.
	 * @param bab_Node $newNode
	 * @return boolean
	 */
	function appendChild(&$newNode)
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
	function insertBefore(&$newNode, &$refNode)
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
	 * @return boolean
	 */
	function removeChild(&$node)
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
				$oChildNode->_previousSibling->_nextSibling =& bab_Node::NULL_NODE();
				$oChildNode->_previousSibling =& bab_Node::NULL_NODE();
			} else {
				$node->_previousSibling->_nextSibling =& $node->_nextSibling;
				$node->_nextSibling->_previousSibling =& $node->_previousSibling;
				$node->_previousSibling =& bab_Node::NULL_NODE();
				$node->_nextSibling =& bab_Node::NULL_NODE();
			}			
		}
        return true;
	}


	/**
	 * Replace the node $oldNode from the child nodes by the node $newNode. 
	 * @param bab_Node $newNode
	 * @param bab_Node $oldNode
	 * @return bab_Node The node replaced.
	 */
	function &replaceChild(&$newNode, &$oldNode) // TODO Finish
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
	function _swapConsecutiveNodes(&$firstNode)
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
	function sortChildNodes(/*$comparisonFunction = 'bab_Node_defaultNodeComparison'*/)
	{
		$nodes = array();
		$node =& $this->firstChild();
		for ($i = 0; !is_null($node); $node =& $node->nextSibling()) {
			$nodes[$i++] =& $node;
		}
		if ($i === 0)
			return;
		$elementClass = get_class($nodes[0]);
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
	 * Recursively sorts the descendants of the node.
	 * @see bab_Node::sortChildNodes()
	 */
	function sortSubTree()
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
	
	function bab_RootNode()
	{
		parent::bab_Node(bab_Node::NULL_NODE());
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
	function bab_NodeIterator(&$node)
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
	function level()
	{
		return $this->_level;
	}

	/**
	 * Returns the next node in the tree.
	 * @return bab_Node
	 */
	function &nextNode()
	{
		$node =& $this->_currentNode;
		
		if (!is_null($node)) {
			if ($node->hasChildNodes()) {
				$sibling =& $node->nextSibling();
				if (!is_null($sibling)) {
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
 * @package Utilities
 * @subpackage Types
 */
class bab_OrphanRootNode extends bab_RootNode
{
	/**#@+
	 * @access private
	 */	
	var $_orphansByParent;
	var $_orphans;
	/**#@-*/
	
	function bab_OrphanRootNode()
	{
		parent::bab_RootNode();
		$this->_orphansByParent = array();
		$this->_orphans = array();
	}
	

	/**
	 * Checks if the node $newNodeId has orphans waiting for it and
	 * append them to its list of child nodes.
	 * @access private
	 */
	function _update($newNodeId)
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
	 * @param mixed $data
	 * @param string $id
	 */
	function &createNode(&$data, $id = null)
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
	function appendChild(&$newNode, $id = null)
	{
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




/**
 * Base class for all widgets
 * @package Utilities
 * @subpackage Widgets
 */
class bab_Widget
{
	
}


/**
 * An element (node) of a bab_TreeView.
 * @see bab_TreeView::createElement()
 * @package Utilities
 * @subpackage Widgets
 */
class bab_TreeViewElement extends bab_Widget
{
	/**#@+
	 * @access private
	 */	
	var $_id;
	var $_type;
	var $_title;
	var $_description;
	var $_link;

	var $_icon;

	var $_actions;
	var $_checkBoxes;

	var $_info;
	
	var $_subTree;
	/**#@-*/


	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 */
	function bab_TreeViewElement($id, $type, $title, $description, $link)
	{
		$this->_id = $id;
		$this->_type = $type;
		$this->_title = $title;
		$this->_description = $description;
		$this->_link = $link;
		$this->_actions = array();
		$this->_checkBoxes = array();
		$this->_icon= '';
		$this->_info = '';
		$this->_subTree = '';
	}

	/**
	 * Adds an action icon for the treeview element.
	 * @param string $name
	 * @param string $caption
	 * @param string $icon
	 * @param string $link
	 * @param string $script
	 */
	function addAction($name, $caption, $icon, $link, $script)
	{
		$this->_actions[] = array('name' => $name,
								  'caption' => $caption,
								  'icon' => $icon,
								  'link' => $link,
								  'script' => $script);
	}

	/**
	 * Adds a checkbox to the treeview element.
	 * @param string $name
	 */
	function addCheckBox($name)
	{
		$this->_checkBoxes[] = array('name' => $name);
	}

	/**
	 * Defines an info text that will appear on the right of the treeview element title.
	 * @param string $text
	 */
	function setInfo($text)
	{
		$this->_info = $text;
	}

	/**
	 * Defines the url of the treeview element icon.
	 * @param string $url
	 */
	function setIcon($url)
	{
		$this->_icon = $url;
	}

	/**
	 * Defines the url of the subTree (the url should provide the content of the subTree to be inserted).
	 * The url will be called when the TreeViewElement is expanded.
	 * @param string $url
	 */
	function setSubTree($url)
	{
		$this->_subTree = $url;
	}


	function compare(&$element)
	{
		if ((int)$this->_info > (int)$element->_info)
			return -1;
		if ((int)$this->_info < (int)$element->_info)
			return 1;
	
		if (strtoupper($this->_title) > strtoupper($element->_title))
			return 1;
		if (strtoupper($this->_title) < strtoupper($element->_title))
			return -1;
		return 0;
	}

}



define('BAB_TREE_VIEW_ID_SEPARATOR',	'__');

define('BAB_TREE_VIEW_COLLAPSED',		1);
define('BAB_TREE_VIEW_EXPANDED',		2);

define('BAB_TREE_VIEW_MULTISELECT',		1024);

/**
 * A TreeView widget used to display hierarchical data.
 * @package Utilities
 * @subpackage Widgets
 */
class bab_TreeView extends bab_Widget
{
	/**#@+
	 * @access private
	 */	
	var $_id;
	var $_rootNode;
	var $_iterator;

	var $_upToDate;

	var $t_treeViewId;
	var $t_id;
	var $t_type;
	var $t_title;
	var $t_description;
	var $t_link;
	var $t_levelVariation;
	var $t_level;
	var $t_previousLevel;
	
	var $t_isFirstChild;
	var $t_isMiddleChild;
	var $t_isSingleChild;
	var $t_isLastChild;
	
	var $t_info;
	var $t_showRightElements;

	var $t_nodeIcon;

	var $t_expand;
	var $t_collapse;
	var $t_submit;
	
	var $t_id_separator;

	var $t_isMultiSelect;
	
	var $_currentElement;

	var $_templateFile;
	var $_templateSection;
	var $_templateCss;
	var $_templateScripts;
	var $_templateCache;
	/**#@-*/


	/**
	 * @param string $id A unique treeview id in the page. Must begin with a letter ([A-Za-z]) and may be followed by any number of letters, digits ([0-9]), hyphens ("-"), underscores ("_"), colons (":"), and periods (".").
	 * @return bab_TreeView
	 */
	function bab_TreeView($id)
	{
		$this->_id = $id;
		$this->_rootNode = new bab_OrphanRootNode();
		$this->_iterator = null;

		$this->t_treeViewId= $this->_id;
		$this->t_expand = bab_translate('Expand');
		$this->t_collapse = bab_translate('Collapse');
		$this->t_submit = bab_translate('Valider');
		
		$this->t_level = null;
		$this->t_previousLevel = null;
		
		$this->t_layout = 'horizontal';
	
		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCss = 'treeview_css';
		$this->_templateScripts = 'treeview_scripts';
		$this->_templateCache = null;
		
		$this->t_id_separator = BAB_TREE_VIEW_ID_SEPARATOR;

		$this->_upToDate = false;
	}


	/**
	 * Defines the attributes of the treeview.
	 * @param int $attributes
	 */
	function setAttributes($attributes)
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
		$this->t_isMultiSelect = $attributes & BAB_TREE_VIEW_MULTISELECT;
	}

	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 * @return bab_TreeViewElement
	 */
	function &createElement($id, $type, $title, $description, $link)
	{
		$element =& new bab_TreeViewElement($id, $type, $title, $description, $link);
		return $element;
	}

	/**
	 * Appends $element as the last child of the element with the id $parentId.
	 * If $parentId is null, the element will appear as a first level node.
	 * @param bab_TreeViewElement &$element An element created by the method createElement.
	 * @param string $parentId The id of the parent element.
	 */
	function appendElement(&$element, $parentId)
	{
		$node =& $this->_rootNode->createNode($element, $element->_id);
		$this->_rootNode->appendChild($node, $parentId);
		$this->_upToDate = false;
	}
		
	function sort($comparisonFunctionName = 'treeViewNodeComparison')
	{
		$this->_updateTree();
		$this->_invalidateCache();
		$this->_rootNode->sortSubTree($comparisonFunctionName);
	}

		
	/**#@+
	 * Template methods.
	 * @ignore
	 */	
	function getNextElement()
	{
		if (is_null($this->_iterator)) {
			$this->_iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
			$this->_iterator->nextNode();
			$this->t_level = $this->_iterator->level();
			$this->t_previousLevel = $this->t_level - 1;
		}
		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
		if ($this->t_levelVariation < -1) {
//			$this->t_previousLayout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');
			$this->t_previousLevel--;
//			$this->t_layout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');
			return true;
		}

//		$this->t_previousLayout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');
		
		$this->t_previousLevel = $this->t_level;

//		$this->t_layout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');

		if ($node =& $this->_iterator->nextNode()) {
			$this->t_isFirstChild = $node->isFirstChild();
			$this->t_isLastChild = $node->isLastChild();
			$this->t_isMiddleChild = (!$node->isFirstChild() && !$node->isLastChild());
			$this->t_isSingleChild = ($node->isFirstChild() && $node->isLastChild());
			
				
			$this->t_level = $this->_iterator->level();
			$element =& $node->getData();
			$this->t_id = $this->_id . '.' . $element->_id;
			$this->t_type =& $element->_type;
			$this->t_title =& $element->_title;
			$this->t_description =& $element->_description;
			$this->t_link =& $element->_link;
			$this->t_info =& $element->_info;
			$this->t_nodeIcon =& $element->_icon;
			$this->_currentElement =& $element;
			reset($this->_currentElement->_actions);

			$this->t_showRightElements = ($element->_info != '')
							|| (count($this->_currentElement->_actions) > 0)
							|| (count($this->_currentElement->_checkBoxes) > 0);
			return true;
		}
		if ($this->t_level > -1) {
			$this->t_level = -1;
			return $this->getNextElement();
		}
		$this->_iterator = null;
		return false;
	}
	
	function getNextAction()
	{
		if (list(,$action) = each($this->_currentElement->_actions)) {
			$this->action_name = $action['name'];
			$this->action_caption = $action['caption'];
			$this->action_icon = $action['icon'];
			$this->action_url = $action['link'];
			$this->action_script = $action['script'];
			return true;
		}
		reset($this->_currentElement->_actions);
		return false;
	}

	function getNextCheckBox()
	{
		if (list(,$checkBox) = each($this->_currentElement->_checkBoxes)) {
			$this->checkbox_name = $checkBox['name'];
			return true;
		}
		reset($this->_currentElement->_checkBoxes);
		return false;
	}
	/**#@-*/

	/**
	 * @access private
	 */
	function _invalidateCache()
	{
		$this->_templateCache = null;
	}

	/**
	 * @access private
	 */
	function _updateTree()
	{
		$this->_upToDate = true;
	}

	/**
	 * 
	 * @return string
	 */
	function printTemplate()
	{
		if (is_null($this->_templateCache)) {
			if (!$this->_upToDate)
				$this->_updateTree();
			$this->_templateCache = bab_printTemplate($this, $this->_templateFile, $this->_templateCss);
			$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, $this->_templateSection);
			$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, $this->_templateScripts);
		}
		return $this->_templateCache;
	}
}







class bab_OrgChartElement extends bab_TreeViewElement
{
	/**#@+
	 * @access private
	 */	
	var $_members;
	/**#@-*/
	
	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 */
	function bab_OrgChartElement($id, $type, $title, $description, $link)
	{
		parent::bab_TreeViewElement($id, $type, $title, $description, $link);
		$this->_members = array();
	}

	
	function addMember($memberName, $role = '')
	{
		if (!isset($this->_members[$role])) {
			$this->_members[$role] = array();
		}
		$this->_members[$role][] = $memberName;
	}
}






class bab_OrgChart extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_verticalThreshold;

	var $t_layout;
	var $t_previousLayout;
	
	var $t_nbMembers;
	var $t_memberName;
	/**#@-*/
	
	
	/**
	 * @param string $id A unique treeview id in the page. Must begin with a letter ([A-Za-z]) and may be followed by any number of letters, digits ([0-9]), hyphens ("-"), underscores ("_"), colons (":"), and periods (".").
	 * @return bab_OrgChart
	 */
	function bab_OrgChart($id)
	{
		parent::bab_TreeView($id);
		$this->_verticalThreshold = 4;
		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'orgchart';
		$this->_templateCss = 'orgchart_css';
		$this->_templateScripts = 'orgchart_scripts';
	}

	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 * @return bab_OrgChartElement
	 */
	function &createElement($id, $type, $title, $description, $link)
	{
		$element =& new bab_OrgChartElement($id, $type, $title, $description, $link);
		return $element;
	}

	/**
	 * Defines the depth level from which the org chart branches are displayed vertically.
	 * @access public
	 */
	function setVerticalThreshold($threshold)
	{
		$this->_verticalThreshold = $threshold;		
	}

	/**#@+
	 * Template methods.
	 * @ignore
	 */	
	function getNextElement()
	{
		if (is_null($this->_iterator)) {
			$this->_iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
			$this->_iterator->nextNode();
			$this->t_level = $this->_iterator->level();
			$this->t_previousLevel = $this->t_level - 1;
		}
		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
		if ($this->t_levelVariation < -1) {
			$this->t_previousLayout = ($this->t_previousLevel >= $this->_verticalThreshold ? 'vertical' : 'horizontal');
			$this->t_previousLevel--;
			$this->t_layout = ($this->t_previousLevel >= $this->_verticalThreshold ? 'vertical' : 'horizontal');
			return true;
		}

		$this->t_previousLayout = ($this->t_previousLevel >= $this->_verticalThreshold ? 'vertical' : 'horizontal');
		
		$this->t_previousLevel = $this->t_level;

		$this->t_layout = ($this->t_previousLevel >= $this->_verticalThreshold ? 'vertical' : 'horizontal');

		if ($node =& $this->_iterator->nextNode()) {
			$this->t_isFirstChild = $node->isFirstChild();
			$this->t_isLastChild = $node->isLastChild();
			$this->t_isMiddleChild = (!$node->isFirstChild() && !$node->isLastChild());
			$this->t_isSingleChild = ($node->isFirstChild() && $node->isLastChild());
			
				
			$this->t_level = $this->_iterator->level();
			$element =& $node->getData();
			$this->t_id = $this->_id . '.' . $element->_id;
			$this->t_type =& $element->_type;
			$this->t_title =& $element->_title;
			$this->t_description =& $element->_description;
			$this->t_link =& $element->_link;
			$this->t_info =& $element->_info;
			$this->t_nodeIcon =& $element->_icon;
			$this->_currentElement =& $element;
			reset($this->_currentElement->_actions);
			reset($this->_currentElement->_members);
			$this->t_nbMembers = count($this->_currentElement->_members);
			
			$this->t_showRightElements = ($element->_info != '')
							|| (count($this->_currentElement->_actions) > 0)
							|| (count($this->_currentElement->_checkBoxes) > 0);
			return true;
		}
		if ($this->t_level > -1) {
			$this->t_level = -1;
			return $this->getNextElement();
		}
		$this->_iterator = null;
		return false;
	}

	function getNextMemberRole()
	{
		if (list($memberRole, ) = each($this->_currentElement->_members)) {
			$this->t_memberRole = $memberRole;
			$this->_members =& $this->_currentElement->_members[$memberRole];
			reset($this->_members);
			return true;
		}
		reset($this->_currentElement->_members);
		return false;
	}

	function getNextMemberName()
	{
		if (list(,$memberName) = each($this->_members)) {
			$this->t_memberName = $memberName;
			return true;
		}
		reset($this->_members);
		return false;
	}
	/**#@-*/
}








define('BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES',			 0);
define('BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS',				 1);
define('BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES',			 2);
define('BAB_ARTICLE_TREE_VIEW_HIDE_EMPTY_TOPICS',		 4);
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES',	 8);
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS',		16);
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES',		32);

class bab_ArticleTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_db;
	var $_babBody;
	var $_attributes;
	/**#@-*/


	function bab_ArticleTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCache = null;

		$this->_attributes = BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES;
	}

	/**
	 * Add article topics to the tree.
	 * @access private
	 */
	function _addTopics()
	{
		global $babBody;

		$sql = 'SELECT tt.id, tt.id_cat, tt.category FROM ' . BAB_TOPICS_TBL.' tt';
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' LEFT JOIN '.BAB_TOPICS_CATEGORIES_TBL.' tct ON tt.id_cat=tct.id WHERE tct.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		$elementType = 'topic';
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS) {
			$elementType .= ' clickable';
		}
		$topics = $this->_db->db_query($sql);
		while ($topic = $this->_db->db_fetch_array($topics)) {
			$element =& $this->createElement('topic' . BAB_TREE_VIEW_ID_SEPARATOR . $topic['id'],
											 $elementType,
											 bab_toHtml($topic['category']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/topic.png');
			$parentId = ($topic['id_cat'] === '0' ? null :
												'category' . BAB_TREE_VIEW_ID_SEPARATOR . $topic['id_cat']);
			$this->appendElement($element, $parentId);
		}
	}

	/**
	 * Add article categories to the tree.
	 * @access private
	 */
	function _addCategories()
	{
		global $babBody;

		$sql = 'SELECT id, title, id_parent FROM ' . BAB_TOPICS_CATEGORIES_TBL;
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' WHERE id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		$elementType = 'category';
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES) {
			$elementType .= ' clickable';
		}
		$categories = $this->_db->db_query($sql);
		while ($category = $this->_db->db_fetch_array($categories)) {
			$element =& $this->createElement('category' . BAB_TREE_VIEW_ID_SEPARATOR . $category['id'],
											 $elementType,
											 bab_toHtml($category['title']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');
			$parentId = ($category['id_parent'] === '0' ? null :
											'category' . BAB_TREE_VIEW_ID_SEPARATOR . $category['id_parent']);
			$this->appendElement($element, $parentId);
		}
	}

	/**
	 * Add articles to the tree.
	 * @access private
	 */
	function _addArticles()
	{
		global $babBody;

		$sql = 'SELECT at.id, at.title, at.id_topic FROM ' . BAB_ARTICLES_TBL.' at';
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' LEFT JOIN '.BAB_TOPICS_TBL.' tt ON at.id_topic=tt.id LEFT JOIN '.BAB_TOPICS_CATEGORIES_TBL.' tct ON tt.id_cat=tct.id WHERE id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		$elementType = 'article';
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES) {
			$elementType .= ' clickable';
		}
		$rs = $this->_db->db_query($sql);
		while ($article = $this->_db->db_fetch_array($rs)) {
			$element =& $this->createElement('article' . BAB_TREE_VIEW_ID_SEPARATOR . $article['id'],
											 $elementType,
											 bab_toHtml($article['title']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/article.png');
			$this->appendElement($element, 'topic' . BAB_TREE_VIEW_ID_SEPARATOR . $article['id_topic']);
		}
	}

	function addStatistics($start, $end)
	{
		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
		$iterator->nextNode();
		while ($node = $iterator->nextNode())
			(!is_null($node)) && $node->_data->setInfo('0');

		$sql = 'SELECT st_article_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_ARTICLES_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= \'' . $start . '\'';
			$end && $where[] = 'st_date <= \'' . $end . ' 23:59:59\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		
		$articles = $this->_db->db_query($sql);
		while ($article = $this->_db->db_fetch_array($articles)) {
			$node =& $this->_rootNode->getNodeById('article' . BAB_TREE_VIEW_ID_SEPARATOR . $article['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($article['hits']);
				$node =& $node->parentNode();
				while (!is_null($node)) {
					$element =& $node->getData();
					if ($element)
						$element->setInfo((int)$element->_info + (int)$article['hits']);
					$node =& $node->parentNode();			
				}
			}
		}
	}

	/**
	 * @access private
	 */
	function _updateTree()
	{
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS
			|| $this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES)
			$this->_addTopics();

		$this->_addCategories();

		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES)
			$this->_addArticles();
		parent::_updateTree();
	}
}






define('BAB_FILE_TREE_VIEW_SHOW_COLLECTIVE_DIRECTORIES',		 0);
define('BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES',				 1);
define('BAB_FILE_TREE_VIEW_SHOW_FILES',							 2);
define('BAB_FILE_TREE_VIEW_SHOW_PERSONAL_DIRECTORIES',		 	 4);
define('BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES',	 8);
define('BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES',	 		16);
define('BAB_FILE_TREE_VIEW_SELECTABLE_FILES',					32);


class bab_FileTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_db;
	var $_babBody;
	var $_attributes;
	var $_gr;
	
	var $_adminView;
	/**#@-*/


	function bab_FileTreeView($id, $adminView = true)
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];

		$this->_attributes = BAB_FILE_TREE_VIEW_SHOW_FILES;
		
		$this->_adminView = $adminView;
	}

	/**
	 * Add collective directories.
	 * @access private
	 */
	function _addCollectiveDirectories()
	{
		global $babBody;

		if (!is_array($babBody->aclfm)) {
			return;
		}
		$aclFlip = array_flip($babBody->aclfm['id']);
		$directoriesDownloadAcl = array();
		$directoriesUploadAcl = array();
		$directoriesUpdateAcl = array();
		$directoriesManageAcl = array();
		$directoriesHide = array();
		foreach ($babBody->aclfm['id'] as $directoryId) {
			$directoriesDownloadAcl[$directoryId] = $babBody->aclfm['down'][$aclFlip[$directoryId]];
			$directoriesManageAcl[$directoryId] = $babBody->aclfm['ma'][$aclFlip[$directoryId]];
		}

		$sql = 'SELECT folder.id, folder.folder FROM ' . BAB_FM_FOLDERS_TBL. ' folder';
		if ($babBody->currentAdmGroup != 0)	{
			$sql .= ' WHERE folder.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}
		$sql .= ' ORDER BY folder.folder';

		$elementType = 'folder';
		if ($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES) {
			$elementType .= ' clickable';
		}
		$folders = $this->_db->db_query($sql);
		
		$element =& $this->createElement('cd',
										 'foldercategory',
										 bab_translate("Collective folders"),
										 '',
										 '');
		$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/collective_folder.png');
		$this->appendElement($element, null);
		
		while ($folder = $this->_db->db_fetch_array($folders)) {
			if ($this->_adminView
				|| isset($directoriesDownloadAcl[$folder['id']]) && $directoriesDownloadAcl[$folder['id']]
				|| isset($directoriesManageAcl[$folder['id']]) && $directoriesManageAcl[$folder['id']]) {
				$element =& $this->createElement('d' . BAB_TREE_VIEW_ID_SEPARATOR . $folder['id'],
												 $elementType,
												 bab_toHtml($folder['folder']),
												 '',
												 '');
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
				if (($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES)
					&& ($this->_attributes & BAB_TREE_VIEW_MULTISELECT)) {
					$element->addCheckBox('select');
				}
				$this->appendElement($element, 'cd');
			}
		}
	}


	/**
	 * Add files and subdirectories.
	 * @access private
	 */
	function _addFiles()
	{
		global $babBody;

		$sql = 'SELECT file.id, file.path, file.name, file.id_owner, file.bgroup FROM ' . BAB_FILES_TBL.' file';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FM_FOLDERS_TBL.' folder ON file.id_owner=folder.id';
			$sql .= ' WHERE file.bgroup=\'Y\' AND folder.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		} elseif ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_PERSONAL_DIRECTORIES) {
			$sql .= ' WHERE (file.bgroup=\'Y\' OR (file.bgroup=\'N\' AND file.id_owner=\'' . $GLOBALS['BAB_SESS_USERID'] . '\'))';
		} else {
			$sql .= ' WHERE file.bgroup=\'Y\'';
		}
		$sql .= ' AND file.state<>\'D\'';
		$sql .= ' ORDER BY file.name';
				
		$directoryType = 'folder';
		if ($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES) {
			$directoryType .= ' clickable';
		}
		$personalFileType = 'pfile';
		$groupFileType = 'gfile';
		if ($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_FILES) {
			$personalFileType .= ' clickable';
			$groupFileType .= ' clickable';
		}
		$files = $this->_db->db_query($sql);
		while ($file = $this->_db->db_fetch_array($files)) {
			
			$fullpath = bab_getUploadFullPath($file['bgroup'], $file['id_owner']) . $file['path'] . '/' . $file['name'];
			if (!is_file($fullpath))
				continue;

			$subdirs = explode('/', $file['path']);
			if ($file['bgroup'] == 'Y') {
				$fileId = 'g' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id'];
				$parentId = 'd' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id_owner'];
				$fileType =& $groupFileType;
			} else {
				$fileId = 'p' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id'];
				$fileType =& $personalFileType;
				$parentId = 'pd' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id_owner'];
				if (is_null($this->_rootNode->getNodeById($parentId))) {
					$element =& $this->createElement($parentId,
													 'foldercategory',
													 bab_translate("Personal folders"),
													 '',
													 '');
					$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/personal_folder.png');
					$this->appendElement($element, null);
				}
			}

			foreach ($subdirs as $subdir) {
				if (trim($subdir) !== '') {
					if (is_null($this->_rootNode->getNodeById($parentId . ':' . $subdir))) {
						$element =& $this->createElement($parentId . ':' . $subdir,
														 $directoryType,
														 $subdir,
														 '',
														 '');
						$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
						if (($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES)
							&& ($this->_attributes & BAB_TREE_VIEW_MULTISELECT)) {
							$element->addCheckBox('select');
						}
						$this->appendElement($element, $parentId);
					}
					$parentId .= ':' . $subdir;
				}
			}
			if ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_FILES) {
				$element =& $this->createElement($fileId,
												 $fileType,
												 $file['name'],
												 '',
												 '');
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/file.png');
				if (($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_FILES)
					&& ($this->_attributes & BAB_TREE_VIEW_MULTISELECT)) {
					$element->addCheckBox('select');
				}
				$this->appendElement($element, $parentId);
			}
		}
	}

	function addStatistics($start, $end)
	{
		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
		$iterator->nextNode();
		while ($node = $iterator->nextNode())
			(!is_null($node)) && $node->_data->setInfo('0');

		$sql = 'SELECT st_fmfile_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_FMFILES_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= \'' . $start . '\'';
			$end && $where[] = 'st_date <= \'' . $end . ' 23:59:59\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';
		
		$files = $this->_db->db_query($sql);
		while ($file = $this->_db->db_fetch_array($files)) {
			$node =& $this->_rootNode->getNodeById('f' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($file['hits']);
				$node =& $node->parentNode();
				while (!is_null($node)) {
					$element =& $node->getData();
					if ($element)
						$element->setInfo((int)$element->_info + (int)$file['hits']);
					$node =& $node->parentNode();			
				}
			}
		}
	}

	/**
	 * @access private
	 */
	function _updateTree()
	{
		$this->_addCollectiveDirectories();
		if ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_FILES
			|| $this->_attributes & BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES) {
			$this->_addFiles();
		}
		parent::_updateTree();
	}
}




define('BAB_FORUM_TREE_VIEW_SHOW_FORUMS',				 0);
define('BAB_FORUM_TREE_VIEW_SHOW_THREADS',				 1);
define('BAB_FORUM_TREE_VIEW_SHOW_POSTS',				 2);
define('BAB_FORUM_TREE_VIEW_SELECTABLE_FORUMS',	 		 4);
define('BAB_FORUM_TREE_VIEW_SELECTABLE_THREADS',	 	 8);
define('BAB_FORUM_TREE_VIEW_SELECTABLE_POSTS',			16);


class bab_ForumTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_db;
	var $_babBody;
	var $_attributes;
	/**#@-*/
	

	function bab_ForumTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];

		$this->_attributes = BAB_FORUM_TREE_VIEW_SHOW_POSTS;
	}

	/**
	 * Add forums to the tree.
	 * @access private
	 */
	function _addForums()
	{
		global $babBody;

		$sql = 'SELECT id, name FROM ' . BAB_FORUMS_TBL;
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' WHERE id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}
		$sql .= ' ORDER BY ordering';
		
		$forumType = 'forum';
		if ($this->_attributes & BAB_FORUM_TREE_VIEW_SELECTABLE_FORUMS) {
			$forumType .= ' clickable';
		}
		$rs = $this->_db->db_query($sql);
		while ($forum = $this->_db->db_fetch_array($rs)) {
			$element =& $this->createElement('forum' . BAB_TREE_VIEW_ID_SEPARATOR . $forum['id'],
											 $forumType,
											 bab_translate('Forum: ') . bab_toHtml($forum['name']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/forum.png');
			$this->appendElement($element, null);
		}
	}


	/**
	 * Add threads and posts to the tree.
	 * @access private
	 */
	function _addThreads()
	{
		global $babBody;

		$sql = 'SELECT tt.id, tt.forum FROM ' . BAB_THREADS_TBL. ' tt';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FORUMS_TBL.' ft ON tt.forum=ft.id WHERE ft.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}
		$sql .= ' ORDER BY tt.date';
		
		$threadType = 'thread';
		if ($this->_attributes & BAB_FORUM_TREE_VIEW_SELECTABLE_THREADS) {
			$threadType .= ' clickable';
		}
		$postType = 'post';
		if ($this->_attributes & BAB_FORUM_TREE_VIEW_SELECTABLE_POSTS) {
			$postType .= ' clickable';
		}
		$threads = $this->_db->db_query($sql);
		while ($thread = $this->_db->db_fetch_array($threads)) {
			$sql = 'SELECT id, subject, id_parent FROM ' . BAB_POSTS_TBL
				. ' WHERE id_thread = ' . $thread['id']
				. ' ORDER BY ' . BAB_POSTS_TBL . '.date';
			
			$posts = $this->_db->db_query($sql);
			$firstPost = true;
			while ($post = $this->_db->db_fetch_array($posts)) {
				if ($post['id_parent'] === '0') {
					$parentId = 'forum' . BAB_TREE_VIEW_ID_SEPARATOR . $thread['forum'];
					$elementType = $threadType;
					$iconUrl = $GLOBALS['babSkinPath'] . 'images/nodetypes/thread.png';
				} else {
					$parentId = 'post' . BAB_TREE_VIEW_ID_SEPARATOR . $post['id_parent'];
					$elementType = $postType;
					$iconUrl = $GLOBALS['babSkinPath'] . 'images/nodetypes/post.png';
				}
				if (($this->_attributes & BAB_FORUM_TREE_VIEW_SHOW_POSTS) && $post['id_parent'] !== '0'
				    || $post['id_parent'] === '0') {
					$element =& $this->createElement('post' . BAB_TREE_VIEW_ID_SEPARATOR . $post['id'],
													 $elementType,
													 bab_toHtml($post['subject']),
													 '',
													 '');
					$element->setIcon($iconUrl);
					$this->appendElement($element, $parentId);
				}
			}
		}
	}

	function addStatistics($start, $end)
	{
		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
		$iterator->nextNode();
		while ($node = $iterator->nextNode())
			(!is_null($node)) && $node->_data->setInfo('0');

		$sql = 'SELECT st_post_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_POSTS_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= \'' . $start . '\'';
			$end && $where[] = 'st_date <= \'' . $end . ' 23:59:59\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';
		
		$posts = $this->_db->db_query($sql);
		while ($post = $this->_db->db_fetch_array($posts)) {
			$node =& $this->_rootNode->getNodeById('post' . BAB_TREE_VIEW_ID_SEPARATOR . $post['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($post['hits']);
			}
		}
	}

	/**
	 * @access private
	 */
	function _updateTree()
	{
		$this->_addForums();
		if ($this->_attributes & BAB_FORUM_TREE_VIEW_SHOW_THREADS
			|| $this->_attributes & BAB_FORUM_TREE_VIEW_SHOW_POSTS) {
			$this->_addThreads();
		}
		parent::_updateTree();
	}
}


/**
 * Display FAQ categories.
 */
define('BAB_FAQ_TREE_VIEW_SHOW_CATEGORIES',				 0);
/**
 * Display FAQ sub-categories.
 */
define('BAB_FAQ_TREE_VIEW_SHOW_SUB_CATEGORIES',			 1);
/**
 * Display FAQ questions-answers.
 */
define('BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS',				 2);
/**
 * Make FAQ categories selectable.
 */
define('BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES',		 4);
/**
 * Make FAQ sub-categories selectable.
 */
define('BAB_FAQ_TREE_VIEW_SELECTABLE_SUB_CATEGORIES',	 8);
/**
 * Make FAQ questions-answers selectable.
 */
define('BAB_FAQ_TREE_VIEW_SELECTABLE_QUESTIONS',		16);


class bab_FaqTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_db;
	var $_babBody;
	var $_attributes;
	var $_categories;
	/**#@-*/

	function bab_FaqTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
	
		$this->_attributes = BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS;
	}

	/**
	 * Add FAQ categories to the tree.
	 * @access private
	 */
	function _addCategories()
	{
		global $babBody;

		$sql = 'SELECT id, category FROM ' . BAB_FAQCAT_TBL;
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' WHERE id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}

		$faqcategoryType = 'faqcategory';
		if ($this->_attributes & BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES) {
			$faqcategoryType .= ' clickable';
		}		
		$categories = $this->_db->db_query($sql);
		while ($category = $this->_db->db_fetch_array($categories)) {
			$element =& $this->createElement('category' . BAB_TREE_VIEW_ID_SEPARATOR . $category['id'],
											 $faqcategoryType,
											 bab_translate('Category: ') . bab_toHtml($category['category']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			$this->appendElement($element, null);
		}

		$sql = 'SELECT ftt.id_parent, fst.* FROM ' . BAB_FAQ_TREES_TBL . ' ftt ,' . BAB_FAQ_SUBCAT_TBL
			. ' fst WHERE ftt.id = fst.id_node AND ftt.id_parent = 0'
			. ' ORDER BY ftt.id';
		
		$subCategories = $this->_db->db_query($sql);
		while ($subCategory = $this->_db->db_fetch_array($subCategories)) {
			$this->_categories[$subCategory['id']] = $subCategory['id_cat'];
		}
	}


	/**
	 * Add FAQ sub-categories to the tree.
	 * @access private
	 */
	function _addSubCategories()
	{
		global $babBody;

		$sql = 'SELECT ftt.id_parent, fst.* FROM ' . BAB_FAQ_TREES_TBL . ' ftt ,' . BAB_FAQ_SUBCAT_TBL.' fst';

		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FAQCAT_TBL.' ft ON ft.id=fst.id_cat';
		}
		$sql .= ' WHERE';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' id_dgowner=\''.$babBody->currentAdmGroup.'\' AND';
		}
		$sql .= ' ftt.id = fst.id_node AND ftt.id_parent <> 0';
		$sql .= ' ORDER BY ftt.id';
		
		$faqsubcategoryType = 'faqsubcategory';
		if ($this->_attributes & BAB_FAQ_TREE_VIEW_SELECTABLE_SUB_CATEGORIES) {
			$faqsubcategoryType .= ' clickable';
		}		
		$subCategories = $this->_db->db_query($sql);
		while ($subCategory = $this->_db->db_fetch_array($subCategories)) {
			$element =& $this->createElement('subcat' . BAB_TREE_VIEW_ID_SEPARATOR . $subCategory['id'],
											 $faqsubcategoryType,
											 bab_toHtml($subCategory['name']),
											 '',
											 '');
			$parentId = isset($this->_categories[$subCategory['id_parent']])
								? 'category' . BAB_TREE_VIEW_ID_SEPARATOR . $this->_categories[$subCategory['id_parent']]
								: 'subcat' . BAB_TREE_VIEW_ID_SEPARATOR . $subCategory['id_parent'];
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			$this->appendElement($element, $parentId);
		}
	}

	/**
	 * Add FAQ questions-answers to the tree.
	 * @access private
	 */
	function _addQuestions()
	{
		global $babBody;

		$sql = 'SELECT fqt.id, fqt.question, fqt.id_subcat FROM ' . BAB_FAQQR_TBL.' fqt';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FAQCAT_TBL.' fct ON fqt.idcat=fct.id WHERE fct.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}

		$questionType = 'faqquestion';
		if ($this->_attributes & BAB_FAQ_TREE_VIEW_SELECTABLE_QUESTIONS) {
			$questionType .= ' clickable';
		}
		$questions = $this->_db->db_query($sql);
		while ($question = $this->_db->db_fetch_array($questions)) {
			$element =& $this->createElement('question' . BAB_TREE_VIEW_ID_SEPARATOR . $question['id'],
											 $questionType,
											 bab_toHtml($question['question']),
											 '',
											 '');
			$parentId = isset($this->_categories[$question['id_subcat']])
								? 'category' . BAB_TREE_VIEW_ID_SEPARATOR . $this->_categories[$question['id_subcat']]
								: 'subcat' . BAB_TREE_VIEW_ID_SEPARATOR . $question['id_subcat'];
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/faq.png');
			$this->appendElement($element, $parentId);
		}
	}


	function addStatistics($start, $end)
	{
		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
		$iterator->nextNode();
		while ($node = $iterator->nextNode())
			(!is_null($node)) && $node->_data->setInfo('0');		
		
		$sql = 'SELECT st_faqqr_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_FAQQRS_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= \'' . $start . '\'';
			$end && $where[] = 'st_date <= \'' . $end . ' 23:59:59\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		$faqs = $this->_db->db_query($sql);
		while ($faq = $this->_db->db_fetch_array($faqs)) {
			$node =& $this->_rootNode->getNodeById('question' . BAB_TREE_VIEW_ID_SEPARATOR . $faq['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($faq['hits']);
				$node =& $node->parentNode();
				while (!is_null($node)) {
					$element =& $node->getData();
					if ($element)
						$element->setInfo((int)$element->_info + (int)$faq['hits']);
					$node =& $node->parentNode();			
				}
			}
		}
	}

	/**
	 * @access private
	 */
	function _updateTree()
	{
		$this->_categories = array();
		$this->_addCategories();
		if (($this->_attributes & BAB_FAQ_TREE_VIEW_SHOW_SUB_CATEGORIES)
			|| ($this->_attributes & BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS)) {
			$this->_addSubCategories();
		}
		if ($this->_attributes & BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS) {
			$this->_addQuestions();
		}
		parent::_updateTree();
	}
}



class bab_OvidentiaOrgChart extends bab_OrgChart 
{
	var $_db;
	var $_babBody;
	var $_orgChartId; // Ovidentia org chart id

	function bab_OvidentiaOrgChart($id, $orgChartId)
	{
		parent::bab_OrgChart($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
		$this->_orgChartId = $orgChartId;
	
	}

	function _addEntities($startNode)
	{
		$entityType = 'entity';

		$sql = 'SELECT * ';
		$sql .= ' FROM ' . BAB_OC_TREES_TBL . ' AS trees';
		$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities ON entities.id_node = trees.id';
		$sql .= ' WHERE trees.id_user = ' . $this->_db->quote($this->_orgChartId);
 
		$entities = $this->_db->db_query($sql);
		while ($entity = $this->_db->db_fetch_array($entities)) {
			$element =& $this->createElement('entity' . BAB_TREE_VIEW_ID_SEPARATOR . $entity['id'],
											 $entityType,
											 bab_toHtml($entity['name']),
											 '',
											 '');
			$element->setInfo();
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			$this->appendElement($element, $entity['id_parent'] ? 'entity' . BAB_TREE_VIEW_ID_SEPARATOR .  $entity['id_parent'] : null);
			
		}
	}
	/**
	 * @access private
	 */
	function _updateTree()
	{
		$this->_addEntities();
		parent::_updateTree();
	}
}


function bab_tree_test()
{
	global $babBody;
	
	
	
	// Example of custom tree view.
	//------------------------------
/*
	$orgChart = new bab_OrgChart('my_library');

	$element =& $orgChart->createElement('mayor', 'entity', 'Laurent LAFON', 'My favorite cookbook', 'http://localhost/mycookbook/index.php');
	$element->setInfo('Maire');
	$element->setIcon($GLOBALS['babSkinPath'] . 'images/maire.jpeg');
	$element->addMember('Catherine GOMEZ', 'Cabinet et Direction');
	$element->addMember('Claire DEWEERTD-PHLIX', 'Cabinet et Direction');
	$element->addMember('Dominique MOYSE', 'Cabinet et Direction');
	$element->addMember('Frédéric PARRINELLO', 'Cabinet et Direction');
	$element->addMember('Gildas LECOQ', 'Cabinet et Direction');
	$element->addMember('Sophie MARIN', 'Assistante');
	$orgChart->appendElement($element, null);

	$element =& $orgChart->createElement('book1_1', 'entity', 'Chapter 1', '', 'http://localhost/mycookbook/chapter1.php');
	$element->setIcon($GLOBALS['babSkinPath'] . 'images/dg1.jpeg');
	$orgChart->appendElement($element, 'mayor');

	$element =& $orgChart->createElement('book1_2', 'entity', 'Chapter 2', '', 'http://localhost/mycookbook/chapter2.php');
	$element->addAction('move_down', 'Move down', '', 'move_down.php', '');
	$element->addAction('delete', 'Delete', '', 'delete.php', '');
	$element->setInfo('Info');
	$orgChart->appendElement($element, 'mayor');

	$element =& $orgChart->createElement('1.1', 'entity', 'Paragraphe 1.1', 'Description', 'lien');
	$orgChart->appendElement($element, 'book1_1');
	$element->addAction('add', 'Add', '', 'add.php', '');

	$element =& $orgChart->createElement('1.2', 'entity', 'Paragraphe 1.2', 'Description', 'lien');
	$element->setInfo('Info');
	$orgChart->appendElement($element, 'book1_1');

	$element =& $orgChart->createElement('1.3', 'entity', 'Paragraphe 1.3', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$orgChart->appendElement($element, 'book1_1');

	$element =& $orgChart->createElement('1.3.1', 'entity', 'Sous-paragraphe 1.3.1', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$orgChart->appendElement($element, '1.3');

	$element =& $orgChart->createElement('1.3.2', 'entity', 'Sous-paragraphe 1.3.2', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$orgChart->appendElement($element, '1.3');

	$element =& $orgChart->createElement('1.4', 'entity', 'Paragraphe 1.4', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$element->setIcon($GLOBALS['babSkinPath'] . 'images/dg1.jpeg');
	$orgChart->appendElement($element, 'book1_1');

	$element =& $orgChart->createElement('b', 'entity', 'b', 'Description', 'lien');
	$element->setInfo('Info');
	$element->addMember('Jean TOTO');
	$element->addMember('Marcel TURLUTUTU');
	$element->addAction('move_down', 'Move down', $GLOBALS['babSkinPath'] . 'images/Puces/members.png', '', 'alert(\'hello\');');
	$element->addAction('from_here', 'Show from here', $GLOBALS['babSkinPath'] . 'images/Puces/go-down.png', '', '');
	$orgChart->appendElement($element, 'book1_2');

	$element =& $orgChart->createElement('a', 'entity', 'a', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$element->addAction('move_down', 'Move down', $GLOBALS['babSkinPath'] . 'images/Puces/members.png', '', '');
	$orgChart->appendElement($element, 'book1_2');

	$element =& $orgChart->createElement('x', 'entity', 'x', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$element->addAction('move_down', 'Move down', $GLOBALS['babSkinPath'] . 'images/Puces/members.png', '', '');
	$orgChart->appendElement($element, 'book1_2');

	$element =& $orgChart->createElement('s', 'entity', 's', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$orgChart->appendElement($element, 'book1_2');

	$element =& $orgChart->createElement('c', 'entity', 'c', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$orgChart->appendElement($element, 'book1_2');

	$element =& $orgChart->createElement('book1_3', 'entity', 'Chapitre 3', 'Description', 'lien');
	$orgChart->appendElement($element, 'mayor');
	$element->addMember('Albert TRUCMUCHE de la PALOMBIERE', 'Toto');
	$element->addMember('Simone MACHIN', 'Toto');
	
	$element =& $orgChart->createElement('3.1', 'entity', 'Paragraphe 3.1', 'Description', 'lien');
	$orgChart->appendElement($element, 'book1_3');

	
	$element =& $orgChart->createElement('dg1', 'entity', 'Direction générale des services', '', 'lien');
	$element->setInfo('Patrice BÉCU');
	$orgChart->appendElement($element, 'mayor');
	$element->setIcon($GLOBALS['babSkinPath'] . 'images/dg1.jpeg');
*/
/*
	$element->addMember('Patrice BÉCU', 'Directeur générale des services');
	$element->addMember('Catherine GOMEZ', 'Directeurs');
	$element->addMember('Claire DEWEERTD-PHLIX', 'Directeurs');
	$element->addMember('Isabelle CHASSAGNARD', 'Directeurs');
	$element->addMember('Isabelle ETLIN', 'Directeurs');
	$element->addMember('Joël DEGOUY', 'Directeurs');
	$element->addMember('Françoise CHAMPAGNAC', 'Conseiller en gestion');
	$element->addMember('Pierre BIGNON', 'Secrétariat CM et CME');
	$element->addMember('Annabelle MARIEAU', 'Secrétariat D.G.S.');
*/	
//	$iterator = $treeView->_rootNode->createNodeIterator($treeView->_rootNode);

//	while ($node =& $iterator->nextNode()) {
//		$node->sortChildNodes();
//	}

	$orgChart = new bab_OvidentiaOrgChart('my_library', 1);
	
	$babBody->babecho('<h2>Example of a simple custom tree (<code>bab_TreeView</code>)</h2>');
	$babBody->babecho($orgChart->printTemplate());
	

	
	$treeView = new bab_FileTreeView('file', 'N', '0');
//	$end = microtime_float(true);
//	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
//	print_r('new bab_FileTreeView : ' . ($end - $start));
//	echo "</div></pre>\n";

//	$start = microtime_float(true);
//	$treeView->addStatistics('2000-01-01 00:00', '2007-01-01 00:00');
//	$end = microtime_float(true);
//	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
//	print_r('addStatistics : ' . ($end - $start));
//	echo "</div></pre>\n";

//	$start = microtime_float(true);
	$treeView->sort();
//	$end = microtime_float(true);
//	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
//	print_r('sort : ' . ($end - $start));
//	echo "</div></pre>\n";

//	$babBody->babecho('<h2>Example of file tree (<code>bab_FileTreeView</code>)</h2>');
	
//	$babBody->babecho($treeView->printTemplate());
	
	
/*
	

	// Example of faq tree view.
	//--------------------------
	$treeView = new bab_FaqTreeView('faq');
	$treeView->addStatistics('0000-00-00 00:00', '2007-01-01 00:00');
	$treeView->sort();
	$babBody->babecho('<h2>Example of faq tree (<code>bab_FaqTreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());

	// Example of forum tree view.
	//----------------------------
	$treeView = new bab_ForumTreeView('forum');
	$treeView->addStatistics('0000-00-00 00:00', '2007-01-01 00:00');
	$treeView->sort();
	$babBody->babecho('<h2>Example of forum tree (<code>bab_ForumTreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());

	// Example of article tree view.
	//------------------------------
	$treeView = new bab_ArticleTreeView('article');
	$treeView->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES);
	$treeView->addStatistics('0000-00-00 00:00', '2050-01-01 00:00');
	$treeView->sort();
	$babBody->babecho('<h2>Example of article tree (<code>bab_ArticleTreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());


	// Example of file tree view.
	//---------------------------
//	$start = microtime_float(true);
	$treeView = new bab_FileTreeView('file', 'N', '0');
//	$end = microtime_float(true);
//	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
//	print_r('new bab_FileTreeView : ' . ($end - $start));
//	echo "</div></pre>\n";

//	$start = microtime_float(true);
	$treeView->addStatistics('2000-01-01 00:00', '2007-01-01 00:00');
//	$end = microtime_float(true);
//	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
//	print_r('addStatistics : ' . ($end - $start));
//	echo "</div></pre>\n";

//	$start = microtime_float(true);
	$treeView->sort();
//	$end = microtime_float(true);
//	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
//	print_r('sort : ' . ($end - $start));
//	echo "</div></pre>\n";

	$babBody->babecho('<h2>Example of file tree (<code>bab_FileTreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());
*/
}











class bab_GroupTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_db;

	/**#@-*/

	function bab_GroupTreeView($id)
	{
		parent::bab_TreeView($id);
		$this->t_isMultiSelect = true;
		$this->_db =& $GLOBALS['babDB'];
	}

	/**
	 * @access private
	 */
	function _addGroups()
	{

		include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

		$tree = new bab_grptree();
		$groups = $tree->getGroups(BAB_ALLUSERS_GROUP, '');

		foreach ($groups as $group) {
			$element =& $this->createElement('group' . BAB_TREE_VIEW_ID_SEPARATOR . $group['id'],
											 'group',
											 bab_toHtml(bab_translate($group['name'])),
											 '',
											 '');
			$element->addCheckBox('select');

			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			$parentId = (BAB_REGISTERED_GROUP === (int) $group['id'] ? NULL : 'group' . BAB_TREE_VIEW_ID_SEPARATOR . $group['id_parent']);
			$this->appendElement($element, $parentId);
		}

	}


	/**
	 * @access private
	 */
	function _updateTree()
	{
		$this->_categories = array();
		$this->_addGroups();
		
		
		parent::_updateTree();
	}
}



?>