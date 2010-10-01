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
 * Articles search realm
 * Search in posts
 *
 * @package	search
 */
class bab_SearchRealmArticlesFiles extends bab_SearchRealmTopic {

	/**	
	 * The default search form can set a filter on topic author and date
	 * The filter is not usable with swish-e, it is stored here for a use in the search method
	 */
	private $sql_criteria = null;







	/**
	 * @return 	string
	 */
	public function getName() {
		return 'articlesfiles';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Articles attachements');
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return null;
	}

	/**
	 *
	 * @return	array
	 */
	public function getSortMethods() {
		
		return array(
			'relevance' => bab_translate('Relevance')
		);
	}

	/**
	 * Search locations
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'content' => bab_translate('Files content'),
			'dbtable' => bab_translate('Files names and descriptions')
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
				$this->createField('ov_reference'		, bab_translate('Ovidentia reference'))					->virtual(true),
				$this->createField('file'				, bab_translate('File path'))							->searchable(false),
				$this->createField('filename'			, bab_translate('File name'))->setRealName('name'),
				$this->createField('description'		, bab_translate('File description'))					->setTableAlias('f'),
				$this->createField('title'				, bab_translate('File title'))							->searchable(false),
				$this->createField('relevance'			, bab_translate('Search relevance'))					->virtual(true),
				$this->createField('id'					, bab_translate('Attachement numeric identifier'))		->searchable(false)->setTableAlias('f'),
				$this->createField('id_article'			, bab_translate('Article numeric identifier'))			->searchable(false)->setTableAlias('a'),
				$this->createField('id_author'			, bab_translate('Article author numeric identifier'))	->searchable(false)->setTableAlias('a'),
				$this->createField('date_publication'	, bab_translate('Article publication date'))			->searchable(false)->setTableAlias('a')->setRealName('date'),
				$this->createField('id_topic'			, bab_translate('Articles numeric identifier'))			->searchable(false)->setTableAlias('f'),
				$this->createField('search'				, bab_translate('search in file content'))				->searchable(false),
				$this->createField('id_dgowner'			, bab_translate('Delegation numeric identifier'))		->searchable(false)->setTableAlias('c')
				
			);
		}

		return $return;
	}

	/**
	 * Test if search realm is accessible
	 * @return bool
	 */
	public function isAccessValid() {
		$engine = bab_searchEngineInfos();
		if ($engine['indexes']['bab_art_files']['index_disabled']) {
			return false;
		}

		return 0 < count(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL));
	}


	/**
	 * Get default criteria 
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {

		return new bab_SearchInvariant;
	}


	/**
	 * Search in content
	 * @return	array
	 */
	private function searchContent() {

		global $babDB;

		$return = array();
		
		$engine = bab_searchEngineInfos();
		if ($engine['indexes']['bab_art_files']['index_disabled']) {
			return array();
		}


		$arr = bab_searchIndexedFilesFromCriteria($this->getSearchFormFieldLessCriteria(), 'bab_art_files');
		if (empty($arr)) {
			return array();
		}

		foreach($arr as $key => $result) {

			$filename = basename($result['file']);
			$iOffset = mb_strpos($filename,',');

			if(false === $iOffset)
			{
				bab_debug('Unexpected error, article file format', DBG_ERROR , 'Search');
				break;
			}

			$id_article = (int) mb_substr($filename, 0, $iOffset);
			$filename = mb_substr($filename, $iOffset + 1);
				

			$query = '
				SELECT 
					f.id,
					f.description,
					a.id_topic,
					a.id id_article,
					a.id_author,
					a.restriction, 
					a.date date_publication, 
					c.id_dgowner 
				FROM 
					'.BAB_ART_FILES_TBL.' f, 
					'.BAB_ARTICLES_TBL.' a,
					'.BAB_TOPICS_TBL.' t,
					'.BAB_TOPICS_CATEGORIES_TBL.' c 
				WHERE 
					a.id_topic = t.id
					AND c.id = t.id_cat 
					AND f.name = '.$babDB->quote($filename).' 
					AND f.id_article = '.$babDB->quote($id_article).' 
					AND f.id_article = a.id 
					AND a.id_topic IN('.$babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)).')
			';

			if ($this->sql_criteria) {
				$mysql = $this->getBackend('mysql');
				$where = $this->sql_criteria->toString($mysql);

				if ($where) {
					$query .= ' AND '.$where;
				}
			}

			// bab_debug($query);

			$res = $babDB->db_query($query);

			$access = $babDB->db_fetch_assoc($res);

			if (!$access) {
				continue;
			}

			if(!bab_articleAccessByRestriction($access['restriction'])) {
				continue;
			}

			$id_file = (int) $access['id'];

			$return[$id_file] = array(
				'ov_reference' 		=> bab_buildReference('articles', 'attachement', $id_file),
				'id' 				=> $id_file,
				'file'				=> $result['file'],
				'filename' 			=> $filename,
				'description'		=> $access['description'],
				'title'				=> $result['title'],
				'relevance'			=> $result['relevance'],
				'id_topic'			=> (int) $access['id_topic'],
				'id_article'		=> (int) $access['id_article'],
				'id_author'			=> (int) $access['id_author'], 
				'date_publication' 	=> $access['date_publication'],
				'id_dgowner'		=> (int) $access['id_dgowner']
			);
		}

		return $return;
	}




	/**
	 * Search filename and description
	 * @return array
	 */
	private function dbtable(bab_SearchCriteria $criteria) {

		include_once dirname(__FILE__) .'/artincl.php';

		global $babDB;

		$query = '
			SELECT 
				f.id,
				f.name filename,
				f.description,
				a.id_topic,
				a.id id_article,
				a.id_author,
				a.restriction, 
				a.date date_publication,
				c.id_dgowner 
			FROM 
				'.BAB_ART_FILES_TBL.' f, 
				'.BAB_ARTICLES_TBL.' a,
				'.BAB_TOPICS_TBL.' t,
				'.BAB_TOPICS_CATEGORIES_TBL.' c  
			WHERE 
				a.id_topic = t.id
				AND c.id = t.id_cat 
				AND f.id_article = a.id 
				AND a.id_topic IN('.$babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)).')
		';

		$mysql = $this->getBackend('mysql');
		$where = $criteria->toString($mysql);

		if ($where) {
			$query .= ' AND '.$where;
		}

		$query .= ' ORDER BY a.date DESC';

		$return = array();
		
		$res = $babDB->db_query($query);
		while ($row = $babDB->db_fetch_assoc($res)) {

			if(!bab_articleAccessByRestriction($row['restriction'])) {
				continue;
			}

			$id_file = (int) $row['id'];

			$return[$id_file] = array(
				'ov_reference' 		=> bab_buildReference('articles', 'attachement', $id_file),
				'id' 				=> $id_file,
				'file'				=> bab_getUploadArticlesPath().$row['id_article'].','.$row['filename'],
				'filename' 			=> $row['filename'],
				'description'		=> $row['description'],
				'title'				=> '',
				'relevance'			=> 0,
				'id_topic'			=> (int) $row['id_topic'],
				'id_article'		=> (int) $row['id_article'],
				'id_author'			=> (int) $row['id_author'], 
				'date_publication' 	=> $row['date_publication'],
				'id_dgowner'		=> (int) $access['id_dgowner']
			);
		}

		return $return;
	}






	/**
	 * Search from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {
		global $babDB;
		$arr = array();
		$locations = $this->getSearchLocations();

		if (isset($locations['content'])) {
			$arr += $this->searchContent();
		}

		if (isset($locations['dbtable'])) {
			$arr += $this->dbtable($criteria);
		}


		$result = new bab_SearchArticlesFilesResult($arr);
		$result->setRealm($this);

		return $result;
	}








	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		// default search fields
		$criteria = bab_SearchDefaultForm::getCriteria($this);

		$this->sql_criteria = new bab_SearchInvariant;
		
		
		$delegation = bab_rp('delegation', null);
		
		if (null !== $delegation && 'DGAll' !== $delegation)
		{
			// if id_dgowner field exist on search real, filter by delegation
			
			require_once dirname(__FILE__).'/delegincl.php';
			$arr = bab_getUserVisiblesDelegations();
			
			if (isset($arr[$delegation]))
			{
				$id_dgowner = $arr[$delegation]['id'];
				$this->sql_criteria = $this->sql_criteria->_AND_($this->id_dgowner->is($id_dgowner));
			}
		}
		
		
		if ($id_topic = self::getRequestedTopics()) {
			$this->sql_criteria = $this->sql_criteria->_AND_($this->id_topic->in($id_topic));
		}
		

		$a_authorid = (int) bab_rp('a_authorid');
		if ($a_authorid) {
			$this->sql_criteria = $this->sql_criteria->_AND_($this->id_author->is($a_authorid));
		}

		include_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		if ($after = BAB_DateTime::fromUserInput(bab_rp('after'))) {
			$this->sql_criteria = $this->sql_criteria->_AND_($this->date_publication->greaterThanOrEqual($after->getIsoDateTime()));
		}

		if ($before = BAB_DateTime::fromUserInput(bab_rp('before'))) {
			$before->add(1, BAB_DATETIME_DAY);
			$this->sql_criteria = $this->sql_criteria->_AND_($this->date_publication->lessThan($before->getIsoDateTime()));
		}


		return $criteria;
	}
}



class bab_SearchArticlesFilesResult extends bab_searchArrayResult {

	/**
	 * Get a view of search results as HTML string
	 * The items to display are extracted from the <code>bab_SearchResult</code> object,
	 * the display start at the iterator current position and stop after $count elements
	 *
	 * @param	int				$count		number of items to display
	 *
	 * @return string
	 */
	public function getHtml($count) {

		$return = '';

		while ($this->valid() && 0 < $count) {

			$count--;
			$file = $this->current();
			$icon = bab_SearchTemplate::getIcon($file->file);
			$downloadurl = bab_sprintf('?tg=articles&idx=getf&topics=%d&idf=%d', $file->id_topic, $file->id);

			$arttopic 		= bab_SearchRealmTopic::categoriesHierarchy($file->id_topic);

			$return .= bab_sprintf('
				<div class="bab_SearchRecord">
					<table>
						<tr>
							<td>%s</td>
							<td>
								<p><strong><a href="%s">%s</a></strong><br />
								%s</p>
								<p>%s <span class="bottom">%s / <a href="%s">%s</a></span></p>
							</td>
						</tr>
					</table>
				</div>', 
				$icon, 
				bab_toHtml($downloadurl), 
				bab_toHtml($file->filename), 
				empty($file->description) ? bab_toHtml($file->title) : bab_toHtml($file->description),
				bab_toHtml(bab_translate('The file is attached to article :')),
				$arttopic,
				bab_toHtml($GLOBALS['babUrlScript'].'?tg=articles&idx=More&topics='.$file->id_topic.'&article='.$file->id_article),
				bab_toHtml(bab_getArticleTitle($file->id_article))
			);

			$this->next();
		}

		return $return;
	}
}

