<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function bab_getCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_TBL." where id='$id'";
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
	$query = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='$id'";
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
	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
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
	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
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
	$query = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from ".BAB_USERS_TBL." where id='".$arr['id_author']."'";
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
	$query = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
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
	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='$id'";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		// delete article and comments
		bab_confirmDeleteArticle($id, $arr['id']);
		}
	$req = "delete from BAB_".TOPICSCOM_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);
	
	$req = "delete from ".BAB_TOPICSSUB_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_TOPICS_TBL." where id='$id'";
	$res = $db->db_query($req);
	}

function bab_confirmDeleteArticle($topics, $article)
	{
	// delete comments
	$db = $GLOBALS['babDB'];
	$req = "delete from ".BAB_COMMENTS_TBL." where id_article='$article'";
	$res = $db->db_query($req);

	$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$article."'";
	$res = $db->db_query($req);

	// delete article
	$req = "delete from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($req);
	}

function bab_deleteComments($com)
	{
	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_COMMENTS_TBL." where id_parent='$com'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res))
		{
		while( $arr = $db->db_fetch_array($res))
			{
			bab_deleteComments($arr['id']);
			}
		}
	$req = "delete from ".BAB_COMMENTS_TBL." where id='$com'";
	$res = $db->db_query($req);	
	}
?>