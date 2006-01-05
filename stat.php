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
//include_once $babInstallPath."utilit/uiutil.php";

define("STAT_IT_TOTAL",		0);
define("STAT_IT_TODAY",		1);
define("STAT_IT_YESTERDAY",	2);
define("STAT_IT_WEEK",		3);
define("STAT_IT_LASTWEEK",	4);
define("STAT_IT_MONTH",		5);
define("STAT_IT_LASTMONTH",	6);
define("STAT_IT_YEAR",		7);
define("STAT_IT_LASTYEAR",	8);
define("STAT_IT_OTHER",		9);



function updateStatPreferences()
{
	global $babDB;

	$res = $babDB->db_query("select * from ".BAB_STATS_PREFERENCES_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$pref['itwhat'] = $arr['time_interval'];
		$pref['sd'] = $arr['time_interval'] == STAT_IT_OTHER ? $arr['begin_date']: '';
		$pref['ed'] = $arr['time_interval'] == STAT_IT_OTHER ? $arr['end_date']: '';
		$pref['exportchr'] = chr($arr['separatorchar']);
		}
	else
		{
		$babDB->db_query("insert into ".BAB_STATS_PREFERENCES_TBL." (id_user, time_interval, begin_date, end_date, separatorchar) values ('".$GLOBALS['BAB_SESS_USERID']."', '".STAT_IT_TOTAL."', '', '', '".ord(",")."')");
		$pref['itwhat'] = STAT_IT_TOTAL;
		$pref['sd'] = '';
		$pref['ed'] = '';
		$pref['exportchr'] = ",";
		}

	if( !isset($GLOBALS['itwhat'])) 
		{
		$GLOBALS['itwhat'] = $pref['itwhat'];
		$GLOBALS['sd'] = $pref['sd'];
		$GLOBALS['ed'] = $pref['ed'];
		$GLOBALS['exportchr'] = $pref['exportchr'];
		}
	else
		{
		if( $GLOBALS['itwhat'] != STAT_IT_OTHER )
			{
			$GLOBALS['sd'] = "";
			$GLOBALS['ed'] = "";
			}

		$GLOBALS['exportchr'] = $pref['exportchr'];
		$babDB->db_query("update ".BAB_STATS_PREFERENCES_TBL." set time_interval='".$GLOBALS['itwhat']."', begin_date='".$GLOBALS['sd']."', end_date='".$GLOBALS['ed']."' where id_user='".$GLOBALS['BAB_SESS_USERID']."'");
		}
}


