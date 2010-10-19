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
			$this->sitemap = bab_siteMap::get();
		} else {
			$this->sitemap = bab_siteMap::getByUid($sitemap);
			
			if (null === $this->sitemap) {
				trigger_error('incorrect sitemap attribute in OCSitemapEntries');
				return;
			}
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
		$this->ctx->curctx->push('SitemapEntryFolder', $this->IdEntries[$this->idx]['folder']);
		$this->ctx->curctx->push('SitemapEntryPageTitle', $this->IdEntries[$this->idx]['pageTitle']);
		$this->ctx->curctx->push('SitemapEntryPageDescription', $this->IdEntries[$this->idx]['pageDescription']);
		$this->ctx->curctx->push('SitemapEntryPageKeywords', $this->IdEntries[$this->idx]['pageKeywords']);
		$this->ctx->curctx->push('SitemapEntryClassnames', $this->IdEntries[$this->idx]['classnames']);
		$this->idx++;
		$this->index = $this->idx;
		return true;
	}
}



/**
 * Get one level of sitemap node
 * <OCSitemapEntries node="parentNode" sitemap="sitemapName">
 * 
 * </OCSitemapEntries>
 * 
 * the sitemap attribute is optional
 *
 */
class Func_Ovml_Container_SitemapEntries extends Ovml_Container_Sitemap
{


	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babUseRewrittenUrl;

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
					if (isset($babUseRewrittenUrl) && $babUseRewrittenUrl) {
						$tmp['url'] = bab_Sitemap::rewrittenUrl($item->id_function);
					} else {
						$tmp['url'] = $item->url;
					}
//					$tmp['url'] = $item->url;
					$tmp['text'] = $item->name;
					$tmp['description'] = $item->description;
					$tmp['id'] = $item->id_function;
					$tmp['onclick'] = $item->onclick;
					$tmp['folder'] = $item->folder;
					$tmp['pageTitle'] = $item->getPageTitle();
					$tmp['pageDescription'] = $item->getPageDescription();
					$tmp['pageKeywords'] = $item->getPageKeywords();
					$tmp['classnames'] = $item->getIconClassnames();
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
 * Get path starting from root to a specific sitemap node
 * <OCSitemapPath [node="node"] [sitemap="sitemapName"] [basenode="node"]>
 * 
 * </OCSitemapPath>
 * 
 * the sitemap attribute is optional
 *
 */
class Func_Ovml_Container_SitemapPath extends Ovml_Container_Sitemap
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		global $babUseRewrittenUrl;

		parent::setOvmlContext($ctx);
		
		
		$node = $ctx->get_value('node');

		if ($node === false) {
			$node = bab_Sitemap::getPosition();
		}

		$baseNode = $ctx->get_value('basenode');

		if (isset($this->sitemap)) {
			$node = $this->sitemap->getNodeById($node);

			while ($node && ($item = $node->getData())) {
				/* @var $item bab_SitemapItem */
				$tmp = array();
				if (isset($babUseRewrittenUrl) && $babUseRewrittenUrl) {
					$tmp['url'] = bab_Sitemap::rewrittenUrl($item->id_function);
				} else {
					$tmp['url'] = $item->url;
				}
				$tmp['text'] = $item->name;
				$tmp['description'] = $item->description;
				$tmp['id'] = $item->id_function;
				$tmp['onclick'] = $item->onclick;
				$tmp['folder'] = $item->folder;
				$tmp['pageTitle'] = $item->getPageTitle();
				$tmp['pageDescription'] = $item->getPageDescription();
				$tmp['pageKeywords'] = $item->getPageKeywords();
				$tmp['classnames'] = $item->getIconClassnames();
				array_unshift($this->IdEntries, $tmp);
				if ($item->id_function === $baseNode) {
					break;
				}
				$node = $node->parentNode();
			}
	
			$this->count = count($this->IdEntries);
			$this->ctx->curctx->push('CCount', $this->count);
		}
	}

}









/**
 * Return the sitemap position in a html LI
 * <OFSitemapPosition [sitemap="sitemapName"] [keeplastknown="0|1"] [limit=max_nodes|start_node,max_nodes] >
 * 
 * - The sitemap attribute is optional, the default value is the sitemap selected in Administration > Sites > Site configuration
 * - The keeplastknown attribute is optional, if set to "1", the last accessed sitemap node is kept selected if accessing a page not in the sitemap. 
 */
class Func_Ovml_Function_SitemapPosition extends Func_Ovml_Function
{
	
