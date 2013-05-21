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

include_once "base.php";
require_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";
require_once $GLOBALS['babInstallPath']."utilit/session.class.php";
	
	
class macl
{
	var $tables = array();
	var $altbg = true;
	var $aHiddenFields = array();
	var $iIdDelegation = null;

	function macl($target, $index, $id_object, $return, $bsetofgroups=true, $iIdDelegation=NULL)
	{
		$this->target = $target;
		$this->index = $index;
		$this->id_object = $id_object;
		$this->return = $return;
		
		$session = bab_getInstance('bab_Session');
		$session->bab_acl_tablelist = array();
	}
	
	
	function addtable($table, $name = '')
	{
		global $babDB;

		$this->tables[] = array(
				'table'		=> $table,
				'title'		=> empty($name) ? bab_translate("Access rights") : $name,
				'filter'	=> array()
		);
		
		$session = bab_getInstance('bab_Session');
		if (isset($session->bab_acl_tablelist))
		{
			$s_table = $session->bab_acl_tablelist;
		} else {
			$s_table = array();
		}
		
		// store allowed table for verification while saving
		$s_table[$table] = $table;
		
		$session->bab_acl_tablelist = $s_table;
	}
	

	function filter($listgroups = 0,$disabled = 0,$everybody = 0,$users = 0,$guest = 0,$groups = array())
	{
		$tblindex = count($this->tables) - 1;

		if ($listgroups) {
			$this->tables[$tblindex]['filter'][] = array(BAB_REGISTERED_GROUP, true);
		}
		/*
		if ($disabled) {
			trigger_error('You can\'t filter on disabled, this function has been deprecated');
		}
		*/
		if ($everybody) {
			$this->tables[$tblindex]['filter'][] = array(BAB_ALLUSERS_GROUP, false);
		}

		if ($users) {
			$this->tables[$tblindex]['filter'][] = array(BAB_REGISTERED_GROUP, false);
		}

		if ($guest) {
			$this->tables[$tblindex]['filter'][] = array(BAB_UNREGISTERED_GROUP, false);
		}

		if (count($groups) > 0) {
			foreach($groups as $grp)
			{
				$this->tables[$tblindex]['filter'][] = array($grp, false);
			}
		}
	}

	


	private function getForm()
	{
		$W = bab_Widgets();
		
		$form = $W->Form(null, $W->VBoxLayout()->setVerticalSpacing(1.5,'em'));
		$form->addClass('bab-acl-form');
		$form->setHiddenValue('tg', $this->target);
		$form->setHiddenValue('idx', $this->index);
		$form->setHiddenValue('item', $this->id_object);
		$form->setHiddenValue($this->return, 'update');
		
		foreach($this->aHiddenFields as $name => $value)
		{
			$form->setHiddenValue($name, $value);
		}
		
		foreach($this->tables as $table)
		{
			$widget = $W->Acl()
				->setTitle($table['title'])
				->setName($table['table'])
				->setValue(aclGetRightsString($table['table'], $this->id_object));
			
			foreach($table['filter'] as $g)
			{
				$widget->disableGroup($g[0], $g[1]);
			}
			
			
			$form->addItem($widget);
		}
		
		$form->addItem($W->SubmitButton()->setLabel(bab_translate('Save')));
		
		return $form;
	}

	
	public function getHtml()
	{
		$W = bab_Widgets();
		
		return $this->getForm()->display($W->HtmlCanvas());
	}
	

	function babecho()
	{
		$W = bab_Widgets();
		
		$page = $W->BabPage();
		$page->setEmbedded();
		$page->addItem($this->getForm());
		
		$page->displayHtml();
	}
	

	function get_hidden_field($sName, &$sValue)
	{
		if(isset($this->aHiddenFields[$sName]))
		{
			$sValue = $this->aHiddenFields[$sName];
			return true;
		}
		return false;
	}

	function set_hidden_field($sName, $sValue)
	{
		$this->aHiddenFields[$sName] = $sValue;
		return true;
	}
}
		
	
	



