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

include_once dirname(__FILE__).'/searchcriterion.php';
include_once dirname(__FILE__).'/searchfield.php';
require_once dirname(__FILE__).'/searchrealmsincl.php';

/**
 * Search API, main object
 * @package search
 */
class bab_Search {

	/**
	 * Get the sorted list of search realms 
	 *
	 * @see bab_eventSearchRealms
	 * @see bab_SearchRealm
	 *
	 * @return	array	: list of realm objects
	 * 
	 */
	public static function getRealms() {

		$event = new bab_eventSearchRealms;
		bab_fireEvent($event);
		$realms = $event->getRealms();

		

		return $realms;
	}


	/**
	 * @return	bab_SearchRealm | null
	 */
	public static function getRealm($realmname) {

		$event = new bab_eventSearchRealms;
		$event->setRequestedRealms($realmname);
		bab_fireEvent($event);
		$realms = $event->getRealms();

		if (empty($realms)) {
			return null;
		}

		return reset($realms);
	}

}










/**
 * Search query criteria
 * @package search
 */
class bab_SearchCriteria {


	public function _OR_(bab_SearchCriteria $oCriteria)
	{
		return new bab_SearchOr($this, $oCriteria);
	}

	public function _AND_(bab_SearchCriteria $oCriteria)
	{
		return new bab_SearchAnd($this, $oCriteria);
	}

	public function _NOT_()
	{
		return new bab_SearchNot($this);
	}
	
	private function createCriteria($sClassName, $oRightCriteria)
	{
		return new $sClassName($this, $oRightCriteria);
	}

	public function toString(bab_SearchBackEnd $oBackEnd)
	{
		return '';
	}

}




/**
 * Collection of Search results object
 * @see bab_SearchRealm::search()
 * @see bab_SearchResult
 * @package search
 */
class bab_SearchResultCollection extends ArrayIterator {

	private $title;

	/**
	 * Set a title to describe the stored search results
	 * @param	string	$title
	 * @return	bab_SearchResultCollection
	 */
	public function setTitle($title) {
		$this->title = $title;
		return $this;
	}

	/**
	 * Get the title for all results
	 * @return string
	 */
	public function getTitle() {
		return $this->title;
	}

}







/**
 * Search results
 * @see bab_SearchRealm::search()
 *
 * seekableIterator must implement :
 *
 *	current()
 *	key ()
 *	next ()
 *	rewind ()
 *	seek ($index)
 *	valid ()
 *
 * countable must implement :
 *
 * count()
 *
 * @package search
 */
abstract class bab_SearchResult implements Countable, seekableIterator {

	/**
	 * @var bab_SearchRealm
	 */
	private $realm = null;

	/**
	 * Set search realm associated to this result set
	 * @return bab_searchResult
	 */
	final public function setRealm($realm) {
		$this->realm = $realm;
	}

	/**
	 * Get search realm associated to this result set
	 * @return bab_SearchRealm | null
	 */
	final public function getRealm() {
		return $this->realm;
	}



	/**
	 * A default display for search result
	 * @param	bab_SearchRealm						$realm
	 * @param	bab_SearchResult | ArrayIterator	$res		display results from this iterator
	 * @param	int									$count		number of items to display
	 */
	public static function getHtmlDefault($realm, $res, $count) {
		$elements = 0;
		$return = '';

		foreach($res as $record) {
			
			if ($elements > $count) {
				return $return;
			}

			$return .= '<div class="bab_SearchRecord">';
			foreach($realm->getFields() as $field) {

				$fieldname = $field->getName();
				$return .= bab_toHtml($field->getDescription().' : '.$record->$fieldname)."<br />\n";
			}
			
			$return .= '</div>';	
			$elements++;
		}

		return $return;
	}


	/**
	 * Helper method to display a record in search results
	 * @param	string	$title		HTML
	 * @param	string	$content	HTML
	 * @param	string	$classname
	 * @return string
	 */
	public static function getRecordHtml($title, $content, $classname = null) {

		if (null !== $classname) {
			$classname = ' '.$classname;
		} else {
			$classname = '';
		}

		return bab_sprintf(
			'<div class="bab_SearchRecord'.$classname.'">
				<h6 class="BabSiteAdminTitleFontBackground">%s</h6> 
				%s
			</div>',
			$title,
			$content 
		);
	}


	
	/**
	 * Remove html entities 
	 * @param	string	$string
	 * @return string
	 */
	public static function unhtmlentities($string) {

		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
		// replace literal entities
		$trans_tbl = get_html_translation_table(HTML_ENTITIES);
		$trans_tbl = array_flip($trans_tbl);
		return strtr($string, $trans_tbl);
	}


