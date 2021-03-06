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
include 'base.php';

include_once $GLOBALS['babInstallPath'] . 'utilit/dirincl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/ldap.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/tempfile.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/userinfosincl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/directoryimport.php';
include_once $GLOBALS['babInstallPath'] . 'admin/register.php';

function trimQuotes($str)
{
    if ($str[mb_strlen($str) - 1] == "\"" && $str[0] == "\"")
        return mb_substr(mb_substr($str, 1), 0, mb_strlen($str) - 2);
    else
        return $str;
}

function listUserAds()
{
    global $babBody;

    class listUserAdsTpl
    {

        public $db;

        public $res;

        public $count;

        public $directories;

        public $urlname;

        public $emptyname;

        public $emptyurl;

        public $name;

        public $description;

        public $desctxt;

        public $ldapid = array();

        public $dbid = array();

        public $altbg = true;

        public function __construct()
        {
            global $babDB;
            $this->directories = bab_translate("Directories");
            $this->desctxt = bab_translate("Description");
            $this->databasetitle = bab_translate("Databases Directories list");
            $this->ldaptitle = bab_translate("Ldap Directories list");
            $this->adminurlname = bab_translate("Management");
            $this->badd = false;
            $res = $babDB->db_query("select id from " . BAB_LDAP_DIRECTORIES_TBL . " ORDER BY name");
            while ($row = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id'])) {
                    array_push($this->ldapid, $row['id']);
                }
            }
            $this->countldap = count($this->ldapid);

            $this->dbid = array_keys(bab_getUserDirectories());
            $this->countdb = count($this->dbid);

            if ($this->countldap == 0 && $this->countdb == 0) {
                $GLOBALS['babBody']->msgerror = bab_translate("Access denied");
            }
        }

        public function getnextldap()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->countldap) {
                $this->altbg = ! $this->altbg;
                $arr = $babDB->db_fetch_array($babDB->db_query("select name, description from " . BAB_LDAP_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($this->ldapid[$i]) . "'"));
                $this->description = bab_toHtml($arr['description'], BAB_HTML_ALL ^ BAB_HTML_P);
                $this->url = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sldap&id=" . $this->ldapid[$i]);
                $this->urlname = bab_toHtml($arr['name']);
                $i ++;
                return true;
            }
            return false;
        }

        public function getnextdb()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->countdb) {
                $this->altbg = ! $this->altbg;
                $arr = $babDB->db_fetch_array($babDB->db_query("select name, description, id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($this->dbid[$i]) . "'"));
                $this->description = bab_toHtml($arr['description'], BAB_HTML_ALL ^ BAB_HTML_P);
                $this->adminurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $this->dbid[$i]);
                $this->url = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdbovml&directoryid=" . $this->dbid[$i]);
                $this->urlname = bab_toHtml($arr['name']);
                $this->badd = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $this->dbid[$i]);
                $this->baddmod = $this->badd || bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $this->dbid[$i]);
                if ($this->badd && $arr['id_group'] != 0)
                    $this->badd = false;
                $i ++;
                return true;
            }
            return false;
        }
    }

    $temp = new listUserAdsTpl();
    $babBody->babecho(bab_printTemplate($temp, "directory.html", "useradlist"));
}



function browseLdapDirectory($id, $pos)
{
    global $babBody;

    class browseLdapDirectoryTpl
    {

        public $count;

        public $allname;

        public $cntxt;

        public $bteltxt;

        public $hteltxt;

        public $emailtxt;

        public $addname;

        public $id;

        public $pos;

        public $allselected;

        public $allurl;

        public $ldap;

        public $entries;

        public $db;

        public $accid;

        public $cn;

        public $url;

        public $btel;

        public $htel;

        public $email;

        public $urlmail;

        public $selectname;

        public $selecturl;

        public $selected;

        public $badd;

        public $altbg = true;

        private $current_order = null;

        public function __construct($id, $pos)
        {
            global $babDB;
            $this->allname = bab_translate("All");
            $this->sntxt = bab_translate("Name");
            $this->givennametxt = bab_translate("Firstname");
            $this->bteltxt = bab_translate("Business Phone");
            $this->hteltxt = bab_translate("Home Phone");
            $this->emailtxt = bab_translate("Email");
            $this->addname = bab_translate("Add");
            $this->badd = false;
            $this->id = $id;
            $this->pos = $pos;
            if (empty($pos))
                $this->allselected = 1;
            else
                $this->allselected = 0;
            $this->allurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sldap&id=" . $id . "&pos=");
            $this->count = 0;
            $res = $babDB->db_query("select * , DECODE(password, \"" . $babDB->db_escape_string($GLOBALS['BAB_HASH_VAR']) . "\") as adpass from " . BAB_LDAP_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'");
            if ($res && $babDB->db_num_rows($res) > 0) {
                $arr = $babDB->db_fetch_array($res);
                $GLOBALS['babWebStat']->addLdapDirectory($id);
                $this->ldapdecodetype = $arr['decoding_type'];
                $this->ldap = new babLDAP($arr['host'], "", true);
                $this->ldap->connect();
                $this->ldap->bind($arr['userdn'], $arr['adpass']);
                $this->entries = $this->ldap->search($arr['basedn'], "(|(sn=" . ldap_escapefilter($pos) . "*))", array(
                    "sn",
                    "givenname",
                    "cn",
                    "telephonenumber",
                    "mail",
                    "homephone"
                ));
                if (is_array($this->entries)) {
                    $this->count = $this->entries['count'];
                    $this->order = array();
                    for ($i = 0; $i < $this->count; $i ++) {
                        $this->order[$i] = bab_ldapDecode($this->entries[$i]['sn'][0], $this->ldapdecodetype);
                    }

                    bab_sort::natcasesort($this->order);
                    $this->order = array_keys($this->order);
                }
            }

            /* find prefered mail account */
            $this->accid = 0;
        }

        public function getFromEntry($keyname)
        {
            if (! isset($this->entries[$this->current_order][$keyname][0])) {
                return '';
            }

            return bab_ldapDecode($this->entries[$this->current_order][$keyname][0], $this->ldapdecodetype);
        }

        public function getnext()
        {
            static $i = 0;
            if ($i < $this->count) {
                $this->current_order = $this->order[$i];
                $this->altbg = ! $this->altbg;
                $this->cn = "";
                $this->sn = bab_toHtml($this->getFromEntry('sn'));
                $this->givenname = bab_toHtml($this->getFromEntry('givenname'));
                $this->url = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=dldap&id=" . $this->id . "&cn=" . urlencode($this->getFromEntry('cn')) . "&pos=" . $this->pos);
                $this->btel = bab_toHtml($this->getFromEntry('telephonenumber'));
                $this->htel = bab_toHtml($this->getFromEntry('homephone'));
                $this->email = bab_toHtml($this->getFromEntry('mail'));
                $this->urlmail = null;
                $i ++;
                return true;
            }
            $this->ldap->close();
            return false;
        }

        public function getnextselect()
        {
            static $k = 0;
            static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            if ($k < 26) {
                $this->selectname = mb_substr($t, $k, 1);
                $this->selecturl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sldap&id=" . $this->id . "&pos=" . $this->selectname);
                if ($this->pos == $this->selectname) {
                    $this->selected = 1;
                } else {
                    $this->selected = 0;
                }
                $k ++;
                return true;
            }
            return false;
        }
    }

    $temp = new browseLdapDirectoryTpl($id, $pos);
    $babBody->babecho(bab_printTemplate($temp, "directory.html", "adldapbrowse"));
}



function browseDbDirectory($id, $pos, $xf, $badd, $disable_email = 'N')
{
    global $babBody;

    class browseDbDirectoryTpl
    {

        public $count;

        public $altbg = true;

        public $sContent;

        public function __construct($id, $pos, $xf, $badd, $disable_email)
        {
            global $babDB;
            global $babBody;

            $this->mass_mailing = ($babBody->babsite['mass_mailing'] == 'Y');
            $this->t_or_txt = mb_strtolower(bab_translate("Or"));
            $this->t_separator_comma = bab_translate("Comma separator");
            $this->t_separator_semicolon = bab_translate("Semicolon separator");
            $this->t_copy_email_addresses = bab_translate("Copy email addresses");
            $this->allname = bab_translate("All");
            $this->addname = bab_translate("Add");
            $this->assignname = bab_translate("Assign");
            $this->id = $id;
            $this->pos = $pos;
            $this->badd = $badd;
            $this->xf = $xf;
            $this->sContent = 'text/html; charset=' . bab_charset::getIso();
            $this->mail_txt = bab_translate("Send a mail");
            $this->view_txt = bab_translate("View the entry");
            $this->dirmember_txt = bab_translate("Directory member");
            $this->disable_email = ($disable_email == 'Y') ? true : false;

            if (mb_substr($pos, 0, 1) == "-") {
                $this->pos = mb_substr($pos, 1);
                $this->ord = "";
            } else {
                $this->pos = $pos;
                $this->ord = "-";
            }

            if (empty($pos)) {
                $this->allselected = 1;
            } else {
                $this->allselected = 0;
            }
            if ($_GET['idx'] == 'sdbovml') {
                $this->allurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdbovml&directoryid=" . $id . "&pos=" . urlencode(($this->ord == "-" ? "" : $this->ord)) . "&xf=" . urlencode($this->xf));
            } else {
                $this->allurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $id . "&pos=" . urlencode(($this->ord == "-" ? "" : $this->ord)) . "&xf=" . urlencode($this->xf));
            }
            $this->addurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=adbc&id=" . urlencode($id));
            $this->count = 0;
            $arr = $babDB->db_fetch_array($babDB->db_query("select description, id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
            if ($arr['description']) {
                $this->description = bab_toHtml($arr['description'], BAB_HTML_ALL ^ BAB_HTML_P);
            } else {
                $this->description = false;
            }
            $this->idgroup = $arr['id_group'];
            if (bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $id)) {
                $GLOBALS['babWebStat']->addDatabaseDirectory($id);
                $this->rescol = $babDB->db_query("select id, id_field from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . ($this->idgroup != 0 ? 0 : $babDB->db_escape_string($this->id)) . "' and ordering!='0' order by ordering asc");
                $this->countcol = $babDB->db_num_rows($this->rescol);
            } else {
                $GLOBALS['babBody']->msgerror = bab_translate("Access denied");
                $this->countcol = 0;
                $this->count = 0;
            }

            $this->bassign = false;
            if (bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id) && $arr['id_group'] && $arr['id_group'] != BAB_REGISTERED_GROUP) {
                $this->bassign = true;
                $this->assignurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=assign&id=" . urlencode($id));
            }
            $this->bgroup = $arr['id_group'] > 0;

            /* find prefered mail account */
            $this->accid = 0;

            $this->select = array();
        }

        public function getnextcol()
        {
            global $babDB;
            static $i = 0;
            static $tmp = array();
            static $leftjoin = array();
            if ($i < $this->countcol) {
                $arr = $babDB->db_fetch_array($this->rescol);
                if ($arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS) {
                    $rr = $babDB->db_fetch_array($babDB->db_query("select name, description from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'"));
                    $this->coltxt = bab_toHtml(translateDirectoryField($rr['description']));
                    $filedname = $rr['name'];
                    $tmp[] = $filedname;
                    $this->select[] = 'e.' . $filedname;
                } else {
                    $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
                    $this->coltxt = bab_toHtml(translateDirectoryField($rr['name']));
                    $filedname = "babdirf" . $arr['id'];

                    $leftjoin[] = 'LEFT JOIN ' . BAB_DBDIR_ENTRIES_EXTRA_TBL . ' lj' . $arr['id'] . " ON lj" . $arr['id'] . ".id_fieldx='" . $arr['id'] . "' AND e.id=lj" . $arr['id'] . ".id_entry";
                    $tmp[] = $filedname;
                    $this->select[] = "lj" . $arr['id'] . '.field_value ' . $filedname . "";
                }
                if ($this->xf == '') {
                    $this->xf = $tmp[0];
                }
                if ($_GET['idx'] == 'sdbovml') {
                    $this->colurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdbovml&directoryid=" . urlencode($this->id) . "&pos=" . urlencode($this->ord . $this->pos) . "&xf=" . urlencode($filedname));
                } else {
                    $this->colurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $this->id . "&pos=" . urlencode($this->ord . $this->pos) . "&xf=" . urlencode($filedname));
                }
                if ($this->xf == $filedname) {
                    $this->border = true;
                } else {
                    $this->border = false;
                }
                $i ++;
                return true;
            } else {
                if (count($tmp) > 0) {
                    if ($this->xf == "") {
                        $this->xf = $tmp[0];
                    }


                    if ($this->idgroup > 1) {
                        $req = " " . BAB_USERS_TBL . " u2,
                                " . BAB_USERS_GROUPS_TBL . " u,
                                " . BAB_DBDIR_ENTRIES_TBL . " e
                                    " . implode(' ', $leftjoin) . "
                                    WHERE u.id_group='" . $babDB->db_escape_string($this->idgroup) . "'
                                    AND u2.id=e.id_user
                                    AND " . bab_userInfos::queryAllowedUsers('u2') . "
                                    AND u.id_object=e.id_user
                                    AND e.id_directory='0'";
                    } elseif (1 == $this->idgroup) {
                        $req = " " . BAB_USERS_TBL . " u,
                        " . BAB_DBDIR_ENTRIES_TBL . " e
                        " . implode(' ', $leftjoin) . "
                        WHERE
                            u.id=e.id_user
                            AND " . bab_userInfos::queryAllowedUsers('u') . "
                            AND e.id_directory='0'";
                    } else {
                        $req = " " . BAB_DBDIR_ENTRIES_TBL . " e " . implode(' ', $leftjoin) . " WHERE e.id_directory='" . $babDB->db_escape_string($this->id) . "'";
                    }


                    $this->select[] = 'e.id';
                    if (! in_array('email', $this->select))
                        $this->select[] = 'e.email';

                    if (! empty($this->pos) && false === mb_strpos($this->xf, 'babdirf'))
                        $like = " AND e.`" . $babDB->db_escape_string($this->xf) . "` LIKE '" . $babDB->db_escape_string($this->pos) . "%'";
                    elseif (0 === mb_strpos($this->xf, 'babdirf')) {
                        $idfield = mb_substr($this->xf, 7);
                        $like = " AND lj" . $idfield . ".field_value LIKE '" . $babDB->db_escape_string($this->pos) . "%'";
                    } else
                        $like = '';

                    $req = "select " . implode(',', $this->select) . " from " . $req . " " . $like . " order by `" . $babDB->db_escape_string($this->xf) . "` ";
                    if ($this->ord == "-") {
                        $req .= "asc";
                    } else {
                        $req .= "desc";
                    }


                    $this->res = $babDB->db_query($req);
                    $this->count = $babDB->db_num_rows($this->res);
                } else {
                    $this->count = 0;
                }

                return false;
            }
        }

        public function getnext()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->count) {
                $this->altbg = ! $this->altbg;
                $this->arrf = $babDB->db_fetch_array($this->res);
                $this->urlmail = null;
                $this->email = $this->arrf['email'];

                if ($_GET['idx'] == 'sdbovml') {
                    $this->url = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=ddbovml&directoryid=" . $this->id . "&userid=" . $this->arrf['id'] . "&pos=" . urlencode($this->ord . $this->pos) . "&xf=" . urlencode($this->xf));
                } else {
                    $this->url = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=ddb&id=" . urlencode($this->id) . "&idu=" . urlencode($this->arrf['id']));
                }
                $this->urledir = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=ddbed&id=" . urlencode($this->id) . "&idu=" . urlencode($this->arrf['id']));
                $i ++;
                return true;
            }
            return false;
        }

        public function getnextcolval()
        {
            static $i = 0;
            if ($i < $this->countcol) {
                $this->coltxt = nl2br(bab_toHtml(stripslashes($this->arrf[$i]), BAB_HTML_ALL & ~ BAB_HTML_LINKS & ~ BAB_HTML_P));
                $this->mailcol = $this->arrf[$i] == $this->email && $this->email != '';
                $i ++;
                return true;
            }
            $i = 0;
            return false;
        }

        public function getnextselect()
        {
            static $k = 0;
            static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            if ($k < 26) {
                $this->selectname = mb_substr($t, $k, 1);
                if ($_GET['idx'] == 'sdbovml') {
                    $this->selecturl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdbovml&directoryid=" . urlencode($this->id) . "&pos=" . urlencode(($this->ord == "-" ? "" : $this->ord)) . $this->selectname . "&xf=" . urlencode($this->xf));
                } else {
                    $this->selecturl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . urlencode($this->id) . "&pos=" . urlencode(($this->ord == "-" ? "" : $this->ord)) . $this->selectname . "&xf=" . urlencode($this->xf));
                }
                if ($this->pos == $this->selectname)
                    $this->selected = 1;
                else
                    $this->selected = 0;
                $k ++;
                return true;
            }
            return false;
        }
    }

    $temp = new browseDbDirectoryTpl($id, $pos, $xf, $badd, $disable_email);
    $babBody->babecho(bab_printTemplate($temp, "directory.html", "adbrowse"));

    bab_siteMap::setPosition('bab', 'UserDbDirId' . $id);

    return $temp->idgroup;
}