class acl_grp_node extends macl
{
	function acl_grp_node(&$acl,$id_group)
	{
	$this->acl = &$acl;
	$this->tree = &$acl->tree;
	$this->tables = &$acl->tables;
	$this->childs = $this->tree->getChilds($id_group, false);	
	$this->t_group = bab_translate("Group");
	}

	function getnextgroup()
	{
	if ($this->childs && list(,$nodeid) = each($this->childs))
		{
		$this->arr = $this->tree->getNodeInfo($nodeid);
		if ($nodeid <= BAB_ADMINISTRATOR_GROUP)
			{
			$this->arr['name'] = bab_translate($this->arr['name']);
			$this->arr['description'] = bab_translate($this->arr['description']);
			}

		//$this->arr['name'] = '['.$this->arr['lf'].','.$this->arr['lr'].'] '.$this->arr['name'];
		$this->arr['name'] = bab_toHtml($this->arr['name']);
		$this->arr['description'] = bab_toHtml($this->arr['description']);
		$this->id_group = bab_toHtml($this->arr['id']);
		$this->subtree = acl_grp_node_html($this->acl, $this->id_group);
		return true;
		}
	else 
		{
		return false;
		}
	}

	function get()
	{
	if ($this->childs)
		return bab_printTemplate($this, 'acl.html', 'grp_childs');
	else return '';
	}
}

function acl_grp_node_html(&$acl, $id_group)
{
	$obj = new acl_grp_node($acl, $id_group);
	return $obj->get();
}





/**
 * Record ACL form
 * 

function _maclGroups()
	{

	global $babBody,$babDB;
	$id_object = &$_POST['item'];

	unset($_SESSION['bab_groupAccess']['acltables']);
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	
	if (!isset($_SESSION['bab_acl_tablelist'])) {
		return;
	}
	
	$s_table = $_SESSION['bab_acl_tablelist'];
	unset($_SESSION['bab_acl_tablelist']);
	
	if (isset($_POST['group']) && count($_POST['group']) > 0) {
		foreach($_POST['group'] as $table => $groups)
			{
			if (isset($s_table[$table])) {
				$babDB->db_query("DELETE FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."' AND id_group NOT IN(".$babDB->quote($groups).") AND id_group < '".BAB_ACL_GROUP_TREE."'");
	
				$groups = array_flip($groups);
	
				$res = $babDB->db_query("SELECT id_group FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."' AND id_group < '".BAB_ACL_GROUP_TREE."'");
				while ($arr = $babDB->db_fetch_assoc($res))
					{
					if (isset($groups[$arr['id_group']])) {
						unset($groups[$arr['id_group']]);
						}
					}
	
				foreach ($groups as $id => $value)
					{
					$babDB->db_query("INSERT INTO ".$babDB->db_escape_string($table)." (id_object, id_group) VALUES ('".$babDB->db_escape_string($id_object)."', '".$babDB->db_escape_string($id)."')");
					}
				}
			}
		}

	if (isset($s_table)) {
		foreach($s_table as $table)
			{
			if (!isset($_POST['group'][$table])) {
				$babDB->db_query("DELETE FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."' AND id_group < '".BAB_ACL_GROUP_TREE."'");
			}
		}
	}

	
	if (isset($_POST['tree']) && count($_POST['tree']) > 0) {
		foreach($_POST['tree'] as $table => $groups)
			{
			if (isset($s_table[$table])) {
				array_walk($groups, create_function('&$v,$k','$v += BAB_ACL_GROUP_TREE;'));
				
				$babDB->db_query("DELETE FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."' AND id_group NOT IN(".$babDB->quote($groups).") AND id_group >= '".BAB_ACL_GROUP_TREE."'");
	
				$groups = array_flip($groups);
	
				$res = $babDB->db_query("SELECT id_group FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."' AND id_group > '".BAB_ACL_GROUP_TREE."'");
				while ($arr = $babDB->db_fetch_assoc($res))
					{
					if (isset($groups[$arr['id_group']])) {
						unset($groups[$arr['id_group']]);
						}
					}
	
				foreach ($groups as $id => $value)
					{
					$babDB->db_query("INSERT INTO ".$babDB->db_escape_string($table)."  (id_object, id_group) VALUES ('".$babDB->db_escape_string($id_object)."', '".$babDB->db_escape_string($id)."')");
					}
				}
			}
		}

	
	if (isset($s_table)) { 
		foreach($s_table as $table) {
			if (!isset($_POST['tree'][$table])) {
				$babDB->db_query("DELETE FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."' AND id_group >= '".BAB_ACL_GROUP_TREE."'");
			}
		}
	}
}
 */


