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
function updateSchemaInstance($idschi)
{

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");

	$arr = $db->db_fetch_array($res);
	$tab = explode(",", $arr['formula']);
	for( $i= 0; $i < count($tab); $i++)
		{
		$rr = array();
		if( strchr($tab[$i], "&"))
			$op = "&";
		else
			$op = "|";

		$rr = explode($op, $tab[$i]);
		for($k=0; $k < count($rr); $k++)
			{
			if( count($tabusers) == 0 || (count($tabusers) > 0 && !in_array( $rr[$k], $tabusers )))
				$tabusers[] = $rr[$k];
			}
		}

	$tab = $tabusers;
	$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."'");
	while( $arr3 = $db->db_fetch_array($res))
		{
		if( !in_array($arr3['iduser'], $tab))
			{
			$db->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where id='".$arr3['id']."'");
			}
		else 
			{
			for($j = 0; $j < count($tab); $j++)
				{
					if ($tab[$j] == $arr3['iduser'])
					{
						array_splice($tab, $j, 1);
						break;
					}
				}
			}
		}

	for($j = 0; $j < count($tab); $j++)
		{
		$db->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser) VALUES ('".$idschi."', '".$tab[$j]."')");
		}

}

function makeFlowInstance($idsch, $extra)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsch."'");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( !empty($GLOBALS['BAB_SESS_USERID']))
			{
			$iduser = $GLOBALS['BAB_SESS_USERID'];
			}
		else
			{
			$iduser = 0;
			}
		$db->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra, iduser) VALUES ('".$idsch."', '".$extra."', '".$iduser."')");
		$id = $db->db_insert_id();
		$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".($arr['refcount'] + 1)."' where id='".$idsch."'");
		updateSchemaInstance($id);
		return $id;
		}
	return "";
}

function evalFlowInstance($idschi)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and result='0'");
	if( $res && $db->db_num_rows($res) > 0 )
		return 0;

	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$arr = explode(",", $arr['formula']);
		for( $i= 0; $i < count($arr); $i++)
			{
			if( strchr($arr[$i], "&"))
				$op = "&";
			else if( strchr($arr[$i], "|"))
				$op = "|";
			else
				$op = "";

			switch($op)
				{
				case "&":
					$rr = explode($op, $arr[$i]);
					for( $k = 0; $k < count($rr); $k++)
						{
						$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$rr[$k]."' and result=''");
						if( $res && $db->db_num_rows($res) > 0)
							{
							return -1;
							}
						}
					break;
				case "|":
					$rr = explode($op, $arr[$i]);
					for( $k = 0; $k < count($rr); $k++)
						{
						$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$rr[$k]."' and result=''");
						if( $res && $db->db_num_rows($res) > 0)
							{
							return -1;
							}
						}
					break;
				default:
					$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$arr[$i]."'");
					$tab = $db->db_fetch_array($res);
					if( $tab['result'] == '')
					{
						return -1;
					}
					break;
				}
			}
		return 1;
		}
	return -1;
}

function deleteFlowInstance($idschi)
{
	$db = $GLOBALS['babDB'];
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_FA_INSTANCES_TBL." where id='".$idschi."'"));
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$arr['idsch']."'"));
	if( $arr['refcount'] > 0)
		$db->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".($arr['refcount'] - 1 )."' where id='".$arr['id']."'");

	$db->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."'");
	$db->db_query("delete from ".BAB_FA_INSTANCES_TBL." where id='".$idschi."'");
}

function updateFlowInstance($idschi, $iduser, $bool)
{

	$db = $GLOBALS['babDB'];
	$scinfo = $db->db_fetch_array($db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." fat left join ".BAB_FA_INSTANCES_TBL." fait on fait.idsch=fat.id where fait.id='".$idschi."'"));

	if( $scinfo['satype'] == 1 )
	{
		$idroles = array();
		$roles = getApproversFlow($scinfo['formula']);
		if( count($roles) > 0 )
		{
			$rr = bab_getSuperior($scinfo['iduser']);
			if( count($rr['iduser']) > 0  && $rr['iduser'][0] == $iduser )
				{
				$idroles[] = 0;
				}

			for( $i = 0; $i < count($roles); $i++ )
			{
				if( $roles[$i] != 0 )
				{
					$idnroles[] = 0;
				}
			}

			if( count($idnroles) > 0 )
			{
				$arr = bab_getOrgChartRoleUsers($idnroles);
				for( $i = 0; $i < count($arr['iduser']); $i++ )
				{
					if( $arr['iduser'][$i] == $iduser )
					{
						$idroles[] = $arr['idrole'][$i];
					}
				}
			}

		}
		$iduser = $idroles;

	}

	$idsu = is_array($iduser)? $iduser: array($iduser);

	if( count($idsu) > 0 )
	{
		$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser IN (".implode(',', $idsu).")");
		while( $row = $db->db_fetch_array($res) )
		{
		if( $bool)
			$result = "1";
		else
			$result ="0";
		$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='".$result."' where id='".$row['id']."'");
		
		if( $result == 0 )
			{
			$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='x' where idschi='".$idschi."' and result=''");
			}
		else
			{
			$result = array();
			$arr = explode(",", $scinfo['formula']);
			for( $i= 0; $i < count($arr); $i++)
				{
				if( strchr($arr[$i], "&"))
					$op = "&";
				else if( strchr($arr[$i], "|"))
					$op = "|";
				else
					$op = "";

				if( $op != "")
					{
					$rr = explode($op, $arr[$i]);
					if( count($rr) > 1 && $op == "|" && in_array($row['iduser'], $rr))
						{
						for( $k = 0; $k < count($rr); $k++)
							{
							if( !in_array($rr[$k], $idsu) )
								$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='x' where idschi='".$idschi."' and iduser='".$rr[$k]."'");
							}
						}
					}
				}
			}
		}
	}
	return evalFlowInstance($idschi);
}


