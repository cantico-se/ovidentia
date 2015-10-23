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
 * page body
 */
class babBody
{

    /**
     *
     * @var array
     */
    public $sections = array();

    /**
     *
     * @var babMenu
     */
    public $menu;

    /**
     * Messages to display on page
     * 
     * @see babBody::addMessage();
     * @var unknown_type
     */
    public $messages = array();

    /**
     * Error message as html
     * 
     * @access public
     */
    public $msgerror;

    /**
     * List of errors as text
     * 
     * @see babBody::addError()
     * @access public
     */
    public $errors = array();

    /**
     *
     * @var string
     */
    public $content;

    /**
     * Page title text
     * 
     * @var string
     */
    public $raw_title;

    /**
     * Page title
     * HTML
     */
    public $title;

    /**
     *
     * @var string
     */
    public $message;

    /**
     *
     * @var string
     */
    public $script;

    /**
     *
     * @deprecated use bab_Settings instead
     * @var array
     */
    public $babsite;

    /**
     * List of stylesheets
     * paths are relative to the site root folder
     *
     * @var array
     */
    public $styleSheet = array();

    public function __construct()
    {
        global $babDB;
        require_once dirname(__FILE__) . '/menu.class.php';
        $this->menu = new babMenu();
        $this->message = '';
        $this->script = '';
        $this->title = '';
        $this->msgerror = '';
        $this->content = '';
        $this->saarray = array();
        $this->babaddons = array();
        
        require_once dirname(__FILE__) . '/session.class.php';
        $session = bab_getInstance('bab_Session');
        if (isset($session->bab_page_messages)) {
            foreach ($session->bab_page_messages as $message) {
                $this->addMessage($message);
            }
            unset($session->bab_page_messages);
        }
        
        if (isset($session->bab_page_errors)) {
            foreach ($session->bab_page_errors as $error) {
                $this->addError($error);
            }
            unset($session->bab_page_errors);
        }
    }

    /**
     */
    public function __isset($propertyName)
    {
        switch ($propertyName) {
            case 'isSuperAdmin':
            case 'lastlog':
            case 'currentAdmGroup':
            case 'currentDGGroup':
                return true;
            
            default:
                return false;
        }
    }

    public function __get($propertyName)
    {
        switch ($propertyName) {
            case 'isSuperAdmin':
                trigger_error('babBody->isSuperAdmin is deprecated, please use bab_isUserAdministrator() instead');
                return bab_isUserAdministrator();
            
            case 'lastlog':
                trigger_error('babBody->lastlog is deprecated, please use bab_userInfos::getUserSettings() instead');
                if (bab_isUserLogged()) {
                    require_once dirname(__FILE__) . '/userinfosincl.php';
                    $usersettings = bab_userInfos::getUserSettings();
                    return $usersettings['lastlog'];
                }
                return '';
            
            case 'currentAdmGroup':
                trigger_error('babBody->currentAdmGroup is deprecated, please use bab_getCurrentAdmGroup() instead');
                return bab_getCurrentAdmGroup();
            
            case 'currentDGGroup':
                trigger_error('babBody->currentDGGroup is deprecated, please use bab_getCurrentDGGroup() instead');
                return bab_getCurrentDGGroup();
        }
    }

    public function resetContent()
    {
        $this->content = '';
    }

    public function babecho($txt)
    {
        $this->content .= $txt;
    }

    /**
     * Set page title with a text string (no html)
     * 
     * @param string $title            
     */
    public function setTitle($title)
    {
        $this->raw_title = $title;
        $this->title = bab_toHtml($title);
    }

    /**
     * Add text message to page
     * 
     * @param string $message            
     */
    public function addMessage($message)
    {
        $this->messages[] = $message;
        return $this;
    }

    /**
     * Add message to display in next page
     * 
     * @param unknown_type $message            
     */
    public function addNextPageMessage($message)
    {
        $session = bab_getInstance('bab_Session');
        if (! isset($session->bab_page_messages)) {
            $session->bab_page_messages = array();
        }
        $messages = $session->bab_page_messages;
        $messages[] = $message;
        $session->bab_page_messages = $messages;
        
        return $this;
    }

