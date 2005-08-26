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

function summaryModules($col, $order, $startday, $endday)
	{
	global $babBody;
	class summaryModulesCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryModulesCls($col, $order, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->summaryBaseCls();
			$this->fullname = bab_translate("Ovidentia functions");
			$this->hitstxt = bab_translate("Hits");
			$req = "SELECT  stip.module_name, sum( stp.st_hits ) hits FROM  ".BAB_STATS_MODULES_TBL." stp left join ".BAB_STATS_IMODULES_TBL." stip  on stp.st_module_id=stip.id";
			if( !empty($startday) && !empty($endday))
				{
				$req .= " where stp.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " where stp.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " where stp.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by stp.st_module_id";

			$res = $babDB->db_query($req);

			$order = strtolower($order);
			$this->sortord = $order == "asc"? "desc": "asc";
			$this->sortcol = $col;
			$this->totalhits = 0;
			$this->arrinfo = array();
			while($arr = $babDB->db_fetch_array($res))
				{
				$tmparr = array();
				$tmparr['module'] = $arr['module_name'];
				$tmparr['hits'] = $arr['hits'];
				$this->arrinfo[] = $tmparr;
				$this->totalhits += $tmparr['hits'];
				}

			$this->totalhitspc = 100;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=mod&order=".($col == 'module'? $this->sortord: $order)."&col=module";
			$this->urlordhits = "idx=mod&order=".($col == 'hits'? $this->sortord: $order)."&col=hits";
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
				$this->modulename = bab_translate($this->arrinfo[$i]['module']);
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
	$temp = new summaryModulesCls($col, $order, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Ovidentia functions");
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
		header("Content-Length: ". strlen($output)."\n");
		header("Content-transfert-encoding: binary"."\n");
		print $output;
		exit;
		}
	else
		{
		$babBody->babecho(	bab_printTemplate($temp, "statmod.html", "summarymoduleslist"));
		return $temp->count;
		}
	}
?>