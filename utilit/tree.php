<?php



class bab_NodeList
{
	var $_firstNode;

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
	 * @return bab_InNode
	 */
	function &item($n)
	{
		$i = 0;
		$node =& $this->_firstNode;
		while (!is_null($node) && $i < $n) {
			$node =& $node->nextSibling();
			$i++;
		}
		return ($i === $n) ? $node : bab_InNode::NULL_NODE();
	}
}


$GLOBALS['BAB_NODE_NULL'] = null;


class bab_InNode
{

	/**
	 * Returns a reference to null.
	 * @return &null
	 */
	function &NULL_NODE()
	{
		return $GLOBALS['BAB_NODE_NULL'];
	}

	/**
	 * Returns the id of the node.
	 * @return int|string
	 */
	function getId()
	{
	}

	/**
	 * Returns the guid of the node.
	 * @return int|string
	 */
	function getGuid()
	{
	}
	
	/**
	 * Returns the previous sibling of the node.
	 * @return &bab_Node
	 */
	function &previousSibling()
	{
	}

	/**
	 * Returns the next sibling of the node.
	 * @return &bab_Node
	 */
	function &nextSibling()
	{
	}

	/**
	 * Returns the parent of the node.
	 * @return &bab_Node
	 */
	function &parentNode()
	{
	}

	/**
	 * Returns the first child of the node.
	 * @return &bab_Node
	 */
	function &firstChild()
	{
	}

	/**
	 * Returns the last child of the node.
	 * @return &bab_Node
	 */
	function &lastChild()
	{
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
}


class bab_Node extends bab_InNode
{
	var $_guid;
	var $_id;
	var $_data;
	var $_nextSibling;
	var $_previousSibling;
	var $_parent;
	var $_firstChild;
	var $_lastChild;
	var $_tree;

	/**
	 * Constructor
	 */
	function bab_Node(&$tree, $id = null)
	{
		$this->_guid = md5(uniqid(rand(), true));
		$this->_id = $id;
		$this->_data = null;
		$this->_nextSibling =& bab_InNode::NULL_NODE();
		$this->_previousSibling =& bab_InNode::NULL_NODE();
		$this->_parent =& bab_InNode::NULL_NODE();
		$this->_firstChild =& bab_InNode::NULL_NODE();
		$this->_lastChild =& bab_InNode::NULL_NODE();
		$this->_tree =& $tree;
	}

	function setData(&$data)
	{
		$this->_data =& $data;		
	}

	function &getData()
	{
		return $this->_data;		
	}

	function getId()
	{
		return $this->_id;
	}

	function getGuid()
	{
		return $this->_guid;
	}
	
	function &previousSibling()
	{
		return $this->_previousSibling;
	}

	function &nextSibling()
	{
		return $this->_nextSibling;
	}

	function &parentNode()
	{
		return $this->_parent;
	}

	function &firstChild()
	{
		return $this->_firstChild;
	}

