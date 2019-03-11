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



class Func_Ovml_Container_ArticlesHomePages extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $arrid = array();
    public $index;
    public $count;
    public $idgroup;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;

        parent::setOvmlContext($ctx);

        require_once dirname(__FILE__) . '/settings.class.php';
        $settings = bab_getInstance('bab_Settings');
        /*@var $settings bab_Settings */
        $site = $settings->getSiteSettings();

        $this->idgroup = $ctx->curctx->getAttribute('type');
        $order = $ctx->curctx->getAttribute('order');
        if ($order === false || $order === '')
            $order = "asc";

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        switch (mb_strtoupper($order)) {
            case "DESC":
                $order = "ht.ordering DESC";
                break;
            case "RAND":
                $order = "rand()";
                break;
            case "ASC":
            default:
                $order = "ht.ordering";
                break;
        }

        switch (mb_strtolower($this->idgroup)) {
            case "public":
                $this->idgroup = 2; // non registered users
                break;
            case "private":
            default:
                if ($GLOBALS['BAB_SESS_LOGGED']) {
                    $this->idgroup = 1; // registered users
                } else {
                    $this->idgroup = 2; // non registered users
                }
                break;
        }


        $topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);

        $res = $babDB->db_query("select ht.id, at.id_topic, at.restriction from " . BAB_ARTICLES_TBL . " at
            LEFT JOIN " . BAB_HOMEPAGES_TBL . " ht on ht.id_article=at.id
            where
                ht.id_group='" . $babDB->db_escape_string($this->idgroup) . "'
                and ht.id_site='" . $site['id'] . "' and ht.ordering!='0'
                and (at.date_publication='0000-00-00 00:00:00' or at.date_publication <= now())
                and (date_archiving='0000-00-00 00:00:00' or date_archiving >= now())
            GROUP BY at.id order by " . $babDB->db_escape_string($order));

        while ($arr = $babDB->db_fetch_array($res)) {
            if ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])) {
                if (isset($topview[$arr['id_topic']])) {
                    $this->IdEntries[] = $arr['id'];
                }
            }
        }

        $this->count = count($this->IdEntries);
        if ($this->count > 0) {
            $sQuery = 'SELECT
                    at.*,
                    count(aft.id) as nfiles,
                    topicCategory.id_dgowner iIdDelegation
                FROM ' . BAB_ARTICLES_TBL . ' at ' . 'LEFT JOIN ' . BAB_HOMEPAGES_TBL . ' ht on ht.id_article=at.id ' . 'LEFT JOIN ' . BAB_ART_FILES_TBL . ' aft on aft.id_article=at.id ' . 'LEFT JOIN ' . BAB_TOPICS_TBL . ' topic on topic.id = at.id_topic ' . 'LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' topicCategory on topicCategory.id = topic.id_cat ' . 'WHERE
                    ht.id IN (' . $babDB->quote($this->IdEntries) . ') ' . 'GROUP BY ' . 'at.id order by ' . $babDB->db_escape_string($order);

            $this->res = $babDB->db_query($sQuery);
        }

        $this->count = isset($this->res) ? $babDB->db_num_rows($this->res) : 0;
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('ArticleTitle', $arr['title']);

            $this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
            $this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');

            if (empty($arr['body'])) {
                $this->ctx->curctx->push('ArticleReadMore', 0);
            } else {
                $this->ctx->curctx->push('ArticleReadMore', 1);
            }
            $this->ctx->curctx->push('ArticleId', $arr['id']);
            $this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=entry&idx=more&article=" . $arr['id'] . "&idg=" . $this->idgroup);
            $this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
            if ($arr['date'] == $arr['date_modification']) {
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
            } else {
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
            }
            $this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date']));
            $this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
            $this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
            $this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
            $this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
            $topic = bab_getTopicArray($arr['id_topic']);
            $this->ctx->curctx->push('ArticleCategoryId', $topic['id_cat']);
            $this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
            $this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
            list ($topictitle) = $babDB->db_fetch_array($babDB->db_query("select category from " . BAB_TOPICS_TBL . " where id='" . $babDB->db_escape_string($arr['id_topic']) . "'"));
            $this->ctx->curctx->push('ArticleTopicTitle', $topictitle);

            if (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])) {
                $this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Modify&topics=" . $arr['id_topic'] . '&article=' . $arr['id']);
                $this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
            } else {
                $this->ctx->curctx->push('ArticleEditUrl', '');
                $this->ctx->curctx->push('ArticleEditName', '');
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


class Func_Ovml_Container_ArticleCategories extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $index;
    public $count;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $parentid = $ctx->curctx->getAttribute('parentid');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        if ($parentid === false || $parentid === '') {
            $parentid[] = 0;
        } else {
            require_once dirname(__FILE__) . '/artapi.php';
            $topcatview = bab_getReadableArticleCategories();
            $parentid = array_intersect(array_keys($topcatview), explode(',', $parentid));
        }

        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        include_once $GLOBALS['babInstallPath'] . 'utilit/artapi.php';
        $this->res = bab_getArticleCategoriesRes($parentid, $delegationid);

        if (false === $this->res) {
            $this->count = 0;
        } else {
            $this->count = $babDB->db_num_rows($this->res);
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setCategoryAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CategoryName', $arr['title']);
            $this->ctx->curctx->push('CategoryDescription', $arr['description']);
            $this->ctx->curctx->push('CategoryId', $arr['id']);
            $this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
            $this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=topusr&cat=" . $arr['id']);
            $this->ctx->curctx->push('CategoryDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_ParentsArticleCategory extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $res;
    public $index;
    public $count;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $categoryid = $ctx->curctx->getAttribute('categoryid');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        if ($categoryid === false || $categoryid === '') {
            $this->count = 0;
        } else {
            require_once dirname(__FILE__) . '/artapi.php';
            $topcats = bab_getArticleCategories();

            while ($topcats[$categoryid]['parent'] != 0) {
                $this->IdEntries[] = $topcats[$categoryid]['parent'];
                $categoryid = $topcats[$categoryid]['parent'];
            }
            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $reverse = $ctx->curctx->getAttribute('reverse');
                if ($reverse === false || $reverse !== '1')
                    $this->IdEntries = array_reverse($this->IdEntries);
                $this->res = $babDB->db_query("select * from " . BAB_TOPICS_CATEGORIES_TBL . " where id IN (" . $babDB->quote($this->IdEntries) . ")");
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

            setCategoryAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CategoryName', $arr['title']);
            $this->ctx->curctx->push('CategoryDescription', $arr['description']);
            $this->ctx->curctx->push('CategoryId', $arr['id']);
            $this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
            $this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=topusr&cat=" . $arr['id']);
            $this->ctx->curctx->push('CategoryDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_ArticleCategory extends Func_Ovml_Container
{
    public $arrid = array();
    public $index;
    public $count;
    public $res;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        $this->count = 0;
        parent::setOvmlContext($ctx);
        $catid = $ctx->curctx->getAttribute('categoryid');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        require_once dirname(__FILE__) . '/artapi.php';
        $topcatview = bab_getReadableArticleCategories();

        if ($catid === false || $catid === '') {
            $catid = array_keys($topcatview);
        } else {
            $catid = array_intersect(array_keys($topcatview), explode(',', $catid));
        }

        if (count($catid) > 0) {
            $sql = 'SELECT topics_categories.*, topcat_order.ordering
                    FROM ' . BAB_TOPICS_CATEGORIES_TBL . ' AS topics_categories
                    LEFT JOIN ' . BAB_TOPCAT_ORDER_TBL . ' AS topcat_order
                        ON topcat_order.type=' . $babDB->quote('1') . '
                        AND topcat_order.id_topcat = topics_categories.id
                    WHERE topics_categories.id IN (' . $babDB->quote($catid) . ')
                    ORDER BY topcat_order.ordering ASC';

            $this->res = $babDB->db_query($sql);
            $this->count = $babDB->db_num_rows($this->res);
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setCategoryAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CategoryName', $arr['title']);
            $this->ctx->curctx->push('CategoryDescription', $arr['description']);
            $this->ctx->curctx->push('CategoryId', $arr['id']);
            $this->ctx->curctx->push('CategoryParentId', $arr['id_parent']);
            $this->ctx->curctx->push('TopicsUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=topusr&cat=" . $arr['id']);
            $this->ctx->curctx->push('CategoryDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_ArticleCategoryPrevious extends Func_Ovml_Container_ArticleCategory
{
    public $handler;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleCategories');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('categoryid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        $this->bab_ArticleCategory($ctx);
    }
}


class Func_Ovml_Container_ArticleCategoryNext extends Func_Ovml_Container_ArticleCategory
{
    public $handler;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleCategories');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('categoryid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        $this->bab_ArticleCategory($ctx);
    }
}




class Func_Ovml_Container_ArticleTopics extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $ctx;
    public $index;
    public $count;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $catid = $ctx->curctx->getAttribute('categoryid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        require_once dirname(__FILE__) . '/artapi.php';
        $topcatview = bab_getReadableArticleCategories();

        if ($catid === false || $catid === '')
            $catid = array_keys($topcatview);
        else
            $catid = array_intersect(array_keys($topcatview), explode(',', $catid));

        include_once $GLOBALS['babInstallPath'] . 'utilit/artapi.php';
        $this->res = bab_getArticleTopicsRes($catid, $delegationid);

        if (false === $this->res) {
            $this->count = 0;
        } else {
            $this->count = $babDB->db_num_rows($this->res);
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setTopicAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id_cat'], $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('TopicTotal', $this->count);
            $this->ctx->curctx->push('TopicName', $arr['category']);

            $this->pushEditor('TopicDescription', $arr['description'], $arr['description_format'], 'bab_topic');
            $this->ctx->curctx->push('TopicId', $arr['id']);
            $this->ctx->curctx->push('TopicLanguage', $arr['lang']);
            $this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&topics=" . $arr['id']);
            if (bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id'])) {
                $this->ctx->curctx->push('TopicSubmitUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Submit&topics=" . $arr['id']);
                $this->ctx->curctx->push('TopicSubmitName', bab_translate("Submit"));
            } else {
                $this->ctx->curctx->push('TopicSubmitUrl', '');
                $this->ctx->curctx->push('TopicSubmitName', '');
            }
            if (bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id'])) {
                $this->ctx->curctx->push('TopicManageUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=topman&idx=Articles&item=" . $arr['id']);
                $this->ctx->curctx->push('TopicManageName', bab_translate("Articles management"));
            } else {
                $this->ctx->curctx->push('TopicManageUrl', '');
                $this->ctx->curctx->push('TopicManageName', '');
            }
            list ($cattitle, $iddgowner) = $babDB->db_fetch_array($babDB->db_query("select title, id_dgowner from " . BAB_TOPICS_CATEGORIES_TBL . " where id='" . $babDB->db_escape_string($arr['id_cat']) . "'"));
            $this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
            $this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
            $this->ctx->curctx->push('TopicCategoryDelegationId', $iddgowner);

            /**
             *
             * @see bab_TopicNotificationSubscription()
             */
            if (! $GLOBALS['BAB_SESS_LOGGED'] || 'N' === $arr['notify'] || 0 === (int) $arr['allow_unsubscribe']) {
                $this->ctx->curctx->push('TopicSubscription', - 1);
                $this->ctx->curctx->push('TopicSubscriptionUrl', '');
            } else {
                $this->ctx->curctx->push('TopicSubscription', null === $arr['unsubscribed'] ? 1 : 0);
                $this->ctx->curctx->push('TopicSubscriptionUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=subscription&topic=" . $arr['id']);
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

class Func_Ovml_Container_ArticleTopic extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $topicid;
    public $count;
    public $index;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->topicid = $ctx->curctx->getAttribute('topicid');
        $this->topicname = $ctx->curctx->getAttribute('topicname');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        if ($this->topicid === false || $this->topicid === '') {
            $this->IdEntries = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
        } else {
            $this->IdEntries = array_values(array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $this->topicid)));
        }
        $this->count = count($this->IdEntries);

        if ($this->count > 0) {
            $req = "
                SELECT t.*, u.id_user unsubscribed
                FROM " . BAB_TOPICS_TBL . " t

                LEFT JOIN bab_topics_unsubscribe u
                ON t.id=u.id_topic AND u.id_user=" . $babDB->quote(bab_getUserId()) . "
                WHERE t.id IN (" . $babDB->quote($this->IdEntries) . ")
            ";
            if ($this->topicname !== false && $this->topicname !== '') {
                $req .= " and t.category like '" . $babDB->db_escape_like($this->topicname) . "'";
            }

            $this->res = $babDB->db_query($req);
            $this->count = $babDB->db_num_rows($this->res);

            $req = 'SELECT at.id_topic as id, count(at.id) as nb
                    FROM ' . BAB_ARTICLES_TBL . ' AS at

                    WHERE at.id_topic IN (' . $babDB->quote($this->IdEntries) . ')
                    AND (at.date_publication=' . $babDB->quote('0000-00-00 00:00:00') . ' OR at.date_publication <= NOW())
                    AND archive="N"
                    GROUP BY at.id_topic';

            $res = $babDB->db_query($req);
            while ($arr = $babDB->db_fetch_array($res)) {
                $this->nbarticles[$arr['id']] = $arr['nb'];
            }
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setTopicAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id_cat'], $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('TopicName', $arr['category']);
            $this->pushEditor('TopicDescription', $arr['description'], $arr['description_format'], 'bab_topic');
            $this->ctx->curctx->push('TopicId', $arr['id']);
            $this->ctx->curctx->push('TopicLanguage', $arr['lang']);
            if (! isset($this->nbarticles[$arr['id']])) {
                $this->nbarticles[$arr['id']] = 0;
            }
            $this->ctx->curctx->push('TopicArticleNumber', $this->nbarticles[$arr['id']]);
            $this->ctx->curctx->push('ArticlesListUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&topics=" . $arr['id']);
            if (bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id'])) {
                $this->ctx->curctx->push('TopicSubmitUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Submit&topics=" . $arr['id']);
                $this->ctx->curctx->push('TopicSubmitName', bab_translate("Submit"));
            } else {
                $this->ctx->curctx->push('TopicSubmitUrl', '');
                $this->ctx->curctx->push('TopicSubmitName', '');
            }

            if (bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id'])) {
                $this->ctx->curctx->push('TopicManageUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=topman&idx=Articles&item=" . $arr['id']);
                $this->ctx->curctx->push('TopicManageName', bab_translate("Articles management"));
            } else {
                $this->ctx->curctx->push('TopicManageUrl', '');
                $this->ctx->curctx->push('TopicManageName', '');
            }
            list ($cattitle, $iddgowner) = $babDB->db_fetch_array($babDB->db_query("select title, id_dgowner from " . BAB_TOPICS_CATEGORIES_TBL . " where id='" . $babDB->db_escape_string($arr['id_cat']) . "'"));
            $this->ctx->curctx->push('TopicCategoryId', $arr['id_cat']);
            $this->ctx->curctx->push('TopicCategoryTitle', $cattitle);
            $this->ctx->curctx->push('TopicCategoryDelegationId', $iddgowner);

            /**
             *
             * @see bab_TopicNotificationSubscription()
             */
            if (! $GLOBALS['BAB_SESS_LOGGED'] || 'N' === $arr['notify'] || 0 === (int) $arr['allow_unsubscribe']) {
                $this->ctx->curctx->push('TopicSubscription', - 1);
                $this->ctx->curctx->push('TopicSubscriptionUrl', '');
            } else {
                $this->ctx->curctx->push('TopicSubscription', null === $arr['unsubscribed'] ? 1 : 0);
                $this->ctx->curctx->push('TopicSubscriptionUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=subscription&topic=" . $arr['id']);
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


class Func_Ovml_Container_ArticleTopicPrevious extends Func_Ovml_Container_ArticleTopic
{
    public $handler;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleTopics');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('topicid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        $this->bab_ArticleTopic($ctx);
    }
}


class Func_Ovml_Container_ArticleTopicNext extends Func_Ovml_Container_ArticleTopic
{
    public $handler;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_ArticleCategories');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('topicid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        $this->bab_ArticleTopic($ctx);
    }
}



class Func_Ovml_Container_Articles extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $index;
    public $count;
    public $res;

    public $imageheightmax;
    public $imagewidthmax;


    /**
     *
     * @param int $groupId
     * @return int[]
     */
    protected function getHomePageArticleIds($groupId)
    {
        global $babDB;

        require_once dirname(__FILE__) . '/settings.class.php';
        $settings = bab_getInstance('bab_Settings');
        /*@var $settings bab_Settings */
        $site = $settings->getSiteSettings();

        $articleIds = array();

        $topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);

        $res = $babDB->db_query("
            SELECT at.id, at.id_topic, at.restriction
            FROM " . BAB_ARTICLES_TBL . " at
            LEFT JOIN " . BAB_HOMEPAGES_TBL . " ht ON ht.id_article = at.id
            WHERE
                ht.id_group = " . $babDB->quote($groupId) . "
                AND ht.id_site = " . $babDB->quote($site['id']) . " AND ht.ordering != '0'
                AND (at.date_publication = '0000-00-00 00:00:00' OR at.date_publication <= NOW())
                AND (date_archiving = '0000-00-00 00:00:00' OR date_archiving >= NOW())
            GROUP BY at.id");

        while ($arr = $babDB->db_fetch_array($res)) {
            if ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])) {
                if (isset($topview[$arr['id_topic']])) {
                    $articleIds[] = $arr['id'];
                }
            }
        }

        return $articleIds;
    }


    /**
     *
     * @param int $categoryId
     * @param
     *            int[] &$topicIds
     * @return int[]
     */
    protected function getTopicIds($categoryId, &$topicIds)
    {
        global $babDB;
        require_once dirname(__FILE__) . '/artapi.php';
        $topcats = bab_getArticleCategories();

        foreach ($topcats as $id => $arr) {
            if ($categoryId == $arr['parent']) {
                $this->getTopicIds($id, $topicIds);
            }
        }

        $res = $babDB->db_query("
            SELECT id
            FROM " . BAB_TOPICS_TBL . "
            WHERE
                id_cat = " . $babDB->quote($categoryId) . "
                AND id IN(" . $babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)) . ")");
        while ($row = $babDB->db_fetch_array($res)) {
            $topicIds[] = $row['id'];
        }
    }


    /**
     *
     * {@inheritdoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $articleid = $ctx->curctx->getAttribute('articleid');
        $topicid = $ctx->curctx->getAttribute('topicid');
        $topcatid = $ctx->curctx->getAttribute('categoryid');
        $this->excludetopicid = $ctx->curctx->getAttribute('excludetopicid');
        $excludetopcatid = $ctx->curctx->getAttribute('excludecategoryid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');
        $homepage = $ctx->curctx->getAttribute('homepage');

        $rows = $ctx->curctx->getAttribute('rows');
        $offset = $ctx->curctx->getAttribute('offset');

        $limit = $ctx->getAttribute('limit');
        if (is_string($limit)) {
            $limits = explode(',', $limit);
            if (count($limits) === 1) {
                $rows = $limit;
            } else {
                $offset = $limits[0];
                $rows = $limits[1];
            }
        }


        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        $delegationSql = ' ';
        $sLeftJoin = ' ';
        if (0 != $delegationid) {
            $sLeftJoin = 'LEFT JOIN ' . BAB_TOPICS_TBL . ' t ON t.id = at.id_topic ' . 'LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = t.id_cat ';

            $delegationSql = ' AND tpc.id_dgowner = ' . $babDB->quote($delegationid) . ' ';
        }

        $homePageSql = '';
        if ($homepage) {
            switch (mb_strtolower($homepage)) {
                case 'public':
                    $groupId = BAB_UNREGISTERED_GROUP;
                    break;
                case 'private':
                default:
                    if ($GLOBALS['BAB_SESS_LOGGED']) {
                        $groupId = BAB_REGISTERED_GROUP;
                    } else {
                        $groupId = BAB_UNREGISTERED_GROUP;
                    }
                    break;
            }

            $articleIds = $this->getHomePageArticleIds($groupId);
            $homePageSql = " AND at.id IN(" . $babDB->quote($articleIds) . ") ";
        }

        $articleSql = '';
        if ($articleid) {
            $articleid = explode(',', $articleid);
            $articleSql = " AND at.id IN(" . $babDB->quote($articleid) . ") ";
        }

        $topicIds = array();
        if ($topcatid === false || $topcatid === '') {
            if ($topicid === false || $topicid === '') {
                $topicIds = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
            } else {
                $topicIds = array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $topicid));
            }
        } else {
            $this->getTopicIds($topcatid, $topicIds);
        }

        if (count($topicIds) > 0) {
            if ($this->excludetopicid !== false && $this->excludetopicid !== '') {
                $topicIds = array_diff($topicIds, explode(',', $this->excludetopicid));
            }

            if ($excludetopcatid !== false && $excludetopcatid !== '') {
                $excludeTopicIds = array();
                $this->getTopicIds($excludetopcatid, $excludeTopicIds);
                $topicIds = array_diff($topicIds, $excludeTopicIds);
            }


            $archive = $ctx->curctx->getAttribute('archive');
            if ($archive === false || $archive === '') {
                $archive = "no";
            }

            switch (mb_strtoupper($archive)) {
                case 'NO':
                    $archiveSql = " AND archive='N' ";
                    break;
                case 'YES':
                    $archiveSql = " AND archive='Y' ";
                    break;
                default:
                    $archiveSql = '';
                    break;
            }

            $minRating = $ctx->curctx->getAttribute('minrating');
            if (! is_numeric($minRating)) {
                $minRating = 0;
                $ratingGroupBy = ' GROUP BY at.id ';
            } else {
                $ratingGroupBy = ' GROUP BY at.id HAVING average_rating >= ' . $babDB->quote($minRating) . ' ';
            }

            $req = 'SELECT at.id, at.restriction, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
                    FROM ' . BAB_ARTICLES_TBL . ' AS at
                    LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
                    ' . $sLeftJoin . '
                    WHERE at.id_topic IN (' . $babDB->quote($topicIds) . ')
                    AND (at.date_publication=' . $babDB->quote('0000-00-00 00:00:00') . ' OR at.date_publication <= NOW())' . $archiveSql . $delegationSql . $articleSql . $homePageSql . $ratingGroupBy;

            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '') {
                $order = 'asc';
            }

            /* topicorder=yes : order defined by managers */
            $forder = $ctx->curctx->getAttribute('topicorder');
            switch (mb_strtoupper($forder)) {
                case 'YES':
                    $forder = true;
                    break;
                case 'NO': /* no break */
                default:
                    $forder = false;
                    break;
            }

            $orderby = $ctx->curctx->getAttribute('orderby');
            if ($orderby === false || $orderby === '') {
                $orderby = "at.date";
            } else {
                switch (mb_strtolower($orderby)) {
                    case 'title':
                        $orderby = 'at.title';
                        break;
                    case 'rating':
                        $orderby = 'average_rating';
                        break;
                    case 'creation':
                        $orderby = 'at.date';
                        break;
                    case 'publication':
                        $orderby = 'at.date_publication';
                        break;
                    case 'modification':
                    default:
                        $orderby = 'at.date_modification';
                        break;
                }
            }

            switch (mb_strtoupper($order)) {
                case 'ASC':
                    if ($forder) { /* topicorder=yes : order defined by managers */
                        $order = 'at.ordering asc, at.date_modification desc';
                    } else {
                        $order = $orderby . ' asc';
                    }
                    break;
                case 'RAND':
                    $order = 'rand()';
                    break;
                case 'DESC':
                default:
                    if ($forder) { /* topicorder=yes : order defined by managers */
                        $order = 'at.ordering desc, at.date_modification asc';
                    } else {
                        $order = $orderby . ' desc';
                    }
                    break;
            }

            $req .= 'ORDER BY ' . $order;


            if ($rows === false || $rows === '')
                $rows = "-1";

            if ($offset === false || $offset === '') {
                $offset = "0";
            }

            if ($rows != - 1) {
                $req .= ' limit ' . $babDB->db_escape_string($offset) . ', ' . $babDB->db_escape_string($rows);
            }

            $res = $babDB->db_query($req);

            while ($arr = $babDB->db_fetch_array($res)) {
                if ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])) {
                    $this->IdEntries[] = $arr['id'];
                }
            }

            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $req = 'SELECT at.*, COUNT(aft.id) AS nfiles, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
                        FROM ' . BAB_ARTICLES_TBL . ' AS at
                        LEFT JOIN ' . BAB_ART_FILES_TBL . ' AS aft ON at.id=aft.id_article
                        LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
                        WHERE at.id IN (' . $babDB->quote($this->IdEntries) . ')
                        ' . $ratingGroupBy . '
                        ORDER BY ' . $order;

                $this->res = $babDB->db_query($req);
            }
        } else {
            $this->count = 0;
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('ArticleTitle', $arr['title']);
            $this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
            $this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
            if (empty($arr['body'])) {
                $this->ctx->curctx->push('ArticleReadMore', 0);
            } else {
                $this->ctx->curctx->push('ArticleReadMore', 1);
            }
            $this->ctx->curctx->push('ArticleId', $arr['id']);
            $this->ctx->curctx->push('ArticleUrl', bab_siteMap::url('babArticle_' . $arr['id'], $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=More&topics=" . $arr['id_topic'] . "&article=" . $arr['id']));
            $this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=viewa&topics=" . $arr['id_topic'] . "&article=" . $arr['id']);
            $this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
            if ($arr['date'] == $arr['date_modification']) {
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
            } else {
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
            }
            $this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date'])); /* for compatibility */
            $this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
            $this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
            $this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
            $this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
            $topic = bab_getTopicArray($arr['id_topic']);
            $this->ctx->curctx->push('ArticleCategoryId', $topic['id_cat']);
            $this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
            $this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
            if (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])) {
                $this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Modify&topics=" . $arr['id_topic'] . '&article=' . $arr['id']);
                $this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
            } else {
                $this->ctx->curctx->push('ArticleEditUrl', '');
                $this->ctx->curctx->push('ArticleEditName', '');
            }
            $this->ctx->curctx->push('ArticleAverageRating', (float) $arr['average_rating']);
            $this->ctx->curctx->push('ArticleNbRatings', (float) $arr['nb_ratings']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        }
        $this->idx = 0;
        return false;
    }
}



class Func_Ovml_Container_Article extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $res;
    public $index;
    public $count;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $articleid = $ctx->curctx->getAttribute('articleid');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        if ($articleid === false || $articleid === '')
            $this->count = 0;
        else {
            $res = $babDB->db_query("select id, id_topic, restriction from " . BAB_ARTICLES_TBL . " where id IN (" . $babDB->quote(explode(',', $articleid)) . ") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())");
            while ($arr = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']))) {
                    $this->IdEntries[] = $arr['id'];
                }
            }
            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $this->res = $babDB->db_query("select at.*, count(aft.id) as nfiles from " . BAB_ARTICLES_TBL . " at left join " . BAB_ART_FILES_TBL . " aft on at.id=aft.id_article where at.id IN (" . $babDB->quote($this->IdEntries) . ") group by at.id");
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

            setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('ArticleTitle', $arr['title']);
            $this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
            $this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
            if (empty($arr['body']))
                $this->ctx->curctx->push('ArticleReadMore', 0);
            else
                $this->ctx->curctx->push('ArticleReadMore', 1);
            $this->ctx->curctx->push('ArticleId', $arr['id']);
            $this->ctx->curctx->push('ArticleUrl', bab_siteMap::url('babArticle_' . $arr['id'], $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=More&topics=" . $arr['id_topic'] . "&article=" . $arr['id']));
            $this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=viewa&topics=" . $arr['id_topic'] . "&article=" . $arr['id']);
            $this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
            if ($arr['date'] == $arr['date_modification'])
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
            else
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
            $this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date'])); /* for compatibility */
            $this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
            $this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
            $this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
            $this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
            $topic = bab_getTopicArray($arr['id_topic']);
            $this->ctx->curctx->push('ArticleCategoryId', $topic['id_cat']);
            $this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
            $this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
            if (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])) {
                $this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Modify&topics=" . $arr['id_topic'] . '&article=' . $arr['id']);
                $this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
            } else {
                $this->ctx->curctx->push('ArticleEditUrl', '');
                $this->ctx->curctx->push('ArticleEditName', '');
            }
            $this->ctx->curctx->push('ArticleAverageRating', bab_getArticleAverageRating($arr['id']));
            $this->ctx->curctx->push('ArticleNbRatings', bab_getArticleNbRatings($arr['id']));
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_ArticleFiles extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $res;
    public $index;
    public $count;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        require_once dirname(__FILE__) . '/artincl.php';
        parent::setOvmlContext($ctx);
        $articleid = $ctx->curctx->getAttribute('articleid');
        if ($articleid === false || $articleid === '') {
            $this->count = 0;
        } else {
            $res = $babDB->db_query("select id, id_topic, restriction from " . BAB_ARTICLES_TBL . " where id IN (" . $babDB->quote(explode(',', $articleid)) . ") and (date_publication='0000-00-00 00:00:00' or date_publication <= now())");
            while ($arr = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id_topic']) && ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction']))) {
                    $this->IdEntries[] = $arr['id'];
                }
            }
            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $this->res = $babDB->db_query("select aft.*, at.id_topic from " . BAB_ART_FILES_TBL . " aft left join " . BAB_ARTICLES_TBL . " at on aft.id_article=at.id where aft.id_article IN (" . $babDB->quote($this->IdEntries) . ") ORDER BY aft.ordering");
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
            $this->ctx->curctx->push('ArticleFileName', $arr['name']);
            $this->ctx->curctx->push('ArticleFileDescription', $arr['description']);
            $this->ctx->curctx->push('ArticleFileUrlGet', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=getf&topics=" . $arr['id_topic'] . "&idf=" . $arr['id']);
            $this->ctx->curctx->push('ArticleFileFullPath', bab_getUploadArticlesPath() . $arr['id_article'] . "," . stripslashes($arr['name']));
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}

class Func_Ovml_Container_ArticlePrevious extends Func_Ovml_Container_Article
{
    public $handler;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Articles');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('articleid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        parent::setOvmlContext($ctx);
    }
}

class Func_Ovml_Container_ArticleNext extends Func_Ovml_Container_Article
{
    public $handler;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Articles');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('articleid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        parent::setOvmlContext($ctx);
    }
}




class Func_Ovml_Container_RecentArticles extends Func_Ovml_Container
{
    public $res;
    public $IdEntries = array();
    public $arrid = array();
    public $index;
    public $count;
    public $resarticles;
    public $nbdays;
    public $last;
    public $topicid;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->nbdays = $ctx->curctx->getAttribute('from_lastlog');
        $this->last = $ctx->curctx->getAttribute('last');
        $this->topicid = $ctx->curctx->getAttribute('topicid');
        $this->topcatid = $ctx->curctx->getAttribute('categoryid');
        $lang = $ctx->curctx->getAttribute('lang');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        if ($this->topcatid === false || $this->topcatid === '') {
            if ($this->topicid === false || $this->topicid === '')
                $this->topicid = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
            else
                $this->topicid = array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $this->topicid));
        } else {
            $this->topicid = array();
            $this->gettopics($this->topcatid);
        }

        if (count($this->topicid) > 0) {
            $this->excludetopicid = $ctx->curctx->getAttribute('excludetopicid');
            if ($this->excludetopicid !== false && $this->excludetopicid !== '') {
                $this->topicid = array_diff($this->topicid, explode(',', $this->excludetopicid));
            }

            $archive = $ctx->curctx->getAttribute('archive');
            if ($archive === false || $archive === '')
                $archive = "no";

            switch (mb_strtoupper($archive)) {
                case 'NO':
                    $archive = " AND at.archive='N' ";
                    break;
                case 'YES':
                    $archive = " AND at.archive='Y' ";
                    break;
                default:
                    $archive = '';
                    break;
            }

            $minRating = $ctx->curctx->getAttribute('minrating');
            if (! is_numeric($minRating)) {
                $minRating = 0;
                $ratingGroupBy = ' GROUP BY at.id ';
            } else {
                $ratingGroupBy = ' GROUP BY at.id HAVING average_rating >= ' . $babDB->quote($minRating) . ' ';
            }
            ;


            $req = 'SELECT ' . 'at.id, ' . 'at.restriction ' . ', AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings ' . 'FROM ' . BAB_ARTICLES_TBL . ' at ' . 'LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0 ';

            $sDelegation = ' ';
            if (0 != $delegationid) {
                $req .= 'LEFT JOIN ' . BAB_TOPICS_TBL . ' tp ON tp.id = at.id_topic ' . 'LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';

                $sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
            }

            $req .= 'WHERE ' . 'at.id_topic IN (' . $babDB->quote($this->topicid) . ') AND ' . '(at.date_publication = \'0000-00-00 00:00:00\' OR at.date_publication <= now()) ' . $sDelegation;

            if ($this->nbdays !== false && bab_isUserLogged()) {
                require_once dirname(__FILE__) . '/userinfosincl.php';
                $usersettings = bab_userInfos::getUserSettings();

                $req .= " AND at.date >= DATE_ADD(" . $babDB->quote($usersettings['lastlog']) . ", INTERVAL -" . $babDB->db_escape_string($this->nbdays) . " DAY)";
            }


            if ($lang !== false) {
                $req .= " AND at.lang='" . $babDB->db_escape_string($lang) . "'";
            }

            $req .= $archive;

            $req .= $ratingGroupBy;

            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '') {
                $order = 'desc';
            }

            /* topicorder=yes : order defined by managers */
            $forder = $ctx->curctx->getAttribute('topicorder');
            switch (mb_strtoupper($forder)) {
                case 'YES':
                    $forder = true;
                    break;
                case 'NO': /* no break */
                default:
                    $forder = false;
                    break;
            }

            $orderby = $ctx->curctx->getAttribute('orderby');
            if ($orderby === false || $orderby === '') {
                $orderby = 'at.date_modification';
            } else {
                switch (mb_strtolower($orderby)) {
                    case 'title':
                        $orderby = 'at.title';
                        break;
                    case 'rating':
                        $orderby = 'average_rating';
                        break;
                    case 'creation':
                        $orderby = 'at.date';
                        break;
                    case 'publication':
                        $orderby = 'at.date_publication';
                        break;
                    case 'modification':
                    default:
                        $orderby = 'at.date_modification';
                        break;
                }
            }

            switch (mb_strtoupper($order)) {
                case 'ASC':
                    if ($forder) { /* topicorder=yes : order defined by managers */
                        $order = 'at.ordering ASC, at.date_modification DESC';
                    } else {
                        $order = $orderby . ' ASC';
                    }
                    break;
                case 'RAND':
                    $order = 'rand()';
                    break;
                case 'DESC':
                default:
                    if ($forder) { /* topicorder=yes : order defined by managers */
                        $order = 'at.ordering DESC, at.date_modification ASC';
                    } else {
                        $order = $orderby . ' DESC';
                    }
                    break;
            }

            $req .= ' ORDER BY ' . $order;

            if (! empty($this->last) && is_numeric($this->last)) {
                $req .= ' LIMIT 0, ' . $babDB->db_escape_string($this->last);
            }

            $this->resarticles = $babDB->db_query($req);
            while ($arr = $babDB->db_fetch_array($this->resarticles)) {
                if ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])) {
                    $this->IdEntries[] = $arr['id'];
                }
            }
            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $req = 'SELECT at.*, atc.id_dgowner, COUNT(aft.id) AS nfiles, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
                            FROM ' . BAB_ARTICLES_TBL . ' AS at
                            LEFT JOIN ' . BAB_ART_FILES_TBL . ' AS aft ON at.id=aft.id_article
                            LEFT JOIN ' . BAB_TOPICS_TBL . ' AS att ON at.id_topic=att.id
                            LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' AS atc ON att.id_cat=atc.id
                            LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
                            WHERE at.id IN (' . $babDB->quote($this->IdEntries) . ')
                            ' . $ratingGroupBy . '
                            ORDER BY ' . $order;
                $this->res = $babDB->db_query($req);
                $this->count = $babDB->db_num_rows($this->res);
            }
        } else {
            $this->count = 0;
        }
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function gettopics($idparent)
    {
        require_once dirname(__FILE__) . '/artapi.php';
        $topcats = bab_getArticleCategories();

        foreach ($topcats as $id => $arr) {
            if ($idparent == $arr['parent']) {
                $this->gettopics($id);
            }
        }

        $babDB = &$GLOBALS['babDB'];


        $res = $babDB->db_query("select id from " . BAB_TOPICS_TBL . " where id_cat='" . $babDB->db_escape_string($idparent) . "' AND id IN(" . $babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)) . ")");
        while ($row = $babDB->db_fetch_array($res)) {
            $this->topicid[] = $row['id'];
        }
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('ArticleTitle', $arr['title']);
            $this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
            $this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
            if (empty($arr['body']))
                $this->ctx->curctx->push('ArticleReadMore', 0);
            else
                $this->ctx->curctx->push('ArticleReadMore', 1);
            $this->ctx->curctx->push('ArticleId', $arr['id']);
            $this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
            if ($arr['date'] == $arr['date_modification'])
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_author']);
            else
                $this->ctx->curctx->push('ArticleModifiedBy', $arr['id_modifiedby']);
            $this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date_modification'])); /* for compatibility */
            $this->ctx->curctx->push('ArticleDateModification', bab_mktime($arr['date_modification']));
            $this->ctx->curctx->push('ArticleDatePublication', bab_mktime($arr['date_publication']));
            $this->ctx->curctx->push('ArticleDateCreation', bab_mktime($arr['date']));
            $this->ctx->curctx->push('ArticleUrl', bab_siteMap::url('babArticle_' . $arr['id'], $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=More&topics=" . $arr['id_topic'] . "&article=" . $arr['id']));
            $this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=viewa&topics=" . $arr['id_topic'] . "&article=" . $arr['id']);
            $this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
            $topic = bab_getTopicArray($arr['id_topic']);
            $this->ctx->curctx->push('ArticleCategoryId', $topic['id_cat']);
            $this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
            $this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
            $this->ctx->curctx->push('ArticleDelegationId', $arr['id_dgowner']);
            if (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])) {
                $this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Modify&topics=" . $arr['id_topic'] . '&article=' . $arr['id']);
                $this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
            } else {
                $this->ctx->curctx->push('ArticleEditUrl', '');
                $this->ctx->curctx->push('ArticleEditName', '');
            }
            $this->ctx->curctx->push('ArticleAverageRating', (float) $arr['average_rating']);
            $this->ctx->curctx->push('ArticleNbRatings', (float) $arr['nb_ratings']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}

