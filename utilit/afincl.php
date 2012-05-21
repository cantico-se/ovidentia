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

function updateSchemaInstance($idschi)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$babDB->db_escape_string($idschi)."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$arr = $babDB->db_fetch_array($res);
	$tab = explode(",", $arr['formula']);
	for( $i= 0; $i < count($tab); $i++)
		{
		$rr = array();
		if( strchr($tab[$i], "&"))
			$op = "&";
		else
			$op = "|";

		$rr = explode($op, $tab[$i]);

		$res = $babDB->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and far_order='".$i."'");

		while( $arr3 = $babDB->db_fetch_array($res))
			{
			if( !in_array($arr3['iduser'], $rr))
				{
				$babDB->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where id='".$babDB->db_escape_string($arr3['id'])."'");
				}
			else 
				{
				for($j = 0; $j < count($rr); $j++)
					{
						if ($rr[$j] == $arr3['iduser'])
						{
							array_splice($rr, $j, 1);
							break;
						}
					}
				}
			}

		for($j = 0; $j < count($rr); $j++)
			{
			$babDB->db_query("insert into ".BAB_FAR_INSTANCES_TBL." (idschi, iduser, far_order) VALUES ('".$babDB->db_escape_string($idschi)."', '".$babDB->db_escape_string($rr[$j])."', '".$i."')");
			}
		$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET schi_change='1'");
		bab_siteMap::clearAll();
		}
}


/**
 * Create workflow instance
 * @param	int		$idsch	approbation scheme
 * @param	string	$extra	instance identification string
 * @param	int		[$user]	Owner for the auto-approbation, 0 = no auto-approbation
 */
function makeFlowInstance($idsch, $extra, $user = 0)
{
	global $babDB;
	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($idsch)."'");
	$result = array();

	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if( !empty($GLOBALS['BAB_SESS_USERID']))
			{
			$idcurrentuser = $GLOBALS['BAB_SESS_USERID'];
			}
		else
			{
			$idcurrentuser = 0;
			}
		$babDB->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra, iduser) VALUES ('".$babDB->db_escape_string($idsch)."', '".$babDB->db_escape_string($extra)."', '".$babDB->db_escape_string($idcurrentuser)."')");
		$id = $babDB->db_insert_id();
		$babDB->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".$babDB->db_escape_string(($arr['refcount'] + 1))."' where id='".$babDB->db_escape_string($idsch)."'");
		updateSchemaInstance($id);
		if( $user )
			{
			$nfusers = getWaitingApproversFlowInstance($id, false);
			while (count($nfusers) > 0 && in_array($user, $nfusers))
				{
				$res = updateFlowInstance($id, $user, true);
				// $res can't have -1 as value. See last parameter of updateFlowInstance function
				switch($res)
					{
					case 1: // AF accepted
						deleteFlowInstance($id);				
						return true;
							
					default: // AF continue
						$nfusers = getWaitingApproversFlowInstance($id, false);
						break;
					}
				}
			}
		return $id;
		}
	return 0;
}


/**
 * @param	int		$idschi
 * @param	int		$id_user
 */
function setFlowInstanceOwner($idschi, $id_user) {
	global $babDB;
	
	$babDB->db_query('UPDATE '.BAB_FA_INSTANCES_TBL.' 
		SET iduser='.$babDB->quote($id_user).' 
		WHERE id='.$babDB->quote($idschi)
	);
}



function evalFlowInstance($idschi)
{
	global $babDB;
	$res = $babDB->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and result='0'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		return 0;

	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$babDB->db_escape_string($idschi)."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$arr = explode(",", $arr['formula']);
		for( $i= 0; $i < count($arr); $i++)
			{
			if( strchr($arr[$i], "&"))
				$op = "&";
			else
				$op = "|";

			$rr = explode($op, $arr[$i]);

			switch($op)
				{
				case "&":
					$res = $babDB->db_query("select id from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and iduser IN (".$babDB->quote($rr).") and far_order='".$i."' and result=''");
					if( $res && $babDB->db_num_rows($res) > 0)
						{
						return -1;
						}
					break;
				case "|":
					$res = $babDB->db_query("select id from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and iduser IN (".$babDB->quote($rr).") and far_order='".$i."' and result=''");
					if( $res && $babDB->db_num_rows($res) > 0)
						{
						return -1;
						}
					break;
				default:
					$res = $babDB->db_query("select id from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and iduser='".$babDB->db_escape_string($arr[$i])."' and far_order='".$i."'");
					$tab = $babDB->db_fetch_array($res);
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
	global $babDB;
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FA_INSTANCES_TBL." where id='".$babDB->db_escape_string($idschi)."'"));
	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($arr['idsch'])."'"));
	if( $arr['refcount'] > 0)
		$babDB->db_query("update ".BAB_FLOW_APPROVERS_TBL." set refcount='".$babDB->db_escape_string(($arr['refcount'] - 1 ))."' where id='".$babDB->db_escape_string($arr['id'])."'");

	$babDB->db_query("delete from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."'");
	$babDB->db_query("delete from ".BAB_FA_INSTANCES_TBL." where id='".$babDB->db_escape_string($idschi)."'");
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET schi_change='1'");
	bab_siteMap::clearAll();
}








