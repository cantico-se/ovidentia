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



define('BAB_DASHBOARD_NB_ITEMS', 20);


function createYearHeaders(&$headers, $startDate, $endDate)
{
	$year = date('Y', $startDate);
	$date = mktime(0, 0, 0, 1, 1, $year);
	while ($date <= $endDate) {
		$headers[] = array('type' => '',
						   'name' => $year);
		$year++;
		$date = mktime(0, 0, 0, 1, 1, $year);
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
	$newRow = array();
	$startYear = date('Y', $startDate);
	$date = mktime(0, 0, 0, 1, 1, $startYear);
	while ($date <= $endDate) {
		$year = date('Y', $date);
		$newRow[$year] = isset($row[$year]) ? $row[$year] : 0;
		$startYear++;
		$date = mktime(0, 0, 0, 1, 1, $startYear);
	}
	return $newRow;
}


function createEmptyMonthRow(&$row, $startDate, $endDate)
{
	$newRow = array();
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);
	$date = mktime(0, 0, 0, $startMonth, 1, $startYear);
	while ($date <= $endDate) {
		$month = date('Y-m', $date);
		$newRow[$month] = isset($row[$month]) ? $row[$month] : 0;
		$startMonth++;
		$date = mktime(0, 0, 0, $startMonth, 1, $startYear);
	}
	return $newRow;
}


function createEmptyDayRow(&$row, $startDate, $endDate)
{
	$newRow = array();
	$startDay = date('d', $startDate);
	$startMonth = date('m', $startDate);
	$startYear = date('Y', $startDate);
	$date = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	while ($date <= $endDate) {
		$day = date('Y-m-d', $date);
		$newRow[$day] = isset($row[$day]) ? $row[$day] : 0;
		$startDay++;		
		$date = mktime(0, 0, 0, $startMonth, $startDay, $startYear);
	}
	return $newRow;
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

function fillRow($row, $startDate, $endDate)
{
	$nbDays = (int)round(($endDate - $startDate) / 86400.0);
	if ($nbDays <= 31)
		$newRow = createEmptyDayRow($row, $startDate, $endDate);
	elseif ($nbDays <= 365)
		$newRow = createEmptyMonthRow($row, $startDate, $endDate);
	else
		$newRow = createEmptyYearRow($row, $startDate, $endDate);
	return $newRow;
}

function getSqlDateFormat($start, $end)
{
	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31)
		return '%Y-%m-%d';
	if ($nbDays <= 365)
		return '%Y-%m';
	return '%Y';
}



function createArticleCategoriesDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Article Categories Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title);
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT tct.id AS id, SUM(sat.st_hits) AS hits, tct.title AS title';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tct ON tct.id=tt.id_cat';
	$sql .= ' WHERE at.title IS NOT NULL';
	if ($babBody->currentAdmGroup != 0) {
		$sql .= ' AND  tct.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
	}
	if ($start || $end) {
		$sql .= ' AND ';
		$where = array();
		$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		$sql .= implode(' AND ', $where);
	}
	$sql .= ' GROUP BY tct.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$categories = $babDB->db_query($sql);
	while ($category = $babDB->db_fetch_array($categories)) {
		$sql = 	'SELECT tct.id AS id, DATE_FORMAT(sat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(sat.st_hits) AS hits';
		$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' sat';
		$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id';
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' tt ON tt.id=at.id_topic';
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tct ON tct.id=tt.id_cat';
		$sql .= ' WHERE tct.id=\'' . $category['id'] . '\'';
		if ($start || $end) {
			$sql .= ' AND ';
			$where = array();
			$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
			$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY stat_date';
		$sql .= ' ORDER BY stat_date ASC';
	
		$stats = $babDB->db_query($sql);
		$r = array();
		while ($stat = $babDB->db_fetch_array($stats)) {
			$r[$stat['stat_date']] = $stat['hits'];
		}
		$row = array();
		$row['label'] = $category['title'];
		$row += fillRow($r, $start, $end);
		$row['total'] = $category['hits'];
			
		$dashboard->addRow($row);
	}	

	return $dashboard;
}


