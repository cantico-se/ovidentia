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
 * File manager search realm
 * Search in files
 *
 * to search in files content, use the method <code>setPrimaryCriteria</code>
 * to search in all use <code>setPrimaryCriteria</code> and add the same criteria on the realm object
 *
 * @package	search
 */
class bab_SearchRealmFiles extends bab_SearchRealm {

	/**
	 * Criteria for search
	 * @var bab_SearchCriteria
	 */
	private $criteria;

	/**
	 * additional criteria to search in locations :
	 * 	<ul>
	 *		<li>files_content</li>
	 *		<li>files_content_versions</li>
	 *		<li>metadata</li>
	 *	</ul>
	 * 
	 * @var	bab_SearchCriteria
	 */
	private $contentCriteria = null;

	/**
	 * cache for indexed files results search
	 */
	private $index_result = null;


	/**
	 * @return 	string
	 */
	public function getName() {
		return 'files';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('File manager');
	}

	public function getSortKey() {
		return '0050';
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return $GLOBALS['babUrlScript'].'tg=fileman';
	}

	/**
	 *
	 * @return	array
	 */
	public function getSortMethods() {
		
		return array(
			'relevance' 	=> bab_translate('Relevance'),
			'name'			=> bab_translate('File name'),
			'modified'		=> bab_translate('Modification date'),
			'modifieddesc'	=> bab_translate('Modification date descending')
		);
	}

	/**
	 * Search locations
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'files_content' 			=> bab_translate('Content'),
			'files_content_versions' 	=> bab_translate('Content of old versions'),
			'files'						=> bab_translate('Folders, names and descriptions'),
			'metadata'					=> bab_translate('Aditionnal metadata')
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
				$this->createField('ov_reference'	, bab_translate('Ovidentia reference'))->virtual(true),
				$this->createField('id'				, bab_translate('File numeric identifier'))->searchable(false), 
				$this->createField('name'			, bab_translate('File name')),
				$this->createField('id_owner'		, bab_translate('Owner numeric identifier'))->searchable(false),
				$this->createField('description'	, bab_translate('Description')),
				$this->createField('created'  		, bab_translate('Creation date')),
				$this->createField('modified'		, bab_translate('Modification date')),
				$this->createField('path'			, bab_translate('File path')),
				$this->createField('collective'		, bab_translate('The file is in a collective folder'))->setRealName('bgroup')->searchable(false),
				$this->createField('author'			, bab_translate('Author numeric identifier'))->searchable(false),
				$this->createField('confirmed'		, bab_translate('Approbation status'))->searchable(false),
				$this->createField('state'			, bab_translate('Delete state'))->searchable(false),
				$this->createField('relevance'		, bab_translate('Relevance'))->searchable(false), 
				$this->createField('id_delegation'	, bab_translate('Delegation numeric identifier'))->searchable(false),
				$this->createField('search'			, bab_translate('search on metadata and file content'))->setRealName('fvalue')->searchable(false)
			);
		}

		return $return;
	}

	/**
	 * Test if search realm is accessible
	 * @return bool
	 */
	public function isAccessValid() {
		return (userHavePersonnalStorage() || userHaveRightOnCollectiveFolder());
	}


	/**
	 * Get default criteria 
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {

		$criteria = $this->getFoldersCriteria()
					->_AND_($this->state->is('D')->_NOT_())
					->_AND_($this->confirmed->is('Y'));

		return $criteria;
	}


	/**
	 * Set criteria used to search in files content and in metadata
	 * This criteria set criterions on field : 'search'
	 * @param	bab_SearchCriteria	$criteria
	 * @return 	bab_SearchRealmFiles
	 */
	public function setFieldLessCriteria(bab_SearchCriteria $criteria) {
		$this->contentCriteria = $criteria;

		return $this;
	}



