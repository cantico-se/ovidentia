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
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';

define('STAT_IT_TOTAL',		0);
define('STAT_IT_TODAY',		1);
define('STAT_IT_YESTERDAY',	2);
define('STAT_IT_WEEK',		3);
define('STAT_IT_LASTWEEK',	4);
define('STAT_IT_MONTH',		5);
define('STAT_IT_LASTMONTH',	6);
define('STAT_IT_YEAR',		7);
define('STAT_IT_LASTYEAR',	8);
define('STAT_IT_OTHER',		9);



function updateStatPreferences()
{
	global $babDB;

	$pref = array();
	$res = $babDB->db_query("select * from ".BAB_STATS_PREFERENCES_TBL." where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
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
		$babDB->db_query("insert into ".BAB_STATS_PREFERENCES_TBL." (id_user, time_interval, begin_date, end_date, separatorchar) values ('".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."', '".STAT_IT_TOTAL."', '', '', '".ord(",")."')");
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
		$babDB->db_query("update ".BAB_STATS_PREFERENCES_TBL." set time_interval='".$babDB->db_escape_string($GLOBALS['itwhat'])."', begin_date='".$babDB->db_escape_string($GLOBALS['sd'])."', end_date='".$babDB->db_escape_string($GLOBALS['ed'])."' where id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
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
			global $babBody, $babDB;

			$this->updatetxt = bab_translate('Last update time');
			$this->updatettime = bab_shortDate(bab_mktime($babBody->babsite['stat_update_time']));
			// There is no export for statistics baskets.
			$this->exporttxt = ($idx == 'baskets' ? '' : bab_translate("Export"));
			$this->current = $idx;

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
				{
				$this->itemarray[] = array( array('idx' => 'users', 'item' => bab_translate("Users"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=users")
				, array('idx' => 'sections', 'item' => bab_translate("Optional sections"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=sections")
				, array('idx' => 'delegat', 'item' => bab_translate("Delegation"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=delegation", 'popup' => true) );
				if( empty($this->current)) { $this->current = 'users'; }
				}

			$tmparr = array();
			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
				{
				$tmparr[] = array('idx' => 'mod', 'item' => bab_translate("Ovidentia functions"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=mod");
				}


			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION)
				{
				$tmparr[] = array('idx' => 'page', 'item' => bab_translate("Pages"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=page");
				}

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
				{
				$tmparr[] = array('idx' => 'search', 'item' => bab_translate("Search keywords"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=search");
				}

			if( count($tmparr))
				{
				$this->itemarray[] = $tmparr;

				if( empty($this->current)) { $this->current = 'page'; }
				}

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
				{
				$this->itemarray[] = array( array('idx' => 'fm', 'item' => bab_translate("File manager"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=fm",	'treeviewurl' => $GLOBALS['babUrlScript']."?tg=stat&idx=fmtree"), array('idx' => 'fmfold', 'item' => bab_translate("Folders"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=fmfold"), array('idx' => 'fmdown', 'item' => bab_translate("Downloads"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=fmdown"));
				if( empty($this->current)) { $this->current = 'fm'; }
				}


			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
				{
				$this->itemarray[] = array(  array('idx' => 'topcat', 'item' => bab_translate("Topics categories"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=topcat", 		'treeviewurl' => $GLOBALS['babUrlScript']."?tg=stat&idx=arttree")
				, array('idx' => 'topart', 'item' => bab_translate("Topics"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=topart")
				, array('idx' => 'art', 'item' => bab_translate("Articles"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=art")
				);
				if( empty($this->current)) { $this->current = 'art'; }
				}

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
				{
				$this->itemarray[] = array( array('idx' => 'for', 'item' => bab_translate("Forums"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=for", 		'treeviewurl' => $GLOBALS['babUrlScript']."?tg=stat&idx=fortree")
				, array('idx' => 'forth', 'item' => bab_translate("Threads"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=forth")
				, array('idx' => 'forpo', 'item' => bab_translate("Posts"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=forpo") );
				if( empty($this->current)) { $this->current = 'for'; }
				}

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('faqs')) )
				{
				$this->itemarray[] = array( array('idx' => 'faq', 'item' => bab_translate("Faqs"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=faq", 'treeviewurl' => $GLOBALS['babUrlScript']."?tg=stat&idx=faqtree")
					, array('idx' => 'faqqr', 'item' => bab_translate("Faq questions"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=faqqr") );
				if( empty($this->current)) { $this->current = 'faq'; }
				}


			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
				{
				$this->itemarray[] = array( array('idx' => 'ovml', 'item' => bab_translate("Ovml Files"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=ovml"), array('idx' => 'addon', 'item' => bab_translate("Add-ons"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=addon"), array('idx' => 'xlink', 'item' => bab_translate("External links"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=xlink"));
				}

			$tmparr = array();

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION )
				{
				$tmparr[] = array('idx' => 'dashboard', 'item' => bab_translate("Dashboard"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=dashboard", 'popup' => true);
				}
			
			if( bab_statisticsAccess() == BAB_STAT_ACCESS_USER || bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION)
				{
				$tmparr[] = array('idx' => 'baskets', 'item' => bab_translate("Baskets"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=baskets");
				}

			if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION )
				{
				$tmparr[] = array('idx' => 'connections', 'item' => bab_translate("Connections"), 'url' => $GLOBALS['babUrlScript']."?tg=stat&idx=connections");
				}
			
			if( count($tmparr))
				{
				$this->itemarray[] = $tmparr;
				if( empty($this->current)) { $this->current = $this->itemarray[0][0]['idx']; }
				}

			
			$this->maxcols = 2;
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
			if( $this->current != 'users' )
				{
				$this->urlexport = "idx=".$this->current."&export=1";
				}
			else
				{
				$this->urlexport = '';
				}
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
					$item =& $this->itemarray[$this->row][$i];
					$this->itemurltxt = bab_toHtml($item['item']);
					$this->itemurl = $item['url'];
					$this->itemtreeviewurl = isset($item['treeviewurl']) ? $item['treeviewurl'] : '';
					$this->popup = isset($item['popup']);
					$this->disabled = ($this->current == $item['idx']);
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
	if( empty($idx))
	{
		$GLOBALS['idx'] = $temp->current;
	}
}

class displayTimeIntervalCls
{
	var $timeintervaltxt;
	var $itemarray = array();
	var $current;

	function displayTimeIntervalCls($iwhat, $sd, $ed, $idx, $params = null)
		{
		$this->current = $iwhat;
		switch($idx)
			{
			case 'users':
			case 'fm':
			case 'sections':
			case 'delegat':
			case 'baskets':
				$this->showform = false;
				break;
			default:
				$this->showform = true;
				break;
			}
		if (!is_null($params)) {
			$this->hiddenParameters = $params;
			reset($this->hiddenParameters);
		} else {
			$this->hiddenParameters = array();
		}
		$this->submittxt = bab_translate("Ok");
		$this->fromtxt = bab_translate("From");
		$this->totxt = bab_translate("to");
		$this->dateformattxt = bab_translate("dd-mm-yyyy");
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
			$i = 0;
			return false;
			}
		}
		
	function getNextHiddenParameter()
	{
		while (list($this->param_name, $this->param_value) = each($this->hiddenParameters)) {
			$this->param_name = bab_toHtml($this->param_name);
			$this->param_value = bab_toHtml($this->param_value);
			return true;
		}
		reset($this->hiddenParameters);
		return false;
	}
}

function displayTimeInterval($iwhat, $sd, $ed, $idx, $params)
{
	global $babBody;
	$temp = new displayTimeIntervalCls($iwhat, $sd, $ed, $idx, $params);
	$babBody->babecho(bab_printTemplate($temp, "stat.html", "timeinterval"));
}

function displayTimeIntervalInPopup($iwhat, $sd, $ed, $idx, &$body, $idbasket = null)
{
	$temp = new displayTimeIntervalCls($iwhat, $sd, $ed, $idx, array('idbasket' => $idbasket));
	$body->babecho(bab_printTemplate($temp, "stat.html", "timeinterval"));
}


/**
 * Returns the date in iso format or an empty string if the input date is not valid.
 * 
 * @param string $inputDate The date in format 'dd-mm-yyyy'.
 * @return string The date in iso format 'yyyy-mm-dd' or en empty string.
 */
function validateDate($inputDate)
{
	$date = preg_split('/[^0-9]+/', $inputDate);
	if (count($date) == 3 && checkDate($date[1], $date[0], $date[2]))
	{
		return $date[2] . '-' . $date[1] . '-' . $date[0];
	}
	return '';
}


/* main */
if ( bab_statisticsAccess() == -1 )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', '');

// Start date
$sd = bab_rp('sd', '');
$sd = validateDate($sd);

// End date
$ed = bab_rp('ed', '');
$ed = validateDate($ed);



displayStatisticPanel($idx);
updateStatPreferences();

if ($idx != 'connection')
{
    $stat_params = array();
	if($reqvars = bab_rp('reqvars')) {
	    parse_str($reqvars, $stat_params);
	}
	displayTimeInterval(bab_rp('itwhat'), $sd, $ed, $idx, isset($stat_params) ? $stat_params : bab_rp('stat_params', null));
	if (isset($reqvars))
	{
		parse_str($reqvars);
	}
}

switch($idx)
	{
	case 'connections':
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION)
		{
			include_once $GLOBALS['babInstallPath'] . 'statconnections.php'; 
			summaryConnections(
			    bab_rp('col', 'connections'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0),
			    $sd,
			    $ed
			);
		}
		break;
	case 'connection':
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION)
			{
			include_once $GLOBALS['babInstallPath'] . 'statconnections.php';

			$stat_params = array();
			
			if ($reqvars = bab_rp('reqvars')) {
			    parse_str($reqvars, $stat_params);
			}
			displayTimeInterval(bab_rp('itwhat'), $sd, $ed, $idx, $stat_params + array('item' => bab_rp('item')));
			detailConnections(
			    bab_rp('col', 'connection'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0), 
			    $sd, 
			    $ed, 
			    bab_rp('item')
			   );
			}
		break;
	case "xlink":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
			{
			include_once $GLOBALS['babInstallPath']."statxlink.php";
			summaryXlinks(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    $sd, $ed);
			}
		break;
	case "arttree":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			displayArticleTree($sd, $ed);
			}
		break;
	case "art":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			summaryArticles(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0), 
			    $sd, $ed
			   );
			}
		break;
	case "sart":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			$babBodyPopup = new babBodyPopup();
			showStatArticle(bab_rp('item'), bab_rp('date'));
			$babBodyPopup->addItemMenu("sart", bab_translate("Statistic"), $GLOBALS['babUrlScript']."?tg=stat&idx=sart&item=".bab_rp('item')."&date=".bab_rp('date'));
			$babBodyPopup->addItemMenu("refart", bab_translate("Referents"), $GLOBALS['babUrlScript']."?tg=stat&idx=refart&item=".bab_rp('item')."&date=".bab_rp('date'));
			$babBodyPopup->setCurrentItemMenu($idx);
			printBabBodyPopup();
			exit;
			}
		break;
	case "refart":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			$babBodyPopup = new babBodyPopup();
			showReferentsArticle(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0), 
			    bab_rp('item'), 
			    bab_rp('date')
			   );
			$babBodyPopup->addItemMenu("sart", bab_translate("Statistic"), $GLOBALS['babUrlScript']."?tg=stat&idx=sart&item=".bab_rp('item')."&date=".bab_rp('date'));
			$babBodyPopup->addItemMenu("refart", bab_translate("Referents"), $GLOBALS['babUrlScript']."?tg=stat&idx=refart&item=".bab_rp('item')."&date=".bab_rp('date'));
			$babBodyPopup->setCurrentItemMenu($idx);
			printBabBodyPopup();
			exit;
			}
		break;
	case "topart":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			summaryTopicsArticles(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0), $sd, $ed);
			}
		break;
	case "stop":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			$babBodyPopup = new babBodyPopup();
			showStatTopic(bab_rp('item'), bab_rp('date'));
			printBabBodyPopup();
			exit;
			}
		break;
	case "topcat":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			summaryTopicCategoryArticles(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0), 
			    $sd, $ed);
			}
		break;
	case "scat":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('articles') ))
			{
			include_once $GLOBALS['babInstallPath']."statart.php";
			$babBodyPopup = new babBodyPopup();
			showStatTopicCategory(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "fortree":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";
			displayForumTree($sd, $ed);
			}
		break;
	case "for":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";
			summaryForums(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0),
			    $sd, $ed);
			}
		break;
	case "sfor":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";
			$babBodyPopup = new babBodyPopup();
			showStatForum(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "forth":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";

			summaryThreads(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0), 
			    $sd, $ed);
			}
		break;
	case "sforth":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";
			$babBodyPopup = new babBodyPopup();
			showStatThread(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "forpo":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";
			summaryPosts(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0),
			    $sd, $ed);
			}
		break;
	case "sforpo":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('forums')) )
			{
			include_once $GLOBALS['babInstallPath']."statfor.php";
			$babBodyPopup = new babBodyPopup();
			showStatPost(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "faqtree":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('faqs')) )
			{
			include_once $GLOBALS['babInstallPath']."statfaq.php";
			displayFaqTree($sd, $ed);
			}
		break;
	case "faq":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('faqs')) )
			{
			include_once $GLOBALS['babInstallPath']."statfaq.php";
			summaryFaqs(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0),
			    $sd, $ed
			  );
			}
		break;
	case "sfaq":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('faqs')) )
			{
			include_once $GLOBALS['babInstallPath']."statfaq.php";
			$babBodyPopup = new babBodyPopup();
			showStatFaq(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "faqqr":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('faqs')) )
			{
			include_once $GLOBALS['babInstallPath']."statfaq.php";
			summaryQuestionsFaqs(
			    bab_rp('col', 'hits'), 
			    bab_rp('order', 'asc'), 
			    bab_rp('pos', 0),
			    $sd, $ed);
			}
		break;
	case "sfaqqr":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('faqs')) )
			{
			include_once $GLOBALS['babInstallPath']."statfaq.php";
			$babBodyPopup = new babBodyPopup();
			showStatFaqQuestion(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "search":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
		{
		include_once $GLOBALS['babInstallPath']."statword.php";
		summarySearchKeyWords(
		    bab_rp('col', 'hits'), 
			bab_rp('order', 'asc'), 
			bab_rp('pos', 0),
		    $sd, $ed);
		}
		break;
	case "mod":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
		{
		include_once $GLOBALS['babInstallPath']."statmod.php";
		summaryModules(
		    bab_rp('col', 'hits'), 
			bab_rp('order', 'asc'), $sd, $ed);
		}
		break;
	case "page":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION)
		{
		include_once $GLOBALS['babInstallPath']."statpages.php";
		summaryPages(
		    bab_rp('col', 'hits'), 
			bab_rp('order', 'asc'), $sd, $ed);
		}
		break;
	case "users":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
		{
		include_once $GLOBALS['babInstallPath']."statboard.php";
		summaryUsers();
		}
		break;
	case "sections":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
		{
		include_once $GLOBALS['babInstallPath']."statboard.php";
		summarySections(
		    bab_rp('col', 'usage'), 
			bab_rp('order', 'desc')
		   );
		}
		break;

	case "delegat":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
		{
		include_once $GLOBALS['babInstallPath']."statboard.php";
		summaryDelegatList(
		    bab_rp('col', 'dgname'), 
			bab_rp('order', 'desc')
		   );
		}
		break;
	case "sumdp":
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		include_once $GLOBALS['babInstallPath']."statfile.php";
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Personal Folders");
		if( 0 === (int) bab_rp('fid') )
			{
			showPersonalFoldersDetail();
			}
		printBabBodyPopup();
		exit;
		break;
	case "fmtree":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
		{
		include_once $GLOBALS['babInstallPath']."statfile.php";
		displayFileTree($sd, $ed);
		}
		break;
	case "fm":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
		{
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		include_once $GLOBALS['babInstallPath']."statfile.php";
		summaryFileManager(
		    bab_rp('col', 'diskspace'), 
			bab_rp('order', 'asc')
		   );
		}
		break;
	case "fmfold":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
		{
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		include_once $GLOBALS['babInstallPath']."statfile.php";

		summaryFmFolders(
		    bab_rp('col', 'hits'), 
			bab_rp('order', 'asc'), 
			bab_rp('pos', 0), 
		    $sd, $ed);
		}
		break;
	case "sfmfold":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
		{
		include_once $GLOBALS['babInstallPath']."statfile.php";
		$babBodyPopup = new babBodyPopup();
		showStatFmFolder(
			    bab_rp('item'), 
			    bab_rp('date')
			);
		printBabBodyPopup();
		exit;
		}
		break;
	case "fmdown":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
		{
		include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
		include_once $GLOBALS['babInstallPath']."statfile.php";

		summaryFmDownloads(
		    bab_rp('col', 'hits'), 
			bab_rp('order', 'asc'), 
			bab_rp('pos', 0), 
		    $sd, $ed
		   );
		}
		break;
	case "sfmdown":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || (bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION &&  bab_isDelegated('filemanager')) )
		{
		include_once $GLOBALS['babInstallPath']."statfile.php";
		$babBodyPopup = new babBodyPopup();
		showStatFmDownloads(
			    bab_rp('item'), 
			    bab_rp('date')
			);
		printBabBodyPopup();
		exit;
		}
		break;
	case "ovml":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
			{
			include_once $GLOBALS['babInstallPath']."statovml.php";
			summaryOvmlFiles(
    		    bab_rp('col', 'hits'), 
    			bab_rp('order', 'asc'), 
    			bab_rp('pos', 0), 
    		    $sd, $ed
			   );
			}
		break;
	case "addon":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
			{
			include_once $GLOBALS['babInstallPath']."stataddons.php";
			summaryAddons(
			    bab_rp('col', 'hits'), 
    			bab_rp('order', 'asc'), 
    			bab_rp('pos', 0), 
			    $sd, $ed);
			}
		break;
	case "saddon":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
			{
			include_once $GLOBALS['babInstallPath']."stataddons.php";
			$babBodyPopup = new babBodyPopup();
			showStatAddon(
			    bab_rp('item'), 
			    bab_rp('date')
			);
			printBabBodyPopup();
			exit;
			}
		break;
	case "baskets":
		include_once $GLOBALS['babInstallPath']."statbaskets.php";
		listUserBaskets();
		break;
	case "dashboard":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION )
			{
			include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
			include_once $GLOBALS['babInstallPath']."statdashboard.php";
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			displayTimeIntervalInPopup(bab_rp('itwhat'), $sd, $ed, $idx, $GLOBALS['babBodyPopup']);
			showDashboard($sd, $ed);
			printBabBodyPopup();
			exit;
			}
		break;
	case "dashboardexport":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION )
			{
			include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
			include_once $GLOBALS['babInstallPath']."statdashboard.php";
	//		$GLOBALS['babBodyPopup'] = new babBodyPopup();
	//		displayTimeIntervalInPopup($itwhat, $sd, $ed, $idx, $GLOBALS['babBodyPopup']);
			exportDashboard($sd, $ed);
	//		printBabBodyPopup();
			exit;
			}
		break;
	case "delegation":
		if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
			{
	//		include_once $GLOBALS['babInstallPath']."statboard.php";
	//		summaryDelegatList($col, $order);
			include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
			include_once $GLOBALS['babInstallPath']."statdashboard.php";
			$GLOBALS['babBodyPopup'] = new babBodyPopup();
			displayTimeIntervalInPopup(bab_rp('itwhat'), $sd, $ed, $idx, $GLOBALS['babBodyPopup']);
			showDelegationDashboard($sd, $ed);
			printBabBodyPopup();
			exit;
			}
		break;
	case "basket":
		include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
		include_once $GLOBALS['babInstallPath']."statdashboard.php";
		$GLOBALS['babBodyPopup'] = new babBodyPopup();
		$idbasket = bab_rp('idbasket');
		displayTimeIntervalInPopup(bab_rp('itwhat'), $sd, $ed, $idx, $GLOBALS['babBodyPopup'], $idbasket);
		showBasket($idbasket, $sd, $ed);
		printBabBodyPopup();
		exit;
		break;
	case "basketexport":
		include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
		include_once $GLOBALS['babInstallPath']."statdashboard.php";
		$idbasket = bab_rp('idbasket');
		exportBasket($idbasket, $sd, $ed);
		exit;
		break;
		
		
	default:
		break;
	}

$babBody->title = bab_translate("Statistics");
if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER || bab_statisticsAccess() == BAB_STAT_ACCESS_DELEGATION )
{
$babBody->addItemMenu("stat", bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=stat&idx=stat");
$babBody->addItemMenu("pages", bab_translate("Pages"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pages");
$babBody->addItemMenu("pref", bab_translate("Preferences"), $GLOBALS['babUrlScript']."?tg=statconf&idx=pref");
$babBody->addItemMenu("bask", bab_translate("Baskets"), $GLOBALS['babUrlScript']."?tg=statconf&idx=bask");
}
if( bab_statisticsAccess() == BAB_STAT_ACCESS_MANAGER )
{
$babBody->addItemMenu("maj", bab_translate("Update"), $GLOBALS['babUrlScript']."?tg=statconf&idx=maj&statrows=12000");
}
$babBody->setCurrentItemMenu("stat");
?>