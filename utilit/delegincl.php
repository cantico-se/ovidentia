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
	global $babDB;
	
	if (0 === $id_delegation || '0' === $id_delegation) {
		return true;
	}
	
	
	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}
	
	$deleg = bab_getDelegationById($id_delegation);
	
	return bab_isMemberOfTree($deleg[0]['id_group'], $id_user);
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
		$pathTo = new bab_path(BAB_FmFolderHelper::getUploadPath(), 'fileManager', 'collectives', 'DG0');
		$pathsFrom = new bab_path(BAB_FmFolderHelper::getUploadPath(), 'fileManager', 'collectives', 'DG'.$idsafe);
		
		$moveArray = array();
		/* @var $pathFrom bab_path */
		foreach($pathsFrom as $pathFrom){
			if($pathFrom->isDir()){
				$dirname = $pathFrom->getBasename();
				$pathsToExist = new bab_path($pathTo->tostring(),$dirname);
				if($pathsToExist->fileExists()){
					throw new ErrorException('Delete not done, a folder with this name already exist');
				}
				$moveArray[] = array('from' => $pathFrom->tostring(), 'to' => $pathsToExist->tostring());
			}
		}
		
		foreach($moveArray as $move){
			rename($move['from'], $move['to']);
		}
		
		$babDB->db_query("update ".BAB_SECTIONS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_TOPICS_CATEGORIES_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FLOW_APPROVERS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FORUMS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FAQCAT_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FM_FOLDERS_TBL." set id_dgowner='0' where id_dgowner='".$idsafe."'");
		$babDB->db_query("update ".BAB_FILES_TBL." set iIdDgOwner='0' where iIdDgOwner='".$idsafe."'");
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