    /**
     * Add error to display in next page
     * 
     * @param string $message            
     */
    public function addNextPageError($message)
    {
        $session = bab_getInstance('bab_Session');
        if (! isset($session->bab_page_errors)) {
            $session->bab_page_errors = array();
        }
        $messages = $session->bab_page_errors;
        $messages[] = $message;
        $session->bab_page_errors = $messages;
        
        return $this;
    }

    /**
     * Add error message
     * 
     * @param string $title            
     */
    public function addError($error)
    {
        $this->errors[] = $error;
        if (empty($this->msgerror)) {
            $this->msgerror = bab_toHtml($error);
        } else {
            $this->msgerror .= '<br /> ' . bab_toHtml($error);
        }
    }

    /**
     * View as popup
     * 
     * @param string $txt            
     */
    public function babpopup($txt)
    {
        include_once $GLOBALS['babInstallPath'] . 'utilit/uiutil.php';
        $GLOBALS['babBodyPopup'] = new babBodyPopup();
        $GLOBALS['babBodyPopup']->menu = & $GLOBALS['babBody']->menu;
        $GLOBALS['babBodyPopup']->styleSheet = & $GLOBALS['babBody']->styleSheet;
        $GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
        $GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
        $GLOBALS['babBody']->babecho($txt);
        $GLOBALS['babBodyPopup']->babecho($GLOBALS['babBody']->content);
        printBabBodyPopup();
        die();
    }

