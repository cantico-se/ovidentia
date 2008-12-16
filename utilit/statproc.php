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
* @internal SEC1 NA 11/12/2006 FULL
*/
include_once 'base.php';
define("OVSTAT_DEBUG", 1);
define("OVSTAT_LIMIT", 300);
define("OVSTAT_ROWS",  30000);

function bab_stat_debug($txt)
{
	global $statecho;

	if( !OVSTAT_DEBUG || !$statecho )
		return;
	echo $txt;
}


class bab_stats_base
{
	function bab_stats_base()
	{
		global $babDB;
	}

	function start()
	{
		
	}
	function process($datas)
	{

	}

	function terminate()
	{
		
	}
}

class bab_stats_pages extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_pages()
	{
		global $babDB;
		$res = $babDB->db_query("select * from ".BAB_STATS_IPAGES_TBL."");
		while( $arr = $babDB->db_fetch_array($res))
		{
			$this->pages[$arr['id']] = $arr['page_url'];
		}
	}

	function start()
	{
		bab_stat_debug("Pages start...<br>");	
	}

	function process($datas)
	{
		global $babDB;

		bab_stat_debug("Pages process...<br>");	
		foreach ($this->pages as $key => $val)
		{
			if (preg_match("/".preg_quote($val, '/')."/", $datas['url']))
			{
				if(!isset($this->results[$datas['date']][$datas['hour']][$key]))
					{
					$res = $babDB->db_query("select * from ".BAB_STATS_PAGES_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_page_id='".$babDB->db_escape_string($key)."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$arr = $babDB->db_fetch_array($res);
						$this->results[$datas['date']][$datas['hour']][$key] = $arr['st_hits'];
						}
					else
						{
						$babDB->db_query("insert into ".BAB_STATS_PAGES_TBL." (st_date, st_hour, st_hits, st_page_id) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($key)."')");
						$this->results[$datas['date']][$datas['hour']][$key] = 0;
						}				
					}

				$this->results[$datas['date']][$datas['hour']][$key]++;		
			}
		}
	}

	function terminate()
	{
		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_PAGES_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_page_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}


class bab_stats_modules extends bab_stats_base
{
	var $results = array();

	function bab_stats_modules()
		{	
		}

	function start()
		{
		bab_stat_debug("Modules start...<br>");	
		}

	function process($datas)
		{
		global $babDB, $babStatRefs;
		bab_stat_debug("Modules process...<br>");	

		$tg = $datas['tg'];
		if( empty($tg))
			{
			$mcount = count($datas['info']['bab_module']);
			if(  $mcount > 0 )
				{
				for( $i=0; $i < $mcount; $i++ )
					{
					if( !empty($datas['info']['bab_module'][$i]) )
						{
						$tg = $datas['info']['bab_module'][$i];
						break;
						}
					}
				}
			}

		switch($tg)
			{
			case "topman":
			case "topusr":
			case "articles":
			case "artedit":
			case "comments":
				$id = 2; /* articles */
				break;
			case "threads":
			case "posts":
			case "forumsuser":
				$id = 3; /* Forums */
				break;
			case "fileman":
			case "filever":
				$id = 4; /* Files Manager */
				break;
			case "faq":
				$id = 5; /* Faqs */
				break;
			case "calendar":
			case "calmonth":
			case "calweek":
			case "calday":
			case "event":
			case "month":
				$id = 8; /* Calendar */
				break;
			case "calview":
				$id = 9; /* Summary page */
				break;
			case "directory":
				$id = 10; /* Directories */
				break;
			case "search":
				$id = 11; /* Search */
				break;
			case "charts":
			case "chart":
			case "frchart":
			case "fltchart":
			case "flbchart":
				$id = 12; /* Charts */
				break;
			case "notes":
			case "note":
				$id = 13; /* Notes */
				break;
			case "contacts":
			case "contact":
				$id = 14; /* Contacts */
				break;
			case "sections":
			case "users":
			case "user":
			case "section":
			case "groups":
			case "group":
			case "profiles":
			case "admfaqs":
			case "admfaq":
			case "topcat":
			case "topcats":
			case "apprflow":
			case "admfms":
			case "admfm":
			case "topics":
			case "topic":
			case "forums":
			case "forum":
			case "admvacs":
			case "admvac":
			case "admcals":
			case "admcal":
			case "admocs":
			case "admoc":
			case "sites":
			case "site":
			case "addons":
			case "admdir":
			case "delegat":
			case "admstats":
			case "delegusr":
			case "maildoms":
			case "maildom":
			case "confcals":
			case "confcal":
			case "statproc":
			case "statconf":
			case "stat":
			case "admindex":
			case 'mailspool':
			case 'thesaurus':
			case 'admthesaurus':
				$id = 15; /* Administration */
				break;
			case "vacuser":
			case "vacchart":
			case "vacadm":
			case "vacadma":
			case "vacadmb":
				$id = 16;  /* Vacation */
				break;
			case "mail":
			case "mailopt":
			case "inbox":
			case "address":
				$id = 17; /* Mail */
				break;
			case "register":
			case "login":
				$id = 19; /* Login/Logout and registration  */
				break;
			case "calopt":
			case "sectopt":
			case "options":
				$id = 20; /* Options  */
				break;
			case "approb":
				$id = 21; /* Workflow  */
				break;
			case "htmlarea":
			case "editorovml":
			case "editorcontdir":
			case "selectcolor":
			case "editorfaq":
			case "editorarticle":
			case "editorfunctions":
				$id = 22; /* Editor  */
				break;
			case "oml":
				$id = 23; /* OvML  */
				break;
			case 'admTskMgr':
			case 'usrTskMgr':
				$id = 24; /* Task manager  */
				break;
			case "omlsoap":
				$id = 25; /* Web services */
				break;

			case "aclug":
			case "lusers":
			case "lsa":
			case "images":
			case "version":
			case "imgget":
			case "link":
				$id = 1; /* others */
				break;
			case "accden":
			case "entry":
				if( empty($datas['iduser']) )
					{
					$id = 7; // public home page
					}
				else
					{
					$id = 6; // private home page
					}
				break;
			default:
				if( empty($tg) ) // home pages
				{
					if( empty($datas['iduser']) )
					{
						$id = 7; // public home page
					}
					else
					{
						$id = 6; // private home page
					}
				}
				else  // others
				{
					$arr = explode("/", $tg);
					if( sizeof($arr) >= 3 && $arr[0] == "addon")
						{
						$id = 18; // addons
						}
					else
						{
						$id = 1;
						}
				}
				break;
			}

		$babStatRefs[$datas['session_id']] = $id;

		if(!isset($this->results[$datas['date']][$datas['hour']][$id]))
			{
			$res = $babDB->db_query("select * from ".BAB_STATS_MODULES_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_module_id='".$babDB->db_escape_string($id)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->results[$datas['date']][$datas['hour']][$id] = $arr['st_hits'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_MODULES_TBL." (st_date, st_hour, st_hits, st_module_id) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($id)."')");
				$this->results[$datas['date']][$datas['hour']][$id] = 0;
				}				
			}

		$this->results[$datas['date']][$datas['hour']][$id]++;		
		}

	function terminate()
		{
		global $babDB;
		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
			{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
				{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
					{
					$babDB->db_query("update ".BAB_STATS_MODULES_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_module_id= '".$babDB->db_escape_string($r3[0])."'");
					}
				}
			}
		}
}


class bab_stats_articles extends bab_stats_base
{
	var $pages = array();
	var $results = array();
	var $referents = array();

	function bab_stats_articles()
	{
	}

	function start()
	{
		bab_stat_debug("Articles start...<br>");	
	}

	function process($datas)
	{
		global $babDB, $babStatRefs;
		bab_stat_debug("Articles process...<br>");
		if( isset($datas['info']['bab_articles']))
		{
		for( $i = 0; $i < count($datas['info']['bab_articles']); $i++ )
			{
			if(!isset($this->results[$datas['date']][$datas['hour']][$datas['info']['bab_articles'][$i]]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_ARTICLES_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_article_id='".$babDB->db_escape_string($datas['info']['bab_articles'][$i])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_articles'][$i]] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_ARTICLES_TBL." (st_date, st_hour, st_hits, st_article_id) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($datas['info']['bab_articles'][$i])."')");
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_articles'][$i]] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_articles'][$i]]++;

			if( isset( $babStatRefs[$datas['session_id']]) )
				{
				if( !isset($this->referents[$datas['info']['bab_articles'][$i]][$babStatRefs[$datas['session_id']]]))
					{
					$res = $babDB->db_query("select * from ".BAB_STATS_ARTICLES_REF_TBL." where st_article_id='".$babDB->db_escape_string($datas['info']['bab_articles'][$i])."' and st_module_id='".$babDB->db_escape_string($babStatRefs[$datas['session_id']])."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$arr = $babDB->db_fetch_array($res);
						$this->referents[$datas['info']['bab_articles'][$i]][$babStatRefs[$datas['session_id']]] = $arr['st_hits'];
						}
					else
						{
						$babDB->db_query("insert into ".BAB_STATS_ARTICLES_REF_TBL." (st_article_id, st_module_id, st_hits) values ('".$babDB->db_escape_string($datas['info']['bab_articles'][$i])."','".$babDB->db_escape_string($babStatRefs[$datas['session_id']])."', '0')");
						$this->referents[$datas['info']['bab_articles'][$i]][$babStatRefs[$datas['session_id']]] = 0;
						}				
					}
				$this->referents[$datas['info']['bab_articles'][$i]][$babStatRefs[$datas['session_id']]]++;
				}
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_ARTICLES_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_article_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}	
		}

		reset($this->referents);
		while( $r1 = each($this->referents) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				$babDB->db_query("update ".BAB_STATS_ARTICLES_REF_TBL." set st_hits='".$babDB->db_escape_string($r2[1])."' where st_article_id='".$babDB->db_escape_string($r1[0])."' and st_module_id= '".$babDB->db_escape_string($r2[0])."'");
			}
		}
	
	}
}

