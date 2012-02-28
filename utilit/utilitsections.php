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
include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';


function bab_getAddonsMenus($row, $what)
{
	global $babDB;
	$addon_urls = array();
	$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
	if( is_file($addonpath."/init.php" ))
		{
		bab_setAddonGlobals($row['id']);
		
		require_once( $addonpath."/init.php" );
		$func = $row['title']."_".$what;
		if( !empty($func) && function_exists($func))
			{
			while( $func($url, $txt))
				{
				$addon_urls[$txt] = $url;
				}
			$func = $row['title']."_onSectionCreate";
			$res = $babDB->db_query("select id from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");
			if( $res && $babDB->db_num_rows($res) < 1 && function_exists($func))
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'"));
				$babDB->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$row['id']. "', '0', '4', '" . ($arr[0]+1). "')");
				}
			else if( $res && $babDB->db_num_rows($res) > 0 && !function_exists($func))
				{
				$babDB->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");	
				$babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$row['id']."' and type='4'");	
				}
			}
		}
	return $addon_urls;
}



class babSection
{
var $title;
var $content;
var $hiddenz;
var $position;
var $close;
var $boxurl;
var $bbox;
var $template;

function babSection($title = "Section", $content="<br>This is a sample of content<br>")
{
	$this->title = $title;
	$this->content = $content;
	$this->hiddenz = false;
	$this->position = 0;
	$this->close = 0;
	$this->boxurl = "";
	$this->bbox = 0;
	$this->template = "default";
	$this->t_open = bab_translate("Open");
	$this->t_close = bab_translate("Close");
}

function getTitle() { return $this->title;}
function setTitle($title) {	$this->title = $title;}
function getContent() {	return $this->content;}
function getTemplate() { return $this->template;}
function setTemplate($template)
	{
	if( !empty($template))
		$this->template = $template;
	}

function setContent($content)
{
	$this->content = $content;
}

function getPosition()
{
	return $this->position;
}

function setPosition($pos)
{
	$this->position = $pos;
}

function isVisible()
{
	if( $this->hiddenz == true)
		return false;
	else
		return true;
}

function show()
{
	$this->hiddenz = false;
}

function hide()
{
	$this->hiddenz = true;
}

function close()
{
	$this->close = 1;
}

function open()
{
	$this->close = 0;
}

function printout()
{
	$file = "sectiontemplate.html";
	$str = bab_printTemplate($this,$file, $this->template);
	if( empty($str))
		return bab_printTemplate($this,$file, "default");
	else
		return $str;
}

}  /* end of class babSection */


class babSectionTemplate extends babSection
{
var $file;
function babSectionTemplate($file, $section="")
	{
	$this->babSection("","");

	
	$this->file = $file;
	$this->htmlid = mb_substr($this->file,0,-5);
	$this->setTemplate($section);
	}

function printout()
	{
	if( !file_exists( 'skins/'.$GLOBALS['babSkin'].'/templates/'. $this->file ) )
		{
		if (!$this->close)
			$this->content = bab_printTemplate($this,'insections.html', $this->htmlid );
		return bab_printTemplate($this,'sectiontemplate.html', 'default');
		}
	$str = bab_printTemplate($this,$this->file, $this->template);
	if( empty($str))
		return bab_printTemplate($this,$this->file, "template");
	else
		return $str;
	}
}

