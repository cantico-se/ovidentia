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
include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/vacincl.php";


function bab_isRequestEditable($id)
	{
	if ($id == 0)
		{
		return true;
		}
	$db = & $GLOBALS['babDB'];
	list($id_user,$status) = $db->db_fetch_array($db->db_query("SELECT id_user,status FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$id."'"));

	

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


function requestVacation($begin,$end, $halfdaybegin, $halfdayend, $id)
	{
	global $babBody;
	class temp
		{
		

		function temp($begin,$end, $halfdaybegin, $halfdayend, $id)
			{
			global $babBody;
			$this->datebegintxt = bab_translate("Begin date");
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
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->t_delete = bab_translate("Delete");
			$this->t_delete_confirm = bab_translate("Are you sure you want to remove this request");
			$this->totalval = 0;
			$this->maxallowed = 0;
			$this->id = $id;
			$this->id_user = $_POST['id_user'];
			$this->username = bab_getUserName($this->id_user);
			$this->t_period = bab_translate("The period contain");
			$this->t_days = bab_translate("working days");
			$this->period_nbdays = $_POST['period_nbdays'];


			$this->db = & $GLOBALS['babDB'];

			list($yearbegin, $monthbegin, $daybegin) = explode('-',$begin);
			list($yearend, $monthend, $dayend) = explode('-',$end);

			$begin = mktime(0, 0, 0, $monthbegin, $daybegin, $yearbegin );
			$end = mktime(0, 0, 0, $monthend, $dayend, $yearend);

			$calcul = round(($end - $begin)/86400) + 1;
			if (empty($this->period_nbdays) || ($this->period_nbdays == 1 && $calcul > 1))
				{
				$this->period_nbdays = $calcul;
				$this->t_days = bab_translate("Day(s)");
				}

			$this->period_nbdays2 = $this->period_nbdays;

			$this->begin = $yearbegin.'-'.$monthbegin.'-'.$daybegin;
			$this->end = $yearend.'-'.$monthend.'-'.$dayend;


			$this->halfdaybegin = $halfdaybegin;
			$this->halfdayend = $halfdayend;

			$this->rights = bab_getRightsOnPeriod($this->begin, $this->end, $this->id_user);

			$this->recorded = array();
			if (!empty($this->id))
				{
				$res = $this->db->db_query("SELECT * FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry='".$this->id."'");
				while($arr = $this->db->db_fetch_array($res))
					{
					$this->recorded[$arr['id_type']] = $arr['quantity'];
					}
				}

			$this->datebegin = bab_longdate($begin,false);
			$this->dateend = bab_longdate($end,false);

			$this->remarks = isset($_POST['remarks']) ? stripslashes($_POST['remarks']) : '';
			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$this->id_user."&popup=1";

			

			}


		function getnextright()
			{

			if (list(,$this->right) = each($this->rights))
				{
				$this->right['quantitydays'] = $this->right['quantitydays'] - $this->right['waiting'];
				if (isset($_POST['nbdays'.$this->right['id']]))
					{
					$this->nbdays = $_POST['nbdays'.$this->right['id']];
					}
				elseif( count($this->recorded) > 0)
					{
					if (isset($this->recorded[$this->right['id']]))
						$this->nbdays = $this->recorded[$this->right['id']];
					else
						$this->nbdays = 0;
					}
				elseif ( $this->period_nbdays2 > 0 && $this->right['quantitydays'] > 0)
					{
					if ($this->period_nbdays2 >= $this->right['quantitydays'])
						{
						$this->nbdays = $this->right['quantitydays'];
						$this->period_nbdays2 -= $this->right['quantitydays'];
						}
					elseif ($this->right['quantitydays'] > 0)
						{
						$this->nbdays = $this->period_nbdays2;
						$this->period_nbdays2 = 0;
						}
					}
				else
					{
					$this->nbdays = 0;
					}
				$this->totalval += $this->nbdays;
				return true;
				}
			else
				return false;

			}

		}

	$temp = new temp($begin,$end, $halfdaybegin, $halfdayend, $id);
	$babBody->babecho(	bab_printTemplate($temp,"vacuser.html", "newvacation"));
	}



function period($id_user, $id = 0)
	{
	class ptemp
		{


		function ptemp($id_user, $id)
			{
			global $babBody;
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
			$this->invalidentry2 = bab_translate("Days must be multiple of 0.5");
			$this->invalidentry3 = bab_translate("The number of days exceed the total allowed");
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->t_total = bab_translate("Total");
			$this->t_avariable_days = bab_translate("Avariable days");
			$this->t_waiting_days = bab_translate("Waiting days");
			$this->t_period_nbdays = bab_translate("Period days");

			$this->totalval = 0;
			$this->maxallowed = 0;
			$this->db = & $GLOBALS['babDB'];
			$this->id = $id;
			$this->id_user = $id_user;

			$this->year = isset($_REQUEST['year']) ? $_REQUEST['year'] : date('Y');

			if (!empty($this->id) && !isset($_POST['daybegin']))
				{
				$res = $this->db->db_query("SELECT * FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$this->id."'");
				$arr = $this->db->db_fetch_array($res);
				list($this->yearbegin, $this->monthbegin, $this->daybegin) = explode('-',$arr['date_begin']);
				list($this->yearend, $this->monthend, $this->dayend) = explode('-',$arr['date_end']);
				$this->halfdaybegin = $arr['day_begin'];
				$this->halfdayend = $arr['day_end'];
				}
			elseif (isset($_POST['daybegin']))
				{
				$this->daybegin = $_POST['daybegin'];
				$this->dayend = $_POST['dayend'];
				$this->monthbegin = $_POST['monthbegin'];
				$this->monthend = $_POST['monthend'];
				$this->yearbegin = $this->year+ $_POST['yearbegin']-1;
				$this->yearend = $this->year+ $_POST['yearend']-1;
				$this->halfdaybegin = $_POST['halfdaybegin'];
				$this->halfdayend = $_POST['halfdayend'];
				}
			else
				{
				$this->daybegin = date("j");
				$this->dayend = date("j");
				$this->monthbegin = date("n");
				$this->monthend = date("n");
				$this->yearbegin = $this->year;
				$this->yearend = $this->year;
				$this->halfdaybegin = 1;
				$this->halfdayend = 1;
				}

			$this->halfdaysel = $this->halfdaybegin;

			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=cal&idu=".$id_user;

			$this->rights = bab_getRightsOnPeriod();
			$this->total = 0;
			$this->total_waiting = 0;
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
			global $babMonths;
			static $i = 1;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
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
		function getnexthalf()
			{
			global $babDayType;
			static $i = 1;
			static $count = 4;
			if( $i < $count)
				{
				$this->halfname = $babDayType[$i];
				$this->halfid = $i;
				if( $this->halfdaysel == $this->halfid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				$this->halfdaysel = $this->halfdayend;
				return false;
				}

			}

		function getnextright()
			{
			if ($this->right = & current($this->rights))
				{
				next($this->rights);
				$this->right['quantitydays'] = $this->right['quantitydays'] - $this->right['waiting'];
				$this->total += $this->right['quantitydays'];
				$this->total_waiting += $this->right['waiting'];
				return true;
				}
			else
				return false;

			}

		}
		$temp = new ptemp($id_user, $id);
		$GLOBALS['babBody']->babecho(bab_printTemplate($temp, "vacuser.html", "period"));
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
	echo bab_printTemplate($temp,"vacuser.html", "vedunload");
	}

function addNewVacation($id_user, $id_request, $begin,$end, $halfdaybegin, $halfdayend, $remarks, $total)
{
	global $babBody, $babDB;
	$nbdays = array();


	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$id_user."'"));

	$rights = bab_getRightsOnPeriod($begin, $end, $id_user);

	$ntotal = 0;
	foreach($rights as $arr)
	{

		if( isset($_POST['nbdays'.$arr['id']]))
		{
			$nbd = $_POST['nbdays'.$arr['id']];
			if( !is_numeric($nbd) || $nbd < 0 )
				{
				$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
				return false;
				}

			if (!empty($nbd) && $arr['cbalance'] != 'Y' && ($arr['quantitydays'] - $arr['waiting'] - $nbd) < 0)
				{
				$babBody->msgerror = bab_translate("You can't take more than").' '.($arr['quantitydays']- $arr['waiting']).' '.bab_translate("days on the right").' '.$arr['description'];
				return false;
				}
			
			if( $nbd > 0 )
			{
				$nbdays['id'][] = $arr['id'];
				$nbdays['val'][] = $nbd;
				$ntotal += $nbd;
			}
		}
	}

	if( $ntotal <= 0 || $ntotal != $total)
		{
		$babBody->msgerror = bab_translate("Incorrect total number of days") ." !";
		return false;
		}

	$arr = explode('-',$begin);
	$ts_begin = mktime(0, 0, 0, $arr[1], $arr[2] , $arr[0]);

	$arr = explode('-',$end);
	$ts_end = mktime(0, 0, 0, $arr[1], $arr[2] , $arr[0]);

	if( $ts_begin > $ts_end || ( $begin == $end && $halfdaybegin != $halfdayend ))
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$remarks = addslashes($remarks);
		}

	if (empty($id_request))
		{
		$babDB->db_query("insert into ".BAB_VAC_ENTRIES_TBL." (id_user, date_begin, date_end, day_begin, day_end, comment, date, idfai) values  ('" .$id_user. "', '" . $begin. "', '" . $end. "', '" . $halfdaybegin. "', '" . $halfdayend. "', '" . $remarks. "', curdate(), '0')");
		$id = $babDB->db_insert_id();
		}
	else
		{
		$babDB->db_query("DELETE FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry='".$id_request."'");

		list($idfai) = $babDB->db_fetch_array($babDB->db_query("SELECT idfai FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$id_request."'"));
		
		if ($idfai > 0)
			deleteFlowInstance($idfai);

		$babDB->db_query("UPDATE ".BAB_VAC_ENTRIES_TBL." SET date_begin =  '".$begin."', date_end = '".$end."', day_begin = '" . $halfdaybegin. "', day_end = '" . $halfdayend. "', comment = '" . $remarks. "', date = curdate(), idfai = '0' WHERE id='".$id_request."'");

		$id = $id_request;
		}

	for( $i = 0; $i < count($nbdays['id']); $i++)
		{
		$babDB->db_query("insert into ".BAB_VAC_ENTRIES_ELEM_TBL." (id_entry, id_type, quantity) values  ('" .$id. "', '" .$nbdays['id'][$i]. "', '" . $nbdays['val'][$i]. "')");
		}

	if ($id_user == $GLOBALS['BAB_SESS_USERID'])
		{
		$idfai = makeFlowInstance($row['id_sa'], "vac-".$id);
		$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set idfai='".$idfai."',status='' where id='".$id."'");
		$nfusers = getWaitingApproversFlowInstance($idfai, true);
		notifyVacationApprovers($id, $nfusers);
		}
	else
		{
		notifyOnRequestChange($id);
		}
	return true;
}




function viewVacationRequestDetail($id)
	{
	global $babBody;

	class temp
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


		function temp($id)
			{
			global $babDayType;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->nbdaystxt = bab_translate("Quantities");
			$this->totaltxt = bab_translate("Total");
			$this->statustxt = bab_translate("Status");
			$this->commenttxt = bab_translate("Description");
			$this->remarktxt = bab_translate("Comment");
			$this->db = $GLOBALS['babDB'];
			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false);
			$this->halfnamebegin = $babDayType[$row['day_begin']];
			$this->dateend = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false);
			$this->halfnameend = $babDayType[$row['day_end']];
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

			$req = "select * from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$id."'";
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
				list($this->typename) = $this->db->db_fetch_row($this->db->db_query("select description from ".BAB_VAC_RIGHTS_TBL." where id ='".$arr['id_type']."'"));
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

	$temp = new temp($id);
	echo bab_printTemplate($temp, "vacuser.html", "ventrydetail");
	return $temp->count;
	}

function test_period()
{
global $babBody;
$db = & $GLOBALS['babDB'];

if (!isset($_POST['daybegin']) || 
	!isset($_POST['monthbegin']) ||
	!isset($_POST['yearbegin']) || 
	!isset($_POST['halfdaybegin']) ||
	!isset($_POST['dayend']) || 
	!isset($_POST['monthend']) ||
	!isset($_POST['yearend']) || 
	!isset($_POST['halfdayend']) ||
	!isset($_POST['id_user'])
	)
	{
	$babBody->msgerror = bab_translate("Error");
	return false;
	}

	$yearbegin = date("Y") + $_POST['yearbegin'] - 1;
	$yearend = date("Y") + $_POST['yearend'] - 1;

	$begin = mktime( 0,0,0,$_POST['monthbegin'], $_POST['daybegin'], $yearbegin);
	$end = mktime( 0,0,0,$_POST['monthend'], $_POST['dayend'], $yearend);

	if( $begin > $end || ( $begin == $end && $_POST['halfdaybegin'] != $_POST['halfdayend'] ))
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	$date_begin = sprintf("%04d-%02d-%02d", $yearbegin, $_POST['monthbegin'], $_POST['daybegin']);
	$date_end = sprintf("%04d-%02d-%02d", $yearend, $_POST['monthend'], $_POST['dayend']);

	$id_entry = isset($_POST['id']) ? $_POST['id'] : 0;

	$res = $db->db_query("SELECT * FROM ".BAB_VAC_ENTRIES_TBL." WHERE id_user='".$_POST['id_user']."' AND ((date_begin BETWEEN '".$date_begin."' AND '".$date_end."' ) OR ( date_end BETWEEN '".$date_begin."' AND '".$date_end."')) AND id <> '".$id_entry."' AND status<>'N'");

	if ($db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: a request is allready defined on this period");
		return false;
		}

return true;
}


function delete_request($id_request)
{
	notifyOnRequestChange($id_request, true);

	$db = &$GLOBALS['babDB'];
	$db->db_query("DELETE FROM ".BAB_VAC_ENTRIES_ELEM_TBL." WHERE id_entry='".$id_request."'");

	list($idfai) = $db->db_fetch_array($db->db_query("SELECT idfai FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$id_request."'"));
	
	if ($idfai > 0)
		deleteFlowInstance($idfai);

	$db->db_query("DELETE FROM ".BAB_VAC_ENTRIES_TBL." WHERE id='".$id_request."'");
}

/* main */
$acclevel = bab_vacationsAccess();
$userentities = & bab_OCGetUserEntities($GLOBALS['BAB_SESS_USERID']);
$entities_access = count($userentities['superior']);

if( count($acclevel) == 0)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx))
	$idx = "lvreq";

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
		if(!addNewVacation($_POST['id_user'], $_POST['id'], $begin, $end, $halfdaybegin, $halfdayend, $remarks, $total))
			$idx = "vunew";
		elseif ($_POST['id_user'] == $GLOBALS['BAB_SESS_USERID'])
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			exit;
			}
		else
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
			}
		}
		break;

	case 'delete_request':
		$id_user = bab_isRequestEditable($_POST['id']);
		if ($id_user)
		{
			delete_request($_POST['id']);
			if ($id_user == $GLOBALS['BAB_SESS_USERID'])
				$idx = 'lvreq';
			else
				{
				Header("Location: ". $GLOBALS['babUrlScript']."?tg=vacchart&idx=entities");
				}
		}
		break;
	}
}