class bab_stats_forums extends bab_stats_base
{
	var $pages = array();
	var $forums = array();
	var $threads = array();
	var $posts = array();

	function bab_stats_forums()
	{
	}

	function start()
	{
		bab_stat_debug("Forums start...<br>");	
	}

	function addForum($date, $hour, $idforum)
	{
		global $babDB;
		if(!isset($this->forums[$date][$hour][$idforum]))
			{
			$res = $babDB->db_query("select * from ".BAB_STATS_FORUMS_TBL." where st_date='".$babDB->db_escape_string($date)."' and st_hour='".$babDB->db_escape_string($hour)."' and st_forum_id='".$babDB->db_escape_string($idforum)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->forums[$date][$hour][$idforum] = $arr['st_hits'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_FORUMS_TBL." (st_date, st_hour, st_hits, st_forum_id) values ('".$babDB->db_escape_string($date)."','".$babDB->db_escape_string($hour)."', '0', '".$babDB->db_escape_string($idforum)."')");
				$this->forums[$date][$hour][$idforum] = 0;
				}				
			}
		$this->forums[$date][$hour][$idforum]++;		
	}

	function addThread($date, $hour, $idthread)
	{
		global $babDB;
		if(!isset($this->threads[$date][$hour][$idthread]))
			{
			$res = $babDB->db_query("select * from ".BAB_STATS_THREADS_TBL." where st_date='".$babDB->db_escape_string($date)."' and st_hour='".$babDB->db_escape_string($hour)."' and st_thread_id='".$babDB->db_escape_string($idthread)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->threads[$date][$hour][$idthread] = $arr['st_hits'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_THREADS_TBL." (st_date, st_hour, st_hits, st_thread_id) values ('".$babDB->db_escape_string($date)."','".$babDB->db_escape_string($hour)."', '0', '".$babDB->db_escape_string($idthread)."')");
				$this->threads[$date][$hour][$idthread] = 0;
				}				
			}
		$this->threads[$date][$hour][$idthread]++;
	}

