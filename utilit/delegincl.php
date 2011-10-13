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

$GLOBALS['babDG'] = array(
				array("users", bab_translate("Create a new user")),
				array("groups", bab_translate("Manage groups")),
				array('battach', bab_translate("Assign/unassign a user to group and group children")),
				array("sections", bab_translate("Sections")),
				array("articles", bab_translate("Articles")),
				array("faqs", bab_translate("Faq")),
				array("forums", bab_translate("Forums")),
				array("calendars", bab_translate("Calendar")),
				array("mails", bab_translate("Mail")),
				array("directories", bab_translate("Directories")),
				array("approbations", bab_translate("Approbation schemas")),
				array("filemanager", bab_translate("File manager")),
				array("orgchart", bab_translate("Charts")),
				array("taskmanager", bab_translate("Task Manager"))
				);


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
	foreach($GLOBALS['babDG'] as $arr) {
		$allobjects[$arr[0]] = $arr[1];
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
 * Test if a user is member of a delegation
 * if the id_user not given, the current user is used
 * 
 * @since 7.4.0
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
			AND d.id = '.$babDB->quote($id_delegation).'
		
		ORDER BY name 
	');
	
	return ($babDB->db_num_rows($res) !== 0);
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
	
	$dgall = $babDB->db_num_rows($res) > 0;
	$dg0 = bab_isMemberOfGroup(BAB_ADMINISTRATOR_GROUP, $id_user);
	
	
	return bab_getDelegationsFromResource($res, $dgall, $dg0);
}








/**
* Return a delegation array
*
* @param mixed $name Array of name or name of the delegation to return
* @since 6.7.0
* @author Z�bina Samuel
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
* @author Z�bina Samuel
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






