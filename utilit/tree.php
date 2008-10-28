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
include_once $GLOBALS['babInstallPath'].'utilit/orgchart.php';



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
	function bab_TreeViewElement($id, $type, $title, $description, $link)
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
	 * @access public
	 */
	function setFetchContentScript($url)
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
	 * @access public 
	 */
	function addAction($name, $caption, $icon, $link, $script, $scriptArgs = array('this'))
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
	 * @access public 
	 */
	function addMenu($name, $caption, $icon)
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
	 * @access public
	 */
	function addMenuAction($menuName, $actionName, $caption, $icon, $link, $script)
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
	 * @access public
	 */
	function addMenuSeparator($menuName)
	{
		$this->_menus[$menuName]['actions'][] = array('name' => '-');
	}

	/**
	 * Adds a checkbox to the treeview element.
	 * 
	 * @param string $name
	 * @param boolean $check		True to check the box.
	 * @param string $script		The script to execute on click.
	 * @access public
	 */
	function addCheckBox($name, $check = false, $script = '')
	{
		$this->_checkBoxes[] = array('name' => $name, 'checked' => $check, 'script' => $script);
	}

	/**
	 * Defines an info text that will appear on the right of the treeview element title.
	 * 
	 * @param string $text
	 * @access public
	 */
	function setInfo($text)
	{
		$this->_info = $text;
	}

	/**
	 * Defines a tooltip text that will appear when hovering the icon and label of the element.
	 * 
	 * @param string $text
	 * @access public
	 */
	function setTooltip($text)
	{
		$this->_tooltip = $text;
	}

	/**
	 * Defines the rank of the treeview element (that can be used by the compare and sort methods).
	 * 
	 * @param int $rank
	 * @access public
	 */
	function setRank($rank)
	{
		$this->_rank = $rank;
	}

	/**
	 * Defines the url link when the element is clicked.
	 * 
	 * @param string $url
	 * @access public
	 */
	function setLink($url)
	{
		$this->_link = $url;
	}

	/**
	 * Defines the url of the treeview element icon.
	 * 
	 * @param string $url
	 * @access public
	 */
	function setIcon($url)
	{
		$this->_icon = $url;
	}

	/**
	 * Defines the url of the subTree (the url should provide the content of the subTree to be inserted).
	 * The url will be called when the TreeViewElement is expanded.
	 * 
	 * @param string $url
	 * @access public
	 */
	function setSubTree($url)
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
	function compare(&$element)
	{
		$diff = (int)$element->_rank - (int)$this->_rank;
		if ($diff === 0) {
			return strcasecmp($this->_title, $element->_title);
		}
		return $diff;
	}

}



define('BAB_TREE_VIEW_ID_SEPARATOR',	'__');

define('BAB_TREE_VIEW_COLLAPSED',			1);
define('BAB_TREE_VIEW_EXPANDED',			2);

define('BAB_TREE_VIEW_MULTISELECT',			1024);
define('BAB_TREE_VIEW_MEMORIZE_OPEN_NODES',	2048);

/**
 * A TreeView widget used to display hierarchical data.
 */
class bab_TreeView
{
	/**#@+
	 * @access private
	 */
	var $_id;
	/**
	 * @var bab_OrphanRootNode
	 */
	var $_rootNode;
	var $_iterator;
	
	var $_highlightedElements;

	var $_upToDate;
	
	var $_attributes;

	var $t_treeViewId;
	var $t_id;
	var $t_previousId;
	var $t_type;
	var $t_title;
	var $t_description;
	var $t_link;
	var $t_levelVariation;
	var $t_level;
	var $t_previousLevel;
	var $t_offsetLevel;
	var $t_offsetPreviousLevel;
	var $t_baseLevel;
	
	var $t_isFirstChild;
	var $t_isMiddleChild;
	var $t_isSingleChild;
	var $t_isLastChild;
	
	var $t_info;
	var $t_tooltip;
	var $t_showRightElements;

	var $t_nodeIcon;

	var $t_classes;

	var $t_expand;
	var $t_collapse;
	var $t_submit;
	
	var $t_highlighted;
	
	var $t_loading;

	var $t_id_separator;

	var $t_isMultiSelect;
	var $t_memorizeOpenNodes;
	
	var $t_subtree;

	var $_currentElement;

	var $_templateFile;
	var $_templateSection;
	var $_templateCss;
	var $_templateScripts;
	var $_templateCache;
	
	/**
	 * @param string $id	A unique treeview id in the page.
	 * 						Must begin with a letter ([A-Za-z]) and may be followed by any
	 * 						number of letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
	 * 						colons (":"), and periods (".").
	 * 
	 * @return bab_TreeView
	 * @access public
	 */
	function bab_TreeView($id)
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

		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCss = 'treeview_css';
		$this->_templateScripts = 'treeview_scripts';
		$this->_templateCache = null;

		$this->t_id_separator = BAB_TREE_VIEW_ID_SEPARATOR;

		$this->t_subtree = null;

		$this->_upToDate = false;

		$this->t_memorizeOpenNodes = true;
		$this->t_isMultiSelect = false;
		$this->t_fetchContentScript = false;
	}


	/**
	 * Sets additional classes for use in css stylesheets.
	 *
	 * @param string $classes		Space separated list of classes.
	 */
	function setClasses($classes)
	{
		$this->t_classes = $classes;
	}


	/**
	 * Defines the attributes of the treeview.
	 * 
	 * @param int $attributes
	 * @access public
	 */
	function setAttributes($attributes)
	{
		$this->_attributes = $attributes;
		$this->_invalidateCache();
		$this->t_isMultiSelect = (($attributes & BAB_TREE_VIEW_MULTISELECT) !== 0);
		$this->t_memorizeOpenNodes = (($attributes & BAB_TREE_VIEW_MEMORIZE_OPEN_NODES) !== 0);
	}

	/**
	 * Returns the attributes of the treeview.
	 * 
	 * @return int
	 * @access public
	 */
	function getAttributes()
	{
		return $this->_attributes;
	}
	
	/**
	 * Adds attributes to the treeview.
	 * 
	 * @param int $attributes
	 * @access public
	 */
	function addAttributes($attributes)
	{		
		$this->setAttributes($this->getAttributes() | $attributes);
	}

	/**
	 * Adds attributes to the treeview.
	 * 
	 * @param int $attributes
	 * @access public
	 */
	function removeAttributes($attributes)
	{		
		$this->setAttributes($this->getAttributes() & ~$attributes);
	}


	/**
	 * @param string $id			A unique element id in the treeview.
	 * @param string $type			Will be used as a css class to style the element.
	 * @param string $title			The title (label) of the node.
	 * @param string $description	An additional description that will appear as a tooltip.
	 * @param string $link			A link when clicking the node title.
	 * 
	 * @return bab_TreeViewElement
	 * @access public
	 */
	function &createElement($id, $type, $title, $description, $link)
	{
		$element =& new bab_TreeViewElement($id, $type, $title, $description, $link);
		return $element;
	}


	/**
	 * Appends $element as the last child of the element with the id $parentId.
	 * If $parentId is null, the element will appear as a first level node.
	 * 
	 * @param bab_TreeViewElement	$element	An element created by the method createElement.
	 * @param string 				$parentId	The id of the parent element.
	 * @access public
	 */
	function appendElement(&$element, $parentId)
	{
		$node =& $this->_rootNode->createNode($element, $element->_id);
		$this->_rootNode->appendChild($node, $parentId);
		$this->_upToDate = false;
		$this->onElementAppended($element, $parentId);
	}

	/**
	 * Sorts the TreeView.
	 * 
	 * Siblings of the same branch are ordered.
	 * Ordering is performed using the bab_TreeViewElement::compare() method.
	 *
	 * @access public
	 */
	function sort()
	{
//		$this->_updateTree();
		$this->_invalidateCache();
		$this->_rootNode->sortSubTree();
	}

	
	function highlightElement($id)
	{
		$this->_highlightedElements[$id] = true;
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
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;
			$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;
		}
		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
		if ($this->t_levelVariation < -1) {
//			$this->t_previousLayout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');
			$this->t_previousLevel--;
			$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;
//			$this->t_layout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');
			return true;
		}

