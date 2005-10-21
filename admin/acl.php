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
include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";


class macl
	{
	var $tables = array();
	var $altbg = true;
		
	function macl($target, $index,$id_object, $return)
		{
		global $babBody;
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

		

		$this->db = &$GLOBALS['babDB'];

		$this->tree = & new bab_grptree();

		$res = $this->db->db_query("SELECT * FROM ".BAB_GROUPS_TBL."");
		while ($arr = $this->db->db_fetch_assoc($res))
			{
			$this->df_groups[$arr['id']] = 1;
			}

		if ($babBody->currentAdmGroup == 0)
			{
			$this->resset = $this->db->db_query("SELECT * FROM ".BAB_GROUPS_TBL." WHERE nb_groups>='0'");
			$this->countsets = $this->db->db_num_rows($this->resset);
			}
		else
			{
			$this->countsets = 0;
			}
		}
		
	function addtable($table,$name = '')
		{
		$checked = array();
		$res = $this->db->db_query("SELECT id_group FROM ".$table." WHERE id_object='".$this->id_object."'");
		while ($arr = $this->db->db_fetch_assoc($res))
			{
			$checked[$arr['id_group']] = 1;
			}

		$tblindex = count($this->tables);
		$this->tables[$tblindex] = array(
				'table'		=> $table,
				'title'		=> empty($name) ? bab_translate("Access rights") : $name,
				'groups'	=> $this->df_groups,
				'checked'	=> $checked
			);
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
		if ($everybody) {
			unset($this->tables[$tblindex]['groups'][BAB_ALLUSERS_GROUP]);
			}

		if ($users) {
			unset($this->tables[$tblindex]['groups'][BAB_REGISTERED_GROUP]);
			}

		if ($guest) {
			unset($this->tables[$tblindex]['groups'][BAB_UNREGISTERED_GROUP]);
			}

		if (count($groups) > 0) {
			foreach($groups as $grp)
				{
				if (isset($this->tables[$tblindex]['groups'][$grp]))
					unset($this->tables[$tblindex]['groups'][$grp]);
				}
			}
		}
		
	function getnexttable()
		{
		static $i = 0;
		if( $i < count($this->tables))
			{
			$this->table = $this->tables[$i]['table'];
			$this->title = $this->tables[$i]['title'];
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
		if ($this->arr = $this->db->db_fetch_assoc($this->resset))
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
			$this->arr = $this->tree->getNodeInfo($this->tree->firstnode);
			$this->id_group = $this->arr['id'];
			$this->arr['name'] = bab_translate($this->arr['name']);
			$this->arr['description'] = htmlentities(bab_translate($this->arr['description']));

			$this->tpl_tree = acl_grp_node_html($this, $this->tree->firstnode);
			return true;
			}

		return false;
		}


	function getHtml()
		{
		global $babBody;
		$babBody->addStyleSheet('groups.css');
		$html = bab_printTemplate($babBody,"uiutil.html", "styleSheet");
		$html .= bab_printTemplate($this, "acl.html", "grp_maintree");
		return $html;
		}
	
		
	function babecho()
		{
		global $babBody;
		$babBody->addStyleSheet('groups.css');
		$babBody->babecho(	bab_printTemplate($this, "acl.html", "grp_maintree"));
		}
	}



class acl_grp_node extends macl
{
	function acl_grp_node(&$acl,$id_group)
	{
	$this->acl = &$acl;
	$this->tree = &$acl->tree;
	$this->tables = &$acl->tables;
	$this->childs = $this->tree->getChilds($id_group);
	}

