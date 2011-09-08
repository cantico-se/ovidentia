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
include_once dirname(__FILE__).'/searcharticlesincl.php';

/**
 * Article search realm
 * @package	search
 */
class bab_SearchRealmArticles extends bab_SearchRealmTopic {




	/**
	 * cache for indexed files results search
	 */
	private $index_result = null;

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'articles';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Articles content');
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return $GLOBALS['babUrlScript'].'?tg=topusr';
	}

	/**
	 * Articles are sorted by publication date
	 * @return	array
	 */
	public function getSortMethods() {

		return array(
			'relevance'	=> bab_translate('Relevance'),
			'date' => bab_translate('Publication date')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbtable' => bab_translate('Articles in database'),
			'content' => bab_translate('Indexed articles')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))	->virtual(true),
			$this->createField('id'					, bab_translate('Id'))									->searchable(false)	->setTableAlias('a'),
			$this->createField('id_topic'			, bab_translate('Topic numeric identifier'))			->searchable(false)	->setTableAlias('a'),
			$this->createField('id_author'			, bab_translate('Author numeric identifier'))			->searchable(false)	->setTableAlias('a'),
			$this->createField('title'				, bab_translate('Title'))													->setTableAlias('a'),
			$this->createField('head'				, bab_translate('Head'))													->setTableAlias('a'),
			$this->createField('head_format'		, bab_translate('Head format'))							->searchable(false)	->setTableAlias('a'),
			$this->createField('body'				, bab_translate('Body'))													->setTableAlias('a'),
			$this->createField('body_format'		, bab_translate('Body format'))							->searchable(false)	->setTableAlias('a'),
			$this->createField('date_publication'	, bab_translate('Creation date'))->setRealName('date')	->searchable(false)->setTableAlias('a'),
			$this->createField('archive'			, bab_translate('Archived article'))					->searchable(false)	->setTableAlias('a'),
			$this->createField('relevance'			, bab_translate('Relevance'))							->searchable(false)	->setTableAlias('a'),
			$this->createField('id_dgowner'			, bab_translate('Delegation'))							->searchable(false)	->setTableAlias('c'),
			$this->createField('search'				, bab_translate('search in file content'))				->searchable(false)	->setTableAlias('a')
		);
	}

	/**
	 * @return bool
	 */
	public function isAccessValid() {
		return 0 < count(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL));
	}


	/**
	 * Get default criteria for notes
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {
		
		return $this->id_topic->in(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL));
	}



	

	/**
	 *
	 */
	private function createTemporaryTable() {
		global $babDB;

		$req = "
			CREATE TEMPORARY TABLE artresults (
				`id` 			int(11) unsigned NOT NULL, 
				`id_dgowner`	int(11) unsigned NOT NULL, 
				`relevance` 	int(11) unsigned NOT NULL 
			)
		";

		$babDB->db_query($req);
	}



	/**
	 * Search location "dbtable"
	 * @see bab_SearchRealmArticles::getSearchLocations()
	 * @return resource
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		global $babDB;

		$mysql = $this->getBackend('mysql');
		$req = 'INSERT INTO artresults 
			SELECT 
				a.id,
				c.id_dgowner,
				\'0\' relevance 
			
		FROM 
			'.BAB_ARTICLES_TBL.' a 
				LEFT JOIN '.BAB_TOPICS_TBL.' t ON t.id=a.id_topic 
				LEFT JOIN '.BAB_TOPICS_CATEGORIES_TBL.' c ON c.id = t.id_cat 
			
			'.$mysql->getWhereClause($criteria).' 

		ORDER BY a.`title` DESC';

		bab_debug($req, DBG_INFO, 'Search');

		$babDB->db_query($req);
	}




	/**
	 * Query search engine once and store result in a property for others needs
	 * @return array
	 */
	private function getIndexedResults() {

		$criteria = $this->getFieldLessCriteria();

		$engine = bab_searchEngineInfos();
		if ($engine['indexes']['bab_articles']['index_disabled']) {
			return array();
		}
		
		

		if (null === $this->index_result) {
			$this->index_result = bab_searchIndexedFilesFromCriteria($criteria, 'bab_articles');
		}

		return $this->index_result;
	}






	
	/**
	 * Add search into content to temporary table
	 */
	private function addContentSearchResult() {
		global $babDB;

		$arr = $this->getIndexedResults();

		if (empty($arr)) {
			return;
		}

		$insert 	= array();
		$relevance 	= array();

		foreach($arr as $result) {
			$file = basename($result['file']);

			if (preg_match('/^top([0-9]+)_art([0-9]+)\.html$/', $file, $m)) {

				$id_topic 	= (int) $m[1];
				$id_article = (int) $m[2];

				

				if ($id_article && bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $id_topic)) {
					$insert[] = '('.$babDB->quote($id_article).', '.$babDB->quote($result['relevance']).')';
				}
			}
		}

		if ($insert) {
			// insert result references into temporary table
			$babDB->db_query('INSERT INTO artresults (id, relevance) VALUES '.implode(', ',$insert));
		}
	}













	/**
	 * Search from criteria
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {

		$result = new bab_SearchArticlesResult;
		$result->setRealm($this);

		$this->createTemporaryTable();

		$locations = $this->getSearchLocations();

		if (isset($locations['content'])) {
			$this->addContentSearchResult();
		}

		if (isset($locations['dbtable'])) {
			$this->dbtable($criteria);
		}

		$result->setResource($this->getResultResource());
		return $result;
	}



	

	/**
	 * Get the result query
	 * The query is a join between the temporary table and the files table, 
	 * the temporary table contain references to result and relevance key
	 *
	 * @return resource
	 */
	private function getResultResource() {
		global $babDB;

		if (null === $this->sort_method) {
			$this->setSortMethod('relevance');
		}


		switch($this->sort_method) {

			case 'date':
				$orderby = 'date_publication DESC';
				break;

			case 'relevance':
				$orderby = 'relevance DESC, date_publication DESC';
				break;
		}



		$query = '
			SELECT 

				a.`id`, 
				a.`id_topic`, 
				a.`id_author`, 
				a.`title`, 
				a.`head`, 
				a.`body`,
				a.`date` date_publication,
				a.`archive` ,
				r.id_dgowner, 
				r.relevance  
			FROM 
				artresults r,
				'.BAB_ARTICLES_TBL.' a

			WHERE
				a.id = r.id 

			
			GROUP BY r.id 
			ORDER BY '.$orderby.' 
		';

		bab_debug($query, DBG_INFO, 'Search');

		return $babDB->db_query($query);
	}









	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		
		return parent::getSearchFormCriteria();
	}

	/**
	 * Get criteria used to search in articles content
	 * @return 	bab_SearchRealmFiles
	 */
	public function getFieldLessCriteria() {
		return bab_SearchDefaultForm::getFieldLessCriteria($this);
	}
}







