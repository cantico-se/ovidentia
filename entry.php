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
* @internal SEC1 NA 05/12/2006 FULL
*/
include_once 'base.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';

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
			global $babBody, $babDB;
			$this->idgroup = $idgroup;
			$req = "select at.id, at.id_topic ,at.id_author, at.date, at.date_modification, at.title, at.head , LENGTH(at.body) as blen, at.restriction   from ".BAB_HOMEPAGES_TBL." ht left join ".BAB_ARTICLES_TBL." at on ht.id_article=at.id where ht.id_group='".$babDB->db_escape_string($idgroup)."' and ht.id_site='".$babDB->db_escape_string($babBody->babsite['id'])."'  and (at.date_publication='0000-00-00 00:00:00' or at.date_publication <= now()) and ht.ordering!='0' order by ht.ordering asc";
			$this->res = $babDB->db_query($req);
			$this->countres = $babDB->db_num_rows($this->res);
			$this->morename = bab_translate("Read More");
			$this->printable = bab_translate("Print Friendly");
			$this->attachmentxt = bab_translate("Associated documents");
			}

		function getnext(&$skip)
			{
			global $babDB, $new; 
			static $i = 0;
			if( $i < $this->countres)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( $arr['restriction'] != '' && !bab_articleAccessByRestriction($arr['restriction']))
					{
					$skip = true;
					$i++;
					return true;
					}
				$this->blen = $arr['blen'];
				if( $arr['blen'] == 0 )
					{
					$GLOBALS['babWebStat']->addArticle($arr['id']);
					}
				$this->title = bab_toHtml($arr['title']);
				
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($arr['head']);
				$this->content = $editor->getHtml();
				
				if( $arr['id_author'] != 0 && (($author = bab_getUserName($arr['id_author'])) != ""))
					{
					$this->articleauthor = bab_toHtml($author);
					}
				else
					{
					$this->articleauthor = bab_translate("Anonymous");
					}
				$this->articledate = bab_toHtml(bab_strftime(bab_mktime($arr['date_modification'])));
				$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;
				$this->moreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=entry&idx=more&article=".$arr['id']."&idg=".$this->idgroup);
				$this->printurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=entry&idx=print&topics=".$arr['id_topic']."&article=".$arr['id']);

				$this->resf = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($arr['id'])."' order by ordering asc");
				$this->countf = $babDB->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=entry&idx=getf&idf=".$arr['id']."&article=".$arr['id_article']."&idg=".$this->idgroup);
				$this->docname = bab_toHtml($arr['name']);
				$this->docdesc = bab_toHtml($arr['description']);
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
	
	$temp = new temp($idgroup);
	$babBody->babecho(	bab_printTemplate($temp,"entry.html", "homepage0"));
	}

