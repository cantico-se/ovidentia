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
require_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/omlincl.php';




/**
 * Base class for all sitemap-related ovml containers.
 *
 */
abstract class Ovml_Container_Sitemap extends Func_Ovml_Container
{
	public $IdEntries = array();
	public $index;
	public $count;
	public $data;

	/**
	 * @var bab_siteMap $sitemap	The sitemap the container is working on.
	 */
	protected $sitemap;

	/**
	 *
	 * @var string
	 */
	protected $sitemap_name;


	/**
	 * @var int $limit				The max number of elements to return.
	 */
	protected $limitOffset = 0;
	protected $limitRows = null;


	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$limit = $ctx->get_value('limit');
		if (is_string($limit)) {
			$limits = explode(',', $limit);
			if (count($limits) === 1) {
				$this->limitRows = $limit;
			} else {
				$this->limitOffset = $limits[0];
				$this->limitRows = $limits[1];
			}
		}

		$this->idx += $this->limitOffset;

		$sitemap = $ctx->get_value('sitemap');

		if (false === $sitemap) {
			global $babBody;
			$this->sitemap_name = $babBody->babsite['sitemap'];
			$this->sitemap = bab_siteMap::getByUid($this->sitemap_name);
			if (!isset($this->sitemap)) {
				$this->sitemap = bab_siteMap::get();
			}
		} else {
			$this->sitemap = bab_siteMap::getByUid($sitemap);

			if (null === $this->sitemap) {
				trigger_error(sprintf('incorrect attribute in %s#%s sitemap="%s"', (string) $ctx->debug_location, get_class($this), $sitemap));
				return;
			}

			$this->sitemap_name = $sitemap;
		}

	}

	/**
	 * (non-PHPdoc)
	 * @see utilit/Func_Ovml_Container#getnext()
	 */
	public function getnext()
	{
		if ($this->idx >= $this->count || (isset($this->limitRows) && ($this->idx >= $this->limitRows + $this->limitOffset))) {
			$this->idx = $this->limitOffset;
			return false;
		}
		$this->ctx->curctx->push('CIndex', $this->idx);
		$this->ctx->curctx->push('SitemapEntryUrl', $this->IdEntries[$this->idx]['url']);
		$this->ctx->curctx->push('SitemapEntryText', $this->IdEntries[$this->idx]['text']);
		$this->ctx->curctx->push('SitemapEntryDescription', $this->IdEntries[$this->idx]['description']);
		$this->ctx->curctx->push('SitemapEntryId', $this->IdEntries[$this->idx]['id']);
		$this->ctx->curctx->push('SitemapEntryOnclick', $this->IdEntries[$this->idx]['onclick']);
		$this->ctx->curctx->push('SitemapEntryFolder', $this->IdEntries[$this->idx]['folder'] ? '1' : '0');
		$this->ctx->curctx->push('SitemapEntryPageTitle', $this->IdEntries[$this->idx]['pageTitle']);
		$this->ctx->curctx->push('SitemapEntryPageDescription', $this->IdEntries[$this->idx]['pageDescription']);
		$this->ctx->curctx->push('SitemapEntryPageKeywords', $this->IdEntries[$this->idx]['pageKeywords']);
		$this->ctx->curctx->push('SitemapEntryClassnames', $this->IdEntries[$this->idx]['classnames']);
		$this->ctx->curctx->push('SitemapEntryMenuIgnore', $this->IdEntries[$this->idx]['menuIgnore']);
		$this->idx++;
		$this->index = $this->idx;
		return true;
	}
}



/**
 * Get child nodes (one level) of the specified node.
 *
 * <OCSitemapEntries sitemap="sitemapName" node="parentNode">
 *
 * </OCSitemapEntries>
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 * - The node attribute is mandatory, it specifies the sitemap id of the node for which child nodes be returned.
 *
 */
class Func_Ovml_Container_SitemapEntries extends Ovml_Container_Sitemap
{


