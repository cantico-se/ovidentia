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

include_once $GLOBALS['babInstallPath'] . "utilit/dashboard.php";
include_once $GLOBALS['babInstallPath'] . "utilit/uiutil.php";


function createYearHeaders(&$headers, $startDate, $endDate)
{
	$startYear = date('Y', $startDate);

	$date = mktime(0, 0, 0, 1, 1, $startYear);
	while ($startYear <= $endDate) {
		$headers[] = array('type' => '',
						   'name' => $startYear);
		$startYear++;
		$date = mktime(0, 0, 0, 1, 1, $startYear);
	}
}

function createMonthHeaders(&$headers, $startDate, $endDate)
{
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);

	$date = mktime(0, 0, 0, $startMonth, 1, $startYear);
	while ($date <= $endDate) {
		$headers[] = array('type' => '',
						   'name' => bab_formatDate('%m %Y', $date));
		$startMonth++;
		$date = mktime(0, 0, 0, $startMonth, 1, $startYear);
	}
}

function createDayHeaders(&$headers, $startDate, $endDate)
{
	$startDay = date('d', $startDate);
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);

	$date = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	while ($date <= $endDate) {
		$dayOfWeek = (date('w', $date) + 6) % 7;
		$headers[] = array('type' => 'day' . $dayOfWeek,
						   'name' => bab_formatDate('%d %j', $date));
		$startDay++;
		$date = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	}
}

function createHeaders($startDate, $endDate)
{
	$headers = array();
	$headers['label'] = array('type' => 'label',
							  'name' => '');
	$nbDays = (int)round(($endDate - $startDate) / 86400.0);
	if ($nbDays <= 31)
		createDayHeaders($headers, $startDate, $endDate);
	elseif ($nbDays <= 365)
		createMonthHeaders($headers, $startDate, $endDate);
	else
		createYearHeaders($headers, $startDate, $endDate);
	$headers['total'] = array('type' => 'total',
					   'name' => bab_translate("Total"));
	return $headers;
}


function createEmptyYearRow(&$row, $startDate, $endDate)
{
	$startYear = date('Y', $startDate);
	$date = mktime(0, 0, 0, 1, 1, $startYear);
	while ($date <= $endDate) {
		$year = date('Y', $date);
		$row[$year] = 0;
		$startYear++;
		$date = mktime(0, 0, 0, 1, 1, $startYear);
	}
}


function createEmptyMonthRow(&$row, $startDate, $endDate)
{
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);
	$date = mktime(0, 0, 0, $startMonth, 1, $startYear);
	while ($date <= $endDate) {
		$month = date('Y-m', $date);
		$row[$month] = 0;
		$startMonth++;
		$date = mktime(0, 0, 0, $startMonth, 1, $startYear);
	}
}


function createEmptyDayRow(&$row, $startDate, $endDate)
{
	$startDay = date('d', $startDate);
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);
	$date = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	while ($date <= $endDate) {
		$month = date('Y-m-d', $date);
		$row[$month] = 0;
		$startDay++;		
		$date = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	}
}


function createEmptyRow($startDate, $endDate, $label)
{
	$row = array();
	$row['label'] = $label;
	$startDay = date('d', $startDate);
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);
	$nbDays = (int)round(($endDate - $startDate) / 86400.0);
	if ($nbDays <= 31)
		createEmptyDayRow($row, $startDate, $endDate);
	elseif ($nbDays <= 365)
		createEmptyMonthRow($row, $startDate, $endDate);
	else
		createEmptyYearRow($row, $startDate, $endDate);
	return $row;
}



