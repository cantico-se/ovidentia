<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function bab_getCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from topics where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}

function bab_getTopicCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from topics_categories where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleTitle($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleDate($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_strftime(bab_mktime($arr['date']));
		}
	else
		{
		return "";
		}
	}

function bab_getArticleAuthor($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from users where id='".$arr['id_author']."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return bab_composeUserName($arr['firstname'], $arr['lastname']);
			}
		else
			return bab_translate("Anonymous");
		}
	else
		{
		return bab_translate("Anonymous");
		}
	}

function bab_getCommentTitle($com)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from comments where id='$com'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['subject'];
		}
	else
		{
		return "";
		}
	}

function bab_confirmDeleteCategory($id)
	{

	$db = $GLOBALS['babDB'];
	$req = "select * from articles where id_topic='$id'";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		// delete article and comments
		bab_confirmDeleteArticle($id, $arr['id']);
		}
	$req = "delete from topicscom_groups where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from topicssub_groups where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from topicsview_groups where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from topics where id='$id'";
	$res = $db->db_query($req);
	}

function bab_confirmDeleteArticle($topics, $article)
	{
	// delete comments
	$db = $GLOBALS['babDB'];
	$req = "delete from comments where id_article='$article'";
	$res = $db->db_query($req);

	$req = "delete from homepages where id_article='".$article."'";
	$res = $db->db_query($req);

	// delete article
	$req = "delete from articles where id='$article'";
	$res = $db->db_query($req);
	}

function bab_deleteComments($com)
	{
	$db = $GLOBALS['babDB'];
	$req = "select * from comments where id_parent='$com'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res))
		{
		while( $arr = $db->db_fetch_array($res))
			{
			bab_deleteComments($arr['id']);
			}
		}
	$req = "delete from comments where id='$com'";
	$res = $db->db_query($req);	
	}
?>