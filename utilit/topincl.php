<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";

class categoriesHierarchy
{

	var $parentscount;
	var $parentname;
	var $parenturl;
	var $burl;
	var $topics;
	var $topictitle;

	function categoriesHierarchy($topics,$cat,$link)
		{
		global $babBody, $babDB;
		$this->link = $link;

		if( $topics != 0 )
			{
			$res = $babDB->db_query("select id_cat, category from ".BAB_TOPICS_TBL." where id='".$topics."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$cat = $arr['id_cat'];
				$this->arrparents[] = array($topics, $arr['category']);
				}
			else
				{
				$cat = 0;
				}		
			}
		else
			{
			$topics = 0;
			}

		if( $cat == -1)
			{
			$cat = 0;
			}

		$this->topics = $topics;
		$this->cat = $cat;
		if( isset($babBody->topcats[$cat]) )
			{
			$this->arrparents[] = array($cat, $babBody->topcats[$cat]['title']);
			while( $babBody->topcats[$cat]['parent'] != 0 )
			{
				$this->arrparents[] = array($babBody->topcats[$cat]['parent'],$babBody->topcats[$babBody->topcats[$cat]['parent']]['title']);
				$cat = $babBody->topcats[$cat]['parent'];
			}
			}
		$this->arrparents[] = array(0, bab_translate("Top"));

		$this->parentscount = count($this->arrparents);
		$this->arrparents = array_reverse($this->arrparents);
		}

	function getnextparent()
		{
		global $babBody;

		static $i = 0;
		if( $i < $this->parentscount)
			{
			if( $i == ($this->parentscount - 1))
				{
				$this->parenturl = "";
				$this->burl = false;
				}
			else
				{
				$this->burl = true;
				$this->parenturl = $this->link."&cat=".$this->arrparents[$i][0];
					}
			$this->parentname = $this->arrparents[$i][1];
			$i++;
			return true;
			}
		else
			{ 
			$i = 0;
			return false;
			}
		}
}



function viewCategoriesHierarchy($topics)
	{
	global $babBody;
	class tempvch extends categoriesHierarchy
		{

		function tempvch($topics)
			{
			$this->categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
			}
		}

	$temp = new tempvch($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "categorieshierarchy"));
	}

class tempvch_txt extends categoriesHierarchy
	{

	function tempvch_txt($topics)
		{
		$this->categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
		}
	}

function viewCategoriesHierarchy_txt($topics)
	{
	global $babBody;

	$temp = new tempvch_txt($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
	return bab_printTemplate($temp,"articles.html", "categorieshierarchy_txt");
	}


function bab_getCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select category from ".BAB_TOPICS_TBL." where id='$id'";
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

function bab_getCategoryDescription($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select description from ".BAB_TOPICS_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['description'];
		}
	else
		{
		return "";
		}
	}

function bab_getTopicCategoryTitle($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='$id'";
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

function bab_getTopicCategoryDescription($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select description from ".BAB_TOPICS_CATEGORIES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['description'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleTitle($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select title from ".BAB_ARTICLES_TBL." where id='$article'";
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

// used in add-ons since 4.09
function bab_getArticleArray($article,$fullpath = false)
	{
	$db = $GLOBALS['babDB'];
	$query = "select a.*,t.category topic from ".BAB_ARTICLES_TBL." a,".BAB_TOPICS_TBL." t where a.id='".$article."' AND t.id=a.id_topic";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		if ($fullpath) $arr['CategoriesHierarchy'] = viewCategoriesHierarchy_txt($arr['id_topic']);
		return $arr;
		}
	else
		{
		return array();
		}
	}

function bab_getArticleDate($article)
	{
	$db = $GLOBALS['babDB'];
	$query = "select date from ".BAB_ARTICLES_TBL." where id='$article'";
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
	$query = "select id_author from ".BAB_ARTICLES_TBL." where id='$article'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='".$arr['id_author']."'";
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
	$query = "select subject from ".BAB_COMMENTS_TBL." where id='$com'";
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
?>