function readMore($article, $idg)
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

		function temp($article, $idg)
			{
			global $babDB;
			$this->idgroup = $idg;
			$req = "select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
			$this->res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($this->res);
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
			$editor = new bab_contentEditor('bab_article_body');
			if( empty($arr['body']))
				{
				$editor->setContent($arr['head']);
				}
			else
				{
				$editor->setContent($arr['body']);
				}
			$this->content = $editor->getHtml();
			
			$this->title = bab_toHtml($arr['title']);
			if( $arr['id_author'] != 0 && (($author = bab_getUserName($arr['id_author'])) != ""))
				{
				$this->articleauthor = bab_toHtml($author);
				}
			else
				{
				$this->articleauthor = bab_toHtml(bab_translate("Anonymous"));
				}
			$this->articledate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->author = bab_translate("by") . " ". $this->articleauthor. " - ". $this->articledate;

			$this->resf = $babDB->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$babDB->db_escape_string($article)."' order by ordering asc");
			$this->countf = $babDB->db_num_rows($this->resf);

			if( $this->countf > 0 )
				{
				$this->attachmentxt = bab_translate("Associated documents");
				$this->battachments = true;
				}
			else
				{
				$this->battachments = false;
				}
			$GLOBALS['babWebStat']->addArticle($article);
			}

		function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=entry&idx=getf&idf=".$arr['id']."&article=".$arr['id_article']."&idg=".$this->idgroup);
				$this->docname = bab_toHtml($arr['name']);
				$this->docdesc = bab_toHtml($arr['description']);
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
	
	$temp = new temp($article, $idg);
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
		var $sContent;
		
		function temp($topics, $article)
			{
			global $babDB;
			$req			= "select * from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
			$this->res		= $babDB->db_query($req);
			$this->count	= $babDB->db_num_rows($this->res);
			$this->topics	= $topics;
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			
			if( $this->count > 0 )
				{
				$GLOBALS['babWebStat']->addArticle($article);
				$this->arr = $babDB->db_fetch_array($this->res);
				
				
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
					
				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['head']);
				$this->head = $editor->getHtml();
				
				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['body']);
				$this->content = $editor->getHtml();
				
				$this->title = bab_toHtml($this->arr['title']);
				$this->url = "<a href=\"".bab_toHtml($GLOBALS['babUrl'])."\">".bab_toHtml($GLOBALS['babSiteName'])."</a>";
				}
			}
		}
	
	$temp = new temp($topics, $article);
	echo bab_printTemplate($temp,"articleprint.html");
	}


function getDocumentArticle($idf, $article)
{
	global $babDB;
	$arr = $babDB->db_fetch_array($babDB->db_query("select id_article from ".BAB_ART_FILES_TBL." where id='".$babDB->db_escape_string($idf)."'"));

	$access = false;
	if( $arr['id_article'] == $article )
		{
		$access = true;
		}

	if( !$access )
	{
		echo bab_translate("Access denied");
	}
	else
	{
		bab_getDocumentArticle($idf);
	}
}


function isAccessValid($article, $idg)
{
	global $babBody, $babDB;
	$access = false;

	if( !bab_articleAccessById($article))
		return $access;

	$res = $babDB->db_query("select * from ".BAB_HOMEPAGES_TBL." where id_group='".$babDB->db_escape_string($idg)."' and id_site='".$babDB->db_escape_string($babBody->babsite['id'])."' and id_article='".$babDB->db_escape_string($article)."' and ordering!='0'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}
	return $access;
}

/* main */
$idx = bab_rp('idx', 'list');
$idg = bab_rp('idg', 2); // non registered users

if( $BAB_SESS_LOGGED)
	{
	$idg = 1; // registered users
	}

switch($idx)
	{
	case "getf":
		$article = bab_rp('article');
		if( !isAccessValid($article, $idg) )
			{
			$babBody->msgerror = bab_translate("Access denied");
			return;
			}
		else
			{
			$idf = bab_rp('idf');
			getDocumentArticle($idf, $article);
			exit;
			}
		break;

	case "print":
		$article = bab_rp('article');
		if( !isAccessValid($article, $idg) )
			{
			$babBody->msgerror = bab_translate("Access denied");
			return;
			}
		else
			{
			$topics = bab_rp('topics');
			articlePrint($topics, $article);
			exit();
			}
		break;

	case "more":
		$article = bab_rp('article');
		if( !isAccessValid($article, $idg) )
			{
			$babBody->msgerror = bab_translate("Access denied");
			return;
			}
		else
			{
			readMore($article, $idg);
			$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=entry");
			$babBody->addItemMenu("more", bab_translate("Article"), $GLOBALS['babUrlScript']."?tg=entry&idx=more");
			}
		break;

	default:
	case "list":
		if( $idg == 1 )
			{
			$babBody->title = bab_translate("Private home page articles");
			}
		else
			{
			$babBody->title = bab_translate("Public home page articles");
			}
		$babBody->addItemMenu("list", bab_translate("List"), $GLOBALS['babUrlScript']."?tg=entry");
		listArticles($idg);
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>