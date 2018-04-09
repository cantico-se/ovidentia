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
include_once 'base.php';

include_once $GLOBALS['babInstallPath'].'admin/acl.php';
include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/nwdaysincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/sitesincl.php';

$bab_ldapAttributes = array('uid', 'cn', 'sn', 'givenname', 'mail', 'telephonenumber', 'mobile', 'homephone', 'facsimiletelephonenumber', 'title', 'o', 'street', 'l', 'postalcode', 'st', 'homepostaladdress', 'jpegphoto', 'departmentnumber');




class site_configuration_cls
{
    var $menu;

    function site_configuration_cls($id_site = false)
    {
    global $babDB;
    $this->t_record = bab_translate("Record");
    $this->yes = bab_translate("Yes");
    $this->no = bab_translate("No");

    $this->item = $id_site;
    $this->menu = bab_getSitesConfigurationMenus();

    if (false !== $id_site)
        {
        $res = $babDB->db_query("SELECT *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass FROM ".BAB_SITES_TBL." WHERE id='".$babDB->db_escape_string($id_site)."'");
        $this->row = $babDB->db_fetch_assoc($res);

        bab_debug($this->row);

        $this->arr = array();
        foreach($this->row as $k => $val)
            {
            $this->arr[$k] = bab_toHtml($val);
            }

        if (isset($_REQUEST['idx']))
            {
            $key = (int) mb_substr($_REQUEST['idx'],4);
            if (isset($this->menu[$key]))
                $GLOBALS['babBody']->title = $this->menu[$key];
            }
        }
    else
        {
        $GLOBALS['babBody']->title = bab_translate("Create site");
        }
    }
}


function site_menu1()
{
    global $babBody;
    class temp extends site_configuration_cls
        {

        function temp()
            {
            $this->name = bab_translate("Site name");
            $this->description = bab_translate("Description");
            $this->lang = bab_translate("Lang");
            $this->t_lang_default = bab_translate("Determine automatically");

            $this->skin = bab_translate("Skin");
            $this->sitemap = bab_translate("Site map");
            $this->confirmation = bab_translate("Send email confirmation")."?";

            $this->stattxt = bab_translate("Enable statistics recording")."?";
            $this->disabled = bab_translate("Disabled");
            $this->imagessize = bab_translate("Max image size ( Kb )");
            $this->langfiltertxt = bab_translate("Language filter");
            $this->siteemail = bab_translate("Email site");
            $this->adminnametxt = bab_translate("Name to use for notification emails");
            $this->name_order_title = bab_translate("User name composition");
            $this->firstlast = bab_translate("Firstname")." ".bab_translate("Lastname");
            $this->lastfirst = bab_translate("Lastname")." ".bab_translate("Firstname");
            $this->babslogan_title = bab_translate("Site slogan");
            $this->maxemailtxt = bab_translate("Max number of recipients by notification email");
            $this->mailfieldaddresstxt = bab_translate("Field to use when sending notification email");
            $this->proposemassmailingtxt = bab_translate("Propose mass mailing in directory views?");
            $this->staticurl_title = sprintf(bab_translate("Base url to use for static content (default is %s)"), $GLOBALS['babUrl']);

            $this->sSiteMapInfo = bab_translate("The chosen site map will be use to fill menus.");

            $this->skselectedindex = 0;
            $this->stselectedindex = 0;


            if (empty($_REQUEST['item']))
                {
                $this->site_configuration_cls();
                $this->row = $this->arr = array(
                        'name'			=> '',
                        'description'	=> '',
                        'lang'			=> '',
                        'adminname'		=> '',
                        'adminemail'	=> '',
                        'skin'			=> '',
                        'sitemap'		=> 'core',
                        'style'			=> '',
                        'babslogan'		=> '',
                        'langfilter'	=> '',
                        'mail_maxperpacket'	=> '25',
                        'mail_fieldaddress'	=> 'Bcc',
                        'mass_mailing' => 'N',
                        'staticurl'		=> ''
                    );

                $this->item = '';
                }
            else
                {
                $this->site_configuration_cls($_REQUEST['item']);
                }

            $this->bccselected = '';
            $this->toselected = '';
            $this->ccselected = '';
            switch( $this->row['mail_fieldaddress'] )
                {
                case 'Bcc': $this->bccselected = 'selected'; break;
                case 'Cc': $this->ccselected = 'selected'; break;
                case 'To': $this->toselected = 'selected'; break;
                }

            $this->arrlang	= bab_getAvailableLanguages();
            $this->count	= count($this->arrlang);
            if($this->count > 0)
            {
                bab_sort::sort($this->arrlang);
            }

            include_once $GLOBALS['babInstallPath'].'utilit/skinincl.php';
            $this->arrskins = bab_skin::getList();
            $this->cntskins = count($this->arrskins);
            bab_sort::natcasesort($this->arrskins);

            $ignoredskins = bab_skin::getNotAccessibles();

            if (0 < count($ignoredskins)) {
                    $this->skinerror = bab_translate('One or more skins are not compatible with the ovidentia charset or access rights are not defined correctly.');
                    if ('UTF-8' === bab_charset::getIso()) {
                        $this->skinerror .= '<br /> '.bab_translate('Ovidentia is in UTF-8, all skins must be embeded in addons and the addonini file must contain the parameter mysql_character_set_database with supported character sets as value');
                    }
                } else {
                    $this->skinerror = false;
                }


            $this->sitemaps = bab_siteMap::getList();

            }

        function getnextlang()
            {
            static $i = 0;
            if( $i < $this->count)
                {
                $this->langval = $this->arrlang[$i];
                if( $this->row['lang'] == $this->langval )
                    $this->langselected = "selected";
                else
                    $this->langselected = "";
                $i++;
                return true;
                }
            else
                return false;
            }

        function getExtention($sFileName, $sSearch)
            {
            $iPos = mb_strpos($sFileName, $sSearch);
            if (false !== $iPos)
                {
                    return mb_substr($sFileName, $iPos+1);
                }
            return false;
            }

        function getnextskin()
            {
            static $i = 0;
            if( list(,$obj) = each($this->arrskins))
                {

                $this->iindex = $i;
                $this->skinname = bab_toHtml($obj->getName());
                $this->skinval = bab_toHtml($obj->getName());
                if( $this->skinname == $this->row['skin'] )
                    {
                    $this->skselectedindex = $i;
                    $this->skinselected = "selected";
                    }
                else
                    $this->skinselected = "";

                $this->arrstyles = array_values($obj->getStyles());
                $this->cntstyles = count($this->arrstyles);
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                reset($this->arrskins);
                return false;
                }
            }

        function getnextstyle()
            {
            static $j = 0;
            if( $j < $this->cntstyles)
                {
                $this->stylename = $this->arrstyles[$j];
                $this->styleval = $this->arrstyles[$j];
                if( $this->skinname == $this->row['skin'] && $this->row['style'] == $this->styleval)
                    $this->stselectedindex = $j;
                $j++;
                return true;
                }
            else
                {
                $j = 0;
                return false;
                }
            }

        function getnextlangfilter()
            {
            static $i = 0;
            if( $i < (bab_getInstance('babLanguageFilter')->countFilters()))
                {
                $this->langfilterval =	bab_getInstance('babLanguageFilter')->getFilterStr($i);
                if($this->row['langfilter'] == $i )
                    {
                    $this->langfilterselected = "selected";
                    }
                else
                    {
                    $this->langfilterselected = "";
                    }
                $i++;
                return true;
                }
            else
                return false;
            } //getnextlangfilter



        public function getnextsitemap()
            {
            if (list($uid,$sitemap) = each($this->sitemaps))
                {
                    $this->uid = bab_toHtml($uid);
                    $this->name = bab_toHtml($sitemap->getSiteMapName());
                    $this->selected = $uid === $this->row['sitemap'];
                    return true;
                }
                return false;
            }


        } // class temp

    $temp = new temp();
    $babBody->babecho(	bab_printTemplate($temp, 'sites.html', 'menu1'));
}



/**
 * Email server configuration.
 *
 * @param int $id
 */
function site_menu2($id)
{
    global $babBody;

    class email_site_configuration_cls extends site_configuration_cls
    {

        public function __construct($id)
        {
            $this->disabled = bab_translate("Disabled");
            $this->mailfunction = bab_translate("Mail function");
            $this->server = bab_translate("Smtp server");
            $this->serverport = bab_translate("Server port");
            $this->validation = bab_translate("Certificate validation");
            $this->certificate_validation = bab_translate("Validate certificates");
            $this->no_certificate_validation = bab_translate("Suppress certificate validation");
            $this->security = bab_translate("Connection security");
            $this->security_none = bab_translate("No security");
            $this->security_ssl = bab_translate("SSL");
            $this->security_tls = bab_translate("TLS");
            $this->none = bab_translate("None");
            $this->smtpuser = bab_translate("SMTP username");
            $this->smtppass = bab_translate("SMTP password");
            $this->smtppass2 = bab_translate("Re-type SMTP password");
            $this->t_mailspool = bab_translate("Undelivered mails");
            $this->smtp = "smtp";
            $this->sendmail = "sendmail";
            $this->mail = "mail";
            $this->thelp = bab_translate('Help to the configuration of the messaging:');
            $this->thelp2 = bab_translate('Disabled: the announcements e-mails of the portal will not be sent');
            $this->thelp3 = bab_translate('E-mail: Ovidentia uses the SMTP waiter(server) informed in the configuration PHP (see the variables smtp, smtp_port, sendmail_from and sendmail_path in the php.ini file)');
            $this->thelp4 = bab_translate('SMTP: manual configuration');
            $this->thelp5 = bab_translate('Sendmail: Ovidentia uses the application sendmail installed on the local machine (see variable sendmail_path in the php.ini file');

            $this->site_configuration_cls($id);

// 			switch ($this->arr['smtpsecurity']) {
// 				case 'ssl':
// 					$this->security__selected =
// 			}

            if (!extension_loaded('openssl')) {
                $this->security .= ' (' . bab_translate("the php openssl extension is not loaded and is required to use SSL or TLS connections") . ')';
                $this->security_ssl .= ' (' . bab_translate("requires openssl php extension") . ')';
                $this->security_tls .= ' (' . bab_translate("requires openssl php extension") . ')';
                $this->ssl_disabled = true;
                $this->tls_disabled = true;
            } else {
                $this->ssl_disabled = false;
                $this->tls_disabled = false;
            }


            $addon = bab_getAddonInfosInstance('mailspooler');
            if (false === $addon)
            {
                $this->mailspooler = false;
            } else {
                $this->mailspooler = $addon->isAccessValid();
            }

        }


    } // class temp

    $temp = new email_site_configuration_cls($id);
    $babBody->babecho(bab_printTemplate($temp, 'sites.html', 'menu2'));
}


