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

require_once dirname(__FILE__).'/userincl.php';
require_once dirname(__FILE__).'/sitemap.php';


define('BAB_TAG_CONTAINER', 'OC');
define('BAB_TAG_VARIABLE', 'OV');
define('BAB_TAG_FUNCTION', 'OF');


define('BAB_OPE_EQUAL'				, 1);
define('BAB_OPE_NOTEQUAL'			, 2);
define('BAB_OPE_LESSTHAN'			, 3);
define('BAB_OPE_LESSTHANOREQUAL'	, 4);
define('BAB_OPE_GREATERTHAN'		, 5);
define('BAB_OPE_GREATERTHANOREQUAL'	, 6);



/**
 * OVML root functionality
 *
 *
 */
class Func_Ovml extends bab_functionality
{
    public function getDescription()
    {
        return bab_translate('Ovidentia Markup language');
    }
}



/**
 * OVML containers root functionality
 * Replace the old bab_handler
 */
class Func_Ovml_Container extends Func_Ovml
{

    /**
     * The ovml template object
     * Warning, this is not a context
     *
     * @var babOvTemplate
     */
    public $ctx;

    /**
     * index of the loop
     *
     * @var int
     */
    public $idx;


    /**
     * Method called on context initialization
     *
     * @param babOvTemplate $ctx
     *            The ovml template object
     *
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->ctx = $ctx;
        $this->idx = 0;
    }

    /**
     * default description of OVML functionalities
     *
     * @see utilit/bab_functionality#getDescription()
     */
    public function getDescription()
    {
        if (__CLASS__ === get_class($this)) {
            return bab_translate('All OVML containers');
        }

        $classname = explode('_', get_class($this));
        return BAB_TAG_CONTAINER . end($classname);
    }


    public function printout($txt)
    {
        $this->ctx->push_handler($this);
        $res = '';
        $skip = false;
        while ($this->getnext($skip)) {
            if (! $skip)
                $res .= $this->ctx->handle_text($txt);
            $skip = false;
        }
        $this->ctx->pop_handler();
        return $res;
    }


    public function printoutws()
    {
        $this->ctx->push_handler($this);
        $res = array();
        $skip = false;
        while ($this->getnext($skip)) {
            $tmparr = array();
            if (! $skip) {
                foreach ($this->ctx->get_variables($this->ctx->get_currentContextname()) as $key => $val) {
                    $tmparr[] = array(
                        'name' => $key,
                        'value' => $val
                    );
                }
            }
            $res[] = $tmparr;
            $skip = false;
        }
        $this->ctx->pop_handler();
        return $res;
    }


    /**
     * Fetch the next container's element.
     *
     * @return bool True if an element has been fetched, false if the container has reached the end.
     */
    public function getnext()
    {
        return false;
    }


    /**
     * Push editor content into context and apply editor transformations
     *
     * @param string $var
     *            OVML variable name
     * @param string $txt
     * @param string $txtFormat
     *            The text format (html, text...) corresponding to the format of the wysiwyg editor.
     * @param string $editor
     *            editor ID
     */
    protected function pushEditor($var, $txt, $txtFormat, $editor)
    {
        include_once $GLOBALS['babInstallPath'] . "utilit/editorincl.php";
        $editor = new bab_contentEditor($editor);
        $editor->setContent($txt);
        $editor->setFormat($txtFormat);
        $txt = $editor->getHtml();

        $this->ctx->curctx->push($var, $txt);

        if ('html' === strtolower($txtFormat)) {
            $this->ctx->curctx->setFormat($var, bab_context::HTML);
        }
    }
}





/**
 * OVML containers root functionality
 * Replace the old bab_handler
 */
class Func_Ovml_Function extends Func_Ovml
{
    /**
     * @var babOvTemplate
     */
    public $template;

    /**
     * @var bab_context
     */
    protected $gctx;

    protected $args = array();


    /**
     * default description of OVML functionalities
     *
     * @see utilit/bab_functionality#getDescription()
     */
    public function getDescription()
    {
        if (__CLASS__ === get_class($this)) {
            return bab_translate('All OVML functions');
        }

        $classname = explode('_', get_class($this));
        return BAB_TAG_FUNCTION . end($classname);
    }


    /**
     *
     * @param babOvTemplate $template
     * @return Func_Ovml_Function
     */
    public function setTemplate(babOvTemplate $template)
    {
        $this->template = $template;
        $this->gctx = $template->gctx;

        return $this;
    }

    /**
     *
     * @param array $args
     * @return Func_Ovml_Function
     */
    public function setArgs($args)
    {
        $this->args = $args;
        return $this;
    }


    protected function format_output($val, $matches, $format = bab_context::TEXT)
    {
        return $this->template->format_output($val, $matches, $format, $this->getDescription());
    }


    public function cast($str)
    {
        return $this->template->cast($str);
    }
}



class Func_Ovml_Container_IfIsSet extends Func_Ovml_Container
{
    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $name = $ctx->curctx->getAttribute('name');
        if ($name !== false && ! empty($name)) {
            if ($ctx->getVariable($name) !== false) {
                $this->count = 1;
            }
        }
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->idx ++;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_IfNotIsSet extends Func_Ovml_Container
{
    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $name = $ctx->curctx->getAttribute('name');
        if( $name !== false && !empty($name))
            {
            if( $ctx->getVariable($name) === false )
                {
                $this->count = 1;
                }
            }
    }


    public function getnext()
    {
        if( $this->idx < $this->count)
        {
            $this->idx++;
            return true;
        }
        else
        {
            $this->idx=0;
            return false;
        }
    }
}



class bab_Ovml_Container_Operator extends Func_Ovml_Container
{
    public $count;

    protected $operator = null;


    /**
     *
     * {@inheritdoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $expr1 = $ctx->curctx->getAttribute('expr1');
        $expr2 = $ctx->curctx->getAttribute('expr2');
        if ($expr1 !== false && $expr2 !== false) {
            switch ($this->operator) {
                case BAB_OPE_EQUAL:
                    if ($expr1 == $expr2) {
                        $this->count = 1;
                    }
                    break;
                case BAB_OPE_NOTEQUAL:
                    if ($expr1 != $expr2) {
                        $this->count = 1;
                    }
                    break;
                case BAB_OPE_LESSTHAN:
                    if ($expr1 < $expr2) {
                        $this->count = 1;
                    }
                    break;
                case BAB_OPE_LESSTHANOREQUAL:
                    if ($expr1 <= $expr2) {
                        $this->count = 1;
                    }
                    break;
                case BAB_OPE_GREATERTHAN:
                    if ($expr1 > $expr2) {
                        $this->count = 1;
                    }
                    break;
                case BAB_OPE_GREATERTHANOREQUAL:
                    if ($expr1 >= $expr2) {
                        $this->count = 1;
                    }
                    break;
                default:
                    break;
            }
        }
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->idx ++;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_IfEqual extends bab_Ovml_Container_Operator
{
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->operator = BAB_OPE_EQUAL;
        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_IfNotEqual extends bab_Ovml_Container_Operator
{
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->operator = BAB_OPE_NOTEQUAL;
        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_IfLessThan extends bab_Ovml_Container_Operator
{
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->operator = BAB_OPE_LESSTHAN;
        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_IfLessThanOrEqual extends bab_Ovml_Container_Operator
{
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->operator = BAB_OPE_LESSTHANOREQUAL;
        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_IfGreaterThan extends bab_Ovml_Container_Operator
{
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->operator = BAB_OPE_GREATERTHAN;
        parent::setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_IfGreaterThanOrEqual extends bab_Ovml_Container_Operator
{
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->operator = BAB_OPE_GREATERTHANOREQUAL;
        parent::setOvmlContext($ctx);
    }
}



class Func_Ovml_Container_Addon extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);
        $name = $ctx->curctx->getAttribute('name');
        $addon = bab_getAddonInfosInstance($name);

        if ($addon && $addon->isAccessValid()) {
            if (is_file($addon->getPhpPath() . "ovml.php")) {
                /* save old vars */
                $this->AddonFolder = isset($GLOBALS['babAddonFolder']) ? $GLOBALS['babAddonFolder'] : '';
                $this->AddonTarget = isset($GLOBALS['babAddonTarget']) ? $GLOBALS['babAddonTarget'] : '';
                $this->AddonUrl = isset($GLOBALS['babAddonUrl']) ? $GLOBALS['babAddonUrl'] : '';
                $this->AddonPhpPath = isset($GLOBALS['babAddonPhpPath']) ? $GLOBALS['babAddonPhpPath'] : '';
                $this->AddonHtmlPath = isset($GLOBALS['babAddonHtmlPath']) ? $GLOBALS['babAddonHtmlPath'] : '';
                $this->AddonUpload = isset($GLOBALS['babAddonUpload']) ? $GLOBALS['babAddonUpload'] : '';

                bab_setAddonGlobals($addon->getId());
                require_once ($addon->getPhpPath() . "ovml.php");