class Func_Ovml_Container_RecentComments extends Func_Ovml_Container
{
    public $index;
    public $count;
    public $rescomments;
    public $countcomments;
    public $lastlog;
    public $nbdays;
    public $last;
    public $articleid;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $this->nbdays = $ctx->curctx->getAttribute('from_lastlog');
        $this->last = $ctx->curctx->getAttribute('last');
        $this->articleid = $ctx->curctx->getAttribute('articleid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        if ($this->articleid === false || $this->articleid === '')
            $arrid = array();
        else
            $arrid = explode(',', $this->articleid);

        $req = '';
        $topview = ' ';
        if (count(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)) > 0) {

            $req = 'SELECT ' . '* ' . 'FROM ' . BAB_COMMENTS_TBL . ' ';

            if (count($arrid) > 0) {
                $topview = "where id_article IN (" . $babDB->quote($arrid) . ") and confirmed='Y' and id_topic IN (" . $babDB->quote(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL))) . ")";
            } else {
                $topview = "where confirmed='Y' and id_topic IN (" . $babDB->quote(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL))) . ")";
            }

            $sDelegation = ' ';
            if (0 != $delegationid) {
                $req .= 'LEFT JOIN ' . BAB_TOPICS_TBL . ' tp ON tp.id = id_topic ' . 'LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';

                $sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
            }

            $req .= $topview . $sDelegation;
        }

        if ($req != '') {
            if ($this->nbdays !== false && bab_isUserLogged()) {
                require_once dirname(__FILE__) . '/userinfosincl.php';
                $usersettings = bab_userInfos::getUserSettings();

                $this->nbdays = (int) $this->nbdays;
                $req .= " and date >= DATE_ADD(\"" . $babDB->db_escape_string($usersettings['lastlog']) . "\", INTERVAL -" . $babDB->db_escape_string($this->nbdays) . " DAY)";
            }
            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '')
                $order = "desc";

            switch (mb_strtoupper($order)) {
                case "ASC":
                    $order = "date ASC";
                    break;
                case "RAND":
                    $order = "rand()";
                    break;
                case "DESC":
                default:
                    $order = "date DESC";
                    break;
            }

            $req .= " order by " . $order;

            if ($this->last !== false)
                $req .= " limit 0, " . $babDB->db_escape_string($this->last);
            $this->rescomments = $babDB->db_query($req);
            $this->count = $babDB->db_num_rows($this->rescomments);
        } else
            $this->count = 0;
        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->rescomments);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CommentTitle', $arr['subject']);
            $this->ctx->curctx->push('CommentText', $arr['message']);
            $this->ctx->curctx->push('CommentId', $arr['id']);
            $this->ctx->curctx->push('CommentTopicId', $arr['id_topic']);
            $this->ctx->curctx->push('CommentArticleId', $arr['id_article']);
            $this->ctx->curctx->push('CommentDate', bab_mktime($arr['date']));
            if ($arr['id_author']) {
                $this->ctx->curctx->push('CommentAuthor', bab_getUserName($arr['id_author']));
            } else {
                $this->ctx->curctx->push('CommentAuthor', $arr['name']);
            }

            $this->ctx->curctx->push('CommentLanguage', $arr['lang']);
            $this->ctx->curctx->push('CommentUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=comments&idx=read&topics=" . $arr['id_topic'] . "&article=" . $arr['id_article'] . "&com=" . $arr['id']);
            $this->ctx->curctx->push('CommentPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=comments&idx=viewc&com=" . $arr['id'] . "&article=" . $arr['id_article'] . "&topics=" . $arr['id_topic']);
            $this->ctx->curctx->push('CommentArticleRating', $arr['article_rating']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_WaitingArticles extends Func_Ovml_Container
{
    public $IdEntries = array();
    public $res;
    public $index;
    public $count;
    public $topicid;

    public $imageheightmax;
    public $imagewidthmax;

    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);

        $this->imageheightmax = (int) $ctx->curctx->getAttribute('imageheightmax');
        $this->imagewidthmax = (int) $ctx->curctx->getAttribute('imagewidthmax');

        $userid = $ctx->curctx->getAttribute('userid');
        if ($userid === false || $userid === '') {
            $userid = $GLOBALS['BAB_SESS_USERID'];
        }

        if ($userid != '') {
            $this->topicid = $ctx->curctx->getAttribute('topicid');
            $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

            $req = 'select ' . 'adt.id, ' . 'adt.id_topic ' . 'FROM ' . BAB_ART_DRAFTS_TBL . ' adt ';

            $sDelegation = ' ';
            if (0 != $delegationid) {
                $req .= 'LEFT JOIN ' . BAB_TOPICS_TBL . ' tp ON tp.id = id_topic ' . 'LEFT JOIN ' . BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';

                $sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
            }

            $req .= "where adt.result='" . BAB_ART_STATUS_WAIT . "'" . $sDelegation;

            if ($this->topicid !== false && $this->topicid !== '') {
                $req .= " and adt.id_topic IN (" . $babDB->quote(explode(',', $this->topicid)) . ")";
            }

            $res = $babDB->db_query($req);
            while ($arr = $babDB->db_fetch_array($res)) {
                $waitart = bab_getWaitingArticles($arr['id_topic']);
                if (count($waitart) > 0 && in_array($arr['id'], $waitart)) {
                    $this->IdEntries[] = $arr['id'];
                }
            }

            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $this->res = $babDB->db_query("select adt.*, count(adft.id) as nfiles from " . BAB_ART_DRAFTS_TBL . " adt left join " . BAB_ART_DRAFTS_FILES_TBL . " adft on adt.id=adft.id_draft where adt.id IN (" . $babDB->quote($this->IdEntries) . ") group by adt.id order by adt.date_submission desc");
                $this->count = $babDB->db_num_rows($this->res);
            }
        } else {
            $this->count = 0;
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);

            setArticleAssociatedImageInfo($this->ctx, $this->imageheightmax, $this->imagewidthmax, $arr['id']);

            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('ArticleTitle', $arr['title']);
            $this->pushEditor('ArticleHead', $arr['head'], $arr['head_format'], 'bab_article_head');
            $this->pushEditor('ArticleBody', $arr['body'], $arr['body_format'], 'bab_article_body');
            if (empty($arr['body'])) {
                $this->ctx->curctx->push('ArticleReadMore', 0);
            } else {
                $this->ctx->curctx->push('ArticleReadMore', 1);
            }
            $this->ctx->curctx->push('ArticleId', $arr['id']);
            $this->ctx->curctx->push('ArticleAuthor', $arr['id_author']);
            $this->ctx->curctx->push('ArticleDate', bab_mktime($arr['date_submission']));
            $this->ctx->curctx->push('ArticleTopicId', $arr['id_topic']);
            $topic = bab_getTopicArray($arr['id_topic']);
            $this->ctx->curctx->push('ArticleCategoryId', $topic['id_cat']);
            $this->ctx->curctx->push('ArticleLanguage', $arr['lang']);
            $this->ctx->curctx->push('ArticleFiles', $arr['nfiles']);
            $this->ctx->curctx->push('ArticleUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=approb");
            $this->ctx->curctx->push('ArticlePopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=approb&idx=viewart&idart=" . $arr['id'] . "&topics=" . $arr['id_topic']);
            if (bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic'])) {
                $this->ctx->curctx->push('ArticleEditUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=articles&idx=Modify&topics=" . $arr['id_topic'] . '&article=' . $arr['id']);
                $this->ctx->curctx->push('ArticleEditName', bab_translate("Modify"));
            } else {
                $this->ctx->curctx->push('ArticleEditUrl', '');
                $this->ctx->curctx->push('ArticleEditName', '');
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


class Func_Ovml_Container_WaitingComments extends Func_Ovml_Container
{
    public $res;
    public $IdEntries = array();
    public $index;
    public $count;
    public $articleid;


    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);

        $userid = $ctx->curctx->getAttribute('userid');
        if ($userid === false || $userid === '' ) {
            $userid = $GLOBALS['BAB_SESS_USERID'];
        }

        if ($userid != '') {
            $this->articleid = $ctx->curctx->getAttribute('articleid');
            $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

            $req = "select c.id, c.id_topic from ".BAB_COMMENTS_TBL." c ";

            $sDelegation = ' ';
            if (0 != $delegationid) {
                $req .=
                    'LEFT JOIN ' .
                        BAB_TOPICS_TBL . ' tp ON tp.id = id_topic ' .
                    'LEFT JOIN ' .
                        BAB_TOPICS_CATEGORIES_TBL . ' tpCat ON tpCat.id = tp.id_cat ';

                $sDelegation = ' AND tpCat.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
            }

            $req .= "where c.confirmed='N'" . $sDelegation;


            if ($this->articleid !== false && $this->articleid !== '' ) {
                $req .= " and c.id_article IN (".$babDB->quote(explode(',', $this->articleid)).")";
            }

            $res = $babDB->db_query($req);
            while ($arr = $babDB->db_fetch_array($res)) {
                $waitcom = bab_getWaitingComments($arr['id_topic']);
                if (count($waitcom) > 0 && in_array( $arr['id'], $waitcom)) {
                    $this->IdEntries[] = $arr['id'];
                }
            }
            $this->count = count($this->IdEntries);
            if ($this->count > 0) {
                $this->res = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id IN (".$babDB->quote($this->IdEntries).") order by date desc");
                $this->count = $babDB->db_num_rows($this->res);
            }
        } else {
            $this->count = 0;
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }

    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('CommentTitle', $arr['subject']);
            $this->ctx->curctx->push('CommentText', $arr['message']);
            $this->ctx->curctx->push('CommentId', $arr['id']);
            $this->ctx->curctx->push('CommentTopicId', $arr['id_topic']);
            $this->ctx->curctx->push('CommentArticleId', $arr['id_article']);
            $this->ctx->curctx->push('CommentDate', bab_mktime($arr['date']));
            if ($arr['id_author']) {
                $this->ctx->curctx->push('CommentAuthor', bab_getUserName($arr['id_author']));
            } else {
                $this->ctx->curctx->push('CommentAuthor', $arr['name']);
            }
            $this->ctx->curctx->push('CommentLanguage', $arr['lang']);
            $this->ctx->curctx->push('CommentUrl', $GLOBALS['babUrl'].bab_getSelf()."?tg=approb");
            $this->ctx->curctx->push('CommentPopupUrl', $GLOBALS['babUrl'].bab_getSelf()."?tg=approb&idx=viewcom&idcom=".$arr['id']."&idart=".$arr['id_article']."&topics=".$arr['id_topic']);
            $this->ctx->curctx->push('CommentArticleRating', $arr['article_rating']);
            $this->idx++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}




/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdArticle
 * @return void
 */
function setArticleAssociatedImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $iIdArticle)
{
    require_once dirname(__FILE__) . '/gdiincl.php';
    require_once dirname(__FILE__) . '/artapi.php';
    require_once dirname(__FILE__) . '/pathUtil.class.php';
    require_once dirname(__FILE__) . '/settings.class.php';


    $settings = bab_getInstance('bab_Settings');
    /*@var $settings bab_Settings */

    $bProcessed		= false;
    $sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($settings->getUploadPath()));

    if (is_dir($sUploadPath)) {
        $aImgInfo = bab_getImageArticle($iIdArticle);
        if (is_array($aImgInfo)) {
            $iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
            $iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
            $sName				= $aImgInfo['name'];
            $sRelativePath		= $aImgInfo['relativePath'];
            $sFullPathName		= $sUploadPath . $sRelativePath . $sName;
            $sImageUrl			= $GLOBALS['babUrl'].bab_getSelf() . '?tg=articles&idx=getImage&sImage=' . urlencode($sName);
            $sOriginalImageUrl	= $sImageUrl . '&iIdArticle=' . $iIdArticle;

            $T = bab_functionality::get('Thumbnailer');
            $thumbnailUrl = null;

            if ($T) {
                // The thumbnailer functionality is available.
                 $T->setSourceFile($sFullPathName);
                $thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
            }
            if ($thumbnailUrl) {
                // The thumbnailer functionality was able to create a thumbnail.
                $oCtx->curctx->push('AssociatedImage', 1);
                $oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
                $oCtx->curctx->push('ImageUrl', $thumbnailUrl);
                $oCtx->curctx->push('ImageWidth', $iWidth);
                $oCtx->curctx->push('ImageHeight', $iHeight);

                // We reload the thumbnail image to get the real resized width and height.
                $thumbnailPath = $T->getThumbnailPath($iWidth, $iHeight);
                $imageSize = getImageSize($thumbnailPath->toString());
                if ($imageSize !== false) {
                    $oCtx->curctx->push('ResizedImageWidth', $imageSize[0]);
                    $oCtx->curctx->push('ResizedImageHeight', $imageSize[1]);
                }

                $bProcessed = true;
            } else {
                // If the thumbnailer was not available or not able to create a thumbnail,
                // we fall back to the old method for creating thumbnails (url of the page
                // dynamically resizing the image).
                $oImageResize = new bab_ImageResize();
                $iHeight = $iMaxImageHeight;
                $iWidth = $iMaxImageWidth;
                if (false !== $oImageResize->computeImageResizeWidthAndHeight($sFullPathName, $iWidth, $iHeight)) {
                    $sImageUrl .= '&iIdArticle=' . $iIdArticle;
                    $sImageUrl .= '&iWidth=' . $iWidth;
                    $sImageUrl .= '&iHeight=' . $iHeight;

                    $oCtx->curctx->push('AssociatedImage', 1);
                    $oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
                    $oCtx->curctx->push('ImageUrl', $sImageUrl);
                    $oCtx->curctx->push('ImageWidth', $oImageResize->getRealWidth());
                    $oCtx->curctx->push('ImageHeight', $oImageResize->getRealHeight());
                    $oCtx->curctx->push('ResizedImageWidth', $iWidth);
                    $oCtx->curctx->push('ResizedImageHeight', $iHeight);

                    $bProcessed = true;
                }
            }
        }
    }

    if (false === $bProcessed) {
        $oCtx->curctx->push('AssociatedImage', 0);
        $oCtx->curctx->push('OriginalImageUrl', '');
        $oCtx->curctx->push('ImageUrl', '');
        $oCtx->curctx->push('ImageWidth', 0);
        $oCtx->curctx->push('ImageHeight', 0);
        $oCtx->curctx->push('ResizedImageWidth', 0);
        $oCtx->curctx->push('ResizedImageHeight', 0);
    }
}




/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdCategory
 * @return void
 */
function setCategoryAssociatedImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $iIdCategory)
{
    require_once dirname(__FILE__) . '/gdiincl.php';
    require_once dirname(__FILE__) . '/artapi.php';
    require_once dirname(__FILE__) . '/pathUtil.class.php';

    $bProcessed		= false;
    $sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));

    if (is_dir($sUploadPath)) {
        $aImgInfo = bab_getImageCategory($iIdCategory);
        if (is_array($aImgInfo)) {
            $iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
            $iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
            $sName				= $aImgInfo['name'];
            $sRelativePath		= $aImgInfo['relativePath'];
            $sFullPathName		= $sUploadPath . $sRelativePath . $sName;
            $sImageUrl			= $GLOBALS['babUrl'].bab_getSelf() . '?tg=topusr&idx=getCategoryImage&sImage=' . bab_toHtml($sName);
            $sOriginalImageUrl	= $sImageUrl . '&iIdCategory=' . $iIdCategory;

            $T = bab_functionality::get('Thumbnailer');
            $thumbnailUrl = null;

            if ($T && $iWidth && $iHeight) {
                // The thumbnailer functionality is available.
                 $T->setSourceFile($sFullPathName);
                $thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
            }
            if ($thumbnailUrl) {
                // The thumbnailer functionality was able to create a thumbnail.
                $oCtx->curctx->push('AssociatedImage', 1);
                $oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
                $oCtx->curctx->push('ImageUrl', $thumbnailUrl);
                $oCtx->curctx->push('ImageWidth', $iWidth);
                $oCtx->curctx->push('ImageHeight', $iHeight);

                // We reload the thumbnail image to get the real resized width and height.
                $thumbnailPath = $T->getThumbnailPath($iWidth, $iHeight);
                $imageSize = getImageSize($thumbnailPath->toString());
                if ($imageSize !== false) {
                    $oCtx->curctx->push('ResizedImageWidth', $imageSize[0]);
                    $oCtx->curctx->push('ResizedImageHeight', $imageSize[1]);
                }

                $bProcessed = true;
            } else {
                // If the thumbnailer was not available or not able to create a thumbnail,
                // we fall back to the old method for creating thumbnails (url of the page
                // dynamically resizing the image).
                $oImageResize = new bab_ImageResize();
                $iHeight = $iMaxImageHeight;
                $iWidth = $iMaxImageWidth;
                if (false !== $oImageResize->computeImageResizeWidthAndHeight($sFullPathName, $iWidth, $iHeight)) {
                    $sImageUrl .= '&iIdCategory=' . $iIdCategory;
                    $sImageUrl .= '&iWidth=' . $iWidth;
                    $sImageUrl .= '&iHeight=' . $iHeight;

                    $oCtx->curctx->push('AssociatedImage', 1);
                    $oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
                    $oCtx->curctx->push('ImageUrl', $sImageUrl);
                    $oCtx->curctx->push('ImageWidth', $oImageResize->getRealWidth());
                    $oCtx->curctx->push('ImageHeight', $oImageResize->getRealHeight());
                    $oCtx->curctx->push('ResizedImageWidth', $iWidth);
                    $oCtx->curctx->push('ResizedImageHeight', $iHeight);

                    $bProcessed = true;
                }
            }
        }
    }

    if (false === $bProcessed) {
        $oCtx->curctx->push('AssociatedImage', 0);
        $oCtx->curctx->push('OriginalImageUrl', '');
        $oCtx->curctx->push('ImageUrl', '');
        $oCtx->curctx->push('ImageWidth', 0);
        $oCtx->curctx->push('ImageHeight', 0);
        $oCtx->curctx->push('ResizedImageWidth', 0);
        $oCtx->curctx->push('ResizedImageHeight', 0);
    }
}




