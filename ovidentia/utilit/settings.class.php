<?php
//-------------------------------------------------------------------------
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
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */



class bab_Settings
{
	private $siteSettings = null;

	private $userSettings = null;

	/**
	 * Get get current site settings
	 * @throws ErrorException
	 * @return array
	 */
	public function getSiteSettings()
	{

		if (null === $this->siteSettings)
		{
			global $babDB;

			$BAB_HASH_VAR = bab_getHashVar();

			$req="select *, DECODE(smtppassword, \"".$babDB->db_escape_string($BAB_HASH_VAR)."\") as smtppass, DECODE(ldap_adminpassword, \"".$babDB->db_escape_string($BAB_HASH_VAR)."\") as ldap_adminpassword from bab_sites";

			if (isset($GLOBALS['babSiteName'])) {
			    $req .= " where name='".$babDB->db_escape_string($GLOBALS['babSiteName'])."'";
			}

			$res=$babDB->db_query($req);
			if ($babDB->db_num_rows($res) == 0)
			{
				throw new ErrorException("Configuration error : babSiteName in config.php not match site name in administration sites configuration");
			}
			$arr = $babDB->db_fetch_assoc($res);
			$this->siteSettings = $arr;

		}

		return $this->siteSettings;
	}

    /**
     * Get absolute upload path
     *
     * The value can be overriden by the global variable $babUploadPath.
     *
     * @return string
     */
    public function getUploadPath()
    {
        if (isset($GLOBALS['babUploadPath'])){
            return $GLOBALS['babUploadPath'];
        }

        $site = $this->getSiteSettings();

        if ('' === $site['uploadpath']) {
            return '';
        }

        return realpath($site['uploadpath']);
    }


	public function setForCurrentSite($key, $value)
	{
		global $babDB;
		$babDB->db_query('UPDATE bab_sites SET '.$babDB->backTick($key).'='.$babDB->quote($value).' WHERE name='.$babDB->quote($GLOBALS['babSiteName']));
	}

	public function setForAllSites($key, $value)
	{
		global $babDB;
		$babDB->db_query('UPDATE bab_sites SET '.$babDB->backTick($key).'='.$babDB->quote($value));
	}

	/**
	 * @return array()
	 */
	public function getUserSettings()
	{
        if (!bab_isUserLogged()) {
            return null;
        }

	    if (null === $this->userSettings)
	    {
	        global $babDB;
	        $res = $babDB->db_query('
	            SELECT
	                lang,
	                skin,
	                style,
	                lastlog,
	                langfilter,
	                date_shortformat,
	                date_longformat,
	                time_format
	            FROM
	                '.BAB_USERS_TBL.'
	            WHERE id='.$babDB->quote(bab_getUserId())
	        );

	        if (!$res || 0 === $babDB->db_num_rows($res))
	        {
	            $this->userSettings = false;
	        } else {
	            $this->userSettings = $babDB->db_fetch_assoc($res);
	        }
	    }

	    return $this->userSettings;
	}


	/**
	 * Get site language
	 * @return string
	 */
	public function getSiteLanguage()
	{
	    $site = $this->getSiteSettings();

	    if (!empty($site['lang'])) {
	        return $site['lang'];
	    }

	    // detect from browser setting

	    if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"])) {

	        $arrLanguages = bab_getAvailableLanguages();
	        $previousPos = 1000;
	        $bLang = null;
	        $accepted = mb_strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"]);

	        foreach ($arrLanguages as $bLangTmp) {
	            $pos = mb_strpos($accepted, $bLangTmp);
	            if (false === $pos) {
	                continue;
	            }
	            if ($previousPos > $pos) {
	                $previousPos = $pos;
	                $bLang = $bLangTmp;
	            }
	        }

	        if (isset($bLang)) {
	            return $bLang;
	        }

	    }



	    // default to FR

	    return 'fr';
	}


	/**
	 * @return bab_Settings
	 */
	public static function get()
	{
	    return bab_getInstance(__CLASS__);
	}
}