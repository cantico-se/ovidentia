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


include_once $GLOBALS['babInstallPath'].'utilit/treebase.php';



/**
 * An element (node) of a bab_TreeView.
 * 
 * @see bab_TreeView::createElement()
 */
class bab_TreeViewElement
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
	var $_menus;
	var $_checkBoxes;

	var $_info;
	var $_rank;
	
	var $_subTree;
	var $_fetchContentScript;
	/**#@-*/


	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 */
	public function __construct($id, $type, $title, $description, $link)
	{
		$this->_id = $id;
		$this->_type = $type;
		$this->_title = $title;
		$this->_description = $description;
		$this->_link = $link;
		$this->_actions = array();
		$this->_menus = array();
		$this->_checkBoxes = array();
		$this->_icon= '';
		$this->_info = '';
		$this->_tooltip = '';
		$this->_rank = 0;
		$this->_subTree = '';
		$this->setFetchContentScript(false);
	}


	/**
	 * Defines the url that will be called when dynamically fetching
	 * the subtree of an element.
	 *
	 * @param string $url
	 */
	public function setFetchContentScript($url)
	{
		$this->_fetchContentScript = $url;
	}


	/**
	 * Adds an action icon for the treeview element.
	 * 
	 * @param string	$name
	 * @param string	$caption
	 * @param string	$icon
	 * @param string	$link
	 * @param string	$script
	 * @param array		$scriptArgs
	 */
	public function addAction($name, $caption, $icon, $link, $script, $scriptArgs = array('this'))
	{
		$this->_actions[] = array('name' => $name,
								  'caption' => $caption,
								  'icon' => $icon,
								  'link' => $link,
								  'script' => $script,
								  'args' => $scriptArgs);
	}


	/**
	 * Adds a menu to the treeview element.
	 * 
	 * @param string $name
	 * @param string $caption
	 * @param string $icon
	 */
	public function addMenu($name, $caption, $icon)
	{
		$this->_menus[$name] = array('name' => $name,
									 'caption' => $caption,
									 'icon' => $icon,
									 'actions' => array());
	}


	/**
	 * Adds an item to the specified menu.
	 *
	 * @param string $menuName
	 * @param string $actionName
	 * @param string $caption
	 * @param string $icon
	 * @param string $link
	 * @param string $script
	 */
	public function addMenuAction($menuName, $actionName, $caption, $icon, $link, $script)
	{
		$this->_menus[$menuName]['actions'][] = array('name' => $actionName,
													  'caption' => $caption,
													  'icon' => $icon,
													  'link' => $link,
													  'script' => $script);
	}


	/**
	 * Adds a separator (typically an horizontal line) in the specified menu.
	 *
	 * @param string $menuName
	 */
	public function addMenuSeparator($menuName)
	{
		$this->_menus[$menuName]['actions'][] = array('name' => '-');
	}

	/**
	 * Adds a checkbox to the treeview element.
	 * 
	 * @param string $name
	 * @param boolean $check		True to check the box.
	 * @param string $script		The script to execute on click.
	 */
	public function addCheckBox($name, $check = false, $script = '')
	{
		$this->_checkBoxes[] = array('name' => $name, 'checked' => $check, 'script' => $script);
	}

	/**
	 * Defines an info text that will appear on the right of the treeview element title.
	 * 
	 * @param string $text
	 */
	public function setInfo($text)
	{
		$this->_info = $text;
	}

	/**
	 * Defines a tooltip text that will appear when hovering the icon and label of the element.
	 * 
	 * @param string $text
	 */
	public function setTooltip($text)
	{
		$this->_tooltip = $text;
	}

	/**
	 * Defines the rank of the treeview element (that can be used by the compare and sort methods).
	 * 
	 * @param int $rank
	 */
	public function setRank($rank)
	{
		$this->_rank = $rank;
	}

	/**
	 * Defines the url link when the element is clicked.
	 * 
	 * @param string $url
	 */
	public function setLink($url)
	{
		$this->_link = $url;
	}

	/**
	 * Defines the url of the treeview element icon.
	 * 
	 * @param string $url
	 */
	public function setIcon($url)
	{
		$this->_icon = $url;
	}

	/**
	 * Defines the url of the subTree (the url should provide the content of the subTree to be inserted).
	 * The url will be called when the TreeViewElement is expanded.
	 * 
	 * @param string $url
	 */
	public function setSubTree($url)
	{
		$this->_subTree = $url;
	}


	/**
	 * Compare two bab_TreeViewElements
	 *
	 * The result of $a->compare($b) will be :
	 * 	< 0 if $a is less than $b;
	 *  > 0 if $a is greater than $b,
	 *  = 0 if they are equal.
	 * 
	 * @param bab_TreeViewElement $element
	 * @return int
	 */
	public function compare($element)
	{
		$diff = (int)$element->_rank - (int)$this->_rank;
		if ($diff === 0) {
			return bab_compare(mb_strtolower($this->_title), mb_strtolower($element->_title));
		}
		return $diff;
	}

}



/**
 * DEPRECATED ** Use corresponding bab_TreeView constant.
 * @deprecated
 */
define('BAB_TREE_VIEW_ID_SEPARATOR',	'__');

//define('BAB_TREE_VIEW_COLLAPSED',			1);
//define('BAB_TREE_VIEW_EXPANDED',			2);

/**
 * DEPRECATED ** Use corresponding bab_TreeView constant.
 * @deprecated
 */
define('BAB_TREE_VIEW_MULTISELECT',			1024);
/**
 * DEPRECATED ** Use corresponding bab_TreeView constant.
 * @deprecated
 */
define('BAB_TREE_VIEW_MEMORIZE_OPEN_NODES',	2048);
/**
 * DEPRECATED ** Use corresponding bab_TreeView constant.
 * @deprecated
 */
define('BAB_TREE_VIEW_SHOW_TOOLBAR',		4096);


/**
 * A TreeView widget used to display hierarchical data.
 */
class bab_TreeView extends bab_Template
{
//	const VIEW_COLLAPSED		=    1;
//	const VIEW_EXPANDED			=    2;
	
	// Constants used for add/set/get/removeAttributes methods.
	/**
	 * The treeview will offer the selection of multiple nodes.
	 */
	const MULTISELECT			= 1024;
	
	/**
	 * Will store the state of open/closed nodes in a cookie.
	 */
	const MEMORIZE_OPEN_NODES	= 2048;

	/**
	 * A toolbar with expand / collapse and a search field will be added a the top of the treeview.
	 */
	const SHOW_TOOLBAR			= 4096;


	/**
	 * The separator string use to separate type and id in an html id.
	 */
	const ID_SEPARATOR			= '__';
	
	/**
	 * @var string
	 */
	protected $_id;
	/**
	 * @var bab_OrphanRootNode
	 */
	protected $_rootNode;
	

	/**
	 * @var bab_NodeIterator
	 */
	protected $_iterator;

	/**
	 * @var array		Array of boolean for which keys are highlighted elements ids.
	 */
	protected $_highlightedElements;

	/**
	 * @var bool
	 */
	private $_upToDate;

	/**
	 * @var int
	 */
	private $_attributes;

	protected $_currentElement;


	public $_templateFile;
	public $_templateSection;
	public $_templateCss;
	public $_templateScripts;
	public $_templateCache;

	
	// Template properties.
	protected $t_treeViewId;
	protected $t_id;
	protected $t_previousId;
	protected $t_type;
	protected $t_title;
	protected $t_description;
	protected $t_link;
	protected $t_levelVariation;
	protected $t_level;
	protected $t_previousLevel;
	protected $t_offsetLevel;
	protected $t_offsetPreviousLevel;
	protected $t_baseLevel;

	protected $t_isFirstChild;
	protected $t_isMiddleChild;
	protected $t_isSingleChild;
	protected $t_isLastChild;

	protected $t_info;
	protected $t_tooltip;
	protected $t_showRightElements;

	protected $t_nodeIcon;

	protected $t_classes;

	protected $t_expand;
	protected $t_collapse;
	protected $t_submit;

	protected $t_highlighted;

	protected $t_loading;

	protected $t_id_separator;

	protected $t_isMultiSelect;
	protected $t_memorizeOpenNodes;
	protected $t_showToolbar;

	protected $t_subtree;


	/**
	 * @param string $id	A unique treeview id in the page.
	 * 						Must begin with a letter ([A-Za-z]) and may be followed by any
	 * 						number of letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
	 * 						colons (":"), and periods (".").
	 * 
	 * @return bab_TreeView
	 * @access public
	 */
	public function __construct($id)
	{
		$this->_id = $id;
		$this->_rootNode = new bab_OrphanRootNode();
		$this->_iterator = null;

		$this->_highlightedElements = array();

		$this->t_treeViewId= $this->_id;
		
		$this->t_loading = bab_translate('Loading...');
		$this->t_expand = bab_translate('Expand');
		$this->t_collapse = bab_translate('Collapse');
		$this->t_submit = bab_translate('Valider');

		$this->t_level = null;
		$this->t_previousLevel = null;
		$this->t_baseLevel = 0;

		$this->t_classes = '';

		$this->t_layout = 'horizontal';

		// Default template files.
		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCss = 'treeview_css';
		$this->_templateScripts = 'treeview_scripts';
		$this->_templateCache = null;

		$this->t_id_separator = self::ID_SEPARATOR;

		$this->t_subtree = null;

		$this->_upToDate = false;

		$this->setAttributes(self::SHOW_TOOLBAR | self::MEMORIZE_OPEN_NODES);

		$this->t_fetchContentScript = false;
	}


