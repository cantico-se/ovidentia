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
* @internal SEC1 PR 27/02/2007 FULL
*/

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
		$this->username = bab_toHtml(bab_getUserName($row['id_user']));
		$this->begindate = bab_longDate(bab_mktime($row['date_begin']));
		$this->enddate = bab_longDate(bab_mktime($row['date_end']));
		list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$babDB->db_escape_string($row['id'])."'"));
		$this->comment = bab_toHtml($row['comment']);
		}
	}

function notifyVacationApprovers($id, $users, $modify = false)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail;

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'"));

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

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'"));

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
			$this->username = bab_toHtml(bab_getUserName($row['id_user']));
			$this->begindate = bab_longDate(bab_mktime($row['date_begin']));
			$this->enddate = bab_longDate(bab_mktime($row['date_end']));
			list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry =".$babDB->quote($row['id'])));
			$this->comment = bab_toHtml($row['comment']);
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

	$message = bab_printTemplate($tempb,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);
	
	$mail->send();
	
	if ($mail->ErrorInfo())
		{
		trigger_error($mail->ErrorInfo());
		}
	}
	
	
	
	
	
/**
 *  
 *
 * @param	int		$id_right
 * @param	int		$beginp			begin date of request		timestamp
 * @param	int		$endp			end date of request			timestamp
 * @param	boolean	$overlap		TRUE if overlap is allowed
 *
 * @return 	boolean
 */	