function site_menu3($id)
    {

    global $babBody;
    class temp extends site_configuration_cls
        {

        function temp($id)
            {
            $this->change_nickname_title = bab_translate("User can modifiy his login ID");
            $this->change_password_title = bab_translate("User can modifiy his password");

            $this->user_workdays_title = bab_translate("User can modifiy his working days");
            $this->change_lang_title = bab_translate("User can modifiy his language");
            $this->change_skin_title = bab_translate("User can choose his skin");
            $this->change_date_title = bab_translate("User can modifiy his date format");
            $this->change_unavailability_title = bab_translate("User can modifiy his unavailability");
            $this->browse_users_title = bab_translate("User can browse all registered users");

            $this->site_configuration_cls($id);
            }


        } // class temp

    $temp = new temp($id);
    $babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu3"));
    }


function site_menu4($id)
{
    global $babBody;

    class bab_site_menu4_cls extends site_configuration_cls
    {
        function __construct($id)
        {
            $this->imgsize_title = bab_translate("Max image size ( Kb )");
            $this->uploadpath_title = bab_translate("Upload path");
            $this->maxfilesize_title = bab_translate("Upload max file size");
            $this->maxzipsize_title = bab_translate("Max zip file size for treatement");
            $this->t_filemanager = bab_translate("File manager");
            $this->folder_diskspace_title = bab_translate("File manager max group directory size");
            $this->user_diskspace_title = bab_translate("File manager max user directory size");
            $this->total_diskspace_title = bab_translate("File manager max total size");
            $this->notif_quota_total = bab_translate("Notification when this quota is exceeded for the file manager");
            $this->notif_quota_folder = bab_translate("Notification when this quota is exceeded for group directory");
            $this->t_kb = bab_translate("Kb");
            $this->t_mb = bab_translate("Mb");
            $this->t_gb = bab_translate("Gb");
            $this->t_pc = '%';

            $settings = bab_getInstance('bab_Settings');
            $uploadPath = $settings->getUploadPath();
            $disk_free_space = (disk_free_space($uploadPath) / (1024 * 1024));

            $this->disk_free_space_title = bab_translate('Free disk space');
            $this->disk_free_space = number_format($disk_free_space, 2, ',', ' ') . ' ' . bab_translate('Mb');
            $this->site_configuration_cls($id);
        }
    }

    $temp = new bab_site_menu4_cls($id);
    $babBody->babecho(bab_printTemplate($temp, "sites.html", "menu4"));
}


function site_menu5($id)
    {

    global $babBody;
    class temp extends site_configuration_cls
        {

        function temp($id)
            {

            $this->regsettings_title = bab_translate("Date and Time formats");
            $this->date_lformat_title = bab_translate("Long date format");
            $this->date_sformat_title = bab_translate("Short date format");
            $this->time_format_title = bab_translate("Time format");

            $this->site_configuration_cls($id);

            $formats = bab_getRegionalFormats();

            $this->arrlfdate 	= $formats['longDate'];
            $this->arrsfdate 	= $formats['shortDate'];
            $this->arrtime 		= $formats['hour'];
            }

        function getnextlongdate()
            {
            static $i = 0;
            if( $i < count($this->arrlfdate))
                {
                $this->dateval = bab_toHtml($this->arrlfdate[$i]);
                $this->datetxt = bab_toHtml($this->arrlfdate[$i].' : '.bab_formatDate( bab_getDateFormat($this->arrlfdate[$i]), mktime() ));
                $this->selected = $this->arrlfdate[$i] === $this->row['date_longformat'];
                $i++;
                return true;
                }
            else
                return false;
            }

        function getnextshortdate()
            {
            static $i = 0;
            if( $i < count($this->arrsfdate))
                {
                $this->dateval = bab_toHtml($this->arrsfdate[$i]);
                $this->datetxt = bab_toHtml($this->arrsfdate[$i].' : '.bab_formatDate( bab_getDateFormat($this->arrsfdate[$i]), mktime() ));
                $this->selected = $this->arrsfdate[$i] === $this->row['date_shortformat'];
                $i++;
                return true;
                }
            else
                return false;
            }

        function getnexttime()
            {
            static $i = 0;
            if( $i < count($this->arrtime))
                {
                $this->timeval = bab_toHtml($this->arrtime[$i]);
                $this->timetxt = bab_toHtml($this->arrtime[$i].' : '.date( bab_getTimeFormat($this->arrtime[$i]) ));
                $this->selected = $this->arrtime[$i] === $this->row['time_format'];
                $i++;
                return true;
                }
            else
                return false;
            }


        } // class temp

    $temp = new temp($id);
    $babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu5"));
    }



function site_menu6($id)
    {

    global $babBody;
    class temp extends site_configuration_cls
        {
            var $t_defaultCalAccess		= '';
            var $aCalAccess				= array();
            var $iCalAccess				= -1;
            var $sCalAccess				= '';
            var $sCalAccessSelected		= '';
            var $iSelectedCalAccess		= -1;

        function temp($id)
            {
            global $babDB;
            $this->t_dispdays = bab_translate("Days to display");
            $this->t_startdaytxt = bab_translate("First day of week");
            $this->t_starttimetxt = bab_translate("Start time");
            $this->t_endtimetxt = bab_translate("End time");
            $this->allday = bab_translate("On create new event, check all day");
            $this->usebgcolor = bab_translate("Use background color for events");
            $this->usecatcolor = bab_translate("Use category color for events");
            $this->weeknumberstxt = bab_translate("Show week numbers");
            $this->elapstime = bab_translate("Time scale");
            $this->defaultview = bab_translate("Calendar default view");
            $this->minutes = bab_translate("Minutes");
            $this->showupdateinfo = bab_translate("Show the date and the author of the updated event");
            $this->showonlydaysmonthinfo = bab_translate("In month view, display only the days of current month");

            $this->t_defaultCalAccess = bab_translate("Default calendar access for new user");
            $this->t_personalCalAccess = bab_translate("Users that doesn't have personal agenda can view other personal agendas");

            $this->t_callendarnotif = bab_translate("When notification is selected, also notify the author");

            $this->ypcalaccess = 'selected';
            $this->npcalaccess = '';

            $this->aCalAccess = array(
                BAB_CAL_ACCESS_NONE => bab_translate("None"),
                BAB_CAL_ACCESS_VIEW => bab_translate("Consultation"),
                BAB_CAL_ACCESS_UPDATE => bab_translate("Creation and modification"),
                BAB_CAL_ACCESS_SHARED_UPDATE => bab_translate("Shared creation and modification"),
                BAB_CAL_ACCESS_FULL => bab_translate("Full access")

            );

            $this->site_configuration_cls($id);

            if(array_key_exists('row', get_object_vars($this)))
            {
                if(is_array($this->row) && array_key_exists('iDefaultCalendarAccess', $this->row))
                {
                    $this->iSelectedCalAccess = $this->row['iDefaultCalendarAccess'];
                    //bab_debug('iDefaultCalendarAccess ==> ' . $this->iSelectedCalAccess);
                    if( $this->row['iPersonalCalendarAccess'] ==  'Y')
                    {
                        $this->ypcalaccess = 'selected';
                        $this->npcalaccess = '';
                    }
                    else
                    {
                        $this->ypcalaccess = '';
                        $this->npcalaccess = 'selected';
                    }

                }
            }

            $site = new site_configuration_cls($id);
            $site = $site->arr;

            $this->dispdays = array_flip(explode(',',$site['dispdays']));
            $this->startday = $site['startday'];
            $this->sttime = $site['start_time'];
            $this->arrdv = array(bab_translate("Month"), bab_translate("Week"),bab_translate("Day"));
            if( $site['allday'] ==  'Y')
                {
                $this->yallday = 'selected';
                $this->nallday = '';
                }
            else
                {
                $this->nallday = 'selected';
                $this->yallday = '';
                }

            if( $site['usebgcolor'] ==  'Y')
                {
                $this->yusebgcolor = 'selected';
                $this->nusebgcolor = '';
                }
            else
                {
                $this->nusebgcolor = 'selected';
                $this->yusebgcolor = '';
                }

            if( $site['usecatcolor'] == 'Y')
                {
                $this->yusecatcolor = 'selected';
                $this->nusecatcolor = '';
                }
            else
                {
                $this->nusecatcolor = 'selected';
                $this->yusecatcolor = '';
                }

            if( $site['show_update_info'] ==  'Y')
                {
                $this->yshowupdateinfo = 'selected';
                $this->nshowupdateinfo = '';
                }
            else
                {
                $this->nshowupdateinfo = 'selected';
                $this->yshowupdateinfo = '';
                }

            if( $site['show_onlydays_of_month'] ==  'Y')
                {
                $this->yshowonlydaysmonthinfo = 'selected';
                $this->nshowonlydaysmonthinfo = '';
                }
            else
                {
                $this->nshowonlydaysmonthinfo = 'selected';
                $this->yshowonlydaysmonthinfo = '';
                }

            if( $site['calendar_notif_author'] ==  'Y')
                {
                $this->ycallendarnotif = 'selected';
                $this->ncallendarnotif = '';
                }
            else
                {
                $this->ncallendarnotif = 'selected';
                $this->ycallendarnotif = '';
                }
            }

        function getnextdispday()
            {
            static $i = 0;
            if ($i < 7)
                {
                if( isset($this->dispdays[$i] ))
                    {
                    $this->checked = "checked";
                    }
                else
                    {
                    $this->checked = "";
                    }
                $this->dayid = $i;
                $this->shortday = bab_DateStrings::getDay($i);
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                return false;
                }
            }

        function getnextstartday()
            {
            static $i = 0;
            if ($i < 7)
                {
                if( $this->startday == $i )
                    {
                    $this->checked = "selected";
                    }
                else
                    {
                    $this->checked = "";
                    }
                $this->dayid = $i;
                $this->shortday = bab_DateStrings::getDay($i);
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                return false;
                }
            }

        function getnexttime()
            {
            static $i = 0;
            if( $i < 24 )
                {
                $this->timeid = sprintf("%02s:00:00", $i);
                $this->timeval = mb_substr($this->timeid, 0, 2);
                if( $this->timeid == $this->sttime)
                    {
                    $this->checked = "selected";
                    }
                else
                    {
                    $this->checked = "";
                    }
                $i++;
                return true;
                }
            else
                {
                $this->sttime = $GLOBALS['babBody']->babsite['end_time'];
                $i = 0;
                return false;
                }

            }


        function getnextet()
            {
            static $i = 0;
            if( $i < 5 )
                {
                switch($i)
                    {
                    case 0:
                        $this->etval = 5;
                        break;
                    case 1:
                        $this->etval = 10;
                        break;
                    case 2:
                        $this->etval = 15;
                        break;
                    case 3:
                        $this->etval = 30;
                        break;
                    case 4:
                        $this->etval = 60;
                        break;
                    }

                if( $this->etval == $GLOBALS['babBody']->babsite['elapstime'])
                    $this->etselected = 'selected';
                else
                    $this->etselected = '';
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                return false;
                }

            }

        function getnextdv()
            {
            static $i = 0;
            if( $i < count($this->arrdv) )
                {
                if( $i == $GLOBALS['babBody']->babsite['defaultview'])
                    $this->dvselected = 'selected';
                else
                    $this->dvselected = '';
                $this->dvvalid = $i;
                $this->dvval = $this->arrdv[$i];
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                return false;
                }

            }

        function getNextCalAccess()
            {
                $this->sCalAccessSelected = '';

                $aCalAccessItem = each($this->aCalAccess);
                if(false !== $aCalAccessItem)
                {
                    $this->iCalAccess = $aCalAccessItem['key'];
                    $this->sCalAccess = $aCalAccessItem['value'];

                    if($this->iSelectedCalAccess == $this->iCalAccess)
                    {
                        $this->sCalAccessSelected = 'selected="selected"';
                    }
                    //bab_debug($aCalAccessItem);
                    return true;
                }
                return false;
            }

        } // class temp

    $temp = new temp($id);
    $babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu6"));
    }

