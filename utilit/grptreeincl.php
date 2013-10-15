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
include_once $GLOBALS['babInstallPath']."utilit/treeincl.php";
include_once $GLOBALS['babInstallPath']."utilit/defines.php";

class bab_grptree extends bab_dbtree
{
	var $iduser = '';
	var $userinfo = '';
	var $table;

	function bab_grptree()
	{
	$this->bab_dbtree(BAB_GROUPS_TBL, null);


	$this->firstnode = BAB_ALLUSERS_GROUP;
	$this->firstnode_parent = NULL;
	$this->where = 'nb_set >= \'0\'';

	if (bab_getCurrentAdmGroup() > 0)
		{
		$currentDGGroup = bab_getCurrentDGGroup();
		$this->firstnode_info = $this->getNodeInfo($currentDGGroup['id_group']);

		$this->setSubTree($currentDGGroup['lf'], $currentDGGroup['lr']);
		$this->firstnode = $currentDGGroup['id_group'];

		$this->firstnode_parent = $this->firstnode_info['id_parent'];
		}
	else
		{
		$this->firstnode_info = $this->getNodeInfo($this->firstnode);
		}


	//delegation 
	$this->delegat = array();

	global $babDB;
	$res = $babDB->db_query("SELECT id_group FROM ".BAB_DG_GROUPS_TBL."");
	while ($arr = $babDB->db_fetch_assoc($res))
		{
		$this->delegat[$arr['id_group']] = 1;
		}
	}


	/**
	 * get groupes indented with non breakin spaces for display in select boxes
	 * @param int		$id_parent
	 * @return array
	 */
	function getIndentedGroups($id_parent) {

		return $this->getGroups($id_parent, '%s '.bab_nbsp().' '.bab_nbsp().' ');
	}






	/**
	 * Returns an array containing information about groups.
	 *
	 * @param int		$id_parent
	 * @param string	$format
	 * @param bool		$all
	 * @return array
	 */
	function getGroups($id_parent, $format = '%2$s > ', $all = true)
	{
	$grp = array();
	$prefix = array();
	
	$groups = $this->getChilds($id_parent, $all);
	
	

	if ($id_parent === $this->firstnode_parent && $all)
		{
		// add the parent node ony if it is the first node and we requested all groups
		array_unshift ($groups, $this->getNodeInfo($id_parent));
		}

	if (is_array($groups))
		{
		foreach ($groups as $arr)
			{
			if ($arr['id'] <= BAB_ADMINISTRATOR_GROUP)
				{
				$arr['name'] = bab_translate($arr['name']);
				}
	
			if (isset($prefix[$arr['id_parent']]))
				{
					require_once $GLOBALS['babInstallPath'].'utilit/addonapi.php';
					$prefix[$arr['id']] = bab_sprintf($format, $prefix[$arr['id_parent']], $grp[$arr['id_parent']]['name']);
				}
			else
				{
				$prefix[$arr['id']] = '';
				}
	
			$arr['name'] = $prefix[$arr['id']].$arr['name'];
			
			$grp[$arr['id']] = $arr;
			}
		}
	return $grp;
	}


	function setAlphaChild($id_parent, $childname)
	{
	$groups = $this->getChilds($id_parent);
	$grp = array();
	if (is_array($groups))
		{
		foreach ($groups as $arr)
			{
			$grp[$arr['id']] = $arr['name'];
			}
		}
	$grp['new'] = $childname;
	bab_sort::natcasesort($grp);
	
	if (count($groups) > 0)
		$firstchild = $groups[0]['id'];
	else
		$firstchild = 0;

	return array($grp,$firstchild);
	}


	function addAlpha($id_parent, $childname)
	{
	global $babDB;
	
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	$node_id = getNextAvariableId();

	list($grp, $firstchild) = $this->setAlphaChild($id_parent, $childname);

	foreach($grp as $key => $value)
		{
		if ('new' == $key && isset($id_previous))
			{
			return $this->add($id_parent,$id_previous, true, $node_id);
			}
		elseif ('new' == $key)
			{
			return $this->add($id_parent, $firstchild, false, $node_id);
			}

		$id_previous = $key;
		}
	}

	function moveAlpha($id, $id_parent, $childname, $function='move')
	{
	if ($id_parent == $id)
		return false;

	list($grp, $firstchild) = $this->setAlphaChild($id_parent, $childname);


	foreach($grp as $key => $value)
		{
		if ('new' == $key && isset($id_previous))
			{
			return $this->$function($id, $id_parent, $id_previous);
			}
		elseif ('new' == $key)
			{
			return $this->$function($id, $id_parent, $firstchild, false);
			}

		if( $key != 'new' && $key != $id )
			{
			$id_previous = $key;
			}
		}
	}

