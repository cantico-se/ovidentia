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

include_once dirname(__FILE__).'/searchapi.php';






/**
 * Abstract realm for objects in topics
 * @see bab_SearchRealmArticles
 * @see bab_SearchRealmArticlesComments
 */
abstract class bab_SearchRealmTopic extends bab_SearchRealm { 

	/**
	 * additional criteria
	 * 
	 * @var	bab_SearchCriteria
	 */
	protected $contentCriteria = null;


	/**
	 * Get category hierarchy of topics
	 * 
	 * @param	int		$topics
	 * @return string HTML
	 */
	public static function categoriesHierarchy($topics) {
		include_once dirname(__FILE__).'/topincl.php';
		$article_path = new categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
		$out = bab_printTemplate($article_path,"search.html", "article_path");
		return $out;
	}

	/**
	 * The Ovidentia search engine will display a list of places to search, 
	 * the search realm will not be displayed if this method return false
	 * @return boolean
	 */
	public function displayInSearchEngine() {
		return false;
	}


	/**
	 * Get search form as HTML string
	 * @return string
	 */
	public function getSearchFormHtml() {

		$html = parent::getSearchFormHtml();

		$template = new bab_SearchRealmArticles_SearchTemplate();
		$html .= bab_printTemplate($template, 'search.html', 'articles_form');

		return $html;
	}

	
	/**
	 * Get requested topics from drop down list
	 * @return array
	 */
	protected static function getRequestedTopics() {

		$return = array();
		$a_topiccategory = bab_rp('a_topiccategory');

		if (trim($a_topiccategory) != "") {

			$id_category = false;
			$id_topic = false;

			if (false !== mb_strpos($a_topiccategory, 'category-')) {
				$id_category = (int) mb_substr($a_topiccategory, strlen('category-'));
			}

			if (false !== mb_strpos($a_topiccategory, 'topic-')) {
				$id_topic = (int) mb_substr($a_topiccategory, strlen('topic-'));
			}

			if ($id_topic) {
				$return[] = $id_topic;
			}


			if ($id_category) {
				include_once $GLOBALS['babInstallPath'].'utilit/topincl.php';
				$return = bab_getTopicsFromCategory($id_category);
			}
		}
		
		// list only allowed topics
		if ($return)
		{
			$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
			$return = array_intersect($return, $topview);
		}

		return $return;
	}

	
	/**
	 * Set criteria used to search in articles content
	 * @param	bab_SearchCriteria	$criteria
	 * @return 	bab_SearchRealmFiles
	 */
	public function setFieldLessCriteria(bab_SearchCriteria $criteria) {
		$this->contentCriteria = $criteria;

		return $this;
	}


	/**
	 * Get criteria used to search in articles content
	 * @return 	bab_SearchRealmFiles
	 */
	public function getFieldLessCriteria() {
		return $this->contentCriteria;
	}



	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		// default search fields
		$criteria = bab_SearchDefaultForm::getCriteria($this);
		
		if ($topics = self::getRequestedTopics()) {
			$criteria = $criteria->_AND_($this->id_topic->in($topics));
		} else {
			$criteria = $criteria->_AND_($this->id_topic->in(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)));
		}
		

		$a_authorid = (int) bab_rp('a_authorid');
		if ($a_authorid) {
			$criteria = $criteria->_AND_($this->id_author->is($a_authorid));
		}

		include_once $GLOBALS['babInstallPath'].'utilit/dateTime.php';
		if ($after = BAB_DateTime::fromUserInput(bab_rp('after'))) {
			$criteria = $criteria->_AND_($this->date_publication->greaterThanOrEqual($after->getIsoDateTime()));
		}

		if ($before = BAB_DateTime::fromUserInput(bab_rp('before'))) {
			$before->add(1, BAB_DATETIME_DAY);
			$criteria = $criteria->_AND_($this->date_publication->lessThan($before->getIsoDateTime()));
		}


		return $criteria;
	}

}




