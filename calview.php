<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."utilit/topincl.php";
include $babInstallPath."utilit/forumincl.php";

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
			$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$mktime = $mktime + 518400;
			$daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id_cal='".$idcal."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date, start_time asc";		
			$this->resevent = $this->db->db_query($req);
			$this->countevent = $this->db->db_num_rows($this->resevent);
			$idgrp = bab_getPrimaryGroupId($BAB_SESS_USERID);
			$this->grpname = bab_getGroupName($idgrp);
			$req = "select * from ".BAB_CAL_EVENTS_TBL." where id_cal='".bab_getCalendarId($idgrp, 2)."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date, start_time asc";		
			$this->resgrpevent = $this->db->db_query($req);
			$this->countgrpevent = $this->db->db_num_rows($this->resgrpevent);
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

function newArticles($days)
{
	global $babBody;

	class temp2
		{

		var $db;
		var $arrid = array();
		var $count;
		var $resarticles;
		var $countarticles;
		var $lastlog;
		var $newarticles;
		var $nbdays;

		function temp2($days)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->db = $GLOBALS['babDB'];

			$this->nbdays = $days;
			$this->count = count($babBody->topview);
			if( $days > 0 )
				{
				$this->newarticles = bab_translate("Last articles ( Since seven days before your last visit )");
				}
			else
				{
				$this->newarticles = bab_translate("New articles");
				}
			}

		function getnexttopic()
			{
			global $babBody;
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$babBody->topview[$k]."' and confirmed='Y'and date >= ";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$babBody->lastlog."'";
				$req .= " order by date desc";
				$this->resarticles = $this->db->db_query($req);
				$this->countarticles = $this->db->db_num_rows($this->resarticles);
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}


		function getarticle()
			{
			global $babBody;
			static $k=0;
			if( $k < $this->countarticles)
				{
				$arr = $this->db->db_fetch_array($this->resarticles);
				$this->title = $arr['title'];
				$this->titleurl = $GLOBALS['babUrlScript']."?tg=articles&idx=viewa&topics=".$arr['id_topic']."&article=".$arr['id'];
				if( $arr['id_author'] != 0 && (($author = bab_getUserName($arr['id_author'])) != ""))
					$this->author = $author;
				else
					$this->author = bab_translate("Anonymous");
				$this->date = bab_strftime(bab_mktime($arr['date']));
				$req = "select * from ".BAB_COMMENTS_TBL." where id_article='".$arr['id']."' and confirmed='Y' and date >= ";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$babBody->lastlog."'";
				$req .= " order by date desc";
				$this->rescomments = $this->db->db_query($req);
				$this->countcomments = $this->db->db_num_rows($this->rescomments);
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

	$temp = new temp2($days);
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "articleslist"));
}


