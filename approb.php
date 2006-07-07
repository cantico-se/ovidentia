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
include "base.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/afincl.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/artincl.php";
include_once $babInstallPath."utilit/vacincl.php";
include_once $babInstallPath."utilit/evtincl.php";
include_once $babInstallPath."utilit/calincl.php";

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
				$this->begindate = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false). " ". $babDayType[$row['day_begin']];
				$this->enddate = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false). " ". $babDayType[$row['day_end']];
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

	$mail->mailFrom($BAB_SESS_EMAIL, $BAB_SESS_USER);
	$mail->mailSubject($subject);

	$tempa = new tempa($row, $subject);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "infovacation"));
	$mail->mailBody($message, "html");

	$message = bab_printTemplate($tempa,"mailinfo.html", "infovacationtxt");
	$mail->mailAltBody($message);

	$mail->send();
	}


function listWaitingArticles()
{
	global $babBody;
	class listWaitingArticlesCls
		{
		var $waitingarticlestxt;
		var $artdatetxt;
		var $artnametxt;
		var $authortxt;
		var $validationtxt;
		var $artdate;
		var $wartres;
		var $wartcount;
		var $artpath;
		var $arttitle;
		var $author;
		var $confirmurl;
		var $artviewurl;
		var $battachment;

		function listWaitingArticlesCls()
			{
			global $babDB;
			$this->validationtxt = bab_translate("Validation");
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				$req = "select adt.*, count(adft.id) as totalf, count(adnt.id) as totaln from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id  left join ".BAB_ART_DRAFTS_NOTES_TBL." adnt on adnt.id_draft=adt.id where adt.trash !='Y' and adt.idfai IN(".implode(',', $arrschi).") GROUP BY adt.id order by date_submission desc";
				$this->wartres = $babDB->db_query($req);
				$this->wartcount = $babDB->db_num_rows($this->wartres);
				if( $this->wartcount > 0 )
					{
					$this->waitingarticlestxt = bab_translate("Waiting articles");
					$this->artdatetxt = bab_translate("Date");
					$this->artnametxt = bab_translate("Article");
					$this->authortxt = bab_translate("Author");
					$this->attachmenttxt = bab_translate("Attachments");
					$this->notestxt = bab_translate("Notes");
					}
				}
			$this->altbg = true;
			}

		function getnextarticle()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->wartcount)
				{
				$arr = $babDB->db_fetch_array($this->wartres);
				if( $arr['totalf'] >  0 )
					{
					$this->battachment = true;
					}
				else
					{
					$this->battachment = false;
					}
				if( $arr['totaln'] >  0 )
					{
					$this->bnotes = true;
					}
				else
					{
					$this->bnotes = false;
					}
				$this->artdate = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_submission']), true);
				$this->artpath = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->arttitle = $arr['title'];
				$this->author = bab_getUserName($arr['id_author']);
				$this->confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confart&idart=".$arr['id'];
				$this->artviewurl = $GLOBALS['babUrlScript']."?tg=approb&idx=viewart&idart=".$arr['id'];
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new listWaitingArticlesCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingarticles"));
}

