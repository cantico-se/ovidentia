<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/vacincl.php";
include $babInstallPath."utilit/mailincl.php";


function findVacations()
	{
	global $babBody;

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

			$this->group = bab_translate("Group name");
			$this->status = bab_translate("Status");
			$this->user = bab_translate("User email");
			$this->all = bab_translate("All");
			$this->search = bab_translate("List");

			$this->db = $GLOBALS['babDB'];
			$req = "select * from vacationsmana_groups where id_object='$BAB_SESS_USERID' and approver='Y'";
			$this->resgroups = $this->db->db_query($req);
			$this->countgroups = $this->db->db_num_rows($this->resgroups);

			$req = "select * from ".BAB_VACATIONS_STATES_TBL."";
			$this->resstatus = $this->db->db_query($req);
			$this->countstatus = $this->db->db_num_rows($this->resstatus);
			}

		function getnextgroup()
			{
			static $i = 0;
			if( $i < $this->countgroups)
				{
				$arr = $this->db->db_fetch_array($this->resgroups);
				$this->groupname = bab_getGroupName($arr['id_group']);
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
				$this->statusname = bab_getStatusName($arr['id']);
				$this->statusid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "vacapp.html", "findvacations"));
	return $temp->count;
	}

function listVacations( $group, $email)
	{
	global $babBody;

	class temp
		{
		var $type;
		var $begin;
		var $end;
		var $typename;
		var $datebegin;
		var $dateend;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp($group, $email)
			{
			global $BAB_SESS_USERID;
			$this->type = bab_translate("Type");
			$this->begin = bab_translate("Begin date");
			$this->end = bab_translate("End date");
			$this->user = bab_translate("User");
			$this->db = $GLOBALS['babDB'];

			$thsi->email = $email;
			$req = "select id_group, ordering, status from ".BAB_VACATIONSMAN_GROUPS_TBL." join ".BAB_GROUPS_TBL." where id_object='$BAB_SESS_USERID'";
			if( !empty($group))
				$req .= " and id_group='".$group."'";
			$req .= " and ".BAB_GROUPS_TBL.".id=".BAB_VACATIONSMAN_GROUPS_TBL.".id_group and ".BAB_GROUPS_TBL.".vacation='Y'";
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
				$req = "select * from ".BAB_USERS_GROUPS_TBL." where id_group='".$arr['id_group']."' and isprimary='Y'";
				if( !empty($this->email))
					{
					$query = "select * from ".BAB_USERS_TBL." where email='".$this->email."'";
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
				$req = "select * from ".BAB_VACATIONS_TBL." where userid='".$arr['id_object']."' and status='".$this->statusgrp."'";
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
				//$this->statusval = bab_getStatusName($arr['status']);
				$this->userurl = $GLOBALS['babUrlScript']."?tg=vacapp&idx=updatevac&item=".$arr['id']."&groupid=".$this->groupid;
				$this->username = bab_getUserName($this->userid);
				$req = "select * from ".BAB_VACATIONS_TYPES_TBL." where id='".$arr['type']."'";
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

	$temp = new temp($group, $email);
	$babBody->babecho(	bab_printTemplate($temp, "vacapp.html", "vacationslist"));
	return $temp->count;
	}

function updateVacation($vacid, $groupid)
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
		var $vacid;
		var $commentrefused;
		var $user;
		var $username;
		var $groupid;

		function temp($vacid, $groupid)
			{
			global $babDayType;
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_VACATIONS_TBL." where id='".$vacid."'";
			$res = $db->db_query($req);
			$arr = $db->db_fetch_array($res);
			$this->groupid = $groupid;
			$this->from = bab_translate("From");
			$this->to = bab_translate("Until");
			$this->type = bab_translate("Vacation type");
			$this->remarkstext = bab_translate("Remarks");
			$this->confirm = bab_translate("Update");
			$this->refused = bab_translate("Refused");
			$this->accepted = bab_translate("Accepted");
			$this->commentrefused = bab_translate("Reasons of refusal");
			$this->user = bab_translate("User");
			$this->datefrom = bab_strftime(bab_mktime($arr['datebegin']), false);
			$this->halfdayfrom = $babDayType[$arr['daybegin']];

			$this->dateto = bab_strftime(bab_mktime($arr['dateend']), false);
			$this->halfdayto =  $babDayType[$arr['dayend']];
			$this->remarks = $arr['comment'];
			$this->username = bab_getUserName($arr['userid']);

			$req = "select * from ".BAB_VACATIONS_TYPES_TBL." where id='".$arr['type']."'";
			$res = $db->db_query($req);
			if( $res )
				{
				$arr2 = $db->db_fetch_array($res);
				$this->typename = $arr2['name'];
				}

			$req = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where status='".$arr['status']."'";
			$res = $db->db_query($req);
			if( $res )
				{
				$arr = $db->db_fetch_array($res);
				$req = "select max(ordering) as maxorder from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$arr['id_group']."'";
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
	$babBody->babecho(	bab_printTemplate($temp,"vacapp.html", "updatevacation"));
	}

function confirmUpdateVacation($vacid, $ordering, $status, $groupid, $comref)
	{
		global $BAB_SESS_USERID, $babAdminEmail, $babDayType;

		$db = $GLOBALS['babDB'];
		$req = "select * from ".BAB_VACATIONS_TBL." where id='".$vacid."'";
		$res = $db->db_query($req);
		$arr = $db->db_fetch_array($res);
		
		$subject = bab_translate("Vacation request"); 
		$username = bab_getUserName($arr['userid']);

		class tempa
			{
			var $message;
			var $from;
			var $site;
			var $until;
			var $begindate;
			var $enddate;


			function tempa($userid, $begindate, $enddate, $halfdaybegin, $halfdayend, $status, $more)
				{
				global $babDayType;
				$this->message = bab_translate("Vacation request");
				$this->fromuser = bab_translate("User");
				$this->from = bab_translate("from");
				$this->until = bab_translate("until");
				$this->username = bab_getUserName($userid);
				$this->status = bab_translate("Status");
				$this->more = bab_translate("Information");
				$this->begindate = bab_strftime(bab_mktime($begindate), false). " ". bab_translate($babDayType[$halfdaybegin]);
				$this->enddate = bab_strftime(bab_mktime($enddate), false). " ". bab_translate($babDayType[$halfdayend]);
				$this->statustxt = $status;
				$this->moretxt = $more;
				}
			}

		$idto = $arr['userid'];

		if( $status == 0) // refused
		{
			$status = bab_translate("Vacation has been refused");
			$newstatus = 1;
			if( $ordering == 0 )
				{
				$req = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$groupid."' and id_object!='".$BAB_SESS_USERID."'";
				}
			else
				{
				$req = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$groupid."' and ordering >= '1' and ordering < '".$ordering."' and id_object!='".$BAB_SESS_USERID."'";
				}

			$more = $comref;
		}
		else // accepted
		{
			if( $ordering == 0 )
				{
				$req = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$groupid."' and id_object!='".$BAB_SESS_USERID."'";
				$newstatus = 2;
				$status = bab_translate("Vacation has been accepted");
				}
			else
				{
				$req = "select * from ".BAB_VACATIONSMAN_GROUPS_TBL." where id_group='".$groupid."' and ordering='".$ordering."'";
				$res = $db->db_query($req);
				$r = $db->db_fetch_array($res);
				$newstatus = $r['status'];
				$status = bab_getStatusName($newstatus);
			}
			$more = "";
		}


		$mail = bab_mail();
		if( $mail == false )
			return;

		$mail->mailTo(bab_getUserEmail($idto), bab_getUserName($idto));

		$mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
		$mail->mailSubject(bab_translate("Vacation request"));

		$tempa = new tempa($arr['userid'], $arr['datebegin'], $arr['dateend'], $arr['daybegin'], $arr['dayend'], $status, $more);
		$message = bab_printTemplate($tempa,"mailinfo.html", "vacationupdate");
		$mail->mailBody($message, "html");

		$message = bab_printTemplate($tempa,"mailinfo.html", "vacationupdatetxt");
		$mail->mailAltBody($message);



		$res = $db->db_query($req);
		$arrrecipients = array();
		while( $ar = $db->db_fetch_array($res))
		{
			array_push($arrrecipients, $ar['id_object']);
		}

		if( $status != 0 && $ordering != 0)
		{
			$mail->send();	
			$mail->clearBcc();
			$mail->clearTo();
			$status = bab_translate("Vacation is waiting to be validated");
			$tempa = new tempa($arr['userid'], $arr['datebegin'], $arr['dateend'], $arr['daybegin'], $arr['dayend'], $status, $more);
			$message = bab_printTemplate($tempa,"mailinfo.html", "vacationupdate");
			$mail->mailBody($message, "html");
			$message = bab_printTemplate($tempa,"mailinfo.html", "vacationupdatetxt");
			$mail->mailAltBody($message);
			for( $i=0; $i < count($arrrecipients); $i++)
				$mail->mailTo(bab_getUserEmail($arrrecipients[$i]), bab_getUserName($arrrecipients[$i]));
			$mail->send();	
			$mail->clearBcc();
			$mail->clearTo();
		}
		else
		{
			for( $i=0; $i < count($arrrecipients); $i++)
				$mail->mailCc(bab_getUserEmail($arrrecipients[$i]), bab_getUserName($arrrecipients[$i]));
			$mail->send();	
		}

		if( !bab_isMagicQuotesGpcOn())
			{
			$comref = addslashes($comref);
			}
		$req = "update ".BAB_VACATIONS_TBL." set ";
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
		if( bab_isUserVacationApprover())
			{
			$babBody->title = bab_translate("Vacation request");
			$babBody->addItemMenu("listvac", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=listvac");
			//$babBody->addItemMenu("findvac", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=findvac");
			$babBody->addItemMenu("updatevac", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=updatevac&item=".$item);
			updateVacation($item, $groupid);
			}
		break;

	/*
	case "findvac":
		if( bab_isUserVacationApprover())
			{
			$babBody->addItemMenu("listvac", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=listvac");
			$babBody->addItemMenu("findvac", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=findvac");
			findVacations();
			}
		break;
	*/
	case "listvac":
	default:
		if( bab_isUserVacationApprover())
			{
			$babBody->title = bab_translate("Vacation request");
			$babBody->addItemMenu("listvac", bab_translate("Vacations"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=listvac");
			//$babBody->addItemMenu("findvac", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=vacapp&idx=findvac");
			listVacations("","");
			}

		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
