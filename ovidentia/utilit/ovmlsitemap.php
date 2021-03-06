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
     * @var bab_siteMapOrphanRootNode $sitemap	The sitemap the container is working on.
     */
    protected $sitemap;

    /**
     *
     * @var string
     */
    protected $sitemap_name;

    /**
     * @var string  The current sitemap node id
     */
    protected	$selectedNodeId = null;

    /**
     * @var string[] The node ids of the current sitemap path
     */
    protected	$activeNodes = array();

    protected	$selectedClass = 'selected';
    protected	$activeClass = 'active';


    /**
     * @var int $limit				The max number of elements to return.
     */
    protected $limitOffset = 0;
    protected $limitRows = null;


    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $limit = $ctx->curctx->getAttribute('limit');
        if (is_string($limit)) {
            $limits = explode(',', $limit);
            if (count($limits) === 1) {
                $this->limitRows = (int) $limit;
            } else {
                $this->limitOffset = (int) $limits[0];
                $this->limitRows = (int) $limits[1];
            }
        }

        if ($this->limitOffset > 0) {
            $this->idx += $this->limitOffset;
        }

        $sitemap = $ctx->curctx->getAttribute('sitemap');

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

        $baseNodeId = $this->getBaseNode();
        $home = $this->sitemap->getNodeById($baseNodeId);

        $this->selectedNodeId = bab_siteMap::getPosition();

        // if base node (parameter 'basenode') has been specified,
        // we try to find if a descendant of this node has
        // a target to the current position.
        if ($customNode = $this->sitemap->getNodeByTargetId($baseNodeId, $selectedNodeId)) {
            $this->selectedNodeId = $customNode->getId();
        }

        $selectedNode = $this->sitemap->getNodeById($this->selectedNodeId);

        while ($selectedNode && ($item = $selectedNode->getData())) {
            /* @var $item bab_SitemapItem */
            $this->activeNodes[$item->id_function] = $item->id_function;
            if ($home->getData()->id_function === $item->id_function) {
                break;
            }
            $selectedNode = $selectedNode->parentNode();
        }
    }


    /**
     * Get base node ID from attribute or from visible root node
     * @return string
     */
    public function getBaseNode()
    {
        $baseNodeId = $this->ctx->getAttribute('basenode');
        $baseNodeId = str_replace(' ', '', $baseNodeId);
        if (empty($baseNodeId))
        {
            $baseNodeId = bab_siteMap::getVisibleRootNodeByUid($this->sitemap_name);
        }


        return $baseNodeId;
    }


    /**
     * Get a node using the node id or the language node id
     * @param string $nodeIdAttributeName
     * @param string $langIdAttributeName
     * @return bab_Node
     */
    protected function getNodeByIdOrLangId($nodeIdAttributeName, $langIdAttributeName)
    {
        $nodeId = $this->ctx->getAttribute($nodeIdAttributeName);
        $langId = $this->ctx->getAttribute($langIdAttributeName);

        if ($langId) {
            $node = $this->sitemap->getNodeByLangId(bab_getLanguage(), $langId);
            if (isset($node)) {
                return $node;
            }

            // if node not found by language, we continue with a test by id if the attribute is available
        }

        if ($nodeId) {
            return $this->sitemap->getNodeById($nodeId);
        }


        return null;
    }

    /**
     * Get a node using the node id or the language node id
     * @param string $nodeIdAttributeName
     * @param string $langIdAttributeName
     * @return bab_Node
     */
    protected function getNode($nodeIdAttributeName, $langIdAttributeName)
    {
        if ($node = $this->getNodeByIdOrLangId($nodeIdAttributeName, $langIdAttributeName)) {
            return $node;
        }

        // do not display error because this can be un inaccessible node

        /*
        trigger_error(sprintf('incorrect attribute in %s#%s, either %s or %s must be used to get a sitemap node',
            (string) $this->ctx->debug_location,
            get_class($this),
            $nodeIdAttributeName,
            $langIdAttributeName
        ));
        */
        return null;
    }




    /**
     * (non-PHPdoc)
     * @see utilit/Func_Ovml_Container#getnext()
     */
    public function getnext()
    {
        if (0 === $this->idx && $this->limitOffset < 0) {
            // initialize real offset if offset < 0
            $this->limitOffset = $this->count + $this->limitOffset;
            $this->idx += $this->limitOffset;
        }

        if ($this->idx >= $this->count || (isset($this->limitRows) && ($this->idx >= $this->limitRows + $this->limitOffset))) {
            $this->idx = $this->limitOffset;
            return false;
        }

        if (!isset($this->IdEntries[$this->idx])) {
            return false;
        }

        $entry = $this->IdEntries[$this->idx];

        $this->ctx->curctx->push('CIndex'                          , $this->idx);
        $this->ctx->curctx->push('SitemapEntryUrl'                 , $entry['url']);
        $this->ctx->curctx->push('SitemapEntryText'                , $entry['text']);
        $this->ctx->curctx->push('SitemapEntryDescription'         , $entry['description']);
        $this->ctx->curctx->push('SitemapEntryId'                  , $entry['id']);
        $this->ctx->curctx->push('SitemapEntryOnclick'             , $entry['onclick']);
        $this->ctx->curctx->push('SitemapEntryFolder'              , $entry['folder'] ? '1' : '0');
        $this->ctx->curctx->push('SitemapEntryPageTitle'           , $entry['pageTitle']);
        $this->ctx->curctx->push('SitemapEntryPageDescription'     , $entry['pageDescription']);
        $this->ctx->curctx->push('SitemapEntryPageKeywords'        , $entry['pageKeywords']);
        $this->ctx->curctx->push('SitemapEntryClassnames'          , $entry['classnames']);
        $this->ctx->curctx->push('SitemapEntryMenuIgnore'          , $entry['menuIgnore']);
        $this->ctx->curctx->push('SitemapEntryBreadCrumbIgnore'    , $entry['breadCrumbIgnore']);
        $this->ctx->curctx->push('SitemapEntryTarget'              , $entry['target']);
        $this->idx++;
        $this->index = $this->idx;
        return true;
    }
}



