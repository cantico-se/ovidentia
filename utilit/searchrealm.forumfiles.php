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
include_once dirname(__FILE__).'/forumincl.php';


/**
 * Forum search realm
 * Search in posts
 *
 * @package	search
 */
class bab_SearchRealmForumFiles extends bab_SearchRealm {

	/**
	 * @return 	string
	 */
	public function getName() {
		return 'forumfiles';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Forum posts attachements');
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
			'content' 		=> bab_translate('Files content'),
			'dbtable' 		=> bab_translate('Files names')
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
				$this->createField('ov_reference'	, bab_translate('Ovidentia reference'))				->virtual(true),
				$this->createField('file'			, bab_translate('File path'))						->searchable(false),
				$this->createField('filename'		, bab_translate('File name'))->setRealName('name')->setTableAlias('f'),
				$this->createField('title'			, bab_translate('File title'))						->searchable(false),
				$this->createField('relevance'		, bab_translate('Search relevance'))				->searchable(false),
				$this->createField('id'				, bab_translate('Attachement numeric identifier'))	->virtual(true),
				$this->createField('id_post'		, bab_translate('Post numeric identifier'))			->virtual(true),
				$this->createField('id_thread'		, bab_translate('Thread numeric identifier'))		->virtual(true),
				$this->createField('id_forum'		, bab_translate('Forum numeric identifier'))		->virtual(true),
				$this->createField('post_subject'	, bab_translate('Post subject'))					->virtual(true),
				$this->createField('id_dgowner'		, bab_translate('Delegation numeric identifier'))	->searchable(false)->setTableAlias('m')
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
		if ($engine['indexes']['bab_forumsfiles']['index_disabled']) {
			return false;
		}

		return 0 < count(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL));
	}


	/**
	 * Get default criteria 
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {

		return new bab_SearchInvariant;
	}



	/**
	 * Search in files content
	 * @return array
	 */
	private function content(bab_SearchCriteria $criteria) {
		global $babDB;

		$return = array();
		$arr = array();

		$engine = bab_searchEngineInfos();
		if ($engine['indexes']['bab_forumsfiles']['index_disabled']) {
			return array();
		}

		$arr = bab_searchIndexedFilesFromCriteria($criteria, 'bab_forumsfiles');

		if (empty($arr)) {
			return array();
		}

		foreach($arr as $key => $result) {
			list($id_post, $filename) = bab_getForumFileParts($result['file']);

			$query = '
				SELECT 
					f.id,
					t.forum id_forum,
					p.id_thread, 
					p.subject post_subject,
					m.id_dgowner  
				FROM 
					'.BAB_FORUMSFILES_TBL.' f, 
					'.BAB_POSTS_TBL.' p,
					'.BAB_THREADS_TBL.' t,
					'.BAB_FORUMS_TBL.' m 
				WHERE 
					f.name = '.$babDB->quote($filename).' 
					AND p.id = '.$babDB->quote($id_post).' 
					AND f.id_post = p.id 
					AND p.id_thread = t.id 
					AND p.confirmed = '.$babDB->quote('Y').' 
					AND t.forum IN('.$babDB->quote(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL)).') 
					AND t.forum = m.id 
			';

			$res = $babDB->db_query($query);

			$access = $babDB->db_fetch_assoc($res);

			if (!$access) {
				continue;
			}

			$id_file = (int) $access['id'];


			$return[$id_file] = array(
				'ov_reference' 		=> bab_buildReference('forums', 'attachement', $id_file),
				'id' 				=> $id_file,
				'file'				=> $result['file'],
				'filename' 			=> $filename,
				'title'				=> $result['title'],
				'relevance'			=> $result['relevance'],
				'id_post'			=> (int) $id_post,
				'id_forum'			=> (int) $access['id_forum'],
				'id_thread'			=> (int) $access['id_thread'], 
				'post_subject' 		=> $access['post_subject'],
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

		global $babDB;

		$query = '
			SELECT 
				f.id,
				f.name filename,
				t.forum id_forum,
				p.id id_post,
				p.id_thread, 
				p.subject post_subject,
				m.id_dgowner  
			FROM 
				'.BAB_FORUMSFILES_TBL.' f, 
				'.BAB_POSTS_TBL.' p,
				'.BAB_THREADS_TBL.' t, 
				'.BAB_FORUMS_TBL.' m 
			WHERE 
				f.id_post = p.id 
				AND p.id_thread = t.id 
				AND p.confirmed = '.$babDB->quote('Y').' 
				AND t.forum IN('.$babDB->quote(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL)).') 
				AND t.forum = m.id 
				
		';

		$mysql = $this->getBackend('mysql');
		$where = $criteria->toString($mysql);

		if ($where) {
			$query .= ' AND '.$where;
		}

		$query .= ' ORDER BY p.date DESC';

		$return = array();
		
		$res = $babDB->db_query($query);
		while ($row = $babDB->db_fetch_assoc($res)) {

			$id_file = (int) $row['id'];

			$return[$id_file] = array(
				'ov_reference' 		=> bab_buildReference('forums', 'attachement', $id_file),
				'id' 				=> $id_file,
				'file'				=> $GLOBALS['babUploadPath'].'/forums/'.$row['id_post'].','.$row['filename'],
				'filename' 			=> $row['filename'],
				'title'				=> '',
				'relevance'			=> 0,
				'id_post'			=> (int) $row['id_post'],
				'id_forum'			=> (int) $row['id_forum'],
				'id_thread'			=> (int) $row['id_thread'], 
				'post_subject' 		=> $row['post_subject'],
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
			$arr += $this->content($criteria);
		}
		
		if (isset($locations['dbtable'])) {
			$arr += $this->dbtable($criteria);
		}
		
		$result = new bab_SearchForumFilesResult($arr);
		$result->setRealm($this);

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



class bab_SearchForumFilesResult extends bab_searchArrayResult {




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

		while ($this->valid()) {

			$record = $this->current();
			$icon = bab_SearchTemplate::getIcon($record->file);
			$downloadurl = bab_sprintf('?tg=posts&idx=dlfile&forum=%d&post=%d&file=%s', $record->id_forum, $record->id_post, $record->filename);
			

			$return .= bab_sprintf('
				<div class="bab_SearchRecord">
					<table>
						<tr>
							<td>%s</td>
							<td>
								<strong><a href="%s">%s</a></strong><br />
								%s<br />
								<p>%s <span class="bottom"><a href="%s">%s</a></span></p>
							</td>
						</tr>
					</table>
				</div>', 
				$icon, 
				bab_toHtml($downloadurl), 
				bab_toHtml($record->filename), 
				empty($record->description) ? bab_toHtml($record->title) : bab_toHtml($record->description),
				bab_toHtml(bab_translate('The file is attached to post :')),
				bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$record->id_forum."&thread=".$record->id_thread."&post=".$record->id_post."&flat=0"),
				bab_toHtml($record->post_subject)
			);

			$this->next();
		}

		return $return;
	}

}

