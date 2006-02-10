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
include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

function bab_WFMakeInstance($idsch, $extra, $user=0)
{
	return makeFlowInstance($idsch, $extra, $user);
}

function bab_WFDeleteInstance($idschi)
{
	return deleteFlowInstance($idschi);
}

function bab_WFUpdateInstance($idschi, $iduser, $bool)
{
	return updateFlowInstance($idschi, $iduser, $bool);
}

function bab_WFCheckInstance($idsa, $iduser, $update=false)
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


function bab_WFGetWaitingApproversInstance($idschi, $notify=false)
{
	return getWaitingApproversFlowInstance($idschi, $notify);
}

function bab_WFGetWaitingInstances($iduser, $update=false)
{
	$arr = getWaitingApprobations($iduser, $update);
	$result = array();
	for( $i=0; $i < count($arr['idsch']); $i++)
	{
		$result[] = $arr['idschi'][$i];
	}
	return $result;
}

function bab_WFGetApprobationsList()
{
	global $babDB, $babBody;
	$result = array();
	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$result[] = array('name' => $arr['name'], 'id' => $arr['id']);
	}
	return $result;
}

?>