    public function loadSections()
    {
        include_once $GLOBALS['babInstallPath'] . 'utilit/utilitsections.php';
        
        global $babDB, $babBody, $BAB_SESS_LOGGED, $BAB_SESS_USERID;
        $babSectionsType = isset($_SESSION['babSectionsType']) ? $_SESSION['babSectionsType'] : BAB_SECTIONS_ALL;
        $req = "SELECT " . BAB_SECTIONS_ORDER_TBL . ".*, " . BAB_SECTIONS_STATES_TBL . ".closed, " . BAB_SECTIONS_STATES_TBL . ".hidden, " . BAB_SECTIONS_STATES_TBL . ".id_section AS states_id_section FROM " . BAB_SECTIONS_ORDER_TBL . " LEFT JOIN " . BAB_SECTIONS_STATES_TBL . " ON " . BAB_SECTIONS_STATES_TBL . ".id_section=" . BAB_SECTIONS_ORDER_TBL . ".id_section AND " . BAB_SECTIONS_STATES_TBL . ".type=" . BAB_SECTIONS_ORDER_TBL . ".type AND " . BAB_SECTIONS_STATES_TBL . ".id_user='" . $babDB->db_escape_string($BAB_SESS_USERID) . "' ORDER BY " . BAB_SECTIONS_ORDER_TBL . ".ordering ASC";
        $res = $babDB->db_query($req);
        $arrsections = array();
        $arrsectionsinfo = array();
        $arrsectionsbytype = array();
        $arrsectionsorder = array();
        
        while ($arr = $babDB->db_fetch_array($res)) {
            $objectid = $arr['id'];
            
            $arrsectioninfo = array(
                'close' => 0,
                'bshow' => false
            );
            $typeid = $arr['type'];
            $sectionid = $arr['id_section'];
            
            if (isset($arr['states_id_section']) && ! empty($arr['states_id_section'])) {
                if ($arr['closed'] == 'Y') {
                    $arrsectioninfo['close'] = 1;
                }
                if ($arr['hidden'] == 'N') {
                    $arrsectioninfo['bshow'] = true;
                }
            }
            
            if ($typeid == 1 || $typeid == 3 || $typeid == 4) {
                $arrsectionsbytype[$typeid][$sectionid] = $objectid;
                $arrsectioninfo['type'] = $typeid;
            } else {
                $arrsectionsbytype['users'][$sectionid] = $objectid;
                $arrsectioninfo['type'] = $typeid;
            }
            
            $arrsectioninfo['position'] = $arr['position'];
            $arrsectioninfo['sectionid'] = $sectionid;
            $arrsectionsinfo[$objectid] = $arrsectioninfo;
            
            $arrsectionsorder[] = $objectid;
        }
        
        // BAB_PRIVATE_SECTIONS_TBL
        
        $type = 1;
        if (! empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_CORE)) {
            $res2 = $babDB->db_query("select * from " . BAB_PRIVATE_SECTIONS_TBL . " where id IN(" . $babDB->quote(array_keys($arrsectionsbytype[$type])) . ")");
            while ($arr2 = $babDB->db_fetch_array($res2)) {
                $arrdbinfo[$arr2['id']] = $arr2;
            }
            foreach ($arrsectionsbytype[$type] as $sectionid => $objectid) {
                $arr2 = $arrdbinfo[$sectionid];
                $arrsectioninfo = $arrsectionsinfo[$objectid];
                
                switch ($sectionid) {
                    case 1: // admin
                        if (isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0)) {
                            $sec = new babAdminSection($arrsectioninfo['close']);
                            $arrsections[$objectid] = $sec;
                        }
                        break;
                    case 2: // month
                        if ($arr2['enabled'] == 'Y' && ($arr2['optional'] == 'N' || $arrsectioninfo['bshow'])) {
                            $sec = new babMonthA();
                            $arrsections[$objectid] = $sec;
                        }
                        break;
                    case 3: // topics
                        $sec = new babTopcatSection($arrsectioninfo['close']);
                        if ($sec->count > 0) {
                            if ($arr2['enabled'] == 'Y' && ($arr2['optional'] == 'N' || $arrsectioninfo['bshow'])) {
                                $arrsections[$objectid] = $sec;
                            }
                        }
                        break;
                    case 4: // Forums
                        $sec = new babForumsSection($arrsectioninfo['close']);
                        if ($sec->count > 0) {
                            if ($arr2['enabled'] == 'Y' && ($arr2['optional'] == 'N' || $arrsectioninfo['bshow'])) {
                                $arrsections[$objectid] = $sec;
                            }
                        }
                        break;
                    case 5: // user's section
                        if ($arr2['enabled'] == 'Y') {
                            $sec = new babUserSection($arrsectioninfo['close']);
                            $arrsections[$objectid] = $sec;
                        }
                        break;
                }
            }
        }
        
        // BAB_TOPICS_CATEGORIES_TBL sections
        $type = '3';
        if (! empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_ARTICLES)) {
            if (isset($_SESSION['babOvmlCurrentDelegation']) && $_SESSION['babOvmlCurrentDelegation'] !== '') {
                $req = "select id, enabled, optional from " . BAB_TOPICS_CATEGORIES_TBL . " where id IN(" . $babDB->quote(array_keys($arrsectionsbytype[$type])) . ") and id_dgowner='" . $babDB->db_escape_string($_SESSION['babOvmlCurrentDelegation']) . "'";
            } else {
                $req = "select id, enabled, optional from " . BAB_TOPICS_CATEGORIES_TBL . " where id IN(" . $babDB->quote(array_keys($arrsectionsbytype[$type])) . ")";
            }
            $res2 = $babDB->db_query($req);
            while ($arr2 = $babDB->db_fetch_array($res2)) {
                $sectionid = $arr2['id'];
                $objectid = $arrsectionsbytype[$type][$sectionid];
                if ($arr2['enabled'] == 'Y' && ($arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow'])) {
                    $sec = new babTopicsSection($sectionid, $arrsectionsinfo[$objectid]['close']);
                    if ($sec->count > 0) {
                        $arrsections[$objectid] = $sec;
                    }
                }
            }
        }
        
        // BAB_ADDONS_TBL sections
        $type = '4';
        if (! empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_ADDONS)) {
            $at = array_keys($arrsectionsbytype[$type]);
            for ($i = 0; $i < count($at); $i ++) {
                if ($arr2 = bab_addonsInfos::getRow($at[$i])) {
                    $sectionid = $arr2['id'];
                    $objectid = $arrsectionsbytype[$type][$sectionid];
                    
                    if ($arr2['access'] && is_file($GLOBALS['babInstallPath'] . 'addons/' . $arr2['title'] . '/init.php')) {
                        bab_setAddonGlobals($arr2['id']);
                        
                        require_once ($GLOBALS['babInstallPath'] . 'addons/' . $arr2['title'] . '/init.php');
                        $func = $arr2['title'] . '_onSectionCreate';
                        if (function_exists($func)) {
                            if (! isset($template))
                                $template = false;
                            $stitle = '';
                            $scontent = '';
                            if ($func($stitle, $scontent, $template)) {
                                if (! $arrsectionsinfo[$objectid]['close']) {
                                    $sec = new babSection($stitle, $scontent);
                                    $sec->setTemplate($template);
                                } else {
                                    $sec = new babSection($stitle, '');
                                }
                                $sec->setTemplate($arr2['title']);
                                $sec->htmlid = $arr2['title'];
                                $arrsections[$objectid] = $sec;
                            }
                        }
                    }
                }
            }
        }
        
        // personnalized sections
        $type = 'users';
        if (! empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_SITE)) {
            $langFilterValues = bab_getInstance('babLanguageFilter')->getLangValues();
            $req = "SELECT * FROM " . BAB_SECTIONS_TBL . " WHERE id IN(" . $babDB->quote(array_keys($arrsectionsbytype[$type])) . ") and enabled='Y'";
            if (count($langFilterValues) > 0) {
                $req .= " AND SUBSTRING(lang, 1, 2 ) IN ('*'," . implode(',', $langFilterValues) . ")"; // $langFilterValues is already escaped
            }
            if (isset($_SESSION['babOvmlCurrentDelegation']) && $_SESSION['babOvmlCurrentDelegation'] !== '') {
                $req .= " and id_dgowner='" . $babDB->db_escape_string($_SESSION['babOvmlCurrentDelegation']) . "'";
            }
            $res2 = $babDB->db_query($req);
            while ($arr2 = $babDB->db_fetch_array($res2)) {
                $sectionid = $arr2['id'];
                $objectid = $arrsectionsbytype[$type][$sectionid];
                if (bab_isAccessValid(BAB_SECTIONS_GROUPS_TBL, $sectionid)) {
                    if ($arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow']) {
                        if (! $arrsectionsinfo[$objectid]['close']) {
                            if ($arr2['script'] == 'Y') {
                                eval("\$arr2['content'] = \"" . $arr2['content'] . "\";");
                            } else {
                                include_once $GLOBALS['babInstallPath'] . 'utilit/editorincl.php';
                                $editor = new bab_contentEditor('bab_section');
                                $editor->setContent($arr2['content']);
                                $editor->setFormat($arr2['content_format']);
                                $arr2['content'] = $editor->getHtml();
                            }
                            $sec = new babSection($arr2['title'], $arr2['content']);
                        } else {
                            $sec = new babSection($arr2['title'], '');
                        }
                        $sec->setTemplate($arr2['template']);
                        $sec->htmlid = 'customsection';
                        $arrsections[$objectid] = $sec;
                    }
                }
            }
        }
        
        foreach ($arrsectionsorder as $objectid) {
            $sectionid = $arrsectionsinfo[$objectid]['sectionid'];
            $type = $arrsectionsinfo[$objectid]['type'];
            if (isset($arrsections[$objectid])) {
                $sec = $arrsections[$objectid];
                $sec->setPosition($arrsectionsinfo[$objectid]['position']);
                $sec->close = $arrsectionsinfo[$objectid]['close'];
                $sec->bbox = 1;
                if (empty($BAB_SESS_USERID)) {
                    $sec->bbox = 0;
                }
                if ($sec->close) {
                    $sec->boxurl = $GLOBALS['babUrlScript'] . '?tg=options&amp;idx=ob&amp;s=' . $sectionid . '&amp;w=' . $type;
                } else {
                    $sec->boxurl = $GLOBALS['babUrlScript'] . '?tg=options&amp;idx=cb&amp;s=' . $sectionid . '&amp;w=' . $type;
                }
                $babBody->addSection($sec);
            }
        }
    }

    public function addSection($sec)
    {
        array_push($this->sections, $sec);
    }

    public function showSection($title)
    {
        for ($i = 0; $i < count($this->sections); $i ++) {
            if (! strcmp($this->sections[$i]->getTitle(), $title)) {
                $this->sections[$i]->show();
            }
        }
    }

    public function hideSection($title)
    {
        for ($i = 0; $i < count($this->sections); $i ++) {
            if (! strcmp($this->sections[$i]->getTitle(), $title)) {
                $this->sections[$i]->hide();
            }
        }
    }

    public function addItemMenu($title, $txt, $url, $enabled = true)
    {
        $this->menu->addItem($title, $txt, $url, $enabled);
    }

    public function addItemMenuAttributes($title, $attr)
    {
        $this->menu->addItemAttributes($title, $attr);
    }

    public function setCurrentItemMenu($title, $enabled = false)
    {
        $this->menu->setCurrent($title, $enabled);
    }

    /**
     * Add a stylesheet to the page
     * 
     * @param string $filepath
     *            relative to the site root
     *            
     */
    public function addCssStyleSheet($filepath)
    {
        if (! in_array($filepath, $this->styleSheet)) {
            $this->styleSheet[] = $filepath;
        }
    }

    /**
     * Add a stylesheet to the page
     * 
     * @param string $filename
     *            relative to the site root only in allowed paths or relative to the styles/ folder
     * @return void
     */
    public function addStyleSheet($filename)
    {
        $allowedprefix = array(
            $GLOBALS['babInstallPath'] . 'styles/',
            'vendor/ovidentia'
        );
        
        foreach ($allowedprefix as $test) {
            $length = mb_strlen($test);
            if ($test === mb_substr($filename, 0, $length)) {
                return $this->addCssStyleSheet($filename);
            }
        }
        
        return $this->addCssStyleSheet($GLOBALS['babInstallPath'] . 'styles/' . $filename);
    }

    /**
     * Add a javscript file url to head
     *
     * @param string $file
     *            javascript file URL
     * @param bool $defer
     *            script loading
     */
    public function addJavascriptFile($file, $defer = false)
    {
        global $babOvidentiaJs;
        static $jfiles = array();
        
        if (! array_key_exists($file, $jfiles)) {
            $jfiles[$file] = 1;
            
            $defer_attribute = '';
            if ($defer) {
                $defer_attribute = ' defer="defer" ';
            }
            
            if ($GLOBALS['babInstallPath'] === mb_substr($file, 0, mb_strlen($GLOBALS['babInstallPath']))) {
                $file = bab_getStaticUrl() . $file;
            }
            $babOvidentiaJs .= '"></script>' . "\n\t" . '<script type="text/javascript" ' . $defer_attribute . ' src="' . $file;
        }
    }

    /**
     * Adds some javascript code to the current page.
     *
     * @param string $code            
     */
    public function addJavascript($code)
    {
        $this->script .= "\n" . $code;
    }

    /**
     * Template method for uiutil.html#styleSheet
     */
    public function getnextstylesheet()
    {
        if (list (, $csspath) = each($this->styleSheet)) {
            $this->file = bab_toHtml(bab_getStaticUrl() . $csspath);
            return true;
        }
        
        return false;
    }

    public function printout()
    {
        if (count($this->styleSheet) > 0 && false !== current($this->styleSheet)) {
            $this->content = bab_printTemplate($this, 'uiutil.html', 'styleSheet') . $this->content;
        }
        
        if (! empty($this->msgerror)) {
            $this->message = bab_printTemplate($this, 'warning.html', 'texterror');
            // return '';
        } else 
            if (! empty($this->title)) {
                $this->message = bab_printTemplate($this, 'warning.html', 'texttitle');
            }
        return $this->content;
    }

    /**
     *
     * @deprecated Use bab_getArticleCategories()
     */
    public function get_topcats()
    {
        require_once dirname(__FILE__) . '/artapi.php';
        trigger_error('deprecated : ' . __FUNCTION__);
        return bab_getArticleCategories();
    }

    /**
     *
     * @deprecated Use bab_getReadableArticleCategories()
     */
    public function get_topcatview()
    {
        require_once dirname(__FILE__) . '/artapi.php';
        trigger_error('deprecated : ' . __FUNCTION__);
        return bab_getReadableArticleCategories();
    }
}
