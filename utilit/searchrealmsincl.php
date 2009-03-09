<?php
/* **********************************************************************
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
require_once dirname(__FILE__).'/eventincl.php';







/**
 * search realm : a category of item to search, 
 * extend this object to create a new search realm
 * On each search realms you can get available fields, available sorting methods, available search locations
 *
 * @package search
 */
abstract class bab_SearchRealm extends bab_SearchTestable {





	/**
	 * Store an array with <code>bab_SearchField</code> as objects
	 * @var array
	 */
	private $fields = null;


	
	/**
	 * Store the search locations
	 * if the value is NULL, all available search location will be used
	 * or the value can be an array with the locations as keys, values of the array will not be used
	 * @see bab_SearchRealm::getSearchLocations()
	 *
	 * @var	null | array
	 */
	protected $search_locations = null;


	/**
	 * Store the sort method
	 * if the value is NULL, the first available sort method will be used
	 * or the value is a mixed dependant of searchrealm class
	 *
	 * @var null | mixed
	 */
	protected $sort_method = null;




	/**
	 * Display the title of the realm is case of a string representation of the object
	 * @return string
	 */
	final public function __tostring() {
		return $this->getDescription();
	}


	/**
	 * Get Url to functionality on the portal
	 * The return value may be null
	 * @return	string | null
	 */
	public function getLink() {
		return null;
	}

	/**
	 * Get the list of available sorting methods for this realm
	 * this method return an array where keys are constants from the <code>bab_SearchRealm</code> object 
	 * and the values are internationalized descriptions of the sorting methods
	 *
	 *
	 * @return 	array
	 */
	abstract public function getSortMethods();


	/**
	 * Set sort method
	 * @param	mixed		$method		one sort method from the list provided by the getSortMethods() method on the same object
	 * @param	bool		
	 * @return 	bab_SearchCriteria
	 */
	public function setSortMethod($method) {

		$sort_methods = $this->getSortMethods();

		if (!isset($sort_methods[$method])) {
			throw new Exception('This sort method is not in the list of valid sort methods : '.$method);
		}

		$this->sort_method = $method;
		return $this;
	}




	/**
	 * Get the list of all available searching locations
	 * This method return an array where keys are mixed (each realm can have his specific location identifiers) 
	 * and the values are internationalized descriptions of the searching locations
	 *
	 * @see bab_SearchCriteria
	 * 
	 * @return	array
	 */
	abstract public function getAllSearchLocations();


	/**
	 * Get the list of user selected searching locations
	 * This method return an array where keys are mixed (each realm can have his specific location identifiers) 
	 * and the values are internationalized descriptions of the searching locations
	 *
	 * @see bab_SearchCriteria
	 * 
	 * @return	array
	 */
	public function getSearchLocations() {
		if (null !== $this->search_locations) {
			return $this->search_locations;
		}

		return $this->getAllSearchLocations();
	}
	
	
	/**
	 * Set search location
	 * @param	string	$location		a string provided by the <code>getSearchLocations</code> method
	 * @return	bab_SearchCriteria
	 */
	public function addSearchLocation($location) {

		$all = $this->getAllSearchLocations();

		if (!isset($all[$location])) {
			throw new Exception('This search location is not allowed : '.$location);
		}
		
		if (null === $this->search_locations) {
			$this->search_locations = array();
		}

		$this->search_locations[$location] = $all[$location];
		return $this;
	}


	/**
	 * Get a list of available fields
	 * 
	 * @return	array	each values will be a <code>bab_SearchField</code> object
	 */
	abstract public function getFields();



	/**
	 * Test if the current user have access to this search realm.
	 * If this method return true, a search with criteria as given by the method <code>getDefaultCriteria</code> must return 
	 * all items accessibles by the current user.
	 *
	 * @return	bool
	 */
	abstract public function isAccessValid();


	/**
	 * Get default criteria, may contain access rights verification as criteria for search request
	 * This default criteria is valid when the search realm is accessible
	 * @see bab_SearchRealm::isAccessValid()
	 *
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		include_once dirname(__FILE__).'/searchcriterion.php';
		return new bab_SearchInvariant;
	}

	
	/**
	 * Search in realm with criteria
	 * This method will use the <code>getSearchLocations</code> method to get the search locations
	 * and the <code>sort_method</code> property to sort results
	 * 
	 * @param	bab_SearchCriteria		$criteria
	 *
	 * @return 	bab_SearchResult | bab_SearchResultCollection	(iterator)
	 */
	abstract public function search(bab_SearchCriteria $criteria);



	/**
	 * Create a field for the realm
	 *
	 * @param	string		$name			field name
	 * @param	string		$description	internationalized description
	 * @return	bab_SearchField
	 */
	public function createField($name, $description) {

		
		$field = new bab_SearchField;

		$field->setRealm($this);
		$field->setName($name);
		$field->setDescription($description);

		return $field;
	}