class babAdminSection extends babSectionTemplate
{

var $head;
var $foot;
var $key;
var $val;

function babAdminSection($close)
	{
	global $babBody, $babDB;
	$this->babSectionTemplate("adminsection.html", "template");
	$this->title = bab_translate("Administration");
	if( $close )
		return;
		
	$dgPrefix = 'babDG'.$babBody->currentAdmGroup;

	$rootNode = bab_siteMap::get(array('root', 'DG'.$babBody->currentAdmGroup , $dgPrefix.'Admin'));
	$nodename = $dgPrefix.'AdminSection';

	$this->babAdminSection = $rootNode->getNodeById($nodename);
	
	$this->head = '';
	$this->foot = '';
	
	global $babBody;
	
	$sDgName = '';
	if( $babBody->currentAdmGroup == 0 ) {
		$sDgName = bab_translate("all site");
		}
	else {
		$sDgName = $babBody->currentDGGroup['name'];
		}

	if ($this->babAdminSection) {
		$this->babAdminSection->sortChildNodes();
		$this->head = bab_translate("Currently you administer ") . $sDgName;
		$this->babAdminSection = $this->babAdminSection->firstChild();
	}
	
	$this->babAdminSectionAddons = $rootNode->getNodeById($dgPrefix.'AdminSectionAddons');

	

	if ($this->babAdminSectionAddons) {
		$this->babAdminSectionAddons->sortChildNodes();
		$this->babAdminSectionAddons = $this->babAdminSectionAddons->firstChild();
	}


	}

function addUrl()
	{
	if (!$this->babAdminSection) {
		return false;
	}

	$item = $this->babAdminSection->getData();
	$this->val = bab_toHtml($item->url);
	$this->key = bab_toHtml($item->name);
	$this->description = bab_toHtml($item->description);
	$this->babAdminSection = $this->babAdminSection->nextSibling();
	return true; 
	
	}

function addAddonUrl()
	{
	
	
	
	if (!$this->babAdminSectionAddons) {
		return false;
	}

	$item = $this->babAdminSectionAddons->getData();
	$this->val = bab_toHtml($item->url);
	$this->key = bab_toHtml($item->name);
	$this->babAdminSectionAddons = $this->babAdminSectionAddons->nextSibling();
	return true;
	}

}

class babUserSection extends babSectionTemplate
{
var $head;
var $foot;
var $key;
var $val;
var $newcount;
var $newtext;
var $newurl;
var $blogged;
var $aidetxt;
var $vacwaiting;

function babUserSection($close) {

	global $babDB, $babBody, $BAB_SESS_USERID;
	$this->babSectionTemplate("usersection.html", "template");
	$this->title = bab_translate("User's section");
	$this->vacwaiting = false;

	if( $close )
		{
		return;
		}

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->head = bab_translate("You are logged on as").":<br><center><b>";
		$this->head .= bab_toHtml($GLOBALS['BAB_SESS_USER']);
		$this->login = bab_translate("You are logged on as");
		}
	else
		{
		$this->head = bab_translate("You are not yet logged in")."<br><center><b>";
		$this->login = bab_translate("You are not yet logged in");
		}
	$this->head .= "</b></center>";
	$this->foot = "";
	$this->aidetxt = bab_translate("Since your last connection:");

	$this->blogged = false;
	
	if(!empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->blogged = true;

	}
	
	$rootNode = bab_siteMap::get(array('root', 'DGAll', 'babUser'));
	$this->babUserSection = $rootNode->getNodeById('babUserSection');
	if ($this->babUserSection) {
		$this->babUserSection->sortChildNodes();
		$this->babUserSection = $this->babUserSection->firstChild();
	}
	
	$this->babUserSectionAddons = $rootNode->getNodeById('babUserSectionAddons');
	if ($this->babUserSectionAddons) {
		$this->babUserSectionAddons->sortChildNodes();
		$this->babUserSectionAddons = $this->babUserSectionAddons->firstChild();
	}
	
	$this->lastlog = $babBody->lastlog;
}


function addUrl(&$skip) {

	if (!$this->babUserSection) {
		return false;
	}
	
	$uid = $this->babUserSection->getId();
	$item = $this->babUserSection->getData();

	
	$this->url = bab_toHtml($item->url);
	$this->text = bab_toHtml($item->name);
	$this->description = bab_toHtml($item->description);
	$this->babUserSection = $this->babUserSection->nextSibling();
	
	if ('babUserApprob' === $uid && !bab_isWaitingApprobations())
	{
		// the approbation entry will not be displayed in user section if there is no approbation
		// but the entry is still in sitemap
		$skip = true;
	}
	
	return true;
}


function addAddonUrl()
	{
	if (!$this->babUserSectionAddons) {
		return false;
	}

	$item = $this->babUserSectionAddons->getData();
	$this->url = bab_toHtml($item->url);
	$this->text = bab_toHtml($item->name);
	$this->babUserSectionAddons = $this->babUserSectionAddons->nextSibling();
	return true; 

	}

