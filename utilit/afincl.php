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
	$tabusers = array();
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
			if( count($tabusers) == 0 || !in_array( $rr[$k], $tabusers ))
				$tabusers[] = $rr[$k];
			}
		}

	if( count($tabusers) > 0 )
	{
		$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."'");
		while( $arr3 = $db->db_fetch_array($res))
			{
			if( !in_array($arr3['iduser'], $tabusers))
				{
				$db->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where id='".$arr3['id']."'");
				}
			else 
				{
				for($j = 0; $j < count($tabusers); $j++)
					{
						if ($tabusers[$j] == $arr3['iduser'])
						{
							array_splice($tabusers, $j, 1);
							break;
						}
					}
				}
			}

		for($j = 0; $j < count($tabusers); $j++)
			{
			$db->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser) VALUES ('".$idschi."', '".$tabusers[$j]."')");
			}
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
	global $babBody;

	$db = $GLOBALS['babDB'];

	$idusers = array($iduser);
	for( $j=0; $j < 2; $j++)
	{
		for( $k=0; $k < count($babBody->substitutes[$j]); $k++)
		{
			if( !in_array($babBody->substitutes[$j][$k], $idusers))
				{
				$idusers[] = $babBody->substitutes[$j][$k];
				}
		}
	}

	$scinfo = $db->db_fetch_array($db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." fat left join ".BAB_FA_INSTANCES_TBL." fait on fait.idsch=fat.id where fait.id='".$idschi."'"));

	if( $scinfo['satype'] == 1 )
	{
		$idroles = array();
		$roles = getApproversFlow($scinfo['formula']);
		if( count($roles) > 0 )
		{

			if( in_array(0, $roles ))
			{
				$rr = bab_getSuperior($scinfo['iduser']);
				if( count($rr['iduser']) > 0  && in_array($rr['iduser'][0], $idusers) )
					{
					$idroles[] = 0;
					}
			}
			$idnroles = array();
			for( $i = 0; $i < count($roles); $i++ )
			{
				if( $roles[$i] != 0 )
				{
					$idnroles[] = $roles[$i];
				}
			}

			if( count($idnroles) > 0 )
			{
				$arr = bab_getOrgChartRoleUsers($idnroles);
				for( $i = 0; $i < count($arr['iduser']); $i++ )
				{
					if( in_array($arr['iduser'][$i], $idusers) )
					{
						$idroles[] = $arr['idrole'][$i];
					}
				}
			}

		}
		$idusers = $idroles;
	}
	elseif( $scinfo['satype'] == 2 )
	{
		$idgroups = array();
		$groups = getApproversFlow($scinfo['formula']);
		if( count($groups) > 0 )
		{
			if( in_array(1, $groups))
			{
				$idgroups[] = 1;
			}

			$res = $db->db_query("select ugt.id_group from ".BAB_USERS_GROUPS_TBL." ugt where ugt.id_object='".$iduser."'");
			while( $rr = $db->db_fetch_array($res))
			{
				if( in_array($rr['id_group'], $groups))
				{
					$idgroups[] = $rr['id_group'];
				}
			}
		}
		$idusers = $idgroups;
	}

	if( count($idusers) > 0 )
	{
		$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser IN (".implode(',', $idusers).")");
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
							if( !in_array($rr[$k], $idusers) )
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
			if( $res && $db->db_num_rows($res) > 0 )
				{
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
		if( count($result) > 0 )
			{
			switch($arr['satype'])
				{
				case 1:
					$arroles = array();
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
					$ret = array();
					if( count($arroles) > 0 )
						{
						$rr =  bab_getOrgChartRoleUsers($arroles);
						$ret[] = $rr['iduser'][0];
						}
					if( isset($rr1) && count($rr1['iduser']) > 0 )
						{
						$ret[] = $rr1['iduser'][0];
						}
					$result = $ret;
					break;
				case 2:
					if( in_array(1, $result)) // registered users
						{
						$res2 = $db->db_query("select id from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
						}
					else
						{
						$res2 = $db->db_query("select distinct ut.id from ".BAB_USERS_TBL." ut left join ".BAB_USERS_GROUPS_TBL." ugt on ugt.id_object=ut.id where ut.is_confirmed='1' and ut.disabled='0' and ugt.id_group in (".implode(',', $result).")");
						}

					$ret = array();
					while( $rr = $db->db_fetch_array($res2))
						{
						$ret[] = $rr['id'];
						}
					$result = $ret;
					break;
				default:
					break;
				}
			}
		}

	if( count($result) > 0 )
	{
	$res = $db->db_query("select id_user, id_substitute from ".BAB_USERS_UNAVAILABILITY_TBL." where curdate() between start_date and end_date and id_user in (".implode(',', $result).")");

	$substitutes = array();
	if( $res && $db->db_num_rows($res) > 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/ocapi.php";
		while( $arr = $db->db_fetch_array($res) )
			{
			if( $arr['id_substitute'] !=  0 )
				{
				if( !isset($substitutes[$arr['id_user']] ) || !in_array($arr['id_substitute'], $substitutes[$arr['id_user']]))
					{
					$substitutes[$arr['id_user']][] =  $arr['id_substitute'];
					}
				}

			$entities = bab_OCGetUserEntities($arr['id_user']);
			if( count($entities['superior']) > 0 )
				{
				for( $i=0; $i < count($entities['superior']); $i++ )
					{
					$idsub = bab_OCGetTemporaryEmployee($entities['superior'][$i]['id']);
					if( $idsub )
						if( !isset($substitutes[$arr['id_user']]) || !in_array($idsub['id_user'],$substitutes[$arr['id_user']]))
						{
						$substitutes[$arr['id_user']][] =  $idsub['id_user'];
						}
					}
				}				
			}
		}

	if( count($substitutes) > 0 )
		{
		foreach($substitutes as $i => $val )
			{
			for( $k=0; $k < count($val); $k++ )
				{
				if( !in_array($val[$k], $result) )
					{
					$result[] = $val[$k];
					}
				}
			}
		}
	}
	return $result;
}

function getWaitingApprobations($iduser, $update=false)
{
	global $babBody;

	static $wauser = array();
	if( isset($wauser[$iduser]) && !$update )
	{
		return $wauser[$iduser];
	}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select frit.*, fit.idsch, fat.satype, fit.iduser as fit_iduser from ".BAB_FAR_INSTANCES_TBL." frit left join ".BAB_FA_INSTANCES_TBL." fit on frit.idschi=fit.id left join ".BAB_FLOW_APPROVERS_TBL." fat on fit.idsch=fat.id where frit.result='' and frit.notified='Y'");
	$result['idsch'] = array();
	$result['idschi'] = array();
	while( $row = $db->db_fetch_array($res))
		{
		switch($row['satype'])
			{
			case 0:
				if( $row['iduser'] == $iduser && (count($result['idschi']) == 0 || !in_array($row['idschi'], $result['idschi'])))
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
						if( isset($rr['iduser']) && count($rr['iduser']) > 0  && $rr['iduser'][0] == $iduser )
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
			case 2:
				if( $row['iduser'] == 1 )
				{
					if( count($result['idschi']) == 0 || !in_array($row['idschi'], $result['idschi']))
						{
						$result['idsch'][] = $row['idsch'];
						$result['idschi'][] = $row['idschi'];
						}
				}
				else
				{
					$groups = bab_getUserGroups($iduser);
					if( count($groups) > 0 && in_array($row['iduser'],$groups['id']))
					{
						if( count($result['idschi']) == 0 || !in_array($row['idschi'], $result['idschi']))
							{
							$result['idsch'][] = $row['idsch'];
							$result['idschi'][] = $row['idschi'];
							}
					}
				}
				break;
			default:
				break;
			}
		}
/**/
	if( $iduser == $GLOBALS['BAB_SESS_USERID'] )
	{
		$arrsub = array_unique(array_merge($babBody->substitutes[0], $babBody->substitutes[1]));

		for($i = 0; $i < count($arrsub); $i++ )
		{
			$rr = getWaitingApprobations($arrsub[$i], $update);
			for( $k=0; $k < count($rr['idsch']); $k++ )
			{
				$add = false;

				list($type) = $db->db_fetch_row($db->db_query("select satype from ".BAB_FLOW_APPROVERS_TBL." where id='".$rr['idsch'][$k]."'"));
				if( $type != 1 && in_array($arrsub[$i], $babBody->substitutes[0]))
				{
					$add = true;
				}
				elseif( in_array($arrsub[$i], $babBody->substitutes[1]) )
				{
					$add = true;
				}

				if( $add )
				{
					if( count($result['idsch']) == 0 || !in_array($rr['idsch'][$k], $result['idsch']))
						{
						$result['idsch'][] = $rr['idsch'][$k];
						}
					if( count($result['idschi']) == 0 || !in_array($rr['idschi'][$k], $result['idschi']))
						{
						$result['idschi'][] = $rr['idschi'][$k];
						}
				}
			}		
		}
	}
/**/
	$wauser[$iduser] = $result;
	return $result;

}


?>