function bab_vac_isRightAccessibleOnPeriod($id_right, $beginp, $endp, $overlap) {
	
	global $babDB;
	
	$res = $babDB->db_query('SELECT * FROM '.BAB_VAC_RIGHTS_INPERIOD_TBL.' WHERE id_right='.$babDB->quote($id_right));
	
	if( !$res || $babDB->db_num_rows($res) == 0 )
	 {
		return true;
	 }
	
	
	$access_include = 0;
	$access_exclude = 1;
	
	
	while ($arr = $babDB->db_fetch_assoc($res)) {
		
		$period_start 	= bab_mktime($arr['period_start']);
		$period_end 	= 86400 + bab_mktime($arr['period_end']);
		
		
		$period_access = ((int) $arr['right_inperiod']) + (((int) $overlap)*10);
		
		switch ($period_access)
			{
			case 0: // Toujours
			case 10:
				
				break;
			
			case 1: // Dans la p�riode de la r�gle
				if ($period_start <= $beginp && $period_end >= $endp) {
						$access_include |= 1;
						$debug_result = 'TRUE';
					} else {
						$access_include |= 0;
						$debug_result = 'FALSE';
					}
					
					bab_debug(
							"Disponibilite en fonction de la periode de cones demandee\n".
							"Dans l'intervale\n".
							'id = '.$id_right."\n".
							bab_shortDate($period_start).' <= '.bab_shortDate($beginp). 
							' && '.bab_shortDate($period_end).' >= '.bab_shortDate($endp)."\n".
							' => '.$debug_result
							);
					
					
				break;
			
			case 2: // En dehors de la p�riode de la r�gle
				if ($period_end <= $beginp || $period_start >= $endp) {
						$access_exclude &= 1;
						$debug_result = 'TRUE';
					} else {
						$access_exclude &= 0;
						$debug_result = 'FALSE';
					}
					
					
					bab_debug(
							"Disponibilite en fonction de la periode de cong�s demandee\n".
							"En dehors de l'intervale\n".
							'id = '.$id_right."\n".
							bab_shortDate($period_end).' <= '.bab_shortDate($beginp). 
							' && '.bab_shortDate($period_start).' >= '.bab_shortDate($endp)."\n".
							' => '.$debug_result
							);
				break;
				
			case 11: // Dans la p�riode de la r�gle mais peut d�passer � l'exterieur
				
				if ($period_start < $endp && $period_end > $beginp ) {
						$access_include |= 1;
						$debug_result = 'TRUE';
					} else {
						$access_include |= 0;
						$debug_result = 'FALSE';
					}
					
					
					bab_debug(
							"Disponibilite en fonction de la periode de conges demandee\n".
							"Dans l'intervale mais peut depasser a l'exterieur\n".
							'id = '.$id_right."\n".
							bab_shortDate($period_start).' < '.bab_shortDate($endp). 
							' && '.bab_shortDate($period_end).' > '.bab_shortDate($beginp)."\n".
							' => '.$debug_result
							);
				break;

			case 12: // En dehors de la p�riode de la r�gle mais peut d�passer � l'int�rieur
				if ($period_start > $beginp || $period_end < $endp) {
						$access_exclude &= 1;
						$debug_result = 'TRUE';
					} else {
						$access_exclude &= 0;
						$debug_result = 'FALSE';
					}
					
					bab_debug(
							"acces sur la periode, en fonction de la periode de la demande\n".
							"En dehors de l'intervale mais peut depasser a l'interieur\n".
							'id = '.$id_right."\n".
							bab_shortDate($period_start).' < '.bab_shortDate($endp). 
							' && '.bab_shortDate($period_end).' > '.bab_shortDate($beginp)."\n".
							' => '.$debug_result
							);
				break;
		}
	}
	
	$debug_include = $access_include ? 'TRUE' : 'FALSE';
	$debug_exclude = $access_exclude ? 'TRUE' : 'FALSE';

	bab_debug(sprintf("id = %d \ntests de periodes d'inclusion %s \ntests de periodes d'exclusion %s\n",$id_right, $debug_include, $debug_exclude));
	
	return $access_include && $access_exclude;
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

	global $babDB;

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
					AND p.id_user=".$babDB->quote($id_user)." 
				";
	
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

	$req .= " AND ur.id_user=".$babDB->quote($id_user)." AND ur.id_right=r.id 	AND r.id_type=t.id 	GROUP BY r.id ORDER BY r.description";
	
	
	
	$res = $babDB->db_query($req);

	
	while ( $arr = $babDB->db_fetch_assoc($res) )
		{

		
		$access = true;

		if( $arr['date_begin_valid'] != '0000-00-00' && (bab_mktime($arr['date_begin_valid']." 00:00:00") > mktime())){
			$access= false;
			}

		if( $arr['date_end_valid'] != '0000-00-00' && (bab_mktime($arr['date_end_valid']." 23:59:59") < mktime())){
			$access= false;
			}

		// dont't display vacations with fixed dates that are gone 
		if( $arr['date_end_fixed'] != '0000-00-00' && (bab_mktime($arr['date_end_fixed']." 23:59:59") < mktime())){
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
		
		$req = "select sum(el.quantity) total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user=".$babDB->quote($id_user)." and e.status='Y' and el.id_right=".$babDB->quote($arr['id'])." and el.id_entry=e.id";
		$row = $babDB->db_fetch_array($babDB->db_query($req));
				
		$qdp = isset($row['total'])? $row['total'] : 0;

		$req = "select sum(el.quantity) total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user=".$babDB->quote($id_user)." and e.status='' and el.id_right=".$babDB->quote($arr['id'])." and el.id_entry=e.id";
		$row = $babDB->db_fetch_array($babDB->db_query($req));

		$waiting = isset($row['total'])? $row['total'] : 0;

		if( $arr['ur_quantity'] != '')
			{
			$quantitydays = $arr['ur_quantity'] - $qdp;
			}
		else
			{
			$quantitydays = $arr['quantity'] - $qdp;
			}	
		
		if ($access && !empty($arr['id_right']) ) {
			// rules 


			

			// acces sur la p�riode, en fonction de la p�riode de la demande
			
			
			
			if ($begin) { 
				$access = bab_vac_isRightAccessibleOnPeriod((int) $arr['id_right'], $beginp, $endp, (bool) $arr['validoverlap']);
			} else {
				$access = true; // le doit est accessible si on ne test pas de demande (premiere page de la demande)
			}


			

			
			// Attribution du droit en fonction des jours demand�s et valid�s
			if ( $access ) {

				

				$p1 = '';
				$p2 = '';
				$req = '';

				if ('0000-00-00' != $arr['trigger_p1_begin'] && '0000-00-00' != $arr['trigger_p1_end']) {
					$p1 = "(e.date_begin < ".$babDB->quote($arr['trigger_p1_end'])." AND e.date_end > ".$babDB->quote($arr['trigger_p1_begin']).')';
				}

				if ('0000-00-00' != $arr['trigger_p2_begin'] && '0000-00-00' != $arr['trigger_p2_end']) {
					$p2 = "(e.date_begin < ".$babDB->quote($arr['trigger_p2_end'])." AND e.date_end > ".$babDB->quote($arr['trigger_p2_begin']).')';
				}

				if ($p1 && $p2) {
					$req = 'AND ('.$p1.' OR '.$p2.')';
				} else if ($p1 || $p2) {
					$req = 'AND '.$p1.$p2;
				}


				if ($req) { // une requete valide a pu �tre cr�e a partir des p�riode

					
				
					if (!empty($arr['trigger_type']))
						{
						$table = ", ".BAB_VAC_RIGHTS_TBL." r ";
						$where = " AND el.id_right=r.id AND r.id_type=".$babDB->quote($arr['trigger_type'])." ";
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
							e.id_user=".$babDB->quote($id_user)." 
							and e.status='Y' 
							and el.id_entry=e.id 
							".$req.$where."
						GROUP BY e.id";

					$nbdays = 0;
					$res_entry = $babDB->db_query($req);
					while ($entry = $babDB->db_fetch_assoc($res_entry)) {

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
					}



					$access = false;
					if ( '' !== $arr['trigger_nbdays_min'] && '' !== $arr['trigger_nbdays_max'] && $arr['trigger_nbdays_min'] <= $nbdays && $nbdays < $arr['trigger_nbdays_max'] ) {

						bab_debug(
							"Attribution du droit en fonction des jours demand�s et valid�s\n".
							"Le droit est accord� si l'utilisateur a pris entre ".$arr['trigger_nbdays_min']." et ".$arr['trigger_nbdays_max']." jours\n".
							$arr['description']."\n".
							"nb de jours pris : ".$nbdays
						);
						$access = true;
					} 
					
				} // endif ($req)	
			} // endif ($access)
		} // endif rule ($arr[id_right])

		

		if ( $access )
			$return[$arr['id']] = array(
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


/**
 * Display a vacation calendar
 * @param	array		$users		array of id_user to display
 * @param	boolean		$period		allow period selection, first step of vacation request
 */
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
		var $db_month;


		function temp($users, $period)
			{
			global $babBody;

			include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";


			$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : Date("n");
			$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : Date("Y");

			global $babDB,$babMonths;
			$this->month = $month;
			$this->year = $year;

			$this->userNameArr = array();
			foreach ($users as $uid)
				{
				$uid = (int) $uid;

				

				$this->userNameArr[$uid] = bab_getUserName($uid);
				}

			bab_sort::natcasesort($this->userNameArr);

			$this->idusers 		= array_keys($this->userNameArr);
			$this->nbusers 		= count($this->idusers);
			$this->firstuser 	= bab_toHtml(current($this->userNameArr));
			
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
			$this->nwd_color = 'FFFFFF';
			
			if( $GLOBALS['babBody']->babsite['id_calendar_cat'] != 0)
			{
				include_once $GLOBALS['babInstallPath']."utilit/calapi.php";
				$idcat = bab_calGetCategories($GLOBALS['babBody']->babsite['id_calendar_cat']);
				if( isset($idcat[0]['color']))
				{
					$this->nwd_color = $idcat[0]['color'];
				}
			}
			


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
			
			if (1 == bab_rp('rfrom', 0)) {
				$urltmp .= '&amp;rfrom='.bab_rp('rfrom');
			}


			$this->previousmonth	= $urltmp."&amp;month=".date("n", mktime( 0,0,0, $month-1, 1, $year));
			$this->previousmonth	.= "&amp;year=".date("Y", mktime( 0,0,0, $month-1, 1, $year));
			$this->nextmonth		= $urltmp."&amp;month=". date("n", mktime( 0,0,0, $month+1, 1, $year));
			$this->nextmonth		.= "&amp;year=". date("Y", mktime( 0,0,0, $month+1, 1, $year));

			$this->previousyear		= $urltmp."&amp;month=".date("n", mktime( 0,0,0, $month, 1, $year-1));
			$this->previousyear		.= "&amp;year=".date("Y", mktime( 0,0,0, $month, 1, $year-1));
			$this->nextyear			= $urltmp."&amp;month=". date("n", mktime( 0,0,0, $month, 1, $year+1));
			$this->nextyear			.= "&amp;year=". date("Y", mktime( 0,0,0, $month, 1, $year+1));

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

			$res = $babDB->db_query("
				SELECT 
					id_user, 
					monthkey 
				FROM ".BAB_VAC_CALENDAR_TBL." 
				WHERE 
					id_user IN(".$babDB->quote($this->idusers).") 
					AND cal_date BETWEEN ".$babDB->quote($dateb->getIsoDate())." 
					AND ".$babDB->quote($datee->getIsoDate())."  
				GROUP BY id_user, monthkey 
			");

			while($arr = $babDB->db_fetch_assoc($res)) {
				$this->db_month[$arr['monthkey']][$arr['id_user']] = 1;
			}

			


			$this->restypes = $babDB->db_query("
			
					SELECT 
						t.* 
					FROM 
						".BAB_VAC_TYPES_TBL." t, 
						".BAB_VAC_COLL_TYPES_TBL." ct, 
						".BAB_VAC_PERSONNEL_TBL." p 
					WHERE 
						p.id_user IN(".$babDB->quote($this->idusers).") 
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
				$this->username = bab_toHtml($this->userNameArr[$this->id_user]);
					

				$key = $this->curmonth.$this->curyear;

				if (!isset($this->db_month[$key][$this->id_user])) {
					bab_vac_updateCalendar($this->id_user, $this->curyear, $this->curmonth);
				}

				global $babDB;
				
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
							ON d.nw_day = c.cal_date AND id_site=".$babDB->quote($GLOBALS['babBody']->babsite['id'])."
					WHERE monthkey=".$babDB->quote($key)." AND c.id_user=".$babDB->quote($this->id_user)." 
						ORDER BY cal_date 
				";
				$res = $babDB->db_query($req);

				$this->periodIndex = array();
				while ($arr = $babDB->db_fetch_assoc($res)) {
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
				$this->monthname = bab_toHtml($GLOBALS['babShortMonths'][$this->curmonth]);
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

					$this->am_clickable = false;
					$this->pm_clickable = false;

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


					// am

					if (BAB_PERIOD_NWDAY == $period_am['period_type']) {
						$this->day_classname = 'nonworking';
						$this->halfday = false;
						$this->tdtext = bab_translate($period_am['nw_type']);
						$this->am_color = $this->nwd_color;
						$d++;
						return true;

					} elseif (BAB_PERIOD_WORKING == $period_am['period_type']) {
						$this->am_classname = $this->period ? 'free' : 'default';
						$this->am_clickable = true;

					} elseif (BAB_PERIOD_VACATION == $period_am['period_type']) {

						if ($period_am['id_entry'] == $this->id_request) {
							$this->am_clickable = true;
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
						$this->pm_clickable = true;

					} elseif (BAB_PERIOD_VACATION == $period_pm['period_type']) {
						
						if ($period_pm['id_entry'] == $this->id_request) {
							$this->pm_clickable = true;
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
			global $babDB;
			if ($this->arr = $babDB->db_fetch_array($this->restypes))
				{
				$this->arr['name'] 			= bab_toHtml($this->arr['name']);
				$this->arr['description'] 	= bab_toHtml($this->arr['description']);
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

	$temp = new temp($users, $period);
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
		var $babDB;
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
			global $babDB;
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

			if (!is_array($id_user))
				$uarr = array($id_user);
			else
				$uarr = $id_user;

			$this->calurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$id_user."&popup=1");
			$this->personal = $id_user == $GLOBALS['BAB_SESS_USERID'];
			$this->pos = isset($_REQUEST['pos']) ? $_REQUEST['pos'] : 0;

			$req = "".BAB_VAC_ENTRIES_TBL." where id_user IN(".$babDB->quote($uarr).")";

			list($total) = $babDB->db_fetch_row($babDB->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{
				$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : '';
				$ide = isset($_REQUEST['ide']) ? $_REQUEST['ide'] : '';
				$id_user = isset($_REQUEST['id_user']) ? $_REQUEST['id_user'] : '';
				$tmpurl = $GLOBALS['babUrlScript']."?tg=".urlencode($_REQUEST['tg'])."&idx=".urlencode($idx)."&ide=".$ide."&id_user=".$id_user."&pos=";

				
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
				$req .= " limit ".$babDB->db_escape_string($this->pos).",".VAC_MAX_REQUESTS_LIST;
				}
			$this->res = $babDB->db_query("select * from ".$req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->statarr = array(bab_translate("Waiting"), bab_translate("Accepted"), bab_translate("Refused"));
			}

		function getnext()
			{

			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=vacuser&idx=morve&id=".$arr['id']);
				list($this->quantity) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$babDB->db_escape_string($arr['id'])."'"));
				$this->urlname = bab_toHtml(bab_getUserName($arr['id_user']));

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
		var $babDB;
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
			global $babDB;
			$this->iduser = $id;
			$this->updatetxt = bab_translate("Update");
			$this->desctxt = bab_translate("Description");
			$this->typetxt = bab_translate("Type");
			$this->consumedtxt = bab_translate("Consumed");
			$this->datebtxt = bab_translate("Begin date");
			$this->dateetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->datetxt = bab_translate("Entry date");
			$this->invalidentry = bab_toHtml(bab_translate("Invalid entry!  Only numbers are accepted or . !"), BAB_HTML_JS);
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$GLOBALS['babBody']->title = bab_translate("Vacation rights of:").' '.bab_getUserName($id);
			
			$infos = bab_getUserInfos($id);
			$this->currentUserLastname = $infos['sn'];
			$this->currentUserFirstname = $infos['givenname'];

			$this->tg = $_REQUEST['tg'];


			$this->res = $babDB->db_query("
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
				WHERE id_user=".$babDB->quote($id)." 
					AND r.id = u.id_right 
					AND t.id = r.id_type 
					ORDER BY year DESC, label ASC
			");

			$this->count = $babDB->db_num_rows($this->res);
			list($this->idcoll) = $babDB->db_fetch_row($babDB->db_query("select id_coll from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($id)."'"));
			
			}

		function getnextright()
			{
			static $y = '';
			static $label = '';
			static $i = 0;
			if( $i < $this->count)
				{
				global $babDB;
				$arr = $babDB->db_fetch_array($this->res);
				$res = $babDB->db_query("SELECT id from ".BAB_VAC_COLL_TYPES_TBL." WHERE id_coll='".$babDB->db_escape_string($this->idcoll)."' and id_type='".$babDB->db_escape_string($arr['id_type'])."'");
				$this->bview = false;
				if( $res && $babDB->db_num_rows($res) > 0 )
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
					$arr = $babDB->db_fetch_array($babDB->db_query(
						"SELECT sum(quantity) as total 
						FROM 
							".BAB_VAC_ENTRIES_ELEM_TBL." ee, 
							".BAB_VAC_ENTRIES_TBL." e
						WHERE 
								e.id_user = ".$babDB->quote($this->iduser)." 
							AND e.status = 'Y' 
							AND ee.id_right = ".$babDB->quote($this->idright)." 
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
			
			
			/**
			 * get previous or next user in manager list
			 * @param	string	$sign
			 * @param	string	$sqlOrderType
			 * @return 	int|false
			 */
			function getUserInManagerList($sign, $sqlOrderType) {
			

				global $babDB;

				
				$ide = bab_rp('ide');
				
				// delegation administration list
				if ($ide && bab_isAccessibleEntityAsSuperior($ide)) {
				
					$users = bab_OCGetCollaborators($ide);
					$superior = bab_OCGetSuperior($ide);
					
					if (((int)$superior['id_user']) !== (int)$GLOBALS['BAB_SESS_USERID'] && false === bab_isAccessibleEntityAsCoManager($ide)) {
						$users[] = $superior;
					}

					
					$ordering = array();
					foreach($users as $key => $user) {
						$ordering[$key] = $user['lastname'].' '.$user['firstname'];
					}
					
					bab_sort::natcasesort($ordering);
					
					
					$previous = false;
					$next = false;
					
					$next_prev_index = array();
					
					foreach($ordering as $key => $dummy) {
					
						if (false !== $previous) {
							$next_prev_index[$previous]['next'] = $users[$key]['id_user'];
						}
					
						$next_prev_index[$users[$key]['id_user']] = array(
							'previous' => $previous,
							'next' => $next
						);
						
						$previous = $users[$key]['id_user'];
					}
					
					reset($ordering);
					$firstuser = $users[key($ordering)]['id_user'];
					next($ordering);
					$seconduser = $users[key($ordering)]['id_user'];
					
					$next_prev_index[$firstuser]['next'] = $seconduser;

					
					
					switch($sign) {
						case '<':
							return $next_prev_index[$this->iduser]['previous'];
							
						case '>':
							return $next_prev_index[$this->iduser]['next'];
					}
					

					return false;
				}
				
				
				
				
				
				
				// manager list
				
				$acclevel = bab_vacationsAccess();
				if( true === $acclevel['manager'])
					{
				
					$res = $babDB->db_query('
						SELECT 
							p.id_user  
						FROM 
							'.BAB_VAC_PERSONNEL_TBL.' p, '.BAB_USERS_TBL.' u 
							
						WHERE 
							u.id = p.id_user 
							AND (u.lastname '.$sign.' '.$babDB->quote($this->currentUserLastname).' 
									OR (u.lastname = '.$babDB->quote($this->currentUserLastname).' 
										AND u.firstname '.$sign.' '.$babDB->quote($this->currentUserFirstname).')
								)
							
						ORDER BY u.lastname '.$sqlOrderType.', u.firstname '.$sqlOrderType.' 
						
						LIMIT 0,2 
					');
					
					
					
					if ($arr = $babDB->db_fetch_assoc($res)) {
						return (int) $arr['id_user'];
					}
					
				}
				
				
				
				
				
				return false;
			}
			
			
			
			
			function previoususer() {
				
				static $i = 0;
				
				if (0 === $i) {
				
					$id_user = $this->getUserInManagerList('<','DESC');
					if (!$id_user) {
						return false;
					}
					
					$this->previous = bab_toHtml(bab_getUserName($id_user));
					
					require_once $GLOBALS['babInstallPath'] . 'utilit/urlincl.php';
					$url = bab_url::request_gp();
					
					if (bab_rp('idu')) {
						$url = bab_url::mod($url, 'idu', $id_user);
					}
					
					if (bab_rp('id_user')) {
						$url = bab_url::mod($url, 'id_user', $id_user);
					}
					
					$this->previousurl = bab_toHtml($url);
					
				
					$i++;
					return true;
				}
				
				return false;
			}
			
			function nextuser() {

				static $i = 0;
				
				if (0 === $i) {
				
					$id_user = $this->getUserInManagerList('>','ASC');
					if (!$id_user) {
						return false;
					}
					
					$this->next = bab_toHtml(bab_getUserName($id_user));
					
					require_once $GLOBALS['babInstallPath'] . 'utilit/urlincl.php';
					$url = bab_url::request_gp();
					if (bab_rp('idu')) {
						$url = bab_url::mod($url, 'idu', $id_user);
					}
					
					if (bab_rp('id_user')) {
						$url = bab_url::mod($url, 'id_user', $id_user);
					}
					$this->nexturl = bab_toHtml($url);
					
				
					$i++;
					return true;
				}

				return false;
			}
			
		}

	$temp = new temp($id);
	$GLOBALS['babBody']->babPopup(bab_printTemplate($temp, "vacadm.html", "rlistbyuser"));
	}



/**
 * @param	int		$userid
 * @param	array	$quantities
 * @param	array	$idrights
 *
 * @return 	boolean
 */
function updateVacationRightByUser($userid, $quantities, $idrights)
{
	global $babDB;
	

	for($i = 0; $i < count($idrights); $i++)
		{
		
		list($quantity) = $babDB->db_fetch_array($babDB->db_query("select quantity from ".BAB_VAC_RIGHTS_TBL." where id='".$babDB->db_escape_string($idrights[$i])."'"));
		if( $quantity != $quantities[$i] )
			$quant = $quantities[$i];
		else
			$quant = '';

		$babDB->db_query("
			UPDATE 
				".BAB_VAC_USERS_RIGHTS_TBL." 
					SET quantity='".$babDB->db_escape_string($quant)."' 
			WHERE 
				id_user='".$babDB->db_escape_string($userid)."' 
				AND id_right='".$babDB->db_escape_string($idrights[$i])."'
		");
	}
	
	return true;
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
	global $babBody;
	$babBody->babPopup(bab_printTemplate($temp,"vacadm.html", "rlistbyuserunload"));
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
		var $babDB;
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

			global $babDB;

			$this->idp = $idp;

			list($n) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_VAC_ENTRIES_TBL." WHERE id_user='".$babDB->db_escape_string($this->idp)."' AND status=''"));

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
				$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($this->idp)."'"));
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

			$this->sares = $babDB->db_query("select * from ".BAB_FLOW_APPROVERS_TBL." order by name asc");
			if( !$this->sares )
				$this->countsa = 0;
			else
				$this->countsa = $babDB->db_num_rows($this->sares);

			$this->colres = $babDB->db_query("select * from ".BAB_VAC_COLLECTIONS_TBL." order by name asc");
			$this->countcol = $babDB->db_num_rows($this->colres);
			}
		
		function getnextsa()
			{
			global $babDB;
			static $j= 0;
			if( $j < $this->countsa )
				{
				
				$arr = $babDB->db_fetch_array($this->sares);
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
					$babDB->db_data_seek($this->sares,0);
				return false;
				}
			}

		function getnextcol()
			{
			static $j= 0;
			if( $j < $this->countcol )
				{
				global $babDB;
				$arr = $babDB->db_fetch_array($this->colres);
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





/**
 * 
 * @param	int		$ide
 * @return	boolean
 */
function bab_isAccessibleEntityAsSuperior($ide) {

	$ide = (int) $ide;

	$userentities = & bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
	bab_addCoManagerEntities($userentities, $GLOBALS['BAB_SESS_USERID']);
	$userentities['superior'];
	
	foreach($userentities['superior'] as $arr) {
		if ($ide === (int) $arr['id']) {
			return true;
		}
	}
	
	return false;
}


/**
 * 
 * @param	int		$ide
 * @return	boolean
 */
function bab_isAccessibleEntityAsCoManager($ide) {

	global $babDB;

	list($n) = $babDB->db_fetch_array($babDB->db_query('
		SELECT COUNT(*) FROM '.BAB_VAC_COMANAGER_TBL.' 
		WHERE 
			id_user='.$babDB->quote($GLOBALS['BAB_SESS_USERID']).' 
			AND id_entity='.$babDB->quote($ide).'
	'));
	
	if ($n > 0) {
		return true;
	}
	
	return false;
}



function updateVacationUser($userid, $idsa)
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id_user=".$babDB->quote($userid)." and status=''");
	while( $row = $babDB->db_fetch_array($res)) {
		if( $row['idfai'] != 0 ) {
			deleteFlowInstance($row['idfai']);
		}
		$idfai = makeFlowInstance($idsa, "vac-".$row['id']);
		setFlowInstanceOwner($idfai, $row['id_user']);
		$babDB->db_query("UPDATE ".BAB_VAC_ENTRIES_TBL." SET idfai=".$babDB->quote($idfai)." where id=".$babDB->quote($row['id'])."");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyVacationApprovers($row['id'], $nfusers);
	}
}

function updateUserColl()
{
	global $babDB;

	if (empty($_POST['idp']))
		{
		return false;
		}

	$users_rights = array();
	$worked_ids = array();

	$res = $babDB->db_query("SELECT id,id_right FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id_user='".$babDB->db_escape_string($_POST['idp'])."'");
	while($arr = $babDB->db_fetch_array($res))
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
		if (mb_substr($field,0,mb_strlen($prefix)) == $prefix)
			{
			list(,$id_right) = explode('_',$field);
			if (isset($used[$id_right]))
				{
				$value += $used[$id_right];
				}

			$post_rights[$id_right] = $value;

			if ($value < 0)
				{
				list($name,$cbalance) = $babDB->db_fetch_array($babDB->db_query("SELECT description,cbalance FROM ".BAB_VAC_RIGHTS_TBL." WHERE id='".$babDB->db_escape_string($id_right)."'"));
				
				if ($cbalance == 'N')
					{
					$GLOBALS['babBody']->addError(bab_translate("Negative balance are not alowed on right").' '.$name);
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
			$babDB->db_query("UPDATE ".BAB_VAC_USERS_RIGHTS_TBL." SET quantity='".$babDB->db_escape_string($value)."' WHERE id='".$babDB->db_escape_string($users_rights[$id_right])."'");
			$worked_ids[] = $users_rights[$id_right];
			}
		else
			{
			$babDB->db_query("INSERT INTO ".BAB_VAC_USERS_RIGHTS_TBL." (id_user,id_right,quantity) VALUES ('".$babDB->db_escape_string($_POST['idp'])."', '".$babDB->db_escape_string($id_right)."', '".$babDB->db_escape_string($value)."')");
			$worked_ids[] = $babDB->db_insert_id();
			}
		}

	if (count($worked_ids) > 0)
		{
		$babDB->db_query("DELETE FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id NOT IN(".$babDB->quote($worked_ids).") AND id_user= '".$babDB->db_escape_string($_POST['idp'])."'");
		}

	$babDB->db_query("UPDATE ".BAB_VAC_PERSONNEL_TBL." SET id_coll='".$babDB->db_escape_string($_POST['idcol'])."' WHERE id_user='".$babDB->db_escape_string($_POST['idp'])."'");

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

			global $babDB;
			$this->tg = $_REQUEST['tg'];


			// Les droits de l'ancien r�gime sont les m�me que ceux afficher sur la premi�re page d'une demande de cong� pour l'utilisateur.

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
						p.id_user='".$babDB->db_escape_string($id_user)."' AND c.id='".$babDB->db_escape_string($newcol)."'
					";

			$arr = $babDB->db_fetch_array($babDB->db_query($req));


			$this->oldcol = $arr['old'];
			$this->newcol = $arr['new'];

			$req = "SELECT r.* FROM ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_COLL_TYPES_TBL." t WHERE t.id_type = r.id_type AND t.id_coll='".$babDB->db_escape_string($newcol)."' ORDER BY r.description";
			$res = $babDB->db_query($req);
			
			$new_rights = array();
			while ($arr = $babDB->db_fetch_array($res))
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
		$res = $babDB->db_query("select id, id_sa,id_user from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($iduser)."'");

		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);

			$babDB->db_query("UPDATE ".BAB_VAC_PERSONNEL_TBL." SET id_sa='".$babDB->db_escape_string($idsa)."' where id='".$babDB->db_escape_string($arr['id'])."'");

			if( $arr['id_sa'] != $idsa )
				{
				updateVacationUser($arr['id_user'], $idsa);
				}

			}
		else
			{
			$babDB->db_query("INSERT INTO ".BAB_VAC_PERSONNEL_TBL." ( id_user,id_sa ) VALUES ('".$babDB->db_escape_string($iduser)."', '".$babDB->db_escape_string($idsa)."' )");
			}
		}

	
	return true;
	}

function saveVacationPersonnel($userid,  $idcol, $idsa)
	{
	include_once $GLOBALS['babInstallPath']."utilit/vacfixedincl.php";
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


	$res = $babDB->db_query("select id from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$babDB->db_escape_string($userid)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$babBody->msgerror = bab_translate("This user already exist in personnel list") ." !";
		return false;
		}

	$babDB->db_query("insert into ".BAB_VAC_PERSONNEL_TBL." ( id_user, id_coll, id_sa) values ('".$babDB->db_escape_string($userid)."','".$babDB->db_escape_string($idcol)."','".$babDB->db_escape_string($idsa)."')");

	// create default user rights

	$babDB->db_query("DELETE FROM ".BAB_VAC_USERS_RIGHTS_TBL." WHERE id_user='".$babDB->db_escape_string($userid)."'");

	$res = $babDB->db_query("SELECT r.id FROM ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_COLL_TYPES_TBL." t WHERE r.id_type=t.id_type AND t.id_coll='".$babDB->db_escape_string($idcol)."'");

	while($r = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("INSERT INTO ".BAB_VAC_USERS_RIGHTS_TBL." ( id_user,  id_right ) VALUES ('".$babDB->db_escape_string($userid)."','".$babDB->db_escape_string($r['id'])."')");
		}


	// update fixed vacation right
	bab_vac_updateFixedRightsOnUser($userid);

	
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
	global $babDB;

	static $arr = null;

	if (null == $arr) {
		$res = $babDB->db_query("SELECT * FROM ".BAB_VAC_OPTIONS_TBL);
		if (0 < $babDB->db_num_rows($res)) {
			$arr = $babDB->db_fetch_assoc($res);
		} else {
			$arr = array(
				'chart_superiors_create_request' => 0	
			);
		}
	}

	return $arr[$field];
}


/**
 * Push and shift into a stack
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
		return array_shift($stack[$id_entry]);
	}

	array_push($stack[$id_entry], $push);
}




/**
 * Set vacation events into object  
 * 
 * @todo process queries with null dates
 * 
 * @param bab_VacationPeriodCollection	$period_collection		collection of events
 * @param bab_UserPeriods				$user_periods			query result set
 * @param array							$id_users
 * @param BAB_DateTime					$begin
 * @param BAB_DateTime					$end
 * 
 * 
 */
function bab_vac_setVacationPeriods(bab_UserPeriods $user_periods, $id_users) {
	global $babDB;
	
	require_once dirname(__FILE__).'/nwdaysincl.php';
	require_once dirname(__FILE__).'/dateTime.php';
	
	$begin = $user_periods->begin;
	$end = $user_periods->end;
	
	
	$backend = bab_functionality::get('CalendarBackend/Ovi');
	
	$query = "
	SELECT * from ".BAB_VAC_ENTRIES_TBL." 
		WHERE 
		id_user IN(".$babDB->quote($id_users).")   
		AND status!='N' 
	";
	
	if (null !== $begin)
	{
		$query .= " AND date_end > ".$babDB->quote($begin->getIsoDateTime())." ";
	}
	
	if (null !== $end)
	{
		$query .= " AND date_begin < ".$babDB->quote($end->getIsoDateTime())." ";
	}

	
	$res = $babDB->db_query($query);
	
	// find begin and end
	
	$date_end = null;
	$date_begin = null;
	
	while ($row = $babDB->db_fetch_assoc($res))
	{
		if (null === $date_end || $row['date_end'] > $date_end)
		{
			$date_end = $row['date_end'];
		}
		
		if (null === $date_begin || $row['date_begin'] < $date_begin)
		{
			$date_begin = $row['date_begin'];
		}
	}
	
	if ($babDB->db_num_rows($res))
	{
		$babDB->db_data_seek($res, 0);
	}
	
	
	$begin 	= BAB_DateTime::fromIsoDateTime($date_begin);
	$end	= BAB_DateTime::fromIsoDateTime($date_end);
	
	
	
	$nwdays = bab_getNonWorkingDaysBetween($begin->getIsoDate(), $end->getIsoDate());
	$collections = array();

	while( $row = $babDB->db_fetch_assoc($res)) 
	{

		if (!isset($collections[$row['id_user']]))
		{
			$id_user = (int) $row['id_user'];
			$calendar = $backend->Personalcalendar($id_user);
			
			if ($calendar)
			{
				$collections[$row['id_user']] = $backend->VacationPeriodCollection($calendar);
			} else {
				$collections[$row['id_user']] = null;
			}

		}


		if (isset($collections[$row['id_user']]))
		{
			$p = new bab_calendarPeriod;
			bab_vac_setPeriodProperties($p, $row, $begin);
			$collections[$row['id_user']]->addPeriod($p);
			$p->setProperty('UID', 'VAC'.$row['id']);
			$user_periods->addPeriod($p);
		}
		
	}
}





/**
 * Search for a vacation request in ovidentia database and update the corresponding calendar period if the period is found using the user calendar backend
 * @param int			$id_request
 * @param BAB_DateTime 	$begin			old begin date
 * @param BAB_DateTime	$end			old end date
 * @return unknown_type
 */
function bab_vac_updatePeriod($id_request, BAB_DateTime $begin, BAB_DateTime $end)
{
	global $babDB;
	
	$res = $babDB->db_query('SELECT * FROM '.BAB_VAC_ENTRIES_TBL.' WHERE id='.$babDB->quote($id_request));
	$row = $babDB->db_fetch_assoc($res);
	
	$period = bab_vac_getPeriod($id_request, $row['id_user'], $begin, $end);
	if (null === $period)
	{
		bab_debug('no period found in backend');
		return null;
	}
	
	bab_vac_setPeriodProperties($period, $row, $begin);
	
	$period->save();
}


/**
 * Create a new vacation request into calendar backend
 * @param $id_request
 * @return unknown_type
 */
function bab_vac_createPeriod($id_request)
{
	global $babDB;
	require_once dirname(__FILE__).'/calincl.php';
	require_once dirname(__FILE__).'/dateTime.php';
	
	$res = $babDB->db_query('SELECT * FROM '.BAB_VAC_ENTRIES_TBL.' WHERE id='.$babDB->quote($id_request));
	$row = $babDB->db_fetch_assoc($res);
	
		
	$icalendars = new bab_icalendars($row['id_user']);
	
	$calendar = $icalendars->getPersonalCalendar();
	
	if (!$calendar)
	{
		// do not create the vacation period if no personal calendar
		return;
	}
	
	$backend = $calendar->getBackend();
	
	if ($backend instanceof Func_CalendarBackend_Ovi)
	{
		// do not create the vacation period if the calendar backend is ovidentia because the calendar api will get the original vacation period
		return;
	}
	
	$date_begin = BAB_DateTime::fromIsoDateTime($row['date_begin']);
	$date_end	= BAB_DateTime::fromIsoDateTime($row['date_end']);
	
	$period = $backend->CalendarPeriod($date_begin->getTimeStamp(), $date_end->getTimeStamp());
	$collection = $backend->CalendarEventCollection($calendar);
	$collection->addPeriod($period);
	
	bab_vac_setPeriodProperties($period, $row, $date_begin);
	
	$period->save();
}








/**
 * Update the period properties with vacation informations
 * @param 	bab_CalendarPeriod	$p
 * @param 	Array				$row			entry of vacation request
 * @param	BAB_DateTime		$begin			begin date of request period
 * @return unknown_type
 */
function bab_vac_setPeriodProperties(bab_CalendarPeriod $p, $row, BAB_DateTime $begin)
{
	
	require_once dirname(__FILE__).'/workinghoursincl.php';
	global $babDB;
	
	$date_begin = BAB_DateTime::fromIsoDateTime($row['date_begin']);
	$date_end	= BAB_DateTime::fromIsoDateTime($row['date_end']);
	$p->setDates($date_begin, $date_end);
	
	
	
	$ventilation = array();

	
	$req = "SELECT 
			e.quantity, 
			t.name type, 
			t.color 
		FROM ".BAB_VAC_ENTRIES_ELEM_TBL." e,
			".BAB_VAC_RIGHTS_TBL." r,
			".BAB_VAC_TYPES_TBL." t 
		WHERE 
			e.id_entry=".$babDB->quote($row['id'])." 
			AND r.id=e.id_right 
			AND t.id=r.id_type 
			
		ORDER BY t.name";

	$res2 = $babDB->db_query($req);

	$count = $babDB->db_num_rows($res2);

	$type_day       = $date_begin->cloneDate();
	$type_day_end   = $date_begin->cloneDate();
	$ignore 		= array();
	
	while ($arr = $babDB->db_fetch_assoc($res2))
		{
		$ventilation[] = $arr;
		
		for($d = 0; $d < $arr['quantity']; $d += 0.5) {

			// si le jour est ferie ou non travaille , ajouter plus de jours
			while (!$wh = bab_getWHours($row['id_user'], date('w', $type_day_end->getTimeStamp())) || isset($nwdays[$type_day_end->getIsoDate()])) {
				$ignore[$type_day_end->getIsoDate()] = 1;
				$type_day_end->add(12, BAB_DATETIME_HOUR);
			}
			
			$type_day_end->add(12, BAB_DATETIME_HOUR);
			
		}
		
		//bab_debug('periode '.bab_longDate($type_day->getTimeStamp()).' - '.bab_longDate($type_day_end->getTimeStamp()).' <div style="background:#'.$arr['color'].'">'.$arr['type'].' '.$arr['quantity'].'</div>');
		
		while ($type_day->getTimeStamp() < $type_day_end->getTimeStamp() ) {
			
			if ($type_day->getTimeStamp() >= $begin->getTimeStamp() && !isset($ignore[$type_day->getIsoDate()])) {
				
				//bab_debug('push '.bab_longDate($type_day->getTimeStamp()).'  end : '.bab_longDate($type_day_end->getTimeStamp()).' <div style="background:#'.$arr['color'].'">'.$arr['type'].'</div>');

				bab_vac_typeColorStack(
						$row['id'], 
						array(
								'id_type'       => $arr['type'], 
								'color'         => $arr['color']
						)
				);	
			}
			
			$type_day->add(12, BAB_DATETIME_HOUR);
		}
	}

	
	
	
	
	list($id_cat, $category, $color) = $babDB->db_fetch_row($babDB->db_query("
	
		SELECT 
			cat.id,
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
			AND vet.id=".$babDB->quote($row['id'])." 
			AND cat.id = vct.id_cat 
	"));


	
	$p->setProperty('SUMMARY'			, bab_translate("Vacation"));
	$p->setProperty('CATEGORIES'		, $category);
	$p->setProperty('X-CTO-COLOR'		, $color);
	$p->setProperty('X-CTO-VACATION'	, $row['id']);
	
	if ($row['comment'])
	{
		$p->setProperty('COMMENT'		, $row['comment']);
	}

	$description = '';
	$descriptiontxt = '';

	if ('Y' !== $row['status']) {
		$description .= '<p>'.bab_translate("Waiting to be validate").'</p>';
		$descriptiontxt .= bab_translate("Waiting to be validate")."\n";
	}

	$label = (1 === count($ventilation)) ? bab_translate('Vacations type') : bab_translate('Vacations types');

	$description .= '<table class="bab_cal_vacation_types" cellspacing="0">';
	$description .= '<thead><tr><td colspan="3">'.bab_toHtml($label).'</td></tr></thead>';
	$description .= '<tbody>';
	
	foreach($ventilation as $type) {
		
		$days = rtrim($type['quantity'],'0.');
		
		$description .= sprintf(
			'<tr><td style="background:#%s">&nbsp; &nbsp;</td><td>%s</td><td>%s</td></tr>',
			$type['color'],
			$days,
			$type['type']
			);
			
		$descriptiontxt .= $days.' '.$type['type']."\n";
	}
	$description .= '</tbody></table>';
	
	$data = array(
		'id' => $row['id'],
		'description' => $description,
		'description_format' => 'html',
		'id_user' => $row['id_user']
	);
	
	$p->setData($data);

	$p->setProperty('DESCRIPTION', $descriptiontxt);
}








/**
 * Clear calendar data
 * On non-working days changes by admin
 * On working hours changes by admin
 */
function bab_vac_clearCalendars() {
	global $babDB;
	$babDB->db_query("DELETE FROM ".BAB_VAC_CALENDAR_TBL."");
}


/**
 * Clear calendar data for user
 */
function bab_vac_clearUserCalendar($id_user = NULL) {
	if (NULL === $id_user) {
		$id_user = $GLOBALS['BAB_SESS_USERID'];
	}
	global $babDB;
	$babDB->db_query("DELETE FROM ".BAB_VAC_CALENDAR_TBL." WHERE id_user=".$babDB->quote($id_user));
}

/**
 * Update calendar data overlapped with event
 * @param 	int 	$id_event
 */
function bab_vac_updateEventCalendar($id_entry) {
	global $babDB;
	$res = $babDB->db_query("
		SELECT 
			id_user,
			date_begin, 
			date_end 
		FROM 
			".BAB_VAC_ENTRIES_TBL." 
		WHERE 
			id=".$babDB->quote($id_entry)
	);
	$arr = $babDB->db_fetch_assoc($res);
	
	$date_begin = bab_mktime($arr['date_begin']);
	$date_end	= bab_mktime($arr['date_end']);
	
	include_once $GLOBALS['babInstallPath']."utilit/eventperiod.php";
	$event = new bab_eventPeriodModified($date_begin, $date_end, $arr['id_user']);
	$event->types = BAB_PERIOD_VACATION;
	bab_fireEvent($event);
}

/**
 * Refresh calendar if modified
 * @param	bab_eventPeriodModified	$event
 */
function bab_vac_onModifyPeriod($event) {
	global $babDB;

	$vacation 	= (BAB_PERIOD_VACATION 	=== ($event->types & BAB_PERIOD_VACATION));
	$nwday		= (BAB_PERIOD_NWDAY 	=== ($event->types & BAB_PERIOD_NWDAY));
	$working	= (BAB_PERIOD_WORKING	=== ($event->types & BAB_PERIOD_WORKING));

	if (!$vacation && !$nwday && !$working) {
		return;
	}

	if (false === $event->id_user) {
		$babDB->db_query("TRUNCATE bab_vac_calendar");
		return;
	}

	if (false === $event->begin || false === $event->end) {
		bab_vac_clearUserCalendar($event->id_user);
		return;
	}

	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

	$date_begin = BAB_DateTime::fromTimeStamp($event->begin);
	$date_end	= BAB_DateTime::fromTimeStamp($event->end);
	$date_end->add(1, BAB_DATETIME_MONTH);

	while ($date_begin->getTimeStamp() <= $date_end->getTimeStamp()) {
		$month	= $date_begin->getMonth();
		$year	= $date_begin->getYear();
		bab_vac_updateCalendar($event->id_user, $year, $month);
		$date_begin->add(1, BAB_DATETIME_MONTH);
	}
}






/**
 * si type2 est prioritaire, return true
 */
function bab_vac_compare($type1, $type2, $vacation_is_free) {
	
	if ($vacation_is_free) {
	
	$order = array(
		'bab_VacationPeriodCollection'			=> 1,
		'bab_NonWorkingPeriodCollection'		=> 2,
		'bab_WorkingPeriodCollection' 			=> 3,
		'bab_NonWorkingDaysCollection'			=> 6
	);
	
	} else {
	
	$order = array(

		'bab_NonWorkingPeriodCollection'		=> 1,
		'bab_WorkingPeriodCollection'			=> 2,
		'bab_VacationPeriodCollection'			=> 5,
		'bab_NonWorkingDaysCollection'			=> 6
	);
	
	}
	
	
	if (!isset($order[$type1]))
	{
		throw new Exception(sprintf('The vacation calendar request has received the collection %s from a calendar backend, the backends must not return events of non requested collections', $type1));
	}
	
	if (!isset($order[$type2]))
	{
		throw new Exception(sprintf('The vacation calendar request has received the collection %s from a calendar backend, the backends must not return events of non requested collections', $type2));
	}


	if ($order[$type2] > $order[$type1]) {
		return true;
	}

	return false;
}

function bab_vac_is_free($collection) {

	
	switch(true) {
		case $collection instanceof bab_WorkingPeriodCollection:
			return true;

		case $collection instanceof bab_NonWorkingPeriodCollection:
		case $collection instanceof bab_VacationPeriodCollection:
		case $collection instanceof bab_NonWorkingDaysCollection:
			return false;
	}
}







/**
 * @param	int				$id_user
 * @param	BAB_dateTime	$begin
 * @param	BAB_dateTime	$end
 * @param	boolean			$vacation_is_free
 * @return array
 */
function bab_vac_getHalfDaysIndex($id_user, $dateb, $datee, $vacation_is_free = false) {

	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/calincl.php";

	$obj = new bab_UserPeriods( 
			$dateb, 
			$datee
		);
		
	$factory = bab_getInstance('bab_PeriodCriteriaFactory');
	/* @var $factory bab_PeriodCriteriaFactory */

	$criteria = $factory->Collection(
		array(
			'bab_NonWorkingDaysCollection', 
			'bab_NonWorkingPeriodCollection',
			'bab_WorkingPeriodCollection', 
			'bab_VacationPeriodCollection' 
		)
	);
	
	$icalendars = new bab_icalendars($id_user);

	/*
	$calendar = $icalendars->getPersonalCalendar();
	
	if (!isset($calendar))
	{
		// the user personal calendar is not accessible
		// create an instance only for vacations
		
		// $calendar = bab_functionality::get('CalendarBackend')->PersonalCalendar($GLOBALS['BAB_SESS_USERID']);
		$calendar = bab_functionality::get('CalendarBackend')->PersonalCalendar($id_user);
	}
	*/
	
	$calendar = bab_functionality::get('CalendarBackend')->PersonalCalendar($id_user);
	
	$criteria = $criteria->_AND_($factory->Calendar($calendar));

	$obj->createPeriods($criteria);
	$obj->orderBoundaries();

	$index = array();
	$is_free = array();
	$stack = array();
	
	foreach($obj as $pe) {
		
		bab_debug(bab_shortDate($pe->ts_begin).' '.bab_shortDate($pe->ts_end).' '.$pe->getProperty('SUMMARY'));
		
		/*@var $pe bab_CalendarPeriod */
		$group = $pe->split(12 * 3600);
		foreach($group as $p) {
			
			/*@var $p bab_CalendarPeriod */
			if ($p->ts_begin < $datee->getTimeStamp() && $p->ts_end > $dateb->getTimeStamp()) {
				$key = date('Ymda',$p->ts_begin);
				$collection = $p->getCollection();
				$type = get_class($collection);
				
				$stack[$key][$type] = $p;

				if (!isset($index[$key]) || bab_vac_compare(get_class($index[$key]->getCollection()), $type, $vacation_is_free)) {
					
					$index[$key] = $p;
					
					
					if (bab_vac_is_free($collection)) {
						$is_free[$key] = 1;
					} elseif (isset($is_free[$key])) {
						unset($is_free[$key]);
					}
				}
			}
		}
	}

	return array($index, $is_free, $stack);
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







/**
 * Update planning for the given user
 * and the given period
 * @param int		$id_user
 * @param int		$year
 * @param int		$month
 */
function bab_vac_updateCalendar($id_user, $year, $month) {

	global $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";

	$babDB->db_query("DELETE FROM ".BAB_VAC_CALENDAR_TBL." WHERE monthkey=".$babDB->quote($month.$year).' AND id_user='.$babDB->quote($id_user));

	$dateb = new BAB_dateTime($year, $month, 1); 
	$datee = $dateb->cloneDate();
	$datee->add(1, BAB_DATETIME_MONTH);

	list($index, $is_free, $stack) = bab_vac_getHalfDaysIndex($id_user, $dateb, $datee);
	$previous = NULL;

	foreach($index as $key => $p) {

		$ampm		= 'pm' === date('a',$p->ts_begin) ? 1 : 0;
		$data		= $p->getData();
		$id_entry	= 0;
		$color		= '';
		
		$collection = $p->getCollection();
		
		switch(true) {
			case $collection instanceof bab_WorkingPeriodCollection:
				$type = BAB_PERIOD_WORKING;
				break;
				
			case $collection instanceof bab_NonWorkingPeriodCollection:
				$type = BAB_PERIOD_NONWORKING;
				break;
				
			case $collection instanceof bab_VacationPeriodCollection:
				$type = BAB_PERIOD_VACATION;
				break;
				
			case $collection instanceof bab_NonWorkingDaysCollection:
				$type = BAB_PERIOD_NWDAY;
				break;
			
		}
		
		


		if ($p->getCollection() instanceof bab_VacationPeriodCollection) { 
			if (isset($stack[$key]['bab_WorkingPeriodCollection'])) {
				$id_entry = $data['id']; 
				$arr = bab_vac_typeColorStack($id_entry);
				$color = $arr['color'];
			} else {
				$type = BAB_PERIOD_NONWORKING;
			}
		}


		$key = $id_user.$month.$year.$id_entry.$color.$type;

		if ($key !== $previous) {

			$previous = $key;
			bab_vac_group_insert("(
				".$babDB->quote($id_user).",
				".$babDB->quote($month.$year).",
				".$babDB->quote(date('Y-m-d',$p->ts_begin)).",
				".$babDB->quote($ampm).",
				".$babDB->quote($type).",
				".$babDB->quote($id_entry).",
				".$babDB->quote($color)."
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

	global $babDB;
	
	
	
	$arr = $babDB->db_fetch_assoc($babDB->db_query("
		SELECT idfai, id_user, date_begin, date_end  
			FROM ".BAB_VAC_ENTRIES_TBL." 
			WHERE id=".$babDB->quote($id_request)));
	
	if (!$arr)
	{
		return false;
	}
	
	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";
	$date_begin = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
	$date_end	= BAB_DateTime::fromIsoDateTime($arr['date_end']);
	
	$period = bab_vac_getPeriod($id_request, $arr['id_user'], $date_begin, $date_end);
	if (null !== $period)
	{
		$period->delete();
	}

	if ($arr['idfai'] > 0) 
	{
		deleteFlowInstance($arr['idfai']);
	}

	$babDB->db_query("DELETE FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry=".$babDB->quote($id_request)."");
	$babDB->db_query("DELETE FROM ".BAB_VAC_ENTRIES_TBL." WHERE id=".$babDB->quote($id_request));
	
	
	
	
	
	
	$date_end->add(1, BAB_DATETIME_MONTH);

	while ($date_begin->getTimeStamp() <= $date_end->getTimeStamp()) {
		$month	= $date_begin->getMonth();
		$year	= $date_begin->getYear();
		bab_vac_updateCalendar($arr['id_user'], $year, $month);
		$date_begin->add(1, BAB_DATETIME_MONTH);
	}
}



/**
 * Try to get a period from the calendar API from the request
 * The calendar backend can contain a period duplicated into the calendarEventCollection with need to be updated or deleted
 * This function can work without access to the personal calendar of the user
 * 
 * @param	int				$id_request
 * @param	int				$id_user		search the period in this user personal calendar
 * @param	BAB_DateTime	$begin			request search begin date	(should be the request begin date)
 * @param	BAB_DateTime	$end			request search end date		(should be the request end date)
 * 
 * @return bab_CalendarPeriod | null
 */
function bab_vac_getPeriod($id_request, $id_user, BAB_DateTime $begin, BAB_DateTime $end)
{
	require_once dirname(__FILE__).'/calincl.php';
	global $babDB;
		
	$icalendars = new bab_icalendars($id_user);
	
	$calendar = $icalendars->getPersonalCalendar();
	
	if (!$calendar)
	{
		return null;
	}
	
	$backend = $calendar->getBackend();
	
	$factory = $backend->Criteria();
	$criteria = $factory->Calendar($calendar);
	$criteria = $criteria->_AND_($factory->Collection('bab_CalendarEventCollection'));
	$criteria = $criteria->_AND_($factory->Begin($begin));
	$criteria = $criteria->_AND_($factory->End($end));
	$criteria = $criteria->_AND_($factory->Property('X-CTO-VACATION', $id_request));
	
	$periods = $backend->selectPeriods($criteria);
	
	foreach($periods as $period)
	{
		return $period;
	}
	
	return null;
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
	var $babDB;
	var $count;
	var $res;
	var $veid;
	var $wusers = array();

	var $statustxt;
	var $status;


	function bab_vacationRequestDetail($id)
		{
		$this->daterequesttxt = bab_translate("Request date");
		$this->datebegintxt = bab_translate("Begin date");
		$this->dateendtxt = bab_translate("End date");
		$this->nbdaystxt = bab_translate("Quantities");
		$this->totaltxt = bab_translate("Total");
		$this->statustxt = bab_translate("Status");
		$this->commenttxt = bab_translate("Description");
		$this->remarktxt = bab_translate("Comment");
		$this->t_approb = bab_translate("Approver");
		global $babDB;
		$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id=".$babDB->quote($id)));

		$acclevel = bab_vacationsAccess();
		if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
		{
			if (!bab_IsUserUnderSuperior($row['id_user'])) {
				global $babBody;
				$babBody->addError(bab_translate('Access denied'));
				return false;
			}
		}

		$this->daterequest = bab_toHtml(bab_longDate(bab_mktime($row['date']), false));
		$this->datebegin = bab_toHtml(bab_vac_longDate(bab_mktime($row['date_begin'])));
		$this->dateend = bab_toHtml(bab_vac_longDate(bab_mktime($row['date_end'])));
		$this->owner = bab_toHtml(bab_getUserName($row['id_user']));
		$this->statarr = array(bab_translate("Waiting to be valiadte by"), bab_translate("Accepted"), bab_translate("Refused"));
		$this->comment = bab_toHtml($row['comment'], BAB_HTML_ALL);
		$this->remark = bab_toHtml($row['comment2'], BAB_HTML_ALL);
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
		
		$this->approb = bab_toHtml(bab_getUserName($row['id_approver']));

		$req = "select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry=".$babDB->quote($id);
		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
		$this->totalval = 0;
		$this->veid = $id;
		}

	function getnexttype()
		{
		static $i = 0;
		if( $i < $this->count)
			{
			global $babDB;
			$arr = $babDB->db_fetch_array($this->res);
			list($this->typename) = $babDB->db_fetch_row($babDB->db_query("select description from ".BAB_VAC_RIGHTS_TBL." where id ='".$babDB->db_escape_string($arr['id_right'])."'"));
			$this->nbdays = $arr['quantity'];
			$this->totalval += $this->nbdays;
			$this->typename = bab_toHtml($this->typename);
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
			$this->fullname = bab_toHtml(bab_getUserName($this->wusers[$i]));
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
	global $babDB;
	$res = $babDB->db_query("SELECT id_entity FROM ".BAB_VAC_COMANAGER_TBL." WHERE id_user=".$babDB->quote($id_user));

	if (0 == $babDB->db_num_rows($res)) {
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

	while ($arr = $babDB->db_fetch_assoc($res)) {
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
 * by half days
 * @param	int		$id_user	
 * @param	int		$begin				timestamp
 * @param	int		$end				timestamp
 * @param	boolean	$vacation_is_free
 * @return	int
 */
function bab_vac_getFreeDaysBetween($id_user, $begin, $end, $vacation_is_free = false) {

	$calcul = 0;

	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

	bab_debug(bab_shortDate($begin).' '.bab_shortDate($end));

	list($index, $is_free) = bab_vac_getHalfDaysIndex(
		$id_user, 
		BAB_DateTime::fromTimeStamp($begin), 
		BAB_DateTime::fromTimeStamp($end),
		$vacation_is_free
	);


	foreach($index as $key => $p) {

		if (isset($is_free[$key])) {
			$calcul += 0.5;
		}
	}

	
	return $calcul;
}





