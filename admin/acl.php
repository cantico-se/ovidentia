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
	var $groupsfilter = array();
	var $arr_listgroups = array();
	var $arr_disabled = array();
	var $arr_everybody = array();
	var $arr_users = array();
	var $arr_guests = array();
	var $arr_groupsfilter = array();
		
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

		

		$this->db = &$GLOBALS['babDB'];

		$this->tree = & new bab_grptree();
		$this->df_groups = $this->tree->getGroups(NULL);

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

		if ($disabled) {
			trigger_error('You can\'t filter on disabled, this function has been deprecated');
			}

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
			if (isset($this->id_group) && isset($this->tables[$i]['groups'][$this->id_group]))
				{
				$this->disabled = false;
				if (isset($this->tables[$i]['checked'][$this->id_group]))
					{
					$this->checked = true;
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
		print_r($_POST);
		die();
	global $babBody;
	$db = $GLOBALS['babDB'];
	$prefix = 'macl_what_';
	foreach($_POST as $field => $value)
		{
		if (substr_count($field,$prefix) == 1)
			{
			$table = substr($field,strlen($prefix));
			$id = $_POST['item'];
			$what = $_POST['macl_what_'.$table];
			$groups = isset($_POST['macl_groups_'.$table])?$_POST['macl_groups_'.$table]:array();
			
			$req = "delete from ".$table." where id_object = '".$id."'";
			$res = $db->db_query($req);
		
			$arr = array();
		
			if( $what == "")
				{
				$cnt = count($groups);
				if( $cnt > 0)
					{
					for( $i = 0; $i < $cnt; $i++)
						{
						$req = "insert into ".$table." (id_object, id_group) values ('". $id. "', '" . $groups[$i]. "')";
						$res = $db->db_query($req);
						}
					}
				}
			else if( $what != -1)
				{
				if( $what == '0' && $babBody->currentAdmGroup != 0 )
					$what = $babBody->currentDGGroup['id_group'];
				$req = "insert into ".$table." (id_object, id_group) values ('". $id. "', '" . $what. "')";
				$res = $db->db_query($req);
				}
			}
		}
	}
	
function aclGroups($target, $index, $table, $id, $return)
	{
	global $babBody;
	$macl = new macl($target, $index, $id, $return);
	$macl->addtable($table);
	$babBody->babecho(	bab_printTemplate($macl, "acl.html", "maclgroups"));
	}

function aclUpdate($table, $id, $groups, $what)
	{
	maclGroups();
	}
?>