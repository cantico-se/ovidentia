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



/**
 * Get delegation objects in an array
 * object with url are displayed in administration section only if the user is superadmin or delegated on this object
 * for users and groups, the link is allways displayed for delegated administors
 *
 * 0 : object name as in dg_groups table
 * 1 : object name, translated
 * 2 : sitemap ID without the bab prefix or null if no url
 * 3 : url or null
 * 4 : description or null
 * 5 : icon classname
 *
 * @return array
 */
function bab_getDelegationsObjects()
{
	static $objects = null;

	if (null === $objects)
	{
		bab_functionality::includeOriginal('Icons');

		$babUrlScript	= $GLOBALS['babUrl'].bab_getSelf();

		$objects = array(
			array("users"		, bab_translate("Create a new user")	, null				, null											, null, null),
			array("groups"		, bab_translate("Manage groups")		, null				, null											, null, null),
			array('battach'		, bab_translate("Assign/unassign a user to group and group children"), null, null							, null, null),
			array("sections"	, bab_translate("Sections")				, 'AdminSections'	, $babUrlScript.'?tg=sections'					, null, Func_Icons::APPS_SECTIONS),
			array("articles"	, bab_translate("Articles")				, 'AdminArticles'	, $babUrlScript.'?tg=topcats'					, bab_translate("Categories and topics management"), Func_Icons::APPS_ARTICLES),
			array("faqs"		, bab_translate("Faq")					, 'AdminFaqs'		, $babUrlScript.'?tg=admfaqs'					, bab_translate("Frequently Asked Questions"), Func_Icons::APPS_FAQS),
			array("forums"		, bab_translate("Forums")				, 'AdminForums'		, $babUrlScript.'?tg=forums'					, null, Func_Icons::APPS_FORUMS),
			array("calendars"	, bab_translate("Calendar")				, 'AdminCalendars'	, $babUrlScript.'?tg=admcals'					, null, Func_Icons::APPS_CALENDAR),
			array("directories"	, bab_translate("Directories")			, 'AdminDir'		, $babUrlScript.'?tg=admdir'					, null, Func_Icons::APPS_DIRECTORIES),
			array("approbations", bab_translate("Approbation schemas")	, 'AdminApprob'		, null					                        , null, null),
			array("filemanager"	, bab_translate("File manager")			, 'AdminFm'			, $babUrlScript.'?tg=admfms'					, null, Func_Icons::APPS_FILE_MANAGER),
			array("orgchart"	, bab_translate("Charts")				, 'AdminCharts'		, $babUrlScript.'?tg=admocs'					, null, Func_Icons::APPS_ORGCHARTS)
		//	array("taskmanager"	, bab_translate("Task Manager")			, 'AdminTm'			, $babUrlScript.'?tg=admTskMgr'					, null, Func_Icons::APPS_TASK_MANAGER)
		);
	}

	return $objects;
}


/**
 * Set current user delegation
 *
 * @param	int		$iIdDelegation
 */
function bab_setCurrentUserDelegation($iIdDelegation)
{
	$_SESSION['babCurrentDelegation'] = (int) $iIdDelegation;
}



/**
 * Returns a valid delegation for the current user.
 *
 * @return int
 */
function bab_getCurrentUserDefaultDelegation()
{
	//May it is not a good idea to comment this. /!\ if it cause issue it should be uncomment.
	/*$aCurrUsrDg = bab_getUserFmVisibleDelegations();
	if (count($aCurrUsrDg) > 1) {
		$aItem = each($aCurrUsrDg);
		$aItem = each($aCurrUsrDg);
		if (false !== $aItem) {
			return $aItem['key'];
		}
	}*/
	return 0;
}


/**
 * Get current user delegation
 *
 * @param bool	$useDefault		true to initialize current delegation with a valid delegation if it was not set before.
 * @return 	int					or null if no current delegation.
 */
function bab_getCurrentUserDelegation($useDefault = true)
{
	require_once dirname(__FILE__) . '/fileincl.php';

	if (array_key_exists('babCurrentDelegation', $_SESSION)) {
		return (int) $_SESSION['babCurrentDelegation'];
	}
	if ($useDefault) {
		$currentDelegation = bab_getCurrentUserDefaultDelegation();
		bab_setCurrentUserDelegation($currentDelegation);
		return $currentDelegation;
	}
	return null;
}