	public function setOvmlContext(babOvTemplate $ctx)
	{

		parent::setOvmlContext($ctx);

		$node = $ctx->get_value('node');

		if (isset($this->sitemap)) {
			$node = $this->sitemap->getNodeById($node);

			if ($node) {
				$node = $node->firstChild();
				while($node) {
					/* @var $item bab_SitemapItem */
					$item = $node->getData();
					$tmp = array();

					$tmp['url'] = $item->getRwUrl();
					$tmp['text'] = $item->name;
					$tmp['description'] = $item->description;
					$tmp['id'] = $item->id_function;
					$tmp['onclick'] = $item->onclick;
					$tmp['folder'] = $item->folder;
					$tmp['pageTitle'] = $item->getPageTitle();
					$tmp['pageDescription'] = $item->getPageDescription();
					$tmp['pageKeywords'] = $item->getPageKeywords();
					$tmp['classnames'] = $item->getIconClassnames();
					$tmp['menuIgnore'] = $item->menuIgnore;
					$this->IdEntries[] = $tmp;
					$node = $node->nextSibling();
				}
			}

			$this->count = count($this->IdEntries);
			$this->ctx->curctx->push('CCount', $this->count);
		}
	}

}





/**
 * Get node.
 *
 * <OCSitemapEntry sitemap="sitemapName" node="nodeId">
 *
 * </OCSitemapEntry>
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 * - The node attribute is mandatory.
 *
 */
class Func_Ovml_Container_SitemapEntry extends Ovml_Container_Sitemap
{


	public function setOvmlContext(babOvTemplate $ctx)
	{

		parent::setOvmlContext($ctx);

		$node = $ctx->get_value('node');

		if (isset($this->sitemap)) {
			$node = $this->sitemap->getNodeById($node);

			if ($node) {

				/* @var $item bab_SitemapItem */
				$item = $node->getData();
				$tmp = array();

				$tmp['url'] = $item->getRwUrl();
				$tmp['text'] = $item->name;
				$tmp['description'] = $item->description;
				$tmp['id'] = $item->id_function;
				$tmp['onclick'] = $item->onclick;
				$tmp['folder'] = $item->folder;
				$tmp['pageTitle'] = $item->getPageTitle();
				$tmp['pageDescription'] = $item->getPageDescription();
				$tmp['pageKeywords'] = $item->getPageKeywords();
				$tmp['classnames'] = $item->getIconClassnames();
				$tmp['menuIgnore'] = $item->menuIgnore;
				$this->IdEntries[] = $tmp;
			}

			$this->count = count($this->IdEntries);
			$this->ctx->curctx->push('CCount', $this->count);
		}
	}

}







/**
 * Get path starting from root (or a specified base node) to a specific sitemap node.
 *
 * <OCSitemapPath [sitemap="sitemapName"] [node="node"] [basenode="node"] [keeplastknown="0|1"] [limit=max_nodes|start_node,max_nodes]>
 *
 * </OCSitemapPath>
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 * - The node attribute is optional, it specifies the sitemap id of the node for which the path will be returned.
 * 		The default is the node corresponding to the current page (or the last known page displayed if keeplastknown is active).
 * - The basenode attribute is optional, it will be the starting node used for the <ul> tree.
 * 		The default value is set by api (ex: sitemap_editor).
 * - The keeplastknown attribute is optional, if set to "1", the last accessed sitemap node is kept selected if accessing a page not in the sitemap.
 * 		The default value is '1'.
 */