/**
 * Record ACL form
 * 
 */
function maclGroups()
{
	global $babDB;
	$id_object = &$_POST['item'];
	
	unset($_SESSION['bab_groupAccess']['acltables']);
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	
	$session = bab_getInstance('bab_Session');
	
	if (!isset($session->bab_acl_tablelist)) {
		return;
	}

	$s_table = $session->bab_acl_tablelist;
	unset($session->bab_acl_tablelist);
	
	foreach($s_table as $tablename)
	{
		if (isset($_POST[$tablename]))
		{
			aclSetRightsString($tablename, $id_object, $_POST[$tablename]);
		}
	}
}





	
function aclGroups($target, $index, $table, $id, $return)
	{
	global $babBody;
	$macl = new macl($target, $index, $id, $return);
	$macl->addtable($table);
	$macl->babecho();
	}

function aclUpdate($table, $id, $groups, $what)
	{
	maclGroups();
	}

function aclDelete($table, $id_object)
	{
	global $babDB;
	$babDB->db_query("DELETE FROM ".$babDB->backTick($table)." WHERE id_object='".$babDB->db_escape_string($id_object)."'");
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	}

/**
 * Removes all the ACL information stored for a group in the specified table.
 * 
 * @since	6.1.0
 * 
 * @param	string	$table			The acl table name.
 * @param	int		$id_group		The group for which acl should be removed.
 */
function aclDeleteGroup($table, $id_group)
{
	global $babDB;
	$babDB->db_query('DELETE FROM ' . $babDB->backTick($table) . ' WHERE id_group=' . $babDB->quote($id_group) . ' OR id_group=' . $babDB->quote($id_group + BAB_ACL_GROUP_TREE));
}

function aclSetGroups_all($table, $id_object)
	{
	global $babDB;
	$babDB->db_query("INSERT INTO ".$babDB->backTick($table)."  (id_object, id_group) VALUES ('".$babDB->db_escape_string($id_object)."', '".BAB_ALLUSERS_GROUP."')");
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	}

function aclSetGroups_registered($table, $id_object)
	{
	global $babDB;
	$babDB->db_query("INSERT INTO ".$babDB->backTick($table)."  (id_object, id_group) VALUES ('".$babDB->db_escape_string($id_object)."', '".BAB_REGISTERED_GROUP."')");
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	}

function aclSetGroups_unregistered($table, $id_object)
	{
	global $babDB;
	$babDB->db_query("INSERT INTO ".$babDB->backTick($table)."  (id_object, id_group) VALUES ('".$babDB->db_escape_string($id_object)."', '".BAB_UNREGISTERED_GROUP."')");
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	}