function listWaitingComments()
{
	global $babBody;
	class listWaitingCommentsCls
		{
		var $waitingcommentstxt;
		var $comdatetxt;
		var $comnametxt;
		var $authortxt;
		var $validationtxt;
		var $comdate;
		var $wcomres;
		var $wcomcount;
		var $artpath;
		var $arttitle;
		var $author;
		var $confirmurl;
		var $artviewurl;

		function listWaitingCommentsCls()
			{
			global $babDB;
			$this->validationtxt = bab_translate("Validation");
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				$req = "select ct.* from ".BAB_COMMENTS_TBL." ct where ct.idfai IN(".implode(',', $arrschi).") order by date desc";
				$this->wcomres = $babDB->db_query($req);
				$this->wcomcount = $babDB->db_num_rows($this->wcomres);
				if( $this->wcomcount > 0 )
					{
					$this->waitingcommentstxt = bab_translate("Waiting comments");
					$this->comdatetxt = bab_translate("Date");
					$this->comnametxt = bab_translate("Comment");
					$this->authortxt = bab_translate("Author");
					}
				}
			$this->altbg = true;
			}

		function getnextcomment()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->wcomcount)
				{
				$arr = $babDB->db_fetch_array($this->wcomres);
				$this->comdate = $arr['date'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date']), true);
				$this->compath = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->comtitle = $arr['subject'];
				$this->author = $arr['name'];
				$this->confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confcom&idcom=".$arr['id'];
				$this->comviewurl = $GLOBALS['babUrlScript']."?tg=approb&idx=viewcom&idcom=".$arr['id'];
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new listWaitingCommentsCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingcomments"));
}


function listWaitingFiles()
{
	global $babBody;

	class listWaitingFilesCls
		{
		var $waitingfilestxt;
		var $filedatetxt;
		var $filenametxt;
		var $authortxt;
		var $validationtxt;
		var $filedate;
		var $wfilesres;
		var $wfilescount;
		var $filepath;
		var $filetitle;
		var $author;
		var $confirmurl;
		var $fileviewurl;

		function listWaitingFilesCls()
			{
			global $babDB;
			$this->validationtxt = bab_translate("Validation");
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			$this->wfilescount = 0;
			if( count($arrschi) > 0 )
				{
				$req = "select * from ".BAB_FILES_TBL." where bgroup='Y' and confirmed='N' and idfai IN(".implode(',', $arrschi).") order by created desc";
				$this->wfilesres = $babDB->db_query($req);
				$this->wfilescount = $babDB->db_num_rows($this->wfilesres);
				if( $this->wfilescount > 0 )
					{
					$this->waitingfilestxt = bab_translate("Waiting files");
					$this->filedatetxt = bab_translate("Date");
					$this->filenametxt = bab_translate("File");
					$this->authortxt = bab_translate("Author");
					}
				}
			$this->altbg = true;
			}

		function getnextfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->wfilescount)
				{
				$arr = $babDB->db_fetch_array($this->wfilesres);

				$this->filedate = $arr['created'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['created']), true);
				$this->filepath = $arr['path'];
				$this->filetitle = $arr['name'];
				$this->author = bab_getUserName($arr['author']);
				$this->fileviewurl = $GLOBALS['babUrlScript']."?tg=fileman&idx=viewfile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($arr['path'])."&file=".urlencode($arr['name']);
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new listWaitingFilesCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingfiles"));
}


function listWaitingPosts()
{
	global $babBody;

	class listWaitingPostsCls
		{
		var $waitingpoststxt;
		var $postdatetxt;
		var $postnametxt;
		var $authortxt;
		var $validationtxt;
		var $postdate;
		var $wpostsres;
		var $wpostscount;
		var $postpath;
		var $poststitle;
		var $author;
		var $confirmurl;
		var $postviewurl;

		function listWaitingPostsCls()
			{
			global $babDB;
			$this->validationtxt = bab_translate("Validation");
			$this->wpostscount = 0;
			$arrf = array();
			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL." where active='Y'");
			while( $arr = $babDB->db_fetch_array($res))
				{
				if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $arr['id']) )
					{
					$arrf[] = $arr['id'];
					}
				}
			if( count($arrf) > 0 )
				{
				$req = "select pt.*, pt2.subject as threadtitle, tt.id as threadid, tt.forum as forumid, ft.name as forumname from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on pt.id_thread=tt.id left join ".BAB_POSTS_TBL." pt2 on tt.post=pt2.id left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.confirmed='N' and ft.id IN(".implode(',', $arrf).") order by date desc";
				$this->wpostsres = $babDB->db_query($req);
				$this->wpostscount = $babDB->db_num_rows($this->wpostsres);
				if( $this->wpostscount > 0 )
					{
					$this->waitingpoststxt = bab_translate("Waiting posts");
					$this->postdatetxt = bab_translate("Date");
					$this->postnametxt = bab_translate("Post");
					$this->authortxt = bab_translate("Author");
					}
				}
			$this->altbg = true;
			}

		function getnextpost()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->wpostscount)
				{
				$arr = $babDB->db_fetch_array($this->wpostsres);
				$this->postdate = $arr['date'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date']), true);
				$this->postpath = $arr['forumname']." / ".$arr['threadtitle'];
				$this->posttitle = $arr['subject'];
				$this->author = $arr['author'];
				$this->confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confpost&idpost=".$arr['id']."&thread=".$arr['threadid'];
				$this->postviewurl = $GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$arr['forumid']."&thread=".$arr['threadid']."&post=".$arr['id'];
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new listWaitingPostsCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingposts"));
}

function listWaitingVacations()
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

		var $altbg = true;


		var $entryid;

		function temp()
			{
			$this->db = $GLOBALS['babDB'];
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				$this->res = $this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where idfai IN (".implode(',', $arrschi).") order by date desc");
				$this->wvacationscount = $this->db->db_num_rows($this->res);
				$this->waitingvacationstxt = bab_translate("Request vacations waiting to be validate");
				$this->validationtxt = bab_translate("Validation");
				$this->nametxt = bab_translate("Fullname");
				$this->datebtxt = bab_translate("Begin date");
				$this->dateetxt = bab_translate("End date");
				$this->totaltxt = bab_translate("Quantity");
				}
			else
				{
				$this->wvacationscount = 0;
				}
			}

		function getnextvacation()
			{
			static $i = 0;
			if( $i < $this->wvacationscount)
				{
				$this->altbg = !$this->altbg;
				$arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=approb&idx=confvac&idvac=".$arr['id'];
				list($this->total) = $this->db->db_fetch_row($this->db->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry ='".$arr['id']."'"));
				$this->urlname = bab_getUserName($arr['id_user']);
				$this->dateb = bab_shortDate(bab_mktime($arr['date_begin']." 00:00:00"), false);
				$this->datee = bab_shortDate(bab_mktime($arr['date_end']." 00:00:00"), false);
				$this->entryid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "approb.html", "waitingvacations"));
	return $temp->wvacationscount;
}