function browseDbDirectoryWithOvml($badd)
{
    global $babBody, $babDB;

    $args = &$_GET;

    if (bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $args['directoryid'])) {
        $arr = $babDB->db_fetch_array($babDB->db_query("select id_group, ovml_list, disable_email from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($args['directoryid']) . "'"));

        if (! empty($arr['ovml_list'])) {
            $GLOBALS['babWebStat']->addDatabaseDirectory($args['directoryid']);
            $args['DirectoryUrl'] = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=sdbovml");
            if (! isset($args['order'])) {
                $args['order'] = 'asc';
            }
            if (! isset($args['orderby'])) {
                $args['orderby'] = '';
            }
            if (! isset($args['like'])) {
                $args['like'] = 'A';
            }
            $args['disable_email'] = $arr['disable_email'];
            $babBody->babecho(bab_printOvmlTemplate($arr['ovml_list'], $args));
        } else {
            if (! isset($GLOBALS['pos'])) {
                $GLOBALS['pos'] = 'A';
            }
            if (! isset($GLOBALS['xf'])) {
                $GLOBALS['xf'] = '';
            }
            return browseDbDirectory($args['directoryid'], $GLOBALS['pos'], $GLOBALS['xf'], $badd, $arr['disable_email']);
        }
        return $arr['id_group'];
    }
    return '';
}



function summaryLdapContact($id, $cn)
{

    class summaryLdapContactTpl
    {

        public function __construct($id, $cn)
        {
            global $babDB;
            $this->res = $babDB->db_query("select * from " . BAB_DBDIR_FIELDS_TBL . " where name !='jpegphoto' and x_name!=''");
            if ($this->res && $babDB->db_num_rows($this->res) > 0) {
                $this->count = $babDB->db_num_rows($this->res);
            } else {
                $this->count = 0;
            }

            $res = $babDB->db_query("select * , DECODE(password, \"" . $GLOBALS['BAB_HASH_VAR'] . "\") as adpass from " . BAB_LDAP_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'");
            if ($res && $babDB->db_num_rows($res) > 0) {
                $arr = $babDB->db_fetch_array($res);
                $this->ldapdecodetype = $arr['decoding_type'];
                $this->ldap = new babLDAP($arr['host'], "", true);
                $this->ldap->connect();
                $this->ldap->bind($arr['userdn'], $arr['adpass']);
                $this->entries = $this->ldap->search($arr['basedn'], "(|(cn=" . bab_ldapEncode(ldap_escapefilter($cn), $this->ldapdecodetype) . "))");
                $this->ldap->close();
                $this->name = bab_toHtml(bab_ldapDecode($this->entries[0]['cn'][0], $this->ldapdecodetype));

                $photo = new bab_dirEntryPhoto($id);
                $this->urlimg = bab_toHtml($photo->getUrl());
            }
            $this->bfieldv = true;
            $this->showph = true;
        }

        public function getnextfield()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->count) {
                $arr = $babDB->db_fetch_array($this->res);
                $this->fieldn = bab_toHtml(translateDirectoryField($arr['description']));
                $this->fieldv = isset($this->entries[0][$arr['x_name']][0]) ? bab_toHtml(bab_ldapDecode($this->entries[0][$arr['x_name']][0], $this->ldapdecodetype)) : '';
                $i ++;
                return true;
            }
            return false;
        }
    }

    $temp = new summaryLdapContactTpl($id, $cn);
    echo bab_printTemplate($temp, "directory.html", "summaryldapcontact");
}



class bab_modifyDbContact
{

    public $refresh;

