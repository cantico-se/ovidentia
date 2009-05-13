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
 * All classes extended from this class use a workaround for search filters
 * criteria are not used, theses realms work only with the default form of the Ovidentia search engine
 *
 * @deprecated Use a custom search realm object for each addon
 */
abstract class bab_SearchRealmAddon extends bab_SearchRealm {

}




/**
 * Convert old addon search api to a regular search realm
 * @deprecated Use a custom search realm object for each addon
 */
class bab_addonsSearch
	{
	public $tabSearchAddons = array();
	public $tabLinkAddons = array();
	public $titleAddons = array();

	public function __construct()
		{
		global $babDB;
		
		include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
		
		$searchinfos = bab_callAddonsFunction('searchinfos');
		
		foreach($searchinfos as $id => $arr) {

			$title = $arr['addon_name'];
			$func_results = $arr['return_value'];

			if (is_array($arr['return_value'])) {
				$this->titleAddons[$id] = $arr['return_value'][0];
			} else {
				$this->titleAddons[$id] = $arr['return_value'];
			}
			
			if (is_array($arr['return_value'])) {
				list($text,$link) = $arr['return_value'];
				}
			else {
				$text = $arr['return_value'];
				}

			if (is_string($text))
				{
				$func_results = $title."_searchresults";
				if (function_exists($func_results))
					{
					$this->func_results[$id] = $func_results;
					$this->tabSearchAddons[$id] = $text;
					}
				if (isset($link))
					{
					$this->tabLinkAddons[$id] = $text;
					$this->querystring[$id] = $link;
					}
				}
			}
		}


	public function createRealm($id_addon) {

		$classname = 'bab_SearchRealmAddon'.$id_addon;

		if (!isset($this->func_results[$id_addon])) {
			return false;
		}

		

		if (!class_exists($classname)) {
			eval('
				class bab_SearchRealmAddon'.$id_addon.' extends bab_SearchRealmAddon {

					public function getName() {
						return \'addon/'.$id_addon.'\';
					}

					public function getDescription() {
						return \''.addslashes($this->titleAddons[$id_addon]).'\';
					}

					public function getSortMethods() {
						return array(
							\'none\' => \'none\'
						);
					}

					public function getAllSearchLocations() {
						return array(
							\'none\' => \'none\'
						);
					}

					public function getFields() {
						return array(
							
							$this->createField(\'content\'		, bab_translate(\'Content\'))		->virtual(true),
							$this->createField(\'linktitle\'	, bab_translate(\'Link title\'))	->virtual(true),
							$this->createField(\'linkurl\'		, bab_translate(\'Link url\'))		->virtual(true),
							$this->createField(\'popuptitle\'	, bab_translate(\'Popup title\'))	->virtual(true),
							$this->createField(\'popupurl\'		, bab_translate(\'Popup url\'))		->virtual(true)
						);
					}

					public function isAccessValid() {
						return true;
					}


					public function search(bab_SearchCriteria $criteria) {

						$obj = bab_getInstance(\'bab_addonsSearch\');
						$result = array();
						$total = 0;

						while($arr = $obj->callSearchFunction(\''.$id_addon.'\')) {

							$result[] = array(
								\'content\' 	=> $arr[0],
								\'linktitle\' 	=> !empty($arr[2]) ? $arr[2][1] : null,
								\'linkurl\' 	=> !empty($arr[2]) ? $arr[2][0] : null,
								\'popuptitle\' 	=> !empty($arr[3]) ? $arr[3][1] : null,
								\'popupurl\' 	=> !empty($arr[3]) ? $arr[3][0] : null
							);

							$total = $arr[1];
						}

						$navpos = bab_rp(\'navitem\') == \''.addslashes(bab_addonsSearch::getItemFromAddon($id_addon)).'\' ? bab_rp(\'navpos\', 0) : 0;

						if ($navpos > 0) {
							$new_size = -1 * ($navpos + count($result));
							$result = array_pad($result, $new_size, array());
						}

						$result = array_pad($result, $total, array());

						$result = new bab_SearchAddonResult($result);
						$result->setRealm($this);

						return $result;
					}
				}
			');
		}

		

		return bab_getInstance($classname);

	}







	/**
	 * item definition for searching in an addon
	 * @param	int		$id_addon
	 * @return string
	 */
	public static function getItemFromAddon($id_addon) {
		return 'addon/'.$id_addon;
	}

	/**
	 * get addon from item or false if item is not an addon
	 * @return int		id_addon
	 */
	public static function getAddonFromItem($item) {

		if (empty($item)) {
			return false;
		}

		if (false === mb_strpos($item, 'addon/')) {
			return false;
		}

		$trail = mb_substr($item, mb_strlen('addon/'));

		if (!is_numeric($trail)) {
			$addon = bab_getAddonInfosInstance($trail);

			if (false === $addon) {
				return false;
			}

			return $addon->getId();
		}

		return (int) $trail;
	}




	function getsearcharray($item)
		{
		if (empty($item))
			{
			return $this->tabSearchAddons;
			}
		elseif ($id = self::getAddonFromItem($item))
			{
			if (isset($this->tabSearchAddons[$id])) {
				return array($id => $this->tabSearchAddons[$id]);	
				}
			}
		}

	function setSearchParam($q1, $q2, $option, $nb_result)
		{
		$this->q1 = $q1;
		$this->q2 = $q2;
		$this->option = $option;
		$this->nb_result = $nb_result;
		}


	function callSearchFunction($id)
		{
		if (!isset($this->func_results[$id])) {
			return null;
		}


		if (!isset($this->i[$id]))
			$this->i[$id] = 0;

		if ($this->i[$id] >= $this->nb_result)
			return false;
		$this->i[$id]++;

		$navpos = bab_rp('navitem') == $this->getItemFromAddon($id) ? bab_rp('navpos', 0) : 0;
		
		bab_setAddonGlobals($id);
		$func = $this->func_results[$id];
		return $func($this->q1, $this->q2, $this->option, $navpos, $this->nb_result);
		}
	}










/**
 * @deprecated Use a custom result object for each addon
 */
class bab_SearchAddonResult extends bab_searchArrayResult {

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
		$i = 0;
		$return = '';

		while ($this->valid()) {

			if ($i >= $count) {
				break;
			}

			$i++;
	
			$record = $this->current();

			if ($record->linkurl) {
				$link = bab_sprintf('<a href="%s">%s</a>', $record->linkurl, $record->linktitle);
			} else {
				$link = '';
			}

			if ($record->popupurl) {
				$popup = bab_sprintf('<a href="%s" onclick="bab_popup(this.href);return false;">%s</a>', $record->popupurl, $record->popuptitle);
			} else {
				$popup = '';
			}
			
			$return .= bab_sprintf('
				<div class="bab_SearchRecord">
					%s<br />
					<p><span class="bottom">%s %s</span></p>
				</div>', 
				$record->content, $link, $popup
			);

			

			$this->next();
		}

		return $return;
	}
}