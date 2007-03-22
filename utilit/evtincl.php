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

/**
* @internal SEC1 PR 20/02/2007 FULL
*/




/**
 * Update selected calendars for event
 * Creation and modification
 *
 * @param	int		$id_event
 * @param	array	$idcals
 * 
 * @return	array	calendar id were the event has been inserted
 */
function bab_updateSelectedCalendars($id_event, $idcals) {

	global $babBody, $babDB;
	$arrcals = array();
	
	$res = $babDB->db_query('
		SELECT id_cal FROM '.BAB_CAL_EVENTS_OWNERS_TBL.' WHERE id_event='.$babDB->quote($id_event).'
	');
	
	$associated = array();
	while ($arr = $babDB->db_fetch_assoc($res)) {
		$associated[$arr['id_cal']] = $arr['id_cal'];
	}
	
	

	foreach($idcals as $id_cal)
		{
		$add = false;
		$arr = $babBody->icalendars->getCalendarInfo($id_cal);

		switch($arr['type'])
			{
			case BAB_CAL_USER_TYPE:
				if( $arr['idowner'] ==  $GLOBALS['BAB_SESS_USERID'] )
					{
					$add = true;
					$ustatus = BAB_CAL_STATUS_ACCEPTED;
					}
				elseif( $arr['access'] == BAB_CAL_ACCESS_UPDATE || $arr['access'] == BAB_CAL_ACCESS_SHARED_UPDATE)
					{
					$add = true;
					$ustatus = BAB_CAL_STATUS_NONE;
					}
				elseif( $arr['access'] == BAB_CAL_ACCESS_FULL || $arr['access'] == BAB_CAL_ACCESS_SHARED_FULL)
					{
					$add = true;
					$ustatus = BAB_CAL_STATUS_ACCEPTED;
					}
				break;
			case BAB_CAL_PUB_TYPE:
				if( $arr['idsa'] != 0 )
					{
					$ustatus = BAB_CAL_STATUS_NONE;			
					}
				else
					{
					$ustatus = BAB_CAL_STATUS_ACCEPTED;
					}

				if( $arr['manager'] )
					{
					$add = true;
					}
				break;
			case BAB_CAL_RES_TYPE:
				if( $arr['idsa'] != 0 )
					{
					$ustatus = BAB_CAL_STATUS_NONE;			
					}
				else
					{
					$ustatus = BAB_CAL_STATUS_ACCEPTED;
					}

				if( $arr['manager'] || $arr['add'])
					{
					$add = true;
					}
				break;
			}

		if( $add )
			{

			if (!isset($associated[$id_cal])) {
			
				// add owner

				$babDB->db_query("
					INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." 
						(
							id_event,
							id_cal, 
							status
						) 
					VALUES 
						(
							'".$babDB->db_escape_string($id_event)."',
							'".$babDB->db_escape_string($id_cal)."', 
							'".$babDB->db_escape_string($ustatus)."'
						)
					");
					
				if( ($arr['type'] == BAB_CAL_PUB_TYPE ||  $arr['type'] == BAB_CAL_RES_TYPE) && ($arr['idsa'] != 0) )
					{
					include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
					$idfai = makeFlowInstance($arr['idsa'], "cal-".$id_cal."-".$id_event);
					$babDB->db_query("
						UPDATE ".BAB_CAL_EVENTS_OWNERS_TBL." 
						SET 
							idfai='".$babDB->db_escape_string($idfai)."' 
						where 
							id_event='".$babDB->db_escape_string($id_event)."' 
							AND id_cal='".$babDB->db_escape_string($id_cal)."'
						");
						
					$nfusers = getWaitingApproversFlowInstance($idfai, true);
					notifyEventApprovers($id_event, $nfusers, $arr);
					}
				}
				
			$arrcals[] = $id_cal;
			unset($associated[$id_cal]);
			}
		}
		
	foreach($associated as $id_cal) {
		// remove owner
		
		//$arr = $babBody->icalendars->getCalendarInfo($id_cal);
		

		$babDB->db_query("
			DELETE FROM ".BAB_CAL_EVENTS_OWNERS_TBL." 
				WHERE id_event='".$babDB->db_escape_string($id_event)."' 
				AND id_cal='".$babDB->db_escape_string($id_cal)."'
			");
		
		}

	if( count($arrcals) == 0 )
		{
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'");
		}
		
	return $arrcals;
	
}



/**
 * Create calendar event
 *
 * @param	array	$idcals
 * @param	int		$id_owner
 * @param	string	$title			(text)
 * @param	string	$description	(html)
 * @param	string	$location		(text)
 * @param	int		$startdate		(timestamp)
 * @param	int		$enddate		(timestamp)
 * @param	int		$category
 * @param	string	$color
 * @param	string	$private		(Y|N)
 * @param	string	$lock			(Y|N)
 * @param	string	$free			(Y|N)
 * @param	string	$hash
 * @param	array	$arralert
 *
 * @return	array	calendar id were the event has been inserted
 *
 */
function createEvent($idcals,$id_owner, $title, $description, $location, $startdate, $enddate, $category, $color, $private, $lock, $free, $hash, $arralert)
{

	global $babBody, $babDB;

	bab_editor_record($description);


	$babDB->db_query("insert into ".BAB_CAL_EVENTS_TBL." 
	( title, description, location, start_date, end_date, id_cat, id_creator, color, bprivate, block, bfree, hash) 
	
	values (
		".$babDB->quote($title).", 
		".$babDB->quote($description).", 
		".$babDB->quote($location).", 
		".$babDB->quote(date('Y-m-d H:i:s',$startdate)).", 
		".$babDB->quote(date('Y-m-d H:i:s',$enddate)).", 
		".$babDB->quote($category).", 
		".$babDB->quote($id_owner).", 
		".$babDB->quote($color).", 
		".$babDB->quote($private).", 
		".$babDB->quote($lock).", 
		".$babDB->quote($free).", 
		".$babDB->quote($hash)."
	)
		");
	
	$id_event = $babDB->db_insert_id();
	$arrcals = bab_updateSelectedCalendars($id_event, $idcals);
	

	if(0 !== count($arrcals) && !empty($GLOBALS['BAB_SESS_USERID']) && $arralert !== false )
		{
		$babDB->db_query("
			INSERT INTO ".BAB_CAL_EVENTS_REMINDERS_TBL." 
				(
					id_event, 
					id_user, 
					day, 
					hour, 
					minute, 
					bemail 
				) 
			VALUES 
				(
					'".$babDB->db_escape_string($id_event)."', 
					'".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', 
					'".$babDB->db_escape_string($arralert['day'])."', 
					'".$babDB->db_escape_string($arralert['hour'])."', 
					'".$babDB->db_escape_string($arralert['minute'])."', 
					'".$babDB->db_escape_string($arralert['email'])."'
				)
			");
		}
	
	return $arrcals;
}





/**
 *	$args['startdate'] 	: array('month', 'day', 'year', 'hours', 'minutes')
 *	$args['enddate'] 	: array('month', 'day', 'year', 'hours', 'minutes')
 *	$args['owner'] 		: id of the owner
 *	$args['rrule'] 		: // BAB_CAL_RECUR_DAILY, ...
 *	$args['until'] 		: array('month', 'day', 'year')
 *	$args['rdays'] 		: repeat days array(0,1,2,3,4,5,6)
 *	$args['ndays'] 		: nb days 
 *	$args['nweeks'] 	: nb weeks 
 *	$args['nmonths'] 	: nb weeks 
 *	$args['category'] 	: id of the category
 *	$args['private'] 	: if the event is private
 *	$args['lock'] 		: to lock the event
 *	$args['free'] 		: free event
 *	$args['alert'] 		: array('day', 'hour', 'minute', 'email'=>'Y')
 */
function bab_createEvent($idcals, $args, &$msgerror)
	{
	global $babBody;

	$begin = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $args['startdate']['day'], $args['startdate']['year'] );
	$end = mktime( $args['enddate']['hours'],$args['enddate']['minutes'],0,$args['enddate']['month'], $args['enddate']['day'], $args['enddate']['year'] );


	if( isset($args['rrule']) && $args['rrule'] != 0 )
		{
		$repeatdate = mktime( 23,59,59, $args['until']['month'], $args['until']['day'], $args['until']['year'] );
		}


	if( $begin > $end)
		{
		$msgerror = bab_translate("End date must be older")." !";
		return false;
		}

	$arrnotify = array();

	if( !isset($args['alert']))
		{
		$args['alert'] = false;
		}

	if( isset($args['lock']) && $args['lock'] )
		{
		$args['lock'] = 'Y';
		}
	else
		{
		$args['lock'] = 'N';
		}

	if( isset($args['private']) && $args['private'] )
		{
		$args['private'] = 'Y';
		}
	else
		{
		$args['private'] = 'N';
		}

	if( isset($args['free']) && $args['free'])
		{
		$args['free'] = 'Y';
		}
	else
		{
		$args['free'] = 'N';
		}

	if( !isset($args['color']))
		{
		$args['color'] = '';
		}

	if( !isset($args['location']))
		{
		$args['location'] = '';
		}

	if( isset($args['rrule']) )
		{
		$hash = "R_".md5(uniqid(rand(),1));
		$duration = $end - $begin;
		switch( $args['rrule'] )
			{
			case BAB_CAL_RECUR_WEEKLY:

				if( !isset($args['nweeks']) )
					{
					$args['nweeks'] = 1;
					}

				$rtime = 24*3600*7*$args['nweeks'];

				if( $duration > $rtime)
					{
					$msgerror = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;					
					}

				if( !isset($args['rdays']) )
					{
					$day = $args['startdate']['day'];
					$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $day, $args['startdate']['year'] );
					do
						{
						$arrf = createEvent($idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
						$arrnotify = array_unique(array_merge($arrnotify, $arrf));
						$day += 7*$args['nweeks'];
						$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $day, $args['startdate']['year'] );
						}
					while( $time < $repeatdate );
					}
				else
					{
					if( $duration > 24*3600 )
						{
						$msgerror = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
						return false;					
						}

					for( $i = 0; $i < count($args['rdays']); $i++ )
						{
						$delta = $args['rdays'][$i] - Date("w", $begin);
						if( $delta < 0 )
							{
							$delta = 7 - Abs($delta);
							}

						$day = $args['startdate']['day']+$delta;
						$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $day, $args['startdate']['year']);
						do
							{
							$arrf = createEvent($idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
							$day += 7*$args['nweeks'];					
							$arrnotify = array_unique(array_merge($arrnotify, $arrf));
							$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $day, $args['startdate']['year'] );
							}
						while( $time < $repeatdate );
						}
					}

				break;
			case BAB_CAL_RECUR_MONTHLY: /* monthly */
				if( !isset($args['nmonths']) || empty($args['nmonths']))
					{
					$args['nmonths'] = 1;
					}

				if( $duration > 24*3600*28*$args['nmonths'])
					{
					$msgerror = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;					
					}

				$time = $begin;
				do
					{
					$arrf = createEvent($idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
					$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,date("m", $time)+$args['nmonths'], date("j", $time), date("Y", $time) );
					$arrnotify = array_unique(array_merge($arrnotify, $arrf));
					}
				while( $time < $repeatdate );
				break;
			case BAB_CAL_RECUR_YEARLY: /* yearly */
				if( !isset($args['nyears']) || empty($args['nyears']))
					{
					$args['nyears'] = 1;
					}
				if( $duration > 24*3600*365*$args['nyears'])
					{
					$msgerror = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;					
					}
				$time = $begin;
				do
					{
					$arrf = createEvent($idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
					$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,date("m", $time), date("j", $time), date("Y", $time)+$args['nyears'] );
					$arrnotify = array_unique(array_merge($arrnotify, $arrf));
					}
				while( $time < $repeatdate );
				break;
			case BAB_CAL_RECUR_DAILY: /* daily */
			default:
				if( !isset($args['ndays']) || empty($args['ndays']))
					{
					$args['ndays'] = 1;
					}
				$rtime = 24*3600*$args['ndays'];

				if( $duration > $rtime )
					{
					$msgerror = bab_translate("The duration of the event must be shorter than how frequently it occurs")." !";
					return false;
					}

				$day = $args['startdate']['day'];
				$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $day, $args['startdate']['year']  );
				do
					{
					$arrf = createEvent($idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
					$day += $args['ndays'];
					$arrnotify = array_unique(array_merge($arrnotify, $arrf));
					$time = mktime( $args['startdate']['hours'],$args['startdate']['minutes'],0,$args['startdate']['month'], $day, $args['startdate']['year']);
					}
				while( $time < $repeatdate );
				break;
			}

		}
	else
		{
		$arrnotify = createEvent($idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $begin, $end, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], '', $args['alert']);
		
		
		}
		
		
	include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
	$endperiod = isset($repeatdate) ? $repeatdate : $end;
	$event = new bab_eventPeriodModified($begin, $endperiod, false);
	$event->types = BAB_PERIOD_CALEVENT;
	bab_fireEvent($event);


	if( count($arrnotify) > 0 )
		{
		$arrusr = array();
		$arrres = array();
		$arrpub = array();
		for( $i = 0; $i < count($arrnotify); $i++ )
			{
			$arr = $babBody->icalendars->getCalendarInfo($arrnotify[$i]);

			switch($arr['type'])
				{
				case BAB_CAL_USER_TYPE:
					if( $arr['idowner'] != $GLOBALS['BAB_SESS_USERID'] )
						{
						$arrusr[] = $arrnotify[$i];
						}
					break;
				case BAB_CAL_PUB_TYPE:
					if( $arr['idsa'] == 0 )
						{
						$arrpub[] = $arrnotify[$i];
						}
					break;
				case BAB_CAL_RES_TYPE:
					if( $arr['idsa'] == 0 )
						{
						$arrres[] = $arrnotify[$i];
						}
					break;
				}
			}
		
		$startdate = bab_longDate($begin);
		$enddate = bab_longDate($end);
		if( count($arrusr) > 0 )
			{
			notifyPersonalEvent($args['title'], $args['description'], $startdate, $enddate, $arrusr);
			}
		if( count($arrres) > 0 )
			{
			notifyResourceEvent($args['title'], $args['description'], $startdate, $enddate, $arrres);
			}
		if( count($arrpub) > 0 )
			{
			notifyPublicEvent($args['title'], $args['description'], $startdate, $enddate, $arrpub);
			}
		}
		
	return true;	
	}


function confirmEvent($evtid, $idcal, $bconfirm, $comment, $bupdrec)
{
	global $babDB, $babBody;
	$arr = $babBody->icalendars->getCalendarInfo($idcal);
	
	$arrevtids = array();
	if( $bupdrec == 1)
		{
		list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($evtid)."'"));
		if( !empty($hash) &&  $hash[0] == 'R')
			{
			$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$babDB->db_escape_string($hash)."'");
			while($row = $babDB->db_fetch_array($res))
				{
				$arrevtids[] = $row['id']; 	
				}
			}
		else
			{
			$arrevtids[] = $evtid; 	
			}
		}
	else
		{
		$arrevtids[] = $evtid; 	
		}
	if( $bconfirm == "Y" )
		{
		$bconfirm = BAB_CAL_STATUS_ACCEPTED;
		}
	else
		{
		$bconfirm = BAB_CAL_STATUS_DECLINED;
		}

	switch($arr['type'])
	{
		case BAB_CAL_USER_TYPE:
			if( count($arrevtids) > 0 && ($arr['access'] == BAB_CAL_ACCESS_FULL || $arr['access'] == BAB_CAL_ACCESS_SHARED_FULL))
				{
				$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$babDB->db_escape_string($bconfirm)."' where id_event IN (".$babDB->quote($arrevtids).") and id_cal=".$babDB->quote($idcal));
				notifyEventApprobation($evtid, $bconfirm, $comment, bab_translate("Personal calendar"));
				}
			break;
		case BAB_CAL_PUB_TYPE:
		case BAB_CAL_RES_TYPE:
			if( count($arrevtids) > 0 )
			{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				$calinfo = $babBody->icalendars->getCalendarInfo($idcal);
				$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event IN (".$babDB->quote($arrevtids).") and id_cal=".$babDB->quote($idcal)." and idfai != '0'");
				while( $row = $babDB->db_fetch_array($res))
					{
					if( in_array($row['idfai'], $arrschi))
						{
						$ret = updateFlowInstance($row['idfai'], $GLOBALS['BAB_SESS_USERID'], ($bconfirm == 'Y'? true: false ));
						switch($ret)
							{
							case 0:
								deleteFlowInstance($row['idfai']);
								$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$babDB->db_escape_string($bconfirm)."', idfai='0' where id_event='".$babDB->db_escape_string($row['id_event'])."'  and id_cal='".$babDB->db_escape_string($row['id_cal'])."'");
								notifyEventApprobation($evtid, $bconfirm, $comment, $arr['name']);
								break;
							case 1:
								deleteFlowInstance($row['idfai']);
								$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$babDB->db_escape_string($bconfirm)."', idfai='0' where id_event='".$babDB->db_escape_string($row['id_event'])."'  and id_cal='".$babDB->db_escape_string($row['id_cal'])."'");
								notifyEventApprobation($evtid, $bconfirm, $comment, $arr['name']);
						
								$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($row['id_event'])."'"));
								
								if (BAB_CAL_RES_TYPE == $arr['type']) {
									notifyResourceEvent($rr['title'], $rr['description'], bab_longDate(bab_mktime($rr['start_date'])), bab_longDate(bab_mktime($rr['end_date'])), array($idcal));
								} else {
									notifyPublicEvent($rr['title'], $rr['description'], bab_longDate(bab_mktime($rr['start_date'])), bab_longDate(bab_mktime($rr['end_date'])), array($idcal));
								}
								
								
								break;
							default:
								$nfusers = getWaitingApproversFlowInstance($row['idfai'], true);
								if( count($nfusers) > 0 )
									{
									notifyEventApprovers($evtid, $nfusers, $calinfo);
									}
								break;
							}
						}
					}
				}
			}
			break;
	}

	if( count($arrevtids) > 0 )
	{
		// delete declined events
		$res = $babDB->db_query("select count(id_cal) as total, id_event from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event IN (".$babDB->quote($arrevtids).") and status in (".BAB_CAL_STATUS_ACCEPTED.",".BAB_CAL_STATUS_NONE.") group by id_event");
		$arrtmp =array();
		while($arr = $babDB->db_fetch_array($res))
		{
			$arrtmp[] = $arr['id_event'];
		}

		for( $i= 0; $i < count($arrevtids); $i++ )
		{
			if( count($arrtmp) == 0 || !in_array($arrevtids[$i], $arrtmp))
			{
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id=".$babDB->quote($arrevtids[$i]));
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event=".$babDB->quote($arrevtids[$i]));
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event=".$babDB->quote($arrevtids[$i]));
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event=".$babDB->quote($arrevtids[$i]));
			}
		}
	}

}





