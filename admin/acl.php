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
		
	function macl($target, $index,$id, $return)
		{
		global $babBody;
		$this->target = $target;
		$this->index = $index;
		$this->id = $id;
		
		$this->return = $return;
		$this->name = bab_translate("Groups Names");
		$this->updategroups = bab_translate("Update Groups");
		$this->disabled = bab_translate("Disabled");
		$this->everybody = bab_translate("Everybody");
		$this->users = bab_translate("Registered Users");
		$this->guests = bab_translate("Unregistered Users");
		$this->listgroups = bab_translate("Groups List");
		$this->db = $GLOBALS['babDB'];
		$this->multiple = false;

		if( $babBody->currentAdmGroup == 0 )
			$this->ballsite = true;
		else
			$this->ballsite = false;
		
		

		include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

		$tree = new bab_grptree();
		$this->allgroups = $tree->getGroups(BAB_REGISTERED_GROUP);
		}
		
	function addtable($table,$name = '')
		{
		global $babBody;
		$tblindex = count($this->tables);
		if ($tblindex > 0) $this->multiple = true;
		$this->tables[$tblindex]['table'] = $table;
		$this->tables[$tblindex]['name'] = $name != '' ? $name : $table;
		$this->tables[$tblindex]['what'] = array();
		$this->tables[$tblindex]['what']['everybody'] = "";
		$this->tables[$tblindex]['what']['users'] = "";
		$this->tables[$tblindex]['what']['guests'] = "";
		$this->tables[$tblindex]['what']['disabled'] = "";
		$req = "select * from ".$table." where id_object='".$this->id."'";
		$this->tables[$tblindex]['res'] = $this->db->db_query($req);
		$this->tables[$tblindex]['count'] = $this->db->db_num_rows($this->tables[$tblindex]['res']);
		if( $this->tables[$tblindex]['count'] < 1 )
			$this->tables[$tblindex]['what']['disabled'] = "selected";			
		else if( $this->tables[$tblindex]['count'] == 1)
			{
			$arr = $this->db->db_fetch_array($this->tables[$tblindex]['res']);
			if( $arr['id_group'] < 3)
				{
				$this->count1 = 0;
				switch($arr['id_group'])
					{
					case 0:
						$this->tables[$tblindex]['what']['everybody'] = "selected";
						break;
					case 1:
						$this->tables[$tblindex]['what']['users'] = "selected";
						break;
					case 2:
						$this->tables[$tblindex]['what']['guests'] = "selected";
						break;
					}
				}
			else if( $babBody->currentAdmGroup != 0 && $arr['id_group'] == $babBody->currentAdmGroup )
				{
				$this->tables[$tblindex]['what']['everybody'] = "selected";
				}
			}
		}
		
	function filter($listgroups = 0,$disabled = 0,$everybody = 0,$users = 0,$guest = 0,$groups = array())
		{
		$tblindex = count($this->tables) - 1;
		$this->arr_listgroups[$tblindex] = $listgroups;
		$this->arr_disabled[$tblindex] = $disabled;
		$this->arr_everybody[$tblindex] = $everybody;
		$this->arr_users[$tblindex] = $users;
		$this->arr_guests[$tblindex] = $guest;
		$this->arr_groupsfilter[$tblindex] = $groups;
		}
		
	function getnexttable()
		{
		static $i = 0;
		if( $i < count($this->tables))
			{
			$this->table = $this->tables[$i]['table'];
			$this->title = $this->tables[$i]['name'];
			$this->what = $this->tables[$i]['what'];
			$this->res1 = $this->tables[$i]['res'];
			$this->count1 = $this->tables[$i]['count'];
			$this->listgroups = !empty($this->arr_listgroups[$i]) ? false : bab_translate("Groups List");
			$this->disabled = !empty($this->arr_disabled[$i]) ? false : bab_translate("Disabled");
			$this->everybody = !empty($this->arr_everybody[$i]) ? false : bab_translate("Everybody");
			$this->users = !empty($this->arr_users[$i]) ? false : bab_translate("Registered Users");
			$this->guests = !empty($this->arr_guests[$i]) ? false : bab_translate("Unregistered Users");
			$this->groupsfilter = isset($this->arr_groupsfilter[$i]) && is_array($this->arr_groupsfilter[$i]) ? $this->arr_groupsfilter[$i] : array();
			$i++;
			return true;
			}
		else
			{
			$i = 0;
			return false;
			}
		}

	function getnextgroup(&$skip)
		{
		
		if( list(,$this->arrgroups) = each($this->allgroups))
			{
			if (in_array($this->arrgroups['id'],$this->groupsfilter))
				{
				$skip = true;
				return true;
				}
			$this->arrgroups['select'] = "";
			if($this->count1 > 0)
				{
				$this->db->db_data_seek($this->res1, 0);
				for( $j = 0; $j < $this->count1; $j++)
					{
					$this->groups = $this->db->db_fetch_array($this->res1);
					if( $this->groups['id_group'] == $this->arrgroups['id'])
						{
						$this->arrgroups['select'] = "selected";
						break;
						}
					}
				}
			return true;
			}
		else
			{
			reset($this->allgroups);
			$i = 0;
			return false;
			}
		}
		
	function babecho()
		{
		global $babBody;
		$babBody->babecho(	bab_printTemplate($this, "acl.html", "maclgroups"));
		}
	}




	
function maclGroups()
	{
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