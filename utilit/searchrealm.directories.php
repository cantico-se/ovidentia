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
 * Directories search realm
 * Search in ovidentia users (group directories) and in database directories
 *
 * @package	search
 */
class bab_SearchRealmDirectories extends bab_SearchRealm {

	/**
	 * search is access rights dependant or not
	 * @see bab_SearchRealmDirectories::getDefaultCriteria()
	 * @var bool
	 */
	private $access_rights = false;

	/**
	 * 
	 * @var  int | null
	 */
	private $search_id_directory = null;

	/**	
	 * @var string
	 */
	private $primary_search = null;


	/**
	 * Set directory to search
	 * @param	int	$id_directory		specify an id_directory to search
	 * @return	bab_SearchRealmDirectories
	 */
	public function setDirectory($id_directory) {
		
		$arr = bab_getUserDirectories();

		if (!isset($arr[$id_directory])) {
			throw new Exception('This directory is not accessible');
		}

		$this->search_id_directory = (int) $id_directory;
		return $this;
	}


	/**
	 * Set primary search, used to do a global search with relevance
	 * If the exact lastname is found in search result it will be ranked first when result are sorted by default ordering method
	 *
	 * @param	string	$keyword
	 * @return	bab_SearchRealmDirectories
	 */
	public function setPrimarySearch($keyword) {

		$keyword = trim($keyword);

		if ('' !== $keyword) {
			$this->primary_search = $keyword;
		}

		return $this;
	}



	/**
	 * @return 	string
	 */
	public function getName() {
		return 'directories';
	}

	/**
	 * Get Title of functionality throw sitemap API
	 * @return 	string
	 */
	public function getDescription() {
		return bab_translate('Directories');
	}

	/**
	 * Get Url of functionality throw sitemap API
	 * @return 	string | null
	 */
	public function getLink() {
		return $GLOBALS['babUrlScripts'].'?tg=directory';
	}

	/**
	 * Directory entries are sorted by custom field
	 * all possibles fields are proposed
	 *
	 * @return	array
	 */
	public function getSortMethods() {
		$fields = $this->getFields();
		$return = array();
		foreach($fields as $field) {
			$return[$field->getName()] = $field->getDescription();
			$return[$field->getName().'desc'] = $field->getDescription().' '.bab_translate('descending');
		}

		return $return;
	}

	/**
	 * Search locations
	 * @todo add ldap directory search location
	 * @return array
	 */
	public function getAllSearchLocations() {

		return array(
			'dbgroup' 		=> bab_translate('Users groups directories'),
			'dbdirectory'	=> bab_translate('database directories')
		);
	}



	/**
	 * Get directory field from name or description or ID
	 * @param	mixed	$keyword			name or ID (from table bab_dbdir_fieldsextra)
	 * @return	bab_searchField | null		return null if nothing found
	 */
	public function getDirField($keyword) {

		global $babDB;


		if (is_numeric($keyword)) {
			$id_field = (int) $keyword;

			$property = 'babdirf'.$id_field;
			if (isset($this->$property)) {
				return $this->$property;
			}
		}

		foreach($this->getFields() as $field) {
			if ($keyword === $field->getName() || $keyword === $field->getDescription()) {
				return $field;
			}
		}

		return null;
	}







	/**
	 * output fields for search result
	 * contain fields proposed in configuration
	 * and common fields : id, id_user, id_directory, id_group
	 *
	 *
	 * @return array
	 */
	public function getFields() {

		static $return = null;

		if (!$return) {

			include_once dirname(__FILE__).'/dirincl.php';

			$return = array(
				$this->createField('ov_reference'	, bab_translate('Ovidentia reference'))					->virtual(true),
				$this->createField('id'				, bab_translate('directory entry numeric identifier'))	->searchable(false)->setTableAlias('e'),
				$this->createField('id_user'		, bab_translate('user numeric identifier'))				->searchable(false)->setTableAlias('e'),
				$this->createField('id_directory'	, bab_translate('directory numeric identifier'))		->searchable(false)->setTableAlias('e')
			);

			$sd = array_keys($this->getSearchableDirectories());

			foreach(bab_getDirectoriesFields($sd) as $id_field => $arr) {
				
				if (BAB_DBDIR_ENTRIES_TBL === $arr['table']) {
					
					$field = $this->createField($arr['name'], $arr['description']);
					$field->setTableAlias('e');
				}

				if (BAB_DBDIR_ENTRIES_EXTRA_TBL === $arr['table']) {

					$field = $this->createField($arr['name'], $arr['description']);
					$field->setTableAlias('extra_'.$arr['name']);
					$field->setRealName('field_value');
				}

				if ('jpegphoto' === $field->getName()) {
					$field->virtual(false);
				}

				$return[] = $field;
			}
		}

		return $return;
	}