	/**
	 * Magic method to get a field
	 * @param 	string 		$sFieldName 	The name of the field to return
	 * @return	bab_SearchField
	 */
	public function __get($sFieldName) {
		if (null === $this->fields) {

			foreach($this->getFields() as $field) {
				$this->fields[$field->getName()] = $field;
			}
		}

		if (!isset($this->fields[$sFieldName])) {
			throw new Exception('The field '.$sFieldName.' does not exists');
		}

		return $this->fields[$sFieldName];
	}


	/**
	 * Magic method to test a field
	 * @param 	string 		$sFieldName 	The name of the field to return
	 * @return	bool
	 */
	public function __isset($sFieldName) {
		if (null === $this->fields) {

			foreach($this->getFields() as $field) {
				$this->fields[$field->getName()] = $field;
			}
		}

		return isset($this->fields[$sFieldName]);
	}


	/**
	 * Get search backend object (query builder)
	 * @param	string		$backendName
	 * @return	bab_SearchBackEnd
	 */
	public function getBackend($backendName) {

		switch($backendName) {
			case 'mysql':
				include_once dirname(__FILE__).'/searchbackend.mysql.php';
				$obj = new bab_SearchMySqlBackEnd;
				global $babDB;
				$obj->setDataBaseAdapter($babDB);
				return $obj;
				break;


			case 'swish':
				include_once dirname(__FILE__).'/searchbackend.swish.php';
				$obj = new bab_SearchSwishBackEnd;
				break;
		}
	}


	/**	
	 * Set a criteria without fields criterions used for swish-e
	 * @param	bab_SearchCriteria	$criteria
	 * @return bab_SearchRealm
	 */
	public function setFieldLessCriteria(bab_SearchCriteria $criteria) {
		return $this;
	}



	/**
	 * Get search form as HTML string
	 * @return string
	 */
	public function getSearchFormHtml() {
		return bab_SearchDefaultForm::getHTML();
	}

	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		return bab_SearchDefaultForm::getCriteria($this);
	}

	/**
	 * get a criteria without field criterions from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormFieldLessCriteria() {
		return bab_SearchDefaultForm::getFieldLessCriteria($this);
	}

	
	


	


	

	/**
	 * The Ovidentia search engine will display a list of places to search, 
	 * the search realm will not be displayed if this method return false
	 * @return boolean
	 */
	public function displayInSearchEngine() {
		return true;
	}

}







class bab_SearchTemplate {

	public $altbg = true;


	/**
	 * test iterrator validity and return true only on current page
	 * @return boolean
	 */
	protected function slicePage(bab_SearchResult $results, $navitem, $navpos, $limit) {

		if (0 === $results->key() && 0 !== $navpos) {
			$results->seek($navpos);
		}

		if (($navpos + $limit) < $results->key()) {
			return false;
		}

		return $results->valid();
	}


	
	public static function getIcon($filepath) {

		$icon = '';

		if ($thumbnail = @bab_functionality::get('Thumbnailer')) {
			if (method_exists($thumbnail, 'getIcon')) {
				$src = $thumbnail->getIcon($filepath, 100, 100);
				$type = (false !== $src->getThumbnail()) ? 'thumbnail' : 'icon';
				$icon = bab_sprintf('<img src="%s" alt="" class="%s" />', $src, $type);
			}
		}
		
		return $icon;
	}
}











/**
 * Event used to collect all available search realms
 * @package events
 */
class bab_eventSearchRealms extends bab_event {

	/**
	 * Store the requested realms, if the value is null, this mean all realms are nedded
	 * In this variable, realms class names are stored as keys but the <code>setRequestedRealms</code> method 
	 * is expecting an array with class names as values.
	 *
	 * @see bab_eventSearchRealms::setRequestedRealms()
	 * @var NULL | array
	 */
	private $requestedRealms	= null;

	/**
	 * Collected realms
	 * @see bab_eventSearchRealms::getRealms()
	 * @var array
	 */
	private $realms 			= array();


	/**
	 * set one or more realm name to collect with the event
	 *
	 *
	 * @param	mixed	$realmnames		the parameter can be of type :
	 *									<ul>
	 *										<li>null 	: all available realms will be collected</li>
	 *										<li>string 	: if a realm with the string exists as class name, it will be collected</li>
	 *										<li>array 	: the values of the array must be class names of realms objects, only matching realms will be collected</li>
	 *									<ul>
	 *
	 * @return	bab_eventSearchRealms
	 */
	public function setRequestedRealms($realmnames = null) {
		if (is_null($realmnames)) {
			$this->requestedRealms = null;
			return $this;
		}

		if (is_string($realmnames)) {
			$this->requestedRealms = array($realmnames => 1);
			return $this;
		}

		if (is_array($realmnames)) {
			$this->requestedRealms = array_flip($realmnames);
			return $this;
		}

		throw new Exception('Invalid parameter type : $realmnames');
	}

