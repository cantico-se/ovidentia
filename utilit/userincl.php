<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function bab_isUserApprover($topics)
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$query = "select id from ".BAB_TOPICS_TBL." where id='$topics' and id_approver='$BAB_SESS_USERID'";
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

function bab_isUserGroupManager($grpid="")
	{
	global $BAB_SESS_USERID;
	if( empty($BAB_SESS_USERID))
		return false;

	if( empty($grpid))
		$query = "select id from ".BAB_GROUPS_TBL." where manager='$BAB_SESS_USERID'";
	else
		$query = "select id from ".BAB_GROUPS_TBL." where manager='$BAB_SESS_USERID' and id='$grpid'";
	$db = $GLOBALS['babDB'];
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


function bab_isMemberOfGroup($groupname, $userid="")
{
	global $BAB_SESS_USERID;
	if( !empty($groupname))
		{
		if( $userid == "")
			$userid = $BAB_SESS_USERID;
		$db = $GLOBALS['babDB'];
		$req = "select id from ".BAB_GROUPS_TBL." where name='$groupname'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='$userid' and id_group='".$arr['id']."'";
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

function bab_isUserAdministrator()
{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and id_group='3'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return 3;
		}
	else
		return 0;
}


function bab_isAccessValid($table, $idobject)
{
	global $babBody, $BAB_SESS_USERID, $BAB_SESS_LOGGED;
	$add = false;
	if( !isset($idobject))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid item !!");
		return $add;
		}
	$db = $GLOBALS['babDB'];
	$req = "select id from ".$table." where id_object='$idobject' and id_group='0'"; // everybody
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$add = true;
		}
	else
		{
		$req = "select id from ".$table." where id_object='$idobject' and id_group='1'"; // users
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0 && $BAB_SESS_LOGGED)
			{
			$add = true;
			}
		else
			{
			$req = "select id from ".$table." where id_object='$idobject' and id_group='2'"; //guests
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				if(!$BAB_SESS_LOGGED)
					$add = true;
				}
			else if( $BAB_SESS_USERID != "")
				{
				$req = "select id_group from ".$table." where id_object='$idobject'"; //groups
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0 )
					{
					while( $row = $db->db_fetch_array($res))
						{
						$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object=$BAB_SESS_USERID and id_group='".$row['id_group']."'"; //groups
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
function bab_isUserLogged($iduser = "")
{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;
	
	if( !isset($iduser) || empty($iduser) || $iduser == $GLOBALS['BAB_SESS_USERID'])
		{
		if (isset($BAB_SESS_LOGGED))
			{
			return $BAB_SESS_LOGGED;
			}

		if (!empty($BAB_SESS_NICKNAME) && !empty($BAB_SESS_HASHID))
			{
			$hash=md5($BAB_SESS_NICKNAME.$BAB_HASH_VAR);
			if ($hash == $BAB_SESS_HASHID)
				{
				$BAB_SESS_LOGGED=true;
				}
			else
				{
				$BAB_SESS_LOGGED=false;
				}
			}
		else
			{
			$BAB_SESS_LOGGED=false;
			}
		return $BAB_SESS_LOGGED;
		}
	else
	{
		if( $iduser == 0)
			return false;
		$db = $GLOBALS['babDB'];
		$res=$db->db_query("select * from ".BAB_USERS_LOG_TBL." where id_user='".$iduser."'");
		if( $res && $db->db_num_rows($res) > 0)
			return true;		
		return false;
	}
}

/* for current user */
function bab_userIsloggedin()
	{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;

	if (isset($BAB_SESS_LOGGED))
		{
		return $BAB_SESS_LOGGED;
		}
	if (!empty($BAB_SESS_NICKNAME) && !empty($BAB_SESS_HASHID))
		{
		$hash=md5($BAB_SESS_NICKNAME.$BAB_HASH_VAR);
		if ($hash == $BAB_SESS_HASHID)
			{
			$BAB_SESS_LOGGED=true;
			}
		else
			{
			$BAB_SESS_LOGGED=false;
			}
		}
	else
		{
		$BAB_SESS_LOGGED=false;
		}
    return $BAB_SESS_LOGGED;
	}

function bab_getUserName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_composeUserName($arr['firstname'], $arr['lastname']);
		}
	else
		{
		return "";
		}
	}

function bab_getUserEmail($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select email from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['email'];
		}
	else
		{
		return "";
		}
	}

function bab_getUserNickname($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select nickname from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['nickname'];
		}
	else
		{
		return "";
		}
	}

function bab_getUserSetting($id, $what)
	{
	$db = $GLOBALS['babDB'];
	$query = "select ".$what." from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[$what];
		}
	else
		{
		return "";
		}
	}

