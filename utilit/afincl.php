<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
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
		$db->db_query("insert into ".BAB_FA_INSTANCES_TBL." (idsch, extra) VALUES ('".$idsch."', '".$extra."')");
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
	$res = $db->db_query("select * from ".BAB_FAR_INSTANCES_TBL." where idschi='".$idschi."' and iduser='".$iduser."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $bool)
			$result = "1";
		else
			$result ="0";
		$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='".$result."' where id='".$arr['id']."'");
		
		if( $result == 0 )
			{
			$db->db_query("update ".BAB_FAR_INSTANCES_TBL." set result='x' where idschi='".$idschi."' and result=''");
			}
		else
			{
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

					if( $op != "")
						{
						$rr = explode($op, $arr[$i]);
						if( count($rr) > 1 && $op == "|" && in_array($iduser, $rr))
							{
							for( $k = 0; $k < count($rr); $k++)
								{
								if( $rr[$k] != $iduser )
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


function getApproversFlow($idsa)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsa."'");
	$result = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$tab = explode(",", $arr['formula']);
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
		}
	return $result;
}

function isUserApproverFlow($idsa, $iduser)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." where id='".$idsa."'");
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$tab = explode(",", $arr['formula']);
		for( $i= 0; $i < count($tab); $i++)
			{
			if( strchr($tab[$i], "&"))
				$op = "&";
			else
				$op = "|";

			$rr = explode($op, $tab[$i]);
			for( $k = 0; $k < count($rr); $k++)
				{
				if( $rr[$k] == $iduser )
					return true;
				}
			}
		}
	return false;
}

function getWaitingApproversFlowInstance($idschi, $notify=false)
{
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." join ".BAB_FA_INSTANCES_TBL." where ".BAB_FA_INSTANCES_TBL.".id='".$idschi."' and ".BAB_FA_INSTANCES_TBL.".idsch=".BAB_FLOW_APPROVERS_TBL.".id");
	$result = array();
	$notifytab = array();
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$tab = explode(",", $arr['formula']);
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

			if( $arr['forder'] == "Y" &&  count($result) > 0 )
				{
				if( $notify)
				{
					return $notifytab;
				}
				else
				{
					return $result;
				}
				}
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

?>