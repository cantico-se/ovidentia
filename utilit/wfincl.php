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
* @internal SEC1 NA 05/12/2006 FULL
*/
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';




/**
 * Creates an instance of approbation schema with id $idsch and return the id of the instance.
 * 
 * @param int		$idsch	Id of the approbation schema.
 * @param string	$extra	Instance identification string. This information id not used by Ovidentia, can be used debugging purpose.
 * @param int		$user	User id of owner for auto-approbation, 0 = no auto-approbation.
 * 
 * @return int		The id of the new instance.
 */
function bab_WFMakeInstance($idsch, $extra, $user = 0)
{
	return makeFlowInstance($idsch, $extra, $user);
}





/**
 * Deletes the specified instance.
 * 
 * @param int		$idschi		Id of the instance.
 */
function bab_WFDeleteInstance($idschi)
{
	return deleteFlowInstance($idschi);
}





/**
 * Updates an instance with user’s response.
 * 
 * @param int		$idschi		Id of the instance.
 * @param int		$iduser		User id.
 * @param bool		$bool		True if the user accept the approbation and false if the user decline the approbation.
 * 
 * @return number	The new result of the schema approbation:
 *						- 0 if the approbation is declined and then the subject of the approbation must be revoked.
 * 						- 1 if the approbation is accepted and hence the subject of the approbation must be accepeted.
 */
function bab_WFUpdateInstance($idschi, $iduser, $bool)
{
	return updateFlowInstance($idschi, $iduser, $bool);
}





/**
 * This function return true if there is a waiting approbation instance for the user with id $iduser.
 * 
 * @param int	$idsa		Id of the approbation schema.
 * @param int 	$iduser		User id.
 * @param bool	$update		If true the session cache will not be used.
 * @return boolean
 */
function bab_WFCheckInstance($idsa, $iduser, $update = false)
{

	$arr = getWaitingApprobations($iduser, $update);
	for( $i=0; $i < count($arr['idsch']); $i++)
	{
		if( $arr['idsch'][$i] == $idsa )
		{
			return true;
		}
	}
	return false;
}





/**
 * Returns an array with users ids who wait for approbation schema instance with id $idschi.
 * 
 * @param int	$idschi		The approbation schema id.
 * @param bool	$notify		If true, the mark waiting users to be as notified.
 * @return array
 */
function bab_WFGetWaitingApproversInstance($idschi, $notify = false)
{
	$result = getWaitingApproversFlowInstance($idschi, $notify);
	return $result;
}





/**
 * Returns an array of all waiting instances.
 * 
 * @see bab_getWaitingIdSAInstance($iduser)
 * 
 * @param int	$iduser
 * @param bool	$update		If true the session cache will not be used.
 * @return array			An array of instances id.
 */
function bab_WFGetWaitingInstances($iduser, $update = false)
{
	$arr = getWaitingApprobations($iduser, $update);
	return $arr['idschi'];
}





/**
 * Returns the list of list of approbation schema.
 * The returned array has the following structure:
 * array(
 * 		0 => array('name' = first schema name, 'id' = first schema id),
 *  	...
 *  	n => array('name' = nth schema name, 'id' = nth schema id)
 * )
 *
 * @return array
 */
function bab_WFGetApprobationsList()
{
	global $babDB, $babBody;
	$result = array();
	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babDB->db_escape_string(bab_getCurrentAdmGroup())."' order by name asc");
	while( $arr = $babDB->db_fetch_assoc($res))
	{
		$result[] = array('name' => $arr['name'], 'id' => $arr['id']);
	}
	return $result;
}





/**
 * Get approbationscheme information
 * 
 * @since 8.0.94
 * 
 * @param int $idsch		approbation scheme
 * @return array
 */
function bab_WFGetApprobationInfos($idsch)
{
	global $babDB;
	$result = array();
	$res = $babDB->db_query("select 
		name, 
		description, 
		satype, 
		id_oc,			# if linked to organizational chart
		id_dgowner,		# delegation
		refcount  		# usage count
	from 
		".BAB_FLOW_APPROVERS_TBL." 
			
	where 
		id=".$babDB->quote($idsch)
	);
	
	
	while( $arr = $babDB->db_fetch_assoc($res))
	{
		switch($arr['satype'])
		{
			case 1:
				$arr['type'] = bab_translate('Staff schema');
				break;
			case 2:
				$arr['type'] = bab_translate('Group schema');
				break;
			default:
				$arr['type'] = bab_translate('Nominative schema');
		}
		
		return $arr;
	}
	
	return null;
}