                $call = $addon->getName() . "_ovml";
                if (! empty($call) && function_exists($call)) {
                    $this->IdEntries = $call($ctx->curctx->attributes);
                }
            }
        }
        $this->count = count($this->IdEntries);
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            foreach ($this->IdEntries[$this->idx] as $name => $val) {
                if (is_object($val) && isset($val->format)) {
                    $this->ctx->curctx->push($name, $val->value);
                    $this->ctx->curctx->setFormat($name, $val->format);
                } else {
                    $this->ctx->curctx->push($name, $val);
                }
            }
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            if (isset($this->AddonFolder)) {
                $GLOBALS['babAddonFolder'] = $this->AddonFolder;
                $GLOBALS['babAddonTarget'] = $this->AddonTarget;
                $GLOBALS['babAddonUrl'] = $this->AddonUrl;
                $GLOBALS['babAddonPhpPath'] = $this->AddonPhpPath;
                $GLOBALS['babAddonHtmlPath'] = $this->AddonHtmlPath;
                $GLOBALS['babAddonUpload'] = $this->AddonUpload;
            }
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_ObjectsInfo extends Func_Ovml_Container
{
    public $res;

    public $fields = array();

    public $ovmlfields = array();

    public $index;

    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->count = 0;
        $type = $ctx->curctx->getAttribute('type');
        if ($type !== false && $type !== '') {
            $type = mb_strtolower(trim($type));
            switch ($type) {
                case 'folder':
                    $folder = $ctx->curctx->getAttribute('folder');
                    if ($folder !== false && $folder !== '') {
                        $this->fields = array(
                            'id',
                            'folder'
                        );
                        $this->ovmlfields = array(
                            'Id',
                            'Folder'
                        );
                        $this->res = $babDB->db_query('select id, folder from ' . BAB_FM_FOLDERS_TBL . ' where folder=\'' . $babDB->db_escape_string($folder) . '\'');
                        $this->count = $babDB->db_num_rows($this->res);
                    }
                case 'articlecategories':
                    $category = $ctx->curctx->getAttribute('category');
                    if ($category !== false && $category !== '') {
                        $parent = $ctx->curctx->getAttribute('parent');
                        $parents = array();
                        if ($parent !== false && $parent !== '') {
                            $parents = array_reverse(explode('/', $parent));
                        }
                        $this->fields = array(
                            'id',
                            'title'
                        );
                        $this->ovmlfields = array(
                            'Id',
                            'Category'
                        );
                        $leftjoin = '';
                        $where = ' where c0.title=\'' . $babDB->db_escape_string($category) . '\'';
                        $req = 'select c0.title, c0.id from ' . BAB_TOPICS_CATEGORIES_TBL . ' c0';
                        for ($k = 0; $k < count($parents); $k ++) {
                            $leftjoin .= ' left join ' . BAB_TOPICS_CATEGORIES_TBL . ' c' . ($k + 1) . ' on c' . ($k + 1) . '.id = c' . $k . '.id_parent';
                            $where .= ' and  c' . ($k + 1) . '.title=\'' . $babDB->db_escape_string($parents[$k]) . '\'';
                        }
                        $this->res = $babDB->db_query($req . $leftjoin . $where);
                        $this->count = $babDB->db_num_rows($this->res);
                    }
                    break;
                case 'articletopics':
                    $topic = $ctx->curctx->getAttribute('topic');
                    if ($topic !== false && $topic !== '') {
                        $parent = $ctx->curctx->getAttribute('parent');
                        $parents = array();
                        if ($parent !== false && $parent !== '') {
                            $parents = array_reverse(explode('/', $parent));
                        }
                        $this->fields = array(
                            'id',
                            'category'
                        );
                        $this->ovmlfields = array(
                            'Id',
                            'Topic'
                        );
                        $leftjoin = '';
                        $where = ' where c0.category=\'' . $babDB->db_escape_string($topic) . '\'';
                        $req = 'select c0.category, c0.id from ' . BAB_TOPICS_TBL . ' c0';
                        for ($k = 0; $k < count($parents); $k ++) {
                            $leftjoin .= ' left join ' . BAB_TOPICS_CATEGORIES_TBL . ' c' . ($k + 1) . ' on c' . ($k + 1) . '.id = c' . $k . ($k == 0 ? '.id_cat' : '.id_parent');
                            $where .= ' and  c' . ($k + 1) . '.title=\'' . $babDB->db_escape_string($parents[$k]) . '\'';
                        }
                        $this->res = $babDB->db_query($req . $leftjoin . $where);
                        $this->count = $babDB->db_num_rows($this->res);
                    }
                    break;
                case 'user':
                    $nickname = $ctx->curctx->getAttribute('nickname');
                    if ($nickname !== false && $nickname !== '') {
                        $this->fields = array(
                            'id',
                            'nickname',
                            'firstname',
                            'lastname',
                            'mn'
                        );
                        $this->ovmlfields = array(
                            'Id',
                            'Nickname',
                            'Firstname',
                            'Lastname',
                            'Middlename'
                        );
                        $this->res = $babDB->db_query('select u.id, u.nickname, u.firstname, u.lastname, d.mn from ' . BAB_USERS_TBL . ' u left join ' . BAB_DBDIR_ENTRIES_TBL . ' d on u.id=d.id_user where d.id_directory=0 and u.nickname=\'' . $babDB->db_escape_string($nickname) . '\'');
                        $this->count = $babDB->db_num_rows($this->res);
                    }
                    break;
                default:
                    break;
            }
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            for ($k = 0; $k < count($this->fields); $k ++) {
                $this->ctx->curctx->push('Object' . $this->ovmlfields[$k], $arr[$this->fields[$k]]);
            }
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



/**
 * <OCTags module="files|articles" type="file|article|draft" objectid="">
 * 		<OVTagName>
 * 		<OVTagSearchUrl>
 * </OCTags>
 */
class Func_Ovml_Container_Tags extends Func_Ovml_Container
{
    private $iterator;


    /**
     *
     * {@inheritdoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        parent::setOvmlContext($ctx);
        $this->count = 0;
        $module = $ctx->curctx->getAttribute('module');
        $type = $ctx->curctx->getAttribute('type');
        $objectid = $ctx->curctx->getAttribute('objectid');

        require_once dirname(__FILE__) . '/tagApi.php';

        $oReferenceMgr = bab_getInstance('bab_ReferenceMgr');

        $this->iterator = $oReferenceMgr->getTagsByReference(bab_Reference::makeReference('ovidentia', '', $module, $type, $objectid));
        $this->iterator->orderAsc('tag_name');

        $this->ctx->curctx->push('CCount', $this->iterator->count());
        $this->iterator->rewind();
        $this->idx = 0;
    }


    public function getnext()
    {
        if ($this->iterator->valid()) {

            $this->ctx->curctx->push('CIndex', $this->idx);

            $tag = $this->iterator->current();

            $this->ctx->curctx->push('TagName', $tag->getName());

            $searchUi = bab_functionality::get('SearchUi');
            /*@var $searchUi Func_SearchUi */


            $this->ctx->curctx->push('TagSearchUrl', $searchUi->getUrl('tags', $tag->getName()));

            $this->iterator->next();
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            $this->iterator->rewind();
            return false;
        }
    }
}




class Func_Ovml_Container_IfUserMemberOfGroups extends Func_Ovml_Container
{
    public $res;

    public $IdEntries = array();

    public $index;

    public $count;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->count = 0;

        $userid = $ctx->curctx->getAttribute('userid');
        if ($userid === false) {
            $userid = $GLOBALS['BAB_SESS_USERID'];
        }


        if ($userid != "") {
            $all = $ctx->curctx->getAttribute('all');

            if ($all !== false && mb_strtoupper($all) == "YES")
                $all = true;
            else
                $all = false;

            $groupid = $ctx->curctx->getAttribute('groupid');
            if ($groupid !== false && $groupid !== '') {
                $groupid = explode(',', $groupid);
            } else {
                $groupid = array();
            }

            $childs = $ctx->curctx->getAttribute('childs');
            if ($childs !== false && mb_strtoupper($childs) == "YES") {
                include_once $GLOBALS['babInstallPath'] . "utilit/grptreeincl.php";
                $rr = $groupid;
                $tree = new bab_grptree();
                for ($k = 0; $k < count($rr); $k ++) {
                    $groups = $tree->getChilds($rr[$k], 1);
                    if (is_array($groups) && count($groups) > 0) {
                        foreach ($groups as $arr) {
                            if (! in_array($arr['id'], $rr)) {
                                $groupid[] = $arr['id'];
                            }
                        }
                    }
                }
            }

            if (count($groupid)) {
                list ($total) = $babDB->db_fetch_row($babDB->db_query("select count(id) as total from " . BAB_USERS_GROUPS_TBL . " where id_object='" . $babDB->db_escape_string($userid) . "' and id_group IN (" . $babDB->quote($groupid) . ")"));
                if ($all == false) {
                    if ($total) {
                        $this->count = 1;
                    }
                } else {
                    if ($total >= count($groupid)) {
                        $this->count = 1;
                    }
                }
            }
        }
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->idx ++;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_OvmlArray extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;

