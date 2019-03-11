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


class Func_Ovml_Container_Faqs extends Func_Ovml_Container
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
        $faqid = $ctx->curctx->getAttribute('faqid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        if (empty($faqid)) {
            $faqid = false;
        } else {
            $faqid = explode(',', $faqid);
        }

        if (empty($delegationid)) {
            $delegationid = false;
        }

        include_once $GLOBALS['babInstallPath'] . 'utilit/faqincl.php';

        $this->res = bab_getFaqRes($faqid, $delegationid);
        $this->count = $babDB->db_num_rows($this->res);
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FaqName', $arr['category']);
            $this->ctx->curctx->push('FaqDescription', $arr['description']);
            $this->ctx->curctx->push('FaqId', $arr['id']);
            $this->ctx->curctx->push('FaqLanguage', $arr['lang']);
            $this->ctx->curctx->push('FaqUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=questions&item=" . $arr['id']);
            $this->ctx->curctx->push('FaqDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_Faq extends Func_Ovml_Container
{
    public $index;

    public $count;

    public $res;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->res = $babDB->db_query("select * from " . BAB_FAQCAT_TBL . " where id='" . $babDB->db_escape_string($ctx->curctx->getAttribute('faqid')) . "'");
        if ($this->res && $babDB->db_num_rows($this->res) == 1)
            $this->count = 1;
        else
            $this->count = 0;
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FaqName', $arr['category']);
            $this->ctx->curctx->push('FaqDescription', $arr['description']);
            $this->ctx->curctx->push('FaqId', $arr['id']);
            $this->ctx->curctx->push('FaqLanguage', $arr['lang']);
            $this->ctx->curctx->push('FaqUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=questions&item=" . $arr['id']);
            $this->ctx->curctx->push('FaqDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_FaqPrevious extends Func_Ovml_Container_Faq
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_Faq::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Faqs');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('faqid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        parent:setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_FaqNext extends Func_Ovml_Container_Faq
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_Faq::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Faqs');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('faqid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        parent:setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_FaqSubCategories extends Func_Ovml_Container
{
    public $res;

    public $index;

    public $count;

    public $faqinfo;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->count = 0;
        $faqid = $ctx->curctx->getAttribute('faqid');
        if ($faqid != '') {
            if (bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $faqid)) {
                $this->faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_FAQCAT_TBL . " where id='" . $babDB->db_escape_string($faqid) . "'"));
                $this->res = $babDB->db_query("select * from " . BAB_FAQ_SUBCAT_TBL . " where id_cat='" . $babDB->db_escape_string($faqid) . "' order by name asc");
                $this->count = $babDB->db_num_rows($this->res);
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
            if ($this->faqinfo['id_root'] == $arr['id']) {
                $this->ctx->curctx->push('FaqSubCatName', $this->faqinfo['category']);
            } else {
                $this->ctx->curctx->push('FaqSubCatName', $arr['name']);
            }
            $this->ctx->curctx->push('FaqId', $arr['id_cat']);
            $this->ctx->curctx->push('FaqSubCatId', $arr['id']);
            $this->ctx->curctx->push('FaqSubCatUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=questions&item=" . $arr['id_cat'] . "&idscat=" . $arr['id']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_FaqSubCategory extends Func_Ovml_Container
{
    public $res;

    public $index;

    public $count;

    public $faqinfo;

    public $IdEntries = array();


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->count = 0;
        $faqsubcatid = $ctx->curctx->getAttribute('faqsubcatid');
        if ($faqsubcatid !== false && $faqsubcatid !== '') {
            $res = $babDB->db_query("select * from " . BAB_FAQ_SUBCAT_TBL . " where id_cat IN (" . $babDB->quote(explode(',', $faqsubcatid)) . ")");
            while ($row = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id_cat'])) {
                    array_push($this->IdEntries, $row['id']);
                }
            }
        }

        $this->count = count($this->IdEntries);
        if ($this->count > 0) {
            $this->res = $babDB->db_query("select * from " . BAB_FAQ_SUBCAT_TBL . " where id IN (" . $babDB->quote($this->IdEntries) . ")");
            $this->count = $babDB->db_num_rows($this->res);
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            if (empty($arr['name'])) {
                $this->faqinfo = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_FAQCAT_TBL . " where id='" . $babDB->db_escape_string($arr['id_cat']) . "'"));
                $this->ctx->curctx->push('FaqSubCatName', $this->faqinfo['category']);
            } else {
                $this->ctx->curctx->push('FaqSubCatName', $arr['name']);
            }
            $this->ctx->curctx->push('FaqId', $arr['id_cat']);
            $this->ctx->curctx->push('FaqSubCatId', $arr['id']);
            $this->ctx->curctx->push('FaqSubCatUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=questions&item=" . $arr['id_cat'] . "&idscat=" . $arr['id']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_FaqQuestions extends Func_Ovml_Container
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
        $faqid = $ctx->curctx->getAttribute('faqid');
        $faqsubcatid = $ctx->curctx->getAttribute('faqsubcatid');
        $req = "select id, idcat from " . BAB_FAQQR_TBL;
        if ($faqid !== false && $faqid !== '') {
            $req .= " where idcat IN (" . $babDB->quote(explode(',', $faqid)) . ")";
            if ($faqsubcatid !== false && $faqsubcatid !== '') {
                $req .= " and id_subcat IN (" . $babDB->quote(explode(',', $faqsubcatid)) . ")";
            }
        } elseif ($faqsubcatid !== false && $faqsubcatid !== '') {
            $req .= " where id_subcat IN (" . $babDB->quote(explode(',', $faqsubcatid)) . ")";
        }

        $res = $babDB->db_query($req);
        while ($row = $babDB->db_fetch_array($res)) {
            if (bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['idcat'])) {
                array_push($this->IdEntries, $row['id']);
            }
        }

        $this->count = count($this->IdEntries);
        if ($this->count > 0) {
            $this->res = $babDB->db_query("select * from " . BAB_FAQQR_TBL . " where id IN (" . $babDB->quote($this->IdEntries) . ")");
            $this->count = $babDB->db_num_rows($this->res);
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FaqQuestion', $arr['question']);
            $this->pushEditor('FaqResponse', $arr['response'], $arr['response_format'], 'bab_faq_response');
            $this->ctx->curctx->push('FaqQuestionId', $arr['id']);
            $this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=viewq&item=" . $arr['idcat'] . "&idscat=" . $arr['id_subcat'] . "&idq=" . $arr['id']);
            $this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=viewpq&idcat=" . $arr['idcat'] . "&idscat=" . $arr['id_subcat'] . "&idq=" . $arr['id']);
            if ($arr['id_modifiedby']) {
                $this->ctx->curctx->push('FaqQuestionDate', bab_mktime($arr['date_modification']));
                $this->ctx->curctx->push('FaqQuestionAuthor', bab_getUserName($arr['id_modifiedby']));
            } else {
                $this->ctx->curctx->push('FaqQuestionDate', '');
                $this->ctx->curctx->push('FaqQuestionAuthor', '');
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


class Func_Ovml_Container_FaqQuestion extends Func_Ovml_Container
{
    public $index;

    public $count;

    public $res;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->res = $babDB->db_query("select * from " . BAB_FAQQR_TBL . " where id='" . $babDB->db_escape_string($ctx->curctx->getAttribute('questionid')) . "'");
        if ($this->res && $babDB->db_num_rows($this->res) == 1)
            $this->count = 1;
        else
            $this->count = 0;
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FaqQuestion', $arr['question']);
            $this->pushEditor('FaqResponse', $arr['response'], $arr['response_format'], 'bab_faq_response');
            $this->ctx->curctx->push('FaqQuestionId', $arr['id']);
            $this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=viewq&item=" . $arr['idcat'] . "&idscat=" . $arr['id_subcat'] . "&idq=" . $arr['id']);
            $this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=viewpq&item=" . $arr['idcat'] . "&idscat=" . $arr['id_subcat'] . "&idq=" . $arr['id']);
            if ($arr['id_modifiedby']) {
                $this->ctx->curctx->push('FaqQuestionDate', bab_mktime($arr['date_modification']));
                $this->ctx->curctx->push('FaqQuestionAuthor', bab_getUserName($arr['id_modifiedby']));
            } else {
                $this->ctx->curctx->push('FaqQuestionDate', '');
                $this->ctx->curctx->push('FaqQuestionAuthor', '');
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


class Func_Ovml_Container_FaqQuestionPrevious extends Func_Ovml_Container_FaqQuestion
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_FaqQuestion::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_FaqQuestions');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('questionid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        parent:setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_FaqQuestionNext extends Func_Ovml_Container_FaqQuestion
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_FaqQuestion::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_FaqQuestions');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('questionid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        parent:setOvmlContext($ctx);
    }
}


class Func_Ovml_Container_RecentFaqQuestions extends Func_Ovml_Container
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
        $this->nbdays = $ctx->curctx->getAttribute('from_lastlog');
        $this->last = $ctx->curctx->getAttribute('last');
        $faqid = $ctx->curctx->getAttribute('faqid');
        $faqsubcatid = $ctx->curctx->getAttribute('faqsubcatid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        $req = "select ft.id, ft.idcat from " . BAB_FAQQR_TBL . " ft";
        $where = array();
        if (0 != $delegationid) {
            $req .= " left join " . BAB_FAQCAT_TBL . " fct on fct.id=ft.idcat";
            $where[] = 'fct.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
        }

        if ($faqid !== false && $faqid !== '') {
            $where[] = "ft.idcat IN (" . $babDB->quote(explode(',', $faqid)) . ")";
        }
        if ($faqsubcatid !== false && $faqsubcatid !== '') {
            $where[] = "ft.id_subcat IN (" . $babDB->quote(explode(',', $faqsubcatid)) . ")";
        }

        if ($this->nbdays !== false) {
            require_once dirname(__FILE__) . '/userinfosincl.php';
            $usersettings = bab_userInfos::getUserSettings();

            $where[] = "ft.date_modification >= DATE_ADD(\"" . $babDB->db_escape_string($usersettings['lastlog']) . "\", INTERVAL -" . $babDB->db_escape_string($this->nbdays) . " DAY)";
        }

        if (count($where)) {
            $req .= " where " . implode(' AND ', $where);
        }

        if ($this->last !== false) {
            $req .= ' LIMIT 0, ' . $babDB->db_escape_string($this->last);
        }

        $res = $babDB->db_query($req);
        while ($row = $babDB->db_fetch_array($res)) {
            if (bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['idcat'])) {
                array_push($this->IdEntries, $row['id']);
            }
        }

        $this->count = count($this->IdEntries);
        if ($this->count > 0) {
            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '') {
                $order = 'asc';
            }
            $this->res = $babDB->db_query("select * from " . BAB_FAQQR_TBL . " where id IN (" . $babDB->quote($this->IdEntries) . ") order by date_modification " . $order);
            $this->count = $babDB->db_num_rows($this->res);
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('FaqQuestion', $arr['question']);
            $this->pushEditor('FaqResponse', $arr['response'], $arr['response_format'], 'bab_faq_response');
            $this->ctx->curctx->push('FaqQuestionId', $arr['id']);
            $this->ctx->curctx->push('FaqQuestionUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=viewq&item=" . $arr['idcat'] . "&idscat=" . $arr['id_subcat'] . "&idq=" . $arr['id']);
            $this->ctx->curctx->push('FaqQuestionPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=faq&idx=viewpq&idcat=" . $arr['idcat'] . "&idscat=" . $arr['id_subcat'] . "&idq=" . $arr['id']);
            if ($arr['id_modifiedby']) {
                $this->ctx->curctx->push('FaqQuestionDate', bab_mktime($arr['date_modification']));
                $this->ctx->curctx->push('FaqQuestionAuthor', bab_getUserName($arr['id_modifiedby']));
            } else {
                $this->ctx->curctx->push('FaqQuestionDate', '');
                $this->ctx->curctx->push('FaqQuestionAuthor', '');
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