function aclGetAccessGroups($table, $id_object) {
	global $babBody, $babDB;
	
	$tree = new bab_grptree();
	$groups = array();
	
	$res = $babDB->db_query("SELECT t.id_group, g.nb_groups FROM ".$babDB->backTick($table)." t left join ".BAB_GROUPS_TBL." g on g.id=t.id_group WHERE t.id_object='".$babDB->db_escape_string($id_object)."'");
	while ($arr = $babDB->db_fetch_assoc($res)) {
		if ($arr['id_group'] >= BAB_ACL_GROUP_TREE )
			{
			$arr['id_group'] -= BAB_ACL_GROUP_TREE;
			$groups[$arr['id_group']] = $arr['id_group'];
			$tmp = $tree->getChilds($arr['id_group'], true);
			if( $tmp && is_array($tmp ))
				{
				foreach($tmp as $child) {
					$groups[$child['id']] = $child['id'];
					}
				}
			}
		else
			{
			if( $arr['nb_groups'] !== null )
				{
				$rs=$babDB->db_query("select id_group from ".BAB_GROUPS_SET_ASSOC_TBL." where id_set=".$babDB->quote($arr['id_group']));
				while( $rr = $babDB->db_fetch_array($rs))
					{
					$groups[$rr['id_group']] = $rr['id_group'];
					}
				}
			else
				{
				$groups[$arr['id_group']] = $arr['id_group'];
				}
			}
		}

	return $groups;
	}
	

/**
 * Return the list of the users who have the access right specified (table and id object)
 * 		Users disabled, non confirmed or invalid (validity_start & validity_end) are not selected
 * @param string $table : name of the table
 * @param int $id_object : id of the object
 * @param string $activeOrderBy : 'lastname', 'firstname', NULL by default (since version ovidentia-7-2-94-20100522)
 * @param boolean $returnDisabledUsers : if true the list of users contains disabled users. False by default
 * @param boolean $returnNonConfirmedUsers : if true the list of users contains non confirmed users. False by default
 * @return array :
 * array
   (
    [154] =>
        (
            [name] => Guillaume Dupont
            [firstname] => Guillaume
            [lastname] => Dupont
            [email] => test@test.com
		)
	)
 */
function aclGetAccessUsers($table, $id_object, $activeOrderBy=NULL, $returnDisabledUsers=false, $returnNonConfirmedUsers=false) {
	global $babBody, $babDB;
	
	$groups = aclGetAccessGroups($table, $id_object);
	$query = '';
	$today = date('Y-m-d');
	if (isset($groups[BAB_REGISTERED_GROUP]) || isset($groups[BAB_ALLUSERS_GROUP])) {
		$query = 'SELECT `id`, `firstname`, `lastname`, `email` 
					FROM '.BAB_USERS_TBL.' ';
		if ($returnDisabledUsers && $returnNonConfirmedUsers) {
			//no condition
		}
		if ($returnDisabledUsers && !$returnNonConfirmedUsers) {
			$query .= '		WHERE `is_confirmed` = \'1\'';
		}
		if (!$returnDisabledUsers && $returnNonConfirmedUsers) {
			$query .= '		WHERE `disabled` = \'0\' AND (`validity_end` = \'0000-00-00\' OR `validity_end` >= \''.$today.'\')';
		}
		if (!$returnDisabledUsers && !$returnNonConfirmedUsers) {
			$query .= '		WHERE `disabled` = \'0\' AND `is_confirmed` = \'1\' AND (`validity_end` = \'0000-00-00\' OR `validity_end` >= \''.$today.'\')';
		}
		if (isset($activeOrderBy)) {
			if ($activeOrderBy == 'lastname') {
				$query .= ' ORDER by `lastname`,`firstname`';
			} else {
				$query .= ' ORDER by `firstname`,`lastname`';
			}
		}
	} else {
		$query = 'SELECT `u`.id,`u`.`firstname`, `u`.`lastname`,`u`.`email` 
					FROM '.BAB_USERS_TBL.' `u`, '.BAB_USERS_GROUPS_TBL.' `g`
						WHERE `g`.`id_object`=`u`.`id` AND `g`.`id_group` IN('.$babDB->quote($groups).') ';
		if ($returnDisabledUsers && $returnNonConfirmedUsers) {
			//no condition
		}
		if ($returnDisabledUsers && !$returnNonConfirmedUsers) {
			$query .= '		AND `u`.`is_confirmed` = \'1\'';
		}
		if (!$returnDisabledUsers && $returnNonConfirmedUsers) {
			$query .= '		AND `u`.`disabled` = \'0\' AND (`u`.`validity_end` = \'0000-00-00\' OR `u`.`validity_end` >= \''.$today.'\')';
		}
		if (!$returnDisabledUsers && !$returnNonConfirmedUsers) {
			$query .= '		AND `u`.`disabled` = \'0\' AND `u`.`is_confirmed` = \'1\' AND (`u`.`validity_end` = \'0000-00-00\' OR `u`.`validity_end` >= \''.$today.'\')';
		}
		if (isset($activeOrderBy)) {
			if ($activeOrderBy == 'lastname') {
				$query .= ' ORDER by `u`.`lastname`,`u`.`firstname`';
			} else {
				$query .= ' ORDER by `u`.`firstname`,`u`.`lastname`';
			}
		}
	}
	
	$user = array();
	if( !empty($query))
	{
	$res = $babDB->db_query($query);
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$user[$arr['id']] = array(
					'name' => bab_composeUserName($arr['firstname'],$arr['lastname']),
					'firstname' => $arr['firstname'],
					'lastname' => $arr['lastname'],
					'email' => isset($arr['email']) ? $arr['email'] : false
				);
		}
	}

	return $user;
}

