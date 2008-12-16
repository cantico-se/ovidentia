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
include_once $babInstallPath."utilit/statutil.php";
include_once $babInstallPath."utilit/uiutil.php";

function summarySearchKeyWords($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summarySearchKeyWordsCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summarySearchKeyWordsCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Words");
			$this->hitstxt = bab_translate("Hits");
			$req = "SELECT  sat.st_word, sum( sat.st_hits ) hits FROM  ".BAB_STATS_SEARCH_TBL." sat ";
			if( !empty($startday) && !empty($endday))
				{
				$req .= " where st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " where st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " where st_date <= '".$endday."'";
				}

			$req .= " GROUP  by st_word order by hits desc";

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
					$tmparr['module'] = $arr['st_word'];
					$tmparr['hits'] = $arr['hits'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=search&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=search&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=search&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=search&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=search&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=search&order=".$order."&col=".$col."&pos=".$bottom;
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
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summarySearchKeyWordsCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Search keywords");
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
		$babBody->babecho(	bab_printTemplate($temp, "statword.html", "summarywordslist"));
		return $temp->count;
		}
	}
?>