function listWaitingEvents()
{
	global $babBody;

	class listWaitingEventsCls
		{
		var $waitingpoststxt;
		var $eventdatetxt;
		var $eventtitletxt;
		var $eventauthortxt;
		var $validationtxt;
		var $eventdate;
		var $weventsres;
		var $weventscount;
		var $eventdescription;
		var $eventauthor;
		var $confirmurl;
		var $eventviewurl;

		function listWaitingEventsCls()
			{
			global $babDB;
			$this->validationtxt = bab_translate("Validation");
			$this->weventscount = 0;
			$this->arrevts = array();
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				$res = $babDB->db_query("SELECT cet.*, ceot.id_cal from ".BAB_CAL_EVENTS_TBL." cet , ".BAB_CAL_EVENTS_OWNERS_TBL." ceot where cet.id=ceot.id_event and ceot.idfai in (".implode(',', $arrschi).") order by cet.start_date asc");
				while( $arr = $babDB->db_fetch_array($res) )
					{
					$tmp = array();
					$tmp['title'] = $arr['title'];
					$tmp['description'] = $arr['description'];
					$tmp['startdate'] = bab_shortDate(bab_mktime($arr['start_date']), true);
					$tmp['enddate'] = bab_shortDate(bab_mktime($arr['end_date']), true);
					$tmp['author'] = bab_getUserName($arr['id_creator']);
					$tmp['idevent'] = $arr['id'];
					$tmp['idcal'] = $arr['id_cal'];
					$tmp['calendar'] = bab_getCalendarOwnerName($arr['id_cal']);
					$this->arrevts[] = $tmp;
					}
				}

			$this->weventscount = count($this->arrevts);
			if( $this->weventscount > 0 )
				{
				$this->waitingeventstxt = bab_translate("Waiting appointments");
				$this->eventdatetxt = bab_translate("Date");
				$this->eventtitletxt = bab_translate("Appointment");
				$this->eventauthortxt = bab_translate("Author");
				$this->eventcalendartxt = bab_translate("Calendar");
				}
			$this->altbg = true;
			}

		function getnextevent()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->weventscount)
				{
				$this->eventdate = $this->arrevts[$i]['startdate'];
				$this->eventdescription = $this->arrevts[$i]['description'];
				$this->eventtitle = $this->arrevts[$i]['title'];
				$this->eventauthor = $this->arrevts[$i]['author'];
				$this->eventcalendar = $this->arrevts[$i]['calendar'];
				$this->confirmurl = $GLOBALS['babUrlScript']."?tg=approb&idx=confevt&idevent=".$this->arrevts[$i]['idevent']."&idcal=".$this->arrevts[$i]['idcal'];
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}

	$temp = new listWaitingEventsCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingevents"));
}