/**
 * Duplicate rights from a source table with a certain id_object to a 
 * target table with an another id_object
 * 
 * @access  public 
 * @param   string	$srcTable		right table to duplicate
 * @param   int		$srcIdObject	id of object from which the corresponding rights will duplicated  
 * @param   string	$trgTable		duplicated rights table
 * @param   int		$trgIdObject	new affected rights id_object 
 * 
 */
function aclDuplicateRights($srcTable, $srcIdObject, $trgTable, $trgIdObject)
{
	global $babDB;
	require_once dirname(__FILE__).'/../utilit/sitemap.php';

	$res = $babDB->db_query('SELECT id_group FROM '.$babDB->backTick($srcTable).' WHERE id_object='.$babDB->quote($srcIdObject));
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$babDB->db_query('INSERT INTO ' . $babDB->backTick($trgTable) . ' ( `id_object` , `id_group`) VALUES ( ' . $babDB->quote($trgIdObject) . ', ' . $babDB->quote($arr['id_group']) . ')');
	}
	
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	if(array_key_exists('bab_groupAccess', $_SESSION))
	{
		unset($_SESSION['bab_groupAccess']);
	}
	bab_siteMap::clearAll();
}

/**
 * Clone rights from a source table with a certain id_object to a 
 * target table with an another id_object
 * 
 * @access  public 
 * @param   string	$srcTable		right table to duplicate
 * @param   int		$srcIdObject	id of object from which the corresponding rights will duplicated  
 * @param   string	$trgTable		duplicated rights table
 * @param   int		$trgIdObject	new affected rights id_object 
 * 
 */
function aclCloneRights($srcTable, $srcIdObject, $trgTable, $trgIdObject)
{
	global $babDB;
	
	$babDB->db_query('DELETE FROM '.$babDB->backTick($trgTable).' WHERE id_object='.$babDB->quote($trgIdObject));

	aclDuplicateRights($srcTable, $srcIdObject, $trgTable, $trgIdObject);
}


/**
 * Set right for an object identifier
 *
 * @param string	$sTable		Table name
 * @param int 		$iIdGroup	Group identifier
 * @param int 		$iIdObject	Identifier of the object on which the right applies
 * @return bool
 */
function aclAdd($sTable, $iIdGroup, $iIdObject)
{
	global $babDB;

	if ($babDB->db_query('INSERT INTO ' . $babDB->backTick($sTable) . ' ( `id_object` , `id_group`) VALUES (' . $babDB->quote($iIdObject) . ', ' . $babDB->quote($iIdGroup) . ')')) {
		unset($_SESSION['bab_groupAccess']['acltables']);
		$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
		bab_siteMap::clearAll();
		return true;
	}
	return false;
}