/**
 * @return array
 */
function bab_getDelegationsFromResource($res, $dgall = true, $dg0 = true) {

	global $babDB;

	$allobjects = array();
	foreach(bab_getDelegationsObjects() as $arr) {
		$allobjects[$arr[0]] = $arr;
	}

	$return = array();

	if ($dgall) {
		$return['DGAll'] = array(
			'id' 			=> false,
			'name' 			=> bab_translate('All site'),
			'description' 	=> bab_translate('All site'),
			'color' 		=> 'FFFFFF',
			'homePageUrl' 	=> '?',
			'objects' 		=> $allobjects
		);
	}

	if ($dg0) {
		$return['DG0'] = array(
			'id' 			=> 0,
			'name' 			=> bab_translate('Common content'),
			'description' 	=> bab_translate('Common content created in the main delegation'),
			'color' 		=> 'FFFFFF',
			'homePageUrl' 	=> '?tg=oml&file=DG0.html',
			'objects' 		=> $allobjects
		);
	}

	while ($arr = $babDB->db_fetch_assoc($res)) {

		$objects = array();

		foreach($allobjects as $key => $value) {
			if (isset($arr[$key]) && 'Y' === $arr[$key]) {
				$objects[$key] = $value;
			}
		}

		$return['DG'.$arr['id']] = array(
			'id' 			=> (int) $arr['id'],
			'name' 			=> $arr['name'],
			'description' 	=> $arr['description'],
			'color' 		=> $arr['color'],
			'homePageUrl' 	=> '?tg=oml&file=DG'.$arr['id'].'.html',
			'objects' 		=> $objects
		);
	}

	return $return;
}








/**
 * Get the delegation where the user is a member of the delegation group
 * @param	int	$id_user
 * @since	6.7.0
 *
 * @return 	array
 */
function bab_getUserVisiblesDelegations($id_user = NULL) {

	global $babDB;

	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}


	$res = $babDB->db_query('
		SELECT
			d.*

		FROM
			'.BAB_USERS_GROUPS_TBL.' ug,
			'.BAB_DG_GROUPS_TBL.' d
		WHERE
			(
				d.id_group = ug.id_group
				OR d.id_group='.$babDB->quote(BAB_REGISTERED_GROUP).'
				OR d.id_group='.$babDB->quote(BAB_ALLUSERS_GROUP).'
			)
			AND ug.id_object = '.$babDB->quote($id_user).'

		ORDER BY name
	');


	return bab_getDelegationsFromResource($res);
}


/**
 * delegations in sitemap
 *
 * @since 7.8.0 this function replace bab_getUserVisiblesDelegations since 7.8.0
 * @see bab_getUserVisiblesDelegations()
 *
 * @return array
 */
function bab_getUserSitemapDelegations($id_user = NULL)
{

	$return = array();

	$return['DGAll'] = array(
			'id' 			=> false,
			'name' 			=> bab_translate('All site'),
			'description' 	=> bab_translate('All site'),
			'color' 		=> 'FFFFFF',
			'homePageUrl' 	=> '?'
		);

	return $return;
}


/**
 * Test if a user is member of a delegation
 * if the id_user not given, the current user is used
 *
 * @since 7.4.0	The user must be attached to the group
 * @since 7.7.4 The user must be attached to the group or one of the sub-group (BUGM #1867)
 *
 * @param int $id_delegation
 * @param int $id_user
 *
 * @return bool
 */
function bab_isUserInDelegation($id_delegation, $id_user = null)
{
	require_once dirname(__FILE__).'/groupsincl.php';
	global $babDB;

	if (0 === $id_delegation || '0' === $id_delegation) {
		return true;
	}


	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}

	$deleg = bab_getDelegationById($id_delegation);

	return bab_Groups::isMemberOfTree($deleg[0]['id_group'], $id_user);
}