class clsNotifyEvent {

	/**
	 * @private
	 */
	var $vars = array();
	
	/**
	 * @public
	 */
	var $title;
	var $description;
	var $startdate;
	var $enddate;
	var $descriptiontxt;
	var $titletxt;
	var $startdatetxt;
	var $enddatetxt;

	function asText() {
		$this->title = $this->vars['title'];
		$this->description = strip_tags(bab_toHtml($this->vars['description'], BAB_HTML_REPLACE_MAIL));
		$this->startdate = $this->vars['startdate'];
		$this->enddate = $this->vars['enddate'];
		
		$this->descriptiontxt = bab_translate("Description");
		$this->titletxt = bab_translate("Title");
		$this->startdatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->calendartxt = bab_translate("Calendar");
	}

	function asHtml() {
		$this->title = bab_toHtml($this->vars['title']);
		$this->description = bab_toHtml($this->vars['description'], BAB_HTML_REPLACE_MAIL);
		$this->startdate = bab_toHtml($this->vars['startdate']);
		$this->enddate = bab_toHtml($this->vars['enddate']);
		
		$this->descriptiontxt = bab_translate("Description");
		$this->titletxt = bab_translate("Title");
		$this->startdatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->calendartxt = bab_translate("Calendar");
	}
}




function notifyPersonalEvent($title, $description, $startdate, $enddate, $idcals)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyAttendees"))
		{
		class clsNotifyAttendees extends clsNotifyEvent
			{
			var $message;
			var $calendar;

			function clsNotifyAttendees($title, $description, $startdate, $enddate)
				{
				
				$this->message = bab_translate("New appointement");
				$this->calendar = bab_translate("Personal calendar");

				$this->vars['title'] 		= $title;
				$this->vars['description'] 	= $description;
				$this->vars['startdate'] 	= $startdate;
				$this->vars['enddate'] 		= $enddate;

				}
				
			}
		}
	

	if( count($idcals) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;

		$res=$babDB->db_query("select ut.firstname, ut.lastname, ut.email from ".BAB_USERS_TBL." ut left join ".BAB_CALENDAR_TBL." ct on ut.id=ct.owner where ct.type='1' and ct.id in (".$babDB->quote($idcals).")");

		while( $arr = $babDB->db_fetch_array($res))
			{
			$mail->mailBcc($arr['email']);
			}

		if( empty($GLOBALS['BAB_SESS_USER']))
			{
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			}
		else
			{
			$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
			}

		$tempc = new clsNotifyAttendees($title, $description, $startdate, $enddate);
		$tempc->asHtml();
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
		$mail->mailSubject(bab_translate("New appointement"));
		$mail->mailBody($message, "html");

		$tempc->asText();
		$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}


