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
include "base.php";
require_once($babInstallPath . 'utilit/tmdefines.php');
require_once($babInstallPath . 'utilit/baseFormProcessingClass.php');

		
class BAB_TM_ListBase extends BAB_BaseFormProcessing
{
	var $m_db;
	var $m_result;
	var $m_rowDatas;

	var $m_is_altbg;

	function BAB_TM_ListBase($result = false)
	{
		parent::BAB_BaseFormProcessing();

		$this->m_db	= & $GLOBALS['babDB'];
		$this->m_is_altbg = true;

		$this->set_caption('name', bab_translate("Name"));
		$this->set_caption('description', bab_translate("Description"));
		$this->set_data('isLink', true);
		$this->set_data('name', '');
		$this->set_data('description', '');
		$this->m_rowDatas = false;
		$this->m_result = $result;
		
		$this->init();
	}

	function init()
	{
		
	}
	
	function nextItem()
	{
		if(false != $this->m_result)
		{
			$this->m_rowDatas = $this->m_db->db_fetch_array($this->m_result);
	
			if(false != $this->m_rowDatas)
			{
				$this->m_is_altbg = !$this->m_is_altbg;
				$this->set_data('id', $this->m_rowDatas['id']);
				$this->set_data('name', htmlentities($this->m_rowDatas['name'], ENT_QUOTES));
				$this->set_data('description', htmlentities($this->m_rowDatas['description'], ENT_QUOTES));
				return true;
			}
		}
		return false;
	}
}
?>