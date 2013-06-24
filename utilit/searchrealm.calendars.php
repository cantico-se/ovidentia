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
			'dtstart' => bab_translate('Events start date')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'api' => bab_translate('Events')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))				->virtual(true),
			$this->createField('uid'				, bab_translate('Event numeric identifier'))		->searchable(false),
			$this->createField('summary'			, bab_translate('Title')),
			$this->createField('description'		, bab_translate('Description')),
			$this->createField('location'			, bab_translate('Location')),
			$this->createField('dtstart'			, bab_translate('Start date')),	// iCal
			$this->createField('dtend'				, bab_translate('End date')),	// iCal
			$this->createField('start_date'			, bab_translate('Start date')),
			$this->createField('end_date'			, bab_translate('End date')),
			$this->createField('categories'			, bab_translate('Category')),
			$this->createField('calendar'			, bab_translate('Calendar url identifier')) 		->searchable(false),
			$this->createField('collection'			, bab_translate('Collection')) 						->searchable(false),
			$this->createField('class'				, bab_translate('Class property')) 					->searchable(false),
			$this->createField('color'				, bab_translate('Color')) 							->searchable(false),
			$this->createField('id_dgowner'			, bab_translate('Delegation numeric identifier')) 	->searchable(false),
		);
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() {
		return bab_getICalendars()->calendarAccess();
	}


	/**
	 * Get default criteria for calendars
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		$calendars = bab_getICalendars()->getCalendars();
		$calendars = array_keys($calendars);
		
		return $this->calendar->in($calendars)->_AND_($this->collection->is('bab_CalendarEventCollection'));
	}



	

	/**
	 * Query the calendar api from the search api
	 * @param bab_SearchCriteria $criteria
	 * @return unknown_type
	 */
	private function api(bab_SearchCriteria $criteria)
	{
		$searchbackend = $this->getBackend('calendar');
		return $searchbackend->getEventsIterator($criteria); 
		
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

		if (isset($locations['api'])) {
			$iterator = $this->api($criteria);
			$result->setIterator($iterator);
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

		
		$h_calendar = bab_rp('h_calendar');
		if ($h_calendar) {
			$criteria = $criteria->_AND_($this->calendar->is($h_calendar));
		}

		include_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		
		$after = BAB_DateTime::fromUserInput(bab_rp('after'));
		
		if (!$after) {
			$after = BAB_DateTime::now();
			$after->less(1, BAB_DATETIME_DAY);
		}
		
		$criteria = $criteria->_AND_($this->end_date->greaterThanOrEqual($after->getIsoDateTime()));

		if ($before = BAB_DateTime::fromUserInput(bab_rp('before'))) {
			$before->add(1, BAB_DATETIME_DAY);
			
		} else {
			$before = BAB_DateTime::now();
			$before->add(1, BAB_DATETIME_DAY);
		}
		
		$criteria = $criteria->_AND_($this->start_date->lessThanOrEqual($before->getIsoDateTime()));

		return $criteria;
	}
	
	
	
	/**
	 * Display a select for delegation
	 */
	public function selectableDelegation() {
		return true;
	}

}






/**
 * Custom result object to add reference support to the record
 * @package search
 */
class bab_SearchCalendarsResult extends bab_SearchResult {

	/**
	 * 
	 * @var bab_UserPeriods
	 */
	private $periods;
	
	public function setIterator(bab_UserPeriods $periods) {
		$this->periods = $periods;
	}
	
	
	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {

		$calendarPeriod = $this->periods->current();
		/*@var $calendarPeriod bab_CalendarPeriod */
		
		$record = new bab_SearchRecord();
		$record->setRealm($this->getRealm());

		$record->ov_reference = bab_buildReference('calendars', 'event', $calendarPeriod->getProperty('UID'));
		$record->uid = $calendarPeriod->getProperty('UID');
		$record->summary = $calendarPeriod->getProperty('SUMMARY');
		$record->location = $calendarPeriod->getProperty('LOCATION');
		$record->dtstart = $calendarPeriod->getProperty('DTSTART');
		$record->dtend = $calendarPeriod->getProperty('DTEND');
		$record->start_date = date('Y-m-d H:i:s', $calendarPeriod->ts_begin);
		$record->end_date = date('Y-m-d H:i:s', $calendarPeriod->ts_end);
		$record->categories = $calendarPeriod->getProperty('CATEGORIES');
		$record->description = $calendarPeriod->getProperty('DESCRIPTION');
		$record->class = $calendarPeriod->getProperty('CLASS');
		$record->color = $calendarPeriod->getProperty('X-CTO-COLOR');
		
		$collection = $calendarPeriod->getCollection();
		
		$record->collection = $collection;
		
		$calendar = $collection->getCalendar();
		
		if ($calendar) {
			$record->calendar = $calendar->getUrlIdentifier();
		}
		
		
		$data = $calendarPeriod->getData();
		// display html from WYSIWYG if any :
		if (isset($data['description']) && isset($data['description_format']) && 'html' === $data['description_format'])
		{
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_calendar_event');
			$editor->setContent($data['description']);
			$editor->setFormat($data['description_format']);
			
			$record->description = $editor->getHtml();
		}
		

	

		return $record;
	}
	
	
	
	
	
	
	/**
	 * @return string	
	 */
	public function key() {
		return $this->periods->key();
	}

