<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function getCategoryTitle($id)
	{
	$db = new db_mysql();
	$query = "select * from topics where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[category];
		}
	else
		{
		return "";
		}
	}

function getArticleTitle($article)
	{
	$db = new db_mysql();
	$query = "select * from articles where id='$article'";
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

function getArticleDate($article)
	{
	$db = new db_mysql();
	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return bab_strftime(bab_mktime($arr[date]));
		}
	else
		{
		return "";
		}
	}

function getArticleAuthor($article)
	{
	$db = new db_mysql();
	$query = "select * from articles where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from users where id='".$arr[id_author]."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return composeName($arr[firstname], $arr[lastname]);
			}
		else
			return babTranslate("Anonymous");
		}
	else
		{
		return babTranslate("Anonymous");
		}
	}

function getCommentTitle($com)
	{
	$db = new db_mysql();
	$query = "select * from comments where id='$com'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr[subject];
		}
	else
		{
		return "";
		}
	}

function confirmDeleteCategory($id)
	{

	$db = new db_mysql();
	$req = "select * from articles where id_topic='$id'";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		// delete article and comments
		confirmDeleteArticle($id, $arr[id]);
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

function confirmDeleteArticle($topics, $article)
	{
	// delete comments
	$db = new db_mysql();
	$req = "delete from comments where id_article='$article'";
	$res = $db->db_query($req);

	$req = "delete from homepages where id_article='".$article."'";
	$res = $db->db_query($req);

	// delete article
	$req = "delete from articles where id='$article'";
	$res = $db->db_query($req);
	}

function deleteComments($com)
	{
	$db = new db_mysql();
	$req = "select * from comments where id_parent='$com'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res))
		{
		while( $arr = $db->db_fetch_array($res))
			{
			deleteComments($arr[id]);
			}
		}
	$req = "delete from comments where id='$com'";
	$res = $db->db_query($req);	
	}

function locateArticle( $txt )
{
	$reg = "/\\\$ARTICLE\((.*?)\)/";
	preg_match_all($reg, $txt, $m);

	$db = new db_mysql();
	for ($k = 0; $k < count($m[1]); $k++ )
		{
		$req = "select * from articles where title like '%".addslashes(trim($m[1][$k]))."%'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$txt = preg_replace("/\\\$ARTICLE\(".$m[1][$k]."\)/", "<a href=\"".$GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$arr[id_topic]."&article=".$arr[id]."\">".$arr[title]."</a>", $txt);
			}
		}
	return $txt;
}
?>