	/**
	 * Test if search realm is accessible
	 * @return bool
	 */
	public function isAccessValid() {
		return 0 < count(bab_getUserDirectories());
	}


	/**
	 * Get default criteria for directories, do the request based on access rights
	 * @return	bab_SearchCriteria
	 */
	public function getDefaultCriteria() {

		$this->access_rights = true;

		$entry_directories = array();
		foreach($this->getSearchableDirectories() as $arr) {
			$entry_directories[$arr['entry_id_directory']] = $arr['entry_id_directory'];
		}

		$crit = $this->id_directory->in($entry_directories);

		return $crit;
	}




	
	/**
	 * Return true if one of the searchable directories is the ovidentia directory
	 * @return bool
	 */
	private function containAllRegisteredUsers() {

		$searchable = $this->getSearchableDirectories();

		if (null !== $this->search_id_directory && isset($searchable[$this->search_id_directory]['id_group'])) {
			return BAB_REGISTERED_GROUP === (int) $searchable[$this->search_id_directory]['id_group'];
		}

		foreach($searchable as $arr) {
			if (BAB_REGISTERED_GROUP == $arr['id_group']) {
				return true;
			}
		}

		return false;
	}



	/**
	 * Get all directories to search
	 * if default criteria is initialized, this method will use access right filter for the return value
	 * directories are filtered with search locations
	 *
	 * @return array
	 */
	private function getSearchableDirectories() {

		include_once dirname(__FILE__).'/dirincl.php';
		$all = getUserDirectories($this->access_rights);

		// filter with allowed search location
		$locations = $this->getSearchLocations();
		if (2 > count($this->search_locations)) {
			foreach($all as $key => $directory) {
				if ($directory['id_group'] > 0 && !isset($locations['dbgroup'])) {
					unset($all[$key]);
				} 

				if (0 == $directory['id_group'] && !isset($locations['dbdirectory'])) {
					unset($all[$key]);
				}
			}
		}


		if (null === $this->search_id_directory) {
			return $all;
		} else {

			if (!isset($all[$this->search_id_directory])) {
				return array();
			}

			return array(
				$this->search_id_directory => $all[$this->search_id_directory] 
			);
		}
	}


	/**
	 * Get searchable groups 
	 * return list of id_group found in searchable directories
	 * @return array
	 */
	private function getSearchableGroups() {
		
		$return = array();

		foreach($this->getSearchableDirectories() as $arr) {
			if ($arr['id_group'] > 0) {
				$return[$arr['id_group']] = $arr['id_group'];
			}
		}
		
		return $return;
	}




	/**
	 * Get a list of left join to use for values contained in custom fields
	 * @return array
	 */
	private function getAdditionalTables() {
		global $babDB;
		$return = array();

		foreach($this->getFields() as $field) {
			if ('extra_' === mb_substr($field->getTableAlias(),0,6)) {
				$id_field = (int) mb_substr($field->getName(), strlen('babdirf'));

				$return[] = ' 
				LEFT JOIN '.BAB_DBDIR_ENTRIES_EXTRA_TBL.' AS '.$field->getTableAlias().' # '.$field->getDescription().'
					ON  '.$field->getTableAlias().'.id_entry = e.id 
					AND '.$field->getTableAlias().'.id_fieldx='.$babDB->quote($id_field).' 
				';

				// AND e.id_directory IN('.$babDB->quote().')
			}
		}

		return $return;
	}



	/**
	 * get the column display settings for a search result in displayed in list
	 * Each directory have setting for displayed columns
	 * Ovidentia have a default configuration to display columns when a search is made on multiples directories
	 * This method will return a list of fields for the search context
	 * @return array
	 */
	public function getColumnsSettings() {

		include_once dirname(__FILE__).'/dirincl.php';
		return bab_getDirectorySearchHeaders($this->search_id_directory);
	}