	function moveTreeAlpha($id, $id_parent, $childname)
	{
	return $this->moveAlpha($id, $id_parent, $childname, 'moveTree');
	}
}



class bab_grp_node
{
	function bab_grp_node(&$tree,$id_group)
	{
	$this->tree = &$tree;
	$this->t_group_set_d = bab_translate("Users group with delegation and group set associated");
	$this->t_group_d = bab_translate("Users group with delegation");
	$this->t_group_set = bab_translate("Group associated with one or more sets of groups");
	$this->t_group = bab_translate("Users group");
	$this->t_group_members = bab_translate("Group's members");
	$this->t_members = bab_translate("Members");
	$this->childs = $this->tree->getChilds($id_group);
	$this->bupdate = bab_getCurrentAdmGroup() == 0 || bab_isDelegated('groups');
	
	/* Icons functionality */
	$icons = @bab_functionality::get('Icons');
	if ($icons != false) {
		$icons->includeCss();
	}
	$this->iconCssClass_Members = Func_Icons::APPS_USERS;
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

		$this->arr['description'] = bab_toHtml($this->arr['description']);
		$this->delegat = bab_getCurrentAdmGroup() == 0 && isset($this->tree->delegat[$this->arr['id']]);
		$this->set = bab_getCurrentAdmGroup() == 0 && $this->arr['nb_set'] > 0;
		$this->option = isset($this->options[$this->arr['id']]) ? $this->options[$this->arr['id']] : false;
		$this->subtree = bab_grp_node_html($this->tree, $this->arr['id'], $this->file, $this->template, $this->options);
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
		return bab_printTemplate($this, $this->file, $this->template);
	else return '';
	}
}

function bab_grp_node_html(&$tree, $id_group, $file, $template, $options = array())
{
	$obj = new bab_grp_node($tree, $id_group);
	$obj->file = &$file;
	$obj->template = &$template;
	$obj->options = &$options;
	return $obj->get();
}


function bab_grpGetNbChildsByParent($id_parent)
{
	$nb = 0;
	$tmp = bab_Groups::getGroups();
	foreach($tmp as $grp)
		{
		if (isset($grp['id_parent']) && $grp['id_parent'] == $id_parent)
			{
			$nb += bab_grpGetNbChildsByParent($grp['id']);
			$nb++;
			}
		}
	return $nb;
}


function bab_grpTreeCreate($id_parent, $lf)
{
	$db = &$GLOBALS['babDB'];
	
	$db->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET grp_change='1'");
	
	if (is_null($id_parent))
		$parent = 'IS NULL';
	else
		$parent = " = '".$id_parent."'";
	
	
	$res = $db->db_query("SELECT id, lf, lr, name FROM ".BAB_GROUPS_TBL." WHERE id_parent ".$parent." AND nb_set>='0' ORDER BY name");
	while ($arr = $db->db_fetch_assoc($res))
		{
		$nb_child = bab_grpGetNbChildsByParent($arr['id']);

		$tmp = 0;
		
		if ($arr['lf'] != $lf) {
			$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET lf='".$lf."' WHERE id='".$arr['id']."'");
			$tmp = $arr['lf'] - $lf;
			if ($tmp > 0) 
				$tmp = ' -'.$tmp;
			else 
				$tmp = ' +'.(-1*$tmp);

			echo 'lf'.$tmp.' : '.$arr['name'].'<br />';
			}

		$tmp = 0;
		
		$lr = $lf + 1 + ($nb_child*2);
		//echo $lf.','.$lr.' - '.$arr['lf'].','.$arr['lr'].' - '.$arr['name'].'<br />';
		bab_grpTreeCreate($arr['id'], ($lf+1));

		if ($arr['lr'] != $lr) {
			$db->db_query("UPDATE ".BAB_GROUPS_TBL." SET lr='".$lr."' WHERE id='".$arr['id']."'");

			$tmp = $arr['lr'] - $lr;
			if ($tmp > 0) 
				$tmp = ' -'.$tmp;
			else 
				$tmp = ' +'.(-1*$tmp);

			echo 'lr'.$tmp.' : '.$arr['name'].'<br />';
			}

		$lf = $lr+1;
		}
}

?>