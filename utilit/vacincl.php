<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
$babDayType = array(1=>bab_translate("Whole day"), bab_translate("Morning"), bab_translate("Afternoon"));

function bab_getVacationName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_VACATIONS_TYPES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function bab_getStatusName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_VACATIONS_STATES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['status'];
		}
	else
		{
		return "";
		}
	}


?>