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
/**
* @internal SEC1 NA 08/12/2006 FULL
*/
include_once 'base.php';

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
			$res = $babDB->db_query("select id_cat, category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'");
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
		$topcats = $babBody->get_topcats();
		if( isset($topcats[$cat]) )
			{
			$this->arrparents[] = array($cat, $topcats[$cat]['title']);
			while( $topcats[$cat]['parent'] != 0 )
			{
				$this->arrparents[] = array($topcats[$cat]['parent'],$topcats[$topcats[$cat]['parent']]['title']);
				$cat = $topcats[$cat]['parent'];
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
				$this->parenturl = bab_toHtml($this->link."&cat=".$this->arrparents[$i][0]);
				}
			$this->parentname = bab_toHtml($this->arrparents[$i][1]);
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
	global $babDB;
	$query = "select category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['category'];
		}
	else
		{
		return "";
		}
	}

function bab_getCategoryDescription($id)
	{
	global $babDB;
	$query = "select description from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['description'];
		}
	else
		{
		return "";
		}
	}

function bab_getTopicCategoryTitle($id)
	{
	global $babDB;
	$query = "select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

function bab_getTopicCategoryDescription($id)
	{
	global $babDB;
	$query = "select description from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['description'];
		}
	else
		{
		return "";
		}
	}

function bab_getArticleTitle($article)
	{
	global $babDB;
	$query = "select title from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
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
	global $babDB;
	$query = "select a.*,t.category topic from ".BAB_ARTICLES_TBL." a,".BAB_TOPICS_TBL." t where a.id='".$babDB->db_escape_string($article)."' AND t.id=a.id_topic";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
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
	global $babDB;
	$query = "select date from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return bab_strftime(bab_mktime($arr['date']));
		}
	else
		{
		return "";
		}
	}

function bab_getArticleAuthor($article)
	{
	global $babDB;
	$query = "select id_author from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($arr['id_author'])."'";
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
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
	global $babDB;
	$query = "select subject from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['subject'];
		}
	else
		{
		return "";
		}
	}


function bab_addTopicsCategory($name, $description, $benabled, $template, $disptmpl, $topcatid, $dgowner=0)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='".$babDB->db_escape_string($name)."' and id_parent='".$babDB->db_escape_string($topcatid)."' and id_dgowner='".$babDB->db_escape_string($dgowner)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This topic category already exists");
		return false;
		}
	else
		{
		$req = "insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled, template, id_dgowner, id_parent, display_tmpl) VALUES (
		'" .$babDB->db_escape_string($name). "',
		'" . $babDB->db_escape_string($description). "',
		'" . $babDB->db_escape_string($benabled). "', 
		'" . $babDB->db_escape_string($template). "',
		'" . $babDB->db_escape_string($dgowner). "', 
		'" . $babDB->db_escape_string($topcatid). "', 
		'" . $babDB->db_escape_string($disptmpl). "'
		)";
		$babDB->db_query($req);

		$id = $babDB->db_insert_id();
		$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_TOPICS_CATEGORIES_TBL." tc where so.position='0' and so.type='3' and tc.id=so.id_section and tc.id_dgowner='".$babDB->db_escape_string($dgowner)."'";
		$res = $babDB->db_query($req);
		$arr = $babDB->db_fetch_array($res);
		if( empty($arr[0]))
			{
			$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so where so.position='0'";
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			if( empty($arr[0]))
				$arr[0] = 0;
			}
		$babDB->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering=ordering+1 where position='0' and ordering > '".$babDB->db_escape_string($arr[0])."'");
		$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$babDB->db_escape_string($id). "', '0', '3', '" . $babDB->db_escape_string(($arr[0]+1)). "')";
		$babDB->db_query($req);

		$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($topcatid)."'");
		$arr = $babDB->db_fetch_array($res);
		if( isset($arr[0]))
			$ord = $arr[0] + 1;
		else
			$ord = 1;
		$babDB->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$babDB->db_escape_string($id). "', '1', '" . $babDB->db_escape_string($ord). "', '".$babDB->db_escape_string($topcatid)."')");

		/* update default rights */
		include_once $GLOBALS['babInstallPath'].'admin/acl.php';
		aclCloneRights(BAB_DEF_TOPCATVIEW_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATVIEW_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATSUB_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATSUB_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATCOM_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATCOM_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATMOD_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATMOD_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATMAN_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATMAN_GROUPS_TBL, $id);		
		return $id;
		}
	}

?>