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
 * Articles comments search realm
 *
 * @package	search
 */
class bab_SearchRealmArticlesComments extends bab_SearchRealmTopic {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'articlescomments';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Articles comments');
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return null;
	}

	/**
	 * ArticlesComments are sorted by publication date
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
			'dbtable' => bab_translate('Comments')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))			->virtual(true),
			$this->createField('id'					, bab_translate('Id'))							->searchable(false),
			$this->createField('id_author'			, bab_translate('Author numeric identifier'))	->searchable(false),
			$this->createField('name'				, bab_translate('Author name')),
			$this->createField('id_topic'			, bab_translate('Topic numeric identifier'))	->searchable(false),
			$this->createField('id_article'			, bab_translate('Article numeric identifier'))	->searchable(false),
			$this->createField('subject'			, bab_translate('Subject')),
			$this->createField('message'			, bab_translate('Message')),
			$this->createField('message_format'		, bab_translate('Message format'))				->searchable(false),
			$this->createField('confirmed'			, bab_translate('Approbation status'))			->searchable(false),
			$this->createField('date_publication'	, bab_translate('Creation date'))->setRealName('date') 
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
	 * Search location "dbtable"
	 * @see bab_SearchRealmArticlesComments::getSearchLocations()
	 * @return ressource
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		global $babDB;

		$mysql = $this->getBackend('mysql');
		$req = '
			SELECT 
				id, 
				id_author,
				`name`,
				id_topic, 
				id_article,
				subject,
				message,
				confirmed,
				`date` date_publication  
			FROM 
				'.BAB_COMMENTS_TBL.' '.$mysql->getWhereClause($criteria).' 
			ORDER BY `date` DESC
		';

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

		$result = new bab_SearchArticlesCommentsResult;
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
class bab_SearchArticlesCommentsResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('articles', 'comment', $record->id);

			include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';

			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($record->message);
			$editor->setFormat($record->message_format);
			$record->message = $editor->getHtml();
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

			$date 			= BAB_DateTimeUtil::relativePastDate($record->date_publication);
			$author 		= empty($record->id_author) ? bab_translate("Anonymous") : bab_toHtml(bab_getUserName($record->id_author));
			$arttopic 		= bab_SearchRealmTopic::categoriesHierarchy($record->id_topic);
			$articleurlpop 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=articles&id=".$record->id."&w=".bab_SearchDefaultForm::highlightKeyword());
			$articleurl 	= bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$record->id_topic."&article=".$record->id);
			$message 		= bab_abbr(bab_SearchResult::unhtmlentities(strip_tags(bab_toHtml($record->message, BAB_HTML_REPLACE))), BAB_ABBR_FULL_WORDS, 500);

			$popupicon 		= bab_sprintf('<img alt="popup" src="%simages/Puces/popupselect.gif" align="absmiddle" />', $GLOBALS['babSkinPath']);
			$author			= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Author'), bab_toHtml($author));
			$creation_date	= $date ? bab_sprintf('<strong>%s :</strong> %s', bab_translate('Date'), bab_toHtml($date)) : '';

			$position		= bab_sprintf('<strong>%s :</strong> %s / <a href="%s">%s</a> <a href="%s" onclick="bab_popup(this.href);return false;">%s</a>',
								bab_translate('Comment on article'),
								$arttopic,
								$articleurl,
								bab_toHtml(bab_getArticleTitle($record->id_article)),
								$articleurlpop,
								$popupicon
							);

			$return .= bab_SearchResult::getRecordHtml(
								bab_toHtml($record->subject),
								bab_sprintf('
										<p>%s</p>
										<p>%s</p>
										<p><span class="bottom">%s &nbsp;&nbsp; %s</span></p>
									', 
									bab_toHtml($message),
									$position,
									$author, $creation_date
								)
							);

			$this->next();
		}

		return $return;
	}
}