	/**
	 * Search in directories from query
	 * @param	bab_SearchCriteria	$criteria
	 *
	 * @return 	bab_SearchResult
	 */
	public function search(bab_SearchCriteria $criteria) {


		$locations = $this->getSearchLocations();

		if (!isset($locations['dbgroup']) && !isset($locations['dbdirectory'])) {
			throw new Exception('No valid search location');
		}


		global $babDB;
		$mysql = $this->getBackend('mysql');

		$result = new bab_SearchDirectoriesResult;
		$result->setRealm($this);

		

		$req = "SELECT ";

		$fields = array();
		foreach($this->getFields() as $field) {

			if ($field->virtual()) {
				continue;
			}

			$fn = $mysql->getFieldAlias($field);
			if ($field->getRealName() !== $field->getName()) {
				$fn .= ' AS '.$field->getName();
			}

			$fields[] = $fn;
		}

		$req .= implode(", \n", $fields);

		if (null !== $this->primary_search) {
			$req .= ", ABS(STRCMP(e.sn,". $babDB->quote($this->primary_search) .")) AS relevance ";
		}
		
		$req .= "	 
		FROM `".BAB_DBDIR_ENTRIES_TBL."` e

		";
		if( $this->containAllRegisteredUsers())
			{
			$req .= " LEFT JOIN ".BAB_USERS_TBL." dis ON dis.id = e.id_user AND dis.disabled='0' 
				";
			}
		else
			{
			$req .= " LEFT JOIN ".BAB_USERS_GROUPS_TBL." u ON u.id_object = e.id_user 
					AND u.id_group IN  (".$babDB->quote($this->getSearchableGroups()).") 
					LEFT JOIN ".BAB_USERS_TBL." dis ON dis.id = u.id_object AND dis.disabled='0' 
				";
			}

		// add additional left join if list of fields use custom fields
		foreach($this->getAdditionalTables() as $leftjoin) {
			$req .= $leftjoin."\n";
		}
		
		$req .= $mysql->getWhereClause($criteria);

		if(false === $this->containAllRegisteredUsers()) {
			$req .= ' AND dis.id IS NOT NULL';
		}


		if (null !== $this->primary_search) {
			$sort = 'relevance ASC, sn ASC, givenname ASC';
		} else {
			$sort = 'sn ASC, givenname ASC';
		}

		
		if (null !== $this->sort_method) {

			$sortcol = $this->sort_method;
			$order_type = 'ASC';
			if ('desc' === mb_substr($sortcol, -4)) {
				$sortcol = mb_substr($sortcol, 0, -4);
				$order_type = 'DESC';
			}

			switch($sortcol) {
				case 'sn':
					break;
				default:
					$sort = $babDB->backTick($sortcol).' '.$order_type;
			}
		}


		$req .= ' ORDER BY '.$sort;
		
		bab_debug($req, DBG_INFO, 'Search');

		$result->setRessource($babDB->db_query($req));
		return $result;
	}


	/**
	 * Get search form as HTML string
	 * @return string
	 */
	public function getSearchFormHtml() {

		$html = parent::getSearchFormHtml();

		$template = new bab_SearchRealmDirectories_SearchTemplate($this);
		$html .= bab_printTemplate($template, 'search.html', 'directories_form');

		return $html;
	}


	/**
	 * get a criteria from a search query made with the form generated with the method <code>getSearchFormHtml()</code>
	 * @see bab_SearchRealm::getSearchFormHtml()
	 * @return bab_SearchCriteria
	 */
	public function getSearchFormCriteria() {
		// default serach fields
		$criteria = bab_SearchDefaultForm::getCriteria($this);

		$g_directory = (int) bab_rp('g_directory');
		if ($g_directory) {
			$this->setDirectory($g_directory);
		}

		$select = bab_rp('dirselect');
		$values = bab_rp('dirfield');

		foreach($select as $key => $customfield) {
			if (isset($this->$customfield) && !empty($values[$key])) {
				$criteria = $criteria->_AND_($this->$customfield->contain($values[$key]));
			}
		}

		return $criteria;
	}






	

}








/**
 * Custom result object to add jpegphoto support to the record
 * @package search
 */
class bab_SearchDirectoriesResult extends bab_SearchSqlResult {

