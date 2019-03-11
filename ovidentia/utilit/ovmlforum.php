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


class Func_Ovml_Container_Forums extends Func_Ovml_Container
{
    public $index;

    public $count;

    public $IdEntries = array();

    public $res;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);
        $forumid = $ctx->curctx->getAttribute('forumid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        if (0 === $delegationid) {
            $delegationid = false;
        }

        if ($forumid === '' || $forumid === false) {
            $forumid = false;
        } else {
            $forumid = explode(',', $forumid);
        }

        include_once dirname(__FILE__) . '/forumincl.php';
        $this->res = bab_getForumsRes($forumid, $delegationid);
        $this->count = $babDB->db_num_rows($this->res);

        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;

        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('ForumName', $arr['name']);
            $this->ctx->curctx->push('ForumDescription', $arr['description']);
            $this->ctx->curctx->push('ForumId', $arr['id']);
            $this->ctx->curctx->push('ForumUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=threads&forum=" . $arr['id']);
            if (bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $arr['id'])) {
                $this->ctx->curctx->push('ForumNewThreadUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=threads&idx=newthread&forum=" . $arr['id']);
            } else {
                $this->ctx->curctx->push('ForumNewThreadUrl', '');
            }
            $this->ctx->curctx->push('ForumDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_Forum extends Func_Ovml_Container
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
        /* Valid access rights */
        if (bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $ctx->curctx->getAttribute('forumid'))) {
            $this->res = $babDB->db_query("select * from " . BAB_FORUMS_TBL . " where id='" . $babDB->db_escape_string($ctx->curctx->getAttribute('forumid')) . "' and active='Y'");
            if ($this->res && $babDB->db_num_rows($this->res) == 1) {
                $this->count = 1;
            } else {
                $this->count = 0;
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
            $this->ctx->curctx->push('ForumName', $arr['name']);
            $this->ctx->curctx->push('ForumDescription', $arr['description']);
            $this->ctx->curctx->push('ForumId', $arr['id']);
            $this->ctx->curctx->push('ForumUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=threads&forum=" . $arr['id']);
            $this->ctx->curctx->push('ForumDelegationId', $arr['id_dgowner']);
            if (bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $arr['id'])) {
                $this->ctx->curctx->push('ForumNewThreadUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=threads&idx=newthread&forum=" . $arr['id']);
            } else {
                $this->ctx->curctx->push('ForumNewThreadUrl', '');
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



class Func_Ovml_Container_ForumPrevious extends Func_Ovml_Container_Forum
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_Forum::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Forums');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index > 1) {
                $ctx->curctx->push('IndexEntry', $this->handler->index - 2);
                $ctx->curctx->push('forumid', $this->handler->IdEntries[$this->handler->index - 2]);
            }
        }
        $this->bab_Forum($ctx);
    }
}



class Func_Ovml_Container_ForumNext extends Func_Ovml_Container_Forum
{
    public $handler;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container_Forum::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        $this->handler = $ctx->get_handler('Func_Ovml_Container_Forums');
        if ($this->handler !== false && $this->handler !== '') {
            if ($this->handler->index < $this->handler->count) {
                $this->count = 1;
                $ctx->curctx->push('IndexEntry', $this->handler->index);
                $ctx->curctx->push('forumid', $this->handler->IdEntries[$this->handler->index]);
            }
        }
        $this->bab_Forum($ctx);
    }
}



class Func_Ovml_Container_Post extends Func_Ovml_Container
{
    public $res;

    public $arrid = array();

    public $arrfid = array();

    public $resposts;

    public $count;

    public $postid;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        include_once $GLOBALS['babInstallPath'] . 'utilit/forumincl.php';
        parent::setOvmlContext($ctx);
        $this->postid = $ctx->curctx->getAttribute('postid');
        if ($this->postid === false || $this->postid === '') {
            $arr = array();
        } else {
            $arr = explode(',', $this->postid);
        }

        $this->confirmed = $ctx->curctx->getAttribute('confirmed');
        if ($this->confirmed === false)
            $this->confirmed = "yes";

        switch (mb_strtoupper($this->confirmed)) {
            case "YES":
                $this->confirmed = 'Y';
                break;
            case "NO":
                $this->confirmed = 'N';
                break;
            default:
                $this->confirmed = '';
                break;
        }