	/**
	 * Get a view of search results as HTML string
	 * The items to display are extracted from the <code>bab_SearchResult</code> object,
	 * the display start at the iterator current position and stop after $count elements
	 *
	 * @param	int									$count		number of items to display
	 *
	 * @return string
	 */
	public function getHtml($count) {
		return bab_SearchResult::getHtmlDefault($this->getRealm(), $this, $count);
	}


}



class bab_searchArrayResult extends ArrayIterator {


	/**
	 * @var bab_SearchRealm
	 */
	private $realm = null;

	/**
	 * Set search realm associated to this result set
	 * @return bab_searchResult
	 */
	final public function setRealm($realm) {
		$this->realm = $realm;
	}

	/**
	 * Get search realm associated to this result set
	 * @return bab_SearchRealm | null
	 */
	final public function getRealm() {
		return $this->realm;
	}

	
	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$arr = parent::current();

		$record = new bab_SearchRecord();
		$record->setRealm($this->getRealm());
		foreach($arr as $field => $value) {
			$record->$field = $value;
		}

		return $record;
	}


	/**
	 * Get a view of search results as HTML string
	 * The items to display are extracted from the <code>bab_SearchResult</code> object,
	 * the display start at the iterator current position and stop after $count elements
	 *
	 * @param	int									$count		number of items to display
	 *
	 * @return string
	 */
	public function getHtml($count) {
		return bab_SearchResult::getHtmlDefault($this->getRealm(), $this, $count);
	}

}





/**
 * Search results for mysql ressource
 * @see bab_SearchRealm::search()
 *
 *
 * @package search
 */
class bab_SearchSqlResult extends bab_SearchResult {

	private $key = null;
	private $ressource = null;
	private $current = null;

	public function setRessource($res) {

		$this->ressource = $res;
	}

	public function current() {

		if (!$this->valid()) {
			return false;
		}

		$record = new bab_SearchRecord();
		$record->setRealm($this->getRealm());
		foreach($this->current as $field => $value) {
			$record->$field = $value;
		}

		return $record;
	}

	/**
	 * @return int	
	 */
	public function key() {

		if (null === $this->key) {
			return 0;
		}

		return $this->key;
	}

	public function next() {
		global $babDB;

		if ($this->current = $babDB->db_fetch_assoc($this->ressource)) {
			$this->key++;
		}
	}

	public function rewind() {
		if (0 !== $this->key()) {
			$this->seek(0);
		}
	}

	public function count() {
		global $babDB;
		return $babDB->db_num_rows($this->ressource);
	}

	public function seek($index) {
		global $babDB;

		if (!$this->ressource) {
			throw new OutOfBoundsException('Invalid ressource');
		}

		if ($index >= $this->count()) {
			throw new OutOfBoundsException('Invalid seek position : '.$index.', count : '.$this->count());
		}

		$this->key = (int) $index;

		$babDB->db_data_seek($this->ressource, $this->key);
	}

	/**
	 * @return boolean
	 */
	public function valid() {

		if (null === $this->key || null === $this->current) {
			$this->next();
		}

		return false !== $this->current;
	}
}



/**
 * Create empty resultset
 */
class bab_SearchEmptyResult extends bab_SearchSqlResult {

	public function __construct($realm) {
		$this->setRealm($realm);
		$this->setRessource(false);
	}
}







/**
 * Search record, contain the data of a seach result
 * @package search
 */
class bab_SearchRecord {

	/**
	 * @var bab_SearchRealm
	 */
	private $realm 	= null;


	private $data	= array();


	/**
	 * Set search realm associated to this result set
	 * @return bab_searchResult
	 */
	final public function setRealm($realm) {
		$this->realm = $realm;
	}

	/**
	 * Get search realm associated to this result set
	 * @return bab_SearchRealm | null
	 */
	final public function getRealm() {
		return $this->realm;
	}


	/**
	 * Return the value of the specified field
	 * 
	 * @param	string	$sFieldName	The name of the field for which the value must be returned.
	 *
	 * @return mixed The value of the field or null if the field is not a part of the record
	 */
	public function __get($sFieldName)
	{
		if (isset($this->data[$sFieldName])) {
			return $this->data[$sFieldName];
		}

		return null;
	}

	
	public function __isset($sFieldName)
	{
		return isset($this->data[$sFieldName]);
	}

	/**
	 * Set the value of a field, if the fieldname is not a part of the record nothing happend
	 *
	 * @param	string	$sFieldName		The name of the field for which the value must set
	 * @param	mixed	$sFieldValue	The value of the field for which the value must set
	 */
	public function __set($sFieldName, $sFieldValue)
	{
		$this->data[$sFieldName] = $sFieldValue;
	}
}