function addArticleTopicsDashboardRow(&$dashboard, $topic, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT tt.id AS id, DATE_FORMAT(sat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(sat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
	$sql .= '  WHERE at.title IS NOT NULL AND tt.id=\'' . $topic['id'] . '\'';
	if ($start || $end) {
		$sql .= ' AND ';
		$where = array();
		$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		$sql .= implode(' AND ', $where);
	}
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	while ($stat = $babDB->db_fetch_array($stats)) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $topic['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $topic['hits'];
	
	$dashboard->addRow($row);	
}

function createArticleTopicsDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Article Topics Top %d"), BAB_DASHBOARD_NB_ITEMS);	
	$dashboard = new bab_DashboardElement($title);
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT tt.id AS id, SUM(sat.st_hits) AS hits, tt.category AS title';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
	if ($babBody->currentAdmGroup != 0) {
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tct ON tct.id=tt.id_cat';
	}
	$sql .= ' WHERE at.title IS NOT NULL';
	if ($babBody->currentAdmGroup != 0) {
		$sql .= ' AND  tct.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
	}
	if ($start || $end) {
		$sql .= ' AND ';
		$where = array();
		$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		$sql .= implode(' AND ', $where);
	}
	$sql .= ' GROUP BY tt.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$topics = $babDB->db_query($sql);
	while ($topic = $babDB->db_fetch_array($topics)) {
		addArticleTopicsDashboardRow($dashboard, $topic, $start, $end, $sqlDateFormat);
	}	
	
	return $dashboard;
}


function createArticlesDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Articles Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title);
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 	'SELECT at.id AS id, SUM(sat.st_hits) AS hits ';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON at.id=sat.st_article_id';
	if ($babBody->currentAdmGroup) {
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS tct ON tct.id=tt.id_cat';
	}
	$where = array();
	$where[] = 'at.title IS NOT NULL';
	$babBody->currentAdmGroup && $where[] = 'tct.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
	$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);

	$articles = array();
	$rsArticles = $babDB->db_query($sql);
	while ($article = $babDB->db_fetch_array($rsArticles)) {
		$articles[$article['id']] = $article;
	}
	
	if (!empty($articles))
	{
		$sql = 'SELECT id, title ';
		$sql .= ' FROM ' . BAB_ARTICLES_TBL;
		$sql .= ' WHERE id IN(' . implode(',', array_keys($articles)) . ')';
		$rsArticles = $babDB->db_query($sql);
		while ($article = $babDB->db_fetch_array($rsArticles)) {
			$articles[$article['id']]['title'] = $article['title'];
		}
	
		$sql = 	'SELECT st_article_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
		$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL;
		$where = array();
		$where[] = 'st_article_id IN(' . implode(',', array_keys($articles)) . ')';
		$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY st_article_id, stat_date';
		$sql .= ' ORDER BY st_article_id, stat_date ASC';
	
		$stats = array();
		$rsStats = $babDB->db_query($sql);
		while ($stat = $babDB->db_fetch_array($rsStats)) {
			if (!isset($stats[$stat['id']]))
				$stats[$stat['id']] = array();
			$stats[$stat['id']][$stat['stat_date']] = $stat['hits'];
		}
	
		reset($articles);
		while (list($articleId, $article) = each($articles)) {
			$row = array();
			$row['label'] = $article['title'];
			$row += fillRow($stats[$articleId], $start, $end);
			$row['total'] = $article['hits'];
			$dashboard->addRow($row);
		}
	}
	return $dashboard;
}


function createDirectoriesDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Collective Directories Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title);
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT fft.id AS id, fft.folder AS title, SUM(sft.st_hits) AS hits';
	$sql .= ' FROM  ' . BAB_STATS_FMFOLDERS_TBL . ' sft';
	$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' fft ON sft.st_folder_id=fft.id';
	$where = array();
	$where[] = 'fft.folder IS NOT NULL';
	$babBody->currentAdmGroup && $where[] = 'fft.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
	$start && $where[] = 'sft.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'sft.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY fft.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$directories = $babDB->db_query($sql);
	while ($directory = $babDB->db_fetch_array($directories)) {
		$sql = 	'SELECT st_folder_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
		$sql .= ' FROM ' . BAB_STATS_FMFOLDERS_TBL;
		$where = array();
		$where[] = 'st_folder_id=\'' . $directory['id'] . '\'';
		$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY stat_date';
		$sql .= ' ORDER BY stat_date ASC';
	
		$stats = $babDB->db_query($sql);
		$r = array();
		while ($stat = $babDB->db_fetch_array($stats)) {
			$r[$stat['stat_date']] = $stat['hits'];
		}
		$row = array();
		$row['label'] = $directory['title'];
		$row += fillRow($r, $start, $end);
		$row['total'] = $directory['hits'];
			
		$dashboard->addRow($row);
	}	

	return $dashboard;
}


function createFileDownloadsDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("File Downloads Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title);
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT ft.id AS id, ft.name AS title, fft.folder AS folder, SUM(sff.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FMFILES_TBL . ' sff';
	$sql .= ' LEFT JOIN ' . BAB_FILES_TBL . ' ft ON sff.st_fmfile_id=ft.id';
	$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' fft ON fft.id=ft.id_owner';
	$where = array();
	$where[] = 'ft.bgroup=\'Y\'';
	$babBody->currentAdmGroup && $where[] = 'fft.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
	$start && $where[] = 'sff.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'sff.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY ft.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$files = $babDB->db_query($sql);
	while ($file = $babDB->db_fetch_array($files)) {
		$sql = 	'SELECT st_fmfile_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
		$sql .= ' FROM ' . BAB_STATS_FMFILES_TBL;
		$where = array();
		$where[] = 'st_fmfile_id=\'' . $file['id'] . '\'';
		$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY stat_date';
		$sql .= ' ORDER BY stat_date ASC';
	
		$stats = $babDB->db_query($sql);
		$r = array();
		while ($stat = $babDB->db_fetch_array($stats)) {
			$r[$stat['stat_date']] = $stat['hits'];
		}
		$row = array();
		$row['label'] = $file['title'];
		$row += fillRow($r, $start, $end);
		$row['total'] = $file['hits'];
			
		$dashboard->addRow($row);
	}	

	return $dashboard;
}

function createFunctionsDashboard($start, $end)
{
	global $babDB;
	$dashboard = new bab_DashboardElement(bab_translate("Activity by Functions"));
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT stip.id AS id, stip.module_name AS title, SUM(stp.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_MODULES_TBL . ' AS stp';
	$sql .= ' LEFT JOIN ' . BAB_STATS_IMODULES_TBL . ' AS stip ON stp.st_module_id=stip.id';
	$where = array();
	$start && $where[] = 'stp.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'stp.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY stip.id';
	$sql .= ' ORDER BY hits DESC';

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$modules = $babDB->db_query($sql);
	while ($module = $babDB->db_fetch_array($modules)) {
		$sql = 	'SELECT st_module_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
		$sql .= ' FROM ' .BAB_STATS_MODULES_TBL;
		$where = array();
		$where[] = 'st_module_id=\'' . $module['id'] . '\'';
		$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
		$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
		if (!empty($where)) {
			$sql .= ' WHERE ' . implode(' AND ', $where);
		}
		$sql .= ' GROUP BY stat_date';
		$sql .= ' ORDER BY stat_date ASC';

		$stats = $babDB->db_query($sql);
		$r = array();
		while ($stat = $babDB->db_fetch_array($stats)) {
			$r[$stat['stat_date']] = $stat['hits'];
		}
		$row = array();
		$row['label'] = bab_translate($module['title']);
		$row += fillRow($r, $start, $end);
		$row['total'] = $module['hits'];

		$dashboard->addRow($row);
	}	

	return $dashboard;
}


function createGlobalActivityDashboard($start, $end)
{
	global $babDB;
	$dashboard = new bab_DashboardElement(bab_translate("Global Site Activity"));
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$sql = 	'SELECT DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_MODULES_TBL;
	$where = array();
	$start && $where[] = 'st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$total = 0;
	$r = array();
	while ($stat = $babDB->db_fetch_array($stats)) {
		$r[$stat['stat_date']] = $stat['hits'];
		$total += $stat['hits'];
	}
	$row['label'] = bab_translate("Global hits");
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	$dashboard->addRow($row);

	$sql = 	'SELECT DATE_FORMAT(date_publication,\'' . $sqlDateFormat . '\') AS stat_date, COUNT(*) AS hits';
	$sql .= ' FROM ' . BAB_ARTICLES_TBL;
	$where = array();
	$start && $where[] = 'date_publication >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'date_publication <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$total = 0;
	$r = array();
	while ($stat = $babDB->db_fetch_array($stats)) {
		$r[$stat['stat_date']] = $stat['hits'];
		$total += $stat['hits'];
	}
	$row = array();
	$row['label'] = bab_translate("Published articles");
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	$dashboard->addRow($row);

	$sql = 	'SELECT DATE_FORMAT(created,\'' . $sqlDateFormat . '\') AS stat_date, COUNT(*) AS hits';
	$sql .= ' FROM ' . BAB_FILES_TBL;
	$where = array();
	$start && $where[] = 'created >= \'' . date('Y-m-d', $start) . '\'';
	$end && $where[] = 'created <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	if (!empty($where)) {
		$sql .= ' WHERE ' . implode(' AND ', $where);
	}
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$total = 0;
	$r = array();
	while ($stat = $babDB->db_fetch_array($stats)) {
		$r[$stat['stat_date']] = $stat['hits'];
		$total += $stat['hits'];
	}
	$row = array();
	$row['label'] = bab_translate("Downloaded files");
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	$dashboard->addRow($row);

	return $dashboard;
}


function createBasketDashboard($basketId, $start, $end)
{
	global $babDB;

	$sql = 	'SELECT * FROM ' . BAB_STATS_BASKETS_TBL . ' WHERE id=' . $basketId;
	$baskets = $babDB->db_query($sql);
	$basket = $babDB->db_fetch_array($baskets);
	
	$dashboard = new bab_DashboardElement(bab_translate("Statistics basket: ") . $basket['basket_name']);
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 	'SELECT * FROM ' . BAB_STATS_BASKET_CONTENT_TBL . ' WHERE basket_id=' . $basketId;
	$basketContents = $babDB->db_query($sql);
	while ($basketContent = $babDB->db_fetch_array($basketContents)) {
		// Article topics
		$sql = 'SELECT tt.id AS id, SUM(sat.st_hits) AS hits, tt.category AS title';
		$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat';
		$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON sat.st_article_id=at.id';
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tct ON tct.id=tt.id_cat';
		}
		$sql .= ' WHERE at.title IS NOT NULL AND tt.id=' . $basketContent['bc_id'];
		if ($babBody->currentAdmGroup != 0) {
			$sql .= ' AND  tct.id_dgowner=\'' . $babBody->currentAdmGroup . '\'';
		}
		if ($start || $end) {
			$sql .= ' AND ';
			$where = array();
			$start && $where[] = 'sat.st_date >= \'' . date('Y-m-d', $start) . '\'';
			$end && $where[] = 'sat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
			$sql .= implode(' AND ', $where);
		}
		$sql .= ' GROUP BY tt.id';
		$sql .= ' ORDER BY hits DESC';
		$sql .= ' LIMIT 1';
	
		$sqlDateFormat = getSqlDateFormat($start, $end);
	
		$topics = $babDB->db_query($sql);
		$topic = $babDB->db_fetch_array($topics);
		addArticleTopicsDashboardRow(&$dashboard, $topic, $start, $end, $sqlDateFormat);
	}
	
	return $dashboard;
}

