<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."utilit/topincl.php";

function upComingEvents($idcal)
{
	global $body;

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
			$this->db = new db_mysql();
			$mktime = mktime();
			$this->newevents = babTranslate("Upcoming Events");
			$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$mktime = $mktime + 518400;
			$daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$req = "select * from cal_events where id_cal='".$idcal."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date, start_time asc";		
			$this->resevent = $this->db->db_query($req);
			$this->countevent = $this->db->db_num_rows($this->resevent);
			$idgrp = getPrimaryGroupId($BAB_SESS_USERID);
			$this->grpname = getGroupName($idgrp);
			$req = "select * from cal_events where id_cal='".getCalendarId($idgrp, 2)."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
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
				$this->time = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5);
				$this->date = bab_strftime(bab_mktime($arr[start_date]), false);
				$this->title = $arr[title];
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$this->calid. "&evtid=".$arr[id];
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
				$this->time = substr($arr[start_time], 0 ,5). " " . substr($arr[end_time], 0 ,5);
				$this->date = bab_strftime(bab_mktime($arr[start_date]), false);
				$this->title = $arr[title] . " ( ". $this->grpname ." )";
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=event&idx=modify&day=".$day."&month=".$month."&year=".$year. "&calid=".$arr[id_cal]. "&evtid=".$arr[id];
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
	$body->babecho(	babPrintTemplate($temp,"calview.html", "eventslist"));
}

function newArticles($days)
{
	global $body;

	class temp2
		{

		var $db;
		var $arrid = array();
		var $count;
		var $resarticles;
		var $countarticles;
		var $rescomments;
		var $countcomments;
		var $lastlog;
		var $newarticles;
		var $newcomments;
		var $nbdays;

		function temp2($days)
			{
			global $body, $BAB_SESS_USERID;
			$this->db = new db_mysql();

			$this->nbdays = $days;
			$req = "select * from topics";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(isAccessValid("topicsview_groups", $row[id]))
					{
					array_push($this->arrid, $row[id]);
					}
				}
			$this->count = count($this->arrid);
			if( $days > 0 )
				{
				$this->newarticles = babTranslate("Last articles");
				$this->newcomments = babTranslate("Last comments");
				}
			else
				{
				$this->newarticles = babTranslate("New articles");
				$this->newcomments = babTranslate("New comments");
				}
			}

		function getnexttopic()
			{
			global $body;
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select * from articles where id_topic='".$this->arrid[$k]."' and confirmed='Y'and date >= ";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$body->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$body->lastlog."'";
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
			global $body;
			static $k=0;
			if( $k < $this->countarticles)
				{
				$arr = $this->db->db_fetch_array($this->resarticles);
				$this->title = $arr[title];
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$arr[id_topic]."&article=".$arr[id];
				$this->author = getArticleAuthor($arr[id]);
				$this->date = getArticleDate($arr[id]);
				$req = "select * from comments where id_article='".$arr[id]."' and confirmed='Y' and date >= ";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$body->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$body->lastlog."'";
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

		function getnexttopiccom()
			{
			global $body;
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select * from comments where id_topic='".$this->arrid[$k]."' and confirmed='Y'and date >= ";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$body->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$body->lastlog."'";
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
				$this->title = $arr[subject];
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=comments&idx=read&topics=".$arr[id_topic]."&article=".$arr[id_article]."&com=".$arr[id];
				$this->author = $arr[name];
				$this->date = bab_strftime(bab_mktime($arr[date]));
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
	$body->babecho(	babPrintTemplate($temp,"calview.html", "articleslist"));
}


function newThreads($nbdays)
{
	global $body;

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

		function temp3($nbdays)
			{
			global $BAB_SESS_USERID;
			$this->db = new db_mysql();

			$this->nbdays = $nbdays;
			$req = "select * from forums";
			$res = $this->db->db_query($req);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(isAccessValid("forumsview_groups", $row[id]))
					{
					array_push($this->arrid, $row[id]);
					}
				}
			$this->count = count($this->arrid);
			if( $nbdays > 0)
				$this->newposts = babTranslate("Last posts");
			else
				$this->newposts = babTranslate("New posts");
			}

		function getnextforum()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select * from threads where forum='".$this->arrid[$k]."' order by date desc";
				$this->resthread = $this->db->db_query($req);
				$this->countthreads = $this->db->db_num_rows($this->resthread);
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
			global $body;
			static $m=0;
			if( $m < $this->countthreads)
				{
				$this->total = 0;
				$arr = $this->db->db_fetch_array($this->resthread);
				$req = "select * from posts where id_thread='".$arr[id]."' and confirmed='Y' and date >=";
				if( $this->nbdays > 0)
					$req .= "DATE_ADD(\"".$body->lastlog."\", INTERVAL -".$this->nbdays." DAY)";
				else
					$req .= "'".$body->lastlog."'";
				$this->resposts = $this->db->db_query($req);
				$this->total = $this->db->db_num_rows($this->resposts);

				$req = "select * from posts where id='".$arr[post]."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->posts = $arr2[subject];
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
			if( $this->total > 0)
				{
				$arr = $this->db->db_fetch_array($this->resposts);
				$this->total--;
				$this->date = bab_strftime(bab_mktime($arr[date]));
				$this->title = $arr[subject];
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$this->forum."&thread=".$arr[id_thread]."&post=".$arr[id];
				return true;
				}
			else
				{
				return false;
				}
			}

		}

	$temp = new temp3($nbdays);
	$body->babecho(	babPrintTemplate($temp,"calview.html", "threadslist"));
}

function newEmails()
{
	global $body;

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
			$this->db = new db_mysql();
			$req = "select *, DECODE(password, \"".$BAB_HASH_VAR."\") as accpass from mail_accounts where owner='".$BAB_SESS_USERID."'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->newmails = babTranslate("Waiting mails");
			}

		function getmail()
			{
			static $i=0;
			if( $i < $this->count )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from mail_domains where id='".$arr[domain]."'";
				$res2 = $this->db->db_query($req);
				$this->domain = "";
				$this->nbemails = "";
				$this->domainurl = "";
				if( $res2 && $this->db->db_num_rows($res2) > 0 )
					{
					$arr2 = $this->db->db_fetch_array($res2);
					$this->domain = $arr2[name];
					$cnxstring = "{".$arr2[inserver]."/".$arr2[access].":".$arr2[inport]."}INBOX";
					$mbox = @imap_open($cnxstring, $arr[account], $arr[accpass]);
					if($mbox)
						{
						$this->domainurl = $GLOBALS[babUrl]."index.php?tg=inbox&&accid=".$arr[id];
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
	$body->babecho(	babPrintTemplate($temp,"calview.html", "mailslist"));
}

/* main */
if(!isset($idx))
	{
	$idx = "view";
	}

switch($idx)
	{
	case "com":
	case "art":
		newArticles(0);
		break;
	case "for":
		newThreads(0);
		break;
	default:
	case "view":
		$body->title = "";
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		if( (getCalendarId(1, 2) != 0  || getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			upComingEvents($idcal);
			/*
			$body->addItemMenu("viewm", babTranslate("Calendar"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&calid=".$idcal);
			if( isUserGroupManager())
				{
				$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
			*/
		}
		newArticles(7);
		newThreads(7);
		$bemail = mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			{
			newEmails();
			}
		break;
	}
$body->setCurrentItemMenu($idx);

?>