/**
 * Test if a user is member of a group not in the delegation
 * if the id_user not given, the current user is used
 *
 * @since 7.5.91
 *
 * @param int $id_delegation
 * @param int $id_user
 *
 * @return bool
 */
function bab_isUserOutOfDelegation($id_delegation, $id_user = null)
{
	global $babDB;

	if (0 === $id_delegation || '0' === $id_delegation) {
		return false;
	}


	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}


	$res = $babDB->db_query('
		SELECT
			g.id
		FROM
			bab_groups g,
			bab_users_groups ug,
			bab_dg_groups d,
			bab_groups dg
		WHERE
			dg.id = d.id_group
			AND ug.id_object = '.$babDB->quote($id_user).'
			AND d.id = '.$babDB->quote($id_delegation).'
			AND (g.lf < dg.lf OR g.lr > dg.lr )
			AND g.id=ug.id_group
	');

	return ($babDB->db_num_rows($res) !== 0);
}





/**
 * Get all delegations
 *
 * @param bool $dgall
 * @param bool $dg0
 *
 * @since	8.1.90
 *
 * @return 	array
 */
function bab_getDelegations($dgall = false, $dg0 = false) {

	global $babDB;

	$res = $babDB->db_query('
			SELECT
			d.*

			FROM
			'.BAB_DG_GROUPS_TBL.' d
				ORDER BY d.name
			');

	return bab_getDelegationsFromResource($res, $dgall, $dg0);
}


/**
 * Get the delegation where the user is administrator
 *
 * if the user is administrator of one delegation he will be admin of his delegation AND DGAll
 * the superadministrator is admin of DG0
 *
 * @param	int	$id_user
 * @since	6.7.0
 *
 * @return 	array
 */
function bab_getUserAdministratorDelegations($id_user = NULL) {

	global $babDB;

	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}


	$res = $babDB->db_query('
		SELECT
			d.*

		FROM
			'.BAB_DG_ADMIN_TBL.' a,
			'.BAB_DG_GROUPS_TBL.' d
		WHERE
			d.id = a.id_dg
			AND a.id_user = '.$babDB->quote($id_user).'

		ORDER BY d.name
	');

	$dg0 = bab_isMemberOfGroup(BAB_ADMINISTRATOR_GROUP, $id_user);
	$dgall = $babDB->db_num_rows($res) > 0 || $dg0;

	return bab_getDelegationsFromResource($res, $dgall, $dg0);
}






/**
 * Get administrators of a specified delegation
 *
 * @param	int	$deleg_id
 * @since	7.8.93		Fixed only in 8.1.90
 *
 * @return 	array
 */
function bab_getAdministratorsDelegation($deleg_id) {

	global $babDB;


	$res = $babDB->db_query('
		SELECT
			id_user

		FROM
			'.BAB_DG_ADMIN_TBL.'
		WHERE
			id_dg = '.$babDB->quote($deleg_id).'
	');

	$users = array();
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$id_user = (int) $arr['id_user'];
		$user = bab_getUserInfos($arr['id_user']);
		if($user){
			$users[] = array(
				'id' => $id_user,
				'name' => bab_composeUserName($user['givenname'],$user['sn']),
				'email' => $user['email']
			);
		}
	}

	return $users;
}








/**
* Return a delegation array
*
* @param mixed $name Array of name or name of the delegation to return
* @since 6.7.0
* @author Zebina Samuel
*
* @return array The matching delegation
*/
function bab_getDelegationByName($name)
{
	global $babDB;
	$sQuery =
		'SELECT
			*
		FROM ' .
			BAB_DG_GROUPS_TBL . '
		WHERE
			name IN(' . $babDB->quote($name) . ')';

	$aDG = array();
	$oResult = $babDB->db_query($sQuery);
	if(false != $oResult && $babDB->db_num_rows($oResult) > 0)
	{
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$aDG[] = $aDatas;
		}
	}
	return $aDG;
}