/**
 * Custom result object to add reference support to the record
 * @package search
 */
class bab_SearchArticlesResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('articles', 'article', $record->id);

			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';

			$editor = new bab_contentEditor('bab_article_head');
			$editor->setContent($record->head);
			$editor->setFormat($record->head_format);
			$record->head = $editor->getHtml();

			$editor = new bab_contentEditor('bab_article_body');
			$editor->setContent($record->body);
			$editor->setFormat($record->body_format);
			$record->body = $editor->getHtml();

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
		include_once dirname(__FILE__).'/userinfosincl.php';

		$return = '';


		while ($this->valid() && 0 < $count) {
			$count--;

			$record = $this->current();

			$artdate 		= BAB_DateTimeUtil::relativePastDate($record->date_publication);
			$artauthor 		= empty($record->id_author) ? bab_translate("Anonymous") : bab_userInfos::composeHtml($record->id_author);
			$arttopic 		= bab_SearchRealmTopic::categoriesHierarchy($record->id_topic);
			$articleurlpop 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=articles&id=".$record->id."&w=".bab_SearchDefaultForm::highlightKeyword());
			$articleurl 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$record->id_topic."&article=".$record->id);
			$intro 			= bab_abbr(bab_unhtmlentities(strip_tags(bab_toHtml($record->head, BAB_HTML_REPLACE))), BAB_ABBR_FULL_WORDS, 600);

			$popupicon 		= bab_sprintf('<img alt="popup" src="%simages/Puces/popupselect.gif" />', $GLOBALS['babSkinPath']);
			$author			= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Author'), $artauthor);
			$creation_date	= $artdate ? bab_sprintf('<strong>%s :</strong> %s', bab_translate('Date'), bab_toHtml($artdate)) : '';

			$title			= bab_sprintf('%s / <a href="%s">%s</a> <a href="%s" onclick="bab_popup(this.href);return false;">%s</a>',
								$arttopic,
								$articleurl,
								bab_toHtml($record->title),
								$articleurlpop,
								$popupicon	
							);

			$return .= bab_SearchResult::getRecordHtml(
								$title,
								bab_sprintf('
										<p>%s</p>
										<p><span class="bottom">%s &nbsp;&nbsp; %s</span></p>
									', 
									bab_toHtml($intro),
									$author,
									$creation_date
								),
								'bab-article-'.$record->id
							);

			$this->next();
		}

		return $return;
	}
}