function getApproversFlow($formula)
{
	$result = array();
	$tab = explode(",", $formula);
	for( $i= 0; $i < count($tab); $i++)
		{
		if( strchr($tab[$i], "&"))
			$op = "&";
		else
			$op = "|";

		$rr = explode($op, $tab[$i]);
		for( $k = 0; $k < count($rr); $k++)
			{
			$result[] = $rr[$k];
			}
		}
	return $result;
}

function isUserApproverFlow($idsa, $iduser, $update=false)
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

function getWaitingIdsFlowInstance($scinfo, $idschi, $notify=false)
{
	$db = $GLOBALS['babDB'];
	$result = array();
	$notifytab = array();
	$tab = explode(",", $scinfo['formula']);
	for( $i= 0; $i < count($tab); $i++)
		{
		if( strchr($tab[$i], "&"))
			$op = "&";
		else
			$op = "|";

		$rr = explode($op, $tab[$i]);
		for( $k = 0; $k < count($rr); $k++)
			{
			$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$rr[$k]."'");
			$arr2 = $db->db_fetch_array($res);
			if( $arr2['result'] == "")
				{
				$result[] = $rr[$k];
				if( $notify && $arr2['notified'] == "N" )
					{
					$notifytab[] = $rr[$k];
					$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set notified='Y' where id='".$arr2['id']."'");
					}
				}
			}

		if( $scinfo['forder'] == "Y" &&  count($result) > 0 )
			{
			break;
			}
		}

	if( $notify)
	{
		return $notifytab;
	}
	else
	{
		return $result;
	}
}

function getWaitingApproversFlowInstance($idschi, $notify=false)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$result = getWaitingIdsFlowInstance($arr, $idschi, $notify);
		if( count($result) > 0 && $arr['satype'] == 1 )
			{
			if( in_array(0, $result))
				{
				for( $i = 0; $i < count($result); $i++ )
					{
					if( $result[$i] != 0 )
						{
						$arroles[] = $result[$i];
						}
					}
				$rr1 = bab_getSuperior($arr['iduser']);
				}
			else
				{
				$arroles = $result;
				}
			if( count($arroles) > 0 )
				{
				$rr =  bab_getOrgChartRoleUsers($arroles);
				$result = $rr['iduser'];
				}
			if( count($rr1['iduser']) > 0 )
				{
				$result[] = $rr1['iduser'][0];
				}
			}
		}

	return $result;
}

function getWaitingApprobations($iduser, $update=false)
{
	static $wauser = array();
	if( isset($wauser[$iduser]) && !$update )
	{
		return $wauser[$iduser];
	}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select frit.*, fit.idsch, fat.satype, fit.iduser as fit_iduser from ".BAB_FAR_INSTANCES_TBL." frit left join ".BAB_FA_INSTANCES_TBL." fit on frit.idschi=fit.id left join ".BAB_FLOW_APPROVERS_TBL." fat on fit.idsch=fat.id where (frit.iduser='".$iduser."' or frit.iduser='0') and frit.result='' and frit.notified='Y'");
	$result = array();
	while( $row = $db->db_fetch_array($res))
		{
		switch($row['satype'])
			{
			case 0:
				if( count($result['idschi']) == 0 || !in_array($row['idschi'], $result['idschi']))
					{
					$result['idsch'][] = $row['idsch'];
					$result['idschi'][] = $row['idschi'];
					}
				break;
			case 1:
				if( $row['iduser'] == 0 )
				{
					if( $row['fit_iduser'] != 0 )
						{
						$rr = bab_getSuperior($row['fit_iduser']);
						if( count($rr['iduser']) > 0  && $rr['iduser'][0] == $iduser )
							{
							if( count($result['idschi']) == 0 || !in_array($row['idschi'], $result['idschi']))
								{
								$result['idsch'][] = $row['idsch'];
								$result['idschi'][] = $row['idschi'];
								}
							}
						}
				}
				else
				{
					$rusers = bab_getOrgChartRoleUsers($row['iduser']);
					for( $i = 0; $i < count($rusers['iduser']); $i++ )
					{
						if( $rusers['iduser'][$i] == $iduser )
						{
							if( count($result['idschi']) == 0 || !in_array($row['idschi'], $result['idschi']))
								{
								$result['idsch'][] = $row['idsch'];
								$result['idschi'][] = $row['idschi'];
								}
						}
					}
				}
				
				break;
			default:
				break;
			}
		}
	$wauser[$iduser] = $result;
	return $result;

}


?>