/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdCategory
 * @param int           $iIdTopic
 * @return void
 */
function setTopicAssociatedImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $iIdCategory, $iIdTopic)
{
    require_once dirname(__FILE__) . '/gdiincl.php';
    require_once dirname(__FILE__) . '/artapi.php';
    require_once dirname(__FILE__) . '/pathUtil.class.php';
    require_once dirname(__FILE__) . '/settings.class.php';


    $settings = bab_getInstance('bab_Settings');
    /*@var $settings bab_Settings */

    $bProcessed		= false;
    $sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($settings->getUploadPath()));

    if(is_dir($sUploadPath)) {
        $aImgInfo = bab_getImageTopic($iIdTopic);
        if (is_array($aImgInfo)) {
            $iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
            $iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
            $sName				= $aImgInfo['name'];
            $sRelativePath		= $aImgInfo['relativePath'];
            $sFullPathName		= $sUploadPath . $sRelativePath . $sName;
            $sImageUrl			= $GLOBALS['babUrl'].bab_getSelf() . '?tg=topusr&idx=getTopicImage&sImage=' . bab_toHtml($sName);
            $sOriginalImageUrl	= $sImageUrl . '&iIdTopic=' . $iIdTopic . '&item=' . $iIdTopic  . '&iIdCategory=' . $iIdCategory;

            $T = bab_functionality::get('Thumbnailer');
            $thumbnailUrl = null;

            if ($T && $iWidth && $iHeight) {
                // The thumbnailer functionality is available.
                 $T->setSourceFile($sFullPathName);
                $thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
            }
            if ($thumbnailUrl) {
                // The thumbnailer functionality was able to create a thumbnail.
                $oCtx->curctx->push('AssociatedImage', 1);
                $oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
                $oCtx->curctx->push('ImageUrl', $thumbnailUrl);
                $oCtx->curctx->push('ImageWidth', $iWidth);
                $oCtx->curctx->push('ImageHeight', $iHeight);

                // We reload the thumbnail image to get the real resized width and height.
                $thumbnailPath = $T->getThumbnailPath($iWidth, $iHeight);
                $imageSize = getImageSize($thumbnailPath->toString());
                if ($imageSize !== false) {
                    $oCtx->curctx->push('ResizedImageWidth', $imageSize[0]);
                    $oCtx->curctx->push('ResizedImageHeight', $imageSize[1]);
                }

                $bProcessed = true;
            } else {
                // If the thumbnailer was not available or not able to create a thumbnail,
                // we fall back to the old method for creating thumbnails (url of the page
                // dynamically resizing the image).
                $oImageResize = new bab_ImageResize();
                $iHeight = $iMaxImageHeight;
                $iWidth = $iMaxImageWidth;
                if (false !== $oImageResize->computeImageResizeWidthAndHeight($sFullPathName, $iWidth, $iHeight)) {
                    $sImageUrl .= '&iIdTopic=' . $iIdTopic;
                    $sImageUrl .= '&item=' . $iIdTopic;
                    $sImageUrl .= '&iIdCategory=' . $iIdCategory;
                    $sImageUrl .= '&iWidth=' . $iWidth;
                    $sImageUrl .= '&iHeight=' . $iHeight;

                    $oCtx->curctx->push('AssociatedImage', 1);
                    $oCtx->curctx->push('OriginalImageUrl', $sOriginalImageUrl);
                    $oCtx->curctx->push('ImageUrl', $sImageUrl);
                    $oCtx->curctx->push('ImageWidth', $oImageResize->getRealWidth());
                    $oCtx->curctx->push('ImageHeight', $oImageResize->getRealHeight());
                    $oCtx->curctx->push('ResizedImageWidth', $iWidth);
                    $oCtx->curctx->push('ResizedImageHeight', $iHeight);

                    $bProcessed = true;
                }
            }
        }
    }

    if (false === $bProcessed) {
        $oCtx->curctx->push('AssociatedImage', 0);
        $oCtx->curctx->push('OriginalImageUrl', '');
        $oCtx->curctx->push('ImageUrl', '');
        $oCtx->curctx->push('ImageWidth', 0);
        $oCtx->curctx->push('ImageHeight', 0);
        $oCtx->curctx->push('ResizedImageWidth', 0);
        $oCtx->curctx->push('ResizedImageHeight', 0);
    }
}