    public $data;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $this->name = $ctx->curctx->getAttribute('name');
        $value = $ctx->curctx->getAttribute('value');
        $m2 = null;
        if (preg_match_all("/(.*?)\[([^\]]+)\]/", $value, $m2) > 0) {
            $this->IdEntries = $ctx->get_value($m2[1][0]);
            for ($t = 0; $t < count($m2[2]); $t ++) {
                if (isset($this->IdEntries[$m2[2][$t]])) {
                    $this->IdEntries = $this->IdEntries[$m2[2][$t]];
                } else
                    break;
            }
        } else {
            $this->IdEntries = $ctx->get_value($value);
        }
        if (is_array($this->IdEntries)) {
            $this->ctx->curctx->push($this->name, $this->IdEntries);
            $this->count = count($this->IdEntries);
        } else {
            $this->count = 0;
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            list ($key, $val) = each($this->IdEntries);
            $this->ctx->curctx->push($this->name . 'Key', $key);
            $this->ctx->curctx->push($this->name . 'Value', $val);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_OvmlArrayFields extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;

    public $data;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $this->name = $ctx->curctx->getAttribute('name');
        $value = $ctx->curctx->getAttribute('value');
        $m2 = null;
        if (preg_match_all("/(.*?)\[([^\]]+)\]/", $value, $m2) > 0) {
            $this->IdEntries = $ctx->get_value($m2[1][0]);
            for ($t = 0; $t < count($m2[2]); $t ++) {
                if (isset($this->IdEntries[$m2[2][$t]])) {
                    $this->IdEntries = $this->IdEntries[$m2[2][$t]];
                } else
                    break;
            }
        } else {
            $this->IdEntries = $ctx->get_value($value);
        }

        if (is_array($this->IdEntries)) {
            $this->ctx->curctx->push($this->name, $this->IdEntries);
            $this->count = 1; // count($this->IdEntries);
        } else {
            $this->count = 0;
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        if ($this->idx < $this->count) {
            foreach ($this->IdEntries as $key => $val) {
                $this->ctx->curctx->push($key, $val);
            }
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_OvmlSoap extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;

    public $data;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 1;
        parent::setOvmlContext($ctx);
        $vars = $ctx->get_variables($ctx->get_currentContextname());
        if (isset($vars['apiserver']) && isset($vars['container'])) {
            $apiserver = $vars['apiserver'];
            unset($vars['apiserver']);
            $args = array();
            $args['container'] = $vars['container'];
            unset($vars['container']);
            if (isset($vars['debug'])) {
                $debug = $vars['debug'];
                unset($vars['debug']);
            } else {
                $debug = false;
            }

            if (isset($vars['proxyhost'])) {
                $proxyhost = $vars['proxyhost'];
                unset($vars['proxyhost']);
                if (isset($vars['proxyport'])) {
                    $proxyport = $vars['proxyport'];
                    unset($vars['proxyport']);
                } else {
                    $proxyport = false;
                }
                if (isset($vars['proxyusername'])) {
                    $proxyusername = $vars['proxyusername'];
                    unset($vars['proxyusername']);
                } else {
                    $proxyusername = false;
                }
                if (isset($vars['proxypassword'])) {
                    $proxypassword = $vars['proxypassword'];
                    unset($vars['proxypassword']);
                } else {
                    $proxypassword = false;
                }
            } else {
                $proxyhost = false;
            }

            $args['args'] = array();
            foreach ($vars as $key => $val) {
                $args['args'][] = array(
                    'name' => $key,
                    'value' => $val
                );
            }


            include_once $GLOBALS['babInstallPath'] . "utilit/nusoap/nusoap.php";

            if (! empty($proxyhost)) {
                $soapclient = new nusoap_client($apiserver, false, $proxyhost, $proxyport, $proxyusername, $proxypassword);
            } else {
                $soapclient = new nusoap_client($apiserver);
            }
            $this->IdEntries = $soapclient->call('babSoapOvml', $args, '');
            $err = $soapclient->getError();
            if ($debug) {
                $this->ctx->curctx->push('babSoapDebug', $soapclient->getDebug());
            }
            bab_debug($soapclient->getDebug());
            if ($err) {
                $this->ctx->curctx->push('babSoapError', $err);
                $this->ctx->curctx->push('babSoapResponse', $soapclient->response);
                $this->ctx->curctx->push('babSoapRequest', $soapclient->request);
                if ($soapclient->fault) {
                    foreach ($this->IdEntries as $key => $val) {
                        $this->ctx->curctx->push($key, $val);
                    }
                }
            }

            $this->count = count($this->IdEntries);
        }
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            for ($i = 0; $i < count($this->IdEntries[$this->idx]); $i ++) {
                $this->ctx->curctx->push($this->IdEntries[$this->idx][$i]['name'], $this->IdEntries[$this->idx][$i]['value']);
            }
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_Soap extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;

    public $data;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 1;
        parent::setOvmlContext($ctx);
        $vars = $ctx->get_variables($ctx->get_currentContextname());
        if (isset($vars['apiserver']) && isset($vars['apicall'])) {
            $apiserver = $vars['apiserver'];
            unset($vars['apiserver']);
            $apicall = $vars['apicall'];
            unset($vars['apicall']);
            if (isset($vars['debug'])) {
                $debug = $vars['debug'];
                unset($vars['debug']);
            } else {
                $debug = false;
            }
            if (isset($vars['apinamespace'])) {
                $apinamespace = $vars['apinamespace'];
                unset($vars['apinamespace']);
            } else {
                $apinamespace = '';
            }

            $soapAction = '';
            $headers = false;
            $style = 'rpc';
            $use = 'encoded';
            if (isset($vars['soapaction'])) {
                $soapAction = $vars['soapaction'];
                unset($vars['soapaction']);
            }
            if (isset($vars['headers'])) {
                $headers = $vars['headers'];
                unset($vars['soapaction']);
            }
            if (isset($vars['style'])) {
                $style = $vars['style'];
                unset($vars['style']);
            }
            if (isset($vars['use'])) {
                $use = $vars['use'];
                unset($vars['use']);
            }

            if (isset($vars['proxyhost'])) {
                $proxyhost = $vars['proxyhost'];
                unset($vars['proxyhost']);
                if (isset($vars['proxyport'])) {
                    $proxyport = $vars['proxyport'];
                    unset($vars['proxyport']);
                } else {
                    $proxyport = false;
                }
                if (isset($vars['proxyusername'])) {
                    $proxyusername = $vars['proxyusername'];
                    unset($vars['proxyusername']);
                } else {
                    $proxyusername = false;
                }
                if (isset($vars['proxypassword'])) {
                    $proxypassword = $vars['proxypassword'];
                    unset($vars['proxypassword']);
                } else {
                    $proxypassword = false;
                }
            } else {
                $proxyhost = false;
            }


            $args = array();
            foreach ($vars as $key => $val) {
                $args[$key] = $val;
            }

            include_once $GLOBALS['babInstallPath'] . "utilit/nusoap/nusoap.php";
            if (! empty($proxyhost)) {
                $soapclient = new nusoap_client($apiserver, false, $proxyhost, $proxyport, $proxyusername, $proxypassword);
            } else {
                $soapclient = new nusoap_client($apiserver);
            }
            $this->IdEntries = $soapclient->call($apicall, $args, $apinamespace, $soapAction, $headers, null, $style, $use);
            $err = $soapclient->getError();
            if ($debug) {
                $this->ctx->curctx->push('babSoapDebug', $soapclient->getDebug());
            }
            bab_debug($soapclient->getDebug());

            if ($err) {
                $this->ctx->curctx->push('babSoapError', $err);
                $this->ctx->curctx->push('babSoapResponse', $soapclient->response);
                $this->ctx->curctx->push('babSoapRequest', $soapclient->request);
                if ($soapclient->fault) {
                    foreach ($this->IdEntries as $key => $val) {
                        $this->ctx->curctx->push($key, $val);
                    }
                }
            } else {
                $this->ctx->curctx->push('SoapResult', $this->IdEntries);
                // print_r($this->IdEntries);
            }
            // $this->count = count($this->IdEntries);
        }
    }

    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->idx ++;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_Multipages extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $index;

    public $count;

    public $data;


    /**
     *
     * {@inheritdoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $total = $ctx->curctx->getAttribute('total');

        $maxpages = $ctx->curctx->getAttribute('maxpages');
        $perpage = $ctx->curctx->getAttribute('perpage');
        $currentpage = $ctx->curctx->getAttribute('currentpage');
        if (false === $currentpage || ! is_numeric($currentpage)) {
            $currentpage = 1;
        }

        if (false !== $total && is_numeric($total)) {
            if (false === $perpage || ! is_numeric($perpage)) {
                $perpage = $total;
            }

            $total_pages = ceil($total / $perpage);

            if (false === $maxpages || ! is_numeric($maxpages)) {
                $maxpages = $total_pages;
            }

            $tmp = array();
            for ($k = 0; $k < $maxpages && $currentpage + $k <= $total_pages; $k ++) {
                $tmp['CurrentPageNumber'] = $currentpage + $k;
                if ($currentpage + $k + 1 > $total_pages) {
                    $tmp['NextPageNumber'] = '';
                } else {
                    $tmp['NextPageNumber'] = $currentpage + $k + 1;
                }
                if ($currentpage + $k > 1 && $total_pages > 1) {
                    $tmp['PreviousPageNumber'] = $currentpage + $k - 1;
                } else {
                    $tmp['PreviousPageNumber'] = '';
                }

                $tmp['TotalPages'] = $total_pages;
                $tmp['ResultFirst'] = (($currentpage + $k - 1) * $perpage) + 1;
                if ($currentpage + $k < $total_pages) {
                    $tmp['ResultLast'] = $tmp['ResultFirst'] + $perpage - 1;
                } else {
                    $tmp['ResultLast'] = $total;
                }

                $tmp['ResultsPage'] = $tmp['ResultLast'] - $tmp['ResultFirst'] + 1;
                $this->IdEntries[] = $tmp;
            }
        }

        $this->count = count($this->IdEntries);
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            foreach ($this->IdEntries[$this->idx] as $key => $val) {
                $this->ctx->curctx->push($key, $val);
            }
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



/**
 */
class bab_context
{

    const TEXT = 0;

    const HTML = 1;


    /**
     * Name of context
     * This will contain bab_main or the container name without OC
     *
     * @var string
     */
    public $name;


    /**
     * List of variables and attributes merged
     *
     * @since 8.4.93
     *
     * @var array
     */
    public $values = array();

    /**
     * List of variable inside a context
     * attributes values are also added to variables list for historical reason
     *
     * @since 8.4.93
     *
     * @var array
     */
    public $variables = array();


    /**
     * List of attributes in the current context
     * This is for one container only and should be used by container classes instead of the
     * variable to prevent names conflicts
     *
     * @var array
     */
    public $attributes = array();


    /**
     *
     * @var string
     */
    public $content;


    /**
     * storage for variable content format
     *
     * @var array
     */
    private $format = array();

    public function bab_context($name)
    {
        $this->name = $name;
    }

    /**
     * Push a new variable in the context
     *
     * @param string $var
     * @param string $value
     */
    public function push($var, $value)
    {
        $this->variables[$var] = $value;
        $this->values[$var] = $value;
    }

    /**
     * Push a new attribute to context
     * in ovidentia before 8.4.93, attributes where pushed to the variables array
     *
     * @since 8.4.93
     *
     * @param string $var
     * @param string $value
     */
    public function addAttribute($var, $value)
    {
        $this->attributes[$var] = $value;
        $this->values[$var] = $value;
    }


    /**
     * Method to get a safe variable value
     *
     * @since 8.4.93
     *
     * @return mixed | false
     */
    public function getVariable($var)
    {
        if (! isset($this->variables[$var])) {
            return false;
        }

        return $this->variables[$var];
    }


    /**
     * Method to use in containers classes to get a safe attribute value
     *
     * @since 8.4.93
     *
     * @return string | false
     */
    public function getAttribute($var)
    {
        if (! isset($this->attributes[$var])) {
            return false;
        }

        return $this->attributes[$var];
    }


    /**
     * Set optional format of content on a variable (optional)
     *
     * @param string $var
     * @param int $format
     *            bab_context::TEXT | bab_context::HTML
     * @return self
     */
    public function setFormat($var, $format)
    {
        $this->format[$var] = $format;
        return $this;
    }

    public function pop()
    {
        return array_pop($this->variables);
    }

    public function setContent($txt)
    {
        $this->content = $txt;
    }

    public function getcontent()
    {
        return $this->content;
    }

    /**
     * Get value in context
     *
     * @deprecated use getVariable or getAttribute instead
     *
     * @param string $var
     * @return mixed | false
     */
    public function get($var)
    {
        if (isset($this->values[$var])) {
            return $this->values[$var];
        }

        return false;
    }

    /**
     * get string format of value
     *
     * @param string $var
     * @return int | false bab_context::TEXT | bab_context::HTML
     */
    public function getFormat($var)
    {
        if (! isset($this->variables[$var])) {
            return false;
        }

        if (! isset($this->format[$var])) {
            return self::TEXT;
        }

        return $this->format[$var];
    }

    public function getname()
    {
        return $this->name;
    }

    public function getvars()
    {
        return $this->variables;
    }
}





/**
 * OVML template
 *
 */
class babOvTemplate
{
    /**
     * Stack of used contexts
     * @var array
     */
    public $contexts = array();


    public $handlers = array();

    /**
     * The current processed context, updated when we enter in a container and when we get out
     * @var bab_context
     */
    public $curctx;

    /**
     * global context (root context)
     * @var bab_context
     */
    public $gctx;

    /**
     * Contain the ovml file path if we are in a file
     * @var string
     */
    public $debug_location;


    public function __construct($args = array())
    {
        global $babBody;
        $this->gctx = new bab_context('bab_main');
        $this->gctx->push("babSiteName", $GLOBALS['babSiteName']);
        if (isset($babBody->babsite)) {
            $this->gctx->push("babSiteSlogan", $babBody->babsite['babslogan']);
        }

        if (bab_isUserLogged()) {
            $this->gctx->push("babUserName", bab_getUserName(bab_getUserId()));
        } else {
            $this->gctx->push("babUserName", '');
        }

        $this->gctx->push("babCurrentDate", time());

        foreach ($args as $variable => $contents) {
            $this->gctx->push($variable, $contents);
        }
        $this->push_ctx($this->gctx);
    }


    public function push_ctx(&$ctx)
    {
        $this->contexts[] = &$ctx;
        $this->curctx = &$ctx;
        return $this->curctx;
    }


    public function pop_ctx()
    {
        if (count($this->contexts) > 1) {
            array_pop($this->contexts);
            $this->curctx = & $this->contexts[count($this->contexts) - 1];
            return $this->curctx;
        }
    }


    protected function callInAllContexts($methodName, $parameters)
    {
        for ($i = count($this->contexts) - 1; $i >= 0; $i --) {
            $context = $this->contexts[$i];
            /*@var $context bab_context */

            $val = call_user_func_array(array($context, $methodName), $parameters);

            if ($val !== false) {
                return $val;
            }
        }

        return false;
    }


    /**
     * Get variable value with context inheritance
     *
     * @return mixed | false
     */
    public function getVariable($name)
    {
        return $this->callInAllContexts(__FUNCTION__, array($name));
    }


    /**
     * Get attribute value with context inheritance
     *
     * @param string $name
     * @return string
     */
    public function getAttribute($name)
    {
        return $this->callInAllContexts(__FUNCTION__, array($name));
    }



    /**
     * Get variable or attribute value with context inheritance
     *
     * @deprecated Use getVariable or getAttribute instead
     * @return mixed | false
     */
    public function get_value($name)
    {
        $message = 'get_value is deprecated, use ->curctx->getAttribute(' . $name . ') or ->getVariable(' . $name . ') instead, ovidentia 8.4.93 is required for the new methods';
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $context = BAB_TAG_CONTAINER . $this->curctx->name . ' in ' . $trace[0]['file'] . ' (' . $trace[0]['line'] . ')';
        bab_debug(bab_toHtml($message . "\n" . $context, BAB_HTML_ALL), DBG_INFO, 'ovml');

        return $this->callInAllContexts('get', array(
            $name
        ));
    }


    /**
     * Get format with context inheritance
     * The first format set on ancestors contexts
     *
     * @return mixed | false
     */
    public function get_format($name)
    {
        return $this->callInAllContexts(
            'getFormat',
            array(
                $name
            )
        );
    }


    /**
     * Get variables from a context specific context
     *
     * @return array
     */
    public function get_variables($contextname)
    {
        for ($i = count($this->contexts) - 1; $i >= 0; $i --) {
            if ($this->contexts[$i]->getname() == $contextname) {
                return $this->contexts[$i]->getvars();
            }
        }
        return array();
    }

    public function get_currentContextname()
    {
        return $this->curctx->name;
    }

    public function push_handler(&$handler)
    {
        $this->handlers[] = &$handler;
    }

    public function pop_handler()
    {
        if (count($this->handlers) > 0) {
            array_pop($this->handlers);
        }
    }

    public function get_handler($name)
    {
        for ($i = count($this->handlers) - 1; $i >= 0; $i --) {
            $handler = get_class($this->handlers[$i]);
            if ($handler && (mb_strtolower($handler) == mb_strtolower($name))) {
                return $this->handlers[$i];
            }
        }
        return false;
    }


    public function getArgs($str)
    {
        $args = array();
        $mm = null;
        if (preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $this->vars_replace($str), $mm)) {
            for ($j = 0; $j < count($mm[1]); $j ++) {
                $args[$mm[1][$j]] = $this->cast($mm[3][$j]);
            }
        }
        return $args;
    }


    /**
     * Process a container
     *
     * @param string $handler   Container name without OC, this is the functionality name
     * @param string $txt       Container content
     * @param array $args       Container arguments
     */
    public function handle_tag($handler, $txt, $args, $fprint = 'printout')
    {
        $out = '';

        $cls = bab_functionality::get('Ovml/Container/' . $handler, false);
        /*@var $cls Func_Ovml_Container */

        if (false === $cls) {
            if ($fprint == 'object') {
                return null;
            }
            return sprintf(bab_translate("OVML : the container %s does not exists"), BAB_TAG_CONTAINER . $handler);
        }


        $ctx = new bab_context($handler);
        $ctx->setContent($txt);
        $this->push_ctx($ctx);

        foreach ($args as $key => $val) {
            $this->curctx->addAttribute($key, $val);
        }

        $cls->setOvmlContext($this);
        if ($fprint == 'object') {
            return $cls;
        }

        $out = $cls->$fprint($txt);
        $this->pop_ctx();
        return $out;
    }


    public function cast($str)
    {
        if (! empty($str) && $str{0} == '(') {
            $m = null;
            if (preg_match('/\(\s*(.*?)\s*\)(.*)/', $str, $m)) {
                switch ($m[1]) {
                    case 'bool':
                    case 'boolean':
                        return (bool) $m[2];
                        break;
                    case 'integer':
                    case 'int':
                        return (int) $m[2];
                        break;
                    case 'float':
                    case 'double':
                    case 'real':
                        return (float) $m[2];
                        break;
                    case 'string':
                        return (string) $m[2];
                        break;
                    case 'var':
                    case 'variable':
                        return $this->getVariable($m[2]);
                        break;
                }
            }
        }
        return $str;
    }


    /**
     * Format output
     *
     * @param string $val       Variable content
     * @param array $matches    Keys are attributes names, values are attribute value
     * @param int $format       Format of string bab_context::TEXT | bab_context::HTML
     * @param string $debugInfo Variable or function name
     *
     * @return string the modified variable content
     */
    public function format_output($val, $matches, $format = bab_context::TEXT, $debugInfo = null)
    {
        $saveas = null;
        $attributes = new bab_OvmlAttributes($this, $format);

        foreach ($matches as $p => $v) {
            $method = mb_strtolower(trim($p));

            if ('saveas' === $method) {
                $saveas = $v;
                continue;
            }

            $val = $attributes->$method($val, $v);
            $attributes->history[$method] = $v;
        }

        $ghtmlentities = $this->getVariable('babHtmlEntities');
        $escapedisabled = ($ghtmlentities !== false && 0 === intval($ghtmlentities));

        if ($format === bab_context::TEXT && ! $escapedisabled) {
            // apply global htmlentities only for text variables
            $val = $attributes->htmlentities($val, 1);
        }

        if ($saveas) {
            // always apply saveas as the last attribute
            $val = $attributes->saveas($val, $saveas);
        }

        return $val;
    }


    public function vars_replace($txt)
    {
        if (empty($txt)) {
            return $txt;
        }
        $m = null;
        if (preg_match_all("/[<{](" . BAB_TAG_FUNCTION . "|" . BAB_TAG_VARIABLE . ")([^\s>}]*)\s*(\w+\s*=\s*[\"].*?\")*\s*\/?[>}]/s", $txt, $m)) {
            for ($i = 0; $i < count($m[1]); $i ++) {
                switch ($m[1][$i]) {
                    case BAB_TAG_FUNCTION:
                        $handler = $m[2][$i];
                        $params = array();
                        $argsStr = $this->vars_replace(trim($m[3][$i]));

                        $mm = null;
                        if ($this->match_args($argsStr, $mm)) {
                            for ($j = 0; $j < count($mm[1]); $j ++) {
                                $p = trim($mm[1][$j]);
                                if (! empty($p)) {
                                    $params[$p] = $mm[3][$j];
                                }
                            }
                        }

                        $cls = bab_functionality::get('Ovml/Function/' . $handler);

                        if (false === $cls) {
                            $val = sprintf(bab_translate("OVML : the function %s does not exists"), BAB_TAG_FUNCTION . $handler);
                        } else {

                            $cls->setTemplate($this);
                            $cls->setArgs($params);
                            $val = $cls->toString();
                        }


                        // $val = $this->$handler($params);

                        $txt = preg_replace("/" . preg_quote($m[0][$i], "/") . "/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
                        break;

                    case BAB_TAG_VARIABLE:
                        $m2 = null;
                        if (preg_match_all("/(.*?)\[([^\]]+)\]/", $m[2][$i], $m2) > 0) {
                            // print_r($m2);
                            $val = $this->getVariable($m2[1][0]);
                            $format = $this->get_format($m2[1][0]);
                            for ($t = 0; $t < count($m2[2]); $t ++) {
                                if (isset($val[$m2[2][$t]])) {
                                    $val = $val[$m2[2][$t]];
                                } else {
                                    $val = '';
                                    break;
                                }
                            }
                        } else {
                            $val = $this->getVariable($m[2][$i]);
                            $format = $this->get_format($m[2][$i]);
                        }

                        $args = $this->vars_replace(trim($m[3][$i]));
                        if ($val !== false) {
                            $params = array();
                            if ($this->match_args($args, $mm)) {
                                for ($j = 0; $j < count($mm[1]); $j ++) {
                                    $p = trim($mm[1][$j]);
                                    if (! empty($p)) {
                                        $params[$p] = $mm[3][$j];
                                    }
                                }
                            }
                            $val = $this->format_output($val, $params, $format, BAB_TAG_VARIABLE . $m[2][$i]);
                            $txt = preg_replace("/" . preg_quote($m[0][$i], "/") . "/", preg_replace("/\\$[0-9]/", "\\\\$0", $val), $txt);
                        }
                        break;
                }
            }
        }

        return $txt;
    }

    public function handle_text($txt)
    {
        $m = null;
        if (preg_match_all("/(.*?)<" . BAB_TAG_CONTAINER . "([^\s]*)\s*(\w+\s*=\s*[\"].*?\")*\s*(\w*)\s*>(.*?)<\/" . BAB_TAG_CONTAINER . "\\2\s*\\4\s*>(.*)/s", $txt, $m)) {
            $out = '';
            for ($i = 0; $i < count($m[3]); $i ++) {

                $out .= $this->handle_text($m[1][$i]);
                $out .= $this->handle_tag($m[2][$i], $m[5][$i], $this->getArgs($m[3][$i]));
                $out .= $this->handle_text($m[6][$i]);
            }
            return $out;
        } else {
            $out = $this->vars_replace($txt);
            return $out;
        }
    }


    public function match_args(&$args, &$mm)
    {
        return preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/s", $args, $mm);
    }


    /**
     * Process ovml source
     *
     * @param string $txt               ovml source content
     * @param string $debug_location    can contain the file path of the processed ovml file or any info to describe where the ovml source is located
     * @return string
     */
    public function printout($txt, $debug_location = null)
    {
        $this->debug_location = $debug_location;
        $replace = bab_replace_get();
        $replace->addIgnoreMacro('OVML');
        $txt = $this->handle_text($txt);
        $replace->removeIgnoreMacro('OVML');
        return $txt;
    }
}


/**
 * All methods of this objects are OVML attributes
 */
class bab_OvmlAttributes
{

    /**
     * OVML template object
     * Warning, this is not a context!
     *
     * @var babOvTemplate
     */
    private $ctx;

    /**
     * OVML global context
     *
     * @var bab_context
     */
    private $gctx;

    /**
     * contain the list of called methods
     *
     * @var bool
     */
    public $history = array();

    /**
     * bab_context::TEXT | bab_context::HTML
     *
     * @var int
     */
    private $format;


    /**
     * @param babOvTemplate $ctx    Ovml template
     * @param int $format           bab_context::HTML
     */
    public function __construct(babOvTemplate $ctx, $format)
    {
        $this->ctx = $ctx;
        $this->gctx = $ctx->gctx;
        $this->format = $format;
    }

    /**
     *
     * @return bool
     */
    private function done($method, $option = null)
    {
        if (! isset($this->history[$method])) {
            return false;
        }

        if (null !== $option && $this->history[$method] !== $option) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $method
     * @param array $args
     * @return string
     */
    public function __call($method, $args)
    {
        trigger_error(sprintf('Unknown OVML attribute %s="%s" in %s, attribute ignored', $method, $args[1], (string) $this->ctx->debug_location));
        return $args[0];
    }

    /**
     * Cut string,
     * for html, remove tags if not allready removed
     *
     * @param string $val
     * @param string $v
     * @return string
     */
    public function strlen($val, $v)
    {
        if (bab_context::HTML === $this->format) {
            if (! $this->done('striptags')) {
                $val = $this->striptags($val, '1');
            }

            if (! $this->done('htmlentities', '2')) {
                $val = $this->htmlentities($val, '2');
            }

            if (! $this->done('trim')) {
                $val = $this->trim($val, 'left');
            }
        }

        $arr = explode(',', $v);
        if (mb_strlen($val) > $arr[0]) {
            if (isset($arr[1])) {
                $val = mb_substr($val, 0, $arr[0]) . $arr[1];
            } else {
                $val = mb_substr($val, 0, $v);
            }
            $this->gctx->push('substr', 1); // permet de savoir dans la suite du code ovml si la variable a ete coupe ou non
        } else
            $this->gctx->push('substr', 0);

        return $val;
    }

    public function striptags($val, $v)
    {
        switch ($v) {
            case '1':
                return strip_tags($val);

            case '2':
                $val = preg_replace('/<BR[[:space:]]*\/?[[:space:]]*>/i', "\n ", $val);
                $val = preg_replace('/<P>|<\/P>|<P \/>|<P\/>/i', "\n ", $val);
                return strip_tags($val);
        }
    }

    /**
     * Encoding of html entites can be set only one time per variable
     *
     * @param string $val
     * @param int $v
     * @return string
     */
    public function htmlentities($val, $v)
    {
        if ($this->done(__FUNCTION__, '0')) {
            // auto htmlentities has been disabled with an attribute htmlentities="0", others htmlentities are ignored
            return $val;
        }

        if ($this->done(__FUNCTION__, '1') || $this->done(__FUNCTION__, '3')) {
            // job allready done
            return $val;
        }

        switch ($v) {
            case '0': // disable auto htmlentities
                break;

            case '1':
                $val = bab_toHtml($val);
                break;
            case '2':
                require_once dirname(__FILE__) . '/tohtmlincl.php';
                $val = bab_unhtmlentities($val);
                break;
            case '3':
                $val = htmlspecialchars($val, ENT_COMPAT, bab_charset::getIso());
                break;
        }

        return $val;
    }

    public function stripslashes($val, $v)
    {
        if ($v == '1') {
            $val = stripslashes($val);
        }
        return $val;
    }

    public function urlencode($val, $v)
    {
        if ($v == '1') {
            $val = urlencode($val);
        }

        return $val;
    }

    public function jsencode($val, $v)
    {
        if ($v == '1') {
            $val = bab_toHtml($val, BAB_HTML_JS);
        }
        return $val;
    }

    public function strcase($val, $v)
    {
        switch ($v) {
            case 'upper':
                $val = mb_strtoupper($val);
                break;
            case 'lower':
                $val = mb_strtolower($val);
                break;
        }
        return $val;
    }

    public function nlremove($val, $v)
    {
        if ($v == '1') {
            $val = preg_replace("(\r\n|\n|\r)", "", $val);
        }

        return $val;
    }

    public function trim($val, $v)
    {
        switch ($v) {
            case 'left':
                $val = ltrim($val, " \x0B\0\n\t\r" . bab_nbsp());
                break;
            case 'right':
                $val = rtrim($val, " \x0B\0\n\t\r" . bab_nbsp());
                break;
            case 'all':
                $val = trim($val, " \x0B\0\n\t\r" . bab_nbsp());
                break;
        }

        return $val;
    }

    public function nl2br($val, $v)
    {
        if ($v == '1') {
            $val = nl2br($val);
        }

        return $val;
    }

    public function sprintf($val, $v)
    {
        return sprintf($v, $val);
    }


    /**
     * Formats the value as a date.
     *
     * @param int|string $val   An integer timestamp or an iso-formatted date or datetime string.
     * @param string $v         The format
     * @return string The formatted string
     */
    public function date($val, $v)
    {
        if (! is_int($val)) {
            if (strpos($val, '-') !== false) {
                if (strpos($val, ':') !== false) {
                    $val = bab_mktime($val);
                } else {
                    $val = bab_mktime($val . ' 00:00:00');
                }
            }
        }
        return bab_formatDate($v, $val);
    }

    public function author($val, $v)
    {
        return bab_formatAuthor($v, $val);
    }

    public function saveas($val, $v)
    {
        $this->gctx->push($v, $val);
        return $val;
    }

    public function strtr($val, $v)
    {
        if (! empty($v)) {
            $trans = array();
            for ($i = 0; $i < mb_strlen($v); $i += 2) {
                $trans[mb_substr($v, $i, 1)] = mb_substr($v, $i + 1, 1);
            }
            if (count($trans) > 0) {
                $val = strtr($val, $trans);
            }
        }

        return $val;
    }
}




/**
 * Get language
 */
class Func_Ovml_Function_GetLanguage extends Func_Ovml_Function
{

    public function toString()
    {
        return $this->format_output(bab_getLanguage(), $this->args);
    }
}




/**
 * Translate text
 */
class Func_Ovml_Function_Translate extends Func_Ovml_Function
{
    /**
     *
     * @return string
     */
    public function toString()
    {
        $args = $this->args;
        $lang = '';
        $folder = '';

        if (count($args) === 0) {
            return '';
        }
        foreach ($args as $p => $value) {
            switch (mb_strtolower(trim($p))) {
                case 'text':
                    $text = $value;
                    unset($args[$p]);
                    break;
                case 'lang':
                    $lang = $value;
                    unset($args[$p]);
                    break;
                case 'folder':
                    $folder = $value;
                    unset($args[$p]);
                    break;
            }
        }

        return $this->format_output(bab_translate($text, $folder, $lang), $args);
    }
}



/**
 * Web statistic
 */
class Func_Ovml_Function_WebStat extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;

        if (count($args)) {
            $name = '';
            $value = '';
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                    case 'value':
                        $value = $v;
                        break;
                }
            }
            if (! empty($name) && ! empty($value)) {
                if (mb_substr($name, 0, 4) == "bab_") {
                    $arr = explode(',', $value);
                    for ($k = 0; $k < count($arr); $k ++) {
                        $GLOBALS['babWebStat']->addArrayInfo($name, $arr[$k]);
                    }
                } else {
                    $GLOBALS['babWebStat']->addInfo($name, $value);
                }
            }
        }
    }
}


