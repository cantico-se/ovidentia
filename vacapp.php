<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/vacincl.php";


function findVacations()
	{
	global $body;

	class temp
		{
		var $group;
		var $groupid;
		var $groupname;
		var $status;
		var $statusid;
		var $statusname;
		var $user;
		var $search;
		var $all;
				
		var $arr = array();
		var $db;
		var $countstatus;
		var $countgroups;
		var $resstatus;
		var $resgroups;

		function temp()
			{
			global $BAB_SESS_USERID;

			$this->group = babTranslate("Group name");
			$this->status = babTranslate("Status");
			$this->user = babTranslate("User email");
			$this->all = babTranslate("All");
			$this->search = babTranslate("List");

			$this->db = new db_mysql();
			$req = "select * from vacationsmana_groups where id_object='$BAB_SESS_USERID' and approver='Y'";
			$this->resgroups = $this->db->db_query($req);
			$this->countgroups = $this->db->db_num_rows($this->resgroups);

			$req = "select * from vacations_states";
			$this->resstatus = $this->db->db_query($req);
			$this->countstatus = $this->db->db_num_rows($this->resstatus);
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->countgroups)
				{
				$arr = $this->db->db_fetch_array($this->resgroups);
				$this->groupname = getGroupName($arr['id_group']);
				$this->groupid = $arr['id_group'];
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextstatus()
			{
			static $i = 0;
			if( $i < $this->countstatus)
				{
				$arr = $this->db->db_fetch_array($this->resstatus);
				$this->statusname = getstatusName($arr['id']);
				$this->statusid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp, "vacapp.html", "findvacations"));
	return $temp->count;
	}

function listVacations( $group, $email)
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

		function temp($group, $status, $email)
			{
			global $BAB_SESS_USERID;
			$this->type = babTranslate("Type");
			$this->begin = babTranslate("Begin date");
			$this->end = babTranslate("End date");
			$this->user = babTranslate("User");
			$this->db = new db_mysql();

			$thsi->email = $email;
			$req = "select id_group, ordering, status from vacationsman_groups join groups where id_object='$BAB_SESS_USERID'";
			if( !empty($group))
				$req .= " and id_group='".$group."'";
			$req .= " and groups.id=vacationsman_groups.id_group and groups.vacation='Y'";
			$this->resgrp = $this->db->db_query($req);
			if( $this->resgrp )
				{
				$this->countgrp = $this->db->db_num_rows($this->resgrp);
				}
			else
				$this->countgrp = 0;
			}

		function getnextgroup()
			{
			static $j = 0;
			if( $j < $this->countgrp)
				{
				$arr = $this->db->db_fetch_array($this->resgrp);
				$this->statusgrp = $arr['status'];
				$req = "select * from users_groups where id_group='".$arr['id_group']."' and isprimary='Y'";
				if( !empty($this->email))
					{
					$query = "select * from users where email='".$this->email."'";
					$res = $this->db->db_query($query);
					if( $res && $this->db->db_num_rows($res) > 0)
						{
						$arr = $this->db->db_fetch_array($res);
						$req .= " and id_object='".$arr['id']."'";
						}
					}
				$this->resusers = $this->db->db_query($req);
				if( $this->resusers )
					{
					$this->countusers = $this->db->db_num_rows($this->resusers);
					}
				else
					$this->countusers = 0;
				$j++;
				return true;
				}
			else
				return false;
			}

		function getnextuser()
			{
			static $k = 0;
			if( $k < $this->countusers)
				{
				$arr = $this->db->db_fetch_array($this->resusers);
				$this->groupid = $arr['id_group'];
				$req = "select * from vacations where userid='".$arr['id_object']."' and status='".$this->statusgrp."'";
				$this->resvac = $this->db->db_query($req);
				if( $this->resvac )
					{
					$this->countvac = $this->db->db_num_rows($this->resvac);
					$this->userid = $arr['id_object'];
					}
				else
					$this->countvac = 0;
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getnextvac()
			{
			global $babDayType;
			static $i = 0;
			if( $i < $this->countvac)
				{
				$arr = $this->db->db_fetch_array($this->resvac);
				$this->datebegin = bab_strftime(bab_mktime($arr['datebegin']), false) . "  " . $babDayType[$arr['daybegin']];
				$this->dateend = bab_strftime(bab_mktime($arr['dateend']), false) . "  " . $babDayType[$arr['dayend']];
				//$this->statusval = getStatusName($arr['status']);
				$this->userurl = $GLOBALS['babUrl']."index.php?tg=vacapp&idx=updatevac&item=".$arr['id']."&groupid=".$this->groupid;
				$this->username = getUserName($this->userid);
				$req = "select * from vacations_types where id='".$arr['type']."'";
				$r = $this->db->db_query($req);
				$ar = $this->db->db_fetch_array($r);
				$this->typename = $ar['name'];
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

	$temp = new temp($group, $status, $email);
	$body->babecho(	babPrintTemplate($temp, "vacapp.html", "vacationslist"));
	return $temp->count;
	}

function updateVacation($vacid, $groupid)
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
		var $vacid;
		var $commentrefused;
		var $user;
		var $username;
		var $groupid;

		function temp($vacid, $groupid)
			{
			global $babDayType;
			$db = new db_mysql();
			$req = "select * from vacations where id='".$vacid."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->groupid = $groupid;
			$this->from = babTranslate("From");
			$this->to = babTranslate("To");
			$this->type = babTranslate("Vacation type");
			$this->remarkstext = babTranslate("Remarks");
			$this->confirm = babTranslate("Update");
			$this->refused = babTranslate("Refused");
			$this->accepted = babTranslate("Accepted");
			$this->commentrefused = babTranslate("Reasons of refusal");
			$this->user = babTranslate("User");
			$this->datefrom = bab_strftime(bab_mktime($arr['datebegin']), false);
			$this->halfdayfrom = $babDayType[$arr['daybegin']];

			$this->dateto = bab_strftime(bab_mktime($arr['dateend']), false);
			$this->halfdayto =  $babDayType[$arr['dayend']];
			$this->remarks = $arr['comment'];
			$this->username = getUserName($arr['userid']);

			$req = "select * from vacations_types where id='".$arr['type']."'";
			$res = $db->db_query($req);
			if( $res )
				{
				$arr2 = $db->db_fetch_array($res);
				$this->typename = $arr2['name'];
				}

			$req = "select * from vacationsman_groups where status='".$arr['status']."'";
			$res = $db->db_query($req);
			if( $res )
				{
				$arr = $db->db_fetch_array($res);
				$req = "select max(ordering) as maxorder from vacationsman_groups where id_group='".$arr['id_group']."'";
				$res = $db->db_query($req);
				$arr2 = $db->db_fetch_array($res);
				if( $arr2['maxorder'] == $arr['ordering'])
					{
					$this->ordering = 0;
					}
				else
					{
					$this->ordering = $arr['ordering'] + 1;
					}
				}

			$this->vacid = $vacid;
			}
		}

	$temp = new temp($vacid, $groupid);
	$body->babecho(	babPrintTemplate($temp,"vacapp.html", "updatevacation"));
	}

function confirmUpdateVacation($vacid, $ordering, $status, $groupid, $comref)
	{
		global $BAB_SESS_USERID, $babAdminEmail, $babDayType;

		$db = new db_mysql();
		$req = "select * from vacations where id='".$vacid."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);

		$subject = babTranslate("Vacation request"); 
		$username = getUserName($arr['userid']);
	
		$message = babTranslate("Mr")."/".babTranslate("Mrs"). " ". $username . " ." .babTranslate("request a vacation").":\n";
		$message .= babTranslate("Request date") .": " . bab_strftime(bab_mktime($arr['date']), false) ."\n";

		$message .= babTranslate("Vacation").":\n";
		$message .= babTranslate("from"). " " . bab_strftime(bab_mktime($arr['datebegin']), false). " ". $babDayType[$arr['daybegin']] . "\n";
		$message .= babTranslate("to"). " " . bab_strftime(bab_mktime($arr['dateend']), false). " ". $babDayType[$arr['dayend']] . "\n";

		$req = "select * from users where id='".$arr['userid']."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		$email = $arr['email'];

		if( $status == 0) // refused
		{
			$result = "has been refused";
			$newstatus = 1;
			if( $ordering == 0 )
				{
				$req = "select * from vacationsman_groups where id_group='".$groupid."' and id_object!='".$BAB_SESS_USERID."'";
				}
			else
				{
				$req = "select * from vacationsman_groups where id_group='".$groupid."' and ordering >= '1' and ordering < '".$ordering."' and id_object!='".$BAB_SESS_USERID."'";
				}

			$result .= "\n". $comref;
		}
		else // accepted
		{
			if( $ordering == 0 )
				{
				$req = "select * from vacationsman_groups where id_group='".$groupid."' and id_object!='".$BAB_SESS_USERID."'";
				$newstatus = 2;
				$result = babTranslate("has been accepted");
				}
			else
				{
				$req = "select * from vacationsman_groups where id_group='".$groupid."' and ordering='".$ordering."'";
				$res = $db->db_query($req);
				$r = $db->db_fetch_array($res);
				$newstatus = $r['status'];
				$result = babTranslate("has a new status")." :" . getStatusName($newstatus);
			}
		}

		$res = $db->db_query($req);

		$arrrecipients = array();
		while( $ar = $db->db_fetch_array($res))
		{
			$req = "select * from users where id='".$ar['id_object']."'";
			$res2 = $db->db_query($req);
			$r = $db->db_fetch_array($res2);
			array_push($arrrecipients, $r['email']);
		}

		$header = "From: ".$babAdminEmail ."\r\n";
		if( $status != 0 && $ordering != 0)
		{
			mail($email, $subject, $message.$result, $header);
			$result = babTranslate("is waiting to be validated");
			$email = implode($arrrecipients, " ");
			mail($email, $subject, $message.$result, $header);
		}
		else
		{
			$header .= "CC: ";
			$header .= implode($arrrecipients, ",");
			mail($email, $subject, $message.$result, $header);
		}

		$req = "update vacations set ";
		if( $status == 0)
			$req .= "comref='".$comref."',";
		$req .= "status='".$newstatus."' where id='".$vacid."'";
		$res = $db->db_query($req);
	}

/* main */
if( !isset($idx))
	$idx = "listvac";

if( isset($findvac) && $findvac == "vac" )
	{
		listVacations( $group, $email);
	}

if( isset($updatevac) && $updatevac == "update")
	{
		confirmUpdateVacation($vacid, $ordering, $status, $groupid, $comref);
	}

switch($idx)
	{
	case "updatevac":
		if( isUserVacationApprover())
			{
			$body->addItemMenu("listvac", babTranslate("Vacations"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=listvac");
			//$body->addItemMenu("findvac", babTranslate("Search"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=findvac");
			$body->addItemMenu("updatevac", babTranslate("Update"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=updatevac&item=".$item);
			updateVacation($item, $groupid);
			}
		break;

	/*
	case "findvac":
		if( isUserVacationApprover())
			{
			$body->addItemMenu("listvac", babTranslate("Vacations"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=listvac");
			$body->addItemMenu("findvac", babTranslate("Search"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=findvac");
			findVacations();
			}
		break;
	*/
	case "listvac":
	default:
		if( isUserVacationApprover())
			{
			$body->addItemMenu("listvac", babTranslate("Vacations"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=listvac");
			//$body->addItemMenu("findvac", babTranslate("Search"), $GLOBALS['babUrl']."index.php?tg=vacapp&idx=findvac");
			listVacations("","");
			}

		break;
	}
$body->setCurrentItemMenu($idx);

?>