function notifyPublicEvent($title, $description, $startdate, $enddate, $idcals)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyPublicEvent"))
		{
		class clsNotifyPublicEvent extends clsNotifyEvent
			{
			var $title;
			var $message;
			var $description;
			var $startdate;
			var $enddate;
			var $descriptiontxt;
			var $titletxt;
			var $startdatetxt;
			var $enddatetxt;

			function clsNotifyPublicEvent($title, $description, $startdate, $enddate)
				{
				$this->message = bab_translate("New appointement");
				$this->calendar = "";
				
				$this->vars['title'] 		= $title;
				$this->vars['description'] 	= $description;
				$this->vars['startdate'] 	= $startdate;
				$this->vars['enddate'] 		= $enddate;
				}
			}
		}
	

	if( count($idcals) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;

		if( empty($GLOBALS['BAB_SESS_USER']))
			{
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			}
		else
			{
			$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
			}
		$tempc = new clsNotifyPublicEvent($title, $description, $startdate, $enddate);

		$arrusers = array();
		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals, BAB_CAL_PUB_TYPE);
			$mail->mailSubject(bab_translate("New appointement"));
			
			
			
			$tempc->asHtml();
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailBody($message, "html");
			
			$tempc->asText();
			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$arrusers = cal_usersToNotiy($idcals[$i], BAB_CAL_PUB_TYPE, 0);
			

			if( $arrusers )
				{
				$count = 0;
				while(list(,$arr) = each($arrusers))
					{
					$mail->mailBcc($arr['email'], $arr['name']);
					$count++;

					if( $count > 25 )
						{
						$mail->send();
						$mail->clearBcc();
						$mail->clearTo();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->send();
					$mail->clearBcc();
					$mail->clearTo();
					$count = 0;
					}
				}		
			}
		}
	}





	
