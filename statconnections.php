<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
//"admin/admfaq.php"
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
include_once $babInstallPath . 'utilit/statutil.php';
include_once $babInstallPath . 'utilit/uiutil.php';


function summaryConnections($col, $order, $pos, $startday, $endday)
{
	global $babBody;

	class summaryConnectionsCls extends summaryBaseCls
	{
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;
		
		var $startday;
		var $endday;

		var $startnum;
		var $lastnum;
		var $total;
		var $sortord;
		var $sortcol;
		
		var $bnavigation;
		
		var $t_connections;
		var $t_user_name;

		function summaryConnectionsCls($col, $order, $pos, $startday, $endday)
		{
			global $babBody, $babDB;
			$this->t_user_name = bab_translate("User");
			$this->t_connections = bab_translate("Connections");
	
			$this->startday = $startday;
			$this->endday = $endday;

			$req = 'SELECT users.id AS id_user, users.firstname, users.lastname, COUNT(*) AS nb_connections';
			$req .= ' FROM ' . BAB_STATS_CONNECTIONS_TBL . ' AS connections INNER JOIN ' . BAB_USERS_TBL . ' AS users ON users.id=connections.id_user';
			$where = array();
			if (!empty($startday)) {
				$where[] = 'connections.login_time >= ' . $babDB->quote($startday);
			}
			if (!empty($endday)) {
				$where[] = 'connections.login_time <= ' . $babDB->quote($endday . ' 23:59:59');
			}
			if (!empty($where))
				$req .= ' WHERE ' . implode(' AND ', $where);
			$req .= ' GROUP BY users.id';
			$req .= ' ORDER BY nb_connections DESC';

			$res = $babDB->db_query($req);
			$this->total = $babDB->db_num_rows($res);

			$this->bnavigation = ($this->total > BAB_STAT_MAX_ROWS);
			if ($this->bnavigation) {
				$prev = $pos - BAB_STAT_MAX_ROWS;
				if ($prev < 0) {
					$prev = 0;
				}

				$next = $pos + BAB_STAT_MAX_ROWS;
				if( $next > $this->total) {
					$next = $pos;
				}
				$top = 0;
				$bottom = $this->total - $this->total %  BAB_STAT_MAX_ROWS;
			}

			$this->startnum = $pos + 1;
			$this->lastnum = ($pos + BAB_STAT_MAX_ROWS) > $this->total ? $this->total: ($pos + BAB_STAT_MAX_ROWS);
			$order = mb_strtolower($order);
			$this->sortord = ($order == 'asc' ? 'desc': 'asc');
			$this->sortcol = $col;
			$this->totalconnections = 0;
			$this->ptotalconnections = 0;
			$this->arrinfo = array();
			for ($i = 0; $arr = $babDB->db_fetch_array($res); $i++) {
				if ((isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ($i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS)) {
					$tmparr = array();
					$tmparr['id_user'] = $arr['id_user'];
					$tmparr['user'] = $arr['lastname'] . ' ' . $arr['firstname'];
					$tmparr['connections'] = $arr['nb_connections'];
					$this->arrinfo[] = $tmparr;
					$this->ptotalconnections += $arr['nb_connections'];
				}
				$this->totalconnections += $arr['nb_connections'];
			}

			$this->ptotalconnectionspc = $this->totalconnections > 0 ? round(($this->ptotalconnections * 100) / $this->totalconnections, 2): 0;

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlorduser = 'idx=connections&order=' . ($col == 'user' ? $this->sortord : $order) . '&col=user&pos=' . $pos;
			$this->urlordconnections = 'idx=connections&order=' . ($col == 'connections' ? $this->sortord : $order) . '&col=connections&pos=' . $pos;
			if ($this->bnavigation) {
				$this->prevpageurl = 'idx=connections&order=' . $order . '&col=' . $col . '&pos=' . $prev;
				$this->nextpageurl = 'idx=connections&order=' . $order . '&col=' . $col . '&pos=' . $next;
				$this->toppageurl = 'idx=connections&order=' . $order . '&col=' . $col . '&pos=' . $top;
				$this->bottompageurl = 'idx=connections&order=' . $order . '&col=' . $col . '&pos=' . $bottom;
			}
			$this->summaryBaseCls();
		}

		function isNumeric($col)
		{
			switch ($this->sortcol)
			{
				case 'connections':
					return true;
				default:
					return false;
			}
		}

		function getnext()
		{
			static $i = 0;

			if ($i >= $this->count) {
				return false;
			}
			$this->altbg = $this->altbg ? false : true;
			$this->user_name = $this->arrinfo[$i]['user'];
			$this->nb_connections = $this->arrinfo[$i]['connections'];
			$this->nb_connectionspc = $this->totalconnections > 0 ? round(($this->nb_connections * 100) / $this->totalconnections, 2) : 0;
			$this->urldetail = $GLOBALS['babUrlScript'] . '?tg=stat&idx=connection&item=' . $this->arrinfo[$i]['id_user'] . '&sd=' . $this->startday . '&ed=' . $this->endday . '&reqvars=item=' . $this->arrinfo[$i]['id_user'];
			$i++;
			return true;
		}

	}
		
	$temp = new summaryConnectionsCls($col, $order, $pos, $startday, $endday);

	if (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) {
		$output = bab_translate("Connections");
		if (!empty($startday) && !empty($endday)) {
			$output .= ' (' . bab_strftime(bab_mktime($startday . ' 00:00:00'), false) . ' - ' . bab_strftime(bab_mktime($endday . ' 00:00:00'), false) . ')';
		} else if (!empty($startday)) {
			$output .= ' (' . bab_strftime(bab_mktime($startday . ' 00:00:00'), false) . ' - )';
		} else if (!empty($endday)) {
			$output .= ' ( - ' . bab_strftime(bab_mktime($endday . ' 00:00:00'), false) . ')';
		}
		$output .= ' - ' . bab_translate("Total: ") . $temp->totalconnections;
		$output .= "\n";
		$output .= $temp->t_user_name . $GLOBALS['exportchr'] . $temp->t_connections . $GLOBALS['exportchr'] . "%\n";
		while ($temp->getnext()) {
			$output .= $temp->user_name . $GLOBALS['exportchr'] . $temp->nb_connections . $GLOBALS['exportchr'] . $temp->nb_connectionspc . "\n";
		}
		header('Content-Disposition: attachment; filename="export.csv"' . "\n");
		header('Content-Type: text/plain' . "\n");
		header('Content-Length: ' . mb_strlen($output) . "\n");
		header('Content-transfert-encoding: binary' . "\n");
		print $output;
		exit;
	} else {
		$babBody->babecho(	bab_printTemplate($temp, 'statconnections.html', 'summaryconnectionslist'));
		return $temp->count;
	}

}




function detailConnections($col, $order, $pos, $startday, $endday, $userId)
{
	global $babBody;

	class detailConnectionsCls extends summaryBaseCls
	{
		var $res;
		var $count;
		var $url;
		var $urlname;
		var $altbg = true;
		
		var $startnum;
		var $lastnum;
		var $total;
		var $sortord;
		var $sortcol;
		
		var $bnavigation;
		
		var $id_user;

		var $t_connection_time;
		var $t_duration;

		function detailConnectionsCls($col, $order, $pos, $startday, $endday, $userId)
		{
			global $babBody, $babDB;

			$this->sorttxt = bab_translate("Sort");
			$this->t_connection_time = bab_translate("Connection start");
			$this->t_duration = bab_translate("Duration");

			$this->id_user = $userId;

			$req = 'SELECT connections.login_time, UNIX_TIMESTAMP(connections.last_action_time) - UNIX_TIMESTAMP(connections.login_time) AS connection_duration';
			$req .= ' FROM ' . BAB_STATS_CONNECTIONS_TBL . ' AS connections';
			$where = array('connections.id_user = ' . $babDB->quote($userId));
			if (!empty($startday)) {
				$where[] = 'connections.login_time >= ' . $babDB->quote($startday);
			}
			if (!empty($endday)) {
				$where[] = 'connections.login_time <= ' . $babDB->quote($endday . ' 23:59:59');
			}
			if (!empty($where))
				$req .= ' WHERE ' . implode(' AND ', $where);
				
			switch($col) {
				case 'duration':
					$order_column = 'connection_duration';
					break;
				case 'connection':
				default:
					$order_column = 'login_time';
					break;
			}
			$req .= ' ORDER BY ' . $order_column . ' ' . ($order == 'asc' ? 'desc': 'asc');
			
			$res = $babDB->db_query($req);
			$this->total = $babDB->db_num_rows($res);

			$this->bnavigation = ($this->total > BAB_STAT_MAX_ROWS);
			if ($this->bnavigation) {
				$prev = $pos - BAB_STAT_MAX_ROWS;
				if ($prev < 0) {
					$prev = 0;
				}

				$next = $pos + BAB_STAT_MAX_ROWS;
				if( $next > $this->total) {
					$next = $pos;
				}
				$top = 0;
				$bottom = $this->total - $this->total %  BAB_STAT_MAX_ROWS;
			}

			$this->startnum = $pos + 1;
			$this->lastnum = ($pos + BAB_STAT_MAX_ROWS) > $this->total ? $this->total: ($pos + BAB_STAT_MAX_ROWS);
			$order = mb_strtolower($order);
			$this->sortord = ($order == 'asc' ? 'desc': 'asc');
			$this->sortcol = $col;
			$this->totalduration = 0;
			$this->ptotalduration = 0;
			$this->arrinfo = array();
			for ($i = 0; $arr = $babDB->db_fetch_array($res); $i++) {
				if ((isset($GLOBALS['export']) && $GLOBALS['export'] == 1) || ($i >= $pos && $i < $pos + BAB_STAT_MAX_ROWS)) {
					$tmparr = array();
					$tmparr['connection'] = $arr['login_time'];
					$tmparr['login_time'] = bab_shortDate(bab_mktime($arr['login_time']));
					$nbSeconds = $arr['connection_duration'] % 60;
					$nbMinutes = floor($arr['connection_duration'] / 60) % 60;
					$nbHours = floor($arr['connection_duration'] / 3600);
					
					$tmparr['duration'] = $arr['connection_duration'];
					$tmparr['connection_duration'] = $this->formatTime($arr['connection_duration']);
					$this->arrinfo[] = $tmparr;
					$this->ptotalduration += $arr['connection_duration'];
				}
				$this->totalduration += $arr['connection_duration'];
			}

			$this->ptotaldurationpc = $this->totalduration > 0 ? round(($this->ptotalduration * 100) / $this->totalduration, 2) : 0;

			$this->ptotalduration = $this->formatTime($this->ptotalduration);
			$this->totalduration = $this->formatTime($this->totalduration);

			usort($this->arrinfo, array($this, 'compare'));
			$this->count = count($this->arrinfo);

			$this->urlordconnection = 'idx=connection&order=' . ($col == 'connection' ? $this->sortord : $order) . '&col=connection&pos=' . $pos . '&item=' . $userId;
			$this->urlordduration = 'idx=connection&order=' . ($col == 'duration' ? $this->sortord : $order) . '&col=duration&pos=' . $pos . '&item=' . $userId;
			if ($this->bnavigation) {
				$this->prevpageurl = 'idx=connection&order=' . $order . '&col=' . $col . '&pos=' . $prev . '&item=' . $userId;
				$this->nextpageurl = 'idx=connection&order=' . $order . '&col=' . $col . '&pos=' . $next . '&item=' . $userId;
				$this->toppageurl = 'idx=connection&order=' . $order . '&col=' . $col . '&pos=' . $top . '&item=' . $userId;
				$this->bottompageurl = 'idx=connection&order=' . $order . '&col=' . $col . '&pos=' . $bottom . '&item=' . $userId;
			}
			$this->summaryBaseCls();
		}

		function formatTime($nbSeconds)
		{
			$nbMinutes = floor($nbSeconds / 60) % 60;
			$nbHours = floor($nbSeconds / 3600);
			$nbSeconds = $nbSeconds % 60;
			
			return sprintf('%d:%02d:%02d', $nbHours, $nbMinutes, $nbSeconds);
		}

		function isNumeric($col)
		{
			switch($col)
			{
				case 'duration':
					return true;
				default:
					return false;
			}
		}

		function getnext()
		{
			static $i = 0;

			if ($i >= $this->count) {
				return false;
			}
			$this->altbg = $this->altbg ? false : true;
			$this->login_time = $this->arrinfo[$i]['login_time'];
			$this->connection_duration = $this->arrinfo[$i]['connection_duration'];
			$this->connection_durationpc = $this->totalduration > 0 ? round(($this->connection_duration * 100) / $this->totalduration, 2) : 0;
			$i++;
			return true;
		}

	}
		
	$temp = new detailConnectionsCls($col, $order, $pos, $startday, $endday, $userId);

	if (isset($GLOBALS['export']) && $GLOBALS['export'] == 1) {
		$output = bab_translate("Connections");
		if (!empty($startday) && !empty($endday)) {
			$output .= ' (' . bab_strftime(bab_mktime($startday . ' 00:00:00'), false) . ' - ' . bab_strftime(bab_mktime($endday . ' 00:00:00'), false) . ')';
		} else if (!empty($startday)) {
			$output .= ' (' . bab_strftime(bab_mktime($startday . ' 00:00:00'), false) . ' - )';
		} else if (!empty($endday)) {
			$output .= ' ( - ' . bab_strftime(bab_mktime($endday . ' 00:00:00'), false) . ')';
		}
		$output .= ' - ' . bab_translate("Total: ") . $temp->totalduration;
		$output .= "\n";
		$output .= $temp->t_connection_time . $GLOBALS['exportchr'] . $temp->t_duration . "\n";
		while ($temp->getnext()) {
			$output .= $temp->login_time . $GLOBALS['exportchr'] . $temp->connection_duration . "\n";
		}
		header('Content-Disposition: attachment; filename="export.csv"' . "\n");
		header('Content-Type: text/plain' . "\n");
		header('Content-Length: ' . mb_strlen($output) . "\n");
		header('Content-transfert-encoding: binary' . "\n");
		print $output;
		exit;
	} else {
		$babBody->babecho(bab_printTemplate($temp, 'statconnections.html', 'detailconnectionslist'));
	}

}



?>