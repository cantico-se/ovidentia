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

notifyAttendees($title, $description, $startdate, $enddate, $users)
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
				global $BAB_SESS_USER, $BAB_SESS_EMAIL, $babSiteName;
				$this->title = $title;
				$this->message = "New appointement";

				$this->description = $description;
				$this->startdate = $startdate;
				$this->enddate = $startdate;
				$this->descriptiontxt = bab_translate("Description");
				$this->titletxt = bab_translate("Title");
				$this->startdatetxt = bab_translate("Begin date");
				$this->enddatetxt = bab_translate("End date");
				}
			}
		}
	

	if( count($users) > 0 )
		{
		$mail = bab_mail();
		if( $mail == false )
			return;

		$res=$babDB->db_query(select email from ".BAB_USERS_TBL." where id IN (".implode(',', $users).")");
		while( $arr = $babDB->db_fetch_array($res))
			{
			$mail->mailBcc($arr['email']);
			}
		$mail->mailFrom($GLOBALS['babAdminEmail'], $GLOBALS['babAdminName']);

		$tempc = new clsNotifyAttendees($title, $description, $startdate, $enddate);
		$message = $mail->mailTemplate(bab_printTemplate($tempc,"mailinfo.html", "newevent"));
		$mail->mailSubject($tempc->about);
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempc,"mailinfo.html", "neweventtxt");
		$mail->mailAltBody($message);
		$mail->send();
		}
	}

?>