function cal_usersToNotiy($id_cal, $cal_type, $id_owner) {

	include_once $GLOBALS['babInstallPath']."admin/acl.php";

	global $babDB;
	$arrusers = array();
	
	switch($cal_type)
		{
		case BAB_CAL_USER_TYPE:
			if( !isset($arrusers[$id_owner]))
				{
				$arrusers[$id_owner] = array(
						'name' => bab_getUserName($id_owner),
						'email' => bab_getUserEmail($id_owner)
					);
				}
			break;
			
		case BAB_CAL_PUB_TYPE:
			$arr = aclGetAccessUsers(BAB_CAL_PUB_GRP_GROUPS_TBL, $id_cal);
			$arrusers = array_merge($arrusers, $arr);
			break;
			
		case BAB_CAL_RES_TYPE:
			$arr = aclGetAccessUsers(BAB_CAL_RES_GRP_GROUPS_TBL, $id_cal);
			$arrusers = array_merge($arrusers, $arr);
			break;
		}
		
	if (isset($GLOBALS['BAB_SESS_USERID'])) {
		unset($arrusers[$GLOBALS['BAB_SESS_USERID']]);
	}
	
	return $arrusers;
}







function notifyResourceEvent($title, $description, $startdate, $enddate, $idcals)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyResourceEvent"))
		{
		class clsNotifyResourceEvent extends clsNotifyEvent
			{

			var $message;
			var $calendar;

			function clsNotifyResourceEvent($title, $description, $startdate, $enddate)
				{
				$this->message = bab_translate("New appointement");
				$this->calendar = "";
				
				$this->vars['title'] 		= $title;
				$this->vars['description'] 	= $description;
				$this->vars['startdate'] 	= $startdate;
				$this->vars['enddate'] 		= $enddate;
				}
			}
		}
	

	if( count($idcals) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;

		if( empty($GLOBALS['BAB_SESS_USER']))
			{
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			}
		else
			{
			$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
			}
		$tempc = new clsNotifyResourceEvent($title, $description, $startdate, $enddate);
		

		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals[$i], BAB_CAL_RES_TYPE);
			$mail->mailSubject(bab_translate("New appointement"));
			
			$tempc->asHtml();
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailBody($message, "html");
			
			$tempc->asText();
			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);
			
			$arrusers = cal_usersToNotiy($idcals[$i], BAB_CAL_RES_TYPE, 0);
			

			if( $arrusers )
				{
				$count = 0;
				while(list(,$arr) = each($arrusers))
					{
					$mail->mailBcc($arr['email'], $arr['name']);
					$count++;

					if( $count > 25 )
						{
						$mail->send();
						$mail->clearBcc();
						$mail->clearTo();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->send();
					$mail->clearBcc();
					$mail->clearTo();
					$count = 0;
					}
				}	
			}		
		}
	}


