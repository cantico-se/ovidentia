<?php
function getCategoryCalName($id)
	{
	$db = new db_mysql();
	$query = "select * from categoriescal where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[name];
		}
	else
		{
		return "";
		}
	}

function getResourceCalName($id)
	{
	$db = new db_mysql();
	$query = "select * from resourcescal where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[name];
		}
	else
		{
		return "";
		}
	}

function getCalendarid($iduser, $type)
{
	$db = new db_mysql();
	$query = "select * from calendar where owner='$iduser' and actif='Y' and type='".$type."'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[id];
		}
	else
		{
		return 0;
		}
}

function getCalendarType($idcal)
{
	$db = new db_mysql();
	$query = "select * from calendar where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[type];
		}
	else
		{
		return 0;
		}
}

function getEventTitle($evtid)
{
	$db = new db_mysql();
	$query = "select * from cal_events where id='$evtid'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[title];
		}
	else
		{
		return "";
		}
}

function getCalendarOwnerName($idcal, $type)
{
	$ret = "";
	$db = new db_mysql();
	$query = "select * from calendar where id='$idcal'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		if( $type == 1)
			{
			$arr = $db->db_fetch_array($res);
			$query = "select * from users where id='".$arr[owner]."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = $arr[fullname];
			}
		else if( $type == 2)
			{
			$arr = $db->db_fetch_array($res);
			$query = "select * from groups where id='".$arr[owner]."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = $arr[name];
			}
		else if( $type == 3)
			{
			$arr = $db->db_fetch_array($res);
			$query = "select * from resourcescal where id='".$arr[owner]."'";
			$res = $db->db_query($query);
			$arr = $db->db_fetch_array($res);
			$ret = $arr[name];
			}
		return $ret;
		}
	else
		{
		return $ret;
		}
}

?>