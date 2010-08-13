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
 * Faqs search realm
 * @package	search
 */
class bab_SearchRealmFaqs extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'faqs';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('FAQs');
	}

	public function getSortKey() {
		return '0030';
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return $GLOBALS['babUrlScript'].'?tg=faq';
	}

	/**
	 * Faqs are sorted by publication date
	 * @return	array
	 */
	public function getSortMethods() {

		return array(
			'question' => bab_translate('Question')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbtable' => bab_translate('Questions and responses')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))				->virtual(true),
			$this->createField('id'					, bab_translate('Id'))								->searchable(false),
			$this->createField('idcat'				, bab_translate('Category numeric identifier'))		->searchable(false),
			$this->createField('question'			, bab_translate('Question')),
			$this->createField('response'			, bab_translate('Response')),
			$this->createField('response_format'	, bab_translate('Response format'))					->searchable(false),
			$this->createField('date_modification'	, bab_translate('Modification date'))
		);
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() {
		return 0 < count(bab_getUserIdObjects(BAB_FAQCAT_GROUPS_TBL));
	}


	/**
	 * Get default criteria for notes
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		
		return $this->idcat->in(bab_getUserIdObjects(BAB_FAQCAT_GROUPS_TBL));
	}



	/**
	 * Search location "dbtable"
	 * @see bab_SearchRealmFaqs::getSearchLocations()
	 * @return resource
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		global $babDB;

		$mysql = $this->getBackend('mysql');
		$req = 'SELECT 
			`id`, 
			`idcat`, 
			`question`, 
			`response`, 
			`date_modification`  
		FROM 
			'.BAB_FAQQR_TBL.' '.$mysql->getWhereClause($criteria).' 

		ORDER BY `question` ASC';

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

		$result = new bab_SearchFaqsResult;
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
class bab_SearchFaqsResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('faqs', 'question', $record->id);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_faq_response');
			$editor->setContent($record->response);
			$editor->setContent($record->response_format);
			$record->response = $editor->getHtml();

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
		require_once dirname(__FILE__).'/faqincl.php';
		$return = '';


		while ($this->valid() && 0 < $count) {
			$count--;

			$record = $this->current();

			$cat 			= bab_getFaqCategoryHierarchy($record->id);
			$cat[]	 		= bab_toHtml($record->question);

			foreach($cat as &$folder) {
				$folder = bab_toHtml($folder);
			}


			$title 			= implode(' <img src="'.$GLOBALS['babSkinPath'].'images/Puces/arrow_right.gif" alt="/" /> ', $cat);
			$questionurlpop = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=faqs&idc=".$record->idcat."&idq=".$record->id."&w=".bab_SearchDefaultForm::highlightKeyword()); 
			$questionurl 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=Print&item=".$record->idcat."#".$record->id);
			$response		= bab_abbr(bab_SearchResult::unhtmlentities(strip_tags(bab_toHtml($record->response, BAB_HTML_REPLACE))), BAB_ABBR_FULL_WORDS, 500);

			$date_modification = BAB_DateTimeUtil::relativePastDate($record->date_modification);

			if ($date_modification) {
				$date_modification = bab_sprintf('<strong>%s :</strong> %s', bab_translate('Last modification'), $date_modification);
			}


			$return .= $this->getRecordHtml(
								$title,
								bab_sprintf('
										<p>%s</p>
										<a href="%s">%s</a>
										<p><span class="bottom">%s</span></p>
									', 
									bab_toHtml($response),
									$questionurl,
									bab_translate('Read more'),
									$date_modification
								)
							);

			$this->next();
		}

		return $return;
	}
}
