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


// Article categories
//-------------------
function &getArticleCategoriesDashboardRow($category, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT category.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' category ON category.id=topic.id_cat';
	$sql .= '  WHERE category.id=\'' . $category['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $category['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createArticleCategoriesDashboard($start, $end)
{
	global $babBody, $babDB;
	$admGroup = bab_getCurrentAdmGroup();
	$title = sprintf(bab_translate("Article Categories Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title, 'categories');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT category.id AS id, SUM(stat.st_hits) AS hits, category.title AS title';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' category ON category.id=topic.id_cat';
	$sql .= ' WHERE article.title IS NOT NULL';
	$admGroup && $sql .= ' AND  category.id_dgowner=\'' . $admGroup . '\'';
	$start    && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end      && $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY category.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$categories = $babDB->db_query($sql);
	while ($category = $babDB->db_fetch_array($categories)) {
		$dashboard->addRow(getArticleCategoriesDashboardRow($category, $start, $end, $sqlDateFormat));	
	}	
	return $dashboard;
}

function &getArticleCategoriesByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT category.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS category ON category.id=topic.id_cat';
	$sql .= ' WHERE category.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createArticleCategoriesByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Article categories"));
	$dashboard = new bab_DashboardElement($title, 'categories');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getArticleCategoriesByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getArticleCategoriesByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Article topics
//---------------
function &getArticleTopicsDashboardRow($topic, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT topic.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$sql .= '  WHERE article.title IS NOT NULL AND topic.id=\'' . $topic['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $topic['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createArticleTopicsDashboard($start, $end)
{
	global $babBody, $babDB;
	$admGroup = bab_getCurrentAdmGroup();
	$title = sprintf(bab_translate("Article Topics Top %d"), BAB_DASHBOARD_NB_ITEMS);	
	$dashboard = new bab_DashboardElement($title, 'topics');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT topic.id AS id, SUM(stat.st_hits) AS hits, topic.category AS title';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$admGroup && $sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' category ON category.id=topic.id_cat';
	$sql .= ' WHERE article.title IS NOT NULL';
	$admGroup && $sql .= ' AND  category.id_dgowner=\'' . $admGroup . '\'';
	$start    && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end      && $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY topic.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$topics = $babDB->db_query($sql);
	while ($topic = $babDB->db_fetch_array($topics)) {
		$dashboard->addRow(getArticleTopicsDashboardRow($topic, $start, $end, $sqlDateFormat));	
	}
	return $dashboard;
}

function &getArticleTopicsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT topic.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS category ON category.id=topic.id_cat';
	$sql .= ' WHERE category.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createArticleTopicsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Article topics"));
	$dashboard = new bab_DashboardElement($title, 'topics');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getArticleTopicsByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getArticleTopicsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Articles
//---------
function &getArticlesDashboardRow($article, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT article.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= '  WHERE article.id=\'' . $article['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $article['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createArticlesDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Articles Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title, 'articles');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 	'SELECT at.id AS id, SUM(sat.st_hits) AS hits ';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS sat LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS at ON at.id=sat.st_article_id';
	if (bab_getCurrentAdmGroup()) {
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS tt ON tt.id=at.id_topic';
		$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS tct ON tct.id=tt.id_cat';
	}
	$where = array();
	$where[] = 'at.title IS NOT NULL';
	bab_getCurrentAdmGroup() && $where[] = 'tct.id_dgowner=\'' . bab_getCurrentAdmGroup() . '\'';
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

function &getArticlesByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT article.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_ARTICLES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_ARTICLES_TBL . ' AS article ON stat.st_article_id=article.id';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
	$sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS category ON category.id=topic.id_cat';
	$sql .= ' WHERE category.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createArticlesByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Articles"));
	$dashboard = new bab_DashboardElement($title, 'articles');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getArticlesByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getArticlesByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Faqs
//-----
function &getFaqsDashboardRow($faq, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_faq_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FAQS_TBL;
	$sql .= ' WHERE st_faq_id=\'' . $faq['id'] . '\'';
	$start && $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $faq['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;		
}

function &getFaqsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT faq.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FAQS_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FAQCAT_TBL . ' AS faq ON stat.st_faq_id=faq.id';
	$sql .= ' WHERE faq.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createFaqsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Faqs"));
	$dashboard = new bab_DashboardElement($title, 'faqs');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getFaqsByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getFaqsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Faq questions
//--------------
function &getFaqQuestionsDashboardRow($question, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_faqqr_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FAQQRS_TBL;
	$sql .= ' WHERE st_faqqr_id=\'' . $question['id'] . '\'';
	$start && $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $question['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function &getFaqQuestionsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT question.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FAQQRS_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FAQQR_TBL . ' AS question ON stat.st_faqqr_id=question.id';
	$sql .= ' LEFT JOIN ' . BAB_FAQCAT_TBL . ' AS faq ON question.idcat=faq.id';
	$sql .= ' WHERE faq.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createFaqQuestionsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Faq questions"));
	$dashboard = new bab_DashboardElement($title, 'questions');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getFaqQuestionsByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getFaqQuestionsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}



// Forums
//-------
function &getForumsDashboardRow($forum, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_forum_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FORUMS_TBL;
	$sql .= ' WHERE st_forum_id=\'' . $forum['id'] . '\'';
	$start && $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&	  $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $forum['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;		
}

function &getForumsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT forum.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FORUMS_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FORUMS_TBL . ' AS forum ON stat.st_forum_id=forum.id';
	$sql .= ' WHERE forum.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createForumsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Forums"));
	$dashboard = new bab_DashboardElement($title, 'forums');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getForumsByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getForumsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Posts
//------
function &getPostsDashboardRow($post, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_post_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_POSTS_TBL;
	$sql .= ' WHERE st_post_id=\'' . $post['id'] . '\'';
	$start && $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&	  $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $post['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function &getPostsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT post.id AS id, DATE_FORMAT(stat.st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_POSTS_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_POSTS_TBL . ' AS post ON stat.st_post_id=post.id';
	$sql .= ' LEFT JOIN ' . BAB_THREADS_TBL . ' AS thread ON post.id_thread=thread.id';
	$sql .= ' LEFT JOIN ' . BAB_FORUMS_TBL . ' AS forum ON thread.forum=forum.id';
	$sql .= ' WHERE forum.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&   $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createPostsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Posts"));
	$dashboard = new bab_DashboardElement($title, 'posts');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getPostsByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getPostsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Collective directories
//------------------------
function &getFoldersDashboardRow($folder, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_folder_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FMFOLDERS_TBL;
	$sql .= ' WHERE st_folder_id=\'' . $folder['id'] . '\'';
	$start && $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&	  $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $folder['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;		
}
	
function createFoldersDashboard($start, $end)
{
	global $babBody, $babDB;
	$admGroup = bab_getCurrentAdmGroup();
	$title = sprintf(bab_translate("Collective Directories Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title, 'folders');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT folder.id AS id, folder.folder AS title, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM  ' . BAB_STATS_FMFOLDERS_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' folder ON stat.st_folder_id=folder.id';
	$sql .= ' WHERE folder.folder IS NOT NULL';
	$admGroup && $sql .= ' AND folder.id_dgowner=\'' . $admGroup . '\'';
	$start &&	 $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&		 $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY folder.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$folders = $babDB->db_query($sql);
	while ($folder = $babDB->db_fetch_array($folders)) {
		$dashboard->addRow(getFoldersDashboardRow($folder, $start, $end, $sqlDateFormat));
	}
	return $dashboard;
}

function &getFoldersByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_folder_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FMFOLDERS_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' folder ON folder.id=stat.st_folder_id';
	$sql .= ' WHERE folder.id_dgowner=\'' . $delegation['id'] . '\'';
	$start && $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&	  $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;		
}
	
function createFoldersByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Collective directories"));
	$dashboard = new bab_DashboardElement($title, 'folders');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getFoldersByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getFoldersByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}



// File downloads
//---------------
function &getFileDownloadsDashboardRow($file, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_fmfile_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FMFILES_TBL;
	$sql .= ' WHERE st_fmfile_id=\'' . $file['id'] . '\'';
	$start &&	$sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&		$sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $file['title'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createFileDownloadsDashboard($start, $end)
{
	global $babBody, $babDB;
	$admGroup = bab_getCurrentAdmGroup();
	$title = sprintf(bab_translate("File Downloads Top %d"), BAB_DASHBOARD_NB_ITEMS);
	$dashboard = new bab_DashboardElement($title, 'files');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT file.id AS id, file.name AS title, folder.folder AS folder, SUM(stat.st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FMFILES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FILES_TBL . ' AS file ON stat.st_fmfile_id=file.id';
	$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' AS folder ON folder.id=file.id_owner';
	$sql .= ' WHERE file.bgroup=\'Y\'';
	$admGroup && $sql .= ' AND folder.id_dgowner=\'' . $admGroup . '\'';
	$start &&	 $sql .= ' AND stat.st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end &&		 $sql .= ' AND stat.st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY file.id';
	$sql .= ' ORDER BY hits DESC';
	$sql .= ' LIMIT ' . BAB_DASHBOARD_NB_ITEMS;

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$files = $babDB->db_query($sql);
	while ($file = $babDB->db_fetch_array($files)) {
		$dashboard->addRow(getFileDownloadsDashboardRow($file, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}

function &getFileDownloadsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat)
{
	global $babDB;
	$sql = 	'SELECT st_fmfile_id AS id, DATE_FORMAT(st_date,\'' . $sqlDateFormat . '\') AS stat_date, SUM(st_hits) AS hits';
	$sql .= ' FROM ' . BAB_STATS_FMFILES_TBL . ' AS stat';
	$sql .= ' LEFT JOIN ' . BAB_FILES_TBL . ' AS file ON stat.st_fmfile_id=file.id';
	$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' AS folder ON folder.id=file.id_owner';
	$sql .= ' WHERE folder.id_dgowner=\'' . $delegation['id'] . '\'';
	$start	&& $sql .= ' AND st_date >= \'' . date('Y-m-d', $start) . '\'';
	$end	&& $sql .= ' AND st_date <= \'' . date('Y-m-d', $end) . ' 23:59:59\'';
	$sql .= ' GROUP BY stat_date';
	$sql .= ' ORDER BY stat_date ASC';

	$stats = $babDB->db_query($sql);
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
	}
	$row = array();
	$row['label'] = $delegation['name'];
	$row += fillRow($r, $start, $end);
	$row['total'] = $total;
	return $row;
}

function createFileDownloadsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("File downloads"));
	$dashboard = new bab_DashboardElement($title, 'files');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sql = 'SELECT id, name';
	$sql .= ' FROM ' . BAB_DG_GROUPS_TBL;
	
	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$dashboard->addRow(getFileDownloadsByDelegationDashboardRow(array('id' => 0, 'name' => 'Site'), $start, $end, $sqlDateFormat));
	$delegations = $babDB->db_query($sql);
	while ($delegation = $babDB->db_fetch_array($delegations)) {
		$dashboard->addRow(getFileDownloadsByDelegationDashboardRow($delegation, $start, $end, $sqlDateFormat));
	}	
	return $dashboard;
}


// Functions
//----------
function createFunctionsDashboard($start, $end)
{
	global $babDB;
	$dashboard = new bab_DashboardElement(bab_translate("Activity by Functions"), 'functions');
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
	$dashboard = new bab_DashboardElement(bab_translate("Global Site Activity"), 'activity');
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
	$r = array();
	for ($total = 0; $stat = $babDB->db_fetch_array($stats); $total += $stat['hits']) {
		$r[$stat['stat_date']] = $stat['hits'];
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


// Created objects
//----------------
function createCreatedObjectsByDelegationDashboard($start, $end)
{
	global $babBody, $babDB;
	$title = sprintf(bab_translate("Created objects"));
	$dashboard = new bab_DashboardElement($title, 'activity');
	$dashboard->setColumnHeaders(array(array('type' => '', 'name' => ''),
									   array('type' => '', 'name' => bab_translate("Groups")),
									   array('type' => '', 'name' => bab_translate("Sections")),
									   array('type' => '', 'name' => bab_translate("Topics categories")),
									   array('type' => '', 'name' => bab_translate("Faqs")),
									   array('type' => '', 'name' => bab_translate("Forums")),
									   array('type' => '', 'name' => bab_translate("Directories")),
									   array('type' => '', 'name' => bab_translate("Folders")),
									   array('type' => '', 'name' => bab_translate("Charts"))));

	$sql = 'SELECT dg.*, g.lf, g.lr';
	$sql .= ' FROM ' . BAB_GROUPS_TBL . ' AS g, ' . BAB_DG_GROUPS_TBL . ' AS dg';
	$sql .= ' WHERE g.id=dg.id_group';
	$sql .= ' ORDER BY dg.name';

	$res = $babDB->db_query($sql);
	while ($arr = $babDB->db_fetch_array($res)) {
		$row = array('delegation' => $arr['name']);
		list($row['groups']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(id) FROM '.BAB_GROUPS_TBL.' WHERE nb_set>=0 AND lf>\''.$arr['lf'].'\' AND lr<\''.$arr['lr'].'\''));
		list($row['sections']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(id) FROM '.BAB_SECTIONS_TBL." WHERE id_dgowner = '".$arr['id']."'"));
		list($row['topcats']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(id) FROM '.BAB_TOPICS_CATEGORIES_TBL." where id_dgowner = '".$arr['id']."'"));
		list($row['faqs']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(id) FROM '.BAB_FAQCAT_TBL." where id_dgowner = '".$arr['id']."'"));
		list($row['forums']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(id) FROM '.BAB_FORUMS_TBL." where id_dgowner = '".$arr['id']."'"));
		list($row['directories']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(id) FROM '.BAB_DB_DIRECTORIES_TBL." where id_dgowner = '".$arr['id']."'"));
		list($row['folders']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(*) FROM '.BAB_FM_FOLDERS_TBL." where id_dgowner = '".$arr['id']."'"));
		list($row['orgcharts']) = $babDB->db_fetch_row($babDB->db_query('SELECT COUNT(*) FROM '.BAB_ORG_CHARTS_TBL." where id_dgowner = '".$arr['id']."'"));
		$dashboard->addRow($row);
	}
	return $dashboard;
}


define('BAB_STAT_BCT_TOPIC',		1);
define('BAB_STAT_BCT_ARTICLE',		2);
define('BAB_STAT_BCT_FOLDER',		3);
define('BAB_STAT_BCT_FILE',			4);
define('BAB_STAT_BCT_FORUM',		5);
define('BAB_STAT_BCT_POST',			6);
define('BAB_STAT_BCT_FAQ',			7);
define('BAB_STAT_BCT_QUESTION',		8);

function createBasketDashboard($basketId, $start, $end)
{
	global $babDB, $babBody;
	$admGroup = bab_getCurrentAdmGroup();

	$sql = 	'SELECT * FROM ' . BAB_STATS_BASKETS_TBL . ' WHERE id=' . $basketId;
	$baskets = $babDB->db_query($sql);
	$basket = $babDB->db_fetch_array($baskets);
	
	$dashboard = new bab_DashboardElement(bab_translate("Statistics basket: ") . $basket['basket_name'], 'basket');
	$dashboard->setColumnHeaders(createHeaders($start, $end));

	$sqlDateFormat = getSqlDateFormat($start, $end);
	
	$sql = 	'SELECT * FROM ' . BAB_STATS_BASKET_CONTENT_TBL . ' WHERE basket_id=' . $basketId;
	$basketContents = $babDB->db_query($sql);
	while ($basketContent = $babDB->db_fetch_array($basketContents)) {
		switch ($basketContent['bc_type']) {

			case BAB_STAT_BCT_TOPIC:	// Article topics
				$sql = 'SELECT topic.id AS id, topic.category AS title';
				$sql .= ' FROM ' . BAB_ARTICLES_TBL . ' AS article';
				$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
				$admGroup && $sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' category ON category.id=topic.id_cat';
				$sql .= ' WHERE article.title IS NOT NULL AND topic.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND  category.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY topic.id';
				$topics = $babDB->db_query($sql);
				$topic = $babDB->db_fetch_array($topics);
				$dashboard->addRow(getArticleTopicsDashboardRow($topic, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_ARTICLE:	// Articles
				$sql = 'SELECT article.id AS id, article.title AS title';
				$sql .= ' FROM ' . BAB_ARTICLES_TBL . ' AS article';
				$sql .= ' LEFT JOIN ' . BAB_TOPICS_TBL . ' AS topic ON topic.id=article.id_topic';
				$admGroup && $sql .= ' LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' category ON category.id=topic.id_cat';
				$sql .= ' WHERE article.title IS NOT NULL AND article.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND  category.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY article.id';
				$articles = $babDB->db_query($sql);
				$article = $babDB->db_fetch_array($articles);
				$dashboard->addRow(getArticlesDashboardRow($article, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_FOLDER:	// Collective folders
				$sql = 'SELECT folder.id AS id, folder.folder AS title';
				$sql .= ' FROM ' . BAB_FM_FOLDERS_TBL . ' AS folder';
				$sql .= ' WHERE folder.folder IS NOT NULL AND folder.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND folder.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY folder.id';
				$folders = $babDB->db_query($sql);
				$folder = $babDB->db_fetch_array($folders);
				$dashboard->addRow(getFoldersDashboardRow($folder, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_FILE:		// Files
				$sql = 'SELECT file.id AS id, file.name AS title';
				$sql .= ' FROM ' . BAB_FILES_TBL . ' file';
				$sql .= ' LEFT JOIN ' . BAB_FM_FOLDERS_TBL . ' folder ON folder.id=file.id_owner';
				$sql .= ' WHERE file.bgroup=\'Y\' AND file.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND folder.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY file.id';
				$files = $babDB->db_query($sql);
				$file = $babDB->db_fetch_array($files);
				$dashboard->addRow(getFileDownloadsDashboardRow($file, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_FORUM:	// Forums
				$sql = 'SELECT forum.id AS id, forum.name AS title';
				$sql .= ' FROM ' . BAB_FORUMS_TBL . ' AS forum';
				$sql .= ' WHERE forum.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND forum.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY forum.id';
				$forums = $babDB->db_query($sql);
				$forum = $babDB->db_fetch_array($forums);
				$dashboard->addRow(getForumsDashboardRow($forum, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_POST:		// Forum posts
				$sql = 'SELECT post.id AS id, post.subject AS title';
				$sql .= ' FROM ' . BAB_POSTS_TBL . ' AS post';
				$sql .= ' LEFT JOIN ' . BAB_THREADS_TBL . ' AS thread ON thread.id=post.id_thread';
				$sql .= ' LEFT JOIN ' . BAB_FORUMS_TBL . ' AS forum ON forum.id=thread.forum';
				$sql .= ' WHERE post.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND forum.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY post.id';
				$posts = $babDB->db_query($sql);
				$post = $babDB->db_fetch_array($posts);
				$dashboard->addRow(getPostsDashboardRow($post, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_FAQ:		// Faqs
				$sql = 'SELECT faq.id AS id, faq.category AS title';
				$sql .= ' FROM ' . BAB_FAQCAT_TBL . ' AS faq';
				$sql .= ' WHERE faq.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND faq.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY faq.id';
				$faqs = $babDB->db_query($sql);
				$faq = $babDB->db_fetch_array($faqs);
				$dashboard->addRow(getFaqsDashboardRow($faq, $start, $end, $sqlDateFormat));
				break;

			case BAB_STAT_BCT_QUESTION:	// Faq questions
				$sql = 'SELECT question.id AS id, question.question AS title';
				$sql .= ' FROM ' . BAB_FAQQR_TBL . ' AS question';
				$sql .= ' LEFT JOIN ' . BAB_FAQCAT_TBL . ' AS faq ON faq.id=question.idcat';
				$sql .= ' WHERE question.id=' . $basketContent['bc_id'];
				$admGroup && $sql .= ' AND faq.id_dgowner=\'' . $admGroup . '\'';
				$sql .= ' GROUP BY question.id';
				$questions = $babDB->db_query($sql);
				$question = $babDB->db_fetch_array($questions);
				$dashboard->addRow(getFaqQuestionsDashboardRow($question, $start, $end, $sqlDateFormat));
				break;
		}
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
	$dashboard->setExportUrl($GLOBALS['babUrlScript'] . '?tg=stat&idx=dashboardexport');

	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31) {
		$dashboard->addFilter('D', 'day6');
		$dashboard->addFilter('L', 'day0');
		$dashboard->addFilter('M', 'day1');
		$dashboard->addFilter('M', 'day2');
		$dashboard->addFilter('J', 'day3');
		$dashboard->addFilter('V', 'day4');
		$dashboard->addFilter('S', 'day5');
	}

	$dashboard->addElement(createGlobalActivityDashboard($start, $end)); // 0.07
	$dashboard->addElement(createFunctionsDashboard($start, $end)); // 0.8
	$dashboard->addElement(createArticlesDashboard($start, $end)); // 2.2
	$dashboard->addElement(createArticleTopicsDashboard($start, $end)); // 3.6
	$dashboard->addElement(createArticleCategoriesDashboard($start, $end)); // 4.16
	$dashboard->addElement(createFoldersDashboard($start, $end)); // 0.03
	$dashboard->addElement(createFileDownloadsDashboard($start, $end)); // 0.16

	$GLOBALS['babBodyPopup']->babecho($dashboard->printTemplate());
}


function exportDashboard($startDate, $endDate)
{
	$start = ($startDate ? bab_mktime($startDate) : bab_mktime('2000-01-01'));
	$end = ($endDate ? bab_mktime($endDate) : time());

	$hiddenContainers =& $_REQUEST['container_hide'];
	
	$dashboard = new bab_Dashboard();
	$dashboard->setTitle(sprintf(bab_translate("Dashboard from %s to %s"), bab_shortDate($start, false), bab_shortDate($end, false)));
	if (!isset($hiddenContainers['activity']))
		$dashboard->addElement(createGlobalActivityDashboard($start, $end));
	if (!isset($hiddenContainers['functions']))
		$dashboard->addElement(createFunctionsDashboard($start, $end));
	if (!isset($hiddenContainers['articles']))
		$dashboard->addElement(createArticlesDashboard($start, $end));
	if (!isset($hiddenContainers['topics']))
		$dashboard->addElement(createArticleTopicsDashboard($start, $end));
	if (!isset($hiddenContainers['categories']))
		$dashboard->addElement(createArticleCategoriesDashboard($start, $end));
	if (!isset($hiddenContainers['folders']))
		$dashboard->addElement(createFoldersDashboard($start, $end));
	if (!isset($hiddenContainers['files']))
		$dashboard->addElement(createFileDownloadsDashboard($start, $end));

	header('Cache-Control: public');
	header('Content-type: application/vnd.ms-excel');
	header('Content-Disposition: attachement; filename="export.xls"');

	print $dashboard->printTemplateCsv();
	die();	
}


function showDelegationDashboard($startDate, $endDate)
{
	$start = ($startDate ? bab_mktime($startDate) : bab_mktime('2000-01-01'));
	$end = ($endDate ? bab_mktime($endDate) : time());

	$dashboard = new bab_Dashboard();

	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31) {
		$dashboard->addFilter('D', 'day6');
		$dashboard->addFilter('L', 'day0');
		$dashboard->addFilter('M', 'day1');
		$dashboard->addFilter('M', 'day2');
		$dashboard->addFilter('J', 'day3');
		$dashboard->addFilter('V', 'day4');
		$dashboard->addFilter('S', 'day5');
	}

	$dashboard->addElement(createFileDownloadsByDelegationDashboard($start, $end));
	$dashboard->addElement(createFoldersByDelegationDashboard($start, $end));
	$dashboard->addElement(createArticlesByDelegationDashboard($start, $end));
	$dashboard->addElement(createArticleTopicsByDelegationDashboard($start, $end));
	$dashboard->addElement(createArticleCategoriesByDelegationDashboard($start, $end));
	$dashboard->addElement(createForumsByDelegationDashboard($start, $end));
	$dashboard->addElement(createPostsByDelegationDashboard($start, $end));
	$dashboard->addElement(createFaqsByDelegationDashboard($start, $end));
	$dashboard->addElement(createFaqQuestionsByDelegationDashboard($start, $end));
	$dashboard->addElement(createCreatedObjectsByDelegationDashboard($start, $end));

	$GLOBALS['babBodyPopup']->babecho($dashboard->printTemplate());
}



function showBasket($basketId, $startDate, $endDate)
{
	$start = ($startDate ? bab_mktime($startDate) : bab_mktime('2000-01-01'));
	$end = ($endDate ? bab_mktime($endDate) : time());

	$dashboard = new bab_Dashboard();
	$dashboard->setExportUrl($GLOBALS['babUrlScript'] . '?tg=stat&idx=basketexport&idbasket=' . $basketId);
	
	$nbDays = (int)round(($end - $start) / 86400.0);
	if ($nbDays <= 31) {
		$dashboard->addFilter('D', 'day6');
		$dashboard->addFilter('L', 'day0');
		$dashboard->addFilter('M', 'day1');
		$dashboard->addFilter('M', 'day2');
		$dashboard->addFilter('J', 'day3');
		$dashboard->addFilter('V', 'day4');
		$dashboard->addFilter('S', 'day5');
	}

	$dashboard->addElement(createBasketDashboard($basketId, $start, $end));

	$GLOBALS['babBodyPopup']->babecho($dashboard->printTemplate());
}


function exportBasket($basketId, $startDate, $endDate)
{
	$start = ($startDate ? bab_mktime($startDate) : bab_mktime('2000-01-01'));
	$end = ($endDate ? bab_mktime($endDate) : time());

	$dashboard = new bab_Dashboard();
	$dashboard->setTitle(sprintf(bab_translate("From %s to %s"), bab_shortDate($start, false), bab_shortDate($end, false)));
	$dashboard->addElement(createBasketDashboard($basketId, $start, $end));

	header('Cache-Control: public');
	header('Content-type: application/vnd.ms-excel');
	header('Content-Disposition: attachement; filename="export.xls"');

	print $dashboard->printTemplateCsv();
	die();	
}

?>