	function &lastChild()
	{
		return $this->_lastChild;		
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

	function removeChild(&$node)
	{
		$node->_parent =& bab_InNode::NULL_NODE();

		if ($node->isFirstChild()) {
			if ($node->isLastChild()) {
				$this->_firstChild =& bab_InNode::NULL_NODE();
				$this->_lastChild =& bab_InNode::NULL_NODE();
			} else {
				$this->_firstChild =& $node->_nextSibling;
				$this->_firstChild->_previousSibling =& bab_InNode::NULL_NODE();
				$node->_nextSibling =& bab_InNode::NULL_NODE();
			}
		} else {
			if ($node->isLastChild()) {
				$this->_lastChild =& $node->_previousSibling;
				$oChildNode->_previousSibling->_nextSibling =& bab_InNode::NULL_NODE();
				$oChildNode->_previousSibling =& bab_InNode::NULL_NODE();
			} else {
				$node->_previousSibling->_nextSibling =& $node->_nextSibling;
				$node->_nextSibling->_previousSibling =& $node->_previousSibling;
				$node->_previousSibling =& bab_InNode::NULL_NODE();
				$node->_nextSibling =& bab_InNode::NULL_NODE();
			}			
		}
        return true;
	}


	function replaceChild(&$newNode, &$oldNode) // TODO Finish
	{
		$newNode->_parent =& $this;

		if ($node->isFirstChild()) {
			if ($node->isLastChild()) {
				$this->_firstChild =& $newNode;
				$this->_lastChild =& $newNode;
			} else {
				$this->m_oFirstChild =& $newNode;
				$newNode->_nextSibling =& $oldNode->_nextSibling;
				$newNode->_nextSibling->_previousSibling =& $newNode;
			}
		} else {
			if ($node->isLastChild()) {
				$this->m_oLastChild =& $newNode;
				$oldNode->_previousSibling->_nextSibling =& $newNode;
				$newNode->_previousSibling =& $oldNode->_previousSibling;
			} else {
				$newNode->_previousSibling =& $oldNode->_previousSibling;
				$oldNode->_previousSibling->_nextSibling =& $newNode;
				$newNode->_nextSibling =& $oldNode->_nextSibling;
				$oldNode->_nextSibling->_previousSibling =& $newNode;
			}
		}
		$oldNode->_nextSibling =& bab_InNode::NULL_NODE();
		$oldNode->_previousSibling =& bab_InNode::NULL_NODE();
		$oldNode->_parent =& bab_InNode::NULL_NODE();
		$oldNode->_firstChild =& bab_InNode::NULL_NODE();
		$oldNode->_lastChild =& bab_InNode::NULL_NODE();

		return true;
	}
	
	function swapConsecutiveNodes(&$firstNode)
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
		for ($end = count($nodes) - 1; $end > 0; $end--) {
			for ($current = 0; $current < $end; $current++) {
				$currentElement =& $nodes[$current]->getData();
				$nextElement =& $nodes[$current + 1]->getData();
				if (call_user_func(array($currentElement, 'compare'), $nextElement) > 0) {
//				if ($comparisonFunction($nodes[$current], $nodes[$current + 1]) > 0) {
					bab_Node::swapConsecutiveNodes($nodes[$current]);
					$temp =& $nodes[$current];
					$nodes[$current] =& $nodes[$current + 1];
					$nodes[$current + 1] =& $temp;
				}
			}
		}
	}
	
	function sortSubTree(/*$comparisonFunction = 'bab_Node_defaultNodeComparison'*/)
	{
		if ($this->hasChildNodes()) {
			$node =& $this->firstChild();
			while (!is_null($node)) {
				$node->sortSubTree(/*$comparisonFunction*/);
				$node =& $node->_nextSibling;				
			}
			$this->sortChildNodes(/*$comparisonFunction*/);
		}
	}
}

/*
function bab_Node_defaultNodeComparison(&$node1, &$node2)
{
	if ($node1->getId() > $node2->getId())
		return 1;
	if ($node1->getId() < $node2->getId())
		return -1;
	return 0;
}
*/


/**
 * The root node of a tree.
 * bab_RootNode provide the ability to access any node in the tree
 * by its id.
 */
class bab_RootNode extends bab_Node
{
	var $_ids;
	var $_guids;

	function bab_RootNode()
	{
		parent::bab_Node(bab_InNode::NULL_NODE());
		$this->_ids = array();
		$this->_guids = array();
	}

	function &createNode(&$data, $id = null)
	{
		if (!is_null($id) && array_key_exists($id, $this->_ids)) {
			return bab_Node::NULL_NODE();
		}
		$newNode =& new bab_Node($this, $id);
		$newNode->setData($data);
		$this->_guids[$newNode->getGuid()] =& $newNode;
		if (!is_null($newNode->getId())) {
			$this->_ids[$newNode->getId()] =& $newNode;
		}
		return $newNode;
	}
	
	function &createNodeIterator(&$root)
	{
		$nodeIterator =& new bab_NodeIterator($root);
		return $nodeIterator;
	}

	function &getNodeById($id)
	{
		if (array_key_exists($id, $this->_ids)) {
			return $this->_ids[$id];
		}
		return bab_Node::NULL_NODE();
	}

	function &getNodeByGuid($guid)
	{
		if (array_key_exists($guid, $this->_guids)) {
			return $this->_guids[$guid];
		}
		return bab_Node::NULL_NODE();
	}
	
}

/**
 * This class provides the ability to perform a depth-first traversal of a tree.
 */
class bab_NodeIterator
{
	var $_tree;
	var $_currentNode;
	var $_nodeStack;
	var $_levelStack;
	var $_level;

