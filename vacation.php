<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/vacincl.php";

function getApproverEmail($userid, $order)
	{
	$email = "";
	$db = new db_mysql();
	$query = "select * from users_groups where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from vacationsman_groups where id_group='".$arr['id_group']."' and ordering='".$order."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$query = "select * from users where id='".$arr['id_object']."'";
			$res = $db->db_query($query);
			if( $res && $db->db_num_rows($res) > 0)
				{
				$arr = $db->db_fetch_array($res);
				return $arr['email'];
				}
			}
		}
	return $email;
	}

function getApproverStatus($userid, $order)
	{
	$db = new db_mysql();
	$query = "select * from users_groups where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from vacationsman_groups where id_group='".$arr['id_group']."' and ordering='".$order."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			return $arr['status'];
			}
		}
	return 0;
	}


function listVacations()
	{
	global $body;

	class temp
		{
		var $type;
		var $begin;
		var $end;
		var $status;
		var $typename;
		var $datebegin;
		var $dateend;
		var $statusval;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			global $BAB_SESS_USERID;
			$this->type = babTranslate("Type");
			$this->begin = babTranslate("Begin date");
			$this->end = babTranslate("End date");
			$this->status = babTranslate("Status");
			$this->db = new db_mysql();
			$req = "select * from vacations where userid='$BAB_SESS_USERID' order by datebegin desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDayType;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->datebegin = bab_strftime(bab_mktime($this->arr['datebegin']), false) . "  " . $babDayType[$this->arr['daybegin']];
				$this->dateend = bab_strftime(bab_mktime($this->arr['dateend']), false) . "  " . $babDayType[$this->arr['dayend']];
				$this->statusval = getStatusName($this->arr['status']);
				$req = "select * from vacations_types where id='".$this->arr['type']."'";
				$r = $this->db->db_query($req);
				$ar = $this->db->db_fetch_array($r);
				$this->typename = $ar['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "vacation.html", "vacationslist"));
	return $temp->count;

	}



function newVacation()
	{
	global $body;
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

		var $remark;
		var $yearbegin;

		var $db;
		var $res;
		var $count;

		function temp()
			{
			global $body;
			$this->yearbegin = date("Y");
			$this->datebegin = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=month&callback=dateBegin&ymin=0&ymax=2');";
			$this->datebegintxt = babTranslate("Begin date");
			$this->dateend = "javascript:Start('".$GLOBALS['babUrl']."index.php?tg=month&callback=dateEnd&ymin=0&ymax=2');";
			$this->dateendtxt = babTranslate("End date");
			$this->vactype = babTranslate("Vacation type");
			$this->addvac = babTranslate("Add Vacation");
			$this->remark = babTranslate("Remarks");
			$this->db = new db_mysql();
			$req = "select * from vacations_types";
			$this->res = $this->db->db_query($req);
			if( $this->res )
				{
				$this->count = $this->db->db_num_rows($this->res); 
				}
/*
$body->script = <<<EOD
EOD;
*/
			}

		function getnextday()
			{
			static $i = 1;
			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( date("j") == $i)
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
				if( date("n") == $i)
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
				$this->yearidval = $this->yearbegin + $i;
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
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				$count = 3;
				return false;
				}

			}

		function getnextvac()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->vacid = $arr['id'];
				$this->vacname = $arr['name'] . " (".$arr['days']." ". babTranslate("days"). ")";
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"vacation.html", "newvacation"));
	}

function addNewVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype)
	{
	global $body;

	if( $halfdaybegin == 2)
		{
		$nbhalfdays = 1;
		}
	else
		{
		$begin = mktime( 0,0,0,$monthbegin, $daybegin, date("Y") + $yearbegin - 1);
		$end = mktime( 0,0,0,$monthend, $dayend, date("Y") + $yearend - 1);

		if( $end == $begin )
			{
			switch($halfdaybegin)
				{
				case 1: // whole day
					$nbhalfdays = 2;
					break;
				case 2: // morning
				case 3: // afternoon
					$nbhalfdays = 1;
					break;
				}
			}
		else if( $end > $begin )
			{
			}
		else
			{
			$body->msgerror = babTranslate("ERROR: End date must be older")." !";
			return false;
			}
		}
	return true;	
	}


function confirmVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype, $remarks)
	{
	global $body;
	
	class temp
		{
		var $begindate;
		var $halfdaybegin;
		var $enddate;
		var $halfdayend;
		var $vactype;
		var $from;
		var $datefrom;
		var $halfdayfrom;
		var $to;
		var $dateto;
		var $halfdayto;
		var $type;
		var $typename;
		var $confirm;
		var $remarks;
		var $remarkstext;

		function temp($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype, $remarks)
			{
			global $babDayType;
			$yearbegin = date("Y") + $yearbegin - 1;
			$yearend = date("Y") + $yearend - 1;

			$this->from = babTranslate("From");
			$this->to = babTranslate("To");
			$this->type = babTranslate("Vacation type");
			$this->confirm = babTranslate("Confirm");
			$this->begindate = sprintf("%04d-%02d-%02d 00:00:00", $yearbegin, $monthbegin, $daybegin);
			$this->datefrom = bab_strftime(mktime(0,0,0,$monthbegin,$daybegin,$yearbegin), false);
			$this->halfdaybegin = $halfdaybegin;
			$this->enddate = sprintf("%04d-%02d-%02d 00:00:00", $yearend, $monthend, $dayend);
			$this->dateto = bab_strftime(mktime(0,0,0,$monthend,$dayend,$yearend), false);
			$this->halfdayend = $halfdayend;
			$this->remarkstext = babTranslate("Remarks");
			$this->remarks = $remarks;
			$db = new db_mysql();
			$req = "select * from vacations_types where id='$vactype'";
			$res = $db->db_query($req);
			if( $res )
				{
				$arr = $db->db_fetch_array($res);
				$this->typename = $arr['name'];
				}
			$this->vactype = $vactype;
			$this->halfdayfrom = $babDayType[$halfdaybegin];
			$this->halfdayto = $babDayType[$halfdayend];
			}
		}

	$temp = new temp($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype, $remarks);
	$body->babecho(	babPrintTemplate($temp,"vacation.html", "confirmvacation"));
	}

function confirmAddVacation($begindate, $enddate, $halfdaybegin, $halfdayend, $vactype, $remarks)
	{
	global $body, $BAB_SESS_USERID, $BAB_SESS_USER, $babAdminEmail;
	$idstatus = getApproverStatus($BAB_SESS_USERID, 1);
	$db = new db_mysql();
	$req = "insert into vacations (userid, datebegin, dateend, daybegin, dayend, type, status, comment, date) values ";
	$req .= "('" .$BAB_SESS_USERID. "', '" . $begindate. "', '" . $enddate. "', '" . $halfdaybegin. "', '" . $halfdayend. "', '" . $vactype. "', '" . $idstatus. "', '" . $remarks. "', now())";
	$res = $db->db_query($req);
	$emailapprover = getApproverEmail($BAB_SESS_USERID, 1);
	if( !empty($emailapprover))
		{
		$subject = babTranslate("Vacation request is waiting to be validated");
		$message = babTranslate("Mr/Mrs"). " ". $BAB_SESS_USER . " ." .babTranslate("request a vacation").":\n";
		$message .= babTranslate("Vacation").":\n";
		$message .= babTranslate("from"). " " . bab_strftime(bab_mktime($begindate), false). " ". babTranslate($half[$halfdaybegin]) . "\n";
		$message .= babTranslate("to"). " " . bab_strftime(bab_mktime($enddate), false). " ". babTranslate($half[$halfdayend]) . "\n";

		mail($emailapprover,$subject,$message,"From: ".$babAdminEmail);
		}

	}

/* main */
if( !isset($idx))
	$idx = "listvac";

if( isset($newvacation))
	{
	if(!addNewVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype))
		$idx = "newvac";
	}

if( isset($addvacation) && $addvacation == "add")
	{
	confirmAddVacation($begindate, $enddate, $halfdaybegin, $halfdayend, $vactype, $remarks);
	}

switch($idx)
	{
	case "confirmvac":
		if( useVacation($GLOBALS['BAB_SESS_USERID']))
			{
			$body->addItemMenu("listvac", babTranslate("Vacations"), $GLOBALS['babUrl']."index.php?tg=vacation&idx=listvac");
			$body->addItemMenu("newvac", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=vacation&idx=newvac");
			$body->addItemMenu("confirmvac", babTranslate("Confirm"), $GLOBALS['babUrl']."index.php?tg=vacation");
			confirmVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype, $remarks);
			}
		if( isUserVacationApprover())
			$body->addItemMenu("approver", babTranslate("Approver"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=listvac");
		break;

	case "newvac";
		if( useVacation($GLOBALS['BAB_SESS_USERID']))
			{
			$body->addItemMenu("listvac", babTranslate("Vacations"), $GLOBALS['babUrl']."index.php?tg=vacation&idx=listvac");
			$body->addItemMenu("newvac", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=vacation&idx=newvac");
			newVacation();
			}
		if( isUserVacationApprover())
			$body->addItemMenu("approver", babTranslate("Approver"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=listvac");
		break;

	case "listvac":
	default:
		if( useVacation($GLOBALS['BAB_SESS_USERID']))
			{
			$body->addItemMenu("listvac", babTranslate("Vacations"), $GLOBALS['babUrl']."index.php?tg=vacation&idx=listvac");
			$body->addItemMenu("newvac", babTranslate("Create"), $GLOBALS['babUrl']."index.php?tg=vacation&idx=newvac");
			listVacations();
			}
		if( isUserVacationApprover())
			$body->addItemMenu("approver", babTranslate("Approver"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=listvac");
		break;
	}
$body->setCurrentItemMenu($idx);

?>