	/**
	 * Sets additional classes for use in css stylesheets.
	 *
	 * @param string $classes		Space separated list of classes.
	 */
	public function setClasses($classes)
	{
		$this->t_classes = $classes;
	}


	/**
	 * Returns the root node of the treeview
	 * 
	 * @return bab_OrphanRootNode
	 */
	public function getRootNode()
	{
		return $this->_rootNode;
	}


	/**
	 * Returns whether the treeview is up to date.
	 * 
	 * @return bool
	 */
	public function isUpToDate()
	{
		return $this->_upToDate;
	}


	/**
	 * Returns the attributes of the treeview.
	 * 
	 * @return int
	 * @access public
	 */
	public function getAttributes()
	{
		return $this->_attributes;
	}
	
	/**
	 * Defines the attributes of the treeview.
	 * 
	 * @param int $attributes
	 * @access public
	 */
	public function setAttributes($attributes)
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
		$this->t_isMultiSelect = (($attributes & self::MULTISELECT) !== 0);
		$this->t_memorizeOpenNodes = (($attributes & self::MEMORIZE_OPEN_NODES) !== 0);
		$this->t_showToolbar = (($attributes & self::SHOW_TOOLBAR) !== 0);
	}

	/**
	 * Adds attributes to the treeview.
	 * 
	 * @param int $attributes
	 */
	public function addAttributes($attributes)
	{		
		$this->setAttributes($this->getAttributes() | $attributes);
	}


	/**
	 * Adds attributes to the treeview.
	 * 
	 * @param int $attributes
	 */
	public function removeAttributes($attributes)
	{
		$this->setAttributes($this->_attributes & ~$attributes);
	}


	/**
     * Checks if all the specified attributes are set.
     * 
     * @param int	$attributes		A bitset of attributes.
     * @return bool		True if all specified attributes are set.
	 */
	public function hasAttributes($attributes)
	{
		return (($this->_attributes & $attributes) === $attributes);
	}


	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 * 
	 * @return bab_TreeViewElement
	 */
	public function createElement($id, $type, $title, $description, $link)
	{
		$element =new bab_TreeViewElement($id, $type, $title, $description, $link);
		return $element;
	}


	/**
	 * Appends $element as the last child of the element with the id $parentId.
	 * If $parentId is null, the element will appear as a first level node.
	 * 
	 * @param bab_TreeViewElement	$element	An element created by the method createElement.
	 * @param string 				$parentId	The id of the parent element.
	 */
	public function appendElement($element, $parentId)
	{
		$node = $this->_rootNode->createNode($element, $element->_id);
		
		if (!($node instanceof bab_Node))
		{
			throw new ErrorException('Unexpected node, id='.$element->_id);
		}
		
		$this->_rootNode->appendChild($node, $parentId);
		$this->_upToDate = false;
		$this->onElementAppended($element, $parentId);
	}

	
	/**
	 * Removes the selected element and its descendants from the treeview.
	 * 
	 * @param string			$id		The id of the element to remove.
	 * @return	bool
	 */
	public function removeElement($id)
	{
		$node = $this->_rootNode->getNodeById($id);
		if (!isset($node)) {
			return false;
		}
		$parentNode = $node->parentNode();
		if (!isset($parentNode)) {
			return false;
		}
		return $parentNode->removeChild($node);
	}

	/**
	 * Sorts the TreeView.
	 * 
	 * Siblings of the same branch are ordered.
	 * Ordering is performed using the bab_TreeViewElement::compare() method.
	 */
	public function sort()
	{
//		$this->_updateTree();
		$this->_invalidateCache();
		$this->_rootNode->sortSubTree();
	}

	
	public function highlightElement($id)
	{
		$this->_highlightedElements[$id] = true;
	}
		
	/**#@+
	 * Template methods.
	 * @ignore
	 */	
	public function getNextElement()
	{
		if (is_null($this->_iterator)) {
			$this->_iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
			$this->_iterator->nextNode();
			$this->t_level = $this->_iterator->level();
			$this->t_previousLevel = $this->t_level - 1;
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;
			$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;
		}
		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
		if ($this->t_levelVariation < -1) {
			$this->t_previousLevel--;
			$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;
			return true;
		}

		$this->t_previousLevel = $this->t_level;
		$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;

		if ($node = $this->_iterator->nextNode()) {
			$this->t_isFirstChild = $node->isFirstChild();
			$this->t_isLastChild = $node->isLastChild();
			$this->t_isMiddleChild = (!$node->isFirstChild() && !$node->isLastChild());
			$this->t_isSingleChild = ($node->isFirstChild() && $node->isLastChild());

			$this->t_level = $this->_iterator->level();
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;
			$element = $node->getData();
			$this->t_fetchContentScript = $element->_fetchContentScript;
			$this->t_highlighted = isset($this->_highlightedElements[$element->_id]);
			$this->t_previousId = isset($this->t_id) ? $this->t_id : '';
			$this->t_id = $this->_id . '.' . $element->_id;
			$this->t_type = $element->_type;
			$this->t_title = $element->_title;
			$this->t_description = $element->_description;
			$this->t_link = $element->_link;
			$this->t_info = $element->_info;
			$this->t_tooltip = $element->_tooltip;
			$this->t_nodeIcon = $element->_icon;
			$this->_currentElement = $element;
			reset($this->_currentElement->_actions);

			$this->t_showRightElements = ($element->_info != '')
							|| (count($this->_currentElement->_actions) > 0)
							|| (count($this->_currentElement->_menus) > 0)
							|| (count($this->_currentElement->_checkBoxes) > 0);
			return true;
		}
		if ($this->t_level > -1) {
			$this->t_level = -1;
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;
			return $this->getNextElement();
		}
		$this->_iterator = null;
		return false;
	}
	
	public function getNextAction()
	{
		if (!isset($this->_currentElement->_actions))
			return false;
		if (list(,$action) = each($this->_currentElement->_actions)) {
			$this->action_name = $action['name'];
			$this->action_caption = $action['caption'];
			$this->action_icon = $action['icon'];
			$this->action_url = $action['link'];
			$this->action_script = $action['script'];
			$this->action_script_args = implode(',', $action['args']);
			return true;
		}
		reset($this->_currentElement->_actions);
		return false;
	}

	public function getNextMenu()
	{
		if (list(,$menu) = each($this->_currentElement->_menus)) {
			$this->menuActions = $menu['actions'];
			$this->menu_name = $menu['name'];
			$this->menu_caption = $menu['caption'];
			$this->menu_icon = $menu['icon'];
			return true;
		}
		reset($this->_currentElement->_menus);
		return false;
	}

	public function getNextMenuAction()
	{
		if (list(,$action) = each($this->menuActions)) {
			$this->action_name = $action['name'];
			if ($this->action_name != '-') {
				$this->action_caption = $action['caption'];
				$this->action_icon = $action['icon'];
				$this->action_url = $action['link'];
				$this->action_script = $action['script'];
			}
			return true;
		}
		reset($this->menuActions);
		return false;
	}

	public function getNextCheckBox()
	{
		if (list(,$checkBox) = each($this->_currentElement->_checkBoxes)) {
			$this->checkbox_name = $checkBox['name'];
			$this->checkbox_script = $checkBox['script'];
			$this->checkbox_checked = $checkBox['checked'];
			return true;
		}
		reset($this->_currentElement->_checkBoxes);
		return false;
	}
	/**#@-*/

	/**
	 * @access private
	 */
	protected function _invalidateCache()
	{
		$this->_templateCache = null;
	}

	/**
	 * @access protected
	 */
	protected function _updateTree()
	{
		$this->_upToDate = true;
	}

	/**
	 * 
	 * @return string
	 */
	public function printTemplate()
	{
		if (is_null($this->_templateCache)) {
			if (!$this->isUpToDate()) {
				$this->_updateTree();
			}
			$this->t_subtree = bab_printTemplate($this, $this->_templateFile, 'subtree');
			$this->_templateCache = bab_printTemplate($this, $this->_templateFile, $this->_templateCss);
			$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, $this->_templateSection);
			$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, $this->_templateScripts);
		}
		return $this->_templateCache;
	}

	/**
	 * 
	 * @return string
	 */
	public function printSubTree()
	{
		if (!$this->isUpToDate()) {
			$this->_updateTree();
		}
		$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, 'subtree');
		return $this->_templateCache;
	}

	/**#@+
	 * Overridable event method. 
	 */

	/**
	 * DEPRECATED ** DO NOT USE ** Instead override the appendElement method of bab_TreeView.
	 * 
	 * This method is called after the bab_TreeViewElement $element has been appended to the treeview. 
	 *
	 * @param bab_TreeViewElement $element
	 * @param string $parentId
	 * @deprecated
 	 */
	public function onElementAppended($element, $parentId)
	{
	}

	/**#@-*/

}








/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES',						 0);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS',							 1);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES',			 			 2);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_HIDE_EMPTY_TOPICS_AND_CATEGORIES',	 4);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES',				 8);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS',					16);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES',					32);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE',						64);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS',				   128);