function site_menu13($id)
    {

    global $babBody;
    class site_menu13_class extends site_configuration_cls
        {

        function site_menu13_class($id)
            {
            global $babDB;
            $this->t_workdays = bab_translate("Working days (for all sites)");
            $this->t_nonworking = bab_translate("Non-working days");
            $this->t_ok = bab_translate("Ok");
            $this->t_add = bab_translate("Add");
            $this->t_delete = bab_translate("Delete");
            $this->t_load_date = bab_translate("Load date");
            $this->t_date = bab_translate("Date");
            $this->t_text = bab_translate("Name");
            $this->t_type_date = bab_translate("Date type");
            $this->t_type = bab_getNonWorkingDayTypes(true);
            $this->t_starttimetxt = bab_translate("Start time");
            $this->t_endtimetxt = bab_translate("End time");
            $this->t_cal_category = bab_translate("Category to use in calendar for non working day");
            $this->t_workinghours = bab_translate("Main source for working hours:");

            $this->id_cal_category = empty($GLOBALS['babBody']->babsite['id_calendar_cat'])? 0: $GLOBALS['babBody']->babsite['id_calendar_cat'];
            include_once $GLOBALS['babInstallPath']."utilit/calapi.php";
            $this->categs = bab_calGetCategories();

            $this->catcount = count($this->categs);

            $this->sttime = $GLOBALS['babBody']->babsite['start_time'];

            $this->site_configuration_cls($id);

            include_once $GLOBALS['babInstallPath']."utilit/calapi.php";
            $sWorkingDays = '';
            bab_calGetWorkingDays(0, $sWorkingDays);

            $this->workdays = array_flip(explode(',',$sWorkingDays));
            $this->resnw = $babDB->db_query("SELECT * FROM ".BAB_SITES_NONWORKING_CONFIG_TBL." WHERE id_site='".$babDB->db_escape_string($id)."'");

            $this->func = bab_functionality::getFunctionalities('WorkingHours');
            $this->selectedfunc = bab_functionality::get('WorkingHours');

            $this->selectfunc = (count($this->func) > 1);
        }

        public function getnextfunc()
        {
            if (list(, $f) = each($this->func))
            {
                $func = bab_functionality::get("WorkingHours/$f");
                $this->name = bab_toHtml($f);
                $this->description = bab_toHtml($func->getDescription());
                $this->selected = ($this->selectedfunc instanceof $func);
                return true;
            }

            return false;
        }

        function getnextworkday()
            {
            static $i = 0;
            if ($i < 7)
                {
                if( isset($this->workdays[$i] ))
                    {
                    $this->checked = "checked";
                    }
                else
                    {
                    $this->checked = "";
                    }
                $this->dayid = $i;
                $this->shortday = bab_DateStrings::getDay($i);
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                return false;
                }
            }


        function getnextnonworking_type()
            {
            static $i = 1;
            if ($i < 100 && isset($this->t_type[$i]))
                {
                $this->type = $i;
                $this->txt = $this->t_type[$i];
                $i++;
                return true;
                }
            else
                {
                $i = 1;
                return false;
                }
            }


        function getnextnonworking()
            {
            global $babDB;
            if ($arr = $babDB->db_fetch_array($this->resnw))
                {
                $this->value = $arr['nw_text'].'#';
                $this->value .= $arr['nw_type'];
                $this->value .= !empty($arr['nw_day']) ? ','.$arr['nw_day'] : '';
                $this->nw_day = $arr['nw_day'];
                if (!empty($arr['nw_text']))
                    $this->text = $arr['nw_text'];
                else
                    {
                    $this->text = $this->t_type[$arr['nw_type']];
                    if (!empty($this->nw_day))
                        {
                        $this->text .= ' : '.$this->nw_day;
                        }
                    }
                return true;
                }
            else
                return false;
            }

        function getnexttime()
            {
            static $i = 0;
            if( $i < 24 )
                {
                $this->timeid = sprintf("%02s:00:00", $i);
                $this->timeval = mb_substr($this->timeid, 0, 2);
                if( $this->timeid == $this->sttime)
                    {
                    $this->checked = "selected";
                    }
                else
                    {
                    $this->checked = "";
                    }
                $i++;
                return true;
                }
            else
                {
                $this->sttime = $GLOBALS['babBody']->babsite['end_time'];
                $i = 0;
                return false;
                }

            }
        function getnextcat()
            {
            static $i = 0;
            if( $i < $this->catcount)
                {
                $this->categid = $this->categs[$i]['id'];
                $this->categname = bab_toHtml($this->categs[$i]['name']);
                if( $this->categid == $this->id_cal_category )
                    {
                    $this->selected = 'selected';
                    }
                else
                    {
                    $this->selected = '';
                    }
                $i++;
                return true;
                }
            else
                return false;

            }
        } // class site_menu13_class

    $temp = new site_menu13_class($id);
    $babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu13"));
    }


