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
include "base.php";
include $babInstallPath."utilit/topincl.php";

class categoriesHierarchyPopup
{
	var $parentscount;
	var $parentname;
	var $parenturl;
	var $burl;
	var $topics;

	function categoriesHierarchyPopup($topics,$cat,$link)
		{
		global $babDB;
		$this->link = $link;
		if ($topics!=0 || $cat!=0) $this->arrparents[] = $topics;
		if ($cat == -1)
			list($cat) = $babDB->db_fetch_row($babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id='".$topics."'"));
		$this->topics = $topics;
		$this->cat = $cat;
		$this->arrparents[] = $cat;
		$res = $babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'");
		while($arr = $babDB->db_fetch_array($res))
			{
			if( $arr['id_parent'] == 0 )
				break;
			$this->arrparents[] = $arr['id_parent'];
			$res = $babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$arr['id_parent']."'");
			}
		$this->arrparents[] = 0;
		$this->parentscount = count($this->arrparents);
		$this->arrparents = array_reverse($this->arrparents);
		}

	function getnextparent()
		{
		static $i = 0;
		if( $i < $this->parentscount )
			{
			if( $i == $this->parentscount - 1 )
				{
				$this->parentname = bab_getCategoryTitle($this->arrparents[$i]);
				$this->parenturl = "";
				$this->burl = false;
				}
			else
				{
				$this->burl = true;
				if( $this->arrparents[$i] == 0 )
					$this->parentname = bab_translate("Top");
				else
					$this->parentname = bab_getTopicCategoryTitle($this->arrparents[$i]);
				$this->parenturl = $this->link."&cat=".$this->arrparents[$i];
				}
			$i++;
			return true;
			}
		else
			return false;
		}
}



function browse($topics,$cat,$cb)
	{
	global $babBody, $babDB;

	class temp extends categoriesHierarchyPopup
		{
	
		var $db;
		var $count;
		var $res;

		function temp($topics,$cat,$cb)
			{
			
			$this->categoriesHierarchyPopup($topics,$cat,$GLOBALS['babUrlScript']."?tg=editorarticle&cb=".$cb);
			$this->db = $GLOBALS['babDB'];

			$this->cat = $cat;
			$this->topics = $topics;
			$this->cb = "".$cb;

			$reqcat = "select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$cat."'";
			$this->rescat = $this->db->db_query($reqcat);
			$this->countcat = $this->db->db_num_rows($this->rescat);
			
			$reqtop = "select id,category from ".BAB_TOPICS_TBL." where id_cat='".$cat."'";
			$this->restop = $this->db->db_query($reqtop);
			$this->counttop = $this->db->db_num_rows($this->restop);

			$req = "select id, id_topic, id_author, date, title, head from ".BAB_ARTICLES_TBL." where id_topic='$topics' and confirmed='Y' order by date desc";
			$this->resart = $this->db->db_query($req);
			$this->countarticles = $this->db->db_num_rows($this->resart);
			
			$this->target_txt = bab_translate("popup");
			}

		function getnextcat()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->countcat)
				{
				$arr = $this->db->db_fetch_array($this->rescat);
				if (in_array($arr['id'],$babBody->topcatview))
					{
					$this->displaycat = true;
					$this->title = bab_getTopicCategoryTitle($arr['id']);
					$this->url = $GLOBALS['babUrlScript']."?tg=editorarticle&idx=browse&cat=".$arr['id']."&cb=".$this->cb;
					}
				else
					{
					$this->displaycat = false;
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnexttop()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->counttop)
				{
				$arr = $this->db->db_fetch_array($this->restop);
				if (in_array($arr['id'],$babBody->topview) && $this->topics == 0 )
					{
					$this->displaytop = true;
					$this->title = strip_tags($arr['category']);
					$this->url = $GLOBALS['babUrlScript']."?tg=editorarticle&idx=browse&topics=".$arr['id']."&cat=".$this->cat."&cb=".$this->cb;
					}
				else
					{
					$this->displaytop = false;
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextarticle()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->countarticles)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				if (in_array($arr['id_topic'],$babBody->topview))
					{
					$this->display = true;
					if( $arr['id_author'] != 0 && (($author = bab_getUserName($arr['id_author'])) != ""))
						$this->articleauthor = $author;
					else
						$this->articleauthor = bab_translate("Anonymous");
					$this->articledate = bab_strftime(bab_mktime($arr['date']));
					$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;

					$tmp = str_replace("\n"," ",addslashes(substr(strip_tags(bab_replace($arr['head']))."...", 0, 400)." -- ".$this->author));
					$this->content = str_replace("\r"," ",$tmp);
					$this->titledisp = $arr['title'];
					$tmp = str_replace("\""," ",$arr['title']);
					$this->title = addslashes($tmp);
					$this->articleid = $arr['id'];
					}
				else
					{
					$this->display = false;
					}
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($topics,$cat,$cb);
	echo bab_printTemplate($temp,"editorarticle.html", "editorarticle");
	}

if(!isset($cat))
	{
	$cat = 0;
	}

if(!isset($topics))
	{
	$topics = 0;
	}

if(!isset($cb))
	{
	$cb = "EditorOnInsertArticle";
	}

switch($idx)
	{
	default:
	case "browse":
		browse($topics,$cat,$cb);
		exit;
	}
?>