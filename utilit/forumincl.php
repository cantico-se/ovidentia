<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function bab_getForumName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FORUMS_TBL." where id='$id'";
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

function bab_isForumModerated($forum)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FORUMS_TBL." where id='$forum'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['moderation'] == "Y")
			return true;
		else
			return false;
		}
	return false;
	}

function bab_isForumThreadOpen($thread)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_THREADS_TBL." where id='$thread'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr['active'] == "Y")
			return true;
		else
			return false;
		}
	return false;
	}

function bab_getForumThreadTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_THREADS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from ".BAB_POSTS_TBL." where id='".$arr['post']."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return $arr['subject'];
			}
		return "";
		}
	else
		{
		return "";
		}
	}

function bab_isUserForumModerator($forum, $id)
	{
	if( empty($forum) || empty($id))
		return false;

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_FORUMS_TBL." where id='$forum' and moderator='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return true;
		}
	return false;
	}
?>