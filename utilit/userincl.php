<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
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
	if( empty($BAB_SESS_USERID))
		return false;

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
			$req = "select * from users_groups where id_object='$userid' and id_group='".$arr['id']."'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				return $arr['id'];
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
	global $BAB_SESS_USERID;
	$db = new db_mysql();
	$req = "select * from users_groups where id_object='".$BAB_SESS_USERID."' and id_group='3'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return 3;
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
						$req = "select * from users_groups where id_object=$BAB_SESS_USERID and id_group='".$row['id_group']."'"; //groups
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
		if( $arr['islogged'] == "Y")
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
		return composeName($arr['firstname'], $arr['lastname']);
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
		$query = "select * from groups where id='".$arr['id_group']."' and vacation='Y'";
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
	switch( $id )
		{
		case 1:
			return babTranslate("Registered users");
		case 2:
			return babTranslate("Unregistered users");
		default:
			$db = new db_mysql();
			$query = "select * from groups where id='$id'";
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
	}

function getPrimaryGroupId($userid)
	{
	$db = new db_mysql();
	$query = "select * from users_groups where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id_group'];
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
	$req = "select * from users_groups join groups where id_object='".$GLOBALS['BAB_SESS_USERID']."' and mail='Y'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		$bemail = 1;

	$req = "select * from groups where manager='".$GLOBALS['BAB_SESS_USERID']."' and mail='Y'";
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

function fileManagerAccessLevel()
	{
	global $BAB_SESS_USERID;
	$db = new db_mysql();
	$aret = array();
	$badmin = isUserAdministrator();

	$req = "select * from groups where id=2 and (ustorage ='Y' or gstorage ='Y')";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$arr = $db->db_fetch_array($res);
		$aret['id'][] = 2;
		$aret['pu'][] = $arr['gstorage'] == "Y"? 1: 0;
		$aret['pr'][] = 0;
		if( $badmin )
			$aret['ma'][] = 1;
		else
			$aret['ma'][] = 0;
		}

	if( !empty($BAB_SESS_USERID))
		{
		$req = "select * from groups where id=1 and (ustorage ='Y' or gstorage ='Y')";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0 )
			{
			$arr = $db->db_fetch_array($res);
			$aret['id'][] = 1;
			$aret['pu'][] = $arr['gstorage'] == "Y"? 1: 0;
			$aret['pr'][] = $arr['ustorage'] == "Y"? 1: 0;
			if( $badmin )
				$aret['ma'][] = 1;
			else
				$aret['ma'][] = 0;
			}

		$req = "select groups.id, groups.gstorage, groups.ustorage from groups join users_groups where id_object='".$BAB_SESS_USERID."' and groups.id=users_groups.id_group and groups.manager !='".$BAB_SESS_USERID."' and (groups.ustorage ='Y' or groups.gstorage ='Y')";
		$res = $db->db_query($req);
		while( $arr = $db->db_fetch_array($res))
			{
			$aret['id'][] = $arr['id'];
			$aret['pu'][] = $arr['gstorage'] == "Y"? 1: 0;
			$aret['pr'][] = $arr['ustorage'] == "Y"? 1: 0;
			$aret['ma'][] = 0;
			}


		$req = "select id, gstorage, ustorage from groups where manager='".$BAB_SESS_USERID."' and gstorage='Y'";
		$res = $db->db_query($req);
		while( $arr = $db->db_fetch_array($res))
			{
			$aret['id'][] = $arr['id'];
			$aret['pu'][] = $arr['gstorage'] == "Y"? 1: 0;
			$aret['pr'][] = $arr['ustorage'] == "Y"? 1: 0;
			$aret['ma'][] = 1;
			}

		
		}
	return $aret;
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
		return $arr['id'];
		}
	else
		return 0;
	}

function babReplace( $txt )
{
	$db = new db_mysql();
	$reg = "/\\\$ARTICLE\((.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from articles where title like '%".addslashes(trim($m[1][$k]))."%'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(isAccessValid("topicsview_groups", $arr['id_topic'])) 
					$txt = preg_replace("/\\\$ARTICLE\(".$m[1][$k]."\)/", "<a href=\"".$GLOBALS['babUrl']."index.php?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']."\">".$arr['title']."</a>", $txt);
				else
					$txt = preg_replace("/\\\$ARTICLE\(".$m[1][$k]."\)/", $arr['title'], $txt);
				}
			}
		}

	$reg = "/\\\$CONTACT\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from contacts where  owner='".$GLOBALS['BAB_SESS_USERID']."' and firstname like '%".addslashes(trim($m[1][$k]))."%' and lastname like '%".addslashes(trim($m[2][$k]))."%'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$txt = preg_replace("/\\\$CONTACT\(".$m[1][$k].",".$m[2][$k]."\)/", "<a href=\"javascript:{var d=window.open('".$GLOBALS['babUrl']."/index.php?tg=contact&idx=modify&item=".$arr['id']."&bliste=0', 'Contact', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');}\">".$m[1][$k]." ".$m[2][$k]."</a>", $txt);
				}
			else
				$txt = preg_replace("/\\\$CONTACT\(".$m[1][$k].",".$m[2][$k]."\)/", $m[1][$k]." ".$m[2][$k], $txt);
			}
		}
	return $txt;
}
?>