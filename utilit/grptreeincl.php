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
include_once $babInstallPath."utilit/treeincl.php";

class bab_grptree extends bab_dbtree
{
	var $iduser = '';
	var $userinfo = '';
	var $table;

	function bab_grptree()
	{
	$this->table = BAB_GROUPS_TBL;

	$this->dg_lf = &$GLOBALS['babBody']->currentDGGroup['lf'];
	$this->dg_lr = &$GLOBALS['babBody']->currentDGGroup['lr'];

	if ($GLOBALS['babBody']->currentAdmGroup > 0)
		$this->where = "lf>='".$this->dg_lf."' AND lr<='".$this->dg_lr."'";
	else
		$this->where = '';
	}

	function getGroups($id_parent, $format = '%s &gt; %s')
	{
	$grp = array();

	$groups = & $this->getChilds($id_parent, 1);
	if (false !== $groups)
	foreach ($groups as $arr)
		{
		if ($arr['id'] < 4)
			{
			$arr['name'] = bab_translate($arr['name']);
			}

		if (isset($grp[$arr['id_parent']]))
			{
			$arr['name'] = sprintf($format,$grp[$arr['id_parent']],$arr['name']);
			}
		
		$grp[$arr['id']] = $arr['name'];
		}
	return $grp;
	}


	function addAlpha($id_parent, $childname)
	{
	$groups = & $this->getChilds($id_parent);
	$grp = array();
	foreach ($groups as $arr)
		{
		$grp[$arr['id']] = $arr['name'];
		}
	$grp['new'] = $childname;
	natcasesort($grp);

	foreach($grp as $key => $value)
		{
		if ('new' == $key && isset($id_previous))
			{
			return $this->add($id_parent,$id_previous);
			}
		elseif ('new' == $key)
			{
			return $this->add($id_parent,,false);
			}

		$id_previous = $key;
		}
	
	
	}
}


?>