/**
 * @param babOvTemplate $oCtx
 * @param int           $iMaxImageHeight
 * @param int           $iMaxImageWidth
 * @param int           $iIdCategory
 * @param int           $iIdTopic
 * @return void
 */
function setImageInfo($oCtx, $iMaxImageHeight, $iMaxImageWidth, $path)
{
    require_once dirname(__FILE__) . '/gdiincl.php';
    require_once dirname(__FILE__) . '/artapi.php';
    require_once dirname(__FILE__) . '/pathUtil.class.php';

    $bProcessed		= false;

    $iHeight			= $iMaxImageHeight ? $iMaxImageHeight : 2048;
    $iWidth				= $iMaxImageWidth ? $iMaxImageWidth : 2048;
    $sFullPathName		= $path;

    $T = bab_functionality::get('Thumbnailer');
    $thumbnailUrl = null;

    if ($T && $iWidth && $iHeight) {
        // The thumbnailer functionality is available.
        $T->setSourceFile($sFullPathName);
        $thumbnailUrl = $T->getThumbnail($iWidth, $iHeight);
    }
    if ($thumbnailUrl) {
        // The thumbnailer functionality was able to create a thumbnail.
        $oCtx->curctx->push('FileIsImage', 1);
        $oCtx->curctx->push('ImageUrl', $thumbnailUrl);
        $bProcessed = true;
    }
    if (false === $bProcessed) {
        $oCtx->curctx->push('FileIsImage', 0);
        $oCtx->curctx->push('ImageUrl', '');
    }
}