/**
* Return a delegation array
*
* @param mixed $id Array of id or id of the delegation to return, or nothing if you want all delegations
* @since 6.7.0
* @author Zebina Samuel
*
* @return array The matching delegation
*/
function bab_getDelegationById($id=null)
{
	global $babDB;
	$sQuery =
		'SELECT
			*
		FROM ' .
			BAB_DG_GROUPS_TBL;
	if($id !== null){
		$sQuery.= ' WHERE
			id IN(' . $babDB->quote($id) . ')';
	}

	$aDG = array();
	$oResult = $babDB->db_query($sQuery);
	if(false != $oResult && $babDB->db_num_rows($oResult) > 0)
	{
		while(false !== ($aDatas = $babDB->db_fetch_assoc($oResult)))
		{
			$aDG[] = $aDatas;
		}
	}
	return $aDG;
}


/**
 * Groups displayed in an ACL form
 *
 */
class bab_AclGroups
{

	private $id_delegation = 0;


	private $delegation_acl_definition = null;

	/**
	 *
	 * @param int $id_delegation
	 */
	public function __construct($id_delegation)
	{
		$this->id_delegation = $id_delegation;
	}


	/**
	 * number of ancestors in ACL definition of delegation
	 * @param int $id_parent
	 * @return number
	 */
	private function ancestorsInAcl($id_parent)
	{
		global $babDB;

		$ancestors = bab_Groups::getAncestors($id_parent);
		$ancestors = array_keys($ancestors);
		$ancestors[] = $id_parent;

		foreach($ancestors as &$a)
		{
			$a += BAB_ACL_GROUP_TREE;
		}

		// check if one of the ancestors is in acl definition

		$req = "SELECT t.id
			FROM
				".BAB_DG_ACL_GROUPS_TBL." t
			WHERE
				t.id_group IN(".$babDB->quote($ancestors).", ".$babDB->quote(BAB_ALLUSERS_GROUP).", ".$babDB->quote(BAB_REGISTERED_GROUP).")
				AND t.id_object='".$babDB->db_escape_string($this->id_delegation)."'
		";
		$res = $babDB->db_query($req);
		return $babDB->db_num_rows($res);
	}


	/**
	 * number of groups in ACL definition of delegation for one group (the group or one of the childnodes)
	 * @param int $id_group
	 * @return int
	 */
	private function groupsInAcl($id_group)
	{
		$group = bab_Groups::get($id_group);

		global $babDB;

		$req = "SELECT g.id
		FROM
			".BAB_DG_ACL_GROUPS_TBL." t,
			".BAB_GROUPS_TBL." g
		WHERE
			((t.id_group <".BAB_ACL_GROUP_TREE." AND g.id=t.id_group) OR (t.id_group >".BAB_ACL_GROUP_TREE." AND (t.id_group - g.id) = ".BAB_ACL_GROUP_TREE."))
			AND g.lf >=".$babDB->quote($group['lf'])."
			AND g.lr <= ".$babDB->quote($group['lr'])."
			AND t.id_object='".$babDB->db_escape_string($this->id_delegation)."'
		";
		$res = $babDB->db_query($req);
		return $babDB->db_num_rows($res);

	}


	/**
	 * get default group list for one level of group
	 * using the group API
	 * @return array
	 */
	private function getList($id_parent, Array $allowedChildren = null, Array $allowed = null)
	{
		if ($this->id_delegation && !isset($this->delegation_acl_definition))
		{
			trigger_error('missing information');
		}


		$groups = bab_getGroups($id_parent, false);

		// get ancestors

		if ($this->id_delegation && true === $this->delegation_acl_definition)
		{
			$ancestors = $this->ancestorsInAcl($id_parent);
		}


		$list = array();
		foreach ($groups['id'] as $key => $id) {


			// if delegation, if ACL defined on delegation, if no childnode in ACL, if no "checked with childnodes" in ancestors : ignore

			if ($this->id_delegation && true === $this->delegation_acl_definition && 0 === $ancestors && 0 === $this->groupsInAcl($id))
			{
				continue;
			}


			$list[$id] = array(
					'name'				=> $groups['name'][$key],
					'position'			=> $groups['position'][$key],
					'allowed'			=> (isset($allowed[$id]) || null === $allowed),					// group allowed to be checked on ACL form (right checkbox)
					'allowedChildren'	=> (isset($allowedChildren[$id]) || null === $allowedChildren)	// group and children allowed to be checked on ACL form (left checkbox)
			);

		}

		return $list;
	}