	/**
	 * Search from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {
		global $babDB;

		$this->index_result = null;
		$this->criteria = $criteria;
		$this->createTemporaryTable();

		$locations = $this->getSearchLocations();
		

		if (isset($locations['files_content'])) {
			$this->addContentSearchResult();
		}

		if (isset($locations['files_content_versions'])) {
			$this->addVersionsContentSearchResult();
		}

		if (isset($locations['files'])) {
			$this->addDbSearchResult();
		}

		if (isset($locations['metadata'])) {
			$this->addMetadataSearchResult();
		}


		$result = new bab_SearchFilesResult;
		$result->setRealm($this);
		$result->setResource($this->getResultResource());

		return $result;
	}






	/**
	 *
	 */
	private function createTemporaryTable() {
		global $babDB;

		$req = "
			CREATE TEMPORARY TABLE filresults (
				`id` 			int(11) unsigned NOT NULL, 
				`relevance` 	int(11) unsigned NOT NULL 
			)
		";

		$babDB->db_query($req);
	}

	

	/**
	 * Query search engine once and store result in a property for others needs
	 * @return array
	 */
	private function getIndexedResults() {

		if (null === $this->contentCriteria) {
			throw new Exception('there is no primary criteria, please use the method bab_SearchRealmFiles::setFieldLessCriteria()');
			$this->contentCriteria = $this->criteria;
		}

		$engine = bab_searchEngineInfos();
		if ($engine['indexes']['bab_files']['index_disabled']) {
			
			$this->index_result = array(
				'versions' => array(),
				'currents' => array()
			);
			
			return $this->index_result;
		}

		
		if (null === $this->index_result) {
			
			$this->index_result = array(
				'versions' => array(),
				'currents' => array()
			);

			$found_files = bab_searchIndexedFilesFromCriteria($this->contentCriteria, 'bab_files');

			if (!$found_files) {
				return $this->index_result;
			}

			foreach($found_files as $arr) {
				if (preg_match( "/OVF\/\d,\d,(.*)/", $arr['file'])) {
					$this->index_result['versions'][] = $arr;
				} else {
					$this->index_result['currents'][] = $arr;
				}
			}
		}

		return $this->index_result;
	}


	/**
	 * Add search result from indexation search query
	 * @param	array	$search
	 */
	private function addContentSearchResultQueries($search, $relevance) {

		global $babDB;
		
		if (empty($search)) {
			return;
		}
		

		$query = '
			SELECT id, path, name FROM 
				'.BAB_FILES_TBL.' 
			WHERE 
				('.implode(' OR ', $search).')
		';

		$where = $this->getDefaultCriteria()->tostring($this->getBackend('mysql'));
		if (!empty($where)) {
			$query .= ' AND '.$where;
		}

		bab_debug($query, DBG_INFO, 'Search');

		$res = $babDB->db_query($query);
		
		if (0 === $babDB->db_num_rows($res)) {
			bab_debug("Unexpected error, query with no results : \n".$query, DBG_ERROR  , 'Search');
			return;
		}


		$insert = array();
		while ($arr = $babDB->db_fetch_assoc($res)) {
			$insert[] = '('.$babDB->quote($arr['id']).', '.$babDB->quote($relevance[$arr['path'].$arr['name']]).')';
		}

		// insert result references into temporary table
		$babDB->db_query('INSERT INTO filresults (id, relevance) VALUES '.implode(', ',$insert));
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

		if (empty($arr['currents'])) {
			return;
		}

		$search = array();
		$relevance = array();

		foreach($arr['currents'] as $result) {
			$fullpath = bab_removeFmUploadPath($result['file']);
			$name = basename($fullpath);
			$path = dirname($fullpath);
			if( !empty($path) && '/' !== $path{mb_strlen($path) - 1}) 
			{
				$path .='/';
			}

			
			$search[] = '(path=\''.$babDB->db_escape_string($path).'\' AND name=\''. $babDB->db_escape_string($name)."')";
			$relevance[$path.$name] = $result['relevance'];
		}

		$this->addContentSearchResultQueries($search, $relevance);
	}