/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_READ_ARTICLES',						 1);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SUBMIT_ARTICLES',						 2);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_MODIFY_ARTICLES',						 3);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_SUBMIT_COMMENTS',						 4);
/**
 * @deprecated Use corresponding bab_ArticleTreeView constant.
 */
define('BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC',						 5);


/**
 * A Treeview populated with categories/topics/articles.
 */
class bab_ArticleTreeView extends bab_TreeView
{
	// Constants used for add/set/get/removeAttributes methods.
	
	/**
	 * Show article category nodes in the treeview.
	 */
	const SHOW_CATEGORIES					=    0;

	/**
	 * Show article topic nodes in the treeview.
	 */
	const SHOW_TOPICS						=    1;
	
	/**
	 * Show article nodes in the treeview (implies SHOW_TOPICS)
	 */
	const SHOW_ARTICLES						=    2;
	
	/**
	 * Hide topic / category nodes containing zero article
	 * (for the current user and purpose).
	 */
	const HIDE_EMPTY_TOPICS_AND_CATEGORIES	=    4;
	
	/**
	 * Make article category nodes selectable.
	 */
	const SELECTABLE_CATEGORIES				=    8;

	/**
	 * Make article topic nodes selectable.
	 */
	const SELECTABLE_TOPICS					=   16;
	
	/**
	 * Make article nodes selectable.
	 */
	const SELECTABLE_ARTICLES				=   32;
	
	/**
	 * Show a root node (Named "Categories, topics and articles",
	 * "Categories and topics" or "Categories" depending on the
	 * visible nodes).
	 */
	const SHOW_ROOT_NODE					=   64;
	
	/**
	 * DEPRECATED ** Only show administered delegations.
	 * @deprecated by SHOW_ONLY_ADMINISTERED_DELEGATION
	 */
	const HIDE_DELEGATIONS					=  128;
	/**
	 * Only show administered delegations.
	 */
	const SHOW_ONLY_ADMINISTERED_DELEGATION =  128;

	// Constants used for set/getAction methods.
	const READ_ARTICLES						=    1;
	const SUBMIT_ARTICLES					=    2;
	const MODIFY_ARTICLES					=    3;
	const SUBMIT_COMMENTS					=    4;
	const MANAGE_TOPIC						=    5;

	/**#@+
	 * @access private
	 */	
	private $_action;
	/**
	 * @var $_link
	 * @deprecated (see setLink())
	 */
	var $_link;
	var $_categoriesLinks;
	var $_topicsLinks;
	var $_articlesLinks;
	
	private $_ignoredCategories = array();
	
	/**
	 * Datas on which the appendElement work
	 * After the function call the $_datas is
	 * invalid
	 * 
	 * @access private 
	 * @var mixed
	 */
	var $_datas;
	/**#@-*/


	public function __construct($id)
	{
		parent::__construct($id);

		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCache = null;

		$this->setLink('');
		$this->setCategoriesLinks('');
		$this->setTopicsLinks('');
		$this->setArticlesLinks('');

		$this->addAttributes(self::SHOW_ARTICLES | self::SHOW_ROOT_NODE);
		$this->setAction(self::READ_ARTICLES);
	}


	/**
	 * Ignore the specified categories when creating the treeview.
	 * 
	 * @param array	$categoryIds		The array of ids to ignore.
	 */
	public function ignoreCategories($categoryIds)
	{
		foreach ($categoryIds as $categoryId) {
			$this->_ignoredCategories[$categoryId] = $categoryId;
		}
	}


	/**
	 * Returns the array of ignored categories.
	 * 
	 * @return array		An array containing the ids of categories (as key and value).
	 */
	public function getIgnoredCategories()
	{
		return $this->_ignoredCategories;
	}


	/**
	 * Defines the action for which the article tree is displayed.
	 * 
	 * The treeview will only display the topics for which the
	 * current user is allowed to perform the selected action.
	 * Possible values for $action are:
	 *  - bab_ArticleTreeView::READ_ARTICLES
	 *  - bab_ArticleTreeView::SUBMIT_ARTICLES
	 *  - bab_ArticleTreeView::MODIFY_ARTICLES
	 *  - bab_ArticleTreeView::SUBMIT_COMMENTS
	 *  - bab_ArticleTreeView::MANAGE_TOPIC
	 *
	 * @param int $action
	 */
	public function setAction($action)
	{
		$this->_action = $action;
	}

	/**
	 * Returns the action for which the article tree is displayed.
	 * 
	 * Possible returned values are:
	 *  - bab_ArticleTreeView::READ_ARTICLES
	 *  - bab_ArticleTreeView::SUBMIT_ARTICLES
	 *  - bab_ArticleTreeView::MODIFY_ARTICLES
	 *  - bab_ArticleTreeView::SUBMIT_COMMENTS
	 *  - bab_ArticleTreeView::MANAGE_TOPIC
	 * @return int
	 */
	public function getAction()
	{
		return $this->_action;	
	}

	/**
	 * Defines the link (can be javascript:...) that will be called when we click on a element (this has an effect only on topics)
	 * @deprecated Use setCategoriesLinks, setTopicsLinks, setArticlesLinks
	 * @param string $link
	 */
	public function setLink($link)
	{
		$this->_link = $link;
	}
	
	/**
	 * Defines the link (can be javascript:...) that will be called when we click to a category element, SELECTABLE_CATEGORIES must be activated
	 * @param string $links
	 */
	public function setCategoriesLinks($links)
	{
		$this->_categoriesLinks = $links;
	}
	
	/**
	 * Defines the link (can be javascript:...) that will be called when we click to a topic element, SELECTABLE_TOPICS must be activated
	 * @param string $links
	 */
	public function setTopicsLinks($links)
	{
		$this->_topicsLinks = $links;
	}
	
	/**
	 * Defines the link (can be javascript:...) that will be called when we click to a article element, SELECTABLE_ARTICLES must be activated
	 * @param string $links
	 */
	public function setArticlesLinks($links)
	{
		$this->_articlesLinks = $links;
	}
	
	
	/**
	 * @return string	SQL query
	 */
	private function _getQueryByRight($tablename)
	{
		global $babDB, $babBody;

		$where = array();
		$sql = 'SELECT topics.id, topics.id_cat, topics.description, topics.category';
		$sql .= ' FROM ' . BAB_TOPICS_TBL . ' topics';
		if ($this->hasAttributes(self::SHOW_ONLY_ADMINISTERED_DELEGATION)) {
			$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS categories ON topics.id_cat=categories.id';
			$where[] = 'categories.id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
		}
		$where[] = 'topics.id IN (' . $babDB->quote(array_keys(bab_getUserIdObjects($tablename))) . ')';
		$sql .= ' WHERE ' . implode(' AND ', $where);

		return $sql;
	}
	
	