class bab_UserUnavailability
{
	/**
	 * List supperiors of entities where i am temporary employee
	 * The temporary employee replace the supperior when he is unavailable
	 * @return array
	 */
	private static function getSuperiors($id_user)
	{
		$superiors = array();
		$entities = bab_OCGetUserEntities($id_user);
		if( count($entities['temporary']) > 0 )
		{
		// liste des entitees ou l'utilisateur est interimaire
			
		for( $i=0; $i < count($entities['temporary']); $i++ )
			{
			// trouver le supperieur hirarchique de l'entitee
			$idsup = bab_OCGetSuperior($entities['temporary'][$i]['id']);
			if( $idsup )
				{
				$superiors[] =  $idsup['id_user'];
				}
			}
		}
		
		return $superiors;
	}
	
	
	/**
	 * 
	 * [0] liste des user que je remplace, par l'interface manuelle
	 * [1] liste des user que je remplace, par l'organigramme
	 * 
	 * @param	int		$id_user (mon id user) BAB_SESS_USERID
	 * @return array
	 */
	public static function get($id_user)
	{
		global $babBody, $babDB;
		$substitutes = array(
			0 => array(),
			1 => array()
		);
		
		if('Y' === $babBody->babsite['change_unavailability']) 
		{
			$res = $babDB->db_query("select id_user, id_substitute from ".BAB_USERS_UNAVAILABILITY_TBL." where curdate() between start_date and end_date");
			if( $res && $babDB->db_num_rows($res) > 0 )
			{
				
				include_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
						
				$superiors = self::getSuperiors($id_user); // liste des personnes que je remplace dans l'organigramme
	
				while($arr = $babDB->db_fetch_array($res))
				{
					$idsup = 0;
					if( count($superiors) && in_array($arr['id_user'], $superiors))
					{
						if( count($substitutes[1]) == 0 ||  !in_array($arr['id_user'], $substitutes[1]) )
						{
							$substitutes[1][] = $arr['id_user'];
						}
					}
					
					
					
					

					if($arr['id_substitute'] == $id_user && (count($substitutes[0]) == 0 || !in_array($arr['id_user'], $substitutes[0])))
					{
						$add = true;
						$entities = bab_OCGetUserEntities($arr['id_user']);
						if( count($entities['superior']) > 0 )
						{
							// si le substitut est dans l'organigramme, on verifie qu'il est bien un interimaire de la personne a remplacer
							for( $i=0; $i < count($entities['superior']); $i++ )
							{
								$idte = bab_OCGetTemporaryEmployee($entities['superior'][$i]['id']);
								if( $idte && $idte['id_user'] != $id_user)
								{
									$add = false;
									break;
								}
							}
						}
	
						if( count($substitutes[0]) == 0 || !in_array($arr['id_user'], $substitutes[0]) )
						{
							$substitutes[0][] = $arr['id_user'];
						}
	
						if( $add && (count($substitutes[1]) == 0 || !in_array($arr['id_user'], $substitutes[1]) ))
						{
							$substitutes[1][] = $arr['id_user'];
						}
					}
				}
			}
		}
		
		return $substitutes;
	}
}