	function getnextgroup()
	{
	if ($this->childs && list(,$this->arr) = each($this->childs))
		{
		if ($this->arr['id'] <= BAB_ADMINISTRATOR_GROUP)
			{
			$this->arr['name'] = bab_translate($this->arr['name']);
			$this->arr['description'] = bab_translate($this->arr['description']);
			}

		//$this->arr['name'] = '['.$this->arr['lf'].','.$this->arr['lr'].'] '.$this->arr['name'];

		$this->arr['description'] = htmlentities($this->arr['description']);
		$this->id_group = $this->arr['id'];
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
	$obj = & new acl_grp_node($acl, $id_group);
	return $obj->get();
}





	
function maclGroups()
	{
	global $babBody;
	$db = &$GLOBALS['babDB'];
	$id_object = &$_POST['item'];

	unset($_SESSION['bab_groupAccess']['acltables']);
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");

	if (isset($_POST['group']) && count($_POST['group']) > 0) {
		foreach($_POST['group'] as $table => $groups)
			{
			$db->db_query("DELETE FROM ".$table." WHERE id_object='".$id_object."' AND id_group NOT IN('".implode("','",$groups)."') AND id_group < '".BAB_ACL_GROUP_TREE."'");

			$groups = array_flip($groups);

			$res = $db->db_query("SELECT id_group FROM ".$table." WHERE id_object='".$id_object."' AND id_group < '".BAB_ACL_GROUP_TREE."'");
			while ($arr = $db->db_fetch_assoc($res))
				{
				if (isset($groups[$arr['id_group']])) {
					unset($groups[$arr['id_group']]);
					}
				}

			foreach ($groups as $id => $value)
				{
				$db->db_query("INSERT INTO ".$table." (id_object, id_group) VALUES ('".$id_object."', '".$id."')");
				}
			}
		}

	foreach($_POST['tablelist'] as $table)
		{
		if (!isset($_POST['group'][$table]))
			$db->db_query("DELETE FROM ".$table." WHERE id_object='".$id_object."' AND id_group < '".BAB_ACL_GROUP_TREE."'");
		}

	
	if (isset($_POST['tree']) && count($_POST['tree']) > 0) {
		foreach($_POST['tree'] as $table => $groups)
			{
			array_walk($groups, create_function('&$v,$k','$v += BAB_ACL_GROUP_TREE;'));
			
			$db->db_query("DELETE FROM ".$table." WHERE id_object='".$id_object."' AND id_group NOT IN('".implode("','",$groups)."') AND id_group >= '".BAB_ACL_GROUP_TREE."'");

			$groups = array_flip($groups);

			$res = $db->db_query("SELECT id_group FROM ".$table." WHERE id_object='".$id_object."' AND id_group > '".BAB_ACL_GROUP_TREE."'");
			while ($arr = $db->db_fetch_assoc($res))
				{
				if (isset($groups[$arr['id_group']])) {
					unset($groups[$arr['id_group']]);
					}
				}

			foreach ($groups as $id => $value)
				{
				$db->db_query("INSERT INTO ".$table."  (id_object, id_group) VALUES ('".$id_object."', '".$id."')");
				}
			}
		}

	if (isset($_POST['tablelist']))
		foreach($_POST['tablelist'] as $table)
			{
			if (!isset($_POST['tree'][$table]))
				$db->db_query("DELETE FROM ".$table." WHERE id_object='".$id_object."' AND id_group >= '".BAB_ACL_GROUP_TREE."'");
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
	$db = &$GLOBALS['babDB'];
	$db->db_query("DELETE FROM ".$table." WHERE id_object='".$id_object."'");
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	}


function aclSetGroups_all($table, $id_object)
	{
	$db = &$GLOBALS['babDB'];
	$db->db_query("INSERT INTO ".$table."  (id_object, id_group) VALUES ('".$id_object."', '".BAB_ALLUSERS_GROUP."')");
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	}

function aclSetGroups_registered($table, $id_object)
	{
	$db = &$GLOBALS['babDB'];
	$db->db_query("INSERT INTO ".$table."  (id_object, id_group) VALUES ('".$id_object."', '".BAB_REGISTERED_GROUP."')");
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	}

function aclSetGroups_unregistered($table, $id_object)
	{
	$db = &$GLOBALS['babDB'];
	$db->db_query("INSERT INTO ".$table."  (id_object, id_group) VALUES ('".$id_object."', '".BAB_UNREGISTERED_GROUP."')");
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	}
?>