	/**
	 * Add article topics to the tree.
	 */
	private function _addTopics()
	{
		global $babDB, $babBody;

		$sql = '';
		switch ($this->_action)
		{
			case self::MODIFY_ARTICLES:
				/* !!! Only one possibility is not managed : the user has no rights in the topic but the option 'the author can modify his article' is activated */
				
				/* All id topics as which the current user has rights */
				$topsub = bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL);
				$topman = bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL);
				$topmod = bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL);
			
				if (count($topsub) > 0  || count($topman) > 0 || count($topmod) > 0)
				{
					if (count($topsub) > 0) {
						/* allow_update != 0 : authors can modify their articles */
						$tmp[] = '(topics.id IN (' . $babDB->quote(array_keys($topsub)) . ") AND topics.allow_update != '0')";
					}
					if( count($topman) > 0 ) {
						/* allow_manupdate != 0 : managers can modify articles */
						$tmp[] = '(topics.id IN (' . $babDB->quote(array_keys($topman)) . ") AND topics.allow_manupdate != '0')";
					}
					if( count($topmod) > 0 )
						$tmp[] = '(topics.id IN (' . $babDB->quote(array_keys($topmod)) . '))';
					$sql = 'SELECT DISTINCT topics.id, topics.id_cat, topics.description, topics.category'
						. ' FROM ' . BAB_ARTICLES_TBL . ' AS articles'
						. ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topics ON topics.id = articles.id_topic'
						. ' WHERE articles.archive=\'N\' AND ' . implode(' OR ', $tmp);
				}
				break;

			case self::SUBMIT_ARTICLES:
				$sql = $this->_getQueryByRight(BAB_TOPICSSUB_GROUPS_TBL);
				break;
				
			case self::MANAGE_TOPIC:
				$sql = $this->_getQueryByRight(BAB_TOPICSMAN_GROUPS_TBL);
				break;


			case self::READ_ARTICLES:
				$sql = $this->_getQueryByRight(BAB_TOPICSVIEW_GROUPS_TBL);
				break;
				
			// list topic by submit comments right seem to be not very usefull
			// case self::SUBMIT_COMMENTS:



			// admin rights view of topics (view all topics by delegation)
			default:
				$sql = 'SELECT topics.id, topics.id_cat, topics.description, topics.category'
				    . ' FROM ' . BAB_TOPICS_TBL . ' AS topics';
				if ($this->hasAttributes(self::SHOW_ONLY_ADMINISTERED_DELEGATION)) {
					$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS categories ON topics.id_cat=categories.id';
					$sql .= ' WHERE categories.id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
				}
				break;
		}
		
		if ($sql !== '')
		{
			$elementType = 'topic';
			if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_TOPICS)) {
				$elementType .= ' clickable';
			}
			$topics = $babDB->db_query($sql);
			while ($topic = $babDB->db_fetch_array($topics)) {
				/* _link is deprecated but used in ancient script */				
				if ($this->_link !== '') {
					$link = sprintf($this->_link, $topic['id']);
				} else {
					$link = '';
				}
				/* Topic link : set with the function setTopicsLinks() ; SELECTABLE_TOPICS must be activated */
				if ($this->hasAttributes(self::SELECTABLE_TOPICS)) {
					if ($this->_topicsLinks !== '') {
						$link = sprintf($this->_topicsLinks, $topic['id']);
					} else {
						$link = '';
					}
				}
				$element = $this->createElement('t' . self::ID_SEPARATOR . $topic['id'],
												 $elementType,
												 bab_toHtml($topic['category']),
												 ''/*$topic['description']*/,
												 $link);
				
				
				if (self::MANAGE_TOPIC === $this->_action) {
				
					list($nbarticles)= $babDB->db_fetch_row($babDB->db_query("select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topic['id'])."' and archive='N'"));
				
					list($nbarcharticles) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topic['id'])."' and archive='Y'"));
				
					$element->setInfo(sprintf(bab_translate('%d Online article(s) | %d Old article(s)'), $nbarticles, $nbarcharticles));	
				}
				
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/topic.png');
				$parentId = ($topic['id_cat'] === '0' ? null :
													'c' . self::ID_SEPARATOR . $topic['id_cat']);
				$this->_datas = $topic;
				$this->appendElement($element, $parentId);
				$this->_datas = null;
			}
		}
	}


	/**
	 * Add article categories to the tree.
	 */
	private function _addCategories()
	{
		global $babBody;
		global $babDB;

		if ($this->hasAttributes(self::SHOW_ROOT_NODE)) {
			if ($this->hasAttributes(self::SHOW_ARTICLES)) {
				$label = bab_translate("Categories, topics and articles");
			} else if ($this->hasAttributes(self::SHOW_TOPICS)) {
				$label = bab_translate("Categories and topics");
			} else {
				$label = bab_translate("Categories");
			}
			$element = $this->createElement('c' . self::ID_SEPARATOR . '0',
											 'categoryroot',
											 $label,
											 '',
											 '');
			$element->setInfo('');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');
			$this->appendElement($element, null);
		}
		
		$sql = 'SELECT id, title, description, id_parent, enabled FROM ' . BAB_TOPICS_CATEGORIES_TBL;
		if ($this->hasAttributes(self::SHOW_ONLY_ADMINISTERED_DELEGATION)) {
			$sql .= ' WHERE id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
		}
		$elementType = 'category';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_CATEGORIES)) {
			$elementType .= ' clickable';
		}
		$categories = $babDB->db_query($sql);
		while ($category = $babDB->db_fetch_array($categories)) {
			if (isset($this->_ignoredCategories[$category['id']])) {
				continue;
			}
			/* Category link : set with the function setCategoriesLinks() ; SELECTABLE_CATEGORIES must be activated */
			$link = '';
			if ($this->hasAttributes(self::SELECTABLE_CATEGORIES)) {
				if ($this->_categoriesLinks !== '') {
					$link = sprintf($this->_categoriesLinks, $category['id']);
				} else {
					$link = '';
				}
			}
			$element = $this->createElement('c' . self::ID_SEPARATOR . $category['id'],
											 $elementType,
											 bab_toHtml($category['title']),
											 '',
											 $link);
			$element->setInfo('');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');
			if (!($this->hasAttributes(self::SHOW_ROOT_NODE)) && $category['id_parent'] === '0') {
				$parentId = null;
			} else {
				$parentId = 'c' . self::ID_SEPARATOR . $category['id_parent'];
			}
			$this->_datas = $category;
			$this->appendElement($element, $parentId);
			$this->_datas = null;
		}
	}

	/**
	 * Add articles to the tree.
	 */
	private function _addArticles()
	{
		global $babDB, $babBody;
		$sql = 'SELECT articles.id, articles.title, articles.id_topic FROM ' . BAB_ARTICLES_TBL.' articles';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_TOPICS_TBL.' topics ON articles.id_topic=topics.id';
			$sql .= ' LEFT JOIN '.BAB_TOPICS_CATEGORIES_TBL.' categories ON topics.id_cat=categories.id';
			$sql .= ' WHERE id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
		}
		$elementType = 'article';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_ARTICLES)) {
			$elementType .= ' clickable';
		}
		$rs = $babDB->db_query($sql);
		while ($article = $babDB->db_fetch_array($rs)) {
			/* Article link : set with the function setArticlesLinks() ; SELECTABLE_ARTICLES must be activated */
			$link = '';
			if ($this->hasAttributes(self::SELECTABLE_ARTICLES)) {
				if ($this->_articlesLinks !== '') {
					$link = sprintf($this->_articlesLinks, $article['id']);
				} else {
					$link = '';
				}
			}
			$element = $this->createElement('a' . self::ID_SEPARATOR . $article['id'],
											 $elementType,
											 bab_toHtml($article['title']),
											 '',
											 $link);
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/article.png');
			$this->_datas = $article;
			$this->appendElement($element, 't' . self::ID_SEPARATOR . $article['id_topic']);
			$this->_datas = null;
		}
	}

	
	/**
	 * Gives a rank to each element of the treeview as specified in the
	 * articles/topics/categories administration.
	 * 
	 * A call to this method does not actually reorder the tree. It should be
	 * followed by a call to {@link sort} in order to do so.
	 */
	public function order()
	{
		global $babDB;

		$this->_updateTree();
		$sql = 'SELECT id_topcat, type, ordering FROM ' . BAB_TOPCAT_ORDER_TBL;

		$orders = $babDB->db_query($sql);
		while ($order = $babDB->db_fetch_array($orders)) {
			if ($order['type'] == 2) {
				$node = $this->getRootNode()->getNodeById('t' . self::ID_SEPARATOR . $order['id_topcat']);
			} else {
				$node = $this->getRootNode()->getNodeById('c' . self::ID_SEPARATOR . $order['id_topcat']);
			}
			if (!is_null($node)) {
				$element = $node->getData();
				$element->setRank(0x7FFFFFFF - $order['ordering']);
			}
		}
	}


	public function addStatistics($start, $end)
	{
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->getRootNode()->createNodeIterator($this->getRootNode());
		$iterator->nextNode();
		while ($node = $iterator->nextNode()) {
			(!is_null($node)) && $node->_data->setInfo('0');
		}
		$sql = 'SELECT st_article_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_ARTICLES_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= ' . $babDB->quote($start);
			$end && $where[] = 'st_date <= ' . $babDB->quote($end . ' 23:59:59');
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		
		$articles = $babDB->db_query($sql);
		while ($article = $babDB->db_fetch_array($articles)) {
			$node = $this->getRootNode()->getNodeById('a' . self::ID_SEPARATOR . $article['id']);
			if (!is_null($node)) {
				$element = $node->getData();
				$element->setInfo($article['hits']);
				$element->setRank((int)$article['hits']);
				$node = $node->parentNode();
				while (!is_null($node)) {
					$element = $node->getData();
					if ($element) {
						$element->setInfo((int)$element->_info + (int)$article['hits']);
						$element->setRank((int)$element->_rank + (int)$article['hits']);
					}
					$node = $node->parentNode();			
				}
			}
		}
	}


	/**
	 * @access private
	 */
	protected function _updateTree()
	{
		if ($this->isUpToDate()) {
			return;
		}
		if ($this->hasAttributes(self::SHOW_TOPICS)
			|| $this->hasAttributes(self::SHOW_ARTICLES))
			$this->_addTopics();

		$this->_addCategories();

		if ($this->hasAttributes(self::HIDE_EMPTY_TOPICS_AND_CATEGORIES)) {
			/* Here we remove empty topics if we show articles and if we don't want to select the topics */
//			if ($this->hasAttributes(self::SHOW_ARTICLES) && $this->hasAttributes(self::SELECTABLE_TOPICS)) {
//				do {
//					$iterator = $this->getRootNode()->createNodeIterator($this->getRootNode());
//					$deadBranches = array();
//					while ($node = $iterator->nextNode()) {
//						$element = $node->getData();
//						if (!$node->hasChildNodes() && isset($element->_type) && mb_strpos($element->_type, 'topic') !== false) { /* mb_strpos used because _type can be topic, topic clickable... */
//							bab_debug($element);
//							$deadBranches[] = $node;
//						}
//					}
//					$modified = (count($deadBranches) > 0);
//					reset($deadBranches);
//					foreach (array_keys($deadBranches) as $deadBranchKey) {
//						$deadBranch = $deadBranches[$deadBranchKey];
//						$parentNode = $deadBranch->parentNode();
//						if ($parentNode) {
//							$parentNode->removeChild($deadBranch);
//						}
//					}
//				} while ($modified);
//			}
			
			/* Here we remove empty categories */
			do {
				$iterator = $this->getRootNode()->createNodeIterator($this->getRootNode());
				$deadBranches = array();
				while ($node = $iterator->nextNode()) {
					$element = $node->getData();
					if (!$node->hasChildNodes() && isset($element->_type) && mb_strpos($element->_type, 'category') !== false) { /* mb_strpos used because _type can be ctegory, category clickable... */
						$deadBranches[] = $node;
					}
				}
				$modified = (count($deadBranches) > 0);
				reset($deadBranches);
				foreach (array_keys($deadBranches) as $deadBranchKey) {
					$deadBranch = $deadBranches[$deadBranchKey];
					$parentNode = $deadBranch->parentNode();
					if ($parentNode) {
						$parentNode->removeChild($deadBranch);
					}
				}
			} while ($modified);
		}

		if ($this->hasAttributes(self::SHOW_ARTICLES))
			$this->_addArticles();
			
		parent::_updateTree();
	}
}






