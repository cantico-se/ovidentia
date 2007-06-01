<?php

/**
 * @package selectusers
 *
 * Select multiple users by form
 */
class bab_selectusers {

	var $hidden = array();
	var $res;
	var $callback;
	var $auto_include_file;
	var $selected;

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

	
	function bab_selectusers() {
		$this->db = & $GLOBALS['babDB'];

		$this->t_search			= bab_translate("Search by login, firstname and lastname");
		$this->t_grab_users		= bab_translate("Grab users");
		$this->t_drop_users		= bab_translate("Drop users");
		$this->t_record			= bab_translate("Record");
		$this->t_selected_users	= bab_translate("Selected users");
		$this->t_searchsubmit	= bab_translate("Search");
		$this->searchtext		= '';
		$this->selected			= array();
		$this->res				= false;


		if ($tg = bab_rp('tg')) {
			$this->addVar('tg', $tg);
		}

		if ($idx = bab_rp('idx')) {
			$this->addVar('idx', $idx);
		}
	}

	/**
	 * @private
	 */
	function _getnextsearchresult() {
		if ($this->res && $arr = $this->db->db_fetch_assoc($this->res)) {
			$this->id_user	= bab_toHtml($arr['id']);
			$this->username = bab_toHtml(bab_composeUserName($arr['firstname'], $arr['lastname']));
			return true;
		}
		return false;
	}

	/**
	 * @private
	 */
	function _getnextselecteduser() {

		static $list = NULL;

		if (NULL === $list) {
			$list = array();
			foreach($_SESSION['bab_selectusers'] as $id_user) {
				$list[$id_user] = bab_getUserName($id_user);
			}
			natcasesort($list);
		}

		if (list($this->id_user,$u) = each($list)) {
			$this->username = bab_toHtml($u);
			return true;
		}
		return false;
	}

	/**
	 * @private
	 */
	function _getnexthidden() {
		if (list($name, $value) = each($this->hidden)) {
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
	function addVar($name, $value) {
		$this->hidden[$name] = $value;
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
	 * @public
	 */
	function setRecordLabel($label) {
		$this->t_record = bab_toHtml($label);
	}


	/**
	 * Set the identifier of the group in which the users will be searched
	 * @public
	 * @param int $iIdGroup
	 */
	function setGroupId($iIdGroup) {
		$this->iIdGroup = $iIdGroup;
	}


	/**
	 * Set user identifier that will be excluded in the search
	 * @public
	 * @param Array $aExcludesIdUser
	 */
	function setExcludedUserId($aExcludedIdUser) {
		if(is_array($aExcludedIdUser) && count($aExcludedIdUser) > 0)
		$this->aExcludedIdUser = $aExcludedIdUser;
	}

	/**
	 * get html for the form
	 * @public
	 * @return string HTML
	 */
	function getHtml() {
		$act = isset($_POST['act']) ? key($_POST['act']) : false;


		switch($act) {
			case 'search':
				
				break;


			case 'grab':
				if (isset($_POST['searchresult']) && 0 < count($_POST['searchresult'])) {
					foreach($_POST['searchresult'] as $id_user) {
						$_SESSION['bab_selectusers'][$id_user] = $id_user;
					}
				}
				break;

			case 'drop':
				if (isset($_POST['selectedusers']) && 0 < count($_POST['selectedusers'])) {
					foreach($_POST['selectedusers'] as $id_user) {
						unset($_SESSION['bab_selectusers'][$id_user]);
					}
				}
				break;

			case 'record':
				if (!empty($this->auto_include_file)) {
					include_once $this->auto_include_file;
				}
				call_user_func($this->callback, $_SESSION['bab_selectusers'], $this->hidden);
				break;

			default:
				$_SESSION['bab_selectusers'] = $this->selected;
				break;
		}

		if(!empty($_POST['searchtext'])) 
		{
			$searchtext = &$_POST['searchtext'];

			$sUsrGrpInnerJoin = ' ';
			$sUsrGrpWhereClause = ' ';
			$sUsrGrpGroupBy = ' ';
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

			$query = 
				'SELECT ' .
					'usr.id, ' .
					'usr.firstname, ' .
					'usr.lastname ' .
				'FROM ' . 
					BAB_USERS_TBL . ' usr ' . 
				$sUsrGrpInnerJoin . ' ' .
				'WHERE ' .
					'disabled=\'0\' AND ' .
					'is_confirmed=\'1\' AND ' . 
					'(	' .
						'nickname	LIKE \'%' . $this->db->db_escape_like($searchtext) . '%\' OR '  .
						'firstname	LIKE \'%' . $this->db->db_escape_like($searchtext) . '%\' OR '  .
						'lastname	LIKE \'%' . $this->db->db_escape_like($searchtext) . '%\' ' . 
					') ' .
					$sUsrGrpWhereClause . $sExcludedUserIdWhereClause;

			$query .= $sUsrGrpGroupBy;
			$query .= " ORDER BY lastname,firstname";
			//bab_debug($query);
			$this->res = $this->db->db_query($query);
			
			$this->searchtext = bab_toHtml($searchtext);
		}

		return bab_printTemplate($this,"selectusers.html", "select");
	}

	/**
	 * callback will be called with two parameters
	 *  - array of id_user
	 *  - array of $name, $value defined by $this->addVar()
	 *
	 * @param string|array	$callback
	 * @param string		$auto_include_file
	 */
	function setRecordCallback($callback, $auto_include_file = '') {
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
		$sExcludedIdUser = '';

		if(!is_null($this->aExcludedIdUser))
		{
			$sExcludedIdUser = $this->db->quote($this->aExcludedIdUser);
		}

		if(0 < count($_SESSION['bab_selectusers']))
		{
			if(strlen($sExcludedIdUser) > 0)
			{
				$sExcludedIdUser .= ', ';
			}
			$sExcludedIdUser .= $this->db->quote($_SESSION['bab_selectusers']);
		}

		if(strlen($sExcludedIdUser) > 0)
		{
			return sprintf(' AND usr.id NOT IN(%s)', $sExcludedIdUser);
		}
		return $sExcludedIdUser;
	}
}


?>