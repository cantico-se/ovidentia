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

		$res=$babDB->db_query("select ut.firstname, ut.lastname, ut.email from ".BAB_USERS_TBL." ut left join ".BAB_CALENDAR_TBL." ct on ut.id=ct.owner where ct.type='1' and ct.id in (".implode(',', $idcals).")");

		while( $arr = $babDB->db_fetch_array($res))
			{
			$mail->mailBcc($arr['email']);
			}
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);

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

		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		$tempc = new clsNotifyPublicEvent($title, $description, $startdate, $enddate);

		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals, BAB_CAL_PUB_TYPE);
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailSubject(bab_translate("New appointement"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$res = $db->db_query("select id_group from ".BAB_CAL_PUB_GRP_GROUPS_TBL." where  id_object='".$idclas[$i]."'");
			$arrusers = array();
			if( $res && $db->db_num_rows($res) > 0 )
				{
				while( $row = $db->db_fetch_array($res))
					{
					switch($row['id_group'])
						{
						case 0:
						case 1:
							$res2 = $db->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
							break;
						case 2:
							return;
						default:
							$res2 = $db->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
							break;
						}

					if( $res2 && $db->db_num_rows($res2) > 0 )
						{
						$count = 0;
						while(($arr = $db->db_fetch_array($res2)))
							{
							$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
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

		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);
		$tempc = new clsNotifyResourceEvent($title, $description, $startdate, $enddate);

		for( $i = 0; $i < count($idcals); $i++ )
			{
			$tempc->calendar = bab_getCalendarOwnerName($idcals, BAB_CAL_RES_TYPE);
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailSubject(bab_translate("New appointement"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$res = $db->db_query("select id_group from ".BAB_CAL_RES_GRP_GROUPS_TBL." where  id_object='".$idclas[$i]."'");
			$arrusers = array();
			if( $res && $db->db_num_rows($res) > 0 )
				{
				while( $row = $db->db_fetch_array($res))
					{
					switch($row['id_group'])
						{
						case 0:
						case 1:
							$res2 = $db->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'");
							break;
						case 2:
							return;
						default:
							$res2 = $db->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
							break;
						}

					if( $res2 && $db->db_num_rows($res2) > 0 )
						{
						$count = 0;
						while(($arr = $db->db_fetch_array($res2)))
							{
							$mail->mailBcc($arr['email'], bab_composeUserName($arr['firstname'],$arr['lastname']));
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
		}
	}


function notifyEventApprobation($evtid, $bconfirm, $raison)
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

			function clsNotifyEventApprobation($title, $description, $startdate, $enddate, $raison)
				{
				$this->title = $title;
				$this->message = $raison;

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
	

	$mail = bab_mail();
	if( $mail == false )
		return;

	$res=$babDB->db_query("select cet.*, ut.firstname, ut.lastname, ut.email from ".BAB_CAL_EVENTS_TBL." cet left join ".BAB_USERS_TBL." ut on ut.id = cet.id_creator where cet.id='".$evtid."'");
	$arr = $babDB->db_fetch_array($res);

	$mail->mailTo($arr['email'], bab_composeUserName($arr['firstname'], $arr['lastname']));
	$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);

	$tempc = new clsNotifyEventApprobation($arr['title'], $arr['description'], bab_longDate(bab_mktime($arr['start_date'])), bab_longDate(bab_mktime($arr['end_date'])), $raison);
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

?>