/**
 * DEPRECATED ** Use bab_FileTreeView::SHOW_COLLECTIVE_FOLDERS
 * @deprecated by bab_FileTreeView::SHOW_COLLECTIVE_FOLDERS
 */
define('BAB_FILE_TREE_VIEW_SHOW_COLLECTIVE_DIRECTORIES',		 0);
/**
 * DEPRECATED ** Use bab_FileTreeView::SHOW_SUB_FOLDERS
 * @deprecated by bab_FileTreeView::SHOW_SUB_FOLDERS
 */
define('BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES',				 1);
/**
 * DEPRECATED ** Use bab_FileTreeView::SHOW_FILES
 * @deprecated by bab_FileTreeView::SHOW_FILES
 */
define('BAB_FILE_TREE_VIEW_SHOW_FILES',							 2);
/**
 * DEPRECATED ** Use bab_FileTreeView::SHOW_PERSONAL_FOLDERS
 * @deprecated by bab_FileTreeView::SHOW_PERSONAL_FOLDERS
 */
define('BAB_FILE_TREE_VIEW_SHOW_PERSONAL_DIRECTORIES',		 	 4);
/**
 * DEPRECATED ** Use bab_FileTreeView::SELECTABLE_COLLECTIVE_FOLDERS
 * @deprecated by bab_FileTreeView::SELECTABLE_COLLECTIVE_FOLDERS
 */
define('BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES',	 8);
/**
 * DEPRECATED ** Use bab_FileTreeView::SELECTABLE_SUB_FOLDERS
 * @deprecated by bab_FileTreeView::SELECTABLE_SUB_FOLDERS
 */
define('BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES',	 		16);
/**
 * DEPRECATED ** Use bab_FileTreeView::SELECTABLE_FILES
 * @deprecated by bab_FileTreeView::SELECTABLE_FILES
 */
define('BAB_FILE_TREE_VIEW_SELECTABLE_FILES',					32);
/**
 *  DEPRECATED ** Use bab_FileTreeView::SHOW_ONLY_ADMINISTERED_DELEGATION
 *  @deprecated by bab_FileTreeView::SHOW_ONLY_ADMINISTERED_DELEGATION
 */
define('BAB_FILE_TREE_VIEW_SHOW_ONLY_DELEGATION',			   128);

/**
 * A Treeview populated with folders/files from the file manager.
 */
class bab_FileTreeView extends bab_TreeView
{
	/**#@+
	 * Constant used for add/set/get/removeAttributes methods.
	 */
	/**
	 * Show collective folder node in the treeview.
	 */
	const SHOW_COLLECTIVE_FOLDERS			=    0;

	/**
	 * Show sub-folders (i.e. not first-level collective folders) nodes
	 * in the treeview.
	 */
	const SHOW_SUB_FOLDERS					=    1;

	/**
	 * Show file nodes in the treeview.
	 */
	const SHOW_FILES						=    2;

	/**
	 * Show the user personal folder node in the treeview.
	 */
	const SHOW_PERSONAL_FOLDERS				=    4;

	/**
	 * Make collective folders selectable.
	 */
	const SELECTABLE_COLLECTIVE_FOLDERS		=    8;

	/**
	 * Make sub-folders selectable.
	 */
	const SELECTABLE_SUB_FOLDERS			=   16;

	/**
	 * Make files selectable.
	 */
	const SELECTABLE_FILES					=   32;
	
	/**
	 * When the tree is displayed for administrative purpose and this
	 * attribute is set the treeview will only display the currently
	 * administered delegation.
	 */
	const SHOW_ONLY_ADMINISTERED_DELEGATION	=  128;
	/**#@-*/

	/**#@+
	 * @access private
	 */
	var $_adminView;

	var $_startFolderId;
	var $_startPath;
	var $_updateBaseUrl;

	/**
	 * @var array _visibleDelegations
     */
	var $_visibleDelegations;

	var $_directories;
	/**#@-*/



	public function __construct($id, $adminView = true)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		require_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
		parent::__construct($id);

		$this->addAttributes(self::SHOW_FILES);
		
		$this->_adminView = $adminView;

		$this->setStartPath(null, '');
		$this->setUpdateBaseUrl('');

