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
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax')";		
			$this->resevent = $this->db->db_query($req);
			$this->countevent = $this->db->db_num_rows($this->resevent);
			$req = "select * from cal_events where id_cal='".getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2)."' and ('$daymin' between start_date and end_date or '$daymax' between start_date and end_date";
			$req .= " or start_date between '$daymin' and '$daymax' or end_date between '$daymin' and '$daymax')";		
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
				$this->title = $arr[title];
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
		var $datelog;
		var $newarticles;

		function temp2()
			{
			global $BAB_SESS_USERID;
			$this->db = new db_mysql();
			$req = "select * from users_log where id_user='".$BAB_SESS_USERID."'";
			$res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($res);
			$this->datelog = $row[datelog];

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
				$req = "select * from articles where id_topic='".$this->arrid[$k]."' and confirmed='Y' and date >= '".$this->datelog."' order by date desc";
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
		break;
	}
$body->setCurrentItemMenu($idx);

?>