	public function next() {
		$this->periods->next();
	}

	public function rewind() {
		$this->periods->rewind();
	}

	public function count() {
		return $this->periods->count();
	}

	public function seek($index) {
		return $this->periods->seek($index);
	}

	/**
	 * @return boolean
	 */
	public function valid() {
		return $this->periods->valid();
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
			$visibility		= empty($record->class) || 'PUBLIC' === $record->class;
			
			if ($visibility) {
				$title		= bab_toHtml($record->summary);
				$description= bab_abbr(bab_unhtmlentities(strip_tags(bab_toHtml($record->description, BAB_HTML_REPLACE))), BAB_ABBR_FULL_WORDS, 500);
			}

			if ($visibility && !empty($record->location)) {
				$location	= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Location'), $record->location);
			}

			if (!empty($record->category)) {
				$category	= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Category'), $record->categories);
			}

			$obj 			= bab_getICalendars()->getEventCalendar($record->calendar);
			if ($obj)
			{
				$calendar 	= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Calendar'), $obj->getName());
			} else {
				$calendar = '';
				bab_debug('Calendar not found : '.$record->calendar);
			}


			$start_date		= bab_mktime($record->start_date);
			$end_date		= bab_mktime($record->end_date);
			$duration		= ($end_date - $start_date) / 3600;
			$duration 		= bab_sprintf('<strong>%s :</strong> %s %s', bab_translate('Duration'), round($duration, 1), bab_translate('Hours'));

			$startdate		= bab_sprintf('<strong>%s :</strong> <abbr class="dtstart" title="%s">%s</abbr>', bab_translate('Start date'), $record->start_date, bab_LongDate(bab_mktime($record->start_date)));
			$enddate		= bab_sprintf('<strong>%s :</strong> <abbr class="dtend" title="%s">%s</abbr>', bab_translate('End date'), $record->end_date, bab_LongDate(bab_mktime($record->end_date)));

			$eventurl 		= bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$record->uid."&idcal=".$record->calendar);
			
			$date = bab_sprintf('%d,%d,%d', date('Y', $start_date), date('n', $start_date), date('j', $start_date));
			
			$calendarurl 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=calweek&date=$date&calid=".$record->calendar);


			$return .= bab_SearchResult::getRecordHtml(
								bab_sprintf('<a class="summary" href="%s" onclick="bab_popup(this.href);return false;">%s</a>', $eventurl, $title),
								bab_sprintf('
										<p> 
											%s<br />
											%s
										</p>
										<p class="description">%s</p>
										<p><a href="%s">%s</a> &nbsp;&nbsp; %s</p>
										<p><span class="bottom"><span style="background-color:#%s"> &nbsp; &nbsp; </span>&nbsp;&nbsp; %s &nbsp;&nbsp; %s &nbsp;&nbsp; %s</span></p>
									', 
									$startdate,
									$enddate,
									bab_toHtml($description),
									$calendarurl, bab_translate('Go to calendar'), $location,
									$record->color,
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
		include_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		
		$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'bab_dialog.js');
		
		
		$delegation = bab_rp('delegation', null);
		$id_dgowner = null;
		if (null !== $delegation && 'DGAll' !== $delegation)
		{
			include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
			$arr = bab_getUserVisiblesDelegations();
			if (isset($arr[$delegation]))
			{
				$id_dgowner = $arr[$delegation]['id'];
			}
			$id_dgowner = str_replace('DG', '', $delegation);
		}

		$this->rescal = bab_getICalendars()->getCalendars($id_dgowner);
		bab_sort::sortObjects($this->rescal, 'getName');
		
		$this->t_calendar = bab_translate('Calendar');
		$this->t_all 	= bab_translate('All');
		$this->t_after 	= bab_translate('After');
		$this->t_before = bab_translate('Before');
		$this->t_calendar_boundaries_mandatory = bab_translate('The dates search boundaries are mandatory');
		
		
		
		$after = BAB_DateTime::fromUserInput(bab_rp('after'));
		
		if (!$after) {
			$after = BAB_DateTime::now();
			$after->less(1, BAB_DATETIME_DAY);
		}
		
		if ($before = BAB_DateTime::fromUserInput(bab_rp('before'))) {
			$before->add(1, BAB_DATETIME_DAY);
			
		} else {
			$before = BAB_DateTime::now();
			$before->add(1, BAB_DATETIME_DAY);
		}

		
		

		$this->after 	= date('d-m-Y', $after->getTimeStamp());
		$this->before 	= date('d-m-Y', $before->getTimeStamp());
	}

	public function getnextcal() {

		if (list(, $calendar) = each($this->rescal)) {
			$this->value = bab_toHtml($calendar->getUrlIdentifier());
			$this->option = bab_toHtml($calendar->getName());
			$this->selected = $calendar->getUrlIdentifier() == bab_rp('h_calendar');
			return true;
		}

		return false;
	}
	
}