		$this->_directories = array();
	}

	
	
	public function setStartPath($folderId, $path)
	{
		$this->_startFolderId = $folderId;
		$this->_startPath = $path;
	}


	public function setUpdateBaseUrl($url)
	{
		$this->_updateBaseUrl = $url;
	}


	/**
	 * 
	 */
	protected function _addVisibleDelegations()
	{
		global $babBody;

		$this->_visibleDelegations = bab_getUserFmVisibleDelegations();

		
		// When the tree is displayed for administrative purpose, we only
		// display the currently administered delegation.
		if ($this->hasAttributes(self::SHOW_ONLY_ADMINISTERED_DELEGATION))
		{
			$this->_visibleDelegations = array($babBody->currentAdmGroup => $this->_visibleDelegations[$babBody->currentAdmGroup]);
		}

		// We create a first-level node for each visible delegation.
		foreach ($this->_visibleDelegations as $delegationId => $delegationName)
		{
			$element = $this->createElement('d' . $delegationId,
											 'foldercategory',
											 $delegationName,
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/collective_folder.png');
			$this->appendElement($element, null);
		}
	}


	/**
	 * Add files and subdirectories for the personal folder.
	 */
	protected function _addPersonalFiles()
	{
		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		global $babDB, $babBody;

		$rootPath = '';

		$sql = 'SELECT file.id, file.path, file.name, file.id_owner, file.bgroup '
			 . ' FROM ' . BAB_FILES_TBL . ' file'
			 . ' WHERE file.bgroup=\'N\' AND file.id_owner=' . $babDB->quote($GLOBALS['BAB_SESS_USERID'])
			 . ' AND file.state <> \'D\''
			 . ' ORDER BY file.name';

		$directoryType = 'personnalfolder';
		if (!($this->hasAttributes(self::MULTISELECT))
		&& $this->hasAttributes(self::SELECTABLE_SUB_FOLDERS)) {
			$directoryType .= ' clickable';
		}
		$personalFileType = 'pfile';
		if (!($this->hasAttributes(self::MULTISELECT))
		&& $this->hasAttributes(self::SELECTABLE_FILES)) {
			$personalFileType .= ' clickable';
		}
		$files = $babDB->db_query($sql);


		$folders = new BAB_FmFolderSet();

		$oRelativePath = $folders->aField['sRelativePath'];
		$oName = $folders->aField['sName'];

		while ($file = $babDB->db_fetch_array($files)) {

			$filePath = $file['path'];
			$subdirs = explode('/', $filePath);
				
			$fileId = 'p' . self::ID_SEPARATOR . $file['id'];
			$fileType = $personalFileType;
			$rootId = 'pd' . self::ID_SEPARATOR . $file['id_owner'];
			if (is_null($this->getRootNode()->getNodeById($rootId))) {
				$element = $this->createElement($rootId,
												 'foldercategory',
												 bab_translate("Personal folders"),
												 '',
												 '');
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/personal_folder.png');
				$this->appendElement($element, null);
			}

			$parentId = '';

			foreach ($subdirs as $subdir) {
				if (trim($subdir) !== '') {
					if (is_null($this->getRootNode()->getNodeById($rootId . $parentId . ':' . $subdir))) {
						$element = $this->createElement($rootId . $parentId . ':' . $subdir,
														 $directoryType,
														 $subdir,
														 '',
														 '');
						$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
						if (($this->hasAttributes(self::SELECTABLE_SUB_FOLDERS))
						&& ($this->hasAttributes(self::MULTISELECT))) {
							$element->addCheckBox('select');
						}
						$this->appendElement($element, $rootId . $parentId);
					}
					$parentId .= ':' . $subdir;
				}
			}
			$element = $this->createElement($fileId,
											 $fileType,
											 bab_toHtml($file['name']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/file.png');
			if (($this->hasAttributes(self::SELECTABLE_FILES))
			&& ($this->hasAttributes(self::MULTISELECT))) {
				$element->addCheckBox('select');
			}
			$this->appendElement($element, $rootId . $parentId);
		}
	}



	/**
	 * Add collective folders.
	 */
	protected function _addCollectiveDirectories($folderId = null)
	{
		global $babDB, $babBody;

		$folders = new BAB_FmFolderSet();

		$oRelativePath = $folders->aField['sRelativePath'];
		$oIdDgOwner = $folders->aField['iIdDgOwner'];
		$oActive = $folders->aField['sActive'];
		$oHide = $folders->aField['sHide'];
		$oId = $folders->aField['iId'];

		$oCriteria = $oRelativePath->in($babDB->db_escape_like(''));
		$oCriteria = $oCriteria->_and($oIdDgOwner->in(array_keys($this->_visibleDelegations)));

		$oCriteria = $oCriteria->_and($oActive->in('Y'));
		
		// hidden directories must be visibles in popup 
		// this functionality allow users to publish files throw articles while the real file in file manager is not visible
		// $oCriteria = $oCriteria->_and($oHide->in('N'));
		
		if (!is_null($folderId)) {
			$oCriteria = $oCriteria->_and($oId->in($folderId));
		}
		$folders->select($oCriteria, array('sName' => 'ASC'));

		$elementType = 'folder';
		if (!($this->hasAttributes(self::MULTISELECT))
		&& $this->hasAttributes(self::SELECTABLE_COLLECTIVE_FOLDERS)) {
			$elementType .= ' clickable';
		}

		while (null !== ($folder = $folders->next()))
		{
			$bManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $folder->getId());
			$bDownload = bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folder->getId());

			if($this->_adminView || $bManager || $bDownload)
			{
				$element = $this->createElement('d' . self::ID_SEPARATOR . $folder->getId().':'.bab_toHtml($folder->getName()),
												 $elementType,
												 bab_toHtml($folder->getName()),
												 '',
												 '');
				if ($this->_updateBaseUrl)
				{
					$element->setFetchContentScript(bab_toHtml("bab_loadSubTree(document.getElementById('li" . $this->_id . '.' . $element->_id .  "'), '" . $this->_updateBaseUrl . "&start=" . $folder->getId().':'.bab_toHtml($folder->getName()) . "')"));
				}
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
				if (($this->hasAttributes(self::SELECTABLE_COLLECTIVE_FOLDERS))
				&& ($this->hasAttributes(self::MULTISELECT))) {
					$element->addCheckBox('select');
				}
				$this->appendElement($element, 'd' . $folder->getDelegationOwnerId());
			}
		}
	}




    /**
     * Add files and subdirectories for a specific collective folder.
     */
    protected function _addCollectiveFiles($folderId = null, $path = '')
    {
        global $babDB, $babBody;
        
        $sEndSlash = (mb_strlen(trim($path)) > 0 ) ? '/' : '' ;

       
       // $rootPath = '';

        $folders = new BAB_FmFolderSet();
        $oId = $folders->aField['iId'];
        
        if ($folderId !== null) {
            $oFolder = $folders->get($oId->in($folderId));
            if (is_a($oFolder, 'BAB_FmFolder')) {
               // $rootPath .= $oFolder->getName() . '/';
                $idDgOwner = $oFolder->getDelegationOwnerId();
            }
        } elseif ($babBody->currentAdmGroup != 0 && ($this->hasAttributes(self::SHOW_ONLY_DELEGATION))) {
        	$idDgOwner = $babBody->currentAdmGroup;
        } else {
        	$idDgOwner = null;
        }

        $aLeftJoin            = array();
        $aWhereClauseItem    = array();
                
        if (isset($idDgOwner))
        {
            $aLeftJoin[]        = 'LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' folder ON file.id_owner=folder.id ';
            $aWhereClauseItem[]    = 'file.bgroup=\'Y\'';
            $aWhereClauseItem[]    = 'folder.id_dgowner = ' . $babDB->quote($idDgOwner);
        }
        else
        {
            $aWhereClauseItem[]    = 'file.bgroup=\'Y\'';
        }
        
        if ($path . $sEndSlash !== '')
        {
            $aWhereClauseItem[]    = 'file.path LIKE ' . $babDB->quote($path . $sEndSlash . '%');
        }

        $aWhereClauseItem[]    = 'file.state<>\'D\'';

        
        $directoryType = 'folder';
        if (!($this->hasAttributes(self::MULTISELECT))
        	&& ($this->hasAttributes(self::SELECTABLE_SUB_FOLDERS))) {
            $directoryType .= ' clickable';
        }
        $groupFileType = 'gfile';
        if (!($this->hasAttributes(self::MULTISELECT))
       		&& ($this->hasAttributes(self::SELECTABLE_FILES))) {
            $groupFileType .= ' clickable';
        }
        
        
        $sWhereClause = '';
        if(count($aWhereClauseItem) > 0)
        {
            $sWhereClause = 'WHERE ' . implode(' AND ', $aWhereClauseItem);
        }
        
        $sQuery =
            'SELECT ' .
                'file.id, ' .
                'file.path, ' .
                'file.name, ' .
                'file.id_owner, ' .
                'file.bgroup ' .
            'FROM ' .
                BAB_FILES_TBL . ' file ' .
	            implode(' ', $aLeftJoin) . ' ' .
    	        $sWhereClause . ' ' .
            'ORDER BY ' .
                'file.path ASC, file.display_position ASC, file.name ASC';

        $files = $babDB->db_query($sQuery);

        $folders = new BAB_FmFolderSet();

        $oRelativePath = $folders->aField['sRelativePath'];
        $oName = $folders->aField['sName'];

        while ($file = $babDB->db_fetch_array($files)) {
           $filePath = removeFirstPath($file['path']);
            //$filePath = $file['path'];
            $subdirs = explode('/', $filePath);

            $fileId = 'g' . self::ID_SEPARATOR . $file['id'];
            $rootFolderName = getFirstPath($file['path']);
            if (is_null($folderId)) {
	            $oCriteria = $oRelativePath->in($babDB->db_escape_like(''));
	            $oCriteria = $oCriteria->_and($oName->in($rootFolderName));
	
	            $folder = $folders->get($oCriteria);
	            if (!$folder) {
	                continue;
	            }
            	$rootId = 'd' . self::ID_SEPARATOR . $folder->getId().':'.bab_toHtml($rootFolderName); // $file['id_owner'];
            } else {
            	$rootId = 'd' . self::ID_SEPARATOR . $folderId.':'.bab_toHtml($oFolder->getName()); // $file['id_owner'];
            }
            $fileType = $groupFileType;

            $parentId = $rootId;

            foreach ($subdirs as $subdir) {
                if (trim($subdir) !== '') {
                    if (is_null($this->getRootNode()->getNodeById($parentId . ':' . bab_toHtml($subdir)))) {
                        $element = $this->createElement($parentId . ':' . bab_toHtml($subdir),
                                                         $directoryType,
                                                         bab_toHtml($subdir),
                                                         '',
                                                         '');
                        $element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
                        if (($this->hasAttributes(self::SELECTABLE_SUB_FOLDERS))
                        && ($this->hasAttributes(self::MULTISELECT))) {
                            $element->addCheckBox('select');
                        }
                        $this->appendElement($element, $parentId);
                    }
                    $parentId .= ':' . bab_toHtml($subdir);
                }
            }
            if ($this->hasAttributes(self::SHOW_FILES)) {
                $element = $this->createElement($fileId,
                                                 $fileType,
                                                 bab_toHtml($file['name']),
                                                 '',
                                                 '');
                $element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/file.png');
                if (($this->hasAttributes(self::SELECTABLE_FILES))
                		&& ($this->hasAttributes(self::MULTISELECT))) {
                    $element->addCheckBox('select');
                }
                $this->appendElement($element, $parentId);
            }
        }
    }
	




	/**
	 * Fill the 'info' of each element with the number of hits from the statistics.
	 *
	 * @param string $start		An iso formatted date 'yyyy-mm-dd'.
	 * @param string $end		An iso formatted date 'yyyy-mm-dd'.
	 */
	public function addStatistics($start, $end)
	{
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->getRootNode()->createNodeIterator($this->getRootNode());
		$iterator->nextNode();
		while ($node = $iterator->nextNode()) {
			(!is_null($node)) && $node->_data->setInfo('0');
		}

		$sql = 'SELECT st_fmfile_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_FMFILES_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= ' . $babDB->quote($start);
			$end && $where[] = 'st_date <= ' .$babDB->quote($end . ' 23:59:59');
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		$files = $babDB->db_query($sql);
		while ($file = $babDB->db_fetch_array($files)) {
			$node = $this->getRootNode()->getNodeById('g' . self::ID_SEPARATOR . $file['id']);
			if (!is_null($node)) {
				$element = $node->getData();
				$element->setInfo($file['hits']);
				$element->setRank((int)$file['hits']);
				$node = $node->parentNode();
				while (!is_null($node)) {
					$element = $node->getData();
					if ($element) {
						$element->setInfo((int)$element->_info + (int)$file['hits']);
						$element->setRank((int)$element->_rank + (int)$file['hits']);
					}
					$node = $node->parentNode();
				}
			}
		}
	}


	protected function _updateTree()
	{
		if ($this->isUpToDate()) {
			return;
		}

		$this->_addVisibleDelegations();

		$this->_addCollectiveDirectories($this->_startFolderId);

		if ($this->hasAttributes(self::SHOW_FILES)
				|| $this->hasAttributes(self::SHOW_SUB_FOLDERS)) {
			$attributes = $this->getAttributes();
			$this->removeAttributes(self::SHOW_FILES);
			//$this->_addCollectiveFiles($this->_startFolderId, $this->_startPath);
			$this->setAttributes($attributes);
			$this->_addCollectiveFiles($this->_startFolderId, $this->_startPath);
		}

		if ($this->hasAttributes(self::SHOW_PERSONAL_FOLDERS)
				&& is_null($this->_startFolderId)) {
			$this->_addPersonalFiles();
		}

		if (!is_null($this->_startFolderId))
		{
			$nodeId = 'd' . self::ID_SEPARATOR . $this->_startFolderId.':'.$this->_startPath;
			$node = $this->getRootNode()->getNodeById($nodeId);
			$this->_iterator = $this->getRootNode()->createNodeIterator($node);
			$this->_iterator->nextNode();
			$this->t_baseLevel = $this->_iterator->level() + 1;
			$this->t_level = 1;
			$this->t_previousLevel = 0;
		}
		parent::_updateTree();
	}

}




