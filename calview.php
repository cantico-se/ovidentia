<?php
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
			$daymin = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$mktime = $mktime + 518400;
			$daymax = sprintf("%04d-%02d-%02d", date("Y", $mktime), Date("n", $mktime), Date("j", $mktime));
			$req = "select * from cal_events where id_cal='".$idcal."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date asc";		
			$this->resevent = $this->db->db_query($req);
			$this->countevent = $this->db->db_num_rows($this->resevent);
			$idgrp = getPrimaryGroupId($BAB_SESS_USERID);
			$this->grpname = getGroupName($idgrp);
			$req = "select * from cal_events where id_cal='".getCalendarId($idgrp, 2)."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax') order by start_date asc";		
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

function newArticles()
{
	global $body;

	class temp2
		{

		var $db;
		var $arrid = array();
		var $count;
		var $resarticles;
		var $countarticles;
		var $lastlog;
		var $newarticles;

		function temp2()
			{
			global $BAB_SESS_USERID;
			$this->db = new db_mysql();
			$req = "select * from users_log where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($res);
			$this->lastlog = $row[lastlog];

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
			$this->newarticles = babTranslate("New articles");
			}

		function getnexttopic()
			{
			static $k=0;
			if( $k < $this->count)
				{
				$req = "select * from articles where id_topic='".$this->arrid[$k]."' and confirmed='Y' and date >= '".$this->lastlog."' order by date desc";
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
			static $k=0;
			if( $k < $this->countarticles)
				{
				$arr = $this->db->db_fetch_array($this->resarticles);
				$this->title = $arr[title];
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=articles&idx=More&topics=".$arr[id_topic]."&article=".$arr[id];
				$this->author = getArticleAuthor($arr[id]);
				$this->date = getArticleDate($arr[id]);
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

	$temp = new temp2();
	$body->babecho(	babPrintTemplate($temp,"calview.html", "articleslist"));
}

function newThreads()
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

		function temp3()
			{
			global $BAB_SESS_USERID;
			$this->db = new db_mysql();
			$req = "select * from users_log where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($res);
			$this->lastlog = $row[lastlog];

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
			static $k=0;
			if( $k < $this->countthreads)
				{
				$this->total = 0;
				$arr = $this->db->db_fetch_array($this->resthread);
				$req = "select count(*) as total from posts where id_thread='".$arr[id]."' and confirmed='Y' and date >= '".$this->lastlog."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->total = $arr2[total];
				$req = "select * from posts where id='".$arr[lastpost]."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->date = bab_strftime(bab_mktime($arr2[date]));
				$req = "select * from posts where id='".$arr[post] ."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				$this->title = $arr2[subject];
				$this->titleurl = $GLOBALS[babUrl]."index.php?tg=posts&idx=List&forum=".$arr[forum]."&thread=".$arr[id]."&views=1";
				$this->posts = $this->total . " ". babTranslate("Post(s)");
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}

		function getpost()
			{
			if( $this->total > 0)
				{
				$this->total--;
				return true;
				}
			else
				{
				return false;
				}
			}

		}

	$temp = new temp3();
	$body->babecho(	babPrintTemplate($temp,"calview.html", "threadslist"));
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
		$body->title = babTranslate("Upcoming Events");
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		if( $idcal != 0)
		{
			upComingEvents($idcal);
			$body->addItemMenu("viewm", babTranslate("Calendar"), $GLOBALS[babUrl]."index.php?tg=calendar&idx=viewm&calid=".$idcal);
			if( isUserGroupManager())
				{
				$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=$BAB_SESS_USERID");
				$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=$BAB_SESS_USERID");
				}
			//$body->addItemMenu("newevent", babTranslate("Add Event"), $GLOBALS[babUrl]."index.php?tg=event&idx=newevent&calendarid=0");
		}
		newArticles();
		newThreads();
		break;
	}
$body->setCurrentItemMenu($idx);

?>