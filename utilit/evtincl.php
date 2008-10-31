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
 * @param	array	$exclude
 * 
 * @return	array	calendar id were the event has been inserted
 */
function bab_updateSelectedCalendars($id_event, $idcals, &$exclude) {

	global $babBody, $babDB;
	$arrcals = array();
	$exclude = array();
	
	$res = $babDB->db_query('SELECT * FROM '.BAB_CAL_EVENTS_TBL.' WHERE id='.$babDB->quote($id_event));
	$event = $babDB->db_fetch_assoc($res);
	
	
	$startdate = bab_longDate(bab_mktime($event['start_date']));
	$enddate = bab_longDate(bab_mktime($event['end_date']));
	
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
			
				// add calendar to event

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
				else 
					{
						$exclude[] = $id_cal;
						cal_notify(
							$event['title'], 
							$event['description'], 
							$event['location'], 
							$startdate, 
							$enddate, 
							$id_cal, 
							$arr['type'], 
							$arr['idowner'],
							bab_translate("New appointement")
						);
					}
				}
				
			$arrcals[] = $id_cal;
			unset($associated[$id_cal]);
			}
		}
		
	foreach($associated as $id_cal) {
		// remove calendar from event

		$babDB->db_query("
			DELETE FROM ".BAB_CAL_EVENTS_OWNERS_TBL." 
				WHERE id_event='".$babDB->db_escape_string($id_event)."' 
				AND id_cal='".$babDB->db_escape_string($id_cal)."'
			");
			
		$arr = $babBody->icalendars->getCalendarInfo($id_cal);
		$exclude[] = $id_cal;
		cal_notify(
			$event['title'], 
			$event['description'], 
			$event['location'], 
			$startdate, 
			$enddate, 
			$id_cal, 
			$arr['type'], 
			$arr['idowner'],
			sprintf(
				bab_translate("The calendar %s has been removed from an appointement"), 
				$arr['name']
				)
			);
		
		}

	if( count($arrcals) == 0 )
		{
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'");
		}
		
	return $arrcals;
	
}



/**
 * Send a generic notification for create/delete event
 * or add a calendar to event
 * or remove calendar from event
 *
 * @param	string	$title				event title
 * @param	string	$description		event description
 * @param	string	$startdate			internationalized string
 * @param	string	$enddate			internationalized string
 * @param	int		$id_cal
 * @param	int		$calendar_type
 * @param	int		$calendar_idowner	
 * @param	string	$message			used as mail subject and in mail body
 */
