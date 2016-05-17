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
	require_once($GLOBALS['babInstallPath'] . 'tmContext.php');
	
function displayNoticeEventForm()
{
	class BAB_NoticeEvent extends BAB_BaseFormProcessing
	{
		var $m_is_altbg;
		
		var $m_aProfil;
		var $m_aEvent;
		
		var $m_iIdProjectSpace;
		var $m_iIdProject;

		function BAB_NoticeEvent()
		{
			parent::BAB_BaseFormProcessing();

			$this->m_is_altbg = true;

			$this->set_caption('sSupervisor', bab_translate("Supervisor"));
			$this->set_caption('sManager', bab_translate("Manager"));
			$this->set_caption('sResponsible', bab_translate("Responsible"));
			$this->set_caption('modify', bab_translate("Modify"));
			$this->set_data('sCheckedNotice', '');
			
			$this->m_aProfil = array(BAB_TM_SUPERVISOR, BAB_TM_PROJECT_MANAGER, BAB_TM_TASK_RESPONSIBLE);
		
			$this->m_aEvent = array(
				BAB_TM_EV_PROJECT_CREATED => bab_translate("Project created"),
				BAB_TM_EV_PROJECT_DELETED => bab_translate("Project deleted"),
				BAB_TM_EV_TASK_CREATED => bab_translate("Task created"),
				BAB_TM_EV_TASK_UPDATED_BY_MGR => bab_translate("Task updated by manager"),
				BAB_TM_EV_TASK_UPDATED_BY_RESP => bab_translate("Task updated by responsible"),
				BAB_TM_EV_TASK_DELETED => bab_translate("Task deleted"),
				BAB_TM_EV_NOTICE_ALERT => bab_translate("Alert notice")
			);
			
			$this->set_data('modifyIdx', BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM);
			$this->set_data('modifyAction', BAB_TM_ACTION_MODIFY_NOTICE_EVENT);

			$this->set_data('tg', bab_rp('tg', ''));
			
			$oTmCtx =& getTskMgrContext();
			$this->m_iIdProjectSpace = $oTmCtx->getIdProjectSpace();
			$this->m_iIdProject = $oTmCtx->getIdProject();
		}

		function nextEvent()
		{
			$datas = each($this->m_aEvent);
			
			if(false != $datas)
			{
				$this->m_is_altbg = !$this->m_is_altbg;
				$this->set_data('iIdEvent', $datas['key']);
				$this->set_data('sEventName', $datas['value']);
				return true;
			}
			return false;
		}
		
		function nextProfil()
		{
			$datas = each($this->m_aProfil);
			
			if(false != $datas)
			{
			    $iIdEvent = null;
				$this->get_data('iIdEvent', $iIdEvent);
				
				$iProfil =& $datas['value'];
				$this->set_data('iProfil', $iProfil);
				
				$this->set_data('sCheckedNotice', '');
				if(bab_isNoticeEventSet($this->m_iIdProjectSpace, $this->m_iIdProject, $iIdEvent, $iProfil))
				{
					$this->set_data('sCheckedNotice', 'checked="checked"');
				}
				return true;
			}
			else 
			{
				reset($this->m_aProfil);
				return false;
			}
		}
	}		
	
	global $babBody;
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();
	
	$tg = bab_rp('tg', '');
	
	$itemMenu = array(
		array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST,
			'mnuStr' => bab_translate("Projects spaces"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=' . $tg . '&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_SPACES_LIST));
/*			
	if('usrTskMgr' == $tg)
	{
		$itemMenu[] = array(
			'idx' => BAB_TM_IDX_DISPLAY_PROJECTS_LIST,
			'mnuStr' => bab_translate("Projects list"),
			'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_PROJECTS_LIST . 
			'&iIdProjectSpace=' . $iIdProjectSpace);

	}
//*/			
	$itemMenu[] = array(
		'idx' => BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM,
		'mnuStr' => bab_translate("Notice events"),
		'url' => $GLOBALS['babUrlScript'] . '?tg=usrTskMgr&idx=' . BAB_TM_IDX_DISPLAY_NOTICE_EVENT_FORM . 
		'&iIdProjectSpace=' . $iIdProjectSpace);

	add_item_menu($itemMenu);
	$babBody->title = bab_translate("Notice events");
	
	
	$oNoticeEvent = new BAB_NoticeEvent();
	$babBody->babecho(bab_printTemplate($oNoticeEvent, 'tmCommon.html', 'noticeForm'));
}

function modifyNoticeEvent()
{
	//bab_debug(__FUNCTION__);
	
	$aNoticeEvent = (isset($_POST['notiveEvent'])) ? $_POST['notiveEvent'] : array();
	//bab_debug($aNoticeEvent);
	
	$oTmCtx =& getTskMgrContext();
	$iIdProjectSpace = $oTmCtx->getIdProjectSpace();
	$iIdProject = $oTmCtx->getIdProject();

	bab_deleteAllNoticeEvent($iIdProjectSpace, $iIdProject);
	
	//{ m_datas[iIdEvent] }_{ m_datas[iProfil] }
	$iIdEventIdx = 0;
	$iProfilIdx = 1;
	
	foreach($aNoticeEvent as $key => $value)
	{
		$aItems = explode('_', $value);
		if(false !== $aItems && count($aItems) == 2)
		{
			bab_createNoticeEvent($iIdProjectSpace, $iIdProject, $aItems[$iIdEventIdx], $aItems[$iProfilIdx]);
		}
	}
}
?>