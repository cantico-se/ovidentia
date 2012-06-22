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

function summaryForums($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryForumsCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		var $startday = null;
		var $endday = null;

		function summaryForumsCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;

			$this->endday = $endday;
			$this->startday = $startday;

			$this->fullname = bab_translate("Forums");
			$this->hitstxt = bab_translate("Hits");
			$this->threadstxt = bab_translate("Threads");
			$this->poststxt = bab_translate("Posts");
			$req = "SELECT  ft.id, ft.name, sum( sat.st_hits ) hits FROM  ".BAB_STATS_FORUMS_TBL." sat left join ".BAB_FORUMS_TBL." ft  on sat.st_forum_id=ft.id  where ft.name is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and ft.id_dgowner='".$babBody->currentAdmGroup."'";
				}
			if( !empty($startday) && !empty($endday))
				{
				$req .= " and sat.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and sat.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and sat.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by sat.st_forum_id order by hits desc";
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
					$tmparr['forum'] = $arr['name'];
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

			$this->urlordmod = "idx=for&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=for&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			$this->urlordthread = "idx=for&order=".($col == 'threads'? $this->sortord: $order)."&col=threads&pos=".$pos;
			$this->urlordposts = "idx=for&order=".($col == 'posts'? $this->sortord: $order)."&col=posts&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=for&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=for&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=for&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=for&order=".$order."&col=".$col."&pos=".$bottom;
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
				$this->modulename = $this->arrinfo[$i]['forum'];
				$this->nbhits = $this->arrinfo[$i]['hits'];
				$this->nbhitspc = $this->totalhits > 0 ? round(($this->nbhits*100)/$this->totalhits,2): 0;
				$taille=($this->nbhits*100)/$this->totalhits;
				$this->size=$taille;
				$this->size2=100-$taille;

// 				list($this->nbthreads) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_THREADS_TBL." where forum='".$this->arrinfo[$i]['id']."'"));
// 				list($this->nbposts) = $babDB->db_fetch_row($babDB->db_query("SELECT count( pt.id ) FROM  `bab_posts` pt LEFT  JOIN bab_threads tt ON tt.id = pt.id_thread LEFT  JOIN bab_forums ft ON ft.id = tt.forum WHERE ft.id ='".$this->arrinfo[$i]['id']."'"));

				$sql = '
					SELECT COUNT(id)
					FROM '.BAB_THREADS_TBL.'
					WHERE forum=' . $babDB->quote($this->arrinfo[$i]['id']) . '
					AND `date` <= ' . $babDB->quote($this->endday) . '
				';
				list($nbThreads) = $babDB->db_fetch_row($babDB->db_query($sql));
				$sql = '
					SELECT COUNT(id)
					FROM '.BAB_THREADS_TBL.'
					WHERE forum=' . $babDB->quote($this->arrinfo[$i]['id']) . '
					AND `date` <= ' . $babDB->quote($this->endday) . '
					AND `date` >= ' . $babDB->quote($this->startday) . '
				';
				list($nbNewThreads) = $babDB->db_fetch_row($babDB->db_query($sql));
				$this->nbthreads = $nbThreads . ' (+' . $nbNewThreads . ')';

				$sql = '
					SELECT COUNT(pt.id)
					FROM `bab_posts` pt LEFT JOIN bab_threads tt ON tt.id = pt.id_thread LEFT JOIN bab_forums ft ON ft.id = tt.forum
					WHERE ft.id = ' . $babDB->quote($this->arrinfo[$i]['id']) . '
					AND pt.date <= ' . $babDB->quote($this->endday) . '
				';
				list($nbPosts) = $babDB->db_fetch_row($babDB->db_query($sql));
				$sql = '
					SELECT COUNT(pt.id)
					FROM `bab_posts` pt LEFT JOIN bab_threads tt ON tt.id = pt.id_thread LEFT JOIN bab_forums ft ON ft.id = tt.forum
					WHERE ft.id = ' . $babDB->quote($this->arrinfo[$i]['id']) . '
					AND pt.date <= ' . $babDB->quote($this->endday) . '
					AND pt.date >= ' . $babDB->quote($this->startday) . '
				';

				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sfor&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryForumsCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Forums");
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
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->threadstxt.$GLOBALS['exportchr'].$temp->poststxt.$GLOBALS['exportchr'].$temp->hitstxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->nbthreads.$GLOBALS['exportchr'].$temp->nbposts.$GLOBALS['exportchr'].$temp->nbhits.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
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
		$babBody->babecho( bab_printTemplate($temp, "statfor.html", "summaryforumslist"));
		return $temp->count;
		}
	}