	/**
	 * Constructor
	 * @param bab_InNode $node	The starting node for the iterator.
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
	 * @return &bab_Node
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
 * bab_OrphanRootNode 
 */
class bab_OrphanRootNode extends bab_RootNode
{
	var $_orphansByParent;
	var $_orphans;
	
	/**
	 * Constructor
	 */
	function bab_OrphanRootNode()
	{
		parent::bab_RootNode();
		$this->_orphansByParent = array();
		$this->_orphans = array();
	}
	

	function _update($newNodeId)
	{
		if (!isset($this->_orphansByParent[$newNodeId])) {
			return;
		}
		$newNode =& $this->getNodeById($newNodeId);	
		foreach (array_keys($this->_orphansByParent[$newNodeId]) as $childId) {
			$childNode =& $this->_orphansByParent[$newNodeId][$childId];
			unset($this->_orphansByParent[$newNodeId][$childId]);
			unset($this->_orphans[$childNode->getId()]);
			$newNode->appendChild($childNode);
		}
	}
	
	
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
	 * @param bab_Node $newNode
	 * @param int|string $id
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
 * 
 */
class bab_TreeViewElement
{
	var $_id;
	var $_type;
	var $_title;
	var $_description;
	var $_link;
	
	var $_icon;
	
	var $_actions;
	
	var $_info;
	
	function bab_TreeViewElement($id, $type, $title, $description, $link)
	{
		$this->_id = $id;
		$this->_type = $type;
		$this->_title = $title;
		$this->_description = $description;
		$this->_link = $link;
		$this->_actions = array();
		$this->_icon= '';
		$this->_info = '';
	}
	
	function addAction($name, $caption, $link, $script)
	{
		$this->_actions[] = array('name' => $name,
								  'caption' => $caption,
								  'link' => $link,
								  'script' => $script);
	}
	
	function setInfo($text)
	{
		$this->_info = $text;
	}

