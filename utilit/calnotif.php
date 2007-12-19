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
* @internal SEC1 NA 12/12/2006 FULL
*/
include_once 'base.php';

function sendReminders()
{
	global $babDB;

	if( !isset($GLOBALS['babEmailReminder']) || $GLOBALS['babEmailReminder'] != true )
	{
		return;
	}

	$res = $babDB->db_query("select ce.*, cer.* from ".BAB_CAL_EVENTS_REMINDERS_TBL." cer left join ".BAB_CAL_EVENTS_TBL." ce on ce.id=cer.id_event where bemail='Y' and (unix_timestamp(ce.start_date)-((cer.day*24*60*60)+(cer.hour*60*60)+(cer.minute*60))) < unix_timestamp() and processed='N'");

	if( $res && $babDB->db_num_rows($res) > 0 )
	{
		$mail = bab_mail();
		if( $mail == false )
			return;

		if(!class_exists("clsCalEventReminder"))
			{
			class clsCalEventReminder
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

				function clsCalEventReminder($title, $description, $startdate, $enddate)
					{
					$this->title = $title;
					$this->message = bab_translate("Event reminder");
					$this->description = $description;
					$this->startdate = $startdate;
					$this->enddate = $enddate;
					$this->descriptiontxt = bab_translate("Description");
					$this->titletxt = bab_translate("Title");
					$this->startdatetxt = bab_translate("Begin date");
					$this->enddatetxt = bab_translate("End date");
					}
				}
			}

		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

		while( $arr = $babDB->db_fetch_array($res))
		{
			$mail->clearTo();
			$email = bab_getUserEmail($arr['id_user']);
			$name = bab_getUserName($arr['id_user']);
			$mail->mailTo($email, $name);

			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setParameters(array('email' => true));
			$editor->setContent($arr['description']);

			$tempc = new clsCalEventReminder(
				$arr['title'], 
				$editor->getHtml(), 
				bab_longDate(bab_mktime($arr['start_date'])), 
				bab_longDate(bab_mktime($arr['end_date']))
			);
			
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "eventreminder"));
			$mail->mailSubject(bab_translate("Event reminder"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "eventremindertxt");
			$mail->mailAltBody($message);

			$mail->send();
			$babDB->db_query("update ".BAB_CAL_EVENTS_REMINDERS_TBL." set processed='Y' where id_event='".$babDB->db_escape_string($arr['id_event'])."' and id_user='".$babDB->db_escape_string($arr['id_user'])."'");
		}
	}
}


function updatePopupNotifier()
{

	global $babBody;

	class popupNotifierCls
		{

		function popupNotifierCls()
			{
			global $babDB;
			$this->resevent = $babDB->db_query("select ce.*, cer.* from ".BAB_CAL_EVENTS_REMINDERS_TBL." cer left join ".BAB_CAL_EVENTS_TBL." ce on ce.id=cer.id_event where cer.id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and (unix_timestamp(ce.start_date)-((cer.day*24*60*60)+(cer.hour*60*60)+(cer.minute*60))) < unix_timestamp() and processed='N'");
			$this->countevents = $babDB->db_num_rows($this->resevent);
			$this->altbg = true;
			$this->datetxt = bab_translate("Date");
			$this->eventtxt = bab_translate("Appointment");
			$this->dismisstxt = bab_translate("Dismiss the notification");
			$this->notifiertxt = bab_translate("Event notifier");
			}

		function getnextevent()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countevents)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->resevent);
				$this->eventurl = '';
				$this->eventtitle = bab_toHtml($arr['title']);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_calendar_event');
				$editor->setContent($arr['description']);
				$this->eventdesc = $editor->getHtml();
				
				$time = bab_mktime($arr['start_date']);
				$this->startdate = bab_shortDate($time, false);
				$this->starttime = bab_time($time);
				$this->dismissurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calnotif&idx=dismiss&evtid=".$arr['id']);
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new popupNotifierCls();
	echo bab_printTemplate($temp, "calnotif.html", "eventnotifier");

}

function dismissEvent($evtid)
{
	global $babDB;
	$babDB->db_query("update ".BAB_CAL_EVENTS_REMINDERS_TBL." set processed='Y' where id_event='".$babDB->db_escape_string($evtid)."' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
}

/* main */
$idx = bab_rp('idx');

if( $idx == 'dismiss')
{
	dismissEvent($evtid);
	$idx = 'popup';
}

switch($idx)
{
	case 'email':
		sendReminders();
		exit;
		break;
	case 'popup':
		updatePopupNotifier();
		exit;
		break;
}
?>