function summaryThreads($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryThreadsCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryThreadsCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Threads");
			$this->forumtxt = bab_translate("Forum");

			$req = "SELECT ft.name, tt.id, pt.subject, sum( stt.st_hits ) hits FROM ".BAB_STATS_THREADS_TBL." stt left join ".BAB_THREADS_TBL." tt on tt.id=stt.st_thread_id left join ".BAB_POSTS_TBL." pt on pt.id=tt.post left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where pt.subject is not null ";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and ft.id_dgowner='".$babBody->currentAdmGroup."'";
				}

			if( !empty($startday) && !empty($endday))
				{
				$req .= " and stt.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and stt.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and stt.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by stt.st_thread_id order by hits desc";
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
					$tmparr['module'] = $arr['subject'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['id'] = $arr['id'];
					$tmparr['forum'] = $arr['name'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=forth&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=forth&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			$this->urlordfor = "idx=forth&order=".($col == 'forum'? $this->sortord: $order)."&col=forum&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=forth&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=forth&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=forth&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=forth&order=".$order."&col=".$col."&pos=".$bottom;
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
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sforth&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$this->forumname = $this->arrinfo[$i]['forum'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryThreadsCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Threads");
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
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->forumtxt.$GLOBALS['exportchr'].$temp->hitstxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->forumname.$GLOBALS['exportchr'].$temp->nbhits.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
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
		$babBody->babecho(	bab_printTemplate($temp, "statfor.html", "summarythreadslist"));
		return $temp->count;
		}
	}

function summaryPosts($col, $order, $pos, $startday, $endday)
	{
	global $babBody;
	class summaryPostsCls extends summaryBaseCls
		{
		var $fullname;
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;

		function summaryPostsCls($col, $order, $pos, $startday, $endday)
			{
			global $babBody, $babDB;
			$this->fullname = bab_translate("Posts");
			$this->forumtxt = bab_translate("Forum");
			$this->threadtxt = bab_translate("Thread");

			$req = "SELECT pt.id_thread, pt.id, pt.subject, sum( stp.st_hits ) hits FROM ".BAB_STATS_POSTS_TBL." stp left join ".BAB_POSTS_TBL." pt on pt.id=stp.st_post_id";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum";
				}

			$req .= " where pt.subject is not null";
			if( $babBody->currentAdmGroup != 0 )
				{
				$req .= " and ft.id_dgowner='".$babBody->currentAdmGroup."'";
				}


			if( !empty($startday) && !empty($endday))
				{
				$req .= " and stp.st_date between '".$startday."' and '".$endday."'";
				}
			else if( !empty($startday))
				{
				$req .= " and stp.st_date >= '".$startday."'";
				}
			else if( !empty($endday))
				{
				$req .= " and stp.st_date <= '".$endday."'";
				}

			$req .= " GROUP  by stp.st_post_id order by hits desc";
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
				if((isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ( $i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS ) )
					{
					$tmparr = array();
					$tmparr['module'] = $arr['subject'];
					$tmparr['hits'] = $arr['hits'];
					$tmparr['id'] = $arr['id'];
					$tmparr['idthread'] = $arr['id_thread'];

					$rr = $babDB->db_fetch_array($babDB->db_query("select pt.subject, ft.name from ".BAB_FORUMS_TBL." ft left join ".BAB_THREADS_TBL." tt on tt.forum=ft.id left join ".BAB_POSTS_TBL." pt on pt.id=tt.post where tt.id='".$arr['id_thread']."'"));
					$tmparr['forum'] = $rr['name'];
					$tmparr['thread'] = $rr['subject'];

					$this->arrinfo[] = $tmparr;
					$this->ptotalhits += $tmparr['hits'];
					}
				$this->totalhits += $arr['hits'];
				$i++;
				}

			$this->ptotalhitspc = $this->totalhits > 0 ? round(($this->ptotalhits*100)/$this->totalhits,2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordmod = "idx=forpo&order=".($col == 'module'? $this->sortord: $order)."&col=module&pos=".$pos;
			$this->urlordhits = "idx=forpo&order=".($col == 'hits'? $this->sortord: $order)."&col=hits&pos=".$pos;
			$this->urlordfor = "idx=forpo&order=".($col == 'forum'? $this->sortord: $order)."&col=forum&pos=".$pos;
			$this->urlordforth = "idx=forpo&order=".($col == 'thread'? $this->sortord: $order)."&col=thread&pos=".$pos;
			if( $this->bnavigation )
				{
				$this->prevpageurl = "idx=forpo&order=".$order."&col=".$col."&pos=".$prev;
				$this->nextpageurl = "idx=forpo&order=".$order."&col=".$col."&pos=".$next;
				$this->toppageurl = "idx=forpo&order=".$order."&col=".$col."&pos=".$top;
				$this->bottompageurl = "idx=forpo&order=".$order."&col=".$col."&pos=".$bottom;
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
				$this->urlview = $GLOBALS['babUrlScript']."?tg=stat&idx=sforpo&item=".$this->arrinfo[$i]['id']."&date=".$this->currentdate;
				$this->forumname = $this->arrinfo[$i]['forum'];
				$this->threadname = $this->arrinfo[$i]['thread'];

				$i++;
				return true;
				}
			else
				return false;

			}
		}
	$temp = new summaryPostsCls($col, $order, $pos, $startday, $endday);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Posts");
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
		$output .= $temp->fullname.$GLOBALS['exportchr'].$temp->forumtxt.$GLOBALS['exportchr'].$temp->threadtxt.$GLOBALS['exportchr'].$temp->hitstxt.$GLOBALS['exportchr']."%\n";
		while($temp->getnext())
			{
			$output .= $temp->modulename.$GLOBALS['exportchr'].$temp->forumname.$GLOBALS['exportchr'].$temp->threadname.$GLOBALS['exportchr'].$temp->nbhits.$GLOBALS['exportchr'].$temp->nbhitspc."\n";
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
		$babBody->babecho(	bab_printTemplate($temp, "statfor.html", "summarypostslist"));
		return $temp->count;
		}
	}


function showStatForum($id, $date)
{
	global $babBodyPopup;
	class showStatForumCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatForumCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_FORUMS_TBL." where id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sfor", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_FORUMS_TBL." WHERE st_forum_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

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


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_FORUMS_TBL." WHERE st_forum_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
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

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_FORUMS_TBL." WHERE st_forum_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
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
	$temp = new showStatForumCls($id, $date);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Forum").": ".$babBodyPopup->title;
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
		$babBodyPopup->babecho(bab_printTemplate($temp, "statfor.html", "summarydetail"));
		}
}

function showStatThread($id, $date)
{
	global $babBodyPopup;
	class showStatThreadCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatThreadCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select pt.subject from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on pt.id=tt.post where tt.id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sforth", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_THREADS_TBL." WHERE st_thread_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

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


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_THREADS_TBL." WHERE st_thread_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
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

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_THREADS_TBL." WHERE st_thread_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
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
	$temp = new showStatThreadCls($id, $date);
	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Thread").": ".$babBodyPopup->title;
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
		$babBodyPopup->babecho(bab_printTemplate($temp, "statfor.html", "summarydetail"));
		}
}

