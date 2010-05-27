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
 * Forum search realm
 * Search in posts
 *
 * @package	search
 */
class bab_SearchRealmForumPosts extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'forumposts';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Forums posts');
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return $GLOBALS['babUrlScript'].'?tg=forumsuser';
	}

	/**
	 * 
	 * @return	array
	 */
	public function getSortMethods() {
		
		return array(
			'date' => bab_translate('Publication date')
		);
	}

	/**
	 * Search locations
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbposts' 		=> bab_translate('Forums posts')
		);
	}



	/**
	 *
	 *
	 * @return array
	 */
	public function getFields() {

		static $return = null;

		if (!$return) {

			$return = array(
				$this->createField('ov_reference'	, bab_translate('Ovidentia reference'))			->virtual(true),
				$this->createField('id'				, bab_translate('Post numeric identifier'))		->searchable(false)->setTableAlias('p'),
				$this->createField('id_thread'		, bab_translate('Thread numeric identifier'))	->searchable(false)->setTableAlias('p'),
				$this->createField('id_forum'		, bab_translate('Forum numeric identifier'))	->searchable(false)->setTableAlias('f')->setRealName('id'),
				$this->createField('forum_name'		, bab_translate('Forum name'))					->setTableAlias('f')->setRealName('name'),
				$this->createField('subject'		, bab_translate('Subject'))						->setTableAlias('p'),
				$this->createField('message'		, bab_translate('Message'))						->setTableAlias('p'),
				$this->createField('message_format'	, bab_translate('Message format'))				->searchable(false)->setTableAlias('p'),
				$this->createField('author'			, bab_translate('Author'))						->setTableAlias('p'),
				$this->createField('date'			, bab_translate('Publication date')) 			->setTableAlias('p'),
				$this->createField('confirmed'		, bab_translate('Post approbation status'))		->setTableAlias('p')
			);
		}

		return $return;
	}

	/**
	 * Test if search realm is accessible
	 * @return bool
	 */
	public function isAccessValid() {
		return 0 < count(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL));
	}


	/**
	 * Get default criteria, do the request based on access rights
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {

		$crit = $this->id_forum->in(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL));
		$crit = $crit->_AND_($this->confirmed->is('Y'));
		return $crit;
	}





	/**
	 * Search from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {


		$locations = $this->getSearchLocations();

		if (!isset($locations['dbposts'])) {
			throw new Exception('No valid search location');
		}

		global $babDB;
		

		$result = new bab_SearchForumsResult;
		$result->setRealm($this);

		if (isset($locations['dbposts'])) {
			$where = $criteria->tostring($this->getBackend('mysql'));
		}

		

		$req = 'SELECT 
				p.id, 
				p.id_thread, 
				f.id id_forum, 
				f.name forum_name, 
				p.subject,
				p.message, 
				p.author, 
				p.id_author, 
				p.date,
				p.confirmed 
			FROM 
				'.BAB_POSTS_TBL.' p, 
				'.BAB_THREADS_TBL.' t, 
				'.BAB_FORUMS_TBL.' f   
			WHERE 
				p.id_thread = t.id 
				AND t.forum = f.id 
				';

		if (!empty($where)) {
			$req .= ' AND '.$where;
		}

		$req .= ' ORDER BY date DESC';
		
		
		bab_debug($req, DBG_INFO, 'Search');

		$result->setRessource($babDB->db_query($req));
		return $result;
	}





	/**
	 * The Ovidentia search engine will display a list of places to search, 
	 * the search realm will not be displayed if this method return false
	 * @return boolean
	 */
	public function displayInSearchEngine() {
		return false;
	}

	
}








/**
 * Custom result object
 * @package search
 */
class bab_SearchForumsResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('forums', 'post', $record->id);

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

			$editor = new bab_contentEditor('bab_forum_post');
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

			$date 			= BAB_DateTimeUtil::relativePastDate($record->date);
			$date			= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Date'), $date);

			$message		= bab_abbr(bab_SearchResult::unhtmlentities(strip_tags(bab_toHtml($record->message, BAB_HTML_REPLACE))), BAB_ABBR_FULL_WORDS, 600);
			$author			= '';

			if ($record->author) {
				$author = $record->author;
				$author = bab_getForumContributor($record->id_forum, $record->id_author, $author);
				$author	= bab_sprintf('<strong>%s :</strong> %s', bab_translate('Author'), $author);
			}

			$posturl		= $GLOBALS['babUrlScript'].'?tg=posts&idx=List&forum='.$record->id_forum.'&thread='.$record->id_thread.'&flat=1#p'.$record->id;
			$subject 		= bab_sprintf('<a href="%s">%s</a>', bab_toHtml($posturl), bab_toHtml($record->subject));

			$return .= bab_SearchResult::getRecordHtml(
								bab_sprintf('
										%s <img src="'.$GLOBALS['babSkinPath'].'images/Puces/arrow_right.gif" alt="/" /> %s
									', 
									bab_toHtml($record->forum_name),
									$subject
								),
								bab_sprintf('
										<p>%s</p>
										<p><span class="bottom">%s &nbsp;&nbsp; %s</span></p>
									', 
									bab_toHtml($message),
									$author,
									$date
								)
							);

			$this->next();
		}

		return $return;
	}

}