function getnextnew()
	{
	global $babBody;
	static $i = 0;
	if( $i < 4)
		{
		switch( $i )
			{
			case 0:
				$this->newcount = $this->get_newarticles();
				$this->newtext = bab_translate("Article(s)");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newarticles.html&amp;nbdays=0";
				break;
			case 1:
				$this->newcount = $this->get_newcomments();
				$this->newtext = bab_translate("Comment(s)");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newcomments.html&amp;nbdays=0";
				break;
			case 2:
				$this->newcount = $this->get_newposts();
				$this->newtext = bab_translate("Reply(ies)");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newposts.html&amp;nbdays=0";
				break;
			case 3:
				$this->newcount = $this->get_newfiles();
				$this->newtext = bab_translate("File(s)");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newfiles.html&amp;nbdays=0";
				break;
			}
		$i++;
		return true;
		}
	else
		return false;
	}
	
	
	
private function get_newarticles() {
	
	static $newarticles = null;
	if (!is_null($newarticles))	
		return $newarticles;
	
	
	$newarticles = 0;
	$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
	if( count($topview) > 0 )
		{
		global $babDB;
		$res = $babDB->db_query("select id_topic, restriction from ".BAB_ARTICLES_TBL." where (date_publication = '0000-00-00 00:00:00' OR date_publication <= now()) AND date >= '".$babDB->db_escape_string($this->lastlog)."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($topview[$row['id_topic']]) && ( $row['restriction'] == '' || bab_articleAccessByRestriction($row['restriction']) ))
				{
				$newarticles++;
				}
			}
		}
	return $newarticles;
	}

private function get_newcomments() {

	static $newcomments = null;
	if (!is_null($newcomments))	
		return $newcomments;

	$newcomments = 0;
	$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
	if( count($topview) > 0 )
		{
		global $babDB;
		$res = $babDB->db_query("select id_topic from ".BAB_COMMENTS_TBL." where confirmed='Y' and date >= '".$babDB->db_escape_string($this->lastlog)."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($topview[$row['id_topic']]) )
				{
				$newcomments++;
				}
			}
		}
	return $newcomments;
	}



private function get_newposts() {

	static $newposts = null;
	if (!is_null($newposts))	
		return $newposts;

	global $babDB;

	list($newposts) = $babDB->db_fetch_array($babDB->db_query("select count(p.id) from ".BAB_POSTS_TBL." p, ".BAB_THREADS_TBL." t where p.date >= '".$this->lastlog."' and p.confirmed='Y' and p.id_thread=t.id and t.forum IN(".$babDB->quote(array_keys(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL))).")"));

	return $newposts;
	}

private function get_newfiles() {

	static $newfiles = null;
	if (!is_null($newfiles))	
		return $newfiles;

	$arrfid = array();
	$arrfid = bab_getUserIdObjects(BAB_FMDOWNLOAD_GROUPS_TBL);
	
	if( is_array($arrfid) && count($arrfid) > 0 )
		{
		global $babDB;
		$req = "select count(f.id) from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.state='' and f.confirmed='Y' and f.id_owner IN (".$babDB->quote($arrfid).")";
		$req .= " and f.modified >= '".$babDB->db_escape_string($this->lastlog)."'";
		$req .= " order by f.modified desc";

		list($newfiles) = $babDB->db_fetch_row($babDB->db_query($req));
		}
	else
		{
			$newfiles = 0;
		}

	return $newfiles;
	}

	

}


class babTopcatSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;

function babTopcatSection($close)
	{
	global $babDB, $babBody;
	$this->babSectionTemplate("topcatsection.html", "template");
	$this->title = bab_translate("Topics categories");

	$res = $babDB->db_query("SELECT tct.id, tct.title FROM ".BAB_TOPCAT_ORDER_TBL." tot left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tot.id_topcat=tct.id WHERE tot.id_parent='0' and tot.type='1' order by tot.ordering asc");
	$topcatview = $babBody->get_topcatview();
	while( $row = $babDB->db_fetch_array($res))
		{
		if( isset($topcatview[$row['id']]) )
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}

			$this->arrid[] = array($row['id'], $row['title']);
			}
		}
	$this->head = bab_translate("List of different topics categories");
	$this->count = count($this->arrid);
	}

function topcatGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$this->text = bab_toHtml($this->arrid[$i][1]);
		$this->url = $GLOBALS['babUrlScript']."?tg=topusr&amp;cat=".$this->arrid[$i][0];
		$i++;
		return true;
		}
	else
		return false;
	}
}

class babTopicsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;
var $newa;
var $newc;
var $waitingc;
var $waitinga;
var $waitingcimg;
var $waitingaimg;
var $bfooter;

