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

function confirmEvent($evtid, $idcal, $bconfirm, $comment, $bupdrec)
{
	global $babDB, $babBody;
	$arr = $babBody->icalendars->getCalendarInfo($idcal);
	
	$arrevtids = array();
	if( $bupdrec == 1)
		{
		list($hash) = $babDB->db_fetch_row($babDB->db_query("select hash from ".BAB_CAL_EVENTS_TBL." where id='".$evtid."'"));
		if( !empty($hash) &&  $hash[0] == 'R')
			{
			$res = $babDB->db_query("select id from ".BAB_CAL_EVENTS_TBL." where hash='".$hash."'");
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
				$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$bconfirm."' where id_event IN (".implode(',', $arrevtids).") and id_cal='".$idcal."'");
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
				$res = $babDB->db_query("select * from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event IN (".implode(',', $arrevtids).") and id_cal='".$idcal."' and idfai != '0'");
				while( $row = $babDB->db_fetch_array($res))
					{
					if( in_array($row['idfai'], $arrschi))
						{
						$ret = updateFlowInstance($row['idfai'], $GLOBALS['BAB_SESS_USERID'], ($bconfirm == 'Y'? true: false ));
						switch($ret)
							{
							case 0:
								deleteFlowInstance($row['idfai']);
								$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$bconfirm."', idfai='0' where id_event='".$row['id_event']."'  and id_cal='".$row['id_cal']."'");
								notifyEventApprobation($evtid, $bconfirm, $comment, $arr['name']);
								break;
							case 1:
								deleteFlowInstance($row['idfai']);
								$babDB->db_query("update ".BAB_CAL_EVENTS_OWNERS_TBL." set status='".$bconfirm."', idfai='0' where id_event='".$row['id_event']."'  and id_cal='".$row['id_cal']."'");
								notifyEventApprobation($evtid, $bconfirm, $comment, $arr['name']);
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
		$res = $babDB->db_query("select count(id_cal) as total, id_event from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event IN (".implode(',', $arrevtids).") and status in (".BAB_CAL_STATUS_ACCEPTED.",".BAB_CAL_STATUS_NONE.") group by id_event");
		$arrtmp =array();
		while($arr = $babDB->db_fetch_array($res))
		{
			$arrtmp[] = $arr['id_event'];
		}

		for( $i= 0; $i < count($arrevtids); $i++ )
		{
			if( count($arrtmp) == 0 || !in_array($arrevtids[$i], $arrtmp))
			{
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_TBL." where id='".$arrevtids[$i]."'");
			$babDB->db_query("delete from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$arrevtids[$i]."'");
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

		$res=$babDB->db_query("select ut.firstname, ut.lastname, ut.email from ".BAB_USERS_TBL." ut left join ".BAB_CALENDAR_TBL." ct on ut.id=ct.owner where ct.type='1' and ct.id in (".implode(',', $idcals).")");

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

			$res = $babDB->db_query("select id_group from ".BAB_CAL_PUB_GRP_GROUPS_TBL." where  id_object='".$idcals[$i]."'");
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
							$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
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
			$tempc->calendar = bab_getCalendarOwnerName($idcals, BAB_CAL_RES_TYPE);
			$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
			$mail->mailSubject(bab_translate("New appointement"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
			$mail->mailAltBody($message);

			$res = $babDB->db_query("select id_group from ".BAB_CAL_RES_GRP_GROUPS_TBL." where  id_object='".$idcals[$i]."'");
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
							$res2 = $babDB->db_query("select ".BAB_USERS_TBL.".id, ".BAB_USERS_TBL.".email, ".BAB_USERS_TBL.".firstname, ".BAB_USERS_TBL.".lastname from ".BAB_USERS_TBL." join ".BAB_USERS_GROUPS_TBL." where is_confirmed='1' and disabled='0' and ".BAB_USERS_GROUPS_TBL.".id_group='".$row['id_group']."' and ".BAB_USERS_GROUPS_TBL.".id_object=".BAB_USERS_TBL.".id");
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

	$res=$babDB->db_query("select cet.*, ut.firstname, ut.lastname, ut.email from ".BAB_CAL_EVENTS_TBL." cet left join ".BAB_USERS_TBL." ut on ut.id = cet.id_creator where cet.id='".$evtid."'");
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

	$evtinfo=$babDB->db_fetch_array($babDB->db_query("select cet.* from ".BAB_CAL_EVENTS_TBL." cet where cet.id='".$evtid."'"));

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

	$res = $babDB->db_query("select ceot.*, ct.type, ct.owner from ".BAB_CAL_EVENTS_OWNERS_TBL." ceot left join ".BAB_CALENDAR_TBL." ct on ct.id=ceot.id_cal where ceot.id_event='".$evtid."'");
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
				$res2 = $babDB->db_query("select id_group from ".BAB_CAL_PUB_GRP_GROUPS_TBL." where id_object='".$arr['id_cal']."'");
				while( $row = $babDB->db_fetch_array($res2) && !$all)
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
				$res2 = $babDB->db_query("select id_group from ".BAB_CAL_RES_GRP_GROUPS_TBL." where id_object='".$arr['id_cal']."'");
				while( $row = $babDB->db_fetch_array($res2) && !$all)
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
			echo "select id, email, firstname, lastname from ".BAB_USERS_TBL." where is_confirmed='1' and disabled='0'";
			}
		else
			{
			if( count($arrgroups) > 0 )
				{
				$res2 = $babDB->db_query("select id_object from ".BAB_USERS_GROUPS_TBL." where id_group in (".implode(',', array_keys($arrgroups)).")");
				echo "select id_object from ".BAB_USERS_GROUPS_TBL." where id_group in (".implode(',', array_keys($arrgroups)).")<br>";
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
				$res2 = $babDB->db_query("select id, email, firstname, lastname from ".BAB_USERS_TBL." WHERE is_confirmed='1' and disabled='0' and id in (".implode(',', array_keys($arrusers)).") AND id <> '".$GLOBALS['BAB_SESS_USERID']."'");
				echo "select id, email, firstname, lastname from ".BAB_USERS_TBL." WHERE is_confirmed='1' and disabled='0' and id in (".implode(',', array_keys($arrusers)).") AND id <> '".$GLOBALS['BAB_SESS_USERID']."'<br>";
				}
			}

exit;
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

?>