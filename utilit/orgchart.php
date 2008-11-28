<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
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
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/tree.php';


class bab_OrgChartElement extends bab_TreeViewElement
{
	/**#@+
	 * @access private
	 */	
	var $_members;
	var $_linkEntity;
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

	/**
	 * Defines the url link when the entity is clicked.
	 * @param string $url
	 */
	function setLinkEntity($url)
	{
		$this->_linkEntity = $url;
	}

}






class bab_OrgChart extends bab_TreeView
{
	/**#@+
	 * @access private
	 */	
	var $_verticalThreshold;
	var $_startLevel;
	
	var $_locationElements;
	var $_openNodes;
	var $_openMembers;

	var $t_zoomFactor;
	
	var $t_nodeId;
	var $t_layout;
	var $t_previousLayout;
	
	var $t_nbMembers;
	var $t_memberName;

	var $t_linkEntity;
	/**#@-*/
	
	
	/**
	 * @param string 	$id		A unique treeview id in the page.
	 * 							Must begin with a letter ([A-Za-z]) and may be followed
	 * 							by any number of letters, digits ([0-9]), hyphens ("-"),
	 * 							underscores ("_"), colons (":"), and periods (".").
	 * @param int		$startLevel
	 * @return bab_OrgChart
	 * @access public
	 */
	function bab_OrgChart($id, $startLevel = 0)
	{
		parent::bab_TreeView($id);

		$this->_verticalThreshold = 3;
		
		$this->_startLevel = $startLevel;
		$this->_templateFile = 'treeview.html';
		$this->_templateSection = 'orgchart';
		$this->_templateCss = 'orgchart_css';
		$this->_templateScripts = 'orgchart_scripts';
		$this->_openNodes = array();
		$this->_openMembers = array();
		$this->_zoomFactor = 1.0;
		$this->_locationElements = array();

		$this->t_fit_width = bab_translate("Fit width");
		$this->t_threshold = bab_translate("Horizontal/vertical threshold");
		$this->t_threshold_tip = bab_translate("Level at which org chart branchs will be displayed vertically");
		$this->t_visible_levels = bab_translate("Visible levels");
		$this->t_visible_levels_tip = bab_translate("Only show n first levels of the org chart");
		$this->t_zoom_in = bab_translate("Zoom in");
		$this->t_zoom_out = bab_translate("Zoom out");
		$this->t_default_view = bab_translate("Default view");
		$this->t_save_default_view = bab_translate("Save default view");
		$this->t_print = bab_translate("Print");
		$this->t_help = bab_translate("Help");
		$this->t_parameters = bab_translate("Display parameters");
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

	
	function setOpenNodes($openNodes)
	{
		$this->_openNodes = $openNodes;
		reset($this->_openNodes);
	}


	function setOpenMembers($openMembers)
	{
		$this->_openMembers = $openMembers;
		reset($this->_openMembers);
	}

	function setZoomFactor($zoomFactor)
	{
		$this->t_zoomFactor = $zoomFactor;
	}

	/**
	 * Sets the depth level from which the org chart branches are displayed vertically.
	 * @param int	$threshold
	 * @access public
	 */
	function setVerticalThreshold($threshold)
	{
		$this->_verticalThreshold = $threshold;		
	}

	/**
	 * Returns the depth level from which the org chart branches are displayed vertically.
	 * @access public
	 * @return int
	 */
	function getVerticalThreshold()
	{
		return $this->_verticalThreshold;
	}

	/**#@+
	 * Template methods.
	 * @ignore
	 */	
	
	function getNextOpenNode()
	{
		while (list(, $nodeId) = each($this->_openNodes)) {
			$this->t_nodeId = $nodeId;
			return true;	
		}
		reset($this->_openNodes);
		return false;
	}

	function getNextOpenMember()
	{
		while (list(, $nodeId) = each($this->_openMembers)) {
			$this->t_memberId = $nodeId;
			return true;	
		}
		reset($this->_openMembers);
		return false;
	}

	function getNextElement()
	{
		$this->t_tooltip = '';
		$verticalThreshold = $this->_verticalThreshold - $this->_startLevel;
		
		if (is_null($this->_iterator)) {
			$this->_iterator = $this->_rootNode->createNodeIterator($this->_rootNode);
			$this->_iterator->nextNode();
			$this->t_level = $this->_iterator->level();
			$this->t_previousLevel = $this->t_level - 1;
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;//
			$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;//
		}
		$this->t_levelVariation = $this->t_level - $this->t_previousLevel;
		if ($this->t_levelVariation < -1) {
			$this->t_previousLayout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
			$this->t_previousLevel--;
			$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;//
			$this->t_layout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
			return true;
		}

		$this->t_previousLayout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');
		
		$this->t_previousLevel = $this->t_level;
		$this->t_offsetPreviousLevel = $this->t_previousLevel + $this->t_baseLevel;//
		
		$this->t_layout = ($this->t_previousLevel >= $verticalThreshold ? 'vertical' : 'horizontal');

		if ($node =& $this->_iterator->nextNode()) {
			$this->t_isFirstChild = $node->isFirstChild();
			$this->t_isLastChild = $node->isLastChild();
			$this->t_isMiddleChild = (!$node->isFirstChild() && !$node->isLastChild());
			$this->t_isSingleChild = ($node->isFirstChild() && $node->isLastChild());
			
				
			$this->t_level = $this->_iterator->level();
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;//
			
			$this->t_total_level = $this->t_level + $this->_startLevel;
			$element =& $node->getData();
			$this->t_id = $this->_id . '.' . $element->_id;
			$this->t_type =& $element->_type;
			$this->t_title =& $element->_title;
			$this->t_description =& $element->_description;
			$this->t_link =& $element->_link;
			$this->t_linkEntity =& $element->_linkEntity;
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
			$this->t_offsetLevel = $this->t_level + $this->t_baseLevel;//
			
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






class bab_OvidentiaOrgChart extends bab_OrgChart 
{
	var $_orgChartId; // Ovidentia org chart id
	var $_startEntityId;
	var $_userId;
	var $_adminMode;
	
	var $t_orgChartId;
	var $t_entityId;
	var $t_adminMode;
	var $t_userId;
	
	/**
	 * @param string 	$id		A unique treeview id in the page.
	 * 							Must begin with a letter ([A-Za-z]) and may be followed
	 * 							by any number of letters, digits ([0-9]), hyphens ("-"),
	 * 							underscores ("_"), colons (":"), and periods (".").
	 * @param int		$orgChartId
	 * @param int		$startEntityId
	 * @param int		$userId
	 * @param int		$startLevel
	 * @param bool		$adminMode
	 * @return bab_OrgChart
	 * @access public
	 */
	function bab_OvidentiaOrgChart($id, $orgChartId, $startEntityId = 0, $userId = 0, $startLevel = 0, $adminMode = false)
	{
		parent::bab_OrgChart($id, $startLevel);

		$this->_orgChartId = $this->t_orgChartId = $orgChartId;
		$this->_startEntityId = $this->t_entityId = $startEntityId;
		$this->_userId = $this->t_userId = $userId;
		$this->_adminMode = $this->t_adminMode = $adminMode;

		$this->_initLocation();
	}



	function _initLocation()
	{
		global $babDB;

		$this->_locationElements = array();

		
		$entityId = $this->_startEntityId;
		
		$sql = 'SELECT entities.name 
				FROM ' . BAB_OC_ENTITIES_TBL . ' AS entities
				WHERE entities.id = ' . $babDB->quote($entityId) . '
				LIMIT 1';
		$entities = $babDB->db_query($sql);
		if ($entity = $babDB->db_fetch_array($entities))
		{
			$this->_locationElements[$entityId] = $entity['name'];
		
			do {
				$sql = 'SELECT trees.id_parent, parents.id, parents.name
						FROM ' . BAB_OC_TREES_TBL . ' AS trees
						LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities ON entities.id_node = trees.id
						LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS parents ON parents.id_node = trees.id_parent
						WHERE entities.id_node = ' . $babDB->quote($entityId) . '
						AND trees.id_user = ' . $babDB->quote($this->_orgChartId) . '
						LIMIT 1';
				$entities = $babDB->db_query($sql);
				if (($entity = $babDB->db_fetch_assoc($entities)) && (($entityId = $entity['id']) != 0))
				{
					$this->_locationElements[$entity['id']] = $entity['name'];
				}
				else
				{
					$entityId = 0;
				}
					
			} while ($entityId != 0);
		}
		$this->_locationElements = array_reverse($this->_locationElements, true);
		$this->setVerticalThreshold($this->getVerticalThreshold() - (count($this->_locationElements) - 1));
	}

	/**
	 * Returns a record set containing the child entities of $startEntityId, $startEntityId included. 
	 *
	 * @param int $startEntityId
	 * @access private
	 */
	function _selectEntities($startEntityId)
	{
		global $babDB;

		$where = array('trees.id_user = ' . $babDB->quote($this->_orgChartId));
		
		if ($this->_startEntityId != 0) {
			$sql = 'SELECT trees.id, trees.lf, trees.lr ';
			$sql .= ' FROM ' . BAB_OC_TREES_TBL . ' AS trees';
			$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities on entities.id_node=trees.id';
			$sql .= ' WHERE trees.id_user = ' . $babDB->quote($this->_orgChartId);
			$sql .= ' AND entities.id = ' . $babDB->quote($startEntityId);
			$trees = $babDB->db_query($sql);
			$tree = $babDB->db_fetch_array($trees);

			$where[] = '(trees.id = ' . $babDB->quote($tree['id']) .
						' OR (trees.lf > ' . $babDB->quote($tree['lf']) .
							' AND trees.lr < '  . $babDB->quote($tree['lr']) . '))';
		}


		$sql = 'SELECT entities.*, entities2.id as id_parent ';
		$sql .= ' FROM ' . BAB_OC_TREES_TBL . ' AS trees';
		$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities ON entities.id_node = trees.id';
		$sql .= ' LEFT JOIN ' . BAB_OC_ENTITIES_TBL . ' AS entities2 ON entities2.id_node = trees.id_parent';

		$sql .= ' WHERE ' . implode(' AND ', $where);
		$sql .= ' ORDER BY trees.lf ASC';

		$entities = $babDB->db_query($sql);
		
		return $entities;
	}



	/**
	 * Returns an array containing information about types associated with the specified entity.
	 *
	 * For each type an array with 'id', 'name' and 'description' keys is provided.
	 * 
	 * @param int $entityId
	 * @return array
	 */
	function _getEntityTypes($entityId)
	{
		global $babDB;

		$entityTypes = bab_OCGetEntityTypes($entityId);
		
		$types = array();
		while ($entityType = $babDB->db_fetch_assoc($entityTypes)) {
			$types[] = $entityType;
		}
		
		return $types;
	}



	function &_addEntity($entityId, $entityParentId, $entityType, $entityName)
	{

		$elementIdPrefix = 'ENT';
		
		$element =& $this->createElement($elementIdPrefix . $entityId,
										 $entityType,
										 bab_toHtml($entityName),
										 '',
										 '');
		$this->_addMembers($element, $entityId);
		
		$element->setLinkEntity('javascript:'
								. "bab_updateFlbFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entityId . "&idx=detr');"
								. "bab_updateFltFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entityId . "&idx=listr');changestyle('ENT" . $entityId . "','BabLoginMenuBackground','BabTopicsButtonBackground');");

		$this->_addActions($element, $entityId, $entityParentId);
		
		return $element;
	}

	/**
	 * Add members to the specified element for the specified entity.
	 *
	 * @param bab_OrgChartElement	$element
	 * @param int					$entityId
	 */
	function _addMembers(&$element, $entityId)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
		global $babDB;

		$members = bab_OCselectEntityCollaborators($entityId);
		while ($member = $babDB->db_fetch_array($members)) {
			if ($member['user_disabled'] !== '1' && $member['user_confirmed'] !== '0') { // We don't display disabled and unconfirmed users
				$memberDirectoryEntryId = $member['id_dir_entry'];
				$dirEntry = bab_getDirEntry($member['id_dir_entry'], BAB_DIR_ENTRY_ID);
				if (isset($dirEntry['givenname']) && isset($dirEntry['sn'])) {
					$memberName = bab_composeUserName($dirEntry['givenname']['value'], $dirEntry['sn']['value']);
					if ($member['role_type'] == 1) {
						if (isset($dirEntry['jpegphoto']) && !empty($dirEntry['jpegphoto']['value'])) {
							$element->setIcon($dirEntry['jpegphoto']['value'] . '&width=150&height=150');
						}
						$element->setInfo($memberName);
						$element->setLink('javascript:'
												. "flbhref('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&idx=detr&ocid=" . $this->_orgChartId . "&oeid=" . $entityId . "&iduser=" . $memberDirectoryEntryId . "');"
												. "changestyle('ENT" . $entityId . "','BabLoginMenuBackground','BabTopicsButtonBackground');"
												. "bab_updateFltFrame('" . $GLOBALS['babUrlScript'] . "?tg=fltchart&rf=0&ocid=" . $this->_orgChartId . "&oeid=" . $entityId . "&idx=listr');");
					}
					$element->addMember($memberName, $member['role_name']);
				}
			}
		}
	}


	/**
	 * Adds actions in the contextual menu for the specified entity element.
	 *
	 * @param bab_OrgChartElement	$element
	 * @param int					$entityId
	 * @param int					$entityParentId
	 */
	function _addActions(&$element, $entityId, $entityParentId)
	{
		if ($entityId != $this->_startEntityId) {
			$element->addAction('show_from_here',
								bab_translate("Show orgchart from this entity"),
								$GLOBALS['babSkinPath'] . 'images/Puces/bottom.png',
								$GLOBALS['babUrlScript'] . '?tg=' . bab_rp('tg') . '&idx' . bab_rp('idx') . '&ocid=' . $this->_orgChartId . '&oeid=' . $entityId . '&disp=disp3',
								'');
		} else if ($entityParentId != 0) {
			$element->addAction('show_from_parent',
								bab_translate("Show orgchart from parent entity"),
								$GLOBALS['babSkinPath'] . 'images/Puces/parent.gif',
								$GLOBALS['babUrlScript'] . '?tg=' . bab_rp('tg') . '&idx' . bab_rp('idx') . '&ocid=' . $this->_orgChartId . '&oeid=' . $entityParentId . '&disp=disp3',
								'');
		}
		$element->addAction('toggle_members',
							bab_translate("Show/Hide entity members"),
							$GLOBALS['babSkinPath'] . 'images/Puces/members.png',
							'',
							'toggleMembers');
		if ($this->_adminMode) {
			$element->addAction('edit',
								bab_translate("Roles"),
								$GLOBALS['babSkinPath'] . 'images/Puces/head.gif',
								'',
								'editEntityRoles', array($this->_orgChartId, $entityId));
			if ($entityId != $this->_startEntityId) { // The root entity cannot be removed
				$element->addAction('delete',
									bab_translate("Delete"),
									$GLOBALS['babSkinPath'] . 'images/Puces/del.gif',
									'',
									'deleteEntity', array($this->_orgChartId, $entityId));
			}
		}	
	}


	/**
	 * Adds entities starting at entity id $startEntityId in the orgchart.
	 * The entity with id $startEntityId will be the root of the orgchart. 
	 * 
	 * @param int $startEntityId
	 * @access private
	 */
	function _addEntities($startEntityId)
	{
		require_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
		global $babDB;

		$elementIdPrefix = 'ENT';
		$entityType = 'entity';

		$entities = $this->_selectEntities($startEntityId);
		while ($entity = $babDB->db_fetch_assoc($entities)) {
			$entityType = 'entity';
			$entityTypes = $this->_getEntityTypes($entity['id']);
			foreach($entityTypes as $type) {
				$entityType .= ' ' . strtr($type['name'], ' ', '_');
			}

			$element =& $this->_addEntity($entity['id'], $entity['id_parent'], $entityType, $entity['name']);

			$this->appendElement($element, ($entity['id_parent'] == 0 || $entity['id'] == $this->_startEntityId) ? null : $elementIdPrefix . $entity['id_parent']);		
		}
	}



	// Template function

	function getNextLocationElement()
	{
		if (list($entityId, $entityTitle) = each($this->_locationElements))
		{
			$this->t_entityUrl = bab_toHtml('?tg=frchart&ocid=' .$this->_orgChartId . '&oeid=' . $entityId . '&iduser=' . $this->t_userId . '&disp=disp3');
			$this->t_entityTitle = bab_toHtml($entityTitle);
			return true;
		}
		reset($this->_locationElements);
		return false;
	}


	/**
	 * @access private
	 */
	function _updateTree()
	{
		if ($this->_upToDate)
			return;
		$this->_addEntities($this->_startEntityId);
		parent::_updateTree();
	}
}
