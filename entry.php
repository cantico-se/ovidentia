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
include_once $babInstallPath."utilit/topincl.php";

function ListArticles($idgroup)
	{
	global $babBody;

	class temp
		{	
		var $title;
		var $content;
		var $db;
		var $countres;
		var $res;
		var $author;
		var $moreurl;
		var $morename;
		var $blen;

		function temp($idgroup)
			{
			global $babBody;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_HOMEPAGES_TBL." where id_group='".$idgroup."' and id_site='".$babBody->babsite['id']."' and ordering!='0' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->countres = $this->db->db_num_rows($this->res);
			$this->morename = bab_translate("Read More");
			$this->printable = bab_translate("Print Friendly");
			}

		function getnext(&$skip)
			{
			global $new; 
			static $i = 0;
			if( $i < $this->countres)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select id, id_topic ,id_author, date, title, head , LENGTH(body) as blen, restriction  from ".BAB_ARTICLES_TBL." where id='".$arr['id_article']."'";
				$res = $this->db->db_query($req);
				$arr = $this->db->db_fetch_array($res);
				if( $arr['restriction'] != '' && !bab_articleAccessByRestriction($arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->blen = $arr['blen'];
				$this->title = $arr['title'];
				$this->content = bab_replace($arr['head']);
				if( $arr['id_author'] != 0 && (($author = bab_getUserName($arr['id_author'])) != ""))
					$this->articleauthor = $author;
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_strftime(bab_mktime($arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->moreurl = $GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id'];
				$this->printurl = $GLOBALS['babUrlScript']."?tg=entry&idx=print&topics=".$arr['id_topic']."&article=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($idgroup);
	$babBody->babecho(	bab_printTemplate($temp,"entry.html", "homepage0"));
	}

function readMore($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $db;
		var $count;
		var $res;
		var $title;
		var $author;

		function temp($article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->content = bab_replace($arr['body']);
				$this->title = $arr['title'];
				if( $arr['id_author'] != 0 && (($author = bab_getUserName($arr['id_author'])) != ""))
					$this->articleauthor = $author;
				else
					$this->articleauthor = bab_translate("Anonymous");
				$this->articledate = bab_strftime(bab_mktime($arr['date']));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($article);
	$babBody->babecho(	bab_printTemplate($temp,"entry.html", "readmore"));
	}

function articlePrint($topics, $article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $head;
		var $title;
		var $url;

		function temp($topics, $article)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->topics = $topics;
			if( $this->count > 0 )
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->head = bab_replace($this->arr['head']);
				$this->content = bab_replace($this->arr['body']);
				$this->title = $this->arr['title'];
				$this->url = "<a href=\"".$GLOBALS['babUrl']."\">".$GLOBALS['babSiteName']."</a>";
				}
			}
		}
	
	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
	}

function isAccessValid($article, $idg)
{
	global $babBody;
	$access = false;
	$db = $GLOBALS['babDB'];

	if( !bab_articleAccessById($article))
		return $access;

	$res = $db->db_query("select * from ".BAB_HOMEPAGES_TBL." where id_group='".$idg."' and id_site='".$babBody->babsite['id']."' and id_article='".$article."' and ordering!='0'");
	if( $res && $db->db_num_rows($res) > 0 )
		{
		$access = true;
		}
	return $access;
}

/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

if(!isset($idg))
	{
	$idg = 2; // non registered users
	}

if( $BAB_SESS_LOGGED)
	$idg = 1; // registered users

switch($idx)
	{
	case "print":
		if( !isAccessValid($article, $idg) )
			{
				$babBody->msgerror = bab_translate("Access denied");
				return;
			}
		else
			{
			articlePrint($topics, $article);
			exit();
			}
		break;

	case "more":
		if( !isAccessValid($article, $idg) )
			{
				$babBody->msgerror = bab_translate("Access denied");
				return;
			}
		else
			{
			readMore($article);
			$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=entry");
			$babBody->addItemMenu("more", bab_translate("Article"), $GLOBALS['babUrlScript']."?tg=entry&idx=more");
			}
		break;

	default:
	case "list":
		if( $idg == 1 )
			$babBody->title = bab_translate("Private home page articles");
		else
			$babBody->title = bab_translate("Public home page articles");
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=entry");
		listArticles($idg);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>