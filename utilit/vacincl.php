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
$babDayType = array(1=>bab_translate("Whole day"), bab_translate("Morning"), bab_translate("Afternoon"));

function bab_printDate($date)
	{
		$arr = explode('-', $date );
		return $arr[2].'-'.$arr[1].'-'.$arr[0];
	}

function notifyVacationApprovers($id, $users)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail;

	if(!class_exists("tempa"))
		{
		class tempa
			{
			var $message;
			var $from;
			var $site;
			var $until;
			var $begindate;
			var $enddate;
			var $quantitytxt;
			var $quantity;
			var $commenttxt;
			var $comment;


			function tempa($row)
				{
				global $babDayType, $babDB;
				$this->message = bab_translate("Vacation request is waiting to be validated");
				$this->fromuser = bab_translate("User");
				$this->from = bab_translate("from");
				$this->until = bab_translate("until");
				$this->quantitytxt = bab_translate("Quantity");
				$this->commenttxt = bab_translate("Comment");
				$this->username = bab_getUserName($row['id_user']);
				$this->begindate = bab_strftime(bab_mktime($row['date_begin']), false). " ". bab_translate($babDayType[$row['day_begin']]);
				$this->enddate = bab_strftime(bab_mktime($row['date_end']), false). " ". bab_translate($babDayType[$row['day_end']]);
				list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$row['id']."'"));
				$this->comment = htmlentities($row['comment']);
				}
			}
		}
	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));

	$mail = bab_mail();
	if( $mail == false )
		return;

	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]), bab_getUserName($users[$i]));

	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailSubject(bab_translate("Vacation request is waiting to be validated"));

	$tempa = new tempa($row);
	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacation");
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


?>