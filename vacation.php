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
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_USERS_GROUPS_TBL." where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$arr['id_group']."' and ordering='".$order."'";
		$res = $db->db_query($query);
		if( $res && $db->db_num_rows($res) > 0)
			{
			$arr = $db->db_fetch_array($res);
			$query = "select * from ".BAB_USERS_TBL." where id='".$arr['id_object']."'";
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
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_USERS_GROUPS_TBL." where id_object='$userid' and isprimary='Y'";
	$res = $db->db_query($query);

	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$query = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$arr['id_group']."' and ordering='".$order."'";
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
	global $babBody;

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
			$this->type = bab_translate("Type");
			$this->begin = bab_translate("Begin date");
			$this->end = bab_translate("End date");
			$this->status = bab_translate("Status");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_TBL." where userid='$BAB_SESS_USERID' order by datebegin desc";
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
				$this->statusval = bab_getStatusName($this->arr['status']);
				$req = "select * from ".BAB_VACATIONS_TYPES_TBL." where id='".$this->arr['type']."'";
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
	$babBody->babecho(	bab_printTemplate($temp, "vacation.html", "vacationslist"));
	return $temp->count;

	}



function newVacation()
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

		var $remark;
		var $yearbegin;

		var $db;
		var $res;
		var $count;

		function temp()
			{
			global $babBody;
			$this->yearbegin = date("Y");
			$this->datebegin = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=2";
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateend = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=2";
			$this->dateendtxt = bab_translate("End date");
			$this->vactype = bab_translate("Vacation type");
			$this->addvac = bab_translate("Add Vacation");
			$this->remark = bab_translate("Remarks");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_TYPES_TBL."";
			$this->res = $this->db->db_query($req);
			if( $this->res )
				{
				$this->count = $this->db->db_num_rows($this->res); 
				}
/*
$babBody->script = <<<EOD
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
				$this->vacname = $arr['name'] . " (".$arr['days']." ". bab_translate("days"). ")";
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
	$babBody->babecho(	bab_printTemplate($temp,"vacation.html", "newvacation"));
	}

function addNewVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype)
	{
	global $babBody;

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
			$babBody->msgerror = bab_translate("ERROR: End date must be older")." !";
			return false;
			}
		}
	return true;	
	}


function confirmVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype, $remarks)
	{
	global $babBody;
	
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

			$this->from = bab_translate("From");
			$this->to = bab_translate("To");
			$this->type = bab_translate("Vacation type");
			$this->confirm = bab_translate("Confirm");
			$this->begindate = sprintf("%04d-%02d-%02d 00:00:00", $yearbegin, $monthbegin, $daybegin);
			$this->datefrom = bab_strftime(mktime(0,0,0,$monthbegin,$daybegin,$yearbegin), false);
			$this->halfdaybegin = $halfdaybegin;
			$this->enddate = sprintf("%04d-%02d-%02d 00:00:00", $yearend, $monthend, $dayend);
			$this->dateto = bab_strftime(mktime(0,0,0,$monthend,$dayend,$yearend), false);
			$this->halfdayend = $halfdayend;
			$this->remarkstext = bab_translate("Remarks");
			$this->remarks = $remarks;
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_TYPES_TBL." where id='$vactype'";
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
	$babBody->babecho(	bab_printTemplate($temp,"vacation.html", "confirmvacation"));
	}

function confirmAddVacation($begindate, $enddate, $halfdaybegin, $halfdayend, $vactype, $remarks)
	{
	global $babBody, $BAB_SESS_USERID, $BAB_SESS_USER, $babAdminEmail;
	$idstatus = getApproverStatus($BAB_SESS_USERID, 1);
	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_VACATIONS_TBL." (userid, datebegin, dateend, daybegin, dayend, type, status, comment, date) values ";
	$req .= "('" .$BAB_SESS_USERID. "', '" . $begindate. "', '" . $enddate. "', '" . $halfdaybegin. "', '" . $halfdayend. "', '" . $vactype. "', '" . $idstatus. "', '" . $remarks. "', now())";
	$res = $db->db_query($req);
	$emailapprover = getApproverEmail($BAB_SESS_USERID, 1);
	if( !empty($emailapprover))
		{
		$subject = bab_translate("Vacation request is waiting to be validated");
		$message = bab_translate("Mr")."/".bab_translate("Mrs"). " ". $BAB_SESS_USER . " ." .bab_translate("request a vacation").":\n";
		$message .= bab_translate("Vacation").":\n";
		$message .= bab_translate("from"). " " . bab_strftime(bab_mktime($begindate), false). " ". bab_translate($half[$halfdaybegin]) . "\n";
		$message .= bab_translate("to"). " " . bab_strftime(bab_mktime($enddate), false). " ". bab_translate($half[$halfdayend]) . "\n";

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
		if( bab_isUserUseVacation($GLOBALS['BAB_SESS_USERID']))
			{
			$babBody->addItemMenu("listvac", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacation&idx=listvac");
			$babBody->addItemMenu("newvac", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=vacation&idx=newvac");
			$babBody->addItemMenu("confirmvac", bab_translate("Confirm"), $GLOBALS['babUrlScript']."?tg=vacation");
			confirmVacation($daybegin, $monthbegin, $yearbegin,$dayend, $monthend, $yearend, $halfdaybegin, $halfdayend, $vactype, $remarks);
			}
		if( bab_isUserVacationApprover())
			$babBody->addItemMenu("approver", bab_translate("Approver"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=listvac");
		break;

	case "newvac";
		if( bab_isUserUseVacation($GLOBALS['BAB_SESS_USERID']))
			{
			$babBody->addItemMenu("listvac", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacation&idx=listvac");
			$babBody->addItemMenu("newvac", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=vacation&idx=newvac");
			newVacation();
			}
		if( bab_isUserVacationApprover())
			$babBody->addItemMenu("approver", bab_translate("Approver"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=listvac");
		break;

	case "listvac":
	default:
		if( bab_isUserUseVacation($GLOBALS['BAB_SESS_USERID']))
			{
			$babBody->addItemMenu("listvac", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacation&idx=listvac");
			$babBody->addItemMenu("newvac", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=vacation&idx=newvac");
			listVacations();
			}
		if( bab_isUserVacationApprover())
			$babBody->addItemMenu("approver", bab_translate("Approver"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=listvac");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>