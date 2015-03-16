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

include_once $GLOBALS['babInstallPath'].'utilit/afincl.php';




/**
 * Creates an instance of approbation schema with id $idsch and return the id of the instance.
 *
 * @since 8.0.100	the $owner parameter has been added in 8.0.100
 *
 * @param int		$idsch		Id of the approbation schema.
 * @param string	$extra		Instance identification string. This information is not used by Ovidentia, can be used debugging purpose.
 * @param int		[$user]		User id of owner for auto-approbation, 0 = no auto-approbation.
 * @param int		[$owner]	Owner of instance, default is the current logged in user. This user will be used for the supperior function in organizational charts
 *
 * @return int		The id of the new instance.
 */
function bab_WFMakeInstance($idsch, $extra, $user = 0, $owner = null)
{
    return makeFlowInstance($idsch, $extra, $user, $owner);
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
 * Updates an instance with user's response.
 *
 * @see bab_WFGetWaitingApproversInstance
 *
 * @param int		$idschi		Id of the instance.
 * @param int		$iduser		User id.
 * @param bool		$bool		True if the user accept the approbation and false if the user decline the approbation.
 *
 * @return number	The new result of the schema approbation:
 *		 0 	if the approbation is declined and then the subject of the approbation must be revoked.
 * 		 1 	if the approbation is accepted and hence the subject of the approbation must be accepted.
 * 		-1	if the approbation can't be evaluated at this moment and you must bab_WFGetWaitingApproversInstance()
 * 			to see which users needs to approve the instance.
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
 * 		0 => array('name' = first schema name, 'description' = first schema description, 'id' = first schema id, 'type' = first schema type, 'id_oc' = first schema associated OC),
 *  	...
 *  	n => array('name' = nth schema name, 'description' = nth schema description, 'id' = nth schema id, 'type' = nth schema type, 'id_oc' = nth schema associated OC)
 * )
 *
 *
 * type = array('0' => 'name', '1' => 'fonctionnal', '2' => 'group')
 * id_oc is only used when the type is fonctionnal.
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
        $result[$arr['id']] = array(
            'name' => $arr['name'],
            'description' => $arr['description'],
            'id' => $arr['id'],
            'type' => $arr['satype'],
            'id_oc' => $arr['id_oc'])
        ;
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
        formula,		#The rule
        forder,			#Should step respect the order
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





/**
 * Set approbationscheme information
 *
 * @since 8.2.92
 *
 * @param int 		$idsch			id (if it's null then it will create it)
 * @param string	$name			name
 * @param string	$description	description
 * @param string	$formula		the approbation rule (who with which order)
 * @param string	$forder			if it should respect step order
 * @param string	$satype			type (0=name,1=group,2=function)
 * @param string	$id_orgchart	only
 * @return array
 */
function bab_WFSetApprobationInfos($idsch = null, $name, $description, $formula, $forder, $satype, $id_orgchart = 0)
{
    global $babDB;
    $result = array();

    if($idsch !== null){
        $babDB->db_query("
            UPDATE bab_flow_approvers
            SET
                name = ".$babDB->quote($name).",
                description = ".$babDB->quote($description).",
                formula = ".$babDB->quote($formula).",
                forder = ".$babDB->quote($forder).",
                satype = ".$babDB->quote($satype).",
                id_oc = ".$babDB->quote($id_orgchart).",
                id_dgowner = ".$babDB->quote(bab_getCurrentAdmGroup())."
            WHERE
                id=".$babDB->quote($idsch)
        );
    }else{
        $req = "select id from ".BAB_FLOW_APPROVERS_TBL." where name='".$babDB->db_escape_string($name)."'";
        $res = $babDB->db_query($req);
        if( $res && $babDB->db_num_rows($res) > 0) {
            $babBody->msgerror = bab_translate("This flow approvers already exists");
            return false;
        }

        $babDB->db_query("
            INSERT INTO bab_flow_approvers ( name, description, formula, forder, satype, id_oc, id_dgowner )
            Values (
                ".$babDB->quote($name).",
                ".$babDB->quote($description).",
                ".$babDB->quote($formula).",
                ".$babDB->quote($forder).",
                ".$babDB->quote($satype).",
                ".$babDB->quote($id_orgchart).",
                ".$babDB->quote(bab_getCurrentAdmGroup())."
            )"
        );
    }

    return true;
}





/**
 * Delete approbationscheme
 *
 * @since 8.2.92
 *
 * @param int $idsch		approbation scheme
 * @return array
 */
function bab_WFDeleteSchema($idsch)
{
    global $babDB;

    if(bab_WFIsApprobationInUse($idsch) !== false){
        return false;
    }

    $res = $babDB->db_query("DELETE FROM ".BAB_FLOW_APPROVERS_TBL." WHERE id=".$babDB->quote($idsch));

    return true;
}



/**
 * Check if a approbation scheme is in used
 * Used to remove it.
 *
 * @since 8.2.93
 *
 * @param int $idsch		approbation scheme
 * @return bool | null when schema not found
 */

function bab_WFIsApprobationInUse($idsch)
{
    global $babDB;
    $res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($idsch)."'");
    if( $res && $babDB->db_num_rows($res) > 0) {
        $arr = $babDB->db_fetch_array($res);
        if( $arr['refcount'] == 0 ) {
            return false;
        } else {
            return true;
        }
    }

    return null;
}
