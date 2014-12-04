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
require_once dirname(__FILE__).'/tagApi.php';
require_once dirname(__FILE__).'/urlincl.php';

/**
 * Tags search realm
 * Search in thesorus
 *
 *
 * @package	search
 */
class bab_SearchRealmTags extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'tags';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Thesaurus');
	}

	/**
	 *
	 * @return	array
	 */
	public function getSortMethods() {
		
		return array(
			'title' 	=> bab_translate('Object title')
		);
	}

	/**
	 * Search locations
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'all' 		=> bab_translate('All objects associated to tag')
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
				$this->createField('ov_reference'	, bab_translate('Ovidentia reference'))				->searchable(false),
				$this->createField('title'			, bab_translate('Object title'))					->virtual(true),
				$this->createField('description'	, bab_translate('Object description'))				->virtual(true),
				$this->createField('url'			, bab_translate('Object url'))						->virtual(true),
				$this->createField('type'			, bab_translate('Object type'))						->virtual(true),
				$this->createField('search'			, bab_translate('Search'))							->searchable(false)
				

			);
		}

		return $return;
	}

	/**
	 * Test if search realm is accessible
	 * @return bool
	 */
	public function isAccessValid() {
		return true;
	}


	/**
	 * Get default criteria 
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {

		$criteria = new bab_SearchInvariant;

		return $criteria;
	}

	
	
	/**
	 * Get array of tags references found using default form
	 * @return array
	 */
	public function getTagsReferences() {
	
	    $option = bab_rp('option');
	
	    require_once dirname(__FILE__) . '/tagApi.php';
	    $oRefMgr = new bab_ReferenceMgr();
	
	    $primary = array();
	    $secondary = array();
	
	
	    if (bab_rp('what')) {
	        $primary_search 	= $oRefMgr->get(bab_rp('what'));
	        if ($primary_search) {
	            foreach($primary_search as $ref) {
	                $primary[(string) $ref] = $ref;
	            }
	        }
	    }
	
	
	    if (bab_rp('what2')) {
	        $secondary_search 	= $oRefMgr->get(bab_rp('what2'));
	        if ($secondary_search) {
	            foreach($secondary_search as $ref) {
	                $secondary[(string) $ref] = $ref;
	            }
	        }
	    } else {
	        $option = 'OR';
	    }
	
	    switch($option) {
	        case 'AND':
	            return array_intersect($primary, $secondary);
	
	        case 'NOT':
	            return array_diff_assoc($primary, $secondary);
	
	        case 'OR':
	        default:
	            return ($primary + $secondary);
	    }
	}
	


	/**
	 * Search from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {

		$arr = $this->getTagsReferences();
		$result = array();

		foreach($arr as $reference) {

			$oReferenceDescription = bab_Reference::getReferenceDescription($reference);
			
			if (is_object($oReferenceDescription) 
				&& in_array('IReferenceDescription', class_implements($oReferenceDescription))) {

				try {
					$accessValid = $oReferenceDescription->isAccessValid();
				} catch (Exception $e) {
					bab_debug($e->getMessage());
					$accessValid = false;
				}
				if ($accessValid) {

					$result[] = array(
						'ov_reference' 	=> $reference,
						'description'	=> $oReferenceDescription
					);

				}
					
			} else {
				bab_debug(sprintf('Result found in thesaurus "%s" but no reference description', $reference->__toString()));
				
			}

			
		}
		
		
		
		
		$result = new bab_SearchTagsResult($result);
		$result->setRealm($this);
		
		return $result;
	}

	/**
	 * Url for tag search
	 * @return string
	 */
	public static function getUrl() {
		$url = bab_url::request_gp();
		$url = bab_url::mod($url, 'what2', '');
		$url = bab_url::mod($url, 'idx', 'find');

		return $url;
	}



}








/**
 * Custom result object
 * @package search
 */
class bab_SearchTagsResult extends bab_searchArrayResult {

	

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {

			$oReferenceDescription = $record->description;

			$record->title			= $oReferenceDescription->getTitle();
			$record->description 	= $oReferenceDescription->getDescription();
			$record->url 			= $oReferenceDescription->getUrl();
			$record->type 			= $oReferenceDescription->getType();
		}

		return $record;
	}



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
		$oRefMgr    = new bab_ReferenceMgr();
		$url = bab_SearchRealmTags::getUrl();

		while ($this->valid() && 0 < $count) {

			$count--;
			$record = $this->current();

			$content = bab_sprintf('<p><strong>%s :</strong> %s</p>', bab_translate('Type'), bab_toHtml($record->type));

			if ($record->description) {
				$description = trim(bab_unhtmlentities(strip_tags($record->description)));
				$content .= '<p>'.bab_toHtml(bab_abbr($description, BAB_ABBR_FULL_WORDS, 500)).'</p>';
			}

			

			$content .= '<p>';
			foreach($oRefMgr->getTagsByReference($record->ov_reference) as $tag) {
				$content .= bab_sprintf(' <span class="bottom"><a href="%s">%s</a></span>',
					bab_toHtml(bab_url::mod($url, 'what', $tag->getName())),
					bab_toHtml($tag->getName())
				);
			}
			$content .= '</p>';

			if ($record->url) {
				$title = bab_sprintf('<a href="%s">%s</a>', bab_toHtml($record->url), bab_toHtml($record->title));
			} else {
				$title = bab_toHtml($record->title);
			}

			$return .= bab_SearchResult::getRecordHtml($title, $content);

			$this->next();
		}

		return $return;
	}

}








