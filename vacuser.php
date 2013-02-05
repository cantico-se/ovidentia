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

include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/vacincl.php";


function bab_isRequestEditable($id)
	{
	if ($id == 0)
		{
		return true;
		}

	global $babDB;
	list($id_user,$status) = $babDB->db_fetch_array($babDB->db_query("SELECT id_user,status FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$babDB->db_escape_string($id)."'"));



	if ($id_user == $GLOBALS['BAB_SESS_USERID'])
		{
		if (empty($status))
			return $id_user;
		else
			return false;
		}
	elseif($status == 'Y')
		{
		if (bab_IsUserUnderSuperior($id_user))
			return $id_user;
		else
			return false;
		}
	else
		return false;
	}




function bab_vacRequestCreate($id_user) {
	global $babBody, $babDB;


	$res = $babDB->db_query("SELECT COUNT(*) FROM ".BAB_VAC_PERSONNEL_TBL." WHERE id_user='".$babDB->db_escape_string($id_user)."'");
	list($n) = $babDB->db_fetch_array($res);

	if ($n == 0) {
		$babBody->msgerror = bab_translate("The user is not registered in the personnel list for vacations");
		return false;
		}

	if ($id_user == $GLOBALS['BAB_SESS_USERID']) {
		return true;
		}
	else
		{
		$acclevel = bab_vacationsAccess();
		if( isset($acclevel['manager']) && $acclevel['manager'] == true)
			{
			return true;
			}

		if (bab_getVacationOption('chart_superiors_create_request') && bab_IsUserUnderSuperior($id_user)) {
			return true;
			}
		}

	$babBody->msgerror = bab_translate("Access denied");
	return false;
	}


function requestVacation($begin,$end, $id)
	{
	global $babBody;
	class temp
		{


		public function __construct($begin, $end, $id)
			{
			global $babBody, $babDB;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->vactype = bab_translate("Vacation type");
			$this->addvac = bab_translate("Request vacation");
			$this->remark = bab_translate("Remarks");
			$this->nbdaystxt = bab_translate("Quantity");
			$this->invaliddate = bab_toHtml(bab_translate("ERROR: End date must be older"), BAB_HTML_JS);
			$this->invaliddate2 = bab_toHtml(bab_translate("Total number of days does not fit between dates"), BAB_HTML_JS);
			$this->invalidentry = bab_toHtml(bab_translate("Invalid entry!  Only numbers are accepted or . !"), BAB_HTML_JS);
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->total_days = 0;
			$this->total_hours = 0;
			$this->maxallowed = 0;
			$this->id = $id;
			$this->id_user = $_POST['id_user'];
			$this->username = bab_toHtml(bab_getUserName($this->id_user));
			$this->t_days = bab_translate("working days");
			$this->t_alert_nomatch = bab_toHtml(bab_translate("Total number of affected days does not match the period."),BAB_HTML_JS);
			$this->t_confirm_nomatch = bab_toHtml(bab_translate("Total number of affected days does not match the period, do you really want to submit your request with this mismatch?"),BAB_HTML_JS);
			$this->t_or = bab_translate('Or');


			$res = $babDB->db_query('SELECT allow_mismatch FROM ' . BAB_VAC_OPTIONS_TBL);
			if ($arr = $babDB->db_fetch_array($res)) {
				$this->allow_mismatch = $arr['allow_mismatch'];
			} else {
				$this->allow_mismatch = '1';
			}

			$date_begin = BAB_DateTime::fromIsoDateTime($begin);
			$date_end	= BAB_DateTime::fromIsoDateTime($end);

			$begin	= $date_begin->getTimeStamp();
			$end	= $date_end->getTimeStamp();

			list($days, $hours) = bab_vac_getFreeDaysBetween($this->id_user, $begin, $end, true);
			$this->period_nbdays = $days;
			$this->period_nbhours = $hours;

			/**
			 * nombre de jours non utilises restant, initialisation
			 */
			$this->last_days = $days;

			/**
			 * nombre d'heures non utilises restantes, initialisation
			 */
			$this->last_hours = $hours;


			$this->t_days = bab_translate("Day(s)");



			$this->begin		= $date_begin->getIsoDateTime();
			$this->end			= $date_end->getIsoDateTime();

			$this->rfrom = isset($_POST['rfrom'])? $_POST['rfrom'] : 0;
			$this->rights = array();
			$rights = bab_getRightsOnPeriod($this->begin, $this->end, $this->id_user, $this->rfrom);

			$this->contain_hours_rights = false;


			foreach($rights as $right) {
				$id		= empty($right['id_rgroup']) ? 'r'.$right['id'] : 'g'.$right['id_rgroup'];

				if ('H' === $right['quantity_unit'])
				{
					$this->contain_hours_rights = true;
				}

				if (isset($this->rights[$id])) {
					$this->rights[$id]['rights'][$right['id']] = array(
						'description'	=> $right['description'],
						'quantity_available'	=> $right['quantity_available'] - $right['waiting']
					);
					continue;

				} elseif(!empty($right['id_rgroup'])) {
					$right['rights'] = array(
						$right['id'] => array(
							'description' => $right['description'],
							'quantity_available'	=> $right['quantity_available'] - $right['waiting']
						)
					);
				}

				$this->rights[$id] = $right;
			}



			$days = sprintf('<strong>%s</strong>', $days);
			$hours = sprintf('<strong>%s</strong>', $hours);


			if ($this->contain_hours_rights)
			{
				$this->period_infos = sprintf(bab_translate('The period contain %s day(s) or %s hour(s)'), $days, $hours);

			} else {
				$this->period_infos = sprintf(bab_translate('The period contain %s day(s)'), $days);

			}



			if (!empty($this->id))
				{
				$res = $babDB->db_query("SELECT id_right, quantity FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry='".$babDB->db_escape_string($this->id)."'");
				while ($arr = $babDB->db_fetch_array($res))
					{
					$this->current['r'.$arr['id_right']] = $arr['quantity'];
					}
				}

			$this->recorded = array();
			if (!empty($this->id))
				{
				$res = $babDB->db_query("
				SELECT
					e.id_right,
					r.id_rgroup,
					e.quantity
				FROM
					".BAB_VAC_ENTRIES_ELEM_TBL." e,
					".BAB_VAC_RIGHTS_TBL." r

					WHERE
						e.id_entry='".$babDB->db_escape_string($this->id)."'
						AND e.id_right = r.id
				");
				while($arr = $babDB->db_fetch_array($res))
					{
						if (empty($arr['id_rgroup'])) {
							$this->recorded['r'.$arr['id_right']] = $arr['quantity'];
						} else {
							$this->recorded['g'.$arr['id_rgroup']] = $arr['quantity'];
						}
					}

				list($this->remarks) = $babDB->db_fetch_array($babDB->db_query("SELECT comment FROM ".BAB_VAC_ENTRIES_TBL." WHERE id=".$babDB->quote($this->id)));
				}
			else
				{
				$this->remarks = isset($_POST['remarks']) ? stripslashes($_POST['remarks']) : '';
				}

			$this->datebegin = bab_vac_longDate($begin);
			$this->dateend = bab_vac_longDate($end);


			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$this->id_user."&popup=1";

			}

		/**
		 *
		 * @param float $hours
		 * @return float
		 */
		private function daysToHours($days)
		{
			if (0 == $this->period_nbdays)
			{
				return 0;
			}

			$ratio = $this->period_nbhours / $this->period_nbdays;
			return round(($ratio * $days), 2);
		}

		/**
		 *
		 * @param float $hours
		 * @return float
		 */
		private function hoursToDays($hours)
		{
			if (0 == $this->period_nbhours)
			{
				return 0;
			}

			$ratio = $this->period_nbdays / $this->period_nbhours;
			return round(($ratio * $hours), 2);
		}

		/**
		 *
		 * @param string 	$unit		D | H
		 * @param float		$set
		 *
		 * @return mixed
		 */
		private function last($unit, $set = null)
		{
			if (null === $set)
			{
				switch($unit)
				{
					case 'D':
						return $this->last_days;

					case 'H':
						return $this->last_hours;
				}
			}
			else
			{
				switch($unit)
				{
					case 'D':
						$this->last_days = $set;
						$this->last_hours = $this->daysToHours($set);
						break;

					case 'H':
						$this->last_days = $this->hoursToDays($set);
						$this->last_hours = $set;
						break;
				}
			}
		}


		public function getnextright()
		{

			if (list($id,$this->right) = each($this->rights))
				{

				$this->id_rgroup = $this->right['id_rgroup'];
				$this->rgroup = bab_toHtml($this->right['rgroup']);

				$this->right['description'] = bab_toHtml($this->right['description']);
				$this->right['waiting'] -= isset($this->current[$id]) ? $this->current[$id] : 0;
				$this->right['quantity_available'] = $this->right['quantity_available'] - $this->right['waiting'];
				$this->quantity_available = bab_toHtml(bab_vac_quantity($this->right['quantity_available'], $this->right['quantity_unit']));

				switch($this->right['quantity_unit'])
				{
					case 'D':
						$this->unit = bab_translate('day(s)');
						break;

					case 'H':
						$this->unit = bab_translate('hour(s)');
						break;
				}


				if (isset($_POST['nbdays'][$id]))
					{
					$this->quantity = $_POST['quantity'][$id];
					}
				elseif( count($this->recorded) > 0) {
					if (isset($this->recorded[$id])) {
						$this->quantity = $this->recorded[$id];
					}
					else {
						$this->quantity = 0;
					}
				}
				elseif (0 == $this->right['no_distribution'] && $this->last($this->right['quantity_unit']) > 0 && $this->right['quantity_available'] > 0)
					{

					$last = $this->last($this->right['quantity_unit']);


					if ($last >= $this->right['quantity_available'])
						{
						$this->quantity = $this->right['quantity_available'];

						$last -= $this->right['quantity_available'];
						$this->last($this->right['quantity_unit'], $last);
						}
					elseif ($this->right['quantity_available'] > 0)
						{
						$this->quantity = $last;
						$this->last($this->right['quantity_unit'], 0);
						}
					}
				else
					{
					$this->quantity = 0;

					}


				switch($this->right['quantity_unit'])
				{
					case 'D':
						$this->total_days += $this->quantity;
						$this->total_hours += $this->daysToHours($this->quantity);
						break;
					case 'H':
						$this->total_hours += $this->quantity;
						$this->total_days += $this->hoursToDays($this->quantity);
						break;
				}

				return true;
				}
			else
				return false;

			}


		public function getnextrgroupright() {
			if (list($id, $arr) = each($this->right['rights'])) {
				$this->id_right = bab_toHtml($id);
				$this->description = bab_toHtml($arr['description']);
				$this->quantity_available = bab_toHtml($arr['quantity_available']);
				return true;
			}
			return false;
			}

		}

	$temp = new temp($begin,$end, $id);
	$babBody->babecho(	bab_printTemplate($temp,"vacuser.html", "newvacation"));
	}



function period($id_user, $id = 0)
	{
	class ptemp
		{


		function ptemp($id_user, $id)
			{
			global $babBody, $babDB;
			$this->datebegin = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=2";
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateend = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=2";
			$this->dateendtxt = bab_translate("End date");
			$this->vactype = bab_translate("Vacation type");
			$this->addvac = bab_translate("Request vacation");
			$this->remark = bab_translate("Remarks");
			$this->nbdaystxt = bab_translate("Quantity");
			$this->invaliddate = bab_translate("ERROR: End date must be older");
			$this->invaliddate = str_replace("'", "\'", $this->invaliddate);
			$this->invaliddate = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate);
			$this->invaliddate2 = bab_translate("Total days does'nt fit between dates");
			$this->invaliddate2 = str_replace("'", "\'", $this->invaliddate2);
			$this->invaliddate2 = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate2);
			$this->invalidentry = bab_translate("Invalid entry!  Only numbers are accepted or . !");
			$this->invalidentry = str_replace("'", "\'", $this->invalidentry);
			$this->invalidentry = str_replace('"', "'+String.fromCharCode(34)+'",$this->invalidentry);
			$this->invalidentry1 = bab_translate("Invalid entry");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->t_total = bab_translate("Total");
			$this->t_available = bab_translate("Available");
			$this->t_waiting = bab_translate("Waiting");
			$this->t_period_nbdays = bab_translate("Period days");
			$this->t_view_rights = bab_translate("View rights details");

			$this->totalval = 0;
			$this->maxallowed = 0;
			$this->id = $id;
			$this->id_user = $id_user;

			$this->year = isset($_REQUEST['year']) ? $_REQUEST['year'] : date('Y');
			$this->rfrom = isset($_REQUEST['rfrom']) ? $_REQUEST['rfrom'] : 0;

			if (!empty($this->id) && !isset($_POST['daybegin']))
				{
				include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

				$res = $babDB->db_query("SELECT * FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$babDB->db_escape_string($this->id)."'");
				$arr = $babDB->db_fetch_array($res);

				$date_begin			= BAB_DateTime::fromIsoDateTime($arr['date_begin']);
				$date_end			= BAB_DateTime::fromIsoDateTime($arr['date_end']);

				$this->yearbegin	= $date_begin->getYear();
				$this->monthbegin	= $date_begin->getMonth();
				$this->daybegin		= $date_begin->getDayOfMonth();

				$this->yearend		= $date_end->getYear();
				$this->monthend		= $date_end->getMonth();
				$this->dayend		= $date_end->getDayOfMonth();

				$this->hourbegin	= date('H:i:s', $date_begin->getTimeStamp());
				$this->halfdaybegin = 'am' === date('a', $date_begin->getTimeStamp())	? 0 : 1;
				$this->hourend		= date('H:i:s', $date_end->getTimeStamp());
				$this->halfdayend	= 'am' === date('a', $date_end->getTimeStamp())		? 0 : 1;
				}
			elseif (isset($_POST['daybegin']))
				{
				$this->daybegin		= $_POST['daybegin'];
				$this->dayend		= $_POST['dayend'];
				$this->monthbegin	= $_POST['monthbegin'];
				$this->monthend		= $_POST['monthend'];
				$this->yearbegin	= $this->year + $_POST['yearbegin']	- 1;
				$this->yearend		= $this->year + $_POST['yearend']	- 1;
				$this->halfdaybegin = 0;
				$this->halfdayend	= 1;
				$this->hourbegin	= $_POST['hourbegin'];
				$this->hourend		= $_POST['hourend'];
				}
			else
				{
				$this->daybegin		= date("j");
				$this->dayend		= date("j");
				$this->monthbegin	= date("n");
				$this->monthend		= date("n");
				$this->yearbegin	= $this->year;
				$this->yearend		= $this->year;
				$this->halfdaybegin = 0;
				$this->halfdayend	= 1;
				$this->hourbegin	= '00:00:00';
				$this->hourend		= '23:59:59';
				}

			$this->halfdaysel = $this->halfdaybegin;

			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$id_user;

			$this->rights = bab_getRightsByGroupOnPeriod($id_user, $this->rfrom);
			$this->total = array('D' => 0, 'H' => 0);
			$this->total_waiting = array('D' => 0, 'H' => 0);

			$this->dayType = array(
				bab_translate("Morning"),
				bab_translate("Afternoon")
				);

			$this->hours = bab_vac_hoursList($GLOBALS['BAB_SESS_USERID']);


			require_once dirname(__FILE__).'/utilit/workinghoursincl.php';
			$jSON = array();
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 0), $jSON);
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 1), $jSON);
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 2), $jSON);
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 3), $jSON);
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 4), $jSON);
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 5), $jSON);
			$this->addWorkingPeriod(bab_getWHours($GLOBALS['BAB_SESS_USERID'], 6), $jSON);

			$this->json_working_hours = '['.implode(',', $jSON).']';

		}

		/**
		 * reduce working periods for planning
		 */
		private function addWorkingPeriod($whours, &$jSON)
		{
			if (empty($whours))
			{
				return;
			}

			foreach($whours as $k => $period)
			{
				if ($period['startHour'] < '11:59:59' && $period['endHour'] > '12:00:00')
				{
					$whours[$k]['endHour'] = '11:59:59';
					$whours[] = array(
							'startHour' => '12:00:00',
							'endHour' => $period['endHour'],
							'weekDay' => $period['weekDay']
					);
				}
			}


			foreach($whours as $period)
			{
				$jSON[] = '{
					weekDay:'.$period['weekDay'].',
					startHour:\''.$period['startHour'].'\',
					endHour:\''.$period['endHour'].'\'
				}';
			}

		}


		function getnextday()
			{
			static $i = 1;

			if( $i <= date("t"))
				{
				$this->dayid = $i;
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			static $i = 1;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = bab_DateStrings::getMonth($i);
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0;
			if( $i < 3)
				{
				$this->yearid = $i+1;
				$this->yearidval = $this->year + $i;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		function getnexthour()
		{
			if (list($key, $value) = each($this->hours))
			{
				$this->value = bab_toHtml($key);
				$this->option = bab_toHtml($value);
				return true;
			}
			reset($this->hours);
			return false;
		}

		function getnextright()
			{
			if ($this->right = & current($this->rights))
				{
				next($this->rights);
				$this->right['quantity_available'] = $this->right['quantity_available'] - $this->right['waiting'];

				$this->total[$this->right['quantity_unit']] += $this->right['quantity_available'];
				$this->total_waiting[$this->right['quantity_unit']] += $this->right['waiting'];

				$this->right['quantity_available'] = bab_toHtml(bab_vac_quantity($this->right['quantity_available'], $this->right['quantity_unit']));
				$this->right['waiting'] = bab_toHtml(bab_vac_quantity($this->right['waiting'], $this->right['quantity_unit']));

				return true;
				}
			else
				return false;

			}

		private function getDisplay($arr)
		{
			$list = array();
			foreach($arr as $quantity_unit => $quantity) {
				if ($quantity) {
					$list[] = bab_vac_quantity($quantity, $quantity_unit);
				}
			}

			return implode(', ', $list);
		}

		public function gettotal()
			{
				static $call = null;

				if (isset($call)) {
					return false;
				}

				$call = true;

				$this->total = bab_toHtml($this->getDisplay($this->total));
				$this->total_waiting = bab_toHtml($this->getDisplay($this->total_waiting));

				return true;
			}

		}
		$temp = new ptemp($id_user, $id);
		$GLOBALS['babBody']->babecho(bab_printTemplate($temp, "vacuser.html", "period"));
	}




function viewrights($id_user)
	{
	class temp
		{

		var $altbg = true;

		function temp($id_user) {
			$this->rights = bab_getRightsByGroupOnPeriod($id_user);
			$this->total = 0;
			$this->total_waiting = 0;
			$this->t_avariable_days = bab_translate("Avariable days");
			$this->t_waiting_days = bab_translate("Waiting days");
			$this->t_period_nbdays = bab_translate("Period days");
			$this->t_total = bab_translate("Total");

			$this->t_available = bab_translate("Available");
			$this->t_waiting = bab_translate("Waiting");

			$this->total = array('D' => 0, 'H' => 0);
			$this->total_waiting = array('D' => 0, 'H' => 0);
		}

		function getnextright()
			{
			if ($right = & current($this->rights))
				{
				$this->altbg = !$this->altbg;
				next($this->rights);
				$quantity_available = $right['quantity_available'] - $right['waiting'];
				$this->description = bab_toHtml($right['description']);

				$this->quantity_available = array('D' => 0, 'H' => 0);
				$this->waiting = array('D' => 0, 'H' => 0);

				$this->quantity_available[$right['quantity_unit']] = $quantity_available;
				$this->waiting[$right['quantity_unit']] = $right['waiting'];

				$this->total[$right['quantity_unit']] += $this->quantity_available[$right['quantity_unit']];
				$this->total_waiting[$right['quantity_unit']] += $this->waiting[$right['quantity_unit']];
				return true;
				}
			else
				return false;

			}

		}
		$temp = new temp($id_user);
		$GLOBALS['babBody']->babecho(bab_printTemplate($temp, "vacuser.html", "viewrights"));
	}



function vedUnload()
	{
	class temp
		{
		var $message;
		var $close;
		var $redirecturl;

		function temp()
			{
			$this->message = bab_translate("Vacation entry has been updated");
			$this->close = bab_translate("Close");
			$this->redirecturl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=lval";
			}
		}

	$temp = new temp();
	global $babBody;
	$babBody->babPopup(bab_printTemplate($temp,"vacuser.html", "vedunload"));
	}



class bab_vac_saveVacation
{


	/**
	 * Test vacation right validity
	 * @see addNewVacation()
	 *
	 * @param	array	&$nbdays	list of rights to save in request, rows are added on each valid calls
	 * @param	Array	$arr		vacation rights properties
	 * @param	float	$quantity	requested quantity in form
	 * @param	int		$id_request	if this is a request modification
	 */
	private static function addVacationRight(&$nbdays, Array $arr, $quantity, $id_request)
	{
		global $babBody;
		$quantity = str_replace(',','.', $quantity);


		if (!empty($id_request))
		{
			global $babDB;

			$res = $babDB->db_query("SELECT quantity FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry=".$babDB->quote($id_request)." AND id_right=".$babDB->quote($arr['id']));
			while ($current = $babDB->db_fetch_array($res))
			{
				$arr['waiting'] -= $current['quantity'];
			}
		}


		if( !is_numeric($quantity) || $quantity < 0 ) {
			$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
			return false;
		}



		if (!empty($quantity) && $arr['cbalance'] != 'Y' && ($arr['quantity_available'] - $arr['waiting'] - $quantity) < 0) {
			$babBody->addError(bab_translate("You can't take more than").' '.bab_vac_quantity(($arr['quantity_available']- $arr['waiting']), $arr['quantity_unit']).' '.bab_translate("on the right").' '.$arr['description']);
			return false;
		}

		if( $quantity > 0 ) {
			$nbdays['id'][] = (int) $arr['id'];
			$nbdays['val'][] = $quantity;
		}

		return true;
	}


	public static function save()
	{
		require_once dirname(__FILE__).'/utilit/dateTime.php';
		global $babBody, $babDB;





		$id_user		= $_POST['id_user'];
		$id_request		= $_POST['id'];
		$begin			= $_POST['begin'];
		$end			= $_POST['end'];
		$remarks		= $_POST['remarks'];
		$total			= $_POST['total'];
		$rfrom			= bab_pp('rfrom',0);


		$date_begin = BAB_DateTime::fromIsoDateTime($begin);
		$date_end	= BAB_DateTime::fromIsoDateTime($end);

		if (!test_period2($id_request, $id_user, $date_begin->getTimeStamp(), $date_end->getTimeStamp())) {
			return false;
		}


		$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." WHERE  id_user='".$babDB->db_escape_string($id_user)."'"));

		if( $rfrom == 1 )
			{
			$acclevel = bab_vacationsAccess();
			if( !isset($acclevel['manager']) || $acclevel['manager'] != true)
				{
				$rfrom = 0;
				}
			}




		$rights = bab_getRightsOnPeriod($begin, $end, $id_user, $rfrom);

		$rgroups = array();

		$nbdays = array();

		// regular vacation rights
		foreach($rights as $arr)
		{
			if( isset($_POST['quantity'][$arr['id']]))
			{
				$quantity = $_POST['quantity'][$arr['id']];
				if (!self::addVacationRight($nbdays, $arr, $quantity, $id_request))
				{
					return false;
				}
			}

			if ($arr['id_rgroup'] > 0) {
				$rgroups[$arr['id']] = $arr['id_rgroup'];
			}
		}

		// right groups

		if (isset($_POST['rgroup_right'])) {
			foreach($_POST['rgroup_right'] as $id_rgroup => $id_right) {
				if (isset($rgroups[$id_right]) && $rgroups[$id_right] == $id_rgroup) {
					$quantity = $_POST['rgroup_value'][$id_rgroup];
					$arr = $rights[$id_right];

					if (!self::addVacationRight($nbdays, $arr, $quantity, $id_request))
					{
						return false;
					}
				}
			}
		}



		if( $date_begin->getTimeStamp() >= $date_end->getTimeStamp())
			{
			$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
			return false;
			}


		if (empty($id_request))
			{
			// event creation

			$babDB->db_query("
				INSERT INTO
					".BAB_VAC_ENTRIES_TBL."
					(id_user, date_begin, date_end, comment, date, idfai, status)
				values
					(
						'" . $babDB->db_escape_string($id_user) . "',
						'" . $babDB->db_escape_string($date_begin->getIsoDateTime()) . "',
						'" . $babDB->db_escape_string($date_end->getIsoDateTime()) . "',
						'" . $babDB->db_escape_string($remarks) . "',
						curdate(),
						'0',
						'Y'
					)
			");
			$id = $babDB->db_insert_id();

			$period_begin = $date_begin->getTimestamp();
			$period_end = $date_end->getTimestamp();


			}
		else
			{
			// event modification


			$babDB->db_query("DELETE FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry='".$babDB->db_escape_string($id_request)."'");

			$rescurrent = $babDB->db_query("
				SELECT
					idfai,
					date_begin,
					date_end
				FROM
					".BAB_VAC_ENTRIES_TBL."
				WHERE
					id='".$babDB->db_escape_string($id_request)."'
			");
			list($idfai, $old_date_begin, $old_date_end) = $babDB->db_fetch_array($rescurrent);




			if ($idfai > 0) {
				deleteFlowInstance($idfai);
			}

			$babDB->db_query("
				UPDATE ".BAB_VAC_ENTRIES_TBL."
					SET
					date_begin	= '".$babDB->db_escape_string($date_begin->getIsoDateTime())."',
					date_end	= '".$babDB->db_escape_string($date_end->getIsoDateTime())."',
					comment		= '".$babDB->db_escape_string($remarks)."',
					date 		= curdate(),
					idfai 		= '0',
					status 		= 'Y'
				WHERE
					id='".$babDB->db_escape_string($id_request)."'
				");

			$id = $id_request;

			$old_date_begin_obj = BAB_DateTime::fromIsoDateTime($old_date_begin);
			$old_date_end_obj = BAB_DateTime::fromIsoDateTime($old_date_end);

			$old_date_begin = $old_date_begin_obj->getTimeStamp();
			$old_date_end = $old_date_end_obj->getTimeStamp();

			$new_date_begin = $date_begin->getTimeStamp();
			$new_date_end = $date_end->getTimeStamp();

			$period_begin	= $old_date_begin 	< $new_date_begin 	? $old_date_begin 	: $new_date_begin;
			$period_end 	= $old_date_end 	> $new_date_end 	? $old_date_end 	: $new_date_end;

			}


		// insert rights

		if (isset($nbdays['id']))
		{
		for( $i = 0; $i < count($nbdays['id']); $i++)
			{
			$babDB->db_query("INSERT into ".BAB_VAC_ENTRIES_ELEM_TBL."
					(id_entry, id_right, quantity)
				values
					(
						'" .$babDB->db_escape_string($id). "',
						'" .$babDB->db_escape_string($nbdays['id'][$i]). "',
						'" .$babDB->db_escape_string($nbdays['val'][$i]). "'
					)
				");
			}
		}

		// set period into calendar backend if necessary

		if (empty($id_request))
		{
			// event creation
			bab_vac_createPeriod($id);

		} else
		{
			// event modification
			bab_vac_updatePeriod($id, $old_date_begin_obj, $old_date_end_obj);
		}


		include_once $GLOBALS['babInstallPath']."utilit/eventperiod.php";
		$event = new bab_eventPeriodModified($period_begin, $period_end, $id_user);
		$event->types = BAB_PERIOD_VACATION;
		bab_fireEvent($event);



		$idfai = makeFlowInstance($row['id_sa'], "vac-".$id );
		$status = '';
		setFlowInstanceOwner($idfai, $id_user);

		$nfusers = getWaitingApproversFlowInstance($idfai, true);

		if (empty($nfusers))
		{
			// if no approvers, delete instance
			deleteFlowInstance($idfai);
			notifyOnRequestChange($id);
			bab_vac_notifyManagers::send($id_request);
		}
		else
		{
			$babDB->db_query("
					UPDATE
					".BAB_VAC_ENTRIES_TBL."
					SET
					idfai=".$babDB->quote($idfai).",
					status=".$babDB->quote($status)."
					WHERE
					id=".$babDB->quote($id)
			);

			notifyVacationApprovers($id, $nfusers, !empty($id_request));
			
			if (!empty($id_request))
			{
				bab_vac_notifyManagers::send($id_request);
			}
		}

		return true;
	}


}






function deleteVacationRequest($id)
	{
	global $babBody;

	class temp extends bab_vacationRequestDetail
		{

		function temp($id)
			{
				parent::bab_vacationRequestDetail($id);

				$this->id_entry = bab_rp('id_entry');

				$this->t_deleteconfirm = bab_translate("Do you really want to delete the vacation request ?");

				$this->url = isset($_REQUEST['from']) ? $_REQUEST['from'] : $_SERVER['HTTP_REFERER'];
				$this->t_delete = bab_translate("Delete");
			}

		}

	$temp = new temp($id);
	$babBody->babecho(bab_printTemplate($temp, "vacuser.html", "delete"));
	}





function test_period2($id_entry,$id_user,$begin,$end)
{
	global $babBody, $babDB;

	if( $begin >= $end)
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	$date_begin = date('Y-m-d H:i:s',$begin);
	$date_end = date('Y-m-d H:i:s',$end);


	$req = "SELECT
				COUNT(*)
		FROM ".BAB_VAC_ENTRIES_TBL."
			WHERE
			id_user='".$babDB->db_escape_string($id_user)."'
			AND (
					(date_begin BETWEEN '".$babDB->db_escape_string($date_begin)."' AND '".$babDB->db_escape_string($date_end)."' )
					 OR
					(date_end BETWEEN '".$babDB->db_escape_string($date_begin)."' AND '".$babDB->db_escape_string($date_end)."')
				)
			AND id <> '".$babDB->db_escape_string($id_entry)."'
			AND status<>'N'";

	$res = $babDB->db_query($req);
	list($n) = $babDB->db_fetch_array($res);

	if ($n > 0) {
		$babBody->msgerror = bab_translate("ERROR: a request is already defined on this period");
		return false;
	}

	return true;
}

function test_period()
{
global $babBody;
include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";

if (!isset($_POST['daybegin']) ||
	!isset($_POST['monthbegin']) ||
	!isset($_POST['yearbegin']) ||
	!isset($_POST['hourbegin']) ||
	!isset($_POST['dayend']) ||
	!isset($_POST['monthend']) ||
	!isset($_POST['yearend']) ||
	!isset($_POST['hourend']) ||
	!isset($_POST['id_user'])
	)
	{
	$babBody->msgerror = bab_translate("Error");
	return false;
	}

	$yearbegin = $_POST['year'] + $_POST['yearbegin'] - 1;
	$yearend = $_POST['year'] + $_POST['yearend'] - 1;

	$begin	= BAB_DateTime::fromIsoDateTime($yearbegin.'-'.$_POST['monthbegin'].'-'.$_POST['daybegin'].' '.$_POST['hourbegin']);
	$end	= BAB_DateTime::fromIsoDateTime($yearend.'-'.$_POST['monthend'].'-'.$_POST['dayend'].' '.$_POST['hourend']);

	$id_entry = isset($_POST['id']) ? $_POST['id'] : 0;

return test_period2($id_entry, $_POST['id_user'], $begin->getTimeStamp(), $end->getTimeStamp());
}


/**
 * Display a vacation calendar
 * test access rights
 * @param	array		$users		array of id_user to display
 * @param	boolean		$period		allow period selection, first step of vacation request
 */
function user_viewVacationCalendar($users, $period = false) {

	global $babBody, $babDB;
	$acclevel = bab_vacationsAccess();

	if (empty($acclevel['manager'])) {

		$calendar_entities = array();
		$res = $babDB->db_query("SELECT id_entity FROM ".BAB_VAC_PLANNING_TBL." WHERE id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID']));
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$calendar_entities[$arr['id_entity']] = $arr['id_entity'];
			$tmp = & bab_OCGetChildsEntities($arr['id_entity']);

			foreach($tmp as $entity) {
				$calendar_entities[$entity['id']] = $entity['id'];
			}
		}

		foreach($users as $uid) {
			if (!bab_IsUserUnderSuperior($uid)) {

				// trouver les entites du user et verifier si au moins une est autorisee pour l'affichage du planning
				$arr = & bab_OCGetUserEntities($uid);
				$user_entities = array_merge($arr['superior'], $arr['temporary'], $arr['members']);
				$allowed = false;
				foreach($user_entities as $entity) {
					if (isset($calendar_entities[$entity['id']])) {
						$allowed = true;
					}
				}

				if (!$allowed) {
					$babBody->addError(bab_translate('Access denied'));
					return;
				}
			}
		}
	}


	viewVacationCalendar($users, $period);
}





/* main */
$acclevel = bab_vacationsAccess();
$userentities = & bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
bab_addCoManagerEntities($userentities, $GLOBALS['BAB_SESS_USERID']);
$entities_access = count($userentities['superior']);

if( count($acclevel) == 0)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

$idx = bab_rp('idx', "lvreq");

if (isset($_POST['action']))
{
switch ($_POST['action'])
	{
	case 'period':
		if (!test_period())
			$idx = 'period';
		break;

	case 'vacation_request':
		if (bab_isRequestEditable($_POST['id']))
		{
		if(!bab_vac_saveVacation::save()) {
			$idx = "vunew";
			}
		elseif ($_POST['id_user'] == $GLOBALS['BAB_SESS_USERID'])
			{
			header("Location: ". $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			exit;
			}
		else
			{
			if (isset($_POST['ide'])) {
				header("Location: ". $GLOBALS['babUrlScript'].'?tg=vacchart&idx=entity_members&ide='.bab_pp('ide'));
				exit;
			}

			header("Location: ". $GLOBALS['babUrlScript']."?tg=vacadm&idx=lper");
			exit;
			}
		}
		break;

	case 'delete_request':
		$id_user = bab_isRequestEditable(bab_pp('id_entry'));
		if ($id_user || $acclevel['manager'])
		{
			bab_vac_delete_request(bab_pp('id_entry'));
			header("Location: ". bab_pp('url'));
			exit;
		}
		break;
	}
}



$res = $babDB->db_query("

	select ".BAB_VAC_RIGHTS_TBL.".*
	from ".BAB_VAC_RIGHTS_TBL."
	join ".BAB_VAC_USERS_RIGHTS_TBL."
	where ".BAB_VAC_RIGHTS_TBL.".active='Y'
	and ".BAB_VAC_USERS_RIGHTS_TBL.".id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'
	and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right=".BAB_VAC_RIGHTS_TBL.".id
	");
$countt = $babDB->db_num_rows($res);

switch($idx)
	{
	case "cal":
		$users = explode(',',$_REQUEST['idu']);
		user_viewVacationCalendar($users);
		break;

	case "unload":
		vedUnload();
		exit;
		break;

	case "morve":
		bab_viewVacationRequestDetail(bab_rp('id'));
		exit;
		break;

	case "period":
		$babBody->addItemMenu("period", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=period");
		$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
		if( isset($acclevel['manager']) && $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm");

		$babBody->title = bab_translate("Request vacation");

		if (!empty($_REQUEST['id']))
			{
			$id_user = bab_isRequestEditable($_REQUEST['id']);
			if (!$id_user)
				{
				$babBody->msgerror = bab_translate("Access denied");
				}
			else
				{
				user_viewVacationCalendar(array($id_user), true);
				period($id_user, $_REQUEST['id']);
				}
			}
		else
			{
			if (isset($_GET['idu']) && is_numeric($_GET['idu'])) {
				$id_user = $_GET['idu'];
				}
			else
				$id_user = !isset($_REQUEST['id_user']) ? $GLOBALS['BAB_SESS_USERID'] : $_REQUEST['id_user'];

			if (bab_vacRequestCreate($id_user)) {
				user_viewVacationCalendar(array($id_user), true);
				period($id_user);
				}
			}
		if ($entities_access > 0)
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		break;

	case "vunew":
		$babBody->title = bab_translate("Request vacation");
		if( bab_isRequestEditable($_POST['id']) )
			{
			if (isset($_POST['daybegin']))
				{
				$yearbegin = $_POST['year'] + $_POST['yearbegin'] -1;
				$yearend = $_POST['year'] + $_POST['yearend'] -1;
				$begin = $yearbegin.'-'.$_POST['monthbegin'].'-'.$_POST['daybegin'].' '.$_POST['hourbegin'];
				$end = $yearend.'-'.$_POST['monthend'].'-'.$_POST['dayend'].' '.$_POST['hourend'];
				}
			else
				{
				$begin = $_POST['begin'];
				$end = $_POST['end'];
				}

			requestVacation($begin , $end,$_POST['id']);
			if( $countt > 0 )
				$babBody->addItemMenu("vunew", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=vunew");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		else
			{
			$idx = "lvt";
			}
		if( isset($acclevel['manager']) && $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm");
		if ($entities_access > 0)
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		break;

	case 'delete':
		$babBody->title = bab_translate("Delete vacation request");
		deleteVacationRequest(bab_rp('id_entry'));
		break;

	case 'viewrights':
		if (bab_IsUserUnderSuperior($_GET['id_user']) || !empty($acclevel['manager'])) {
			$babBody->setTitle(bab_translate("Balance").' : '.bab_getUserName($_GET['id_user']));

			if (isset($_GET['ide'])) {
				$babBody->addItemMenu("entity_members", bab_translate("Entity members"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entity_members&ide=".$_GET['ide']);
			}

			$babBody->addItemMenu("viewrights", bab_translate("Balance"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			viewrights($_GET['id_user']);
		} else {
			$babBody->msgerror = bab_translate("Access denied");
		}
		break;


	case 'clear':
		$babDB->db_query("TRUNCATE ".BAB_VAC_CALENDAR_TBL);

	case "lvreq":
	default:
		$babBody->title = bab_translate("Vacation requests list");
		if( isset($acclevel['user']) && $acclevel['user'] == true )
			{
			listVacationRequests($GLOBALS['BAB_SESS_USERID']);
			if( $countt > 0 )
				$babBody->addItemMenu("period", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=period");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		else
			{
			$idx = "lvt";
			}
		if( isset($acclevel['manager']) && $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm");

		if ($entities_access > 0)
			$babBody->addItemMenu("entities", bab_translate("Delegate management"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
		break;
	}

if ( bab_isPlanningAccessValid())
{
	$babBody->addItemMenu("planning", bab_translate("Planning"), $GLOBALS['babUrlScript']."?tg=vacchart&idx=planning");
}




$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserVac');
?>
