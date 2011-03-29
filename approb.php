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
* @internal SEC1 NA 14/12/2006 FULL
*/
include 'base.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/afincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';
include_once $babInstallPath.'utilit/vacincl.php';
include_once $babInstallPath.'utilit/evtincl.php';
include_once $babInstallPath.'utilit/calincl.php';
include_once $babInstallPath.'utilit/forumincl.php';

include_once $babInstallPath.'utilit/eventincl.php';


/**
 * Event fired when the approbation page is displayed
 * @since 6.1.1
 * @package events
 */
class bab_eventBeforeWaitingItemsDisplayed extends bab_event {

	/**
	 * @public
	 */
	var $objects = array();
	
	function addObject($title,$arr) {
		static $i = 0;
		$key = mb_strtolower(mb_substr($title,0,3));
		$this->objects[$key.$i] = array(
			'title' => $title,
			'arr'	=> $arr
		);
		
		$i++;
	}
}



function notifyVacationAuthor($id, $subject)
	{
	global $babBody, $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $babAdminEmail;

	if(!class_exists('tempa'))
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
				$this->message = $subject;
				$this->fromuser = bab_translate("User");
				$this->from = bab_translate("from");
				$this->until = bab_translate("until");
				$this->begindate = bab_longDate(bab_mktime($row['date_begin']));
				$this->enddate = bab_longDate(bab_mktime($row['date_end']));
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
	$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'"));

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
				$req = "select adt.*, count(adft.id) as totalf, count(adnt.id) as totaln from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id  left join ".BAB_ART_DRAFTS_NOTES_TBL." adnt on adnt.id_draft=adt.id where adt.trash !='Y' and adt.idfai IN(".$babDB->quote($arrschi).") GROUP BY adt.id order by date_submission desc";
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
				$this->artdate = $arr['date_submission'] == '0000-00-00 00:00:00'? '':bab_toHtml(bab_shortDate(bab_mktime($arr['date_submission']), true));
				$this->artpath = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->arttitle = bab_toHtml($arr['title']);
				$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				$this->confirmurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=approb&idx=confart&idart=".$arr['id']);
				$this->artviewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=approb&idx=viewart&idart=".$arr['id']);
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
				$req = "select ct.* from ".BAB_COMMENTS_TBL." ct where ct.idfai IN(".$babDB->quote($arrschi).") order by date desc";
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
				$this->comdate = $arr['date'] == '0000-00-00 00:00:00'? '':bab_toHtml(bab_shortDate(bab_mktime($arr['date']), true));
				$this->compath = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->comtitle = bab_toHtml($arr['subject']);
				if( $arr['id_author'] )
					{
					$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
					}
				else
					{
					$this->author = bab_toHtml($arr['name']);
					}
				$this->confirmurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=approb&idx=confcom&idcom=".$arr['id']);
				$this->comviewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=approb&idx=viewcom&idcom=".$arr['id']);
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
			$this->wfilescount = false;
			if( count($arrschi) > 0 )
				{
				$req = "select * from ".BAB_FILES_TBL." where bgroup='Y' and confirmed='N' and idfai IN(".$babDB->quote($arrschi).") order by created desc";
				$this->wfilesres = $babDB->db_query($req);
				$this->wfilesnorcount = $babDB->db_num_rows($this->wfilesres);

				$req = "select fft.*, ft.path, ft.name from ".BAB_FM_FILESVER_TBL." fft left join ".BAB_FILES_TBL." ft on ft.id=fft.id_file where fft.confirmed='N' and fft.idfai IN(".$babDB->quote($arrschi).") order by date desc";

				$this->wfilesverres = $babDB->db_query($req);
				$this->wfilesvercount = $babDB->db_num_rows($this->wfilesverres);
				if( $this->wfilesvercount > 0 || $this->wfilesnorcount > 0 )
					{
					$this->wfilescount = true;
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
			if( $i < $this->wfilesnorcount)
				{
				$arr = $babDB->db_fetch_array($this->wfilesres);

				$this->filedate = $arr['created'] == '0000-00-00 00:00:00'? '':bab_toHtml(bab_shortDate(bab_mktime($arr['created']), true));
				$this->filepath = bab_toHtml($arr['path']);
				$this->filetitle = bab_toHtml($arr['name']);
				$this->author = bab_toHtml(bab_getUserName($arr['author']));
				
				$this->fileviewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=viewFile&idf=".$arr['id']."&id=".$arr['id_owner']."&gr=".$arr['bgroup']."&path=".urlencode($this->cleanFmPath($arr['path']))."&file=".urlencode($arr['name']));
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}

		function getnextfilever()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->wfilesvercount)
				{
				$arr = $babDB->db_fetch_array($this->wfilesverres);

				$this->filedate = $arr['date'] == '0000-00-00 00:00:00'? '':bab_toHtml(bab_shortDate(bab_mktime($arr['date']), true));
				$this->filepath = bab_toHtml($arr['path']);
				$this->filetitle = bab_toHtml($arr['name']);
				$this->fileversion = bab_toHtml($arr['ver_major'].".".$arr['ver_minor']);
				$this->author = bab_toHtml(bab_getUserName($arr['author']));

				include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
				$fm_file = fm_getFileAccess($arr['id_file']);
				$oFmFolder =& $fm_file['oFmFolder'];
				$oFolderFile =& $fm_file['oFolderFile'];
				$sPathName = getUrlPath($oFolderFile->getPathName());	
				$iIdUrl = $oFmFolder->getId();
				if(mb_strlen($oFmFolder->getRelativePath()) > 0)
				{
					$oRootFmFolder = BAB_FmFolderSet::getFirstCollectiveParentFolder($oFmFolder->getRelativePath());
					if(!is_null($oRootFmFolder))
					{
						$iIdUrl = $oRootFmFolder->getId();
					}
				}

				$this->fileviewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=filever&idx=conf&id=".$iIdUrl."&gr=".$oFolderFile->getGroup()."&path=".urlencode($sPathName)."&idf=".$arr['id_file']);
				$this->altbg = !$this->altbg;
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		function cleanFmPath($sPath)
			{
			return mb_substr($sPath, 0, -1);	
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
				$req = "select pt.*, pt2.subject as threadtitle, tt.id as threadid, tt.forum as forumid, ft.name as forumname from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on pt.id_thread=tt.id left join ".BAB_POSTS_TBL." pt2 on tt.post=pt2.id left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.confirmed='N' and ft.id IN(".$babDB->quote($arrf).") order by date desc";
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
				$this->postdate = $arr['date'] == '0000-00-00 00:00:00'? '':bab_toHtml(bab_shortDate(bab_mktime($arr['date']), true));
				$this->postpath = bab_toHtml($arr['forumname'].' / '.$arr['threadtitle']);
				$this->posttitle = bab_toHtml($arr['subject']);
				$this->author = bab_getForumContributor($arr['forumid'], $arr['id_author'], $arr['author']);
				$this->author = bab_toHtml($this->author);
				$this->confirmurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=approb&idx=confpost&idpost=".$arr['id']."&thread=".$arr['threadid']);
				$this->postviewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=viewp&forum=".$arr['forumid']."&thread=".$arr['threadid']."&post=".$arr['id']);
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
			global $babDB;
			$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
			if( count($arrschi) > 0 )
				{
				include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
				$this->res = $babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where idfai IN (".$babDB->quote($arrschi).") order by date desc");
				$this->wvacationscount = $babDB->db_num_rows($this->res);
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
			global $babDB;
			static $i = 0;
			if( $i < $this->wvacationscount)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml($GLOBALS['babUrlScript']."?tg=approb&idx=confvac&idvac=".$arr['id']);
				list($this->total) = $babDB->db_fetch_row($babDB->db_query("select sum(quantity) from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry =".$babDB->quote($arr['id']).""));
				$this->total = bab_toHtml($this->total);
				$this->urlname = bab_toHtml(bab_getUserName($arr['id_user']));
				$this->dateb = bab_toHtml(bab_vac_shortDate(bab_mktime($arr['date_begin'])));
				$this->datee = bab_toHtml(bab_vac_shortDate(bab_mktime($arr['date_end'])));
				$this->entryid = bab_toHtml($arr['id']);
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
				$res = $babDB->db_query("SELECT cet.*, ceot.caltype, ceot.id_cal 
					from 
						".BAB_CAL_EVENTS_TBL." cet ,
					 	".BAB_CAL_EVENTS_OWNERS_TBL." ceot 
					 where 
					 	cet.id=ceot.id_event and ceot.idfai in (".$babDB->quote($arrschi).") order by cet.start_date asc
				");
				
				while( $arr = $babDB->db_fetch_array($res) )
				{
						
					$calendar = bab_getICalendars()->getEventCalendar($arr['caltype'].'/'.$arr['id_cal']);
						
					if ($calendar)
					{
						$tmp = array();
						$tmp['uuid'] = $arr['uuid'];
						$tmp['title'] = $arr['title'];
						$tmp['description'] = $arr['description'];
						$tmp['description_format'] = $arr['description_format'];
						$tmp['startdate'] = bab_shortDate(bab_mktime($arr['start_date']), true);
						$tmp['enddate'] = bab_shortDate(bab_mktime($arr['end_date']), true);
						$tmp['author'] = bab_getUserName($arr['id_creator']);
						$tmp['idevent'] = $arr['id'];
						$tmp['idcal'] = $arr['parent_calendar'];
						$tmp['relation'] = $calendar->getUrlIdentifier();
						$tmp['calendar'] = $calendar->getName();
						$this->arrevts[] = $tmp;
					}
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
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			}

		function getnextevent()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->weventscount)
				{
				require_once dirname(__FILE__).'/utilit/dateTime.php';
				$start = BAB_DateTime::fromIsoDateTime($this->arrevts[$i]['startdate']);
				$this->eventdate = bab_toHtml($this->arrevts[$i]['startdate']);
				
				$editor = new bab_contentEditor('bab_calendar_event');
				$editor->setContent($this->arrevts[$i]['description']);
				$editor->setFormat($this->arrevts[$i]['description_format']);
				$this->eventdescription = $editor->getHtml();
				
				$this->eventtitle = bab_toHtml($this->arrevts[$i]['title']);
				$this->eventauthor = bab_toHtml($this->arrevts[$i]['author']);
				$this->eventcalendar = bab_toHtml($this->arrevts[$i]['calendar']);
				$this->confirmurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=approb&evtid=".$this->arrevts[$i]['uuid']."&idcal=".$this->arrevts[$i]['idcal']."&relation=".$this->arrevts[$i]['relation']);
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
		var $arrObjects = array();
		var $firstcall = false;

		var $addonTitle;
		var $url;
		var $text;
		var $description;

		function listWaitingAddonsCls()
			{
			global $babBody;
			
			$event = new bab_eventBeforeWaitingItemsDisplayed();
			bab_fireEvent($event);
			$this->arrObjects = &$event->objects;
		
			/**
			 * @deprecated
			 * Addons should not use this method since 6.1.1
			 */
			include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
			foreach(bab_addonsInfos::getRows() as $key => $row)
				{
				$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
				if($row['access'] && is_file($addonpath."/init.php" ))
					{
					$this->_setGlobals($row['id'],$row['title']);
					require_once( $addonpath."/init.php" );
					
					if( function_exists($this->call) )
						{
						
						bab_debug('The callback '.$this->call.' is deprecated, please use bab_addEventListener() instead');
						
						$title = $row['title'];
						$arr = array();
						call_user_func_array($this->call, array(&$title, &$arr));
						if (count($arr) > 0) {
								$key = mb_strtolower(mb_substr($title,0,3));
								$this->arrObjects[$key.$row['id']] = array(
									'title' => $title,
									'arr'	=> $arr
								);
							}
						}
					}
				}
				
			bab_sort::ksort($this->arrObjects);
			
			}

		function _setGlobals($id,$title)
			{
			include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
			bab_setAddonGlobals($id);
			$this->call = $title."_getWaitingItems";
			}

		function getnextaddon()
			{
			$this->addonTitle = '';
			$this->arr = array();

			if (list(, $arr) = each($this->arrObjects))
				{
				$this->addonTitle = bab_toHtml($arr['title']);
				$this->arr = $arr['arr'];
				return true;
				}
			return false;
			}

		function getnextitem()
			{
			$this->altbg = !$this->altbg;
			
			if (!isset($this->arr)) {
				return false;
			}
			
			if (list( , $arr) = each($this->arr))
				{
				$this->text 			= bab_toHtml($arr['text']);
				$this->description 		= $arr['description'];
				$this->url 				= bab_toHtml($arr['url']);
				$this->popup 			= $arr['popup'];
				$this->idschi 			= bab_toHtml($arr['idschi']);

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

	$GLOBALS['babBodyPopup'] = new babBodyPopup();

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
			global $babDB;
			$this->datebegintxt = bab_translate("Begin date");
			$this->dateendtxt = bab_translate("End date");
			$this->nbdaystxt = bab_translate("Quantities");
			$this->totaltxt = bab_translate("Total");
			$this->commenttxt = bab_translate("Additional information");
			$this->confirm = bab_translate("Confirm");
			$this->refuse = bab_translate("Refuse");
			$this->remarktxt = bab_translate("Description");
			$this->t_alert = bab_translate("Negative balance");
			$this->t_nomatch = bab_translate("The length of the period is different from the requested vacation");
			$row = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
			$this->begin = bab_mktime($row['date_begin']);
			$this->end = bab_mktime($row['date_end']);
			$this->datebegin	= bab_toHtml(bab_vac_longDate($this->begin));
			$this->dateend		= bab_toHtml(bab_vac_longDate($this->end));
			$this->id_user		= $row['id_user'];
			$this->fullname		= bab_toHtml(bab_getUserName($row['id_user']));
			$this->remark = bab_toHtml($row['comment'], BAB_HTML_ALL);
			
			 $this->totaldates = bab_vac_getFreeDaysBetween($this->id_user, $this->begin, $this->end, true);

			$rights = bab_getRightsOnPeriod($row['date_begin'], $row['date_end'], $row['id_user']);
			$this->negative = array();
			foreach ($rights as $r)
				{
				$after = $r['quantitydays'] - $r['waiting'];
				if ($after < 0)
					$this->negative[$r['id']] = $after;
				}

			$req = "
				SELECT e.*, r.description FROM 
					".BAB_VAC_ENTRIES_ELEM_TBL." e 
					LEFT JOIN ".BAB_VAC_RIGHTS_TBL." r ON r.id=e.id_right
				WHERE e.id_entry=".$babDB->quote($id);
				
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->totalval = 0;
			$this->veid = bab_toHtml($id);
			$this->nomatch = false;
			}

		function getnexttype()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				
				$this->nbdays = $arr['quantity'];
				$this->alert = isset($this->negative[$arr['id_right']]) ? $this->negative[$arr['id_right']] : false;

				$this->totalval += $this->nbdays;
				$this->typename = bab_toHtml($arr['description']);
				$i++;
				return true;
				}
			else
				return false;

			}

		function getmatch() {
			$this->nomatch = $this->totalval !== $this->totaldates;
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
			$res = $babDB->db_query("select id, title, idfai, id_topic, id_author from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
				if( count($arrschi) > 0  && in_array($arr['idfai'], $arrschi) )
					{
					$this->idart = bab_toHtml($idart);
					$this->arttxt = bab_translate("Article");
					$this->pathtxt = bab_translate("Path");
					$this->authortxt = bab_translate("Author");
					$this->confirmtxt = bab_translate("Confirm");
					$this->commenttxt = bab_translate("Comment");
					$this->yes = bab_translate("Yes");
					$this->no = bab_translate("No");
					$this->updatetxt = bab_translate("Update");

					$this->arttitle = bab_toHtml($arr['title']);
					$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
					$this->author = bab_toHtml(bab_getUserName($arr['id_author']));				
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
			global $babDB;
			$this->idpost = bab_toHtml($post);
			$this->thread = bab_toHtml($thread);

			$req = "select pt.*, ft.id as forumid, ft.name as forumname from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.id='".$babDB->db_escape_string($post)."'";
			
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			
			$GLOBALS['babBody']->title = $arr['forumname'];
			$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->postauthor = bab_getForumContributor($arr['forumid'], $arr['id_author'], $arr['author']);
			$this->postauthor = bab_toHtml($this->postauthor);
			$this->postsubject = bab_toHtml($arr['subject']);
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_forum_post');
			$editor->setContent($arr['message']);
			$this->postmessage = $editor->getHtml();
			
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
			global $babDB;
			$req = "select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($idcom)."'";
			$res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($res);
			if( $this->count > 0)
				{
				$arr = $babDB->db_fetch_array($res);
				$this->idcom = bab_toHtml($idcom);
				$this->name = bab_translate("Submiter");
				$this->modify = bab_translate("Update");
				$this->action = bab_translate("Action");
				$this->confirm = bab_translate("Confirm");
				$this->refuse = bab_translate("Refuse");
				$this->what = bab_translate("Send an email to author");
				$this->message = bab_translate("Message");
				$this->confval = 'comment';
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



function previewWaitingArticle($idart)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
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
	$res = $babDB->db_query("select idfai from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($idcom)."'");
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

	$res = $babDB->db_query("select id, idfai, id_author, id_article from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$arrschi = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
		if( count($arrschi) > 0 && in_array($arr['idfai'],$arrschi))
			{
			$bret = $bconfirm == "Y"? true: false;
			
			$babDB->db_query("insert into ".BAB_ART_DRAFTS_NOTES_TBL." (id_draft, content, id_author, date_note) values ('".$babDB->db_escape_string($idart)."','".$babDB->db_escape_string($comment)."','".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', now())");

			$res = updateFlowInstance($arr['idfai'], $GLOBALS['BAB_SESS_USERID'], $bret);
			switch($res)
				{
				case 0:
					$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='".BAB_ART_STATUS_NOK."', idfai='0' where id = '".$babDB->db_escape_string($idart)."'");
					if( $arr['id_article'] != 0 )
						{
						$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log, art_log) values ('".$babDB->db_escape_string($arr['id_article'])."', '".$babDB->db_escape_string($arr['id_author'])."', now(), 'refused', '".$babDB->db_escape_string($comment)."')");		
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
						$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$babDB->db_escape_string($arr['id_article'])."', '".$babDB->db_escape_string($arr['id_author'])."', now(), 'accepted')");		
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
	global $babBody, $babDB, $new, $BAB_SESS_USERID, $babAdminEmail;

	$query = "select * from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($idcom)."'";
	$res = $babDB->db_query($query);
	$arr = $babDB->db_fetch_array($res);

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
			$babDB->db_query("update ".BAB_COMMENTS_TBL." set confirmed='Y', idfai='0' where id = '".$babDB->db_escape_string($idcom)."'");
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

	$thread = intval($thread);
	$post = intval($post);
	if( $thread && $post )
		{
		$res = $babDB->db_query("select tt.forum, tt.starter, tt.notify, pt.subject from ".BAB_THREADS_TBL." tt left join ".BAB_POSTS_TBL." pt on tt.post=pt.id where tt.id='".$babDB->db_escape_string($thread)."'");
		$arrf = $babDB->db_fetch_array($res);
		$action = bab_pp('action', '');

		if( $action !== '' )
			{
			if( $action == 1 ) // Confirm
				{
					bab_confirmPost($arrf['forum'], $thread, $post);
				}
			else // refuse
				{
					bab_deletePost($arrf['forum'], $post);
				}
			}

		}
	}

function confirmVacationRequest($veid, $remarks, $action)
{
	global $babBody, $babDB, $approbinit;
	require_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';

	$res = $babDB->db_query("select idfai, id_user, date_begin, date_end FROM ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($veid)."'");
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

			$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set status='N', idfai='0', id_approver='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', comment2='".$babDB->db_escape_string($remarks)."' where id = '".$babDB->db_escape_string($veid)."'");
			$subject = bab_translate("Your vacation request has been refused");
			notifyVacationAuthor($veid, $subject);
			break;
		case 1:
			deleteFlowInstance($arr['idfai']);

			$babDB->db_query("update ".BAB_VAC_ENTRIES_TBL." set status='Y', idfai='0', id_approver='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', comment2='".$babDB->db_escape_string($remarks)."' where id = '".$babDB->db_escape_string($veid)."'");

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
		
	// try to update event copy in other backend (caldav)
	
	$begin = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
	$end = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
	$period = bab_vac_getPeriod($veid, $arr['id_user'],  $begin, $end);
	if ($period)
	{
		// select the updated row 
		$res = $babDB->db_query("select * FROM ".BAB_VAC_ENTRIES_TBL." where id=".$babDB->quote($veid));
		$row = $babDB->db_fetch_array($res);
		
		// probably set a new description if the event has been approved or rejected
		bab_vac_setPeriodProperties($period, $row, $begin);
		
		// save copy of event to calendar backend (if caldav)
		$period->save();
	}
		

	bab_vac_updateEventCalendar($veid);
}


function approb_init()
{
	$arapprob = array();
	$arapprob = bab_getWaitingIdSAInstance($GLOBALS['BAB_SESS_USERID']);
	return $arapprob;
}

/* main */
$approbinit = approb_init();
$idx = bab_rp('idx', 'all');

if( '' != ($conf = bab_pp('conf')))
{
	if( $conf == 'art')
	{
		$bconfirm = bab_pp('bconfirm', 'N');
		updateConfirmationWaitingArticle(bab_pp('idart'), $bconfirm, bab_pp('comment'));
		$idx = 'unload';
	}
	elseif( $conf == 'com' )
	{
		updateConfirmationWaitingComment(bab_pp('idcom'), bab_pp('action'), bab_pp('send'), bab_pp('message'));
		$idx = 'unload';
	}
	elseif( $conf == 'post' )
	{
		updateConfirmationWaitingPost(bab_pp('thread'), bab_pp('idpost'));
		$idx = 'unload';
	}
	elseif( $conf == 'vac' )
	{
		if( isset($_POST['confirm']))
			{
			confirmVacationRequest(bab_pp('veid'), bab_pp('remarks'), true);
			}
		elseif( isset($_POST['refuse']))
		{
			confirmVacationRequest(bab_pp('veid'), bab_pp('remarks'), false);
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

	case "confart":
		confirmWaitingArticle(bab_gp('idart'));
		exit;
		break;

	case "confcom":
		confirmWaitingComment(bab_gp('idcom'));
		exit;
		break;

	case "confpost":
		confirmWaitingPost(bab_gp('thread'), bab_gp('idpost'));
		exit;
		break;

	case "viewart":
		previewWaitingArticle(bab_gp('idart'));
		exit;
		break;

	case "viewcom":
		previewWaitingComment(bab_gp('idcom'));
		exit;
		break;

	case "confvac":
		include_once $GLOBALS['babInstallPath']."utilit/vacincl.php";
		confirmWaitingVacation(bab_gp('idvac'));
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