	/**
	 * get one group in a level of group
	 * @param int $id_group
	 * return array
	 */
	private function getGroup($id_group, $allowedChildren = true, $allowed = true)
	{
		$group = bab_Groups::get($id_group);

		return array(
				$id_group => array(
						'name'				=> $group['name'],
						'position'			=> array('lf' => $group['lf'], 'lr' => $group['lr']),
						'allowed'			=> $allowed,					// group allowed to be checked on ACL form (right checkbox)
						'allowedChildren'	=> $allowedChildren				// group and children allowed to be checked on ACL form (left checkbox)
				)
		);
	}


	/**
	 * Get allowed groups for ACL of a delegation for one level of groups
	 * if a group template is set in table BAB_DG_ACL_GROUPS_TBL allow only the treeview defined in this template
	 * otherwise, use the delegation group as root of allowed groups
	 *
	 * @param int	$id_delegation
	 * @param int 	$id_parent
	 *
	 *
	 * @return array
	 */
	public function getLevel($id_parent)
	{

		$id_delegation = $this->id_delegation;


		if (0 == $id_delegation)
		{
			return $this->getList($id_parent);
		}

		global $babDB;

		// get LF and LR of parent group

		$parent = bab_Groups::get($id_parent);
		$allowed = array();
		$allowedChildren = array();


		$res = $babDB->db_query("SELECT t.id_group, g.nb_groups FROM ".$babDB->backTick(BAB_DG_ACL_GROUPS_TBL)." t
				left join ".BAB_GROUPS_TBL." g on g.id=t.id_group
				WHERE t.id_object='".$babDB->db_escape_string($id_delegation)."'
				");

		if (0 === $babDB->db_num_rows($res))
		{
			// no ACL definition, get the delegation group as allowed root
			$this->delegation_acl_definition = false;

			list($delegation) = bab_getDelegationById($id_delegation);
			$delegation_group = bab_Groups::get($delegation['id_group']);


			// allow the predefined list if under delegation group

			if ($id_parent == $delegation['id_group'] || ($parent['lf'] > $delegation_group['lf'] && $parent['lr'] < $delegation_group['lr']))
			{
				return $this->getList($id_parent);
			}

			$allowed[$delegation['id_group']] = 1;
			$allowedChildren[$delegation['id_group']] = 1;

			if ($id_parent == BAB_REGISTERED_GROUP)
			{
				// simplify the ACL treeview ...
				return $this->getGroup($delegation['id_group']);
			}

		} else {

			$this->delegation_acl_definition = true;

			// there is an ACL definition on the delegation
			while ($arr = $babDB->db_fetch_assoc($res)) {

				if ($arr['id_group'] >= BAB_ACL_GROUP_TREE )
				{
					$arr['id_group'] -= BAB_ACL_GROUP_TREE;

					// if one of the left checked group is upper or equal to id_parent, return the predefined list, all fields allowed

					$checked_group = bab_Groups::get($arr['id_group']);

					if ($id_parent == $arr['id_group'] || ($parent['lf'] > $checked_group['lf'] && $parent['lr'] < $checked_group['lr']))
					{
						return $this->getList($id_parent);
					}

					$allowed[$arr['id_group']] = 1;
					$allowedChildren[$arr['id_group']] = 1;
				}
				else if ($arr['id_group'] == BAB_ALLUSERS_GROUP || $arr['id_group'] == BAB_REGISTERED_GROUP)
				{
					return $this->getList($id_parent);
				}
				else
				{
					if( $arr['nb_groups'] !== null )
					{
						// set of groups

						$rs=$babDB->db_query("select id_group from ".BAB_GROUPS_SET_ASSOC_TBL." where id_set=".$babDB->quote($arr['id_group']));
						while( $rr = $babDB->db_fetch_array($rs))
						{
							$allowed[$rr['id_group']] = 1;
						}
					}
					else
					{
						$allowed[$arr['id_group']] = 1;
					}
				}
			}
		}


		return $this->getList($id_parent, $allowedChildren, $allowed);

	}

}

/**
 * Remove a delegation group from database
 *
 * @param	int		$id_delegation
 * @param	bool	$deleteObjects		true : objects in delegation are deleted, false : objects are moved into main site (DG0)
 *
 * @since 7.7.100
 *
 * @return bool
 */
function bab_deleteDelegation($id_delegation, $deleteObjects)
{
	global $babDB;
	require_once dirname(__FILE__).'/eventdelegation.php';

	if (empty($id_delegation))
	{
		throw new ErrorException('Invalid delegation id');
	}

	$idsafe = $babDB->db_escape_string($id_delegation);

	if($deleteObjects)
	{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
		$res = $babDB->db_query("select id from ".BAB_SECTIONS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteSection($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteTopicCategory($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteApprobationSchema($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteForum($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_FAQCAT_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteFaq($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_FM_FOLDERS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteFolder($arr['id']);
			//deletion of DGx folder
			require_once $GLOBALS['babInstallPath']."utilit/path.class.php";
			require_once $GLOBALS['babInstallPath']."utilit/iterator/iterator.php";
			require_once $GLOBALS['babInstallPath']."utilit/fmset.class.php";
			$path = new bab_path(BAB_FmFolderHelper::getUploadPath(), 'fileManager', 'collectives', 'DG'.$idsafe);
			rmdir($path->tostring());
		}

		$res = $babDB->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteLdapDirectory($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteDbDirectory($arr['id']);
		}

		$res = $babDB->db_query("select id from ".BAB_ORG_CHARTS_TBL." where id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteOrgChart($arr['id']);
		}

		$res = $babDB->db_query("select crt.id, ct.id as idcal from ".BAB_CAL_PUBLIC_TBL." crt left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id and ct.type='".BAB_CAL_PUB_TYPE."' where crt.id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteCalendar($arr['idcal']);
			$babDB->db_query("delete from ".BAB_CAL_PUBLIC_TBL." where id='".$arr['id']."'");
		}

		$res = $babDB->db_query("select crt.id, ct.id as idcal from ".BAB_CAL_RESOURCES_TBL." crt left join ".BAB_CALENDAR_TBL." ct on ct.owner=crt.id and ct.type='".BAB_CAL_RES_TYPE."' where crt.id_dgowner='".$idsafe."'");
		while($arr = $babDB->db_fetch_array($res))
		{
			bab_deleteCalendar($arr['idcal']);
			$babDB->db_query("delete from ".BAB_CAL_RESOURCES_TBL." where id='".$arr['id']."'");
		}
	}
	else
	{
		require_once $GLOBALS['babInstallPath']."utilit/path.class.php";
		require_once $GLOBALS['babInstallPath']."utilit/iterator/iterator.php";
		require_once $GLOBALS['babInstallPath']."utilit/fmset.class.php";
		$pathTo = new bab_path(BAB_FmFolderHelper::getUploadPath(), 'fileManager', 'collectives', 'DG0', 'DG'.$idsafe);
		$pathsFrom = new bab_path(BAB_FmFolderHelper::getUploadPath(), 'fileManager', 'collectives', 'DG'.$idsafe);

		if($pathsFrom->fileExists()){
			$babDB->db_query("
				INSERT INTO bab_fm_folders
					(folder, sRelativePath, manager, idsa, filenotify, active, version, id_dgowner, bhide, auto_approbation, baddtags, bcap_downloads, max_downloads, bdownload_history, manual_order)
				VALUES ('DG".$idsafe."', '', '0', '0', 'N', 'Y', 'N', '0', 'N', 'N', 'Y', 'N', '0', 'N', '0')

			");
			if(!$pathTo->fileExists()){
				rename($pathsFrom->tostring(), $pathTo->tostring());
			}else{
				throw new ErrorException('Delete not done, a folder with this name already exist');
			}
		}

		$babDB->db_query("update ".BAB_SECTIONS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_TOPICS_CATEGORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FLOW_APPROVERS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FORUMS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FAQCAT_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set id_dgowner='0', sRelativePath=CONCAT('".'DG'.$idsafe."/',sRelativePath) where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FILES_TBL." set iIdDgOwner='0', path=CONCAT('".'DG'.$idsafe."/',path) where iIdDgOwner='".$idsafe."'");
		$babDB->db_query("update ".BAB_LDAP_DIRECTORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_DB_DIRECTORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_ORG_CHARTS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_CAL_RESOURCES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_CAL_PUBLIC_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
	}



	$babDB->db_query("delete from ".BAB_DG_ADMIN_TBL." where id_dg='".$idsafe."'");
	$babDB->db_query("delete from ".BAB_DG_GROUPS_TBL." where id='".$idsafe."'");
	$babDB->db_query("delete from ".BAB_DG_ACL_GROUPS_TBL." where id_object='".$idsafe."'");


	$event = new bab_eventDelegationDeleted();
	$event->id_delegation = $id_delegation;
	bab_fireEvent($event);

	return $event->returnStatus;
}





/**
 * list delegation groups where i am administrator
 * this function remplace the $babBody->dgAdmGroups variable
 * @return array
 */
function bab_getDgAdmGroups()
{
	static $groups = null;

	if (null === $groups)
	{
		global $babDB;
		$groups = array();
		$res = $babDB->db_query("SELECT dg.id FROM ".BAB_DG_ADMIN_TBL." da,".BAB_DG_GROUPS_TBL." dg where da.id_user='".$babDB->db_escape_string(bab_getUserId())."' AND da.id_dg=dg.id AND dg.id_group >= '0'");
		while( $arr = $babDB->db_fetch_array($res) ) {
			$groups[] = $arr['id'];
		}
	}
	return $groups;
}




/**
 * Delegation chosen by the delegated administrator
 *
 */
class bab_currentDelegation
{
	/**
	 * @var int
	 */
	private $id = null;

	/**
	 *
	 * @var array
	 */
	private $row = null;

	/**
	 * Set necessary variable for delegations
	 * @param int $id_dg		user current delegation
	 * @return unknown_type
	 */
	public function set($id_dg)
	{
		$this->id = $id_dg;
		$this->row = null;
	}



	/**
	 * Get current user delegation in bab_user_log
	 * @return int
	 */
	private function getFromUserLog()
	{

		$log = bab_UsersLog::getCurrentRow();
		if (false === $log)
		{
			return null;
		}

		$id_dg = (int) $log['id_dg'];

		if ($id_dg <= 0)
		{
			return null;
		}

		return $id_dg;
	}


	/**
	 * Get default delegation if the user is not super-administrator
	 * @return int
	 */
	private function getDefaultDelegation()
	{


		if(!bab_isUserAdministrator())
		{
			// not set by bab_users_log, use the first available delegation group
			$dgAdmGroups = bab_getDgAdmGroups();

			if (count($dgAdmGroups) > 0)
			{
				return (int) $dgAdmGroups[0];
			}
		}

		return 0;
	}


	/**
	 * Get the ID of the current admin delegated group
	 * @see bab_getCurrentAdmGroup()
	 * @return int
	 */
	public function getCurrentAdmGroup()
	{
		if (null === $this->id)
		{
			$this->id = 0;

			// check first in bab_users_log
			if ($id_dg = $this->getFromUserLog())
			{
				$this->id = $id_dg;

			} else if ($id_dg = $this->getDefaultDelegation()) {
				$this->id = $id_dg;
			}
		}

		return $this->id;
	}

	/**
	 *
	 * @see bab_getCurrentDGGroup()
	 * @return array
	 */
	public function getCurrentDGGroup()
	{
		if (!isset($this->row))
		{
			$this->row = array('id' => 0);

			if ($id = $this->getCurrentAdmGroup())
			{
				global $babDB;

				$this->row = $babDB->db_fetch_assoc(
					$babDB->db_query("
					SELECT
						dg.*, g.lf, g.lr
					FROM
						".BAB_DG_GROUPS_TBL." dg,
						".BAB_GROUPS_TBL." g
					WHERE
						g.id=dg.id_group AND dg.id='".$babDB->db_escape_string($id)."'")
				);
			}
		}

		return $this->row;
	}
}






