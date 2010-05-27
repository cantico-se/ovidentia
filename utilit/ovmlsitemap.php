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
		parent::setOvmlContext($ctx);

		$node = $ctx->get_value('node');

		if (isset($this->sitemap)) {				
			$node = $this->sitemap->getNodeById($node);
	
			if ($node) {
				$node = $node->firstChild();
				while($node) {
					$item = $node->getData();
					$tmp = array();
					$tmp['url'] = $item->url;
					$tmp['text'] = $item->name;
					$tmp['description'] = $item->description;
					$tmp['id'] = $item->id_function;
					$tmp['onclick'] = $item->onclick;
					$tmp['folder'] = $item->folder;
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
 * <OCSitemapPath node="node" sitemap="sitemapName">
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
		parent::setOvmlContext($ctx);
		$node = $ctx->get_value('node');

		if (isset($this->sitemap)) {
			$node = $this->sitemap->getNodeById($node);
	
			while ($node && ($item = $node->getData())) {
				$tmp = array();
				$tmp['url'] = $item->url;
				$tmp['text'] = $item->name;
				$tmp['description'] = $item->description;
				$tmp['id'] = $item->id_function;
				$tmp['onclick'] = $item->onclick;
				$tmp['folder'] = $item->folder;
				array_unshift($this->IdEntries, $tmp);
				$node = $node->parentNode();
			}
	
			$this->count = count($this->IdEntries);
			$this->ctx->curctx->push('CCount', $this->count);
		}
	}

}









/**
 * Return the sitemap position in a html UL LI
 * <OFSitemapPosition sitemap="sitemapName">
 * the sitemap attribute is optional, the default value is the sitemap selected in site options
 * 
 */
class Func_Ovml_Function_SitemapPosition extends Func_Ovml_Function
{
	
	/**
	 * 
	 * @param bab_siteMap	$sitemap
	 * @param string 		$baseNodeId
	 * @param string		$pageId			Optional node id, use automatic kernel current node if not specified.		
	 */	
	public function breadCrumbFromBaseNode($sitemap, $baseNodeId, $pageId = null)
	{
		if (!isset($pageId)) {
			$pageId = bab_Sitemap::getPosition();
			if (!isset($pageId)) {
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
			if ($node->getId() === $pageId) {
				$matchingNodes[] = $node;
				continue;
			}
			/* @var $data bab_SitemapItem */
			$data = $node->getData();
			if ($data->getTarget() === $pageId) {
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
				$node = isset($args['node']) ? $args['node'] : null; 
				$breadcrumb = $this->breadcrumbFromBaseNode($sitemap, $args['basenode'], $node);
			}

 		} else if (isset($args['sitemap'])) {

			$breadcrumb = bab_siteMap::getBreadCrumb($args['sitemap']);

		} else {

			$breadcrumb = bab_siteMap::getBreadCrumb();

		}


		if (empty($breadcrumb)) {
			if (!isset($args['keeplastknown'])) {
				return '';
			}
			return $_SESSION['bab_sitemap_lastknowposition'];
		}

		$html = '<ul class="sitemap-position">'."\n";
		
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
		
		$html .= '</ul>';
	
		if (isset($args['keeplastknown'])) {
			$_SESSION['bab_sitemap_lastknowposition'] = $html;
		}

		return $html;
	}
}














/**
 * Return the sitemap menu tree in a html UL LI
 * <OFSitemapMenu sitemap="sitemapName" node="parentNode">
 * the sitemap attribute is optional, the default value is the sitemap selected in site options
 * the node attribute is optional, the default value is babDgAll
 */
class Func_Ovml_Function_SitemapMenu extends Func_Ovml_Function {
	
	
	
	
	private function getHtml(bab_Node $node, $mainmenuclass = null) {
		
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
		
	
		if ($siteMapItem->url) {
	
			if ($siteMapItem->onclick) {
				$onclick = ' onclick="'.bab_toHtml($siteMapItem->onclick).'"';
			} else {
				$onclick = '';
			}
	
			$htmlData = '<a class="'.bab_toHtml($icon).'" href="'.bab_toHtml($siteMapItem->url).'" '.$onclick.''.$description.'>'.bab_toHtml($siteMapItem->name).'</a>';
		} else {
			$htmlData = '<span class="'.bab_toHtml($icon).'"'.$description.'>'.bab_toHtml($siteMapItem->name).'</span>';
		}
	
		
	
		$classnames[] = 'sitemap-'.$siteMapItem->id_function;
	
		if ($siteMapItem->folder) {
			$classnames[] = 'sitemap-folder';
		} 
	
	
		if (null !== $mainmenuclass) {
			$classnames[] = $mainmenuclass;
			$return .= '<li class="'.implode(' ', $classnames).'"><div>'.$htmlData.'</div>';
		} else {
			$return .= '<li class="'.implode(' ', $classnames).'">'.$htmlData;
		}
	
		//  icon-16x16 icon-left icon-left-16
	
		if ($node->hasChildNodes()) {
			$return .= "<ul>\n";
	
			$node = $node->firstChild();
			do {
				$return .= $this->getHtml($node);
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
			if (!isset($sitemap))
			{
				$sitemap = bab_siteMap::get();
			}
		}
		
		if (!isset($sitemap)) {
			return '';
		}
		
		$dg_node = $sitemap->firstChild();
		
		if (!($dg_node instanceOf bab_Node)) {
			return '';
		}
		
		
		if (isset($args['node'])) {
			$home = $sitemap->getNodeById($args['node']);
		} else {
			$home = $dg_node->firstChild();
		}
		
		if (!($home instanceOf bab_Node)) {
			return '';
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

	