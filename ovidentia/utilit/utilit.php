<?php
// -------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
// -------------------------------------------------------------------------
/**
 *
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */

/**
 * @internal SEC1 PR 2006-12-12 FULL
 */

include_once $GLOBALS['babInstallPath'] . 'utilit/template.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/userincl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/mailincl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/sitemap.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/eventincl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/groupsincl.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/body.class.php';
include_once $GLOBALS['babInstallPath'] . 'utilit/registry.php';


/**
 *
 * @param string $plaintext
 * @param string $key
 * @return string
 */
function bab_encrypt($plaintext, $key)
{
    $cipher = "AES-128-CBC";

    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($ivlen);
    $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $hmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    $ciphertext = base64_encode($iv . $hmac . $ciphertext_raw);

    return $ciphertext;
}


/**
 *
 * @param string $ciphertext
 * @param string $key
 * @return string
 */
function bab_decrypt($ciphertext, $key)
{
    $cipher = "AES-128-CBC";

    $c = base64_decode($ciphertext);
    $ivlen = openssl_cipher_iv_length($cipher);
    $iv = substr($c, 0, $ivlen);
    $sha2len = 32;
    $hmac = substr($c, $ivlen, $sha2len);
    $ciphertext_raw = substr($c, $ivlen + $sha2len);
    $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv);
    $calcmac = hash_hmac('sha256', $ciphertext_raw, $key, true);
    if (!hash_equals($hmac, $calcmac)) {
        $plaintext = '';
    }

    return $plaintext;
}


function bab_formatAuthor($format, $id)
{
    global $babDB;
    static $bab_authors = array();

    $txt = bab_translate('Anonymous');

    if (! empty($id)) {
        if (! isset($bab_authors[$id])) {
            $res = $babDB->db_query("select givenname, sn, mn from " . BAB_DBDIR_ENTRIES_TBL . " where id_directory='0' and id_user='" . $babDB->db_escape_string($id) . "'");
            if ($res && $babDB->db_num_rows($res) > 0) {
                $bab_authors[$id] = $babDB->db_fetch_array($res);
            }
        }
        if (isset($bab_authors[$id])) {
            $m = null;
            if (preg_match_all('/%(.)/', $format, $m)) {
                $txt = $format;
                for ($i = 0; $i < count($m[1]); $i ++) {
                    switch ($m[1][$i]) {
                        case 'F':
                            $val = $bab_authors[$id]['givenname'];
                            break;
                        case 'L':
                            $val = $bab_authors[$id]['sn'];
                            break;
                        case 'M':
                            $val = $bab_authors[$id]['mn'];
                            break;
                    }
                    $txt = preg_replace('/' . preg_quote($m[0][$i]) . '/', $val, $txt);
                }
            }
        }
    }

    return $txt;
}


function bab_isEmailValid($email)
{
    if (empty($email) || preg_match('/\s+/', $email)) {
        return false;
    } else {
        return true;
    }
}


/**
 * Get stylesheet url selected in options
 * @return string
 */
function bab_getCssUrl()
{
    global $babSkinPath, $babSkin;

    $skin = new bab_Skin($babSkin);

    $filepath = $skin->getThemePath() . 'styles/' . $GLOBALS['babStyle'];
    if (! file_exists($filepath)) {
        $filepath = $babSkinPath . 'styles/' . $GLOBALS['babStyle'];
        if (! file_exists($filepath)) {
            $filepath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/styles/ovidentia.css';
        }
    }
    return bab_getStaticUrl() . $filepath;
}

/**
 * Date and time format proposed for site configuration and user configuration
 * @array
 */
function bab_getRegionalFormats() {

    return array(
        'longDate' => array(
            'dd MMMM yyyy',
            'MMMM dd, yyyy',
            'dddd, MMMM dd, yyyy',
            'dddd dd MMMM yyyy',
            'dd MMMM, yyyy'
        ),

        'shortDate'	=> array(
            'M/d/yyyy',
            'dd/MM/yyyy',
            'dd/MM/yy',
            'M/d/yy',
            'MM/dd/yy',
            'MM/dd/yyyy',
            'yy/MM/dd',
            'yyyy-MM-dd',
            'dd-MMM-yy'
        ),

        'hour' => array(
            'HH:mm',
            'HH:mm tt',
            'HH:mm TT',
            'HH:mm:ss tt',
            'HH:mm:ss tt',
            'h:mm:ss tt',
            'hh:mm:ss tt',
            'HH:mm:ss',
            'H:m:s'
        )
    );
}



