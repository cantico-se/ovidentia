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
include_once dirname(__FILE__).'/searchapi.php';
include_once dirname(__FILE__).'/calincl.php';

/**
 * Calendars search realm
 * @package	search
 */
class bab_SearchRealmCalendars extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'calendars';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Calendar');
	}

	public function getSortKey() {
		return '0080';
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return bab_siteMap::getUrlById('babUserCal');
	}

	/**
	 * Calendars are sorted by publication date
	 * @return	array
	 */
	public function getSortMethods() {

		return array(
			'date' => bab_translate('Events start date')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbtable' => bab_translate('Events')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))			->virtual(true),
			$this->createField('id'					, bab_translate('Event numeric identifier'))	->searchable(false)->setTableAlias('e'),
			$this->createField('title'				, bab_translate('Title'))->setTableAlias('e'),
			$this->createField('description'		, bab_translate('Description'))->setTableAlias('e'),
			$this->createField('description_format'	, bab_translate('Description format'))->setTableAlias('e')	->searchable(false),
			$this->createField('location'			, bab_translate('Location'))->setTableAlias('e'),
			$this->createField('start_date'			, bab_translate('Start date'))->setTableAlias('e'),
			$this->createField('end_date'			, bab_translate('End date'))->setTableAlias('e'),
			$this->createField('category'			, bab_translate('Category'))->setTableAlias('c')->setRealName('name'),
			$this->createField('id_cat'				, bab_translate('Category numeric identifier'))->setTableAlias('e') ->searchable(false),
			$this->createField('id_cal'				, bab_translate('Calendar numeric identifier'))->setTableAlias('o') ->searchable(false),
			$this->createField('owner'				, bab_translate('Owner numeric identifier'))->setTableAlias('o') 	->searchable(false),
			$this->createField('type'				, bab_translate('Owner type'))->setTableAlias('o') 					->searchable(false),
			$this->createField('date_modification'	, bab_translate('Last modification date'))->setTableAlias('e') 		->searchable(false),
			$this->createField('bprivate'			, bab_translate('Private event'))->setTableAlias('e') 				->searchable(false),
		);
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() {
		return 0 < count(bab_getAvailableCalendars());
	}


	/**
	 * Get default criteria for notes
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		
		return $this->id_cal->in(bab_getAvailableCalendars());
	}



	/**
	 * Search location "dbtable"
	 * @see bab_SearchRealmCalendars::getSearchLocations()
	 * @return ressource
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		global $babDB;

		$mysql = $this->getBackend('mysql');
		$query = '
		SELECT 
			e.id, 
			e.title, 
			e.description,
			e.location,
			e.start_date,
			e.end_date,
			e.bprivate,
			c.name category,
			e.id_cat,
			o.id_cal, 
			cal.owner,
			cal.type 
		FROM 
			'.BAB_CAL_EVENTS_OWNERS_TBL.' o,
			'.BAB_CALENDAR_TBL.' cal,
			'.BAB_CAL_EVENTS_TBL.' e LEFT JOIN '.BAB_CAL_CATEGORIES_TBL.' c ON c.id = e.id_cat  
		WHERE 
			e.id = o.id_event 
			AND cal.id = o.id_cal 
			';
			
		$where = $criteria->tostring($this->getBackend('mysql'));
		if (!empty($where)) {
			$query .= ' AND '.$where;
		}

	
		 
		$query .= ' ORDER BY start_date DESC, end_date  DESC';

		bab_debug($query, DBG_INFO, 'Search');

		return $babDB->db_query($query);
	}





	/**
	 * Search in notes from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {

		$result = new bab_SearchCalendarsResult;
		$result->setRealm($this);

		$locations = $this->getSearchLocations();

		// only one location possible in this search realm

		if (isset($locations['dbtable'])) {
			$ressource = $this->dbtable($criteria);
			$result->setRessource($ressource);
			return $result;
		}
		
		throw new Exception('No valid search location');
	}




	/**
	 * Get search form as HTML string
	 * @return string
	 */
	public function getSearchFormHtml() {

		$html = parent::getSearchFormHtml();

		$template = new bab_SearchRealmCalendar_SearchTemplate();
		$html .= bab_printTemplate($template, 'search.html', 'calendar_form');

		return $html;
	}







	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		// default search fields
		$criteria = bab_SearchDefaultForm::getCriteria($this);

		
		$h_calendar = (int) bab_rp('h_calendar');
		if ($h_calendar) {
			$criteria = $criteria->_AND_($this->id_cal->is($h_calendar));
		}

		include_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		if ($after = BAB_DateTime::fromUserInput(bab_rp('after'))) {
			$criteria = $criteria->_AND_($this->end_date->greaterThanOrEqual($after->getIsoDateTime()));
		}

		if ($before = BAB_DateTime::fromUserInput(bab_rp('before'))) {
			$before->add(1, BAB_DATETIME_DAY);
			$criteria = $criteria->_AND_($this->start_date->lessThan($before->getIsoDateTime()));
		}

		return $criteria;
	}

}






