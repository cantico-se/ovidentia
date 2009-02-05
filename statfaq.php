<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
// 
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once $babInstallPath.'utilit/statutil.php';
include_once $babInstallPath.'utilit/uiutil.php';

function summaryFaqs($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryFasCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryFasCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Faqs");
			$this->hitstxt = bab_translate("Hits");
			$req = "SELECT  ft.id, ft.category, sum( sft.st_hits ) hits FROM  ".BAB_STATS_FAQS_TBL." sft left join ".BAB_FAQCAT_TBL." ft  on sft.st_faq_id=ft.id  where ft.category is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and ft.id_dgowner='".$babBody->currentAdmGroup."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sft.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sft.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sft.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by sft.st_faq_id order by hits desc";
			$res = $babDB->db_query($req);
			$this->total = $babDB->db_num_rows($res);

			if( $this->total > BAB_STAT_MAX_ROWS)
				{
				$this->bnavigation = true;

				$prev = $pos - BAB_STAT_MAX_ROWS;
				if( $prev < 0)
					{
					$prev = 0;
					}

				$next = $pos + BAB_STAT_MAX_ROWS;
				if( $next > $this->total)
					{
					$next = $pos;
					}
				$top = 0;
				$bottom = $this->total - $this->total %  BAB_STAT_MAX_ROWS;
				}
			else
				{
				$this->bnavigation = false;
				}

			$this->startnum = $pos+1;
			$this->lastnum = ($pos + BAB_STAT_MAX_ROWS) > $this->total ? $this->total: ($pos + BAB_STAT_MAX_ROWS);
			$order = mb_strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->totalhits = 0;
			$this->ptotalhits = 0;
			$this->arrinfo = array();
			$i = 0;
			while($arr = $babDB->db_fetch_array($res))
				{
				if( (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ) )
					{
					$tmparr = array();
					$tmparr['module'] = $arr['category'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['id'] = $arr['id'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=faq&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=faq&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=faq&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=faq&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=faq&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=faq&order=".$order."&col=".$col."&pos=".$bottom;
				}
			$this->summaryBaseCls();
			}

		function isNumeric($col)
			{
			switch( $this->sortcol )
				{
				case 'hits':
					return true;
				default:
					return false;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->modulename = $this->arrinfo[$i]['module'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sfaq&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryFasCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Faqs");
		if( !empty($startday) && !empty($endday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		else if( !empty($startday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - )";
			}
		else if( !empty($endday))
			{
			$output .= " ( - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		$output .= " - ".bab_translate("Total: ").$temp->totalhits;
		$output .= "\n";
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->hitstxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->nbhits.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". mb_strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho(	bab_printTemplate($temp, "statfaq.html", "summaryfaqslist"));
		return $temp->count;
		}
	}


function summaryQuestionsFaqs($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryQuestionsFaqsCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryQuestionsFaqsCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Questions");
			$this->hitstxt = bab_translate("Hits");
			$this->faqtxt = bab_translate("Faq");
			$req = "SELECT fqrt.id, fqrt.question, ft.category, sum( sft.st_hits ) hits FROM ".BAB_STATS_FAQQRS_TBL." sft left join ".BAB_FAQQR_TBL." fqrt on sft.st_faqqr_id=fqrt.id left join ".BAB_FAQCAT_TBL." ft on ft.id=fqrt.idcat where fqrt.question is not null ";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and ft.id_dgowner='".$babBody->currentAdmGroup."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sft.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sft.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sft.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by sft.st_faqqr_id order by hits desc";
			$res = $babDB->db_query($req);
			$this->total = $babDB->db_num_rows($res);

			if( $this->total > BAB_STAT_MAX_ROWS)
				{
				$this->bnavigation = true;

				$prev = $pos - BAB_STAT_MAX_ROWS;
				if( $prev < 0)
					{
					$prev = 0;
					}

				$next = $pos + BAB_STAT_MAX_ROWS;
				if( $next > $this->total)
					{
					$next = $pos;
					}
				$top = 0;
				$bottom = $this->total - $this->total %  BAB_STAT_MAX_ROWS;
				}
			else
				{
				$this->bnavigation = false;
				}

			$this->startnum = $pos+1;
			$this->lastnum = ($pos + BAB_STAT_MAX_ROWS) > $this->total ? $this->total: ($pos + BAB_STAT_MAX_ROWS);
			$order = mb_strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->totalhits = 0;
			$this->ptotalhits = 0;
			$this->arrinfo = array();
			$i = 0;
			while($arr = $babDB->db_fetch_array($res))
				{
				if( (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ) )
					{
					$tmparr = array();
					$tmparr['module'] = $arr['question'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['id'] = $arr['id'];
					$tmparr['faq'] = $arr['category'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=faqqr&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=faqqr&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			$this->urlordfaq = "idx=faqqr&order=".($col == 'faq'? $this->sortord: $order)."&col=faq&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=faqqr&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=faqqr&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=faqqr&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=faqqr&order=".$order."&col=".$col."&pos=".$bottom;
				}
			$this->summaryBaseCls();
			}

		function isNumeric($col)
			{
			switch( $this->sortcol )
				{
				case 'hits':
					return true;
				default:
					return false;
				}
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->modulename = $this->arrinfo[$i]['module'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sfaqqr&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$this->faqname = $this->arrinfo[$i]['faq'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryQuestionsFaqsCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Faqs questions");
		if( !empty($startday) && !empty($endday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		else if( !empty($startday))
			{
			$output .= " (".bab_strftime(bab_mktime($startday." 00:00:00"), false)." - )";
			}
		else if( !empty($endday))
			{
			$output .= " ( - ".bab_strftime(bab_mktime($endday." 00:00:00"), false).")";
			}
		$output .= " - ".bab_translate("Total: ").$temp->totalhits;
		$output .= "\n";
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->hitstxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->nbhits.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
			}
		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". mb_strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho(	bab_printTemplate($temp, "statfaq.html", "summaryfaqqrslist"));
		return $temp->count;
		}
	}

function showStatFaq($id, $date)
{
	global $babBodyPopup;
	class showStatFaqCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatFaqCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select category from ".BAB_FAQCAT_TBL." where id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sfaq", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_FAQS_TBL." WHERE st_faq_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

			$this->dayinfo = array();
			$this->maxdayhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->dayinfo[$arr['day']] = $arr['hits'];
				if( $arr['hits'] > $this->maxdayhits )
					{
					$this->maxdayhits = $arr['hits'];
					}
				}


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_FAQS_TBL." WHERE st_faq_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
			$this->monthinfo = array();
			$this->maxmonthhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->monthinfo[$arr['month']] = $arr['hits'];
				if( $arr['hits'] > $this->maxmonthhits )
					{
					$this->maxmonthhits = $arr['hits'];
					}
				}

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_FAQS_TBL." WHERE st_faq_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
			$this->hourinfo = array();
			$this->maxhourhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->hourinfo[$arr['st_hour']] = $arr['hits'];
				if( $arr['hits'] > $this->maxhourhits )
					{
					$this->maxhourhits = $arr['hits'];
					}
				}

			}

		}
	$temp = new showStatFaqCls($id, $date);

	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Faq").": ".$babBodyPopup->title;
		$output .= "\n";
		$output .= bab_translate("Day").": ".$temp->daydate;
		$output .= "\n";
		$output .= bab_translate("Hour").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnexthour())
			{
			$output .= $temp->hour.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		$output .= "\n";
		$output .= bab_translate("Month").": ".$temp->monthdate;
		$output .= "\n";
		$output .= bab_translate("Day").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnextday())
			{
			$output .= $temp->day.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		$output .= "\n";
		$output .= bab_translate("Year").": ".$temp->yeardate;
		$output .= "\n";
		$output .= bab_translate("Month").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnextmonth())
			{
			$output .= $temp->monthname.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". mb_strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBodyPopup->babecho(bab_printTemplate($temp, "statfaq.html", "summarydetail"));
		}
}

function showStatFaqQuestion($id, $date)
{
	global $babBodyPopup;
	class showStatFaqQuestionCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatFaqQuestionCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select question from ".BAB_FAQQR_TBL." where id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sfaqqr", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_FAQQRS_TBL." WHERE st_faqqr_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

			$this->dayinfo = array();
			$this->maxdayhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->dayinfo[$arr['day']] = $arr['hits'];
				if( $arr['hits'] > $this->maxdayhits )
					{
					$this->maxdayhits = $arr['hits'];
					}
				}


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_FAQQRS_TBL." WHERE st_faqqr_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
			$this->monthinfo = array();
			$this->maxmonthhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->monthinfo[$arr['month']] = $arr['hits'];
				if( $arr['hits'] > $this->maxmonthhits )
					{
					$this->maxmonthhits = $arr['hits'];
					}
				}

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_FAQQRS_TBL." WHERE st_faqqr_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
			$this->hourinfo = array();
			$this->maxhourhits = 0;
			$res = $babDB->db_query($req);
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->hourinfo[$arr['st_hour']] = $arr['hits'];
				if( $arr['hits'] > $this->maxhourhits )
					{
					$this->maxhourhits = $arr['hits'];
					}
				}

			}

		}
	$temp = new showStatFaqQuestionCls($id, $date);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Question").": ".$babBodyPopup->title;
		$output .= "\n";
		$output .= bab_translate("Day").": ".$temp->daydate;
		$output .= "\n";
		$output .= bab_translate("Hour").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnexthour())
			{
			$output .= $temp->hour.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		$output .= "\n";
		$output .= bab_translate("Month").": ".$temp->monthdate;
		$output .= "\n";
		$output .= bab_translate("Day").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnextday())
			{
			$output .= $temp->day.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		$output .= "\n";
		$output .= bab_translate("Year").": ".$temp->yeardate;
		$output .= "\n";
		$output .= bab_translate("Month").$GLOBALS['exportchr'].$temp->hitstxt."\n";
		while($temp->getnextmonth())
			{
			$output .= $temp->monthname.$GLOBALS['exportchr'].$temp->hits."\n";
			}

		header("Content-Disposition: attachment; filename=\"export.csv\""."\n");
		header("Content-Type: text/plain"."\n");
		header("Content-Length: ". mb_strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBodyPopup->babecho(bab_printTemplate($temp, 'statfaq.html', 'summarydetail'));
		}
}


function displayFaqTree($startDay, $endDay)
{
	global $babBody;

	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';
	$treeView = new bab_FaqTreeView('faq');
	$treeView->addStatistics($startDay, $endDay);
	$treeView->sort();
	$babBody->babEcho($treeView->printTemplate());
}