	/**
	 * 
	 * @param bab_siteMap	$sitemap
	 * @param string 		$baseNodeId		The node from which the breadcrumb will start.
	 * @param string		$nodeId			Optional node id, use automatic kernel current node if not specified.		
	 */	
	public function breadCrumbFromBaseNode($sitemap, $baseNodeId, $nodeId = null)
	{
		if (!isset($nodeId)) {
			$nodeId = bab_Sitemap::getPosition();
			if (!isset($nodeId)) {
				return array();
			}
		}

		$baseNode = $sitemap->getNodeById($baseNodeId);
		if (!isset($baseNode)) {
			return array();
		}

		$subNodes = new bab_NodeIterator($baseNode);
		
		$matchingNodes = array();
		while (($node = $subNodes->nextNode()) && (count($matchingNodes) < 2)) {
			/* @var $node bab_Node */
			if ($node->getId() === $nodeId) {
				$matchingNodes[] = $node;
				continue;
			}
			/* @var $data bab_SitemapItem */
			$data = $node->getData();
			if ($data->getTarget() === $nodeId) {
				$matchingNodes[] = $node;
			}
		}

		if (count($matchingNodes) !== 1) {
			return array();			
		}

		$node = $matchingNodes[0];
		$breadCrumbs = array($node);
		while (($node = $node->parentNode()) && ($node->getId() !== $baseNodeId)) {
			array_unshift($breadCrumbs, $node);
		}

		return $breadCrumbs;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function toString()
	{
		$args = $this->args;

		if (isset($args['basenode'])) {

			if (isset($args['sitemap'])) {
				$sitemap = bab_siteMap::getByUid($args['sitemap']);
			} else {
				global $babBody;
				$sitemapId = $babBody->babsite['sitemap'];
				$sitemap = bab_siteMap::getByUid($sitemapId);
				if (!isset($sitemap)) {
					$sitemap = bab_siteMap::getByUid('core');
				}
			}
			if (!isset($sitemap)) {
				$breadcrumb = array();
			} else {
				$node = (isset($args['node']) && (!empty($args['node']))) ? $args['node'] : null; 
				$breadcrumb = $this->breadcrumbFromBaseNode($sitemap, $args['basenode'], $node);
			}

 		} else if (isset($args['sitemap'])) {

			$breadcrumb = bab_siteMap::getBreadCrumb($args['sitemap']);

		} else {

			$breadcrumb = bab_siteMap::getBreadCrumb();

		}


		if (empty($breadcrumb)) {
			if ( (!isset($args['keeplastknown'])) || (!$args['keeplastknown']) || (!isset($_SESSION['bab_sitemap_lastknownposition'])) ) {
				return '';
			}
			if (isset($_SESSION['bab_sitemap_lastknownposition'])) {
				return $_SESSION['bab_sitemap_lastknownposition'];
			} else {
				return '';
			}
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
					$sitemapItem->url,
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
	
		if (isset($args['keeplastknown'])) {
			$_SESSION['bab_sitemap_lastknownposition'] = $html;
		}

		return $html;
	}
}














/**
 * Return the sitemap menu tree in a html UL LI
 * <OFSitemapMenu [sitemap="sitemapName"] [basenode="parentNode"] [selectednode=""] [keeplastknown="1"] [maxdepth="depth"] >
 * 
 * - The sitemap attribute is optional, the default value is the sitemap selected in Administration > Sites > Site configuration
 * - The keeplastknown attribute is optional, if set to "1", the last accessed sitemap node is kept selected if accessing a page not in the sitemap. 
 * - The basenode attribute is optional, the default value is babDgAll.
 * - The selectednode attribute is optional, is the node corresponding to the current page.
 */
class Func_Ovml_Function_SitemapMenu extends Func_Ovml_Function {
	
	protected	$sitemap; 

	/* The current sitemap node id */
	protected	$selectedNodeId = null;

	/* the node ids of the current sitemap path */
	protected	$activeNodes = array();

	protected	$selectedClass = 'selected';
	protected	$activeClass = 'active';
	
	protected	$maxDepth = 100;
	
	
	private function getHtml(bab_Node $node, $mainmenuclass = null, $depth = 1) {
		
		global $babUseRewrittenUrl;

		$return = '';
		$classnames = array();
	
		$id = $node->getId();
		$siteMapItem = $node->getData(); 	
		/* @var $siteMapItem bab_siteMapItem */
	
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


		if (isset($babUseRewrittenUrl) && $babUseRewrittenUrl) {
			$url = bab_Sitemap::rewrittenUrl($siteMapItem->id_function);
		} else {
			$url = $siteMapItem->url;
		}

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

		if (null !== $mainmenuclass) {
			$classnames[] = $mainmenuclass;
			$return .= '<li class="no-icon '.implode(' ', $classnames).'"><div>'.$htmlData.'</div>';
		} else {
			$return .= '<li class="no-icon '.implode(' ', $classnames).'">'.$htmlData;
		}
	
		//  icon-16x16 icon-left icon-left-16
	
		if ($node->hasChildNodes() && $depth < $this->maxDepth) {
			$return .= "<ul>\n";
	
			$node = $node->firstChild();
			do {
				$return .= $this->getHtml($node, null, $depth + 1);
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
		$args = $this->args;
		
		if (isset($args['sitemap'])) {
			$sitemap = bab_siteMap::getByUid($args['sitemap']);
		} else {
			global $babBody;
			$sitemap = bab_siteMap::getByUid($babBody->babsite['sitemap']);
			if (!isset($sitemap)) {
				$sitemap = bab_siteMap::get();
			}
		}
		
		if (!isset($sitemap)) {
			return '';
		}

		$this->sitemap = $sitemap;

		$dg_node = $sitemap->firstChild();
		
		if (!($dg_node instanceOf bab_Node)) {
			return '';
		}


		if (isset($args['node'])) {
			$home = $sitemap->getNodeById($args['node']);
			$baseNodeId = $args['node'];
		} else {
			$home = $dg_node->firstChild();
			$baseNodeId = null;
		}

		if (!($home instanceOf bab_Node)) {
			return '';
		}

		if (isset($args['maxdepth'])) {
			$this->maxDepth = $args['maxdepth'];
		}

		
		if (isset($args['selectednode'])) {
			$selectedNodeId = $args['selectednode'];
		}
		if (!isset($selectedNode)) {
			$selectedNodeId = bab_Sitemap::getPosition();

			if (isset($baseNodeId)) {
				// if base node (parameter 'node') has been specified,
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

		if (empty($selectedNodeId)) {
			if (isset($args['keeplastknown']) && $args['keeplastknown'] && isset($_SESSION['bab_sitemap_lastknownnode'])) {
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
		
		if ($node) {
			
			$return .= '<ul class="sitemap-menu-root">'."\n";
			
			do {
				$return .= $this->getHtml($node, 'sitemap-main-menu');
			} while ($node = $node->nextSibling());
			
			$return .= '</ul>'."\n";
		}
		return $return;
	}
}

	