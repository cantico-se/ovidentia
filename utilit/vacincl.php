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
				$this->begindate = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false). " ". $babDayType[$row['day_begin']];
				$this->enddate = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false). " ". $babDayType[$row['day_end']];
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
		$mail->mailBcc(bab_getUserEmail($users[$i]), bab_getUserName($users[$i]));

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("Vacation request is waiting to be validated"));

	$tempa = new tempa($row);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "newvacation"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


function bab_getRightsOnPeriod($begin, $end, $id_user = false)
	{
	$return = array();
	$begin = bab_mktime($begin);
	$end = bab_mktime($end);

	if (!$id_user) $id_user = $GLOBALS['BAB_SESS_USERID'];

	$db = & $GLOBALS['babDB'];

	$res = $db->db_query("select r.*, rules.*, ur.quantity ur_quantity from ".BAB_VAC_TYPES_TBL." t, ".BAB_VAC_COLL_TYPES_TBL." c, ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_USERS_RIGHTS_TBL." ur, ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_RIGHTS_RULES_TBL." rules ON rules.id_right = r.id where t.id = c.id_type and c.id_coll=p.id_coll AND p.id_user='".$id_user."' AND r.active='Y' and ur.id_user='".$id_user."' and ur.id_right=r.id and r.id_type=t.id");
	
	while ( $arr = $db->db_fetch_array($res) )
		{
		$row = $db->db_fetch_array($db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user='".$id_user."' and e.status!='N' and el.id_type='".$arr['id_type']."' and el.id_entry=e.id"));
				
		$qdp = isset($row['total'])? $row['total'] : 0;

		if( $arr['ur_quantity'] != '')
			{
			$quantitydays = $arr['ur_quantity'] - $qdp;
			}
		else
			{
			$quantitydays = $arr['quantity'] - $qdp;
			}

		$access = true;
		if ( !empty($arr['id_right']) )
			{
			// rules

			$period_start = bab_mktime($arr['period_start']);
			$period_end = bab_mktime($arr['period_end']);
			
			$nbdays = round(($end - $begin) / 86400);

			if ($begin == -1 || $end == -1 || $period_start == -1 || $period_end == -1)
				break;

			if ($begin < bab_mktime($arr['date_begin']) || $end > bab_mktime($arr['date_end']))
				break;

			$access = false;

			if ($arr['right_inperiod'] == 0)
				$access = true;

			if ( $arr['right_inperiod'] == 1 && 
				!empty($arr['period_start']) && 
				!empty($arr['period_end']) && 
				$period_start >= $begin && 
				$period_end <= $end )
					{
					$access = true;
					}

			if ($arr['right_inperiod'] == 2 && 
				!empty($arr['period_start']) && 
				!empty($arr['period_end']) && 
				!($period_start >= $begin && $period_end <= $end) )
					{
					$access = true;
					}

			if ( $access )
				{
				switch ($arr['trigger_inperiod'])
					{
					case 0:
						$req = " AND e.date_begin >= '".$arr['date_begin']."' AND e.date_end <= '".$arr['date_end']."'";
						break;
					case 1:
						$req = " AND e.date_begin >= '".$arr['period_start']."' AND e.date_end <= '".$arr['period_end']."'";
						break;
					case 2:
						$req = " AND ((e.date_begin < '".$arr['period_start']."' AND e.date_end <= '".$arr['period_start']."') OR (e.date_begin >= '".$arr['period_end']."' AND e.date_end > '".$arr['period_end']."'))";
						break;
					default:
						$req = '';
						break;
					}
				
				list($nbdays) = $db->db_fetch_array($db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user='".$id_user."' and e.status!='N' and el.id_type='".$arr['id_type']."' and el.id_entry=e.id ".$req));


				$access = false;

				if ( $arr['trigger_nbdays_min'] <= $nbdays && $nbdays <= $arr['trigger_nbdays_max'] )
					$access = true;
				}
			
			}
		
		if ( $access )
			$return[] = array(
						'date_begin' => $arr['date_begin'],
						'date_end' =>   $arr['date_end'],
						'quantity' =>   $arr['quantity'],
						'description' =>$arr['description'],
						'cbalance' =>   $arr['cbalance'],
						'quantitydays'=>$quantitydays
						);
		}

	return $return;
	}

?>