class Func_Ovml_Function_SetCookie extends Func_Ovml_Function
{
    public function toString()
    {
        $name = '';
        $value = '';
        $args = $this->args;

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                    case 'value':
                        $value = $v;
                        break;
                    case 'expire': // seconds
                        $expire = time() + $v;
                        break;
                }
            }

            if (! empty($name)) {
                if (! isset($expire)) {
                    setcookie($name, $value);
                    $_COOKIE[$name] = $value; /* It allows to recover in the same code OVML the value of the cookie (OFSeCookie then OFGetCookie will work) */
                } else {
                    setcookie($name, $value, $expire);
                    $_COOKIE[$name] = $value; /* It allows to recover in the same code OVML the value of the cookie (OFSeCookie then OFGetCookie will work) */
                }
            }
        }
    }
}


class Func_Ovml_Function_GetCookie extends Func_Ovml_Function
{
    public function toString()
    {
        $name = '';
        $args = $this->args;

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                }
            }

            if (! empty($name) && isset($_COOKIE[$name])) {
                $this->gctx->push($name, $_COOKIE[$name]);
            }
        }
    }
}


class Func_Ovml_Function_SetSessionVar extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;
        $name = '';
        $value = '';

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                    case 'value':
                        $value = $v;
                        break;
                }
            }
            if ($name !== '') {
                $_SESSION[$name] = $value;
                $this->gctx->push($name, $value);
            }
        }
    }
}



