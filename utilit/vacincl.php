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

function notifyVacationApprovers($id, $users)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail;

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

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));

	$mail = bab_mail();
	if( $mail == false )
		return;

	for( $i=0; $i < count($users); $i++)
		$mail->mailTo(bab_getUserEmail($users[$i]), bab_getUserName($users[$i]));

	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("Vacation request is waiting to be validated"));

	$tempa = new tempa($row);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "newvacation"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	
	}


function notifyOnRequestChange($id)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL;

	class tempb
		{


		function tempb($row)
			{
			global $babDayType, $babDB;
			$this->message = bab_translate("Vacation request has been modified");
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

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));

	$mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo(bab_getUserEmail($row['id_user']), bab_getUserName($row['id_user']));

	$mail->mailFrom($BAB_SESS_EMAIL, $BAB_SESS_USER);
	$mail->mailSubject(bab_translate("Vacation request has been modified"));

	$tempb = new tempb($row);
	$message = $mail->mailTemplate(bab_printTemplate($tempb,"mailinfo.html", "newvacation"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "newvacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}



function bab_getRightsOnPeriod($begin = false, $end = false, $id_user = false)
	{
	$return = array();
	$begin = $begin ? bab_mktime( $begin ) : $begin;
	$end = $end ? bab_mktime( $end ) : $end;

	if (!$id_user) $id_user = $GLOBALS['BAB_SESS_USERID'];

	$db = & $GLOBALS['babDB'];

	$res = $db->db_query("select r.*,r.id idright, rules.*, ur.quantity ur_quantity from ".BAB_VAC_TYPES_TBL." t, ".BAB_VAC_COLL_TYPES_TBL." c, ".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_USERS_RIGHTS_TBL." ur, ".BAB_VAC_PERSONNEL_TBL." p LEFT JOIN ".BAB_VAC_RIGHTS_RULES_TBL." rules ON rules.id_right = r.id where t.id = c.id_type and c.id_coll=p.id_coll AND p.id_user='".$id_user."' AND r.active='Y' and ur.id_user='".$id_user."' and ur.id_right=r.id and r.id_type=t.id GROUP BY r.id  ORDER BY r.description");
	
	while ( $arr = $db->db_fetch_array($res) )
		{
		if (!$begin)
			$begin = bab_mktime($arr['date_begin']);
		if (!$end)
			$end = bab_mktime($arr['date_end']);

		$row = $db->db_fetch_array($db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user='".$id_user."' and e.status='Y' and el.id_type='".$arr['id_type']."' and el.id_entry=e.id"));
				
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
				continue;

			if ($begin < bab_mktime($arr['date_begin']) || $end > bab_mktime($arr['date_end']))
				continue;

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
				
				list($nbdays) = $db->db_fetch_array($db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." el, ".BAB_VAC_ENTRIES_TBL." e where e.id_user='".$id_user."' and e.status='Y' and el.id_type='".$arr['id_type']."' and el.id_entry=e.id ".$req));


				$access = false;

				if ( $arr['trigger_nbdays_min'] <= $nbdays && $nbdays <= $arr['trigger_nbdays_max'] )
					$access = true;
				}
			
			}

		
		
		if ( $access )
			$return[] = array(
						'id' =>			$arr['idright'],
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
		var $emptylines = false;


		function temp($users, $period)
			{

			$month = isset($_REQUEST['month']) ? $_REQUEST['month'] : Date("n");
			$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : Date("Y");

			global $babMonths;
			$this->db = &$GLOBALS['babDB'];
			$this->month = $month;
			$this->year = $year;

			$this->idusers = $users;
			$this->nbusers = count($this->idusers);
			$this->firstuser = bab_getUserName($this->idusers[0]);
			
			$this->period = $period;
			$this->vacwaitingtxt = bab_translate("Waiting vacation request");
			$this->vacapprovedtxt = bab_translate("Approved vacation request");
			$this->print = bab_translate("Print");
			$this->close = bab_translate("Close");

			$this->t_previousmonth = bab_translate("Previous month");
			$this->t_previousyear = bab_translate("Previous year");
			$this->t_nextmonth = bab_translate("Next month");
			$this->t_nextyear = bab_translate("Next year");

			$this->t_nonworking = bab_translate("Non-working day");
			$this->t_weekend = bab_translate("Week-end");
			
			$id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

			$urltmp = $GLOBALS['babUrlScript']."?tg=".$_REQUEST['tg']."&idx=".$_REQUEST['idx']."&id=".$id;
			if (!empty($_REQUEST['popup']))
				{
				$urltmp .= '&popup=1';
				$this->popup = true;
				}

			if (!empty($_REQUEST['emptylines']))
				{
				$urltmp .= '&emptylines=1';
				$this->emptylines = true;
				}

			if (isset($_REQUEST['ide']))
				{
				$urltmp .= '&ide='.$_REQUEST['ide'];
				}
			else
				{
				$urltmp .= '&idu='.implode(',',$this->idusers);
				}
			$this->previousmonth = $urltmp."&month=".date("n", mktime( 0,0,0, $month-1, 1, $year));
			$this->previousmonth .= "&year=".date("Y", mktime( 0,0,0, $month-1, 1, $year));
			$this->nextmonth = $urltmp."&month=". date("n", mktime( 0,0,0, $month+1, 1, $year));
			$this->nextmonth .= "&year=". date("Y", mktime( 0,0,0, $month+1, 1, $year));

			$this->previousyear = $urltmp."&month=".date("n", mktime( 0,0,0, $month, 1, $year-1));
			$this->previousyear .= "&year=".date("Y", mktime( 0,0,0, $month, 1, $year-1));
			$this->nextyear = $urltmp."&month=". date("n", mktime( 0,0,0, $month, 1, $year+1));
			$this->nextyear .= "&year=". date("Y", mktime( 0,0,0, $month, 1, $year+1));

			if( $month != 1 )
				{
				$dateb = $year."-".$month."-01";
				$datee = ($year+1)."-".date("n", mktime( 0,0,0, $month + 11, 1, $year))."-01";
				$this->yearname = ($year)."-".($year+1);
				}
			else
				{
				$dateb = $year."-01-01";
				$datee = $year."-12-01";
				$this->yearname = $year;
				}


			$res = $this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id_user IN(".implode(',',$this->idusers).") and status!='N' and (date_end >= '".$dateb."' OR date_begin <='".$datee."')");

			while( $row = $this->db->db_fetch_array($res))
				{
				$colors = array();
				$req = "select e.quantity,t.color from ".BAB_VAC_ENTRIES_ELEM_TBL." e,".BAB_VAC_RIGHTS_TBL." r, ".BAB_VAC_TYPES_TBL." t  where e.id_entry='".$row['id']."' AND r.id=e.id_type AND t.id=r.id_type";

				$res2 = $this->db->db_query($req);
				while ($arr = $this->db->db_fetch_array($res2))
					{
					for ($i = 0 ; $i < $arr['quantity'] ; $i++)
						$colors[] = $arr['color'];
					}

				if (!$this->period || !isset($_REQUEST['id']) || $_REQUEST['id'] != $row['id'])
					{

					$this->entries[] = array(
										'id'=> $row['id'],
										'id_user' => $row['id_user'],
										'db'=> $row['date_begin'],
										'de'=> $row['date_end'],
										'st' => $row['status'],
										'color' => $colors
										);
					}
				}

			$this->workdays = & explode(',',$GLOBALS['babBody']->icalendars->workdays);

			include_once $GLOBALS['babInstallPath']."utilit/nwdaysincl.php";

			$this->nonWorkingDays = array_merge(bab_getNonWorkingDays($year), bab_getNonWorkingDays($year+1));

			$this->restypes = $this->db->db_query("SELECT t.* FROM ".BAB_VAC_TYPES_TBL." t, ".BAB_VAC_COLL_TYPES_TBL." ct, ".BAB_VAC_PERSONNEL_TBL." p WHERE p.id_user IN(".implode(',', $this->idusers).") AND p.id_coll=ct.id_coll AND ct.id_type=t.id GROUP BY t.id");

			$this->counttypes = $this->db->db_num_rows($this->restypes);
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
				if ($this->emptylines)
					{
					$this->id_user = $this->idusers[$i];
					$this->username = bab_getUserName($this->id_user);
					}
				elseif (isset($this->month_users[$i]))
					{
					$this->id_user = $this->month_users[$i];
					$this->username = bab_getUserName($this->id_user);
					}
				else
					{
					$this->id_user = 0;
					$this->username = '';
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

		function getmonth()
			{
			static $i = 0;
			if( $i < 12)
				{
				$this->curyear = date("Y", mktime( 0,0,0, $this->month + $i, 1, $this->year));
				$this->curmonth = date("n", mktime( 0,0,0, $this->month + $i, 1, $this->year));
				$this->monthname = $GLOBALS['babShortMonths'][$this->curmonth];
				$this->totaldays = date("t", mktime(0,0,0,$this->month + $i,1,$this->year));

				$startmonth = sprintf("%04d-%02d-%02d", $this->curyear, $this->curmonth, 1);
				$endmonth = sprintf("%04d-%02d-%02d", $this->curyear, $this->curmonth, $this->totaldays); 

				$this->month_users = array();
				$this->month_entries = array();

				for( $k=0; $k < count($this->entries); $k++)
					{
					if( $startmonth <= $this->entries[$k]['db'] && $endmonth >= $this->entries[$k]['db'] || $startmonth <= $this->entries[$k]['de'] && $endmonth >= $this->entries[$k]['de'] )
						{
						$this->month_entries[] = $k;
						if (!in_array($this->entries[$k]['id_user'],$this->month_users))
							$this->month_users[] = $this->entries[$k]['id_user'];
						}
					}
				$this->nb_month_users = count($this->month_users);
				

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
					$curdate = mktime(0,0,0,$this->curmonth,$d,$this->curyear);
					$dayweek = date("w", $curdate);
					$this->titledate = bab_longdate($curdate,false);
					$this->date = sprintf("%04d-%02d-%02d", $this->curyear, $this->curmonth, $d);
					$this->weekend = !in_array($dayweek, $this->workdays);
					$this->nonworking = isset($this->nonWorkingDays[$this->date]);
					$this->nonworking_text = $this->nonworking ? $this->nonWorkingDays[$this->date] : '';
					$this->bvac = false;
					$this->bwait = false;

					foreach( $this->month_entries as $k)
						{
						if( $this->date >= $this->entries[$k]['db'] && $this->date <= $this->entries[$k]['de'] && $this->entries[$k]['id_user'] == $this->id_user )
							{

							if( $this->entries[$k]['st'] == "")
								$this->bwait = true;
							else
								$this->bvac = true;

							if (!$this->nonworking && !$this->weekend)
								{
								$this->color = current($this->entries[$k]['color']);
								unset($this->entries[$k]['color'][key($this->entries[$k]['color'])]);
								}

							break;
							}
						}
					$this->noday = false;
					}
				else
					{
					$this->noday = true;
					$this->daynumbername = "";
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
			static $i =1;
			if ($this->arr = $this->db->db_fetch_array($this->restypes))
				{
				$this->newline = $i % 2 == 0 && $this->counttypes > $i-1;
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function printhtml()
			{
			$html = & bab_printTemplate($this,"vacuser.html", "calendarbyuser");

			if (isset($_REQUEST['popup']) && $_REQUEST['popup'] == 1)
				{
				include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
				$GLOBALS['babBodyPopup'] = new babBodyPopup();
				$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
				$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
				$GLOBALS['babBodyPopup']->babecho($html);
				printBabBodyPopup();
				die();
				}
			else
				{
				$GLOBALS['babBody']->babecho($html);
				}
			}

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
			$this->t_edit = bab_translate("Edit");
			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$id_user."&popup=1";
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->personal = $id_user == $GLOBALS['BAB_SESS_USERID'];
			$this->pos = isset($_REQUEST['pos']) ? $_REQUEST['pos'] : 0;
			$this->db = $GLOBALS['babDB'];
			$req = "".BAB_VAC_ENTRIES_TBL." where id_user='".$id_user."'";

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{
				$tmpurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq&pos=";
				if( $this->pos > 0)
					{
					$this->topurl = $tmpurl."0";
					$this->topname = "&lt;&lt;";
					}

				$next = $this->pos - VAC_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $tmpurl.$next;
					$this->prevname = "&lt;";
					}

				$next = $this->pos + VAC_MAX_REQUESTS_LIST;
				if( $next < $total)
					{
					$this->nexturl = $tmpurl.$next;
					$this->nextname = "&gt;";
					if( $next + VAC_MAX_REQUESTS_LIST < $total)
						{
						$bottom = $total - VAC_MAX_REQUESTS_LIST;
						}
					else
						$bottom = $next;
					$this->bottomurl = $tmpurl.$bottom;
					$this->bottomname = "&gt;&gt;";
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
			global $babDayType;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacuser&idx=morve&id=".$arr['id'];
				list($this->quantity) = $this->db->db_fetch_row($this->db->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$arr['id']."'"));
				$this->urlname = bab_getUserName($arr['id_user']);

				$begin_ts = bab_mktime($arr['date_begin']." 00:00:00");
				$end_ts = bab_mktime($arr['date_end']." 00:00:00");

				$this->begindate = bab_shortDate($begin_ts, false);
				if( $arr['day_begin'] != 1)
					$this->begindate .= " ". $babDayType[$arr['day_begin']];

				$this->enddate = bab_shortDate($end_ts, false);
				if( $arr['day_begin'] != 1)
					$this->enddate .= " ". $babDayType[$arr['day_end']];

				$this->urledit = $GLOBALS['babUrlScript']."?tg=vacuser&idx=period&id=".$arr['id']."&year=".date('Y',$begin_ts)."&month=".date('n',$begin_ts);

				switch($arr['status'])
					{
					case 'Y':
						$this->status = $this->statarr[1];
						$this->modify = !$this->personal;
						break;
					case 'N':
						$this->status = $this->statarr[2];
						$this->modify = false;
						break;
					default:
						$this->status = $this->statarr[0];
						$this->modify = $this->personal;
						break;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id_user);
	$babBody->babecho(	bab_printTemplate($temp, "vacuser.html", "vrequestslist"));
	return $temp->count;
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

?>