    public function __construct($id, $idu, $fields, $refresh)
    {
        global $babBody, $babDB;
        $this->helpfields = bab_translate("Those fields must be filled");
        $this->file = bab_translate("Photo");
        $this->update = bab_translate("Update");
        $this->id = bab_toHtml($id);

        $this->fields = $fields;
        $this->what = 'dbc';
        $this->badd = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id);
        $this->bupd = bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id);
        $this->buserinfo = false;
        $this->refresh = bab_toHtml($refresh);

        if (! empty($babBody->msgerror)) {
            $this->msgerror = $babBody->msgerror;
            $this->error = true;
        }

        $arr = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
        $this->idgroup = $arr['id_group'];
        $arr = $babDB->db_fetch_array($babDB->db_query("select user_update from " . BAB_DB_DIRECTORIES_TBL . " where id_group=" . $babDB->quote(BAB_REGISTERED_GROUP)));
        $allowuu = $arr['user_update'];

        $personnal = false;

        if (false === $idu) {
            $req = "select id from " . BAB_DBDIR_ENTRIES_TBL . " where id_user='" . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . "'";
            list ($idu) = $babDB->db_fetch_array($babDB->db_query($req));
            $personnal = true;
        } else {
            $req = "select id from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "' AND id_user='" . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . "'";
            $res = $babDB->db_query($req);
            $personnal = $babDB->db_num_rows($res) > 0;
        }

        $this->idu = bab_toHtml($idu);


        $this->res = $babDB->db_query("select * from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . ($this->idgroup != 0 ? 0 : $babDB->db_escape_string($this->id)) . "' and disabled='N' order by list_ordering asc");
        $this->fxidaccess = array();
        if ($this->res && $babDB->db_num_rows($this->res) > 0) {
            $this->count = $babDB->db_num_rows($this->res);
            while ($arr = $babDB->db_fetch_array($this->res)) {
                if ($this->bupd || (! $this->bupd && $allowuu == 'Y' && $personnal) || bab_isAccessValid(BAB_DBDIRFIELDUPDATE_GROUPS_TBL, $arr['id'])) {
                    $this->fxidaccess[$arr['id']] = true;
                }
            }
            $babDB->db_data_seek($this->res, 0);
        } else {
            $this->count = 0;
        }

        if (count($this->fxidaccess) == 0) {
            die(bab_translate('Access denied'));
        }

        $this->showph = false;
        $res = $babDB->db_query("select *, LENGTH(photo_data) as plen from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='" . ($this->idgroup != 0 ? 0 : $babDB->db_escape_string($this->id)) . "' and id='" . $babDB->db_escape_string($idu) . "'");
        if ($res && $babDB->db_num_rows($res) > 0) {
            $this->arr = $babDB->db_fetch_array($res);
            $res = $babDB->db_query("select * from " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " where id_entry='" . $babDB->db_escape_string($idu) . "'");
            while ($arr = $babDB->db_fetch_array($res)) {
                $this->arr['babdirf' . $arr['id_fieldx']] = $arr['field_value'];
            }

            $this->name = stripslashes($this->arr['givenname'] . " " . $this->arr['sn']);
            $this->name = bab_toHtml($this->name);
            if ($this->arr['plen'] > 0) {
                $this->showph = true;
                $photo = new bab_dirEntryPhoto($idu);
                $this->urlimg = bab_toHtml($photo->getUrl());
                $this->delete = bab_translate("Delete this picture");
            }
        } else {
            $this->name = '';
            $this->urlimg = '';
        }

        $res = $babDB->db_query("select dft.id, dft.modifiable, dft.required from " . BAB_DBDIR_FIELDSEXTRA_TBL . " dft join " . BAB_DBDIR_FIELDS_TBL . " where id_directory='" . ($this->idgroup != 0 ? 0 : $babDB->db_escape_string($this->id)) . "' and id_field=" . BAB_DBDIR_FIELDS_TBL . ".id and " . BAB_DBDIR_FIELDS_TBL . ".name='jpegphoto' and disabled ='N'");

        $this->modify = false;
        $this->phrequired = false;
        $this->delph = false;
        if ($res && $babDB->db_num_rows($res) > 0) {
            $arr = $babDB->db_fetch_array($res);
            if (isset($this->fxidaccess[$arr['id']]) && $arr['modifiable'] == "Y") {
                $this->modify = true;
                $this->delph = true;
            }

            if ($arr['required'] == 'Y') {
                $this->phrequired = true;
                $this->delph = false;
            }
        }
    }

    public function getnextfield(&$skip)
    {
        global $babDB;
        static $i = 0;
        if ($i < $this->count) {
            $arr = $babDB->db_fetch_array($this->res);
            if ($arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS) {
                $res = $babDB->db_query("select description, name from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'");
                $rr = $babDB->db_fetch_array($res);
                $this->fieldn = bab_toHtml(translateDirectoryField($rr['description']));
                $this->fieldv = bab_toHtml($rr['name']);
            } else {
                $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
                $this->fieldn = bab_toHtml(translateDirectoryField($rr['name']));
                $this->fieldv = "babdirf" . $arr['id'];
            }

            if ($this->fieldv == 'jpegphoto') {
                $skip = true;
                $i ++;
                return true;
            }

            if (isset($this->fields[$this->fieldv])) {
                $this->fvalue = stripslashes($this->fields[$this->fieldv]);
            } else {
                $this->fvalue = isset($this->arr[$this->fieldv]) ? stripslashes($this->arr[$this->fieldv]) : '';
            }

            $this->foriginalvalue = $this->fvalue;
            $this->fvalue = bab_toHtml($this->fvalue);

            $this->resfxv = $babDB->db_query("select field_value from " . BAB_DBDIR_FIELDSVALUES_TBL . " where id_fieldextra='" . $babDB->db_escape_string($arr['id']) . "' ORDER BY field_value ASC");
            $this->countfxv = $babDB->db_num_rows($this->resfxv);

            $this->required = $arr['required'];
            if ($this->countfxv == 0) {
                $this->multivalues = false;
            } elseif ($this->countfxv > 1) {
                $this->multivalues = true;
            } else {
                $this->multivalues = $arr['multi_values'] == 'Y' ? true : false;
            }
            $this->fieldt = $arr['multilignes'];

            if ($this->badd || (isset($this->fxidaccess[$arr['id']]) && $arr['modifiable'] == "Y")) {
                $this->modify = true;
            } else {
                $this->modify = false;
                if (empty($this->fvalue)) {
                    $skip = true;
                    $i ++;
                    return true;
                }
            }


            $i ++;
            return true;
        }
        return false;
    }

    public function getnextfxv()
    {
        global $babDB;
        static $i = 0;
        if ($i < $this->countfxv) {
            $arr = $babDB->db_fetch_array($this->resfxv);
            $this->fxvvalue = bab_toHtml($arr['field_value']);
            if ($this->foriginalvalue == $arr['field_value']) {
                $this->selected = 'selected';
            } else {
                $this->selected = '';
            }
            $i ++;
            return true;
        }
        $i = 0;
        return false;
    }
}