	function setIcon($name)
	{
		$this->_icon = $name;
	}
	
	
	function compare(&$element)
	{
//		if ($this->firstChild() && !$node2->firstChild())
//			return -1;
//		if (!$this->firstChild() && $node2->firstChild())
//			return 1;
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

class bab_TreeView
{
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
	var $t_info;
	var $t_showRightElements;

	var $t_nodeIcon;

	var $t_expand;
	var $t_collapse;
	
	var $_currentElement;

	var $_templateFile;
	var $_templateSection;
	var $_templateCache;
		

	function bab_TreeView($id)
	{
		$this->_id = $id;
		$this->_rootNode = new bab_OrphanRootNode();
		$this->_iterator = null;
		
		$this->t_treeViewId= $this->_id;
		$this->t_expand = bab_translate('Expand');
		$this->t_collapse = bab_translate('Collapse');

		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCache = null;
		
		$this->_upToDate = false;
	}
	
	function &createElement($id, $type, $title, $description, $link)
	{
		$element =& new bab_TreeViewElement($id, $type, $title, $description, $link);
		return $element;
	}

	function appendElement(&$element, $parentId)
	{
		$node =& $this->_rootNode->createNode($element, $element->_id);
		$this->_rootNode->appendChild($node, $parentId);
		$this->_upToDate = false;
	}
		
	function sort($comparisonFunctionName = 'treeViewNodeComparison')
	{
		$this->_invalidateCache();
		$this->_rootNode->sortSubTree($comparisonFunctionName);
	}

		
	// Template functions.
	function getNextElement()
	{
		if (is_null($this->_iterator)) {
			$this->_iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
			$this->_iterator->nextNode();
			$this->t_level = $this->_iterator->level();
			$this->t_previousLevel = $this->t_level - 1;
		}
		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
		if ($this->t_levelVariation < -1)
		{
			$this->t_previousLevel--;
			return true;
		}

		$this->t_previousLevel = $this->t_level;

		if ($node =& $this->_iterator->nextNode()) {
			$this->t_level = $this->_iterator->level();
			$element =& $node->getData();
			$this->t_id = $this->_id . '.' . $element->_id;
			$this->t_type = $element->_type;
			$this->t_title = $element->_title;
			$this->t_description = $element->_description;
			$this->t_link = $element->_link;
			$this->t_info = $element->_info;
			$this->t_nodeIcon= $element->_icon;
			$this->_currentElement =& $element;
			reset($this->_currentElement->_actions);

			$this->t_showRightElements = ($element->_info != '')
							|| (count($this->_currentElement->_actions) > 0);
			return true;
		}
		$this->_iterator = null;
		return false;
	}
	
	function getNextAction()
	{
		if (list(,$action) = each($this->_currentElement->_actions)) {
			$this->action_name = $action['name'];
			$this->action_caption = $action['caption'];
			$this->action_url = $action['link'];
			$this->action_script = $action['script'];
			return true;
		}
		reset($this->_currentElement->_actions);
		return false;
	}

	function _invalidateCache()
	{
		$this->_templateCache = null;
	}

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
			$this->_templateCache = bab_printTemplate($this, $this->_templateFile, $this->_templateSection);
			$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, 'treeview_scripts');
		}
		return $this->_templateCache;
	}
}

/*
function treeViewNodeComparison(&$node1, &$node2)
{
	if ($node1->firstChild() && !$node2->firstChild())
		return -1;
	if (!$node1->firstChild() && $node2->firstChild())
		return 1;

	$element1 =& $node1->getData();
	$element2 =& $node2->getData();
	
	if ((int)$element1->_info > (int)$element2->_info)
		return -1;
	if ((int)$element1->_info < (int)$element2->_info)
		return 1;

	if (strtoupper($element1->_title) > strtoupper($element2->_title))
		return 1;
	if (strtoupper($element1->_title) < strtoupper($element2->_title))
		return -1;
	return 0;
}
*/






define('BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES',		1);
define('BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS',			2);
define('BAB_ARTICLE_TREE_VIEW_SHOW_EMPTY_TOPICS',	4);

class bab_ArticleTreeView extends bab_TreeView
{
	var $_db;
	var $_babBody;
	var $_attributes;

	function bab_ArticleTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCache = null;

		$this->_attributes = BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES
						   | BAB_ARTICLE_TREE_VIEW_SHOW_EMPTY_TOPICS;
	}

	function setAttributes($attributes) 
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
	}