/**
 * Custom result object to add reference support to the record
 * @package search
 */
class bab_SearchCalendarsResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('calendars', 'event', $record->id);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setContent($record->description);
			$editor->setFormat($record->description_format);
			$record->description = $editor->getHtml();

		}

		return $record;
	}


	/**
	 * Get a view of search results as HTML string
	 * The items to display are extracted from the <code>bab_SearchResult</code> object,
	 * the display start at the iterator current position and stop after $count elements
	 *
	 * @param	int					$count		number of items to display
	 *
	 * @return string
	 */
	public function getHtml($count) {

		include_once dirname(__FILE__).'/dateTime.php';
		$return = '';

		while ($this->valid() && 0 < $count) {

			$count--;
			$record = $this->current();

			$title			= bab_translate('Private event');
			$description 	= '';
			$location		= '';
			$category		= '';
			$visibility		= 'N' === $record->bprivate;
			
			if ($visibility) {
				$title		= bab_toHtml($record->title);
				$description= bab_abbr(bab_SearchResult::unhtmlentities(strip_tags(bab_toHtml($record->description, BAB_HTML_REPLACE))), BAB_ABBR_FULL_WORDS, 500);
			}

			if ($visibility && !empty($record->location)) {
				$location	= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Location'), $record->location);
			}

			if (!empty($record->category)) {
				$category	= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Category'), $record->category);
			}

			$iarr 			= bab_getICalendars()->getCalendarInfo($record->id_cal);
			$calendar 		= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Calendar'), $iarr['name']);


			$start_date		= bab_mktime($record->start_date);
			$end_date		= bab_mktime($record->end_date);
			$duration		= ($end_date - $start_date) / 3600;
			$duration 		= bab_sprintf('<strong>%s :</strong> %s %s', bab_translate('Duration'), round($duration, 1), bab_translate('Hours'));

			$startdate		= bab_sprintf('<strong>%s :</strong> <abbr class="dtstart" title="%s">%s</abbr>', bab_translate('Start date'), $record->start_date, bab_LongDate(bab_mktime($record->start_date)));
			$enddate		= bab_sprintf('<strong>%s :</strong> <abbr class="dtend" title="%s">%s</abbr>', bab_translate('End date'), $record->end_date, bab_LongDate(bab_mktime($record->end_date)));

			$eventurl 		= bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$record->id."&idcal=".$record->id_cal);
			$calendarurl 	= bab_toHtml(bab_sprintf('?tg=calweek&date=%d,%d,%d', date('Y', $start_date), date('n', $start_date), date('j', $start_date)));


			$return .= bab_SearchResult::getRecordHtml(
								bab_sprintf('<a class="summary" href="%s" onclick="bab_popup(this.href);return false;">%s</a>', $eventurl, $title),
								bab_sprintf('
										<p> 
											%s<br />
											%s
										</p>
										<p class="description">%s</p>
										<p><a href="%s">%s</a> &nbsp;&nbsp; %s</p>
										<p><span class="bottom">%s &nbsp;&nbsp; %s &nbsp;&nbsp; %s</span></p>
									', 
									$startdate,
									$enddate,
									bab_toHtml($description),
									$calendarurl, bab_translate('Go to calendar'), $location,
									$calendar,
									$category,
									$duration
								),
								'vevent'
							);

			$this->next();
		}

		return $return;
	}


}










/**
 * @package search
 */
class bab_SearchRealmCalendar_SearchTemplate extends bab_SearchTemplate {

	private $rescal;


	public function __construct() {
		global $babDB;
		global $babBody;
		include_once $GLOBALS['babInstallPath']."utilit/calincl.php";

		$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'bab_dialog.js');

		$this->rescal = array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
		
		foreach ($this->rescal as $k => $arr)
			{
			$this->rescal[$arr['name']] = $arr;
			unset($this->rescal[$k]);
			}
		bab_sort::ksort($this->rescal);
		$this->rescal = array_values($this->rescal);


		$this->t_calendar = bab_translate('Calendar');
		$this->t_all 	= bab_translate('All');
		$this->t_after 	= bab_translate('After');
		$this->t_before = bab_translate('Before');

		$this->after 	= bab_rp('after');
		$this->before 	= bab_rp('before');
	}

	public function getnextcal() {

		if (list(, $arr) = each($this->rescal)) {
			$this->value = bab_toHtml($arr['idcal']);
			$this->option = bab_toHtml($arr['name']);
			$this->selected = $arr['idcal'] == bab_rp('h_calendar');
			return true;
		}

		return false;
	}
	
}



