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
include_once 'base.php';
require_once dirname(__FILE__).'/dateTime.php';
include_once dirname(__FILE__)."/editorincl.php";

/**
 * Collect waiting items for approval in core
 * @param bab_eventBeforeWaitingItemsDisplayed $event
 */
function bab_onBeforeWaitingItemsDisplayed(bab_eventBeforeWaitingItemsDisplayed $event)
{
	if ($event->status_only)
	{
		// test if there are waiting items in core
		
		$event->setStatus(bab_isWaitingApprobations());
		return;
	}
	
	
	// collect waiting items
	
	bab_listWaitingPosts($event);
	bab_listWaitingFiles($event);
	bab_listWaitingArticles($event);
	bab_listWaitingComments($event);
	bab_listWaitingEvents($event);
}












/**
 * Waiting posts
 * @param bab_eventBeforeWaitingItemsDisplayed $event
 */
function bab_listWaitingPosts(bab_eventBeforeWaitingItemsDisplayed $event)
{
	global $babDB;
	$W = bab_Widgets();
	
	$arrf = array();
	$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $arr['id']) )
		{
			$arrf[] = $arr['id'];
		}
	}
	
	if( count($arrf) == 0 )
	{
		return;
	}


	$res = $babDB->db_query("select pt.*, pt2.subject as threadtitle, tt.id as threadid, tt.forum as forumid, ft.name as forumname 
			from ".BAB_POSTS_TBL." pt 
				left join ".BAB_THREADS_TBL." tt on pt.id_thread=tt.id 
				left join ".BAB_POSTS_TBL." pt2 on tt.post=pt2.id 
				left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum 
			
			where pt.confirmed='N' and ft.id IN(".$babDB->quote($arrf).") order by date desc
	");

	if ($babDB->db_num_rows($res) <= 0)
	{
		return;
	}

	$items = array();

	while( $arr = $babDB->db_fetch_assoc($res) )
	{

		$postdate = $arr['date'] == '0000-00-00 00:00:00'? '':$W->Label(bab_shortDate(bab_mktime($arr['date']), true));
		$postpath = $W->Link($arr['forumname'].' / '.$arr['threadtitle'], $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['forumid']."&thread=".$arr['threadid']."&post=".$arr['id']."&flat=1");
		$author = $W->Label(bab_getForumContributor($arr['forumid'], $arr['id_author'], $arr['author']));
		$confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confpost&idpost=".$arr['id']."&thread=".$arr['threadid'];

		$layout = $W->HBoxItems(
				$W->VBoxItems($W->Label(bab_translate("Date"))->colon()->addClass('widget-strong'), $postdate),
				$W->VBoxItems($W->Label(bab_translate("Author"))->colon()->addClass('widget-strong'), $author)
		)->setHorizontalSpacing(4,'em');


		$description = $W->VBoxItems(
				$postpath,
				$layout
		)->setVerticalSpacing(.5,'em');

		$items[] = array(
				'text' 			=> $arr['subject'],
				'description' 	=> $description->display($W->HtmlCanvas()),
				'url'			=> $confirmurl,
				'popup'			=> true,
				'idschi'		=> 0
		);

	}

	$event->addObject(bab_translate("Waiting posts"), $items);
}












/**
 * Waiting files and files versions
 * @param bab_eventBeforeWaitingItemsDisplayed $event
 */