function showStatPost($id, $date)
{
	global $babBodyPopup;
	class showStatPostCls extends summaryDetailBaseCls
		{
		var $altbg = true;

		function showStatPostCls($id, $date)
			{
			global $babBodyPopup, $babBody, $babDB;


			list($babBodyPopup->title) = $babDB->db_fetch_row($babDB->db_query("select pt.subject from ".BAB_POSTS_TBL." pt where pt.id='".$id."'"));

			$rr = explode(',', $date);
			if( !is_array($rr) || count($rr) != 3)
				{
				$rr = array(date('Y'), date('n'),date('j'));
				}

			$this->summaryDetailBaseCls($rr[0], $rr[1], $rr[2], "sforpo", $id);

			$req = "SELECT  st_date , EXTRACT(DAY FROM st_date) as day, sum( st_hits ) hits FROM  ".BAB_STATS_POSTS_TBL." WHERE st_post_id ='".$id."' and st_date between '".sprintf("%04s-%02s-01", $rr[0], $rr[1])."' and '".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $this->nbdays)."' GROUP  BY st_date ORDER  BY st_date ASC ";

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


			$req = "SELECT  EXTRACT(MONTH FROM st_date) as month, sum( st_hits ) hits FROM  ".BAB_STATS_POSTS_TBL." WHERE st_post_id ='".$id."' and st_date between '".sprintf("%04s-01-01", $rr[0])."' and '".sprintf("%04s-12-31", $rr[0])."' GROUP BY month ORDER  BY month ASC ";
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

			$req = "SELECT  st_hour, st_hits as hits FROM  ".BAB_STATS_POSTS_TBL." WHERE st_post_id ='".$id."' and st_date ='".sprintf("%04s-%02s-%02s", $rr[0], $rr[1], $rr[2] )."' ORDER  BY st_hour ASC ";
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
	$temp = new showStatPostCls($id, $date);

	if( isset($GLOBALS['export']) && $GLOBALS['export'] == 1 )
		{
		$output = bab_translate("Post").": ".$babBodyPopup->title;
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
		$babBodyPopup->babecho(bab_printTemplate($temp, 'statfor.html', 'summarydetail'));
		}
}


function displayForumTree($startDay, $endDay)
{
	global $babBody;

	require_once $GLOBALS['babInstallPath'] . 'utilit/tree.php';
	$treeView = new bab_ForumTreeView('forum');
	$treeView->addStatistics($startDay, $endDay);
	$treeView->sort();
	$babBody->babEcho($treeView->printTemplate());
}