class Func_Ovml_Container_SitemapPath extends Ovml_Container_Sitemap
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		parent::setOvmlContext($ctx);

		$baseNodeId = $ctx->get_value('basenode');

		$nodeId = $ctx->get_value('node');

		if (isset($this->sitemap)) {

			if ($nodeId === false || empty($nodeId)) {
				$nodeId = bab_Sitemap::getPosition();

				if ($baseNodeId) {
					// if base node (parameter 'basenode') has been specified,
					// we try to find if a descendant of this node has
					// a target to the current position.
					$baseNode = $this->sitemap->getNodeById($baseNodeId);
					
					if (null === $baseNode)
					{
						trigger_error(sprintf('the basenode="%s" attribute has been specified in ovml file %s but the node has not been found in the sitemap',$baseNodeId, (string) $ctx->debug_location));
						bab_debug((string) $this->sitemap);
					} else {
					
						$nodes = $this->sitemap->createNodeIterator($baseNode);
	
						while ($node = $nodes->nextNode()) {
							$sitemapItem = $node->getData();
							$target = $sitemapItem->target;
							if ($target === $nodeId) {
								$nodeId = $sitemapItem->id_function;
								break;
							}
						}
					}
				}
			}


			if (empty($nodeId)) {
				$keepLastKnown = $ctx->get_value('keeplastknown');
				if ($keepLastKnown === false) {
					// If keeplastknown is not specified, active by default
					$keepLastKnown = 1;
				}
				if ($keepLastKnown && isset($_SESSION['bab_sitemap_lastknownnode'])) {
					$nodeId = $_SESSION['bab_sitemap_lastknownnode'];
				}
			} else {
				$_SESSION['bab_sitemap_lastknownnode'] = $nodeId;
			}


			if ($nodeId) {
				$node = $this->sitemap->getNodeById($nodeId);
				

				if (empty($baseNodeId))
				{
					$baseNodeId = bab_Sitemap::getVisibleRootNodeByUid($this->sitemap_name);
				}

				$baseNodeFound = false;


				while ($node && ($item = $node->getData())) {
					/* @var $item bab_SitemapItem */
					$tmp = array();

					$tmp['url'] = $item->getRwUrl();
					$tmp['text'] = $item->name;
					$tmp['description'] = $item->description;
					$tmp['id'] = $item->id_function; 
					if ($baseNodeId === $item->id_function)
					{
						$baseNodeFound = true;
					}
					$tmp['onclick'] = $item->onclick;
					$tmp['folder'] = $item->folder;
					$tmp['pageTitle'] = $item->getPageTitle();
					$tmp['pageDescription'] = $item->getPageDescription();
					$tmp['pageKeywords'] = $item->getPageKeywords();
					$tmp['classnames'] = $item->getIconClassnames();
					$tmp['menuIgnore'] = $item->menuIgnore;
					array_unshift($this->IdEntries, $tmp);
					if ($item->id_function === $baseNodeId) {
						break;
					}
					$node = $node->parentNode();
				}


				if (!$baseNodeFound)
				{
					$this->IdEntries = array();
					$this->count = 0;
					$this->ctx->curctx->push('CCount', $this->count);
					return;
				}

				$this->count = count($this->IdEntries);
				$this->ctx->curctx->push('CCount', $this->count);

			} else {
				$this->IdEntries = array();
				$this->count = 0;
				$this->ctx->curctx->push('CCount', $this->count);
			}
		}
	}

}









/**
 * Return the sitemap position in a html LI
 * <OFSitemapPosition [sitemap="sitemapName"] [keeplastknown="0|1"] [basenode="node"] >
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 * - The node attribute is optional.
 * 		By default it is the node corresponding to the current page (or the last known page displayed if keeplastknown is active).
 * - The basenode attribute is optional, it will be the starting node used for the <ul> tree.
 * 		The default value is 'babDgAll'.
 * - The keeplastknown attribute is optional, if set to "1", the last accessed sitemap node is kept selected if accessing a page not in the sitemap.
 * 		The default value is '1'.
 */
class Func_Ovml_Function_SitemapPosition extends Func_Ovml_Function
{



