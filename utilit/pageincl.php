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


/**
 * Display the current page : head, metas, sections, body...
 * using page.html
 */
function printBody()
{
	class tpl
	{
		public $sitename;
		public $style;
		public $script;
		public $babSlogan;
		public $login;
		public $logurl;
		public $enabled;
		public $menuclass;
		public $menuattribute;
		public $menuurl;
		public $menutext;
		public $menukeys = array();
		public $menuvals = array();
		public $arrsectleft = array();
		private $nbsectleft = null;
		public $arrsectright = array();
		private $nbsectright = null;
		public $message;
		public $version;
		public $search;
		public $searchurl;
		public $sContent;
		public $styleSheet;

		private	$babLogoLT = null;
		private	$babLogoRT = null;
		private	$babLogoLB = null;
		private	$babLogoRB = null;
		private	$babBanner = null;
		private $babHeadStyleSheets = null;

		private $sitemapPosition = null;

		public function __construct()
		{
			global $babSiteName, $babSlogan, $babStyle;

			$babBody = bab_getInstance('babBody');

			$this->version		= isset($GLOBALS['babVersion']) ? $GLOBALS['babVersion'] : '';
			$this->sContent		= 'text/html; charset=' . bab_charset::getIso();

			$this->style = $babStyle;

			$this->script = $babBody->script;
			$this->home = bab_translate("Home");
			$this->homeurl = $GLOBALS['babUrlScript'];
			$this->tpowered = bab_translate("Powered by Ovidentia,");
			$this->tgroupware = bab_translate("Groupware Portal");
			$this->ttrademark = bab_translate('Ovidentia is a registered trademark by');
			if (bab_isUserLogged()) {
				$this->login = bab_translate("Logout");
				$this->logurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=login&amp;cmd=signoff');
			} else {
				// Variables redeclarations for IIS (bug or default config)
				if (!isset($GLOBALS['BAB_SESS_FIRSTNAME'])) $GLOBALS['BAB_SESS_FIRSTNAME'] = '';
				if (!isset($GLOBALS['BAB_SESS_LASTNAME'])) $GLOBALS['BAB_SESS_LASTNAME'] = '';
				$this->login = bab_translate("Login");
				$this->logurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=login&amp;cmd=signon');
			}

			$this->search = bab_translate("Search");
			$this->searchurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=search');

			if (!isset($GLOBALS['babMarquee']) || $GLOBALS['babMarquee'] == '') {
				$this->babSlogan = $babSlogan;
			} else {
				$this->babSlogan = $GLOBALS['babMarquee'];
			}
			$this->menukeys = array_keys($babBody->menu->items);
			$this->menuvals = array_values($babBody->menu->items);

			if (isset($GLOBALS['babHideMenu'])) {
				$tg = bab_rp('tg', '');
				$idx = bab_rp('idx', '');

				if ($tg && isset($GLOBALS['babHideMenu'][$tg]) && (count($GLOBALS['babHideMenu'][$tg]) == 0  || in_array($idx, $GLOBALS['babHideMenu'][$tg]))) {
					$this->menuitems = 0;
				} else {
					$this->menuitems = count($this->menukeys);
				}
			} else {
				$this->menuitems = count($this->menukeys);
			}

			$this->message = $babBody->message;
			$this->title = $babBody->title;
			$this->msgerror = $babBody->msgerror;


		}


		/**
		 * Template method for messages
		 */
		public function getNextMessage()
		{
			$babBody = bab_getInstance('babBody');

			if (list($key, $message) = each($babBody->messages))
			{
				$this->message = bab_toHtml($message);
				return true;
			}

			return false;
		}


		/**
		 * These getter are used to do some initialization stuff when some variables are
		 * accessed for the first time.
		 *
		 * @param string $propertyName
		 * @return mixed
		 */
		public function __get($propertyName)
		{
			$babBody = bab_getInstance('babBody');

			switch ($propertyName) {

				case 'content':

					$this->content = '';

					$debug = bab_getDebug();
					if (false !== $debug) {
						$this->content  .= $debug;
					}

					// if message not added to page by skin, add them to content
					while($this->getNextMessage())
					{
						$this->content .= sprintf('<div class="bab-page-message">%s</div>', $this->message);
					}

					$this->content .= $babBody->printout();
					return $this->content;

					// The values of nbsectleft and nbsectright are only valid after loadsections has been called.
				case 'nbsectleft':
					$this->loadsections();
					return $this->nbsectleft;

				case 'nbsectright':
					$this->loadsections();
					return $this->nbsectright;

				case 'babLogoLT':
					if (!isset($this->babLogoLT)) {
						$this->babLogoLT = bab_printTemplate($this, 'config.html', 'babLogoLT');
					}
					return $this->babLogoLT;

				case 'babLogoRT':
					if (!isset($this->babLogoRT)) {
						$this->babLogoRT = bab_printTemplate($this, 'config.html', 'babLogoRT');
					}
					return $this->babLogoRT;

				case 'babLogoLB':
					if (!isset($this->babLogoLB)) {
						$this->babLogoLB = bab_printTemplate($this, 'config.html', 'babLogoLB');
					}
					return $this->babLogoLB;

				case 'babLogoRB':
					if (!isset($this->babLogoRB)) {
						$this->babLogoRB = bab_printTemplate($this, 'config.html', 'babLogoRB');
					}
					return $this->babLogoRB;

				case 'babBanner':
					if (!isset($this->babBanner)) {
						$this->babBanner = bab_printTemplate($this, 'config.html', 'babBanner');
					}
					return $this->babBanner;

				case 'babHeadStyleSheets':
					if (!isset($this->babHeadStyleSheets)) {
						foreach ($babBody->styleSheet as $sheet) {
							$this->babHeadStyleSheets .= '<link rel="stylesheet" type="text/css" href="' . bab_toHtml($GLOBALS['babInstallPath'] . 'styles/' . $sheet) . '" />' . "\n";
						}
						$babBody->styleSheet = array();
					}
					return $this->babHeadStyleSheets;

				case 'sitemapPosition':
					if (null === $this->sitemapPosition)
					{
						$func = bab_functionality::get('Ovml/Function/SitemapPosition');
						$this->sitemapPosition = $func->toString();
					}
					return $this->sitemapPosition;


				case 'imageUrl':

					$head = bab_getInstance('babHead');
					if ($imageUrl = $head->getImageUrl())
					{
						return bab_toHtml($imageUrl);
					}
					return '';


				case 'canonicalUrl':

					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($canonicalUrl = $sitemapItem->getCanonicalUrl())
						{
							return bab_toHtml($canonicalUrl);
						}
					}

					$head = bab_getInstance('babHead');
					if ($canonicalUrl = $head->getCanonicalUrl())
					{
						return bab_toHtml($canonicalUrl);
					}
					return '';


				case 'pageTitle':
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($title = $sitemapItem->getPageTitle(true))
						{
							return bab_toHtml($title);
						}
					}

					// no sitemap node, use title provided by script
					$head = bab_getInstance('babHead');
					if ($title = $head->getTitle())
					{
						return bab_toHtml($title);
					}

					// use the sitemap root node page title
// 					if ($root = bab_siteMap::getVisibleRootNodeSitemapItem()) {
// 						return bab_toHtml($root->getPageTitle());
// 					}

					return $babBody->title;



				case 'pageDescription':
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($description = $sitemapItem->getPageDescription(true))
						{
							return bab_toHtml($description);
						}
					}

					$head = bab_getInstance('babHead');
					if ($description = $head->getDescription())
					{
						return bab_toHtml($description);
					}

					if ($root = bab_siteMap::getVisibleRootNodeSitemapItem()) {
						return bab_toHtml($root->getPageDescription());
					}

					return '';



				case 'pageKeywords':
				case 'sitemapPageKeywords':
					if ( null !== $sitemapItem = $this->getSitemapItem() ) {
						if ($keywords = $sitemapItem->getPageKeywords(true))
						{
							return bab_toHtml($keywords);
						}
					}

					$head = bab_getInstance('babHead');
					if ($keywords = $head->getKeywords())
					{
						return bab_toHtml($keywords);
					}

					if ($root = bab_siteMap::getVisibleRootNodeSitemapItem()) {
						return bab_toHtml($root->getPageKeywords());
					}

					return '';

				default:
					return $this->$propertyName;
			}
		}

		/**
		 *
		 * @return bab_sitemapItem
		 */
		private function getSitemapItem()
		{

			if (($rootNode = bab_siteMap::getFromSite())
					&& ($currentNodeId = bab_Sitemap::getPosition())
					&& ($currentNode = $rootNode->getNodeById($currentNodeId))
					&& ($sitemapItem = $currentNode->getData()) ) {
				// if on a positioned sitemap node
				return $sitemapItem;
			}

			return null;
		}


		/**
		 * Isset has to be overriden as well for some variables.
		 *
		 * @param string $propertyName
		 * @return bool
		 */
		public function __isset($propertyName)
		{
			switch ($propertyName) {

				case 'content':
				case 'nbsectleft':
				case 'nbsectright':
				case 'babLogoLT':
				case 'babLogoRT':
				case 'babLogoLB':
				case 'babLogoRB':
				case 'babBanner':
				case 'babHeadStyleSheets':
				case 'sitemapPosition':
				case 'sitemapPageKeywords':
				case 'pageKeywords':
				case 'pageDescription':
				case 'pageTitle':
				case 'canonicalUrl':
				case 'imageUrl':
					return true;
			}
			return false;
		}


		public function getNextMenu()
		{
			$babBody = bab_getInstance('babBody');

			static $i = 0;
			if ($i < $this->menuitems) {
				if (!strcmp($this->menukeys[$i], $babBody->menu->curItem)) {
					$this->menuclass = 'BabMenuCurArea';
				} else {
					$this->menuclass = 'BabMenuArea';
				}

				$this->menutext = $this->menuvals[$i]['text'];
				if( $this->menuvals[$i]['enabled'] == false) {
					$this->enabled = 0;
					if (!empty($this->menuvals[$i]['attributes'])) {
						$this->menuattribute = $this->menuvals[$i]['attributes'];
					} else {
						$this->menuattribute = "";
					}
				} else {
					$this->enabled = 1;
					if (!empty($this->menuvals[$i]['attributes'])) {
						$this->menuattribute = $this->menuvals[$i]['attributes'];
					} else {
						$this->menuattribute = "";
					}
					$this->menuurl = bab_toHtml($this->menuvals[$i]['url']);
				}
				$i++;
				return true;
			} else {
				return false;
			}
		}


		private function loadsections()
		{
			$babBody = bab_getInstance('babBody');

			if (null !== $this->nbsectleft) {
				return;
			}

			$babBody->loadSections();

			$this->nbsectleft = 0;
			$this->nbsectright = 0;
			foreach($babBody->sections as $sec)
			{
				if ($sec->isVisible())
				{
					if ($sec->getPosition() == 0)
					{
						$this->arrsectleft[$this->nbsectleft] = $sec;
						$this->nbsectleft++;
					}
					else
					{
						$this->arrsectright[$this->nbsectright] = $sec;
						$this->nbsectright++;
					}
				}
			}
		}


		public function getNextSectionLeft()
		{
			$this->loadsections();
			static $i = 0;
			if( $i < $this->nbsectleft)
			{
				$sec = $this->arrsectleft[$i];
				$this->sectionleft = $sec->printout();
				$i++;
				return true;
			}
			else
				return false;
		}

		public function getNextSectionRight()
		{
			$this->loadsections();
			static $i = 0;
			if( $i < $this->nbsectright)
			{
				$sec = $this->arrsectright[$i];
				$this->sectionright = $sec->printout();
				$i++;
				return true;
			}
			else
				return false;
		}


		public function getNextStyleSheet()
		{
			$babBody = bab_getInstance('babBody');

			list(,$this->styleSheet) = $babBody->getnextstylesheet();
			if ($this->styleSheet) {
				$this->styleSheet = bab_getStaticUrl().$GLOBALS['babInstallPath'] . 'styles/' . bab_toHtml($this->styleSheet);
				return true;
			}
			return false;
		}
	}

	// we make sure that the sitemap is created before final processing of the page
	// because sitemap reconstruction errors are hidden if the reconstruction process is done in an eval
	bab_sitemap::getFromSite();

	$temp = new tpl();
	echo bab_printTemplate($temp, 'page.html', '');
}


