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
		bab_functionality::includefile('Icons');
		
		$objects = array(
			array("users"		, bab_translate("Create a new user")	, null				, null											, null, null),
			array("groups"		, bab_translate("Manage groups")		, null				, null											, null, null),
			array('battach'		, bab_translate("Assign/unassign a user to group and group children"), null, null							, null, null),
			array("sections"	, bab_translate("Sections")				, 'AdminSections'	, $GLOBALS['babUrlScript'].'?tg=sections'		, null, Func_Icons::APPS_SECTIONS),
			array("articles"	, bab_translate("Articles")				, 'AdminArticles'	, $GLOBALS['babUrlScript'].'?tg=topcats'		, bab_translate("Categories and topics management"), Func_Icons::APPS_ARTICLES),
			array("faqs"		, bab_translate("Faq")					, 'AdminFaqs'		, $GLOBALS['babUrlScript'].'?tg=admfaqs'		, bab_translate("Frequently Asked Questions"), Func_Icons::APPS_FAQS),
			array("forums"		, bab_translate("Forums")				, 'AdminForums'		, $GLOBALS['babUrlScript'].'?tg=forums'			, null, Func_Icons::APPS_FORUMS),
			array("calendars"	, bab_translate("Calendar")				, 'AdminCalendars'	, $GLOBALS['babUrlScript'].'?tg=admcals'		, null, Func_Icons::APPS_CALENDAR),
			array("mails"		, bab_translate("Mail")					, 'AdminMail'		, $GLOBALS['babUrlScript'].'?tg=maildoms&userid=0&bgrp=y' , null, Func_Icons::APPS_MAIL),
			array("directories"	, bab_translate("Directories")			, 'AdminDir'		, $GLOBALS['babUrlScript'].'?tg=admdir'			, null, Func_Icons::APPS_DIRECTORIES),
			array("approbations", bab_translate("Approbation schemas")	, 'AdminApprob'		, $GLOBALS['babUrlScript'].'?tg=apprflow'		, null, Func_Icons::APPS_APPROBATIONS),
			array("filemanager"	, bab_translate("File manager")			, 'AdminFm'			, $GLOBALS['babUrlScript'].'?tg=admfms'			, null, Func_Icons::APPS_FILE_MANAGER),
			array("orgchart"	, bab_translate("Charts")				, 'AdminCharts'		, $GLOBALS['babUrlScript'].'?tg=admocs'			, null, Func_Icons::APPS_ORGCHARTS),
			array("taskmanager"	, bab_translate("Task Manager")			, 'AdminTm'			, $GLOBALS['babUrlScript'].'?tg=admTskMgr'		, null, Func_Icons::APPS_TASK_MANAGER)
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
* @param mixed $id Array of id or id of the delegation to return
* @since 6.7.0
* @author Zebina Samuel
* 
* @return array The matching delegation
*/
function bab_getDelegationById($id)
{
	global $babDB;
	$sQuery = 
		'SELECT  
			* 
		FROM ' . 
			BAB_DG_GROUPS_TBL . ' 
		WHERE  
			id IN(' . $babDB->quote($id) . ')';

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

	/**
	 *
	 * @param int $id_delegation
	 */
	public function __construct($id_delegation)
	{
		$this->id_delegation = $id_delegation;
	}


	/**
	 * get default group list for one level of group
	 * using the group API
	 * @return array
	 */
	private function getList($id_parent, Array $allowedChildren = null, Array $allowed = null)
	{
		$groups = bab_getGroups($id_parent, false);

		$list = array();
		foreach ($groups['id'] as $key => $id) {

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


		$res = $babDB->db_query("SELECT t.id_group, g.lf, g.lr, g.nb_groups FROM ".$babDB->backTick(BAB_DG_ACL_GROUPS_TBL)." t
				left join ".BAB_GROUPS_TBL." g on g.id=t.id_group
				WHERE t.id_object='".$babDB->db_escape_string($id_delegation)."'
				");

		if (0 === $babDB->db_num_rows($res))
		{
			// no ACL definition, get the delegation group as allowed root

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
		}




		while ($arr = $babDB->db_fetch_assoc($res)) {

			if ($arr['id_group'] >= BAB_ACL_GROUP_TREE )
			{
				$arr['id_group'] -= BAB_ACL_GROUP_TREE;

				// if one of the left checked group is upper or equal to id_parent, return the predefined list

				if ($id_parent == $arr['id_group'] || ($parent['lf'] > $arr['lf'] && $parent['lr'] < $arr['lr']))
				{
					return $this->getList($id_parent);
				}

				$allowed[$arr['id_group']] = 1;
				$allowedChildren[$arr['id_group']] = 1;
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
	
	
	return true;
}