function bab_getDateFormat($format)
{
    $format = strtolower($format);


    $format = preg_replace("/(?<!m)m(?!m)/", "$1%n$2", $format);
    $format = preg_replace("/(?<!m)mm(?!m)/", "$1%n$2", $format);
    $format = preg_replace("/(?<!m)mmm(?!m)/", "$1%m$2", $format);
    $format = preg_replace("/(?<!m)m{4,}(?!m)/", "$1%M$3", $format);

    $format = preg_replace("/(?<!d)d(?!d)/", "$1%j$2", $format);
    $format = preg_replace("/(?<!d)dd(?!d)/", "$1%j$2", $format);
    $format = preg_replace("/(?<!d)ddd(?!d)/", "$1%d$2", $format);
    $format = preg_replace("/(?<!d)d{4,}(?!d)/", "$1%D$2", $format);

    $format = preg_replace("/(?<!y)y(?!y)/", "$1%y$2", $format);
    $format = preg_replace("/(?<!y)yy(?!y)/", "$1%y$2", $format);
    $format = preg_replace("/(?<!y)yyy(?!y)/", "$1%Y$2", $format);
    $format = preg_replace("/(?<!y)y{4,}(?!y)/", "$1%Y$2", $format);

    return $format;
}


/**
 * Test if time format use AM/PM
 * @return bool
 */
function bab_isAmPm()
{
    require_once dirname(__FILE__) . '/settings.class.php';

    $settings = bab_getInstance('bab_Settings');
    /*@var $settings bab_Settings */
    $arr = $settings->getSiteSettings();

    if ($arr['time_format'] == '') {
        return false;
    }

    $pos = mb_strpos(mb_strtolower($arr['time_format']), 't');
    if ($pos === false) {
        return false;
    }

    return true;
}



/**
 *
 * @param string $format
 * @return string
 */
function bab_getTimeFormat($format)
{
    $format = preg_replace("/(?<!h)h(?!h)/", "$1g$2", $format);
    $format = preg_replace("/(?<!h)h{2,}(?!h)/", "$1h$2", $format);

    $format = preg_replace("/(?<!H)H(?!H)/", "$1G$2", $format);
    $format = preg_replace("/(?<!H)H{2,}(?!H)/", "$1H$2", $format);

    $format = preg_replace("/(?<!m)m{1,}(?!m)/", "$1i$2", $format);
    $format = preg_replace("/(?<!s)s{1,}(?!s)/", "$1s$2", $format);

    $format = preg_replace("/(?<!t)t{1,}(?!t)/", "$1a$2", $format);
    $format = preg_replace("/(?<!T)T{1,}(?!T)/", "$1A$2", $format);

    return $format;
}





/**
 * This function convert the input string to the charset
 * of the database. If the charset of the string and the
 * database match so the string is not converted.
 *
 * bug with utf8 in url on firefox
 *
 * @param	string $sString	The string to convert
 * @return	string			The converted input string
 */
function bab_convertToDatabaseEncoding($sString)
{
    /*
     * An ending with 'e' with a acute accent (and probably other accentuated chars) mislead mb_detect_encoding
     * Adding a character will suppress the situation where the error occurs and will not modify our variable.
     * And it will still work if the error in the function will be fixed one day.
     */
    $sDetectedEncoding = mb_detect_encoding($sString . 'a', 'UTF-8, ISO-8859-15');
    $sEncoding = bab_charset::getIso();

    if ($sEncoding != $sDetectedEncoding) {
        return mb_convert_encoding($sString, $sEncoding, $sDetectedEncoding);
    }
    return $sString;
}









/**
 * Page head
 *
 */
class babHead
{
    /**
     * Page title in raw text
     * used for referencing
     * @see babBody::setTitle()
     * @var string
     */
    private $page_title = null;


    /**
     * Contain page description used for referencing
     * @see babBody::setDescription()
     * @var string
     */
    private $page_description = null;

    /**
     * Contain page keywords used for referencing
     * @see babBody::setKeywords()
     * @var string
     */
    private $page_keywords = null;

    /**
     *
     * @var string
     */
    private $canonicalUrl = null;

    /**
     *
     * @var string
     */
    private $imageUrl = null;


    /**
     * Get page title
     * @return string
     */
    public function getTitle()
    {
        if (null === $this->page_title) {
            return $GLOBALS['babBody']->raw_title;
        }

        return $this->page_title;
    }

    /**
     * Set page title with a text string (no html)
     * @param	string $title
     */
    public function setTitle($title)
    {
        $this->page_title = $title;
    }