/**
 * Get child nodes (one level) of the specified node.
 *
 * <OCSitemapEntries sitemap="sitemapName" (node="parentNode" | langid="parentNode")>
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




        if (isset($this->sitemap)) {
            $node = $this->getNode('node', 'langid');

            if ($node) {
                $node = $node->firstChild();
                while($node) {
                    /* @var $item bab_SitemapItem */
                    $item = $node->getData();
                    $tmp = array();

                    $classnames = $item->getIconClassnames();

                    if (isset($this->activeNodes[$item->id_function])) {
                        // the nodes in the current path have the "active" class.
                        $classnames .= ' ' . $this->activeClass;
                    }
                    if ($this->selectedNodeId === $item->id_function) {
                        // the current node has the "selected" class.
                        $classnames .= ' ' . $this->selectedClass;
                    }

                    $tmp['url'] = $item->getRwUrl();
                    $tmp['text'] = $item->name;
                    $tmp['description'] = $item->description;
                    $tmp['id'] = $item->id_function;
                    $tmp['onclick'] = $item->onclick;
                    $tmp['folder'] = $item->folder; 					// set as folder by developper
                    $tmp['hasChildNodes'] = $node->hasChildNodes(); 	// has childnodes in sitemap
                    $tmp['pageTitle'] = $item->getPageTitle();
                    $tmp['pageDescription'] = $item->getPageDescription();
                    $tmp['pageKeywords'] = $item->getPageKeywords();
                    $tmp['classnames'] = $classnames;
                    $tmp['menuIgnore'] = $item->menuIgnore;
                    $tmp['breadCrumbIgnore'] = $item->breadCrumbIgnore;
                    $tmp['target'] = $item->getTarget()->id_function;
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
 * <OCSitemapEntry sitemap="sitemapName" ( node="nodeId" | langid="langId" )>
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


        if (isset($this->sitemap)) {
            $node = $this->getNode('node', 'langid');

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
                $tmp['breadCrumbIgnore'] = $item->breadCrumbIgnore;
                $tmp['target'] = $item->getTarget()->id_function;
                $this->IdEntries[] = $tmp;
            }

            $this->count = count($this->IdEntries);
            $this->ctx->curctx->push('CCount', $this->count);
        }
    }

}







/**
 * Get nodes under basenode with a target
 *
 * <OCSitemapCustomNode [sitemap="sitemapName"] [basenode="node"] target="node" [limit=""]>
 *
 * </OCSitemapCustomNode>
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 *
 * - The limit attribute can be used to limit number of items
 *
 */