	function addPost($date, $hour, $idpost)
	{
		global $babDB;
		if(!isset($this->posts[$date][$hour][$idpost]))
			{
			$res = $babDB->db_query("select * from ".BAB_STATS_POSTS_TBL." where st_date='".$babDB->db_escape_string($date)."' and st_hour='".$babDB->db_escape_string($hour)."' and st_post_id='".$babDB->db_escape_string($idpost)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->posts[$date][$hour][$idpost] = $arr['st_hits'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_POSTS_TBL." (st_date, st_hour, st_hits, st_post_id) values ('".$babDB->db_escape_string($date)."','".$babDB->db_escape_string($hour)."', '0', '".$babDB->db_escape_string($idpost)."')");
				$this->posts[$date][$hour][$idpost] = 0;
				}				
			}
		$this->posts[$date][$hour][$idpost]++;		
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("Forums process...<br>");
		if( isset($datas['info']['bab_forums']))
		{
		for( $i = 0; $i < count($datas['info']['bab_forums']); $i++ )
			{
			$this->addForum($datas['date'], $datas['hour'], $datas['info']['bab_forums'][$i]);
			}
		}

		if( isset($datas['info']['bab_threads']))
		{
		$arrt = array();
		for( $i = 0; $i < count($datas['info']['bab_threads']); $i++ )
			{
			$this->addThread($datas['date'], $datas['hour'], $datas['info']['bab_threads'][$i]);
			$arrt[] = $datas['info']['bab_threads'][$i];
			}

		$arru = array_unique($arrt);
		if( count($arru) > 0 )
			{
			$res = $babDB->db_query("select distinct forum from ".BAB_THREADS_TBL." where id in (".$babDB->quote($arru).")");
			while( $row = $babDB->db_fetch_array($res))
				{
				$this->addForum($datas['date'], $datas['hour'], $row['forum']);
				}
			}
		}

		if( isset($datas['info']['bab_posts']))
		{
		$arrp = array();
		for( $i = 0; $i < count($datas['info']['bab_posts']); $i++ )
			{
			$this->addPost($datas['date'], $datas['hour'], $datas['info']['bab_posts'][$i]);
			$arrp[] = $datas['info']['bab_posts'][$i];
			}

		$arru = array_unique($arrp);
		if( count($arru) > 0 )
			{
			$res = $babDB->db_query("select tt.forum, tt.id from ".BAB_THREADS_TBL." tt left join ".BAB_POSTS_TBL." pt on pt.id_thread=tt.id where pt.id in (".$babDB->quote($arru).")");
			while( $row = $babDB->db_fetch_array($res))
				{
				$this->addForum($datas['date'], $datas['hour'], $row['forum']);
				$this->addThread($datas['date'], $datas['hour'], $row['id']);
				}
			}
		}
	}