	/**
	 * Add article topics to the tree.
	 * @access private
	 */
	function _addTopics()
	{
		global $babBody;

		$sql = 'SELECT tt.id, tt.id_cat, tt.category FROM ' . BAB_TOPICS_TBL.' tt';
		//*
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' left join '.BAB_TOPICS_CATEGORIES_TBL.' tct on tt.id_cat=tct.id where tct.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		//*/
		$topics = $this->_db->db_query($sql);
		while ($topic = $this->_db->db_fetch_array($topics)) {
			$element =& $this->createElement('topic' . $topic['id'],
											 'topic',
											 $topic['category'],
											 '',
											 '');
			$element->setIcon('topic');
			$parentId = ($topic['id_cat'] === '0' ? null :
												'category' . $topic['id_cat']);
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
		//*
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' where id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		//*/
		$categories = $this->_db->db_query($sql);

		while ($category = $this->_db->db_fetch_array($categories)) {
			$element =& $this->createElement('category' . $category['id'],
											 'category',
											 $category['title'],
											 '',
											 '');
			$element->setIcon('category');
			$parentId = ($category['id_parent'] === '0' ? null :
											'category' . $category['id_parent']);
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
		//*
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' left join '.BAB_TOPICS_TBL.' tt on at.id_topic=tt.id left join '.BAB_TOPICS_CATEGORIES_TBL.' tct on tt.id_cat=tct.id where id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		//*/

		$rs = $this->_db->db_query($sql);
		while ($article = $this->_db->db_fetch_array($rs)) {
			$element =& $this->createElement('article' . $article['id'],
											 'article',
											 $article['title'],
											 '',
											 '');
			$element->setIcon('article');
			$this->appendElement($element, 'topic' . $article['id_topic']);
		}
	}

	function addStatistics($start, $end)
	{
		$this->_updateTree();
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
			$node =& $this->_rootNode->getNodeById('article' . $article['id']);
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




class bab_FileTreeView extends bab_TreeView
{
	var $_db;
	var $_babBody;
	var $_attributes;
	var $_fullpath;
	

	function bab_FileTreeView($id, $gr, $id)
	{
		require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
		$this->_fullpath = bab_getUploadFullPath($gr, $id);
	}

	function setAttributes($attributes) 
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
	}


	/**
	 * Add collective directories.
	 * @access private
	 */
	function _addCollectiveDirectories()
	{
		global $babBody;

		$sql = 'SELECT fft.id, fft.folder FROM ' . BAB_FM_FOLDERS_TBL. ' fft';
		//*
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' where fft.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		//*/
		$sql .= ' ORDER BY folder';

		$folders = $this->_db->db_query($sql);
		while ($folder = $this->_db->db_fetch_array($folders)) {
			$element =& $this->createElement('d' . $folder['id'],
											 'directory',
											 $folder['folder'],
											 '',
											 '');
			$element->setIcon('folder');
			$this->appendElement($element, null);
		}
	}
	
	function _addFiles()
	{
		global $babBody;

		$sql = 'SELECT ft.id, ft.path, ft.name, ft.id_owner FROM ' . BAB_FILES_TBL.' ft';
		//*
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' left join '.BAB_FM_FOLDERS_TBL.' fft on ft.id_owner=fft.id where ft.bgroup=\'Y\' and fft.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		else
			{
			$sql .=' WHERE bgroup=\'Y\'';
			}
		//*/
		$sql .= ' ORDER BY name';
		
		$files = $this->_db->db_query($sql);
		while ($file = $this->_db->db_fetch_array($files)) {
			$subdirs = explode('/', $file['path']);
			$parentId = 'd' . $file['id_owner'];

			foreach ($subdirs as $subdir) {
				if (trim($subdir) !== '') {
					if (is_null($this->_rootNode->getNodeById($parentId . '_' . $subdir))) {
						$element =& $this->createElement($parentId . '_' . $subdir,
														 'directory',
														 $subdir,
														 '',
														 '');
						$this->appendElement($element, $parentId);
						$element->setIcon('folder');
					}
					$parentId .= '_' . $subdir;
				}
			}
			$element =& $this->createElement('f' . $file['id'],
											 'file',
											 $file['name'],
											 '',
											 '');
			$element->setIcon('file');
			$this->appendElement($element, $parentId);
		}
	}

	function addStatistics($start, $end)
	{
		$this->_updateTree();
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
			$node =& $this->_rootNode->getNodeById('f' . $file['id']);
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


	function _updateTree()
	{
		$this->_addCollectiveDirectories();
		$this->_addFiles();		
		parent::_updateTree();
	}
}




class bab_ForumTreeView extends bab_TreeView
{
	var $_db;
	var $_babBody;
	var $_attributes;


	function bab_ForumTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
	}

	function setAttributes($attributes) 
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
	}

	/**
	 * Add forums to the tree.
	 * @access private
	 */
	function _addForums()
	{
		global $babBody;

		$sql = 'SELECT id, name FROM ' . BAB_FORUMS_TBL;
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' where id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}

		$sql .= ' ORDER BY ordering';
		
		$rs = $this->_db->db_query($sql);
		while ($forum = $this->_db->db_fetch_array($rs)) {
			$element =& $this->createElement('forum' . $forum['id'],
											 'forum',
											 bab_translate('Forum: ') . $forum['name'],
											 '',
											 '');
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
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' left join '.BAB_FORUMS_TBL.' ft on tt.forum=ft.id where ft.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}


		$sql .= ' ORDER BY tt.date';
		
		$threads = $this->_db->db_query($sql);
		while ($thread = $this->_db->db_fetch_array($threads)) {
			$sql = 'SELECT id, subject, id_parent FROM ' . BAB_POSTS_TBL
				. ' WHERE id_thread = ' . $thread['id']
				. ' ORDER BY ' . BAB_POSTS_TBL . '.date';
			
			$posts = $this->_db->db_query($sql);
			$firstPost = true;
			while ($post = $this->_db->db_fetch_array($posts)) {
				$element =& $this->createElement('post' . $post['id'],
												 'thread',
												 $post['subject'],
												 '',
												 '');
				$parentId = ($post['id_parent'] === '0')
										? 'forum' . $thread['forum']
										: 'post' . $post['id_parent'];
				$this->appendElement($element, $parentId);
			}
		}
	}

	function addStatistics($start, $end)
	{
		$this->_updateTree();
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
			$node =& $this->_rootNode->getNodeById('post' . $post['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($post['hits']);
//				$node =& $node->parentNode();
//				while (!is_null($node)) {
//					$element =& $node->getData();
//					if ($element)
//						$element->setInfo((int)$element->_info + (int)$post['hits']);
//					$node =& $node->parentNode();			
//				}
			}
		}
	}

	function _updateTree()
	{
		$this->_addForums();
		$this->_addThreads();
		parent::_updateTree();
	}
}



class bab_FaqTreeView extends bab_TreeView
{
	var $_db;
	var $_babBody;
	var $_attributes;
	var $_categories;
	

	function bab_FaqTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_db =& $GLOBALS['babDB'];
		$this->_babBody =& $GLOBALS['babBody'];
	}

	function setAttributes($attributes) 
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
	}

	/**
	 * Add faq categories to the tree.
	 * @access private
	 */
	function _addCategories()
	{
		global $babBody;

		$sql = 'SELECT id, category FROM ' . BAB_FAQCAT_TBL;
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' where id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}

//			. ' ORDER BY id';
		
		$categories = $this->_db->db_query($sql);
		while ($category = $this->_db->db_fetch_array($categories)) {
			$element =& $this->createElement('category' . $category['id'],
											 'faqcategory',
											 bab_translate('Category: ') . $category['category'],
											 '',
											 '');
			$element->setIcon('folder');
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
	 * Add sub-categories to the tree.
	 * @access private
	 */
	function _addSubCategories()
	{
		global $babBody;

		$sql = 'SELECT ftt.id_parent, fst.* FROM ' . BAB_FAQ_TREES_TBL . ' ftt ,' . BAB_FAQ_SUBCAT_TBL.' fst';

		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' left join '.BAB_FAQCAT_TBL.' ft on ft.id=fst.id_cat';
			}

		$sql.= ' where';
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' id_dgowner=\''.$babBody->currentAdmGroup.'\' and';
			}

		$sql .= ' ftt.id = fst.id_node AND ftt.id_parent <> 0';
		$sql.= ' ORDER BY ftt.id';
		
		$subCategories = $this->_db->db_query($sql);
		while ($subCategory = $this->_db->db_fetch_array($subCategories)) {
			$element =& $this->createElement('subcat' . $subCategory['id'],
											 'faqsubcategory',
											 $subCategory['name'],
											 '',
											 '');
			$parentId = isset($this->_categories[$subCategory['id_parent']])
								? 'category' . $this->_categories[$subCategory['id_parent']]
								: 'subcat' . $subCategory['id_parent'];
			$element->setIcon('folder');
			$this->appendElement($element, $parentId);
		}
	}

	/**
	 * Add questions to the tree.
	 * @access private
	 */
	function _addQuestions()
	{
		global $babBody;

		$sql = 'SELECT fqt.id, fqt.question, fqt.id_subcat FROM ' . BAB_FAQQR_TBL.' fqt';
		if( $babBody->currentAdmGroup != 0 )
			{
			$sql .= ' left join '.BAB_FAQCAT_TBL.' fct on fqt.idcat=fct.id where fct.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
			}
		
		$questions = $this->_db->db_query($sql);
		while ($question = $this->_db->db_fetch_array($questions)) {
			$element =& $this->createElement('question' . $question['id'],
											 'question',
											 $question['question'],
											 '',
											 '');
			$parentId = isset($this->_categories[$question['id_subcat']])
								? 'category' . $this->_categories[$question['id_subcat']]
								: 'subcat' . $question['id_subcat'];
			$element->setIcon('faq');
			$this->appendElement($element, $parentId);
		}
	}


	function addStatistics($start, $end)
	{
		$this->_updateTree();
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
			$node =& $this->_rootNode->getNodeById('question' . $faq['id']);
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

	function _updateTree()
	{
		$this->_categories = array();
		$this->_addCategories();
		$this->_addSubCategories();
		$this->_addQuestions();
		parent::_updateTree();
	}
}








function bab_tree_test()
{	
	global $babBody;

	// Example of custom tree view.
	//------------------------------
	$treeView = new bab_TreeView('custom');

	$element =& $treeView->createElement('1', 'type1', 'Le titre 1', 'Description', 'lien');
	$treeView->appendElement($element, null);

	$element =& $treeView->createElement('1.2', 'type2', 'Le titre 1.2', 'Description', 'lien');
	$treeView->appendElement($element, '1');

	$element =& $treeView->createElement('1.1', 'type2', 'Le titre 1.1', 'Description', 'lien');
	$element->addAction('move_down', 'Move down', 'move_down.php', '');
	$element->addAction('delete', 'Delete', 'delete.php', '');
	$element->setInfo('Info');
	$treeView->appendElement($element, '1');

	$element =& $treeView->createElement('1.1.1', 'type2', 'Le titre 1.1.1', 'Description', 'lien');
	$treeView->appendElement($element, '1.1');
	$element->addAction('add', 'Add', 'add.php', '');

	$element =& $treeView->createElement('1.1.2', 'type2', 'Le titre 1.1.2', 'Description', 'lien');
	$element->setInfo('Info');
	$treeView->appendElement($element, '1.1');

	$element =& $treeView->createElement('1.1.3', 'type2', 'Le titre 1.1.3', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$treeView->appendElement($element, '1.1');

	$element =& $treeView->createElement('1.1.4', 'type2', 'Le titre 1.1.4', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$treeView->appendElement($element, '1.1');

	$element =& $treeView->createElement('b', 'type2', 'b', 'Description', 'lien');
	$element->setInfo('Info');
	$treeView->appendElement($element, '1.2');

	$element =& $treeView->createElement('a', 'type2', 'a', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$treeView->appendElement($element, '1.2');

	$element =& $treeView->createElement('x', 'type2', 'x', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$treeView->appendElement($element, '1.2');

	$element =& $treeView->createElement('s', 'type2', 's', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$treeView->appendElement($element, '1.2');

	$element =& $treeView->createElement('c', 'type2', 'c', 'Description', 'lien');
	$element->setInfo('Un autre noeud');
	$treeView->appendElement($element, '1.2');

	$element =& $treeView->createElement('2', 'type1', 'Le titre 2', 'Description', 'lien');
	$treeView->appendElement($element, '0');

	
	$iterator = $treeView->_rootNode->createNodeIterator($treeView->_rootNode);

	while ($node =& $iterator->nextNode()) {
		$node->sortChildNodes();
	}

	$babBody->babecho('<h2>Example of a simple custom tree (<code>bab_TreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());
	
	

	

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
	$treeView->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES
							| BAB_ARTICLE_TREE_VIEW_SHOW_EMPTY_TOPICS);
	$treeView->addStatistics('0000-00-00 00:00', '2050-01-01 00:00');
	$treeView->sort();
	$babBody->babecho('<h2>Example of article tree (<code>bab_ArticleTreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());


	// Example of file tree view.
	//---------------------------
	$start = microtime(true);
	$treeView = new bab_FileTreeView('file', 'N', '0');
	$end = microtime(true);
	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
	print_r('new bab_FileTreeView : ' . ($end - $start));
	echo "</div></pre>\n";

	$start = microtime(true);
	$treeView->addStatistics('2000-01-01 00:00', '2007-01-01 00:00');
	$end = microtime(true);
	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
	print_r('addStatistics : ' . ($end - $start));
	echo "</div></pre>\n";

	$start = microtime(true);
	$treeView->sort();
	$end = microtime(true);
	echo '<div style="background-color: #FFFFFF; border: 1px solid red; padding: 4px; position: relative; z-index: 255"><pre>';
	print_r('sort : ' . ($end - $start));
	echo "</div></pre>\n";

	$babBody->babecho('<h2>Example of file tree (<code>bab_FileTreeView</code>)</h2>');
	$babBody->babecho($treeView->printTemplate());

}



?>