	/**
	 * Add search into old versions content to temporary table
	 */
	private function addVersionsContentSearchResult() {
		global $babDB;
		
		$arr = $this->getIndexedResults();

		if (empty($arr)) {
			return;
		}

		if (empty($arr['versions'])) {
			return;
		}

		$search = array();
		$relevance = array();

		

		foreach($arr['versions'] as $result) {
			$fullpath = bab_removeFmUploadPath($result['file']);
			$name = basename($fullpath);
			$path = dirname($fullpath);
			if( !empty($path) && '/' !== $path{mb_strlen($path) - 1}) 
			{
				$path .='/';
			}
	
			$path = dirname($path).'/';
			if (!preg_match('/^\d+,\d+,(.*)$/', $name, $match)) {
				bab_debug('Unexpected error, version file format', DBG_ERROR , 'Search');
				return;
			}

			$name = $match[1];
			

			$search[] = '(path=\''.$babDB->db_escape_string($path).'\' AND name=\''. $babDB->db_escape_string($name)."')";
			$relevance[$path.$name] = $result['relevance'];
		}

		$this->addContentSearchResultQueries($search, $relevance);
		
	}


	/**
	 * Add search into database to temporary table
	 */
	private function addDbSearchResult() {
		global $babDB;
		
		$query = '
			INSERT INTO filresults 
			SELECT 
				f.id,
				\'0\' relevance 
			FROM 
				'.BAB_FILES_TBL.' f 
			';

		$where = $this->criteria->tostring($this->getBackend('mysql'));
		if (!empty($where)) {
			$query .= ' WHERE '.$where;
		}

		return $babDB->db_query($query);

	}


	/**
	 * Add search into aditionnal metadata to temporary table
	 */
	private function addMetadataSearchResult() {
		global $babDB;

		if (null === $this->contentCriteria) {
			throw new Exception('there is no primary criteria, please use the method bab_SearchRealmFiles::setPrimaryCriteria()');
			$this->contentCriteria = $this->criteria;
		}


		$query = '
			INSERT INTO filresults 
			SELECT 
				f.id,
				\'500\' relevance 
			FROM 
				'.BAB_FM_FIELDSVAL_TBL.' m,
				'.BAB_FILES_TBL.' f 

			WHERE 
				f.id = m.id_file
		';

		// query on metadata
	
		$where = $this->contentCriteria->toString($this->getBackend('mysql'));
		if (!empty($where)) {
			$query .= ' AND '.$where;
		}

		// and test access rights

		$criteria = $this->getDefaultCriteria();

		$where = $criteria->toString($this->getBackend('mysql'));
		if (!empty($where)) {
			$query .= ' AND '.$where;
		}

		$babDB->db_query($query);
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

			case 'modifieddesc':
				$orderby = 'modified DESC';
				break;

			case 'relevance':
				$orderby = 'relevance DESC';
				break;

			default:
				$orderby = $babDB->backTick($this->sort_method).' ';
				break;
		}



		$query = '
			SELECT 

				f.id,
				f.name,
				f.id_owner,
				f.description,
				f.created,
				f.modified,
				f.path,
				f.bgroup collective,
				f.author,
				f.state,
				f.confirmed, 
				f.iIdDgOwner id_delegation,
				r.relevance  
			FROM 
				filresults r,
				'.BAB_FILES_TBL.' f

			WHERE
				f.id = r.id 

			
			GROUP BY r.id 
			ORDER BY '.$orderby.' 
		';


		return $babDB->db_query($query);
	}





	/**
	 * Get accessibles folders
	 * @return array
	 */
	private function getFolders() {
		$aIdFolder = array();
		$aDownload = bab_getUserIdObjects(BAB_FMDOWNLOAD_GROUPS_TBL);
		if(is_array($aDownload) && count($aDownload) > 0)
		{
			$aIdFolder = $aDownload;
		}
		
		$aManager = bab_getUserIdObjects(BAB_FMMANAGERS_GROUPS_TBL);
		if(is_array($aManager) && count($aManager) > 0)
		{
			$aIdFolder += $aManager;
		}

		return $aIdFolder;
	}





	/**
	 * get access rights verification criteria for folders
	 * @return	bab_SearchCriteria
	 */
	private function getFoldersCriteria() {

		$criteria = new bab_SearchInvariant;

		if(userHavePersonnalStorage()) {
			$criteria = $criteria
				->_AND_($this->collective->is('N'))
				->_AND_($this->id_owner->is($GLOBALS['BAB_SESS_USERID'])
			);
		}

		foreach($this->getFolders() as $iIdFolder) {
			$criterion = $this->getCollectiveFolderCriteria($iIdFolder);
			if ($criterion) {
				$criteria = $criteria
				->_OR_($criterion);
			}
		}

		return $criteria;
	}

	/**
	 * get access rights verification criteria for one collective folder
	 * @param	int		$iIdFolder
	 * @return	bab_SearchCriteria
	 */
	private function getCollectiveFolderCriteria($iIdFolder) {

		$criteria = new bab_SearchInvariant;

		$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
		if(!is_null($oFmFolder)) {
			$sRelativePath = (mb_strlen(trim($oFmFolder->getRelativePath())) > 0 ? $oFmFolder->getRelativePath() : $oFmFolder->getName() . '/');

			$criteria = $criteria
				->_AND_($this->id_owner->is($iIdFolder)
				->_AND_($this->path->startWith($sRelativePath)
			));
		} else {
			return false;
		}	

		return $criteria;
	}








	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		$criteria = parent::getSearchFormCriteria();

		$this->setFieldLessCriteria($criteria);
	
		return $criteria;
	}







}