//		$this->t_previousLayout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');
		
		$this->t_previousLevel = $this->t_level;
		$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;

//		$this->t_layout = ($this->t_previousLevel >= 3 ? 'vertical' : 'horizontal');

		if ($node =& $this->_iterator->nextNode()) {
			$this->t_isFirstChild = $node->isFirstChild();
			$this->t_isLastChild = $node->isLastChild();
			$this->t_isMiddleChild = (!$node->isFirstChild() && !$node->isLastChild());
			$this->t_isSingleChild = ($node->isFirstChild() && $node->isLastChild());

			$this->t_level = $this->_iterator->level();
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;
			$element =& $node->getData();
			$this->t_fetchContentScript = $element->_fetchContentScript;
			$this->t_highlighted = isset($this->_highlightedElements[$element->_id]);
			$this->t_previousId = isset($this->t_id) ? $this->t_id : '';
			$this->t_id = $this->_id . '.' . $element->_id;
			$this->t_type =& $element->_type;
			$this->t_title =& $element->_title;
			$this->t_description =& $element->_description;
			$this->t_link =& $element->_link;
			$this->t_info =& $element->_info;
			$this->t_tooltip =& $element->_tooltip;
			$this->t_nodeIcon =& $element->_icon;
			$this->_currentElement =& $element;
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
	
	function getNextAction()
	{
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

	function getNextMenu()
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

	function getNextMenuAction()
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

	function getNextCheckBox()
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
	function _invalidateCache()
	{
		$this->_templateCache = null;
	}

	/**
	 * @access protected
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
			if (!$this->_upToDate) {
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
	function printSubTree()
	{
		if (!$this->_upToDate)
			$this->_updateTree();
		$this->_templateCache .= bab_printTemplate($this, $this->_templateFile, 'subtree');
		return $this->_templateCache;
	}

	/**#@+
	 * Overridable event method. 
	 */

	/**
	 * This method is called after the bab_TreeViewElement $element has been appended to the treeview. 
	 *
	 * @param bab_TreeViewElement $element
	 * @param string $parentId
 	 */
	function onElementAppended(&$element, $parentId)
	{
	}

	/**#@-*/

}







//class bab_OrgChartElement extends bab_TreeViewElement
//{
//	/**#@+
//	 * @access private
//	 */	
//	var $_members;
//	var $_linkEntity;
//	/**#@-*/
//	
//	/**
//	 * @param string $id			A unique element id in the treeview.
//	 * @param string $type			Will be used as a css class to style the element.
//	 * @param string $title			The title (label) of the node.
//	 * @param string $description	An additional description that will appear as a tooltip.
//	 * @param string $link			A link when clicking the node title.
//	 */
//	function bab_OrgChartElement($id, $type, $title, $description, $link)
//	{
//		parent::bab_TreeViewElement($id, $type, $title, $description, $link);
//		$this->_members = array();
//	}
//
//	
//	function addMember($memberName, $role = '')
//	{
//		if (!isset($this->_members[$role])) {
//			$this->_members[$role] = array();
//		}
//		$this->_members[$role][] = $memberName;
//	}
//
//	/**
//	 * Defines the url link when the entity is clicked.
//	 * @param string $url
//	 */
//	function setLinkEntity($url)
//	{
//		$this->_linkEntity = $url;
//	}
//
//}
//
//
//
//
//
//
//class bab_OrgChart extends bab_TreeView
//{
//	/**#@+
//	 * @access private
//	 */	
//	var $_verticalThreshold;
//	var $_startLevel;
//	
//	var $_openNodes;
//	var $_openMembers;
//
//	var $t_zoomFactor;
//	
//	var $t_nodeId;
//	var $t_layout;
//	var $t_previousLayout;
//	
//	var $t_nbMembers;
//	var $t_memberName;
//
//	var $t_linkEntity;
//	/**#@-*/
//	
//	
//	/**
//	 * @param string 	$id		A unique treeview id in the page.
//	 * 							Must begin with a letter ([A-Za-z]) and may be followed
//	 * 							by any number of letters, digits ([0-9]), hyphens ("-"),
//	 * 							underscores ("_"), colons (":"), and periods (".").
//	 * @param int		$startLevel
//	 * @return bab_OrgChart
//	 * @access public
//	 */
//	function bab_OrgChart($id, $startLevel = 0)
//	{
//		parent::bab_TreeView($id);
//		$this->_verticalThreshold = 4;
//		$this->_startLevel = $startLevel;
//		$this->_templateFile = 'treeview.html';
//		$this->_templateSection = 'orgchart';
//		$this->_templateCss = 'orgchart_css';
//		$this->_templateScripts = 'orgchart_scripts';
//		$this->_openNodes = array();
//		$this->_openMembers = array();
//		$this->_zoomFactor = 1.0;
//
//		$this->t_fit_width = bab_translate('Fit width');
//		$this->t_visible_levels = bab_translate('Visible levels');
//		$this->t_visible_levels_tip = bab_translate('Only show n first levels of the org chart');
//		$this->t_zoom_in = bab_translate('Zoom in');
//		$this->t_zoom_out = bab_translate('Zoom out');
//		$this->t_default_view = bab_translate('Default view');
//		$this->t_save_default_view = bab_translate('Save default view');
//		$this->t_print = bab_translate('Print');
//		$this->t_help = bab_translate('Help');
//	}
//
//	/**
//	 * @param string $id			A unique element id in the treeview.
//	 * @param string $type			Will be used as a css class to style the element.
//	 * @param string $title			The title (label) of the node.
//	 * @param string $description	An additional description that will appear as a tooltip.
//	 * @param string $link			A link when clicking the node title.
//	 * @return bab_OrgChartElement
//	 */
//	function &createElement($id, $type, $title, $description, $link)
//	{
//		$element =& new bab_OrgChartElement($id, $type, $title, $description, $link);
//		return $element;
//	}
//
//	
//	function setOpenNodes($openNodes)
//	{
//		$this->_openNodes = $openNodes;
//		reset($this->_openNodes);
//	}
//
//
//	function setOpenMembers($openMembers)
//	{
//		$this->_openMembers = $openMembers;
//		reset($this->_openMembers);
//	}
//
//	function setZoomFactor($zoomFactor)
//	{
//		$this->t_zoomFactor = $zoomFactor;
//	}
//	/**
//	 * Defines the depth level from which the org chart branches are displayed vertically.
//	 * @access public
//	 */
//	function setVerticalThreshold($threshold)
//	{
//		$this->_verticalThreshold = $threshold;		
//	}
//
//	/**#@+
//	 * Template methods.
//	 * @ignore
//	 */	
//	
//	function getNextOpenNode()
//	{
//		while (list(, $nodeId) = each($this->_openNodes)) {
//			$this->t_nodeId = $nodeId;
//			return true;	
//		}
//		reset($this->_openNodes);
//		return false;
//	}
//
//	function getNextOpenMember()
//	{
//		while (list(, $nodeId) = each($this->_openMembers)) {
//			$this->t_memberId = $nodeId;
//			return true;	
//		}
//		reset($this->_openMembers);
//		return false;
//	}
//
//	function getNextElement()
//	{
//		$verticalThreshold = $this->_verticalThreshold - $this->_startLevel;
//		
//		if (is_null($this->_iterator)) {
//			$this->_iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
//			$this->_iterator->nextNode();
//			$this->t_level = $this->_iterator->level();
//			$this->t_previousLevel = $this->t_level - 1;
//		}
//		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
//		if ($this->t_levelVariation < -1) {
//			$this->t_previousLayout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
//			$this->t_previousLevel--;
//			$this->t_layout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
//			return true;
//		}
//
//		$this->t_previousLayout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
//		
//		$this->t_previousLevel = $this->t_level;
//
//		$this->t_layout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
//
//		if ($node =& $this->_iterator->nextNode()) {
//			$this->t_isFirstChild = $node->isFirstChild();
//			$this->t_isLastChild = $node->isLastChild();
//			$this->t_isMiddleChild = (!$node->isFirstChild() && !$node->isLastChild());
//			$this->t_isSingleChild = ($node->isFirstChild() && $node->isLastChild());
//			
//				
//			$this->t_level = $this->_iterator->level();
//			$this->t_total_level = $this->t_level + $this->_startLevel;
//			$element =& $node->getData();
//			$this->t_id = $this->_id . '.' . $element->_id;
//			$this->t_type =& $element->_type;
//			$this->t_title =& $element->_title;
//			$this->t_description =& $element->_description;
//			$this->t_link =& $element->_link;
//			$this->t_linkEntity =& $element->_linkEntity;
//			$this->t_info =& $element->_info;
//			$this->t_nodeIcon =& $element->_icon;
//			$this->_currentElement =& $element;
//			reset($this->_currentElement->_actions);
//			reset($this->_currentElement->_members);
//			$this->t_nbMembers = count($this->_currentElement->_members);
//			
//			$this->t_showRightElements = ($element->_info != '')
//							|| (count($this->_currentElement->_actions) > 0)
//							|| (count($this->_currentElement->_checkBoxes) > 0);
//			return true;
//		}
//		if ($this->t_level > -1) {
//			$this->t_level = -1;
//			return $this->getNextElement();
//		}
//		$this->_iterator = null;
//		return false;
//	}
//
//	function getNextMemberRole()
//	{
//		if (list($memberRole, ) = each($this->_currentElement->_members)) {
//			$this->t_memberRole = $memberRole;
//			$this->_members =& $this->_currentElement->_members[$memberRole];
//			reset($this->_members);
//			return true;
//		}
//		reset($this->_currentElement->_members);
//		return false;
//	}
//
//	function getNextMemberName()
//	{
//		if (list(,$memberName) = each($this->_members)) {
//			$this->t_memberName = $memberName;
//			return true;
//		}
//		reset($this->_members);
//		return false;
//	}
//	/**#@-*/
//}










define('BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES',						 0);
define('BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS',							 1);
define('BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES',			 			 2);
define('BAB_ARTICLE_TREE_VIEW_HIDE_EMPTY_TOPICS_AND_CATEGORIES',	 4);
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES',				 8);
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS',					16);
define('BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES',					32);
define('BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE',						64);
define('BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS',					128);

define('BAB_ARTICLE_TREE_VIEW_READ_ARTICLES',						 1);
define('BAB_ARTICLE_TREE_VIEW_SUBMIT_ARTICLES',						 2);
define('BAB_ARTICLE_TREE_VIEW_MODIFY_ARTICLES',						 3);
define('BAB_ARTICLE_TREE_VIEW_SUBMIT_COMMENTS',						 4);
define('BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC',						 5);

class bab_ArticleTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_action;
	var $_link;
	
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


	function bab_ArticleTreeView($id)
	{
		parent::bab_TreeView($id);

		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'treeview';
		$this->_templateCache = null;

		$this->setLink('');

		$this->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES | BAB_ARTICLE_TREE_VIEW_READ_ARTICLES | BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE);
		$this->setAction(BAB_ARTICLE_TREE_VIEW_READ_ARTICLES);
	}

	
	/**
	 * Defines the action for which the article tree is displayed.
	 * 
	 * The treeview will only display the topics for which the
	 * current user is allowed to perform the selected action.
	 * Possible values for $action are:
	 *  - BAB_ARTICLE_TREE_VIEW_READ_ARTICLES
	 *  - BAB_ARTICLE_TREE_VIEW_SUBMIT_ARTICLES
	 *  - BAB_ARTICLE_TREE_VIEW_MODIFY_ARTICLES
	 *  - BAB_ARTICLE_TREE_VIEW_SUBMIT_COMMENTS
	 *  - BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC
	 *
	 * @param int $action
	 * @access public
	 */
	function setAction($action)
	{
		$this->_action = $action;
	}

	/**
	 * Defines the script that will be called 
	 * @param int $link
	 * @access public
	 */
	function setLink($link)
	{
		$this->_link = $link;
	}
	
	
	/**
	 * @return string	SQL query
	 */
	function getQueryByRight($tablename) {
	
		global $babDB;
	
	
		$where = array();
		$sql = 'SELECT topics.id, topics.id_cat, topics.description, topics.category';
		$sql .= ' FROM ' . BAB_TOPICS_TBL . ' topics';
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS) {
			$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS categories ON topics.id_cat=categories.id';
			$where[] = 'categories.id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
		}
		$where[] = 'topics.id IN (' . $babDB->quote(array_keys(bab_getUserIdObjects($tablename))) . ')';
		$sql .= ' WHERE ' . implode(' AND ', $where);
		
		return $sql;
	}
	
	

	/**
	 * Add article topics to the tree.
	 * @access private
	 */
	function _addTopics()
	{
		global $babDB, $babBody;

		$sql = '';
		switch ($this->_action)
		{
			case BAB_ARTICLE_TREE_VIEW_MODIFY_ARTICLES:
			
				$topsub = bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL);
				$topman = bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL);
				$topmod = bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL);
			
				if (count($topsub) > 0  || count($topman) > 0 || count($topmod) > 0)
				{
					if (count($babBody->topsub) > 0)
						$tmp[] = '(topics.id IN (' . $babDB->quote(array_keys($topsub)) . ") AND topics.allow_update != '0')";
					if( count($babBody->topman) > 0 )
						$tmp[] = '(topics.id IN (' . $babDB->quote(array_keys($topman)) . ") AND topics.allow_manupdate != '0')";
					if( count($babBody->topmod) > 0 )
						$tmp[] = '(topics.id IN (' . $babDB->quote(array_keys($topmod)) . '))';
					$sql = 'SELECT DISTINCT topics.id, topics.id_cat, topics.description, topics.category'
						. ' FROM ' . BAB_ARTICLES_TBL . ' AS articles'
						. ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topics ON topics.id = articles.id_topic'
						. ' WHERE articles.archive=\'N\' AND ' . implode(' OR ', $tmp);
				}
				break;

			case BAB_ARTICLE_TREE_VIEW_SUBMIT_ARTICLES:
				$sql = $this->getQueryByRight(BAB_TOPICSSUB_GROUPS_TBL);
				break;
				
			case BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC:
				$sql = $this->getQueryByRight(BAB_TOPICSMAN_GROUPS_TBL);
				break;


			case BAB_ARTICLE_TREE_VIEW_READ_ARTICLES:
				$sql = $this->getQueryByRight(BAB_TOPICSVIEW_GROUPS_TBL);
				break;
				
			// list topic by submit comments right seem to be not very usefull
			// case BAB_ARTICLE_TREE_VIEW_SUBMIT_COMMENTS:
				
				
				
			// admin rights view of topics (view all topics by delegation)
			default:
				$sql = 'SELECT topics.id, topics.id_cat, topics.description, topics.category'
				    . ' FROM ' . BAB_TOPICS_TBL . ' AS topics';
				if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS) {
					$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS categories ON topics.id_cat=categories.id';
					$sql .= ' WHERE categories.id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
				}
				break;
		}
		
		if ($sql !== '')
		{
			$elementType = 'topic';
			if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_ARTICLE_TREE_VIEW_SELECTABLE_TOPICS) {
				$elementType .= ' clickable';
			}
			$topics = $babDB->db_query($sql);
			while ($topic = $babDB->db_fetch_array($topics)) {
				if ($this->_link !== '') {
					$link = sprintf($this->_link, $topic['id']);
				} else {
					$link = '';
				}
				$element =& $this->createElement('t' . BAB_TREE_VIEW_ID_SEPARATOR . $topic['id'],
												 $elementType,
												 bab_toHtml($topic['category']),
												 ''/*$topic['description']*/,
												 $link);
				
				
				if (BAB_ARTICLE_TREE_VIEW_MANAGE_TOPIC === $this->_action) {
				
					list($nbarticles)= $babDB->db_fetch_row($babDB->db_query("select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topic['id'])."' and archive='N'"));
				
					list($nbarcharticles) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topic['id'])."' and archive='Y'"));
				
					$element->setInfo(sprintf(bab_translate('%d Online article(s) | %d Old article(s)'), $nbarticles, $nbarcharticles));
					
				}
				
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/topic.png');
				$parentId = ($topic['id_cat'] === '0' ? null :
													'c' . BAB_TREE_VIEW_ID_SEPARATOR . $topic['id_cat']);
				$this->_datas = $topic;
				$this->appendElement($element, $parentId);
				$this->_datas = null;
			}
		}
	}

	/**
	 * Add article categories to the tree.
	 * @access private
	 */
	function _addCategories()
	{
		global $babBody;
		global $babDB;

		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE) {
			if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES) {
				$label = bab_translate("Categories, topics and articles");
			} else if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS) {
				$label = bab_translate("Categories and topics");
			} else {
				$label = bab_translate("Categories");
			}
			$element =& $this->createElement('c' . BAB_TREE_VIEW_ID_SEPARATOR . '0',
											 'categoryroot',
											 $label,
											 '',
											 '');
			$element->setInfo('');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');
			$this->appendElement($element, null);
		}
		
		$sql = 'SELECT id, title, description, id_parent, enabled FROM ' . BAB_TOPICS_CATEGORIES_TBL;
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS) {
			$sql .= ' WHERE id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
		}
		$elementType = 'category';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_ARTICLE_TREE_VIEW_SELECTABLE_CATEGORIES) {
			$elementType .= ' clickable';
		}
		$categories = $babDB->db_query($sql);
		while ($category = $babDB->db_fetch_array($categories)) {
			$element =& $this->createElement('c' . BAB_TREE_VIEW_ID_SEPARATOR . $category['id'],
											 $elementType,
											 bab_toHtml($category['title']),
											 '',
											 '');
			$element->setInfo('');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/category.png');
			if (!($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE) && $category['id_parent'] === '0') {
				$parentId = null;
			} else {
				$parentId = 'c' . BAB_TREE_VIEW_ID_SEPARATOR . $category['id_parent'];
			}
			$this->_datas = $category;
			$this->appendElement($element, $parentId);
			$this->_datas = null;
		}
	}

	/**
	 * Add articles to the tree.
	 * @access private
	 */
	function _addArticles()
	{
		global $babDB, $babBody;
		
		$sql = 'SELECT articles.id, articles.title, articles.id_topic FROM ' . BAB_ARTICLES_TBL.' articles';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_TOPICS_TBL.' topics ON articles.id_topic=topics.id';
			$sql .= ' LEFT JOIN '.BAB_TOPICS_CATEGORIES_TBL.' categories ON topics.id_cat=categories.id';
			$sql .= ' WHERE id_dgowner=' . $babDB->quote($babBody->currentAdmGroup);
		}
		$elementType = 'article';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_ARTICLE_TREE_VIEW_SELECTABLE_ARTICLES) {
			$elementType .= ' clickable';
		}
		$rs = $babDB->db_query($sql);
		while ($article = $babDB->db_fetch_array($rs)) {
			$element =& $this->createElement('a' . BAB_TREE_VIEW_ID_SEPARATOR . $article['id'],
											 $elementType,
											 bab_toHtml($article['title']),
											 '',
											 '');
			$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/article.png');
			$this->_datas = $article;
			$this->appendElement($element, 't' . BAB_TREE_VIEW_ID_SEPARATOR . $article['id_topic']);
			$this->_datas = null;
		}
	}

	
	/**
	 * Gives a rank to each element of the treeview as specified in the
	 * articles/topics/categories administration.
	 * 
	 * A call to this method does not actually reorder the tree. It should be
	 * followed by a call to {@link sort} in order to do so.
	 * 
	 * @access public
	 */
	function order()
	{
		global $babDB;

		$this->_updateTree();
		$sql = 'SELECT id_topcat, type, ordering FROM ' . BAB_TOPCAT_ORDER_TBL;

		$orders = $babDB->db_query($sql);
		while ($order = $babDB->db_fetch_array($orders)) {
			if ($order['type'] == 2) {
				$node =& $this->_rootNode->getNodeById('t' . BAB_TREE_VIEW_ID_SEPARATOR . $order['id_topcat']);
			} else {
				$node =& $this->_rootNode->getNodeById('c' . BAB_TREE_VIEW_ID_SEPARATOR . $order['id_topcat']);
			}
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setRank(0x7FFFFFFF - $order['ordering']);
			}
		}
	}


	function addStatistics($start, $end)
	{
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
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
			$node =& $this->_rootNode->getNodeById('a' . BAB_TREE_VIEW_ID_SEPARATOR . $article['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($article['hits']);
				$element->setRank((int)$article['hits']);
				$node =& $node->parentNode();
				while (!is_null($node)) {
					$element =& $node->getData();
					if ($element) {
						$element->setInfo((int)$element->_info + (int)$article['hits']);
						$element->setRank((int)$element->_rank + (int)$article['hits']);
					}
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
		if ($this->_upToDate) {
			return;
		}
		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS
			|| $this->_attributes & BAB_ARTICLE_TREE_VIEW_SHOW_ARTICLES)
			$this->_addTopics();


		$this->_addCategories();

		if ($this->_attributes & BAB_ARTICLE_TREE_VIEW_HIDE_EMPTY_TOPICS_AND_CATEGORIES) {
			// Here we remove empty categories
			do {
				$iterator =& $this->_rootNode->createNodeIterator($this->_rootNode);
				$deadBranches = array();
				while ($node =& $iterator->nextNode()) {
					$element =& $node->getData();
					if (!$node->hasChildNodes() && isset($element->_type) && strstr($element->_type, 'category'))
						$deadBranches[] =& $node;
				}
				$modified = (count($deadBranches) > 0);
				reset($deadBranches);
				foreach (array_keys($deadBranches) as $deadBranchKey) {
					$deadBranch =& $deadBranches[$deadBranchKey];
					$parentNode =& $deadBranch->parentNode();
					if ($parentNode) {
						$parentNode->removeChild($deadBranch);
					}
				}
			} while ($modified);
		}

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
define('BAB_FILE_TREE_VIEW_SHOW_ONLY_DELEGATION',				64);

class bab_FileTreeView extends bab_TreeView
{
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


	function bab_FileTreeView($id, $adminView = true)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		require_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
		parent::bab_TreeView($id);

		$this->_attributes = BAB_FILE_TREE_VIEW_SHOW_FILES;

		$this->_adminView = $adminView;

		$this->setStartPath(null, '');
		$this->setUpdateBaseUrl('');

		$this->_directories = array();
	}

	
	
	function setStartPath($folderId, $path)
	{
		$this->_startFolderId = $folderId;
		$this->_startPath = $path;
	}


	function setUpdateBaseUrl($url)
	{
		$this->_updateBaseUrl = $url;
	}


	/**
	 * 
	 * @access private
	 */
	function _addVisibleDelegations()
	{
		global $babBody;

		$this->_visibleDelegations = bab_getUserFmVisibleDelegations();

		
		// When the tree is displayed for administrative purpose, we only
		// display the currently administered delegation.
		if ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_ONLY_DELEGATION)
		{
			$this->_visibleDelegations = array($babBody->currentAdmGroup => $this->_visibleDelegations[$babBody->currentAdmGroup]);
		}

		// We create a first-level node for each visible delegation.
		foreach ($this->_visibleDelegations as $delegationId => $delegationName)
		{
			$element =& $this->createElement('d' . $delegationId,
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
	 * @access private
	 */
	function _addPersonalFiles()
	{
		require_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
		global $babDB, $babBody;

		$rootPath = '';

		$sql = 'SELECT file.id, file.path, file.name, file.id_owner, file.bgroup '
			 . ' FROM ' . BAB_FILES_TBL . ' file'
			 . ' WHERE file.bgroup=\'N\' AND file.id_owner=' . $babDB->quote($GLOBALS['BAB_SESS_USERID'])
			 . ' AND file.state <> \'D\''
			 . ' ORDER BY file.name';

		$directoryType = 'folder';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
		&& $this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES) {
			$directoryType .= ' clickable';
		}
		$personalFileType = 'pfile';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
		&& $this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_FILES) {
			$personalFileType .= ' clickable';
		}
		$files = $babDB->db_query($sql);


		$folders = new BAB_FmFolderSet();

		$oRelativePath =& $folders->aField['sRelativePath'];
		$oName =& $folders->aField['sName'];

		while ($file = $babDB->db_fetch_array($files)) {

			$filePath = $file['path'];
			$subdirs = explode('/', $filePath);
				
			$fileId = 'p' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id'];
			$fileType =& $personalFileType;
			$rootId = 'pd' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id_owner'];
			if (is_null($this->_rootNode->getNodeById($rootId))) {
				$element =& $this->createElement($rootId,
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
					if (is_null($this->_rootNode->getNodeById($rootId . $parentId . ':' . $subdir))) {
						$element =& $this->createElement($rootId . $parentId . ':' . $subdir,
														 $directoryType,
														 $subdir,
														 '',
														 '');
						$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
						if (($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES)
						&& ($this->_attributes & BAB_TREE_VIEW_MULTISELECT)) {
							$element->addCheckBox('select');
						}
						$this->appendElement($element, $rootId . $parentId);
					}
					$parentId .= ':' . $subdir;
				}
			}
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
			$this->appendElement($element, $rootId . $parentId);
		}
	}

	function _getFolderIdDgOwner($folderId)
	{
		
	}

	/**
	 * Add collective folders.
	 * @access private
	 */
	function _addCollectiveDirectories($folderId = null)
	{
		global $babDB, $babBody;

		$folders = new BAB_FmFolderSet();

		$oRelativePath =& $folders->aField['sRelativePath'];
		$oIdDgOwner =& $folders->aField['iIdDgOwner'];
		$oActive =& $folders->aField['sActive'];
		$oHide =& $folders->aField['sHide'];
		$oId =& $folders->aField['iId'];

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
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
		&& $this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES) {
			$elementType .= ' clickable';
		}

		while (null !== ($folder = $folders->next()))
		{
			$bManager = bab_isAccessValid(BAB_FMMANAGERS_GROUPS_TBL, $folder->getId());
			$bDownload = bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $folder->getId());

			if($this->_adminView || $bManager || $bDownload)
			{
				$element =& $this->createElement('d' . BAB_TREE_VIEW_ID_SEPARATOR . $folder->getId(),
												 $elementType,
												 bab_toHtml($folder->getName()),
												 '',
												 '');
				if ($this->_updateBaseUrl)
				{
					$element->setFetchContentScript(bab_toHtml("bab_loadSubTree(document.getElementById('li" . $this->_id . '.' . $element->_id .  "'), '" . $this->_updateBaseUrl . "&start=" . $folder->getId() . "')"));
				}
				$element->setIcon($GLOBALS['babSkinPath'] . 'images/nodetypes/folder.png');
				if (($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_COLLECTIVE_DIRECTORIES)
				&& ($this->_attributes & BAB_TREE_VIEW_MULTISELECT)) {
					$element->addCheckBox('select');
				}
				$this->appendElement($element, 'd' . $folder->getDelegationOwnerId());
			}
		}
	}




    /**
     * Add files and subdirectories for a specific collective folder.
     * @access private
     */
    function _addCollectiveFiles($folderId = null, $path = '')
    {
        global $babDB, $babBody;

        $sEndSlash = (strlen(trim($path)) > 0 ) ? '/' : '' ;

        $rootPath = '';

        $folders = new BAB_FmFolderSet();
        $oId =& $folders->aField['iId'];
        
        if ($folderId !== null) {
            $oFolder = $folders->get($oId->in($folderId));
            if (is_a($oFolder, 'BAB_FmFolder')) {
                $rootPath .= $oFolder->getName() . '/';
                $idDgOwner = $oFolder->getDelegationOwnerId();
            }
        } elseif ($babBody->currentAdmGroup != 0 && ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_ONLY_DELEGATION)) {
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
        
        if ($rootPath . $path . $sEndSlash !== '')
        {
            $aWhereClauseItem[]    = 'file.path LIKE ' . $babDB->quote($rootPath . $path . $sEndSlash . '%');
        }

        $aWhereClauseItem[]    = 'file.state<>\'D\'';

        
        $directoryType = 'folder';
        if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
        	&& ($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_SUB_DIRECTORIES)) {
            $directoryType .= ' clickable';
        }
        $groupFileType = 'gfile';
        if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
       		&& ($this->_attributes & BAB_FILE_TREE_VIEW_SELECTABLE_FILES)) {
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
                'file.path ASC, file.name ASC';
            
        $files = $babDB->db_query($sQuery);


        $folders = new BAB_FmFolderSet();

        $oRelativePath =& $folders->aField['sRelativePath'];
        $oName =& $folders->aField['sName'];

        while ($file = $babDB->db_fetch_array($files)) {

            $filePath = removeFirstPath($file['path']);
            $subdirs = explode('/', $filePath);

            $fileId = 'g' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id'];
            $rootFolderName = getFirstPath($file['path']);

            if (is_null($folderId)) {
	            $oCriteria = $oRelativePath->in($babDB->db_escape_like(''));
	            $oCriteria = $oCriteria->_and($oName->in($rootFolderName));
	
	            $folder = $folders->get($oCriteria);
	            if (!$folder) {
	                continue;
	            }
            	$rootId = 'd' . BAB_TREE_VIEW_ID_SEPARATOR . $folder->getId(); // $file['id_owner'];
            } else {
            	$rootId = 'd' . BAB_TREE_VIEW_ID_SEPARATOR . $folderId; // $file['id_owner'];
            }
            $fileType =& $groupFileType;

            $parentId = $rootId;

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
	




	/**
	 * Fill the 'info' of each element with the number of hits from the statistics.
	 *
	 * @param string $start		An iso formatted date 'yyyy-mm-dd'.
	 * @param string $end		An iso formatted date 'yyyy-mm-dd'.
	 * @access public
	 */
	function addStatistics($start, $end)
	{
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
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
			$node =& $this->_rootNode->getNodeById('g' . BAB_TREE_VIEW_ID_SEPARATOR . $file['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($file['hits']);
				$element->setRank((int)$file['hits']);
				$node =& $node->parentNode();
				while (!is_null($node)) {
					$element =& $node->getData();
					if ($element) {
						$element->setInfo((int)$element->_info + (int)$file['hits']);
						$element->setRank((int)$element->_rank + (int)$file['hits']);
					}
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
		if ($this->_upToDate) {
			return;
		}

		$this->_addVisibleDelegations();

		$this->_addCollectiveDirectories($this->_startFolderId);

		if ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_FILES
				|| $this->_attributes & BAB_FILE_TREE_VIEW_SHOW_SUB_DIRECTORIES) {
			$attributes = $this->_attributes;
			$this->_attributes &= ~BAB_FILE_TREE_VIEW_SHOW_FILES;
			$this->_addCollectiveFiles($this->_startFolderId, $this->_startPath);
			$this->_attributes = $attributes;
			$this->_addCollectiveFiles($this->_startFolderId, $this->_startPath);
		}

		if ($this->_attributes & BAB_FILE_TREE_VIEW_SHOW_PERSONAL_DIRECTORIES
				&& is_null($this->_startFolderId)) {
			$this->_addPersonalFiles();
		}

		if (!is_null($this->_startFolderId))
		{
			$nodeId = 'd' . BAB_TREE_VIEW_ID_SEPARATOR . $this->_startFolderId;
			$node =& $this->_rootNode->getNodeById($nodeId);
			$this->_iterator = $this->_rootNode->createNodeIterator($node);
			$this->_iterator->nextNode();
			$this->t_baseLevel = $this->_iterator->level() + 1;
			$this->t_level = 1;
			$this->t_previousLevel = 0;
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


	function bab_ForumTreeView($id)
	{
		parent::bab_TreeView($id);
		
		$this->_attributes = BAB_FORUM_TREE_VIEW_SHOW_POSTS;
	}

	/**
	 * Add forums to the tree.
	 * @access private
	 */
	function _addForums()
	{
		global $babDB, $babBody;

		$sql = 'SELECT id, name FROM ' . BAB_FORUMS_TBL;
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' WHERE id_dgowner = ' . $babDB->quote($babBody->currentAdmGroup);
		}
		$sql .= ' ORDER BY ordering';
		
		$forumType = 'forum';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_FORUM_TREE_VIEW_SELECTABLE_FORUMS) {
			$forumType .= ' clickable';
		}
		$rs = $babDB->db_query($sql);
		while ($forum = $babDB->db_fetch_array($rs)) {
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
		global $babDB, $babBody;

		$sql = 'SELECT tt.id, tt.forum FROM ' . BAB_THREADS_TBL. ' tt';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN ' . BAB_FORUMS_TBL . ' ft ON tt.forum = ft.id ' .
					' WHERE ft.id_dgowner = ' . $babDB->quote($babBody->currentAdmGroup);
		}
		$sql .= ' ORDER BY tt.date';
		
		$threadType = 'thread';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_FORUM_TREE_VIEW_SELECTABLE_THREADS) {
			$threadType .= ' clickable';
		}
		$postType = 'post';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_FORUM_TREE_VIEW_SELECTABLE_POSTS) {
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
		global $babDB;

		$this->_updateTree();
		// Init stats at 0
		$iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
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
			$node =& $this->_rootNode->getNodeById('post' . BAB_TREE_VIEW_ID_SEPARATOR . $post['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($post['hits']);
				$element->setRank((int)$post['hits']);
			}
		}

		// For each forum we calculate the total number of hits for all the posts in the forum.

		// We loop over the forum nodes (ie. the siblings of the root node's first child).		
		for ($forumNode =& $this->_rootNode->firstChild(); !is_null($forumNode); $forumNode =& $forumNode->nextSibling()) {
			
			if (!is_null($forumNode->_firstChild)) {
				
				$total = 0;
				$iterator = $this->_rootNode->createNodeIterator($forumNode->_firstChild);
				// We iterate all the nodes under the current forum node and calculate the total hits.
				while ($node =& $iterator->nextNode()) {
					if (!is_null($node)) {
						$total += (int)($node->_data->_info);
					}
				}
				$forumNode->_data->setInfo('' . $total);
				$forumNode->_data->setRank($total);
				
			}
		}
	}

	/**
	 * @access private
	 */
	function _updateTree()
	{
		if ($this->_upToDate) {
			return;
		}
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
	var $_categories;
	/**#@-*/

	function bab_FaqTreeView($id)
	{
		parent::bab_TreeView($id);

		$this->_attributes = BAB_FAQ_TREE_VIEW_SHOW_QUESTIONS;
	}

	/**
	 * Add FAQ categories to the tree.
	 * @access private
	 */
	function _addCategories()
	{
		global $babDB, $babBody;

		$sql = 'SELECT id, category FROM ' . BAB_FAQCAT_TBL;
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' WHERE id_dgowner = ' . $babDB->quote($babBody->currentAdmGroup);
		}
		$sql .= ' order by category asc';

		$faqcategoryType = 'faqcategory';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_FAQ_TREE_VIEW_SELECTABLE_CATEGORIES) {
			$faqcategoryType .= ' clickable';
		}
		$categories = $babDB->db_query($sql);
		while ($category = $babDB->db_fetch_array($categories)) {
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
		
		$subCategories = $babDB->db_query($sql);
		while ($subCategory = $babDB->db_fetch_array($subCategories)) {
			$this->_categories[$subCategory['id']] = $subCategory['id_cat'];
		}
	}


	/**
	 * Add FAQ sub-categories to the tree.
	 * @access private
	 */
	function _addSubCategories()
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
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_FAQ_TREE_VIEW_SELECTABLE_SUB_CATEGORIES) {
			$faqsubcategoryType .= ' clickable';
		}		
		$subCategories = $babDB->db_query($sql);
		while ($subCategory = $babDB->db_fetch_array($subCategories)) {
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
		global $babDB, $babBody;

		$sql = 'SELECT fqt.id, fqt.question, fqt.id_subcat FROM ' . BAB_FAQQR_TBL.' fqt';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN '.BAB_FAQCAT_TBL.' fct ON fqt.idcat=fct.id WHERE fct.id_dgowner=\''.$babBody->currentAdmGroup.'\'';
		}

		$questionType = 'faqquestion';
		if (!($this->_attributes & BAB_TREE_VIEW_MULTISELECT)
					&& $this->_attributes & BAB_FAQ_TREE_VIEW_SELECTABLE_QUESTIONS) {
			$questionType .= ' clickable';
		}
		$questions = $babDB->db_query($sql);
		while ($question = $babDB->db_fetch_array($questions)) {
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
		global $babDB;

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
			$start && $where[] = 'st_date >= ' . $babDB->quote($start);
			$end && $where[] = 'st_date <= ' . $babDB->quote($end . ' 23:59:59');
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY id';

		$faqs = $babDB->db_query($sql);
		while ($faq = $babDB->db_fetch_array($faqs)) {
			$node =& $this->_rootNode->getNodeById('question' . BAB_TREE_VIEW_ID_SEPARATOR . $faq['id']);
			if (!is_null($node)) {
				$element =& $node->getData();
				$element->setInfo($faq['hits']);
				$element->setRank((int)$faq['hits']);
				$node =& $node->parentNode();
				while (!is_null($node)) {
					$element =& $node->getData();
					if ($element) {
						$element->setInfo((int)$element->_info + (int)$faq['hits']);
						$element->setRank((int)$element->_rank + (int)$faq['hits']);
					}
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
		if ($this->_upToDate)
			return;
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



//class bab_OvidentiaOrgChart extends bab_OrgChart 
//{
//	var $_orgChartId; // Ovidentia org chart id
//	var $_startEntityId;
//	var $_userId;
//	var $_adminMode;
//	
//	var $t_orgChartId;
//	var $t_entityId;
//	var $t_adminMode;
//	var $t_userId;
//	
//	function bab_OvidentiaOrgChart($id, $orgChartId, $startEntityId = 0, $userId = 0, $startLevel = 0, $adminMode = false)
//	{
//		parent::bab_OrgChart($id, $startLevel);
//
//		$this->_orgChartId = $this->t_orgChartId = $orgChartId;
//		$this->_startEntityId = $this->t_entityId = $startEntityId;
//		$this->_userId = $this->t_userId = $userId;
//		$this->_adminMode = $this->t_adminMode = $adminMode;
//	}
//
//	/**
//	 * Returns a record set containing the child entities of $startEntityId, $startEntityId included. 
//	 *
//	 * @param int $startEntityId
//	 * @access private
//	 */
//	function _selectEntities($startEntityId)
//	{
//		global $babDB;
//
//		$where = array('trees.id_user = ' . $babDB->quote($this->_orgChartId));
//		
//		if ($this->_startEntityId != 0) {
//			$sql = 'SELECT trees.id, trees.lf, trees.lr ';
//			$sql .= ' FROM ' . BAB_OC_TREES_TBL . ' AS trees';
//			$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities on entities.id_node=trees.id';
//			$sql .= ' WHERE trees.id_user = ' . $babDB->quote($this->_orgChartId);
//			$sql .= ' AND entities.id = ' . $babDB->quote($startEntityId);
//			$trees = $babDB->db_query($sql);
//			$tree = $babDB->db_fetch_array($trees);
//
//			$where[] = '(trees.id = ' . $babDB->quote($tree['id']) .
//						' OR (trees.lf > ' . $babDB->quote($tree['lf']) .
//							' AND trees.lr < '  . $babDB->quote($tree['lr']) . '))';
//		}
//
//		
//		$sql = 'SELECT entities.*, entities2.id as id_parent ';
//		$sql .= ' FROM ' . BAB_OC_TREES_TBL . ' AS trees';
//		$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities ON entities.id_node = trees.id';
//		$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities2 ON entities2.id_node = trees.id_parent';
//
//		$sql .= ' WHERE ' . implode(' AND ', $where);
//		$sql .= ' ORDER BY trees.lf ASC';
//
//		$entities = $babDB->db_query($sql);
//		
//		return $entities;
//	}
//
//	/**
//	 * Adds entities starting at entity id $startNode in the orgchart.
//	 * The entity with id $startNode will be the root of the orgchart. 
//	 * 
//	 * @param int $entityId
//	 * @access private
//	 */
//	function _addEntities($startEntityId)
//	{
//		require_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
//		global $babDB;
//
//		$entityType = 'entity';
//		$elementIdPrefix = 'ENT';
//		
//		$entities = $this->_selectEntities($startEntityId);
//		while ($entity = $babDB->db_fetch_array($entities)) {
//			$element =& $this->createElement($elementIdPrefix . $entity['id'],
//											 $entityType,
//											 bab_toHtml($entity['name']),
//											 '',
//											 '');
//			$members = bab_OCselectEntityCollaborators($entity['id']);
//			while ($member = $babDB->db_fetch_array($members)) {
//				if ($member['user_disabled'] !== '1' && $member['user_confirmed'] !== '0') { // We don't display disabled and unconfirmed users
//					$memberDirectoryEntryId = $member['id_dir_entry'];
//					$dirEntry = bab_getDirEntry($member['id_dir_entry'], BAB_DIR_ENTRY_ID);
//					$memberName = bab_composeUserName($dirEntry['givenname']['value'], $dirEntry['sn']['value']);
//					if ($member['role_type'] == 1) {
//						if (isset($dirEntry['jpegphoto'])) {
//							$element->setIcon($dirEntry['jpegphoto']['value']);
//						}
//						$element->setInfo($memberName);
//						$element->setLink("javascript:flbhref('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&idx=detr&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&iduser=" . $memberDirectoryEntryId . "');changestyle('ENT" . $entity['id'] . "','BabLoginMenuBackground','BabTopicsButtonBackground');updateFltFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=listr');");
//					}
//					$element->addMember($memberName, $member['role_name']);
//				}
//			}
//			$element->setLinkEntity("javascript:updateFlbFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=detr');updateFltFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=listr');changestyle('ENT" . $entity['id'] . "','BabLoginMenuBackground','BabTopicsButtonBackground');");
//
//			if ($entity['id'] != $startEntityId) {
//				$element->addAction('show_from_here', bab_translate("Show from here"), $GLOBALS['babSkinPath'] . 'images/Puces/bottom.png', $GLOBALS['babUrlScript'] . '?tg=' . bab_rp('tg') . '&idx' . bab_rp('idx') . '&ocid=' . $this->_orgChartId . '&oeid=' . $entity['id'] . '&disp=disp3', '');
//			} else if ($entity['id_parent'] != 0) {
//				$element->addAction('show_from_parent', bab_translate("Show from parent"), $GLOBALS['babSkinPath'] . 'images/Puces/parent.gif', $GLOBALS['babUrlScript'] . '?tg=' . bab_rp('tg') . '&idx' . bab_rp('idx') . '&ocid=' . $this->_orgChartId . '&oeid=' . $entity['id_parent'] . '&disp=disp3', '');
//			}
//			$element->addAction('toggle_members', bab_translate("Members"), $GLOBALS['babSkinPath'] . 'images/Puces/members.png', '', 'toggleMembers');
//			if ($this->_adminMode) {
//				$element->addAction('edit', bab_translate("Roles"), $GLOBALS['babSkinPath'] . 'images/Puces/edit.gif', "javascript:updateFltFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=listr');updateFlbFrame('" . $GLOBALS['babUrlScript'] . "?tg=flbchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=listr');", '');
//				$element->addAction('delete', bab_translate("Delete"), $GLOBALS['babSkinPath'] . 'images/Puces/delete.png', "javascript:updateFltFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=listr');updateFlbFrame('" . $GLOBALS['babUrlScript'] . "?tg=flbchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entity['id'] . "&idx=dele');", '');
//			}
//			$this->appendElement($element, ($entity['id_parent'] == 0 || $entity['id'] == $this->_startEntityId) ? null : $elementIdPrefix . $entity['id_parent']);		
//		}
//	}
//
//	/**
//	 * @access private
//	 */
//	function _updateTree()
//	{
//		if ($this->_upToDate)
//			return;
//		$this->_addEntities($this->_startEntityId);
//		parent::_updateTree();
//	}
//}










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
	function bab_GroupTreeViewElement($groupId, $type, $title, $description, $link)
	{
		parent::bab_TreeViewElement($groupId,  $type, $title, $description, $link);
		$this->_groupId = $groupId;
	}

	/**
	 * Returns the group id of the element.
	 *
	 * @return string		The group id of the element.
	 * @access public
	 */
	function getGroupId()
	{
		return $this->_groupId;
	}
	
}


/**
 * Enter description here...
 *
 */
class bab_GroupTreeView extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_selectedGroups;
	/**#@-*/

	function bab_GroupTreeView($id)
	{
		parent::bab_TreeView($id);
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
	 * @access public
	 */
	function &createElement($id, $type, $title, $description, $link)
	{
		$element =& new bab_GroupTreeViewElement($id, $type, $title, $description, $link);
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
	function appendElement(&$element, $parentId)
	{
		parent::appendElement($element, $parentId);
		$groupId = $element->getGroupId();
		if ($groupId !== '' && ($this->_attributes & BAB_TREE_VIEW_MULTISELECT)) {
			$element->addCheckBox('select[' . $groupId . ']', isset($this->_selectedGroups[$groupId]));
		}
	}


	
	/**
	 * Preselect groups in the treeview.
	 *
	 * @param array $groups		An array indexed by group ids (group ids are in the key)
	 */
	function selectGroups($groups)
	{
		$this->_selectedGroups = $groups;
	}

	/**
	 * @access private
	 */
	function _addGroups()
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
			$element =& $this->createElement('group' . BAB_TREE_VIEW_ID_SEPARATOR . $group['id'],
											 'group',
											 bab_toHtml($groupName),
											 '',
											 '');
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
		if ($this->_upToDate) {
			return;
		}
		$this->_addGroups();

		parent::_updateTree();
	}
}



?>