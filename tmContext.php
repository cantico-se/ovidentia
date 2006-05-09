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
	require_once($babInstallPath . 'utilit/tableWrapperClass.php');


class BAB_TM_Context
{
	var $m_oTblWr;
	var $m_aDefaultPC; /* default project configuration */

	var $m_iIdProjectSpace;
	var $m_iIdProject;
	var $m_iIdDelegation;


	function BAB_TM_Context()
	{
		global $babBody;

		$this->m_oTblWr = new BAB_TableWrapper('');
		$this->m_iIdProjectSpace = (int) tskmgr_getVariable('iIdProjectSpace', 0);
		$this->m_iIdProject = (int) tskmgr_getVariable('iIdProject', 0);
		$this->m_iIdDelegation = $babBody->currentAdmGroup;
		$this->loadDefaultProjectsConfiguration();
	}
	
	/* Private */
	function loadDefaultProjectsConfiguration()
	{
		if(0 != $this->m_iIdProjectSpace)
		{
			$attributs = array(
				'idProjectSpace' => $this->m_iIdProjectSpace,
				'id' => -1,
				'tskUpdateByMgr' => -1,
				'endTaskReminder' => -1,
				'tasksNumerotation' => -1,
				'emailNotice' => -1,
				'faqUrl' => '');
				
			$this->m_oTblWr->setTableName(BAB_TSKMGR_DEFAULT_PROJECTS_CONFIGURATION_TBL);
			
			$this->m_aDefaultPC = $this->m_oTblWr->load($attributs, 0, count($attributs), 0, 1);
			if(false != $this->m_aDefaultPC)
			{
				return true;
			}
		}
		$this->m_aDefaultPC = null;
		return false;
	}
	
	
	function getIdProjectSpace()
	{
		return $this->m_iIdProjectSpace;
	}
	
	function getIdProject()
	{
		return $this->m_iIdProject;
	}
	
	function getIdDelegation()
	{
		return $this->m_iIdDelegation;
	}
	
	function &getDefaultProjectsConfiguration()
	{
		return $this->m_aDefaultPC;
	}
	
	function &getTableWrapper()
	{
		return $this->m_oTblWr;
	}
}

function& getTskMgrContext()
{
	if(!isset($GLOBALS['BAB_TM_Context']))
	{
		$GLOBALS['BAB_TM_Context'] = new BAB_TM_Context();
	}
	return $GLOBALS['BAB_TM_Context'];
}

?>