class Func_Ovml_Function_GetSessionVar extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;
        $name = '';

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                }
            }
            if ($name !== '' && isset($_SESSION[$name])) {
                $this->gctx->push($name, $_SESSION[$name]);
            }
        }
    }
}


class Func_Ovml_Function_GetPageTitle extends Func_Ovml_Function
{
    public function toString()
    {
        global $babBody;
        $varname = '';
        $args = $this->args;

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'saveas':
                        $varname = $v;
                        break;
                }
            }
            if ($varname !== '') {
                $this->gctx->push($varname, $babBody->title);
            } else {
                return $babBody->title;
            }
        } else {
            return $babBody->title;
        }
    }
}


/**
 * Save a variable to global space
 */
class Func_Ovml_Function_PutVar extends Func_Ovml_Function
{
    public function toString()
    {
        global $babBody;
        $args = $this->args;
        $name = '';
        $value = '';
        $global = true;

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        $global = true;
                        break;
                    case 'value':
                        $value = $v;
                        $global = false;
                        switch ($name) {
                            case 'babSlogan':
                                $GLOBALS['babSlogan'] = $value;
                                break;
                            case 'babTitle':
                                $babBody->title = $value;
                                break;
                            case 'babError':
                                $babBody->msgerror = $value;
                                break;
                            default:
                                $value = $this->cast($value);
                                break;
                        }

                        break;
                }
            }
            if ($global && isset($GLOBALS[$name])) {
                $value = $GLOBALS[$name];
            }
            $this->gctx->push($name, $value);
        }
    }
}


