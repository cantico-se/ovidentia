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

/**
 * Contacts search realm
 * @package	search
 */
class bab_SearchRealmContacts extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'contacts';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Contacts');
	}

	public function getSortKey() {
		return '0060';
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return bab_siteMap::getUrlById('babUserContacts');
	}

	/**
	 * Contacts are sorted by publication date
	 * @return	array
	 */
	public function getSortMethods() {

		return array(
			'lastname' 			=> bab_translate('Lastname'),
			'firstname' 		=> bab_translate('Firstname'),
			'email' 			=> bab_translate('Email'),
			'compagny' 			=> bab_translate('Compagny'),
			'lastnamedesc' 		=> bab_translate('Lastname descending'),
			'firstnamedesc' 	=> bab_translate('Firstname descending'),
			'emaildesc' 		=> bab_translate('Email descending'),
			'compagnydesc' 		=> bab_translate('Compagny descending')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbtable' => bab_translate('Contacts content')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))	->virtual(true),
			$this->createField('id'					, bab_translate('Id'))					->searchable(false),
			$this->createField('owner'				, bab_translate('Owner'))				->searchable(false),
			$this->createField('firstname'			, bab_translate('Firstname')),
			$this->createField('lastname'			, bab_translate('Lastname')),
			$this->createField('email'				, bab_translate('Email')),
			$this->createField('compagny'			, bab_translate('Compagny')),
			$this->createField('hometel'			, bab_translate('Home tel')),
			$this->createField('mobiletel'			, bab_translate('Mobile')),
			$this->createField('businesstel'		, bab_translate('Business tel')),
			$this->createField('businessfax'		, bab_translate('Business fax')),
			$this->createField('jobtitle'			, bab_translate('Job title')),
			$this->createField('businessaddress'	, bab_translate('Business address')),
			$this->createField('homeaddress'		, bab_translate('Home address')),
		);
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() {
		return ($GLOBALS['BAB_SESS_LOGGED'] && bab_contactsAccess());
	}


	/**
	 * Get default criteria for notes
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		
		return $this->owner->is($GLOBALS['BAB_SESS_USERID']);
	}



	/**
	 * Search location "dbtable"
	 * @see bab_SearchRealmContacts::getSearchLocations()
	 * @return resource
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		global $babDB;

		if (null === $this->sort_method) {
			$this->setSortMethod('lastname');
		}


		switch($this->sort_method) {

			case 'lastnamedesc':
				$orderby = $babDB->backTick('lastname').' DESC';
				break;
			case 'firstnamedesc':
				$orderby = $babDB->backTick('firstname').' DESC';
				break;
			case 'emaildesc':
				$orderby = $babDB->backTick('email').' DESC';
				break;
			case 'compagnydesc':
				$orderby = $babDB->backTick('compagny').' DESC';
				break;

			default:
				$orderby = $babDB->backTick($this->sort_method);
		}


		$mysql = $this->getBackend('mysql');
		$req = 'SELECT 
			`id`, 
			`owner`, 
			`firstname`, 
			`lastname`, 
			`email`, 
			`compagny`, 
			`hometel`, 
			`mobiletel`,
			`businesstel`,
			`businessfax`,
			`jobtitle`,
			`businessaddress`,
			`homeaddress` 
		FROM 
			'.BAB_CONTACTS_TBL.' '.$mysql->getWhereClause($criteria).' 

		ORDER BY '.$orderby;

		bab_debug($req, DBG_INFO, 'Search');

		return $babDB->db_query($req);
	}





	/**
	 * Search in notes from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {

		$result = new bab_SearchContactsResult;
		$result->setRealm($this);

		$locations = $this->getSearchLocations();

		// only one location possible in this search realm

		if (isset($locations['dbtable'])) {
			$resource = $this->dbtable($criteria);
			$result->setResource($resource);
			return $result;
		}
		
		throw new Exception('No valid search location');
	}


	
}






/**
 * Custom result object to add reference support to the record
 * @package search
 */
class bab_SearchContactsResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('contacts', 'contact', $record->id);
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
		$template = new bab_SearchRealmContacts_ResultTemplate($this, $count);
		return bab_printTemplate($template, 'search.html', 'contacts_results');
	}

}












class bab_SearchRealmContacts_ResultTemplate extends bab_SearchTemplate {

	private $rescon;
	private $pos;


	public function __construct($res, $count) {
		
		$this->rescon = $res;
		$this->count = $count;

		$this->pos = $res->key();

		$this->firstname = bab_translate('Firstname');
		$this->lastname = bab_translate('Lastname');
		$this->email = bab_translate('Email');
		$this->company = bab_translate('Company');
	}



	/**
	 * Template method
	 */
	public function getnextcon()
		{
		if($this->slicePage($this->rescon, 'contacts',  $this->pos, $this->count))
			{
			$record = $this->rescon->current();
			$this->altbg 		= !$this->altbg;

			$this->fullname 	= bab_toHtml(bab_composeUserName( $record->firstname,$record->lastname));
			$this->confirstname = bab_toHtml($record->firstname);
			$this->conlastname 	= bab_toHtml($record->lastname);
			$this->conemail 	= bab_toHtml($record->email);
			$this->concompany 	= bab_toHtml($record->compagny);
			$this->fullnameurl 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=contacts&id=".$record->id."&w=".bab_SearchDefaultForm::highlightKeyword());

			$this->rescon->next();
			return true;
			}
		
		return false;	
		}
}