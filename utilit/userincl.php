<?php
function isUserApprover($topics)
	{
	global $BAB_SESS_USERID;
	$db = new db_mysql();
	$query = "select * from topics where id='$topics' and id_approver='$BAB_SESS_USERID'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return true;
		}
	else
		{
		return false;
		}
	}

function isUserGroupManager($grpid="")
	{
	global $BAB_SESS_USERID;
	if( empty($grpid))
		$query = "select * from groups where manager='$BAB_SESS_USERID'";
	else
		$query = "select * from groups where manager='$BAB_SESS_USERID' and id='$grpid'";

	$db = new db_mysql();
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return true;
		}
	else
		{
		return false;
		}
	}


function isMemberOf($groupname, $userid="")
{
	global $BAB_SESS_USERID;
	if( !empty($groupname))
		{
		if( $userid == "")
			$userid = $BAB_SESS_USERID;
		$db = new db_mysql();
		$req = "select * from groups where name='$groupname'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$req = "select * from users_groups where id_object='$userid' and id_group='$arr[id]'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				return $arr[id];
			else
				return 0;
			}
		else
			return 0;
		}
	else
		return 0;
}

function isUserAdministrator()
{
	$db = new db_mysql();
	$req = "select * from groups where id='3'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return isMemberOf($arr[name]);
		}
	else
		return 0;
}


function isAccessValid($table, $idobject)
{
	global $body, $BAB_SESS_USERID, $LOGGED_IN;
	$add = false;
	if( !isset($idobject))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid item !!");
		return $add;
		}
	$db = new db_mysql();
	$req = "select * from ".$table." where id_object='$idobject' and id_group='0'"; // everybody
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$add = true;
		}
	else
		{
		$req = "select * from ".$table." where id_object='$idobject' and id_group='1'"; // users
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0 && $LOGGED_IN)
			{
			$add = true;
			}
		else
			{
			$req = "select * from ".$table." where id_object='$idobject' and id_group='2'"; //guests
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				if(!$LOGGED_IN)
					$add = true;
				}
			else if( $BAB_SESS_USERID != "")
				{
				$req = "select * from ".$table." where id_object='$idobject'"; //groups
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0 )
					{
					while( $row = $db->db_fetch_array($res))
						{
						$req = "select * from users_groups where id_object=$BAB_SESS_USERID and id_group='$row[id_group]'"; //groups
						$res2 = $db->db_query($req);
						if( $res2 && $db->db_num_rows($res2) > 0 )
							{
							$add = true;
							break;
							}
						}
					}
				}
			}
		}
	return $add;
}

/* for all users */
function isUserAlreadyLogged($iduser)
{
	$db = new db_mysql();
	$req="select * from users_log where id_user='$iduser'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr[islogged] == "Y")
			return true;		
		}
	return false;
}

/* for current user */
function userIsloggedin()
	{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$LOGGED_IN;

	if (isset($LOGGED_IN))
		{
		return $LOGGED_IN;
		}
	if (!empty($BAB_SESS_NICKNAME) && !empty($BAB_SESS_HASHID))
		{
		$hash=md5($BAB_SESS_NICKNAME.$BAB_HASH_VAR);
		if ($hash == $BAB_SESS_HASHID)
			{
			$LOGGED_IN=true;
			}
		else
			{
			$LOGGED_IN=false;
			}
		}
	else
		{
		$LOGGED_IN=false;
		}
    return $LOGGED_IN;
	}

function getUserName($id)
	{
	$db = new db_mysql();
	$query = "select * from users where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return composeName($arr[firstname], $arr[lastname]);
		}
	else
		{
		return "";
		}
	}

function isUserVacationApprover($groupid = 0)
	{
	global $BAB_SESS_USERID;
	$db = new db_mysql();
	if( $groupid == 0)
		$query = "select * from vacationsman_groups where id_object='$BAB_SESS_USERID' or supplier='$BAB_SESS_USERID'";
	else
		$query = "select * from vacationsman_groups where id_object='$BAB_SESS_USERID'  or supplier='$BAB_SESS_USERID' and id_group='$groupid'";

	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return true;
		}
	else
		{
		return false;
		}
	}


function useVacation($iduser)
	{
	$db = new db_mysql();
	$query = "select * from users_groups where id_object='$iduser' and isprimary='Y'";
	$res = $db->db_query($query);

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from groups where id='".$arr[id_group]."' and vacation='Y'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			return true;
			}
		else
			return false;
		}
	else
		{
		return false;
		}
	}

function getGroupName($id)
	{
	$db = new db_mysql();
	$query = "select * from groups where id='$id'";
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

function getPrimaryGroupId($userid)
	{
	$db = new db_mysql();
	$query = "select * from users_groups where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[id_group];
		}
	else
		{
		return "";
		}
	}

/* 0 no access, 1 user, 2 user/manager, 3 manager*/ 
function mailAccessLevel()
	{
	$db = new db_mysql();

	$bemail = 0;
	$req = "select * from users_groups join groups where id_object='$GLOBALS[BAB_SESS_USERID]' and mail='Y'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		$bemail = 1;

	$req = "select * from groups where manager='$GLOBALS[BAB_SESS_USERID]' and mail='Y'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		if( $bemail )
			$bemail++;
		else
			$bemail = 3;
		}
	return $bemail;
	}


function getUserId( $name )
	{
	$replace = array( " " => "", "-" => "");
	$db = new db_mysql();
	$hash = md5(strtolower(strtr($name, $replace)));
	$query = "select * from users where hashname='".$hash."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[id];
		}
	else
		return 0;
	}
?>