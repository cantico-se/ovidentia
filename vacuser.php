<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/vacincl.php";

define("VAC_MAX_REQUESTS_LIST", 20);

function notifyVacationAuthor($id, $subject)
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
			var $bview;
			var $by;
			var $reason;
			var $reasontxt;


			function tempa($row, $subject)
				{
				global $babDayType;
				$this->message = $subject;
				$this->fromuser = bab_translate("User");
				$this->from = bab_translate("from");
				$this->until = bab_translate("until");
				$this->begindate = bab_strftime(bab_mktime($row['date_begin']), false). " ". bab_translate($babDayType[$row['day_begin']]);
				$this->enddate = bab_strftime(bab_mktime($row['date_end']), false). " ". bab_translate($babDayType[$row['day_end']]);
				$this->reasontxt = bab_translate("Additional information");
				$this->reason = nl2br($row['comment2']);
				if( $row['status'] == 'N')
					{
					$this->by = bab_translate("By");
					$this->username = bab_getUserName($row['id_approver']);
					$this->bview = true;
					}
				else
					{
					$this->bview = false;
					}
				}
			}
		}
	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));

	$mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo(bab_getUserEmail($row['id_user']), bab_getUserName($row['id_user']));

	$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
	$mail->mailSubject($subject);

	$tempa = new tempa($row, $subject);
	$message = bab_printTemplate($tempa,"mailinfo.html", "infovacation");
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "infovacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}

function requestVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $nbdays, $remarks)
	{
	global $babBody;
	class temp
		{
		var $datebegin;
		var $dateend;
		var $vactype;
		var $addvac;

		var $daybegin;
		var $daybeginid;
		var $monthbegin;
		var $monthbeginid;
		var $nbdaystxt;
		var $nbdays;
		var $invaliddate;

		var $remark;
		var $yearbegin;

		var $db;
		var $res;
		var $count;
		
		var $daybegin;
		var $monthbegin;
		var $yearbegin;
		var $dayend;
		var $monthend;
		var $yearend;
		var $halfdaybegin;
		var $halfdayend;
		var $nbdays;
		var $remarks;

		var $daysel;
		var $monthsel;
		var $yearsel;
		var $halfdaysel;
		var $totaltxt;
		var $totalval;

		var $calurl;
		var $calendar;

		function temp($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $nbdays, $remarks)
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
			$this->totaltxt = bab_translate("Total");
			$this->balancetxt = bab_translate("Balance");
			$this->calendar = bab_translate("Planning");
			$this->db = $GLOBALS['babDB'];
			if( $daybegin ==  "" )
				$this->daybegin = date("j");
			else
				$this->daybegin = $daybegin;
			$this->daysel = $this->daybegin;

			if( $dayend ==  "" )
				$this->dayend = date("j");
			else
				$this->dayend = $dayend;

			if( $monthbegin ==  "" )
				$this->monthbegin = date("n");
			else
				$this->monthbegin = $monthbegin;
			$this->monthsel = $this->monthbegin;

			if( $monthend ==  "" )
				$this->monthend = date("n");
			else
				$this->monthend = $monthend;

			if( $yearbegin ==  "" )
				$this->yearbegin = date("Y");
			else
				$this->yearbegin = date("Y")+ $yearbegin-1;

			$this->yearsel = $this->yearbegin - date("Y") + 1;

			if( $yearend ==  "" )
				$this->yearend = date("Y");
			else
				$this->yearend = date("Y")+ $yearend-1;

			if( $halfdaybegin ==  "" )
				$this->halfdaybegin = 1;
			else
				$this->halfdaybegin = $halfdaybegin;
			$this->halfdaysel = $this->halfdaybegin;

			if( $halfdayend ==  "" )
				$this->halfdayend = 1;
			else
				$this->halfdayend = $halfdayend;

			if( $remarks !=  "" )
				$this->remarks = stripslashes($remarks);
			
			$arr = $this->db->db_fetch_array($this->db->db_query("select id_coll from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'"));

			$this->res = $this->db->db_query("select ".BAB_VAC_TYPES_TBL.".* from ".BAB_VAC_TYPES_TBL." join ".BAB_VAC_COLL_TYPES_TBL." where ".BAB_VAC_TYPES_TBL.".id = ".BAB_VAC_COLL_TYPES_TBL.".id_type and ".BAB_VAC_COLL_TYPES_TBL.".id_coll='".$arr['id_coll']."'");
			$this->count = $this->db->db_num_rows($this->res);

			$this->calurl = $GLOBALS['babUrlScript']."?tg=vacadm&idx=cal&idu=".$GLOBALS['BAB_SESS_USERID'];
			}


		function getnexttype()
			{
			static $i = 0;

			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);

				$this->rest = $this->db->db_query("select ".BAB_VAC_RIGHTS_TBL.".* from ".BAB_VAC_RIGHTS_TBL." join ".BAB_VAC_USERS_RIGHTS_TBL." where ".BAB_VAC_RIGHTS_TBL.".active='Y' and ".BAB_VAC_USERS_RIGHTS_TBL.".id_user='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_VAC_USERS_RIGHTS_TBL.".id_right=".BAB_VAC_RIGHTS_TBL.".id and ".BAB_VAC_RIGHTS_TBL.".id_type='".$arr['id']."'");
				$this->countt = $this->db->db_num_rows($this->rest);

				$i++;
				return true;
				}
			else
				{
				$this->daysel = $this->dayend;
				return false;
				}

			}

		function getnextright()
			{
			static $i = 0;

			if( $i < $this->countt)
				{
				$arr = $this->db->db_fetch_array($this->rest);
				$this->typename = $arr['description'];
				//$this->maxdays = $arr['maxdays'];
				$this->nbdaysname = "nbdays".$arr['id'];

				$row = $this->db->db_fetch_array($this->db->db_query("select sum(quantity) as total from ".BAB_VAC_ENTRIES_ELEM_TBL." join ".BAB_VAC_ENTRIES_TBL." where ".BAB_VAC_ENTRIES_TBL.".id_user='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_VAC_ENTRIES_TBL.".status!='N' and ".BAB_VAC_ENTRIES_ELEM_TBL.".id_type='".$arr['id']."' and ".BAB_VAC_ENTRIES_ELEM_TBL.".id_entry=".BAB_VAC_ENTRIES_TBL.".id"));
				$qdp = isset($row['total'])? $row['total'] : 0;

				$this->quantitydays = $arr['quantity'] - $qdp;

				if( isset($GLOBALS[$this->nbdaysname]))
					{
					$this->nbdays = $GLOBALS[$this->nbdaysname];
					}
				else
					{
					$this->nbdays = 0;
					}
				$this->totalval += $this->nbdays;
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}


		function getnextday()
			{
			static $i = 1;

			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( $this->daysel == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				$this->daysel = $this->dayend;
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
				if( $this->monthsel == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				$this->monthsel = $this->monthend;
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
				$this->yearidval = date("Y") + $i;
				if( $this->yearsel == $this->yearid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				$this->yearsel = $this->yearend - date("Y") + 1;
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

		}

	$temp = new temp($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $nbdays, $remarks);
	$babBody->babecho(	bab_printTemplate($temp,"vacuser.html", "newvacation"));
	}

function listWaitingVacation()
{
	global $babBody;

	class temp
		{
		var $nametxt;
		var $urlname;
		var $url;
		var $datebtxt;
		var $dateb;
		var $dateetxt;
		var $datee;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $total;
		var $totaltxt;
		var $checkall;
		var $uncheckall;


		var $entryid;

		function temp()
			{
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->nametxt = bab_translate("Fullname");
			$this->datebtxt = bab_translate("Begin date");
			$this->dateetxt = bab_translate("End date");
			$this->totaltxt = bab_translate("Quantity");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select ".BAB_VAC_ENTRIES_TBL.".* from ".BAB_VAC_ENTRIES_TBL." join ".BAB_FAR_INSTANCES_TBL." where status='' and ".BAB_FAR_INSTANCES_TBL.".idschi=".BAB_VAC_ENTRIES_TBL.".idfai and ".BAB_FAR_INSTANCES_TBL.".iduser='".$GLOBALS['BAB_SESS_USERID']."' and ".BAB_FAR_INSTANCES_TBL.".result='' and  ".BAB_FAR_INSTANCES_TBL.".notified='Y' order by ".BAB_VAC_ENTRIES_TBL.".date desc");
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=vacuser&idx=morvw&id=".$arr['id'];
				list($this->total) = $this->db->db_fetch_row($this->db->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$arr['id']."'"));
				$this->urlname = bab_getUserName($arr['id_user']);
				$this->dateb = bab_printDate($arr['date_begin']);
				$this->datee = bab_printDate($arr['date_end']);
				$this->entryid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "vacuser.html", "vwaitinglist"));
	return $temp->count;
}

function viewWaitingVacReqDetail($id)
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
		var $remarktxt;
		var $remark;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $veid;

		function temp($id)
			{
			global $babDayType;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->nbdaystxt = bab_translate("Quantities");
			$this->totaltxt = bab_translate("Total");
			$this->commenttxt = bab_translate("Additional information");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->remarktxt = bab_translate("Description");
			$this->db = $GLOBALS['babDB'];
			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']), false);
			$this->halfnamebegin = bab_translate($babDayType[$row['day_begin']]);
			$this->dateend = bab_strftime(bab_mktime($row['date_end']), false);
			$this->halfnameend = bab_translate($babDayType[$row['day_end']]);
			$this->fullname = bab_getUserName($row['id_user']);
			$this->remark = nl2br($row['comment']);

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
		}

	$temp = new temp($id);
	echo bab_printTemplate($temp, "vacuser.html", "vwdetail");
	return $temp->count;

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

function addNewVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $remarks, $total)
{
	global $babBody, $babDB;
	$nbdays = array();

	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'"));

	$res = $babDB->db_query("select ".BAB_VAC_RIGHTS_TBL.".id from ".BAB_VAC_RIGHTS_TBL." join ".BAB_VAC_COLL_TYPES_TBL." where ".BAB_VAC_RIGHTS_TBL.".id_type = ".BAB_VAC_COLL_TYPES_TBL.".id_type and ".BAB_VAC_COLL_TYPES_TBL.".id_coll='".$row['id_coll']."'");

	$ntotal = 0;
	while( $arr = $babDB->db_fetch_array($res))
	{
		$tmp = 'nbdays'.$arr['id'];
		if( isset($GLOBALS[$tmp]))
		{
			$nbd = $GLOBALS[$tmp];
			if( !is_numeric($nbd) || $nbd < 0 )
				{
				$babBody->msgerror = bab_translate("You must specify a correct number days") ." !";
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

	$begin = mktime( 0,0,0,$monthbegin, $daybegin, date("Y") + $yearbegin - 1);
	$end = mktime( 0,0,0,$monthend, $dayend, date("Y") + $yearend - 1);

	if( $begin > $end || ( $begin == $end && $halfdaybegin != $halfdayend ))
		{
		$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$remarks = addslashes($remarks);
		}

	$babDB->db_query("insert into ".BAB_VAC_ENTRIES_TBL." (id_user, date_begin, date_end, day_begin, day_end, comment, date, idfai) values  ('" .$GLOBALS['BAB_SESS_USERID']. "', '" . sprintf("%04d-%02d-%02d", date("Y") + $yearbegin - 1, $monthbegin, $daybegin). "', '" . sprintf("%04d-%02d-%02d", date("Y") + $yearend - 1, $monthend, $dayend). "', '" . $halfdaybegin. "', '" . $halfdayend. "', '" . $remarks. "', curdate(), '0')");
	$id = $babDB->db_insert_id();

	for( $i = 0; $i < count($nbdays['id']); $i++)
		{
		$babDB->db_query("insert into ".BAB_VAC_ENTRIES_ELEM_TBL." (id_entry, id_type, quantity) values  ('" .$id. "', '" .$nbdays['id'][$i]. "', '" . $nbdays['val'][$i]. "')");
		}
	
	$idfai = makeFlowInstance($row['id_sa'], "vac-".$id);
	$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set idfai='".$idfai."' where id='".$id."'");
	$nfusers = getWaitingApproversFlowInstance($idfai, true);
	notifyVacationApprovers($id, $nfusers);
	return true;
}

function confirmVacationRequest($veid, $remarks, $action)
{
	global $babBody, $babDB;

	$res = $babDB->db_query("select idfai from ".BAB_VAC_ENTRIES_TBL." where id='".$veid."'");
	$arr = $babDB->db_fetch_array($res);

	$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $action);

	switch($res)
		{
		case 0:
			deleteFlowInstance($arr['idfai']);
			if( !bab_isMagicQuotesGpcOn())
				{
				$remarks = addslashes($remarks);
				}
			$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set status='N', idfai='0', id_approver='".$GLOBALS['BAB_SESS_USERID']."', comment2='".$remarks."' where id = '".$veid."'");
			$subject = bab_translate("Your vacation request has been refused");
			notifyVacationAuthor($veid, $subject);
			break;
		case 1:
			deleteFlowInstance($arr['idfai']);
			if( !bab_isMagicQuotesGpcOn())
				{
				$remarks = addslashes($remarks);
				}
			$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set status='Y', idfai='0', id_approver='".$GLOBALS['BAB_SESS_USERID']."', comment2='".$remarks."' where id = '".$veid."'");
			$subject = bab_translate("Your vacation request has been accepted");
			notifyVacationAuthor($veid, $subject);
			break;
		default:
			$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
			if( count($nfusers) > 0 )
				notifyVacationApprovers($veid, $nfusers);
			break;
		}
}

function listVacationRequests($pos)
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

		function temp($pos)
			{
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->nametxt = bab_translate("Fullname");
			$this->begindatetxt = bab_translate("Begin date");
			$this->enddatetxt = bab_translate("End date");
			$this->quantitytxt = bab_translate("Quantity");
			$this->statustxt = bab_translate("Status");
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			$this->pos = $pos;
			$this->db = $GLOBALS['babDB'];
			$req = "".BAB_VAC_ENTRIES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."' order by date, id desc";

			list($total) = $this->db->db_fetch_row($this->db->db_query("select count(*) as total from ".$req));
			if( $total > VAC_MAX_REQUESTS_LIST )
				{
				$tmpurl = $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq&pos=";
				if( $pos > 0)
					{
					$this->topurl = $tmpurl."0";
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - VAC_MAX_REQUESTS_LIST;
				if( $next >= 0)
					{
					$this->prevurl = $tmpurl.$next;
					$this->prevname = "&lt;";
					}

				$next = $pos + VAC_MAX_REQUESTS_LIST;
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


			if( $total > VAC_MAX_REQUESTS_LIST)
				{
				$req .= " limit ".$pos.",".VAC_MAX_REQUESTS_LIST;
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
				$this->begindate = bab_printDate($arr['date_begin']);
				if( $arr['day_begin'] != 1)
					$this->begindate .= " ". bab_translate($babDayType[$arr['day_begin']]);
				$this->enddate = bab_printDate($arr['date_end']);
				if( $arr['day_begin'] != 1)
					$this->enddate .= " ". bab_translate($babDayType[$arr['day_end']]);
				switch($arr['status'])
					{
					case 'Y':
						$this->status = $this->statarr[1];
						break;
					case 'N':
						$this->status = $this->statarr[2];
						break;
					default:
						$this->status = $this->statarr[0];
						break;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos);
	$babBody->babecho(	bab_printTemplate($temp, "vacuser.html", "vrequestslist"));
	return $temp->count;
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
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']), false);
			$this->halfnamebegin = bab_translate($babDayType[$row['day_begin']]);
			$this->dateend = bab_strftime(bab_mktime($row['date_end']), false);
			$this->halfnameend = bab_translate($babDayType[$row['day_end']]);
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

/* main */
$acclevel = bab_vacationsAccess();

if( count($acclevel) == 0)
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx))
	$idx = "lvreq";

if( isset($add))
{
	if( $add == "newvu")
	{
	if(!addNewVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $remarks, $total))
		$idx = "vunew";
	else
		$idx = "lvreq";

	}
	else if( $acclevel['approver'] == true && $add == "vven" )
	{
		if( isset($confirm))
			confirmVacationRequest($veid, $remarks, true);
		else if( isset($refuse))
			confirmVacationRequest($veid, $remarks, false);
	}
}

switch($idx)
	{
	case "unload":
		vedUnload();
		exit;
		break;

	case "morvw":
		viewWaitingVacReqDetail($id);
		exit;
		break;

	case "morve":
		viewVacationRequestDetail($id);
		exit;
		break;

	case "lval":
		$babBody->title = bab_translate("Request vacations waiting to be validate");
		if( $acclevel['user'] == true )
			{
			$babBody->addItemMenu("vunew", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=vunew");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		if( $acclevel['approver'] == true )
			{
			listWaitingVacation();
			$babBody->addItemMenu("lval", bab_translate("Validation"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lval");
			}
		else
			$babBody->title = bab_translate("There is no waiting vacation request");
		if( $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		break;
	
	case "vunew":
		$babBody->title = bab_translate("Request vacation");
		if( $acclevel['user'] == true )
			{
			if( !isset($daybegin)) $daybegin = "";
			if( !isset($monthbegin)) $monthbegin = "";
			if( !isset($yearbegin)) $yearbegin = "";
			if( !isset($dayend)) $dayend = "";
			if( !isset($monthend)) $monthend = "";
			if( !isset($yearend)) $yearend = "";
			if( !isset($halfdaybegin)) $halfdaybegin = "";
			if( !isset($halfdayend)) $halfdayend = "";
			if( !isset($remarks)) $remarks = "";
			requestVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $nbdays, $remarks);
			$babBody->addItemMenu("vunew", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=vunew");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		else
			{
			$idx = "lvt";
			}
		if( $acclevel['approver'] == true)
			$babBody->addItemMenu("lval", bab_translate("Validation"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lval");
		if( $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		break;

	case "lvreq":
	default:
		$babBody->title = bab_translate("Request vacation");
		if( $acclevel['user'] == true )
			{
			if( !isset($pos)) $pos = 0;
			listVacationRequests($pos);
			$babBody->addItemMenu("vunew", bab_translate("Request"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=vunew");
			$babBody->addItemMenu("lvreq", bab_translate("Requests"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lvreq");
			}
		else
			{
			$idx = "lvt";
			}
		if( $acclevel['approver'] == true)
			$babBody->addItemMenu("lval", bab_translate("Validation"), $GLOBALS['babUrlScript']."?tg=vacuser&idx=lval");
		if( $acclevel['manager'] == true)
			$babBody->addItemMenu("list", bab_translate("Management"), $GLOBALS['babUrlScript']."?tg=vacadm&idx=lvt");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
