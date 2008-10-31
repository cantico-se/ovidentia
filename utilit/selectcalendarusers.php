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
include_once 'base.php';
include_once dirname(__FILE__).'/selectusers.php';


/**
 * @package selectusers
 *
 * Select multiple users for multiple right 
 * for the calendar
 * 
 * @author Zébina Samuel
 */
class bab_selectCalendarUsers extends bab_selectUsersBase
{
	var $sAccessView			= '';
	var $sAccessUpdate			= '';
	var $sAccessFull			= '';
	var $sAccessSharedUpdate	= '';
	var $sAccessSharedFull		= '';
	var $sSelect				= '';
	var $sAll					= '';
	var $sNone					= '';
	var $sGrapCaption			= '';
	var $sDropCaption			= '';
	var $sMoveTo				= '';
	var $sWildcard				= '';
	
	function bab_selectCalendarUsers() 
	{
		parent::bab_selectUsersBase();	
	}

	function init()
	{
		$this->t_search						= bab_translate("Search by login ID, firstname and lastname");
		$this->t_selected_users				= bab_translate("Selected users");
		$this->t_searchsubmit				= bab_translate("Search");
		$this->t_view_directory_entry_for	= bab_translate("View directory entry for");
		$this->searchtext					= '';
		$this->sTemplateFilename 			= 'selectusers.html';
		$this->sTemplateName 				= 'selectCalendarUsers';
		$this->sAccessView					= bab_translate("Consultation");
		$this->sAccessUpdate				= bab_translate("Creation and modification");
		$this->sAccessFull					= bab_translate("Full access");
		$this->sAccessSharedUpdate			= bab_translate("Shared creation and modification");
		$this->sAccessSharedFull			= bab_translate("Shared full access");
		$this->sGrapCaption					= bab_translate("Grab users");
		$this->sDropCaption					= bab_translate("Drop users");
		$this->sSelect						= bab_translate("Select");
		$this->sAll							= bab_translate("All");
		$this->sNone						= bab_translate("None");
		$this->sMoveTo						= bab_translate("Move to");
		$this->sWildcard					= bab_translate("The character * allows you to retrieve a list of all users");
		
		$sCleanSessVar = (null == bab_rp('sCleanSessVar', null)) ? 'Y' : 'N';
		
		$this->aSessionKey = array(BAB_CAL_ACCESS_VIEW => 'bab_calAccessView', 
			BAB_CAL_ACCESS_UPDATE => 'bab_calAccessUpdate', 
			BAB_CAL_ACCESS_FULL => 'bab_calAccessFull', 
			BAB_CAL_ACCESS_SHARED_UPDATE => 'bab_calAccessSharedUpdate',
			BAB_CAL_ACCESS_SHARED_FULL => 'bab_calAccessSharedFull');

		foreach($this->aSessionKey as $iAccess => $sArrayName)
		{
			if(!array_key_exists($sArrayName, $_SESSION) || 'Y' == $sCleanSessVar)
			{
				$_SESSION[$sArrayName] = array();
			}
		}
	}

	function getSessionKey($iAccess)
	{
		if(array_key_exists($iAccess, $this->aSessionKey))
		{
			return $this->aSessionKey[$iAccess];
		}
		return null;
	}

	function getSessionKeyByInputBtnName($sButtonName)
	{
		//Remove prefix. Valid prefix are sGrabAccess and sDropAccess.
		//All the prefix have the same size
		$sAccess = substr($sButtonName, strlen('sGrabAccess'));
		if(false !== $sAccess)
		{
			$aAccess = array('View' => BAB_CAL_ACCESS_VIEW, 'Update' => BAB_CAL_ACCESS_UPDATE, 
				'Full' => BAB_CAL_ACCESS_FULL, 'SharedUpdate' => BAB_CAL_ACCESS_SHARED_UPDATE, 
				'SharedFull' => BAB_CAL_ACCESS_SHARED_FULL);
			
			if(array_key_exists($sAccess, $aAccess))
			{
				return $this->getSessionKey($aAccess[$sAccess]);
			}
		}
		return null;
	}

	function getArrayNameKeyByInputBtnName($sButtonName)
	{
		//Remove prefix. Valid prefix are sGrabAccess and sDropAccess.
		//All the prefix have the same size
		$sAccess = substr($sButtonName, strlen('sGrabAccess'));
		if(false !== $sAccess)
		{
			$aAccess = array('View' => 'aAccessView', 'Update' => 'aAccessUpdate', 
				'Full' => 'aAccessFull', 'SharedUpdate' => 'aAccessSharedUpdate', 
				'SharedFull' => 'aAccessSharedFull');
			
			if(array_key_exists($sAccess, $aAccess))
			{
				return $aAccess[$sAccess];
			}
		}
		return null;
	}
	