function microtime_float()
{
   list($usec, $sec) = explode(' ', microtime());
   return ((float)$usec + (float)$sec);
}


function showDashboard($startDate, $endDate)
{
	$start = ($startDate ? bab_mktime($startDate) : bab_mktime('2000-01-01'));
	$end = ($endDate ? bab_mktime($endDate) : time());

	$dashboard = new bab_Dashboard();

	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31) {
		$dashboard->addFilter('L', 'day0');
		$dashboard->addFilter('M', 'day1');
		$dashboard->addFilter('M', 'day2');
		$dashboard->addFilter('J', 'day3');
		$dashboard->addFilter('V', 'day4');
		$dashboard->addFilter('S', 'day5');
		$dashboard->addFilter('D', 'day6');
	}

	////
//	$s = microtime_float();
	////
	$dashboard->addElement(createGlobalActivityDashboard($start, $end)); // 0.07
	$dashboard->addElement(createFunctionsDashboard($start, $end)); // 0.8
	$dashboard->addElement(createArticlesDashboard($start, $end)); // 2.2
	$dashboard->addElement(createArticleTopicsDashboard($start, $end)); // 3.6
	$dashboard->addElement(createArticleCategoriesDashboard($start, $end)); // 4.16
	$dashboard->addElement(createDirectoriesDashboard($start, $end)); // 0.03
	$dashboard->addElement(createFileDownloadsDashboard($start, $end)); // 0.16
	$dashboard->addElement(createBasketDashboard(1, $start, $end)); // 0.16
	////
//	$e = microtime_float(); echo '<!-- ';print_r('addElement : ' . ($e - $s));echo " -->\n";
	////

	////
//	$s = microtime_float();
	////
	$GLOBALS['babBodyPopup']->babecho($dashboard->printTemplate());
//	print $dashboard->printTemplateCsv();
//	die();
	////
//	$e = microtime_float();	echo '<!-- ';print_r('babEcho : ' . ($e - $s));echo " -->\n";
	////
}

?>