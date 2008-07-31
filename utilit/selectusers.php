<?php

/**
 * @package selectusers
 *
 * Select multiple users by form
 */
class bab_selectUsersBase 
{
	var $hidden = array();
	var $res;
	var $callback;
	var $auto_include_file;
	
	var $aSessionKey = array();
	
 	/**
	 * Identifier of the group in which the users will be searched
	 * 
	 * @access private 
	 * @var integer
	 */
	var $iIdGroup = null;

 	/**
	 * Array of user id that will be excluded from the search
	 * 
	 * @access private 
	 * @var integer
	 */
	var $aExcludedIdUser = null;
	
	var $sTemplateFilename = '';
	var $sTemplateName = '';
	
	function bab_selectUsersBase() 
	{
		$this->init();
		
		$this->t_record	= bab_translate("Record");
		
		if($tg = bab_rp('tg')) 
		{
			$this->addVar('tg', $tg);
		}

		if($idx = bab_rp('idx')) 
		{
			$this->addVar('idx', $idx);
		}
	}

	function init()
	{
		die('This class must not be instanciated');
	}
	
	/**
	 * @private
	 */
	function _getnextsearchresult() 
	{
		global $babDB;
		if($this->res && $arr = $babDB->db_fetch_assoc($this->res)) 
		{
			$this->id_user	= bab_toHtml($arr['id']);
			$this->username = bab_toHtml(bab_composeUserName($arr['firstname'], $arr['lastname']));
			$url = bab_getUserDirEntryLink($arr['id']);
			$this->entry_url = $url ? bab_toHtml($url, BAB_HTML_JS) : '';
			return true;
		}
		return false;
	}

	/**
	 * @private
	 */
	function _getNextUser($sSessionKey)
	{
		static $aUserList = NULL;

		if(is_null($aUserList)) 
		{
			if(!array_key_exists($sSessionKey, $_SESSION))
			{
				return false;
			}
			
			$aUserList = array();
			foreach($_SESSION[$sSessionKey] as $iIdUser) 
			{
				$aUserList[$iIdUser] = bab_getUserName($iIdUser);
			}
			natcasesort($iIdUser);
		}

		if(list($this->id_user, $u) = each($aUserList)) 
		{
			$this->username = bab_toHtml($u);
			return true;
		}
		
		$aUserList = NULL;
		return false;
	}
	
	/**
	 * @public
	 */
	function setRecordLabel($label) {
		$this->t_record = bab_toHtml($label);
	}
	
	/**
	 * @private
	 */
	function _getnexthidden() 
	{
		if(list($name, $value) = each($this->hidden)) 
		{
			$this->name = bab_toHtml($name);
			$this->value = bab_toHtml($value);
			return true;
		}
		return false;
	}

	/**
	 * Add a variable
	 * @param string $name
	 * @param string $value
	 */
	function addVar($name, $value) 
	{
		$this->hidden[$name] = $value;
	}


	/**
	 * Set the identifier of the group in which the users will be searched
	 * @public
	 * @param int $iIdGroup
	 */
	function setGroupId($iIdGroup) 
	{
		$this->iIdGroup = $iIdGroup;
	}


	/**
	 * Set user identifier that will be excluded in the search
	 * @public
	 * @param Array $aExcludesIdUser
	 */
	function setExcludedUserId($aExcludedIdUser) 
	{
		if(is_array($aExcludedIdUser) && count($aExcludedIdUser) > 0)
		$this->aExcludedIdUser = $aExcludedIdUser;
	}

	
	function processAction()
	{
	}

	function getSelectQueryString($sSearchText)
	{
		$sUsrGrpInnerJoin		= ' ';
		$sUsrGrpWhereClause		= ' ';
		$sUsrGrpGroupBy			= ' ';
		
		if(!is_null($this->iIdGroup))
		{
			$sUsrGrpInnerJoin = 
				',' . BAB_USERS_GROUPS_TBL . ' usrGrp';
			$sUsrGrpWhereClause = 
				' AND usrGrp.id_group = \'' . $this->iIdGroup . '\' AND usrGrp.id_object = usr.id';
			$sUsrGrpGroupBy = 
				'GROUP BY usr.id';
		}

		$sExcludedUserIdWhereClause = $this->processEcludedUserId();

		global $babDB;
		
		$sQuery = 
			'SELECT ' .
				'usr.id, ' .
				'usr.firstname, ' .
				'usr.lastname ' .
			'FROM ' . 
				BAB_USERS_TBL . ' usr ' . 
			$sUsrGrpInnerJoin . ' ' .
			'WHERE ' .
				'disabled=\'0\' AND ' .
				'is_confirmed=\'1\'';
		
		if('*' !== $sSearchText)
		{
			$sQuery .= 
				' AND ' . 
				'(	' .
					'nickname	LIKE \'%' . $babDB->db_escape_like($sSearchText) . '%\' OR '  .
					'firstname	LIKE \'%' . $babDB->db_escape_like($sSearchText) . '%\' OR '  .
					'lastname	LIKE \'%' . $babDB->db_escape_like($sSearchText) . '%\' ' . 
				')';
		}

		$sQuery .= ' ' . $sUsrGrpWhereClause . $sExcludedUserIdWhereClause;		
		$sQuery .= $sUsrGrpGroupBy;
		$sQuery .= " ORDER BY lastname,firstname";
		return $sQuery;
	}
	