	/**
	 * Test if a realm has been requested by the event
	 * @param	string	$realmname	class name of a realm
	 * @return 	bool
	 */
	public function isRequested($realmname) {

		if (null === $this->requestedRealms) {
			return true;
		}

		return isset($this->requestedRealms[$realmname]);
	}



	/**
	 * Add a realm to search possibilities
	 * @param	bab_SearchRealm	$realm
	 * @return	bab_eventSearchRealms
	 */
	public function addRealm(bab_SearchRealm $realm) {
		
		$this->realms[$realm->getName()] = $realm;

		return $this;
	}


	/**
	 * get array of collected realms
	 * @see 	bab_Search::getRealms()
	 * @return	array
	 */
	public function getRealms() {
		
		return $this->realms;
	}
}






/**
 * function registered on search realms event
 * Add search realms from core
 * @see bab_eventSearchRealms
 * @package search
 *
 * @param	bab_eventSearchRealms	$event
 *
 */
function bab_onSearchRealms(bab_eventSearchRealms $event) {
	
	if ($event->isRequested('bab_SearchRealmNotes')) {
		require_once dirname(__FILE__).'/searchrealm.notes.php';
		$event->addRealm(new bab_SearchRealmNotes);
	}

	if ($event->isRequested('bab_SearchRealmDirectories')) {
		require_once dirname(__FILE__).'/searchrealm.directories.php';
		$event->addRealm(new bab_SearchRealmDirectories);
	}

	if ($event->isRequested('bab_SearchRealmForums')) {
		require_once dirname(__FILE__).'/searchrealm.forums.php';
		$event->addRealm(new bab_SearchRealmForums);
	}

	if ($event->isRequested('bab_SearchRealmForumPosts')) {
		require_once dirname(__FILE__).'/searchrealm.forumposts.php';
		$event->addRealm(new bab_SearchRealmForumPosts);
	}

	if ($event->isRequested('bab_SearchRealmForumFiles')) {
		require_once dirname(__FILE__).'/searchrealm.forumfiles.php';
		$event->addRealm(new bab_SearchRealmForumFiles);
	}

	if ($event->isRequested('bab_SearchRealmFiles')) {
		require_once dirname(__FILE__).'/searchrealm.files.php';
		$event->addRealm(new bab_SearchRealmFiles);
	}

	if ($event->isRequested('bab_SearchRealmPublication')) {
		require_once dirname(__FILE__).'/searchrealm.publication.php';
		$event->addRealm(new bab_SearchRealmPublication);
	}

	if ($event->isRequested('bab_SearchRealmArticles')) {
		require_once dirname(__FILE__).'/searchrealm.articles.php';
		$event->addRealm(new bab_SearchRealmArticles);
	}

	if ($event->isRequested('bab_SearchRealmArticlesFiles')) {
		require_once dirname(__FILE__).'/searchrealm.articlesfiles.php';
		$event->addRealm(new bab_SearchRealmArticlesFiles);
	}

	if ($event->isRequested('bab_SearchRealmArticlesComments')) {
		require_once dirname(__FILE__).'/searchrealm.articlescomments.php';
		$event->addRealm(new bab_SearchRealmArticlesComments);
	}

	if ($event->isRequested('bab_SearchRealmFaqs')) {
		require_once dirname(__FILE__).'/searchrealm.faqs.php';
		$event->addRealm(new bab_SearchRealmFaqs);
	}

	if ($event->isRequested('bab_SearchRealmContacts')) {
		require_once dirname(__FILE__).'/searchrealm.contacts.php';
		$event->addRealm(new bab_SearchRealmContacts);
	}

	if ($event->isRequested('bab_SearchRealmCalendars')) {
		require_once dirname(__FILE__).'/searchrealm.calendars.php';
		$event->addRealm(new bab_SearchRealmCalendars);
	}

	if ($event->isRequested('bab_SearchRealmTags')) {
		require_once dirname(__FILE__).'/searchrealm.tags.php';
		$event->addRealm(new bab_SearchRealmtags);
	}

	// addons compatibility layer with old API
	require_once dirname(__FILE__).'/searchaddonincl.php';

	$addons = bab_getInstance('bab_addonsSearch');
	// $addons->setSearchParam($this->primary_search, $this->secondary_search, $option, $limit);
	foreach($addons->titleAddons as $id_addon => $title) {
		if ($event->isRequested('bab_SearchRealmAddon'.$id_addon)) {
			$realm = $addons->createRealm($id_addon);
			if (false !== $realm) {
				$event->addRealm($realm);
			}
		}
	}
}