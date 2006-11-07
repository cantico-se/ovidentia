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
include_once $GLOBALS['babInstallPath']."utilit/ocapi.php";


define("VAC_MAX_REQUESTS_LIST", 20);

define("VAC_FIX_DELETE", 2);
define("VAC_FIX_UPDATE", 1);
define("VAC_FIX_ADD",    0);

class vac_notifyVacationApprovers
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


	function vac_notifyVacationApprovers($row)
		{
		global $babDB;
		$this->fromuser = bab_translate("User");
		$this->from = bab_translate("from");
		$this->until = bab_translate("until");
		$this->quantitytxt = bab_translate("Quantity");
		$this->commenttxt = bab_translate("Comment");
		$this->username = bab_getUserName($row['id_user']);
		$this->begindate = bab_longDate(bab_mktime($row['date_begin']));
		$this->enddate = bab_longDate(bab_mktime($row['date_end']));
		list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$row['id']."'"));
		$this->comment = htmlentities($row['comment']);
		}
	}

function notifyVacationApprovers($id, $users, $modify = false)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail;

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));

	$mail = bab_mail();
	if( $mail == false )
		return;

	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]), bab_getUserName($users[$i]));

	$mail->mailFrom(bab_getUserEmail($row['id_user']), bab_getUserName($row['id_user']));

	$mail->mailSubject(bab_translate("Vacation request is waiting to be validated"));

	$tempa = new vac_notifyVacationApprovers($row);
	if ($modify)
		$tempa->message = bab_translate("The request has been modified");
	else
		$tempa->message = bab_translate("Vacation request is waiting to be validated");

	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "newvacation"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	
	}


function notifyOnRequestChange($id, $delete = false)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL;

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));

	// no mail if vacation is elapsed
	if ($row['date_end'] < date('Y-m-d H:i:s')) {
		return;
	}

	if (!class_exists('tempb')) {
		class tempb
		{
		function tempb($row, $msg)
			{
			global $babDB;
			$this->message = $msg;
			$this->fromuser = bab_translate("User");
			$this->from = bab_translate("from");
			$this->until = bab_translate("until");
			$this->quantitytxt = bab_translate("Quantity");
			$this->commenttxt = bab_translate("Comment");
			$this->username = bab_getUserName($row['id_user']);
			$this->begindate = bab_longDate(bab_mktime($row['date_begin']));
			$this->enddate = bab_longDate(bab_mktime($row['date_end']));
			list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry =".$babDB->quote($row['id'])));
			$this->comment = htmlentities($row['comment']);
			}
		}
	}

	$mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo(bab_getUserEmail($row['id_user']), bab_getUserName($row['id_user']));

	$mail->mailFrom($BAB_SESS_EMAIL, $BAB_SESS_USER);

	$msg = $delete ? bab_translate("Vacation request has been deleted") : bab_translate("Vacation request has been modified");
	$mail->mailSubject($msg);

	$tempb = new tempb($row, $msg);
	$message = $mail->mailTemplate(bab_printTemplate($tempb,"mailinfo.html", "newvacation"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);
	
	$mail->send();
	
	if ($mail->ErrorInfo())
		{
		trigger_error($mail->ErrorInfo());
		}
	}


/**
 * Get list of right for a user
 *
 * @param	string|false	$begin		ISO datetime
 * @param	string|false	$end		ISO datetime
 * @param	int|false		$id_user	
 * @param	1|0				$rfrom		test active flag on right if user is not manager
 *
 * @return array
 */
function bab_getRightsOnPeriod($begin = false, $end = false, $id_user = false, $rfrom = 0)
	{

	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";	

	$return = array();
	$begin = $begin ? bab_mktime( $begin ) : $begin;
	$end = $end ? bab_mktime( $end ) : $end;

	if (!$id_user) $id_user = $GLOBALS['BAB_SESS_USERID'];

	$db = & $GLOBALS['babDB'];

	$req = "SELECT 
				rules.*, 
				r.*, 
				rg.name rgroup,  
				ur.quantity ur_quantity 
				FROM 
					".BAB_VAC_TYPES_TBL." t, 
					".BAB_VAC_COLL_TYPES_TBL." c, 
					".BAB_VAC_USERS_RIGHTS_TBL." ur, 
					".BAB_VAC_PERSONNEL_TBL." p, 
					".BAB_VAC_RIGHTS_TBL." r 
				LEFT JOIN 
					".BAB_VAC_RIGHTS_RULES_TBL." rules 
					ON rules.id_right = r.id 
				LEFT JOIN 
					".BAB_VAC_RGROUPS_TBL." rg 
					ON rg.id = r.id_rgroup 
				WHERE t.id = c.id_type 
					AND c.id_coll=p.id_coll 
					AND p.id_user=".$db->quote($id_user)." ";
	
	if( $rfrom == 1 )
		{
		$acclevel = bab_vacationsAccess();
		if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
			{
			$req .= " AND r.active='Y' ";
			}
		}
	else
		{
		$req .= " AND r.active='Y' ";
		}


	$access_on_period = true;

	if (!$begin) {
		$access_on_period = false;
		}

	$req .= " AND ur.id_user=".$db->quote($id_user)." AND ur.id_right=r.id 	AND r.id_type=t.id 	GROUP BY r.id ORDER BY r.description";
	
	
	
	$res = $db->db_query($req);

	
	while ( $arr = $db->db_fetch_assoc($res) )
		{

		
		$access = true;

		if( $arr['date_begin_valid'] != '0000-00-00' && (bab_mktime($arr['date_begin_valid']." 00:00:00") > mktime())){
			$access= false;
			}

		if( $arr['date_end_valid'] != '0000-00-00' && (bab_mktime($arr['date_end_valid']." 23:59:59") < mktime())){
			$access= false;
			}

		
		$beginp = $begin;
		$endp = $end;
		
		if (!$beginp || -1 == $beginp)
			{
			$beginp = bab_mktime($arr['date_begin_valid']);
			}

		if (!$endp || -1 == $endp)
			{
			$endp = 86400 + bab_mktime($arr['date_end_valid']);
			}
		else 
			{
			$endp += 86400;
			}
		
		$req = "select sum(el.quantity) total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user=".$db->quote($id_user)." and e.status='Y' and el.id_right=".$db->quote($arr['id'])." and el.id_entry=e.id";
		$row = $db->db_fetch_array($db->db_query($req));
				
		$qdp = isset($row['total'])? $row['total'] : 0;

		$req = "select sum(el.quantity) total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user=".$db->quote($id_user)." and e.status='' and el.id_right=".$db->quote($arr['id'])." and el.id_entry=e.id";
		$row = $db->db_fetch_array($db->db_query($req));

		$waiting = isset($row['total'])? $row['total'] : 0;

		if( $arr['ur_quantity'] != '')
			{
			$quantitydays = $arr['ur_quantity'] - $qdp;
			}
		else
			{
			$quantitydays = $arr['quantity'] - $qdp;
			}	
		
		if ( !empty($arr['id_right']) ) {
			// rules 

			$period_start = bab_mktime($arr['period_start']);
			$period_end = bab_mktime($arr['period_end']);
			
			if ($period_start != -1 && $period_end != -1) {
				// acces sur la période, en fonction de la période de la demande

				$access = !$access_on_period; // $access sera false si les droits on été demandés avec une période

				$period_acess = $arr['right_inperiod']+($arr['validoverlap']*10);
				switch ($period_acess)
					{
					case 0: // Toujours
					case 10:
						$access = true;
						break;
					
					case 1: // Dans la période de la règle
						if ($period_start <= $beginp && $period_end >= $endp) {
								$access = true;
								}
						break;
					
					case 2: // En dehors de la période de la règle
						if ($period_end <= $beginp || $period_start >= $endp) {
								$access = true;
								}
						break;
						
					case 11: // Dans la période de la règle mais peut dépasser à l'exterieur
						
						if ($period_start < $endp && $period_end > $beginp ) {
								bab_debug(
									$arr['description']."\n".
									bab_shortDate($period_start).' < '.bab_shortDate($endp). 
									' && '.bab_shortDate($period_end).' > '.bab_shortDate($beginp)
									);
								$access = true;
								}
						break;

					case 12: // En dehors de la période de la règle mais peut dépasser à l'intérieur
						if ($period_start > $beginp || $period_end < $endp) {
								$access = true;
								}
						break;
					}
				}

			

			
			// Attribution du droit en fonction des jours demandés et validés
			if ( $access ) {

				$p1 = '';
				$p2 = '';
				$req = '';

				if ('0000-00-00' != $arr['trigger_p1_begin'] && '0000-00-00' != $arr['trigger_p1_end']) {
					$p1 = "(e.date_begin < ".$db->quote($arr['trigger_p1_end'])." AND e.date_end > ".$db->quote($arr['trigger_p1_begin']).')';
				}

				if ('0000-00-00' != $arr['trigger_p2_begin'] && '0000-00-00' != $arr['trigger_p2_end']) {
					$p2 = "(e.date_begin < ".$db->quote($arr['trigger_p2_end'])." AND e.date_end > ".$db->quote($arr['trigger_p2_begin']).')';
				}

				if ($p1 && $p2) {
					$req = 'AND ('.$p1.' OR '.$p2.')';
				} else if ($p1 || $p2) {
					$req = 'AND '.$p1.$p2;
				}


				if ($req) { // une requete valide a pu être crée a partir des périodes
				
					if (!empty($arr['trigger_type']))
						{
						$table = ", ".BAB_VAC_RIGHTS_TBL." r ";
						$where = " AND el.id_right=r.id AND r.id_type='".$arr['trigger_type']."' ";
						}
					else
						{
						$table = '';
						$where = '';
						}
					
					$req = "SELECT 
						e.date_begin,
						e.date_end,
						sum(el.quantity) total 
						FROM 
							".BAB_VAC_ENTRIES_ELEM_TBL." el, 
							".BAB_VAC_ENTRIES_TBL." e 
							".$table." 
						WHERE  
							e.id_user=".$db->quote($id_user)." 
							and e.status='Y' 
							and el.id_entry=e.id 
							".$req.$where."
						GROUP BY e.id";

					$nbdays = 0;
					$res_entry = $db->db_query($req);
					while ($entry = $db->db_fetch_assoc($res_entry)) {

						list($entry_date_begin) = explode(' ',$entry['date_begin']);
						list($entry_date_end) = explode(' ',$entry['date_end']);

						$intersect_p1 = BAB_DateTime::periodIntersect(
								$entry_date_begin, 
								$entry_date_end, 
								$arr['trigger_p1_begin'], 
								$arr['trigger_p1_end']
							);

						if (false !== $intersect_p1) {
							$period_length = 1 + BAB_DateTime::dateDiffIso($intersect_p1['begin'], $intersect_p1['end']);
							// + 1 for end day
							if ($period_length < $entry['total']) {
								$nbdays += $period_length;
							} else {
								$nbdays += $entry['total'];
							}
						}

						$intersect_p2 = BAB_DateTime::periodIntersect(
								$entry['date_begin'], 
								$entry['date_end'], 
								$arr['trigger_p2_begin'], 
								$arr['trigger_p2_end']
							);

						if (false !== $intersect_p2) {
							$period_length = 1 + BAB_DateTime::dateDiffIso($intersect_p2['begin'], $intersect_p2['end']);
							// + 1 for end day
							if ($period_length < $entry['total']) {
								$nbdays += $period_length;
							} else {
								$nbdays += $entry['total'];
							}
						}
					
						if ($nbdays > 0) {
							bab_debug($arr['description']." - nb de jours pris:".$nbdays." min:".$arr['trigger_nbdays_min']." max:".$arr['trigger_nbdays_max']);
						}

						$access = false;
						if ( '' !== $arr['trigger_nbdays_min'] && '' !== $arr['trigger_nbdays_max'] && $arr['trigger_nbdays_min'] <= $nbdays && $nbdays <= $arr['trigger_nbdays_max'] ) {
							$access = true;
						}
					}
					
					
				} // endif ($req)	
			} // endif ($access)
		} // endif rule ($arr[id_right])

		

		if ( $access )
			$return[] = array(
						'id'				=> $arr['id'],
						'date_begin'		=> $arr['date_begin'],
						'date_end'			=> $arr['date_end'],
						'quantity'			=> $arr['quantity'],
						'description'		=> $arr['description'],
						'cbalance'			=> $arr['cbalance'],
						'quantitydays'		=> $quantitydays,
						'used'				=> $qdp,
						'waiting'			=> $waiting,
						'no_distribution'	=> $arr['no_distribution'],
						'id_rgroup'			=> $arr['id_rgroup'],
						'rgroup'			=> $arr['rgroup']
					);
		}
	return $return;
	}