    /**
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->page_description = $description;
    }


    /**
     *
     * @param string $keywords
     */
    public function setKeywords($keywords)
    {
        $this->page_keywords = $keywords;
    }


    /**
     *
     * @param string $canonicalUrl
     */
    public function setCanonicalUrl($canonicalUrl)
    {
        $this->canonicalUrl = $canonicalUrl;
    }


    /**
     * An image representation for the the page (at least a 200x200 px is recomended)
     * this can be used in a <meta property="og:image"> tag from the opengraph API or <link rel="image_src">
     * @since 7.9.0
     * @param string $imageUrl
     *
     *
     */
    public function setImageUrl($imageUrl)
    {
        $this->imageUrl = $imageUrl;
    }



    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->page_description;
    }

    /**
     * @return string
     */
    public function getKeywords()
    {
        return $this->page_keywords;
    }

    /**
     * @return string
     */
    public function getCanonicalUrl()
    {
        return $this->canonicalUrl;
    }

    /**
     * @since 7.9.0
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }
}




/**
 * Collection of calendars
 *
 * @return bab_icalendars
 */
function bab_getICalendars($id_user = '')
{
    include_once $GLOBALS['babInstallPath'] . 'utilit/calincl.php';
    static $calendars = null;

    if (! isset($calendars[$id_user])) {
        $calendars[$id_user] = new bab_icalendars($id_user);
    }

    return $calendars[$id_user];
}





/**
 * Update users settings
 */
function bab_updateUserSettings()
{
    global $babDB;
    require_once dirname(__FILE__) . '/delegincl.php';


    if (bab_isUserLogged()) {
        require_once dirname(__FILE__) . '/settings.class.php';
        require_once dirname(__FILE__) . '/userinfosincl.php';

        $settings = bab_getInstance('bab_Settings');
        /*@var $settings bab_Settings */
        $site = $settings->getSiteSettings();


        $id_user = bab_getUserId();

        $babDB->db_query("update " . BAB_USERS_LOG_TBL . " set id_user='" . $babDB->db_escape_string($id_user) . "' where sessid='" . $babDB->db_escape_string(session_id()) . "'");


        if ($arr = bab_userInfos::getUserSettings()) {
            if ('Y' === $site['change_lang']) {

                if ($arr['lang'] != '') {
                    $GLOBALS['babLanguage'] = $arr['lang'];
                }

                if ($arr['langfilter'] != '') {
                    bab_getInstance('babLanguageFilter')->setFilter($arr['langfilter']);
                }
            }

            if ('Y' === $site['change_skin']) {

                if ($arr['skin'] !== $GLOBALS['babSkin'] && ! empty($arr['skin'])) {
                    $GLOBALS['babSkin'] = $arr['skin'];
                }

                if (! empty($arr['style']) && is_file('skins/' . $GLOBALS['babSkin'] . '/styles/' . $arr['style'])) {
                    $GLOBALS['babStyle'] = $arr['style'];
                }
            }

            if ('Y' === $site['change_date']) {

                if ($arr['date_shortformat'] != '') {
                    $GLOBALS['babShortDateFormat'] = bab_getDateFormat($arr['date_shortformat']);
                }

                if ($arr['date_longformat'] != '') {
                    $GLOBALS['babLongDateFormat'] = bab_getDateFormat($arr['date_longformat']);
                }

                if ($arr['time_format'] != '') {
                    $GLOBALS['babTimeFormat'] = bab_getTimeFormat($arr['time_format']);
                }
            }

            if (isset($_GET['debug'])) {
                if (0 == $_GET['debug']) {
                    setcookie('bab_debug', '', time() - 31536000); // remove
                } else {
                    setcookie('bab_debug', $_GET['debug'], time() + 31536000); // 1 year
                }
            }
        }

        if ('Y' === $site['change_unavailability']) {
            // les retirer le cache de l'approbation si les parametre d'indisponibilite sont actif
            if (isset($_SESSION['bab_waitingApprobations'][$id_user])) {
                unset($_SESSION['bab_waitingApprobations'][$id_user]);
            }
        }
    }

    // verify skin validity
    include_once dirname(__FILE__) . '/skinincl.php';
    $objSkin = new bab_skin($GLOBALS['babSkin']);
    if (! $objSkin->isAccessValid()) {
        $GLOBALS['babSkin'] = bab_skin::getDefaultSkin()->getName();
    }

    if (bab_isUserLogged() || ! defined('BAB_DISABLE_ANONYMOUS_LOG') || 0 == BAB_DISABLE_ANONYMOUS_LOG) {
        bab_UsersLog::update();
    }
}