function babTopicsSection($cat, $close)
	{
	global $babDB, $babBody;
	static $foot, $waitingc, $waitinga, $waitingaimg, $waitingcimg;
	$this->babSectionTemplate("topicssection.html", "template");
	$r = $babDB->db_fetch_array($babDB->db_query("select description, title, template from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($cat)."'"));
	$this->setTemplate($r['template']);
	$this->title = bab_toHtml($r['title']);
	$this->head = bab_toHtml($r['description']);

	$req = "select top.id topid, type, top.id_topcat id, lang, idsaart, idsacom from ".BAB_TOPCAT_ORDER_TBL." top LEFT JOIN ".BAB_TOPICS_TBL." t ON top.id_topcat=t.id and top.type=2 LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tc ON top.id_topcat=tc.id and top.type=1 where top.id_parent='".$babDB->db_escape_string($cat)."' order by top.ordering asc";
	$res = $babDB->db_query($req);
	$topcatview = $babBody->get_topcatview();
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['type'] == 2 && bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id']))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}

			$whatToFilter = bab_getInstance('babLanguageFilter')->getFilterAsInt();
			if(($arr['lang'] == '*') or ($arr['lang'] == ''))
				$whatToFilter = 0;
			else if((isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose') and ( bab_isUserTopicManager($arr['id']) or bab_isCurrentUserApproverFlow($arr['idsaart']) or bab_isCurrentUserApproverFlow($arr['iddacom'])))
				$whatToFilter = 0;

			if(($whatToFilter == 0)	or ($whatToFilter == 1 and (mb_substr($arr['lang'], 0, 2) == mb_substr($GLOBALS['babLanguage'], 0, 2)))
				or ($whatToFilter == 2 and ($arr['lang'] == $GLOBALS['babLanguage'])))
				array_push($this->arrid, $arr['topid']);
			}
		else if( $arr['type'] == 1 && isset($topcatview[$arr['id']]))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			array_push($this->arrid, $arr['topid']);
			}
		}

	$this->bfooter = 0;
	if( empty($foot)) $foot = bab_translate("Topics with asterisk have waiting articles or comments ");
	if( empty($waitingc)) $waitingc = bab_translate("Waiting comments");
	if( empty($waitinga)) $waitinga = bab_translate("Waiting articles");
	if( empty($waitingaimg)) $waitingaimg = bab_printTemplate($this, "config.html", "babWaitingArticle");
	if( empty($waitingcimg)) $waitingcimg = bab_printTemplate($this, "config.html", "babWaitingComment");

	$this->foot = &$foot;
	$this->waitingc = &$waitingc;
	$this->waitinga = &$waitinga;
	$this->waitingaimg = &$waitingaimg;
	$this->waitingcimg = &$waitingcimg;

	$this->count = count($this->arrid);
	if($this->count > 0)
		{
		$inclause = implode(',', $this->arrid);
		$this->res = $babDB->db_query("SELECT tot.id, tot.id_topcat, tot.type, tt.id AS id_tt, tt.idsacom, tt.idsaart, tt.category, tct.id AS id_tct, tct.title FROM ".BAB_TOPCAT_ORDER_TBL." AS tot LEFT JOIN ".BAB_TOPICS_TBL." AS tt ON tt.id=tot.id_topcat LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." AS tct ON tct.id=tot.id_topcat WHERE tot.id IN(".$inclause.") ORDER BY tot.ordering");
		$this->count = $babDB->db_num_rows($this->res);
		}
	} // function babTopicsSection

function topicsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID, $babInstallPath;
	include_once $babInstallPath."utilit/afincl.php";
	static $i = 0;
	if( $i < $this->count)
		{
		$arr = $babDB->db_fetch_array($this->res, "fff".$i);

		if( $arr['type'] == 2 )
			{
			$this->newa = "";
			$this->newc = "";
			if( bab_isCurrentUserApproverFlow($arr['idsaart']))
				{
				$this->bfooter = 1;
				if( count(bab_getWaitingArticles($arr['id_tt'])) > 0 )
					{
					$this->newa = "a";
					}
				}

			if( bab_isCurrentUserApproverFlow($arr['idsacom']))
				{
				if( count(bab_getWaitingComments($arr['id_tt'])) > 0 )
					{
					$this->newc = "c";
					}
				}
			$this->text = bab_toHtml($arr['category']);
			$this->url = $GLOBALS['babUrlScript']."?tg=articles&amp;topics=".$arr['id_tt'];
			}
		else if( $arr['type'] == 1 )
			{
			$this->newa = "";
			$this->newc = "";
			$this->text = bab_toHtml($arr['title']);
			$this->url = $GLOBALS['babUrlScript']."?tg=topusr&amp;cat=".$arr['id_tct'];
			}
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

class babForumsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;
var $waiting;
var $bfooter;
var $waitingf;
var $waitingpostsimg;

function babForumsSection($close)
	{
	global $babDB, $babBody;
	static $waitingpostsimg = null;
	$this->babSectionTemplate("forumssection.html", "template");
	$this->title = bab_translate("Forums");

	include_once dirname(__FILE__).'/forumincl.php';

	$this->arrid = bab_get_forums();
	if( count($this->arrid) && $close )
		{
		$this->count = 1;
		return;
		}
	
	if( null === $waitingpostsimg) {
		$waitingpostsimg = bab_printTemplate($this, "config.html", "babWaitingPosts");
		
		if (empty($waitingpostsimg)) {
			$waitingpostsimg = '';
		}
	}
	
	$this->waitingpostsimg = $waitingpostsimg;
	
	$this->head = bab_translate("List of different forums");
	$this->waitingf = bab_translate("Waiting posts");
	$this->bfooter = 0;
	$this->count = count($this->arrid);
	$this->foot = "";
	}

function forumsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( list($key,$val) = each($this->arrid) )
		{
		$this->text = bab_toHtml($val['name']);
		$this->url = $GLOBALS['babUrlScript']."?tg=threads&amp;forum=".$key;
		$this->waiting = "";
		if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $key))
			{
			$this->bfooter = 1;
			$req = "select count(".BAB_POSTS_TBL.".id) as total from ".BAB_POSTS_TBL." join ".BAB_THREADS_TBL." where ".BAB_THREADS_TBL.".active='Y' and ".BAB_THREADS_TBL.".forum='".$babDB->db_escape_string($key);
			$req .= "' and ".BAB_POSTS_TBL.".confirmed='N' and ".BAB_THREADS_TBL.".id=".BAB_POSTS_TBL.".id_thread";
			$res = $babDB->db_query($req);
			$ar = $babDB->db_fetch_array($res);
			if( $ar['total'] > 0)
				{
				$this->waiting = "*";
				}
			}
		$i++;
		return true;
		}
	else
		{
		reset($this->arrid);
		return false;
		}
	}
}



