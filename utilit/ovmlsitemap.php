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
 * Get one level of sitemap node
 * <OCSitemapEntries node="parentNode" sitemap="sitemapName">
 * 
 * </OCSitemapEntries>
 * 
 * the sitemap attribute is optional
 *
 */
class Func_Ovml_Container_SitemapEntries extends Func_Ovml_Container
{
	var $IdEntries = array();
	var $index;
	var $count;
	var $data;

	public function setOvmlContext(babOvTemplate $ctx)
	{
		$this->count = 0;
		parent::setOvmlContext($ctx);
		$node = $ctx->get_value('node');
		$sitemap = $ctx->get_value('sitemap');
		
		if (false === $sitemap) {
			$rootNode = bab_siteMap::get();
		} else {
			$rootNode = bab_siteMap::getByUid($sitemap);
		}

		
		$node = $rootNode->getNodeById($node);
		if ($node) {
			$node = $node->firstChild();
			while($node)
				{
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

	public function getnext()
	{
		if( $this->idx < $this->count )
		{
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
		else
		{
			$this->idx=0;
			return false;
		}
	}
}













/**
 * Return the sitemap position in a html UL LI
 * <OFSitemapPosition sitemap="sitemapName">
 * the sitemap attribute is optional
 * 
 */
class Func_Ovml_Function_SitemapPosition extends Func_Ovml_Function {
	
	
	/**
	 * 
	 * @return string
	 */
	public function toString()
	{
		$args = $this->args;
		if (isset($args['sitemap'])) {
			$breadcrumb = bab_siteMap::getBreadCrumb($args['sitemap']);
		} else {
			$breadcrumb = bab_siteMap::getBreadCrumb();
		}
		
		if (empty($breadcrumb)) {
			return '';
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
	
		
		return $html;
	}
}














/**
 * Return the sitemap menu tree in a html UL LI
 * <OFSitemapMenu sitemap="sitemapName" node="parentNode">
 * the sitemap attribute is optional
 * the node attribute is optional
 */
class Func_Ovml_Function_SitemapMenu extends Func_Ovml_Function {
	
	
	/**
	 * 
	 * @return string
	 */
	public function toString()
	{
		$args = $this->args;
		
		return '';
	}
}

	