/**
 * Get a variable
 */
class Func_Ovml_Function_GetVar extends Func_Ovml_Function
{
    public function toString()
    {
        $name = '';
        $args = $this->args;

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                }
            }

            if (! empty($name)) {
                $value = $this->getVariable($name);
                if ($value !== false) {
                    return $value;
                }
            }
        }
    }
}


/**
 * Save a variable to global space if not already defined
 */
class Func_Ovml_Function_IfNotIsSet extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;
        $name = '';
        $value = '';

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                    case 'value':
                        $value = $v;
                        break;
                }
            }

            if ($this->gctx->get($name) === false) {
                $this->gctx->push($name, $this->cast($value));
            }
        }
    }
}


/**
 * Save a array to global space
 */
class Func_Ovml_Function_PutArray extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;
        $name = '';
        $arr = array();
        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = trim($v);
                        break;
                    default:
                        $arr[trim($p)] = $this->cast(trim($v));
                        break;
                }
            }

            $this->gctx->push($name, $arr);
        }
    }
}


/**
 * Save a soap array type to global space
 */
class Func_Ovml_Function_PutSoapArray extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;
        $name = '';
        $arr = array();
        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = trim($v);
                        break;
                    default:
                        $arr[] = array(
                            'name' => trim($p),
                            'value' => $this->cast(trim($v))
                        );
                        break;
                }
            }
        }

        $this->gctx->push($name, $arr);
    }
}



class bab_rgp extends Func_Ovml_Function
{
    public function rgp($args, $method)
    {
        $name = '';
        $default = '';
        $saveas = false;
        $saveasname = '';

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $name = $v;
                        break;
                    case 'default':
                        $default = $v;
                        break;
                    case 'saveas':
                        if (! empty($v)) {
                            $saveas = true;
                            $saveasname = $v;
                        }
                        break;
                }
            }

            if (! empty($name)) {
                if ($saveas) {
                    if (strpos($name, '[') !== false) {
                        $name = str_replace(']', '', $name);
                        $name = explode('[', $name);
                        $value = $method($name[0], $default);
                        $i = 1;
                        while (is_array($value) && isset($name[$i]) && $name[$i]) {
                            $value = $value[$name[$i]];
                            $i ++;
                        }
                        if (is_array($value)) {
                            $this->gctx->push($saveasname, $default);
                        } else {
                            $this->gctx->push($saveasname, $value);
                        }
                    } else {
                        $this->gctx->push($saveasname, $method($name, $default));
                    }
                } else {
                    if (strpos($name, '[') !== false) {
                        $name = str_replace(']', '', $name);
                        $name = explode('[', $name);
                        $value = $method($name[0], $default);
                        $i = 1;
                        while (is_array($value) && isset($name[$i]) && $name[$i]) {
                            $value = $value[$name[$i]];
                            $i ++;
                        }
                        if (is_array($value)) {
                            $this->gctx->push($name[0], $default);
                        } else {
                            $this->gctx->push($name[0], $value);
                        }
                    } else {
                        $this->gctx->push($name, $method($name, $default));
                    }
                }
            }
        }
    }
}


class Func_Ovml_Function_Request extends bab_rgp
{

    public function toString()
    {
        $this->rgp($this->args, 'bab_rp');
    }
}


class Func_Ovml_Function_Post extends bab_rgp
{

    public function toString()
    {
        $this->rgp($this->args, 'bab_pp');
    }
}


class Func_Ovml_Function_Get extends bab_rgp
{

    public function toString()
    {
        $this->rgp($this->args, 'bab_gp');
    }
}


/**
 * Experimental ( can be changed in futur )
 * Returns an HTTP Request javascript call
 *
 * @access  public
 * @return  string	javascript call to bab_ajaxRequest()
 * @param   url	http request
 * @param   output	elem:property like mydiv:innerHTML where to put ajax response
 * @param   action	GET|POST default GET
 * @param   indicator	HTML element to show when request is pending
*/
class Func_Ovml_Function_Ajax extends Func_Ovml_Function
{