function modifyDbContact($id, $idu, $fields, $refresh)
{
    require_once $GLOBALS['babInstallPath'] . 'utilit/urlincl.php';
    global $babBody, $babDB;

    if (false === $idu) {
        $id_user = bab_getUserId();
    } else {
        list ($id_user) = $babDB->db_fetch_array($babDB->db_query("select id_user from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "'"));
    }

    if ($id_user) {
        $unload = bab_url::get_request('tg');
        $unload->idx = 'dbcunload';
        $unload->msg = bab_translate("Your contact has been updated");

        $usereditor = bab_functionality::get('UserEditor');
        /*@var $usereditor Func_UserEditor */
        $usereditor->setDirectory($id);
        $page = $usereditor->getAsPage($id_user, $unload);
        /*@var $page Widget_BabPage */
        $page->setEmbedded(false);
        $page->displayHtml();
    } else {

        $temp = new bab_modifyDbContact($id, $idu, $fields, $refresh);
        $babBody->babPopup(bab_printTemplate($temp, "directory.html", "modifycontact"));
    }
}

class bab_addDbContact
{

    public $helpfields;

    public $file;

    public $update;

    public $id;

    public $idu;

    public $fields;

    public $what;

    public $modify;

    public $showph;

    public $msgerror;

    public $error;

    public $db;

    public $res;

    public $count;

    public $name;

    public $urlimg;

    public $idgroup;

    /**
     * The directory contain users or not
     *
     * @var bool
     */
    public $buserinfo;

    public $nickname;

    public $password;

    public $repassword;

    public $notifyuser;

    public $sendpassword;

    public $yes;

    public $no;

    public $fieldn;

    public $fieldv;

    public $fvalue;

    public $fieldt;

    public $required;

    public $refresh;

    public function __construct($id, $fields)
    {
        global $babBody, $babDB;
        $this->helpfields = bab_translate("Those fields must be filled");
        $this->file = bab_translate("Photo");
        $this->update = bab_translate("Update");
        $this->id = $id;
        $this->idu = '';
        $this->fields = $fields;
        $this->what = 'dbac';
        $this->modify = true;
        $this->showph = false;
        $this->refresh = '';

        if (! empty($babBody->msgerror)) {
            $this->msgerror = $babBody->msgerror;
            $this->error = true;
        }

        $this->name = '';
        $this->urlimg = '';
        $this->name = bab_translate("Add new contact");




        list ($this->idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
        if ($this->idgroup >= 1) {
            $iddir = 0;
            $this->buserinfo = true;
            $this->nickname = bab_translate("Login ID");
            $this->password = bab_translate("Password");
            $this->repassword = bab_translate("Retype Password");
            $this->notifyuser = bab_translate("Notify user");
            $this->sendpassword = bab_translate("Send password with email");
            $this->yes = bab_translate("Yes");
            $this->no = bab_translate("No");
        } else {
            $iddir = $id;
            $this->buserinfo = false;
        }


        $this->phrequired = false;

        $res = $babDB->db_query("
                select
                modifiable, required
                from
                " . BAB_DBDIR_FIELDSEXTRA_TBL . "
                join " . BAB_DBDIR_FIELDS_TBL . " f

                where
                id_directory='" . ($this->idgroup > 0 ? 0 : $babDB->db_escape_string($this->id)) . "'
                and id_field=f.id
                and f.name='jpegphoto'
                AND disabled ='N'
                ");

        if ($res && $babDB->db_num_rows($res) > 0) {
            $arr = $babDB->db_fetch_assoc($res);
            $this->phrequired = &$arr['required'];
            $this->modify = true;
        } else
            $this->modify = false;

        $this->res = $babDB->db_query("select * from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . $babDB->db_escape_string($iddir) . "' and disabled='N' order by list_ordering asc");
        if ($this->res && $babDB->db_num_rows($this->res) > 0) {
            $this->count = $babDB->db_num_rows($this->res);
        } else {
            $this->count = 0;
        }
    }

    public function getnextfield(&$skip)
    {
        global $babDB;
        static $i = 0;
        if ($i < $this->count) {
            $this->modify = true;
            $arr = $babDB->db_fetch_array($this->res);
            if ($arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS) {
                $res = $babDB->db_query("select description, name from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'");
                $rr = $babDB->db_fetch_array($res);
                $this->fieldn = bab_toHtml(translateDirectoryField($rr['description']));
                $this->fieldv = bab_toHtml($rr['name']);
            } else {
                $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
                $this->fieldn = bab_toHtml(translateDirectoryField($rr['name']));
                $this->fieldv = "babdirf" . $arr['id'];
            }

            if ($this->fieldv == 'jpegphoto') {
                $skip = true;
                $i ++;
                return true;
            }

            if (isset($this->fields[$this->fieldv])) {
                $this->fvalue = $this->fields[$this->fieldv];
            } else {
                $this->fvalue = '';
            }

            $this->resfxv = $babDB->db_query("select field_value from " . BAB_DBDIR_FIELDSVALUES_TBL . " where id_fieldextra='" . $babDB->db_escape_string($arr['id']) . "' ORDER BY field_value ASC");
            $this->countfxv = $babDB->db_num_rows($this->resfxv);

            $this->required = $arr['required'];
            if ($this->countfxv == 0) {
                $this->multivalues = false;
            } elseif ($this->countfxv > 1) {
                $this->multivalues = true;
            } else {
                $this->multivalues = $arr['multi_values'] == 'Y' ? true : false;
            }

            $this->fieldt = $arr['multilignes'];
            if (! empty($arr['default_value']) && empty($this->fvalue) && $this->countfxv > 0) {
                $rr = $babDB->db_fetch_array($babDB->db_query("select field_value from " . BAB_DBDIR_FIELDSVALUES_TBL . " where id='" . $babDB->db_escape_string($arr['default_value']) . "' ORDER BY field_value ASC"));
                $this->fvalue = $rr['field_value'];
            }
            $this->ofvalue = $this->fvalue;
            $this->fvalue = bab_toHtml($this->fvalue);
            $i ++;
            return true;
        } else
            return false;
    }

    public function getnextfxv()
    {
        global $babDB;
        static $i = 0;
        if ($i < $this->countfxv) {
            $arr = $babDB->db_fetch_array($this->resfxv);
            $this->fxvvalue = $arr['field_value'];
            if ($this->ofvalue == $this->fxvvalue) {
                $this->selected = 'selected';
            } else {
                $this->selected = '';
            }
            $this->fxvvalue = bab_toHtml($this->fxvvalue);
            $i ++;
            return true;
        } else {
            $i = 0;
            return false;
        }
    }
}

function addDbContact($id, $fields)
{
    global $babBody, $babDB;

    $res = $babDB->db_query('SELECT id_group FROM bab_db_directories WHERE id=' . $babDB->quote($id));
    if ($arr = $babDB->db_fetch_assoc($res)) {
        if ($arr['id_group'] > 0) {

            require_once dirname(__FILE__) . '/utilit/urlincl.php';

            $unload = bab_url::get_request('tg');
            $unload->idx = 'dbcunload';
            $unload->msg = bab_translate("Your contact has been created");

            $usereditor = bab_functionality::get('UserEditor');
            $usereditor->setDirectory($id);
            /*@var $usereditor Func_UserEditor */
            $page = $usereditor->getAsPage(null, $unload);
            /*@var $page Widget_BabPage */
            $page->setEmbedded(false);
            $page->displayHtml();
        }
    }


    $temp = new bab_addDbContact($id, $fields);
    $babBody->babPopup(bab_printTemplate($temp, "directory.html", "modifycontact"));
}

function importDbFile($id)
{
    global $babBody;

    class importDbFileTpl
    {

        public $import;

        public $name;

        public $id;

        public $separator;

        public $other;

        public $comma;

        public $tab;

        public function __construct($id)
        {
            $this->id = bab_toHtml($id);
            $this->import = bab_translate("Import");
            $this->name = bab_translate("File");
            $this->separator = bab_translate("Separator");
            $this->other = bab_translate("Other");
            $this->comma = bab_translate("Comma");
            $this->tab = bab_translate("Tab");
            $this->t_encoding = bab_translate("Encoding");
            $this->maxfilesize = $GLOBALS['babMaxFileSize'];
        }
    }

    $temp = new importDbFileTpl($id);
    $babBody->babecho(bab_printTemplate($temp, "directory.html", "dbimpfile"));
}



function exportDbFile($id)
{
    global $babBody;

    class exportDbFileTpl
    {

        public $export;

        public $name;

        public $id;

        public $separator;

        public $other;

        public $comma;

        public $tab;

        public function __construct($id)
        {
            global $babDB;
            $this->id = bab_toHtml($id);
            $this->export = bab_translate("Export");
            $this->separator = bab_translate("Separator");
            $this->other = bab_translate("Other");
            $this->comma = bab_translate("Comma");
            $this->tab = bab_translate("Tab");
            $this->t_yes = bab_translate("Yes");
            $this->t_no = bab_translate("No");
            $this->t_export_disbaled_users = bab_translate("Include disabled users");
            $this->t_output = bab_translate("Output format");
            $this->t_ovidentia_csv = bab_translate("Ovidentia CSV");
            $this->t_google_csv = bab_translate("Google CSV");
            $this->t_outlook_csv = bab_translate("Outlook CSV");
            $this->t_vcard = bab_translate("VCard");

            $this->infotxt = bab_translate("Specify which fields will be exported");
            $this->listftxt = "---- " . bab_translate("Fields") . " ----";
            $this->listdftxt = "---- " . bab_translate("Fields to export") . " ----";

            $this->moveup = bab_translate("Move Up");
            $this->movedown = bab_translate("Move Down");

            $arr = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
            if ($arr['id_group'] != 0) {
                $iddir = 0;
            } else {
                $iddir = $id;
            }

            $this->bgroup = $arr['id_group'] > 0;

            $this->selected_ovidentia_csv = '';
            $this->selected_google_csv = '';
            $this->selected_outlook_csv = '';
            $this->selected_vcard = '';

            $this->selected_1 = '';
            $this->selected_2 = '';
            $this->selected_0 = '';
            $this->separvalue = '';

            $res = $babDB->db_query("select output_format, separatorchar
                    from " . BAB_DBDIR_CONFIGEXPORT_TBL . "
                    where
                        id_directory='" . $babDB->db_escape_string($id) . "'
                        and id_user='" . $babDB->db_escape_string(bab_getUserId()) . "'");

            if ($res && $babDB->db_num_rows($res) > 0) {
                $arr = $babDB->db_fetch_assoc($res);
            } else {
                $arr = array(
                    'output_format' => 'ovidentia_csv',
                    'separatorchar' => 44
                );
            }


            switch ($arr['output_format']) {
                default:
                case 'ovidentia_csv':
                    $this->selected_ovidentia_csv = 'selected';
                    break;
                case 'google_csv':
                    $this->selected_google_csv = 'selected';
                    break;
                case 'outlook_csv':
                    $this->selected_outlook_csv = 'selected';
                    break;
                case 'vcard':
                    $this->selected_vcard = 'selected';
                    break;
            }


            switch ($arr['separatorchar']) {
                case 44:
                    $this->selected_1 = 'selected';
                    break;
                case 9:
                    $this->selected_2 = 'selected';
                    break;
                default:
                    $this->selected_0 = 'selected';
                    $this->separvalue = chr($arr['separatorchar']);
                    break;
            }

            $this->resfd = $babDB->db_query("select id, id_field from " . BAB_DBDIR_FIELDSEXPORT_TBL . " where id_directory='" . $babDB->db_escape_string($id) . "' and id_user='" . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . "' AND id_field<>5 order by ordering asc");
            $this->countfd = $babDB->db_num_rows($this->resfd);
            $arrexp = array(
                5
            );
            if ($this->countfd) {
                while ($arr = $babDB->db_fetch_array($this->resfd)) {
                    $arrexp[] = $arr['id_field'];
                }
                $babDB->db_data_seek($this->resfd, 0);
            }

            $this->resf = $babDB->db_query("select id, id_field from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . $babDB->db_escape_string($iddir) . "' and id_field NOT IN(" . $babDB->quote($arrexp) . ")  order by list_ordering asc");
            $this->countf = $babDB->db_num_rows($this->resf);
        }

        public function getnextf()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->countf) {
                $arr = $babDB->db_fetch_array($this->resf);
                $this->fid = $arr['id_field'];
                if ($this->fid < BAB_DBDIR_MAX_COMMON_FIELDS) {
                    $arr = $babDB->db_fetch_array($babDB->db_query("select description from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'"));
                    $this->fieldval = bab_toHtml(translateDirectoryField($arr['description']));
                } else {
                    $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($this->fid - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
                    $this->fieldval = bab_toHtml(translateDirectoryField($rr['name']));
                }
                $i ++;
                return true;
            }
            return false;
        }

        public function getnextdf()
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->countfd) {
                $arr = $babDB->db_fetch_array($this->resfd);
                $this->fid = $arr['id_field'];
                if ($this->fid < BAB_DBDIR_MAX_COMMON_FIELDS) {
                    $arr = $babDB->db_fetch_array($babDB->db_query("select description from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'"));
                    $this->fieldval = bab_toHtml(translateDirectoryField($arr['description']));
                } else {
                    $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($this->fid - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
                    $this->fieldval = bab_toHtml(translateDirectoryField($rr['name']));
                }
                $i ++;
                return true;
            }
            return false;
        }
    }

    $temp = new exportDbFileTpl($id);
    $babBody->babecho(bab_printTemplate($temp, "directory.html", "dbexpfile"));
}



function mapDbFile($id, $wsepar, $separ)
{
    global $babBody;

    class mapDbFileTpl
    {

        public $res;

        public $count;

        public $db;

        public $id;

        public function __construct($id, $pfile, $wsepar, $separ)
        {
            global $babDB;
            $this->helpfields = bab_translate("Those fields must be filled");
            $this->process = bab_translate("Import");
            $this->handling = bab_translate("Handling duplicates");
            $this->duphand0 = bab_translate("Allow duplicates to be created");
            $this->duphand1 = bab_translate("Replace duplicates with items imported");
            $this->duphand2 = bab_translate("Do not import duplicates");
            list ($this->idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
            if ($this->idgroup >= 1) {
                $this->t_dupinfo = bab_translate("Entries with the same login ID or the same firstname/lastname are duplicates");
                $this->buserinfo = true;
                $this->nickname = bab_translate("Login ID");
                $this->password = bab_translate("Default password (at least 6 characters)");
                $this->repassword = bab_translate("Retype default password");
                $this->altpassword = bab_translate("Or use this field as password if filled");
                $this->notifyuser = bab_translate("Notify users");
                $this->sendpassword = bab_translate("Send password with email");
                $this->yes = bab_translate("Yes");
                $this->no = bab_translate("No");
            } else {
                $this->t_dupinfo = bab_translate("Entries with the same e-mail address are duplicates");
                $this->buserinfo = false;
            }

            $this->id = bab_toHtml($id);
            $this->pfile = bab_toHtml($pfile);

            $this->res = $babDB->db_query("select * from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . ($this->idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "'");
            if ($this->res && $babDB->db_num_rows($this->res) > 0)
                $this->count = $babDB->db_num_rows($this->res);
            else
                $this->count = 0;

            switch ($wsepar) {
                case "1":
                    $separ = ",";
                    break;
                case "2":
                    $separ = "\t";
                    break;
                default:
                    if (empty($separ))
                        $separ = ",";
                    break;
            }

            $encoding = bab_rp('encoding', 'ISO-8859-15');

            if (file_exists($pfile)) {
                $fd = fopen($pfile, "r");
                $csv = fgetcsv($fd, 4096, $separ);
                // Remove utf8 bom
                foreach ($csv as $k => $v) {
                    $csv[$k] = trim(str_replace("\xEF\xBB\xBF", '', $v), '"');
                }
                $this->arr = bab_getStringAccordingToDataBase($csv, $encoding);
                fclose($fd);
            }
            $this->separ = bab_toHtml($separ);
            $this->encoding = bab_toHtml($encoding);
        }

        public function getnextfield(&$skip)
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->count) {
                $arr = $babDB->db_fetch_array($this->res);
                if ($arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS) {
                    $res = $babDB->db_query("select description, name from " . BAB_DBDIR_FIELDS_TBL . " where id='" . $babDB->db_escape_string($arr['id_field']) . "'");
                    $rr = $babDB->db_fetch_array($res);
                    $this->ofieldname = bab_toHtml(translateDirectoryField($rr['description']));
                    $this->ofieldv = bab_toHtml($rr['name']);
                } else {
                    $rr = $babDB->db_fetch_array($babDB->db_query("select * from " . BAB_DBDIR_FIELDS_DIRECTORY_TBL . " where id='" . $babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)) . "'"));
                    $this->ofieldname = bab_toHtml(translateDirectoryField($rr['name']));
                    $this->ofieldv = "babdirf" . $arr['id'];
                }

                $this->required = $arr['required'];
                if ($this->ofieldv == 'jpegphoto') {
                    $skip = true;
                    $i ++;
                    return true;
                }

                $i ++;
                return true;
            }
            return false;
        }

        public function getnextval()
        {
            static $i = 0;
            static $k = 0;
            if ($i < count($this->arr)) {
                $this->ffieldid = $i;
                $this->ffieldname = bab_toHtml($this->arr[$i]);
                if (isset($this->ofieldname) && mb_strtolower($this->ofieldname) == mb_strtolower($this->ffieldname))
                    $this->fselected = "selected";
                else
                    $this->fselected = "";
                $i ++;
                return true;
            }
            $k ++;
            $i = 0;
            return false;
        }
    }


    include_once $GLOBALS['babInstallPath'] . 'utilit/uploadincl.php';

    $fileObj = bab_fileHandler::upload('uploadf');
    if ($fileObj) {
        $tmpfile = $fileObj->importTemporary();
        if (false === $tmpfile) {
            $babBody->msgerror = bab_translate("Cannot create temporary file");
            return;
        }
    } elseif (isset($_POST['pfile'])) {
        $tmpfile = bab_pp('pfile');
    }

    $temp = new mapDbFileTpl($id, $tmpfile, $wsepar, $separ);
    $babBody->babecho(bab_printTemplate($temp, "directory.html", "dbmapfile"));
}



function emptyDb($id)
{
    global $babBody;

    class emptyDbTpl
    {

        public $warning;

        public $message;

        public $title;

        public $urlyes;

        public $urlno;

        public $yes;

        public $no;

        public function __construct($id)
        {
            $this->message = bab_translate("Are you sure you want to empty this directory");
            $this->title = getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
            $this->warning = bab_translate("WARNING: This operation will delete all entries") . "!";
            $this->urlyes = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=list&id=" . $id . "&action=Yes");
            $this->yes = bab_translate("Yes");
            $this->urlno = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
            $this->no = bab_translate("No");
        }
    }

    $temp = new emptyDbTpl($id);
    $babBody->babecho(bab_printTemplate($temp, "warning.html", "warningyesno"));
}



function contactDbUnload($msg, $refresh)
{

    class contactDbUnloadTpl
    {

        public $message;

        public $close;

        public $refresh;

        public $sContent;

        public function __construct($msg, $refresh)
        {
            $this->sContent = 'text/html; charset=' . bab_charset::getIso();

            if (empty($refresh)) {
                $this->refresh = true;
            } else {
                $this->refresh = false;
            }
            $this->message = bab_toHtml($msg);
            $this->close = bab_translate("Close");
        }
    }

    $temp = new contactDbUnloadTpl($msg, $refresh);
    echo bab_printTemplate($temp, "directory.html", "dbcontactunload");
}



function dbEntryDirectories($id, $idu)
{
    global $babBody;

    class dbEntryDirectoriesCls
    {

        public function __construct($id, $idu)
        {
            global $babDB;

            list ($iduser) = $babDB->db_fetch_row($babDB->db_query("select id_user from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "'"));
            if ($iduser == 0) {
                die(bab_translate('Access denied'));
            }

            $this->directorytxt = bab_translate("Directories");
            $this->desctxt = bab_translate("Description");
            $this->membertxt = bab_translate("is member of the following directories");

            $this->fullname = bab_toHtml(bab_getUserName($iduser));
            $groups = bab_getUserGroups($iduser);
            $res = $babDB->db_query("select id, name, description, id_group from " . BAB_DB_DIRECTORIES_TBL . " where id_group!=0 order by name asc");

            $this->iddirectories = array();
            while ($arr = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id'])) {
                    if ($arr['id_group'] == BAB_REGISTERED_GROUP) {
                        $this->iddirectories[] = $arr;
                    } else
                        if (count($groups) > 0 && in_array($arr['id_group'], $groups['id'])) {
                            $this->iddirectories[] = $arr;
                        }
                }
            }

            $this->count = count($this->iddirectories);
        }

        public function getnextdb()
        {
            static $i = 0;
            if ($i < $this->count) {
                $arr = $this->iddirectories[$i];
                $this->dbname = bab_toHtml($arr['name']);
                $this->dbdescription = bab_toHtml($arr['description']);
                $i ++;
                return true;
            } else {
                $i = 0;
                return false;
            }
        }
    }

    $temp = new dbEntryDirectoriesCls($id, $idu);
    $babBody->babPopup(bab_printTemplate($temp, "directory.html", "dbentrydirectories"));
}

function assignList($id)
{

    class assignListTpl
    {

        public $fullname;

        public $urlname;

        public $url;

        public $fullnameval;

        public $arr = array();

        public $db;

        public $count;

        public $res;

        public $idvr;

        public $pos;

        public $selected;

        public $allselected;

        public $allurl;

        public $allname;

        public $checkall;

        public $uncheckall;

        public $deletealt;

        public $modify;

        public $altbg = true;

        public function __construct($id)
        {
            global $babDB;
            $this->allname = bab_translate("All");
            $this->uncheckall = bab_translate("Uncheck all");
            $this->checkall = bab_translate("Check all");
            $this->modify = bab_translate("Assign");
            $this->t_close = bab_translate("Close");

            $this->id = bab_toHtml($id);
            $this->refresh = '';

            $arrgrpids = array();
            $res = $babDB->db_query("select id, id_group from " . BAB_DB_DIRECTORIES_TBL . " where id != '" . $babDB->db_escape_string($id) . "' and id_group != 0");
            while ($arr = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id'])) {
                    $arrgrpids[] = $arr['id_group'];
                }
            }

            $this->bview = false;
            if (count($arrgrpids)) {

                $this->bview = true;

                if (in_array(BAB_REGISTERED_GROUP, $arrgrpids)) {
                    $arrgrpids = false;
                }

                $this->orderBy = bab_gp('order', 0);
                $this->pos = bab_gp('pos', '');
                $this->ord = '';

                $this->arrgrpids = $arrgrpids;
                if (! $this->orderBy) {
                    if ($arrgrpids === false) {
                        $req = "select ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut where " . bab_userInfos::queryAllowedUsers('ut') . " AND lastname like '" . $babDB->db_escape_string($this->pos) . "%' order by lastname, firstname asc";
                    } else {
                        $req = "select distinct ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut left join " . BAB_USERS_GROUPS_TBL . " ug on ut.id=ug.id_object where " . bab_userInfos::queryAllowedUsers('ut') . " and ug.id in (" . $babDB->quote($arrgrpids) . ") AND lastname like '" . $babDB->db_escape_string($this->pos) . "%' order by lastname, firstname asc";
                    }

                    $this->fullname = bab_translate("Lastname") . " " . bab_translate("Firstname");

                    $this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=assign&order=1&id=" . $id);
                } else {
                    if ($arrgrpids === false) {
                        $req = "select ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut where " . bab_userInfos::queryAllowedUsers('ut') . " AND firstname like '" . $babDB->db_escape_string($this->pos) . "%' order by firstname, lastname asc";
                    } else {
                        $req = "select distinct ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut left join " . BAB_USERS_GROUPS_TBL . " ug on ut.id=ug.id_object where " . bab_userInfos::queryAllowedUsers('ut') . " and ug.id in (" . $babDB->quote($arrgrpids) . ") AND firstname like '" . $babDB->db_escape_string($this->pos) . "%' order by firstname, lastname asc";
                    }

                    $this->fullname = bab_translate("Firstname") . " " . bab_translate("Lastname");
                    $this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=assign&order=0&id=" . $id);
                }
                $this->res = $babDB->db_query($req);
                $this->count = $babDB->db_num_rows($this->res);

                if (empty($this->pos)) {
                    $this->allselected = 1;
                } else {
                    $this->allselected = 0;
                }
                $this->allurl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=assign&pos=&id=" . $id);

                list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
                $res = $babDB->db_query("select id_object from " . BAB_USERS_GROUPS_TBL . " where id_group='" . $babDB->db_escape_string($idgroup) . "'");
                $this->groupmemebers = array();
                while ($arr = $babDB->db_fetch_array($res)) {
                    $this->groupmemebers[$arr['id_object']] = true;
                }
            } else {
                $this->count = 0;
                $this->allselected = 1;
            }
        }

        public function getnext(&$skip)
        {
            global $babDB;
            static $i = 0;
            if ($i < $this->count) {
                $this->arr = $babDB->db_fetch_array($this->res);
                if (! isset($this->groupmemebers[$this->arr['id']])) {
                    $this->selected = '';

                    $this->altbg = ! $this->altbg;


                    $this->url = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=assign&id=" . $this->id . "&order=" . $this->orderBy . "&pos=" . $this->pos);
                    if (! $this->orderBy) {
                        $this->urlname = bab_toHtml($this->arr['lastname'] . ' ' . $this->arr['firstname']);
                    } else {
                        $this->urlname = bab_toHtml($this->arr['firstname'] . ' ' . $this->arr['lastname']);
                    }

                    $this->userid = bab_toHtml($this->arr['id']);
                } else {
                    $skip = true;
                }
                $i ++;
                return true;
            }
            return false;
        }

        public function getnextselect()
        {
            static $k = 0;
            static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            if ($k < 26) {
                $this->selectname = $t[$k];

                global $babDB;

                if (! $this->orderBy) {
                    if ($this->arrgrpids === false) {
                        $req = "select ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut where " . bab_userInfos::queryAllowedUsers('ut') . " AND lastname like '" . $babDB->db_escape_string($this->selectname) . "%' order by lastname, firstname asc";
                    } else {
                        $req = "select distinct ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut left join " . BAB_USERS_GROUPS_TBL . " ug on ut.id=ug.id_object where " . bab_userInfos::queryAllowedUsers('ut') . " and ug.id in (" . $babDB->quote($this->arrgrpids) . ") AND lastname like '" . $babDB->db_escape_string($this->selectname) . "%' order by lastname, firstname asc";
                    }
                } else {
                    if ($this->arrgrpids === false) {
                        $req = "select ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut where " . bab_userInfos::queryAllowedUsers('ut') . " AND firstname like '" . $babDB->db_escape_string($this->selectname) . "%' order by firstname, lastname asc";
                    } else {
                        $req = "select distinct ut.id, ut.firstname, ut.lastname from " . BAB_USERS_TBL . " ut left join " . BAB_USERS_GROUPS_TBL . " ug on ut.id=ug.id_object where " . bab_userInfos::queryAllowedUsers('ut') . " and ug.id in (" . $babDB->quote($this->arrgrpids) . ") AND firstname like '" . $babDB->db_escape_string($this->selectname) . "%' order by firstname, lastname asc";
                    }
                }
                $res = $babDB->db_query($req);
                $count = $babDB->db_num_rows($res);

                if ($count) {
                    $this->selecturl = bab_toHtml($GLOBALS['babUrlScript'] . "?tg=directory&idx=assign&pos=" . $this->selectname . "&id=" . $this->id . "&order=" . $this->orderBy);
                    if ($this->pos == $this->selectname) {
                        $this->selected = 1;
                    } else {
                        $this->selected = 0;
                    }
                } else {
                    $this->selected = 1;
                    $this->selecturl = '#';
                }
                $k ++;
                return true;
            }
            return false;
        }
    }

    $temp = new assignListTpl($id);

    include_once $GLOBALS['babInstallPath'] . 'utilit/uiutil.php';
    $GLOBALS['babBodyPopup'] = new babBodyPopup();
    $GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
    $GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
    $GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp, "directory.html", "assignlist"));
    printBabBodyPopup();
}

function confirmAssignEntry($id, $fields, $idauser, $idatype)
{
    class confirmAssignEntryTpl
    {

        public $warning;

        public $message;

        public $title;

        public $urlyes;

        public $urlno;

        public $yes;

        public $no;

        public $sContent;

        public function __construct($id, $fields, $idauser, $idatype)
        {
            global $babDB;

            $this->refresh = '';
            $this->id = bab_toHtml($id);
            $this->idauser = bab_toHtml($idauser);
            $this->fields = & $fields;
            $arr = $babDB->db_fetch_array($babDB->db_query("select ut.nickname,det.sn, det.givenname, det.mn from " . BAB_DBDIR_ENTRIES_TBL . " det left join " . BAB_USERS_TBL . " ut on ut.id = det.id_user where id_user='" . $babDB->db_escape_string($idauser) . "' and id_directory='0'"));
            list ($this->directoryname) = $babDB->db_fetch_row($babDB->db_query("select name from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
            $this->directoryname = bab_toHtml($this->directoryname);
            $this->fullnametxt = bab_translate("Fullname");
            $this->nicknametxt = bab_translate("Login ID");
            $this->usernickname = bab_toHtml($arr['nickname']);
            $this->userfullname = bab_toHtml(bab_getUserName($idauser));
            $this->sContent = 'text/html; charset=' . bab_charset::getIso();

            if ($idatype == 'nickname') {
                $this->warning = bab_translate("WARNING: User with this login ID already exist");
            } else {
                $this->warning = bab_translate("WARNING: User with this fullname already exist");
            }

            $this->message = bab_translate("Would you like to assign this user to the current directory");
            $this->yes = bab_translate("Yes");
            $this->no = bab_translate("No");
        }

        public function getnextfield()
        {
            if (list ($this->fieldname, $this->fieldvalue) = each($this->fields)) {
                $this->fieldname = bab_toHtml($this->fieldname);
                $this->fieldvalue = bab_toHtml($this->fieldvalue);
                return true;
            }
            return false;
        }
    }

    $temp = new confirmAssignEntryTpl($id, $fields, $idauser, $idatype);
    echo bab_printTemplate($temp, "directory.html", "confirmassignuser");
}





/**
 * Display directory entry image
 *
 * @param int $idu
 *            directory entry ID
 * @see bab_dirEntryPhoto::getUrl()
 *
 */
function getDbContactImage($idu)
{
    global $babDB;
    $res = $babDB->db_query("select photo_data, photo_type, date_modification from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "'");
    if ($res && $babDB->db_num_rows($res) > 0) {
        $arr = $babDB->db_fetch_assoc($res);
        if ($arr['photo_data'] != "") {
            header("Content-type: " . $arr['photo_type']);
            echo $arr['photo_data'];
            return;
        }
    }
    $fp = fopen($GLOBALS['babSkinPath'] . "/images/nophoto.jpg", "rb");
    if ($fp) {
        header("Content-type: image/jpeg");
        echo fread($fp, filesize($GLOBALS['babSkinPath'] . "/images/nophoto.jpg"));
        fclose($fp);
    }
}

function getLdapContactImage($id, $cn)
{
    global $babDB;
    $res = $babDB->db_query("select * , DECODE(password, \"" . $GLOBALS['BAB_HASH_VAR'] . "\") as adpass from " . BAB_LDAP_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'");

    if ($res && $babDB->db_num_rows($res) > 0) {
        $arr = $babDB->db_fetch_array($res);
        $ldap = new babLDAP($arr['host'], "", true);
        $ldap->connect();
        $ldap->bind($arr['userdn'], $arr['adpass']);

        $res = $ldap->read("cn=" . $cn . "," . $arr['basedn'], "objectClass=*", array(
            "jpegphoto"
        ));
        if ($res) {
            $ei = $ldap->first_entry($res);
            if ($ei) {
                $info = $ldap->get_values_len($ei, "jpegphoto");
                header("Content-type: image/jpeg");
                echo $info[0];
                return;
            }
        }
    }

    $fp = fopen($GLOBALS['babSkinPath'] . "/images/nophoto.jpg", "rb");
    if ($fp) {
        header("Content-type: image/jpeg");
        echo fread($fp, filesize($GLOBALS['babSkinPath'] . "/images/nophoto.jpg"));
        fclose($fp);
    }
}

function updateDbContact($id, $idu, $fields, $file, $tmp_file, $photod)
{
    global $babBody, $babDB;

    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
    list ($allowuu) = $babDB->db_fetch_array($babDB->db_query("select user_update from " . BAB_DB_DIRECTORIES_TBL . " where id_group=" . $babDB->quote(BAB_REGISTERED_GROUP)));

    $usertbl = $babDB->db_fetch_assoc($babDB->db_query("select id_user,givenname,mn,sn from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "'"));

    $iduser = &$usertbl['id_user'];

    /* Users who have add access right, can update all fields even those wich are not updatable */
    $badd = bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id);
    $bupd = bab_isAccessValid(BAB_DBDIRUPDATE_GROUPS_TBL, $id);

    $baccess = false;
    if ($badd || ($idgroup != '0' && $allowuu == "Y" && $iduser == $GLOBALS['BAB_SESS_USERID'] && $iduser > 0)) {
        $baccess = true;
    }


    $res = $babDB->db_query("select dfxt.*, dft.name from " . BAB_DBDIR_FIELDSEXTRA_TBL . " dfxt left join " . BAB_DBDIR_FIELDS_TBL . " dft on dfxt.id_field=dft.id where id_directory='" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "' and dfxt.id_field < " . BAB_DBDIR_MAX_COMMON_FIELDS . "");

    $fxidaccess = array();
    if ($res && $babDB->db_num_rows($res) > 0) {
        while ($arr = $babDB->db_fetch_array($res)) {
            if (($baccess && $arr['modifiable'] == 'Y') || (($bupd || bab_isAccessValid(BAB_DBDIRFIELDUPDATE_GROUPS_TBL, $arr['id'])) && $arr['modifiable'] == 'Y')) {
                $fxidaccess[$arr['name']] = $arr;
            }
        }
    }

    $res = $babDB->db_query("select dfxt.* from " . BAB_DBDIR_FIELDSEXTRA_TBL . " dfxt where id_directory='" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "' and dfxt.id_field > " . BAB_DBDIR_MAX_COMMON_FIELDS . "");

    if ($res && $babDB->db_num_rows($res) > 0) {
        while ($arr = $babDB->db_fetch_array($res)) {
            if (($baccess && $arr['modifiable'] == 'Y') || (($bupd || bab_isAccessValid(BAB_DBDIRFIELDUPDATE_GROUPS_TBL, $arr['id'])) && $arr['modifiable'] == 'Y')) {
                $fxidaccess['babdirf' . $arr['id']] = $arr;
            }
        }
    }

    if ($baccess == false && count($fxidaccess)) {
        $baccess = true;
    }

    if (! $baccess) {
        $babBody->addError(bab_translate('Access denied'));
        return false;
    }

    foreach ($fxidaccess as $fname => $datafield) {
        if ($datafield['required'] == 'Y') {
            if ($fname == 'jpegphoto') {
                if (empty($file) || $file == "none") {
                    $tmp = $babDB->db_fetch_assoc($babDB->db_query("select photo_data from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "' and id='" . $babDB->db_escape_string($idu) . "'"));

                    if (empty($tmp['photo_data'])) {
                        $babBody->msgerror = bab_translate("You must complete required fields");
                        return false;
                    }
                } else {
                    if ($babBody->babsite['imgsize'] > 0 && $babBody->babsite['imgsize'] * 1000 < filesize($tmp_file)) {
                        $babBody->msgerror = bab_translate("The image file is too big, maximum is :") . $babBody->babsite['imgsize'] . bab_translate("Kb");
                        return false;
                    }
                    include_once $GLOBALS['babInstallPath'] . "utilit/uploadincl.php";
                    $cphoto = bab_getUploadedFileContent('photof');
                }
            } elseif ($fname == 'email') {
                if (! isset($fields['email']) || empty($fields['email']) || ! bab_isEmailValid($fields['email'])) {
                    $babBody->msgerror = bab_translate("Your email is not valid !!");
                    return false;
                }
            } elseif (! isset($fields[$fname]) || empty($fields[$fname])) {
                $babBody->msgerror = bab_translate("You must complete required fields");
                return false;
            }
        }
    }

    if ($idgroup > 0 && (isset($fields['givenname']) || isset($fields['mn']) || isset($fields['sn']) || isset($fields['email']))) {

        $replace = array(
            " " => "",
            "-" => ""
        );

        if (! isset($fields['givenname']))
            $fields['givenname'] = $usertbl['givenname'];

        if (! isset($fields['mn']))
            $fields['mn'] = $usertbl['mn'];

        if (! isset($fields['sn']))
            $fields['sn'] = $usertbl['sn'];

        $hashname = md5(mb_strtolower(strtr($fields['givenname'] . $fields['mn'] . $fields['sn'], $replace)));
        $query = "select * from " . BAB_USERS_TBL . " where hashname='" . $babDB->db_escape_string($hashname) . "' and id!='" . $babDB->db_escape_string($iduser) . "'";
        $res = $babDB->db_query($query);
        if ($babDB->db_num_rows($res) > 0) {
            $babBody->msgerror = bab_translate("Firstname and Lastname already exists !!");
            return false;
        }

        $babDB->db_query("update " . BAB_USERS_TBL . " set firstname='" . $babDB->db_escape_string($fields['givenname']) . "', lastname='" . $babDB->db_escape_string($fields['sn']) . "', email='" . $babDB->db_escape_string($fields['email']) . "', hashname='" . $babDB->db_escape_string($hashname) . "' where id='" . $babDB->db_escape_string($iduser) . "'");
        $bupdate = true;
    }

    $req = '';
    reset($fxidaccess);
    $cphoto = '';
    foreach ($fxidaccess as $fname => $datafield) {
        if ($fname == 'jpegphoto' && ! empty($file) && $file != "none") {
            include_once $GLOBALS['babInstallPath'] . "utilit/uploadincl.php";
            $cphoto = bab_getUploadedFileContent('photof');
        } elseif (isset($fields[$fname])) {
            if ($datafield['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS) {
                $req .= $fname . "='" . $babDB->db_escape_string($fields[$fname]) . "',";
            } else {
                if (mb_substr($fname, 0, mb_strlen("babdirf")) == 'babdirf') {
                    $tmp = mb_substr($fname, mb_strlen("babdirf"));

                    $bupdate = true;
                    $rs = $babDB->db_query("select id from " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " where id_fieldx='" . $babDB->db_escape_string($tmp) . "' and  id_entry='" . $babDB->db_escape_string($idu) . "'");
                    if ($rs && $babDB->db_num_rows($rs) > 0) {
                        $babDB->db_query("update " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " set field_value='" . $babDB->db_escape_string($fields[$fname]) . "' where id_fieldx='" . $babDB->db_escape_string($tmp) . "' and id_entry='" . $babDB->db_escape_string($idu) . "'");
                    } else {
                        $babDB->db_query("insert into " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " ( field_value, id_fieldx, id_entry) values ('" . $babDB->db_escape_string($fields[$fname]) . "', '" . $babDB->db_escape_string($tmp) . "', '" . $babDB->db_escape_string($idu) . "')");
                    }
                }
            }
        }
    }

    if (! empty($cphoto))
        $req .= " photo_data='" . $babDB->db_escape_string($cphoto) . "'";
    elseif ($photod == "delete")
        $req .= " photo_data=''";
    else
        $req = mb_substr($req, 0, mb_strlen($req) - 1);

    $bupdate = false;
    if (! empty($req)) {
        $req = "update " . BAB_DBDIR_ENTRIES_TBL . " set " . $req;
        $req .= " where id='" . $babDB->db_escape_string($idu) . "'";
        $babDB->db_query($req);
        $bupdate = true;
    }


    if ($bupdate) {
        $babDB->db_query("update " . BAB_DBDIR_ENTRIES_TBL . " set date_modification=now(), id_modifiedby='" . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . "' where id='" . $babDB->db_escape_string($idu) . "'");

        include_once $GLOBALS['babInstallPath'] . "utilit/eventdirectory.php";

        if ($iduser) {
            $event = new bab_eventUserModified($iduser);
            bab_fireEvent($event);
        }

        $event = new bab_eventDirectoryEntryModified($idu);
        bab_fireEvent($event);
    }




    return true;
}

function confirmAddDbContact($id, $fields, $file, $tmp_file, $password1, $password2, $nickname, $notifyuser, $sendpwd)
{
    global $babBody, $babDB;
    $bassign = false;

    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));

    if (! empty($fields['email']) && ! bab_isEmailValid($fields['email'])) {
        $babBody->msgerror = bab_translate("Your email is not valid !!");
        return 0;
    }

    if (! empty($file) && $file != "none") {
        if ($babBody->babsite['imgsize'] > 0 && $babBody->babsite['imgsize'] * 1000 < filesize($tmp_file)) {
            $babBody->msgerror = bab_translate("The image file is too big, maximum is :") . $babBody->babsite['imgsize'] . bab_translate("Kb");
            return 0;
        }
    }

    if ($idgroup > 0) {
        if (bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id) && $idgroup != BAB_REGISTERED_GROUP) {
            $bassign = true;
            $arrgrpids = array();
            $res = $babDB->db_query("select id, id_group from " . BAB_DB_DIRECTORIES_TBL . " where id != '" . $babDB->db_escape_string($id) . "' and id_group != 0");
            while ($arr = $babDB->db_fetch_array($res)) {
                if (bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id'])) {
                    $arrgrpids[] = $arr['id_group'];
                }
            }
        }

        if (empty($nickname)) {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return 0;
        }

        if ($bassign) {
            $res = $babDB->db_query("select ut.id from " . BAB_USERS_TBL . " ut where ut.nickname='" . $babDB->db_escape_string($nickname) . "'");
            if ($babDB->db_num_rows($res) > 0) {
                $arr = $babDB->db_fetch_array($res);
                $groups = bab_getUserGroups($arr['id']);
                $groupsids = &$groups['id'];
                $groupsids[] = BAB_REGISTERED_GROUP;
                if (! in_array($idgroup, $groupsids)) {
                    if (count($arrgrpids)) {
                        $tmparr = array_intersect($groupsids, $arrgrpids);
                        if (count($tmparr)) {
                            $GLOBALS['idauser'] = $arr['id'];
                            $GLOBALS['idatype'] = 'nickname';
                            return 2;
                        }
                    }
                }
            }
        }

        if (empty($fields['sn']) || empty($fields['givenname'])) {
            $babBody->msgerror = bab_translate("You must complete firstname and lastname fields !!");
            return 0;
        }

        if ($bassign) {
            $res = $babDB->db_query("select id_user from " . BAB_DBDIR_ENTRIES_TBL . " where givenname='" . $babDB->db_escape_string($fields['givenname']) . "' and sn='" . $babDB->db_escape_string($fields['sn']) . "' and mn='" . $babDB->db_escape_string($fields['mn']) . "' and id_directory='0'");
            if ($babDB->db_num_rows($res) > 0) {
                $arr = $babDB->db_fetch_array($res);
                $groups = bab_getUserGroups($arr['id_user']);
                $groupsids = &$groups['id'];
                $groupsids[] = BAB_REGISTERED_GROUP;
                if (! in_array($idgroup, $groupsids)) {
                    if (count($arrgrpids)) {
                        $tmparr = array_intersect($groupsids, $arrgrpids);
                        if (count($tmparr)) {
                            $GLOBALS['idauser'] = $arr['id_user'];
                            $GLOBALS['idatype'] = 'fullname';
                            return 2;
                        }
                    }
                }
            }
        }


        if (empty($password1) || empty($password2)) {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return 0;
        }

        if ($password1 != $password2) {
            $babBody->msgerror = bab_translate("Passwords not match !!");
            return 0;
        }
        $minPasswordLengh = 6;
        if (ISSET($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])) {
            $minPasswordLengh = $GLOBALS['babMinPasswordLength'];
            if ($minPasswordLengh < 1) {
                $minPasswordLengh = 1;
            }
        }
        if (mb_strlen($password1) < $minPasswordLengh) {
            $babBody->msgerror = sprintf(bab_translate("Password must be at least %s characters !!"), $minPasswordLengh);
            return 0;
        }
    }

    $res = $babDB->db_query("select id, required from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "' and id_field>'" . BAB_DBDIR_MAX_COMMON_FIELDS . "'");
    while ($arr = $babDB->db_fetch_array($res)) {
        $ixfield = 'babdirf' . $arr['id'];
        if ($arr['required'] == "Y" && (! isset($fields[$ixfield]) || empty($fields[$ixfield]))) {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return 0;
        }
    }

    $res = $babDB->db_query("select * from " . BAB_DBDIR_FIELDS_TBL . "");
    $req = '';
    while ($arr = $babDB->db_fetch_array($res)) {
        $rr = $babDB->db_fetch_array($babDB->db_query("select required from " . BAB_DBDIR_FIELDSEXTRA_TBL . " where id_directory='" . ($idgroup != 0 ? 0 : $babDB->db_escape_string($id)) . "' and id_field='" . $babDB->db_escape_string($arr['id']) . "'"));
        if ($arr['name'] != 'jpegphoto' && $rr['required'] == "Y" && empty($fields[$arr['name']])) {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return 0;
        }

        if ($arr['name'] == 'jpegphoto' && $rr['required'] == "Y" && (empty($file) || $file == "none")) {
            $babBody->msgerror = bab_translate("You must complete required fields");
            return 0;
        }

        if (isset($fields[$arr['name']]) && $arr['name'] != 'jpegphoto') {
            if ($idgroup > 0)
                $req .= $arr['name'] . "='" . $babDB->db_escape_string($fields[$arr['name']]) . "',";
            else
                $req .= $arr['name'] . ",";
        }
    }


    if ($idgroup > 0) {
        $iduser = registerUser(stripslashes($fields['givenname']), stripslashes($fields['sn']), stripslashes($fields['mn']), $fields['email'], $nickname, $password1, $password2, true);
        if ($iduser == false) {
            return 0;
        }
        if ($idgroup > 1) {
            bab_addUserToGroup($iduser, $idgroup);
        }

        if ($notifyuser == "Y") {

            $firstname = $babDB->db_escape_string($fields['givenname']);
            $lastname = $babDB->db_escape_string($fields['sn']);


            notifyAdminUserRegistration(bab_composeUserName($firstname, $lastname), $fields['email'], $nickname, $sendpwd == "Y" ? $password1 : "");
        }
    }


    if (! empty($file) && $file != "none") {
        $fp = fopen($tmp_file, "rb");
        if ($fp) {
            $cphoto = fread($fp, filesize($tmp_file));
            fclose($fp);
        }
    }

    if (! empty($cphoto)) {
        if ($idgroup > 0) {
            $req .= " photo_data='" . $babDB->db_escape_string($cphoto) . "',";
        } else
            $req .= "photo_data,";
    }

    if ($idgroup > 0 && ! empty($req)) {
        list ($iddbu) = $babDB->db_fetch_array($babDB->db_query("select id from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='0' and id_user='" . $babDB->db_escape_string($iduser) . "'"));
        $req = "update " . BAB_DBDIR_ENTRIES_TBL . " set " . mb_substr($req, 0, mb_strlen($req) - 1);
        $req .= " where id='" . $babDB->db_escape_string($iddbu) . "'";
        $babDB->db_query($req);
    } else
        if (! empty($req)) {
            $req = "insert into " . BAB_DBDIR_ENTRIES_TBL . " (" . $req . "id_directory, id_user) values (";
            $babDB->db_data_seek($res, 0);
            while ($arr = $babDB->db_fetch_array($res)) {
                if (isset($fields[$arr['name']])) {
                    $req .= "'" . $babDB->db_escape_string($fields[$arr['name']]) . "',";
                }
            }
            if (! empty($cphoto))
                $req .= "'" . $babDB->db_escape_string($cphoto) . "',";

            if ($idgroup > 0)
                $req .= "'0', '" . $babDB->db_escape_string($iduser) . "')";
            else
                $req .= "'" . $babDB->db_escape_string($id) . "', '0')";
            $babDB->db_query($req);
            $iddbu = $babDB->db_insert_id();
        }

    foreach ($fields as $key => $value) {
        if (mb_substr($key, 0, mb_strlen("babdirf")) == 'babdirf') {
            $tmp = mb_substr($key, mb_strlen("babdirf"));

            $babDB->db_query("INSERT into " . BAB_DBDIR_ENTRIES_EXTRA_TBL . "
                (id_fieldx, id_entry, field_value)
            values
                ('" . $babDB->db_escape_string($tmp) . "','" . $babDB->db_escape_string($iddbu) . "','" . $babDB->db_escape_string($value) . "')");
        }
    }


    $babDB->db_query("update " . BAB_DBDIR_ENTRIES_TBL . " set
        date_modification=now(),
        id_modifiedby='" . $babDB->db_escape_string($GLOBALS['BAB_SESS_USERID']) . "'
        WHERE id='" . $babDB->db_escape_string($iddbu) . "'
    ");


    include_once $GLOBALS['babInstallPath'] . "utilit/eventdirectory.php";
    $event = new bab_eventDirectoryEntryCreated($iddbu);
    bab_fireEvent($event);

    return 1;
}

function confirmEmptyDb($id)
{
    global $babDB;
    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
    if ($idgroup != 0 && $idgroup <= BAB_ADMINISTRATOR_GROUP ) /* Ovidentia directory and administrators group */
        return;

    if ($idgroup == 0) {
        $res = $babDB->db_query("select id, id_user from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='" . $babDB->db_escape_string($id) . "'");
        while ($arr = $babDB->db_fetch_array($res)) {
            $babDB->db_query("delete from " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " where id_entry='" . $babDB->db_escape_string($arr['id']) . "'");
        }
        $babDB->db_query("delete from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='" . $babDB->db_escape_string($id) . "'");
    } elseif ($idgroup > BAB_ADMINISTRATOR_GROUP) {
        include_once $GLOBALS['babInstallPath'] . "utilit/delincl.php";
        $res = $babDB->db_query("delete from " . BAB_USERS_GROUPS_TBL . " where id_group='" . $babDB->db_escape_string($idgroup) . "'");
        /*
         * $babDB->db_fetch_array($res))
         * {
         * bab_deleteUser($arr['id_object']);
         * }
         */
    }
}

function deleteDbContact($id, $idu)
{
    global $babDB;
    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
    if ($idgroup != 0) {
        include_once $GLOBALS['babInstallPath'] . "utilit/delincl.php";
        list ($iddu) = $babDB->db_fetch_array($babDB->db_query("select id_user from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "'"));
        bab_deleteUser($iddu);
        return;
    }
    $babDB->db_query("delete from " . BAB_DBDIR_ENTRIES_EXTRA_TBL . " where id_entry='" . $babDB->db_escape_string($idu) . "'");
    $babDB->db_query("delete from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='" . $babDB->db_escape_string($id) . "' and id='" . $babDB->db_escape_string($idu) . "'");

    include_once $GLOBALS['babInstallPath'] . "utilit/eventdirectory.php";
    $event = new bab_eventDirectoryEntryDeleted($idu);
    bab_fireEvent($event);
}

function unassignDbContact($id, $idu)
{
    global $babDB;
    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
    if ($idgroup != 0 && $idgroup != BAB_REGISTERED_GROUP) {
        list ($iddu) = $babDB->db_fetch_array($babDB->db_query("select id_user from " . BAB_DBDIR_ENTRIES_TBL . " where id='" . $babDB->db_escape_string($idu) . "'"));
        bab_removeUserFromGroup($iddu, $idgroup);

        return;
    }
}

function exportDbDirectory($id, $output_format, $wsepar, $separ, $listfd)
{
    require_once dirname(__FILE__) . '/utilit/direxport.class.php';
    $bdisabled = ('Y' === bab_pp('bdisabled', 'Y'));
    $separ = bab_getPostedSeparator($wsepar, $separ);

    bab_saveExportFormStatus($id, $output_format, $listfd, $separ);

    switch ($output_format) {
        case 'ovidentia_csv':
            $export = new bab_dbdir_export_ovidentia_csv($id, $bdisabled, $separ, $listfd);
            break;

        case 'google_csv':
            $export = new bab_dbdir_export_google_csv($id, $bdisabled);
            break;

        case 'outlook_csv':
            $export = new bab_dbdir_export_outlook_csv($id, $bdisabled);
            break;

        case 'vcard':
            $export = new bab_dbdir_export_vcard($id, $bdisabled);
            break;
    }

    $export->output();
}

function assignDbContact($id, $userids)
{
    global $babDB;

    list ($idgroup) = $babDB->db_fetch_array($babDB->db_query("select id_group from " . BAB_DB_DIRECTORIES_TBL . " where id='" . $babDB->db_escape_string($id) . "'"));
    if ($idgroup && $idgroup != BAB_REGISTERED_GROUP) {
        for ($i = 0; $i < count($userids); $i ++) {
            bab_addUserToGroup($userids[$i], $idgroup);
        }
    }
}

/* main */
if (isset($_REQUEST['directoryid'])) {
    $directoryid = intval($_REQUEST['directoryid']);
    $id = $directoryid;
} else {
    $id = intval(bab_rp('id', ''));
    $directoryid = $id;
}

$idx = bab_rp('idx', 'list');
$msg = bab_rp('msg');

if (('' != bab_pp('pfile')) && bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id)) {
    bab_requireSaveMethod();

    try {
        processImportDbFile($_POST, false); // simulation, verify mapping
        $monitor = new bab_processImportUsers($id);
        $monitor->displayProgress();
    } catch (bab_DirImportMappingException $e) {
        $babBody->addError($e->getMessage());
        $idx = 'dbmap';
    }
}

if (('Yes' == bab_gp('action')) && bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id)) {
    bab_requireSaveMethod() && confirmEmptyDb($id);
}

/*
 * var_dump(bab_pp('expfile'));
 * die;
 */
if ('' != ($modify = bab_pp('modify'))) {
    bab_requireSaveMethod();

    if ($modify == 'dbc') {
        $idx = 'dbmod';
        $photo_name = isset($_FILES['photof']['name']) ? $_FILES['photof']['name'] : '';
        $photof = isset($_FILES['photof']['tmp_name']) ? $_FILES['photof']['tmp_name'] : '';
        if (updateDbContact($id, bab_pp('idu'), bab_pp('fields', array()), $photo_name, $photof, bab_pp('photod'))) {
            $msg = bab_translate("Your contact has been updated");
            $idx = 'dbcunload';
            $fields = array();
        }
    } else
        if ($modify == 'dbac' && bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id)) {
            $photo_name = isset($_FILES['photof']['name']) ? $_FILES['photof']['name'] : '';
            $photof = isset($_FILES['photof']['tmp_name']) ? $_FILES['photof']['tmp_name'] : '';
            $ret = confirmAddDbContact($id, bab_pp('fields', array()), $photo_name, $photof, bab_pp('password1'), bab_pp('password2'), bab_pp('nickname'), bab_pp('notifyuser'), bab_pp('sendpwd'));
            switch ($ret) {
                case 2:
                    $idx = 'cassign';
                    break;
                case 1:
                    $msg = bab_translate("Your contact has been added");
                    $idx = 'dbcunload';
                    break;
                case 0:
                default:
                    $idx = 'adbc';
                    break;
            }
        } elseif ($modify == 'assign' && bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id)) {
            assignDbContact($id, bab_pp('userids', array()));
            $msg = bab_translate("Your contacts has been assigned");
            $idx = 'dbcunload';
        } elseif ($modify == 'cassign') {
            if (isset($_REQUEST['byes']) && bab_isAccessValid(BAB_DBDIRBIND_GROUPS_TBL, $id)) {
                $idauser = bab_pp('idauser');
                assignDbContact($id, ($idauser == '' ? array() : array(
                    $idauser
                )));
                $msg = bab_translate("Your contact has been assigned");
                $idx = 'dbcunload';
            } else {
                $idx = 'adbc';
            }
        }
} else
    if (('' != bab_pp('expfile')) && bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id)) {
        exportDbDirectory($id, bab_pp('output_format'), bab_pp('wsepar'), bab_pp('separ'), bab_pp('listfd', array()));
        $idx = 'sdb';
    }

switch ($idx) {
    case 'deldbc':
        $id = bab_rp('id');
        $idu = bab_rp('idu');
        if (bab_isAccessValid(BAB_DBDIRDEL_GROUPS_TBL, $id) && bab_requireDeleteMethod()) {
            $msg = bab_translate("Your contact has been deleted");
            deleteDbContact($id, $idu);
        } else {
            $msg = bab_translate("Access denied");
        }
    /* no break */
    case 'dbcunload':
        contactDbUnload($msg, bab_rp('refresh'));
        exit();
        break;

    case 'unassign':
        $idu = bab_rp('idu');
        if (bab_isAccessValid(BAB_DBDIRUNBIND_GROUPS_TBL, $id)) {
            $msg = bab_translate("Your contact has been unassigned");
            unassignDbContact($id, $idu);
        } else {
            $msg = bab_translate("Access denied");
            exit();
        }
        contactDbUnload($msg, bab_rp('refresh'));
        exit();
        break;

    case 'ddbed':
        $babBody->title = '';
        dbEntryDirectories($id, bab_gp('idu'));
        exit();
        break;

    case 'dbmod':
        modifyDbContact($id, bab_rp('idu', false), bab_rp('fields', array()), bab_rp('refresh'));
        break;

    case 'getimg':
        getDbContactImage(bab_gp('idu'));
        exit();
        break;
    case 'getimgl':
        getLdapContactImage($id, bab_gp('cn'));
        exit();
        break;

    case 'ddbovml':
        $babBody->title = '';
        summaryDbContactWithOvml($_GET);
        exit();
        break;

    case 'ddb':
        $babBody->title = '';
        summaryDbContact($id, bab_gp('idu'));
        exit();
        break;

    case 'cassign':
        confirmAssignEntry($id, bab_pp('fields', array()), $GLOBALS['idauser'], $GLOBALS['idatype']);
        exit();
        break;

    case 'assign':
        assignList($id);
        exit();
        break;

    case 'adbc':
        $babBody->title = bab_translate("Add entry to") . ": " . getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
        if (bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id)) {
            addDbContact($id, bab_pp('fields', array()));
            exit();
        } else {
            $babBody->msgerror = bab_translate("Access denied");
        }
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        break;

    case 'usdb':
        UBrowseDbDirectory($id, bab_gp('pos', 'A'), bab_gp('xf'), bab_gp('cb'));
        exit();
        break;

    case 'sdbovml':
        $pos = bab_gp('pos', 'A');
        $babBody->title = bab_translate("Database Directory") . ": " . getDirectoryName($directoryid, BAB_DB_DIRECTORIES_TBL);
        $idgroup = browseDbDirectoryWithOvml(bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id));
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        $babBody->addItemMenu('sdbovml', bab_translate("Browse"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=ovml");
        if (bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbimp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbexp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id) && ($idgroup == 0 || $idgroup > BAB_ADMINISTRATOR_GROUP)) {
            $babBody->addItemMenu('empdb', bab_translate("Empty"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=empdb&id=" . $id);
        }
        break;

    case 'sdb':
        $pos = bab_gp('pos', 'A');
        $xf = bab_gp('xf');
        $babBody->title = bab_translate("Database Directory") . ": " . getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
        $idgroup = browseDbDirectory($id, $pos, $xf, bab_isAccessValid(BAB_DBDIRADD_GROUPS_TBL, $id));
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        $babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $id . "&pos=" . $pos);
        if (bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbimp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbexp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id) && ($idgroup == 0 || $idgroup > BAB_ADMINISTRATOR_GROUP)) {
            $babBody->addItemMenu('empdb', bab_translate("Empty"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=empdb&id=" . $id);
        }
        break;

    case 'dbimp':
        $pos = bab_gp('pos', 'A');
        $babBody->title = bab_translate("Import file to") . ": " . getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        $babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $id . "&pos=" . $pos);
        if (bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id)) {
            importDbFile($id);
            $babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbimp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbexp&id=" . $id);
        }
        break;

    case 'dbexp':
        $pos = bab_gp('pos', 'A');
        $babBody->title = bab_translate("Export file from") . ": " . getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        $babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $id . "&pos=" . $pos);
        if (bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbimp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id)) {
            exportDbFile($id);
            $babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbexp&id=" . $id);
        }
        break;

    case 'dbmap':
        $id = $id;
        $pos = bab_pp('pos', 'A');
        $wsepar = bab_pp('wsepar');
        $separ = bab_pp('separ');
        $babBody->title = bab_translate("Import file to") . ": " . getDirectoryName($id, BAB_DB_DIRECTORIES_TBL);
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        $babBody->addItemMenu('sdb', bab_translate("Browse"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=sdb&id=" . $id . "&pos=" . $pos);
        if (bab_isAccessValid(BAB_DBDIRIMPORT_GROUPS_TBL, $id)) {
            mapDbFile($id, $wsepar, $separ);
            $babBody->addItemMenu('dbimp', bab_translate("Import"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbimp&id=" . $id);
        }

        if (bab_isAccessValid(BAB_DBDIREXPORT_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('dbexp', bab_translate("Export"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=dbexp&id=" . $id);
        }

        break;

    case 'dldap':
        $cn = bab_gp('cn', '');
        $babBody->title = bab_translate("Summary of information about") . ': ' . $cn;
        summaryLdapContact($id, $cn);
        exit();
        break;

    case 'sldap':
        $pos = bab_gp('pos', 'A');
        $babBody->title = bab_translate("Ldap Directory") . ': ' . getDirectoryName($id, BAB_LDAP_DIRECTORIES_TBL);
        browseLdapDirectory($id, $pos);
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        $babBody->addItemMenu('sldap', bab_translate("Browse"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=sldap&id=" . $id . "&pos=" . $pos);
        break;

    case 'empdb':
        $babBody->title = bab_translate("Delete Database Directory");
        if (bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id))
            emptyDb($id);
        else
            $babBody->msgerror = bab_translate("Access denied");

        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        if (bab_isAccessValid(BAB_DBDIREMPTY_GROUPS_TBL, $id)) {
            $babBody->addItemMenu('empdb', bab_translate("Empty"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=empdb&id=" . $id);
        }
        break;

    case 'monitorimport':
        bab_processImportUsers::iframe();
        exit();
        break;

    case 'list':
    default:
        $babBody->title = '';
        listUserAds();
        $babBody->addItemMenu('list', bab_translate("Directories"), $GLOBALS['babUrlScript'] . "?tg=directory&idx=list");
        bab_siteMap::setPosition('bab', 'UserDir');
        break;
}

$babBody->setCurrentItemMenu($idx);
