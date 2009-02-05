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
 * @copyright Copyright (c) 2006 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';
include_once $babInstallPath.'utilit/statutil.php';
include_once $babInstallPath.'utilit/uiutil.php';

function summaryArticles($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryArticlesCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryArticlesCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Articles");
			$this->hitstxt = bab_translate("Hits");
			$req = "SELECT  at.id, at.id_topic, at.title, sum( sat.st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at  on sat.st_article_id=at.id";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat";
				}
			$req.= " where at.title is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and tct.id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sat.st_date between '".$babDB->db_escape_string($startday)."' and '".$babDB->db_escape_string($endday)."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sat.st_date >= '".$babDB->db_escape_string($startday)."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sat.st_date <= '".$babDB->db_escape_string($endday)."'";
				}

			$req .= " GROUP  by sat.st_article_id order by hits desc";
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
				if( (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ))
					{
					$tmparr = array();
					$tmparr['article'] = $arr['title'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['id'] = $arr['id'];
					$tmparr['idtopic'] = $arr['id_topic'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=art&order=".($col == 'article'? $this->sortord: $order)."&col=article&pos=".$pos;
			$this->urlordhits = "idx=art&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=art&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=art&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=art&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=art&order=".$order."&col=".$col."&pos=".$bottom;
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
				$this->modulename = $this->arrinfo[$i]['article'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sart&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryArticlesCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Articles");
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
		$babBody->babecho(	bab_printTemplate($temp, "statart.html", "summaryarticleslist"));
		return $temp->count;
		}
	}


function showReferentsArticle($col, $order, $pos, $item, $date)
	{
	global $babBody, $babBodyPopup;
	class showReferentsArticleCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function showReferentsArticleCls($col, $order, $pos, $item, $date)
			{
			global $babBody, $babDB, $babBodyPopup;
			$this->fullname = bab_translate("Ovidentia functions");
			$this->hitstxt = bab_translate("Hits");

			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select title from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($item)."'"));

			$req = "SELECT  it.id, it.module_name, sum( sat.st_hits ) hits FROM  ".BAB_STATS_ARTICLES_REF_TBL." sat left join ".BAB_STATS_IMODULES_TBL." it  on sat.st_module_id=it.id where sat.st_article_id='".$babDB->db_escape_string($item)."'";
			$req .= " GROUP  by sat.st_module_id order by hits desc";

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
				if( (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ))
					{
					$tmparr = array();
					$tmparr['module'] = $arr['module_name'];
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

			$this->urlordmod = $GLOBALS['babUrlScript']."?tg=stat&idx=refart&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos."&item=".$item."&date=".$date;
			$this->urlordhits = $GLOBALS['babUrlScript']."?tg=stat&idx=refart&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos."&item=".$item."&date=".$date;
			if( $this->bnavigation )
				{
				$this->prevpageurl = $GLOBALS['babUrlScript']."?tg=stat&idx=refart&order=".$order."&col=".$col."&pos=".$prev."&item=".$item."&date=".$date;
				$this->nextpageurl = $GLOBALS['babUrlScript']."?tg=stat&idx=refart&order=".$order."&col=".$col."&pos=".$next."&item=".$item."&date=".$date;
				$this->toppageurl = $GLOBALS['babUrlScript']."?tg=stat&idx=refart&order=".$order."&col=".$col."&pos=".$top."&item=".$item."&date=".$date;
				$this->bottompageurl = $GLOBALS['babUrlScript']."?tg=stat&idx=refart&order=".$order."&col=".$col."&pos=".$bottom."&item=".$item."&date=".$date;
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
	$temp = new showReferentsArticleCls($col, $order, $pos, $item, $date);
	$babBodyPopup->babecho(	bab_printTemplate($temp, "statart.html", "summaryarticlereflist"));
	}


function summaryTopicsArticles($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryTopicsArticlesCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryTopicsArticlesCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Topics");
			$this->hitstxt = bab_translate("Hits");
			$req = "SELECT tt.id, tt.category, sum( sat.st_hits ) hits FROM ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at on sat.st_article_id=at.id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req.= " left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat";
				}
			$req.= " where at.title is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req.= " and  tct.id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sat.st_date between '".$babDB->db_escape_string($startday)."' and '".$babDB->db_escape_string($endday)."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sat.st_date >= '".$babDB->db_escape_string($startday)."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sat.st_date <= '".$babDB->db_escape_string($endday)."'";
				}

			$req .= " GROUP  by at.id_topic order by hits desc";
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
					$tmparr['article'] = $arr['category'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['idtopic'] = $arr['id'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=topart&order=".($col == 'article'? $this->sortord: $order)."&col=article&pos=".$pos;
			$this->urlordhits = "idx=topart&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=topart&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=topart&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=topart&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=topart&order=".$order."&col=".$col."&pos=".$bottom;
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
				$this->modulename = $this->arrinfo[$i]['article'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=stop&item=".$this->arrinfo[$i]['idtopic']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryTopicsArticlesCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Articles topics");
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
		$babBody->babecho(	bab_printTemplate($temp, "statart.html", "summaryarticleslist"));
		return $temp->count;
		}
	}


function summaryTopicCategoryArticles($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryTopicCategoryArticlesCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryTopicCategoryArticlesCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Topics categories");
			$this->hitstxt = bab_translate("Hits");
			$req = "SELECT tct.id, tct.title, sum( sat.st_hits ) hits FROM ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at on sat.st_article_id=at.id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat where at.title is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and tct.id_dgowner='".$babDB->db_escape_string($babBody->currentAdmGroup)."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sat.st_date between '".$babDB->db_escape_string($startday)."' and '".$babDB->db_escape_string($endday)."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sat.st_date >= '".$babDB->db_escape_string($startday)."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sat.st_date <= '".$babDB->db_escape_string($endday)."'";
				}

			$req .= " GROUP  by tct.id order by hits desc";
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
					$tmparr['article'] = $arr['title'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['idtopcat'] = $arr['id'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=topcat&order=".($col == 'article'? $this->sortord: $order)."&col=article&pos=".$pos;
			$this->urlordhits = "idx=topcat&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=topcat&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=topcat&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=topcat&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=topcat&order=".$order."&col=".$col."&pos=".$bottom;
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
				$this->modulename = $this->arrinfo[$i]['article'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=scat&item=".$this->arrinfo[$i]['idtopcat']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryTopicCategoryArticlesCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Topics categories");
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
		$babBody->babecho(	bab_printTemplate($temp, "statart.html", "summaryarticleslist"));
		return $temp->count;
		}
	}

function showStatArticle($idart, $date)
{
	global $babBodyPopup;
	class showStatArticleCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatArticleCls($idart, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select title from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($idart)."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sart", $idart);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." WHERE st_article_id ='".$babDB->db_escape_string($idart)."' and st_date between '".$babDB->db_escape_string(sprintf("%04s-%02s-01", $rr[0], $rr[1]))."' and '".$babDB->db_escape_string(sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays))."' GROUP  BY st_date ORDER  BY st_date ASC ";

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


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." WHERE st_article_id ='".$babDB->db_escape_string($idart)."' and st_date between '".$babDB->db_escape_string(sprintf("%04s-01-01", $rr[0]))."' and '".$babDB->db_escape_string(sprintf("%04s-12-31", $rr[0]))."' GROUP BY month ORDER  BY month ASC ";
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

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_ARTICLES_TBL." WHERE st_article_id ='".$babDB->db_escape_string($idart)."' and st_date ='".$babDB->db_escape_string(sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] ))."' ORDER  BY st_hour ASC ";
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

	$temp = new showStatArticleCls($idart, $date);

	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Article").": ".$babBodyPopup->title;
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
		$babBodyPopup->babecho(bab_printTemplate($temp, "statart.html", "summaryarticle"));
		}
}


