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




}