function bab_isUserVacationApprover($groupid = 0)
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	if( $groupid == 0)
		$query = "select id from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_object='$BAB_SESS_USERID' or supplier='$BAB_SESS_USERID'";
	else
		$query = "select id from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_object='$BAB_SESS_USERID'  or supplier='$BAB_SESS_USERID' and id_group='$groupid'";

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


function bab_isUserUseVacation($iduser)
	{
	$db = $GLOBALS['babDB'];
	$query = "select id_group from ".BAB_USERS_GROUPS_TBL." where id_object='$iduser' and isprimary='Y'";
	$res = $db->db_query($query);

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select id from ".BAB_GROUPS_TBL." where id='".$arr['id_group']."' and vacation='Y'";
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

function bab_getGroupName($id)
	{
	switch( $id )
		{
		case 1:
			return bab_translate("Registered users");
		case 2:
			return bab_translate("Unregistered users");
		default:
			$db = $GLOBALS['babDB'];
			$query = "select name from ".BAB_GROUPS_TBL." where id='$id'";
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

function bab_getPrimaryGroupId($userid)
	{
	$db = $GLOBALS['babDB'];
	$query = "select id_group from ".BAB_USERS_GROUPS_TBL." where id_object='$userid' and isprimary='Y'";
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
function bab_mailAccessLevel()
	{
	$db = $GLOBALS['babDB'];

	$bemail = 0;
	$req = "select * from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object='".$GLOBALS['BAB_SESS_USERID']."' and mail='Y' and ".BAB_GROUPS_TBL.".id = ".BAB_USERS_GROUPS_TBL.".id_group";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0 )
		$bemail = 1;

	$req = "select id from ".BAB_GROUPS_TBL." where manager='".$GLOBALS['BAB_SESS_USERID']."' and mail='Y'";
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

function bab_fileManagerAccessLevel()
	{
	global $BAB_SESS_USERID;
	$db = $GLOBALS['babDB'];
	$aret = array();
	$badmin = bab_isUserAdministrator();

	$req = "select * from ".BAB_GROUPS_TBL." where id=2 and (ustorage ='Y' or gstorage ='Y')";
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
		$req = "select * from ".BAB_GROUPS_TBL." where id=1 and (ustorage ='Y' or gstorage ='Y')";
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

		$req = "select ".BAB_GROUPS_TBL.".id, ".BAB_GROUPS_TBL.".gstorage, ".BAB_GROUPS_TBL.".ustorage from ".BAB_GROUPS_TBL." join ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."' and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_GROUPS_TBL.".manager !='".$BAB_SESS_USERID."' and (".BAB_GROUPS_TBL.".ustorage ='Y' or ".BAB_GROUPS_TBL.".gstorage ='Y')";
		$res = $db->db_query($req);
		while( $arr = $db->db_fetch_array($res))
			{
			$aret['id'][] = $arr['id'];
			$aret['pu'][] = $arr['gstorage'] == "Y"? 1: 0;
			$aret['pr'][] = $arr['ustorage'] == "Y"? 1: 0;
			$aret['ma'][] = 0;
			}


		$req = "select id, gstorage, ustorage from ".BAB_GROUPS_TBL." where manager='".$BAB_SESS_USERID."' and gstorage='Y'";
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

function bab_getUserId( $name )
	{
	$replace = array( " " => "", "-" => "");
	$db = $GLOBALS['babDB'];
	$hash = md5(strtolower(strtr($name, $replace)));
	$query = "select id from ".BAB_USERS_TBL." where hashname='".$hash."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['id'];
		}
	else
		return 0;
	}

function bab_getUserGroups($id = "")
	{
	$arr = array();
	if( empty($id))
		$id = $GLOBALS['BAB_SESS_USERID'];
	if( !empty($id))
		{
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select ".BAB_GROUPS_TBL.".id, ".BAB_GROUPS_TBL.".name from ".BAB_USERS_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object=".$id." and ".BAB_GROUPS_TBL.".id=".BAB_USERS_GROUPS_TBL.".id_group");
		if( $res && $db->db_num_rows($res) > 0 )
			{
			while( $r = $db->db_fetch_array($res))
				{
				$arr['id'][] = $r['id'];
				$arr['name'][] = $r['name'];
				}
			}
		}
	return $arr;
	}

function bab_replace( $txt )
{
	$db = $GLOBALS['babDB'];
	$artarray = array("ARTICLEPOPUP", "ARTICLE");
	for( $i = 0; $i < count($artarray); $i++)
	{
	$reg = "/\\\$".$artarray[$i]."\((.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			unset($idtopic);
			$tab = preg_split("/(\"[\s]*,)|(,[\s]*\")|(\"[\s]*,[\s]*\")+/", $m[1][$k]);
			if( sizeof($tab) > 1)
				{
				$topic = trim($tab[0]);
				if( $topic[0] == '"' )
					$topic = substr($topic, 1);
				if( $topic[strlen($topic)-1] == '"' )
					$topic = substr($topic, 0, -1);

				$article = trim($tab[1]);
				if( $article[0] == '"' )
					$article = substr($article, 1);
				if( $article[strlen($article)-1] == '"' )
					$article = substr($article, 0, -1);
				$req = "select * from ".BAB_TOPICS_TBL." where category='".addslashes($topic)."'";
				$res = $db->db_query($req);
				if( $res && $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$idtopic = $arr['id'];
					}
				}
			else
				{
				$tab = preg_split("/[,]+/", $m[1][$k]);
				if( sizeof( $tab ) > 1 )
					{
					$article = trim($tab[1]);
					$req = "select * from ".BAB_TOPICS_TBL." where category='".addslashes(trim($tab[0]))."'";
					$res = $db->db_query($req);
					if( $res && $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$idtopic = $arr['id'];
						}
					}
				else
					{
					$article = trim($m[1][$k]);
					}
				}

			if( isset($idtopic))
				$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$idtopic."' and title= '".addslashes($article)."'";
			else
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where title like '%".addslashes($article)."%'";
				}

			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']))
					{
					if( $i == 0 )
						$txt = preg_replace("/\\\$".$artarray[$i]."\(".preg_quote($m[1][$k])."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=articles&idx=viewa&article=".$arr['id']."', 'Article', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$arr['title']."</a>", $txt);
					else
						$txt = preg_replace("/\\\$".$artarray[$i]."\(".preg_quote($m[1][$k])."\)/", "<a href=\"".$GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id']."&topics=".$arr['id_topic']."\">".$arr['title']."</a>", $txt);
					}
				else
					$txt = preg_replace("/\\\$".$artarray[$i]."\(".preg_quote($m[1][$k])."\)/", $arr['title'], $txt);
				}
			}
		}
	}

	$reg = "/\\\$CONTACT\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_CONTACTS_TBL." where  owner='".$GLOBALS['BAB_SESS_USERID']."' and firstname like '%".addslashes(trim($m[1][$k]))."%' and lastname like '%".addslashes(trim($m[2][$k]))."%'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$txt = preg_replace("/\\\$CONTACT\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k])."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=contact&idx=modify&item=".$arr['id']."&bliste=0', 'Contact', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$m[1][$k]." ".$m[2][$k]."</a>", $txt);
				}
			else
				$txt = preg_replace("/\\\$CONTACT\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k])."\)/", $m[1][$k]." ".$m[2][$k], $txt);
			}
		}

	$reg = "/\\\$FAQ\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_FAQCAT_TBL." where category='".addslashes(trim($m[1][$k]))."'";
			$res = $db->db_query($req);
			$repl = false;
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $arr['id']))
					{
					$req = "select * from ".BAB_FAQQR_TBL." where question='".addslashes(trim($m[2][$k]))."'";
					$res = $db->db_query($req);
					if( $res && $db->db_num_rows($res) > 0)
						{
						$arr = $db->db_fetch_array($res);
						$txt = preg_replace("/\\\$FAQ\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k])."\)/", "<a href=\"javascript:Start('".$GLOBALS['babUrlScript']."?tg=faq&idx=viewpq&item=".$arr['id']."', 'Faq', 'width=550,height=550,status=no,resizable=yes,top=200,left=200,scrollbars=yes');\">".$m[2][$k]."</a>", $txt);
						$repl = true;
						}
					}
				}
			if( $repl == false )
				$txt = preg_replace("/\\\$FAQ\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k])."\)/", $m[2][$k], $txt);
			}
		}


	$reg = "/\\\$FILE\((.*?),(.*?)\)/";
	if( preg_match_all($reg, $txt, $m))
		{
		include $GLOBALS['babInstallPath']."utilit/fileincl.php";
		for ($k = 0; $k < count($m[1]); $k++ )
			{
			$req = "select * from ".BAB_FILES_TBL." where id='".trim($m[1][$k])."' and state='' and confirmed='Y'";
			$res = $db->db_query($req);
			$access = false;
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				$access = bab_isAccessFileValid($arr['bgroup'], $arr['id_owner']);
				}

			$urltxt = trim($m[2][$k]);
			if( empty($urltxt) && $access )
				$urltxt = $arr['name'];

			if( $access )
				{
				$txt = preg_replace("/\\\$FILE\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k], "/")."\)/", "<a href=\"".$GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".$arr['path']."&file=".$arr['name']."\">".$urltxt."</a>", $txt);
				}
			else
				$txt = preg_replace("/\\\$FILE\(".preg_quote($m[1][$k]).",".preg_quote($m[2][$k], "/")."\)/", $urltxt, $txt);
			}
		}

	return $txt;
}
?>