function notifyEventApprobation($evtid, $bconfirm, $raison, $calname)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyEventApprobation"))
		{
		class clsNotifyEventApprobation extends clsNotifyEvent
			{
			var $message;
			var $calendar;

			function clsNotifyEventApprobation(&$evtinfo, $raison, $calname)
				{
				$this->message = $raison;
				$this->calendar = $calname;
				
				$this->vars['title'] 		= $evtinfo['title'];
				$this->vars['description'] 	= $evtinfo['description'];
				$this->vars['startdate'] 	= bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->vars['enddate'] 		= bab_longDate(bab_mktime($evtinfo['end_date']));
				}
			}
		}
	

	$mail = bab_mail();
	if( $mail == false )
		return;

	$res=$babDB->db_query("select cet.*, ut.firstname, ut.lastname, ut.email from ".BAB_CAL_EVENTS_TBL." cet left join ".BAB_USERS_TBL." ut on ut.id = cet.id_creator where cet.id='".$babDB->db_escape_string($evtid)."'");
	$evtinfo = $babDB->db_fetch_array($res);

	$mail->mailTo($evtinfo['email'], bab_composeUserName($evtinfo['firstname'], $evtinfo['lastname']));
	$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);

	$tempc = new clsNotifyEventApprobation($evtinfo, $raison, $calname);
	

	if( $bconfirm == BAB_CAL_STATUS_ACCEPTED)
		{
		$subject = bab_translate("Appointement accepted by ");
		}
	else
		{
		$subject = bab_translate("Appointement declined by ");
		}

	$subject .= $GLOBALS['BAB_SESS_USER'];
	$mail->mailSubject($subject);
	
	$tempc->asHtml();
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
	$mail->mailBody($message, "html");
	
	$tempc->asText();
	$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}
	
	
	
	
	
	
	