/**
 * @deprecated Use corresponding bab_ForumTreeView constant.
 */
define('BAB_FORUM_TREE_VIEW_SHOW_FORUMS',				 0);
/**
 * @deprecated Use corresponding bab_ForumTreeView constant.
 */
define('BAB_FORUM_TREE_VIEW_SHOW_THREADS',				 1);
/**
 * @deprecated Use corresponding bab_ForumTreeView constant.
 */
define('BAB_FORUM_TREE_VIEW_SHOW_POSTS',				 2);
/**
 * @deprecated Use corresponding bab_ForumTreeView constant.
 */
define('BAB_FORUM_TREE_VIEW_SELECTABLE_FORUMS',	 		 4);
/**
 * @deprecated Use corresponding bab_ForumTreeView constant.
 */
define('BAB_FORUM_TREE_VIEW_SELECTABLE_THREADS',	 	 8);
/**
 * @deprecated Use corresponding bab_ForumTreeView constant.
 */
define('BAB_FORUM_TREE_VIEW_SELECTABLE_POSTS',			16);


class bab_ForumTreeView extends bab_TreeView
{
	// Constants used for add/set/get/removeAttributes methods.
	
	/**
	 * Show forum nodes.
	 */
	const SHOW_FORUMS			=    0;

	/**
	 * Show thread nodes (implies SHOW_FORUM).
	 */
	const SHOW_THREADS			=    1;

	/**
	 * Show post nodes (implies SHOW_THREADS).
	 */
	const SHOW_POSTS			=    2;

	/**
	 * Make forum nodes selectable.
	 */
	const SELECTABLE_FORUMS		=    4;

	/**
	 * Make thread nodes selectable.
	 */
	const SELECTABLE_THREADS	=    8;

	/**
	 * Make post nodes selectable.
	 */
	const SELECTABLE_POSTS		=   16;

	/**
	 * Only show administered delegations.
	 * TODO : Has currently no effect
	 */
	const SHOW_ONLY_ADMINISTERED_DELEGATION =  128;
	

	public function __construct($id)
	{
		parent::__construct($id);
		
		$this->addAttributes(self::SHOW_POSTS);
	}

	/**
	 * Add forums to the tree.
	 */
	protected function _addForums()
	{
		global $babDB, $babBody;

		$sql = 'SELECT id, name FROM ' . BAB_FORUMS_TBL;
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' WHERE id_dgowner = ' . $babDB->quote($babBody->currentAdmGroup);
		}
		$sql .= ' ORDER BY ordering';
		
		$forumType = 'forum';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_FORUMS)) {
			$forumType .= ' clickable';
		}
		$rs = $babDB->db_query($sql);
		while ($forum = $babDB->db_fetch_array($rs)) {
			$element = $this->createElement('forum' . self::ID_SEPARATOR . $forum['id'],
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
	 */
	protected function _addThreads()
	{
		global $babDB, $babBody;

		$sql = 'SELECT tt.id, tt.forum FROM ' . BAB_THREADS_TBL. ' tt';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN ' . BAB_FORUMS_TBL . ' ft ON tt.forum = ft.id ' .
					' WHERE ft.id_dgowner = ' . $babDB->quote($babBody->currentAdmGroup);
		}
		$sql .= ' ORDER BY tt.date';
		
		$threadType = 'thread';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_THREADS)) {
			$threadType .= ' clickable';
		}
		$postType = 'post';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_POSTS)) {
			$postType .= ' clickable';
		}
		$threads = $babDB->db_query($sql);
		while ($thread = $babDB->db_fetch_array($threads)) {
			$sql = 'SELECT id, subject, id_parent FROM ' . BAB_POSTS_TBL
				. ' WHERE id_thread = ' . $babDB->quote($thread['id'])
				. ' ORDER BY ' . BAB_POSTS_TBL . '.date';
			
			$posts = $babDB->db_query($sql);
			$firstPost = true;
			while ($post = $babDB->db_fetch_array($posts)) {
				if ($post['id_parent'] === '0') {
					$parentId = 'forum' . self::ID_SEPARATOR . $thread['forum'];
					$elementType = $threadType;
					$iconUrl = $GLOBALS['babSkinPath'] . 'images/nodetypes/thread.png';
				} else {
					$parentId = 'post' . self::ID_SEPARATOR . $post['id_parent'];
					$elementType = $postType;
					$iconUrl = $GLOBALS['babSkinPath'] . 'images/nodetypes/post.png';
				}
				if (($this->hasAttributes(self::SHOW_POSTS)) && $post['id_parent'] !== '0'
				    || $post['id_parent'] === '0') {
					$element = $this->createElement('post' . self::ID_SEPARATOR . $post['id'],
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

	public function addStatistics($start, $end)
	{
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->getRootNode()->createNodeIterator($this->getRootNode());
		$iterator->nextNode();
		while ($node = $iterator->nextNode()) {
			if (!is_null($node)) {
				$node->_data->setInfo('0');
			}
		}

		$sql = 'SELECT st_post_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_POSTS_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= ' . $babDB->quote($start);
			$end && $where[] = 'st_date <= ' . $babDB->quote($end . ' 23:59:59');
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		$posts = $babDB->db_query($sql);
		while ($post = $babDB->db_fetch_array($posts)) {
			$node = $this->getRootNode()->getNodeById('post' . self::ID_SEPARATOR . $post['id']);
			if (!is_null($node)) {
				$element = $node->getData();
				$element->setInfo($post['hits']);
				$element->setRank((int)$post['hits']);
			}
		}

		// For each forum we calculate the total number of hits for all the posts in the forum.

		// We loop over the forum nodes (ie. the siblings of the root node's first child).		
		for ($forumNode = $this->getRootNode()->firstChild(); !is_null($forumNode); $forumNode = $forumNode->nextSibling()) {
			
			if (!is_null($forumNode->_firstChild)) {
				
				$total = 0;
				$iterator = $this->getRootNode()->createNodeIterator($forumNode->_firstChild);
				// We iterate all the nodes under the current forum node and calculate the total hits.
				while ($node = $iterator->nextNode()) {
					if (!is_null($node)) {
						$total += (int)($node->_data->_info);
					}
				}
				$forumNode->_data->setInfo('' . $total);
				$forumNode->_data->setRank($total);
				
			}
		}
	}


	protected function _updateTree()
	{
		if ($this->isUpToDate()) {
			return;
		}
		$this->_addForums();
		if ($this->hasAttributes(self::SHOW_THREADS)
			|| $this->hasAttributes(self::SHOW_POSTS)) {
			$this->_addThreads();
		}
		parent::_updateTree();
	}
}


/**
 * @deprecated Use corresponding bab_FaqTreeView constant.
 */
define('BAB_FAQ_TREE_VIEW_SHOW_CATEGORIES',				 0);
/**
 * @deprecated Use corresponding bab_FaqTreeView constant.
 */
define('BAB_FAQ_TREE_VIEW_SHOW_SUB_CATEGORIES',			 1);
/**
 * @deprecated Use corresponding bab_FaqTreeView constant.
 */
define('BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS',				 2);
/**
 * @deprecated Use corresponding bab_FaqTreeView constant.
 */
define('BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES',		 4);
/**
 * @deprecated Use corresponding bab_FaqTreeView constant.
 */
define('BAB_FAQ_TREE_VIEW_SELECTABLE_SUB_CATEGORIES',	 8);
/**
 * @deprecated Use corresponding bab_FaqTreeView constant.
 */
define('BAB_FAQ_TREE_VIEW_SELECTABLE_QUESTIONS',		16);


class bab_FaqTreeView extends bab_TreeView
{
	// Constants used for add/set/get/removeAttributes methods.

	/**
	 * Show FAQ category nodes.
	 */
	const SHOW_CATEGORIES					=    0;

	/**
	 * Show FAQ sub-category nodes (implies SHOW_CATEGORIES).
	 */
	const SHOW_SUB_CATEGORIES				=    1;

	/**
	 * Show FAQ question nodes (implies SHOW_SUB_CATEGORIES).
	 */
	const SHOW_QUESTIONS					=    2;

	/**
	 * Make FAQ category nodes selectable.
	 */
	const SELECTABLE_CATEGORIES				=    4;

	/**
	 * Make FAQ sub-category nodes selectable.
	 */
	const SELECTABLE_SUB_CATEGORIES			=    8;

	/**
	 * Make FAQ question nodes selectable.
	 */
	const SELECTABLE_QUESTIONS				=   16;

	/**
	 * Only show administered delegations.
	 * TODO : Has currently no effect
	 */
	const SHOW_ONLY_ADMINISTERED_DELEGATION =  128;



	/**#@+
	 * @access private
	 */	
	var $_categories;
	/**#@-*/

	public function __construct($id)
	{
		parent::__construct($id);

		$this->addAttributes(self::SHOW_QUESTIONS);
	}

	/**
	 * Add FAQ categories to the tree.
	 */
	protected function _addCategories()
	{
		global $babDB, $babBody;

		$sql = 'SELECT id, category FROM ' . BAB_FAQCAT_TBL;
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' WHERE id_dgowner = ' . $babDB->quote($babBody->currentAdmGroup);
		}
		$sql .= ' order by category asc';

		$faqcategoryType = 'faqcategory';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_CATEGORIES)) {
			$faqcategoryType .= ' clickable';
		}
		$categories = $babDB->db_query($sql);
		while ($category = $babDB->db_fetch_array($categories)) {
			$element = $this->createElement('category' . self::ID_SEPARATOR . $category['id'],
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

		$subCategories = $babDB->db_query($sql);
		while ($subCategory = $babDB->db_fetch_array($subCategories)) {
			$this->_categories[$subCategory['id']] = $subCategory['id_cat'];
		}
	}


	/**
	 * Add FAQ sub-categories to the tree.
	 */
	protected function _addSubCategories()
	{
		global $babDB, $babBody;

		$sql = 'SELECT ftt.id_parent, fst.* FROM ' . BAB_FAQ_TREES_TBL . ' ftt ,' . BAB_FAQ_SUBCAT_TBL.' fst';

		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FAQCAT_TBL.' ft ON ft.id=fst.id_cat';
		}
		$sql .= ' WHERE';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' id_dgowner = '. $babDB->quote($babBody->currentAdmGroup) . ' AND';
		}
		$sql .= ' ftt.id = fst.id_node AND ftt.id_parent <> 0';
		$sql .= ' ORDER BY ftt.id';

		$faqsubcategoryType = 'faqsubcategory';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_SUB_CATEGORIES)) {
			$faqsubcategoryType .= ' clickable';
		}		
		$subCategories = $babDB->db_query($sql);
		while ($subCategory = $babDB->db_fetch_array($subCategories)) {
			$element = $this->createElement('subcat' . self::ID_SEPARATOR . $subCategory['id'],
											 $faqsubcategoryType,
											 bab_toHtml($subCategory['name']),
											 '',
											 '');
			$parentId = isset($this->_categories[$subCategory['id_parent']])
								? 'category' . self::ID_SEPARATOR . $this->_categories[$subCategory['id_parent']]
								: 'subcat' . self::ID_SEPARATOR . $subCategory['id_parent'];
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			$this->appendElement($element, $parentId);
		}
	}

	/**
	 * Add FAQ questions-answers to the tree.
	 */
	protected function _addQuestions()
	{
		global $babDB, $babBody;

		$sql = 'SELECT fqt.id, fqt.question, fqt.id_subcat FROM ' . BAB_FAQQR_TBL.' fqt';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FAQCAT_TBL.' fct ON fqt.idcat=fct.id WHERE fct.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}

		$questionType = 'faqquestion';
		if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_QUESTIONS)) {
			$questionType .= ' clickable';
		}
		$questions = $babDB->db_query($sql);
		while ($question = $babDB->db_fetch_array($questions)) {
			$element = $this->createElement('question' . self::ID_SEPARATOR . $question['id'],
											 $questionType,
											 bab_toHtml($question['question']),
											 '',
											 '');
			$parentId = isset($this->_categories[$question['id_subcat']])
								? 'category' . self::ID_SEPARATOR . $this->_categories[$question['id_subcat']]
								: 'subcat' . self::ID_SEPARATOR . $question['id_subcat'];
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/faq.png');
			$this->appendElement($element, $parentId);
		}
	}


	public function addStatistics($start, $end)
	{
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->getRootNode()->createNodeIterator($this->getRootNode());
		$iterator->nextNode();
		while ($node = $iterator->nextNode())
			(!is_null($node)) && $node->_data->setInfo('0');		
		
		$sql = 'SELECT st_faqqr_id AS id, SUM(st_hits) AS hits FROM ' . BAB_STATS_FAQQRS_TBL;
		if ($start || $end) {
			$sql .= ' WHERE ';
			$where = array();
			$start && $where[] = 'st_date >= ' . $babDB->quote($start);
			$end && $where[] = 'st_date <= ' . $babDB->quote($end . ' 23:59:59');
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		$faqs = $babDB->db_query($sql);
		while ($faq = $babDB->db_fetch_array($faqs)) {
			$node = $this->getRootNode()->getNodeById('question' . self::ID_SEPARATOR . $faq['id']);
			if (!is_null($node)) {
				$element = $node->getData();
				$element->setInfo($faq['hits']);
				$element->setRank((int)$faq['hits']);
				$node = $node->parentNode();
				while (!is_null($node)) {
					$element = $node->getData();
					if ($element) {
						$element->setInfo((int)$element->_info + (int)$faq['hits']);
						$element->setRank((int)$element->_rank + (int)$faq['hits']);
					}
					$node = $node->parentNode();			
				}
			}
		}
	}


	protected function _updateTree()
	{
		if ($this->isUpToDate())
			return;
		$this->_categories = array();
		$this->_addCategories();
		if (($this->hasAttributes(self::SHOW_SUB_CATEGORIES))
			|| ($this->hasAttributes(self::SHOW_QUESTIONS))) {
			$this->_addSubCategories();
		}
		if ($this->hasAttributes(self::SHOW_QUESTIONS)) {
			$this->_addQuestions();
		}
		parent::_updateTree();
	}
}








