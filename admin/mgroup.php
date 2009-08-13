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


class mgroups
{
	var $expand_checked = false;

	function mgroups($tg, $idx, $id_parent = false)
	{
	$this->t_expand_all = bab_translate("Expand all");
	$this->t_collapse_all = bab_translate("Collapse all");
	$this->t_expand_checked = bab_translate("Expand to checked boxes");
	$this->t_group = bab_translate("Main groups folder");
	$this->t_record = bab_translate("Record");
	$this->id_parent = &$id_parent;
	$this->tgval = &$tg;
	$this->idxval = &$idx;
	$this->options = array();
	$this->fields = array();
	$this->id_expand_to = isset($_REQUEST['expand_to']) ? $_REQUEST['expand_to'] : BAB_ADMINISTRATOR_GROUP;
	}
	
	function setExpandChecked() {
		$this->expand_checked = true;
	}

	function setField($name, $value)
	{
	$this->fields[$name] = $value;
	}

	function setGroupOption($id_group, $name, $value)
	{
	$this->options[$id_group][$name] = $value;
	}

	function setGroupsOptions($arr_groups, $name, $value)
	{
	foreach($arr_groups as $id_group)
		$this->setGroupOption($id_group, $name, $value);
	}

	function getNextField()
	{
	return $this->field = each($this->fields);
	}

	function babecho()
	{
	$tree = new bab_grptree();
	if (false === $this->id_parent)
		{
		$this->id_parent = $tree->firstnode;
		}

	$this->arr = $tree->getNodeInfo($this->id_parent);

	if ($this->arr['lf'] <= $tree->firstnode_info['lf'] || $this->arr['lr'] >= $tree->firstnode_info['lr'] )
		{
		$this->arr = $tree->firstnode_info;
		}
	$this->delegat = isset($tree->delegat[$this->arr['id']]);
	$this->arr['name'] = bab_translate($this->arr['name']);
	$this->arr['description'] = bab_toHtml(bab_translate($this->arr['description']));
	$this->option = isset($this->options[$this->arr['id']]) ? $this->options[$this->arr['id']] : false;
	$this->tpl_tree = bab_grp_node_html($tree, $this->arr['id'], 'mgroup.html', 'grp_childs', $this->options);

	global $babBody;
	$babBody->addStyleSheet('tree.css');
	$babBody->addStyleSheet('groups.css');
	$babBody->babecho(bab_printTemplate($this, "mgroup.html", "grp_maintree"));
	}
}


function mgroups_getSelected()
{
	if (isset($_POST['mgroups']) && count($_POST['mgroups']) > 0)
		{
		return $_POST['mgroups'];
		}
	else
		return array();
}

?>