class Func_Ovml_Container_SitemapCustomNode extends Ovml_Container_Sitemap
{
    var $IdEntries = array();
    var $index;
    var $count;
    var $data;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);

        $baseNodeId = $this->getBaseNode();

        $this->IdEntries = array();
        $this->count = 0;

        if (isset($this->sitemap)) {

            $targetId = $ctx->curctx->getAttribute('target');


            if (!$targetId) {
                trigger_error(sprintf('the target attribute is mandatory in OCSitemapCustomNode, file %s', (string) $ctx->debug_location));
                $this->ctx->curctx->push('CCount', $this->count);
                return;
            }

            $nodes = $this->sitemap->getNodesByTargetId($baseNodeId, $targetId);
            $this->count = count($nodes);
            $this->ctx->curctx->push('CCount', $this->count);

            foreach ($nodes as $node) {
                $item = $node->getData();
                /* @var $item bab_SitemapItem */
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
                $tmp['breadCrumbIgnore'] = $item->breadCrumbIgnore;
                $tmp['target'] = $item->getTarget()->id_function;

                $this->IdEntries[] = $tmp;
            }
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
 *
 * - The node attribute is optional, it specifies the sitemap id of the node for which the path will be returned.
 * 		The default is the node corresponding to the current page (or the last known page displayed if keeplastknown is active).
 *
 * - The basenode attribute is optional, it will be the starting node used for the <ul> tree.
 * 		The default value is set by api (ex: sitemap_editor).
 *
 * - The keeplastknown attribute is optional, if set to "1", the last accessed sitemap node is kept selected if accessing a page not in the sitemap.
 * 		The default value is '1'.
 *
 * - The limit attribute is optional, if start_node<0 the start node will be computed from the last element. max_nodes must be greater than 0.
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

        $baseNodeId = $this->getBaseNode();


        if (isset($this->sitemap)) {

            $node = $this->getNodeByIdOrLangId('node', 'langid');
            if (isset($node)) {
                $nodeId = $node->getId();
            } else {
                $nodeId = bab_siteMap::getPosition();
            }


            if ($baseNodeId && $nodeId) {
                // if base node (parameter 'basenode') has been specified,
                // we try to find if a descendant of this node has
                // a target to the current position.
                $baseNode = $this->sitemap->getNodeById($baseNodeId);

                if (null === $baseNode) {
                    trigger_error(sprintf('the basenode "%s" has not been found in the sitemap "%s", file %s',$baseNodeId, $this->sitemap_name, (string) $ctx->debug_location));
                } else {

                    if ($customNode = $this->sitemap->getNodeByTargetId($baseNodeId, $nodeId)) {
                        $nodeId = $customNode->getId();
                    }

                }
            }



            if (empty($nodeId)) {
                $keepLastKnown = $ctx->curctx->getAttribute('keeplastknown');
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
                    $tmp['breadCrumbIgnore'] = $item->breadCrumbIgnore;
                    $tmp['target'] = $item->getTarget()->id_function;

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
 * <OFSitemapPosition [sitemap="sitemapName"] [keeplastknown="0|1"] [basenode="nodeid"] [node="nodeid"] >
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
                $html .= sprintf('<li>Broken sitemap node : %s</li>'."\n", bab_toHtml((string) $node));
                continue;
            }


            $sitemapItem = $node->getData();

            if (!$sitemapItem) {
                $html .= sprintf('<li>Broken sitemap node : %s</li>'."\n", bab_toHtml($node->getId()));
                continue;
            }




            if ($sitemapItem->url) {

                if ($sitemapItem->onclick) {
                    $onclick = ' onclick="'.bab_toHtml($sitemapItem->onclick).'"';
                } else {
                    $onclick = '';
                }
                 $html .= '<li class="sitemap-' . bab_toHtml($node->getId()) .'"><a href="' . bab_toHtml($sitemapItem->getRwUrl()) . '" ' . $onclick . '>'
                     . bab_toHtml($sitemapItem->name) . '</a></li>'."\n";

            } else {


                $html .= sprintf('<li class="sitemap-%s"><span>%s</span></li>'."\n",

                    bab_toHtml($node->getId()),
                    bab_toHtml($sitemapItem->name)

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
 * Return the node id of the current page
 *
 * <OFCurrentNode  [saveas=""]>
 */
class Func_Ovml_Function_CurrentNode extends Func_Ovml_Function
{
    /**
     *
     * @return string
     */
    public function toString()
    {
        require_once dirname(__FILE__).'/sitemap.php';

        $nodeId = bab_SiteMap::getPosition();

        return $this->format_output($nodeId, $this->args);
    }
}













/**
 * Return the sitemap menu tree in a html UL LI
 *
 * <OFSitemapMenu
 *      [sitemap="sitemapName"] [baselangid="parentnode"] [basenode="parentNode"]
 *      [selectednode=""] [keeplastknown="0|1"] [maxdepth="depth"] [outerul="1"] [admindelegation="0"]
 *      [editlinkslevel="0"]
 *      >
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
 * - The editlinkslevel attribute is optional, if set, the classes used by the editlinks addon are added up to the level specified in the parameter,
 *      The default value is '0', no editlinks support.
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

    protected	$admindelegation = false;

    protected	$maxDepth = 100;

    private function getHtml(bab_Node $node, $mainmenuclass = null, $depth = 1) {

        global $babBody;
        $return = '';
        $classnames = array();

        $id = $node->getId();
        $siteMapItem = $node->getData();
        /* @var $siteMapItem bab_siteMapItem */

        if($siteMapItem->target){
            $truncateId = $siteMapItem->target->id_function;
            if($this->admindelegation
                && !isset($this->delegAdmin[bab_getCurrentAdmGroup()][$truncateId])
                && (substr($truncateId, 0, 8) == 'babAdmin' || $truncateId == 'babSearchIndex') && $truncateId != 'babAdmin')
            {
                //bab_debug($siteMapItem->target->id_function. ' == '.$siteMapItem->id_function);
                return $return;
            }
        }


        $additional_classes = array();
        $additional_classes[] = 'niv'.$depth;

        if (isset($this->activeNodes[$siteMapItem->id_function])) {
            // the nodes in the current path have the "active" class.
            $additional_classes[] = $this->activeClass;
        }
        if ($this->selectedNodeId === $siteMapItem->id_function) {
            // the current node has the "selected" class.
            $additional_classes[] = $this->selectedClass;
        }

        if (isset($this->args['editlinkslevel']) && $this->args['editlinkslevel'] >= $depth) {
            $additional_classes[] = 'smed-sitemapnode-'.$siteMapItem->id_function;
        }


        $ul = null;

        if ($node->hasChildNodes() && $depth < $this->maxDepth) {
            $ul = "<ul class=\"niv".($depth + 1)."\">\n";

            $node = $node->firstChild();
            do {
                if (!$node->getData()->menuIgnore)
                {
                    $ul .= $this->getHtml($node, null, $depth + 1);
                }
            } while ($node = $node->nextSibling());

            $ul .= "</ul>\n";
        }


        return $siteMapItem->getHtmlListItem($ul, $additional_classes);
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
        if( (isset($args['admindelegation']) && $args['admindelegation'] == '1' ) && bab_getCurrentAdmGroup() != 0 && !isset($this->delegAdmin[bab_getCurrentAdmGroup()]))
        {
            $this->admindelegation = $args['admindelegation'];
            $delegation = bab_getDelegationById(bab_getCurrentAdmGroup());
            $delegation = $delegation[0];
            foreach(bab_getDelegationsObjects() as $link)
            {
                if (!isset($link[3]))
                {
                    continue;
                }

                if ($delegation[$link[0]] === 'Y')
                {
                    $this->delegAdmin[bab_getCurrentAdmGroup()]['bab'.$link[2]] = true;
                }
            }
            $dgAdmGroups = bab_getDgAdmGroups();
            if( count($dgAdmGroups) > 0) {
                $this->delegAdmin[bab_getCurrentAdmGroup()]['babAdminDelegChange'] = true;
            }
            $this->delegAdmin[bab_getCurrentAdmGroup()]['babAdminGroups'] = true;
            $this->delegAdmin[bab_getCurrentAdmGroup()]['babAdminUsers'] = true;
            $this->delegAdmin[bab_getCurrentAdmGroup()]['babAdminSection'] = true;
        }

        $this->sitemap = $sitemap;

        $dg_node = $sitemap->firstChild();

        if (!($dg_node instanceOf bab_Node)) {
            return '';
        }



        if (!empty($args['baselangid'])) {
            $baselangNode = $sitemap->getNodeByLangId(bab_getLanguage(), $args['baselangid']);
            if (isset($baselangNode)) {
                $args['basenode'] = $baselangNode->getId();
            }
        }


        if (empty($args['basenode']))
        {
            $args['basenode'] = bab_siteMap::getVisibleRootNodeByUid($sitemap_name);
        }
        $args['basenode'] = str_replace(' ', '', $args['basenode']);
        $home = $sitemap->getNodeById($args['basenode']);
        $baseNodeId = $args['basenode'];


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
            $selectedNodeId = bab_siteMap::getPosition();


            // if base node (parameter 'basenode') has been specified,
            // we try to find if a descendant of this node has
            // a target to the current position.
            if ($customNode = $this->sitemap->getNodeByTargetId($baseNodeId, $selectedNodeId))
            {
                $selectedNodeId = $customNode->getId();
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
            // If outerul is not specified, active by default
            $outerUl = 1;
        } else {
            $outerUl = $args['outerul'];
        }

        if ($node) {

            if ($outerUl) {
                $return .= '<ul class="sitemap-menu-root">'."\n";
            }

            do {
                if (!$node->getData()->menuIgnore)
                {
                    $return .= $this->getHtml($node, 'sitemap-main-menu');
                }
            } while ($node = $node->nextSibling());

            if ($outerUl) {
                $return .= '</ul>'."\n";
            }
        }
        return $return;
    }
}










/**
 * Return the rewriten url if available in sitemap or the url in parameter
 * <OFSitemapUrl [sitemap="sitemapName"] url="">
 *
 * - The sitemap attribute is optional.
 * 		The default value is the sitemap selected in Administration > Sites > Site configuration.
 *
 */
class Func_Ovml_Function_SitemapUrl extends Func_Ovml_Function
{



    /**
     *
     * @return string
     */
    public function toString()
    {
        $args = $this->args;

        $sitemap_uid = empty($args['sitemap']) ? null : $args['sitemap'];
        $url = empty($args['url']) ? null : $args['url'];

        if (null === $sitemap_uid)
        {
            global $babBody;
            $sitemap_uid = $babBody->babsite['sitemap'];
        }

        $rootNode = bab_sitemap::getByUid($sitemap_uid);

        if (isset($args['sitemap']))
        {
            unset($args['sitemap']);
        }

        unset($args['url']);

        if (!isset($rootNode)) {
            bab_debug(sprintf('incorrect sitemap used in OVML %s sitemap="%s"', get_class($this), $sitemap_uid));
            return $this->format_output($url, $args);
        }


        if ($nodes = $rootNode->getNodesByIndex('url', $url))
        {
            $node = reset($nodes);
            $sitemapItem = $node->getData();
            /*@var $sitemapItem bab_SitemapItem */
            $url = $sitemapItem->getRwUrl();
        }

        return $this->format_output($url, $args);
    }
}





/**
 * Return the sitemap node ID found in the current custom sitemap with a target to the node given in parameter from the core sitemap
 * or the nodeid if the custom node does not exists
 *
 * <OFSitemapCustomNodeId node="" [basenode=""] [saveas=""]>
 *
 *
 */
class Func_Ovml_Function_SitemapCustomNodeId extends Func_Ovml_Function
{
    /**
     *
     * @return string
     */
    public function toString()
    {
        $args = $this->args;
        $nodeid = empty($args['node']) ? null : $args['node'];
        $basenode = empty($args['basenode']) ? null : $args['basenode'];

        if (null === $nodeid)
        {
            trigger_error(sprintf('Missing attribute nodeid in %s#%s', (string) $this->template->debug_location, get_class($this)));
            return $this->output('');
        }


        $coreRootNode = bab_sitemap::get(); // core sitemap
        $node = $coreRootNode->getNodeById($nodeid);

        if (null === $node)
        {
            trigger_error(sprintf('Node not found in core sitemap in %s#%s nodeid="%s"', (string) $this->template->debug_location, get_class($this), $nodeid));
            return $this->output('');
        }


        if (null === $basenode)
        {
            $basenode = bab_siteMap::getSitemapRootNode();
        }

        require_once dirname(__FILE__).'/settings.class.php';
        $settings = bab_getInstance('bab_Settings');
        /*@var $settings bab_Settings */
        $site = $settings->getSiteSettings();

        if ('core' === $site['sitemap'])
        {
            // custom sitemap is the core sitemap
            return $this->output($nodeid);
        }

        $customRootNode = bab_siteMap::getByUid($site['sitemap']);
        $customNode = $customRootNode->getNodeByTargetId($basenode, $nodeid);

        if (null === $customNode)
        {
            return $this->output($nodeid);
        }

        return $this->output($customNode->getId());
    }

    /**
     * Process function output
     * @param string $str
     */
    private function output($str)
    {
        $args = $this->args;
        $saveas = empty($args['saveas']) ? null : $args['saveas'];

        if (isset($saveas))
        {
            $this->gctx->push($saveas, $str);
            return ''; // do not display value if saved
        }

        return $str;
    }
}