function updateFlowInstance($idschi, $iduser, $bool)
{
	global $babDB;
	
	$substitutes = bab_UserUnavailability::get($GLOBALS['BAB_SESS_USERID']);

	$idusers = array($iduser);
	for( $j=0; $j < 2; $j++)
	{
		for( $k=0; $k < count($substitutes[$j]); $k++)
		{
			if( !in_array($substitutes[$j][$k], $idusers))
				{
				$idusers[] = $substitutes[$j][$k];
				}
		}
	}

	$scinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." fat left join ".BAB_FA_INSTANCES_TBL." fait on fait.idsch=fat.id where fait.id='".$babDB->db_escape_string($idschi)."'"));

	if( $scinfo['satype'] == 1 )
	{
		$idroles = array();
		$roles = getApproversFlow($scinfo['formula']);
		if( count($roles) > 0 )
		{

			if( in_array(0, $roles ))
			{
				$rr = bab_getSuperior($scinfo['iduser'], $scinfo['id_oc']);
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
				if( isset($arr['iduser']))
				{
					for( $i = 0; $i < count($arr['iduser']); $i++ )
					{
						if( in_array($arr['iduser'][$i], $idusers) )
						{
							$idroles[] = $arr['idrole'][$i];
						}
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

			$res = $babDB->db_query("select ugt.id_group from ".BAB_USERS_GROUPS_TBL." ugt where ugt.id_object='".$babDB->db_escape_string($iduser)."'");
			while( $rr = $babDB->db_fetch_array($res))
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

		if( $bool)
			$result = "1";
		else
			$result ="0";

		$arr = explode(",", $scinfo['formula']);
		for( $i= 0; $i < count($arr); $i++)
			{
			if( strchr($arr[$i], "&"))
				$op = "&";
			else if( strchr($arr[$i], "|"))
				$op = "|";
			else
				$op = '';
			if( $op )
				{
				$rr = explode($op, $arr[$i]);
				}
			else
				{
				$rr = array($arr[$i]);
				}

			$res = $babDB->db_query("select id from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and far_order='".$i."' and result=''");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$babDB->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='".$babDB->db_escape_string($result)."', notified='Y' where idschi='".$babDB->db_escape_string($idschi)."' and iduser IN (".$babDB->quote($idusers).")");

				if( !$bool || $op == '|')
					{
					$babDB->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='x' where idschi='".$babDB->db_escape_string($idschi)."' and far_order='".$i."' and result=''");
					}

				if( $scinfo['forder'] == 'Y' )
					{
					break;
					}
				}
			}

	}
	$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET schi_change='1'");
	bab_siteMap::clearAll();
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
	global $babDB;
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
			$res = $babDB->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$babDB->db_escape_string($idschi)."' and iduser='".$babDB->db_escape_string($rr[$k])."' and far_order='".$i."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr2 = $babDB->db_fetch_array($res);
				if( $arr2['result'] == "")
					{
					$result[] = $rr[$k];
					if( $notify && $arr2['notified'] == "N" )
						{
						$notifytab[] = $rr[$k];
						$babDB->db_query("update ".BAB_FAR_INSTANCES_TBL." set notified='Y' where id='".$babDB->db_escape_string($arr2['id'])."'");
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
		$babDB->db_query("UPDATE ".BAB_USERS_LOG_TBL." SET schi_change='1'");
		bab_siteMap::clearAll();
		return $notifytab;
	}
	else
	{
		return $result;
	}
}

function getWaitingApproversFlowInstance($idschi, $notify=false)
{
	global $babDB;
	$res = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$babDB->db_escape_string($idschi)."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
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
						$rr1 = bab_getSuperior($arr['iduser'], $arr['id_oc']);
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
					if( isset($rr1['iduser']) && count($rr1['iduser']) > 0 )
						{
						$ret[] = $rr1['iduser'][0];
						}
					$result = $ret;
					break;
				case 2:
					if( in_array(1, $result)) // registered users
						{
						$res2 = $babDB->db_query("select id from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
						}
					else
						{
						$res2 = $babDB->db_query("select distinct ut.id from ".BAB_USERS_TBL." ut left join ".BAB_USERS_GROUPS_TBL." ugt on ugt.id_object=ut.id where ut.is_confirmed='1' and ut.disabled='0' and ugt.id_group in (".$babDB->quote($result).")");
						}

					$ret = array();
					while( $rr = $babDB->db_fetch_array($res2))
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
	$res = $babDB->db_query("select id_user, id_substitute from ".BAB_USERS_UNAVAILABILITY_TBL." where curdate() between start_date and end_date and id_user in (".$babDB->quote($result).")");

	$substitutes = array();
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/ocapi.php";
		while( $arr = $babDB->db_fetch_array($res) )
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
	global $babDB;

	if( isset($_SESSION['bab_waitingApprobations'][$iduser]) && !$update )
	{
		
		return $_SESSION['bab_waitingApprobations'][$iduser];
	}

	$res = $babDB->db_query("select 
		
			frit.*, 
			fit.idsch, 
			fat.satype, 
			fat.id_oc, 
			fit.iduser as fit_iduser 

		from ".BAB_FAR_INSTANCES_TBL." frit, 
		".BAB_FA_INSTANCES_TBL." fit,
		".BAB_FLOW_APPROVERS_TBL." fat 

		where 
			frit.idschi=fit.id 
			AND fit.idsch=fat.id 
			AND frit.result='' 
			AND frit.notified='Y'
	");

	$result['idsch'] = array();
	$result['idschi'] = array();
	while( $row = $babDB->db_fetch_array($res))
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
						$rr = bab_getSuperior($row['fit_iduser'], $row['id_oc']);
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
					if( isset($rusers['iduser']))
					{
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
		$substitutes = bab_UserUnavailability::get($GLOBALS['BAB_SESS_USERID']);
		
		
		$arrsub = array_unique(array_merge($substitutes[0], $substitutes[1]));

		for($i = 0; $i < count($arrsub); $i++ )
		{
			$rr = getWaitingApprobations($arrsub[$i], $update);
			for( $k=0; $k < count($rr['idsch']); $k++ )
			{
				$add = false;

				list($type) = $babDB->db_fetch_row($babDB->db_query("select satype from ".BAB_FLOW_APPROVERS_TBL." where id='".$babDB->db_escape_string($rr['idsch'][$k])."'"));
				if( $type == 1 && in_array($arrsub[$i], $substitutes[1]))
				{
					$add = true;
				}
				elseif( in_array($arrsub[$i], $substitutes[0]) )
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
	$_SESSION['bab_waitingApprobations'][$iduser] = $result;
	return $result;

}


?>