function listWaitingAddons()
{
	global $babBody;

	class listWaitingAddonsCls
		{
		var $altbg = true;
		var $arrAddons = array();
		var $firstcall = false;

		var $addonTitle;
		var $url;
		var $text;
		var $description;

		function listWaitingAddonsCls()
			{
			$babBody = & $GLOBALS['babBody'];
			foreach( $babBody->babaddons as $key => $row)
				{
				$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
				if($row['access'] && is_file($addonpath."/init.php" ))
					{
					$this->_setGlobals($row['id'],$row['title']);
					require_once( $addonpath."/init.php" );
					
					if( function_exists($this->call) )
						{
						$this->arrAddons[$row['id']] = $row['title'];
						}
					}
				}
			}

		function _setGlobals($id,$title)
			{
			$GLOBALS['babAddonFolder'] = $title;
			$GLOBALS['babAddonTarget'] = "addon/".$id;
			$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$id."/";
			$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$title."/";
			$GLOBALS['babAddonHtmlPath'] = "addons/".$title."/";
			$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$title."/";

			$this->call = $title."_getWaitingItems";
			}

		function getnextaddon(&$skip)
			{
			$this->addonTitle = '';
			$this->arr = array();
			

			if (list($this->addonId, $title) = each($this->arrAddons))
				{
				$this->_setGlobals($this->addonId,$title);
				call_user_func_array($this->call, array(&$this->addonTitle, &$this->arr));

				if (count($this->arr) == 0)
					$skip = 1;
				
				return true;
				}
			return false;
			}

		function getnextitem()
			{
			$this->altbg = !$this->altbg;
			
			if (list( , $arr) = each($this->arr))
				{
				$this->text = $arr['text'];
				$this->description = $arr['description'];
				$this->url = $arr['url'];
				$this->popup = $arr['popup'];
				$this->idschi = $arr['idschi'];

				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new listWaitingAddonsCls();
	$babBody->babecho( bab_printTemplate($temp, "approb.html", "waitingAddons"));
}


class bab_confirmWaiting
{
	function getHtml($file, $template)
	{
	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";

	$GLOBALS['babBodyPopup'] = & new babBodyPopup();

	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

	$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($this, $file, $template));
	printBabBodyPopup();
	}
}


function confirmWaitingVacation($id)
	{
	global $babBody;

	class temp extends bab_confirmWaiting
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
			$this->t_alert = bab_translate("Negative balance");
			$this->db = $GLOBALS['babDB'];
			$row = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$id."'"));
			$this->datebegin = bab_strftime(bab_mktime($row['date_begin']." 00:00:00"), false);
			$this->halfnamebegin = $babDayType[$row['day_begin']];
			$this->dateend = bab_strftime(bab_mktime($row['date_end']." 00:00:00"), false);
			$this->halfnameend = $babDayType[$row['day_end']];
			$this->fullname = bab_getUserName($row['id_user']);
			$this->remark = nl2br($row['comment']);

			$rights = bab_getRightsOnPeriod($row['date_begin'], $row['date_end'], $row['id_user']);
			$this->negative = array();
			foreach ($rights as $r)
				{
				$after = $r['quantitydays'] - $r['waiting'];
				if ($after < 0)
					$this->negative[$r['id']] = $after;
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
				$this->alert = isset($this->negative[$arr['id_type']]) ? $this->negative[$arr['id_type']] : false;

				$this->totalval += $this->nbdays;
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$temp->getHtml("approb.html", "confirmvacation");
	return $temp->count;
	}

function confirmWaitingArticle($idart)
{
	global $babBody;
	class temp extends bab_confirmWaiting
		{
		var $arttxt;

		function temp($idart)
			{
			global $babDB;
			$res = $babDB->db_query("select id, title, idfai, id_topic, id_author from ".BAB_ART_DRAFTS_TBL." where id='".$idart."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				if( count($arrschi) > 0  && in_array($arr['idfai'], $arrschi) )
					{
					$this->idart = $idart;
					$this->arttxt = bab_translate("Article");
					$this->pathtxt = bab_translate("Path");
					$this->authortxt = bab_translate("Author");
					$this->confirmtxt = bab_translate("Confirm");
					$this->commenttxt = bab_translate("Comment");
					$this->yes = bab_translate("Yes");
					$this->no = bab_translate("No");
					$this->updatetxt = bab_translate("Update");

					$this->arttitle = $arr['title'];
					$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
					$this->author = bab_getUserName($arr['id_author']);				
					}
				else
					{
					$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
					}
				}
			else
				{
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($idart);
	$temp->getHtml("approb.html", "confirmarticle");
}


function confirmWaitingPost($thread, $post)
	{
	global $babBody;

	class confirmWaitingPostCls extends bab_confirmWaiting
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $close;

		function confirmWaitingPostCls($thread, $post)
			{
			$db = $GLOBALS['babDB'];
			$this->idpost = $post;
			$this->thread = $thread;

			$req = "select pt.*, ft.name as forumname from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.id='".$post."'";
			
			$arr = $db->db_fetch_array($db->db_query($req));
			
			$GLOBALS['babBody']->title = $arr['forumname'];
			$this->postdate = bab_strftime(bab_mktime($arr['date']));
			$this->postauthor = $arr['author'];
			$this->postsubject = bab_replace($arr['subject']);
			$this->postmessage = bab_replace($arr['message']);
			$this->close = bab_translate("Close");
			$this->action = bab_translate("Action");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->modify = bab_translate("Update");
			}
		}
	$temp = new confirmWaitingPostCls($thread, $post);
	$temp->getHtml("approb.html", "confirmpost");
	}



function confirmWaitingComment($idcom)
	{

	class confirmWaitingCommentCls extends bab_confirmWaiting
		{
		var $action;
		var $confirm;
		var $refuse;
		var $what;
		var $idcom;
		var $message;
		var $modify;
		var $db;
		var $count;

		function confirmWaitingCommentCls($idcom)
			{
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id='".$idcom."'";
			$res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($res);
			if( $this->count > 0)
				{
				$arr = $this->db->db_fetch_array($res);
				$this->idcom = $idcom;
				$this->name = bab_translate("Submiter");
				$this->modify = bab_translate("Update");
				$this->action = bab_translate("Action");
				$this->confirm = bab_translate("Confirm");
				$this->refuse = bab_translate("Refuse");
				$this->what = bab_translate("Send an email to author");
				$this->message = bab_translate("Message");
				$this->confval = "comment";
				}
			else
				{
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				}
			}
		}
	
	$temp = new confirmWaitingCommentCls($idcom);
	$temp->getHtml("approb.html", "confirmcomment");
	}


function confirmWaitingEvent($idevent, $idcal)
	{
	global $babBody;

	class temp extends bab_confirmWaiting
		{
		var $datebegintxt;

		function temp($idevent, $idcal)
			{
			global $babDB;
			$this->eventstartdatetxt = bab_translate("Begin date");
			$this->eventenddatetxt = bab_translate("End date");
			$this->eventdescriptiontxt = bab_translate("Description");
			$this->eventattendeestxt = bab_translate("Attendees");
			$this->confirm = bab_translate("Accept");
			$this->refuse = bab_translate("Decline");
			$this->commenttxt = bab_translate("Raison");
			$this->idevent = $idevent;
			$this->idcal = $idcal;
			$res = $babDB->db_query("select cet.*, ceot.id_cal from ".BAB_CAL_EVENTS_TBL." cet left join ".BAB_CAL_EVENTS_OWNERS_TBL." ceot on cet.id=ceot.id_event where ceot.id_cal='".$idcal."' and ceot.id_event='".$idevent."'");
			$arr = $babDB->db_fetch_array($res);
			$GLOBALS['babBody']->title = $arr['title'];
			$this->eventstartdate = bab_shortDate(bab_mktime($arr['start_date']), true);
			$this->eventenddate = bab_shortDate(bab_mktime($arr['end_date']), true);
			$this->eventdescription = $arr['description'];

			if( !empty($arr['hash']) &&  $arr['hash'][0] == 'R' )
				{
				$this->recurrent = true;
				$this->warningmsg = bab_translate("Warning! This appointment is recurrent !");
				}
			else
				{
				$this->recurrent = false;
				}

			$this->resatt = $babDB->db_query("select * from ".BAB_CAL_EVENTS_OWNERS_TBL." where id_event='".$idevent."' and id_cal!='".$idcal."'");
			$this->count = $babDB->db_num_rows($this->resatt);
			}

		function getnextattendee()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->resatt);
				$this->eventattendee = bab_getCalendarOwnerName($arr['id_cal']);
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($idevent, $idcal);
	$temp->getHtml("approb.html", "confirmevent");
	return $temp->count;
	}

function previewWaitingArticle($idart)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 && in_array($arr['idfai'],$arrschi))
			{
			include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			$GLOBALS['babBodyPopup']->title = & $babBody->title;
			$GLOBALS['babBodyPopup']->msgerror = & $babBody->msgerror;

			$GLOBALS['babBodyPopup']->babecho(bab_previewArticleDraft($idart, 0));
			printBabBodyPopup();
			}
		}
	else
		{
		echo bab_translate("Access denied");
		}
	}