/**
 * Remove right for an object identifier
 *
 * @param string	$table		Table name
 * @param int 		$groupId	Group identifier
 * @param int 		$objectId	Identifier of the object on which the right applies
 * @return bool
 */
function aclRemove($table, $groupId, $objectId)
{
	global $babDB;

	$tree = new bab_grptree();
	$groups = array($groupId => $groupId);
	if ($groupId >= BAB_ACL_GROUP_TREE ) {
		$groupId -= BAB_ACL_GROUP_TREE;
		$groups[$groupId] = $groupId;
		$children = $tree->getChilds($groupId, true);
		if ($children && is_array($children)) {
			foreach($children as $child) {
				$groups[$child['id']] = $child['id'];
			}
		}
	}
	
	if ($babDB->db_query('DELETE FROM ' . $babDB->backTick($table) . ' WHERE `id_object` = ' . $babDB->quote($objectId) . ' AND `id_group` IN (' . $babDB->quote($groups) . ')')) {
		unset($_SESSION['bab_groupAccess']['acltables']);
		$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
		bab_siteMap::clearAll();
		return true;
	}
	return false;
}




/**
 * Create ACL table if not exists
 * 
 * @since 7.5.94
 * 
 * @param string $table
 * @return bool
 */
function aclCreateTable($table)
{
	global $babDB;
	
	return $babDB->db_query("
		CREATE TABLE IF NOT EXISTS ".$babDB->backTick($table)." (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `id_object` int(11) unsigned NOT NULL,
		  `id_group` int(11) unsigned NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `id_object` (`id_object`),
		  KEY `id_group` (`id_group`)
		)
	");
}


/**
 * Get ACL access rights as string
 * 
 * @since 7.5.94
 * 
 * @param	string	$table
 * @param	int		$id_object
 * 
 * @return string	Access rights string with coma separated group list ex: 2,3,4+,6
 */
function aclGetRightsString($table, $id_object)
{
	global $babDB;
	$res = $babDB->db_query('SELECT id_group FROM '.$babDB->backTick($table).' WHERE id_object='.$babDB->quote($id_object).' ORDER BY id_group');
	$ouput = array();
	while ($arr = $babDB->db_fetch_assoc($res))
	{
		$id_group = (int) $arr['id_group'];
		
		if( $id_group >= BAB_ACL_GROUP_TREE )
		{
			$id_group -= BAB_ACL_GROUP_TREE;
			
			$ouput[] = ((string) $id_group).'+';
			
		} else {
			$ouput[] = (string) $id_group;
		}
	}
	
	return implode(',', $ouput);
}

/**
 * Save ACL access rights from string
 * 
 * @since 7.5.94
 * 
 * @param	string	$table
 * @param	int		$id_object		
 * @param	string	$rights			Access rights string with coma separated group list ex: 2,3,4+,6
 * 
 * @return bool
 */
function aclSetRightsString($table, $id_object, $rights)
{
	global $babDB;
	$babDB->db_query('DELETE FROM '.$babDB->backTick($table).' WHERE id_object='.$babDB->quote($id_object));
	
	if ('' !== $rights)
	{
		$input = array_unique(explode(',',$rights));
		$insert = array();
		
		foreach($input as $g)
		{
			if (mb_strlen($g) > 1 && '+' === mb_substr($g, -1))
			{
				$id_group = (int) mb_substr($g, 0, -1);
				if ($id_group > BAB_UNREGISTERED_GROUP)
				{
					$id_group+= BAB_ACL_GROUP_TREE;
				}
			} else {
				$id_group = (int) $g;
			}
			
			$insert[] = '('.$babDB->quote($id_object).', '.$babDB->quote($id_group).')';
		}
		
		if (count($insert) > 0)
		{
			if (!$babDB->db_query('INSERT INTO '.$babDB->backTick($table).' (id_object, id_group) VALUES '.implode(',', $insert)))
			{
				return false;
			}
		}
	}
	
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	bab_siteMap::clearAll();
	
	return true;
}