function showStatTopic($idtopic, $date)
{
	global $babBodyPopup;
	class showStatTopicCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatTopicCls($idtopic, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($idtopic)."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "stop", $idtopic);

			$req = "SELECT  sat.st_date , EXTRACT(DAY FROM sat.st_date) as day, sum( sat.st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at on at.id=sat.st_article_id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic WHERE tt.id ='".$babDB->db_escape_string($idtopic)."' and sat.st_date between '".$babDB->db_escape_string(sprintf("%04s-%02s-01", $rr[0], $rr[1]))."' and '".$babDB->db_escape_string(sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays))."' GROUP  BY st_date ORDER  BY st_date ASC ";

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


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at on at.id=sat.st_article_id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic WHERE tt.id ='".$babDB->db_escape_string($idtopic)."' and st_date between '".$babDB->db_escape_string(sprintf("%04s-01-01", $rr[0]))."' and '".$babDB->db_escape_string(sprintf("%04s-12-31", $rr[0]))."' GROUP BY month ORDER  BY month ASC ";
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

			$req = "SELECT  st_hour, sum(st_hits) as hits FROM  ".BAB_STATS_ARTICLES_TBL."  sat left join ".BAB_ARTICLES_TBL." at on at.id=sat.st_article_id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic WHERE tt.id ='".$babDB->db_escape_string($idtopic)."' and st_date ='".$babDB->db_escape_string(sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] ))."' group by st_hour ORDER  BY st_hour ASC ";

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

	$temp = new showStatTopicCls($idtopic, $date);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Topic").": ".$babBodyPopup->title;
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
		$babBodyPopup->babecho(bab_printTemplate($temp, "statart.html", "summaryarticle"));
		}
}

