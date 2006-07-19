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

function createEvent($idcals,$id_owner, $title, $description, $location, $startdate, $enddate, $category, $color, $private, $lock, $free, $hash, $arralert)
{

	global $babBody, $babDB;

	bab_editor_record($description);


	$babDB->db_query("insert into ".BAB_CAL_EVENTS_TBL." 
	( title, description, location, start_date, end_date, id_cat, id_creator, color, bprivate, block, bfree, hash) 
	
	values (
		".$babDB->quote($db_title).", 
		".$babDB->quote($db_description).", 
		".$babDB->quote($db_location).", 
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

	$arrcals = array();

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
				elseif( $arr['access'] == BAB_CAL_ACCESS_UPDATE )
					{
					$add = true;
					$ustatus = BAB_CAL_STATUS_NONE;
					}
				elseif( $arr['access'] == BAB_CAL_ACCESS_FULL )
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
			$arrcals[] = $id_cal;
			$babDB->db_query("INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." (id_event,id_cal, status) VALUES ('".$babDB->db_escape_string($id_event)."','".$babDB->db_escape_string($id_cal)."', '".$babDB->db_escape_string($ustatus)."')");
			if( ($arr['type'] == BAB_CAL_PUB_TYPE ||  $arr['type'] == BAB_CAL_RES_TYPE) && ($arr['idsa'] != 0) )
				{
				include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
				$idfai = makeFlowInstance($arr['idsa'], "cal-".$id_cal."-".$id_event);
				$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set idfai='".$babDB->db_escape_string($idfai)."' where id_event='".$babDB->db_escape_string($id_event)."' and id_cal='".$babDB->db_escape_string($id_cal)."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyEventApprovers($id_event, $nfusers, $arr);
				}
			}
		}

	if( count($arrcals) == 0 )
		{
		$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'");
		}
	elseif( !empty($GLOBALS['BAB_SESS_USERID']) && $arralert !== false )
		{
		$babDB->db_query("insert into ".BAB_CAL_EVENTS_REMINDERS_TBL." (id_event, id_user, day, hour, minute, bemail) values ('".$babDB->db_escape_string($id_event)."', '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".$babDB->db_escape_string($arralert['day'])."', '".$babDB->db_escape_string($arralert['hour'])."', '".$babDB->db_escape_string($arralert['minute'])."', '".$babDB->db_escape_string($arralert['email'])."')");

		}
	return $arrcals;
}

/*
	$args['startdate'] : array('month', 'day', 'year', 'hours', 'minutes')
	$args['enddate'] : array('month', 'day', 'year', 'hours', 'minutes')
	$args['owner'] : id of the owner
	$args['rrule'] : // BAB_CAL_RECUR_DAILY, ...
	$args['until'] : array('month', 'day', 'year')
	$args['rdays'] : repeat days array(0,1,2,3,4,5,6)
	$args['ndays'] : nb days 
	$args['nweeks'] : nb weeks 
	$args['nmonths'] : nb weeks 
	$args['category'] : id of the category
	$args['private'] : if the event is private
	$args['lock'] : to lock the event
	$args['free'] : free event
	$args['alert'] : array('day', 'hour', 'minute', 'email'=>'Y')
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
			if( count($arrevtids) > 0 && $arr['access'] == BAB_CAL_ACCESS_FULL)
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
								notifyResourceEvent($rr['title'], $rr['description'], bab_longDate(bab_mktime($rr['start_date'])), bab_longDate(bab_mktime($rr['end_date'])), array($idcal));
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

function notifyPersonalEvent($title, $description, $startdate, $enddate, $idcals)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyAttendees"))
		{
		class clsNotifyAttendees
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

			function clsNotifyAttendees($title, $description, $startdate, $enddate)
				{
				$this->title = $title;
				$this->message = bab_translate("New appointement");

				$this->description = $description;
				$this->startdate = $startdate;
				$this->enddate = $enddate;
				$this->descriptiontxt = bab_translate("Description");
				$this->titletxt = bab_translate("Title");
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddatetxt = bab_translate("End date");
				$this->calendartxt = bab_translate("Calendar");
				$this->calendar = bab_translate("Personal calendar");
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
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
		$mail->mailSubject(bab_translate("New appointement"));
		$mail->mailBody($message, "html");

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
		class clsNotifyPublicEvent
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
				$this->title = $title;
				$this->message = bab_translate("New appointement");

				$this->description = $description;
				$this->startdate = $startdate;
				$this->enddate = $enddate;
				$this->descriptiontxt = bab_translate("Description");
				$this->titletxt = bab_translate("Title");
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddatetxt = bab_translate("End date");
				$this->calendartxt = bab_translate("Calendar");
				$this->calendar = "";
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
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailSubject(bab_translate("New appointement"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$res = $babDB->db_query("select id_group from ".BAB_CAL_PUB_GRP_GROUPS_TBL." where  id_object='".$babDB->db_escape_string($idcals[$i])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				while( $row = $babDB->db_fetch_array($res))
					{
					switch($row['id_group'])
						{
						case 0:
						case 1:
							$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
							break;
						case 2:
							return;
						default:
							$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$babDB->db_escape_string($row['id_group'])."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
							break;
						}

					if( $res2 && $babDB->db_num_rows($res2) > 0 )
						{
						$count = 0;
						while(($arr = $babDB->db_fetch_array($res2)))
							{
							if( count($arrusers) == 0 || !in_array($arr['id'], $arrusers))
								{
								$arrusers[] = $arr['id'];
								$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
								$count++;
								}

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
		}
	}


function notifyResourceEvent($title, $description, $startdate, $enddate, $idcals)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyResourceEvent"))
		{
		class clsNotifyResourceEvent
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

			function clsNotifyResourceEvent($title, $description, $startdate, $enddate)
				{
				$this->title = $title;
				$this->message = bab_translate("New appointement");

				$this->description = $description;
				$this->startdate = $startdate;
				$this->enddate = $enddate;
				$this->descriptiontxt = bab_translate("Description");
				$this->titletxt = bab_translate("Title");
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddatetxt = bab_translate("End date");
				$this->calendartxt = bab_translate("Calendar");
				$this->calendar = "";
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
		
		$arrusers = array();
		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals[$i], BAB_CAL_RES_TYPE);
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailSubject(bab_translate("New appointement"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$res = $babDB->db_query("select id_group from ".BAB_CAL_RES_GRP_GROUPS_TBL." where  id_object='".$babDB->db_escape_string($idcals[$i])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				while( $row = $babDB->db_fetch_array($res))
					{
					switch($row['id_group'])
						{
						case 0:
						case 1:
							$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
							break;
						case 2:
							return;
						default:
							$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$babDB->db_escape_string($row['id_group'])."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
							break;
						}

					if( $res2 && $babDB->db_num_rows($res2) > 0 )
						{
						$count = 0;
						while(($arr = $babDB->db_fetch_array($res2)))
							{
							if( count($arrusers) == 0 || !in_array($arr['id'], $arrusers))
								{
								$arrusers[] = $arr['id'];
								$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
								$count++;
								}

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
		}
	}


function notifyEventApprobation($evtid, $bconfirm, $raison, $calname)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsNotifyEventApprobation"))
		{
		class clsNotifyEventApprobation
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

			function clsNotifyEventApprobation(&$evtinfo, $raison, $calname)
				{
				$this->title = $evtinfo['title'];
				$this->message = $raison;

				$this->description = $evtinfo['description'];
				$this->startdate = bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->enddate = bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->descriptiontxt = bab_translate("Description");
				$this->titletxt = bab_translate("Title");
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddatetxt = bab_translate("End date");
				$this->calendartxt = bab_translate("Calendar");
				$this->calendar = $calname;
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
	$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));

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
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
	$mail->mailAltBody($message);
	$mail->send();
	}

function notifyEventUpdate($evtid, $bdelete)
	{
	global $babBody, $babDB, $babAdminEmail;

	if(!class_exists("clsnotifyEventUpdate"))
		{
		class clsnotifyEventUpdate
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

			function clsnotifyEventUpdate(&$evtinfo)
				{
				$this->title = $evtinfo['title'];
				$this->message = '';

				$this->description = $evtinfo['description'];
				$this->startdate = bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->enddate = bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->descriptiontxt = bab_translate("Description");
				$this->titletxt = bab_translate("Title");
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddatetxt = bab_translate("End date");
				$this->calendartxt = bab_translate("Calendar");
				$this->calendar = '';
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

	$res = $babDB->db_query("select ceot.*, ct.type, ct.owner from ".BAB_CAL_EVENTS_OWNERS_TBL." ceot left join ".BAB_CALENDAR_TBL." ct on ct.id=ceot.id_cal where ceot.id_event='".$babDB->db_escape_string($evtid)."'");
	while( $arr = $babDB->db_fetch_array($res) )
		{
		$arrusers = array();
		$arrgroups = array();
		$all = false;

		switch($arr['type'])
			{
			case BAB_CAL_USER_TYPE:
				if( !isset($arrusers[$arr['owner']]))
					{
					$arrusers[$arr['owner']] = 1;
					}
				break;
			case BAB_CAL_PUB_TYPE:
				$res2 = $babDB->db_query("select id_group from ".BAB_CAL_PUB_GRP_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id_cal'])."'");
				while( ($row = $babDB->db_fetch_array($res2)) && !$all)
					{
					switch($row['id_group'])
						{
						case 0:
						case 1:
							$all = true;
							break;
						case 2:
							break;
						default:
							if( !isset($arrgroups[$row['id_group']]))
								{
								$arrgroups[$row['id_group']] = 1;
								}
							break;
						}
					}

				break;
			case BAB_CAL_RES_TYPE:
				$res2 = $babDB->db_query("select id_group from ".BAB_CAL_RES_GRP_GROUPS_TBL." where id_object='".$babDB->db_escape_string($arr['id_cal'])."'");
				while( ($row = $babDB->db_fetch_array($res2)) && !$all)
					{
					switch($row['id_group'])
						{
						case 0:
						case 1:
							$all = true;
							break;
						case 2:
							break;
						default:
							if( !isset($arrgroups[$row['id_group']]))
								{
								$arrgroups[$row['id_group']] = 1;
								}
							break;
						}
					}
				break;
			}

		$res2 = false;
		if( $all )
			{
			$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
			}
		else
			{
			if( count($arrgroups) > 0 )
				{
				$res2 = $babDB->db_query("select id_object from ".BAB_USERS_GROUPS_TBL." where id_group in (".$babDB->quote( array_keys($arrgroups)).")");
				while( $row = $babDB->db_fetch_array($res2))
					{
					if( !isset($arrusers[$row['id_object']]))
						{
						$arrusers[$row['id_object']] = 1;
						}
					}
				}

			if( count($arrusers) > 0 )
				{
				$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." WHERE is_confirmed='1' and disabled='0' and id in (".$babDB->quote( array_keys($arrusers)).") AND id <> '".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
				}
			}

		if( $res2 )
			{
			$calinfo = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
			$tempc->calendar = $calinfo['name'];
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);
			
			$count = 0;
			while(($row = $babDB->db_fetch_array($res2)))
				{
				$mail->mailBcc($row['email']); // , bab_composeUserName($row['firstname'],$row['lastname'])
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


			function notifyEventApproversCls($id_event, $calinfo)
				{
				global $babDB;

				$this->message = bab_translate("A new event has been scheduled");
				$evtinfo = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_CAL_EVENTS_TBL." where id='".$babDB->db_escape_string($id_event)."'"));

				$this->description = $evtinfo['description'];
				$this->descriptiontxt = bab_translate("Description");
				$this->startdate = bab_longDate(bab_mktime($evtinfo['start_date']));
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddate = bab_longDate(bab_mktime($evtinfo['end_date']));
				$this->enddatetxt = bab_translate("End date");
				$this->titletxt = bab_translate("Title");
				$this->title = $evtinfo['title'];
				if( $calinfo['type'] == BAB_CAL_PUB_TYPE )
					$this->calendartxt = bab_translate("Public calendar");
				else
					$this->calendartxt = bab_translate("Resource calendar");
				$this->calendar = $calinfo['name'];
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
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "eventwait"));
	$mail->mailBody($message, "html");

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