function previewWaitingComment($idcom)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id='".$idcom."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 && in_array($arr['idfai'],$arrschi))
			{
			$babBody->babecho(bab_previewComment($idcom));
			return;
			}
		}
	echo bab_translate("Access denied");
	}

function updateConfirmationWaitingArticle($idart, $bconfirm, $comment)
	{
	global $babDB;

	$res = $babDB->db_query("select id, idfai, id_author, id_article from ".BAB_ART_DRAFTS_TBL." where id='".$idart."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 && in_array($arr['idfai'],$arrschi))
			{
			$bret = $bconfirm == "Y"? true: false;
			
			$comment = $babDB->db_escape_string($comment);
			$babDB->db_query("insert into ".BAB_ART_DRAFTS_NOTES_TBL." (id_draft, content, id_author, date_note) values ('".$idart."','".$comment."','".$GLOBALS['BAB_SESS_USERID']."', now())");

			$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $bret);
			switch($res)
				{
				case 0:
					$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='".BAB_ART_STATUS_NOK."', idfai='0' where id = '".$idart."'");
					if( $arr['id_article'] != 0 )
						{
						$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log, art_log) values ('".$arr['id_article']."', '".$arr['id_author']."', now(), 'refused', '".$comment."')");		
						}
					deleteFlowInstance($arr['idfai']);				
					notifyArticleDraftAuthor($idart, 0);
					break;
				case 1:
					$articleid = acceptWaitingArticle($idart);
					if( $articleid == 0)
						{
						return false;
						}
					deleteFlowInstance($arr['idfai']);				
					notifyArticleDraftAuthor($idart, 1);
					bab_deleteArticleDraft($idart);
					if( $arr['id_article'] != 0 )
						{
						$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$arr['id_article']."', '".$arr['id_author']."', now(), 'accepted')");		
						}
					break;
				default:
					$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
					if( count($nfusers) > 0 )
						{
						notifyArticleDraftApprovers($idart, $nfusers);
						}
					break;
				}

			return true;
			}
		}
		return false;
	}