$res = $babDB->db_query("select ".BAB_VAC_RIGHTS_TBL.".* from ".BAB_VAC_RIGHTS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_VAC_RIGHTS_TBL.".active='Y' and ".BAB_VAC_USERS_RIGHTS_TBL.".id_user='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right=".BAB_VAC_RIGHTS_TBL.".id");
$countt = $babDB->db_num_rows($res);

switch($idx)
	{
	case "cal":
		viewVacationCalendar( explode(',',$_REQUEST['idu']));
		break;

	case "unload":
		vedUnload();
		exit;
		break;

	case "morve":
		viewVacationRequestDetail($id);
		exit;
		break;

	case "period":
		$babBody->addItemMenu("period", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=period");
		$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");

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
				viewVacationCalendar(array($id_user), true);
				period($id_user, $_REQUEST['id']);
				}
			}
		else
			{
			viewVacationCalendar(array($GLOBALS['BAB_SESS_USERID']), true);
			period($GLOBALS['BAB_SESS_USERID']);
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
				$begin = $yearbegin.'-'.$_POST['monthbegin'].'-'.$_POST['daybegin'];
				$end = $yearend.'-'.$_POST['monthend'].'-'.$_POST['dayend'];
				}
			else
				{
				$begin = $_POST['begin'];
				$end = $_POST['end'];
				}

			requestVacation($begin , $end, $_POST['halfdaybegin'], $_POST['halfdayend'],$_POST['id']);
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
$babBody->setCurrentItemMenu($idx);

?>