function newComments($days)
{
	global $babBody;

	class temp5
		{

		var $db;
		var $arrid = array();
		var $count;
		var $rescomments;
		var $countcomments;
		var $lastlog;
		var $newcomments;
		var $nbdays;

		function temp5($days)
			{
			global $babBody, $BAB_SESS_USERID;
			$this->db = $GLOBALS['babDB'];

			$this->nbdays = $days;
			$this->count = count($babBody->topview);
			if( $days > 0 )
				{
				$this->newcomments = bab_translate("Last comments ( Since seven days before your last visit )");
				}
			else
				{
				$this->newcomments = bab_translate("New comments");
				}
			}

		function getnexttopiccom()
			{
			global $babBody;
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select * from ".BAB_COMMENTS_TBL." where id_topic='".$babBody->topview[$k]."' and confirmed='Y'and date >= ";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$babBody->lastlog."'";
				$req .= " order by date desc";
				$this->rescomments = $this->db->db_query($req);
				$this->countcomments = $this->db->db_num_rows($this->rescomments);
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getcomment()
			{
			static $k=0;
			if( $k < $this->countcomments)
				{
				$arr = $this->db->db_fetch_array($this->rescomments);
				$this->title = $arr['subject'];
				$this->titleurl = $GLOBALS['babUrlScript']."?tg=comments&idx=read&topics=".$arr['id_topic']."&article=".$arr['id_article']."&com=".$arr['id'];
				$this->author = $arr['name'];
				$this->date = bab_strftime(bab_mktime($arr['date']));
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

	$temp = new temp5($days);
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "commentslist"));
}

function newThreads($nbdays)
{
	global $babBody;

	class temp3
		{

		var $db;
		var $arrid = array();
		var $count;
		var $resthread;
		var $countarticles;
		var $lastlog;
		var $newposts;
		var $posts;
		var $nbdays;
		var $forumname;

		function temp3($nbdays)
			{
			global $BAB_SESS_USERID;
			$this->db = $GLOBALS['babDB'];

			$this->nbdays = $nbdays;
			$req = "select id from ".BAB_FORUMS_TBL."";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
					{
					array_push($this->arrid, $row['id']);
					}
				}
			$this->count = count($this->arrid);
			if( $nbdays > 0)
				$this->newposts = bab_translate("Last posts ( Since seven days before your last visit )");
			else
				$this->newposts = bab_translate("New posts");
			}

		function getnextforum()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select id, post from ".BAB_THREADS_TBL." where forum='".$this->arrid[$k]."' order by date desc";
				$this->resthread = $this->db->db_query($req);
				$this->countthreads = $this->db->db_num_rows($this->resthread);
				$this->forumname = bab_getForumName($this->arrid[$k]);
				$this->forum = $this->arrid[$k];
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getnextthread()
			{
			global $babBody;
			static $m=0;
			if( $m < $this->countthreads)
				{
				$this->total = 0;
				$arr = $this->db->db_fetch_array($this->resthread);
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$arr['id']."' and confirmed='Y' and date >=";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$babBody->lastlog."'";
				$this->resposts = $this->db->db_query($req);
				$this->total = $this->db->db_num_rows($this->resposts);
				$this->ipost = 0;
				$req = "select subject from ".BAB_POSTS_TBL." where id='".$arr['post']."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->posts = $arr2['subject'];
				$m++;
				return true;
				}
			else
				{
				$m = 0;
				return false;
				}
			}

		function getpost()
			{
			if( $this->ipost < $this->total)
				{
				$arr = $this->db->db_fetch_array($this->resposts);
				//$this->total--;
				$this->date = bab_strftime(bab_mktime($arr['date']));
				$this->title = $arr['subject'];
				$this->titleurl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$this->forum."&thread=".$arr['id_thread']."&post=".$arr['id'];
				$this->ipost++;
				return true;
				}
			else
				{
				$this->ipost = 0;
				return false;
				}
			}

		}

	$temp = new temp3($nbdays);
	$babBody->babecho(	bab_printTemplate($temp,"calview.html", "threadslist"));
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
					$mbox = @imap_open($cnxstring, $arr['account'], $arr['accpass']);
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
			$req = "select ".BAB_FILES_TBL.".id, ".BAB_FILES_TBL.".name, ".BAB_FILES_TBL.".description from ".BAB_FILES_TBL." join ".BAB_USERS_GROUPS_TBL." where ".BAB_USERS_GROUPS_TBL.".id_object = '".$BAB_SESS_USERID."' and ".BAB_FILES_TBL.".confirmed='Y' and ".BAB_FILES_TBL.".id_owner=".BAB_USERS_GROUPS_TBL.".id_group and ".BAB_FILES_TBL.".bgroup='Y' and ".BAB_FILES_TBL.".state='' and ".BAB_FILES_TBL.".modified >=";
			if( $this->nbdays > 0)
				$req .= "DATE_ADD(\"".$babBody->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
			else
				$req .= "'".$babBody->lastlog."'";
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
	case "com":
		$babBody->title = bab_translate("Summary");
		newComments(0);
		break;
	case "art":
		$babBody->title = bab_translate("Summary");
		newArticles(0);
		break;
	case "for":
		$babBody->title = bab_translate("Summary");
		newThreads(0);
		break;
	case "fil":
		$babBody->title = bab_translate("Summary");
		newFiles(0);
		break;
	default:
	case "view":
		$babBody->title = bab_translate("Summary");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		if( (bab_getCalendarId(1, 2) != 0  || bab_getCalendarId(bab_getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			upComingEvents($idcal);
			/*
			$babBody->addItemMenu("viewm", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calendar&idx=viewm&calid=".$idcal);
			if( bab_isUserGroupManager())
				{
				$babBody->addItemMenu("listcat", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$babBody->addItemMenu("resources", bab_translate("Resources"), $GLOBALS['babUrlScript']."?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
			*/
		}
		newArticles(7);
		newComments(7);
		newThreads(7);
		newFiles(7);
		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			{
			newEmails();
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>