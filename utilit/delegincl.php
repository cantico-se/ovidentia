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

$GLOBALS['babDG'] = array(	array("users", bab_translate("Users")),
				array("groups", bab_translate("Groups")),
				array("sections", bab_translate("Sections")),
				array("articles", bab_translate("Topics categories")),
				array("faqs", bab_translate("Faq")),
				array("forums", bab_translate("Forums")),
				array("calendars", bab_translate("Calendar")),
				array("mails", bab_translate("Mail")),
				array("directories", bab_translate("Directories")),
				array("approbations", bab_translate("Approbations")),
				array("filemanager", bab_translate("File manager")),
				array("orgchart", bab_translate("Charts")),
				array("taskmanager", bab_translate("Task Manager"))
				);


/**
 * Set current user delegation
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
	$aCurrUsrDg = bab_getUserFmVisibleDelegations();
	if (count($aCurrUsrDg) > 0) {
		$aItem = each($aCurrUsrDg);
		if (false !== $aItem) {
			return $aItem['key'];
		}
	}
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
 * Get the delegation where the user is a member of the delgation group
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
			d.id,
			d.name,
			d.description, 
			d.color  
		
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
	
	$return = array(
		'DGAll' => array(
			'id' => false,
			'name' => bab_translate('Home'),
			'description' => bab_translate('All site'),
			'color' => 'FFFFFF',
			'homePageUrl' => '?'
		)
	);
	
	
	if (0 < $babDB->db_num_rows($res)) {
		$return['DG0'] = array(
			'id' => 0,
			'name' => bab_translate('Common content'),
			'description' => bab_translate('Common content created in the main delegation'),
			'color' => 'FFFFFF',
			'homePageUrl' => '?tg=oml&file=DG0.html'
		);
	}
	
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$return['DG'.$arr['id']] = array(
			'id' => (int) $arr['id'],
			'name' => $arr['name'],
			'description' => $arr['description'],
			'color' => $arr['color'],
			'homePageUrl' => '?tg=oml&file=DG'.$arr['id'].'.html'
		);
	}
	
	
	return $return;
}


/**
* Return a delegation array
*
* @param mixed $name Array of name or name of the delegation to return
* @since 6.7.0
* @author Zébina Samuel
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
* @author Zébina Samuel
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