function bab_listWaitingFiles(bab_eventBeforeWaitingItemsDisplayed $event)
{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
	$W = bab_Widgets();
	
	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if( count($arrschi) == 0 )
	{
		return;
	}
	
	
	$items = array();
	
	$res = $babDB->db_query("select * from ".BAB_FILES_TBL." where bgroup='Y' and confirmed='N' and idfai IN(".$babDB->quote($arrschi).") order by created desc");
	while( $arr = $babDB->db_fetch_assoc($res) )
	{
	
		$filedate = $arr['created'] == '0000-00-00 00:00:00'? '':$W->Label(bab_shortDate(bab_mktime($arr['created']), true));
		$confirmurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode(mb_substr($arr['path'],0,-1))."&file=".urlencode($arr['name']);
	
		
		$layout = $W->HBoxItems(
				$W->VBoxItems($W->Label(bab_translate("Date"))->colon()->addClass('widget-strong'), $filedate),
				$W->VBoxItems($W->Label(bab_translate("Author"))->colon()->addClass('widget-strong'), $W->Label(bab_getUserName($arr['author']))),
				$W->VBoxItems($W->Label(bab_translate("Path"))->colon()->addClass('widget-strong'), $W->Label($arr['path']))
		)->setHorizontalSpacing(4,'em');
	
		
		$description = empty($arr['description']) ? null : $W->Label($arr['description']);
		
	
		$description = $W->VBoxItems(
				$description,
				$layout
		)->setVerticalSpacing(.5,'em');
	
		$items[] = array(
				'text' 			=> $arr['name'],
				'description' 	=> $description->display($W->HtmlCanvas()),
				'url'			=> $confirmurl,
				'popup'			=> true,
				'idschi'		=> (int) $arr['idfai']
		);
	
	}
	
	
	$res = $babDB->db_query("select fft.*, ft.path, ft.name, ft.description from ".BAB_FM_FILESVER_TBL." fft left join ".BAB_FILES_TBL." ft on ft.id=fft.id_file where fft.confirmed='N' and fft.idfai IN(".$babDB->quote($arrschi).") order by date desc");
	while( $arr = $babDB->db_fetch_assoc($res) )
	{
		$fm_file = fm_getFileAccess($arr['id_file']);
		$oFmFolder =& $fm_file['oFmFolder'];
		$oFolderFile =& $fm_file['oFolderFile'];
		
		$iIdUrl = $oFmFolder->getId();
		if(mb_strlen($oFmFolder->getRelativePath()) > 0)
		{
			$oRootFmFolder = BAB_FmFolderSet::getFirstCollectiveParentFolder($oFmFolder->getRelativePath());
			if(!is_null($oRootFmFolder))
			{
				$iIdUrl = $oRootFmFolder->getId();
			}
		}
		
		$filedate = $arr['date'] == '0000-00-00 00:00:00'? '':$W->Label(bab_shortDate(bab_mktime($arr['date']), true));
		$confirmurl = $GLOBALS['babUrlScript']."?tg=filever&idx=conf&id=".$iIdUrl."&gr=".$oFolderFile->getGroup()."&path=".urlencode(getUrlPath($oFolderFile->getPathName()))."&idf=".$arr['id_file'];
	
		
		$fileversion = $W->Label($arr['ver_major'].".".$arr['ver_minor']);
	
		$layout = $W->HBoxItems(
				$W->VBoxItems($W->Label(bab_translate("Version"))->colon()->addClass('widget-strong'), $fileversion),
				$W->VBoxItems($W->Label(bab_translate("Date"))->colon()->addClass('widget-strong'), $filedate),
				$W->VBoxItems($W->Label(bab_translate("Author"))->colon()->addClass('widget-strong'), $W->Label(bab_getUserName($arr['author']))),
				$W->VBoxItems($W->Label(bab_translate("Path"))->colon()->addClass('widget-strong'), $W->Label($arr['path']))
		)->setHorizontalSpacing(4,'em');
	
	
		$description = empty($arr['description']) ? null : $W->Label($arr['description']);
	
	
		$description = $W->VBoxItems(
				$description,
				$layout
		)->setVerticalSpacing(.5,'em');
	
		$items[] = array(
				'text' 			=> $arr['name'],
				'description' 	=> $description->display($W->HtmlCanvas()),
				'url'			=> $confirmurl,
				'popup'			=> true,
				'idschi'		=> (int) $arr['idfai']
		);
	
	}
	
	
	
	$event->addObject(bab_translate("Waiting files"), $items);
}





/**
 * Waiting articles comments
 * @param bab_eventBeforeWaitingItemsDisplayed $event
 */