function createArticleTopicsDashboard($start, $end)
{
	$dashboard = new bab_Dashboard(bab_translate("Articles Topics Top 20"));
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT tt.id AS id, SUM(sat.st_hits) AS hits, tt.category AS title';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
	if ($GLOBALS['babBody']->currentAdmGroup != 0) {
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tct ON tct.id=tt.id_cat';
	}
	$sql .= ' WHERE at.title IS NOT NULL';
	if ($GLOBALS['babBody']->currentAdmGroup != 0) {
		$sql .= ' AND  tct.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
	}
	if ($start || $end) {
		$sql .= ' AND ';
		$where = array();
		$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . '\'';
		$sql .= implode(' AND ', $where);
	}
	$sql .= ' GROUP BY tt.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT 20';

	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31)
		$sqlDateFormat = '%Y-%m-%d';
	elseif ($nbDays <= 365)
		$sqlDateFormat = '%Y-%m';
	else
		$sqlDateFormat = '%Y';
	
	$topics = $GLOBALS['babDB']->db_query($sql);
	while ($topic = $GLOBALS['babDB']->db_fetch_array($topics)) {
		$sql = 	'SELECT tt.id AS id, DATE_FORMAT(sat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(sat.st_hits) AS hits';
		$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
		$sql .= ' WHERE tt.id=\'' . $topic['id'] . '\'';
		if ($start || $end) {
			$sql .= ' AND ';
			$where = array();
			$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
			$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . '\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY stat_date';
		$sql .= ' ORDER BY stat_date ASC';
	
		$stats = $GLOBALS['babDB']->db_query($sql);
		$row = createEmptyRow($start, $end, $topic['title']);
		while ($stat = $GLOBALS['babDB']->db_fetch_array($stats)) {
			$row[$stat['stat_date']] = $stat['hits'];
		}
			
		$row['total'] = $topic['hits'];
		$dashboard->addRow($row);
	}	
	
	return $dashboard;
}


function createArticlesDashboard($start, $end)
{
	$dashboard = new bab_Dashboard(bab_translate("Articles Top 20"));
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 	'SELECT st_article_id AS id, SUM(st_hits) AS hits, title ';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL;
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' ON ' .BAB_ARTICLES_TBL . '.id=' . BAB_STATS_ARTICLES_TBL . '.st_article_id';
	if ($start || $end) {
		$sql .= ' WHERE ';
		$where = array();
		$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . '\'';
		$sql .= implode(' AND ', $where);
	}
	$sql .= ' GROUP BY id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT 20';

	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31)
		$sqlDateFormat = '%Y-%m-%d';
	elseif ($nbDays <= 365)
		$sqlDateFormat = '%Y-%m';
	else
		$sqlDateFormat = '%Y';
	
	$articles = $GLOBALS['babDB']->db_query($sql);
	while ($article = $GLOBALS['babDB']->db_fetch_array($articles)) {
		$sql = 	'SELECT st_article_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits FROM ' . BAB_STATS_ARTICLES_TBL;
		$sql .= ' WHERE st_article_id=\'' . $article['id'] . '\'';
		if ($start || $end) {
			$sql .= ' AND ';
			$where = array();
			$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
			$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . '\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY stat_date';
		$sql .= ' ORDER BY stat_date ASC';
	
		$stats = $GLOBALS['babDB']->db_query($sql);
		$row = createEmptyRow($start, $end, $article['title']);
		while ($stat = $GLOBALS['babDB']->db_fetch_array($stats)) {
			$row[$stat['stat_date']] = $stat['hits'];
		}
			
		$row['total'] = $article['hits'];
		$dashboard->addRow($row);
	}	
	
	return $dashboard;
}



function showDashboard()
{

	$dashboard = createArticlesDashboard(bab_mktime('2005-01-01'), bab_mktime('2005-01-31'));
	$GLOBALS['babBodyPopup']->babecho($dashboard->printScriptAndCss());
	$GLOBALS['babBodyPopup']->babecho($dashboard->printTemplate());

	$dashboard = createArticleTopicsDashboard(bab_mktime('2005-01-01'), bab_mktime('2005-01-31'));
	$GLOBALS['babBodyPopup']->babecho($dashboard->printTemplate());
}

?>