    public function toString()
    {
        global $babBody;

        $args = $this->args;
        $params = array();
        $url = '';
        $output = '';
        $action = 'GET';
        $indicator = '';

        if (count($args)) {
            $babBody->addJavascriptFile($GLOBALS['babScriptPath'] . "prototype/prototype.js");
            $babBody->addJavascriptFile($GLOBALS['babScriptPath'] . "babajax.js");

            foreach ($args as $p => $v) {
                $p = trim($p);
                switch (mb_strtolower($p)) {
                    case 'url':
                        $url = $v;
                        break;
                    case 'output':
                        $output = $v;
                        break;
                    case 'action':
                        $action = $v;
                        break;
                    case 'indicator':
                        $indicator = $v;
                        break;
                    default:
                        $params[] = $p . '=' . $v;
                        break;
                }
            }
            return "bab_ajaxRequest('" . $url . "','" . $action . "','" . $output . "','" . $indicator . "','" . implode('&', $params) . "')";
        }
        return '';
    }
}


/**
 * Returns a value from the registry.
 *
 * @see bab_Registry::get()
 *
 * @param path      The registry path
 * @param default   The default value if path not found
 *
 * @since 8.6.97
 */
class Func_Ovml_Function_GetRegistryValue extends Func_Ovml_Function
{
    public function toString()
    {
        $path = '';
        $saveas = '';
        $default = null;

        if (count($this->args)) {
            // Récupération des arguments
            foreach ($this->args as $name => $value) {
                switch (mb_strtolower(trim($name))) {
                    case 'path':
                        $path = $value;
                        break;
                    case 'default':
                        $default = $value;
                        break;
                    case 'saveas':
                        $saveas = $value;
                        break;
                }
            }

            if (! $path) {
                return '';
            }

            $value = bab_Registry::get($path, $default);

            if ($saveas) {
                $this->gctx->push($saveas, $value);
                return '';
            }

            return $value;
        }
    }
}



/**
 * Arithmetic operators
 */
class bab_ArithmeticOperator extends Func_Ovml_Function
{
    protected function getValue($args, $ope)
    {
        $expr1 = "";
        $expr2 = "";
        $saveas = true;

        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'expr1':
                        $expr1 = $this->cast($v);
                        break;
                    case 'expr2':
                        $expr2 = $this->cast($v);
                        break;
                    case 'saveas':
                        $saveas = true;
                        $varname = $v;
                        break;
                }
            }
            switch ($ope) {
                case '+':
                    $val = $expr1 + $expr2;
                    break;
                case '-':
                    $val = $expr1 - $expr2;
                    break;
                case '*':
                    $val = $expr1 * $expr2;
                    break;
                case '/':
                    $val = $expr1 / $expr2;
                    break;
                case '%':
                    $val = $expr1 % $expr2;
                    break;
            }

            if ($saveas) {
                $this->gctx->push($varname, $val);
            } else {
                return $val;
            }
        }
    }
}


/* Arithmetic operators */
class Func_Ovml_Function_AOAddition extends bab_ArithmeticOperator
{

    public function toString()
    {
        // print_r($args)
        return parent::getValue($this->args, '+');
    }
}


/* Arithmetic operators */
class Func_Ovml_Function_AOSubtraction extends bab_ArithmeticOperator
{

    public function toString()
    {
        return parent::getValue($this->args, '-');
    }
}


/**
 * Arithmetic operators
 */
class Func_Ovml_Function_AOMultiplication extends bab_ArithmeticOperator
{

    public function toString()
    {
        return parent::getValue($this->args, '*');
    }
}


/**
 * Arithmetic operators
 */
class Func_Ovml_Function_AODivision extends bab_ArithmeticOperator
{

    public function toString()
    {
        return parent::getValue($this->args, '/');
    }
}



/**
 * Arithmetic operators
 */
class Func_Ovml_Function_AOModulus extends bab_ArithmeticOperator
{

    public function toString()
    {
        return parent::getValue($this->args, '%');
    }
}


/**
 * save a variable to global space
 */
class Func_Ovml_Function_UrlContent extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;

        $url = "";
        if (count($args)) {
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'url':
                        $url = $v;
                        $purl = parse_url($url);
                        unset($args[$p]);
                        break;
                }
            }
            return $this->format_output(preg_replace("/(src=|background=|href=)(['\"])([^'\">]*)(['\"])/e", '"\1\"".bab_rel2abs("\3", $purl)."\""', implode('', file($url))), $args);
        }
    }
}

class Func_Ovml_Function_Header extends Func_Ovml_Function
{

    public function toString()
    {
        $value = '';
        if (count($this->args)) {
            foreach ($this->args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'value':
                        $value = $v;
                        break;
                }
            }
            header($value);
        }
    }
}



/**
 * Include another ovml file
 * <OFInclude file="" cache="1|0">
 */
class Func_Ovml_Function_Include extends Func_Ovml_Function
{

    public function toString()
    {
        $file = '';
        $cache = false;
        if (count($this->args)) {
            foreach ($this->args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'file':
                        $file = $v;
                        break;

                    case 'cache':
                        if ($v) {
                            $cache = true;
                        }
                        break;
                }
            }


            if ($cache) {
                return bab_printCachedOvmlTemplate($file, $this->gctx->getvars());
            } else {
                return bab_printOvmlTemplate($file, $this->gctx->getvars());
            }
        }
    }
}





/**
 * Add a stylesheet to the current page
 * the file is relative to "style" folder of ovidentia core
 * <OFAddStyleSheet file="addons/addonname/filename.css">
 */
class Func_Ovml_Function_AddStyleSheet extends Func_Ovml_Function
{
    public function toString()
    {
        $file = null;

        foreach ($this->args as $p => $v) {
            switch (mb_strtolower(trim($p))) {
                case 'file':
                    $file = $v;
                    break;
            }
        }


        if (isset($file)) {
            global $babBody;
            $babBody->addStyleSheet($file);
        } else {
            trigger_error(sprintf('OFAddStyleSheet : the file attribute is mandatory in %s', (string) $this->gctx->debug_location));
        }
    }
}





class Func_Ovml_Function_Recurse extends Func_Ovml_Function
{

    public function toString()
    {
        $handler = $this->template->curctx->getname();
        return $this->template->handle_tag($handler, $this->template->curctx->getcontent(), $this->args);
    }
}



class Func_Ovml_Function_Addon extends Func_Ovml_Function
{
    public function toString()
    {
        $args = $this->args;

        $output = '';
        if (count($args)) {
            $function_args = array();
            foreach ($args as $p => $v) {
                switch (mb_strtolower(trim($p))) {
                    case 'name':
                        $addon = bab_getAddonInfosInstance($v);
                        break;

                    case 'function':
                        $function = $v;
                        break;
                    default:
                        $function_args[] = $v;
                        break;
                }
            }

            if ($addon && $addon->isAccessValid()) {
                $addonpath = $addon->getPhpPath();
                if (is_file($addonpath . "ovml.php")) {
                    /* save old vars */
                    $oldAddonFolder = isset($GLOBALS['babAddonFolder']) ? $GLOBALS['babAddonFolder'] : '';
                    $oldAddonTarget = isset($GLOBALS['babAddonTarget']) ? $GLOBALS['babAddonTarget'] : '';
                    $oldAddonUrl = isset($GLOBALS['babAddonUrl']) ? $GLOBALS['babAddonUrl'] : '';
                    $oldAddonPhpPath = isset($GLOBALS['babAddonPhpPath']) ? $GLOBALS['babAddonPhpPath'] : '';
                    $oldAddonHtmlPath = isset($GLOBALS['babAddonHtmlPath']) ? $GLOBALS['babAddonHtmlPath'] : '';
                    $oldAddonUpload = isset($GLOBALS['babAddonUpload']) ? $GLOBALS['babAddonUpload'] : '';

                    include_once $GLOBALS['babInstallPath'] . "utilit/addonsincl.php";
                    bab_setAddonGlobals($addon->getId());
                    require_once ($addonpath . "ovml.php");

                    $call = $addon->getName() . "_" . $function;
                    if (! empty($call) && function_exists($call)) {
                        $output = call_user_func_array($call, $function_args);
                    }

                    $GLOBALS['babAddonFolder'] = $oldAddonFolder;
                    $GLOBALS['babAddonTarget'] = $oldAddonTarget;
                    $GLOBALS['babAddonUrl'] = $oldAddonUrl;
                    $GLOBALS['babAddonPhpPath'] = $oldAddonPhpPath;
                    $GLOBALS['babAddonHtmlPath'] = $oldAddonHtmlPath;
                    $GLOBALS['babAddonUpload'] = $oldAddonUpload;
                }
            }
        }
        return $output;
    }
}






/**
 * Return the file manager tree in a html UL LI
 *
 * <OFFileTree [path=""] [file="0|1"] [filelimit="fileNumber"] [maxdepth="depth"] [emptyfolder=0|1] [hidefirstnode="0|1"]>
 *
 * - The path attribute is optional. It define where the tree will start.
 * 		The default value is the entire file manager with rights.
 * - The file attribute is optional, it define if file are display or not.
 * 		The default value is '1'.
 * - The filelimit attribute is optional, it will limit the number of file per folder which will be display. 0 = no limit.
 * 		The default value is '0'.
 * - The maxdepth attribute is optional, limits the number of levels of nested <ul>.
 * 		No maximum depth by default.
 * - The emptyfolder attribute is optional, it will desside if empty folder should be display.
 * 		The default value is '1'.
 * - The hidefirstnode attribute is optional, it define if the name of the first not should be display or not.
 * 		The default value is '0'.
 *
 *
 * Example:
 *
 * The following OVML function :
 * <OFFileTree>
 *
 * Will yield:
 *
 * <ul class="filetree-root">
 * a definir
 * </ul>
 */