	function terminate()
	{

		global $babDB;

		reset($this->forums);
		while( $r1 = each($this->forums) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_FORUMS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_forum_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
		reset($this->threads);
		while( $r1 = each($this->threads) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_THREADS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_thread_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}

		reset($this->posts);
		while( $r1 = each($this->posts) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_POSTS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_post_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}

	}
}


class bab_stats_xlinks extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_xlinks()
	{
	}

	function start()
	{
		bab_stat_debug("External links start...<br>");	
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("External links process...<br>");
		if( isset($datas['info']['bab_xlinks']))
		{
		for( $i = 0; $i < count($datas['info']['bab_xlinks']); $i++ )
			{
			if(!isset($this->results[$datas['date']][$datas['hour']][$datas['info']['bab_xlinks'][$i]]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_XLINKS_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_xlink_url='".$babDB->db_escape_string($datas['info']['bab_xlinks'][$i])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_xlinks'][$i]] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_XLINKS_TBL." (st_date, st_hour, st_hits, st_xlink_url) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($datas['info']['bab_xlinks'][$i])."')");
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_xlinks'][$i]] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_xlinks'][$i]]++;		
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_XLINKS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_xlink_url= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}


class bab_stats_search extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_search()
	{
	}

	function start()
	{
		bab_stat_debug("Search keywords start...<br>");	
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("Search keywords process...<br>");
		if( isset($datas['info']['bab_searchword']))
		{
		for( $i = 0; $i < count($datas['info']['bab_searchword']); $i++ )
			{
			$word = mb_strtolower($datas['info']['bab_searchword'][$i]);
			if(!isset($this->results[$datas['date']][$datas['hour']][$word]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_SEARCH_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_word='".$babDB->db_escape_string($word)."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$word] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_SEARCH_TBL." (st_date, st_hour, st_hits, st_word) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($word)."')");
					$this->results[$datas['date']][$datas['hour']][$word] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$word]++;		
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_SEARCH_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_word= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}



class bab_stats_faqs extends bab_stats_base
{
	var $pages = array();
	var $faqs = array();
	var $faqqrs = array();

	function bab_stats_faqs()
	{
	}

	function start()
	{
		bab_stat_debug("Faqs start...<br>");	
	}

	function addFaq($date, $hour, $idfaq)
	{
		global $babDB;
		if(!isset($this->faqs[$date][$hour][$idfaq]))
			{
			$res = $babDB->db_query("select * from ".BAB_STATS_FAQS_TBL." where st_date='".$babDB->db_escape_string($date)."' and st_hour='".$babDB->db_escape_string($hour)."' and st_faq_id='".$babDB->db_escape_string($idfaq)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->faqs[$date][$hour][$idfaq] = $arr['st_hits'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_FAQS_TBL." (st_date, st_hour, st_hits, st_faq_id) values ('".$babDB->db_escape_string($date)."','".$babDB->db_escape_string($hour)."', '0', '".$babDB->db_escape_string($idfaq)."')");
				$this->faqs[$date][$hour][$idfaq] = 0;
				}				
			}
		$this->faqs[$date][$hour][$idfaq]++;		
	}

	function addQuestion($date, $hour, $idfaqqr)
	{
		global $babDB;
		if(!isset($this->faqqrs[$date][$hour][$idfaqqr]))
			{
			$res = $babDB->db_query("select * from ".BAB_STATS_FAQQRS_TBL." where st_date='".$babDB->db_escape_string($date)."' and st_hour='".$babDB->db_escape_string($hour)."' and st_faqqr_id='".$babDB->db_escape_string($idfaqqr)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->faqqrs[$date][$hour][$idfaqqr] = $arr['st_hits'];
				}
			else
				{
				$babDB->db_query("insert into ".BAB_STATS_FAQQRS_TBL." (st_date, st_hour, st_hits, st_faqqr_id) values ('".$babDB->db_escape_string($date)."','".$babDB->db_escape_string($hour)."', '0', '".$babDB->db_escape_string($idfaqqr)."')");
				$this->faqqrs[$date][$hour][$idfaqqr] = 0;
				}				
			}
		$this->faqqrs[$date][$hour][$idfaqqr]++;
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("Faqs process...<br>");
		if( isset($datas['info']['bab_faqs']))
		{
		for( $i = 0; $i < count($datas['info']['bab_faqs']); $i++ )
			{
			$this->addFaq($datas['date'], $datas['hour'], $datas['info']['bab_faqs'][$i]);
			}
		}

		if( isset($datas['info']['bab_faqsqr']))
		{
		$arrt = array();
		for( $i = 0; $i < count($datas['info']['bab_faqsqr']); $i++ )
			{
			$this->addQuestion($datas['date'], $datas['hour'], $datas['info']['bab_faqsqr'][$i]);
			$arrt[] = $datas['info']['bab_faqsqr'][$i];
			}

		$arru = array_unique($arrt);
		if( count($arru) > 0 )
			{
			$res = $babDB->db_query("select distinct idcat from ".BAB_FAQQR_TBL." where id in (".$babDB->quote($arru).")");
			while( $row = $babDB->db_fetch_array($res))
				{
				$this->addFaq($datas['date'], $datas['hour'], $row['idcat']);
				}
			}
		}
	}

	function terminate()
	{

		global $babDB;

		reset($this->faqs);
		while( $r1 = each($this->faqs) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_FAQS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_faq_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
		reset($this->faqqrs);
		while( $r1 = each($this->faqqrs) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_FAQQRS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_faqqr_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}

	}
}


class bab_stats_fmfolders extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_fmfolders()
	{
	}

	function start()
	{
		bab_stat_debug("Folders start...<br>");	
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("Folders process...<br>");
		if( isset($datas['info']['bab_fmfolders']))
		{
		for( $i = 0; $i < count($datas['info']['bab_fmfolders']); $i++ )
			{
			if(!isset($this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfolders'][$i]]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_FMFOLDERS_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_folder_id='".$babDB->db_escape_string($datas['info']['bab_fmfolders'][$i])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfolders'][$i]] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_FMFOLDERS_TBL." (st_date, st_hour, st_hits, st_folder_id) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($datas['info']['bab_fmfolders'][$i])."')");
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfolders'][$i]] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfolders'][$i]]++;		
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_FMFOLDERS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_folder_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}


class bab_stats_fmfiles extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_fmfiles()
	{
	}

	function start()
	{
		bab_stat_debug("FM Files start...<br>");	
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("FM Files process...<br>");
		if( isset($datas['info']['bab_fmfiles']))
		{
		for( $i = 0; $i < count($datas['info']['bab_fmfiles']); $i++ )
			{
			if(!isset($this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfiles'][$i]]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_FMFILES_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_fmfile_id='".$babDB->db_escape_string($datas['info']['bab_fmfiles'][$i])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfiles'][$i]] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_FMFILES_TBL." (st_date, st_hour, st_hits, st_fmfile_id) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($datas['info']['bab_fmfiles'][$i])."')");
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfiles'][$i]] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_fmfiles'][$i]]++;		
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_FMFILES_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_fmfile_id= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}


class bab_stats_ovmlfiles extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_ovmlfiles()
	{
	}

	function start()
	{
		bab_stat_debug("OVML Files start...<br>");	
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("OVML Files process...<br>");
		if( isset($datas['info']['bab_ovml']))
		{
		for( $i = 0; $i < count($datas['info']['bab_ovml']); $i++ )
			{
			if(!isset($this->results[$datas['date']][$datas['hour']][$datas['info']['bab_ovml'][$i]]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_OVML_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_ovml_file='".$babDB->db_escape_string($datas['info']['bab_ovml'][$i])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_ovml'][$i]] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_OVML_TBL." (st_date, st_hour, st_hits, st_ovml_file) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($datas['info']['bab_ovml'][$i])."')");
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_ovml'][$i]] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_ovml'][$i]]++;		
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_OVML_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_ovml_file= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}


class bab_stats_addons extends bab_stats_base
{
	var $pages = array();
	var $results = array();

	function bab_stats_addons()
	{
	}

	function start()
	{
		bab_stat_debug("Addons start...<br>");	
	}

	function process($datas)
	{
		global $babDB;
		bab_stat_debug("Addons process...<br>");
		if( isset($datas['info']['bab_addon']))
		{
		for( $i = 0; $i < count($datas['info']['bab_addon']); $i++ )
			{
			if(!isset($this->results[$datas['date']][$datas['hour']][$datas['info']['bab_addon'][$i]]))
				{
				$res = $babDB->db_query("select * from ".BAB_STATS_ADDONS_TBL." where st_date='".$babDB->db_escape_string($datas['date'])."' and st_hour='".$babDB->db_escape_string($datas['hour'])."' and st_addon='".$babDB->db_escape_string($datas['info']['bab_addon'][$i])."'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_addon'][$i]] = $arr['st_hits'];
					}
				else
					{
					$babDB->db_query("insert into ".BAB_STATS_ADDONS_TBL." (st_date, st_hour, st_hits, st_addon) values ('".$babDB->db_escape_string($datas['date'])."','".$babDB->db_escape_string($datas['hour'])."', '0', '".$babDB->db_escape_string($datas['info']['bab_addon'][$i])."')");
					$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_addon'][$i]] = 0;
					}				
				}
			$this->results[$datas['date']][$datas['hour']][$datas['info']['bab_addon'][$i]]++;		
			}
		}

	}

	function terminate()
	{

		global $babDB;

		reset($this->results);
		while( $r1 = each($this->results) ) 
		{
			reset($r1[1]);
			while( $r2 = each($r1[1]) ) 
			{
				reset($r2[1]);
				while( $r3 = each($r2[1]) ) 
				{
					$babDB->db_query("update ".BAB_STATS_ADDONS_TBL." set st_hits='".$babDB->db_escape_string($r3[1])."' where st_date='".$babDB->db_escape_string($r1[0])."' and  st_hour = '".$babDB->db_escape_string($r2[0])."' and st_addon= '".$babDB->db_escape_string($r3[0])."'");
				}
			}
		}
	}
}


class tempCLs
	{
	function tempCLs($total, $count)
		{
		$this->totaltxt = bab_translate("Total number of records");
		$this->proctxt = bab_translate("Number of records processed");
		$this->totalval = $total;
		$this->procval = $count;
		}
	}

class bab_stats_handler
{
	var $statrows;
	var $statlimit;
	var $statecho;
	var $handlers = array();

	function bab_stats_handler($statrows, $statlimit, $statecho)
		{
		$this->statrows = $statrows;
		$this->statlimit = $statlimit;
		$this->statecho = $statecho;
		}

	function attach(&$handler)
		{
		if( get_parent_class($handler) == "bab_stats_base" )
			{
			$this->handlers[] = &$handler;
			return true;
			}
		else
			{
			return false;
			}
		}

	function process_log()
		{
		global $babDB;

		for( $i = 0; $i < count($this->handlers); $i++ )
			{
			$this->handlers[$i]->start();
			}

		list($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_STATS_EVENTS_TBL.""));
		$count = 0;
		$req = "select * from ".BAB_STATS_EVENTS_TBL." order by id asc limit 0, ".$babDB->db_escape_string($this->statlimit);
		$res = $babDB->db_query($req);
		while( $res && $babDB->db_num_rows($res) > 0)
			{
			$maxid = 0;
			while( $arr = $babDB->db_fetch_array($res))
				{
				$count++;
				$maxid = $arr['id'];
				$rr = explode(" ", $arr['evt_time']);
				$date = $rr[0];
				$time = $rr[1];
				$rr = explode(":", $time);
				$hour = $rr[0];
				settype($hour, "integer");
				$datas['hour'] = $hour;
				$datas['date'] = $date;
				$datas['url'] = $arr['evt_url'];
				$datas['referer'] = $arr['evt_referer'];
				$datas['iduser'] = $arr['evt_iduser'];
				$datas['session_id'] = $arr['evt_session_id'];
				$datas['client'] = $arr['evt_client'];
				$datas['host'] = $arr['evt_host'];
				$datas['id_site'] = $arr['evt_id_site'];
				$datas['tg'] = $arr['evt_tg'];
				$datas['ip'] = $arr['evt_ip'];
				$datas['info'] = unserialize($arr['evt_info']);
				for( $i = 0; $i < count($this->handlers); $i++ )
					{
					$this->handlers[$i]->process($datas);
					}
				}
			if( $maxid )
				{
				$babDB->db_query("delete from ".BAB_STATS_EVENTS_TBL." where id <= '".$babDB->db_escape_string($maxid)."'");
				}

			if( $this->statrows != 0 && $count >= $this->statrows )
				{
				break;
				}

			$res = $babDB->db_query($req);
			}

		for( $i = 0; $i < count($this->handlers); $i++ )
			{
			$this->handlers[$i]->terminate();
			}

		if( $babDB->db_num_rows($res) == 0 )
			{
			$babDB->db_query("TRUNCATE TABLE ".BAB_STATS_EVENTS_TBL."");
			}

		$babDB->db_query("update ".BAB_SITES_TBL." set stat_update_time=now()");
		if( !$this->statecho )
			{
			global $babBody;
			$temp = new tempCLs($total, $count);
			$babBody->babecho(bab_printTemplate($temp,"statconf.html", "updatestat"));
			}
		else
			{
			bab_stat_debug("Number of rows ... ".$total."<br>");	
			bab_stat_debug("Number of rows processed... ".$count."<br>");
			}
		}
	
}

/* main */
$idx = bab_rp('idx');
$statlimit = bab_rp('statlimit', OVSTAT_LIMIT);
$statrows = bab_rp('statrows', OVSTAT_ROWS);

$babStatRefs = array();

switch($idx)
	{
	case "maj":
		$statecho = false;
		break;

	default:
		$statecho = true;
		break;
	}

bab_setTimeLimit(0);

$st = new bab_stats_handler($statrows, $statlimit, $statecho);

$st2 = new bab_stats_pages();
$st->attach($st2);

$st3 = new bab_stats_articles();
$st->attach($st3);

$st4 = new bab_stats_xlinks();
$st->attach($st4);

$st5 = new bab_stats_forums();
$st->attach($st5);

$st6 = new bab_stats_search();
$st->attach($st6);

$st7 = new bab_stats_faqs();
$st->attach($st7);

$st8 = new bab_stats_fmfolders();
$st->attach($st8);

$st9 = new bab_stats_fmfiles();
$st->attach($st9);

$st10 = new bab_stats_ovmlfiles();
$st->attach($st10);

$st11 = new bab_stats_addons();
$st->attach($st11);

$st1 = new bab_stats_modules(); /* must be attached as the last */
$st->attach($st1);

$st->process_log();

if( $statecho )
{
	exit;
}
?>