<?php

function getForumName($id)
	{
	$db = new db_mysql();
	$query = "select * from forums where id='$id'";
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

function isForumModerated($forum)
	{
	$db = new db_mysql();
	$query = "select * from forums where id='$forum'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr[moderation] == "Y")
			return true;
		else
			return false;
		}
	return false;
	}

function isThreadOpen($thread)
	{
	$db = new db_mysql();
	$query = "select * from threads where id='$thread'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if( $arr[active] == "Y")
			return true;
		else
			return false;
		}
	return false;
	}

function getThreadTitle($id)
	{
	$db = new db_mysql();
	$query = "select * from threads where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from posts where id='".$arr[post]."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return $arr[subject];
			}
		return "";
		}
	else
		{
		return "";
		}
	}

function isUserModerator($forum, $id)
	{
	if( empty($forum) || empty($id))
		return false;

	$db = new db_mysql();
	$query = "select * from forums where id='$forum' and moderator='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		return true;
		}
	return false;
	}
?>