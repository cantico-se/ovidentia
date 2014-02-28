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
require_once 'base.php';



class bab_Settings
{
	private $siteSettings = null;
	
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
			
			$req="select *, DECODE(smtppassword, \"".$babDB->db_escape_string($BAB_HASH_VAR)."\") as smtppass, DECODE(ldap_adminpassword, \"".$babDB->db_escape_string($BAB_HASH_VAR)."\") as ldap_adminpassword from ".BAB_SITES_TBL." where name='".$babDB->db_escape_string($GLOBALS['babSiteName'])."'";
			$res=$babDB->db_query($req);
			if ($babDB->db_num_rows($res) == 0)
			{
				throw new ErrorException(bab_translate("Configuration error : babSiteName in config.php not match site name in administration sites configuration"));
			}
			$arr = $babDB->db_fetch_assoc($res);
			$this->siteSettings = $arr;
		
		}
		
		return $this->siteSettings;
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
}