function cal_notify($title, $description, $location, $startdate, $enddate, $id_cal, $calendar_type, $calendar_idowner, $message) {


	switch($calendar_type)
	{
	case BAB_CAL_USER_TYPE:
		if( $calendar_idowner != $GLOBALS['BAB_SESS_USERID'] )
			{
			notifyPersonalEvent(
				$title, 
				$description, 
				$location, 
				$startdate, 
				$enddate, 
				array($id_cal),
				$message
				);
			}
		break;
		
	case BAB_CAL_PUB_TYPE:

		notifyPublicEvent(
			$title, 
			$description, 
			$location, 
			$startdate, 
			$enddate, 
			array($id_cal),
			$message
			);

		break;
		
	case BAB_CAL_RES_TYPE:

		notifyResourceEvent(
			$title, 
			$description, 
			$location, 
			$startdate, 
			$enddate, 
			array($id_cal),
			$message
			);

		break;
	}

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

	require_once $GLOBALS['babInstallPath'].'utilit/uuid.php';

	$babDB->db_query("insert into ".BAB_CAL_EVENTS_TBL." 
	( title, description, location, start_date, end_date, id_cat, id_creator, color, bprivate, block, bfree, hash, date_modification, id_modifiedby, uuid) 
	
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
		".$babDB->quote($hash).",
		now(),
		".$babDB->quote($id_owner).",
		".$babDB->quote(bab_uuid())."
	)
		");
	
	$id_event = $babDB->db_insert_id();
	$exclude = array();
	$arrcals = bab_updateSelectedCalendars($id_event, $idcals, $exclude);
	

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
 *
 * @param	array		$args
 *
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
 *	$args['selected_calendars']
 *
 * @param	string		&$msgerror
 * @param	string		[$action_function]
 */
function bab_createEvent($args, &$msgerror, $action_function = 'createEvent')
	{
	global $babBody;
	
	$idcals = $args['selected_calendars'];


	$begin 	= bab_event_posted::getTimestamp($args['startdate']);
	$end 	= bab_event_posted::getTimestamp($args['enddate']);
	
	
	if( empty($args['title']))
		{
		$msgerror = bab_translate("You must provide a title");
		return false;
	}
	
	
	if (isset($args['until'])) {
		$repeatdate = 86400 + bab_event_posted::getTimestamp($args['until']);
			
		if( $repeatdate < $end) {
			$msgerror = bab_translate("Repeat date must be older than end date");
			return false;
		}
	}


	if( $begin > $end)
		{
		$msgerror = bab_translate("End date must be older");
		return false;
		}
		
	if(0 === count($idcals))
		{
		$msgerror = bab_translate("You must select at least one calendar type");
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
						$arrf = call_user_func($action_function, $idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
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
							$arrf = call_user_func($action_function, $idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
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
					$arrf = call_user_func($action_function, $idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
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
					$arrf = call_user_func($action_function, $idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
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
					$arrf = call_user_func($action_function, $idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $time, $time + $duration, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], $hash, $args['alert']);
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
		$arrnotify = call_user_func($action_function, $idcals, $args['owner'], $args['title'], $args['description'], $args['location'], $begin, $end, $args['category'], $args['color'], $args['private'], $args['lock'], $args['free'], '', $args['alert']);
		
		
		}
		
		
	// if event creation, call event period modified
	if ('createEvent' === $action_function) { 

		include_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';
		$endperiod = isset($repeatdate) ? $repeatdate : $end;
		$event = new bab_eventPeriodModified($begin, $endperiod, false);
		$event->types = BAB_PERIOD_CALEVENT;
		bab_fireEvent($event);

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
									notifyResourceEvent(
										$rr['title'], 
										$rr['description'], 
										$rr['location'], 
										bab_longDate(bab_mktime($rr['start_date'])), 
										bab_longDate(bab_mktime($rr['end_date'])), 
										array($idcal),
										bab_translate('The following appointment has been validated')
									);
								} else {
									notifyPublicEvent(
										$rr['title'], 
										$rr['description'], 
										$rr['location'], 
										bab_longDate(bab_mktime($rr['start_date'])), 
										bab_longDate(bab_mktime($rr['end_date'])), 
										array($idcal),
										bab_translate('The following appointment has been validated')
									);
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
	var $location;
	var $startdate;
	var $enddate;
	var $descriptiontxt;
	var $locationtxt;
	var $titletxt;
	var $startdatetxt;
	var $enddatetxt;
	var $message;

	function asText() {
		$this->title = $this->vars['title'];
		$this->description = strip_tags(bab_toHtml($this->vars['description'], BAB_HTML_REPLACE_MAIL));
		$this->startdate = $this->vars['startdate'];
		$this->enddate = $this->vars['enddate'];
		$this->message = $this->vars['message'];
		$this->location = strip_tags(bab_toHtml($this->vars['location'], BAB_HTML_REPLACE_MAIL));
		
		$this->descriptiontxt = bab_translate("Description");
		$this->titletxt = bab_translate("Title");
		$this->startdatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->calendartxt = bab_translate("Calendar");
		$this->locationtxt = bab_translate("Location");
	}

	function asHtml() {
		$this->title = bab_toHtml($this->vars['title']);
		$this->description = bab_toHtml($this->vars['description'], BAB_HTML_REPLACE_MAIL);
		$this->startdate = bab_toHtml($this->vars['startdate']);
		$this->enddate = bab_toHtml($this->vars['enddate']);
		$this->message = bab_toHtml($this->vars['message']);
		$this->location = bab_toHtml($this->vars['location'], BAB_HTML_REPLACE_MAIL);
		
		$this->descriptiontxt = bab_translate("Description");
		$this->titletxt = bab_translate("Title");
		$this->startdatetxt = bab_translate("Begin date");
		$this->enddatetxt = bab_translate("End date");
		$this->calendartxt = bab_translate("Calendar");
		$this->locationtxt = bab_translate("Location");
	}
}




function notifyPersonalEvent($title, $description, $location, $startdate, $enddate, $idcals, $message)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyAttendees"))
		{
		class clsNotifyAttendees extends clsNotifyEvent
			{
			var $calendar;

			function clsNotifyAttendees($title, $description, $location, $startdate, $enddate, $message)
				{
				
				$this->message = $message;
				$this->calendar = bab_translate("Personal calendar");

				$this->vars['title'] 		= $title;
				$this->vars['description'] 	= $description;
				$this->vars['startdate'] 	= $startdate;
				$this->vars['enddate'] 		= $enddate;
				$this->vars['message'] 		= $message;
				$this->vars['location'] 	= $location;
				}
				
			}
		}
	

	if( count($idcals) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;
		$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

		$res=$babDB->db_query("select ut.firstname, ut.lastname, ut.email from ".BAB_USERS_TBL." ut left join ".BAB_CALENDAR_TBL." ct on ut.id=ct.owner where ct.type='1' and ct.id in (".$babDB->quote($idcals).")");

		while( $arr = $babDB->db_fetch_array($res))
			{
			$mail->$mailBCT($arr['email']);
			}

		if( empty($GLOBALS['BAB_SESS_USER']))
			{
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			}
		else
			{
			$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
			}

		$mail->mailSubject($message);

		$tempc = new clsNotifyAttendees($title, $description, $location, $startdate, $enddate, $message);
		$tempc->asHtml();
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
		
		$mail->mailBody($message, "html");

		$tempc->asText();
		$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}


function notifyPublicEvent($title, $description, $location, $startdate, $enddate, $idcals, $message)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyPublicEvent"))
		{
		class clsNotifyPublicEvent extends clsNotifyEvent
			{
			var $calendar;


			function clsNotifyPublicEvent($title, $description, $location, $startdate, $enddate, $message)
				{
				$this->message = $message;
				$this->calendar = "";
				
				$this->vars['title'] 		= $title;
				$this->vars['description'] 	= $description;
				$this->vars['startdate'] 	= $startdate;
				$this->vars['enddate'] 		= $enddate;
				$this->vars['message'] 		= $message;
				$this->vars['location'] 	= $location;
				}
			}
		}
	

	if( count($idcals) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;
		$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
		$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

		if( empty($GLOBALS['BAB_SESS_USER']))
			{
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			}
		else
			{
			$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
			}
		$tempc = new clsNotifyPublicEvent($title, $description, $location, $startdate, $enddate, $message);

		$arrusers = array();
		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals, BAB_CAL_PUB_TYPE);
			$mail->mailSubject($message);
			
			
			
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
				reset($arrusers);
				while(list(,$arr) = each($arrusers))
					{
					$mail->$mailBCT($arr['email'], $arr['name']);
					$count++;

					if( $count > $babBody->babsite['mail_maxperpacket'] )
						{
						$mail->send();
						$mail->$clearBCT();
						$mail->clearTo();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->send();
					$mail->$clearBCT();
					$mail->clearTo();
					$count = 0;
					}
				}		
			}
		}
	}





/**
 * Get users to notify for a calendar, do not notify a person twice in the same refresh
 * @param	int		$id_cal
 * @param	int		$cal_type
 * @param 	int 	$id_owner
 * @return 	array
 */
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
	
	static $sent = NULL;
	
	if (NULL === $sent) {
		$sent = $arrusers;
	} else {
		
		foreach($arrusers as $id_user => $arr) {
			if (isset($sent[$id_user])) {
				unset($arrusers[$id_user]);
			} else {
				$sent[$id_user] = $arr;
			}
		}
	}
	
	return $arrusers;
}







function notifyResourceEvent($title, $description, $location, $startdate, $enddate, $idcals, $message)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyResourceEvent"))
		{
		class clsNotifyResourceEvent extends clsNotifyEvent
			{

			var $calendar;

			function clsNotifyResourceEvent($title, $description, $location, $startdate, $enddate, $message)
				{
				$this->calendar = "";
				
				$this->vars['title'] 		= $title;
				$this->vars['description'] 	= $description;
				$this->vars['startdate'] 	= $startdate;
				$this->vars['enddate'] 		= $enddate;
				$this->vars['message'] 		= $message;
				$this->vars['location'] 	= $location;
				}
			}
		}
	

	if( count($idcals) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;
		$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
		$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

		if( empty($GLOBALS['BAB_SESS_USER']))
			{
			$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
			}
		else
			{
			$mail->mailFrom($GLOBALS['BAB_SESS_EMAIL'], $GLOBALS['BAB_SESS_USER']);
			}
		$tempc = new clsNotifyResourceEvent($title, $description, $location, $startdate, $enddate, $message);
		

		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals[$i], BAB_CAL_RES_TYPE);
			$mail->mailSubject($message);
			
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
				reset($arrusers);
				while(list(,$arr) = each($arrusers))
					{
					$mail->$mailBCT($arr['email'], $arr['name']);
					$count++;

					if( $count > $babBody->babsite['mail_maxperpacket'] )
						{
						$mail->send();
						$mail->$clearBCT();
						$mail->clearTo();
						$count = 0;
						}
					}

				if( $count > 0 )
					{
					$mail->send();
					$mail->$clearBCT();
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
			var $calendar;

			function clsNotifyEventApprobation(&$evtinfo, $raison, $calname)
				{
				$this->calendar = $calname;
				
				$this->vars['title'] 		= $evtinfo['title'];
				$this->vars['description'] 	= $evtinfo['description'];
				$this->vars['startdate'] 	= bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->vars['enddate'] 		= bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->vars['message'] 		= $raison;
				$this->vars['location'] 	= $evtinfo['location'];
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
	
	
	
	
	
	
	

function notifyEventUpdate($evtid, $bdelete, $exclude)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsnotifyEventUpdate"))
		{
		class clsnotifyEventUpdate extends clsNotifyEvent
			{
			var $calendar;

			function clsnotifyEventUpdate(&$evtinfo)
				{
				$this->calendar = '';
				
				$this->vars['title'] 		= $evtinfo['title'];
				$this->vars['description'] 	= $evtinfo['description'];
				$this->vars['startdate'] 	= bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->vars['enddate'] 		= bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->vars['message'] 		= '';
				$this->vars['location'] 	= $evtinfo['location'];
				}
			}
		}
	

	$mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];
	$clearBCT = 'clear'.$babBody->babsite['mail_fieldaddress'];

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
		if($arrusers && !in_array($arr['id_cal'], $exclude))
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
			reset($arrusers);
			while(list(,$row) = each($arrusers))
				{
				$mail->$mailBCT($row['email'], $row['name']);
				$count++;

				if( $count > $babBody->babsite['mail_maxperpacket'] )
					{
					$mail->send();
					$mail->$clearBCT();
					$mail->clearTo();
					$count = 0;
					}

				}

			if( $count > 0 )
				{
				$mail->send();
				$mail->$clearBCT();
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
				$this->locationtxt = bab_translate("Location");
				$this->startdate = bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddate = bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->enddatetxt = bab_translate("End date");
				$this->titletxt = bab_translate("Title");
				$this->tmp_title = $evtinfo['title'];
				$this->tmp_location = $evtinfo['location'];
				if( $calinfo['type'] == BAB_CAL_PUB_TYPE )
					$this->calendartxt = bab_translate("Public calendar");
				else
					$this->calendartxt = bab_translate("Resource calendar");
					
				
				$this->tmp_calendar = $calinfo['name'];
				}
				
			function asHtml() {
				$this->title = bab_toHtml($this->tmp_title);
				$this->location = bab_toHtml($this->tmp_location);
				$this->description = bab_toHtml($this->tmp_desc, BAB_HTML_REPLACE_MAIL);
				$this->calendar = bab_toHtml($this->tmp_calendar);
				}
				
			function asText() {
				$this->title = $this->tmp_title;
				$this->location = $this->tmp_location;
				$this->description = strip_tags(bab_toHtml($this->tmp_desc, BAB_HTML_REPLACE_MAIL));
				$this->calendar = $this->tmp_calendar;
				}
			}
		}

	$mail = bab_mail();
	if( $mail == false )
		return;
	$mailBCT = 'mail'.$babBody->babsite['mail_fieldaddress'];

	if( count($users) > 0 )
		{
		$sql = "select email from ".BAB_USERS_TBL." where id IN (".$babDB->quote($users).")";
		$result=$babDB->db_query($sql);
		while( $arr = $babDB->db_fetch_array($result))
			{
			$mail->$mailBCT($arr['email']);
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





/**
 * search for availability lock in an array of calendars
 * if one calendar require availability, the function return true
 * @param	array	$calendars
 * @return boolean
 */
function bab_event_availabilityMandatory($calendars) {
	global $babDB;
	
	$res = $babDB->db_query('
		SELECT 
			COUNT(*) 
		FROM 
			'.BAB_CAL_RESOURCES_TBL.' r,
			'.BAB_CALENDAR_TBL.' c
		WHERE 
			r.id = c.owner 
			AND c.type=\''.BAB_CAL_RES_TYPE.'\' 
			AND c.id IN('.$babDB->quote($calendars).') 
			AND r.availability_lock=\'1\'
	');
	
	list($n) = $babDB->db_fetch_row($res);
	
	return 0 !== (int) $n;
}




class bab_event_posted {

	/**
	 * @public
	 */
	var $args = array();



	/**
	 * Get timestamp from date as array with keys
	 * <ul>
	 *	<li>year</li>
	 *	<li>month<li>
	 *	<li>day</li>
	 *	<li>hours (optional)</li>
	 *	<li>minutes (optional)</li>
	 * <ul>
	 *
	 * @static
	 *
	 * @param	array	$arr
	 * @return 	int		Timestamp
	 */
	function getTimestamp($arr) {
	
		if (!isset($arr['hours'])) {
			$arr['hours'] = 0;
		}
		
		if (!isset($arr['minutes'])) {
			$arr['minutes'] = 0;
		}
		
		return mktime( $arr['hours'], $arr['minutes'], 0, $arr['month'], $arr['day'], $arr['year'] );
	}



	/**
	 * Populate $this->args from POST data
	 */
	function createArgsData() {
	
		global $babBody, $babDB;
		
		if (isset($_POST['selected_calendars'])) {
			$this->args['selected_calendars'] = $_POST['selected_calendars'];
		}
	
	
		if( !empty($GLOBALS['BAB_SESS_USERID']) && isset($_POST['creminder']) && $_POST['creminder'] == 'Y')
			{
			$this->args['alert']['day'] = $_POST['rday'];
			$this->args['alert']['hour'] = $_POST['rhour'];
			$this->args['alert']['minute'] = $_POST['rminute'];
			$this->args['alert']['email'] = isset($_POST['remail'])? $_POST['remail']: 'N';
			}
		
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
		$editor = new bab_contentEditor('bab_calendar_event');
		$this->args['description'] = $editor->getContent();
		
		$this->args['title'] = bab_pp('title');
		$this->args['location'] = bab_pp('location');
			
		$this->args['category'] = empty($_POST['category']) ? '0' : $_POST['category'];
		$this->args['color'] = empty($_POST['color']) ? '' : $_POST['color'];
	
		$this->args['startdate']['year'] = $_POST['yearbegin'];
		$this->args['startdate']['month'] = $_POST['monthbegin'];
		$this->args['startdate']['day'] = $_POST['daybegin'];
		
		if (isset($_POST['timebegin'])) {
			$timebegin = $_POST['timebegin'];
		} else {
			$timebegin = $babBody->icalendars->starttime;
		}
		
		$tb = explode(':',$timebegin);
		$this->args['startdate']['hours'] = $tb[0];
		$this->args['startdate']['minutes'] = $tb[1];
	
		$this->args['enddate']['year'] = $_POST['yearend'];
		$this->args['enddate']['month'] = $_POST['monthend'];
		$this->args['enddate']['day'] = $_POST['dayend'];
		
		if (isset($_POST['timeend'])) {
			$timeend = $_POST['timeend'];
		} else {
			if ($babBody->icalendars->endtime > $timebegin) {
				$timeend = $babBody->icalendars->endtime;
			} else {
				$timeend = '23:59:59';
			}
		}
		
		$tb = explode(':',$timeend);
		$this->args['enddate']['hours'] = $tb[0];
		$this->args['enddate']['minutes'] = $tb[1];
	
	
		if( isset($_POST['bprivate']) && $_POST['bprivate'] ==  'Y' )
			{
			$this->args['private'] = true;
			}
		else
			{
			$this->args['private'] = false;
			}
	
		if( isset($_POST['block']) && $_POST['block'] ==  'Y' )
			{
			$this->args['lock'] = true;
			}
		else
			{
			$this->args['lock'] = false;
			}
	
		if( isset($_POST['bfree']) && $_POST['bfree'] ==  'Y' )
			{
			$this->args['free'] = true;
			}
		else
			{
			$this->args['free'] = false;
			}
	
		$id_owner = $GLOBALS['BAB_SESS_USERID'];
	
		if (isset($_POST['event_owner']) && isset($babBody->icalendars->usercal[$_POST['event_owner']]) )
			{
			$arr = $babDB->db_fetch_array(
				$babDB->db_query("
					SELECT 
						owner 
					
					FROM ".BAB_CALENDAR_TBL." 
						WHERE 
						id='".$babDB->db_escape_string($_POST['event_owner'])."'
					"
				)
			);
			$id_owner = isset($arr['owner']) ? $arr['owner'] : $GLOBALS['BAB_SESS_USERID'];
			}
		$this->args['owner'] = $id_owner;
	


	
		if( isset($_POST['repeat_cb']) && $_POST['repeat_cb'] != 0) {

			
			$this->args['until'] = array(
				'year'	=> (int) $_POST['repeat_yearend'], 
				'month'	=> (int) $_POST['repeat_monthend'], 
				'day'	=> (int) $_POST['repeat_dayend']
			);
			
			switch($_POST['repeat'] )
				{
				case BAB_CAL_RECUR_WEEKLY: /* weekly */
					$this->args['rrule'] = BAB_CAL_RECUR_WEEKLY;
					if( empty($_POST['repeat_n_2']))
						{
						$_POST['repeat_n_2'] = 1;
						}
	
					$this->args['nweeks'] = $_POST['repeat_n_2'];
	
					if( isset($_POST['repeat_wd']) )
						{
						$this->args['rdays'] = $_POST['repeat_wd'];
						}
	
					break;
				case BAB_CAL_RECUR_MONTHLY: /* monthly */
					$this->args['rrule'] = BAB_CAL_RECUR_MONTHLY;
					if( empty($_POST['repeat_n_3']))
						{
						$_POST['repeat_n_3'] = 1;
						}
	
					$this->args['nmonths'] = $_POST['repeat_n_3'];
					break;
				case BAB_CAL_RECUR_YEARLY: /* yearly */
					$this->args['rrule'] = BAB_CAL_RECUR_YEARLY;
					if( empty($_POST['repeat_n_4']))
						{
						$_POST['repeat_n_4'] = 1;
						}
					$this->args['nyears'] = $_POST['repeat_n_4'];
					break;
				case BAB_CAL_RECUR_DAILY: /* daily */
				default:
					$this->args['rrule'] = BAB_CAL_RECUR_DAILY;
					if( empty($_POST['repeat_n_1']))
						{
						$_POST['repeat_n_1'] = 1;
						}
	
					$this->args['ndays'] = $_POST['repeat_n_1'];
					$rtime = 24*3600*$_POST['repeat_n_1'];
					break;
			}
		}
	}
	
	
	
	
	/**
	 * Verify form data and 
	 * test availability on all events
	 *
	 * @param	string	&$message
	 * @return boolean
	 */
	function availabilityCheckAllEvents(&$message) {
		if (false === bab_createEvent($this->args, $message, array($this, 'availabilityCheckCallback'))) {
			return false;
		}
		
		$availability_msg_list = bab_event_posted::availabilityConflictsStore('MSG');
		
		if (0 < count($availability_msg_list)) {
			return false;
		}
		
		return true;
	}
	
	
	/**
	 * callback function for bab_createEvent
	 * @see bab_createEvent
	 * 
	 * @return array
	 */
	function availabilityCheckCallback($idcals,$id_owner, $title, $description, $location, $startdate, $enddate, $category, $color, $private, $lock, $free, $hash, $arralert) {
	
		if ('Y' === $free) {
			return array();
		}
		
		$dummy = '';

		bab_event_posted::availabilityCheck($idcals, $startdate, $enddate, false, $dummy);
		
		return array();
	}
	
	
	/**
	 * Test availability on period
	 * On conflicts, this function fill the list of conflicts
	 * @see 	bab_event_posted::availabilityConflictsStore()
	 * @static
	 *
	 * @param	array			$calid
	 * @param	string			$begin					Timestamp
	 * @param	string			$end					Timestamp
	 * @param	int|false		$evtid					If event is in modification
	 *													
	 * @return boolean			true : period available	/ false : period unavailable
	 */
	function availabilityCheck($calid, $begin, $end, $evtid) {
	
		
		$availability_msg_list = array();
		$availability_conflicts_calendars = array();
		
		
		$sdate = sprintf("%04s-%02s-%02s %02s:%02s:%02s", date('Y',$begin), date('m',$begin), date('d',$begin), date('H',$begin), date('i',$begin), date('s',$begin));
		$edate = sprintf("%04s-%02s-%02s %02s:%02s:%02s",  date('Y',$end), date('m',$end), date('d',$end),date('H',$end),date('i',$end), date('s',$end));
	
	
		// working hours test
	

		$whObj = bab_mcalendars::create_events($sdate, $edate, $calid);
		
		while ($event = $whObj->getNextEvent(BAB_PERIOD_CALEVENT)) {
			$data = $event->getData();
			
			if ($evtid) {
				if ((int) $data['id_event'] === (int) $evtid) {
					// considérer l'evenement modifie comme disponible
					$whObj->setAvailability($event, true);
				}
			}
		}
	
		
		$AvaReply = $whObj->getAvailability();
		
		
		
		$mcals = & new bab_mcalendars($sdate, $edate, $calid);
		foreach($AvaReply->conflicts_events as $calPeriod) {
		
			$event = $calPeriod->getData();
			if (!isset($_POST['evtid']) || $_POST['evtid'] != $event['id_event'])
				{

				$title = bab_translate("Private");
				if( 
					(
						'PUBLIC' !== $calPeriod->getProperty('CLASS') 
						&& $event['id_cal'] == $babBody->icalendars->id_percal
					) 
					|| 'PUBLIC' === $calPeriod->getProperty('CLASS'))
				{
					$title = $calPeriod->getProperty('SUMMARY');
				}
				
				
				
				$calendar_labels = array();
				$cals = $mcals->getEventCalendars($calPeriod);
				foreach($cals as $id_cal => $arr) {
					$availability_conflicts_calendars[] = $id_cal;
					$calendar_labels[] = $arr['name'];
				}
				
				

				$availability_msg_list[$calPeriod->getProperty('X-CTO-PUID')] = implode(', ', $calendar_labels).' '.bab_translate("on the event").' : '. $title .' ('.bab_shortDate(bab_mktime($calPeriod->getProperty('DTSTART')),false).')';

			}
		}
		
	
		
			
			
		if (false === $AvaReply->status && count($availability_msg_list) === 0) {
			
			if (0 < count($AvaReply->available_periods)) {
			
				// si il y a une periode dispo, l'afficher
				reset($AvaReply->available_periods);
				$calPeriod = current($AvaReply->available_periods);
				$availability_msg_list[] = sprintf(
					bab_translate('There is a conflict with working hours, the next available period is : %s to %s'),
					bab_shortDate($calPeriod->ts_begin),
					bab_time($calPeriod->ts_end)
				);
			
			} else {
			
				$availability_msg_list[] = bab_translate("There is a conflict with working hours of the selected personnal calendars");
			
			}
		}

		if (count($availability_msg_list) > 0)
			{
			bab_event_posted::availabilityConflictsStore('MSG', $availability_msg_list);
			bab_event_posted::availabilityConflictsStore('CAL', $availability_conflicts_calendars);
			
			return false;
			}
		else
			{
			return true;
			}
	}
	
	
	
	/**
	 * Register an array of message for availability check
	 * if called without parameters, this method return the list
	 *
	 * @static
	 * @param	string	$object_key		( 'MSG' | 'CAL' )
	 * @param	array	[$arr]
	 * @param	array
	 */
	function availabilityConflictsStore($object_key, $arr = NULL) {
	
		static $memory = array();
		
		if (!isset($memory[$object_key])) {
			$memory[$object_key] = array();
		}
		
		if (NULL !== $arr) {
			$memory[$object_key] += $arr;
		}

		return $memory[$object_key];
	}
	
	
	
	
	
	/**
	 * Test if availablity is mandatory after the availablity test
	 * this method use the calendars in conflicts list
	 * @see bab_event_posted::availabilityConflictsStore()
	 * @return boolean
	 */
	function availabilityIsMandatory() {
	
		$calendars = bab_event_posted::availabilityConflictsStore('CAL');
		$calendars = array_unique($calendars);
		
		return bab_event_availabilityMandatory($calendars);
	}
}









?>