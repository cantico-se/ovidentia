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
* @internal SEC1 PR 20/02/2007 FULL
*/

include_once 'base.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/forumincl.php';

function showOthers()
{
	global $babBody;

	class tempA
		{

		function tempA()
			{
			
			}
		}

	$temp = new tempA();
	$babBody->babecho( bab_printTemplate($temp,"calview.html", "otherslist"));
}


function upComingEvents()
{
	global $babBody;

	class temp
		{
		var $arrevent = array();
		var $resevent;
		var $countevent;
		var $alternate;
		var $calid;

		function temp()
			{
			global $babBody, $babDB, $BAB_SESS_USERID;
			$mktime = mktime();
			$this->newevents = bab_translate("Upcoming Events ( in the seven next days )");
			$this->daymin = sprintf("%04d-%02d-%02d 00:00:00", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$mktime = $mktime + 518400;
			$this->daymax = sprintf("%04d-%02d-%02d 23:59:59", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));

			$babBody->icalendars->initializeCalendars();
			if (!empty($babBody->icalendars->id_percal))
				{
				$this->resevent = $babDB->db_query(
					"select 
						ce.*, 
						ceo.id_cal 
					from ".BAB_CAL_EVENTS_TBL." ce 
					left join ".BAB_CAL_EVENTS_OWNERS_TBL." ceo on ce.id=ceo.id_event 
					where ceo.id_cal='".$babDB->db_escape_string($babBody->icalendars->id_percal)."' 
						and ce.start_date < '".$babDB->db_escape_string($this->daymax)."' 
						and ce.end_date > '".$babDB->db_escape_string($this->daymin)."'
					order by ce.start_date
				");
				$this->countevent = $babDB->db_num_rows($this->resevent);
				}
			else
				{
				$this->countevent = 0;
				}

			$idpubcals = array();
			reset($babBody->icalendars->pubcal);
			while( $row=each($babBody->icalendars->pubcal) ) 
				{
				$idpubcals[] = $row[0];
				}

		if (count($idpubcals))
				{
				$this->resgrpevent = $babDB->db_query("
				
				select ce.*, ceo.id_cal from ".BAB_CAL_EVENTS_TBL." ce 
					left join ".BAB_CAL_EVENTS_OWNERS_TBL." ceo on ce.id=ceo.id_event 
					where ceo.id_cal IN (".$babDB->quote($idpubcals).") 
						AND ce.start_date < '".$babDB->db_escape_string($this->daymax)."' 
						AND ce.end_date > '".$babDB->db_escape_string($this->daymin)."'
					ORDER BY ce.start_date 
					");

				$this->countgrpevent = $babDB->db_num_rows($this->resgrpevent);
				}
			else
				{
				$this->countgrpevent = 0;
				}

			if( $this->countevent || $this->countgrpevent )
				{
				$this->bshow = true;
				}
			else
				{
				$this->bshow = false;
				}

			}

		function getevent()
			{
			global $babDB;
			static $k=0;
			if( $k < $this->countevent)
				{
				$arr = $babDB->db_fetch_array($this->resevent);
				bab_debug($arr);
				$this->enddate = bab_toHtml(bab_shortDate(bab_mktime($arr['end_date'])));
				$this->startdate = bab_toHtml(bab_shortDate(bab_mktime($arr['start_date'])));
				$this->title = bab_toHtml($arr['title']);
				$this->titleurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&idcal=".$arr['id_cal']. "&evtid=".$arr['id']);
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

		function getgrpevent()
			{
			global $babDB;
			static $k=0;
			if( $k < $this->countgrpevent)
				{
				$arr = $babDB->db_fetch_array($this->resgrpevent);
				$this->enddate = bab_toHtml(bab_shortDate(bab_mktime($arr['end_date'])));
				$this->startdate = bab_toHtml(bab_shortDate(bab_mktime($arr['start_date'])));
				$this->title = bab_toHtml($arr['title']);
				$this->titleurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&idcal=".$arr['id_cal']. "&evtid=".$arr['id']);
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

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "eventslist"));
}

function newEmails()
{
	global $babBody;

	class temp4
		{
		var $count;
		var $res;
		var $newmails;
		var $domain;
		var $domainurl;
		var $nbemails;

		function temp4()
			{
			global $babDB, $BAB_SESS_USERID, $BAB_HASH_VAR;
			include_once $GLOBALS['babInstallPath'].'utilit/inboxincl.php';
			
			$req = "select *, DECODE(password, \"".$babDB->db_escape_string($BAB_HASH_VAR)."\") as accpass from ".BAB_MAIL_ACCOUNTS_TBL." where owner='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->newmails = bab_translate("Waiting mails");
			}

		function getmail()
			{
			global $babDB;
			static $i=0;
			if( $i < $this->count )
				{
				$arr = $babDB->db_fetch_array($this->res);
				$req = "select * from ".BAB_MAIL_DOMAINS_TBL." where id='".$babDB->db_escape_string($arr['domain'])."'";
				$res2 = $babDB->db_query($req);
				$this->domain = "";
				$this->nbemails = "";
				$this->domainurl = "";
				if( $res2 && $babDB->db_num_rows($res2) > 0 )
					{
					$arr2 = $babDB->db_fetch_array($res2);
					$this->domain = bab_toHtml($arr2['name']);

					$mbox = bab_getMailBox($arr['id']);
					if($mbox)
						{
						$this->domainurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=inbox&&accid=".$arr['id']);
						$nbmsg = imap_num_recent($mbox); 
						$this->nbemails = bab_toHtml("( ". $nbmsg. " )");
						imap_close($mbox);
						}
					else
						{
						$this->nbemails = bab_toHtml("( ". imap_last_error(). " )");
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
		var $count;
		var $res;

		function temp6($nbdays)
			{
			global $babBody, $babDB, $BAB_SESS_USERID, $BAB_HASH_VAR;
			$this->nbdays = $nbdays;
			$req = "select f.* 
			from ".BAB_FILES_TBL." f, 
				".BAB_FMDOWNLOAD_GROUPS_TBL." fmg,  
				".BAB_USERS_GROUPS_TBL." ug 
			where 
				f.bgroup='Y' 
				and f.state='' 
				and f.confirmed='Y' 
				and fmg.id_object = f.id_owner 
				and ( fmg.id_group='2'
			";

			if( $BAB_SESS_USERID != "" )
			$req .= " or fmg.id_group='1' or (fmg.id_group=ug.id_group and ug.id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."')";
			$req .= ")";
			
			if( $this->nbdays > 0)
				$req .= " and f.modified >= DATE_ADD(\"".$babDB->db_escape_string($babBody->lastlog)."\", INTERVAL -".$babDB->db_escape_string($this->nbdays)." DAY)";
			else
				$req .= " and f.modified >= '".$babDB->db_escape_string($babBody->lastlog)."'";

			$req .= " group by f.id";

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			if( $nbdays > 0)
				$this->newfiles = bab_translate("Last files ( Since seven days before your last visit )");
			else
				$this->newfiles = bab_translate("New files");
			}

		function getfile()
			{
			global $babDB;
			static $i=0;
			if( $i < $this->count )
				{
				$arr = $babDB->db_fetch_array($this->res);
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
		if( $babBody->icalendars->calendarAccess())
		{
			upComingEvents();
		}
		$bemail = bab_mailAccessLevel();
		if( ($bemail == 1 || $bemail == 2) && function_exists('imap_open'))
			{
			newEmails();
			}
		$babBody->addItemMenu("view", bab_translate("Summary"), $GLOBALS['babUrlScript']."?tg=calview");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
