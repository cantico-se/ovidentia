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
class bab_SearchRealmForums extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'forums';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Forums');
	}

	public function getSortKey() {
		return '0020';
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
			'all' => bab_translate('Forums posts content, posts files attachements')
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


		$result = new bab_SearchArticlesResult;
		$result->setRealm($this);

		$locations = $this->getSearchLocations();

		// only one location possible in this search realm

		if (isset($locations['all'])) {

			$posts	= new bab_SearchRealmForumPosts;
			$files 	= new bab_SearchRealmForumFiles;

			$arr = array(
				$posts	->search($posts	->getSearchFormCriteria()),
				$files	->search($files	->getSearchFormCriteria())
			);


			$collection = new bab_SearchResultCollection($arr);

			$collection->setTitle('Forum posts, posts attachements');
			
			return $collection;
		}
		
		throw new Exception('No valid search location');
	}







	
}