function updateConfirmationWaitingComment($idcom, $action, $send, $message)
	{
	global $babBody, $new, $BAB_SESS_USERID, $babAdminEmail;

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_COMMENTS_TBL." where id='".$idcom."'";
	$res = $db->db_query($query);
	$arr = $db->db_fetch_array($res);

	$bret = $action == "1"? true: false;
	$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $bret);
	switch($res)
		{
		case 0:
			include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
			$subject = "Your comment has been refused";
			deleteFlowInstance($arr['idfai']);
			bab_deleteComments($idcom);
			break;
		case 1:
			$subject = "Your comment has been accepted";
			deleteFlowInstance($arr['idfai']);
			$db->db_query("update ".BAB_COMMENTS_TBL." set confirmed='Y', idfai='0' where id = '".$idcom."'");
			break;
		default:
			$subject = "About your comment";
			$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
			if( count($nfusers) > 0 )
				{
				notifyCommentApprovers($com, $nfusers);
				}
			break;
		}

	if( $send == "1" && $arr['email'] != "")
		{
		$msg = nl2br($message);
        notifyCommentAuthor($subject, $msg, $BAB_SESS_USERID, $arr['email']);
		}
	}

function updateConfirmationWaitingPost($thread, $post)
	{
	global $babBody, $babDB;

	$babDB->db_query("update ".BAB_THREADS_TBL." set lastpost='".$post."' where id='".$thread."'");
	$babDB->db_query("update ".BAB_POSTS_TBL." set confirmed='Y' where id='".$post."'");

	$res = $babDB->db_query("select tt.forum, tt.starter, tt.notify, pt.subject from ".BAB_THREADS_TBL." tt left join ".BAB_POSTS_TBL." pt on tt.post=pt.id where tt.id='".$thread."'");
	$arrf = $babDB->db_fetch_array($res);
	$arrpost = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_POSTS_TBL." where id='".$post."'"));
	include_once $GLOBALS['babInstallPath']."utilit/forumincl.php";
	if( $arrf['notify'] == "Y" && $arrf['starter'] != 0)
		{
		$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$arrf['starter']."'");
		$arr = $babDB->db_fetch_array($res);
		$email = $arr['email'];
		notifyThreadAuthor($arrf['subject'], $email, $arrpost['author']);
		}
	$url = $GLOBALS['babUrlScript'] ."?tg=posts&idx=List&forum=".$arrf['forum']."&thread=".$thread."&flat=1&views=1";
	notifyForumGroups($arrf['forum'], $arrpost['subject'], $arrpost['author'], bab_getForumName($arrf['forum']), array(BAB_FORUMSNOTIFY_GROUPS_TBL), $url);
	}