	/**
	 *
	 * @return string
	 */
	public function toString()
	{
		$args = $this->args;

		$sitemap = empty($args['sitemap']) ? null : $args['sitemap'];
		$baseNode = empty($args['basenode']) ? null : $args['basenode'];
		$node = empty($args['node']) ? null : $args['node'];

		$breadcrumb = bab_siteMap::getBreadCrumb($sitemap, $baseNode, $node);

		if (!isset($args['keeplastknown'])) {
			// If keeplastknown is not specified, active by default
			$keepLastKnown = 1;
		} else {
			$keepLastKnown = $args['keeplastknown'];
		}
		
		
		

		if (null === $breadcrumb) {


			if ((!$keepLastKnown) || (!isset($_SESSION['bab_sitemap_lastknownposition'])) ) {
				return '';
			}
			if (isset($_SESSION['bab_sitemap_lastknownposition'])) {
				return $_SESSION['bab_sitemap_lastknownposition'];
			} else {
				return '';
			}
		}
		
		if (empty($breadcrumb))
		{
			return '';
		}
		

//		$html = '<ul class="sitemap-position">'."\n";
		$html = '';

		foreach($breadcrumb as $node) {

			if (!($node instanceOf bab_Node)) {
				$html .= sprintf('<li>Broken sitemap node : %s</li>'."\n", (string) $node);
				continue;
			}


			$sitemapItem = $node->getData();

			if (!$sitemapItem) {
				$html .= sprintf('<li>Broken sitemap node : %s</li>'."\n", $node->getId());
				continue;
			}

			if ($sitemapItem->url) {

				$html .= sprintf('<li class="sitemap-%s"><a href="%s">%s</a></li>'."\n",

					$node->getId(),
					$sitemapItem->getRwUrl(),
					$sitemapItem->name
				);

			} else {


				$html .= sprintf('<li class="sitemap-%s"><span>%s</span></li>'."\n",

					$node->getId(),
					$sitemapItem->name

				);

			}
		}

//		$html .= '</ul>';

		if ($keepLastKnown) {
			$_SESSION['bab_sitemap_lastknownposition'] = $html;
		}
		
		

		return $html;
	}
}














/**
 * Return the sitemap menu tree in a html UL LI
 *
 * <OFSitemapMenu [sitemap="sitemapName"] [basenode="parentNode"] [selectednode=""] [keeplastknown="0|1"] [maxdepth="depth"] [outerul="1"] [admindelegation="0"]>
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 * - The keeplastknown attribute is optional, if set to "1", the last accessed sitemap node is kept selected if accessing a page not in the sitemap.
 * 		The default value is '1'.
 * - The basenode attribute is optional, it will be the starting node used for the <ul> tree.
 * 		The default value is set by API (ex: Custom for sitemap from the sitemap_editor).
 * - The selectednode attribute is optional, will add class 'selected' to the corresponding li, and 'active' to itself and all its <li> ancestors.
 * 		By default it is the node corresponding to the current page (or the last known page displayed if keeplastknown is active).
 * - The maxdepth attribute is optional, limits the number of levels of nested <ul>.
 * 		No maximum depth by default.
 * - The outerul attribute is optional, if set to "1" add a UL htmltag
 * 		The default value is '1'.
 * - The admindelegation attribute is optional, if set to "1" the display of ovidentia administration node will only display if the user can manage this property
 * 		The default value is '0'.
 *
 *
 * Example:
 *
 * The following OVML function :
 * <OFSitemapMenu basenode="babUser">
 *
 * Will yield (when we are on 'Publication' page) :
 *
 * <ul class="sitemap-menu-root">
 * <li class="no-icon sitemap-babUserSection sitemap-folder active sitemap-main-menu"><div><span class="icon">Ovidentia functions</span></div><ul>
 * <li class="no-icon sitemap-babUserPublication apps-articles"><a href="index.php?tg=artedit&amp;smed_id=babUserPublication" class="icon apps-articles">Publication</a></li>
 * <li class="no-icon sitemap-babUserArticlesMan apps-articles active selected"><a title="List article topics where i am manager" href="index.php?tg=topman&amp;smed_id=babUserArticlesMan" class="icon apps-articles">Articles management</a></li>
 * <li class="no-icon sitemap-babUserOptions categories-preferences-desktop"><a href="index.php?tg=options&amp;smed_id=babUserOptions" class="icon categories-preferences-desktop">Options</a></li>
 * ...
 * </ul>
 */