/**
 * Return the article tree in a html UL LI
 *
 * <OFArticleTree [category="id"] [topic="id"] [delegation="id"] [article="0|1"] [articlelimit="articleNumber"] [maxdepth="depth"] [hideempty="none|topic|category|all"] [date="publication|modification|all|none"] [hidefirstnode="0|1"]>
 *
 * - The category attribute is optional. It define where the tree will start.
 * 		The default value is the entire articles tree with rights.
 * - The topic attribute is optional. It define where the tree will start.
 * 		The default value is the entire articles tree with rights.
 * - The delegation attribute is optional.
 * 		The default value is '0'.
 * - The article attribute is optional, it define if article are display or not.
 * 		The default value is '1'.
 * - The filelimit attribute is optional, it will limit the number of file per folder which will be display. 0 = no limit.
 * 		The default value is '0'.
 * - The maxdepth attribute is optional, limits the number of levels of nested <ul>.
 * 		No maximum depth by default.
 * - The date attribute is optional, choose which date should be display with the article title.
 * 		The default value is 'none'.
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
 * <ul class="articletree-root">
 * a definir
 * </ul>
 */
class Func_Ovml_Function_ArticleTree extends Func_Ovml_Function {

    protected	$path = null;
    protected	$delegation = 0;
    protected	$article = 1;
    protected	$articlelimit = 0;
    protected	$hideempty = 'none';
    protected	$hidefirstnode = 0;

