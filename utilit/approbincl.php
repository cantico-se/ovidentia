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
	
	bab_listWaitingEvents($event);
}







function bab_listWaitingEvents(bab_eventBeforeWaitingItemsDisplayed $event)
{
	
	global $babDB;
	$W = bab_Widgets();


	$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	if( count($arrschi) == 0 )
	{
		return;	
	}	
		
	$res = $babDB->db_query("SELECT cet.*, ceot.caltype, ceot.id_cal
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
				'idschi'		=> 0
			);
		}
	}

	$event->addObject(bab_translate("Waiting appointments"), $items);
}