class Func_Ovml_Function_SitemapMenu extends Func_Ovml_Function {

	protected	$sitemap;

	/* The current sitemap node id */
	protected	$selectedNodeId = null;

	/* the node ids of the current sitemap path */
	protected	$activeNodes = array();

	protected	$selectedClass = 'selected';
	protected	$activeClass = 'active';
	protected	$delegAdmin = array();

	protected	$maxDepth = 100;

	private function getHtml(bab_Node $node, $mainmenuclass = null, $depth = 1) {

		global $babBody;
		$return = '';
		$classnames = array();

		$id = $node->getId();
		$siteMapItem = $node->getData();
		/* @var $siteMapItem bab_siteMapItem */
		
		if(isset($this->delegAdmin[$babBody->currentAdmGroup][$id]))
		{
			return $return;
		}
		
		
		if (!empty($siteMapItem->iconClassnames)) {
			$icon = 'icon '.$siteMapItem->iconClassnames;
		} else {
			$icon = 'icon';
		}

		if (!empty($siteMapItem->description)) {

			$description = ' title="'.bab_toHtml($siteMapItem->description).'"';
		} else {
			$description = '';
		}

		$url = $siteMapItem->getRwUrl();

		if ($url) {

			if ($siteMapItem->onclick) {
				$onclick = ' onclick="'.bab_toHtml($siteMapItem->onclick).'"';
			} else {
				$onclick = '';
			}

			$htmlData = '<a class="'.bab_toHtml($icon).'" href="'.bab_toHtml($url).'" '.$onclick.' '.$description.'>'.bab_toHtml($siteMapItem->name).'</a>';
		} else {
			$htmlData = '<span class="'.bab_toHtml($icon).'"'.$description.'>'.bab_toHtml($siteMapItem->name).'</span>';
		}



		$classnames[] = 'sitemap-'.$siteMapItem->id_function;

		if (!empty($siteMapItem->iconClassnames)) {
			$classnames[] = $siteMapItem->iconClassnames;
		}

		if ($siteMapItem->folder) {
			$classnames[] = 'sitemap-folder';
		}

		if (isset($this->activeNodes[$siteMapItem->id_function])) {
			// the nodes in the current path have the "active" class.
			$classnames[] = $this->activeClass;
		}
		if ($this->selectedNodeId === $siteMapItem->id_function) {
			// the current node has the "selected" class.
			$classnames[] = $this->selectedClass;
		}

		$return .= '<li class="no-icon '.implode(' ', $classnames).'">'.$htmlData;

		if ($node->hasChildNodes() && $depth < $this->maxDepth) {
			$return .= "<ul>\n";

			$node = $node->firstChild();
			do {
				if (!$node->getData()->menuIgnore)
				{
					$return .= $this->getHtml($node, null, $depth + 1);
				}
			} while ($node = $node->nextSibling());

			$return .= "</ul>\n";
		}

		$return .= "</li>\n";

		return $return;
	}