function notifyEventUpdate($evtid, $bdelete)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsnotifyEventUpdate"))
		{
		class clsnotifyEventUpdate extends clsNotifyEvent
			{
			var $message;
			var $calendar;

			function clsnotifyEventUpdate(&$evtinfo)
				{
				$this->message = '';
				$this->calendar = '';
				
				$this->vars['title'] 		= $evtinfo['title'];
				$this->vars['description'] 	= $evtinfo['description'];
				$this->vars['startdate'] 	= bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->vars['enddate'] 		= bab_longDate(bab_mktime($evtinfo['end_date']));
				}
			}
		}
	

	$mail = bab_mail();
	if( $mail == false )
		return;

	$evtinfo=$babDB->db_fetch_array($babDB->db_query("select cet.* from ".BAB_CAL_EVENTS_TBL." cet where cet.id='".$babDB->db_escape_string($evtid)."'"));

	$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);

	$tempc =& new clsnotifyEventUpdate($evtinfo);

	if( $bdelete )
		{
		$subject = bab_translate("Appointment canceled by ");
		$tempc->message = bab_translate("The following appointment has been canceled");
		}
	else
		{
		$subject = bab_translate("Appointment modifed by ");
		$tempc->message = bab_translate("The following appointment has been modified");
		}

	$subject .= $GLOBALS['BAB_SESS_USER'];
	$mail->mailSubject($subject);

	$res = $babDB->db_query("
		SELECT 
			ceot.*, 
			ct.type, 
			ct.owner 
		FROM 
			".BAB_CAL_EVENTS_OWNERS_TBL." ceot 
			left join ".BAB_CALENDAR_TBL." ct on ct.id=ceot.id_cal 
		WHERE 
			ceot.id_event='".$babDB->db_escape_string($evtid)."' 
			AND status IN('".BAB_CAL_STATUS_ACCEPTED."', '".BAB_CAL_STATUS_NONE."')
		");
	while( $arr = $babDB->db_fetch_array($res) )
		{
		$arrusers = cal_usersToNotiy($arr['id_cal'], $arr['type'], $arr['owner']);


		if($arrusers)
			{
			$calinfo = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
			$tempc->calendar = $calinfo['name'];
			$tempc->asHtml();
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailBody($message, "html");

			$tempc->asText();
			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);
			
			$count = 0;
			while(list(,$row) = each($arrusers))
				{
				$mail->mailBcc($row['email'], $row['name']);
				$count++;

				if( $count > 25 )
					{
					$mail->send();
					$mail->clearBcc();
					$mail->clearTo();
					$count = 0;
					}

				}

			if( $count > 0 )
				{
				$mail->send();
				$mail->clearBcc();
				$mail->clearTo();
				$count = 0;
				}
			}


		}

	}