	function addUser($iIdUser, $iAccess)
	{
		if((int) $iIdUser === (int) $GLOBALS['BAB_SESS_USERID'])
		{
			return;;
		}
		
		$sKey = $this->getSessionKey($iAccess);
		if(!is_null($sKey))
		{
			$_SESSION[$sKey][$iIdUser] = $iIdUser;
		}
	}

	function _getNextAccessViewItem()
	{
		$sSessionKey = 'bab_calAccessView';
		return $this->_getNextUser($sSessionKey);
	}

	function _getNextAccessUpdateItem()
	{
		$sSessionKey = 'bab_calAccessUpdate';
		return $this->_getNextUser($sSessionKey);
	}

	function _getNextAccessFullItem()
	{
		$sSessionKey = 'bab_calAccessFull';
		return $this->_getNextUser($sSessionKey);
	}

	function _getNextAccessSharedUpdateItem()
	{
		$sSessionKey = 'bab_calAccessSharedUpdate';
		return $this->_getNextUser($sSessionKey);
	}

	function _getNextAccessSharedFullItem()
	{
		$sSessionKey = 'bab_calAccessSharedFull';
		return $this->_getNextUser($sSessionKey);
	}
	
	function processAction()
	{
		$act = isset($_POST['act']) ? key($_POST['act']) : false;

		switch($act) 
		{
			case 'search':
				break;
				
				
			case 'sRefresh':
				foreach($this->aSessionKey as $iAccess => $sArrayName)
				{
					$_SESSION[$sArrayName] = array();
				}
				
				$aInputBtnName = array('sGrabAccessView', 'sGrabAccessUpdate', 
					'sGrabAccessFull', 'sGrabAccessSharedUpdate',
					'sGrabAccessSharedFull');
				
				foreach($aInputBtnName as $sBtnName) 
				{
					$sArrayName = $this->getArrayNameKeyByInputBtnName($sBtnName);
					if(!is_null($sArrayName))
					{
						if(isset($_POST[$sArrayName]) && 0 < count($_POST[$sArrayName])) 
						{
							$sKey = $this->getSessionKeyByInputBtnName($sBtnName);
							if(!is_null($sKey))
							{
								foreach($_POST[$sArrayName] as $iIdUser) 
								{
									$_SESSION[$sKey][$iIdUser] = $iIdUser;
								}
							}
						}
					}
				}
				break;
				
			case 'sGrabAccessView':
			case 'sGrabAccessUpdate':
			case 'sGrabAccessFull':
			case 'sGrabAccessSharedUpdate':
			case 'sGrabAccessSharedFull':
				if(isset($_POST['aSearchResult']) && 0 < count($_POST['aSearchResult'])) 
				{
					$sKey = $this->getSessionKeyByInputBtnName($act);
					if(!is_null($sKey))
					{
						foreach($_POST['aSearchResult'] as $iIdUser) 
						{
							if((int) $iIdUser !== (int) $GLOBALS['BAB_SESS_USERID'])
							{
								$_SESSION[$sKey][$iIdUser] = $iIdUser;
							}
						}
					}
				}
				break;

			case 'sDropAccessView':
			case 'sDropAccessUpdate':
			case 'sDropAccessFull':
			case 'sDropAccessSharedUpdate':
			case 'sDropAccessSharedFull':
				$sArrayName = $this->getArrayNameKeyByInputBtnName($act);
				if(!is_null($sArrayName))
				{
					if(isset($_POST[$sArrayName]) && 0 < count($_POST[$sArrayName])) 
					{
						$sKey = $this->getSessionKeyByInputBtnName($act);
						if(!is_null($sKey))
						{
							foreach($_POST[$sArrayName] as $iIdUser) 
							{
								unset($_SESSION[$sKey][$iIdUser]);
							}
						}
					}
				}
				break;

			case 'record':
				if(!empty($this->auto_include_file)) 
				{
					include_once $this->auto_include_file;
				}
				
				$aCalUserAccess = array();

				foreach($this->aSessionKey as $iAccess => $sArrayName)
				{
					$aCalUserAccess[$iAccess] =& $_SESSION[$sArrayName];
				}
				call_user_func($this->callback, $aCalUserAccess, $this->hidden['calid']);
				break;
								
			default:
				break;
		}
	}
}