class babMonthA  extends babSection
{
var $currentMonth;
var $currentYear;
var $curmonth;
var $curyear;
var $day3;
var $curmonthevents = array();
var $days;
var $daynumber;
var $now;
var $w;
var $event;
var $dayurl;
var $babCalendarStartDay;


	public function babMonthA($month='', $year='')
	{
		global $babDB,$babBody, $BAB_SESS_USERID;
	
		$this->babSection("","");
	
		if(empty($month)) {
			$this->currentMonth = date("n");
		} else {
			$this->currentMonth = $month;
		}
		if(empty($year)) {
			$this->currentYear = date("Y");
		} else {
			$this->currentYear = $year;
		}
	
		$this->babCalendarStartDay = bab_getICalendars()->startday;
		$this->curDay = 0;
	}

	public function printout()
	{
		global $babBody, $babDB, $BAB_SESS_USERID;
		$months = bab_DateStrings::getMonths();
		$this->curmonth = $months[date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear))];
		$this->curyear = $this->currentYear;
		$this->days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
		$this->daynumber = date("w", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
		$this->now = date("j");
		$this->w = 0;
		$todaymonth = date("n");
		$todayyear = date("Y");
		
		
	
		$this->htmlid = 'montha';
		
		$nbweek = date('W');
		if (substr($nbweek,0,1) == 0) {
			$nbweek = substr($nbweek,1,strlen($nbweek)-1);
		}
		$this->title = $this->curmonth.' '.$this->curyear.'&nbsp;&nbsp;'.bab_translate("W.").$nbweek;
	
		if( !file_exists( 'skins/'.$GLOBALS['babSkin'].'/templates/montha.html' ) )
			{
			if (!$this->close) {
				$this->content = bab_printTemplate($this,'insections.html', 'montha');
			}
			return bab_printTemplate($this,'sectiontemplate.html', 'default');
			}
	
		return bab_printTemplate($this,"montha.html", "");
	}
	
	
	
	private function initMonthEvents()
	{
		// all calendars, before 7.7.94
		// only personal calendar for users logged in, since 7.7.94
		if ($GLOBALS['BAB_SESS_LOGGED'])
		{
			$personalCalendar = bab_getICalendars()->getPersonalCalendar();
			if (null === $personalCalendar)
			{
				return;
			}
			
			$calendars = array($personalCalendar);
		} else {
			$calendars = bab_getICalendars()->getCalendars();
		}
		

		if(count($calendars) > 0)
		{
			require_once dirname(__FILE__).'/dateTime.php';
			require_once dirname(__FILE__).'/cal.userperiods.class.php';
			require_once dirname(__FILE__).'/cal.criteria.class.php';
			
			$daymin = BAB_DateTime::fromTimeStamp(mktime(0,0,0,$this->currentMonth, 1,$this->currentYear));
			$daymax = BAB_DateTime::fromTimeStamp(mktime(0,0,0,$this->currentMonth, $this->days,$this->currentYear));
			
			$periods = new bab_UserPeriods($daymin, $daymax);
			
			$factory = new bab_PeriodCriteriaFactory();
			$criteria = $factory->Collection('bab_CalendarEventCollection');
			$criteria = $criteria->_AND_($factory->Calendar($calendars));
			
			$periods->createPeriods($criteria);
			$periods->orderBoundaries();
			$this->currmonthevents = array();
			
			foreach($periods as $event)
			{
				$startday = date('j', $event->ts_begin);
				$endday = date('j', $event->ts_end);
				
				for($day = $startday ; $day<=$endday; $day++)
				{
					$collection = $event->getCollection();
					if ($collection)
					{
						$calendar = $collection->getCalendar();
						if ($calendar)
						{
							$this->currmonthevents[$day][] = $calendar->getUrlIdentifier();
						}
					}
				}
			}
		}
	}
	
	private function cacheMonthEvents()
	{
		if (isset($this->currmonthevents)) {
			return;
		}
		
		if (isset($_SESSION['bab_MonthSection']))
		{
			$lastupdate = $_SESSION['bab_MonthSection']['lastupdate'];
			
			if ((time() - $lastupdate) < 900) // 15 minutes cache
			{
				$this->currmonthevents = $_SESSION['bab_MonthSection']['events'];
				return;
			}
		}
		
		
		bab_debug('refresh month section cache');
		$this->initMonthEvents();
		
		
		$_SESSION['bab_MonthSection'] = array(
			'events' => $this->currmonthevents,
			'lastupdate' => time()
		);
	}
	

	public function getnextday3()
		{
			static $i = 0;
			if( $i < 7)
				{
				$a = $i + $this->babCalendarStartDay;
				if( $a > 6)
					$a -=  7;
				$this->day3 = mb_substr(bab_DateStrings::getDay($a), 0, 1);
				$i++;
				return true;
			}
			else
				return false;
		}

	public function getnextweek()
		{
			if( $this->w < 7 && $this->curDay < $this->days)
			{
				$this->cacheMonthEvents();					
					
				$this->w++;
				return true;
			}
			else
			{
				return false;
			}
		}

	public function getnextday()
		{
			global $babDB;
			static $d = 0;
			static $total = 0;
			if( $d < 7)
				{
				$this->bgcolor = 0;
				$this->event = 0;
	
				$a = $this->daynumber - $this->babCalendarStartDay;
				if( $a < 0)
					$a += 7;
	
	
				if( ($this->w == 1 &&  $d < $a) || $total >= $this->days )
					{
					$this->day = "&nbsp;";
				}
				else
					{
					$total++;
					$this->curDay++;
	
					if( $total > $this->days)
						{
						return false;
						}
	
					$this->day = $total;
					
					if(isset($this->currmonthevents[$this->day]) && !empty($this->currmonthevents[$this->day]))
						{
							$idcals = implode(',', array_unique($this->currmonthevents[$this->day]));
							if( !empty($idcals))
								{
									$this->event = 1;
									$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&amp;calid=".$idcals."&amp;date=".$this->currentYear.",".$this->currentMonth.",".$total;
									$this->day = $total;
								}
						}
						
					if( $total == $this->now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
						{
						$this->bgcolor = 1;
						}
	
				}
	
				if( $total > $this->days)
					{
					return false;
				}
					
				$d++;
				return true;
			}
			else
				{
				$d = 0;
				return false;
			}
		}
}