class Func_Ovml_Function_FileTree extends Func_Ovml_Function
{
    protected $path = '';

    protected $delegation = 0;

    protected $file = 1;

    protected $filelimit = 0;

    protected $emptyfolder = 1;

    protected $selectedClass = 'selected';

    protected $activeClass = 'active';

    protected $maxDepth = 100;


    private function getChildTree($relativePath = '')
    {
        global $babDB;

        $return = '';
        $child = '';

        $iIdRootFolder = null;
        $oFmFolder = null;

        BAB_FmFolderHelper::getInfoFromCollectivePath($this->path . $relativePath, $iIdRootFolder, $oFmFolder);
        $rPath = new bab_Path(realpath(BAB_FileManagerEnv::getCollectivePath($this->delegation)), $this->path, $relativePath);
        $rPath->orderAsc(bab_Path::BASENAME);

        if (in_array($oFmFolder->getId(), $this->arrid)) {
            foreach ($rPath as $subPath) {
                if ($subPath->isDir() && $subPath->getBasename() != 'OVF') {
                    $childs = $this->getChildTree($relativePath . '/' . $subPath->getBasename());
                    if ($childs != '') {
                        $child[] = $this->getChildTree($relativePath . '/' . $subPath->getBasename());
                    }
                }
            }

            if ($this->file) { // file display?
                $req = "SELECT * FROM " . BAB_FILES_TBL . " f WHERE f.bgroup='Y' AND f.state='' AND f.confirmed='Y' AND iIdDgOwner = '" . $babDB->db_escape_string($this->delegation) . "'  AND f.path = '" . $babDB->db_escape_string($this->path . $relativePath . '/') . "' ORDER BY display_position ASC, name ASC";
                if ($this->filelimit != 0) {
                    $req .= " LIMIT 0," . $this->filelimit;
                }
                $res = $babDB->db_query($req);
                while ($arr = $babDB->db_fetch_assoc($res)) {
                    $child[] = array(
                        'type' => 'file',
                        'url' => htmlentities($GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&gr=Y&sAction=getFile&idf=' . $arr['id'] . '&id=' . $iIdRootFolder . '&path=' . $arr['path']),
                        'name' => $arr['name'],
                        'child' => ''
                    );
                }
            }

            if ($this->emptyfolder || $child != '') {
                $return = array(
                    'type' => 'folder',
                    'url' => htmlentities($GLOBALS['babUrl'] . bab_getSelf() . '?tg=fileman&idx=list&gr=Y&path=' . $this->path . $relativePath . '&id=' . $iIdRootFolder),
                    'name' => $rPath->getBasename(),
                    'child' => $child
                );
            }
        }
        return $return;
    }


    function generateUL($currentStage, $firstLevel = false)
    {
        $return = '';
        foreach ($currentStage as $nextStage) {
            if ($firstLevel) {
                $return .= '<ul class="filetree">';
            }
            if (! ($this->hidefirstnode && $firstLevel)) {
                // Si on est au prmier niveau et qu'on veut cacher le premier niveau on ne rentre pas dans le IF
                $return .= '<li class="' . $nextStage['type'] . '"><span class="unfold-fold"></span><a href="' . $nextStage['url'] . '">' . $nextStage['name'] . "</a>";
            }

            if (isset($nextStage['child']) && $nextStage['child'] != '') {
                if (! ($this->hidefirstnode && $firstLevel)) {
                    $return .= '<ul>' . $this->generateUL($nextStage['child']) . '</ul>';
                } else {
                    $return .= $this->generateUL($nextStage['child']);
                }
            }

            if (! ($this->hidefirstnode && $firstLevel)) {
                $return .= '</li>';
            }
            if ($firstLevel) {
                $return .= '</ul>';
            }
        }
        return $return;
    }


    /**
     *
     * @return string
     */
    public function toString()
    {
        require_once $GLOBALS['babInstallPath'] . 'utilit/fileincl.php';
        global $babDB;
        $args = $this->args;

        if (isset($args['maxdepth'])) {
            $this->maxDepth = $args['maxdepth'];
        } else {
            $this->maxDepth = 100;
        }

        if (isset($args['file'])) {
            $this->file = $args['file'];
        } else {
            $this->file = 1;
        }

        if (isset($args['filelimit'])) {
            $this->filelimit = $args['filelimit'];
        } else {
            $this->filelimit = 0;
        }

        if (isset($args['emptyfolder'])) {
            $this->emptyfolder = $args['emptyfolder'];
        } else {
            $this->emptyfolder = 1;
        }

        if (isset($args['path'])) {
            $this->path = $args['path'];
        } else {
            $this->path = '';
        }

        if (isset($args['hidefirstnode'])) {
            $this->hidefirstnode = $args['hidefirstnode'];
        } else {
            $this->hidefirstnode = 0;
        }

        $req = "select * from " . BAB_FM_FOLDERS_TBL . " where active='Y' AND id_dgowner = 0 ORDER BY folder ASC";

        $this->arrid = array();
        $res = $babDB->db_query($req);
        while ($arr = $babDB->db_fetch_array($res)) {
            if (bab_isAccessValid(BAB_FMDOWNLOAD_GROUPS_TBL, $arr['id'])) {
                $this->arrid[] = $arr['id'];
            }
        }
        $core = array();
        if ($this->path == '') {
            $req = "select * from " . BAB_FM_FOLDERS_TBL . " where sRelativePath = '' AND id IN (" . $babDB->quote($this->arrid) . ") AND active='Y' AND id_dgowner = 0";

            $res = $babDB->db_query($req);

            while ($arr = $babDB->db_fetch_assoc($res)) {
                $this->path = $arr['folder'];
                $core[] = $this->getChildTree();
            }
        } else {
            $core[] = $this->getChildTree();
        }
        return $this->generateUL($core, true);
    }
}








function bab_rel2abs($relative, $url)
{
    if (preg_match(',^(https?://|ftp://|mailto:|news:),i', $relative)) {
        return $relative;
    }

    if ($relative[0] == '#') {
        return $relative;
    }

    if (mb_strlen($url['path']) > 1 && $url['path']{mb_strlen($url['path']) - 1} == '/') {
        $dir = mb_substr($url['path'], 0, mb_strlen($url['path']) - 1);
    } else {
        $dir = dirname($url['path']);
    }

    if ($relative{0} == '/') {
        $relative = mb_substr($relative, 1);
        $dir = '';
    } else {
        if (mb_substr($relative, 0, 2) == './') {
            $relative = mb_substr($relative, 2);
        } else {
            while (mb_substr($relative, 0, 3) == '../') {
                $relative = mb_substr($relative, 3);
                $dir = mb_substr($dir, 0, mb_strrpos($dir, '/'));
            }
        }
    }
    return sprintf('%s://%s%s/%s', $url['scheme'], $url['host'], $dir, $relative);
}





/**
 * Get a path relative to site root folder
 * <OFGetPath path="" file_relative="1" saveas="">
 *
 * file_relative: convert a path relative to the ovml file to a path relative to ovidentia root
 *
 * @since 8.2.0
 */
class Func_Ovml_Function_GetPath extends Func_Ovml_Function {


    public function toString()
    {
        $path = null;
        $saveas = null;
        $rootpath = dirname('.');

        foreach ($this->args as $p => $v) {
            switch (mb_strtolower(trim($p))) {

                case 'path':
                    $path = trim($v);
                    break;

                case 'saveas':
                    $saveas = $v;
                    break;

                case 'file_relative':
                    $absolute = dirname($this->template->debug_location).'/'.$path;
                    $path = mb_substr($absolute, mb_strlen($rootpath) -1);
                    break;
            }
        }


        if (isset($saveas)) {
            $this->gctx->push($saveas, $path);
            return '';
        }
    }
}



/**
 * Get selected skin path
 * <OFGetSelectedSkinPath>
 *
 * @since 8.3.0
 */
class Func_Ovml_Function_GetSelectedSkinPath extends Func_Ovml_Function {

    /**
     * @return string
     */
    public function toString()
    {
        return bab_skin::getUserSkin()->getThemePath();
    }
}


/**
 * Get CSRF protect token
 * <OFGetCsrfProtectToken>
 *
 * @since 8.4.91
 */
class Func_Ovml_Function_GetCsrfProtectToken extends Func_Ovml_Function {

    /**
     * @return string
     */
    public function toString()
    {
        return bab_getInstance('bab_CsrfProtect')->getToken();
    }
}






/**
 * Get Current Administration Group
 * <OFGetCurrentAdmGroup>
 *
 * @since 8.5.90
 */
class Func_Ovml_Function_GetCurrentAdmGroup extends Func_Ovml_Function {

    public function toString()
    {
        return bab_getCurrentAdmGroup();
    }
}



/**
 *  Ensures that the user is logged in.
 */
class Func_Ovml_Function_RequireCredentials extends Func_Ovml_Function
{

    /**
     * @return string
     */
    public function toString()
    {
        $args = $this->args;
        $authType = '';
        $message = '';

        if (count($args) === 0) {
            return '';
        }
        foreach ($args as $p => $value) {
            switch (mb_strtolower(trim($p))) {
                case 'authType':
                    $authType = $value;
                    unset($args[$p]);
                    break;
                case 'message':
                    $message = $value;
                    unset($args[$p]);
                    break;
            }
        }

        return bab_requireCredential($message, $authType);
    }
}


require_once dirname(__FILE__).'/ovmlfile.php';
require_once dirname(__FILE__).'/ovmlarticle.php';
require_once dirname(__FILE__).'/ovmlcalendar.php';
require_once dirname(__FILE__).'/ovmlforum.php';
require_once dirname(__FILE__).'/ovmlfaq.php';

