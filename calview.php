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
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/forumincl.php";

function showOthers()
{
	global $babBody;

	class tempA
		{

		function tempA()
			{
			$this->approbations = bab_translate("Approbations");
			$this->news = bab_translate("New documents");
			}
		}

	$temp = new tempA();
	$babBody->babecho( bab_printTemplate($temp,"calview.html", "otherslist"));
}


function upComingEvents($idcal)
{
	global $babBody;

	class temp
		{

		var $db;
		var $arrevent = array();
		var $resevent;
		var $countevent;
		var $alternate;
		var $calid;

		function temp($idcal)
			{
			global $BAB_SESS_USERID;
			$this->calid = $idcal;
			$this->db = $GLOBALS['babDB'];
			$mktime = mktime();
			$this->newevents = bab_translate("Upcoming Events ( in the seven next days )");
			$this->daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$mktime = $mktime + 518400;
			$this->daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id_cal='".$idcal."' and ('".$this->daymin."' between start_date and end_date or '".$this->daymax."' between start_date and end_date";
			$req .= " or start_date between '".$this->daymin."' and '".$this->daymax."' or end_date between '".$this->daymin."' and '".$this->daymax."') order by start_date, start_time asc";		
			$this->resevent = $this->db->db_query($req);
			$this->countevent = $this->db->db_num_rows($this->resevent);
			$this->arrgrp = bab_getUserGroups();
			$this->arrgrp['id'][] = '1';
			$this->arrgrp['name'][] = bab_translate("Registered users");
			$this->countgrp = count($this->arrgrp['id']);
			}

		function getevent()
			{
			static $k=0;
			if( $k < $this->countevent)
				{
				$arr = $this->db->db_fetch_array($this->resevent);
				$this->time = substr($arr['start_time'], 0 ,5). " " . substr($arr['end_time'], 0 ,5);
				$this->date = bab_strftime(bab_mktime($arr['start_date']." ". $arr['start_time']), false);
				$this->title = $arr['title'];
				$rr = explode("-", $arr['start_date']);
				$this->titleurl = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$rr[2]."&month=".$rr[1]."&year=".$rr[0]. "&calid=".$this->calid. "&evtid=".$arr['id'];
				if( $k % 2)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getgroup()
			{
			static $k=0;
			if( $k < $this->countgrp)
				{
				$this->grpname = bab_getGroupName($this->arrgrp['id'][$k]);
				$idcal = bab_getCalendarId($this->arrgrp['id'][$k], 2);
				if( $idcal != 0 )
					{
					$req = "select * from ".BAB_CAL_EVENTS_TBL." where id_cal='".bab_getCalendarId($this->arrgrp['id'][$k], 2)."' and ('".$this->daymin."' between start_date and end_date or '".$this->daymax."' between start_date and end_date";
					$req .= " or start_date between '".$this->daymin."' and '".$this->daymax."' or end_date between '".$this->daymin."' and '".$this->daymax."') order by start_date, start_time asc";
					$this->resgrpevent = $this->db->db_query($req);
					$this->countgrpevent = $this->db->db_num_rows($this->resgrpevent);
					}
				else
					{
					$this->countgrpevent = 0;
					}
				$k++;
				return true;
				}
			else
				return false;
			}
		
		function getgrpevent()
			{
			static $k=0;
			if( $k < $this->countgrpevent)
				{
				$arr = $this->db->db_fetch_array($this->resgrpevent);
				$this->time = substr($arr['start_time'], 0 ,5). " " . substr($arr['end_time'], 0 ,5);
				$this->date = bab_strftime(bab_mktime($arr['start_date']." ". $arr['start_time']), false);
				$this->title = $arr['title'] . " ( ". $this->grpname ." )";
				$rr = explode("-", $arr['start_date']);
				$this->titleurl = $GLOBALS['babUrlScript']."?tg=event&idx=modify&day=".$rr[2]."&month=".$rr[1]."&year=".$rr[0]. "&calid=".$arr['id_cal']. "&evtid=".$arr['id'];
				if( $k % 2)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}
		}

	$temp = new temp($idcal);
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "eventslist"));
}

function newEmails()
{
	global $babBody;

	class temp4
		{

		var $db;
		var $count;
		var $res;
		var $newmails;
		var $domain;
		var $domainurl;
		var $nbemails;

		function temp4()
			{
			global $BAB_SESS_USERID, $BAB_HASH_VAR;
			$this->db = $GLOBALS['babDB'];
			$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$BAB_SESS_USERID."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->newmails = bab_translate("Waiting mails");
			}

		function getmail()
			{
			static $i=0;
			if( $i < $this->count )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$arr['domain']."'";
				$res2 = $this->db->db_query($req);
				$this->domain = "";
				$this->nbemails = "";
				$this->domainurl = "";
				if( $res2 && $this->db->db_num_rows($res2) > 0 )
					{
					$arr2 = $this->db->db_fetch_array($res2);
					$this->domain = $arr2['name'];
					$cnxstring = "{".$arr2['inserver']."/".$arr2['access'].":".$arr2['inport']."}INBOX";
					$mbox = @imap_open($cnxstring, $arr['login'], $arr['accpass']);
					if($mbox)
						{
						$this->domainurl = $GLOBALS['babUrlScript']."?tg=inbox&&accid=".$arr['id'];
						$nbmsg = imap_num_recent($mbox); 
						$this->nbemails = "( ". $nbmsg. " )";
						imap_close($mbox);
						}
					else
						{
						$this->nbemails = "( ". imap_last_error(). " )";
						}
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

		}

	$temp = new temp4();
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "mailslist"));
}

function newFiles($nbdays)
{
	global $babBody;

	class temp6
		{

		var $db;
		var $count;
		var $res;

		function temp6($nbdays)
			{
			global $babBody, $BAB_SESS_USERID, $BAB_HASH_VAR;
			$this->nbdays = $nbdays;
			$this->db = $GLOBALS['babDB'];
			$req = "select distinct f.* from ".BAB_FILES_TBL." f, ".BAB_FMDOWNLOAD_GROUPS_TBL." fmg,  ".BAB_USERS_GROUPS_TBL." ug where f.bgroup='Y' and f.state='' and f.confirmed='Y' and fmg.id_object = f.id_owner and ( fmg.id_group='2'";
			if( $BAB_SESS_USERID != "" )
			$req .= " or fmg.id_group='1' or (fmg.id_group=ug.id_group and ug.id_object='".$BAB_SESS_USERID."')";
			$req .= ")";
			
			if( $this->nbdays > 0)
				$req .= " and f.modified >= DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
			else
				$req .= " and f.modified >= '".$babBody->lastlog."'";
		
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $nbdays > 0)
				$this->newfiles = bab_translate("Last files ( Since seven days before your last visit )");
			else
				$this->newfiles = bab_translate("New files");
			}

		function getfile()
			{
			static $i=0;
			if( $i < $this->count )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->file = $arr['name'];
				if( !empty($arr['description']))
					$this->filedesc = $arr['description'];
				else
					$this->filedesc = "";
				$this->fileurl = $GLOBALS['babUrlScript']."?tg=search&idx=e&id=".$arr['id'];
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

	$temp = new temp6($nbdays);
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "fileslist"));
}

/* main */
if(!isset($idx))
	{
	$idx = "view";
	}

switch($idx)
	{
	default:
	case "view":
		$babBody->title = bab_translate("Summary");
		showOthers();
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( $idcal != 0 || $babBody->calaccess || bab_calendarAccess() != 0 )
		{
			upComingEvents($idcal);
		}
		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			{
			newEmails();
			}
		$babBody->addItemMenu("view", bab_translate("Summary"), $GLOBALS['babUrlScript']."?tg=calview");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