	/**
	 * Overwrite the current method on this object to manage the custom value for the field jpegphoto
	 * The <code>jpegphoto</code> field does not exits in table <code>bab_dbdir_entries</code>
	 * @return bab_SearchRecord | false
	 */
	public function current() {
		$record = parent::current();
		
		if ($record instanceOf bab_SearchRecord) {
			
			$record->ov_reference = bab_buildReference('dbdirectories', 'entry', $record->id);
			
			include_once dirname(__FILE__).'/dirincl.php';
			$record->jpegphoto = new bab_dirEntryPhoto($record->id);
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
		$template = new bab_SearchRealmDirectories_ResultTemplate($this->getRealm(), $this, $count);
		return bab_printTemplate($template, 'search.html', 'directories_results');
	}
}







class bab_SearchRealmDirectories_SearchTemplate extends bab_SearchTemplate {

	private $directories = null;
	private $fields = null;

	public function __construct($realm) 
		{
		global $babDB;

		$this->directories = bab_getUserDirectories();

		foreach($realm->getFields() as $field) {
			if ($field->searchable()) {
				$this->fields[] = $field;
			}
		}

		$this->t_filter_by_directory = bab_translate('Filter search results by a directory :');
		$this->t_all = bab_translate('All');
		$this->t_search_in_specific_field = bab_translate('Search in a specific field :');

		}


	/**
	 * Template method
	 */
	public function selectname() 
		{

		if( list($i,$field) = each($this->fields))
			{
			$this->selindex = $i;
			$this->name = bab_toHtml($field->getName());
			$this->description = bab_toHtml($field->getDescription());
			$this->descriptionJs = bab_toHtml($field->getDescription(), BAB_HTML_JS);

			$this->isnotcustom = 0 !== mb_strpos($field->getName(), 'babdirf');

			$dirselect = bab_rp('dirselect');
			$dirfield = bab_rp('dirfield');

			$this->fieldvalue = isset($dirfield[$this->j]) ? bab_toHtml($dirfield[$this->j]) : '';
			if ( isset($dirselect[$this->j]) && $dirselect[$this->j] == $field->getName())
				$this->selected = "selected";
			else	
				$this->selected = false;

			return true;
			}
		else
			{
			reset($this->fields);
			return false;
			}
		}






	/**
	 * Template method
	 */
	public function getnextfield()
		{
		static $k = 0;
		if( $k < $this->countfieldsfromdir)
			{
			$this->fieldnamefromdir = "babdirf".$this->tblxfields[$k]['name'] ;
			$this->name = "babdirf".$this->tblxfields[$k]['name'];
			$this->description = $this->tblxfields[$k]['description'];
			$this->fieldindex = $k;
			if ( isset($this->fields['dirselect['.$this->j.']']) && $this->fields['dirselect['.$this->j.']'] == $this->name)
				{
				$this->selected = "selected";
				}
			else
				{
				$this->selected = false;
				}
			$k++;
			return true;
			}
		else
			{
			$k = 0;
			return false;
			}
		}


	/**
	 * Template method
	 */
	public function getnextfieldtosearch() 
		{
		static $j = 0;
		if( $j < FIELDS_TO_SEARCH)
			{
			$this->fieldcounter = $j;

			$dirfield = bab_rp('dirfield');
			
			$this->value = isset($dirfield[$j]) ? $dirfield[$j] : '';
			$this->j = $j;
			$j++;
			return true;
			}
		else
			{
			$j = 0;
			return false;}
		}