    protected	$selectedClass = 'selected';
    protected	$activeClass = 'active';
    protected	$date = 'none';


    protected	$maxDepth = 100;


    private function getChild($id, $depth = 1)
    {
        global $babDB;
        $return = '';

        $req = "SELECT bab_topics_categories.id as id, bab_topics_categories.title as title
                FROM ".BAB_TOPICS_CATEGORIES_TBL.", ".BAB_TOPCAT_ORDER_TBL."
                WHERE bab_topcat_order.type = 1
                AND bab_topics_categories.id_parent=".$babDB->quote($id)."
                AND bab_topcat_order.id_topcat=bab_topics_categories.id
                ORDER BY bab_topcat_order.ordering ASC";
        $res = $babDB->db_query($req);
        while( $arr = $babDB->db_fetch_assoc($res))
        {
            $child = $this->getChild($arr['id']);
            if(!($child == '' && ($this->hideempty == "all" || $this->hideempty == "category"))){
                $return[] = array(
                    'type' => 'category',
                    'url'=> htmlentities($GLOBALS['babUrl'].bab_getSelf().'?tg=topusr&cat='.$arr['id']),
                    'name' => $arr['title'],
                    'child' => $child,
                    'date' => ''
                );
            }
        }

        $sTopic = ' ';
        if($this->topic){
            $sTopic = ' AND id = \'' . $babDB->db_escape_string($this->topic) . '\' ';
        }
        $req = "SELECT bab_topics.id as id, bab_topics.category as category
                FROM ".BAB_TOPICS_TBL.", ".BAB_TOPCAT_ORDER_TBL."
                WHERE bab_topcat_order.type = 2
                AND id_cat=".$babDB->quote($id) . $sTopic . "
                AND bab_topcat_order.id_topcat=bab_topics.id
                ORDER BY bab_topcat_order.ordering ASC";
        $req = "select * from ".BAB_TOPICS_TBL." where id_cat=".$babDB->quote($id) . $sTopic;
        $res = $babDB->db_query($req);

        if (bab_isUserLogged())
        {
            require_once dirname(__FILE__).'/userinfosincl.php';
            $usersettings = bab_userInfos::getUserSettings();
            $lastlog = $usersettings['lastlog'];
        } else {
            $lastlog = '';
        }

        while( $arr = $babDB->db_fetch_assoc($res))
        {
            $returnTempTopic = '';
            if(bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id'])){
                $child = '';
                if($this->article){
                    $reqArticles = "select * from ".BAB_ARTICLES_TBL." where id_topic=".$babDB->quote($arr['id']) . 'ORDER BY date DESC';
                    if($this->articlelimit != 0){
                        $reqArticles.= " LIMIT 0,".$this->articlelimit;
                    }
                    $resArticles = $babDB->db_query($reqArticles);

                    $returnArticle = "";
                    while( $arrArticles = $babDB->db_fetch_array($resArticles))
                    {
                        $classNew = '';
                        if( $arrArticles['date'] > $lastlog)
                        {
                            $classNew = 'new';
                        }
                        $date = '';
                        if( $this->date == 'publication'){
                            $date = '('.bab_shortDate($arrArticles['date_publication']).')';
                        }elseif( $this->date == 'modification'){
                            $date = '('.bab_shortDate($arrArticles['date_modification']).')';
                        }elseif( $this->date == 'all'){
                            $date = '('.bab_shortDate($arrArticles['date_publication']) .' - '. bab_shortDate($arrArticles['date_modification']).')';
                        }
                        $child[] = array(
                            'type' => 'article '.$classNew,
                            'url'=> bab_toHtml(bab_siteMap::url($arrArticles['id'], $GLOBALS['babUrl'].bab_getSelf()."?tg=articles&idx=More&topics=".$arrArticles['id_topic']."&article=".$arrArticles['id'])),
                            'name' => $arrArticles['title'],
                            'child' => '',
                            'date' => $date
                        );
                    }
                }

                $returnTempTopic = array(
                    'type' => 'topic',
                    'url'=> htmlentities($GLOBALS['babUrl'].bab_getSelf().'?tg=articles&idx=Articles&topics='.$arr['id']),
                    'name' => $arr['category'],
                    'child' => $child,
                    'date' => ''
                );

                if($child != "" || ($this->hideempty != "all" && $this->hideempty != "topic")){
                    $return[] = $returnTempTopic;
                }
            }
        }

        return $return;
    }