/**
 * Custom result object
 * @package search
 */
class bab_SearchFilesResult extends bab_SearchSqlResult {

	/**
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();

		if ($record instanceOf bab_SearchRecord) {
			$record->ov_reference = bab_buildReference('filemanager', 'file', $record->id);
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
		$template = new bab_SearchRealmFiles_ResultTemplate($this, $count);
		return bab_printTemplate($template, 'search.html', 'files_results');
	}

}










class bab_SearchRealmFiles_ResultTemplate extends bab_SearchTemplate {

	private $count;
	private $resdir;
	private $pos;


	public function __construct($res, $count) {

		$this->res = $res;
		$this->count = $count;

		$this->pos = $res->key();

		$this->filename = bab_translate('Filename');
		$this->description = bab_translate('Description');
		$this->author = bab_translate('Author');
		$this->datem = bab_translate('Date');
		$this->t_folder = bab_translate('Folder');

		$baseurl = bab_url::request_gp();

		$this->ordernameurl = bab_url::mod($baseurl, 'field', 'name');
		$this->orderdateurl = bab_url::mod($baseurl, 'field', 'modified');
	}

	
	/**
	 * Template method
	 */
	public function getnextfil()
		{

		if($this->slicePage($this->res, 'files', $this->pos, $this->count))
			{
			$record = $this->res->current();
			$this->altbg = !$this->altbg;
			

		
			if ('Y' === $record->collective) {
				$sUploadPath = BAB_FileManagerEnv::getCollectivePath($record->id_delegation);
			}
			else  {
				$sUploadPath = BAB_FileManagerEnv::getPersonalPath($GLOBALS['BAB_SESS_USERID']);
			}

			$fullpath = $sUploadPath.$record->path.$record->name;

			
			$this->icon			= bab_SearchTemplate::getIcon($fullpath);


			$this->file 		= bab_toHtml($record->name);
			$this->fileid 		= $record->id;
			$this->update 		= bab_toHtml(bab_shortDate(bab_mktime($record->modified), true));
			$this->artauthor 	= bab_toHtml(bab_getUserName($record->author));
			$this->filedesc 	= bab_toHtml($record->description);
			$this->path 		= bab_toHtml($record->path);

			$this->fileurl 		= bab_toHtml($GLOBALS['babUrlScript'].'?tg=search&idx=files&id='.$record->id.'&w='.bab_SearchDefaultForm::highlightKeyword()); 
			
			if ('Y' === $record->collective) {
				$this->folderurl	= bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&idx=list&gr=Y&id='.$record->id_owner.'&path='.urlencode(removeEndSlah($this->path)));
				$this->folder	= bab_toHtml($this->path);
			} else {
				$this->folderurl	= bab_toHtml($GLOBALS['babUrlScript'].'?tg=fileman&idx=list&gr=N&id='.$record->id_owner.'&path='.urlencode(removeEndSlah($this->path)));
				$this->folder	= bab_translate("Private folder")."/".bab_toHtml($this->path);
			}

			$this->res->next();
			return true;
			}
		
		return false;
		}



}