	function doSearch()
	{
		if(!empty($_POST['searchtext'])) 
		{
			$sSearchtext = &$_POST['searchtext'];
			
			$sQuery = $this->getSelectQueryString($sSearchtext);
			//bab_debug($sQuery);
			
			global $babDB;
			$this->res = $babDB->db_query($sQuery);
			
			$this->searchtext = bab_toHtml($sSearchtext);
		}
	}
	
	/**
	 * get html for the form
	 * @public
	 * @return string HTML
	 */
	function getHtml() 
	{
		$this->processAction();
		$this->doSearch();		
		return bab_printTemplate($this, $this->sTemplateFilename, $this->sTemplateName);
	}
	
	/**
	 * callback will be called with two parameters
	 *  - array of id_user
	 *  - array of $name, $value defined by $this->addVar()
	 *
	 * @param string|array	$callback
	 * @param string		$auto_include_file
	 */
	function setRecordCallback($callback, $auto_include_file = '') 
	{
		$this->callback = $callback;
		$this->auto_include_file = $auto_include_file;
	}

	/**
	 * Return the string that will be added in the where clause
	 *
	 * @param string The excluded user id
	 */
	function processEcludedUserId()
	{
		global $babDB;
		$sExcludedIdUser = '';

		if(!is_null($this->aExcludedIdUser))
		{
			$sExcludedIdUser = $babDB->quote($this->aExcludedIdUser);
		}

		if(is_array($this->aSessionKey) && count($this->aSessionKey) > 0)
		{
			$aExcludedUserId = array();
			
			foreach($this->aSessionKey as $iKey => $sSessionKey)
			{
				if(array_key_exists($sSessionKey, $_SESSION) && 0 < count($_SESSION[$sSessionKey]))
				{
					$aExcludedUserId = array_merge($aExcludedUserId, $_SESSION[$sSessionKey]);
				}
			}
			
			if(0 < count($aExcludedUserId))
			{
				if(strlen($sExcludedIdUser) > 0)
				{
					$sExcludedIdUser .= ', ';
				}
				$sExcludedIdUser .= $babDB->quote($aExcludedUserId);
			}
	
			if(strlen($sExcludedIdUser) > 0)
			{
				return sprintf(' AND usr.id NOT IN(%s)', $sExcludedIdUser);
			}
		}
		return $sExcludedIdUser;
	}
}






/**
 * @package selectusers
 *
 * Select multiple users by form
 */
class bab_selectusers extends bab_selectUsersBase
{
	var $selected;
	
	function bab_selectusers() 
	{
		parent::bab_selectUsersBase();
	}

	function init()
	{
		$this->t_search						= bab_translate("Search by login, firstname and lastname");
		$this->t_grab_users					= bab_translate("Grab users");
		$this->t_drop_users					= bab_translate("Drop users");
		$this->t_selected_users				= bab_translate("Selected users");
		$this->t_searchsubmit				= bab_translate("Search");
		$this->t_view_directory_entry_for	= bab_translate("View directory entry for");
		$this->searchtext					= '';
		$this->selected						= array();
		$this->res							= false;
		$this->sTemplateFilename 			= 'selectusers.html';
		$this->sTemplateName 				= 'select';
		$this->aSessionKey					= array('bab_selectusers');
	}

	/**
	 * Add a selected user
	 * @public
	 * @param	int	$id_user
	 */
	function addUser($id_user) {
		$this->selected[$id_user] = $id_user;
	}
	
	/**
	 * @private
	 */
	function _getnextselecteduser() 
	{
		$sSessionKey = 'bab_selectusers';
		return $this->_getNextUser($sSessionKey);
	}
	
	function processAction()
	{
		$act = isset($_POST['act']) ? key($_POST['act']) : false;

		switch($act) 
		{
			case 'search':
				break;


			case 'grab':
				if(isset($_POST['searchresult']) && 0 < count($_POST['searchresult'])) 
				{
					foreach($_POST['searchresult'] as $id_user) 
					{
						$_SESSION['bab_selectusers'][$id_user] = $id_user;
					}
				}
				break;

			case 'drop':
				if(isset($_POST['selectedusers']) && 0 < count($_POST['selectedusers'])) 
				{
					foreach($_POST['selectedusers'] as $id_user) 
					{
						unset($_SESSION['bab_selectusers'][$id_user]);
					}
				}
				break;

			case 'record':
				if(!empty($this->auto_include_file)) 
				{
					include_once $this->auto_include_file;
				}
				call_user_func($this->callback, $_SESSION['bab_selectusers'], $this->hidden);
				break;

			default:
				$_SESSION['bab_selectusers'] = $this->selected;
				break;
		}
	}
}


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
	
	function bab_selectCalendarUsers() 
	{
		parent::bab_selectUsersBase();	
	}

	function init()
	{
		$this->t_search						= bab_translate("Search by login, firstname and lastname");
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
		
		$this->aSessionKey = array(BAB_CAL_ACCESS_VIEW => 'bab_calAccessView', 
			BAB_CAL_ACCESS_UPDATE => 'bab_calAccessUpdate', 
			BAB_CAL_ACCESS_FULL => 'bab_calAccessFull', 
			BAB_CAL_ACCESS_SHARED_UPDATE => 'bab_calAccessSharedUpdate',
			BAB_CAL_ACCESS_SHARED_FULL => 'bab_calAccessSharedFull');
			
		foreach($this->aSessionKey as $iAccess => $sArrayName)
		{
			if(!array_key_exists($sArrayName, $_SESSION))
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
							if((int) $iIdUser === (int) $GLOBALS['BAB_SESS_USERID'])
							{
								return;;
							}
							
							$_SESSION[$sKey][$iIdUser] = $iIdUser;
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
?>