	/**
	 *
	 * @return string
	 */
	public function toString()
	{
		require_once dirname(__FILE__).'/delegincl.php';
		global $babBody;
		$args = $this->args;

		if (isset($args['sitemap'])) {
			$sitemap = bab_siteMap::getByUid($args['sitemap']);
			$sitemap_name = $args['sitemap'];
		} else {
			global $babBody;
			$sitemap = bab_siteMap::getByUid($babBody->babsite['sitemap']);
			$sitemap_name = $babBody->babsite['sitemap'];
			if (!isset($sitemap)) {
				$sitemap_name = 'core';
				$sitemap = bab_siteMap::get();
			}
		}

		if (!isset($sitemap)) {
			trigger_error(sprintf('incorrect attribute in %s#%s sitemap="%s"', (string) $this->template->debug_location, get_class($this), $args['sitemap']));
			return '';
		}
		
		if( (isset($args['admindelegation']) && $args['admindelegation'] == '1' ) && $babBody->currentAdmGroup != 0 && !isset($this->delegAdmin[$babBody->currentAdmGroup]))
		{
			$delegation = bab_getDelegationById($babBody->currentAdmGroup);
			$delegation = $delegation[0];
			foreach(bab_getDelegationsObjects() as $link)
			{
				if (!isset($link[3]))
				{
					continue;
				}
			
				if ($delegation[$link[0]] !== 'Y')
				{
					$this->delegAdmin[$babBody->currentAdmGroup]['bab'.$link[2]] = true;
					continue;
				}
			}
		}

		$this->sitemap = $sitemap;

		$dg_node = $sitemap->firstChild();

		if (!($dg_node instanceOf bab_Node)) {
			return '';
		}

		if (empty($args['basenode']))
		{
			$args['basenode'] = bab_siteMap::getVisibleRootNodeByUid($sitemap_name);
		}

		if (isset($args['basenode']) && (!empty($args['basenode']))) {
			$home = $sitemap->getNodeById($args['basenode']);
			$baseNodeId = $args['basenode'];
		} else {
			$home = $dg_node->firstChild();
			$baseNodeId = null;
		}

		if (!($home instanceOf bab_Node)) {
			return '';
		}

		if (isset($args['maxdepth']) && (!empty($args['maxdepth']))) {
			$this->maxDepth = $args['maxdepth'];
		}


		if (isset($args['selectednode']) && (!empty($args['selectednode']))) {
			$selectedNodeId = $args['selectednode'];
		}
		if (!isset($selectedNodeId)) {
			$selectedNodeId = bab_Sitemap::getPosition();

			if (isset($baseNodeId)) {
				// if base node (parameter 'basenode') has been specified,
				// we try to find if a descendant of this node has
				// a target to the current position.
				$baseNode = $this->sitemap->getNodeById($baseNodeId);
				$nodes = $this->sitemap->createNodeIterator($baseNode);

				while ($node = $nodes->nextNode()) {
					$sitemapItem = $node->getData();
					$target = $sitemapItem->target;
					if ($target === $selectedNodeId) {
						$selectedNodeId = $sitemapItem->id_function;
						break;
					}
				}
			}
		}

		if (!isset($args['keeplastknown'])) {
			// If keeplastknown is not specified, active by default
			$keepLastKnown = 1;
		} else {
			$keepLastKnown = $args['keeplastknown'];
		}

		if (empty($selectedNodeId)) {
			if ($keepLastKnown && isset($_SESSION['bab_sitemap_lastknownnode'])) {
				$selectedNodeId = $_SESSION['bab_sitemap_lastknownnode'];
			}
		} else {
			$_SESSION['bab_sitemap_lastknownnode'] = $selectedNodeId;
		}


		$this->selectedNodeId = $selectedNodeId;

		$selectedNode = $this->sitemap->getNodeById($selectedNodeId);

		while ($selectedNode && ($item = $selectedNode->getData())) {
			/* @var $item bab_SitemapItem */
			$this->activeNodes[$item->id_function] = $item->id_function;
			if ($home->getData()->id_function === $item->id_function) {
				break;
			}
			$selectedNode = $selectedNode->parentNode();
		}

		$node = $home->firstChild();
		$return = '';

		if (!isset($args['outerul'])) {
			// If keeplastknown is not specified, active by default
			$outerUl = 1;
		} else {
			$outerUl = $args['outerul'];
		}

		if ($node) {

			if ($outerUl) {
				$return .= '<ul class="sitemap-menu-root">'."\n";
			}

			do {
				$return .= $this->getHtml($node, 'sitemap-main-menu');
			} while ($node = $node->nextSibling());

			if ($outerUl) {
				$return .= '</ul>'."\n";
			}
		}
		return $return;
	}
}

