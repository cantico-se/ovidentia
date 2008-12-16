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
	require_once $GLOBALS['babInstallPath'] . 'utilit/mailincl.php';
	require_once $GLOBALS['babInstallPath'] . 'admin/acl.php';

	$GLOBALS['g_aEmailMsg'] = array(
		BAB_TM_EV_PROJECT_CREATED => 
			array('subject' => bab_translate("Project creation"),
				'body' => bab_translate("The project %s have been created in the project space %s")),
		BAB_TM_EV_PROJECT_DELETED => 
			array('subject' => bab_translate("Project deletion"), 
				'body' => bab_translate("The project %s have been deleted from the project space %s")),
		BAB_TM_EV_TASK_CREATED => 
			array('subject' => bab_translate("Task creation"), 
				'body' => bab_translate("The task %s have been added to the project %s in the project space %s")),
		BAB_TM_EV_TASK_UPDATED_BY_MGR => 
			array('subject' => bab_translate("Task update"), 
				'body' => bab_translate("The task %s of the project %s in the project space %s have been updated by %s")),
		BAB_TM_EV_TASK_UPDATED_BY_RESP => 
			array('subject' => bab_translate("Task update"), 
				'body' => bab_translate("The task %s of the project %s in the project space %s have been updated by %s")),
		BAB_TM_EV_TASK_DELETED => 
			array('subject' => bab_translate("Task deletion"), 
				'body' => bab_translate("The task %s of the project %s in the project space %s have been deleted by %s")),
		BAB_TM_EV_NOTICE_ALERT => 
			array('subject' => bab_translate("Notice alert"), 
				'body' => bab_translate("The task %s of the project %s in the project space %s is about to exceed the envisaged time")),
		BAB_TM_EV_NEW_TASK_RESPONSIBLE =>
			array('subject' => bab_translate("Task update"),
				'body' => bab_translate("You are responsible for task %s of the %s project in %s project space")),
		BAB_TM_EV_NO_MORE_TASK_RESPONSIBLE =>
			array('subject' => bab_translate("Task update"),
				'body' => bab_translate("You are not responsible any more for the task %s of the %s project in %s project space")),
		BAB_TM_EV_TASK_RESPONSIBLE_PROPOSED =>
			array('subject' => bab_translate("Task update"),
				'body' => bab_translate("You are responsible for task %s of the %s project in %s project space. Please accept/refuse")),
		BAB_TM_EV_TASK_STARTED => 
			array('subject' => bab_translate("Task update"), 
				'body' => bab_translate("The task %s of the project %s in the project space %s is started"))
	);
	
	class BAB_TM_SendEmail extends BAB_BaseFormProcessing
	{
		var $m_mail;

		function BAB_TM_SendEmail()
		{
			parent::BAB_BaseFormProcessing();

			$this->m_mail = bab_mail();
		}

		function send_notification($user_email, $subject, $body)
		{
			if($this->m_mail === false || mb_strlen(trim($user_email)) < 0)
			{
				return;
			}
			
			$this->get_admin_info($admin_name, $admin_email);

			$this->m_mail->mailFrom(mysql_escape_string($admin_email), mysql_escape_string($admin_name));

			$this->m_mail->mailSubject($subject);

			$this->m_datas['body'] = & $body;

			$message = $this->m_mail->mailTemplate(bab_printTemplate($this, 'tmCommon.html', 'notify_user'));

			$this->m_mail->mailBody($message, "html");

			$this->m_mail->mailTo($user_email);

			if(false == @$this->m_mail->send())
			{
				//echo ' send Error ==> ' . $this->m_mail->mail->$ErrorInfo . '<br />';
			}

			$this->m_mail->clearTo();
		}

		function addFileAttach($fname, $realname, $type)
		{
			$this->m_mail->mailFileAttach($fname, $realname, $type);
		}

		function clearAttachments()
		{
			$this->m_mail->mailClearAttachments();
		}
		
		function get_admin_info(&$admin_name, &$admin_email)
		{
			$admin_email = $GLOBALS['babAdminEmail'];
			$admin_name = $GLOBALS['babAdminName'];
		}
	}	
	
	function sendNotice($iIdProjectSpace, $iIdProject, $iIdTask, $iIdEvent, $sSubject, $sBody)
	{
		if(0 != $iIdProjectSpace && 0 != $iIdProject)
		{
			$oMail = new BAB_TM_SendEmail();
	
			$aProfilToGrpTbl = array(
				BAB_TM_SUPERVISOR => BAB_TSKMGR_PROJECTS_SUPERVISORS_GROUPS_TBL,
				BAB_TM_PROJECT_MANAGER => BAB_TSKMGR_PROJECTS_MANAGERS_GROUPS_TBL); 
		
			$aNotifiedIdUser = array();
	
			foreach($aProfilToGrpTbl as $iProfil => $sTblName)
			{
				if(bab_isNoticeEventSet($iIdProjectSpace, $iIdProject, $iIdEvent, $iProfil))
				{
					$aUsers = aclGetAccessUsers($sTblName, $iIdProject);
					
					//bab_debug($aUsers);
					
					foreach($aUsers as $key => $aUserInfo)
					{
						$iIdUser = bab_getUserIdByEmail($aUserInfo['email']);
						//$iIdUser = bab_getUserId($aUserInfo['name']);
						if(!isset($aNotifiedIdUser[$iIdUser]))
						{
							$aNotifiedIdUser[$iIdUser] = $iIdUser;
							$oMail->send_notification($aUserInfo['email'], $sSubject, $sBody);
						}
					}
				}
			}

			if(bab_isNoticeEventSet($iIdProjectSpace, $iIdProject, $iIdEvent, BAB_TM_TASK_RESPONSIBLE))
			{
				//if($iIdEvent == BAB_TM_EV_TASK_UPDATED_BY_MGR)
				{
					bab_getTaskResponsibles($iIdTask, $aTaskResponsibles);
					foreach($aTaskResponsibles as $iIdResponsible => $aResponsible)
					{
						if(!isset($aNotifiedIdUser[$iIdResponsible]))
						{
							$aNotifiedIdUser[$iIdResponsible] = $iIdResponsible;
							$oMail->send_notification($aResponsible['email'], $sSubject, $sBody);
						}
					}
				}
			}
		}
	}
?>