/**
 * Change the bab_users_log table
 * mandatory for logged in users
 * if not used for logged out users, article draft will not work for anonymous
 *
 */
class bab_UsersLog
{

    /**
     * Get row in user log for current user
     *
     * @return array | false
     */
    public static function getCurrentRow()
    {
        global $babDB;

        static $row = null;

        if (! isset($row)) {
            $query = "select id, id_dg, id_user, cpw, sessid, remote_addr, grp_change, schi_change from " . BAB_USERS_LOG_TBL . " where sessid='" . $babDB->db_escape_string(session_id()) . "'";

            if (bab_isUserLogged()) {
                $query .= ' OR (id_user=' . $babDB->quote(bab_getUserId()) . ' AND sessid<>' . $babDB->quote(session_id()) . ') ORDER BY dateact DESC';
            }

            $res = $babDB->db_query($query);
            if ($res && $babDB->db_num_rows($res) > 0) {
                $row = $babDB->db_fetch_assoc($res);
            } else {
                $row = false;
            }
        }

        return $row;
    }



    /**
     * Chech remote addr, grp_change, schi_change
     * cleanup session cache if necessary
     */
    public static function check()
    {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
        } else {
            $REMOTE_ADDR = '0.0.0.0';
        }

        if (session_id() && (bab_rp('tg') !== 'version' || bab_rp('idx') !== 'upgrade')) {

            $arr = bab_UsersLog::getCurrentRow();
            if ($arr) {
                if ((isset($GLOBALS['babCheckIpAddress']) && $GLOBALS['babCheckIpAddress'] === true) && $arr['remote_addr'] != $REMOTE_ADDR) {
                    die(bab_translate("Access denied, your session id has been created by another ip address than yours"));
                }

                if (1 == $arr['grp_change'] && isset($_SESSION['bab_groupAccess'])) {
                    unset($_SESSION['bab_groupAccess']);
                }

                if (1 == $arr['schi_change'] && isset($_SESSION['bab_waitingApprobations'])) {
                    unset($_SESSION['bab_waitingApprobations']);
                }
            }
        }
    }




    /**
     * Update an insert row
     * check for multiple connexion with same account if configured in site : auth_multi_session
     */
    public static function update()
    {
        global $babDB, $babBody, $BAB_SESS_USERID;

        $HTTP_X_FORWARDED_FOR = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '0.0.0.0';
        $REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

        $arr = self::getCurrentRow();
        if ($arr) {
            if ($arr['sessid'] == session_id()) {
                bab_setUserPasswordVariable($arr['id'], $arr['cpw'], $arr['id_user']);

                require_once dirname(__FILE__) . '/delegincl.php';
                $delegation = bab_getInstance('bab_currentDelegation');
                if ($arr['id_dg'] != '0') {
                    $delegation->set($arr['id_dg']);
                }

                $babDB->db_query("update " . BAB_USERS_LOG_TBL . " set
                        dateact=now(),
                        remote_addr=" . $babDB->quote($REMOTE_ADDR) . ",
                        forwarded_for=" . $babDB->quote($HTTP_X_FORWARDED_FOR) . ",
                        id_dg='" . $babDB->db_escape_string(bab_getCurrentAdmGroup()) . "',
                        grp_change=NULL,
                        schi_change=NULL,
                        tg='" . $babDB->db_escape_string(bab_rp('tg')) . "'
                        where
                        id = '" . $babDB->db_escape_string($arr['id']) . "'
                        ");
            } elseif (0 === (int) $babBody->babsite['auth_multi_session']) {
                // another session exists for the same user ID (first is the newest)
                // we want to stay with the newest session so the current session must be disconnected

                require_once dirname(__FILE__) . '/loginIncl.php';
                bab_logout(false);
                $babBody->addError(bab_translate('You will be disconnected because another user has logged in with your account'));
            }
        } else {
            if (! empty($BAB_SESS_USERID)) {
                $userid = $BAB_SESS_USERID;
            } else {
                $userid = 0;
            }

            $babDB->db_query("insert into " . BAB_USERS_LOG_TBL . " (id_user, sessid, dateact, remote_addr, forwarded_for, id_dg, grp_change, schi_change, tg)
                values ('" . $babDB->db_escape_string($userid) . "', '" . session_id() . "', now(), '" . $babDB->db_escape_string($REMOTE_ADDR) . "', '" . $babDB->db_escape_string($HTTP_X_FORWARDED_FOR) . "', '" . $babDB->db_escape_string(bab_getCurrentAdmGroup()) . "', NULL, NULL, '" . $babDB->db_escape_string(bab_rp('tg')) . "')");
        }
    }



    /**
     * Cleanup expired sessions from bab_users_log
     * cleanup associated article draft
     */
    public static function cleanup()
    {
        global $babDB;

        $maxlife = (int) get_cfg_var('session.gc_maxlifetime');
        if (0 === $maxlife) {
            $maxlife = 1440;
        }

        $res = $babDB->db_query("select id from " . BAB_USERS_LOG_TBL . " WHERE (UNIX_TIMESTAMP(dateact) + " . $babDB->quote($maxlife) . ") < UNIX_TIMESTAMP()");
        while ($row = $babDB->db_fetch_array($res)) {
            $res2 = $babDB->db_query("select id from " . BAB_ART_DRAFTS_TBL . " where id_author='0' and id_anonymous='" . $babDB->db_escape_string($row['id']) . "'");
            while ($arr = $babDB->db_fetch_array($res2)) {
                bab_deleteArticleDraft($arr['id']);
            }
            $babDB->db_query("delete from " . BAB_USERS_LOG_TBL . " where id='" . $babDB->db_escape_string($row['id']) . "'");
        }
    }
}


/**
 * Get the ID of the current admin delegation.
 *
 * This function remplaces the $babBody->currentAdmGroup variable.
 *
 * @return int
 */
function bab_getCurrentAdmGroup()
{
    require_once dirname(__FILE__) . '/delegincl.php';
    $delegation = bab_getInstance('bab_currentDelegation');
    /*@var $delegation bab_currentDelegation */
    return $delegation->getCurrentAdmGroup();
}


/**
 * Returns an array with all information about the delegation.
 *
 * This method replace the $babBody->currentDGGroup variable
 *
 * @return array
 */
function bab_getCurrentDGGroup()
{
    require_once dirname(__FILE__) . '/delegincl.php';
    $delegation = bab_getInstance('bab_currentDelegation');
    /*@var $delegation bab_currentDelegation */
    return $delegation->getCurrentDGGroup();
}


/**
 * Test if the functionality is delegated in the current delegation
 * @param string $functionname
 * @return bool
 */
function bab_isDelegated($functionname)
{
    $arr = bab_getCurrentDGGroup();

    if (! isset($arr[$functionname])) {
        return false;
    }

    return ('Y' === $arr[$functionname]);
}




/**
 * Set a global variable with user password if the mcrypt mode is activated
 * this work if $babEncryptionKey is set in config.php
 * @param	int		$id		bab_user_log ID
 * @param 	string 	$cpw	encrypted password
 * @param	int		$id_user
 * @return void
 */
function bab_setUserPasswordVariable($id, $cpw, $id_user)
{
    global $BAB_SESS_USERID;
    if (! empty($cpw) && isset($GLOBALS['babEncryptionKey']) && ! isset($_REQUEST['babEncryptionKey']) && ! empty($GLOBALS['babEncryptionKey']) && ! empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $id_user) {
        $GLOBALS['babUserPassword'] = bab_decrypt($cpw, md5($id . session_id() . $BAB_SESS_USERID . $GLOBALS['babEncryptionKey']));
    }
}




/**
 * Get version stored in database or NULL if Ovidentia is not installed correctely
 *
 * @return string|null
 */
function bab_getDbVersion()
{
    static $dbVersion = false;

    if (false === $dbVersion) {
        global $babDB;
        $dbver = array();
        $res = $babDB->db_query("select foption, fvalue from " . BAB_INI_TBL . " ");
        if (3 <= $babDB->db_num_rows($res)) {
            while ($rr = $babDB->db_fetch_array($res)) {
                $dbver[$rr['foption']] = $rr['fvalue'];
            }

            $dbVersion = $dbver['ver_major'] . "." . $dbver['ver_minor'] . "." . $dbver['ver_build'];

            if (isset($dbver['ver_nightly']) && '0' != $dbver['ver_nightly']) {
                $dbVersion .= '.' . $dbver['ver_nightly'];
            }
        } else {
            $dbVersion = NULL;
        }
    }

    return $dbVersion;
}



/**
 * Get ovidentia version from ini file
 *
 * @return string
 */
function bab_getIniVersion()
{
    $arr = parse_ini_file($GLOBALS['babInstallPath'] . 'version.inc');

    return $arr['version'];
}



/**
 * Get skin path
 *
 * @return string
 */
function bab_getSkinPath()
{
    return bab_getStaticUrl() . $GLOBALS['babInstallPath'] . "skins/ovidentia/";
}




/**
 *
 * @param string $relativeFilepath      A path relative to the skin path or to the kernel's skin path.
 * @param string $filename
 */
function bab_getSkinnableFile($relativeFilepath)
{
    $filepath = 'skins/' . $GLOBALS['babSkin'] . '/' . $relativeFilepath;
    if (! file_exists($filepath)) {
        $filepath = $GLOBALS['babSkinPath'] . '/' . $relativeFilepath;
    }
    if (! file_exists($filepath)) {
        return null;
    }

    return $filepath;
}




/**
 * Returns the path to a template file in the kernel's template path or in
 * the current skin's template path if it was overwritten there.
 *
 * @param string $filename
 * @return string The path to the template file.
 */
function bab_getSkinnableTemplate($filename)
{
    return bab_getSkinnableFile('templates/' . $filename);
}




/**
 * Returns the path to an ovml file in the kernel's ovml path or in
 * the current skin's ovml path if it was overwritten there.
 *
 * @param string	$filename
 * @return string	The path to the ovml file.
 */
function bab_getSkinnableOvml($filename)
{
    return bab_getSkinnableFile('ovml/' . $filename);
}





/**
 * Get the site settings and set globals variables : $babSkin, $babUploadPath...
 * This function is called from index.php
 */
function bab_updateSiteSettings()
{
    global $babDB;

    $babBody = bab_getInstance('babBody');

    require_once dirname(__FILE__) . '/settings.class.php';

    $settings = bab_getInstance('bab_Settings');
    /*@var $settings bab_Settings */

    try {
        $arr = $settings->getSiteSettings();
    } catch (ErrorException $e) {
        $babBody->addError($e->getMessage());
        return;
    }

    $babBody->babsite = $arr;

    $GLOBALS['babSkin'] = $arr['skin'];

    if ($arr['style'] != '') {
        $GLOBALS['babStyle'] = $arr['style'];
    } else {
        $GLOBALS['babStyle'] = 'ovidentia.css';
    }

    // set langage in session if not allready set
    // will not be necessary if all code use bab_getLanguage() instead of $GLOBALS['babLanguage']
    require_once dirname(__FILE__) . '/session.class.php';
    $session = bab_getInstance('bab_Session');
    if (! isset($session->babLanguage)) {
        bab_setLanguage(bab_getLanguage());
    } else {
        $GLOBALS['babLanguage'] = bab_getLanguage();
    }


    if ($arr['adminemail'] != '') {
        $GLOBALS['babAdminEmail'] = $arr['adminemail'];
    } else {
        $GLOBALS['babAdminEmail'] = 'admin@your-domain.com';
    }
    if ($arr['langfilter'] != '') {
        bab_getInstance('babLanguageFilter')->setFilter($arr['langfilter']);
    } else {
        bab_getInstance('babLanguageFilter')->setFilter(0);
    }
    // options bloc2
    if (! empty($arr['total_diskspace'])) {
        $GLOBALS['babMaxTotalSize'] = $arr['total_diskspace'] * 1048576;
    } else {
        $GLOBALS['babMaxTotalSize'] = '200000000';
    }
    if (! empty($arr['user_diskspace'])) {
        $GLOBALS['babMaxUserSize'] = $arr['user_diskspace'] * 1048576;
    } else {
        $GLOBALS['babMaxUserSize'] = '30000000';
    }
    if (! empty($arr['folder_diskspace'])) {
        $GLOBALS['babMaxGroupSize'] = $arr['folder_diskspace'] * 1048576;
    } else {
        $GLOBALS['babMaxGroupSize'] = '50000000';
    }

    if (! empty($arr['imgsize'])) {
        $GLOBALS['babMaxImgFileSize'] = $arr['imgsize'] * 1024;
    } else {
        $GLOBALS['babMaxImgFileSize'] = 0;
    }


    if (! empty($arr['maxfilesize'])) {
        $GLOBALS['babMaxFileSize'] = $arr['maxfilesize'] * 1048576;
    } else {
        include_once $GLOBALS['babInstallPath'] . 'utilit/inifileincl.php';
        $GLOBALS['babMaxFileSize'] = bab_inifile_requirements::getIniMaxUpload();
    }
    if (! empty($arr['maxzipsize']) && $arr['maxzipsize'] < $GLOBALS['babMaxFileSize']) {
        $GLOBALS['babMaxZipSize'] = $arr['maxzipsize'] * 1048576;
    } else {
        $GLOBALS['babMaxZipSize'] = $GLOBALS['babMaxFileSize'];
    }

    $GLOBALS['babQuotaFM'] = $arr['quota_total'];
    $GLOBALS['babQuotaFolder'] = $arr['quota_folder'];
    $GLOBALS['babUploadPath'] = $settings->getUploadPath();


    if ($arr['babslogan'] != '') {
        $GLOBALS['babSlogan'] = $arr['babslogan'];
    } else {
        $GLOBALS['babSlogan'] = '';
    }
    if ($arr['name_order'] != '') {
        $babBody->nameorder = explode(' ', $arr['name_order']);
    } else {
        $babBody->nameorder = Array(
            'F',
            'L'
        );
    }
    if ($arr['remember_login'] == 'Y') {
        $GLOBALS['babCookieIdent'] = true;
    } elseif ($arr['remember_login'] == 'L') {
        $GLOBALS['babCookieIdent'] = 'login';
    } else {
        $GLOBALS['babCookieIdent'] = false;
    }
    if ($arr['email_password'] == 'Y') {
        $GLOBALS['babEmailPassword'] = true;
    } else {
        $GLOBALS['babEmailPassword'] = false;
    }

    $GLOBALS['babAdminName'] = $arr['adminname'];

    if ($arr['date_shortformat'] == '') {
        $GLOBALS['babShortDateFormat'] = bab_getDateFormat('dd/mm/yyyy');
    } else {
        $GLOBALS['babShortDateFormat'] = bab_getDateFormat($arr['date_shortformat']);
    }

    if ($arr['date_longformat'] == '') {
        $GLOBALS['babLongDateFormat'] = bab_getDateFormat('ddd dd mmmm yyyy');
    } else {
        $GLOBALS['babLongDateFormat'] = bab_getDateFormat($arr['date_longformat']);
    }

    if ($arr['time_format'] == '') {
        $GLOBALS['babTimeFormat'] = bab_getTimeFormat('HH:mm');
    } else {
        $GLOBALS['babTimeFormat'] = bab_getTimeFormat($arr['time_format']);
    }

    if ($arr['authentification'] == 1) {
        // LDAP authentification
        $babBody->babsite['registration'] = 'N';
        $babBody->babsite['change_nickname'] = 'N';
    }


    if (NULL === bab_getDbVersion()) {
        include_once $GLOBALS['babInstallPath'] . 'utilit/upgradeincl.php';
        bab_newInstall();
    }

    bab_UsersLog::cleanup();

    $babDB->db_query('LOCK TABLE ' . BAB_ART_DRAFTS_TBL . ' WRITE');

    $res = $babDB->db_query("select id,id_author, id_topic, id_article, date_submission from " . BAB_ART_DRAFTS_TBL . " where result='" . BAB_ART_STATUS_DRAFT . "' and date_submission <= now() and date_submission !='0000-00-00 00:00:00'");
    $drafts = array();
    while ($arr = $babDB->db_fetch_array($res)) {
        $drafts[$arr['id']] = $arr;
    }

    if ($drafts) {
        $babDB->db_query("UPDATE " . BAB_ART_DRAFTS_TBL . " SET date_submission='0000-00-00 00:00:00' WHERE id IN(" . $babDB->quote(array_keys($drafts)) . ")");
    }

    $babDB->db_query('UNLOCK TABLES');

    if ($drafts) {
        include_once $GLOBALS['babInstallPath'] . 'utilit/topincl.php';
        include_once $GLOBALS['babInstallPath'] . 'utilit/artincl.php';
        foreach ($drafts as $arr) {

            if ($arr['id_article'] != 0) {
                $res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate from " . BAB_ARTICLES_TBL . " at left join " . BAB_TOPICS_TBL . " tt on at.id_topic=tt.id  where at.id='" . $babDB->db_escape_string($arr['id_article']) . "'");
                if ($res && $babDB->db_num_rows($res) == 1) {
                    $rr = $babDB->db_fetch_array($res);
                    if (($rr['allow_update'] != '0' && $rr['id_author'] == $arr['id_author']) || bab_isAccessValidByUser(BAB_TOPICSMOD_GROUPS_TBL, $rr['id_topic'], $arr['id_author']) || ($rr['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $arr['id_author']))) {
                        bab_submitArticleDraft($arr['id']);
                        continue;
                    }
                }
            }

            if ($arr['id_topic'] != 0 && bab_isAccessValidByUser(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic'], $arr['id_author'])) {
                bab_submitArticleDraft($arr['id']);
            }
        }
    }

    $res = $babDB->db_query("select id from " . BAB_ARTICLES_TBL . " where date_archiving <= now() and date_archiving !='0000-00-00 00:00:00' and archive='N'");
    while ($arr = $babDB->db_fetch_array($res)) {
        $babDB->db_query("update " . BAB_ARTICLES_TBL . " set archive='Y' where id = '" . $babDB->db_escape_string($arr['id']) . "'");
    }
}



class babLanguageFilter
{
    public $langFilterNames;

    public $activeLanguageFilter;

    public $activeLanguageValues;

    public function __construct()
    {
        $this->setFilter(0);

        $this->langFilterNames = array(
            bab_translate("No filter"),
            bab_translate("Filter language"),
            bab_translate("Filter language and country")
        );
    }

    public function setFilter($filterInt)
    {
        $this->activeLanguageValues = array();
        switch ($filterInt) {
            case 2:
                $this->activeLanguageValues[] = '\'*\'';
                $this->activeLanguageValues[] = '\'\'';
                break;
            case 1:
                $this->activeLanguageValues[] = '\'' . mb_substr($GLOBALS['babLanguage'], 0, 2) . '\'';
                $this->activeLanguageValues[] = '\'*\'';
                $this->activeLanguageValues[] = '\'\'';
                break;
            case 0:
            default:
                break;
        }
        $this->activeLanguageFilter = $filterInt;
    }

    public function getFilterAsInt()
    {
        return $this->activeLanguageFilter;
    }

    public function getFilterAsStr()
    {
        return $this->langFilterNames[$this->activeLanguageFilter];
    }

    public function convertFilterToStr($filterInt)
    {
        return $this->langFilterNames[$filterInt];
    }

    public function convertFilterToInt($filterStr)
    {
        $i = 0;
        while ($i < count($this->langFilterNames)) {
            if ($this->langFilterNames[$i] == $filterStr) {
                return $i;
            }
            $i ++;
        }
        return 0;
    }

    public function countFilters()
    {
        return count($this->langFilterNames);
    }

    public function getFilterStr($i)
    {
        return $this->langFilterNames[$i];
    }

    public function isLangFile($fileName)
    {
        $res = mb_substr($fileName, 0, 5);
        if ($res != 'lang-') {
            return false;
        }

        $iOffset = mb_strpos($fileName, '.');
        if (false === $iOffset) {
            return false;
        }

        $iOffset = mb_strpos($fileName, '.');
        if (false === $iOffset) {
            return false;
        }

        $sFileExtention = mb_strtolower(mb_substr($fileName, $iOffset));

        if ($sFileExtention != '.xml') {
            return false;
        }

        return true;
    }


    public function getLangCode($file)
    {
        $langCode = mb_substr($file, 5);
        return mb_substr($langCode, 0, mb_strlen($langCode) - 4);
    }


    public function readLangFiles()
    {
        $tmpLangFiles = array();
        $i = 0;
        if (file_exists($GLOBALS['babInstallPath'] . 'lang')) {
            $folder = opendir($GLOBALS['babInstallPath'] . 'lang');
            while (false !== ($file = readdir($folder))) {
                if ($this->isLangFile($file)) {
                    $tmpLangFiles[$i] = $this->getLangCode($file);
                    $i ++;
                }
            }
            closedir($folder);
        }
        if (file_exists('lang')) {
            $folder = opendir('lang');
            while (false !== ($file = readdir($folder))) {
                if ($this->isLangFile($file)) {
                    $tmpLangFiles[$i] = $this->getLangCode($file);
                    $i ++;
                }
            }
            closedir($folder);
        }
        $tmpLangFiles[] = '*';
        bab_sort::sort($tmpLangFiles);
        $this->langFiles = array();
        $i = 0;
        $tmpLangFiles[- 1] = '';
        while ($i < count($tmpLangFiles) - 1) {
            if ($tmpLangFiles[$i] != $tmpLangFiles[$i - 1]) {
                $this->langFiles[] = $tmpLangFiles[$i];
            }
            $i ++;
        }
    }

    public function getLangFiles()
    {
        static $callNbr = 0;
        if ($callNbr == 0) {
            $this->readLangFiles();
            $callNbr ++;
        }
        return $this->langFiles;
    }

    public function getLangValues()
    {
        return $this->activeLanguageValues;
    }
}




/**
 * Display page not found
 */
function bab_pageNotFound()
{
    $event = new bab_eventPageNotFound();
    bab_fireEvent($event);

    $babBody = bab_getBody();

    header('HTTP/1.0 404 Not Found');
    $babBody->addError(bab_translate('This page does not exists'));
    $babBody->babpopup(bab_printOvmlTemplate('404.html'));
}