function bab_getRightsByGroupOnPeriod($id_user, $rfrom = 0) {

	$arr = bab_getRightsOnPeriod(false, false, $id_user, $rfrom);
	$rights = array();
	foreach($arr as $right) {
		if (empty($right['id_rgroup'])) {
			$id				= 'r'.$right['id'];
			$description	= $right['description'];
		} else {
			$id = 'g'.$right['id_rgroup'];
			$description	= $right['rgroup'];	
		}

		if (isset($rights[$id])) {
				$quantity		= $rights[$id]['quantity'] + $right['quantity'];
				$quantitydays	= $rights[$id]['quantitydays'] + $right['quantitydays'];
				$used			= $rights[$id]['used'] + $right['used'];
				$waiting		= $rights[$id]['waiting'] + $right['waiting'];
			} else {
				$quantity		= $right['quantity'];
				$quantitydays	= $right['quantitydays'];
				$used			= $right['used'];
				$waiting		= $right['waiting'];
			}
		
		$rights[$id] = array(
			'quantity'		=> $right['quantity'],
			'description'	=> $description,
			'quantity'		=> $quantity,
			'quantitydays'	=> $quantitydays,
			'used'			=> $used,
			'waiting'		=> $waiting 
		);
	}

	return $rights;
}



function viewVacationCalendar($users, $period = false )
	{
	global $babBody;

	class temp
		{
		var $entries = array();
		var $fullname;
		var $vacwaitingtxt;
		var $vacapprovedtxt;
		var $print;
		var $close;
		var $emptylines = true;


		function temp($users, $period)
			{
			include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";


			$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : Date("n");
			$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : Date("Y");

			global $babMonths;
			$this->db = &$GLOBALS['babDB'];
			$this->month = $month;
			$this->year = $year;

			$this->userNameArr = array();
			foreach ($users as $uid)
				{
				$this->userNameArr[$uid] = bab_getUserName($uid);
				}

			natcasesort($this->userNameArr);

			$this->idusers = array_keys($this->userNameArr);
			$this->nbusers = count($this->idusers);
			$this->firstuser = current($this->userNameArr);
			
			$this->period = $period;
			$this->vacwaitingtxt = bab_translate("Waiting vacation request");
			$this->vacapprovedtxt = bab_translate("Approved vacation request");
			$this->t_selected = bab_translate("Selected period");
			$this->print = bab_translate("Print");
			$this->close = bab_translate("Close");

			$this->t_previousmonth = bab_translate("Previous month");
			$this->t_previousyear = bab_translate("Previous year");
			$this->t_nextmonth = bab_translate("Next month");
			$this->t_nextyear = bab_translate("Next year");

			$this->t_nonworking = bab_translate("Non-working day");
			$this->t_weekend = bab_translate("Week-end");
			$this->t_rotate = bab_translate("Print in landscape");
			$this->t_non_used = bab_translate("Non-used days");
			$this->t_waiting = bab_translate("Waiting vacation request");

			$this->t_waiting_vac = bab_translate("Waiting vacation request");
			$this->t_legend = bab_translate("Legend");
			
			$this->id_request = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

			$this->nbmonth = bab_rp('nbmonth',12);

			$urltmp = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&amp;idx=".$_REQUEST['idx']."&amp;id=".$this->id_request;

			


			if (!empty($_REQUEST['popup']))
				{
				$urltmp .= '&amp;popup=1';
				$this->popup = true;
				}

			if (isset($_REQUEST['ide']))
				{
				$urltmp .= '&amp;ide='.$_REQUEST['ide'];
				}
			else
				{
				$urltmp .= '&amp;idu='.implode(',',$this->idusers);
				}

			if (1 == $this->nbmonth) {
				$this->switchurl = $urltmp.'&amp;nbmonth=12';
				$this->switchlabel = bab_translate("Year view");
			} else {
				$this->switchurl = $urltmp.'&amp;nbmonth=1';
				$this->switchlabel = bab_translate("Month view");
			}

			$urltmp .= '&amp;nbmonth='.$this->nbmonth;


			$this->previousmonth	= $urltmp."&month=".date("n", mktime( 0,0,0, $month-1, 1, $year));
			$this->previousmonth	.= "&year=".date("Y", mktime( 0,0,0, $month-1, 1, $year));
			$this->nextmonth		= $urltmp."&month=". date("n", mktime( 0,0,0, $month+1, 1, $year));
			$this->nextmonth		.= "&year=". date("Y", mktime( 0,0,0, $month+1, 1, $year));

			$this->previousyear		= $urltmp."&month=".date("n", mktime( 0,0,0, $month, 1, $year-1));
			$this->previousyear		.= "&year=".date("Y", mktime( 0,0,0, $month, 1, $year-1));
			$this->nextyear			= $urltmp."&month=". date("n", mktime( 0,0,0, $month, 1, $year+1));
			$this->nextyear			.= "&year=". date("Y", mktime( 0,0,0, $month, 1, $year+1));

			if( $month != 1 )
				{
				$dateb = new BAB_DateTime($year, $month, 1);
				$this->yearname = ($year)."-".($year+1);
				}
			else
				{
				$dateb = new BAB_DateTime($year, 1, 1);
				$this->yearname = $year;
				}

			$datee = $dateb->cloneDate();
			$datee->add($this->nbmonth, BAB_DATETIME_MONTH);

			// find computed months

			$res = $this->db->db_query("
				SELECT 
					id_user, 
					monthkey 
				FROM ".BAB_VAC_CALENDAR_TBL." 
				WHERE 
					id_user IN(".$this->db->quote($this->idusers).") 
					AND cal_date BETWEEN ".$this->db->quote($dateb->getIsoDate())." 
					AND ".$this->db->quote($datee->getIsoDate())."  
				GROUP BY id_user, monthkey 
			");

			while($arr = $this->db->db_fetch_assoc($res)) {
				$this->db_month[$arr['monthkey']][$arr['id_user']] = 1;
			}

			


			$this->restypes = $this->db->db_query("
			
					SELECT 
						t.* 
					FROM 
						".BAB_VAC_TYPES_TBL." t, 
						".BAB_VAC_COLL_TYPES_TBL." ct, 
						".BAB_VAC_PERSONNEL_TBL." p 
					WHERE 
						p.id_user IN(".$this->db->quote($this->idusers).") 
						AND p.id_coll=ct.id_coll 
						AND ct.id_type=t.id 
					GROUP BY 
						t.id 
				");

		
			
		
		}

		function getdayname()
			{
			global $babDays;
			static $i = 1;
			if( $i <= 31)
				{
				$this->dayname = sprintf('%02d',$i);
				$i++;
				return true;
				}
			else
				return false;
			}
		
		function getnextuser()
			{
			static $i = 0;

			$n = $this->emptylines ? $this->nbusers : $this->nb_month_users;

			if ( $n == 0 )
				$n = 1;

			$this->rowspan = $this->emptylines ? $this->nbusers : $n;

			if ($i < $n)
				{
				$this->first = $i == 0 ;
				$this->id_user = $this->idusers[$i];
				$this->username = $this->userNameArr[$this->id_user];
					

				$key = $this->curmonth.$this->curyear;

				if (!isset($this->db_month[$key][$this->id_user])) {
					bab_vac_updateCalendar($this->id_user, $this->curyear, $this->curmonth);
				}
				
				$req = "
					SELECT 
						c.*, 
						d.nw_type,
						e.status 
					FROM 
						".BAB_VAC_CALENDAR_TBL." c 
						LEFT JOIN ".BAB_VAC_ENTRIES_TBL." e 
							ON e.id = c.id_entry 
						LEFT JOIN ".BAB_SITES_NONWORKING_DAYS_TBL." d 
							ON d.nw_day = c.cal_date AND id_site=".$this->db->quote($GLOBALS['babBody']->babsite['id'])."
					WHERE monthkey=".$this->db->quote($key)." AND c.id_user=".$this->db->quote($this->id_user)." 
						ORDER BY cal_date 
				";
				$res = $this->db->db_query($req);

				$this->periodIndex = array();
				while ($arr = $this->db->db_fetch_assoc($res)) {
					$key = $arr['cal_date'];
					$key .= $arr['ampm'] ? 'pm' : 'am';
					$this->periodIndex[$key] = $arr;
				}

				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}


		/**
		 * 
		 */
		function getmonth()
			{
			static $i = 0;
			if( $i < $this->nbmonth)
				{

				$dateb = new BAB_DateTime($this->year, $this->month + $i, 1);

				$this->curyear = $dateb->getYear();
				$this->curmonth = $dateb->getMonth();
				$this->monthname = $GLOBALS['babShortMonths'][$this->curmonth];
				$this->totaldays = date("t", $dateb->getTimeStamp());
				$this->previous_period = NULL;
				$i++;
				return true;
				}
			else
				return false;
			}

		function getday()
			{
			static $d = 1;
			static $total = 0;
			if( $d <= 31)
				{
				if( $d <= $this->totaldays )
					{
					$this->daynumbername = $d;
					$this->date = sprintf("%04d-%02d-%02d", $this->curyear, $this->curmonth, $d);
					$this->day_classname = '';
					$this->am_classname = 'weekend';
					$this->pm_classname = 'weekend';
					$this->am_color = '';
					$this->pm_color = '';
					$this->halfday = true;

					$this->tdtext = '';
					$this->am_text = '';
					$this->pm_text = '';

					$period_am = $this->previous_period;
					
					
					if (isset($this->periodIndex[$this->date.'am'])) {
						$period_am = $this->periodIndex[$this->date.'am'];
						$this->previous_period = $period_am;
					}
					
					$period_pm = $this->previous_period;
					
					if (isset($this->periodIndex[$this->date.'pm'])) {
						$period_pm = $this->periodIndex[$this->date.'pm'];
						$this->previous_period = $period_pm;
					}


					// pm

					if (BAB_PERIOD_NWDAY == $period_am['period_type']) {
						$this->day_classname = 'nonworking';
						$this->halfday = false;
						$this->tdtext = bab_translate($period_am['nw_type']);
						$d++;
						return true;

					} elseif (BAB_PERIOD_WORKING == $period_am['period_type']) {
						$this->am_classname = $this->period ? 'free' : 'default';

					} elseif (BAB_PERIOD_VACATION == $period_am['period_type']) {

						if ($period_am['id_entry'] == $this->id_request) {
							$this->am_classname = 'period';
						} else {
							if ('' == $period_am['status']) {
								$this->am_text = $this->t_waiting_vac;
								$this->am_classname = 'wait';
							} else {
								$this->am_classname = 'used';
							}
							$this->am_color = $period_am['color'];
						}
					}

					
					// pm

					if (BAB_PERIOD_WORKING == $period_pm['period_type']) {
						$this->pm_classname = $this->period ? 'free' : 'default';

					} elseif (BAB_PERIOD_VACATION == $period_pm['period_type']) {
						
						if ($period_pm['id_entry'] == $this->id_request) {
							$this->pm_classname = 'period';
						} else {
							if ('' == $period_pm['status']) {
								$this->pm_text = $this->t_waiting_vac;
								$this->pm_classname = 'wait';
							} else {
								$this->pm_classname = 'used';
							}
							$this->pm_color = $period_pm['color'];
						}
					}



					}
				else
					{
					$this->day_classname = 'noday';
					$this->halfday = false;
					$this->titledate = '';
					}


					
				$d++;
				return true;
				}
			else
				{
				$d = 1;
				return false;
				}
			}

		function getnexttype()
			{
			if ($this->arr = $this->db->db_fetch_array($this->restypes))
				{
				return true;
				}
			else
				{
				return false;
				}
			}

		function printhtml($template = true)
			{
			if ($template)
				$html = & bab_printTemplate($this,"vacuser.html", "calendarbyuser");
			else
				$html = '';

			$GLOBALS['babBody']->addStyleSheet('vacation.css');

			if (isset($_REQUEST['popup']) && $_REQUEST['popup'] == 1) {
				$GLOBALS['babBody']->babpopup($html);
				}
			else {
				$GLOBALS['babBody']->babecho($html);
				}
			}
		}

	if (count($users) == 0)
		{
		$GLOBALS['babBody']->msgerror = bab_translate("ERROR: No members");
		temp::printhtml(false);
		}

	$temp = & new temp($users, $period);
	$temp->printhtml();
	}


function listVacationRequests($id_user)
{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $editurl;
		var $begindatetxt;
		var $enddatetxt;
		var $quantitytxt;
		var $statustxt;
		var $begindate;
		var $enddate;
		var $quantity;
		var $status;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $statarr;
		var $total;
		var $checkall;
		var $uncheckall;

		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $pos;

		var $entryid;
		var $altbg = true;

		function temp($id_user)
			{

			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->nametxt = bab_translate("Fullname");
			$this->begindatetxt = bab_translate("Begin date");
			$this->enddatetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->statustxt = bab_translate("Status");
			$this->calendar = bab_translate("Planning");
			$this->t_edit = bab_translate("Modification");

			$this->t_first_page = bab_translate("First page");
			$this->t_previous_page = bab_translate("Previous page");
			$this->t_next_page = bab_translate("Next page");
			$this->t_last_page = bab_translate("Last page");

			$this->t_delete = bab_translate("Delete");

			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";

			$this->t_position = '';

			if (is_array($id_user))
				$id_user = implode(',',$id_user);

			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$id_user."&popup=1";
			$this->personal = $id_user == $GLOBALS['BAB_SESS_USERID'];
			$this->pos = isset($_REQUEST['pos']) ? $_REQUEST['pos'] : 0;
			$this->db = $GLOBALS['babDB'];

			$req = "".BAB_VAC_ENTRIES_TBL." where id_user IN(".$id_user.")";

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{
				$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : '';
				$ide = isset($_REQUEST['ide']) ? $_REQUEST['ide'] : '';
				$id_user = isset($_REQUEST['id_user']) ? $_REQUEST['id_user'] : '';
				$tmpurl = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$idx."&ide=".$ide."&id_user=".$id_user."&pos=";

				
				$page_number = 1 + ($this->pos / VAC_MAX_REQUESTS_LIST);
				$page_total = 1 + ($total / VAC_MAX_REQUESTS_LIST);
				$this->t_position = sprintf(bab_translate("Page %d/%d"), $page_number,$page_total);
				
				if( $this->pos > 0)
					{
					$this->topurl = $tmpurl."0";
					}

				$next = $this->pos - VAC_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $tmpurl.$next;
					}

				$next = $this->pos + VAC_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $tmpurl.$next;
					if( $next + VAC_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $tmpurl.$bottom;
					}
				}

			$req .= " order by date desc";
			if( $total > VAC_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$this->pos.",".VAC_MAX_REQUESTS_LIST;
				}
			$this->res = $this->db->db_query("select * from ".$req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));
			}

		function getnext()
			{

			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacuser&idx=morve&id=".$arr['id'];
				list($this->quantity) = $this->db->db_fetch_row($this->db->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$arr['id']."'"));
				$this->urlname = bab_getUserName($arr['id_user']);

				$begin_ts = bab_mktime($arr['date_begin']);
				$end_ts = bab_mktime($arr['date_end']);

				$this->begindate = bab_vac_shortDate($begin_ts);
				$this->enddate = bab_vac_shortDate($end_ts);
				
				$this->urledit = $GLOBALS['babUrlScript']."?tg=vacuser&amp;idx=period&amp;id=".$arr['id']."&amp;year=".date('Y',$begin_ts)."&amp;month=".date('n',$begin_ts);

				$this->urldelete = $GLOBALS['babUrlScript']."?tg=vacuser&amp;idx=delete&amp;id_entry=".$arr['id'];

				$personal = $arr['id_user'] == $GLOBALS['BAB_SESS_USERID'];

				switch($arr['status'])
					{
					case 'Y':
						$this->status = $this->statarr[1];
						$this->modify = !$personal;
						break;
					case 'N':
						$this->status = $this->statarr[2];
						$this->modify = false;
						break;
					default:
						$this->status = $this->statarr[0];
						$this->modify = $personal;
						break;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	if (empty($id_user)) {
		$babBody->msgerror = bab_translate("ERROR: No members");
		return;
	}

	$temp = new temp($id_user);
	$babBody->babecho(bab_printTemplate($temp, "vacuser.html", "vrequestslist"));
	return $temp->count;
}


function listRightsByUser($id)
	{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $descriptiontxt;
		var $description;
		var $consumedtxt;
		var $consumed;
		var $fullname;
		var $titletxt;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $iduser;
		var $idcoll;
		var $bview;

		var $updatetxt;
		var $invalidentry;
		var $invalidentry1;
		var $invalidentry2;

		function temp($id)
			{
			$this->iduser = $id;
			$this->updatetxt = bab_translate("Update");
			$this->desctxt = bab_translate("Description");
			$this->typetxt = bab_translate("Type");
			$this->consumedtxt = bab_translate("Consumed");
			$this->datebtxt = bab_translate("Begin date");
			$this->dateetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->datetxt = bab_translate("Entry date");
			$this->invalidentry = bab_translate("Invalid entry!  Only numbers are accepted or . !");
			$this->invalidentry = str_replace("'", "\'", $this->invalidentry);
			$this->invalidentry = str_replace('"', "'+String.fromCharCode(34)+'",$this->invalidentry);
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$GLOBALS['babBody']->title = bab_translate("Vacation rights of:").' '.bab_getUserName($id);

			$this->tg = $_REQUEST['tg'];

			$this->db = & $GLOBALS['babDB'];
			$this->res = $this->db->db_query("
				SELECT 
					u.id_user,
					u.id_right,
					u.quantity, 
					r.id_type,
					t.name type, 
					r.description,
					r.quantity r_quantity,
					YEAR(r.date_begin) year,
					r.date_begin,
					r.date_end,
					IFNULL(rg.name, r.description) label,
					rg.name rgroup 
				FROM 
					".BAB_VAC_USERS_RIGHTS_TBL." u,
					".BAB_VAC_RIGHTS_TBL." r 
					LEFT JOIN 
						".BAB_VAC_RGROUPS_TBL." rg ON rg.id = r.id_rgroup 
					, 
					".BAB_VAC_TYPES_TBL." t 
				WHERE id_user=".$this->db->quote($id)." 
					AND r.id = u.id_right 
					AND t.id = r.id_type 
					ORDER BY year DESC, label ASC
			");

			$this->count = $this->db->db_num_rows($this->res);
			list($this->idcoll) = $this->db->db_fetch_row($this->db->db_query("select id_coll from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$id."'"));
			}

		function getnextright()
			{
			static $y = '';
			static $label = '';
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$res = $this->db->db_query("SELECT id from ".BAB_VAC_COLL_TYPES_TBL." WHERE id_coll='".$this->idcoll."' and id_type='".$arr['id_type']."'");
				$this->bview = false;
				if( $res && $this->db->db_num_rows($res) > 0 )
					{
					$this->idright = $arr['id_right'];
					$this->description = bab_toHtml($arr['description']);
					$this->type = bab_toHtml($arr['type']);
					if( $arr['quantity'] != '' )
						$this->quantity = $arr['quantity'];
					else
						$this->quantity = $arr['r_quantity'];

					$this->year = $arr['year'] !== $y ? $arr['year'] : '';
					$y = $arr['year'];

					$this->rgroup = $arr['rgroup'] !== $label && !empty($arr['rgroup']) ? $arr['rgroup'] : '';
					$label = $arr['rgroup'];

					$this->in_rgroup = !empty($arr['rgroup']);

					$this->dateb = bab_shortDate(bab_mktime($arr['date_begin']), false);
					$this->datee = bab_shortDate(bab_mktime($arr['date_end']), false);
					$arr = $this->db->db_fetch_array($this->db->db_query(
						"SELECT sum(quantity) as total 
						FROM 
							".BAB_VAC_ENTRIES_ELEM_TBL." ee, 
							".BAB_VAC_ENTRIES_TBL." e
						WHERE 
								e.id_user = ".$this->db->quote($this->iduser)." 
							AND e.status = 'Y' 
							AND ee.id_right = ".$this->db->quote($this->idright)." 
							AND ee.id_entry = e.id
						"));
					$this->consumed = isset($arr['total'])? $arr['total'] : 0;
					$this->bview = true;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$GLOBALS['babBody']->babPopup(bab_printTemplate($temp, "vacadm.html", "rlistbyuser"));
	}

function updateVacationRightByUser($userid, $quantities, $idrights)
{
	global $babDB;
	for($i = 0; $i < count($idrights); $i++)
		{
		list($quantity) = $babDB->db_fetch_array($babDB->db_query("select quantity from ".BAB_VAC_RIGHTS_TBL." where id='".$idrights[$i]."'"));
		if( $quantity != $quantities[$i] )
			$quant = $quantities[$i];
		else
			$quant = '';

		$babDB->db_query("update ".BAB_VAC_USERS_RIGHTS_TBL." set quantity='".$quant."' where id_user='".$userid."' and id_right='".$idrights[$i]."'");
		}
}


function rlistbyuserUnload($msg)
	{
	class temp
		{
		var $message;
		var $close;

		function temp($msg)
			{
			$this->message = $msg;
			$this->close = bab_translate("Close");
			}
		}

	$temp = new temp($msg);
	echo bab_printTemplate($temp,"vacadm.html", "rlistbyuserunload");
	}


function addVacationPersonnel($idp = false)
	{
	global $babBody;
	class temp
		{
		var $usertext;
		var $grouptext;
		var $userval;
		var $userid;
		var $groupval;
		var $groupid;
		var $collection;
		var $idcollection;
		var $collname;
		var $appschema;
		var $idsapp;
		var $saname;
		var $selected;
		var $add;
		var $bdel;
		var $delete;
		var $groupsbrowurl;
		var $usersbrowurl;
		var $db;
		var $orand;
		var $reset;

		function temp($idp)
			{
			$this->usertext = bab_translate("User");
			$this->collection = bab_translate("Collection");
			$this->appschema = bab_translate("Approbation schema");
			$this->delete = bab_translate("Delete");
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=browu&cb=";
			$this->tg = $_REQUEST['tg'];
			$this->ide = isset($_REQUEST['ide']) ? $_REQUEST['ide'] : false;

			$this->db = & $GLOBALS['babDB'];

			$this->idp = $idp;

			list($n) = $this->db->db_fetch_array($this->db->db_query("SELECT COUNT(*) FROM ".BAB_VAC_ENTRIES_TBL." WHERE id_user='".$this->idp."' AND status=''"));

			if ($n > 0)
				$this->whaiting = bab_translate('Modification are disabled, the user have').' '.$n.' '.bab_translate('whaiting request(s)').'.';

			if (isset($_POST['action']) && $_POST['action'] == 'changeuser')
				{
				$this->userid = $_POST['userid'];
				$this->idsa = $_POST['idsa'];
				$this->idcol = $_POST['idcol'];
				$this->idp = $_POST['idp'];
				}

			if( !empty($this->idp))
				{
				$this->add = bab_translate("Modify");
				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$this->idp."'"));
				$this->userid = $arr['id_user'];
				$this->userval = bab_getUserName($this->idp);
				$this->idcol = $arr['id_coll'];
				$this->idsa = $arr['id_sa'];
				}
			else
				{
				$this->add = bab_translate("Add");
				$this->idcol = '';
				$this->idsa = '';
				$this->userval = '';
				$this->userid = '';
				}

			$this->groupval = "";
			$this->groupid = "";

			$this->sares = $this->db->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
			if( !$this->sares )
				$this->countsa = 0;
			else
				$this->countsa = $this->db->db_num_rows($this->sares);

			$this->colres = $this->db->db_query("select * from ".BAB_VAC_COLLECTIONS_TBL." order by name asc");
			$this->countcol = $this->db->db_num_rows($this->colres);
			}
		
		function getnextsa()
			{
			static $j= 0;
			if( $j < $this->countsa )
				{
				$arr = $this->db->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->idsapp = $arr['id'];
				$this->idsapp = $arr['id'];
				if( $this->idsa == $this->idsapp )
					$this->selected = "selected";
				else
					$this->selected = "";
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				if ($this->countsa > 0)
					$this->db->db_data_seek($this->sares,0);
				return false;
				}
			}

		function getnextcol()
			{
			static $j= 0;
			if( $j < $this->countcol )
				{
				$arr = $this->db->db_fetch_array($this->colres);
				$this->collname = $arr['name'];
				$this->idcollection = $arr['id'];
				if( $this->idcol == $this->idcollection )
					$this->selected = "selected";
				else
					$this->selected = "";
				$j++;
				return true;
				}
			else
				return false;
			}

		function printhtml()
			{
			$GLOBALS['babBody']->babecho(	bab_printTemplate($this,"vacadm.html", "personnelcreate"));
			}
		}

	$temp = new temp($idp);
	$temp->printhtml();
	}

function bab_IsUserUnderSuperior($id_user)
{
	if ($id_user == $GLOBALS['BAB_SESS_USERID'])
		return true;

	$user_entities = & bab_OCGetUserEntities($id_user);
	$user_entities = array_merge($user_entities['superior'], $user_entities['temporary'], $user_entities['members']);
	foreach($user_entities as $entity)
		{
		$user_entities_id[$entity['id']] = $entity['id'];
		}

	$arr = & bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
	bab_addCoManagerEntities($arr, $GLOBALS['BAB_SESS_USERID']);

	$childs = array();
	foreach ($arr['superior'] as $entity)
		{
		$childs[] = $entity;
		$tmp = & bab_OCGetChildsEntities($entity['id']);
		$childs = array_merge($childs, $tmp);
		}

	foreach($childs as $entity)
		{
		if (isset($user_entities_id[$entity['id']]))
			return true;
		}
	return false;
}

function updateVacationUser($userid, $idsa)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id_user='".$userid."' and status=''");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( $row['idfai'] != 0 )
			deleteFlowInstance($row['idfai']);
		$idfai = makeFlowInstance($idsa, "vac-".$row['id']);
		$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyVacationApprovers($row['id'], $nfusers);
		}
}

function updateUserColl()
{
	$db = & $GLOBALS['babDB'];

	if (empty($_POST['idp']))
		{
		return false;
		}

	$users_rights = array();
	$worked_ids = array();

	$res = $db->db_query("SELECT id,id_right FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id_user='".$_POST['idp']."'");
	while($arr = $db->db_fetch_array($res))
		{
		$users_rights[$arr['id_right']] = $arr['id'];
		}

	$old_rights = bab_getRightsOnPeriod(false, false, $_POST['idp']);
	$used = array();
	foreach($old_rights as $r)
		{
		$used[$r['id']] = $r['used'];
		}

	$prefix = 'right_';
	$post_rights = array();

	/* control */

	foreach($_POST as $field => $value)
		{
		if (substr($field,0,strlen($prefix)) == $prefix)
			{
			list(,$id_right) = explode('_',$field);
			if (isset($used[$id_right]))
				{
				$value += $used[$id_right];
				}

			$post_rights[$id_right] = $value;

			if ($value < 0)
				{
				list($name,$cbalance) = $db->db_fetch_array($db->db_query("SELECT description,cbalance FROM ".BAB_VAC_RIGHTS_TBL." WHERE id='".$id_right."'"));
				
				if ($cbalance == 'N')
					{
					$GLOBALS['babBody']->msgerror = bab_translate("Negative balance are not alowed on right").' '.$name;
					return false;

					}
				}
			}
		}

	/* RECORD */

	foreach($post_rights as $id_right => $value)
		{
		if (isset($users_rights[$id_right]))
			{
			$db->db_query("UPDATE ".BAB_VAC_USERS_RIGHTS_TBL." SET quantity='".$value."' WHERE id='".$users_rights[$id_right]."'");
			$worked_ids[] = $users_rights[$id_right];
			}
		else
			{
			$db->db_query("INSERT INTO ".BAB_VAC_USERS_RIGHTS_TBL." (id_user,id_right,quantity) VALUES ('".$_POST['idp']."','".$id_right."','".$value."')");
			$worked_ids[] = $db->db_insert_id();
			}
		}

	if (count($worked_ids) > 0)
		{
		$db->db_query("DELETE FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id NOT IN(".implode(',',$worked_ids).") AND id_user= '".$_POST['idp']."'");
		}

	$db->db_query("UPDATE ".BAB_VAC_PERSONNEL_TBL." SET id_coll='".$_POST['idcol']."' WHERE id_user='".$_POST['idp']."'");

	return true;
}


function changeucol($id_user,$newcol)
	{
	global $babBody;

	class tempa
		{
		var $altbg = true;
		
		function tempa($id_user,$newcol)
			{
			$this->t_oldcol = bab_translate("Old collection");
			$this->t_newcol = bab_translate("New collection");
			$this->t_record = bab_translate("Record");
			$this->t_quantity = bab_translate("Quantity");
			$this->t_nbdays = bab_translate("Day(s)");
			$this->t_right = bab_translate("Rights");
			$this->t_balance = bab_translate("Balance");

			$this->db = & $GLOBALS['babDB'];
			$this->tg = $_REQUEST['tg'];


			// Les droits de l'ancien régime sont les même que ceux afficher sur la première page d'une demande de congé pour l'utilisateur.

			$old_rights = bab_getRightsOnPeriod(false, false, $id_user);

			$this->id_user = $id_user;
			$this->id_coll = $newcol;

			$req = "
					SELECT 
						c.name new, 
						IFNULL(c2.name,'') old 
					FROM 
						".BAB_VAC_PERSONNEL_TBL." p
					LEFT JOIN 
						".BAB_VAC_COLLECTIONS_TBL." c2 
						ON c2.id = p.id_coll , 
						".BAB_VAC_COLLECTIONS_TBL." c 
					
					WHERE 
						p.id_user='".$id_user."' AND c.id='".$newcol."'
					";

			$arr = $this->db->db_fetch_array($this->db->db_query($req));


			$this->oldcol = $arr['old'];
			$this->newcol = $arr['new'];

			$req = "SELECT r.* FROM ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_COLL_TYPES_TBL." t WHERE t.id_type = r.id_type AND t.id_coll='".$newcol."' ORDER BY r.description";
			$res = $this->db->db_query($req);
			
			$new_rights = array();
			while ($arr = $this->db->db_fetch_array($res))
				{
				$new_rights[] = array(
							'id' =>			$arr['id'],
							'date_begin' => $arr['date_begin'],
							'date_end' =>   $arr['date_end'],
							'quantity' =>   $arr['quantity'],
							'description' =>$arr['description']
							);
				}
			
			$this->totaldays = 0;

			$this->rights = array();

			foreach ($old_rights as $v)
				{
				$this->rights[$v['id']] = array( 
							'description' => $v['description'], 
							'quantity_old' => $v['quantity'],
							'quantitydays' => $v['quantitydays']
							);
				}

			foreach ($new_rights as $v)
				{
				if (!isset($this->rights[$v['id']]))
					{
					$this->rights[$v['id']] = array( 
							'description' => $v['description'], 
							'quantity_new' => $v['quantity'],
							'quantitydays' => ''
							);
					}
				else
					{
					$this->rights[$v['id']]['description'] = $v['description'];
					$this->rights[$v['id']]['quantity_new'] = $v['quantity'];
					}
				}
			}

		function getnext()
			{
			if (list($this->id,$this->right) = each($this->rights))
				{
				$this->altbg = !$this->altbg;
				$default = (isset($this->right['quantity_new']) && $this->right['quantitydays'] > $this->right['quantity_new']) || !is_numeric($this->right['quantitydays']) ? $this->right['quantity_new'] : $this->right['quantitydays'];
				$this->newrightvalue = isset($_POST['right_'.$this->id]) ? $_POST['right_'.$this->id] : $default;
				if (!isset($this->right['quantity_new']))
					$this->right['quantity_new'] = '';
				if (!isset($this->right['quantity_old']))
					$this->right['quantity_old'] = '';
				return true;
				}
			else
				{
				return false;
				}
			}
		}


	$tempa = new tempa($id_user,$newcol);
	$babBody->babecho(	bab_printTemplate($tempa,"vacadm.html", "changeucol"));

	}

function updateVacationPersonnel($iduser, $idsa)
	{
	global $babBody, $babDB;

	if( empty($idsa) )
		{
		$babBody->msgerror = bab_translate("You must specify an aprobation schema") ." !";
		return false;
		}

	if( !empty($iduser))
		{
		$res = $babDB->db_query("select id, id_sa,id_user from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$iduser."'");

		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);

			$babDB->db_query("UPDATE ".BAB_VAC_PERSONNEL_TBL." SET id_sa='".$idsa."' where id='".$arr['id']."'");

			if( $arr['id_sa'] != $idsa )
				{
				updateVacationUser($arr['id_user'], $idsa);
				}

			}
		else
			{
			$babDB->db_query("INSERT INTO ".BAB_VAC_PERSONNEL_TBL." ( id_user,id_sa ) VALUES ('".$iduser."', '".$idsa."' )");
			}
		}

	
	return true;
	}

function saveVacationPersonnel($userid,  $idcol, $idsa)
	{
	global $babBody, $babDB;
	if( empty($userid) )
		{
		$babBody->msgerror = bab_translate("You must specify a user") ." !";
		return false;
		}

	if( empty($idcol) )
		{
		$babBody->msgerror = bab_translate("You must specify a vacation collection") ." !";
		return false;
		}

	if( empty($idsa) )
		{
		$babBody->msgerror = bab_translate("You must specify approbation schema") ." !";
		return false;
		}


	$res = $babDB->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$userid."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This user already exist in personnel list") ." !";
		return false;
		}

	$babDB->db_query("insert into ".BAB_VAC_PERSONNEL_TBL." ( id_user, id_coll, id_sa) values ('".$userid."','".$idcol."','".$idsa."')");

	// create default user rights

	$babDB->db_query("DELETE FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id_user='".$userid."'");

	$res = $babDB->db_query("SELECT r.id FROM ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_COLL_TYPES_TBL." t WHERE r.id_type=t.id_type AND t.id_coll='".$idcol."'");

	while($r = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("INSERT INTO ".BAB_VAC_USERS_RIGHTS_TBL." ( id_user,  id_right ) VALUES ('".$userid."','".$r['id']."')");
		}
	
	return true;
	}


/**
 * @param	string		$date	0000-00-00
 * @param	boolean		$b_pm
 * @param	boolean		$b_end
 * @see		BAB_DateTime
 * @return	object		instanceof BAB_DateTime
 */
function getDateFromHalfDay($date, $b_pm, $b_end) {
	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

	if ($b_end) {
		if ($b_pm) {
			$h = 23;
		} else {
			$h = 11;
		}
		$m = 59;
		$s = 59;	
	} else {
		if ($b_pm) {
			$h = 12;
		} else {
			$h = 0;
		}
		$m = 0;
		$s = 0;
	}

	$arr = explode('-',$date);
	return new BAB_DateTime($arr[0], $arr[1], $arr[2], $h, $m, $s);
}


/**
 * Date display for vacation period
 * @param int $timestamp
 * @return string
 

class bab_vacDate {

	function bab_vacDate($begin, $end) {
		$this->begin = $begin;
		$this->end = $end;

		$this->begin_half	= date('a',$begin);
		$this->end_half		= date('a',$end);
	}

	function begin() {
		$date = bab_longDate($timestamp, false);
		$ampm = date('a',$timestamp);
		$date .= 'am' == $ampm ? bab_translate('Morning') : bab_translate('Afternoon');
	}
}
*/



/**
 * Notify on vacation change
 * @param int		$idusers
 * @param int		$quantity
 * @param string	$date_begin		0000-00-00 00:00:00
 * @param string	$date_end		0000-00-00 00:00:00
 * @param string	$what
 */
function notifyOnVacationChange($idusers, $quantity, $date_begin, $date_end, $what)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL;

	if(!class_exists("notifyOnVacationChangeCls"))
		{
		class notifyOnVacationChangeCls
			{

			function notifyOnVacationChangeCls($quantity, $date_begin,  $date_end, $msg)
				{
				global $babDB;
				$this->message = $msg;
				$this->from = bab_translate("from");
				$this->until = bab_translate("until");
				$this->quantitytxt = bab_translate("Quantity");
				$this->begindate = bab_strftime(bab_mktime($date_begin));
				$this->enddate = bab_strftime(bab_mktime($date_end));
				$this->quantity = $quantity;
				}
			}

		$cntusers = count($idusers);

		if( $cntusers > 0 )
			{
			$mail = bab_mail();
			if( $mail == false )
				return;

			$mail->mailFrom($BAB_SESS_EMAIL, $BAB_SESS_USER);
			switch($what)
				{
				case VAC_FIX_UPDATE: $msg = bab_translate("Vacation has been updated");	break;
				case VAC_FIX_DELETE: $msg = bab_translate("Vacation has been deleted");	break;
				default: $msg = bab_translate("Vacation has been scheduled");	break;
				}

			$mail->mailSubject($msg);

			$tempb = new notifyOnVacationChangeCls($quantity, $date_begin, $date_end, $msg);
			$message = $mail->mailTemplate(bab_printTemplate($tempb,"mailinfo.html", "newfixvacation"));
			$mail->mailBody($message, "html");

			$message = bab_printTemplate($tempb,"mailinfo.html", "newfixvacationtxt");
			$mail->mailAltBody($message);
			
			for( $i=0; $i < $cntusers; $i++)
				{
				$mail->clearTo();
				$mail->mailTo(bab_getUserEmail($idusers[$i]), bab_getUserName($idusers[$i]));
				$mail->send();
				}
			}
		}
	}


function bab_isPlanningAccessValid()
{
	global $babDB;
	$res = $babDB->db_query("SELECT id_user FROM ".BAB_VAC_PLANNING_TBL." WHERE id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID']));
	return  $babDB->db_num_rows($res) > 0;
}


function bab_getVacationOption($field) {
	$db = &$GLOBALS['babDB'];

	static $arr = null;

	if (null == $arr) {
		$res = $db->db_query("SELECT * FROM ".BAB_VAC_OPTIONS_TBL);
		if (0 < $db->db_num_rows($res)) {
			$arr = $db->db_fetch_assoc($res);
		} else {
			$arr = array(
				'chart_superiors_create_request' => 0	
			);
		}
	}

	return $arr[$field];
}


/**
 * Push and get into a stack
 * @param int	$id_entry
 * @param mixed $push
 *
 * $push = array(
 *		type, color
 *	)
 */
function bab_vac_typeColorStack($id_entry, $push = false) {
	static $stack = array();

	if (!isset($stack[$id_entry])) {
		$stack[$id_entry] = array();
	}
	
	if (false === $push) {
		return array_pop($stack[$id_entry]);
	}

	$stack[$id_entry][] = $push;
}




/**
 * set vacation events into object
 * @see bab_userWorkingHours 
 * @param object	$obj bab_userWorkingHours instance
 * @param array		$id_users
 * @param object	$begin
 * @param object	$end
 */
function bab_vac_setVacationPeriods(&$obj, $id_users, $begin, $end) {
	$db = $GLOBALS['babDB'];

	$res = $db->db_query("
	SELECT * from ".BAB_VAC_ENTRIES_TBL." 
		WHERE 
		id_user IN(".$db->quote($id_users).")   
		AND status!='N' 
		AND date_end > ".$db->quote($begin->getIsoDateTime())." 
		AND date_begin < ".$db->quote($end->getIsoDateTime())."");

	

	while( $row = $db->db_fetch_array($res)) {

		if ('N' === $row['status']) {
			continue;
		}

		$colors = array();
		$types	= array();

		$date_begin = BAB_DateTime::fromIsoDateTime($row['date_begin']);
		$date_end	= BAB_DateTime::fromIsoDateTime($row['date_end']);

		$req = "SELECT 
				e.quantity, 
				t.name type, 
				t.color 
			FROM ".BAB_VAC_ENTRIES_ELEM_TBL." e,
				".BAB_VAC_RIGHTS_TBL." r,
				".BAB_VAC_TYPES_TBL." t 
			WHERE 
				e.id_entry=".$db->quote($row['id'])." 
				AND r.id=e.id_right 
				AND t.id=r.id_type";

		$res2 = $db->db_query($req);

		$count = $db->db_num_rows($res2);

		$type_day		= $date_begin->cloneDate();
		$type_day_end	= $date_begin->cloneDate();

		
		while ($arr = $db->db_fetch_array($res2))
			{
			$type_day_end->add(($arr['quantity']*86400), BAB_DATETIME_SECOND);

			while ($type_day->getTimeStamp() < $type_day_end->getTimeStamp() ) {

				bab_vac_typeColorStack(
					$row['id'], 
					array(
						'id_type'	=> $arr['type'], 
						'color'		=> $arr['color']
					)
				);

				$type_day->add(12, BAB_DATETIME_HOUR);
				}
			}


		$p = & $obj->setUserPeriod($row['id_user'], $date_begin, $date_end, BAB_PERIOD_VACATION);

		list($category, $color) = $db->db_fetch_row($db->db_query("
		
			SELECT 
				cat.name,
				cat.bgcolor  
			FROM 
				".BAB_VAC_COLLECTIONS_TBL." vct,
				".BAB_VAC_PERSONNEL_TBL." vpt, 
				".BAB_VAC_ENTRIES_TBL." vet, 
				".BAB_CAL_CATEGORIES_TBL." cat 
			WHERE 
				vpt.id_coll=vct.id 
				AND vet.id_user=vpt.id_user 
				AND vet.id=".$db->quote($row['id'])." 
				AND cat.id = vct.id_cat 
		"));

		$data = array('id' => $row['id']);
		$p->setData($data);

		
		$p->setProperty('SUMMARY'		, bab_translate("Vacation"));
		$p->setProperty('DTSTART'		, $date_begin->getIsoDateTime());
		$p->setProperty('DTEND'			, $date_end->getIsoDateTime());
		$p->setProperty('CATEGORIES'	, $category);
		$p->color = $color;

		if ('Y' !== $row['status']) {
			$p->setProperty('DESCRIPTION',bab_translate("Waiting to be validate"));
		}

	}
}


/**
 * Clear calendar data
 * On non-working days changes by admin
 * On working hours changes by admin
 */
function bab_vac_clearCalendars() {
	$db = $GLOBALS['babDB'];
	$db->db_query("DELETE FROM ".BAB_VAC_CALENDAR_TBL."");
}


/**
 * Clear calendar data for user
 */
function bab_vac_clearUserCalendar($id_user = NULL) {
	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}
	$db = $GLOBALS['babDB'];
	$db->db_query("DELETE FROM ".BAB_VAC_CALENDAR_TBL." WHERE id_user=".$db->quote($id_user));
}

/**
 * Update calendar data overlapped with event
 * @param int $id_event
 */
function bab_vac_updateEventCalendar($id_entry) {
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("
		SELECT 
			id_user,
			date_begin, 
			date_end 
		FROM 
			".BAB_VAC_ENTRIES_TBL." 
		WHERE 
			id=".$db->quote($id_entry)
	);
	$event = $db->db_fetch_assoc($res);
	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

	$date_begin = BAB_DateTime::fromIsoDateTime($event['date_begin']);
	$date_end	= BAB_DateTime::fromIsoDateTime($event['date_end']);

	while ($date_begin->getTimeStamp() <= $date_end->getTimeStamp()) {
		$month	= $date_begin->getMonth();
		$year	= $date_begin->getYear();
		bab_vac_updateCalendar($event['id_user'], $year, $month);
		$date_begin->add(1, BAB_DATETIME_MONTH);
	}
}



/**
 * Update planning for the given user
 * and the given period
 * @param int		$id_user
 * @param int		$year
 * @param int		$month
 */
function bab_vac_updateCalendar($id_user, $year, $month) {

	$db = $GLOBALS['babDB'];
	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";

	$db->db_query("DELETE FROM ".BAB_VAC_CALENDAR_TBL." WHERE monthkey=".$db->quote($month.$year));

	$dateb = new BAB_dateTime($year, $month, 1); 
	$datee = $dateb->cloneDate();
	$datee->add(1, BAB_DATETIME_MONTH);

	$obj = new bab_userWorkingHours( 
			$dateb, 
			$datee
		);

	$obj->addIdUser($id_user);
	$obj->createPeriods(BAB_PERIOD_NWDAY | BAB_PERIOD_NONWORKING | BAB_PERIOD_WORKING | BAB_PERIOD_VACATION);
	$obj->orderBoundaries();

	if (!function_exists('bab_vac_compare')) {
		/**
		 * si type2 est prioritaire, return true
		 */
		function bab_vac_compare($type1, $type2) {

			$order = array(

				BAB_PERIOD_NONWORKING		=> 1,
				BAB_PERIOD_WORKING 			=> 2,
				BAB_PERIOD_CALEVENT			=> 3,
				BAB_PERIOD_TSKMGR			=> 4,
				BAB_PERIOD_VACATION			=> 5,
				BAB_PERIOD_NWDAY			=> 6
			);

			
			if ($order[$type2] > $order[$type1]) {
				return true;
			}

			/*


			if (BAB_PERIOD_NWDAY === $type2) {
				return true;
			}

			if (BAB_PERIOD_NWDAY === $type1) {
				return false;
			}

			if (BAB_PERIOD_WORKING === $type2 && BAB_PERIOD_NONWORKING === $type1) {
				return true;
			}

			if ($type2 > $type1) {
				return true;
			}
			*/

			return false;
		}

		function bab_vac_is_free($p) {
			switch($p->type) {
				case BAB_PERIOD_WORKING:
				case BAB_PERIOD_CALEVENT:
				case BAB_PERIOD_TSKMGR:
					return true;

				case BAB_PERIOD_VACATION:
				case BAB_PERIOD_NONWORKING:
				case BAB_PERIOD_NWDAY:
					return false;
			}
		}

		function bab_vac_group_insert($query, $exec = false) {
			static $values = array();
			if ($query) {
				$values[] = $query;
			}
			if (300 <= count($values) || (0 < count($values) && $exec)) {

				$GLOBALS['babDB']->db_query("
				INSERT INTO ".BAB_VAC_CALENDAR_TBL." 
					(id_user, monthkey, cal_date, ampm, period_type, id_entry, color) 
				VALUES 
					".implode(',',$values)."
				");
				$values = array();
			}
		}
	}
	

	$index = array();
	$is_free = array();
	while (false !== $arr = $obj->getNextPeriod()) {
		foreach($arr as $p) {
			$group = $p->split(12*3600);
			foreach($group as $p) {
				$key = date('Ymda',$p->ts_begin);
				if (bab_vac_is_free($p)) {
					$is_free[$key] = 1;
				}

				if (!isset($index[$key]) || bab_vac_compare($index[$key]->type, $p->type)) {
					$index[$key] = $p;
				}
			}
		}
	}

	
	$previous = NULL;

	foreach($index as $key => $p) {

		$ampm		= 'pm' === date('a',$p->ts_begin) ? 1 : 0;
		$data		= $p->getData();
		$id_entry	= 0;
		$color		= '';
		$type		= $p->type;

		if (isset($is_free[$key])) {
			if (BAB_PERIOD_VACATION === $p->type) { 
				$id_entry = $data['id']; 
				$arr = bab_vac_typeColorStack($id_entry);
				$color = $arr['color'];
			}
		} elseif (BAB_PERIOD_VACATION === $p->type) {
			$type = BAB_PERIOD_NONWORKING;
		} 


		$key = $id_user.$month.$year.$id_entry.$color.$type;

		if ($key !== $previous) {

			$previous = $key;
			bab_vac_group_insert("(
				".$db->quote($id_user).",
				".$db->quote($month.$year).",
				".$db->quote(date('Y-m-d',$p->ts_begin)).",
				".$db->quote($ampm).",
				".$db->quote($type).",
				".$db->quote($id_entry).",
				".$db->quote($color)."
				)");

		}
	}

	bab_vac_group_insert('',true);
}



/**
 * Date printout for periods
 * @param int $timestamp
 * @return string
 */
function bab_vac_longDate($timestamp) {
	if (empty($timestamp)) {
		return '';
	}
	$add = 'am' == date('a', $timestamp) ? bab_translate('Morning') : bab_translate('Afternoon');
	return bab_longDate($timestamp, false).' '.$add;
}

function bab_vac_shortDate($timestamp) {
	if (empty($timestamp)) {
		return '';
	}
	$add = 'am' == date('a', $timestamp) ? bab_translate('Morning') : bab_translate('Afternoon');
	return bab_shortDate($timestamp, false).' '.$add;
}


/**
 * Delete vacation request
 * notify user if vacation not elapsed
 * delete approbation instance
 * Update calendar
 * @param int $id_request
 */
function bab_vac_delete_request($id_request)
{
	notifyOnRequestChange($id_request, true);

	$db = &$GLOBALS['babDB'];
	

	$arr = $db->db_fetch_assoc($db->db_query("
		SELECT idfai, id_user, date_begin, date_end  
			FROM ".BAB_VAC_ENTRIES_TBL." 
			WHERE id=".$db->quote($id_request)));


	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

	$date_begin = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
	$date_end	= BAB_DateTime::fromIsoDateTime($arr['date_end']);

	while ($date_begin->getTimeStamp() <= $date_end->getTimeStamp()) {
		$month	= $date_begin->getMonth();
		$year	= $date_begin->getYear();
		bab_vac_updateCalendar($arr['id_user'], $year, $month);
		$date_begin->add(1, BAB_DATETIME_MONTH);
	}
	
	
	if ($arr['idfai'] > 0)
		deleteFlowInstance($arr['idfai']);

	$db->db_query("DELETE FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry=".$db->quote($id_request)."");
	$db->db_query("DELETE FROM ".BAB_VAC_ENTRIES_TBL." WHERE id=".$db->quote($id_request));
}




/**
 * Visualisation popup
 */
class bab_vacationRequestDetail
	{
	var $datebegintxt;
	var $datebegin;
	var $halfnamebegin;
	var $dateendtxt;
	var $dateend;
	var $halfnameend;
	var $nbdaystxt;
	var $typename;
	var $nbdays;
	var $totaltxt;
	var $totalval;
	var $confirm;
	var $refuse;
	var $fullname;
	var $commenttxt;
	var $comment;
	var $remarktxt;
	var $remark;
			
	var $arr = array();
	var $db;
	var $count;
	var $res;
	var $veid;
	var $wusers = array();

	var $statustxt;
	var $status;


	function bab_vacationRequestDetail($id)
		{
		$this->datebegintxt = bab_translate("Begin date");
		$this->dateendtxt = bab_translate("End date");
		$this->nbdaystxt = bab_translate("Quantities");
		$this->totaltxt = bab_translate("Total");
		$this->statustxt = bab_translate("Status");
		$this->commenttxt = bab_translate("Description");
		$this->remarktxt = bab_translate("Comment");
		$this->t_approb = bab_translate("Approver");
		$this->db = $GLOBALS['babDB'];
		$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
		$this->datebegin = bab_vac_longDate(bab_mktime($row['date_begin']));
		$this->dateend = bab_vac_longDate(bab_mktime($row['date_end']));
		$this->fullname = bab_getUserName($row['id_user']);
		$this->statarr = array(bab_translate("Waiting to be valiadte by"), bab_translate("Accepted"), bab_translate("Refused"));
		$this->comment = nl2br($row['comment']);
		$this->remark = nl2br($row['comment2']);
		switch($row['status'])
			{
			case 'Y':
				$this->status = $this->statarr[1];
				break;
			case 'N':
				$this->status = $this->statarr[2];
				break;
			default:
				$this->status = $this->statarr[0];
				$this->wusers = getWaitingApproversFlowInstance($row['idfai'] , false);
				break;
			}
		
		$this->approb = bab_getUserName($row['id_approver']);

		$req = "select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry=".$this->db->quote($id);
		$this->res = $this->db->db_query($req);
		$this->count = $this->db->db_num_rows($this->res);
		$this->totalval = 0;
		$this->veid = $id;
		}

	function getnexttype()
		{
		static $i = 0;
		if( $i < $this->count)
			{
			$arr = $this->db->db_fetch_array($this->res);
			list($this->typename) = $this->db->db_fetch_row($this->db->db_query("select description from ".BAB_VAC_RIGHTS_TBL." where id ='".$arr['id_right']."'"));
			$this->nbdays = $arr['quantity'];
			$this->totalval += $this->nbdays;
			$i++;
			return true;
			}
		else
			return false;

		}

	function getnextuser()
		{
		static $i = 0;
		if( $i < count($this->wusers))
			{
			$this->fullname = bab_getUserName($this->wusers[$i]);
			$i++;
			return true;
			}
		else
			return false;

		}
	}




function bab_viewVacationRequestDetail($id) {
	global $babBody;
	$temp = new bab_vacationRequestDetail($id);
	$babBody->babPopup(bab_printTemplate($temp, "vacuser.html", "ventrydetail"));
	return $temp->count;
}



function bab_addCoManagerEntities(&$entities, $id_user) {
	$db = &$GLOBALS['babDB'];
	$res = $db->db_query("SELECT id_entity FROM ".BAB_VAC_COMANAGER_TBL." WHERE id_user=".$db->quote($id_user));

	if (0 == $db->db_num_rows($res)) {
		return;
	}

	if (!isset($entities['superior'])) {
		$entities['superior'] = array();
	}

	if (!function_exists('is_superior')) {
		function is_superior($entities, $ide) {
			foreach($entities['superior'] as $e) {
				if ($ide == $e['id']) {
					return true;
				}
			}
			return false;
		}
	}

	while ($arr = $db->db_fetch_assoc($res)) {
		$e = bab_OCGetEntity($arr['id_entity']);
		$e['id'] = $arr['id_entity'];
		$e['comanager'] = 1;
		if (!is_superior($entities, $arr['id_entity'])) {
			$entities['superior'][] = $e;
		}
	}
}


/**
 * Number of free days between two dates
 * @param	int	$id_user	
 * @param	int	$begin		timestamp
 * @param	int	$end		timestamp
 * @return	int
 */
function bab_vac_getFreeDaysBetween($id_user, $begin, $end) {
	$calcul = round(($end - $begin)/86400, 1);

	include_once $GLOBALS['babInstallPath']."utilit/nwdaysincl.php";
	$beginY = date('Y',$begin);
	$endY = date('Y',$end);

	if ($beginY != $endY) {
		$nonWorkingDays = array_merge(bab_getNonWorkingDays($beginY), bab_getNonWorkingDays($endY));
		}
	else {
		$nonWorkingDays = bab_getNonWorkingDays($beginY);
		}

	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
	
	for ($i = $begin; $i <= $end ; $i = mktime(0, 0, 0, date("m",$i) , date("d",$i) + 1, date("Y",$i)) )
		{
		$arr = bab_getWHours($id_user, date('w',$i ));
		$am = false;
		$pm = false;
		foreach($arr as $wh_period) {
			if ($wh_period['startHour'] < '12:00:00') {
				$am = true;
			}

			if ($wh_period['endHour'] > '12:00:00') {
				$pm = true;
			}
		}

		if (!$am) {
			$calcul -= 0.5;
		}

		if (!$pm) {
			$calcul -= 0.5;
		}

		if (isset($nonWorkingDays[date('Y-m-d',$i )])) {
			$calcul--;
		}
	}

	return $calcul;
}

?>