function notifyEventApprovers($id_event, $users, $calinfo)
	{
	global $babDB, $babBody, $babAdminEmail;

	if(!class_exists("notifyEventApproversCls"))
		{
		class notifyEventApproversCls 
			{
			var $articletitle;
			var $message;
			var $from;
			var $author;
			var $category;
			var $categoryname;
			var $title;
			var $site;
			var $sitename;
			var $date;
			var $dateval;
			
			var $tmp_title;
			var $tmp_desc;
			var $tmp_calendar;


			function notifyEventApproversCls($id_event, $calinfo)
				{
				global $babDB;

				$this->message = bab_translate("A new event has been scheduled");
				$evtinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'"));

				$this->tmp_desc = $evtinfo['description'];
				$this->descriptiontxt = bab_translate("Description");
				$this->startdate = bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddate = bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->enddatetxt = bab_translate("End date");
				$this->titletxt = bab_translate("Title");
				$this->tmp_title = $evtinfo['title'];
				if( $calinfo['type'] == BAB_CAL_PUB_TYPE )
					$this->calendartxt = bab_translate("Public calendar");
				else
					$this->calendartxt = bab_translate("Resource calendar");
					
				
				$this->tmp_calendar = $calinfo['name'];
				}
				
			function asHtml() {
				$this->title = bab_toHtml($this->tmp_title);
				$this->description = bab_toHtml($this->tmp_desc, BAB_HTML_REPLACE_MAIL);
				$this->calendar = bab_toHtml($this->tmp_calendar);
				}
				
			function asText() {
				$this->title = $this->tmp_title;
				$this->description = strip_tags(bab_toHtml($this->tmp_desc, BAB_HTML_REPLACE_MAIL));
				$this->calendar = $this->tmp_calendar;
				}
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;

	if( count($users) > 0 )
		{
		$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->mailBcc($arr['email']);
			}
		}
	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("New waiting event"));

	$tempa = new notifyEventApproversCls($id_event, $calinfo);
	$tempa->asHtml();
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "eventwait"));
	$mail->mailBody($message, "html");

	$tempa->asText();
	$message = bab_printTemplate($tempa,"mailinfo.html", "eventwaittxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


function bab_deleteEvent($idevent)
{
	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";

	$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($idevent)."'");
	$res2 = $babDB->db_query("select idfai from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$babDB->db_escape_string($idevent)."'");
	while( $rr = $babDB->db_fetch_array($res2) )
		{
		if( $rr['idfai'] != 0 )
			{
			deleteFlowInstance($rr['idfai']);
			}
		}
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event=".$babDB->quote($idevent));
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_NOTES_TBL." where id_event=".$babDB->quote($idevent));
	$babDB->db_query("delete from ".BAB_CAL_EVENTS_REMINDERS_TBL." where id_event=".$babDB->quote($idevent));
}

?>