    function generateUL($currentStage, $firstLevel = false)
    {
        $return = '';
        foreach($currentStage as $nextStage){
            if($firstLevel){
                $return.='<ul class="articletree">';
            }
            if(!($this->hidefirstnode && $firstLevel)){//Si on est au prmier niveau et qu'on veut cacher le premier niveau on ne rentre pas dans le IF
                $return.= '<li class="'.$nextStage['type'].'"><span class="unfold-fold"></span><a href="'.$nextStage['url'].'">'.$nextStage['name']."</a>".$nextStage['date'];
            }

            if($nextStage['child'] != ''){
                if(!($this->hidefirstnode && $firstLevel)){
                    $return.= '<ul>' . $this->generateUL($nextStage['child']) . '</ul>';
                }else{
                    $return.= $this->generateUL($nextStage['child']);
                }
            }

            if(!($this->hidefirstnode && $firstLevel)){
                $return.= '</li>';
            }
            if($firstLevel){
                $return.='</ul>';
            }
        }
        return $return;
    }


    /**
     * @return string
     */
    public function toString()
    {
        global $babDB;
        $args = $this->args;

        if (isset($args['delegation'])) {
            $this->delegation = $args['delegation'];
        }else{
            $this->delegation = 0;
        }

        if (isset($args['maxdepth'])) {
            $this->maxDepth = $args['maxdepth'];
        }else{
            $this->maxDepth = 100;
        }

        if (isset($args['article'])) {
            $this->article = $args['article'];
        }else{
            $this->article = 1;
        }

        if (isset($args['topic'])) {
            $this->topic = $args['topic'];
        }else{
            $this->topic = null;
        }

        if (isset($args['category'])) {
            $this->category = $args['category'];
        }else{
            $this->category = null;
        }

        if (isset($args['articlelimit'])) {
            $this->articlelimit = $args['articlelimit'];
        }else{
            $this->articlelimit = 0;
        }

        if (isset($args['date'])) {
            $this->date = $args['date'];
        }else{
            $this->date = 'none';
        }

        if (isset($args['hideempty'])) {
            $this->hideempty = $args['hideempty'];
        }else{
            $this->hideempty = 'none';
        }

        if (isset($args['hidefirstnode'])) {
            $this->hidefirstnode = $args['hidefirstnode'];
        }else{
            $this->hidefirstnode = 0;
        }

        $sDelegation = ' ';

        $sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($this->delegation) . '\' ';

        $sCategory = ' ';
        if($this->category){
            $sCategory = ' AND bab_topics_categories.id = \'' . $babDB->db_escape_string($this->category) . '\' ';
        }

        $req = "SELECT bab_topics_categories.id as id, bab_topics_categories.title as title
                FROM ".BAB_TOPICS_CATEGORIES_TBL.", ".BAB_TOPCAT_ORDER_TBL."
                WHERE bab_topcat_order.type = 1
                AND bab_topcat_order.id_topcat=bab_topics_categories.id" . $sDelegation . $sCategory."
                ORDER BY bab_topcat_order.ordering ASC";
        $res = $babDB->db_query($req);

        $core = array();
        while( $arr = $babDB->db_fetch_assoc($res))
        {
            $core[]= array(
                'type' => 'category',
                'url' => htmlentities($GLOBALS['babUrl'].bab_getSelf().'?tg=topusr&cat='.$arr['id']),
                'name' => $arr['title'],
                'child' => $this->getChild($arr['id']),
                'date' => '');
        }

        return $this->generateUL($core, true);
    }
}





