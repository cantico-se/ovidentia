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

/**
* @internal SEC1 PR 16/02/2007 FULL
*/


include_once "base.php";
include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";


class macl
	{
	var $tables = array();
	var $altbg = true;
	var $aHiddenFields = array();
	
	var $sHiddenFieldName = '';
	var $sHiddenFieldValue = '';

	var $iIdDelegation = null;
	
	function macl($target, $index,$id_object, $return, $bsetofgroups=true, $iIdDelegation=NULL)
		{
		require_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
		
		global $babBody, $babDB;
		$this->target = &$target;
		$this->index = &$index;
		$this->id_object = $id_object;
		$this->return = &$return;
		
		$this->t_expand_all = bab_translate("Expand all");
		$this->t_collapse_all = bab_translate("Collapse all");
		$this->t_expand_checked = bab_translate("Expand to checked boxes");
		$this->t_group = bab_translate("Group");
		$this->t_record = bab_translate("Record");
		$this->t_sets_of_groups = bab_translate("Sets of groups");
		$this->t_broken = bab_translate("ACL broken. Please resubmit the form");
		$this->firstnode = BAB_ALLUSERS_GROUP;

		$this->df_groups = array();
		$res = $babDB->db_query("SELECT id, lf, lr FROM ".BAB_GROUPS_TBL."");
		while ($arr = $babDB->db_fetch_assoc($res))
			{
			$this->df_groups[$arr['id']] = 1;
			$this->ov_groups[$arr['id']] = $arr;
			}

		$this->tree = new bab_arraytree(BAB_GROUPS_TBL, null);
		
		$this->iIdDelegation = (!is_null($iIdDelegation)) ? $iIdDelegation : $babBody->currentAdmGroup;
		$aDelegation = reset(bab_getDelegationById($this->iIdDelegation));
		$this->iIdDelegationGroup = $aDelegation['id_group'];
		
		if ($this->iIdDelegation > 0)
			{
			$this->aclgroups = array();
			$res = $babDB->db_query("select id_group from ".BAB_DG_ACL_GROUPS_TBL." where id_object='".$this->iIdDelegation."'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->aclgroups[$arr['id_group']] = true;
				}

			if( count($this->aclgroups) == 0 )
				{
				$this->aclgroups[$this->iIdDelegationGroup + BAB_ACL_GROUP_TREE] = true;
				}
			
			if ( !isset($this->aclgroups[BAB_ALLUSERS_GROUP]) && !isset($this->aclgroups[BAB_UNREGISTERED_GROUP]))
				{
				unset($this->df_groups[BAB_UNREGISTERED_GROUP]);
				unset($this->df_groups[BAB_ALLUSERS_GROUP]);
				}

			if (!isset($this->aclgroups[BAB_ALLUSERS_GROUP]) && !isset($this->aclgroups[BAB_REGISTERED_GROUP]))
				{
				$this->aclgroups[BAB_UNREGISTERED_GROUP] = true;
				$this->aclgroups[BAB_ALLUSERS_GROUP] = true;
				$this->aclgroups[BAB_REGISTERED_GROUP] = true;
				unset($this->df_groups[BAB_REGISTERED_GROUP]);
				$childs = $this->tree->getChilds(BAB_REGISTERED_GROUP);
				for( $k = 0; $k < count($childs); $k++ )
					{
					if( $childs[$k] > BAB_UNREGISTERED_GROUP )
						{
						if( isset($this->aclgroups[$childs[$k]+BAB_ACL_GROUP_TREE]) )
							{
							$ch = $this->tree->getChilds($childs[$k]);
							$k += count($ch);
							}
						elseif( !isset($this->aclgroups[$childs[$k]]))
							{
							$this->tree->removeNode($childs[$k]);
							unset($this->df_groups[$childs[$k]]);
							}
						}
					}
				}


			$this->countsets = 0;
			
			}
		elseif( $bsetofgroups)
			{
			$this->resset = $babDB->db_query("SELECT * FROM ".BAB_GROUPS_TBL." WHERE nb_groups>='0'");
			$this->countsets = $babDB->db_num_rows($this->resset);
			}
			
		$_SESSION['bab_acl_tablelist'] = array();
		}

	function issetAclGroup($a)
		{
		if( isset($this->aclgroups[$a]))
			return true;

		if( $a > BAB_ACL_GROUP_TREE )
			{
			$a -= BAB_ACL_GROUP_TREE;
			}

		foreach( $this->aclgroups as $key => $val )
			{
			if( $key > BAB_ACL_GROUP_TREE )
				{
				$b = $key - BAB_ACL_GROUP_TREE;

				if( $this->ov_groups[$b]['lf'] <= $this->ov_groups[$a]['lf'] && $this->ov_groups[$b]['lr'] >= $this->ov_groups[$a]['lr'] )
					{
					return true;
					}
				}

			}

		return false;
		}

	function addtable($table,$name = '')
		{
		global $babDB;
		$checked = array();
		$checked_table = array();
		$res = $babDB->db_query("SELECT id_group FROM ".$babDB->db_escape_string($table)." WHERE id_object='".$babDB->db_escape_string($this->id_object)."'");
		while ($arr = $babDB->db_fetch_assoc($res))
			{
			$checked[$arr['id_group']] = 1;
			if( isset($this->aclgroups) && !$this->issetAclGroup($arr['id_group']))
				{
				$checked_table[] = $arr['id_group'];
				}
			}
		if( isset($this->aclgroups) && !isset($this->aclgroups[BAB_ALLUSERS_GROUP]) && !isset($this->aclgroups[BAB_REGISTERED_GROUP]) && count($checked_table) > 0 )
			{
			$acldiff = true;
			}
		else
			{
			$acldiff = false;
			}

		$tblindex = count($this->tables);
		$this->tables[$tblindex] = array(
				'table'		=> $table,
				'title'		=> empty($name) ? bab_translate("Access rights") : $name,
				'groups'	=> $this->df_groups,
				'checked'	=> $checked,
				'msgerror'	=> $acldiff
			);
			
		$_SESSION['bab_acl_tablelist'][$table] = $table;
		}
		
	function filter($listgroups = 0,$disabled = 0,$everybody = 0,$users = 0,$guest = 0,$groups = array())
		{
		$tblindex = count($this->tables) - 1;
		
		if ($listgroups) {
			$this->tables[$tblindex]['groups'] = array( 
					BAB_ALLUSERS_GROUP => 1, 
					BAB_REGISTERED_GROUP => 1, 
					BAB_UNREGISTERED_GROUP => 1
					);
			}
/*
		if ($disabled) {
			trigger_error('You can\'t filter on disabled, this function has been deprecated');
			}
*/
		if ($everybody && isset($this->tables[$tblindex]['groups'][BAB_ALLUSERS_GROUP])) {
			unset($this->tables[$tblindex]['groups'][BAB_ALLUSERS_GROUP]);
			}

		if ($users && isset($this->tables[$tblindex]['groups'][BAB_REGISTERED_GROUP])) {
			unset($this->tables[$tblindex]['groups'][BAB_REGISTERED_GROUP]);
			}

		if ($guest && isset($this->tables[$tblindex]['groups'][BAB_UNREGISTERED_GROUP])) {
			unset($this->tables[$tblindex]['groups'][BAB_UNREGISTERED_GROUP]);
			}

		if (count($groups) > 0) {
			foreach($groups as $grp)
				{
				if (isset($this->tables[$tblindex]['groups'][$grp]))
					{
					unset($this->tables[$tblindex]['groups'][$grp]);
					}
				}
			}
		}
		
	function getnexttable()
		{
		static $i = 0;
		if( $i < count($this->tables))
			{
			$this->tablenum = $i +1;
			$this->table = bab_toHtml($this->tables[$i]['table']);
			$this->title = bab_toHtml($this->tables[$i]['title']);
			$this->msgerror = bab_toHtml($this->tables[$i]['msgerror']);
			$this->disabled = true;
			$this->checked = false;
			$this->treechecked = false;
			if (isset($this->id_group))
				{
				$tree = $this->id_group + BAB_ACL_GROUP_TREE;
				if ( isset($this->tables[$i]['checked'][$tree]) )
					{
					$this->treechecked = true;
					}
				elseif ( isset($this->tables[$i]['groups'][$this->id_group]) )
					{
					$this->disabled = false;
					if (isset($this->tables[$i]['checked'][$this->id_group]))
						{
						$this->checked = true;
						}
					}
				}
			
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getnextset()
		{
		global $babDB;
		if ($this->arr = $babDB->db_fetch_assoc($this->resset))
			{
			$this->id_group = $this->arr['id'];
			$this->altbg = !$this->altbg;
			return true;
			}
		return false;
		}

	function firstnode()
		{
		if (!isset($this->id_group))
			{
			$this->arr = $this->tree->getNodeInfo($this->firstnode);
			$this->id_group = $this->arr['id'];
			$this->arr['name'] = bab_toHtml(bab_translate($this->arr['name']));
			$this->arr['description'] = bab_toHtml(bab_translate($this->arr['description']));

			$this->tpl_tree = acl_grp_node_html($this, $this->firstnode);
			return true;
			}

		return false;
		}


	function getHtml()
		{
		global $babBody;
		$babBody->addStyleSheet('groups.css');
		$babBody->addStyleSheet('tree.css');
		$babBody->addStyleSheet('acl.css');
		$html = bab_printTemplate($babBody,"uiutil.html", "styleSheet");
		$html .= bab_printTemplate($this, "acl.html", "grp_maintree");
		return $html;
		}
	
		
	function babecho()
		{
		global $babBody;
		
		$babBody->addStyleSheet('groups.css');
		$babBody->addStyleSheet('tree.css');
		$babBody->addStyleSheet('acl.css');
		$babBody->babecho(	bab_printTemplate($this, "acl.html", "grp_maintree"));
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
		
	function get_next_hidden_field()
		{
			$data = each($this->aHiddenFields);
			if(false != $data)
			{
				$this->sHiddenFieldName = bab_toHtml($data['key']);
				$this->sHiddenFieldValue = bab_toHtml($data['value']);
				return true;
			}
			else
			{
				$this->sHiddenFieldName = '';
				$this->sHiddenFieldValue = '';
				reset($this->aHiddenFields);
				return false;
			}
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
 */
function maclGroups()
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
			$query .= '		WHERE `disabled` = \'0\' AND (`validity_end` = \'0000-00-00\' OR `validity_end` < \''.$today.'\')';
		}
		if (!$returnDisabledUsers && !$returnNonConfirmedUsers) {
			$query .= '		WHERE `disabled` = \'0\' AND `is_confirmed` = \'1\' AND (`validity_end` = \'0000-00-00\' OR `validity_end` < \''.$today.'\')';
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
			$query .= '		AND `u`.`disabled` = \'0\' AND (`u`.`validity_end` = \'0000-00-00\' OR `u`.`validity_end` < \''.$today.'\')';
		}
		if (!$returnDisabledUsers && !$returnNonConfirmedUsers) {
			$query .= '		AND `u`.`disabled` = \'0\' AND `u`.`is_confirmed` = \'1\' AND (`u`.`validity_end` = \'0000-00-00\' OR `u`.`validity_end` < \''.$today.'\')';
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

	$res = $babDB->db_query('SELECT id_group FROM '.$babDB->backTick($srcTable).' WHERE id_object='.$babDB->quote($srcIdObject));
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$babDB->db_query('INSERT INTO ' . $babDB->backTick($trgTable) . ' (`id` , `id_object` , `id_group`) VALUES (\'\', ' . $babDB->quote($trgIdObject) . ', ' . $babDB->quote($arr['id_group']) . ')');
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

	if ($babDB->db_query('INSERT INTO ' . $babDB->backTick($sTable) . ' (`id` , `id_object` , `id_group`) VALUES (\'\', ' . $babDB->quote($iIdObject) . ', ' . $babDB->quote($iIdGroup) . ')')) {
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