function confirmVacationRequest($veid, $remarks, $action)
{
	global $babBody, $babDB, $approbinit;

	$res = $babDB->db_query("select idfai, id_user, date_begin, date_end, day_begin, day_end from ".BAB_VAC_ENTRIES_TBL." where id='".$veid."'");
	$arr = $babDB->db_fetch_array($res);
	if( !in_array($arr['idfai'], $approbinit))
	{
		return false;
	}

	$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $action);

	switch($res)
		{
		case 0:
			deleteFlowInstance($arr['idfai']);

			$remarks = $babDB->db_escape_string($remarks);

			$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set status='N', idfai='0', id_approver='".$GLOBALS['BAB_SESS_USERID']."', comment2='".$remarks."' where id = '".$veid."'");
			$subject = bab_translate("Your vacation request has been refused");
			notifyVacationAuthor($veid, $subject);
			break;
		case 1:
			deleteFlowInstance($arr['idfai']);

			$remarks = $babDB->db_escape_string($remarks);

			$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set status='Y', idfai='0', id_approver='".$GLOBALS['BAB_SESS_USERID']."', comment2='".$remarks."' where id = '".$veid."'");
			$idcal = bab_getCalendarId($arr['id_user'], 1);
			if( $idcal != 0 )
				{
				list($idcat) = $babDB->db_fetch_row($babDB->db_query("select vct.id_cat from ".BAB_VAC_COLLECTIONS_TBL." vct left join ".BAB_VAC_PERSONNEL_TBL." vpt on vpt.id_coll=vct.id left join ".BAB_VAC_ENTRIES_TBL." vet on vet.id_user=vpt.id_user where vet.id='".$veid."'"));

				$tbegin = $arr['day_begin'] == 3? '12:00:00': '00:00:00';
				$tend = $arr['day_end'] == 2? '12:00:00': '23:59:59';
				$req = "insert into ".BAB_CAL_EVENTS_TBL." ( title, id_cat, start_date, end_date, id_creator, hash) values ";
				$req .= "('".bab_translate("Vacation")."', '".$idcat."', '".$arr['date_begin']." ".$tbegin."', '".$arr['date_end']." ".$tend."', '0', 'V_".$veid."')";
				$babDB->db_query($req);
				$id_event = $babDB->db_insert_id();
				$babDB->db_query("INSERT INTO ".BAB_CAL_EVENTS_OWNERS_TBL." (id_event,id_cal, status) VALUES ('".$id_event."','".$idcal."', '".BAB_CAL_STATUS_ACCEPTED."')");
				}
			$subject = bab_translate("Your vacation request has been accepted");
			notifyVacationAuthor($veid, $subject);
			break;
		default:
			$nfusers = getWaitingApproversFlowInstance($arr['idfai'], true);
			if( count($nfusers) > 0 )
				{
				notifyVacationApprovers($veid, $nfusers);
				}
			break;
		}
}