class Func_Ovml_Function_PreviousOrNextArticle extends Func_Ovml_Function {

    protected $articleid = null;
    protected $topicid = null;
    protected $excludetopicid = null;
    protected $delegationid = null;
    protected $archive = null;
    protected $orderby = null;
    protected $order = null;
    protected $topicorder = false;
    protected $minrating = null;
    protected $articles = null;

    protected $saveas = null;


    /**
     * @return string
     */
    public function toString()
    {
        return '';
    }



    public function init()
    {

        global $babDB;
        $args = $this->args;

        if (isset($args['saveas'])) {
            $this->saveas = $args['saveas'];
        }

        if (isset($args['articleid'])) {
            $this->articleid = $args['articleid'];
        }

        if (isset($args['topicid'])) {
            $this->topicid = $args['topicid'];
        }

        if (isset($args['excludetopicid'])) {
            $this->excludetopicid = $args['excludetopicid'];
        }

        if (isset($args['delegationid'])) {
            $this->topicid = $args['delegationid'];
        }

        if (isset($args['orderby'])) {
            $this->orderby = $args['orderby'];
        }

        if (isset($args['order'])) {
            $this->order = $args['order'];
        } else {
            $this->order = 'asc';
        }

        if (isset($args['topicorder'])) {
            $this->topicorder = (mb_strtoupper($args['topicorder']) === 'YES');
        }

        if (isset($args['archive'])) {
            $this->archive = $args['archive'];
        } else {
            $this->archive = 'NO';
        }

        if (isset($args['minrating'])) {
            $this->minrating = $args['minrating'];
        }

        $sDelegation = ' ';
        $sLeftJoin = ' ';
        if (0 != $this->delegationid) {
            $sLeftJoin =
                'LEFT JOIN ' .
                    BAB_TOPICS_TBL . ' t ON t.id = at.id_topic ' .
                'LEFT JOIN ' .
                    BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = t.id_cat ';

            $sDelegation = ' AND tpc.id_dgowner = \'' . $babDB->db_escape_string($this->delegationid) . '\' ';
        }

        if ($this->topicid === null || $this->topicid === '' ) {
            $this->topicid = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
        } else {
            $this->topicid = array_intersect(array_keys(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)), explode(',', $this->topicid));
        }

        if (count($this->topicid) == 0) {
            return false;
        }

        switch(mb_strtoupper($this->archive)) {
            case 'YES':
                $this->archive = " AND archive='Y' ";
                break;
            case 'NO':
                 $this->archive = " AND archive='N' ";
                 break;
            default:
                $this->archive = " ";
                break;
        }

        if (!is_numeric($this->minrating)) {
             $this->minrating = 0;
             $ratingGroupBy = ' GROUP BY at.id ';
        } else {
             $ratingGroupBy = ' GROUP BY at.id HAVING average_rating >= ' . $babDB->quote($this->minrating) . ' ';
        }

        $req = '
            SELECT at.id, at.restriction, AVG(c.article_rating) AS average_rating, COUNT(c.article_rating) AS nb_ratings
            FROM ' . BAB_ARTICLES_TBL . ' AS at
            LEFT JOIN ' . BAB_COMMENTS_TBL . ' c ON c.id_article=at.id AND c.article_rating > 0
            ' . $sLeftJoin . '

            WHERE at.id_topic IN (' . $babDB->quote($this->topicid) . ')
            AND (at.date_publication=' . $babDB->quote('0000-00-00 00:00:00') . ' OR at.date_publication <= NOW())'
                . $this->archive
                . $sDelegation
                . $ratingGroupBy
            ;


        if ($this->orderby === null || $this->orderby === '') {
            $this->orderby = 'at.date';
        } else {
            switch (mb_strtolower($this->orderby )) {
                case 'title':
                    $this->orderby = 'at.title';
                    break;
                case 'rating':
                    $this->orderby = 'average_rating';
                    break;
                case 'creation':
                    $this->orderby = 'at.date';
                    break;
                case 'publication':
                    $this->orderby = 'at.date_publication';
                    break;
                case 'modification':
                default:
                    $this->orderby = 'at.date_modification';
                    break;
            }
        }


        switch (mb_strtoupper($this->order)) {
            case 'ASC':
                if ($this->topicorder) { /* topicorder=yes : order defined by managers */
                    $this->order = 'at.ordering ASC, at.date_modification desc';
                } else {
                    $this->order = $this->orderby.' ASC';
                }
                break;
                case 'RAND':
                $this->order = 'rand()';
                break;

            case 'DESC':
            default:
                if ($this->topicorder) { /* topicorder=yes : order defined by managers */
                    $this->order = 'at.ordering DESC, at.date_modification ASC';
                } else {
                    $this->order = $this->orderby.' DESC';
                }
                break;
        }

        $req .=  'ORDER BY ' . $this->order;
        $res = $babDB->db_query($req);
        while ($arr = $babDB->db_fetch_assoc($res)) {
            if ($arr['restriction'] == '' || bab_articleAccessByRestriction($arr['restriction'])) {
                $this->IdEntries[] = $arr['id'];
            }
        }

        $this->count = count($this->IdEntries);
        if ($this->count == 0) {
            return false;
        }

        $req = '
            SELECT at.id
            FROM ' . BAB_ARTICLES_TBL . ' AS at
            WHERE at.id IN ('.$babDB->quote($this->IdEntries).')
            ORDER BY ' . $this->order;

        $this->articles = $babDB->db_query($req);

        return true;
    }
}




class Func_Ovml_Function_NextArticle extends Func_Ovml_Function_PreviousOrNextArticle {

    /**
     * @return string
     */
    public function toString()
    {
        global $babDB;

        if (!parent::init()) {
            return '';
        }

        $nextArticleId = '';
        while ($arr = $babDB->db_fetch_assoc($this->articles)) {
            if ($arr['id'] == $this->articleid) {
                if ($arr = $babDB->db_fetch_assoc($this->articles)) {
                    $nextArticleId = $arr['id'];
                }
                break;
            }
        }

        if ($this->saveas) {
            $this->gctx->push($this->saveas, $nextArticleId);
            return;
        }
        return $nextArticleId;
    }
}



class Func_Ovml_Function_PreviousArticle extends Func_Ovml_Function_PreviousOrNextArticle {

    /**
     * @return string
     */
    public function toString()
    {
        global $babDB;

        if (!parent::init()) {
            return '';
        }

        $previousArticleId = '';
        while ($arr = $babDB->db_fetch_assoc($this->articles))
        {
            if ($arr['id'] == $this->articleid) {
                break;
            }
            $previousArticleId = $arr['id'];
        }


        if ($this->saveas) {
            $this->gctx->push($this->saveas, $previousArticleId);
            return;
        }
        return $previousArticleId;
    }
}