function showStatTopicCategory($idtopcat, $date)
{
	global $babBodyPopup;
	class showStatTopicCategoryCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatTopicCategoryCls($idtopcat, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($idtopcat)."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "scat", $idtopcat);

			$req = "SELECT  sat.st_date , EXTRACT(DAY FROM sat.st_date) as day, sum( sat.st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at on at.id=sat.st_article_id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic left join ".BAB_TOPICS_CATEGORIES_TBL." ttc on ttc.id=tt.id_cat WHERE ttc.id ='".$babDB->db_escape_string($idtopcat)."' and sat.st_date between '".$babDB->db_escape_string(sprintf("%04s-%02s-01", $rr[0], $rr[1]))."' and '".$babDB->db_escape_string(sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays))."' GROUP  BY st_date ORDER  BY st_date ASC ";

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


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_ARTICLES_TBL." sat left join ".BAB_ARTICLES_TBL." at on at.id=sat.st_article_id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic left join ".BAB_TOPICS_CATEGORIES_TBL." ttc on ttc.id=tt.id_cat WHERE ttc.id ='".$babDB->db_escape_string($idtopcat)."' and st_date between '".$babDB->db_escape_string(sprintf("%04s-01-01", $rr[0]))."' and '".$babDB->db_escape_string(sprintf("%04s-12-31", $rr[0]))."' GROUP BY month ORDER  BY month ASC ";
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

			$req = "SELECT  st_hour, sum(st_hits) as hits FROM  ".BAB_STATS_ARTICLES_TBL."  sat left join ".BAB_ARTICLES_TBL." at on at.id=sat.st_article_id left join ".BAB_TOPICS_TBL." tt on tt.id=at.id_topic left join ".BAB_TOPICS_CATEGORIES_TBL." ttc on ttc.id=tt.id_cat WHERE ttc.id ='".$babDB->db_escape_string($idtopcat)."' and st_date ='".$babDB->db_escape_string(sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] ))."' group by st_hour ORDER  BY st_hour ASC ";

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

	$temp = new showStatTopicCategoryCls($idtopcat, $date);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Topic category").": ".$babBodyPopup->title;
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
		$babBodyPopup->babecho(bab_printTemplate($temp, 'statart.html', 'summaryarticle'));
		}
}


function displayArticleTree($startDay, $endDay)
{
	global $babBody;

	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';
	$treeView = new bab_ArticleTreeView('article');
	$treeView->setAttributes( bab_ArticleTreeView::SHOW_ARTICLES
							| bab_ArticleTreeView::SELECTABLE_TOPICS
							| bab_ArticleTreeView::SELECTABLE_ARTICLES
							| bab_ArticleTreeView::SHOW_TOOLBAR
							| bab_ArticleTreeView::MEMORIZE_OPEN_NODES
							);
							
	$treeView->addStatistics($startDay, $endDay);
	$treeView->sort();
	$babBody->babEcho($treeView->printTemplate());
}