function approb_init()
{
	$arapprob = array();
	$arapprob = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	return $arapprob;
}

/* main */
$approbinit = approb_init();
if(!isset($idx))
	{
	$idx = "all";
	}

if( isset($conf))
{
	if( $conf == 'art')
	{
		if( !isset($bconfirm)) { $bconfirm = 'N';}
		updateConfirmationWaitingArticle($idart, $bconfirm, $comment);
		$idx = 'unload';
	}
	elseif( $conf == 'com' )
	{
		if( !isset($send)) { $send = '';}
		updateConfirmationWaitingComment($idcom, $action, $send, $message);
		$idx = 'unload';
	}
	elseif( $conf == 'post' )
	{
		updateConfirmationWaitingPost($thread, $idpost);
		$idx = 'unload';
	}
	elseif( $conf == 'vac' )
	{
		if( isset($confirm))
			{
			confirmVacationRequest($veid, $remarks, true);
			}
		elseif( isset($refuse))
		{
			confirmVacationRequest($veid, $remarks, false);
		}
		$idx = 'unload';
	}
	elseif( $conf == 'evt' )
	{
		if( isset($confirm))
			{
			confirmEvent($idevent, $idcal, 'Y', $remarks, 1);
			}
		elseif( isset($refuse))
		{
			confirmEvent($idevent, $idcal, 'N', $remarks, 1);
		}
		$idx = 'unload';
	}
}

switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		popupUnload(bab_translate("Update done"), $GLOBALS['babUrlScript']."?tg=approb&idx=all");
		exit;
	case "confevt":
		confirmWaitingEvent($idevent, $idcal);
		exit;
		break;

	case "confart":
		confirmWaitingArticle($idart);
		exit;
		break;

	case "confcom":
		confirmWaitingComment($idcom);
		exit;
		break;

	case "confpost":
		confirmWaitingPost($thread, $idpost);
		exit;
		break;

	case "viewart":
		previewWaitingArticle($idart);
		exit;
		break;

	case "viewcom":
		previewWaitingComment($idcom);
		exit;
		break;

	case "confvac":
		include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
		confirmWaitingVacation($idvac);
		exit;
		break;

	case "all":
	default:
		$babBody->title = bab_translate("Approbations");

		if( bab_isWaitingApprobations()  || count($approbinit) > 0 )
		{
		listWaitingArticles();
		listWaitingComments();
		listWaitingFiles();
		listWaitingPosts();
		listWaitingVacations();
		listWaitingEvents();
		listWaitingAddons();		
		}
		$babBody->addItemMenu("all", bab_translate("Approbations"), $GLOBALS['babUrlScript']."?tg=approb&idx=all");
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>