	/**
	 * Template method
	 */
	public function getnextdir() 
		{
		global $babDB;
		if(list(, $arr) = each($this->directories))
			{
			
			$this->topicid = $arr['id'];
			$this->selected = bab_rp('g_directory') == $arr['id'];

			$this->topictitle = bab_toHtml($arr['name']);

			$req = "select df.id, dfd.name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." dfd left join ".BAB_DBDIR_FIELDSEXTRA_TBL." df ON df.id_directory=dfd.id_directory and df.id_field = ( ".BAB_DBDIR_MAX_COMMON_FIELDS." + dfd.id ) where df.id_directory='".(($arr['id_group']==0) ? $arr['id'] : 0)."'";
			$res = $babDB->db_query($req);

			$lk = 0;
			while ($arr = $babDB->db_fetch_array($res))
				{
				$tblxn[$lk] = $arr['id'];
				$tblxd[$lk] = translateDirectoryField($arr['name']);
				$lk++;
				}

			$this->tblxfields = array();
			$this->countfieldsfromdir = 0;
			if( $lk > 0 )
				{
				
				$fliped = array_flip($tblxd);
				bab_sort::ksort($fliped);
				$lk= 0;
				foreach($fliped as $value => $key)
					{
					$this->tblxfields[$lk]['name'] = $tblxn[$key];
					$this->tblxfields[$lk]['description'] = $value;
					$lk++;
					}
				$this->countfieldsfromdir = count($this->tblxfields);
				}
			

			return true;
			}
		else
			{
			reset($this->directories);
			return false;
			}
		}
}




class bab_SearchRealmDirectories_ResultTemplate extends bab_SearchTemplate {

	private $dirrealm;
	private $resdir;
	private $pos;


	public function __construct($realm, $res, $count) {
		
		$this->dirrealm = $realm;
		$this->resdir = $res;
		$this->count = $count;

		$this->pos = $res->key();
	}


	

	/**
	 * Template method
	 */
	public function getnextdirheader() 
		{
		static $fields = null;

		if (null === $fields) 
			{
			$fields = $this->dirrealm->getColumnsSettings();
			}
			
		if( list($name , $description) = each($fields))
			{
			$this->t_name = bab_toHtml($description);
			$this->ordercmd = bab_toHtml($name);
			$this->alloworder = 'jpegphoto' !== $name;
			return true;
			}

		return false;
		}



	/**
	 * Template method
	 */
	public function getnextdirfield()
		{
		static $fields = null;

		$record = $this->dirfields;

		if (null === $fields) {
			// current record
			$fields = $record->getRealm()->getColumnsSettings();
		}


		if( list($name , $description) = each($fields))
			{
			$this->name = $name;
			$this->dirvalue = isset($record->$name) ? bab_toHtml($record->$name) : '';
			$this->vcard_classname = '';
			$this->dirurl = false;
			
			switch ($this->name)
				{
				case 'sn':
					$this->dirurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=directories&id=".$record->id."&w=".bab_SearchDefaultForm::highlightKeyword());	
					$this->popup = true;
					break;
				case 'givenname':
					$this->dirurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=directories&id=".$record->id."&w=".bab_SearchDefaultForm::highlightKeyword());	
					$this->popup = true;
					break;
				case 'email':
					$this->vcard_classname = 'email';
					$this->dirurl = 'mailto:'.$this->dirvalue;
					$this->popup = false;
					break;

				case 'btel':
				case 'htel':
				case 'mobile':
					$this->vcard_classname = 'tel';
					break;

				case 'title':
					$this->vcard_classname = 'title';
					break;

				case 'organisationname':
					$this->vcard_classname = 'org';
					break;

				case 'departmentnumber':
					$this->vcard_classname = 'role';
					break;

				case 'jpegphoto':
					$this->vcard_classname = 'photo';
					$src = (string) $record->$name;

					$this->popup = true;
					$this->dirurl = bab_toHtml($src);

					if ($thumb = @bab_functionality::get('Thumbnailer')) {

						$data = $record->$name->getData();
						if ($data) {

							$thumb->setSourceBinary(
								$data,
								$record->$name->lastUpdate()
							);

							$src = $thumb->getThumbnailOrDefault(200, 40);
						}
					}

					$this->dirvalue = bab_sprintf(
						'<img src="%s" height="40" alt="%s" />',
						bab_toHtml($src),
						bab_toHtml(bab_composeUserName($record->givenname, $record->sn))
					);
					break;

				default:
					
					$this->popup = false;
					break;
				}

			return true;
			}


		reset($fields);
		return false;
		}



	/**
	 * Template method
	 * @see self::getnextdirfield()
	 */
	function getnextdir()
		{
		if($this->slicePage($this->resdir, 'directories', $this->pos, $this->count))
			{	
			$this->altbg = !$this->altbg;
			$this->dirfields = $this->resdir->current();

			$this->sn 			= bab_toHtml($this->dirfields->sn);
			$this->givenname 	= bab_toHtml($this->dirfields->givenname);
			
			$this->resdir->next();
			return true;
			}
		
		return false;
		}
}
