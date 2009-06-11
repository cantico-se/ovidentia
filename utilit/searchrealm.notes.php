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
 * Note search realm
 * @package	search
 */
class bab_SearchRealmNotes extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'notes';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Notes');
	}

	public function getSortKey() {
		return '0040';
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return $GLOBALS['babUrlScript'].'?tg=notes';
	}

	/**
	 * Notes are sorted by publication date
	 * @return	array
	 */
	public function getSortMethods() {

		return array(
			'date' => bab_translate('Publication date')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbtable' => bab_translate('Notes content')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'	, bab_translate('Ovidentia reference'))	->virtual(true),
			$this->createField('id'				, bab_translate('Id'))					->searchable(false),
			$this->createField('id_user'		, bab_translate('Owner'))				->searchable(false),
			$this->createField('content'		, bab_translate('Note content')),
			$this->createField('date'			, bab_translate('Creation date'))
		);
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() {
		return bab_isUserLogged();
	}


	/**
	 * Get default criteria for notes
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		
		return $this->id_user->is($GLOBALS['BAB_SESS_USERID']);
	}



	/**
	 * Search location "dbtable"
	 * @see bab_SearchRealmNotes::getSearchLocations()
	 * @return ressource
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		global $babDB;

		$mysql = $this->getBackend('mysql');
		$req = 'SELECT 
			`id`, 
			`id_user`, 
			`content`, 
			`date` 
		FROM 
			'.BAB_NOTES_TBL.' '.$mysql->getWhereClause($criteria).' 

		ORDER BY `date` DESC';

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

		$result = new bab_SearchNotesResult;
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






}






/**
 * Custom result object to add reference support to the record
 * @package search
 */
class bab_SearchNotesResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('notes', 'note', $record->id);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_note');
			$editor->setContent($record->content);
			$record->content = $editor->getHtml();

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

			$date 			= BAB_DateTimeUtil::relativePastDate($record->date);
			$date			= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Date'), $date);

			$content		= bab_abbr(bab_SearchResult::unhtmlentities(strip_tags($record->content)), BAB_ABBR_FULL_WORDS, 500);
			$editurl		= bab_toHtml($GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$record->id);

			$return .= bab_SearchResult::getRecordHtml(
								$date,
								bab_sprintf('
										<p>%s</p>
										<p><span class="bottom"><a href="%s">%s</a></span></p>
									', 
									bab_toHtml($content),
									$editurl,
									bab_translate('Edit')
								)
							);

			$this->next();
		}

		return $return;
	}

}
