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
include_once dirname(__FILE__).'/searchrealm.articles.php';
include_once dirname(__FILE__).'/searchrealm.articlesfiles.php';
include_once dirname(__FILE__).'/searchrealm.articlescomments.php';

/**
 * Publication search realm
 * @package	search
 */
class bab_SearchRealmPublication extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'publication';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Articles');
	}

	
	public function getSortKey() {
		return '0010';
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
			'date' => bab_translate('Publication date')
		);
	}

	/**
	 * 
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'all' => bab_translate('Articles, comments, articles files attachements')
		);
	}

	/**
	 * @return array
	 */
	public function getFields() {
		return array(
			$this->createField('id_topic'			, bab_translate('Topic numeric identifier'))	->searchable(false)
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
		
		return $this->id_topic->in(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL));
	}



	





	/**
	 * Search with default form
	 * @param	bab_SearchCriteria	$criteria		not used
	 *
	 * @return 	bab_SearchResultCollection
	 */
	public function search(bab_SearchCriteria $criteria) {

		$result = new bab_SearchArticlesResult;
		$result->setRealm($this);

		$locations = $this->getSearchLocations();

		// only one location possible in this search realm

		if (isset($locations['all'])) {

			$articles 	= new bab_SearchRealmArticles;
			$files 		= new bab_SearchRealmArticlesFiles;
			$comments 	= new bab_SearchRealmArticlesComments;

			$arr = array(
				$articles	->search($articles	->getSearchFormCriteria()), 
				$files		->search($files		->getSearchFormCriteria()),
				$comments	->search($comments	->getSearchFormCriteria())
			);


			$collection = new bab_SearchResultCollection($arr);

			$collection->setTitle('Articles, comments, articles attachements');
			
			return $collection;
		}
		
		throw new Exception('No valid search location');
	}


	/**
	 * Get search form as HTML string
	 * @return string
	 */
	public function getSearchFormHtml() {

		$articles 	= new bab_SearchRealmArticles;
		return $articles->getSearchFormHtml();
	}

}