class bab_GroupTreeViewElement extends bab_TreeViewElement
{
	/**#@+
	 * @access private
	 */	
	var $_groupId;
	/**#@-*/
	
	/**
	 * @param string $groupId		The group id.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 * @return bab_GroupTreeViewElement
	 */
	public function __construct($groupId, $type, $title, $description, $link)
	{
		parent::__construct($groupId,  $type, $title, $description, $link);
		$this->_groupId = $groupId;
	}


	/**
	 * Returns the group id of the element.
	 *
	 * @return string		The group id of the element.
	 */
	public function getGroupId()
	{
		return $this->_groupId;
	}
	
}




/**
 * @deprecated Use corresponding bab_GroupTreeView constant.
 */
define('BAB_GROUP_TREE_VIEW_SELECTABLE_GROUPS',		 4);

/**
 * A treeview populated by ovidentia groups.
 */
class bab_GroupTreeView extends bab_TreeView
{
	// Constants used for add/set/get/removeAttributes methods.

	/**
	 * Make group nodes selectable.
	 */
	const SELECTABLE_GROUPS		=    4;


	private $_selectedGroups;


	public function __construct($id)
	{
		parent::__construct($id);
		$this->t_isMultiSelect = true;
	}

	/**
	 * Overloaded from bab_TreeView.
	 * 
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 * 
	 * @return bab_GroupTreeViewElement
	 */
	public function &createElement($id, $type, $title, $description, $link)
	{
		$element =new bab_GroupTreeViewElement($id, $type, $title, $description, $link);
		return $element;
	}


	/**
	 * Overloaded from bab_TreeView.
	 * 
	 * Appends $element as the last child of the element with the id $parentId.
	 * If $parentId is null, the element will appear as a first level node.
	 * 
	 * @param bab_TreeViewElement	$element	An element created by the method createElement.
	 * @param string 				$parentId	The id of the parent element.
	 * @access public
	 */
	public function appendElement($element, $parentId)
	{
		parent::appendElement($element, $parentId);
		$groupId = $element->getGroupId();
		if ($groupId !== '' && ($this->hasAttributes(self::MULTISELECT))) {
			$element->addCheckBox('select[' . $groupId . ']', isset($this->_selectedGroups[$groupId]));
		}
	}



	/**
	 * Preselect groups in the treeview.
	 *
	 * @param array $groups		An array indexed by group ids (group ids are in the key)
	 */
	public function selectGroups($groups)
	{
		$this->_selectedGroups = $groups;
	}


	protected function _addGroups()
	{
		include_once $GLOBALS['babInstallPath']. 'utilit/grptreeincl.php';

		$tree = new bab_grptree();
		$groups = $tree->getGroups(BAB_ALLUSERS_GROUP, '');

		foreach ($groups as $group) {
			if ($group['id'] <= BAB_ADMINISTRATOR_GROUP) {
				$groupName = bab_translate($group['name']);
			} else {
				$groupName = $group['name'];
			}
			$groupType = 'group';
			if (!($this->hasAttributes(self::MULTISELECT))
					&& $this->hasAttributes(self::SELECTABLE_GROUPS)) {
				$groupType .= ' clickable';
			}

			$element = $this->createElement('group' . self::ID_SEPARATOR . $group['id'],
											 $groupType,
											 bab_toHtml($groupName),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
			$parentId = (BAB_REGISTERED_GROUP === (int) $group['id'] ? NULL : 'group' . self::ID_SEPARATOR . $group['id_parent']);
			
			$this->appendElement($element, $parentId);
		}

	}


	protected function _updateTree()
	{
		if ($this->isUpToDate()) {
			return;
		}
		$this->_addGroups();

		parent::_updateTree();
	}
}