function siteAuthentification($id)
{
    $W = bab_Widgets();
    $page = $W->BabPage();

    global $babDB, $bab_ldapAttributes;
    $req = "select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass, DECODE(ldap_adminpassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as ldapadminpwd from ".BAB_SITES_TBL." where id='".$babDB->db_escape_string($id)."'";
    $res = $babDB->db_query($req);
    $res = $babDB->db_fetch_array($res);

    $form = $W->Form();
    $form->setHiddenValue('tg', 'site');
    $form->setHiddenValue('idx', 'modify');
    $form->setHiddenValue('item', $id);
    $form->setHiddenValue('action', 'menu8');

    $funcs = bab_functionality::getFunctionalities('PortalAuthentication');

    $authTypeItem = $W->Select();

    $configuration = $W->VBoxItems();
    $configurationOptions = array();
    foreach ($funcs as $func){
        $currentFunc = bab_functionality::get('PortalAuthentication/'.$func);
        $authTypeItem->addOption($func, $func. ': ' .$currentFunc->getDescription());
        $configuration->addItem($displayable = $W->Html($currentFunc->getConfigForm()));
        $authTypeItem->setAssociatedDisplayable($displayable, array($func));
        if($res['authentification'] == $currentFunc->getPath()){
            $res['authtype'] = $func;
        }
    }

    $form->addItem(
        $W->VBoxItems(
            $W->LabelledWidget(
                bab_translate("Switch to https"),
                $W->CheckBox(),
                'auth_https'
            ),
            $W->LabelledWidget(
                bab_translate("Fullscreen authentication"),
                $W->CheckBox(),
                'auth_fullscreen'
            ),
            $W->LabelledWidget(
                bab_translate("Allow multiple connexions for all accounts"),
                $W->CheckBox(),
                'auth_multi_session'
            ),
            $W->LabelledWidget(
                bab_translate("Automatic connection"),
                $W->Select()->addOptions(
                    array(
                        'Y' => bab_translate("Yes"),
                        'N' => bab_translate("No"),
                        'L' => bab_translate('Login ID only')
                    )
                 ),
                'remember_login'
            ),
            $W->LabelledWidget(
                bab_translate("Display option 'Lost Password'"),
                $W->Select()->addOptions(
                    array(
                        'Y' => bab_translate("Yes"),
                        'N' => bab_translate("No"),
                    )
                ),
                'email_password'
            ),
            $W->LabelledWidget(
                bab_translate("Ask for the nickname to send a new password by email"),
                $W->Select()->addOptions(
                    array(
                        '1' => bab_translate("Yes"),
                        '0' => bab_translate("No"),
                    )
                ),
                'ask_nickname'
            ),
            $W->LabelledWidget(
                bab_translate("Authentication"),
                $authTypeItem,
                'authtype'
            )
        )->setVerticalSpacing(1, 'em')
    );

    $form->setValues($res);

    $form->addItem(
        $W->SubmitButton()->setName('Submit')->setLabel(bab_translate('Save'))
    );

    $page->addItem($form);

    $page->addItem($configuration);


    $page->setSizePolicy('BabLoginCadreBackground');
    $page->addClass('BabLoginMenuBackground');

    $page->pageEcho($W->HtmlCanvas());

    global $babBody;
    class clsSiteAuthentification
    {
        function getnextauth()
        {
            static $value = true;
            if($value === true){
                reset($this->arrayauth);
            }
            if($value === false){
                return false;
            }
            $current = key($this->arrayauth);

            $this->authval = $current;
            $this->authname = $this->arrayauth[$current];
            if( $this->authsite == $this->authval )
                {
                $this->authselected = "selected";
                }
            else
                {
                $this->authselected = "";
                }
            $value = next($this->arrayauth);
            return true;
        }

        function getnextfield()
            {
            global $babDB, $bab_ldapAttributes;
            static $i = 0;
            if( $i < $this->countf)
                {
                $this->iindex = $i;
                if( 0  == $i )
                    {
                    $this->ofieldname = bab_translate("Login ID");
                    $this->ofieldv = "nickname";
                    $this->required = true;
                    if( in_array($this->ldapattributesite, $bab_ldapAttributes) )
                        {
                        $this->ofieldval = '';
                        }
                    else
                        {
                        $this->ofieldval = $this->ldapattributesite;
                        }
                    }
                else
                    {
                    $this->required = false;
                    $arr = $babDB->db_fetch_array($this->resf);
                    if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
                        {
                        $rr = $babDB->db_fetch_array($babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'"));
                        $this->ofieldname = translateDirectoryField($rr['description']);
                        $filedname = $rr['name'];

                        if (in_array($filedname,array('sn','givenname','email')))
                            $this->required = true;
                        }
                    else
                        {
                        $rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
                        $this->ofieldname = translateDirectoryField($rr['name']);
                        $filedname = "babdirf".$arr['id_field'];
                        }



                    $this->ofieldv = $filedname;
                    $this->x_ofieldv = $arr['x_name'];

                    if( in_array($arr['x_name'], $bab_ldapAttributes))
                        {
                        $this->ofieldval = '';
                        }
                    else
                        {
                        $this->ofieldval = $arr['x_name'];
                        }
                    }
                $i++;
                return true;
                }
            else
                {
                $i = 0;
                if( $this->countf > 0 )
                    {
                    $babDB->db_data_seek($this->resf, 0);
                    }
                return false;
                }
            }

        function getnextval()
            {
            global $bab_ldapAttributes;
            static $k = 0;
            if( $k < $this->countv)
                {
                $this->ffieldid = $bab_ldapAttributes[$k];
                $this->ffieldname = $bab_ldapAttributes[$k];

                if( $this->ofieldv == "nickname" )
                    {
                        if( $this->ffieldname == $this->ldapattributesite)
                        {
                        $this->fselected = "selected";
                        }
                        else
                        {
                        $this->fselected = "";
                        }
                    }
                else
                    {
                        if( $this->ffieldname == $this->x_ofieldv )
                        {
                        $this->fselected = "selected";
                        }
                        else
                        {
                        $this->fselected = "";
                        }
                    }

                $k++;
                return true;
                }
            else
                {
                $k = 0;
                return false;
                }
            }

        function getnextpasstype()
            {
            static $i = 0;
            if( $i < count($this->arrayauthpasstype))
                {
                list($this->passtypeval, $this->passtypename) = each($this->arrayauthpasstype);
                if( $this->ldapencryptiontype == $this->passtypeval )
                    {
                    $this->passtypeselected = "selected";
                    }
                else
                    {
                    $this->passtypeselected = "";
                    }
                $i++;
                return true;
                }
            else
                return false;
            }

        function getnextdecodetype()
            {
            static $encodingTypes = null;

            if (null === $encodingTypes) {
                include_once $GLOBALS['babInstallPath'].'utilit/ldap.php';
                $encodingTypes = bab_getLdapEncoding();
            }

            static $i = 0;
            if( $i < count($encodingTypes))
                {
                $this->stid = $i;
                $this->stval = $encodingTypes[$i];
                if( $this->vdecodetype == $i )
                    {
                    $this->selected = 'selected';
                    }
                else
                    {
                    $this->selected = '';
                    }
                $i++;
                return true;
                }
            else
                return false;
            }
        }
}


function sectionDelete($id)
    {
    global $babBody;

    class temp
        {
        var $warning;
        var $message;
        var $title;
        var $urlyes;
        var $urlno;
        var $yes;
        var $no;
        var $topics;
        var $article;

        function temp($id)
            {
            $this->message = bab_translate("Are you sure you want to delete this site");
            $this->title = getSiteName($id);
            $this->warning = bab_translate("WARNING: This operation will delete the site and all references"). "!";
            $this->urlyes = $GLOBALS['babUrlScript']."?tg=site&idx=Delete&item=".$id."&action=Yes";
            $this->yes = bab_translate("Yes");
            $this->urlno = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$id;
            $this->no = bab_translate("No");
            }
        }

    $temp = new temp($id);
    $babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
    }


function siteRegistration($id)
    {
    global $babBody;
    class temp
        {
        function temp($id)
            {
            global $babDB, $babBody;
            $this->item = $id;
            $this->yes = bab_translate("Yes");
            $this->no = bab_translate("No");
            $this->registration = bab_translate("Activate Registration")."?";
            $this->field = bab_translate("Fields to display in registration form");
            $this->rw = bab_translate("Use in registration");
            $this->required = bab_translate("Required");
            $this->multilignes = bab_translate("Multilignes");
            $this->disclaimertxt = bab_translate("Registration options");
            $this->disclaimer = bab_translate("Display link to Disclaimer/Privacy Statement");
            $this->editdptxt = bab_translate("Edit");
            $this->groupregistration = bab_translate("Default group for confirmed users");
            $this->confirmationstxt = array(bab_translate("Confirm account by validating address email"), bab_translate("Manual confirmation by administrators"), bab_translate("Confirm account without address email validation"));
            $this->none = bab_translate("None");
            $this->add = bab_translate("Modify");
            $this->res = $babDB->db_query("select * from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$babDB->db_escape_string($id)."'");
            if( $this->res && $babDB->db_num_rows($this->res) > 0)
                {
                $this->count = $babDB->db_num_rows($this->res);
                }
            else
                {
                $this->count = 0;
                }
            $this->altbg = true;
            $this->urleditdp = $GLOBALS['babUrlScript']."?tg=site&idx=editdp&item=".$id;


            include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

            $tree = new bab_grptree();
            $this->groups = $tree->getIndentedGroups(BAB_REGISTERED_GROUP);
            unset($this->groups[BAB_ADMINISTRATOR_GROUP]);

            $this->arrsite = $babDB->db_fetch_array($babDB->db_query("select authentification, registration, email_confirm, display_disclaimer, idgroup from ".BAB_SITES_TBL." where id='".$babDB->db_escape_string($id)."'"));
            if( $this->arrsite['display_disclaimer'] == "Y")
                {
                $this->dpchecked = "checked";
                }
            else
                {
                $this->dpchecked = "";
                }

            if (1 === (int) $this->arrsite['authentification']) {
                    $babBody->addError(bab_translate('The registration will not be effective while the LDAP authentication is active'));
                }

            }

        function getnextfield()
            {
            global $babDB;
            static $i = 0;
            if( $i < $this->count)
                {
                $arr = $babDB->db_fetch_array($this->res);
                $this->fieldid = $arr['id'];
                $this->altbg = !$this->altbg;

                if( $arr['registration'] == "Y")
                    {
                    $this->rwchecked = "checked";
                    }
                else
                    {
                    $this->rwchecked = "";
                    }

                if( $arr['required'] == "Y")
                    {
                    $this->reqchecked = "checked";
                    }
                else
                    {
                    $this->reqchecked = "";
                    }
                if( $arr['multilignes'] == "Y")
                    {
                    $this->mlchecked = "checked";
                    }
                else
                    {
                    $this->mlchecked = "";
                    }

                if (in_array( $arr['id_field'], array( 2, 3, 4, 6) ))
                    {
                    $this->disabled = true;
                    }
                else
                    {
                    $this->disabled = false;
                    }

                if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
                    {
                    $res = $babDB->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$babDB->db_escape_string($arr['id_field'])."'");
                    $rr = $babDB->db_fetch_array($res);
                    $this->fieldn = bab_toHtml(translateDirectoryField($rr['description']));
                    $this->fieldv = bab_toHtml($rr['name']);
                    }
                else
                    {
                    $rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".$babDB->db_escape_string(($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS))."'"));
                    $this->fieldn = bab_toHtml(translateDirectoryField($rr['name']));
                    $this->fieldv = "babdirf".$arr['id'];
                    }
                $i++;
                return true;
                }
            else
                return false;
            }

        function getnextgrp()
            {

            if( list(,$arr) = each($this->groups) )
                {
                $this->grpname = bab_toHtml($arr['name']);
                $this->grpid = $arr['id'];
                if( $this->arrsite['idgroup'] == $this->grpid )
                    {
                    $this->grpsel = "selected";
                    }
                else
                    {
                    $this->grpsel = "";
                    }
                return true;
                }
            else
                {
                return false;
                }
            }

        function getnextoption()
            {
            static $i = 0;
            if( $i < count($this->confirmationstxt))
                {
                $this->optname = $this->confirmationstxt[$i];
                $this->optid = $i;
                if( $this->arrsite['email_confirm'] == $i )
                    {
                    $this->optsel = "selected";
                    }
                else
                    {
                    $this->optsel = "";
                    }
                $i++;
                return true;
                }
            else
                {
                return false;
                }
            }
        }

    $temp = new temp($id);
    $babBody->babecho( bab_printTemplate($temp,"sites.html", "menu9"));
    }

function editDisclaimerPrivacy($id, $content)
    {
    global $babBody;

    class temp
        {
        var $disclaimertxt;
        var $modify;

        var $db;
        var $arr = array();
        var $res;
        var $msie;
        var $brecevt;
        var $all;
        var $thisone;
        var $updaterec;

        function temp($id, $content)
            {
            global $babDB;
            $this->disclaimertxt = bab_translate("Disclaimer/Privacy Statement");
            $this->modify = bab_translate("Update");
            $this->item = $id;
            $req = "select * from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$babDB->db_escape_string($id)."'";
            $res = $babDB->db_query($req);
            if( empty($content))
                {
                if( $res && $babDB->db_num_rows($res) > 0 )
                    {
                    $arr = $babDB->db_fetch_array($res);
                    $this->disclaimerval = $arr['disclaimer_text'];
                    }
                else
                    {
                    $this->disclaimerval = '';
                    }
                }
            else
                {
                $this->disclaimerval = $content;
                }

            include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
            $editor = new bab_contentEditor('bab_disclaimer');
            $editor->setContent($this->disclaimerval);
            $editor->setFormat('html');
            $this->editor = $editor->getEditor();

            }
        }

    include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
    $GLOBALS['babBodyPopup'] = new babBodyPopup();
    $GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
    $GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;

    $temp = new temp($id, $content);
    $GLOBALS['babBodyPopup']->babecho(bab_printTemplate($temp,"sites.html", "disclaimeredit"));
    printBabBodyPopup();
    die();
    }



function editor_configuration($id_site)
    {
    global $babBody;

    class temp
        {
        function temp($id_site)
            {
            global $babDB;
            $this->id_site = $id_site;

            $this->t_use_editor = bab_translate("Use WYSIWYG editor");
            $this->t_filter_html = bab_translate("Filter and remove HTML elements");
            $this->t_allow_elements = bab_translate("Allow elements on the filter");
            $this->t_presentation = bab_translate("Presentation tags");
            $this->t_lists = bab_translate("Ordered lists, unordered lists, definitions lists");
            $this->t_tables = bab_translate("Tables");
            $this->t_nonhtml = bab_translate("Scripts and plugins");
            $this->t_images = bab_translate("Images");
            $this->t_iframes = bab_translate("Frames (open external url into document body)");
            $this->t_forms = bab_translate("Forms");
            $this->t_target = bab_translate("Targets on links (open links in new windows)");
            $this->t_submit = bab_translate("Submit");

            $res = $babDB->db_query("SELECT use_editor, filter_html, bitstring FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$babDB->db_escape_string($id_site)."'");

            $this->arr = $babDB->db_fetch_assoc($res);

            if (!$this->arr)
                {
                $this->arr = array(
                        'use_editor' => 1,
                        'filter_html' => 0,
                        'bitstring' => '00000000'
                    );
                }

            $this->bitstring = preg_split('//', $this->arr['bitstring'], -1, PREG_SPLIT_NO_EMPTY);
            $this->bitstring_len = count($this->bitstring);
            }
        }

    $temp = new temp($id_site);
    $babBody->babecho(bab_printTemplate($temp,"sites.html", "menu10"));
    }


function siteMenu($id_site)
{
global $babBody;

    class temp extends site_configuration_cls
        {
        function temp($id_site)
            {
            $this->t_delete = bab_translate("Delete");
            $this->site_configuration_cls($id_site);
            }

        function getnext()
            {
            return list($this->page,$this->text) = each($this->menu);
            }
        }

    $temp = new temp($id_site);
    $babBody->babecho(bab_printTemplate($temp,"sites.html", "menu"));
}




class bab_siteSearchTpl {

    /**
     * @var int
     */
    private $id_site;

    /**
     * @var array
     */
    private $searchUi;


    public function __construct($id_site)
    {
        $this->item = bab_toHtml($id_site);
        $this->id_site = $id_site;
        $this->engine_options = $this->getEngine();

        $this->t_interface = bab_translate('Search engine user interface');
        $this->t_record = bab_translate('Save');

        require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';

        $func = new bab_functionalities();
        $this->searchUi = $func->getChildren('SearchUi');
    }


    public function getnextinterface(&$skip)
    {
        if (list(, $name) = each($this->searchUi)) {
            $searchUi = bab_functionality::get('SearchUi/'.$name);

            if (!$searchUi) {
                $skip = true;
                return true;
            }

            $this->name = bab_toHtml($name);
            $this->description = bab_toHtml($searchUi->getDescription());
            $this->selected = get_class(bab_functionality::get('SearchUi')) === get_class($searchUi);
            return true;
        }

        return false;
    }


    /**
     * @return string
     */
    private function getEngine()
    {
        $arr = bab_searchEngineInfos();

        if (false === $arr) {
            return '';
        }

        switch($arr['name'])
        {
            case 'swish':
                include_once $GLOBALS['babInstallPath']."admin/sitesearch.swish.php";
                break;
        }

        return site_menu11($this->id_site);
    }
}




function call_site_menu11($item) {

    $babBody = bab_getBody();

    $template = new bab_siteSearchTpl($item);
    $babBody->babecho(bab_printTemplate($template, "sitesearch.html"));
}


/* ************************** RECORD ************************** */



function record_editor_configuration($id_site)
{
    global $babDB;

    $bitstring = array();
    for ($i = 0; $i < $_POST['bitstring_len']; $i++)
        {
        $bitstring[$i] = isset($_POST['bitstring']) && in_array($i,$_POST['bitstring']) ? 1 : 0;
        }


    $arr_tags = array(
            0 => 'font b a strong span div h1 h2 h3 h4 h5 p code i sup sub strike i em u abbr acronym del cite dfn ins kbd samp map area address br blockquote center hr q',
            1 => 'ul ol li dl dt dd',
            2 => 'table thead tbody tfoot tr td th caption colgroup col',
            3 => 'object script noscript param embed applet',
            4 => 'img',
            5 => 'iframe',
            6 => 'form input select optgroup textarea label fieldset button'
        );

    $arr_attributes = array(
            0 => 'src href accesskey coords hreflang rel rev shape tabindex type class dir id lang title style align border height width alt bgcolor usemap vspace hspace abbr background hspace nowrap char bordercolor bordercolordark bordercolorlight cite name',
            2 => 'cellpadding cellspacing cols rules summary axis colspan rowspan scope',
            3 => 'onclick ondbclick onkeydown onkeypress onkeyup onmousedown onmousemove onmouseout onmouseover onmouseup onload onselect onfocus onblur onchange archive code codebase mayscript object units pluginspage palette hidden classid data declare notab shapes standby defer charset language',
            4 => 'longdesc',
            6 => 'value for',
            7 => 'target'
        );

    $use_editor = isset($_POST['use_editor']) ? 1 : 0;
    $filter_html = isset($_POST['filter_html']) ? 1 : 0;
    $tags = '';
    $attributes = '';
    $verify_href = $bitstring[3] ? 0 : 1;
    $i = 0;
    foreach ($bitstring as $bit)
        {
        if (isset($arr_tags[$i]) && $bit == 1)
            {
            $tags .= ' '.$arr_tags[$i];
            }

        if (isset($arr_attributes[$i]) && $bit == 1)
            {
            $attributes .= ' '.$arr_attributes[$i];
            }
        $i++;
        }

    $res = $babDB->db_query("SELECT id FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$babDB->db_escape_string($id_site)."'");
    if ($babDB->db_num_rows($res) > 0)
        {
        $babDB->db_query("UPDATE ".BAB_SITES_EDITOR_TBL."
                        SET
                            use_editor='".$babDB->db_escape_string($use_editor)."',
                            filter_html='".$babDB->db_escape_string($filter_html)."',
                            tags='".$babDB->db_escape_string(trim($tags))."',
                            attributes='".$babDB->db_escape_string(trim($attributes))."',
                            verify_href='".$babDB->db_escape_string($verify_href)."',
                            bitstring='".$babDB->db_escape_string(implode('',$bitstring))."'
                        WHERE
                            id_site='".$babDB->db_escape_string($id_site)."'
                        ");
        }
    else
        {
        $babDB->db_query("INSERT INTO ".BAB_SITES_EDITOR_TBL."
                        (use_editor, filter_html, tags, attributes, verify_href, bitstring, id_site)
                        VALUES
                            ('".$babDB->db_escape_string($use_editor)."',
                            '".$babDB->db_escape_string($filter_html)."',
                            '".$babDB->db_escape_string(trim($tags))."',
                            '".$babDB->db_escape_string(trim($attributes))."',
                            '".$babDB->db_escape_string($verify_href)."',
                            '".$babDB->db_escape_string(implode('',$bitstring))."',
                            '".$babDB->db_escape_string($id_site)."')
                        ");
        }
}




function siteSave($name)
    {
    global $babBody, $babDB;

    $description 	= bab_pp('description');
    $babslogan 		= bab_pp('babslogan');
    $lang 			= bab_pp('lang');
    $siteemail 		= bab_pp('siteemail');
    $skin 			= bab_pp('skin');
    $style 			= bab_pp('style');
    $sitemap		= bab_pp('sitemap');
    $langfilter		= bab_pp('langfilter');
    $adminname		= bab_pp('adminname');
    $name_order		= bab_pp('name_order');
    $statlog		= bab_pp('statlog');
    $maxemail		= bab_pp('maxemail');
    $mfa			= bab_pp('mfa');
    $staticurl		= bab_pp('staticurl');


    $query = "insert into ".BAB_SITES_TBL."
        (name, description, lang, adminemail, adminname, skin, style, sitemap, stat_log,  langfilter, babslogan, name_order, mail_fieldaddress, mail_maxperpacket, staticurl)
    VALUES
        (
            '" .$babDB->db_escape_string($name). "',
            '" . $babDB->db_escape_string($description). "',
            '" . $babDB->db_escape_string($lang). "',
            '" . $babDB->db_escape_string($siteemail). "',
            '" . $babDB->db_escape_string($adminname). "',
            '" . $babDB->db_escape_string($skin). "',
            '" . $babDB->db_escape_string($style). "',
            '" . $babDB->db_escape_string($sitemap). "',
            '" . $babDB->db_escape_string($statlog). "',
            '" . $babDB->db_escape_string($langfilter)."',
            '" . $babDB->db_escape_string($babslogan)."',
            '" . $babDB->db_escape_string($name_order)."',
            '" . $babDB->db_escape_string($mfa)."',
            '" . $babDB->db_escape_string($maxemail)."',
            '" . $babDB->db_escape_string($staticurl)."'
        )";

    $babDB->db_query($query);
    $idsite = $babDB->db_insert_id();

    $babDB->db_query("insert into ".BAB_SITES_DISCLAIMERS_TBL." (id_site, disclaimer_text) values ('".$babDB->db_escape_string($idsite)."','')");

    $resf = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='0'");
    while( $row = $babDB->db_fetch_array($resf))
        {
        $babDB->db_query("insert into ".BAB_LDAP_SITES_FIELDS_TBL." (id_field, x_name, id_site) values ('".$babDB->db_escape_string($row['id_field'])."','','".$babDB->db_escape_string($idsite)."')");
        $babDB->db_query("insert into ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes) values ('".$babDB->db_escape_string($idsite)."', '".$babDB->db_escape_string($row['id_field'])."','N','N', 'N')");
        }

    $babDB->db_query("update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='Y', required='Y' where id_site='".$babDB->db_escape_string($idsite)."' and id_field IN ('2', '4', '6')");
    $babDB->db_query("update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='Y' where id_site='".$babDB->db_escape_string($idsite)."' and id_field='3'");

    return $idsite;
    }

function siteUpdate_menu1()
{
    global $babBody, $babDB;

    $name			= &$_POST['name'];
    $description	= &$_POST['description'];
    $babslogan		= &$_POST['babslogan'];
    $lang			= &$_POST['lang'];
    $style			= &$_POST['style'];
    $siteemail		= &$_POST['siteemail'];
    $adminname		= &$_POST['adminname'];
    $maxemail		= &$_POST['maxemail'];
    $mfa			= &$_POST['mfa'];
    $skin			= &$_POST['skin'];
    $sitemap		= bab_pp('sitemap');
    $langfilter		= bab_getInstance('babLanguageFilter')->convertFilterToInt($_POST['langfilter']);
    $name_order		= bab_pp('name_order');
    $staticurl		= bab_pp('staticurl');
    $statlog		= &$_POST['statlog'];
    $mass_mailing	= &$_POST['mass_mailing'];


    if( empty($name))
        {
        $babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
        return false;
        }

    $maxemail = is_numeric($maxemail) ? $maxemail : 25;

    if (empty($_POST['item']))
        {
        // create

        $name = bab_pp('name');

        $query = "select * from ".BAB_SITES_TBL." where name='".$babDB->db_escape_string($name)."'";
        $res = $babDB->db_query($query);
        if( $babDB->db_num_rows($res) > 0)
            {
            $babBody->msgerror = bab_translate("ERROR: This site already exists");
            return false;
            }

        return siteSave($name);
        }

    $query = "select * from ".BAB_SITES_TBL." where name='".$babDB->db_escape_string($name)."' AND id<>'".$babDB->db_escape_string($_POST['item'])."'";
    $res = $babDB->db_query($query);
    if( $babDB->db_num_rows($res) > 0)
        {
        $babBody->msgerror = bab_translate("ERROR: This site already exists");
        return false;
        }

    list($oldname) = $babDB->db_fetch_row($babDB->db_query("select name from ".BAB_SITES_TBL." where id='".$babDB->db_escape_string($_POST['item'])."'"));

    if( $oldname != $name && $GLOBALS['babSiteName'] == $oldname)
        {
        $filename = "config.php";
        $file = @fopen($filename, "r");
        $txt = fread($file, filesize($filename));
        fclose($file);
        $reg = "/babSiteName[[:space:]]*=[[:space:]]*\"([^\"]*)\"/";
        $match = null;
        $res = preg_match($reg, $txt, $match);
        $reg = "/babSiteName[[:space:]]*=[[:space:]]*\"".$match[1]."\"/";
        $out = preg_replace($reg, "babSiteName = \"".$name."\"", $txt);
        $file = fopen($filename, "w");
        fputs($file, $out);
        fclose($file);
        }


    $req = "UPDATE ".BAB_SITES_TBL." SET

            name='".$babDB->db_escape_string($name)."',
            description='".$babDB->db_escape_string($description)."',
            lang='".$babDB->db_escape_string($lang)."',
            adminemail='".$babDB->db_escape_string($siteemail)."',
            adminname='".$babDB->db_escape_string($adminname)."',
            skin='".$babDB->db_escape_string($skin)."',
            style='".$babDB->db_escape_string($style)."',
            sitemap=".$babDB->quote($sitemap).",
            stat_log='".$babDB->db_escape_string($statlog)."',
            langfilter='" .$babDB->db_escape_string($langfilter). "',
            name_order='".$babDB->db_escape_string($name_order)."',
            babslogan='".$babDB->db_escape_string($babslogan)."' ,
            mail_fieldaddress='".$babDB->db_escape_string($mfa)."' ,
            mail_maxperpacket='".$babDB->db_escape_string($maxemail)."' ,
            mass_mailing='".$babDB->db_escape_string($mass_mailing)."',
            staticurl='".$babDB->db_escape_string($staticurl)."'

        where id='".$babDB->db_escape_string($_POST['item'])."'";

    $res = $babDB->db_query($req);
    if (0 != $babDB->db_affected_rows($res)) {
        bab_siteMap::clearAll();

        // redirect on list, the $babLanguage is not up to date when the sitemap is generated
        header('location:'.$GLOBALS['babUrlScript'].'?tg=sites&idx=list');
        exit;
    }

    return $_POST['item'];
}


function siteUpdate_menu2()
{
    global $babBody, $babDB;

    if( $_POST['mailfunc'] == "smtp" && empty($_POST['smtpserver']))
        {
        $babBody->msgerror = bab_translate("ERROR: You must provide server address !!");
        return false;
        }

    if( !empty($_POST['smtppass']) || !empty($_POST['smtppass2']))
        {
        if( $_POST['smtppass'] != $_POST['smtppass2'] )
            {
            $babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
            return false;
            }
        }

    if( empty($_POST['serverport']))
        {
        $_POST['serverport'] = "25";
        }



    $req = "UPDATE ".BAB_SITES_TBL." SET
            mailfunc = '".$babDB->db_escape_string($_POST['mailfunc'])."',
            smtpserver = '".$babDB->db_escape_string($_POST['smtpserver'])."',
            smtpport = '".$babDB->db_escape_string($_POST['smtpport'])."',
            smtpsecurity = '".$babDB->db_escape_string($_POST['smtpsecurity'])."',
            smtpnovalidation = '".$babDB->db_escape_string($_POST['smtpnovalidation'])."',
            smtpuser = '".$babDB->db_escape_string($_POST['smtpuser'])."',
            smtppassword=ENCODE(\"".$babDB->db_escape_string($_POST['smtppass'])."\",\"".$babDB->db_escape_string($GLOBALS['BAB_HASH_VAR'])."\")
        where id='".$babDB->db_escape_string($_POST['item'])."'";

    $babDB->db_query($req);

    return true;
}


function siteUpdate_menu3()
{
    global $babDB;

    $req = "UPDATE ".BAB_SITES_TBL." SET
            change_nickname='".$babDB->db_escape_string($_POST['change_nickname'])."',
            change_password='".$babDB->db_escape_string($_POST['change_password'])."',
            change_lang='".$babDB->db_escape_string($_POST['change_lang'])."',
            change_skin='".$babDB->db_escape_string($_POST['change_skin'])."',
            change_date='".$babDB->db_escape_string($_POST['change_date'])."',
            change_unavailability='".$babDB->db_escape_string($_POST['change_unavailability'])."',
            user_workdays='".$babDB->db_escape_string($_POST['user_workdays'])."',
            browse_users='".$babDB->db_escape_string($_POST['browse_users'])."'
        WHERE id='".$babDB->db_escape_string($_POST['item'])."'";

    $babDB->db_query($req);

    return true;
}


/**
 * Checks whether the path is an absolute path.
 * On Windows something like C:/example/path or C:\example\path or C:/example\path
 * On unix something like /example/path
 *
 * @return bool
 */
function isAbsolutePath($path)
{
    if (DIRECTORY_SEPARATOR === '\\') {
        $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
        $regexp = '#^[a-zA-Z]\:/#';
    } else {
        $regexp = '#^/#';
    }

    if (0 !== preg_match($regexp, $path)) {
        return true;
    }
    return false;
}



function bab_initUploadPath($sUploadPath)
{
    $babBody = bab_getBody();

    $sFmUploadPath				= $sUploadPath . '/fileManager';
    $sCollectiveUploadPath		= $sFmUploadPath . '/collectives';
    $sUserUploadPath			= $sFmUploadPath . '/users';
    $sCollectiveDgUploadPath	= $sCollectiveUploadPath . '/DG' . bab_getCurrentAdmGroup();

    if(!is_writable($sUploadPath))
    {
        $error = __LINE__ . ' ' . basename(__FILE__) . ' ' .
            sprintf(bab_translate(" The directory: %s is not writable"), $sUploadPath);
        $babBody->addError($error);
        return false;
    }

    if(!is_dir($sFmUploadPath))
    {
        if(!@mkdir($sFmUploadPath, 0777))
        {
            $error = __LINE__ . ' ' . basename(__FILE__) . ' ' .
                sprintf(bab_translate(" The directory: %s have not been created"), $sFmUploadPath);
            $babBody->addError($error);
        }
    }

    if(!is_dir($sCollectiveUploadPath))
    {
        if(!@mkdir($sCollectiveUploadPath, 0777))
        {
            $error = __LINE__ . ' ' . basename(__FILE__) . ' ' .
                sprintf(bab_translate(" The directory: %s have not been created"), $sCollectiveUploadPath);
            $babBody->addError($error);
        }
    }

    if(!is_dir($sCollectiveDgUploadPath))
    {
        if(!@mkdir($sCollectiveDgUploadPath, 0777))
        {
            $error = __LINE__ . ' ' . basename(__FILE__) . ' ' .
                sprintf(bab_translate(" The directory: %s have not been created"), $sCollectiveDgUploadPath);
            $babBody->addError($error);
        }
    }

    if(!is_dir($sUserUploadPath))
    {
        if(!@mkdir($sUserUploadPath, 0777))
        {
            $error = __LINE__ . ' ' . basename(__FILE__) . ' ' .
                sprintf(bab_translate(" The directory: %s have not been created"), $sUserUploadPath);
            $babBody->addError($error);
        }
    }
}



function bab_createUploadPath($uploadpath)
{
    $babBody = bab_getBody();

    $iLength = mb_strlen(trim($uploadpath));
    if($iLength > 0)
    {
        $sUploadPath = str_replace('\\', '/', trim($uploadpath));
        $iLength = mb_strlen($sUploadPath);
        if($iLength > 0)
        {

            if (!is_dir($sUploadPath)) {
                $aPaths = explode('/', $sUploadPath);
                if(is_array($aPaths) && count($aPaths) > 0)
                {
                    $sPath = '';
                    foreach($aPaths as $sPathItem)
                    {
                        if ($sPathItem) {

                            if (empty($sPath) && '/' !== mb_substr($sUploadPath,0,1)) {
                                $sPath .= $sPathItem;
                            } else {
                                $sPath .= '/' . $sPathItem;
                            }

                            if(!is_dir($sPath))
                            {
                                if(!mkdir($sPath, 0777))
                                {
                                    $babBody->addError(sprintf(bab_translate('The directory: %s have not been created'), $sPath));
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if(is_dir($sUploadPath))
            {
                bab_initUploadPath($sUploadPath);
            }
        }
    }
}




function siteUpdate_menuUpload()
{
    global $babDB, $babBody;

    $errors = false;
    if (!isAbsolutePath($_POST['uploadpath']) && !file_exists(realpath($_POST['uploadpath']))) {
        $babBody->addError(bab_translate("ERROR: The upload path must be an absolute path or relative to ovidentia root folder."));
        $errors = true;
    }


    if (!is_numeric($_POST['quota_total']) || !is_numeric($_POST['quota_folder']) || !is_numeric($_POST['maxzipsize']) || !is_numeric($_POST['maxfilesize']) || !is_numeric($_POST['folder_diskspace']) || !is_numeric($_POST['user_diskspace']) || !is_numeric($_POST['total_diskspace'])) {
        $babBody->addError(bab_translate("ERROR: You must provide all file manager size limits."));
        $errors = true;
    }

    if ($errors) {
        return false;
    }

    $imgsize = is_numeric($_POST['imgsize']) ? $_POST['imgsize'] : 25;
    $uploadpath = rtrim($_POST['uploadpath'],'/\\ ');



    $req = "UPDATE ".BAB_SITES_TBL." set
        imgsize='".$babDB->db_escape_string($imgsize)."',
        uploadpath='".$babDB->db_escape_string($uploadpath)."',
        maxfilesize='".$babDB->db_escape_string($_POST['maxfilesize'])."',
        folder_diskspace='".$babDB->db_escape_string($_POST['folder_diskspace'])."',
        user_diskspace='".$babDB->db_escape_string($_POST['user_diskspace'])."',
        total_diskspace='".$babDB->db_escape_string($_POST['total_diskspace'])."' ,
        maxzipsize='".$babDB->db_escape_string($_POST['maxzipsize'])."'  ,
        quota_total='".$babDB->db_escape_string($_POST['quota_total'])."'  ,
        quota_folder='".$babDB->db_escape_string($_POST['quota_folder'])."'
    where id='".$babDB->db_escape_string($_POST['item'])."'";
    $babDB->db_query($req);


    if (isAbsolutePath($uploadpath)) {
        bab_createUploadPath($uploadpath);
    } else {
        bab_initUploadPath($uploadpath);
    }

    return true;
}



function siteUpdate_menu5($item,$datelformat, $datesformat, $timeformat)
    {
    global $babBody, $babDB;

    $req = "update ".BAB_SITES_TBL." set date_longformat='".$babDB->db_escape_string($datelformat)."', date_shortformat='".$babDB->db_escape_string($datesformat)."', time_format='".$babDB->db_escape_string($timeformat)."' where id='".$babDB->db_escape_string($item)."'";
    $babDB->db_query($req);

    return true;
    }

function siteUpdate_menu6($item)
    {
    global $babDB;

    $reqarr = array("startday='".$_POST['startday']."'");

    if (isset($_POST['dispdays']) && count($_POST['dispdays']))
        {
        $reqarr[] = "dispdays='".$babDB->db_escape_string(implode(',',$_POST['dispdays']))."'";
        }

    if (isset($_POST['starttime']) )
        {
        $reqarr[] = "start_time='".$babDB->db_escape_string($_POST['starttime'])."'";
        }

    if (isset($_POST['endtime']) )
        {
        $reqarr[] = "end_time='".$babDB->db_escape_string($_POST['endtime'])."'";
        }

    if (isset($_POST['allday']) )
        {
        $reqarr[] = "allday='".$babDB->db_escape_string($_POST['allday'])."'";
        }

    if (isset($_POST['usebgcolor']) )
        {
        $reqarr[] = "usebgcolor='".$babDB->db_escape_string($_POST['usebgcolor'])."'";
        }

    if (isset($_POST['usecatcolor']) )
        {
        $reqarr[] = "usecatcolor='".$babDB->db_escape_string($_POST['usecatcolor'])."'";
        }

    if (isset($_POST['elapstime']) )
        {
        $reqarr[] = "elapstime='".$babDB->db_escape_string($_POST['elapstime'])."'";
        }

    if (isset($_POST['defaultview']) )
        {
        $reqarr[] = "defaultview='".$babDB->db_escape_string($_POST['defaultview'])."'";
        }

    if (isset($_POST['showupdateinfo']) )
        {
        $reqarr[] = "show_update_info='".$babDB->db_escape_string($_POST['showupdateinfo'])."'";
        }

    if (isset($_POST['showonlydaysmonthinfo']) )
        {
        $reqarr[] = "show_onlydays_of_month='".$babDB->db_escape_string($_POST['showonlydaysmonthinfo'])."'";
        }

    if (isset($_POST['iDefaultCalendarAccess']) )
        {
        $reqarr[] = "iDefaultCalendarAccess='".$babDB->db_escape_string($_POST['iDefaultCalendarAccess'])."'";
        }

    if (isset($_POST['iPersonalCalendarAccess']) )
        {
        $reqarr[] = "iPersonalCalendarAccess='".$babDB->db_escape_string($_POST['iPersonalCalendarAccess'])."'";
        }

    if (isset($_POST['calendar_notif_author']) )
        {
        $reqarr[] = "calendar_notif_author='".$babDB->db_escape_string($_POST['calendar_notif_author'])."'";
        }

    $babDB->db_query("update ".BAB_SITES_TBL." set ".implode(',',$reqarr)." where id='".$babDB->db_escape_string($item)."'");

    }

function siteUpdate_menu13($item)
    {
    global $babDB;

    if (isset($_POST['funcname']))
    {
        require_once $GLOBALS['babInstallPath'] . 'utilit/functionalityincl.php';
        $func = new bab_functionalities();
        $func->copyToParent("WorkingHours/".bab_pp('funcname'));
    }



    include_once $GLOBALS['babInstallPath']."utilit/workinghoursincl.php";
    bab_deleteAllWorkingHours(0);

    if (isset($_POST['workdays']) && count($_POST['workdays']))
        {
        $endtime = '00:00:00' == $_POST['endtime'] ? '24:00:00' : $_POST['endtime'];

        foreach($_POST['workdays'] as $day) {
            bab_insertWorkingHours(0, $day, $_POST['starttime'], $endtime);
        }
    }

    require_once $GLOBALS['babInstallPath'].'utilit/eventperiod.php';

    $event = new bab_eventPeriodModified(false, false, false);
    $event->types = BAB_PERIOD_NWDAY;

    bab_fireEvent($event);

    if (isset($_POST['nonworking']) && count($_POST['nonworking']))
        {
        $babDB->db_query("DELETE FROM ".BAB_SITES_NONWORKING_CONFIG_TBL."  where id_site='".$babDB->db_escape_string($item)."'");
        foreach($_POST['nonworking'] as $value)
            {
            $tmp = explode('#',$value);
            if (count($tmp) == 2)
                {
                $text = &$tmp[0];
                $nonworking = &$tmp[1];
                }
            else
                {
                $text = '';
                $nonworking = &$tmp[0];
                }
            $arr = explode(',',$nonworking);
            $type = $arr[0];
            $nw = isset($arr[1]) ? $arr[1] : '';

            $babDB->db_query("INSERT INTO ".BAB_SITES_NONWORKING_CONFIG_TBL." (id_site, nw_type, nw_day, nw_text)
            VALUES (
                '".$babDB->db_escape_string($item)."',
                '".$babDB->db_escape_string($type)."',
                '".$babDB->db_escape_string($nw)."',
                '".$babDB->db_escape_string($text)."')");
            }

        }
    else
        {
        $babDB->db_query("DELETE FROM ".BAB_SITES_NONWORKING_CONFIG_TBL."  where id_site='".$babDB->db_escape_string($item)."'");
        }

    bab_emptyNonWorkingDays($item);

    $babDB->db_query("update ".BAB_SITES_TBL." set id_calendar_cat=".$babDB->quote($_POST['category'])." where id='".$babDB->db_escape_string($item)."'");

    }

function siteUpdate_authentification($id, $authtype, $host, $hostname, $ldpapchkcnx, $searchdn)
{
    global $babBody, $babDB;

    $auth_multi_session = (int) bab_pp('auth_multi_session', 0);
    $auth_https = (int) bab_pp('auth_https', 0);
    $auth_fullscreen = (int) bab_pp('auth_fullscreen', 0);
    $remember_login = bab_pp('remember_login', 'N');
    $email_password = bab_pp('email_password', 'N');
    $ask_nickname = (int) bab_pp('ask_nickname', 0);

    if(!bab_functionality::get('PortalAuthentication/'.$authtype)->isConfigured() )
    {
        $errors = $babBody->errors;
        foreach ($errors as $error){
            $babBody->addNextPageError($error);
        }
    }
    else {
        $babDB->db_query("
            UPDATE ".BAB_SITES_TBL." set
            email_password=".$babDB->quote($email_password).",
            ask_nickname=".$babDB->quote($ask_nickname).",
            remember_login=".$babDB->quote($remember_login).",
            authentification='".$babDB->db_escape_string(bab_functionality::get('PortalAuthentication/'.$authtype)->getPath())."',
            auth_multi_session=".$babDB->quote($auth_multi_session).",
            auth_https=".$babDB->quote($auth_https).",
            auth_fullscreen=".$babDB->quote($auth_fullscreen)."
            where id='".$babDB->db_escape_string($id)."'
        ");
    }

    Header("Location: ". $GLOBALS['babUrlScript']."?tg=site&item=".$id);
    exit;
}

function siteUpdateRegistration($item, $rw, $rq, $ml, $cdp, $cen, $group)
{
    global $babBody, $babDB;

    $babDB->db_query("update ".BAB_SITES_TBL." set registration='".$babDB->db_escape_string($_POST['register'])."',display_disclaimer='".$babDB->db_escape_string($cdp)."', email_confirm='".$babDB->db_escape_string($cen)."', idgroup='".$babDB->db_escape_string($group)."' where id='".$babDB->db_escape_string($item)."'");
    $res = $babDB->db_query("select id from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$babDB->db_escape_string($item)."'");
    while( $arr = $babDB->db_fetch_array($res))
        {
        if( count($rw) > 0 && in_array($arr['id'], $rw))
            {
            $registration = "Y";
            }
        else
            {
            $registration = "N";
            }
        if( count($rq) > 0 && in_array($arr['id'], $rq))
            {
            $required = "Y";
            }
        else
            {
            $required = "N";
            }
        if( count($ml) > 0 && in_array($arr['id'], $ml))
            {
            $multilignes = "Y";
            }
        else
            {
            $multilignes = "N";
            }
        $req = "update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='".$babDB->db_escape_string($registration)."', required='".$babDB->db_escape_string($required)."', multilignes='".$babDB->db_escape_string($multilignes)."' where id='".$babDB->db_escape_string($arr['id'])."'";
        $babDB->db_query($req);
        }
}

function confirmDeleteSite($id)
    {
    global $babDB;
    // delete homepages
    $babDB->db_query("delete from ".BAB_HOMEPAGES_TBL." where id_site='".$babDB->db_escape_string($id)."'");
    // delete ldap settings
    $babDB->db_query("delete from ".BAB_LDAP_SITES_FIELDS_TBL." where id_site='".$babDB->db_escape_string($id)."'");
    // delete registration settings
    $babDB->db_query("delete from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$babDB->db_escape_string($id)."'");

    $babDB->db_query("delete from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$babDB->db_escape_string($id)."'");
    // delete site
    $babDB->db_query("delete from ".BAB_SITES_TBL." where id='".$babDB->db_escape_string($id)."'");
    bab_siteMap::clearAll();
    Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
    exit;
    }

function siteUpdateDisclaimer($item)
    {
    global $babDB;

    include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
    $editor = new bab_contentEditor('bab_disclaimer');
    $content = $editor->getContent();

    $db = &$GLOBALS['babDB'];

    $babDB->db_query("update ".BAB_SITES_DISCLAIMERS_TBL." set disclaimer_text='".$babDB->db_escape_string($content)."' where id_site='".$babDB->db_escape_string($item)."'");
    return true;
    }



function call_record_site_menu11($item) {

    // search interface

    require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';

    $func = new bab_functionalities();

    if ($interface = bab_pp('interface', 'Default')) {
        $func->copyToParent('SearchUi/'.$interface);
    }


    $arr = bab_searchEngineInfos();
    if (false === $arr)
        return false;

    switch($arr['name'])
        {
        case 'swish':
            include $GLOBALS['babInstallPath']."admin/sitesearch.swish.php";
            break;
        }

    record_site_menu11($item);
}



/* main */
if( !bab_isUserLogged() ||  !bab_isUserAdministrator())
{
    $babBody->msgerror = bab_translate("Access denied");
    return;
}

$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : 'menusite';



if(isset($_REQUEST['action']) && $_REQUEST['action'] == "Yes") {
    bab_requireDeleteMethod() && confirmDeleteSite($_REQUEST['item']);
}


if (isset($_POST['action']))
{
bab_requireSaveMethod();

switch ($_POST['action'])
    {
    case 'menu1':
        $menu1 = siteUpdate_menu1();
        if (!$menu1)
            $idx = 'menu1';
        else {

            bab_siteMap::clearAll();

            header('location:'.$GLOBALS['babUrlScript']."?tg=site&item=".$menu1);
            exit;
        }
        break;

    case 'menu2':
        if (!siteUpdate_menu2())
            $idx = 'menu2';
        break;

    case 'menu3':
        if (!siteUpdate_menu3())
            $idx = 'menu3';
        break;

    case 'menu4':
        if (!siteUpdate_menuUpload())
            $idx = 'menu4';
        break;

    case 'menu5':

        if(!siteUpdate_menu5($_POST['item'],$_POST['date_longformat'], $_POST['date_shortformat'], $_POST['time_format']))
            $idx = 'menu5';

        break;

    case 'menu6':
        siteUpdate_menu6($_POST['item']);
        break;


    case 'menu8':

        $hostname = isset($_POST['hostname']) ? $_POST['hostname'] : '';
        $ldpapchkcnx = isset($_POST['ldpapchkcnx']) ? $_POST['ldpapchkcnx'] : 'N';
        $searchdn = isset($_POST['searchdn']) ? $_POST['searchdn'] : '';
        $host = isset($_POST['host']) ? $_POST['host'] : '';

        if(!siteUpdate_authentification($_POST['item'], $_POST['authtype'], $host, $hostname, $ldpapchkcnx, $searchdn)){
            $idx = "menu8";
        }


        break;

    case 'menu9':
        $ml = isset($_POST['ml']) ? $_POST['ml'] : array();
        $rw = isset($_POST['rw']) ? $_POST['rw'] : array();
        $req = isset($_POST['req']) ? $_POST['req'] : array();
        $cdp = isset($_POST['cdp']) ? $_POST['cdp'] : 'N';
        $cen = isset($_POST['cen']) ? $_POST['cen'] : 'O';

        siteUpdateRegistration($_POST['item'], $rw, $req, $ml, $cdp, $cen, $_POST['group']);
        Header("Location: ". $GLOBALS['babUrlScript']."?tg=site&idx=menusite&item=".$_POST['item']);
        exit;
        break;

    case 'menu10':
        record_editor_configuration($_POST['item']);
        break;

    case 'menu11':
        call_record_site_menu11($_POST['item']);
        break;

    case 'menu13':
        siteUpdate_menu13($_POST['item']);
        break;

    case 'updisc':
        siteUpdateDisclaimer($_POST['item']);
        $popupmessage = bab_translate("Update done");
        break;
    }
}


if( isset($_REQUEST['aclman']) || isset($_REQUEST['acluws']))
    {
    maclGroups();
    }


$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");



if (0 === mb_strpos($idx, 'menu') && is_numeric(mb_substr($idx, 4))) {
    bab_siteMap::setPosition('bab', 'AdminSite'.bab_rp('item').ucfirst($idx));
}


switch($idx)
    {
    case "unload":
        include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
        popupUnload(bab_rp('popupmessage'), bab_rp('refreshurl'));
        exit;
    case "editdp":
        editDisclaimerPrivacy($_REQUEST['item'], bab_rp('content'));
        exit;
        break;

    case "Delete":
        $babBody->title = getSiteName($_REQUEST['item']);
        sectionDelete($_REQUEST['item']);
        $babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
        $babBody->addItemMenu("menusite", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=site&idx=Delete&item=".$_REQUEST['item']);
        break;

    case "create":
    case "menu1":
        site_menu1();
        if (empty($_REQUEST['item']))
            {

            $babBody->addItemMenu("create", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=site&idx=create");
            }
        else
            {
            $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
            $babBody->addItemMenu("menu1", bab_translate("Modify"), '');
            }
        break;

    case "menu2":
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu2", bab_translate("Modify"),'');
        site_menu2($_REQUEST['item']);
        break;

    case "menu3":
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu3", bab_translate("Modify"),'');
        site_menu3($_REQUEST['item']);
        break;

    case "menu4":
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu4", bab_translate("Modify"),'');
        site_menu4($_REQUEST['item']);
        break;

    case "menu5":
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu5", bab_translate("Modify"),'');
        site_menu5($_REQUEST['item']);
        break;

    case "menu6":
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu6", bab_translate("Modify"),'');
        site_menu6($_REQUEST['item']);
        break;

    case "menu7":
        $babBody->title = bab_translate("Home pages managers").": ".getSiteName($_REQUEST['item']);
        $macl = new macl("site", "menusite", $_REQUEST['item'], "aclman");
        $macl->addtable( BAB_SITES_HPMAN_GROUPS_TBL,bab_translate("Who can manage home pages for this site?"));
        $macl->filter(0,0,1,1,1);
        $macl->babecho();
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu7", bab_translate("Managers"),'');
        break;

    case "menu8":
        $babBody->title = bab_translate("Authentification").": ".getSiteName($_REQUEST['item']);
        siteAuthentification($_REQUEST['item']);
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu8", bab_translate("Authentification"),'');
        break;

    case "menu9":
        $babBody->title = bab_translate("Registration").": ".getSiteName($_REQUEST['item']);
        include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
        siteRegistration($_REQUEST['item']);
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu9", bab_translate("Registration"),'');
        break;

    case "menu10":
        $babBody->title = bab_translate("Editor").": ".getSiteName($_REQUEST['item']);
        editor_configuration($_REQUEST['item']);
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu10", bab_translate("Editor"),'');
        break;

    case "menu11":
        $babBody->title = bab_translate("Search engine");
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu11", bab_translate("Search2"),'');

        call_site_menu11($_REQUEST['item']);

        break;

    case "menu12":
        $babBody->title = bab_translate("Web services").": ".getSiteName($_REQUEST['item']);
        $macl = new macl("site", "menusite", $_REQUEST['item'], "acluws");
        $macl->addtable( BAB_SITES_WS_GROUPS_TBL,bab_translate("Who can connect as user of web services").'?');
        $macl->addtable( BAB_SITES_WSOVML_GROUPS_TBL,bab_translate("Who can use OVML containers").'?');
        $macl->addtable( BAB_SITES_WSFILES_GROUPS_TBL,bab_translate("Who can use OVML files").'?');
        $macl->babecho();
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu12", bab_translate("Web services"),'');
        break;

    case "menu13":
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->addItemMenu("menu13", bab_translate("Modify"),'');
        site_menu13($_REQUEST['item']);
        break;

    default:
    case 'menusite':
        $babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
        $babBody->title = getSiteName($_REQUEST['item']);
        siteMenu($_REQUEST['item']);
        bab_siteMap::setPosition('bab','AdminSite'.bab_rp('item'));
        break;
    }

$babBody->setCurrentItemMenu($idx);