function bab_listWaitingComments(bab_eventBeforeWaitingItemsDisplayed $event)
{
	global $babDB;
	$W = bab_Widgets();

	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if( count($arrschi) == 0 )
	{
		return;
	}


	$res = $babDB->db_query("SELECT 
				ct.*, a.title  
		FROM 
			".BAB_COMMENTS_TBL." ct, 
			bab_articles a 
		where 
			a.id=ct.id_article 
			AND ct.idfai IN(".$babDB->quote($arrschi).") 
			order by date desc
	");

	if ($babDB->db_num_rows($res) <= 0)
	{
		return;
	}

	$items = array();

	while( $arr = $babDB->db_fetch_assoc($res) )
	{

		$comdate = $arr['date'] == '0000-00-00 00:00:00'? '':$W->Label(bab_shortDate(bab_mktime($arr['date']), true));
		$artpath = $W->Html(viewCategoriesHierarchy_txt($arr['id_topic']));

		if( $arr['id_author'] )
		{
			$author = $W->Label(bab_getUserName($arr['id_author']));
		}
		else
		{
			$author = $W->Label($arr['name']);
		}
		
		$confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confcom&idcom=".$arr['id'];
		
		$artlink = $W->Link($arr['title'], $GLOBALS['babUrlScript']."?tg=articles&idx=More&article=".$arr['id_article']);

		$layout = $W->HBoxItems(
				$W->VBoxItems($W->Label(bab_translate("Article"))->colon()->addClass('widget-strong'), $artlink),
				$W->VBoxItems($W->Label(bab_translate("Date"))->colon()->addClass('widget-strong'), $comdate),
				$W->VBoxItems($W->Label(bab_translate("Author"))->colon()->addClass('widget-strong'), $author)
		)->setHorizontalSpacing(4,'em');


		$description = $W->VBoxItems(
				$artpath,
				$layout
		)->setVerticalSpacing(.5,'em');

		$items[] = array(
				'text' 			=> $arr['subject'],
				'description' 	=> $description->display($W->HtmlCanvas()),
				'url'			=> $confirmurl,
				'popup'			=> true,
				'idschi'		=> (int) $arr['idfai']
		);

	}

	$event->addObject(bab_translate("Waiting comments"), $items);
}




/**
 * Waiting articles
 * @param bab_eventBeforeWaitingItemsDisplayed $event
 */
function bab_listWaitingArticles(bab_eventBeforeWaitingItemsDisplayed $event)
{
	global $babDB;
	$W = bab_Widgets();
	
	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if( count($arrschi) == 0 )
	{
		return;
	}
	

	$res = $babDB->db_query("select adt.*, count(adft.id) as totalf, count(adnt.id) as totaln 
			from ".BAB_ART_DRAFTS_TBL." adt 
				left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id  
				left join ".BAB_ART_DRAFTS_NOTES_TBL." adnt on adnt.id_draft=adt.id 
			where 
				adt.trash !='Y' and adt.idfai IN(".$babDB->quote($arrschi).") 
				GROUP BY adt.id 
				order by date_submission desc");
	
	if ($babDB->db_num_rows($res) <= 0)
	{
		return;
	}
	
	$items = array();
	
	while( $arr = $babDB->db_fetch_assoc($res) )
	{
		
		$artdate = $arr['date_submission'] == '0000-00-00 00:00:00' ? '' : $W->Label(bab_shortDate(bab_mktime($arr['date_submission']), true));
		$artpath = $W->Html(viewCategoriesHierarchy_txt($arr['id_topic']));
		$author = $W->Label(bab_getUserName($arr['id_author']));
		$confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confart&idart=".$arr['id'];
		
		$layout = $W->HBoxItems(
				$W->VBoxItems($W->Label(bab_translate("Date"))->colon()->addClass('widget-strong'), $artdate),
				$W->VBoxItems($W->Label(bab_translate("Author"))->colon()->addClass('widget-strong'), $author)
		)->setHorizontalSpacing(4,'em');
		
		
		$description = $W->VBoxItems(
			$artpath,
			$layout
		)->setVerticalSpacing(.5,'em');
	
		$items[] = array(
				'text' 			=> $arr['title'],
				'description' 	=> $description->display($W->HtmlCanvas()),
				'url'			=> $confirmurl,
				'popup'			=> true,
				'idschi'		=> (int) $arr['idfai']
		);
		
	}
	
	$event->addObject(bab_translate("Waiting articles"), $items);
}



/**
 * Wainting calendar events
 * @param bab_eventBeforeWaitingItemsDisplayed $event
 */
function bab_listWaitingEvents(bab_eventBeforeWaitingItemsDisplayed $event)
{
	
	global $babDB;
	$W = bab_Widgets();


	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if( count($arrschi) == 0 )
	{
		return;	
	}	
		
	$res = $babDB->db_query("SELECT cet.*, ceot.caltype, ceot.id_cal, ceot.idfai  
			from
			".BAB_CAL_EVENTS_TBL." cet ,
			".BAB_CAL_EVENTS_OWNERS_TBL." ceot
			where
			cet.id=ceot.id_event and ceot.idfai in (".$babDB->quote($arrschi).") order by cet.start_date asc
	");
	
	if ($babDB->db_num_rows($res) <= 0)
	{
		return;
	}
	
	$items = array();

	while( $arr = $babDB->db_fetch_assoc($res) )
	{

		$calendar = bab_getICalendars()->getEventCalendar($arr['caltype'].'/'.$arr['id_cal']);

		if ($calendar)
		{
			$start = BAB_DateTime::fromIsoDateTime($arr['start_date']);
			$startdate = bab_shortDate(bab_mktime($arr['start_date']), true);
			
			$layout = $W->HBoxItems(
				$W->VBoxItems($W->Label(bab_translate("Date"))->colon()->addClass('widget-strong'), $W->Label($startdate)),
				$W->VBoxItems($W->Label(bab_translate("Author"))->colon()->addClass('widget-strong'), $W->Label(bab_getUserName($arr['id_creator']))),
				$W->VBoxItems($W->Label(bab_translate("Calendar"))->colon()->addClass('widget-strong'), $W->Label($calendar->getName()))
			)->setHorizontalSpacing(4,'em');
			
			$description = $layout->display($W->HtmlCanvas());
			
			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setContent($arr['description']);
			$editor->setFormat($arr['description_format']);
			$description .= $editor->getHtml();

			$url = $GLOBALS['babUrlScript']."?tg=calendar&idx=approb&evtid=".$arr['uuid']."&idcal=".$arr['parent_calendar']."&relation=".$calendar->getUrlIdentifier()."&dtstart=".$start->getICal();
			
			
			$items[] = array(
				'text' 			=> $arr['title'],
				'description' 	=> $description,
				'url'			=> $url,
				'popup'			=> true,
				'idschi'		=> (int) $arr['idfai']
			);
		}
	}

	$event->addObject(bab_translate("Waiting appointments"), $items);
}