        if (count($arr) > 0) {
            $req = "SELECT p.id, p.id_thread, f.id id_forum FROM " . BAB_POSTS_TBL . " p LEFT JOIN " . BAB_THREADS_TBL . " t on p.id_thread = t.id LEFT JOIN " . BAB_FORUMS_TBL . " f on f.id = t.forum WHERE f.active='Y' and p.id IN (" . $babDB->quote($arr) . ")";
            if ($this->confirmed) {
                $req .= " AND p.confirmed =  '" . $this->confirmed . "'";
            }
            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '') {
                $order = "asc";
            }

            switch (mb_strtoupper($order)) {
                case "ASC":
                    $order = "p.date ASC";
                    break;
                case "RAND":
                    $order = "rand()";
                    break;
                case "DESC":
                default:
                    $order = "p.date DESC";
                    break;
            }

            $req .= " order by " . $order;


            $res = $babDB->db_query($req);

            while ($row = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id_forum'])) {
                    array_push($this->arrid, $row['id']);
                    array_push($this->arrfid, $row['id_forum']);
                }
            }
        }
        $this->count = count($this->arrid);
        if ($this->count > 0) {
            $this->res = $babDB->db_query("select p.*, f.bupdatemoderator, f.bupdateauthor, t.active from " . BAB_POSTS_TBL . " p left join " . BAB_THREADS_TBL . " t on t.id=p.id_thread left join " . BAB_FORUMS_TBL . " f on f.id=t.forum where p.id IN (" . $babDB->quote($this->arrid) . ") order by " . $order);
            $this->count = $babDB->db_num_rows($this->res);
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB, $BAB_SESS_USERID;
        if ($this->idx < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('PostTitle', $arr['subject']);
            $this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
            $this->ctx->curctx->push('PostId', $arr['id']);
            $this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
            $this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
            $author = bab_getForumContributor($this->arrfid[$this->idx], $arr['id_author'], $arr['author']);
            $this->ctx->curctx->push('PostAuthor', $author);
            $this->ctx->curctx->push('PostAuthorId', $arr['id_author']);
            $this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
            $this->ctx->curctx->push('PostUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=List&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id'] . '&views=1');
            $this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=viewp&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id'] . '&views=1');
            if (bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $this->arrfid[$this->idx])) {
                $this->ctx->curctx->push('PostReplyUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=reply&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id'] . '&views=1');
            } else {
                $this->ctx->curctx->push('PostReplyUrl', '');
            }
            if ((bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->arrfid[$this->idx]) && $arr['bupdatemoderator'] == 'Y') || ($arr["active"] == 'Y' && $BAB_SESS_USERID && $arr['bupdateauthor'] == 'Y' && $BAB_SESS_USERID == $arr['id_author'])) {
                $this->ctx->curctx->push('PostModifyUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=Modify&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
            } else {
                $this->ctx->curctx->push('PostModifyUrl', '');
            }
            if (bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->arrfid[$this->idx]) && $arr['confirmed'] == 'N') {
                $this->ctx->curctx->push('PostConfirmUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=Modify&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
                $this->ctx->curctx->push('PostDeleteUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=Delete&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
            } else {
                $this->ctx->curctx->push('PostConfirmUrl', '');
                $this->ctx->curctx->push('PostDeleteUrl', '');
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


class Func_Ovml_Container_PostFiles extends Func_Ovml_Container
{
    public $IdEntries = array();

    public $res;

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
        $postid = $ctx->curctx->getAttribute('postid');
        if ($postid === false || $postid === '')
            $this->count = 0;
        else {
            $baseurl = $GLOBALS['babUploadPath'] . '/forums/';
            if (is_dir($baseurl) && $h = opendir($baseurl)) {
                $req = "SELECT t.forum FROM " . BAB_THREADS_TBL . " t," . BAB_POSTS_TBL . " p WHERE t.id = p.id_thread AND p.id='" . $babDB->db_escape_string($postid) . "'";
                list ($forum) = $babDB->db_fetch_array($babDB->db_query($req));

                $this->arr = array();
                while (false !== ($file = readdir($h))) {
                    $iOffset = mb_strpos($file, ',');
                    if (false !== $iOffset && mb_substr($file, 0, $iOffset) == $postid) {
                        $name = mb_substr($file, $iOffset + 1);
                        $this->arr[] = array(
                            'url' => $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=dlfile&forum=" . $forum . "&post=" . $postid . "&file=" . urlencode($name),
                            'name' => $name
                        );
                    }
                }
                $this->count = count($this->arr);
            }
        }

        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            $this->ctx->curctx->push('PostFileName', $this->arr[$this->idx]['name']);
            $this->ctx->curctx->push('PostFileUrlGet', $this->arr[$this->idx]['url']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}


class Func_Ovml_Container_Thread extends Func_Ovml_Container
{
    public $arrid = array();

    public $res;

    public $resposts;

    public $count;

    public $postid;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        include_once $GLOBALS['babInstallPath'] . 'utilit/forumincl.php';
        parent::setOvmlContext($ctx);
        $this->threadid = $ctx->curctx->getAttribute('threadid');
        if ($this->threadid === false || $this->threadid === '') {
            $arr = array();
        } else {
            $arr = explode(',', $this->threadid);
        }

        if (count($arr) > 0) {
            $req = "select tt.id, tt.forum from " . BAB_THREADS_TBL . " tt left join " . BAB_FORUMS_TBL . " ft on ft.id=tt.forum WHERE ft.active='Y' and tt.id IN (" . $babDB->quote($arr) . ") and tt.active='Y'";

            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '')
                $order = "asc";

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

            $res = $babDB->db_query($req);

            while ($row = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['forum'])) {
                    array_push($this->arrid, $row['id']);
                }
            }
        }

        $this->count = count($this->arrid);
        if ($this->count > 0) {
            $this->res = $babDB->db_query("select * from " . BAB_THREADS_TBL . " where id IN (" . $babDB->quote($this->arrid) . ") order by " . $order);
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
            $this->ctx->curctx->push('ThreadForumId', $arr['forum']);
            $this->ctx->curctx->push('ThreadId', $arr['id']);
            $this->ctx->curctx->push('ThreadPostId', $arr['post']);
            $this->ctx->curctx->push('ThreadLastPostId', $arr['lastpost']);
            $this->ctx->curctx->push('ThreadDate', bab_mktime($arr['date']));
            $starter = bab_getForumContributor($arr['forum'], $arr['starter'], bab_getUserName($arr['starter']));
            $this->ctx->curctx->push('ThreadStarter', $starter);
            $this->ctx->curctx->push('ThreadStarterId', $arr['starter']);
            $this->ctx->curctx->push('ThreadUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=List&forum=" . $arr['forum'] . "&thread=" . $arr['id'] . "&views=1");
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}

class Func_Ovml_Container_RecentPosts extends Func_Ovml_Container
{
    public $res;

    public $arrid = array();

    public $arrfid = array();

    public $resposts;

    public $count;

    public $lastlog;

    public $nbdays;

    public $last;

    public $forumid;

    public $threadid;


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
        $this->forumid = $ctx->curctx->getAttribute('forumid');
        $this->threadid = $ctx->curctx->getAttribute('threadid');
        $access = array_keys(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL));
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        if ($this->forumid === false || $this->forumid === '') {
            $arr = $access;
        } else {
            $arr = explode(',', $this->forumid);
            $arr = array_intersect($arr, $access);
        }

        if (count($arr) > 0) {
            $sDelegation = ' ';
            if (0 != $delegationid) {
                $sDelegation = ' AND f.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
            }


            $req = "SELECT p.*, f.id id_forum, f.id_dgowner FROM " . BAB_POSTS_TBL . " p LEFT JOIN " . BAB_THREADS_TBL . " t on p.id_thread = t.id LEFT JOIN " . BAB_FORUMS_TBL . " f on f.id = t.forum WHERE f.active='Y'" . $sDelegation . "and t.forum IN (" . $babDB->quote($arr) . ") and p.confirmed='Y'";
            if ($this->threadid !== false && is_numeric($this->threadid)) {
                $req .= " and p.id_thread = '" . $this->threadid . "'";
            }

            if ($this->nbdays !== false && bab_isUserLogged()) {
                require_once dirname(__FILE__) . '/userinfosincl.php';
                $usersettings = bab_userInfos::getUserSettings();

                $req .= " and p.date >= DATE_ADD(\"" . $babDB->db_escape_string($usersettings['lastlog']) . "\", INTERVAL -" . $babDB->db_escape_string($this->nbdays) . " DAY)";
            }


            $order = $ctx->curctx->getAttribute('order');
            if ($order === false || $order === '')
                $order = "desc";

            switch (mb_strtoupper($order)) {
                case "ASC":
                    $order = "p.date ASC";
                    break;
                case "RAND":
                    $order = "rand()";
                    break;
                case "DESC":
                default:
                    $order = "p.date DESC";
                    break;
            }

            $req .= " order by " . $order;

            if ($this->last !== false)
                $req .= " limit 0, " . $babDB->db_escape_string($this->last);

            $this->res = $babDB->db_query($req);
            $this->count = $babDB->db_num_rows($this->res);
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
            $this->ctx->curctx->push('PostTitle', $arr['subject']);
            $this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
            $this->ctx->curctx->push('PostId', $arr['id']);
            $this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
            $this->ctx->curctx->push('PostForumId', $arr['id_forum']);
            $this->ctx->curctx->push('PostAuthor', $arr['author']);
            $this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
            $this->ctx->curctx->push('PostUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=List&forum=" . $arr['id_forum'] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id'] . '&views=1');
            $this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=viewp&forum=" . $arr['id_forum'] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id'] . '&views=1');
            $this->ctx->curctx->push('PostDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}



class Func_Ovml_Container_RecentThreads extends Func_Ovml_Container
{
    public $res;

    public $arrid = array();

    public $arrfid = array();

    public $resposts;

    public $count;

    public $lastlog;

    public $nbdays;

    public $last;

    public $forumid;


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
        $this->forumid = $ctx->curctx->getAttribute('forumid');
        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        $sDelegation = ' ';
        if (0 != $delegationid) {
            $sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
        }

        if ($this->forumid === false || $this->forumid === '') {
            $arr = array();
        } else {
            $arr = explode(',', $this->forumid);
        }

        $req = "
            SELECT p.id, p.id_thread, f.id id_forum
            FROM
                " . BAB_POSTS_TBL . " p
                LEFT JOIN " . BAB_THREADS_TBL . " t on p.id_thread = t.id
                LEFT JOIN " . BAB_FORUMS_TBL . " f on f.id = t.forum
                LEFT JOIN " . BAB_POSTS_TBL . " lp ON lp.id = t.lastpost

            WHERE
                f.active='Y'" . $sDelegation . "
                and p.confirmed='Y'
                and p.id_parent='0'
        ";

        if (count($arr) > 0) {
            $req .= " and t.forum IN (" . $babDB->quote($arr) . ")";
        }

        if ($this->nbdays !== false && bab_isUserLogged()) {

            require_once dirname(__FILE__) . '/userinfosincl.php';
            $usersettings = bab_userInfos::getUserSettings();

            $req .= " and p.date >= DATE_ADD(\"" . $babDB->db_escape_string($usersettings['lastlog']) . "\", INTERVAL -" . $babDB->db_escape_string($this->nbdays) . " DAY)";
        }

        $order = $ctx->curctx->getAttribute('order');

        if ($order === false || $order === '') {
            $order = "desc";
        }

        switch (mb_strtoupper($order)) {
            case "POST":
                $order = "lp.date DESC";
                break;
            case "ASC":
                $order = "p.date ASC";
                break;
            case "RAND":
                $order = "rand()";
                break;
            case "DESC":
            default:
                $order = "p.date DESC";
                break;
        }

        $res = $babDB->db_query($req);

        while ($row = $babDB->db_fetch_array($res)) {
            if (bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id_forum'])) {
                array_push($this->arrid, $row['id']);
                array_push($this->arrfid, $row['id_forum']);
            }
        }
        $this->count = count($this->arrid);
        if ($this->count > 0) {

            $req = "
                select p.*, f.id_dgowner
                from
                    " . BAB_POSTS_TBL . " p
                    LEFT JOIN " . BAB_THREADS_TBL . " t on p.id_thread = t.id
                    LEFT JOIN " . BAB_FORUMS_TBL . " f on f.id = t.forum
                    LEFT JOIN " . BAB_POSTS_TBL . " lp ON lp.id = t.lastpost
                where
                    p.id IN (" . $babDB->quote($this->arrid) . ") order by " . $order;

            if ($this->last !== false)
                $req .= " limit 0, " . $this->last;

            $this->res = $babDB->db_query($req);

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
            $this->ctx->curctx->push('PostTitle', $arr['subject']);
            $this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
            $this->ctx->curctx->push('PostId', $arr['id']);
            $this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
            $this->ctx->curctx->push('PostForumId', $this->arrfid[$this->idx]);
            $this->ctx->curctx->push('PostAuthor', $arr['author']);
            $this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
            $this->ctx->curctx->push('PostUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=List&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
            $this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=viewp&forum=" . $this->arrfid[$this->idx] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
            $this->ctx->curctx->push('PostDelegationId', $arr['id_dgowner']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}

class Func_Ovml_Container_WaitingPosts extends Func_Ovml_Container
{
    public $res;

    public $index;

    public $count;

    public $topicid;


    /**
     * {@inheritDoc}
     * @see Func_Ovml_Container::setOvmlContext()
     */
    public function setOvmlContext(babOvTemplate $ctx)
    {
        global $babDB;
        parent::setOvmlContext($ctx);

        $userid = $ctx->curctx->getAttribute('userid');
        if ($userid === false || $userid === '') {
            $userid = 0;
        }

        $req = "select id from " . BAB_FORUMS_TBL . " where active='Y'";
        $this->forumid = $ctx->curctx->getAttribute('forumid');
        if ($this->forumid !== false && $this->forumid !== '') {
            $req .= " and id IN (" . $babDB->quote(explode(',', $this->forumid)) . ")";
        }

        $delegationid = (int) $ctx->curctx->getAttribute('delegationid');

        if (0 != $delegationid) {
            $req .= ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
        }

        $res = $babDB->db_query($req);
        $arrf = array();
        while ($arr = $babDB->db_fetch_array($res)) {
            if ($userid == 0 && bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $arr['id'])) {
                $arrf[] = $arr['id'];
            } elseif ($userid != 0 && bab_isAccessValidByUser(BAB_FORUMSMAN_GROUPS_TBL, $arr['id'], $userid)) {
                $arrf[] = $arr['id'];
            }
        }

        if (count($arrf) > 0) {
            $req = "SELECT p.*, t.forum  FROM  " . BAB_POSTS_TBL . " p, " . BAB_FORUMS_TBL . " f, " . BAB_THREADS_TBL . " t WHERE p.confirmed ='N' AND t.forum = f.id AND t.id = p.id_thread and f.id IN (" . $babDB->quote($arrf) . ")";

            $this->res = $babDB->db_query($req);
            $this->count = $babDB->db_num_rows($this->res);
        } else
            $this->count = 0;

        $this->ctx->curctx->push('CCount', $this->count);
    }


    public function getnext()
    {
        global $babDB;
        if ($this->idx < $this->count) {
            $this->ctx->curctx->push('CIndex', $this->idx);
            $arr = $babDB->db_fetch_array($this->res);
            $this->ctx->curctx->push('PostTitle', $arr['subject']);
            $this->pushEditor('PostText', $arr['message'], $arr['message_format'], 'bab_forum_post');
            $this->ctx->curctx->push('PostId', $arr['id']);
            $this->ctx->curctx->push('PostThreadId', $arr['id_thread']);
            $this->ctx->curctx->push('PostForumId', $arr['forum']);
            $this->ctx->curctx->push('PostAuthor', $arr['author']);
            $this->ctx->curctx->push('PostDate', bab_mktime($arr['date']));
            $this->ctx->curctx->push('PostUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=List&forum=" . $arr['forum'] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
            $this->ctx->curctx->push('PostPopupUrl', $GLOBALS['babUrl'] . bab_getSelf() . "?tg=posts&idx=viewp&forum=" . $arr['forum'] . "&thread=" . $arr['id_thread'] . "&post=" . $arr['id']);
            $this->idx ++;
            $this->index = $this->idx;
            return true;
        } else {
            $this->idx = 0;
            return false;
        }
    }
}