function displayStatisticPanel($idx)
{
	global $babBody;
	class displayStatisticPanelCls
		{
		var $altbg = true;
		var $itemarray = array();
		var $nbcols;

		function displayStatisticPanelCls($idx)
			{
			global $babBody;

			$this->updatetxt = bab_translate("Last update time");
			$this->updatettime = bab_shortDate(bab_mktime($babBody->babsite['stat_update_time']));
			$this->exporttxt = bab_translate("Export");
			$this->urlexport = "idx=".$idx."&export=1";
			$this->current = $idx;
			$this->itemarray[] = array( array('idx' => 'users', 'item' => bab_translate("Users"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=users")
				, array('idx' => 'sections', 'item' => bab_translate("Optional sections"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=sections")
				, array('idx' => 'delegat', 'item' => bab_translate("Delegation"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=delegat") );

			$this->itemarray[] = array( array('idx' => 'fm', 'item' => bab_translate("File manager"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=fm"), array('idx' => 'fmfold', 'item' => bab_translate("Folders"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=fmfold"), array('idx' => 'fmdown', 'item' => bab_translate("Downloads"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=fmdown"));
			$this->itemarray[] = array( array('idx' => 'mod', 'item' => bab_translate("Ovidentia functions"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=mod"), array('idx' => 'xlink', 'item' => bab_translate("External links"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=xlink")
				, array('idx' => 'page', 'item' => bab_translate("Pages"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=page") );
			$this->itemarray[] = array( array('idx' => 'art', 'item' => bab_translate("Articles"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=art")
				, array('idx' => 'topart', 'item' => bab_translate("Topics"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=topart")
				, array('idx' => 'topcat', 'item' => bab_translate("Topics categories"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=topcat") );

			$this->itemarray[] = array( array('idx' => 'for', 'item' => bab_translate("Forums"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=for")
				, array('idx' => 'forth', 'item' => bab_translate("Threads"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=forth")
				, array('idx' => 'forpo', 'item' => bab_translate("Posts"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=forpo") );
			$this->itemarray[] = array( array('idx' => 'search', 'item' => bab_translate("Search keywords"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=search"));
			$this->itemarray[] = array( array('idx' => 'faq', 'item' => bab_translate("Faqs"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=faq")
				, array('idx' => 'faqqr', 'item' => bab_translate("Faq questions"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=faqqr") );
			$this->itemarray[] = array( array('idx' => 'ovml', 'item' => bab_translate("Ovml Files"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=ovml"), array('idx' => 'addon', 'item' => bab_translate("Add-ons"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=addon"));

			$this->maxcols = 1;
			$this->count = count($this->itemarray);
			for( $i = 0; $i < $this->count; $i++ )
				{
				if( count($this->itemarray[$i]) > $this->maxcols )
					{
					$this->maxcols = count($this->itemarray[$i]);
					}
				}
			$this->colspanval = $this->maxcols - 1;
			$this->row = 0;
			}

		function getnextrow()
			{
			if( $this->row < $this->count)
				{
				$this->altbg = !$this->altbg;
				return true;
				}
			else
				{
				$this->row = 0;
				return false;
				}
			}

		function getnextcol()
			{
			static $i = 0;
			if( $i < $this->maxcols)
				{
				if( isset($this->itemarray[$this->row][$i]))
					{
					$this->itemurltxt = $this->itemarray[$this->row][$i]['item'];
					$this->itemurl = $this->itemarray[$this->row][$i]['url'];
					if( $this->current == $this->itemarray[$this->row][$i]['idx'] )
						{
						$this->disabled = true;
						}
					else
						{
						$this->disabled = false;
						}
					}
				else
					{
					$this->itemurltxt = false;
					}
				$i++;
				return true;
				}
			else
				{
				$this->row++;
				$i = 0;
				return false;
				}
			}
		}
	$temp = new displayStatisticPanelCls($idx);
	$babBody->babecho(bab_printTemplate($temp, "stat.html", "statisticpanel"));
}


function displayTimeInterval($iwhat, $sd, $ed, $idx)
{
	global $babBody;
	class displayTimeIntervalCls
		{
		var $timeintervaltxt;
		var $itemarray = array();
		var $current;

		function displayTimeIntervalCls($iwhat, $sd, $ed, $idx)
			{
			$this->current = $iwhat;
			switch($idx)
				{
				case 'users':
				case 'fm':
				case 'sections':
				case 'delegat':
					$this->showform = false;
					break;
				default:
					$this->showform = true;
					break;
				}
			$this->submittxt = bab_translate("Ok");
			$this->fromtxt = bab_translate("From");
			$this->totxt = bab_translate("to");
			$this->dateformattxt =bab_translate("dd/mm/yyyy");
			$this->timeintervaltxt = bab_translate("Time interval");
			$this->itemarray[STAT_IT_TOTAL] = bab_translate("Total");
			$this->itemarray[STAT_IT_TODAY] = bab_translate("Today");
			$this->itemarray[STAT_IT_YESTERDAY] = bab_translate("Yesterday");
			$this->itemarray[STAT_IT_WEEK] = bab_translate("Week");
			$this->itemarray[STAT_IT_LASTWEEK] = bab_translate("Last week");
			$this->itemarray[STAT_IT_MONTH] = bab_translate("Month");
			$this->itemarray[STAT_IT_LASTMONTH] = bab_translate("Last month");
			$this->itemarray[STAT_IT_YEAR] = bab_translate("Year");
			$this->itemarray[STAT_IT_LASTYEAR] = bab_translate("Last year");
			$this->itemarray[STAT_IT_OTHER] = bab_translate("Other");
			$this->count = count($this->itemarray);
			$this->begin_url = $GLOBALS['babUrlScript']."?tg=month&callback=beginJs&ymin=1&ymax=6&month=".date("m")."&year=".date("Y");
			$this->end_url = $GLOBALS['babUrlScript']."?tg=month&callback=endJs&ymin=1&ymax=6&month=".date("m")."&year=".date("Y");
			switch($this->current )
				{
				case STAT_IT_TODAY:
					$this->sd = $this->ed = date("Y-m-d");
					$this->sd_disp = $this->ed_disp = date("d-m-Y");
					break;
				case STAT_IT_YESTERDAY:
					$this->sd = $this->ed = date("Y-m-d", time()-(24*3600));
					$this->sd_disp = $this->ed_disp = date("d-m-Y", time()-(24*3600));
					break;
				case STAT_IT_WEEK:
					$stime = time()-(date("w")*24*3600);
					$etime = $stime + (6*24*3600);
					$this->sd = date("Y-m-d", $stime);
					$this->sd_disp = date("d-m-Y", $stime);
					$this->ed = date("Y-m-d", $etime);
					$this->ed_disp = date("d-m-Y", $etime);
					break;
				case STAT_IT_LASTWEEK:
					$stime = time()-(date("w")*24*3600) - (7*24*3600);
					$etime = $stime + (6*24*3600);
					$this->sd = date("Y-m-d", $stime);
					$this->sd_disp = date("d-m-Y", $stime);
					$this->ed = date("Y-m-d", $etime);
					$this->ed_disp = date("d-m-Y", $etime);
					break;
				case STAT_IT_MONTH:
					$stime = time();
					$this->sd = sprintf("%s-01", date("Y-m", $stime));
					$this->sd_disp = sprintf("01-%s", date("m-Y", $stime));
					$this->ed = date("Y-m-t", $stime);
					$this->ed_disp = date("t-m-Y", $stime);
					break;
				case STAT_IT_LASTMONTH:
					$stime = time() - (date("j", time())*24*3600);
					$this->sd = sprintf("%s-01", date("Y-m", $stime));
					$this->sd_disp = sprintf("01-%s", date("m-Y", $stime));
					$this->ed = date("Y-m-t", $stime);
					$this->ed_disp = date("t-m-Y", $stime);
					break;
				case STAT_IT_YEAR:
					$year = date("Y", time());
					$this->sd = sprintf("%s-01-01", $year);
					$this->sd_disp = sprintf("01-01-%s", $year);
					$this->ed = sprintf("%s-12-31", $year);
					$this->ed_disp = sprintf("31-12-%s", $year);
					break;
				case STAT_IT_LASTYEAR:
					$year = date("Y", time()) - 1;
					$this->sd = sprintf("%s-01-01", $year);
					$this->sd_disp = sprintf("01-01-%s", $year);
					$this->ed = sprintf("%s-12-31", $year);
					$this->ed_disp = sprintf("31-12-%s", $year);
					break;
				case STAT_IT_OTHER:
					if( empty($sd) || empty($ed))
					{
						$this->sd = $this->ed = date("Y-m-d");
						$this->sd_disp = $this->ed_disp = date("d-m-Y");						
					}
					else
					{
						$this->sd = $sd;
						$arr = explode('-', $sd);
						$this->sd_disp = sprintf("%s-%s-%s", $arr[2], $arr[1], $arr[0]);
						$this->ed = $ed;
						$arr = explode('-', $ed);
						$this->ed_disp = sprintf("%s-%s-%s", $arr[2], $arr[1], $arr[0]);
					}
					break;
				default:
					$this->sd = '';
					$this->sd_disp = '';
					$this->ed = '';
					$this->ed_disp = '';
					break;
				}

			$GLOBALS['sd'] = $this->sd;
			$GLOBALS['ed'] = $this->ed;
			}

		function getnextitime()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->itval = $i;
				$this->itvaltxt = $this->itemarray[$i];
				if( $this->current == $i )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}
	$temp = new displayTimeIntervalCls($iwhat, $sd, $ed, $idx);
	$babBody->babecho(bab_printTemplate($temp, "stat.html", "timeinterval"));
}

/* main */
if( !bab_isAccessValid(BAB_STATSMAN_GROUPS_TBL, 1))
	{
	$babBody->msgerror = bab_translate("Access denied");
	return;
	}

if( !isset($idx)) { $idx = 'users';}
displayStatisticPanel($idx);
updateStatPreferences();
displayTimeInterval($itwhat, $sd, $ed, $idx);

if( isset($reqvars))
{
	parse_str($reqvars);
}

switch($idx)
	{
	case "xlink":
		include_once $babInstallPath."statxlink.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		summaryXlinks($col, $order, $sd, $ed);
		break;
	case "art":
		include_once $babInstallPath."statart.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryArticles($col, $order, $pos, $sd, $ed);
		break;
	case "sart":
		include_once $babInstallPath."statart.php";
		$babBodyPopup = new babBodyPopup();
		showStatArticle($item, $date);
		$babBodyPopup->addItemMenu("sart", bab_translate("Statistic"), $GLOBALS['babUrlScript']."?tg=stat&idx=sart&item=".$item."&date=".$date);
		$babBodyPopup->addItemMenu("refart", bab_translate("Referents"), $GLOBALS['babUrlScript']."?tg=stat&idx=refart&item=".$item."&date=".$date);
		$babBodyPopup->setCurrentItemMenu($idx);
		printBabBodyPopup();
		exit;
		break;
	case "refart":
		include_once $babInstallPath."statart.php";
		$babBodyPopup = new babBodyPopup();
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		showReferentsArticle($col, $order, $pos, $item, $date);
		$babBodyPopup->addItemMenu("sart", bab_translate("Statistic"), $GLOBALS['babUrlScript']."?tg=stat&idx=sart&item=".$item."&date=".$date);
		$babBodyPopup->addItemMenu("refart", bab_translate("Referents"), $GLOBALS['babUrlScript']."?tg=stat&idx=refart&item=".$item."&date=".$date);
		$babBodyPopup->setCurrentItemMenu($idx);
		printBabBodyPopup();
		exit;
		break;
	case "topart":
		include_once $babInstallPath."statart.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryTopicsArticles($col, $order, $pos, $sd, $ed);
		break;
	case "stop":
		include_once $babInstallPath."statart.php";
		$babBodyPopup = new babBodyPopup();
		showStatTopic($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "topcat":
		include_once $babInstallPath."statart.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryTopicCategoryArticles($col, $order, $pos, $sd, $ed);
		break;
	case "scat":
		include_once $babInstallPath."statart.php";
		$babBodyPopup = new babBodyPopup();
		showStatTopicCategory($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "for":
		include_once $babInstallPath."statfor.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryForums($col, $order, $pos, $sd, $ed);
		break;
	case "sfor":
		include_once $babInstallPath."statfor.php";
		$babBodyPopup = new babBodyPopup();
		showStatForum($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "forth":
		include_once $babInstallPath."statfor.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryThreads($col, $order, $pos, $sd, $ed);
		break;
	case "sforth":
		include_once $babInstallPath."statfor.php";
		$babBodyPopup = new babBodyPopup();
		showStatThread($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "forpo":
		include_once $babInstallPath."statfor.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryPosts($col, $order, $pos, $sd, $ed);
		break;
	case "sforpo":
		include_once $babInstallPath."statfor.php";
		$babBodyPopup = new babBodyPopup();
		showStatPost($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "faq":
		include_once $babInstallPath."statfaq.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryFaqs($col, $order, $pos, $sd, $ed);
		break;
	case "sfaq":
		include_once $babInstallPath."statfaq.php";
		$babBodyPopup = new babBodyPopup();
		showStatFaq($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "faqqr":
		include_once $babInstallPath."statfaq.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryQuestionsFaqs($col, $order, $pos, $sd, $ed);
		break;
	case "sfaqqr":
		include_once $babInstallPath."statfaq.php";
		$babBodyPopup = new babBodyPopup();
		showStatFaqQuestion($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "search":
		include_once $babInstallPath."statword.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summarySearchKeyWords($col, $order, $pos, $sd, $ed);
		break;
	case "mod":
		include_once $babInstallPath."statmod.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		summaryModules($col, $order, $sd, $ed);
		break;
	case "page":
		include_once $babInstallPath."statpages.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		summaryPages($col, $order, $sd, $ed);
		break;
	case "users":
		include_once $babInstallPath."statboard.php";
		summaryUsers();
		break;
	case "sections":
		if( !isset($col)) { $col = 'usage';}
		if( !isset($order)) { $order = 'desc';}
		include_once $babInstallPath."statboard.php";
		summarySections($col, $order);
		break;

	case "delegat":
		if( !isset($col)) { $col = 'dgname';}
		if( !isset($order)) { $order = 'desc';}
		include_once $babInstallPath."statboard.php";
		summaryDelegatList($col, $order);
		break;
	case "sumdp":
		include_once $babInstallPath."utilit/fileincl.php";
		include_once $babInstallPath."statfile.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Personal Folders");
		if( $fid == 0 )
			{
			showPersonalFoldersDetail();
			}
		printBabBodyPopup();
		exit;
		break;
	case "fm":
		include_once $babInstallPath."utilit/fileincl.php";
		include_once $babInstallPath."statfile.php";
		if( !isset($col)) { $col = 'diskspace';}
		if( !isset($order)) { $order = 'asc';}
		summaryFileManager($col, $order);
		break;
	case "fmfold":
		include_once $babInstallPath."utilit/fileincl.php";
		include_once $babInstallPath."statfile.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryFmFolders($col, $order, $pos, $sd, $ed);
		break;
	case "sfmfold":
		include_once $babInstallPath."statfile.php";
		$babBodyPopup = new babBodyPopup();
		showStatFmFolder($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "fmdown":
		include_once $babInstallPath."utilit/fileincl.php";
		include_once $babInstallPath."statfile.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryFmDownloads($col, $order, $pos, $sd, $ed);
		break;
	case "sfmdown":
		include_once $babInstallPath."statfile.php";
		$babBodyPopup = new babBodyPopup();
		showStatFmDownloads($item, $date);
		printBabBodyPopup();
		exit;
		break;
	case "ovml":
		include_once $babInstallPath."statovml.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryOvmlFiles($col, $order, $pos, $sd, $ed);
		break;
	case "addon":
		include_once $babInstallPath."stataddons.php";
		if( !isset($col)) { $col = 'hits';}
		if( !isset($order)) { $order = 'asc';}
		if( !isset($pos)) { $pos = 0;}
		summaryAddons($col, $order, $pos, $sd, $ed);
		break;
	case "saddon":
		include_once $babInstallPath."stataddons.php";
		$babBodyPopup = new babBodyPopup();
		showStatAddon($item, $date);
		printBabBodyPopup();
		exit;
		break;
	default:
		break;
	}

$babBody->title = bab_translate("Statistics");
$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat&idx=stat");
$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
$babBody->setCurrentItemMenu("stat");
?>